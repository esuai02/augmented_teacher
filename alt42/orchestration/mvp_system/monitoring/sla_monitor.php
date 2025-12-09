<?php
// File: mvp_system/monitoring/sla_monitor.php (Line 1)
// Mathking Agentic MVP System - SLA Monitoring Script
//
// Purpose: Monitor pipeline SLA compliance and system health
// Run: php sla_monitor.php [hours] (default: 24 hours)
// Cron: */5 * * * * php /path/to/sla_monitor.php >> /path/to/logs/sla_monitor.log 2>&1

// Add parent directory to path
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

class SLAMonitor
{
    /**
     * SLA monitoring and alerting system
     */

    private $mvp_db;
    private $logger;
    private $sla_limit_seconds = 180; // 3 minutes (180 seconds) - MVP target
    private $warning_threshold = 0.90; // Warn if compliance < 90%
    private $critical_threshold = 0.75; // Critical if compliance < 75%

    public function __construct()
    {
        $this->mvp_db = new MVPDatabase();
        $this->logger = new MVPLogger('sla_monitor');
    }

    /**
     * Run SLA monitoring for specified time period
     *
     * @param int $hours Time period to analyze (default: 24)
     * @return array Monitoring results
     */
    public function monitor($hours = 24)
    {
        $this->logger->info("SLA monitoring started", ['period_hours' => $hours]);

        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'period_hours' => $hours,
            'pipeline_performance' => $this->analyzePipelinePerformance($hours),
            'sla_compliance' => $this->analyzeSLACompliance($hours),
            'layer_performance' => $this->analyzeLayerPerformance($hours),
            'anomalies' => $this->detectAnomalies($hours),
            'recommendations' => []
        ];

        // Generate recommendations based on results
        $results['recommendations'] = $this->generateRecommendations($results);

        // Check for alerts
        $alerts = $this->checkAlerts($results);
        if (!empty($alerts)) {
            $results['alerts'] = $alerts;
            $this->sendAlerts($alerts);
        }

        // Log monitoring results
        $this->logResults($results);

        $this->logger->info("SLA monitoring completed", [
            'sla_compliance' => $results['sla_compliance']['compliance_percent'],
            'total_pipelines' => $results['sla_compliance']['total_pipelines'],
            'alert_count' => count($alerts)
        ]);

