# 에이전트 생성 템플릿 가이드

21개 에이전트를 일관성 있게 생성하기 위한 템플릿 및 가이드

---

## 에이전트 생성 체크리스트

### 1. 폴더 구조 생성

```bash
mkdir -p agents/agent_[name]/{tasks,prompts,tests,logs}
```

### 2. 필수 파일 생성

- [ ] `config.yaml` - 에이전트 설정
- [ ] `tasks/task_*.yaml` - 태스크 정의 (최소 1개)
- [ ] `prompts/report_*.md` - 리포트 템플릿
- [ ] `prompts/directive_*.md` - 지시문 템플릿
- [ ] `tests/fixtures.json` - 테스트 데이터

### 3. 레지스트리 등록

`agents/registry.yaml`에 추가:

```yaml
agent_[name]:
  id: "agent_[name]"
  name: "[한글명] 에이전트"
  category: "[core|support]"
  description: "[설명]"
  status: "active"
  heartbeat_min: 30
  priority: [1-4]
  contexts: ["[context]"]
```

### 4. 온톨로지 등록

`ontology/ontology.jsonld`에 추가:

```json
{
  "@id": "mk:Agent/agent_[name]",
  "@type": "mk:Agent",
  "rdfs:label": "[한글명] 에이전트",
  "mk:context": "mk:Context/[context]",
  "mk:hasTask": ["mk:Task/task_[name]"],
  "mk:priority": 1,
  "mk:heartbeat_min": 30
}
```

---

## 템플릿: config.yaml

```yaml
# Agent [Name] Configuration

agent_id: "agent_[name]"
version: "1.0.0"

metadata:
  name: "[한글명] 에이전트"
  category: "[core|support]"
  description: "[상세 설명]"
  status: "active"
  priority: [1-4]

heartbeat:
  interval_min: [15|30|60|120|1440]
  enabled: true
  triggers:
    - "[trigger_event_1]"
    - "[trigger_event_2]"

permissions:
  can_generate_reports: true
  can_generate_directives: true
  can_modify_curriculum: false
  max_directive_strength: [0.5-1.0]

triggers:
  - id: "trigger_[name]"
    condition: "[DSL expression]"
    priority: [0.0-1.0]

tasks:
  - task_[name]_1
  - task_[name]_2

context_tags:
  - "[context]"
  - "[domain]"

persona_affinity:
  high: ["P_[type]"]
  medium: ["P_[type]"]
  low: ["P_[type]"]

kpis:
  - "[metric_1]"
  - "[metric_2]"

reporting:
  default_lookback_days: 7
  include_charts: true
  chart_types:
    - "[chart_type]"

safety:
  max_[constraint]: [value]
  min_[constraint]: [value]
```

---

## 템플릿: tasks/task_[name].yaml

```yaml
# Task: [설명]

id: "task_[name]"
version: "1.0.0"

goal: "[태스크 목표 설명]"

kpi:
  - "[metric_1]"
  - "[metric_2]"

triggers:
  - "cond.[condition_1]"
  - "cond.[condition_2]"

preconditions:
  - "[precondition_1]"
  - "[precondition_2]"

postconditions:
  - "[postcondition_1]"
  - "[postcondition_2]"

context_tags:
  - "[context]"
  - "[domain]"

templates:
  - id: "report_[name]"
    type: "report"
    priority: 1.0
    params:
      [param_1]: [value]
      [param_2]: [value]

  - id: "directive_[name]"
    type: "directive"
    priority: 0.9
    params:
      [param_1]: [value]

rules:
  - "rule.[agent].[task].[action]"

persona_relevance:
  P_[type]: [0.0-1.0]

expected_outcomes:
  - "[outcome_1]"
  - "[outcome_2]"

metadata:
  created: "YYYY-MM-DD"
  author: "system"
  tags: ["[tag1]", "[tag2]"]
```

---

## 템플릿: prompts/report_[name].md

