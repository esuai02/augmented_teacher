# Agent05 Learning Emotion - Developer Quick Start Guide

> **5분 안에 Agent05 시작하기**
> **Version**: 1.0.0

---

## 🚀 빠른 시작 (3단계)

### Step 1: DB 테이블 생성

브라우저에서 접속:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/db/schema.php
```

"Create All Tables" 버튼 클릭 → 6개 테이블 생성 완료

### Step 2: 기본 코드 복사

```php
<?php
// my_page.php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Agent05 로드
$basePath = __DIR__ . '/agents/agent05_learning_emotion/persona_system/engine/';
require_once($basePath . 'Agent05PersonaEngine.php');

use AugmentedTeacher\Agent05\PersonaSystem\Engine\Agent05PersonaEngine;

// 사용
$engine = new Agent05PersonaEngine();
$result = $engine->processAndRespond(
    $USER->id,
    $_POST['message'] ?? '안녕하세요',
    $_POST['activity'] ?? 'general'
);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
```

### Step 3: 테스트

```bash
curl -X POST "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/my_page.php" \
  -d "message=이 문제 너무 어려워요 ㅠㅠ" \
  -d "activity=problem_solving"
```

예상 응답:
```json
{
  "success": true,
  "response": "어려운 문제 만났구나. 괜찮아, 같이 차근차근 풀어보자.",
  "persona": "차분형",
  "emotion": {
    "type": "frustration",
    "intensity": "high",
    "confidence": 0.85
  }
}
```

---

## 📁 파일 구조 요약

```
persona_system/
├── engine/                        # 핵심 로직
│   ├── Agent05PersonaEngine.php   # 메인 엔진 ⭐
│   ├── EmotionAnalyzer.php        # 감정 분석
│   └── ...
├── db/                            # 데이터베이스
│   ├── schema.php                 # 테이블 생성 ⭐
│   └── EmotionStateRepository.php # DB CRUD
├── templates/                     # 설정 파일
│   ├── personas.yaml              # 22개 페르소나
│   ├── emotion_templates.yaml     # 72개 응답 템플릿
│   └── rules.yaml                 # 선택 규칙
└── docs/                          # 문서
```

---

## 🎯 핵심 클래스 요약

### Agent05PersonaEngine (메인)

```php
$engine = new Agent05PersonaEngine();

// 메시지 처리 및 응답 생성
$result = $engine->processAndRespond($userId, $message, $activityType);
```

### EmotionAnalyzer (감정 분석)

```php
$analyzer = new EmotionAnalyzer();

// 텍스트에서 감정 감지
$emotion = $analyzer->analyze("너무 어려워요 ㅠㅠ");
// → ['emotion_type' => 'frustration', 'intensity' => 'high', ...]
```

### EmotionStateRepository (DB 저장)

```php
$repo = new EmotionStateRepository();

// 감정 저장
$id = $repo->saveEmotionState($userId, 'anxiety', 'high', 0.9);

// 최근 감정 조회
$recent = $repo->getRecentEmotions($userId, 10);

// 감정 분포 통계
$stats = $repo->getEmotionDistribution($userId, 30);
```

### InterAgentCommunicator (에이전트 통신)

```php
$comm = new InterAgentCommunicator();

// 다른 에이전트에 감정 공유
$comm->shareEmotionInfo($userId, $emotionData);

// 긴급 알림 전송
$comm->notifyFrustrationEscalation($userId, $data);
```

---

## 🔧 자주 사용하는 코드 패턴

### 패턴 1: 감정 분석만 하기

```php
$analyzer = new EmotionAnalyzer();
$emotion = $analyzer->analyze($userMessage);

if ($emotion['emotion_type'] === 'frustration' && $emotion['intensity'] === 'high') {
    // 좌절감이 높을 때 특별 처리
    sendSupportNotification($userId);
}
```

### 패턴 2: 감정 히스토리 기반 판단

```php
$repo = new EmotionStateRepository();
$streak = $repo->detectNegativeStreak($userId, 3);

