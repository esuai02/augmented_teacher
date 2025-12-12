# 학생 설문 조사 - Rules에만 있고 온보딩에서 수집되지 않는 필드

## 개요
이 문서는 `agent01_onboarding_rules.yaml`에서 사용하는 필드 중 **온보딩 인터페이스와 설문 인터페이스를 통해 이미 저장되는 데이터를 제외**하고, **rules에만 있고 실제로 수집되지 않는 필드**만 정리한 것입니다.

생성일: 2025-01-21  
업데이트: 2025-01-21 (온보딩 시스템 수집 필드 확인 후 재작성)  
분석 대상: `agent01_onboarding_rules.yaml` vs `onboarding/includes/database.php` (실제 수집 필드)

---

## ✅ 이미 온보딩에서 수집되는 필드 (제외됨)

다음 필드들은 `onboarding_info.php`를 통해 이미 `alt42o_onboarding` 테이블에 저장되고 있습니다:

1. **`math_level`** → `mathLevel` (math_level 필드로 저장)
2. **`math_confidence`** → `mathConfidence` (math_confidence 필드로 저장)
3. **`exam_style`** → `examStyle` (exam_style 필드로 저장)
4. **`parent_style`** → `parentStyle` (parent_style 필드로 저장)
5. **`goals.long_term`** → `longTermGoal` (long_term_goal 필드로 저장, `alt42o_goal_tracking` 테이블에도 저장)
6. **`concept_progress`** → `conceptLevel`, `conceptProgress` (concept_level, concept_progress 필드로 저장)
7. **`advanced_progress`** → `advancedLevel`, `advancedProgress` (advanced_level, advanced_progress 필드로 저장)

**참고**: `data.php`에서 이 필드들을 `alt42o_onboarding` 테이블에서 조회하도록 수정이 필요할 수 있습니다.

---

## ❌ Rules에만 있고 온보딩에서 수집되지 않는 필드

### 1. 주간 학습 시간
**필드명**: `study_hours_per_week`

**현재 상태**:
- 온보딩 시스템에는 `weeklyHours` 필드가 있지만, rules.yaml의 `study_hours_per_week`와 매칭되지 않음
- `data.php`에서 조회하지 못함

**설문 질문 예시**:
- "일주일에 수학 공부를 몇 시간 정도 하시나요?"
  - 입력: 숫자 (시간 단위, 예: 10, 15, 20)

**저장 위치**: 
- `alt42o_onboarding` 테이블에 `study_hours_per_week` 필드 추가 필요
- 또는 `weeklyHours` 필드를 `study_hours_per_week`로 매핑

**필드 구조**:
```sql
ALTER TABLE `alt42o_onboarding` 
ADD COLUMN `study_hours_per_week` INT(3) NULL COMMENT '주간 학습 시간 (시간 단위)' 
AFTER `parent_style`;
```

**매핑 방법** (선택):
- `weeklyHours` 필드가 이미 수집되고 있다면, `data.php`에서 `weeklyHours`를 `study_hours_per_week`로 매핑

---

### 2. 학습 스타일
**필드명**: `study_style`

**현재 상태**:
- 온보딩 시스템에는 `problem_preference` 필드가 있지만, rules.yaml의 `study_style`과는 다른 의미
- `problem_preference`: 문제 선호도 (예: 계산 문제, 응용 문제 등)
- `study_style`: 학습 방식 (개념 정리 위주 vs 문제풀이 위주)
- `data.php`에서 조회하지 못함

**설문 질문 예시**:
- "어떤 방식으로 학습하시나요?"
  - 선택지:
    - "개념 정리 위주" (개념을 먼저 이해하고 정리한 후 문제풀이)
    - "문제풀이 위주" (문제를 많이 풀면서 학습)

**저장 위치**: 
- `alt42o_onboarding` 테이블에 `study_style` 필드 추가 필요

**필드 구조**:
```sql
ALTER TABLE `alt42o_onboarding` 
ADD COLUMN `study_style` VARCHAR(50) NULL COMMENT '학습 스타일: 개념 정리 위주, 문제풀이 위주' 
AFTER `problem_preference`;
```

---

## 📋 수집 필요 필드 요약

