# Agent09 Persona System - 학습 관리 페르소나 엔진

> **Version**: 1.0
> **URL**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/
> **Last Updated**: 2025-12-02

## 📋 개요

Agent09 Persona System은 학습 관리 에이전트를 위한 페르소나 기반 개인화 시스템입니다.
학생의 5가지 핵심 지표를 분석하여 8가지 페르소나 시리즈로 분류하고,
적응적인 학습 개입을 제공합니다.

### 핵심 특징
- 🎯 **5가지 지표 기반 분석**: 출결, 목표, 포모도로, 오답노트, 시험
- 👤 **8가지 페르소나 시리즈**: P, D, A, G, F, R, T, E
- ⚠️ **3단계 이탈 위험 경고**: 주의 → 경고 → 긴급
- 💬 **개인화된 응답 톤**: Gentle, Warm, Steady, Cheerful 등

---

## 📁 폴더 구조

```
persona_system/
├── README.md                    # 이 문서
├── personas.md                  # 페르소나 정의 문서
├── rules.yaml                   # 전환/개입 규칙
│
├── engine/                      # 핵심 엔진
│   ├── Agent09PersonaEngine.php # 페르소나 판정 엔진
│   └── Agent09DataContext.php   # 데이터 컨텍스트
│
├── db/                          # 데이터베이스 레이어
│   ├── schema.php               # DB 스키마 관리
│   ├── PersonaDataRepository.php # 데이터 저장소
│   └── api.php                  # REST API 엔드포인트
│
└── templates/                   # 응답 템플릿
    ├── response_templates.php   # 페르소나별 응답 템플릿
    └── message_templates.php    # 개입 메시지 템플릿
```

---

## 🚀 빠른 시작

### 1. 데이터베이스 설정

```
URL: https://mathking.kr/.../persona_system/db/schema.php
```

웹 브라우저에서 위 URL 접속 후 "Create Tables" 버튼 클릭

### 2. API 테스트

```
# 헬스 체크
GET /db/api.php?action=health

# 테스트 데이터 생성
GET /db/api.php?action=test&user_id=123
```

### 3. 페르소나 상태 조회

```php
<?php
require_once __DIR__ . '/db/PersonaDataRepository.php';

$repo = new PersonaDataRepository('agent09');
$state = $repo->getActivePersonaState($userId);

if ($state) {
    echo "현재 페르소나: " . $state['persona_code'];
    echo "신뢰도: " . $state['confidence_score'];
}
```

---

## 🎭 페르소나 시리즈

### 시리즈 코드 체계

| 코드 | 시리즈명 | 설명 | 핵심 지표 |
|------|----------|------|-----------|
| **P** | Pattern | 활동 패턴 기반 | data_density_score |
| **D** | Dropout | 이탈 위험 기반 | dropout_risk_score |
| **A** | Attendance | 출결 기반 | attendance_trend |
| **G** | Goal | 목표 달성 기반 | goal_completion_rate |
| **F** | Pomodoro | 포모도로 기반 | session_consistency |
| **R** | Wrong Note | 오답노트 기반 | review_effectiveness |
| **T** | Test | 시험 성적 기반 | performance_trend |
| **E** | Emotion | 감정 상태 기반 | motivation_level |

### 페르소나 코드 예시

```
P-SPARSE   : 활동데이터 희박형
P-ACTIVE   : 활발한 참여형
D-ALERT    : 이탈 주의 상태
D-CRITICAL : 이탈 위기 상태
A-IRREGULAR: 불규칙 출석형
G-ACHIEVER : 목표 달성형
```

---

## 📊 5가지 핵심 지표

### 1. 출결 지표 (Attendance)
- **테이블**: `mdl_at_attendance_log`
- **핵심 필드**: attendance_rate, recent_trend, streak_days
- **계산 방식**: 최근 30일 출석 데이터 기반

### 2. 목표 지표 (Goal)
- **테이블**: `mdl_at_student_goals`
- **핵심 필드**: completion_rate, goal_count, progress_momentum
- **계산 방식**: 주간/월간 목표 달성률

