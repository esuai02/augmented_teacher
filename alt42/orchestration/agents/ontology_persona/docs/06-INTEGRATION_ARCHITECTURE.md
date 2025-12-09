# Integration Architecture - Complete System Design

**Version**: 1.0.0
**Last Updated**: 2025-01-29
**Status**: Draft

---

## Purpose

This document specifies the **complete integration architecture** that brings together all system components:
- **21 Mathking Agents** (doc 01)
- **Agent & Task Collaboration Patterns** (doc 02)
- **LLM-Optimized Knowledge Base** (doc 03)
- **6-Layer Ontology System** (doc 04)
- **Hybrid Reasoning Engine** (doc 05)

The architecture ensures seamless communication, data flow, scalability, reliability, and security across the entire Mathking AI Tutor system.

---

## 1. System Overview

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        Mathking AI Tutor System                          │
│                                                                           │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                     Presentation Layer                            │  │
│  │  ┌─────────────┐  ┌──────────────┐  ┌─────────────┐            │  │
│  │  │  Student    │  │   Teacher    │  │   Parent    │            │  │
│  │  │  Dashboard  │  │   Dashboard  │  │   Portal    │            │  │
│  │  └─────────────┘  └──────────────┘  └─────────────┘            │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                               │                                          │
│                               ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                   API Gateway Layer                               │  │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐    │  │
│  │  │  Agent API     │  │  Reporting API │  │  Ontology API  │    │  │
│  │  │  Gateway       │  │  Service       │  │  Service       │    │  │
│  │  └────────────────┘  └────────────────┘  └────────────────┘    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                               │                                          │
│                               ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                   Agent Orchestration Layer                       │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │  Heartbeat Scheduler + Agent Registry + Task Queue         │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  │  ┌────────────────┬────────────────┬────────────────────────┐  │  │
│  │  │  Agent 1-7     │  Agent 8-14    │  Agent 15-21          │  │  │
│  │  │  (Core)        │  (Support)     │  (Support)            │  │  │
│  │  └────────────────┴────────────────┴────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                               │                                          │
│                               ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                   Reasoning & Decision Layer                      │  │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐    │  │
│  │  │  Rule Engine   │  │  LLM Reasoner  │  │  Directive     │    │  │
│  │  │  (DSL Parser)  │  │  (GPT-4/Claude)│  │  Calculator    │    │  │
│  │  └────────────────┘  └────────────────┘  └────────────────┘    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                               │                                          │
│                               ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                   Knowledge & Ontology Layer                      │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │  Ontology (JSON-LD) - 6 Layers + SPARQL Engine            │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │  Knowledge Base (Markdown + YAML) - Rules + Prompts       │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                               │                                          │
│                               ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                   Data & Integration Layer                        │  │
│  │  ┌────────────────┬────────────────┬────────────────────────┐   │  │
│  │  │  Moodle DB     │  Persona DB    │  Activity Logs DB      │   │  │
│  │  │  (MySQL)       │  (PostgreSQL)  │  (MongoDB)             │   │  │
│  │  └────────────────┴────────────────┴────────────────────────┘   │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │  LMS Activity API (Moodle Integration)                     │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Layer Responsibilities

| Layer | Responsibility | Key Components |
|-------|----------------|----------------|
| **Presentation** | User interfaces for students, teachers, parents | Dashboards, portals, mobile apps |
| **API Gateway** | External API access, authentication, rate limiting | REST APIs, GraphQL, WebSocket |
| **Agent Orchestration** | Agent lifecycle, heartbeat scheduling, task routing | 22 agents, registry, scheduler |
| **Reasoning & Decision** | Evidence evaluation, decision making, intervention generation | Rule engine, LLM, directive calculator |
| **Knowledge & Ontology** | Single source of truth for system knowledge | JSON-LD ontology, knowledge base files |
| **Data & Integration** | Persistent storage, external system integration | Databases, Moodle API, logs |

---

## 2. Component Integration Map

### 2.1 Document Cross-Reference

```
┌──────────────────┐
│  Doc 01: Agents  │────┐
│  (22 agents)     │    │
└──────────────────┘    │
                        │
┌──────────────────┐    │    ┌──────────────────────┐
│  Doc 02:         │    │    │  Doc 06: Integration │
│  Collaboration   │────┼───▶│  Architecture        │
│  Patterns        │    │    │  (This Document)     │
└──────────────────┘    │    └──────────────────────┘
                        │
┌──────────────────┐    │
│  Doc 03:         │    │
│  Knowledge Base  │────┤
└──────────────────┘    │
                        │
┌──────────────────┐    │
│  Doc 04:         │    │
│  Ontology System │────┤
└──────────────────┘    │
                        │
┌──────────────────┐    │
│  Doc 05:         │    │
│  Reasoning Engine│────┘
└──────────────────┘
```

**Integration Dependencies**:

- **Doc 01 → Doc 02**: Agents generate evidence packages → Collaboration patterns consume evidence
- **Doc 02 → Doc 05**: Collaboration patterns trigger → Reasoning engine evaluates
- **Doc 03 → Doc 05**: Knowledge base provides rules/prompts → Reasoning engine loads and uses
- **Doc 04 → All**: Ontology provides metadata → All components query for context
- **Doc 05 → Doc 01**: Decisions generate actions → Agents execute actions

### 2.2 Component Communication Matrix

| Source Component | Target Component | Communication Type | Data Format | Frequency |
|------------------|------------------|--------------------|-------------|-----------|
| **Agent** → **Reasoning Engine** | Evidence submission | Sync API call | EvidencePackage (JSON) | Per heartbeat (15-1440min) |
| **Reasoning Engine** → **Agent** | Decision result | Sync response | DecisionResult (JSON) | Immediate response |
| **Agent** → **Ontology** | Metadata query | SPARQL query | SPARQL | On-demand |
| **Agent** → **Collaboration System** | Task link creation | Async message | TaskLink (JSON) | When collaboration triggered |
| **Reasoning Engine** → **LLM** | Inference request | HTTP API call | Prompt (text) + Context (JSON) | When LLM needed (~30% of decisions) |
| **Agent** → **Moodle API** | Activity execution | REST API call | Moodle-specific params | When action involves LMS |
| **Heartbeat Scheduler** → **Agents** | Execution trigger | Event notification | Trigger event (JSON) | Per agent heartbeat interval |
| **Agent** → **DB** | State persistence | Database write | Structured data | After each action |

