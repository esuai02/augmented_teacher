# Calmness Persona Rule Engine - 아키텍처 설계

> 생성일: 2025-12-03
> 버전: 1.0
> 목적: 학생의 평온도(Calmness) 상태를 실시간으로 분석하고 맞춤형 지원을 제공하는 PHP 엔진

---

## 📋 개요

### Agent08 Calmness 미션
학생의 정서적 안정 상태를 실시간으로 모니터링하고, 불안/스트레스 상황에서
적절한 호흡 운동, 그라운딩 기법, 위기 개입을 통해 평온 상태로 회복을 지원합니다.

### 시스템 요구사항
- PHP 7.1.9+
- MySQL 5.7+
- Moodle 3.7 통합
- YAML 파싱 (Symfony YAML 또는 spyc)

### 핵심 컴포넌트
```
┌─────────────────────────────────────────────────────────────────┐
│                  CalmnessPersonaRuleEngine                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌───────────────┐  ┌─────────────────┐  ┌───────────────────┐  │
│  │CalmnessRule   │→ │CalmnessCondition│→ │CalmnessAction     │  │
│  │Parser         │  │Evaluator        │  │Executor           │  │
│  │(YAML→PHP)     │  │(조건 평가)      │  │(액션 실행)        │  │
│  └───────────────┘  └─────────────────┘  └───────────────────┘  │
│         ↑                   ↑                    ↓               │
│  ┌───────────────┐  ┌─────────────────┐  ┌───────────────────┐  │
│  │RuleCache      │  │CalmnessData     │  │CalmnessResponse   │  │
│  │(규칙 캐시)    │  │Context          │  │Generator          │  │
│  └───────────────┘  └─────────────────┘  └───────────────────┘  │
│                            ↑                     ↓               │
│                     ┌─────────────────┐  ┌───────────────────┐  │
│                     │CalmnessNLU      │  │ExerciseManager    │  │
│                     │Analyzer         │  │(호흡/그라운딩)    │  │
│                     └─────────────────┘  └───────────────────┘  │
│                            ↑                                     │
│                     ┌─────────────────┐                         │
│                     │ MoodleDB        │                         │
│                     │ (데이터 소스)   │                         │
│                     └─────────────────┘                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔧 컴포넌트 상세

### 1. CalmnessRuleParser (규칙 파서)

**역할**: YAML 규칙 파일을 PHP 배열로 변환, 평온도 관련 규칙 특화

```php
<?php
class CalmnessRuleParser extends BaseRuleParser {
    /**
     * Calmness 전용 규칙 섹션 파싱
     * - crisis_intervention_rules (위기 개입)
     * - calmness_level_identification_rules (레벨 식별)
     * - anxiety_trigger_identification_rules (불안 트리거)
     * - recovery_pattern_identification_rules (회복 패턴)
     * - exercise_recommendation_rules (운동 추천)
     */
    public function parseRules(string $yamlContent): array;

