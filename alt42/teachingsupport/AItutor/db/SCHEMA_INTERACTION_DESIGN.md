# AI Tutor 상호작용 시스템 DB 스키마 설계

## 개요

| 항목 | 내용 |
|------|------|
| **접두사** | `mdl_alt42i_` (i = interaction) |
| **MySQL 버전** | 5.7 호환 |
| **문자셋** | utf8mb4_unicode_ci |
| **총 테이블** | 15개 |

## URL 파라미터 기반 설계

```
URL: /AItutor/ui/learning_interface.php
     ?studentid=1858
     &contentsid=jnrsorksqcrark15652
     &contentstype=topic
```

모든 테이블에 `student_id`, `contents_id`, `contents_type` 컬럼이 포함되어 URL 파라미터 기반 조회가 가능합니다.

---

## 테이블 구조

### 1. 핵심 테이블

#### `mdl_alt42i_sessions` - 학습 세션
```
┌──────────────────────────────────────────────────────────────┐
│ session_id | student_id | contents_id | contents_type        │
│ whiteboard_id | persona_id | current_step | emotion_type    │
│ session_status | started_at | ended_at | duration_seconds   │
└──────────────────────────────────────────────────────────────┘
```
- **용도**: 학습 세션의 메인 관리
- **핵심 필드**: `session_id` (고유 키), URL 파라미터 3종

#### `mdl_alt42i_interaction_logs` - 상호작용 로그
```
┌──────────────────────────────────────────────────────────────┐
│ session_id | student_id | contents_id | contents_type        │
│ event_type | event_data | previous_state | current_state    │
│ triggered_rules | triggered_interventions | timestamp_ms    │
└──────────────────────────────────────────────────────────────┘
```
- **용도**: 모든 이벤트의 시계열 로깅
- **이벤트 타입**: step_change, emotion_change, gesture_input, persona_change 등

---

### 2. 상태 추적 테이블

| 테이블 | 용도 |
|--------|------|
| `mdl_alt42i_step_progress` | 단계별 진행 상태 (1~5단계) |
| `mdl_alt42i_emotion_history` | 감정 변화 이력 |
| `mdl_alt42i_gestures` | 펜 제스처 인식 기록 |
| `mdl_alt42i_persona_history` | 페르소나 변화 기록 |
| `mdl_alt42i_feedbacks` | AI 피드백 표시 이력 |
| `mdl_alt42i_memory_activities` | 장기기억 활동 (5단계) |

---

### 3. 온톨로지 확장 테이블 ⭐

#### `mdl_alt42i_ontology_nodes` - 온톨로지 노드
```sql
node_id         VARCHAR(100)    -- 고유 ID
node_type       VARCHAR(50)     -- concept, relation, property, instance
node_label      VARCHAR(200)    -- 라벨/이름
parent_node_id  VARCHAR(100)    -- 계층 구조
namespace       VARCHAR(100)    -- 네임스페이스 분리
layer           ENUM(...)       -- agent_core, task_core, task_module, session, dynamic
properties      LONGTEXT        -- JSON: 속성들
relations       LONGTEXT        -- JSON: 관계 정의
```

**Layer 구조**:
```
┌─────────────────────────────────────────────┐
│ agent_core    │ OIW 모델 (Will→Execution)  │
├───────────────┼─────────────────────────────┤
│ task_core     │ 감정, 단계, 페르소나 정의   │
├───────────────┼─────────────────────────────┤
│ task_module   │ 컨텐츠별 수학 개념          │
├───────────────┼─────────────────────────────┤
│ session       │ 세션별 동적 노드            │
├───────────────┼─────────────────────────────┤
│ dynamic       │ AI 생성/추론된 노드         │
└───────────────┴─────────────────────────────┘
```

#### `mdl_alt42i_ontology_relations` - 온톨로지 관계
```sql
source_node_id  VARCHAR(100)    -- 소스 노드
target_node_id  VARCHAR(100)    -- 타겟 노드
relation_type   VARCHAR(50)     -- is_a, has_part, requires, leads_to
weight          DECIMAL(5,4)    -- 관계 강도 (0-1)
direction       ENUM(...)       -- unidirectional, bidirectional
```

