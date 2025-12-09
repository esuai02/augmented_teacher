# Agent04 온톨로지 타입 명세서 (Ontology Types Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints  
**목적**: 온톨로지 값 공간(Value Space) 및 열거형 타입(Enumerations) 정의

---

## 1. 문서 범위

이 문서는 **Agent04 온톨로지에서 사용하는 모든 값 타입**을 정의합니다.

**포함 내용**:
- 열거형 타입(Enumerated Types)
- 값 공간(Value Space) 정의
- 타입 계층 구조
- 타입 간 관계

**제외 내용**:
- 클래스 정의 (01_ontology_specification.md 참조)
- 속성 정의 (01_ontology_specification.md 참조)
- 관계 정의 (03_ontology_relations.md 참조)

---

## 2. 타입 분류

Agent04 온톨로지의 타입은 다음 3가지로 분류됩니다:

1. **열거형 타입 (Enumerated Types)**: 제한된 값 집합
2. **기본 타입 (Primitive Types)**: 문자열, 정수 등 기본 데이터 타입
3. **복합 타입 (Complex Types)**: 리스트, 구조체 등

---

## 3. 열거형 타입 (Enumerated Types)

### 3.1 ActivityType (활동 유형)

**타입 정의**:
```json
{
  "@id": "mk-a04:ActivityType",
  "@type": "owl:Class",
  "rdfs:label": "활동 유형",
  "rdfs:comment": "학습 활동의 유형을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 우선순위 |
|------|--------|------|---------|
| `mk-a04:ConceptUnderstanding` | 개념이해 | 개념 학습 활동 | 1 |
| `mk-a04:TypeLearning` | 유형학습 | 문제 유형 학습 활동 | 2 |
| `mk-a04:ProblemSolving` | 문제풀이 | 문제 해결 활동 | 3 |
| `mk-a04:MistakeNote` | 오답노트 | 오답 정리 활동 | 4 |
| `mk-a04:QnA` | 질의응답 | 질문과 답변 활동 | 5 |
| `mk-a04:ReviewActivity` | 복습활동 | 복습 활동 | 6 |
| `mk-a04:Pomodoro` | 포모도르 | 집중 학습 활동 | 7 |

**사용 위치**:
- `mk-a04:WeakpointDetectionContext.mk-a04:hasActivityType`

**제약 조건**:
- 하나의 WeakpointDetectionContext 인스턴스는 반드시 하나의 ActivityType 값을 가져야 함
- 값은 위 7개 중 하나만 허용

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:ActivityType",
  "@type": "owl:Class",
  "rdfs:label": "활동 유형",
  "owl:oneOf": [
    { "@id": "mk-a04:ConceptUnderstanding" },
    { "@id": "mk-a04:TypeLearning" },
    { "@id": "mk-a04:ProblemSolving" },
    { "@id": "mk-a04:MistakeNote" },
    { "@id": "mk-a04:QnA" },
    { "@id": "mk-a04:ReviewActivity" },
    { "@id": "mk-a04:Pomodoro" }
  ]
}
```

---

### 3.2 SeverityLevel (심각도 수준)

**타입 정의**:
```json
{
  "@id": "mk-a04:SeverityLevel",
  "@type": "owl:Class",
  "rdfs:label": "심각도 수준",
  "rdfs:comment": "취약점의 심각도를 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 우선순위 |
|------|--------|------|---------|
| `mk-a04:Low` | 낮음 | 낮은 우선순위, 경미한 취약점 | 1 |
| `mk-a04:Medium` | 보통 | 중간 우선순위, 보통 취약점 | 2 |
| `mk-a04:High` | 높음 | 높은 우선순위, 심각한 취약점 | 3 |
| `mk-a04:Critical` | 매우 높음 | 최우선 처리, 매우 심각한 취약점 | 4 |

**사용 위치**:
- `mk-a04:WeakpointDetectionContext.mk-a04:hasWeakpointSeverity`

**제약 조건**:
- 하나의 WeakpointDetectionContext 인스턴스는 반드시 하나의 SeverityLevel 값을 가져야 함
- 값은 위 4개 중 하나만 허용

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:SeverityLevel",
  "@type": "owl:Class",
  "rdfs:label": "심각도 수준",
  "owl:oneOf": [
    { "@id": "mk-a04:Low" },
    { "@id": "mk-a04:Medium" },
    { "@id": "mk-a04:High" },
    { "@id": "mk-a04:Critical" }
  ]
}
```

---

### 3.3 ReinforcementStrategy (보강 전략)