    /**
     * 위기 규칙 우선 정렬 (priority 100 최우선)
     */
    public function sortByPriority(array $rules): array;
}
```

### 2. CalmnessConditionEvaluator (조건 평가기)

**역할**: 평온도 관련 조건을 학생 데이터와 비교하여 평가

**Calmness 전용 연산자**:
| 연산자 | 설명 | 예시 |
|--------|------|------|
| `calmness_level_is` | 평온도 레벨 확인 | `calmness_level_is: "C75"` |
| `calmness_score_at_level` | 점수 범위 확인 | `calmness_score_at_level: ["C80", "C90"]` |
| `calmness_trend_is` | 추세 확인 | `calmness_trend_is: "decreasing"` |
| `calmness_improving` | 개선 중 여부 | `calmness_improving: true` |
| `calmness_declining` | 악화 중 여부 | `calmness_declining: true` |
| `calmness_stable_for` | 안정 지속 시간 | `calmness_stable_for: 300` (초) |
| `crisis_indicators_present` | 위기 지표 존재 | `crisis_indicators_present: true` |
| `anxiety_trigger_detected` | 불안 트리거 감지 | `anxiety_trigger_detected: "exam"` |
| `recovery_pattern_matches` | 회복 패턴 매칭 | `recovery_pattern_matches: "fast"` |
| `breathing_exercise_completed` | 호흡 운동 완료 | `breathing_exercise_completed: true` |
| `grounding_exercise_active` | 그라운딩 활성 | `grounding_exercise_active: true` |

**기본 연산자** (상속):
| 연산자 | 설명 |
|--------|------|
| `contains_any` | 배열 중 하나 포함 |
| `contains_all` | 배열 모두 포함 |
| `matches_regex` | 정규식 매칭 |
| `in_range` | 범위 내 값 |

### 3. CalmnessActionExecutor (액션 실행기)

**역할**: 매칭된 규칙의 액션을 실행, 호흡/그라운딩 운동 제어

**Calmness 전용 액션**:
| 액션 | 설명 | 파라미터 |
|------|------|----------|
| `start_breathing_exercise` | 호흡 운동 시작 | `type`, `duration`, `guided` |
| `start_grounding_exercise` | 그라운딩 시작 | `type`, `guided`, `duration` |
| `trigger_crisis_protocol` | 위기 프로토콜 | `severity`, `type` |
| `update_calmness_level` | 레벨 업데이트 | `level`, `confidence` |
| `set_calmness_context` | 컨텍스트 설정 | `key`, `value` |
| `notify_teacher` | 교사 알림 | `urgency`, `message` |
| `record_calmness_event` | 이벤트 기록 | `event_type`, `data` |
| `adjust_support_intensity` | 지원 강도 조절 | `intensity` |
| `provide_encouragement` | 격려 제공 | `type`, `level` |
| `suggest_break` | 휴식 제안 | `duration`, `activity` |

**호흡 운동 타입**:
- `4-7-8`: 4초 들숨, 7초 참기, 8초 날숨 (불안 완화)
- `box`: 박스 호흡 4-4-4-4 (집중력)
- `deep`: 깊은 복식 호흡 (일반 이완)
- `calming`: 느린 호흡 (진정)
- `energizing`: 활력 호흡 (에너지)
- `coherent`: 심장 일관성 호흡 (정서 조절)

**그라운딩 운동 타입**:
- `5-4-3-2-1`: 감각 그라운딩 (5가지 감각)
- `body_scan`: 신체 스캔 (긴장 인식)
- `safe_place`: 안전한 장소 시각화
- `object_focus`: 물건 집중 관찰

### 4. CalmnessDataContext (데이터 컨텍스트)

**역할**: Moodle DB에서 학생의 평온도 관련 데이터 로드

```php
<?php
class CalmnessDataContext extends BaseDataContext {
    /**
     * 평온도 컨텍스트 로드
     * @return array [
     *   'calmness_level' => 'C85',
     *   'calmness_score' => 87,
     *   'calmness_trend' => 'improving',
     *   'anxiety_triggers' => ['exam', 'presentation'],
     *   'recovery_pattern' => 'gradual',
     *   'last_exercise' => [...],
     *   'session_history' => [...],
     *   'crisis_history' => [...]
     * ]
     */
    public function loadByUserId(int $userId): array;

    /**
     * 현재 평온도 레벨 계산
     */
    public function calculateCalmnessLevel(array $indicators): string;

    /**
     * 평온도 추세 분석
     */
    public function analyzeCalmnessTraend(array $history): string;
}
```

### 5. CalmnessNLUAnalyzer (자연어 분석기)

**역할**: 학생 메시지에서 정서적 상태 및 불안 신호 분석

```php
<?php
class CalmnessNLUAnalyzer extends BaseNLUAnalyzer {
    /**
     * 평온도 관련 언어 패턴 분석
     * @return array [
     *   'anxiety_level' => 0.7,
     *   'crisis_indicators' => ['panic', 'overwhelm'],
     *   'emotional_keywords' => ['불안', '무서워'],
     *   'urgency' => 'high',
     *   'physical_symptoms' => ['심장이 빨라', '숨이 안쉬어져'],
     *   'cognitive_patterns' => ['catastrophizing', 'all_or_nothing']
     * ]
     */
    public function analyze(string $message): array;

    /**
     * 위기 지표 감지
     */
    public function detectCrisisIndicators(string $message): array;

    /**
     * 불안 트리거 식별
     */
    public function identifyAnxietyTriggers(string $message, array $context): string;
}
```

### 6. CalmnessResponseGenerator (응답 생성기)

**역할**: 평온도 레벨에 맞는 응답 생성

```php
<?php
class CalmnessResponseGenerator extends BaseResponseGenerator {
    /**
     * 페르소나 기반 응답 생성
     * @param string $personaId 페르소나 ID (C95_P1, C_crisis_P2 등)
     * @param string $templateKey 템플릿 키
     * @param array $variables 치환 변수 (호흡 운동 지시사항 등)
     * @return string 생성된 응답
     */
    public function generate(
        string $personaId,
        string $templateKey,
        array $variables = []
    ): string;

    /**
     * 호흡 운동 가이드 생성
     */
    public function generateBreathingGuide(string $exerciseType): string;

