# Execution Layer - Test Execution Guide

## Overview

This guide explains how to test the Intervention Dispatcher implementation on the live server.

## Files Created

1. **intervention_dispatcher.php** - Core dispatcher logic (342 lines)
2. **api/execute.php** - REST API endpoint (197 lines)
3. **tests/intervention_dispatcher.test.php** - Unit tests (489 lines)

## Server Test Execution

### 1. Run Unit Tests via Browser

Access the test file through the web server:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/execution/tests/intervention_dispatcher.test.php
```

### 2. Run Unit Tests via SSH (if available)

```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/execution/tests
php intervention_dispatcher.test.php
```

Expected output:
```
======================================================================
Mathking Agentic MVP System - Intervention Dispatcher Tests
======================================================================

Test 01: Template loading
✅ PASS: Templates loaded successfully
✅ PASS: Required action templates exist
✅ PASS: Critical micro_break template exists

Test 02: Prepare micro_break (critical)
✅ PASS: Intervention ID generated
✅ PASS: Intervention type is micro_break
✅ PASS: Target student ID is 123
...

======================================================================
Test Summary
======================================================================
Tests run: 10
Successes: 10
Failures: 0
```

### 3. Test API Endpoint via curl

#### Test Mode 1: Execute from decision_id

```bash
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/execution/api/execute.php \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID" \
  -d '{
    "decision_id": 1
  }'
```

Expected response:
```json
{
  "success": true,
  "data": {
    "intervention_id": "int-673456789-123",
    "intervention_db_id": 1,
    "student_id": 123,
    "action": "micro_break",
    "status": "sent",
    "lms_message_id": 1730567890123,
    "timestamp": "2025-11-02T10:30:18Z"
  },
  "performance": {
    "execution_time_ms": 145.8,
    "total_time_ms": 156.2
  }
}
```

#### Test Mode 2: Execute from full decision object

```bash
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/execution/api/execute.php \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID" \
  -d '{
    "student_id": 123,
    "action": "micro_break",
    "params": "{\"duration_minutes\": 3, \"urgency\": \"medium\"}",
    "confidence": 0.85,
    "rationale": "Calm score is low",
    "rule_id": "calm_break_low"
  }'
```

### 4. Integration Test with Full Pipeline

Test complete Sensing → Decision → Execution flow:

```bash
# Step 1: Get metrics (Sensing Layer)
METRICS=$(curl -X POST https://mathking.kr/.../sensing/api/metrics.php \
  -H "Content-Type: application/json" \
  -d '{"student_id": 123, "session_duration": 600, "interruptions": 8, "focus_time": 300}')

# Step 2: Make decision (Decision Layer)
DECISION=$(curl -X POST https://mathking.kr/.../decision/api/decide.php \
  -H "Content-Type: application/json" \
  -d "$METRICS")

# Step 3: Execute intervention (Execution Layer)
EXECUTION=$(curl -X POST https://mathking.kr/.../execution/api/execute.php \
  -H "Content-Type: application/json" \
  -d "$DECISION")

echo "Execution result: $EXECUTION"
```

## Database Verification

Check interventions in database:

```sql
-- Check recent interventions
SELECT
    id,
    intervention_id,
    type,
    target_student_id,
    status,
    executed_at,
    created_at
FROM mdl_mvp_intervention_execution
ORDER BY created_at DESC
LIMIT 10;

-- Check intervention by ID
SELECT * FROM mdl_mvp_intervention_execution
WHERE intervention_id = 'int-673456789-123';

-- Check execution performance
SELECT
    metric_name,
    AVG(metric_value) as avg_ms,
    MIN(metric_value) as min_ms,
    MAX(metric_value) as max_ms,
    COUNT(*) as count
FROM mdl_mvp_system_metrics
WHERE metric_name = 'intervention_execution_time'
GROUP BY metric_name;
```

## Test Coverage

### Unit Tests (10 tests)

1. ✅ Template loading
2. ✅ Prepare intervention - micro_break critical
3. ✅ Prepare intervention - micro_break low
4. ✅ Prepare intervention - ask_teacher
5. ✅ Prepare intervention - none (no action)
6. ✅ Execute intervention - success
7. ✅ Get intervention status
8. ✅ Metadata inclusion
9. ✅ Intervention ID uniqueness
10. ✅ Message format validation

### API Tests

- ✅ POST with decision_id (Mode 1)
- ✅ POST with full decision object (Mode 2)
- ✅ Handle 'none' action gracefully
- ✅ Error handling (invalid input, missing fields)
- ✅ Performance metrics tracking

## Expected Behaviors

### Success Cases

1. **micro_break intervention**:
   - Message prepared with urgency level
   - Duration customized in message
   - Status: pending → sent
   - LMS message ID generated

2. **ask_teacher intervention**:
   - Teacher-focused message
   - Medium urgency
   - Status tracked

3. **none action**:
   - No intervention dispatched
   - API returns success with message

### Error Cases

1. **Invalid decision_id**:
   - HTTP 404
   - Error: "Decision not found"

2. **Missing required fields**:
   - HTTP 400
   - Error specifies missing field

3. **Database connection failure**:
   - HTTP 500
   - Error logged with location

## Performance Targets

- **Execution time**: < 200ms
- **Total API response**: < 300ms
- **Database insert**: < 50ms
- **LMS dispatch**: < 100ms (MVP simulated)

## Troubleshooting

### Common Issues

1. **"Class not found"**: Check file paths in require_once
2. **Database errors**: Verify tables exist (run migrations)
3. **Permission denied**: Check file permissions (644 for PHP files)
4. **Session required**: Must be logged into Moodle

### Debug Mode

Enable detailed logging:

```php
// In app.config.php
define('MVP_DEBUG_MODE', true);
define('MVP_LOG_LEVEL', 'DEBUG');
```

Then check logs:
```bash
tail -f /path/to/mvp_system/logs/execution.log
```

## Next Steps

After successful testing:

1. ✅ Mark Task 2.3 as completed
2. → Proceed to Task 2.4: Implement integrated pipeline Orchestrator
3. → Test end-to-end Calm Break scenario

---

Last Updated: 2025-11-02
Test Environment: Live Server (mathking.kr)
