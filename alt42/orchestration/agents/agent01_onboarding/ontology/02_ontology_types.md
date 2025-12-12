# Agent01 온톨로지 타입 명세서 (Ontology Types Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: 온톨로지 값 공간(Value Space) 및 열거형 타입(Enumerations) 정의

---

## 1. 문서 범위

이 문서는 **Agent01 온톨로지에서 사용하는 모든 값 타입**을 정의합니다.

**포함 내용**:
- 열거형 타입(Enumerated Types)
- 값 공간(Value Space) 정의
- 타입 계층 구조
- 타입 간 관계

**제외 내용**:
- 클래스 정의 (01_ONTOLOGY_SPEC.md 참조)
- 속성 정의 (01_ONTOLOGY_SPEC.md 참조)
- 관계 정의 (03_ONTOLOGY_RELATIONS.md 참조)

---

## 2. 타입 분류

Agent01 온톨로지의 타입은 다음 3가지로 분류됩니다:

1. **열거형 타입 (Enumerated Types)**: 제한된 값 집합
2. **기본 타입 (Primitive Types)**: 문자열, 정수 등 기본 데이터 타입
3. **복합 타입 (Complex Types)**: 리스트, 구조체 등

---

## 3. 열거형 타입 (Enumerated Types)

### 3.1 DifficultyLevel (난이도 수준)

