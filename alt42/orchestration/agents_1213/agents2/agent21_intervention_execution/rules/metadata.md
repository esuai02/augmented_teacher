개입 실행 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **개입을 실제로 실행하고 결과를 기록하며 효과를 모니터링하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 21 - Intervention Execution** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧪 10. AI 분석 및 추론용 메타 정보 (1)

99. 개입 전/후 효과 측정 데이터

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 21의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 개입 실행 최적화
- **핵심 온톨로지**: `InterventionExecutionOptimization`
- **데이터 소스 → 온톨로지 매핑**:
  - `recent_activity` → `RecentActivity` (참조: `ActivityRecord`)
  - `emotion_status_agent05` → `EmotionStatus` (참조: `ref(agent05.current_emotion_code)`)
  - `concentration_time_slot_agent01` → `ConcentrationTimeSlot` (참조: `ref(agent01.focus_time_window)`)
  - `intervention_plan_agent20` → `InterventionPlan` (참조: `ref(agent20.intervention_plan)`)
  - `intervention_intensity` → `InterventionIntensity` (참조: `InterventionTiming`)
  - `delivery_method` → `InterventionMethod` (참조: `InterventionDelivery`)
  - `expected_response_pattern` → `ExpectedResponsePattern` (참조: `ResponsePattern`)
  - `optimal_execution_method` → `OptimalExecutionMethod` (참조: `InterventionMethod`)
  - `optimal_timing` → `OptimalExecutionTime` (참조: `InterventionTiming`)
  - `optimal_intensity` → `InterventionIntensity` (참조: `InterventionTiming`)

### 포괄형 질문 2: 개입 조합 및 조정
- **핵심 온톨로지**: `InterventionCombination`
- **데이터 소스 → 온톨로지 매핑**:
  - `daily_message_limit` → `DailyMessageLimit` (참조: `MessageOverloadConflict`)
  - `chain_trigger` → `ChainTrigger` (참조: `DataTrigger`)
  - `predecessor_successor_relationship` → `PredecessorSuccessorRelationship` (참조: `InterventionPriority`)
  - `fatigue_minimization` → `FatigueMinimizationStrategy`
  - `concentration_change_minimization` → `ConcentrationChange` (참조: `ConcentrationChange`)
  - `execution_sequence` → `ExecutionSequence` (참조: `RuleExecutionOrder`)
  - `pending_interventions` → `PendingInterventions` (참조: `WaitingInterventions`)
  - `intervention_combination` → `InterventionCombination`
  - `natural_execution` → `NaturalExecution` (참조: `InterventionExecution`)

### 포괄형 질문 3: 개입 실행 전략 조정
- **핵심 온톨로지**: `InterventionStrategyAdjustment`
- **데이터 소스 → 온톨로지 매핑**:
  - `read_rate` → `ReadRate` (참조: `InterventionEffectiveness`)
  - `response_time` → `ResponseTime` (참조: `InterventionEffectiveness`)
  - `effectiveness_score` → `InterventionEffectiveness`
  - `behavior_change` → `BehaviorChange`
  - `emotion_recovery_speed` → `EmotionRecoverySpeed`
  - `next_intervention_tone` → `NextInterventionTone` (참조: `MessageTone`)
  - `next_intervention_time` → `NextInterventionTime` (참조: `InterventionTiming`)
  - `next_intervention_cycle` → `NextInterventionCycle` (참조: `InterventionTiming`)
  - `improvement_loop` → `ImprovementLoop` (참조: `InterventionStrategyAdjustment`)
  - `recent_intervention_response` → `RecentInterventionResponse` (참조: `ResponsePattern`)
  - `recent_intervention_performance` → `RecentInterventionPerformance` (참조: `InterventionEffectiveness`)
  - `strategy_adjustment` → `StrategyAdjustment` (참조: `InterventionStrategyAdjustment`)

### 포괄형 질문 4: 데이터 트리거 발생 시 즉시 개입 실행
- **핵심 온톨로지**: `DataTriggerIntervention`
- **데이터 소스 → 온톨로지 매핑**:
  - `trigger_type` → `TriggerType` (참조: `DataTrigger`)
  - `trigger_severity` → `TriggerSeverity` (참조: `DataTrigger`)
  - `calmness_drop` → `CalmnessDrop`
  - `note_delay` → `NoteDelay` (참조: `LearningDelay`)
  - `dropout_risk` → `DropoutRisk`
  - `emotion_status_agent05` → `EmotionStatus` (참조: `ref(agent05.current_emotion_code)`)
  - `concentration_time_slot_agent01` → `ConcentrationTimeSlot` (참조: `ref(agent01.focus_time_window)`)
  - `immediate_intervention_type` → `ImmediateIntervention`
  - `trigger_timing` → `TriggerTiming` (참조: `DataTrigger`)
  - `optimal_execution_timing` → `OptimalExecutionTime`
  - `intervention_intensity` → `InterventionIntensity` (참조: `InterventionTiming`)

