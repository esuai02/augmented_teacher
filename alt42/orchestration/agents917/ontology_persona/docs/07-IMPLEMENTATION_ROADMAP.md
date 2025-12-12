# Implementation Roadmap - Phase-by-Phase Guide

**Version**: 1.0.0
**Last Updated**: 2025-01-29
**Status**: Draft

---

## Purpose

This document provides a **phase-by-phase implementation roadmap** for the Mathking AI Tutor system, translating the specifications from documents 01-06 into concrete, actionable development milestones.

The roadmap ensures:
- Systematic, risk-managed implementation
- Clear milestones and deliverables
- Quality gates at each phase
- Team coordination and accountability
- Predictable timeline and budget

---

## 1. Implementation Overview

### 1.1 Project Goals

**Primary Goals**:
1. ✅ **Deliver Personalized AI Tutoring**: 22 agents providing intelligent, context-aware interventions
2. ✅ **Ensure Decision Quality**: >70% of interventions lead to measurable learning improvements
3. ✅ **Achieve Scalability**: Support 5,000+ students with <2s decision latency
4. ✅ **Maintain Reliability**: >99.5% system uptime with graceful degradation
5. ✅ **Enable Continuous Improvement**: Human-editable knowledge base + LLM flexibility

**Success Criteria**:
- **Student Satisfaction**: >80% report interventions as helpful
- **Teacher Adoption**: >90% of teachers use reporting dashboard weekly
- **Performance**: p95 decision latency <2s, API response time <200ms
- **Reliability**: <1 critical incident per month, <4 hours downtime per quarter
- **Cost Efficiency**: LLM API costs <$500/month for 5,000 students

### 1.2 Project Scope

**In Scope**:
- 22 Mathking agents (doc 01)
- Multi-agent collaboration system (doc 02)
- LLM-optimized knowledge base (doc 03)
- 6-layer ontology system (doc 04)
- Hybrid reasoning engine (doc 05)
- Complete integration (doc 06)
- Moodle integration for action execution

**Out of Scope** (for v1.0):
- Mobile native apps (use responsive web)
- Real-time video tutoring
- Gamification features beyond basic progress tracking
- Parent portal (v2.0 feature)
- Multi-language support beyond Korean/English

### 1.3 Key Assumptions

1. **Team Size**: 8 developers, 2 QA engineers, 1 DevOps engineer, 1 data scientist, 1 project manager
2. **Timeline**: 24 weeks (6 months) from kickoff to production launch
3. **Infrastructure**: Cloud-based (AWS/Azure), Docker containerization
4. **LLM Access**: Approved API access to OpenAI GPT-4 or Anthropic Claude
5. **Data Availability**: Existing Moodle DB with 90 days of student activity logs
6. **Stakeholder Commitment**: Weekly reviews with product owner, monthly demos with teachers

---

## 2. Project Phases Overview

```
Phase 0: Foundation & Setup [2 weeks]
   ↓
Phase 1: Core Infrastructure [3 weeks]
   ↓
Phase 2: Agent System & Reasoning Engine [5 weeks]
   ↓
Phase 3: Collaboration & Knowledge Base [4 weeks]
   ↓
Phase 4: Ontology & Integration [4 weeks]
   ↓
Phase 5: Testing & Optimization [3 weeks]
   ↓
Phase 6: Deployment & Rollout [3 weeks]

Total: 24 weeks
```

**Milestone Checkpoints**:
- **Week 2**: Foundation complete, dev environment ready
- **Week 5**: Core infrastructure deployed, APIs functional
- **Week 10**: First 3 agents operational with basic reasoning
- **Week 14**: All agents integrated, collaboration patterns working
- **Week 18**: Full system integration, ontology queries functional
- **Week 21**: Testing complete, system ready for pilot
- **Week 24**: Production launch with 500 students

---

## 3. Phase-by-Phase Implementation

### Phase 0: Foundation & Setup (Weeks 1-2)

**Goal**: Establish project foundation, team onboarding, development environment setup.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P0.1 | Project kickoff meeting, team formation | PM | ☐ |
| P0.2 | Development environment setup (Docker, Git, IDEs) | DevOps | ☐ |
| P0.3 | Repository structure created | Tech Lead | ☐ |
| P0.4 | CI/CD pipeline basic setup (GitHub Actions) | DevOps | ☐ |
| P0.5 | Database schemas designed (Persona DB, Activity Logs) | Data Engineer | ☐ |
| P0.6 | Architecture review sessions (docs 01-06) | All Developers | ☐ |
| P0.7 | Initial knowledge base structure created | Data Scientist | ☐ |

**Key Activities**:
- Team onboarding: Assign architecture docs for reading
- Set up Slack/Discord channels for communication
- Create Jira/Trello board for task tracking
- Provision cloud infrastructure (AWS/Azure accounts)
- Set up monitoring infrastructure (Prometheus, Grafana)

**Quality Gates**:
- ✅ All team members have working dev environments
- ✅ Repository structure follows standards (see FOLDER_STRUCTURE.txt)
- ✅ CI pipeline successfully runs "hello world" tests
- ✅ Database schemas reviewed and approved

**Risks**:
- **Risk**: Team members unfamiliar with architecture
  - **Mitigation**: Conduct 2-hour architecture walkthrough session
- **Risk**: Cloud infrastructure provisioning delays
  - **Mitigation**: Start provisioning in Week 0, have local Docker fallback

---

### Phase 1: Core Infrastructure (Weeks 3-5)

**Goal**: Build foundational infrastructure: databases, message queue, cache, API gateway.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P1.1 | PostgreSQL Persona DB deployed with schema | Data Engineer | ☐ |
| P1.2 | MongoDB Activity Logs deployed with schema | Data Engineer | ☐ |
| P1.3 | Redis cache deployed and configured | DevOps | ☐ |
| P1.4 | RabbitMQ message queue deployed | DevOps | ☐ |
| P1.5 | API Gateway implemented (FastAPI) | Backend Dev 1 | ☐ |
| P1.6 | Authentication middleware (JWT) | Backend Dev 1 | ☐ |
| P1.7 | Rate limiting and CORS setup | Backend Dev 1 | ☐ |
| P1.8 | Database migration scripts created | Data Engineer | ☐ |
| P1.9 | Monitoring dashboards (Grafana) | DevOps | ☐ |

