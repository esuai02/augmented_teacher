# 03-KNOWLEDGE_BASE_ARCHITECTURE.md

**지식 베이스 아키텍처 명세서**
**LLM-Optimized Knowledge Base Architecture Specification**

Version: 1.0
Last Updated: 2025-10-29
Status: Draft

---

## 문서 개요

이 문서는 Mathking 시스템의 지식 베이스 구조를 LLM(Large Language Model) 관점에서 최적화하는 방법을 명세합니다. 지식 베이스는 Reasoning Engine이 의사결정 시 참조하는 핵심 정보 저장소로, **사람이 편집 가능**하면서도 **LLM이 효율적으로 이해하고 활용**할 수 있어야 합니다.

### 문서 목적

1. **LLM 친화적 구조**: LLM이 효율적으로 파싱하고 이해할 수 있는 지식 구조 설계
2. **중앙 집중화**: 분산된 에이전트별 지식 파일이 아닌, 중앙 집중화된 단일 진실원(SSOT) 구조
3. **규칙 DSL 설계**: 사람이 읽고 편집할 수 있으면서도 기계가 실행 가능한 DSL(Domain-Specific Language)
4. **프롬프트 엔지니어링**: LLM 추론을 위한 최적화된 프롬프트 템플릿 설계
5. **지식 버전 관리**: 지식의 진화와 변경 이력 관리 방법론

---

## 1. 지식 베이스 구조 개요

### 1.1 전체 아키텍처

```
mathking/knowledge/
├── 의사결정_지식.md              # 중앙 집중화된 정책 & 전략 (사람 편집)
├── 의사결정_실행.md              # 실행 액션 정의 (사람 편집)
├── policies/                     # 정책 규칙
│   ├── rule_catalog.yaml        # 규칙 DSL 카탈로그
│   ├── safety.yaml              # 안전 가드레일
│   └── rollback.yaml            # 롤백 정책
├── actions/                     # 액션 정의
│   ├── action_lower_difficulty.yaml
│   ├── action_insert_break.yaml
│   └── action_provide_hint.yaml
├── guides/                      # LLM 가이드
│   ├── llm_decision_prompt.md   # LLM 의사결정 프롬프트
│   └── report_style_guide.md    # 리포트 스타일 가이드
└── version_history/             # 버전 관리
    ├── changelog.md
    └── archived/
```

### 1.2 설계 원칙

```yaml
design_principles:

  principle_1_single_source_of_truth:
    name: "단일 진실원 (Single Source of Truth)"
    description: |
      분산된 에이전트별 지식 파일이 아닌, 중앙 집중화된 knowledge 폴더에서 모든 정책 관리.
      에이전트별 파일들은 참조용으로만 사용하고, 실제 의사결정은 중앙 지식 베이스를 참조.
    enforcement: "Reasoning Engine은 항상 knowledge/ 폴더의 내용을 우선 참조"

  principle_2_llm_first_design:
    name: "LLM 우선 설계 (LLM-First Design)"
    description: |
      모든 지식은 LLM이 효율적으로 파싱하고 이해할 수 있도록 구조화.
      - 명확한 섹션 구분 (Markdown 헤딩)
      - 일관된 포맷 (YAML frontmatter + Markdown body)
      - 컨텍스트가 포함된 자체 설명적(self-descriptive) 내용
    enforcement: "LLM 토큰 효율성 및 이해도 테스트 필수"

  principle_3_human_editable:
    name: "사람 편집 가능 (Human Editable)"
    description: |
      정책 및 전략은 도메인 전문가가 직접 편집할 수 있어야 함.
      복잡한 프로그래밍 없이 Markdown과 YAML로 관리.
    enforcement: "도메인 전문가 리뷰 및 승인 프로세스"

  principle_4_versioned_knowledge:
    name: "버전 관리된 지식 (Versioned Knowledge)"
    description: |
      모든 지식 변경은 Git을 통해 추적하고, 변경 이력을 유지.
      필요 시 이전 버전으로 롤백 가능.
    enforcement: "Git commit을 통한 변경 추적, changelog 유지"

  principle_5_rule_engine_integration:
    name: "규칙 엔진 통합 (Rule Engine Integration)"
    description: |
      명확한 규칙은 DSL로 정의하여 Rule Engine이 먼저 처리.
      모호한 케이스만 LLM에게 위임.
    enforcement: "Rule DSL 파서 및 평가기 구현 필수"
```

