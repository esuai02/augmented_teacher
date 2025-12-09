# 🚀 KTM 코파일럿 플러그인 설정 시스템

`alt42/teacherhome/index.html`에서 사용하는 플러그인 세부설정을 서버에 저장하고 관리하는 시스템입니다.

## 📋 시스템 개요

### 3가지 플러그인 타입
1. **🔗 internal_link**: 내부링크 열기 - 플랫폼 내 다른 페이지로 이동
2. **🌐 external_link**: 외부링크 열기 - 외부 사이트나 도구 연결
3. **📨 send_message**: 메시지 발송 - 사용자에게 자동 메시지 전송

### 3가지 설정 유형
1. **전역 설정**: 모든 사용자에게 적용되는 기본 설정
2. **사용자별 설정**: 각 사용자가 개별적으로 설정할 수 있는 설정
3. **카드별 설정**: 특정 카드에만 적용되는 설정

## 🗄️ 데이터베이스 구조

### 1. 플러그인 기본 정보 테이블 (`mdl_ktm_plugin_types`)
```sql
CREATE TABLE IF NOT EXISTS mdl_ktm_plugin_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_id VARCHAR(50) NOT NULL UNIQUE,
    plugin_title VARCHAR(255) NOT NULL,
    plugin_icon VARCHAR(10) NOT NULL,
    plugin_description TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    timecreated INT(10) NOT NULL,
    timemodified INT(10) NOT NULL
);
```

### 2. 사용자별 플러그인 설정 테이블 (`mdl_ktm_user_plugin_settings`)
```sql
CREATE TABLE IF NOT EXISTS mdl_ktm_user_plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plugin_id VARCHAR(50) NOT NULL,
    setting_name VARCHAR(255) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    timecreated INT(10) NOT NULL,
    timemodified INT(10) NOT NULL
);
```

### 3. 카드별 플러그인 설정 테이블 (`mdl_ktm_card_plugin_settings`)
```sql
CREATE TABLE IF NOT EXISTS mdl_ktm_card_plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    card_title VARCHAR(255) NOT NULL,
    card_index INT DEFAULT 0,
    plugin_id VARCHAR(50) NOT NULL,
    plugin_config TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    timecreated INT(10) NOT NULL,
    timemodified INT(10) NOT NULL
);
```

### 4. 플러그인 설정 히스토리 테이블 (`mdl_ktm_plugin_settings_history`)
```sql
CREATE TABLE IF NOT EXISTS mdl_ktm_plugin_settings_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plugin_id VARCHAR(50) NOT NULL,
    setting_type ENUM('user_setting', 'card_setting') NOT NULL,
    reference_id INT NOT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    change_reason VARCHAR(255) DEFAULT NULL,
    timecreated INT(10) NOT NULL
);
```

## 📁 파일 구조

```
teacherhome/
├── plugin_settings_tables.sql      # 데이터베이스 테이블 생성 SQL
├── plugin_settings_api.php         # PHP API 클래스
├── plugin_settings_client.js       # JavaScript 클라이언트 라이브러리
├── plugin_settings_styles.css      # UI 스타일
├── plugin_settings_demo.html       # 데모 페이지
└── README_PLUGIN_SETTINGS.md       # 이 문서
```

## 🔧 설치 및 설정

### 1. 데이터베이스 설정
```bash
# MySQL 데이터베이스에 테이블 생성
mysql -u username -p database_name < plugin_settings_tables.sql
```

### 2. PHP API 설정
`plugin_settings_api.php` 파일에서 데이터베이스 연결 정보를 수정하세요:

```php
// 데이터베이스 연결 (이 부분은 프로젝트의 DB 설정에 맞게 수정)
$pdo = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
```

### 3. HTML 파일에 스크립트 추가
`index.html`에 다음 스크립트를 추가하세요:

```html
<link rel="stylesheet" href="plugin_settings_styles.css">
<script src="plugin_settings_client.js"></script>
```

## 🎮 사용 방법

### 1. 플러그인 설정 UI 생성
```javascript
// 사용자 설정 UI 생성
const container = document.getElementById('settings-container');
window.ktmPluginSettings.createPluginSettingsUI(container, 'weekly');

// 카드별 설정 UI 생성
window.ktmPluginSettings.createPluginSettingsUI(container, 'weekly', '주간 계획표');
```

### 2. 플러그인 실행
```javascript
// 내부 링크 실행
window.ktmPluginSettings.executePlugin('internal_link', {
    internal_url: '/path/to/page',
    open_new_tab: false
});

// 외부 링크 실행
window.ktmPluginSettings.executePlugin('external_link', {
    external_url: 'https://example.com',
    open_new_tab: true
});

// 메시지 발송
window.ktmPluginSettings.executePlugin('send_message', {
    message_content: '안녕하세요!',
    message_type: 'info'
});
```

