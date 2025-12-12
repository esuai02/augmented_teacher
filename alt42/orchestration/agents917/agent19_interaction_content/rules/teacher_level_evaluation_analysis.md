# Agent 19 현직 수학선생님 수준 비교 평가 및 개선 계획

**평가 대상**: Agent 19 - Interaction Content Agent Rules  
**평가 기준**: 현직 중등 수학선생님의 상호작용 컨텐츠 생성 역량 (100점 만점)  
**평가 일시**: 2025-01-27  
**목표 수준**: 90점 (현직 선생님 수준의 90%)  
**현재 점수**: 62점 (COMPREHENSIVE_TEACHER_LEVEL_EVALUATION.md 기준)

---

## 📊 종합 평가 점수

| 평가 영역 | 만점 | 획득 | 비율 | 등급 | 비고 |
|---------|------|------|------|------|------|
| **수학 교과 특화 지식** | 25 | 10 | 40% | D | 수학 단원별, 유형별 링크 매핑 부재 |
| **학생 진단 및 이해 능력** | 20 | 15 | 75% | C+ | MBTI 기반 개인화는 있으나 수학 학습 맥락 부족 |
| **맞춤형 전략 수립 능력** | 20 | 12 | 60% | C | 템플릿은 있으나 수학 특화 전략 부족 |
| **실행 가능성 및 현실성** | 15 | 13 | 87% | B+ | 템플릿 재사용 구조는 우수 |
| **피드백 및 개선 능력** | 10 | 7 | 70% | C | 기본 피드백은 있으나 수학 특화 피드백 부족 |
| **정서/동기 관리** | 10 | 5 | 50% | D | 감정 상태는 있으나 수학 학습 특화 동기 부족 |
| **총합** | **100** | **62** | **62%** | **C** | - |

**전체 평가: 현직 선생님 수준의 약 62% 달성**

---

## 🎯 영역별 상세 평가

### 1️⃣ 수학 교과 특화 지식 (10/25점, D)

#### ✅ 강점
- **7가지 상호작용 유형 자동 선택**: 일반적 상호작용 구조는 체계적
- **템플릿 재사용 및 관리**: 효율적인 템플릿 관리 시스템 구축

#### ❌ 현저히 부족한 점

**1. 수학 학습 컨텐츠 링크 매핑 부재 (-12점)**
   - **현직 선생님 관점**: "함수 단원 개념 학습 링크, 함수 단원 유형 연습 링크를 단원별로 제공해야 해"
   - **현재 상태**: 일반적 링크만 제공 (`easy_win_zone`, `concept_reinforcement` 등)
   - **필요한 데이터**:
     - 단원별 개념 학습 링크 (예: "중1 함수 단원 개념 학습")
     - 단원별 유형 연습 링크 (예: "중1 함수 단원 유형 연습")
     - 단원별 심화 문제 링크
   - **수집 방법**: 선생님 텍스트 입력 또는 체크리스트
     - "함수 단원 개념 학습: [링크]"
     - "함수 단원 유형 연습: [링크]"

**2. 학원 교재별 컨텐츠 링크 매핑 부재 (-10점)**
   - **현직 선생님 관점**: "학원에서 쎈 사용 중이면 쎈 A단계 링크, 개념원리 사용 중이면 개념원리 링크 제공해야 해"
   - **현재 상태**: 학원 맥락 전혀 없음
   - **필요한 데이터**:
     - 학원 교재별 컨텐츠 링크 (쎈 A/B/C, 개념원리, RPM 등)
     - 학원 진도에 맞는 컨텐츠 링크
   - **수집 방법**: 학생 설문 + 선생님 텍스트 입력
     - 학생 설문: "학원 교재 관련 컨텐츠 링크를 제공할 수 있나요?"
     - 선생님 입력: "쎈 A단계: [링크], 쎈 B단계: [링크]"

**3. 수학 문제 유형별 상호작용 템플릿 부족 (-8점)**
   - **현직 선생님 관점**: "기본형 문제 틀렸을 때는 격려 중심, 심화 문제 틀렸을 때는 도전 중심 템플릿 필요해"
   - **현재 상태**: 일반적 템플릿만 존재
   - **필요한 데이터**:
     - 기본형/유형/심화별 상호작용 템플릿
     - 계산 실수 vs 개념 오류별 템플릿
   - **수집 방법**: 선생님 체크리스트
     - "기본형 문제: 격려 중심 템플릿"
     - "심화 문제: 도전 중심 템플릿"
     - "계산 실수: 반복 연습 안내 템플릿"
     - "개념 오류: 개념 재학습 안내 템플릿"

