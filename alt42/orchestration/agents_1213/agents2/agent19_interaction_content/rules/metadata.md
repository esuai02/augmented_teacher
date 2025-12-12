# Agent 19 - Interaction Content (상호작용 컨텐츠) 데이터 인덱스

상호작용 컨텐츠 생성 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **맞춤형 상호작용 컨텐츠 생성 및 패키징에 필요한 데이터**가 필요합니다. 아래는 **Agent 19 - Interaction Content** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🎯 1. 학습 상황 감지 데이터 (7가지 핵심 상황)

### S1: 학습 이탈 조짐 감지
- **engagement_score**: 학습 집중도 점수 (0.0~1.0) - `EngagementScore`
- **input_event_count**: 입력 이벤트 수 (시간 윈도우 내 사용자 입력 횟수) - `InputEventCount`
- **time_window_minutes**: 시간 윈도우 (분)
- **emotion_state**: 감정 상태 (권태, 지루함, 피로 등) - `EmotionState`
- **immersion_level**: 몰입도 수준 (0.0~1.0) - `ImmersionLevel`
- **current_activity_difficulty**: 현재 활동 난이도 (1~10) - `ActivityDifficulty`
- **detection_source**: 감지 소스 (어떤 에이전트에서 감지되었는지) - `DetectionSource`

### S2: 현재 위치 지연 감지
- **progress_rate**: 목표 대비 진행률 (0.0~1.0) - `ProgressRate`
- **current_position_status**: 현재 위치 상태 (지연 구간, 적절, 원활) - `CurrentPositionStatus`
- **pressure_level**: 압박감 수준 (1~10) - `PressureLevel`
- **study_hours_per_week**: 주당 학습 시간 - `StudyHoursPerWeek`
- **confidence_change**: 자신감 변화량 (-1.0~1.0) - `ConfidenceChange`

### S3: 휴식 루틴 이상 탐지
- **rest_pattern_status**: 휴식 패턴 상태 (정상, 비정상) - `RestPatternStatus`
- **fatigue_accumulation**: 피로 누적도 (0.0~1.0) - `FatigueAccumulation`
- **rest_interval_minutes**: 휴식 간격 (분) - `RestIntervalMinutes`
- **study_session_duration**: 학습 세션 지속 시간 (분)
- **rest_missing_count**: 휴식 누락 횟수 - `RestMissingCount`
- **consecutive_study_minutes**: 연속 학습 시간 (분)

### S4: 오답 패턴 반복
- **error_repeat_count**: 오답 반복 횟수 - `ErrorRepeatCount`
- **error_type**: 오류 유형 (계산 오류, 개념 오류 등) - `ErrorType`
- **error_category**: 오류 카테고리 (개념 이해 부족 등) - `ErrorCategory`
- **concept_review_time_seconds**: 개념 이해 단계 체류시간 (초) - `ConceptReviewTimeSeconds`
- **study_style**: 학습 스타일 (문제풀이 위주, 개념 위주 등) - `StudyStyle`
- **concept_mastery_level**: 개념 숙달도 (0.0~1.0) - `ConceptMasteryLevel`

### S5: 정서적 침착도 저하
- **calmness_score_change**: 침착도 점수 변화량 (이전 대비 변화율) - `CalmnessScoreChange`
- **calmness_score**: 침착도 점수 (0.0~1.0) - `CalmnessScore`
- **selection_error_frequency**: 선택 오류 빈도 (0.0~1.0) - `SelectionErrorFrequency`
- **emotion_log**: 감정 로그 (조급함, 좌절 등) - `EmotionLog`
- **mistake_repeat_count**: 실수 반복 횟수 - `MistakeRepeatCount`

### S6: 목표 대비 활동 불균형
- **activity_distribution_balance**: 활동 분포 균형도 (0.0~1.0) - `ActivityDistributionBalance`
- **concept_study_ratio**: 개념 공부 비율 (0.0~1.0) - `ConceptStudyRatio`
- **problem_solving_ratio**: 문제풀이 비율 (0.0~1.0) - `ProblemSolvingRatio`
- **goal_type**: 목표 유형 (문제풀이 실력 향상, 시험 대비 등) - `GoalType`
- **user_resistance_to_change**: 변화에 대한 저항도 (0.0~1.0)
- **previous_intervention_count**: 이전 개입 횟수

