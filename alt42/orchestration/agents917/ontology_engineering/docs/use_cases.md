# AlphaTutor 온톨로지 활용 예시

생성일: 2025-01-27

---

## 🎯 예시 1: 학습 이탈 위험 학생 자동 감지 및 개입 제안

### 시나리오
수학 수준이 낮고 자신감이 낮은 학생이 최근 출석률이 감소하고 포모도로 완료율이 낮아지고 있습니다. 온톨로지를 통해 이 학생의 이탈 위험을 자동으로 감지하고 적절한 개입 방법을 제안합니다.

### 온톨로지 활용

#### 1. 학생 상태 파악
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?mathLevel ?confidence ?attendance ?pomodoro
WHERE {
    ?student rdf:type mk:Student .
    ?student mk:hasAttribute ?mathLevel .
    ?mathLevel mk:hasValue "수학이 어려워요" .
    
    ?student mk:hasAttribute ?confidence .
    ?confidence mk:isSubtypeOf mk:LowMathConfidence .
    
    ?student mk:hasAttribute ?attendance .
    ?attendance mk:isSubtypeOf mk:AttendanceDecrease .
    
    ?student mk:performs ?pomodoro .
    ?pomodoro mk:isSubtypeOf mk:LowPomodoroCompletionRate .
}
```

#### 2. 이탈 위험 추론
온톨로지 추론 규칙:
- `hasAttribute(?student, LowMathConfidence) ^ hasAttribute(?student, AttendanceDecrease) ^ performs(?student, LowPomodoroCompletionRate) → hasRisk(?student, LearningDropout)`

#### 3. 개입 방법 제안
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?risk ?intervention ?method
WHERE {
    ?student mk:hasRisk ?risk .
    ?risk mk:isSubtypeOf mk:LearningDropout .
    ?risk mk:hasRiskLevel ?level .
    ?level mk:hasValue "High" .
    
    ?risk mk:requires ?intervention .
    ?intervention mk:hasAction ?method .
    ?method mk:isSubtypeOf ?interventionType .
    
    FILTER(?interventionType IN (mk:EmotionalSupport, mk:GoalAdjustment, mk:RoutineModification))
}
```

### 결과
- **자동 감지**: 낮은 수학 수준 + 낮은 자신감 + 출석 감소 → 이탈 위험 높음
- **개입 제안**: 
  - 감정 지원 (EmotionalSupport)
  - 목표 조정 (GoalAdjustment) 
  - 루틴 수정 (RoutineModification)

---

## 🎯 예시 2: 학생 페르소나 기반 맞춤형 학습 루틴 추천

### 시나리오
학생의 학습 활동 패턴(에러 노트 작성 빈도, 개념 노트 작성 방식, 휴식 버튼 클릭 패턴)을 분석하여 페르소나를 식별하고, 해당 페르소나에 맞는 시그너처 루틴을 추천합니다.
    ?conceptNote mk:isSubtypeOf mk:ConceptNote .
    ?conceptNote mk:hasPattern ?conceptPattern .
    
    ?student mk:hasRoutine ?restRoutine .
    ?restRoutine mk:isSubtypeOf mk:RestRoutine .
    ?restRoutine mk:hasPattern ?restPattern .
    
    BIND(CONCAT(?errorPattern, "-", ?conceptPattern, "-", ?restPattern) AS ?pattern)
}
```

#### 2. 페르소나 식별
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?persona ?emotionPattern
WHERE {
    ?student mk:hasPersona ?persona .
    ?persona mk:isSubtypeOf ?personaType .
    
    ?student mk:hasEmotion ?emotionPattern .
    ?emotionPattern mk:leadsTo ?personaIdentification .
    ?personaIdentification mk:identifies ?persona .
    
    ?persona mk:affects ?learningActivity .
}
```

#### 3. 맞춤형 루틴 추천
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?persona ?routine ?routineType
WHERE {
    ?student mk:hasPersona ?persona .
    ?persona mk:requires ?routine .
    ?routine mk:isSubtypeOf mk:SignatureRoutine .
    
    ?routine mk:requires ?persona .
    ?routine mk:requires ?immersion .
    ?routine mk:affects ?learningEfficiency .
    
    ?routine mk:hasType ?routineType .
}
ORDER BY ?student
```

### 결과
- **페르소나 식별**: 학습 활동 패턴 → "완벽주의형", "즉흥형", "계획형" 등
- **맞춤 루틴**: 
  - 완벽주의형 → 체계적인 복습 루틴
  - 즉흥형 → 단기 집중 루틴
  - 계획형 → 장기 목표 기반 루틴

---

## 🎯 예시 3: 목표 달성을 위한 단계별 계획 자동 생성

### 시나리오
학생이 "수학을 잘해서 원하는 학교 가기"라는 장기 목표를 설정했습니다. 온톨로지를 통해 이 목표를 분기별, 주간, 일일 목표로 자동 분해하고, 각 목표에 필요한 계획과 활동을 추론하여 제안합니다.

### 온톨로지 활용

#### 1. 목표 계층 구조 추론
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?longTermGoal ?quarterlyGoal ?weeklyGoal ?todayGoal
WHERE {
    ?student mk:hasGoal ?longTermGoal .
    ?longTermGoal mk:isSubtypeOf mk:LongTermGoal .
    ?longTermGoal mk:hasValue "수학을 잘해서 원하는 학교 가기" .
    
    ?longTermGoal mk:isPrerequisiteOf ?quarterlyGoal .
    ?quarterlyGoal mk:isSubtypeOf mk:QuarterlyGoal .
    
    ?quarterlyGoal mk:isPrerequisiteOf ?weeklyGoal .
    ?weeklyGoal mk:isSubtypeOf mk:WeeklyGoal .
    
    ?weeklyGoal mk:isPrerequisiteOf ?todayGoal .
    ?todayGoal mk:isSubtypeOf mk:TodayGoal .
}
```