### 3. 포모도로 지표 (Pomodoro)
- **테이블**: `mdl_at_pomodoro_sessions`
- **핵심 필드**: avg_sessions_per_day, completion_rate, consistency
- **계산 방식**: 세션 완료율과 일관성 점수

### 4. 오답노트 지표 (Wrong Note)
- **테이블**: `mdl_at_wrong_notes`
- **핵심 필드**: total_notes, review_rate, mastery_improvement
- **계산 방식**: 복습 효과성과 마스터리 향상도

### 5. 시험 지표 (Test)
- **테이블**: `mdl_at_test_results`
- **핵심 필드**: avg_score, trend, percentile
- **계산 방식**: 성적 추세와 백분위 분석

---

## ⚠️ 이탈 위험 관리

### 3단계 경고 시스템

| 단계 | 코드 | dropout_risk_score | 행동 |
|------|------|-------------------|------|
| **주의** | warning | 0.4 ~ 0.6 | 관심 모니터링, 가벼운 격려 |
| **경고** | alert | 0.6 ~ 0.8 | 적극적 개입, 목표 재설정 |
| **긴급** | critical | > 0.8 | 즉각 조치, 담임 알림 |

### 위험 학생 조회

```php
// 고위험 학생 목록 조회
$atRiskStudents = $repo->getAtRiskStudents('high', 20);

foreach ($atRiskStudents as $student) {
    echo $student['firstname'] . ': ' . $student['dropout_risk_score'];
}
```

---

## 🔌 API 엔드포인트

