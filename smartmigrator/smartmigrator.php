<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/Service/CsvAnalyzer.php';
require_once __DIR__ . '/classes/Service/BackupService.php';
require_once __DIR__ . '/classes/Repository/QueueRepository.php';

use SmartMigrator\Service\CsvAnalyzer;
use SmartMigrator\Service\BackupService;
use SmartMigrator\Repository\QueueRepository;

class SmartMigrator extends Module
{
    private $csvAnalyzer;
    private $backupService;
    private $queueRepo;

    public function __construct()
    {
        $this->name = 'smartmigrator';
        $this->tab = 'migration_tools';
        $this->version = '2.0.0';
        $this->author = 'Reinaldo Tineo';
        $this->email = 'rei.vzl@gmail.com';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Smart Product Migrator');
        $this->description = $this->l('Universal Smart Import from ANY CSV.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        // Manual Dependency Injection
        $this->queueRepo = new QueueRepository();
        $this->csvAnalyzer = new CsvAnalyzer();
        $this->backupService = new BackupService($this->name, $this->queueRepo);
    }

    public function install()
    {
        return parent::install() && $this->installDb();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDb();
    }

    public function installDb()
    {
        // Status: 0=Pending, 1=Success, 2=Error, 3=Analyzed/Ready
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shopify_importer_queue` (
            `id_queue` INT(11) NOT NULL AUTO_INCREMENT,
            `handle` VARCHAR(255) NULL,
            `data` LONGTEXT NOT NULL,
            `status` TINYINT(1) NOT NULL DEFAULT 0,
            `error_msg` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_queue`),
            KEY `status` (`status`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'shopify_importer_queue`');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'process_batch') {
            $this->processQueueBatch();
            exit;
        }

        if (Tools::isSubmit('submitShopifyImport')) {
            $output .= $this->postProcess();
        }

        if (Tools::isSubmit('delete_queue_item')) {
            $id = (int) Tools::getValue('id_queue');
            $this->queueRepo->delete($id);
            $output .= $this->displayConfirmation($this->l('Item removed from queue.'));
        }

        if (Tools::isSubmit('clear_queue')) {
            $this->queueRepo->clear();
            $output .= $this->displayConfirmation($this->l('Queue cleared.'));
        }

        if (Tools::isSubmit('delete_imported')) {
            $count = $this->backupService->createBackupAndDelete();
            $output .= $this->displayConfirmation($this->l("Deleted {$count} products and reset to Staging area (Backup saved)."));
        }

