# 맞춤형 컨텐츠 시스템 배포 완료 가이드

## 📦 배포 패키지 개요

**프로젝트**: AI 기반 맞춤형 학습 컨텐츠 생성 시스템
**날짜**: 2025-01-25
**버전**: 1.0
**상태**: ✅ 배포 준비 완료

---

## 🎯 시스템 개요

### 핵심 기능

1. **AI 생성 "자세히 생각하기"**
   - 현재 학습 구간에 집중된 심화 설명 자동 생성
   - OpenAI GPT-4o-mini 사용
   - 전체 대본 컨텍스트 기반, 현재 구간 집중

2. **동적 질문 생성**
   - 구간별 3개의 관련 질문 자동 생성
   - 학습자의 이해도 심화를 위한 질문 설계
   - 다른 영역 확장 없이 현재 절차/구조 집중

3. **실시간 답변 생성**
   - 질문 클릭 시 즉시 AI 답변 생성
   - 토글 기능 (show/hide)
   - 생성된 답변 DB 저장 및 재사용

4. **수식 렌더링**
   - MathJax 3.x 통합
   - 표준화된 LaTeX 표기법 (`\(` `\)`, `\[` `\]`)
   - 동적 콘텐츠 자동 렌더링

5. **데이터 영속성**
   - 구간별(nstep) 데이터 분리 저장
   - 질문과 답변 독립적 관리
   - 타임스탬프 기반 이력 관리

---

## 📂 파일 구조

### 핵심 파일 (총 8개)

```
/mnt/c/1 Project/augmented_teacher/books/
│
├── drillingmath.php                          # 메인 페이지 (752 라인)
│   ├── 2-column layout (좌: 이미지+subtitle, 우: AI 생성 컨텐츠)
│   ├── MathJax 3.x 설정
│   ├── 자동 AI 생성 호출
│   └── 동적 질문/답변 토글
│
├── generate_detailed_thinking.php            # AI 생성 API #1 (266 라인)
│   ├── "자세히 생각하기" 생성
│   ├── 추가 질문 3개 생성
│   ├── DB 저장 (qstn0-3)
│   └── LaTeX 규칙 준수
│
├── get_additional_answer.php                 # AI 생성 API #2 (196 라인)
│   ├── 질문 답변 생성
│   ├── DB 업데이트 (ans0-3)
│   └── LaTeX 규칙 준수
│
├── create_tailored_contents_table.php        # DB 테이블 생성 (105 라인)
│   ├── mdl_abessi_tailoredcontents 생성
│   ├── UNIQUE KEY 제약조건
│   └── 인덱스 5개 생성
│
├── test_runner.php                           # 자동 테스트 러너 (신규)
│   ├── Phase 1: DB 테이블 확인
│   ├── Phase 2: CRUD 작업 테스트
│   └── Phase 3: API 파일 확인
│
├── TESTING_GUIDE.md                          # 테스트 가이드 (상세)
│   ├── 4-phase 테스트 절차
│   ├── SQL 쿼리 샘플
│   ├── 오류 대응 가이드
│   └── 성능 벤치마크
│
├── MATH_RENDERING_GUIDE.md                   # 수식 표기 가이드
│   ├── LaTeX 명령어 레퍼런스
│   ├── AI 프롬프트 규칙
│   └── 디버깅 가이드
│
└── DEPLOYMENT_COMPLETE.md                    # 이 문서
```

---

## 🗄️ 데이터베이스 스키마

### 테이블: `mdl_abessi_tailoredcontents`

