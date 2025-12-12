# 04-ONTOLOGY_SYSTEM_DESIGN.md

**다층 온톨로지 시스템 설계 명세서**
**Multi-Layer Ontology System Design Specification**

Version: 1.0
Last Updated: 2025-10-29
Status: Draft

---

## 문서 개요

이 문서는 Mathking 시스템의 다층 온톨로지(Multi-Layer Ontology) 시스템을 설계합니다. 온톨로지는 시스템 내 모든 개체(Entity), 관계(Relation), 규칙(Rule)을 표현하는 **단일 진실원(Single Source of Truth, SSOT)**입니다.

### 문서 목적

1. **6개 온톨로지 레이어 정의**: 각 레이어의 목적, 구조, 관계 명세
2. **JSON-LD 스키마 설계**: W3C 표준 기반 온톨로지 표현
3. **SPARQL 쿼리 패턴**: 온톨로지 조회를 위한 표준 쿼리 정의
4. **자동 생성 메커니즘**: 콘텐츠 누락 시 자동 생성 로직
5. **온톨로지 통합**: 레이어 간 상호 참조 및 일관성 유지

---

## 1. 온톨로지 시스템 개요

### 1.1 다층 온톨로지 아키텍처

```
                        ┌─────────────────────────────────┐
                        │   Mathking Ontology System      │
                        │      (ontology.jsonld)           │
                        └─────────────────────────────────┘
                                      │
                ┌─────────────────────┴─────────────────────┐
                │                                             │
    ┌───────────▼──────────┐                    ┌───────────▼──────────┐
    │ Layer 1: Entities    │                    │ Layer 2: Relations   │
    │  - Agent             │                    │  - hasTask           │
    │  - Task              │                    │  - collaboratesWith  │
    │  - Persona           │                    │  - triggeredBy       │
    │  - Content           │                    │  - hasPersona        │
    │  - Activity          │                    │  - generates         │
    └──────────────────────┘                    └──────────────────────┘
                │                                             │
    ┌───────────▼──────────────────────────────────────────┴─────────┐
    │                    6 Ontology Layers                             │
    ├──────────────────────────────────────────────────────────────────┤
    │  Layer 3.1: Agent/Task Collaboration Ontology                    │
    │  Layer 3.2: LMS Activities/Features Mapping Ontology             │
    │  Layer 3.3: Heartbeat-Based Dynamic Interaction Ontology         │
    │  Layer 3.4: Persona Correlation Ontology                         │
    │  Layer 3.5: Persona Response Scenario Ontology                   │
    │  Layer 3.6: Content System Correlation Ontology                  │
    └──────────────────────────────────────────────────────────────────┘
```

### 1.2 온톨로지 레이어 개요

```yaml
ontology_layers:

  layer_3_1_collaboration:
    name: "Agent/Task Collaboration Ontology"
    purpose: "에이전트 및 태스크 간 협력 관계 및 알고리즘 정의"
    key_concepts:
      - CollaborationPattern
      - TaskLink
      - CooperationAlgorithm

  layer_3_2_lms_mapping:
    name: "LMS Activities/Features Mapping Ontology"
    purpose: "Mathking 에이전트와 Moodle LMS 활동/기능 간 매핑"
    key_concepts:
      - LMSActivity
      - LMSFeature
      - MappingRule

  layer_3_3_heartbeat:
    name: "Heartbeat-Based Dynamic Interaction Ontology"
    purpose: "Heartbeat 주기 기반 동적 상호작용 및 추천"
    key_concepts:
      - HeartbeatSchedule
      - InteractionOpportunity
      - DynamicRecommendation

  layer_3_4_persona:
    name: "Persona Correlation Ontology"
    purpose: "학생 페르소나 유형 및 특성 상관관계"
    key_concepts:
      - PersonaType
      - PersonaTrait
      - PersonaSimilarity

  layer_3_5_scenario:
    name: "Persona Response Scenario Ontology"
    purpose: "페르소나별 Mathking 모듈 활용 시나리오"
    key_concepts:
      - ResponseScenario
      - ModuleUsage
      - OutcomeExpectation

  layer_3_6_content:
    name: "Content System Correlation Ontology"
    purpose: "콘텐츠 시스템 상관관계 및 자동 생성"
    key_concepts:
      - ContentType
      - ContentDependency
      - AutoGenerationRule
```

---

## 2. 온톨로지 파일 구조

### 2.1 `ontology.jsonld` 기본 구조

```jsonld
{
  "@context": {
    "mk": "https://mathking.kr/ontology#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#"
  },
  "@graph": [
    # Namespace 정의
    {
      "@id": "mk:",
      "@type": "owl:Ontology",
      "rdfs:label": "Mathking Ontology",
      "owl:versionInfo": "1.0.0",
      "rdfs:comment": "Mathking AI Tutor System Ontology - Multi-Layer Architecture"
    },

    # Layer 1: Core Entity Classes
    # Layer 2: Core Relations
    # Layer 3.1: Collaboration Ontology
    # Layer 3.2: LMS Mapping Ontology
    # Layer 3.3: Heartbeat Ontology
    # Layer 3.4: Persona Ontology
    # Layer 3.5: Scenario Ontology
    # Layer 3.6: Content Ontology
  ]
}
```

---

## 3. Layer 3.1: Agent/Task Collaboration Ontology

### 3.1 목적

에이전트 간, 태스크 간 협력 패턴을 온톨로지로 표현하여 Reasoning Engine이 자동으로 협업 팀을 구성할 수 있도록 합니다.

### 3.2 핵심 클래스 정의

```jsonld
{
  "@context": {...},
  "@graph": [
    {
      "@id": "mk:CollaborationPattern",
      "@type": "owl:Class",
      "rdfs:label": "Collaboration Pattern",
      "rdfs:comment": "Defines how multiple agents cooperate for student improvement"
    },
    {
      "@id": "mk:TaskLink",
      "@type": "owl:Class",
      "rdfs:label": "Task Link",
      "rdfs:comment": "Fine-grained link between specific tasks across agents"
    },
    {
      "@id": "mk:CooperationAlgorithm",
      "@type": "owl:Class",
      "rdfs:label": "Cooperation Algorithm",
      "rdfs:comment": "Algorithm defining collaboration sequence and data flow"
    }
  ]
}
```