**Key Activities**:
- Deploy PostgreSQL with master-replica setup
- Deploy MongoDB with 3-node replica set
- Implement API authentication using JWT tokens
- Create initial API endpoints: `/health`, `/metrics`, `/agents/status`
- Set up Prometheus metrics collection
- Configure Grafana dashboards for infrastructure monitoring

**Quality Gates**:
- ✅ All databases pass connection and CRUD tests
- ✅ API Gateway passes load test (1000 RPS for 1 minute)
- ✅ Authentication middleware blocks unauthorized requests
- ✅ Redis cache achieves >80% hit rate in tests
- ✅ Monitoring dashboards show real-time metrics

**Risks**:
- **Risk**: Database performance issues
  - **Mitigation**: Early load testing, add read replicas if needed
- **Risk**: Message queue message loss
  - **Mitigation**: Enable message persistence, implement dead letter queue

**Acceptance Criteria**:
```bash
# All tests must pass
pytest tests/infrastructure/test_api_gateway.py
pytest tests/infrastructure/test_databases.py
pytest tests/infrastructure/test_cache.py

# Load test passes
locust -f tests/load/test_api_load.py --headless -u 1000 -r 100 --run-time 60s
```

---

### Phase 2: Agent System & Reasoning Engine (Weeks 6-10)

**Goal**: Implement core agent system, reasoning engine (rules + LLM), and first 3 agents.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P2.1 | Agent registry and lifecycle management | Backend Dev 2 | ☐ |
| P2.2 | Heartbeat scheduler implemented | Backend Dev 2 | ☐ |
| P2.3 | Rule DSL parser (YAML → Python objects) | Backend Dev 3 | ☐ |
| P2.4 | Condition evaluator with AST compilation | Backend Dev 3 | ☐ |
| P2.5 | LLM integration (OpenAI GPT-4) | ML Engineer | ☐ |
| P2.6 | Context builder for LLM | ML Engineer | ☐ |
| P2.7 | Directive strength calculator | Backend Dev 3 | ☐ |
| P2.8 | Safety guardrails implementation | Backend Dev 3 | ☐ |
| P2.9 | Agent 1: Curriculum Agent | Backend Dev 4 | ☐ |
| P2.10 | Agent 2: Adaptive Agent | Backend Dev 5 | ☐ |
| P2.11 | Agent 3: Exam Prep Agent | ML Engineer | ☐ |
| P2.12 | Evidence package standardization | Data Scientist | ☐ |
| P2.13 | Decision logging and audit trail | Backend Dev 2 | ☐ |

**Key Activities**:
- Implement heartbeat scheduler using Celery Beat
- Create rule catalog YAML with 10-15 initial rules
- Integrate OpenAI GPT-4 API with circuit breaker pattern
- Write LLM prompt templates for common scenarios
- Implement first 3 agents end-to-end (evidence → decision → action)
- Create evidence collector from Moodle activity logs
- Set up decision logging to MongoDB

**Quality Gates**:
- ✅ Heartbeat scheduler triggers agents on schedule (±1 minute)
- ✅ Rule engine evaluates 100 test cases correctly (100% accuracy)
- ✅ LLM integration succeeds with >95% success rate
- ✅ First 3 agents pass end-to-end integration tests
- ✅ Decision latency: <10ms (rules), <2s (LLM)
- ✅ Safety guardrails block 100% of unsafe test cases

**Risks**:
- **Risk**: LLM API rate limits or high latency
  - **Mitigation**: Implement request queueing, use caching aggressively
- **Risk**: Rule DSL too complex for domain experts
  - **Mitigation**: Provide visual rule builder UI (future), comprehensive examples

**Acceptance Criteria**:
```bash
# Unit tests pass
pytest tests/reasoning_engine/test_rule_parser.py
pytest tests/reasoning_engine/test_llm_reasoner.py
pytest tests/reasoning_engine/test_directive_calculator.py

# Integration tests pass
pytest tests/agents/test_agent_curriculum.py
pytest tests/agents/test_agent_adaptive.py
pytest tests/agents/test_agent_emotion.py

# End-to-end test
python scripts/e2e_test_decision_pipeline.py
```

**Milestone Demo** (Week 10):
- **Demo**: Show live decision-making for 3 student scenarios
- **Metrics**: Demonstrate <2s decision latency, explain reasoning
- **Feedback**: Collect stakeholder feedback on decision quality

---

### Phase 3: Collaboration & Knowledge Base (Weeks 11-14)

**Goal**: Implement multi-agent collaboration, expand to 9 agents, finalize knowledge base structure.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P3.1 | Task link system implementation | Backend Dev 2 | ☐ |
| P3.2 | Collaboration pattern matcher | Backend Dev 3 | ☐ |
| P3.3 | Agent 4-9 implementation | Team (6 devs) | ☐ |
| P3.4 | Knowledge base finalization (30+ rules) | Data Scientist | ☐ |
| P3.5 | LLM prompt library (20+ templates) | ML Engineer | ☐ |
| P3.6 | Action definitions (15+ actions) | Backend Dev 4 | ☐ |
| P3.7 | Template rendering engine (Jinja2) | Backend Dev 5 | ☐ |
| P3.8 | Report generation API | Backend Dev 1 | ☐ |
| P3.9 | Directive delivery system | Backend Dev 1 | ☐ |
| P3.10 | Collaboration unit tests | QA Engineer 1 | ☐ |

**Agents 4-9**:
- Agent 4: Exam Prep
- Agent 5: Micro Mission
- Agent 6: Self Reflection
- Agent 7: Self Directed
- Agent 8: Apprenticeship
- Agent 9: Time Reflection

**Key Activities**:
- Implement task link creation and consumption
- Define 5 collaboration patterns (Academic Recovery, Exam Preparation, etc.)
- Expand knowledge base with domain expert input
- Create Jinja2 templates for reports and directives
- Implement API endpoints for report generation
- Build collaboration testing framework

**Quality Gates**:
- ✅ Task links successfully route between agents
- ✅ Collaboration patterns trigger correctly (100% test coverage)
- ✅ All 9 agents pass integration tests
- ✅ Knowledge base passes validation (YAML syntax, rule logic)
- ✅ Report generation completes in <500ms
- ✅ Collaboration reduces decision latency by >20% (via parallel processing)

