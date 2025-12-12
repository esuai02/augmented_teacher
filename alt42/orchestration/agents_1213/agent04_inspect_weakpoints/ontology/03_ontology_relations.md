# Agent04 온톨로지 관계 명세서 (Ontology Relations Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints  
**목적**: 온톨로지 노드 간 관계(Relationships) 모델 정의

---

## 1. 문서 범위

이 문서는 **Agent04 온톨로지의 모든 관계(Relationship)**를 정의합니다.

**포함 내용**:
- 관계 속성(Relationship Properties) 정의
- 관계 카디널리티(Cardinality) 규칙
- 관계 제약 조건
- 관계 방향성 및 순환 참조 규칙

**제외 내용**:
- 클래스 정의 (01_ontology_specification.md 참조)
- 속성 정의 (01_ontology_specification.md 참조)
- 타입 정의 (02_ontology_types.md 참조)

---

## 2. 관계 분류

Agent04 온톨로지의 관계는 다음 3가지로 분류됩니다:

1. **계층 관계 (Hierarchical Relations)**: 부모-자식 관계
2. **참조 관계 (Reference Relations)**: 노드 간 참조
3. **메타 관계 (Meta Relations)**: 메타데이터 관계

---

## 3. 계층 관계 (Hierarchical Relations)

### 3.1 hasParent (부모 노드)

**관계 정의**:
```json
{
  "@id": "mk:hasParent",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "부모 노드를 가짐",
  "rdfs:comment": "온톨로지 계층 구조에서 부모 노드를 참조하는 관계",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "owl:Thing",
  "owl:inverseOf": "mk:hasChild"
}
```

**카디널리티**:
- 최소: 0 (root 노드는 parent 없음)
- 최대: 1 (단일 부모만 허용)

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 설명 |
|-----------|-----------|----------|------|
| `mk-a04:WeakpointDetectionContext` | `null` | 선택 | root 노드 (parent 없음) |
| `mk-a04:ActivityAnalysisContext` | `mk-a04:WeakpointDetectionContext` | 필수 | WeakpointDetectionContext의 자식 |
| `mk-a04:WeakpointAnalysisDecisionModel` | `mk-a04:WeakpointDetectionContext` | 필수 | WeakpointDetectionContext의 자식 |
| `mk-a04:ReinforcementPlanExecutionPlan` | `mk-a04:WeakpointAnalysisDecisionModel` | 필수 | DecisionModel의 자식 |

**제약 조건**:
- 순환 참조 금지: 노드는 자신의 조상(ancestor)을 parent로 가질 수 없음
- 단일 부모: 하나의 노드는 최대 1개의 parent만 가질 수 있음
- 타입 일치: parent는 반드시 유효한 온톨로지 인스턴스여야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:ActivityAnalysisContext/instance_001",
  "@type": "mk-a04:ActivityAnalysisContext",
  "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001"
}
```

---

### 3.2 hasChild (자식 노드)

**관계 정의**:
```json
{
  "@id": "mk:hasChild",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "자식 노드를 가짐",
  "rdfs:comment": "온톨로지 계층 구조에서 자식 노드를 참조하는 관계 (hasParent의 역관계)",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "owl:Thing",
  "owl:inverseOf": "mk:hasParent"
}
```

**카디널리티**:
- 최소: 0 (자식이 없을 수 있음)
- 최대: 무제한

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 설명 |
|-----------|-----------|----------|------|
| `mk-a04:WeakpointDetectionContext` | `mk-a04:ActivityAnalysisContext` | 선택 | 자식 노드 (0개 이상) |
| `mk-a04:WeakpointDetectionContext` | `mk-a04:WeakpointAnalysisDecisionModel` | 선택 | 자식 노드 (0개 이상) |
| `mk-a04:WeakpointAnalysisDecisionModel` | `mk-a04:ReinforcementPlanExecutionPlan` | 선택 | 자식 노드 (0개 또는 1개) |

**제약 조건**:
- 역관계 일관성: `A hasChild B`이면 반드시 `B hasParent A`여야 함
- 타입 일치: child는 반드시 유효한 온톨로지 인스턴스여야 함

---

## 4. 참조 관계 (Reference Relations)

### 4.1 usesContext (Context 참조)

**관계 정의**:
```json
{
  "@id": "mk:usesContext",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "Context를 사용함",
  "rdfs:comment": "Decision/Execution 노드가 참조하는 Context 노드들을 표현하는 관계",
  "rdfs:domain": ["mk-a04:WeakpointAnalysisDecisionModel", "mk-a04:ReinforcementPlanExecutionPlan"],
  "rdfs:range": ["mk-a04:WeakpointDetectionContext", "mk-a04:ActivityAnalysisContext"]
}
```

**카디널리티**:
- 최소: 1 (최소 1개 Context 참조 필수)
- 최대: 무제한

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 최소 개수 | 설명 |
|-----------|-----------|----------|----------|------|
| `mk-a04:WeakpointAnalysisDecisionModel` | `mk-a04:WeakpointDetectionContext` | 필수 | 1 | WeakpointDetectionContext 반드시 참조 |
| `mk-a04:WeakpointAnalysisDecisionModel` | `mk-a04:ActivityAnalysisContext` | 필수 | 1 | ActivityAnalysisContext 반드시 참조 |
| `mk-a04:ReinforcementPlanExecutionPlan` | `mk-a04:WeakpointDetectionContext` | 선택 | 0 | 간접 참조 (parent를 통해) |
| `mk-a04:ReinforcementPlanExecutionPlan` | `mk-a04:ActivityAnalysisContext` | 선택 | 0 | 간접 참조 (parent를 통해) |

**제약 조건**:
- 최소 2개: `mk-a04:WeakpointAnalysisDecisionModel`은 반드시 2개 이상의 Context를 참조해야 함
- 타입 제한: Context Layer 노드만 참조 가능
- 존재성: 참조하는 Context 노드는 반드시 존재해야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
  "@type": "mk-a04:WeakpointAnalysisDecisionModel",
  "mk:usesContext": [
    "mk-a04:WeakpointDetectionContext/instance_001",
    "mk-a04:ActivityAnalysisContext/instance_001"
  ]
}
```