---

## 2. 중앙 지식 파일 구조

### 2.1 `의사결정_지식.md` 구조

이 파일은 **정책 및 전략**의 중앙 저장소로, Reasoning Engine이 의사결정 시 참조합니다.

#### 파일 템플릿

```markdown
---
# YAML Frontmatter
version: "1.5.0"
last_updated: "2025-10-29"
authors: ["교육팀", "AI팀"]
status: "active"
review_cycle: "monthly"
tags: ["policy", "strategy", "decision-making"]
---

# Mathking 의사결정 지식 베이스

> **목적**: AI 튜터가 학생 개입 시 참조하는 정책, 전략, 우선순위 정의
> **대상 독자**: Reasoning Engine (LLM + Rule Engine)
> **최종 갱신**: 2025-10-29

---

## 1. 핵심 원칙 (Core Principles)

### 1.1 학생 중심 의사결정

**원칙**: 모든 개입은 학생의 학습 성과 및 정서적 안녕을 최우선으로 고려합니다.

**적용 방법**:
- 학생의 현재 정서 상태(`affect`)를 항상 확인
- 인지 부하(`cognitive_load`)가 `high`이면 개입 강도 낮춤
- 페르소나 유사도에 따라 지시성 강도 조절

**예외 상황**:
- 긴급 상황(예: 시험 30분 전)에는 정서 상태보다 목표 달성 우선

---

### 1.2 증거 기반 의사결정

**원칙**: 모든 결정은 실제 학습 데이터(Evidence)에 근거해야 합니다.

**증거 필수 요소**:
- `metrics`: 정량적 학습 데이터 (진도율, 정답률, 재시도 등)
- `state`: 학생의 현재 상태 (정서, 집중도, 인지 부하)
- `context`: 상황 정보 (수업 시작/중반/종료, 주제, 난이도)

**증거 없는 추측 금지**:
- 데이터 없이 학생 상태를 가정하지 않음
- 불충분한 증거 시 추가 데이터 수집 권장

---

## 2. 개입 전략 (Intervention Strategies)

### 2.1 진도 미달 대응 전략

**트리거 조건**:
```yaml
condition: evidence.metrics.progress_delta <= -0.15
```

**전략 우선순위**:
1. **원인 분석 우선**: 진도 미달의 근본 원인 파악
   - 인지 부하 과다 → 난이도 조정
   - 시간 관리 문제 → 학습 일정 최적화
   - 동기 부족 → 목표 재설정 및 격려

2. **맞춤형 개입**: 학생 페르소나에 따라 개입 방식 조정
   - P_avoidant (회피형) → 부담 낮은 소규모 목표 제시
   - P_perfectionist (완벽주의형) → 완벽함보다 진도 강조
   - P_anxious (불안형) → 정서 안정 우선, 진도 회복은 단계적 접근

3. **단계적 회복**: 급격한 변화보다 점진적 개선
   - 1단계: 난이도 일시적 낮춤 (1주)
   - 2단계: 회복 속도 모니터링 후 난이도 복원 (2-3주)
   - 3단계: 정상 진도 복귀 확인 및 강화

**기대 효과**:
- 2주 내 `progress_delta >= -0.05` 달성
- 학생 정서 상태 안정 유지 (`affect: med` 이상)

---

### 2.2 시험 불안 관리 전략

**트리거 조건**:
```yaml
conditions:
  - evidence.state.affect == 'low'
  - evidence.context.days_until_exam <= 7
  - evidence.metrics.accuracy_rate < 0.6
```

**전략**:
1. **불안 원인 식별**:
   - 준비 부족 → 효율적 복습 계획 제공
   - 완벽주의 → 현실적 목표 설정 지원
   - 과거 실패 경험 → 긍정적 경험 강조

2. **단기 대응** (시험 7일 전~1일 전):
   - 핵심 주제 집중 복습 가이드
   - 모의고사 및 자신감 회복 활동
   - 호흡법, 마인드풀니스 등 불안 조절 기법 제공

3. **장기 대응** (시험 후):
   - 시험 결과 분석 및 학습 전략 재조정
   - 다음 시험 대비 장기 계획 수립

**주의사항**:
- 시험 30분 전에는 새로운 학습 권장하지 않음 (복습 및 정서 안정만)
- 과도한 압박 금지 (역효과 발생 가능)

---

## 3. 지시성 강도 조절 (Directive Strength Calibration)

### 3.1 기본 공식

```python
strength = base_strength + alpha * persona_similarity