    /**
     * 그라운딩 가이드 생성
     */
    public function generateGroundingGuide(string $exerciseType): string;
}
```

---

## 📦 디렉토리 구조

```
persona_system/
├── engine/
│   ├── README.md                      # 이 문서
│   ├── CalmnessPersonaRuleEngine.php  # 메인 엔진
│   ├── CalmnessRuleParser.php         # YAML 파서
│   ├── CalmnessConditionEvaluator.php # 조건 평가기
│   ├── CalmnessActionExecutor.php     # 액션 실행기
│   ├── CalmnessDataContext.php        # 데이터 컨텍스트
│   ├── CalmnessNLUAnalyzer.php        # NLU 분석기
│   ├── CalmnessResponseGenerator.php  # 응답 생성기
│   └── config/
│       └── engine_config.php          # 엔진 설정
├── api/
│   ├── CalmnessAPI.php                # REST API
│   └── handlers/                      # API 핸들러
├── personas.md                        # 페르소나 정의 문서
├── rules.yaml                         # 페르소나 규칙
└── templates/
    ├── C95/                           # 매우 안정 상태 템플릿
    │   ├── greeting.txt
    │   ├── encouragement.txt
    │   └── maintenance.txt
    ├── C90/                           # 안정 상태 템플릿
    │   ├── greeting.txt
    │   └── support.txt
    ├── C85/                           # 경미한 긴장 템플릿
    │   ├── greeting.txt
    │   ├── calming.txt
    │   └── breathing_intro.txt
    ├── C80/                           # 약간 불안 템플릿
    ├── C75/                           # 불안 상태 템플릿
    ├── C_crisis/                      # 위기 상태 템플릿
    │   ├── immediate_support.txt
    │   ├── grounding_guide.txt
    │   └── crisis_resources.txt
    └── default/
        └── fallback.txt
