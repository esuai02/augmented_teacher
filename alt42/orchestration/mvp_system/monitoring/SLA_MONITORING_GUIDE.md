# SLA Monitoring System - Complete Guide

## Overview

The SLA Monitoring System tracks pipeline execution performance and ensures the Mathking Agentic MVP meets its **Service Level Agreement (SLA)** target of **3 minutes (180 seconds)** per complete intervention cycle.

## Components

### 1. SLA Monitor Script (`sla_monitor.php`)
- **Purpose**: Analyze pipeline performance and SLA compliance
- **Location**: `mvp_system/monitoring/sla_monitor.php`
- **Run Mode**: Command line (manual or cron)
- **Capabilities**:
  - Pipeline performance analysis
  - SLA compliance tracking
  - Layer-by-layer performance breakdown
  - Anomaly detection
  - Alert generation
  - Recommendations

### 2. SLA Dashboard (`sla_dashboard.php`)
- **Purpose**: Web-based monitoring interface
- **Location**: `mvp_system/monitoring/sla_dashboard.php`
- **Access**: Teachers and admins only
- **Features**:
  - Real-time SLA metrics
  - Performance visualizations
  - Alert history
  - Actionable recommendations

## SLA Targets

### MVP Targets
- **Primary SLA**: < 180 seconds (3 minutes) per pipeline
- **Target Compliance**: ≥ 90% of executions within SLA
- **Warning Threshold**: < 90% compliance
- **Critical Threshold**: < 75% compliance

### Expected Performance (MVP with Simulated LMS)
```
Sensing Layer:    100-200ms
Decision Layer:    50-150ms
Execution Layer:  100-200ms (simulated)
-----------------------------------------
Total Pipeline:   250-550ms (well within SLA)
```

### Production Considerations
With real LMS integration:
```
Sensing Layer:    100-200ms
Decision Layer:    50-150ms
Execution Layer:  1-3 seconds (real LMS)
Network Latency:  100-500ms
Database Ops:     50-200ms
-----------------------------------------
Total Pipeline:   2-5 seconds (still within SLA)
```

## Running the SLA Monitor

### Command Line (Manual)

```bash
# Navigate to monitoring directory
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/monitoring

# Run for last 24 hours (default)
php sla_monitor.php

# Run for last hour
php sla_monitor.php 1

# Run for last 6 hours
php sla_monitor.php 6

# Run for last 3 days
php sla_monitor.php 72

# Run for last week
php sla_monitor.php 168
```

### Expected Output

```
==============================================================================
  MATHKING AGENTIC MVP SYSTEM - SLA MONITORING REPORT
==============================================================================

📅 Report Time: 2025-11-02 14:30:00
⏱️  Period: Last 24 hours

==============================================================================
SLA COMPLIANCE
==============================================================================
✅ Status: EXCELLENT
   Total Pipelines: 147
   SLA Met: 145
   SLA Violated: 2
   Compliance Rate: 98.64%
   Target: ≥ 90.0%
   SLA Limit: 180 seconds

==============================================================================
PIPELINE PERFORMANCE
==============================================================================
   Total Executions: 147
   Average Time: 0.385s (385.2ms)
   Min Time: 298.1ms
   Max Time: 512.7ms
   Std Deviation: 45.3ms
   Performance Rating: EXCELLENT

==============================================================================
LAYER PERFORMANCE
==============================================================================

SENSING Layer:
   Average: 145.2ms
   Min: 98.5ms
   Max: 198.7ms
   Rating: EXCELLENT

DECISION Layer:
   Average: 98.5ms
   Min: 45.3ms
   Max: 156.2ms
   Rating: EXCELLENT

EXECUTION Layer:
   Average: 141.5ms
   Min: 89.2ms
   Max: 203.4ms
   Rating: EXCELLENT

==============================================================================
END OF REPORT
==============================================================================
```

### Exit Codes

- **0**: SLA compliance ≥ 90% (Success)
- **1**: SLA compliance 75-90% (Warning)
- **2**: SLA compliance < 75% (Critical)

## Automated Monitoring with Cron

### Setup Cron Job

