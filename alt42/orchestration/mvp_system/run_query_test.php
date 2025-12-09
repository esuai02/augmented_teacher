<?php
// File: run_query_test.php
// Standalone test runner that bypasses authentication

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== MVP Database Query Test Runner ===\n\n";

// Include test file
require_once(__DIR__ . '/tests/unit/MvpDatabaseQueryTest.php');

echo "</pre>";
?>
