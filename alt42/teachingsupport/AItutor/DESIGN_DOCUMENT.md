# AI 튜터 시스템 설계 문서

**생성일**: 2025-01-27  
**시스템명**: 단원 전용 AI 튜터 (Unit-Specific AI Tutor)  
**버전**: 1.0  
**기반**: Agent01 Onboarding 설계 원리

---

## 1. 개요

### 1.1 시스템 목적

단원 전용 AI 튜터는 학생이 특정 수학 단원을 학습할 때, 해당 단원의 온톨로지와 컨텐츠를 기반으로 맞춤형 학습 지원을 제공하는 시스템입니다.

**핵심 가치**:
- 단원별 특화된 학습 경험 제공
- 온톨로지 기반 지식 구조 이해
- 학생의 학습 맥락에 맞춘 개인화된 튜터링

### 1.2 설계 원리 (Agent01 Onboarding 기반)

Agent01 Onboarding의 설계 원리를 단원 전용 튜터에 적용:

1. **의도와 목적 (Intent & Purpose)**: 단원 학습 목표 명확화
2. **포괄적 질문과 요청 (Comprehensive Questions)**: 단원 학습 맥락 종합 분석
3. **최적화된 룰세트 (Optimized Rules)**: 단원별 학습 전략 자동화
4. **온톨로지 (Ontology)**: 단원 지식 구조 및 선후관계 표현
5. **UX (User Experience)**: 온톨로지 방향성 중심의 사용자 경험 설계
6. **UI (User Interface)**: UX 제약과 핵심 경험에 집중한 인터페이스

---

## 2. 의도와 목적 (Intent & Purpose)

### 2.1 시스템 의도

**핵심 의도**: 학생이 특정 수학 단원을 효과적으로 학습할 수 있도록, 단원의 지식 구조와 학생의 학습 상태를 종합하여 맞춤형 튜터링을 제공한다.

### 2.2 목적 계층 구조

#### 2.2.1 최상위 목적 (Will Layer)

Agent01의 OIW Model을 적용한 시스템 가치:

```yaml
will:
  core:
    - value: "학생이 단원 학습에서 좌절하지 않도록 한다"
      priority: 10
      constraints:
        difficulty_progression: { allowed: ["점진적 상승"], forbidden: ["급격한 상승"] }
        concept_sequence: { required: ["선행 개념 확인", "단계별 이해"] }
    
    - value: "단원의 핵심 개념을 확실히 이해하도록 한다"
      priority: 9
      constraints:
        concept_mastery: { threshold: 0.8, measurement: "핵심 개념 이해도" }
        prerequisite_check: { required: true }
    
    - value: "단원 간 연결성을 이해하도록 한다"
      priority: 8
      constraints:
        unit_relations: { required: true }
        prerequisite_units: { check: true }
```

#### 2.2.2 상황별 목적 (Intent Layer)

**단원 학습 시나리오별 목적**:

1. **단원 시작 시**:
   - 선행 단원 완료 여부 확인
   - 단원 학습 목표 제시
   - 학습 계획 수립

2. **개념 학습 중**:
   - 개념 이해도 진단
   - 혼란 지점 식별 및 해소
   - 예제와 연습 문제 제공

3. **단원 완료 시**:
   - 마스터리 평가
   - 후속 단원 연결성 안내
   - 취약점 보완 계획

---

## 3. 포괄적 질문과 요청 (Comprehensive Questions)

### 3.1 질문 프레임워크

Agent01의 포괄형 질문 구조를 단원 학습에 적용:

#### 3.1.1 질문 1: 단원 학습 시작 전략

**질문**: "이 학생이 [단원명] 단원을 학습하기 시작할 때, 어떤 순서와 방법으로 접근해야 할까?"

**요구사항**:
- 선행 단원 완료 여부 확인
- 단원 난이도와 학생 수준 매칭
- 학습 스타일 기반 접근 방법 제시
- 단원 학습 계획 수립

**데이터 소스**:
- `current_unit`: 현재 학습 단원
- `prerequisite_units`: 선행 단원 목록
- `unit_mastery_history`: 단원별 마스터리 이력
- `student_math_level`: 학생 수학 수준
- `learning_style`: 학습 스타일

#### 3.1.2 질문 2: 단원 학습 최적화