**타입 정의**:
```json
{
  "@id": "mk-a04:ReinforcementStrategy",
  "@type": "owl:Class",
  "rdfs:label": "보강 전략",
  "rdfs:comment": "취약점 보강을 위한 전략을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 적용 조건 |
|------|--------|------|----------|
| `mk-a04:ConceptClarificationStrategy` | 개념 명확화 전략 | 개념의 정의와 예시를 명확히 구분 | 개념 혼동 발생 시 |
| `mk-a04:MethodOptimizationStrategy` | 방법 최적화 전략 | 학습 방법을 페르소나에 맞게 최적화 | 방법-페르소나 불일치 시 |
| `mk-a04:AttentionRecoveryStrategy` | 주의 회복 전략 | 집중도 저하 시 주의 회복 | 주의집중도 낮을 때 |
| `mk-a04:CombinationOptimizationStrategy` | 조합 최적화 전략 | 최적 활동 조합 제시 | 몰입도 낮을 때 |
| `mk-a04:BoredomInterventionStrategy` | 지루함 개입 전략 | 지루함 발생 시 개입 | 지루함 탐지 시 |

**사용 위치**:
- `mk-a04:WeakpointAnalysisDecisionModel.mk-a04:hasReinforcementStrategy`

**제약 조건**:
- 하나의 DecisionModel 인스턴스는 반드시 하나의 ReinforcementStrategy 값을 가져야 함
- 값은 위 5개 중 하나만 허용

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:ReinforcementStrategy",
  "@type": "owl:Class",
  "rdfs:label": "보강 전략",
  "owl:oneOf": [
    { "@id": "mk-a04:ConceptClarificationStrategy" },
    { "@id": "mk-a04:MethodOptimizationStrategy" },
    { "@id": "mk-a04:AttentionRecoveryStrategy" },
    { "@id": "mk-a04:CombinationOptimizationStrategy" },
    { "@id": "mk-a04:BoredomInterventionStrategy" }
  ]
}
```

---

### 3.4 PriorityLevel (우선순위 수준)

**타입 정의**:
```json
{
  "@id": "mk-a04:PriorityLevel",
  "@type": "owl:Class",
  "rdfs:label": "우선순위 수준",
  "rdfs:comment": "보강 작업의 우선순위를 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 | 처리 순서 |
|------|--------|------|----------|
| `mk-a04:Low` | 낮음 | 낮은 우선순위 | 4 |
| `mk-a04:Medium` | 보통 | 중간 우선순위 | 3 |
| `mk-a04:High` | 높음 | 높은 우선순위 | 2 |
| `mk-a04:Urgent` | 긴급 | 최우선 처리 | 1 |

**사용 위치**:
- `mk-a04:WeakpointAnalysisDecisionModel.mk-a04:hasReinforcementPriority`

**제약 조건**:
- 하나의 DecisionModel 인스턴스는 반드시 하나의 PriorityLevel 값을 가져야 함
- 값은 위 4개 중 하나만 허용

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:PriorityLevel",
  "@type": "owl:Class",
  "rdfs:label": "우선순위 수준",
  "owl:oneOf": [
    { "@id": "mk-a04:Low" },
    { "@id": "mk-a04:Medium" },
    { "@id": "mk-a04:High" },
    { "@id": "mk-a04:Urgent" }
  ]
}
```

---

### 3.5 InterventionType (개입 유형)

**타입 정의**:
```json
{
  "@id": "mk-a04:InterventionType",
  "@type": "owl:Class",
  "rdfs:label": "개입 유형",
  "rdfs:comment": "취약점에 대한 개입 유형을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk-a04:ConceptWeakPointSupport` | 개념 취약점 지원 | 개념 이해 취약점 지원 |
| `mk-a04:AttentionRecovery` | 주의 회복 | 주의집중도 회복 개입 |
| `mk-a04:ConceptClarification` | 개념 명확화 | 개념 혼동 명확화 |
| `mk-a04:MethodOptimization` | 방법 최적화 | 학습 방법 최적화 |
| `mk-a04:VisualLearningOptimization` | 시각 학습 최적화 | 시각 자료 활용 최적화 |
| `mk-a04:ExampleFocusedLearning` | 예제 중심 학습 | 예제 중심 학습 개입 |
| `mk-a04:CombinationOptimization` | 조합 최적화 | 활동 조합 최적화 |
| `mk-a04:BoredomIntervention` | 지루함 개입 | 지루함 발생 시 개입 |

**사용 위치**:
- `mk-a04:WeakpointAnalysisDecisionModel.mk-a04:hasInterventionType`

**제약 조건**:
- 하나의 DecisionModel 인스턴스는 반드시 하나의 InterventionType 값을 가져야 함
- 값은 위 8개 중 하나만 허용

---

### 3.6 ConfusionType (혼동 유형)

