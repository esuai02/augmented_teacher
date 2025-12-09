<?php
// 파일: mvp_system/config/app.config.php (Line 1)
// Mathking Agentic MVP System - Application Configuration

// Moodle Integration
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// MVP System Version
define('MVP_VERSION', '1.3');
define('MVP_BUILD_DATE', '2025-11-02');
define('MVP_STATUS', 'Production Ready');

// MVP System Paths
define('MVP_ROOT', __DIR__ . '/..');
define('MVP_AGENTS_PATH', '/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents');
define('MVP_LOG_PATH', MVP_ROOT . '/logs');

// Base URL
define('MVP_BASE_URL', 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system');

// Database Table Prefixes
define('MVP_TABLE_PREFIX', 'mvp_');

// Agent Policy Files (Read-Only References)
define('AGENT08_POLICY', MVP_AGENTS_PATH . '/agent08_calmness/agent08_calmness.md');
define('AGENT20_TEMPLATE', MVP_AGENTS_PATH . '/agent20_intervention_preparation/agent20_intervention_preparation.md');
define('AGENT21_TEMPLATE', MVP_AGENTS_PATH . '/agent21_intervention_execution/agent21_intervention_execution.md');

// Python Interpreter
define('PYTHON_BIN', '/usr/bin/python3');

// SLA Configuration
define('SLA_TARGET_SECONDS', 300); // 5 minutes
define('SLA_COMPLIANCE_THRESHOLD', 90); // 90% compliance required

// Logging Configuration
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_TO_FILE', true);
define('LOG_TO_DB', false);

// Feature Flags
define('ENABLE_TEACHER_APPROVAL', true);
define('ENABLE_LLM_REASONING', false); // v2.0 feature
define('ENABLE_RAG_RETRIEVAL', false); // v2.0 feature

// Cache Configuration
define('CACHE_ENABLED', false); // Redis integration in v2.0
define('CACHE_TTL_SECONDS', 3600);

// Error Handling
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', MVP_LOG_PATH . '/php_errors.log');

/**
 * Get configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value
 */
function mvp_config($key, $default = null) {
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

/**
 * Check if running in CLI mode
 * @return bool True if CLI, false otherwise
 */
function mvp_is_cli() {
    return php_sapi_name() === 'cli';
}

/**
 * Get current timestamp in ISO 8601 format
 * @return string Timestamp
 */
function mvp_timestamp() {
    return date('Y-m-d\TH:i:s\Z');
}

// Timezone
date_default_timezone_set('Asia/Seoul');

// System Check
if (!file_exists(AGENT08_POLICY)) {
    error_log("WARNING: agent08_calmness.md not found at " . AGENT08_POLICY . " (File: " . __FILE__ . ":" . __LINE__ . ")");
}

// Log initialization
if (mvp_is_cli()) {
    echo "MVP System Config Loaded at " . __FILE__ . ":" . __LINE__ . "\n";
}
?>