```sql
CREATE TABLE mdl_abessi_tailoredcontents (
    id               BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    contentstype     TINYINT(2) NOT NULL DEFAULT 0,      -- 1=icontent, 2=question
    contentsid       BIGINT(10) NOT NULL DEFAULT 0,      -- 컨텐츠 ID
    nstep            INT(5) NOT NULL DEFAULT 0,          -- 구간 번호 (1,2,3...)

    qstn0            LONGTEXT,                           -- 자세히 생각하기
    qstn1            TEXT,                               -- 추가 질문 1
    qstn2            TEXT,                               -- 추가 질문 2
    qstn3            TEXT,                               -- 추가 질문 3

    ans0             LONGTEXT,                           -- 자세히 생각하기 답변
    ans1             LONGTEXT,                           -- 추가 질문 1 답변
    ans2             LONGTEXT,                           -- 추가 질문 2 답변
    ans3             LONGTEXT,                           -- 추가 질문 3 답변

    timemodified     BIGINT(10) NOT NULL DEFAULT 0,      -- 수정 시간 (unixtime)
    timecreated      BIGINT(10) NOT NULL DEFAULT 0,      -- 생성 시간 (unixtime)

    UNIQUE KEY unique_content_step (contentsid, contentstype, nstep),
    KEY idx_contentsid (contentsid),
    KEY idx_contentstype (contentstype),
    KEY idx_nstep (nstep),
    KEY idx_timecreated (timecreated),
    KEY idx_timemodified (timemodified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**핵심 특징**:
- UNIQUE KEY: 동일 컨텐츠+구간 중복 방지
- 인덱스 5개: 빠른 조회 성능
- LONGTEXT: AI 생성 장문 저장
- Unixtime: 언어 독립적 시간 저장

---

## 🚀 배포 절차 (3단계)

### Step 1: 사전 확인 (5분)

**환경 체크리스트**:
- [ ] PHP 7.1.9 설치 확인
- [ ] MySQL 5.7 연결 확인
- [ ] Moodle 3.7 정상 작동 확인
- [ ] OpenAI API 키 유효성 확인
- [ ] 서버 디스크 용량 확인 (최소 10MB)

**명령어**:
```bash
# PHP 버전 확인
php -v

# MySQL 연결 확인
mysql -u [user] -p -e "SHOW DATABASES;"

# 디스크 용량 확인
df -h /home/moodle/public_html/moodle/
```

### Step 2: 파일 배포 (2분)

**모든 파일이 이미 올바른 위치에 있습니다**:
```bash
/home/moodle/public_html/moodle/local/augmented_teacher/books/
├── drillingmath.php
├── generate_detailed_thinking.php
├── get_additional_answer.php
├── create_tailored_contents_table.php
├── test_runner.php
└── [문서 파일들]
```

**파일 권한 설정**:
```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/books/
chmod 644 *.php *.md
chmod 755 .
```

### Step 3: 테이블 생성 및 테스트 (5분)

#### 3.1 테이블 생성

**URL 접속**:
```
https://mathking.kr/moodle/local/augmented_teacher/books/create_tailored_contents_table.php
```

**예상 결과**:
```
[Success] 테이블 'abessi_tailoredcontents'이(가) 성공적으로 생성되었습니다.
```

#### 3.2 자동 테스트 실행

**URL 접속**:
```
https://mathking.kr/moodle/local/augmented_teacher/books/test_runner.php
```

**확인 사항**:
- Phase 1: 모든 테스트 PASS (3개)
- Phase 2: 모든 테스트 PASS (4개)
- Phase 3: 모든 파일 존재 확인 PASS (3개)

#### 3.3 실제 동작 테스트

**URL 접속**:
```
https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=29566&ctype=1&section=0&nstep=1
```

**체크리스트**:
1. [ ] 페이지 로드 시 로딩 스피너 표시
2. [ ] "자세히 생각하기" 내용 자동 생성 (5-10초)
3. [ ] 추가 질문 3개 버튼 표시
4. [ ] 질문 버튼 클릭 시 답변 생성 (3-5초)
5. [ ] 수식 렌더링 정상 (LaTeX 표기)
6. [ ] DB 저장 확인 (SQL 쿼리)

---

## 🔍 검증 쿼리

### 기본 확인

```sql
-- 테이블 존재 확인
SHOW TABLES LIKE 'mdl_abessi_tailoredcontents';

-- 테이블 구조 확인
DESCRIBE mdl_abessi_tailoredcontents;

-- 전체 레코드 수
SELECT COUNT(*) FROM mdl_abessi_tailoredcontents;
```

### 데이터 확인

```sql
-- 최근 생성된 레코드 (상세)
SELECT
    id,
    contentsid,
    contentstype,
    nstep,
    LEFT(qstn0, 100) as qstn0_preview,
    qstn1,
    qstn2,
    qstn3,
    CASE WHEN ans1 != '' THEN 'O' ELSE 'X' END as ans1_exists,
    CASE WHEN ans2 != '' THEN 'O' ELSE 'X' END as ans2_exists,
    CASE WHEN ans3 != '' THEN 'O' ELSE 'X' END as ans3_exists,
    FROM_UNIXTIME(timecreated) as created,
    FROM_UNIXTIME(timemodified) as modified
FROM mdl_abessi_tailoredcontents
ORDER BY timecreated DESC
LIMIT 5;
```

### 특정 컨텐츠 조회

```sql
-- 컨텐츠 29566의 모든 구간
SELECT
    nstep,
    qstn1,
    qstn2,
    qstn3,
    CHAR_LENGTH(ans1) as ans1_length,
    CHAR_LENGTH(ans2) as ans2_length,
    CHAR_LENGTH(ans3) as ans3_length,
    FROM_UNIXTIME(timemodified) as last_update