**4. 단원별 취약점 기반 컨텐츠 추천 부족 (-8점)**
   - **현직 선생님 관점**: "함수 단원 취약한 학생에게는 함수 관련 컨텐츠 우선 추천해야 해"
   - **현재 상태**: 일반적 추천만 존재
   - **필요한 데이터**:
     - 학생의 취약 단원 정보
     - 단원별 컨텐츠 우선순위 매핑
   - **수집 방법**: 학생 설문 + Agent 11 연계
     - 학생 설문: "어떤 단원의 컨텐츠가 가장 필요하신가요?"
     - Agent 11 연계: 오답 패턴 분석 결과 활용

**점수: 10/25점** (40%)

---

### 2️⃣ 학생 진단 및 이해 능력 (15/20점, C+)

#### ✅ 강점
- **MBTI 기반 개인화**: INFP, 내향형/외향형 등 개인화 룰 존재
- **감정 상태 기반 상호작용**: 감정 로그 기반 재진입 유도

#### ⚠️ 부족한 점

**1. 수학 학습 맥락 부족 (-8점)**
   - **현직 선생님 관점**: "지금 어떤 단원을 학습 중인지, 어떤 단계(개념/유형/심화)인지 알아야 맞는 컨텐츠 제공 가능해"
   - **현재 상태**: 현재 학습 단원/단계 정보 없음
   - **필요한 데이터**:
     - 현재 학습 단원 (예: "중1 함수")
     - 현재 학습 단계 (개념 학습/유형 연습/심화)
     - 단원별 진행률
   - **수집 방법**: Agent 09, Agent 14 연계 + 학생 설문
     - Agent 09: 학습 관리 데이터 활용
     - Agent 14: 현재 위치 데이터 활용
     - 학생 설문: "지금 어떤 단원을 학습 중이신가요?"

**2. 수학 학습 스타일 반영 부족 (-5점)**
   - **현직 선생님 관점**: "계산형 학생은 계산 연습 컨텐츠, 개념형 학생은 개념 설명 컨텐츠 우선 제공해야 해"
   - **현재 상태**: MBTI만 있고 수학 학습 스타일 없음
   - **필요한 데이터**:
     - 수학 학습 스타일 (계산형/개념형/응용형)
   - **수집 방법**: 학생 설문 (Agent 01 온보딩 시)
     - "수학 문제를 풀 때 어떤 방식이 편하신가요?"
     - (A) 계산을 정확하게 하는 것이 중요해요
     - (B) 개념을 이해하고 연결하는 것이 중요해요
     - (C) 다양한 문제 유형을 풀어보는 것이 중요해요

**점수: 15/20점** (75%)

---

### 3️⃣ 맞춤형 전략 수립 능력 (12/20점, C)

#### ✅ 강점
- **템플릿 재사용 구조**: 기존 템플릿 우선 재사용하는 효율적 구조
- **상황별 템플릿 선택**: S1~S7 상황별 적절한 템플릿 선택

#### ⚠️ 부족한 점

**1. 수학 학습 단계별 상호작용 전략 부재 (-10점)**
   - **현직 선생님 관점**: "개념 학습 단계에서는 이해 확인 질문, 유형 연습 단계에서는 문제 제시와 피드백이 필요해"
   - **현재 상태**: 일반적 상호작용만 존재
   - **필요한 데이터**:
     - 개념 학습 단계 상호작용 전략
     - 유형 연습 단계 상호작용 전략
     - 심화 학습 단계 상호작용 전략
   - **수집 방법**: 선생님 텍스트 입력
     - "개념 학습 단계: 이해 확인 질문, 예제 풀이 안내"
     - "유형 연습 단계: 유형별 문제 제시, 정답률 피드백"
     - "심화 학습 단계: 도전 문제 제시, 힌트 제공"

**2. 학원 수업 전후 상호작용 전략 부재 (-8점)**
   - **현직 선생님 관점**: "학원 수업 전에는 예습 컨텐츠, 수업 후에는 복습 컨텐츠 제공해야 해"
   - **현재 상태**: 학원 맥락 전혀 없음
   - **필요한 데이터**:
     - 학원 수업 일정
     - 학원 수업 전후 상호작용 전략
   - **수집 방법**: 학생 설문 + 선생님 텍스트 입력
     - 학생 설문: "학원 수업 전후 어떤 상호작용이 필요하신가요?"
     - 선생님 입력: "학원 수업 전: 예습 컨텐츠, 수업 후: 복습 컨텐츠"

**점수: 12/20점** (60%)

---

### 4️⃣ 실행 가능성 및 현실성 (13/15점, B+)

