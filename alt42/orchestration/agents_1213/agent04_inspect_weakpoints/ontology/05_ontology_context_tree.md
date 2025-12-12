# Agent04 온톨로지 계층 구조 명세서 (Ontology Context Tree Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints  
**목적**: 온톨로지 계층 구조 및 트리 구조 정의

---

## 1. 문서 범위

이 문서는 **Agent04 온톨로지의 계층 구조 및 트리 구조**를 정의합니다.

**포함 내용**:
- Context/Decision/Execution 계층 구조
- 트리 다이어그램
- 각 계층 간 책임 및 데이터 흐름
- 계층 간 데이터 전달 규칙

**제외 내용**:
- 클래스 정의 (01_ontology_specification.md 참조)
- 관계 정의 (03_ontology_relations.md 참조)
- 제약 조건 (04_ontology_constraints.md 참조)

---

## 2. 계층 구조 개요

Agent04 온톨로지는 **3단계 계층 구조**를 가집니다:

```
[1] Context Layer (맥락 레이어)
    └── 취약점 탐지 및 활동 분석 데이터 수집

[2] Decision Layer (결정 레이어)
    └── 취약점 분석 및 보강 방안 결정

[3] Execution Layer (실행 레이어)
    └── 보강 계획 실행 수립
```

---

## 3. Context Layer (맥락 레이어)

### 3.1 계층 정의

**역할**: 학습 활동에서 탐지된 취약점 및 활동 분석 데이터를 수집 및 저장

**특징**:
- 순수 데이터만 저장 (의미 해석 없음)
- 원시 데이터(raw data) 중심
- 정책/의지/추론 제외

**노드 클래스**:
- `mk-a04:WeakpointDetectionContext`
- `mk-a04:ActivityAnalysisContext`

### 3.2 WeakpointDetectionContext

**위치**: Context Layer의 루트 노드

**책임**:
- 학생의 학습 활동 정보 저장
- 취약점 탐지 기본 정보 저장
- 활동 유형 및 카테고리 저장
- 취약점 심각도 저장
- 성능 지표 저장

**데이터 흐름**:
```
학습 활동 수행
    ↓
rules.yaml (CU_A1, PS_A1 등)
    ↓
WeakpointDetectionContext 인스턴스 생성
```

**트리 위치**:
```
WeakpointDetectionContext (root)
├── hasParent: null
└── hasChild: [ActivityAnalysisContext, WeakpointAnalysisDecisionModel]
```

### 3.3 ActivityAnalysisContext

**위치**: Context Layer의 자식 노드

**책임**:
- 활동 단계별 상세 분석 결과 저장
- 멈춤 패턴 정보 저장
- 주의집중도 및 몰입도 점수 저장
- 개념 혼동 유형 저장
- 학습 방법 적합도 점수 저장

**데이터 흐름**:
```
활동 상세 분석 데이터
    ↓
rules.yaml (CU_A2, CU_A3 등)
    ↓
ActivityAnalysisContext 인스턴스 생성
    ↓
parent: WeakpointDetectionContext
```

**트리 위치**:
```
WeakpointDetectionContext
└── ActivityAnalysisContext
    ├── hasParent: WeakpointDetectionContext
    └── hasChild: []
```

---

## 4. Decision Layer (결정 레이어)

### 4.1 계층 정의

**역할**: Context 데이터를 기반으로 취약점 분석 및 보강 방안 결정

**특징**:
- Context Layer의 데이터를 조합하여 결정
- 취약점 근본 원인 분석
- 보강 전략 선택
- 우선순위 결정

**노드 클래스**:
- `mk-a04:WeakpointAnalysisDecisionModel`

### 4.2 WeakpointAnalysisDecisionModel

**위치**: Decision Layer의 단일 노드

**책임**:
- 취약점 설명 및 근본 원인 분석
- 보강 전략 결정
- 권장 방법 및 콘텐츠 결정
- 개입 유형 결정
- 우선순위 결정

