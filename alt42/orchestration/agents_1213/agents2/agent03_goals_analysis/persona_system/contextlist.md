# Agent03 Goals Analysis - Context List (상황 코드 정의서)

> 목표 분석 에이전트(Agent03)가 학습자의 상황을 판별하기 위한 컨텍스트 코드 정의서
> Version: 1.0
> Last Updated: 2025-12-02

---

## 개요

이 문서는 Agent03 Goals Analysis가 사용하는 상황(Context) 코드를 정의합니다.
각 상황 코드는 학습자의 목표 설정 및 달성 과정에서의 현재 상태를 나타냅니다.

### 상황 코드 체계

| 코드 | 상황명 | 설명 | 우선순위 |
|------|--------|------|----------|
| **G0** | Goal Setting | 목표 설정 단계 | Normal |
| **G1** | Goal Progress | 목표 진행 단계 | Normal |
| **G2** | Stagnation/Crisis | 정체/위기 단계 | High |
| **G3** | Goal Reset | 목표 재설정 단계 | Normal |
| **CRISIS** | Crisis Intervention | 위기 개입 필요 | Critical |

---

## G0: Goal Setting (목표 설정 단계)

### 정의
학습자가 새로운 목표를 설정하거나 기존 목표를 검토하는 단계

### 진입 조건
```yaml
entry_conditions:
  - condition: "새 학기/새 단원 시작"
    priority: 1
  - condition: "목표가 설정되지 않음 (goals_count == 0)"
    priority: 2
  - condition: "목표 설정 요청 메시지 감지"
    keywords: ["목표", "계획", "하고 싶어", "해야 해", "목표를 세우", "뭘 해야"]
    priority: 3
  - condition: "G3에서 전이 (목표 재설정 완료 후)"
    priority: 4
```

### 하위 상황 분류
| 하위 코드 | 상황 | 판별 기준 |
|-----------|------|-----------|
| G0.1 | 첫 목표 설정 | `goals_history.count == 0` |
| G0.2 | 추가 목표 설정 | `current_goals.count > 0 AND goal_add_request` |
| G0.3 | 목표 수정 요청 | `goal_modify_request == true` |
| G0.4 | 목표 검토 | `goal_review_request == true` |

### 연관 페르소나
- G0_P1: 야심찬 과목표 설정자
- G0_P2: 목표 회피형
- G0_P3: 모호한 목표 설정자
- G0_P4: 의존적 목표 설정자
- G0_P5: 균형 잡힌 목표 설정자
- G0_P6: 두려움 기반 회피자

### 탈출 조건
```yaml
exit_conditions:
  - to_context: "G1"
    condition: "유효한 목표가 설정됨 (valid_goals >= 1)"
  - to_context: "G2"
    condition: "목표 설정 실패 반복 (failed_attempts >= 3)"
  - to_context: "CRISIS"
    condition: "위기 신호 감지"
```

---

## G1: Goal Progress (목표 진행 단계)

### 정의
설정된 목표를 향해 진행 중인 단계

### 진입 조건
```yaml
entry_conditions:
  - condition: "유효한 목표 존재 AND 진행 중"
    priority: 1
  - condition: "G0에서 전이 (목표 설정 완료)"
    priority: 2
  - condition: "진행 상황 보고/질문"
    keywords: ["어떻게 되고 있", "진행", "달성", "했어", "하고 있어"]
    priority: 3
```

### 하위 상황 분류
| 하위 코드 | 상황 | 판별 기준 |
|-----------|------|-----------|
| G1.1 | 순조로운 진행 | `progress_rate >= 80% AND on_schedule` |
| G1.2 | 보통 진행 | `progress_rate >= 50% AND progress_rate < 80%` |
| G1.3 | 느린 진행 | `progress_rate >= 20% AND progress_rate < 50%` |
| G1.4 | 진행 어려움 | `progress_rate < 20% OR behind_schedule` |
| G1.5 | 목표 달성 임박 | `progress_rate >= 90% AND days_remaining <= 3` |

### 연관 페르소나
- G1_P1: 꾸준한 진행자
- G1_P2: 급진적 진행자
- G1_P3: 불규칙 진행자
- G1_P4: 외부 장애 경험자
- G1_P5: 동기 저하 경험자

### 탈출 조건
```yaml
exit_conditions:
  - to_context: "G0"
    condition: "새 목표 추가 요청"
  - to_context: "G2"
    condition: "진행률 < 20% 지속 (stagnation_days >= 7)"
  - to_context: "G3"
    condition: "목표 재설정 요청 OR 목표 달성 완료"
  - to_context: "CRISIS"
    condition: "위기 신호 감지"
```

