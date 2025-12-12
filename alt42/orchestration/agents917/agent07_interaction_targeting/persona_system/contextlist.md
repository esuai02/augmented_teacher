# Agent07 Persona System - Context Parameters

## Overview
페르소나 식별에 사용되는 컨텍스트 파라미터 정의서입니다.
rules.yaml의 조건 평가에 필요한 모든 변수를 정의합니다.

**Version**: 1.0
**Last Updated**: 2025-12-02
**Agent**: agent07_interaction_targeting

---

## Context Categories

| Category | Description | Parameter Count |
|----------|-------------|-----------------|
| activity | 현재 활동 상태 | 8 |
| temporal | 시간 관련 정보 | 6 |
| behavioral | 행동 지표 | 15 |
| emotional | 정서 상태 | 8 |
| message | 메시지 분석 | 5 |
| performance | 학습 수행 | 10 |

---

## Activity Parameters (활동 상태)

### context.current_activity
- **Type**: string (enum)
- **Values**: `learning`, `preparation`, `goal_setting`, `reflection`, `curriculum_planning`, `break`, `idle`
- **Description**: 현재 학습자의 활동 유형
- **Source**: UI state, session tracking
- **Default**: `idle`

### context.pomodoro_active
- **Type**: boolean
- **Description**: 포모도로 세션 활성화 여부
- **Source**: Pomodoro timer state
- **Default**: false

### context.session_type
- **Type**: string (enum)
- **Values**: `focus`, `break`, `planning`, `review`
- **Description**: 현재 세션 유형
- **Source**: Session controller

### context.help_button_clicked
- **Type**: boolean
- **Description**: 도움 버튼 클릭 여부
- **Source**: UI event
- **Default**: false

### context.preview_activity
- **Type**: boolean
- **Description**: 예습 자료 열람 중 여부
- **Source**: Content access log

### context.viewing_roadmap
- **Type**: boolean
- **Description**: 로드맵/커리큘럼 페이지 열람 중 여부
- **Source**: Page tracking

### context.viewing_preview_material
- **Type**: boolean
- **Description**: 수업 예습 자료 열람 중 여부
- **Source**: Content access log

### context.material_access_count
- **Type**: integer
- **Description**: 자료 접근 횟수 (현재 세션)
- **Range**: 0-100
- **Source**: Access log

---

## Temporal Parameters (시간 정보)

### context.time_to_class
- **Type**: integer (minutes)
- **Description**: 다음 수업까지 남은 시간 (분)
- **Range**: 0-1440
- **Source**: Schedule system

### context.stuck_duration
- **Type**: integer (seconds)
- **Description**: 동일 문제에 머문 시간 (초)
- **Range**: 0-3600
- **Source**: Activity tracker

### context.idle_time
- **Type**: integer (seconds)
- **Description**: 비활동 시간 (초)
- **Range**: 0-3600
- **Source**: Input monitor

### context.no_activity_duration
- **Type**: integer (seconds)
- **Description**: 의미있는 활동 없는 시간
- **Range**: 0-3600
- **Source**: Activity tracker

### context.session_start
- **Type**: boolean
- **Description**: 세션 시작 직후 여부 (5분 이내)
- **Source**: Session controller

### context.session_ending
- **Type**: boolean
- **Description**: 세션 종료 단계 여부
- **Source**: Session controller

---

## Behavioral Parameters (행동 지표)

### context.help_request_explicit
- **Type**: boolean
- **Description**: 명시적 도움 요청 여부
- **Source**: Message analysis

### context.problem_articulation
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 문제 표현 명확도 점수
- **Calculation**: NLP analysis of problem description clarity

### context.urgency_expressed
- **Type**: boolean
- **Description**: 긴급함 표현 여부
- **Source**: Message sentiment analysis

### context.confusion_signals
- **Type**: boolean
- **Description**: 혼란 신호 감지 여부
- **Indicators**: 짧은 응답, "음", "글쎄" 등

### context.interaction_frequency_decreasing
- **Type**: boolean
- **Description**: 상호작용 빈도 감소 추세
- **Source**: Interaction log trend analysis

### context.question_type
- **Type**: string (enum)
- **Values**: `what`, `how`, `why`, `when`, `where`, `yes_no`, `choice`
- **Description**: 질문 유형 분류

### context.self_discovery_preference
- **Type**: boolean
- **Description**: 스스로 발견하려는 성향
- **Source**: Historical preference + current signals

### context.distraction_signals
- **Type**: boolean
- **Description**: 주의 분산 신호
- **Indicators**: 탭 전환, 비학습 사이트 접속

### context.task_switching_frequency
- **Type**: integer
- **Description**: 과제 전환 빈도 (분당)
- **Range**: 0-20

### context.waiting_passively
- **Type**: boolean
- **Description**: 수동적 대기 상태 여부

### context.reassurance_seeking_count
- **Type**: integer
- **Description**: 안심 요청 횟수 (현재 세션)
- **Range**: 0-20

### context.question_preparation
- **Type**: boolean
- **Description**: 질문 준비 여부 (메모 등)