---

### 4. 룰 확장 테이블 ⭐

#### `mdl_alt42i_dynamic_rules` - 동적 룰
```sql
rule_id           VARCHAR(100)    -- 룰 고유 ID
rule_name         VARCHAR(200)    -- 룰 이름
rule_category     VARCHAR(50)     -- U0, U1, U2, U3, U4
priority          INT(5)          -- 우선순위
conditions        LONGTEXT        -- JSON: 조건들
actions           LONGTEXT        -- JSON: 액션들
persona_ids       TEXT            -- JSON: 적용 페르소나
ontology_refs     TEXT            -- JSON: 참조 온톨로지
effectiveness_score DECIMAL(5,4)  -- 효과성 (학습됨)
source            ENUM(...)       -- system, ai_generated, learned
```

**룰 카테고리**:
| 카테고리 | 용도 |
|----------|------|
| U0 | 시스템 제어 룰 |
| U1 | 상태 인식 룰 |
| U2 | 분석 판단 룰 |
| U3 | 개입 결정 룰 |
| U4 | 반영/학습 룰 |

#### `mdl_alt42i_rule_executions` - 룰 실행 로그
```sql
rule_id           VARCHAR(100)    -- 실행된 룰
trigger_event     VARCHAR(100)    -- 트리거 이벤트
input_context     LONGTEXT        -- 입력 컨텍스트
condition_results TEXT            -- 조건 평가 결과
execution_result  ENUM(...)       -- success, partial, failed, skipped
effect_on_student TEXT            -- 학생에게 미친 영향
```

---

### 5. 컨텍스트 & 성과 테이블

#### `mdl_alt42i_context_states` - 컨텍스트 스냅샷
- 현재 학습 맥락의 전체 상태 저장
- `cognitive_load`, `engagement_level`, `understanding_level` 추정치

#### `mdl_alt42i_learning_outcomes` - 학습 성과
- 세션별 집계 데이터
- 제스처, 감정, 페르소나, 피드백 등 통계

#### `mdl_alt42i_whiteboard_actions` - 화이트보드 상호작용
- 필기 패턴 분석용 데이터

---

## ERD 개요

```
┌─────────────────┐
│    sessions     │ ─────┬─────────────────────────────────────────┐
└────────┬────────┘      │                                         │
         │               │                                         │
         ▼               ▼                                         ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌──────────────────┐
│interaction_logs │ │  step_progress  │ │emotion_history  │ │persona_history   │
└─────────────────┘ └─────────────────┘ └─────────────────┘ └──────────────────┘
         │               │                    │                    │
         ▼               ▼                    ▼                    ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌──────────────────┐
│    gestures     │ │   feedbacks     │ │memory_activities│ │whiteboard_actions│
└─────────────────┘ └─────────────────┘ └─────────────────┘ └──────────────────┘
         │
         ├───────────────────────────────────────────────────┐
         ▼                                                   ▼
┌─────────────────────────────┐               ┌─────────────────────────────┐
│      ontology_nodes         │◄──────────────│     ontology_relations      │
│  (동적 온톨로지 확장)         │               │     (노드 간 관계)           │
└─────────────────────────────┘               └─────────────────────────────┘
         ▲
         │
┌─────────────────────────────┐               ┌─────────────────────────────┐
│      dynamic_rules          │──────────────►│     rule_executions         │
│  (동적 룰 확장)              │               │     (룰 실행 로그)           │
└─────────────────────────────┘               └─────────────────────────────┘
         │
         ▼
┌─────────────────────────────┐               ┌─────────────────────────────┐
│     context_states          │──────────────►│    learning_outcomes        │
│  (컨텍스트 스냅샷)           │               │    (학습 성과 요약)          │
└─────────────────────────────┘               └─────────────────────────────┘
```

---

## 사용 예시

### 세션 시작
```php
$session = [
    'session_id' => 'SESSION_' . time() . '_' . rand(1000, 9999),
    'student_id' => $studentid,
    'contents_id' => $contentsid,
    'contents_type' => $contentstype,
    'whiteboard_id' => $wboardid,
    'current_step' => 1,
    'emotion_type' => 'neutral',
    'session_status' => 'active'
];
$DB->insert_record('alt42i_sessions', (object)$session);
```

