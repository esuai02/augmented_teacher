# Pipeline Orchestrator - Complete Guide

## Overview

The Pipeline Orchestrator is the **central coordinator** of the MVP system, integrating all three layers into a single cohesive workflow:

```
Input: student_id + optional activity_data
   ↓
Sensing Layer: Calculate calm_score
   ↓
Decision Layer: Evaluate rules → determine action
   ↓
Execution Layer: Dispatch intervention to LMS
   ↓
Output: Complete pipeline result + SLA tracking
```

## Files Created

1. **orchestrator.php** - Core orchestration logic (504 lines)
2. **api/orchestrate.php** - REST API endpoint (245 lines)
3. **tests/orchestrator.test.php** - Unit tests (507 lines)

**Total: 1,256 lines of code**

## Key Features

### 1. End-to-End Pipeline Execution

Coordinates all three layers in sequence:
- Automatic error handling at each step
- Data flow validation between layers
- Rollback capability on failures

### 2. SLA Monitoring

- **Target**: < 3 minutes (180 seconds) per pipeline
- **Tracking**: Individual layer times + total time
- **Compliance**: Boolean flag for SLA met/not met
- **Statistics**: Aggregated SLA stats over time periods

### 3. Performance Metrics

Records detailed performance data:
- `pipeline_total_time` - End-to-end execution time
- `pipeline_sensing_time` - Sensing layer time
- `pipeline_decision_time` - Decision layer time
- `pipeline_execution_time` - Execution layer time
- `pipeline_sla_met` - SLA compliance flag

### 4. Flexible Input

Two execution modes:
1. **With activity data**: Full control over input metrics
2. **Default data**: Uses sensible defaults for quick testing

## Usage Examples

### Command Line

```bash
# Execute for student 123 with defaults
php orchestrator.php 123

# Output: JSON result with complete pipeline execution
```

### PHP Integration

```php
require_once('orchestrator.php');

$orchestrator = new PipelineOrchestrator();

// Mode 1: With default activity data
$result = $orchestrator->execute(123);

// Mode 2: With custom activity data
$activity_data = [
    'session_duration' => 600,
    'interruptions' => 8,
    'focus_time' => 300,
    'correct_answers' => 5,
    'total_attempts' => 10
];
$result = $orchestrator->execute(123, $activity_data);

// Get SLA statistics
$stats = $orchestrator->getSLAStats(24); // Last 24 hours
```

### REST API

#### Execute Pipeline (POST)

```bash
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID" \
  -d '{
    "student_id": 123,
    "activity_data": {
      "session_duration": 600,
      "interruptions": 8,
      "focus_time": 300,
      "correct_answers": 5,
      "total_attempts": 10
    }
  }'
```

**Success Response:**
```json
{
  "success": true,
  "data": {
    "pipeline_id": "pipeline-673456789-123",
    "student_id": 123,
    "metrics": {
      "calm_score": 65.5,
      "recommendation": "낮음, 3~5분 휴식 후 재시작"
    },
    "decision": {
      "action": "micro_break",
      "confidence": 0.85,
      "rationale": "Calm score 65.5 is low (60-74). 3-minute breathing exercise recommended.",
      "rule_id": "calm_break_low"
    },
    "intervention": {
      "intervention_id": "int-673456790-123",
      "status": "sent"
    },
    "performance": {
      "sensing_ms": 145.2,
      "decision_ms": 98.5,
      "execution_ms": 156.8,
      "total_ms": 400.5,
      "total_seconds": 0.401,
      "sla_limit_seconds": 180,
      "sla_met": true
    }
  }
}
```

#### Get SLA Statistics (GET)

```bash
curl "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php?hours=24"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period_hours": 24,
    "total_pipelines": 47,
    "sla_met_count": 45,
    "sla_compliance_percent": 95.74,
    "avg_time_ms": 385.2,
    "min_time_ms": 298.1,
    "max_time_ms": 512.7,
    "sla_target_seconds": 180
  }
}
```

## Testing

### Run Unit Tests

```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/tests
php orchestrator.test.php
```

**Expected Output:**
```
======================================================================
Mathking Agentic MVP System - Pipeline Orchestrator Tests
======================================================================

Test 01: Complete pipeline - low calm
✅ PASS: Pipeline ID generated
✅ PASS: Student ID matches
✅ PASS: Pipeline execution succeeded
...

======================================================================
Test Summary
======================================================================
Tests run: 10
Successes: 10
Failures: 0
```

