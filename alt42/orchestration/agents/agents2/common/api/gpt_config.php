<?php
/**
 * GPT API Configuration
 *
 * OpenAI API credentials and settings for agent analysis generation
 *
 * @version 1.0
 * @date 2025-01-21
 * File: api/gpt_config.php
 */

// Prevent direct access
if (!defined('MOODLE_INTERNAL') && !defined('GPT_CONFIG_INCLUDED')) {
    define('GPT_CONFIG_INCLUDED', true);
}

// =============================================================================
// GPT API CONFIGURATION
// =============================================================================

// OpenAI API Key
// TODO: Replace with your actual OpenAI API key
// Get your key from: https://platform.openai.com/api-keys
define('OPENAI_API_KEY', 'sk-YOUR-API-KEY-HERE');

// API Endpoint
define('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions');

// Model Selection
// Options: 'gpt-4', 'gpt-4-turbo-preview', 'gpt-3.5-turbo'
// Recommended: 'gpt-4' for best analysis quality
define('OPENAI_MODEL', 'gpt-4');

// Temperature (0.0 - 2.0)
// Lower = more focused and deterministic
// Higher = more creative and diverse
// Recommended: 0.7 for balanced analysis
define('OPENAI_TEMPERATURE', 0.7);

// Max Tokens
// Maximum length of generated response
// Recommended: 1500 for detailed analysis
define('OPENAI_MAX_TOKENS', 1500);

// Request Timeout (seconds)
define('OPENAI_TIMEOUT', 30);

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Validate API configuration
 *
 * @return array ['valid' => bool, 'errors' => string[]]
 */
function validateGPTConfig() {
    $errors = [];

    if (OPENAI_API_KEY === 'sk-YOUR-API-KEY-HERE' || empty(OPENAI_API_KEY)) {
        $errors[] = 'OpenAI API key not configured. Please set OPENAI_API_KEY in gpt_config.php';
    }

    if (!in_array(OPENAI_MODEL, ['gpt-4', 'gpt-4-turbo-preview', 'gpt-3.5-turbo', 'gpt-4o', 'gpt-4o-mini'])) {
        $errors[] = 'Invalid OpenAI model: ' . OPENAI_MODEL;
    }

    if (OPENAI_TEMPERATURE < 0 || OPENAI_TEMPERATURE > 2) {
        $errors[] = 'Temperature must be between 0 and 2';
    }

    if (OPENAI_MAX_TOKENS < 100 || OPENAI_MAX_TOKENS > 4000) {
        $errors[] = 'Max tokens must be between 100 and 4000';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Check if GPT integration is enabled
 *
 * @return bool
 */
function isGPTEnabled() {
    return OPENAI_API_KEY !== 'sk-YOUR-API-KEY-HERE' && !empty(OPENAI_API_KEY);
}

/**
 * Get GPT configuration as array
 *
 * @return array
 */
function getGPTConfig() {
    return [
        'api_key' => OPENAI_API_KEY,
        'endpoint' => OPENAI_API_ENDPOINT,
        'model' => OPENAI_MODEL,
        'temperature' => OPENAI_TEMPERATURE,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'timeout' => OPENAI_TIMEOUT,
        'enabled' => isGPTEnabled()
    ];
}

// Log configuration status on load
if (function_exists('error_log')) {
    $config_status = isGPTEnabled() ? 'ENABLED' : 'DISABLED (using placeholder)';
    error_log("[gpt_config.php] GPT API integration status: {$config_status} | Model: " . OPENAI_MODEL);
}
