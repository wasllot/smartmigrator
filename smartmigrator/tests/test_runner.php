<?php
// Simple Test Runner for CI/CD

namespace PHPUnit\Framework;

if (!class_exists('PHPUnit\Framework\TestCase')) {
    class TestCase
    {
        public function assertStringStartsWith($prefix, $string, $message = '')
        {
            if (strpos($string, $prefix) !== 0) {
                echo "FAIL: '$string' does not start with '$prefix'. $message\n";
                exit(1);
            }
        }

        public function assertNotEquals($expected, $actual, $message = '')
        {
            if ($expected == $actual) {
                echo "FAIL: '$actual' should not equal '$expected'. $message\n";
                exit(1);
            }
        }
    }
}

// ---------------------------------------------------------

require_once __DIR__ . '/Unit/CsvAnalyzerTest.php';

use SmartMigrator\Tests\Unit\CsvAnalyzerTest;

echo "Running Tests...\n";
$test = new CsvAnalyzerTest();

// Relfection to run all test* methods
$methods = get_class_methods($test);
$passed = 0;
$total = 0;

foreach ($methods as $method) {
    if (strpos($method, 'test') === 0) {
        $total++;
        echo "Running $method... ";
        try {
            if (method_exists($test, 'setUp')) {
                $reflectionMethod = new \ReflectionMethod($test, 'setUp');
                $reflectionMethod->setAccessible(true);
                $reflectionMethod->invoke($test);
            }

            $test->$method();
            echo "OK\n";
            $passed++;
        } catch (\Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

echo "\nSummary: $passed / $total passed.\n";
exit(0);
