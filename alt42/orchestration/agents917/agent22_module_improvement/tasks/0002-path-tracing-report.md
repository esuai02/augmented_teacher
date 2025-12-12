# 온톨로지 동작 경로 추적 보고서

**생성일**: 2025-01-27  
**조사 대상**: `index.php`에서 요청 시 온톨로지 동작 경로  
**목적**: 온톨로지가 제대로 동작하는지 경로 추적 및 누락된 부분 파악

---

## 1. 요청 흐름 경로 추적

### 1.1 프론트엔드 → 백엔드 흐름

```
[1] index.php (UI)
    ↓ 사용자 입력
[2] agent_garden.js::sendMessage()
    ↓ POST 요청
[3] agent_garden.controller.php::executeAgent()
    ↓ 서비스 호출
[4] agent_garden.service.php::executeAgent()
    ↓ Agent01인 경우
[5] agent_garden.service.php::executeAgent01WithRules()
    ↓ 룰 평가
[6] OnboardingRuleEvaluator::evaluate()
    ↓ decision 반환 (actions 포함)
[7] agent_garden.service.php::processOntologyActions()
    ↓ 온톨로지 액션 처리
[8] OntologyActionHandler::executeAction()
    ↓ 온톨로지 엔진 호출
[9] UniversalOntologyEngine::createInstance() / reasonOver() 등
    ↓ 인스턴스 저장
[10] DB: alt42_ontology_instances 테이블
```

---

## 2. 발견된 문제점

### 2.1 에이전트 ID 정규화 누락 ⚠️

**위치**: `agent_garden.service.php::processOntologyActions()` 라인 20

**문제**:
- 에이전트 ID를 정규화하지 않고 그대로 사용
- `OntologyConfig::normalizeAgentId()`를 사용해야 함

**영향**:
- 에이전트 ID 형식이 일치하지 않으면 온톨로지 파일을 찾지 못할 수 있음
- 예: 'agent1' vs 'agent01'

**수정 필요**: ✅

### 2.2 에러 처리 강화 필요 ⚠️

**위치**: `agent_garden.service.php::processOntologyActions()` 라인 36-37

**문제**:
- `OntologyActionHandler` 생성 시 에러가 발생하면 전체 프로세스가 중단될 수 있음
- try-catch로 감싸져 있지만, 생성자에서 발생하는 에러는 처리되지 않을 수 있음

**영향**:
- 온톨로지 엔진 초기화 실패 시 기본 동작도 중단될 수 있음

**수정 필요**: ✅

### 2.3 다른 에이전트에 대한 온톨로지 처리 누락 ⚠️

**위치**: `agent_garden.service.php::executeAgent()` 라인 91-94

**문제**:
- 현재는 Agent01만 `processOntologyActions()`를 호출
- 다른 에이전트도 rules.yaml을 사용한다면 온톨로지 처리가 필요함

**영향**:
- Agent02~Agent21은 온톨로지 액션이 있어도 처리되지 않음

**수정 필요**: ⚠️ (향후 확장 시 필요)

### 2.4 온톨로지 파일 로드 검증 누락 ⚠️

**위치**: `agent_garden.service.php::processOntologyActions()`

**문제**:
- 온톨로지 파일이 존재하는지 확인하지 않음
- 파일이 없어도 에러 없이 진행됨

**영향**:
- 온톨로지 파일이 없는 에이전트에서도 처리 시도
- 불필요한 로그 발생

**수정 필요**: ✅

### 2.5 의존성 로드 순서 확인 필요 ⚠️

**위치**: `OntologyActionHandler.php` → `UniversalOntologyEngine.php`

**확인 사항**:
- `UniversalOntologyEngine`이 `OntologyConfig`, `OntologyFileLoader`를 require
- `OntologyActionHandler`가 `UniversalOntologyEngine`을 require
- 순환 참조 없음 ✅

**상태**: 정상

---

## 3. 수정 필요 사항

### 3.1 즉시 수정 필요

1. **에이전트 ID 정규화 추가**
   - `processOntologyActions()`에서 `OntologyConfig::normalizeAgentId()` 사용

2. **온톨로지 파일 존재 여부 확인**
   - `OntologyFileLoader::exists()` 사용하여 파일 존재 확인

3. **에러 처리 강화**
   - `OntologyActionHandler` 생성 시 에러 처리 강화

### 3.2 향후 개선 사항

1. **다른 에이전트 온톨로지 통합**
   - Agent02~Agent21도 rules.yaml을 사용한다면 온톨로지 처리 추가

2. **온톨로지 파일 검증**
   - `OntologyValidator::validate()` 사용하여 파일 유효성 검증

---

## 4. 경로 추적 결과

### 4.1 정상 동작 경로 ✅

1. ✅ `index.php` → `agent_garden.js` → `agent_garden.controller.php` 연결 정상
2. ✅ `agent_garden.controller.php` → `agent_garden.service.php` 연결 정상
3. ✅ `processOntologyActions()` 함수 존재 및 호출됨
4. ✅ `OntologyActionHandler.php` 경로 정상 (`../ontology/OntologyActionHandler.php`)
5. ✅ `UniversalOntologyEngine.php` 의존성 로드 정상

### 4.2 개선 필요 경로 ⚠️

1. ⚠️ 에이전트 ID 정규화 누락
2. ⚠️ 온톨로지 파일 존재 여부 확인 누락
3. ⚠️ 에러 처리 강화 필요

---

## 5. 수정 계획

### 우선순위 1 (즉시 수정)
- [ ] 에이전트 ID 정규화 추가
- [ ] 온톨로지 파일 존재 여부 확인 추가
- [ ] 에러 처리 강화

### 우선순위 2 (향후 개선)
- [ ] 다른 에이전트 온톨로지 통합
- [ ] 온톨로지 파일 검증 추가

---

**조사 완료일**: 2025-01-27  
**조사자**: AI Assistant  
**다음 단계**: 발견된 문제점 수정

