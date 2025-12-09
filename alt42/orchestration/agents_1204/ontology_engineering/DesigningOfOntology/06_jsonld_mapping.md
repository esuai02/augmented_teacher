# Agent01 온톨로지 JSON-LD 매핑 명세서 (JSON-LD Mapping Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: DSL → JSON-LD 변환 규칙 정의

---

## 1. 문서 범위

이 문서는 **DSL(도메인 특화 언어)을 JSON-LD로 변환하는 규칙**을 정의합니다.

**포함 내용**:
- DSL → JSON-LD 매핑 테이블
- Prefix 관리 규칙
- ID 생성 규칙
- Array/List 매핑 방식
- 관계 매핑 규칙
- 샘플 JSON-LD 문서

**제외 내용**:
- DSL 문법 정의 (별도 문서)
- JSON-LD Generator 구현 (별도 문서)

---

## 2. 네임스페이스 및 Prefix

### 2.1 기본 Prefix 정의

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  }
}
```

### 2.2 Prefix 사용 규칙

| Prefix | 용도 | 예시 |
|--------|------|------|
| `mk:` | MathKing 온톨로지 | `mk:OnboardingContext` |
| `rdf:` | RDF 기본 타입 | `rdf:List`, `rdf:type` |
| `rdfs:` | RDFS 스키마 | `rdfs:label`, `rdfs:comment` |
| `owl:` | OWL 스키마 | `owl:Class`, `owl:ObjectProperty` |
| `xsd:` | XML Schema 데이터 타입 | `xsd:string`, `xsd:integer` |

---

## 3. ID 생성 규칙

### 3.1 인스턴스 ID 생성

**규칙**: `{클래스명}/{인스턴스_식별자}`

**예시**:
```
DSL: node "A01_OnboardingContext" { class: "mk:OnboardingContext" }
JSON-LD: "@id": "mk:OnboardingContext/A01_OnboardingContext"
```

**인스턴스 식별자 규칙**:
- 고유성 보장: 동일 클래스 내에서 중복 불가
- 형식: 알파벳, 숫자, 언더스코어만 허용
- 권장 형식: `{에이전트ID}_{클래스명}_{순번}` (예: `A01_OnboardingContext_001`)

### 3.2 클래스 ID 생성

**규칙**: `mk:{클래스명}`

**예시**:
```
DSL: class: "mk:OnboardingContext"
JSON-LD: "@type": "mk:OnboardingContext"
```

---

## 4. 기본 매핑 규칙

### 4.1 노드 매핑

| DSL 구조 | JSON-LD 구조 |
|---------|-------------|
| `node "ID" { class: "mk:Class" }` | `{ "@id": "mk:Class/ID", "@type": "mk:Class" }` |
| `metadata { stage: "Context" }` | `"mk:hasStage": "Context"` |
| `parent: "ParentID"` | `"mk:hasParent": "mk:Class/ParentID"` |
| `usesContext: ["ID1", "ID2"]` | `"mk:usesContext": ["mk:Class/ID1", "mk:Class/ID2"]` |

### 4.2 속성 매핑

| DSL 속성 | JSON-LD 속성 | 타입 |
|---------|-------------|------|
| `hasStudentGrade: "중2"` | `"mk:hasStudentGrade": "중2"` | `xsd:string` |
| `hasMathConfidence: 4` | `"mk:hasMathConfidence": 4` | `xsd:integer` |
| `hasTextbooks: ["책1", "책2"]` | `"mk:hasTextbooks": ["책1", "책2"]` | `rdf:List` |
| `hasDifficultyLevel: "mk:EasyToMedium"` | `"mk:hasDifficultyLevel": "mk:EasyToMedium"` | `mk:DifficultyLevel` |

### 4.3 메타데이터 매핑

| DSL 메타데이터 | JSON-LD 속성 | 타입 |
|--------------|------------|------|
| `metadata.stage` | `mk:hasStage` | `xsd:string` |
| `metadata.intent` | `mk:hasIntent` | `xsd:string` |
| `metadata.identity` | `mk:hasIdentity` | `xsd:string` |
| `metadata.purpose` | `mk:hasPurpose` | `xsd:string` |
| `metadata.context` | `mk:hasContext` | `xsd:string` |

---

## 5. 복합 타입 매핑

### 5.1 배열(Array) 매핑

**DSL**:
```dsl
hasTextbooks: ["개념원리 중2-1", "쎈 중2-1"]
```

**JSON-LD**:
```json
{
  "mk:hasTextbooks": ["개념원리 중2-1", "쎈 중2-1"]
}
```

**규칙**:
- 배열은 JSON 배열로 직접 매핑
- 빈 배열 허용
- 최소 요소 개수는 제약 조건 문서 참조

### 5.2 구조체(Struct) 매핑

**DSL** (Interpretation Layer의 candidate_problems):
```dsl
candidate_problems: [
  {
    id: "P1",
    description: "...",
    severity: "high"
  }
]
```

**JSON-LD**:
```json
{
  "mk:hasCandidateProblems": [
    {
      "@id": "mk:CandidateProblem/P1",
      "@type": "mk:CandidateProblem",
      "mk:hasProblemId": "P1",
      "mk:hasDescription": "...",
      "mk:hasSeverity": "mk:High"
    }
  ]
}
```

**규칙**:
- 구조체는 중첩된 JSON 객체로 매핑
- `@id`와 `@type` 자동 추가
- 속성은 `mk:` prefix 사용

---

## 6. 관계 매핑

### 6.1 단일 관계 매핑

**DSL**:
```dsl
parent: "A01_OnboardingContext"
```

**JSON-LD**:
```json
{
  "mk:hasParent": "mk:OnboardingContext/A01_OnboardingContext"
}
```

**규칙**:
- 관계는 상대 참조를 절대 URI로 변환
- 형식: `{클래스명}/{인스턴스ID}`

### 6.2 다중 관계 매핑

**DSL**:
```dsl
usesContext: ["A01_OnboardingContext", "A01_LearningContextIntegration"]
```

**JSON-LD**:
```json
{
  "mk:usesContext": [
    "mk:OnboardingContext/A01_OnboardingContext",
    "mk:LearningContextIntegration/A01_LearningContextIntegration"
  ]
}
```

**규칙**:
- 다중 관계는 JSON 배열로 매핑
- 각 요소는 절대 URI로 변환

### 6.3 null 관계 매핑

**DSL**:
```dsl
parent: null  // root 노드
```

**JSON-LD**:
```json
{
  "mk:hasParent": null
}
```

또는 속성 생략:
```json
{
  // hasParent 속성 없음
}
```

---

## 7. 열거형 타입 매핑

### 7.1 DifficultyLevel 매핑

**DSL**:
```dsl
difficulty_level: "mk:EasyToMedium"
```

**JSON-LD**:
```json
{
  "mk:hasDifficultyLevel": "mk:EasyToMedium"
}
```

**규칙**:
- 열거형 값은 문자열로 매핑
- `mk:` prefix 유지

### 7.2 AlignmentStrategy 매핑

**DSL**:
```dsl
alignment_strategy: "mk:BridgeStrategy"
```

**JSON-LD**:
```json
{
  "mk:hasAlignmentStrategy": "mk:BridgeStrategy"
}
```

---

## 8. 완전한 JSON-LD 문서 구조

### 8.1 기본 구조

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  },
  "@graph": [
    // 노드 인스턴스들
  ]
}
```

