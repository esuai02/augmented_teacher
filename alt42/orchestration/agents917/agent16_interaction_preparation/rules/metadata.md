상호작용 준비 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **상호작용 콘텐츠 생성을 위한 세계관 선택 및 스토리 구조 설계에 필요한 데이터**가 필요합니다. 아래는 **Agent 16 - Interaction Preparation** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🧪 10. AI 분석 및 추론용 메타 정보 (1)

98. 상호작용 세계관 및 테마 선택 데이터

### Agent 16 데이터 기반 질문에서 사용하는 데이터 소스

#### 포괄형 질문 1: 세계관 선택 관련 데이터

**학생 상태 기반 세계관 추천 분석:**
- `emotion_stability`: 감정 안정도 (Agent 05 연계)
- `routine_maintenance_rate`: 루틴 유지율 (Agent 09, Agent 18 연계)
- `goal_mode`: 목표 모드 (Agent 03 연계)
- `fatigue_level`: 피로도 (Agent 05 연계)
- `immersion_score`: 몰입도 점수 (Agent 05, Agent 14 연계)
- `learning_emotion`: 학습 감정 (Agent 05 연계)
- `math_learning_stage`: 수학 학습 단계 (개념학습/유형연습/심화/기출)
- `student_level`: 학생 수준 (하위권/중위권/상위권)
- `math_recent_accuracy`: 최근 수학 정답률
- `unit_accuracy`: 단원별 정답률
- `math_learning_style`: 수학 학습 스타일 (계산형/개념형/응용형) (Agent 01 연계)
- `emotion_state`: 감정 상태 (Agent 05 연계)
- `persona_type`: 페르소나 유형 (Agent 07 연계)

**세계관별 상황 매핑 및 선택 기준:**
- `long_term_goal_deviation`: 장기 목표 이탈 (Agent 03 연계)
- `emotion_change`: 감정 변화 (Agent 05 연계)
- `level_change`: 수준 변화
- `curriculum_alignment`: 커리큘럼 정렬 (Agent 01 연계)
- `exam_d_day`: 시험 D-Day (Agent 02 연계)
- `exam_schedule`: 시험 일정 (Agent 02 연계)
- `short_term_goal_delay`: 단기 목표 지연 (Agent 03 연계)
- `motivation_level`: 동기 수준 (Agent 05 연계)
- `routine_disruption`: 루틴 붕괴 (Agent 09, Agent 18 연계)
- `time_management_issue`: 시간 관리 이슈 (Agent 14 연계)
- `curiosity_level`: 호기심 수준
- `question_frequency`: 질문 빈도 (Agent 04 연계)

**세계관 선택을 위한 종합 데이터 분석:**
- `previous_worldview`: 이전 세계관
- `worldview_compatibility`: 세계관 호환성
- `student_preference`: 학생 선호도
- `worldview_effectiveness_data`: 세계관 효과성 데이터
- `interaction_history`: 상호작용 이력
- `academy_class_time`: 학원 수업 시간 (Agent 01 연계)
- `academy_class_completed`: 학원 수업 완료 여부
- `pre_class_interaction_needed`: 수업 전 상호작용 필요 여부
- `post_class_interaction_needed`: 수업 후 상호작용 필요 여부
- `problem_solving_status`: 문제 풀이 상태
- `weak_units`: 취약 단원 (Agent 04 연계)
- `current_unit`: 현재 학습 단원
- `current_unit_accuracy`: 현재 단원 정답률
- `self_directed_learning_capability`: 자기주도 학습 능력

#### 포괄형 질문 2: 스토리 테마 및 내러티브 구조 관련 데이터

**피드백 내용 기반 스토리 테마 선택:**
- `problem_redefinition`: 문제 재정의 (Agent 15 연계)
- `problem_improvement_ideas`: 문제 개선 아이디어 (Agent 15 연계)
- `persona_type`: 페르소나 유형 (Agent 07 연계)
- `emotion_tone`: 감정 톤 (Agent 05 연계)
- `worldview_data`: 세계관 데이터
- `learning_emotion`: 학습 감정 (Agent 05 연계)
- `emotion_state`: 감정 상태 (Agent 05 연계)
- `fatigue_level`: 피로도 (Agent 05 연계)
- `motivation_level`: 동기 수준 (Agent 05 연계)
- `student_level`: 학생 수준
- `error_pattern`: 오류 패턴 (Agent 11 연계)
- `recent_error_count`: 최근 오류 횟수 (Agent 11 연계)
- `error_recovery_resilience`: 오류 회복 탄력성

**서사 구조(도입-전개-결말) 설계:**
- `focus_pattern`: 집중력 패턴 (Agent 05, Agent 14 연계)
- `session_stage`: 세션 단계
- `immersion_score_by_stage`: 단계별 몰입도 점수
- `attention_duration`: 주의 지속 시간
- `previous_emotional_flow`: 이전 감정 흐름
- `previous_dialogue_tone`: 이전 대화 톤
- `narrative_continuity`: 서사 연속성
- `character_consistency`: 캐릭터 일관성
- `metacognitive_level`: 메타인지 수준 (Agent 04 연계)
- `self_explanation_score`: 자기 설명 점수
- `question_timing`: 질문 타이밍
- `insight_provision_timing`: 인사이트 제공 타이밍

