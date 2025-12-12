# 온톨로지 문서 중복 분석 및 정리 계획

**생성일**: 2025-01-27  
**목적**: 온톨로지 관련 문서들의 중복성 파악 및 정리 방안 제시

---

## 📊 중복 문서 현황 분석

### 1. 설계 문서 (Design Documents) - 중복 발견

#### 1.1 원본 위치
- `alt42/orchestration/agents/ontology_engineering/DesigningOfOntology/`
  - `01_ontology_specification.md` (Agent01 기준)
  - `02_ontology_types.md`
  - `03_ontology_relations.md`
  - `04_ontology_constraints.md`
  - `05_ontology_context_tree.md` (Agent01 기준)
  - `06_jsonld_mapping.md`
  - `07_three_layer_ontology_architecture.md` (Agent01 기준)

#### 1.2 복사본 위치
- `alt42/orchestration/agents/agent04_inspect_weakpoints/ontology/`
  - `01_ontology_specification.md` (Agent04 버전)
  - `02_ontology_types.md`
  - `03_ontology_relations.md`
  - `04_ontology_constraints.md`
  - `05_ontology_context_tree.md` (Agent04 버전)
  - `06_jsonld_mapping.md`
  - `07_three_layer_ontology_architecture.md` (Agent04 버전)

**중복성 평가**: ⚠️ **부분 중복**
- 구조와 형식은 동일하지만 에이전트별 내용이 다름
- Agent01과 Agent04는 서로 다른 온톨로지 구조를 가짐

**정리 방안**:
- ✅ 유지: 각 에이전트별 설계 문서는 유지 (에이전트별 차이점이 있음)
- 📝 개선: 원본 위치에 범용 템플릿 추가, 각 에이전트는 템플릿을 참조하도록 문서화

---

### 2. 통합 관련 문서 (Integration Documents) - 중복 발견

#### 2.1 ONTOLOGY_INTEGRATION_CHECKLIST.md

**위치**:
- `agent01_onboarding/ontology/ONTOLOGY_INTEGRATION_CHECKLIST.md`
- `agent04_inspect_weakpoints/ontology/ONTOLOGY_INTEGRATION_CHECKLIST.md`

**중복성 평가**: ⚠️ **높은 중복**
- 두 문서의 구조와 체크리스트 항목이 거의 동일
- 에이전트별 차이점은 최소한 (Agent04는 `generate_reinforcement_plan` 추가 정도)

**정리 방안**:
- ✅ **통합 권장**: 범용 체크리스트로 통합
- 📝 위치: `ontology_engineering/docs/ONTOLOGY_INTEGRATION_CHECKLIST.md`
- 📝 각 에이전트 폴더에는 심볼릭 링크 또는 참조 문서만 유지

#### 2.2 ONTOLOGY_ENGINE_INTEGRATION.md

**위치**:
- `agent01_onboarding/ontology/ONTOLOGY_ENGINE_INTEGRATION.md`
- `agent04_inspect_weakpoints/ontology/ONTOLOGY_ENGINE_INTEGRATION.md`

**중복성 평가**: ⚠️ **높은 중복**
- 핵심 메커니즘은 동일 (create_instance, reason_over 등)
- 에이전트별 차이점: Agent01은 `generate_strategy`, Agent04는 `generate_reinforcement_plan`

**정리 방안**:
- ✅ **통합 권장**: 범용 가이드로 통합, 에이전트별 예시 섹션 추가
- 📝 위치: `ontology_engineering/docs/ONTOLOGY_ENGINE_INTEGRATION.md`
- 📝 각 에이전트 폴더에는 심볼릭 링크 또는 참조 문서만 유지

#### 2.3 ONTOLOGY_RULE_INTEGRATION_CHECK.md

**위치**:
- `agent01_onboarding/ontology/ONTOLOGY_RULE_INTEGRATION_CHECK.md`
- `agent04_inspect_weakpoints/ontology/ONTOLOGY_RULE_INTEGRATION_CHECK.md`

**중복성 평가**: ⚠️ **높은 중복**
- 룰 통합 체크 항목이 거의 동일

**정리 방안**:
- ✅ **통합 권장**: 범용 체크리스트로 통합

---

### 3. 구현 워크플로우 문서 - 중복 없음 (범용 문서)

#### 3.1 AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md

**위치**:
- `ontology_engineering/docs/AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md`