**Risks**:
- **Risk**: Collaboration complexity leads to deadlocks
  - **Mitigation**: Implement timeout mechanisms, cycle detection
- **Risk**: Knowledge base becomes inconsistent
  - **Mitigation**: Automated validation in CI pipeline, version control

**Acceptance Criteria**:
```bash
# Collaboration tests
pytest tests/collaboration/test_task_links.py
pytest tests/collaboration/test_patterns.py

# Agent tests (all 9)
pytest tests/agents/

# Knowledge base validation
python scripts/validate_knowledge_base.py

# End-to-end collaboration test
python scripts/e2e_test_collaboration.py
```

**Milestone Demo** (Week 14):
- **Demo**: Show multi-agent collaboration for "Academic Recovery" scenario
- **Metrics**: Demonstrate collaboration reduces intervention time by 30%
- **Feedback**: Teachers review generated reports for quality

---

### Phase 4: Ontology & Integration (Weeks 15-18)

**Goal**: Implement 6-layer ontology system, integrate all components, expand to all 22 agents.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P4.1 | Ontology JSON-LD schema (6 layers) | Data Scientist | ☐ |
| P4.2 | SPARQL query engine integration | Backend Dev 3 | ☐ |
| P4.3 | Ontology API endpoints | Backend Dev 1 | ☐ |
| P4.4 | Agent 10-21 implementation | Team (6 devs) | ☐ |
| P4.5 | Moodle API integration | Backend Dev 4 | ☐ |
| P4.6 | LMS activity mapping logic | Backend Dev 4 | ☐ |
| P4.7 | Persona similarity calculator | Data Scientist | ☐ |
| P4.8 | Content auto-generation rules | ML Engineer | ☐ |
| P4.9 | System integration tests | QA Engineer 2 | ☐ |
| P4.10 | Performance optimization | Backend Dev 2 | ☐ |

**Agents 10-22**:
- Agent 10-14: Emotion, Motivation, Personality, Learning Style, Cognitive
- Agent 15-21: Social, Habit, Time Management, Feedback, Goal Setting, Metacognition, Creativity
- Agent 22: Improvement (System performance monitoring and improvement suggestions)

**Key Activities**:
- Create complete ontology JSON-LD with 6 layers
- Implement SPARQL query engine (RDFLib)
- Build Ontology API with query caching
- Complete remaining 12 agents
- Integrate Moodle API for quiz, assignment, messaging
- Implement persona similarity calculation algorithm
- Define content auto-generation rules for missing dependencies

**Quality Gates**:
- ✅ Ontology passes validation (W3C standards)
- ✅ SPARQL queries return results in <100ms
- ✅ All 22 agents operational with full integration
- ✅ Moodle API integration succeeds (95% success rate)
- ✅ Persona similarity calculation accurate (validated with expert labels)
- ✅ Content auto-generation produces usable content (80% quality score)

**Risks**:
- **Risk**: Ontology complexity causes performance issues
  - **Mitigation**: Aggressive query caching, pre-computed indices
- **Risk**: Moodle API rate limits
  - **Mitigation**: Request batching, request queue with throttling

**Acceptance Criteria**:
```bash
# Ontology tests
pytest tests/ontology/test_schema.py
pytest tests/ontology/test_sparql_queries.py
pytest tests/ontology/test_api.py

# Integration tests (all 21 agents)
pytest tests/integration/test_full_system.py

# Moodle integration tests
pytest tests/integration/test_moodle_api.py

# Performance tests
python scripts/performance_test.py --agents 21 --students 1000
```

**Milestone Demo** (Week 18):
- **Demo**: Show full system with all 22 agents working together
- **Metrics**: Demonstrate system handles 1000 students with <2s latency
- **Feedback**: Conduct usability testing with 5 teachers

---

### Phase 5: Testing & Optimization (Weeks 19-21)

**Goal**: Comprehensive testing, performance tuning, bug fixes, security hardening.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P5.1 | Comprehensive test suite (>80% coverage) | QA Team | ☐ |
| P5.2 | Load testing (5000 students) | QA Engineer 2 | ☐ |
| P5.3 | Security audit and penetration testing | Security Consultant | ☐ |
| P5.4 | Performance profiling and optimization | Backend Devs | ☐ |
| P5.5 | Bug bash and issue resolution | All Developers | ☐ |
| P5.6 | Documentation finalization | Tech Writer | ☐ |
| P5.7 | User acceptance testing (UAT) | Teachers (10) | ☐ |
| P5.8 | Failover and disaster recovery testing | DevOps | ☐ |
| P5.9 | Rollback plan preparation | PM + DevOps | ☐ |

**Key Activities**:
- Run full test suite (unit, integration, E2E, performance, security)
- Conduct load test simulating 5000 concurrent students
- Perform security audit (OWASP Top 10 vulnerabilities)
- Profile system to identify bottlenecks, optimize hot paths
- Organize bug bash with entire team (2-day sprint)
- Finalize API documentation, user guides, runbook
- Conduct UAT with 10 teachers using real student data
- Test failover scenarios (database failure, LLM API down)

**Quality Gates**:
- ✅ Test coverage >80% for all components
- ✅ Load test passes: 5000 students, <2s p95 latency, >99% success rate
- ✅ Security audit: 0 critical, <5 high vulnerabilities
- ✅ UAT: >80% teachers rate system as "good" or "excellent"
- ✅ Failover test: System recovers in <5 minutes
- ✅ All P0/P1 bugs fixed, P2 bugs documented

**Risks**:
- **Risk**: Critical bugs discovered late
  - **Mitigation**: Daily bug triage, prioritize P0/P1 fixes immediately
- **Risk**: Performance issues under load
  - **Mitigation**: Early load testing, have scaling plan ready

**Acceptance Criteria**:
```bash
# Full test suite
pytest tests/ --cov=mathking --cov-report=html
# Coverage must be >80%

# Load test
locust -f tests/load/test_full_system.py --headless -u 5000 -r 500 --run-time 600s
# p95 latency <2s, success rate >99%

# Security scan
bandit -r mathking/
safety check
# 0 critical issues

# UAT results
python scripts/analyze_uat_feedback.py
# Average rating >4.0/5.0
```

