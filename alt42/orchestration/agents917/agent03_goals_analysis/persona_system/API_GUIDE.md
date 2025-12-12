# Agent03 Goals Analysis API Guide
# 목표 분석 API 가이드

**Version**: 1.0
**Endpoint**: `/api/goals_chat.php`
**Last Updated**: 2025-12-02

---

## 목차

1. [빠른 시작](#1-빠른-시작)
2. [인증 및 접근](#2-인증-및-접근)
3. [API 엔드포인트](#3-api-엔드포인트)
4. [요청/응답 상세](#4-요청응답-상세)
5. [에러 처리](#5-에러-처리)
6. [사용 예시](#6-사용-예시)
7. [통합 가이드](#7-통합-가이드)

---

## 1. 빠른 시작

### 1.1 기본 API 호출

```bash
# 최소 요청 (POST)
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"message": "목표를 세우고 싶어요"}' \
  https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php
```

### 1.2 테스트 GET 요청

```
GET /api/goals_chat.php?message=목표진행상황
```

### 1.3 API 정보 확인

```bash
# GET 요청 (메시지 없이)
curl https://mathking.kr/.../api/goals_chat.php
```

응답:
```json
{
  "success": true,
  "api": "Agent03 Goals Analysis Persona Chat API",
  "version": "1.0",
  "agent": "agent03_goals_analysis",
  "description": "목표 설정, 진행 상황, 조정에 관한 대화 처리",
  "contexts": {
    "G0": "목표 설정 단계",
    "G1": "목표 진행 단계",
    "G2": "정체/위기 단계",
    "G3": "목표 재설정 단계",
    "CRISIS": "위기 개입 필요"
  }
}
```

---

## 2. 인증 및 접근

### 2.1 CORS 설정

API는 다음 CORS 헤더를 지원합니다:

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

### 2.2 사용자 식별

| 방법 | 설명 | 우선순위 |
|------|------|---------|
| `user_id` 파라미터 | 명시적 사용자 ID 지정 | 1 (최우선) |
| Moodle 세션 | 로그인된 `$USER->id` 사용 | 2 |
| 게스트 폴백 | `user_id = 1` | 3 (기본값) |

### 2.3 OPTIONS Preflight

CORS preflight 요청 시 자동으로 HTTP 200 응답:

```bash
curl -X OPTIONS https://mathking.kr/.../api/goals_chat.php
# Response: HTTP 200 OK
```

---

## 3. API 엔드포인트

### 3.1 메인 엔드포인트

**URL**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php
```

### 3.2 지원 메서드

| 메서드 | 용도 | Content-Type |
|--------|------|-------------|
| `GET` | 테스트/API 정보 조회 | query string |
| `POST` | 대화 처리 (권장) | application/json |
| `OPTIONS` | CORS preflight | - |

### 3.3 관련 페이지

| 경로 | 설명 |
|------|------|
| `/test.php` | 대화형 테스트 UI |
| `/api/goals_chat.php` | API 엔드포인트 |

---

## 4. 요청/응답 상세

### 4.1 요청 파라미터

#### 필수 파라미터

| 파라미터 | 타입 | 설명 | 예시 |
|---------|------|------|------|
| `message` | string | 사용자 메시지 | "목표를 세우고 싶어요" |

#### 선택 파라미터

| 파라미터 | 타입 | 기본값 | 설명 |
|---------|------|--------|------|
| `user_id` | int | 현재 사용자 | 사용자 ID |
| `context` | string | auto | 컨텍스트 힌트 (G0/G1/G2/G3/CRISIS) |
| `goal_id` | int | 0 | 특정 목표 ID |

### 4.2 요청 예시

#### JSON Body (POST)

```json
{
  "message": "이번 학기 수학 성적을 올리고 싶어요",
  "user_id": 123,
  "context": "G0",
  "goal_id": 0
}
```

#### Query String (GET)

```
?message=이번+학기+수학+성적을+올리고+싶어요&user_id=123&context=G0
```

### 4.3 응답 구조

#### 성공 응답 (HTTP 200)

```json
{
  "success": true,
  "user_id": 123,
  "context": {
    "detected": "G0",
    "sub_context": "G0.2",
    "confidence": 0.85
  },
  "persona": {
    "persona_id": "G0_P3",
    "persona_name": "SMART 가이드",
    "tone": "Professional",
    "intervention": "AssessmentDesign"
  },
  "response": {
    "text": "수학 성적 향상이라는 목표를 세우셨군요! 구체적인 목표 설정을 도와드릴게요...",
    "source": "template",
    "follow_up_questions": [
      "현재 수학 점수는 몇 점인가요?",
      "목표 점수는 몇 점으로 생각하시나요?"
    ]
  },
  "goal_analysis": {
    "goal_intent": "set_goal",
    "emotional_state": "motivated",
    "topics": ["goal_setting", "academic", "math"]
  },
  "meta": {
    "agent": "agent03_goals_analysis",
    "processing_time_ms": 52.18,
    "timestamp": "2025-12-02 14:30:00"
  }
}
```

### 4.4 응답 필드 상세

#### `context` 객체

| 필드 | 타입 | 설명 |
|------|------|------|
| `detected` | string | 감지된 컨텍스트 코드 (G0/G1/G2/G3/CRISIS) |
| `sub_context` | string | 세부 컨텍스트 (예: G0.1, G1.2) |
| `confidence` | float | 감지 신뢰도 (0.0 ~ 1.0) |

#### `persona` 객체

| 필드 | 타입 | 설명 |
|------|------|------|
| `persona_id` | string | 페르소나 ID (예: G0_P1) |
| `persona_name` | string | 페르소나 이름 |
| `tone` | string | 어조 스타일 |
| `intervention` | string | 개입 패턴 |

#### `response` 객체

| 필드 | 타입 | 설명 |
|------|------|------|
| `text` | string | 응답 텍스트 (메인 메시지) |
| `source` | string | 응답 생성 소스 (template/llm/fallback) |
| `follow_up_questions` | array | 후속 질문 목록 |

#### `goal_analysis` 객체

| 필드 | 타입 | 설명 |
|------|------|------|
| `goal_intent` | string | 목표 의도 (set_goal/check_progress/modify_goal 등) |
| `emotional_state` | string | 감정 상태 (motivated/frustrated/anxious 등) |
| `topics` | array | 감지된 주제 태그 |

#### `meta` 객체

| 필드 | 타입 | 설명 |
|------|------|------|
| `agent` | string | 에이전트 ID |
| `processing_time_ms` | float | 처리 시간 (밀리초) |
| `timestamp` | string | 응답 생성 시간 |

### 4.5 위기 감지 응답

위기 신호 감지 시 특수 응답 형식:

```json
{
  "success": true,
  "user_id": 123,
  "context": {
    "detected": "CRISIS",
    "sub_context": "level_0",
    "confidence": 0.95
  },
  "persona": {
    "persona_id": "CRISIS_P1",
    "persona_name": "즉시 개입 필요",
    "tone": "Calm",
    "intervention": "CrisisIntervention"
  },
  "response": {
    "text": "지금 많이 힘드시군요. 당신의 안전이 가장 중요해요...\n\n📞 자살예방상담전화: 1393 (24시간)\n📞 정신건강위기상담전화: 1577-0199",
    "source": "crisis_protocol",
    "immediate_action": true
  },
  "meta": {
    "crisis_detected": true,
    "crisis_level": "level_0"
  }
}
```

---

## 5. 에러 처리

### 5.1 에러 응답 형식

```json
{
  "success": false,
  "error": "에러 메시지",
  "error_code": "ERROR_CODE",
  "file": "/path/to/file.php",
  "line": 82
}
```

### 5.2 에러 코드

| 코드 | HTTP | 설명 | 해결방법 |
|------|------|------|---------|
| `MISSING_MESSAGE` | 400 | message 파라미터 누락 | message 필드 추가 |
| `INVALID_JSON` | 400 | JSON 파싱 실패 | JSON 형식 확인 |
| `INVALID_CONTEXT` | 400 | 잘못된 컨텍스트 값 | G0/G1/G2/G3/CRISIS 중 선택 |
| `USER_NOT_FOUND` | 404 | 사용자 ID 없음 | 유효한 user_id 사용 |
| `ENGINE_ERROR` | 500 | 엔진 처리 오류 | 서버 로그 확인 |
| `INTERNAL_ERROR` | 500 | 내부 서버 오류 | 서버 로그 확인 |

### 5.3 에러 예시

#### 메시지 누락 (400)

```json
{
  "success": false,
  "error": "message 파라미터가 필요합니다",
  "error_code": "MISSING_MESSAGE",
  "file": "/home/.../api/goals_chat.php",
  "line": 82
}
```

#### 서버 오류 (500)

```json
{
  "success": false,
  "error": "Database connection failed",
  "error_code": "INTERNAL_ERROR",
  "file": "/home/.../api/goals_chat.php",
  "line": 164
}
```

---

## 6. 사용 예시

### 6.1 JavaScript (Fetch API)

```javascript
async function sendGoalMessage(message, context = null) {
  const response = await fetch(
    'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        message: message,
        context: context
      })
    }
  );

  const data = await response.json();

  if (data.success) {
    console.log('Response:', data.response.text);
    console.log('Persona:', data.persona.persona_name);
    return data;
  } else {
    throw new Error(data.error);
  }
}

// 사용 예시
sendGoalMessage('목표를 세우고 싶어요', 'G0')
  .then(result => {
    document.getElementById('response').textContent = result.response.text;
  })
  .catch(error => {
    console.error('Error:', error);
  });
```

### 6.2 PHP (cURL)

```php
<?php
function callGoalsChat($message, $userId = null, $context = null) {
    $url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php';

    $data = [
        'message' => $message,
        'context' => $context
    ];

    if ($userId) {
        $data['user_id'] = $userId;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode !== 200 || !$result['success']) {
        throw new Exception($result['error'] ?? 'Unknown error');
    }

    return $result;
}

// 사용 예시
try {
    $result = callGoalsChat('수학 공부 목표를 세우고 싶어요', 123, 'G0');
    echo $result['response']['text'];
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

### 6.3 jQuery AJAX

```javascript
$.ajax({
  url: 'https://mathking.kr/.../api/goals_chat.php',
  method: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({
    message: '목표 진행 상황을 알려주세요',
    context: 'G1'
  }),
  success: function(data) {
    if (data.success) {
      $('#chat-response').html(data.response.text);
      $('#persona-info').text(data.persona.persona_name);
    }
  },
  error: function(xhr) {
    console.error('API Error:', xhr.responseJSON);
  }
});
```

### 6.4 Python (requests)

```python
import requests

def call_goals_chat(message, user_id=None, context=None):
    url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php'

    payload = {
        'message': message,
        'context': context
    }

    if user_id:
        payload['user_id'] = user_id

    response = requests.post(url, json=payload)
    data = response.json()

    if data.get('success'):
        return data
    else:
        raise Exception(data.get('error', 'Unknown error'))

# 사용 예시
result = call_goals_chat('목표를 달성했어요!', user_id=123, context='G1')
print(f"응답: {result['response']['text']}")
print(f"페르소나: {result['persona']['persona_name']}")
```

---

## 7. 통합 가이드

### 7.1 채팅 UI 통합

```html
<!DOCTYPE html>
<html>
<head>
    <title>Goal Chat Integration</title>
    <style>
        .chat-container { max-width: 600px; margin: 0 auto; }
        .message { padding: 10px; margin: 5px 0; border-radius: 10px; }
        .user-message { background: #e3f2fd; text-align: right; }
        .bot-message { background: #f5f5f5; }
        .persona-tag { font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div id="chat-messages"></div>
        <input type="text" id="message-input" placeholder="메시지를 입력하세요">
        <button onclick="sendMessage()">전송</button>
    </div>

    <script>
    const API_URL = 'https://mathking.kr/.../api/goals_chat.php';

    async function sendMessage() {
        const input = document.getElementById('message-input');
        const message = input.value.trim();
        if (!message) return;

        // 사용자 메시지 표시
        appendMessage(message, 'user');
        input.value = '';

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();

            if (data.success) {
                appendMessage(data.response.text, 'bot', data.persona);

                // 후속 질문 표시
                if (data.response.follow_up_questions?.length) {
                    appendFollowUps(data.response.follow_up_questions);
                }
            }
        } catch (error) {
            appendMessage('오류가 발생했습니다.', 'bot');
        }
    }

    function appendMessage(text, type, persona = null) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = `message ${type}-message`;
        div.innerHTML = text;

        if (persona) {
            div.innerHTML += `<div class="persona-tag">${persona.persona_name} (${persona.tone})</div>`;
        }

        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function appendFollowUps(questions) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'follow-ups';
        div.innerHTML = '<strong>💡 추천 질문:</strong><br>' +
            questions.map(q => `<button onclick="askQuestion('${q}')">${q}</button>`).join(' ');
        container.appendChild(div);
    }

    function askQuestion(q) {
        document.getElementById('message-input').value = q;
        sendMessage();
    }
    </script>
</body>
</html>
```

### 7.2 Moodle 블록 통합

```php
<?php
// blocks/goal_chat/block_goal_chat.php
class block_goal_chat extends block_base {
    public function init() {
        $this->title = '목표 코칭 챗봇';
    }

    public function get_content() {
        global $USER;

        $this->content = new stdClass();
        $this->content->text = '
            <div id="goal-chat-widget">
                <div id="goal-messages"></div>
                <input type="text" id="goal-input" placeholder="목표에 대해 이야기해 보세요">
                <button onclick="sendGoalMessage()">전송</button>
            </div>
            <script>
            var userId = ' . $USER->id . ';
            var apiUrl = "' . new moodle_url('/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php') . '";

            function sendGoalMessage() {
                var msg = document.getElementById("goal-input").value;
                fetch(apiUrl, {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({message: msg, user_id: userId})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("goal-messages").innerHTML +=
                            "<p><strong>나:</strong> " + msg + "</p>" +
                            "<p><strong>코치:</strong> " + data.response.text + "</p>";
                    }
                });
                document.getElementById("goal-input").value = "";
            }
            </script>
        ';

        return $this->content;
    }
}
```

### 7.3 Rate Limiting 권장사항

| 구분 | 제한 | 권장 |
|------|------|------|
| 사용자당 | 60 requests/min | 30 requests/min |
| IP당 | 100 requests/min | 50 requests/min |
| 전체 | 1000 requests/min | 500 requests/min |

### 7.4 캐싱 전략

```php
// Redis 캐싱 예시
function getCachedResponse($userId, $messageHash) {
    $cacheKey = "goal_chat:{$userId}:{$messageHash}";
    $cached = $redis->get($cacheKey);

    if ($cached) {
        return json_decode($cached, true);
    }

    return null;
}

function setCachedResponse($userId, $messageHash, $response, $ttl = 300) {
    $cacheKey = "goal_chat:{$userId}:{$messageHash}";
    $redis->setex($cacheKey, $ttl, json_encode($response));
}
```

---

## 부록: API 테스트 체크리스트

### 기본 테스트

- [ ] GET 요청으로 API 정보 확인
- [ ] POST 요청으로 기본 메시지 전송
- [ ] message 누락 시 에러 응답 확인
- [ ] 각 컨텍스트(G0~G3, CRISIS) 테스트

### 위기 감지 테스트

- [ ] level_0 키워드 테스트 (주의: 실제 위기 상황처럼 로깅됨)
- [ ] level_1~3 키워드 테스트
- [ ] 위기 응답 형식 확인

### 통합 테스트

- [ ] 프론트엔드 연동 테스트
- [ ] 사용자 ID 전달 테스트
- [ ] CORS 설정 확인

---

**파일 위치**: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/API_GUIDE.md`
