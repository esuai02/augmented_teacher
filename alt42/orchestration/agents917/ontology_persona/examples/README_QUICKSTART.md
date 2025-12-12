# 🚀 Mathking 온톨로지 추론 엔진 - 빠른 시작 가이드

**온톨로지와 Python 추론 엔진이 처음이신 분을 위한 단계별 가이드입니다.**

---

## 📋 목차

1. [5분 안에 실행하기](#5분-안에-실행하기)
2. [무엇을 만들었나요?](#무엇을-만들었나요)
3. [코드 이해하기](#코드-이해하기)
4. [다음 단계: 확장하기](#다음-단계-확장하기)
5. [문서 일관성 검증](#문서-일관성-검증)

---

## 5분 안에 실행하기

### Step 1: 파일 확인

현재 `examples/` 폴더에 3개 파일이 있습니다:

```
examples/
├── 01_minimal_ontology.json    # 온톨로지 (개념 정의)
├── 02_minimal_inference.py     # 추론 엔진 (규칙 실행)
└── 03_validate_consistency.py  # 문서 일관성 검증 도구
```

### Step 2: 실행

```bash
# examples 폴더로 이동
cd examples

# Python 스크립트 실행
python 02_minimal_inference.py
```

### Step 3: 결과 확인

다음과 같은 출력이 나와야 합니다:

```
============================================================
Mathking 최소 온톨로지 추론 엔진 - Hello World
============================================================

✅ 온톨로지 로드 완료: 01_minimal_ontology.json
온톨로지 개념: ['Student', 'Emotion']

✅ 추론 규칙 3개 로드 완료

────────────────────────────────────────────────────────────
테스트 케이스 1
────────────────────────────────────────────────────────────

🔍 추론 시작
입력 사실: {'student': '철수', 'emotion': '좌절'}
  ✓ 규칙 적용: 좌절 → 격려 → 격려 필요

📊 결과:
  → 격려 필요

────────────────────────────────────────────────────────────
테스트 케이스 2
────────────────────────────────────────────────────────────

🔍 추론 시작
입력 사실: {'student': '영희', 'emotion': '집중'}
  ✓ 규칙 적용: 집중 → 학습 → 학습 진행

📊 결과:
  → 학습 진행

────────────────────────────────────────────────────────────
테스트 케이스 3
────────────────────────────────────────────────────────────

🔍 추론 시작
입력 사실: {'student': '민수', 'emotion': '피로'}
  ✓ 규칙 적용: 피로 → 휴식 → 휴식 필요

📊 결과:
  → 휴식 필요

============================================================
✅ 추론 완료
============================================================
```

**축하합니다! 🎉 첫 온톨로지 추론 엔진이 동작합니다!**

---

## 무엇을 만들었나요?

### 1. 온톨로지 (01_minimal_ontology.json)

**온톨로지**는 "개념들의 사전"입니다.

```json
{
  "Student": "학생",
  "Emotion": "감정",
  "hasEmotion": "학생은 감정을 가진다"
}
```

마치 사전처럼, 프로그램이 "학생"과 "감정"이 무엇인지 알게 됩니다.

### 2. 추론 엔진 (02_minimal_inference.py)

**추론 엔진**은 "IF-THEN 규칙"을 실행합니다.

```python
IF 학생의 감정 == "좌절"
THEN 결론 = "격려 필요"
```

### 3. 왜 이렇게 만들었나요?

| 전통적인 방법 | 온톨로지 기반 방법 |
|---------------|-------------------|
| `if emotion == "좌절": print("격려")` | 규칙을 외부 파일로 분리 |
| 코드 수정 = 재배포 필요 | 규칙 수정 = 파일만 변경 |
| 전문가 지식이 코드에 숨겨짐 | 전문가 지식이 명시적으로 문서화 |

---

## 코드 이해하기

### 온톨로지 파일 (JSON-LD 형식)

```json
{
  "@context": {
    "@vocab": "http://mathking.kr/ontology#"
  },
  "@graph": [
    {
      "@id": "Student",           // 개념 ID
      "@type": "rdfs:Class",      // 개념 타입
      "rdfs:label": "학생",        // 한글 이름
      "rdfs:comment": "학습하는 사람"  // 설명
    }
  ]
}
```

**핵심**:
- `@id`: 고유 식별자 (예: `Student`)
- `@type`: 개념인지, 관계인지 구분
- `rdfs:label`: 사람이 읽을 수 있는 이름

### 추론 엔진 (Python)

```python
class MinimalInferenceEngine:
    def __init__(self):
        self.rules = [
            {
                "condition": lambda facts: facts["emotion"] == "좌절",
                "action": "격려 필요"
            }
        ]

    def infer(self, facts):
        conclusions = []
        for rule in self.rules:
            if rule["condition"](facts):  # 조건 확인
                conclusions.append(rule["action"])  # 결론 추가
        return conclusions
```

**핵심**:
- `condition`: IF 부분 (조건)
- `action`: THEN 부분 (결론)
- `lambda`: Python의 짧은 함수 정의

---

## 다음 단계: 확장하기

### Level 1: 새로운 감정 추가하기

**목표**: "불안" 감정 추가

#### 1단계: 추론 규칙 추가

`02_minimal_inference.py` 수정:

```python
self.rules = [
    # 기존 규칙 3개...
    {
        "id": "rule_4",
        "name": "불안 → 안정화",
        "condition": lambda facts: facts.get("emotion") == "불안",
        "action": "마음 안정화 필요"
    }
]
```

#### 2단계: 테스트 케이스 추가

```python
test_cases = [
    # 기존 케이스...
    {"student": "지수", "emotion": "불안"}
]
```

#### 3단계: 실행

```bash
python 02_minimal_inference.py
```

**예상 출력**:
```
🔍 추론 시작
입력 사실: {'student': '지수', 'emotion': '불안'}
  ✓ 규칙 적용: 불안 → 안정화 → 마음 안정화 필요
```

---

### Level 2: 복합 조건 추가하기

**목표**: "좌절 + 3번 이상 시도" → "난이도 하향"

#### 1단계: 규칙 수정

```python
{
    "id": "rule_5",
    "name": "좌절 + 반복 → 난이도 조정",
    "condition": lambda facts: (
        facts.get("emotion") == "좌절" and
        facts.get("retry_count", 0) >= 3
    ),
    "action": "난이도 하향 필요"
}
```

#### 2단계: 테스트

```python
test_cases = [
    {"student": "철수", "emotion": "좌절", "retry_count": 5}
]
```

---

### Level 3: 온톨로지 확장하기

**목표**: "Learning" 개념 추가

#### 1단계: 온톨로지 수정 (`01_minimal_ontology.json`)

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

#### 2단계: 추론 규칙 추가

```python
{
    "id": "rule_6",
    "name": "집중 + 학습중 → 칭찬",
    "condition": lambda facts: (
        facts.get("emotion") == "집중" and
        facts.get("is_learning") == True
    ),
    "action": "칭찬 및 격려"
}
```

---

## 문서 일관성 검증

### 자동 검증 도구

`examples/` 폴더에 검증 스크립트를 제공합니다:

```bash
python 03_validate_consistency.py
```

### 검증 항목

1. **온톨로지 구문 검증**
   - JSON 형식 유효성
   - 필수 필드 존재 여부

2. **추론 규칙 검증**
   - 규칙 ID 중복 확인
   - 조건 함수 유효성

3. **문서 일관성 검증**
   - 온톨로지 개념 ↔ 추론 규칙 매칭
   - 문서 간 용어 일치도

### 실행 결과 예시

```
✅ 온톨로지 구문 검증: PASS
✅ 추론 규칙 검증: PASS
⚠️ 문서 일관성 경고:
  - 규칙에서 사용한 "불안" 개념이 온톨로지에 없음
  - 권장: Anxiety 개념을 온톨로지에 추가
```

---

## 🎯 로드맵: 작은 것부터 시작

```
✅ Phase 0 (현재)
   └── 개념 3개, 규칙 3개 (Hello World)

📍 Phase 1 (다음 단계)
   ├── 개념 5개 추가
   ├── 규칙 10개로 확장
   └── 복합 조건 3개

📍 Phase 2
   ├── 에이전트 1개 연동
   ├── 실제 학습 데이터 연결
   └── 로그 시스템 추가

📍 Phase 3
   ├── 에이전트 5개 연동
   ├── 22개 에이전트 전체 통합
   └── 프로덕션 배포
```

---

## 📚 추가 학습 자료

### 온톨로지 기초

1. **JSON-LD**: https://json-ld.org/
2. **RDF Schema**: https://www.w3.org/TR/rdf-schema/
3. **온톨로지란?**: Wikipedia - Ontology (information science)

### Python 추론

1. **Lambda 함수**: https://docs.python.org/3/tutorial/controlflow.html#lambda-expressions
2. **Pyke (Python 추론 라이브러리)**: https://pyke.sourceforge.net/
3. **Owlready2 (고급 온톨로지)**: https://owlready2.readthedocs.io/

### Mathking 특화

1. `docs/03-KNOWLEDGE_BASE_ARCHITECTURE.md` - 지식 베이스 구조
2. `docs/04-ONTOLOGY_SYSTEM_DESIGN.md` - 온톨로지 설계
3. `docs/05-REASONING_ENGINE_SPEC.md` - 추론 엔진 상세

---

## 🐛 문제 해결

### Q: "ModuleNotFoundError: No module named 'json'"

**A**: Python 3.x가 설치되어 있는지 확인하세요.

```bash
python --version  # Python 3.7 이상 필요
```

### Q: "FileNotFoundError: 01_minimal_ontology.json"

**A**: `examples/` 폴더에서 실행하고 있는지 확인하세요.

```bash
cd examples
python 02_minimal_inference.py
```

### Q: 규칙이 적용되지 않아요

**A**: `facts` 딕셔너리의 키 이름을 확인하세요.

```python
# 잘못된 예
facts = {"감정": "좌절"}  # ❌ 한글 키

# 올바른 예
facts = {"emotion": "좌절"}  # ✅ 영문 키
```

---

## 💬 질문하기

**상충되는 부분이나 이해가 안 되는 부분이 있으면 언제든지 질문해주세요!**

예시 질문:
- "왜 JSON-LD를 사용하나요?"
- "lambda 함수 대신 일반 함수를 써도 되나요?"
- "온톨로지에 새 개념을 추가하려면 어떻게 하나요?"

---

**문서 버전**: 1.0.0
**최종 업데이트**: 2025-10-30
**작성자**: Mathking Development Team
**난이도**: ⭐ 초급 (온톨로지 처음 배우는 분)