**질문**: "이 학생의 [단원명] 단원 학습을 어떤 방향으로 최적화해야 할까?"

**요구사항**:
- 개념 이해도 기반 학습 순서 조정
- 문제 유형 비중 최적화
- 학습 속도 조절
- 취약 개념 집중 학습

**데이터 소스**:
- `concept_mastery`: 개념별 마스터리
- `problem_solving_history`: 문제 풀이 이력
- `learning_pace`: 학습 속도
- `weak_concepts`: 취약 개념 목록

#### 3.1.3 질문 3: 단원 학습 성장 전략

**질문**: "이 학생이 [단원명] 단원을 완전히 마스터하기 위해 어떤 부분을 특히 신경 써야 할까?"

**요구사항**:
- 단원 마스터리 리스크 예측
- 후속 단원 연결성 고려
- 장기 학습 계획 수립
- 취약점 보완 전략

**데이터 소스**:
- `unit_mastery_level`: 단원 마스터리 수준
- `related_units`: 관련 단원 목록
- `long_term_goals`: 장기 목표
- `risk_factors`: 리스크 요소

---

## 4. 최적화된 룰세트 (Optimized Rules)

### 4.1 룰 구조

Agent01의 `rules.yaml` 구조를 단원 튜터에 적용:

```yaml
version: "1.0"
scenario: "unit_tutoring"
description: "단원 전용 AI 튜터 룰셋"

rules:
  # U0: 단원 학습 시작 전 정보 수집
  - rule_id: "U0_R1_prerequisite_check"
    priority: 99
    description: "선행 단원 완료 여부 확인"
    conditions:
      - field: "current_unit"
        operator: "!="
        value: null
      - field: "prerequisite_units_completed"
        operator: "=="
        value: null
    action:
      - "load_db: 'math_unit_relations.yaml'"
      - "check_prerequisites: '{current_unit}'"
      - "analyze: 'prerequisite_mastery_status'"
      - "display_message: '선행 단원 완료 여부를 확인하여 학습 준비 상태를 평가합니다.'"
    confidence: 0.95
    rationale: "선행 단원 미완료 시 학습 어려움 예방"
  
  - rule_id: "U0_R2_unit_difficulty_assessment"
    priority: 98
    description: "단원 난이도와 학생 수준 매칭"
    conditions:
      - field: "current_unit"
        operator: "!="
        value: null
      - field: "student_math_level"
        operator: "!="
        value: null
    action:
      - "load_db: 'math_unit_relations.yaml'"
      - "get_unit_difficulty: '{current_unit}'"
      - "match_difficulty_level: '{student_math_level}'"
      - "recommend: 'adjusted_learning_approach'"
      - "display_message: '단원 난이도({unit_difficulty})와 학생 수준({student_math_level})을 매칭하여 적합한 학습 접근을 제안합니다.'"
    confidence: 0.93
    rationale: "적절한 난이도 매칭이 학습 효과 향상"
  
  # U1: 단원 학습 시작 전략
  - rule_id: "U1_R1_unit_start_strategy"
    priority: 100
    description: "단원 학습 시작 종합 전략"
    conditions:
      - field: "user_message"
        operator: "contains"
        value: "단원 시작"
      - OR:
        - field: "user_message"
          operator: "contains"
          value: "어떻게 시작"
        - field: "user_message"
          operator: "contains"
          value: "학습 계획"
    action:
      - "create_instance: 'mk:UnitLearningContext'"
      - "set_property: ('mk:hasCurrentUnit', '{current_unit}')"
      - "set_property: ('mk:hasPrerequisiteStatus', '{prerequisite_status}')"
      - "set_property: ('mk:hasUnitDifficulty', '{unit_difficulty}')"
      - "generate_strategy: 'mk:UnitStartStrategy'"
      - "recommend_path: '단원 학습 시작 전략: 선행 확인 + 난이도 매칭 + 학습 계획'"
      - "display_message: '단원 학습을 시작하기 위해 선행 단원 확인, 난이도 매칭, 학습 계획을 종합하여 전략을 수립합니다.'"
    confidence: 0.96
    rationale: "단원 학습 시작 시 종합적 전략 필요"
  
  # U2: 개념 학습 중 전략
  - rule_id: "U2_R1_concept_confusion_detection"
    priority: 95
    description: "개념 혼란 지점 식별"
    conditions:
      - field: "current_concept"
        operator: "!="
        value: null
      - field: "concept_understanding_score"
        operator: "<"
        value: 0.7
    action:
      - "analyze: 'concept_confusion_points'"
      - "identify: 'prerequisite_concept_gaps'"
      - "recommend: 'concept_clarification_strategy'"
      - "display_message: '개념 이해도가 낮아 혼란 지점을 식별하고 보완 전략을 제안합니다.'"
    confidence: 0.92
    rationale: "개념 혼란 조기 발견 및 해소"
  
  # U3: 단원 완료 평가
  - rule_id: "U3_R1_unit_mastery_assessment"
    priority: 94
    description: "단원 마스터리 종합 평가"
    conditions:
      - field: "unit_completion_status"
        operator: "=="
        value: "completed"
      - field: "unit_mastery_level"
        operator: "!="
        value: null
    action:
      - "analyze: 'comprehensive_unit_mastery'"
      - "evaluate: 'weak_concepts'"
      - "recommend: 'follow_up_unit_preparation'"
      - "display_message: '단원 완료를 평가하고 취약점 보완 및 후속 단원 준비를 제안합니다.'"
    confidence: 0.91
    rationale: "단원 완료 후 마스터리 평가 및 다음 단계 안내"
```

