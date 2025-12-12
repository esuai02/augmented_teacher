현재 위치 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **학습 활동 흐름 속에서 현재 위치를 정교하게 계산하는데 필요한 데이터**가 필요합니다. 아래는 **Agent 14 - Current Position** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 📚 3. 수학 학습 진도 정보 (6)

16. 개념 진도
17. 심화 진도
19. 학년 대비 선행 진도 정도
20. 단원별 진도표
21. 문제집 완료율

---

## 🧩 9. 시스템 연계 정보 (1)

88. 진도 자동 측정 도구 연계

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 14의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 현재 학습 진행 상태 정밀 진단
- **핵심 온톨로지**: `CurrentLearningPosition`
- **데이터 소스 → 온톨로지 매핑**:
  - `math_diary_record` → `MathDiaryRecord`
  - `progress_rate` → `ProgressRate`
  - `delay_pattern` → `DelayPattern`
  - `emotion_curve` → `EmotionCurve`
  - `pomodoro_rhythm` → `PomodoroRhythm`
  - `current_position` → `CurrentPosition`
  - `break_point` → `BreakPoint`
  - `speed_interruption` → `SpeedInterruption`
  - `understanding_interruption` → `UnderstandingInterruption`
  - `comprehensive_diagnosis` → `ComprehensiveDiagnosis`
  - `current_status` → `CurrentStatus`

### 포괄형 질문 2: 학습 단계별 강약점 구조적 정리
- **핵심 온톨로지**: `LearningStageStrengthWeakness`
- **데이터 소스 → 온톨로지 매핑**:
  - `unit_by_unit_data` → `UnitByUnitData`
  - `difficulty_level_data` → `DifficultyLevelData`
  - `stage_by_stage_data` → `StageByStageData`
  - `understanding_rate` → `UnderstandingRate`
  - `progress_rate` → `ProgressRate`
  - `time_spent` → `TimeSpent`
  - `error_type` → `ErrorType`
  - `bottleneck_identification` → `BottleneckIdentification`
  - `smooth_section_identification` → `SmoothSectionIdentification`
  - `strength_weakness_analysis` → `StrengthWeaknessAnalysis`
  - `structural_summary` → `StructuralSummary`

### 포괄형 질문 3: 다음 수업 및 개입 방향 설계
- **핵심 온톨로지**: `NextClassInterventionDesign`
- **데이터 소스 → 온톨로지 매핑**:
  - `progress_rate` → `ProgressRate`
  - `emotion_curve` → `EmotionCurve`
  - `rhythm_score` → `RhythmScore`
  - `risk_index` → `RiskIndex`
  - `decision_logic` → `DecisionLogic`
  - `next_class_direction` → `NextClassDirection`
  - `current_position_based_design` → `CurrentPositionBasedDesign`
  - `intervention_direction` → `InterventionDirection`

### 온톨로지 관계 (Triples)
- `CurrentLearningPosition` → requires → `MathDiaryRecord`, `ProgressRate`, `DelayPattern`, `EmotionCurve`, `PomodoroRhythm`, `CurrentPosition`, `BreakPoint`, `SpeedInterruption`, `UnderstandingInterruption`, `ComprehensiveDiagnosis`, `CurrentStatus`
- `LearningStageStrengthWeakness` → requires → `UnitByUnitData`, `DifficultyLevelData`, `StageByStageData`, `UnderstandingRate`, `ProgressRate`, `TimeSpent`, `ErrorType`, `BottleneckIdentification`, `SmoothSectionIdentification`, `StrengthWeaknessAnalysis`, `StructuralSummary`
- `NextClassInterventionDesign` → requires → `ProgressRate`, `EmotionCurve`, `RhythmScore`, `RiskIndex`, `DecisionLogic`, `NextClassDirection`, `CurrentPositionBasedDesign`, `InterventionDirection`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 자세한 데이터 기반 질문 정의는 data_based_questions.js의 agent14 섹션을 참조하세요.
