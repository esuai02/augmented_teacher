문제노트 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **오답 패턴을 분석하여 취약 영역을 규명하고 복습 전략을 설계하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 11 - Problem Notes** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧠 4. 학습 성향 및 습관 (1)

36. 문제 오답 정리 습관

---

## 🧾 6. 학습 이력 (1)

57. 누적 오답노트 보유 여부

---

## 🧩 9. 시스템 연계 정보 (1)

86. 문제풀이 로그 트래킹

---

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 11의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 오답노트 기반 사고습관 분석
- **핵심 온톨로지**: `ThinkingHabitFromNotes`
- **데이터 소스 → 온톨로지 매핑**:
  - `note_writing_method` → `NoteWritingMethod`
  - `note_amount` → `NoteAmount`
  - `note_time` → `NoteTime`
  - `reflection_text` → `ReflectionText`
  - `error_cause_record` → `ErrorCauseRecord`
  - `self_understanding_level` → `SelfUnderstandingLevel`
  - `error_awareness` → `ErrorAwareness`
  - `error_review_method` → `ErrorReviewMethod`
  - `feedback_tone` → `FeedbackTone`
  - `review_cycle` → `ReviewCycle`

### 포괄형 질문 2: 복습 루틴 및 교사 개입 최적화
- **핵심 온톨로지**: `ReviewRoutineOptimization`
- **데이터 소스 → 온톨로지 매핑**:
  - `reflection_score` → `ReflectionScore`
  - `completeness_score` → `CompletenessScore`
  - `error_type` → `ErrorType`
  - `review_effectiveness` → `ReviewEffectiveness`
  - `review_cycle` → `ReviewCycle`
  - `teacher_feedback_intensity` → `TeacherFeedbackIntensity`
  - `ai_feedback_frequency` → `AIFeedbackFrequency`
  - `routine_stability` → `RoutineStability`
  - `routine_persistence` → `RoutinePersistence`
  - `intervention_direction` → `InterventionDirection`

### 포괄형 질문 3: 중장기 성장 루프 형성
- **핵심 온톨로지**: `GrowthLoopFormation`
- **데이터 소스 → 온톨로지 매핑**:
  - `error_type_distribution` → `ErrorTypeDistribution`
  - `reflection_depth_change` → `ReflectionDepthChange`
  - `review_effect_duration` → `ReviewEffectDuration`
  - `signature_routine_level` → `SignatureRoutineLevel`
  - `routine_evolution_stage` → `RoutineEvolutionStage`
  - `next_stage_guide` → `NextStageGuide`
  - `narrative_assessment_frequency` → `NarrativeAssessmentFrequency`
  - `error_summary_method` → `ErrorSummaryMethod`
  - `retry_interval` → `RetryInterval`

### 온톨로지 관계 (Triples)
- `ThinkingHabitFromNotes` → requires → `NoteWritingMethod`, `NoteAmount`, `NoteTime`, `ReflectionText`, `ErrorCauseRecord`, `SelfUnderstandingLevel`, `ErrorAwareness`, `ErrorReviewMethod`, `FeedbackTone`, `ReviewCycle`
- `ReviewRoutineOptimization` → requires → `ReflectionScore`, `CompletenessScore`, `ErrorType`, `ReviewEffectiveness`, `ReviewCycle`, `TeacherFeedbackIntensity`, `AIFeedbackFrequency`, `RoutineStability`, `RoutinePersistence`, `InterventionDirection`
- `GrowthLoopFormation` → requires → `ErrorTypeDistribution`, `ReflectionDepthChange`, `ReviewEffectDuration`, `SignatureRoutineLevel`, `RoutineEvolutionStage`, `NextStageGuide`, `NarrativeAssessmentFrequency`, `ErrorSummaryMethod`, `RetryInterval`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
