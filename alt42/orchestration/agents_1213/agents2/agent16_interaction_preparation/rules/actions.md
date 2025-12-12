# Agent 16 - Interaction Preparation Actions
# 현직 수학선생님 수준 90% 달성을 위한 액션 목록

## 수행 가능한 액션 목록

### S0: 학생 상태 파악 및 데이터 수집

#### 데이터 수집 액션
- `collect_info: 'math_learning_stage'`: 수학 학습 단계 수집 (개념학습/유형연습/심화/기출)
- `collect_info: 'math_performance'`: 수학 성과 수집 (정답률, 단원별 성취도)
- `collect_info: 'math_learning_style'`: 수학 학습 스타일 수집 (계산형/개념형/응용형)
- `collect_info: 'weak_units'`: 취약 단원 목록 수집
- `collect_info: 'academy_info'`: 학원 수업 맥락 정보 수집
- `fetch_from_agent: 'agent01', 'math_learning_style'`: Agent 01에서 학습 스타일 데이터 가져오기
- `fetch_from_agent: 'agent05', 'learning_emotion'`: Agent 05에서 학습 감정 데이터 가져오기
- `fetch_from_agent: 'agent13', 'dropout_pattern'`: Agent 13에서 학습 이탈 패턴 가져오기
- `fetch_from_agent: 'agent14', 'current_position'`: Agent 14에서 현재 위치 데이터 가져오기
- `fetch_from_agent: 'agent15', 'problem_redefinition'`: Agent 15에서 문제 재정의 데이터 가져오기

#### 데이터 처리 액션
- `calculate: 'student_level'`: 학생 수준 자동 계산 (정답률 기반)
- `calculate: 'weak_units'`: 취약 단원 자동 식별 (단원별 정답률 < 60%)
- `validate: 'validation_message'`: 데이터 유효성 검증
- `store: 'field_name'`: 데이터 저장

#### 질문 액션
- `question: 'question_text'`: 학생에게 질문하기
- `display_message: 'message'`: 메시지 표시

---

### S1: 수학 학습 단계별 세계관 매핑

#### 세계관 선택 액션
- `select_worldview: '탐구학습'`: 탐구학습 모드 선택
- `select_worldview: '도제학습'`: 도제학습 모드 선택
- `select_worldview: '시험대비'`: 시험대비 모드 선택
- `select_worldview: '단기미션'`: 단기미션 모드 선택
- `select_worldview: '자기성찰'`: 자기성찰 모드 선택
- `select_worldview: '자기주도'`: 자기주도 모드 선택
- `select_worldview: '맞춤학습'`: 맞춤학습 모드 선택
- `select_worldview: '시간성찰'`: 시간성찰 모드 선택
- `select_worldview: '커리큘럼'`: 커리큘럼 모드 선택

#### 스토리 구조 설계 액션
- `set_narrative_theme: 'theme_text'`: 스토리 테마 설정
- `set_tone: 'tone_type'`: 대화 톤 설정 (호기심 유도형, 안내형, 목표 지향형 등)
- `set_character: 'character_name'`: 캐릭터 설정 (멘토봇, 탐구조교, 루틴마스터 등)
- `set_priority: 'high' | 'medium' | 'low'`: 우선순위 설정

#### 조건부 액션
- `if: 'condition' -> 'action'`: 조건부 액션 실행
- `if_compatible: 'maintain_worldview'`: 호환성 확인 후 세계관 유지
- `if_not_compatible: 'transition_smoothly'`: 호환 안 되면 자연스러운 전환

---

### S2: 학원 수업 맥락 고려

#### 학원 수업 전 준비 액션
- `prepare_pre_class_interaction: 'preparation_type'`: 학원 수업 전 상호작용 준비
  - preparation_type: 예습 가이드, 개념 리마인드, 문제 선행 학습
- `select_worldview_by_preparation_type: 'type'`: 준비 유형별 세계관 선택

#### 학원 수업 후 준비 액션
- `prepare_post_class_interaction: 'understanding_level'`: 학원 수업 후 상호작용 준비
  - understanding_level: 완전 이해, 부분 이해, 이해 부족
- `select_worldview_by_understanding: 'level'`: 이해도별 세계관 선택

---

### S3: 문제 풀이 중 실시간 상호작용 준비

#### 막힘 감지 액션
- `detect_problem_type: 'concept' | 'calculation'`: 문제 유형 감지
- `detect_stuck: 'threshold_minutes'`: 막힘 감지 (임계값: 분)
- `prepare_hint: 'hint_content'`: 힌트 준비
- `prepare_step_by_step_guide: 'guide_content'`: 단계별 안내 준비

