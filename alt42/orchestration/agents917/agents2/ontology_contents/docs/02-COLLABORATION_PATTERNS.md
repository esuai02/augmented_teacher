# 02-COLLABORATION_PATTERNS.md

**에이전트 협업 패턴 명세서**
**Agent & Task Level Cooperation Specification**

Version: 1.0
Last Updated: 2025-10-29
Status: Draft

---

## 문서 개요

이 문서는 22개의 Orchestration 에이전트와 21개의 Mathking 에이전트 간의 협업 패턴을 정의합니다. 학생 개선 목표(Student Improvement Mission)가 주어졌을 때, 에이전트 단위 및 하부 Task 단위에서의 협력 알고리즘을 명세합니다.

### 문서 목적

1. **협업 수준 정의**: Agent-level과 Task-level 협업의 차이와 용도 명확화
2. **증거 기반 트리거**: 학생 데이터 증거 카테고리에 따른 협업 자동 활성화
3. **알고리즘화**: 협업 패턴을 알고리즘으로 정의하여 Reasoning Engine이 실행 가능하도록 함
4. **Agent Links 통합**: Artifact 기반 정보 교환 시스템과의 통합 명세
5. **성공 검증**: 협업 효과성 측정을 위한 메트릭 정의

---

## 1. 협업 레벨 정의

### 1.1 Agent-Level Collaboration (에이전트 레벨 협업)

**정의**: 서로 다른 에이전트 전체가 공동의 학생 개선 목표를 위해 협력하는 패턴

**특징**:
- 에이전트 간 Evidence Package 및 Directive Package 교환
- 각 에이전트는 독립적으로 의사결정하되, 다른 에이전트의 결과를 입력으로 활용
- Ontology의 Agent Collaboration Layer에서 관계 정의
- Agent Links의 artifact → link → inbox/outbox 시스템 활용

**예시**:
```yaml
mission: "시험 불안 감소 + 성적 향상"
collaborating_agents:
  - agent_emotion: 불안 감지 및 조절 전략 제공
  - agent_exam_prep: 시험 준비 전략 및 일정 관리
  - agent_cognitive: 인지 부하 관리 및 학습 효율 최적화
  - agent_self_reflection: 학습 패턴 성찰 및 개선 계획

collaboration_flow:
  1. agent_emotion detects high anxiety → Evidence Package
  2. agent_exam_prep receives anxiety evidence → adjusts exam strategy
  3. agent_cognitive monitors cognitive load → provides break recommendations
  4. agent_self_reflection analyzes patterns → suggests long-term improvements
  5. All agents coordinate through Agent Links system
```

### 1.2 Task-Level Collaboration (태스크 레벨 협업)

**정의**: 서로 다른 에이전트 내의 특정 Task들이 세밀한 개입을 위해 직접 협력하는 패턴

**특징**:
- Task 간 직접적인 I/O 교환 (더 세밀한 granularity)
- 특정 학생 상황에 대한 맞춤형 개입 조합
- Ontology의 Task Collaboration Layer에서 관계 정의
- Agent Links의 task-to-task link 활용

**예시**:
```yaml
mission: "수학 문제 해결 시 실수 패턴 개선"
collaborating_tasks:
  - agent_curriculum.problem_activity_analysis: 실수 패턴 분석
  - agent_cognitive.working_memory_management: 작업 기억 부하 모니터링
  - agent_adaptive.difficulty_adjustment: 난이도 실시간 조정
  - agent_feedback.error_pattern_feedback: 실수 패턴에 대한 즉각 피드백

collaboration_flow:
  1. problem_activity_analysis detects repeated calculation errors
  2. working_memory_management checks cognitive load → finds overload
  3. difficulty_adjustment receives both inputs → lowers difficulty temporarily
  4. error_pattern_feedback provides targeted feedback on error type
  5. All tasks exchange fine-grained data through task-level links
```

---

## 2. 증거 카테고리 분류 (Evidence Taxonomy)

협업 패턴은 학생 데이터에서 추출된 증거 카테고리에 의해 트리거됩니다.

### 2.1 증거 카테고리 체계

```yaml
evidence_categories:

  # Category 1: Academic Performance (학업 성취도)
  academic_performance:
    subcategories:
      - progress_lagging: 진도 미달 (progress_delta < -0.15)
      - accuracy_declining: 정답률 하락 (accuracy_rate < 0.6)
      - completion_low: 완료율 저조 (completion_rate < 0.5)
      - retry_frequent: 재시도 빈번 (retry_count > 5)
    triggering_agents: [agent_curriculum, agent_adaptive, agent_time_management]

  # Category 2: Emotional State (정서 상태)
  emotional_state:
    subcategories:
      - anxiety_high: 높은 불안 (affect: low, stress indicators present)
      - frustration: 좌절감 (repeated failures + negative sentiment)
      - apathy: 무관심 (low engagement, minimal interaction)
      - overwhelm: 압도됨 (cognitive_load: high + progress_lagging)
    triggering_agents: [agent_emotion, agent_motivation, agent_self_reflection]

  # Category 3: Time Management (시간 관리)
  time_management:
    subcategories:
      - procrastination: 미루기 (deadline approaching + low completion)
      - inefficient: 비효율성 (high time_spent + low completion)
      - rushed: 급하게 진행 (high speed + low accuracy)
      - inconsistent: 불규칙성 (session frequency variance > 0.7)
    triggering_agents: [agent_time_management, agent_habit, agent_self_directed]

  # Category 4: Cognitive Load (인지 부하)
  cognitive_load:
    subcategories:
      - overload: 과부하 (load: high + response_time_avg > 120s)
      - underload: 저부하 (load: low + completion too fast)
      - fluctuating: 변동성 (load variance > 0.6)
    triggering_agents: [agent_cognitive, agent_adaptive, agent_time_reflection]

  # Category 5: Learning Strategy (학습 전략)
  learning_strategy:
    subcategories:
      - passive: 수동적 학습 (no self-initiated actions)
      - surface: 피상적 학습 (low depth, high speed)
      - scattered: 산만한 학습 (topic switching frequent)
      - rigid: 경직된 학습 (no strategy adjustment)
    triggering_agents: [agent_self_directed, agent_metacognition, agent_inquiry]

  # Category 6: Social Interaction (사회적 상호작용)
  social_interaction:
    subcategories:
      - isolated: 고립 (peer interaction = 0)
      - help_seeking_low: 도움 요청 저조 (teacher interaction < avg)
      - collaboration_poor: 협업 부족 (group activity participation low)
    triggering_agents: [agent_social, agent_apprenticeship, agent_feedback]

  # Category 7: Goal Alignment (목표 정렬)
  goal_alignment:
    subcategories:
      - misaligned: 목표 불일치 (student goal ≠ curriculum goal)
      - unclear: 목표 불명확 (goal specificity low)
      - unrealistic: 비현실적 목표 (goal difficulty mismatch)
    triggering_agents: [agent_goal_setting, agent_self_reflection, agent_exam_prep]

  # Category 8: Exam Performance (시험 성취도)
  exam_performance:
    subcategories:
      - anxiety_pattern: 시험 불안 패턴 (performance drop in exams)
      - preparation_inadequate: 준비 부족 (last-minute cramming)
      - strategy_poor: 전략 부족 (time management issues in exams)
    triggering_agents: [agent_exam_prep, agent_emotion, agent_time_management]
```

