# 맞춤형 컨텐츠 시스템 테스트 가이드

## 📋 개요

AI 생성 맞춤형 컨텐츠(자세히 생각하기 + 추가 질문/답변) 시스템의 완전한 테스트 절차입니다.

**작성일**: 2025-01-25
**대상 시스템**: drillingmath.php + AI 생성 API + DB 저장

---

## 🎯 테스트 목표

1. ✅ DB 테이블 생성 확인
2. ✅ AI 생성 "자세히 생각하기" + 질문 3개 저장 확인
3. ✅ 질문 클릭 시 AI 답변 생성 및 저장 확인
4. ✅ 수식 렌더링 (LaTeX with MathJax) 확인
5. ✅ nstep별 데이터 격리 확인

---

## 🔧 사전 준비

### 1. 필수 파일 확인

```bash
/mnt/c/1 Project/augmented_teacher/books/
├── drillingmath.php                          # 메인 페이지
├── generate_detailed_thinking.php            # AI 생성 (자세히 생각하기 + 질문)
├── get_additional_answer.php                 # AI 생성 (질문 답변)
├── create_tailored_contents_table.php        # DB 테이블 생성
└── MATH_RENDERING_GUIDE.md                   # 수식 표기 가이드
```

### 2. 환경 확인

- **PHP Version**: 7.1.9
- **MySQL**: 5.7
- **Moodle**: 3.7
- **OpenAI API Key**: 설정 확인 (generate_detailed_thinking.php, get_additional_answer.php)

---

## 📝 테스트 절차

### Phase 1: 데이터베이스 테이블 생성

#### 1.1 테이블 생성 스크립트 실행

**URL**: `https://mathking.kr/moodle/local/augmented_teacher/books/create_tailored_contents_table.php`

**예상 결과**:
```
맞춤형 컨텐츠 테이블 생성 스크립트
====================================

[Info] 테이블 'abessi_tailoredcontents'을(를) 생성합니다...
[Success] 테이블 'abessi_tailoredcontents'이(가) 성공적으로 생성되었습니다.

테이블 구조:
- id: 고유 ID (자동 증가)
- contentstype: 컨텐츠 타입 (1=icontent, 2=question)
- contentsid: 컨텐츠 ID
- nstep: 구간 번호 (1, 2, 3...)
- qstn0: 자세히 생각하기 내용
- qstn1~3: 추가 질문 3개
- ans0: 자세히 생각하기 답변
- ans1~3: 추가 질문 답변 3개
- timemodified: 수정 시간 (unixtime)
- timecreated: 생성 시간 (unixtime)

============================================================
테이블 정보:
============================================================
- id                   C(10)
- contentstype         I(2)
- contentsid           I(10)
- nstep                I(5)
- qstn0                X
- qstn1                X
- qstn2                X
- qstn3                X
- ans0                 X
- ans1                 X
- ans2                 X
- ans3                 X
- timemodified         I(10)
- timecreated          I(10)

[완료] 스크립트 실행이 완료되었습니다.
```

#### 1.2 데이터베이스 확인

**SQL 쿼리**:
```sql
SHOW CREATE TABLE mdl_abessi_tailoredcontents;
SELECT * FROM mdl_abessi_tailoredcontents LIMIT 5;
```

**확인 사항**:
- ✅ UNIQUE KEY `unique_content_step` (contentsid, contentstype, nstep) 존재
- ✅ 인덱스 5개 생성됨 (id, contentsid, contentstype, nstep, timecreated, timemodified)
- ✅ ENGINE=InnoDB, CHARSET=utf8mb4

---

### Phase 2: AI 생성 "자세히 생각하기" 테스트

#### 2.1 페이지 로드 테스트

**테스트 URL**:
```
https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=29566&ctype=1&section=0&nstep=1&subtitle=테스트
```

**파라미터 설명**:
- `cid=29566`: 컨텐츠 ID
- `ctype=1`: 컨텐츠 타입 (1=icontent)
- `section=0`: 섹션 번호
- `nstep=1`: 구간 번호 (1부터 시작)
- `subtitle=테스트`: 서브타이틀 (URL 인코딩 필요 시)

#### 2.2 로딩 과정 확인

**예상 동작 순서**:
1. 페이지 로드 시 "자세히 생각하기" 영역에 로딩 스피너 표시
2. 자동으로 `generate_detailed_thinking.php` 호출
3. AI가 현재 구간에 대한 설명 생성
4. AI가 추가 질문 3개 생성
5. 우측 칼럼에 내용 표시 + 질문 버튼 3개 표시

**브라우저 콘솔 확인**:
```javascript
// 정상 로그
[drillingmath.php] Generating detailed thinking for nstep: 1
[drillingmath.php] Response received from generate_detailed_thinking.php
[drillingmath.php] Questions generated: 3
```

#### 2.3 데이터베이스 저장 확인