FROM mdl_abessi_tailoredcontents
WHERE contentsid = 29566 AND contentstype = 1
ORDER BY nstep;
```

---

## ⚙️ 설정

### OpenAI API 설정

**현재 설정 위치**:
- `generate_detailed_thinking.php` (라인 36)
- `get_additional_answer.php` (라인 38)

**API 키 변경 방법**:
```php
// 두 파일 모두 수정
$secret_key = 'your-new-api-key-here';
```

### 프롬프트 커스터마이징

**자세히 생각하기 프롬프트** (`generate_detailed_thinking.php` 라인 39-50):
```php
$prompt = "전체 대본 내용:\n{$context}\n\n";
if (!empty($subtitle)) {
    $prompt .= "현재 구간 내용:\n{$subtitle}\n\n";
}
$prompt .= "전체 대본 내용 중 현재 '자세히 생각하기' 부분의 내용을 효과적으로 분리하여...";
```

**답변 생성 프롬프트** (`get_additional_answer.php` 라인 41):
```php
$prompt = "다음은 수학 문제에 대한 설명입니다:\n\n{$context}\n\n학생의 질문: {$question}\n\n...";
```

---

## 📊 성능 모니터링

### 응답 시간 목표

| 작업 | 목표 | 허용 범위 |
|------|------|-----------|
| 페이지 로드 | < 2초 | < 5초 |
| AI 생성 (자세히) | 5-10초 | < 15초 |
| AI 생성 (답변) | 3-5초 | < 10초 |
| DB 저장 | < 100ms | < 500ms |
| 수식 렌더링 | < 500ms | < 2초 |

### 모니터링 방법

**브라우저 콘솔**:
```javascript
// Performance API 사용
const start = performance.now();
// ... 작업 실행 ...
const end = performance.now();
console.log('Execution time:', end - start, 'ms');
```

**PHP 에러 로그**:
```bash
# 실시간 로그 모니터링
tail -f /var/log/php/error.log | grep "generate_detailed_thinking\|get_additional_answer"
```

### API 사용량 추적

**OpenAI Dashboard**:
- URL: https://platform.openai.com/usage
- 일일 토큰 사용량 확인
- 비용 모니터링

**예상 사용량** (일일 20회 생성 기준):
- 자세히 생각하기: ~1,000 tokens × 20회 = 20,000 tokens
- 질문 답변: ~500 tokens × 60회 = 30,000 tokens
- **총 예상**: ~50,000 tokens/day

---

## 🚨 문제 해결

### 일반적인 문제

#### 문제 1: "테이블이 존재하지 않습니다"

**원인**: 테이블 생성 스크립트 미실행

**해결**:
```
1. create_tailored_contents_table.php 실행
2. test_runner.php로 확인
```

#### 문제 2: AI 생성 무한 로딩

**원인**: OpenAI API 오류, 네트워크 문제, API 키 유효하지 않음

**해결**:
```bash
# 1. PHP 에러 로그 확인
tail -n 50 /var/log/php/error.log

# 2. API 키 확인
# generate_detailed_thinking.php, get_additional_answer.php 파일 확인

# 3. 네트워크 연결 확인
curl -I https://api.openai.com/v1/chat/completions
```

#### 문제 3: 수식 렌더링 실패

**원인**: MathJax CDN 로드 실패, LaTeX 표기법 오류

**해결**:
```javascript
// 브라우저 콘솔에서 확인
console.log('MathJax:', typeof MathJax);

// MathJax 재로드
if (typeof MathJax !== 'undefined') {
    MathJax.typesetPromise();
}
```

#### 문제 4: DB 저장 실패

**원인**: 권한 문제, 테이블 구조 문제, UNIQUE KEY 중복

**해결**:
```sql
-- 권한 확인
SHOW GRANTS FOR CURRENT_USER;

-- UNIQUE KEY 중복 확인
SELECT contentsid, contentstype, nstep, COUNT(*)
FROM mdl_abessi_tailoredcontents
GROUP BY contentsid, contentstype, nstep
HAVING COUNT(*) > 1;

-- 중복 레코드 삭제 (주의: 최신 것만 남기고 삭제)
DELETE t1 FROM mdl_abessi_tailoredcontents t1
INNER JOIN mdl_abessi_tailoredcontents t2
WHERE t1.id < t2.id
  AND t1.contentsid = t2.contentsid
  AND t1.contentstype = t2.contentstype
  AND t1.nstep = t2.nstep;
