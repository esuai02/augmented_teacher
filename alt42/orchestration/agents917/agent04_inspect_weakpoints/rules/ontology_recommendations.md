# Agent 04 - Ontology 사용 영역 추천

Agent 04 (취약점검사)는 8가지 학습 활동 영역별로 페르소나 분석 및 맞춤형 행동유도를 수행합니다. 각 활동 영역에서 ontology를 사용해야 할 영역을 식별하고 추천합니다.

---

## 📊 Ontology 사용 영역 추천 (Agent 03 케이스 참고)

### ① 개념이해 (Concept Understanding)

**Ontology 사용 영역:**

1. **ConceptWeakpoint** (핵심 온톨로지)
   - **사용 목적**: 개념이해 단계별(이해/정리/적용) 취약구간을 구조화하여 탐지 및 보강 전략 수립
   - **적용 영역**: 
     - 개념이해 과정의 멈춤 지점 분석 (CU_A1 룰)
     - 개념쌍 혼동 패턴 탐지 (CU_A3 룰)
     - 취약구간별 맞춤형 보강 콘텐츠 추천
   - **데이터 소스**: `concept_stage`, `pause_frequency`, `pause_stage`, `concept_confusion_detected`, `confusion_type`

2. **ConceptLearningStyle**
   - **사용 목적**: 개념 학습 스타일(시각형/청각형/요약형)과 페르소나 매칭을 구조화하여 최적 학습 방법 추천
   - **적용 영역**:
     - 페르소나-학습방식 적합성 평가 (CU_B1 룰)
     - 시각 자료 반응 분석 (CU_B2 룰)
     - 텍스트 vs 예제 선호도 분석 (CU_B3 룰)
   - **데이터 소스**: `persona_type`, `current_method`, `method_persona_match_score`, `visual_response_score`, `text_organization_score`, `example_verification_score`

3. **ConceptImmersionPattern**
   - **사용 목적**: 개념 학습 몰입 패턴과 활동 조합(TTS/필기/예제)을 구조화하여 몰입 루틴 설계
   - **적용 영역**:
     - 최적 활동 조합 식별 (CU_C1 룰)
     - 지루함/집중 이탈 시점 탐지 (CU_C2 룰)
     - 피드백 유형 최적화 (CU_C3 룰)
   - **데이터 소스**: `immersion_score_by_combination`, `best_combination`, `boredom_detected`, `attention_drop_time`, `feedback_types_tested`, `feedback_effectiveness_score`

---

### ② 유형학습 (Type Learning)

**Ontology 사용 영역:**

1. **TypeLearningRoutine** (핵심 온톨로지)
   - **사용 목적**: 유형학습 루틴 구조(문제풀이 순서, 난이도 변화 대응, 세션 단계별 집중)를 구조화하여 효율성 분석
   - **적용 영역**:
     - 문제풀이 순서 조정 (TL_A1 룰)
     - 난이도 변화 대응 안정성 평가 (TL_A2 룰)
     - 세션 단계별 집중 패턴 분석 (TL_A3 룰)
   - **데이터 소스**: `problem_sequence`, `sequence_efficiency_score`, `difficulty_change`, `speed_consistency_score`, `focus_consistency_score`, `session_stage`, `focus_duration_by_stage`

2. **TypeLearningStrategy**
   - **사용 목적**: 유형학습 접근 전략(공식회상형/유추형/비교형)과 반복 학습 태도를 구조화하여 자기조절능력 분석
   - **적용 영역**:
     - 접근 전략 분석 및 다양화 (TL_B1 룰)
     - 반복 오류 패턴 탐지 (TL_B2 룰)
     - 포기/지루함 패턴 탐지 (TL_B3 룰)
   - **데이터 소스**: `approach_strategy`, `strategy_usage_frequency`, `repeated_error_count`, `error_type`, `repetition_count`, `giveup_or_boredom_detected`, `detection_timing`