### 8.2 단일 노드 매핑 예시

**DSL**:
```dsl
node "A01_OnboardingContext" {
  metadata {
    stage: Context
    intent: "학생의 초기 수학 맥락을 구조화"
  }
  hasStudentGrade: "중2"
  hasSchool: "OO중학교"
  hasAcademy: "OO수학학원"
  hasMathConfidence: 4
  hasTextbooks: ["개념원리 중2-1", "쎈 중2-1"]
}
```

**JSON-LD**:
```json
{
  "@id": "mk:OnboardingContext/A01_OnboardingContext",
  "@type": "mk:OnboardingContext",
  "mk:hasStage": "Context",
  "mk:hasIntent": "학생의 초기 수학 맥락을 구조화",
  "mk:hasStudentGrade": "중2",
  "mk:hasSchool": "OO중학교",
  "mk:hasAcademy": "OO수학학원",
  "mk:hasMathConfidence": 4,
  "mk:hasTextbooks": ["개념원리 중2-1", "쎈 중2-1"]
}
```

---

## 9. 전체 온톨로지 인스턴스 매핑 예시

### 9.1 완전한 JSON-LD 문서

**DSL** (간소화):
```dsl
ontology {
  node "OC_001" {
    class: "mk:OnboardingContext"
    hasStudentGrade: "중2"
    hasSchool: "OO중학교"
  }
  
  node "LCI_001" {
    class: "mk:LearningContextIntegration"
    parent: "OC_001"
    hasConceptProgress: "중2-1 일차방정식까지"
  }
  
  node "DCM_001" {
    class: "mk:FirstClassDecisionModel"
    parent: "OC_001"
    usesContext: ["OC_001", "LCI_001"]
    hasDifficultyLevel: "mk:EasyToMedium"
  }
  
  node "ECP_001" {
    class: "mk:FirstClassExecutionPlan"
    parent: "DCM_001"
    referencesDecision: "DCM_001"
    hasAction: ["도입 루틴: ..."]
  }
}
```