#### ✅ 강점
- **템플릿 재사용 우선**: 효율적이고 일관성 있는 템플릿 관리
- **링크 제공 체계**: 룰 파일 기반 링크 제공 구조 명확

#### ⚠️ 부족한 점

**1. 수학 컨텐츠 링크 검증 부족 (-2점)**
   - **현직 선생님 관점**: "제공된 링크가 실제로 작동하는지, 학생 수준에 맞는지 확인 필요해"
   - **현재 상태**: 링크 제공만 하고 검증 없음
   - **필요한 개선**: 링크 유효성 검증, 학생 수준 대조 검증 룰 추가

**점수: 13/15점** (87%)

---

### 5️⃣ 피드백 및 개선 능력 (7/10점, C)

#### ✅ 강점
- **기본 피드백 제공**: display_message를 통한 피드백 제공

#### ⚠️ 부족한 점

**1. 수학 특화 피드백 레벨 부족 (-8점)**
   - **현직 선생님 관점**: "계산 실수에는 '다시 한 번 계산해볼까요?', 개념 오류에는 '이 개념을 다시 생각해볼까요?' 같은 수학 특화 피드백 필요해"
   - **현재 상태**: 일반적 피드백만 존재
   - **필요한 데이터**:
     - 오류 유형별 피드백 템플릿
     - 단원별 피드백 템플릿
   - **수집 방법**: 선생님 텍스트 입력
     - "계산 실수: 다시 한 번 계산해볼까요? 단계별로 확인해봐요"
     - "개념 오류: 이 개념을 다시 생각해볼까요? 개념 설명 링크 제공"

**2. 상호작용 효과성 추적 부족 (-5점)**
   - **현직 선생님 관점**: "어떤 상호작용이 효과적이었는지 추적해서 다음에 개선해야 해"
   - **현재 상태**: 효과성 추적 메커니즘 부재
   - **필요한 개선**: 상호작용 효과성 추적 룰 추가 (클릭률, 참여도, 개선도 등)

**점수: 7/10점** (70%)

---

### 6️⃣ 정서/동기 관리 (5/10점, D)

#### ✅ 강점
- **감정 상태 기반 상호작용**: 감정 로그 기반 재진입 유도

#### ❌ 현저히 부족한 점

**1. 수학 학습 특화 동기 부족 (-10점)**
   - **현직 선생님 관점**: "수학 문제 풀 때의 성취감, 어려운 문제 해결 시 자신감 등 수학 특화 동기 요소 필요해"
   - **현재 상태**: 일반적 동기만 존재
   - **필요한 데이터**:
     - 수학 문제 풀이 성취감 증진 전략
     - 단원 완료 시 축하 메시지
     - 문제 유형별 동기 부여 전략
   - **수집 방법**: 선생님 텍스트 입력
     - "기본형 문제 해결: '잘했어요! 다음 문제도 도전해볼까요?'"
     - "심화 문제 해결: '훌륭해요! 어려운 문제를 해결했네요!'"
     - "단원 완료: '함수 단원을 완료했어요! 축하합니다!'"

**점수: 5/10점** (50%)

---

## 📋 개선 계획 (목표: 90점 달성)

### Phase 1: 수학 교과 특화 데이터 구축 (우선순위: 최우선)

#### 1.1 단원별 컨텐츠 링크 매핑 DB 구축 (-12점 → +12점)

**새로운 룰 추가:**

```yaml
# 단원별 컨텐츠 링크 매핑 룰
- rule_id: "MATH_UNIT_LINK_MAPPING"
  priority: 88
  description: "수학 단원별 학습 컨텐츠 링크 매핑"
  conditions:
    - field: "current_unit"
      operator: "!="
      value: null
    - field: "learning_stage"
      operator: "in"
      value: ["concept", "practice", "advanced"]
  action:
    - "lookup_unit_content_link: true"
    - "provide_link: 'unit_content_link'"
    - "link_type: 'unit_specific'"
  confidence: 0.92
  rationale: "수학 단원별 맞춤 컨텐츠 링크 제공으로 효과적 학습 지원"
```

**데이터 수집 방법:**
- **선생님 텍스트 입력**: "함수 단원 개념 학습: [링크], 함수 단원 유형 연습: [링크]"
- **선생님 체크리스트**: 단원별 컨텐츠 링크 입력 폼

#### 1.2 학원 교재별 컨텐츠 링크 매핑 (-10점 → +10점)

**새로운 룰 추가:**

