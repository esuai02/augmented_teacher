# Phase 1 실행 계획 - 온톨로지 기반 추론 시스템 전환

**시작일**: 2025-11-01
**목표 완료일**: 2025-11-15 (2주)
**상태**: 준비 중
**버전**: 1.0

---

## 📋 목차

1. [목표 및 범위](#목표-및-범위)
2. [DRY RUN (사전 검증)](#dry-run-사전-검증)
3. [단계별 실행 계획](#단계별-실행-계획)
4. [검증 및 테스트](#검증-및-테스트)
5. [롤백 계획](#롤백-계획)
6. [완료 기준](#완료-기준)

---

## 목표 및 범위

### 🎯 Phase 1 목표

**핵심 전환**: 하드코딩된 규칙 → 온톨로지 기반 동적 추론

```yaml
before_phase1:
  ontology_file: "존재하지만 사용되지 않음"
  rules: "PHP 코드에 하드코딩"
  scalability: "새 규칙 추가 시 코드 수정 필요"
  maintainability: "낮음"

after_phase1:
  ontology_file: "시스템의 단일 진실원(SSOT)"
  rules: "JSON-LD 파일에서 동적 로드"
  scalability: "코드 수정 없이 규칙 추가 가능"
  maintainability: "높음"
```

### 📊 확장 범위

| 항목 | Phase 0 (현재) | Phase 1 (목표) | 증가율 |
|------|----------------|----------------|--------|
| **개념** | 3개 | 10개 | +233% |
| **규칙** | 3개 | 10개 | +233% |
| **감정 상태** | 3개 | 5개 | +67% |
| **추론 방식** | 하드코딩 | 온톨로지 기반 | 질적 전환 |

### 🚫 범위 외 (Phase 2 이후)

- ❌ 복합 조건 (AND, OR 논리)
- ❌ 에이전트 연동
- ❌ LLM 통합
- ❌ Moodle DB 연동

---

## DRY RUN (사전 검증)

### 🧪 DRY RUN 목적

실제 코드 수정 전에 다음을 검증:
1. 온톨로지 구조의 타당성
2. 추론 엔진 로직의 정확성
3. 성능 영향 예측
4. 잠재적 문제점 사전 발견

### 📝 DRY RUN Step 1: 온톨로지 구조 검증

#### 1.1 현재 온톨로지 파일 분석

```bash
# 현재 파일 확인
cat examples/01_minimal_ontology.json

# 예상 출력:
# - 3개 클래스: Student, Emotion, hasEmotion
# - JSON-LD 구조 올바름
# - 확장 가능 구조
```

#### 1.2 확장된 온톨로지 설계 (종이 작업)

```json
// 설계안 (실제 파일 수정 전 검토)
{
  "@context": { ... },
  "@graph": [
    // 기존 3개 개념
    {"@id": "Student", "@type": "rdfs:Class"},
    {"@id": "Emotion", "@type": "rdfs:Class"},
    {"@id": "hasEmotion", "@type": "rdf:Property"},

    // 신규 5개 감정 인스턴스
    {"@id": "Frustrated", "@type": "Emotion"},
    {"@id": "Focused", "@type": "Emotion"},
    {"@id": "Tired", "@type": "Emotion"},
    {"@id": "Anxious", "@type": "Emotion"},
    {"@id": "Happy", "@type": "Emotion"},

    // 신규 규칙 개념
    {"@id": "InferenceRule", "@type": "rdfs:Class"},
    {"@id": "Condition", "@type": "rdfs:Class"},
    {"@id": "Action", "@type": "rdfs:Class"},

    // 10개 규칙 정의
    {"@id": "rule_frustrated", "@type": "InferenceRule", ...},
    {"@id": "rule_focused", "@type": "InferenceRule", ...},
    // ... 8개 더
  ]
}
```

**검증 질문**:
- ✅ JSON-LD 구조가 유효한가?
- ✅ W3C 표준을 준수하는가?
- ✅ 확장 가능한 구조인가?
- ✅ 기존 시스템과 호환되는가?

#### 1.3 예상 문제점 및 해결책

| 문제 | 가능성 | 영향 | 해결책 |
|------|--------|------|--------|
| JSON 파싱 오류 | 중간 | 높음 | 파일 저장 전 JSON 검증 |
| 한글 인코딩 문제 | 높음 | 중간 | UTF-8 명시, 테스트 |
| 규칙 ID 중복 | 낮음 | 높음 | 네이밍 컨벤션 수립 |
| 성능 저하 | 낮음 | 중간 | 벤치마크 측정 |

### 📝 DRY RUN Step 2: 추론 엔진 로직 검증

#### 2.1 현재 추론 흐름 분석

```python
# 현재 inference_lab_v2.php의 로직 (Python 코드 내장)
rules = [
    {"condition": {"emotion": "좌절"}, "conclusion": "격려 필요"},
    # ... 하드코딩된 규칙
]

# 단순 매칭
for rule in rules:
    if facts.get("emotion") == rule["condition"]["emotion"]:
        conclusions.append(rule["conclusion"])
```

**문제점**:
- 규칙이 PHP 코드에 하드코딩
- 새 규칙 추가 시 코드 수정 필요
- 온톨로지 파일을 사용하지 않음

#### 2.2 신규 추론 흐름 설계

```python
# 신규 로직 (설계안)
import json

# 1. 온톨로지 로드
with open('01_minimal_ontology.json', 'r', encoding='utf-8') as f:
    ontology = json.load(f)

# 2. 규칙 추출
rules = extract_rules_from_ontology(ontology)

# 3. 추론 실행
for rule in rules:
    condition = rule['condition']
    if evaluate_condition(condition, facts):
        conclusions.append({
            'rule_id': rule['@id'],
            'conclusion': rule['conclusion']
        })
```

**신규 함수 필요**:
- `extract_rules_from_ontology()`: JSON-LD에서 규칙 추출
- `evaluate_condition()`: 조건 평가 (단순 매칭)

#### 2.3 성능 영향 예측

```yaml
current_performance:
  ontology_load: "0ms (파일 읽지 않음)"
  rule_matching: "<1ms (3개 규칙)"
  total: "<1ms"

predicted_performance:
  ontology_load: "~5ms (JSON 파싱)"
  rule_matching: "<2ms (10개 규칙)"
  total: "<10ms"

acceptable_threshold: "<100ms"
impact: "무시 가능"
```

### 📝 DRY RUN Step 3: 통합 시나리오 시뮬레이션

#### 시나리오 1: 기본 추론 (좌절 감정)

```yaml
input:
  student: "철수"
  emotion: "좌절"

expected_flow:
  1. PHP가 Python 스크립트 실행
  2. Python이 ontology.json 로드
  3. 규칙 추출: rule_frustrated 발견
  4. 조건 평가: emotion == "좌절" → True
  5. 결론 반환: "격려 필요"
  6. PHP가 JSON 응답 생성

expected_output:
  success: true
  applied_rules: ["rule_frustrated"]
  conclusion: "격려 필요"
```

#### 시나리오 2: 새 감정 (불안)

```yaml
input:
  student: "영희"
  emotion: "불안"

expected_flow:
  1. 온톨로지 로드
  2. 규칙 추출: rule_anxious 발견
  3. 조건 평가: emotion == "불안" → True
  4. 결론 반환: "마음 안정화 필요"

expected_output:
  success: true
  applied_rules: ["rule_anxious"]
  conclusion: "마음 안정화 필요"
```

#### 시나리오 3: 규칙 없음

```yaml
input:
  student: "민수"
  emotion: "분노"  # 온톨로지에 없는 감정

expected_flow:
  1. 온톨로지 로드
  2. 규칙 추출: 해당 감정에 대한 규칙 없음
  3. 기본 메시지 반환

expected_output:
  success: true
  applied_rules: []
  conclusion: "적용 가능한 규칙 없음"
```

### ✅ DRY RUN 체크리스트

실제 코드 수정 전 모든 항목 확인:

- [ ] 온톨로지 구조 설계 완료 및 검토
- [ ] JSON-LD 유효성 검증 방법 확립
- [ ] 추론 엔진 로직 설계 완료
- [ ] 성능 영향 예측 및 허용 범위 확인
- [ ] 테스트 시나리오 3개 이상 준비
- [ ] 롤백 계획 수립
- [ ] 백업 생성 절차 확인

---

## 단계별 실행 계획

### 📅 Week 1: 온톨로지 확장 및 엔진 리팩토링

#### Day 1-2: 온톨로지 파일 확장

**작업 1.1: 기존 파일 백업**

```bash
# 백업 생성
cp examples/01_minimal_ontology.json examples/01_minimal_ontology.json.backup_20251101

# Git 커밋 (롤백 지점)
git add examples/01_minimal_ontology.json
git commit -m "백업: Phase 1 시작 전 온톨로지 원본"
```

**작업 1.2: 감정 인스턴스 추가**

```json
// examples/01_minimal_ontology.json에 추가
{
  "@id": "Frustrated",
  "@type": "Emotion",
  "rdfs:label": "좌절",
  "rdfs:comment": "문제를 해결하지 못해 느끼는 감정",
  "emotionIntensity": "medium"
},
{
  "@id": "Focused",
  "@type": "Emotion",
  "rdfs:label": "집중",
  "rdfs:comment": "학습에 몰입한 상태",
  "emotionIntensity": "positive"
},
{
  "@id": "Tired",
  "@type": "Emotion",
  "rdfs:label": "피로",
  "rdfs:comment": "학습으로 인한 정신적 피로",
  "emotionIntensity": "low"
},
{
  "@id": "Anxious",
  "@type": "Emotion",
  "rdfs:label": "불안",
  "rdfs:comment": "성취에 대한 걱정과 두려움",
  "emotionIntensity": "medium"
},
{
  "@id": "Happy",
  "@type": "Emotion",
  "rdfs:label": "기쁨",
  "rdfs:comment": "문제를 해결했을 때의 성취감",
  "emotionIntensity": "high"
}
```

**작업 1.3: 규칙 개념 및 인스턴스 추가**

```json
// 규칙 클래스 정의
{
  "@id": "InferenceRule",
  "@type": "rdfs:Class",
  "rdfs:label": "추론 규칙",
  "rdfs:comment": "조건과 결론으로 구성된 IF-THEN 규칙"
},

// 10개 규칙 인스턴스
{
  "@id": "rule_frustrated",
  "@type": "InferenceRule",
  "ruleName": "좌절 → 격려",
  "condition": {
    "@type": "Condition",
    "emotionEquals": "Frustrated"
  },
  "conclusion": "격려 필요",
  "priority": 1.0
},
{
  "@id": "rule_focused",
  "@type": "InferenceRule",
  "ruleName": "집중 → 학습",
  "condition": {
    "@type": "Condition",
    "emotionEquals": "Focused"
  },
  "conclusion": "학습 진행",
  "priority": 1.0
},
{
  "@id": "rule_tired",
  "@type": "InferenceRule",
  "ruleName": "피로 → 휴식",
  "condition": {
    "@type": "Condition",
    "emotionEquals": "Tired"
  },
  "conclusion": "휴식 필요",
  "priority": 1.0
},
{
  "@id": "rule_anxious",
  "@type": "InferenceRule",
  "ruleName": "불안 → 안정화",
  "condition": {
    "@type": "Condition",
    "emotionEquals": "Anxious"
  },
  "conclusion": "마음 안정화 필요",
  "priority": 0.9
},
{
  "@id": "rule_happy",
  "@type": "InferenceRule",
  "ruleName": "기쁨 → 칭찬",
  "condition": {
    "@type": "Condition",
    "emotionEquals": "Happy"
  },
  "conclusion": "칭찬 및 격려",
  "priority": 0.8
}
// ... 5개 더 추가 (예: 복습 권장, 난이도 조정 등)
```

**검증**:
```bash
# JSON 유효성 검사
python -m json.tool examples/01_minimal_ontology.json > /dev/null
echo $?  # 0이면 성공

# 파일 인코딩 확인
file examples/01_minimal_ontology.json  # UTF-8 확인
```

#### Day 3-4: Python 추론 엔진 리팩토링

**작업 2.1: 온톨로지 로더 구현**

```python
# examples/ontology_loader.py (신규 파일)
import json
from typing import Dict, List, Any

class OntologyLoader:
    """온톨로지 파일 로더"""

    def __init__(self, ontology_path: str):
        self.ontology_path = ontology_path
        self.ontology = None

    def load(self) -> Dict[str, Any]:
        """온톨로지 파일 로드"""
        with open(self.ontology_path, 'r', encoding='utf-8') as f:
            self.ontology = json.load(f)
        return self.ontology

    def extract_rules(self) -> List[Dict[str, Any]]:
        """온톨로지에서 InferenceRule 추출"""
        rules = []
        graph = self.ontology.get('@graph', [])

        for item in graph:
            if item.get('@type') == 'InferenceRule':
                rules.append({
                    'id': item['@id'],
                    'name': item['ruleName'],
                    'condition': item['condition'],
                    'conclusion': item['conclusion'],
                    'priority': item.get('priority', 1.0)
                })

        # 우선순위 정렬
        rules.sort(key=lambda x: x['priority'], reverse=True)
        return rules

    def extract_emotions(self) -> List[str]:
        """온톨로지에서 감정 목록 추출"""
        emotions = []
        graph = self.ontology.get('@graph', [])

        for item in graph:
            if item.get('@type') == 'Emotion' and '@id' in item:
                emotions.append(item['@id'])

        return emotions
```

**작업 2.2: 추론 엔진 업데이트**

```python
# examples/inference_engine.py (신규 파일)
from typing import Dict, List, Any
from ontology_loader import OntologyLoader

class InferenceEngine:
    """온톨로지 기반 추론 엔진"""

    def __init__(self, ontology_path: str):
        self.loader = OntologyLoader(ontology_path)
        self.ontology = self.loader.load()
        self.rules = self.loader.extract_rules()

    def infer(self, facts: Dict[str, Any]) -> Dict[str, Any]:
        """
        추론 실행

        Args:
            facts: 입력 사실 (예: {"student": "철수", "emotion": "Frustrated"})

        Returns:
            추론 결과 (적용된 규칙, 결론 등)
        """
        applied_rules = []
        conclusions = []

        for rule in self.rules:
            if self._evaluate_condition(rule['condition'], facts):
                applied_rules.append({
                    'rule_id': rule['id'],
                    'rule_name': rule['name']
                })
                conclusions.append(rule['conclusion'])

        return {
            'applied_rules': applied_rules,
            'conclusions': conclusions,
            'input': facts
        }

    def _evaluate_condition(self, condition: Dict[str, Any], facts: Dict[str, Any]) -> bool:
        """조건 평가 (단순 매칭)"""
        # Phase 1에서는 emotionEquals만 지원
        if 'emotionEquals' in condition:
            return facts.get('emotion') == condition['emotionEquals']

        return False
```

**작업 2.3: 테스트 스크립트 작성**

```python
# examples/test_phase1_engine.py (신규 파일)
from inference_engine import InferenceEngine

def test_basic_inference():
    """기본 추론 테스트"""
    engine = InferenceEngine('01_minimal_ontology.json')

    # 테스트 1: 좌절
    result = engine.infer({"student": "철수", "emotion": "Frustrated"})
    assert len(result['applied_rules']) == 1
    assert result['conclusions'][0] == "격려 필요"
    print("✅ 테스트 1 통과: 좌절 → 격려")

    # 테스트 2: 불안
    result = engine.infer({"student": "영희", "emotion": "Anxious"})
    assert len(result['applied_rules']) == 1
    assert result['conclusions'][0] == "마음 안정화 필요"
    print("✅ 테스트 2 통과: 불안 → 안정화")

    # 테스트 3: 규칙 없음
    result = engine.infer({"student": "민수", "emotion": "Unknown"})
    assert len(result['applied_rules']) == 0
    print("✅ 테스트 3 통과: 규칙 없음")

    print("\n🎉 모든 테스트 통과!")

if __name__ == "__main__":
    test_basic_inference()
```

**실행 및 검증**:
```bash
cd /mnt/c/1\ Project/augmented_teacher/alt42/ontology_brain/examples
python test_phase1_engine.py
```

#### Day 5-6: PHP 웹 인터페이스 통합

**작업 3.1: inference_lab_v3.php 생성**

```php
<?php
// inference_lab_v3.php - 온톨로지 기반 버전

// ... (기존 코드 유지)

// Python 스크립트 생성 (온톨로지 기반)
$pythonCode = <<<PYTHON
import sys
import json
sys.path.append('{$examplesDir}')

from inference_engine import InferenceEngine

try:
    # 온톨로지 기반 추론 엔진 초기화
    engine = InferenceEngine('{$examplesDir}/01_minimal_ontology.json')

    # 추론 실행
    facts = {
        "student": "{$student}",
        "emotion": "{$emotion}"
    }

    result = engine.infer(facts)

    # 결과 포맷팅
    print("="*60)
    print(f"📥 입력 사실: {facts}")
    print("="*60)
    print()

    for rule_info in result['applied_rules']:
        print(f"✓ 규칙 적용: {rule_info['rule_id']} ({rule_info['rule_name']})")

    print()
    print("="*60)
    print("📊 추론 결과:")
    if result['conclusions']:
        for conclusion in result['conclusions']:
            print(f"  → {conclusion}")
    else:
        print("  (적용 가능한 규칙 없음)")
    print("="*60)

    sys.exit(0)

except Exception as e:
    print(f"오류: {e}", file=sys.stderr)
    import traceback
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
PYTHON;

// ... (기존 proc_open 로직 유지)
```

**작업 3.2: 웹 UI 업데이트**

```html
<!-- 감정 선택 옵션 확장 -->
<select id="emotion" name="emotion" required>
    <option value="">선택하세요</option>
    <option value="Frustrated">좌절 (Frustrated)</option>
    <option value="Focused">집중 (Focused)</option>
    <option value="Tired">피로 (Tired)</option>
    <option value="Anxious">불안 (Anxious)</option>
    <option value="Happy">기쁨 (Happy)</option>
</select>

<!-- 예제 버튼 업데이트 -->
<div class="example-btn" onclick="setExample('철수', 'Frustrated')">
    😰 좌절
</div>
<div class="example-btn" onclick="setExample('영희', 'Focused')">
    😊 집중
</div>
<div class="example-btn" onclick="setExample('민수', 'Tired')">
    😴 피로
</div>
<div class="example-btn" onclick="setExample('지수', 'Anxious')">
    😟 불안
</div>
<div class="example-btn" onclick="setExample('현수', 'Happy')">
    😄 기쁨
</div>
```

### 📅 Week 2: 테스트, 검증 및 문서화

#### Day 7-8: E2E 테스트

**작업 4.1: Playwright 테스트 업데이트**

```javascript
// tests/test_phase1_complete.js
const { chromium } = require('@playwright/test');

(async () => {
  console.log('\n🧪 Phase 1 완전 테스트 시작...\n');

  const browser = await chromium.launch({ headless: false, slowMo: 500 });
  const page = await browser.newPage();

  try {
    const url = 'https://mathking.kr/.../inference_lab_v3.php';
    await page.goto(url, { waitUntil: 'networkidle' });

    // 5가지 감정 모두 테스트
    const emotions = [
      { name: '좌절', value: 'Frustrated', expected: '격려 필요' },
      { name: '집중', value: 'Focused', expected: '학습 진행' },
      { name: '피로', value: 'Tired', expected: '휴식 필요' },
      { name: '불안', value: 'Anxious', expected: '안정화' },
      { name: '기쁨', value: 'Happy', expected: '칭찬' }
    ];

    for (const emotion of emotions) {
      console.log(`\n🧪 테스트: ${emotion.name}`);

      await page.selectOption('#emotion', emotion.value);
      await page.click('button:has-text("추론 실행")');
      await page.waitForTimeout(3000);

      const result = await page.locator('#resultContent').textContent();

      if (result.includes(emotion.expected)) {
        console.log(`  ✅ ${emotion.name} → ${emotion.expected} 검증 성공`);
      } else {
        console.error(`  ❌ ${emotion.name} 실패 (예상: ${emotion.expected})`);
      }
    }

    console.log('\n✅ Phase 1 테스트 완료!\n');

  } catch (error) {
    console.error('\n❌ 오류:', error.message);
  } finally {
    await browser.close();
  }
})();
```

#### Day 9-10: 성능 측정 및 최적화

**작업 5.1: 성능 벤치마크**

```python
# examples/benchmark_phase1.py
import time
from inference_engine import InferenceEngine

def benchmark():
    """성능 측정"""
    engine = InferenceEngine('01_minimal_ontology.json')

    test_cases = [
        {"student": f"학생{i}", "emotion": "Frustrated"}
        for i in range(100)
    ]

    start = time.time()
    for facts in test_cases:
        engine.infer(facts)
    end = time.time()

    total_time = (end - start) * 1000  # ms
    avg_time = total_time / len(test_cases)

    print(f"총 실행 시간: {total_time:.2f}ms")
    print(f"평균 추론 시간: {avg_time:.2f}ms/건")

    assert avg_time < 10, f"성능 기준 미달: {avg_time}ms > 10ms"
    print("✅ 성능 기준 통과 (<10ms)")

if __name__ == "__main__":
    benchmark()
```

#### Day 11-12: 문서화

**작업 6.1: README 업데이트**

```markdown
# Phase 1 완료 - 온톨로지 기반 추론 시스템

## 변경 사항

### 1. 온톨로지 확장
- 감정 인스턴스: 3개 → 5개 (+67%)
- 추론 규칙: 3개 → 10개 (+233%)
- 개념: 3개 → 10개

### 2. 추론 엔진 리팩토링
- 하드코딩 규칙 → 온톨로지 동적 로드
- 신규 모듈: `ontology_loader.py`, `inference_engine.py`

### 3. 웹 인터페이스
- 감정 선택 5개로 확장
- 예제 버튼 5개 추가

## 사용 방법

1. 웹 인터페이스: https://mathking.kr/.../inference_lab_v3.php
2. Python 직접 실행:
   ```bash
   cd examples
   python -c "from inference_engine import InferenceEngine; ..."
   ```

## 성능
- 평균 추론 시간: <10ms
- 온톨로지 로드: ~5ms
```

**작업 6.2: 변경 이력 기록**

```markdown
# CHANGELOG.md

## [Phase 1] - 2025-11-15

### Added
- 온톨로지 기반 추론 엔진 (`inference_engine.py`)
- 온톨로지 로더 (`ontology_loader.py`)
- 5개 감정 인스턴스 (Frustrated, Focused, Tired, Anxious, Happy)
- 10개 추론 규칙

### Changed
- `inference_lab_v2.php` → `inference_lab_v3.php` (온톨로지 기반)
- 웹 UI: 감정 선택 3개 → 5개

### Performance
- 추론 시간: <10ms (목표 <100ms)

### Testing
- E2E 테스트: 5개 감정 시나리오
- 성능 벤치마크: 100건 평균 측정
```

#### Day 13-14: 최종 검증 및 배포

**작업 7.1: 체크리스트 검증**

```yaml
pre_deployment_checklist:
  code:
    - [ ] 모든 Python 테스트 통과
    - [ ] E2E 테스트 5개 시나리오 통과
    - [ ] 성능 벤치마크 통과 (<10ms)
    - [ ] 에러 처리 완비

  documentation:
    - [ ] README 업데이트
    - [ ] CHANGELOG 작성
    - [ ] API 문서화 (함수 docstring)

  deployment:
    - [ ] 프로덕션 서버 백업
    - [ ] 롤백 계획 준비
    - [ ] 배포 실행
    - [ ] 배포 후 검증
```

---

## 검증 및 테스트

### 🧪 테스트 레벨

#### Level 1: 단위 테스트 (Unit Tests)

```bash
# Python 모듈 테스트
cd examples
python test_phase1_engine.py

# 예상 결과:
# ✅ 테스트 1 통과: 좌절 → 격려
# ✅ 테스트 2 통과: 불안 → 안정화
# ✅ 테스트 3 통과: 규칙 없음
# 🎉 모든 테스트 통과!
```

#### Level 2: 통합 테스트 (Integration Tests)

```bash
# PHP + Python 통합 테스트
node tests/test_phase1_complete.js

# 예상 결과:
# 🧪 테스트: 좌절
#   ✅ 좌절 → 격려 필요 검증 성공
# ... (5개 모두)
# ✅ Phase 1 테스트 완료!
```

#### Level 3: 성능 테스트 (Performance Tests)

```bash
# 성능 벤치마크
cd examples
python benchmark_phase1.py

# 예상 결과:
# 총 실행 시간: 850.23ms
# 평균 추론 시간: 8.50ms/건
# ✅ 성능 기준 통과 (<10ms)
```

#### Level 4: 사용자 수용 테스트 (UAT)

```yaml
manual_testing:
  - 웹 브라우저에서 5가지 감정 모두 테스트
  - 각 감정에 대한 적절한 결과 확인
  - UI/UX 사용성 검증
  - 에러 메시지 명확성 확인
```

### ✅ 완료 기준 (Definition of Done)

Phase 1은 다음 조건을 **모두** 만족해야 완료:

```yaml
technical_criteria:
  - [ ] 온톨로지 파일에 10개 개념, 10개 규칙 정의
  - [ ] Python 추론 엔진이 온톨로지 동적 로드
  - [ ] 5가지 감정 정확하게 추론
  - [ ] 평균 추론 시간 <10ms
  - [ ] 모든 테스트 통과 (단위, 통합, 성능, E2E)

functional_criteria:
  - [ ] 웹 인터페이스에서 5가지 감정 선택 가능
  - [ ] 각 감정에 대한 적절한 결과 표시
  - [ ] 적용된 규칙 ID 표시
  - [ ] 에러 발생 시 명확한 메시지

documentation_criteria:
  - [ ] README 업데이트 (사용 방법, 변경 사항)
  - [ ] CHANGELOG 작성
  - [ ] 코드 주석 완비 (함수 docstring)
  - [ ] Phase 1 완료 보고서 작성

deployment_criteria:
  - [ ] 프로덕션 배포 성공
  - [ ] 배포 후 검증 완료
  - [ ] 롤백 계획 문서화
```

---

## 롤백 계획

### 🔄 롤백 시나리오

#### 시나리오 1: 온톨로지 파일 오류

**증상**: JSON 파싱 실패, 잘못된 구조

**롤백 절차**:
```bash
# 백업에서 복원
cp examples/01_minimal_ontology.json.backup_20251101 examples/01_minimal_ontology.json

# Git에서 복원 (백업이 없는 경우)
git checkout HEAD~1 examples/01_minimal_ontology.json

# 검증
python -m json.tool examples/01_minimal_ontology.json
```

#### 시나리오 2: Python 엔진 오류

**증상**: 추론 실패, 예외 발생

**롤백 절차**:
```bash
# 새 파일 삭제
rm examples/ontology_loader.py
rm examples/inference_engine.py

# 기존 버전으로 복원
git checkout HEAD~1 examples/

# inference_lab_v2.php로 되돌리기
cp inference_lab_v2.php inference_lab.php
```

#### 시나리오 3: 성능 저하

**증상**: 추론 시간 >100ms

**완화 조치**:
```python
# 캐싱 추가
class InferenceEngine:
    def __init__(self, ontology_path: str):
        self._cache = {}
        # ...

    def infer(self, facts: Dict[str, Any]):
        cache_key = json.dumps(facts, sort_keys=True)
        if cache_key in self._cache:
            return self._cache[cache_key]

        result = self._do_inference(facts)
        self._cache[cache_key] = result
        return result
```

### 🚨 긴급 롤백 (Production Hotfix)

```bash
# 1. 즉시 이전 버전으로 교체
cp inference_lab_v2.php.backup inference_lab.php

# 2. 캐시 클리어 (있는 경우)
# ...

# 3. 서비스 재시작 (필요한 경우)
# ...

# 4. 검증
curl https://mathking.kr/.../inference_lab.php
```

---

## 완료 기준

### ✅ Phase 1 완료 체크리스트

```yaml
week_1:
  day_1_2:
    - [ ] 온톨로지 파일 백업
    - [ ] 5개 감정 인스턴스 추가
    - [ ] 10개 규칙 정의
    - [ ] JSON 유효성 검증

  day_3_4:
    - [ ] ontology_loader.py 작성
    - [ ] inference_engine.py 작성
    - [ ] 단위 테스트 작성 및 통과

  day_5_6:
    - [ ] inference_lab_v3.php 작성
    - [ ] 웹 UI 업데이트 (5개 감정)
    - [ ] 로컬 테스트 성공

week_2:
  day_7_8:
    - [ ] E2E 테스트 작성
    - [ ] 5개 시나리오 모두 통과

  day_9_10:
    - [ ] 성능 벤치마크 실행
    - [ ] <10ms 목표 달성
    - [ ] 최적화 (필요한 경우)

  day_11_12:
    - [ ] README 업데이트
    - [ ] CHANGELOG 작성
    - [ ] 코드 주석 완비

  day_13_14:
    - [ ] 최종 검증 체크리스트 완료
    - [ ] 프로덕션 배포
    - [ ] 배포 후 검증
    - [ ] Phase 1 완료 보고서
```

### 📊 성공 지표 (KPI)

```yaml
technical_kpis:
  ontology_coverage:
    target: "10개 개념, 10개 규칙"
    actual: "측정 예정"

  inference_accuracy:
    target: "100% (5/5 감정 정확)"
    actual: "측정 예정"

  performance:
    target: "<10ms 평균 추론 시간"
    actual: "측정 예정"

  test_coverage:
    target: "100% (모든 감정 테스트)"
    actual: "측정 예정"

functional_kpis:
  usability:
    target: "5개 감정 모두 선택 가능"
    actual: "측정 예정"

  reliability:
    target: "에러 없이 작동"
    actual: "측정 예정"
```

---

## 부록

### A. 온톨로지 파일 전체 구조 (예제)

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "owl": "http://www.w3.org/2002/07/owl#"
  },
  "@graph": [
    {
      "@id": "mk:",
      "@type": "owl:Ontology",
      "rdfs:label": "Mathking Phase 1 Ontology",
      "owl:versionInfo": "1.0.0"
    },

    // 기존 개념 (3개)
    {"@id": "Student", "@type": "rdfs:Class", "rdfs:label": "학생"},
    {"@id": "Emotion", "@type": "rdfs:Class", "rdfs:label": "감정"},
    {"@id": "hasEmotion", "@type": "rdf:Property"},

    // 신규 개념 (2개)
    {"@id": "InferenceRule", "@type": "rdfs:Class", "rdfs:label": "추론 규칙"},
    {"@id": "Condition", "@type": "rdfs:Class", "rdfs:label": "조건"},

    // 감정 인스턴스 (5개)
    {"@id": "Frustrated", "@type": "Emotion", "rdfs:label": "좌절"},
    {"@id": "Focused", "@type": "Emotion", "rdfs:label": "집중"},
    {"@id": "Tired", "@type": "Emotion", "rdfs:label": "피로"},
    {"@id": "Anxious", "@type": "Emotion", "rdfs:label": "불안"},
    {"@id": "Happy", "@type": "Emotion", "rdfs:label": "기쁨"},

    // 규칙 인스턴스 (10개)
    {
      "@id": "rule_frustrated",
      "@type": "InferenceRule",
      "ruleName": "좌절 → 격려",
      "condition": {"@type": "Condition", "emotionEquals": "Frustrated"},
      "conclusion": "격려 필요",
      "priority": 1.0
    }
    // ... 9개 더
  ]
}
```

### B. 용어 사전

| 용어 | 설명 |
|------|------|
| **온톨로지** | 개념과 관계를 정형화한 지식 표현 체계 |
| **JSON-LD** | JSON 기반의 링크드 데이터 포맷 (W3C 표준) |
| **추론 엔진** | 규칙을 적용하여 새로운 사실을 도출하는 시스템 |
| **SSOT** | Single Source of Truth (단일 진실원) |
| **DRY RUN** | 실제 실행 전 시뮬레이션 및 검증 |

---

**문서 버전**: 1.0.0
**작성일**: 2025-11-01
**작성자**: Ontology Brain Team
**검토자**: (검토 예정)
**승인자**: (승인 예정)
