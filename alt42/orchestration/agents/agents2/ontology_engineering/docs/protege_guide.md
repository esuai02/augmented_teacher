# Protégé 온톨로지 검증 가이드

생성일: 2025-01-27
온톨로지: AlphaTutor Learning Ontology

---

## 📥 Protégé 설치

### 1. 다운로드
- **공식 사이트**: https://protege.stanford.edu/
- **최신 버전**: Protégé 5.6.3 (또는 최신 버전)
- **요구사항**: Java 11 이상

### 2. 설치
1. 다운로드한 설치 파일 실행
2. Java가 설치되어 있지 않다면 Java 11+ 설치 필요
3. 설치 완료 후 Protégé 실행

---

## 📂 온톨로지 파일 열기

### 방법 1: RDF Turtle 형식 (.ttl) - 권장

1. **Protégé 실행**
   - Protégé를 실행합니다

2. **파일 열기**
   - `File` → `Open...` (또는 `Ctrl+O`)
   - 파일 형식: **"All Files"** 또는 **"Turtle Files (*.ttl)"** 선택
   - 경로: `alt42/orchestration/agents/ontology_engineering/alphatutor_ontology.ttl`
   - `Open` 클릭

3. **로딩 확인**
   - 하단 상태바에서 로딩 진행 상황 확인
   - 로딩 완료까지 몇 초 소요될 수 있습니다

### 방법 2: OWL XML 형식 (.owl)

1. **파일 열기**
   - `File` → `Open...`
   - 파일 형식: **"OWL Files (*.owl)"** 선택
   - 경로: `alt42/orchestration/agents/ontology_engineering/alphatutor_ontology.owl`
   - `Open` 클릭

---

## 🔍 기본 검증 작업

### 1. 구조 확인

#### Entities 탭 확인
1. 왼쪽 패널에서 **"Entities"** 탭 클릭
2. **"Classes"** 선택
3. 클래스 목록 확인:
   - `Student`, `Teacher`, `Goal`, `Plan`, `Routine` 등
   - 총 약 1,296개의 클래스/개체 확인

#### Object Properties 확인
1. **"Object properties"** 선택
2. 속성 목록 확인:
   - `requires`, `affects`, `leadsTo`, `isSubtypeOf` 등
   - 총 183개의 속성 확인

#### Data Properties 확인
1. **"Data properties"** 선택
2. 데이터 속성 확인 (있는 경우)

### 2. 클래스 계층 구조 확인

#### Class Hierarchy 보기
1. **"Classes"** 탭에서 계층 구조 확인
2. `isSubtypeOf` 관계로 형성된 계층 확인
3. 예시:
   ```
   Student
   ├── StrugglingStudent
   ├── HighPotentialStudent
   └── CrammingStudent
   ```

#### 특정 클래스 상세 보기
1. 클래스를 클릭하면 오른쪽 패널에 상세 정보 표시
2. **"Description"** 탭에서:
   - 클래스 정의 확인
   - 상위 클래스 확인
   - 속성 제약 확인

### 3. 속성 확인

#### Object Property 상세 보기
1. **"Object properties"** 탭에서 속성 선택
2. 오른쪽 패널에서 확인:
   - **Domain**: 속성이 적용되는 클래스
   - **Range**: 속성 값의 클래스
   - **Characteristics**: 전이성, 대칭성 등

#### 주요 속성 확인
- `requires`: Cognitive 계층
- `affects`: Affective 계층
- `leadsTo`: Behavioral 계층
- `isSubtypeOf`: Meta 계층

---

## 🧠 추론 엔진 사용

### 1. 추론 엔진 선택

#### Pellet 사용 (권장)
1. 상단 메뉴: **"Reasoner"** → **"Pellet reasoner"** 선택
2. 또는 **"Reasoner"** → **"Configure reasoner..."** → **"Pellet"** 선택

#### HermiT 사용 (대안)
1. **"Reasoner"** → **"HermiT reasoner"** 선택
2. HermiT이 설치되어 있지 않다면 자동 설치 안내

### 2. 추론 시작

1. **"Reasoner"** → **"Start reasoner"** (또는 `Ctrl+R`)
2. 추론 진행 상황 확인:
   - 하단 상태바에서 진행률 표시
   - 완료까지 몇 초~몇 분 소요될 수 있습니다

### 3. 추론 결과 확인

#### 분류 결과 확인
1. **"Classes"** 탭에서 계층 구조 업데이트 확인
2. 추론으로 새로 추가된 하위 클래스 확인

#### 일관성 검사
1. **"Reasoner"** → **"Check consistency"**
2. 결과 확인:
   - ✅ **"Ontology is consistent"**: 일관성 있음
   - ⚠️ **"Ontology is inconsistent"**: 모순 발견 (자세한 내용 확인 필요)

---

## 🔎 고급 검증 작업

### 1. SPARQL 쿼리 실행

3. 결과 확인

#### 예제 쿼리들
- `sparql_queries.md` 파일 참조
- 17개의 예제 쿼리 제공

### 2. 개체(Individuals) 확인

#### Individuals 탭
1. **"Entities"** → **"Individuals"** 선택
2. 개체 목록 확인
3. 특정 개체 클릭하여 속성 확인

### 3. 관계 시각화

#### OntoGraf 사용
1. 상단 탭에서 **"OntoGraf"** 탭 클릭
2. 클래스 간 관계 시각화 확인
3. 특정 클래스 선택하여 연결된 클래스 확인

---

## ⚠️ 일반적인 문제 해결