```

---

## 🔄 실행 흐름

### 1. 초기화
```php
$engine = new CalmnessPersonaRuleEngine();
$engine->loadRules('/path/to/rules.yaml');
```

### 2. 학생 컨텍스트 로드
```php
$context = $engine->loadStudentContext($USER->id);
// $context 예시:
// [
//   'user_id' => 123,
//   'calmness_level' => 'C80',
//   'calmness_score' => 82,
//   'calmness_trend' => 'declining',
//   'user_message' => '시험 생각하면 숨이 막혀요...',
//   'anxiety_level' => 0.65,
//   'crisis_indicators' => [],
//   'physical_symptoms' => ['숨이 막혀'],
//   'anxiety_trigger' => 'exam'
// ]
```

### 3. 규칙 매칭 (우선순위 순)
```php
$matchedRules = $engine->matchRules($context);
// 1. crisis_intervention_rules (priority 100)
// 2. calmness_level_identification_rules (priority 90)
// 3. exercise_recommendation_rules (priority 85)
// ...
```

### 4. 페르소나 식별 및 액션 실행
```php
$result = $engine->identifyPersona($context);
// [
//   'persona_id' => 'C80_P3',
//   'persona_name' => '환경 민감형 (Environmental Sensitive)',
//   'confidence' => 0.88,
//   'matched_rule' => 'CALMNESS_LEVEL_C80_003',
//   'tone' => 'Warm',
//   'pace' => 'Slow',
//   'intervention' => 'EmotionalSupport',
//   'recommended_exercise' => [
//     'type' => 'breathing',
//     'exercise' => '4-7-8',
//     'guided' => true
//   ]
// ]
```

### 5. 응답 생성
```php
$response = $engine->generateResponse($result, 'calming_with_breathing');
// "지금 시험 생각에 숨이 막히는 느낌이 드시는군요.
//  잠깐 함께 호흡을 해볼까요?
//  천천히 4초 동안 숨을 들이쉬고...
//  7초 동안 편안하게 멈추고...
//  8초 동안 천천히 내쉬어 보세요."
```

---

## 📊 평온도 레벨 시스템

### 레벨 정의
| 레벨 | 점수 범위 | 상태 | 개입 유형 |
|------|-----------|------|-----------|
| C95 | 95-100 | 매우 안정 | 유지/강화 |
| C90 | 90-94 | 안정 | 경미한 지원 |
| C85 | 85-89 | 경미한 긴장 | 예방적 지원 |
| C80 | 80-84 | 약간 불안 | 적극적 지원 |
| C75 | 75-79 | 불안 | 집중 지원 |
| C_crisis | <75 | 위기 | 긴급 개입 |

### 평온도 계산 요소
1. **자가 보고**: 학생이 직접 보고한 불안 수준 (40%)
2. **언어 분석**: 메시지의 불안 지표 (30%)
3. **행동 패턴**: 반응 시간, 입력 패턴 (15%)
4. **이력 데이터**: 과거 평온도 추세 (15%)

---

## 🚨 위기 개입 프로토콜

### 심각도 레벨
| 심각도 | 조건 | 즉시 조치 |
|--------|------|-----------|
| critical | 자해/자살 언급 | 119/1393 안내, 교사 즉시 알림 |
| high | 공황 상태 징후 | 그라운딩 즉시 시작, 교사 알림 |
| moderate | 심한 불안 | 호흡 운동 안내, 모니터링 강화 |
| low | 경미한 불안 | 지지적 대화, 자가 관리 안내 |

### 한국 위기 자원
- **119**: 응급 서비스
- **1393**: 자살예방상담전화 (24시간)
- **1577-0199**: 정신건강위기상담전화
- **1388**: 청소년전화

---

## 🗄️ Moodle DB 연동

### 필요 테이블
| 테이블 | 용도 |
|--------|------|
| `mdl_user` | 사용자 기본 정보 |
| `at_agent_calmness_sessions` | 평온도 세션 데이터 |
| `at_agent_calmness_exercises` | 운동 이력 |
| `at_agent_calmness_events` | 이벤트 로그 |
| `at_agent_persona_state` | 페르소나 상태 |

### 커스텀 테이블 스키마

```sql
-- 평온도 세션 테이블
CREATE TABLE at_agent_calmness_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_key VARCHAR(64) NOT NULL UNIQUE,
    calmness_level VARCHAR(10) NOT NULL DEFAULT 'C85',
    calmness_score DECIMAL(5,2),
    calmness_trend VARCHAR(20),
    current_persona VARCHAR(20),
    anxiety_triggers JSON,
    recovery_pattern VARCHAR(30),
    active_exercise JSON,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_session (user_id, session_key),
    INDEX idx_calmness (calmness_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 운동 이력 테이블
CREATE TABLE at_agent_calmness_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT NOT NULL,
    exercise_type ENUM('breathing', 'grounding') NOT NULL,
    exercise_name VARCHAR(30) NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    duration_seconds INT,
    completion_rate DECIMAL(5,2),
    effectiveness_rating TINYINT,
    pre_calmness_score DECIMAL(5,2),
    post_calmness_score DECIMAL(5,2),

    INDEX idx_user_exercises (user_id, exercise_type),
    INDEX idx_session (session_id),
    FOREIGN KEY (session_id) REFERENCES at_agent_calmness_sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 위기 이벤트 테이블
CREATE TABLE at_agent_calmness_crisis_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT,
    severity ENUM('low', 'moderate', 'high', 'critical') NOT NULL,
    crisis_type VARCHAR(30),
    trigger_message TEXT,
    intervention_taken TEXT,
    teacher_notified BOOLEAN DEFAULT FALSE,
    resolution_status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,

    INDEX idx_user_crisis (user_id, severity),
    INDEX idx_severity (severity, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ⚡ 성능 최적화

### 규칙 캐싱
- YAML 파싱 결과를 메모리/파일 캐시
- 규칙 변경 시 캐시 무효화
- TTL: 3600초 (1시간)

### 조건 평가 최적화
- 단락 평가 (short-circuit evaluation)
- 위기 규칙 우선 평가
- OR 조건: 첫 번째 true에서 중단

### 실시간 응답
- 위기 감지: <100ms 목표
- 일반 페르소나 식별: <200ms 목표
- 응답 생성: <50ms 목표

---

## 🔗 온톨로지 통합

### 공통 온톨로지 엔진 연동
```php
// ontology_engineering/persona_engine/core/ 인터페이스 구현
use AugmentedTeacher\PersonaEngine\Core\IRuleParser;
use AugmentedTeacher\PersonaEngine\Core\IConditionEvaluator;
use AugmentedTeacher\PersonaEngine\Core\IActionExecutor;
use AugmentedTeacher\PersonaEngine\Core\IResponseGenerator;
```

### 온톨로지 참조
- [ontology_engineering/persona_engine/core/](../../../../ontology_engineering/persona_engine/core/) - 공통 인터페이스
- [ontology_engineering/persona_engine/impl/](../../../../ontology_engineering/persona_engine/impl/) - 기본 구현체

---

## 📝 참고 문서

- [personas.md](../personas.md) - 페르소나 상세 정의
- [rules.yaml](../rules.yaml) - 식별 규칙 (58개)
- [Agent08 정보](../../agentinfo08.md) - 에이전트 미션 정보
- [공통 엔진 인터페이스](../../../../ontology_engineering/persona_engine/core/) - 인터페이스 명세