### 진행률 계산 공식
```php
function calculateProgressRate($goal) {
    $completed_tasks = count(array_filter($goal['tasks'], fn($t) => $t['completed']));
    $total_tasks = count($goal['tasks']);

    if ($total_tasks == 0) {
        return calculateTimeBasedProgress($goal);
    }

    return ($completed_tasks / $total_tasks) * 100;
}

function calculateTimeBasedProgress($goal) {
    $total_days = dateDiff($goal['start_date'], $goal['end_date']);
    $elapsed_days = dateDiff($goal['start_date'], date('Y-m-d'));

    return min(100, ($elapsed_days / $total_days) * 100);
}
```

---

## G2: Stagnation/Crisis (정체/위기 단계)

### 정의
목표 진행이 멈추거나 심각한 어려움을 겪는 단계

### 진입 조건
```yaml
entry_conditions:
  - condition: "진행률 정체 (7일 이상 변화 없음)"
    priority: 1
  - condition: "반복적인 실패 표현"
    keywords: ["못 하겠", "포기", "안 돼", "어려워", "힘들어", "싫어"]
    priority: 2
  - condition: "G1에서 전이 (진행률 < 20% 지속)"
    priority: 3
  - condition: "부정적 감정 지속 감지"
    priority: 4
```

### 하위 상황 분류
| 하위 코드 | 상황 | 판별 기준 |
|-----------|------|-----------|
| G2.1 | 일시적 정체 | `stagnation_days < 14 AND mood_score > 40` |
| G2.2 | 장기 정체 | `stagnation_days >= 14 AND stagnation_days < 30` |
| G2.3 | 포기 위기 | `abandonment_signals >= 2 OR mood_score < 30` |
| G2.4 | 번아웃 | `burnout_indicators >= 3` |

### 연관 페르소나
- G2_P1: 일시적 좌절자
- G2_P2: 만성적 정체자
- G2_P3: 포기 선언자
- G2_P4: 번아웃 경험자

### 정체 신호 감지
```yaml
stagnation_signals:
  behavioral:
    - "로그인 빈도 감소 (login_frequency < 50%)"
    - "과제 제출률 하락 (submission_rate < 30%)"
    - "응답 시간 증가 (avg_response_time > 48h)"

  verbal:
    - keywords: ["안 해", "나중에", "모르겠", "왜 해야", "의미 없"]
    - patterns: ["부정문 + 목표 관련 단어"]
    - sentiment_score: "< 0.3"

  performance:
    - "성적 하락 추세 (grade_trend < -10%)"
    - "목표 대비 달성률 격차 (gap > 30%)"
```

### 탈출 조건
```yaml
exit_conditions:
  - to_context: "G1"
    condition: "진행 재개 (progress_resumed == true)"
  - to_context: "G3"
    condition: "목표 재설정 동의"
  - to_context: "CRISIS"
    condition: "위기 신호 감지 OR 심각한 정서적 어려움"
```

---

## G3: Goal Reset (목표 재설정 단계)

### 정의
기존 목표를 수정, 대체, 또는 완전히 새로 설정하는 단계

### 진입 조건
```yaml
entry_conditions:
  - condition: "목표 달성 완료"
    priority: 1
  - condition: "목표 재설정 요청"
    keywords: ["바꾸고 싶", "다시 세우", "새로운 목표", "수정하고 싶"]
    priority: 2
  - condition: "G2에서 전이 (목표 재설정 권유 수락)"
    priority: 3
  - condition: "환경/상황 변화로 인한 목표 무효화"
    priority: 4
```

### 하위 상황 분류
| 하위 코드 | 상황 | 판별 기준 |
|-----------|------|-----------|
| G3.1 | 목표 달성 후 새 목표 | `previous_goal.status == 'completed'` |
| G3.2 | 목표 하향 조정 | `new_goal.difficulty < previous_goal.difficulty` |
| G3.3 | 목표 상향 조정 | `new_goal.difficulty > previous_goal.difficulty` |
| G3.4 | 목표 방향 전환 | `new_goal.category != previous_goal.category` |

### 연관 페르소나
- G3_P1: 성공적 달성자
- G3_P2: 전략적 조정자

### 목표 재설정 가이드라인
```yaml
reset_guidelines:
  achievement_based:
    condition: "목표 달성 완료"
    action: "축하 + 새 목표 설정 유도"
    tone: "Encouraging"

  adjustment_based:
    condition: "현실적 조정 필요"
    action: "긍정적 프레이밍 + SMART 기준 적용"
    tone: "Warm"

  failure_based:
    condition: "실패로 인한 재설정"
    action: "감정 지지 + 학습 포인트 추출 + 현실적 목표 제안"
    tone: "Empathetic"
```

### 탈출 조건
```yaml
exit_conditions:
  - to_context: "G0"
    condition: "새 목표 설정 프로세스 시작"
  - to_context: "G1"
    condition: "조정된 목표로 바로 진행"
  - to_context: "CRISIS"
    condition: "위기 신호 감지"
```