3. **TypeLearningEmotionLoop**
   - **사용 목적**: 유형학습 감정-몰입-동기 루프를 구조화하여 정서-몰입-재도전 루프 설계
   - **적용 영역**:
     - 높은 몰입 활동 식별 (TL_C1 룰)
     - 난이도 상승 시 감정 반응 분석 (TL_C2 룰)
     - 피드백 유형별 재도전 효과성 평가 (TL_C3 룰)
   - **데이터 소스**: `sub_activity_type`, `immersion_score_by_activity`, `difficulty_increase`, `emotion_response`, `feedback_types`, `retry_effectiveness_score`

---

### ③ 문제풀이 (Problem Solving)

**Ontology 사용 영역:**

1. **ProblemSolvingStrategy** (핵심 온톨로지)
   - **사용 목적**: 문제풀이 사고 흐름과 전략을 구조화하여 인지구조화 능력 및 사고 유연성 진단
   - **적용 영역**:
     - 핵심 조건 구분 능력 분석 (PS_A1 룰)
     - 구조적 문제 파악 습관 평가 (PS_A2 룰)
     - 전략 전환 능력 평가 (PS_A3 룰)
   - **데이터 소스**: `problem_reading_stage`, `key_condition_identification_score`, `structural_analysis_before_solving`, `solving_stage`, `stuck_detected`, `strategy_switch_attempted`

2. **CognitiveLoadPattern**
   - **사용 목적**: 문제풀이 인지부하와 감정 반응 패턴을 구조화하여 실시간 인지부하 조정 포인트 탐지
   - **적용 영역**:
     - 인지부하 신호 탐지 (PS_B1 룰)
     - 감정 상태 분석 (PS_B2 룰)
     - 효율 유지 여부 평가 (PS_B3 룰)
   - **데이터 소스**: `gaze_detection`, `gaze_away_frequency`, `pause_frequency`, `emotion_during_solving`, `emotion_intensity`, `solving_duration`, `efficiency_trend`

3. **MetacognitiveReview**
   - **사용 목적**: 문제풀이 메타인지적 검토와 자기조절을 구조화하여 자기점검 루틴 정착도 평가
   - **적용 영역**:
     - 자기 설명 능력 평가 (PS_C1 룰)
     - 검토 루틴 일관성 평가 (PS_C2 룰)
     - 확신도-정확도 일치도 평가 (PS_C3 룰)
   - **데이터 소스**: `solving_stage`, `self_explanation_score`, `review_routine_consistency`, `self_confidence_level`, `actual_result`, `confidence_accuracy_match_score`

---

### ④ 오답노트 (Error Notes)

**Ontology 사용 영역:**

1. **ErrorPattern** (핵심 온톨로지)
   - **사용 목적**: 오답 발생 원인과 사고 패턴을 구조화하여 인지적 기원 및 행동패턴 정밀 추적
   - **적용 영역**:
     - 오답 원인 분류 분석 (EN_A1 룰)
     - 오답 전 행동 패턴 분석 (EN_A2 룰)
     - 오답 후 성찰 습관 평가 (EN_A3 룰)
   - **데이터 소스**: `error_occurred`, `error_category`, `pre_error_behavior`, `post_error_reflection`

2. **ErrorRecoveryResilience**
   - **사용 목적**: 오답 인지적 회복력과 재도전 태도를 구조화하여 인지 회복력 및 재도전 메커니즘 진단
   - **적용 영역**:
     - 성찰 초점 분석 (EN_B1 룰)
     - 재도전 감정 분석 (EN_B2 룰)
     - 전략 변화 여부 분석 (EN_B3 룰)
   - **데이터 소스**: `error_recognized`, `reflection_focus`, `retry_attempted`, `retry_emotion`, `same_type_retry`, `strategy_change_detected`