**타입 정의**:
```json
{
  "@id": "mk-a04:ConfusionType",
  "@type": "owl:Class",
  "rdfs:label": "혼동 유형",
  "rdfs:comment": "개념 혼동의 유형을 표현하는 열거형 타입",
  "rdfs:subClassOf": "owl:Thing"
}
```

**값 집합 (Value Set)**:

| 값 ID | 레이블 | 설명 |
|------|--------|------|
| `mk-a04:DefinitionVsExample` | 정의 vs 예시 | 정의와 예시를 구분하지 못함 |
| `mk-a04:FormulaVsCondition` | 공식 vs 조건 | 공식과 조건을 구분하지 못함 |
| `mk-a04:SimilarConcepts` | 유사 개념 | 유사한 개념을 혼동함 |

**사용 위치**:
- `mk-a04:ActivityAnalysisContext.mk-a04:hasConfusionType`

**제약 조건**:
- 값은 위 3개 중 하나 이상 허용 (다중 선택 가능)

---

## 4. 기본 타입 (Primitive Types)

### 4.1 문자열 타입 (String Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:string` | 일반 문자열 | 없음 | "개념이해" |
| `xsd:normalizedString` | 정규화된 문자열 | 공백 정규화 | "TTS 듣기" |
| `mk-a04:ActivityCategoryString` | 활동 카테고리 문자열 | 최대 100자 | "개념이해" |
| `mk-a04:SubActivityString` | 하위 활동 문자열 | 최대 200자 | "TTS 듣기" |
| `mk-a04:WeakpointPatternString` | 취약점 패턴 문자열 | 최대 500자 | "개념 정독 단계에서 멈춤 빈번" |

### 4.2 정수 타입 (Integer Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:integer` | 일반 정수 | 없음 | 5 |
| `xsd:nonNegativeInteger` | 비음수 정수 | 0 이상 | 5 |
| `mk-a04:PauseFrequency` | 멈춤 빈도 | 0 이상 | 5 |
| `mk-a04:AttentionDropTime` | 집중 이탈 시점 (초) | 0 이상 | 1200 |

### 4.3 실수 타입 (Decimal Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:decimal` | 일반 실수 | 없음 | 0.65 |
| `mk-a04:AttentionScore` | 주의집중도 점수 | 0.0 이상 1.0 이하 | 0.6 |
| `mk-a04:GazeAttentionScore` | 시선 집중도 점수 | 0.0 이상 1.0 이하 | 0.55 |
| `mk-a04:ImmersionScore` | 몰입도 점수 | 0.0 이상 1.0 이하 | 0.65 |
| `mk-a04:MethodPersonaMatchScore` | 방법-페르소나 적합도 점수 | 0.0 이상 1.0 이하 | 0.7 |
| `mk-a04:VisualResponseScore` | 시각 자료 반응 점수 | 0.0 이상 1.0 이하 | 0.5 |
| `mk-a04:TextOrganizationScore` | 텍스트 정리 점수 | 0.0 이상 1.0 이하 | 0.6 |
| `mk-a04:ExampleVerificationScore` | 예제 확인 점수 | 0.0 이상 1.0 이하 | 0.7 |

### 4.4 불리언 타입 (Boolean Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:boolean` | 불리언 | true/false | true |

**사용 위치**:
- `mk-a04:ActivityAnalysisContext.mk-a04:hasConceptConfusionDetected`
- `mk-a04:ActivityAnalysisContext.mk-a04:hasBoredomDetected`

### 4.5 날짜/시간 타입 (DateTime Types)

| 타입 ID | 설명 | 제약 조건 | 예시 |
|--------|------|----------|------|
| `xsd:dateTime` | 날짜 및 시간 | ISO 8601 형식 | "2025-01-27T10:30:00Z" |

**사용 위치**:
- `mk-a04:WeakpointDetectionContext.mk-a04:hasDetectionTimestamp`

---

## 5. 복합 타입 (Complex Types)

### 5.1 리스트 타입 (List Types)

| 타입 ID | 설명 | 요소 타입 | 제약 조건 | 예시 |
|--------|------|----------|----------|------|
| `rdf:List` | 일반 리스트 | 제한 없음 | 없음 | ["정답률: 40%", "소요시간: 25분"] |
| `mk-a04:PerformanceMetricsList` | 성능 지표 리스트 | `xsd:string` | 최소 1개 요소 | ["정답률: 40%", "소요시간: 25분"] |
| `mk-a04:ActionList` | 행동 리스트 | `xsd:string` | 최소 1개 요소 | ["개념 비교 콘텐츠 제공", "예제 중심 학습 자료 제시"] |
| `mk-a04:MeasurementList` | 측정 방법 리스트 | `xsd:string` | 최소 1개 요소 | ["멈춤 빈도 감소 여부", "개념 이해도 향상 여부"] |
| `mk-a04:FeedbackList` | 피드백 리스트 | `xsd:string` | 최소 0개 요소 | ["다음 활동에서 개념 혼동 감소 여부 확인"] |
| `mk-a04:AdjustmentList` | 조정 계획 리스트 | `xsd:string` | 최소 0개 요소 | ["예제 중심 학습이 효과 없으면 다른 방법 시도"] |
| `mk-a04:ContentLinksList` | 콘텐츠 링크 리스트 | `xsd:string` | 최소 1개 요소 | ["concept_comparison_definition_vs_example"] |
| `mk-a04:ConfusionTypeList` | 혼동 유형 리스트 | `mk-a04:ConfusionType` | 최소 0개 요소 | ["mk-a04:DefinitionVsExample"] |

