# Agent01 온톨로지 관계 명세서 (Ontology Relations Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: 온톨로지 노드 간 관계(Relationships) 모델 정의

---

## 1. 문서 범위

이 문서는 **Agent01 온톨로지의 모든 관계(Relationship)**를 정의합니다.

**포함 내용**:
- 관계 속성(Relationship Properties) 정의
- 관계 카디널리티(Cardinality) 규칙
- 관계 제약 조건
- 관계 방향성 및 순환 참조 규칙

**제외 내용**:
- 클래스 정의 (01_ONTOLOGY_SPEC.md 참조)
- 속성 정의 (01_ONTOLOGY_SPEC.md 참조)
- 타입 정의 (02_ONTOLOGY_TYPES.md 참조)

---

## 2. 관계 분류

Agent01 온톨로지의 관계는 다음 3가지로 분류됩니다:

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
| `mk:OnboardingContext` | `null` | 선택 | root 노드 (parent 없음) |
| `mk:LearningContextIntegration` | `mk:OnboardingContext` | 필수 | OnboardingContext의 자식 |
| `mk:FirstClassDecisionModel` | `mk:OnboardingContext` | 필수 | OnboardingContext의 자식 |
| `mk:FirstClassExecutionPlan` | `mk:FirstClassDecisionModel` | 필수 | DecisionModel의 자식 |

**제약 조건**:
- 순환 참조 금지: 노드는 자신의 조상(ancestor)을 parent로 가질 수 없음
- 단일 부모: 하나의 노드는 최대 1개의 parent만 가질 수 있음
- 타입 일치: parent는 반드시 유효한 온톨로지 인스턴스여야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk:LearningContextIntegration/instance_001",
  "@type": "mk:LearningContextIntegration",
  "mk:hasParent": "mk:OnboardingContext/instance_001"
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
| `mk:OnboardingContext` | `mk:LearningContextIntegration` | 선택 | 자식 노드 (0개 이상) |
| `mk:OnboardingContext` | `mk:FirstClassDecisionModel` | 선택 | 자식 노드 (0개 이상) |
| `mk:FirstClassDecisionModel` | `mk:FirstClassExecutionPlan` | 선택 | 자식 노드 (0개 또는 1개) |

**제약 조건**:
- 역관계 일관성: `A hasChild B`이면 반드시 `B hasParent A`여야 함
- 타입 일치: child는 반드시 유효한 온톨로지 인스턴스여야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk:OnboardingContext/instance_001",
  "@type": "mk:OnboardingContext",
  "mk:hasChild": [
    "mk:LearningContextIntegration/instance_001",
    "mk:FirstClassDecisionModel/instance_001"
  ]
}
```

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
  "rdfs:domain": ["mk:FirstClassDecisionModel", "mk:FirstClassExecutionPlan"],
  "rdfs:range": ["mk:OnboardingContext", "mk:LearningContextIntegration"]
}
```

**카디널리티**:
- 최소: 1 (최소 1개 Context 참조 필수)
- 최대: 무제한

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 최소 개수 | 설명 |
|-----------|-----------|----------|----------|------|
| `mk:FirstClassDecisionModel` | `mk:OnboardingContext` | 필수 | 1 | OnboardingContext 반드시 참조 |
| `mk:FirstClassDecisionModel` | `mk:LearningContextIntegration` | 필수 | 1 | LearningContextIntegration 반드시 참조 |
| `mk:FirstClassExecutionPlan` | `mk:OnboardingContext` | 선택 | 0 | 간접 참조 (parent를 통해) |
| `mk:FirstClassExecutionPlan` | `mk:LearningContextIntegration` | 선택 | 0 | 간접 참조 (parent를 통해) |

**제약 조건**:
- 최소 2개: `mk:FirstClassDecisionModel`은 반드시 2개 이상의 Context를 참조해야 함
- 타입 제한: Context Layer 노드만 참조 가능
- 존재성: 참조하는 Context 노드는 반드시 존재해야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk:FirstClassDecisionModel/instance_001",
  "@type": "mk:FirstClassDecisionModel",
  "mk:usesContext": [
    "mk:OnboardingContext/instance_001",
    "mk:LearningContextIntegration/instance_001"
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
  "rdfs:domain": "mk:FirstClassExecutionPlan",
  "rdfs:range": "mk:FirstClassDecisionModel"
}
```

**카디널리티**:
- 최소: 1 (필수)
- 최대: 1 (단일 Decision만 참조)

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 설명 |
|-----------|-----------|----------|------|
| `mk:FirstClassExecutionPlan` | `mk:FirstClassDecisionModel` | 필수 | 실행 계획이 참조하는 결정 모델 |

**제약 조건**:
- 단일 참조: 하나의 ExecutionPlan은 하나의 Decision만 참조
- 타입 제한: Decision Layer 노드만 참조 가능
- 존재성: 참조하는 Decision 노드는 반드시 존재해야 함
- 일관성: `referencesDecision`과 `hasParent`가 같은 노드를 가리켜야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk:FirstClassExecutionPlan/instance_001",
  "@type": "mk:FirstClassExecutionPlan",
  "mk:referencesDecision": "mk:FirstClassDecisionModel/instance_001",
  "mk:hasParent": "mk:FirstClassDecisionModel/instance_001"
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
  "rdfs:domain": ["mk:FirstClassDecisionModel", "mk:FirstClassExecutionPlan"],
  "rdfs:range": "owl:Thing"
}
```

