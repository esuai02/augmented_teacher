# Agent04 온톨로지 제약 조건 명세서 (Ontology Constraints Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints  
**목적**: 온톨로지 데이터 무결성 제약 조건 정의

---

## 1. 문서 범위

이 문서는 **Agent04 온톨로지의 모든 제약 조건(Constraints)**을 정의합니다.

**포함 내용**:
- 필수 속성 제약
- 카디널리티 제약
- 타입 범위 제약
- 관계 제약
- 비즈니스 규칙 제약

---

## 2. 필수 속성 제약

### 2.1 WeakpointDetectionContext 필수 속성

**제약 규칙**:
- `mk-a04:hasStudentId` (필수)
- `mk-a04:hasActivityType` (필수)
- `mk-a04:hasActivityCategory` (필수)
- `mk-a04:hasWeakpointSeverity` (필수)
- `mk:hasStage` (필수)

### 2.2 ActivityAnalysisContext 필수 속성

**제약 규칙**:
- `mk:hasParent` (필수, WeakpointDetectionContext 참조)
- `mk-a04:hasActivityStage` (필수)
- `mk-a04:hasPauseFrequency` (필수)
- `mk-a04:hasAttentionScore` (필수)
- `mk:hasStage` (필수)

### 2.3 WeakpointAnalysisDecisionModel 필수 속성

**제약 규칙**:
- `mk:hasParent` (필수, WeakpointDetectionContext 참조)
- `mk:usesContext` (필수, 최소 2개 Context 참조)
- `mk-a04:hasWeakpointDescription` (필수)
- `mk-a04:hasRootCause` (필수)
- `mk-a04:hasReinforcementStrategy` (필수)
- `mk-a04:hasReinforcementPriority` (필수)
- `mk:hasStage` (필수)

### 2.4 ReinforcementPlanExecutionPlan 필수 속성

**제약 규칙**:
- `mk:hasParent` (필수, WeakpointAnalysisDecisionModel 참조)
- `mk:referencesDecision` (필수, WeakpointAnalysisDecisionModel 참조)
- `mk-a04:hasAction` (필수, 최소 1개)
- `mk-a04:hasMeasurement` (필수, 최소 1개)
- `mk:hasStage` (필수)

---

## 3. 타입 범위 제약

### 3.1 점수 타입 제약

- `mk-a04:hasAttentionScore`: 0.0 이상 1.0 이하의 실수
- `mk-a04:hasGazeAttentionScore`: 0.0 이상 1.0 이하의 실수
- `mk-a04:hasImmersionScore`: 0.0 이상 1.0 이하의 실수
- `mk-a04:hasMethodPersonaMatchScore`: 0.0 이상 1.0 이하의 실수

### 3.2 정수 타입 제약

- `mk-a04:hasPauseFrequency`: 0 이상의 정수
- `mk-a04:hasAttentionDropTime`: 0 이상의 정수 (초 단위)

### 3.3 열거형 타입 제약

- `mk-a04:hasActivityType`: `mk-a04:ActivityType` 열거형 값만 허용
- `mk-a04:hasWeakpointSeverity`: `mk-a04:SeverityLevel` 열거형 값만 허용
- `mk-a04:hasReinforcementStrategy`: `mk-a04:ReinforcementStrategy` 열거형 값만 허용
- `mk-a04:hasReinforcementPriority`: `mk-a04:PriorityLevel` 열거형 값만 허용

---

## 4. 관계 제약

### 4.1 순환 참조 금지

- `mk:hasParent`는 순환 참조를 허용하지 않음
- 노드는 자신의 조상(ancestor)을 parent로 가질 수 없음

### 4.2 참조 존재성

- `mk:usesContext`는 반드시 존재하는 Context 인스턴스를 참조해야 함
- `mk:referencesDecision`은 반드시 존재하는 Decision 인스턴스를 참조해야 함

### 4.3 카디널리티 제약

- `mk:usesContext`: 최소 2개 (WeakpointAnalysisDecisionModel의 경우)
- `mk:referencesDecision`: 정확히 1개 (단일 Decision만 참조)

---

## 5. 비즈니스 규칙 제약

### 5.1 취약점 심각도 규칙

- 멈춤 빈도가 10회 이상이면 Critical 심각도
- 멈춤 빈도가 5-9회이면 High 심각도
- 멈춤 빈도가 3-4회이면 Medium 심각도
- 멈춤 빈도가 0-2회이면 Low 심각도

### 5.2 주의집중도 규칙

- 주의집중도 점수가 0.0-0.3이면 Critical 심각도
- 주의집중도 점수가 0.3-0.5이면 High 심각도
- 주의집중도 점수가 0.5-0.7이면 Medium 심각도
- 주의집중도 점수가 0.7-1.0이면 Low 심각도

### 5.3 보강 전략 선택 규칙

- 개념 혼동이 탐지되면 ConceptClarificationStrategy
- 방법-페르소나 불일치가 탐지되면 MethodOptimizationStrategy
- 주의집중도가 낮으면 AttentionRecoveryStrategy
- 몰입도가 낮으면 CombinationOptimizationStrategy
- 지루함이 탐지되면 BoredomInterventionStrategy

---

## 6. 참고 문서

- **01_ontology_specification.md**: 클래스 및 속성 정의
- **02_ontology_types.md**: 타입 정의
- **03_ontology_relations.md**: 관계 정의

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent04 Ontology Team

