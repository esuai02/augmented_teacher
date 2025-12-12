# Agent05 Learning Emotion Persona System - Integration Guide

> **Version**: 1.0.0
> **Last Updated**: 2025-06-03

---

## 1. 사전 요구사항

### 1.1 시스템 요구사항

| 항목 | 최소 버전 | 권장 버전 |
|------|----------|----------|
| PHP | 7.1.9 | 7.4+ |
| MySQL | 5.7 | 5.7+ |
| Moodle | 3.7 | 3.7+ |

### 1.2 필수 파일 의존성

```
ontology_engineering/persona_engine/
├── AbstractPersonaEngine.php
├── interfaces/
│   ├── IDataContext.php
│   └── IResponseGenerator.php
└── db/
    └── install.php (공통 테이블)
```

### 1.3 필수 Moodle 권한

- `local/augmented_teacher:use`
- `local/augmented_teacher:manage`

---

## 2. 설치 절차

### 2.1 Step 1: 파일 배포

```bash
# 프로젝트 루트에서 실행
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/

# 파일 구조 확인
ls -la agents/agent05_learning_emotion/persona_system/
```

예상 파일 구조:
```
persona_system/
├── engine/
│   ├── Agent05PersonaEngine.php
│   ├── Agent05DataContext.php
│   ├── Agent05ResponseGenerator.php
│   ├── EmotionAnalyzer.php
│   └── LearningActivityDetector.php
├── db/
│   ├── schema.php
│   ├── EmotionStateRepository.php
│   └── InterAgentCommunicator.php
├── templates/
│   ├── personas.yaml
│   ├── emotion_templates.yaml
│   └── rules.yaml
└── docs/
    └── *.md
```

### 2.2 Step 2: 데이터베이스 테이블 생성

#### 방법 1: 브라우저 실행

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/db/schema.php
```

웹 UI에서 "Create All Tables" 버튼 클릭.

#### 방법 2: CLI 실행

```php
<?php
// install_agent05_tables.php
include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . '/agents/agent05_learning_emotion/persona_system/db/schema.php');

use AugmentedTeacher\Agent05\PersonaSystem\DB\Agent05SchemaManager;

$manager = new Agent05SchemaManager();
$results = $manager->createAllTables();

foreach ($results as $table => $status) {
    echo "{$table}: " . ($status ? 'SUCCESS' : 'FAILED') . "\n";
}
```

### 2.3 Step 3: 테이블 생성 확인

```sql
-- MySQL에서 직접 확인
SHOW TABLES LIKE 'mdl_at_%';

-- 기대 결과 (6개 테이블)
-- mdl_at_learning_emotion_log
-- mdl_at_emotion_transition_log
-- mdl_at_agent_emotion_share
-- mdl_at_learning_activity_log
-- mdl_at_persona_response_log
-- mdl_at_emotion_pattern
```

### 2.4 Step 4: 공통 엔진 의존성 확인

```php
<?php
// 공통 엔진 존재 확인
$enginePath = __DIR__ . '/ontology_engineering/persona_engine/AbstractPersonaEngine.php';
if (!file_exists($enginePath)) {
    die("Error: AbstractPersonaEngine.php not found at {$enginePath}");
}

// 인터페이스 확인
$interfacePath = __DIR__ . '/ontology_engineering/persona_engine/interfaces/IDataContext.php';
if (!file_exists($interfacePath)) {
    die("Error: IDataContext.php not found");
}

echo "Dependencies OK!";
```

---

## 3. 기본 통합

### 3.1 간단한 통합 예제

```php
<?php
/**
 * Agent05 기본 통합 예제
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 네임스페이스 로드
require_once(__DIR__ . '/agents/agent05_learning_emotion/persona_system/engine/Agent05PersonaEngine.php');

use AugmentedTeacher\Agent05\PersonaSystem\Engine\Agent05PersonaEngine;

// 엔진 초기화
$engine = new Agent05PersonaEngine();

// 사용자 메시지 처리
$userMessage = "이 문제 어떻게 풀어야 하는지 모르겠어요 ㅠㅠ";
$activityType = "problem_solving";

$result = $engine->processAndRespond($USER->id, $userMessage, $activityType);

if ($result['success']) {
    echo "응답: " . $result['response'];
    echo "선택된 페르소나: " . $result['persona'];
    echo "감지된 감정: " . $result['emotion']['type'];
} else {
    echo "처리 실패";
}
```

### 3.2 AJAX 엔드포인트 통합

```php
<?php
/**
 * ajax/agent05_emotion.php
 * Agent05 감정 분석 AJAX 엔드포인트
 */