**SQL 쿼리**:
```sql
SELECT
    id,
    contentsid,
    contentstype,
    nstep,
    LEFT(qstn0, 50) as qstn0_preview,
    qstn1,
    qstn2,
    qstn3,
    ans0,
    ans1,
    ans2,
    ans3,
    FROM_UNIXTIME(timecreated) as created,
    FROM_UNIXTIME(timemodified) as modified
FROM mdl_abessi_tailoredcontents
WHERE contentsid = 29566 AND contentstype = 1 AND nstep = 1;
```

**확인 사항**:
- ✅ `qstn0`: "자세히 생각하기" 내용 저장 (LONGTEXT)
- ✅ `qstn1`, `qstn2`, `qstn3`: 질문 3개 저장
- ✅ `ans0`, `ans1`, `ans2`, `ans3`: 모두 빈 문자열 (아직 답변 생성 안 됨)
- ✅ `timecreated`, `timemodified`: unixtime 형식으로 저장

#### 2.4 수식 렌더링 확인

**확인 사항**:
- ✅ 인라인 수식: `\(x^2 + 1\)` 형태로 렌더링
- ✅ 디스플레이 수식: `\[\frac{a}{b}\]` 형태로 렌더링
- ✅ 금지 표기법 사용 안 됨: `$`, `$$`, `\begin{}`, `\end{}`

**브라우저 콘솔 확인**:
```javascript
// MathJax 로드 확인
console.log('MathJax loaded:', typeof MathJax !== 'undefined');

// 렌더링 성공 로그
[MathJax] Typesetting completed for element: #thinkingContent
```

---

### Phase 3: AI 생성 "질문 답변" 테스트

#### 3.1 질문 버튼 클릭 테스트

**테스트 순서**:
1. 첫 번째 질문 버튼 클릭 (qstn1)
2. 두 번째 질문 버튼 클릭 (qstn2)
3. 세 번째 질문 버튼 클릭 (qstn3)

**예상 동작**:
- 버튼 클릭 시 로딩 스피너 표시
- `get_additional_answer.php` 호출 (questionNum=1, 2, 3)
- AI 답변 생성
- 답변 영역에 표시 + MathJax 렌더링

**브라우저 콘솔 확인**:
```javascript
// 정상 로그
[drillingmath.php] Generating answer for question 1
[drillingmath.php] Answer received from get_additional_answer.php
[drillingmath.php] MathJax rendering completed
```

#### 3.2 데이터베이스 업데이트 확인

**SQL 쿼리** (각 버튼 클릭 후):
```sql
SELECT
    LEFT(ans0, 50) as ans0_preview,
    LEFT(ans1, 50) as ans1_preview,
    LEFT(ans2, 50) as ans2_preview,
    LEFT(ans3, 50) as ans3_preview,
    FROM_UNIXTIME(timemodified) as modified
FROM mdl_abessi_tailoredcontents
WHERE contentsid = 29566 AND contentstype = 1 AND nstep = 1;
```

**확인 사항**:
- ✅ 첫 번째 버튼 클릭 후: `ans1` 필드에 답변 저장
- ✅ 두 번째 버튼 클릭 후: `ans2` 필드에 답변 저장
- ✅ 세 번째 버튼 클릭 후: `ans3` 필드에 답변 저장
- ✅ `timemodified`: 각 업데이트마다 갱신

#### 3.3 답변 토글 테스트

**테스트 순서**:
1. 질문 버튼 클릭 → 답변 표시
2. 같은 버튼 다시 클릭 → 답변 숨김
3. 다시 클릭 → 답변 다시 표시 (DB에서 로드, AI 재생성 안 함)

**확인 사항**:
- ✅ 답변이 이미 생성된 경우 DB에서 즉시 로드
- ✅ AI API 중복 호출 안 함
- ✅ 토글 동작 정상 (show/hide)

---

### Phase 4: nstep 격리 테스트

#### 4.1 여러 구간 테스트

**테스트 URL**:
```
# 구간 1
https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=29566&ctype=1&section=0&nstep=1

# 구간 2
https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=29566&ctype=1&section=1&nstep=2

# 구간 3
https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=29566&ctype=1&section=2&nstep=3
```

#### 4.2 데이터 격리 확인

**SQL 쿼리**:
```sql
SELECT
    nstep,
    LEFT(qstn0, 50) as qstn0_preview,
    qstn1,
    LEFT(ans1, 30) as ans1_preview,
    FROM_UNIXTIME(timecreated) as created
FROM mdl_abessi_tailoredcontents
WHERE contentsid = 29566 AND contentstype = 1
ORDER BY nstep;
```

**확인 사항**:
- ✅ nstep=1, 2, 3 각각 별도 레코드로 저장
- ✅ UNIQUE KEY 제약조건 작동 (중복 방지)
- ✅ 각 구간의 내용이 독립적으로 생성됨

---

## 🚨 오류 대응 가이드

### 문제 1: 테이블 생성 실패

**증상**: "Table already exists" 또는 권한 오류

