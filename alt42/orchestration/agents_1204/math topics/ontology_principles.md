
# 🧭 [명세서] Adaptive Review Ontology 설계 규격서 (수학 교과용)

## 1️⃣ 목적

대한민국 수학 교과의 단원 및 학습 콘텐츠를 **온톨로지(RDF/OWL)**로 구조화하여,
단원별·학년별 학습 순서, 개념 관계, 복습경로, 활동유형을 **데이터적으로 연결**하기 위함.

---

## 2️⃣ 기본 구조

| 계층               | 개체 타입(Class)          | 설명                           |
| ---------------- | --------------------- | ---------------------------- |
| Stage            | `ar:Stage`            | 학년 및 단원 단위 (예: 초6-2, 중1-1 등) |
| Subtopic         | `ar:Subtopic`         | 실제 학습 콘텐츠에 대응하는 소주제          |
| LearningActivity | `ar:LearningActivity` | 학습활동(요약, 이해, 체크, 퀴즈, 대표유형)   |
| Ontology root    | `owl:Ontology`        | 전체 교육 체계의 최상위 엔트리            |

---
 
## 3️⃣ 속성(Property) 명세

| 속성명               | 타입       | 도메인              | 범위               | 설명                                 |
| ----------------- | -------- | ---------------- | ---------------- | ---------------------------------- |
| `ar:stage`        | Datatype | Subtopic         | xsd:integer      | 학년·단계 번호 (정렬용)                     |
| `ar:belongsTo`    | Object   | Subtopic         | Stage            | 어떤 단원(Stage)에 속하는지                 |
| `ar:description`  | Datatype | Subtopic         | xsd:string       | 교과 수준의 상세 설명 (성취기준, 핵심개념, 활동예시 포함) |
| `ar:hasURL`       | Datatype | Subtopic         | xsd:anyURI       | 콘텐츠 링크(URL)                        |
| `ar:includes`     | Object   | Subtopic         | LearningActivity | 해당 주제가 포함하는 학습 활동                  |
| `ar:precedes`     | Object   | Subtopic         | Subtopic         | 동일 단원 내 순서 관계                      |
| `ar:dependsOn`    | Object   | Subtopic         | Subtopic         | 선행 학습 필요 관계                        |
| `ar:difficulty`   | Datatype | LearningActivity | xsd:string       | 활동 난이도(Level 1~5)                  |
| `ar:timeCost`     | Datatype | LearningActivity | xsd:float        | 예상 소요 시간(분 단위)                     |
| `ar:hasMetricKey` | Datatype | Subtopic         | xsd:string       | 외부 API·로그 연결용 식별자                  |

---

## 4️⃣ 학습활동 세트 정의

모든 Subtopic은 아래 5단계 활동 세트를 반드시 `includes`로 연결한다.

| 활동명    | 영어 ID                        | 설명(교과형)              |
| ------ | ---------------------------- | -------------------- |
| 개념요약   | `ConceptRemind_Default`      | 핵심 개념을 짧게 상기(remind) |
| 개념이해하기 | `ConceptRebuild_Default`     | 개념 구조를 체계적으로 복기      |
| 개념체크   | `ConceptCheck_Default`       | 간단한 예제를 통한 확인        |
| 예제퀴즈   | `ExampleQuiz_Default`        | 개념 적용 문제 풀이          |
| 대표유형   | `RepresentativeType_Default` | 대표유형 문제로 확장활동        |

---

## 5️⃣ description 작성 규칙

`ar:description`은 아래 4요소를 포함해야 한다.
대한민국 교육과정 성취기준 문구를 참고하여 교과서 수준으로 기술한다.

```
1️⃣ 핵심 개념 : 주제의 중심 개념  
2️⃣ 성취기준 : 교육부 성취기준 코드 및 문장  
3️⃣ 학습활동 : 학습자가 수행할 행동  
4️⃣ 적용 예시 : 실생활 또는 문제 적용 예
```

📘 예시:

```xml
<ar:description xml:lang="ko">
  [핵심개념] 분수의 덧셈과 뺄셈  
  [성취기준: 5수02-01] 분모가 같은 분수의 덧셈과 뺄셈의 계산 원리를 이해하고 계산할 수 있다.  
  [학습활동] 공통분모를 구해 계산하고, 결과를 기약분수로 표현한다.  
  [적용예시] 분수 단위의 길이·시간·면적 문제 해결.
</ar:description>
```

---

## 6️⃣ 학년별 계층 구조

```
Stage(초등)
 ├── 큰수
 │    ├── 만
 │    ├── 억
 │    ├── 조
 │    └── 두 수의 크기 비교
 ├── 곱셈과 나눗셈
 │    └── 세 자리 수 × 두 자리 수 ...
 └── ...
Stage(중등)
 ├── 소인수분해
 ├── 유리수의 계산
 └── ...
Stage(고등)
 ├── 제곱근
 ├── 복소수
```

---

## 7️⃣ 관계 정의 규칙

| 관계          | 의미         | 연결 기준                    |
| ----------- | ---------- | ------------------------ |
| `precedes`  | 동일 단원 내 순차 | 리스트 순서 기준                |
| `dependsOn` | 상위 개념 선행   | 예: “분수의 덧셈과 뺄셈 → 분수의 곱셈” |
| `includes`  | 활동 세트 연결   | 모든 Subtopic 공통 적용        |

---

## 8️⃣ 데이터 구축 프로세스

| 단계 | 작업       | 설명                                        |
| -- | -------- | ----------------------------------------- |
| 1  | 주제 목록 입력 | 학년, 단원명, 소주제명, URL                        |
| 2  | 교과 정보 매핑 | 각 소주제에 성취기준·핵심개념 추가                       |
| 3  | 온톨로지 변환  | RDF/XML 구조로 생성                            |
| 4  | 관계 설정    | `precedes`, `dependsOn`, `includes` 자동 삽입 |
| 5  | 검증       | IRI 중복/순서 오류/누락 검증                        |
| 6  | 배포       | `/mnt/data/susystem_full.owl` 형태로 출력      |

---

## 9️⃣ 출력 사양

* **파일명:** `susystem_full.owl`
* **형식:** RDF/XML (UTF-8)
* **네임스페이스:** `xmlns:ar="http://example.org/adaptive-review#"`
* **라벨/설명 언어:** `xml:lang="ko"`

---

## 🔟 확장 설계 제안

| 기능                    | 설명                              |
| --------------------- | ------------------------------- |
| `ar:hasCoursePreset`  | 최단/탄탄/문제풀이형 복습 코스 정의            |
| `ar:hasMetricKey`     | 외부 BI시스템과 학습로그 연동               |
| `ar:StudentSituation` | 학생 상태(시간·난이도·컨디션)에 따른 추천 제어     |
| `ar:ActivityWeight`   | 학습활동별 중요도 조정(예: 요약=0.5, 퀴즈=1.2) |

--- 