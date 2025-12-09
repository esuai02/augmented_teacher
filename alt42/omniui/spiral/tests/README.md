# Spiral Scheduler Test Suite

이 디렉토리는 Spiral Scheduler 시스템의 포괄적인 테스트 스위트를 포함합니다.

## 📋 테스트 구조

### PHPUnit 단위 테스트

```
tests/
├── RatioCalculatorTest.php          # 7:3 비율 계산 테스트
├── TimeAllocatorTest.php            # 시간 할당 로직 테스트
├── ConflictResolverTest.php         # 충돌 탐지/해결 테스트
├── SecurityTest.php                 # 보안 취약점 테스트
├── SpiralSchedulerIntegrationTest.php # 통합 테스트
├── phpunit.xml                      # PHPUnit 설정
└── bootstrap.php                    # 테스트 환경 설정
```

### Playwright E2E 테스트

```
tests/e2e/
├── schedule_editor.spec.js          # UI 편집기 E2E 테스트
├── security.spec.js                 # 보안 E2E 테스트
├── global-setup.js                  # 글로벌 셋업
├── global-teardown.js               # 글로벌 정리
└── playwright.config.js             # Playwright 설정
```

## 🚀 실행 방법

### 1. PHPUnit 테스트 실행

```bash
# 전체 테스트 스위트 실행
cd /path/to/local/spiral/tests
./vendor/bin/phpunit

# 특정 테스트 클래스 실행
./vendor/bin/phpunit RatioCalculatorTest.php

# 커버리지 포함 실행
./vendor/bin/phpunit --coverage-html coverage/html
```

### 2. Playwright E2E 테스트 실행

```bash
# 테스트 환경 설정
export TEST_BASE_URL="https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui"
export TEST_TEACHER_USER="teacher1"
export TEST_TEACHER_PASS="Teacher123!"
export TEST_STUDENT_USER="student1"
export TEST_STUDENT_PASS="Student123!"

# Playwright 설치 (최초 1회)
npm install @playwright/test
npx playwright install

# 전체 E2E 테스트 실행
npx playwright test

# 특정 브라우저에서 실행
npx playwright test --project=chromium

# 헤드풀 모드로 실행
npx playwright test --headed

# 디버그 모드로 실행
npx playwright test --debug
```

## 📊 테스트 커버리지

### PHPUnit 테스트 범위

#### RatioCalculatorTest.php
- ✅ α=0.7/β=0.3 목표 비율 달성 (±5% 허용오차)
- ✅ α=0.6/0.8 경계값 처리 확인
- ✅ 소수 후보군에서의 비율 조정
- ✅ 가중치 기반 분배 알고리즘
- ✅ 자동 조정 메커니즘 검증
- ✅ 엣지 케이스 (빈 후보군, 단일 후보)

#### TimeAllocatorTest.php
- ✅ 일일 시간 제한 준수
- ✅ 세션 지속시간 제약 (min/max)
- ✅ 가중치 기반 시간 분배
- ✅ 과목별 비율 분배 (수학 40%, 국어/영어 30%)
- ✅ 세션 간 휴식시간 확보
- ✅ 멀티데이 할당 최적화

#### ConflictResolverTest.php
- ✅ TIME_OVERLAP: 시간 중복 탐지
- ✅ PREREQUISITE: 선수학습 순서 위반
- ✅ COGNITIVE_LOAD: 인지적 부하 초과
- ✅ PHYSICAL_LIMIT: 물리적 한계 초과
- ✅ 충돌 해결 전략 (shift/shrink/move)
- ✅ 우선순위 기반 해결

#### SecurityTest.php
- ✅ CSRF 토큰 검증
- ✅ XSS 공격 방지
- ✅ SQL 인젝션 차단
- ✅ 교사 권한 강제
- ✅ 정보 노출 방지
- ✅ 세션 보안
- ✅ 파일 업로드 보안

#### SpiralSchedulerIntegrationTest.php
- ✅ 전체 스케줄 생성 워크플로
- ✅ 충돌 탐지/해결 통합
- ✅ 과목 분배 통합 검증
- ✅ 스케줄 수정 워크플로
- ✅ 대용량 데이터셋 성능
- ✅ 트랜잭션 동작

### Playwright E2E 테스트 범위