### context.early_termination_request
- **Type**: boolean
- **Description**: 조기 종료 요청 여부

### context.reflection_avoidance
- **Type**: boolean
- **Description**: 회고 회피 행동

### context.option_exploration
- **Type**: boolean
- **Description**: 여러 옵션 탐색 중 여부

---

## Emotional Parameters (정서 상태)

### context.anxiety_signals
- **Type**: boolean
- **Description**: 불안 신호 감지
- **Source**: Message sentiment + behavioral indicators

### context.confidence_score
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 자신감 수준 점수

### context.motivation_score
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 동기 수준 점수

### context.meaning_questioning
- **Type**: boolean
- **Description**: 의미 의문 표현 ("왜 해야 해?")

### context.negative_self_talk
- **Type**: boolean
- **Description**: 부정적 자기 대화 감지

### context.self_criticism_score
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 자기 비판 수준

### context.achievement_dismissal
- **Type**: boolean
- **Description**: 성취 과소평가 경향

### context.reflection_willingness
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 회고 의지 수준

---

## Message Parameters (메시지 분석)

### context.message_length
- **Type**: integer
- **Description**: 메시지 길이 (문자 수)
- **Range**: 0-5000

### context.response_length
- **Type**: integer
- **Description**: 응답 길이 (최근 응답)
- **Range**: 0-5000

### context.message_contains_keywords
- **Type**: function(keywords: array) → boolean
- **Description**: 특정 키워드 포함 여부 확인
- **Usage**: `context.message_contains_keywords: ["도와", "모르겠"]`

### context.goal_statement_length
- **Type**: integer
- **Description**: 목표 진술 길이
- **Range**: 0-500

### context.expression_vague
- **Type**: boolean
- **Description**: 모호한 표현 사용 여부

---

## Performance Parameters (학습 수행)

### context.focus_score
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 집중도 점수
- **Calculation**: Composite of activity patterns

### context.task_completion_rate
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 과제 완료율

### context.error_count
- **Type**: integer
- **Description**: 오류 횟수 (현재 문제)
- **Range**: 0-50

### context.preparation_level
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 준비 수준 점수

### context.initiative_score
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 주도성 점수

### context.goal_clarity
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 목표 명확도 점수

### context.plan_specificity
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 계획 구체성 점수

### context.self_direction_score
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 자기 주도성 점수

### context.concept_curiosity
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 개념 탐구 호기심 수준

### context.direction_certainty
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 방향 확신도

---

## Source Classification

### context.goal_source
- **Type**: string (enum)
- **Values**: `internal`, `external`, `mixed`
- **Description**: 목표 출처 (내재적/외부적)

### context.intrinsic_motivation
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 내재적 동기 수준

### context.obligation_driven
- **Type**: boolean
- **Description**: 의무감 기반 학습 여부

---

## Temporal Scope

### context.vision_timeframe
- **Type**: string (enum)
- **Values**: `immediate`, `short_term`, `medium_term`, `long_term`
- **Description**: 비전/계획 시간 범위

### context.focus_timeframe
- **Type**: string (enum)
- **Values**: `immediate`, `short_term`, `medium_term`, `long_term`
- **Description**: 집중 시간 범위

### context.roadmap_interest
- **Type**: float
- **Range**: 0.0-1.0
- **Description**: 로드맵 관심도

### context.milestone_awareness
- **Type**: boolean
- **Description**: 이정표 인식 여부

### context.discovery_oriented
- **Type**: boolean
- **Description**: 발견 지향적 학습 성향

---

## Session State

### context.weekly_planning
- **Type**: boolean
- **Description**: 주간 계획 수립 중

### context.daily_summary
- **Type**: boolean
- **Description**: 일일 요약/회고 중

### context.self_assessment_active
- **Type**: boolean
- **Description**: 자기 평가 진행 중

### context.learning_synthesis_attempt
- **Type**: boolean
- **Description**: 학습 내용 종합 시도 중

### context.termination_urgency
- **Type**: boolean
- **Description**: 종료 긴급성

### context.immediate_needs_priority
- **Type**: boolean
- **Description**: 즉각적 필요 우선 여부

---

## Data Collection Sources

| Source | Parameters | Collection Method |
|--------|------------|-------------------|
| UI Events | button clicks, page views | Event listeners |
| Session Tracker | activity, duration | Server-side logging |
| Message Analyzer | keywords, sentiment, length | NLP processing |
| Performance Tracker | scores, completion rates | Database queries |
| Schedule System | time_to_class, sessions | Moodle calendar |
| Behavior Analyzer | patterns, trends | ML model inference |

---

## DB Integration

**Table**: mdl_agent07_context_log

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| userid | INT | Moodle user ID |
| session_id | VARCHAR(50) | Session identifier |
| context_key | VARCHAR(100) | Parameter name |
| context_value | TEXT | Parameter value (JSON) |
| source | VARCHAR(50) | Data source |
| created_at | DATETIME | Timestamp |

---

## Related Files

- `rules.yaml`: Rules using these context parameters
- `personas.md`: Persona definitions with behavioral indicators
- `engine/DataContext.php`: Context data collection class