### 1. 파일이 열리지 않는 경우

#### 문제: "File format not recognized"
**해결책**:
- 파일 확장자 확인 (`.ttl` 또는 `.owl`)
- `File` → `Open...` → 파일 형식 명시적으로 선택

#### 문제: "Encoding error"
**해결책**:
- 파일이 UTF-8 인코딩인지 확인
- Protégé 재시작

### 2. 추론 엔진이 작동하지 않는 경우

#### 문제: "Reasoner not found"
**해결책**:
1. `File` → `Preferences` → `Reasoner`
2. 추론 엔진 경로 확인
3. 필요시 수동 설치:
   - Pellet: http://pellet.owldl.com/
   - HermiT: http://www.hermit-reasoner.com/

#### 문제: "Out of memory"
**해결책**:
1. Protégé 실행 옵션에서 메모리 증가:
   - `protege.bat` 또는 `protege.sh` 편집
   - `-Xmx2048m` 추가 (2GB 메모리 할당)

### 3. 성능 문제

#### 문제: 로딩이 느림
**해결책**:
- 파일 크기가 크므로(약 230KB) 로딩에 시간 소요 가능
- 추론 엔진 사용 시 더 오래 걸릴 수 있음
- 인내심을 가지고 기다리기

---

## ✅ 검증 체크리스트

### 기본 검증
- [ ] 파일이 정상적으로 열림
- [ ] 클래스 목록 확인 (약 1,296개)
- [ ] 속성 목록 확인 (약 183개)
- [ ] 클래스 계층 구조 확인
- [ ] 속성 정의 확인 (domain, range)

### 추론 검증
- [ ] 추론 엔진 선택 (Pellet 또는 HermiT)
- [ ] 추론 실행 성공
- [ ] 일관성 검사 통과
- [ ] 분류 결과 확인
- [ ] 추론된 관계 확인

### 쿼리 검증
- [ ] SPARQL 쿼리 탭 열기
- [ ] 기본 쿼리 실행 성공
- [ ] 복합 쿼리 실행 성공
- [ ] 결과 확인

### 시각화
- [ ] OntoGraf로 관계 시각화 확인
- [ ] 주요 엔티티 간 연결 확인

---

## 📊 예상 결과

### 정상적인 경우
- ✅ 파일 로딩 성공
- ✅ 클래스 및 속성 정상 표시
- ✅ 추론 엔진 정상 작동
- ✅ 일관성 검사 통과
- ✅ SPARQL 쿼리 정상 실행

### 주의가 필요한 경우
- ⚠️ 일부 리터럴 값이 클래스로 잘못 분류됨 (정상 - 후처리 필요)
- ⚠️ 일부 서술어가 정의되지 않음 (정상 - 추가 서술어 사용)
- ⚠️ 고립된 엔티티 존재 (정상 - 리터럴 값들)

---

## 🎯 주요 확인 사항

### 1. 핵심 클래스 확인
다음 클래스들이 정상적으로 정의되어 있는지 확인:
- `Student`
- `Teacher`
- `Goal` (및 하위: `LongTermGoal`, `QuarterlyGoal`, `WeeklyGoal`, `TodayGoal`)
- `Routine` (및 하위: `SignatureRoutine`, `RestRoutine`)
- `LearningActivity`
- `Persona`
- `EmotionPattern`

### 2. 핵심 속성 확인
다음 속성들이 정상적으로 정의되어 있는지 확인:
- `requires` (Cognitive)
- `affects` (Affective)
- `leadsTo` (Behavioral)
- `isSubtypeOf` (Meta)

### 3. 관계 확인
다음 관계들이 정상적으로 표현되어 있는지 확인:
- `Student → hasAttribute → MathLevel`
- `Student → hasGoal → Goal`
- `Student → hasRoutine → Routine`
- `LearningActivity → affects → Persona`

---

## 📝 다음 단계

### Protégé 검증 후
1. ✅ 일관성 검사 완료
2. ✅ 추론 결과 확인
3. ✅ SPARQL 쿼리 테스트
4. [ ] 발견된 문제점 문서화
5. [ ] 필요시 온톨로지 수정 및 재생성

### 추가 작업
1. [ ] 온톨로지 최적화 (불필요한 클래스 제거)
2. [ ] 속성 정의 보완 (domain, range 명시)
3. [ ] 주석 및 설명 추가
4. [ ] 버전 관리 설정

---

## 🔗 유용한 리소스

### Protégé 공식 문서
- 사용자 가이드: https://protegeproject.github.io/protege/
- 튜토리얼: https://protegeproject.github.io/protege/getting-started/

### 온톨로지 관련
- OWL 2 프라이머: https://www.w3.org/TR/owl2-primer/
- SPARQL 쿼리 언어: https://www.w3.org/TR/sparql11-query/

### 추론 엔진
- Pellet: http://pellet.owldl.com/
- HermiT: http://www.hermit-reasoner.com/

---

## 💡 팁

1. **대용량 파일 처리**
   - 파일이 크므로 처음 로딩 시 시간이 걸릴 수 있습니다
   - 추론 엔진 사용 시 메모리 사용량이 증가합니다

2. **성능 최적화**
   - 불필요한 추론 비활성화
   - 필요한 클래스만 표시

3. **백업**
   - 검증 전 원본 파일 백업 권장
   - Protégé에서 수정한 경우 별도 저장

---

**마지막 업데이트**: 2025-01-27
**Protégé 버전**: 5.6.3 이상 권장

