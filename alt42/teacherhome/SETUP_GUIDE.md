# 🚀 ALT42 플러그인 설정 시스템 설정 가이드

`alt42/teacherhome/index.html`에서 사용하는 플러그인 세부설정을 서버에 저장하고 관리하는 시스템의 완전한 설정 가이드입니다.

## 📋 시스템 개요

### ✨ 주요 기능
- **3가지 플러그인 타입**: 내부링크, 외부링크, 메시지 발송
- **3가지 설정 레벨**: 전역, 사용자별, 카드별
- **실시간 설정 관리**: 저장/불러오기/수정/삭제
- **사용 통계 추적**: 플러그인 실행 횟수 및 패턴 분석
- **변경 이력 관리**: 모든 설정 변경 사항 추적

### 📊 데이터베이스 구조 (mdl_alt42DB_)
1. **plugin_types** - 플러그인 기본 정보
2. **user_plugin_settings** - 사용자별 플러그인 설정
3. **card_plugin_settings** - 카드별 플러그인 설정
4. **plugin_settings_history** - 플러그인 설정 변경 히스토리
5. **plugin_usage_stats** - 플러그인 사용 통계

## 🔧 설치 단계

### 1단계: 파일 확인
다음 파일들이 `teacherhome/` 디렉토리에 있는지 확인하세요:

```
teacherhome/
├── create_alt42_plugin_tables.sql     # 데이터베이스 테이블 생성 SQL
├── execute_database_setup.php         # 데이터베이스 설정 스크립트
├── setup_database.bat                 # Windows 배치 파일
├── plugin_settings_api.php            # PHP API 클래스
├── plugin_settings_client.js          # JavaScript 클라이언트
├── plugin_settings_styles.css         # UI 스타일
├── plugin_settings_demo.html          # 데모 페이지
└── SETUP_GUIDE.md                     # 이 가이드
```

### 2단계: 데이터베이스 연결 정보 설정

#### A. `execute_database_setup.php` 파일 수정
```php
// 데이터베이스 연결 설정 (실제 설정에 맞게 수정하세요)
$host = 'localhost';                    // 실제 호스트명
$dbname = 'your_database_name';         // 실제 데이터베이스명
$username = 'your_username';            // 실제 사용자명
$password = 'your_password';            // 실제 비밀번호
```

#### B. `plugin_settings_api.php` 파일 수정
```php
// 데이터베이스 연결 (이 부분은 프로젝트의 DB 설정에 맞게 수정)
$pdo = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
```

### 3단계: 데이터베이스 테이블 생성

#### 방법 1: 배치 파일 사용 (Windows 권장)
```bash
# Windows에서 실행
setup_database.bat
```

#### 방법 2: 웹 브라우저 사용 (권장)
웹 브라우저에서 다음 URL을 열어주세요:
```
http://localhost/alt42/teacherhome/execute_database_setup.php
```

#### 방법 3: 명령행 사용
```bash
# PHP가 설치된 환경에서
php execute_database_setup.php
```

#### 방법 4: MySQL 클라이언트 직접 사용
```bash
mysql -u username -p database_name < create_alt42_plugin_tables.sql
```

### 4단계: 설정 확인

데이터베이스 설정이 완료되면 다음 테이블들이 생성되어야 합니다:

```sql
-- 생성된 테이블 확인
SHOW TABLES LIKE 'mdl_alt42DB_%';

-- 초기 데이터 확인
SELECT * FROM mdl_alt42DB_plugin_types;
```

예상 결과:
```
+----+--------------+------------------+--------------+----------------------------------------+-----------+-------------+--------------+
| id | plugin_id    | plugin_title     | plugin_icon  | plugin_description                     | is_active | timecreated | timemodified |
+----+--------------+------------------+--------------+----------------------------------------+-----------+-------------+--------------+
|  1 | internal_link| 내부링크 열기    | 🔗           | 플랫폼 내 다른 페이지로 이동          |         1 |  1703980800 |   1703980800 |
|  2 | external_link| 외부링크 열기    | 🌐           | 외부 사이트나 도구 연결               |         1 |  1703980800 |   1703980800 |
|  3 | send_message | 메시지 발송      | 📨           | 사용자에게 자동 메시지 전송           |         1 |  1703980800 |   1703980800 |
+----+--------------+------------------+--------------+----------------------------------------+-----------+-------------+--------------+
```

## 🎮 사용 방법

### 1. HTML 파일에 스크립트 추가

`teacherhome/index.html`에 다음 코드를 추가하세요:

```html
<head>
    <!-- 기존 head 태그 내용 -->
    <link rel="stylesheet" href="plugin_settings_styles.css">
</head>

<body>
    <!-- 기존 body 태그 내용 -->
    
    <!-- 플러그인 설정 스크립트 -->
    <script src="plugin_settings_client.js"></script>
    
    <!-- 사용 예시 -->
    <script>
        // 플러그인 설정 UI 생성
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('plugin-settings-container');
            if (container) {
                window.ktmPluginSettings.createPluginSettingsUI(container, 'weekly');
            }
        });
    </script>
</body>
```

### 2. JavaScript에서 플러그인 사용

```javascript
// 플러그인 실행
window.ktmPluginSettings.executePlugin('internal_link', {
    internal_url: '/dashboard',
    open_new_tab: false
});

// 사용자 설정 저장
await window.ktmPluginSettings.saveUserSetting(
    'external_link',
    'default_config',
    { url: 'https://google.com', new_tab: true },
    'weekly'
);

// 카드 설정 저장
await window.ktmPluginSettings.saveCardSetting(
    'weekly',
    '주간 계획표',
    0,
    'send_message',
    { message_content: '계획표가 업데이트되었습니다!', message_type: 'success' }
);
```