**JSON-LD**:
```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  },
  "@graph": [
    {
      "@id": "mk:OnboardingContext/OC_001",
      "@type": "mk:OnboardingContext",
      "mk:hasStage": "Context",
      "mk:hasStudentGrade": "중2",
      "mk:hasSchool": "OO중학교"
    },
    {
      "@id": "mk:LearningContextIntegration/LCI_001",
      "@type": "mk:LearningContextIntegration",
      "mk:hasStage": "Context",
      "mk:hasParent": "mk:OnboardingContext/OC_001",
      "mk:hasConceptProgress": "중2-1 일차방정식까지"
    },
    {
      "@id": "mk:FirstClassDecisionModel/DCM_001",
      "@type": "mk:FirstClassDecisionModel",
      "mk:hasStage": "Decision",
      "mk:hasParent": "mk:OnboardingContext/OC_001",
      "mk:usesContext": [
        "mk:OnboardingContext/OC_001",
        "mk:LearningContextIntegration/LCI_001"
      ],
      "mk:hasDifficultyLevel": "mk:EasyToMedium"
    },
    {
      "@id": "mk:FirstClassExecutionPlan/ECP_001",
      "@type": "mk:FirstClassExecutionPlan",
      "mk:hasStage": "Execution",
      "mk:hasParent": "mk:FirstClassDecisionModel/DCM_001",
      "mk:referencesDecision": "mk:FirstClassDecisionModel/DCM_001",
      "mk:hasAction": ["도입 루틴: ..."]
    }
  ]
}
```

---

## 10. 매핑 규칙 상세

### 10.1 타입 추론 규칙

| DSL 값 타입 | JSON-LD 타입 | 변환 규칙 |
|-----------|-------------|----------|
| 문자열 | `xsd:string` | 그대로 매핑 |
| 정수 | `xsd:integer` | 그대로 매핑 |
| 불리언 | `xsd:boolean` | 그대로 매핑 |
| 배열 | `rdf:List` | JSON 배열로 매핑 |
| `mk:` prefix 값 | 해당 열거형 타입 | prefix 유지 |

### 10.2 관계 해석 규칙

| DSL 관계 | JSON-LD 변환 | 설명 |
|---------|-------------|------|
| `parent: "ID"` | `"mk:hasParent": "mk:Class/ID"` | 클래스명 추론 필요 |
| `usesContext: ["ID1", "ID2"]` | `"mk:usesContext": ["mk:Class1/ID1", "mk:Class2/ID2"]` | 각 ID의 클래스 추론 필요 |
| `referencesDecision: "ID"` | `"mk:referencesDecision": "mk:FirstClassDecisionModel/ID"` | 타입 고정 |

