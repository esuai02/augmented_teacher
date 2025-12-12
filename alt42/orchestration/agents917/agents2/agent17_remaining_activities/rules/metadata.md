잔여활동 조정 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **목표 대비 잔여 활동 식별 및 조정에 필요한 데이터**가 필요합니다. 아래는 **Agent 17 - Remaining Activities** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🎯 7. 목표 설정 정보 (3)

61. 단기 목표 (예: 숙제 완수)
62. 중기 목표 (예: 개념 완성)
64. 목표 우선순위

---

## 📍 2. 위치 및 환경 정보 (1)

12. 등하교 시간

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 17의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 학습 흐름 단절 원인 및 리듬 회복
- **핵심 온톨로지**: `FlowInterruptionRecovery`
- **데이터 소스 → 온톨로지 매핑**:
  - `remaining_time` → `RemainingTimeBased` (참조: `AvailableTime`)
  - `fatigue_level` → `FatigueLevel`
  - `emotion_status` → `EmotionStatus` (참조: `ref(agent05.current_emotion_code)`)
  - `concentration_flow` → `ConcentrationFlow` (참조: `ConcentrationLevel`, `FocusState`)
  - `bottleneck_cause` → `BottleneckIdentification` (참조: `BottleneckActivityDivision`)
  - `short_term_recovery_routine` → `RecoveryRoutine` (참조: `ShortTermRecoveryRoutine`)
  - `warmup_core_summary` → `RecoveryRoutineStructure` (예열-핵심-정리 구조)
  - `emotional_stability_device` → `EmotionalStabilityInterval` (참조: `EmotionalRecoveryRoutine`)
  - `flow_interruption_cause` → `FlowInterruptionCause` (참조: `RhythmBreakage`)
  - `rhythm_recovery_strategy` → `RhythmRecovery`

### 포괄형 질문 2: 활동 재배치 최적화
- **핵심 온톨로지**: `ActivityReallocation`
- **데이터 소스 → 온톨로지 매핑**:
  - `available_time` → `AvailableTime`
  - `cognitive_load` → `CognitiveLoad` (참조: `CognitiveBurden`)
  - `activity_difficulty` → `ActivityDifficulty`
  - `priority` → `Priority` (참조: `PrioritySetting`, `AssignmentPriority`)
  - `time_slot_reallocation` → `TimeSlotReallocation` (참조: `ScheduleAdjustment`)
  - `fatigue_prevention_routine` → `FatiguePreventionRoutine` (참조: `RestRoutine`, `FatigueLevel`)
  - `remaining_learning_time` → `RemainingLearningTime` (참조: `RemainingTimeBased`, `AvailableTime`)
  - `energy_level` → `EnergyLevel`
  - `sustainable_reallocation` → `SustainableReallocation` (참조: `RoutineFeasibility`)

### 포괄형 질문 3: 장기 학습 리듬 유지
- **핵심 온톨로지**: `LongTermRhythmMaintenance`
- **데이터 소스 → 온톨로지 매핑**:
  - `recent_rhythm_pattern` → `RhythmPattern` (참조: `RhythmAnalysis`)
  - `emotional_loop` → `EmotionalLoop` (참조: `ref(agent05.emotion_curve)`, `EmotionFirstLoop`)
  - `concentration_maintenance_time` → `ConcentrationMaintenanceTime` (참조: `FocusDuration`, `ConcentrationLevel`)
  - `routine_collapse_history` → `RoutineCollapseHistory` (참조: `RoutineCollapsePoint`, `RoutineCollapseCycle`)
  - `routine_return_persistence` → `RoutineReturnPersistence` (참조: `RoutineRecovery`, `RoutineContinuity`)
  - `recovery_pattern_for_today` → `RecoveryPatternForToday` (참조: `RecoveryRoutine`, `PersonalizedRecoveryRoutine`)
  - `long_term_rhythm_maintenance` → `LongTermRhythmStability`
  - `today_recovery_pattern` → `TodayRecoveryPattern` (참조: `RecoveryPatternForToday`)

### 온톨로지 관계 (Triples)
- `FlowInterruptionRecovery` → requires → `RemainingTimeBased`, `FatigueLevel`, `EmotionStatus`, `ConcentrationFlow`, `BottleneckIdentification`, `RecoveryRoutine`, `RecoveryRoutineStructure`, `EmotionalStabilityInterval`, `FlowInterruptionCause`, `RhythmRecovery`
- `ActivityReallocation` → requires → `AvailableTime`, `CognitiveLoad`, `ActivityDifficulty`, `Priority`, `TimeSlotReallocation`, `FatiguePreventionRoutine`, `RemainingLearningTime`, `EnergyLevel`, `SustainableReallocation`
- `LongTermRhythmMaintenance` → requires → `RhythmPattern`, `EmotionalLoop`, `ConcentrationMaintenanceTime`, `RoutineCollapseHistory`, `RoutineReturnPersistence`, `RecoveryPatternForToday`, `LongTermRhythmStability`, `TodayRecoveryPattern`

### 데이터 참조 구조
이 에이전트는 다음 마스터 소스의 데이터를 참조합니다:
- **감정/정서 데이터**: `ref(agent05.*)` - Agent 05 (학습감정 분석) 참조
- **루틴/리듬 데이터**: `ref(agent18.*)` - Agent 18 (시그너처 루틴 탐색) 참조

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
- 일부 데이터 소스는 다른 에이전트(Agent 05, Agent 18)의 데이터를 참조합니다.
