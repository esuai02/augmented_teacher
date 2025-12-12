# Agent04 온톨로지 명세서 (Ontology Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Inspect Weakpoints  
**목적**: 온톨로지 데이터 구조의 완전한 명세 (정책, 의지, 추론 제외)

---

## 1. 문서 범위

이 문서는 **Agent04 온톨로지의 데이터 구조만** 정의합니다.

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
@prefix mk-a04: <https://mathking.kr/ontology/agent04/>
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>
@prefix owl: <http://www.w3.org/2002/07/owl#>
@prefix xsd: <http://www.w3.org/2001/XMLSchema#>
```

---

## 3. 계층 구조 (Stage Hierarchy)

Agent04 온톨로지는 3단계 계층 구조를 가집니다:

```
[1] Context Layer
    ├── WeakpointDetectionContext
    └── ActivityAnalysisContext

[2] Decision Layer
    └── WeakpointAnalysisDecisionModel

[3] Execution Layer
    └── ReinforcementPlanExecutionPlan
```

**계층 관계**:
- `WeakpointDetectionContext` → `ActivityAnalysisContext` (parent-child)
- `WeakpointDetectionContext` → `WeakpointAnalysisDecisionModel` (parent-child)
- `WeakpointAnalysisDecisionModel` → `ReinforcementPlanExecutionPlan` (parent-child)

---

## 4. Context Layer

### 4.1 WeakpointDetectionContext

**클래스 정의**:
```json
{
  "@id": "mk-a04:WeakpointDetectionContext",
  "@type": "owl:Class",
  "rdfs:label": "취약점 탐지 컨텍스트",
  "rdfs:comment": "학생의 학습 활동에서 취약점을 탐지하기 위한 기본 맥락 정보를 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:Context",
  "mk:hasStage": "Context"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk-a04:hasStudentId` | xsd:integer | 1 | 학생 ID | 12345 |
| `mk-a04:hasActivityType` | mk-a04:ActivityType | 1 | 활동 유형 | "concept_understanding" |
| `mk-a04:hasActivityCategory` | xsd:string | 1 | 활동 카테고리 | "개념이해" |
| `mk-a04:hasSubActivity` | xsd:string | 1 | 하위 활동 항목 | "TTS 듣기" |
| `mk-a04:hasDetectionTimestamp` | xsd:dateTime | 1 | 탐지 시각 | "2025-01-27T10:30:00Z" |
| `mk-a04:hasWeakpointSeverity` | mk-a04:SeverityLevel | 1 | 취약점 심각도 | "mk-a04:High" |
| `mk-a04:hasWeakpointPattern` | xsd:string | 1 | 취약점 패턴 (원시 데이터) | "개념 정독 단계에서 멈춤 빈번" |
| `mk-a04:hasPerformanceMetrics` | rdf:List | 1..* | 성능 지표 리스트 | ["정답률: 40%", "소요시간: 25분"] |
| `mk-a04:hasBehaviorType` | xsd:string | 0..1 | 행동 유형 | "벼락치기형" |

**메타데이터 속성**:

| Property ID | 타입 | 카디널리티 | 설명 |
|------------|------|-----------|------|
| `mk:hasStage` | xsd:string | 1 | 레이어 단계 | "Context" |
| `mk:hasIntent` | xsd:string | 0..1 | 노드의 의도 (메타데이터) | "학습 활동에서 취약점을 탐지" |
| `mk:hasIdentity` | xsd:string | 0..1 | 노드의 정체성 (메타데이터) | "특정 학생의 특정 활동에서 탐지된 취약점" |
| `mk:hasPurpose` | xsd:string | 0..1 | 노드의 목적 (메타데이터) | "취약점 분석 및 보강 방안 수립을 위한 기반 데이터 제공" |
| `mk:hasContext` | xsd:string | 0..1 | 노드의 맥락 (메타데이터) | "활동 유형, 성능 지표, 취약점 패턴 상태" |

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 0..1 | `mk-a04:WeakpointDetectionContext` | 부모 노드 (root는 null) |
| `mk:hasChild` | owl:ObjectProperty | 0..* | `mk-a04:ActivityAnalysisContext`, `mk-a04:WeakpointAnalysisDecisionModel` | 자식 노드 |

**JSON-LD 예시**:
```json
{
  "@id": "mk-a04:WeakpointDetectionContext/instance_001",
  "@type": "mk-a04:WeakpointDetectionContext",
  "mk:hasStage": "Context",
  "mk:hasIntent": "학습 활동에서 취약점을 탐지",
  "mk:hasIdentity": "학생 12345의 개념이해 활동에서 탐지된 취약점",
  "mk:hasPurpose": "취약점 분석 및 보강 방안 수립을 위한 기반 데이터 제공",
  "mk:hasContext": "활동 유형: 개념이해, 성능 지표: 정답률 40%, 취약점 패턴: 개념 정독 단계 멈춤",
  "mk-a04:hasStudentId": 12345,
  "mk-a04:hasActivityType": "mk-a04:ConceptUnderstanding",
  "mk-a04:hasActivityCategory": "개념이해",
  "mk-a04:hasSubActivity": "TTS 듣기",
  "mk-a04:hasDetectionTimestamp": "2025-01-27T10:30:00Z",
  "mk-a04:hasWeakpointSeverity": "mk-a04:High",
  "mk-a04:hasWeakpointPattern": "개념 정독 단계에서 멈춤 빈번, 집중도 저하",
  "mk-a04:hasPerformanceMetrics": [
    "정답률: 40%",
    "소요시간: 25분",
    "멈춤 횟수: 5회"
  ],
  "mk-a04:hasBehaviorType": "벼락치기형",
  "mk:hasParent": null
}
```

---

### 4.2 ActivityAnalysisContext

**클래스 정의**:
```json
{
  "@id": "mk-a04:ActivityAnalysisContext",
  "@type": "owl:Class",
  "rdfs:label": "활동 분석 컨텍스트",
  "rdfs:comment": "특정 학습 활동의 상세 분석 결과를 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:Context",
  "mk:hasStage": "Context"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk-a04:hasActivityStage` | xsd:string | 1 | 활동 단계 (원시 데이터) | "개념 정독" |
| `mk-a04:hasPauseFrequency` | xsd:integer | 1 | 멈춤 빈도 | 5 |
| `mk-a04:hasPauseStage` | xsd:string | 1 | 멈춤 발생 단계 | "핵심 의미 파악" |
| `mk-a04:hasAttentionScore` | xsd:decimal | 0..1 | 주의집중도 점수 (0.0-1.0) | 0.6 |
| `mk-a04:hasGazeAttentionScore` | xsd:decimal | 0..1 | 시선 집중도 점수 (0.0-1.0) | 0.55 |
| `mk-a04:hasNoteTakingPattern` | xsd:string | 1 | 필기 패턴 (원시 데이터) | "핵심어 위주, 연결 구조 부족" |
| `mk-a04:hasConfusionType` | mk-a04:ConfusionType | 0..* | 혼동 유형 | ["mk-a04:DefinitionVsExample"] |
| `mk-a04:hasConceptConfusionDetected` | xsd:boolean | 1 | 개념 혼동 탐지 여부 | true |
| `mk-a04:hasImmersionScore` | xsd:decimal | 0..1 | 몰입도 점수 (0.0-1.0) | 0.65 |
| `mk-a04:hasBoredomDetected` | xsd:boolean | 1 | 지루함 탐지 여부 | false |
| `mk-a04:hasAttentionDropTime` | xsd:integer | 0..1 | 집중 이탈 시점 (초) | 1200 |
| `mk-a04:hasEmotionState` | xsd:string | 0..1 | 감정 상태 (원시 데이터) | "지루함" |
| `mk-a04:hasMethodPersonaMatchScore` | xsd:decimal | 0..1 | 방법-페르소나 적합도 점수 (0.0-1.0) | 0.7 |
| `mk-a04:hasCurrentMethod` | xsd:string | 1 | 현재 사용 방법 | "TTS" |
| `mk-a04:hasVisualResponseScore` | xsd:decimal | 0..1 | 시각 자료 반응 점수 (0.0-1.0) | 0.5 |
| `mk-a04:hasTextOrganizationScore` | xsd:decimal | 0..1 | 텍스트 정리 점수 (0.0-1.0) | 0.6 |
| `mk-a04:hasExampleVerificationScore` | xsd:decimal | 0..1 | 예제 확인 점수 (0.0-1.0) | 0.7 |
| `mk-a04:hasOptimalCombination` | xsd:string | 0..1 | 최적 활동 조합 (원시 데이터) | "TTS + 필기 + 예제풀이" |

**메타데이터 속성**: WeakpointDetectionContext와 동일

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 1 | `mk-a04:WeakpointDetectionContext` | 부모 노드 (필수) |
| `mk:usesContext` | owl:ObjectProperty | 0..* | `mk-a04:WeakpointDetectionContext`, `mk-a04:ActivityAnalysisContext` | 참조하는 Context 노드 |

**JSON-LD 예시**:
```json
{
  "@id": "mk-a04:ActivityAnalysisContext/instance_001",
  "@type": "mk-a04:ActivityAnalysisContext",
  "mk:hasStage": "Context",
  "mk:hasIntent": "활동의 상세 분석 결과를 저장",
  "mk:hasIdentity": "개념이해 활동의 상세 분석 데이터",
  "mk:hasPurpose": "취약점 분석을 위한 활동 상세 정보 제공",
  "mk:hasContext": "활동 단계, 멈춤 패턴, 주의집중도, 혼동 유형",
  "mk-a04:hasActivityStage": "개념 정독",
  "mk-a04:hasPauseFrequency": 5,
  "mk-a04:hasPauseStage": "핵심 의미 파악",
  "mk-a04:hasAttentionScore": 0.6,
  "mk-a04:hasGazeAttentionScore": 0.55,
  "mk-a04:hasNoteTakingPattern": "핵심어 위주, 연결 구조 부족",
  "mk-a04:hasConfusionType": ["mk-a04:DefinitionVsExample"],
  "mk-a04:hasConceptConfusionDetected": true,
  "mk-a04:hasImmersionScore": 0.65,
  "mk-a04:hasBoredomDetected": false,
  "mk-a04:hasMethodPersonaMatchScore": 0.7,
  "mk-a04:hasCurrentMethod": "TTS",
  "mk-a04:hasTextOrganizationScore": 0.6,
  "mk-a04:hasExampleVerificationScore": 0.7,
  "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001"
}
```

---

## 5. Decision Layer

### 5.1 WeakpointAnalysisDecisionModel

**클래스 정의**:
```json
{
  "@id": "mk-a04:WeakpointAnalysisDecisionModel",
  "@type": "owl:Class",
  "rdfs:label": "취약점 분석 결정 모델",
  "rdfs:comment": "탐지된 취약점을 분석하고 보강 방안을 결정하는 데이터 구조",
  "rdfs:subClassOf": "mk:DecisionModel",
  "mk:hasStage": "Decision"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk-a04:hasWeakpointId` | xsd:string | 1 | 취약점 ID | "WP001" |
| `mk-a04:hasWeakpointDescription` | xsd:string | 1 | 취약점 설명 | "개념 정독 단계에서 멈춤이 빈번하고, 핵심 의미 파악 단계에서 혼동 발생" |
| `mk-a04:hasRootCause` | xsd:string | 1 | 근본 원인 분석 | "개념의 정의와 예시를 구분하지 못함, TTS 방식이 학습 스타일과 맞지 않음" |
| `mk-a04:hasReinforcementStrategy` | mk-a04:ReinforcementStrategy | 1 | 보강 전략 | "mk-a04:ConceptClarificationStrategy" |
| `mk-a04:hasReinforcementPriority` | mk-a04:PriorityLevel | 1 | 보강 우선순위 | "mk-a04:High" |
| `mk-a04:hasRecommendedMethod` | xsd:string | 1 | 권장 방법 | "예제 중심 학습" |
| `mk-a04:hasRecommendedContent` | rdf:List | 1..* | 권장 콘텐츠 | ["concept_comparison_definition_vs_example", "example_focused_learning"] |
| `mk-a04:hasInterventionType` | mk-a04:InterventionType | 1 | 개입 유형 | "mk-a04:ConceptWeakPointSupport" |
| `mk-a04:hasFeedbackMessage` | xsd:string | 1 | 피드백 메시지 | "핵심 의미 파악 단계에서 멈추는 패턴이 보입니다. 이 구간을 집중적으로 보강해볼까요?" |
| `mk-a04:hasExpectedImpact` | xsd:string | 1 | 예상 효과 | "개념 이해도 향상, 멈춤 빈도 감소, 학습 효율 20% 향상" |
| `mk-a04:hasDataSources` | rdf:List | 1..* | 데이터 소스 | ["mk-a04:WeakpointDetectionContext/instance_001", "mk-a04:ActivityAnalysisContext/instance_001"] |

**메타데이터 속성**: WeakpointDetectionContext와 동일

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 1 | `mk-a04:WeakpointDetectionContext` | 부모 노드 (필수) |
| `mk:usesContext` | owl:ObjectProperty | 2..* | `mk-a04:WeakpointDetectionContext`, `mk-a04:ActivityAnalysisContext` | 참조하는 Context 노드 (필수) |
| `mk:hasChild` | owl:ObjectProperty | 0..1 | `mk-a04:ReinforcementPlanExecutionPlan` | 자식 노드 |

**JSON-LD 예시**:
```json
{
  "@id": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
  "@type": "mk-a04:WeakpointAnalysisDecisionModel",
  "mk:hasStage": "Decision",
  "mk:hasIntent": "취약점을 분석하고 보강 방안을 결정",
  "mk:hasIdentity": "개념이해 활동 취약점 분석 결정 모델",
  "mk:hasPurpose": "근본 원인 분석, 보강 전략 결정, 권장 방법 및 콘텐츠 제시",
  "mk:hasContext": "WeakpointDetectionContext와 ActivityAnalysisContext 데이터를 기반으로 결정",
  "mk-a04:hasWeakpointId": "WP001",
  "mk-a04:hasWeakpointDescription": "개념 정독 단계에서 멈춤이 빈번하고, 핵심 의미 파악 단계에서 혼동 발생",
  "mk-a04:hasRootCause": "개념의 정의와 예시를 구분하지 못함, TTS 방식이 학습 스타일과 맞지 않음",
  "mk-a04:hasReinforcementStrategy": "mk-a04:ConceptClarificationStrategy",
  "mk-a04:hasReinforcementPriority": "mk-a04:High",
  "mk-a04:hasRecommendedMethod": "예제 중심 학습",
  "mk-a04:hasRecommendedContent": [
    "concept_comparison_definition_vs_example",
    "example_focused_learning"
  ],
  "mk-a04:hasInterventionType": "mk-a04:ConceptWeakPointSupport",
  "mk-a04:hasFeedbackMessage": "핵심 의미 파악 단계에서 멈추는 패턴이 보입니다. 이 구간을 집중적으로 보강해볼까요?",
  "mk-a04:hasExpectedImpact": "개념 이해도 향상, 멈춤 빈도 감소, 학습 효율 20% 향상",
  "mk-a04:hasDataSources": [
    "mk-a04:WeakpointDetectionContext/instance_001",
    "mk-a04:ActivityAnalysisContext/instance_001"
  ],
  "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001",
  "mk:usesContext": [
    "mk-a04:WeakpointDetectionContext/instance_001",
    "mk-a04:ActivityAnalysisContext/instance_001"
  ]
}
```

---

## 6. Execution Layer

### 6.1 ReinforcementPlanExecutionPlan

**클래스 정의**:
```json
{
  "@id": "mk-a04:ReinforcementPlanExecutionPlan",
  "@type": "owl:Class",
  "rdfs:label": "보강 계획 실행 계획",
  "rdfs:comment": "결정된 보강 방안을 실제로 실행하기 위한 구체적 계획을 표현하는 데이터 구조",
  "rdfs:subClassOf": "mk:ExecutionPlan",
  "mk:hasStage": "Execution"
}
```

**속성(Properties)**:

| Property ID | 타입 | 카디널리티 | 설명 | 예시 |
|------------|------|-----------|------|------|
| `mk-a04:hasAction` | rdf:List | 1..* | 실행할 행동 목록 | ["개념 비교 콘텐츠 제공", "예제 중심 학습 자료 제시", ...] |
| `mk-a04:hasMeasurement` | rdf:List | 1..* | 측정 방법 목록 | ["멈춤 빈도 감소 여부", "개념 이해도 향상 여부", ...] |
| `mk-a04:hasFeedback` | rdf:List | 0..* | 피드백 수집 계획 | ["다음 활동에서 개념 혼동 감소 여부 확인", ...] |
| `mk-a04:hasAdjustment` | rdf:List | 0..* | 조정 계획 | ["예제 중심 학습이 효과 없으면 다른 방법 시도", ...] |
| `mk-a04:hasContentLinks` | rdf:List | 1..* | 콘텐츠 링크 | ["concept_comparison_definition_vs_example", "example_focused_learning"] |
| `mk-a04:hasTimeline` | xsd:string | 1 | 실행 타임라인 (원시 데이터) | "즉시 실행, 1주일 모니터링" |

**메타데이터 속성**: WeakpointDetectionContext와 동일

**관계(Relationships)**:

| Relationship | 타입 | 카디널리티 | 대상 클래스 | 설명 |
|-------------|------|-----------|-----------|------|
| `mk:hasParent` | owl:ObjectProperty | 1 | `mk-a04:WeakpointAnalysisDecisionModel` | 부모 노드 (필수) |
| `mk:referencesDecision` | owl:ObjectProperty | 1 | `mk-a04:WeakpointAnalysisDecisionModel` | 참조하는 Decision 노드 (필수) |

**JSON-LD 예시**:
```json
{
  "@id": "mk-a04:ReinforcementPlanExecutionPlan/instance_001",
  "@type": "mk-a04:ReinforcementPlanExecutionPlan",
  "mk:hasStage": "Execution",
  "mk:hasIntent": "DecisionModel의 결정을 실제 보강 실행 계획으로 변환",
  "mk:hasIdentity": "보강 계획 실행 계획안",
  "mk:hasPurpose": "취약점 보강을 위한 구체적 실행 단계 제공",
  "mk:hasContext": "DecisionModel의 결정사항을 실행 가능한 단계로 분해",
  "mk-a04:hasAction": [
    "개념 비교 콘텐츠 제공: 정의와 예시를 비교하는 자료 제시",
    "예제 중심 학습 자료 제시: 개념을 예제로 먼저 접근",
    "학습 방법 변경: TTS에서 예제 풀이 중심으로 전환"
  ],
  "mk-a04:hasMeasurement": [
    "멈춤 빈도 감소 여부 (목표: 5회 → 2회 이하)",
    "개념 이해도 향상 여부 (목표: 40% → 70% 이상)",
    "집중도 점수 향상 여부 (목표: 0.6 → 0.8 이상)"
  ],
  "mk-a04:hasFeedback": [
    "다음 활동에서 개념 혼동 감소 여부 확인",
    "예제 중심 학습의 효과성 평가",
    "학생 만족도 및 학습 효율 피드백 수집"
  ],
  "mk-a04:hasAdjustment": [
    "예제 중심 학습이 효과 없으면 다른 방법 시도",
    "집중도가 여전히 낮으면 학습 시간 단축 및 휴식 추가",
    "개념 혼동이 지속되면 더 기본적인 개념부터 재학습"
  ],
  "mk-a04:hasContentLinks": [
    "concept_comparison_definition_vs_example",
    "example_focused_learning"
  ],
  "mk-a04:hasTimeline": "즉시 실행, 1주일 모니터링",
  "mk:hasParent": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
  "mk:referencesDecision": "mk-a04:WeakpointAnalysisDecisionModel/instance_001"
}
```

---

## 7. 열거형 타입 (Enumerated Types)

### 7.1 ActivityType

```json
{
  "@id": "mk-a04:ActivityType",
  "@type": "owl:Class",
  "rdfs:label": "활동 유형"
}
```

**인스턴스**:
- `mk-a04:ConceptUnderstanding` - 개념이해
- `mk-a04:TypeLearning` - 유형학습
- `mk-a04:ProblemSolving` - 문제풀이
- `mk-a04:MistakeNote` - 오답노트
- `mk-a04:QnA` - 질의응답
- `mk-a04:ReviewActivity` - 복습활동
- `mk-a04:Pomodoro` - 포모도르

### 7.2 SeverityLevel

```json
{
  "@id": "mk-a04:SeverityLevel",
  "@type": "owl:Class",
  "rdfs:label": "심각도 수준"
}
```

**인스턴스**:
- `mk-a04:Low` - 낮음
- `mk-a04:Medium` - 보통
- `mk-a04:High` - 높음
- `mk-a04:Critical` - 매우 높음

### 7.3 ReinforcementStrategy

```json
{
  "@id": "mk-a04:ReinforcementStrategy",
  "@type": "owl:Class",
  "rdfs:label": "보강 전략"
}
```

**인스턴스**:
- `mk-a04:ConceptClarificationStrategy` - 개념 명확화 전략
- `mk-a04:MethodOptimizationStrategy` - 방법 최적화 전략
- `mk-a04:AttentionRecoveryStrategy` - 주의 회복 전략
- `mk-a04:CombinationOptimizationStrategy` - 조합 최적화 전략
- `mk-a04:BoredomInterventionStrategy` - 지루함 개입 전략

### 7.4 PriorityLevel

```json
{
  "@id": "mk-a04:PriorityLevel",
  "@type": "owl:Class",
  "rdfs:label": "우선순위 수준"
}
```

**인스턴스**:
- `mk-a04:Low` - 낮음
- `mk-a04:Medium` - 보통
- `mk-a04:High` - 높음
- `mk-a04:Urgent` - 긴급

### 7.5 InterventionType

```json
{
  "@id": "mk-a04:InterventionType",
  "@type": "owl:Class",
  "rdfs:label": "개입 유형"
}
```

**인스턴스**:
- `mk-a04:ConceptWeakPointSupport` - 개념 취약점 지원
- `mk-a04:AttentionRecovery` - 주의 회복
- `mk-a04:ConceptClarification` - 개념 명확화
- `mk-a04:MethodOptimization` - 방법 최적화
- `mk-a04:VisualLearningOptimization` - 시각 학습 최적화
- `mk-a04:ExampleFocusedLearning` - 예제 중심 학습
- `mk-a04:CombinationOptimization` - 조합 최적화
- `mk-a04:BoredomIntervention` - 지루함 개입

### 7.6 ConfusionType

```json
{
  "@id": "mk-a04:ConfusionType",
  "@type": "owl:Class",
  "rdfs:label": "혼동 유형"
}
```

**인스턴스**:
- `mk-a04:DefinitionVsExample` - 정의 vs 예시
- `mk-a04:FormulaVsCondition` - 공식 vs 조건
- `mk-a04:SimilarConcepts` - 유사 개념

---

## 8. JSON-LD 매핑 규칙

### 8.1 기본 매핑 규칙

| DSL 구조 | JSON-LD 구조 |
|---------|-------------|
| `node "ID" { class: "mk-a04:Class" }` | `{ "@id": "mk-a04:Class/ID", "@type": "mk-a04:Class" }` |
| `property: "value"` | `"mk-a04:property": "value"` |
| `property: ["value1", "value2"]` | `"mk-a04:property": ["value1", "value2"]` |
| `metadata { stage: "Context" }` | `"mk:hasStage": "Context"` |
| `parent: "ParentID"` | `"mk:hasParent": "mk-a04:Class/ParentID"` |
| `usesContext: ["ID1", "ID2"]` | `"mk:usesContext": ["mk-a04:Class/ID1", "mk-a04:Class/ID2"]` |

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
    "mk-a04": "https://mathking.kr/ontology/agent04/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  },
  "@graph": [
    {
      "@id": "mk-a04:WeakpointDetectionContext/instance_001",
      "@type": "mk-a04:WeakpointDetectionContext",
      "mk:hasStage": "Context",
      "mk-a04:hasStudentId": 12345,
      "mk-a04:hasActivityType": "mk-a04:ConceptUnderstanding",
      "mk-a04:hasActivityCategory": "개념이해",
      "mk-a04:hasWeakpointSeverity": "mk-a04:High"
    },
    {
      "@id": "mk-a04:ActivityAnalysisContext/instance_001",
      "@type": "mk-a04:ActivityAnalysisContext",
      "mk:hasStage": "Context",
      "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001",
      "mk-a04:hasPauseFrequency": 5,
      "mk-a04:hasAttentionScore": 0.6
    },
    {
      "@id": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
      "@type": "mk-a04:WeakpointAnalysisDecisionModel",
      "mk:hasStage": "Decision",
      "mk:hasParent": "mk-a04:WeakpointDetectionContext/instance_001",
      "mk:usesContext": [
        "mk-a04:WeakpointDetectionContext/instance_001",
        "mk-a04:ActivityAnalysisContext/instance_001"
      ],
      "mk-a04:hasReinforcementStrategy": "mk-a04:ConceptClarificationStrategy",
      "mk-a04:hasReinforcementPriority": "mk-a04:High"
    },
    {
      "@id": "mk-a04:ReinforcementPlanExecutionPlan/instance_001",
      "@type": "mk-a04:ReinforcementPlanExecutionPlan",
      "mk:hasStage": "Execution",
      "mk:hasParent": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
      "mk:referencesDecision": "mk-a04:WeakpointAnalysisDecisionModel/instance_001",
      "mk-a04:hasAction": [
        "개념 비교 콘텐츠 제공",
        "예제 중심 학습 자료 제시"
      ]
    }
  ]
}
```

---

## 9. 데이터 무결성 제약 조건

### 9.1 필수 속성 제약

**WeakpointDetectionContext**:
- `mk-a04:hasStudentId` (필수)
- `mk-a04:hasActivityType` (필수)
- `mk-a04:hasActivityCategory` (필수)
- `mk-a04:hasWeakpointSeverity` (필수)
- `mk:hasStage` (필수)

**ActivityAnalysisContext**:
- `mk:hasParent` (필수, WeakpointDetectionContext 참조)
- `mk-a04:hasActivityStage` (필수)
- `mk-a04:hasPauseFrequency` (필수)
- `mk-a04:hasAttentionScore` (필수)
- `mk:hasStage` (필수)

**WeakpointAnalysisDecisionModel**:
- `mk:hasParent` (필수, WeakpointDetectionContext 참조)
- `mk:usesContext` (필수, 최소 2개 Context 참조)
- `mk-a04:hasWeakpointDescription` (필수)
- `mk-a04:hasRootCause` (필수)
- `mk-a04:hasReinforcementStrategy` (필수)
- `mk-a04:hasReinforcementPriority` (필수)
- `mk:hasStage` (필수)

**ReinforcementPlanExecutionPlan**:
- `mk:hasParent` (필수, WeakpointAnalysisDecisionModel 참조)
- `mk:referencesDecision` (필수, WeakpointAnalysisDecisionModel 참조)
- `mk-a04:hasAction` (필수, 최소 1개)
- `mk-a04:hasMeasurement` (필수, 최소 1개)
- `mk:hasStage` (필수)

### 9.2 관계 제약

- `mk:hasParent`는 순환 참조를 허용하지 않음
- `mk:usesContext`는 반드시 존재하는 Context 인스턴스를 참조해야 함
- `mk:referencesDecision`은 반드시 존재하는 Decision 인스턴스를 참조해야 함

### 9.3 타입 제약

- `mk-a04:hasAttentionScore`: 0.0 이상 1.0 이하의 실수
- `mk-a04:hasPerformanceMetrics`: 문자열 배열 (최소 1개 요소)
- `mk-a04:hasAction`: 문자열 배열 (최소 1개 요소)
- `mk-a04:hasWeakpointSeverity`: `mk-a04:SeverityLevel` 열거형 값만 허용
- `mk-a04:hasReinforcementStrategy`: `mk-a04:ReinforcementStrategy` 열거형 값만 허용

---

## 10. 온톨로지 스키마 다이어그램

```
┌─────────────────────────────┐
│  WeakpointDetectionContext │
│  (Context Layer)            │
│                             │
│  - hasStudentId            │
│  - hasActivityType         │
│  - hasWeakpointSeverity    │
│  - ...                      │
└───────────┬─────────────────┘
            │
            ├──────────────────┐
            │                  │
            ▼                  ▼
┌──────────────────────┐  ┌──────────────────────────────┐
│ ActivityAnalysis     │  │ WeakpointAnalysisDecision    │
│ Context              │  │ Model (Decision Layer)      │
│ (Context Layer)      │  │                             │
│                      │  │ - hasWeakpointDescription   │
│ - hasPauseFrequency  │  │ - hasRootCause              │
│ - hasAttentionScore  │  │ - hasReinforcementStrategy  │
│ - ...                │  │ - ...                       │
└──────────────────────┘  └───────────┬──────────────────┘
                                       │
                                       ▼
                            ┌──────────────────────────────┐
                            │ ReinforcementPlanExecution   │
                            │ Plan (Execution Layer)      │
                            │                             │
                            │ - hasAction                 │
                            │ - hasMeasurement            │
                            │ - hasFeedback               │
                            │ - hasAdjustment             │
                            └──────────────────────────────┘
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
**작성자**: Agent04 Ontology Team  
**다음 문서**: `02_ontology_types.md`