### 10.3 null 값 처리

| DSL null | JSON-LD 처리 | 설명 |
|---------|-------------|------|
| `parent: null` | 속성 생략 또는 `null` | root 노드 |
| `hasTextbooks: []` | `"mk:hasTextbooks": []` | 빈 배열 허용 |
| 선택 속성 미지정 | 속성 생략 | 필수 속성은 반드시 포함 |

---

## 11. 변환 알고리즘 (의사코드)

### 11.1 기본 변환 알고리즘

```
function convertDSLToJSONLD(dslDocument):
  context = createContext()
  graph = []
  
  for node in dslDocument.ontology.nodes:
    jsonNode = {
      "@id": generateID(node.id, node.class),
      "@type": node.class
    }
    
    // 메타데이터 변환
    if node.metadata:
      jsonNode = addMetadata(jsonNode, node.metadata)
    
    // 속성 변환
    for property in node.properties:
      jsonNode[property.name] = convertValue(property.value, property.type)
    
    // 관계 변환
    if node.parent:
      jsonNode["mk:hasParent"] = resolveReference(node.parent)
    
    if node.usesContext:
      jsonNode["mk:usesContext"] = [
        resolveReference(ctx) for ctx in node.usesContext
      ]
    
    graph.append(jsonNode)
  
  return {
    "@context": context,
    "@graph": graph
  }
```

### 11.2 ID 생성 알고리즘

```
function generateID(instanceId, className):
  // className에서 클래스명 추출 (예: "mk:OnboardingContext" → "OnboardingContext")
  classShortName = extractClassShortName(className)
  
  // ID 생성: {클래스명}/{인스턴스ID}
  return className + "/" + instanceId
```

### 11.3 참조 해석 알고리즘

```
function resolveReference(referenceId):
  // 참조 ID로부터 클래스 추론
  className = inferClassFromReference(referenceId)
  
  // 절대 URI 생성
  return className + "/" + referenceId
```

---

## 12. 에러 처리

### 12.1 일반적인 에러 케이스

| 에러 유형 | 원인 | 처리 방법 |
|---------|------|----------|
| 클래스명 누락 | `class` 속성 없음 | 에러 반환, 변환 중단 |
| ID 중복 | 동일 클래스 내 중복 ID | 에러 반환, 변환 중단 |
| 참조 해석 실패 | parent/usesContext의 클래스 추론 실패 | 경고 로그, null 처리 |
| 타입 불일치 | 값이 속성 타입과 불일치 | 에러 반환, 변환 중단 |
| 필수 속성 누락 | 필수 속성이 없음 | 에러 반환, 변환 중단 |

### 12.2 에러 처리 알고리즘

```
function validateAndConvert(dslDocument):
  errors = []
  warnings = []
  
  // 1. 기본 검증
  if not dslDocument.ontology:
    errors.append("Missing ontology block")
    return ConversionResult(null, errors, warnings)
  
  // 2. 노드 검증
  for node in dslDocument.ontology.nodes:
    if not node.class:
      errors.append("Node missing class: " + node.id)
      continue
    
    if not node.id:
      errors.append("Node missing id")
      continue
    
    // 필수 속성 검증
    requiredProps = getRequiredProperties(node.class)
    for prop in requiredProps:
      if prop not in node.properties:
        errors.append("Missing required property: " + prop)
  
  // 3. 관계 검증
  for node in dslDocument.ontology.nodes:
    if node.parent:
      parentNode = findNode(node.parent)
      if not parentNode:
        warnings.append("Parent node not found: " + node.parent)
  
  if errors:
    return ConversionResult(null, errors, warnings)
  
  // 4. 변환 수행
  jsonld = convertDSLToJSONLD(dslDocument)
  return ConversionResult(jsonld, errors, warnings)
```

---

## 13. 최적화 규칙

### 13.1 중복 제거

**규칙**: 동일한 값은 한 번만 저장