---

## 3. Data Flow Diagrams

### 3.1 End-to-End Student Intervention Flow

```
[Student Activity in Moodle]
         │
         ▼
[Activity Log → Evidence Collector]
         │
         ▼
[Evidence Package Created]
         │
         ▼
[Heartbeat Scheduler Triggers Agent]
         │
         ▼
[Agent Retrieves Evidence Package]
         │
         ▼
[Agent Submits Evidence to Reasoning Engine]
         │
         ▼
[Reasoning Engine: Rule Evaluation]
         │
    ┌────┴────┐
    │         │
[Match Found] [No Match / Ambiguous]
    │         │
    │         ▼
    │    [LLM Reasoner Invoked]
    │         │
    │         ▼
    │    [LLM Response Parsed]
    │         │
    └────┬────┘
         │
         ▼
[Decision Result Generated]
         │
         ▼
[Directive Strength Calculated]
         │
         ▼
[Safety Guardrails Checked]
         │
    ┌────┴────┐
    │         │
 [Safe]   [Unsafe]
    │         │
    │         ▼
    │    [Decision Blocked / Adjusted]
    │         │
    └────┬────┘
         │
         ▼
[Report/Directive Template Rendered]
         │
         ▼
[Action Executed in Moodle (if needed)]
         │
         ▼
[Report/Directive Delivered to Student]
         │
         ▼
[Student Feedback Collected]
         │
         ▼
[Feedback Used for Rollback / Improvement]
```

### 3.2 Multi-Agent Collaboration Flow

```
[Agent A: Detects Issue (e.g., progress lagging)]
         │
         ▼
[Agent A: Checks Collaboration Patterns in Ontology]
         │
         ▼
[Collaboration Pattern Matched: "Academic Recovery"]
         │
         ▼
[Agent A: Creates Task Links]
         │
    ┌────┴────┬────────┬────────┐
    │         │        │        │
    ▼         ▼        ▼        ▼
[Agent B]  [Agent C] [Agent D] [Agent E]
(Adaptive) (Time    (Cognitive)(Emotion)
           Mgmt)
    │         │        │        │
    │         │        │        │
    └────┬────┴────┬───┴────┬───┘
         │         │        │
         ▼         ▼        ▼
    [Task 1]   [Task 2]  [Task 3]
    (Analyze)  (Adjust)  (Monitor)
         │         │        │
         └────┬────┴────┬───┘
              │         │
              ▼         ▼
       [Collaboration Results Aggregated]
              │
              ▼
       [Final Intervention Generated]
              │
              ▼
       [Delivered to Student]
```

### 3.3 Knowledge Base Update Flow

```
[Developer Edits knowledge/의사결정_지식.md]
         │
         ▼
[File Saved to Repository]
         │
         ▼
[Git Commit + Push]
         │
         ▼
[CI/CD Pipeline Triggered]
         │
         ▼
[Knowledge Base Validation Script]
         │
    ┌────┴────┐
    │         │
[Valid]   [Invalid]
    │         │
    │         ▼
    │    [Validation Error Reported]
    │         │
    │         ▼
    │    [Deployment Blocked]
    │
    ▼
[Knowledge Base Deployed to Production]
         │
         ▼
[Reasoning Engine Reloads Rules/Prompts]
         │
         ▼
[New Rules Active]
```

---

## 4. API Specifications

### 4.1 Agent Gateway API

**Base URL**: `https://mathking.kr/api/v1/agents`

**Endpoints**:

#### 4.1.1 Submit Evidence

```http
POST /evidence
Content-Type: application/json
Authorization: Bearer {agent_token}

Request Body:
{
  "agent_id": "agent_curriculum",
  "student_id": "student_12345",
  "timestamp": "2025-01-29T10:30:00Z",
  "category": "academic_performance",
  "metrics": {
    "progress_delta": -0.18,
    "accuracy_rate": 0.55,
    "completion_rate": 0.70
  },
  "state": {
    "affect": "frustrated",
    "engagement": "low"
  },
  "persona_type": "perfectionist",
  "persona_similarity": 0.82
}

Response:
{
  "decision_id": "dec_abc123",
  "decision_type": "llm_based",
  "action": "generate_directive",
  "template_id": "directive_difficulty_adjust",
  "params": {
    "target_difficulty": "medium",
    "reason": "학생이 현재 어려움을 겪고 있습니다"
  },
  "directive_strength": 0.75,
  "priority": 0.85,
  "reasoning": "학생의 정답률이 낮고 좌절감을 느끼고 있으므로 난이도 조정 필요",
  "confidence": 0.80,
  "execution_time_ms": 2150
}
```

#### 4.1.2 Get Agent Status

```http
GET /agents/{agent_id}/status
Authorization: Bearer {admin_token}

Response:
{
  "agent_id": "agent_curriculum",
  "status": "active",
  "heartbeat_interval_min": 30,
  "last_execution": "2025-01-29T10:00:00Z",
  "next_execution": "2025-01-29T10:30:00Z",
  "total_decisions_today": 45,
  "success_rate": 0.92
}
```

#### 4.1.3 Execute Action

```http
POST /actions/execute
Content-Type: application/json
Authorization: Bearer {agent_token}

Request Body:
{
  "agent_id": "agent_adaptive",
  "student_id": "student_12345",
  "action_type": "adjust_difficulty",
  "params": {
    "content_id": "quiz_789",
    "target_difficulty": "easy"
  }
}

Response:
{
  "action_id": "act_xyz456",
  "status": "executed",
  "lms_activity_id": "moodle_quiz_789",
  "execution_time_ms": 350,
  "result": {
    "difficulty_changed": true,
    "previous_difficulty": "medium",
    "new_difficulty": "easy"
  }
}
```

### 4.2 Reporting API

**Base URL**: `https://mathking.kr/api/v1/reports`

**Endpoints**:

#### 4.2.1 Generate Report

