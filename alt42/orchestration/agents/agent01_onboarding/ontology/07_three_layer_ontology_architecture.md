# 완전 확장형 온톨로지 3-Layer 아키텍처 (Three-Layer Ontology Architecture)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: Agent 내부에서 Task가 복잡해질 때 붕괴되지 않도록 설계한 최종 구조

---

## 1. 개요

각 Agent는 내부적으로 **3개의 온톨로지 계층**을 가집니다:

```
① Agent Core Ontology      ← 모든 Task 공통 (변하지 않음)
② Task Core Ontology       ← 특정 Task 공통 (잘 안 변함)
③ Task Module Ontology     ← 세부 기능 단위 (자주 바뀌어도 안전)
```

이 구조는 **무한 확장하면서도 절대 깨지지 않는 견고한 구조**를 제공합니다.

---

## 2. 구조 개념도

```
Agent01/
 ├── ontology/
 │     ├── agent_core/            ← Base Meta + Relations + Common Types
 │     │   ├── metadata_schema.jsonld
 │     │   ├── common_types.jsonld
 │     │   └── base_relations.jsonld
 │     ├── task_core/             ← Task-level abstractions
 │     │   ├── onboarding_task_core.jsonld
 │     │   └── exam_prep_task_core.jsonld
 │     └── modules/               ← 세부 기능 온톨로지(무한 확장)
 │           ├── onboarding/
 │           │   ├── personality_module.jsonld
 │           │   ├── confidence_module.jsonld
 │           │   └── stress_module.jsonld
 │           ├── first_class/
 │           │   ├── strategy_module.jsonld
 │           │   └── execution_module.jsonld
 │           └── exam_prep/
 │               └── schedule_module.jsonld
```

---

## 3. 각 계층의 책임 정의

### 3.1 Agent Core Ontology (에이전트 내부 공통 표준)

**역할**: 모든 Task가 공유하는 구조 통일

**포함 요소**:
- 메타데이터 스키마: `mk:hasStage`, `mk:hasIntent`, `mk:hasIdentity`, `mk:hasPurpose`, `mk:hasContext`
- 공통 관계: `mk:hasParent`, `mk:usesContext`, `mk:referencesDecision`
- 공통 타입: `mk:DifficultyLevel`, `mk:AlignmentStrategy`, `mk:ConfidenceLevel`
- 기본 제약 조건

**특징**:
- 절대 수정하지 않음
- 모든 Task와 Module의 기반
- 버전 관리 최소화

**파일 위치**: `agent01/ontology/agent_core/`

---

### 3.2 Task Core Ontology (Task 내 공통 추상계층)

**역할**: Task 내부 모든 모듈이 공통으로 사용하는 추상적 구조 제공

**예시 - Onboarding Task Core**:
```json
{
  "@id": "mk-a01-task:OnboardingTaskCore",
  "@type": "owl:Class",
  "rdfs:label": "온보딩 Task 공통 구조",
  "mk-a01-task:baseClasses": [
    "mk-a01-task:ContextBase",
    "mk-a01-task:DiagnosticBase",
    "mk-a01-task:InterpretationBase",
    "mk-a01-task:StrategyBase",
    "mk-a01-task:ExecutionBase"
  ]
}
```

**예시 - Mastery Task Core**:
```json
{
  "@id": "mk-a04-task:MasteryTaskCore",
  "@type": "owl:Class",
  "rdfs:label": "마스터리 Task 공통 구조",
  "mk-a04-task:baseClasses": [
    "mk-a04-task:MasterySnapshotBase",
    "mk-a04-task:WeakPointBase",
    "mk-a04-task:ProgressEvaluationBase"
  ]
}
```

**특징**:
- Task별로 독립적
- 잘 안 변함
- Module들의 공통 인터페이스 역할

**파일 위치**: `agent01/ontology/task_core/`

---

### 3.3 Task Module Ontology (세부 기능 확장)

**역할**: Task의 세부 기능을 독립 스키마로 구성

**예시 - Onboarding Task Modules**:
```
onboarding/
 ├── personality_module.jsonld      ← 성격 분석 모듈
 ├── math_confidence_module.jsonld   ← 수학 자신감 모듈
 ├── textbook_profile_module.jsonld  ← 교재 프로필 모듈
 ├── stress_profile_module.jsonld    ← 스트레스 프로필 모듈
 └── study_style_module.jsonld       ← 학습 스타일 모듈
```

**예시 - Mastery Task Modules**:
```
mastery/
 ├── weakpoint_detector.jsonld       ← 약점 탐지 모듈
 ├── strength_map.jsonld             ← 강점 맵 모듈
 ├── gap_analyzer.jsonld             ← 간극 분석 모듈
 └── alignment_calculator.jsonld     ← 정렬 계산 모듈
```

