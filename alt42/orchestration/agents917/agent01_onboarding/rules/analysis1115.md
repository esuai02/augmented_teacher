# 온보딩 에이전트 필드 매핑 및 수정 전략 분석
## Analysis Date: 2025-01-15

---

## 목차
1. [실행 경로 분석](#1-실행-경로-분석)
2. [문제점 분석](#2-문제점-분석)
3. [필드 매핑 현황](#3-필드-매핑-현황)
4. [데이터 소스 분류 전략](#4-데이터-소스-분류-전략)
5. [수정 전략 및 우선순위](#5-수정-전략-및-우선순위)
6. [수정 코드](#6-수정-코드)

---

## 1. 실행 경로 분석

### 1.1 '첫수업 어떻게해?' 입력 시 전체 실행 흐름

```
사용자 입력: "첫수업 어떻게해?"
    ↓
[1] index.php (UI)
    - 사용자 입력 받음
    - JavaScript로 전달
    ↓
[2] agent_garden.js (Frontend)
    - sendMessage() 함수 실행 (라인 119)
    - fetch 요청: agent_garden.controller.php?action=execute
    - POST 데이터: {agent_id: "agent01", request: "첫수업 어떻게해?", student_id: 2}
    ↓
[3] agent_garden.controller.php (Controller)
    - executeAgent() 실행 (라인 60)
    - student_id 결정 (라인 82-112)
    - service->executeAgent() 호출 (라인 116)
    ↓
[4] agent_garden.service.php (Service)
    - executeAgent() 실행 (라인 14)
    - agent01이면 executeAgent01WithRules() 호출 (라인 26)
    ↓
[5] executeAgent01WithRules() (라인 45)
    - data_access.php 로드 (라인 90)
    - prepareRuleContext() 호출하여 학생 컨텍스트 가져오기 (라인 93)
    - user_message를 컨텍스트에 추가: "첫수업 어떻게해?" (라인 117)
    - rule_evaluator.php 로드 (라인 122)
    - OnboardingRuleEvaluator 생성 (라인 126)
    - evaluator->evaluate() 호출 (라인 129)
    ↓
[6] rule_evaluator.php (PHP Wrapper)
    - evaluate() 실행 (라인 46)
    - Python 스크립트 실행: onboarding_rule_engine.py (라인 170)
    - JSON 입력: {"student_id": 2, "user_message": "첫수업 어떻게해?", ...}
    ↓
[7] onboarding_rule_engine.py (Python Rule Engine)
    - decide() 실행 (라인 294)
    - rules.yaml 로드 (라인 67)
    - 각 룰을 우선순위 순으로 평가 (라인 317)
    - S1_R0_first_class_question 룰 평가 시도
    ↓
[8] generateResponseFromActions() (라인 192)
    - Python에서 반환된 decision의 actions 파싱
    - 메시지 생성
    - 온보딩 정보 추가
    ↓
[9] agent_garden.js (Frontend)
    - 응답 받아서 화면에 표시 (라인 265)
```

### 1.2 주요 파일 및 역할

| 파일 | 경로 | 역할 | 핵심 함수/라인 |
|------|------|------|---------------|
| **index.php** | `agent22_module_improvement/ui/index.php` | UI 진입점 | userid 파라미터 처리 (라인 14-21) |
| **agent_garden.js** | `agent22_module_improvement/ui/agent_garden.js` | 프론트엔드 로직 | `sendMessage()` (라인 119) |
| **agent_garden.controller.php** | `agent22_module_improvement/ui/agent_garden.controller.php` | 요청 라우팅 | `executeAgent()` (라인 60) |
| **agent_garden.service.php** | `agent22_module_improvement/ui/agent_garden.service.php` | 비즈니스 로직 | `executeAgent01WithRules()` (라인 45) |
| **data_access.php** | `agent01_onboarding/rules/data_access.php` | 데이터 조회 | `prepareRuleContext()` (라인 451) |
| **rule_evaluator.php** | `agent01_onboarding/rules/rule_evaluator.php` | Python 래퍼 | `evaluate()` (라인 46) |
| **onboarding_rule_engine.py** | `agent01_onboarding/rules/onboarding_rule_engine.py` | 룰 엔진 | `decide()` (라인 294) |
| **rules.yaml** | `agent01_onboarding/rules/rules.yaml` | 룰 정의 | `S1_R0_first_class_question` (라인 149) |

---

## 2. 문제점 분석

### 2.1 핵심 문제: OR 조건 처리 미흡

**위치**: `onboarding_rule_engine.py`의 `evaluate_rule()` 메서드 (라인 226-247)

**문제 코드**:
```python
def evaluate_rule(self, rule: Dict[str, Any], context: Dict[str, Any]) -> bool:
    conditions = rule.get('conditions', [])
    # All conditions must be True (AND logic)
    for condition in conditions:
        if not self.evaluate_condition(condition, context):
            return False
    return True
```

**문제점**:
- 모든 조건을 AND로 처리함
- `rules.yaml`의 OR 조건 블록을 처리하지 못함
- `S1_R0_first_class_question` 룰의 OR 조건이 평가되지 않음

**영향**:
- "첫수업 어떻게해?" 입력 시:
  - 첫 번째 조건: `user_message contains "첫"` → ✅ True
  - 두 번째 조건: OR 블록 → ❌ 처리 안 됨 (False로 간주)
  - 결과: 룰 매치 실패 → default_rule 사용

### 2.2 필드명 불일치 문제

#### 2.2.1 rules.yaml에만 있는 필드 (DB에 없음)

| 필드명 | 사용 빈도 | 문제 | 영향도 |
|--------|----------|------|--------|
| `math_learning_style` | 높음 | 항상 null로 평가됨 | 높음 |
| `math_recent_score` | 중간 | 항상 null로 평가됨 | 중간 |
| `textbooks` | 중간 | 항상 null로 평가됨 | 중간 |
| `math_unit_mastery` | 중간 | 항상 null로 평가됨 | 중간 |
| `new_student_flag` | 높음 | 항상 false로 평가됨 | 높음 |
| `profile_update_flag` | 높음 | 항상 false로 평가됨 | 높음 |

#### 2.2.2 필드명 불일치

| rules.yaml | DB 필드 | 상태 | 문제 |
|-----------|---------|------|------|
| `student_grade` | `grade_detail` | ❌ 매핑 안 됨 | 룰이 항상 null로 평가됨 |
| `study_style` | `problem_preference` | ✅ 매핑됨 | 정상 |
| `study_hours_per_week` | `vacation_hours` | ✅ 매핑됨 | 정상 |
| `goals.long_term` | `long_term_goal` | ✅ 매핑됨 | 정상 |
| `academy_name` | `mdl_alt42o_learning_history.academy_name` | ⚠️ 다른 테이블 | 조회 안 됨 |

### 2.3 현재 상태에서 예상 동작

1. "첫수업 어떻게해?" 입력
2. `user_message`에 "첫수업 어떻게해?" 저장
3. `S1_R0_first_class_question` 룰 평가 시도
4. 첫 번째 조건: `user_message contains "첫"` → ✅ True
5. 두 번째 조건: OR 블록 → ❌ 처리 안 됨 (False로 간주)
6. 룰 매치 실패
7. default_rule 사용
8. 기본 메시지 반환: "온보딩 정보를 분석 중입니다..."

---

## 3. 필드 매핑 현황

### 3.1 필드 매핑 상세 분석

| rules.yaml 필드 | DB 필드 | 매핑 상태 | 데이터 소스 추천 | 우선순위 | 비고 |
|----------------|---------|----------|----------------|---------|------|
| `math_level` | `course_level` + `grade_detail` | ✅ 조합 매핑 | **sysdata** | P0 | 조합하여 생성 |
| `concept_progress` | `concept_progress` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `advanced_progress` | `advanced_progress` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `study_style` | `problem_preference` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `exam_style` | `exam_style` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `math_confidence` | `math_confidence` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `parent_style` | `parent_style` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `study_hours_per_week` | `vacation_hours` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `goals.long_term` | `long_term_goal` | ✅ 직접 매핑 | **survdata** | P0 | 설문 수집 |
| `student_grade` | `grade_detail` | ⚠️ 매핑 필요 | **sysdata** | P1 | 즉시 수정 필요 |
| `academy_name` | `mdl_alt42o_learning_history.academy_name` | ⚠️ 다른 테이블 | **survdata** | P1 | learning_history 조회 필요 |
| `math_learning_style` | ❌ 없음 | ❌ 없음 | **hybriddata** | P2 | 추론 로직 필요 |
| `math_recent_score` | ❌ 없음 | ❌ 없음 | **hybriddata** | P2 | 다른 테이블 확인 또는 설문 |
| `textbooks` | ❌ 없음 | ❌ 없음 | **survdata** | P2 | 설문 수집 필요 |
| `math_unit_mastery` | ❌ 없음 | ❌ 없음 | **hybriddata** | P2 | 추론 로직 필요 |
| `new_student_flag` | ❌ 없음 | ❌ 없음 | **sysdata** | P1 | 계산 로직 필요 |
| `profile_update_flag` | ❌ 없음 | ❌ 없음 | **sysdata** | P1 | 계산 로직 필요 |

### 3.2 DB 스키마 분석

#### 3.2.1 mdl_alt42o_onboarding 테이블

**주요 필드**:
- `course_level` (VARCHAR): 과정 (초등/중등/고등)
- `grade_detail` (VARCHAR): 학년 (1학년/2학년/3학년)
- `concept_progress` (INT): 개념공부 진도 (0-6)
- `advanced_progress` (INT): 심화학습 진도 (0-6)
- `problem_preference` (VARCHAR): 문제풀이 선호도
- `exam_style` (VARCHAR): 시험 대비 성향
- `math_confidence` (INT): 수학 자신감 (0-10)
- `parent_style` (VARCHAR): 부모님 학습 지도 스타일
- `vacation_hours` (INT): 방학중 주별 공부시간
- `long_term_goal` (VARCHAR): 장기 목표
- `short_term_goal` (VARCHAR): 단기 목표
- `mid_term_goal` (VARCHAR): 중기 목표
- `created_at` (TIMESTAMP): 생성일
- `updated_at` (TIMESTAMP): 수정일

#### 3.2.2 mdl_alt42o_learning_history 테이블

**주요 필드**:
- `academy_name` (VARCHAR): 학원/과외 이름
- `academy_type` (VARCHAR): 유형 (학원/과외/자습/온라인)
- `academy_duration` (VARCHAR): 경험 기간
- `start_date` (DATE): 시작일
- `end_date` (DATE): 종료일

#### 3.2.3 mdl_user 테이블

**주요 필드**:
- `id` (BIGINT): 사용자 ID
- `timecreated` (BIGINT): 가입일 (Unix timestamp)
- `firstname` (VARCHAR): 이름
- `lastname` (VARCHAR): 성

---

## 4. 데이터 소스 분류 전략

### 4.1 데이터 소스 분류 정의

#### sysdata (시스템 데이터)
- **정의**: 시스템에 이미 존재하는 데이터로 계산 가능한 값
- **특징**: 별도 수집 불필요, 자동 계산 가능
- **예시**: `new_student_flag`, `profile_update_flag`, `student_grade`

#### survdata (설문 데이터)
- **정의**: 사용자 설문을 통해 수집하는 데이터
- **특징**: 직접적인 사용자 입력 필요
- **예시**: `textbooks`, `academy_name`, `math_recent_score`

#### hybriddata (복합 데이터)
- **정의**: 여러 데이터 소스를 조합하여 추론/모델링한 값
- **특징**: 기존 데이터로 추론 가능하나 정확도는 낮을 수 있음
- **예시**: `math_learning_style`, `math_unit_mastery`

#### gendata (생성 데이터)
- **정의**: 다른 데이터로부터 모델링하여 생성한 값
- **특징**: AI/알고리즘 기반 생성
- **예시**: (현재 사용 안 함)

### 4.2 필드별 데이터 소스 분류 및 생성 전략

#### P0: 즉시 수정 (DB 필드 직접 매핑)

**1. `student_grade`** - sysdata
```php
// 매핑: grade_detail → student_grade
if (!empty($onboarding->grade_detail)) {
    $context['student_grade'] = $onboarding->grade_detail;
}
```
- **근거**: DB에 `grade_detail` 필드가 존재하며 직접 매핑 가능
- **우선순위**: P0 (즉시 수정)

#### P1: sysdata (시스템 데이터로 계산)

**1. `new_student_flag`** - sysdata
```php
// 학생 가입일 기준 (30일 이내 = 신규)
$daysSinceRegistration = floor((time() - $student->timecreated) / 86400);
$context['new_student_flag'] = $daysSinceRegistration <= 30;
```
- **근거**: 
  - `mdl_user.timecreated` 필드 존재
  - 30일 기준은 일반적인 온보딩 기간
  - 별도 수집 불필요
- **우선순위**: P1 (단기 수정)

**2. `profile_update_flag`** - sysdata
```php
// 프로필 업데이트 여부 (updated_at > created_at)
if ($onboarding && isset($onboarding->updated_at) && isset($onboarding->created_at)) {
    $context['profile_update_flag'] = strtotime($onboarding->updated_at) > strtotime($onboarding->created_at);
} else {
    $context['profile_update_flag'] = false;
}
```
- **근거**:
  - `mdl_alt42o_onboarding` 테이블에 `created_at`, `updated_at` 필드 존재
  - 타임스탬프 비교로 판단 가능
- **우선순위**: P1 (단기 수정)

**3. `academy_name`** - survdata (다른 테이블)
```php
// learning_history 테이블에서 최신 학원 정보 가져오기
if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_learning_history'))) {
    $learningHistory = $DB->get_record_sql(
        "SELECT academy_name, academy_type FROM {alt42o_learning_history} 
         WHERE userid = ? AND academy_name IS NOT NULL 
         ORDER BY created_at DESC LIMIT 1",
        [$studentid]
    );
    if ($learningHistory) {
        $context['academy_name'] = $learningHistory->academy_name;
        $context['academy_grade'] = $learningHistory->academy_type;
    }
}
```
- **근거**:
  - `mdl_alt42o_learning_history` 테이블에 `academy_name` 필드 존재
  - 최신 이력 조회로 현재 학원 정보 파악 가능
- **우선순위**: P1 (단기 수정)

#### P2: hybriddata (복합 데이터로 모델링)

**1. `math_learning_style`** - hybriddata (추천)
```php
// 전략: problem_preference + math_confidence + exam_style 조합으로 추론
if (!empty($onboarding->problem_preference)) {
    // 1차: problem_preference 직접 사용
    $context['math_learning_style'] = $onboarding->problem_preference;
} else {
    // 2차: 다른 필드로 추론
    $inferredStyle = null;
    
    // 계산형: math_confidence 높고 exam_style이 '계산 중심'
    if ($onboarding->math_confidence >= 7 && 
        (strpos($onboarding->exam_style ?? '', '계산') !== false)) {
        $inferredStyle = '계산형';
    }
    // 개념형: exam_style이 '개념 중심' 또는 study_style이 '개념 정리 위주'
    elseif (strpos($onboarding->exam_style ?? '', '개념') !== false ||
            strpos($onboarding->problem_preference ?? '', '개념') !== false) {
        $inferredStyle = '개념형';
    }
    // 응용형: advanced_progress가 높고 exam_style이 '응용 중심'
    elseif ($onboarding->advanced_progress >= 4) {
        $inferredStyle = '응용형';
    }
    
    $context['math_learning_style'] = $inferredStyle;
}
```
- **근거**:
  - 단일 필드가 없어도 기존 필드 조합으로 추론 가능
  - `problem_preference`, `exam_style`, `math_confidence`, `advanced_progress` 조합
  - 정확도: 중간 (추론이므로 설문 수집이 더 정확하나, 초기값으로 사용 가능)
- **우선순위**: P2 (중기 수정)

**2. `math_recent_score`** - hybriddata (추천)
```php
// 전략: 다른 테이블에서 성적 데이터 가져오기 또는 null 유지
// 옵션 1: mdl_user_info_data에서 성적 정보 확인
if ($DB->get_manager()->table_exists(new xmldb_table('user_info_data'))) {
    // fieldid 확인 필요 (수학 성적 관련 필드)
    $mathScoreField = $DB->get_record_sql(
        "SELECT data FROM {user_info_data} 
         WHERE userid = ? AND fieldid IN (
             SELECT id FROM {user_info_field} 
             WHERE shortname LIKE '%math%score%' OR shortname LIKE '%수학%성적%'
         )
         ORDER BY id DESC LIMIT 1",
        [$studentid]
    );
    if ($mathScoreField && is_numeric($mathScoreField->data)) {
        $context['math_recent_score'] = intval($mathScoreField->data);
    }
}

// 옵션 2: 없으면 null 유지 (설문으로 수집)
```
- **근거**:
  - Moodle의 `user_info_data` 테이블에 성적 정보가 있을 수 있음
  - 없으면 설문으로 수집 필요
  - 정확도: 낮음 (다른 테이블 의존)
- **우선순위**: P2 (중기 수정)

**3. `math_unit_mastery`** - hybriddata (추천)
```php
// 전략: concept_progress + advanced_progress + course_level로 추론
$unitMastery = [];
if ($onboarding->concept_progress !== null && $onboarding->advanced_progress !== null) {
    // course_level과 progress 값으로 단원 마스터리 추론
    $courseLevel = $onboarding->course_level ?? '';
    $gradeDetail = $onboarding->grade_detail ?? '';
    
    if (!empty($courseLevel) && !empty($gradeDetail)) {
        $unitMastery = [
            'current_unit' => $courseLevel . ' ' . $gradeDetail,
            'concept_mastery' => $onboarding->concept_progress,
            'advanced_mastery' => $onboarding->advanced_progress,
            'completed_units' => [], // 향후 확장 가능
            'in_progress_units' => [], // 향후 확장 가능
            'not_started_units' => [] // 향후 확장 가능
        ];
    }
}
$context['math_unit_mastery'] = $unitMastery;
```
- **근거**:
  - 진도 정보(`concept_progress`, `advanced_progress`)와 과정 정보(`course_level`, `grade_detail`)로 단원 마스터리 추론 가능
  - 정확도: 중간 (기본 정보는 제공 가능하나, 상세 단원별 정보는 설문 필요)
- **우선순위**: P2 (중기 수정)

#### P2: survdata (설문으로 수집)

**1. `textbooks`** - survdata
```php
// 설문으로 수집 (DB에 필드 추가 필요하거나 별도 테이블)
// 현재는 null 유지, 향후 설문으로 수집
$context['textbooks'] = null; // 설문 수집 필요
```
- **근거**:
  - 교재 정보는 사용자 입력이 필요
  - DB에 필드 추가 또는 별도 테이블 필요
  - 정확도: 높음 (직접 입력)
- **우선순위**: P2 (중기 수정)

---

## 5. 수정 전략 및 우선순위

### 5.1 수정 우선순위

#### 우선순위 1 (P0): 즉시 수정 - OR 조건 처리 + 필드명 매핑

**목표**: 기본 동작 복구

1. ✅ `onboarding_rule_engine.py` - OR 조건 처리 추가
2. ✅ `data_access.php` - `student_grade` 매핑 추가
3. ✅ `data_access.php` - `academy_name` learning_history에서 가져오기
4. ✅ `data_access.php` - `new_student_flag`, `profile_update_flag` 계산 로직 추가

**예상 효과**:
- '첫수업 어떻게해?' 질문이 정상 처리됨
- 기본 필드 매핑 문제 해결

#### 우선순위 2 (P1): 단기 수정 - sysdata 계산 로직

**목표**: 시스템 데이터 활용

1. ✅ `new_student_flag` 계산 로직 구현
2. ✅ `profile_update_flag` 계산 로직 구현
3. ✅ `academy_name` 조회 로직 구현

**예상 효과**:
- 신규 학생 판단 정확도 향상
- 프로필 업데이트 감지 가능
- 학원 정보 조회 가능

#### 우선순위 3 (P2): 중기 수정 - hybriddata 모델링

**목표**: 누락 필드 추론

1. ✅ `math_learning_style` 추론 로직 추가
2. ✅ `math_unit_mastery` 추론 로직 추가
3. ⚠️ `math_recent_score` 다른 테이블 확인 또는 설문 수집
4. ⚠️ `textbooks` 설문 수집 인터페이스 추가
5. ⚠️ `rules.yaml` - 해당 필드 사용 룰의 조건 완화

**예상 효과**:
- 누락 필드로 인한 룰 실패 감소
- 초기값 제공으로 온보딩 품질 향상

### 5.2 rules.yaml 수정 전략

#### Phase 1: 즉시 수정 (P0, P1)

**수정 1: student_grade 필드명 변경 또는 매핑**
```yaml
# 옵션 1: rules.yaml에서 필드명 변경
- field: "grade_detail"  # student_grade → grade_detail
  operator: "!="
  value: null

# 옵션 2: data_access.php에서 매핑 (권장)
# student_grade를 grade_detail로 매핑하여 rules.yaml 수정 불필요
```

**수정 2: new_student_flag, profile_update_flag 조건 유지**
```yaml
# sysdata로 계산되므로 조건 그대로 유지 가능
# (data_access.php에서 계산 로직 추가 필요)
- OR:
  - field: "new_student_flag"
    operator: "=="
    value: true
  - field: "profile_update_flag"
    operator: "=="
    value: true
```

#### Phase 2: 중기 수정 (P2 - hybriddata)

**수정 3: math_learning_style 조건 완화**
```yaml
# 옵션 1: problem_preference로 대체
- OR:
  - field: "math_learning_style"
    operator: "=="
    value: null
  - field: "problem_preference"
    operator: "=="
    value: null

# 옵션 2: 조건 완화 (null이 아니면 통과)
- field: "math_learning_style"
  operator: "!="
  value: null
```

**수정 4: math_recent_score, textbooks, math_unit_mastery**
```yaml
# 옵션 1: 조건을 optional로 변경 (없어도 룰 실행 가능)
# 옵션 2: 해당 필드를 사용하는 룰의 priority를 낮춤
# 옵션 3: 필드가 없을 때의 fallback 룰 추가
```

---

## 6. 수정 코드

### 6.1 onboarding_rule_engine.py 수정

**파일**: `alt42/orchestration/agents/agent01_onboarding/rules/onboarding_rule_engine.py`

**수정 위치**: 라인 226-247

**수정 내용**:
```python
def evaluate_rule(self, rule: Dict[str, Any], context: Dict[str, Any]) -> bool:
    """
    Evaluate all conditions of a rule (AND logic with OR support)
    
    Args:
        rule: Rule definition with conditions list
        context: Student context data
    
    Returns:
        bool: True if all conditions match
    """
    conditions = rule.get('conditions', [])
    
    if not conditions:
        return True
    
    # Process conditions with OR support
    for condition in conditions:
        # Check if this is an OR condition
        if isinstance(condition, dict) and 'OR' in condition:
            # OR condition: at least one must be True
            or_conditions = condition['OR']
            or_result = False
            for or_cond in or_conditions:
                if self.evaluate_condition(or_cond, context):
                    or_result = True
                    print(f"INFO: OR condition matched: {or_cond.get('field', 'unknown')} at {__file__}:{self._get_line()}", 
                          file=sys.stderr)
                    break
            if not or_result:
                print(f"INFO: OR condition failed - none of {len(or_conditions)} conditions matched at {__file__}:{self._get_line()}", 
                      file=sys.stderr)
                return False  # OR condition failed
        else:
            # Regular AND condition
            if not self.evaluate_condition(condition, context):
                print(f"INFO: Condition failed: {condition.get('field', 'unknown')} {condition.get('operator', 'unknown')} {condition.get('value', 'unknown')} at {__file__}:{self._get_line()}", 
                      file=sys.stderr)
                return False
    
    return True
```

### 6.2 data_access.php 수정

**파일**: `alt42/orchestration/agents/agent01_onboarding/rules/data_access.php`

#### 수정 1: student_grade 매핑 추가 (라인 410 이후)

```php
// student_grade 매핑 추가 (grade_detail 사용)
if (!empty($onboarding->grade_detail)) {
    $context['student_grade'] = $onboarding->grade_detail;
}
```

#### 수정 2: academy_name을 learning_history에서 가져오기 (라인 429 이후)

```php
// academy_name을 learning_history 테이블에서 가져오기
if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_learning_history'))) {
    $learningHistory = $DB->get_record_sql(
        "SELECT academy_name, academy_type FROM {alt42o_learning_history} 
         WHERE userid = ? AND academy_name IS NOT NULL 
         ORDER BY created_at DESC LIMIT 1",
        [$studentid]
    );
    if ($learningHistory) {
        $context['academy_name'] = $learningHistory->academy_name;
        $context['academy_grade'] = $learningHistory->academy_type;
    }
}
```

#### 수정 3: new_student_flag 계산 (라인 285 이후)

```php
// 학생 가입일 확인 (30일 이내면 신규 학생)
$daysSinceRegistration = floor((time() - $student->timecreated) / 86400);
$context['new_student_flag'] = $daysSinceRegistration <= 30;
```

#### 수정 4: profile_update_flag 계산 (라인 420 이후)

```php
// 프로필 업데이트 플래그 (updated_at이 created_at보다 최근이면 true)
if ($onboarding && isset($onboarding->updated_at) && isset($onboarding->created_at)) {
    $context['profile_update_flag'] = strtotime($onboarding->updated_at) > strtotime($onboarding->created_at);
} else {
    $context['profile_update_flag'] = false;
}
```

#### 수정 5: math_learning_style 추론 (hybriddata) - 라인 420 이후

```php
// math_learning_style 추론 (hybriddata)
if (!empty($onboarding->problem_preference)) {
    // 1차: problem_preference 직접 사용
    $context['math_learning_style'] = $onboarding->problem_preference;
} else {
    // 2차: 다른 필드로 추론
    $inferredStyle = null;
    
    // 계산형: math_confidence 높고 exam_style이 '계산 중심'
    if ($onboarding->math_confidence >= 7 && 
        (strpos($onboarding->exam_style ?? '', '계산') !== false)) {
        $inferredStyle = '계산형';
    }
    // 개념형: exam_style이 '개념 중심' 또는 study_style이 '개념 정리 위주'
    elseif (strpos($onboarding->exam_style ?? '', '개념') !== false ||
            strpos($onboarding->problem_preference ?? '', '개념') !== false) {
        $inferredStyle = '개념형';
    }
    // 응용형: advanced_progress가 높고 exam_style이 '응용 중심'
    elseif ($onboarding->advanced_progress >= 4) {
        $inferredStyle = '응용형';
    }
    
    $context['math_learning_style'] = $inferredStyle;
}
```

#### 수정 6: math_unit_mastery 추론 (hybriddata) - 라인 420 이후

```php
// math_unit_mastery 추론 (hybriddata)
if ($onboarding->concept_progress !== null && $onboarding->advanced_progress !== null) {
    $courseLevel = $onboarding->course_level ?? '';
    $gradeDetail = $onboarding->grade_detail ?? '';
    
    if (!empty($courseLevel) && !empty($gradeDetail)) {
        $context['math_unit_mastery'] = [
            'current_unit' => $courseLevel . ' ' . $gradeDetail,
            'concept_mastery' => $onboarding->concept_progress,
            'advanced_mastery' => $onboarding->advanced_progress,
            'completed_units' => [], // 향후 확장 가능
            'in_progress_units' => [], // 향후 확장 가능
            'not_started_units' => [] // 향후 확장 가능
        ];
    }
}
```

#### 수정 7: math_recent_score 조회 (hybriddata) - 라인 420 이후

```php
// math_recent_score 조회 (hybriddata)
// 옵션 1: mdl_user_info_data에서 성적 정보 확인
if ($DB->get_manager()->table_exists(new xmldb_table('user_info_data'))) {
    $mathScoreField = $DB->get_record_sql(
        "SELECT data FROM {user_info_data} 
         WHERE userid = ? AND fieldid IN (
             SELECT id FROM {user_info_field} 
             WHERE shortname LIKE '%math%score%' OR shortname LIKE '%수학%성적%'
         )
         ORDER BY id DESC LIMIT 1",
        [$studentid]
    );
    if ($mathScoreField && is_numeric($mathScoreField->data)) {
        $context['math_recent_score'] = intval($mathScoreField->data);
    }
}
// 옵션 2: 없으면 null 유지 (설문으로 수집)
```

### 6.3 전체 수정 코드 통합

**data_access.php 전체 수정 위치**:

```php
// 라인 285 이후 (학생 기본 정보 조회 후)
// new_student_flag 계산 추가
$daysSinceRegistration = floor((time() - $student->timecreated) / 86400);
$context['new_student_flag'] = $daysSinceRegistration <= 30;

// ... 기존 코드 ...

// 라인 410 이후 (onboarding 데이터 처리 중)
// student_grade 매핑 추가
if (!empty($onboarding->grade_detail)) {
    $context['student_grade'] = $onboarding->grade_detail;
}

// ... 기존 코드 ...

// 라인 420 이후 (onboarding 데이터 처리 완료 후)
// profile_update_flag 계산
if ($onboarding && isset($onboarding->updated_at) && isset($onboarding->created_at)) {
    $context['profile_update_flag'] = strtotime($onboarding->updated_at) > strtotime($onboarding->created_at);
} else {
    $context['profile_update_flag'] = false;
}

// math_learning_style 추론 (hybriddata)
if (!empty($onboarding->problem_preference)) {
    $context['math_learning_style'] = $onboarding->problem_preference;
} else {
    $inferredStyle = null;
    if ($onboarding->math_confidence >= 7 && 
        (strpos($onboarding->exam_style ?? '', '계산') !== false)) {
        $inferredStyle = '계산형';
    } elseif (strpos($onboarding->exam_style ?? '', '개념') !== false ||
              strpos($onboarding->problem_preference ?? '', '개념') !== false) {
        $inferredStyle = '개념형';
    } elseif ($onboarding->advanced_progress >= 4) {
        $inferredStyle = '응용형';
    }
    $context['math_learning_style'] = $inferredStyle;
}

// math_unit_mastery 추론 (hybriddata)
if ($onboarding->concept_progress !== null && $onboarding->advanced_progress !== null) {
    $courseLevel = $onboarding->course_level ?? '';
    $gradeDetail = $onboarding->grade_detail ?? '';
    
    if (!empty($courseLevel) && !empty($gradeDetail)) {
        $context['math_unit_mastery'] = [
            'current_unit' => $courseLevel . ' ' . $gradeDetail,
            'concept_mastery' => $onboarding->concept_progress,
            'advanced_mastery' => $onboarding->advanced_progress
        ];
    }
}

// math_recent_score 조회 (hybriddata)
if ($DB->get_manager()->table_exists(new xmldb_table('user_info_data'))) {
    $mathScoreField = $DB->get_record_sql(
        "SELECT data FROM {user_info_data} 
         WHERE userid = ? AND fieldid IN (
             SELECT id FROM {user_info_field} 
             WHERE shortname LIKE '%math%score%' OR shortname LIKE '%수학%성적%'
         )
         ORDER BY id DESC LIMIT 1",
        [$studentid]
    );
    if ($mathScoreField && is_numeric($mathScoreField->data)) {
        $context['math_recent_score'] = intval($mathScoreField->data);
    }
}

// 라인 429 이후 (onboarding 데이터 처리 완료 후)
// academy_name을 learning_history에서 가져오기
if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_learning_history'))) {
    $learningHistory = $DB->get_record_sql(
        "SELECT academy_name, academy_type FROM {alt42o_learning_history} 
         WHERE userid = ? AND academy_name IS NOT NULL 
         ORDER BY created_at DESC LIMIT 1",
        [$studentid]
    );
    if ($learningHistory) {
        $context['academy_name'] = $learningHistory->academy_name;
        $context['academy_grade'] = $learningHistory->academy_type;
    }
}
```

---

## 7. 검증 및 테스트 계획

### 7.1 수정 후 검증 항목

1. **OR 조건 처리 검증**
   - '첫수업 어떻게해?' 입력 시 `S1_R0_first_class_question` 룰 매치 확인
   - 로그에서 `matched_rule_id`가 `S1_R0_first_class_question`인지 확인

2. **필드 매핑 검증**
   - `student_grade` 값이 `grade_detail`과 일치하는지 확인
   - `academy_name`이 learning_history에서 조회되는지 확인
   - `new_student_flag`, `profile_update_flag` 계산 값 확인

3. **hybriddata 추론 검증**
   - `math_learning_style` 추론 값이 합리적인지 확인
   - `math_unit_mastery` 구조가 올바른지 확인

### 7.2 테스트 시나리오

**시나리오 1: 신규 학생 첫 수업 질문**
- 입력: "첫수업 어떻게해?"
- 예상: `S1_R0_first_class_question` 룰 매치
- 확인: 적절한 메시지와 질문 반환

**시나리오 2: 기존 학생 프로필 요약**
- 입력: "내 정보 요약해줘"
- 예상: 수집된 온보딩 정보 표시
- 확인: 필드 매핑이 올바른지 확인

---

## 8. 향후 개선 사항

### 8.1 DB 스키마 확장 고려

1. **`textbooks` 필드 추가**
   - `mdl_alt42o_onboarding` 테이블에 `textbooks` JSON 필드 추가 고려
   - 또는 별도 테이블 `mdl_alt42o_textbooks` 생성

2. **`math_recent_score` 필드 추가**
   - `mdl_alt42o_onboarding` 테이블에 `math_recent_score`, `math_recent_ranking` 필드 추가 고려

3. **`math_learning_style` 필드 추가**
   - `mdl_alt42o_onboarding` 테이블에 `math_learning_style` 필드 추가 고려
   - 또는 `mdl_alt42o_learning_style_analysis` 테이블 활용

### 8.2 설문 수집 인터페이스

1. **온보딩 설문 폼 개발**
   - `textbooks`, `math_recent_score` 등 누락 필드 수집
   - 온보딩 프로세스에 통합

2. **데이터 검증 로직**
   - 수집된 데이터의 유효성 검증
   - 필수 필드 누락 시 알림

---

## 9. 참고 자료

### 9.1 관련 파일
- `alt42/orchestration/agents/agent01_onboarding/rules/rules.yaml`
- `alt42/orchestration/agents/agent01_onboarding/rules/data_access.php`
- `alt42/orchestration/agents/agent01_onboarding/rules/onboarding_rule_engine.py`
- `alt42/orchestration/agents/agent01_onboarding/rules/rule_evaluator.php`
- `alt42/orchestrationk/db/create_alt42o_tables.sql`

### 9.2 관련 문서
- `alt42/orchestration/agents/agent01_onboarding/rules/metadata.md`
- `alt42/orchestration/agents/agent01_onboarding/rules/questions.md`
- `alt42/orchestration/agents/agent01_onboarding/rules/student_survey.md`

---

## 10. 변경 이력

| 날짜 | 버전 | 변경 내용 | 작성자 |
|------|------|----------|--------|
| 2025-01-15 | 1.0 | 초기 분석 문서 작성 | AI Assistant |

---

**문서 끝**