where:
  base_strength: 규칙 기반 기본 강도 (0.5 ~ 0.9)
  alpha: 페르소나 영향 계수 (0.1 ~ 0.3)
  persona_similarity: 페르소나 유사도 (0.0 ~ 1.0)
```

### 3.2 강도 레벨 정의

| Strength | Level | Description | Tone |
|----------|-------|-------------|------|
| 0.9 - 1.0 | Very High | "지금 바로 시작하세요!" | 명령형 |
| 0.7 - 0.89 | High | "다음 활동을 우선 후보로 고려하세요" | 강한 권장 |
| 0.5 - 0.69 | Medium | "다음 활동을 추천드립니다" | 권장 |
| 0.3 - 0.49 | Low | "여유가 되면 고려해보세요" | 제안 |
| < 0.3 | Very Low | "참고용 정보입니다" | 정보 제공 |

### 3.3 상황별 조정 규칙

```yaml
strength_adjustments:

  urgent_deadline:
    condition: context.class_status == 'end_30min'
    adjustment: "+0.2"
    max_strength: 1.0

  high_anxiety:
    condition: state.affect == 'low'
    adjustment: "-0.2"
    min_strength: 0.3

  low_engagement:
    condition: metrics.response_time_avg > 180  # 3분 이상
    adjustment: "+0.15"

  high_cognitive_load:
    condition: state.cognitive_load == 'high'
    adjustment: "-0.25"
    min_strength: 0.2
```

---

## 4. 우선순위 결정 원칙 (Priority Decision Principles)

### 4.1 다중 개입 상황 시 우선순위

여러 에이전트가 동시에 개입 권장 시 우선순위 결정 기준:

```yaml
priority_factors:

  # Factor 1: Urgency (긴급도) - 30%
  urgency:
    weight: 0.30
    calculation: |
      if days_until_exam <= 3: urgency = 1.0
      elif days_until_exam <= 7: urgency = 0.7
      elif class_status == 'end_30min': urgency = 0.8
      else: urgency = 0.4

  # Factor 2: Severity (심각도) - 25%
  severity:
    weight: 0.25
    calculation: |
      if progress_delta <= -0.30: severity = 1.0
      elif progress_delta <= -0.15: severity = 0.7
      elif accuracy_rate < 0.5: severity = 0.8
      else: severity = 0.4

  # Factor 3: Impact Potential (효과 가능성) - 25%
  impact_potential:
    weight: 0.25
    calculation: |
      Based on historical success rate of similar interventions
      High success rate (>80%) → impact = 0.9
      Medium success rate (60-80%) → impact = 0.7
      Low success rate (<60%) → impact = 0.5

  # Factor 4: Student Receptiveness (학생 수용성) - 20%
  receptiveness:
    weight: 0.20
    calculation: |
      Based on persona affinity and current state
      High affinity + good state → receptiveness = 0.9
      Medium affinity + neutral state → receptiveness = 0.6
      Low affinity + poor state → receptiveness = 0.3

# Final Priority Score
priority_score = (urgency * 0.30) + (severity * 0.25) +
                 (impact_potential * 0.25) + (receptiveness * 0.20)
```

### 4.2 개입 상한선 (Intervention Limits)

```yaml
intervention_limits:

  max_per_session:
    description: "한 세션당 최대 개입 횟수"
    limit: 3
    reasoning: "과도한 개입은 학습 방해 및 피로 유발"

  min_interval:
    description: "개입 간 최소 시간 간격"
    limit: "15 minutes"
    reasoning: "학생이 이전 개입을 소화할 시간 필요"

  max_strength_in_session:
    description: "한 세션 내 최대 지시성 강도"
    limit: 0.85
    reasoning: "지나치게 강한 지시는 반발 초래"
    exception: "시험 30분 전에는 1.0까지 허용"
