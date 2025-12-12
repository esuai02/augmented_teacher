# 온보딩 에이전트 룰 엔진 사용 가이드

## 📋 개요

제공된 `onboarding_rules.yaml` 형식을 완전히 지원하는 확장된 룰 엔진입니다.

## ✅ 지원 기능

### 1. Operator 지원
- `==` / `equal`: 동등 비교
- `!=` / `not_equal`: 부등 비교
- `<` / `less_than`: 미만
- `<=` / `less_than_or_equal`: 이하
- `>` / `greater_than`: 초과
- `>=` / `greater_than_or_equal`: 이상
- `in`: 리스트 멤버십 체크
- `matches`: 정규식 매칭
- `contains`: 문자열 포함 체크

### 2. 중첩 필드 접근
- 점 표기법 지원: `goals.long_term`
- 예: `context["goals"]["long_term"]` → `field: "goals.long_term"`

### 3. 액션 형식
- 배열 형식: `["action1", "action2"]`
- 문자열 형식: `"key: value"`
- 딕셔너리 형식: `{"type": "action", "params": {}}`

## 🚀 사용 방법

### Python에서 직접 사용

```python
from onboarding_rule_engine import OnboardingRuleEngine

# Initialize engine
engine = OnboardingRuleEngine('rules/onboarding_rules.yaml')

# Prepare context
context = {
    'student_id': 12345,
    'math_level': '수학이 어려워요',
    'math_confidence': 4,
    'exam_style': '벼락치기',
    'parent_style': '적극 개입',
    'study_hours_per_week': 8,
    'goals': {
        'long_term': '경시대회 준비해 보기'
    },
    'advanced_progress': '공통수학1',
    'concept_progress': '중등3-1',
    'study_style': '개념 정리 위주'
}

# Evaluate rules
decision = engine.decide(context)

# Process actions
for action in decision['actions']:
    print(f"Action: {action}")
    
print(f"Confidence: {decision['confidence']}")
print(f"Rationale: {decision['rationale']}")
```

### PHP에서 사용

```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/rules/rule_evaluator.php');

try {
    $evaluator = new OnboardingRuleEvaluator();
    
    // Prepare student context
    $context = [
        'student_id' => $USER->id,
        'math_level' => '수학이 어려워요',
        'math_confidence' => 4,
        'exam_style' => '벼락치기',
        'parent_style' => '적극 개입',
        'study_hours_per_week' => 8,
        'goals' => [
            'long_term' => '경시대회 준비해 보기'
        ],
        'advanced_progress' => '공통수학1',
        'concept_progress' => '중등3-1',
        'study_style' => '개념 정리 위주'
    ];
    
    // Evaluate rules
    $decision = $evaluator->evaluate($context);
    
    // Process actions
    foreach ($decision['actions'] as $action) {
        // Handle each action
        if (isset($action['display_message'])) {
            echo $action['display_message'] . "\n";
        }
        if (isset($action['recommend_path'])) {
            echo "추천 경로: " . $action['recommend_path'] . "\n";
        }
        // ... 기타 액션 처리
    }
    
    header('Content-Type: application/json');
    echo json_encode($decision, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Rule evaluation error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

### CLI에서 사용

```bash
# Python 직접 실행
python3 onboarding_rule_engine.py '{"student_id": 123, "math_level": "수학이 어려워요", "math_confidence": 4}'

# 룰 파일 지정
python3 onboarding_rule_engine.py '{"student_id": 123, "math_level": "수학이 어려워요"}' custom_rules.yaml
```

## 📝 룰 작성 예시

### 기본 룰 형식

```yaml
rules:
  - rule_id: "R1"
    priority: 90
    description: "설명"
    conditions:
      - field: "math_level"
        operator: "=="
        value: "수학이 어려워요"
    action:
      - "initialize_support_mode: true"
      - "display_message: '메시지'"
    confidence: 0.92
    rationale: "근거 설명"
```

### 복합 조건 (AND 로직)

```yaml
  - rule_id: "R7a"
    priority: 90
    conditions:
      - field: "math_level"
        operator: "in"
        value: ["중위권", "수학이 어려워요"]
      - field: "exam_style"
        operator: "=="
        value: "벼락치기"
    action:
      - "analyze: 'exam_gap_areas'"
```

### 중첩 필드 사용

```yaml
  - rule_id: "R6a"
    conditions:
      - field: "goals.long_term"
        operator: "in"
        value: ["경시대회 준비해 보기", "심화 문제도 풀 수 있는 실력 쌓기"]
    action:
      - "generate_description: 'long_term_focus_summary'"
```

### 정규식 매칭

```yaml
  - rule_id: "R6c"
    conditions:
      - field: "concept_progress"
        operator: "matches"
        value: "^(초등|중등|고등)[0-9]-[1-2]$|중등3-1|중등3-2"
    action:
      - "generate_description: 'concept_progress_summary'"
```

## 🔍 출력 형식

```json
{
  "student_id": 12345,
  "rule_id": "R1",
  "actions": [
    {
      "initialize_support_mode": true
    },
    {
      "recommend_path": "개념 이해 중심 학습 + 짧은 주기 피드백 루프"
    },
    {
      "display_message": "기초 개념 강화 루틴을 우선 추천합니다."
    }
  ],
  "confidence": 0.92,
  "rationale": "수학이 어려운 학생에게 개입 우선순위 높음",
  "description": "수학이 어려운 학생에게 개념 중심 루틴 추천",
  "trace_data": {
    "rules_evaluated": 13,
    "matched_rule_id": "R1",
    "matched_rule_priority": 90,
    "context_snapshot": {...},
    "evaluation_timestamp": "2025-11-03T10:30:00Z"
  },
  "timestamp": "2025-11-03T10:30:00Z"
}
```

## ⚠️ 주의사항

1. **필수 필드**: `student_id`는 반드시 포함되어야 합니다.
2. **Operator 대소문자**: Operator는 대소문자를 구분하지 않습니다 (`==` = `equal`).
3. **필드 누락**: 필드가 없는 경우 `None`으로 처리되며, 조건 평가 시 `False`가 됩니다.
4. **Python 버전**: Python 3.6 이상 필요.
5. **의존성**: `pyyaml` 패키지 필요 (`pip install pyyaml`).

## 🐛 디버깅

### 로그 확인
Python 엔진은 stderr로 상세 로그를 출력합니다:
```bash
python3 onboarding_rule_engine.py '{"student_id": 123, ...}' 2>&1 | grep INFO
```

### 룰 요약 조회
```python
engine = OnboardingRuleEngine()
summary = engine.get_rules_summary()
print(json.dumps(summary, indent=2, ensure_ascii=False))
```

## 📚 참고

- 룰 파일: `onboarding_rules.yaml`
- Python 엔진: `onboarding_rule_engine.py`
- PHP 래퍼: `rule_evaluator.php`
- 기존 MVP 룰 엔진: `../mvp_system/decision/rule_engine.py`

