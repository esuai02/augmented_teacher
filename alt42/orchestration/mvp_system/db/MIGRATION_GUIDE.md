# 📋 Database Migration Guide: V1 → V2

## 개요

이 가이드는 `mdl_mvp_decision_log` 테이블을 V1 스키마에서 V2 스키마로 안전하게 마이그레이션하는 절차를 설명합니다.

## 🎯 마이그레이션 목표

### 추가되는 컬럼들:
1. `agent_name` - VARCHAR(100) NULL
2. `context_data` - TEXT NULL
3. `result_data` - TEXT NULL
4. `is_cascade` - TINYINT(1) NOT NULL DEFAULT 0
5. `cascade_depth` - INT NOT NULL DEFAULT 0
6. `parent_decision_id` - BIGINT NULL
7. `execution_time_ms` - DECIMAL(10,2) NULL
8. `notes` - TEXT NULL

### 수정되는 컬럼:
- `confidence`: DECIMAL(3,2) → DECIMAL(5,4)

### 추가되는 인덱스:
- `idx_is_cascade` on `is_cascade`
- `idx_parent_decision` on `parent_decision_id`

## ⚠️ 사전 준비사항

### 1. 데이터베이스 백업 (필수)
```bash
# SSH로 서버 접속 후 실행
mysqldump -u [username] -p mathking mdl_mvp_decision_log > backup_mvp_decision_log_$(date +%Y%m%d_%H%M%S).sql

# 백업 확인
ls -lh backup_mvp_decision_log_*.sql
```

### 2. 현재 스키마 확인
```sql
DESCRIBE mdl_mvp_decision_log;
SELECT COUNT(*) as row_count FROM mdl_mvp_decision_log;
```

### 3. 서버 환경 확인
- PHP 버전: 7.1.9 이상
- MySQL 버전: 5.7 이상
- Moodle 버전: 3.7 이상
- 충분한 디스크 공간 (백업 파일 저장용)

## 🚀 마이그레이션 실행 절차

### Step 1: DRY RUN 테스트 (권장)

1. 마이그레이션 스크립트를 서버로 업로드:
```bash
# 로컬에서 서버로 파일 복사
scp db/migrate_v1_to_v2.php [user]@mathking.kr:/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/db/
```

2. DRY RUN 모드 활성화:
```php
// migrate_v1_to_v2.php 파일에서 다음 라인을 수정
$dry_run = true; // Set to true for testing without actual changes
```

3. DRY RUN 실행:
```bash
# SSH로 서버 접속 후 실행
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
php db/migrate_v1_to_v2.php
```

4. 출력 확인:
- ✅ 모든 검증 단계가 통과하는지 확인
- ⚠️ WARNING 메시지가 있다면 해결 필요
- 📋 마이그레이션 단계 목록 확인

### Step 2: 실제 마이그레이션 실행

1. DRY RUN 모드 비활성화:
```php
// migrate_v1_to_v2.php 파일에서 다음 라인을 수정
$dry_run = false; // Now ready for actual migration
```

2. 마이그레이션 실행:
```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
php db/migrate_v1_to_v2.php
```

3. 프롬프트에서 ENTER 키를 눌러 계속 진행

4. 완료 메시지 확인:
```
✅ MIGRATION COMPLETED SUCCESSFULLY
```

### Step 3: 마이그레이션 검증

1. 테이블 구조 확인:
```sql
DESCRIBE mdl_mvp_decision_log;
```

예상 결과:
```
+---------------------+--------------+------+-----+-------------------+
| Field               | Type         | Null | Key | Default           |
+---------------------+--------------+------+-----+-------------------+
| id                  | bigint(10)   | NO   | PRI | NULL              |
| student_id          | bigint(10)   | NO   | MUL | NULL              |
| agent_id            | varchar(50)  | YES  | MUL | NULL              |
| agent_name          | varchar(100) | YES  |     | NULL              | ← NEW
| rule_id             | varchar(100) | YES  | MUL | NULL              |
| action              | varchar(50)  | NO   | MUL | NULL              |
| confidence          | decimal(5,4) | NO   |     | NULL              | ← MODIFIED
| rationale           | text         | NO   |     | NULL              |
| context_data        | text         | YES  |     | NULL              | ← NEW
| result_data         | text         | YES  |     | NULL              | ← NEW
| is_cascade          | tinyint(1)   | NO   | MUL | 0                 | ← NEW
| cascade_depth       | int(11)      | NO   |     | 0                 | ← NEW
| parent_decision_id  | bigint(20)   | YES  | MUL | NULL              | ← NEW
| execution_time_ms   | decimal(10,2)| YES  |     | NULL              | ← NEW
| timestamp           | datetime     | NO   | MUL | NULL              |
| created_at          | datetime     | YES  |     | CURRENT_TIMESTAMP |
| notes               | text         | YES  |     | NULL              | ← NEW
+---------------------+--------------+------+-----+-------------------+
```