### Base URL
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/db/api.php
```

### 엔드포인트 목록

| Method | Endpoint | 설명 |
|--------|----------|------|
| GET | `?action=health` | 서버 상태 확인 |
| GET | `?action=get_persona_state&user_id={id}` | 현재 페르소나 상태 |
| POST | `?action=save_persona_state&user_id={id}` | 페르소나 상태 저장 |
| POST | `?action=log_transition&user_id={id}` | 전환 기록 |
| GET | `?action=get_transition_history&user_id={id}` | 전환 이력 |
| POST | `?action=log_intervention&user_id={id}` | 개입 기록 |
| GET | `?action=get_interventions&user_id={id}` | 개입 기록 조회 |
| GET | `?action=get_statistics&user_id={id}` | 통계 조회 |
| GET | `?action=get_at_risk_students&risk_level={level}` | 위험 학생 목록 |

### API 요청 예시

#### 페르소나 상태 저장 (POST)
```javascript
fetch('/db/api.php?action=save_persona_state&user_id=123', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        persona_code: 'D-ALERT',
        persona_series: 'D',
        confidence_score: 0.85,
        dropout_risk_score: 0.65,
        intervention_level: 'high',
        recommended_tone: 'Warm'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

#### 개입 기록 (POST)
```javascript
fetch('/db/api.php?action=log_intervention&user_id=123', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        persona_code: 'D-ALERT',
        intervention_type: 'encouragement',
        intervention_level: '경고',
        indicator_type: 'attendance',
        message: '최근 출석이 불규칙해요. 같이 루틴을 다시 세워볼까요?',
        follow_up_needed: 1,
        follow_up_date: '2025-12-05'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

---

## 🔧 통합 가이드

### Agent09와 통합

```php
<?php
// agent09_learning_management/index.php 에서 사용

require_once __DIR__ . '/persona_system/engine/Agent09PersonaEngine.php';
require_once __DIR__ . '/persona_system/engine/Agent09DataContext.php';
require_once __DIR__ . '/persona_system/db/PersonaDataRepository.php';

// 1. 데이터 컨텍스트 생성
$context = new Agent09DataContext($USER->id);
$studentData = $context->collectAllData();

// 2. 페르소나 엔진 초기화
$engine = new Agent09PersonaEngine($USER->id, $studentData);

// 3. 현재 페르소나 판정
$persona = $engine->determinePersona();

// 4. 저장소에 상태 저장
$repo = new PersonaDataRepository('agent09');
$repo->savePersonaState($USER->id, [
    'persona_code' => $persona['code'],
    'persona_series' => $persona['series'],
    'confidence_score' => $persona['confidence'],
    'dropout_risk_score' => $studentData['dropout_risk'] ?? 0,
    'intervention_level' => $persona['intervention_level'],
    'recommended_tone' => $persona['tone']
]);

// 5. 응답 생성
$response = $engine->generateResponse($persona);
echo $response['message'];
```

### 다른 에이전트 참조

Agent09 페르소나 시스템은 다른 에이전트에서도 참조할 수 있습니다:

```php
// 다른 에이전트에서 Agent09 데이터 참조
$repo = new PersonaDataRepository('agent09');
$state = $repo->getActivePersonaState($userId);

if ($state && $state['dropout_risk_score'] > 0.6) {
    // 이탈 위험이 높은 학생에게 특별 조치
}
```

---

## 📝 설정 가이드

### 규칙 커스터마이징 (rules.yaml)

```yaml
# persona_system/rules.yaml

indicators:
  dropout_risk:
    thresholds:
      high: 0.8      # 0.8 이상이면 긴급
      medium: 0.6    # 0.6~0.8 경고
      low: 0.4       # 0.4~0.6 주의
```

### 응답 톤 커스터마이징

```php
// templates/response_templates.php

$toneTemplates = [
    'Gentle' => [
        'greeting' => '안녕하세요 {name}님, 오늘도 조금씩 나아가볼까요?',
        'encouragement' => '천천히 가도 괜찮아요. {progress}만큼 성장하고 있어요.'
    ],
    'Cheerful' => [
        'greeting' => '{name}님! 오늘도 화이팅! 🔥',
        'encouragement' => '와! {progress} 달성! 대단해요! 🎉'
    ]
];
```

---

## 🗄️ 데이터베이스 테이블

### 핵심 테이블 목록

| 테이블명 | 용도 |
|----------|------|
| `mdl_at_agent_persona_state` | 현재 페르소나 상태 |
| `mdl_at_persona_transition_log` | 페르소나 전환 이력 |
| `mdl_at_intervention_log` | 개입 기록 |
| `mdl_at_attendance_log` | 출결 로그 |
| `mdl_at_student_goals` | 학습 목표 |
| `mdl_at_pomodoro_sessions` | 포모도로 세션 |
| `mdl_at_wrong_notes` | 오답노트 |
| `mdl_at_test_results` | 시험 결과 |

### 스키마 생성

```
URL: https://mathking.kr/.../persona_system/db/schema.php
```

---

## 🔍 디버깅

### 로그 확인

```php
$repo = new PersonaDataRepository('agent09');
$debug = $repo->getDebugInfo();
print_r($debug);
```

### API 헬스 체크

```bash
curl "https://mathking.kr/.../db/api.php?action=health"
```

### 테스트 데이터 생성

```bash
curl "https://mathking.kr/.../db/api.php?action=test&user_id=123"
```

---

## 📚 참조 문서

- [personas.md](./personas.md) - 상세 페르소나 정의
- [rules.yaml](./rules.yaml) - 전환 규칙 정의
- [Agent01 Persona System](../../../ontology_engineering/persona_engine/) - 공통 엔진 참조

---

## 🆘 문제 해결

### 자주 발생하는 문제

1. **테이블 없음 오류**
   - 해결: `db/schema.php` 접속 후 테이블 생성

2. **API 인증 오류**
   - 해결: Moodle 로그인 필요 (require_login)

3. **페르소나 판정 안됨**
   - 해결: 데이터 밀도 확인, 최소 7일 데이터 필요

4. **신뢰도 점수 낮음**
   - 해결: 더 많은 활동 데이터 수집 필요

---

## 📞 지원

- **담당**: Agent09 Learning Management Team
- **문서 위치**: `/agents/agent09_learning_management/persona_system/`
- **API 엔드포인트**: `/db/api.php`

---

*Last Updated: 2025-12-02 | Agent09 Persona System v1.0*