```

### 긴급 롤백 절차

**데이터 백업**:
```sql
-- 테이블 백업
CREATE TABLE mdl_abessi_tailoredcontents_backup AS
SELECT * FROM mdl_abessi_tailoredcontents;
```

**테이블 삭제** (주의: 모든 데이터 손실):
```sql
DROP TABLE IF EXISTS mdl_abessi_tailoredcontents;
```

**파일 롤백** (이전 버전 복원):
```bash
# Git 사용 시
git checkout HEAD~1 -- drillingmath.php

# 백업 파일 사용 시
cp drillingmath.php.bak drillingmath.php
```

---

## 📈 향후 개선 사항

### 단기 개선 (1-2주)

1. **캐싱 시스템**
   - Redis/Memcached 통합
   - AI 생성 결과 캐싱 (1시간 TTL)
   - DB 쿼리 결과 캐싱

2. **에러 알림**
   - 이메일 알림 (API 실패 시)
   - Slack/Discord 웹훅 통합
   - 관리자 대시보드

3. **사용자 피드백**
   - 답변 유용성 평가 (👍/👎)
   - 추가 질문 요청 기능
   - 학습 진도 추적

### 중기 개선 (1-3개월)

1. **AI 모델 업그레이드**
   - GPT-4 Turbo 통합
   - 응답 품질 비교 A/B 테스트
   - 프롬프트 최적화

2. **다국어 지원**
   - 영어, 일본어, 중국어
   - 자동 언어 감지
   - 번역 API 통합

3. **성능 최적화**
   - DB 쿼리 최적화
   - 페이지 로딩 속도 개선
   - CDN 통합

### 장기 개선 (3-6개월)

1. **개인화 학습**
   - 학습자별 맞춤형 질문 생성
   - 학습 패턴 분석
   - 추천 시스템

2. **콘텐츠 관리 시스템**
   - 관리자 UI
   - 벌크 생성/수정/삭제
   - 버전 관리

3. **통계 및 분석**
   - 학습자 행동 분석
   - AI 생성 품질 메트릭
   - ROI 측정

---

## ✅ 최종 체크리스트

### 배포 전

- [ ] 모든 파일 서버에 업로드 완료
- [ ] 파일 권한 설정 완료 (644)
- [ ] OpenAI API 키 설정 확인
- [ ] DB 연결 테스트 통과
- [ ] test_runner.php 모든 테스트 PASS

### 배포 후

- [ ] 테이블 생성 성공 확인
- [ ] 실제 페이지 로드 테스트 통과
- [ ] AI 생성 정상 작동 확인
- [ ] DB 저장 확인 (SQL 쿼리)
- [ ] 수식 렌더링 확인
- [ ] 브라우저 콘솔 에러 없음
- [ ] PHP 에러 로그 확인

### 사용자 교육

- [ ] 사용 방법 안내
- [ ] 주의사항 공지
- [ ] 피드백 채널 안내

---

## 📞 지원

### 문서

- **TESTING_GUIDE.md**: 상세 테스트 절차
- **MATH_RENDERING_GUIDE.md**: 수식 표기 가이드
- **IMPLEMENTATION_UPDATE.md**: 구현 상세 내역

### 로그 위치

```bash
# PHP 에러 로그
/var/log/php/error.log

# Apache 에러 로그
/var/log/apache2/error.log

# Moodle 에러 로그
/home/moodle/public_html/moodle/error.log
```

### 유용한 명령어

```bash
# 실시간 로그 모니터링
tail -f /var/log/php/error.log | grep "generate_detailed_thinking\|get_additional_answer\|drillingmath"

# DB 레코드 수 확인
mysql -u [user] -p -e "SELECT COUNT(*) FROM moodle.mdl_abessi_tailoredcontents;"

# 디스크 사용량
du -sh /home/moodle/public_html/moodle/local/augmented_teacher/books/
```

---

## 🎉 완료!

시스템 배포가 완료되었습니다. 위 절차를 따라 테스트를 진행하고, 문제 발생 시 문제 해결 섹션을 참조하세요.

**배포 성공 기준**:
- ✅ test_runner.php 모든 테스트 PASS
- ✅ drillingmath.php 정상 로드 및 AI 생성 작동
- ✅ DB에 데이터 정상 저장
- ✅ 수식 렌더링 정상
- ✅ 에러 로그 없음

**다음 단계**:
1. 실제 사용자 테스트 진행
2. 피드백 수집
3. 성능 모니터링
4. 향후 개선 사항 검토

---

**작성일**: 2025-01-25
**작성자**: Claude Code
**버전**: 1.0
**상태**: ✅ 배포 준비 완료