3. **FeedbackTransfer**
   - **사용 목적**: 오답 피드백 수용과 행동변화 루프를 구조화하여 피드백의 행동전이 효과 분석
   - **적용 영역**:
     - 피드백 수용 분석 (EN_C1 룰)
     - 피드백-행동 전이율 평가 (EN_C2 룰)
     - 개선 패턴 유지 여부 평가 (EN_C3 룰)
   - **데이터 소스**: `feedback_provided`, `feedback_type`, `reception_indicators`, `action_taken`, `feedback_to_action_rate`, `improved_pattern_detected`, `next_unit_maintenance`

---

### ⑤ 질의응답 (Q&A)

**Ontology 사용 영역:**

1. **QuestionTimingPattern** (핵심 온톨로지)
   - **사용 목적**: 질문 발생 타이밍과 패턴을 구조화하여 질문의 발생 타이밍·패턴·억제 요인 분석
   - **적용 영역**:
     - 질문 발생 상황 분석 (QA_A1 룰)
     - 질문 표현 타이밍 분석 (QA_A2 룰)
     - 세션 단계별 질문 빈도 분석 (QA_A3 룰)
   - **데이터 소스**: `question_occurred`, `question_context`, `expression_timing`, `delay_duration`, `session_stage`, `question_frequency_by_stage`

2. **QuestionDepth**
   - **사용 목적**: 질문 내용과 사고 깊이를 구조화하여 질문의 인지 수준(사실 → 개념 → 적용 → 통합) 분석
   - **적용 영역**:
     - 질문 유형 분석 (QA_B1 룰)
     - 질문 복잡도 분석 (QA_B2 룰)
     - 반복 질문 패턴 분석 (QA_B3 룰)
   - **데이터 소스**: `question_type`, `question_complexity`, `repeated_question_count`, `question_topic`

3. **QuestionFeedbackLoop**
   - **사용 목적**: 질문 피드백 반응과 루프 지속성을 구조화하여 피드백의 사고 확장 효과 및 질문 루프의 자율성 평가
   - **적용 영역**:
     - 답변 만족도 평가 (QA_C1 룰)
     - 후속 질문 능력 평가 (QA_C2 룰)
     - 사고 전환 순간 탐지 (QA_C3 룰)
   - **데이터 소스**: `answer_provided`, `satisfaction_score`, `follow_up_action`, `insight_moment_detected`, `insight_indicators`

---

### ⑥ 복습활동 (Review)

**Ontology 사용 영역:**

1. **ReviewTimingOptimization** (핵심 온톨로지)
   - **사용 목적**: 복습 타이밍·주기·분량 최적화를 구조화하여 개인별 기억곡선 및 피로곡선 기반 복습 타이밍 탐색
   - **적용 영역**:
     - 복습 타이밍 패턴 분석 (RV_A1 룰)
     - 시기별 효율 비교 (RV_A2 룰)
     - 분량-집중도 관계 분석 (RV_A3 룰)
   - **데이터 소스**: `review_timing`, `review_timing_category`, `review_timing_comparison`, `efficiency_by_timing`, `review_volume`, `focus_decline`, `emotion_rhythm_change`

2. **ReviewMethodStructure**
   - **사용 목적**: 복습 방식과 내용 구조를 구조화하여 복습의 전략적 다양성 및 재구성 능력 평가
   - **적용 영역**:
     - 복습 방식 선호도 분석 (RV_B1 룰)
     - 연결 시도 경향 분석 (RV_B2 룰)
     - 매체 선호도 일관성 평가 (RV_B3 룰)
   - **데이터 소스**: `review_method`, `method_preference_score`, `connection_attempt`, `review_medium`, `medium_preference_consistency`

3. **ReviewEmotionRoutine**
   - **사용 목적**: 복습 정서적 몰입과 루틴 지속성을 구조화하여 정서 루프 분석 및 복습 저항감 완화
   - **적용 영역**:
     - 초기 감정 상태 분석 (RV_C1 룰)
     - 저항감 발생 시점 탐지 (RV_C2 룰)
     - 만족감 표현 여부 분석 (RV_C3 룰)
   - **데이터 소스**: `review_start_emotion`, `resistance_detected`, `resistance_timing`, `review_completed`, `satisfaction_expression`