---

## CRISIS: Crisis Intervention (위기 개입)

### 정의
즉각적인 개입이 필요한 심각한 정서적/학습적 위기 상황

### 진입 조건 (Critical Priority)
```yaml
entry_conditions:
  - condition: "자해/자살 관련 언급"
    keywords: ["죽고 싶", "사라지고 싶", "끝내고 싶", "살기 싫", "자해", "자살"]
    priority: 0  # 최우선
    immediate_action: true

  - condition: "극심한 정서적 고통"
    keywords: ["너무 힘들", "못 견디겠", "미치겠", "죽겠", "무너질 것 같"]
    priority: 1

  - condition: "학대/폭력 신호"
    keywords: ["맞았", "때려", "무서워", "도망가고 싶"]
    priority: 1
    immediate_action: true

  - condition: "심각한 고립감"
    keywords: ["아무도 없", "혼자", "이해 못 해", "외로워"]
    priority: 2
```

### 위기 수준 분류
| 수준 | 상황 | 즉시 조치 |
|------|------|-----------|
| **Level 0** | 생명 위협 | 즉시 전문가 연결 + 긴급 연락처 제공 |
| **Level 1** | 심각한 위기 | 감정 안정화 + 전문 상담 권유 |
| **Level 2** | 중간 위기 | 적극적 경청 + 지지 + 자원 연결 |
| **Level 3** | 경미한 위기 | 감정 인정 + 대처 전략 제안 |

### 연관 페르소나
- CRISIS_P1: 즉시 개입 필요 (Level 0-1)
- CRISIS_P2: 안정화 필요 (Level 2-3)

### 위기 대응 프로토콜
```yaml
crisis_protocol:
  level_0:
    immediate_actions:
      - "안전 확인 메시지 즉시 발송"
      - "긴급 연락처 표시 (자살예방상담전화 1393, 정신건강위기상담전화 1577-0199)"
      - "담당 교사/상담사에게 자동 알림"
      - "대화 로그 보존"
    tone: "Calm, Empathetic"
    intervention: "CrisisIntervention"

  level_1:
    actions:
      - "감정 안정화 메시지"
      - "전문 상담 연결 제안"
      - "담당자 알림 (선택적)"
    tone: "Empathetic, Calm"
    intervention: "EmotionalSupport + SafetyNet"

  level_2:
    actions:
      - "적극적 경청"
      - "감정 인정 및 공감"
      - "자원 및 지원 정보 제공"
    tone: "Warm, Empathetic"
    intervention: "EmotionalSupport"

  level_3:
    actions:
      - "감정 인정"
      - "대처 전략 제안"
      - "후속 체크인 예약"
    tone: "Encouraging, Warm"
    intervention: "EmotionalSupport + SkillBuilding"
```

### 탈출 조건
```yaml
exit_conditions:
  - to_context: "G1"
    condition: "위기 해소 + 목표 진행 가능"
  - to_context: "G3"
    condition: "위기 해소 + 목표 재설정 필요"
  - to_context: "EXTERNAL"
    condition: "전문가 개입으로 이관"
```

---

## 상황 전이 매트릭스

### 전이 다이어그램
```
                    ┌─────────────────────────────────────────┐
                    │                                         │
                    ▼                                         │
              ┌─────────┐                                     │
    ┌────────►│   G0    │◄────────┐                          │
    │         │ Setting │         │                          │
    │         └────┬────┘         │                          │
    │              │              │                          │
    │              │ 목표 설정    │                          │
    │              ▼              │                          │
    │         ┌─────────┐         │                          │
    │         │   G1    │─────────┤                          │
    │         │Progress │         │ 새 목표 추가             │
    │         └────┬────┘         │                          │
    │              │              │                          │
    │              │ 정체/위기    │                          │
    │              ▼              │                          │
    │         ┌─────────┐         │                          │
    │         │   G2    │─────────┘                          │
    │         │Stagnate │                                    │
    │         └────┬────┘                                    │
    │              │                                         │
    │              │ 재설정 동의                              │
    │              ▼                                         │
    │         ┌─────────┐                                    │
    └─────────│   G3    │────────────────────────────────────┘
              │ Reset   │
              └────┬────┘
                   │
                   │ 달성 완료
                   ▼
              ┌─────────┐
              │Complete │
              └─────────┘

    ※ 모든 상황에서 CRISIS로 즉시 전이 가능 (위기 신호 감지 시)

              ┌─────────┐
              │ CRISIS  │◄──── (G0, G1, G2, G3 어디서든 진입 가능)
              │         │
              └─────────┘
```

### 전이 조건 상세표