```

---

## 5. 안전 가드레일 (Safety Guardrails)

### 5.1 금지 액션 (Prohibited Actions)

```yaml
prohibited_actions:

  no_excessive_difficulty:
    rule: "난이도를 2단계 이상 올리지 않음"
    reasoning: "급격한 난이도 상승은 좌절감 유발"
    exception: "학생이 명시적으로 요청한 경우만 예외"

  no_overload:
    rule: "cognitive_load == 'high' 시 추가 학습 권장 금지"
    reasoning: "인지 과부하 상태에서 학습은 역효과"
    action: "대신 휴식 또는 쉬운 활동 권장"

  no_negative_feedback:
    rule: "부정적 피드백 단독 제공 금지"
    reasoning: "동기 저하 방지"
    action: "건설적 피드백 + 격려 메시지 조합 필수"

  no_night_intensive:
    rule: "밤 11시 이후 집중 학습 권장 금지"
    reasoning: "수면 방해 및 피로 누적"
    action: "가벼운 복습 또는 내일 계획 수립 권장"
```

### 5.2 롤백 정책 (Rollback Policy)

```yaml
rollback_conditions:

  negative_outcome:
    trigger: "개입 후 학습 성과 하락"
    measurement: |
      - accuracy_rate 하락 > 10%
      - completion_rate 하락 > 15%
      - affect: med → low 전환
    action: "이전 설정으로 복원 및 대체 전략 탐색"

  student_resistance:
    trigger: "학생의 명시적 거부 또는 회피"
    measurement: |
      - 권장 활동 무시 횟수 > 3
      - 학습 세션 조기 종료 빈도 증가
    action: "지시성 강도 낮춤 및 페르소나 재평가"

  unexpected_behavior:
    trigger: "예상치 못한 학습 패턴 출현"
    measurement: |
      - 평소와 다른 극단적 행동 패턴
      - 시스템 오작동 의심
    action: "자동 개입 일시 중단 및 사람 검토 요청"
```

---

## 6. LLM 추론 가이드 (LLM Reasoning Guide)

### 6.1 모호한 케이스 판단 기준

Rule Engine으로 해결할 수 없는 모호한 케이스를 LLM에게 위임할 때 사용하는 가이드:

```markdown
# LLM 추론 요청 프롬프트 템플릿

당신은 학생 맞춤형 AI 튜터의 의사결정 엔진입니다.
다음 증거(Evidence)를 바탕으로 학생에게 최적의 개입을 결정해주세요.

## 학생 정보
- **학생 ID**: {student_id}
- **페르소나 유형**: {persona_type}
- **페르소나 유사도**: {persona_similarity}

## 증거 (Evidence)
- **학습 메트릭**:
  - 진도율 변화: {progress_delta}
  - 정답률: {accuracy_rate}
  - 평균 응답 시간: {response_time_avg}
  - 재시도 횟수: {retry_count}
  - 완료율: {completion_rate}

- **학생 상태**:
  - 정서 상태: {affect}
  - 집중도: {focus}
  - 인지 부하: {cognitive_load}

- **상황 정보**:
  - 수업 상태: {class_status}
  - 주제: {topic}
  - 난이도: {difficulty_level}
  - 시험까지 남은 일수: {days_until_exam}

## 규칙 엔진 판단
- **트리거된 규칙**: {triggered_rules}
- **규칙 기반 제안**: {rule_suggestions}
- **모호성 원인**: {ambiguity_reason}

## 요청 사항
1. **원인 분석**: 현재 학생 상황의 근본 원인을 추론하세요.
2. **개입 전략**: 가장 효과적일 것으로 판단되는 개입 전략을 제안하세요.
3. **우선순위**: 여러 개입이 가능한 경우, 우선순위를 정하고 그 이유를 설명하세요.
4. **지시성 강도**: 개입의 강도(0.0~1.0)를 결정하고 근거를 제시하세요.
5. **예상 효과**: 이 개입으로 기대되는 학습 성과를 예측하세요.
6. **위험 요소**: 이 개입의 잠재적 부작용이나 위험을 평가하세요.

## 응답 형식
다음 JSON 형식으로 응답해주세요:

```json
{
  "analysis": {
    "root_cause": "원인 분석 (문장)",
    "contributing_factors": ["요인 1", "요인 2", "..."]
  },
  "recommendation": {
    "primary_intervention": "주요 개입 (문장)",
    "supporting_interventions": ["보조 개입 1", "..."],
    "priority_ranking": [
      {"intervention": "...", "priority": 0.9, "reason": "..."},
      {"intervention": "...", "priority": 0.7, "reason": "..."}
    ]
  },
  "directive_strength": {
    "value": 0.75,
    "reasoning": "강도 결정 근거"
  },
  "expected_outcomes": {
    "short_term": ["1주 내 예상 결과"],
    "long_term": ["1개월 내 예상 결과"]
  },
  "risks": {
    "potential_issues": ["잠재적 문제점"],
    "mitigation": ["위험 완화 방안"]
  },
  "confidence_score": 0.85
}
```
```

### 6.2 LLM 응답 검증 기준

```yaml
llm_response_validation:

  required_fields:
    - analysis.root_cause
    - recommendation.primary_intervention
    - directive_strength.value
    - confidence_score

  confidence_threshold:
    minimum: 0.70
    action_if_below: "규칙 엔진 기본 제안 사용 또는 사람 검토 요청"

  consistency_check:
    directive_strength_range: [0.0, 1.0]
    priority_ranking_sum: "각 priority 값은 0.0~1.0 범위"
    reasoning_length: "최소 50자 이상"

  safety_check:
    prohibited_actions_in_recommendation: "안전 가드레일 위반 시 거부"
    extreme_values: "지나치게 극단적 값(>0.95 또는 <0.05) 재검토"
```

---

## 7. 지식 버전 관리 (Knowledge Versioning)

### 7.1 버전 관리 정책

```yaml
versioning_policy:

  semantic_versioning:
    format: "MAJOR.MINOR.PATCH"
    rules:
      - MAJOR: "정책의 근본적 변경 (기존 규칙 삭제 또는 대체)"
      - MINOR: "새로운 정책 추가 (기존 규칙 유지)"
      - PATCH: "설명 개선, 오타 수정, 명확화"

  review_cycle:
    frequency: "monthly"
    process:
      - "교육팀 및 AI팀 합동 리뷰"
      - "실제 운영 데이터 기반 정책 효과 평가"
      - "필요 시 정책 업데이트 및 버전 상승"

  change_log:
    file: "version_history/changelog.md"
    format: |
      # Changelog

      ## [1.5.0] - 2025-10-29
      ### Added
      - 시험 불안 관리 전략 추가

      ### Changed
      - 지시성 강도 조절 공식 개선 (alpha 계수 조정)

      ### Fixed
      - 진도 미달 대응 전략에서 페르소나 고려 누락 수정

      ## [1.4.0] - 2025-09-15
      ...
```

### 7.2 아카이빙 정책

```yaml
archiving_policy:

  archive_trigger:
    condition: "MAJOR 버전 변경 시"
    action: "이전 버전 전체를 version_history/archived/ 폴더로 이동"

  archived_version_retention:
    duration: "최소 2년 보관"
    reasoning: "과거 의사결정 추적 및 감사 목적"

  rollback_process:
    trigger: "심각한 정책 오류 발견 시"
    steps:
      1. "아카이브에서 이전 안정 버전 복원"
      2. "현재 버전을 rollback/ 폴더로 이동"
      3. "긴급 패치 버전 발행 (예: 1.5.0 → 1.5.1)"
```

---

## 8. 규칙 DSL 설계 (Rule DSL Design)

### 8.1 DSL 기본 구조

```yaml
# rule_catalog.yaml

# Condition 정의 (조건식)
Condition:
  cond.progress.below_avg15:
    expression: "evidence.metrics.progress_delta <= -0.15"
    description: "진도율이 평균보다 15% 이상 낮음"

  cond.affect.low:
    expression: "evidence.state.affect == 'low'"
    description: "정서 상태가 낮음 (불안, 좌절 등)"

  cond.cognitive.overload:
    expression: "evidence.state.cognitive_load == 'high'"
    description: "인지 부하가 높음"

# Rule 정의 (규칙)
Rule:
  rule.curriculum.lagging_report:
    id: "rule_001"
    name: "진도 미달 리포트 생성"
    when: "cond.progress.below_avg15"
    then:
      suggest:
        - template: "report_lagging"
          params:
            chart: "progress_vs_avg"
            severity: "medium"
      priority: 0.80

  rule.emotion.anxiety_regulation:
    id: "rule_002"
    name: "불안 조절 전략 제공"
    when: "cond.affect.low AND cond.cognitive.overload"
    then:
      suggest:
        - template: "directive_emotion_regulation"
          params:
            strategy: "breathing_exercise"
            duration: "5 minutes"
      priority: 0.90

  rule.adaptive.difficulty_down:
    id: "rule_003"
    name: "난이도 하향 조정"
    when: "cond.progress.below_avg15 AND cond.cognitive.overload"
    then:
      action:
        - action_type: "adjust_difficulty"
          params:
            direction: "down"
            step: 1
      priority: 0.85