### 3.3 관계 (Properties) 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:collaboratesWith",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Agent",
      "rdfs:range": "mk:Agent",
      "rdfs:label": "collaborates with",
      "rdfs:comment": "Defines collaboration relationship between agents"
    },
    {
      "@id": "mk:hasCollaborationPattern",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Mission",
      "rdfs:range": "mk:CollaborationPattern",
      "rdfs:label": "has collaboration pattern"
    },
    {
      "@id": "mk:participatesIn",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Agent",
      "rdfs:range": "mk:CollaborationPattern",
      "rdfs:label": "participates in"
    },
    {
      "@id": "mk:linksTo",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Task",
      "rdfs:range": "mk:Task",
      "rdfs:label": "links to",
      "rdfs:comment": "Task-to-task collaboration link"
    }
  ]
}
```

### 3.4 인스턴스 예시

```jsonld
{
  "@graph": [
    {
      "@id": "mk:CollaborationPattern/AcademicRecovery",
      "@type": "mk:CollaborationPattern",
      "rdfs:label": "Academic Performance Recovery Pattern",
      "mk:missionId": "mission_01",
      "mk:triggerEvidenceCategories": [
        "mk:EvidenceCategory/academic_performance.progress_lagging",
        "mk:EvidenceCategory/academic_performance.accuracy_declining"
      ],
      "mk:participatingAgents": [
        "mk:Agent/agent_curriculum",
        "mk:Agent/agent_adaptive",
        "mk:Agent/agent_time_management",
        "mk:Agent/agent_cognitive"
      ],
      "mk:collaborationSequence": {
        "step_1": {
          "agent": "mk:Agent/agent_curriculum",
          "task": "mk:Task/analyze_progress_gap",
          "outputTo": ["mk:Agent/agent_cognitive", "mk:Agent/agent_adaptive"]
        },
        "step_2": {
          "agent": "mk:Agent/agent_cognitive",
          "task": "mk:Task/assess_learning_efficiency",
          "inputFrom": ["mk:Agent/agent_curriculum"],
          "outputTo": ["mk:Agent/agent_adaptive"]
        }
      },
      "mk:priority": 0.85,
      "mk:expectedOutcomes": [
        "Progress rate improvement > 15%",
        "Accuracy rate recovery > 10%"
      ]
    },
    {
      "@id": "mk:TaskLink/curriculum_to_adaptive",
      "@type": "mk:TaskLink",
      "rdfs:label": "Progress Analysis to Difficulty Adjustment",
      "mk:sourceTask": "mk:Task/problem_activity_analysis",
      "mk:targetTask": "mk:Task/difficulty_adjustment",
      "mk:exchangeSchema": {
        "from_source": {
          "current_progress_rate": {
            "type": "float",
            "description": "Current progress completion rate"
          },
          "weak_topic_list": {
            "type": "array",
            "items": "string",
            "description": "List of topics where student is weak"
          },
          "error_pattern": {
            "type": "object",
            "description": "Pattern of errors student is making"
          }
        },
        "to_target": {
          "difficulty_level_recommendation": {
            "type": "string",
            "enum": ["very_easy", "easy", "medium", "hard"],
            "description": "Recommended difficulty level"
          },
          "content_focus_areas": {
            "type": "array",
            "items": "string",
            "description": "Topics to focus on"
          }
        }
      },
      "mk:activationCondition": "source_task.output.progress_rate < 0.5 AND target_task.context == 'difficulty_adjustment'"
    }
  ]
}
```

### 3.5 SPARQL 쿼리 예시

```sparql
# Query: Find all collaboration patterns triggered by evidence category
PREFIX mk: <https://mathking.kr/ontology#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?pattern ?label ?priority ?agents
WHERE {
  ?pattern a mk:CollaborationPattern .
  ?pattern rdfs:label ?label .
  ?pattern mk:priority ?priority .
  ?pattern mk:triggerEvidenceCategories ?evidence .
  ?pattern mk:participatingAgents ?agents .

  FILTER (?evidence IN (
    mk:EvidenceCategory/academic_performance.progress_lagging,
    mk:EvidenceCategory/emotional_state.anxiety_high
  ))
}
ORDER BY DESC(?priority)
```

---

## 4. Layer 3.2: LMS Activities/Features Mapping Ontology

### 4.1 목적

Mathking 에이전트의 지시문이 실제 Moodle LMS의 어떤 활동(Activity) 또는 기능(Feature)과 매핑되는지 명확히 정의합니다.

### 4.2 핵심 클래스 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:LMSActivity",
      "@type": "owl:Class",
      "rdfs:label": "LMS Activity",
      "rdfs:comment": "Moodle LMS activity types (quiz, assignment, forum, etc.)"
    },
    {
      "@id": "mk:LMSFeature",
      "@type": "owl:Class",
      "rdfs:label": "LMS Feature",
      "rdfs:comment": "Moodle LMS features (grading, messaging, calendar, etc.)"
    },
    {
      "@id": "mk:MappingRule",
      "@type": "owl:Class",
      "rdfs:label": "Mapping Rule",
      "rdfs:comment": "Rule mapping Mathking action to LMS activity/feature"
    }
  ]
}
```

### 4.3 관계 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:mapsToActivity",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Action",
      "rdfs:range": "mk:LMSActivity",
      "rdfs:label": "maps to activity"
    },
    {
      "@id": "mk:mapsToFeature",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Action",
      "rdfs:range": "mk:LMSFeature",
      "rdfs:label": "maps to feature"
    },
    {
      "@id": "mk:hasParameter",
      "@type": "owl:DatatypeProperty",
      "rdfs:domain": "mk:MappingRule",
      "rdfs:range": "xsd:string",
      "rdfs:label": "has parameter"
    }
  ]
}
```

### 4.4 인스턴스 예시

```jsonld
{
  "@graph": [
    # LMS Activity 정의
    {
      "@id": "mk:LMSActivity/quiz",
      "@type": "mk:LMSActivity",
      "rdfs:label": "Quiz Activity",
      "mk:moodleType": "mod_quiz",
      "mk:description": "Moodle quiz activity for assessments",
      "mk:supportedActions": [
        "create_quiz",
        "modify_quiz_difficulty",
        "schedule_quiz",
        "provide_hint"
      ]
    },
    {
      "@id": "mk:LMSActivity/assignment",
      "@type": "mk:LMSActivity",
      "rdfs:label": "Assignment Activity",
      "mk:moodleType": "mod_assign",
      "mk:description": "Moodle assignment activity",
      "mk:supportedActions": [
        "create_assignment",
        "extend_deadline",
        "provide_feedback"
      ]
    },

    # LMS Feature 정의
    {
      "@id": "mk:LMSFeature/grading",
      "@type": "mk:LMSFeature",
      "rdfs:label": "Grading Feature",
      "mk:moodleAPI": "gradebook_api",
      "mk:supportedActions": [
        "calculate_grade",
        "update_grade",
        "generate_grade_report"
      ]
    },
    {
      "@id": "mk:LMSFeature/messaging",
      "@type": "mk:LMSFeature",
      "rdfs:label": "Messaging Feature",
      "mk:moodleAPI": "message_api",
      "mk:supportedActions": [
        "send_message",
        "create_notification"
      ]
    },

    # Mapping Rule 정의
    {
      "@id": "mk:MappingRule/adjust_difficulty_to_quiz",
      "@type": "mk:MappingRule",
      "rdfs:label": "Adjust Difficulty → Modify Quiz",
      "mk:mathkingAction": "mk:Action/adjust_difficulty",
      "mk:mapsToActivity": "mk:LMSActivity/quiz",
      "mk:mappingLogic": {
        "condition": "action.params.content_type == 'quiz'",
        "lmsMethod": "mod_quiz_modify_questions",
        "parameterMapping": {
          "difficulty_level": "question_difficulty",
          "topic": "question_category"
        }
      }
    },
    {
      "@id": "mk:MappingRule/provide_hint_to_quiz",
      "@type": "mk:MappingRule",
      "rdfs:label": "Provide Hint → Quiz Hint Feature",
      "mk:mathkingAction": "mk:Action/provide_hint",
      "mk:mapsToActivity": "mk:LMSActivity/quiz",
      "mk:mapsToFeature": "mk:LMSFeature/hint_system",
      "mk:mappingLogic": {
        "condition": "context.current_activity == 'quiz'",
        "lmsMethod": "mod_quiz_show_hint",
        "parameterMapping": {
          "hint_content": "hint_text",
          "hint_level": "hint_strength"
        }
      }
    },
    {
      "@id": "mk:MappingRule/send_encouragement_to_message",
      "@type": "mk:MappingRule",
      "rdfs:label": "Send Encouragement → Message Feature",
      "mk:mathkingAction": "mk:Action/send_encouragement",
      "mk:mapsToFeature": "mk:LMSFeature/messaging",
      "mk:mappingLogic": {
        "lmsMethod": "message_api_send",
        "parameterMapping": {
          "message_content": "body",
          "student_id": "recipient_id"
        }
      }
    }
  ]
}
```

### 4.5 SPARQL 쿼리 예시

```sparql
# Query: Find LMS activity/feature for a given Mathking action
PREFIX mk: <https://mathking.kr/ontology#>