```http
POST /generate
Content-Type: application/json
Authorization: Bearer {teacher_token}

Request Body:
{
  "student_id": "student_12345",
  "report_type": "weekly_progress",
  "lookback_days": 7
}

Response:
{
  "report_id": "rep_abc789",
  "student_id": "student_12345",
  "generated_at": "2025-01-29T11:00:00Z",
  "content": {
    "title": "주간 학습 리포트",
    "summary": "이번 주 학생의 진도율은 평균 대비 15% 낮습니다...",
    "metrics": {
      "progress_rate": 0.68,
      "accuracy_rate": 0.75,
      "time_on_task": 180
    },
    "recommendations": [
      "난이도를 낮추어 자신감을 회복하세요",
      "매일 20분씩 꾸준히 학습하는 습관을 만드세요"
    ],
    "chart_urls": [
      "https://mathking.kr/charts/progress_12345.png"
    ]
  }
}
```

### 4.3 Ontology API

**Base URL**: `https://mathking.kr/api/v1/ontology`

**Endpoints**:

#### 4.3.1 Query Ontology (SPARQL)

```http
POST /query
Content-Type: application/sparql-query
Authorization: Bearer {service_token}

Request Body:
PREFIX mk: <https://mathking.kr/ontology#>
SELECT ?pattern ?label ?priority
WHERE {
  ?pattern a mk:CollaborationPattern .
  ?pattern rdfs:label ?label .
  ?pattern mk:priority ?priority .
  FILTER (?priority > 0.7)
}
ORDER BY DESC(?priority)

Response:
{
  "results": [
    {
      "pattern": "mk:CollaborationPattern/AcademicRecovery",
      "label": "Academic Performance Recovery Pattern",
      "priority": 0.85
    },
    {
      "pattern": "mk:CollaborationPattern/ExamPreparation",
      "label": "Exam Preparation Pattern",
      "priority": 0.80
    }
  ]
}
```

#### 4.3.2 Find Collaboration Pattern

```http
GET /collaboration-patterns?evidence_categories=academic_performance,emotional_state
Authorization: Bearer {agent_token}

Response:
{
  "patterns": [
    {
      "id": "mk:CollaborationPattern/AcademicRecovery",
      "label": "Academic Performance Recovery Pattern",
      "trigger_categories": [
        "academic_performance.progress_lagging",
        "emotional_state.frustration"
      ],
      "participating_agents": [
        "agent_curriculum",
        "agent_adaptive",
        "agent_emotion",
        "agent_cognitive"
      ],
      "priority": 0.85
    }
  ]
}
```

---

## 5. Message Formats

### 5.1 Evidence Package

**Standard Format** (all agents must conform):

```json
{
  "agent_id": "agent_curriculum",
  "student_id": "student_12345",
  "timestamp": "2025-01-29T10:30:00Z",
  "category": "academic_performance",
  "subcategory": "progress_lagging",
  "metrics": {
    "progress_delta": -0.18,
    "accuracy_rate": 0.55,
    "completion_rate": 0.70,
    "time_on_task": 120,
    "retry_count": 3
  },
  "state": {
    "affect": "frustrated",
    "engagement": "low",
    "motivation": "declining"
  },
  "context": {
    "current_topic": "Algebra - Quadratic Equations",
    "difficulty_level": "medium",
    "days_until_exam": 14
  },
  "persona_type": "perfectionist",
  "persona_similarity": 0.82
}
```

### 5.2 Decision Result

**Standard Format**:

```json
{
  "decision_id": "dec_abc123",
  "timestamp": "2025-01-29T10:30:15Z",
  "decision_type": "llm_based",
  "action": "generate_directive",
  "template_id": "directive_difficulty_adjust",
  "params": {
    "target_difficulty": "easy",
    "content_id": "quiz_789",
    "reason": "학생이 현재 어려움을 겪고 있습니다"
  },
  "directive_strength": 0.75,
  "priority": 0.85,
  "reasoning": "학생의 정답률이 낮고 좌절감을 느끼고 있으므로 난이도 조정 필요",
  "confidence": 0.80,
  "execution_time_ms": 2150,
  "token_usage": 2800
}
```

### 5.3 Task Link

**Standard Format** (for multi-agent collaboration):

```json
{
  "link_id": "link_xyz789",
  "source_agent": "agent_curriculum",
  "target_agent": "agent_adaptive",
  "task_type": "adjust_content_difficulty",
  "priority": 0.90,
  "created_at": "2025-01-29T10:30:20Z",
  "expires_at": "2025-01-29T12:30:20Z",
  "payload": {
    "student_id": "student_12345",
    "current_difficulty": "medium",
    "recommended_difficulty": "easy",
    "reason": "학생이 현재 내용에 어려움을 겪고 있음"
  },
  "status": "pending"
}
```

### 5.4 Action Execution

**Standard Format**:

```json
{
  "action_id": "act_xyz456",
  "agent_id": "agent_adaptive",
  "student_id": "student_12345",
  "action_type": "adjust_difficulty",
  "timestamp": "2025-01-29T10:30:25Z",
  "params": {
    "content_id": "quiz_789",
    "target_difficulty": "easy"
  },
  "lms_mapping": {
    "activity_type": "quiz",
    "moodle_method": "mod_quiz_modify_questions",
    "parameters": {
      "quiz_id": 789,
      "difficulty": "easy"
    }
  },
  "status": "executed",
  "execution_time_ms": 350,
  "result": {
    "success": true,
    "details": {
      "difficulty_changed": true,
      "previous_difficulty": "medium",
      "new_difficulty": "easy"
    }
  }
}
```

---

## 6. Integration Patterns

### 6.1 Request-Response Pattern

**Use Case**: Agent submits evidence and receives immediate decision.

**Pattern**:
1. Agent makes synchronous HTTP POST to `/api/v1/agents/evidence`
2. Reasoning Engine processes evidence (rules + LLM if needed)
3. Response returned with decision result
4. Agent executes action based on decision

**Advantages**:
- Simple to implement
- Immediate feedback
- Strong consistency

**Disadvantages**:
- Blocking (agent waits for response)
- Latency sensitive to LLM call time

**When to Use**:
- Real-time decisions required
- Low latency critical
- Strong consistency needed

### 6.2 Event-Driven Pattern

**Use Case**: Heartbeat scheduler triggers agent execution.

**Pattern**:
1. Scheduler publishes `AgentExecutionEvent` to message queue
2. Agent subscribes to event and executes when triggered
3. Agent processes evidence and makes decision request
4. Result stored in database and/or notification sent

