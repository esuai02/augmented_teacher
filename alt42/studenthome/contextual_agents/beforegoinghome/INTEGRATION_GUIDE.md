# 귀가검사 리포트 저장 수정 통합 가이드

**날짜**: 2025-11-13
**목적**: utf8mb4 마이그레이션 후 index.php 코드 업데이트
**우선순위**: 높음 (프로덕션 오류 수정)

---

## 📋 사전 준비

### 1. 마이그레이션 실행 (필수)

먼저 UTF-8mb4 마이그레이션을 완료해야 합니다:

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/migrate_to_utf8mb4.php
```

**확인사항**:
- ✅ `"overall_status": "MIGRATION_SUCCESS"`
- ✅ `"emoji_preserved": true`
- ✅ `"all_columns_utf8mb4": true`

### 2. 백업 생성 (필수)

```bash
# index.php 백업
cp index.php index_backup_20251113.php
```

---

## 🔧 적용 방법

### Option A: 수동 편집 (권장)

1. **index.php 열기**

2. **542-640줄 찾기** (if ($tableExists) { 부터 시작)

3. **기존 코드 삭제**:
   ```php
   // 삭제할 부분: 542줄부터 645줄까지
   if ($tableExists) {
       $record = new stdClass();
       ...
       // 이모지 처리 로직 (553-567줄)
       $reportHtmlSafe = preg_replace_callback(...);
       ...
       // INSERT 로직 (594-640줄)
       $insertId = $DB->insert_record($tableName, $record, true);
       ...
   } else {
       $errorMessage = '리포트 테이블(alt42_goinghome_reports)이 존재하지 않습니다.';
       $debugInfo['table_exists'] = false;
   }
   ```

4. **새 코드 삽입**:
   - `index_save_report_updated.php` 파일의 내용을 복사
   - 542줄 위치부터 붙여넣기
   - 646줄 이후 코드(기존 테이블 저장 로직)는 그대로 유지

5. **저장 후 테스트**

### Option B: 파일 비교 도구 사용

```bash
# 변경 사항 확인
diff -u index.php index_save_report_updated.php

# 또는 GUI 도구 사용 (예: WinMerge, Beyond Compare)
```

---

## 🔍 주요 변경사항

### 변경 1: 이모지 처리 로직 제거 ❌

**Before (553-567줄)**:
```php
$reportHtmlSafe = preg_replace_callback(
    '/[\x{1F300}-\x{1F9FF}...]/u',
    function($matches) {
        $char = $matches[0];
        $utf32 = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
        if (strlen($utf32) >= 4) {
            $codePoint = unpack('N', $utf32)[1];
            return '&#x' . strtoupper(dechex($codePoint)) . ';';
        }
        return $char;
    },
    $reportHtmlSafe
);
```

**After**:
```php
// 이모지 처리 로직 제거 - utf8mb4로 그대로 저장
$htmlData = $reportHtml;
```

**이유**: utf8mb4 인코딩이 이모지를 네이티브로 지원하므로 복잡한 변환 불필요

---

### 변경 2: INSERT → UPDATE 패턴 적용 ✅

**Before (단일 INSERT)**:
```php
$record = new stdClass();
$record->userid = $studentId;
$record->report_id = $reportId;
$record->report_html = $reportHtmlSafe; // 한 번에 모든 데이터
$record->report_data = json_encode($reportData, JSON_UNESCAPED_UNICODE);
$record->report_date = date('Y년 n월 j일');
$record->timecreated = time();
$record->timemodified = time();

$insertId = $DB->insert_record($tableName, $record, true);
```

**After (Progressive Updates)**:
```php
// Step 1: 기본 레코드 INSERT
$record = new stdClass();
$record->userid = $studentId;
$record->report_id = $reportId;
$record->report_html = ''; // 빈 값
$record->report_data = ''; // 빈 값
$record->report_date = date('Y년 n월 j일');
$record->timecreated = time();
$record->timemodified = time();
$insertId = $DB->insert_record($tableName, $record, true);

// Step 2: JSON 데이터 UPDATE
$updateJson = new stdClass();
$updateJson->id = $insertId;
$updateJson->report_data = $jsonData;
$updateJson->timemodified = time();
$DB->update_record($tableName, $updateJson);

