개념노트 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **개념노트 데이터를 기반으로 개념 이해도와 학습 흐름을 해석하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 10 - Concept Notes** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 📚 3. 수학 학습 진도 정보 (1)

25. 개념별 취약 영역 기록

---

## 🧠 4. 학습 성향 및 습관 (3)

26. 개념 중심 vs 문제 중심
28. 반복 학습 선호도
38. 필기 습관 유무
39. 정리 도구(노션/노트 등) 사용 여부

---

## 🧾 6. 학습 이력 (1)

56. 개념 완성 이력

---

## 🧪 10. AI 분석 및 추론용 메타 정보 (1)

94. 선행-복습 최적 타이밍 분석

---

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 10의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 첫 개념 수업 설계
- **핵심 온톨로지**: `ConceptLearningPattern`
- **데이터 소스 → 온톨로지 매핑**:
  - `note_taking_amount` → `NoteTakingAmount`
  - `stay_time` → `StayTime`
  - `tts_usage` → `TTSUsage`
  - `revisit_pattern` → `RevisitPattern`
  - `step_by_step_understanding` → `StepByStepUnderstanding`
  - `concept_understanding_style` → `ConceptUnderstandingStyle`
  - `learning_preferences` → `LearningPreferences`
  - `textbook_composition` → `TextbookComposition`
  - `note_taking_habit` → `NoteTakingHabit`
  - `note_taking_preference` → `NoteTakingPreference`
  - `note_taking_tool` → `NoteTakingTool`

### 포괄형 질문 2: 개념 학습 루틴 최적화
- **핵심 온톨로지**: `ConceptRoutineOptimization`
- **데이터 소스 → 온톨로지 매핑**:
  - `concept_summary` → `ConceptSummary`
  - `understanding` → `ConceptUnderstanding`
  - `check` → `ConceptCheck`
  - `example` → `Example`
  - `representative_type` → `RepresentativeType`
  - `test` → `Test`
  - `tts_log` → `TTSLog`
  - `note_taking_log` → `NoteTakingLog`
  - `stay_log` → `StayLog`
  - `error_occurrence_section` → `ErrorOccurrenceSection`
  - `optimal_sequence` → `OptimalSequence`
  - `time_allocation` → `TimeAllocation`
  - `activity_form` → `ActivityForm`
  - `focus_block_structure` → `FocusBlockStructure`

### 포괄형 질문 3: 중장기 개념 학습 효율 향상
- **핵심 온톨로지**: `ConceptLearningSustainability`
- **데이터 소스 → 온톨로지 매핑**:
  - `error_cause` → `ErrorCause`
  - `routine_maintenance_rate` → `RoutineMaintenanceRate`
  - `understanding_deviation` → `UnderstandingDeviation`
  - `revisit_reason` → `RevisitReason`
  - `understanding_stability` → `UnderstandingStability`
  - `input_dependency` → `InputDependency`
  - `routine_sustainability_risk` → `RoutineSustainabilityRisk`
  - `sustainability_improvement_strategy` → `SustainabilityImprovementStrategy`
  - `habit_formation` → `HabitFormation`
  - `tool_usage` → `ToolUsage`
  - `feedback_loop` → `FeedbackLoop`

### 온톨로지 관계 (Triples)
- `ConceptLearningPattern` → requires → `NoteTakingAmount`, `StayTime`, `TTSUsage`, `RevisitPattern`, `StepByStepUnderstanding`, `ConceptUnderstandingStyle`, `LearningPreferences`, `TextbookComposition`, `NoteTakingHabit`, `NoteTakingPreference`, `NoteTakingTool`
- `ConceptRoutineOptimization` → requires → `ConceptSummary`, `ConceptUnderstanding`, `ConceptCheck`, `TTSLog`, `NoteTakingLog`, `StayLog`, `ErrorOccurrenceSection`, `OptimalSequence`, `TimeAllocation`, `ActivityForm`, `FocusBlockStructure`
- `ConceptLearningSustainability` → requires → `ErrorCause`, `RoutineMaintenanceRate`, `UnderstandingDeviation`, `RevisitReason`, `UnderstandingStability`, `InputDependency`, `RoutineSustainabilityRisk`, `SustainabilityImprovementStrategy`, `HabitFormation`, `ToolUsage`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