**Advantages**:
- Decoupled (scheduler doesn't wait for agent completion)
- Scalable (multiple agents process in parallel)
- Resilient (failed events can be retried)

**Disadvantages**:
- Eventually consistent
- More complex to implement
- Requires message queue infrastructure

**When to Use**:
- High throughput required
- Parallel processing needed
- Decoupling desired

### 6.3 Publish-Subscribe Pattern

**Use Case**: Multiple agents need to react to same evidence.

**Pattern**:
1. Evidence collector publishes `EvidenceAvailableEvent` to topic
2. Multiple agents subscribe to evidence topic
3. Each agent independently processes evidence
4. Agents coordinate via task links if collaboration needed

**Advantages**:
- Fan-out (one event, many consumers)
- Flexible (new agents can subscribe without changing publisher)
- Scalable

**Disadvantages**:
- Eventual consistency
- Potential duplication if not careful
- Requires pub-sub infrastructure

**When to Use**:
- Multiple agents need same data
- Fan-out required
- Loose coupling desired

### 6.4 Command Pattern

**Use Case**: External system (teacher dashboard) triggers agent action.

**Pattern**:
1. Teacher dashboard sends `ExecuteActionCommand`
2. Command handler validates command
3. Command routed to appropriate agent
4. Agent executes action and returns result

**Advantages**:
- Clear separation of command and execution
- Easy to add authorization/validation
- Auditable (commands logged)

**Disadvantages**:
- Extra layer of indirection
- Requires command handler infrastructure

**When to Use**:
- External triggers required
- Authorization/validation needed
- Audit trail important

---

## 7. Deployment Architecture

### 7.1 Physical Deployment Topology

```
┌─────────────────────────────────────────────────────────────────┐
│                      Load Balancer (Nginx)                       │
└─────────────────────────────────────────────────────────────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
                ▼                             ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│  Application Server 1    │  │  Application Server 2    │
│  - API Gateway           │  │  - API Gateway           │
│  - Agent Orchestration   │  │  - Agent Orchestration   │
│  - Reasoning Engine      │  │  - Reasoning Engine      │
│  (Python 3.10)           │  │  (Python 3.10)           │
└──────────────────────────┘  └──────────────────────────┘
                │                             │
                └──────────────┬──────────────┘
                               │
                ┌──────────────┴──────────────┬──────────────┐
                │                             │              │
                ▼                             ▼              ▼
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────┐
│  Moodle DB (MySQL)   │  │  Persona DB          │  │  Activity Logs   │
│  (Existing)          │  │  (PostgreSQL)        │  │  (MongoDB)       │
└──────────────────────┘  └──────────────────────┘  └──────────────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
                ▼                             ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│  Redis Cache             │  │  Message Queue (RabbitMQ)│
│  (Decision cache,        │  │  (Heartbeat events,      │
│   Session cache)         │  │   Task links)            │
└──────────────────────────┘  └──────────────────────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
                ▼                             ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│  External LLM API        │  │  Monitoring Stack        │
│  - OpenAI GPT-4          │  │  - Prometheus            │
│  - Anthropic Claude      │  │  - Grafana               │
└──────────────────────────┘  │  - ELK Stack             │
                               └──────────────────────────┘
```

### 7.2 Deployment Units

| Unit | Technology | Purpose | Replicas |
|------|------------|---------|----------|
| **API Gateway** | FastAPI (Python) | REST API endpoints, authentication, rate limiting | 2+ |
| **Agent Orchestrator** | Python + Celery | Agent execution, heartbeat scheduling | 2+ |
| **Reasoning Engine** | Python | Rule evaluation, LLM inference | 2+ |
| **Ontology Service** | RDFLib + SPARQL | Ontology queries | 2+ |
| **Worker Pool** | Celery Workers | Async task processing | 5+ |
| **Moodle DB** | MySQL 5.7 | Existing LMS database | 1 (master) + 1 (replica) |
| **Persona DB** | PostgreSQL 12+ | Student persona data | 1 (master) + 1 (replica) |
| **Activity Logs** | MongoDB 4.4+ | High-volume activity logs | 3-node replica set |
| **Cache** | Redis 6+ | Decision cache, session cache | 1 (master) + 2 (replicas) |
| **Message Queue** | RabbitMQ 3.9+ | Event-driven communication | 3-node cluster |

### 7.3 Containerization Strategy

**Docker Compose Configuration** (simplified):

```yaml
version: '3.8'

services:
  api_gateway:
    image: mathking/api-gateway:1.0.0
    ports:
      - "8000:8000"
    environment:
      - DATABASE_URL=postgresql://user:pass@persona_db:5432/mathking
      - REDIS_URL=redis://redis:6379/0
      - RABBITMQ_URL=amqp://user:pass@rabbitmq:5672
    depends_on:
      - redis
      - rabbitmq
      - persona_db
    deploy:
      replicas: 2

  agent_orchestrator:
    image: mathking/agent-orchestrator:1.0.0
    environment:
      - REDIS_URL=redis://redis:6379/0
      - RABBITMQ_URL=amqp://user:pass@rabbitmq:5672
    depends_on:
      - redis
      - rabbitmq
    deploy:
      replicas: 2

  reasoning_engine:
    image: mathking/reasoning-engine:1.0.0
    environment:
      - OPENAI_API_KEY=${OPENAI_API_KEY}
      - REDIS_URL=redis://redis:6379/0
    depends_on:
      - redis
    deploy:
      replicas: 2

  worker:
    image: mathking/celery-worker:1.0.0
    command: celery -A tasks worker --loglevel=info
    environment:
      - REDIS_URL=redis://redis:6379/0
      - RABBITMQ_URL=amqp://user:pass@rabbitmq:5672
    depends_on:
      - redis
      - rabbitmq
    deploy:
      replicas: 5

  persona_db:
    image: postgres:12
    environment:
      - POSTGRES_USER=mathking
      - POSTGRES_PASSWORD=secure_password
      - POSTGRES_DB=mathking
    volumes:
      - persona_data:/var/lib/postgresql/data

  redis:
    image: redis:6-alpine
    volumes:
      - redis_data:/data

  rabbitmq:
    image: rabbitmq:3.9-management-alpine
    ports:
      - "15672:15672"
    environment:
      - RABBITMQ_DEFAULT_USER=mathking
      - RABBITMQ_DEFAULT_PASS=secure_password

volumes:
  persona_data:
  redis_data:
```

---

## 8. Scalability Considerations

### 8.1 Horizontal Scaling Strategies

**Component-Level Scaling**:

| Component | Scaling Dimension | Strategy | Trigger |
|-----------|-------------------|----------|---------|
| **API Gateway** | Request volume | Add replicas (auto-scale) | CPU >70% or RPS >1000 |
| **Agent Orchestrator** | Agent execution load | Add replicas + partition agents by ID | Queue depth >100 |
| **Reasoning Engine** | LLM inference load | Add replicas with load balancing | Response time >5s |
| **Worker Pool** | Async task volume | Add Celery workers | Queue depth >50 |
| **Redis Cache** | Cache hit rate | Add read replicas | Read QPS >10K |
| **RabbitMQ** | Message throughput | Add nodes to cluster | Queue depth >1000 |
| **Databases** | Query load | Read replicas + sharding | Slow query >100ms |

### 8.2 Partitioning Strategies

**Agent Partitioning by Student Cohort**:

```
Agent Orchestrator 1: Students with ID % 3 == 0
Agent Orchestrator 2: Students with ID % 3 == 1
Agent Orchestrator 3: Students with ID % 3 == 2
```

**Advantages**:
- Even load distribution
- Reduced contention (each orchestrator handles different students)
- Parallel processing

**Database Sharding**:

```
Persona DB Shard 1: Student IDs 1-10000
Persona DB Shard 2: Student IDs 10001-20000
Persona DB Shard 3: Student IDs 20001-30000
```

### 8.3 Caching Strategies

**Multi-Level Cache**:

```
Level 1: In-Memory Cache (application server)
  - Decision cache (TTL: 1 hour)
  - Ontology query cache (TTL: 24 hours)

Level 2: Redis Cache (distributed)
  - Session cache (TTL: 30 minutes)
  - Evidence package cache (TTL: 15 minutes)
  - LLM response cache (TTL: 6 hours)

Level 3: CDN Cache (static assets)
  - Knowledge base files (TTL: 1 week)
  - Ontology JSON-LD (TTL: 1 day)
```

**Cache Invalidation Strategy**:

- **Time-based**: Most caches use TTL (Time To Live)
- **Event-based**: Knowledge base updates trigger cache invalidation
- **Manual**: Admin can manually flush cache via API

---

## 9. Reliability & Fault Tolerance

### 9.1 Failure Scenarios and Mitigation

| Failure Scenario | Impact | Mitigation Strategy | Recovery Time |
|------------------|--------|---------------------|---------------|
| **LLM API Unavailable** | Decision delays | Fallback to rules-only mode + queue for retry | <1 minute |
| **Database Connection Lost** | Data write failures | Connection pooling + retry with exponential backoff | <30 seconds |
| **Agent Crash** | Missed heartbeat | Health check + auto-restart + catchup on next cycle | <5 minutes |
| **Message Queue Down** | Event loss | Persistent messages + dead letter queue | <10 minutes |
| **Cache Unavailable** | Performance degradation | Bypass cache + direct database access | Immediate |
| **Ontology Service Down** | Metadata unavailable | In-memory fallback + cached ontology | Immediate |

### 9.2 Circuit Breaker Pattern

**Implementation** (for LLM API calls):

```python
from circuitbreaker import circuit

@circuit(failure_threshold=5, recovery_timeout=60, expected_exception=LLMAPIError)
def call_llm_api(prompt: str) -> str:
    """
    LLM API 호출 (Circuit Breaker 패턴 적용)

    - 5번 연속 실패 시 Circuit Open (60초 동안 호출 차단)
    - 60초 후 Half-Open 상태로 전환하여 재시도
    - 성공 시 Circuit Closed (정상 동작)
    """
    response = llm_client.chat.completions.create(...)
    return response.choices[0].message.content
```

**States**:
- **Closed**: Normal operation, all calls go through
- **Open**: Failures exceeded threshold, all calls immediately fail without attempting
- **Half-Open**: Recovery timeout elapsed, single test call allowed

### 9.3 Graceful Degradation

**Degradation Levels**:

| Level | Service Level | Available Features |
|-------|--------------|-------------------|
| **Level 0 (Normal)** | 100% | All features enabled |
| **Level 1 (LLM Unavailable)** | 85% | Rules-only decision making, cached reports |
| **Level 2 (Ontology Unavailable)** | 70% | Static mappings, no dynamic collaboration |
| **Level 3 (Emergency)** | 50% | Read-only mode, basic reporting only |

**Automatic Degradation Logic**:

```python
def get_current_service_level() -> int:
    """
    현재 서비스 수준 결정

    Returns:
        0: Normal, 1: LLM unavailable, 2: Ontology unavailable, 3: Emergency
    """
    if not llm_health_check() and not ontology_health_check():
        return 3  # Emergency
    elif not ontology_health_check():
        return 2  # Ontology unavailable
    elif not llm_health_check():
        return 1  # LLM unavailable
    else:
        return 0  # Normal
```

---

## 10. Security Architecture

### 10.1 Authentication & Authorization

**Multi-Tier Security**:

```
┌────────────────────────────────────────────────────────────┐
│  External Users (Students, Teachers, Parents)              │
│  - OAuth 2.0 / SAML (via Moodle SSO)                      │
│  - JWT tokens (short-lived, refresh tokens)               │
└────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  API Gateway                                                │
│  - Token validation                                         │
│  - Rate limiting (per user)                                │
│  - Role-based access control (RBAC)                        │
└────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  Internal Services (Agents, Reasoning Engine)              │
│  - Service-to-service authentication (API keys)            │
│  - Mutual TLS (mTLS) for sensitive operations              │
│  - Principle of least privilege                            │
└────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  Data Layer (Databases)                                     │
│  - Database-level access control                           │
│  - Encrypted connections (TLS)                             │
│  - Encrypted at rest (AES-256)                             │
└────────────────────────────────────────────────────────────┘
```

**Role Definitions**:

| Role | Permissions | Typical Users |
|------|-------------|---------------|
| **Student** | View own data, receive interventions | Students |
| **Teacher** | View student data, generate reports, override agent decisions | Teachers |
| **Parent** | View child's data (limited), receive notifications | Parents |
| **Agent** | Submit evidence, execute actions, read ontology | System agents |
| **Admin** | Full access, system configuration, knowledge base editing | System administrators |

### 10.2 Data Protection

**Sensitive Data Categories**:

1. **PII (Personally Identifiable Information)**:
   - Student name, email, phone
   - Protection: Encryption at rest + access control

2. **Learning Data**:
   - Quiz scores, activity logs, time spent
   - Protection: Access control + audit logging

3. **Psychological Data**:
   - Persona type, emotional state, cognitive load
   - Protection: Strict access control + anonymization for analytics

4. **Decision Data**:
   - Evidence packages, decision results, reasoning traces
   - Protection: Access control + retention policy (90 days)

**Data Retention Policy**:

| Data Type | Retention Period | Post-Retention Action |
|-----------|------------------|----------------------|
| **Active student data** | Duration of enrollment + 1 year | Archive to cold storage |
| **Activity logs** | 90 days | Aggregate and delete details |
| **Decision logs** | 30 days | Archive for audit |
| **Ontology snapshots** | All versions | Keep indefinitely (small size) |
| **Knowledge base history** | All versions | Keep indefinitely (version control) |

### 10.3 Audit Logging

**Audit Events**:

```python
AUDIT_EVENTS = {
    'EVIDENCE_SUBMITTED': 'Agent submitted evidence for student',
    'DECISION_MADE': 'Reasoning engine made decision',
    'ACTION_EXECUTED': 'Agent executed action in LMS',
    'REPORT_GENERATED': 'Report generated for student',
    'KNOWLEDGE_BASE_UPDATED': 'Knowledge base file modified',
    'ONTOLOGY_QUERIED': 'Ontology query executed',
    'USER_ACCESSED_STUDENT_DATA': 'User accessed student data',
    'ADMIN_OVERRIDE': 'Admin overrode agent decision'
}
```

**Audit Log Format** (JSONL):

```json
{
  "timestamp": "2025-01-29T10:30:00Z",
  "event_type": "DECISION_MADE",
  "actor": "reasoning_engine_instance_2",
  "resource": "student_12345",
  "action": "generate_directive",
  "result": "success",
  "details": {
    "decision_id": "dec_abc123",
    "directive_strength": 0.75
  },
  "ip_address": "10.0.1.15",
  "user_agent": "Python/3.10 ReasoningEngine/1.0.0"
}
```

---

## 11. Performance Architecture

### 11.1 Performance Targets

**System-Wide Performance SLAs**:

| Metric | Target | Max Acceptable | Measurement |
|--------|--------|----------------|-------------|
| **API Response Time (p95)** | <200ms | <500ms | Prometheus + Grafana |
| **Decision Latency (Rules)** | <10ms | <50ms | Internal timing |
| **Decision Latency (LLM)** | <2s | <5s | Internal timing |
| **Heartbeat Execution** | On schedule ±5min | ±15min | Scheduler logs |
| **Database Query (p95)** | <50ms | <100ms | Database monitoring |
| **Cache Hit Rate** | >80% | >60% | Redis metrics |
| **LLM API Success Rate** | >95% | >90% | Circuit breaker stats |
| **System Uptime** | >99.5% | >99.0% | External monitoring |

### 11.2 Database Optimization

**Index Strategy**:

```sql
-- Persona DB (PostgreSQL)
CREATE INDEX idx_student_id ON personas(student_id);
CREATE INDEX idx_persona_type ON personas(persona_type);
CREATE INDEX idx_similarity ON personas(persona_similarity);

-- Activity Logs (MongoDB)
db.activity_logs.createIndex({ "student_id": 1, "timestamp": -1 });
db.activity_logs.createIndex({ "agent_id": 1, "timestamp": -1 });
db.activity_logs.createIndex({ "decision_id": 1 });
```

**Query Optimization**:

- Use prepared statements to reduce parsing overhead
- Implement connection pooling (max 20 connections per replica)
- Use read replicas for read-heavy operations
- Implement query result caching for repeated queries

### 11.3 Async Processing

**Task Queue Strategy** (Celery):

```python
# High Priority Queue (real-time decisions)
@celery_app.task(queue='high_priority', time_limit=10)
def process_real_time_decision(evidence: dict) -> dict:
    """
    실시간 의사결정 처리 (높은 우선순위)
    """
    return reasoning_engine.decide(evidence['student_id'], evidence)

# Low Priority Queue (batch reports)
@celery_app.task(queue='low_priority', time_limit=300)
def generate_batch_reports(student_ids: list) -> list:
    """
    배치 리포트 생성 (낮은 우선순위)
    """
    return [generate_report(sid) for sid in student_ids]
```

**Queue Prioritization**:
- `high_priority`: Real-time decisions, urgent interventions
- `medium_priority`: Reports, analytics
- `low_priority`: Batch jobs, data exports

---

## 12. Monitoring & Observability

### 12.1 Metrics Collection

**Key Metrics** (Prometheus):

```yaml
# Application Metrics
mathking_agent_execution_total: Counter
mathking_agent_execution_duration_seconds: Histogram
mathking_decision_latency_seconds: Histogram
mathking_llm_api_calls_total: Counter
mathking_llm_api_errors_total: Counter
mathking_cache_hits_total: Counter
mathking_cache_misses_total: Counter
mathking_decision_type_total: Counter (labels: rule_based, llm_based, hybrid)

# Infrastructure Metrics
http_requests_total: Counter
http_request_duration_seconds: Histogram
database_query_duration_seconds: Histogram
rabbitmq_queue_depth: Gauge
redis_memory_used_bytes: Gauge
```

### 12.2 Distributed Tracing

**OpenTelemetry Implementation**:

```python
from opentelemetry import trace
from opentelemetry.exporter.jaeger import JaegerExporter
from opentelemetry.sdk.trace import TracerProvider
from opentelemetry.sdk.trace.export import BatchSpanProcessor

# Setup tracing
trace.set_tracer_provider(TracerProvider())
jaeger_exporter = JaegerExporter(
    agent_host_name="localhost",
    agent_port=6831,
)
trace.get_tracer_provider().add_span_processor(
    BatchSpanProcessor(jaeger_exporter)
)

tracer = trace.get_tracer(__name__)

# Instrument decision pipeline
@tracer.start_as_current_span("decide")
def decide(student_id: str, evidence: dict):
    with tracer.start_as_current_span("rule_evaluation"):
        rule_result = rule_engine.execute(evidence)

    if should_use_llm(rule_result):
        with tracer.start_as_current_span("llm_inference"):
            llm_result = llm_reasoner.reason(...)
    ...
```

**Trace Flow**:
```
decide [2150ms]
├─ rule_evaluation [15ms]
├─ llm_inference [2000ms]
│  ├─ context_preparation [100ms]
│  ├─ llm_api_call [1800ms]
│  └─ response_parsing [100ms]
├─ directive_calculation [10ms]
└─ safety_check [5ms]
```

### 12.3 Log Aggregation

**ELK Stack Configuration**:

- **Elasticsearch**: Store logs (7-day retention for hot data, 90-day for warm data)
- **Logstash**: Parse and transform logs
- **Kibana**: Visualize and search logs

**Log Levels**:

| Level | Use Case | Example |
|-------|----------|---------|
| **DEBUG** | Development only | Rule condition evaluation details |
| **INFO** | Normal operation | Agent executed, decision made |
| **WARNING** | Potential issues | LLM response confidence <0.6, cache miss |
| **ERROR** | Errors that don't stop execution | API call failed (with retry), validation error |
| **CRITICAL** | Errors requiring immediate attention | Database connection lost, all workers down |

### 12.4 Alerting Rules

**Critical Alerts** (PagerDuty):

```yaml
- alert: LLMAPIDownFor5Minutes
  expr: llm_api_errors_total / llm_api_calls_total > 0.5
  for: 5m
  severity: critical
  action: Page on-call engineer

- alert: DecisionLatencyHigh
  expr: histogram_quantile(0.95, mathking_decision_latency_seconds) > 5
  for: 10m
  severity: high
  action: Notify DevOps team

- alert: DatabaseConnectionPoolExhausted
  expr: database_connection_pool_available == 0
  for: 2m
  severity: critical
  action: Page on-call engineer + auto-scale database replicas

- alert: AgentHeartbeatMissed
  expr: time() - mathking_agent_last_execution_timestamp > 3600
  for: 15m
  severity: high
  action: Notify DevOps team + restart agent orchestrator
```

---

## 13. Cross-Cutting Concerns

### 13.1 Internationalization (i18n)

**Language Support**:
- **Primary**: Korean (ko-KR)
- **Secondary**: English (en-US)

**i18n Strategy**:

```python
from flask_babel import Babel, gettext as _

# Reports and directives use template-based i18n
directive_template = _("""
## {strength_level} 추천 활동

{student_name}님, 다음 활동을 {recommendation_verb}:
- **{activity_name}**
- 예상 소요: **{duration}분**
- 목표: {goal}
""")

# Render with locale-specific formatting
rendered = directive_template.format(
    strength_level=_("strong") if strength > 0.7 else _("suggestion"),
    student_name=student.name,
    recommendation_verb=_("immediately_start") if strength > 0.8 else _("consider"),
    activity_name=activity.name,
    duration=activity.duration_min,
    goal=activity.goal
)
```

### 13.2 Configuration Management

**Configuration Hierarchy**:

```
1. Defaults (embedded in code)
   ↓
2. Environment-specific configs (config/production.yaml)
   ↓
3. Environment variables (.env file)
   ↓
4. Runtime overrides (admin dashboard)
```

**Example Configuration File** (`config/production.yaml`):

```yaml
# Application
debug: false
log_level: INFO

# API Gateway
api:
  host: 0.0.0.0
  port: 8000
  workers: 4

# Reasoning Engine
reasoning:
  llm_provider: openai_gpt4
  llm_model: gpt-4-turbo
  decision_cache_ttl: 3600
  token_budget_daily: 100000

# Agents
agents:
  heartbeat_min_interval: 15
  heartbeat_max_interval: 1440
  max_parallel_executions: 10

# Databases
databases:
  moodle:
    host: moodle-db.internal
    port: 3306
    name: moodle
  persona:
    host: persona-db.internal
    port: 5432
    name: mathking
    pool_size: 20
    max_overflow: 10

# Cache
redis:
  host: redis.internal
  port: 6379
  db: 0
  max_connections: 50

# Message Queue
rabbitmq:
  host: rabbitmq.internal
  port: 5672
  vhost: mathking
  prefetch_count: 10
```

### 13.3 Testing Strategy

**Multi-Level Testing**:

| Test Level | Scope | Tools | Coverage Target |
|------------|-------|-------|-----------------|
| **Unit Tests** | Individual functions/classes | pytest | >80% |
| **Integration Tests** | Component interactions | pytest + docker-compose | >70% |
| **Contract Tests** | API contracts | Pact | 100% of public APIs |
| **E2E Tests** | Full user flows | Selenium + pytest | Critical paths |
| **Performance Tests** | Load and stress testing | Locust | Key scenarios |
| **Security Tests** | Vulnerability scanning | OWASP ZAP | Continuous |

**CI/CD Pipeline**:

```yaml
# .github/workflows/ci.yml (simplified)

name: CI Pipeline

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run unit tests
        run: pytest tests/unit --cov=mathking --cov-report=xml
      - name: Run integration tests
        run: pytest tests/integration
      - name: Upload coverage
        uses: codecov/codecov-action@v2

  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run linters
        run: |
          flake8 mathking/
          mypy mathking/
          black --check mathking/

  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Security scan
        run: |
          bandit -r mathking/
          safety check

  deploy:
    needs: [test, lint, security]
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to staging
        run: ./scripts/deploy.sh staging
```

---

## 14. Migration & Rollout Strategy

### 14.1 Phased Rollout Plan

**Phase 1: Pilot (2 weeks)**
- **Scope**: 50 students, 3 agents (curriculum, adaptive, emotion)
- **Goal**: Validate core decision pipeline, collect feedback
- **Criteria**: >70% student satisfaction, <5s decision latency

**Phase 2: Limited Release (4 weeks)**
- **Scope**: 500 students, 9 core agents
- **Goal**: Validate collaboration patterns, scale testing
- **Criteria**: >75% satisfaction, >90% uptime, handle 1K decisions/day

**Phase 3: Full Rollout (8 weeks)**
- **Scope**: All students (5000+), all 22 agents
- **Goal**: Full system deployment, production readiness
- **Criteria**: >80% satisfaction, >99.5% uptime, handle 50K decisions/day

**Phase 4: Optimization (Ongoing)**
- **Scope**: Continuous improvement
- **Goal**: Fine-tune LLM prompts, expand knowledge base, improve accuracy

### 14.2 Data Migration

**Migration Steps**:

1. **Persona Data Migration**:
   - Extract from Moodle user info fields
   - Transform to new persona schema
   - Load into PostgreSQL persona DB
   - Validate completeness (100% of active students)

2. **Activity Log Migration**:
   - Export last 90 days of Moodle logs
   - Transform to evidence package format
   - Load into MongoDB activity logs
   - Backfill missing data with defaults

3. **Knowledge Base Initialization**:
   - Create initial rule catalog from expert input
   - Write LLM prompts based on best practices
   - Validate rules with sample evidence
   - Deploy to production

### 14.3 Rollback Plan

**Rollback Triggers**:
- System uptime <95% for 24 hours
- Decision latency >10s (p95) for 1 hour
- Student satisfaction <60% for pilot cohort
- Critical security vulnerability discovered

**Rollback Procedure**:
1. Disable new agent system (feature flag)
2. Revert to previous version via blue-green deployment
3. Restore database snapshot if needed
4. Notify stakeholders and students
5. Post-mortem analysis

---

## 15. Operational Runbook

### 15.1 Common Operational Tasks

**Restart Agent Orchestrator**:
```bash
# Stop all agents gracefully
docker-compose exec agent_orchestrator python scripts/stop_agents.py

# Wait for all tasks to complete (max 5 minutes)
sleep 300

# Restart orchestrator
docker-compose restart agent_orchestrator

# Verify agents restarted
curl http://localhost:8000/api/v1/agents/status
```

**Clear Decision Cache**:
```bash
# Connect to Redis
redis-cli -h redis.internal -p 6379

# Flush decision cache (DB 0)
SELECT 0
FLUSHDB

# Verify cleared
DBSIZE  # Should return 0
```

**Reload Knowledge Base**:
```bash
# Pull latest knowledge base from Git
cd /opt/mathking/knowledge
git pull origin main

# Validate knowledge base
python scripts/validate_knowledge_base.py

# Reload in reasoning engine (graceful)
curl -X POST http://localhost:8000/api/v1/admin/reload-knowledge \
  -H "Authorization: Bearer ${ADMIN_TOKEN}"

# Verify reload
curl http://localhost:8000/api/v1/admin/knowledge-version
```

### 15.2 Troubleshooting Guide

**Problem: Agent not executing on schedule**

**Diagnosis**:
```bash
# Check agent status
curl http://localhost:8000/api/v1/agents/agent_curriculum/status

# Check heartbeat scheduler logs
docker-compose logs heartbeat_scheduler | grep agent_curriculum

# Check message queue
rabbitmqctl list_queues name messages
```

**Solution**:
1. Verify heartbeat schedule in ontology
2. Check if agent crashed (restart if needed)
3. Verify message queue connectivity
4. Manually trigger agent execution for testing

**Problem: High LLM API latency**

**Diagnosis**:
```bash
# Check LLM call metrics
curl http://localhost:9090/api/v1/query?query=mathking_llm_api_duration_seconds

# Check circuit breaker status
curl http://localhost:8000/api/v1/admin/circuit-breaker-status
```

**Solution**:
1. Verify OpenAI/Anthropic API status
2. Check network latency to API endpoint
3. Consider increasing timeout or using faster model
4. Enable circuit breaker to fail fast

---

## 16. Documentation & Knowledge Transfer

### 16.1 Documentation Artifacts

| Document | Audience | Purpose | Maintenance |
|----------|----------|---------|-------------|
| **Architecture Docs (this series)** | Architects, developers | System design, integration patterns | Per major release |
| **API Reference (OpenAPI)** | Frontend developers, integrators | API contracts, request/response formats | Automated from code |
| **Runbook** | DevOps, on-call engineers | Operational procedures, troubleshooting | Continuous |
| **User Guides** | Teachers, students, parents | How to use dashboards, interpret reports | Per feature release |
| **Agent Design Docs** | ML engineers, data scientists | Agent logic, evidence collection, decision criteria | Per agent |
| **Knowledge Base** | Domain experts, maintainers | Rules, prompts, action definitions | Continuous |

### 16.2 Knowledge Transfer Plan

**Onboarding New Team Members**:

1. **Week 1**: Read architecture docs (01-06), understand system overview
2. **Week 2**: Setup local dev environment, run integration tests
3. **Week 3**: Implement a small feature (e.g., new agent or rule)
4. **Week 4**: Shadow on-call engineer, participate in incident response

**Training Materials**:
- Video walkthrough of system architecture (30 minutes)
- Code walkthrough session (1 hour)
- Hands-on workshop: Create a new agent (2 hours)

---

## 17. Next Steps

After completing this Integration Architecture document, the final document should be:

**07-IMPLEMENTATION_ROADMAP.md**:
- Phase-by-phase implementation guide with detailed milestones
- Team roles and responsibilities
- Development timeline and dependencies
- Risk mitigation strategies
- Quality gates and acceptance criteria
- Deployment checklist
- Go-live plan

---

## 18. Glossary

| Term | Definition |
|------|------------|
| **API Gateway** | Entry point for all external API requests, handling authentication and routing |
| **Agent Orchestration** | Coordination layer managing agent lifecycle and heartbeat scheduling |
| **Heartbeat** | Periodic agent execution triggered by scheduler |
| **Evidence Package** | Structured data containing student metrics for decision-making |
| **Decision Pipeline** | End-to-end flow from evidence input to intervention output |
| **Task Link** | Message connecting two agents for collaboration |
| **Directive Strength** | Intervention intensity (0.0 ~ 1.0) |
| **Circuit Breaker** | Fault tolerance pattern preventing cascading failures |
| **Graceful Degradation** | System continues with reduced functionality during failures |
| **Blue-Green Deployment** | Zero-downtime deployment strategy using two identical environments |

---

**End of Document**

**Version History**:
- v1.0.0 (2025-01-29): Initial integration architecture with complete system design, deployment, scalability, security, monitoring, and operational procedures