For continuous monitoring, run the script every 5 minutes:

```bash
# Edit crontab
crontab -e

# Add this line (adjust paths as needed)
*/5 * * * * cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/monitoring && php sla_monitor.php 1 >> /path/to/logs/sla_monitor.log 2>&1
```

This runs every 5 minutes and:
- Analyzes last 1 hour of data
- Logs output to file
- Sends alerts if thresholds violated

### Cron Schedule Options

```bash
# Every 5 minutes (recommended for production)
*/5 * * * * php sla_monitor.php 1 >> logs/sla.log 2>&1

# Every 15 minutes (lighter load)
*/15 * * * * php sla_monitor.php 1 >> logs/sla.log 2>&1

# Every hour (for trend analysis)
0 * * * * php sla_monitor.php 24 >> logs/sla_hourly.log 2>&1

# Daily summary at midnight
0 0 * * * php sla_monitor.php 168 >> logs/sla_weekly.log 2>&1
```

## Web Dashboard Access

### URL

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/monitoring/sla_dashboard.php
```

### Features

1. **Time Period Selection**: View metrics for 1 hour, 6 hours, 24 hours, 3 days, or 1 week
2. **SLA Compliance Card**: Overall compliance percentage with visual status
3. **Performance Metrics**: Total pipelines, violations, average execution time
4. **Layer Performance Bars**: Visual breakdown of each layer's performance
5. **Anomalies Section**: Recent performance anomalies and unusual patterns
6. **Recommendations**: Actionable suggestions for optimization
7. **Recent Alerts**: Last 24 hours of critical and warning alerts

### Dashboard Screenshots

#### Excellent Status (≥95% compliance)
- Green border on compliance card
- All metrics in healthy range
- Minimal or no recommendations

#### Warning Status (75-90% compliance)
- Orange border on compliance card
- Performance optimization recommendations
- Action items listed

#### Critical Status (<75% compliance)
- Red border on compliance card
- Critical alerts visible
- Immediate action required

## Understanding Metrics

### SLA Compliance Percentage

**Formula**: `(SLA Met Count / Total Pipelines) × 100`

**Interpretation**:
- **≥95%**: Excellent - System performing optimally
- **90-95%**: Good - Minor optimization opportunities
- **75-90%**: Warning - Performance degradation detected
- **<75%**: Critical - Immediate investigation required

### Performance Rating

**Based on average execution time**:
- **Excellent**: < 1 second
- **Good**: 1-3 seconds
- **Fair**: 3-10 seconds
- **Poor**: > 10 seconds

### Layer Performance

Each layer (Sensing, Decision, Execution) is tracked separately:
- **Average Time**: Mean execution time across all runs
- **Min/Max Time**: Performance envelope
- **Rating**: Performance classification

## Anomaly Detection

### Types of Anomalies

1. **Slow Executions**
   - Triggers: Execution >2× average time
   - Severity: Warning
   - Action: Review individual execution logs

2. **Recent SLA Violations**
   - Triggers: >5 violations in last hour
   - Severity: Critical
   - Action: Immediate investigation required

3. **Execution Rate Changes**
   - Triggers: >50% change hour-over-hour
   - Severity: Info/Warning
   - Action: Monitor for patterns

## Recommendations

The system generates actionable recommendations based on:
- SLA compliance trends
- Average execution times
- Layer performance bottlenecks
- Anomaly patterns

### Example Recommendations

**Critical SLA Compliance (<75%)**:
- Review slowest pipeline executions
- Check database query performance
- Verify Python script execution times
- Consider infrastructure scaling

**High Average Execution Time (>60s)**:
- Profile individual layer performance
- Check network latency
- Review database connection pooling
- Consider caching frequently accessed data

**Slow Layer Performance (>30s average)**:
- Optimize specific layer implementation
- Review layer dependencies
- Check layer resource usage

## Alerting System

### Alert Levels

1. **Critical** (🚨)
   - SLA compliance < 75%
   - >5 violations in last hour
   - Requires immediate action

2. **Warning** (⚠️)
   - SLA compliance 75-90%
   - Slow executions detected
   - Requires investigation

3. **Info** (ℹ️)
   - Execution rate changes
   - Minor performance variations
   - For awareness

### Alert Delivery (MVP)

Currently logs to:
- `logs/sla_monitor.log` file
- Database (`mdl_mvp_system_metrics` table)
- Dashboard display

### Production Alert Channels

For production deployment, configure:
- **Email**: Send to ops team
- **Slack**: Webhook integration
- **PagerDuty**: Critical alerts
- **SMS**: Critical production issues

## Troubleshooting

### No Data Available

**Symptom**: "No monitoring data available" message

**Causes**:
- No pipeline executions in time period
- Database connection issues
- Metrics not being recorded

**Solutions**:
1. Run a test pipeline: `php orchestrator.php 123`
2. Check database tables exist: `SHOW TABLES LIKE 'mdl_mvp_%'`
3. Verify logging configuration in `app.config.php`

### Low SLA Compliance

**Symptom**: Compliance < 90%

**Investigation Steps**:
1. Check layer performance breakdown
2. Review recent slow executions
3. Analyze database query performance
4. Check Python script execution times
5. Verify server resources (CPU, memory)

**Common Causes**:
- Database query performance
- Python script execution delays
- Network latency
- Resource contention

### High Execution Times

**Symptom**: Average execution time >3 seconds

**Investigation Steps**:
1. Identify slowest layer
2. Profile layer execution
3. Check for resource bottlenecks
4. Review database indexes

**Optimization Targets**:
- Sensing layer: Optimize Python calm_score.py
- Decision layer: Simplify rule evaluation
- Execution layer: Reduce LMS API overhead

## Database Queries for Manual Analysis

### Recent Pipeline Executions

```sql
SELECT
    JSON_EXTRACT(context, '$.pipeline_id') as pipeline_id,
    metric_value as time_ms,
    timestamp
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_total_time'
ORDER BY timestamp DESC
LIMIT 20;
```

### SLA Violations

```sql
SELECT
    JSON_EXTRACT(context, '$.pipeline_id') as pipeline_id,
    timestamp
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_sla_met'
AND metric_value = 0
ORDER BY timestamp DESC
LIMIT 10;
```

### Layer Performance Comparison

```sql
SELECT
    metric_name,
    AVG(metric_value) as avg_ms,
    MIN(metric_value) as min_ms,
    MAX(metric_value) as max_ms,
    COUNT(*) as count