**Milestone Demo** (Week 21):
- **Demo**: Full system walkthrough with live student data
- **Metrics**: Show load test results, security audit report
- **Feedback**: Final stakeholder sign-off for production deployment

---

### Phase 6: Deployment & Rollout (Weeks 22-24)

**Goal**: Production deployment, pilot launch, monitoring, and support.

**Deliverables**:

| ID | Deliverable | Owner | Status |
|----|-------------|-------|--------|
| P6.1 | Production environment setup | DevOps | ☐ |
| P6.2 | Data migration (Moodle → Persona DB) | Data Engineer | ☐ |
| P6.3 | Blue-green deployment scripts | DevOps | ☐ |
| P6.4 | Monitoring and alerting setup | DevOps | ☐ |
| P6.5 | Pilot launch (50 students) | PM + Teachers | ☐ |
| P6.6 | Daily monitoring and bug fixes | All Team | ☐ |
| P6.7 | Feedback collection and analysis | Data Scientist | ☐ |
| P6.8 | Limited release (500 students) | PM + Teachers | ☐ |
| P6.9 | Full rollout planning | PM | ☐ |
| P6.10 | Post-launch support handoff | DevOps + Support Team | ☐ |

**Rollout Timeline**:

| Week | Phase | Students | Goal |
|------|-------|----------|------|
| Week 22 | Pilot | 50 | Validate in production, collect feedback |
| Week 23 | Limited Release | 500 | Scale testing, bug fixes |
| Week 24 | Full Rollout | 5000 | Production launch |

**Key Activities**:
- Set up production infrastructure (replicas, load balancers)
- Migrate student data from Moodle to Persona DB
- Deploy system using blue-green deployment
- Configure monitoring (Prometheus, Grafana, ELK stack)
- Launch pilot with 50 students, collect feedback daily
- Address feedback, fix bugs, optimize based on real usage
- Expand to 500 students, monitor closely for 1 week
- Full rollout to all 5000 students

**Quality Gates**:
- ✅ Pilot: >70% student satisfaction, <3 critical bugs
- ✅ Limited Release: >75% satisfaction, <5 critical bugs, >99% uptime
- ✅ Full Rollout: >80% satisfaction, >99.5% uptime, <2s latency

**Risks**:
- **Risk**: Production issues not caught in testing
  - **Mitigation**: Pilot phase with small cohort, rollback plan ready
- **Risk**: Student/teacher adoption slow
  - **Mitigation**: Training sessions, user guides, onboarding support

**Acceptance Criteria**:
```bash
# Production deployment verification
python scripts/verify_production_deployment.py
# All health checks pass

# Pilot metrics
python scripts/analyze_pilot_feedback.py
# Satisfaction >70%, bugs <3 critical

# Full rollout metrics
python scripts/generate_launch_report.py
# Uptime >99.5%, latency p95 <2s, satisfaction >80%
```

**Milestone Demo** (Week 24):
- **Demo**: Production system with real students and teachers
- **Metrics**: Show pilot results, limited release metrics
- **Celebration**: Project launch celebration, lessons learned session

---

## 4. Team Structure & Responsibilities

### 4.1 Core Team

| Role | Count | Responsibilities | Key Deliverables |
|------|-------|------------------|------------------|
| **Project Manager** | 1 | Timeline, coordination, stakeholder communication | Weekly status reports, milestone demos |
| **Tech Lead / Architect** | 1 | Technical decisions, architecture review, code review | Architecture decisions, technical debt tracking |
| **Backend Developers** | 5 | API, agents, reasoning engine, integration | Core system components, APIs |
| **ML Engineer** | 1 | LLM integration, prompt engineering, data science | LLM reasoner, context builder, prompts |
| **Data Scientist** | 1 | Knowledge base, ontology, persona analysis | Knowledge base, ontology, analytics |
| **QA Engineers** | 2 | Test automation, manual testing, bug tracking | Test suites, bug reports, UAT coordination |
| **DevOps Engineer** | 1 | Infrastructure, CI/CD, monitoring, deployment | Deployment scripts, monitoring dashboards |
| **Tech Writer** | 0.5 | Documentation, user guides, API docs | User guides, API reference, runbook |

**Total**: 11.5 FTE

### 4.2 Extended Team

| Role | Involvement | Responsibilities |
|------|-------------|------------------|
| **Product Owner** | 20% (weekly reviews) | Requirements, priorities, acceptance criteria |
| **Teachers (UAT)** | 10-15 volunteers | User testing, feedback, pilot participation |
| **Security Consultant** | 1 week (Phase 5) | Security audit, penetration testing |
| **Domain Expert** | On-demand | Knowledge base validation, rule authoring |

### 4.3 RACI Matrix (Sample)

| Activity | PM | Tech Lead | Backend Dev | ML Eng | Data Sci | QA | DevOps |
|----------|----|-----------|--------------------|--------|----------|-------|--------|
| **Architecture Design** | C | A | R | R | R | I | C |
| **Agent Implementation** | I | R | A | R | C | C | I |
| **LLM Integration** | I | C | C | A | R | C | I |
| **Knowledge Base** | I | C | I | C | A | I | I |
| **Testing** | I | C | C | C | C | A | R |
| **Deployment** | R | C | I | I | I | C | A |

**Legend**: R = Responsible, A = Accountable, C = Consulted, I = Informed

---

## 5. Development Timeline & Dependencies

### 5.1 Gantt Chart (Simplified)

```
Phase 0 [Weeks 1-2]:  ████
Phase 1 [Weeks 3-5]:      ██████
Phase 2 [Weeks 6-10]:           ██████████
Phase 3 [Weeks 11-14]:                      ████████
Phase 4 [Weeks 15-18]:                              ████████
Phase 5 [Weeks 19-21]:                                      ██████
Phase 6 [Weeks 22-24]:                                            ██████

Key Milestones:
Week 2  ▼ Foundation Complete
Week 5  ▼ Infrastructure Deployed
Week 10 ▼ First 3 Agents Operational
Week 14 ▼ Collaboration Working
Week 18 ▼ Full System Integrated
Week 21 ▼ Testing Complete
Week 24 ▼ Production Launch
```