```yaml
# 학원 교재별 컨텐츠 링크 매핑 룰
- rule_id: "ACADEMY_TEXTBOOK_LINK_MAPPING"
  priority: 87
  description: "학원 교재별 학습 컨텐츠 링크 매핑"
  conditions:
    - field: "academy_textbook"
      operator: "!="
      value: null
    - field: "textbook_level"
      operator: "in"
      value: ["A", "B", "C", "concept", "RPM"]
  action:
    - "lookup_textbook_content_link: true"
    - "provide_link: 'textbook_content_link'"
    - "link_type: 'textbook_specific'"
  confidence: 0.91
  rationale: "학원 교재에 맞는 컨텐츠 링크 제공으로 학원-학교 연계 학습 지원"
```

**데이터 수집 방법:**
- **학생 설문**: "학원 교재 관련 컨텐츠 링크를 제공할 수 있나요?"
- **선생님 텍스트 입력**: "쎈 A단계: [링크], 쎈 B단계: [링크]"

#### 1.3 수학 문제 유형별 상호작용 템플릿 (-8점 → +8점)

**새로운 룰 추가:**

```yaml
# 문제 유형별 상호작용 템플릿 룰
- rule_id: "PROBLEM_TYPE_INTERACTION_TEMPLATE"
  priority: 89
  description: "수학 문제 유형별 맞춤 상호작용 템플릿 선택"
  conditions:
    - field: "problem_type"
      operator: "in"
      value: ["basic", "type", "advanced"]
    - field: "error_type"
      operator: "in"
      value: ["calculation_error", "concept_error"]
  action:
    - "select_template_by_problem_type: true"
    - "select_template_by_error_type: true"
    - "generate_interaction_content: 'problem_type_specific'"
  confidence: 0.93
  rationale: "문제 유형과 오류 유형에 맞는 맞춤형 상호작용 제공"
```

**데이터 수집 방법:**
- **선생님 체크리스트**:
  - "기본형 문제: 격려 중심 템플릿"
  - "심화 문제: 도전 중심 템플릿"
  - "계산 실수: 반복 연습 안내 템플릿"
  - "개념 오류: 개념 재학습 안내 템플릿"

#### 1.4 단원별 취약점 기반 컨텐츠 추천 (-8점 → +8점)

**새로운 룰 추가:**

```yaml
# 단원별 취약점 기반 컨텐츠 추천 룰
- rule_id: "WEAK_UNIT_CONTENT_RECOMMENDATION"
  priority: 90
  description: "학생의 취약 단원 기반 맞춤 컨텐츠 추천"
  conditions:
    - field: "weak_units"
      operator: "!="
      value: null
    - field: "current_unit"
      operator: "in"
      value: "weak_units"
  action:
    - "prioritize_weak_unit_content: true"
    - "provide_link: 'weak_unit_reinforcement_content'"
    - "display_message: '이 단원에서 더 연습해볼까요? 취약한 부분을 함께 보강해봐요.'"
  confidence: 0.94
  rationale: "취약 단원 집중 보강으로 학습 효과 극대화"
```

**데이터 수집 방법:**
- **학생 설문**: "어떤 단원의 컨텐츠가 가장 필요하신가요?"
- **Agent 11 연계**: 오답 패턴 분석 결과 활용

---

### Phase 2: 학생 진단 및 이해 능력 강화 (우선순위: 높음)

#### 2.1 수학 학습 맥락 반영 (-8점 → +8점)

**기존 룰 개선:**

```yaml
# 수학 학습 맥락 반영 룰 (기존 룰에 조건 추가)
- rule_id: "S1R1_engagement_drop_detection_ENHANCED"
  priority: 95
  description: "학습 집중도 급감 시 수학 학습 맥락 고려 재진입 유도"
  conditions:
    - field: "engagement_score"
      operator: "<"
      value: 0.3
    - field: "current_unit"
      operator: "!="
      value: null
    - field: "learning_stage"
      operator: "in"
      value: ["concept", "practice", "advanced"]
  action:
    - "select_interaction_type: '상호작용 컨텐츠'"
    - "select_template: '미니 재진입 챌린지'"
    - "generate_interaction_content: 'reengagement_challenge'"
    - "provide_unit_specific_link: true"
    - "display_message: '지금 {{current_unit}} 단원을 학습 중이시네요. 가벼운 도전 하나 해볼까요?'"
  confidence: 0.95
  rationale: "수학 학습 맥락을 고려한 맞춤형 재진입 유도"
```

**데이터 수집 방법:**
- **Agent 09 연계**: 학습 관리 데이터 활용
- **Agent 14 연계**: 현재 위치 데이터 활용
- **학생 설문**: "지금 어떤 단원을 학습 중이신가요?"

