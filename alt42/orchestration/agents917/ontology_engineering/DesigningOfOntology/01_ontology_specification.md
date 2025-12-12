# Agent01 온톨로지 명세서 (Ontology Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: 온톨로지 데이터 구조의 완전한 명세 (정책, 의지, 추론 제외)

---

## 1. 문서 범위

이 문서는 **Agent01 온톨로지의 데이터 구조만** 정의합니다.

**포함 내용**:
- 모든 노드 클래스 정의
- 속성(Property) 정의
- 관계(Relationship) 정의
- 계층 구조
- JSON-LD 매핑 규칙

**제외 내용**:
- 정책(Policy)
- 의지(Will)
- 의도(Intent)
- 추론 규칙(Reasoning Rules)
- 의사결정 로직(Decision Logic)

---

## 2. 온톨로지 네임스페이스

```
@prefix mk: <https://mathking.kr/ontology/mathking/>
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>
@prefix owl: <http://www.w3.org/2002/07/owl#>
@prefix xsd: <http://www.w3.org/2001/XMLSchema#>
```

---

## 3. 계층 구조 (Stage Hierarchy)

Agent01 온톨로지는 3단계 계층 구조를 가집니다:

```
[1] Context Layer
    ├── OnboardingContext
    └── LearningContextIntegration

[2] Decision Layer
    └── FirstClassDecisionModel

[3] Execution Layer
    └── FirstClassExecutionPlan
```

**계층 관계**:
- `OnboardingContext` → `LearningContextIntegration` (parent-child)
- `OnboardingContext` → `FirstClassDecisionModel` (parent-child)
- `FirstClassDecisionModel` → `FirstClassExecutionPlan` (parent-child)

---

## 4. Context Layer

### 4.1 OnboardingContext

**클래스 정의**:
```json
{
  "@id": "mk:OnboardingContext",
  "@type": "owl:Class",
  "rdfs:label": "온보딩 컨텍스트",
  "rdfs:comment": "학생의 온보딩 정보와 초기 학습 맥락을 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:Context",
  "mk:hasStage": "Context"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk:hasStudentGrade` | xsd:string | 1 | 학생 학년 | "중2" |
| `mk:hasSchool` | xsd:string | 1 | 학교명 | "OO중학교" |
| `mk:hasAcademy` | xsd:string | 1 | 학원명 | "OO수학학원" |
| `mk:hasAcademyGrade` | xsd:string | 1 | 학원 등급(반) | "중2 상위반" |
| `mk:hasOnboardingInfo` | xsd:string | 1 | 온보딩 종합 정보 (원시 설문 응답) | "중위권, 벼락치기, 개념형, 자신감 낮음" |
| `mk:hasMathConfidence` | xsd:integer | 1 | 수학 자신감 점수 (0-10) | 4 |
| `mk:hasMathStressLevel` | xsd:string | 1 | 수학 스트레스 수준 (원시 응답) | "높음" |
| `mk:hasMathLevel` | xsd:string | 1 | 수학 수준 (원시 응답) | "중위권" |
| `mk:hasTextbooks` | rdf:List | 0..* | 사용 교재 목록 | ["개념원리 중2-1", "쎈 중2-1"] |
| `mk:hasAcademyTextbook` | xsd:string | 1 | 학원 교재 | "쎈 중2-1" |

**메타데이터 속성**:

| Property ID | 타입 | 카디널리티 | 설명 |
|------------|------|-----------|------|
| `mk:hasStage` | xsd:string | 1 | 레이어 단계 | "Context" |
| `mk:hasIntent` | xsd:string | 0..1 | 노드의 의도 (메타데이터) | "학생의 초기 수학 맥락을 구조화" |
| `mk:hasIdentity` | xsd:string | 0..1 | 노드의 정체성 (메타데이터) | "특정 학생의 온보딩 정보" |
| `mk:hasPurpose` | xsd:string | 0..1 | 노드의 목적 (메타데이터) | "첫 수업 전략 수립을 위한 기반 데이터 제공" |
| `mk:hasContext` | xsd:string | 0..1 | 노드의 맥락 (메타데이터) | "신규/갱신, 학년, 학교, 학원, 온보딩 설문 상태" |

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 0..1 | `mk:OnboardingContext` | 부모 노드 (root는 null) |
| `mk:hasChild` | owl:ObjectProperty | 0..* | `mk:LearningContextIntegration`, `mk:FirstClassDecisionModel` | 자식 노드 |