### 2.2 증거 조합 패턴 (Evidence Combination Patterns)

여러 증거 카테고리가 동시에 검출될 경우, 더 복잡한 협업 패턴이 활성화됩니다.

```yaml
combination_patterns:

  pattern_01:
    name: "Anxiety-Driven Performance Decline"
    evidence_combo:
      - emotional_state.anxiety_high
      - academic_performance.accuracy_declining
      - cognitive_load.overload
    severity: high
    priority: 0.95

  pattern_02:
    name: "Time Mismanagement with Procrastination"
    evidence_combo:
      - time_management.procrastination
      - academic_performance.completion_low
      - emotional_state.overwhelm
    severity: medium
    priority: 0.75

  pattern_03:
    name: "Passive Learning with Low Engagement"
    evidence_combo:
      - learning_strategy.passive
      - emotional_state.apathy
      - social_interaction.isolated
    severity: medium
    priority: 0.70

  pattern_04:
    name: "Exam Anxiety with Poor Preparation"
    evidence_combo:
      - exam_performance.anxiety_pattern
      - exam_performance.preparation_inadequate
      - emotional_state.anxiety_high
    severity: high
    priority: 0.90
```

---

## 3. 학생 개선 미션 카탈로그 (Student Improvement Mission Catalog)

학생 개선 미션은 증거 기반으로 활성화되며, 각 미션은 특정 에이전트 및 태스크 협업 패턴을 정의합니다.

### Mission 01: Academic Performance Recovery (학업 성취도 회복)

**트리거 증거**:
- `academic_performance.progress_lagging`
- `academic_performance.accuracy_declining`
- `academic_performance.completion_low`

**목표**: 학업 성취도를 정상 수준으로 회복시키고, 학습 효율을 높임

#### Agent-Level Collaboration

```yaml
participating_agents:
  agent_curriculum:
    role: "진도 분석 및 커리큘럼 조정"
    input_from: [agent_adaptive, agent_time_management]
    output_to: [agent_adaptive, agent_self_directed]

  agent_adaptive:
    role: "난이도 조정 및 맞춤형 콘텐츠 추천"
    input_from: [agent_curriculum, agent_cognitive]
    output_to: [agent_curriculum, agent_feedback]

  agent_time_management:
    role: "학습 시간 최적화 및 일정 재조정"
    input_from: [agent_habit, agent_self_directed]
    output_to: [agent_curriculum, agent_goal_setting]

  agent_cognitive:
    role: "인지 부하 모니터링 및 학습 효율 분석"
    input_from: [agent_adaptive, agent_time_reflection]
    output_to: [agent_adaptive, agent_time_management]

collaboration_sequence:
  step_1:
    agent: agent_curriculum
    task: analyze_progress_gap
    output: progress_gap_analysis

  step_2:
    agent: agent_cognitive
    task: assess_learning_efficiency
    input: [progress_gap_analysis]
    output: efficiency_assessment

  step_3:
    agent: agent_adaptive
    task: recommend_difficulty_adjustment
    input: [progress_gap_analysis, efficiency_assessment]
    output: adjusted_content_plan

  step_4:
    agent: agent_time_management
    task: create_recovery_schedule
    input: [adjusted_content_plan]
    output: recovery_schedule

  step_5:
    agent: agent_curriculum
    task: implement_curriculum_changes
    input: [recovery_schedule, adjusted_content_plan]
    output: directive_package
```

#### Task-Level Collaboration

```yaml
task_collaboration_pattern:

  collaboration_01:
    name: "Progress Gap Analysis → Difficulty Adjustment"
    tasks:
      source: agent_curriculum.problem_activity_analysis
      target: agent_adaptive.difficulty_adjustment
    data_exchange:
      from_source:
        - current_progress_rate: float
        - weak_topic_list: [string]
        - error_pattern: object
      to_target:
        - difficulty_level_recommendation: string
        - content_focus_areas: [string]

  collaboration_02:
    name: "Cognitive Load Monitoring → Break Insertion"
    tasks:
      source: agent_cognitive.working_memory_management
      target: agent_time_management.break_insertion
    data_exchange:
      from_source:
        - cognitive_load_level: float (0-1)
        - overload_duration: integer (seconds)
      to_target:
        - break_timing: datetime
        - break_duration: integer (minutes)

  collaboration_03:
    name: "Difficulty Adjustment → Feedback Generation"
    tasks:
      source: agent_adaptive.difficulty_adjustment
      target: agent_feedback.personalized_feedback_generation
    data_exchange:
      from_source:
        - adjusted_difficulty: string
        - reasoning: string
      to_target:
        - feedback_content: string
        - encouragement_level: string
```

---

### Mission 02: Emotional Well-being Enhancement (정서 안정성 향상)

**트리거 증거**:
- `emotional_state.anxiety_high`
- `emotional_state.frustration`
- `emotional_state.overwhelm`

**목표**: 학생의 정서적 안정을 도모하고, 긍정적 학습 태도를 형성

#### Agent-Level Collaboration