**해결책**:
```sql
-- 기존 테이블 삭제 (주의: 데이터 손실)
DROP TABLE IF EXISTS mdl_abessi_tailoredcontents;

-- 또는 테이블 구조 확인
SHOW CREATE TABLE mdl_abessi_tailoredcontents;
```

### 문제 2: AI 생성 실패

**증상**: 로딩 스피너가 계속 표시됨

**확인 사항**:
1. 브라우저 콘솔 에러 메시지 확인
2. PHP 에러 로그 확인:
   ```bash
   tail -f /var/log/php/error.log | grep "generate_detailed_thinking"
   ```
3. OpenAI API 키 유효성 확인
4. API 응답 코드 확인 (200 OK 여부)

**해결책**:
- API 키 재발급 및 업데이트
- 네트워크 연결 확인
- API 할당량 확인

### 문제 3: DB 저장 실패

**증상**: 생성은 되지만 DB에 저장 안 됨

**확인 사항**:
1. PHP 에러 로그:
   ```bash
   tail -f /var/log/php/error.log | grep "DB save failed"
   ```
2. DB 연결 확인:
   ```php
   global $DB;
   var_dump($DB->get_manager()->table_exists('abessi_tailoredcontents'));
   ```

**해결책**:
- DB 권한 확인 (INSERT, UPDATE 권한)
- Moodle 설정 파일 확인 (/config.php)
- 테이블 이름 확인 (prefix 'mdl_' 포함 여부)

### 문제 4: 수식 렌더링 실패

**증상**: 수식이 텍스트 그대로 표시됨 (`\(x^2\)` 형태)

**확인 사항**:
1. MathJax CDN 로드 확인:
   ```javascript
   console.log('MathJax:', typeof MathJax);
   ```
2. LaTeX 표기법 확인 (MATH_RENDERING_GUIDE.md 참조)
3. `MathJax.typesetPromise()` 호출 확인

**해결책**:
- MathJax CDN URL 확인
- AI 프롬프트의 LaTeX 규칙 준수 확인
- 동적 렌더링 코드 확인

---

## 📊 성능 벤치마크

### 예상 응답 시간

| 작업 | 예상 시간 | 비고 |
|------|----------|------|
| 페이지 로드 | < 2초 | MathJax CDN 로드 포함 |
| AI 생성 (자세히 생각하기 + 질문) | 5-10초 | OpenAI API 응답 시간 |
| AI 생성 (질문 답변) | 3-5초 | 짧은 답변 |
| DB 저장/업데이트 | < 100ms | 인덱스 사용 |
| 수식 렌더링 | < 500ms | 수식 개수에 따라 변동 |

### 리소스 사용량

- **AI API 토큰 사용량**:
  - 자세히 생각하기: ~1000 tokens
  - 질문 답변: ~500 tokens
  - 하루 예상: ~10,000 tokens (20회 생성 기준)

- **DB 스토리지**:
  - 레코드당 평균: ~5KB
  - 100개 구간 기준: ~500KB

---

## ✅ 체크리스트

### 배포 전 필수 확인

- [ ] DB 테이블 생성 완료
- [ ] OpenAI API 키 설정 확인
- [ ] 모든 파일 서버에 업로드 완료
- [ ] 파일 권한 설정 (644 for PHP, 755 for directories)
- [ ] MathJax CDN 로드 확인
- [ ] 브라우저 콘솔 에러 없음
- [ ] PHP 에러 로그 확인

### 기능 테스트 완료

- [ ] Phase 1: 테이블 생성 확인
- [ ] Phase 2: AI 생성 "자세히 생각하기" 정상 작동
- [ ] Phase 3: AI 생성 "질문 답변" 정상 작동
- [ ] Phase 4: nstep 격리 확인
- [ ] 수식 렌더링 확인
- [ ] 모바일 반응형 확인

---

## 📚 관련 문서

- **MATH_RENDERING_GUIDE.md**: 수식 표기법 가이드
- **IMPLEMENTATION_UPDATE.md**: 구현 내역 상세
- **drillingmath.php**: 메인 페이지 소스
- **generate_detailed_thinking.php**: AI 생성 API (자세히 생각하기)
- **get_additional_answer.php**: AI 생성 API (질문 답변)

---

## 📞 문제 발생 시

**에러 로그 확인**:
```bash
# PHP 에러 로그
tail -f /var/log/php/error.log

# Apache 에러 로그
tail -f /var/log/apache2/error.log

# Moodle 에러 로그
tail -f /home/moodle/public_html/moodle/error.log
```

**DB 쿼리 디버깅**:
```sql
-- 최근 생성된 레코드 확인
SELECT * FROM mdl_abessi_tailoredcontents
ORDER BY timecreated DESC
LIMIT 10;

-- 특정 컨텐츠의 모든 구간 확인
SELECT nstep, qstn1, LEFT(ans1, 50)
FROM mdl_abessi_tailoredcontents
WHERE contentsid = 29566 AND contentstype = 1
ORDER BY nstep;
```

---

**최종 업데이트**: 2025-01-25
**작성자**: Claude Code
**버전**: 1.0
