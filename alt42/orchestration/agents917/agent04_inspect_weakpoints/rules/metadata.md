# Agent 04 - Problem Activity 메타데이터

문제 활동 분석 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **학습활동별 페르소나 분석 및 맞춤 행동유도에 필요한 데이터**가 필요합니다. 

이 문서는 **Agent 04 - Inspect Weakpoints** 에이전트가 실제로 '문제활동을 분석·판단·조정'하기 위해 직접 참조하거나 생성해야 할 데이터를 **8가지 활동 영역별로 정리**한 것입니다.

---

## 📌 데이터 참조 구조

이 에이전트는 다음 마스터 소스의 데이터를 참조합니다:
- **감정/정서 데이터**: `ref(agent05.*)` - Agent 05 (학습감정 분석) 참조
- **루틴/리듬 데이터**: `ref(agent18.*)` - Agent 18 (시그너처 루틴 탐색) 참조

*(페르소나 분석, 몰입유도, 난이도 조정, 루틴 최적화 중심)*

---

## 🎯 8가지 활동 영역별 메타데이터

### ① 개념이해 (Concept Understanding)

**활동 단계**: 이해 → 정리 → 적용

**필수 데이터 항목**:
- 개념이해 단계별 멈춤 지점 (`concept_stage`, `pause_frequency`, `pause_stage`)
- TTS 듣기 중 시선집중도 및 필기패턴 (`gaze_attention_score`, `note_taking_pattern_change`)
- 개념쌍 혼동 패턴 (`concept_confusion_detected`, `confusion_type`)
- 학습스타일과 개념공부 방식 적합성 (`persona_type`, `current_method`, `method_persona_match_score`)
- 시각 자료 반응도 (`visual_content_present`, `visual_response_score`)
- 텍스트 정리 vs 예제 확인 선호도 (`text_organization_score`, `example_verification_score`)
- 최적 활동 조합 (TTS, 필기, 예제풀이) (`immersion_score_by_combination`, `best_combination`)
- 지루함/집중 이탈 시점 (`boredom_detected`, `attention_drop_time`, `emotion_state`)
- 피드백 유형 효과성 (`feedback_types_tested`, `feedback_effectiveness_score`)

**관련 룰**: `CU_A1` ~ `CU_C3` (9개)

---

### ② 유형학습 (Type Learning)

**활동 단계**: 순서 선택 → 방법 선택 → 반복 학습 → 피드백

**필수 데이터 항목**:
- 문제풀이 순서 효율성 (`problem_sequence`, `sequence_efficiency_score`)
- 난이도 변화에 따른 풀이속도/집중도 일정성 (`difficulty_change`, `speed_consistency_score`, `focus_consistency_score`)
- 세션 단계별 집중 지속시간 (`session_stage`, `focus_duration_by_stage`)
- 접근전략 유형 (공식회상형/유추형/비교형) (`approach_strategy`, `strategy_usage_frequency`)
- 동일 오류 반복 경향 (`repeated_error_count`, `error_type`)
- 포기/지루함 발생 시점 (`repetition_count`, `giveup_or_boredom_detected`, `detection_timing`)
- 높은 몰입감 활동 식별 (`sub_activity_type`, `immersion_score_by_activity`)
- 난이도 상승 시 감정 반응 (`difficulty_increase`, `emotion_response`)
- 피드백 유형별 재도전 효과성 (`feedback_types`, `retry_effectiveness_score`)

**관련 룰**: `TL_A1` ~ `TL_C3` (9개)

---

### ③ 문제풀이 (Problem Solving)

**활동 단계**: 해석 → 시작 → 과정 → 마무리 → 검토

**필수 데이터 항목**:
- 핵심 조건과 불필요한 정보 구분 능력 (`problem_reading_stage`, `key_condition_identification_score`)
- 문제 전체 구조적 파악 습관 (`structural_analysis_before_solving`)
- 전략 전환 시도 여부 (`solving_stage`, `stuck_detected`, `strategy_switch_attempted`)
- 인지부하 신호 (시선 이탈, 멈춤, 표정 변화) (`gaze_detection`, `gaze_away_frequency`, `pause_frequency`)
- 풀이 과정 감정 상태 (긴장감/피로감/집중감) (`emotion_during_solving`, `emotion_intensity`)
- 풀이 시간 대비 효율 유지 여부 (`solving_duration`, `efficiency_trend`)
- 자기 설명 능력 (`self_explanation_score`)
- 검토 루틴 일정성 (`review_routine_consistency`)
- 확신도와 실제 결과 일치도 (`self_confidence_level`, `actual_result`, `confidence_accuracy_match_score`)