**데이터 흐름**:
```
WeakpointDetectionContext + ActivityAnalysisContext
    ↓
취약점 분석 로직
    ↓
WeakpointAnalysisDecisionModel 인스턴스 생성
    ↓
보강 전략/방법/콘텐츠 결정
```

**트리 위치**:
```
WeakpointDetectionContext
└── WeakpointAnalysisDecisionModel
    ├── hasParent: WeakpointDetectionContext
    ├── usesContext: [WeakpointDetectionContext, ActivityAnalysisContext]
    └── hasChild: [ReinforcementPlanExecutionPlan]
```

**의존성**:
- 반드시 `WeakpointDetectionContext`와 `ActivityAnalysisContext`를 모두 참조해야 함
- `usesContext`를 통해 두 Context를 모두 참조

---

## 5. Execution Layer (실행 레이어)

### 5.1 계층 정의

**역할**: Decision의 결정을 실행 가능한 보강 계획으로 변환

**특징**:
- Decision Layer의 결정을 구체적 행동으로 분해
- 실행 가능한 단계로 변환
- 측정 및 피드백 계획 수립

**노드 클래스**:
- `mk-a04:ReinforcementPlanExecutionPlan`

### 5.2 ReinforcementPlanExecutionPlan

**위치**: Execution Layer의 단일 노드

**책임**:
- Decision의 결정을 실행 행동(action)으로 변환
- 측정 방법(measurement) 정의
- 피드백 수집 계획 정의
- 조정 계획(adjustment) 정의

**데이터 흐름**:
```
WeakpointAnalysisDecisionModel
    ↓
ReinforcementPlanExecutionPlan 인스턴스 생성
    ↓
action/measurement/feedback/adjustment 정의
```

**트리 위치**:
```
WeakpointDetectionContext
└── WeakpointAnalysisDecisionModel
    └── ReinforcementPlanExecutionPlan
        ├── hasParent: WeakpointAnalysisDecisionModel
        ├── referencesDecision: WeakpointAnalysisDecisionModel
        └── hasChild: []
```

**의존성**:
- 반드시 `WeakpointAnalysisDecisionModel`을 parent로 가져야 함
- `referencesDecision`과 `hasParent`는 같은 노드를 참조해야 함

---

## 6. 전체 트리 구조

### 6.1 완전한 트리 다이어그램

```
                    ┌─────────────────────────────┐
                    │  WeakpointDetectionContext │
                    │  (Context Layer)            │
                    │                            │
                    │  Stage: "Context"          │
                    │  Parent: null (root)       │
                    └────────────┬───────────────┘
                                 │
                    ┌────────────┴───────────────┐
                    │                            │
                    ▼                            ▼
        ┌──────────────────────┐    ┌──────────────────────────┐
        │ ActivityAnalysis     │    │ WeakpointAnalysisDecision│
        │ Context              │    │ Model (Decision Layer)   │
        │ (Context Layer)      │    │                         │
        │                      │    │ Stage: "Decision"       │
        │ Stage: "Context"     │    │ Parent: WDC             │
        │ Parent: WDC          │    │ usesContext: [WDC, AAC] │
        └──────────────────────┘    └───────────┬──────────────┘
                                                 │
                                                 ▼
                                    ┌──────────────────────────┐
                                    │ ReinforcementPlanExecution│
                                    │ Plan (Execution Layer)  │
                                    │                         │
                                    │ Stage: "Execution"      │
                                    │ Parent: WADM            │
                                    │ referencesDecision: WADM│
                                    └──────────────────────────┘

WDC = WeakpointDetectionContext
AAC = ActivityAnalysisContext
WADM = WeakpointAnalysisDecisionModel
```

### 6.2 트리 노드 요약

| 노드 ID | 클래스 | Stage | Parent | Children | Context 참조 |
|--------|--------|-------|--------|----------|-------------|
| WDC | WeakpointDetectionContext | Context | null | AAC, WADM | - |
| AAC | ActivityAnalysisContext | Context | WDC | - | - |
| WADM | WeakpointAnalysisDecisionModel | Decision | WDC | REP | WDC, AAC |
| REP | ReinforcementPlanExecutionPlan | Execution | WADM | - | - (간접) |

