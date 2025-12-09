# Agent07 Interaction Targeting - Persona System

## Overview
Agent07 상호작용 타겟팅을 위한 18개 페르소나 정의서입니다.
6개 상황(Situation) × 3개 페르소나로 구성됩니다.

**Version**: 1.0
**Last Updated**: 2025-12-02
**Agent**: agent07_interaction_targeting

---

## Situation Categories

| ID | Situation | Description | Personas |
|----|-----------|-------------|----------|
| S1 | 실시간고민 | 학습 중 즉각적인 도움이 필요한 상황 | S1_P1, S1_P2, S1_P3 |
| S2 | 포모도르 | 집중 학습 세션 중 상태 | S2_P1, S2_P2, S2_P3 |
| S3 | 수업준비 | 수업 시작 전 준비 단계 | S3_P1, S3_P2, S3_P3 |
| S4 | 목표설정 | 학습 목표 수립 상황 | S4_P1, S4_P2, S4_P3 |
| S5 | 귀가검사 | 학습 종료 후 회고 단계 | S5_P1, S5_P2, S5_P3 |
| S6 | 커리큘럼 | 장기 학습 계획 수립 | S6_P1, S6_P2, S6_P3 |

---

## S1: 실시간고민 (Real-time Problem) Personas

### S1_P1: 긴급 도움 요청자
**ID**: S1_P1
**Name**: 긴급 도움 요청자
**Situation**: 실시간고민
**Description**: 문제 해결에 막혀 즉각적인 도움을 적극적으로 요청하는 학습자

**Characteristics**:
- 문제 상황을 명확하게 표현함
- 도움 요청에 적극적
- 빠른 해결을 원함

**Behavioral Indicators**:
- help_request_explicit: true
- problem_articulation: high
- urgency_level: high
- response_time_expectation: immediate

**Recommended Approach**:
- 직접적이고 구체적인 힌트 제공
- 단계별 가이드 제시
- 즉각적인 피드백 제공

**Response Tone**: supportive, direct, solution-focused

---

### S1_P2: 막힘 표현 어려움형
**ID**: S1_P2
**Name**: 막힘 표현 어려움형
**Situation**: 실시간고민
**Description**: 문제가 있지만 무엇이 문제인지 표현하기 어려워하는 학습자

**Characteristics**:
- 막힘 상태이나 원인 파악 어려움
- 질문 형성에 어려움
- 침묵하거나 모호한 표현 사용

**Behavioral Indicators**:
- help_request_explicit: false
- problem_articulation: low
- confusion_signals: high
- interaction_frequency: decreasing

**Recommended Approach**:
- 진단적 질문으로 문제 파악 도움
- 선택지 제공하여 상태 확인
- 공감적 접근으로 안심시키기

**Response Tone**: patient, empathetic, exploratory

---

### S1_P3: 탐색적 질문자
**ID**: S1_P3
**Name**: 탐색적 질문자
**Situation**: 실시간고민
**Description**: 답을 직접 원하기보다 이해를 깊게 하려는 탐구적 학습자

**Characteristics**:
- "왜?"라는 질문을 자주 함
- 개념 연결에 관심
- 스스로 발견하고 싶어함

**Behavioral Indicators**:
- question_type: exploratory
- concept_curiosity: high
- self_discovery_preference: true
- depth_over_speed: true

**Recommended Approach**:
- 소크라테스식 질문법 활용
- 개념 간 연결 힌트 제공
- 탐구 과정 격려

**Response Tone**: curious, guiding, intellectually engaging

---

## S2: 포모도르 (Pomodoro) Personas

### S2_P1: 집중력 저하형
**ID**: S2_P1
**Name**: 집중력 저하형
**Situation**: 포모도로
**Description**: 포모도로 세션 중 집중이 흐트러지는 학습자

**Characteristics**:
- 세션 중반부터 집중도 하락
- 주의 분산 신호 감지
- 피로감 호소

**Behavioral Indicators**:
- focus_duration: short
- distraction_signals: high
- task_switching: frequent
- session_completion_rate: low

**Recommended Approach**:
- 짧은 목표 재설정 제안
- 집중 회복 기법 안내
- 적절한 휴식 권장

**Response Tone**: gentle, encouraging, practical

---

### S2_P2: 동기 이탈형
**ID**: S2_P2
**Name**: 동기 이탈형
**Situation**: 포모도로
**Description**: 학습 동기가 저하되어 세션 지속이 어려운 학습자

**Characteristics**:
- "왜 해야 하지?" 의문 표출
- 학습 의미 상실
- 조기 종료 희망

**Behavioral Indicators**:
- motivation_level: declining
- meaning_questioning: true
- early_termination_request: true
- engagement_score: low

**Recommended Approach**:
- 학습 목적 재확인
- 작은 성취 상기시키기
- 목표와의 연결 강조

**Response Tone**: motivating, meaningful, connecting

---