```yaml
participating_agents:
  agent_emotion:
    role: "정서 상태 감지 및 조절 전략 제공"
    input_from: [agent_cognitive, agent_self_reflection]
    output_to: [agent_adaptive, agent_motivation]

  agent_motivation:
    role: "동기 부여 및 긍정적 피드백 제공"
    input_from: [agent_emotion, agent_goal_setting]
    output_to: [agent_micro_mission, agent_feedback]

  agent_adaptive:
    role: "학습 부담 조절 및 맞춤형 지원"
    input_from: [agent_emotion, agent_curriculum]
    output_to: [agent_time_management, agent_curriculum]

  agent_cognitive:
    role: "인지 부하 완화 및 휴식 제안"
    input_from: [agent_emotion, agent_time_reflection]
    output_to: [agent_emotion, agent_time_management]

collaboration_sequence:
  step_1:
    agent: agent_emotion
    task: detect_emotional_state
    output: emotion_analysis

  step_2:
    agent: agent_cognitive
    task: assess_cognitive_load
    input: [emotion_analysis]
    output: load_assessment

  step_3:
    agent: agent_adaptive
    task: adjust_learning_intensity
    input: [emotion_analysis, load_assessment]
    output: intensity_adjustment_plan

  step_4:
    agent: agent_motivation
    task: provide_encouragement
    input: [emotion_analysis]
    output: motivational_message

  step_5:
    agent: agent_emotion
    task: recommend_regulation_strategy
    input: [intensity_adjustment_plan, motivational_message]
    output: directive_package
```

#### Task-Level Collaboration

```yaml
task_collaboration_pattern:

  collaboration_01:
    name: "Anxiety Detection → Load Reduction"
    tasks:
      source: agent_emotion.emotion_detection
      target: agent_cognitive.load_management
    data_exchange:
      from_source:
        - anxiety_level: float (0-1)
        - anxiety_triggers: [string]
      to_target:
        - load_reduction_recommendation: float (0-0.5)
        - strategy: string

  collaboration_02:
    name: "Emotional State → Motivational Message"
    tasks:
      source: agent_emotion.emotion_regulation
      target: agent_motivation.encouragement_generation
    data_exchange:
      from_source:
        - current_emotion: string
        - regulation_needs: [string]
      to_target:
        - message_tone: string
        - focus_areas: [string]
```

---

### Mission 03: Time Management Optimization (시간 관리 최적화)

**트리거 증거**:
- `time_management.procrastination`
- `time_management.inefficient`
- `time_management.inconsistent`

**목표**: 학습 시간의 효율적 활용 및 규칙적인 학습 습관 형성

#### Agent-Level Collaboration

```yaml
participating_agents:
  agent_time_management:
    role: "시간 사용 패턴 분석 및 일정 최적화"
    input_from: [agent_habit, agent_goal_setting]
    output_to: [agent_self_directed, agent_micro_mission]

  agent_habit:
    role: "학습 습관 분석 및 개선 제안"
    input_from: [agent_time_management, agent_personality]
    output_to: [agent_time_management, agent_self_directed]

  agent_self_directed:
    role: "자기주도 학습 계획 수립 지원"
    input_from: [agent_time_management, agent_goal_setting]
    output_to: [agent_micro_mission, agent_self_reflection]

  agent_micro_mission:
    role: "일일 목표 설정 및 달성 지원"
    input_from: [agent_time_management, agent_self_directed]
    output_to: [agent_motivation, agent_self_reflection]

collaboration_sequence:
  step_1:
    agent: agent_time_management
    task: analyze_time_usage_pattern
    output: time_usage_analysis

  step_2:
    agent: agent_habit
    task: identify_habit_obstacles
    input: [time_usage_analysis]
    output: habit_analysis

  step_3:
    agent: agent_self_directed
    task: create_study_plan
    input: [time_usage_analysis, habit_analysis]
    output: study_plan

  step_4:
    agent: agent_micro_mission
    task: set_daily_goals
    input: [study_plan]
    output: daily_goals

  step_5:
    agent: agent_time_management
    task: monitor_plan_adherence
    input: [daily_goals, study_plan]
    output: directive_package
```

---

### Mission 04: Self-Directed Learning Development (자기주도학습 개발)

**트리거 증거**:
- `learning_strategy.passive`
- `learning_strategy.rigid`
- `goal_alignment.unclear`

**목표**: 학생의 자기주도 학습 능력 향상 및 메타인지 발달

#### Agent-Level Collaboration

```yaml
participating_agents:
  agent_self_directed:
    role: "자기주도 학습 전략 개발"
    input_from: [agent_metacognition, agent_goal_setting]
    output_to: [agent_inquiry, agent_self_reflection]

  agent_metacognition:
    role: "메타인지 능력 평가 및 향상"
    input_from: [agent_self_reflection, agent_inquiry]
    output_to: [agent_self_directed, agent_creativity]

  agent_inquiry:
    role: "탐구 학습 유도 및 질문 생성"
    input_from: [agent_self_directed, agent_creativity]
    output_to: [agent_metacognition, agent_curriculum]

  agent_goal_setting:
    role: "구체적 학습 목표 설정 지원"
    input_from: [agent_self_directed, agent_exam_prep]
    output_to: [agent_self_directed, agent_micro_mission]

collaboration_sequence:
  step_1:
    agent: agent_metacognition
    task: assess_metacognitive_awareness
    output: metacognition_assessment

  step_2:
    agent: agent_goal_setting
    task: facilitate_goal_clarification
    input: [metacognition_assessment]
    output: clarified_goals

  step_3:
    agent: agent_self_directed
    task: develop_learning_plan
    input: [metacognition_assessment, clarified_goals]
    output: self_directed_plan

  step_4:
    agent: agent_inquiry
    task: generate_guiding_questions
    input: [self_directed_plan]
    output: guiding_questions

  step_5:
    agent: agent_metacognition
    task: monitor_learning_process
    input: [self_directed_plan, guiding_questions]
    output: directive_package
```

---

### Mission 05: Exam Preparation Excellence (시험 대비 우수성)

**트리거 증거**:
- `exam_performance.anxiety_pattern`
- `exam_performance.preparation_inadequate`
- `exam_performance.strategy_poor`

