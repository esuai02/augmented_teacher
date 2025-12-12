# Task List: Agent04 온톨로지-룰 엔진 연동

**생성일**: 2025-01-27  
**PRD**: `0001-prd-agent04-ontology-rule-integration.md`  
**상태**: 진행 중

---

## Relevant Files

- `alt42/orchestration/agents/agent04_inspect_weakpoints/rules/rules.yaml` - 룰 정의 파일 (온톨로지 액션 추가)
- `alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.service.php` - Agent Garden Service (Agent04 연동 추가)
- `alt42/orchestration/agents/agent04_inspect_weakpoints/ontology/OntologyActionHandler.php` - 온톨로지 액션 핸들러 (이미 구현됨, 검증 필요)
- `alt42/orchestration/agents/agent04_inspect_weakpoints/ontology/OntologyEngine.php` - 온톨로지 엔진 (이미 구현됨, 검증 필요)
- `alt42/orchestration/agents/agent04_inspect_weakpoints/rules/rule_evaluator.php` - 룰 평가기 (존재 여부 확인 필요)
- `alt42/orchestration/agents/agent04_inspect_weakpoints/rules/data_access.php` - 데이터 접근 (존재 여부 확인 필요)

### Notes

- Agent01의 온톨로지 연동 방식을 참조하여 구현
- 온톨로지 엔진은 이미 구현되어 있으므로 검증만 수행
- 에러 발생 시 기본 동작 유지 (기존 룰 동작 계속)

---

## Tasks

- [ ] 1.0 Agent04 룰 평가기 및 데이터 접근 확인
  - [ ] 1.1 `agent04_inspect_weakpoints/rules/rule_evaluator.php` 파일 존재 여부 확인
  - [ ] 1.2 `agent04_inspect_weakpoints/rules/data_access.php` 파일 존재 여부 확인
  - [ ] 1.3 Agent01의 `rule_evaluator.php`와 비교하여 구조 파악
  - [ ] 1.4 Agent01의 `data_access.php`와 비교하여 구조 파악
  - [ ] 1.5 Agent04 룰 평가기가 Python인지 PHP인지 확인

- [ ] 2.0 Rules.yaml에 온톨로지 액션 추가 (개념이해 활동)
  - [ ] 2.1 `CU_A1_weak_point_detection` 룰에 `create_instance: 'mk-a04:WeakpointDetectionContext'` 추가
  - [ ] 2.2 `CU_A1_weak_point_detection` 룰에 `set_property: ('mk-a04:hasActivityType', '{activity_type}')` 추가
  - [ ] 2.3 `CU_A1_weak_point_detection` 룰에 `set_property: ('mk-a04:hasPauseFrequency', '{pause_frequency}')` 추가
  - [ ] 2.4 `CU_A1_weak_point_detection` 룰에 `set_property: ('mk-a04:hasPauseStage', '{pause_stage}')` 추가
  - [ ] 2.5 `CU_A2_tts_attention_pattern` 룰에 `create_instance: 'mk-a04:ActivityAnalysisContext'` 추가
  - [ ] 2.6 `CU_A2_tts_attention_pattern` 룰에 `set_property: ('mk-a04:hasGazeAttentionScore', '{gaze_attention_score}')` 추가
  - [ ] 2.7 `CU_A2_tts_attention_pattern` 룰에 `reason_over: 'mk-a04:ActivityAnalysisContext'` 추가
  - [ ] 2.8 `CU_A3_concept_confusion_detection` 룰에 `create_instance: 'mk-a04:ActivityAnalysisContext'` 추가
  - [ ] 2.9 `CU_A3_concept_confusion_detection` 룰에 `set_property: ('mk-a04:hasConceptConfusionDetected', '{concept_confusion_detected}')` 추가
  - [ ] 2.10 `CU_A3_concept_confusion_detection` 룰에 `set_property: ('mk-a04:hasConfusionType', '{confusion_type}')` 추가
  - [ ] 2.11 `CU_A3_concept_confusion_detection` 룰에 `reason_over: 'mk-a04:ActivityAnalysisContext'` 추가
  - [ ] 2.12 `CU_B1_persona_method_match` 룰에 `set_property: ('mk-a04:hasMethodPersonaMatchScore', '{method_persona_match_score}')` 추가
  - [ ] 2.13 `CU_C2_boredom_detection` 룰에 `set_property: ('mk-a04:hasBoredomDetected', '{boredom_detected}')` 추가
  - [ ] 2.14 모든 개념이해 룰에 `generate_reinforcement_plan: 'mk-a04:WeakpointAnalysisDecisionModel'` 추가

- [ ] 3.0 Rules.yaml에 온톨로지 액션 추가 (다른 활동)
  - [ ] 3.1 유형학습(TL) 룰에 온톨로지 액션 추가
  - [ ] 3.2 문제풀이(PS) 룰에 온톨로지 액션 추가
  - [ ] 3.3 오답노트(MN) 룰에 온톨로지 액션 추가
  - [ ] 3.4 질의응답(QA) 룰에 온톨로지 액션 추가
  - [ ] 3.5 복습활동(RA) 룰에 온톨로지 액션 추가
  - [ ] 3.6 포모도르(PO) 룰에 온톨로지 액션 추가

- [ ] 4.0 Agent Garden Service에 Agent04 연동 추가
  - [ ] 4.1 `executeAgent()` 메서드에서 Agent04 감지 로직 추가
  - [ ] 4.2 `executeAgent04WithRules()` 메서드 생성 (Agent01의 `executeAgent01WithRules` 참조)
  - [ ] 4.3 Agent04의 `rules.yaml` 경로 설정
  - [ ] 4.4 Agent04의 `rule_evaluator.php` 경로 설정
  - [ ] 4.5 Agent04의 `data_access.php` 경로 설정
  - [ ] 4.6 `executeAgent04WithRules()` 메서드에서 룰 평가기 호출
  - [ ] 4.7 `executeAgent04WithRules()` 메서드에서 `processOntologyActions('agent04', ...)` 호출
  - [ ] 4.8 에러 처리 및 로깅 추가

- [ ] 5.0 온톨로지 액션 핸들러 검증
  - [ ] 5.1 `OntologyActionHandler.php`의 `executeAction()` 메서드 검증
  - [ ] 5.2 `create_instance` 액션 처리 검증
  - [ ] 5.3 `set_property` 액션 처리 검증 (변수 치환 포함)
  - [ ] 5.4 `reason_over` 액션 처리 검증
  - [ ] 5.5 `generate_reinforcement_plan` 액션 처리 검증
  - [ ] 5.6 에이전트 ID 정규화 확인 (`agent04` vs `agent04_inspect_weakpoints`)

- [ ] 6.0 통합 테스트
  - [ ] 6.1 개념이해 활동 취약점 탐지 시나리오 테스트
  - [ ] 6.2 온톨로지 인스턴스 생성 확인
  - [ ] 6.3 온톨로지 추론 결과 확인
  - [ ] 6.4 보강 방안 생성 확인
  - [ ] 6.5 에러 발생 시 기본 동작 유지 확인
  - [ ] 6.6 로그 확인 (에러 메시지, 파일 경로, 라인 번호 포함)

---

**작성일**: 2025-01-27  
**다음 단계**: PRD 0002 (응답 생성 통합)

