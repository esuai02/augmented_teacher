# 📈 Mathking 온톨로지 추론 시스템 - 점진적 확장 가이드

**"먼저 동작하게 만들고, 그 다음에 확장한다"**

이 문서는 최소 예제(3개 개념, 3개 규칙)에서 전체 Mathking 시스템(22개 에이전트, 수백 개 규칙)으로 **안전하게 단계별로 확장**하는 방법을 안내합니다.

---

## 📍 현재 위치: Phase 0 (Hello World)

✅ **완료된 것**:
- 온톨로지: 3개 개념 (Student, Emotion, hasEmotion)
- 추론 엔진: 3개 규칙 (좌절→격려, 집중→학습, 피로→휴식)
- 검증 도구: 문서 일관성 자동 검증

🎯 **다음 목표**: Phase 1 - 온톨로지 확장 (5개 감정 추가)

---

## 🗺️ 전체 확장 로드맵

```
Phase 0 (현재)
  └─ 개념 3개, 규칙 3개 ✅ 완료
      ↓
Phase 1 (온톨로지 확장)
  └─ 개념 10개, 규칙 10개
      ↓
Phase 2 (복합 조건 추가)
  └─ 개념 15개, 규칙 20개 (복합 조건 5개)
      ↓
Phase 3 (에이전트 연동 준비)
  └─ 개념 30개, 규칙 50개 (에이전트 1개 연동)
      ↓
Phase 4 (다중 에이전트 통합)
  └─ 개념 100개, 규칙 200개 (에이전트 5개 연동)
      ↓
Phase 5 (전체 시스템)
  └─ 22개 에이전트 전체 통합
```

---

## 📖 Phase 1: 온톨로지 확장 (감정 개념 추가)

### Step 1-1: 새로운 감정 개념 정의

**목표**: 온톨로지에 5개 감정 추가 (좌절, 집중, 피로, 불안, 기쁨)

**작업**: `01_minimal_ontology.json` 수정

```json
{
  "@context": {
    "@vocab": "http://mathking.kr/ontology#",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#"
  },
  "@graph": [
    {
      "@id": "Student",
      "@type": "rdfs:Class",
      "rdfs:label": "학생",
      "rdfs:comment": "학습하는 사람"
    },
    {
      "@id": "Emotion",
      "@type": "rdfs:Class",
      "rdfs:label": "감정",
      "rdfs:comment": "학습 중 느끼는 감정 상태"
    },
    {
      "@id": "hasEmotion",
      "@type": "rdf:Property",
      "rdfs:label": "감정을 가진다",
      "rdfs:domain": "Student",
      "rdfs:range": "Emotion"
    },

    // ✨ 여기에 새로운 감정 개념들 추가
    {
      "@id": "좌절",
      "@type": "Emotion",
      "rdfs:label": "좌절감",
      "rdfs:comment": "문제를 해결하지 못해 느끼는 감정"
    },
    {
      "@id": "집중",
      "@type": "Emotion",
      "rdfs:label": "집중 상태",
      "rdfs:comment": "학습에 몰입한 상태"
    },
    {
      "@id": "피로",
      "@type": "Emotion",
      "rdfs:label": "피로감",
      "rdfs:comment": "학습으로 인한 정신적 피로"
    },
    {
      "@id": "불안",
      "@type": "Emotion",
      "rdfs:label": "불안감",
      "rdfs:comment": "성취에 대한 걱정과 두려움"
    },
    {
      "@id": "기쁨",
      "@type": "Emotion",
      "rdfs:label": "기쁨",
      "rdfs:comment": "문제를 해결했을 때의 성취감"
    }
  ]
}
```

### Step 1-2: 검증 실행

```bash
python 03_validate_consistency.py
```

**예상 결과**:
```
✅ 온톨로지 구문 검증 통과
✅ 추론 규칙 검증 통과
✅ 문서 간 일관성 검증 통과
```

### Step 1-3: 새로운 추론 규칙 추가

**작업**: `02_minimal_inference.py`에 2개 규칙 추가

```python
self.rules = [
    # 기존 규칙 3개...
    {
        "id": "rule_4",
        "name": "불안 → 안정화",
        "condition": lambda facts: facts.get("emotion") == "불안",
        "action": "마음 안정화 필요"
    },
    {
        "id": "rule_5",
        "name": "기쁨 → 칭찬",
        "condition": lambda facts: facts.get("emotion") == "기쁨",
        "action": "칭찬 및 격려"
    }
]
```

