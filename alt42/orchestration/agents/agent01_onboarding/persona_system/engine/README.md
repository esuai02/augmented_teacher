# Persona Rule Engine - 아키텍처 설계

> 생성일: 2025-12-02
> 버전: 1.0
> 목적: 페르소나 식별 규칙을 실시간으로 실행하는 경량 PHP 엔진

---

## 📋 개요

### 시스템 요구사항
- PHP 7.1.9+
- MySQL 5.7+
- Moodle 3.7 통합
- YAML 파싱 (Symfony YAML 또는 spyc)

### 핵심 컴포넌트
```
┌─────────────────────────────────────────────────────────────┐
│                    PersonaRuleEngine                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │ RuleParser  │→ │ConditionEval│→ │ActionExecutor│        │
│  │ (YAML→PHP)  │  │ (조건 평가)  │  │ (액션 실행)  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│         ↑               ↑               ↓                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │ RuleCache   │  │ DataContext │  │ ResponseGen │         │
│  │ (규칙 캐시) │  │ (학생 데이터)│  │ (응답 생성) │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│                          ↑                                  │
│                   ┌─────────────┐                          │
│                   │ MoodleDB    │                          │
│                   │ (데이터 소스)│                          │
│                   └─────────────┘                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 컴포넌트 상세

### 1. RuleParser (규칙 파서)

**역할**: YAML 규칙 파일을 PHP 배열로 변환

```php
<?php
// engine/RuleParser.php 인터페이스

interface RuleParserInterface {
    /**
     * YAML 규칙 파일을 파싱
     * @param string $filePath rules.yaml 경로
     * @return array 파싱된 규칙 배열
     */
    public function parseRules(string $filePath): array;

    /**
     * 규칙을 우선순위로 정렬
     * @param array $rules 규칙 배열
     * @return array 정렬된 규칙
     */
    public function sortByPriority(array $rules): array;
}
```

### 2. ConditionEvaluator (조건 평가기)

**역할**: 규칙의 조건을 학생 데이터와 비교하여 평가

```php
<?php
// engine/ConditionEvaluator.php 인터페이스

interface ConditionEvaluatorInterface {
    /**
     * 조건 평가
     * @param array $condition 규칙의 조건
     * @param array $context 학생 컨텍스트 데이터
     * @return bool 조건 충족 여부
     */
    public function evaluate(array $condition, array $context): bool;

    /**
     * OR 조건 평가
     */
    public function evaluateOr(array $conditions, array $context): bool;

    /**
     * AND 조건 평가
     */
    public function evaluateAnd(array $conditions, array $context): bool;
}
```

**지원 연산자**:
| 연산자 | 설명 | 예시 |
|--------|------|------|
| `==` | 동등 비교 | `field == "value"` |
| `!=` | 불일치 | `field != "value"` |
| `>`, `>=` | 초과/이상 | `score >= 80` |
| `<`, `<=` | 미만/이하 | `response_length <= 5` |
| `contains` | 부분 일치 | `message contains "몰라요"` |
| `contains_any` | 배열 중 하나 포함 | `contains_any ["몰라요", "그냥"]` |
| `in` | 배열 내 포함 | `status in ["active", "new"]` |
| `regex` | 정규식 매칭 | `regex "/^수학.*못해/"` |

### 3. ActionExecutor (액션 실행기)

**역할**: 매칭된 규칙의 액션을 실행

```php
<?php
// engine/ActionExecutor.php 인터페이스

interface ActionExecutorInterface {
    /**
     * 액션 실행
     * @param array $actions 실행할 액션 목록
     * @param array $context 현재 컨텍스트
     * @return array 실행 결과
     */
    public function execute(array $actions, array &$context): array;

    /**
     * 커스텀 액션 핸들러 등록
     */
    public function registerHandler(string $actionName, callable $handler): void;
}
```

**기본 액션 타입**:
| 액션 | 설명 | 파라미터 |
|------|------|----------|
| `identify_persona` | 페르소나 식별 | `persona_id` |
| `set_tone` | 톤 설정 | `tone_name` |
| `set_pace` | 페이스 설정 | `pace_value` |
| `prioritize_intervention` | 개입 유형 우선순위 | `intervention_type` |
| `set_information_depth` | 정보 깊이 설정 | `depth_level` |
| `add_flag` | 플래그 추가 | `flag_name` |

### 4. DataContext (데이터 컨텍스트)

**역할**: Moodle DB에서 학생 데이터를 가져와 컨텍스트 구성

```php
<?php
// engine/DataContext.php 인터페이스

interface DataContextInterface {
    /**
     * 학생 ID로 컨텍스트 로드
     * @param int $userId Moodle 사용자 ID
     * @return array 학생 컨텍스트
     */
    public function loadByUserId(int $userId): array;

    /**
     * 현재 상황 코드 결정
     * @param array $sessionData 세션 데이터
     * @return string 상황 코드 (S0-S5, C, Q, E)
     */
    public function determineSituation(array $sessionData): string;

