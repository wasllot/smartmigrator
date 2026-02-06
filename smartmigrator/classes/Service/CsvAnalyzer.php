<?php
namespace SmartMigrator\Service;

use SmartMigrator\Service\ColumnMapper;

class CsvAnalyzer
{
    private $mapper;

    public function __construct()
    {
        $this->mapper = new ColumnMapper();
    }

    /**
     * Parses CSV and groups products by Handle
     */
    public function analyze($filePath)
    {
        $handle = fopen($filePath, "r");
        if ($handle === FALSE) {
            throw new \Exception('Cannot open CSV file.');
        }

        // Get Headers
        $headers = fgetcsv($handle, 0, ",");
        $headers = array_map('trim', $headers);

        // Compute Mapping
        $mapping = $this->mapper->getMappedHeaders($headers);

        if (empty($mapping['handle']) && empty($mapping['title'])) {
            throw new \Exception('Could not detect "Handle" or "Title" columns. Please verify CSV headers.');
        }

        $products = [];
        $lastHandle = null;

        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($headers) !== count($data))
                continue;
            $row = array_combine($headers, $data);

            // Normalize Row using Mapper
            $row = $this->mapper->mapRow($row, $mapping);

            // Logic 1: Handle/Grouping Detection
            // If explicit 'handle' exists, use it. If not, fallback to 'title' (simple grouping)
            $productHandle = !empty($row['handle']) ? $row['handle'] : (!empty($row['title']) ? $this->slugify($row['title']) : null);

            // Logic 2: Inherit Handle if empty (Spreadsheet style)
            if (empty($productHandle) && $lastHandle) {
                // If it's a variant line without handle/title, assume it belongs to last parent
                $productHandle = $lastHandle;
                // Backfill handle in row for consistency
                $row['handle'] = $productHandle;
            }

            if (empty($productHandle))
                continue;

            $lastHandle = $productHandle;

            if (!isset($products[$productHandle])) {
                $products[$productHandle] = [];
            }
            $products[$productHandle][] = $row;
        }
        fclose($handle);

        if (empty($products)) {
            throw new \Exception('No valid products found in CSV.');
        }

        return $this->processGroups($products);
    }

    private function slugify($text)
    {
        // Simple slugify for fallback handle
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return $text;
    }

    private function processGroups($products)
    {
        $processed = [];
        foreach ($products as $handle => $rows) {
            $parentTitle = $this->findParentTitle($rows, $handle);

            foreach ($rows as &$row) {
                // Smart SKU Generation if missing
                if (empty($row['sku'])) {
                    $row['sku'] = $this->generateSmartSku($parentTitle, $row);
                    $row['Variant SKU'] = $row['sku']; // Backwards compatibility for now
                    $row['_generated_sku'] = true;
                }
            }
            $processed[$handle] = $rows;
        }
        return $processed;
    }

    private function findParentTitle($rows, $defaultHandle)
    {
        foreach ($rows as $r) {
            if (!empty($r['title']))
                return $r['title'];
        }
        return $defaultHandle;
    }

    public function generateSmartSku($parentTitle, $row)
    {
        // Format: SM-[TITLE_PART]-[OPTIONS]-[RAND]
        $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '', $parentTitle);
        $titlePart = strtoupper(substr($cleanTitle, 0, 3));

        $optionPart = '00';
        // Use normalized option keys
        if (!empty($row['option1_value']) && $row['option1_value'] != 'Default Title') {
            $cleanOption = preg_replace('/[^a-zA-Z0-9]/', '', $row['option1_value']);
            $optionPart = strtoupper(substr($cleanOption, 0, 2));
        }

        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));

        return "SM-{$titlePart}{$optionPart}-{$random}"; // Changed prefix to SM (SmartMigrator)
    }
}