---

## 7. 계층 간 책임 분리

### 7.1 Context Layer 책임

**단일 책임**: 취약점 탐지 및 활동 분석 데이터 수집 및 저장

**포함**:
- 원시 데이터(raw data) 저장
- 활동 유형 및 카테고리 저장
- 성능 지표 저장
- 활동 상세 분석 결과 저장

**제외**:
- 의미 해석
- 취약점 분석
- 보강 방안 결정
- 실행 계획 수립

### 7.2 Decision Layer 책임

**단일 책임**: 취약점 분석 및 보강 방안 결정

**포함**:
- 취약점 설명 및 근본 원인 분석
- 보강 전략 선택
- 권장 방법 및 콘텐츠 결정
- 우선순위 결정

**제외**:
- 데이터 수집
- 실행 계획 수립
- 실제 보강 실행

### 7.3 Execution Layer 책임

**단일 책임**: 보강 계획 실행 수립

**포함**:
- 행동(action) 정의
- 측정 방법 정의
- 피드백 계획 정의
- 조정 계획 정의

**제외**:
- 데이터 수집
- 취약점 분석
- 보강 방안 결정
- 실제 실행 (이는 시스템 외부에서 수행)

---

## 8. 계층 간 데이터 흐름

### 8.1 데이터 흐름 다이어그램

```
[학습 활동 수행]
    ↓
┌─────────────────────────────────────┐
│  Context Layer                      │
│  - WeakpointDetectionContext        │
│  - ActivityAnalysisContext          │
│  (순수 데이터 저장)                  │
└──────────────┬──────────────────────┘
               │
               │ 데이터 전달
               ▼
┌─────────────────────────────────────┐
│  Decision Layer                     │
│  - WeakpointAnalysisDecisionModel   │
│  (취약점 분석 및 보강 방안 결정)      │
└──────────────┬──────────────────────┘
               │
               │ 결정 전달
               ▼
┌─────────────────────────────────────┐
│  Execution Layer                    │
│  - ReinforcementPlanExecutionPlan  │
│  (보강 계획 실행 수립)                │
└─────────────────────────────────────┘
```

### 8.2 데이터 전달 규칙

**Context → Decision**:
- `usesContext` 관계를 통해 전달
- 최소 2개 Context 참조 필수
- WeakpointDetectionContext와 ActivityAnalysisContext 모두 참조

**Decision → Execution**:
- `hasParent` 및 `referencesDecision` 관계를 통해 전달
- 단일 Decision만 참조
- Decision의 모든 결정사항을 실행 계획으로 변환

---

## 9. 트리 탐색 규칙

### 9.1 상향 탐색 (Bottom-Up)

**규칙**: 자식 노드에서 부모 노드로 탐색

**예시**:
```
ReinforcementPlanExecutionPlan
    → hasParent → WeakpointAnalysisDecisionModel
        → hasParent → WeakpointDetectionContext
            → hasParent → null (root)
```

**사용 사례**:
- ExecutionPlan에서 Context 데이터 접근
- 계층 구조 검증
- 부모 노드의 속성 참조

### 9.2 하향 탐색 (Top-Down)

**규칙**: 부모 노드에서 자식 노드로 탐색

**예시**:
```
WeakpointDetectionContext
    → hasChild → [ActivityAnalysisContext, WeakpointAnalysisDecisionModel]
        → WeakpointAnalysisDecisionModel.hasChild → [ReinforcementPlanExecutionPlan]
```

**사용 사례**:
- Context에서 모든 하위 노드 찾기
- 전체 트리 구조 파악
- 계층 구조 시각화

### 9.3 횡단 탐색 (Lateral)

**규칙**: 같은 계층 내 노드 간 탐색