#### schedule_editor.spec.js
- ✅ 7:3 비율 스케줄 생성 UI
- ✅ 드래그앤드롭 세션 이동
- ✅ 충돌 탐지/해결 UI
- ✅ 스케줄 저장/발행
- ✅ 필터링/검색 기능
- ✅ 캘린더 뷰 상호작용
- ✅ 반응형 디자인 (모바일)
- ✅ 접근성 준수
- ✅ 성능 모니터링

#### security.spec.js
- ✅ CSRF 토큰 E2E 검증
- ✅ XSS 방지 실제 테스트
- ✅ SQL 인젝션 시도
- ✅ 권한 우회 시도
- ✅ 세션 보안 검증
- ✅ 정보 노출 확인
- ✅ 파일 업로드 보안
- ✅ 요청 빈도 제한
- ✅ CSP 헤더 검증
- ✅ 인증 우회 시도

## 🔧 테스트 환경 설정

### 필수 사항

1. **PHP 8.0+** with extensions:
   - PDO MySQL
   - JSON
   - mbstring

2. **Composer** for PHPUnit dependencies

3. **Node.js 16+** for Playwright

4. **MySQL/MariaDB** for test database

### 환경 변수

```bash
# PHPUnit 테스트용
export DB_HOST="localhost"
export DB_NAME="test_mathking"
export DB_USER="test_user"
export DB_PASS="test_pass"
export SETUP_TEST_DB="true"

# Playwright 테스트용
export TEST_BASE_URL="https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui"
export TEST_TEACHER_USER="teacher1"
export TEST_TEACHER_PASS="Teacher123!"
export TEST_STUDENT_USER="student1"
export TEST_STUDENT_PASS="Student123!"
export TEST_STUDENT_ID="123"
```

### 테스트 데이터 설정

```sql
-- 테스트용 사용자 생성 (Moodle DB)
INSERT INTO mdl_user (username, password, firstname, lastname, email, auth) VALUES
('teacher1', '$2y$10$...', '테스트', '교사', 'teacher1@test.com', 'manual'),
('student1', '$2y$10$...', '테스트', '학생', 'student1@test.com', 'manual');

-- 교사 권한 부여
INSERT INTO mdl_user_info_data (userid, fieldid, data) VALUES
(1, 22, 'editingteacher'),  -- teacher1
(2, 22, 'student');         -- student1
```

## 📈 CI/CD 통합

### GitHub Actions 예시

```yaml
name: Test Suite
on: [push, pull_request]

jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: ./vendor/bin/phpunit --coverage-clover coverage.xml
      
  playwright:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: npm install
      - run: npx playwright install
      - run: npx playwright test
```

## 🐛 디버깅 가이드

### PHPUnit 디버깅

```bash
# 상세 출력으로 실행
./vendor/bin/phpunit --verbose --debug

# 특정 테스트만 실행
./vendor/bin/phpunit --filter testStandardRatioAchievement

# 실패 시 즉시 중단
./vendor/bin/phpunit --stop-on-failure
```

### Playwright 디버깅

```bash
# 디버그 모드 (브라우저 열림)
npx playwright test --debug

# 특정 테스트만 실행
npx playwright test schedule_editor.spec.js

# 헤드풀 모드로 실행
npx playwright test --headed

# 트레이스 수집
npx playwright test --trace on
```

## 📋 테스트 결과 보고서

### 커버리지 보고서
- **PHPUnit HTML**: `coverage/html/index.html`
- **PHPUnit Clover**: `coverage/clover.xml`
- **Playwright**: `coverage/playwright-report/index.html`

### 성능 벤치마크
- 스케줄 생성: < 5초 (3개월치)
- 충돌 해결: < 2초
- UI 응답성: < 3초 (모든 상호작용)

## ✅ 품질 기준

### 통과 기준
- **코드 커버리지**: ≥ 80%
- **테스트 통과율**: 100%
- **성능**: 벤치마크 기준 미달 시 실패
- **보안**: 모든 보안 테스트 통과 필수
- **접근성**: WCAG 2.1 AA 준수

### 지속적 개선
- 새 기능 추가 시 테스트 케이스 추가 필수
- 버그 발견 시 재현 테스트 추가
- 성능 저하 시 벤치마크 업데이트
- 보안 이슈 시 관련 테스트 강화