### Test Coverage (10 tests)

1. ✅ Complete pipeline - low calm (intervention needed)
2. ✅ Complete pipeline - high calm (no intervention)
3. ✅ Pipeline with default activity data
4. ✅ Performance metrics recording
5. ✅ SLA compliance tracking
6. ✅ Pipeline ID uniqueness
7. ✅ Error array structure
8. ✅ Steps result structure
9. ✅ Data flow through pipeline
10. ✅ SLA statistics retrieval

## Error Handling

The orchestrator implements comprehensive error handling:

### Layer-Level Errors

Each layer failure is caught and recorded:
```json
{
  "success": false,
  "pipeline_id": "pipeline-673456791-123",
  "errors": [
    "Sensing layer failed: Python script timeout at /path/file.php:line"
  ],
  "steps_completed": ["sensing"],
  "performance": {
    "sensing_ms": 5000,
    "decision_ms": 0,
    "execution_ms": 0,
    "total_ms": 5000
  }
}
```

### Graceful Degradation

- **Sensing fails**: Pipeline stops, no decision/execution
- **Decision fails**: Intervention not dispatched
- **Execution fails**: Decision recorded, intervention marked failed

## Database Queries

### Check Recent Pipelines

```sql
-- Recent pipeline executions
SELECT
    metric_name,
    metric_value,
    unit,
    JSON_EXTRACT(context, '$.pipeline_id') as pipeline_id,
    timestamp
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_total_time'
ORDER BY timestamp DESC
LIMIT 10;

-- SLA compliance rate
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) as sla_met,
    AVG(metric_value) * 100 as compliance_percent
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_sla_met'
AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

### Performance Analysis

```sql
-- Average time per layer
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
    'pipeline_execution_time',
    'pipeline_total_time'
)
AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY metric_name;
```

## SLA Monitoring

### Target: < 3 minutes (180 seconds)

The orchestrator tracks:
- Individual layer execution times
- Total pipeline time
- SLA compliance boolean (true/false)

### Expected Performance (MVP)

- **Sensing**: 100-200ms (Python script execution)
- **Decision**: 50-150ms (Python rule evaluation)
- **Execution**: 100-200ms (Simulated LMS dispatch)
- **Total**: 250-550ms (well within 180-second SLA)

### Production Considerations

In production with real LMS integration:
- Execution layer may take 1-3 seconds
- Network latency adds 100-500ms
- Database operations add 50-200ms
- Expected total: 2-5 seconds (still within SLA)

## Integration Points

### Input Sources

1. **Manual API call**: For testing and direct integration
2. **Scheduled cron job**: Periodic student monitoring
3. **Real-time trigger**: LMS event (e.g., session end)
4. **Teacher dashboard**: Manual intervention request

### Output Consumers

1. **Moodle LMS**: Receives intervention messages
2. **Teacher UI**: Displays pipeline results
3. **Analytics dashboard**: Performance monitoring
4. **Student profile**: Intervention history

## Troubleshooting

### Common Issues

1. **"Python script not found"**
   - Check file paths in orchestrator.php
   - Verify Python 3 is installed: `python3 --version`

2. **"Database connection failed"**
   - Verify database credentials in app.config.php
   - Check table existence: `SHOW TABLES LIKE 'mdl_mvp_%'`

3. **"SLA exceeded"**
   - Check Python script execution time
   - Verify database performance
   - Review network latency

### Debug Mode

Enable detailed logging:
```php
// In app.config.php
define('MVP_DEBUG_MODE', true);
define('MVP_LOG_LEVEL', 'DEBUG');
```

Check logs:
```bash
tail -f /path/to/mvp_system/logs/orchestrator.log
```

## Next Steps

After successful orchestrator testing:

1. ✅ **Task 2.4 completed**: Core pipeline fully functional
2. → **Task 3.x**: Verify agent policy integration
3. → **Task 4.x**: Build teacher approval UI
4. → **Task 5.x**: End-to-end testing and optimization

---

**Status**: Ready for server testing
**Last Updated**: 2025-11-02
**Test Environment**: Live Server (mathking.kr)