```

### 8.2 DSL 파서 요구사항

```python
# Pseudocode: Rule DSL Parser

class RuleDSLParser:
    """
    Rule DSL을 파싱하여 실행 가능한 객체로 변환
    """

    def parse_conditions(self, conditions_yaml: dict) -> Dict[str, Condition]:
        """
        Condition 정의를 파싱하여 평가 가능한 객체로 변환
        """
        conditions = {}

        for cond_id, cond_def in conditions_yaml.items():
            expression = cond_def['expression']
            description = cond_def['description']

            # 표현식을 Python AST로 파싱
            condition_ast = ast.parse(expression, mode='eval')

            conditions[cond_id] = Condition(
                id=cond_id,
                expression=expression,
                ast=condition_ast,
                description=description
            )

        return conditions

    def parse_rules(self, rules_yaml: dict, conditions: Dict[str, Condition]) -> List[Rule]:
        """
        Rule 정의를 파싱하여 실행 가능한 규칙 객체로 변환
        """
        rules = []

        for rule_id, rule_def in rules_yaml.items():
            when_clause = rule_def['when']
            then_clause = rule_def['then']

            # when 절 파싱 (조건식)
            condition_ast = self.parse_condition_expression(when_clause, conditions)

            # then 절 파싱 (액션 또는 제안)
            actions = []
            if 'suggest' in then_clause:
                for suggestion in then_clause['suggest']:
                    actions.append(SuggestAction(
                        template=suggestion['template'],
                        params=suggestion.get('params', {})
                    ))

            if 'action' in then_clause:
                for action_def in then_clause['action']:
                    actions.append(ExecuteAction(
                        action_type=action_def['action_type'],
                        params=action_def.get('params', {})
                    ))

            rule = Rule(
                id=rule_def['id'],
                name=rule_def['name'],
                condition=condition_ast,
                actions=actions,
                priority=rule_def.get('priority', 0.5)
            )

            rules.append(rule)

        return rules

    def parse_condition_expression(self, expression: str, conditions: Dict[str, Condition]) -> ConditionAST:
        """
        조건식 파싱 (AND, OR, NOT 지원)
        """
        # 예: "cond.progress.below_avg15 AND cond.cognitive.overload"
        tokens = expression.split()

        # 간단한 파서 구현 (실제로는 더 정교한 파서 필요)
        # 여기서는 개념만 보여줌

        if 'AND' in tokens:
            left = tokens[0]
            right = tokens[2]
            return AndCondition(conditions[left], conditions[right])

        elif 'OR' in tokens:
            left = tokens[0]
            right = tokens[2]
            return OrCondition(conditions[left], conditions[right])

        elif 'NOT' in tokens:
            operand = tokens[1]
            return NotCondition(conditions[operand])

        else:
            # 단일 조건
            return conditions[tokens[0]]
```

---

## 9. 지식 베이스 최적화 전략

### 9.1 LLM 토큰 효율성

```yaml
token_efficiency_strategies:

  strategy_1_chunking:
    name: "컨텍스트 청킹 (Context Chunking)"
    description: |
      전체 지식 베이스를 LLM에게 한 번에 전달하지 않고,
      현재 상황과 관련된 섹션만 선택적으로 로드.
    implementation:
      - "Evidence 카테고리 기반 섹션 매칭"
      - "트리거된 규칙 관련 정책만 로드"
      - "최대 컨텍스트 크기: 4000 토큰"

  strategy_2_caching:
    name: "프롬프트 캐싱 (Prompt Caching)"
    description: |
      자주 사용되는 정책 섹션은 LLM 프롬프트 캐시에 저장하여 재사용.
    implementation:
      - "핵심 원칙, 지시성 강도 공식 등은 캐시 대상"
      - "세션 내 캐시 재사용으로 API 비용 절감"

  strategy_3_summarization:
    name: "동적 요약 (Dynamic Summarization)"
    description: |
      긴 정책 설명은 상황에 따라 요약하여 제공.
    implementation:
      - "긴급 상황 시: 핵심 액션만 요약 전달"
      - "비긴급 상황 시: 상세 설명 포함"