**JSON-LD 예시**:
```json
{
  "@id": "mk:OnboardingContext/instance_001",
  "@type": "mk:OnboardingContext",
  "mk:hasStage": "Context",
  "mk:hasIntent": "학생의 초기 수학 맥락을 구조화",
  "mk:hasIdentity": "특정 학생의 온보딩 정보",
  "mk:hasPurpose": "첫 수업 전략 수립을 위한 기반 데이터 제공",
  "mk:hasContext": "신규/갱신, 학년, 학교, 학원, 온보딩 설문 상태",
  "mk:hasStudentGrade": "중2",
  "mk:hasSchool": "OO중학교",
  "mk:hasAcademy": "OO수학학원",
  "mk:hasAcademyGrade": "중2 상위반",
  "mk:hasOnboardingInfo": "중위권, 벼락치기, 개념형, 자신감 낮음",
  "mk:hasMathConfidence": 4,
  "mk:hasMathStressLevel": "높음",
  "mk:hasMathLevel": "중위권",
  "mk:hasTextbooks": ["개념원리 중2-1", "쎈 중2-1"],
  "mk:hasAcademyTextbook": "쎈 중2-1",
  "mk:hasParent": null
}
```

---

### 4.2 LearningContextIntegration

**클래스 정의**:
```json
{
  "@id": "mk:LearningContextIntegration",
  "@type": "owl:Class",
  "rdfs:label": "학습 맥락 통합",
  "rdfs:comment": "학생의 진도, 단원, 정렬 상태를 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:Context",
  "mk:hasStage": "Context"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk:hasConceptProgress` | xsd:string | 1 | 개념 진도 (원시 데이터) | "중2-1 일차방정식까지" |
| `mk:hasAdvancedProgress` | xsd:string | 1 | 심화 진도 (원시 데이터) | "중2-1 심화 전반" |
| `mk:hasUnitMastery` | xsd:string | 1 | 단원별 마스터리 (원시 데이터) | "방정식 보통, 함수 미이수" |
| `mk:hasCurrentPosition` | xsd:string | 1 | 현재 진도 위치 (원시 데이터) | "중2-1 중반" |
| `mk:hasAcademyProgress` | xsd:string | 1 | 학원 진도 (원시 데이터) | "중2-1 심화 진행 중" |
| `mk:hasCurriculumAlignment` | xsd:string | 1 | 커리큘럼 정렬 상태 (원시 데이터) | "학원 진도가 학교보다 빠름" |
| `mk:hasAcademySchoolHomeAlignment` | xsd:string | 1 | 학원-학교-집 정렬 상태 (원시 데이터) | "학원-학교 불완전 정렬" |

**메타데이터 속성**: OnboardingContext와 동일

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 1 | `mk:OnboardingContext` | 부모 노드 (필수) |
| `mk:usesContext` | owl:ObjectProperty | 0..* | `mk:OnboardingContext`, `mk:LearningContextIntegration` | 참조하는 Context 노드 |