---

### ⑦ 포모도르 수학일기 (Pomodoro Journal)

**Ontology 사용 영역:**

1. **PomodoroFocusRhythm** (핵심 온톨로지)
   - **사용 목적**: 포모도르 집중 리듬과 세션 설계를 구조화하여 집중리듬 안정성 및 세션 구조 적합도 분석
   - **적용 영역**:
     - 집중 지속시간 분석 (PJ_A1 룰)
     - 세션 단계별 집중력 분석 (PJ_A2 룰)
     - 휴식 회복 패턴 분석 (PJ_A3 룰)
   - **데이터 소스**: `average_focus_duration`, `pomodoro_unit_match_score`, `session_stage`, `focus_stability_by_stage`, `rest_duration_type`, `recovery_pattern_consistency`

2. **MetacognitiveReflection**
   - **사용 목적**: 포모도르 일기 자기 성찰과 메타인지 수준을 구조화하여 자기성찰 루프(사고-감정-행동)의 일관성 분석
   - **적용 영역**:
     - 메타인지적 성찰 수준 평가 (PJ_B1 룰)
     - 감정 언급 비율 분석 (PJ_B2 룰)
     - 실수 인식 패턴 분석 (PJ_B3 룰)
   - **데이터 소스**: `journal_content_analyzed`, `what_learned_ratio`, `how_learned_ratio`, `emotion_mention_ratio`, `mistake_recognition_pattern`

3. **EmotionRoutineFormation**
   - **사용 목적**: 포모도르 일기 감정표현과 루틴 형성을 구조화하여 감정 인식이 행동 루틴 강화로 이어지는지 확인
   - **적용 영역**:
     - 감정 균형 표현 분석 (PJ_C1 룰)
     - 감정-행동 연결 분석 (PJ_C2 룰)
     - 루틴 형성 징후 탐지 (PJ_C3 룰)
   - **데이터 소스**: `journal_content_analyzed`, `emotion_balance_score`, `emotion_expressed`, `subsequent_behavior_change`, `journal_consistency_days`, `routine_mention_frequency`, `repeat_willingness`

---

### ⑧ 귀가검사 (Return Check)

**Ontology 사용 영역:**

1. **ReturnCheckAchievement** (핵심 온톨로지)
   - **사용 목적**: 귀가검사 학습 마무리 인식과 성취 정리를 구조화하여 하루 학습 성취의 자각 및 정서적 마무리 안정성 평가
   - **적용 영역**:
     - 성취 명확화 평가 (RC_A1 룰)
     - 의미 있는 순간 인식 평가 (RC_A2 룰)
     - 만족감-피로감 균형 평가 (RC_A3 룰)
   - **데이터 소스**: `return_check_stage`, `achievement_clarity_score`, `meaningful_moment_identified`, `satisfaction_fatigue_balance_score`

2. **FeedbackAcceptanceTransfer**
   - **사용 목적**: 귀가검사 피드백 수용과 행동전이를 구조화하여 피드백의 수용률 및 행동전이율 진단
   - **적용 영역**:
     - 피드백 유형별 반응 분석 (RC_B1 룰)
     - 즉시 행동 전이 평가 (RC_B2 룰)
     - 피드백 반응 유형 분석 (RC_B3 룰)
   - **데이터 소스**: `return_check_stage`, `feedback_type`, `response_intensity`, `feedback_provided`, `immediate_action_taken`, `reception_type`

3. **RoutineLoopConnection**
   - **사용 목적**: 귀가검사 개선 루틴 추적과 다음 루프 연결을 구조화하여 피드백 루프 → 행동 루프 → 지속 루프 전환 여부 평가
   - **적용 영역**:
     - 개선 포인트 반영 여부 평가 (RC_C1 룰)
     - 자기 점검 행동 평가 (RC_C2 룰)
     - 루틴 유지 개선 패턴 분석 (RC_C3 룰)
   - **데이터 소스**: `return_check_stage`, `improvement_point_identified`, `next_schedule_reflection`, `self_check_behavior`, `feedback_repeat_count`, `routine_maintenance_trend`