**평가**: ✅ **범용 문서로 적절**
- Agent01 구현 경험을 바탕으로 작성된 범용 가이드
- 다른 에이전트에서도 활용 가능한 구조

**정리 방안**:
- ✅ 유지: 현재 위치가 적절함
- 📝 개선: Agent04 사례 추가 권장

---

### 4. PRD 문서 (Tasks) - 중복 발견

#### 4.1 tasks-0001-prd-ontology-integration.md

**위치**:
- `agent22_module_improvement/tasks/tasks-0001-prd-ontology-integration.md`
- `agent22_module_improvement/tasks/0001-prd-ontology-integration.md` (중복?)

**중복성 평가**: ⚠️ **파일명 중복 가능성**
- `tasks-` 접두사 유무 차이만 있을 수 있음

**정리 방안**:
- 📝 확인 필요: 두 파일 내용 비교 후 중복이면 하나로 통합

#### 4.2 tasks-0003-prd-ontology-modularization.md

**위치**:
- `orchestration/tasks/tasks-0003-prd-ontology-modularization.md`
- `orchestration/tasks/0003-prd-ontology-modularization.md` (중복?)
- `orchestration1115ts/tasks/...` (백업 폴더?)

**중복성 평가**: ⚠️ **백업 폴더 중복**
- `orchestration1115ts`는 백업 폴더로 보임

**정리 방안**:
- 📝 확인 필요: 백업 폴더는 별도 관리, 작업 폴더는 정리

---

### 5. 기타 문서

#### 5.1 IMPLEMENTATION_SUMMARY.md

**위치**:
- `agent01_onboarding/ontology/IMPLEMENTATION_SUMMARY.md`
- `agent04_inspect_weakpoints/ontology/IMPLEMENTATION_SUMMARY.md`

**중복성 평가**: ✅ **에이전트별 구현 요약이므로 중복 아님**
- 각 에이전트의 구현 완료 상태를 기록한 문서

**정리 방안**:
- ✅ 유지: 각 에이전트별로 유지

#### 5.2 ONTOLOGY_INTEGRATION_ISSUES.md

**위치**:
- `agent01_onboarding/ontology/ONTOLOGY_INTEGRATION_ISSUES.md`

**평가**: ✅ **에이전트별 이슈 문서이므로 중복 아님**

**정리 방안**:
- ✅ 유지: Agent01 전용 이슈 문서

---

## 🎯 정리 우선순위

### Priority 1: 즉시 통합 (높은 중복)

1. **ONTOLOGY_INTEGRATION_CHECKLIST.md**
   - 통합 위치: `ontology_engineering/docs/ONTOLOGY_INTEGRATION_CHECKLIST.md`
   - 각 에이전트 폴더: 참조 링크만 유지

2. **ONTOLOGY_ENGINE_INTEGRATION.md**
   - 통합 위치: `ontology_engineering/docs/ONTOLOGY_ENGINE_INTEGRATION.md`
   - 각 에이전트 폴더: 참조 링크만 유지

3. **ONTOLOGY_RULE_INTEGRATION_CHECK.md**
   - 통합 위치: `ontology_engineering/docs/ONTOLOGY_RULE_INTEGRATION_CHECK.md`
   - 각 에이전트 폴더: 참조 링크만 유지

### Priority 2: 확인 후 정리 (중복 가능성)

1. **PRD 문서들**
   - `tasks-0001-prd-ontology-integration.md` vs `0001-prd-ontology-integration.md`
   - 내용 비교 후 중복이면 하나로 통합

2. **백업 폴더 정리**
   - `orchestration1115ts/` 폴더는 백업으로 보이므로 별도 관리
   - 작업 중인 폴더만 정리

### Priority 3: 개선 (중복 아님, 구조 개선)

1. **설계 문서 템플릿화**
   - `ontology_engineering/DesigningOfOntology/`에 범용 템플릿 추가
   - 각 에이전트는 템플릿을 참조하도록 문서화

2. **워크플로우 문서 보완**
   - `AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md`에 Agent04 사례 추가

---

## 📋 정리 작업 체크리스트

### Phase 1: 통합 문서 생성

- [ ] `ontology_engineering/docs/ONTOLOGY_INTEGRATION_CHECKLIST.md` 생성
  - [ ] Agent01과 Agent04 체크리스트 통합
  - [ ] 에이전트별 특수 사항 섹션 추가
  - [ ] 범용 체크리스트와 에이전트별 확장 구분

