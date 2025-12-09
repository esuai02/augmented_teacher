# Protégé에서 Turtle 파일 열기 - 단계별 안내

생성일: 2025-01-27

---

## ⚠️ 오류 해결: "Content is not allowed in the prologue"

이 오류는 Protégé가 Turtle 파일을 XML로 잘못 파싱하려고 할 때 발생합니다.

---

## ✅ 해결 방법

### 방법 1: 파일 형식 명시적으로 지정 (권장)

#### 단계별 안내

1. **Protégé 실행**

2. **파일 열기 대화상자**
   - `File` → `Open...` (또는 `Ctrl+O`)

3. **중요: 파일 형식 명시**
   - 파일 선택 대화상자에서
   - **"Files of type"** 드롭다운 클릭
   - **"Turtle Files (*.ttl)"** 선택
   - 또는 **"All Files (*.*)"** 선택 후 파일 선택

4. **파일 선택**
   - 경로: `C:\1 Project\augmented_teacher\alt42\orchestration\agents\ontology_engineering\`
   - 파일: `alphatutor_ontology.ttl`
   - `Open` 클릭

5. **형식 확인**
   - Protégé가 자동으로 형식을 감지하면 "Turtle Syntax"로 인식되어야 합니다
   - 만약 여전히 오류가 발생하면 다음 단계로 진행

---

### 방법 2: 파일을 드래그 앤 드롭

1. **Windows 탐색기에서 파일 찾기**
   - `alphatutor_ontology.ttl` 파일 위치

2. **Protégé에 드래그 앤 드롭**
   - 파일을 Protégé 창으로 드래그
   - 자동으로 형식 감지 시도

---

### 방법 3: 명령줄에서 형식 지정 (고급)

Protégé를 명령줄에서 실행할 수 있는 경우:

```bash
protege.exe --file "C:\1 Project\augmented_teacher\alt42\orchestration\agents\ontology_engineering\alphatutor_ontology.ttl" --format "Turtle"
```

---

### 방법 4: 파일 내용 확인 및 수정

파일이 손상되었을 가능성을 확인:

1. **텍스트 에디터로 파일 열기**
   - 파일이 UTF-8 인코딩인지 확인
   - 첫 줄이 `@prefix`로 시작하는지 확인
   - BOM(Byte Order Mark)이 없는지 확인

2. **파일 재생성**
   ```bash
   python generate_ontology.py
   ```

---

## 🔍 파일 형식 확인

### 올바른 Turtle 파일 형식

파일의 첫 몇 줄이 다음과 같아야 합니다:

```
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix mk: <http://mathking.kr/ontology/alphatutor#> .
```

### 문제가 있는 경우

- XML 선언(`<?xml version="1.0"?>`)이 있으면 안 됩니다
- BOM 문자가 있으면 안 됩니다
- 첫 줄이 공백이면 안 됩니다

---

## 🛠️ Protégé 설정 확인

### 1. 파일 연결 설정

1. `File` → `Preferences` (또는 `Edit` → `Preferences`)
2. `File Associations` 확인
3. `.ttl` 확장자가 "Turtle Syntax"와 연결되어 있는지 확인

### 2. 플러그인 확인

1. `File` → `Preferences` → `Plugins`
2. "Turtle Parser" 또는 "RDF Parser" 플러그인이 활성화되어 있는지 확인

---

## 📝 대안: 다른 도구 사용

Protégé에서 계속 문제가 발생하면:

### 1. Apache Jena (명령줄)

```bash
# 파일 검증
riot --validate alphatutor_ontology.ttl

# 형식 변환
riot --output=RDF/XML alphatutor_ontology.ttl > alphatutor_ontology.rdf
```

### 2. 온라인 도구

- **RDF Validator**: https://www.w3.org/RDF/Validator/
- **Turtle Validator**: 온라인 Turtle 검증 도구 사용

### 3. Python (rdflib)

```python
from rdflib import Graph

# 파일 로드
g = Graph()
g.parse("alphatutor_ontology.ttl", format="turtle")

# 검증
print(f"Triples: {len(g)}")
print("파일이 올바르게 로드되었습니다!")
```

---

## ✅ 성공 확인

파일이 정상적으로 열리면:

1. **하단 상태바**
   - "Loading..." → "Ready"로 변경
   - 오류 메시지 없음

2. **왼쪽 패널**
   - "Entities" 탭에서 클래스 목록 표시
   - 약 1,296개의 클래스 확인 가능

3. **오른쪽 패널**
   - 선택한 클래스의 상세 정보 표시

---

## 🔧 문제가 계속되는 경우

### 체크리스트

- [ ] 파일 형식을 명시적으로 "Turtle Files (*.ttl)"로 선택했는가?
- [ ] 파일이 UTF-8 인코딩인가?
- [ ] 파일 첫 줄이 `@prefix`로 시작하는가?
- [ ] Protégé 버전이 5.6.3 이상인가?
- [ ] Java 버전이 11 이상인가?

### 로그 확인

1. `Help` → `Show Log`
2. 오류 메시지 확인
3. 스택 트레이스 확인

---

## 💡 권장 작업 순서

1. ✅ **파일 형식 명시**: "Turtle Files (*.ttl)" 선택
2. ✅ **파일 열기**: `alphatutor_ontology.ttl` 선택
3. ✅ **로딩 확인**: 상태바에서 "Ready" 확인
4. ✅ **구조 확인**: Entities 탭에서 클래스 목록 확인
5. ✅ **추론 실행**: Reasoner → Start reasoner

---

**마지막 업데이트**: 2025-01-27