### Step 1-4: 테스트 케이스 추가

```python
test_cases = [
    # 기존 케이스...
    {"student": "지수", "emotion": "불안"},
    {"student": "현수", "emotion": "기쁨"}
]
```

### Step 1-5: 실행 및 검증

```bash
# 추론 엔진 실행
python 02_minimal_inference.py

# 일관성 검증
python 03_validate_consistency.py
```

✅ **Phase 1 완료 기준**: 10개 개념, 5개 규칙이 정상 작동하고 일관성 검증 통과

---

## 📖 Phase 2: 복합 조건 추가

### Step 2-1: Learning 개념 추가

**목표**: 학습 활동(Learning)과 관계(isLearning) 추가

**작업**: `01_minimal_ontology.json`에 추가

```json
{
  "@id": "Learning",
  "@type": "rdfs:Class",
  "rdfs:label": "학습",
  "rdfs:comment": "학습 활동"
},
{
  "@id": "isLearning",
  "@type": "rdf:Property",
  "rdfs:label": "학습 중이다",
  "rdfs:domain": "Student",
  "rdfs:range": "Learning"
}
```

### Step 2-2: 복합 조건 규칙 추가

**목표**: 2개 이상의 조건을 조합한 규칙 작성

```python
{
    "id": "rule_6",
    "name": "좌절 + 반복 → 난이도 조정",
    "condition": lambda facts: (
        facts.get("emotion") == "좌절" and
        facts.get("retry_count", 0) >= 3
    ),
    "action": "난이도 하향 필요"
},
{
    "id": "rule_7",
    "name": "집중 + 학습중 → 칭찬",
    "condition": lambda facts: (
        facts.get("emotion") == "집중" and
        facts.get("is_learning") == True
    ),
    "action": "칭찬 및 격려"
}
```

### Step 2-3: 테스트 케이스

```python
test_cases = [
    {"student": "철수", "emotion": "좌절", "retry_count": 5},
    {"student": "영희", "emotion": "집중", "is_learning": True}
]
```

✅ **Phase 2 완료 기준**: 15개 개념, 7개 규칙 (복합 조건 2개 포함), 일관성 검증 통과

---

## 📖 Phase 3: 에이전트 연동 준비

### Step 3-1: Agent 개념 추가

**목표**: 에이전트 시스템과 통합 준비

```json
{
  "@id": "Agent",
  "@type": "rdfs:Class",
  "rdfs:label": "에이전트",
  "rdfs:comment": "학습 지원 AI 에이전트"
},
{
  "@id": "CurriculumAgent",
  "@type": "Agent",
  "rdfs:label": "커리큘럼 에이전트",
  "rdfs:comment": "학습 경로를 설계하는 에이전트"
},
{
  "@id": "providesGuidance",
  "@type": "rdf:Property",
  "rdfs:label": "가이드를 제공한다",
  "rdfs:domain": "Agent",
  "rdfs:range": "Student"
}
```

### Step 3-2: 에이전트 연동 규칙

```python
{
    "id": "rule_8",
    "name": "좌절 + 높은 난이도 → 커리큘럼 조정",
    "condition": lambda facts: (
        facts.get("emotion") == "좌절" and
        facts.get("difficulty_level", 0) > 7
    ),
    "action": "CurriculumAgent 호출: 난이도 재설정"
}
```

✅ **Phase 3 완료 기준**: 30개 개념, 10개 규칙, 에이전트 1개 연동 테스트 성공

---

## 📖 Phase 4: 다중 에이전트 통합

### Step 4-1: 5개 에이전트 추가

**목표**: 주요 에이전트 5개 통합 (Curriculum, Assessment, Content, Emotion, Interaction)

**작업**: `ontology/ontology.jsonld` 확장

```json
{
  "@id": "AssessmentAgent",
  "@type": "Agent",
  "rdfs:label": "평가 에이전트"
},
{
  "@id": "ContentAgent",
  "@type": "Agent",
  "rdfs:label": "콘텐츠 에이전트"
},
{
  "@id": "EmotionAgent",
  "@type": "Agent",
  "rdfs:label": "감정 에이전트"
},
{
  "@id": "InteractionAgent",
  "@type": "Agent",
  "rdfs:label": "상호작용 에이전트"
}
```