**특징**:
- 완전 독립적
- 자주 바뀌어도 안전
- 무한 확장 가능

**파일 위치**: `agent01/ontology/modules/{task_name}/`

---

## 4. 확장 시 깨지지 않는 구조

### 4.1 확장 전략

**Step 1: Agent Core는 절대 수정하지 않는다**
- 모든 Task와 Module은 Core를 기반으로 움직임
- Core 변경은 전체 시스템에 영향

**Step 2: 새로운 Task가 생기면 Task Core를 만든다**
```
새 Task: ExamPrep Task
→ ExamPrepTaskCore 생성
  ├── ExamPrepContextBase
  ├── ExamPrepDiagnosticBase
  └── ExamPrepStrategyBase
```

**Step 3: 해당 Task 안에서 모듈 생성**
```
ExamPrep Task Modules:
├── exam_range_detection_module.jsonld
├── memorization_module.jsonld
└── weak_area_refresh_module.jsonld
```

**Step 4: 각 Module은 완전 독립적인 JSON-LD 스키마**
- Module 간 의존성 최소화
- 독립적 버전 관리

**Step 5: Gateway에서 Core Type만 바라보면 통신 안정성 확보**
- Gateway는 Agent Core와 Task Core만 참조
- Module 변경이 Gateway에 영향 없음

---

## 5. 3-계층 구조의 견고성

### 5.1 공통 논리와 Task 특화 논리 충돌 없음

**기존 2계층 구조의 문제**:
```
Agent Core + Task
→ Task 안에 여러 기능이 생기면 다시 섞임
→ 충돌 발생
```

**3계층 구조의 해결**:
```
Agent Core + Task Core + Task Modules
→ Task Core가 추상 계층으로 충돌 완전 차단
→ Module 간 독립성 보장
```

---

### 5.2 Task 내부의 무한 확장 가능

**기존 구조의 문제**:
- Task 단일 스키마가 비대해짐
- 기능 추가 시 충돌 발생

**3계층 구조의 해결**:
- Module Ontology로 기능 단위 독립
- 새 Module 추가 시 기존 Module에 영향 없음

**예시**:
```
온보딩 Task 안에:
├── Personality Module (독립)
├── Confidence Module (독립)
├── Stress Module (독립)
└── Study Style Module (독립)
→ 각 Module이 완전히 독립적
```

---

### 5.3 유지보수 비용 최소화

**변경 빈도**:
```
Agent Core → 변하지 않음 (안정)
Task Core → 잘 안 변함 (안정)
Module Ontology → 자주 바뀌어도 안전 (유연)
```

**변경 영향 범위**:
- Agent Core 변경: 전체 시스템 영향 (거의 없음)
- Task Core 변경: 해당 Task의 모든 Module 영향 (드묾)
- Module 변경: 해당 Module만 영향 (빈번하지만 안전)

---

## 6. 하이브리드 아키텍처와의 통합

### 6.1 전체 아키텍처 구조

```
┌─────────────────────────────────────────┐
│      공통 온톨로지 (Shared Ontology)     │
│  - Student (학생 기본 정보)              │
│  - CommonContext (공통 맥락)             │
│  - BaseTypes (기본 타입)                 │
└─────────────────────────────────────────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        ▼           ▼           ▼
┌──────────┐  ┌──────────┐  ┌──────────┐
│ Agent01  │  │ Agent03  │  │ Agent05  │
│          │  │          │  │          │
│ ┌──────┐ │  │ ┌──────┐ │  │ ┌──────┐ │
│ │Core  │ │  │ │Core  │ │  │ │Core  │ │
│ └──┬───┘ │  │ └──┬───┘ │  │ └──┬───┘ │
│    │     │  │    │     │  │    │     │
│ ┌──▼───┐ │  │ ┌──▼───┐ │  │ ┌──▼───┐ │
│ │Task │ │  │ │Task │ │  │ │Task │ │
│ │Core │ │  │ │Core │ │  │ │Core │ │
│ └──┬───┘ │  │ └──┬───┘ │  │ └──┬───┘ │
│    │     │  │    │     │  │    │     │
│ ┌──▼───┐ │  │ ┌──▼───┐ │  │ ┌──▼───┐ │
│ │Mod1  │ │  │ │Mod1  │ │  │ │Mod1  │ │
│ │Mod2  │ │  │ │Mod2  │ │  │ │Mod2  │ │
│ └──────┘ │  │ └──────┘ │  │ └──────┘ │
└──────────┘  └──────────┘  └──────────┘
```

---

### 6.2 계층별 통신 규칙

**에이전트 간 통신 (Agent ↔ Agent)**:
- Agent Core 레벨에서만 통신
- Task Core와 Module은 내부 구현 세부사항

