# Agent01 온톨로지 계층 구조 명세서 (Ontology Context Tree Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: 온톨로지 계층 구조 및 트리 구조 정의

---

## 1. 문서 범위

이 문서는 **Agent01 온톨로지의 계층 구조 및 트리 구조**를 정의합니다.

**포함 내용**:
- Context/Decision/Execution 계층 구조
- 트리 다이어그램
- 각 계층 간 책임 및 데이터 흐름
- DIL 단계와 계층 구조 매핑
- OIW 단계와 계층 구조 매핑

**제외 내용**:
- 클래스 정의 (01_ONTOLOGY_SPEC.md 참조)
- 관계 정의 (03_ONTOLOGY_RELATIONS.md 참조)
- 제약 조건 (04_ONTOLOGY_CONSTRAINTS.md 참조)

---

## 2. 계층 구조 개요

Agent01 온톨로지는 **3단계 계층 구조**를 가집니다:

```
[1] Context Layer (맥락 레이어)
    └── 데이터 수집 및 저장

[2] Decision Layer (결정 레이어)
    └── 의사결정 수행

[3] Execution Layer (실행 레이어)
    └── 실행 계획 수립
```

---

## 3. Context Layer (맥락 레이어)

### 3.1 계층 정의

**역할**: 학생의 온보딩 정보와 학습 맥락 데이터를 수집 및 저장

**특징**:
- 순수 데이터만 저장 (의미 해석 없음)
- 원시 데이터(raw data) 중심
- 정책/의지/추론 제외

**노드 클래스**:
- `mk:OnboardingContext`
- `mk:LearningContextIntegration`

### 3.2 OnboardingContext

**위치**: Context Layer의 루트 노드

**책임**:
- 학생의 기본 정보 저장
- 학교/학원 정보 저장
- 온보딩 설문 응답 데이터 저장
- 학습 스타일/자신감 등 원시 데이터 저장

**데이터 흐름**:
```
온보딩 설문 응답
    ↓
rules.yaml (S0_R1~S0_R6)
    ↓
OnboardingContext 인스턴스 생성
```

**트리 위치**:
```
OnboardingContext (root)
├── hasParent: null
└── hasChild: [LearningContextIntegration, FirstClassDecisionModel]
```

### 3.3 LearningContextIntegration

**위치**: Context Layer의 자식 노드

**책임**:
- 진도 정보 저장 (원시 데이터)
- 단원 마스터리 정보 저장
- 정렬 상태 정보 저장

**데이터 흐름**:
```
진도/단원 데이터
    ↓
rules.yaml (S0_R5)
    ↓
LearningContextIntegration 인스턴스 생성
    ↓
parent: OnboardingContext
```

**트리 위치**:
```
OnboardingContext
└── LearningContextIntegration
    ├── hasParent: OnboardingContext
    └── hasChild: []
```

---

## 4. Decision Layer (결정 레이어)

### 4.1 계층 정의

**역할**: Context 데이터를 기반으로 의사결정 수행

**특징**:
- Context Layer의 데이터를 조합하여 결정
- 문제 선택 및 해결 방안 결정
- 난이도, 전략, 범위 등 결정

**노드 클래스**:
- `mk:FirstClassDecisionModel`

### 4.2 FirstClassDecisionModel

**위치**: Decision Layer의 단일 노드

**책임**:
- Interpretation Layer의 문제 후보군에서 최종 문제 선택
- 난이도 수준 결정
- 정렬 전략 결정
- 단원 계획 결정
- 내용 범위 결정

**데이터 흐름**:
```
OnboardingContext + LearningContextIntegration
    ↓
Interpretation Layer (문제 후보군 도출)
    ↓
Will Layer + Intent Layer (제약 및 목표)
    ↓
FirstClassDecisionModel 인스턴스 생성
    ↓
난이도/전략/범위 결정
```

**트리 위치**:
```
OnboardingContext
└── FirstClassDecisionModel
    ├── hasParent: OnboardingContext
    ├── usesContext: [OnboardingContext, LearningContextIntegration]
    └── hasChild: [FirstClassExecutionPlan]
```

