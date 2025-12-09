# Persona Engine - 공통 페르소나 엔진

21개 에이전트가 공유하는 페르소나 시스템의 공통 엔진입니다.

## 📁 구조

```
persona_engine/
├── core/                          # 핵심 인터페이스
│   ├── AbstractPersonaEngine.php  # 추상 엔진 클래스
│   ├── IConditionEvaluator.php    # 조건 평가 인터페이스
│   ├── IActionExecutor.php        # 액션 실행 인터페이스
│   ├── IRuleParser.php            # 규칙 파서 인터페이스
│   ├── IDataContext.php           # 데이터 컨텍스트 인터페이스
│   └── IResponseGenerator.php     # 응답 생성 인터페이스
│
├── impl/                          # 기본 구현체
│   ├── BaseConditionEvaluator.php # 기본 조건 평가기
│   ├── BaseActionExecutor.php     # 기본 액션 실행기
│   ├── YamlRuleParser.php         # YAML 규칙 파서
│   ├── MoodleDataContext.php      # Moodle 데이터 컨텍스트
│   └── TemplateResponseGenerator.php # 템플릿 응답 생성기
│
├── communication/                 # 에이전트 간 통신
│   ├── PersonaStateSync.php       # 상태 동기화
│   ├── AgentMessageBus.php        # 메시지 버스 (Pub/Sub)
│   └── InterAgentProtocol.php     # 통신 프로토콜 정의
│
├── config/
│   └── persona_engine.config.php  # 전역 설정
│
├── db/
│   └── install.php                # DB 테이블 설치 스크립트
│
└── README.md                      # 이 문서
```

## 🎯 설계 원칙

### Interface Segregation Principle (ISP)
각 기능을 독립적인 인터페이스로 분리하여 에이전트별로 필요한 부분만 구현/오버라이드 가능

### 확장성
- `AbstractPersonaEngine`을 상속하여 에이전트별 엔진 구현
- 인터페이스 구현체를 주입하여 동작 커스터마이징

## 🚀 새 에이전트에 페르소나 시스템 추가하기

### 1단계: 폴더 구조 생성
```
agents/agent{N}_{name}/persona_system/
├── PersonaEngine.php    # AbstractPersonaEngine 상속
├── config.php           # 에이전트 로컬 설정
├── rules/
│   └── rules.yaml       # 페르소나 규칙
└── templates/
    └── {PersonaName}/   # 페르소나별 템플릿
```

### 2단계: 엔진 클래스 생성
```php
<?php
namespace AugmentedTeacher\Agent{N}\PersonaSystem;

require_once(__DIR__ . '/../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
// ... 기타 require

use AugmentedTeacher\PersonaEngine\Core\AbstractPersonaEngine;

class Agent{N}PersonaEngine extends AbstractPersonaEngine {
    protected $agentId = 'agent{N}';
    protected $defaultPersona = 'DefaultPersona';
    
    public function __construct(bool $debugMode = false) {
        parent::__construct(
            new BaseConditionEvaluator($debugMode),
            new BaseActionExecutor($debugMode),
            new YamlRuleParser(),
            new MoodleDataContext($debugMode),
            new TemplateResponseGenerator(__DIR__ . '/templates', $debugMode),
            $debugMode
        );
    }
}
```

### 3단계: 규칙 파일 작성 (rules.yaml)
```yaml
version: "1.0"
agent_id: agent{N}
default_persona: DefaultPersona

personas:
  DefaultPersona:
    name: "기본 페르소나"
    tone: Professional

transition_rules:
  - id: rule_1
    priority: 1
    conditions:
      emotional_state:
        in: [frustrated]
    target_persona: SupportivePersona
```

## 🔌 에이전트 간 통신

### 메시지 전송
```php
$engine->getStateSync()->getMessageBus()->send(
    'emotion_detected',           // 메시지 타입
    'agent07',                    // 수신자 (또는 'broadcast')
    ['emotion' => 'frustrated'],  // 페이로드
    2                             // 우선순위 (1=높음, 5=낮음)
);
```

### 메시지 수신
```php
$messages = $engine->processIncomingMessages(10);
foreach ($messages as $msg) {
    // 메시지 처리
}
```

## 📊 DB 테이블

| 테이블 | 용도 |
|--------|------|
| at_agent_persona_state | 사용자별 현재 페르소나 상태 |
| at_agent_messages | 에이전트 간 비동기 메시지 |
| at_persona_rules | 규칙 캐시 (선택적) |
| at_persona_history | 페르소나 전환 이력 |

### DB 설치
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/db/install.php
```

## ⚙️ 전역 설정

`config/persona_engine.config.php`에서 설정:

```php
PersonaEngineConfig::get('cache.state_ttl');        // 상태 캐시 TTL
PersonaEngineConfig::get('messaging.max_retries');  // 메시지 재시도 횟수

// 에이전트별 오버라이드
PersonaEngineConfig::setAgentOverrides('agent11', [
    'cache.state_ttl' => 120
]);
```

## 🔗 통합 에이전트 목록

| Agent ID | 이름 | 역할 |
|----------|------|------|
| agent01 | 온보딩 | 신규 사용자 안내 |
| agent06 | 퀴즈 | 퀴즈 출제 |
| agent07 | 피드백 | 학습 피드백 |
| agent08 | 동기부여 | 학습 동기 관리 |
| agent09 | 분석 | 학습 데이터 분석 |
| agent10 | 학부모 | 학부모 리포트 |
| agent11 | 문제노트 | 오답 분석 |
| agent20 | 리포트 | 종합 리포트 |

---
*Persona Engine v1.0 - AugmentedTeacher*
