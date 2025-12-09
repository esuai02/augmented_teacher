시그너처 루틴 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **개인 최적 학습 루틴을 발견하고 정교화하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 18 - Signature Routine** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧠 4. 학습 성향 및 습관 (1)

29. 집중 시간 평균
32. 학습 루틴 시간대

---

## 🎯 7. 목표 설정 정보 (1)

70. 목표 기반 루틴 이행률

---

## 🧪 10. AI 분석 및 추론용 메타 정보 (2)

95. 루틴 유지 성공률
100. 시그너처 루틴 매칭 결과

---

## 📊 Agent 18 데이터 기반 질문에 필요한 데이터 소스 (온톨로지 매핑)

### 포괄형 질문 1: 몰입 패턴 및 감정 리듬 종합 분석

**필요 데이터 소스:**
- `onboarding_data` → `OnboardingInfo` (온톨로지)
- `latest_preference` → `LatestPreferenceInfo` (온톨로지)
- `immersion_psychological_trigger` → `ImmersionPsychologicalTrigger` (온톨로지 추가됨)
- `emotion_trigger` → `EmotionTrigger` (온톨로지 추가됨)
- `environment_trigger` → `EnvironmentTrigger` (온톨로지 추가됨)
- `behavior_trigger` → `BehaviorTrigger` (온톨로지 추가됨)
- `start_routine` → `StartRoutine` (온톨로지 추가됨)
- `concentration_routine` → `ConcentrationRoutine` (온톨로지 추가됨)
- `recovery_routine` → `RecoveryRoutine` (온톨로지 기존)
- `signature_routine_draft` → `SignatureRoutineDraft` (온톨로지 추가됨)
- `natural_immersion_condition` → `NaturalImmersionCondition` (온톨로지 추가됨)
- `current_immersion_point` → `ImmersionBreakPoint` (온톨로지 기존)

**온톨로지 매핑:** `SignatureRoutineDiscovery`

### 포괄형 질문 2: 루틴 유지 및 강화 요소

**필요 데이터 소스:**
- `learning_emotion_curve` → `LearningEmotionCurve` (온톨로지 추가됨)
- `immersion_duration` → `ImmersionDuration` (온톨로지 추가됨)
- `reinforcement_feedback_response` → `ReinforcementFeedbackResponse` (온톨로지 추가됨)
- `reward_loop_design` → `RewardLoopDesign` (온톨로지 추가됨)
- `recovery_feedback_strategy` → `RecoveryFeedbackStrategy` (온톨로지 추가됨)
- `emotional_reinforcement` → `EmotionalReinforcement` (온톨로지 추가됨)
- `interaction_method_for_routine` → `InteractionMethodForRoutine` (온톨로지 추가됨)

**온톨로지 매핑:** `RoutineMaintenanceReinforcement`

### 포괄형 질문 3: 루틴 진화 경로 설계

**필요 데이터 소스:**
- `routine_repetition_stability` → `RoutineRepetitionStability` (온톨로지 추가됨)
- `emotion_amplitude` → `EmotionAmplitude` (온톨로지 추가됨)
- `cognitive_load_pattern` → `CognitiveLoadPattern` (온톨로지 추가됨)
- `emotional_correction_point` → `EmotionalCorrectionPoint` (온톨로지 추가됨)
- `cognitive_correction_point` → `CognitiveCorrectionPoint` (온톨로지 추가됨)
- `long_term_immersion_growth` → `LongTermImmersionGrowth` (온톨로지 추가됨)
- `signature_routine_evolution` → `RoutineEvolutionStage` (온톨로지 기존)

**온톨로지 매핑:** `RoutineEvolutionPath`

### 다른 에이전트에서 참조하는 Agent 18 데이터

- `agent_data.agent18_data.signature_routine` → `SignatureRoutine` (온톨로지 기존)
- `agent_data.agent18_data.routine_stability` → `RoutineStability` (온톨로지 추가됨)

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 모든 데이터 소스는 `alphatutor_ontology.owl` 파일에 정의되어 있으며, 2025-01-21 기준으로 업데이트되었습니다.