```

### 9.2 지식 업데이트 전략

```yaml
knowledge_update_strategies:

  strategy_1_feedback_loop:
    name: "피드백 루프 (Feedback Loop)"
    description: |
      실제 운영 결과를 바탕으로 정책 효과 평가 및 개선.
    implementation:
      - "주간 리뷰: 주요 지표 추적 (미션 성공률, 학생 성과 개선도)"
      - "월간 리뷰: 정책 효과 분석 및 업데이트 제안"
      - "분기 리뷰: 주요 정책 변경 및 MAJOR 버전 업데이트"

  strategy_2_ab_testing:
    name: "A/B 테스팅 (A/B Testing)"
    description: |
      새로운 정책은 일부 학생 그룹에만 먼저 적용하여 효과 검증.
    implementation:
      - "테스트 그룹: 20% 학생"
      - "대조 그룹: 80% 학생 (기존 정책 유지)"
      - "2주 후 결과 비교 및 전체 적용 여부 결정"

  strategy_3_expert_review:
    name: "전문가 검토 (Expert Review)"
    description: |
      교육 전문가 및 AI 전문가의 정기 검토를 통한 품질 보증.
    implementation:
      - "교육팀: 교육학적 타당성 검증"
      - "AI팀: LLM 프롬프트 최적화 및 토큰 효율성 검증"
      - "승인 프로세스: 2명 이상 승인 후 배포"
```

---

## 10. 사용 예시 (Usage Examples)

### 예시 1: 규칙 기반 의사결정

**상황**: 학생의 진도가 평균보다 18% 낮음

**증거 패키지**:
```yaml
evidence:
  metrics:
    progress_delta: -0.18
    accuracy_rate: 0.62
  state:
    affect: "med"
    cognitive_load: "medium"
  context:
    class_status: "mid"
```

**규칙 엔진 처리**:
1. 조건 평가: `cond.progress.below_avg15` → TRUE
2. 매칭 규칙: `rule.curriculum.lagging_report`
3. 액션: `suggest report_lagging with params {chart: progress_vs_avg, severity: medium}`
4. 우선순위: 0.80

**결과**: LLM 호출 없이 규칙 엔진만으로 처리 완료 (빠르고 비용 효율적)

---

### 예시 2: LLM 추론 필요 케이스

**상황**: 학생이 시험 3일 전인데, 진도도 낮고 불안 수준도 높음

**증거 패키지**:
```yaml
evidence:
  metrics:
    progress_delta: -0.20
    accuracy_rate: 0.55
  state:
    affect: "low"
    cognitive_load: "high"
  context:
    class_status: "mid"
    days_until_exam: 3
```

**규칙 엔진 처리**:
1. 여러 규칙 동시 트리거:
   - `rule.curriculum.lagging_report` (priority 0.80)
   - `rule.emotion.anxiety_regulation` (priority 0.90)
   - `rule.exam_prep.urgent_prep` (priority 0.95)
2. **모호성 발생**: 3개 규칙 중 어느 것을 우선할지 불명확
3. → LLM에게 위임

**LLM 프롬프트 구성**:
```markdown
# (위의 템플릿 사용)
...
## 규칙 엔진 판단
- 트리거된 규칙: rule_001, rule_002, rule_003
- 규칙 기반 제안:
  1. 진도 미달 리포트 생성 (0.80)
  2. 불안 조절 전략 제공 (0.90)
  3. 긴급 시험 준비 가이드 (0.95)
