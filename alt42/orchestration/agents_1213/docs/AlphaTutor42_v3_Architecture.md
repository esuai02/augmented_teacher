# AlphaTutor42 v3.0: 계측형 모듈러 플랫폼 아키텍처
**Technical Design Document**

> **버전**: 3.0  
> **날짜**: 2025-11-20  
> **핵심 철학**: 의도(Intent)를 가진 온톨로지, 측정 가능한(Measurable) 모듈 시스템

---

## 1. 아키텍처 비전 (Architectural Vision)

AlphaTutor42 시스템은 기존의 평면적 멀티 에이전트 구조에서 **"의도를 가진 온톨로지 기반의 계측형 모듈러 플랫폼"**으로 진화합니다.

### 핵심 변화 (Core Shifts)
1.  **구조적 변화**: Flat Multi-Agent → **4-Layered Architecture** (Runtime, Event, Ontology, Reasoning)
2.  **의도의 구체화**: 암묵적 룰 → **온톨로지 1급 객체** (Goal, Policy, Constraint)
3.  **계측의 내재화**: 단순 로그 → **표준화된 이벤트 & 메트릭** (Latency, Confidence, Delta)
4.  **모듈화**: 22개 에이전트 → **5개 기능 모듈** (Profile, Learning, Emotion, Interaction, Meta)

---

## 2. 4-Layer 아키텍처 (The 4 Layers)

### Layer 1: Reasoning & Policy (의지/의도 계층)
*   **역할**: 시스템의 "뇌". 최상위 의지(Will)가 구체적인 정책으로 발현되는 곳입니다.
*   **구성요소**:
    *   **Goal Tree**: 목표 계층 (성장, 정서, 몰입 등)
    *   **Policy Engine**: 현재 맥락에 맞는 목표와 제약조건을 활성화
    *   **Orchestrator**: 활성화된 정책에 따라 실행할 모듈/에이전트 결정
*   **핵심**: "의지는 에이전트가 아닌 온톨로지와 정책에 내재된다."

### Layer 2: Ontology (월드 모델 계층)
*   **역할**: 시스템의 "기억"이자 "맥락".
*   **구조**: 학생과 세계의 상태를 표현하는 그래프 데이터베이스.
*   **3축 설계 (3-Axis Design)**:
    1.  **Time Scale**: Event(초) < Session(분) < Period(일/월)
    2.  **Abstraction**: Task(구체) < Pattern(패턴) < Policy(추상)
    3.  **Control**: Local(즉시) < Tactical(전술) < Strategic(전략)

### Layer 3: Event & Metrics (신경망 계층)
*   **역할**: 통신 및 측정.
*   **메커니즘**: 모든 에이전트의 입출력은 표준화된 이벤트로 캡처되며, 성과 지표(Metric)를 포함합니다.
*   **스키마**: `event_id`, `type`, `timestamp`, `agent_id`, `metrics` (latency, confidence, delta_score).

### Layer 4: Runtime Agent (실행 계층)
*   **역할**: 관찰 및 실행 (Actuators & Observers).
*   **구성**: 5개 모듈로 그룹화된 22개 전문 에이전트.
*   **동작**: 온톨로지/이벤트를 읽고(Read), 판단 후 액션/이벤트를 발행(Write)하는 Stateless 유닛.

---

## 3. 온톨로지 레이어 설계 (Ontology Layer Design)

### 3.1. 핵심 엔티티 (Core Entities)
*   **Actors**: `Student`, `Agent`
*   **Context**: `Session`, `Task`, `Concept`
*   **State**: `EmotionState`, `LearningState`, `RiskState`
*   **Planning**: `Plan`, `Intervention`
*   **Intent**: `Goal`, `Policy`, `Constraint`

### 3.2. 의미론적 관계 (Semantic Relationships)
단순한 호출 관계를 의미론적 관계로 재정의합니다:
*   `observes(Agent, Entity)`: 에이전트가 관찰하는 대상
*   `updates(Agent, Entity)`: 에이전트가 변경하는 대상
*   `predicts(Agent, Entity)`: 에이전트가 예측하는 미래 상태
*   `mitigates(Intervention, RiskState)`: 개입이 완화하려는 위험
*   `supports(Intervention, Goal)`: 개입이 지지하는 상위 목표
*   `triggers(Event, Event)`: 이벤트 간의 인과 관계

