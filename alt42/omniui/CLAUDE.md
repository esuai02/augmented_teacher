# MathKing 시스템 개발 가이드

## 📊 데이터베이스 구조

### 1. 데이터베이스 연결 정보

#### MathKing (메인 DB)
```php
Host: 58.180.27.46
Database: mathking
User: moodle
Password: @MCtrigd7128
Prefix: mdl_
Charset: utf8mb4
```

#### Alt42t (시험 시스템 DB)
```php
Host: localhost
Database: alt42t
User: root
Password: (empty)
Prefix: (none)
Charset: utf8mb4
```

### 2. 주요 테이블 구조

#### 2.1 MathKing 핵심 테이블

##### mdl_user (사용자)
- `id`: 사용자 ID (PRIMARY KEY)
- `username`: 로그인 아이디
- `password`: bcrypt 암호화된 비밀번호
- `firstname`: 이름
- `lastname`: 성
- `email`: 이메일
- `deleted`: 삭제 여부 (0: 활성, 1: 삭제)
- `phone1`: 연락처
- `phone2`: 부모 연락처

##### mdl_user_info_data (사용자 추가정보)
- `userid`: 사용자 ID
- `fieldid`: 필드 타입 ID
  - 22: 역할 (role) - 'student' 또는 교사 역할
  - attendance_stats: 출결 통계 (JSON)
- `data`: 저장된 데이터
- `dataformat`: 데이터 포맷 (기본값: 0)

##### mdl_user_info_field (사용자 정보 필드 정의)
- `id`: 필드 ID
- `shortname`: 필드 짧은 이름
- `name`: 필드 이름
- `datatype`: 데이터 타입
- `description`: 설명
- `categoryid`: 카테고리 ID

##### mdl_abessi_attendance_record (출결 기록)
```sql
- id: bigint(10) PRIMARY KEY AUTO_INCREMENT
- userid: bigint(10) - 학생 ID
- teacherid: bigint(10) - 교사 ID  
- type: varchar(50) - absence, makeup_complete, add_absence 등
- reason: varchar(255) - 사유
- hours: decimal(5,2) - 시간
- date: date - 날짜
- timecreated: bigint(10) - 생성 시간
```

##### mdl_abessi_attendance_log (출결 로그)
```sql
- id: bigint(10) PRIMARY KEY AUTO_INCREMENT
- userid: bigint(10) - 학생 ID
- teacherid: bigint(10) - 교사 ID
- action: varchar(50) - 액션 타입
- data: text - 추가 데이터 (JSON)
- timecreated: bigint(10) - 생성 시간
```

##### mdl_abessi_alert_log (알림 로그)
```sql
- id: bigint(10) PRIMARY KEY AUTO_INCREMENT
- alertid: varchar(50) - 알림 ID
- teacherid: bigint(10) - 교사 ID
- action: varchar(50) - 액션 타입
- timecreated: bigint(10) - 생성 시간
```

##### mdl_abessi_schedule (스케줄 정보)
```sql
- id: bigint(10) PRIMARY KEY
- userid: bigint(10) - 사용자 ID
- pinned: tinyint(1) - 고정 여부
- schedule_data: text - JSON 형식 스케줄 데이터
- timecreated: bigint(10)
- timemodified: bigint(10)
```

##### mdl_abessi_missionlog (미션/활동 로그)
```sql
- id: bigint(10) PRIMARY KEY
- userid: bigint(10) - 사용자 ID
- page: varchar(255) - 페이지/활동 타입
- timecreated: bigint(10) - 생성 시간
```

##### 기타 Abessi 테이블
- `mdl_abessi_tracking`: 추적 정보
- `mdl_abessi_today`: 오늘 목표
- `mdl_abessi_chapterlog`: 챕터 로그
- `mdl_abessi_progress`: 진행 상황
- `mdl_abessi_mathtalk`: 수학토크

#### 2.2 Alt42t 시험 시스템 테이블

##### student_exam_settings (학생별 시험 설정)
```sql
- id: INT AUTO_INCREMENT PRIMARY KEY
- user_id: INT NOT NULL (mdl_user.id 참조)
- name: VARCHAR(100) - 학생 이름
- school: VARCHAR(200) - 학교명
- grade: VARCHAR(50) - 학년
- semester: VARCHAR(20) - 학기
- exam_type: VARCHAR(50) - 시험 종류
- exam_start_date: DATE - 시험 시작일
- exam_end_date: DATE - 시험 종료일
- math_exam_date: DATE - 수학 시험일
- exam_scope: TEXT - 시험 범위
- exam_status: ENUM('expected', 'confirmed')
- study_level: ENUM('concept', 'review', 'practice')
- created_at/updated_at: TIMESTAMP
```