### S7: 시그너처 루틴 형성 시점
- **signature_routine_detected**: 시그너처 루틴 감지 여부 (true/false) - `SignatureRoutineDetected`
- **routine_consistency_days**: 루틴 일관성 일수 - `RoutineConsistencyDays`
- **routine_success_rate**: 루틴 성공률 (0.0~1.0) - `RoutineSuccessRate`

---

## 🎨 2. 상호작용 유형 및 템플릿 선택 데이터

- **interaction_type**: 상호작용 유형 (텍스트 전달, 루틴 개선, 비선형 등) - `InteractionType`
- **template_type**: 템플릿 유형 - `InteractionTemplate`
- **template_library_has_match**: 템플릿 라이브러리 매칭 여부 - `TemplateLibraryMatch`
- **template_match_score**: 템플릿 매칭 점수 (0.0~1.0) - `TemplateMatchScore`
- **detected_learning_situation**: 감지된 학습 상황 (S1~S7) - `LearningSituationDetection`
- **situation_code**: 상황 코드 (S1, S2, S3, S4, S5, S6, S7) - `SituationCode`
- **optimal_interaction_type**: 최적 상호작용 유형 - `InteractionType`
- **rule_link_mapping**: 룰 링크 매핑

---

## 👤 3. 개인화 데이터

- **mbti_type**: MBTI 유형 (INFP, ENFP 등) - `MBTIType`
- **concentration_time_slot**: 집중 시간대 - `ConcentrationTimeSlot`
- **learning_style**: 학습 스타일 - `LearningStyle`
- **math_learning_style**: 수학 학습 스타일 (계산형, 개념형, 응용형) - `MathLearningStyle`

---

## 📦 4. 템플릿 패키징 데이터

- **use_existing_template**: 기존 템플릿 사용 여부
- **customize_template**: 템플릿 맞춤화 여부
- **create_new_template**: 신규 템플릿 생성 여부
- **register_to_library**: 라이브러리 등록 여부
- **generate_code**: 코드 생성 여부 (HTML, CSS, JavaScript)
- **customized_ui**: 맞춤형 UI 구성
- **customized_tone**: 맞춤형 톤 구성
- **customized_link**: 맞춤형 링크 구성

---

## 📊 5. 상호작용 효과성 추적 데이터

- **participation_rate**: 참여율 (상호작용 컨텐츠 참여 비율) - `ParticipationRate`
- **click_rate**: 클릭률 (상호작용 컨텐츠 링크 클릭 비율) - `ClickRate`
- **reentry_success_rate**: 재진입 성공률 (학습 이탈 후 재진입 성공 비율) - `ReentrySuccessRate`
- **track_click_rate**: 클릭률 추적 여부
- **track_engagement_rate**: 참여율 추적 여부
- **track_improvement_rate**: 개선율 추적 여부
- **interaction_delivered**: 상호작용 전달 여부
- **template_efficiency**: 템플릿 효율성
- **rule_correction**: 룰 보정
- **feedback_loop_design**: 피드백 루프 설계
- **update_template_effectiveness**: 템플릿 효과성 업데이트 여부
- **send_to_agent22**: Agent 22로 전송 여부

---

## 📚 6. 수학 교과 특화 데이터

- **current_unit**: 현재 단원 - `CurrentUnit`
- **learning_stage**: 학습 단계 (concept, practice, advanced) - `LearningStage`
- **unit_content_link**: 단원별 컨텐츠 링크 - `UnitContentLink`
- **weak_units**: 취약 단원 목록 - `WeakUnits`
- **academy_textbook**: 학원 교재 - `AcademyTextbook`
- **textbook_level**: 교재 레벨 (A, B, C, concept, RPM) - `TextbookLevel`
- **problem_type**: 문제 유형 (basic, type, advanced) - `ProblemType`
- **academy_class_time**: 학원 수업 시간 - `AcademyClassTime`
- **academy_unit**: 학원 단원 - `AcademyUnit`

---

## 🔗 7. 링크 및 컨텐츠 시스템 연동 데이터