        return $results;
    }

    /**
     * Analyze overall pipeline performance
     */
    private function analyzePipelinePerformance($hours)
    {
        $query = "
            SELECT
                COUNT(*) as total_executions,
                AVG(metric_value) as avg_time_ms,
                MIN(metric_value) as min_time_ms,
                MAX(metric_value) as max_time_ms,
                STDDEV(metric_value) as stddev_time_ms
            FROM mdl_mvp_system_metrics
            WHERE metric_name = 'pipeline_total_time'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ";

        $result = $this->mvp_db->query($query, [$hours]);

        if (empty($result)) {
            return [
                'status' => 'no_data',
                'message' => 'No pipeline executions in the specified period at sla_monitor.php:94'
            ];
        }

        $data = $result[0];

        return [
            'status' => 'ok',
            'total_executions' => (int)$data['total_executions'],
            'avg_time_ms' => round($data['avg_time_ms'], 2),
            'avg_time_seconds' => round($data['avg_time_ms'] / 1000, 3),
            'min_time_ms' => round($data['min_time_ms'], 2),
            'max_time_ms' => round($data['max_time_ms'], 2),
            'stddev_ms' => round($data['stddev_time_ms'], 2),
            'performance_rating' => $this->ratePerformance($data['avg_time_ms'])
        ];
    }

    /**
     * Analyze SLA compliance
     */
    private function analyzeSLACompliance($hours)
    {
        $query = "
            SELECT
                COUNT(*) as total_pipelines,
                SUM(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) as sla_met_count,
                SUM(CASE WHEN metric_value = 0 THEN 1 ELSE 0 END) as sla_violated_count
            FROM mdl_mvp_system_metrics
            WHERE metric_name = 'pipeline_sla_met'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ";

        $result = $this->mvp_db->query($query, [$hours]);

        if (empty($result) || $result[0]['total_pipelines'] == 0) {
            return [
                'status' => 'no_data',
                'message' => 'No SLA data available at sla_monitor.php:132'
            ];
        }

        $data = $result[0];
        $compliance_percent = ($data['sla_met_count'] / $data['total_pipelines']) * 100;

        // Determine compliance status
        $status = 'excellent'; // >= 95%
        if ($compliance_percent < 95) $status = 'good'; // 90-95%
        if ($compliance_percent < $this->warning_threshold * 100) $status = 'warning'; // 75-90%
        if ($compliance_percent < $this->critical_threshold * 100) $status = 'critical'; // < 75%

        return [
            'status' => $status,
            'total_pipelines' => (int)$data['total_pipelines'],
            'sla_met_count' => (int)$data['sla_met_count'],
            'sla_violated_count' => (int)$data['sla_violated_count'],
            'compliance_percent' => round($compliance_percent, 2),
            'sla_limit_seconds' => $this->sla_limit_seconds,
            'target_compliance' => 90.0
        ];
    }

    /**
     * Analyze individual layer performance
     */
    private function analyzeLayerPerformance($hours)
    {
        $layers = ['sensing', 'decision', 'execution'];
        $results = [];

        foreach ($layers as $layer) {
            $query = "
                SELECT
                    AVG(metric_value) as avg_time_ms,
                    MIN(metric_value) as min_time_ms,
                    MAX(metric_value) as max_time_ms,
                    COUNT(*) as execution_count
                FROM mdl_mvp_system_metrics
                WHERE metric_name = ?
                AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ";

            $metric_name = "pipeline_{$layer}_time";
            $result = $this->mvp_db->query($query, [$metric_name, $hours]);

            if (!empty($result) && $result[0]['execution_count'] > 0) {
                $data = $result[0];
                $results[$layer] = [
                    'avg_time_ms' => round($data['avg_time_ms'], 2),
                    'min_time_ms' => round($data['min_time_ms'], 2),
                    'max_time_ms' => round($data['max_time_ms'], 2),
                    'execution_count' => (int)$data['execution_count'],
                    'performance_rating' => $this->ratePerformance($data['avg_time_ms'])
                ];
            } else {
                $results[$layer] = [
                    'status' => 'no_data',
                    'message' => "No {$layer} layer data at sla_monitor.php:194"
                ];
            }
        }

        return $results;
    }

    /**
     * Detect performance anomalies
     */
    private function detectAnomalies($hours)
    {
        $anomalies = [];

        // Check for slow executions (> 2x average)
        $slow_query = "
            SELECT
                JSON_EXTRACT(context, '$.pipeline_id') as pipeline_id,
                metric_value as time_ms,
                timestamp
            FROM mdl_mvp_system_metrics
            WHERE metric_name = 'pipeline_total_time'
            AND metric_value > (
                SELECT AVG(metric_value) * 2
                FROM mdl_mvp_system_metrics
                WHERE metric_name = 'pipeline_total_time'
                AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            )
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY metric_value DESC
            LIMIT 10
        ";

        $slow_executions = $this->mvp_db->query($slow_query, [$hours, $hours]);

        if (!empty($slow_executions)) {
            $anomalies[] = [
                'type' => 'slow_executions',
                'severity' => 'warning',
                'count' => count($slow_executions),
                'details' => array_slice($slow_executions, 0, 5), // Top 5
                'message' => count($slow_executions) . " executions >2x average time at sla_monitor.php:236"
            ];
        }

        // Check for SLA violations in last hour
        $recent_violations = $this->mvp_db->query(
            "SELECT COUNT(*) as count
             FROM mdl_mvp_system_metrics
             WHERE metric_name = 'pipeline_sla_met'
             AND metric_value = 0
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );

        if ($recent_violations[0]['count'] > 0) {
            $anomalies[] = [
                'type' => 'recent_sla_violations',
                'severity' => 'critical',
                'count' => (int)$recent_violations[0]['count'],
                'message' => "{$recent_violations[0]['count']} SLA violations in last hour at sla_monitor.php:254"
            ];
        }

        // Check for execution rate changes
        $rate_check = $this->checkExecutionRateChange($hours);
        if ($rate_check) {
            $anomalies[] = $rate_check;
        }

        return $anomalies;
    }

    /**
     * Check for execution rate changes
     */
    private function checkExecutionRateChange($hours)
    {
        // Compare current hour vs previous hour
        $current_hour = $this->mvp_db->query(
            "SELECT COUNT(*) as count
             FROM mdl_mvp_system_metrics
             WHERE metric_name = 'pipeline_total_time'
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );

        $previous_hour = $this->mvp_db->query(
            "SELECT COUNT(*) as count
             FROM mdl_mvp_system_metrics
             WHERE metric_name = 'pipeline_total_time'
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
             AND timestamp < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );

        $current_count = $current_hour[0]['count'];
        $previous_count = $previous_hour[0]['count'];

        if ($previous_count > 0) {
            $change_percent = (($current_count - $previous_count) / $previous_count) * 100;

            // Alert if >50% increase or >80% decrease
            if (abs($change_percent) > 50) {
                return [
                    'type' => 'execution_rate_change',
                    'severity' => abs($change_percent) > 80 ? 'warning' : 'info',
                    'current_count' => $current_count,
                    'previous_count' => $previous_count,
                    'change_percent' => round($change_percent, 1),
                    'message' => "Execution rate changed by " . round($change_percent, 1) . "% at sla_monitor.php:305"
                ];
            }
        }

        return null;
    }

    /**
     * Rate performance (excellent/good/fair/poor)
     */
    private function ratePerformance($avg_time_ms)
    {
        $avg_seconds = $avg_time_ms / 1000;

        if ($avg_seconds < 1) return 'excellent';
        if ($avg_seconds < 3) return 'good';
        if ($avg_seconds < 10) return 'fair';
        return 'poor';
    }

    /**
     * Generate recommendations based on monitoring results
     */
    private function generateRecommendations($results)
    {
        $recommendations = [];

        // Check SLA compliance
        if (isset($results['sla_compliance']['compliance_percent'])) {
            $compliance = $results['sla_compliance']['compliance_percent'];

            if ($compliance < 75) {
                $recommendations[] = [
                    'priority' => 'critical',
                    'category' => 'sla_compliance',
                    'message' => "SLA compliance critically low ({$compliance}%). Immediate optimization required.",
                    'actions' => [
                        'Review slowest pipeline executions',
                        'Check database query performance',
                        'Verify Python script execution times',
                        'Consider infrastructure scaling'
                    ]
                ];
            } elseif ($compliance < 90) {
                $recommendations[] = [
                    'priority' => 'high',
                    'category' => 'sla_compliance',
                    'message' => "SLA compliance below target ({$compliance}% < 90%). Optimization recommended.",
                    'actions' => [
                        'Analyze layer performance bottlenecks',
                        'Optimize database queries',
                        'Review Python script efficiency'
                    ]
                ];
            }
        }

        // Check average execution time
        if (isset($results['pipeline_performance']['avg_time_ms'])) {
            $avg_time_ms = $results['pipeline_performance']['avg_time_ms'];
            $avg_seconds = $avg_time_ms / 1000;

            if ($avg_seconds > 60) {
                $recommendations[] = [
                    'priority' => 'high',
                    'category' => 'performance',
                    'message' => "Average execution time high (" . round($avg_seconds, 1) . "s). Performance optimization needed.",
                    'actions' => [
                        'Profile individual layer performance',
                        'Check network latency',
                        'Review database connection pooling',
                        'Consider caching frequently accessed data'
                    ]
                ];
            }
        }

        // Check layer performance
        if (isset($results['layer_performance'])) {
            foreach ($results['layer_performance'] as $layer => $perf) {
                if (isset($perf['avg_time_ms']) && $perf['avg_time_ms'] > 30000) {
                    $recommendations[] = [
                        'priority' => 'medium',
                        'category' => 'layer_performance',
                        'message' => ucfirst($layer) . " layer slow (" . round($perf['avg_time_ms'] / 1000, 1) . "s average).",
                        'actions' => [
                            "Optimize {$layer} layer implementation",
                            "Review {$layer} layer dependencies",
                            "Check {$layer} layer resource usage"
                        ]
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Check for alert conditions
     */
    private function checkAlerts($results)
    {
        $alerts = [];

        // Critical: SLA compliance < 75%
        if (isset($results['sla_compliance']['compliance_percent']) &&
            $results['sla_compliance']['compliance_percent'] < 75) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'sla_compliance',
                'message' => "CRITICAL: SLA compliance at {$results['sla_compliance']['compliance_percent']}% (threshold: 75%)",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Warning: SLA compliance < 90%
        if (isset($results['sla_compliance']['compliance_percent']) &&
            $results['sla_compliance']['compliance_percent'] < 90 &&
            $results['sla_compliance']['compliance_percent'] >= 75) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'sla_compliance',
                'message' => "WARNING: SLA compliance at {$results['sla_compliance']['compliance_percent']}% (target: 90%)",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Critical: Recent SLA violations
        foreach ($results['anomalies'] as $anomaly) {
            if ($anomaly['type'] === 'recent_sla_violations' && $anomaly['count'] > 5) {
                $alerts[] = [
                    'level' => 'critical',
                    'type' => 'sla_violations',
                    'message' => "CRITICAL: {$anomaly['count']} SLA violations in last hour",
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        return $alerts;
    }

    /**
     * Send alerts via logging and notification channels
     */
    private function sendAlerts($alerts)
    {
        foreach ($alerts as $alert) {
            // Log alert
            $log_level = $alert['level'] === 'critical' ? 'error' : 'warning';
            $this->logger->log($log_level, $alert['message'], [
                'alert_type' => $alert['type'],
                'timestamp' => $alert['timestamp']
            ]);

            // Store alert in database for dashboard
            $this->mvp_db->execute(
                "INSERT INTO mdl_mvp_system_metrics
                 (metric_name, metric_value, unit, context, timestamp)
                 VALUES (?, ?, ?, ?, NOW())",
                [
                    'sla_alert',
                    $alert['level'] === 'critical' ? 1 : 0.5,
                    'severity',
                    json_encode($alert)
                ]
            );

            // In production, send to notification channels:
            // - Email to ops team
            // - Slack webhook
            // - PagerDuty for critical alerts
            // - SMS for critical production issues
        }
    }

    /**
     * Log monitoring results
     */
    private function logResults($results)
    {
        // Store monitoring snapshot in database
        $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_system_metrics
             (metric_name, metric_value, unit, context, timestamp)
             VALUES (?, ?, ?, ?, NOW())",
            [
                'sla_monitoring_snapshot',
                $results['sla_compliance']['compliance_percent'] ?? 0,
                'percent',
                json_encode($results)
            ]
        );
    }

    /**
     * Generate human-readable report
     */
    public function generateReport($results)
    {
        $output = "\n";
        $output .= "==" . str_repeat("=", 68) . "\n";
        $output .= "  MATHKING AGENTIC MVP SYSTEM - SLA MONITORING REPORT\n";
        $output .= "==" . str_repeat("=", 68) . "\n\n";

        $output .= "📅 Report Time: {$results['timestamp']}\n";
        $output .= "⏱️  Period: Last {$results['period_hours']} hours\n\n";

        // SLA Compliance
        $output .= "==" . str_repeat("=", 68) . "\n";
        $output .= "SLA COMPLIANCE\n";
        $output .= "==" . str_repeat("=", 68) . "\n";

        if ($results['sla_compliance']['status'] !== 'no_data') {
            $compliance = $results['sla_compliance'];
            $status_icon = $compliance['status'] === 'excellent' ? '✅' :
                          ($compliance['status'] === 'good' ? '✅' :
                          ($compliance['status'] === 'warning' ? '⚠️' : '❌'));

            $output .= "{$status_icon} Status: " . strtoupper($compliance['status']) . "\n";
            $output .= "   Total Pipelines: {$compliance['total_pipelines']}\n";
            $output .= "   SLA Met: {$compliance['sla_met_count']}\n";
            $output .= "   SLA Violated: {$compliance['sla_violated_count']}\n";
            $output .= "   Compliance Rate: {$compliance['compliance_percent']}%\n";
            $output .= "   Target: ≥ {$compliance['target_compliance']}%\n";
            $output .= "   SLA Limit: {$compliance['sla_limit_seconds']} seconds\n";
        } else {
            $output .= "ℹ️  No SLA data available\n";
        }

        // Pipeline Performance
        $output .= "\n" . str_repeat("=", 70) . "\n";
        $output .= "PIPELINE PERFORMANCE\n";
        $output .= str_repeat("=", 70) . "\n";

        if ($results['pipeline_performance']['status'] === 'ok') {
            $perf = $results['pipeline_performance'];
            $output .= "   Total Executions: {$perf['total_executions']}\n";
            $output .= "   Average Time: {$perf['avg_time_seconds']}s ({$perf['avg_time_ms']}ms)\n";
            $output .= "   Min Time: {$perf['min_time_ms']}ms\n";
            $output .= "   Max Time: {$perf['max_time_ms']}ms\n";
            $output .= "   Std Deviation: {$perf['stddev_ms']}ms\n";
            $output .= "   Performance Rating: " . strtoupper($perf['performance_rating']) . "\n";
        } else {
            $output .= "ℹ️  No performance data available\n";
        }

        // Layer Performance
        $output .= "\n" . str_repeat("=", 70) . "\n";
        $output .= "LAYER PERFORMANCE\n";
        $output .= str_repeat("=", 70) . "\n";

        foreach ($results['layer_performance'] as $layer => $perf) {
            $output .= "\n" . strtoupper($layer) . " Layer:\n";
            if (isset($perf['status']) && $perf['status'] === 'no_data') {
                $output .= "   ℹ️  No data available\n";
            } else {
                $output .= "   Average: {$perf['avg_time_ms']}ms\n";
                $output .= "   Min: {$perf['min_time_ms']}ms\n";
                $output .= "   Max: {$perf['max_time_ms']}ms\n";
                $output .= "   Rating: " . strtoupper($perf['performance_rating']) . "\n";
            }
        }

        // Anomalies
        if (!empty($results['anomalies'])) {
            $output .= "\n" . str_repeat("=", 70) . "\n";
            $output .= "ANOMALIES DETECTED\n";
            $output .= str_repeat("=", 70) . "\n";

            foreach ($results['anomalies'] as $anomaly) {
                $icon = $anomaly['severity'] === 'critical' ? '🚨' :
                       ($anomaly['severity'] === 'warning' ? '⚠️' : 'ℹ️');
                $output .= "\n{$icon} " . strtoupper($anomaly['severity']) . ": {$anomaly['type']}\n";
                $output .= "   {$anomaly['message']}\n";
            }
        }

        // Recommendations
        if (!empty($results['recommendations'])) {
            $output .= "\n" . str_repeat("=", 70) . "\n";
            $output .= "RECOMMENDATIONS\n";
            $output .= str_repeat("=", 70) . "\n";

            foreach ($results['recommendations'] as $rec) {
                $icon = $rec['priority'] === 'critical' ? '🚨' :
                       ($rec['priority'] === 'high' ? '⚠️' : 'ℹ️');
                $output .= "\n{$icon} " . strtoupper($rec['priority']) . " - {$rec['category']}\n";
                $output .= "   {$rec['message']}\n";
                $output .= "   Actions:\n";
                foreach ($rec['actions'] as $action) {
                    $output .= "   • {$action}\n";
                }
            }
        }

        // Alerts
        if (isset($results['alerts']) && !empty($results['alerts'])) {
            $output .= "\n" . str_repeat("=", 70) . "\n";
            $output .= "ACTIVE ALERTS\n";
            $output .= str_repeat("=", 70) . "\n";

            foreach ($results['alerts'] as $alert) {
                $icon = $alert['level'] === 'critical' ? '🚨' : '⚠️';
                $output .= "\n{$icon} " . strtoupper($alert['level']) . "\n";
                $output .= "   {$alert['message']}\n";
                $output .= "   Time: {$alert['timestamp']}\n";
            }
        }

        $output .= "\n" . str_repeat("=", 70) . "\n";
        $output .= "END OF REPORT\n";
        $output .= str_repeat("=", 70) . "\n\n";

        return $output;
    }
}


// =============================================================================
// Command Line Execution
// =============================================================================

if (php_sapi_name() === 'cli') {
    // Get hours parameter (default: 24)
    $hours = isset($argv[1]) ? intval($argv[1]) : 24;

    if ($hours <= 0) {
        echo "Error: Hours must be positive integer at sla_monitor.php:624\n";
        exit(1);
    }

    // Run monitoring
    $monitor = new SLAMonitor();
    $results = $monitor->monitor($hours);

    // Generate and print report
    $report = $monitor->generateReport($results);
    echo $report;

    // Exit with appropriate code
    $exit_code = 0;
    if (isset($results['sla_compliance']['compliance_percent']) &&
        $results['sla_compliance']['compliance_percent'] < 75) {
        $exit_code = 2; // Critical
    } elseif (isset($results['sla_compliance']['compliance_percent']) &&
              $results['sla_compliance']['compliance_percent'] < 90) {
        $exit_code = 1; // Warning
    }

    exit($exit_code);
}
?>