### S2_P3: 안정적 수행자
**ID**: S2_P3
**Name**: 안정적 수행자
**Situation**: 포모도로
**Description**: 포모도로 세션을 안정적으로 수행하는 학습자

**Characteristics**:
- 일정한 학습 리듬 유지
- 계획대로 진행
- 자기 조절 능력 양호

**Behavioral Indicators**:
- focus_duration: sustained
- task_completion: high
- self_regulation: good
- session_consistency: stable

**Recommended Approach**:
- 현재 페이스 유지 격려
- 필요시 심화 과제 제안
- 긍정적 강화

**Response Tone**: affirming, minimal intervention, supportive

---

## S3: 수업준비 (Class Preparation) Personas

### S3_P1: 적극적 준비형
**ID**: S3_P1
**Name**: 적극적 준비형
**Situation**: 수업준비
**Description**: 수업 전 적극적으로 예습하고 준비하는 학습자

**Characteristics**:
- 사전 자료 검토
- 질문 목록 준비
- 높은 학습 기대감

**Behavioral Indicators**:
- preparation_level: high
- preview_activity: active
- question_preparation: true
- anticipation_positive: true

**Recommended Approach**:
- 심화 예습 자료 제공
- 예상 질문 힌트 제공
- 기대감 고양

**Response Tone**: enthusiastic, challenging, preparatory

---

### S3_P2: 수동적 대기형
**ID**: S3_P2
**Name**: 수동적 대기형
**Situation**: 수업준비
**Description**: 수업을 기다리되 특별한 준비 없이 대기하는 학습자

**Characteristics**:
- 최소한의 준비만 수행
- 수동적 학습 자세
- 지시 의존적

**Behavioral Indicators**:
- preparation_level: minimal
- initiative: low
- waiting_passively: true
- direction_dependent: true

**Recommended Approach**:
- 간단한 워밍업 활동 제안
- 호기심 자극 질문
- 점진적 참여 유도

**Response Tone**: inviting, gentle activation, non-pressuring

---

### S3_P3: 불안한 준비형
**ID**: S3_P3
**Name**: 불안한 준비형
**Situation**: 수업준비
**Description**: 수업에 대한 불안감으로 긴장하고 있는 학습자

**Characteristics**:
- 수업 불안 표현
- 준비 부족 걱정
- 과도한 확인 행동

**Behavioral Indicators**:
- anxiety_level: high
- preparation_worry: true
- reassurance_seeking: frequent
- confidence_low: true

**Recommended Approach**:
- 안심시키는 메시지
- 준비 상태 확인 및 격려
- 작은 성공 경험 상기

**Response Tone**: calming, reassuring, confidence-building

---

## S4: 목표설정 (Goal Setting) Personas

### S4_P1: 명확한 목표 보유형
**ID**: S4_P1
**Name**: 명확한 목표 보유형
**Situation**: 목표설정
**Description**: 이미 명확한 학습 목표를 가지고 있는 학습자

**Characteristics**:
- 구체적 목표 진술
- 실행 계획 보유
- 자기 주도적

**Behavioral Indicators**:
- goal_clarity: high
- plan_specificity: detailed
- self_direction: strong
- commitment_level: high

**Recommended Approach**:
- 목표 검증 및 정교화
- 실행 계획 구체화 지원
- 장애물 예상 및 대비 도움

**Response Tone**: validating, refining, strategic

---

### S4_P2: 목표 모호형
**ID**: S4_P2
**Name**: 목표 모호형
**Situation**: 목표설정
**Description**: 학습 목표가 불분명하거나 막연한 학습자

**Characteristics**:
- "잘하고 싶다" 등 추상적 표현
- 구체적 방향 부재
- 선택 어려움

**Behavioral Indicators**:
- goal_clarity: low
- expression_vague: true
- decision_difficulty: true
- direction_unclear: true

**Recommended Approach**:
- 목표 구체화 질문
- SMART 목표 설정 가이드
- 작은 목표부터 설정 도움

**Response Tone**: clarifying, structured, scaffolding

---

### S4_P3: 외압형 목표 수용자
**ID**: S4_P3
**Name**: 외압형 목표 수용자
**Situation**: 목표설정
**Description**: 부모/선생님 등 외부 압력으로 인한 목표를 가진 학습자

**Characteristics**:
- 타인이 정한 목표 언급
- 내적 동기 부족
- 의무감 위주

**Behavioral Indicators**:
- goal_source: external
- intrinsic_motivation: low
- obligation_driven: true
- ownership_low: true

**Recommended Approach**:
- 개인적 의미 찾기 도움
- 내재화 촉진 질문
- 자율성 회복 지원

**Response Tone**: empathetic, autonomy-supportive, meaning-finding

---

## S5: 귀가검사 (Return Check) Personas

### S5_P1: 성찰적 회고형
**ID**: S5_P1
**Name**: 성찰적 회고형
**Situation**: 귀가검사
**Description**: 학습 내용을 깊이 성찰하고 정리하려는 학습자

**Characteristics**:
- 자발적 회고 참여
- 배운 점 정리 시도
- 개선점 탐색