**목표**: 효과적인 시험 준비 전략 수립 및 시험 불안 관리

#### Agent-Level Collaboration

```yaml
participating_agents:
  agent_exam_prep:
    role: "시험 준비 전략 수립 및 일정 관리"
    input_from: [agent_curriculum, agent_time_management]
    output_to: [agent_emotion, agent_cognitive]

  agent_emotion:
    role: "시험 불안 감지 및 조절"
    input_from: [agent_exam_prep, agent_self_reflection]
    output_to: [agent_exam_prep, agent_motivation]

  agent_time_management:
    role: "시험 준비 일정 최적화"
    input_from: [agent_exam_prep, agent_habit]
    output_to: [agent_exam_prep, agent_micro_mission]

  agent_curriculum:
    role: "시험 범위 분석 및 핵심 주제 선정"
    input_from: [agent_exam_prep, agent_adaptive]
    output_to: [agent_exam_prep, agent_adaptive]

collaboration_sequence:
  step_1:
    agent: agent_exam_prep
    task: analyze_exam_requirements
    output: exam_analysis

  step_2:
    agent: agent_curriculum
    task: identify_key_topics
    input: [exam_analysis]
    output: key_topics

  step_3:
    agent: agent_time_management
    task: create_study_schedule
    input: [exam_analysis, key_topics]
    output: study_schedule

  step_4:
    agent: agent_emotion
    task: assess_exam_anxiety
    input: [exam_analysis]
    output: anxiety_assessment

  step_5:
    agent: agent_exam_prep
    task: integrate_preparation_plan
    input: [study_schedule, anxiety_assessment, key_topics]
    output: directive_package
```

---

## 4. 협업 알고리즘 (Collaboration Algorithms)

협업 패턴을 알고리즘으로 정의하여 Reasoning Engine이 자동으로 실행할 수 있도록 합니다.

### 4.1 Agent Collaboration Activation Algorithm

```python
# Pseudocode: Agent Collaboration Activation

def activate_agent_collaboration(evidence_packages: List[EvidencePackage]) -> CollaborationPlan:
    """
    증거 패키지 리스트를 분석하여 적절한 에이전트 협업 패턴을 활성화
    """

    # Step 1: 증거 카테고리 분류
    evidence_categories = classify_evidence_categories(evidence_packages)

    # Step 2: 증거 조합 패턴 매칭
    combination_pattern = match_combination_pattern(evidence_categories)

    # Step 3: 학생 개선 미션 선택
    mission = select_improvement_mission(combination_pattern)

    # Step 4: 협업 에이전트 선택
    participating_agents = select_collaborating_agents(mission, evidence_categories)

    # Step 5: 협업 시퀀스 생성
    collaboration_sequence = generate_collaboration_sequence(participating_agents, mission)

    # Step 6: 협업 계획 생성
    collaboration_plan = CollaborationPlan(
        mission=mission,
        agents=participating_agents,
        sequence=collaboration_sequence,
        priority=combination_pattern.priority,
        expected_outcomes=mission.expected_outcomes
    )

    return collaboration_plan


def classify_evidence_categories(evidence_packages: List[EvidencePackage]) -> Dict[str, List[EvidencePackage]]:
    """
    증거 패키지를 카테고리별로 분류
    """
    categories = {
        'academic_performance': [],
        'emotional_state': [],
        'time_management': [],
        'cognitive_load': [],
        'learning_strategy': [],
        'social_interaction': [],
        'goal_alignment': [],
        'exam_performance': []
    }

    for evidence in evidence_packages:
        # 증거 메트릭 분석
        if evidence.metrics.progress_delta < -0.15:
            categories['academic_performance'].append(evidence)

        if evidence.state.affect == 'low':
            categories['emotional_state'].append(evidence)

        if evidence.context.class_status == 'end_30min' and evidence.metrics.completion_rate < 0.5:
            categories['time_management'].append(evidence)

        if evidence.state.cognitive_load == 'high':
            categories['cognitive_load'].append(evidence)

        # ... (다른 카테고리 분류 로직)

    return categories


def match_combination_pattern(evidence_categories: Dict) -> CombinationPattern:
    """
    증거 조합 패턴 매칭
    """
    # 사전 정의된 조합 패턴과 매칭
    patterns = load_combination_patterns()

    best_match = None
    best_score = 0.0

    for pattern in patterns:
        score = calculate_pattern_match_score(pattern, evidence_categories)
        if score > best_score:
            best_score = score
            best_match = pattern

    return best_match


def select_improvement_mission(combination_pattern: CombinationPattern) -> Mission:
    """
    증거 조합 패턴에 따라 학생 개선 미션 선택
    """
    missions = load_improvement_missions()

    # 패턴에 가장 적합한 미션 선택
    for mission in missions:
        if pattern_matches_mission(combination_pattern, mission):
            return mission

    # 기본 미션 반환
    return missions['default']


def select_collaborating_agents(mission: Mission, evidence_categories: Dict) -> List[Agent]:
    """
    미션과 증거 카테고리에 따라 협업 에이전트 선택
    """
    agents = []

    # 미션에 정의된 필수 에이전트
    agents.extend(mission.required_agents)

    # 증거 카테고리에 따라 추가 에이전트 선택
    for category, evidences in evidence_categories.items():
        if evidences:  # 해당 카테고리에 증거가 있으면
            triggering_agents = get_triggering_agents_for_category(category)
            agents.extend(triggering_agents)

    # 중복 제거
    agents = list(set(agents))

    return agents


def generate_collaboration_sequence(agents: List[Agent], mission: Mission) -> List[CollaborationStep]:
    """
    에이전트 간 협업 시퀀스 생성 (토폴로지 정렬 기반)
    """
    # 에이전트 간 의존성 그래프 생성
    dependency_graph = build_dependency_graph(agents, mission)

    # 토폴로지 정렬
    sequence = topological_sort(dependency_graph)

    return sequence
```

### 4.2 Task Collaboration Activation Algorithm