#### 2. 각 목표에 필요한 계획 추론
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?goal ?plan ?requirement ?activity
WHERE {
    ?goal mk:hasPlan ?plan .
    ?plan mk:isSubtypeOf mk:Plan .
    
    ?plan mk:requires ?requirement .
    ?requirement mk:isSubtypeOf ?reqType .
    
    ?plan mk:leadsTo ?activity .
    ?activity mk:isSubtypeOf mk:LearningActivity .
    
    FILTER(?reqType IN (mk:FeasibilityCheck, mk:ResilienceDesign, mk:TimeBudget))
}
```

#### 3. 목표 달성을 위한 활동 체인 추론
```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?goal ?activity ?nextActivity ?outcome
WHERE {
    ?goal mk:hasPlan ?plan .
    ?plan mk:leadsTo ?activity .
    ?activity mk:isSubtypeOf mk:LearningActivity .
    
    ?activity mk:leadsTo ?nextActivity .
    ?nextActivity mk:isSubtypeOf mk:LearningActivity .
    
    ?activity mk:resultsIn ?outcome .
    ?outcome mk:isSubtypeOf ?outcomeType .
    
    FILTER(?outcomeType IN (mk:ConceptUnderstanding, mk:ProblemSolving, mk:BehaviorChange))
}
ORDER BY ?goal ?activity
```

### 결과
- **목표 분해**: 
  - 장기 목표 → 분기 목표 → 주간 목표 → 일일 목표
- **계획 생성**:
  - 각 목표에 필요한 실행 계획 자동 생성
  - 실현 가능성 검토 (FeasibilityCheck)
  - 탄력성 설계 (ResilienceDesign)
- **활동 체인**:
  - 개념 이해 → 문제 풀이 → 에러 노트 → 복습 → 행동 변화

---

## 📊 온톨로지 활용의 핵심 가치

### 1. 자동 추론
- 명시적으로 정의되지 않은 관계도 추론 가능
- 복잡한 조건 조합을 자동으로 처리

### 2. 지식 통합
- 22개 Agent의 지식을 하나의 온톨로지로 통합
- Cross-Agent 관계를 쉽게 탐색

### 3. 확장 가능성
- 새로운 개념과 관계를 쉽게 추가 가능
- 기존 지식과의 일관성 자동 검증

---

## 🔧 실제 구현 예시

### Python (rdflib 사용)
```python
from rdflib import Graph, Namespace
from rdflib.namespace import RDF

# 온톨로지 로드
g = Graph()
g.parse("alphatutor_ontology.ttl", format="turtle")

MK = Namespace("http://mathking.kr/ontology/alphatutor#")

# 예시 1: 이탈 위험 학생 찾기
def find_at_risk_students():
    query = """
    PREFIX mk: <http://mathking.kr/ontology/alphatutor#>
    SELECT ?student WHERE {
        ?student mk:hasAttribute ?level .
        ?level mk:hasValue "수학이 어려워요" .
        ?student mk:hasRisk ?risk .
        ?risk mk:hasRiskLevel ?level .
        ?level mk:hasValue "High" .
    }
    """
    results = g.query(query)
    return [str(row.student) for row in results]

# 예시 2: 페르소나 기반 루틴 추천
def recommend_routine(student_uri, persona_uri):
    query = """
    PREFIX mk: <http://mathking.kr/ontology/alphatutor#>
    SELECT ?routine WHERE {
        ?routine mk:requires ?persona .
        ?routine mk:isSubtypeOf mk:SignatureRoutine .
        FILTER(?persona = ?persona_uri)
    }
    """
    results = g.query(query, initBindings={'persona_uri': persona_uri})
    return [str(row.routine) for row in results]

# 예시 3: 목표 계획 생성
def generate_goal_plan(long_term_goal_uri):
    query = """
    PREFIX mk: <http://mathking.kr/ontology/alphatutor#>
    SELECT ?goal ?plan ?activity WHERE {
        ?goal mk:hasPlan ?plan .
        ?plan mk:leadsTo ?activity .
        ?goal mk:isPrerequisiteOf* ?subgoal .
    }
    """
    results = g.query(query, initBindings={'goal': long_term_goal_uri})
    return [(str(row.goal), str(row.plan), str(row.activity)) for row in results]
```

---

**마지막 업데이트**: 2025-01-27