**카디널리티**:
- 최소: 1 (최소 1개 데이터 소스 필수)
- 최대: 무제한

**사용 위치**:

| 소스 클래스 | 대상 클래스 | 필수 여부 | 설명 |
|-----------|-----------|----------|------|
| `mk:FirstClassDecisionModel` | `mk:OnboardingContext` | 필수 | 데이터 소스 |
| `mk:FirstClassDecisionModel` | `mk:LearningContextIntegration` | 필수 | 데이터 소스 |
| `mk:FirstClassExecutionPlan` | `mk:FirstClassDecisionModel` | 필수 | 데이터 소스 (parent) |

**제약 조건**:
- 타입 제한: 온톨로지 인스턴스 또는 속성 경로만 참조 가능
- 존재성: 참조하는 데이터 소스는 반드시 존재해야 함

**JSON-LD 표현**:
```json
{
  "@id": "mk:FirstClassDecisionModel/instance_001",
  "@type": "mk:FirstClassDecisionModel",
  "mk:hasDataSources": [
    "mk:OnboardingContext/instance_001",
    "mk:LearningContextIntegration/instance_001",
    "rules:S0_R1",
    "rules:S0_R5"
  ]
}
```

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
| `mk:OnboardingContext` | `"Context"` | 필수 |
| `mk:LearningContextIntegration` | `"Context"` | 필수 |
| `mk:FirstClassDecisionModel` | `"Decision"` | 필수 |
| `mk:FirstClassExecutionPlan` | `"Execution"` | 필수 |

**제약 조건**:
- 값 제한: 위 3개 값 중 하나만 허용
- 불변성: 노드 생성 후 stage 값은 변경 불가

---

### 5.2 hasIntent (의도)

**관계 정의**:
```json
{
  "@id": "mk:hasIntent",
  "@type": "owl:DatatypeProperty",
  "rdfs:label": "의도를 가짐",
  "rdfs:comment": "노드의 의도를 표현하는 메타데이터 관계",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "xsd:string"
}
```

**카디널리티**:
- 최소: 0 (선택)
- 최대: 1

**사용 위치**: 모든 온톨로지 노드 (선택)

---

### 5.3 hasIdentity (정체성)

**관계 정의**:
```json
{
  "@id": "mk:hasIdentity",
  "@type": "owl:DatatypeProperty",
  "rdfs:label": "정체성을 가짐",
  "rdfs:comment": "노드의 정체성을 표현하는 메타데이터 관계",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "xsd:string"
}
```

**카디널리티**:
- 최소: 0 (선택)
- 최대: 1

**사용 위치**: 모든 온톨로지 노드 (선택)

---

### 5.4 hasPurpose (목적)

**관계 정의**:
```json
{
  "@id": "mk:hasPurpose",
  "@type": "owl:DatatypeProperty",
  "rdfs:label": "목적을 가짐",
  "rdfs:comment": "노드의 목적을 표현하는 메타데이터 관계",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "xsd:string"
}
```

**카디널리티**:
- 최소: 0 (선택)
- 최대: 1

**사용 위치**: 모든 온톨로지 노드 (선택)

---

### 5.5 hasContext (맥락)

**관계 정의**:
```json
{
  "@id": "mk:hasContext",
  "@type": "owl:DatatypeProperty",
  "rdfs:label": "맥락을 가짐",
  "rdfs:comment": "노드의 맥락을 표현하는 메타데이터 관계",
  "rdfs:domain": "owl:Thing",
  "rdfs:range": "xsd:string"
}
```

**카디널리티**:
- 최소: 0 (선택)
- 최대: 1

**사용 위치**: 모든 온톨로지 노드 (선택)

---

## 6. 관계 다이어그램

### 6.1 전체 관계 구조

