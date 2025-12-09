<?php
// 파일: mvp_system/database/generate_rules_yaml.php (Line 1)
// Mathking Agentic MVP System - Rules YAML Generator
//
// Purpose: Convert agent.md files to rules.yaml for MVPAgentOrchestrator
// Usage: Direct browser access (one-time generation)

// Server connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $CFG;

// Set embedded layout
$PAGE->set_pagelayout('embedded');
$PAGE->set_context(context_system::instance());

// Authentication
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student/parent
if ($role === 'student' || $role === 'parent') {
    header("HTTP/1.1 403 Forbidden");
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1><p>This page is not accessible to students or parents.</p>";
    echo "<p>Error Location: generate_rules_yaml.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rules YAML Generator - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .agent-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Rules YAML Generator</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Generate rules.yaml files from agent.md knowledge files for 22 agents
        </div>

<?php

try {
    $agents_dir = __DIR__ . '/../../agents';

    // Define 22 actual agents
    $agent_ids = [
        'agent01_onboarding',
        'agent02_exam_schedule',
        'agent03_goals_analysis',
        'agent04_problem_activity',
        'agent05_learning_emotion',
        'agent06_teacher_feedback',
        'agent07_interaction_targeting',
        'agent08_calmness',
        'agent09_learning_management',
        'agent10_concept_notes',
        'agent11_problem_notes',
        'agent12_rest_routine',
        'agent13_learning_dropout',
        'agent14_current_position',
        'agent15_problem_redefinition',
        'agent16_interaction_preparation',
        'agent17_remaining_activities',
        'agent18_signature_routine',
        'agent19_interaction_content',
        'agent20_intervention_preparation',
        'agent21_intervention_execution',
        'agent22_module_improvement'
    ];

    $generated_count = 0;
    $error_count = 0;

    foreach ($agent_ids as $agent_id) {
        $agent_dir = "{$agents_dir}/{$agent_id}";
        $md_file = "{$agent_dir}/{$agent_id}.md";
        $yaml_file = "{$agent_dir}/rules.yaml";

        echo "<div class='agent-section'>";
        echo "<h3>{$agent_id}</h3>";

        // Check if MD file exists
        if (!file_exists($md_file)) {
            echo "<div class='warning'>⚠️ MD file not found: {$md_file}</div>";
            $error_count++;
            echo "</div>";
            continue;
        }

        // Read MD content
        $md_content = file_get_contents($md_file);

        // Parse MD file
        $rules = parse_agent_md($md_content, $agent_id);

        // Generate YAML
        $yaml_content = generate_yaml($rules, $agent_id, $md_content);

        // Write YAML file to agent directory
        $result = file_put_contents($yaml_file, $yaml_content);

        if ($result !== false) {
            echo "<div class='success'>";
            echo "✅ Generated: {$yaml_file}<br>";
            echo "Rules created: " . count($rules) . "<br>";
            echo "File size: " . round($result / 1024, 2) . " KB<br>";
            echo "</div>";
            $generated_count++;

            // Show preview
            echo "<pre>";
            echo htmlspecialchars(substr($yaml_content, 0, 500));
            if (strlen($yaml_content) > 500) {
                echo "\n... (truncated)";
            }
            echo "</pre>";
        } else {
            echo "<div class='error'>";
            echo "❌ Failed to write: {$yaml_file}<br>";
            echo "Directory: {$agent_dir}<br>";
            echo "Writable: " . (is_writable($agent_dir) ? 'Yes' : 'No') . "<br>";
            echo "Exists: " . (file_exists($agent_dir) ? 'Yes' : 'No') . "<br>";
            echo "</div>";
            $error_count++;
        }

        echo "</div>";
    }

    // Summary
    echo "<hr>";
    echo "<h2>📊 Generation Summary</h2>";
    echo "<div class='" . ($error_count === 0 ? 'success' : 'warning') . "'>";
    echo "<p><strong>Successfully Generated:</strong> {$generated_count} / 22</p>";
    echo "<p><strong>Errors:</strong> {$error_count}</p>";

    if ($generated_count === 22) {
        echo "<p><strong>✅ All 22 agents now have rules.yaml files!</strong></p>";
        echo "<p>Next step: Run orchestrator test at test_orchestrator.php</p>";
    } else {
        echo "<p><strong>⚠️ Some agents are missing rules.yaml</strong></p>";
        echo "<p>Please check error messages above and fix missing MD files.</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Generation Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";
}

/**
 * Parse agent markdown file and extract rules
 *
 * @param string $md_content Markdown file content
 * @param string $agent_id Agent identifier
 * @return array Array of rules
 */
function parse_agent_md($md_content, $agent_id) {
    $rules = [];
    $priority = 90;

    // Extract interpretation criteria (해석 기준)
    if (preg_match('/##\s*해석\s*기준.*?\n(.*?)(?=\n##|\z)/s', $md_content, $matches)) {
        $criteria_text = $matches[1];
        $lines = explode("\n", $criteria_text);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match patterns like "- 95+: 매우 침착, 고난도/심화 추천"
            if (preg_match('/^-\s*(\d+)([+~\-])(\d*):\s*(.+)$/', $line, $match)) {
                $threshold = intval($match[1]);
                $operator = $match[2];
                $range_end = !empty($match[3]) ? intval($match[3]) : null;
                $description = trim($match[4]);

                // Create rule
                $rule = [
                    'rule_id' => "{$agent_id}_threshold_{$threshold}",
                    'priority' => $priority,
                    'description' => $description,
                    'conditions' => [],
                    'action' => extract_action($description),
                    'confidence' => calculate_confidence($threshold, $operator),
                    'rationale' => $description
                ];

                // Add condition based on operator
                if ($operator === '+') {
                    $rule['conditions'][] = [
                        'field' => 'score',
                        'operator' => '>=',
                        'value' => $threshold
                    ];
                } elseif ($operator === '~' && $range_end !== null) {
                    $rule['conditions'][] = [
                        'field' => 'score',
                        'operator' => '>=',
                        'value' => $threshold
                    ];
                    $rule['conditions'][] = [
                        'field' => 'score',
                        'operator' => '<=',
                        'value' => $range_end
                    ];
                } elseif ($operator === '-' && $range_end !== null) {
                    $rule['conditions'][] = [
                        'field' => 'score',
                        'operator' => 'between',
                        'value' => [$threshold, $range_end]
                    ];
                }

                $rules[] = $rule;
                $priority -= 5;
            }
        }
    }

    // Extract pattern heuristics (패턴 휴리스틱)
    if (preg_match('/##\s*패턴\s*휴리스틱.*?\n(.*?)(?=\n##|\z)/s', $md_content, $matches)) {
        $pattern_text = $matches[1];
        $lines = explode("\n", $pattern_text);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match patterns like "- 기준선 대비 +5 이상: 고효율 상태 → 심화 컨텐츠 배치"
            if (preg_match('/^-\s*기준선\s*대비\s*([+\-])(\d+)\s*([이상하]*):\s*(.+?)\s*→\s*(.+)$/', $line, $match)) {
                $sign = $match[1];
                $value = intval($match[2]);
                $condition_type = $match[3];
                $state = trim($match[4]);
                $action = trim($match[5]);

                $rule = [
                    'rule_id' => "{$agent_id}_pattern_" . ($sign === '+' ? 'above' : 'below') . "_{$value}",
                    'priority' => 85,
                    'description' => "{$state} → {$action}",
                    'conditions' => [],
                    'action' => $action,
                    'confidence' => 0.85,
                    'rationale' => $state
                ];

                // Add delta condition
                if ($sign === '+') {
                    $rule['conditions'][] = [
                        'field' => 'score_delta',
                        'operator' => $condition_type === '이상' ? '>=' : '>',
                        'value' => $value
                    ];
                } else {
                    $rule['conditions'][] = [
                        'field' => 'score_delta',
                        'operator' => $condition_type === '이하' ? '<=' : '<',
                        'value' => -$value
                    ];
                }

                $rules[] = $rule;
            }
        }
    }

    // If no rules parsed, create a default rule
    if (empty($rules)) {
        $rules[] = [
            'rule_id' => "{$agent_id}_default",
            'priority' => 50,
            'description' => 'Default rule - analyze student context',
            'conditions' => [
                ['field' => 'student_id', 'operator' => 'exists', 'value' => true]
            ],
            'action' => 'analyze_context',
            'confidence' => 0.70,
            'rationale' => 'Default rule for agent activation'
        ];
    }

    return $rules;
}