**관련 룰**: `PS_A1` ~ `PS_C3` (9개)

---

### ④ 오답노트 (Error Notes)

**활동 단계**: 오답 발생 → 원인 분석 → 재시도 → 피드백 수용 → 행동 전이

**필수 데이터 항목**:
- 오답 원인 분류 (개념오류/계산실수/문제이해 착오) (`error_occurred`, `error_category`)
- 오답 직전 풀이 행동 패턴 (`pre_error_behavior`)
- 오답 후 성찰 습관 (`post_error_reflection`)
- 오답 인식 후 사고 우선순위 (`error_recognized`, `reflection_focus`)
- 재시도 시 감정 반응 (`retry_attempted`, `retry_emotion`)
- 재풀이 시 전략 변화 여부 (`same_type_retry`, `strategy_change_detected`)
- 피드백 수용 후 반응 변화 (`feedback_provided`, `feedback_type`, `reception_indicators`)
- 피드백 → 행동 전이율 (`action_taken`, `feedback_to_action_rate`)
- 개선된 패턴의 다음 단원 유지 여부 (`improved_pattern_detected`, `next_unit_maintenance`)

**관련 룰**: `EN_A1` ~ `EN_C3` (9개)

---

### ⑤ 질의응답 (Q&A)

**활동 단계**: 의문 발생 → 질문 생성 → 답변 수용 → 사고 전환

**필수 데이터 항목**:
- 질문 발생 상황 (문제 막힘/개념 혼동/복습 중) (`question_occurred`, `question_context`)
- 질문 표현 타이밍 (즉시/지연) (`expression_timing`, `delay_duration`)
- 세션 단계별 질문 빈도 (`session_stage`, `question_frequency_by_stage`)
- 질문 유형 (개념확인형/이유탐구형) (`question_type`)
- 질문 복잡도 (단순/비교/응용) (`question_complexity`)
- 반복 질문 패턴 (`repeated_question_count`, `question_topic`)
- 답변 만족도 (`answer_provided`, `satisfaction_score`)
- 추가 질문/요약 정리 시도 (`follow_up_action`)
- 사고 전환 순간 탐지 (`insight_moment_detected`, `insight_indicators`)

**관련 룰**: `QA_A1` ~ `QA_C3` (9개)

---

### ⑥ 복습활동 (Review Activity)

**활동 단계**: 타이밍 결정 → 방식 선택 → 실행 → 마무리

**필수 데이터 항목**:
- 복습 타이밍 패턴 (즉시/다음날/일주일 후) (`review_timing`, `review_timing_category`)
- 복습 시기별 효율 차이 (`review_timing_comparison`, `efficiency_by_timing`)
- 복습 분량과 집중도/감정 리듬 관계 (`review_volume`, `focus_decline`, `emotion_rhythm_change`)
- 복습 방식 선호도 (개념 재확인/문제풀이/요약정리) (`review_method`, `method_preference_score`)
- 새로운 연결 시도 경향 (`connection_attempt`)
- 매체 선호도 일정성 (노트/화이트보드/디지털) (`review_medium`, `medium_preference_consistency`)
- 복습 시작 시 감정 상태 (`review_start_emotion`)
- 저항감/회피 행동 발생 시점 (`resistance_detected`, `resistance_timing`)
- 만족감/효능감 표현 여부 (`review_completed`, `satisfaction_expression`)

**관련 룰**: `RV_A1` ~ `RV_C3` (9개)

---

### ⑦ 포모도르 수학일기 (Pomodoro Journal)

**활동 단계**: 세션 설계 → 성찰 기록 → 감정 표현 → 루틴 강화

