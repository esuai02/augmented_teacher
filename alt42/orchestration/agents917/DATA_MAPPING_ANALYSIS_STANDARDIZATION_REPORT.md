# 데이터 매핑 분석 도구 표준화 보고서

## 📋 분석 개요

모든 에이전트의 `data_mapping_analysis.php` 파일을 분석하여 구조 일치 여부를 확인하고, 가장 성공적인 구조를 기준으로 통일 작업을 진행합니다.

## 🔍 분석 결과

### 1. 파일명 일치 여부

| 에이전트 | 파일 경로 | 상태 |
|---------|----------|------|
| agent01_onboarding | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent02_exam_schedule | `ui/data_mapping_analysis.php` | ⚠️ 경로 다름 |
| agent05_learning_emotion | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent08_calmness | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent09_learning_management | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent11_problem_notes | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent12_rest_routine | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent14_current_position | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent15_problem_redefinition | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent16_interaction_preparation | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent17_remaining_activities | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent18_signature_routine | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent19_interaction_content | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent20_intervention_preparation | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent21_intervention_execution | `rules/data_mapping_analysis.php` | ✅ 표준 |
| agent22_module_improvement | `ui/data_mapping_analysis.php` | ⚠️ 경로 다름 |

**결론**: 대부분 `rules/data_mapping_analysis.php` 경로를 사용하지만, agent02와 agent22는 `ui/` 경로를 사용합니다.

### 2. 구조 일치 여부

#### 공통 구조 (agent01 기준)

```php
1. Moodle 설정 및 인증
   - include_once("/home/moodle/public_html/moodle/config.php")
   - global $DB, $USER, $PAGE, $OUTPUT
   - require_login()
   - 권한 체크

2. rules.yaml 필드 추출
   - preg_match_all('/field:\s*"([^"]+)"/', ...)

3. data_access.php 필드 추출
   - preg_match_all('/\$context\[\'([^\']+)\'\]/', ...)
   - 추가 패턴 추출 (에이전트별 상이)

4. view_reports.php 필드 추출
   - 테이블명 추출
   - 필드명 추출

5. DB 테이블 구조 확인
   - xmldb_table 사용

6. 데이터 타입 분류 함수
   - classifyDataType($fieldName, $tableName)

7. 분석 결과 생성
   - inRulesNotInDataAccess
   - inDataAccessNotInRules
   - inDbNotInRules
   - inViewReportsNotInRules
   - mappingMismatches

8. HTML 출력
   - 통일된 스타일
   - 섹션별 분석 결과 표시
```

#### 주요 차이점

| 에이전트 | 차이점 |
|---------|--------|
| agent01 | ✅ 가장 기본적이고 명확한 구조 |
| agent02 | 함수 기반 구조 (parseYamlRules, parseDataAccess 등) |
| agent05 | 추가 패턴 추출 (activity_type, emotion_type 등) |
| agent08 | factors 배열 패턴 추가 |
| agent11 | field_path 패턴, 배열 필드 추출 추가 |
| agent12 | source_type 패턴 추가 |
| agent19 | 필드 타입 정보(survdata/sysdata/gendata) 상세 분석 |
| agent20 | 함수 기반 구조 + collect_info, depends_on 패턴 |

### 3. 기능 일치 여부

#### 필수 기능 체크리스트

- [x] rules.yaml 필드 추출
- [x] data_access.php 필드 추출
- [x] view_reports.php 필드 추출
- [x] DB 테이블 구조 확인
- [x] 데이터 타입 분류 (survdata/sysdata/gendata)
- [x] rules.yaml vs data_access.php 비교
- [x] DB 존재 여부 확인
- [x] 매핑 불일치 확인
- [ ] **DB에 실제 데이터 존재 여부 확인** (일부 에이전트만 구현)
- [ ] **data_access.php에서 실제 사용 여부 확인** (일부 에이전트만 구현)

## 🎯 표준화 기준

**기준 에이전트**: `agent01_onboarding`

**선택 이유**:
1. 가장 기본적이고 명확한 구조
2. 가장 널리 사용되는 구조
3. 다른 에이전트들이 이를 기반으로 확장

**개선 사항**:
- agent19의 상세한 데이터 타입 분석 기능 통합
- agent02의 함수 기반 구조는 유지하되, agent01의 단순함 유지
- DB 실제 데이터 존재 여부 확인 기능 추가

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
// 8. 분석 결과 생성
// 9. HTML 출력
```

## ✅ 통일 작업 계획

1. **Phase 1**: agent01을 기준으로 표준 템플릿 생성
2. **Phase 2**: 각 에이전트별 특화 부분 식별 및 통합
3. **Phase 3**: 모든 에이전트에 표준 템플릿 적용
4. **Phase 4**: dataindex.html 파일도 일치 여부 확인

## 📊 진행 상황

- [x] 모든 에이전트 파일 구조 분석 완료
- [ ] 표준 템플릿 생성
- [ ] 각 에이전트에 적용
- [ ] 테스트 및 검증

---

**작성일**: 2025-01-XX
**작성자**: AI Assistant

