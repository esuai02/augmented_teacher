<?php
// File: mvp_system/monitoring/sla_dashboard.php (Line 1)
// Mathking Agentic MVP System - SLA Monitoring Dashboard
//
// Purpose: Web-based SLA monitoring and reporting interface
// Access: Admin/Teacher view of system health

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is teacher or admin
if ($role !== 'teacher' && $role !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    echo "<h1>Access Denied</h1><p>This page is only accessible to teachers and admins.</p>";
    exit;
}

// Load MVP system dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');
require_once(__DIR__ . '/sla_monitor.php');

$mvp_db = new MVPDatabase();
$logger = new MVPLogger('sla_dashboard');

$logger->info("SLA dashboard accessed", [
    'user_id' => $USER->id,
    'user_name' => $USER->username
]);

// Get time period from query parameter
$hours = isset($_GET['hours']) ? intval($_GET['hours']) : 24;
if ($hours <= 0 || $hours > 168) $hours = 24; // Max 1 week

// Run monitoring
$monitor = new SLAMonitor();
$results = $monitor->monitor($hours);

// Get recent alerts
$recent_alerts = $mvp_db->query(
    "SELECT context, timestamp
     FROM mdl_mvp_system_metrics
     WHERE metric_name = 'sla_alert'
     AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     ORDER BY timestamp DESC
     LIMIT 10"
);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLA Monitoring Dashboard - Mathking MVP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            font-size: 14px;
        }

        .time-selector {
            background: white;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .time-selector label {
            font-weight: 600;
            color: #2c3e50;
        }

        .time-selector select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #95a5a6;
        }

        .metric-card.excellent {
            border-left-color: #27ae60;
            background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
        }

        .metric-card.good {
            border-left-color: #3498db;
            background: linear-gradient(135deg, #f0f8ff 0%, #ffffff 100%);
        }

        .metric-card.warning {
            border-left-color: #f39c12;
            background: linear-gradient(135deg, #fff9f0 0%, #ffffff 100%);
        }

        .metric-card.critical {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }

        .metric-title {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .metric-value {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .metric-subtitle {
            font-size: 13px;
            color: #7f8c8d;
        }

        .section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #2c3e50;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
        }

        .performance-bars {
            margin-top: 16px;
        }

        .perf-item {
            margin-bottom: 16px;
        }

        .perf-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }

        .perf-bar {
            height: 24px;
            background-color: #ecf0f1;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .perf-fill {
            height: 100%;
            border-radius: 12px;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .perf-fill.excellent { background-color: #27ae60; }
        .perf-fill.good { background-color: #3498db; }
        .perf-fill.fair { background-color: #f39c12; }
        .perf-fill.poor { background-color: #e74c3c; }

        .alert-list {
            list-style: none;
        }

        .alert-item {
            padding: 12px 16px;
            border-left: 4px solid;
            border-radius: 6px;
            margin-bottom: 12px;
            background-color: #fafbfc;
        }

        .alert-item.critical {
            border-left-color: #e74c3c;
            background-color: #fff5f5;
        }

        .alert-item.warning {
            border-left-color: #f39c12;
            background-color: #fff9f0;
        }

        .alert-item.info {
            border-left-color: #3498db;
            background-color: #f0f8ff;
        }

        .alert-time {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 4px;
        }

        .recommendation-list {
            list-style: none;
        }

        .recommendation-item {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            background-color: #fafbfc;
            border-left: 4px solid #3498db;
        }

        .recommendation-header {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .recommendation-actions {
            margin-top: 12px;
            padding-left: 20px;
        }

        .recommendation-actions li {
            margin-bottom: 6px;
            color: #555;
            font-size: 14px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }

        .refresh-btn {
            background-color: #667eea;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .refresh-btn:hover {
            background-color: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 SLA Monitoring Dashboard</h1>
            <div class="header-info">
                <span>User: <strong><?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?></strong></span>
                <span>Last Updated: <?php echo $results['timestamp']; ?></span>
            </div>
        </div>

        <!-- Time Period Selector -->
        <div class="time-selector">
            <label for="hours">Time Period:</label>
            <select id="hours" name="hours" onchange="window.location.href='?hours='+this.value">
                <option value="1" <?php echo $hours === 1 ? 'selected' : ''; ?>>Last Hour</option>
                <option value="6" <?php echo $hours === 6 ? 'selected' : ''; ?>>Last 6 Hours</option>
                <option value="24" <?php echo $hours === 24 ? 'selected' : ''; ?>>Last 24 Hours</option>
                <option value="72" <?php echo $hours === 72 ? 'selected' : ''; ?>>Last 3 Days</option>
                <option value="168" <?php echo $hours === 168 ? 'selected' : ''; ?>>Last Week</option>
            </select>
            <a href="?hours=<?php echo $hours; ?>" class="refresh-btn">🔄 Refresh</a>
        </div>

        <!-- SLA Compliance Metrics -->
        <div class="metrics-grid">
            <?php if ($results['sla_compliance']['status'] !== 'no_data'): ?>
                <div class="metric-card <?php echo $results['sla_compliance']['status']; ?>">
                    <div class="metric-title">SLA Compliance</div>
                    <div class="metric-value"><?php echo $results['sla_compliance']['compliance_percent']; ?>%</div>
                    <div class="metric-subtitle">
                        <?php echo $results['sla_compliance']['sla_met_count']; ?> / <?php echo $results['sla_compliance']['total_pipelines']; ?> within SLA
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-title">Total Pipelines</div>
                    <div class="metric-value"><?php echo $results['sla_compliance']['total_pipelines']; ?></div>
                    <div class="metric-subtitle">Executions in period</div>
                </div>

                <div class="metric-card">
                    <div class="metric-title">SLA Violations</div>
                    <div class="metric-value"><?php echo $results['sla_compliance']['sla_violated_count']; ?></div>
                    <div class="metric-subtitle">Exceeded <?php echo $results['sla_compliance']['sla_limit_seconds']; ?>s limit</div>
                </div>
            <?php endif; ?>

            <?php if ($results['pipeline_performance']['status'] === 'ok'): ?>
                <div class="metric-card <?php echo $results['pipeline_performance']['performance_rating']; ?>">
                    <div class="metric-title">Avg Execution Time</div>
                    <div class="metric-value"><?php echo $results['pipeline_performance']['avg_time_seconds']; ?>s</div>
                    <div class="metric-subtitle">Rating: <?php echo strtoupper($results['pipeline_performance']['performance_rating']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Layer Performance -->
        <div class="section">
            <h2>Layer Performance</h2>
            <div class="performance-bars">
                <?php foreach ($results['layer_performance'] as $layer => $perf): ?>
                    <?php if (isset($perf['avg_time_ms'])): ?>
                        <div class="perf-item">
                            <div class="perf-label">
                                <span><?php echo ucfirst($layer); ?> Layer</span>
                                <span><?php echo $perf['avg_time_ms']; ?>ms (<?php echo strtoupper($perf['performance_rating']); ?>)</span>
                            </div>
                            <div class="perf-bar">
                                <?php
                                    $max_time = 5000; // 5 seconds max for visualization
                                    $width = min(($perf['avg_time_ms'] / $max_time) * 100, 100);
                                ?>
                                <div class="perf-fill <?php echo $perf['performance_rating']; ?>" style="width: <?php echo $width; ?>%">
                                    <?php echo $perf['avg_time_ms']; ?>ms
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Anomalies -->
        <?php if (!empty($results['anomalies'])): ?>
            <div class="section">
                <h2>Anomalies Detected</h2>
                <ul class="alert-list">
                    <?php foreach ($results['anomalies'] as $anomaly): ?>
                        <li class="alert-item <?php echo $anomaly['severity']; ?>">
                            <strong><?php echo strtoupper($anomaly['severity']); ?>:</strong> <?php echo htmlspecialchars($anomaly['message']); ?>
                            <?php if (isset($anomaly['count'])): ?>
                                <br><small>Count: <?php echo $anomaly['count']; ?></small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Recommendations -->
        <?php if (!empty($results['recommendations'])): ?>
            <div class="section">
                <h2>Recommendations</h2>
                <ul class="recommendation-list">
                    <?php foreach ($results['recommendations'] as $rec): ?>
                        <li class="recommendation-item">
                            <div class="recommendation-header">
                                <?php echo strtoupper($rec['priority']); ?> - <?php echo ucwords(str_replace('_', ' ', $rec['category'])); ?>
                            </div>
                            <p><?php echo htmlspecialchars($rec['message']); ?></p>
                            <ul class="recommendation-actions">
                                <?php foreach ($rec['actions'] as $action): ?>
                                    <li><?php echo htmlspecialchars($action); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Recent Alerts -->
        <?php if (!empty($recent_alerts)): ?>
            <div class="section">
                <h2>Recent Alerts (Last 24h)</h2>
                <ul class="alert-list">
                    <?php foreach ($recent_alerts as $alert_row): ?>
                        <?php
                            $alert = json_decode($alert_row['context'], true);
                            if ($alert):
                        ?>
                            <li class="alert-item <?php echo $alert['level']; ?>">
                                <strong><?php echo strtoupper($alert['level']); ?>:</strong> <?php echo htmlspecialchars($alert['message']); ?>
                                <div class="alert-time"><?php echo $alert['timestamp']; ?></div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($results['sla_compliance']['status'] === 'no_data'): ?>
            <div class="no-data">
                <p>📭 No monitoring data available for the selected time period.</p>
                <p>Run some pipeline executions to see SLA metrics.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
