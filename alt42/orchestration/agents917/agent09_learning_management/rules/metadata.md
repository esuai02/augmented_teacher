학습관리 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **출결, 목표, 포모도로, 오답노트, 시험 패턴을 종합하여 학습관리 전략을 도출하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 09 - Learning Management** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧠 4. 학습 성향 및 습관 (1)

31. 포모도로 경험 유무

---

## 🧾 6. 학습 이력 (1)

60. 학기별 학습 강도 변화

---

## 🧩 9. 시스템 연계 정보 (4)

82. 출결 체크 방식
83. 온라인 수업 수강 시간
87. 포모도로 타이머 데이터

---

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 09의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 학습관리 취약점 진단
- **핵심 온톨로지**: `LearningManagementWeakness`
- **데이터 소스 → 온톨로지 매핑**:
  - `data_generation_frequency` → `DataGenerationFrequency`
  - `routine_maintenance_rate` → `RoutineMaintenanceRate`
  - `pomodoro_missing_rate` → `PomodoroMissingRate`
  - `emotion_fluctuation` → `EmotionFluctuation`
  - `system_reliability` → `SystemReliability`
  - `management_decline_pattern` → `ManagementDeclinePattern`
  - `root_cause_analysis` → `RootCauseAnalysis`
  - `risk_level` → `RiskLevel`
  - `risk_summary` → `RiskSummary`

### 포괄형 질문 2: 피드백 루프 재설계
- **핵심 온톨로지**: `FeedbackLoopRedesign`
- **데이터 소스 → 온톨로지 매핑**:
  - `data_imbalance` → `DataImbalance`
  - `routine_collapse_cycle` → `RoutineCollapseCycle`
  - `automation_resistance` → `AutomationResistance`
  - `planning_feedback` → `PlanningFeedback`
  - `execution_feedback` → `ExecutionFeedback`
  - `verification_feedback` → `VerificationFeedback`
  - `behavior_strategy` → `BehaviorStrategy`
  - `notification_method` → `NotificationMethod`
  - `feedback_priority` → `FeedbackPriority`

### 포괄형 질문 3: 관리 안정성 향상
- **핵심 온톨로지**: `ManagementStability`
- **데이터 소스 → 온톨로지 매핑**:
  - `data_consistency` → `DataConsistency`
  - `routine_success_rate` → `RoutineSuccessRate`
  - `feedback_reflection_rate` → `FeedbackReflectionRate`
  - `system_dependency` → `SystemDependency`
  - `recovery_speed` → `RecoverySpeed`
  - `habitual_routine` → `HabitualRoutine`
  - `automation_intervention_points` → `AutomationInterventionPoints`
  - `stability_improvement_scenario` → `StabilityImprovementScenario`
  - `improvement_stages` → `ImprovementStages`

### 온톨로지 관계 (Triples)
- `LearningManagementWeakness` → requires → `DataGenerationFrequency`, `RoutineMaintenanceRate`, `PomodoroMissingRate`, `EmotionFluctuation`, `SystemReliability`, `ManagementDeclinePattern`, `RootCauseAnalysis`
- `LearningManagementWeakness` → generates → `RiskLevel`, `RiskSummary`
- `FeedbackLoopRedesign` → requires → `DataImbalance`, `RoutineCollapseCycle`, `AutomationResistance`, `PlanningFeedback`, `ExecutionFeedback`, `VerificationFeedback`, `BehaviorStrategy`, `NotificationMethod`, `FeedbackPriority`
- `ManagementStability` → requires → `DataConsistency`, `RoutineSuccessRate`, `FeedbackReflectionRate`, `SystemDependency`, `RecoverySpeed`, `HabitualRoutine`, `AutomationInterventionPoints`, `StabilityImprovementScenario`, `ImprovementStages`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