// Step 3: HTML 데이터 UPDATE
$updateHtml = new stdClass();
$updateHtml->id = $insertId;
$updateHtml->report_html = $htmlData;
$updateHtml->timemodified = time();
$DB->update_record($tableName, $updateHtml);
```

**장점**:
- 각 단계별 실패 원인 명확히 파악 가능
- 부분 실패 시에도 기본 레코드 유지
- 디버깅 용이

---

### 변경 3: 상세 에러 로깅 추가 📊

**Before**:
```php
catch (dml_exception $e) {
    $errorMessage = '리포트 저장 중 DB 오류: ' . $e->getMessage();
    $debugInfo['dml_exception'] = $e->getMessage();
}
```

**After**:
```php
catch (dml_exception $e) {
    $errorMessage = '기본 레코드 INSERT 중 DB 오류: ' . $e->getMessage();
    $debugInfo['insert_dml_exception'] = $e->getMessage();
    $debugInfo['insert_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
    error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
    error_log("Details: " . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
}
```

**개선사항**:
- 파일 위치 (`__FILE__:__LINE__`) 명시
- 단계별 에러 구분 (insert/json_update/html_update)
- 상세 디버그 정보 로깅

---

### 변경 4: 크기 검증 강화 📏

**Before**:
```php
// HTML만 크기 제한
$maxHtmlSize = 4 * 1024 * 1024;
if ($reportHtmlSafeSize > $maxHtmlSize) {
    $record->report_html = substr($reportHtmlSafe, 0, $maxHtmlSize);
}
```

**After**:
```php
// JSON 크기 검증 (16MB 제한)
$maxJsonSize = 16 * 1024 * 1024;
if ($jsonSize > $maxJsonSize) {
    $errorMessage = "JSON 데이터가 너무 큽니다: {$jsonSize} bytes";
    error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
    echo json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML 크기 제한 (4MB)
$maxHtmlSize = 4 * 1024 * 1024;
if ($htmlSize > $maxHtmlSize) {
    $htmlData = substr($htmlData, 0, $maxHtmlSize);
    $debugInfo['html_truncated'] = true;
    error_log("리포트 HTML 잘림 at " . __FILE__ . ":" . __LINE__ . " - 원본: {$htmlSize} bytes");
}
```

**개선사항**:
- JSON 크기 검증 추가
- 크기 초과 시 명확한 에러 메시지
- 로깅으로 추적 가능

---

## ✅ 검증 단계

### 1. 문법 체크
```bash
php -l index.php
```

**Expected**: `No syntax errors detected`

### 2. 테스트 리포트 저장

브라우저에서:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/index.php?userid=1951
```

1. 귀가검사 진행
2. "리포트 생성 및 저장" 클릭
3. 결과 확인

### 3. 성공 응답 확인

```json
{
  "success": true,
  "message": "리포트가 성공적으로 저장되었습니다!",
  "report_id": "REPORT_1731484800_abc123def",
  "debug": {
    "insert_id": 123,
    "insert_success": true,
    "json_update_success": true,
    "html_update_success": true,
    "save_strategy": "progressive_update",
    "emoji_processing": "utf8mb4_native"
  }
}
```

### 4. 이모지 저장 확인

리포트에 이모지(😊 😄 😃) 포함 후 저장 → DB에서 직접 확인:

```sql
SELECT id, report_id, report_html
FROM mdl_alt42_goinghome_reports
WHERE userid = 1951
ORDER BY id DESC
LIMIT 1;
```

**Expected**: `report_html`에 이모지가 그대로 저장되어 있어야 함

---

## 🚨 문제 해결

### 문제 1: "Call to undefined function error_log()"

**원인**: PHP 설정 문제
**해결**: `error_log()` 호출 제거 또는 주석 처리

```php
// error_log(...);  // 주석 처리
```

### 문제 2: "Data too long for column 'report_data'"

**원인**: JSON 데이터가 16MB를 초과
**확인**:
```php
$debugInfo['json_size_mb']  // JSON 크기 확인
```

**해결**: 응답 데이터 크기 줄이기 또는 제한 증가

### 문제 3: UTF-8mb4 마이그레이션 실패

**증상**: 여전히 이모지 저장 실패
**확인**:
```sql
SHOW CREATE TABLE mdl_alt42_goinghome_reports;
```

**Expected**: `CHARACTER SET utf8mb4`

**해결**: `migrate_to_utf8mb4.php` 재실행

---

## 📊 모니터링

### 에러 로그 확인

```bash
# Moodle 에러 로그
tail -f /home/moodle/public_html/moodle/error_log

# 또는 PHP 에러 로그
tail -f /var/log/php_errors.log
```

### 성공률 추적

24시간 동안 모니터링:
- ✅ 저장 성공 횟수
- ❌ 저장 실패 횟수
- ⚠️ 부분 성공 횟수

**Target**: 성공률 ≥ 95%

---

## 🔄 롤백 절차

문제 발생 시:

1. **백업 복원**:
   ```bash
   cp index_backup_20251113.php index.php
   ```

2. **서비스 재시작** (필요 시):
   ```bash
   sudo systemctl restart php-fpm
   # 또는
   sudo systemctl restart apache2
   ```

3. **문제 보고**:
   - 에러 로그 수집
   - `$debugInfo` 내용 기록
   - 재현 단계 문서화

---

## 📝 체크리스트

적용 전:
- [ ] UTF-8mb4 마이그레이션 완료 확인
- [ ] index.php 백업 생성
- [ ] 변경 사항 검토

적용 후:
- [ ] 문법 에러 없음 확인
- [ ] 테스트 리포트 저장 성공
- [ ] 이모지 저장 확인
- [ ] 에러 로그 모니터링 시작
- [ ] 24시간 성공률 추적 설정

---

## 📞 지원

문제 발생 시:
1. 에러 로그 확인
2. `$debugInfo` 내용 검토
3. 백업으로 롤백
4. 상세 오류 내용 보고

---

**Last Updated**: 2025-11-13
**Version**: 1.0