---

## 6. 타입 계층 구조

```
owl:Thing
├── mk-a04:ActivityType
│   ├── mk-a04:ConceptUnderstanding
│   ├── mk-a04:TypeLearning
│   ├── mk-a04:ProblemSolving
│   ├── mk-a04:MistakeNote
│   ├── mk-a04:QnA
│   ├── mk-a04:ReviewActivity
│   └── mk-a04:Pomodoro
├── mk-a04:SeverityLevel
│   ├── mk-a04:Low
│   ├── mk-a04:Medium
│   ├── mk-a04:High
│   └── mk-a04:Critical
├── mk-a04:ReinforcementStrategy
│   ├── mk-a04:ConceptClarificationStrategy
│   ├── mk-a04:MethodOptimizationStrategy
│   ├── mk-a04:AttentionRecoveryStrategy
│   ├── mk-a04:CombinationOptimizationStrategy
│   └── mk-a04:BoredomInterventionStrategy
├── mk-a04:PriorityLevel
│   ├── mk-a04:Low
│   ├── mk-a04:Medium
│   ├── mk-a04:High
│   └── mk-a04:Urgent
├── mk-a04:InterventionType
│   ├── mk-a04:ConceptWeakPointSupport
│   ├── mk-a04:AttentionRecovery
│   ├── mk-a04:ConceptClarification
│   ├── mk-a04:MethodOptimization
│   ├── mk-a04:VisualLearningOptimization
│   ├── mk-a04:ExampleFocusedLearning
│   ├── mk-a04:CombinationOptimization
│   └── mk-a04:BoredomIntervention
└── mk-a04:ConfusionType
    ├── mk-a04:DefinitionVsExample
    ├── mk-a04:FormulaVsCondition
    └── mk-a04:SimilarConcepts
```

---

## 7. 타입 간 관계

### 7.1 타입 호환성

| 타입 A | 관계 | 타입 B | 설명 |
|--------|------|--------|------|
| `mk-a04:SeverityLevel` | `→` | `mk-a04:PriorityLevel` | 심각도에 따라 우선순위 결정 |
| `mk-a04:AttentionScore` | `→` | `mk-a04:SeverityLevel` | 주의집중도가 낮으면 높은 심각도 |
| `mk-a04:PauseFrequency` | `→` | `mk-a04:SeverityLevel` | 멈춤 빈도가 높으면 높은 심각도 |

### 7.2 타입 변환 규칙

**SeverityLevel → PriorityLevel**:
```
mk-a04:Critical → mk-a04:Urgent
mk-a04:High → mk-a04:High
mk-a04:Medium → mk-a04:Medium
mk-a04:Low → mk-a04:Low
```

**AttentionScore → SeverityLevel (추정)**:
```
0.0-0.3 → mk-a04:Critical
0.3-0.5 → mk-a04:High
0.5-0.7 → mk-a04:Medium
0.7-1.0 → mk-a04:Low
```

**PauseFrequency → SeverityLevel (추정)**:
```
10회 이상 → mk-a04:Critical
5-9회 → mk-a04:High
3-4회 → mk-a04:Medium
0-2회 → mk-a04:Low
```

---

## 8. 타입 확장 가이드

### 8.1 새로운 열거형 타입 추가

새로운 열거형 타입을 추가할 때는 다음 구조를 따릅니다:

```json
{
  "@id": "mk-a04:NewEnumType",
  "@type": "owl:Class",
  "rdfs:label": "새 열거형 타입",
  "rdfs:comment": "설명",
  "rdfs:subClassOf": "owl:Thing",
  "owl:oneOf": [
    { "@id": "mk-a04:Value1" },
    { "@id": "mk-a04:Value2" }
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

- **01_ontology_specification.md**: 클래스 및 속성 정의
- **03_ontology_relations.md**: 관계 모델 정의
- **04_ontology_constraints.md**: 무결성 제약 조건
- **06_jsonld_mapping.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent04 Ontology Team  
**다음 문서**: `03_ontology_relations.md`