### 4.2 룰 카테고리

1. **U0**: 단원 학습 시작 전 정보 수집
2. **U1**: 단원 학습 시작 전략
3. **U2**: 개념 학습 중 전략
4. **U3**: 단원 완료 평가
5. **U4**: 단원 간 연결성 전략

---

## 5. 온톨로지 (Ontology)

### 5.1 단원 온톨로지 구조

Agent01의 OIW Model을 단원 튜터에 적용:

#### 5.1.1 Context Layer: 단원 학습 맥락

```json
{
  "@id": "mk:UnitLearningContext",
  "@type": "owl:Class",
  "rdfs:label": "단원 학습 맥락",
  "rdfs:subClassOf": "mk:Context",
  "mk:properties": [
    "mk:hasCurrentUnit",
    "mk:hasPrerequisiteStatus",
    "mk:hasUnitDifficulty",
    "mk:hasStudentMathLevel",
    "mk:hasLearningStyle",
    "mk:hasUnitMasteryHistory"
  ]
}
```

#### 5.1.2 Decision Layer: 단원 학습 전략 결정

```json
{
  "@id": "mk:UnitLearningStrategy",
  "@type": "owl:Class",
  "rdfs:label": "단원 학습 전략",
  "rdfs:subClassOf": "mk:Strategy",
  "mk:properties": [
    "mk:recommendsLearningSequence",
    "mk:recommendsConceptOrder",
    "mk:recommendsProblemTypes",
    "mk:recommendsLearningPace",
    "mk:recommendsFocusAreas"
  ]
}
```

#### 5.1.3 Execution Layer: 단원 학습 실행 계획

```json
{
  "@id": "mk:UnitLearningExecutionPlan",
  "@type": "owl:Class",
  "rdfs:label": "단원 학습 실행 계획",
  "rdfs:subClassOf": "mk:ExecutionPlan",
  "mk:properties": [
    "mk:hasActionSteps",
    "mk:hasMeasurementCriteria",
    "mk:hasFeedbackPoints",
    "mk:hasAdjustmentRules"
  ]
}
```

### 5.2 Math Topics 온톨로지 연동

`math topics` 폴더의 온톨로지 파일과 연동:

#### 5.2.1 단원 온톨로지 파일 매핑

```yaml
unit_ontology_mapping:
  equations:
    ontology_file: "6 방정식_ontology.owl"
    content_file: "6 방정식.md"
    unit_code: "EQ"
    concepts:
      - "일차방정식의 풀이"
      - "일차방정식의 활용"
      - "연립일차방정식의 풀이"
      - "연립일차방정식의 활용"
  
  functions:
    ontology_file: "8 functions_ontology.owl"
    content_file: "8 함수.md"
    unit_code: "FN"
    concepts:
      - "일차함수"
      - "이차함수"
      - "유리함수"
      - "무리함수"
```