### 3. 설정 관리
```javascript
// 사용자 설정 저장
await window.ktmPluginSettings.saveUserSetting(
    'internal_link',
    'default_config',
    { url: '/dashboard', new_tab: false },
    'weekly'
);

// 카드 설정 저장
await window.ktmPluginSettings.saveCardSetting(
    'weekly',
    '주간 계획표',
    0,
    'external_link',
    { url: 'https://calendar.google.com', new_tab: true }
);

// 설정 조회
const userSettings = await window.ktmPluginSettings.getUserSettings('weekly');
const cardSettings = await window.ktmPluginSettings.getCardSettings('weekly');
```

## 🔍 API 엔드포인트

### POST /plugin_settings_api.php

#### 플러그인 타입 조회
```json
{
    "action": "get_plugin_types"
}
```

#### 사용자 설정 저장
```json
{
    "action": "save_user_setting",
    "user_id": 1,
    "plugin_id": "internal_link",
    "setting_name": "default_config",
    "setting_value": {"url": "/dashboard", "new_tab": false},
    "category": "weekly"
}
```

#### 카드 설정 저장
```json
{
    "action": "save_card_setting",
    "user_id": 1,
    "category": "weekly",
    "card_title": "주간 계획표",
    "card_index": 0,
    "plugin_id": "external_link",
    "plugin_config": {"url": "https://example.com", "new_tab": true}
}
```

#### 사용자 설정 조회
```json
{
    "action": "get_user_settings",
    "user_id": 1,
    "category": "weekly"
}
```

#### 카드 설정 조회
```json
{
    "action": "get_card_settings",
    "user_id": 1,
    "category": "weekly",
    "card_title": "주간 계획표"
}
```

## 🎨 스타일 커스터마이징

CSS 변수를 사용하여 스타일을 커스터마이징할 수 있습니다:

```css
.plugin-settings-ui {
    --primary-color: #3b82f6;
    --border-color: #e1e5e9;
    --background-color: #fff;
    --text-color: #374151;
}
```

## 🧪 테스트 및 데모

`plugin_settings_demo.html` 파일을 열어 시스템을 테스트할 수 있습니다:

```bash
# 웹 서버에서 실행
http://localhost/alt42/teacherhome/plugin_settings_demo.html
```

## 📝 설정 예시

### 내부 링크 설정
```json
{
    "internal_url": "/dashboard",
    "open_new_tab": false
}
```

### 외부 링크 설정
```json
{
    "external_url": "https://google.com",
    "open_new_tab": true
}
```

### 메시지 발송 설정
```json
{
    "message_content": "작업이 완료되었습니다!",
    "message_type": "success"
}
```

## 🔒 보안 고려사항

1. **사용자 인증**: 모든 API 호출에서 사용자 인증 확인
2. **데이터 검증**: 입력 데이터의 형식과 내용 검증
3. **SQL 인젝션 방지**: 준비된 명령문(Prepared Statements) 사용
4. **XSS 방지**: 사용자 입력 데이터 이스케이프 처리
5. **CSRF 방지**: 토큰 기반 요청 검증

## 🚀 성능 최적화

1. **데이터베이스 인덱스**: 자주 검색되는 컬럼에 인덱스 추가
2. **캐싱**: 자주 사용되는 설정은 클라이언트 캐시 활용
3. **JSON 압축**: 큰 설정 데이터는 압축하여 저장
4. **배치 처리**: 여러 설정을 한 번에 저장하는 배치 API 제공

## 🐛 문제 해결

### 일반적인 문제들

1. **데이터베이스 연결 실패**
   - 연결 정보 확인
   - 데이터베이스 서버 상태 확인

2. **플러그인 실행 실패**
   - 브라우저 콘솔에서 JavaScript 오류 확인
   - 플러그인 설정 데이터 형식 확인

3. **설정 저장 실패**
   - 사용자 권한 확인
   - 데이터베이스 테이블 존재 여부 확인

### 디버깅 팁

```javascript
// 디버그 모드 활성화
window.ktmPluginSettings.debug = true;

// 콘솔에서 상태 확인
console.log(window.ktmPluginSettings.getPluginTypes());
console.log(window.ktmPluginSettings.getUserSettings());
```

## 📞 지원 및 문의

문제가 발생하거나 기능 요청이 있으시면 다음 정보와 함께 문의해주세요:

- 브라우저 버전
- 오류 메시지
- 재현 단계
- 예상 동작

## 📄 라이선스

이 프로젝트는 MIT 라이선스를 따릅니다.

---

**버전**: 1.0.0  
**작성일**: 2024-12-31  
**최종 수정**: 2024-12-31 