FROM mdl_mvp_system_metrics
WHERE metric_name IN (
    'pipeline_sensing_time',
    'pipeline_decision_time',
    'pipeline_execution_time'
)
AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY metric_name;
```

### Hourly SLA Compliance Trend

```sql
SELECT
    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00') as hour,
    COUNT(*) as total,
    SUM(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) as sla_met,
    AVG(metric_value) * 100 as compliance_percent
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_sla_met'
AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY hour
ORDER BY hour;
```

## Performance Optimization Tips

### Database Optimization

1. **Add Indexes** (if not exists):
```sql
CREATE INDEX idx_metrics_name_time ON mdl_mvp_system_metrics(metric_name, timestamp);
CREATE INDEX idx_decision_timestamp ON mdl_mvp_decision_log(timestamp);
```

2. **Regular Maintenance**:
```sql
-- Archive old metrics (keep last 30 days)
DELETE FROM mdl_mvp_system_metrics
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Optimize tables
OPTIMIZE TABLE mdl_mvp_system_metrics;
```

### Python Script Optimization

- Cache frequently loaded data
- Optimize pandas operations
- Profile with `cProfile`
- Consider multiprocessing for batch operations

### PHP Optimization

- Enable opcode caching (OPcache)
- Use connection pooling
- Minimize database queries
- Implement result caching

## Next Steps

After successful SLA monitoring implementation:

1. ✅ **Task 5.2 completed**: SLA monitoring operational
2. → **Task 5.3**: Verify performance and optimize
3. → **Production deployment**: MVP ready for teacher testing

---

**Status**: Ready for deployment
**SLA Target**: < 180 seconds (3 minutes)
**Target Compliance**: ≥ 90%
**Last Updated**: 2025-11-02