#### 5.2.2 단원 관계 온톨로지

`math_unit_relations.yaml` 기반:

```yaml
unit_relations:
  equations:
    prerequisites: ["expression_calculation"]
    related_units: ["inequalities", "functions", "plane_coordinates"]
    difficulty: 3
    level: "중등1-3"
  
  functions:
    prerequisites: ["equations", "inequalities", "plane_coordinates"]
    related_units: ["differentiation", "integration", "exponents_and_logs"]
    difficulty: 4
    level: "중등2-3"
```

### 5.3 컨텐츠 연결 링크

각 단원의 개념 노트와 진단 목록 연결:

```yaml
content_links:
  equations:
    concepts:
      - name: "등식"
        note_url: "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53178&page=1&quizid=86105"
        diagnostic_points:
          - "등식과 방정식, 항등식의 차이점을 이해하는데 어려움"
          - "등식의 성질에 대한 이해가 부족"
```

---

## 6. UX (User Experience)

### 6.1 UX 설계 원칙

온톨로지 방향성을 중심으로 한 UX 설계:

#### 6.1.1 단원 학습 흐름 (Unit Learning Flow)

```
[단원 선택]
  ↓
[선행 단원 확인] ← 온톨로지: prerequisite check
  ↓
[단원 학습 계획 수립] ← 온톨로지: UnitLearningStrategy
  ↓
[개념 학습] ← 온톨로지: concept sequence
  ├─ [개념 이해도 진단]
  ├─ [혼란 지점 식별]
  └─ [보완 전략 제시]
  ↓
[문제 풀이] ← 온톨로지: problem type recommendation
  ├─ [기초 문제]
  ├─ [기본 문제]
  └─ [심화 문제]
  ↓
[단원 마스터리 평가] ← 온톨로지: UnitMasteryAssessment
  ↓
[후속 단원 안내] ← 온톨로지: related units
```

#### 6.1.2 핵심 UX 경험

1. **단원 시작 경험**:
   - 선행 단원 자동 확인
   - 단원 학습 목표 명확 제시
   - 개인화된 학습 계획 제안

2. **개념 학습 경험**:
   - 단계별 개념 설명
   - 이해도 실시간 진단
   - 혼란 지점 즉시 해소

3. **문제 풀이 경험**:
   - 난이도별 문제 추천
   - 풀이 과정 피드백
   - 취약 유형 집중 연습

4. **단원 완료 경험**:
   - 마스터리 시각화
   - 취약점 보완 제안
   - 후속 단원 연결 안내

### 6.2 UX 제약사항

Agent01의 Will Layer를 UX 제약으로 적용:

```yaml
ux_constraints:
  - constraint: "학생이 좌절하지 않도록 한다"
    ux_rule: "난이도 점진적 상승, 급격한 난이도 상승 금지"
    ui_manifestation: "난이도 표시, 진행률 표시"
  
  - constraint: "핵심 개념을 확실히 이해하도록 한다"
    ux_rule: "선행 개념 확인 필수, 개념 이해도 80% 이상 목표"
    ui_manifestation: "개념 이해도 게이지, 선행 개념 체크리스트"
  
  - constraint: "단원 간 연결성을 이해하도록 한다"
    ux_rule: "관련 단원 시각화, 선후관계 그래프 제공"
    ui_manifestation: "단원 관계 그래프, 연결성 설명"
```

---

## 7. UI (User Interface)

### 7.1 UI 설계 원칙

UX 제약과 핵심 경험에 집중한 UI:

#### 7.1.1 핵심 UI 컴포넌트

1. **단원 선택 화면**:
   - 단원 목록 (난이도 표시)
   - 선행 단원 완료 상태 표시
   - 단원 학습 목표 미리보기

2. **단원 학습 대시보드**:
   - 학습 진행률
   - 개념 이해도 게이지
   - 취약 개념 하이라이트
   - 단원 관계 그래프

3. **개념 학습 화면**:
   - 개념 설명 (단계별)
   - 이해도 체크 포인트
   - 관련 예제 및 연습 문제
   - 혼란 지점 해소 팁

4. **문제 풀이 화면**:
   - 난이도별 문제 필터
   - 풀이 과정 입력
   - 실시간 피드백
   - 해설 및 관련 개념 링크