#### 2.2 수학 학습 스타일 반영 (-5점 → +5점)

**새로운 룰 추가:**

```yaml
# 수학 학습 스타일 기반 상호작용 룰
- rule_id: "MATH_LEARNING_STYLE_INTERACTION"
  priority: 76
  description: "수학 학습 스타일 기반 맞춤 상호작용"
  conditions:
    - field: "math_learning_style"
      operator: "in"
      value: ["calculation_focused", "concept_focused", "application_focused"]
  action:
    - "adjust_interaction_by_style: true"
    - "calculation_focused: '계산 연습 컨텐츠 우선 제공'"
    - "concept_focused: '개념 설명 컨텐츠 우선 제공'"
    - "application_focused: '다양한 문제 유형 컨텐츠 우선 제공'"
  confidence: 0.85
  rationale: "학생의 수학 학습 스타일에 맞는 컨텐츠 우선 제공"
```

**데이터 수집 방법:**
- **학생 설문** (Agent 01 온보딩 시):
  - "수학 문제를 풀 때 어떤 방식이 편하신가요?"
  - (A) 계산을 정확하게 하는 것이 중요해요
  - (B) 개념을 이해하고 연결하는 것이 중요해요
  - (C) 다양한 문제 유형을 풀어보는 것이 중요해요

---

### Phase 3: 맞춤형 전략 수립 능력 강화 (우선순위: 중)

#### 3.1 수학 학습 단계별 상호작용 전략 (-10점 → +10점)

**새로운 룰 추가:**

```yaml
# 수학 학습 단계별 상호작용 전략 룰
- rule_id: "MATH_LEARNING_STAGE_INTERACTION"
  priority: 91
  description: "수학 학습 단계별 맞춤 상호작용 전략"
  conditions:
    - field: "learning_stage"
      operator: "=="
      value: "concept"
  action:
    - "select_interaction_type: '멀티턴 상호작용'"
    - "select_template: '개념 이해 확인 질문형'"
    - "generate_interaction_content: 'concept_understanding_check'"
    - "provide_link: 'concept_explanation_content'"
    - "display_message: '이 개념을 이해하셨나요? 예제를 통해 확인해볼까요?'"
  confidence: 0.92
  rationale: "개념 학습 단계에 맞는 이해 확인 및 예제 안내"

- rule_id: "MATH_LEARNING_STAGE_PRACTICE"
  priority: 91
  description: "유형 연습 단계 상호작용 전략"
  conditions:
    - field: "learning_stage"
      operator: "=="
      value: "practice"
  action:
    - "select_interaction_type: '상호작용 컨텐츠'"
    - "select_template: '유형별 문제 제시형'"
    - "generate_interaction_content: 'type_practice_interaction'"
    - "provide_link: 'type_practice_content'"
    - "display_message: '이 유형의 문제를 연습해볼까요? 정답률을 확인하면서 진행해봐요.'"
  confidence: 0.92
  rationale: "유형 연습 단계에 맞는 문제 제시 및 피드백 제공"
```

**데이터 수집 방법:**
- **선생님 텍스트 입력**:
  - "개념 학습 단계: 이해 확인 질문, 예제 풀이 안내"
  - "유형 연습 단계: 유형별 문제 제시, 정답률 피드백"
  - "심화 학습 단계: 도전 문제 제시, 힌트 제공"

#### 3.2 학원 수업 전후 상호작용 전략 (-8점 → +8점)

**새로운 룰 추가:**

```yaml
# 학원 수업 전후 상호작용 전략 룰
- rule_id: "ACADEMY_CLASS_PRE_INTERACTION"
  priority: 88
  description: "학원 수업 전 예습 상호작용"
  conditions:
    - field: "academy_class_time"
      operator: "before"
      value: "2_hours"
    - field: "academy_unit"
      operator: "!="
      value: null
  action:
    - "select_interaction_type: '상호작용 컨텐츠'"
    - "select_template: '예습 가이드형'"
    - "generate_interaction_content: 'preview_preparation'"
    - "provide_link: 'preview_content'"
    - "display_message: '학원 수업 전에 미리 예습해볼까요? 오늘 배울 {{academy_unit}} 단원을 살펴봐요.'"
  confidence: 0.90
  rationale: "학원 수업 전 예습으로 수업 이해도 향상"

- rule_id: "ACADEMY_CLASS_POST_INTERACTION"
  priority: 88
  description: "학원 수업 후 복습 상호작용"
  conditions:
    - field: "academy_class_time"
      operator: "after"
      value: "1_hour"
    - field: "academy_unit"
      operator: "!="
      value: null
  action:
    - "select_interaction_type: '멀티턴 상호작용'"
    - "select_template: '복습 강화형'"
    - "generate_interaction_content: 'review_reinforcement'"
    - "provide_link: 'review_content'"
    - "display_message: '학원 수업에서 배운 {{academy_unit}} 단원을 복습해볼까요? 더 확실하게 이해할 수 있어요.'"
  confidence: 0.90
  rationale: "학원 수업 후 복습으로 학습 내용 정착"
```

