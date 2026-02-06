<?php
namespace SmartMigrator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SmartMigrator\Service\CsvAnalyzer;

require_once __DIR__ . '/../../classes/Service/ColumnMapper.php';
require_once __DIR__ . '/../../classes/Service/CsvAnalyzer.php';

class CsvAnalyzerTest extends TestCase
{
    private $analyzer;

    protected function setUp()
    {
        $this->analyzer = new CsvAnalyzer();
    }

    public function testGenerateSmartSku()
    {
        // Case 1: Standard Title + Option (Using mapped keys)
        $sku = $this->analyzer->generateSmartSku("Cool T-Shirt", ['option1_value' => 'Size L']);
        $this->assertStringStartsWith('SM-COOSI', $sku);

        // Case 2: Parent Title Only (Default Title)
        $sku2 = $this->analyzer->generateSmartSku("My Product", ['option1_value' => 'Default Title']);
        $this->assertStringStartsWith('SM-MYP00', $sku2);

        // Case 3: Randomness check
        $sku3 = $this->analyzer->generateSmartSku("Cool T-Shirt", ['option1_value' => 'Size L']);
        $this->assertNotEquals($sku, $sku3);
    }
}
