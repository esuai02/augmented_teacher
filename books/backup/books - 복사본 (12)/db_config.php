<?php
/**
 * Database configuration for standalone operation
 * Update these settings to match your database configuration
 */

return [
    'host' => 'localhost',           // Database host
    'dbname' => 'moodle',            // Database name
    'username' => 'root',            // Database username
    'password' => '',                // Database password
    'charset' => 'utf8mb4',          // Character set
    'port' => 3306,                  // MySQL port (default: 3306)
    'options' => [                   // PDO options
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
?>