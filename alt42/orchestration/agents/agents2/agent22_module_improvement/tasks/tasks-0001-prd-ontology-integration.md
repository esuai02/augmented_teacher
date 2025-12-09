# 작업계획: Agent22 Module Improvement 온톨로지 통합

**기반 PRD**: `0001-prd-ontology-integration.md`  
**생성일**: 2025-01-27  
**버전**: 1.0

---

## Relevant Files

### 신규 생성 파일
- `agent22_module_improvement/ontology/UniversalOntologyEngine.php` - 범용 온톨로지 엔진 (Agent01의 OntologyEngine 기반)
- `agent22_module_improvement/ontology/OntologyActionHandler.php` - 온톨로지 액션 핸들러 (범용 버전)
- `agent22_module_improvement/ontology/OntologyFileLoader.php` - 온톨로지 파일 로더 및 검증
- `agent22_module_improvement/ontology/OntologyValidator.php` - 온톨로지 파일 유효성 검증
- `agent22_module_improvement/ontology/OntologyConfig.php` - 온톨로지 설정 관리

### 수정 파일
- `agent22_module_improvement/ui/agent_garden.service.php` - 온톨로지 엔진 통합 및 모든 에이전트 지원
- `agent22_module_improvement/ui/index.php` - 온톨로지 상태 표시 (선택사항)

### 참조 파일
- `agent01_onboarding/ontology/OntologyEngine.php` - 기존 구현 참조
- `agent01_onboarding/ontology/OntologyActionHandler.php` - 기존 구현 참조
- `ontology_engineering/modules/agent22.owl` - Agent22 온톨로지 파일
- `ontology_engineering/modules/*.owl` - 다른 에이전트 온톨로지 파일들

### Notes
- 모든 PHP 파일은 Moodle 환경에서 동작하도록 작성 (`include_once("/home/moodle/public_html/moodle/config.php")`)
- 에러 메시지는 파일 경로와 라인 번호를 포함해야 함
- 기존 Agent01의 온톨로지 엔진 구현을 참조하여 범용화

---

## Tasks

### Phase 1: 범용 온톨로지 엔진 구축

- [ ] 1.0 UniversalOntologyEngine.php 구현
  - [ ] 1.1 Agent01의 OntologyEngine.php를 참조하여 범용 온톨로지 엔진 클래스 구조 설계
  - [ ] 1.2 에이전트별 온톨로지 파일 경로를 동적으로 받을 수 있도록 생성자 수정
  - [ ] 1.3 `createInstance()` 메서드를 범용적으로 구현 (에이전트 ID 파라미터 추가)
  - [ ] 1.4 `setProperty()` 메서드를 범용적으로 구현
  - [ ] 1.5 `reasonOver()` 메서드를 범용적으로 구현
  - [ ] 1.6 `generateStrategy()` 메서드를 범용적으로 구현
  - [ ] 1.7 `generateProcedure()` 메서드를 범용적으로 구현
  - [ ] 1.8 `getInstance()` 메서드를 범용적으로 구현
  - [ ] 1.9 데이터베이스 스키마 확인 및 필요시 `agent_id` 컬럼 추가 로직 구현

- [ ] 2.0 OntologyFileLoader.php 구현
  - [ ] 2.1 온톨로지 파일 경로를 에이전트 ID로부터 자동 생성하는 로직 구현
  - [ ] 2.2 온톨로지 파일 존재 여부 확인 기능 구현
  - [ ] 2.3 온톨로지 파일 읽기 기능 구현 (파일 내용 반환)
  - [ ] 2.4 온톨로지 파일 캐싱 기능 구현 (메모리 캐싱)
  - [ ] 2.5 에러 처리 및 로깅 기능 구현

- [ ] 3.0 OntologyValidator.php 구현
  - [ ] 3.1 OWL 파일 기본 구조 검증 (XML 파싱 가능 여부 확인)
  - [ ] 3.2 필수 네임스페이스 확인 (mk:, at: 등)
  - [ ] 3.3 기본 클래스 존재 여부 확인
  - [ ] 3.4 검증 결과를 구조화된 형태로 반환

- [ ] 4.0 OntologyConfig.php 구현
  - [ ] 4.1 에이전트별 온톨로지 파일 경로 매핑 정의
  - [ ] 4.2 온톨로지 네임스페이스 설정 관리
  - [ ] 4.3 온톨로지 프리픽스 설정 관리 (mk:, at: 등)
  - [ ] 4.4 설정 파일 또는 상수로 관리

### Phase 2: 온톨로지 액션 핸들러 구축

- [ ] 5.0 OntologyActionHandler.php 구현 (범용 버전)
  - [ ] 5.1 Agent01의 OntologyActionHandler.php를 참조하여 범용 버전 설계
  - [ ] 5.2 `executeAction()` 메서드를 범용적으로 구현 (에이전트 ID 파라미터 추가)
  - [ ] 5.3 `parseAction()` 메서드 구현 (문자열/배열 형식 모두 지원)
  - [ ] 5.4 `handleCreateInstance()` 메서드 구현
  - [ ] 5.5 `handleSetProperty()` 메서드 구현
  - [ ] 5.6 `handleReasonOver()` 메서드 구현
  - [ ] 5.7 `handleGenerateStrategy()` 메서드 구현
  - [ ] 5.8 `handleGenerateProcedure()` 메서드 구현
  - [ ] 5.9 에러 처리 및 로깅 강화