SELECT ?rule ?lmsActivity ?lmsFeature ?mappingLogic
WHERE {
  ?rule a mk:MappingRule .
  ?rule mk:mathkingAction mk:Action/adjust_difficulty .

  OPTIONAL { ?rule mk:mapsToActivity ?lmsActivity . }
  OPTIONAL { ?rule mk:mapsToFeature ?lmsFeature . }
  OPTIONAL { ?rule mk:mappingLogic ?mappingLogic . }
}
```

---

## 5. Layer 3.3: Heartbeat-Based Dynamic Interaction Ontology

### 5.1 목적

Heartbeat 주기(예: 30분)마다 실행되는 에이전트들이 학생 상황을 점검하고, 적절한 시점에 동적으로 상호작용 기회를 포착합니다.

### 5.2 핵심 클래스 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:HeartbeatSchedule",
      "@type": "owl:Class",
      "rdfs:label": "Heartbeat Schedule",
      "rdfs:comment": "Periodic execution schedule for agents"
    },
    {
      "@id": "mk:InteractionOpportunity",
      "@type": "owl:Class",
      "rdfs:label": "Interaction Opportunity",
      "rdfs:comment": "Detected opportunity for timely intervention"
    },
    {
      "@id": "mk:DynamicRecommendation",
      "@type": "owl:Class",
      "rdfs:label": "Dynamic Recommendation",
      "rdfs:comment": "Context-aware recommendation based on current state"
    }
  ]
}
```

### 5.3 관계 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:hasHeartbeatInterval",
      "@type": "owl:DatatypeProperty",
      "rdfs:domain": "mk:Agent",
      "rdfs:range": "xsd:integer",
      "rdfs:label": "has heartbeat interval",
      "rdfs:comment": "Interval in minutes for periodic execution"
    },
    {
      "@id": "mk:detectsOpportunity",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:Agent",
      "rdfs:range": "mk:InteractionOpportunity",
      "rdfs:label": "detects opportunity"
    },
    {
      "@id": "mk:recommends",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:InteractionOpportunity",
      "rdfs:range": "mk:DynamicRecommendation",
      "rdfs:label": "recommends"
    }
  ]
}
```

### 5.4 인스턴스 예시

```jsonld
{
  "@graph": [
    # Heartbeat Schedule 정의
    {
      "@id": "mk:HeartbeatSchedule/agent_curriculum",
      "@type": "mk:HeartbeatSchedule",
      "mk:agent": "mk:Agent/agent_curriculum",
      "mk:interval_minutes": 30,
      "mk:executionTriggers": [
        "session_start",
        "periodic_30min",
        "session_end"
      ]
    },
    {
      "@id": "mk:HeartbeatSchedule/agent_emotion",
      "@type": "mk:HeartbeatSchedule",
      "mk:agent": "mk:Agent/agent_emotion",
      "mk:interval_minutes": 20,
      "mk:executionTriggers": [
        "periodic_20min",
        "frustration_detected",
        "anxiety_spike"
      ]
    },

    # Interaction Opportunity 정의
    {
      "@id": "mk:InteractionOpportunity/break_timing",
      "@type": "mk:InteractionOpportunity",
      "rdfs:label": "Optimal Break Timing",
      "mk:detectedBy": "mk:Agent/agent_cognitive",
      "mk:detectionCriteria": {
        "cognitive_load": "> 0.8 for 15 minutes",
        "focus": "< 0.5",
        "time_since_last_break": "> 45 minutes"
      },
      "mk:optimalTiming": "immediately",
      "mk:priority": 0.90
    },
    {
      "@id": "mk:InteractionOpportunity/concept_clarification",
      "@type": "mk:InteractionOpportunity",
      "rdfs:label": "Concept Clarification Opportunity",
      "mk:detectedBy": "mk:Agent/agent_curriculum",
      "mk:detectionCriteria": {
        "repeated_errors": "> 3 on same concept",
        "response_time": "increasing trend",
        "confidence": "< 0.4"
      },
      "mk:optimalTiming": "after_current_problem",
      "mk:priority": 0.85
    },

    # Dynamic Recommendation 정의
    {
      "@id": "mk:DynamicRecommendation/take_break",
      "@type": "mk:DynamicRecommendation",
      "rdfs:label": "Take a Break",
      "mk:triggeredBy": "mk:InteractionOpportunity/break_timing",
      "mk:recommendationType": "directive",
      "mk:action": "mk:Action/insert_break",
      "mk:parameters": {
        "break_duration": 10,
        "break_activity": "stretch_or_walk",
        "message_tone": "gentle_reminder"
      },
      "mk:contextAwareness": {
        "class_ending_soon": "if < 15 min, suggest short break (5 min)",
        "exam_tomorrow": "if exam tomorrow, suggest active rest"
      }
    },
    {
      "@id": "mk:DynamicRecommendation/clarify_concept",
      "@type": "mk:DynamicRecommendation",
      "rdfs:label": "Clarify Concept",
      "mk:triggeredBy": "mk:InteractionOpportunity/concept_clarification",
      "mk:recommendationType": "suggestion",
      "mk:action": "mk:Action/provide_concept_review",
      "mk:parameters": {
        "concept": "extracted_from_errors",
        "review_type": "video_or_example",
        "estimated_time": 5
      }
    }
  ]
}
```

### 5.5 Heartbeat 실행 알고리즘

```python
# Pseudocode: Heartbeat Execution

def execute_heartbeat_cycle(current_timestamp: datetime) -> List[DynamicRecommendation]:
    """
    Heartbeat 주기 실행: 모든 에이전트 점검 및 상호작용 기회 탐지
    """
    recommendations = []

    # Step 1: Heartbeat 스케줄 조회
    due_schedules = query_due_heartbeat_schedules(current_timestamp)

    for schedule in due_schedules:
        agent = get_agent(schedule.agent_id)

        # Step 2: 에이전트 실행 (상태 점검)
        current_evidence = collect_current_evidence(agent)
        agent_state = agent.check_state(current_evidence)

        # Step 3: 상호작용 기회 탐지
        opportunities = detect_interaction_opportunities(agent, agent_state, current_evidence)

        # Step 4: 동적 추천 생성
        for opportunity in opportunities:
            if should_activate_opportunity(opportunity, current_timestamp):
                recommendation = generate_dynamic_recommendation(opportunity)
                recommendations.append(recommendation)

    # Step 5: 우선순위 정렬
    recommendations.sort(key=lambda r: r.priority, reverse=True)

    return recommendations


