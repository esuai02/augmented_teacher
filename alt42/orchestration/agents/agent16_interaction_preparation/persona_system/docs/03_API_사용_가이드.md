# API 사용 가이드

## 1. 개요

Agent16 페르소나 시스템은 RESTful JSON API를 제공합니다.
이 가이드는 API 엔드포인트와 사용 방법을 설명합니다.

**베이스 URL**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/persona_system/api/chat.php
```

---

## 2. 인증

### 2.1 Moodle 세션 기반 인증

모든 API 요청은 Moodle 로그인 세션이 필요합니다.

```php
// 서버 측 인증 처리
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();  // 미로그인시 401 반환
```

### 2.2 인증 실패 응답

```json
{
  "success": false,
  "error": "로그인이 필요합니다",
  "error_location": "/path/to/chat.php:113",
  "timestamp": "2025-06-02T10:00:00+09:00",
  "api_version": "1.0.0"
}
```

---

## 3. GET 엔드포인트

### 3.1 에이전트 상태 조회

```bash
GET /api/chat.php?action=status
```

**응답**:
```json
{
  "success": true,
  "timestamp": "2025-06-02T10:00:00+09:00",
  "api_version": "1.0.0",
  "agent_id": "agent16",
  "agent_name": "상호작용 준비 에이전트",
  "status": "active",
  "user_id": 12345,
  "debug_info": {
    "rules_loaded": true,
    "personas_count": 9,
    "worldviews_count": 9
  }
}
```

### 3.2 세계관 목록 조회

```bash
GET /api/chat.php?action=worldviews
```

**응답**:
```json
{
  "success": true,
  "worldviews": [
    {
      "id": "curriculum",
      "name": "커리큘럼",
      "description": "체계적인 학습 과정 기반의 세계관",
      "triggers": ["정규과정", "단원", "학기", "교과서"],
      "priority": 1
    },
    {
      "id": "exam_prep",
      "name": "시험대비",
      "description": "시험 준비 집중 모드의 세계관",
      "triggers": ["시험", "테스트", "평가", "중간고사", "기말고사"],
      "priority": 3
    }
    // ... 나머지 세계관
  ],
  "count": 9
}
```

### 3.3 상호작용 이력 조회

```bash
GET /api/chat.php?action=history&limit=10
```

**파라미터**:
- `limit` (선택): 조회할 이력 수 (기본값: 20, 최대: 100)

**응답**:
```json
{
  "success": true,
  "user_id": 12345,
  "history": [
    {
      "from_persona": "A16_P1",
      "to_persona": "A16_P3",
      "trigger_message": "시험 준비 도와줘",
      "changed_at": "2025-06-02 09:30:00"
    }
  ],
  "count": 1
}
```

### 3.4 현재 페르소나 상태 조회

```bash
GET /api/chat.php?action=state
```

**응답** (상태 있음):
```json
{
  "success": true,
  "user_id": 12345,
  "current_persona": "A16_P3",
  "context": {
    "worldview": "exam_prep",
    "worldview_confidence": 0.85,
    "last_topic": "중간고사 준비"
  },
  "last_interaction": "2025-06-02 09:30:00"
}
```

**응답** (상태 없음):
```json
{
  "success": true,
  "user_id": 12345,
  "current_persona": null,
  "context": null,
  "message": "아직 상호작용 기록이 없습니다"
}
```

---

## 4. POST 엔드포인트

### 4.1 채팅 처리 (기본)

```bash
POST /api/chat.php
Content-Type: application/json

{
  "message": "시험이 다가오는데 어떻게 준비해야 할까요?",
  "session_data": {
    "course_id": 123
  }
}
```

**요청 파라미터**:
| 파라미터 | 타입 | 필수 | 설명 |
|----------|------|------|------|
| message | string | ✓ | 사용자 메시지 (최대 2000자) |
| session_data | object | - | 세션 컨텍스트 데이터 |
| action | string | - | 기본값: "chat" |
| debug | boolean | - | 디버그 모드 활성화 |

**응답**:
```json
{
  "success": true,
  "timestamp": "2025-06-02T10:00:00+09:00",
  "api_version": "1.0.0",
  "agent_id": "agent16",
  "persona": {
    "id": "A16_P3",
    "name": "시험 전략가",
    "tone": "Analytical",
    "confidence": 0.85
  },
  "response": {
    "text": "시험까지 남은 기간을 효율적으로 활용해볼게요...",
    "suggestions": [
      "출제 빈도 높은 단원 점검",
      "취약 영역 집중 보완"
    ],
    "actions": []
  },
  "analysis": {
    "detected_worldview": "exam_prep",
    "worldview_confidence": 0.85,
    "situation_group": "S4",
    "learning_stage": "preparation"
  },
  "meta": {
    "processing_time_ms": 45,
    "rules_applied": 2
  }
}
```

### 4.2 디버그 모드 채팅

```bash
POST /api/chat.php
Content-Type: application/json

{
  "message": "시험 준비 도와줘",
  "debug": true
}
```

**추가 응답 (debug 객체)**:
```json
{
  "debug": {
    "full_analysis": {
      "detected_worldview": "exam_prep",
      "worldview_confidence": 0.85,
      "situation_group": "S4",
      "learning_stage": "preparation",
      "trigger_matches": ["시험", "준비"],
      "persona_selection_reason": "worldview_match"
    },
    "actions": [
      {
        "type": "select_persona",
        "value": "A16_P3"
      }
    ],
    "engine_info": {
      "rules_loaded": true,
      "rules_count": 45,
      "personas_count": 9
    }
  }
}
```

### 4.3 세계관 감지 (분석만)

```bash
POST /api/chat.php
Content-Type: application/json