### 3.3. 의도 객체 (Intent Objects)
*   **Goal**: 예) `MaximizeLearningGain`, `PreventDropout`
*   **Policy**: 예) `DropoutEmergencyPolicy` (Risk > 0.7이면 정서 안정 최우선)
*   **Constraint**: 예) `MaxDailyStudyTime` (일일 학습 시간 제한)

---

## 4. 모듈형 에이전트 구조 (Modular Agent Structure)

22개 에이전트를 기능별 5개 모듈로 재편하여 응집도를 높이고 결합도를 낮춥니다.

### Module A: Long-term Profile & Planning
*   **Agents**: 01(온보딩), 02(시험), 03(목표), 14(위치), 17(남은활동), 18(루틴)
*   **Focus**: 장기적 전략 수립, 학생 아이덴티티 관리
*   **Ontology**: `Student`, `Plan`, `SignatureRoutine`

### Module B: Real-time Learning Loop
*   **Agents**: 09(관리), 04(취약점), 10(개념), 11(문제), 15(재정의)
*   **Focus**: 인지적 과제 수행, 즉각적인 학습 피드백
*   **Ontology**: `Task`, `Concept`, `LearningState`

### Module C: Emotion & Resilience
*   **Agents**: 05(정서), 08(평정심), 12(휴식), 13(이탈)
*   **Focus**: 정서 상태 관리, 리듬 조절, 이탈 방지
*   **Ontology**: `EmotionState`, `RiskState`

### Module D: Interaction & Intervention
*   **Agents**: 07(타겟팅), 16(준비), 19(내용), 20(개입준비), 21(실행)
*   **Focus**: 사용자 경험(UX) 구성, 개입 전달
*   **Ontology**: `Intervention`, `InteractionTarget`

### Module E: Meta & Improvement
*   **Agents**: 06(피드백), 22(개선)
*   **Focus**: 시스템 진화, 교사 피드백 정렬
*   **Ontology**: `Policy`, `ImprovementSuggestion`

---

## 5. 계측 및 메트릭 (Instrumentation & Metrics)

### 공통 이벤트 스키마 (Common Event Schema)
```json
{
  "event_id": "uuid-v4",
  "type": "EmotionStateUpdated",
  "agent_id": "A05",
  "timestamp": "2025-11-20T10:00:00Z",
  "context": {
    "student_id": "s123",
    "session_id": "sess456"
  },
  "metrics": {
    "latency_ms": 120,
    "confidence": 0.85,
    "delta_score": 0.2
  },
  "payload": {
    "state": "focused",
    "intensity": 0.7
  }
}
```

### 성과 측정 (Success Metrics)
*   **Agent Performance**: 개별 에이전트의 작업 성공률 및 지연 시간
*   **Policy Effectiveness**: 특정 정책 적용 시 장기 목표(Goal) 달성도 변화

---

## 6. 수직 통합 추론 (Vertical Integrated Reasoning)

시스템은 온톨로지의 3축을 따라 수직으로 이동하며 추론합니다.

1.  **Local Control (즉시)**: "지금 문제 틀렸음" → Module B (오답 분석)
2.  **Tactical Control (전술)**: "오늘 컨디션 난조" → Module C (휴식 제안) + Module A (일정 조정)
3.  **Strategic Control (전략)**: "장기적 학습 패턴 변화" → Module E (정책 수정) + Module A (루틴 변경)

이러한 수직 이동은 **Policy Engine**에 의해 자동으로 조율됩니다.

---

## 7. 구현 로드맵 (Implementation Roadmap)

1.  **Schema Definition**: 온톨로지 엔티티 및 이벤트 스키마 정의 (YAML/JSON)
2.  **Event Bus Implementation**: 계측 레이어 구현
3.  **Policy Migration**: 하드코딩된 협업 패턴을 Policy 객체로 변환
4.  **Graph Migration**: 기존 의존성을 온톨로지 관계(Edge)로 매핑
5.  **Dashboarding**: 메트릭 시각화 및 모니터링 구축

---
