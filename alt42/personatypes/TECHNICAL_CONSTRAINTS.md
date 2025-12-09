# 기술 제약사항 및 구현 가이드

## 시스템 환경
- **Moodle**: 3.7
- **MySQL**: 5.2.1
- **PHP**: 7.3
- **프론트엔드**: 순수 JavaScript, HTML, CSS (프레임워크 없음)

## 구현 제약사항

### 1. PHP 7.3 호환성
```php
// ❌ PHP 7.4+ 기능 사용 불가
// - Typed properties
// - Arrow functions
// - Null coalescing assignment operator (??=)

// ✅ PHP 7.3 호환 코드
class Agent {
    private $openai;  // Type hints 없이
    
    public function __construct() {
        $this->openai = null;
    }
}
```

### 2. MySQL 5.2.1 호환성
```sql
-- ❌ JSON 타입 사용 불가 (MySQL 5.7.8+)
-- ❌ Generated columns 사용 불가

-- ✅ TEXT 필드로 JSON 저장
CREATE TABLE ss_prompt_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    variables TEXT,  -- JSON 대신 TEXT로 저장
    -- ...
);
```

### 3. 순수 JavaScript (ES5 호환)
```javascript
// ❌ ES6+ 기능 제한적 사용
// - import/export 모듈 시스템 불가
// - async/await 주의 (폴리필 필요)
// - 화살표 함수 주의

// ✅ ES5 호환 코드
var JourneyMap = function(containerId, userId) {
    this.container = document.getElementById(containerId);
    this.userId = userId;
};

JourneyMap.prototype.init = function() {
    // 구현
};
```

### 4. Moodle 3.7 통합
```php
// Moodle 3.7 인증 방식
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

// 사용자 정보 접근
global $DB, $USER, $CFG;
$userid = required_param('userid', PARAM_INT);  // GET 파라미터 안전하게 받기

// 사용자 역할 확인
$context = context_system::instance();
$isTeacher = has_capability('moodle/course:manageactivities', $context);
```

## 수정된 구현 방안

### 1. 데이터베이스 스키마 수정
```sql
-- JSON 필드를 TEXT로 변경
CREATE TABLE IF NOT EXISTS ss_prompt_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_type ENUM('system', 'user', 'assistant') NOT NULL,
    template_text TEXT NOT NULL,
    variables TEXT,  -- JSON 문자열로 저장
    is_active TINYINT(1) DEFAULT 1,  -- BOOLEAN 대신
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (template_name),
    INDEX idx_type_active (template_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. PHP 클래스 구조 간소화
```php
// classes/Agent.php
class Agent {
    private $apiKey;
    private $model;
    
    public function __construct() {
        $this->apiKey = getenv('OPENAI_API_KEY') ?: OPENAI_API_KEY;
        $this->model = getenv('OPENAI_MODEL') ?: 'gpt-4';
    }
    
    public function generateResponse($reflection, $context) {
        $prompt = $this->buildPrompt($reflection, $context);
        return $this->callOpenAI($prompt);
    }
    
    private function callOpenAI($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => $this->model,
            'messages' => array(
                array('role' => 'system', 'content' => $this->getSystemPrompt()),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.7,
            'max_tokens' => 1000
        );
        
        $options = array(
            'http' => array(
                'header' => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ),
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        return json_decode($result, true);
    }
}
```

### 3. JavaScript 구조 (ES5 호환)
```javascript
// assets/js/journey.js
(function() {
    'use strict';
    
    // 네임스페이스
    window.ShiningStars = window.ShiningStars || {};
    
    // JourneyMap 생성자
    function JourneyMap(containerId, userId) {
        this.container = document.getElementById(containerId);
        this.userId = userId;
        this.nodes = [];
        this.connections = [];
        this.currentNode = null;
        this.completedNodes = [];
        this.unlockedNodes = [0];
    }
    
    // 프로토타입 메서드
    JourneyMap.prototype = {
        init: function() {
            this.loadProgress();
            this.renderMap();
            this.attachEvents();
        },
        
        loadProgress: function() {
            var self = this;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'api/student/progress.php?user_id=' + this.userId);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    self.updateProgress(data);
                }
            };
            xhr.send();
        },
        
        renderMap: function() {
            // 맵 렌더링 로직
        }
    };
    
    // 전역 노출
    window.ShiningStars.JourneyMap = JourneyMap;
})();
```

### 4. AJAX 통신 (jQuery 없이)
```javascript
// AJAX 헬퍼 함수
function ajaxRequest(method, url, data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                callback(null, response);
            } else {
                callback(new Error('Request failed: ' + xhr.status));
            }
        }
    };
    
    xhr.send(data ? JSON.stringify(data) : null);
}
```

### 5. CSS 구조 (최신 기능 제한)
```css
/* CSS Grid와 Flexbox는 지원되지만 주의해서 사용 */
.journey-map {
    display: flex;  /* IE11+ */
    align-items: center;
    justify-content: center;
}

/* CSS 변수 사용 불가, Sass 없이 순수 CSS */
.avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* fallback 색상 제공 */
    background-color: #667eea;
}
```

### 6. 보안 강화 (Moodle 방식)
```php
// includes/auth.php
require_once($CFG->dirroot . '/config.php');
require_login();

// CSRF 보호
require_sesskey();

// 파라미터 검증
$userid = required_param('userid', PARAM_INT);
$nodeid = optional_param('node', 0, PARAM_INT);

// SQL 인젝션 방지 (Moodle DB 레이어 사용)
$user = $DB->get_record('user', array('id' => $userid));
$reflection = $DB->get_record_sql(
    "SELECT * FROM {ss_reflections} WHERE user_id = ? AND node_id = ?",
    array($userid, $nodeid)
);
```

## 프로젝트 구조 수정

```
shiningstars/
├── api/
│   └── *.php (각 API 엔드포인트)
├── assets/
│   ├── css/
│   │   └── style.css (단일 CSS 파일로 통합)
│   └── js/
│       ├── main.js
│       ├── journey.js
│       └── agent.js
├── classes/
│   └── *.php (PHP 클래스들)
├── includes/
│   ├── config.php
│   ├── auth.php
│   └── functions.php
├── lang/
│   └── ko/
│       └── shiningstars.php (Moodle 언어팩)
├── index.php
├── agent.php
└── version.php (Moodle 플러그인 버전 정보)
```

## 개발 시 주의사항

1. **브라우저 호환성**: IE11 이상 지원 필요
2. **모바일 대응**: 반응형 디자인 필수
3. **성능**: 폴리필 최소화, 번들링 없이 최적화
4. **보안**: Moodle 보안 가이드라인 준수
5. **에러 처리**: try-catch 대신 전통적인 에러 처리