##### exam_settings (학교별 시험 정보 공유)
```sql
- id: INT AUTO_INCREMENT PRIMARY KEY
- school: VARCHAR(200) - 학교명
- exam_type: VARCHAR(50) - 시험 종류
- exam_start_date: DATE
- exam_end_date: DATE
- math_exam_date: DATE
- exam_scope: TEXT
- created_by: INT - 생성자 ID
```

##### user_sessions (로그인 세션)
```sql
- id: INT AUTO_INCREMENT PRIMARY KEY
- user_id: INT
- session_id: VARCHAR(255)
- ip_address: VARCHAR(45)
- user_agent: TEXT
- last_activity: TIMESTAMP
```

##### schools (학교 정보)
```sql
- id: INT AUTO_INCREMENT PRIMARY KEY
- name: VARCHAR(200) UNIQUE
- homepage_url: VARCHAR(500)
- address: TEXT
```

##### mdl_alt42t_* 테이블
- `mdl_alt42t_users`: Alt42t 시스템 사용자
- `mdl_alt42t_exams`: 시험 정보
- `mdl_alt42t_exam_dates`: 시험 날짜
- `mdl_alt42t_exam_resources`: 시험 자료 (file_url, tip_text 포함)
- `mdl_alt42t_study_status`: 학습 상태

## 🔒 보안 및 인증

### 1. 데이터베이스 연결 패턴

#### PDO 연결 (권장)
```php
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
}
```

### 2. 사용자 인증

#### 로그인 체크
```php
// Moodle 사용자 인증
function authenticateUser($username, $password) {
    // 1. mdl_user 테이블에서 사용자 조회
    $stmt = $pdo->prepare("
        SELECT id, username, firstname, lastname, email, password
        FROM mdl_user
        WHERE (username = :username OR email = :username) 
        AND deleted = 0
        LIMIT 1
    ");
    
    // 2. bcrypt로 암호화된 비밀번호 검증
    if (password_verify($password, $user['password'])) {
        // 세션 설정
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
        $_SESSION['login_time'] = time();
    }
}
```

#### 교사 권한 체크
```php
// mdl_user_info_data의 fieldid 22로 역할 확인
function isTeacher($userid) {
    $sql = "SELECT data FROM mdl_user_info_data 
            WHERE userid = ? AND fieldid = 22";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userid]);
    $role = $stmt->fetchColumn();
    
    // 'student'가 아니면 교사로 간주
    return $role !== 'student';
}

// Moodle 통합 인증 (require_once 사용)
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

// Moodle 역할 기반 권한 체크
$context = context_system::instance();
require_capability('moodle/course:viewparticipants', $context);
```

### 3. 세션 관리
```php
// 세션 시작 및 타임아웃 체크
session_start();
define('SESSION_TIMEOUT', 3600); // 1시간

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // 세션 타임아웃 체크
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
```

## 📝 쿼리 패턴 및 최적화

### 1. 사용자 정보 조회
```php
// 기본 사용자 정보
$stmt = $pdo->prepare("SELECT * FROM mdl_user WHERE id = ? AND deleted = 0");

// 사용자 추가 정보 포함
$stmt = $pdo->prepare("
    SELECT u.*, uid.data as role 
    FROM mdl_user u
    LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
    WHERE u.id = ? AND u.deleted = 0
");

// 교사 담당 학생 목록
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.phone1, u.phone2
    FROM mdl_user u
    JOIN mdl_user_enrolments ue ON ue.userid = u.id
    JOIN mdl_enrol e ON e.id = ue.enrolid
    JOIN mdl_course c ON c.id = e.courseid
    JOIN mdl_context ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
    JOIN mdl_role_assignments ra ON ra.contextid = ctx.id AND ra.userid = ?
    WHERE ra.roleid IN (3,4,5) AND u.deleted = 0
    ORDER BY u.lastname, u.firstname
");
```

### 2. 출결 정보 조회
```php
// 특정 날짜의 출결 기록
$stmt = $pdo->prepare("
    SELECT * FROM mdl_abessi_attendance_record 
    WHERE userid = ? AND date = ?
    ORDER BY timecreated DESC
");

// 출결 통계
$stmt = $pdo->prepare("
    SELECT type, COUNT(*) as count, SUM(hours) as total_hours
    FROM mdl_abessi_attendance_record
    WHERE userid = ? AND date BETWEEN ? AND ?
    GROUP BY type
");

// 최근 활동 체크
$stmt = $pdo->prepare("
    SELECT MAX(timecreated) as last_activity
    FROM mdl_abessi_missionlog
    WHERE userid = ? AND timecreated > ?
");
```