define('AJAX_SCRIPT', true);
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../agents/agent05_learning_emotion/persona_system/engine/Agent05PersonaEngine.php');

use AugmentedTeacher\Agent05\PersonaSystem\Engine\Agent05PersonaEngine;

// 파라미터 수신
$message = required_param('message', PARAM_TEXT);
$activity = optional_param('activity', 'general', PARAM_ALPHA);

try {
    $engine = new Agent05PersonaEngine();
    $result = $engine->processAndRespond($USER->id, $message, $activity);

    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}
```

### 3.3 JavaScript 클라이언트 예제

```javascript
/**
 * Agent05 클라이언트 통합
 */
class Agent05Client {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || '/moodle/local/augmented_teacher/alt42/orchestration/ajax/';
    }

    /**
     * 감정 분석 및 응답 요청
     * @param {string} message - 사용자 메시지
     * @param {string} activity - 학습 활동 유형
     * @returns {Promise<Object>}
     */
    async analyzeAndRespond(message, activity = 'general') {
        const response = await fetch(this.baseUrl + 'agent05_emotion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `message=${encodeURIComponent(message)}&activity=${activity}`
        });

        return await response.json();
    }

    /**
     * 실시간 감정 피드백 표시
     * @param {HTMLElement} container - 표시할 컨테이너
     * @param {Object} emotionData - 감정 데이터
     */
    displayEmotionFeedback(container, emotionData) {
        const emotionColors = {
            'anxiety': '#ff9800',
            'frustration': '#f44336',
            'confidence': '#4caf50',
            'curiosity': '#2196f3',
            'boredom': '#9e9e9e',
            'fatigue': '#795548',
            'achievement': '#ffeb3b',
            'confusion': '#9c27b0'
        };

        const color = emotionColors[emotionData.type] || '#000';
        container.innerHTML = `
            <div style="border-left: 4px solid ${color}; padding: 10px;">
                <strong>${emotionData.type}</strong> (${emotionData.intensity})
                <br>신뢰도: ${Math.round(emotionData.confidence * 100)}%
            </div>
        `;
    }
}

// 사용 예시
const agent05 = new Agent05Client();

document.getElementById('sendBtn').addEventListener('click', async () => {
    const message = document.getElementById('userInput').value;
    const result = await agent05.analyzeAndRespond(message, 'problem_solving');

    if (result.success) {
        document.getElementById('response').innerText = result.data.response;
        agent05.displayEmotionFeedback(
            document.getElementById('emotionDisplay'),
            result.data.emotion
        );
    }
});
```

---

## 4. 에이전트간 통합

### 4.1 Agent06과 통합 (학습 접근법)

```php
<?php
/**
 * Agent05 → Agent06 감정 기반 접근법 권장
 */
require_once(__DIR__ . '/agents/agent05_learning_emotion/persona_system/db/InterAgentCommunicator.php');

use AugmentedTeacher\Agent05\PersonaSystem\DB\InterAgentCommunicator;

$communicator = new InterAgentCommunicator();

// 좌절감 고조시 Agent06에 알림
$emotionData = [
    'emotion_type' => 'frustration',
    'intensity' => 'high',
    'confidence' => 0.92,
    'context' => [
        'activity' => 'problem_solving',
        'consecutive_failures' => 3
    ]
];

$result = $communicator->shareEmotionInfo($userId, $emotionData);

// Agent06이 받는 데이터 형식
// {
//     "type": "emotion_alert",
//     "emotion": "frustration",
//     "intensity": "high",
//     "recommendation": "simplify_approach",
//     "priority": "high"
// }
```

### 4.2 Agent08과 통합 (피로 관리)

```php
<?php
/**
 * Agent05 → Agent08 피로 알림
 */