| From | To | Condition | Priority |
|------|-----|-----------|----------|
| G0 | G1 | 유효한 목표 1개 이상 설정 | Normal |
| G0 | G2 | 목표 설정 실패 3회 이상 | High |
| G0 | CRISIS | 위기 신호 감지 | Critical |
| G1 | G0 | 새 목표 추가 요청 | Normal |
| G1 | G2 | 7일 이상 진행률 < 20% | High |
| G1 | G3 | 목표 달성 OR 재설정 요청 | Normal |
| G1 | CRISIS | 위기 신호 감지 | Critical |
| G2 | G1 | 진행 재개 확인 | Normal |
| G2 | G3 | 목표 재설정 동의 | Normal |
| G2 | CRISIS | 심각한 정서적 어려움 | Critical |
| G3 | G0 | 새 목표 설정 프로세스 시작 | Normal |
| G3 | G1 | 조정된 목표로 즉시 진행 | Normal |
| G3 | CRISIS | 위기 신호 감지 | Critical |
| CRISIS | G1 | 위기 해소, 목표 진행 가능 | Low |
| CRISIS | G3 | 위기 해소, 목표 재설정 필요 | Low |
| CRISIS | EXTERNAL | 전문가 이관 필요 | Critical |

---

## 컨텍스트 감지 알고리즘

### 메시지 분석 프로세스
```php
/**
 * 학습자 메시지로부터 현재 컨텍스트 감지
 *
 * @param string $message 학습자 메시지
 * @param array $studentData 학습자 데이터 (목표, 진행률 등)
 * @return array ['context' => string, 'sub_context' => string, 'confidence' => float]
 */
function detectContext($message, $studentData) {
    // 1. 위기 신호 우선 검사 (Critical Priority)
    $crisisResult = checkCrisisSignals($message);
    if ($crisisResult['detected']) {
        return [
            'context' => 'CRISIS',
            'sub_context' => $crisisResult['level'],
            'confidence' => $crisisResult['confidence'],
            'immediate_action' => $crisisResult['immediate']
        ];
    }

    // 2. 키워드 기반 의도 분석
    $intent = analyzeIntent($message);

    // 3. 현재 상태 데이터 분석
    $stateAnalysis = analyzeCurrentState($studentData);

    // 4. 복합 판단
    return determineContext($intent, $stateAnalysis);
}

function checkCrisisSignals($message) {
    $crisisKeywords = [
        'level_0' => ['죽고 싶', '자살', '자해', '사라지고 싶', '끝내고 싶'],
        'level_1' => ['못 견디겠', '미치겠', '무너질 것 같', '너무 힘들'],
        'level_2' => ['아무도 없', '혼자', '외로워', '이해 못 해'],
        'level_3' => ['힘들어', '지쳤어', '스트레스', '우울해']
    ];

    foreach ($crisisKeywords as $level => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return [
                    'detected' => true,
                    'level' => $level,
                    'confidence' => 0.9,
                    'immediate' => in_array($level, ['level_0', 'level_1'])
                ];
            }
        }
    }

    return ['detected' => false];
}
```

### 상태 분석 기준
```yaml
state_analysis_criteria:
  goal_status:
    no_goals: "G0"
    active_goals: "G1 or G2 (진행률 기준)"
    completed_goals: "G3"

  progress_metrics:
    healthy: "progress_rate >= 50%"
    concerning: "progress_rate < 50% AND progress_rate >= 20%"
    critical: "progress_rate < 20%"

  engagement_metrics:
    active: "login_frequency >= 70% AND response_rate >= 60%"
    declining: "login_frequency < 70% OR response_rate < 60%"
    disengaged: "login_frequency < 30% AND response_rate < 30%"

  emotional_state:
    positive: "sentiment_score >= 0.6"
    neutral: "sentiment_score >= 0.4 AND sentiment_score < 0.6"
    negative: "sentiment_score < 0.4"
    critical: "sentiment_score < 0.2"
```

---

## 관련 문서

- [rules.yaml](./rules.yaml) - 페르소나 규칙 정의
- [personas.md](./personas.md) - 페르소나 상세 정의
- [persona_engine.config.php](../../ontology_engineering/persona_engine/config/persona_engine.config.php) - 공통 설정

---

## 변경 이력

| 버전 | 날짜 | 변경 내용 | 작성자 |
|------|------|-----------|--------|
| 1.0 | 2025-12-02 | 초기 버전 생성 | Agent03 Team |

---

## 관련 DB 테이블

- `at_student_goals` - 학생 목표 데이터
- `at_goal_progress` - 목표 진행 상황
- `at_agent_persona_state` - 에이전트 페르소나 상태
- `at_persona_log` - 페르소나 처리 로그
- `at_crisis_alerts` - 위기 알림 기록

**파일 위치:** `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/contextlist.md`