### 5.2 Critical Path

**Longest dependency chain**:

```
Foundation Setup
  → Database Deployment
    → API Gateway
      → Agent Registry
        → Heartbeat Scheduler
          → Rule Engine
            → LLM Integration
              → First 3 Agents
                → Collaboration System
                  → Ontology System
                    → Full Integration
                      → Testing
                        → Deployment
```

**Critical Path Duration**: 20 weeks (Phases 0-5)
**Buffer**: 2 weeks (Phase 6 overlaps with final testing)

### 5.3 Dependencies Between Phases

| Phase | Depends On | Blocks |
|-------|------------|--------|
| **Phase 0** | None | All subsequent phases |
| **Phase 1** | Phase 0 | Phase 2, 3, 4 |
| **Phase 2** | Phase 1 | Phase 3, 4, 5 |
| **Phase 3** | Phase 2 | Phase 4, 5 |
| **Phase 4** | Phase 3 | Phase 5, 6 |
| **Phase 5** | Phase 4 | Phase 6 |
| **Phase 6** | Phase 5 | None (project complete) |

---

## 6. Technical Dependencies & Prerequisites

### 6.1 External Dependencies

| Dependency | Purpose | Availability | Risk |
|------------|---------|--------------|------|
| **OpenAI GPT-4 API** | LLM reasoning | Subscription required | Medium (rate limits, costs) |
| **Moodle 3.7** | Student activity data | Existing system | Low (stable) |
| **Cloud Provider (AWS/Azure)** | Infrastructure | Account setup required | Low (reliable) |
| **Docker** | Containerization | Open source | Low |
| **PostgreSQL 12+** | Persona DB | Open source | Low |
| **MongoDB 4.4+** | Activity logs | Open source | Low |
| **Redis 6+** | Caching | Open source | Low |
| **RabbitMQ 3.9+** | Message queue | Open source | Low |

### 6.2 Internal Dependencies

| Dependency | Owner | Required By | Status |
|------------|-------|-------------|--------|
| **Moodle API Access** | Moodle Admin | Phase 4 (Week 15) | ☐ Pending approval |
| **Student Data Export** | Data Team | Phase 6 (Week 22) | ☐ Pending |
| **Domain Expert Availability** | Product Owner | Phase 3 (Week 11) | ☐ Scheduled |
| **Teacher UAT Volunteers** | Product Owner | Phase 5 (Week 19) | ☐ Recruiting |
| **LLM API Budget Approval** | Finance | Phase 2 (Week 6) | ☐ Pending |

### 6.3 Skill Dependencies

| Skill | Required For | Team Coverage | Gap |
|-------|--------------|---------------|-----|
| **Python 3.10+** | All backend development | 100% | None |
| **FastAPI** | API Gateway | 80% | Training needed for 1 dev |
| **LLM Prompt Engineering** | LLM integration | 20% (ML Engineer) | External consultant considered |
| **SPARQL** | Ontology queries | 10% (Data Scientist) | Learning curve planned |
| **Docker/Kubernetes** | Deployment | 100% (DevOps) | None |
| **React** | Dashboard (out of scope v1.0) | N/A | Future requirement |

---

## 7. Risk Management

### 7.1 Risk Register

| Risk ID | Risk Description | Probability | Impact | Mitigation Strategy | Owner |
|---------|------------------|-------------|--------|---------------------|-------|
| **R01** | LLM API costs exceed budget | Medium | High | Aggressive caching, rule fallback, monitor usage | ML Engineer |
| **R02** | LLM API rate limits hit | Medium | High | Request queueing, multiple API keys, cache | ML Engineer |
| **R03** | Critical bugs in production | Medium | Critical | Comprehensive testing, pilot phase, rollback plan | QA Team |
| **R04** | Performance issues under load | Medium | High | Early load testing, auto-scaling, optimization | Backend Devs |
| **R05** | Knowledge base inconsistencies | Medium | Medium | Validation automation, version control, reviews | Data Scientist |
| **R06** | Teacher/student adoption slow | High | Medium | Training sessions, user guides, onboarding support | PM |
| **R07** | Team member leaves mid-project | Low | High | Knowledge sharing, documentation, cross-training | PM |
| **R08** | Security vulnerability discovered | Low | Critical | Security audit, penetration testing, monitoring | Security Consultant |
| **R09** | Moodle integration breaks | Medium | High | Contract testing, API version monitoring | Backend Dev 4 |
| **R10** | Timeline slips due to complexity | High | Medium | Buffer time, scope management, daily standups | PM |

### 7.2 Risk Mitigation Actions

**R01-R02: LLM API Risks**
- **Action 1**: Implement decision caching with 1-hour TTL
- **Action 2**: Use rule-only fallback when LLM unavailable
- **Action 3**: Set daily token budget limits (100K tokens/day)
- **Action 4**: Monitor costs weekly, alert at 80% budget

**R03: Production Bugs**
- **Action 1**: Maintain >80% test coverage throughout project
- **Action 2**: Conduct bug bash in Phase 5
- **Action 3**: Launch pilot with only 50 students for 1 week
- **Action 4**: Have rollback plan ready (blue-green deployment)

**R06: Adoption**
- **Action 1**: Conduct 3 training sessions for teachers (Weeks 21, 23, 24)
- **Action 2**: Create video tutorials and user guides
- **Action 3**: Assign "champion teachers" for peer support
- **Action 4**: Collect feedback weekly, iterate on UX

**R10: Timeline Slips**
- **Action 1**: Daily 15-minute standup to identify blockers early
- **Action 2**: Weekly progress reviews with stakeholders
- **Action 3**: Prioritize ruthlessly (MVP first, nice-to-haves later)
- **Action 4**: Have 2-week buffer built into Phase 6

---

## 8. Quality Gates & Acceptance Criteria

### 8.1 Phase-Level Quality Gates

Each phase must pass quality gates before proceeding to next phase.

**Phase 0 Quality Gate**:
- ☐ All team members onboarded and trained
- ☐ Development environment functional for all developers
- ☐ Repository structure approved
- ☐ CI pipeline runs successfully

**Phase 1 Quality Gate**:
- ☐ All databases deployed and pass CRUD tests
- ☐ API Gateway handles 1000 RPS for 1 minute
- ☐ Monitoring dashboards show live metrics
- ☐ Authentication blocks unauthorized requests

