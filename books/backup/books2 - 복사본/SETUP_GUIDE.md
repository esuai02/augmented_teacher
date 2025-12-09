# 질문/답변 시스템 설정 가이드 v2.0

## 📋 시스템 개요

**DB 구조**: 기존 `mdl_abessi_tailoredcontents` 테이블 활용
- **qstn0**: 풀이 단계 전용 (기존 기능 유지)
- **qstn1-3, ans1-3**: 학생 질문/답변 (새로운 기능)

## 🚀 사용 방법

### drillingmath3.php (추천)
```
https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath3.php?cid=29565&ctype=1&nstep=1
```

**동작 방식**:
1. **풀이 단계 로드**: DB에서 `qstn0` 읽기
2. **Q&A 캐시 확인**: DB에서 `qstn1-3`, `ans1-3` 확인
3. **없으면 생성**: AI로 자동 생성 후 DB에 저장
4. **화면 표시**: 카드 기반 아코디언 UI로 표시

### 데이터 흐름
```
페이지 로드
    ↓
qstn0 (풀이 단계) 로드
    ↓
qstn1-3, ans1-3 확인
    ↓
있음 → DB에서 표시
없음 → AI 생성 → DB 저장 → 표시
```

## 📊 DB 테이블 구조

### mdl_abessi_tailoredcontents
기존 테이블 활용 (새 테이블 불필요)

**필드**:
- `id`: Primary key
- `contentsid`: 컨텐츠 ID
- `contentstype`: 컨텐츠 타입 (1=icontent, 2=question)
- `nstep`: 구간 번호
- **`qstn0`**: 풀이 단계 (TEXT) - 기존 기능
- **`qstn1-3`**: 학생 질문 1-3 (TEXT) - 신규
- **`ans1-3`**: 답변 1-3 (TEXT) - 신규
- `timecreated`: 생성 시간
- `timemodified`: 수정 시간

**데이터 예시**:
```sql
SELECT qstn0, qstn1, ans1, qstn2, ans2, qstn3, ans3
FROM mdl_abessi_tailoredcontents
WHERE contentsid=29565 AND contentstype=1 AND nstep=1;
```

## 🔍 문제 해결

### "풀이 단계 내용이 없습니다"
**원인**: DB에 qstn0 값이 없음
**해결**: drillingmath.php 또는 다른 도구로 먼저 풀이 단계 생성

### "질문 생성 실패: JSON 파싱 오류"
**원인**: AI 응답 형식 오류
**해결**:
1. 서버 로그 확인 (아래 명령어)
2. "Full AI Response" 로그에서 실제 AI 응답 확인
3. Regex fallback 로그 확인

```bash
# 서버 로그 확인
tail -f /var/log/apache2/error.log | grep generate_questions_with_answers

# 주요 로그:
# - Full AI Response: AI 원본 응답
# - Extracted JSON: 추출된 JSON
# - Regex fallback: 정규식 대체 시도
# - DB save error: DB 저장 오류
```

### "답변이 표시되지 않음"
**원인**: DB에 Q&A가 없고 AI 생성도 실패
**해결**:
1. 브라우저 콘솔(F12) → Console 탭 확인
2. `[drillingmath3.php:loadQuestions]` 로그 확인
3. "Using cached Q&A from DB" 또는 "generating with AI" 확인

## ⚙️ 디버깅 팁

### 브라우저 콘솔 로그
```javascript
// DB 캐시 사용
[drillingmath3.php:loadQuestions] Using cached Q&A from DB: [{...}]

// AI 생성
[drillingmath3.php:loadQuestions] No cached Q&A, generating with AI...
[drillingmath3.php:loadQuestions] API Response: {success: true, qa_pairs: [...]}
```

### 서버 로그 (PHP)
```bash
# 정상 흐름
[generate_questions_with_answers.php] File: ..., Line: 141, Full AI Response: {...}
[generate_questions_with_answers.php] File: ..., Line: 170, Extracted JSON: {...}
[generate_questions_with_answers.php] File: ..., Line: 252, Updated DB record id=123

# Regex fallback 사용
[generate_questions_with_answers.php] File: ..., Line: 180, JSON decode error: Syntax error, Attempting regex fallback
[generate_questions_with_answers.php] File: ..., Line: 192, Regex fallback successful, found 3 Q&A pairs
```

## 📞 추가 지원

문제가 지속되면:
1. 서버 로그 전체 내용 확인
2. 브라우저 콘솔 로그 스크린샷
3. DB 데이터 확인:
```sql
SELECT * FROM mdl_abessi_tailoredcontents
WHERE contentsid=29565 AND contentstype=1 AND nstep=1;
```