**예시**:
```
WeakpointDetectionContext
    → usesContext (via WeakpointAnalysisDecisionModel) → ActivityAnalysisContext
```

**사용 사례**:
- DecisionModel에서 참조하는 모든 Context 찾기
- 같은 계층의 관련 노드 찾기

---

## 10. 트리 구조 확장 가이드

### 10.1 새로운 계층 추가

새로운 계층을 추가할 때는:

1. **Stage 값 정의**: 새로운 Stage 값 추가 (예: "Evaluation")
2. **노드 클래스 정의**: 새로운 클래스 생성
3. **계층 관계 정의**: parent-child 관계 설정
4. **제약 조건 정의**: 계층 간 제약 조건 추가

### 10.2 기존 계층 확장

기존 계층에 노드를 추가할 때는:

1. **클래스 정의**: 01_ontology_specification.md에 클래스 추가
2. **관계 정의**: 03_ontology_relations.md에 관계 추가
3. **트리 구조 업데이트**: 이 문서에 트리 구조 반영

---

## 11. 트리 구조 검증 규칙

### 11.1 계층 구조 검증

**규칙**: 각 노드의 stage는 부모 노드의 stage보다 낮을 수 없음

**검증 알고리즘**:
```
function validateStageHierarchy(node):
  if node.hasParent == null:
    return true  // root 노드
  
  parent = getOntologyInstance(node.hasParent)
  stageHierarchy = ["Context", "Decision", "Execution"]
  
  parentIndex = stageHierarchy.index(parent.hasStage)
  nodeIndex = stageHierarchy.index(node.hasStage)
  
  if nodeIndex <= parentIndex:
    return ValidationError("Child stage must be later than parent")
  
  return true
```

### 11.2 트리 구조 무결성 검증

**규칙**: 
- 모든 노드는 단일 root를 가져야 함
- 순환 참조가 없어야 함
- 모든 관계가 일관되어야 함

**검증 알고리즘**:
```
function validateTreeIntegrity(allNodes):
  roots = [n for n in allNodes if n.hasParent == null]
  
  if len(roots) != 1:
    return ValidationError("Tree must have exactly one root")
  
  // 순환 참조 검증
  for node in allNodes:
    if hasCircularReference(node):
      return ValidationError("Circular reference detected")
  
  // 관계 일관성 검증
  for node in allNodes:
    if not validateInverseConsistency(node):
      return ValidationError("Inverse relation inconsistency")
  
  return true
```

---

## 12. GraphDB 트리 구조 표현

### 12.1 Neo4j 트리 구조

**노드 레이블**:
- `:WeakpointDetectionContext`
- `:ActivityAnalysisContext`
- `:WeakpointAnalysisDecisionModel`
- `:ReinforcementPlanExecutionPlan`

**관계 타입**:
- `:HAS_PARENT`
- `:HAS_CHILD`
- `:USES_CONTEXT`
- `:REFERENCES_DECISION`

**Cypher 쿼리 예시**:
```cypher
// 전체 트리 구조 조회
MATCH path = (root:WeakpointDetectionContext)-[:HAS_CHILD*]->(leaf)
WHERE root.hasParent IS NULL
RETURN path

// 특정 Context의 모든 하위 노드 찾기
MATCH (wdc:WeakpointDetectionContext {id: $contextId})-[:HAS_CHILD*]->(descendant)
RETURN descendant

// DecisionModel이 참조하는 모든 Context 찾기
MATCH (wadm:WeakpointAnalysisDecisionModel)-[:USES_CONTEXT]->(ctx)
RETURN wadm, collect(ctx) as contexts
```

---

## 13. 참고 문서

- **01_ontology_specification.md**: 클래스 및 속성 정의
- **02_ontology_types.md**: 타입 정의
- **03_ontology_relations.md**: 관계 정의
- **04_ontology_constraints.md**: 제약 조건 정의
- **06_jsonld_mapping.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent04 Ontology Team  
**다음 문서**: `06_jsonld_mapping.md`

