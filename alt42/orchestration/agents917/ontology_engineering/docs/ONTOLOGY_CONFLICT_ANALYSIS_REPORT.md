# 온톨로지 문서 충돌 및 일관성 분석 보고서

**분석 대상**: `ontology_engineering/docs/` 내의 주요 문서들
**분석 목적**: 문서 간의 기술적, 개념적 충돌을 식별하고 해결 방안 제시

---

## 🚨 1. 치명적 기술 충돌 (Critical Technical Conflicts)

가장 시급하게 해결해야 할 문제는 **네임스페이스(Namespace)**와 **서술어(Predicate)**의 불일치입니다. 이는 시스템이 실제로 작동하지 않게 만드는 원인이 됩니다.

### 1.1 네임스페이스 불일치 (`at:` vs `mk:`)
- **현상**:
  - **추론 규칙 (`inference_rules.md`, `ontology_validation.md`)**: `at:` (AlphaTutor) 접두사를 사용합니다. (예: `at:Student`, `at:isPrerequisiteOf`)
  - **구현 가이드 (`01_GUIDE...`, `AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md`)**: `mk:` (MathKing) 접두사를 사용합니다. (예: `mk:OnboardingContext`, `mk:hasStudentGrade`)
- **문제점**: 구현된 시스템이 `mk:`로 데이터를 생성하면, `at:`을 기대하는 추론 엔진(Rule Engine)이 작동하지 않습니다.
- **해결 방안**: **`mk:`로 통일**하거나, 온톨로지 파일(`owl`) 내에서 두 접두사가 동일한 URI를 가리키도록 매핑해야 합니다. (문서상으로는 하나로 통일하는 것을 권장)

### 1.2 핵심 서술어(Predicate) 정의 누락 및 불일치
- **현상**:
  - **원칙 문서 (`priciples_서술어.md`)**: 핵심 15개 서술어만 정의 (`hasPart`, `causes`, `leadsTo` 등).
  - **실제 사용 (`inference_rules.md`, `triples_summary.md`)**: 원칙 문서에 없는 서술어들이 대거 등장하며, 시스템의 핵심 로직을 담당함.
    - `hasAttribute` (Student 속성 정의에 필수적이나 원칙 문서에 없음)
    - `hasGoal`, `hasPlan` (Agent 03의 핵심이나 원칙 문서에 없음)
    - `performs` (학습 활동 정의에 필수적이나 원칙 문서에 없음)
    - `isPrerequisiteOf` (추론 규칙의 핵심이나 원칙 문서의 표에는 누락됨)
- **문제점**: 개발자가 `priciples_서술어.md`만 보고 개발할 경우, 실제 시스템(`inference_rules.md`)에서 요구하는 필수 관계를 정의하지 않아 추론이 실패합니다.
- **해결 방안**: `priciples_서술어.md`를 업데이트하여 `inference_rules.md`와 `triples_summary.md`에서 사용되는 **실전 서술어(Practical Predicates)**를 모두 포함시켜야 합니다.

---

## ⚠️ 2. 개념적 불일치 (Conceptual Inconsistencies)

온톨로지를 바라보는 관점이 문서마다 다릅니다.

### 2.1 주어 선택 기준: 동적(Dynamic) vs 정적(Static)
- **현상**:
  - **`priciples_주어.md`**: 대화의 맥락에 따라 "의미 에너지", "관계 생성력" 등을 고려하여 주어를 **동적으로 선택**하라고 가이드합니다. (NLP 기반 접근)
  - **`triples_summary.md`, `inference_rules.md`**: `Student`, `Goal`, `Plan` 등 **고정된 스키마(Schema)** 기반의 주어를 주로 사용합니다. (DB/구조적 접근)
- **문제점**: 개발자가 혼란을 겪습니다. "매번 새로운 주어를 찾아야 하는가?" 아니면 "정해진 스키마에 맞춰 데이터를 넣어야 하는가?"
- **해결 방안**: **하이브리드 모델 명시**. "기본 골격(Student, Goal 등)은 정적 스키마를 따르고, 대화 내용 분석(Context)은 동적 주어를 허용한다"는 지침으로 정리해야 합니다.

---

## 📝 3. 문서 간 범위 및 용어 충돌

### 3.1 구현 가이드 중복 및 용어 차이
- **현상**:
  - `01_GUIDE_ONTOLOGY_ENGINE_INTEGRATION.md`와 `AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md`가 유사한 내용을 다루지만 관점이 다름 (메커니즘 vs 절차).
  - 용어 차이: `generate_procedure` (Guide) vs `generate_strategy` (Workflow).
- **해결 방안**: `WORKFLOW` 문서를 메인으로 삼고, `GUIDE` 문서는 엔진의 내부 동작 원리를 설명하는 기술 문서(Reference)로 격하시키거나 통합해야 합니다.

---

## ✅ 4. 종합 해결 로드맵

이 충돌들을 해결하기 위한 구체적인 실행 계획입니다.

1.  **Namespace 통일 (즉시 실행)**
    *   모든 문서의 예시 코드를 `mk:` (또는 결정된 하나)로 일괄 변경.
    *   `inference_rules.md`의 SPARQL 쿼리 수정.

2.  **서술어(Predicate) 동기화**
    *   `triples_summary.md`에 있는 실제 사용 서술어(`hasAttribute`, `hasGoal` 등)를 수집.
    *   `priciples_서술어.md`의 "핵심 서술어" 목록을 "이론적 서술어"와 "시스템적 서술어"로 나누어 확장 정의.

3.  **문서 위계 재정립**
    *   **Level 1 (개념)**: `priciples_*.md` (이상적인 원칙)
    *   **Level 2 (규격)**: `ontology_schema.md` (신규 생성 제안 - 확정된 클래스와 서술어 목록)
    *   **Level 3 (구현)**: `AGENT_ONTOLOGY_IMPLEMENTATION_WORKFLOW.md` (실제 구현 가이드)
    *   **Level 4 (검증)**: `inference_rules.md`, `ontology_validation.md`

4.  **레거시 청산**
    *   `docindex.php` 등 웹 뷰 관련 파일이 현재 문서화 목적에 맞지 않다면 제거하거나 별도 디렉토리로 이동.