    /**
     * 실시간 메시지 분석
     * @param string $message 사용자 메시지
     * @return array 분석 결과 (길이, 감정, 키워드 등)
     */
    public function analyzeMessage(string $message): array;
}
```

### 5. ResponseGenerator (응답 생성기)

**역할**: 식별된 페르소나에 맞는 응답 생성

```php
<?php
// engine/ResponseGenerator.php 인터페이스

interface ResponseGeneratorInterface {
    /**
     * 페르소나 기반 응답 생성
     * @param string $personaId 페르소나 ID
     * @param string $templateKey 템플릿 키
     * @param array $variables 치환 변수
     * @return string 생성된 응답
     */
    public function generate(string $personaId, string $templateKey, array $variables): string;
}
```

---

## 📦 디렉토리 구조

```
persona_system/
├── engine/
│   ├── README.md              # 이 문서
│   ├── PersonaRuleEngine.php  # 메인 엔진 클래스
│   ├── RuleParser.php         # YAML 파서
│   ├── ConditionEvaluator.php # 조건 평가기
│   ├── ActionExecutor.php     # 액션 실행기
│   ├── DataContext.php        # 데이터 컨텍스트
│   ├── ResponseGenerator.php  # 응답 생성기
│   ├── RuleCache.php          # 규칙 캐시
│   └── config.php             # 엔진 설정
├── ontology.jsonld            # 페르소나 온톨로지
├── rules.yaml                 # 페르소나 규칙
├── personas.md                # 페르소나 정의 문서
└── templates/                 # 응답 템플릿 (6단계에서 생성)
```

---

## 🔄 실행 흐름

### 1. 초기화
```php
$engine = new PersonaRuleEngine();
$engine->loadRules('/path/to/rules.yaml');
```

### 2. 학생 컨텍스트 로드
```php
$context = $engine->loadStudentContext($USER->id);
// $context 예시:
// [
//   'user_id' => 123,
//   'situation' => 'S1',
//   'user_message' => '수학 진짜 못해요...',
//   'response_length' => 12,
//   'emotional_keywords' => ['못해요'],
//   'session_history' => [...],
//   'moodle_data' => [...]
// ]
```

### 3. 규칙 매칭
```php
$matchedRules = $engine->matchRules($context);
// 우선순위별로 정렬된 매칭 규칙 반환
```

### 4. 페르소나 식별
```php
$result = $engine->identifyPersona($context);
// [
//   'persona_id' => 'S1_P2',
//   'persona_name' => '과거 트라우마형 긴장자',
//   'confidence' => 0.90,
//   'matched_rule' => 'PI_S1_002_trauma_fearful',
//   'tone' => 'Warm',
//   'pace' => 'slow',
//   'intervention' => 'EmotionalSupport'
// ]
```

### 5. 응답 생성
```php
$response = $engine->generateResponse($result, 'welcome_message');
```

---

## 🗄️ Moodle DB 연동

### 필요 테이블 접근

| 테이블 | 용도 |
|--------|------|
| `mdl_user` | 사용자 기본 정보 |
| `mdl_user_info_data` | 커스텀 필드 (역할 등) |
| `mdl_grade_grades` | 성적 데이터 |
| `mdl_logstore_standard_log` | 활동 로그 |
| `augmented_teacher_sessions` | AI 세션 데이터 (커스텀) |
| `augmented_teacher_personas` | 페르소나 이력 (커스텀) |

### 커스텀 테이블 스키마

```sql
-- 학생 페르소나 이력 테이블
CREATE TABLE augmented_teacher_personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agent_id VARCHAR(20) NOT NULL DEFAULT 'agent01',
    persona_id VARCHAR(20) NOT NULL,
    situation VARCHAR(5) NOT NULL,
    confidence DECIMAL(3,2) NOT NULL,
    matched_rule VARCHAR(50),
    context_snapshot TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_agent (user_id, agent_id),
    INDEX idx_persona (persona_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 세션 컨텍스트 테이블
CREATE TABLE augmented_teacher_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agent_id VARCHAR(20) NOT NULL,
    session_key VARCHAR(64) NOT NULL UNIQUE,
    current_situation VARCHAR(5),
    current_persona VARCHAR(20),
    context_data JSON,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_session (user_id, session_key),
    INDEX idx_agent (agent_id)
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
- OR 조건: 첫 번째 true에서 중단
- AND 조건: 첫 번째 false에서 중단

### DB 쿼리 최적화
- 학생 데이터 프리페치
- 세션 단위 캐싱
- 인덱스 최적화

---

## 🔜 다음 단계

1. **4단계**: Moodle DB 데이터 소스 매핑 상세 구현
2. **5단계**: 페르소나 전환 관계 모델링
3. **6단계**: 동적 응답 템플릿 시스템
4. **7단계**: NLU 기반 조건 매칭 개선

---

## 📝 참고 문서

- [ontology.jsonld](./ontology.jsonld) - 페르소나 온톨로지
- [rules.yaml](./rules.yaml) - 식별 규칙
- [personas.md](./personas.md) - 페르소나 상세 정의