**JSON-LD 예시**:
```json
{
  "@id": "mk:LearningContextIntegration/instance_001",
  "@type": "mk:LearningContextIntegration",
  "mk:hasStage": "Context",
  "mk:hasIntent": "진도/단원/정렬 상태 데이터를 저장",
  "mk:hasIdentity": "해당 학생의 수학 진도 구조 데이터",
  "mk:hasPurpose": "첫 수업 전략 수립을 위한 진도/단원 정보 제공",
  "mk:hasContext": "개념/심화 진도, 단원 마스터리, 학원-학교-집 정렬 상태",
  "mk:hasConceptProgress": "중2-1 일차방정식까지",
  "mk:hasAdvancedProgress": "중2-1 심화 전반",
  "mk:hasUnitMastery": "방정식 보통, 함수 미이수",
  "mk:hasCurrentPosition": "중2-1 중반",
  "mk:hasAcademyProgress": "중2-1 심화 진행 중",
  "mk:hasCurriculumAlignment": "학원 진도가 학교보다 빠름",
  "mk:hasAcademySchoolHomeAlignment": "학원-학교 불완전 정렬",
  "mk:hasParent": "mk:OnboardingContext/instance_001"
}
```

---

## 5. Decision Layer

### 5.1 FirstClassDecisionModel

**클래스 정의**:
```json
{
  "@id": "mk:FirstClassDecisionModel",
  "@type": "owl:Class",
  "rdfs:label": "첫 수업 결정 모델",
  "rdfs:comment": "첫 수업의 핵심 의사결정 결과를 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:DecisionModel",
  "mk:hasStage": "Decision"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk:hasSelectedProblem` | xsd:string | 1 | 선택된 문제 ID | "P1" |
| `mk:hasProblemPriority` | rdf:List | 1..* | 문제 우선순위 리스트 | ["P1", "P3", "P2"] |
| `mk:hasProblem` | xsd:string | 1 | 최종 문제 정의 | "방정식 개념은 애매하고..." |
| `mk:hasDecision` | xsd:string | 1 | 결정 내용 | "방정식 핵심 개념을 쉬운 예제로 재정리..." |
| `mk:hasImpact` | xsd:string | 1 | 예상 영향 | "첫 수업에서 '아, 이해된다'는 경험을 주어..." |
| `mk:hasDifficultyLevel` | mk:DifficultyLevel | 1 | 난이도 수준 | "mk:EasyToMedium" |
| `mk:hasAlignmentStrategy` | mk:AlignmentStrategy | 1 | 정렬 전략 | "mk:BridgeStrategy" |
| `mk:hasContentRange` | xsd:string | 1 | 내용 범위 | "방정식 핵심 유형 복습 + 함수 개념 전단계 다리 놓기" |
| `mk:hasUnitPlan` | rdf:List | 1..* | 단원 계획 | ["중2-1 방정식 핵심 복습", "함수 도입 준비"] |
| `mk:hasDataSources` | rdf:List | 1..* | 데이터 소스 | ["mk:OnboardingContext/instance_001", "mk:LearningContextIntegration/instance_001"] |

**메타데이터 속성**: OnboardingContext와 동일

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 1 | `mk:OnboardingContext` | 부모 노드 (필수) |
| `mk:usesContext` | owl:ObjectProperty | 2..* | `mk:OnboardingContext`, `mk:LearningContextIntegration` | 참조하는 Context 노드 (필수) |
| `mk:hasChild` | owl:ObjectProperty | 0..1 | `mk:FirstClassExecutionPlan` | 자식 노드 |

**JSON-LD 예시**:
```json
{
  "@id": "mk:FirstClassDecisionModel/instance_001",
  "@type": "mk:FirstClassDecisionModel",
  "mk:hasStage": "Decision",
  "mk:hasIntent": "첫 수업의 핵심 의사결정을 수행",
  "mk:hasIdentity": "첫 수업 전략 결정 모델",
  "mk:hasPurpose": "난이도, 정렬 전략, 단원 범위, 내용 범위 결정",
  "mk:hasContext": "OnboardingContext와 LearningContextIntegration 데이터를 기반으로 결정",
  "mk:hasSelectedProblem": "P1",
  "mk:hasProblemPriority": ["P1", "P3", "P2"],
  "mk:hasProblem": "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태에서 학원 진도만 빠르게 진행 중",
  "mk:hasDecision": "방정식 핵심 개념을 쉬운 예제로 재정리하고, 함수 도입을 위한 연결 개념까지 첫 수업에서 다룬다",
  "mk:hasImpact": "첫 수업에서 '아, 이해된다'는 경험을 주어 자신감과 안정감을 올린다",
  "mk:hasDifficultyLevel": "mk:EasyToMedium",
  "mk:hasAlignmentStrategy": "mk:BridgeStrategy",
  "mk:hasContentRange": "방정식 핵심 유형 복습 + 함수 개념 전단계 다리 놓기",
  "mk:hasUnitPlan": ["중2-1 방정식 핵심 복습", "함수 도입 준비"],
  "mk:hasDataSources": [
    "mk:OnboardingContext/instance_001",
    "mk:LearningContextIntegration/instance_001"
  ],
  "mk:hasParent": "mk:OnboardingContext/instance_001",
  "mk:usesContext": [
    "mk:OnboardingContext/instance_001",
    "mk:LearningContextIntegration/instance_001"
  ]
}
```

