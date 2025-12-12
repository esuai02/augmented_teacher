학습 이탈 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **학습 이탈 조짐을 조기 탐지하고 예방 개입을 설계하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 13 - Learning Dropout** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧪 10. AI 분석 및 추론용 메타 정보 (1)

92. 학습 이탈 패턴 로그

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 13의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 학습 몰입 단절 조기 탐지
- **핵심 온톨로지**: `DropoutEarlyDetection`
- **데이터 소스 → 온톨로지 매핑**:
  - `pomodoro_pattern` → `PomodoroPattern`
  - `emotion_change_rate` → `EmotionChangeRate`
  - `note_taking_trend` → `NoteTakingTrend`
  - `goal_input_delay` → `GoalInputDelay`
  - `routine_collapse_point` → `RoutineCollapsePoint`
  - `dropout_prediction` → `DropoutPrediction`
  - `intervention_timing` → `InterventionTiming`
  - `intervention_message` → `InterventionMessage`
  - `action_routine` → `ActionRoutine`
  - `immersion_break_point` → `ImmersionBreakPoint`
  - `dropout_cause` → `DropoutCause`

### 포괄형 질문 2: 집중 회복력 향상
- **핵심 온톨로지**: `ConcentrationRecovery`
- **데이터 소스 → 온톨로지 매핑**:
  - `return_success_rate` → `ReturnSuccessRate`
  - `return_delay_after_dropout` → `ReturnDelayAfterDropout`
  - `emotion_recovery_speed` → `EmotionRecoverySpeed`
  - `fatigue_accumulation_index` → `FatigueAccumulationIndex`
  - `routine_maintenance_stability` → `RoutineMaintenanceStability`
  - `return_loop_strategy` → `ReturnLoopStrategy`
  - `routine_adjustment` → `RoutineAdjustment`
  - `time_intensity_reward` → `TimeIntensityReward`
  - `concentration_recovery` → `ConcentrationRecovery`
  - `environment_variables` → `EnvironmentVariables`

### 포괄형 질문 3: 자기조절 루틴 구축
- **핵심 온톨로지**: `SelfRegulationRoutine`
- **데이터 소스 → 온톨로지 매핑**:
  - `weekly_dropout_trend` → `WeeklyDropoutTrend`
  - `emotion_rest_data` → `EmotionRestData`
  - `teacher_intervention_effect` → `TeacherInterventionEffect`
  - `feedback_tolerance` → `FeedbackTolerance`
  - `return_loop_completion_rate` → `ReturnLoopCompletionRate`
  - `self_regulation_routine` → `SelfRegulationRoutine`
  - `emotional_recovery_routine` → `EmotionalRecoveryRoutine`
  - `long_term_dropout_reduction` → `LongTermDropoutReduction`
  - `self_regulation_establishment` → `SelfRegulationEstablishment`
  - `feedback_system` → `FeedbackSystem`

### 온톨로지 관계 (Triples)
- `DropoutEarlyDetection` → requires → `PomodoroPattern`, `EmotionChangeRate`, `NoteTakingTrend`, `GoalInputDelay`, `RoutineCollapsePoint`, `DropoutPrediction`, `InterventionTiming`, `InterventionMessage`, `ActionRoutine`, `ImmersionBreakPoint`, `DropoutCause`
- `ConcentrationRecovery` → requires → `ReturnSuccessRate`, `ReturnDelayAfterDropout`, `EmotionRecoverySpeed`, `FatigueAccumulationIndex`, `RoutineMaintenanceStability`, `ReturnLoopStrategy`, `RoutineAdjustment`, `TimeIntensityReward`, `EnvironmentVariables`
- `SelfRegulationRoutine` → requires → `WeeklyDropoutTrend`, `EmotionRestData`, `TeacherInterventionEffect`, `FeedbackTolerance`, `ReturnLoopCompletionRate`, `EmotionalRecoveryRoutine`, `LongTermDropoutReduction`, `SelfRegulationEstablishment`, `FeedbackSystem`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 자세한 데이터 기반 질문 정의는 data_based_questions.js의 agent13 섹션을 참조하세요.
