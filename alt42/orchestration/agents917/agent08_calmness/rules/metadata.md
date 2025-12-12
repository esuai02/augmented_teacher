침착도 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **침착도 지표로 학습 수행 적합도 판단에 필요한 데이터**가 필요합니다. 아래는 **Agent 08 - Calmness** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧠 4. 학습 성향 및 습관 (1)

37. 실수 vs 개념 미해결 비율

---

## 🧪 10. AI 분석 및 추론용 메타 정보 (1)

93. 반복 실수 유형

---

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 08의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 사고 속도와 판단 균형 분석
- **핵심 온톨로지**: `ThinkingSpeedBalance`
- **데이터 소스 → 온톨로지 매핑**:
  - `solving_time` → `SolvingTime`
  - `answer_selection_pattern` → `AnswerSelectionPattern`
  - `correct_rate` → `CorrectRate`
  - `emotion_stability` → `EmotionStability` (Agent 05)
  - `calmness_score` → `CalmnessScore`
  - `thinking_routine` → `ThinkingRoutine`
  - `pause_strategy` → `PauseStrategy`
  - `fatigue_level` → `FatigueLevel`
  - `thinking_speed` → `ThinkingSpeed`

### 포괄형 질문 2: 사고 패턴 강화 및 교정
- **핵심 온톨로지**: `ThinkingPattern`
- **데이터 소스 → 온톨로지 매핑**:
  - `error_pattern` → `ErrorPattern`
  - `confidence_signal` → `ConfidenceSignal`
  - `over_attempt_ratio` → `OverAttemptRatio`
  - `thinking_omission_rate` → `ThinkingOmissionRate`
  - `intuitive_judgment` → `IntuitiveJudgment`
  - `uncertainty_avoidance` → `UncertaintyAvoidance`
  - `omission_thinking` → `OmissionThinking`
  - `feedback_strategy` → `FeedbackStrategy`
  - `self_verification_questions` → `SelfVerificationQuestions`
  - `evidence_training` → `EvidenceTraining`
  - `time_management_routine` → `TimeManagementRoutine`

### 포괄형 질문 3: 중장기 사고 습관 개선
- **핵심 온톨로지**: `ThinkingHabitImprovement`
- **데이터 소스 → 온톨로지 매핑**:
  - `calmness_trend` → `CalmnessTrend`
  - `confidence_trend` → `ConfidenceTrend`
  - `correct_rate_trend` → `CorrectRateTrend`
  - `emotion_data` → `EmotionData` (Agent 05)
  - `fatigue_data` → `FatigueData`
  - `concentration_data` → `ConcentrationData`
  - `thinking_habit_improvement` → `ThinkingHabitImprovement`
  - `training_plan` → `TrainingPlan`
  - `improvement_stages` → `ImprovementStages`
  - `habit_formation` → `HabitFormation`

### 온톨로지 관계 (Triples)
- `ThinkingSpeedBalance` → requires → `SolvingTime`, `AnswerSelectionPattern`, `CorrectRate`, `EmotionStability`, `CalmnessScore`, `ThinkingRoutine`, `PauseStrategy`, `ThinkingSpeed`, `FatigueLevel`
- `ThinkingPattern` → requires → `ErrorPattern`, `ConfidenceSignal`, `OverAttemptRatio`, `ThinkingOmissionRate`, `IntuitiveJudgment`, `UncertaintyAvoidance`, `OmissionThinking`, `FeedbackStrategy`, `SelfVerificationQuestions`, `EvidenceTraining`, `TimeManagementRoutine`
- `ThinkingHabitImprovement` → requires → `CalmnessTrend`, `ConfidenceTrend`, `CorrectRateTrend`, `EmotionData`, `FatigueData`, `ConcentrationData`, `TrainingPlan`, `ImprovementStages`, `HabitFormation`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 감정 안정도 데이터는 Agent 05에서 관리됩니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
