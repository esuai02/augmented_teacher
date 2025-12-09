<?php
/**
 * Configuration file for API settings
 * 
 * ⚠️ IMPORTANT: Keep this file secure and never commit to version control
 * Add to .gitignore: api/config.php
 */

// OpenAI GPT API Configuration
define('OPENAI_API_KEY', 'sk-YOUR_ACTUAL_API_KEY_HERE'); // Replace with your actual API key
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-4'); // or 'gpt-3.5-turbo' for lower cost

// API Settings
define('API_TIMEOUT', 30); // seconds
define('API_MAX_TOKENS', 500); // max response length
define('API_TEMPERATURE', 0.7); // creativity level (0-1)

// Database Connection (if needed)
define('DB_HOST', 'localhost');
define('DB_NAME', 'shiningstars');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Feature Flags
define('ENABLE_GPT_API', true); // Toggle GPT API on/off
define('ENABLE_FALLBACK', true); // Use fallback responses when API fails
define('ENABLE_LOGGING', true); // Log API interactions

// Rate Limiting
define('API_RATE_LIMIT', 60); // requests per minute
define('API_DAILY_LIMIT', 1000); // requests per day

// Error Messages
define('ERROR_API_KEY_MISSING', 'API key not configured. Please contact administrator.');
define('ERROR_API_UNAVAILABLE', 'AI service temporarily unavailable. Using local feedback.');
define('ERROR_RATE_LIMIT', 'Too many requests. Please wait a moment.');

/**
 * Security check - ensure this file is not directly accessible
 */
if (!defined('SECURE_ACCESS')) {
    // If accessed directly, show generic error
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}
?>