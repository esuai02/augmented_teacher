# Mathking Agentic MVP System - Deployment Checklist

## Pre-Deployment Verification

### Step 1: Connect to Server

```bash
# SSH into production server
ssh user@mathking.kr

# Navigate to MVP system directory
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
```

### Step 2: Verify File Structure

```bash
# Check all required files exist
ls -la config/app.config.php
ls -la lib/database.php
ls -la lib/logger.php
ls -la sensing/calm_score.py
ls -la decision/rule_engine.py
ls -la execution/dispatcher.php
ls -la orchestrator.php
ls -la ui/teacher_panel.php
ls -la api/feedback.php
ls -la monitoring/sla_monitor.php

# Check Python availability
python3 --version
# Expected: Python 3.x

# Check PHP version
php --version
# Expected: PHP 7.1.9
```

### Step 3: Database Setup

```bash
# Run database migration
cd database
php migrate.php

# Verify tables created
mysql -u [user] -p [database] -e "SHOW TABLES LIKE 'mdl_mvp_%';"
# Expected output: 5 tables
# - mdl_mvp_snapshot_metrics
# - mdl_mvp_decision_log
# - mdl_mvp_intervention_execution
# - mdl_mvp_teacher_feedback
# - mdl_mvp_system_metrics
```

### Step 4: Test Individual Components

```bash
# Navigate to tests directory
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/tests

# Test Sensing Layer
cd ../sensing/tests
python3 calm_score.test.py
# Expected: 12/12 tests pass

# Test Decision Layer
cd ../../decision/tests
python3 rule_engine.test.py
# Expected: 12/12 tests pass

# Test Execution Layer
cd ../../tests
php dispatcher.test.php
# Expected: 10/10 tests pass

# Test Orchestrator
php orchestrator.test.php
# Expected: 10/10 tests pass

# Test Feedback API
php feedback.test.php
# Expected: 8/8 tests pass
```

### Step 5: Run E2E Tests

```bash
# Navigate to E2E test directory
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/tests/e2e

# Run complete E2E test suite
php calm_break_scenario.test.php

# Expected output:
# - 7 scenarios executed
# - 75+ assertions passed
# - All database records verified
# - SLA compliance validated
# - Total time < 5 seconds
```

### Step 6: Run Complete Verification

```bash
# Navigate to tests directory
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/tests

# Run complete MVP verification
php verify_mvp.php

# Expected output:
# PHASE 1: INFRASTRUCTURE VERIFICATION
# - Database connection: ✅ PASS
# - Database tables: ✅ PASS
# - File structure: ✅ PASS
# - Python environment: ✅ PASS
# - File permissions: ✅ PASS (or WARN if logs not writable)
#
# PHASE 2: COMPONENT VERIFICATION
# - Sensing layer: ✅ PASS
# - Decision layer: ✅ PASS
# - Execution layer: ✅ PASS
#
# PHASE 3: INTEGRATION VERIFICATION
# - Orchestrator: ✅ PASS
# - Feedback API: ✅ PASS
# - Teacher UI: ✅ PASS
# - SLA monitoring: ✅ PASS
#
# PHASE 4: PERFORMANCE VERIFICATION
# - Pipeline benchmark: ✅ PASS
# - SLA compliance: ✅ PASS
# - Layer performance: ✅ PASS
#
# PHASE 5: READINESS VERIFICATION
# - Documentation: ✅ PASS
# - Test coverage: ✅ PASS
# - Logging: ✅ PASS
# - Error handling: ✅ PASS
#
# Overall Status: READY
# Exit Code: 0
```

### Step 7: Test Manual Pipeline Execution

```bash
# Navigate to MVP system root
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system

# Execute test pipeline
php orchestrator.php 123

# Expected output (JSON):
# {
#   "success": true,
#   "pipeline_id": "pipeline-...-123",
#   "student_id": 123,
#   "metrics": {
#     "calm_score": 65.5,
#     ...
#   },
#   "decision": {
#     "action": "micro_break",
#     "confidence": 0.85,
#     ...
#   },
#   "intervention": {
#     "intervention_id": "int-...",
#     "status": "sent"
#   },
#   "performance": {
#     "total_ms": 385.2,
#     "sla_met": true
#   }
# }
```

### Step 8: Test API Endpoints

```bash
# Test Orchestrate API
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID" \
  -d '{"student_id": 124}'

# Expected: 200 OK with pipeline result JSON

# Test SLA Stats API
curl "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php?hours=24" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID"

# Expected: 200 OK with SLA statistics JSON
```

### Step 9: Test Teacher UI

```bash
# Open browser and navigate to Teacher Panel
# URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/ui/teacher_panel.php

# Login as teacher account
# Expected:
# - Statistics dashboard loads
# - Decision cards displayed
# - Filters work
# - Action buttons functional

# Test feedback submission:
# 1. Click "Approve" on a decision
# 2. Check page refreshes
# 3. Verify decision card shows "Approved" badge
```

### Step 10: Test SLA Dashboard

```bash
# Open browser and navigate to SLA Dashboard
# URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/monitoring/sla_dashboard.php

# Expected:
# - SLA compliance metrics displayed
# - Layer performance bars shown
# - Time period selector works
# - Refresh button functional
```

## Production Deployment

### Step 11: Setup Automated Monitoring

```bash
# Add cron job for SLA monitoring
crontab -e

# Add this line (every 5 minutes):
*/5 * * * * cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/monitoring && php sla_monitor.php 1 >> /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/logs/sla_monitor.log 2>&1

# Verify cron job added
crontab -l | grep sla_monitor
```