### 3. 데모 페이지 테스트

`plugin_settings_demo.html`을 열어서 시스템을 테스트하세요:

```
http://localhost/alt42/teacherhome/plugin_settings_demo.html
```

## 🔍 API 사용법

### 플러그인 타입 조회
```javascript
const response = await fetch('plugin_settings_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'get_plugin_types' })
});
```

### 사용자 설정 저장
```javascript
const response = await fetch('plugin_settings_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'save_user_setting',
        user_id: 1,
        plugin_id: 'internal_link',
        setting_name: 'default_config',
        setting_value: { url: '/dashboard', new_tab: false },
        category: 'weekly'
    })
});
```

### 플러그인 사용 통계 업데이트
```javascript
const response = await fetch('plugin_settings_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'update_usage_stats',
        user_id: 1,
        plugin_id: 'internal_link',
        category: 'weekly',
        execution_data: { url: '/dashboard', timestamp: Date.now() }
    })
});
```

## 🎨 UI 커스터마이징

### CSS 변수 사용
```css
.plugin-settings-ui {
    --primary-color: #3b82f6;
    --border-color: #e1e5e9;
    --background-color: #fff;
    --text-color: #374151;
    --border-radius: 8px;
}
```

### 다크 테마 적용
```css
@media (prefers-color-scheme: dark) {
    .plugin-settings-ui {
        --primary-color: #60a5fa;
        --border-color: #4b5563;
        --background-color: #1f2937;
        --text-color: #f9fafb;
    }
}
```

## 🛠️ 문제 해결

### 일반적인 문제들

#### 1. 데이터베이스 연결 실패
```
오류: SQLSTATE[HY000] [1045] Access denied for user
```
**해결방법:**
- 데이터베이스 연결 정보 확인
- 사용자 권한 확인
- MySQL 서버 실행 상태 확인

#### 2. 테이블 생성 실패
```
오류: Table 'mdl_alt42DB_plugin_types' already exists
```
**해결방법:**
- 기존 테이블 삭제 후 재생성
- 또는 IF NOT EXISTS 구문 사용

#### 3. JavaScript 오류
```
오류: window.ktmPluginSettings is not defined
```
**해결방법:**
- plugin_settings_client.js 파일 로드 확인
- 스크립트 실행 순서 확인

### 디버깅 팁

#### 1. 브라우저 콘솔 확인
```javascript
// 플러그인 시스템 상태 확인
console.log('Plugin Types:', window.ktmPluginSettings.getPluginTypes());
console.log('User Settings:', window.ktmPluginSettings.getUserSettings());
```

#### 2. PHP 오류 로그 확인
```php
// API 파일에 디버그 모드 추가
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### 3. 데이터베이스 쿼리 로그 확인
```sql
-- MySQL 쿼리 로그 활성화
SET global general_log = 1;
SET global log_output = 'table';
SELECT * FROM mysql.general_log ORDER BY event_time DESC LIMIT 10;
```

## 📈 성능 최적화

### 1. 데이터베이스 인덱스 추가
```sql
-- 자주 사용되는 검색 조건에 인덱스 추가
CREATE INDEX idx_user_category ON mdl_alt42DB_user_plugin_settings (user_id, category);
CREATE INDEX idx_card_user_category ON mdl_alt42DB_card_plugin_settings (user_id, category);
```

### 2. 캐싱 구현
```javascript
// 클라이언트 캐싱
const cache = new Map();
const cacheTimeout = 5 * 60 * 1000; // 5분

async function getCachedData(key, fetchFunction) {
    const cached = cache.get(key);
    if (cached && Date.now() - cached.timestamp < cacheTimeout) {
        return cached.data;
    }
    
    const data = await fetchFunction();
    cache.set(key, { data, timestamp: Date.now() });
    return data;
}
```

### 3. 배치 처리
```javascript
// 여러 설정을 한 번에 저장
async function saveBatchSettings(settings) {
    const promises = settings.map(setting => 
        window.ktmPluginSettings.saveUserSetting(
            setting.plugin_id,
            setting.setting_name,
            setting.setting_value,
            setting.category
        )
    );
    
    return Promise.all(promises);
}
```

## 🔒 보안 고려사항

### 1. 사용자 인증
```php
// API 호출 시 사용자 인증 확인
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}
```

### 2. 입력 데이터 검증
```php
// 플러그인 ID 검증
function validatePluginId($plugin_id) {
    $allowed_plugins = ['internal_link', 'external_link', 'send_message'];
    return in_array($plugin_id, $allowed_plugins);
}

// URL 검증
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}
```

### 3. SQL 인젝션 방지
```php
// 준비된 명령문 사용
$stmt = $pdo->prepare("SELECT * FROM mdl_alt42DB_plugin_types WHERE plugin_id = ?");
$stmt->execute([$plugin_id]);
```

## 📞 지원 및 문의

문제가 발생하거나 도움이 필요하시면:

1. **로그 확인**: 브라우저 콘솔 및 서버 로그
2. **데모 페이지**: `plugin_settings_demo.html`에서 테스트
3. **설정 확인**: 데이터베이스 연결 정보 및 파일 경로
4. **권한 확인**: 파일 및 데이터베이스 접근 권한

---

**버전**: 1.0.0  
**최종 수정**: 2024-12-31  
**작성자**: ALT42 Team 