```markdown
# [리포트 제목] ({{date}})

## 현황

- **[지표명]**: {{metric_value}}
- **[상태]**: {% if condition %}[메시지]{% endif %}

## 분석

{% if condition_1 -%}
⚠️ **주의**: [분석 내용]
{%- elif condition_2 -%}
ℹ️ [분석 내용]
{%- else -%}
✅ [분석 내용]
{%- endif %}

## 제안

{% if strength >= 0.8 -%}
**지금 바로 다음 활동을 시작하세요:**
{%- elif strength >= 0.6 -%}
**다음 활동을 우선 후보로 고려하세요:**
{%- else -%}
다음 활동을 참고해보세요:
{%- endif %}

- 📚 **{{activity_name}}**
- ⏱️ 예상 소요: **{{minutes}}분**
- 🎯 목표: {{goal}}

{% if chart_ref %}
## 그래프

![차트]({{chart_ref}})
{% endif %}

---
*생성 시각: {{timestamp}}*
*신뢰도: {{confidence|round(2)}}*
```

---

## 템플릿: prompts/directive_[name].md

```markdown
# [지시문 제목]

{% if strength >= 0.8 -%}
## 🎯 지금 바로 시작하세요!

다음 **{{minutes}}분** 동안 **{{activity_name}}** 활동에 집중해주세요.
{%- elif strength >= 0.6 -%}
## 📋 우선 추천 활동

다음 활동을 우선 후보로 고려해보세요:
- **{{activity_name}}**
- 예상 소요: 약 **{{minutes}}분**
{%- else -%}
## 💡 참고 제안

여유가 되면 다음 활동을 고려해보세요:
- {{activity_name}} ({{minutes}}분 정도)
{%- endif %}

## 세부 내용

- **[필드명]**: {{value}}
- **난이도**: {% if difficulty == 'easy' %}쉬움 🟢{% elif difficulty == 'medium' %}보통 🟡{% else %}어려움 🔴{% endif %}
- **목표**: {{goal_description}}

{% if strength >= 0.7 %}
## ⏰ 타이밍

{% if mode == 'now' %}
**지금 시작**하는 것이 가장 효과적입니다.
{% elif mode == 'next' %}
**다음 시간**에 우선적으로 진행하세요.
{% else %}
**지금 또는 다음 시간** 중 선택하세요.
{% endif %}
{% endif %}

## 기대 효과

{% for outcome in expected_outcomes %}
- {{ outcome }}
{% endfor %}

---
*강도: {{strength|round(2)}} | 우선순위: {{priority|round(2)}}*
*생성: {{timestamp}}*
```

---

## 9개 핵심 에이전트 빠른 참조

### 1. agent_curriculum (커리큘럼)
- **컨텍스트**: curriculum
- **주요 태스크**: task_lagging, task_overfocus, task_balanced_progress
- **KPI**: progress_rate, time_on_task, completion_ratio
- **Heartbeat**: 30분

### 2. agent_exam_prep (시험대비)
- **컨텍스트**: exam_prep
- **주요 태스크**: task_exam_strategy, task_weak_area, task_mock_test
- **KPI**: score_improvement, weak_area_coverage, confidence_level
- **Heartbeat**: 60분

### 3. agent_adaptive (맞춤학습)
- **컨텍스트**: adaptive
- **주요 태스크**: task_difficulty_adjust, task_learning_pace, task_content_match
- **KPI**: difficulty_fit, correct_rate_optimal, engagement_level
- **Heartbeat**: 30분

### 4. agent_micro_mission (마이크로미션)
- **컨텍스트**: micro_mission
- **주요 태스크**: task_daily_goal, task_mini_challenge, task_quick_win
- **KPI**: goal_completion_rate, streak_days, motivation_boost
- **Heartbeat**: 15분

### 5. agent_self_reflection (자기성찰)
- **컨텍스트**: self_reflection
- **주요 태스크**: task_learning_review, task_mistake_analysis, task_growth_tracking
- **KPI**: reflection_frequency, insight_quality, improvement_rate
- **Heartbeat**: 45분

### 6. agent_self_directed (자기주도학습)
- **컨텍스트**: self_directed
- **주요 태스크**: task_plan_creation, task_resource_selection, task_progress_monitor
- **KPI**: autonomy_level, plan_adherence, resource_efficiency
- **Heartbeat**: 60분

### 7. agent_apprenticeship (도제학습)
- **컨텍스트**: apprenticeship
- **주요 태스크**: task_mentor_matching, task_modeling, task_guided_practice
- **KPI**: mentor_interaction, skill_transfer, mastery_level
- **Heartbeat**: 90분

### 8. agent_time_reflection (시간성찰)
- **컨텍스트**: time_reflection
- **주요 태스크**: task_time_analysis, task_pattern_detect, task_efficiency_boost
- **KPI**: time_efficiency, pattern_consistency, waste_reduction
- **Heartbeat**: 120분