**Task 간 통신 (Task ↔ Task)**:
- Task Core 레벨에서 통신
- Module은 Task 내부에서만 사용

**Module 간 통신 (Module ↔ Module)**:
- 같은 Task 내부에서만 통신
- 다른 Task의 Module과 직접 통신 불가

---

## 7. Agent01 적용 예시

### 7.1 Agent Core Ontology

**파일**: `agent01/ontology/agent_core/metadata_schema.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a01-core": "https://mathking.kr/ontology/agent01/core/"
  },
  "@graph": [
    {
      "@id": "mk-a01-core:hasStage",
      "@type": "owl:DatatypeProperty",
      "rdfs:domain": "owl:Thing",
      "rdfs:range": "xsd:string",
      "rdfs:label": "레이어 단계",
      "rdfs:comment": "노드가 속한 레이어 단계를 표현하는 메타데이터"
    },
    {
      "@id": "mk-a01-core:hasParent",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "owl:Thing",
      "rdfs:range": "owl:Thing",
      "rdfs:label": "부모 노드를 가짐",
      "rdfs:comment": "온톨로지 계층 구조에서 부모 노드를 참조하는 관계"
    },
    {
      "@id": "mk-a01-core:usesContext",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "owl:Thing",
      "rdfs:range": "owl:Thing",
      "rdfs:label": "Context를 사용함",
      "rdfs:comment": "Decision/Execution 노드가 참조하는 Context 노드들을 표현하는 관계"
    }
  ]
}
```

---

### 7.2 Task Core Ontology

**파일**: `agent01/ontology/task_core/onboarding_task_core.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a01-core": "https://mathking.kr/ontology/agent01/core/",
    "mk-a01-task": "https://mathking.kr/ontology/agent01/task/"
  },
  "@graph": [
    {
      "@id": "mk-a01-task:OnboardingContextBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-core:ContextBase",
      "rdfs:label": "온보딩 Context 기본 구조",
      "rdfs:comment": "온보딩 Task의 모든 Context Module이 상속받는 기본 클래스"
    },
    {
      "@id": "mk-a01-task:OnboardingDecisionBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-core:DecisionBase",
      "rdfs:label": "온보딩 Decision 기본 구조",
      "rdfs:comment": "온보딩 Task의 모든 Decision Module이 상속받는 기본 클래스"
    },
    {
      "@id": "mk-a01-task:OnboardingExecutionBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-core:ExecutionBase",
      "rdfs:label": "온보딩 Execution 기본 구조",
      "rdfs:comment": "온보딩 Task의 모든 Execution Module이 상속받는 기본 클래스"
    }
  ]
}
```

---

### 7.3 Task Module Ontology

**파일**: `agent01/ontology/modules/onboarding/personality_module.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a01-core": "https://mathking.kr/ontology/agent01/core/",
    "mk-a01-task": "https://mathking.kr/ontology/agent01/task/",
    "mk-a01-mod": "https://mathking.kr/ontology/agent01/modules/"
  },
  "@graph": [
    {
      "@id": "mk-a01-mod:PersonalityProfile",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-task:OnboardingContextBase",
      "rdfs:label": "성격 프로필 모듈",
      "rdfs:comment": "학생의 성격 특성을 분석하는 모듈",
      "mk-a01-mod:moduleType": "personality",
      "mk-a01-mod:extends": "mk-a01-task:OnboardingContextBase",
      "mk-a01-mod:properties": [
        {
          "@id": "mk-a01-mod:hasPersonalityType",
          "@type": "owl:DatatypeProperty",
          "rdfs:domain": "mk-a01-mod:PersonalityProfile",
          "rdfs:range": "xsd:string"
        },
        {
          "@id": "mk-a01-mod:hasLearningPreference",
          "@type": "owl:DatatypeProperty",
          "rdfs:domain": "mk-a01-mod:PersonalityProfile",
          "rdfs:range": "xsd:string"
        }
      ]
    }
  ]
}
```

---

## 8. 확장 시나리오

### 8.1 시나리오: 새 Module 추가

**상황**: 온보딩 Task에 "학습 환경 분석 Module" 추가

**과정**:
1. `agent01/ontology/modules/onboarding/learning_environment_module.jsonld` 생성
2. `mk-a01-mod:LearningEnvironmentProfile` 클래스 정의
3. `mk-a01-task:OnboardingContextBase` 확장
4. Agent Core와 Task Core는 수정 없음

**결과**: 기존 Module에 영향 없이 확장 완료

**영향 범위**:
- ✅ Agent Core: 영향 없음
- ✅ Task Core: 영향 없음
- ✅ 기존 Modules: 영향 없음
- ✅ 새 Module만 추가됨