- **easy_win_zone**: 쉬운 승리 구간 링크
- **alternative_easy_activity**: 대안 쉬운 활동 링크
- **emotion_support_content**: 감정 지원 컨텐츠 링크
- **pace_adjustment_guide**: 페이스 조정 가이드 링크
- **alternative_learning_path**: 대안 학습 경로 링크
- **rest_routine_guide**: 휴식 루틴 가이드 링크
- **optimal_rest_pattern**: 최적 휴식 패턴 링크
- **rest_activity_content**: 휴식 활동 컨텐츠 링크
- **concept_reinforcement**: 개념 보강 링크
- **concept_explanation_content**: 개념 설명 컨텐츠 링크
- **balanced_learning_approach**: 균형잡힌 학습 접근 링크
- **emotional_stability_content**: 감정 안정 컨텐츠 링크
- **infp_support_content**: INFP 지원 컨텐츠 링크
- **resilience_building_content**: 회복력 구축 컨텐츠 링크
- **alternative_activity_links**: 대안 활동 링크
- **problem_solving_activity**: 문제풀이 활동 링크
- **balanced_activity_mix**: 균형잡힌 활동 조합 링크
- **reward_content**: 보상 컨텐츠 링크
- **routine_maintenance_tips**: 루틴 유지 팁 링크
- **weak_unit_reinforcement_content**: 취약 단원 보강 컨텐츠 링크
- **textbook_content_link**: 교재 컨텐츠 링크
- **preview_content**: 예습 컨텐츠 링크
- **review_content**: 복습 컨텐츠 링크
- **calculation_practice_content**: 계산 연습 컨텐츠 링크
- **next_problem_content**: 다음 문제 컨텐츠 링크
- **advanced_problem_content**: 심화 문제 컨텐츠 링크
- **next_unit_content**: 다음 단원 컨텐츠 링크

---

## 🔄 8. 복합 상황 대응 데이터

- **complex_situation_resolution**: 복합 상황 종합 해결 경로
- **comprehensive_support_content**: 종합 지원 컨텐츠 링크
- **fatigue_error_comprehensive_improvement**: 피로와 오답 종합 개선 가이드
- **rest_and_learning_balance**: 휴식과 학습 균형 링크
- **balance_calmness_comprehensive**: 균형과 침착도 종합 개선 경로
- **balanced_emotional_stability**: 균형잡힌 감정 안정 링크

---

## 🧩 9. 시스템 연계 정보

- **콘텐츠 추천 알고리즘 연동 여부**: 외부 콘텐츠 추천 시스템과의 연동 상태

---

## 📋 Ontology 매핑 요약

### 핵심 온톨로지 클래스
- `InteractionTypeTemplate`: 상호작용 유형과 템플릿 매핑 (Agent 19 핵심 온톨로지)
- `LearningSituationDetection`: 학습 상황 감지 (S1~S7)
- `ReentryInteraction`: 재진입 유도 상호작용 (S1)
- `DelayRecoveryInteraction`: 지연 회복 상호작용 (S2)
- `RestRoutineImprovementInteraction`: 휴식 루틴 개선 상호작용 (S3)
- `ErrorPatternRecoveryInteraction`: 오답 패턴 회복 상호작용 (S4)
- `EmotionalStabilityInteraction`: 정서적 침착도 저하 대응 상호작용 (S5)
- `ActivityBalanceInteraction`: 목표 대비 활동 불균형 조정 상호작용 (S6)
- `SignatureRoutineReinforcementInteraction`: 시그너처 루틴 강화 상호작용 (S7)

### 데이터 소스 온톨로지 매핑
모든 데이터 소스는 `alphatutor_ontology.owl` 파일에 정의된 클래스로 매핑됩니다. 각 데이터 소스 옆에 표시된 클래스명을 참조하세요.

---

**참고**: 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.

**Ontology 파일 위치**: `alt42/orchestration/agents/ontology_engineering/alphatutor_ontology.owl`

**질문 데이터 위치**: `alt42/orchestration/agents/agent_orchestration/data_based_questions.js` (agent19 섹션)

**Rules 파일 위치**: `alt42/orchestration/agents/agent19_interaction_content/rules/rules.yaml`