---

## 🎯 Ontology 사용 우선순위 추천

### 높은 우선순위 (즉시 적용 권장)

1. **ConceptWeakpoint** (① 개념이해)
   - 개념이해는 모든 학습의 기초이므로 취약점 탐지가 가장 중요
   - CU_A1, CU_A2, CU_A3 룰과 직접 연계되어 즉시 활용 가능

2. **ErrorPattern** (④ 오답노트)
   - 오답 패턴 분석은 학습 개선의 핵심
   - EN_A1, EN_A2, EN_A3 룰과 직접 연계되어 즉시 활용 가능

3. **ProblemSolvingStrategy** (③ 문제풀이)
   - 문제풀이 전략은 수학 학습의 핵심 역량
   - PS_A1, PS_A2, PS_A3 룰과 직접 연계되어 즉시 활용 가능

### 중간 우선순위 (단기 적용 권장)

4. **TypeLearningRoutine** (② 유형학습)
   - 유형학습 루틴 최적화는 학습 효율 향상에 중요
   - TL_A1, TL_A2, TL_A3 룰과 직접 연계

5. **PomodoroFocusRhythm** (⑦ 포모도르 수학일기)
   - 집중 리듬 분석은 학습 지속성에 중요
   - PJ_A1, PJ_A2, PJ_A3 룰과 직접 연계

6. **ReturnCheckAchievement** (⑧ 귀가검사)
   - 성취 인식은 학습 동기 강화에 중요
   - RC_A1, RC_A2, RC_A3 룰과 직접 연계

### 낮은 우선순위 (중장기 적용 고려)

7. **QuestionTimingPattern** (⑤ 질의응답)
   - 질문 패턴 분석은 학습 깊이 향상에 도움
   - QA_A1, QA_A2, QA_A3 룰과 직접 연계

8. **ReviewTimingOptimization** (⑥ 복습활동)
   - 복습 최적화는 장기 기억 강화에 중요
   - RV_A1, RV_A2, RV_A3 룰과 직접 연계

---

## 📋 Ontology 구현 시 고려사항

1. **활동 영역 간 연계성**: 각 활동 영역의 ontology는 서로 연계되어 있어야 합니다. 예를 들어, ConceptWeakpoint와 ErrorPattern은 개념 오류로 연결될 수 있습니다.

2. **페르소나 연계**: 각 ontology는 Agent 01의 페르소나 데이터와 연계되어 개인별 맞춤 분석이 가능해야 합니다.

3. **시간적 연속성**: 각 활동 영역의 ontology는 시간에 따른 변화를 추적할 수 있어야 합니다. 예를 들어, ErrorPattern은 시간에 따라 개선되는 패턴을 추적할 수 있어야 합니다.

4. **룰 기반 연계**: 각 ontology는 rules.yaml의 해당 룰과 직접 연계되어 자동으로 트리거될 수 있어야 합니다.

5. **데이터 소스 매핑**: 각 ontology는 metadata.md에 정의된 데이터 소스와 명확하게 매핑되어야 합니다.

---

## 🔗 Agent 03 케이스 참고 사항

Agent 03의 ontology 구조를 참고하여 Agent 04도 동일한 패턴으로 구현되었습니다:

- 각 활동 영역(①~⑧)마다 3개의 포괄형 질문(A, B, C)에 대응하는 ontology가 정의됨
- 각 ontology는 name과 description을 가지며, 특정 목적을 가짐
- 각 ontology는 rules.yaml의 해당 룰과 직접 연계됨
- 각 ontology는 data_based_questions.js에 정의되어 questions.html에서 자동으로 로드됨