if ($streak['has_streak']) {
    // 부정적 감정 3회 연속 → 개입 필요
    triggerIntervention($userId, $streak['emotions']);
}
```

### 패턴 3: AJAX 엔드포인트 만들기

```php
<?php
// ajax/emotion_check.php
define('AJAX_SCRIPT', true);
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$message = required_param('message', PARAM_TEXT);

require_once(__DIR__ . '/../engine/EmotionAnalyzer.php');
$analyzer = new \AugmentedTeacher\Agent05\PersonaSystem\Engine\EmotionAnalyzer();

echo json_encode($analyzer->analyze($message), JSON_UNESCAPED_UNICODE);
```

### 패턴 4: 학습 활동별 페르소나 조정

```php
$activityPersonaMap = [
    'problem_solving' => '분석형',
    'concept_understanding' => '멘토형',
    'error_note' => '격려형',
    'pomodoro' => '코치형'
];

$preferredPersona = $activityPersonaMap[$activityType] ?? '기본형';
```

---

## 📊 지원 데이터 타입

### 감정 타입 (8종)

| 타입 | 한국어 | 사용 예 |
|------|--------|---------|
| `anxiety` | 불안 | "시험 걱정돼요" |
| `frustration` | 좌절 | "왜 안되는 거야!" |
| `confidence` | 자신감 | "이건 잘 할 수 있어요" |
| `curiosity` | 호기심 | "이게 궁금해요" |
| `boredom` | 지루함 | "재미없어요..." |
| `fatigue` | 피로 | "너무 힘들어요" |
| `achievement` | 성취감 | "드디어 풀었다!" |
| `confusion` | 혼란 | "뭐가 뭔지 모르겠어요" |

### 강도 레벨 (3단계)

| 레벨 | 설명 | 감지 기준 |
|------|------|----------|
| `high` | 높음 | 강조어, 반복, 이모티콘 다수 |
| `medium` | 중간 | 일반적 표현 |
| `low` | 낮음 | 완화된 표현 |

### 학습 활동 (8종)

| 타입 | 한국어 |
|------|--------|
| `concept_understanding` | 개념이해 |
| `type_learning` | 유형학습 |
| `problem_solving` | 문제풀이 |
| `error_note` | 오답노트 |
| `qa` | Q&A |
| `review` | 복습 |
| `pomodoro` | 포모도로 |
| `home_check` | 홈체크 |

---

## ⚠️ 주의사항

### 필수 체크리스트

- [ ] Moodle config.php include 확인
- [ ] `require_login()` 호출 확인
- [ ] DB 테이블 6개 생성 완료
- [ ] 네임스페이스 use 문 확인

### 흔한 실수

```php
// ❌ 잘못된 예
$engine = new Agent05PersonaEngine();  // use 문 누락

// ✅ 올바른 예
use AugmentedTeacher\Agent05\PersonaSystem\Engine\Agent05PersonaEngine;
$engine = new Agent05PersonaEngine();
```

```php
// ❌ 잘못된 예 - 로그인 체크 누락
$result = $engine->processAndRespond($USER->id, ...);

// ✅ 올바른 예
require_login();
$result = $engine->processAndRespond($USER->id, ...);
```

---

## 📚 추가 문서

- [01_architecture_overview.md](01_architecture_overview.md) - 전체 아키텍처
- [02_api_reference.md](02_api_reference.md) - 상세 API
- [03_integration_guide.md](03_integration_guide.md) - 통합 가이드

---

## 🆘 문제 해결

### "Class not found" 에러

```php
// 파일 경로 확인
$path = __DIR__ . '/agents/agent05_learning_emotion/persona_system/engine/Agent05PersonaEngine.php';
if (!file_exists($path)) {
    die("File not found: {$path}");
}
require_once($path);
```

### DB 테이블 존재 확인

```php
$repo = new EmotionStateRepository();
$status = $repo->checkTablesExist();
print_r($status);
// ['emotion_log' => true, 'transition_log' => true, ...]
```

### 디버그 로그 추가

```php
// 에러 로그에 기록
error_log("[Agent05 DEBUG] Message: " . $userMessage);
error_log("[Agent05 DEBUG] Emotion: " . json_encode($result['emotion']));
```

---

**Happy Coding! 🎉**
