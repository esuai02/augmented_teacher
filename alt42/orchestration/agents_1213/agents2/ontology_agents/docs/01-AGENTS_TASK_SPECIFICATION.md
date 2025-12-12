# 01. 에이전트 및 Task 명세서

**문서 버전**: 1.0
**작성일**: 2025-10-29
**목적**: 22개 에이전트와 각 Task의 입출력 명세 정의

---

## 📋 목차

1. [에이전트 개요](#에이전트-개요)
2. [에이전트-Task 매핑](#에이전트-task-매핑)
3. [Task 입출력 명세](#task-입출력-명세)
4. [Orchestration ↔ Mathking 매핑](#orchestration--mathking-매핑)
5. [표준 데이터 구조](#표준-데이터-구조)

---

## ⚠️ 구현 상태 (Implementation Status)

**버전**: 1.0 (설계 완료, 단계적 구현 중)
**최종 업데이트**: 2025-10-30

### 현재 구현 현황

| 상태 | 개수 | 에이전트 | 비고 |
|------|------|---------|------|
| ✅ 완료 | 1개 | agent_curriculum | config/tasks/prompts 완전 구현 |
| 📁 구조만 | 21개 | agent02-agent22 | 빈 폴더 상태 (Phase 2-4 구현 예정) |

### 이 문서의 역할

- **최종 목표 상태 기술**: 22개 에이전트 모두 완성된 상태를 설명
- **구현 가이드라인 제공**: 각 Task I/O 명세는 향후 구현 시 참조 기준
- **단계적 구현 계획**: 07-IMPLEMENTATION_ROADMAP 참조
  - Phase 2 (Week 9-12): 첫 3개 에이전트 구현
  - Phase 3 (Week 13-16): 추가 5개 구현
  - Phase 4 (Week 17-20): 나머지 14개 구현

### 구현 우선순위 (Phase 2 대상)

1. **agent_exam_prep**: 시험 관리 (높은 교육적 가치)
2. **agent_adaptive**: 맞춤 학습 (핵심 개인화 기능)
3. **agent_goal_setting**: 목표 설정 (학습 방향 설정)

---

## 에이전트 개요

### Orchestration 에이전트 (22개)

#### 🎯 핵심 학습 관리 에이전트 (9개)

| ID | 이름 | Mathking 매핑 | 주요 책임 |
|----|------|---------------|----------|
| agent01 | 온보딩 | agent_self_directed | 학습 시작, 초기 설정 |
| agent02 | 시험일정 | agent_exam_prep | 시험 일정 관리, 대비 계획 |
| agent03 | 목표분석 | agent_goal_setting | 학습 목표 설정 및 분석 |
| agent04 | 문제활동 | agent_curriculum | 문제풀이 활동 관리 |
| agent05 | 학습정서 | agent_emotion | 학습 감정 상태 관리 |
| agent06 | 교사피드백 | agent_feedback | 교사 피드백 처리 |
| agent07 | 상호작용타겟팅 | agent_adaptive | 맞춤형 상호작용 설계 |
| agent08 | 평정심 | agent_emotion | 감정 안정화, 스트레스 관리 |
| agent09 | 학습관리 | agent_time_management | 학습 시간 및 진도 관리 |

#### 📚 학습 콘텐츠 관리 에이전트 (4개)

| ID | 이름 | Mathking 매핑 | 주요 책임 |
|----|------|---------------|----------|
| agent10 | 개념노트 | agent_metacognition | 개념 정리 및 노트 관리 |
| agent11 | 문제노트 | agent_metacognition | 오답 노트, 문제 분석 |
| agent12 | 휴식루틴 | agent_habit | 휴식 패턴 관리 |
| agent13 | 학습이탈 | agent_motivation | 이탈 방지, 동기 부여 |

#### 🔍 학습 진단 및 개입 에이전트 (9개)

| ID | 이름 | Mathking 매핑 | 주요 책임 |
|----|------|---------------|----------|
| agent14 | 현재위치 | agent_self_reflection | 학습 현황 분석 |
| agent15 | 문제재정의 | agent_cognitive | 문제 이해 수준 진단 |
| agent16 | 상호작용준비 | agent_inquiry | 질문 준비, 탐구 유도 |
| agent17 | 남은활동 | agent_micro_mission | 남은 학습량 관리 |
| agent18 | 시그니처루틴 | agent_habit | 학습 루틴 형성 |
| agent19 | 상호작용내용 | agent_social | 학습 상호작용 촉진 |
| agent20 | 개입준비 | agent_apprenticeship | 교사 개입 준비 |
| agent21 | 개입실행 | agent_apprenticeship | 실제 개입 실행 |
| agent22 | 모듈개선 | agent_improvement | 시스템 성능 개선 제안 |

---

## 에이전트-Task 매핑

### Agent01: 온보딩 (Onboarding)

**목적**: 학생의 학습 시작 및 초기 설정 지원

#### Task 1.1: 학습자 프로필 초기화 (profile_initialization)

**입력 (Input)**:
```yaml
student_id: string           # 학생 ID
grade: integer              # 학년
subject: string             # 과목
entry_test_result:          # 진단평가 결과
  score: float              # 점수
  weak_areas: [string]      # 취약 영역
  strong_areas: [string]    # 강점 영역
learning_style:             # 학습 스타일 (설문)
  visual: float (0-1)
  auditory: float (0-1)
  kinesthetic: float (0-1)
persona_survey:             # 페르소나 설문
  responses: [integer]      # 설문 응답 배열
timestamp: datetime
```

**출력 (Output)**:
```yaml
profile_created: boolean
initial_persona_id: string  # 초기 페르소나 (예: P_avoidant)
recommended_path:           # 추천 학습 경로
  curriculum_id: string
  difficulty_level: string  # easy|medium|hard
  estimated_duration_days: integer
initial_goals:              # 초기 목표
  short_term: string
  medium_term: string
  long_term: string
next_agent_recommendation:  # 다음 에이전트 추천
  agent_id: string
  reason: string
metadata:
  confidence: float (0-1)
  processing_time_ms: integer
```

#### Task 1.2: 학습 환경 설정 (environment_setup)

**입력 (Input)**:
```yaml
student_id: string
device_info:
  type: string              # desktop|tablet|mobile
  os: string
  browser: string
preferences:
  notification_enabled: boolean
  sound_enabled: boolean
  theme: string             # light|dark
accessibility:
  needs: [string]           # 접근성 요구사항
timestamp: datetime
```

**출력 (Output)**:
```yaml
environment_configured: boolean
personalized_settings:
  ui_layout: string
  font_size: string
  color_scheme: string
accessibility_adjustments: [string]
heartbeat_schedule:         # Heartbeat 스케줄 초기화
  agent_01: "30min"
  agent_02: "60min"
  # ... (22개 에이전트)
```

---

### Agent02: 시험일정 (Exam Schedule)

**목적**: 시험 일정 관리 및 대비 계획 수립

#### Task 2.1: 시험 일정 등록 (exam_registration)

**입력 (Input)**:
```yaml
student_id: string
exam_info:
  exam_id: string
  exam_name: string
  exam_date: datetime
  exam_type: string         # midterm|final|mock
  subject: string
  syllabus_coverage: [string] # 시험 범위
  estimated_difficulty: string # easy|medium|hard
current_study_progress:     # 현재 학습 진도
  completed_units: [string]
  pending_units: [string]
  weak_units: [string]
timestamp: datetime
```

**출력 (Output)**:
```yaml
exam_registered: boolean
study_plan:                 # 학습 계획
  days_remaining: integer
  daily_targets: [
    {
      date: date
      units: [string]
      estimated_hours: float
      priority: string      # high|medium|low
    }
  ]
  review_schedule: [
    {
      date: date
      topics: [string]
      type: string          # first_review|second_review|final_review
    }
  ]
milestones:                 # 중간 체크포인트
  - date: date
    goal: string
    check_method: string
alert_schedule:             # 알림 일정
  - date: datetime
    message: string
    priority: string
next_actions:               # 즉시 수행 액션
  - action_type: string     # adjust_pace|focus_weak_area
    target: string
    reason: string
```

#### Task 2.2: 시험 대비 진도 점검 (exam_progress_check)

**입력 (Input)**:
```yaml
student_id: string
exam_id: string
current_progress:           # 현재 진행 상황
  completed_percentage: float (0-100)
  time_spent_hours: float
  practice_test_scores: [float]
  weak_area_improvements: [
    {
      area: string
      before_score: float
      after_score: float
    }
  ]
days_remaining: integer
timestamp: datetime
```

**출력 (Output)**:
```yaml
progress_assessment:
  status: string            # on_track|behind|ahead
  completion_rate: float (0-1)
  predicted_readiness: float (0-1)
adjustments_needed:         # 조정 사항
  - type: string            # increase_pace|focus_weak_area|add_practice
    priority: float (0-1)
    details: string
recommendations:            # 추천 액션
  - action: string
    reason: string
    expected_impact: string
alerts:                     # 경고 사항
  - level: string           # warning|critical
    message: string
    recommended_action: string
```

---

### Agent03: 목표분석 (Goals Analysis)

**목적**: 학습 목표 설정, 추적 및 분석

#### Task 3.1: 목표 설정 (goal_setting)

**입력 (Input)**:
```yaml
student_id: string
goal_scope: string          # daily|weekly|monthly|quarterly
context:
  current_position:         # 현재 학습 위치
    curriculum_unit: string
    completion_percentage: float
    skill_level: string     # beginner|intermediate|advanced
  constraints:
    available_time_hours_per_day: float
    exam_deadlines: [datetime]
    other_commitments: [string]
  aspirations:              # 학생의 희망
    desired_outcome: string
    motivation_level: float (0-1)
    confidence_level: float (0-1)
timestamp: datetime
```

**출력 (Output)**:
```yaml
goal_created:
  goal_id: string
  goal_text: string
  goal_type: string         # completion|improvement|mastery
  scope: string             # daily|weekly|monthly|quarterly
  measurable_criteria:      # 측정 가능 기준
    metric: string          # completion_rate|score|time
    target_value: float
    current_value: float
  deadline: datetime
  sub_goals: [              # 하위 목표
    {
      sub_goal_id: string
      description: string
      deadline: datetime
    }
  ]
action_plan:                # 실행 계획
  - step: integer
    action: string
    estimated_duration: string
    dependencies: [string]
success_indicators:         # 성공 지표
  - indicator: string
    measurement_method: string
motivation_strategy:        # 동기 부여 전략
  rewards: [string]
  checkpoints: [datetime]
  encouragement_messages: [string]
```

#### Task 3.2: 목표 진도 추적 (goal_tracking)

**입력 (Input)**:
```yaml
student_id: string
goal_id: string
progress_data:             # 진행 데이터
  completed_actions: [string]
  time_invested_hours: float
  current_metric_value: float
  obstacles_encountered: [string]
  adjustments_made: [string]
timestamp: datetime
```

**출력 (Output)**:
```yaml
progress_report:
  completion_percentage: float (0-100)
  pace: string              # faster|on_track|slower
  quality_score: float (0-1)
  trend: string             # improving|stable|declining
gap_analysis:               # 목표와 현실 차이
  expected_position: float
  actual_position: float
  gap: float
  gap_reasons: [string]
adjustments_recommended:    # 조정 권장사항
  - adjustment_type: string # revise_goal|change_strategy|add_support
    details: string
    urgency: string         # high|medium|low
next_actions:               # 다음 액션
  - action: string
    priority: integer
    deadline: datetime
motivational_feedback:      # 동기 부여 피드백
  message: string
  tone: string              # encouraging|challenging|supportive
```

---

### Agent04: 문제활동 (Problem Activity)

**목적**: 문제 풀이 활동 관리 및 최적화

#### Task 4.1: 문제 세트 추천 (problem_recommendation)

**입력 (Input)**:
```yaml
student_id: string
context:
  current_unit: string
  learning_objective: string
  time_available_minutes: integer
  difficulty_preference: string # adaptive|fixed
student_state:              # 학생 상태
  cognitive_load: string    # low|medium|high
  attention_level: float (0-1)
  recent_performance:       # 최근 성과
    avg_score: float
    avg_time_per_problem: float
    error_patterns: [string]
timestamp: datetime
```

**출력 (Output)**:
```yaml
recommended_problems:
  - problem_id: string
    problem_type: string    # concept|application|practice
    difficulty: string      # easy|medium|hard
    estimated_time_min: integer
    tags: [string]
    prerequisite_concepts: [string]
    expected_learning_outcome: string
sequence_optimization:      # 문제 순서 최적화
  ordering_rationale: string
  warm_up_problems: [string]
  core_problems: [string]
  challenge_problems: [string]
adaptive_rules:             # 적응 규칙
  - condition: string       # if score < 0.6
    action: string          # reduce difficulty
  - condition: string
    action: string
estimated_completion_time: integer
success_criteria:           # 성공 기준
  min_score: float
  min_completion_rate: float
```

#### Task 4.2: 문제 활동 분석 (problem_activity_analysis)

**입력 (Input)**:
```yaml
student_id: string
session_id: string
problems_attempted:         # 시도한 문제들
  - problem_id: string
    attempt_number: integer
    time_spent_seconds: integer
    answer_submitted: string
    correct: boolean
    hint_used: boolean
    partial_credit: float (0-1)
    thinking_process:       # 사고 과정 (선택)
      steps: [string]
      errors: [string]
session_metrics:
  total_time_minutes: integer
  total_problems: integer
  correct_count: integer
  accuracy_rate: float
  avg_time_per_problem: float
timestamp: datetime
```

**출력 (Output)**:
```yaml
performance_summary:
  session_score: float (0-100)
  efficiency: string        # high|medium|low
  consistency: string       # consistent|variable|declining
  stamina: string           # maintained|declined
error_analysis:             # 오류 분석
  error_categories:
    - category: string      # calculation|concept|careless
      frequency: integer
      severity: string      # critical|moderate|minor
  recurring_mistakes: [string]
  misconceptions_detected: [string]
learning_insights:          # 학습 통찰
  strengths: [string]
  weaknesses: [string]
  improvement_areas: [string]
  mastery_level: string     # novice|developing|proficient|expert
next_recommendations:       # 다음 추천
  focus_areas: [string]
  difficulty_adjustment: string # increase|maintain|decrease
  supplementary_materials: [string]
intervention_needed:        # 개입 필요 여부
  required: boolean
  type: string              # concept_review|practice_boost|rest
  urgency: string
```

---

### Agent05: 학습정서 (Learning Emotion)

**목적**: 학습 과정의 감정 상태 모니터링 및 관리

#### Task 5.1: 감정 상태 감지 (emotion_detection)

**입력 (Input)**:
```yaml
student_id: string
behavioral_signals:         # 행동 신호
  click_pattern: string     # rapid|normal|slow|hesitant
  pause_frequency: integer
  revisit_count: integer    # 같은 문제 재방문
  help_requests: integer
  abandon_rate: float       # 문제 포기 비율
interaction_data:           # 상호작용 데이터
  response_time_avg_ms: integer
  error_rate: float
  consecutive_errors: integer
  consecutive_successes: integer
contextual_factors:         # 맥락 요인
  time_of_day: string
  day_of_week: string
  session_duration_min: integer
  recent_feedback: string   # positive|negative|neutral
timestamp: datetime
```

**출력 (Output)**:
```yaml
emotion_assessment:
  primary_emotion: string   # engaged|frustrated|anxious|confident|bored
  intensity: float (0-1)
  confidence_score: float (0-1)
  emotion_trajectory: string # improving|stable|declining
contributing_factors:       # 기여 요인
  - factor: string
    impact: string          # high|medium|low
    evidence: string
risk_indicators:            # 위험 지표
  frustration_level: float (0-1)
  anxiety_level: float (0-1)
  engagement_level: float (0-1)
  burnout_risk: float (0-1)
intervention_recommendation:
  needed: boolean
  urgency: string           # immediate|soon|monitor
  suggested_actions: [string]
  avoid_actions: [string]
```

#### Task 5.2: 감정 조절 개입 (emotion_regulation)

**입력 (Input)**:
```yaml
student_id: string
current_emotion: string
intensity: float (0-1)
trigger_event: string       # consecutive_failures|time_pressure|difficulty_spike
persona_profile:            # 페르소나 프로필
  persona_id: string
  emotional_sensitivity: float (0-1)
  preferred_support_style: string # encouragement|challenge|space
timestamp: datetime
```

**출력 (Output)**:
```yaml
intervention_strategy:
  approach: string          # reframe|break|encourage|adjust_difficulty
  tone: string              # supportive|neutral|challenging
  immediacy: string         # now|next_transition|end_of_session
actions_to_take:            # 취할 조치
  - action_type: string     # display_message|insert_break|change_activity
    content: string
    duration_seconds: integer
    follow_up_needed: boolean
messages:                   # 표시할 메시지
  - message_text: string
    message_type: string    # encouragement|tip|reminder
    display_timing: string
    display_duration_sec: integer
activity_adjustments:       # 활동 조정
  difficulty_change: string # easier|same|harder
  pace_change: string       # slower|same|faster
  break_recommendation: boolean
  break_duration_min: integer
monitoring_plan:            # 모니터링 계획
  check_after_minutes: integer
  success_indicators: [string]
  escalation_conditions: [string]
```

---

### Agent06: 교사피드백 (Teacher Feedback)

**목적**: 교사 피드백 수집, 분석 및 학습 조정

#### Task 6.1: 피드백 수집 및 분류 (feedback_collection)

**입력 (Input)**:
```yaml
student_id: string
teacher_id: string
feedback_data:
  feedback_id: string
  feedback_date: datetime
  feedback_context: string  # homework|test|class_participation|overall
  feedback_text: string
  feedback_format: string   # written|verbal_transcribed|structured
  rating_if_provided:       # 교사 평가 (선택)
    understanding: float (1-5)
    effort: float (1-5)
    improvement: float (1-5)
  specific_areas_mentioned: [string]
  action_items: [string]
timestamp: datetime
```

**출력 (Output)**:
```yaml
feedback_analysis:
  sentiment: string         # positive|mixed|constructive|critical
  key_themes: [string]
  strengths_identified: [string]
  areas_for_improvement: [string]
  urgency_level: string     # high|medium|low
actionable_insights:        # 실행 가능 통찰
  - insight: string
    action_required: string
    priority: integer
    estimated_impact: string # high|medium|low
curriculum_adjustments:     # 커리큘럼 조정
  - adjustment_type: string # focus|remediation|acceleration
    target_area: string
    reason: string
    implementation_plan: string
student_communication:      # 학생 소통
  should_inform_student: boolean
  message_tone: string      # encouraging|informative|directive
  message_summary: string
follow_up_needed:
  required: boolean
  follow_up_date: datetime
  follow_up_agent: string
```

---

### Agent07: 상호작용타겟팅 (Interaction Targeting)

**목적**: 맞춤형 학습 상호작용 설계 및 실행

#### Task 7.1: 상호작용 기회 식별 (interaction_opportunity_identification)

**입력 (Input)**:
```yaml
student_id: string
learning_context:
  current_topic: string
  learning_phase: string    # introduction|practice|mastery
  difficulty_level: string
  time_in_session_min: integer
student_state:
  engagement_level: float (0-1)
  understanding_level: float (0-1)
  recent_performance: float (0-1)
interaction_history:        # 상호작용 이력
  last_interaction_type: string
  last_interaction_time: datetime
  interaction_frequency_today: integer
  preferred_interaction_types: [string]
timestamp: datetime
```

**출력 (Output)**:
```yaml
interaction_recommendations:
  - opportunity_id: string
    interaction_type: string # question|hint|challenge|discussion|example
    target_concept: string
    timing: string          # now|after_current_problem|end_of_session
    priority: float (0-1)
    rationale: string
    expected_benefit: string
    estimated_duration_min: integer
personalization:            # 개인화 요소
  language_style: string    # formal|friendly|encouraging
  complexity_level: string  # simple|moderate|complex
  scaffolding_needed: boolean
  multimedia_preference: string # text|visual|interactive
interaction_design:         # 상호작용 설계
  opening: string
  core_content: string
  closure: string
  success_criteria: string
fallback_plan:              # 대체 계획
  if_no_response: string
  if_incorrect_response: string
  if_confusion_detected: string
```

---

### Agent08: 평정심 (Calmness)

**목적**: 학습 중 감정 안정화 및 스트레스 관리

#### Task 8.1: 스트레스 감지 (stress_detection)

**입력 (Input)**:
```yaml
student_id: string
stress_indicators:          # 스트레스 지표
  error_rate_increase: boolean
  response_time_variance: float
  repeated_mistakes: integer
  help_seeking_frequency: integer
  abandonment_signals: boolean
physiological_proxies:      # 생리적 대리 지표 (간접)
  click_force_estimate: string # normal|increased
  typing_speed_change: string # slower|faster|erratic
contextual_stress_factors:
  exam_proximity_days: integer
  workload_level: string    # low|medium|high|overwhelming
  recent_setbacks: integer
timestamp: datetime
```

**출력 (Output)**:
```yaml
stress_assessment:
  stress_level: string      # low|moderate|high|critical
  stress_type: string       # performance_anxiety|time_pressure|cognitive_overload
  confidence: float (0-1)
  trend: string             # increasing|stable|decreasing
immediate_interventions:    # 즉각 개입
  - intervention_type: string # breathing_exercise|break|difficulty_reduce
    instruction: string
    duration_seconds: integer
    trigger_condition: string
stress_management_plan:     # 스트레스 관리 계획
  short_term: [string]
  medium_term: [string]
  preventive_measures: [string]
safe_space_protocol:        # 안전 공간 프로토콜
  enabled: boolean
  message: string
  available_resources: [string]
escalation_needed:
  required: boolean
  escalation_target: string # agent_05|teacher|counselor
```

---

### Agent09: 학습관리 (Learning Management)

**목적**: 전반적인 학습 시간, 진도 및 효율성 관리

#### Task 9.1: 학습 세션 계획 (session_planning)

**입력 (Input)**:
```yaml
student_id: string
planning_scope: string      # today|week|month
constraints:
  available_time_slots: [
    {
      date: date
      start_time: time
      end_time: time
      quality: string       # optimal|acceptable|suboptimal
    }
  ]
  commitments: [            # 기존 약속
    {
      date: date
      time: time
      type: string
    }
  ]
learning_goals:             # 학습 목표
  - goal_id: string
    priority: integer
    deadline: datetime
current_progress:
  completion_rate: float (0-1)
  pace: string              # ahead|on_track|behind
timestamp: datetime
```

**출력 (Output)**:
```yaml
session_plan:
  sessions: [
    {
      session_id: string
      date: date
      start_time: time
      duration_min: integer
      objectives: [string]
      activities: [
        {
          activity_type: string # concept_study|problem_solving|review
          duration_min: integer
          materials: [string]
        }
      ]
      break_schedule: [
        {
          after_minutes: integer
          duration_min: integer
          activity: string  # rest|stretch|snack
        }
      ]
      expected_outcomes: [string]
    }
  ]
optimization_notes:
  energy_level_consideration: string
  difficulty_progression: string
  variety_balance: string
contingency_plans:          # 비상 계획
  - scenario: string
    alternative_plan: string
```

#### Task 9.2: 학습 효율성 분석 (efficiency_analysis)

**입력 (Input)**:
```yaml
student_id: string
analysis_period:
  start_date: date
  end_date: date
session_data: [             # 세션 데이터
  {
    session_id: string
    date: date
    duration_min: integer
    planned_objectives: [string]
    achieved_objectives: [string]
    time_distribution:      # 시간 배분
      productive_time: integer
      distracted_time: integer
      break_time: integer
    performance_metrics:
      problems_solved: integer
      accuracy_rate: float
      learning_gains: float
  }
]
```

**출력 (Output)**:
```yaml
efficiency_report:
  overall_efficiency: float (0-1)
  time_utilization:
    productive_percentage: float
    wasted_percentage: float
    optimal_percentage: float
  performance_trends:
    improvement_rate: float
    consistency_score: float (0-1)
    peak_performance_times: [string]
  bottlenecks_identified:
    - bottleneck: string
      impact: string
      suggested_solution: string
recommendations:
  time_management: [string]
  activity_optimization: [string]
  energy_management: [string]
predicted_improvements:
  if_implemented: string
  estimated_gain_percentage: float
```

---

### Agent10-13: 학습 콘텐츠 관리

*(개념노트, 문제노트, 휴식루틴, 학습이탈 - 상세 명세 유사 패턴 반복)*

---

### Agent14-21: 학습 진단 및 개입

*(현재위치, 문제재정의, 상호작용준비, 남은활동, 시그니처루틴, 상호작용내용, 개입준비, 개입실행 - 상세 명세 유사 패턴 반복)*

---

### Agent22: 모듈개선 (Module Improvement)

**목적**: 시스템 성능 모니터링 및 개선 제안

#### Task 22.1: 시스템 성능 모니터링 (system_monitoring)

**입력 (Input)**:
```yaml
monitoring_scope: string    # agent|task|overall
time_window:
  start: datetime
  end: datetime
performance_metrics:
  agent_execution_times: {agent_id: float}
  task_success_rates: {task_id: float}
  error_frequencies: {error_type: integer}
  resource_usage: {resource: float}
user_feedback:              # 사용자 피드백
  satisfaction_scores: [float]
  complaint_categories: [string]
timestamp: datetime
```

**출력 (Output)**:
```yaml
performance_assessment:
  overall_health: string    # excellent|good|fair|poor
  critical_issues: [string]
  performance_bottlenecks: [string]
improvement_proposals:
  - proposal_id: string
    category: string        # agent_optimization|task_redesign|new_feature
    description: string
    expected_impact: string
    implementation_effort: string # low|medium|high
    priority: integer
regression_alerts:          # 성능 저하 경고
  - alert: string
    severity: string
    affected_components: [string]
recommended_actions:        # 권장 조치
  immediate: [string]
  short_term: [string]
  long_term: [string]
```

---

## Orchestration ↔ Mathking 매핑

### 완전 매핑 테이블

| Orchestration | Mathking | 통합 방식 | 데이터 교환 |
|---------------|----------|----------|------------|
| agent01 (온보딩) | agent_self_directed | Evidence 생성 | Profile → Evidence |
| agent02 (시험일정) | agent_exam_prep | Evidence 생성 | Schedule → Evidence |
| agent03 (목표분석) | agent_goal_setting | Evidence 생성 | Goals → Evidence |
| agent04 (문제활동) | agent_curriculum | Evidence 생성 | Performance → Evidence |
| agent05 (학습정서) | agent_emotion | Evidence 생성 | Emotion → Evidence |
| agent06 (교사피드백) | agent_feedback | Evidence 생성 | Feedback → Evidence |
| agent07 (상호작용타겟팅) | agent_adaptive | Evidence 생성 | Interaction → Evidence |
| agent08 (평정심) | agent_emotion | Evidence 생성 | Stress → Evidence |
| agent09 (학습관리) | agent_time_management | Evidence 생성 | Session → Evidence |
| agent10 (개념노트) | agent_metacognition | Evidence 생성 | Notes → Evidence |
| agent11 (문제노트) | agent_metacognition | Evidence 생성 | Errors → Evidence |
| agent12 (휴식루틴) | agent_habit | Evidence 생성 | Rest → Evidence |
| agent13 (학습이탈) | agent_motivation | Evidence 생성 | Engagement → Evidence |
| agent14 (현재위치) | agent_self_reflection | Evidence 생성 | Position → Evidence |
| agent15 (문제재정의) | agent_cognitive | Evidence 생성 | Understanding → Evidence |
| agent16 (상호작용준비) | agent_inquiry | Evidence 생성 | Questions → Evidence |
| agent17 (남은활동) | agent_micro_mission | Evidence 생성 | Remaining → Evidence |
| agent18 (시그니처루틴) | agent_habit | Evidence 생성 | Routine → Evidence |
| agent19 (상호작용내용) | agent_social | Evidence 생성 | Social → Evidence |
| agent20 (개입준비) | agent_apprenticeship | Evidence 생성 | Preparation → Evidence |
| agent21 (개입실행) | agent_apprenticeship | Evidence 생성 | Execution → Evidence |
| agent22 (모듈개선) | agent_improvement | Evidence 생성 | Performance → Evidence |

---

## 표준 데이터 구조

### Evidence Package (통합 데이터 구조)

**모든 Orchestration 에이전트 → Mathking으로 전달되는 표준 형식**

```yaml
evidence_package:
  # 메타데이터
  evidence_id: string           # 고유 ID
  source_agent_id: string       # 출처 에이전트
  source_task_id: string        # 출처 태스크
  timestamp: datetime

  # 학생 정보
  student_id: string
  session_id: string

  # Evidence 데이터
  metrics:                      # 정량적 지표
    progress_delta: float       # 진도 변화
    accuracy_rate: float        # 정답률
    response_time_avg: float    # 평균 응답 시간
    retry_count: integer        # 재시도 횟수
    completion_rate: float      # 완성률
    [custom_metrics]: float     # 에이전트별 커스텀 지표

  window:                       # 시간 윈도우
    start_ts: datetime
    end_ts: datetime
    duration_minutes: integer

  context:                      # 컨텍스트
    class_status: string        # start|mid|end_30min
    topic: string
    difficulty_level: string
    learning_phase: string      # introduction|practice|mastery

  state:                        # 상태
    affect: string              # low|med|high
    focus: float (0-1)
    cognitive_load: string      # low|med|high
    engagement: float (0-1)

  tags: [string]                # 분류 태그
  priority: float (0-1)         # 우선순위
  confidence: float (0-1)       # 신뢰도
```

### Directive Package (Mathking → Orchestration 반환)

```yaml
directive_package:
  # 메타데이터
  directive_id: string
  decision_id: string           # 의사결정 추적 ID
  source_agent_mathking: string # Mathking 에이전트
  target_agent_orchestration: string # Orchestration 에이전트
  timestamp: datetime

  # 지시 내용
  directive_type: string        # report|action|alert|recommendation
  priority: float (0-1)
  urgency: string               # immediate|soon|scheduled

  # 액션
  actions: [
    {
      action_id: string
      action_type: string       # adjust_difficulty|insert_break|provide_feedback
      action_target: string     # student|content|system
      action_params: object     # 액션별 파라미터
      execution_timing: string  # now|next|scheduled
      expected_outcome: string
    }
  ]

  # 리포트
  report:
    title: string
    summary: string
    details: string
    visualizations: [string]    # 차트 참조

  # 근거
  rationale:
    rules_triggered: [string]   # 트리거된 규칙
    llm_reasoning: string       # LLM 추론 (있을 경우)
    evidence_used: [string]     # 사용된 Evidence ID
    confidence: float (0-1)

  # 추적
  links:
    parent_artifact_id: string  # Agent Links Artifact
    related_directives: [string]
```

---

## 다음 단계

1. ✅ **Task 입출력 명세 완료**
2. 🔄 **다음**: [02-COLLABORATION_PATTERNS.md](./02-COLLABORATION_PATTERNS.md) - 에이전트 및 Task 협업 패턴
3. 🔄 **다음**: [03-KNOWLEDGE_BASE_ARCHITECTURE.md](./03-KNOWLEDGE_BASE_ARCHITECTURE.md) - 지식베이스 구조

---

**문서 버전**: 1.0
**최종 업데이트**: 2025-10-29
**작성자**: Architecture Team