**Phase 2 Quality Gate**:
- ☐ First 3 agents operational end-to-end
- ☐ Rule engine achieves 100% test accuracy
- ☐ LLM integration has >95% success rate
- ☐ Decision latency <10ms (rules), <2s (LLM)
- ☐ Safety guardrails block 100% of unsafe cases

**Phase 3 Quality Gate**:
- ☐ All 9 agents pass integration tests
- ☐ Collaboration patterns trigger correctly
- ☐ Knowledge base passes validation
- ☐ Report generation <500ms

**Phase 4 Quality Gate**:
- ☐ All 21 agents operational
- ☐ Ontology passes W3C validation
- ☐ SPARQL queries <100ms
- ☐ Moodle integration >95% success rate
- ☐ System handles 1000 students with <2s latency

**Phase 5 Quality Gate**:
- ☐ Test coverage >80%
- ☐ Load test passes (5000 students, <2s p95 latency)
- ☐ Security audit: 0 critical issues
- ☐ UAT: >80% teacher satisfaction
- ☐ All P0/P1 bugs fixed

**Phase 6 Quality Gate**:
- ☐ Pilot: >70% satisfaction, <3 critical bugs
- ☐ Limited Release: >75% satisfaction, >99% uptime
- ☐ Full Rollout: >80% satisfaction, >99.5% uptime

### 8.2 Go/No-Go Criteria for Production Launch

**Go Criteria** (all must be met):
- ✅ Phase 5 Quality Gate passed
- ✅ Limited release (500 students) successful (>75% satisfaction)
- ✅ System uptime >99% during limited release
- ✅ All P0/P1 bugs fixed, P2 bugs documented
- ✅ Rollback plan tested and ready
- ✅ Monitoring and alerting fully functional
- ✅ On-call rotation established
- ✅ Stakeholder sign-off received

**No-Go Criteria** (any triggers delay):
- ❌ Test coverage <80%
- ❌ Critical security vulnerabilities unresolved
- ❌ Limited release satisfaction <75%
- ❌ System uptime <99% during limited release
- ❌ Any P0 bugs open
- ❌ Load test failure (p95 latency >5s or success rate <95%)

---

## 9. Testing Strategy

### 9.1 Test Pyramid

```
                   ▲
                  ╱ ╲
                 ╱ E2E ╲           10% - Full user workflows
                ╱───────╲
               ╱ Integ- ╲          30% - Component interactions
              ╱  ration  ╲
             ╱───────────╲
            ╱    Unit     ╲        60% - Individual functions/classes
           ╱──────────────╲
          ──────────────────
```

### 9.2 Test Types & Coverage Targets

| Test Type | Tools | Coverage Target | Frequency |
|-----------|-------|-----------------|-----------|
| **Unit Tests** | pytest | >80% code coverage | Every commit (CI) |
| **Integration Tests** | pytest + docker-compose | >70% API endpoints | Every PR |
| **Contract Tests** | Pact | 100% public APIs | Every PR |
| **E2E Tests** | Selenium + pytest | Critical paths (5-10 scenarios) | Daily (nightly) |
| **Performance Tests** | Locust | Key scenarios | Weekly + before release |
| **Security Tests** | Bandit, Safety, OWASP ZAP | All code | Every PR + before release |
| **Load Tests** | Locust | Full system | Weekly + before release |

### 9.3 Test Scenarios (E2E)

**Scenario 1: Student Falls Behind → Intervention**
1. Student completes quiz with 50% accuracy (below 60% threshold)
2. Evidence collector creates evidence package
3. Heartbeat triggers Curriculum Agent
4. Agent submits evidence to Reasoning Engine
5. Reasoning Engine decides: "Adjust difficulty to easy"
6. Agent executes action in Moodle
7. Directive delivered to student: "다음 학습 난이도를 낮췄습니다"

**Expected**: Decision made in <2s, directive delivered, difficulty actually changed in Moodle

**Scenario 2: Multi-Agent Collaboration**
1. Student shows progress lagging + low affect (frustrated)
2. Evidence matches "Academic Recovery" collaboration pattern
3. Curriculum Agent creates task links to Adaptive, Emotion, Cognitive agents
4. Each agent contributes analysis
5. Collaborative intervention generated
6. Student receives comprehensive support plan

**Expected**: Collaboration triggers correctly, all agents contribute, plan delivered

**Scenario 3: LLM Fallback**
1. Student evidence is ambiguous (mixed signals)
2. Rule engine finds no clear match
3. System routes to LLM reasoner
4. LLM analyzes context and makes recommendation
5. Response validated and executed

**Expected**: LLM call succeeds in <2s, response parsed correctly, safety checks pass

### 9.4 Test Data Management

**Test Databases**:
- **Unit Tests**: In-memory SQLite, mock data
- **Integration Tests**: Docker containers with seed data
- **E2E Tests**: Dedicated test environment with anonymized real data
- **Load Tests**: Generated synthetic data (5000 students, 90 days history)

**Data Privacy**:
- Never use real student PII in test environments
- Anonymize data: replace names with "Student_001", emails with "test001@example.com"
- Mask sensitive fields (phone numbers, addresses)

---

## 10. Deployment Checklist

### 10.1 Pre-Deployment Checklist

**Infrastructure**:
- ☐ Production servers provisioned and configured
- ☐ Load balancer configured and tested
- ☐ Databases deployed with master-replica setup
- ☐ Redis cache deployed with replication
- ☐ RabbitMQ cluster deployed
- ☐ SSL certificates installed and verified
- ☐ Firewall rules configured
- ☐ Monitoring agents installed (Prometheus, etc.)

**Application**:
- ☐ All tests passing (unit, integration, E2E)
- ☐ Code review complete and approved
- ☐ Security audit passed (0 critical issues)
- ☐ Load test passed (5000 students, <2s p95 latency)
- ☐ Configuration files prepared for production
- ☐ Environment variables set (API keys, DB credentials)
- ☐ Migration scripts tested in staging

**Documentation**:
- ☐ API documentation up-to-date
- ☐ User guides finalized
- ☐ Runbook prepared
- ☐ Rollback procedure documented