```python
# Pseudocode: Task-Level Collaboration

def activate_task_collaboration(agent_collaboration_plan: CollaborationPlan, evidence_packages: List[EvidencePackage]) -> List[TaskLink]:
    """
    에이전트 협업 계획을 기반으로 태스크 레벨 협업 활성화
    """
    task_links = []

    # Step 1: 각 에이전트에서 실행할 태스크 선택
    agent_tasks = {}
    for agent in agent_collaboration_plan.agents:
        selected_tasks = select_tasks_for_agent(agent, evidence_packages)
        agent_tasks[agent.id] = selected_tasks

    # Step 2: 태스크 간 협업 필요성 분석
    for agent1, tasks1 in agent_tasks.items():
        for agent2, tasks2 in agent_tasks.items():
            if agent1 != agent2:
                # 태스크 간 협업 패턴 찾기
                for task1 in tasks1:
                    for task2 in tasks2:
                        if requires_task_collaboration(task1, task2, evidence_packages):
                            task_link = create_task_link(task1, task2, evidence_packages)
                            task_links.append(task_link)

    return task_links


def select_tasks_for_agent(agent: Agent, evidence_packages: List[EvidencePackage]) -> List[Task]:
    """
    증거에 따라 에이전트에서 실행할 태스크 선택
    """
    selected_tasks = []

    # 에이전트의 모든 태스크 평가
    for task in agent.tasks:
        # 태스크 트리거 조건 평가
        if evaluate_task_triggers(task, evidence_packages):
            selected_tasks.append(task)

    return selected_tasks


def requires_task_collaboration(task1: Task, task2: Task, evidence_packages: List[EvidencePackage]) -> bool:
    """
    두 태스크 간 협업 필요성 판단
    """
    # 온톨로지에서 태스크 협업 관계 조회
    collaboration_relations = query_ontology_task_relations(task1, task2)

    if not collaboration_relations:
        return False

    # 현재 증거 상황에서 협업이 필요한지 평가
    for relation in collaboration_relations:
        if evaluate_collaboration_condition(relation, evidence_packages):
            return True

    return False


def create_task_link(source_task: Task, target_task: Task, evidence_packages: List[EvidencePackage]) -> TaskLink:
    """
    두 태스크 간 링크 생성
    """
    # 온톨로지에서 데이터 교환 스키마 조회
    exchange_schema = query_task_exchange_schema(source_task, target_task)

    # 증거 기반 데이터 준비
    exchange_data = prepare_exchange_data(source_task, exchange_schema, evidence_packages)

    task_link = TaskLink(
        source_task_id=source_task.id,
        target_task_id=target_task.id,
        exchange_schema=exchange_schema,
        data=exchange_data,
        priority=calculate_link_priority(source_task, target_task, evidence_packages)
    )

    return task_link
```

### 4.3 Priority Calculation Algorithm

```python
# Pseudocode: Collaboration Priority Calculation

def calculate_collaboration_priority(collaboration_plan: CollaborationPlan, student_context: StudentContext) -> float:
    """
    협업 우선순위 계산 (0.0 ~ 1.0)
    """

    # 기본 우선순위 (미션 기반)
    base_priority = collaboration_plan.mission.base_priority

    # 조정 요인들
    urgency_factor = calculate_urgency_factor(collaboration_plan, student_context)
    severity_factor = calculate_severity_factor(collaboration_plan)
    persona_affinity = calculate_persona_affinity(collaboration_plan, student_context.persona)

    # 가중 평균
    priority = (
        base_priority * 0.4 +
        urgency_factor * 0.3 +
        severity_factor * 0.2 +
        persona_affinity * 0.1
    )

    return min(priority, 1.0)


def calculate_urgency_factor(collaboration_plan: CollaborationPlan, student_context: StudentContext) -> float:
    """
    긴급도 계산
    """
    urgency = 0.5  # 기본값

    # 시간 제약
    if student_context.class_status == 'end_30min':
        urgency += 0.3

    # 시험 임박
    if student_context.days_until_exam < 7:
        urgency += 0.2

    # 연속된 실패
    if student_context.consecutive_failures > 3:
        urgency += 0.2

    return min(urgency, 1.0)


def calculate_severity_factor(collaboration_plan: CollaborationPlan) -> float:
    """
    심각도 계산
    """
    severity = collaboration_plan.mission.severity_score

    # 증거 조합 패턴의 심각도 반영
    if collaboration_plan.combination_pattern:
        severity = max(severity, collaboration_plan.combination_pattern.severity)

    return severity


def calculate_persona_affinity(collaboration_plan: CollaborationPlan, persona: Persona) -> float:
    """
    페르소나 친화도 계산
    """
    affinity_scores = []

    for agent in collaboration_plan.agents:
        # 각 에이전트의 페르소나 친화도 조회
        affinity = query_agent_persona_affinity(agent, persona)
        affinity_scores.append(affinity)

    # 평균 친화도
    return sum(affinity_scores) / len(affinity_scores) if affinity_scores else 0.5
```

---

## 5. Agent Links 시스템 통합

Agent Links는 Artifact 기반 정보 교환 시스템으로, 에이전트 및 태스크 간 협업을 지원합니다.

### 5.1 Agent Links 아키텍처

```yaml
agent_links_architecture:

  # Artifact: 에이전트가 생성한 정보 객체
  artifact:
    structure:
      artifact_id: string (UUID)
      source_agent_id: string
      artifact_type: string  # evidence|directive|report|recommendation
      content: object
      metadata:
        created_at: datetime
        priority: float (0-1)
        tags: [string]

  # Link: Artifact를 특정 에이전트에게 전달하는 연결
  link:
    structure:
      link_id: string (UUID)
      artifact_id: string (FK)
      source_agent_id: string
      target_agent_id: string
      link_type: string  # agent_to_agent|task_to_task
      status: string  # pending|delivered|consumed
      created_at: datetime

  # Inbox/Outbox: 에이전트별 메시지 큐
  inbox:
    structure:
      agent_id: string (FK)
      link_id: string (FK)
      received_at: datetime
      read_status: boolean

  outbox:
    structure:
      agent_id: string (FK)
      link_id: string (FK)
      sent_at: datetime
      delivery_status: string
```

### 5.2 Collaboration Flow with Agent Links