def detect_interaction_opportunities(agent: Agent, state: AgentState, evidence: Evidence) -> List[InteractionOpportunity]:
    """
    에이전트가 현재 상황에서 상호작용 기회를 탐지
    """
    opportunities = []

    # 온톨로지에서 이 에이전트가 탐지할 수 있는 기회 유형 조회
    opportunity_types = query_opportunity_types_for_agent(agent)

    for opp_type in opportunity_types:
        # 탐지 기준 평가
        if evaluate_detection_criteria(opp_type.detection_criteria, state, evidence):
            opportunity = InteractionOpportunity(
                type=opp_type,
                detected_by=agent,
                detected_at=datetime.now(),
                priority=calculate_opportunity_priority(opp_type, state, evidence)
            )
            opportunities.append(opportunity)

    return opportunities
```

### 5.6 SPARQL 쿼리 예시

```sparql
# Query: Find all dynamic recommendations triggered by current opportunities
PREFIX mk: <https://mathking.kr/ontology#>

SELECT ?opportunity ?recommendation ?action ?priority
WHERE {
  ?opportunity a mk:InteractionOpportunity .
  ?opportunity mk:detectedBy ?agent .
  ?opportunity mk:priority ?oppPriority .

  ?recommendation a mk:DynamicRecommendation .
  ?recommendation mk:triggeredBy ?opportunity .
  ?recommendation mk:action ?action .
  ?recommendation mk:priority ?priority .

  FILTER (?oppPriority >= 0.8)
}
ORDER BY DESC(?priority)
```

---

## 6. Layer 3.4: Persona Correlation Ontology

### 6.1 목적

학생 페르소나 유형 간의 상관관계, 유사도, 특성을 정의하여 페르소나 기반 맞춤형 개입을 지원합니다.

### 6.2 핵심 클래스 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:PersonaType",
      "@type": "owl:Class",
      "rdfs:label": "Persona Type",
      "rdfs:comment": "Student personality/learning style archetypes"
    },
    {
      "@id": "mk:PersonaTrait",
      "@type": "owl:Class",
      "rdfs:label": "Persona Trait",
      "rdfs:comment": "Specific trait or characteristic of a persona"
    },
    {
      "@id": "mk:PersonaSimilarity",
      "@type": "owl:Class",
      "rdfs:label": "Persona Similarity",
      "rdfs:comment": "Similarity measure between two persona types"
    }
  ]
}
```

### 6.3 관계 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:hasTrait",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:PersonaType",
      "rdfs:range": "mk:PersonaTrait",
      "rdfs:label": "has trait"
    },
    {
      "@id": "mk:similarTo",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:PersonaType",
      "rdfs:range": "mk:PersonaType",
      "rdfs:label": "similar to"
    },
    {
      "@id": "mk:similarityScore",
      "@type": "owl:DatatypeProperty",
      "rdfs:domain": "mk:PersonaSimilarity",
      "rdfs:range": "xsd:float",
      "rdfs:label": "similarity score",
      "rdfs:comment": "Score between 0.0 and 1.0"
    }
  ]
}
```

### 6.4 인스턴스 예시

```jsonld
{
  "@graph": [
    # Persona Type 정의
    {
      "@id": "mk:PersonaType/P_avoidant",
      "@type": "mk:PersonaType",
      "rdfs:label": "Avoidant Persona",
      "mk:description": "회피형 학습자 - 어려움 회피 경향",
      "mk:keyCharacteristics": [
        "avoids_challenges",
        "low_persistence",
        "seeks_easy_tasks"
      ],
      "mk:hasTrait": [
        "mk:PersonaTrait/low_risk_taking",
        "mk:PersonaTrait/preference_for_familiar",
        "mk:PersonaTrait/fear_of_failure"
      ]
    },
    {
      "@id": "mk:PersonaType/P_perfectionist",
      "@type": "mk:PersonaType",
      "rdfs:label": "Perfectionist Persona",
      "mk:description": "완벽주의형 학습자 - 완벽함 추구",
      "mk:keyCharacteristics": [
        "high_standards",
        "fear_of_mistakes",
        "detail_oriented"
      ],
      "mk:hasTrait": [
        "mk:PersonaTrait/high_achievement_motivation",
        "mk:PersonaTrait/self_critical",
        "mk:PersonaTrait/slow_decision_making"
      ]
    },
    {
      "@id": "mk:PersonaType/P_anxious",
      "@type": "mk:PersonaType",
      "rdfs:label": "Anxious Persona",
      "mk:description": "불안형 학습자 - 높은 불안 수준",
      "mk:keyCharacteristics": [
        "high_anxiety",
        "test_anxiety",
        "stress_sensitive"
      ],
      "mk:hasTrait": [
        "mk:PersonaTrait/emotional_reactivity",
        "mk:PersonaTrait/worry_prone",
        "mk:PersonaTrait/needs_reassurance"
      ]
    },

    # Persona Trait 정의
    {
      "@id": "mk:PersonaTrait/low_risk_taking",
      "@type": "mk:PersonaTrait",
      "rdfs:label": "Low Risk Taking",
      "mk:description": "Avoids taking risks or trying new approaches",
      "mk:impact_on_learning": "May miss learning opportunities; needs encouragement"
    },
    {
      "@id": "mk:PersonaTrait/high_achievement_motivation",
      "@type": "mk:PersonaTrait",
      "rdfs:label": "High Achievement Motivation",
      "mk:description": "Strong drive to achieve excellence",
      "mk:impact_on_learning": "Can lead to stress; needs balance guidance"
    },

    # Persona Similarity 정의
    {
      "@id": "mk:PersonaSimilarity/avoidant_anxious",
      "@type": "mk:PersonaSimilarity",
      "mk:persona1": "mk:PersonaType/P_avoidant",
      "mk:persona2": "mk:PersonaType/P_anxious",
      "mk:similarityScore": 0.65,
      "mk:commonTraits": [
        "fear_of_failure",
        "needs_reassurance"
      ],
      "mk:differenceTraits": [
        "avoidant: avoids challenges",
        "anxious: attempts challenges but with high anxiety"
      ]
    },
    {
      "@id": "mk:PersonaSimilarity/perfectionist_anxious",
      "@type": "mk:PersonaSimilarity",
      "mk:persona1": "mk:PersonaType/P_perfectionist",
      "mk:persona2": "mk:PersonaType/P_anxious",
      "mk:similarityScore": 0.72,
      "mk:commonTraits": [
        "fear_of_mistakes",
        "high_stress_levels",
        "self_critical"
      ]
    }
  ]
}
```

### 6.5 페르소나 유사도 계산 알고리즘

```python
# Pseudocode: Persona Similarity Calculation

def calculate_persona_similarity(student_profile: StudentProfile, target_persona: PersonaType) -> float:
    """
    학생 프로필과 타겟 페르소나 간 유사도 계산 (0.0 ~ 1.0)
    """
    # Step 1: 학생의 trait 추출 (설문 또는 행동 데이터 기반)
    student_traits = extract_student_traits(student_profile)

    # Step 2: 타겟 페르소나의 trait 조회
    persona_traits = query_persona_traits(target_persona)

    # Step 3: 공통 trait 비율 계산
    common_traits = set(student_traits) & set(persona_traits)
    jaccard_similarity = len(common_traits) / (len(student_traits) + len(persona_traits) - len(common_traits))

    # Step 4: 가중치 적용 (핵심 trait에 더 높은 가중치)
    weighted_similarity = 0.0
    for trait in common_traits:
        weight = get_trait_weight(trait, target_persona)
        weighted_similarity += weight

    weighted_similarity /= len(persona_traits)  # 정규화

    # Step 5: 최종 유사도 (Jaccard + 가중치 평균)
    final_similarity = (jaccard_similarity * 0.4) + (weighted_similarity * 0.6)

    return final_similarity
