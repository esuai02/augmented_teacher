# DB 마이그레이션 문제 해결 가이드

## API 오류: Unexpected end of JSON input

이 오류는 다음과 같은 원인으로 발생합니다:

### 1. 데이터베이스에 데이터가 없는 경우
SQL 파일을 아직 실행하지 않았다면:

```bash
# 1. 기본 카드 데이터 삽입
mysql -u root -p ktm_database < insert_default_cards_data.sql

# 2. 누락된 daily 카드 데이터 삽입
mysql -u root -p ktm_database < insert_missing_cards_data.sql
```

### 2. Moodle 설정 파일 경로 문제
`plugin_db_config.php`의 Moodle 경로 확인:
```php
// 실제 Moodle 설치 경로로 변경
require_once("/home/moodle/public_html/moodle/config.php");
```

### 3. 테이블이 생성되지 않은 경우
```sql
-- 테이블 생성 SQL 실행
mysql -u root -p ktm_database < plugin_settings_tables.sql
```

## 테스트 방법

### 1. DB 연결 테스트
브라우저에서 열기:
```
http://mathking.kr/moodle/local/augmented_teacher/alt42/teacherhome/test_db_connection.php
```

### 2. 간단한 API 테스트
브라우저에서 직접 열기:
```
http://mathking.kr/moodle/local/augmented_teacher/alt42/teacherhome/plugin_settings_api_simple.php?action=load&user_id=1&category=daily
```

### 3. 수동 데이터 확인
MySQL 콘솔에서:
```sql
-- 테이블 확인
SHOW TABLES LIKE 'mdl_alt42DB_%';

-- 데이터 확인
SELECT * FROM mdl_alt42DB_card_plugin_settings 
WHERE user_id = 1 AND category = 'daily';
```

## 임시 해결 방법

데이터가 없을 때 테스트하려면 임시 데이터 삽입:

```sql
-- 테스트용 데이터 1개 삽입
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
(1, 'daily', '테스트 카드', 0, 'external_link', 
'{"url": "#", "target": "_self", "description": "테스트 설명", "details": ["테스트1", "테스트2"]}', 
0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

## 권한 문제 해결

DB 사용자 권한 확인:
```sql
-- 권한 부여 (root로 실행)
GRANT ALL PRIVILEGES ON ktm_database.* TO 'moodle_user'@'localhost';
FLUSH PRIVILEGES;
```

## 추가 디버깅

`plugin_settings_api_simple.php`에 디버그 정보 추가:
```php
// 파일 상단에 추가
error_log("API called with: " . json_encode($_GET));
error_log("User ID: " . $user_id . ", Category: " . $category);
```

서버 로그 확인:
```bash
tail -f /var/log/apache2/error.log
```