---

### 4.2 referencesDecision (Decision 참조)

**관계 정의**:
```json
{
  "@id": "mk:referencesDecision",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "Decision을 참조함",
  "rdfs:comment": "Execution 노드가 참조하는 Decision 노드를 표현하는 관계",
  "rdfs:domain": "mk-a04:ReinforcementPlanExecutionPlan",
  "rdfs:range": "mk-a04:WeakpointAnalysisDecisionModel"
}
```

**카디널리티**:
- 최소: 1 (필수)
- 최대: 1 (단일 Decision만 참조)

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 설명 |
|-----------|-----------|----------|------|
| `mk-a04:ReinforcementPlanExecutionPlan` | `mk-a04:WeakpointAnalysisDecisionModel` | 필수 | 실행 계획이 참조하는 결정 모델 |

**제약 조건**:
- 단일 참조: 하나의 ExecutionPlan은 하나의 Decision만 참조
- 타입 제한: Decision Layer 노드만 참조 가능
- 존재성: 참조하는 Decision 노드는 반드시 존재해야 함
- 일관성: `referencesDecision`과 `hasParent`가 같은 노드를 가리켜야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk-a04:ReinforcementPlanExecutionPlan/instance_001",
  "@type": "mk-a04:ReinforcementPlanExecutionPlan",
  "mk:referencesDecision": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
  "mk:hasParent": "mk-a04:WeakpointAnalysisDecisionModel/instance_001"
}
```

---

### 4.3 hasDataSources (데이터 소스 참조)

**관계 정의**:
```json
{
  "@id": "mk:hasDataSources",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "데이터 소스를 가짐",
  "rdfs:comment": "Decision/Execution 노드가 참조하는 데이터 소스 노드들을 표현하는 관계",
  "rdfs:domain": ["mk-a04:WeakpointAnalysisDecisionModel", "mk-a04:ReinforcementPlanExecutionPlan"],
  "rdfs:range": "owl:Thing"
}
```

**카디널리티**:
- 최소: 1 (최소 1개 데이터 소스 필수)
- 최대: 무제한

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 설명 |
|-----------|-----------|----------|------|
| `mk-a04:WeakpointAnalysisDecisionModel` | `mk-a04:WeakpointDetectionContext` | 필수 | 데이터 소스 |
| `mk-a04:WeakpointAnalysisDecisionModel` | `mk-a04:ActivityAnalysisContext` | 필수 | 데이터 소스 |
| `mk-a04:ReinforcementPlanExecutionPlan` | `mk-a04:WeakpointAnalysisDecisionModel` | 필수 | 데이터 소스 (parent) |

**제약 조건**:
- 타입 제한: 온톨로지 인스턴스 또는 속성 경로만 참조 가능
- 존재성: 참조하는 데이터 소스는 반드시 존재해야 함

---

## 5. 메타 관계 (Meta Relations)

### 5.1 hasStage (레이어 단계)

**관계 정의**:
```json
{
  "@id": "mk:hasStage",
  "@type": "owl:DatatypeProperty",
  "rdfs:label": "레이어 단계를 가짐",
  "rdfs:comment": "노드가 속한 레이어 단계를 표현하는 메타데이터 관계",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "xsd:string"
}
```

**값 집합**:
- `"Context"`
- `"Decision"`
- `"Execution"`

**사용 위치**:

| 클래스 | Stage 값 | 필수 여부 |
|--------|---------|----------|
| `mk-a04:WeakpointDetectionContext` | `"Context"` | 필수 |
| `mk-a04:ActivityAnalysisContext` | `"Context"` | 필수 |
| `mk-a04:WeakpointAnalysisDecisionModel` | `"Decision"` | 필수 |
| `mk-a04:ReinforcementPlanExecutionPlan` | `"Execution"` | 필수 |

**제약 조건**:
- 값 제한: 위 3개 값 중 하나만 허용
- 불변성: 노드 생성 후 stage 값은 변경 불가

---

## 6. 관계 다이어그램

### 6.1 전체 관계 구조

```
┌─────────────────────────────┐
│  WeakpointDetectionContext │
│  (Context Layer)            │
│                             │
│  hasStage: "Context"        │
└───────────┬─────────────────┘
            │
            ├───hasParent───┐
            │               │
            ▼               ▼