5. **단원 완료 화면**:
   - 마스터리 리포트
   - 취약점 분석
   - 후속 단원 추천
   - 보완 학습 계획

### 7.2 UI 제약 구현

```yaml
ui_constraints:
  - ux_constraint: "좌절 방지"
    ui_elements:
      - "진행률 표시 (항상 긍정적 프레이밍)"
      - "난이도 표시 (점진적 상승 시각화)"
      - "성취 배지 (작은 성공 강조)"
  
  - ux_constraint: "핵심 개념 이해"
    ui_elements:
      - "개념 이해도 게이지 (80% 목표 표시)"
      - "선행 개념 체크리스트 (필수 확인)"
      - "개념 관계 그래프 (연결성 시각화)"
  
  - ux_constraint: "단원 간 연결성"
    ui_elements:
      - "단원 관계 그래프 (선후관계 시각화)"
      - "관련 단원 링크 (쉽게 이동 가능)"
      - "학습 경로 추천 (최적 순서 제시)"
```

### 7.3 UI 최소 기능 원칙

프로젝트 규칙에 따라 최소 기능적 코드로 개발:

- PHP, JS, CSS, HTML만 사용
- 최소한의 UI 컴포넌트
- 핵심 기능에 집중
- 불필요한 장식 제거

---

## 8. 시스템 아키텍처

### 8.1 파일 구조

```
AItutor/
├── index.php                    # 프론트 컨트롤러
├── unit_tutor.php               # 단원 튜터 메인 로직
├── includes/
│   ├── unit_context.php         # 단원 맥락 데이터
│   ├── unit_rules.php           # 단원 룰 엔진
│   └── unit_ontology.php        # 단원 온톨로지 처리
├── services/
│   ├── unit_learning_service.php    # 단원 학습 서비스
│   ├── concept_mastery_service.php  # 개념 마스터리 서비스
│   └── unit_relation_service.php    # 단원 관계 서비스
├── ui/
│   ├── unit_dashboard.php       # 단원 대시보드
│   ├── concept_learning.php     # 개념 학습 화면
│   ├── problem_solving.php      # 문제 풀이 화면
│   ├── unit_completion.php      # 단원 완료 화면
│   ├── unit_tutor.css           # 스타일시트
│   └── unit_tutor.js            # 클라이언트 로직
├── rules/
│   └── unit_rules.yaml          # 단원 룰 정의
├── ontology/
│   ├── unit_ontology.jsonld     # 단원 온톨로지 스키마
│   └── unit_relations.yaml      # 단원 관계 데이터
└── DESIGN_DOCUMENT.md           # 이 문서
```

### 8.2 데이터 흐름

```
[학생 요청]
  ↓
[unit_tutor.php] ← 프론트 컨트롤러
  ↓
[unit_context.php] ← 단원 맥락 수집
  ├─ 선행 단원 확인
  ├─ 단원 난이도 확인
  └─ 학생 수준 확인
  ↓
[unit_rules.php] ← 룰 엔진 실행
  ├─ U0: 정보 수집 룰
  ├─ U1: 시작 전략 룰
  └─ U2: 학습 중 룰
  ↓
[unit_ontology.php] ← 온톨로지 처리
  ├─ UnitLearningContext 생성
  ├─ UnitLearningStrategy 생성
  └─ UnitLearningExecutionPlan 생성
  ↓
[unit_learning_service.php] ← 비즈니스 로직
  ├─ 학습 계획 수립
  ├─ 개념 마스터리 평가
  └─ 문제 추천
  ↓
[UI 컴포넌트] ← 화면 렌더링
  └─ 사용자에게 결과 표시
```

---

## 9. 구현 단계

### 9.1 Phase 1: 기본 구조 구축

1. **파일 구조 생성**
   - 기본 디렉토리 및 파일 생성
   - Moodle 연동 설정

2. **데이터 접근 레이어**
   - `unit_context.php`: 단원 맥락 데이터 수집
   - Math Topics 온톨로지 파일 로드 기능

3. **기본 UI**
   - 단원 선택 화면
   - 단원 학습 대시보드 기본 구조

### 9.2 Phase 2: 룰 엔진 통합

1. **룰 파일 작성**
   - `unit_rules.yaml`: 단원 튜터 룰 정의
   - Agent01 룰 구조 기반 확장

