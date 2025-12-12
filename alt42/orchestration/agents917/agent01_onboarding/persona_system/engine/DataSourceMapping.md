# Data Source Mapping - Moodle DB 연동 명세

> 생성일: 2025-12-02
> 버전: 1.0
> 목적: 페르소나 시스템과 Moodle DB 간의 데이터 연동 정의

---

## 📋 개요

페르소나 시스템은 Moodle 데이터베이스와 연동하여 학생 정보를 수집하고,
식별된 페르소나 및 세션 데이터를 저장합니다.

### 데이터 흐름

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│   Moodle DB     │ ───► │   DataContext   │ ───► │ PersonaEngine   │
│   (읽기 전용)    │      │   (데이터 변환)  │      │   (규칙 평가)    │
└─────────────────┘      └─────────────────┘      └─────────────────┘
                                                          │
                                                          ▼
                         ┌─────────────────┐      ┌─────────────────┐
                         │  Custom Tables  │ ◄─── │   결과 저장      │
                         │  (쓰기)          │      │                 │
                         └─────────────────┘      └─────────────────┘
```

---

## 🗄️ Moodle 기본 테이블 (읽기 전용)

### 1. mdl_user - 사용자 기본 정보

| 필드 | 타입 | 용도 | 컨텍스트 키 |
|------|------|------|------------|
| id | BIGINT | 사용자 고유 ID | `user_id` |
| username | VARCHAR(100) | 로그인 아이디 | `moodle_data.user.username` |
| firstname | VARCHAR(100) | 이름 | `moodle_data.user.firstname` |
| lastname | VARCHAR(100) | 성 | `moodle_data.user.lastname` |
| email | VARCHAR(100) | 이메일 | `moodle_data.user.email` |

**사용 쿼리:**
```sql
SELECT id, username, firstname, lastname, email
FROM mdl_user
WHERE id = ?
```

### 2. mdl_user_info_data - 사용자 역할 정보

| 필드 | 타입 | 용도 | 컨텍스트 키 |
|------|------|------|------------|
| userid | BIGINT | 사용자 ID | - |
| fieldid | BIGINT | 필드 ID (22=역할) | - |
| data | TEXT | 역할 값 | `moodle_data.role` |

**역할 값 예시:**
- `student`: 학생
- `teacher`: 교사
- `admin`: 관리자
- `parent`: 학부모

**사용 쿼리:**
```sql
SELECT data
FROM mdl_user_info_data
WHERE userid = ? AND fieldid = 22
```

### 3. mdl_grade_grades - 성적 데이터

| 필드 | 타입 | 용도 | 컨텍스트 키 |
|------|------|------|------------|
| userid | BIGINT | 사용자 ID | - |
| itemid | BIGINT | 성적 항목 ID | - |
| finalgrade | DECIMAL | 최종 성적 | `moodle_data.grades[].avg_grade` |

**집계 데이터:**
- `grade_count`: 성적 항목 수
- `avg_grade`: 평균 성적
- `max_grade`: 최고 성적
- `min_grade`: 최저 성적

**사용 쿼리:**
```sql
SELECT
    gi.courseid,
    COUNT(gg.id) as grade_count,
    AVG(gg.finalgrade) as avg_grade,
    MAX(gg.finalgrade) as max_grade,
    MIN(gg.finalgrade) as min_grade
FROM mdl_grade_grades gg
JOIN mdl_grade_items gi ON gg.itemid = gi.id
WHERE gg.userid = ?
GROUP BY gi.courseid
ORDER BY gi.courseid DESC
LIMIT 5
```

### 4. mdl_logstore_standard_log - 활동 로그

| 필드 | 타입 | 용도 | 컨텍스트 키 |
|------|------|------|------------|
| userid | BIGINT | 사용자 ID | - |
| component | VARCHAR(100) | 활동 컴포넌트 | `moodle_data.recent_activity[].component` |
| action | VARCHAR(100) | 활동 유형 | `moodle_data.recent_activity[].action` |
| timecreated | BIGINT | 활동 시간 | `moodle_data.recent_activity[].last_activity` |

**사용 쿼리:**
```sql
SELECT
    component,
    action,
    COUNT(*) as count,
    MAX(timecreated) as last_activity