// 피로 감지시 Agent08에 알림
$fatigueData = [
    'fatigue_level' => 'high',
    'duration_minutes' => 45,
    'activity_type' => 'concept_understanding',
    'suggested_action' => 'break'
];

$communicator->notifyFatigue($userId, $fatigueData);

// Agent08이 수신하는 메시지
// {
//     "type": "fatigue_alert",
//     "action_needed": "suggest_break",
//     "priority": "high"
// }
```

### 4.3 Agent09과 통합 (학습 관리)

```php
<?php
/**
 * Agent05 → Agent09 감정 요약 전달
 */
// 세션 종료시 감정 요약 전달
$summaryData = [
    'session_duration' => 3600,  // 1시간
    'dominant_emotion' => 'curiosity',
    'emotion_transitions' => [
        ['from' => 'confusion', 'to' => 'curiosity', 'time' => 600],
        ['from' => 'curiosity', 'to' => 'confidence', 'time' => 2400]
    ],
    'intervention_count' => 2,
    'overall_sentiment' => 'positive'
];

$communicator->shareEmotionInfo($userId, [
    'emotion_type' => 'summary',
    'intensity' => 'n/a',
    'context' => $summaryData
]);
```

---

## 5. 커스터마이징

### 5.1 새 감정 타입 추가

#### Step 1: EmotionAnalyzer.php 수정

```php
// engine/EmotionAnalyzer.php
private const EMOTION_PATTERNS = [
    // 기존 패턴들...

    // 새 감정 추가: 기대감 (anticipation)
    'anticipation' => [
        'keywords' => ['기대', '설레', '두근', '궁금해', '어떨까'],
        'emoticons' => ['♡', '❤', '💕'],
        'intensity_modifiers' => [
            'high' => ['너무 기대', '정말 설레', '엄청 궁금'],
            'medium' => ['기대돼', '설레네'],
            'low' => ['좀 기대']
        ]
    ]
];
```

#### Step 2: emotion_templates.yaml 수정

```yaml
# templates/emotion_templates.yaml
emotions:
  # 기존 감정들...

  anticipation:
    high:
      - template: "{student_name}아, 기대가 크구나! 그 설렘을 잘 활용해보자."
        context: "general"
        tone: "enthusiastic"
    medium:
      - template: "기대되는 마음이 느껴져. 좋은 결과가 있을 거야."
        context: "general"
        tone: "supportive"
    low:
      - template: "조금 기대되는 것 같아. 천천히 해보자."
        context: "general"
        tone: "calm"
```

#### Step 3: rules.yaml 수정

```yaml
# templates/rules.yaml
persona_selection:
  emotion_mapping:
    anticipation:
      high:
        primary: "격려형"
        alternatives: ["동기형", "코치형"]
      medium:
        primary: "친근형"
      low:
        primary: "기본형"
```

#### Step 4: DB 스키마 업데이트

```sql
-- emotion_type ENUM에 새 값 추가
ALTER TABLE mdl_at_learning_emotion_log
MODIFY emotion_type ENUM(
    'anxiety', 'frustration', 'confidence', 'curiosity',
    'boredom', 'fatigue', 'achievement', 'confusion',
    'anticipation'  -- 새로 추가
);
```

### 5.2 새 페르소나 추가

#### Step 1: personas.yaml 수정

```yaml
# templates/personas.yaml
personas:
  # 기존 페르소나들...

  명상형:
    id: "meditation"
    name: "명상형"
    description: "마음 안정과 집중력 향상에 초점"
    characteristics:
      tone: "calm"
      pace: "slow"
      keywords: ["호흡", "천천히", "집중", "평온"]
    suitable_for:
      emotions: ["anxiety", "fatigue"]
      activities: ["pomodoro", "review"]
    avoid_for:
      emotions: ["boredom"]
```

#### Step 2: rules.yaml에 매핑 추가

```yaml
persona_selection:
  emotion_mapping:
    anxiety:
      high:
        primary: "차분형"
        alternatives: ["공감형", "명상형"]  # 새 페르소나 추가
