# Agent 14 - Current Position Actions

## 수행 가능한 액션 목록

### 기본 분석
- `calculate_completion_rate`: 완료율 계산
- `assess_progress_status`: 진행 상태 평가 (원활/적절/지연/정체)
- `analyze_emotion_state`: 감정 상태 분석
- `calculate_rhythm_score`: 학습 리듬 점수 계산 (0~100)
- `calculate_risk_index`: 위험도 점수 계산 (Low/Medium/High/Critical)

### 수학 교과 특화 분석
- `extract_unit_name`: plan1~plan16에서 단원명 추출
- `calculate_unit_completion_rate`: 단원별 완료율 계산
- `analyze_unit_delay_pattern`: 단원별 지연 패턴 분석
- `classify_difficulty`: 난이도 분류 (기본형/유형/심화)
- `calculate_difficulty_completion_time`: 난이도별 평균 소요 시간 계산
- `classify_learning_stage`: 학습 단계 구분 (개념/유형/심화/기출)
- `extract_problem_types`: 단원별 문제 유형 분류
- `classify_error_type`: 계산 실수 vs 개념 오류 구분

### 학원 맥락 통합 분석
- `analyze_comprehension_delay_correlation`: 학원 수업 이해도와 학습 지연 상관 분석
- `analyze_homework_delay_correlation`: 학원 과제 양과 학습 지연 상관 분석
- `calculate_progress_gap`: 학원 진도와 학교 진도 차이 계산
- `analyze_progress_alignment`: 진도 정렬도 분석

### 학생 수준별 차별화 분석
- `classify_math_level`: 수학 성적 수준 분류 (하위권/중위권/상위권)
- `analyze_level_progress_pattern`: 수준별 진행 패턴 분석
- `classify_learning_style`: 수학 학습 스타일 분류 (계산형/개념형/응용형)
- `analyze_style_progress_pattern`: 학습 스타일별 진행 패턴 분석

### 조치
- `suggest_micro_goals`: 마이크로 목표 제안
- `split_delayed_items`: 지연 항목 분할
- `suggest_deep_learning`: 심화 학습 제안
- `suggest_unit_strategy`: 단원별 맞춤 전략 제안
- `suggest_difficulty_strategy`: 난이도별 맞춤 전략 제안
- `suggest_stage_strategy`: 학습 단계별 맞춤 전략 제안
- `suggest_error_strategy`: 오류 유형별 맞춤 전략 제안

### 리포트 생성
- `generate_comprehensive_report`: 종합 리포트 생성
- `create_agent_summary`: Agent Summary 형식 요약 생성
- `generate_unit_insight`: 단원별 인사이트 생성
- `generate_difficulty_insight`: 난이도별 인사이트 생성
- `generate_stage_insight`: 학습 단계별 인사이트 생성
- `generate_comprehension_insight`: 학원 수업 이해도 기반 인사이트 생성
- `generate_homework_insight`: 학원 과제 부담 기반 인사이트 생성
- `generate_level_insight`: 학생 수준별 인사이트 생성

### 전달
- `send_to_agent09`: Agent 09 (Learning Management)에 전달
- `send_to_agent12`: Agent 12 (Rest Routine)에 전달
- `send_to_agent13`: Agent 13 (Learning Dropout)에 전달