| 필드명 | 현재 상태 | 수집 방법 | 우선순위 |
|--------|----------|----------|---------|
| `study_hours_per_week` | ❌ 수집 안 됨 | 온보딩 폼에 추가 또는 `weeklyHours` 매핑 | 높음 |
| `study_style` | ❌ 수집 안 됨 | 온보딩 폼에 추가 | 높음 |

---

## 🔧 데이터베이스 스키마 업데이트 SQL

```sql
-- alt42o_onboarding 테이블에 누락된 필드 추가
ALTER TABLE `alt42o_onboarding` 
ADD COLUMN `study_hours_per_week` INT(3) NULL COMMENT '주간 학습 시간 (시간 단위)' AFTER `parent_style`,
ADD COLUMN `study_style` VARCHAR(50) NULL COMMENT '학습 스타일: 개념 정리 위주, 문제풀이 위주' AFTER `problem_preference`;

-- 인덱스 추가 (선택사항)
ALTER TABLE `alt42o_onboarding` 
ADD INDEX `idx_study_hours` (`study_hours_per_week`),
ADD INDEX `idx_study_style` (`study_style`);
```

---

## 📝 data.php 업데이트 필요 사항

`data.php`의 `getOnboardingContext()` 함수에서 `alt42o_onboarding` 테이블을 조회하도록 수정이 필요합니다:

```php
// alt42o_onboarding 테이블에서 데이터 조회 (현재는 mdl_alt42_onboarding만 조회)
if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_onboarding'))) {
    $onboarding = $DB->get_record('alt42o_onboarding', ['userid' => $studentid], '*', IGNORE_MISSING);
    if ($onboarding) {
        // 기존 필드 매핑
        $context['math_level'] = $onboarding->math_level ?? null;
        $context['math_confidence'] = $onboarding->math_confidence ?? null;
        $context['exam_style'] = $onboarding->exam_style ?? null;
        $context['parent_style'] = $onboarding->parent_style ?? null;
        
        // 새로 추가할 필드
        $context['study_hours_per_week'] = $onboarding->study_hours_per_week ?? $onboarding->weekly_hours ?? null;
        $context['study_style'] = $onboarding->study_style ?? null;
        
        // goals 매핑
        if (!empty($onboarding->long_term_goal)) {
            $context['goals']['long_term'] = $onboarding->long_term_goal;
        }
        
        // 진도 매핑
        $context['concept_progress'] = $onboarding->concept_level ?? null;
        $context['advanced_progress'] = $onboarding->advanced_level ?? null;
    }
}
```

---

## 🎯 수집 방법 제안

### 방법 1: 온보딩 폼에 필드 추가 (권장)
- `onboarding_info.php`의 폼에 다음 필드 추가:
  - 주간 학습 시간 입력 필드
  - 학습 스타일 선택 필드 (개념 정리 위주 / 문제풀이 위주)

### 방법 2: 기존 필드 매핑
- `weeklyHours` → `study_hours_per_week` 매핑 (이미 수집되고 있다면)
- `problem_preference`는 `study_style`과 다른 의미이므로 별도 수집 필요

### 방법 3: 별도 설문 페이지
- 온보딩 완료 후 추가 설문 페이지 제공
- rules.yaml에서 필요한 필드만 수집

---

## ✅ 확인 사항

1. **`weeklyHours` 필드 확인**
   - `onboarding_info.php`에서 `weeklyHours`가 실제로 수집되는지 확인
   - 수집된다면 `data.php`에서 매핑만 추가하면 됨

2. **`problem_preference` vs `study_style`**
   - `problem_preference`는 문제 유형 선호도 (계산/응용 등)
   - `study_style`은 학습 방식 (개념 위주/문제 위주)
   - 두 필드는 다른 의미이므로 별도 수집 필요

3. **`alt42o_onboarding` 테이블 구조 확인**
   - 실제 테이블에 어떤 필드가 있는지 확인
   - 필드명이 다를 수 있음 (예: `userid` vs `user_id`)

---

## 📌 참고사항

- 모든 필드는 NULL 허용 (점진적 수집 가능)
- 기존 온보딩 데이터와의 호환성 유지 필요
- `data.php`에서 `alt42o_onboarding` 테이블 조회 로직 추가 필요
- 필드명 매핑 시 camelCase ↔ snake_case 변환 주의