### 9. agent_inquiry (탐구학습)
- **컨텍스트**: inquiry
- **주요 태스크**: task_question_generation, task_exploration, task_discovery
- **KPI**: question_quality, exploration_depth, discovery_count
- **Heartbeat**: 45분

---

## 12개 보조 에이전트 빠른 참조

### 10. agent_emotion (감정관리)
- **컨텍스트**: emotion, adaptive
- **Heartbeat**: 20분

### 11. agent_motivation (동기부여)
- **컨텍스트**: motivation, micro_mission
- **Heartbeat**: 30분

### 12. agent_personality (성격유형)
- **컨텍스트**: personality, adaptive
- **Heartbeat**: 1440분 (1일)

### 13. agent_learning_style (학습스타일)
- **컨텍스트**: learning_style, adaptive
- **Heartbeat**: 720분 (12시간)

### 14. agent_cognitive (인지능력)
- **컨텍스트**: cognitive, adaptive
- **Heartbeat**: 30분

### 15. agent_social (사회적학습)
- **컨텍스트**: social, apprenticeship
- **Heartbeat**: 120분

### 16. agent_habit (학습습관)
- **컨텍스트**: habit, self_directed
- **Heartbeat**: 1440분 (1일)

### 17. agent_time_management (시간관리)
- **컨텍스트**: time_management, curriculum
- **Heartbeat**: 60분

### 18. agent_feedback (피드백)
- **컨텍스트**: feedback, exam_prep
- **Heartbeat**: 30분

### 19. agent_goal_setting (목표설정)
- **컨텍스트**: goal_setting, self_directed
- **Heartbeat**: 1440분 (1일)

### 20. agent_metacognition (메타인지)
- **컨텍스트**: metacognition, self_reflection
- **Heartbeat**: 60분

### 21. agent_creativity (창의성)
- **컨텍스트**: creativity, inquiry
- **Heartbeat**: 120분

---

## 일괄 생성 스크립트

```bash
#!/bin/bash
# generate_all_agents.sh

AGENTS=(
  "exam_prep:시험대비:exam_prep:60"
  "adaptive:맞춤학습:adaptive:30"
  "micro_mission:마이크로미션:micro_mission:15"
  "self_reflection:자기성찰:self_reflection:45"
  "self_directed:자기주도학습:self_directed:60"
  "apprenticeship:도제학습:apprenticeship:90"
  "time_reflection:시간성찰:time_reflection:120"
  "inquiry:탐구학습:inquiry:45"
  "emotion:감정관리:emotion:20"
  "motivation:동기부여:motivation:30"
  "personality:성격유형:personality:1440"
  "learning_style:학습스타일:learning_style:720"
  "cognitive:인지능력:cognitive:30"
  "social:사회적학습:social:120"
  "habit:학습습관:habit:1440"
  "time_management:시간관리:time_management:60"
  "feedback:피드백:feedback:30"
  "goal_setting:목표설정:goal_setting:1440"
  "metacognition:메타인지:metacognition:60"
  "creativity:창의성:creativity:120"
)

for agent_spec in "${AGENTS[@]}"; do
  IFS=':' read -r id name context heartbeat <<< "$agent_spec"

  echo "Creating agent_$id..."

  # config.yaml 생성
  cat > "agents/agent_$id/config.yaml" <<EOF
agent_id: "agent_$id"
version: "1.0.0"

metadata:
  name: "$name 에이전트"
  category: "core"
  description: "$name 관련 의사결정"
  status: "active"
  priority: 2

heartbeat:
  interval_min: $heartbeat
  enabled: true

permissions:
  can_generate_reports: true
  can_generate_directives: true

tasks: []
context_tags: ["$context"]
kpis: []
EOF

  echo "Created agent_$id"
done

echo "All agents created!"
```

---

## 검증 체크리스트

생성 후 반드시 확인:

- [ ] registry.yaml에 등록되었는가?
- [ ] ontology.jsonld에 추가되었는가?
- [ ] config.yaml이 유효한가?
- [ ] 최소 1개 이상의 task가 있는가?
- [ ] 리포트/지시문 템플릿이 있는가?
- [ ] Heartbeat 주기가 합리적인가?
- [ ] 컨텍스트 태그가 올바른가?
- [ ] 페르소나 연관도가 정의되었는가?

---

**템플릿 가이드 끝**