```
┌─────────────────────────┐
│  OnboardingContext      │
│  (Context Layer)         │
│                         │
│  hasStage: "Context"     │
└───────────┬─────────────┘
            │
            ├───hasParent───┐
            │               │
            ▼               ▼
┌──────────────────────┐  ┌──────────────────────────┐
│ LearningContext      │  │ FirstClassDecisionModel  │
│ Integration          │  │ (Decision Layer)        │
│ (Context Layer)      │  │                          │
│                      │  │ hasStage: "Decision"     │
│ hasParent: OC        │  │ hasParent: OC            │
│                      │  │ usesContext: [OC, LCI]   │
└──────────────────────┘  └───────────┬──────────────┘
                                       │
                                       ├───hasParent───┐
                                       │               │
                                       ▼               │
                            ┌──────────────────────────┐
                            │ FirstClassExecutionPlan  │
                            │ (Execution Layer)        │
                            │                          │
                            │ hasStage: "Execution"     │
                            │ hasParent: DCM           │
                            │ referencesDecision: DCM  │
                            └──────────────────────────┘

OC = OnboardingContext
LCI = LearningContextIntegration
DCM = FirstClassDecisionModel
```

### 6.2 관계 카디널리티 요약

| 관계 | 소스 클래스 | 대상 클래스 | 최소 | 최대 | 필수 여부 |
|------|-----------|-----------|------|------|----------|
| `hasParent` | `LearningContextIntegration` | `OnboardingContext` | 1 | 1 | 필수 |
| `hasParent` | `FirstClassDecisionModel` | `OnboardingContext` | 1 | 1 | 필수 |
| `hasParent` | `FirstClassExecutionPlan` | `FirstClassDecisionModel` | 1 | 1 | 필수 |
| `hasChild` | `OnboardingContext` | `LearningContextIntegration` | 0 | * | 선택 |
| `hasChild` | `OnboardingContext` | `FirstClassDecisionModel` | 0 | * | 선택 |
| `hasChild` | `FirstClassDecisionModel` | `FirstClassExecutionPlan` | 0 | 1 | 선택 |
| `usesContext` | `FirstClassDecisionModel` | `OnboardingContext` | 1 | 1 | 필수 |
| `usesContext` | `FirstClassDecisionModel` | `LearningContextIntegration` | 1 | 1 | 필수 |
| `referencesDecision` | `FirstClassExecutionPlan` | `FirstClassDecisionModel` | 1 | 1 | 필수 |
| `hasDataSources` | `FirstClassDecisionModel` | `OnboardingContext` | 1 | * | 필수 |
| `hasDataSources` | `FirstClassDecisionModel` | `LearningContextIntegration` | 1 | * | 필수 |
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

## 8. 관계 확장 가이드

### 8.1 새로운 관계 추가

새로운 관계를 추가할 때는 다음 구조를 따릅니다:

```json
{
  "@id": "mk:newRelation",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "새 관계",
  "rdfs:comment": "설명",
  "rdfs:domain": "소스클래스",
  "rdfs:range": "대상클래스",
  "owl:cardinality": 최대개수,
  "owl:minCardinality": 최소개수
}
```

### 8.2 관계 버전 관리

관계 정의가 변경될 때는:
1. 버전 번호 증가
2. 변경 이력 기록
3. 하위 호환성 확인
4. 기존 인스턴스 마이그레이션 계획 수립

---

## 9. GraphDB 연동

### 9.1 Neo4j 관계 매핑

| 온톨로지 관계 | Neo4j 관계 타입 | 방향성 |
|-------------|---------------|--------|
| `hasParent` | `HAS_PARENT` | 단방향 (자식 → 부모) |
| `hasChild` | `HAS_CHILD` | 단방향 (부모 → 자식) |
| `usesContext` | `USES_CONTEXT` | 단방향 (사용자 → Context) |
| `referencesDecision` | `REFERENCES_DECISION` | 단방향 (Execution → Decision) |
| `hasDataSources` | `HAS_DATA_SOURCE` | 단방향 (노드 → 소스) |

### 9.2 Cypher 쿼리 예시

**모든 Context를 참조하는 Decision 찾기**:
```cypher
MATCH (d:FirstClassDecisionModel)-[:USES_CONTEXT]->(c)
RETURN d, collect(c) as contexts
```

**특정 OnboardingContext의 모든 자식 찾기**:
```cypher
MATCH (oc:OnboardingContext {id: $contextId})<-[:HAS_PARENT]-(child)
RETURN child
```

---

## 10. 참고 문서

- **01_ONTOLOGY_SPEC.md**: 클래스 및 속성 정의
- **02_ONTOLOGY_TYPES.md**: 타입 정의
- **04_ONTOLOGY_CONSTRAINTS.md**: 무결성 제약 조건
- **05_ONTOLOGY_CONTEXT_TREE.md**: 계층 구조 정의
- **06_JSONLD_MAPPING.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**다음 문서**: `04_ontology_constraints.md`