#### 계산 실수 감지 액션
- `classify_error_type: 'error_type'`: 계산 실수 유형 분류
  - error_type: 부호 실수, 계산 과정 오류, 답안 옮기기 실수
- `prepare_attention_guide: 'guide_content'`: 주의 안내 준비

#### 진도 기반 준비 액션
- `detect_progress_delay: 'expected_speed'`: 진도 지연 감지
- `prepare_time_management_guide: 'tips'`: 시간 관리 전략 안내 준비

---

### S4: 단원별 취약점 기반 상호작용 준비

#### 취약 단원 감지 액션
- `identify_weak_units: 'threshold'`: 취약 단원 식별 (임계값: 정답률)
- `select_worldview_for_weak_unit: 'unit_name'`: 취약 단원용 세계관 선택

#### 단원별 전략 액션
- `apply_unit_specific_strategy: 'unit_name'`: 단원별 특성 반영 전략 적용
  - 함수 단원: 그래프 이해 중심
  - 도형 단원: 작도 과정 중심
  - 통계 단원: 데이터 해석 중심
  - 방정식 단원: 단계별 풀이 과정 중심
- `adjust_worldview_narrative: 'unit_characteristics'`: 단원 특성 반영 서사 조정

---

### S5: 학생 수준별 차별화

#### 수준별 차별화 액션
- `select_worldview_by_level: 'student_level'`: 학생 수준별 세계관 선택
  - 하위권: 자기성찰 모드
  - 중위권: 단기미션 모드
  - 상위권: 탐구학습 모드
- `adjust_tone_by_level: 'level'`: 수준별 톤 조정
- `adjust_narrative_theme: 'level_appropriate_theme'`: 수준별 적합한 테마 조정

#### 학습 스타일 기반 조정 액션
- `adjust_to: 'worldview'`: 학습 스타일 기반 세계관 조정
- `preserve_worldview_if_conflict: 'action'`: 충돌 시 기존 선택 유지하되 톤 조정

---

### S6: 상호작용 연속성 및 개인화

#### 연속성 유지 액션
- `check_worldview_compatibility: 'previous_worldview'`: 이전 세계관과 호환성 확인
- `maintain_worldview: 'worldview'`: 세계관 유지
- `transition_smoothly: 'new_worldview'`: 자연스러운 전환
- `maintain_character_consistency: 'character'`: 캐릭터 일관성 유지
- `preserve_emotional_tone: 'tone'`: 감정 톤 보존

#### 개인화 액션
- `analyze_effectiveness: 'worldview_data'`: 세계관별 효과성 분석
- `identify_preferred_worldview: 'effectiveness_data'`: 학생별 선호 세계관 식별
- `prioritize_preferred: 'worldview'`: 선호 세계관 우선 선택
- `track_effectiveness: 'metrics'`: 효과성 추적 지속
  - metrics: 학습 지속성, 정답률 향상 등

---

### 공통 액션

#### 데이터베이스 액션
- `load_db: 'db_name'`: 데이터베이스 로드
- `save_interaction_effectiveness: 'data'`: 상호작용 효과성 데이터 저장
- `get_preferred_worldview: 'studentid'`: 학생별 선호 세계관 조회

#### 검증 액션
- `validate_all: ['field1', 'field2', ...]`: 모든 필드 검증
- `analyze: 'analysis_type'`: 분석 수행
- `generate_description: 'description_type'`: 설명 생성
- `recommend_path: 'recommendation'`: 경로 추천

---

## 액션 실행 우선순위

1. **긴급 상황** (문제 풀이 중 막힘 감지)
   - `detect_stuck` → `select_worldview` → `prepare_hint`

2. **학원 수업 맥락** (수업 전후)
   - `prepare_pre_class_interaction` 또는 `prepare_post_class_interaction` → `select_worldview`

3. **학습 단계 기반**
   - `select_worldview_by_stage` → `set_narrative_theme` → `set_tone`

4. **취약 단원 기반**
   - `identify_weak_units` → `select_worldview_for_weak_unit` → `apply_unit_specific_strategy`

5. **학생 수준 기반**
   - `select_worldview_by_level` → `adjust_tone_by_level`

6. **연속성 및 개인화**
   - `check_worldview_compatibility` → `maintain_worldview` 또는 `transition_smoothly`

---

**참고**: 모든 액션은 `rules.yaml`의 룰 정의에 따라 조건부로 실행됩니다.
