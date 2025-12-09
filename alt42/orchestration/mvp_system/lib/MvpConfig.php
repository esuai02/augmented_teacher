<?php
// File: lib/MvpConfig.php

/**
 * Configuration loader for MVP system
 * Extracts database configuration from Moodle config.php
 */
class MvpConfig {

    /**
     * Get path to Moodle config.php
     * Supports environment-specific configuration through:
     * 1. MOODLE_CONFIG_PATH constant (highest priority)
     * 2. MOODLE_CONFIG_PATH environment variable
     * 3. Default production path (fallback)
     * @return string
     */
    public static function getMoodleConfigPath() {
        // Allow constant override (for testing/different environments)
        if (defined('MOODLE_CONFIG_PATH')) {
            return MOODLE_CONFIG_PATH;
        }

        // Check environment variable
        $envPath = getenv('MOODLE_CONFIG_PATH');
        if ($envPath !== false && file_exists($envPath)) {
            return $envPath;
        }

        // Default to production path
        return '/home/moodle/public_html/moodle/config.php';
    }

    /**
     * Get database configuration from Moodle
     * @return array Database configuration
     * @throws Exception if Moodle config cannot be loaded
     */
    public static function getDatabaseConfig() {
        $file = __FILE__;
        $line = __LINE__;

        $configPath = self::getMoodleConfigPath();

        if (!file_exists($configPath)) {
            throw new Exception("[{$file}:{$line}] Moodle config.php not found at: {$configPath}");
        }

        // Include Moodle config to access $CFG
        require_once($configPath);
        global $CFG;

        if (!isset($CFG)) {
            $line = __LINE__;
            throw new Exception("[{$file}:{$line}] Moodle \$CFG object not available after including config.php");
        }

        // Extract database configuration
        return [
            'host' => $CFG->dbhost ?? 'localhost',
            'name' => $CFG->dbname ?? '',
            'user' => $CFG->dbuser ?? '',
            'pass' => $CFG->dbpass ?? '',
            'prefix' => $CFG->prefix ?? 'mdl_',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ];
    }

    /**
     * Validate database configuration
     * @param array $config
     * @return bool
     * @throws Exception if configuration is invalid
     */
    public static function validateConfig(array $config) {
        $file = __FILE__;
        $line = __LINE__;

        $required = ['host', 'name', 'user', 'pass', 'prefix'];

        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new Exception("[{$file}:{$line}] Database configuration missing required key: {$key}");
            }
        }

        return true;
    }
}
?>
