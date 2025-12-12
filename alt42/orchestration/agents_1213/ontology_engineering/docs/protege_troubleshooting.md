# Protégé 오류 해결 가이드

생성일: 2025-01-27

---

## ⚠️ OWL 파일 파싱 오류 해결

### 문제 상황
Protégé에서 `alphatutor_ontology.owl` 파일을 열 때 파싱 오류가 발생하는 경우

### 해결 방법

#### 방법 1: Turtle 파일 사용 (권장) ✅

**Turtle 형식이 더 안정적이고 Protégé에서 잘 작동합니다.**

1. Protégé 실행
2. `File` → `Open...`
3. 파일 형식: **"Turtle Files (*.ttl)"** 선택
4. 파일 선택: `alphatutor_ontology.ttl`
5. `Open` 클릭

**장점**:
- 더 유연한 형식
- 특수 문자 처리 용이
- Protégé에서 안정적으로 작동

---

#### 방법 2: OWL 파일 재생성

OWL 파일에 문제가 있는 경우, 스크립트를 수정하여 재생성:

```bash
python generate_ontology.py
```

**개선 사항**:
- URI 안전한 이름 변환 추가
- XML 특수 문자 이스케이프 처리
- 숫자로 시작하는 이름 처리

---

#### 방법 3: 파일 형식 변환

Turtle 파일을 Protégé에서 열고 OWL 형식으로 저장:

1. Turtle 파일(`.ttl`)을 Protégé에서 열기
2. `File` → `Save As...`
3. 형식: **"OWL/XML Syntax"** 선택
4. 저장

---

## 🔍 일반적인 오류 및 해결

### 오류 1: "Could not parse the ontology"

**원인**:
- 파일 형식 불일치
- 특수 문자 문제
- XML 구조 오류

**해결**:
1. Turtle 파일(`.ttl`) 사용
2. 파일 인코딩이 UTF-8인지 확인
3. 파일이 손상되지 않았는지 확인

### 오류 2: "Invalid URI"

**원인**:
- 숫자로 시작하는 클래스 이름
- 특수 문자가 포함된 URI

**해결**:
- Turtle 파일 사용 (더 유연함)
- 또는 스크립트로 URI 정규화

### 오류 3: "Out of memory"

**원인**:
- 파일이 너무 큼 (약 230KB)
- 추론 엔진 메모리 부족

**해결**:
1. Protégé 메모리 증가:
   - `protege.bat` 편집
   - `-Xmx2048m` 추가
2. 추론 엔진 비활성화 후 파일 열기
3. 필요한 부분만 로드

---

## ✅ 권장 작업 순서

### 1단계: Turtle 파일로 시작
```
1. Protégé 실행
2. File → Open...
3. 파일 형식: "Turtle Files (*.ttl)"
4. alphatutor_ontology.ttl 선택
5. Open 클릭
```

### 2단계: 기본 확인
- 파일이 정상적으로 열리는지 확인
- 클래스 및 속성 목록 확인

### 3단계: 추론 엔진 실행
- Reasoner → Pellet reasoner 선택
- Reasoner → Start reasoner 실행
- 일관성 검사

### 4단계: 필요시 OWL 변환
- Protégé에서 File → Save As...
- 형식: "OWL/XML Syntax" 선택
- 저장

---

## 📝 파일 형식 비교

| 형식 | 확장자 | 장점 | 단점 |
|------|--------|------|------|
| **Turtle** | `.ttl` | 읽기 쉬움, 유연함, 안정적 | - |
| **OWL XML** | `.owl` | 표준 형식 | 특수 문자 처리 어려움 |
| **RDF/XML** | `.rdf` | 표준 형식 | 읽기 어려움 |

**권장**: Turtle 형식 (`.ttl`) 사용

---

## 🔧 스크립트 개선 사항

최신 버전의 `generate_ontology.py`에는 다음 개선이 포함되었습니다:

1. ✅ URI 안전한 이름 변환 (`sanitize_uri` 함수)
2. ✅ XML 특수 문자 이스케이프 처리
3. ✅ 숫자로 시작하는 이름 처리
4. ✅ Windows 콘솔 인코딩 지원

---

## 💡 팁

1. **항상 Turtle 파일 사용**: 더 안정적이고 유연합니다
2. **파일 크기 확인**: 큰 파일은 로딩에 시간이 걸립니다
3. **백업**: 원본 파일은 항상 백업해두세요
4. **단계별 확인**: 파일 열기 → 구조 확인 → 추론 실행 순서로 진행

---

**마지막 업데이트**: 2025-01-27