/**
 * Extract action from description text
 */
function extract_action($description) {
    // Common action keywords
    $action_map = [
        '추천' => 'recommend_content',
        '권장' => 'suggest_action',
        '진행' => 'proceed',
        '복습' => 'review',
        '휴식' => 'take_break',
        '복구' => 'recovery',
        '심화' => 'advanced_content',
        '워밍업' => 'warmup'
    ];

    foreach ($action_map as $keyword => $action) {
        if (strpos($description, $keyword) !== false) {
            return $action;
        }
    }

    return 'assess_and_act';
}

/**
 * Calculate confidence based on threshold level
 */
function calculate_confidence($threshold, $operator) {
    if ($threshold >= 95) return 0.95;
    if ($threshold >= 90) return 0.90;
    if ($threshold >= 85) return 0.85;
    if ($threshold >= 80) return 0.80;
    if ($threshold >= 75) return 0.75;
    return 0.70;
}

/**
 * Generate YAML content from rules
 */
function generate_yaml($rules, $agent_id, $md_content) {
    $yaml = "# Auto-generated rules.yaml for {$agent_id}\n";
    $yaml .= "# Generated at: " . date('Y-m-d H:i:s') . "\n";
    $yaml .= "# Source: {$agent_id}.md\n\n";

    $yaml .= "version: \"1.0\"\n";
    $yaml .= "scenario: \"{$agent_id}\"\n";

    // Extract description from MD
    $description = "Agent rules for {$agent_id}";
    if (preg_match('/##\s*목적\s*\n-\s*(.+)/m', $md_content, $match)) {
        $description = trim($match[1]);
    }
    $yaml .= "description: \"{$description}\"\n\n";

    $yaml .= "rules:\n";

    foreach ($rules as $rule) {
        $yaml .= "  - rule_id: \"{$rule['rule_id']}\"\n";
        $yaml .= "    priority: {$rule['priority']}\n";
        $yaml .= "    description: \"" . addslashes($rule['description']) . "\"\n";

        // Conditions
        if (!empty($rule['conditions'])) {
            $yaml .= "    conditions:\n";
            foreach ($rule['conditions'] as $condition) {
                $yaml .= "      - field: \"{$condition['field']}\"\n";
                $yaml .= "        operator: \"{$condition['operator']}\"\n";

                if (is_array($condition['value'])) {
                    $yaml .= "        value: [" . implode(", ", $condition['value']) . "]\n";
                } elseif (is_bool($condition['value'])) {
                    $yaml .= "        value: " . ($condition['value'] ? 'true' : 'false') . "\n";
                } elseif (is_numeric($condition['value'])) {
                    $yaml .= "        value: {$condition['value']}\n";
                } else {
                    $yaml .= "        value: \"{$condition['value']}\"\n";
                }
            }
        }

        $yaml .= "    action: \"{$rule['action']}\"\n";
        $yaml .= "    confidence: {$rule['confidence']}\n";
        $yaml .= "    rationale: \"" . addslashes($rule['rationale']) . "\"\n\n";
    }

    return $yaml;
}

?>

    </div>
</body>
</html>

<?php
/**
 * Database Tables Used: None (file generation only)
 *
 * File Dependencies:
 * - /agents/{agent_id}/{agent_id}.md: Agent knowledge files (input)
 * - /agents/{agent_id}/rules.yaml: Generated YAML rules (output)
 */
?>
