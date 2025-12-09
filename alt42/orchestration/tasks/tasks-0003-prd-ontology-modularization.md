## Relevant Files

- `alt42/orchestration/agents/ontology_engineering/tools/split_ontology.py` - 온톨로지 분할 도구 (Agent 주석 기반 자동 분할)
- `alt42/orchestration/agents/ontology_engineering/tools/split_ontology.test.py` - split_ontology.py 단위 테스트
- `alt42/orchestration/agents/ontology_engineering/tools/incremental_loader.py` - 증분 온톨로지 로더 클래스
- `alt42/orchestration/agents/ontology_engineering/tools/incremental_loader.test.py` - incremental_loader.py 단위 테스트
- `alt42/orchestration/agents/ontology_engineering/tools/incremental_validator.py` - 증분 검증 시스템
- `alt42/orchestration/agents/ontology_engineering/tools/incremental_validator.test.py` - incremental_validator.py 단위 테스트
- `alt42/orchestration/agents/ontology_engineering/modules/core.owl` - 핵심 클래스 모듈 (Student, Goal, Plan 등)
- `alt42/orchestration/agents/ontology_engineering/modules/properties.owl` - 공통 속성 정의 모듈
- `alt42/orchestration/agents/ontology_engineering/modules/agent01.owl` - Agent 01 관련 모듈
- `alt42/orchestration/agents/ontology_engineering/modules/agent02.owl` - Agent 02 관련 모듈
- `alt42/orchestration/agents/ontology_engineering/modules/agentXX.owl` - 기타 Agent 모듈들 (Agent 03-22)
- `alt42/orchestration/agents/ontology_engineering/alphatutor_ontology_main.owl` - 메인 온톨로지 (owl:imports 포함)
- `alt42/orchestration/agents/ontology_engineering/consistency_check.py` - 기존 일관성 검증 스크립트 (모듈화 지원 수정)
- `alt42/orchestration/agents/ontology_engineering/generate_ontology.py` - 기존 온톨로지 생성 스크립트 (모듈화 지원 수정)
- `alt42/orchestration/agents/ontology_engineering/README.md` - 모듈화 사용 가이드 업데이트

### Notes

- 모든 Python 테스트는 `pytest`를 사용하여 실행합니다.
- OWL 파일은 XML 형식이므로 XML 파서를 사용하여 검증합니다.
- 캐시 디렉토리 `.ontology_cache`는 `.gitignore`에 추가해야 합니다.
- 기존 `alphatutor_ontology.owl` 파일은 백업용으로 유지합니다.

## Tasks

- [ ] 1.0 온톨로지 분할 도구 개발 (split_ontology.py)
  - [ ] 1.1 Agent 주석 패턴 분석 및 파싱 로직 구현 (`<!-- Agent XX: ... -->` 형식 감지)
  - [ ] 1.2 Agent별 섹션 추출 로직 구현 (주석부터 다음 Agent 주석 또는 파일 끝까지)
  - [ ] 1.3 핵심 클래스 식별 로직 구현 (Student, Goal, Plan, Routine 등 core.owl로 분리)
  - [ ] 1.4 공통 속성 추출 로직 구현 (ObjectProperty, DataProperty 정의를 properties.owl로 분리)
  - [ ] 1.5 모듈 파일 생성 로직 구현 (각 Agent별 modules/agentXX.owl 파일 생성)
  - [ ] 1.6 OWL XML 헤더 및 네임스페이스 생성 로직 구현 (각 모듈에 적절한 헤더 추가)
  - [ ] 1.7 XML 구조 무결성 검증 로직 구현 (잘못된 XML 생성 방지)
  - [ ] 1.8 분할 결과 리포트 생성 (생성된 모듈 수, 각 모듈의 클래스 수 등)
  - [ ] 1.9 split_ontology.py 단위 테스트 작성 (Agent 주석 파싱, 파일 생성 검증)
  - [ ] 1.10 기존 alphatutor_ontology.owl 파일로 분할 테스트 실행 및 검증

- [ ] 2.0 메인 온톨로지 파일 생성
  - [ ] 2.1 메인 온톨로지 템플릿 생성 (owl:imports를 포함한 기본 구조)
  - [ ] 2.2 생성된 모듈 목록 자동 감지 로직 구현 (modules/ 디렉토리 스캔)
  - [ ] 2.3 owl:imports 엘리먼트 자동 생성 로직 구현 (모든 모듈 파일 import)
  - [ ] 2.4 네임스페이스 일관성 검증 (모든 모듈이 동일한 네임스페이스 사용 확인)
  - [ ] 2.5 alphatutor_ontology_main.owl 파일 생성
  - [ ] 2.6 메인 온톨로지 파일 검증 (XML 유효성, imports 정확성)