```python
# Pseudocode: Agent Links Integration

def execute_collaboration_with_links(collaboration_plan: CollaborationPlan) -> List[DirectivePackage]:
    """
    Agent Links를 사용하여 협업 실행
    """
    directives = []

    for step in collaboration_plan.sequence:
        # Step 1: 이전 단계의 아티팩트 수집
        input_artifacts = collect_input_artifacts(step)

        # Step 2: 에이전트 실행
        agent = get_agent(step.agent_id)
        task = get_task(step.task_id)
        output_artifact = agent.execute_task(task, input_artifacts)

        # Step 3: 아티팩트 저장
        artifact_id = store_artifact(output_artifact)

        # Step 4: 다음 에이전트에게 링크 생성
        next_steps = get_next_steps(step, collaboration_plan)
        for next_step in next_steps:
            link = create_link(
                artifact_id=artifact_id,
                source_agent_id=step.agent_id,
                target_agent_id=next_step.agent_id,
                link_type='agent_to_agent'
            )
            store_link(link)

            # Inbox에 추가
            add_to_inbox(next_step.agent_id, link.link_id)

        # Step 5: 최종 지시문이면 수집
        if is_final_step(step, collaboration_plan):
            directive = create_directive_from_artifact(output_artifact)
            directives.append(directive)

    return directives


def collect_input_artifacts(step: CollaborationStep) -> List[Artifact]:
    """
    현재 단계에 필요한 입력 아티팩트 수집
    """
    artifacts = []

    # Step의 입력 의존성 확인
    for input_agent_id in step.input_from:
        # Inbox에서 해당 에이전트의 아티팩트 조회
        links = query_inbox(step.agent_id, source_agent_id=input_agent_id)

        for link in links:
            artifact = get_artifact(link.artifact_id)
            artifacts.append(artifact)

            # 읽음 처리
            mark_as_read(link.link_id)

    return artifacts
```

### 5.3 Task-Level Links

```python
# Pseudocode: Task-Level Links

def execute_task_collaboration_with_links(task_links: List[TaskLink]) -> None:
    """
    태스크 레벨 협업 실행 (Agent Links 사용)
    """
    for task_link in task_links:
        # Step 1: 소스 태스크 실행
        source_task = get_task(task_link.source_task_id)
        source_agent = get_agent_for_task(source_task)

        # Step 2: 태스크 실행 (입력 데이터 포함)
        task_output = source_agent.execute_task(source_task, task_link.data)

        # Step 3: 출력 데이터 추출 (exchange_schema에 따라)
        exchange_data = extract_exchange_data(task_output, task_link.exchange_schema)

        # Step 4: 아티팩트 생성 (task-level artifact)
        artifact = create_task_artifact(
            source_task_id=task_link.source_task_id,
            content=exchange_data,
            artifact_type='task_output'
        )
        artifact_id = store_artifact(artifact)

        # Step 5: 타겟 태스크에게 링크 생성
        link = create_link(
            artifact_id=artifact_id,
            source_task_id=task_link.source_task_id,
            target_task_id=task_link.target_task_id,
            link_type='task_to_task'
        )
        store_link(link)

        # Step 6: 타겟 에이전트의 Inbox에 추가
        target_agent = get_agent_for_task(get_task(task_link.target_task_id))
        add_to_inbox(target_agent.id, link.link_id)
```

---

## 6. 협업 성공 메트릭 (Collaboration Success Metrics)

협업의 효과성을 측정하기 위한 메트릭을 정의합니다.

### 6.1 협업 성과 지표

```yaml
collaboration_metrics:

  # Metric 1: Mission Success Rate (미션 성공률)
  mission_success_rate:
    definition: "협업을 통해 학생 개선 미션이 달성된 비율"
    calculation: |
      success_count / total_missions * 100
    target: "> 75%"

  # Metric 2: Agent Coordination Efficiency (에이전트 조율 효율성)
  agent_coordination_efficiency:
    definition: "협업에 참여한 에이전트 간 정보 교환의 효율성"
    calculation: |
      successful_exchanges / total_exchanges * 100
    target: "> 85%"

  # Metric 3: Task Collaboration Quality (태스크 협업 품질)
  task_collaboration_quality:
    definition: "태스크 레벨 협업에서 교환된 데이터의 품질"
    calculation: |
      avg(data_quality_scores)
    target: "> 0.8"
    measurement:
      - completeness: 데이터 완전성
      - relevance: 데이터 관련성
      - timeliness: 데이터 적시성

  # Metric 4: Response Time (응답 시간)
  response_time:
    definition: "증거 감지부터 지시문 생성까지 소요 시간"
    calculation: |
      directive_timestamp - evidence_timestamp
    target: "< 5 minutes"

  # Metric 5: Student Outcome Improvement (학생 성과 개선)
  student_outcome_improvement:
    definition: "협업 후 학생의 실제 학습 성과 개선도"
    calculation: |
      (post_collaboration_score - pre_collaboration_score) / pre_collaboration_score * 100
    target: "> 20%"
    measurement_period: "2 weeks after collaboration"

  # Metric 6: Collaboration Precision (협업 정밀도)
  collaboration_precision:
    definition: "활성화된 협업 중 실제로 효과가 있었던 비율"
    calculation: |
      effective_collaborations / total_collaborations * 100
    target: "> 70%"
```

### 6.2 메트릭 수집 및 분석

```python
# Pseudocode: Metrics Collection

def collect_collaboration_metrics(collaboration_plan: CollaborationPlan, execution_log: ExecutionLog) -> CollaborationMetrics:
    """
    협업 메트릭 수집
    """
    metrics = CollaborationMetrics()

    # Mission Success Rate
    if execution_log.mission_achieved:
        metrics.mission_success_rate = 1.0
    else:
        metrics.mission_success_rate = 0.0

    # Agent Coordination Efficiency
    total_exchanges = len(execution_log.artifact_exchanges)
    successful_exchanges = sum(1 for ex in execution_log.artifact_exchanges if ex.status == 'success')
    metrics.agent_coordination_efficiency = successful_exchanges / total_exchanges if total_exchanges > 0 else 0.0

    # Task Collaboration Quality
    quality_scores = []
    for task_link in execution_log.task_links:
        quality = evaluate_data_quality(task_link)
        quality_scores.append(quality)
    metrics.task_collaboration_quality = sum(quality_scores) / len(quality_scores) if quality_scores else 0.0

    # Response Time
    metrics.response_time = (execution_log.directive_timestamp - execution_log.evidence_timestamp).total_seconds()

    # Student Outcome Improvement (delayed metric)
    # Will be measured 2 weeks after collaboration
    metrics.student_outcome_improvement = None  # To be collected later

    # Collaboration Precision
    metrics.collaboration_precision = calculate_collaboration_precision(collaboration_plan, execution_log)

    return metrics


def evaluate_data_quality(task_link: TaskLink) -> float:
    """
    태스크 링크의 데이터 품질 평가
    """
    completeness = evaluate_completeness(task_link.data, task_link.exchange_schema)
    relevance = evaluate_relevance(task_link.data, task_link.target_task)
    timeliness = evaluate_timeliness(task_link.created_at, task_link.expected_deadline)

    # 가중 평균
    quality = completeness * 0.4 + relevance * 0.4 + timeliness * 0.2

    return quality
```