FROM mdl_logstore_standard_log
WHERE userid = ? AND timecreated > ?
GROUP BY component, action
ORDER BY count DESC
LIMIT 10
```

---

## 🆕 커스텀 테이블 (읽기/쓰기)

### 1. augmented_teacher_personas - 페르소나 식별 이력

**용도:** 학생의 페르소나 식별 결과를 시계열로 저장

| 필드 | 타입 | NULL | 기본값 | 설명 |
|------|------|------|--------|------|
| id | BIGINT AUTO_INCREMENT | NO | - | PK |
| user_id | BIGINT | NO | - | Moodle 사용자 ID |
| agent_id | VARCHAR(20) | NO | 'agent01' | 에이전트 ID |
| persona_id | VARCHAR(20) | NO | - | 페르소나 ID (예: S1_P1) |
| situation | VARCHAR(5) | NO | - | 상황 코드 (S0-S5, C, Q, E) |
| confidence | DECIMAL(3,2) | NO | 0.50 | 신뢰도 (0.00-1.00) |
| matched_rule | VARCHAR(50) | YES | NULL | 매칭된 규칙 ID |
| context_snapshot | TEXT | YES | NULL | 컨텍스트 스냅샷 (JSON) |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | 생성 시간 |

**인덱스:**
- `idx_user_agent (user_id, agent_id)` - 사용자별 조회
- `idx_persona (persona_id)` - 페르소나별 통계
- `idx_situation (situation)` - 상황별 분석
- `idx_created (created_at)` - 시계열 조회
- `idx_confidence (confidence)` - 신뢰도 필터링

### 2. augmented_teacher_sessions - AI 세션 컨텍스트

**용도:** AI 대화 세션의 상태와 컨텍스트 저장

| 필드 | 타입 | NULL | 기본값 | 설명 |
|------|------|------|--------|------|
| id | BIGINT AUTO_INCREMENT | NO | - | PK |
| user_id | BIGINT | NO | - | Moodle 사용자 ID |
| agent_id | VARCHAR(20) | NO | 'agent01' | 에이전트 ID |
| session_key | VARCHAR(64) | NO | - | 세션 고유 키 (UK) |
| current_situation | VARCHAR(5) | YES | NULL | 현재 상황 코드 |
| current_persona | VARCHAR(20) | YES | NULL | 현재 페르소나 ID |
| context_data | JSON | YES | NULL | 컨텍스트 데이터 |
| message_count | INT | NO | 0 | 메시지 수 |
| last_message | TEXT | YES | NULL | 마지막 메시지 |
| last_activity | TIMESTAMP | NO | ON UPDATE | 마지막 활동 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | 생성 시간 |

**인덱스:**
- `uk_session (session_key)` - UNIQUE KEY
- `idx_user_agent (user_id, agent_id)` - 사용자별 조회
- `idx_user_session (user_id, session_key)` - 세션 조회
- `idx_last_activity (last_activity)` - 활성 세션 조회
- `idx_current_persona (current_persona)` - 페르소나별 세션

### 3. augmented_teacher_persona_transitions - 페르소나 전환 이력

**용도:** 페르소나 변화 패턴 분석

| 필드 | 타입 | NULL | 기본값 | 설명 |
|------|------|------|--------|------|
| id | BIGINT AUTO_INCREMENT | NO | - | PK |
| user_id | BIGINT | NO | - | Moodle 사용자 ID |
| agent_id | VARCHAR(20) | NO | 'agent01' | 에이전트 ID |
| session_key | VARCHAR(64) | NO | - | 세션 키 |
| from_persona | VARCHAR(20) | YES | NULL | 이전 페르소나 |
| to_persona | VARCHAR(20) | NO | - | 새 페르소나 |
| from_situation | VARCHAR(5) | YES | NULL | 이전 상황 |
| to_situation | VARCHAR(5) | NO | - | 새 상황 |
| trigger_type | VARCHAR(30) | NO | - | 전환 트리거 유형 |
| trigger_detail | TEXT | YES | NULL | 전환 상세 |
| confidence_change | DECIMAL(4,2) | YES | NULL | 신뢰도 변화 |
| transition_time | TIMESTAMP | NO | CURRENT_TIMESTAMP | 전환 시간 |

**전환 트리거 유형:**
- `message_analysis`: 메시지 분석 결과
- `behavior_pattern`: 행동 패턴 감지
- `explicit_request`: 명시적 요청
- `situation_change`: 상황 변화
- `confidence_threshold`: 신뢰도 임계값 변화
- `time_based`: 시간 기반 (세션 타임아웃 등)

---

## 🔗 컨텍스트 데이터 구조

### 전체 컨텍스트 스키마

```php
$context = [
    // === 기본 정보 ===
    'user_id' => 123,                      // Moodle 사용자 ID
    'situation' => 'S1',                   // 현재 상황 코드
    'user_message' => '수학이 너무 어려워요', // 현재 메시지

    // === 메시지 분석 결과 ===
    'response_length' => 15,               // 메시지 길이
    'word_count' => 4,                     // 단어 수
    'emotional_keywords' => ['어려워'],     // 감정 키워드
    'negative_keywords' => ['어려워'],      // 부정 키워드
    'positive_keywords' => [],              // 긍정 키워드
    'emotional_state' => 'negative',        // 감정 상태
    'has_question' => false,                // 질문 포함 여부
    'is_short_response' => false,           // 짧은 응답 여부
    'is_defensive' => false,                // 방어적 응답 여부
    'shows_confidence' => false,            // 자신감 표현
    'shows_anxiety' => false,               // 불안 표현

    // === 세션 히스토리 ===
    'session_history' => [
        [
            'session_key' => 'abc123',
            'current_situation' => 'S1',
            'current_persona' => 'S1_P2',
            'last_activity' => '2025-12-02 10:30:00'
        ]
    ],

    // === 이전 페르소나 기록 ===
    'previous_personas' => [
        [
            'persona_id' => 'S1_P2',
            'situation' => 'S1',
            'confidence' => 0.85,
            'created_at' => '2025-12-02 10:25:00'
        ]
    ],

    // === Moodle 데이터 ===
    'moodle_data' => [
        'user' => [
            'id' => 123,
            'username' => 'student01',
            'firstname' => '철수',
            'lastname' => '김'
        ],
        'role' => 'student',
        'grades' => [
            [
                'courseid' => 5,
                'grade_count' => 10,
                'avg_grade' => 75.5,
                'max_grade' => 95,
                'min_grade' => 55
            ]
        ],
        'recent_activity' => [
            [
                'component' => 'mod_quiz',
                'action' => 'attempt',
                'count' => 15,
                'last_activity' => 1701500000
            ]
        ]
    ]
];
```

---

## 📊 데이터 접근 패턴

### 1. 학생 컨텍스트 로드

```php
// DataContext 사용
$dataContext = new DataContext();
$context = $dataContext->loadByUserId($userId, [
    'situation' => 'S1',
    'user_message' => '수학이 어려워요'
]);
```

### 2. 메시지 분석

```php
$analysis = $dataContext->analyzeMessage('수학이 너무 어려워요...');
// 반환: response_length, emotional_keywords, emotional_state 등
```

### 3. 페르소나 식별 결과 저장

```php
// PersonaRuleEngine 내부에서 자동 저장
// 또는 직접 저장
global $DB;
$record = new stdClass();
$record->user_id = $userId;
$record->agent_id = 'agent01';
$record->persona_id = 'S1_P2';
$record->situation = 'S1';
$record->confidence = 0.87;
$record->matched_rule = 'PI_S1_002';
$record->created_at = date('Y-m-d H:i:s');
$DB->insert_record('augmented_teacher_personas', $record);
```

### 4. 세션 컨텍스트 저장

```php
$dataContext->saveContext($userId, [
    'session_key' => 'unique_session_key',
    'situation' => 'S1',
    'persona_id' => 'S1_P2',
    // ... 기타 컨텍스트 데이터
]);
```

---

## ⚙️ 설정 및 초기화

### DB 테이블 생성

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/engine/db_setup.php
```

### 엔진 초기화

```php
require_once(__DIR__ . '/engine/PersonaRuleEngine.php');

$engine = new PersonaRuleEngine([
    'cache_enabled' => true,
    'cache_ttl' => 3600,
    'debug_mode' => false,
    'log_enabled' => true
]);

$engine->loadRules(__DIR__ . '/rules.yaml');
```

---

## 🔜 다음 단계

1. **5단계**: 페르소나 전환 관계 모델링 (augmented_teacher_persona_transitions 활용)
2. **6단계**: 동적 응답 템플릿 시스템
3. **7단계**: NLU 기반 조건 매칭 개선

---

## 📝 참고

- [engine/README.md](./README.md) - 엔진 아키텍처
- [db_setup.php](./db_setup.php) - DB 테이블 생성 스크립트
- [DataContext.php](./DataContext.php) - 데이터 컨텍스트 클래스