**예시**:
```json
// 최적화 전
{
  "@graph": [
    {"@id": "mk:OnboardingContext/OC_001", "mk:hasSchool": "OO중학교"},
    {"@id": "mk:LearningContextIntegration/LCI_001", "mk:hasSchool": "OO중학교"}
  ]
}

// 최적화 후 (공통 값 참조)
{
  "@graph": [
    {"@id": "mk:OnboardingContext/OC_001", "mk:hasSchool": "mk:School/OO중학교"},
    {"@id": "mk:LearningContextIntegration/LCI_001", "mk:usesSchool": "mk:School/OO중학교"}
  ]
}
```

### 13.2 압축 규칙

**규칙**: `@context`는 문서 시작 부분에 한 번만 정의

**규칙**: 반복되는 prefix는 `@context`에 정의

---

## 14. GraphDB 연동

### 14.1 Neo4j 임포트 형식

**JSON-LD → Neo4j Cypher 변환**:

```cypher
// JSON-LD의 각 노드를 Neo4j 노드로 생성
CREATE (oc:OnboardingContext {
  id: "OC_001",
  studentGrade: "중2",
  school: "OO중학교"
})

// 관계 생성
MATCH (oc:OnboardingContext {id: "OC_001"})
MATCH (lci:LearningContextIntegration {id: "LCI_001"})
CREATE (lci)-[:HAS_PARENT]->(oc)
```

### 14.2 변환 스크립트 예시

```python
def jsonld_to_cypher(jsonld_doc):
    cypher_queries = []
    
    for node in jsonld_doc["@graph"]:
        node_id = node["@id"].split("/")[-1]
        node_type = node["@type"].split(":")[-1]
        
        # 노드 생성 쿼리
        props = {k: v for k, v in node.items() 
                 if k not in ["@id", "@type"]}
        props_str = ", ".join([f"{k}: ${k}" for k in props.keys()])
        
        query = f"CREATE (n:{node_type} {{id: '{node_id}', {props_str}}})"
        cypher_queries.append(query)
    
    # 관계 생성 쿼리
    for node in jsonld_doc["@graph"]:
        if "mk:hasParent" in node:
            parent_id = node["mk:hasParent"].split("/")[-1]
            node_id = node["@id"].split("/")[-1]
            node_type = node["@type"].split(":")[-1]
            
            query = f"""
            MATCH (child:{node_type} {{id: '{node_id}'}})
            MATCH (parent {{id: '{parent_id}'}})
            CREATE (child)-[:HAS_PARENT]->(parent)
            """
            cypher_queries.append(query)
    
    return cypher_queries
```

---

## 15. 매핑 테스트 케이스

### 15.1 기본 매핑 테스트

**입력 (DSL)**:
```dsl
node "test_001" {
  class: "mk:OnboardingContext"
  hasStudentGrade: "중2"
}
```

**예상 출력 (JSON-LD)**:
```json
{
  "@id": "mk:OnboardingContext/test_001",
  "@type": "mk:OnboardingContext",
  "mk:hasStudentGrade": "중2"
}
```

### 15.2 관계 매핑 테스트

**입력 (DSL)**:
```dsl
node "child_001" {
  class: "mk:LearningContextIntegration"
  parent: "parent_001"
}
```

**예상 출력 (JSON-LD)**:
```json
{
  "@id": "mk:LearningContextIntegration/child_001",
  "@type": "mk:LearningContextIntegration",
  "mk:hasParent": "mk:OnboardingContext/parent_001"
}
```

---

## 16. 참고 문서

- **01_ONTOLOGY_SPEC.md**: 클래스 및 속성 정의
- **02_ONTOLOGY_TYPES.md**: 타입 정의
- **03_ONTOLOGY_RELATIONS.md**: 관계 정의
- **04_ONTOLOGY_CONSTRAINTS.md**: 제약 조건 정의
- **05_ONTOLOGY_CONTEXT_TREE.md**: 계층 구조 정의
- **07_JSONLD_GENERATOR_DESIGN.md**: 변환기 설계 문서 (예정)

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**다음 문서**: `07_jsonld_generator_design.md` (예정)