2. **룰 엔진 연동**
   - `unit_rules.php`: 룰 평가 로직
   - Agent01 룰 엔진 재사용

### 9.3 Phase 3: 온톨로지 통합

1. **온톨로지 스키마**
   - `unit_ontology.jsonld`: 단원 온톨로지 정의
   - Agent01 OIW Model 적용

2. **온톨로지 엔진**
   - `unit_ontology.php`: 온톨로지 처리
   - Math Topics 온톨로지 파일 연동

### 9.4 Phase 4: 서비스 레이어 구현

1. **학습 서비스**
   - `unit_learning_service.php`: 단원 학습 로직
   - 개념 마스터리 평가
   - 문제 추천 알고리즘

2. **관계 서비스**
   - `unit_relation_service.php`: 단원 관계 처리
   - 선후관계 확인
   - 관련 단원 추천

### 9.5 Phase 5: UI 완성

1. **핵심 화면 구현**
   - 개념 학습 화면
   - 문제 풀이 화면
   - 단원 완료 화면

2. **인터랙션 구현**
   - 실시간 피드백
   - 진행률 표시
   - 이해도 게이지

---

## 10. 데이터 소스 매핑

### 10.1 Math Topics 연동

```yaml
data_sources:
  unit_ontology:
    source: "alt42/orchestration/agents/math topics/{unit}_ontology.owl"
    parser: "owl_parser.py"
    output: "unit_ontology_data"
  
  unit_content:
    source: "alt42/orchestration/agents/math topics/{unit}.md"
    parser: "markdown_parser"
    output: "unit_content_data"
  
  unit_relations:
    source: "alt42/orchestration/agents/agent01_onboarding/rules/math_unit_relations.yaml"
    parser: "yaml_parser"
    output: "unit_relations_data"
```

### 10.2 학생 데이터 연동

```yaml
student_data:
  current_unit:
    source: "mdl_alt42_learning_progress.current_unit"
    type: "string"
  
  unit_mastery:
    source: "mdl_alt42_unit_mastery"
    type: "array"
  
  concept_understanding:
    source: "mdl_alt42_concept_understanding"
    type: "object"
  
  problem_solving_history:
    source: "mdl_alt42_problem_history"
    type: "array"
```

---

## 11. 테스트 전략

### 11.1 단위 테스트

- 룰 엔진 테스트
- 온톨로지 파싱 테스트
- 서비스 로직 테스트

### 11.2 통합 테스트

- Math Topics 온톨로지 연동 테스트
- Agent01 룰 엔진 연동 테스트
- 데이터베이스 연동 테스트

### 11.3 사용자 테스트

- 단원 학습 흐름 테스트
- 개념 학습 경험 테스트
- 문제 풀이 경험 테스트

---

## 12. 참고 자료

### 12.1 Agent01 Onboarding 문서

- `agent01_onboarding/rules/rules.yaml`: 룰 구조 참조
- `agent01_onboarding/ontology/principles.md`: 온톨로지 설계 원리
- `agent01_onboarding/COMPREHENSIVE_QUESTIONS_RULES.md`: 포괄형 질문 구조

### 12.2 Math Topics 문서

- `math topics/contents_info.md`: 단원 컨텐츠 정보
- `math topics/{unit}.md`: 단원별 상세 내용
- `math topics/{unit}_ontology.owl`: 단원 온톨로지 파일

### 12.3 단원 관계 데이터

- `agent01_onboarding/rules/math_unit_relations.yaml`: 단원 관계 및 난이도 데이터

---

## 13. 다음 단계

### 13.1 즉시 시작 가능한 작업

1. ✅ 기본 파일 구조 생성
2. ✅ `unit_rules.yaml` 초안 작성
3. ✅ `unit_ontology.jsonld` 스키마 정의
4. ✅ 기본 UI 컴포넌트 구현

### 13.2 추가 검토 필요 사항

1. ⏳ Math Topics 온톨로지 파일 파싱 로직
2. ⏳ Agent01 룰 엔진 재사용 방법
3. ⏳ 단원 마스터리 평가 기준
4. ⏳ 문제 추천 알고리즘

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: AI 튜터 설계 팀  
**기반 시스템**: Agent01 Onboarding