**Behavioral Indicators**:
- reflection_willingness: high
- self_assessment: active
- learning_synthesis: attempting
- improvement_seeking: true

**Recommended Approach**:
- 성찰 질문 제공
- 학습 연결 도움
- 다음 단계 연결

**Response Tone**: reflective, deepening, forward-looking

---

### S5_P2: 빠른 종료 희망형
**ID**: S5_P2
**Name**: 빠른 종료 희망형
**Situation**: 귀가검사
**Description**: 학습 종료를 서두르며 회고에 관심 없는 학습자

**Characteristics**:
- 조기 종료 요청
- 회고 과정 생략 희망
- 피로감 또는 다른 일정

**Behavioral Indicators**:
- termination_urgency: high
- reflection_avoidance: true
- fatigue_signals: possible
- external_pressure: possible

**Recommended Approach**:
- 핵심만 간결하게 요약
- 1-2개 핵심 질문만
- 다음 세션 연결 고리 남기기

**Response Tone**: efficient, respectful, brief

---

### S5_P3: 자기비판형
**ID**: S5_P3
**Name**: 자기비판형
**Situation**: 귀가검사
**Description**: 학습 결과에 대해 과도하게 자기 비판적인 학습자

**Characteristics**:
- 부정적 자기 평가
- 성취 과소평가
- 완벽주의적 경향

**Behavioral Indicators**:
- self_criticism: high
- achievement_dismissal: true
- perfectionism: present
- negative_self_talk: true

**Recommended Approach**:
- 성취 재프레이밍
- 과정 중심 피드백
- 균형 잡힌 시각 제공

**Response Tone**: balanced, appreciative, growth-focused

---

## S6: 커리큘럼 (Curriculum) Personas

### S6_P1: 장기 비전형
**ID**: S6_P1
**Name**: 장기 비전형
**Situation**: 커리큘럼
**Description**: 장기적인 학습 목표와 비전을 가진 학습자

**Characteristics**:
- 장기 목표 명확
- 로드맵 관심
- 단계적 성장 지향

**Behavioral Indicators**:
- vision_timeframe: long_term
- roadmap_interest: high
- milestone_awareness: true
- strategic_thinking: present

**Recommended Approach**:
- 장기 로드맵 설계 지원
- 이정표 설정 도움
- 큰 그림과 현재 연결

**Response Tone**: visionary, strategic, milestone-oriented

---

### S6_P2: 단기 집중형
**ID**: S6_P2
**Name**: 단기 집중형
**Situation**: 커리큘럼
**Description**: 당장의 과제나 시험에 집중하는 학습자

**Characteristics**:
- 즉각적 필요 중심
- 단기 목표 위주
- 실용적 접근

**Behavioral Indicators**:
- focus_timeframe: short_term
- immediate_needs: prioritized
- practical_orientation: high
- long_term_planning: limited

**Recommended Approach**:
- 단기 목표 최적화
- 장기와의 연결 암시
- 효율적 학습 경로 제안

**Response Tone**: practical, efficient, task-focused

---

### S6_P3: 방향 탐색형
**ID**: S6_P3
**Name**: 방향 탐색형
**Situation**: 커리큘럼
**Description**: 학습 방향이나 진로를 탐색 중인 학습자

**Characteristics**:
- 다양한 옵션 탐색
- 불확실성 수용
- 발견적 학습 선호

**Behavioral Indicators**:
- direction_certainty: exploring
- option_exploration: active
- uncertainty_tolerance: present
- discovery_oriented: true

**Recommended Approach**:
- 탐색 기회 제공
- 다양한 경로 소개
- 자기 이해 질문

**Response Tone**: open, exploratory, discovery-supporting

---

## Persona Selection Matrix

| Situation | Primary Indicator | P1 Condition | P2 Condition | P3 Condition |
|-----------|-------------------|--------------|--------------|--------------|
| S1 실시간고민 | help_request_type | explicit & articulate | implicit & confused | exploratory |
| S2 포모도로 | session_state | focus_declining | motivation_declining | stable |
| S3 수업준비 | preparation_style | active | passive | anxious |
| S4 목표설정 | goal_clarity | clear | vague | external |
| S5 귀가검사 | reflection_attitude | reflective | rushing | self-critical |
| S6 커리큘럼 | planning_horizon | long_term | short_term | exploring |

---

## DB Integration

**Table**: mdl_agent07_persona_log

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| userid | INT | Moodle user ID |
| situation_id | VARCHAR(10) | S1-S6 |
| persona_id | VARCHAR(10) | S1_P1 ~ S6_P3 |
| confidence_score | DECIMAL(3,2) | 0.00-1.00 |
| context_data | JSON | Detection context |
| created_at | DATETIME | Timestamp |

---

## Related Files

- `rules.yaml`: Persona identification rules
- `contextlist.md`: Context parameters definition
- `engine/PersonaRuleEngine.php`: Core engine
- `templates/`: Response templates by situation
