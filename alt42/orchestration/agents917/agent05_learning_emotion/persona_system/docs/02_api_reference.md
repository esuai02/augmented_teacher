# Agent05 Learning Emotion Persona System - API Reference

> **Version**: 1.0.0
> **Last Updated**: 2025-06-03

---

## 목차

1. [Agent05PersonaEngine](#1-agent05personaengine)
2. [EmotionAnalyzer](#2-emotionanalyzer)
3. [LearningActivityDetector](#3-learningactivitydetector)
4. [Agent05DataContext](#4-agent05datacontext)
5. [Agent05ResponseGenerator](#5-agent05responsegenerator)
6. [EmotionStateRepository](#6-emotionstaterepository)
7. [InterAgentCommunicator](#7-interagentcommunicator)
8. [데이터 타입 및 상수](#8-데이터-타입-및-상수)

---

## 1. Agent05PersonaEngine

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\Engine`
**파일**: `engine/Agent05PersonaEngine.php`

### 1.1 생성자

```php
public function __construct()
```

Moodle 환경을 초기화하고 의존성 컴포넌트를 로드합니다.

### 1.2 주요 메서드

#### processAndRespond()

```php
public function processAndRespond(
    int $userId,
    string $userMessage,
    string $activityType = 'general'
): array
```

**설명**: 사용자 메시지를 처리하고 페르소나 기반 응답을 생성합니다.

**매개변수**:
| 이름 | 타입 | 필수 | 설명 |
|------|------|------|------|
| userId | int | ✓ | Moodle 사용자 ID |
| userMessage | string | ✓ | 사용자 입력 텍스트 |
| activityType | string | | 학습 활동 유형 (기본: 'general') |

**반환값**:
```php
[
    'success' => bool,
    'response' => string,        // 생성된 응답 텍스트
    'persona' => string,         // 선택된 페르소나 타입
    'emotion' => [
        'type' => string,        // 감지된 감정 타입
        'intensity' => string,   // 감정 강도
        'confidence' => float    // 신뢰도 (0.0 ~ 1.0)
    ],
    'metadata' => array          // 추가 메타데이터
]
```

**예시**:
```php
$engine = new Agent05PersonaEngine();
$result = $engine->processAndRespond(
    123,
    "이 문제 너무 어려워요 ㅠㅠ",
    "problem_solving"
);

// 결과:
// [
//     'success' => true,
//     'response' => '어려운 문제 만났구나. 괜찮아, 같이 차근차근 풀어보자.',
//     'persona' => '차분형',
//     'emotion' => ['type' => 'frustration', 'intensity' => 'high', 'confidence' => 0.85]
// ]
```

---

## 2. EmotionAnalyzer

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\Engine`
**파일**: `engine/EmotionAnalyzer.php`

### 2.1 생성자

```php
public function __construct()
```

### 2.2 주요 메서드

#### analyze()

```php
public function analyze(string $text): array
```

**설명**: 텍스트에서 감정을 분석합니다.

**매개변수**:
| 이름 | 타입 | 필수 | 설명 |
|------|------|------|------|
| text | string | ✓ | 분석할 텍스트 |

**반환값**:
```php
[
    'emotion_type' => string,      // 주요 감정 타입
    'intensity' => string,         // high|medium|low
    'confidence' => float,         // 신뢰도 (0.0 ~ 1.0)
    'detection_source' => string,  // keyword|pattern|emoticon|mixed
    'secondary_emotions' => array, // 부수 감정 목록
    'trigger_text' => string       // 감지 트리거 텍스트
]
```

**예시**:
```php
$analyzer = new EmotionAnalyzer();
$result = $analyzer->analyze("정말 짜증나!! 왜 안되는 거야 ㅡㅡ");

// 결과:
// [
//     'emotion_type' => 'frustration',
//     'intensity' => 'high',
//     'confidence' => 0.92,
//     'detection_source' => 'mixed',
//     'secondary_emotions' => ['confusion'],
//     'trigger_text' => '짜증나'
// ]
```

#### detectEmoticons()

```php
public function detectEmoticons(string $text): array
```

**설명**: 텍스트에서 이모티콘 기반 감정을 감지합니다.

**반환값**:
```php
[
    'emoticons' => ['ㅠㅠ', ';;'],
    'emotions' => ['sadness', 'anxiety'],
    'intensity_modifier' => float  // 강도 조절자
]
```

#### getIntensityLevel()

```php
public function getIntensityLevel(
    string $text,
    string $emotionType
): string
```

**설명**: 감정의 강도 수준을 판별합니다.

**반환값**: `'high'` | `'medium'` | `'low'`

---

## 3. LearningActivityDetector

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\Engine`
**파일**: `engine/LearningActivityDetector.php`

### 3.1 생성자

```php
public function __construct()
```

### 3.2 주요 메서드

#### detect()

```php
public function detect(
    string $text,
    array $contextData = []
): array
```

**설명**: 학습 활동 유형을 감지합니다.

**매개변수**:
| 이름 | 타입 | 필수 | 설명 |
|------|------|------|------|
| text | string | ✓ | 분석할 텍스트 |
| contextData | array | | 추가 컨텍스트 (페이지, 이전 활동 등) |

**반환값**:
```php
[
    'activity_type' => string,     // 감지된 활동 타입
    'confidence' => float,         // 신뢰도
    'indicators' => array,         // 감지 근거 키워드
    'suggested_activities' => array // 대안 활동 목록
]
```

**예시**:
```php
$detector = new LearningActivityDetector();
$result = $detector->detect("이 유형의 문제는 어떻게 푸나요?");

// 결과:
// [
//     'activity_type' => 'type_learning',
//     'confidence' => 0.88,
//     'indicators' => ['유형', '문제', '풀'],
//     'suggested_activities' => ['problem_solving', 'qa']
// ]
```

#### getSupportedActivities()

```php
public function getSupportedActivities(): array
```

**설명**: 지원하는 모든 학습 활동 유형 목록을 반환합니다.

**반환값**:
```php
[
    'concept_understanding' => ['name' => '개념이해', 'description' => '...'],
    'type_learning' => ['name' => '유형학습', 'description' => '...'],
    // ... 8개 활동 유형
]
```

---

## 4. Agent05DataContext

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\Engine`
**파일**: `engine/Agent05DataContext.php`
**구현 인터페이스**: `IDataContext`

### 4.1 생성자

```php
public function __construct()
```

### 4.2 주요 메서드

#### getContext()

```php
public function getContext(array $params): array
```

**설명**: 페르소나 선택에 필요한 전체 컨텍스트를 수집합니다.

**매개변수**:
```php
$params = [
    'user_id' => int,           // 필수: 사용자 ID
    'emotion_data' => array,    // 선택: 현재 감정 데이터
    'activity_type' => string   // 선택: 학습 활동 유형
];
```

**반환값**:
```php
[
    'user_profile' => [...],           // 사용자 프로필
    'emotion_history' => [...],        // 최근 감정 히스토리
    'activity_history' => [...],       // 학습 활동 히스토리
    'persona_effectiveness' => [...],  // 페르소나 효과성 데이터
    'current_state' => [...]           // 현재 상태 요약
]
```

#### getEmotionHistory()

```php
public function getEmotionHistory(int $userId, int $limit = 10): array
```

**설명**: 사용자의 감정 히스토리를 조회합니다.

#### getPersonaEffectiveness()

```php
public function getPersonaEffectiveness(int $userId): array
```

**설명**: 사용자별 페르소나 효과성 통계를 반환합니다.

---

## 5. Agent05ResponseGenerator

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\Engine`
**파일**: `engine/Agent05ResponseGenerator.php`
**구현 인터페이스**: `IResponseGenerator`

### 5.1 생성자

```php
public function __construct()
```

YAML 템플릿 파일을 로드합니다.

### 5.2 주요 메서드

#### generateResponse()

```php
public function generateResponse(
    string $personaType,
    array $context
): string
```

**설명**: 지정된 페르소나로 응답을 생성합니다.

**매개변수**:
| 이름 | 타입 | 필수 | 설명 |
|------|------|------|------|
| personaType | string | ✓ | 페르소나 타입 (예: '차분형') |
| context | array | ✓ | 응답 생성에 필요한 컨텍스트 |

**컨텍스트 구조**:
```php
$context = [
    'emotion' => [
        'type' => 'frustration',
        'intensity' => 'high'
    ],
    'student_name' => '철수',
    'activity_type' => 'problem_solving',
    'topic' => '미적분',
    'custom_vars' => [...]  // 추가 변수
];
```

**반환값**: 생성된 응답 텍스트 (string)

**예시**:
```php
$generator = new Agent05ResponseGenerator();
$response = $generator->generateResponse('차분형', [
    'emotion' => ['type' => 'anxiety', 'intensity' => 'high'],
    'student_name' => '민수'
]);

// 결과: "민수야, 많이 걱정되고 있구나... 괜찮아, 선생님이 같이 해줄게."
```

#### getTemplateForEmotion()

```php
public function getTemplateForEmotion(
    string $emotionType,
    string $intensity
): array
```

**설명**: 특정 감정과 강도에 맞는 템플릿 목록을 반환합니다.

#### applyVariables()

```php
public function applyVariables(
    string $template,
    array $variables
): string
```

**설명**: 템플릿에 변수를 적용합니다.

---

## 6. EmotionStateRepository

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\DB`
**파일**: `db/EmotionStateRepository.php`

### 6.1 생성자

```php
public function __construct()
```

### 6.2 감정 로그 메서드

#### saveEmotionState()

```php
public function saveEmotionState(
    int $userId,
    string $emotionType,
    string $intensity,
    float $confidenceScore,
    string $detectionSource = 'mixed',
    ?string $triggerText = null,
    array $contextData = []
): int|false
```

**설명**: 새 감정 상태를 저장합니다.

**반환값**: 삽입된 레코드 ID 또는 실패시 `false`

#### getRecentEmotions()

```php
public function getRecentEmotions(int $userId, int $limit = 10): array
```

**설명**: 최근 감정 로그를 조회합니다.

#### getCurrentEmotion()

```php
public function getCurrentEmotion(int $userId): ?object
```

**설명**: 현재(최신) 감정 상태를 조회합니다.

#### getEmotionsByPeriod()

```php
public function getEmotionsByPeriod(
    int $userId,
    int $startTime,
    int $endTime,
    ?string $emotionType = null
): array
```

**설명**: 특정 기간의 감정 로그를 조회합니다.

### 6.3 전환 로그 메서드

#### saveEmotionTransition()

```php
public function saveEmotionTransition(
    int $userId,
    string $fromEmotion,
    string $toEmotion,
    string $triggerType,
    ?int $fromEmotionLogId = null,
    ?int $toEmotionLogId = null,
    array $transitionData = []
): int|false
```

**설명**: 감정 전환을 기록합니다.

#### getTransitionHistory()

```php
public function getTransitionHistory(int $userId, int $limit = 20): array
```

**설명**: 감정 전환 히스토리를 조회합니다.

### 6.4 분석 메서드

#### getEmotionDistribution()

```php
public function getEmotionDistribution(int $userId, ?int $days = 30): array
```

**설명**: 감정 분포 통계를 조회합니다.

**반환값**:
```php
[
    (object)[
        'emotion_type' => 'anxiety',
        'emotion_intensity' => 'high',
        'count' => 15,
        'avg_confidence' => 0.82,
        'first_occurrence' => 1717200000,
        'last_occurrence' => 1717400000
    ],
    // ...
]
```

#### getEmotionTrend()

```php
public function getEmotionTrend(int $userId, int $days = 7): array
```

**설명**: 시간대별 감정 추세를 분석합니다.

#### detectNegativeStreak()

```php
public function detectNegativeStreak(int $userId, int $threshold = 3): array
```

**설명**: 연속 부정적 감정을 감지합니다.

**반환값**:
```php
[
    'has_streak' => true,
    'streak_count' => 4,
    'emotions' => [...]  // 연속된 감정 레코드
]
```

#### analyzeTriggers()

```php
public function analyzeTriggers(int $userId, string $emotionType): array
```

**설명**: 특정 감정의 트리거를 분석합니다.

---

## 7. InterAgentCommunicator

**네임스페이스**: `AugmentedTeacher\Agent05\PersonaSystem\DB`
**파일**: `db/InterAgentCommunicator.php`

### 7.1 생성자

```php
public function __construct()
```

### 7.2 주요 메서드

#### shareEmotionInfo()

```php
public function shareEmotionInfo(int $userId, array $emotionData): array
```

**설명**: 감정 정보를 다른 에이전트와 공유합니다.

**emotionData 구조**:
```php
$emotionData = [
    'emotion_type' => 'frustration',
    'intensity' => 'high',
    'confidence' => 0.85,
    'activity_type' => 'problem_solving',
    'context' => [...]
];
```

**반환값**:
```php
[
    'success' => true,
    'shared_to' => ['agent06', 'agent07'],
    'message_ids' => [123, 124]
]
```

#### sendUrgentAlert()

```php
public function sendUrgentAlert(
    int $userId,
    string $targetAgent,
    string $alertType,
    array $data
): array
```

**설명**: 특정 에이전트에 긴급 알림을 전송합니다.

**매개변수**:
| 이름 | 타입 | 설명 |
|------|------|------|
| targetAgent | string | 'agent06', 'agent07', 'agent08', 'agent09' |
| alertType | string | 알림 유형 (예: 'frustration_escalation') |
| data | array | 알림 데이터 |

#### notifyFrustrationEscalation()

```php
public function notifyFrustrationEscalation(
    int $userId,
    array $frustrationData
): array
```

**설명**: 좌절감 고조를 관련 에이전트에 알립니다.

#### notifyFatigue()

```php
public function notifyFatigue(int $userId, array $fatigueData): array
```

**설명**: 피로 상태를 Agent08에 알립니다.

#### syncCelebration()

```php
public function syncCelebration(int $userId, array $achievementData): array
```

**설명**: 성취 달성을 모든 관련 에이전트와 동기화합니다.

---

## 8. 데이터 타입 및 상수

### 8.1 감정 타입 (EmotionType)

```php
const EMOTION_TYPES = [
    'anxiety',      // 불안
    'frustration',  // 좌절
    'confidence',   // 자신감
    'curiosity',    // 호기심
    'boredom',      // 지루함
    'fatigue',      // 피로
    'achievement',  // 성취감
    'confusion'     // 혼란
];
```

### 8.2 감정 강도 (EmotionIntensity)

```php
const INTENSITIES = [
    'high',    // 높음
    'medium',  // 중간
    'low'      // 낮음
];
```

### 8.3 학습 활동 타입 (ActivityType)

```php
const ACTIVITY_TYPES = [
    'concept_understanding',  // 개념이해
    'type_learning',          // 유형학습
    'problem_solving',        // 문제풀이
    'error_note',             // 오답노트
    'qa',                     // Q&A
    'review',                 // 복습
    'pomodoro',               // 포모도로
    'home_check'              // 홈체크
];
```

### 8.4 감지 소스 (DetectionSource)

```php
const DETECTION_SOURCES = [
    'keyword',   // 키워드 패턴
    'pattern',   // 복합 패턴
    'emoticon',  // 이모티콘
    'mixed',     // 혼합
    'ai'         // AI 분석
];
```

### 8.5 전환 트리거 타입 (TransitionTrigger)

```php
const TRANSITION_TRIGGERS = [
    'user_input',       // 사용자 입력
    'time_based',       // 시간 기반
    'activity_change',  // 활동 변경
    'external_event'    // 외부 이벤트
];
```

### 8.6 페르소나 타입

22개 페르소나는 `personas.yaml` 파일에 정의되어 있습니다.
주요 페르소나:
- 친근형, 차분형, 분석형, 격려형, 유머형
- 엄격형, 실용형, 감성형, 논리형, 창의형
- 공감형, 코치형, 멘토형, 탐구형, 성찰형
- 동기형, 집중형, 협력형, 도전형, 배려형, 기본형, 상담형

---

## 에러 코드

| 코드 | 설명 |
|------|------|
| E_INVALID_EMOTION | 유효하지 않은 감정 타입 |
| E_INVALID_INTENSITY | 유효하지 않은 강도 값 |
| E_USER_NOT_FOUND | 사용자를 찾을 수 없음 |
| E_DB_ERROR | 데이터베이스 오류 |
| E_TEMPLATE_NOT_FOUND | 템플릿을 찾을 수 없음 |
| E_AGENT_COMM_FAILED | 에이전트 통신 실패 |

---

**문서 끝**