### 3. 시험 정보 관리
```php
// 학교별 시험 정보 조회 (Alt42t DB)
$stmt = $pdo->prepare("
    SELECT * FROM student_exam_settings 
    WHERE school = ? AND grade = ? AND exam_type = ?
    ORDER BY exam_start_date DESC
");

// 시험 자료 조회
$stmt = $pdo->prepare("
    SELECT resource_id, title, file_url, tip_text, uploaded_at
    FROM mdl_alt42t_exam_resources 
    WHERE exam_id = ?
    ORDER BY uploaded_at DESC
");

// 학교 정보 조회
$stmt = $pdo->prepare("
    SELECT * FROM schools 
    WHERE name = ?
    LIMIT 1
");
```

### 4. 인덱스 활용
```sql
-- 성능 최적화를 위한 인덱스
CREATE INDEX idx_user_id ON table_name(user_id);
CREATE INDEX idx_school_exam ON exam_settings(school, exam_type);
CREATE INDEX idx_attendance_date_userid ON mdl_abessi_attendance_record(date, userid);
```

## ⚠️ 주의사항

### 1. SQL Injection 방지
```php
// ❌ 위험한 코드
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];

// ✅ 안전한 코드
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

### 2. 민감정보 처리
- API 키나 비밀번호를 하드코딩하지 마세요
- 환경변수나 별도 설정 파일 사용
- .gitignore에 config.php 추가 필수

### 3. 에러 처리
```php
try {
    // 데이터베이스 작업
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // 사용자에게는 일반적인 에러 메시지 표시
    echo "처리 중 오류가 발생했습니다.";
}
```

### 4. 트랜잭션 사용
```php
// 여러 테이블 업데이트 시
$pdo->beginTransaction();
try {
    // 여러 INSERT/UPDATE 쿼리 실행
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

## 🔗 파일 구조 및 경로

### 주요 경로
- Moodle 설정: `/home/moodle/public_html/moodle/config.php`
- 프로젝트 루트: `/mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/`
- 웹 URL: `https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/`
- 업로드 디렉토리: `uploads/`
- 오디오 파일: `audio/`
- CSS: `assets/css/`
- 테스트 파일 백업: `test_files_backup/`

### 파일 네이밍 규칙
- 교사용 파일: `teacher_*.php`
- AJAX 핸들러: `ajax_*.php`, `get_*.php`, `save_*.php`
- 테스트 파일: `test_*.php`
- 정보 조회: `info_*.php`
- 설정 파일: `config.php`
- 인증 관련: `login*.php`, `logout*.php`

## 📱 프론트엔드 통합

### AJAX 요청 패턴
```javascript
// jQuery AJAX
$.ajax({
    url: 'get_dashboard_data.php',
    method: 'POST',
    data: { user_id: userId },
    dataType: 'json',
    success: function(response) {
        if (response.success) {
            // 데이터 처리
        }
    },
    error: function(xhr, status, error) {
        console.error('Error:', error);
    }
});
```

### 응답 포맷
```php
// 표준 JSON 응답
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $result,
    'message' => '성공적으로 처리되었습니다.'
]);
```

## 🚀 개발 팁

1. **디버그 모드**: `config.php`에서 `DEBUG_MODE = true` 설정
2. **로그 확인**: `error_log()` 함수 활용
3. **성능 모니터링**: 쿼리 실행 시간 측정
4. **캐싱 고려**: 자주 조회되는 데이터는 세션이나 캐시 활용
5. **코드 재사용**: 공통 함수는 별도 파일로 분리
6. **Moodle 통합**: `require_once('/home/moodle/public_html/moodle/config.php')` 사용
7. **권한 체크**: Moodle의 `require_login()` 및 `require_capability()` 활용

## 📚 참고 리소스

- Moodle 데이터베이스 구조: https://docs.moodle.org/dev/Database_schema
- PDO 문서: https://www.php.net/manual/en/book.pdo.php
- 보안 가이드: https://www.php.net/manual/en/security.php
- Moodle API: https://docs.moodle.org/dev/Main_Page

## 🎯 중요 참고사항

### Moodle 통합 시
- Global 변수: `$DB`, `$USER`, `$CFG` 사용 가능
- `require_login()`: 로그인 필수 체크
- `$USER->id`: 현재 로그인한 사용자 ID
- `$DB->get_record()`, `$DB->insert_record()` 등 Moodle DB API 사용 가능

### 시간 처리
- Moodle은 Unix timestamp (bigint) 사용
- PHP `time()` 함수로 현재 시간 저장
- 한국 시간대: `date_default_timezone_set('Asia/Seoul')`

### JSON 데이터 처리
- 응답 헤더: `header('Content-Type: application/json; charset=utf-8')`
- 한글 인코딩: `json_encode($data, JSON_UNESCAPED_UNICODE)`
- CORS 설정: AJAX 요청 시 필요

---

이 가이드는 MathKing 시스템 개발 시 참고해야 할 핵심 정보를 담고 있습니다.
파일 경로: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/