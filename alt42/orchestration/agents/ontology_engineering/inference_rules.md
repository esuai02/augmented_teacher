# Triple 추론 규칙 정의

생성일: 2025-01-27
온톨로지: AlphaTutor Learning Ontology

---

## 📋 추론 규칙 개요

추론 규칙은 기존 triple로부터 새로운 triple을 유도하는 규칙입니다. 이를 통해 온톨로지의 완전성과 일관성을 확보할 수 있습니다.

---

## 🔄 전이성 규칙 (Transitivity Rules)

### 1. isPrerequisiteOf 전이성

**규칙**: A가 B의 전제조건이고, B가 C의 전제조건이면, A는 C의 전제조건이다.

```
IF (A, isPrerequisiteOf, B) AND (B, isPrerequisiteOf, C)
THEN (A, isPrerequisiteOf, C)
```

**예시**:
```
(ConceptProgress, isPrerequisiteOf, AdvancedProgress)
(AdvancedProgress, isPrerequisiteOf, ExamPreparation)
→ (ConceptProgress, isPrerequisiteOf, ExamPreparation)
```

**SPARQL 구현**:
```sparql
CONSTRUCT {
    ?a at:isPrerequisiteOf ?c
}
WHERE {
    ?a at:isPrerequisiteOf ?b .
    ?b at:isPrerequisiteOf ?c .
}
```

---

### 2. isSubtypeOf 전이성

**규칙**: A가 B의 하위타입이고, B가 C의 하위타입이면, A는 C의 하위타입이다.

```
IF (A, isSubtypeOf, B) AND (B, isSubtypeOf, C)
THEN (A, isSubtypeOf, C)
```

**예시**:
```
(MathConfidence, isSubtypeOf, Confidence)
(Confidence, isSubtypeOf, EmotionalState)
→ (MathConfidence, isSubtypeOf, EmotionalState)
```

---

## 🔁 대칭성 규칙 (Symmetry Rules)

### 3. coOccursWith 대칭성

**규칙**: A가 B와 함께 발생하면, B도 A와 함께 발생한다.

```
IF (A, coOccursWith, B)
THEN (B, coOccursWith, A)
```

**예시**:
```
(HighWritingAmount, coOccursWith, Concentration)
→ (Concentration, coOccursWith, HighWritingAmount)
```

**SPARQL 구현**:
```sparql
CONSTRUCT {
    ?b at:coOccursWith ?a
}
WHERE {
    ?a at:coOccursWith ?b .
}
```

---

## ⬅️ 역관계 규칙 (Inverse Rules)

### 4. isPrerequisiteOf 역관계

**규칙**: A가 B의 전제조건이면, B는 A를 필요로 한다.

```
IF (A, isPrerequisiteOf, B)
THEN (B, requires, A)
```

**예시**:
```
(ConceptProgress, isPrerequisiteOf, AdvancedProgress)
→ (AdvancedProgress, requires, ConceptProgress)
```

---

### 5. affects 역관계 (조건부)

**규칙**: A가 B에 영향을 미치면, B는 A의 영향을 받는다 (일부 경우).

```
IF (A, affects, B) AND (A, rdf:type, at:EmotionPattern)
THEN (B, affectedBy, A)
```

---

## 🔗 결합 규칙 (Composition Rules)

### 6. requires + leadsTo 결합

**규칙**: A가 B를 필요로 하고, B가 C로 이어지면, A는 C로 이어질 수 있다.

```
IF (A, requires, B) AND (B, leadsTo, C)
THEN (A, leadsTo, C) [확률적]
```

**예시**:
```
(Routine, requires, MathLevel)
(MathLevel, leadsTo, LearningProgress)
→ (Routine, leadsTo, LearningProgress) [높은 확률]
```

---

### 7. causes + affects 결합

**규칙**: A가 B를 유발하고, B가 C에 영향을 미치면, A는 C에 영향을 미친다.

```
IF (A, causes, B) AND (B, affects, C)
THEN (A, affects, C)
```

**예시**:
```
(LowMathConfidence, causes, LowMotivation)
(LowMotivation, affects, LearningActivity)
→ (LowMathConfidence, affects, LearningActivity)
```

---

## 📊 계층 규칙 (Hierarchy Rules)

### 8. 하위타입 속성 상속

**규칙**: A가 B의 하위타입이고, B가 C를 가진다면, A도 C를 가진다.

```
IF (A, isSubtypeOf, B) AND (B, hasAttribute, C)
THEN (A, hasAttribute, C)
```

**예시**:
```
(StrugglingStudent, isSubtypeOf, Student)
(Student, hasAttribute, MathLevel)
→ (StrugglingStudent, hasAttribute, MathLevel)
```

---

### 9. 하위타입 관계 상속

**규칙**: A가 B의 하위타입이고, B가 C와 관계를 가지면, A도 C와 관계를 가진다.

```
IF (A, isSubtypeOf, B) AND (B, performs, C)
THEN (A, performs, C)
```

---

## 🎯 도메인 특화 규칙 (Domain-Specific Rules)

### 10. 목표 계층 전이성

**규칙**: 장기 목표가 분기 목표의 전제조건이고, 분기 목표가 주간 목표의 전제조건이면, 장기 목표는 주간 목표의 전제조건이다.