---

## 7. 협업 패턴 온톨로지 통합

협업 패턴은 Ontology 시스템에 정의되어 Reasoning Engine이 활용할 수 있습니다.

### 7.1 Ontology Collaboration Layer 구조

```jsonld
{
  "@context": {
    "mk": "https://mathking.kr/ontology#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#"
  },
  "@graph": [
    {
      "@id": "mk:CollaborationPattern",
      "@type": "owl:Class",
      "rdfs:label": "Collaboration Pattern",
      "rdfs:comment": "Defines how agents and tasks cooperate for student improvement"
    },
    {
      "@id": "mk:CollaborationPattern/Mission01",
      "@type": "mk:CollaborationPattern",
      "rdfs:label": "Academic Performance Recovery",
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
      "mk:collaborationSequence": [
        "mk:CollaborationStep/mission01_step1",
        "mk:CollaborationStep/mission01_step2",
        "mk:CollaborationStep/mission01_step3",
        "mk:CollaborationStep/mission01_step4",
        "mk:CollaborationStep/mission01_step5"
      ],
      "mk:priority": 0.85,
      "mk:expectedOutcomes": [
        "Progress rate improvement",
        "Accuracy rate recovery",
        "Learning efficiency increase"
      ]
    },
    {
      "@id": "mk:CollaborationStep/mission01_step1",
      "@type": "mk:CollaborationStep",
      "rdfs:label": "Analyze Progress Gap",
      "mk:stepNumber": 1,
      "mk:agent": "mk:Agent/agent_curriculum",
      "mk:task": "mk:Task/analyze_progress_gap",
      "mk:inputFrom": [],
      "mk:outputTo": ["mk:Agent/agent_cognitive", "mk:Agent/agent_adaptive"]
    },
    {
      "@id": "mk:TaskCollaboration",
      "@type": "owl:Class",
      "rdfs:label": "Task Collaboration",
      "rdfs:comment": "Fine-grained cooperation between specific tasks"
    },
    {
      "@id": "mk:TaskCollaboration/curriculum_to_adaptive",
      "@type": "mk:TaskCollaboration",
      "rdfs:label": "Progress Analysis to Difficulty Adjustment",
      "mk:sourceTask": "mk:Task/problem_activity_analysis",
      "mk:targetTask": "mk:Task/difficulty_adjustment",
      "mk:exchangeSchema": {
        "from_source": {
          "current_progress_rate": "float",
          "weak_topic_list": "[string]",
          "error_pattern": "object"
        },
        "to_target": {
          "difficulty_level_recommendation": "string",
          "content_focus_areas": "[string]"
        }
      },
      "mk:activationCondition": "source_task.output.progress_rate < 0.5 AND target_task.context == 'difficulty_adjustment'"
    }
  ]
}
```

### 7.2 Ontology 기반 협업 쿼리

```python
# Pseudocode: Ontology-based Collaboration Query

def query_collaboration_patterns_from_ontology(evidence_categories: List[str]) -> List[CollaborationPattern]:
    """
    온톨로지에서 증거 카테고리에 해당하는 협업 패턴 조회
    """
    sparql_query = """
    PREFIX mk: <https://mathking.kr/ontology#>
    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

    SELECT ?pattern ?label ?priority ?agents
    WHERE {
        ?pattern a mk:CollaborationPattern .
        ?pattern rdfs:label ?label .
        ?pattern mk:priority ?priority .
        ?pattern mk:triggerEvidenceCategories ?evidenceCategory .
        ?pattern mk:participatingAgents ?agents .

        FILTER (?evidenceCategory IN (%s))
    }
    ORDER BY DESC(?priority)
    """ % ', '.join([f'mk:EvidenceCategory/{cat}' for cat in evidence_categories])

    results = execute_sparql_query(sparql_query)

    collaboration_patterns = []
    for result in results:
        pattern = CollaborationPattern(
            id=result['pattern'],
            label=result['label'],
            priority=float(result['priority']),
            agents=parse_agents(result['agents'])
        )
        collaboration_patterns.append(pattern)

    return collaboration_patterns
```

---

## 8. 실행 예시 (Execution Examples)

### Example 1: Academic Performance Recovery

**시나리오**: 학생이 최근 2주간 진도가 평균보다 20% 뒤처지고, 정답률이 55%로 하락

**증거 패키지**:
```yaml
evidence_package:
  evidence_id: "ev_20251029_001"
  source_agent_id: "agent04"
  student_id: "S12345"
  metrics:
    progress_delta: -0.20
    accuracy_rate: 0.55
    response_time_avg: 95
    retry_count: 4
    completion_rate: 0.62
  window:
    start_ts: "2025-10-15T00:00:00Z"
    end_ts: "2025-10-29T00:00:00Z"
  context:
    class_status: "mid"
    topic: "quadratic_equations"
    difficulty_level: "medium"
  state:
    affect: "med"
    focus: 0.65
    cognitive_load: "medium"
  tags: ["progress_lagging", "accuracy_declining"]
  priority: 0.85
```

**협업 활성화**:
1. Evidence 분류: `academic_performance.progress_lagging`, `academic_performance.accuracy_declining`
2. Mission 선택: Mission 01 (Academic Performance Recovery)
3. 참여 에이전트: `agent_curriculum`, `agent_adaptive`, `agent_time_management`, `agent_cognitive`