### Step 12: Configure Log Rotation

```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/mvp-system

# Add this content:
/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}

# Test logrotate
sudo logrotate -d /etc/logrotate.d/mvp-system
```

### Step 13: Set File Permissions

```bash
# Ensure web server can write to logs
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
chmod 755 logs
chmod 644 logs/*.log
chown -R www-data:www-data logs

# Verify permissions
ls -la logs
```

### Step 14: Create Backup

```bash
# Backup current state
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration
tar -czf mvp_system_backup_$(date +%Y%m%d_%H%M%S).tar.gz mvp_system/

# Backup database
mysqldump -u [user] -p [database] \
  mdl_mvp_snapshot_metrics \
  mdl_mvp_decision_log \
  mdl_mvp_intervention_execution \
  mdl_mvp_teacher_feedback \
  mdl_mvp_system_metrics \
  > mvp_system_db_backup_$(date +%Y%m%d_%H%M%S).sql
```

## Post-Deployment Monitoring

### Step 15: Monitor Initial Operations (First 24 Hours)

```bash
# Watch logs in real-time
tail -f /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/logs/*.log

# Check SLA compliance every hour
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/monitoring
php sla_monitor.php 1

# Check database growth
mysql -u [user] -p [database] -e "
SELECT
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = '[database]'
AND table_name LIKE 'mdl_mvp_%'
ORDER BY table_name;
"
```

### Step 16: Run Sample Executions

```bash
# Execute 10 test pipelines
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system

for i in {1..10}; do
    php orchestrator.php $((10000 + i))
    echo "Pipeline $i completed"
    sleep 2
done

# Check SLA compliance
cd monitoring
php sla_monitor.php 1
```

### Step 17: Verify Data Persistence

```bash
# Check recent pipeline executions
mysql -u [user] -p [database] -e "
SELECT COUNT(*) as total_pipelines
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_total_time'
AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR);
"

# Check SLA compliance rate
mysql -u [user] -p [database] -e "
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) as sla_met,
    ROUND(AVG(metric_value) * 100, 2) as compliance_percent
FROM mdl_mvp_system_metrics
WHERE metric_name = 'pipeline_sla_met'
AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR);
"
```

## Rollback Plan (If Issues Arise)

### Emergency Rollback Procedure

```bash
# 1. Stop cron job
crontab -e
# Comment out SLA monitoring line

# 2. Restore database from backup
mysql -u [user] -p [database] < mvp_system_db_backup_YYYYMMDD_HHMMSS.sql

# 3. Restore files from backup
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration
rm -rf mvp_system
tar -xzf mvp_system_backup_YYYYMMDD_HHMMSS.tar.gz

# 4. Verify rollback
cd mvp_system/tests
php verify_mvp.php
```

## Teacher Training Checklist

### Training Session Preparation

- [ ] Schedule 1-2 hour training session
- [ ] Prepare demo student accounts (IDs 10001-10010)
- [ ] Execute sample pipelines for demo data
- [ ] Prepare training presentation
- [ ] Create teacher quick reference guide

### Training Topics

1. **System Overview** (15 min)
   - What is the Agentic System
   - How Calm Score is calculated
   - When interventions are triggered

2. **Teacher Panel Walkthrough** (30 min)
   - Accessing the panel
   - Understanding decision cards
   - Reviewing AI rationale
   - Using filters
   - Approving/rejecting decisions
   - Adding comments

3. **Best Practices** (15 min)
   - When to approve vs reject
   - How to provide helpful feedback
   - Understanding confidence scores
   - Interpreting calm scores

4. **Hands-On Practice** (30 min)
   - Review sample decisions
   - Practice approving/rejecting
   - Test comment functionality
   - Q&A session

## Success Criteria

### Day 1
- [ ] All verification tests pass
- [ ] 10 sample pipelines executed successfully
- [ ] Teacher UI accessible and functional
- [ ] SLA monitoring showing data
- [ ] No critical errors in logs

### Week 1
- [ ] ≥ 50 pipeline executions
- [ ] SLA compliance ≥ 90%
- [ ] ≥ 3 teachers using system
- [ ] ≥ 20 feedback submissions
- [ ] Average teacher response time < 1 hour

### Month 1
- [ ] ≥ 500 pipeline executions
- [ ] SLA compliance ≥ 95%
- [ ] All teachers trained and active
- [ ] ≥ 100 feedback submissions
- [ ] Teacher satisfaction ≥ 4/5

## Support Contacts

**Technical Issues**:
- Check logs: `/home/moodle/.../mvp_system/logs/`
- Review guides: `ORCHESTRATOR_GUIDE.md`, `SLA_MONITORING_GUIDE.md`
- Run verification: `php tests/verify_mvp.php`

**Database Issues**:
- Check connection: `config/app.config.php`
- Verify tables: `SHOW TABLES LIKE 'mdl_mvp_%'`
- Review migration: `database/migrate.php`

**Performance Issues**:
- Check SLA dashboard
- Run monitor: `php monitoring/sla_monitor.php 1`
- Review performance metrics in database

## Deployment Sign-Off

**Deployment Date**: _________________

**Deployed By**: _________________

**Verified By**: _________________

**Verification Results**:
- [ ] All tests passed
- [ ] APIs functional
- [ ] UI accessible
- [ ] Monitoring operational
- [ ] Documentation reviewed

**Sign-Off**: _________________

---

**Document Version**: 1.0
**Last Updated**: 2025-11-02
**Status**: Ready for Deployment
