# Agent04 온톨로지 JSON-LD 매핑 명세서

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints

---

## 1. 네임스페이스 및 Prefix

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a04": "https://mathking.kr/ontology/agent04/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  }
}
```

---

## 2. 기본 매핑 규칙

| DSL 구조 | JSON-LD 구조 |
|---------|-------------|
| `node "ID" { class: "mk-a04:Class" }` | `{ "@id": "mk-a04:Class/ID", "@type": "mk-a04:Class" }` |
| `property: "value"` | `"mk-a04:property": "value"` |
| `property: ["value1", "value2"]` | `"mk-a04:property": ["value1", "value2"]` |
| `metadata { stage: "Context" }` | `"mk:hasStage": "Context"` |
| `parent: "ParentID"` | `"mk:hasParent": "mk-a04:Class/ParentID"` |
| `usesContext: ["ID1", "ID2"]` | `"mk:usesContext": ["mk-a04:Class/ID1", "mk-a04:Class/ID2"]` |

---

## 3. 완전한 JSON-LD 문서 예시

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a04": "https://mathking.kr/ontology/agent04/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  },
  "@graph": [
    {
      "@id": "mk-a04:WeakpointDetectionContext/instance_001",
      "@type": "mk-a04:WeakpointDetectionContext",
      "mk:hasStage": "Context",
      "mk-a04:hasStudentId": 12345,
      "mk-a04:hasActivityType": "mk-a04:ConceptUnderstanding",
      "mk-a04:hasActivityCategory": "개념이해",
      "mk-a04:hasWeakpointSeverity": "mk-a04:High"
    },
    {
      "@id": "mk-a04:ActivityAnalysisContext/instance_001",
      "@type": "mk-a04:ActivityAnalysisContext",
      "mk:hasStage": "Context",
      "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001",
      "mk-a04:hasPauseFrequency": 5,
      "mk-a04:hasAttentionScore": 0.6
    },
    {
      "@id": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
      "@type": "mk-a04:WeakpointAnalysisDecisionModel",
      "mk:hasStage": "Decision",
      "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001",
      "mk:usesContext": [
        "mk-a04:WeakpointDetectionContext/instance_001",
        "mk-a04:ActivityAnalysisContext/instance_001"
      ],
      "mk-a04:hasReinforcementStrategy": "mk-a04:ConceptClarificationStrategy",
      "mk-a04:hasReinforcementPriority": "mk-a04:High"
    },
    {
      "@id": "mk-a04:ReinforcementPlanExecutionPlan/instance_001",
      "@type": "mk-a04:ReinforcementPlanExecutionPlan",
      "mk:hasStage": "Execution",
      "mk:hasParent": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
      "mk:referencesDecision": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
      "mk-a04:hasAction": [
        "개념 비교 콘텐츠 제공",
        "예제 중심 학습 자료 제시"
      ]
    }
  ]
}
```

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27