```

### 6.6 SPARQL 쿼리 예시

```sparql
# Query: Find similar personas with similarity score > 0.7
PREFIX mk: <https://mathking.kr/ontology#>

SELECT ?persona1 ?persona2 ?score ?commonTraits
WHERE {
  ?similarity a mk:PersonaSimilarity .
  ?similarity mk:persona1 ?persona1 .
  ?similarity mk:persona2 ?persona2 .
  ?similarity mk:similarityScore ?score .
  ?similarity mk:commonTraits ?commonTraits .

  FILTER (?score >= 0.7)
}
ORDER BY DESC(?score)
```

---

## 7. Layer 3.5: Persona Response Scenario Ontology

### 7.1 목적

각 페르소나 유형에 대해 Mathking의 어떤 모듈을 어떻게 활용할지 시나리오를 정의합니다.

### 7.2 핵심 클래스 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:ResponseScenario",
      "@type": "owl:Class",
      "rdfs:label": "Response Scenario",
      "rdfs:comment": "Predefined scenario for persona-specific response"
    },
    {
      "@id": "mk:ModuleUsage",
      "@type": "owl:Class",
      "rdfs:label": "Module Usage",
      "rdfs:comment": "How a specific Mathking module is used in a scenario"
    },
    {
      "@id": "mk:OutcomeExpectation",
      "@type": "owl:Class",
      "rdfs:label": "Outcome Expectation",
      "rdfs:comment": "Expected learning/behavioral outcome from scenario"
    }
  ]
}
```

### 7.3 관계 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:appliesTo",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:ResponseScenario",
      "rdfs:range": "mk:PersonaType",
      "rdfs:label": "applies to"
    },
    {
      "@id": "mk:usesModule",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:ResponseScenario",
      "rdfs:range": "mk:ModuleUsage",
      "rdfs:label": "uses module"
    },
    {
      "@id": "mk:expectsOutcome",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:ResponseScenario",
      "rdfs:range": "mk:OutcomeExpectation",
      "rdfs:label": "expects outcome"
    }
  ]
}
```

### 7.4 인스턴스 예시

```jsonld
{
  "@graph": [
    # Response Scenario 정의
    {
      "@id": "mk:ResponseScenario/avoidant_progress_lagging",
      "@type": "mk:ResponseScenario",
      "rdfs:label": "Avoidant Persona - Progress Lagging Response",
      "mk:appliesTo": "mk:PersonaType/P_avoidant",
      "mk:triggerCondition": "progress_delta < -0.15",
      "mk:scenarioDescription": "회피형 학생의 진도 미달 시, 부담을 낮춘 소규모 목표 제시",
      "mk:usesModule": [
        "mk:ModuleUsage/micro_mission_small_goals",
        "mk:ModuleUsage/adaptive_difficulty_down",
        "mk:ModuleUsage/motivation_encouragement"
      ],
      "mk:directiveStrength": 0.60,
      "mk:messageTone": "gentle_encouragement",
      "mk:expectsOutcome": [
        "mk:OutcomeExpectation/small_wins_achieved",
        "mk:OutcomeExpectation/confidence_boost",
        "mk:OutcomeExpectation/progress_recovery"
      ]
    },
    {
      "@id": "mk:ResponseScenario/perfectionist_exam_anxiety",
      "@type": "mk:ResponseScenario",
      "rdfs:label": "Perfectionist Persona - Exam Anxiety Response",
      "mk:appliesTo": "mk:PersonaType/P_perfectionist",
      "mk:triggerCondition": "affect == 'low' AND days_until_exam <= 7",
      "mk:scenarioDescription": "완벽주의형 학생의 시험 불안 시, 현실적 목표 재설정 및 과정 강조",
      "mk:usesModule": [
        "mk:ModuleUsage/exam_prep_realistic_goals",
        "mk:ModuleUsage/emotion_regulation_mindfulness",
        "mk:ModuleUsage/self_reflection_process_focus"
      ],
      "mk:directiveStrength": 0.75,
      "mk:messageTone": "reassuring_realistic",
      "mk:expectsOutcome": [
        "mk:OutcomeExpectation/anxiety_reduction",
        "mk:OutcomeExpectation/realistic_expectations",
        "mk:OutcomeExpectation/exam_performance_improvement"
      ]
    },

    # Module Usage 정의
    {
      "@id": "mk:ModuleUsage/micro_mission_small_goals",
      "@type": "mk:ModuleUsage",
      "rdfs:label": "Micro Mission - Small Goals",
      "mk:module": "mk:Agent/agent_micro_mission",
      "mk:specificTask": "mk:Task/set_daily_goals",
      "mk:parameters": {
        "goal_size": "small",
        "goal_count": 1,
        "difficulty": "easy",
        "time_estimate": "< 15 minutes"
      },
      "mk:rationale": "회피형 학생은 작은 성공 경험이 중요함"
    },
    {
      "@id": "mk:ModuleUsage/exam_prep_realistic_goals",
      "@type": "mk:ModuleUsage",
      "rdfs:label": "Exam Prep - Realistic Goals",
      "mk:module": "mk:Agent/agent_exam_prep",
      "mk:specificTask": "mk:Task/set_exam_goals",
      "mk:parameters": {
        "goal_type": "realistic_achievable",
        "focus": "core_concepts_only",
        "perfection_emphasis": "low"
      },
      "mk:rationale": "완벽주의형 학생에게 완벽함보다 핵심 이해 강조"
    },

    # Outcome Expectation 정의
    {
      "@id": "mk:OutcomeExpectation/small_wins_achieved",
      "@type": "mk:OutcomeExpectation",
      "rdfs:label": "Small Wins Achieved",
      "mk:description": "Student achieves small, manageable goals",
      "mk:measurement": "completion_rate of micro missions >= 80%",
      "mk:timeframe": "1 week"
    },
    {
      "@id": "mk:OutcomeExpectation/anxiety_reduction",
      "@type": "mk:OutcomeExpectation",
      "rdfs:label": "Anxiety Reduction",
      "mk:description": "Student's anxiety level decreases",
      "mk:measurement": "affect: low → med within 3 days",
      "mk:timeframe": "3 days"
    }
  ]
}
```

### 7.5 시나리오 선택 알고리즘

```python
# Pseudocode: Scenario Selection

def select_response_scenario(student: Student, evidence: Evidence) -> ResponseScenario:
    """
    학생 페르소나 및 증거에 따라 최적 시나리오 선택
    """
    # Step 1: 학생의 페르소나 유형 확인
    persona_type = get_student_persona(student)

    # Step 2: 현재 증거 카테고리 분류
    evidence_category = classify_evidence(evidence)

    # Step 3: 온톨로지에서 매칭되는 시나리오 조회
    matching_scenarios = query_scenarios_for_persona_and_evidence(persona_type, evidence_category)

    # Step 4: 트리거 조건 평가
    valid_scenarios = []
    for scenario in matching_scenarios:
        if evaluate_trigger_condition(scenario.trigger_condition, evidence):
            valid_scenarios.append(scenario)

    # Step 5: 최적 시나리오 선택 (우선순위, 과거 효과 등)
    best_scenario = select_best_scenario(valid_scenarios, student.history)

    return best_scenario