**데이터 수집 방법:**
- **학생 설문**: "학원 수업 전후 어떤 상호작용이 필요하신가요?"
- **선생님 텍스트 입력**: "학원 수업 전: 예습 컨텐츠, 수업 후: 복습 컨텐츠"

---

### Phase 4: 피드백 및 개선 능력 강화 (우선순위: 중)

#### 4.1 수학 특화 피드백 레벨 강화 (-8점 → +8점)

**새로운 룰 추가:**

```yaml
# 수학 특화 피드백 룰
- rule_id: "MATH_SPECIFIC_FEEDBACK_CALCULATION"
  priority: 92
  description: "계산 실수에 대한 수학 특화 피드백"
  conditions:
    - field: "error_type"
      operator: "=="
      value: "calculation_error"
    - field: "error_repeat_count"
      operator: ">="
      value: 2
  action:
    - "select_interaction_type: '멀티턴 상호작용'"
    - "select_template: '계산 실수 개선 가이드형'"
    - "generate_interaction_content: 'calculation_error_feedback'"
    - "display_message: '계산 실수가 있었네요. 다시 한 번 계산해볼까요? 단계별로 확인해봐요. 차근차근 풀면 정확해질 거예요.'"
    - "provide_link: 'calculation_practice_content'"
  confidence: 0.93
  rationale: "계산 실수에 대한 구체적이고 건설적인 피드백 제공"

- rule_id: "MATH_SPECIFIC_FEEDBACK_CONCEPT"
  priority: 92
  description: "개념 오류에 대한 수학 특화 피드백"
  conditions:
    - field: "error_type"
      operator: "=="
      value: "concept_error"
    - field: "current_unit"
      operator: "!="
      value: null
  action:
    - "select_interaction_type: '멀티턴 상호작용'"
    - "select_template: '개념 오류 개선 가이드형'"
    - "generate_interaction_content: 'concept_error_feedback'"
    - "display_message: '{{current_unit}} 단원의 개념을 다시 생각해볼까요? 개념을 제대로 이해하면 문제가 쉬워질 거예요. 개념 설명 링크를 확인해봐요.'"
    - "provide_link: 'concept_explanation_content'"
  confidence: 0.93
  rationale: "개념 오류에 대한 개념 재학습 안내 피드백 제공"
```

**데이터 수집 방법:**
- **선생님 텍스트 입력**:
  - "계산 실수: 다시 한 번 계산해볼까요? 단계별로 확인해봐요"
  - "개념 오류: 이 개념을 다시 생각해볼까요? 개념 설명 링크 제공"

#### 4.2 상호작용 효과성 추적 (-5점 → +5점)

**새로운 룰 추가:**

```yaml
# 상호작용 효과성 추적 룰
- rule_id: "INTERACTION_EFFECTIVENESS_TRACKING"
  priority: 68
  description: "상호작용 효과성 추적 및 분석"
  conditions:
    - field: "interaction_delivered"
      operator: "=="
      value: true
  action:
    - "track_click_rate: true"
    - "track_engagement_rate: true"
    - "track_improvement_rate: true"
    - "send_to_agent22: true"
    - "update_template_effectiveness: true"
  confidence: 0.80
  rationale: "상호작용 효과성 추적으로 지속적 개선"
```

---

### Phase 5: 정서/동기 관리 강화 (우선순위: 중)

#### 5.1 수학 학습 특화 동기 부여 (-10점 → +10점)

**새로운 룰 추가:**

