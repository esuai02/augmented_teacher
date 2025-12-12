# 📚 AlphaTutor42 온톨로지 시스템 통합 문서 목차
**Ontology System Documentation Index**

> **버전**: 1.0  
> **최종 업데이트**: 2025-11-20  
> **목적**: 전체 온톨로지 관련 문서의 통합 네비게이션

---

## 📖 문서 구조 개요

이 문서는 AlphaTutor42 시스템의 온톨로지 관련 문서들을 의미 단위로 분류하여 제공합니다.

---

## 🏗️ 1. 시스템 아키텍처 & 철학

### 1.1. 핵심 설계 문서
- **[AlphaTutor42_v3_Architecture.md](agents/docs/AlphaTutor42_v3_Architecture.md)** 🆕
  - AlphaTutor42 v3.0 계측형 모듈러 플랫폼 아키텍처
  - 4-Layer 구조 (Reasoning, Ontology, Event, Runtime)
  - 의도를 가진 온톨로지 설계
  - 5대 기능 모듈 (Profile, Learning, Emotion, Interaction, Meta)

- **[firstprinciple.md](agents/docs/firstprinciple.md)**
  - 22개 에이전트 간 상호의존성 문서 (원본 대화록)
  - 설계 철학 및 진화 과정

---

## 🔗 2. 온톨로지 통합 가이드

### 2.1. 통합 문서 (Central Guides)
- **[01_GUIDE_ONTOLOGY_ENGINE_INTEGRATION.md](01_GUIDE_ONTOLOGY_ENGINE_INTEGRATION.md)**
  - 온톨로지 엔진 연계 메커니즘
  - 공통 액션 타입 (create_instance, reason_over, generate_strategy 등)
  - 에이전트별 통합 방법

- **[02_CHECKLIST_ONTOLOGY_INTEGRATION.md](02_CHECKLIST_ONTOLOGY_INTEGRATION.md)**
  - 온톨로지 통합 체크리스트
  - Phase별 리팩터링 플랜
  - 에이전트별 진행 상황

- **[03_REPORT_ONTOLOGY_RULE_INTEGRATION_CHECK.md](03_REPORT_ONTOLOGY_RULE_INTEGRATION_CHECK.md)**
  - 온톨로지-룰 연동 검증 리포트
  - Agent01, Agent04 통합 현황
  - 향후 조치 계획

### 2.2. 워크플로우
- **[AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md](AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md)**
  - 에이전트 온톨로지 구현 워크플로우
  - 단계별 가이드 (설계 → 구현 → 검증)

---

## 📊 3. 온톨로지 데이터 & 원칙

### 3.1. 트리플 데이터
- **[triples_all_agents.md](triples_all_agents.md)**
  - 전체 에이전트의 온톨로지 트리플
  - 통합 데이터셋

- **[triples_summary.md](triples_summary.md)**
  - 트리플 요약 및 통계
  - 에이전트별 분석

- **[triples_cleaned.txt](triples_cleaned.txt)**
  - 정제된 트리플 데이터 (텍스트)

### 3.2. 설계 원칙
- **[priciples_주어.md](priciples_주어.md)**
  - 주어 선택 기준 (5단계 필터링)

- **[priciples_서술어.md](priciples_서술어.md)**
  - 서술어 설계 기준 (4계층 분류)

---

## 🔍 4. 온톨로지 도구 & 쿼리

### 4.1. 쿼리 & 추론
- **[sparql_queries.md](sparql_queries.md)**
  - SPARQL 쿼리 예제 (17개)
  - 학생/개념/활동 조회

- **[inference_rules.md](inference_rules.md)**
  - 추론 규칙 정의 (18개)
  - 자동 추론 로직

### 4.2. 검증 & 사용 사례
- **[ontology_validation.md](ontology_validation.md)**
  - 온톨로지 검증 및 최적화 가이드
  - 일관성 체크, 성능 개선

- **[use_cases.md](use_cases.md)**
  - 온톨로지 활용 사례

---

## 🛠️ 5. 개발 도구

### 5.1. 스크립트
- **[generate_ontology.py](generate_ontology.py)**
  - 온톨로지 자동 생성 스크립트

- **[consistency_check.py](consistency_check.py)**
  - 일관성 검증 스크립트

### 5.2. Protégé 가이드
- **[protege_guide.md](protege_guide.md)**
  - Protégé 사용 가이드

- **[protege_open_instructions.md](protege_open_instructions.md)**
  - Protégé 파일 열기 방법

- **[protege_troubleshooting.md](protege_troubleshooting.md)**
  - Protégé 문제 해결

---

## 📋 6. 프로젝트 관리

### 6.1. 메인 문서
- **[README.md](README.md)**
  - 온톨로지 엔지니어링 프로젝트 개요
  - 파일 구조, 완료 작업, 통계

### 6.2. 정리 계획
- **[ONTOLOGY_DOCS_CLEANUP_PLAN.md](ONTOLOGY_DOCS_CLEANUP_PLAN.md)**
  - 중복 문서 정리 계획
  - 통합 전략

---

## 👥 7. 에이전트별 온톨로지

### 7.1. Agent 01 (Onboarding)
- **[agent01_onboarding/ontology/](agent01_onboarding/ontology/)**
  - IMPLEMENTATION_SUMMARY.md
  - ONTOLOGY_INTEGRATION_ISSUES.md
  - 참조 링크: 통합 문서로 이동됨

### 7.2. Agent 04 (Weakpoints)
- **[agent04_inspect_weakpoints/ontology/](agent04_inspect_weakpoints/ontology/)**
  - IMPLEMENTATION_SUMMARY.md
  - 참조 링크: 통합 문서로 이동됨

### 7.3. Agent 04 Tasks
- **[agent04_inspect_weakpoints/tasks/](agent04_inspect_weakpoints/tasks/)**
  - 작업 문서

### 7.4. Agent 22 (Module Improvement)
- **[agent22_module_improvement/tasks/](agent22_module_improvement/tasks/)**
  - 0001-prd-ontology-integration.md (PRD)
  - tasks-0001-prd-ontology-integration.md (작업 계획)

---

## 🎯 권장 학습 경로

### 초급 (신규 사용자)
1.  **README.md** → 프로젝트 전체 이해
2.  **AlphaTutor42_v3_Architecture.md** → 시스템 아키텍처 파악
3.  **01_GUIDE_ONTOLOGY_ENGINE_INTEGRATION.md** → 통합 방법 학습

### 중급 (개발자)
1.  **02_CHECKLIST_ONTOLOGY_INTEGRATION.md** → 통합 체크리스트 확인
2.  **AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md** → 워크플로우 따라하기
3.  **sparql_queries.md** + **inference_rules.md** → 쿼리 및 추론 학습

### 고급 (아키텍트)
1.  **AlphaTutor42_v3_Architecture.md** → v3.0 설계 심화
2.  **priciples_주어.md** + **priciples_서술어.md** → 설계 원칙 숙지
3.  **ontology_validation.md** → 검증 및 최적화

---

## 🔄 최근 업데이트

### 2025-11-20
- ✅ AlphaTutor42 v3.0 아키텍처 문서 추가
- ✅ 통합 문서 파일명 개선 (번호 및 유형 접두사 추가)
- ✅ `agents/docs/` 디렉토리를 docindex.php에 추가
- ✅ README.md 업데이트 (통합 문서 섹션)

---

## 📞 연락처 & 기여

문서 관련 질문이나 개선 사항은 프로젝트 관리자에게 문의하세요.

**문서 버전**: 1.0  
**관리**: AlphaTutor42 Team