```

### 7.6 SPARQL 쿼리 예시

```sparql
# Query: Find all response scenarios for a given persona
PREFIX mk: <https://mathking.kr/ontology#>

SELECT ?scenario ?description ?modules ?outcomes
WHERE {
  ?scenario a mk:ResponseScenario .
  ?scenario mk:appliesTo mk:PersonaType/P_avoidant .
  ?scenario mk:scenarioDescription ?description .
  ?scenario mk:usesModule ?modules .
  ?scenario mk:expectsOutcome ?outcomes .
}
```

---

## 8. Layer 3.6: Content System Correlation Ontology

### 8.1 목적

콘텐츠 시스템 간 상관관계를 정의하고, 필요한 콘텐츠가 누락된 경우 자동 생성 규칙을 명시합니다.

### 8.2 핵심 클래스 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:ContentType",
      "@type": "owl:Class",
      "rdfs:label": "Content Type",
      "rdfs:comment": "Types of learning content (video, text, quiz, etc.)"
    },
    {
      "@id": "mk:ContentDependency",
      "@type": "owl:Class",
      "rdfs:label": "Content Dependency",
      "rdfs:comment": "Dependency relationship between content pieces"
    },
    {
      "@id": "mk:AutoGenerationRule",
      "@type": "owl:Class",
      "rdfs:label": "Auto-Generation Rule",
      "rdfs:comment": "Rule for automatically generating missing content"
    }
  ]
}
```

### 8.3 관계 정의

```jsonld
{
  "@graph": [
    {
      "@id": "mk:dependsOn",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:ContentType",
      "rdfs:range": "mk:ContentType",
      "rdfs:label": "depends on"
    },
    {
      "@id": "mk:generatesContent",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "mk:AutoGenerationRule",
      "rdfs:range": "mk:ContentType",
      "rdfs:label": "generates content"
    },
    {
      "@id": "mk:hasGenerationMethod",
      "@type": "owl:DatatypeProperty",
      "rdfs:domain": "mk:AutoGenerationRule",
      "rdfs:range": "xsd:string",
      "rdfs:label": "has generation method"
    }
  ]
}
```

### 8.4 인스턴스 예시

```jsonld
{
  "@graph": [
    # Content Type 정의
    {
      "@id": "mk:ContentType/video_lecture",
      "@type": "mk:ContentType",
      "rdfs:label": "Video Lecture",
      "mk:format": "mp4",
      "mk:averageDuration": "10-15 minutes",
      "mk:suitableFor": ["visual_learners", "conceptual_understanding"]
    },
    {
      "@id": "mk:ContentType/practice_problem",
      "@type": "mk:ContentType",
      "rdfs:label": "Practice Problem",
      "mk:format": "interactive",
      "mk:suitableFor": ["skill_building", "assessment"]
    },
    {
      "@id": "mk:ContentType/worked_example",
      "@type": "mk:ContentType",
      "rdfs:label": "Worked Example",
      "mk:format": "text_or_video",
      "mk:suitableFor": ["step_by_step_learning", "scaffolding"]
    },

    # Content Dependency 정의
    {
      "@id": "mk:ContentDependency/problem_needs_example",
      "@type": "mk:ContentDependency",
      "mk:dependentContent": "mk:ContentType/practice_problem",
      "mk:prerequisiteContent": "mk:ContentType/worked_example",
      "mk:dependencyReason": "Students need to see worked examples before attempting problems",
      "mk:mandatoryness": "recommended"
    },
    {
      "@id": "mk:ContentDependency/example_needs_lecture",
      "@type": "mk:ContentDependency",
      "mk:dependentContent": "mk:ContentType/worked_example",
      "mk:prerequisiteContent": "mk:ContentType/video_lecture",
      "mk:dependencyReason": "Conceptual understanding from lecture needed before examples",
      "mk:mandatoryness": "optional"
    },

    # Auto-Generation Rule 정의
    {
      "@id": "mk:AutoGenerationRule/generate_worked_example",
      "@type": "mk:AutoGenerationRule",
      "rdfs:label": "Auto-Generate Worked Example",
      "mk:triggeredWhen": "practice_problem exists but worked_example missing",
      "mk:generatesContent": "mk:ContentType/worked_example",
      "mk:hasGenerationMethod": "llm_generation_from_problem",
      "mk:generationLogic": {
        "input": {
          "problem_statement": "from practice_problem",
          "solution_steps": "from practice_problem.solution",
          "topic": "from practice_problem.topic"
        },
        "process": {
          "llm_prompt_template": "guides/llm_content_generation_prompt.md",
          "required_elements": [
            "problem_restatement",
            "step_by_step_solution",
            "explanation_for_each_step",
            "final_answer_verification"
          ]
        },
        "output": {
          "format": "markdown",
          "storage_location": "content/worked_examples/{topic}_{problem_id}.md"
        }
      },
      "mk:qualityAssurance": {
        "review_required": true,
        "reviewer_role": "domain_expert",
        "approval_before_publish": true
      }
    },
    {
      "@id": "mk:AutoGenerationRule/generate_hint",
      "@type": "mk:AutoGenerationRule",
      "rdfs:label": "Auto-Generate Hint",
      "mk:triggeredWhen": "student_stuck_on_problem for > 3 minutes",
      "mk:generatesContent": "mk:ContentType/hint",
      "mk:hasGenerationMethod": "dynamic_llm_generation",
      "mk:generationLogic": {
        "input": {
          "problem": "current_problem",
          "student_attempts": "error_history",
          "topic": "problem_topic"
        },
        "process": {
          "llm_prompt": "Create a hint that guides student to the next step without giving away the answer",
          "hint_levels": ["gentle_nudge", "more_specific", "almost_complete_guidance"]
        },
        "output": {
          "format": "text",
          "delivery": "real_time_display"
        }
      },
      "mk:qualityAssurance": {
        "review_required": false,
        "real_time_generation": true
      }
    }
  ]
}
```

### 8.5 콘텐츠 자동 생성 알고리즘

```python
# Pseudocode: Content Auto-Generation

def auto_generate_missing_content(content_request: ContentRequest) -> Content:
    """
    누락된 콘텐츠 자동 생성
    """
    # Step 1: 요청된 콘텐츠 타입 확인
    requested_content_type = content_request.content_type

    # Step 2: 온톨로지에서 자동 생성 규칙 조회
    generation_rule = query_generation_rule(requested_content_type)

    if not generation_rule:
        raise ContentNotGenerableError("No auto-generation rule found")

    # Step 3: 생성 로직 실행
    generation_logic = generation_rule.generation_logic

    # 입력 데이터 준비
    input_data = prepare_generation_input(generation_logic.input, content_request)

    # LLM 프롬프트 구성
    llm_prompt = load_prompt_template(generation_logic.process.llm_prompt_template)
    llm_prompt_filled = fill_prompt_template(llm_prompt, input_data)

    # LLM 호출
    llm_response = call_llm(llm_prompt_filled)

    # 출력 형식화
    generated_content = format_content(llm_response, generation_logic.output.format)

    # Step 4: 품질 보증
    if generation_rule.quality_assurance.review_required:
        # 검토 대기 상태로 저장
        save_for_review(generated_content, generation_rule.quality_assurance.reviewer_role)
    else:
        # 즉시 사용 가능
        save_and_publish(generated_content, generation_logic.output.storage_location)

    return generated_content


def check_content_dependencies(content: Content) -> List[ContentDependency]:
    """
    콘텐츠의 의존성 확인 및 누락 콘텐츠 탐지
    """
    dependencies = query_content_dependencies(content.content_type)

    missing_dependencies = []
    for dep in dependencies:
        if not content_exists(dep.prerequisite_content):
            missing_dependencies.append(dep)

    return missing_dependencies
```

