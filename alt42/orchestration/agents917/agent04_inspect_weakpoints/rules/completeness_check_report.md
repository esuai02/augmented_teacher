# Agent 04 완결성 체크 리포트

**생성일**: 2025-01-27  
**검토 대상**: Agent 04 (Inspect Weakpoints)  
**검토 범위**: metadata.md, questions.md, rules.yaml, 웹사이트 문서

---

## 📋 1. 문서 간 완결성 분석

### 1.1 metadata.md 완결성 문제 ⚠️

**현재 상태**:
- `metadata.md`는 단 2개 항목만 포함 (학습 성향 2개, 정서 정보 2개)
- `questions.md`의 8가지 활동 영역과 72개 세분화 질문을 전혀 반영하지 않음
- `rules.yaml`의 72개 룰과 매칭되지 않음

**문제점**:
1. **데이터 항목 누락**: `gendata.md`에 정의된 100개 데이터 항목 중 대부분이 `metadata.md`에 없음
2. **활동 영역 불일치**: 8가지 활동 영역(개념이해, 유형학습, 문제풀이, 오답노트, 질의응답, 복습활동, 포모도르, 귀가검사)이 반영되지 않음
3. **페르소나 데이터 부재**: mission.md에서 강조하는 페르소나 분석 관련 메타데이터가 없음

**권장 사항**:
- `metadata.md`를 `questions.md`와 `rules.yaml`의 구조에 맞춰 재구성 필요
- 8가지 활동 영역별 메타데이터 섹션 추가
- 페르소나 관련 데이터 항목 추가

---

### 1.2 questions.md ↔ rules.yaml 매칭 분석 ✅

**매칭 상태**: 양호

| 활동 영역 | questions.md 질문 수 | rules.yaml 룰 수 | 매칭도 |
|---------|-------------------|----------------|-------|
| ① 개념이해 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ② 유형학습 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ③ 문제풀이 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ④ 오답노트 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ⑤ 질의응답 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ⑥ 복습활동 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ⑦ 포모도르 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| ⑧ 귀가검사 | 9개 세분화 질문 | 9개 룰 | ✅ 완벽 |
| **복합 상황** | - | 4개 룰 | ✅ 추가 |

**결론**: `questions.md`의 세분화 질문과 `rules.yaml`의 룰이 1:1로 잘 매칭되어 있음.

---

### 1.3 웹사이트 문서 ↔ questions.md 매칭 분석 ✅

**매칭 상태**: 일치

웹사이트의 포괄형 질문 3개가 `questions.md`의 상단 포괄형 질문과 일치:
- ✅ "이 학생의 개념이해 과정에서 가장 취약한 구간은 어디인가요?"
- ✅ "현재 개념공부 방식이 학생의 인지스타일과 잘 맞고 있나요?"
- ✅ "이 학생이 개념공부에 몰입하기 위해 지금 어떤 활동 조합(TTS, 필기, 예제)이 효과적일까요?"

8가지 활동 영역별 포괄형 질문도 모두 일치함.

---

## 🎯 2. Rule 기반 효율적 시스템 동작 방안

### 2.1 우선순위 기반 룰 실행 전략

**현재 상태**:
- `rules.yaml`의 룰들은 `priority` 필드로 우선순위가 설정되어 있음 (88~96 범위)
- 복합 상황 룰의 우선순위가 더 높음 (92~96)

**효율적 실행 전략**:

1. **우선순위 큐 기반 실행**
   ```
   Priority 96: 복합 상황 룰 (CR1~CR4)
   Priority 95: 핵심 취약점 탐지 룰
   Priority 94: 패턴 분석 룰
   Priority 93: 최적화 룰
   Priority 92 이하: 일반 진단 룰
   ```

2. **조건 충족도 기반 필터링**
   - 모든 조건이 충족된 룰만 실행
   - 부분 조건 충족 시 confidence 점수 조정

3. **룰 체이닝 최적화**
   - 선행 룰의 결과를 후행 룰의 조건으로 활용
   - 예: `CU_A1_weak_point_detection` → `EN_A1_error_cause_analysis`

### 2.2 룰 실행 효율화 제안