### Phase 3: Agent Garden Service 통합

- [ ] 6.0 agent_garden.service.php 수정
  - [ ] 6.1 범용 온톨로지 엔진 및 액션 핸들러 로드 로직 추가
  - [ ] 6.2 `executeAgent()` 메서드에서 모든 에이전트에 대해 온톨로지 액션 감지 로직 추가
  - [ ] 6.3 `executeAgent01WithRules()` 메서드의 온톨로지 처리 로직을 범용 함수로 추출
  - [ ] 6.4 `executeAgentWithOntology()` 범용 메서드 구현 (모든 에이전트 공통 사용)
  - [ ] 6.5 에이전트별 온톨로지 파일 경로 자동 감지 로직 구현
  - [ ] 6.6 온톨로지 액션 처리 결과를 응답에 통합하는 로직 구현
  - [ ] 6.7 에러 발생 시 기본 동작 유지 로직 구현

- [ ] 7.0 온톨로지 액션 자동 감지 및 처리
  - [ ] 7.1 Rules.yaml의 액션에서 온톨로지 액션 패턴 감지 로직 구현
  - [ ] 7.2 감지된 온톨로지 액션을 OntologyActionHandler로 전달
  - [ ] 7.3 온톨로지 처리 결과를 decision 배열에 추가
  - [ ] 7.4 온톨로지 결과를 응답 생성에 활용하는 로직 구현

### Phase 4: 데이터베이스 스키마 확장

- [ ] 8.0 데이터베이스 스키마 확인 및 확장
  - [ ] 8.1 기존 `alt42_ontology_instances` 테이블 구조 확인
  - [ ] 8.2 `agent_id` 컬럼 존재 여부 확인
  - [ ] 8.3 `agent_id` 컬럼이 없으면 추가하는 마이그레이션 로직 구현
  - [ ] 8.4 `agent_id` 인덱스 추가 (성능 최적화)
  - [ ] 8.5 UniversalOntologyEngine에서 `agent_id`를 저장하도록 수정

### Phase 5: 테스트 및 검증

- [ ] 9.0 Agent01 온톨로지 통합 테스트
  - [ ] 9.1 Agent01의 기존 온톨로지 기능이 정상 동작하는지 확인
  - [ ] 9.2 범용 엔진으로 전환 후 기존 기능 유지 확인
  - [ ] 9.3 온톨로지 인스턴스 생성/조회 테스트

- [ ] 10.0 다른 에이전트 온톨로지 통합 테스트
  - [ ] 10.1 Agent02~Agent21의 온톨로지 파일 존재 여부 확인
  - [ ] 10.2 각 에이전트의 온톨로지 파일 로드 테스트
  - [ ] 10.3 온톨로지 액션이 있는 에이전트의 액션 처리 테스트

- [ ] 11.0 에러 처리 테스트
  - [ ] 11.1 온톨로지 파일이 없는 경우 에러 처리 테스트
  - [ ] 11.2 손상된 온톨로지 파일에 대한 에러 처리 테스트
  - [ ] 11.3 잘못된 온톨로지 액션에 대한 에러 처리 테스트
  - [ ] 11.4 에러 발생 시 기본 동작 유지 확인

- [ ] 12.0 성능 테스트
  - [ ] 12.1 온톨로지 파일 캐싱 효과 확인
  - [ ] 12.2 대량 인스턴스 조회 성능 테스트
  - [ ] 12.3 응답 시간 측정 (기존 대비 20% 이내 증가 확인)

### Phase 6: 문서화 및 정리

- [ ] 13.0 문서화
  - [ ] 13.1 온톨로지 엔진 사용법 문서 작성
  - [ ] 13.2 에이전트별 온톨로지 통합 가이드 작성
  - [ ] 13.3 온톨로지 액션 정의 가이드 작성
  - [ ] 13.4 트러블슈팅 가이드 작성

- [ ] 14.0 코드 정리
  - [ ] 14.1 불필요한 주석 제거
  - [ ] 14.2 코드 스타일 통일
  - [ ] 14.3 에러 메시지 일관성 확인
  - [ ] 14.4 로깅 메시지 일관성 확인

---

## 구현 우선순위

### 높음 (즉시 구현)
1. UniversalOntologyEngine.php 기본 구조 구현
2. OntologyFileLoader.php 구현
3. agent_garden.service.php 통합

### 중간 (1주 내)
4. OntologyActionHandler.php 범용 버전 구현
5. 데이터베이스 스키마 확장
6. 온톨로지 액션 자동 감지 및 처리

### 낮음 (2주 내)
7. OntologyValidator.php 구현
8. 성능 최적화
9. 문서화

---

## 주의사항

1. **기존 Agent01 기능 유지**: Agent01의 기존 온톨로지 기능이 정상 동작하도록 주의
2. **에러 처리**: 온톨로지 관련 에러가 발생해도 기본 동작은 유지되어야 함
3. **성능**: 온톨로지 처리로 인한 성능 저하를 최소화해야 함
4. **호환성**: 기존 데이터베이스 스키마와의 호환성 유지
5. **로깅**: 모든 에러는 파일 경로와 라인 번호를 포함하여 로깅

---

**작업계획 작성일**: 2025-01-27  
**작성자**: AI Assistant  
**다음 단계**: Phase 1 작업 시작