**Team Readiness**:
- ☐ On-call rotation established
- ☐ Incident response plan reviewed
- ☐ Rollback plan tested
- ☐ Stakeholder communication plan ready

### 10.2 Deployment Steps (Blue-Green)

**Step 1: Prepare Green Environment**
```bash
# Deploy to green environment (inactive)
ansible-playbook deploy.yml --extra-vars "environment=production-green"

# Run smoke tests on green
python scripts/smoke_test.py --env green

# Verify all services healthy
curl https://green.mathking.kr/health
```

**Step 2: Database Migration**
```bash
# Run migrations on production DB (carefully!)
python scripts/migrate_database.py --dry-run
# Review output, then:
python scripts/migrate_database.py --apply

# Verify migration success
python scripts/verify_migration.py
```

**Step 3: Switch Traffic to Green**
```bash
# Update load balancer to route 10% traffic to green (canary)
./scripts/route_traffic.sh --green 10

# Monitor for 10 minutes
# If all good, increase to 50%
./scripts/route_traffic.sh --green 50

# Monitor for 10 minutes
# If all good, increase to 100%
./scripts/route_traffic.sh --green 100

# Mark green as active, blue as standby
./scripts/mark_active.sh green
```

**Step 4: Verify Production**
```bash
# Run production verification tests
python scripts/verify_production.py

# Check monitoring dashboards
# - Prometheus: Verify metrics flowing
# - Grafana: Check dashboards for anomalies
# - ELK: Verify logs are being collected

# Notify team and stakeholders
./scripts/send_deployment_notification.sh --status success
```

**Step 5: Monitor for 24 Hours**
```bash
# Keep close watch on:
# - Error rates
# - Response times
# - User feedback
# - System resources

# If issues arise, rollback to blue:
./scripts/rollback.sh blue
```

### 10.3 Rollback Procedure

**When to Rollback**:
- Critical bugs affecting >10% of users
- System uptime <95%
- p95 latency >5s
- Security vulnerability discovered

**Rollback Steps**:
```bash
# Step 1: Revert traffic to blue environment
./scripts/route_traffic.sh --blue 100

# Step 2: Verify blue is serving traffic
curl https://mathking.kr/health

# Step 3: Investigate issue in green (now offline)
# (Green remains available for debugging)

# Step 4: Notify team and stakeholders
./scripts/send_deployment_notification.sh --status rollback

# Step 5: Post-mortem within 24 hours
```

---

## 11. Go-Live Plan

### 11.1 Go-Live Timeline

**Week 22 (Pilot Launch)**:

| Day | Activity | Participants | Status |
|-----|----------|--------------|--------|
| **Mon** | Final pre-launch checks | DevOps, QA | ☐ |
| **Tue** | Deploy to production (green) | DevOps | ☐ |
| **Wed** | Activate pilot (50 students) | PM, Teachers (2) | ☐ |
| **Thu-Fri** | Monitor closely, collect feedback | All team | ☐ |

**Week 23 (Limited Release)**:

| Day | Activity | Participants | Status |
|-----|----------|--------------|--------|
| **Mon** | Review pilot feedback, fix bugs | All team | ☐ |
| **Tue** | Expand to 500 students | PM, Teachers (5) | ☐ |
| **Wed-Fri** | Monitor, collect feedback | All team | ☐ |

**Week 24 (Full Rollout)**:

| Day | Activity | Participants | Status |
|-----|----------|--------------|--------|
| **Mon** | Review limited release, final fixes | All team | ☐ |
| **Tue** | Training session for all teachers | PM, Teachers (all) | ☐ |
| **Wed** | Full rollout to 5000 students | PM, All Teachers | ☐ |
| **Thu** | Monitor, provide support | All team | ☐ |
| **Fri** | Project celebration, retrospective | All team | ☐ |

### 11.2 Communication Plan

**Internal Communication**:
- **Daily Standups** (15 min): Team sync on progress and blockers
- **Weekly Status Reports**: PM sends to stakeholders
- **Slack Channel**: Real-time updates and incident notifications

**Stakeholder Communication**:
- **Weekly Demos** (Week 10, 14, 18, 21): Show progress to product owner
- **Monthly Reviews**: Present to school administration
- **Go-Live Announcement**: Email to all teachers and parents (Week 24)

**User Communication**:
- **Teacher Training** (Week 21, 23, 24): 3 sessions on using dashboard
- **User Guides**: Available online before Week 22
- **In-App Onboarding**: First-time user tutorials
- **Support Channel**: Dedicated email/chat for questions

### 11.3 Success Metrics (Post-Launch)

**Week 1 (Pilot)**:
- Student satisfaction: >70%
- Teacher satisfaction: >75%
- System uptime: >99%
- Critical bugs: <3

**Week 2 (Limited Release)**:
- Student satisfaction: >75%
- Teacher satisfaction: >80%
- System uptime: >99.5%
- Critical bugs: <2

**Week 3-4 (Full Rollout)**:
- Student satisfaction: >80%
- Teacher satisfaction: >85%
- System uptime: >99.5%
- Decision latency p95: <2s
- Learning improvement (measured after 1 month): >10% increase in average scores

**3-Month Goals**:
- 90% of students receive at least 1 intervention per week
- 80% of students report interventions as helpful
- 95% of teachers use reporting dashboard weekly
- 70% of interventions lead to measurable learning improvement

---

## 12. Post-Launch Support

### 12.1 Support Model

**Tier 1: User Support**
- **Who**: Dedicated support team (2 people)
- **Channels**: Email (support@mathking.kr), in-app chat
- **Hours**: 9 AM - 6 PM KST, Monday-Friday
- **SLA**: Respond within 2 hours (business hours)

**Tier 2: Technical Support**
- **Who**: On-call engineer rotation (1 person per week)
- **Channels**: PagerDuty alerts, Slack escalation
- **Hours**: 24/7 for critical issues
- **SLA**: Acknowledge within 30 minutes (critical), 4 hours (high)

**Tier 3: Engineering Escalation**
- **Who**: Full engineering team
- **Channels**: Jira tickets, weekly triage meetings
- **Hours**: Business hours
- **SLA**: Fix critical bugs within 24 hours, high-priority within 1 week

### 12.2 Maintenance Windows

