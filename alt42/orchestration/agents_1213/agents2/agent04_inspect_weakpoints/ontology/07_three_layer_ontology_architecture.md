# Agent04 온톨로지 3-Layer 아키텍처

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints

---

## 1. 개요

Agent04 온톨로지는 **3개의 온톨로지 계층**을 가집니다:

```
① Agent Core Ontology      ← 모든 Task 공통 (변하지 않음)
② Task Core Ontology       ← 특정 Task 공통 (잘 안 변함)
③ Task Module Ontology     ← 세부 기능 단위 (자주 바뀌어도 안전)
```

---

## 2. 계층 구조

### 2.1 Agent Core Ontology

**역할**: 모든 Task가 공유하는 구조 통일

**포함 요소**:
- 메타데이터 스키마: `mk:hasStage`, `mk:hasIntent`, `mk:hasIdentity`, `mk:hasPurpose`, `mk:hasContext`
- 공통 관계: `mk:hasParent`, `mk:usesContext`, `mk:referencesDecision`
- 기본 제약 조건

### 2.2 Task Core Ontology

**역할**: 취약점 분석 Task 내부 모든 모듈이 공통으로 사용하는 추상적 구조 제공

**예시**:
```json
{
  "@id": "mk-a04-task:WeakpointTaskCore",
  "@type": "owl:Class",
  "rdfs:label": "취약점 분석 Task 공통 구조",
  "mk-a04-task:baseClasses": [
    "mk-a04-task:WeakpointContextBase",
    "mk-a04-task:AnalysisDecisionBase",
    "mk-a04-task:ReinforcementExecutionBase"
  ]
}
```

### 2.3 Task Module Ontology

**역할**: Task의 세부 기능을 독립 스키마로 구성

**예시**:
```
weakpoint/
 ├── detection_module.jsonld      ← 취약점 탐지 모듈
 ├── analysis_module.jsonld        ← 활동 분석 모듈
 ├── reinforcement_module.jsonld   ← 보강 방안 모듈
 └── execution_module.jsonld      ← 실행 계획 모듈
```

---

## 3. 확장 전략

**Step 1**: Agent Core는 절대 수정하지 않는다

**Step 2**: 새로운 Task가 생기면 Task Core를 만든다

**Step 3**: 해당 Task 안에서 모듈 생성

**Step 4**: 각 Module은 완전 독립적인 JSON-LD 스키마

**Step 5**: Gateway에서 Core Type만 바라보면 통신 안정성 확보

---

## 4. Agent04 적용 예시

### 4.1 Agent Core Ontology

공통 메타데이터 및 관계 정의 (모든 Agent 공통)

### 4.2 Task Core Ontology

**파일**: `agent04/ontology/task_core/weakpoint_task_core.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a04-core": "https://mathking.kr/ontology/agent04/core/",
    "mk-a04-task": "https://mathking.kr/ontology/agent04/task/"
  },
  "@graph": [
    {
      "@id": "mk-a04-task:WeakpointContextBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a04-core:ContextBase",
      "rdfs:label": "취약점 Context 기본 구조"
    },
    {
      "@id": "mk-a04-task:WeakpointDecisionBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a04-core:DecisionBase",
      "rdfs:label": "취약점 Decision 기본 구조"
    }
  ]
}
```

### 4.3 Task Module Ontology

**파일**: `agent04/ontology/modules/weakpoint/detection_module.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a04-core": "https://mathking.kr/ontology/agent04/core/",
    "mk-a04-task": "https://mathking.kr/ontology/agent04/task/",
    "mk-a04-mod": "https://mathking.kr/ontology/agent04/modules/"
  },
  "@graph": [
    {
      "@id": "mk-a04-mod:WeakpointDetectionProfile",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a04-task:WeakpointContextBase",
      "rdfs:label": "취약점 탐지 프로필 모듈"
    }
  ]
}
```

---

## 5. 참고 문서

- **01_ontology_specification.md**: 클래스 및 속성 정의
- **principles.md**: 전체 온톨로지 구축 전략

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27

