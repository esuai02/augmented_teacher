# Heartbeat Scheduler 실행 가이드

**작성일**: 2025-01-27  
**대상 환경**: 서버 (Linux)

---

## 📋 사전 준비사항

1. **서버 접속**
   ```bash
   ssh user@your-server
   ```

2. **작업 디렉토리로 이동**
   ```bash
   cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration
   ```

3. **파일 권한 확인**
   ```bash
   ls -la db/migrations/*.php
   ls -la api/scheduler/heartbeat.php
   ```

---

## 🚀 실행 방법

### 방법 1: 자동 실행 스크립트 사용 (권장)

```bash
cd db/migrations
chmod +x run_all_heartbeat_migrations.sh
bash run_all_heartbeat_migrations.sh
```

### 방법 2: 수동 실행

#### 1단계: 마이그레이션 005 실행
```bash
cd db/migrations
php run_005_migration.php
```

**예상 출력:**
```
=== Migration 005: Heartbeat and State Change Tables ===
Starting at 2025-01-27 10:00:00

✓ Migration SQL loaded (xxxx bytes)

Found 5 SQL statements

[0] Creating table: mdl_alt42_heartbeat_log... ✓ SUCCESS
[1] Creating table: mdl_alt42_state_change_log... ✓ SUCCESS
[2] Creating table: mdl_alt42_event_processing_log... ✓ SUCCESS
[3] Creating table: mdl_alt42_student_state_cache... ✓ SUCCESS
[4] Creating table: mdl_alt42_scenario_evaluation_log... ✓ SUCCESS

=== Migration Summary ===
Success: 5
Errors: 0
Completed at 2025-01-27 10:00:05
```

#### 2단계: 마이그레이션 006 실행
```bash
php run_006_migration.php
```

**예상 출력:**
```
=== Migration 006: Heartbeat Views and Tables ===
Starting at 2025-01-27 10:00:10

✓ Migration SQL loaded (xxxx bytes)

Found 3 SQL statements

[0] Creating VIEW mdl_alt42_v_student_state... ✓ SUCCESS
[1] Creating TABLE mdl_alt42_student_activity... ✓ SUCCESS
[2] Executing ALTER TABLE mdl_alt42_learning_sessions... ✓ SUCCESS

=== Migration Summary ===
Success: 3
Skipped: 0
Errors: 0
Completed at 2025-01-27 10:00:15
```

#### 3단계: 테스트 실행
```bash
cd ../../api/scheduler
php test_heartbeat.php
```

**예상 출력:**
```
=== Heartbeat Scheduler Test ===
Started at 2025-01-27 10:00:20

1. Checking database tables...
   mdl_alt42_heartbeat_log: ✓ EXISTS
   mdl_alt42_scenario_evaluation_log: ✓ EXISTS
   mdl_alt42_student_activity: ✓ EXISTS

2. Checking views...
   mdl_alt42_v_student_state: ✓ EXISTS

3. Checking dependency files...
   event_bus.php: ✓ EXISTS
   agent_data_layer.php: ✓ EXISTS
   event_scenario_mapper.php: ✓ EXISTS
   route.php: ✓ EXISTS
   event_schemas.php: ✓ EXISTS
   rule_evaluator.php: ✓ EXISTS

4. Testing Heartbeat execution...
   ✓ HeartbeatScheduler instance created
   ✓ execute() method exists

5. Running Heartbeat (dry run)...
   Note: This will process active students if any exist.

   Result:
   - Success: YES
   - Students processed: 0
   - Errors: 0
   - Duration: 15.23 ms

=== Test Summary ===
Tables: ✓ ALL OK
View: ✓ OK
Dependencies: ✓ ALL OK
Heartbeat execution: ✓ SUCCESS

Completed at 2025-01-27 10:00:25
```

---

## ✅ 실행 확인

### 1. 데이터베이스 테이블 확인
```bash
mysql -u moodle_user -p moodle_db -e "SHOW TABLES LIKE 'mdl_alt42_heartbeat%';"
```

**예상 결과:**
```
+------------------------------------------+
| Tables_in_moodle_db (mdl_alt42_heartbeat%) |
+------------------------------------------+
| mdl_alt42_heartbeat_log                  |
+------------------------------------------+
```

### 2. 뷰 확인
```bash
mysql -u moodle_user -p moodle_db -e "SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_moodle_db LIKE 'mdl_alt42_v_student_state';"
```

### 3. 수동 Heartbeat 실행 테스트
```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler
php heartbeat.php
```

**예상 출력 (JSON):**
```json
{
    "success": true,
    "timestamp": "2025-01-27T10:00:00+00:00",
    "students_processed": 0,
    "errors": 0,
    "duration_ms": 15.23,
    "results": {}
}
```

---

## 🔧 문제 해결

### 문제 1: "Table already exists" 에러
**원인**: 테이블이 이미 존재함

**해결**: 정상 동작입니다. `IF NOT EXISTS` 구문으로 인해 스킵됩니다.

### 문제 2: "View already exists" 에러
**원인**: 뷰가 이미 존재함

**해결**: `CREATE OR REPLACE VIEW` 구문으로 인해 자동으로 교체됩니다.

### 문제 3: "Class not found" 에러
**원인**: 네임스페이스 또는 require 경로 문제

**해결**: 
1. 파일이 올바른 위치에 있는지 확인
2. `agent_data_layer.php`에 `namespace ALT42\Database;`가 있는지 확인

### 문제 4: "Database connection failed" 에러
**원인**: Moodle config 파일 경로 문제

**해결**:
1. `/home/moodle/public_html/moodle/config.php` 파일 존재 확인
2. 또는 standalone 모드로 동작하도록 설정 확인

---

## 📅 Cron 등록 (프로덕션)

### Cron 설정 파일 생성
```bash
sudo nano /etc/cron.d/alt42_heartbeat
```

### 파일 내용
```
# ALT42 Heartbeat Scheduler - 30분마다 실행
*/30 * * * * www-data php /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php >> /var/log/alt42/heartbeat.log 2>&1
```

### Cron 활성화 확인
```bash
sudo crontab -l | grep heartbeat
```

### 로그 확인
```bash
tail -f /var/log/alt42/heartbeat.log
```

---

## 📊 모니터링

### Heartbeat 실행 로그 확인
```bash
mysql -u moodle_user -p moodle_db -e "SELECT * FROM mdl_alt42_heartbeat_log ORDER BY created_at DESC LIMIT 10;"
```

### 시나리오 평가 로그 확인
```bash
mysql -u moodle_user -p moodle_db -e "SELECT * FROM mdl_alt42_scenario_evaluation_log ORDER BY evaluated_at DESC LIMIT 10;"
```

---

## ⚠️ 주의사항

1. **백업**: 마이그레이션 실행 전 데이터베이스 백업 권장
2. **권한**: PHP 실행 사용자(www-data)가 데이터베이스 접근 권한이 있는지 확인
3. **로그**: 로그 파일 디렉토리(`/var/log/alt42/`) 생성 및 권한 설정 필요

---

**작성자**: AI Assistant  
**최종 업데이트**: 2025-01-27

