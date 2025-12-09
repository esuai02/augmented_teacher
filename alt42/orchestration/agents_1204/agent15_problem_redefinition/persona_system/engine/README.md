# Persona Rule Engine - Agent15 Problem Redefinition

> 생성일: 2025-12-02
> 버전: 1.0
> 목적: 문제 재정의 상황별 학생 페르소나 식별 및 맞춤형 개선방안 생성

---

## 개요

### 시스템 요구사항
- PHP 7.1.9+
- MySQL 5.7+
- Moodle 3.7 통합
- YAML 파싱 (Symfony YAML 또는 spyc)

### Agent15 도메인: 문제 재정의
- **핵심 프레임**: 증상 → 원인 가설 → 검증 계획 → 조치안
- **10가지 트리거 시나리오**: S1~S10
- **다층 원인 분석**: 인지적/행동적/동기적/환경적

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
│                   │  NLUAnalyzer│                          │
│                   │ (자연어 분석)│                          │
│                   └─────────────┘                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 트리거 시나리오 (S1-S10)

| 코드 | 상황명 | 설명 |
|------|--------|------|
| S1 | 학습 성과 하락 탐지 | 최근 2주간 점수, 목표 달성률 동반 하락 |
| S2 | 학습이탈 경고 감지 | 24시간 내 이탈 이벤트 ≥ 2회 |
| S3 | 동일 오답 반복 | 오답 유형이 3회 이상 반복 |
| S4 | 루틴 불안정 | 포모도로 완료율 < 50% |
| S5 | 시간관리 실패 | 계획 대비 실제 수행시간 차이 과도 |
| S6 | 정서/동기 저하 | 감정로그에서 의욕저하 다수 탐지 |
| S7 | 개념 이해 부진 | 특정 단원 개념테스트 점수 < 60% |
| S8 | 교사 피드백 경고 | 교사가 집중력 저하/기본기 부족 기록 |
| S9 | 전략 불일치 | 설정된 지도모드와 실제 학습행동 불일치 |
| S10 | 회복 실패 | 휴식 후 집중도 회복 < 50% 지속 |

---

## 페르소나 카테고리

### R-Series: 문제 인식 유형 (Recognition)
- R1_P1 ~ R1_P6: 증상 인식 방식에 따른 분류

### A-Series: 원인 귀인 유형 (Attribution)
- A2_P1 ~ A2_P6: 원인 귀인 스타일에 따른 분류

### V-Series: 검증 태도 유형 (Validation)
- V3_P1 ~ V3_P6: 검증 계획 수용 태도에 따른 분류

### S-Series: 솔루션 수용 유형 (Solution)
- S4_P1 ~ S4_P6: 조치안 수용 태도에 따른 분류

### E-Series: 정서적 UX 상황
- E_P1 ~ E_P6: 정서적 지원이 필요한 상황

---

## 디렉토리 구조

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
│   ├── NLUAnalyzer.php        # NLU 분석기
│   ├── PersonaTransitionManager.php # 전환 관리자
│   ├── db_setup.php           # DB 설정
│   └── config/
│       └── ai_config.php      # AI 설정
├── personas.md                # 페르소나 정의 문서
├── contextlist.md             # 컨텍스트 목록
├── rules.yaml                 # 페르소나 규칙
├── ontology.jsonld            # 페르소나 온톨로지
├── templates/                 # 응답 템플릿
│   ├── default/
│   ├── S1/
│   ├── S2/
│   └── E/
└── api/
    └── chat.php               # API 엔드포인트
```

---

## 실행 흐름

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
//   'trigger_scenario' => 'S1',
//   'user_message' => '최근에 성적이 많이 떨어졌어요...',
//   'agent_data' => [...],
//   'math_unit_vulnerability' => [...],
//   'student_level' => 'mid'
// ]
```

### 3. 문제 재정의 프로세스
```php
$result = $engine->process($userId, $message, $sessionData);
// [
//   'persona_id' => 'R1_P2',
//   'persona_name' => '회피형 문제 인식자',
//   'trigger_scenario' => 'S1',
//   'redefined_problem' => '표면적 성적 하락의 근본 원인은...',
//   'action_plan' => [...],
//   'priority_items' => [1, 2, 3]
// ]
```

---

## DB 연동

### 필요 테이블
| 테이블 | 용도 |
|--------|------|
| `mdl_user` | 사용자 기본 정보 |
| `augmented_teacher_personas` | 페르소나 이력 |
| `augmented_teacher_sessions` | 세션 데이터 |
| `at_agent_persona_state` | 에이전트 간 페르소나 상태 공유 |
| `at_agent_messages` | 에이전트 간 메시지 교환 |

---

## 참고 문서

- [personas.md](../personas.md) - 페르소나 상세 정의
- [rules.yaml](../rules.yaml) - 식별 규칙
- [ontology.jsonld](../ontology.jsonld) - 페르소나 온톨로지
- [../rules/mission.md](../../rules/mission.md) - Agent15 미션