---

## 6. Execution Layer

### 6.1 FirstClassExecutionPlan

**클래스 정의**:
```json
{
  "@id": "mk:FirstClassExecutionPlan",
  "@type": "owl:Class",
  "rdfs:label": "첫 수업 실행 계획",
  "rdfs:comment": "첫 수업의 구체적 실행 계획을 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:ExecutionPlan",
  "mk:hasStage": "Execution"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk:hasAction` | rdf:List | 1..* | 실행할 행동 목록 | ["도입 루틴: 쉬운 방정식 1~2문제로 워밍업", ...] |
| `mk:hasMeasurement` | rdf:List | 1..* | 측정 방법 목록 | ["도입 문제 정답 여부와 풀이 설명 가능 여부", ...] |
| `mk:hasFeedback` | rdf:List | 0..* | 피드백 수집 계획 | ["둘째 수업에서 함수 도입 비율을 올릴지...", ...] |
| `mk:hasAdjustment` | rdf:List | 0..* | 조정 계획 | ["답변/표정/속도에 따라 난이도 상/하향 조정", ...] |

**메타데이터 속성**: OnboardingContext와 동일

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 1 | `mk:FirstClassDecisionModel` | 부모 노드 (필수) |
| `mk:referencesDecision` | owl:ObjectProperty | 1 | `mk:FirstClassDecisionModel` | 참조하는 Decision 노드 (필수) |

**JSON-LD 예시**:
```json
{
  "@id": "mk:FirstClassExecutionPlan/instance_001",
  "@type": "mk:FirstClassExecutionPlan",
  "mk:hasStage": "Execution",
  "mk:hasIntent": "DecisionModel의 결정을 실제 첫 수업 실행 계획으로 변환",
  "mk:hasIdentity": "첫 수업 실행 계획안",
  "mk:hasPurpose": "수학 자존감, 이해도, 루틴 형성의 첫 발판",
  "mk:hasContext": "DecisionModel의 결정사항을 실행 가능한 단계로 분해",
  "mk:hasAction": [
    "도입 루틴: 쉬운 방정식 1~2문제로 워밍업",
    "설명 전략: 방정식 의미를 그림/상황 설명으로 재정리",
    "자료 선택: 개념원리 예제 + 쎈 A/B 타입 쉬운 문제 위주",
    "정렬 전략 실행: 학교 진도 기준으로 방정식 마무리 후 함수 도입 예고"
  ],
  "mk:hasMeasurement": [
    "도입 문제 정답 여부와 풀이 설명 가능 여부",
    "설명 후 유사 문제에서 스스로 풀이 가능 여부",
    "학생 표정/반응 관찰"
  ],
  "mk:hasFeedback": [
    "둘째 수업에서 함수 도입 비율을 올릴지, 방정식 복습을 더 할지 결정",
    "Will 준수도에 따라 다음 전략 조정"
  ],
  "mk:hasAdjustment": [
    "답변/표정/속도에 따라 난이도 상/하향 조정",
    "필요시 벼락치기 패턴을 고려한 시험 대비 설명 추가"
  ],
  "mk:hasParent": "mk:FirstClassDecisionModel/instance_001",
  "mk:referencesDecision": "mk:FirstClassDecisionModel/instance_001"
}
```