**감정 흐름 설계 및 톤 조정:**
- `emotion_recovery_speed`: 감정 회복 속도 (Agent 05 연계)
- `emotional_return_pattern`: 정서적 복귀 패턴 (Agent 05 연계)
- `emotion_curve`: 감정 곡선 (Agent 05 연계)
- `recovery_resilience`: 회복 탄력성
- `math_learning_style`: 수학 학습 스타일 (Agent 01 연계)
- `preferred_dialogue_tone`: 선호 대화 톤
- `tone_effectiveness_data`: 톤 효과성 데이터
- `interaction_history`: 상호작용 이력
- `academy_context`: 학원 맥락 (Agent 01 연계)
- `pre_class_emotion`: 수업 전 감정 (Agent 05 연계)
- `post_class_emotion`: 수업 후 감정 (Agent 05 연계)
- `problem_solving_emotion`: 문제 풀이 감정 (Agent 05 연계)
- `solving_stage`: 풀이 단계

#### 포괄형 질문 3: 상호작용 연속성 관련 데이터

**이전 상호작용 맥락 추적 및 연결:**
- `previous_worldview`: 이전 세계관
- `previous_emotional_tone`: 이전 감정 톤
- `previous_dialogue_flow`: 이전 대화 흐름
- `previous_character_role`: 이전 캐릭터 역할
- `worldview_compatibility`: 세계관 호환성
- `previous_interaction_topic`: 이전 상호작용 주제
- `current_feedback_content`: 현재 피드백 내용
- `topic_connection`: 주제 연결
- `previous_interaction_response`: 이전 상호작용 반응
- `read_rate`: 읽음률
- `response_time`: 응답 시간
- `behavior_change`: 행동 변화
- `interaction_effectiveness`: 상호작용 효과성

**캐릭터 일관성 및 대화 톤 매칭:**
- `previous_character_role`: 이전 캐릭터 역할
- `character_consistency`: 캐릭터 일관성
- `situation_change`: 상황 변화
- `role_transition_needed`: 역할 전환 필요 여부
- `previous_dialogue_tone`: 이전 대화 톤
- `current_feedback_purpose`: 현재 피드백 목적
- `tone_maintenance`: 톤 유지
- `tone_adjustment`: 톤 조정
- `preferred_expression_style`: 선호 표현 스타일
- `preferred_dialogue_style`: 선호 대화 스타일
- `interaction_history`: 상호작용 이력
- `student_preference`: 학생 선호도

**연속성 유지를 위한 개인화 전략:**
- `interaction_history_count`: 상호작용 이력 횟수
- `worldview_effectiveness_data`: 세계관 효과성 데이터
- `preferred_worldview`: 선호 세계관
- `worldview_priority`: 세계관 우선순위
- `effective_narrative_structure`: 효과적인 서사 구조
- `effective_emotional_flow`: 효과적인 감정 흐름
- `pattern_replication`: 패턴 재현
- `pattern_improvement`: 패턴 개선
- `long_term_learning_trajectory`: 장기 학습 궤도
- `cumulative_interaction_effects`: 누적 상호작용 효과
- `narrative_role`: 서사 역할
- `story_continuity`: 스토리 연속성

### 온톨로지 매핑

Agent 16의 데이터 기반 질문에서 사용하는 데이터 소스들은 다음 온톨로지 클래스와 매핑됩니다:

**핵심 온톨로지:**
- `WorldviewSelection`: 학생 상태 기반 세계관 선택
- `StoryThemeSelection`: 피드백 내용 기반 스토리 테마 선택
- `InteractionContinuity`: 이전 상호작용 맥락 추적 및 연결

**보조 온톨로지:**
- `WorldviewSituationMapping`: 세계관별 상황 매핑
- `WorldviewCompatibility`: 세계관 호환성
- `NarrativeStructure`: 서사 구조
- `EmotionalFlowDesign`: 감정 흐름 설계
- `CharacterConsistency`: 캐릭터 일관성
- `PersonalizationStrategy`: 개인화 전략

**데이터 소스 온톨로지:**
- `EmotionStability`: 감정 안정도
- `RoutineMaintenanceRate`: 루틴 유지율
- `GoalMode`: 목표 모드
- `ImmersionScore`: 몰입도 점수
- `WorldviewEffectivenessData`: 세계관 효과성 데이터
- `AcademyClassTime`: 학원 수업 시간
- `ProblemSolvingStatus`: 문제 풀이 상태
- `PreviousWorldview`: 이전 세계관
- `PreviousEmotionalTone`: 이전 감정 톤
- `PreviousDialogueTone`: 이전 대화 톤
- `PreviousDialogueFlow`: 이전 대화 흐름
- `PreviousCharacterRole`: 이전 캐릭터 역할
- `PreferredDialogueTone`: 선호 대화 톤
- `ToneEffectivenessData`: 톤 효과성 데이터

---

**참고**: 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