- 모호성 원인: "3개 규칙 모두 트리거되었으나, 상충 가능성 있음. 시험 준비 우선 vs. 정서 안정 우선?"
...
```

**LLM 응답** (예상):
```json
{
  "analysis": {
    "root_cause": "시험 압박과 진도 미달이 복합적으로 작용하여 불안 증가",
    "contributing_factors": ["준비 시간 부족", "인지 과부하", "자신감 하락"]
  },
  "recommendation": {
    "primary_intervention": "긴급 시험 준비 + 불안 조절 통합 전략",
    "supporting_interventions": ["핵심 주제 집중 복습", "짧은 호흡 운동"],
    "priority_ranking": [
      {
        "intervention": "핵심 주제 집중 복습 (3일 계획)",
        "priority": 0.95,
        "reason": "시험 임박으로 실질적 준비 최우선"
      },
      {
        "intervention": "불안 조절 기법 (호흡 운동)",
        "priority": 0.85,
        "reason": "정서 안정 없이는 효과적 학습 불가"
      }
    ]
  },
  "directive_strength": {
    "value": 0.90,
    "reasoning": "시험 3일 전이라 긴급성 높음, 하지만 불안 상태 고려하여 명령형 톤은 피함"
  },
  "expected_outcomes": {
    "short_term": ["불안 수준 완화 (3일 내)", "핵심 주제 이해도 향상"],
    "long_term": ["시험 성적 개선", "자신감 회복"]
  },
  "risks": {
    "potential_issues": ["3일로는 진도 완전 회복 어려움", "과도한 압박 시 불안 악화"],
    "mitigation": ["현실적 목표 설정 (완벽함보다 핵심)", "휴식 시간 확보"]
  },
  "confidence_score": 0.82
}
```

**최종 지시문 생성**: LLM 응답을 바탕으로 Directive Package 구성

---

## 11. 지식 베이스 품질 관리

### 11.1 품질 메트릭

```yaml
quality_metrics:

  metric_1_completeness:
    name: "완전성 (Completeness)"
    description: "모든 필수 정책 및 전략이 문서화되어 있는가?"
    measurement: "문서화된 정책 수 / 요구되는 정책 수"
    target: "> 95%"

  metric_2_consistency:
    name: "일관성 (Consistency)"
    description: "정책 간 충돌이나 모순이 없는가?"
    measurement: "자동 일관성 검사 + 전문가 리뷰"
    target: "0 충돌"

  metric_3_clarity:
    name: "명확성 (Clarity)"
    description: "LLM이 정책을 정확히 이해하고 적용할 수 있는가?"
    measurement: "LLM 이해도 테스트 점수"
    target: "> 90%"

  metric_4_effectiveness:
    name: "효과성 (Effectiveness)"
    description: "정책이 실제로 학생 성과 개선에 기여하는가?"
    measurement: "정책 적용 후 학생 성과 개선도"
    target: "> 15% 개선"

  metric_5_token_efficiency:
    name: "토큰 효율성 (Token Efficiency)"
    description: "LLM 컨텍스트 사용량이 최적화되어 있는가?"
    measurement: "평균 토큰 사용량 / 의사결정"
    target: "< 3000 tokens"
```

### 11.2 품질 보증 프로세스

```yaml
quality_assurance_process:

  step_1_automated_checks:
    description: "자동화된 검증"
    tools:
      - "YAML 파서: 구문 오류 검출"
      - "일관성 검사기: 규칙 간 충돌 검출"
      - "토큰 카운터: 컨텍스트 크기 모니터링"

  step_2_llm_testing:
    description: "LLM 이해도 테스트"
    process:
      - "샘플 증거 패키지로 LLM 추론 실행"
      - "LLM 응답의 적절성 평가"
      - "개선 필요 사항 식별"

  step_3_expert_review:
    description: "전문가 검토"
    reviewers:
      - "교육 전문가: 교육학적 타당성"
      - "AI 전문가: LLM 최적화"
    frequency: "월 1회"

  step_4_production_monitoring:
    description: "프로덕션 모니터링"
    metrics:
      - "정책 적용 성공률"
      - "학생 성과 개선도"
      - "LLM API 비용"
    alerts:
      - "성공률 < 70% 시 알림"
      - "비용 > 예산 120% 시 알림"
```

---

## 12. 다음 단계

이 문서는 **03-KNOWLEDGE_BASE_ARCHITECTURE.md**로서, LLM 최적화된 지식 베이스 구조를 설계했습니다.

**다음 문서**:
- `04-ONTOLOGY_SYSTEM_DESIGN.md`: 다층 온톨로지 시스템 상세 명세
- `05-REASONING_ENGINE_SPEC.md`: 규칙 엔진 + LLM 추론 엔진 명세
- `06-INTEGRATION_ARCHITECTURE.md`: 전체 시스템 통합 아키텍처
- `07-IMPLEMENTATION_ROADMAP.md`: 단계별 구현 로드맵

---

**문서 끝**
