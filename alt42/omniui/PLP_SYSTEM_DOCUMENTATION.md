# 📚 PLP (Personal Learning Panel) 시스템 완전 문서

## 📋 목차
1. [시스템 개요](#시스템-개요)
2. [주요 기능](#주요-기능)
3. [시스템 아키텍처](#시스템-아키텍처)
4. [데이터베이스 구조](#데이터베이스-구조)
5. [파일 구조](#파일-구조)
6. [API 엔드포인트](#api-엔드포인트)
7. [설치 및 배포](#설치-및-배포)
8. [사용 방법](#사용-방법)
9. [문제 해결](#문제-해결)
10. [개발 이력](#개발-이력)

---

## 🎯 시스템 개요

### 목적
PLP(Personal Learning Panel)는 수학 학습이 부진한 학생들의 학습 습관 개선을 위한 개인화된 학습 관리 시스템입니다. 특히 미적분학을 학습하는 이현선 학생을 위해 설계되었으며, 매일의 학습 활동을 체계적으로 기록하고 추적합니다.

### 핵심 가치
- **학습 습관 형성**: 매일 꾸준한 학습을 유도
- **7:3 황금비율**: 진도 70%, 복습 30%의 이상적 학습 비율 유지
- **시각적 피드백**: 실시간 통계와 그래프로 학습 현황 파악
- **동기부여**: 연속 학습 스트릭과 보상 시스템

### 대상 사용자
- **주 사용자**: 이현선 (User ID: 2) - 미적분학 학습자
- **부가 사용자**: 학습이 부진한 수학 과목 학생들
- **관리자**: 교사 및 학부모

---

## 🚀 주요 기능

### 1. 학습 요약 기록
- **일일 학습 요약** (30-60자)
- **진도/복습 시간 자동 계산**
- **날짜별 학습 이력 관리**
- **중복 방지 및 수정 기능**

### 2. 오답 태그 관리
- **문제별 오답 원인 분석**
- **태그 기반 약점 파악**
- **난이도 설정 (1-5)**
- **태그 통계 및 빈도 분석**

### 3. 문제 풀이 체크리스트
- **실제 시험 문제 연동**
- **일일 문제 풀이 목록**
- **체크 개수 자동 집계**
- **mdl_alt42t_exam_resources 연동**

### 4. 연속 학습 스트릭
- **현재 연속 학습일 추적**
- **최고 기록 갱신**
- **마지막 학습일 표시**
- **동기부여 메시지**

### 5. 실시간 통계 대시보드
- **총 학습 시간**
- **7:3 비율 시각화**
- **주간/월간 통계**
- **5초 자동 새로고침**

---

## 🏗️ 시스템 아키텍처

### 기술 스택
```
Frontend:
├── HTML5
├── CSS3 (Gradient Design)
├── JavaScript (ES6+)
├── jQuery 3.6.0
├── Bootstrap Icons
└── Responsive Design

Backend:
├── PHP 7.4+
├── MySQL 5.7+
├── PDO Database Driver
├── AJAX JSON API
└── Session Management

Integration:
├── Moodle LMS
├── MathKing Platform
└── Alt42t Exam System
```

### 시스템 구성도
```
┌─────────────────────────────────────────┐
│            사용자 인터페이스              │
│  (HTML/CSS/JavaScript)                   │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│         AJAX API Layer                   │
│  (ajax_*.php endpoints)                  │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│      PHP Business Logic                  │
│  (plp_full_fixed.php)                   │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│         Database Layer                   │
│   ┌──────────────┬──────────────┐       │
│   │  MathKing DB │  Alt42t DB   │       │
│   └──────────────┴──────────────┘       │
└─────────────────────────────────────────┘
```

---

## 💾 데이터베이스 구조

### 연결 정보
```php
// MathKing 메인 데이터베이스
Host: 58.180.27.46
Database: mathking
User: moodle
Password: @MCtrigd7128
Charset: utf8mb4
Prefix: mdl_
```

### PLP 전용 테이블

#### 1. mdl_plp_learning_records (학습 기록)
```sql
CREATE TABLE mdl_plp_learning_records (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) NOT NULL,            -- 사용자 ID
    date DATE NOT NULL,                    -- 학습 날짜
    summary TEXT,                           -- 학습 요약 (30-60자)
    advance_mins INT DEFAULT 0,            -- 진도 시간(분)
    review_mins INT DEFAULT 0,             -- 복습 시간(분)
    summary_count INT DEFAULT 0,           -- 요약 개수
    timecreated BIGINT(10) NOT NULL,      -- 생성 시간
    timemodified BIGINT(10) NOT NULL,     -- 수정 시간
    UNIQUE KEY user_date (userid, date),
    KEY user_idx (userid),
    KEY date_idx (date)
);
```

#### 2. mdl_plp_error_tags (오답 태그)
```sql
CREATE TABLE mdl_plp_error_tags (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) NOT NULL,            -- 사용자 ID
    problem_id VARCHAR(50) NOT NULL,       -- 문제 ID
    problem_text TEXT,                      -- 문제 내용
    tags TEXT,                              -- 태그 목록 (쉼표 구분)
    difficulty TINYINT(1) DEFAULT 1,       -- 난이도 (1-5)
    timecreated BIGINT(10) NOT NULL,      -- 생성 시간
    KEY user_idx (userid),
    KEY problem_idx (problem_id)
);
```

#### 3. mdl_plp_streak_tracker (연속 학습 추적)
```sql
CREATE TABLE mdl_plp_streak_tracker (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) NOT NULL,            -- 사용자 ID
    current_streak INT DEFAULT 0,          -- 현재 연속일
    best_streak INT DEFAULT 0,             -- 최고 기록
    last_pass_date DATE,                   -- 마지막 학습일
    timemodified BIGINT(10) NOT NULL,     -- 수정 시간
    UNIQUE KEY user_unique (userid)
);
```

#### 4. mdl_plp_practice_checks (문제 체크)
```sql
CREATE TABLE mdl_plp_practice_checks (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) NOT NULL,            -- 사용자 ID
    date DATE NOT NULL,                    -- 체크 날짜
    problem_ids TEXT,                       -- 문제 ID 목록
    problem_texts TEXT,                     -- 문제 내용 목록
    checked_count INT DEFAULT 0,           -- 체크 개수
    timecreated BIGINT(10) NOT NULL,      -- 생성 시간
    KEY user_date_idx (userid, date)
);
```

### 연관 테이블 (기존 MathKing)

#### mdl_user (사용자 정보)
- id: 사용자 ID
- username: 로그인 ID
- firstname, lastname: 이름
- email: 이메일

#### mdl_alt42t_exam_resources (시험 자료)
- resource_id: 자료 ID
- title: 제목
- file_url: 파일 URL
- tip_text: 팁 텍스트

---

## 📁 파일 구조

### 프로젝트 디렉토리
```
/mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/
├── 📄 plp_full_fixed.php          # 메인 애플리케이션 (최종 수정본)
├── 📄 plp_full.php                # 메인 애플리케이션 (원본)
├── 📄 plp_setup_db.php            # 데이터베이스 설정 스크립트
├── 📄 plp_create_tables.sql       # 테이블 생성 SQL
├── 📄 plp_test_complete.php       # 시스템 테스트 페이지
├── 📄 deploy_plp_complete.sh      # 배포 스크립트
│
├── 📂 ajax_handlers/              # AJAX 처리 파일
│   ├── ajax_save_summary.php     # 학습 요약 저장
│   ├── ajax_save_error_tag.php   # 오답 태그 저장
│   ├── ajax_save_check.php       # 문제 체크 저장
│   ├── ajax_save_streak.php      # 연속 학습 업데이트
│   └── ajax_get_stats.php        # 통계 조회
│
├── 📄 CLAUDE.md                   # MathKing 시스템 개발 가이드
├── 📄 PLP_SYSTEM_DOCUMENTATION.md # 이 문서
└── 📄 README.md                   # 프로젝트 README
```

### 웹 접근 경로
```
Base URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/

주요 URL:
├── plp_full_fixed.php     # 메인 시스템
├── plp_setup_db.php        # DB 설정
├── plp_test_complete.php  # 시스템 테스트
├── ajax_save_summary.php   # 요약 저장 API
├── ajax_save_error_tag.php # 태그 저장 API
├── ajax_save_check.php     # 체크 저장 API
├── ajax_save_streak.php    # 스트릭 API
└── ajax_get_stats.php      # 통계 API
```

---

## 🔌 API 엔드포인트

### 1. 학습 요약 저장
**Endpoint**: `ajax_save_summary.php`
```javascript
POST /ajax_save_summary.php
Content-Type: application/json

Request:
{
    "user_id": 2,
    "summary": "오늘은 극한과 연속성 개념을 학습하고 문제를 풀었습니다",
    "date": "2024-01-15"
}

Response:
{
    "success": true,
    "message": "저장되었습니다",
    "advance_mins": 42,
    "review_mins": 18
}
```

### 2. 오답 태그 저장
**Endpoint**: `ajax_save_error_tag.php`
```javascript
POST /ajax_save_error_tag.php
Content-Type: application/json

Request:
{
    "user_id": 2,
    "problem_id": "calc_001",
    "problem_text": "lim(x→0) sin(x)/x = ?",
    "tags": "극한,삼각함수,계산실수",
    "difficulty": 3
}

Response:
{
    "success": true,
    "message": "태그가 저장되었습니다"
}
```

### 3. 문제 체크 저장
**Endpoint**: `ajax_save_check.php`
```javascript
POST /ajax_save_check.php
Content-Type: application/json

Request:
{
    "user_id": 2,
    "problem_id": "calc_001",
    "problem_text": "극한 문제",
    "checked": true
}

Response:
{
    "success": true,
    "checked_count": 5
}
```

### 4. 연속 학습 업데이트
**Endpoint**: `ajax_save_streak.php`
```javascript
POST /ajax_save_streak.php
Content-Type: application/json

Request:
{
    "user_id": 2,
    "passed": true
}

Response:
{
    "success": true,
    "current_streak": 7,
    "best_streak": 14,
    "message": "7일 연속 학습 중! 최고 기록: 14일"
}
```

### 5. 통계 조회
**Endpoint**: `ajax_get_stats.php`
```javascript
GET /ajax_get_stats.php?user_id=2

Response:
{
    "total_time": 245,
    "advance_ratio": 70,
    "review_ratio": 30,
    "weekly_avg": 35,
    "study_days": 25,
    "current_streak": 7,
    "best_streak": 14
}
```

---

## 🛠️ 설치 및 배포

### 1. 요구사항
- PHP 7.4 이상
- MySQL 5.7 이상
- Apache/Nginx 웹서버
- Moodle LMS (선택사항)

### 2. 설치 단계

#### Step 1: 파일 업로드
```bash
# 파일을 웹 서버로 복사
scp -r /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/* \
    user@mathking.kr:/home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/
```

#### Step 2: 데이터베이스 설정
```bash
# 옵션 1: 웹 인터페이스 사용
https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_setup_db.php

# 옵션 2: SQL 직접 실행
mysql -h 58.180.27.46 -u moodle -p mathking < plp_create_tables.sql
```

#### Step 3: 권한 설정
```bash
# 웹 서버 쓰기 권한 설정
chmod 755 /path/to/omniui/
chown -R www-data:www-data /path/to/omniui/
```

#### Step 4: 테스트
```bash
# 시스템 테스트 페이지 접속
https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_test_complete.php
```

### 3. 배포 스크립트
```bash
# 자동 배포 스크립트 실행
chmod +x deploy_plp_complete.sh
./deploy_plp_complete.sh
```

---

## 📖 사용 방법

### 1. 시스템 접속
1. 브라우저에서 접속: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_full_fixed.php
2. Moodle 로그인 (필요시)
3. 자동으로 사용자 정보 로드

### 2. 학습 요약 작성
1. "오늘의 학습 요약" 섹션에서 30-60자로 요약 작성
2. "저장" 버튼 클릭
3. 자동으로 진도/복습 시간 계산 및 표시

### 3. 오답 태그 추가
1. "오답 태그" 섹션에서 문제 선택
2. 오답 원인 태그 입력 (쉼표로 구분)
3. 난이도 선택 (1-5)
4. "태그 추가" 클릭

### 4. 문제 체크
1. "문제 풀이 체크" 섹션에서 푼 문제 체크
2. 자동으로 체크 개수 집계
3. 실시간으로 서버에 저장

### 5. 연속 학습 기록
1. 오늘 학습 완료시 "오늘도 통과!" 클릭
2. 연속 학습일 자동 업데이트
3. 최고 기록 갱신시 축하 메시지

### 6. 통계 확인
1. 상단 대시보드에서 실시간 통계 확인
2. 5초마다 자동 새로고침
3. 진도/복습 비율 시각적 확인

---

## 🔧 문제 해결

### 일반적인 문제

#### 1. 데이터베이스 연결 오류
```
Error: SQLSTATE[HY000] [2002] Connection refused
```
**해결**: 
- DB 서버 상태 확인
- 방화벽 설정 확인
- config.php의 연결 정보 확인

#### 2. 테이블 없음 오류
```
Error: Table 'mathking.mdl_plp_learning_records' doesn't exist
```
**해결**:
- plp_setup_db.php 실행
- 또는 plp_create_tables.sql 직접 실행

#### 3. 권한 오류
```
Error: Permission denied
```
**해결**:
```bash
chmod 755 /path/to/files
chown www-data:www-data /path/to/files
```

#### 4. 세션 오류
**해결**:
- 쿠키 설정 확인
- session_start() 호출 확인
- PHP 세션 디렉토리 권한 확인

### 디버깅 팁

#### 1. 에러 로그 확인
```bash
tail -f /var/log/apache2/error.log
tail -f /var/log/php/error.log
```

#### 2. 데이터베이스 쿼리 테스트
```sql
-- 연결 테스트
SELECT 1;

-- 테이블 확인
SHOW TABLES LIKE 'mdl_plp_%';

-- 데이터 확인
SELECT * FROM mdl_plp_learning_records WHERE userid = 2;
```

#### 3. AJAX 디버깅
```javascript
// 브라우저 개발자 도구 콘솔에서
$.ajax({
    url: 'ajax_get_stats.php',
    data: {user_id: 2},
    success: console.log,
    error: console.error
});
```

---

## 📝 개발 이력

### 버전 히스토리

#### v1.0.0 (2024-01-15)
- 초기 시스템 구축
- 기본 UI/UX 설계
- 데이터베이스 스키마 생성

#### v1.1.0 (2024-01-15)
- 테이블 자동 생성 기능 추가
- 오류 처리 개선
- 실제 시험 데이터 연동

#### v1.2.0 (2024-01-15) - 최종 수정
- UI 크기 개선 (입력창 확대)
- 가시성 문제 수정 (이름/ID 색상)
- 통계 숫자 크기 조정
- 반응형 디자인 최적화

### 주요 개선사항
1. **데이터베이스 오류 해결**: 테이블 자동 생성 기능
2. **UI/UX 개선**: 크기 조정, 색상 대비 개선
3. **실시간 데이터**: Alt42t 시험 시스템 연동
4. **성능 최적화**: AJAX 캐싱, 쿼리 최적화

### 개발팀
- **시스템 설계**: Claude (AI Assistant)
- **요구사항 정의**: 사용자
- **테스트**: 이현선 학생 (User ID: 2)

---

## 🔗 관련 링크

### 시스템 URL
- **메인 시스템**: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_full_fixed.php
- **DB 설정**: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_setup_db.php
- **시스템 테스트**: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_test_complete.php

### 참고 문서
- **MathKing 시스템 가이드**: CLAUDE.md
- **Moodle 문서**: https://docs.moodle.org
- **PHP 문서**: https://www.php.net/manual
- **MySQL 문서**: https://dev.mysql.com/doc

### 지원 및 문의
- **시스템 관리자**: MathKing Admin
- **기술 지원**: Alt42 Team
- **데이터베이스**: mathking@58.180.27.46

---

## 📌 중요 참고사항

### 보안 고려사항
1. **SQL Injection 방지**: 모든 쿼리에 Prepared Statements 사용
2. **XSS 방지**: htmlspecialchars() 사용
3. **세션 보안**: session_regenerate_id() 정기 실행
4. **HTTPS 필수**: SSL/TLS 암호화 통신

### 성능 최적화
1. **인덱스 활용**: userid, date 컬럼에 인덱스
2. **쿼리 캐싱**: 자주 조회되는 통계 캐싱
3. **AJAX 배치**: 여러 요청을 하나로 묶기
4. **이미지 최적화**: SVG 아이콘 사용

### 유지보수
1. **백업**: 일일 데이터베이스 백업
2. **모니터링**: 에러 로그 정기 확인
3. **업데이트**: PHP/MySQL 보안 패치
4. **문서화**: 코드 변경사항 기록

### 확장 가능성
1. **다중 사용자**: userid 기반 확장 가능
2. **과목 확장**: 수학 외 타 과목 적용 가능
3. **모바일 앱**: API 기반 앱 개발 가능
4. **AI 분석**: 학습 패턴 분석 기능 추가 가능

---

## 🎯 향후 계획

### 단기 계획 (1-3개월)
- [ ] 모바일 반응형 개선
- [ ] 주간/월간 리포트 기능
- [ ] 학습 목표 설정 기능
- [ ] 알림 시스템

### 중기 계획 (3-6개월)
- [ ] AI 기반 학습 추천
- [ ] 친구 시스템 및 랭킹
- [ ] 학부모 대시보드
- [ ] 성취 배지 시스템

### 장기 계획 (6-12개월)
- [ ] 모바일 앱 개발
- [ ] 타 과목 확장
- [ ] 학교별 통계
- [ ] 빅데이터 분석

---

**문서 작성일**: 2024-01-15  
**최종 수정일**: 2024-01-15  
**버전**: 1.2.0

---

*이 문서는 PLP 시스템의 모든 측면을 다루는 공식 문서입니다. 시스템 사용, 개발, 유지보수에 필요한 모든 정보를 포함하고 있습니다.*