**Regular Maintenance**:
- **When**: Every Sunday 2 AM - 4 AM KST
- **Activities**: Database backups, system updates, non-breaking changes
- **Notification**: Announced 1 week in advance via email and in-app banner

**Emergency Maintenance**:
- **When**: As needed for critical bugs or security patches
- **Duration**: Target <2 hours
- **Notification**: Email and SMS to teachers immediately

### 12.3 Continuous Improvement

**Feedback Loop**:
1. Collect user feedback daily (in-app surveys, support tickets)
2. Weekly feedback review meeting (PM, designers, engineers)
3. Prioritize improvements and bugs
4. Sprint planning: 70% new features, 30% bug fixes/improvements
5. Monthly release cycle for non-critical updates

**Metrics Review**:
- **Daily**: System uptime, error rates, decision latency
- **Weekly**: User satisfaction, adoption metrics, bug counts
- **Monthly**: Learning outcomes, cost analysis, feature usage

**Knowledge Base Updates**:
- **Bi-weekly**: Domain expert reviews rule effectiveness
- **Monthly**: Update knowledge base based on data analysis
- **Quarterly**: Major knowledge base refactoring if needed

---

## 13. Lessons Learned & Best Practices

### 13.1 Architecture Decisions

**What Worked Well**:
- ✅ **Hybrid Rule + LLM approach**: Balances determinism with flexibility
- ✅ **JSON-LD ontology**: Provides clear semantic relationships
- ✅ **Heartbeat-based scheduling**: Decouples agent execution from user actions
- ✅ **Task links for collaboration**: Simple, effective multi-agent coordination

**What to Improve**:
- ⚠️ **LLM cost management**: Need better token budget enforcement
- ⚠️ **Knowledge base UX**: Consider visual rule builder for non-technical users
- ⚠️ **Caching strategy**: Could be more aggressive to reduce latency

### 13.2 Process Improvements

**Recommendations for Future Projects**:
1. **Start testing earlier**: Begin load testing in Phase 2, not Phase 5
2. **Involve users sooner**: Conduct UAT at end of Phase 3, not Phase 5
3. **Document as you go**: Don't leave documentation to the end
4. **Automate more**: More automation in testing, deployment, monitoring
5. **Buffer time**: Always add 20% buffer to estimates

---

## 14. Appendices

### Appendix A: Tech Stack Summary

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| **Backend** | Python | 3.10+ | Core application logic |
| **API Framework** | FastAPI | 0.104+ | REST API |
| **LLM Integration** | OpenAI GPT-4 | Latest | Reasoning |
| **Database (Persona)** | PostgreSQL | 12+ | Structured data |
| **Database (Logs)** | MongoDB | 4.4+ | Unstructured logs |
| **Cache** | Redis | 6+ | Caching |
| **Message Queue** | RabbitMQ | 3.9+ | Async messaging |
| **Task Queue** | Celery | 5.2+ | Background tasks |
| **Ontology** | RDFLib | 6.0+ | SPARQL queries |
| **Template Engine** | Jinja2 | 3.0+ | Report/directive rendering |
| **Monitoring** | Prometheus + Grafana | Latest | Metrics visualization |
| **Logging** | ELK Stack | 8.0+ | Log aggregation |
| **CI/CD** | GitHub Actions | N/A | Automated testing and deployment |
| **Containerization** | Docker + Docker Compose | Latest | Deployment |

### Appendix B: Budget Estimate

**One-Time Costs** (Setup):
- Cloud infrastructure setup: $5,000
- LLM API credits (initial): $1,000
- Security audit: $3,000
- **Total One-Time**: **$9,000**

**Monthly Operational Costs**:
- Cloud hosting (2 app servers, databases, cache, queue): $1,500/month
- LLM API costs (100K tokens/day @ $0.01/1K tokens): $30/month (conservative)
- Monitoring tools (Grafana Cloud, etc.): $200/month
- **Total Monthly**: **$1,730/month**

**Personnel Costs** (6 months):
- 11.5 FTE × $8,000/month × 6 months = $552,000

**Grand Total (6 months)**: **$561,380**

### Appendix C: Success Metrics Dashboard

**Real-Time Metrics** (Grafana Dashboard):
- System uptime (%)
- API response time p50, p95, p99 (ms)
- Decision latency p50, p95 (ms)
- LLM API success rate (%)
- Cache hit rate (%)
- Active students (count)
- Interventions delivered today (count)

**Weekly Metrics**:
- Student satisfaction (average rating 1-5)
- Teacher satisfaction (average rating 1-5)
- Bugs reported (count by severity)
- Test coverage (%)

**Monthly Metrics**:
- Learning improvement (% change in average scores)
- Adoption rate (% of students receiving interventions)
- Cost per student (LLM + infrastructure)
- Knowledge base updates (count)

---

**End of Document**

**Version History**:
- v1.0.0 (2025-01-29): Initial implementation roadmap with 7 phases, team structure, timeline, risks, quality gates, deployment checklist, and go-live plan

---

**🎉 All 7 Architecture Documents Complete! 🎉**

This completes the comprehensive documentation series for the Mathking AI Tutor system:
1. ✅ **01-AGENTS_TASK_SPECIFICATION.md** - 22 agents with tasks and I/O specifications
2. ✅ **02-COLLABORATION_PATTERNS.md** - Agent & task-level cooperation
3. ✅ **03-KNOWLEDGE_BASE_ARCHITECTURE.md** - LLM-optimized knowledge structure
4. ✅ **04-ONTOLOGY_SYSTEM_DESIGN.md** - 6-layer ontology system
5. ✅ **05-REASONING_ENGINE_SPEC.md** - Hybrid rule engine + LLM
6. ✅ **06-INTEGRATION_ARCHITECTURE.md** - Complete system integration
7. ✅ **07-IMPLEMENTATION_ROADMAP.md** - Phase-by-phase implementation guide

**Total Documentation**: ~250,000 tokens covering architecture, design, implementation, deployment, and operations.

**Next Steps for Implementation Team**:
1. Review all 7 documents in order
2. Conduct architecture walkthrough session (2 hours)
3. Set up development environment (Phase 0, Week 1)
4. Begin Phase 0 deliverables
5. Weekly progress reviews against this roadmap
