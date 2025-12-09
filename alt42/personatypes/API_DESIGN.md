# API 및 컴포넌트 인터페이스 설계

## API 엔드포인트 설계

### 1. AI 에이전트 API

#### POST /api/agent/chat.php
학생의 성찰에 대한 AI 응답 생성

**Request:**
```json
{
    "user_id": 123,
    "node_id": 5,
    "reflection_text": "오늘 수학 문제를 풀면서...",
    "emotion_context": {
        "previous_mood": "anxious",
        "current_activity": "problem_solving"
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "feedback": {
            "encouragement": "네가 문제를 끝까지 해결하려는 모습이 정말 멋져!",
            "insight": "불안함을 느끼면서도 포기하지 않은 것은 큰 성장이야.",
            "next_step": "다음에는 비슷한 문제를 만나면 이 경험을 떠올려봐."
        },
        "emotion_detected": "determination",
        "confidence_boost": 0.15
    }
}
```

#### POST /api/agent/analyze.php
학생의 감정 상태 및 학습 패턴 분석

**Request:**
```json
{
    "user_id": 123,
    "text": "답변 텍스트",
    "context": "current_node"
}
```

**Response:**
```json
{
    "emotion": "frustrated_but_trying",
    "confidence_level": 0.65,
    "learning_pattern": "visual_learner",
    "suggestions": ["그림으로 그려보기", "단계별로 나누어 접근하기"]
}
```

### 2. 학생 API

#### GET /api/student/progress.php
학생의 여정 진행 상태 조회

**Request:**
```
GET /api/student/progress.php?user_id=123
```

**Response:**
```json
{
    "success": true,
    "data": {
        "completed_nodes": [0, 1, 3],
        "unlocked_nodes": [0, 1, 2, 3, 4],
        "current_node": 4,
        "total_reflections": 3,
        "achievements": ["first_step", "consistent_learner"],
        "dopamine_events": 5
    }
}
```

#### POST /api/student/reflection.php
성찰 저장 및 진행 상태 업데이트

**Request:**
```json
{
    "user_id": 123,
    "node_id": 4,
    "reflection_text": "성찰 내용...",
    "time_spent": 300
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "reflection_id": 456,
        "next_nodes_unlocked": [5, 6],
        "achievement_earned": "deep_thinker"
    }
}
```

### 3. 교사 API

#### GET /api/teacher/dashboard.php
학급 전체 현황 대시보드 데이터

**Request:**
```
GET /api/teacher/dashboard.php?class_id=789
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_students": 25,
        "active_today": 18,
        "average_progress": 0.65,
        "emotion_summary": {
            "positive": 15,
            "neutral": 7,
            "needs_attention": 3
        },
        "insights": [
            {
                "student_id": 123,
                "type": "low_confidence",
                "message": "지속적으로 불안감을 표현하고 있습니다",
                "priority": "high"
            }
        ]
    }
}
```

## 핵심 클래스 인터페이스

### Agent 클래스
```php
class Agent {
    private $openai;
    private $prompts;
    
    public function generateResponse($reflection, $context);
    public function analyzeEmotion($text);
    public function createPersonalizedFeedback($userId, $nodeId, $response);
    public function selectPromptTemplate($emotionState, $progressLevel);
}
```

### Student 클래스
```php
class Student {
    private $userId;
    private $profile;
    
    public function getProgress();
    public function updateProgress($nodeId, $status);
    public function saveReflection($nodeId, $text);
    public function trackDopamineEvent($type, $intensity);
    public function earnAchievement($type);
}
```

### Journey 클래스
```php
class Journey {
    private $nodes;
    private $connections;
    
    public function getNodeData($nodeId);
    public function checkUnlockConditions($userId, $nodeId);
    public function getNextAvailableNodes($currentNode);
    public function calculateProgress($userId);
}
```

## 프론트엔드 컴포넌트 인터페이스

### JourneyMap 컴포넌트
```javascript
class JourneyMap {
    constructor(containerId, userId) {
        this.container = document.getElementById(containerId);
        this.userId = userId;
        this.nodes = [];
        this.connections = [];
    }
    
    init() {}
    renderNodes() {}
    renderConnections() {}
    handleNodeClick(nodeId) {}
    updateNodeStatus(nodeId, status) {}
    animateUnlock(nodeIds) {}
}
```

### AIAgent 컴포넌트
```javascript
class AIAgent {
    constructor(avatarId, speechId) {
        this.avatar = document.getElementById(avatarId);
        this.speech = document.getElementById(speechId);
    }
    
    showMessage(message, emotion) {}
    animateAvatar(animationType) {}
    async getResponse(reflection) {}
    displayFeedback(feedback) {}
}
```

### ReflectionPanel 컴포넌트
```javascript
class ReflectionPanel {
    constructor(panelId) {
        this.panel = document.getElementById(panelId);
        this.currentNode = null;
    }
    
    open(nodeId) {}
    close() {}
    loadQuestion(nodeId) {}
    submitReflection() {}
    showFeedback(feedback) {}
}
```

## 에러 처리 표준

모든 API 응답은 다음 형식을 따름:

**성공:**
```json
{
    "success": true,
    "data": { ... }
}
```

**실패:**
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "사용자 친화적 메시지",
        "details": "개발자용 상세 정보"
    }
}
```

## 보안 헤더
모든 API 요청에 포함:
- `X-Auth-Token`: Moodle 세션 토큰
- `X-User-Id`: 사용자 ID
- `X-Request-Time`: 타임스탬프

## Rate Limiting
- AI API: 분당 10회
- 일반 API: 분당 60회
- 초과 시 429 에러 반환