---

## 7. 열거형 타입 (Enumerated Types)

### 7.1 DifficultyLevel

```json
{
  "@id": "mk:DifficultyLevel",
  "@type": "owl:Class",
  "rdfs:label": "난이도 수준"
}
```

**인스턴스**:
- `mk:Easy`
- `mk:EasyToMedium`
- `mk:Medium`
- `mk:MediumToHard`
- `mk:Hard`
- `mk:VeryHard`

### 7.2 AlignmentStrategy

```json
{
  "@id": "mk:AlignmentStrategy",
  "@type": "owl:Class",
  "rdfs:label": "정렬 전략"
}
```

**인스턴스**:
- `mk:BridgeStrategy` - 진도 간극 메우기 전략
- `mk:ReinforcementStrategy` - 보강 전략
- `mk:PreviewStrategy` - 예습 전략
- `mk:SynchronizedStrategy` - 동기화 전략

---

## 8. JSON-LD 매핑 규칙

### 8.1 기본 매핑 규칙

| DSL 구조 | JSON-LD 구조 |
|---------|-------------|
| `node "ID" { class: "mk:Class" }` | `{ "@id": "mk:Class/ID", "@type": "mk:Class" }` |
| `property: "value"` | `"mk:property": "value"` |
| `property: ["value1", "value2"]` | `"mk:property": ["value1", "value2"]` |
| `metadata { stage: "Context" }` | `"mk:hasStage": "Context"` |
| `parent: "ParentID"` | `"mk:hasParent": "mk:Class/ParentID"` |
| `usesContext: ["ID1", "ID2"]` | `"mk:usesContext": ["mk:Class/ID1", "mk:Class/ID2"]` |

### 8.2 메타데이터 매핑

| DSL 메타데이터 | JSON-LD 속성 |
|--------------|------------|
| `metadata.stage` | `mk:hasStage` |
| `metadata.intent` | `mk:hasIntent` |
| `metadata.identity` | `mk:hasIdentity` |
| `metadata.purpose` | `mk:hasPurpose` |
| `metadata.context` | `mk:hasContext` |