┌──────────────────────┐  ┌──────────────────────────────┐
│ ActivityAnalysis     │  │ WeakpointAnalysisDecision    │
│ Context              │  │ Model (Decision Layer)       │
│ (Context Layer)      │  │                              │
│                      │  │ hasStage: "Decision"         │
│ hasParent: WDC       │  │ hasParent: WDC              │
│                      │  │ usesContext: [WDC, AAC]     │
└──────────────────────┘  └───────────┬──────────────────┘
                                       │
                                       ├───hasParent───┐
                                       │               │
                                       ▼               │
                            ┌──────────────────────────────┐
                            │ ReinforcementPlanExecution   │
                            │ Plan (Execution Layer)       │
                            │                              │
                            │ hasStage: "Execution"         │
                            │ hasParent: WADM              │
                            │ referencesDecision: WADM     │
                            └──────────────────────────────┘

WDC = WeakpointDetectionContext
AAC = ActivityAnalysisContext
WADM = WeakpointAnalysisDecisionModel
```

### 6.2 관계 카디널리티 요약

| 관계 | 소스 클래스 | 대상 클래스 | 최소 | 최대 | 필수 여부 |
|------|-----------|-----------|------|------|----------|
| `hasParent` | `ActivityAnalysisContext` | `WeakpointDetectionContext` | 1 | 1 | 필수 |
| `hasParent` | `WeakpointAnalysisDecisionModel` | `WeakpointDetectionContext` | 1 | 1 | 필수 |
| `hasParent` | `ReinforcementPlanExecutionPlan` | `WeakpointAnalysisDecisionModel` | 1 | 1 | 필수 |
| `hasChild` | `WeakpointDetectionContext` | `ActivityAnalysisContext` | 0 | * | 선택 |
| `hasChild` | `WeakpointDetectionContext` | `WeakpointAnalysisDecisionModel` | 0 | * | 선택 |
| `hasChild` | `WeakpointAnalysisDecisionModel` | `ReinforcementPlanExecutionPlan` | 0 | 1 | 선택 |
| `usesContext` | `WeakpointAnalysisDecisionModel` | `WeakpointDetectionContext` | 1 | 1 | 필수 |
| `usesContext` | `WeakpointAnalysisDecisionModel` | `ActivityAnalysisContext` | 1 | 1 | 필수 |
| `referencesDecision` | `ReinforcementPlanExecutionPlan` | `WeakpointAnalysisDecisionModel` | 1 | 1 | 필수 |
| `hasDataSources` | `WeakpointAnalysisDecisionModel` | `WeakpointDetectionContext` | 1 | * | 필수 |
| `hasDataSources` | `WeakpointAnalysisDecisionModel` | `ActivityAnalysisContext` | 1 | * | 필수 |
| `hasStage` | 모든 노드 | `xsd:string` | 1 | 1 | 필수 |

---

## 7. 관계 제약 조건

### 7.1 순환 참조 금지

**규칙**: 노드는 자신의 조상(ancestor)을 parent로 가질 수 없음

**검증 알고리즘**:
```
function validateNoCircularReference(node, parent):
  if parent == null:
    return true
  if node == parent:
    return false
  if parent.hasParent != null:
    return validateNoCircularReference(node, parent.hasParent)
  return true
```

### 7.2 역관계 일관성

**규칙**: `A hasChild B`이면 반드시 `B hasParent A`여야 함

**검증 알고리즘**:
```
function validateInverseConsistency(parent, child):
  if parent.hasChild.contains(child):
    assert child.hasParent == parent
  if child.hasParent == parent:
    assert parent.hasChild.contains(child)
```

### 7.3 타입 일치

**규칙**: 관계의 대상은 반드시 올바른 타입이어야 함

**검증 규칙**:
- `hasParent`의 대상은 반드시 유효한 온톨로지 인스턴스
- `usesContext`의 대상은 반드시 Context Layer 노드
- `referencesDecision`의 대상은 반드시 Decision Layer 노드

### 7.4 존재성 검증

**규칙**: 참조하는 노드는 반드시 존재해야 함

**검증 알고리즘**:
```
function validateReferenceExists(reference):
  if reference is URI:
    assert ontologyInstanceExists(reference)
  return true
```

---

## 8. 참고 문서

- **01_ontology_specification.md**: 클래스 및 속성 정의
- **02_ontology_types.md**: 타입 정의
- **04_ontology_constraints.md**: 무결성 제약 조건
- **06_jsonld_mapping.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent04 Ontology Team  
**다음 문서**: `04_ontology_constraints.md`