---

### 8.2 시나리오: 새 Task 추가

**상황**: Agent01에 "시험 대비 Task" 추가

**과정**:
1. `agent01/ontology/task_core/exam_prep_task_core.jsonld` 생성
2. Task Core 클래스 정의 (Agent Core 확장)
3. `agent01/ontology/modules/exam_prep/` 폴더 생성
4. 필요한 Module들 추가

**결과**: 기존 Onboarding Task에 영향 없이 새 Task 추가

**영향 범위**:
- ✅ Agent Core: 영향 없음
- ✅ 기존 Task Core: 영향 없음
- ✅ 기존 Modules: 영향 없음
- ✅ 새 Task Core와 Modules만 추가됨

---

## 9. Gateway 통신 규칙

### 9.1 Gateway가 참조하는 계층

**Gateway는 다음만 참조**:
- 공통 온톨로지 (Shared Ontology)
- Agent Core Ontology
- Task Core Ontology (선택적)

**Gateway가 참조하지 않는 것**:
- Task Module Ontology (내부 구현 세부사항)

**이유**:
- Module은 자주 변경되므로 Gateway가 참조하면 불안정
- Core만 참조하면 안정적인 통신 보장

---

### 9.2 통신 프로토콜

**에이전트 간 요청**:
```json
{
  "request_type": "ontology_query",
  "source_agent": "agent03",
  "target_agent": "agent01",
  "query": {
    "operation": "get_task_core",
    "task_type": "onboarding",
    "core_class": "OnboardingContextBase",
    "student_id": "12345"
  }
}
```

**응답**:
```json
{
  "response_type": "ontology_response",
  "source_agent": "agent01",
  "target_agent": "agent03",
  "data": {
    "@id": "mk-a01-task:OnboardingContextBase/instance_001",
    "@type": "mk-a01-task:OnboardingContextBase",
    "mk-a01-core:hasStage": "Context",
    "mk-a01-core:hasStudentGrade": "중2"
    // Task Core 레벨의 데이터만 반환
    // Module 세부사항은 포함하지 않음
  },
  "metadata": {
    "version": "2.3",
    "timestamp": "2025-01-27T10:00:00Z",
    "core_level": true
  }
}
```

---

## 10. 3-계층 구조의 궁극적 장점

### ✅ 무한 확장 가능
- 새 Module 추가해도 Agent Core와 Task Core는 그대로 유지
- 확장이 기존 구조에 영향 없음

### ✅ 계층 간 데이터 충돌 없음
- 각 계층은 책임이 완전히 분리
- 충돌 가능성 제로

### ✅ 규칙 자동 생성과 상호작용 설계 용이
- LLM이 생성하는 Task Module은 독립 JSON-LD로 바로 생성
- Module 간 상호작용 설계가 명확

### ✅ API/Gateway 호환성 100%
- Gateway는 Core 영역만 보면 되므로 전체 구조가 안정적
- Module 변경이 Gateway에 영향 없음

### ✅ 사람·LLM·시스템 모두 이해하기 쉬운 구조
- 직관적 계층화
- 각 계층의 역할이 명확

---

## 11. 구현 체크리스트

### Phase 1: Agent Core 구축
- [ ] Agent Core 스키마 정의
- [ ] 공통 메타데이터 정의
- [ ] 공통 타입 정의
- [ ] 공통 관계 정의
- [ ] Agent Core 문서화

### Phase 2: Task Core 구축
- [ ] Onboarding Task Core 정의
- [ ] 다른 Task Core 정의 (필요시)
- [ ] Task Core와 Agent Core 연결
- [ ] Task Core 문서화

### Phase 3: Task Module 구축
- [ ] Onboarding Modules 정의
  - [ ] Personality Module
  - [ ] Confidence Module
  - [ ] Stress Module
  - [ ] Study Style Module
- [ ] Module과 Task Core 연결
- [ ] Module 문서화

### Phase 4: Gateway 통합
- [ ] Gateway가 Agent Core만 참조하도록 설정
- [ ] Task Core 레벨 통신 프로토콜 정의
- [ ] Module은 내부 구현으로 처리
- [ ] 통신 테스트

---

## 12. 참고 문서

- **01_ontology_specification.md**: 클래스 및 속성 정의
- **02_ontology_types.md**: 타입 정의
- **03_ontology_relations.md**: 관계 정의
- **04_ontology_constraints.md**: 제약 조건 정의
- **05_ontology_context_tree.md**: 계층 구조 정의
- **06_jsonld_mapping.md**: JSON-LD 변환 규칙
- **principles.md**: 전체 온톨로지 구축 전략

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**관련 문서**: `principles.md` 섹션 12