2. 데이터 무결성 확인:
```sql
-- 레코드 수 확인 (마이그레이션 전후 동일해야 함)
SELECT COUNT(*) FROM mdl_mvp_decision_log;

-- 기존 데이터 샘플 확인
SELECT id, student_id, agent_id, action, confidence, created_at
FROM mdl_mvp_decision_log
LIMIT 5;

-- 새 컬럼이 NULL로 채워졌는지 확인
SELECT COUNT(*) as null_count FROM mdl_mvp_decision_log WHERE agent_name IS NULL;
```

3. Backward Compatibility 테스트 실행:
```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
php tests/test_backward_compatibility.php
```

예상 결과:
```
✅ Backward Compatibility: PASS
4/4 test case(s) passed
```

### Step 4: 성능 확인

1. 인덱스 사용 확인:
```sql
SHOW INDEX FROM mdl_mvp_decision_log;
```

2. 쿼리 성능 테스트:
```sql
-- Cascade 쿼리 성능 (새 인덱스 활용)
EXPLAIN SELECT * FROM mdl_mvp_decision_log
WHERE is_cascade = 1 AND parent_decision_id IS NOT NULL;
```

## 🔄 롤백 절차 (문제 발생 시)

### 옵션 1: 백업에서 복원
```bash
# 백업 파일에서 전체 테이블 복원
mysql -u [username] -p mathking < backup_mvp_decision_log_YYYYMMDD_HHMMSS.sql
```

### 옵션 2: 수동 컬럼 제거 (부분 롤백)
```sql
-- V2 컬럼 제거
ALTER TABLE mdl_mvp_decision_log
DROP COLUMN agent_name,
DROP COLUMN context_data,
DROP COLUMN result_data,
DROP COLUMN is_cascade,
DROP COLUMN cascade_depth,
DROP COLUMN parent_decision_id,
DROP COLUMN execution_time_ms,
DROP COLUMN notes;

-- confidence 컬럼 원복
ALTER TABLE mdl_mvp_decision_log
MODIFY COLUMN confidence DECIMAL(3,2) NOT NULL;

-- 인덱스 제거
DROP INDEX idx_is_cascade ON mdl_mvp_decision_log;
DROP INDEX idx_parent_decision ON mdl_mvp_decision_log;
```

## 📊 마이그레이션 체크리스트

### 마이그레이션 전:
- [ ] 데이터베이스 백업 완료
- [ ] DRY RUN 테스트 성공
- [ ] 현재 레코드 수 기록
- [ ] 서비스 점검 시간 공지 (선택사항)

### 마이그레이션 중:
- [ ] 실제 마이그레이션 실행
- [ ] 모든 단계 성공 확인
- [ ] 에러 메시지 없음

### 마이그레이션 후:
- [ ] 테이블 구조 확인
- [ ] 레코드 수 일치 확인
- [ ] Backward compatibility 테스트 통과
- [ ] 애플리케이션 정상 동작 확인
- [ ] 마이그레이션 로그 저장

## 🛠️ 트러블슈팅

### 문제 1: "Table is locked"
**원인**: 다른 프로세스가 테이블을 사용 중
**해결**:
```sql
SHOW PROCESSLIST;
-- 필요시 KILL [process_id];
```

### 문제 2: "Duplicate column name"
**원인**: 컬럼이 이미 존재함
**해결**: 마이그레이션 스크립트는 자동으로 스킵함 (ℹ️ 메시지 확인)

### 문제 3: "Out of disk space"
**원인**: ALTER TABLE을 위한 임시 공간 부족
**해결**:
```bash
df -h  # 디스크 공간 확인
# 불필요한 파일 정리 후 재시도
```

### 문제 4: Backward compatibility 테스트 실패
**원인**: 스키마 불일치 또는 코드 오류
**해결**:
1. 테이블 구조 재확인: `DESCRIBE mdl_mvp_decision_log`
2. 누락된 컬럼 확인
3. confidence 타입 확인: DECIMAL(5,4)
4. 로그 파일 확인: `/tmp/mvp_orchestrator_v2.log`

## 📞 지원

문제가 발생하면 다음 정보를 포함하여 보고:
1. 에러 메시지 전문
2. 마이그레이션 로그 파일
3. `DESCRIBE mdl_mvp_decision_log` 결과
4. 현재 레코드 수

## 📝 마이그레이션 이력

| 날짜 | 버전 | 수행자 | 결과 | 비고 |
|------|------|--------|------|------|
| YYYY-MM-DD | V1→V2 | | | |

## 📚 참고 자료

- [Moodle XMLDB Documentation](https://docs.moodle.org/dev/XMLDB)
- [MySQL ALTER TABLE](https://dev.mysql.com/doc/refman/5.7/en/alter-table.html)
- Project: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/`
