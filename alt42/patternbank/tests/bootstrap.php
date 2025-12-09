<?php
/**
 * Test Bootstrap
 * Loads Moodle environment for testing
 */

// Moodle configuration
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Autoload library files
spl_autoload_register(function($class) {
    $libPath = __DIR__ . '/../lib/' . $class . '.php';
    if (file_exists($libPath)) {
        require_once($libPath);
    }
});

echo "Test environment initialized\n";