{
  "action": "detect_worldview",
  "message": "오늘 할 수 있는 간단한 미션 줘"
}
```

**응답**:
```json
{
  "success": true,
  "message": "오늘 할 수 있는 간단한 미션 줘",
  "detected_worldview": "short_mission",
  "worldview_name": "단기미션",
  "confidence": 0.72,
  "description": "단기 목표 달성 중심의 세계관"
}
```

### 4.4 세계관 수동 설정

```bash
POST /api/chat.php
Content-Type: application/json

{
  "action": "set_worldview",
  "worldview_id": "self_reflection"
}
```

**응답**:
```json
{
  "success": true,
  "worldview_id": "self_reflection",
  "worldview_name": "자기성찰",
  "persona": {
    "persona_id": "A16_P5",
    "persona_name": "성찰 촉진자",
    "tone": "Reflective"
  },
  "message": "세계관이 설정되었습니다"
}
```

---

## 5. 에러 응답

### 5.1 에러 응답 형식

```json
{
  "success": false,
  "error": "에러 메시지",
  "error_location": "파일경로:줄번호",
  "timestamp": "2025-06-02T10:00:00+09:00",
  "api_version": "1.0.0"
}
```

### 5.2 HTTP 상태 코드

| 코드 | 의미 | 예시 |
|------|------|------|
| 200 | 성공 | 정상 처리 |
| 400 | 잘못된 요청 | JSON 파싱 오류, 필수 필드 누락 |
| 401 | 인증 필요 | 로그인 필요 |
| 405 | 메서드 불허 | PUT, DELETE 등 미지원 메서드 |
| 500 | 서버 오류 | 내부 처리 오류 |

### 5.3 일반적인 에러

**JSON 파싱 오류**:
```json
{
  "success": false,
  "error": "JSON 파싱 오류: Syntax error",
  "error_location": "chat.php:315"
}
```

**필수 필드 누락**:
```json
{
  "success": false,
  "error": "message 필드는 필수입니다",
  "error_location": "chat.php:349"
}
```

**잘못된 세계관 ID**:
```json
{
  "success": false,
  "error": "유효하지 않은 세계관 ID입니다",
  "error_location": "chat.php:463"
}
```

---

## 6. 사용 예시

### 6.1 JavaScript (fetch)

```javascript
async function sendChat(message) {
  const response = await fetch('/api/chat.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    credentials: 'include',  // Moodle 세션 쿠키 포함
    body: JSON.stringify({
      message: message,
      session_data: {
        course_id: courseId
      }
    })
  });

  const data = await response.json();

  if (data.success) {
    console.log('페르소나:', data.persona.name);
    console.log('응답:', data.response.text);
  } else {
    console.error('오류:', data.error);
  }

  return data;
}
```

### 6.2 PHP (cURL)

```php
function sendChat($message, $sessionData = []) {
    $url = 'https://mathking.kr/.../api/chat.php';

    $data = [
        'message' => $message,
        'session_data' => $sessionData
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
```

### 6.3 jQuery

```javascript
$.ajax({
  url: '/api/chat.php',
  method: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({
    message: '시험 준비 도와줘'
  }),
  xhrFields: {
    withCredentials: true
  },
  success: function(response) {
    if (response.success) {
      $('#response').text(response.response.text);
      $('#persona').text(response.persona.name);
    }
  },
  error: function(xhr, status, error) {
    console.error('API 오류:', error);
  }
});
```

---

## 7. CORS 설정

### 7.1 현재 CORS 헤더

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

### 7.2 프리플라이트 요청 처리

```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

---

## 8. 속도 제한

### 8.1 현재 제한 (권장)

| 제한 유형 | 값 |
|-----------|-----|
| 요청당 메시지 길이 | 최대 2000자 |
| 이력 조회 최대 | 100건 |

### 8.2 향후 제한 (계획)

- 분당 요청 수: 60회
- 일일 요청 수: 10,000회

---

## 9. 버전 관리

### 9.1 현재 버전

- API 버전: 1.0.0
- 응답에 항상 `api_version` 포함

### 9.2 버전 호환성

현재는 단일 버전만 지원합니다. 향후 버전 변경 시:
- 하위 호환성 유지 우선
- 주요 변경 시 새 엔드포인트 추가

---

## 10. 문제 해결

### 10.1 401 Unauthorized

- Moodle 로그인 상태 확인
- 세션 쿠키가 요청에 포함되었는지 확인
- `credentials: 'include'` (fetch) 또는 쿠키 전달 설정

### 10.2 빈 응답

- Content-Type 헤더 확인 (`application/json`)
- JSON 형식 유효성 확인
- 서버 에러 로그 확인

### 10.3 느린 응답

- 디버그 모드 비활성화
- 메시지 길이 축소
- 세션 데이터 최소화

---

*이 문서는 Agent16 API 사용 방법을 설명합니다.*
*관련: [00_시스템_개요.md](./00_시스템_개요.md), [07_통합_배포_가이드.md](./07_통합_배포_가이드.md)*
