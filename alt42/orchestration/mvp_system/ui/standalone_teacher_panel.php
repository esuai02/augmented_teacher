<?php
// File: mvp_system/ui/standalone_teacher_panel.php
// Standalone Teacher Approval Panel (No Moodle Dependency)
//
// Purpose: Teacher interface for reviewing and approving interventions
// Access: Teachers only (standalone authentication)
// Error Location: /mvp_system/ui/standalone_teacher_panel.php

require_once(__DIR__ . '/standalone_config.php');
require_once(__DIR__ . '/standalone_database.php');

// Require teacher access
require_teacher_access();

$db = new StandaloneDB();
$user = get_current_user();

log_message("Teacher panel accessed by user ID: " . $user['id'], "INFO");

// Get filter parameters
$filter_action = $_GET['action'] ?? 'all';
$filter_status = $_GET['status'] ?? 'pending';
$limit = intval($_GET['limit'] ?? 20);
$offset = intval($_GET['offset'] ?? 0);

// Build query
$where_clauses = [];
$params = [];

if ($filter_action !== 'all') {
    $where_clauses[] = "d.action = ?";
    $params[] = $filter_action;
}

if ($filter_status !== 'all') {
    if ($filter_status === 'pending') {
        $where_clauses[] = "(f.response IS NULL OR f.response = 'defer')";
    } else {
        $where_clauses[] = "f.response = ?";
        $params[] = $filter_status;
    }
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Fetch pending/recent decisions with optional teacher feedback
$query = "
    SELECT
        d.id as decision_id,
        d.student_id,
        d.action,
        d.params,
        d.confidence,
        d.rationale,
        d.rule_id,
        d.timestamp as decision_time,
        m.calm_score,
        m.recommendation,
        m.timestamp as metrics_time,
        i.intervention_id,
        i.status as intervention_status,
        i.message as intervention_message,
        f.id as feedback_id,
        f.response as teacher_response,
        f.comment as teacher_comment,
        f.timestamp as feedback_time
    FROM mdl_mvp_decision_log d
    LEFT JOIN mdl_mvp_snapshot_metrics m ON d.student_id = m.student_id AND m.timestamp <= d.timestamp
    LEFT JOIN mdl_mvp_intervention_execution i ON d.id = i.decision_id
    LEFT JOIN mdl_mvp_teacher_feedback f ON d.id = f.decision_id
    {$where_sql}
    ORDER BY d.timestamp DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

try {
    $decisions = $db->query($query, $params);
} catch (Exception $e) {
    $decisions = [];
    log_message("Error fetching decisions: " . $e->getMessage(), "ERROR");
}

// Get total count for pagination
$count_query = "
    SELECT COUNT(DISTINCT d.id) as total
    FROM mdl_mvp_decision_log d
    LEFT JOIN mdl_mvp_teacher_feedback f ON d.id = f.decision_id
    {$where_sql}
";
$count_params = array_slice($params, 0, -2); // Remove limit/offset

try {
    $count_result = $db->query($count_query, $count_params);
    $total_count = $count_result[0]['total'] ?? 0;
} catch (Exception $e) {
    $total_count = 0;
    log_message("Error counting decisions: " . $e->getMessage(), "ERROR");
}

$total_pages = ceil($total_count / $limit);
$current_page = floor($offset / $limit) + 1;

// Get statistics
$stats = [
    'total_decisions' => 0,
    'pending_approval' => 0,
    'approved' => 0,
    'rejected' => 0
];

try {
    $result = $db->query("SELECT COUNT(*) as count FROM mdl_mvp_decision_log");
    $stats['total_decisions'] = $result[0]['count'] ?? 0;

    $result = $db->query("
        SELECT COUNT(DISTINCT d.id) as count
        FROM mdl_mvp_decision_log d
        LEFT JOIN mdl_mvp_teacher_feedback f ON d.id = f.decision_id
        WHERE f.response IS NULL OR f.response = 'defer'
    ");
    $stats['pending_approval'] = $result[0]['count'] ?? 0;

    $result = $db->query("
        SELECT COUNT(*) as count
        FROM mdl_mvp_teacher_feedback
        WHERE response = 'approve'
    ");
    $stats['approved'] = $result[0]['count'] ?? 0;

    $result = $db->query("
        SELECT COUNT(*) as count
        FROM mdl_mvp_teacher_feedback
        WHERE response = 'reject'
    ");
    $stats['rejected'] = $result[0]['count'] ?? 0;

} catch (Exception $e) {
    log_message("Error fetching statistics: " . $e->getMessage(), "ERROR");
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Approval Panel - Mathking MVP (Standalone)</title>
    <link rel="stylesheet" href="teacher_panel.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="panel-header">
            <h1>🎓 Intervention Approval Panel (Standalone)</h1>
            <div class="user-info">
                <span>Teacher: <strong><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong></span>
                <span class="user-role">(<?php echo htmlspecialchars($user['role']); ?>)</span>
                <a href="standalone_login.php?action=logout" class="btn-logout">Logout</a>
            </div>
        </header>

        <!-- Statistics -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_decisions']; ?></div>
                <div class="stat-label">Total Decisions</div>
            </div>
            <div class="stat-card highlight">
                <div class="stat-value"><?php echo $stats['pending_approval']; ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </section>

        <!-- Filters -->
        <section class="filters-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="action">Action:</label>
                    <select name="action" id="action" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_action === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="micro_break" <?php echo $filter_action === 'micro_break' ? 'selected' : ''; ?>>Micro Break</option>
                        <option value="ask_teacher" <?php echo $filter_action === 'ask_teacher' ? 'selected' : ''; ?>>Ask Teacher</option>
                        <option value="none" <?php echo $filter_action === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" onchange="this.form.submit()">
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approve" <?php echo $filter_status === 'approve' ? 'selected' : ''; ?>>Approved</option>
                        <option value="reject" <?php echo $filter_status === 'reject' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                <button type="button" onclick="window.location.href='standalone_teacher_panel.php'" class="btn-reset">Reset</button>
            </form>
        </section>

        <!-- Decisions List -->
        <section class="decisions-section">
            <h2>Intervention Decisions (<?php echo $total_count; ?> total)</h2>

            <?php if (empty($decisions)): ?>
                <div class="empty-state">
                    <p>📭 No decisions to display with current filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($decisions as $decision): ?>
                    <?php
                        $params = json_decode($decision['params'], true);
                        $needs_approval = ($decision['teacher_response'] === null || $decision['teacher_response'] === 'defer');
                        $card_class = 'decision-card';
                        if (!$needs_approval) {
                            $card_class .= ' reviewed';
                            if ($decision['teacher_response'] === 'approve') $card_class .= ' approved';
                            if ($decision['teacher_response'] === 'reject') $card_class .= ' rejected';
                        }
                    ?>
                    <div class="<?php echo $card_class; ?>" data-decision-id="<?php echo $decision['decision_id']; ?>">
                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="student-info">
                                <h3>Student ID: <?php echo $decision['student_id']; ?></h3>
                                <span class="timestamp"><?php echo date('Y-m-d H:i', strtotime($decision['decision_time'])); ?></span>
                            </div>
                            <div class="action-badge action-<?php echo $decision['action']; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $decision['action'])); ?>
                            </div>
                        </div>

                        <!-- Metrics -->
                        <div class="card-section">
                            <h4>📊 Student Metrics</h4>
                            <div class="metrics-grid">
                                <div class="metric">
                                    <span class="metric-label">Calm Score:</span>
                                    <span class="metric-value calm-score-<?php echo $decision['calm_score'] < 60 ? 'critical' : ($decision['calm_score'] < 75 ? 'low' : ($decision['calm_score'] < 90 ? 'moderate' : 'high')); ?>">
                                        <?php echo number_format($decision['calm_score'], 1); ?>
                                    </span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Confidence:</span>
                                    <span class="metric-value"><?php echo number_format($decision['confidence'] * 100, 0); ?>%</span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Rule:</span>
                                    <span class="metric-value"><?php echo htmlspecialchars($decision['rule_id']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Rationale -->
                        <div class="card-section">
                            <h4>💡 Decision Rationale</h4>
                            <p class="rationale"><?php echo htmlspecialchars($decision['rationale']); ?></p>
                        </div>

                        <!-- Intervention Details -->
                        <?php if ($decision['action'] !== 'none' && $decision['intervention_message']): ?>
                            <div class="card-section">
                                <h4>📨 Intervention Message</h4>
                                <?php
                                    $message = json_decode($decision['intervention_message'], true);
                                ?>
                                <div class="intervention-message">
                                    <strong><?php echo htmlspecialchars($message['title'] ?? 'N/A'); ?></strong>
                                    <p><?php echo htmlspecialchars($message['body'] ?? 'N/A'); ?></p>
                                    <?php if ($message['urgency'] ?? null): ?>
                                        <span class="urgency-badge urgency-<?php echo $message['urgency']; ?>">
                                            Urgency: <?php echo ucfirst($message['urgency']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Teacher Feedback (if exists) -->
                        <?php if (!$needs_approval): ?>
                            <div class="card-section feedback-section">
                                <h4>✅ Teacher Feedback</h4>
                                <p><strong>Response:</strong>
                                    <span class="response-badge response-<?php echo $decision['teacher_response']; ?>">
                                        <?php echo ucfirst($decision['teacher_response']); ?>
                                    </span>
                                </p>
                                <?php if ($decision['teacher_comment']): ?>
                                    <p><strong>Comment:</strong> <?php echo htmlspecialchars($decision['teacher_comment']); ?></p>
                                <?php endif; ?>
                                <p class="feedback-time">
                                    <small>Reviewed: <?php echo date('Y-m-d H:i', strtotime($decision['feedback_time'])); ?></small>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Action Buttons -->
                            <div class="card-actions">
                                <button class="btn btn-approve" onclick="submitFeedback(<?php echo $decision['decision_id']; ?>, 'approve')">
                                    ✅ Approve
                                </button>
                                <button class="btn btn-reject" onclick="submitFeedback(<?php echo $decision['decision_id']; ?>, 'reject')">
                                    ❌ Reject
                                </button>
                                <button class="btn btn-defer" onclick="submitFeedback(<?php echo $decision['decision_id']; ?>, 'defer')">
                                    ⏸️ Defer
                                </button>
                                <button class="btn btn-comment" onclick="openCommentModal(<?php echo $decision['decision_id']; ?>)">
                                    💬 Add Comment
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?action=<?php echo $filter_action; ?>&status=<?php echo $filter_status; ?>&limit=<?php echo $limit; ?>&offset=<?php echo ($current_page - 2) * $limit; ?>" class="btn-page">← Previous</a>
                    <?php endif; ?>

                    <span class="page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?action=<?php echo $filter_action; ?>&status=<?php echo $filter_status; ?>&limit=<?php echo $limit; ?>&offset=<?php echo $current_page * $limit; ?>" class="btn-page">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Comment Modal -->
    <div id="commentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeCommentModal()">&times;</span>
            <h3>Add Teacher Comment</h3>
            <form id="commentForm" onsubmit="submitCommentFeedback(event)">
                <input type="hidden" id="commentDecisionId" name="decision_id">
                <div class="form-group">
                    <label for="response">Response:</label>
                    <select id="response" name="response" required>
                        <option value="approve">✅ Approve</option>
                        <option value="reject">❌ Reject</option>
                        <option value="defer">⏸️ Defer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="comment">Comment:</label>
                    <textarea id="comment" name="comment" rows="4" placeholder="Enter your feedback..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCommentModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
        <p>Submitting feedback...</p>
    </div>

    <script src="teacher_panel.js"></script>
</body>
</html>