**A. 룰 그룹화 및 배치 실행**
```yaml
rule_groups:
  - group_id: "concept_understanding_batch"
    rules: ["CU_A1", "CU_A2", "CU_A3"]
    execution_mode: "parallel"  # 병렬 실행 가능
    cache_key: "activity_type:concept_understanding"
    
  - group_id: "error_analysis_batch"
    rules: ["EN_A1", "EN_A2", "EN_A3"]
    execution_mode: "sequential"  # 순차 실행 필요
    depends_on: ["concept_understanding_batch"]
```

**B. 조건 캐싱 전략**
- 자주 사용되는 조건 필드(`activity_type`, `emotion_state` 등)를 캐시
- 세션별로 한 번만 평가하고 재사용

**C. 룰 실행 결과 재사용**
- 동일 세션 내에서 이미 실행된 룰의 결과를 재사용
- 시간 윈도우 기반 결과 유효성 검증

---

## 🧠 3. 온톨로지 필수 적용 영역

### 3.1 페르소나 관계 모델링 (필수) 🔴

**이유**:
- Agent 04의 핵심 미션은 "페르소나 분석 및 맞춤 행동유도"
- 60개 이상의 페르소나 유형이 존재 (문제풀이만 60개)
- 페르소나 간 관계, 전환 패턴, 효율성 비교가 필요

**온톨로지 구조 제안**:
```json
{
  "@id": "mk:Persona",
  "@type": "owl:Class",
  "rdfs:label": "학습 페르소나",
  "mk:hasActivity": ["concept_understanding", "type_learning", "problem_solving"],
  "mk:hasCharacteristics": ["analysis_type", "response_speed", "retry_tendency"],
  "mk:transitionsTo": "mk:PersonaTransition"
}

{
  "@id": "mk:PersonaTransition",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "페르소나 전환",
  "mk:hasTrigger": ["emotion_change", "difficulty_change", "success_pattern"],
  "mk:hasProbability": "xsd:decimal"
}
```

**필요한 관계**:
- `persona_has_optimal_method`: 페르소나 → 최적 학습 방법
- `persona_transitions_to`: 페르소나 → 페르소나 (전환 패턴)
- `persona_matches_activity`: 페르소나 → 활동 유형 (적합도)
- `persona_requires_intervention`: 페르소나 → 개입 유형

---

### 3.2 활동-취약점-개입 연계 모델링 (필수) 🔴

**이유**:
- 8가지 활동 영역 간 취약점이 연계되어 있음
- 복합 상황 룰(CR1~CR4)이 이를 증명
- 개념이해 취약점 → 오답 패턴, 문제풀이 인지부하 → 복습 저항감 등

**온톨로지 구조 제안**:
```json
{
  "@id": "mk:WeakPoint",
  "@type": "owl:Class",
  "rdfs:label": "학습 취약점",
  "mk:belongsToActivity": "mk:Activity",
  "mk:causesError": "mk:ErrorPattern",
  "mk:requiresIntervention": "mk:Intervention"
}

{
  "@id": "mk:WeakPointRelation",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "취약점 연계",
  "mk:relationType": ["causes", "exacerbates", "mitigates"],
  "mk:strength": "xsd:decimal"
}
```

**필요한 관계**:
- `weakpoint_causes_error`: 취약점 → 오류 패턴
- `weakpoint_requires_intervention`: 취약점 → 개입 유형
- `activity_has_weakpoint`: 활동 → 취약점
- `weakpoint_chain`: 취약점 → 취약점 (연쇄 관계)

---

### 3.3 학습 단계 계층 구조 모델링 (필수) 🔴

**이유**:
- 각 활동이 여러 단계로 구성됨 (예: 문제풀이 = 해석→시작→과정→마무리→검토)
- 단계별 페르소나, 취약점, 개입이 다름
- 단계 간 선후 관계와 전환 조건이 중요

**온톨로지 구조 제안**:
```json
{
  "@id": "mk:ActivityStage",
  "@type": "owl:Class",
  "rdfs:label": "활동 단계",
  "mk:belongsToActivity": "mk:Activity",
  "mk:hasPrerequisite": "mk:ActivityStage",
  "mk:hasNextStage": "mk:ActivityStage",
  "mk:hasWeakPoint": "mk:WeakPoint",
  "mk:hasPersona": "mk:Persona"
}

{
  "@id": "mk:StageTransition",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "단계 전환",
  "mk:hasCondition": "mk:TransitionCondition",
  "mk:hasProbability": "xsd:decimal"
}
```