### 8.3 완전한 JSON-LD 문서 예시

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  },
  "@graph": [
    {
      "@id": "mk:OnboardingContext/instance_001",
      "@type": "mk:OnboardingContext",
      "mk:hasStage": "Context",
      "mk:hasStudentGrade": "중2",
      "mk:hasSchool": "OO중학교",
      "mk:hasAcademy": "OO수학학원",
      "mk:hasAcademyGrade": "중2 상위반",
      "mk:hasMathConfidence": 4,
      "mk:hasTextbooks": ["개념원리 중2-1", "쎈 중2-1"]
    },
    {
      "@id": "mk:LearningContextIntegration/instance_001",
      "@type": "mk:LearningContextIntegration",
      "mk:hasStage": "Context",
      "mk:hasParent": "mk:OnboardingContext/instance_001",
      "mk:hasConceptProgress": "중2-1 일차방정식까지",
      "mk:hasUnitMastery": "방정식 보통, 함수 미이수"
    },
    {
      "@id": "mk:FirstClassDecisionModel/instance_001",
      "@type": "mk:FirstClassDecisionModel",
      "mk:hasStage": "Decision",
      "mk:hasParent": "mk:OnboardingContext/instance_001",
      "mk:usesContext": [
        "mk:OnboardingContext/instance_001",
        "mk:LearningContextIntegration/instance_001"
      ],
      "mk:hasDifficultyLevel": "mk:EasyToMedium",
      "mk:hasAlignmentStrategy": "mk:BridgeStrategy"
    },
    {
      "@id": "mk:FirstClassExecutionPlan/instance_001",
      "@type": "mk:FirstClassExecutionPlan",
      "mk:hasStage": "Execution",
      "mk:hasParent": "mk:FirstClassDecisionModel/instance_001",
      "mk:referencesDecision": "mk:FirstClassDecisionModel/instance_001",
      "mk:hasAction": [
        "도입 루틴: 쉬운 방정식 1~2문제로 워밍업",
        "설명 전략: 방정식 의미를 그림/상황 설명으로 재정리"
      ]
    }
  ]
}
```

---

## 9. 데이터 무결성 제약 조건

### 9.1 필수 속성 제약

**OnboardingContext**:
- `mk:hasStudentGrade` (필수)
- `mk:hasSchool` (필수)
- `mk:hasAcademy` (필수)
- `mk:hasStage` (필수)

**LearningContextIntegration**:
- `mk:hasParent` (필수, OnboardingContext 참조)
- `mk:hasConceptProgress` (필수)
- `mk:hasUnitMastery` (필수)
- `mk:hasStage` (필수)

**FirstClassDecisionModel**:
- `mk:hasParent` (필수, OnboardingContext 참조)
- `mk:usesContext` (필수, 최소 2개 Context 참조)
- `mk:hasProblem` (필수)
- `mk:hasDecision` (필수)
- `mk:hasDifficultyLevel` (필수)
- `mk:hasStage` (필수)

**FirstClassExecutionPlan**:
- `mk:hasParent` (필수, FirstClassDecisionModel 참조)
- `mk:referencesDecision` (필수, FirstClassDecisionModel 참조)
- `mk:hasAction` (필수, 최소 1개)
- `mk:hasMeasurement` (필수, 최소 1개)
- `mk:hasStage` (필수)

### 9.2 관계 제약

- `mk:hasParent`는 순환 참조를 허용하지 않음
- `mk:usesContext`는 반드시 존재하는 Context 인스턴스를 참조해야 함
- `mk:referencesDecision`은 반드시 존재하는 Decision 인스턴스를 참조해야 함

### 9.3 타입 제약

- `mk:hasMathConfidence`: 0 이상 10 이하의 정수
- `mk:hasTextbooks`: 문자열 배열 (빈 배열 허용)
- `mk:hasAction`: 문자열 배열 (최소 1개 요소)
- `mk:hasDifficultyLevel`: `mk:DifficultyLevel` 열거형 값만 허용
- `mk:hasAlignmentStrategy`: `mk:AlignmentStrategy` 열거형 값만 허용

---

## 10. 온톨로지 스키마 다이어그램

```
┌─────────────────────────┐
│  OnboardingContext      │
│  (Context Layer)        │
│                         │
│  - hasStudentGrade      │
│  - hasSchool            │
│  - hasAcademy           │
│  - hasMathConfidence    │
│  - ...                   │
└───────────┬─────────────┘
            │
            ├──────────────────┐
            │                  │
            ▼                  ▼
┌──────────────────────┐  ┌──────────────────────────┐
│ LearningContext      │  │ FirstClassDecisionModel  │
│ Integration          │  │ (Decision Layer)         │
│ (Context Layer)      │  │                          │
│                      │  │ - hasProblem             │
│ - hasConceptProgress │  │ - hasDecision            │
│ - hasUnitMastery     │  │ - hasDifficultyLevel     │
│ - ...                │  │ - hasAlignmentStrategy   │
└──────────────────────┘  └───────────┬──────────────┘
                                       │
                                       ▼
                            ┌──────────────────────────┐
                            │ FirstClassExecutionPlan  │
                            │ (Execution Layer)        │
                            │                          │
                            │ - hasAction              │
                            │ - hasMeasurement         │
                            │ - hasFeedback            │
                            │ - hasAdjustment          │
                            └──────────────────────────┘
```

---

## 11. 참고 문서

- **OIW Core Principles**: 의지와 의도 원칙 정의
- **DIL Reasoning Framework**: 의사결정 추론 구조
- **Rules & Constraints**: 룰 엔진 및 제약 조건
- **OIW DSL Specification**: DSL 문법 명세
- **JSON-LD Generator Design**: 변환기 설계 문서

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**다음 문서**: `02_oiw_core_principles.md`