### 포괄형 질문 5: 개입 계획 도착 후 실행 단계
- **핵심 온톨로지**: `InterventionPlanExecution`
- **데이터 소스 → 온톨로지 매핑**:
  - `intervention_plan_agent20` → `InterventionPlan` (참조: `ref(agent20.intervention_plan)`)
  - `planned_content` → `PlannedContent` (참조: `InterventionContent`)
  - `scheduled_timing` → `ScheduledTiming` (참조: `InterventionTiming`)
  - `planned_priority` → `PlannedPriority` (참조: `InterventionPriority`)
  - `pending_intervention_list` → `PendingInterventionList` (참조: `WaitingInterventions`)
  - `conflict_detection` → `ConflictDetection` (참조: `MessageOverloadConflict`)
  - `priority_readjustment_needed` → `PriorityReadjustmentNeeded` (참조: `InterventionPriority`)
  - `intervention_list_update` → `InterventionListUpdate` (참조: `PersonalInterventionList`)
  - `optimal_execution_timing` → `OptimalExecutionTime`
  - `execution_method` → `ExecutionMethod` (참조: `InterventionMethod`)

### 포괄형 질문 6: 메시지 과다 또는 충돌 상황 처리
- **핵심 온톨로지**: `MessageOverloadConflict`
- **데이터 소스 → 온톨로지 매핑**:
  - `daily_message_count` → `DailyMessageCount` (참조: `MessageOverloadConflict`)
  - `daily_message_limit` → `DailyMessageLimit` (참조: `MessageOverloadConflict`)
  - `limit_exceeded` → `LimitExceeded` (참조: `MessageOverloadConflict`)
  - `pending_interventions` → `PendingInterventions` (참조: `WaitingInterventions`)
  - `intervention_priority` → `InterventionPriority`
  - `chain_trigger_detected` → `ChainTriggerDetected` (참조: `ChainTrigger`)
  - `priority_reordering` → `PriorityReordering` (참조: `InterventionPriority`)
  - `teacher_approval_needed` → `TeacherApprovalNeeded` (참조: `TeacherDirectIntervention`)
  - `approval_criteria` → `ApprovalCriteria` (참조: `TeacherDirectIntervention`)

### 포괄형 질문 7: 집중 시간대 또는 활동 전환 직전 개입 실행
- **핵심 온톨로지**: `ConcentrationTimeIntervention`
- **데이터 소스 → 온톨로지 매핑**:
  - `next_concentration_time_slot` → `NextConcentrationTimeSlot` (참조: `ConcentrationTimeSlot`)
  - `current_time` → `CurrentTime` (참조: `TimeContext`)
  - `time_until_concentration` → `TimeUntilConcentration` (참조: `ConcentrationTimeSlot`)
  - `effective_intervention_types` → `EffectiveInterventionTypes` (참조: `InterventionType`)
  - `effectiveness_prediction_score` → `EffectivenessPrediction`
  - `concentration_time_interventions` → `ConcentrationTimeInterventions` (참조: `ConcentrationTimeIntervention`)
  - `selected_interventions` → `SelectedInterventions` (참조: `InterventionSelection`)
  - `execution_order` → `ExecutionOrder` (참조: `RuleExecutionOrder`)
  - `time_offset_calculation` → `TimeOffsetCalculation` (참조: `InterventionTiming`)

### 포괄형 질문 8: 선생님 직접 개입 필요 시 처리
- **핵심 온톨로지**: `TeacherDirectIntervention`
- **데이터 소스 → 온톨로지 매핑**:
  - `emotion_stability` → `EmotionStability`
  - `emotion_instability_detected` → `EmotionInstabilityDetected` (참조: `EmotionStability`)
  - `auto_intervention_inappropriate` → `AutoInterventionInappropriate` (참조: `TeacherDirectIntervention`)
  - `pending_interventions` → `PendingInterventions` (참조: `WaitingInterventions`)
  - `teacher_delivery_more_effective` → `TeacherDeliveryMoreEffective` (참조: `TeacherDirectIntervention`)
  - `effectiveness_comparison` → `EffectivenessComparison` (참조: `InterventionEffectiveness`)
  - `auto_intervention_hold` → `AutoInterventionHold` (참조: `TeacherDirectIntervention`)
  - `teacher_feedback_request` → `TeacherFeedbackRequest` (참조: `TeacherDirectIntervention`)
  - `intervention_status_change` → `InterventionStatusChange` (참조: `InterventionExecution`)