**의존성**:
- 반드시 `OnboardingContext`와 `LearningContextIntegration`을 모두 참조해야 함
- `usesContext`를 통해 두 Context를 모두 참조

---

## 5. Execution Layer (실행 레이어)

### 5.1 계층 정의

**역할**: Decision의 결정을 실행 가능한 계획으로 변환

**특징**:
- Decision Layer의 결정을 구체적 행동으로 분해
- 실행 가능한 단계로 변환
- 측정 및 피드백 계획 수립

**노드 클래스**:
- `mk:FirstClassExecutionPlan`

### 5.2 FirstClassExecutionPlan

**위치**: Execution Layer의 단일 노드

**책임**:
- Decision의 결정을 실행 행동(action)으로 변환
- 측정 방법(measurement) 정의
- 피드백 수집 계획 정의
- 조정 계획(adjustment) 정의

**데이터 흐름**:
```
FirstClassDecisionModel
    ↓
FirstClassExecutionPlan 인스턴스 생성
    ↓
action/measurement/feedback/adjustment 정의
```

**트리 위치**:
```
OnboardingContext
└── FirstClassDecisionModel
    └── FirstClassExecutionPlan
        ├── hasParent: FirstClassDecisionModel
        ├── referencesDecision: FirstClassDecisionModel
        └── hasChild: []
```

**의존성**:
- 반드시 `FirstClassDecisionModel`을 parent로 가져야 함
- `referencesDecision`과 `hasParent`는 같은 노드를 참조해야 함

---

## 6. 전체 트리 구조

### 6.1 완전한 트리 다이어그램

```
                    ┌─────────────────────────────┐
                    │  OnboardingContext         │
                    │  (Context Layer)           │
                    │                            │
                    │  Stage: "Context"          │
                    │  Parent: null (root)       │
                    └────────────┬───────────────┘
                                 │
                    ┌────────────┴───────────────┐
                    │                            │
                    ▼                            ▼
        ┌──────────────────────┐    ┌──────────────────────────┐
        │ LearningContext      │    │ FirstClassDecisionModel  │
        │ Integration          │    │ (Decision Layer)        │
        │ (Context Layer)      │    │                         │
        │                      │    │ Stage: "Decision"       │
        │ Stage: "Context"     │    │ Parent: OnboardingContext│
        │ Parent: OC           │    │ usesContext: [OC, LCI]  │
        └──────────────────────┘    └───────────┬──────────────┘
                                                 │
                                                 ▼
                                    ┌──────────────────────────┐
                                    │ FirstClassExecutionPlan  │
                                    │ (Execution Layer)        │
                                    │                         │
                                    │ Stage: "Execution"      │
                                    │ Parent: DCM            │
                                    │ referencesDecision: DCM │
                                    └──────────────────────────┘

OC = OnboardingContext
LCI = LearningContextIntegration
DCM = FirstClassDecisionModel
```

### 6.2 트리 노드 요약

| 노드 ID | 클래스 | Stage | Parent | Children | Context 참조 |
|--------|--------|-------|--------|----------|-------------|
| OC | OnboardingContext | Context | null | LCI, DCM | - |
| LCI | LearningContextIntegration | Context | OC | - | - |
| DCM | FirstClassDecisionModel | Decision | OC | ECP | OC, LCI |
| ECP | FirstClassExecutionPlan | Execution | DCM | - | - (간접) |

---

## 7. 계층 간 책임 분리

### 7.1 Context Layer 책임

**단일 책임**: 데이터 수집 및 저장

**포함**:
- 원시 데이터(raw data) 저장
- 설문 응답 데이터 저장
- 진도/단원 데이터 저장

**제외**:
- 의미 해석
- 문제 정의
- 의사결정
- 전략 수립

### 7.2 Decision Layer 책임

**단일 책임**: 의사결정 수행

**포함**:
- 문제 선택
- 난이도 결정
- 전략 선택
- 범위 결정

**제외**:
- 데이터 수집
- 실행 계획 수립
- 실제 행동 수행

### 7.3 Execution Layer 책임

**단일 책임**: 실행 계획 수립

**포함**:
- 행동(action) 정의
- 측정 방법 정의
- 피드백 계획 정의
- 조정 계획 정의