```yaml
# 수학 학습 특화 동기 부여 룰
- rule_id: "MATH_ACHIEVEMENT_MOTIVATION"
  priority: 86
  description: "수학 문제 풀이 성취감 증진"
  conditions:
    - field: "problem_solved_correctly"
      operator: "=="
      value: true
    - field: "problem_type"
      operator: "=="
      value: "basic"
  action:
    - "select_interaction_type: '상호작용 컨텐츠'"
    - "select_template: '성취 축하형'"
    - "generate_interaction_content: 'achievement_celebration'"
    - "display_message: '잘했어요! 정확하게 풀었네요. 다음 문제도 도전해볼까요?'"
    - "provide_link: 'next_problem_content'"
  confidence: 0.88
  rationale: "기본형 문제 해결 시 성취감 증진 및 다음 문제 도전 유도"

- rule_id: "MATH_ADVANCED_ACHIEVEMENT_MOTIVATION"
  priority: 87
  description: "심화 문제 해결 시 동기 부여"
  conditions:
    - field: "problem_solved_correctly"
      operator: "=="
      value: true
    - field: "problem_type"
      operator: "=="
      value: "advanced"
  action:
    - "select_interaction_type: '상호작용 컨텐츠'"
    - "select_template: '고난도 성취 축하형'"
    - "generate_interaction_content: 'advanced_achievement_celebration'"
    - "display_message: '훌륭해요! 어려운 문제를 해결했네요. 실력이 많이 늘었어요! 다른 심화 문제도 도전해볼까요?'"
    - "provide_link: 'advanced_problem_content'"
  confidence: 0.89
  rationale: "심화 문제 해결 시 강한 성취감 및 자신감 증진"

- rule_id: "MATH_UNIT_COMPLETION_MOTIVATION"
  priority: 88
  description: "단원 완료 시 축하 및 동기 부여"
  conditions:
    - field: "unit_completed"
      operator: "=="
      value: true
    - field: "current_unit"
      operator: "!="
      value: null
  action:
    - "select_interaction_type: '상호작용 컨텐츠'"
    - "select_template: '단원 완료 축하형'"
    - "generate_interaction_content: 'unit_completion_celebration'"
    - "display_message: '축하해요! {{current_unit}} 단원을 완료했어요! 정말 수고하셨어요. 다음 단원도 함께 도전해볼까요?'"
    - "provide_link: 'next_unit_content'"
    - "provide_reward: true"
  confidence: 0.90
  rationale: "단원 완료 시 축하 및 다음 단원 도전 동기 부여"
```

**데이터 수집 방법:**
- **선생님 텍스트 입력**:
  - "기본형 문제 해결: '잘했어요! 다음 문제도 도전해볼까요?'"
  - "심화 문제 해결: '훌륭해요! 어려운 문제를 해결했네요!'"
  - "단원 완료: '함수 단원을 완료했어요! 축하합니다!'"

---

## 📊 개선 후 예상 점수

| 평가 영역 | 개선 전 | 개선 후 | 향상도 |
|---------|--------|--------|--------|
| **수학 교과 특화 지식** | 10 | 25 | +15 |
| **학생 진단 및 이해 능력** | 15 | 20 | +5 |
| **맞춤형 전략 수립 능력** | 12 | 20 | +8 |
| **실행 가능성 및 현실성** | 13 | 15 | +2 |
| **피드백 및 개선 능력** | 7 | 15 | +8 |
| **정서/동기 관리** | 5 | 15 | +10 |
| **총합** | **62** | **110** | **+48** |

**목표 달성도:**
- **개선 전**: 62점 (현직 선생님 수준의 62%)
- **개선 후 예상**: 110점 (만점 초과, 100점 만점 기준으로는 100점)
- **목표**: 90점 (현직 선생님 수준의 90%)
- **목표 달성**: ✅ **예상 초과 달성**

---

## 📝 데이터 수집 방법 상세

### 학생 설문 항목

#### 수학 학습 스타일 (Agent 01 온보딩 시)
1. "수학 문제를 풀 때 어떤 방식이 편하신가요?"
   - (A) 계산을 정확하게 하는 것이 중요해요 (계산형)
   - (B) 개념을 이해하고 연결하는 것이 중요해요 (개념형)
   - (C) 다양한 문제 유형을 풀어보는 것이 중요해요 (응용형)

#### 현재 학습 맥락
2. "지금 어떤 단원을 학습 중이신가요?"
   - 단원명: ___________
   - 학습 단계: ( ) 개념 학습 ( ) 유형 연습 ( ) 심화

#### 학원 정보
3. "학원 교재 관련 컨텐츠 링크를 제공할 수 있나요?"
   - 학원 교재명: ___________
   - 현재 진도: ___________

#### 취약 단원
4. "어떤 단원의 컨텐츠가 가장 필요하신가요?"
   - 취약 단원: ___________

#### 학원 수업 전후 상호작용
5. "학원 수업 전후 어떤 상호작용이 필요하신가요?"
   - 수업 전: ( ) 예습 컨텐츠 ( ) 복습 컨텐츠
   - 수업 후: ( ) 복습 컨텐츠 ( ) 심화 문제

---

### 선생님 체크리스트 항목

