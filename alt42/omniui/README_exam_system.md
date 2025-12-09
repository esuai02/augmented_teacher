# 수학킹 시험 대비 시스템 설정 가이드

## 시스템 개요
이 시스템은 학생들이 시험 정보를 입력하고 관리할 수 있는 PHP 기반 웹 애플리케이션입니다.

## 필요 사항
- PHP 7.4 이상
- MySQL 5.7 이상
- 웹 서버 (Apache/Nginx)

## 설치 방법

### 1. 데이터베이스 설정

#### Alt42t 데이터베이스 생성
```sql
mysql -u root -p < alt42t_schema.sql
```

#### 데이터베이스 연결 정보 수정
`config.php` 파일에서 Alt42t 데이터베이스 정보를 수정하세요:
```php
define('ALT42T_DB_HOST', 'localhost');    // 실제 호스트로 변경
define('ALT42T_DB_NAME', 'alt42t');        // 실제 데이터베이스명으로 변경
define('ALT42T_DB_USER', 'root');          // 실제 사용자명으로 변경
define('ALT42T_DB_PASS', '');              // 실제 비밀번호로 변경
```

### 2. 파일 구조
```
/omniui/
├── exam_system.php     # 메인 시험 설정 시스템
├── dashboard_new.php   # 대시보드
├── login.php          # 로그인 페이지
├── login_check.php    # 로그인 처리 로직
├── logout.php         # 로그아웃 처리
├── config.php         # 설정 파일
├── alt42t_schema.sql  # Alt42t 데이터베이스 스키마
└── README_exam_system.md  # 이 파일
```

### 3. 권한 설정
웹 서버가 파일을 읽을 수 있도록 권한을 설정하세요:
```bash
chmod 755 *.php
```

## 사용 방법

### 1. 로그인
- `login.php`에 접속하여 MathKing 계정으로 로그인합니다.
- MathKing(Moodle) 데이터베이스의 사용자 정보로 인증됩니다.

### 2. 시험 정보 설정
로그인 후 `exam_system.php`로 이동하여 다음 단계를 진행합니다:

#### Step 1: 정보입력
- 이름 (MathKing DB에서 자동 로드)
- 학교명 (MathKing DB에서 자동 로드)
- 학년 (출생년도 기반 자동 계산)
- 학기 (현재 날짜 기반 자동 설정)

#### Step 2: 시험설정
- 시험 종류 (현재 날짜 기반 자동 추천)
- 시험 시작일/종료일
- 수학 시험일
- 시험 범위
- 예상/확정 상태
- 같은 학교 학생들의 시험 정보 참조 가능

#### Step 3: 전략이해
- 라스트 청킹 전략 소개

#### Step 4: 단계선택
- 개념공부: 기본 개념부터 시작
- 개념복습: 배운 개념 복습
- 유형공부: 문제 유형 학습

#### Step 5: 시작하기
- 대시보드로 이동

### 3. 대시보드 사용
`dashboard_new.php`에서:
- 시험 정보 확인
- D-Day 확인
- 현재 학습 단계 확인
- 라스트 청킹 알림 (시험 3-5일 전)
- 빠른 액션 메뉴

## 데이터베이스 구조

### MathKing DB (읽기 전용)
- mdl_user: 사용자 정보
- mdl_user_info_data: 추가 사용자 정보 (학교 등)

### Alt42t DB
- student_exam_settings: 학생별 시험 설정 저장
- exam_settings: 학교별 공유 시험 정보
- user_sessions: 로그인 세션 관리
- schools: 학교 정보 및 홈페이지 URL

## 보안 고려사항
1. SQL Injection 방지를 위해 PDO prepared statements 사용
2. 세션 타임아웃 설정 (1시간)
3. 비밀번호는 bcrypt로 암호화 (Moodle 표준)
4. XSS 방지를 위해 htmlspecialchars() 사용

## 문제 해결

### 로그인이 안 되는 경우
1. MathKing DB 연결 정보 확인
2. 사용자 이름/이메일과 비밀번호 확인
3. PHP 세션 설정 확인

### 데이터가 저장되지 않는 경우
1. Alt42t DB 연결 정보 확인
2. 테이블이 제대로 생성되었는지 확인
3. PHP 에러 로그 확인

### 학교 정보가 나타나지 않는 경우
1. MathKing DB의 mdl_user_info_data 테이블 확인
2. 사용자 ID가 올바른지 확인

## 추가 개발 예정
- 학습 진도 추적 기능
- 친구들과 진도 비교
- 시험 자료 공유
- AI 튜터 통합