### 포괄형 질문 9: 효과성 검증 및 리포트 요청
- **핵심 온톨로지**: `InterventionEffectivenessVerification`
- **데이터 소스 → 온톨로지 매핑**:
  - `intervention_execution_history` → `InterventionExecutionHistory` (참조: `InterventionHistory`)
  - `read_pattern` → `ReadPattern` (참조: `ResponsePattern`)
  - `response_pattern` → `ResponsePattern`
  - `delivery_method` → `InterventionMethod` (참조: `InterventionDelivery`)
  - `goal_achievement_rate` → `GoalAchievementRate` (참조: `InterventionEffectiveness`)
  - `behavior_change_degree` → `BehaviorChangeDegree` (참조: `BehaviorChange`)
  - `emotion_recovery_speed` → `EmotionRecoverySpeed`
  - `effectiveness_score` → `InterventionEffectiveness`
  - `effective_patterns` → `EffectivePatterns` (참조: `InterventionEffectiveness`)
  - `ineffective_patterns` → `IneffectivePatterns` (참조: `InterventionEffectiveness`)
  - `improvement_recommendations` → `ImprovementRecommendations` (참조: `InterventionStrategyAdjustment`)
  - `next_intervention_improvements` → `NextInterventionImprovements` (참조: `InterventionStrategyAdjustment`)

### 온톨로지 관계 (Triples)
- `InterventionExecution` → requires → `InterventionPlan`, `InterventionExecutionOptimization`, `InterventionCombination`, `InterventionStrategyAdjustment`, `DataTriggerIntervention`, `InterventionPlanExecution`, `MessageOverloadConflict`, `ConcentrationTimeIntervention`, `TeacherDirectIntervention`, `InterventionEffectivenessVerification`
- `InterventionExecutionOptimization` → requires → `RecentActivity`, `EmotionStatus`, `ConcentrationTimeSlot`, `InterventionPlan`, `InterventionIntensity`, `InterventionMethod`, `ExpectedResponsePattern`, `OptimalExecutionMethod`, `OptimalExecutionTime`
- `InterventionCombination` → requires → `DailyMessageLimit`, `ChainTrigger`, `PredecessorSuccessorRelationship`, `FatigueMinimizationStrategy`, `ConcentrationChange`, `ExecutionSequence`, `PendingInterventions`, `InterventionCombination`, `NaturalExecution`
- `InterventionStrategyAdjustment` → requires → `ReadRate`, `ResponseTime`, `InterventionEffectiveness`, `BehaviorChange`, `EmotionRecoverySpeed`, `NextInterventionTone`, `NextInterventionTime`, `NextInterventionCycle`, `ImprovementLoop`, `RecentInterventionResponse`, `RecentInterventionPerformance`, `StrategyAdjustment`
- `DataTriggerIntervention` → requires → `TriggerType`, `TriggerSeverity`, `CalmnessDrop`, `NoteDelay`, `DropoutRisk`, `EmotionStatus`, `ConcentrationTimeSlot`, `ImmediateIntervention`, `TriggerTiming`, `OptimalExecutionTime`, `InterventionIntensity`
- `InterventionPlanExecution` → requires → `InterventionPlan`, `PlannedContent`, `ScheduledTiming`, `PlannedPriority`, `PendingInterventionList`, `ConflictDetection`, `PriorityReadjustmentNeeded`, `InterventionListUpdate`, `OptimalExecutionTime`, `ExecutionMethod`
- `MessageOverloadConflict` → requires → `DailyMessageCount`, `DailyMessageLimit`, `LimitExceeded`, `PendingInterventions`, `InterventionPriority`, `ChainTriggerDetected`, `PriorityReordering`, `TeacherApprovalNeeded`, `ApprovalCriteria`
- `ConcentrationTimeIntervention` → requires → `NextConcentrationTimeSlot`, `CurrentTime`, `TimeUntilConcentration`, `EffectiveInterventionTypes`, `EffectivenessPrediction`, `ConcentrationTimeInterventions`, `SelectedInterventions`, `ExecutionOrder`, `TimeOffsetCalculation`
- `TeacherDirectIntervention` → requires → `EmotionStability`, `EmotionInstabilityDetected`, `AutoInterventionInappropriate`, `PendingInterventions`, `TeacherDeliveryMoreEffective`, `EffectivenessComparison`, `AutoInterventionHold`, `TeacherFeedbackRequest`, `InterventionStatusChange`
- `InterventionEffectivenessVerification` → requires → `InterventionExecutionHistory`, `ReadPattern`, `ResponsePattern`, `InterventionMethod`, `GoalAchievementRate`, `BehaviorChangeDegree`, `EmotionRecoverySpeed`, `InterventionEffectiveness`, `EffectivePatterns`, `IneffectivePatterns`, `ImprovementRecommendations`, `NextInterventionImprovements`

### 다른 에이전트에서 참조하는 Agent 21 데이터
- `agent_data.agent21_data.intervention_execution_history` → `InterventionExecutionHistory` (온톨로지)
- `agent_data.agent21_data.effectiveness_score` → `InterventionEffectiveness` (온톨로지)
- `agent_data.agent21_data.read_rate` → `ReadRate` (온톨로지)
- `agent_data.agent21_data.response_time` → `ResponseTime` (온톨로지)

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
- Agent 20(개입준비)에서 전달받은 `InterventionPlan` 데이터를 기반으로 실행합니다.
- Agent 05(학습감정)의 `EmotionStatus` 데이터를 참조하여 개입 강도를 조정합니다.
- Agent 01(온보딩)의 `ConcentrationTimeSlot` 데이터를 참조하여 최적 타이밍을 결정합니다.
