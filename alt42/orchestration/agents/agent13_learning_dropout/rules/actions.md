# Agent 13 - Learning Dropout Actions

## 수행 가능한 액션 목록

### 탐지
- `detect_dropout_risk`: 이탈 위험도 탐지
- `calculate_risk_tier`: 위험 등급 계산
- `identify_dropout_patterns`: 이탈 패턴 식별

### 수학 특화 탐지 (추가)
- `analyze_unit_difficulty_dropout`: 단원별 난이도 기반 이탈 분석
- `analyze_difficulty_based_dropout`: 난이도별 이탈 패턴 분석
- `analyze_learning_stage_dropout`: 학습 단계별 이탈 패턴 분석
- `analyze_academy_understanding_dropout`: 학원 수업 이해도 기반 이탈 분석
- `analyze_homework_burden_dropout`: 학원 과제 부담 기반 이탈 분석
- `analyze_level_based_dropout`: 수학 성적 수준별 이탈 원인 분석
- `analyze_learning_style_dropout`: 수학 학습 스타일별 이탈 패턴 분석

### 개입
- `send_refocus_message`: 리포커스 메시지 전송
- `suggest_easy_win`: 쉬운 승리 제안
- `adjust_routine`: 루틴 조정

### 수학 특화 개입 (추가)
- `suggest_unit_basic_concept_review`: 단원별 기본 개념 재학습 제안
- `suggest_switch_to_basic_problems`: 기본형 문제로 전환 제안
- `suggest_visual_concept_explanation`: 시각 자료/예시 활용 설명 보강
- `suggest_switch_to_easier_problems`: 쉬운 문제로 전환 제안
- `suggest_review_unclear_concepts`: 이해 못한 개념 복습 제안
- `suggest_academy_textbook_concept_review`: 학원 교재 개념 부분 재학습 제안
- `suggest_prioritize_homework_tasks`: 과제 우선순위 조정 제안
- `suggest_start_with_easiest_problems`: 가장 쉬운 문제부터 시작 제안
- `suggest_routine_strengthening_task`: 루틴 강화 과제 제안
- `suggest_challenging_task_presentation`: 도전 과제 제시 (상위권)
- `suggest_style_matched_activity`: 학습 스타일 맞는 활동 전환 제안

### 루틴 조정 (수학 특화 추가)
- `adjust_routine: 'unit_based_session_length'`: 단원 기반 세션 길이 조정
- `adjust_routine: 'reduce_difficulty_level'`: 난이도 레벨 감소
- `adjust_routine: 'step_by_step_difficulty'`: 단계적 난이도 증가
- `adjust_routine: 'concept_first_then_problems'`: 개념 먼저, 문제 나중
- `adjust_routine: 'problem_difficulty_adjustment'`: 문제 난이도 조정
- `adjust_routine: 'homework_priority_adjustment'`: 과제 우선순위 조정
- `adjust_routine: 'low_level_focused_session'`: 하위권 집중 세션
- `adjust_routine: 'mid_level_routine_focus'`: 중위권 루틴 집중
- `adjust_routine: 'high_level_challenge_focus'`: 상위권 도전 집중
- `adjust_routine: 'style_based_activity_switch'`: 스타일 기반 활동 전환

### 에스컬레이션
- `notify_parents`: 보호자 알림
- `notify_teacher`: 담임 알림

### 데이터 수집 (추가)
- `collect_info: 'current_math_unit'`: 현재 학습 중인 수학 단원 수집
- `collect_info: 'problem_difficulty'`: 문제 난이도 수집
- `collect_info: 'learning_stage'`: 학습 단계 수집
- `collect_info: 'academy_class_understanding'`: 학원 수업 이해도 수집
- `collect_info: 'academy_homework_burden'`: 학원 과제 부담 수집
- `collect_info: 'math_level'`: 수학 성적 수준 수집

### 데이터베이스 로드 (추가)
- `load_db: 'math_unit_difficulty.yaml'`: 단원별 난이도 DB 로드
- `load_db: 'unit_intervention_strategies.yaml'`: 단원별 개입 전략 DB 로드
- `load_db: 'learning_style_activities.yaml'`: 학습 스타일별 적합 활동 DB 로드

