<?php
// File: mvp_system/ui/rule_manager_api.php
// Mathking Agentic MVP System - Rule Management API
//
// Purpose: API endpoint for CRUD operations on decision rules (YAML-based)
// Access: Teachers and administrators only

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Use output buffering to suppress any Moodle output
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student (allow all non-student roles)
if ($role === 'student') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Students cannot modify rules.',
        'location' => __FILE__ . ':' . __LINE__
    ]);
    exit;
}

// Load MVP system dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/logger.php');

$logger = new MVPLogger('rule_manager_api');

// Set JSON response headers
header('Content-Type: application/json');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON request',
        'location' => __FILE__ . ':' . __LINE__
    ]);
    exit;
}

$action = $data['action'] ?? '';
$yaml_file = __DIR__ . '/../decision/rules/calm_break_rules.yaml';

// Log the request
$logger->info("Rule API request", [
    'action' => $action,
    'user_id' => $USER->id,
    'user_name' => $USER->username
]);

try {
    switch ($action) {
        case 'create':
            $result = createRule($yaml_file, $data['rule_data']);
            break;

        case 'update':
            $result = updateRule($yaml_file, $data['rule_index'], $data['rule_data']);
            break;

        case 'delete':
            $result = deleteRule($yaml_file, $data['rule_index']);
            break;

        default:
            throw new Exception("Unknown action: $action at " . __FILE__ . ":" . __LINE__);
    }

    echo json_encode([
        'success' => true,
        'message' => $result['message']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'location' => __FILE__ . ':' . __LINE__
    ]);

    $logger->error("Rule API error", [
        'error' => $e->getMessage(),
        'action' => $action
    ]);
}

/**
 * Create new rule
 */
function createRule($yaml_file, $rule_data) {
    global $logger, $USER;

    if (!file_exists($yaml_file)) {
        throw new Exception("YAML file not found at " . __FILE__ . ":" . __LINE__);
    }

    // Read existing YAML
    $content = file_get_contents($yaml_file);
    $lines = explode("\n", $content);

    // Find the rules section and add new rule
    $new_rule_yaml = generateRuleYaml($rule_data);

    // Insert before the last line (which should be empty or closing)
    array_splice($lines, -1, 0, explode("\n", $new_rule_yaml));

    // Write back to file
    $backup_file = $yaml_file . '.backup.' . time();
    copy($yaml_file, $backup_file);

    if (!file_put_contents($yaml_file, implode("\n", $lines))) {
        throw new Exception("Failed to write YAML file at " . __FILE__ . ":" . __LINE__);
    }

    $logger->info("Rule created", [
        'rule_id' => $rule_data['rule_id'],
        'user_id' => $USER->id,
        'backup' => $backup_file
    ]);

    return [
        'message' => 'Rule created successfully',
        'backup' => $backup_file
    ];
}

/**
 * Update existing rule
 */
function updateRule($yaml_file, $rule_index, $rule_data) {
    global $logger, $USER;

    if (!file_exists($yaml_file)) {
        throw new Exception("YAML file not found at " . __FILE__ . ":" . __LINE__);
    }

    // Read and parse YAML
    $content = file_get_contents($yaml_file);
    $rules = parseYamlToRules($content);

    if (!isset($rules[$rule_index])) {
        throw new Exception("Rule index $rule_index not found at " . __FILE__ . ":" . __LINE__);
    }

    // Update the rule
    $rules[$rule_index] = $rule_data;

    // Regenerate YAML
    $new_yaml = generateFullYaml($rules);

    // Backup and write
    $backup_file = $yaml_file . '.backup.' . time();
    copy($yaml_file, $backup_file);

    if (!file_put_contents($yaml_file, $new_yaml)) {
        throw new Exception("Failed to write YAML file at " . __FILE__ . ":" . __LINE__);
    }

    $logger->info("Rule updated", [
        'rule_id' => $rule_data['rule_id'],
        'rule_index' => $rule_index,
        'user_id' => $USER->id,
        'backup' => $backup_file
    ]);

    return [
        'message' => 'Rule updated successfully',
        'backup' => $backup_file
    ];
}

/**
 * Delete rule
 */