```

### 5.3 응답 템플릿 커스터마이징

```yaml
# 특정 활동 + 감정 조합에 대한 커스텀 템플릿
activity_emotion_templates:
  problem_solving:
    frustration:
      high:
        - template: |
            {student_name}아, 문제가 잘 안 풀리는구나.
            한번 다른 방법으로 접근해볼까?
            1. 문제를 다시 한번 천천히 읽어보자
            2. 어떤 개념이 필요한지 생각해보자
            3. 비슷한 유형을 먼저 풀어봐도 좋아
          context: "step_by_step"
          tone: "supportive"
```

---

## 6. 테스트 및 검증

### 6.1 단위 테스트

```php
<?php
/**
 * Agent05 단위 테스트
 * tests/agent05_test.php
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/../agents/agent05_learning_emotion/persona_system/engine/EmotionAnalyzer.php');
require_once(__DIR__ . '/../agents/agent05_learning_emotion/persona_system/engine/LearningActivityDetector.php');

use AugmentedTeacher\Agent05\PersonaSystem\Engine\EmotionAnalyzer;
use AugmentedTeacher\Agent05\PersonaSystem\Engine\LearningActivityDetector;

// 테스트 케이스
$testCases = [
    [
        'input' => '너무 어려워요 ㅠㅠ 모르겠어요',
        'expected_emotion' => 'frustration',
        'expected_intensity' => 'high'
    ],
    [
        'input' => '아 이제 알겠다!',
        'expected_emotion' => 'achievement',
        'expected_intensity' => 'medium'
    ],
    [
        'input' => '이거 왜 해야 해요...',
        'expected_emotion' => 'boredom',
        'expected_intensity' => 'medium'
    ]
];

$analyzer = new EmotionAnalyzer();
$passed = 0;
$failed = 0;

foreach ($testCases as $i => $test) {
    $result = $analyzer->analyze($test['input']);

    $emotionMatch = ($result['emotion_type'] === $test['expected_emotion']);
    $intensityMatch = ($result['intensity'] === $test['expected_intensity']);

    if ($emotionMatch && $intensityMatch) {
        echo "✅ Test {$i}: PASSED\n";
        $passed++;
    } else {
        echo "❌ Test {$i}: FAILED\n";
        echo "   Input: {$test['input']}\n";
        echo "   Expected: {$test['expected_emotion']} ({$test['expected_intensity']})\n";
        echo "   Got: {$result['emotion_type']} ({$result['intensity']})\n";
        $failed++;
    }
}

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
```

### 6.2 통합 테스트

```php
<?php
/**
 * Agent05 통합 테스트
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/../agents/agent05_learning_emotion/persona_system/engine/Agent05PersonaEngine.php');
require_once(__DIR__ . '/../agents/agent05_learning_emotion/persona_system/db/EmotionStateRepository.php');

use AugmentedTeacher\Agent05\PersonaSystem\Engine\Agent05PersonaEngine;
use AugmentedTeacher\Agent05\PersonaSystem\DB\EmotionStateRepository;

// 테스트용 사용자 ID (실제 환경에서는 테스트 계정 사용)
$testUserId = $USER->id;

echo "=== Integration Test Start ===\n\n";

// 1. 엔진 초기화 테스트
echo "1. Engine Initialization...\n";
try {
    $engine = new Agent05PersonaEngine();
    echo "   ✅ Engine initialized\n";
} catch (Exception $e) {
    echo "   ❌ Engine init failed: " . $e->getMessage() . "\n";
    die();
}

// 2. 감정 분석 및 응답 테스트
echo "\n2. Emotion Analysis & Response...\n";
$testMessages = [
    ['msg' => '문제가 너무 어려워요 ㅠㅠ', 'activity' => 'problem_solving'],
    ['msg' => '와 이제 이해했어요!', 'activity' => 'concept_understanding'],
    ['msg' => '좀 지루해요...', 'activity' => 'review']
];

foreach ($testMessages as $test) {
    $result = $engine->processAndRespond($testUserId, $test['msg'], $test['activity']);
    if ($result['success']) {
        echo "   ✅ '{$test['msg']}' → {$result['emotion']['type']} → {$result['persona']}\n";
    } else {
        echo "   ❌ Failed for: {$test['msg']}\n";
    }
}

// 3. Repository 테스트
echo "\n3. Repository Operations...\n";
$repo = new EmotionStateRepository();

// 저장 테스트
$savedId = $repo->saveEmotionState(
    $testUserId,
    'curiosity',
    'medium',
    0.85,
    'mixed',
    '테스트 트리거'
);
echo $savedId ? "   ✅ Save: ID {$savedId}\n" : "   ❌ Save failed\n";

// 조회 테스트
$recent = $repo->getRecentEmotions($testUserId, 5);
echo "   ✅ Recent emotions: " . count($recent) . " records\n";

// 분포 테스트
$distribution = $repo->getEmotionDistribution($testUserId, 7);
echo "   ✅ Distribution: " . count($distribution) . " categories\n";

echo "\n=== Integration Test Complete ===\n";
```

### 6.3 DB 테이블 검증 스크립트

```php
<?php
/**
 * DB 테이블 상태 검증
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

$tables = [
    'at_learning_emotion_log',
    'at_emotion_transition_log',
    'at_agent_emotion_share',
    'at_learning_activity_log',
    'at_persona_response_log',
    'at_emotion_pattern'
];

echo "=== DB Table Verification ===\n\n";

$dbManager = $DB->get_manager();

foreach ($tables as $table) {
    $exists = $dbManager->table_exists($table);
    $fullName = "mdl_{$table}";

    if ($exists) {
        // 레코드 수 확인
        $count = $DB->count_records($table);
        echo "✅ {$fullName}: EXISTS ({$count} records)\n";
    } else {
        echo "❌ {$fullName}: NOT FOUND\n";
    }
}
```

---

## 7. 트러블슈팅

### 7.1 일반적인 문제

| 문제 | 원인 | 해결책 |
|------|------|--------|
| "Class not found" 에러 | 파일 경로 또는 네임스페이스 오류 | require_once 경로 및 use 문 확인 |
| DB 테이블 없음 | 스키마 미생성 | schema.php 실행 |
| 감정 감지 안됨 | 패턴 매칭 실패 | EmotionAnalyzer 로그 확인 |
| 응답 생성 실패 | 템플릿 YAML 오류 | YAML 문법 검증 |
| 에이전트 통신 실패 | at_agent_messages 테이블 누락 | 공통 테이블 확인 |

### 7.2 디버그 모드 활성화

```php
<?php
// 디버그 설정
define('AGENT05_DEBUG', true);

// EmotionAnalyzer에서 디버그 출력
if (defined('AGENT05_DEBUG') && AGENT05_DEBUG) {
    error_log("[Agent05] Analyzing: {$text}");
    error_log("[Agent05] Detected: {$emotionType} ({$intensity})");
}
```

### 7.3 로그 확인

```bash
# Moodle 로그 확인
tail -f /home/moodle/moodledata/error.log | grep Agent05

# Apache 에러 로그
tail -f /var/log/apache2/error.log | grep Agent05
```

---

## 8. 성능 최적화

### 8.1 YAML 캐싱

```php
<?php
// 템플릿 캐싱 예제
class YamlCache {
    private static $cache = [];

    public static function load($filePath) {
        if (!isset(self::$cache[$filePath])) {
            self::$cache[$filePath] = yaml_parse_file($filePath);
        }
        return self::$cache[$filePath];
    }
}
```

### 8.2 DB 쿼리 최적화

```sql
-- 자주 사용되는 쿼리에 대한 인덱스 추가
CREATE INDEX idx_emotion_user_time
ON mdl_at_learning_emotion_log (userid, timecreated DESC);

CREATE INDEX idx_transition_user
ON mdl_at_emotion_transition_log (userid, timecreated DESC);
```

### 8.3 배치 처리

```php
<?php
// 에이전트 공유 배치 처리
$communicator->batchShareEmotions($userId, $emotionBatch, [
    'batch_size' => 10,
    'delay_ms' => 100
]);
```

---

**문서 끝**
