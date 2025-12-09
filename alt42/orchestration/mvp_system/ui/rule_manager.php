<?php
// File: mvp_system/ui/rule_manager.php
// Mathking Agentic MVP System - Rule Management Interface
//
// Purpose: Web interface for managing decision rules (YAML-based)
// Access: Teachers and administrators only
// Database: Rules stored in mvp_system/decision/rules/calm_break_rules.yaml

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $CFG;

// Set embedded layout to minimize Moodle theme
$PAGE->set_pagelayout('embedded');
$PAGE->set_context(context_system::instance());

// Use output buffering to suppress Moodle theme output
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student (allow all non-student roles)
if ($role === 'student') {
    header("HTTP/1.1 403 Forbidden");
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1><p>This page is not accessible to students.</p>";
    echo "<p>Error Location: rule_manager.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP system dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/logger.php');

$logger = new MVPLogger('rule_manager');

$logger->info("Rule manager accessed", [
    'user_id' => $USER->id,
    'user_name' => $USER->username
]);

// Parse YAML file
$yaml_file = __DIR__ . '/../decision/rules/calm_break_rules.yaml';

function parseYamlFile($file_path) {
    if (!file_exists($file_path)) {
        error_log("[RuleManager] YAML file not found at " . __FILE__ . ":" . __LINE__);
        return null;
    }

    $content = file_get_contents($file_path);

    // Simple YAML parser (for basic structure)
    // In production, consider using symfony/yaml or similar
    $lines = explode("\n", $content);
    $data = [
        'version' => '',
        'scenario' => '',
        'description' => '',
        'rules' => []
    ];

    $current_rule = null;
    $current_section = null;
    $indent_level = 0;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Skip comments and empty lines
        if (empty($trimmed) || $trimmed[0] === '#') {
            continue;
        }

        // Parse top-level fields
        if (preg_match('/^version:\s*"([^"]+)"/', $line, $matches)) {
            $data['version'] = $matches[1];
        } elseif (preg_match('/^scenario:\s*"([^"]+)"/', $line, $matches)) {
            $data['scenario'] = $matches[1];
        } elseif (preg_match('/^description:\s*"([^"]+)"/', $line, $matches)) {
            $data['description'] = $matches[1];
        } elseif (preg_match('/^rules:/', $line)) {
            $current_section = 'rules';
        } elseif ($current_section === 'rules' && preg_match('/^\s*-\s*rule_id:\s*"([^"]+)"/', $line, $matches)) {
            // New rule
            if ($current_rule !== null) {
                $data['rules'][] = $current_rule;
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
        $data['rules'][] = $current_rule;
    }

    return $data;
}

$yaml_data = parseYamlFile($yaml_file);

if ($yaml_data === null) {
    $yaml_data = [
        'version' => '1.0',
        'scenario' => 'calm_break',
        'description' => 'Decision rules for student calm state intervention',
        'rules' => []
    ];
}

// Get statistics
$total_rules = count($yaml_data['rules']);
$active_rules = count(array_filter($yaml_data['rules'], function($rule) {
    return ($rule['priority'] ?? 0) >= 50;
}));
$high_priority = count(array_filter($yaml_data['rules'], function($rule) {
    return ($rule['priority'] ?? 0) >= 90;
}));

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rule Manager - Mathking MVP</title>
    <link rel="stylesheet" href="rule_manager.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="panel-header">
            <h1>⚙️ Decision Rule Manager</h1>
            <div class="user-info">
                <span>User: <strong><?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?></strong></span>
                <a href="<?php echo $CFG->wwwroot; ?>/login/logout.php?sesskey=<?php echo sesskey(); ?>" class="btn-logout">Logout</a>
            </div>
        </header>

        <!-- Statistics -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_rules; ?></div>
                <div class="stat-label">Total Rules</div>
            </div>
            <div class="stat-card highlight">
                <div class="stat-value"><?php echo $active_rules; ?></div>
                <div class="stat-label">Active Rules</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?php echo $high_priority; ?></div>
                <div class="stat-label">High Priority</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $yaml_data['version']; ?></div>
                <div class="stat-label">Version</div>
            </div>
        </section>

        <!-- Actions -->
        <section class="actions-section">
            <button class="btn btn-primary" onclick="openAddRuleModal()">
                ➕ Add New Rule
            </button>
            <button class="btn btn-secondary" onclick="refreshRules()">
                🔄 Refresh
            </button>
        </section>

        <!-- Rules List -->
        <section class="rules-section">
            <h2>Decision Rules (<?php echo $total_rules; ?> total)</h2>

            <?php if (empty($yaml_data['rules'])): ?>
                <div class="empty-state">
                    <p>📭 No rules defined yet. Click "Add New Rule" to create your first rule.</p>
                </div>
            <?php else: ?>
                <?php foreach ($yaml_data['rules'] as $index => $rule): ?>
                    <div class="rule-card" data-rule-index="<?php echo $index; ?>">
                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="rule-info">
                                <h3><?php echo htmlspecialchars($rule['rule_id']); ?></h3>
                                <span class="priority-badge priority-<?php echo $rule['priority'] >= 90 ? 'high' : ($rule['priority'] >= 70 ? 'medium' : 'low'); ?>">
                                    Priority: <?php echo $rule['priority']; ?>
                                </span>
                            </div>
                            <div class="rule-actions">
                                <button class="btn-icon" onclick="editRule(<?php echo $index; ?>)" title="Edit">✏️</button>
                                <button class="btn-icon" onclick="deleteRule(<?php echo $index; ?>, '<?php echo htmlspecialchars($rule['rule_id']); ?>')" title="Delete">🗑️</button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="card-section">
                            <h4>📝 Description</h4>
                            <p><?php echo htmlspecialchars($rule['description']); ?></p>
                        </div>

                        <!-- Action & Confidence -->
                        <div class="card-section">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Action:</span>
                                    <span class="action-badge action-<?php echo $rule['action']; ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $rule['action'])); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Confidence:</span>
                                    <span class="confidence-value"><?php echo number_format($rule['confidence'] * 100, 0); ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Rationale -->
                        <div class="card-section">
                            <h4>💡 Rationale</h4>
                            <p class="rationale"><?php echo htmlspecialchars($rule['rationale']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <!-- Add/Edit Rule Modal -->
    <div id="ruleModal" class="modal" style="display: none;">
        <div class="modal-content modal-large">
            <span class="modal-close" onclick="closeRuleModal()">&times;</span>
            <h3 id="modalTitle">Add New Rule</h3>
            <form id="ruleForm" onsubmit="submitRule(event)">
                <input type="hidden" id="ruleIndex" name="rule_index" value="-1">

                <div class="form-row">
                    <div class="form-group">
                        <label for="ruleId">Rule ID *</label>
                        <input type="text" id="ruleId" name="rule_id" required placeholder="e.g., calm_break_critical">
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority *</label>
                        <input type="number" id="priority" name="priority" required min="0" max="100" placeholder="0-100">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <input type="text" id="description" name="description" required placeholder="Brief description of this rule">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="action">Action *</label>
                        <select id="action" name="action" required>
                            <option value="">Select action...</option>
                            <option value="micro_break">Micro Break</option>
                            <option value="ask_teacher">Ask Teacher</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="confidence">Confidence *</label>
                        <input type="number" id="confidence" name="confidence" required min="0" max="1" step="0.01" placeholder="0.0 - 1.0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="rationale">Rationale *</label>
                    <textarea id="rationale" name="rationale" required rows="3" placeholder="Explain why this rule applies..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Rule</button>
                    <button type="button" class="btn btn-secondary" onclick="closeRuleModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
        <p>Processing...</p>
    </div>

    <script src="rule_manager.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.rulesData = <?php echo json_encode($yaml_data['rules']); ?>;
        window.yamlFile = '<?php echo $yaml_file; ?>';
    </script>
</body>
</html>