### 상호작용 로그 기록
```php
$log = [
    'session_id' => $sessionId,
    'student_id' => $studentid,
    'contents_id' => $contentsid,
    'contents_type' => $contentstype,
    'event_type' => 'gesture_input',
    'event_data' => json_encode([
        'gesture_type' => 'check',
        'gesture_symbol' => '✓',
        'action' => 'step_advance'
    ]),
    'timestamp_ms' => round(microtime(true) * 1000)
];
$DB->insert_record('alt42i_interaction_logs', (object)$log);
```

### 동적 온톨로지 노드 추가
```php
$node = [
    'node_id' => 'CONCEPT_' . $contentsid . '_' . uniqid(),
    'session_id' => $sessionId,
    'student_id' => $studentid,
    'contents_id' => $contentsid,
    'contents_type' => $contentstype,
    'node_type' => 'concept',
    'node_label' => '일차방정식 이항',
    'namespace' => 'math',
    'layer' => 'session',
    'properties' => json_encode(['difficulty' => 'medium']),
    'source' => 'ai_generated'
];
$DB->insert_record('alt42i_ontology_nodes', (object)$node);
```

### 동적 룰 추가/학습
```php
$rule = [
    'rule_id' => 'RULE_' . uniqid(),
    'student_id' => $studentid,
    'rule_name' => '막힘 감지 시 힌트 제공',
    'rule_category' => 'U3',
    'priority' => 80,
    'conditions' => json_encode([
        ['field' => 'emotion_type', 'operator' => 'equals', 'value' => 'stuck'],
        ['field' => 'pause_duration', 'operator' => '>', 'value' => 10]
    ]),
    'actions' => json_encode([
        ['type' => 'show_hint', 'level' => 1],
        ['type' => 'show_feedback', 'message' => '힌트를 줄까?']
    ]),
    'source' => 'learned',
    'effectiveness_score' => 0.85
];
$DB->insert_record('alt42i_dynamic_rules', (object)$rule);
```

---

## 온톨로지 확장 전략

### 3-Layer 아키텍처

```
┌─────────────────────────────────────────────────────────────┐
│                    Agent Core Ontology                       │
│  - OIW 모델 (Will → Intent → Context → ... → Execution)    │
│  - 시스템 수준 개념들                                        │
│  - 변경 불가 (readonly)                                      │
├─────────────────────────────────────────────────────────────┤
│                    Task Core Ontology                        │
│  - 감정, 단계, 페르소나, 제스처 개념                          │
│  - 42개 개입 활동                                           │
│  - 관리자만 수정 가능                                        │
├─────────────────────────────────────────────────────────────┤
│                   Task Module Ontology                       │
│  - 수학 단원별 개념 (방정식, 함수 등)                         │
│  - 문제 유형별 노드                                          │
│  - AI가 확장 가능                                           │
├─────────────────────────────────────────────────────────────┤
│                    Session Ontology                          │
│  - 학생별, 세션별 동적 노드                                   │
│  - 학습 과정에서 발견된 개념                                  │
│  - 자동 생성 및 연결                                         │
└─────────────────────────────────────────────────────────────┘
```

### 룰 학습 메커니즘

1. **실행 로그 수집**: `rule_executions` 테이블에 모든 룰 실행 기록
2. **효과성 측정**: 룰 실행 후 학생 상태 변화 추적
3. **점수 업데이트**: `effectiveness_score` 자동 조정
4. **신규 룰 생성**: 패턴 분석을 통한 AI 룰 생성 (`source = 'learned'`)

---

## 설치

```sql
-- 스키마 실행
SOURCE /path/to/schema_interaction.sql;

-- 확인
SHOW TABLES LIKE 'mdl_alt42i_%';
```

## 버전

| 버전 | 날짜 | 변경사항 |
|------|------|----------|
| 1.0 | 2025-11-26 | 초기 스키마 설계 |