**제외**:
- 데이터 수집
- 의사결정
- 실제 실행 (이는 시스템 외부에서 수행)

---

## 8. 계층 간 데이터 흐름

### 8.1 데이터 흐름 다이어그램

```
[데이터 수집]
    ↓
┌─────────────────────────────────────┐
│  Context Layer                      │
│  - OnboardingContext                │
│  - LearningContextIntegration       │
│  (순수 데이터 저장)                  │
└──────────────┬──────────────────────┘
               │
               │ 데이터 전달
               ▼
┌─────────────────────────────────────┐
│  Interpretation Layer               │
│  (의미 해석 및 문제 후보군 도출)      │
│  - Will Layer 제약 적용             │
│  - Intent Layer 목표 적용           │
└──────────────┬──────────────────────┘
               │
               │ 문제 후보군 전달
               ▼
┌─────────────────────────────────────┐
│  Decision Layer                     │
│  - FirstClassDecisionModel          │
│  (최종 문제 선택 및 결정)            │
└──────────────┬──────────────────────┘
               │
               │ 결정 전달
               ▼
┌─────────────────────────────────────┐
│  Execution Layer                    │
│  - FirstClassExecutionPlan          │
│  (실행 계획 수립)                    │
└─────────────────────────────────────┘
```

### 8.2 데이터 전달 규칙

**Context → Decision**:
- `usesContext` 관계를 통해 전달
- 최소 2개 Context 참조 필수
- OnboardingContext와 LearningContextIntegration 모두 참조

**Decision → Execution**:
- `hasParent` 및 `referencesDecision` 관계를 통해 전달
- 단일 Decision만 참조
- Decision의 모든 결정사항을 실행 계획으로 변환

---

## 9. DIL 단계와 계층 구조 매핑

### 9.1 DIL Vertical 매핑

| DIL 단계 | 계층 | 노드 | 설명 |
|---------|------|------|------|
| -12 ~ -5 | - | Reasoning | 추론 규칙 (온톨로지 외부) |
| -4 ~ -1 | Context | OnboardingContext, LearningContextIntegration | 데이터 구조화 |
| 0 ~ 3 | Decision | FirstClassDecisionModel | 문제 선택 및 결정 |
| 4 ~ 10 | Execution | FirstClassExecutionPlan | 실행 계획 수립 |

### 9.2 DIL 단계별 상세 매핑

**DIL -4 ~ -1 (Ontic Zone) → Context Layer**:
- DIL -4 (Intention): `mk:hasIntent` 메타데이터
- DIL -3 (Identity): `mk:hasIdentity` 메타데이터
- DIL -2 (Purpose): `mk:hasPurpose` 메타데이터
- DIL -1 (Context): `mk:hasContext` 메타데이터

**DIL 0 ~ 3 (Decision Zone) → Decision Layer**:
- DIL 0 (Problem): `mk:hasProblem` 속성
- DIL 1 (Decision): `mk:hasDecision` 속성
- DIL 2 (Impact): `mk:hasImpact` 속성
- DIL 3 (Data): `mk:hasDataSources` 속성

**DIL 4 ~ 10 (Execution Zone) → Execution Layer**:
- DIL 4 (Action): `mk:hasAction` 속성
- DIL 5 (Measurement): `mk:hasMeasurement` 속성
- DIL 7 (Feedback): `mk:hasFeedback` 속성
- DIL 8 (Adjustment): `mk:hasAdjustment` 속성

---

## 10. OIW 단계와 계층 구조 매핑

### 10.1 OIW 레이어 매핑

| OIW 레이어 | 계층 | 노드 | 설명 |
|-----------|------|------|------|
| Will Layer | - | - | 시스템 가치 (온톨로지 외부) |
| Intent Layer | - | - | 상황별 목표 (온톨로지 외부) |
| Context Layer | Context | OnboardingContext, LearningContextIntegration | 데이터 구조 |
| Interpretation Layer | - | - | 의미 해석 (온톨로지 외부) |
| Decision Layer | Decision | FirstClassDecisionModel | 의사결정 |
| Execution Layer | Execution | FirstClassExecutionPlan | 실행 계획 |

### 10.2 OIW와 온톨로지 분리