function deleteRule($yaml_file, $rule_index) {
    global $logger, $USER;

    if (!file_exists($yaml_file)) {
        throw new Exception("YAML file not found at " . __FILE__ . ":" . __LINE__);
    }

    // Read and parse YAML
    $content = file_get_contents($yaml_file);
    $rules = parseYamlToRules($content);

    if (!isset($rules[$rule_index])) {
        throw new Exception("Rule index $rule_index not found at " . __FILE__ . ":" . __LINE__);
    }

    $deleted_rule_id = $rules[$rule_index]['rule_id'];

    // Remove the rule
    array_splice($rules, $rule_index, 1);

    // Regenerate YAML
    $new_yaml = generateFullYaml($rules);

    // Backup and write
    $backup_file = $yaml_file . '.backup.' . time();
    copy($yaml_file, $backup_file);

    if (!file_put_contents($yaml_file, $new_yaml)) {
        throw new Exception("Failed to write YAML file at " . __FILE__ . ":" . __LINE__);
    }

    $logger->info("Rule deleted", [
        'rule_id' => $deleted_rule_id,
        'rule_index' => $rule_index,
        'user_id' => $USER->id,
        'backup' => $backup_file
    ]);

    return [
        'message' => 'Rule deleted successfully',
        'backup' => $backup_file
    ];
}

/**
 * Parse YAML content to rules array
 */
function parseYamlToRules($content) {
    $lines = explode("\n", $content);
    $rules = [];
    $current_rule = null;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Skip comments and empty lines
        if (empty($trimmed) || $trimmed[0] === '#') {
            continue;
        }

        // Detect new rule
        if (preg_match('/^-\s*rule_id:\s*"([^"]+)"/', $line, $matches)) {
            if ($current_rule !== null) {
                $rules[] = $current_rule;
            }
            $current_rule = [
                'rule_id' => $matches[1],
                'priority' => 0,
                'description' => '',
                'conditions' => [],
                'action' => '',
                'params' => [],
                'confidence' => 0,
                'rationale' => ''
            ];
        } elseif ($current_rule !== null) {
            // Parse rule fields
            if (preg_match('/^\s*priority:\s*(\d+)/', $line, $matches)) {
                $current_rule['priority'] = (int)$matches[1];
            } elseif (preg_match('/^\s*description:\s*"([^"]+)"/', $line, $matches)) {
                $current_rule['description'] = $matches[1];
            } elseif (preg_match('/^\s*action:\s*"([^"]+)"/', $line, $matches)) {
                $current_rule['action'] = $matches[1];
            } elseif (preg_match('/^\s*confidence:\s*([\d.]+)/', $line, $matches)) {
                $current_rule['confidence'] = (float)$matches[1];
            } elseif (preg_match('/^\s*rationale:\s*"(.+)"/', $line, $matches)) {
                $current_rule['rationale'] = $matches[1];
            }
        }
    }

    // Add last rule
    if ($current_rule !== null) {
        $rules[] = $current_rule;
    }

    return $rules;
}

/**
 * Generate YAML for a single rule
 */
function generateRuleYaml($rule) {
    $yaml = "\n  - rule_id: \"{$rule['rule_id']}\"\n";
    $yaml .= "    priority: {$rule['priority']}\n";
    $yaml .= "    description: \"{$rule['description']}\"\n";
    $yaml .= "    conditions: []\n"; // TODO: Add conditions editor
    $yaml .= "    action: \"{$rule['action']}\"\n";
    $yaml .= "    params: {}\n"; // TODO: Add params editor
    $yaml .= "    confidence: {$rule['confidence']}\n";
    $yaml .= "    rationale: \"{$rule['rationale']}\"\n";

    return $yaml;
}

/**
 * Generate full YAML file
 */
function generateFullYaml($rules) {
    $yaml = "---\n";
    $yaml .= "version: \"1.0\"\n";
    $yaml .= "scenario: \"calm_break\"\n";
    $yaml .= "description: \"Decision rules for student calm state intervention\"\n\n";
    $yaml .= "rules:\n";

    foreach ($rules as $rule) {
        $yaml .= generateRuleYaml($rule);
    }

    return $yaml;
}
?>
