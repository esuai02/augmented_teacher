# Agent03 Goals Analysis Persona System Manual
# 목표 분석 페르소나 시스템 매뉴얼

**Version**: 1.0
**Agent**: agent03_goals_analysis
**Last Updated**: 2025-12-02
**Author**: Augmented Teacher Development Team

---

## 목차

1. [시스템 개요](#1-시스템-개요)
2. [아키텍처](#2-아키텍처)
3. [설치 및 설정](#3-설치-및-설정)
4. [컨텍스트 시스템](#4-컨텍스트-시스템)
5. [페르소나 시스템](#5-페르소나-시스템)
6. [API 사용법](#6-api-사용법)
7. [위기 개입 시스템](#7-위기-개입-시스템)
8. [커스터마이징](#8-커스터마이징)
9. [문제 해결](#9-문제-해결)
10. [참조](#10-참조)

---

## 1. 시스템 개요

### 1.1 목적

Agent03 Goals Analysis Persona System은 학습자의 **목표 설정, 진행 관리, 위기 개입**을 담당하는 AI 기반 대화 시스템입니다. 학습자의 상황에 따라 적절한 페르소나를 선택하여 맞춤형 코칭을 제공합니다.

### 1.2 핵심 기능

| 기능 | 설명 |
|------|------|
| **컨텍스트 감지** | 사용자 메시지에서 목표 관련 상황 자동 인식 |
| **페르소나 선택** | 19개 페르소나 중 최적 매칭 |
| **위기 개입** | 4단계 위기 신호 감지 및 즉시 대응 |
| **템플릿 응답** | 상황별 맞춤 응답 생성 |
| **진행률 추적** | 목표 달성 상태 모니터링 |

### 1.3 시스템 흐름도

```
┌─────────────────────────────────────────────────────────────┐
│                    사용자 메시지 입력                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              ① 위기 신호 검사 (Critical Priority)            │
│   - level_0: 즉시 개입 (자살, 자해 언급)                      │
│   - level_1: 긴급 개입 (극심한 스트레스)                      │
│   - level_2/3: 정서적 지원                                   │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │ 위기 감지?                     │
              │                               │
         [예] │                          [아니오]
              ▼                               ▼
┌─────────────────────┐         ┌─────────────────────────────┐
│ 위기 응답 즉시 반환  │         │ ② 컨텍스트 감지              │
│ (전문가 연계 안내)   │         │   G0/G1/G2/G3 판단          │
└─────────────────────┘         └─────────────────────────────┘
                                              │
                                              ▼
                                ┌─────────────────────────────┐
                                │ ③ 페르소나 선택              │
                                │   상황별 최적 페르소나 매칭   │
                                └─────────────────────────────┘
                                              │
                                              ▼
                                ┌─────────────────────────────┐
                                │ ④ 응답 생성                  │
                                │   템플릿 기반 맞춤 응답      │
                                └─────────────────────────────┘
                                              │
                                              ▼
                                ┌─────────────────────────────┐
                                │ ⑤ JSON 응답 반환            │
                                └─────────────────────────────┘
```

---

## 2. 아키텍처

### 2.1 디렉토리 구조

```
agent03_goals_analysis/persona_system/
│
├── engine/
│   └── Agent03PersonaEngine.php    # 핵심 엔진 (컨텍스트 감지, 페르소나 선택)
│
├── api/
│   └── goals_chat.php              # REST API 엔드포인트
│
├── templates/
│   └── goal_templates.php          # 응답 템플릿 클래스
│
├── rules.yaml                      # 컨텍스트/페르소나 규칙 정의
├── personas.md                     # 페르소나 상세 문서
├── contextlist.md                  # 컨텍스트 정의 문서
├── test.php                        # 테스트 UI 페이지
│
└── docs/                           # 문서
    ├── MANUAL.md                   # 이 문서
    ├── API_GUIDE.md                # API 상세 가이드
    └── CUSTOMIZATION.md            # 커스터마이징 가이드
```

### 2.2 클래스 다이어그램

```
┌───────────────────────────────┐
│    AbstractPersonaEngine      │  (from ontology_engineering/persona_engine)
│  ─────────────────────────── │
│  + loadRules()               │
│  + analyzeMessage()          │
│  + selectPersona()           │
│  + process()                 │
└───────────────────────────────┘
                △
                │ extends
                │
┌───────────────────────────────┐
│    Agent03PersonaEngine       │
│  ─────────────────────────── │
│  - dataContext               │
│  - responseTemplates         │
│  ─────────────────────────── │
│  + detectContext()           │
│  + matchPersona()            │
│  + generateResponse()        │
│  + analyzeGoalIntent()       │
│  + detectEmotionalState()    │
└───────────────────────────────┘

┌───────────────────────────────┐
│    Agent03DataContext         │
│  ─────────────────────────── │
│  + loadUserContext()         │
│  + loadGoalData()            │
│  + loadActivityHistory()     │
│  + getGoalProgress()         │
│  + analyzeGoalCategoryBalance│
└───────────────────────────────┘

┌───────────────────────────────┐
│  Agent03ResponseTemplates     │
│  ─────────────────────────── │
│  + getTemplate()             │
│  + processTemplate()         │
│  + getAllTemplates()         │
│  + getByContext()            │
└───────────────────────────────┘
```

### 2.3 의존성

| 컴포넌트 | 경로 | 설명 |
|---------|------|------|
| AbstractPersonaEngine | `ontology_engineering/persona_engine/` | 공통 엔진 추상 클래스 |
| BaseDataContext | `ontology_engineering/persona_engine/` | 데이터 컨텍스트 기본 클래스 |
| BaseRuleParser | `ontology_engineering/persona_engine/` | YAML 규칙 파서 |
| Moodle DB | `/home/moodle/public_html/moodle/config.php` | 무들 데이터베이스 연결 |

---

## 3. 설치 및 설정

### 3.1 요구사항

- PHP 7.1.9+
- MySQL 5.7+
- Moodle 3.7+
- YAML 파서 (Symfony/Yaml 또는 native)

### 3.2 데이터베이스 테이블

#### 필수 테이블

```sql
-- 사용자 목표 테이블
CREATE TABLE IF NOT EXISTS at_user_goals (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    agent_id VARCHAR(50) DEFAULT 'agent03',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    goal_type ENUM('learning', 'behavior', 'achievement', 'personal') DEFAULT 'learning',
    target_value DECIMAL(10,2),
    current_value DECIMAL(10,2) DEFAULT 0,
    progress_percent DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'completed', 'paused', 'abandoned') DEFAULT 'active',
    priority INT DEFAULT 5,
    start_date DATE,
    target_date DATE,
    completed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_userid (userid),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 목표 활동 로그
CREATE TABLE IF NOT EXISTS at_goal_activities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    goal_id BIGINT NOT NULL,
    userid BIGINT NOT NULL,
    activity_type VARCHAR(50),
    description TEXT,
    progress_delta DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES at_user_goals(id) ON DELETE CASCADE,
    INDEX idx_goal (goal_id),
    INDEX idx_user (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 위기 알림 기록
CREATE TABLE IF NOT EXISTS at_crisis_alerts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    agent_id VARCHAR(50) DEFAULT 'agent03',
    crisis_level INT NOT NULL,
    detected_keyword VARCHAR(100),
    confidence DECIMAL(3,2),
    message_excerpt TEXT,
    action_taken VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_userid (userid),
    INDEX idx_level (crisis_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 페르소나 상태 기록
CREATE TABLE IF NOT EXISTS at_agent_persona_state (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    agent_id VARCHAR(50) NOT NULL,
    current_context VARCHAR(20),
    current_persona VARCHAR(50),
    session_data JSON,
    last_interaction TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_agent (userid, agent_id),
    INDEX idx_userid (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 설정 파일

`rules.yaml` 파일에서 핵심 설정을 관리합니다:

```yaml
agent:
  id: "agent03"
  name: "Goals Analysis Agent"
  version: "1.0"
  description: "목표 설정 및 진행 관리 에이전트"

context_detection:
  priority_order: ["CRISIS", "G2", "G0", "G3", "G1"]
  default_context: "G1"
  confidence_threshold: 0.6

crisis_detection:
  enabled: true
  auto_escalate: true
  log_all_detections: true
```

---

## 4. 컨텍스트 시스템

### 4.1 컨텍스트 코드 정의

| 코드 | 명칭 | 설명 | 대표 상황 |
|------|------|------|----------|
| **G0** | 목표 설정 | 새 목표를 설정하는 단계 | "이번 학기 목표를 세우고 싶어요" |
| **G1** | 목표 진행 | 정상적인 진행 상태 | "수학 공부 잘 하고 있어요" |
| **G2** | 정체/위기 | 진행 정체 또는 동기 저하 | "더 이상 못하겠어요" |
| **G3** | 목표 재설정 | 목표 수정/조정 필요 | "목표가 너무 어려워요" |
| **CRISIS** | 위기 상황 | 즉각적 개입 필요 | 자살/자해 언급 등 |

### 4.2 컨텍스트 감지 키워드

#### G0 (Goal Setting) 키워드
```
세우고 싶, 목표를 정하, 새로운 목표, 계획을 짜, 시작하고 싶,
무엇을 해야, 어떤 목표, 목표 추천, 처음이라, 새 학기
```

#### G1 (Progress) 키워드
```
진행 중, 잘 되고 있, 하고 있어, 달성률, 어디까지,
얼마나 했, 순조롭, 문제없이, 계속하고 있, 꾸준히
```

#### G2 (Stagnation/Crisis) 키워드
```
못하겠, 힘들어, 포기하고 싶, 안 되, 막혔, 지쳤,
의미없, 싫어, 그만두고 싶, 실패, 좌절, 무기력
```

#### G3 (Reset) 키워드
```
바꾸고 싶, 수정하고 싶, 다시 정하, 목표가 안 맞,
너무 높, 너무 낮, 조정하고 싶, 변경, 재설정
```

### 4.3 컨텍스트 전이 규칙

```
G0 ──(목표 설정 완료)──> G1
G1 ──(정상 진행)──────> G1
G1 ──(정체 감지)──────> G2
G1 ──(목표 달성)──────> G0 (새 목표)
G2 ──(회복)──────────> G1
G2 ──(목표 조정 필요)──> G3
G2 ──(심각한 위기)────> CRISIS
G3 ──(재설정 완료)────> G0
CRISIS ──(안정화)─────> G2 또는 G1
```

---

## 5. 페르소나 시스템

### 5.1 페르소나 매트릭스

#### G0 (Goal Setting) 페르소나

| ID | 명칭 | 톤 | 개입 패턴 | 상황 |
|----|------|-----|----------|------|
| G0_P1 | 현실적 목표 안내자 | Gentle | GapAnalysis | 비현실적 목표 |
| G0_P2 | 목표 탐색 조력자 | Curious | GoalSetting | 목표 불확실 |
| G0_P3 | SMART 가이드 | Professional | AssessmentDesign | 모호한 목표 |
| G0_P4 | 동기 발견자 | Warm | EmotionalSupport | 의욕 탐색 |
| G0_P5 | 균형 잡힌 설정자 | Balanced | PlanDesign | 표준 설정 |

#### G1 (Progress) 페르소나

| ID | 명칭 | 톤 | 개입 패턴 | 상황 |
|----|------|-----|----------|------|
| G1_P1 | 진행 체커 | Professional | InformationProvision | 일반 확인 |
| G1_P2 | 성취 축하자 | Encouraging | EmotionalSupport | 마일스톤 달성 |
| G1_P3 | 속도 조절자 | Calm | BehaviorModification | 과도한 속도 |
| G1_P4 | 학습 최적화 코치 | Analytical | SkillBuilding | 효율성 개선 |
| G1_P5 | 꾸준함 응원자 | Warm | EmotionalSupport | 일상적 진행 |

#### G2 (Stagnation/Crisis) 페르소나

| ID | 명칭 | 톤 | 개입 패턴 | 상황 |
|----|------|-----|----------|------|
| G2_P1 | 공감적 경청자 | Empathetic | EmotionalSupport | 감정적 어려움 |
| G2_P2 | 장벽 분석가 | Analytical | GapAnalysis | 장애물 식별 |
| G2_P3 | 작은 성공 유도자 | Encouraging | BehaviorModification | 동기 회복 |
| G2_P4 | 외부 자원 연결자 | Supportive | SafetyNet | 추가 지원 필요 |
| G2_P5 | 재도전 설계자 | Hopeful | PlanDesign | 새 접근법 |

#### G3 (Reset) 페르소나

| ID | 명칭 | 톤 | 개입 패턴 | 상황 |
|----|------|-----|----------|------|
| G3_P1 | 성찰 촉진자 | Thoughtful | GapAnalysis | 원인 분석 |
| G3_P2 | 유연성 조력자 | Flexible | GoalSetting | 목표 조정 |
| G3_P3 | 새 방향 제시자 | Visionary | PlanDesign | 대안 제시 |
| G3_P4 | 경험 가치화 코치 | Positive | EmotionalSupport | 실패 수용 |

#### CRISIS 페르소나

| ID | 명칭 | 톤 | 개입 패턴 | 상황 |
|----|------|-----|----------|------|
| CRISIS_P1 | 즉각 안정화 담당 | Calm | CrisisIntervention | Level 0-1 |
| CRISIS_P2 | 정서적 안전망 | Empathetic | EmotionalSupport | Level 2-3 |

### 5.2 페르소나 선택 알고리즘

```php
function selectPersona($context, $userState, $messageAnalysis) {
    // 1. 위기 우선 처리
    if ($context === 'CRISIS') {
        return $messageAnalysis['crisis_level'] <= 1
            ? 'CRISIS_P1'
            : 'CRISIS_P2';
    }

    // 2. 컨텍스트별 페르소나 풀 조회
    $personaPool = $this->getPersonasByContext($context);

    // 3. 조건 매칭 점수 계산
    $scores = [];
    foreach ($personaPool as $persona) {
        $scores[$persona['id']] = $this->calculateMatchScore(
            $persona,
            $userState,
            $messageAnalysis
        );
    }

    // 4. 최고 점수 페르소나 반환
    arsort($scores);
    return key($scores);
}
```

---

## 6. API 사용법

### 6.1 엔드포인트

**URL**: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php`

### 6.2 요청 형식

#### POST 요청 (권장)

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "message": "이번 학기 목표를 세우고 싶어요",
    "user_id": 123,
    "context": "G0"
  }' \
  https://mathking.kr/.../api/goals_chat.php
```

#### GET 요청 (테스트용)

```
GET /api/goals_chat.php?message=목표진행상황&user_id=123&context=G1
```

### 6.3 요청 파라미터

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| `message` | string | ✅ | 사용자 메시지 |
| `user_id` | int | ❌ | 사용자 ID (미입력시 로그인 사용자) |
| `context` | string | ❌ | 컨텍스트 힌트 (G0/G1/G2/G3/CRISIS) |
| `goal_id` | int | ❌ | 특정 목표 ID |

### 6.4 응답 형식

```json
{
  "success": true,
  "user_id": 123,
  "context": {
    "detected": "G0",
    "sub_context": "G0.1",
    "confidence": 0.85
  },
  "persona": {
    "persona_id": "G0_P5",
    "persona_name": "균형 잡힌 목표 설정자",
    "tone": "Balanced",
    "intervention": "PlanDesign"
  },
  "response": {
    "text": "이번 학기 목표를 세우시려고 하시는군요! ...",
    "source": "template",
    "follow_up_questions": [
      "학업 목표인가요, 개인 성장 목표인가요?"
    ]
  },
  "goal_analysis": {
    "goal_intent": "set_goal",
    "emotional_state": "motivated",
    "topics": ["goal_setting", "academic"]
  },
  "meta": {
    "agent": "agent03_goals_analysis",
    "processing_time_ms": 45.32,
    "timestamp": "2025-12-02 10:30:00"
  }
}
```

### 6.5 에러 응답

```json
{
  "success": false,
  "error": "message 파라미터가 필요합니다",
  "error_code": "MISSING_MESSAGE",
  "file": "/path/to/goals_chat.php",
  "line": 82
}
```

---

## 7. 위기 개입 시스템

### 7.1 위기 레벨 정의

| 레벨 | 심각도 | 키워드 예시 | 대응 |
|------|--------|------------|------|
| **level_0** | 즉시 개입 | 죽고 싶, 자살, 자해, 끝내고 싶 | 즉시 전문가 연계 안내 |
| **level_1** | 긴급 | 못 견디겠, 무너질 것 같, 더 이상 못 | 정서적 지지 + 상담 권유 |
| **level_2** | 주의 | 아무도 없, 혼자야, 소용없어 | 공감 및 정서적 지원 |
| **level_3** | 관찰 | 힘들어, 지쳤어, 우울해 | 정서적 지원 및 대화 |

### 7.2 위기 응답 프로토콜

#### Level 0 응답

```
지금 많이 힘드시군요. 당신의 안전이 가장 중요해요.
혼자 감당하지 마시고 전문가의 도움을 받으세요.

📞 자살예방상담전화: 1393 (24시간)
📞 정신건강위기상담전화: 1577-0199

언제든 이야기 나눌 준비가 되어 있어요.
```

### 7.3 위기 로깅

모든 위기 감지는 `at_crisis_alerts` 테이블에 자동 기록됩니다:

```php
$DB->insert_record('at_crisis_alerts', [
    'userid' => $userId,
    'agent_id' => 'agent03',
    'crisis_level' => $level,
    'detected_keyword' => $keyword,
    'confidence' => $confidence,
    'created_at' => date('Y-m-d H:i:s')
]);
```

---

## 8. 커스터마이징

### 8.1 페르소나 추가

`templates/goal_templates.php`에서 새 페르소나 템플릿 추가:

```php
'G1_P6_custom' => [
    'tone' => 'CustomTone',
    'intervention' => 'CustomIntervention',
    'templates' => [
        '새로운 템플릿 메시지 {{user_name}}{{honorific}}...',
    ]
],
```

### 8.2 컨텍스트 키워드 수정

`rules.yaml`에서 키워드 수정:

```yaml
contexts:
  G0:
    keywords:
      - "새 키워드 추가"
```

### 8.3 응답 변수

템플릿에서 사용 가능한 변수:

| 변수 | 설명 | 예시 |
|------|------|------|
| `{{user_name}}` | 사용자 이름 | 김철수 |
| `{{honorific}}` | 경칭 | 님, 학생 |
| `{{goal_title}}` | 목표 제목 | 수학 점수 향상 |
| `{{progress}}` | 진행률 | 45% |
| `{{days_left}}` | 남은 일수 | 30 |

---

## 9. 문제 해결

### 9.1 일반적인 문제

#### Q: 컨텍스트가 잘못 감지됩니다

**원인**: 키워드 중복 또는 우선순위 문제
**해결**: `rules.yaml`에서 `priority_order` 확인 및 키워드 정제

#### Q: 위기 감지가 너무 민감합니다

**원인**: level_3 키워드가 일반 대화에 자주 등장
**해결**: `crisis_detection.confidence_threshold` 값 상향 조정

#### Q: 응답이 생성되지 않습니다

**원인**: 템플릿 미등록 또는 페르소나 매칭 실패
**해결**: 해당 컨텍스트의 기본(default) 페르소나 확인

### 9.2 디버그 모드

테스트 페이지에서 디버그 정보 확인:
```
https://mathking.kr/.../persona_system/test.php
```

---

## 10. 참조

### 10.1 관련 문서

- [API 상세 가이드](./API_GUIDE.md)
- [커스터마이징 가이드](./CUSTOMIZATION.md)
- [페르소나 정의서](./personas.md)
- [컨텍스트 정의서](./contextlist.md)

### 10.2 관련 테이블

| 테이블명 | 설명 |
|---------|------|
| `at_user_goals` | 사용자 목표 정보 |
| `at_goal_activities` | 목표 활동 로그 |
| `at_agent_persona_state` | 페르소나 상태 |
| `at_crisis_alerts` | 위기 알림 기록 |

### 10.3 버전 히스토리

| 버전 | 날짜 | 변경 내용 |
|------|------|----------|
| 1.0 | 2025-12-02 | 최초 릴리즈 |

---

**문의**: Augmented Teacher Development Team
**파일 위치**: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/MANUAL.md`
