<?php
namespace SmartMigrator\Service;

use SmartMigrator\Repository\QueueRepository;
use Product;
use Db;

class BackupService
{
    private $moduleName;
    private $queueRepo;

    public function __construct($moduleName, QueueRepository $queueRepo)
    {
        $this->moduleName = $moduleName;
        $this->queueRepo = $queueRepo;
    }

    public function createBackupAndDelete()
    {
        $items = $this->queueRepo->getSuccessItems();

        if (empty($items))
            return 0;

        // 1. Create Backup
        $backupDir = _PS_MODULE_DIR_ . $this->moduleName . '/backups/';
        if (!is_dir($backupDir))
            mkdir($backupDir, 0755, true);

        $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backupFile, json_encode($items));

        $count = 0;
        foreach ($items as $item) {
            $handle = $item['handle'];
            $id_product = Db::getInstance()->getValue('SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference = "' . pSQL($handle) . '"');

            if ($id_product) {
                $p = new Product($id_product);
                $p->delete();
                $count++;
            }

            // Reset Status
            $this->queueRepo->updateStatus($item['id_queue'], 3, NULL); // 3 = Analyzed
        }

        return $count;
    }
}