### Step 4-2: 에이전트 협업 규칙

```python
{
    "id": "rule_9",
    "name": "좌절 → 감정 에이전트 + 커리큘럼 에이전트",
    "condition": lambda facts: (
        facts.get("emotion") == "좌절" and
        facts.get("session_duration", 0) > 60
    ),
    "action": "EmotionAgent & CurriculumAgent 협업: 휴식 + 난이도 조정"
}
```

✅ **Phase 4 완료 기준**: 100개 개념, 50개 규칙, 에이전트 5개 협업 테스트 성공

---

## 📖 Phase 5: 전체 시스템 통합

### Step 5-1: 22개 에이전트 전체 통합

**목표**: 전체 에이전트 시스템 통합

**작업**: `agents/registry.yaml` 참조하여 모든 에이전트 온톨로지 정의

### Step 5-2: 지식 베이스 연동

**작업**: `knowledge/의사결정_지식.md` 내용을 추론 규칙으로 변환

### Step 5-3: 전체 시스템 검증

```bash
# 전체 시스템 일관성 검증
python workflows/run_document_loop.py --mode single_iteration

# 모든 문서 평가
python workflows/run_document_loop.py --mode loop_until_pass
```

✅ **Phase 5 완료 기준**: 모든 문서 50점 이상, 22개 에이전트 정상 작동, 프로덕션 배포 준비 완료

---

## 🔧 각 Phase 전환 시 체크리스트

각 Phase를 완료하고 다음 단계로 넘어가기 전에 **반드시** 다음을 확인하세요:

### ✅ 기술적 검증
- [ ] `python 03_validate_consistency.py` 통과
- [ ] 모든 테스트 케이스 실행 성공
- [ ] 새로운 개념이 온톨로지에 정의됨
- [ ] 새로운 규칙이 정상 작동
- [ ] 에러 메시지 없음

### ✅ 문서 검증
- [ ] README 업데이트 (새로운 개념, 규칙 설명)
- [ ] 예제 코드 추가
- [ ] 주석 작성 완료

### ✅ 성능 검증
- [ ] 추론 시간 측정 (<100ms)
- [ ] 메모리 사용량 확인
- [ ] 확장성 테스트

---

## 🚨 문제 해결

### Q: Phase 1에서 일관성 검증이 실패합니다.

**A**: 온톨로지에 감정 개념을 추가했는지 확인하세요.

```bash
# 온톨로지 내용 확인
cat 01_minimal_ontology.json | grep "좌절"
```

### Q: Phase 2 복합 조건이 작동하지 않습니다.

**A**: 테스트 케이스에 필요한 모든 필드가 있는지 확인하세요.

```python
# 잘못된 예
facts = {"student": "철수", "emotion": "좌절"}  # ❌ retry_count 없음

# 올바른 예
facts = {"student": "철수", "emotion": "좌절", "retry_count": 5}  # ✅
```

### Q: Phase 3 에이전트 연동이 안 됩니다.

**A**: 에이전트가 온톨로지에 정의되어 있는지 확인하세요.

```bash
python 03_validate_consistency.py
```

---

## 📚 참고 문서

- `examples/README_QUICKSTART.md` - 기본 사용법
- `docs/04-ONTOLOGY_SYSTEM_DESIGN.md` - 온톨로지 설계 원칙
- `docs/05-REASONING_ENGINE_SPEC.md` - 추론 엔진 상세 명세
- `workflows/README.md` - 전체 시스템 워크플로우

---

## 🎯 다음 단계

현재 Phase 0을 완료했으므로, **Phase 1**부터 시작하세요:

1. `01_minimal_ontology.json`에 5개 감정 추가
2. `02_minimal_inference.py`에 2개 규칙 추가
3. `python 03_validate_consistency.py` 실행
4. 모두 통과하면 Phase 2로 진행

**중요**: 각 Phase를 **완전히 검증**한 후에 다음 단계로 넘어가세요. 서두르지 마세요!

---

**문서 버전**: 1.0.0
**최종 업데이트**: 2025-10-30
**작성자**: Mathking Development Team
**난이도**: ⭐⭐ 중급 (Phase별 진행)