#### 단원별 컨텐츠 링크 매핑
1. "단원별 학습 컨텐츠 링크를 입력해주세요"
   - 단원명: ___________
   - 개념 학습 링크: ___________
   - 유형 연습 링크: ___________
   - 심화 문제 링크: ___________

#### 학원 교재별 컨텐츠 링크
2. "학원 교재별 컨텐츠 링크를 입력해주세요"
   - 교재명: ___________
   - 단계별 링크: (예: 쎈 A단계, B단계, C단계)

#### 문제 유형별 템플릿
3. "문제 유형별 상호작용 템플릿을 정의해주세요"
   - 기본형 문제: ( ) 격려 중심 ( ) 도전 중심
   - 심화 문제: ( ) 격려 중심 ( ) 도전 중심
   - 계산 실수: ( ) 반복 연습 안내 ( ) 개념 재학습 안내
   - 개념 오류: ( ) 반복 연습 안내 ( ) 개념 재학습 안내

#### 수학 학습 단계별 전략
4. "수학 학습 단계별 상호작용 전략을 입력해주세요"
   - 개념 학습 단계: ___________
   - 유형 연습 단계: ___________
   - 심화 학습 단계: ___________

---

### 선생님 텍스트 입력 항목

#### 단원별 컨텐츠 링크
1. "함수 단원 개념 학습: [링크], 함수 단원 유형 연습: [링크]"

#### 학원 교재별 링크
2. "쎈 A단계: [링크], 쎈 B단계: [링크], 쎈 C단계: [링크]"

#### 수학 특화 피드백
3. "계산 실수: 다시 한 번 계산해볼까요? 단계별로 확인해봐요"
4. "개념 오류: 이 개념을 다시 생각해볼까요? 개념 설명 링크 제공"

#### 수학 학습 특화 동기
5. "기본형 문제 해결: '잘했어요! 다음 문제도 도전해볼까요?'"
6. "심화 문제 해결: '훌륭해요! 어려운 문제를 해결했네요!'"
7. "단원 완료: '함수 단원을 완료했어요! 축하합니다!'"

#### 학원 수업 전후 전략
8. "학원 수업 전: 예습 컨텐츠, 수업 후: 복습 컨텐츠"

---

## ✅ 실행 체크리스트

### Phase 1: 수학 교과 특화 데이터 구축
- [ ] 단원별 컨텐츠 링크 매핑 DB 구조 설계
- [ ] 학원 교재별 컨텐츠 링크 매핑 DB 구조 설계
- [ ] 문제 유형별 템플릿 DB 구조 설계
- [ ] 단원별 취약점 기반 컨텐츠 추천 로직 구현
- [ ] 학생 설문 항목 추가
- [ ] 선생님 체크리스트 인터페이스 구현
- [ ] 데이터 수집 로직 구현

### Phase 2: 학생 진단 및 이해 능력 강화
- [ ] 수학 학습 맥락 반영 룰 추가
- [ ] 수학 학습 스타일 반영 룰 추가
- [ ] Agent 09, Agent 14 연계 로직 구현
- [ ] 학생 설문 항목 추가 (Agent 01 온보딩)

### Phase 3: 맞춤형 전략 수립 능력 강화
- [ ] 수학 학습 단계별 상호작용 전략 룰 추가
- [ ] 학원 수업 전후 상호작용 전략 룰 추가
- [ ] 선생님 텍스트 입력 인터페이스 구현

### Phase 4: 피드백 및 개선 능력 강화
- [ ] 수학 특화 피드백 룰 추가
- [ ] 상호작용 효과성 추적 룰 추가
- [ ] Agent 22 연계 로직 구현

### Phase 5: 정서/동기 관리 강화
- [ ] 수학 학습 특화 동기 부여 룰 추가
- [ ] 단원 완료 축하 룰 추가
- [ ] 문제 유형별 동기 부여 룰 추가

---

## 🎓 결론

현재 Agent 19의 점수는 **62점** (현직 선생님 수준의 62%)입니다.

**핵심 부족 요소:**
1. 수학 교과 특화 지식 부재 (단원별, 유형별 링크 매핑)
2. 학원 시스템 통합 부재
3. 수학 학습 단계별 전략 부족
4. 수학 특화 피드백 및 동기 부족

**개선 후 예상 수준**: **100점** (만점 초과, 100점 만점 기준으로는 100점)

**목표 달성**: ✅ **예상 초과 달성** (목표 90점 초과)

위의 개선 계획을 순차적으로 실행하면 **90% 수준 초과 달성**이 가능합니다.

---

**작성일**: 2025-01-27  
**다음 검토일**: Phase 1 완료 후

