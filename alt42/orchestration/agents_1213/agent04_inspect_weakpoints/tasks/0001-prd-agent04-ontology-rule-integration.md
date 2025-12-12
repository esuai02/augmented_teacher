# PRD: Agent04 온톨로지-룰 엔진 연동

**생성일**: 2025-01-27  
**버전**: 1.0  
**상태**: 초안

---

## 1. Introduction/Overview

Agent04 (Inspect Weakpoints)는 학습 활동별 취약점을 분석하고 보강 방안을 제시하는 에이전트입니다. 현재 온톨로지 엔진(`OntologyEngine.php`, `OntologyActionHandler.php`)은 구현 완료되었으나, `rules.yaml`의 룰과 연동되지 않아 실제로 동작하지 않습니다.

이 PRD는 Agent04의 룰 엔진과 온톨로지 엔진을 연동하여, 취약점 탐지 시 자동으로 온톨로지 인스턴스를 생성하고 의미 기반 추론을 수행하여 보강 방안을 자동 생성하도록 하는 기능을 정의합니다.

### 문제 정의

1. **온톨로지 액션 미추가**: `rules.yaml`의 룰에 온톨로지 액션(`create_instance`, `reason_over`, `generate_reinforcement_plan`)이 추가되지 않음
2. **Agent Garden Service 연동 부재**: Agent04 실행 시 온톨로지 액션이 처리되지 않음
3. **보강 방안 자동 생성 미동작**: 취약점 분석 후 보강 방안이 온톨로지 기반으로 자동 생성되지 않음

---

## 2. Goals

1. **Rules.yaml에 온톨로지 액션 추가**
   - 모든 취약점 탐지 룰에 `create_instance` 액션 추가
   - 활동 분석 룰에 `reason_over` 액션 추가
   - 보강 방안 생성 룰에 `generate_reinforcement_plan` 액션 추가

2. **Agent Garden Service 연동**
   - Agent04 실행 시 온톨로지 액션 자동 감지 및 처리
   - `processOntologyActions` 메서드가 Agent04에서도 동작하도록 확장

3. **온톨로지 기반 보강 방안 자동 생성**
   - 취약점 탐지 → 활동 분석 → 보강 방안 결정 → 실행 계획 수립의 전체 흐름 구현

---

## 3. User Stories

### US-1: 학습 활동 수행 시
**As a** 학습자  
**I want** 취약점이 탐지되면 자동으로 온톨로지 인스턴스가 생성되도록  
**So that** 취약점이 구조화된 형태로 저장되고 분석될 수 있다

### US-2: 취약점 분석 시
**As a** 시스템  
**I want** 온톨로지 기반 의미 추론을 수행하도록  
**So that** 단순 패턴 매칭이 아닌 의미 기반 분석이 가능하다

### US-3: 보강 방안 제시 시
**As a** 학습자  
**I want** 온톨로지 기반으로 보강 방안이 자동 생성되도록  
**So that** 개인화된 맞춤형 보강 방안을 받을 수 있다

---

## 4. Functional Requirements

### FR-1: Rules.yaml 온톨로지 액션 추가
- **FR-1.1**: 개념이해 취약점 탐지 룰(`CU_A1_weak_point_detection`)에 `create_instance: 'mk-a04:WeakpointDetectionContext'` 액션 추가
- **FR-1.2**: 활동 분석 룰에 `create_instance: 'mk-a04:ActivityAnalysisContext'` 액션 추가
- **FR-1.3**: 활동 분석 후 `reason_over: 'mk-a04:ActivityAnalysisContext'` 액션 추가
- **FR-1.4**: 보강 방안 생성 룰에 `generate_reinforcement_plan: 'mk-a04:WeakpointAnalysisDecisionModel'` 액션 추가
- **FR-1.5**: 모든 룰에 필요한 `set_property` 액션 추가

### FR-2: Agent Garden Service 연동
- **FR-2.1**: `executeAgent()` 메서드에서 Agent04 실행 시 룰 엔진 사용하도록 수정
- **FR-2.2**: Agent04의 `rules.yaml` 경로 설정 및 룰 평가기 호출
- **FR-2.3**: `processOntologyActions` 메서드가 Agent04에서도 동작하도록 확장
- **FR-2.4**: Agent04의 온톨로지 핸들러 경로 자동 감지

### FR-3: 온톨로지 액션 처리
- **FR-3.1**: `create_instance` 액션 처리 (WeakpointDetectionContext, ActivityAnalysisContext)
- **FR-3.2**: `set_property` 액션 처리 (변수 치환 포함)
- **FR-3.3**: `reason_over` 액션 처리 (심각도 추론, 보강 전략 추론)
- **FR-3.4**: `generate_reinforcement_plan` 액션 처리 (보강 방안 자동 생성)

### FR-4: 데이터 흐름 구현
- **FR-4.1**: 학습 활동 수행 → 룰 평가 → 온톨로지 인스턴스 생성 흐름 구현
- **FR-4.2**: 온톨로지 추론 결과를 룰 엔진에 반환하는 흐름 구현
- **FR-4.3**: 보강 방안 생성 결과를 응답에 포함하는 흐름 구현

---

## 5. Non-Goals (Out of Scope)

1. **온톨로지 엔진 수정**: 이미 구현된 `OntologyEngine.php`와 `OntologyActionHandler.php`는 수정하지 않음
2. **룰 엔진 수정**: Python 룰 엔진 자체는 수정하지 않음 (액션만 추가)
3. **UI 변경**: 프론트엔드 UI 변경은 포함하지 않음 (백엔드 통합만)

---

## 6. Technical Considerations

### 6.1 기존 인프라 활용
- Agent01의 온톨로지 연동 방식을 참조하여 Agent04에 적용
- `agent_garden.service.php`의 `processOntologyActions` 메서드 활용
- Agent04의 `OntologyActionHandler.php`는 이미 구현되어 있음

### 6.2 에이전트 ID 정규화
- Agent04의 에이전트 ID는 `agent04` 또는 `agent04_inspect_weakpoints`로 처리
- `OntologyActionHandler` 생성 시 올바른 에이전트 ID 전달 필요

### 6.3 변수 치환
- `{activity_type}`, `{pause_frequency}` 등의 변수는 컨텍스트에서 자동 치환
- 변수가 없을 경우 에러 로깅 및 기본값 처리

### 6.4 에러 처리
- 온톨로지 액션 실패 시 기본 동작 유지 (기존 룰 동작 계속)
- 에러 로깅 강화 (파일 경로 및 라인 번호 포함)

---

## 7. Success Metrics

1. **온톨로지 액션 처리율**: 모든 취약점 탐지 룰에서 온톨로지 액션이 정상 처리되는 비율 100%
2. **보강 방안 생성율**: 취약점 탐지 시 보강 방안이 자동 생성되는 비율 100%
3. **에러 발생률**: 온톨로지 액션 처리 중 에러 발생률 5% 이하

---

## 8. Open Questions

1. Agent04의 룰 평가기는 Python인가, PHP인가? (Agent01은 Python 사용)
2. Agent04 실행 시 `executeAgent04WithRules` 같은 별도 메서드가 필요한가?
3. 온톨로지 결과를 응답에 어떻게 포함시킬 것인가? (다음 PRD에서 다룰 예정)

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent04 Development Team

