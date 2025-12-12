# Task List: Agent04 온톨로지 결과 응답 생성 통합

**생성일**: 2025-01-27  
**PRD**: `0002-prd-agent04-ontology-response-integration.md`  
**상태**: 진행 중

---

## Relevant Files

- `alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.service.php` - Agent Garden Service (응답 생성 로직 수정)
- `alt42/orchestration/agents/agent01_onboarding/rules/rule_evaluator.php` - Agent01 응답 생성 로직 참조용
- `alt42/orchestration/agents/agent04_inspect_weakpoints/ontology/OntologyEngine.php` - 온톨로지 엔진 (결과 구조 확인)

### Notes

- Agent01의 `generateResponseFromActions` 메서드를 참조하여 구현
- 온톨로지 결과가 없을 경우 기본 동작 유지
- 구조화된 데이터와 텍스트 메시지 모두 제공

---

## Tasks

- [ ] 1.0 Agent01 응답 생성 로직 분석
  - [ ] 1.1 Agent01의 `generateResponseFromActions` 메서드 분석
  - [ ] 1.2 Agent01의 온톨로지 결과 통합 방식 파악
  - [ ] 1.3 Agent01의 응답 구조 분석 (텍스트 메시지 + 구조화된 데이터)

- [ ] 2.0 온톨로지 결과 추출 함수 구현
  - [ ] 2.1 `extractReinforcementPlan()` 함수 구현 (보강 방안 추출)
  - [ ] 2.2 `extractReasoningResults()` 함수 구현 (추론 결과 추출)
  - [ ] 2.3 `extractExecutionPlan()` 함수 구현 (실행 계획 추출)
  - [ ] 2.4 온톨로지 결과 파싱 에러 처리

- [ ] 3.0 보강 방안 메시지 생성 함수 구현
  - [ ] 3.1 `formatReinforcementPlanMessage()` 함수 구현
  - [ ] 3.2 보강 방안 설명(`weakpointDescription`) 메시지 변환
  - [ ] 3.3 근본 원인(`rootCause`) 메시지 변환
  - [ ] 3.4 보강 전략(`reinforcementStrategy`) 메시지 변환
  - [ ] 3.5 권장 방법(`recommendedMethod`) 메시지 변환
  - [ ] 3.6 권장 콘텐츠(`recommendedContent`) 메시지 변환
  - [ ] 3.7 피드백 메시지(`feedbackMessage`) 포함

- [ ] 4.0 추론 결과 메시지 생성 함수 구현
  - [ ] 4.1 `formatReasoningResultsMessage()` 함수 구현
  - [ ] 4.2 심각도(`inferredSeverity`) 메시지 변환
  - [ ] 4.3 보강 전략(`inferredStrategy`) 메시지 변환
  - [ ] 4.4 우선순위(`inferredPriority`) 메시지 변환

- [ ] 5.0 Agent04 응답 생성 메서드 구현
  - [ ] 5.1 `generateAgent04Response()` 메서드 생성
  - [ ] 5.2 기존 룰 기반 응답 생성 로직 통합
  - [ ] 5.3 온톨로지 결과 추출 및 메시지 생성 통합
  - [ ] 5.4 온톨로지 결과가 없을 경우 기본 동작 유지
  - [ ] 5.5 중복 메시지 제거 로직 구현

- [ ] 6.0 구조화된 데이터 제공
  - [ ] 6.1 JSON 응답에 `reinforcement_plan` 필드 추가
  - [ ] 6.2 JSON 응답에 `reasoning_results` 필드 추가
  - [ ] 6.3 JSON 응답에 `execution_plan` 필드 추가
  - [ ] 6.4 각 필드의 데이터 구조 정의

- [ ] 7.0 Agent Garden Service 통합
  - [ ] 7.1 `executeAgent04WithRules()` 메서드에서 응답 생성 메서드 호출
  - [ ] 7.2 온톨로지 결과를 응답에 포함
  - [ ] 7.3 에러 처리 및 로깅 추가

- [ ] 8.0 통합 테스트
  - [ ] 8.1 취약점 탐지 시나리오 테스트
  - [ ] 8.2 보강 방안 메시지 생성 확인
  - [ ] 8.3 추론 결과 메시지 생성 확인
  - [ ] 8.4 구조화된 데이터 확인
  - [ ] 8.5 온톨로지 결과가 없을 경우 기본 동작 확인
  - [ ] 8.6 여러 취약점 탐지 시 응답 확인

---

**작성일**: 2025-01-27  
**다음 단계**: PRD 0003 (실제 사용 검증 및 테스트)