### 8.6 SPARQL 쿼리 예시

```sparql
# Query: Find auto-generation rules for missing content type
PREFIX mk: <https://mathking.kr/ontology#>

SELECT ?rule ?method ?generationLogic ?qualityAssurance
WHERE {
  ?rule a mk:AutoGenerationRule .
  ?rule mk:generatesContent mk:ContentType/worked_example .
  ?rule mk:hasGenerationMethod ?method .
  ?rule mk:generationLogic ?generationLogic .
  ?rule mk:qualityAssurance ?qualityAssurance .
}
```

---

## 9. 온톨로지 통합 및 일관성 유지

### 9.1 레이어 간 참조

```jsonld
{
  "@graph": [
    # 예시: Collaboration Pattern이 LMS Activity를 참조
    {
      "@id": "mk:CollaborationPattern/AcademicRecovery",
      "mk:resultsInAction": "mk:Action/adjust_difficulty",
      "mk:actionMapsTo": "mk:LMSActivity/quiz"  # Layer 3.2 참조
    },

    # 예시: Response Scenario가 Heartbeat Opportunity를 참조
    {
      "@id": "mk:ResponseScenario/avoidant_progress_lagging",
      "mk:triggeredByOpportunity": "mk:InteractionOpportunity/concept_clarification"  # Layer 3.3 참조
    },

    # 예시: Persona Type이 Content Type 선호도를 가짐
    {
      "@id": "mk:PersonaType/P_visual_learner",
      "mk:prefersContentType": [
        "mk:ContentType/video_lecture",
        "mk:ContentType/diagram"
      ]  # Layer 3.6 참조
    }
  ]
}
```

### 9.2 일관성 검증 규칙

```yaml
consistency_rules:

  rule_1_agent_task_existence:
    name: "Agent and Task Existence Check"
    description: "CollaborationPattern에 참조된 모든 Agent와 Task가 실제로 존재하는지 확인"
    validation:
      - "FOR EACH agent IN collaborationPattern.participating_agents:"
      - "  ASSERT exists(ontology, agent)"
      - "FOR EACH step IN collaborationPattern.collaboration_sequence:"
      - "  ASSERT exists(ontology, step.task)"

  rule_2_lms_mapping_validity:
    name: "LMS Mapping Validity"
    description: "MappingRule의 LMS Activity/Feature가 실제 Moodle에 존재하는지 확인"
    validation:
      - "FOR EACH mapping IN ontology.mapping_rules:"
      - "  ASSERT moodle_api_check(mapping.lms_activity) == true"

  rule_3_persona_similarity_symmetry:
    name: "Persona Similarity Symmetry"
    description: "PersonaSimilarity는 대칭적이어야 함 (A→B와 B→A의 유사도 동일)"
    validation:
      - "FOR EACH similarity IN ontology.persona_similarities:"
      - "  ASSERT similarity(A, B) == similarity(B, A)"

  rule_4_content_dependency_acyclic:
    name: "Content Dependency Acyclic"
    description: "ContentDependency 그래프는 순환 참조가 없어야 함"
    validation:
      - "dependency_graph = build_graph(ontology.content_dependencies)"
      - "ASSERT is_acyclic(dependency_graph) == true"

  rule_5_heartbeat_interval_reasonable:
    name: "Heartbeat Interval Reasonable"
    description: "Heartbeat 주기는 합리적 범위 내 (5분 ~ 1440분)"
    validation:
      - "FOR EACH schedule IN ontology.heartbeat_schedules:"
      - "  ASSERT 5 <= schedule.interval_minutes <= 1440"
```

### 9.3 온톨로지 검증 스크립트

```python
# Pseudocode: Ontology Validation

def validate_ontology(ontology: Ontology) -> ValidationReport:
    """
    온톨로지 일관성 및 무결성 검증
    """
    report = ValidationReport()

    # Rule 1: Agent/Task Existence Check
    for pattern in ontology.collaboration_patterns:
        for agent_id in pattern.participating_agents:
            if not ontology.agent_exists(agent_id):
                report.add_error(f"Agent {agent_id} referenced but not defined")

        for step in pattern.collaboration_sequence:
            if not ontology.task_exists(step.task_id):
                report.add_error(f"Task {step.task_id} referenced but not defined")

    # Rule 2: LMS Mapping Validity
    for mapping in ontology.mapping_rules:
        if mapping.lms_activity:
            if not check_moodle_activity_exists(mapping.lms_activity):
                report.add_warning(f"LMS Activity {mapping.lms_activity} not found in Moodle")

    # Rule 3: Persona Similarity Symmetry
    for sim in ontology.persona_similarities:
        reverse_sim = ontology.find_similarity(sim.persona2, sim.persona1)
        if not reverse_sim:
            report.add_warning(f"Asymmetric similarity: {sim.persona1} → {sim.persona2} exists, but reverse does not")
        elif abs(sim.score - reverse_sim.score) > 0.05:
            report.add_error(f"Similarity scores not symmetric: {sim.score} != {reverse_sim.score}")

    # Rule 4: Content Dependency Acyclic
    dependency_graph = build_dependency_graph(ontology.content_dependencies)
    if has_cycle(dependency_graph):
        cycles = find_cycles(dependency_graph)
        report.add_error(f"Circular content dependencies detected: {cycles}")

    # Rule 5: Heartbeat Interval Reasonable
    for schedule in ontology.heartbeat_schedules:
        if not (5 <= schedule.interval_minutes <= 1440):
            report.add_error(f"Heartbeat interval {schedule.interval_minutes} out of range for {schedule.agent}")

    return report
```

---

## 10. 온톨로지 쿼리 인터페이스

### 10.1 Python 쿼리 API