        return $output . $this->renderStagingArea() . $this->renderForm();
    }

    public function postProcess()
    {
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $file = $_FILES['file'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

            if ($ext != 'csv') {
                return $this->displayError($this->l('Invalid file format. Please upload a CSV.'));
            }

            $target_file = _PS_MODULE_DIR_ . $this->name . '/uploads/import.csv';
            if (!is_dir(dirname($target_file)))
                mkdir(dirname($target_file), 0755, true);

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                try {
                    $groupedProducts = $this->csvAnalyzer->analyze($target_file);
                    $count = 0;
                    foreach ($groupedProducts as $handle => $rows) {
                        $this->queueRepo->saveAnalyzedGroup($handle, $rows);
                        $count++;
                    }
                    return $this->displayConfirmation($this->l("Analysis Complete. Found {$count} products."));
                } catch (Exception $e) {
                    return $this->displayError($e->getMessage());
                }
            } else {
                return $this->displayError($this->l('Error uploading file.'));
            }
        }
    }

    public function renderStagingArea()
    {
        $items = $this->queueRepo->getAnalyzedItems();

        if (empty($items))
            return '';

        foreach ($items as &$item) {
            $data = json_decode($item['data'], true);
            $name = $item['handle'];
            $image = '';

            foreach ($data as $d) {
                if (!empty($d['Title']))
                    $name = $d['Title'];
                if (!empty($d['Image Src']))
                    $image = $d['Image Src'];
                if ($name != $item['handle'] && $image != '')
                    break;
            }

            $item['name'] = $name;
            $item['variants_count'] = count($data);
            $item['image'] = $image;
            $skuPreview = isset($data[0]['Variant SKU']) ? $data[0]['Variant SKU'] : '';
            $item['generated_sku'] = isset($data[0]['_generated_sku']) ? $skuPreview . ' (Auto)' : $skuPreview;
        }

        $this->context->smarty->assign([
            'analyzed_items' => $items,
            'import_url' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' . $this->name . '&ajax=1&action=process_batch',
            'base_url' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name
        ]);

        return $this->display(__FILE__, 'views/templates/admin/staging_area.tpl');
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Upload Shopify CSV'),
                    'icon' => 'icon-cloud-upload',
                ],
                'input' => [
                    [
                        'type' => 'file',
                        'label' => $this->l('CSV File'),
                        'name' => 'file',
                        'required' => true,
                        'desc' => $this->l('Upload your Shopify exported CSV file.')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Analyze File'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitShopifyImport';

        return $helper->generateForm([$fields_form]);
    }

    public function processQueueBatch()
    {
        $limit = 1;
        $queueItems = $this->queueRepo->getReadyToImport($limit);

        if (empty($queueItems)) {
            die(json_encode(['done' => true, 'msg' => 'All items processed.']));
        }

        foreach ($queueItems as $item) {
            $productData = json_decode($item['data'], true);
            $handle = $item['handle'];

            try {
                $parentRow = $productData[0];
                foreach ($productData as $pRow) {
                    if (!empty($pRow['Title'])) {
                        $parentRow = $pRow;
                        break;
                    }
                }

                $id_product = $this->importProduct($parentRow, $handle);

                foreach ($productData as $variantRow) {
                    $this->importCombination($id_product, $variantRow);
                }

                $this->queueRepo->updateStatus($item['id_queue'], 1, 'Imported Successfully');

            } catch (Exception $e) {
                $this->queueRepo->updateStatus($item['id_queue'], 2, $e->getMessage());
            }
        }

        $remaining = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'shopify_importer_queue WHERE status = 3');

        die(json_encode([
            'done' => false,
            'remaining' => $remaining,
            'msg' => "Processed {$handle}. Remaining: {$remaining}"
        ]));
    }

    // Keep Import Logic Private/Protected (Simulating ProductImporter Service)
    protected function importProduct($row, $handle)
    {
        $id_product = (int) Db::getInstance()->getValue('SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference = "' . pSQL($handle) . '"');

        $product = new Product($id_product ? $id_product : null);
        $product->name = [Configuration::get('PS_LANG_DEFAULT') => $row['Title']];
        $product->description = [Configuration::get('PS_LANG_DEFAULT') => $row['Body (HTML)']];
        $product->reference = $handle;
        $product->price = (float) $row['Variant Price'];
        $product->id_tax_rules_group = 0;
        $product->active = strtolower($row['Status']) == 'active' ? 1 : 0;
        $product->redirect_type = '404';

        if (!empty($row['Vendor'])) {
            $product->id_manufacturer = $this->getManufacturerId($row['Vendor']);
        }

        if (!empty($row['Type'])) {
            $product->id_category_default = $this->getCategoryId($row['Type']);
            $product->addToCategories([$product->id_category_default]);
        }

        $product->save();
        StockAvailable::setQuantity($product->id, 0, (int) $row['Variant Inventory Qty']);

        if (!empty($row['Image Src'])) {
            $this->uploadImage($product->id, null, $row['Image Src'], true);
        }

        return $product->id;
    }

    protected function importCombination($id_product, $row)
    {
        if ($row['Option1 Name'] == 'Title' && $row['Option1 Value'] == 'Default Title') {
            return;
        }

        $attributes = [];
        for ($i = 1; $i <= 3; $i++) {
            $name = $row["Option$i Name"];
            $value = $row["Option$i Value"];
            if (!empty($name) && !empty($value)) {
                $attributes[] = $this->getAttributeId($name, $value);
            }
        }

        if (empty($attributes))
            return;

        $product = new Product($id_product);
        $id_product_attribute = $product->addCombinationEntity(
            (float) $row['Variant Price'],
            (float) $row['Variant Weight Unit'] == 'kg' ? (float) $row['Variant Grams'] / 1000 : 0,
            0,
            0,
            0,
            null,
            $row['Variant SKU'],
            0,
            0,
            1,
            null
        );

        $combination = new Combination($id_product_attribute);
        $combination->setAttributes($attributes);
        $combination->quantity = (int) $row['Variant Inventory Qty'];
        $combination->save();

        if (!empty($row['Variant Image'])) {
            $this->uploadImage($id_product, $id_product_attribute, $row['Variant Image']);
        }
    }

    protected function getManufacturerId($name)
    {
        $id = Manufacturer::getIdByName($name);
        if (!$id) {
            $m = new Manufacturer();
            $m->name = $name;
            $m->active = 1;
            $m->save();
            return $m->id;
        }
        return $id;
    }

    protected function getCategoryId($name)
    {
        $id = Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category_lang WHERE name = "' . pSQL($name) . '"');
        if (!$id) {
            $c = new Category();
            $c->name = [Configuration::get('PS_LANG_DEFAULT') => $name];
            $c->link_rewrite = [Configuration::get('PS_LANG_DEFAULT') => Tools::link_rewrite($name)];
            $c->active = 1;
            $c->id_parent = 2; // Home
            $c->save();
            return $c->id;
        }
        return $id;
    }

    protected function getAttributeId($groupName, $valueName)
    {
        $id_group = Db::getInstance()->getValue('SELECT id_attribute_group FROM ' . _DB_PREFIX_ . 'attribute_group_lang WHERE name = "' . pSQL($groupName) . '"');
        if (!$id_group) {
            $g = new AttributeGroup();
            $g->name = [Configuration::get('PS_LANG_DEFAULT') => $groupName];
            $g->public_name = [Configuration::get('PS_LANG_DEFAULT') => $groupName];
            $g->group_type = 'select';
            $g->save();
            $id_group = $g->id;
        }

        $id_attr = Db::getInstance()->getValue('
            SELECT a.id_attribute 
            FROM ' . _DB_PREFIX_ . 'attribute a
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (a.id_attribute = al.id_attribute)
            WHERE a.id_attribute_group = ' . (int) $id_group . '
            AND al.name = "' . pSQL($valueName) . '"
        ');

        if (!$id_attr) {
            $a = new Attribute();
            $a->id_attribute_group = $id_group;
            $a->name = [Configuration::get('PS_LANG_DEFAULT') => $valueName];
            $a->save();
            $id_attr = $a->id;
        }

        return $id_attr;
    }

    protected function uploadImage($id_product, $id_product_attribute, $url, $isCover = false)
    {
        $image = new Image();
        $image->id_product = $id_product;
        $image->position = Image::getHighestPosition($id_product) + 1;
        $image->cover = $isCover;

        if ($image->save()) {
            $temp = _PS_TMP_IMG_DIR_ . md5($url);
            if (@copy($url, $temp)) {
                $path = $image->getPathForCreation();
                ImageManager::resize($temp, $path . '.jpg');
                $imagesTypes = ImageType::getImagesTypes('products');
                foreach ($imagesTypes as $imageType) {
                    ImageManager::resize($temp, $path . '-' . stripslashes($imageType['name']) . '.jpg', $imageType['width'], $imageType['height']);
                }
            }
        }

        if ($id_product_attribute) {
            $result = Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'product_attribute_image` (`id_product_attribute`, `id_image`) VALUES (' . (int) $id_product_attribute . ', ' . (int) $image->id . ')');
        }
    }
}