**필수 데이터 항목**:
- 평균 집중 지속시간 및 포모도르 단위 적합성 (`average_focus_duration`, `pomodoro_unit_match_score`)
- 세션 단계별 집중력 안정성 (초반/중반/후반) (`session_stage`, `focus_stability_by_stage`)
- 휴식 시간별 회복 패턴 일정성 (`rest_duration_type`, `recovery_pattern_consistency`)
- 메타인지적 성찰 수준 (무엇 vs 어떻게) (`journal_content_analyzed`, `what_learned_ratio`, `how_learned_ratio`)
- 감정 변화 언급 비율 (`emotion_mention_ratio`)
- 실수 인식 및 개선 다짐 패턴 (`mistake_recognition_pattern`)
- 긍정/부정 감정 균형 (`emotion_balance_score`)
- 감정 표현 후 학습 태도 변화 (`emotion_expressed`, `subsequent_behavior_change`)
- 루틴 강화 징후 (`journal_consistency_days`, `routine_mention_frequency`, `repeat_willingness`)

**관련 룰**: `PJ_A1` ~ `PJ_C3` (9개)

---

### ⑧ 귀가검사 (Return Check)

**활동 단계**: 준비 → 참여 → 후속 조치

**필수 데이터 항목**:
- 핵심 성취 명확화 능력 (`return_check_stage`, `achievement_clarity_score`)
- 의미 있는 학습 순간 식별 (`meaningful_moment_identified`)
- 만족감과 피로감 균형 (`satisfaction_fatigue_balance_score`)
- 피드백 유형별 반응 강도 (칭찬/교정/조언) (`feedback_type`, `response_intensity`)
- 즉시 수정 행동 수행 여부 (`immediate_action_taken`)
- 피드백 수용 유형 (방어적/성장형) (`reception_type`)
- 개선 포인트의 다음 학습일정 반영 (`improvement_point_identified`, `next_schedule_reflection`)
- 스스로 개선 루틴 점검 행동 (`self_check_behavior`)
- 루틴 유지 기간 증가 패턴 (`feedback_repeat_count`, `routine_maintenance_trend`)

**관련 룰**: `RC_A1` ~ `RC_C3` (9개)

---

## 🔗 복합 상황 메타데이터

**복합 상황 룰** (CR1~CR4)에서 사용되는 데이터:
- 개념 취약점과 오답 패턴 연계 (`concept_weak_stage_detected`, `error_pattern_detected`, `error_category`)
- 유형학습 지루함과 진행 지연 (`type_learning_boredom_detected`, `progress_delay_detected`)
- 문제풀이 인지부하와 복습 저항감 (`cognitive_overload_detected`, `review_resistance_detected`)
- 포모도르 감정 불균형과 루틴 불안정 (`emotion_balance_score`, `routine_stability_score`)

**관련 룰**: `CR1` ~ `CR4` (4개)

---

## 📊 전체 데이터 항목 요약

Agent 04가 사용하는 전체 데이터는 **100개 항목**으로 구성되며, 다음과 같이 분류됩니다:

1. **학습 세션 기본 메타데이터** (10개)
2. **문제풀이 성능 데이터** (15개)
3. **페르소나 행동 패턴 데이터** (15개)
4. **난이도 조정 및 성장 구간 데이터** (10개)
5. **피드백 및 상호작용 데이터** (10개)
6. **인지·몰입 상태 데이터** (10개)
7. **학습 루틴 및 포모도르 데이터** (10개)
8. **오답 및 학습회복 데이터** (10개)
9. **시스템 추론 및 상호연동 메타데이터** (10개)

**상세 내용**: `gendata.md` 참조

---

## 🧠 학습 성향 및 습관

- 고난도 선호도
- 학습 자료 스스로 선택 여부
- 페르소나 유형 (분석형/직관형/도전형/안정형 등)
- 행동 반응 속도
- 재시도 경향

---

## ❤️ 정서 및 동기 정보

- 수업 중 감정 상태 기록 → `ref(agent05.emotion_during_class)`
- 질문 요청 경향
- 오답 후 감정 반응 → `ref(agent05.emotion_after_wrong_answer)`
- 페르소나 감정-행동 일치도 → `ref(agent05.persona_emotion_behavior_match)`
- 난이도-감정 상관도 → `ref(agent05.difficulty_emotion_correlation)`

---

## 📝 참고 문서

- **전체 데이터 항목 상세**: `gendata.md`
- **포괄형 질문 세트**: `questions.md`
- **룰 정의**: `rules.yaml`
- **미션 및 목표**: `mission.md`
- **완결성 체크 리포트**: `completeness_check_report.md`

**참고**: 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