```python
# Pseudocode: Ontology Query API

class OntologyQueryAPI:
    """
    온톨로지 쿼리를 위한 Python API
    """

    def __init__(self, ontology_file: str):
        self.graph = rdflib.Graph()
        self.graph.parse(ontology_file, format='json-ld')
        self.ns = rdflib.Namespace("https://mathking.kr/ontology#")

    def find_collaboration_patterns(self, evidence_categories: List[str]) -> List[CollaborationPattern]:
        """
        증거 카테고리에 해당하는 협업 패턴 조회
        """
        sparql_query = f"""
        PREFIX mk: <https://mathking.kr/ontology#>
        SELECT ?pattern ?label ?priority
        WHERE {{
            ?pattern a mk:CollaborationPattern .
            ?pattern rdfs:label ?label .
            ?pattern mk:priority ?priority .
            ?pattern mk:triggerEvidenceCategories ?evidence .
            FILTER (?evidence IN ({', '.join([f'mk:EvidenceCategory/{cat}' for cat in evidence_categories])}))
        }}
        ORDER BY DESC(?priority)
        """
        results = self.graph.query(sparql_query)
        return [self._parse_pattern(row) for row in results]

    def find_lms_mapping(self, action_id: str) -> Optional[MappingRule]:
        """
        Mathking 액션에 대한 LMS 매핑 조회
        """
        sparql_query = f"""
        PREFIX mk: <https://mathking.kr/ontology#>
        SELECT ?rule ?lmsActivity ?lmsFeature
        WHERE {{
            ?rule a mk:MappingRule .
            ?rule mk:mathkingAction mk:Action/{action_id} .
            OPTIONAL {{ ?rule mk:mapsToActivity ?lmsActivity . }}
            OPTIONAL {{ ?rule mk:mapsToFeature ?lmsFeature . }}
        }}
        """
        results = self.graph.query(sparql_query)
        if results:
            return self._parse_mapping(results[0])
        return None

    def find_heartbeat_opportunities(self, agent_id: str, current_state: AgentState) -> List[InteractionOpportunity]:
        """
        특정 에이전트가 탐지할 수 있는 상호작용 기회 조회
        """
        sparql_query = f"""
        PREFIX mk: <https://mathking.kr/ontology#>
        SELECT ?opportunity ?detectionCriteria ?priority
        WHERE {{
            ?opportunity a mk:InteractionOpportunity .
            ?opportunity mk:detectedBy mk:Agent/{agent_id} .
            ?opportunity mk:detectionCriteria ?detectionCriteria .
            ?opportunity mk:priority ?priority .
        }}
        """
        results = self.graph.query(sparql_query)
        opportunities = [self._parse_opportunity(row) for row in results]

        # 현재 상태에 따라 필터링
        valid_opportunities = []
        for opp in opportunities:
            if self._evaluate_detection_criteria(opp.detection_criteria, current_state):
                valid_opportunities.append(opp)

        return valid_opportunities

    def calculate_persona_similarity(self, student_profile: StudentProfile, persona_type: str) -> float:
        """
        학생 프로필과 페르소나 간 유사도 계산 (온톨로지 기반)
        """
        # PersonaSimilarity에서 사전 계산된 유사도 조회
        # 또는 Trait 기반 실시간 계산
        # (구현 생략)
        pass

    def get_response_scenario(self, persona_type: str, evidence_category: str) -> Optional[ResponseScenario]:
        """
        페르소나 및 증거 카테고리에 맞는 시나리오 조회
        """
        sparql_query = f"""
        PREFIX mk: <https://mathking.kr/ontology#>
        SELECT ?scenario ?description ?modules ?outcomes
        WHERE {{
            ?scenario a mk:ResponseScenario .
            ?scenario mk:appliesTo mk:PersonaType/{persona_type} .
            ?scenario mk:scenarioDescription ?description .
            ?scenario mk:usesModule ?modules .
            ?scenario mk:expectsOutcome ?outcomes .
        }}
        """
        results = self.graph.query(sparql_query)
        if results:
            return self._parse_scenario(results[0])
        return None

    def check_content_dependency(self, content_type: str) -> List[ContentDependency]:
        """
        콘텐츠 의존성 확인
        """
        sparql_query = f"""
        PREFIX mk: <https://mathking.kr/ontology#>
        SELECT ?dependency ?prerequisite ?mandatoryness
        WHERE {{
            ?dependency a mk:ContentDependency .
            ?dependency mk:dependentContent mk:ContentType/{content_type} .
            ?dependency mk:prerequisiteContent ?prerequisite .
            ?dependency mk:mandatoryness ?mandatoryness .
        }}
        """
        results = self.graph.query(sparql_query)
        return [self._parse_dependency(row) for row in results]

    def get_auto_generation_rule(self, content_type: str) -> Optional[AutoGenerationRule]:
        """
        콘텐츠 자동 생성 규칙 조회
        """
        sparql_query = f"""
        PREFIX mk: <https://mathking.kr/ontology#>
        SELECT ?rule ?method ?logic ?qualityAssurance
        WHERE {{
            ?rule a mk:AutoGenerationRule .
            ?rule mk:generatesContent mk:ContentType/{content_type} .
            ?rule mk:hasGenerationMethod ?method .
            ?rule mk:generationLogic ?logic .
            ?rule mk:qualityAssurance ?qualityAssurance .
        }}
        """
        results = self.graph.query(sparql_query)
        if results:
            return self._parse_generation_rule(results[0])
        return None
```

---

## 11. 온톨로지 업데이트 및 버전 관리

### 11.1 버전 관리 정책

```yaml
ontology_versioning:

  semantic_versioning:
    format: "MAJOR.MINOR.PATCH"
    rules:
      - MAJOR: "온톨로지 구조 변경 (클래스/관계 삭제 또는 변경)"
      - MINOR: "새로운 클래스/관계 추가 (기존 구조 유지)"
      - PATCH: "인스턴스 추가/수정, 설명 개선"

  current_version: "1.0.0"

  version_history:
    - version: "1.0.0"
      date: "2025-10-29"
      changes:
        - "Initial ontology design with 6 layers"
        - "Complete Agent/Task collaboration definitions"
        - "LMS mapping for all 22 agents"
        - "Persona correlation with 5 main types"

  update_process:
    step_1: "변경 사항 제안 (Issue 생성)"
    step_2: "전문가 검토 (교육팀 + AI팀)"
    step_3: "온톨로지 파일 수정 (JSON-LD)"
    step_4: "일관성 검증 (validation script 실행)"
    step_5: "버전 번호 업데이트"
    step_6: "Git commit 및 태그 생성"
    step_7: "프로덕션 배포"
```

### 11.2 온톨로지 마이그레이션

```python
# Pseudocode: Ontology Migration

def migrate_ontology(old_version: str, new_version: str) -> MigrationReport:
    """
    온톨로지 버전 마이그레이션
    """
    old_ontology = load_ontology(f"ontology_v{old_version}.jsonld")
    new_ontology = load_ontology(f"ontology_v{new_version}.jsonld")

    migration_report = MigrationReport()

    # Step 1: 변경 사항 감지
    changes = detect_changes(old_ontology, new_ontology)

    # Step 2: 데이터 마이그레이션
    for change in changes:
        if change.type == 'class_deleted':
            # 삭제된 클래스의 인스턴스 처리
            instances = find_instances(old_ontology, change.class_id)
            migration_report.add_warning(f"Class {change.class_id} deleted, {len(instances)} instances need manual migration")

        elif change.type == 'property_renamed':
            # 속성 이름 변경
            update_all_instances(change.old_name, change.new_name)
            migration_report.add_info(f"Property {change.old_name} renamed to {change.new_name}")

        elif change.type == 'class_added':
            # 새 클래스 추가 (특별한 처리 불필요)
            migration_report.add_info(f"New class {change.class_id} added")

    # Step 3: 일관성 검증
    validation_result = validate_ontology(new_ontology)
    migration_report.merge(validation_result)

    return migration_report
```

---

## 12. 다음 단계

이 문서는 **04-ONTOLOGY_SYSTEM_DESIGN.md**로서, 6개 레이어의 다층 온톨로지 시스템을 상세히 설계했습니다.

**다음 문서**:
- `05-REASONING_ENGINE_SPEC.md`: 규칙 엔진 + LLM 추론 엔진 명세
- `06-INTEGRATION_ARCHITECTURE.md`: 전체 시스템 통합 아키텍처
- `07-IMPLEMENTATION_ROADMAP.md`: 단계별 구현 로드맵

---

**문서 끝**