**온톨로지에 포함되지 않는 OIW 요소**:
- Will Layer: 정책/제약 (별도 문서)
- Intent Layer: 목표 설정 (별도 문서)
- Interpretation Layer: 의미 해석 (별도 문서)

**온톨로지에 포함되는 OIW 요소**:
- Context Layer: 데이터 구조
- Decision Layer: 결정 결과
- Execution Layer: 실행 계획

---

## 11. 트리 탐색 규칙

### 11.1 상향 탐색 (Bottom-Up)

**규칙**: 자식 노드에서 부모 노드로 탐색

**예시**:
```
FirstClassExecutionPlan
    → hasParent → FirstClassDecisionModel
        → hasParent → OnboardingContext
            → hasParent → null (root)
```

**사용 사례**:
- ExecutionPlan에서 Context 데이터 접근
- 계층 구조 검증
- 부모 노드의 속성 참조

### 11.2 하향 탐색 (Top-Down)

**규칙**: 부모 노드에서 자식 노드로 탐색

**예시**:
```
OnboardingContext
    → hasChild → [LearningContextIntegration, FirstClassDecisionModel]
        → FirstClassDecisionModel.hasChild → [FirstClassExecutionPlan]
```

**사용 사례**:
- Context에서 모든 하위 노드 찾기
- 전체 트리 구조 파악
- 계층 구조 시각화

### 11.3 횡단 탐색 (Lateral)

**규칙**: 같은 계층 내 노드 간 탐색

**예시**:
```
OnboardingContext
    → usesContext (via FirstClassDecisionModel) → LearningContextIntegration
```

**사용 사례**:
- DecisionModel에서 참조하는 모든 Context 찾기
- 같은 계층의 관련 노드 찾기

---

## 12. 트리 구조 확장 가이드

### 12.1 새로운 계층 추가

새로운 계층을 추가할 때는:

1. **Stage 값 정의**: 새로운 Stage 값 추가 (예: "Evaluation")
2. **노드 클래스 정의**: 새로운 클래스 생성
3. **계층 관계 정의**: parent-child 관계 설정
4. **제약 조건 정의**: 계층 간 제약 조건 추가

### 12.2 기존 계층 확장

기존 계층에 노드를 추가할 때는:

1. **클래스 정의**: 01_ONTOLOGY_SPEC.md에 클래스 추가
2. **관계 정의**: 03_ONTOLOGY_RELATIONS.md에 관계 추가
3. **트리 구조 업데이트**: 이 문서에 트리 구조 반영

---

## 13. 트리 구조 검증 규칙

### 13.1 계층 구조 검증

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

### 13.2 트리 구조 무결성 검증

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

## 14. GraphDB 트리 구조 표현

### 14.1 Neo4j 트리 구조

**노드 레이블**:
- `:OnboardingContext`
- `:LearningContextIntegration`
- `:FirstClassDecisionModel`
- `:FirstClassExecutionPlan`

**관계 타입**:
- `:HAS_PARENT`
- `:HAS_CHILD`
- `:USES_CONTEXT`
- `:REFERENCES_DECISION`

**Cypher 쿼리 예시**:
```cypher
// 전체 트리 구조 조회
MATCH path = (root:OnboardingContext)-[:HAS_CHILD*]->(leaf)
WHERE root.hasParent IS NULL
RETURN path

// 특정 Context의 모든 하위 노드 찾기
MATCH (oc:OnboardingContext {id: $contextId})-[:HAS_CHILD*]->(descendant)
RETURN descendant

// DecisionModel이 참조하는 모든 Context 찾기
MATCH (dcm:FirstClassDecisionModel)-[:USES_CONTEXT]->(ctx)
RETURN dcm, collect(ctx) as contexts
```

---

## 15. 참고 문서

- **01_ONTOLOGY_SPEC.md**: 클래스 및 속성 정의
- **02_ONTOLOGY_TYPES.md**: 타입 정의
- **03_ONTOLOGY_RELATIONS.md**: 관계 정의
- **04_ONTOLOGY_CONSTRAINTS.md**: 제약 조건 정의
- **06_JSONLD_MAPPING.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**다음 문서**: `06_jsonld_mapping.md`

