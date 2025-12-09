<?php
// File: tests/unit/MvpExceptionTest.php

require_once(__DIR__ . '/../../lib/MvpException.php');

class MvpExceptionTest {

    public function testBaseExceptionMessage() {
        try {
            throw new MvpException("Test error message", 500);
        } catch (MvpException $e) {
            assert($e->getMessage() === "Test error message");
            assert($e->getCode() === 500);
            echo "✓ Base exception message test passed\n";
        }
    }

    public function testConnectionExceptionInheritance() {
        $exception = new MvpConnectionException("Connection failed");
        assert($exception instanceof MvpException);
        echo "✓ Connection exception inheritance test passed\n";
    }

    public function testQueryExceptionContext() {
        $exception = new MvpQueryException("Query failed", 0, [
            'sql' => 'SELECT * FROM test',
            'params' => ['value1']
        ]);

        $context = $exception->getContext();
        assert($context['sql'] === 'SELECT * FROM test');
        assert($context['params'] === ['value1']);
        echo "✓ Query exception context test passed\n";
    }

    public function testLogFormat() {
        $exception = new MvpDataException("Duplicate key", 0, [
            'key' => 'test_key',
            'value' => 'test_value'
        ]);

        $logFormat = $exception->toLogFormat();
        assert(strpos($logFormat, 'MvpDataException') !== false);
        assert(strpos($logFormat, 'Duplicate key') !== false);
        assert(strpos($logFormat, 'test_key') !== false);
        echo "✓ Log format test passed\n";
    }

    public function testDetailedMessage() {
        $exception = new MvpException("Test error", 0, [
            'detail1' => 'value1',
            'detail2' => 'value2'
        ]);

        $detailed = $exception->getDetailedMessage();
        assert(strpos($detailed, 'Test error') !== false);
        assert(strpos($detailed, 'MvpException.php') !== false); // File location
        assert(strpos($detailed, 'detail1') !== false);
        assert(strpos($detailed, 'value1') !== false);
        echo "✓ Detailed message test passed\n";
    }

    public function runAllTests() {
        echo "=== Running MvpException Tests ===\n";
        $this->testBaseExceptionMessage();
        $this->testConnectionExceptionInheritance();
        $this->testQueryExceptionContext();
        $this->testLogFormat();
        $this->testDetailedMessage();
        echo "✅ All MvpException tests passed (5 tests)\n\n";
    }
}

// Run tests
$tester = new MvpExceptionTest();
$tester->runAllTests();
?>