**협업 실행 시퀀스**:

```yaml
step_1:
  agent: agent_curriculum
  task: analyze_progress_gap
  input: [evidence_package]
  output:
    progress_gap_analysis:
      gap_percentage: -0.20
      weak_topics: ["quadratic_equations", "factoring"]
      recommended_focus: "quadratic_equations"
  artifact_id: "art_001"
  next_agents: [agent_cognitive, agent_adaptive]

step_2:
  agent: agent_cognitive
  task: assess_learning_efficiency
  input: [art_001]
  output:
    efficiency_assessment:
      efficiency_score: 0.62
      bottleneck: "working_memory_overload"
      recommendation: "reduce_cognitive_load"
  artifact_id: "art_002"
  next_agents: [agent_adaptive]

step_3:
  agent: agent_adaptive
  task: recommend_difficulty_adjustment
  input: [art_001, art_002]
  output:
    adjusted_content_plan:
      difficulty_level: "easy"
      content_type: "guided_practice"
      focus_areas: ["quadratic_equations basics"]
  artifact_id: "art_003"
  next_agents: [agent_time_management, agent_curriculum]

step_4:
  agent: agent_time_management
  task: create_recovery_schedule
  input: [art_003]
  output:
    recovery_schedule:
      daily_goal: "3 quadratic equation problems"
      session_duration: "30 minutes"
      frequency: "daily"
      deadline: "2025-11-12"
  artifact_id: "art_004"
  next_agents: [agent_curriculum]

step_5:
  agent: agent_curriculum
  task: implement_curriculum_changes
  input: [art_003, art_004]
  output:
    directive_package:
      directive_id: "dir_20251029_001"
      actions:
        - action_type: "adjust_difficulty"
          params: {difficulty: "easy", topic: "quadratic_equations"}
        - action_type: "recommend_content"
          params: {content_type: "guided_practice", quantity: 3}
        - action_type: "set_daily_goal"
          params: {goal: "3 problems", duration: "30 min"}
  artifact_id: "art_005"
```

**최종 지시문** (`directive_package`):
```yaml
directive_package:
  directive_id: "dir_20251029_001"
  decision_id: "dec_20251029_001"
  source_agent_mathking: "agent_curriculum"
  target_agent_orchestration: "agent04"
  directive_type: "action"
  actions:
    - action_id: "act_001"
      action_type: "adjust_difficulty"
      action_params:
        difficulty_level: "easy"
        topic: "quadratic_equations"
      execution_timing: "immediate"
    - action_id: "act_002"
      action_type: "recommend_content"
      action_params:
        content_type: "guided_practice"
        quantity: 3
        focus: "quadratic_equations basics"
      execution_timing: "immediate"
    - action_id: "act_003"
      action_type: "set_daily_goal"
      action_params:
        goal_description: "Complete 3 quadratic equation problems"
        duration_minutes: 30
        frequency: "daily"
        deadline: "2025-11-12"
      execution_timing: "next_session"
  rationale:
    rules_triggered:
      - "rule.curriculum.progress_lagging"
      - "rule.adaptive.difficulty_adjustment"
      - "rule.time_management.recovery_schedule"
    llm_reasoning: "Student is falling behind in quadratic equations due to cognitive overload. Reducing difficulty and providing guided practice will help rebuild confidence and understanding."
    evidence_used: ["ev_20251029_001", "art_001", "art_002"]
  priority: 0.85
  expected_impact:
    progress_delta_improvement: 0.15
    accuracy_improvement: 0.10
    confidence_boost: "medium"
  validation_criteria:
    check_after_days: 7
    success_metrics:
      - "progress_delta >= -0.05"
      - "accuracy_rate >= 0.65"
      - "completion_rate >= 0.70"
```

---

## 9. 협업 패턴 확장 가이드

새로운 협업 패턴을 추가하는 방법:

### 9.1 새로운 미션 추가

1. **증거 카테고리 정의**: 어떤 학생 데이터가 이 미션을 트리거하는가?
2. **목표 명확화**: 이 미션이 달성하려는 학생 개선 목표는 무엇인가?
3. **참여 에이전트 선정**: 어떤 에이전트들이 협력해야 하는가?
4. **협업 시퀀스 설계**: 에이전트 간 정보 흐름은 어떻게 되는가?
5. **태스크 레벨 협업 정의**: 세밀한 태스크 간 협력이 필요한가?
6. **성공 메트릭 정의**: 미션 성공을 어떻게 측정하는가?

### 9.2 온톨로지에 패턴 등록

```jsonld
{
  "@id": "mk:CollaborationPattern/MissionXX",
  "@type": "mk:CollaborationPattern",
  "rdfs:label": "New Mission Name",
  "mk:missionId": "mission_xx",
  "mk:triggerEvidenceCategories": [
    "mk:EvidenceCategory/new_category.subcategory"
  ],
  "mk:participatingAgents": [
    "mk:Agent/agent_xxx",
    "mk:Agent/agent_yyy"
  ],
  "mk:collaborationSequence": [...],
  "mk:priority": 0.xx,
  "mk:expectedOutcomes": [...]
}
```

### 9.3 알고리즘에 패턴 추가

`mission_catalog.yaml`에 새 미션 추가:
```yaml
mission_xx:
  id: "mission_xx"
  name: "New Mission Name"
  trigger_evidence: [...]
  participating_agents: [...]
  collaboration_sequence: [...]
  priority: 0.xx
```

---

## 10. 다음 단계

이 문서는 **02-COLLABORATION_PATTERNS.md**로서, 에이전트 및 태스크 간 협업 패턴을 정의했습니다.

**다음 문서**:
- `03-KNOWLEDGE_BASE_ARCHITECTURE.md`: LLM 최적화된 지식 베이스 구조 설계
- `04-ONTOLOGY_SYSTEM_DESIGN.md`: 다층 온톨로지 시스템 상세 명세
- `05-REASONING_ENGINE_SPEC.md`: 규칙 엔진 + LLM 추론 엔진 명세
- `06-INTEGRATION_ARCHITECTURE.md`: 전체 시스템 통합 아키텍처
- `07-IMPLEMENTATION_ROADMAP.md`: 단계별 구현 로드맵

---

**문서 끝**
