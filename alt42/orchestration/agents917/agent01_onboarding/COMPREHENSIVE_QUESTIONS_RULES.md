# Agent01 Onboarding 포괄형 질문 룰 완성 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**버전**: 1.0

---

## 개요

[questions.html](https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/questions.html#agent01)의 포괄형 질문 3개에 대해 섬세한 답변이 가능하도록 `rules.yaml`에 룰을 추가했습니다.

---

## 포괄형 질문 1: 첫 수업 시작 전략

**질문**: "이 학생의 현재 수학 학습 맥락을 종합해서, 첫 수업에서 무엇을 어떻게 시작해야 할지 알려줘."

**요구사항**: 온보딩 정보, 개념/심화 진도, 학습 스타일, 시험 대비 성향, 자신감 수준 등을 반영해서 수업 도입 루틴, 설명 전략, 자료 유형 등을 포함한 총체적 개입 전략 도출

### 추가된 룰 (4개)

| Rule ID | 우선순위 | 설명 | 주요 조건 |
|---------|----------|------|-----------|
| `Q1_comprehensive_first_class_strategy` | 100 | 첫 수업 종합 전략 도출 | user_message contains "첫 수업" + "어떻게 시작" |
| `Q1_introduction_routine_by_confidence` | 98 | 자신감 수준별 도입 루틴 | math_confidence <= 5, concept_progress != null |
| `Q1_explanation_strategy_by_learning_style` | 97 | 학습 스타일별 설명 전략 | math_learning_style != null, study_style != null |
| `Q1_material_type_by_progress` | 96 | 진도별 자료 유형 추천 | concept_progress, advanced_progress, textbooks != null |

### 사용 데이터 필드

- `math_level` - 수학 수준
- `concept_progress` - 개념 진도
- `advanced_progress` - 심화 진도
- `math_learning_style` - 수학 학습 스타일 (계산형/개념형/응용형)
- `study_style` - 학습 스타일
- `exam_style` - 시험 대비 성향
- `math_confidence` - 수학 자신감 (0-10)
- `textbooks` - 사용 중인 교재

### 출력 요소

1. **수업 도입 루틴**: 자신감 수준에 맞춘 부드러운 도입 또는 도전적 도입
2. **설명 전략**: 학습 스타일별 맞춤형 설명 방식
3. **자료 유형**: 진도와 교재에 맞춘 자료 선택

---

## 포괄형 질문 2: 커리큘럼과 루틴 최적화

**질문**: "이 학생의 성향과 목표를 기반으로, 커리큘럼과 루틴을 어떤 방향으로 최적화해야 할까?"

**요구사항**: 목표(단기/중기/장기), 학습 성향, 스트레스/자신감, 부모 개입도 등을 고려하여 진도별 우선순위, 학습 흐름, 문제 유형 비중 조절 등 커스터마이징 설계 도출

### 추가된 룰 (5개)

| Rule ID | 우선순위 | 설명 | 주요 조건 |
|---------|----------|------|-----------|
| `Q2_comprehensive_curriculum_routine_optimization` | 100 | 커리큘럼과 루틴 종합 최적화 | user_message contains "커리큘럼" + "루틴"/"최적화" |
| `Q2_progress_priority_by_goals` | 99 | 목표 기반 진도별 우선순위 | long_term_goal, concept_progress, advanced_progress != null |
| `Q2_learning_flow_by_style` | 98 | 학습 성향 기반 학습 흐름 | study_style, math_learning_style, exam_style != null |
| `Q2_problem_type_ratio_by_confidence` | 97 | 자신감/스트레스 기반 문제 유형 비중 | math_confidence, stress_level, math_level != null |
| `Q2_parent_involvement_integration` | 96 | 부모 개입도 반영 커리큘럼 조정 | parent_style, weekly_hours != null |

### 사용 데이터 필드

- `short_term_goal` - 단기 목표
- `mid_term_goal` - 중기 목표
- `long_term_goal` - 장기 목표
- `study_style` - 학습 스타일
- `math_learning_style` - 수학 학습 스타일
- `math_confidence` - 수학 자신감
- `stress_level` - 스트레스 수준
- `parent_style` - 부모 개입 스타일
- `weekly_hours` - 주간 학습 시간
- `concept_progress` - 개념 진도
- `advanced_progress` - 심화 진도
- `exam_style` - 시험 대비 성향

### 출력 요소

1. **진도별 우선순위**: 목표 달성을 위한 개념/심화 진도 우선순위
2. **학습 흐름**: 개념-문제풀이-심화의 비중과 순서 조정
3. **문제 유형 비중**: 기초:기본:심화 문제 비중 조절

---

## 포괄형 질문 3: 중장기 성장 전략

**질문**: "이 학생이 중장기적으로 성장하기 위해 지금부터 어떤 부분을 특히 신경 써야 할까?"

**요구사항**: 경시 준비, 진학 목표, 수학 자존감 성장, 피로 누적 방지, 루틴 유지 여부 등을 포괄적으로 분석하여 조기 리스크 예측 및 트래킹 우선요소 추천

### 추가된 룰 (7개)

| Rule ID | 우선순위 | 설명 | 주요 조건 |
|---------|----------|------|-----------|
| `Q3_comprehensive_long_term_growth_strategy` | 100 | 중장기 성장 종합 전략 | user_message contains "중장기" + "성장"/"신경 써야 할까" |
| `Q3_competition_prep_risk_assessment` | 99 | 경시 준비 리스크 평가 | long_term_goal contains "경시"/"경시대회"/"올림피아드" |
| `Q3_university_goal_alignment` | 98 | 진학 목표 정렬 및 달성 경로 | long_term_goal contains "학교"/"대학"/"진학" |
| `Q3_math_confidence_growth_strategy` | 97 | 수학 자존감 성장 전략 | math_confidence <= 6, math_level in ["수학이 어려워요", "중위권"] |
| `Q3_fatigue_accumulation_prevention` | 96 | 피로 누적 방지 전략 | stress_level in ["높음", "매우 높음"] OR weekly_hours >= 20 OR exam_style == "벼락치기" |
| `Q3_routine_sustainability_check` | 95 | 루틴 유지 가능성 평가 | study_hours_per_week, exam_style, parent_style != null |
| `Q3_early_risk_prediction_comprehensive` | 94 | 조기 리스크 예측 및 트래킹 | math_confidence <= 5 OR stress_level 높음 OR weekly_hours < 10 |

### 사용 데이터 필드

- `long_term_goal` - 장기 목표
- `math_confidence` - 수학 자신감
- `stress_level` - 스트레스 수준
- `weekly_hours` - 주간 학습 시간
- `exam_style` - 시험 대비 성향
- `parent_style` - 부모 개입 스타일
- `math_level` - 수학 수준
- `concept_progress` - 개념 진도
- `advanced_progress` - 심화 진도
- `academy_experience` - 학원 경험

### 출력 요소

1. **경시 준비**: 현실성 평가 및 리스크 관리
2. **진학 목표**: 단계별 마일스톤 설정
3. **자존감 성장**: 점진적 난이도 상승 계획
4. **피로 방지**: 휴식 루틴 및 모니터링 포인트
5. **루틴 유지**: 지속 가능한 루틴 설계 및 장애 요인 예측
6. **조기 리스크**: 위험 요소 식별 및 트래킹 우선요소 추천

---

## 룰 통계

### 전체 룰 수
- **기존 룰**: 30개 (S0~S5, C1~C3, B1~B5)
- **신규 추가 룰**: 16개 (Q1: 4개, Q2: 5개, Q3: 7개)
- **총 룰 수**: 46개

### 우선순위 분포

| 우선순위 범위 | 룰 수 | 설명 |
|--------------|-------|------|
| 100 | 3개 | 포괄형 질문 종합 전략 (Q1, Q2, Q3) |
| 95-99 | 8개 | 핵심 세부 전략 |
| 85-94 | 15개 | 일반 전략 및 복합 상황 |
| 80-84 | 8개 | 기본 루틴 및 경고 |
| 50 | 1개 | 기본 fallback 룰 |

---

## 데이터 필드 매핑 상태

### 포괄형 질문 1에 필요한 필드
- ✅ `math_level` - data_access.php에 매핑됨
- ✅ `concept_progress` - data_access.php에 매핑됨
- ✅ `advanced_progress` - data_access.php에 매핑됨
- ✅ `math_learning_style` - data_access.php에 매핑됨
- ✅ `study_style` - data_access.php에 매핑됨
- ✅ `exam_style` - data_access.php에 매핑됨
- ✅ `math_confidence` - data_access.php에 매핑됨
- ✅ `textbooks` - data_access.php에 매핑됨
- ✅ `notes` - data_access.php에 매핑됨 (2025-01-27 추가)
- ✅ `weekly_hours` - data_access.php에 매핑됨 (2025-01-27 추가)

### 포괄형 질문 2에 필요한 필드
- ✅ `short_term_goal` - data_access.php에 매핑됨 (2025-01-27 추가)
- ✅ `mid_term_goal` - data_access.php에 매핑됨 (2025-01-27 추가)
- ✅ `long_term_goal` - data_access.php에 매핑됨 (2025-01-27 추가)
- ✅ `stress_level` - data_access.php에 매핑됨
- ✅ `parent_style` - data_access.php에 매핑됨
- ✅ `weekly_hours` - data_access.php에 매핑됨 (2025-01-27 추가)

### 포괄형 질문 3에 필요한 필드
- ✅ `long_term_goal` - data_access.php에 매핑됨 (2025-01-27 추가)
- ✅ `math_confidence` - data_access.php에 매핑됨
- ✅ `stress_level` - data_access.php에 매핑됨
- ✅ `weekly_hours` - data_access.php에 매핑됨 (2025-01-27 추가)
- ✅ `exam_style` - data_access.php에 매핑됨
- ✅ `parent_style` - data_access.php에 매핑됨
- ✅ `academy_experience` - data_access.php에 매핑됨 (2025-01-27 추가)

---

## 룰 실행 흐름

### 포괄형 질문 1 실행 흐름

```
[사용자 질문: "첫 수업 어떻게 시작해야 할지 알려줘"]
  ↓
[Q1_comprehensive_first_class_strategy] (priority: 100)
  ├─→ [Q1_introduction_routine_by_confidence] (priority: 98)
  ├─→ [Q1_explanation_strategy_by_learning_style] (priority: 97)
  └─→ [Q1_material_type_by_progress] (priority: 96)
  ↓
[종합 전략 도출]
  - 수업 도입 루틴
  - 설명 전략
  - 자료 유형
```

### 포괄형 질문 2 실행 흐름

```
[사용자 질문: "커리큘럼과 루틴을 어떤 방향으로 최적화해야 할까?"]
  ↓
[Q2_comprehensive_curriculum_routine_optimization] (priority: 100)
  ├─→ [Q2_progress_priority_by_goals] (priority: 99)
  ├─→ [Q2_learning_flow_by_style] (priority: 98)
  ├─→ [Q2_problem_type_ratio_by_confidence] (priority: 97)
  └─→ [Q2_parent_involvement_integration] (priority: 96)
  ↓
[최적화 전략 도출]
  - 진도별 우선순위
  - 학습 흐름
  - 문제 유형 비중
```

### 포괄형 질문 3 실행 흐름

```
[사용자 질문: "중장기적으로 성장하기 위해 어떤 부분을 신경 써야 할까?"]
  ↓
[Q3_comprehensive_long_term_growth_strategy] (priority: 100)
  ├─→ [Q3_competition_prep_risk_assessment] (priority: 99) - 경시 준비
  ├─→ [Q3_university_goal_alignment] (priority: 98) - 진학 목표
  ├─→ [Q3_math_confidence_growth_strategy] (priority: 97) - 자존감 성장
  ├─→ [Q3_fatigue_accumulation_prevention] (priority: 96) - 피로 방지
  ├─→ [Q3_routine_sustainability_check] (priority: 95) - 루틴 유지
  └─→ [Q3_early_risk_prediction_comprehensive] (priority: 94) - 리스크 예측
  ↓
[성장 전략 도출]
  - 조기 리스크 예측
  - 트래킹 우선요소 추천
```

---

## 룰 상세 설명

### Q1: 첫 수업 시작 전략 룰

#### Q1_comprehensive_first_class_strategy
- **조건**: 사용자 메시지에 "첫 수업" 및 "어떻게 시작"/"시작해야 할지" 포함
- **동작**: 
  - 수학 학습 맥락 종합 분석
  - 도입 루틴, 설명 전략, 자료 유형 통합 설계
- **출력**: 총체적 개입 전략

#### Q1_introduction_routine_by_confidence
- **조건**: 자신감 <= 5, 개념 진도 존재
- **동작**: 자신감 수준에 맞춘 부드러운 도입 루틴 설계
- **출력**: 성공 경험 제공을 위한 도입 전략

#### Q1_explanation_strategy_by_learning_style
- **조건**: 수학 학습 스타일 및 학습 스타일 존재
- **동작**: 학습 스타일별 맞춤형 설명 전략 선택
- **출력**: 개인화된 설명 방식

#### Q1_material_type_by_progress
- **조건**: 개념/심화 진도 및 교재 정보 존재
- **동작**: 진도와 교재에 맞춘 자료 유형 추천
- **출력**: 최적 자료 유형

### Q2: 커리큘럼과 루틴 최적화 룰

#### Q2_comprehensive_curriculum_routine_optimization
- **조건**: 사용자 메시지에 "커리큘럼" 및 "루틴"/"최적화" 포함
- **동작**: 성향과 목표 종합 분석하여 최적화 전략 도출
- **출력**: 커스터마이징 설계

#### Q2_progress_priority_by_goals
- **조건**: 장기 목표 및 개념/심화 진도 존재
- **동작**: 목표 달성을 위한 진도 우선순위 설정
- **출력**: 우선순위 시퀀스

#### Q2_learning_flow_by_style
- **조건**: 학습 스타일, 수학 학습 스타일, 시험 스타일 존재
- **동작**: 학습 성향에 맞춘 학습 흐름 설계
- **출력**: 개인화된 학습 순서

#### Q2_problem_type_ratio_by_confidence
- **조건**: 자신감, 스트레스, 수학 수준 존재
- **동작**: 자신감과 스트레스 기반 문제 유형 비중 조절
- **출력**: 문제 유형 분배

#### Q2_parent_involvement_integration
- **조건**: 부모 스타일 및 주간 학습 시간 존재
- **동작**: 부모 개입도 반영 커리큘럼 조정
- **출력**: 가족 통합 학습 계획

### Q3: 중장기 성장 전략 룰

#### Q3_comprehensive_long_term_growth_strategy
- **조건**: 사용자 메시지에 "중장기" 및 "성장"/"신경 써야 할까" 포함
- **동작**: 중장기 성장 종합 분석
- **출력**: 리스크 예측 및 트래킹 우선요소

#### Q3_competition_prep_risk_assessment
- **조건**: 장기 목표에 "경시"/"경시대회"/"올림피아드" 포함
- **동작**: 경시 준비 현실성 및 리스크 평가
- **출력**: 경시 준비 전략

#### Q3_university_goal_alignment
- **조건**: 장기 목표에 "학교"/"대학"/"진학" 포함
- **동작**: 진학 목표 정렬 및 달성 경로 설계
- **출력**: 단계별 마일스톤

#### Q3_math_confidence_growth_strategy
- **조건**: 자신감 <= 6, 수학 수준이 어려움/중위권
- **동작**: 자존감 성장 전략 수립
- **출력**: 점진적 난이도 상승 계획

#### Q3_fatigue_accumulation_prevention
- **조건**: 스트레스 높음 OR 주간 학습 시간 >= 20시간 OR 벼락치기
- **동작**: 피로 누적 방지 전략 수립
- **출력**: 휴식 루틴 및 모니터링 포인트

#### Q3_routine_sustainability_check
- **조건**: 주간 학습 시간, 시험 스타일, 부모 스타일 존재
- **동작**: 루틴 유지 가능성 평가
- **출력**: 지속 가능한 루틴 설계

#### Q3_early_risk_prediction_comprehensive
- **조건**: 자신감 <= 5 OR 스트레스 높음 OR 주간 학습 시간 < 10시간
- **동작**: 조기 리스크 예측
- **출력**: 트래킹 우선요소 추천

---

## 연계 에이전트

### 포괄형 질문 2
- **Agent 03** (목표 분석) - 목표 데이터
- **Agent 05** (감정) - 스트레스/감정 데이터
- **Agent 09** (학습관리) - 학습 루틴 데이터
- **Agent 18** (시그너처루틴) - 루틴 설계

### 포괄형 질문 3
- **Agent 03** (목표) - 장기 목표 데이터
- **Agent 05** (감정) - 피로 패턴 데이터
- **Agent 09** (학습관리) - 루틴 유지 데이터
- **Agent 12** (휴식) - 휴식 루틴 데이터
- **Agent 13** (이탈 방지) - 리스크 레벨 데이터
- **Agent 18** (루틴) - 루틴 설계 데이터

---

## 완성 상태

### ✅ 완료된 작업

1. **포괄형 질문 1 룰 추가** (4개)
   - 종합 전략, 도입 루틴, 설명 전략, 자료 유형

2. **포괄형 질문 2 룰 추가** (5개)
   - 종합 최적화, 진도 우선순위, 학습 흐름, 문제 유형 비중, 부모 개입 통합

3. **포괄형 질문 3 룰 추가** (7개)
   - 종합 성장 전략, 경시 준비, 진학 목표, 자존감 성장, 피로 방지, 루틴 유지, 리스크 예측

4. **필드 매핑 완료**
   - 모든 필요한 필드가 `data_access.php`에 매핑됨
   - `rules.yaml`에 필드 메타데이터 추가됨

5. **문서화 완료**
   - 포괄형 질문 연계 정보 업데이트
   - 각 룰의 연계 관계 명시

---

## 다음 단계

1. ⏳ 룰 엔진에서 OR 조건 처리 로직 확인 필요
2. ⏳ 실제 질문 테스트 및 답변 품질 검증
3. ⏳ 연계 에이전트 데이터 연동 확인

---

**문서 작성자**: Agent 01 Onboarding System  
**문서 위치**: `alt42/orchestration/agents/agent01_onboarding/COMPREHENSIVE_QUESTIONS_RULES.md`  
**마지막 업데이트**: 2025-01-27

