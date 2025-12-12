# Agent11 Problem Notes Persona System

문제노트 에이전트(Agent11)의 페르소나 시스템입니다.

## 📁 폴더 구조

```
persona_system/
├── PersonaEngine.php      # 메인 엔진 (AbstractPersonaEngine 상속)
├── config.php             # 에이전트 로컬 설정
├── test.php               # 테스트 스크립트
├── README.md              # 이 문서
├── api/
│   └── persona.php        # REST API 엔드포인트
├── rules/
│   └── rules.yaml         # 페르소나 전환 규칙
└── templates/
    ├── default/           # 기본 템플릿
    ├── AnalyticalHelper/  # 분석적 조력자 템플릿
    ├── EncouragingCoach/  # 격려형 코치 템플릿
    ├── PatientGuide/      # 차분한 안내자 템플릿
    └── PracticeLeader/    # 연습 리더 템플릿
```

## 🎭 페르소나 목록

| ID | 이름 | 톤 | 용도 |
|----|------|-----|------|
| AnalyticalHelper | 분석적 조력자 | Professional | 오답 원인 분석 |
| EncouragingCoach | 격려형 코치 | Encouraging | 좌절한 학생 격려 |
| PatientGuide | 차분한 안내자 | Supportive | 단계별 설명 |
| PracticeLeader | 연습 리더 | Directive | 반복 연습 유도 |

## 🚀 사용법

### PHP에서 사용

```php
require_once(__DIR__ . '/PersonaEngine.php');
use AugmentedTeacher\Agent11\PersonaSystem\Agent11PersonaEngine;

// 엔진 초기화
$engine = new Agent11PersonaEngine(false); // 디버그 모드 끄기

// 페르소나 결정
$persona = $engine->determinePersona($userId, [
    'error_type' => 'concept_confusion',
    'emotional_state' => 'frustrated'
]);

// 문제노트 분석 응답 생성
$response = $engine->generateNoteAnalysisResponse($userId, [
    'problem_id' => 123,
    'error_type' => 'calculation_mistake',
    'student_answer' => '25',
    'correct_answer' => '35'
]);
```

### API 사용

```bash
# 현재 페르소나 조회
GET /api/persona.php?action=current&user_id=123

# 페르소나 결정
POST /api/persona.php?action=determine
Body: { "error_type": "concept_confusion", "emotional_state": "frustrated" }

# 문제노트 분석
POST /api/persona.php?action=analyze
Body: { "problem_id": 123, "error_type": "calculation_mistake" }

# 모든 페르소나 목록
GET /api/persona.php?action=list
```

## ⚙️ 설정

`config.php`에서 다음을 설정할 수 있습니다:

- `personas.default`: 기본 페르소나 (AnalyticalHelper)
- `personas.transition.min_interval`: 최소 전환 간격 (300초)
- `response.max_length`: 최대 응답 길이 (500자)
- `analysis.error_classification`: 오류 분류 목록

## 🔗 의존성

- 공통 엔진: `../../ontology_engineering/persona_engine/`
- DB 테이블: `at_agent_persona_state`, `at_agent_messages`

## 🧪 테스트

브라우저에서 접속:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test.php
```

## 📊 관련 DB 테이블

| 테이블명 | 용도 |
|---------|------|
| at_agent_persona_state | 사용자별 페르소나 상태 |
| at_agent_messages | 에이전트 간 메시지 |
| at_persona_rules | 페르소나 규칙 캐시 |
| at_persona_history | 페르소나 변경 이력 |

---
*Agent11 문제노트 페르소나 시스템 v1.0*