```
IF (LongTermGoal, isPrerequisiteOf, QuarterlyGoal) 
   AND (QuarterlyGoal, isPrerequisiteOf, WeeklyGoal)
THEN (LongTermGoal, isPrerequisiteOf, WeeklyGoal)
```

---

### 11. 학습 활동 → 페르소나 → 루틴 체인

**규칙**: 학습 활동이 페르소나에 영향을 미치고, 페르소나가 시그너처 루틴으로 이어지면, 학습 활동은 시그너처 루틴으로 이어질 수 있다.

```
IF (LearningActivity, affects, Persona) 
   AND (Persona, leadsTo, SignatureRoutine)
THEN (LearningActivity, leadsTo, SignatureRoutine) [높은 확률]
```

---

### 12. 감정 → 피드백 → 행동변화 체인

**규칙**: 감정 패턴이 피드백 명령으로 이어지고, 피드백 명령이 행동변화로 이어지면, 감정 패턴은 행동변화로 이어질 수 있다.

```
IF (EmotionPattern, leadsTo, FeedbackCommand) 
   AND (FeedbackCommand, leadsTo, BehaviorChange)
THEN (EmotionPattern, leadsTo, BehaviorChange)
```

---

## ⚠️ 모순 검사 규칙 (Contradiction Detection)

### 13. contradicts + coOccursWith 모순

**규칙**: 두 엔티티가 서로 모순되면서 동시에 함께 발생할 수 없다.

```
IF (A, contradicts, B) AND (A, coOccursWith, B)
THEN ERROR: 모순 발견
```

**검증 쿼리**:
```sparql
SELECT ?a ?b WHERE {
    ?a at:contradicts ?b .
    ?a at:coOccursWith ?b .
}
```

---

### 14. requires + contradicts 모순

**규칙**: A가 B를 필요로 하면서 동시에 B와 모순될 수 없다.

```
IF (A, requires, B) AND (A, contradicts, B)
THEN ERROR: 모순 발견
```

---

## 🔍 완전성 검사 규칙 (Completeness Rules)

### 15. 필수 속성 검사

**규칙**: Student는 반드시 MathLevel을 가져야 한다.

```
IF (X, rdf:type, at:Student) 
   AND NOT EXISTS (X, at:hasAttribute, ?level WHERE ?level rdf:type at:MathLevel)
THEN WARNING: Student가 MathLevel을 가지지 않음
```

---

### 16. 관계 완전성 검사

**규칙**: Goal은 반드시 Plan을 가져야 한다.

```
IF (X, rdf:type, at:Goal) 
   AND NOT EXISTS (X, at:hasPlan, ?plan)
THEN WARNING: Goal이 Plan을 가지지 않음
```

---

## 📈 확률적 추론 규칙 (Probabilistic Rules)

### 17. 높은 확률 관계

**규칙**: 특정 조건에서 높은 확률로 성립하는 관계

```
IF (HighMathConfidence, suggests, ChallengeProblem) [확률: 0.8]
   AND (ChallengeProblem, requires, AdvancedContent) [확률: 0.9]
THEN (HighMathConfidence, suggests, AdvancedContent) [확률: 0.72]
```

---

### 18. 조건부 확률 관계

**규칙**: 조건에 따라 확률이 변하는 관계

```
IF (Student, hasAttribute, LowMathLevel) 
   AND (LowMathLevel, causes, LowMotivation) [확률: 0.7]
THEN (Student, hasAttribute, LowMotivation) [확률: 0.7]
```

---

## 🛠️ 구현 방법

### OWL 2 RL 규칙 사용

```turtle
# 전이성 속성 정의
at:isPrerequisiteOf rdf:type owl:TransitiveProperty .

# 대칭성 속성 정의
at:coOccursWith rdf:type owl:SymmetricProperty .

# 역관계 정의
at:hasPrerequisite owl:inverseOf at:isPrerequisiteOf .
```

### SPARQL CONSTRUCT 사용

```sparql
# 추론 규칙을 CONSTRUCT 쿼리로 구현
CONSTRUCT {
    ?a at:isPrerequisiteOf ?c
}
WHERE {
    ?a at:isPrerequisiteOf ?b .
    ?b at:isPrerequisiteOf ?c .
}
```

### SWRL 규칙 사용

```swrl
# SWRL (Semantic Web Rule Language) 예제
isPrerequisiteOf(?a, ?b) ^ isPrerequisiteOf(?b, ?c) 
  -> isPrerequisiteOf(?a, ?c)
```

---

## ✅ 검증 체크리스트

- [x] 전이성 규칙 정의
- [x] 대칭성 규칙 정의
- [x] 역관계 규칙 정의
- [x] 결합 규칙 정의
- [x] 계층 규칙 정의
- [x] 도메인 특화 규칙 정의
- [x] 모순 검사 규칙 정의
- [x] 완전성 검사 규칙 정의
- [ ] 확률적 추론 규칙 구현
- [ ] 추론 엔진 테스트

---

## 📝 다음 단계

1. 추론 규칙을 OWL 2 RL로 변환
2. 추론 엔진 설정 및 테스트
3. 추론 결과 검증
4. 성능 최적화
5. 문서화 완료

