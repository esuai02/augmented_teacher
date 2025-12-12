문제 재정의 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **문제의 원인을 파악하고 본질적 질문과 개선 방향을 도출하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 15 - Problem Redefinition** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🎯 7. 목표 설정 정보 (1)

69. 목표 실패 원인 분석 능력

---

**참고**: 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 15의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 반복 패턴 기반 문제 재정의 및 근본 원인 분석
- **핵심 온톨로지**: `ProblemRedefinition`, `MathUnitVulnerability`, `MathErrorTypeClassification`, `StudentLevelDifferentiation`, `AcademyContext`, `RootCauseAnalysis`
- **데이터 소스 → 온톨로지 매핑**:
  - `recent_2weeks_performance` → `RecentPerformance`
  - `score_decline` → `ScoreDecline`
  - `goal_completion_rate` → `GoalCompletionRate`
  - `immersion_indicator` → `ImmersionIndicator`
  - `math_unit_vulnerability` → `MathUnitVulnerability`
  - `prerequisite_unit_completion` → `PrerequisiteUnitAnalysis`
  - `related_units_impact` → `MathUnitVulnerability`
  - `math_unit_relations` → `PrerequisiteUnitAnalysis`
  - `student_level.overall_level` → `StudentLevelDifferentiation`
  - `student_level.unit_level` → `StudentLevelDifferentiation`
  - `student_level.recent_trend` → `StudentLevelDifferentiation`
  - `math_error_types.calculation_error` → `MathErrorTypeClassification`
  - `math_error_types.concept_error` → `MathErrorTypeClassification`
  - `math_error_types.application_error` → `MathErrorTypeClassification`
  - `repeated_error_pattern` → `RepeatedErrorPattern`
  - `progress_delay_pattern` → `ProgressDelayPattern`
  - `emotion_fluctuation_pattern` → `EmotionFluctuation`
  - `cognitive_factor` → `RootCauseAnalysis`
  - `emotional_factor` → `RootCauseAnalysis`
  - `habit_factor` → `RootCauseAnalysis`
  - `environment_factor` → `RootCauseAnalysis`
  - `diagnostic_code` → `DiagnosticCode`
  - `priority_3` → `Priority3`
  - `improvement_scenario` → `ImprovementScenario`
  - `standard_diagnosis_codes` → `StandardDiagnosisItem`
  - `academy_context.academy_class_understanding` → `AcademyContext`
  - `academy_context.academy_homework_completion_rate` → `AcademyContext`
  - `academy_context.academy_progress` → `AcademyContext`

### 포괄형 질문 2: 루틴 붕괴 원인 진단 및 표준코드 분류
- **핵심 온톨로지**: `RoutineCollapseRecovery`, `MathStudyTimeAllocation`, `TimeManagementFailure`, `MathLearningStyleMismatch`, `StandardCodeClassification`
- **데이터 소스 → 온톨로지 매핑**:
  - `agent_data.agent07_data.pomodoro_completion_rate` → `RoutineCollapseRecovery`
  - `agent_data.agent14_data.actual_progress_vs_planned` → `RoutineCollapseRecovery`
  - `agent_data.agent09_data.learning_routine` → `RoutineCollapseRecovery`
  - `unit_study_time_allocation` → `MathStudyTimeAllocation`
  - `agent_data.agent09_data.planning_time` → `TimeManagementFailure`
  - `agent_data.agent09_data.actual_time` → `TimeManagementFailure`
  - `time_management_issue` → `TimeManagementFailure`
  - `strategy_mismatch` → `MathLearningStyleMismatch`
  - `emotional_rhythm_issue` → `StandardCodeClassification`
  - `agent_data.agent01_data.math_learning_style` → `MathLearningStyleMismatch`
  - `agent_data.agent04_data.actual_learning_behavior` → `MathLearningStyleMismatch`
  - `interaction_tone` → `StandardCodeClassification`
  - `intervention_timing` → `StandardCodeClassification`

### 포괄형 질문 3: 구조적 문제 재정의 및 협력 해결 방안
- **핵심 온톨로지**: `StructuralProblemDiagnosis`, `ComprehensiveMathRedefinition`, `CollaborativeProblemSolving`, `PrerequisiteUnitAnalysis`, `MathAnxietyRedefinition`, `MathRecoveryFailure`
- **데이터 소스 → 온톨로지 매핑**:
  - `recent_2weeks_performance` → `StructuralProblemDiagnosis`
  - `score_decline` → `StructuralProblemDiagnosis`
  - `progress_decline` → `StructuralProblemDiagnosis`
  - `emotion_stability_decline` → `StructuralProblemDiagnosis`
  - `math_unit_vulnerability` → `ComprehensiveMathRedefinition`
  - `math_error_types` → `ComprehensiveMathRedefinition`
  - `student_level` → `ComprehensiveMathRedefinition`
  - `academy_context` → `ComprehensiveMathRedefinition`
  - `core_diagnostic_code_1` → `CollaborativeProblemSolving`
  - `core_diagnostic_code_2` → `CollaborativeProblemSolving`
  - `core_diagnostic_code_3` → `CollaborativeProblemSolving`
  - `expected_risk` → `CollaborativeProblemSolving`
  - `intervention_effect` → `CollaborativeProblemSolving`
  - `monitoring_variables` → `CollaborativeProblemSolving`
  - `prerequisite_unit_completion` → `PrerequisiteUnitAnalysis`
  - `agent_data.agent05_data.math_anxiety_level` → `MathAnxietyRedefinition`
  - `agent_data.agent05_data.motivation_level` → `MathAnxietyRedefinition`
  - `agent_data.agent12_data.recovery_rate` → `MathRecoveryFailure`
  - `agent_data.agent12_data.concentration_recovery` → `MathRecoveryFailure`