- [ ] `ontology_engineering/docs/ONTOLOGY_ENGINE_INTEGRATION.md` 생성
  - [ ] 공통 메커니즘 설명
  - [ ] Agent01 예시 섹션 추가
  - [ ] Agent04 예시 섹션 추가
  - [ ] 에이전트별 차이점 명시

- [ ] `ontology_engineering/docs/ONTOLOGY_RULE_INTEGRATION_CHECK.md` 생성
  - [ ] 공통 체크 항목 정리
  - [ ] 에이전트별 특수 사항 추가

### Phase 2: 에이전트 폴더 정리

- [ ] `agent01_onboarding/ontology/` 정리
  - [ ] 중복 문서 제거 또는 참조 링크로 교체
  - [ ] `README.md`에 통합 문서 링크 추가

- [ ] `agent04_inspect_weakpoints/ontology/` 정리
  - [ ] 중복 문서 제거 또는 참조 링크로 교체
  - [ ] `README.md`에 통합 문서 링크 추가

### Phase 3: PRD 문서 확인

- [ ] `agent22_module_improvement/tasks/` 확인
  - [ ] `tasks-0001-prd-ontology-integration.md` vs `0001-prd-ontology-integration.md` 비교
  - [ ] 중복이면 하나로 통합

- [ ] `orchestration/tasks/` 확인
  - [ ] `tasks-0003-prd-ontology-modularization.md` vs `0003-prd-ontology-modularization.md` 비교
  - [ ] 중복이면 하나로 통합

### Phase 4: 문서화 개선

- [ ] `ontology_engineering/DesigningOfOntology/README.md` 생성
  - [ ] 설계 문서 템플릿 가이드 추가
  - [ ] 에이전트별 설계 문서 작성 가이드 추가

- [ ] `AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md` 보완
  - [ ] Agent04 사례 추가
  - [ ] 에이전트별 차이점 섹션 강화

---

## 📁 권장 폴더 구조 (정리 후)

```
ontology_engineering/
├── DesigningOfOntology/          # 설계 문서 (범용 템플릿)
│   ├── 01_ontology_specification.md (템플릿)
│   ├── 02_ontology_types.md
│   ├── ...
│   └── README.md (템플릿 사용 가이드)
├── docs/                         # 범용 문서
│   ├── AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md
│   ├── ONTOLOGY_INTEGRATION_CHECKLIST.md (통합)
│   ├── ONTOLOGY_ENGINE_INTEGRATION.md (통합)
│   └── ONTOLOGY_RULE_INTEGRATION_CHECK.md (통합)
└── modules/                      # 에이전트별 온톨로지 파일
    ├── agent01.owl
    └── agent04.owl

agent01_onboarding/ontology/
├── README.md (통합 문서 링크)
├── OntologyEngine.php
├── OntologyActionHandler.php
├── IMPLEMENTATION_SUMMARY.md (에이전트별)
└── ONTOLOGY_INTEGRATION_ISSUES.md (에이전트별)

agent04_inspect_weakpoints/ontology/
├── README.md (통합 문서 링크)
├── OntologyEngine.php
├── OntologyActionHandler.php
├── IMPLEMENTATION_SUMMARY.md (에이전트별)
└── 01_ontology_specification.md (에이전트별 설계)
```

---

## ⚠️ 주의사항

1. **에이전트별 차이점 보존**
   - 설계 문서는 에이전트별로 유지 (내용이 다름)
   - 통합 문서는 공통 부분만 통합, 차이점은 별도 섹션으로 명시

2. **참조 링크 유지**
   - 각 에이전트 폴더에서 통합 문서로의 링크 유지
   - 문서 이동 시 기존 링크 업데이트

3. **백업 폴더 처리**
   - `orchestration1115ts/` 같은 백업 폴더는 별도 관리
   - 작업 중인 폴더만 정리

4. **문서 버전 관리**
   - 통합 문서에는 버전 정보 추가
   - 에이전트별 문서와의 호환성 명시

---

## 📝 다음 단계

1. **즉시 실행**: Priority 1 문서 통합 작업 시작
2. **확인 작업**: Priority 2 문서 내용 비교 및 중복 확인
3. **개선 작업**: Priority 3 문서 구조 개선

---

**작성일**: 2025-01-27  
**상태**: 📋 분석 완료, 정리 작업 대기

