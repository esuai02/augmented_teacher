# 데이터베이스 마이그레이션 가이드

## 개요
`mdl_alt42DB_card_plugin_settings` 테이블의 `plugin_config` JSON 필드를 개별 컬럼으로 분리하는 작업입니다.

## 실행 순서

### 1. 새 테이블 생성
```bash
# 옵션 1: PHP 스크립트로 실행
php execute_sql_file.php create_new_card_plugin_settings_table.sql

# 옵션 2: MySQL 직접 실행
mysql -u [username] -p [database] < create_new_card_plugin_settings_table.sql
```

### 2. 테이블 생성 확인
```bash
php check_new_table.php
```

### 3. 데이터 마이그레이션
```bash
php migrate_plugin_config_data.php
```

### 4. 마이그레이션 검증
```bash
php validate_migration.php
```

### 5. 테이블 전환 (검증 성공 후)
```bash
# 옵션 1: PHP 스크립트로 실행
php execute_sql_file.php switch_to_new_table.sql

# 옵션 2: MySQL 직접 실행
mysql -u [username] -p [database] < switch_to_new_table.sql
```

## 파일 설명

### SQL 파일
- `create_new_card_plugin_settings_table.sql`: 새 테이블 생성 스크립트
- `switch_to_new_table.sql`: 테이블 이름 교체 스크립트

### PHP 스크립트
- `execute_sql_file.php`: SQL 파일 실행 도구
- `check_new_table.php`: 테이블 존재 및 구조 확인
- `migrate_plugin_config_data.php`: 데이터 마이그레이션
- `validate_migration.php`: 마이그레이션 검증

### API 파일 (새 구조용)
- `plugin_settings_api_new.php`: 새로운 테이블 구조를 위한 API
- `plugin_settings_client.js`: JavaScript 클라이언트 (수정됨)

### 테스트 파일
- `test_new_table_structure.html`: 웹 기반 테스트 인터페이스

## 주의사항

1. **백업 필수**: 작업 전 반드시 데이터베이스 백업
2. **순서 준수**: 위 순서대로 실행
3. **검증 확인**: 검증 단계에서 문제가 없어야 테이블 전환 진행
4. **롤백 가능**: 문제 발생 시 `mdl_alt42DB_card_plugin_settings_old`에서 복구 가능

## 롤백 방법
문제 발생 시:
```sql
-- 원래 테이블로 복구
RENAME TABLE mdl_alt42DB_card_plugin_settings TO mdl_alt42DB_card_plugin_settings_failed;
RENAME TABLE mdl_alt42DB_card_plugin_settings_old TO mdl_alt42DB_card_plugin_settings;
```

## 새 테이블 구조
기존 `plugin_config` JSON 필드가 다음과 같이 분리됨:
- `plugin_name`: 플러그인 이름
- `card_description`: 카드 설명
- `internal_url`, `external_url`: URL 필드
- `message_content`, `message_type`: 메시지 필드
- `agent_type`, `agent_code`, etc.: 에이전트 필드
- `agent_config_*`: 에이전트 설정 필드