**타입 정의**:
```json
{
  "@id": "mk:DifficultyLevel",
  "@type": "owl:Class",
  "rdfs:label": "난이도 수준",
  "rdfs:comment": "수업/문제의 난이도를 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 우선순위 |
|------|--------|------|---------|
| `mk:Easy` | 쉬움 | 기초 수준, 개념 이해 중심 | 1 |
| `mk:EasyToMedium` | 쉬움~보통 | 기초에서 중급으로 전환 | 2 |
| `mk:Medium` | 보통 | 표준 수준, 응용 문제 | 3 |
| `mk:MediumToHard` | 보통~어려움 | 중급에서 고급으로 전환 | 4 |
| `mk:Hard` | 어려움 | 고급 수준, 심화 문제 | 5 |
| `mk:VeryHard` | 매우 어려움 | 최고 수준, 경시대회 수준 | 6 |

**사용 위치**:
- `mk:FirstClassDecisionModel.mk:hasDifficultyLevel`

**제약 조건**:
- 하나의 DecisionModel 인스턴스는 반드시 하나의 DifficultyLevel 값을 가져야 함
- 값은 위 6개 중 하나만 허용

**JSON-LD 표현**:
```json
{
  "@id": "mk:DifficultyLevel",
  "@type": "owl:Class",
  "rdfs:label": "난이도 수준",
  "owl:oneOf": [
    { "@id": "mk:Easy" },
    { "@id": "mk:EasyToMedium" },
    { "@id": "mk:Medium" },
    { "@id": "mk:MediumToHard" },
    { "@id": "mk:Hard" },
    { "@id": "mk:VeryHard" }
  ]
}
```

---

### 3.2 AlignmentStrategy (정렬 전략)

**타입 정의**:
```json
{
  "@id": "mk:AlignmentStrategy",
  "@type": "owl:Class",
  "rdfs:label": "정렬 전략",
  "rdfs:comment": "학원-학교-집 진도를 정렬하는 전략을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 적용 조건 |
|------|--------|------|----------|
| `mk:BridgeStrategy` | 다리 놓기 전략 | 진도 간극을 메우는 전략 | 학원 진도 선행, 개념 이해 부족 |
| `mk:ReinforcementStrategy` | 보강 전략 | 현재 진도를 강화하는 전략 | 학원 진도 선행, 개념 이해 양호 |
| `mk:PreviewStrategy` | 예습 전략 | 학교 진도를 미리 학습하는 전략 | 학교 진도 선행 |
| `mk:SynchronizedStrategy` | 동기화 전략 | 모든 진도를 일치시키는 전략 | 진도가 일치하는 경우 |

**사용 위치**:
- `mk:FirstClassDecisionModel.mk:hasAlignmentStrategy`

**제약 조건**:
- 하나의 DecisionModel 인스턴스는 반드시 하나의 AlignmentStrategy 값을 가져야 함
- 값은 위 4개 중 하나만 허용

**JSON-LD 표현**:
```json
{
  "@id": "mk:AlignmentStrategy",
  "@type": "owl:Class",
  "rdfs:label": "정렬 전략",
  "owl:oneOf": [
    { "@id": "mk:BridgeStrategy" },
    { "@id": "mk:ReinforcementStrategy" },
    { "@id": "mk:PreviewStrategy" },
    { "@id": "mk:SynchronizedStrategy" }
  ]
}
```

---

### 3.3 ConfidenceLevel (자신감 수준)

**타입 정의**:
```json
{
  "@id": "mk:ConfidenceLevel",
  "@type": "owl:Class",
  "rdfs:label": "자신감 수준",
  "rdfs:comment": "학생의 수학 자신감 수준을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 점수 범위 |
|------|--------|------|----------|
| `mk:VeryLow` | 매우 낮음 | 자신감 거의 없음 | 0-2 |
| `mk:Low` | 낮음 | 자신감 부족 | 3-4 |
| `mk:Medium` | 보통 | 적절한 자신감 | 5-6 |
| `mk:High` | 높음 | 자신감 충분 | 7-8 |
| `mk:VeryHigh` | 매우 높음 | 자신감 매우 높음 | 9-10 |

**사용 위치**:
- `mk:OnboardingContext` (파생 속성, 원시 데이터 `mk:hasMathConfidence`에서 계산)

**제약 조건**:
- `mk:hasMathConfidence` (0-10 정수) 값에 따라 자동 계산
- 값은 위 5개 중 하나만 허용

**계산 규칙**:
```
if mk:hasMathConfidence ∈ [0, 2] then mk:ConfidenceLevel = mk:VeryLow
if mk:hasMathConfidence ∈ [3, 4] then mk:ConfidenceLevel = mk:Low
if mk:hasMathConfidence ∈ [5, 6] then mk:ConfidenceLevel = mk:Medium
if mk:hasMathConfidence ∈ [7, 8] then mk:ConfidenceLevel = mk:High
if mk:hasMathConfidence ∈ [9, 10] then mk:ConfidenceLevel = mk:VeryHigh
```

---

### 3.4 StressLevel (스트레스 수준)

**타입 정의**:
```json
{
  "@id": "mk:StressLevel",
  "@type": "owl:Class",
  "rdfs:label": "스트레스 수준",
  "rdfs:comment": "학생의 수학 스트레스 수준을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk:Low` | 낮음 | 스트레스가 거의 없음 |
| `mk:Medium` | 보통 | 적절한 수준의 스트레스 |
| `mk:High` | 높음 | 스트레스가 높음 |
| `mk:VeryHigh` | 매우 높음 | 스트레스가 매우 높음 |

**사용 위치**:
- `mk:OnboardingContext.mk:hasMathStressLevel` (원시 데이터)

**제약 조건**:
- 값은 위 4개 중 하나만 허용

---

### 3.5 MathLevel (수학 수준)

**타입 정의**:
```json
{
  "@id": "mk:MathLevel",
  "@type": "owl:Class",
  "rdfs:label": "수학 수준",
  "rdfs:comment": "학생의 전반적인 수학 수준을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk:Lower` | 하위권 | 하위권 수준 |
| `mk:LowerToMiddle` | 하위~중위 | 하위에서 중위로 전환 |
| `mk:Middle` | 중위권 | 중위권 수준 |
| `mk:MiddleToUpper` | 중위~상위 | 중위에서 상위로 전환 |
| `mk:Upper` | 상위권 | 상위권 수준 |
| `mk:Top` | 최상위권 | 최상위권 수준 |

**사용 위치**:
- `mk:OnboardingContext.mk:hasMathLevel` (원시 데이터)

**제약 조건**:
- 값은 위 6개 중 하나만 허용

---

### 3.6 MathLearningStyle (수학 학습 스타일)

**타입 정의**:
```json
{
  "@id": "mk:MathLearningStyle",
  "@type": "owl:Class",
  "rdfs:label": "수학 학습 스타일",
  "rdfs:comment": "학생의 수학 학습 스타일을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk:CalculationType` | 계산형 | 계산과 연습 문제 중심 |
| `mk:ConceptType` | 개념형 | 개념 이해 중심 |
| `mk:ApplicationType` | 응용형 | 응용 문제 중심 |
| `mk:MixedType` | 혼합형 | 여러 스타일 혼합 |

**사용 위치**:
- `mk:OnboardingContext` (파생 속성, 원시 데이터에서 추출)

**제약 조건**:
- 값은 위 4개 중 하나만 허용

---

### 3.7 ExamStyle (시험 대비 성향)

**타입 정의**:
```json
{
  "@id": "mk:ExamStyle",
  "@type": "owl:Class",
  "rdfs:label": "시험 대비 성향",
  "rdfs:comment": "학생의 시험 대비 성향을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk:Cramming` | 벼락치기 | 시험 직전 집중 학습 |
| `mk:Planned` | 계획형 | 일정에 맞춘 계획적 학습 |
| `mk:Continuous` | 지속형 | 꾸준한 학습 |
| `mk:Irregular` | 불규칙형 | 불규칙한 학습 패턴 |

**사용 위치**:
- `mk:OnboardingContext` (파생 속성, 원시 데이터에서 추출)

**제약 조건**:
- 값은 위 4개 중 하나만 허용

---

## 4. 기본 타입 (Primitive Types)

### 4.1 문자열 타입 (String Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:string` | 일반 문자열 | 없음 | "중2" |
| `xsd:normalizedString` | 정규화된 문자열 | 공백 정규화 | "OO중학교" |
| `mk:GradeString` | 학년 문자열 | 패턴: "초1"~"고3" | "중2" |
| `mk:SchoolNameString` | 학교명 문자열 | 최대 100자 | "OO중학교" |
| `mk:AcademyNameString` | 학원명 문자열 | 최대 100자 | "OO수학학원" |

### 4.2 정수 타입 (Integer Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:integer` | 일반 정수 | 없음 | 4 |
| `xsd:nonNegativeInteger` | 비음수 정수 | 0 이상 | 4 |
| `mk:ConfidenceScore` | 자신감 점수 | 0 이상 10 이하 | 4 |
| `mk:PriorityScore` | 우선순위 점수 | 1 이상 10 이하 | 8 |

### 4.3 불리언 타입 (Boolean Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:boolean` | 불리언 | true/false | true |

---

## 5. 복합 타입 (Complex Types)

### 5.1 리스트 타입 (List Types)

| 타입 ID | 설명 | 요소 타입 | 제약 조건 | 예시 |
|--------|------|----------|----------|------|
| `rdf:List` | 일반 리스트 | 제한 없음 | 없음 | ["개념원리 중2-1", "쎈 중2-1"] |
| `mk:TextbookList` | 교재 리스트 | `xsd:string` | 최소 1개 요소 | ["개념원리 중2-1", "쎈 중2-1"] |
| `mk:UnitPlanList` | 단원 계획 리스트 | `xsd:string` | 최소 1개 요소 | ["중2-1 방정식 핵심 복습", "함수 도입 준비"] |
| `mk:ActionList` | 행동 리스트 | `xsd:string` | 최소 1개 요소 | ["도입 루틴: ...", "설명 전략: ..."] |
| `mk:ProblemPriorityList` | 문제 우선순위 리스트 | `xsd:string` (문제 ID) | 최소 1개 요소 | ["P1", "P3", "P2"] |

### 5.2 구조체 타입 (Struct Types)

#### 5.2.1 CandidateProblem (문제 후보)

**타입 정의**:
```json
{
  "@id": "mk:CandidateProblem",
  "@type": "owl:Class",
  "rdfs:label": "문제 후보",
  "rdfs:comment": "Interpretation Layer에서 도출된 문제 후보 구조체"
}
```

**속성**:

| 속성 ID | 타입 | 카디널리티 | 설명 |
|--------|------|-----------|------|
| `mk:hasProblemId` | `xsd:string` | 1 | 문제 ID (예: "P1") |
| `mk:hasDescription` | `xsd:string` | 1 | 문제 설명 |
| `mk:hasSeverity` | `mk:SeverityLevel` | 1 | 심각도 (high/medium/low) |
| `mk:hasWillAlignment` | `rdf:List` | 0..* | 관련 Will 항목 리스트 |
| `mk:hasDataSources` | `rdf:List` | 1..* | 데이터 소스 리스트 |

**JSON-LD 예시**:
```json
{
  "@id": "mk:CandidateProblem/instance_001",
  "@type": "mk:CandidateProblem",
  "mk:hasProblemId": "P1",
  "mk:hasDescription": "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태",
  "mk:hasSeverity": "mk:High",
  "mk:hasWillAlignment": ["좌절 방지", "자존감 보호"],
  "mk:hasDataSources": [
    "mk:LearningContextIntegration/instance_001.mk:hasUnitMastery",
    "mk:LearningContextIntegration/instance_001.mk:hasConceptProgress"
  ]
}
```

#### 5.2.2 SeverityLevel (심각도 수준)

**타입 정의**:
```json
{
  "@id": "mk:SeverityLevel",
  "@type": "owl:Class",
  "rdfs:label": "심각도 수준",
  "rdfs:comment": "문제의 심각도를 표현하는 열거형 타입"
}
```

**값 집합**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk:Low` | 낮음 | 낮은 우선순위 |
| `mk:Medium` | 보통 | 중간 우선순위 |
| `mk:High` | 높음 | 높은 우선순위 |

---

## 6. 타입 계층 구조

```
owl:Thing
├── mk:DifficultyLevel
│   ├── mk:Easy
│   ├── mk:EasyToMedium
│   ├── mk:Medium
│   ├── mk:MediumToHard
│   ├── mk:Hard
│   └── mk:VeryHard
├── mk:AlignmentStrategy
│   ├── mk:BridgeStrategy
│   ├── mk:ReinforcementStrategy
│   ├── mk:PreviewStrategy
│   └── mk:SynchronizedStrategy
├── mk:ConfidenceLevel
│   ├── mk:VeryLow
│   ├── mk:Low
│   ├── mk:Medium
│   ├── mk:High
│   └── mk:VeryHigh
├── mk:StressLevel
│   ├── mk:Low
│   ├── mk:Medium
│   ├── mk:High
│   └── mk:VeryHigh
├── mk:MathLevel
│   ├── mk:Lower
│   ├── mk:LowerToMiddle
│   ├── mk:Middle
│   ├── mk:MiddleToUpper
│   ├── mk:Upper
│   └── mk:Top
├── mk:MathLearningStyle
│   ├── mk:CalculationType
│   ├── mk:ConceptType
│   ├── mk:ApplicationType
│   └── mk:MixedType
├── mk:ExamStyle
│   ├── mk:Cramming
│   ├── mk:Planned
│   ├── mk:Continuous
│   └── mk:Irregular
└── mk:SeverityLevel
    ├── mk:Low
    ├── mk:Medium
    └── mk:High
```

---

## 7. 타입 간 관계

### 7.1 타입 호환성

| 타입 A | 관계 | 타입 B | 설명 |
|--------|------|--------|------|
| `mk:ConfidenceScore` | `→` | `mk:ConfidenceLevel` | 점수를 수준으로 변환 |
| `mk:MathLevel` | `→` | `mk:DifficultyLevel` | 수준에 따라 난이도 추정 가능 |
| `mk:StressLevel` | `→` | `mk:DifficultyLevel` | 스트레스가 높으면 쉬운 난이도 권장 |

### 7.2 타입 변환 규칙

**ConfidenceScore → ConfidenceLevel**:
```
0-2 → mk:VeryLow
3-4 → mk:Low
5-6 → mk:Medium
7-8 → mk:High
9-10 → mk:VeryHigh
```

**MathLevel → DifficultyLevel (추정)**:
```
mk:Lower → mk:Easy
mk:LowerToMiddle → mk:EasyToMedium
mk:Middle → mk:Medium
mk:MiddleToUpper → mk:MediumToHard
mk:Upper → mk:Hard
mk:Top → mk:VeryHard
```

---

## 8. 타입 확장 가이드

### 8.1 새로운 열거형 타입 추가

새로운 열거형 타입을 추가할 때는 다음 구조를 따릅니다:

```json
{
  "@id": "mk:NewEnumType",
  "@type": "owl:Class",
  "rdfs:label": "새 열거형 타입",
  "rdfs:comment": "설명",
  "rdfs:subClassOf": "owl:Thing",
  "owl:oneOf": [
    { "@id": "mk:Value1" },
    { "@id": "mk:Value2" }
  ]
}
```

### 8.2 타입 버전 관리

타입 정의가 변경될 때는:
1. 버전 번호 증가
2. 변경 이력 기록
3. 하위 호환성 확인

---

## 9. 참고 문서

- **01_ONTOLOGY_SPEC.md**: 클래스 및 속성 정의
- **03_ONTOLOGY_RELATIONS.md**: 관계 모델 정의
- **04_ONTOLOGY_CONSTRAINTS.md**: 무결성 제약 조건
- **06_JSONLD_MAPPING.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**다음 문서**: `03_ontology_relations.md`

