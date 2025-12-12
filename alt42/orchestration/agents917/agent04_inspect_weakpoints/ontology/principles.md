# Agent04 온톨로지 구축 전략

**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints  
**버전**: 1.0  
**목적**: 학습 활동에서 탐지된 취약점을 분석하고 보강 방안을 제시하기 위한 온톨로지 설계

---

## 1. 개요

Agent04 온톨로지는 학습 활동에서 탐지된 취약점을 상세 분석하여 구체적인 보강 방안을 제시하는 지식 표현 체계입니다. 룰 기반 시스템(`rules.yaml`)과 연계하여 자동화된 취약점 분석 및 보강 방안 도출을 지원합니다.

### 핵심 온톨로지 요소

1. **WeakpointDetectionContext**: 취약점 탐지 기본 정보 표현
2. **ActivityAnalysisContext**: 활동 상세 분석 결과 표현
3. **WeakpointAnalysisDecisionModel**: 취약점 분석 및 보강 방안 결정 표현
4. **ReinforcementPlanExecutionPlan**: 보강 계획 실행 계획 표현

---

## 2. 주요 기능

### 2.1 취약점 탐지

- 학습 활동별 취약점 탐지 (7개 활동 카테고리)
- 취약점 심각도 평가
- 취약점 패턴 분석

### 2.2 활동 분석

- 활동 단계별 멈춤 패턴 분석
- 주의집중도 및 몰입도 측정
- 개념 혼동 유형 탐지
- 학습 방법-페르소나 적합도 평가

### 2.3 보강 방안 결정

- 근본 원인 분석
- 보강 전략 선택
- 권장 방법 및 콘텐츠 제시
- 우선순위 결정

### 2.4 실행 계획 수립

- 구체적 행동 계획
- 측정 방법 정의
- 피드백 수집 계획
- 조정 계획 수립

---

## 3. 온톨로지 구조

### 3.1 3-Layer 아키텍처

```
[1] Context Layer
    ├── WeakpointDetectionContext (취약점 탐지 기본 정보)
    └── ActivityAnalysisContext (활동 상세 분석)

[2] Decision Layer
    └── WeakpointAnalysisDecisionModel (취약점 분석 및 보강 방안 결정)

[3] Execution Layer
    └── ReinforcementPlanExecutionPlan (보강 계획 실행)
```

### 3.2 주요 클래스

**Context Layer**:
- `mk-a04:WeakpointDetectionContext`: 취약점 탐지 컨텍스트
- `mk-a04:ActivityAnalysisContext`: 활동 분석 컨텍스트

**Decision Layer**:
- `mk-a04:WeakpointAnalysisDecisionModel`: 취약점 분석 결정 모델

**Execution Layer**:
- `mk-a04:ReinforcementPlanExecutionPlan`: 보강 계획 실행 계획

---

## 4. 룰 엔진 연동

### 4.1 온톨로지 액션

룰 엔진에서 다음 온톨로지 액션을 지원합니다:

- `create_instance: 'mk-a04:WeakpointDetectionContext'`
- `set_property: ('mk-a04:hasStudentId', '{studentId}')`
- `reason_over: 'mk-a04:ActivityAnalysisContext'`
- `generate_reinforcement_plan: 'mk-a04:WeakpointAnalysisDecisionModel'`

### 4.2 룰 연계

`rules.yaml`의 룰에서 온톨로지 액션을 사용하여 취약점 분석 및 보강 방안을 자동으로 생성합니다.

---

## 5. 데이터 흐름

```
학습 활동 수행
  ↓
취약점 탐지 (WeakpointDetectionContext 생성)
  ↓
활동 상세 분석 (ActivityAnalysisContext 생성)
  ↓
취약점 분석 및 보강 방안 결정 (WeakpointAnalysisDecisionModel 생성)
  ↓
보강 계획 실행 (ReinforcementPlanExecutionPlan 생성)
  ↓
보강 실행 및 모니터링
```

---

## 6. 참고 문서

- `01_ontology_specification.md`: 온톨로지 명세서
- `02_ontology_types.md`: 타입 정의
- `03_ontology_relations.md`: 관계 정의
- `04_ontology_constraints.md`: 제약 조건

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent04 Ontology Team

