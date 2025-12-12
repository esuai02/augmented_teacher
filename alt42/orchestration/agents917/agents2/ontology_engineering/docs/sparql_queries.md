# SPARQL 쿼리 예제

생성일: 2025-01-27
온톨로지: AlphaTutor Learning Ontology

---

## 기본 쿼리

### 1. 모든 Student 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?student
WHERE {
    ?student rdf:type mk:Student .
}
```

### 2. Student의 모든 속성 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?property ?value
WHERE {
    ?student rdf:type mk:Student .
    ?student ?property ?value .
}
```

### 3. MathLevel이 "상위권"인 Student 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student
WHERE {
    ?student mk:hasAttribute ?level .
    ?level mk:hasValue "상위권" .
}
```

---

## 관계 탐색 쿼리

### 4. Student → Goal → Plan 경로 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?goal ?plan
WHERE {
    ?student mk:hasGoal ?goal .
    ?goal mk:hasPlan ?plan .
}
```

### 5. LearningActivity를 수행하는 Student 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT DISTINCT ?student ?activity
WHERE {
    ?student mk:performs ?activity .
    ?activity rdf:type mk:LearningActivity .
}
```

### 6. Persona를 가진 Student와 관련 활동 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?persona ?activity
WHERE {
    ?student mk:hasPersona ?persona .
    ?persona mk:affects ?activity .
}
```

---

## 추론 쿼리

### 7. isSubtypeOf 계층 구조 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?subclass ?superclass
WHERE {
    ?subclass mk:isSubtypeOf ?superclass .
}
ORDER BY ?superclass ?subclass
```

### 8. requires 의존성 체인 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?entity ?requires ?required
WHERE {
    ?entity mk:requires ?required .
}
```

### 9. affects 영향 관계 체인 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?source ?target
WHERE {
    ?source mk:affects ?target .
}
```

---

## 집계 쿼리

### 10. 서술어별 사용 빈도

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?predicate (COUNT(*) AS ?count)
WHERE {
    ?s ?predicate ?o .
    FILTER(STRSTARTS(STR(?predicate), "http://mathking.kr/ontology/alphatutor#"))
}
GROUP BY ?predicate
ORDER BY DESC(?count)
```

### 11. 가장 많이 연결된 엔티티

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?entity (COUNT(?predicate) AS ?connectionCount)
WHERE {
    ?entity ?predicate ?object .
    FILTER(STRSTARTS(STR(?predicate), "http://mathking.kr/ontology/alphatutor#"))
}
GROUP BY ?entity
ORDER BY DESC(?connectionCount)
LIMIT 20
```

---

## 복합 쿼리

### 12. Student의 완전한 프로필 조회

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?level ?confidence ?style ?goal ?routine
WHERE {
    ?student rdf:type mk:Student .
    OPTIONAL { ?student mk:hasAttribute ?level . ?level rdf:type mk:MathLevel . }
    OPTIONAL { ?student mk:hasAttribute ?confidence . ?confidence rdf:type mk:MathConfidence . }
    OPTIONAL { ?student mk:hasAttribute ?style . ?style rdf:type mk:MathLearningStyle . }
    OPTIONAL { ?student mk:hasGoal ?goal . }
    OPTIONAL { ?student mk:hasRoutine ?routine . }
}
```

### 13. EmotionPattern → Persona → SignatureRoutine 경로

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?student ?emotion ?persona ?routine
WHERE {
    ?student mk:hasEmotion ?emotion .
    ?emotion mk:leadsTo ?personaId .
    ?personaId mk:affects ?persona .
    ?persona mk:leadsTo ?routine .
    ?routine rdf:type mk:SignatureRoutine .
}
```

### 14. Teacher Feedback → Interaction Content 경로

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?teacher ?feedback ?content ?student
WHERE {
    ?teacher mk:provides ?feedback .
    ?feedback mk:generates ?content .
    ?content mk:affects ?student .
}
```

---

## 검증 쿼리

### 15. 순환 참조 검사 (isSubtypeOf)

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?a ?b ?c
WHERE {
    ?a mk:isSubtypeOf ?b .
    ?b mk:isSubtypeOf ?c .
    ?c mk:isSubtypeOf ?a .
}
```

### 16. 모순 관계 검사 (contradicts)

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?entity1 ?entity2
WHERE {
    ?entity1 mk:contradicts ?entity2 .
    ?entity1 mk:coOccursWith ?entity2 .
}
```

### 17. 고립된 엔티티 검사 (object로만 나타나는 엔티티)

```sparql
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

SELECT ?isolated
WHERE {
    ?s ?p ?isolated .
    FILTER NOT EXISTS {
        ?isolated ?p2 ?o .
    }
}
```

---

## 추론 규칙 예제

### 규칙 1: 전이성 (Transitivity)

```sparql
# isPrerequisiteOf의 전이성
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

CONSTRUCT {
    ?a mk:isPrerequisiteOf ?c
}
WHERE {
    ?a mk:isPrerequisiteOf ?b .
    ?b mk:isPrerequisiteOf ?c .
}
```

### 규칙 2: 대칭성 (Symmetry)

```sparql
# coOccursWith의 대칭성
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

CONSTRUCT {
    ?b mk:coOccursWith ?a
}
WHERE {
    ?a mk:coOccursWith ?b .
}
```

### 규칙 3: 역관계 (Inverse)

```sparql
# isPrerequisiteOf의 역관계
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>

CONSTRUCT {
    ?b mk:hasPrerequisite ?a
}
WHERE {
    ?a mk:isPrerequisiteOf ?b .
}
```

---

## 사용 방법

### Apache Jena 사용 예제

```bash
# 쿼리 실행
sparql --data=alphatutor_ontology.ttl --query=query1.rq
```

### Python (rdflib) 사용 예제

```python
from rdflib import Graph, Namespace
from rdflib.plugins.sparql import prepareQuery

# 온톨로지 로드
g = Graph()
g.parse("alphatutor_ontology.ttl", format="turtle")

# 쿼리 실행
query = """
PREFIX mk: <http://mathking.kr/ontology/alphatutor#>
SELECT ?student WHERE {
    ?student rdf:type mk:Student .
}
"""

results = g.query(query)
for row in results:
    print(row.student)
```

---

## 성능 최적화 팁

1. **인덱싱**: 자주 사용되는 속성에 대한 인덱스 생성
2. **필터 최적화**: FILTER 절을 가능한 한 빨리 적용
3. **LIMIT 사용**: 대용량 결과셋에 LIMIT 적용
4. **OPTIONAL 최소화**: 필요한 경우에만 OPTIONAL 사용

