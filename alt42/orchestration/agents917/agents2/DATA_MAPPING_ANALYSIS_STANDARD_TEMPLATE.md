# 데이터 매핑 분석 도구 표준 템플릿

## 📋 개요

이 문서는 모든 에이전트의 `data_mapping_analysis.php` 파일을 표준화하기 위한 템플릿입니다.

**기준 에이전트**: `agent01_onboarding`
**개선 사항**: 다른 에이전트의 장점 통합

## ✅ 표준화된 기능

### 1. 기본 구조
- Moodle 설정 및 인증
- 파라미터 및 권한 체크
- rules.yaml 필드 추출
- data_access.php 필드 추출
- view_reports.php 필드 추출
- DB 테이블 구조 확인

### 2. 개선된 기능 (통합됨)

#### ✅ DB 실제 데이터 존재 여부 확인
- rules.yaml 필드 기준으로 실제 DB에 데이터가 있는지 확인
- 샘플 데이터 표시
- 에이전트별 테이블에 맞게 수정 필요

#### ✅ data_access.php 실제 사용 여부 확인
- 단순 필드명 비교가 아닌 실제 코드에서 사용 여부 확인
- `checkDataAccessUsage()` 함수 사용

#### ✅ 상세한 데이터 타입 분류
- survdata: 설문/사용자 입력 데이터
- sysdata: 시스템/DB 자동 생성 데이터
- gendata: 계산/추론된 데이터
- unknown: 알 수 없음

## 📝 표준 템플릿 구조

```php
<?php
/**
 * 데이터 매핑 분석 도구 - [Agent Name]
 * view_reports.php에서 사용하는 데이터와 rules.yaml, data_access.php를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/[agent_id]/rules/
 */

// 1. Moodle 설정 및 인증
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 2. 파라미터 및 권한 체크
$studentid = optional_param('studentid', 1603, PARAM_INT);
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());
if (!$isTeacher) {
    $studentid = $USER->id;
}

// 3. rules.yaml 필드 추출
// 4. data_access.php 필드 추출
// 5. view_reports.php 필드 추출
// 6. DB 테이블 구조 확인

// 7. 데이터 타입 분류 함수
function classifyDataType($fieldName, $tableName = '') {
    // 에이전트별 필드 분류
}

// 8. data_access.php 실제 사용 여부 확인 함수
function checkDataAccessUsage($fieldName, $dataAccessContent) {
    // 표준 함수 (모든 에이전트 동일)
}

// 9. DB 실제 데이터 존재 여부 확인
// 에이전트별 테이블에 맞게 수정 필요

// 10. 분석 결과 생성
// 11. HTML 출력
```

## 🔧 에이전트별 커스터마이징 가이드

### 1. DB 테이블 확인 부분 수정

각 에이전트는 사용하는 테이블이 다르므로, 다음 부분을 수정해야 합니다:

```php
// 실제 DB 데이터 존재 여부 확인 (rules.yaml 필드 기준)
$dbDataExists = [];
$dbDataSample = [];

foreach ($rulesFields as $field) {
    $exists = false;
    $tableName = '';
    $sampleValue = null;
    
    // [에이전트별 테이블 1] 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('your_table_name'))) {
        try {
            // 에이전트별 쿼리 작성
            $sampleData = $DB->get_record_sql(
                "SELECT * FROM {your_table_name} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$studentid],
                IGNORE_MISSING
            );
            // 데이터 확인 로직
        } catch (Exception $e) {
            error_log("Error checking your_table_name: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // [에이전트별 테이블 2] 확인 (필요시)
    // ...
    
    if ($exists) {
        $dbDataExists[] = [
            'field' => $field,
            'table' => $tableName,
            'type' => classifyDataType($field, $tableName),
            'sample' => $sampleValue
        ];
    }
}
```

### 2. classifyDataType 함수 수정

각 에이전트는 사용하는 필드가 다르므로, 필드 분류를 수정해야 합니다:

```php
function classifyDataType($fieldName, $tableName = '') {
    // 에이전트별 survFields 정의
    $survFields = [...];
    
    // 에이전트별 sysFields 정의
    $sysFields = [...];
    
    // 에이전트별 genFields 정의
    $genFields = [...];
    
    // 분류 로직 (표준)
    if (in_array($fieldName, $survFields) || strpos($tableName, 'survey') !== false) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields) || strpos($tableName, 'system') !== false) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields)) {
        return 'gendata';
    } else {
        return 'unknown';
    }
}
```

### 3. data_access.php 필드 추출 패턴 수정

일부 에이전트는 특별한 패턴을 사용할 수 있습니다:

```php
// 기본 패턴
preg_match_all('/\$context\[\'([^\']+)\'\]/', $dataAccessContent, $matches);

// 추가 패턴 (에이전트별)
// 예: factors 배열 패턴 (agent08)
preg_match_all('/factors\[\'([^\']+)\'\]/', $dataAccessContent, $matches);

// 예: 객체 속성 패턴
preg_match_all('/\$object->([a-zA-Z_]+)/', $dataAccessContent, $matches);
```

## 📊 표준화 체크리스트

각 에이전트의 `data_mapping_analysis.php` 파일이 다음을 포함하는지 확인:

- [x] Moodle 설정 및 인증
- [x] 파라미터 및 권한 체크
- [x] rules.yaml 필드 추출
- [x] data_access.php 필드 추출
- [x] view_reports.php 필드 추출
- [x] DB 테이블 구조 확인
- [x] `classifyDataType()` 함수
- [x] `checkDataAccessUsage()` 함수
- [x] DB 실제 데이터 존재 여부 확인
- [x] 분석 결과 생성 (5가지 비교)
- [x] HTML 출력 (표준 스타일)

## 🎯 적용 예시

### agent01_onboarding ✅
- 표준 템플릿 적용 완료
- DB 실제 데이터 확인 기능 추가
- data_access.php 실제 사용 여부 확인 기능 추가

### agent08_calmness (적용 예정)
- alt42_calmness 테이블 확인 로직 추가 필요
- factors 배열 패턴 유지

### agent11_problem_notes (적용 예정)
- abessi_messages 테이블 확인 로직 수정 필요
- field_path 패턴 유지

## 📝 참고사항

1. **파일 경로**: 대부분 `rules/data_mapping_analysis.php`이지만, agent02와 agent22는 `ui/data_mapping_analysis.php` 사용
2. **에이전트별 특성**: 각 에이전트는 고유한 테이블과 필드를 사용하므로, 표준 템플릿을 기반으로 커스터마이징 필요
3. **에러 처리**: 모든 DB 쿼리는 try-catch로 감싸고, 에러 로그에 파일 경로와 라인 번호 포함

---

**작성일**: 2025-01-XX
**버전**: 1.0
**기준**: agent01_onboarding (개선 버전)