**필요한 관계**:
- `stage_precedes`: 단계 → 단계 (선후 관계)
- `stage_has_persona`: 단계 → 페르소나
- `stage_has_weakpoint`: 단계 → 취약점
- `stage_transition_condition`: 단계 전환 → 조건

---

### 3.4 개입-효과성 추론 모델링 (권장) 🟡

**이유**:
- 동일 개입이 페르소나/상황에 따라 효과가 다름
- 개입 효과성 데이터를 축적하여 추론 가능
- 미래 개입 선택 시 온톨로지 기반 추론 활용

**온톨로지 구조 제안**:
```json
{
  "@id": "mk:Intervention",
  "@type": "owl:Class",
  "rdfs:label": "학습 개입",
  "mk:targetsWeakPoint": "mk:WeakPoint",
  "mk:suitsPersona": "mk:Persona",
  "mk:hasEffectiveness": "mk:EffectivenessScore",
  "mk:hasDeliveryMethod": ["TTS", "visual", "interactive"]
}

{
  "@id": "mk:InterventionEffectiveness",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "개입 효과성",
  "mk:hasContext": "mk:Context",
  "mk:hasScore": "xsd:decimal",
  "mk:hasEvidence": "mk:Evidence"
}
```

---

### 3.5 개념-문제-오류 연계 모델링 (권장) 🟡

**이유**:
- 오답 원인이 개념 이해 부족과 연결됨
- 문제 유형과 개념의 관계가 중요
- 오류 패턴이 개념별로 다름

**온톨로지 구조 제안**:
```json
{
  "@id": "mk:Concept",
  "@type": "owl:Class",
  "rdfs:label": "수학 개념",
  "mk:hasPrerequisite": "mk:Concept",
  "mk:appearsInProblem": "mk:ProblemType",
  "mk:causesError": "mk:ErrorPattern"
}

{
  "@id": "mk:ConceptErrorRelation",
  "@type": "owl:ObjectProperty",
  "rdfs:label": "개념-오류 관계",
  "mk:hasErrorType": ["concept_error", "calculation_error", "understanding_error"],
  "mk:hasFrequency": "xsd:decimal"
}
```

---

## 📊 4. 개선 우선순위 및 액션 아이템

### 우선순위 1 (즉시) 🔴
1. **metadata.md 재구성**
   - 8가지 활동 영역별 메타데이터 섹션 추가
   - `gendata.md`의 100개 데이터 항목 반영
   - 페르소나 관련 메타데이터 추가

2. **페르소나 온톨로지 설계 및 구현**
   - 페르소나 엔티티 정의
   - 페르소나-활동-개입 관계 모델링
   - 페르소나 전환 패턴 온톨로지화

### 우선순위 2 (단기) 🟡
3. **활동-취약점-개입 온톨로지 구현**
   - 취약점 엔티티 정의
   - 활동-취약점-개입 연계 관계 모델링
   - 복합 상황 룰의 온톨로지 기반 추론

4. **학습 단계 계층 구조 온톨로지 구현**
   - 활동 단계 엔티티 정의
   - 단계 간 전환 조건 모델링

### 우선순위 3 (중기) 🟢
5. **룰 실행 효율화 개선**
   - 룰 그룹화 및 배치 실행 구현
   - 조건 캐싱 전략 적용
   - 룰 실행 결과 재사용 메커니즘

6. **개입 효과성 추론 시스템**
   - 개입 효과성 데이터 수집
   - 온톨로지 기반 효과성 추론 엔진

---

## 📝 5. 결론

### 완결성 점수
- **questions.md ↔ rules.yaml**: 100% ✅
- **웹사이트 ↔ questions.md**: 100% ✅
- **metadata.md 완결성**: 20% ⚠️ (개선 필요)

### 핵심 권장사항
1. **metadata.md 즉시 개선**: questions.md와 rules.yaml의 구조를 반영하여 재작성
2. **온톨로지 필수 적용**: 페르소나, 취약점-개입 연계, 학습 단계 계층 구조
3. **룰 실행 최적화**: 우선순위 기반 실행, 조건 캐싱, 결과 재사용

### 다음 단계
1. metadata.md 재작성 작업 시작
2. 페르소나 온톨로지 설계 문서 작성
3. 온톨로지 스키마 정의 및 데이터베이스 구조 설계