- [ ] 3.0 증분 온톨로지 로더 개발 (incremental_loader.py)
  - [ ] 3.1 IncrementalOntologyLoader 클래스 기본 구조 구현 (캐시 디렉토리 관리)
  - [ ] 3.2 파일 해시 계산 로직 구현 (MD5 또는 SHA256 사용)
  - [ ] 3.3 캐시 로드/저장 메커니즘 구현 (파일 해시 기반 캐시 키 관리)
  - [ ] 3.4 모듈별 캐싱 로직 구현 (변경되지 않은 모듈은 캐시에서 로드)
  - [ ] 3.5 Agent 번호 기반 모듈 로딩 메서드 구현 (load_classes_by_agent)
  - [ ] 3.6 URI 접두사 기반 클래스 로딩 메서드 구현 (get_classes_by_prefix)
  - [ ] 3.7 스트리밍 XML 파싱 구현 (메모리 효율적 처리, iterparse 사용)
  - [ ] 3.8 캐시 무효화 메커니즘 구현 (파일 변경 감지 시 캐시 삭제)
  - [ ] 3.9 incremental_loader.py 단위 테스트 작성 (캐싱, 로딩 로직 검증)
  - [ ] 3.10 성능 테스트 작성 (로딩 시간, 메모리 사용량 측정)

- [ ] 4.0 기존 코드 통합 및 수정
  - [ ] 4.1 consistency_check.py 수정 (모듈화된 온톨로지 지원)
  - [ ] 4.2 generate_ontology.py 수정 (모듈화된 구조에서 작동하도록 수정)
  - [ ] 4.3 owl_parser.py 확인 및 수정 (모듈화된 온톨로지 파싱 지원)
  - [ ] 4.4 기존 스크립트들이 메인 온톨로지 파일을 통해 전체 온톨로지 로드하도록 수정
  - [ ] 4.5 통합 테스트 작성 (기존 스크립트들이 모듈화된 온톨로지에서 정상 작동 확인)

- [ ] 5.0 증분 검증 시스템 개발 (incremental_validator.py)
  - [ ] 5.1 IncrementalValidator 클래스 기본 구조 구현 (검증 캐시 관리)
  - [ ] 5.2 파일 해시 기반 검증 캐시 구현 (JSON 형식으로 캐시 저장)
  - [ ] 5.3 캐시된 검증 결과 재사용 로직 구현 (변경되지 않은 모듈은 캐시 사용)
  - [ ] 5.4 변경된 모듈만 재검증 로직 구현 (파일 해시 비교)
  - [ ] 5.5 검증 결과 캐시 저장 로직 구현 (타임스탬프 포함)
  - [ ] 5.6 캐시 무효화 메커니즘 구현 (수동/자동 캐시 삭제)
  - [ ] 5.7 incremental_validator.py 단위 테스트 작성 (캐싱, 검증 로직 검증)
  - [ ] 5.8 성능 테스트 작성 (검증 시간 측정, 캐시 효과 확인)

- [ ] 6.0 문서화 및 가이드 작성
  - [ ] 6.1 모듈화된 온톨로지 사용 가이드 작성 (README.md 업데이트)
  - [ ] 6.2 모듈 구조 및 네이밍 컨벤션 문서화
  - [ ] 6.3 마이그레이션 가이드 작성 (기존 단일 파일에서 모듈화로 전환하는 방법)
  - [ ] 6.4 split_ontology.py 사용법 문서화
  - [ ] 6.5 IncrementalOntologyLoader 사용 예제 작성
  - [ ] 6.6 IncrementalValidator 사용 예제 작성
  - [ ] 6.7 Protégé에서 모듈화된 온톨로지 사용 방법 문서화
  - [ ] 6.8 트러블슈팅 가이드 작성 (일반적인 문제 및 해결 방법)

- [ ] 7.0 테스트 및 검증
  - [ ] 7.1 분할된 모듈들이 원본과 논리적으로 동일한지 검증 (SPARQL 쿼리 비교)
  - [ ] 7.2 메인 온톨로지를 통한 전체 로딩이 원본과 동일한지 검증
  - [ ] 7.3 Protégé에서 메인 온톨로지 로딩 테스트 (owl:imports 작동 확인)
  - [ ] 7.4 성능 벤치마크 실행 (로딩 시간, 메모리 사용량 측정)
  - [ ] 7.5 기존 SPARQL 쿼리가 모듈화 후에도 동일하게 작동하는지 테스트
  - [ ] 7.6 모든 Agent 모듈이 올바르게 생성되었는지 검증 (클래스 수, 관계 수 확인)

