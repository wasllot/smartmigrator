<?php
namespace SmartMigrator\Repository;

use Db;

class QueueRepository
{
    private $table = 'shopify_importer_queue';

    public function saveAnalyzedGroup($handle, $data)
    {
        return Db::getInstance()->insert($this->table, [
            'handle' => pSQL($handle),
            'data' => pSQL(json_encode($data)),
            'status' => 3, // Analyzed
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAnalyzedItems()
    {
        return Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . $this->table . ' WHERE status = 3');
    }

    public function getReadyToImport($limit = 1)
    {
        return Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . $this->table . ' WHERE status = 3 LIMIT ' . (int) $limit);
    }

    public function getSuccessItems()
    {
        return Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . $this->table . ' WHERE status = 1');
    }

    public function updateStatus($id, $status, $msg = null)
    {
        $update = ['status' => (int) $status];
        if ($msg)
            $update['error_msg'] = pSQL($msg);

        return Db::getInstance()->update($this->table, $update, 'id_queue = ' . (int) $id);
    }

    public function delete($id)
    {
        return Db::getInstance()->delete($this->table, 'id_queue = ' . (int) $id);
    }

    public function clear()
    {
        return Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . $this->table);
    }
}
