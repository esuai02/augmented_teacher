# Agent13 학습 이탈 페르소나 시스템 (혼합형)

## 개요

Agent13은 학습자의 이탈 위험을 실시간으로 감지하고 적절한 개입 전략을 제공하는 에이전트입니다.
**혼합형 접근법**을 사용하여 **위험 등급**과 **이탈 원인**을 조합한 12개 페르소나를 통해
더욱 정밀한 맞춤형 코칭을 제공합니다.

**서버 URL**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/

---

## 혼합형 페르소나 매트릭스

### 구조: 3 위험등급 × 4 이탈원인 = 12 페르소나

| 위험등급 | M (동기저하) | R (루틴붕괴) | S (시작장벽) | E (외부요인) |
|---------|-------------|-------------|-------------|-------------|
| **Low** | L_M | L_R | L_S | L_E |
| **Medium** | M_M | M_R | M_S | M_E |
| **High** | H_M | H_R | H_S | H_E |

> **Critical 등급**: 연속 2일 이상 High 상태 시 자동 에스컬레이션 (페르소나와 무관하게 긴급 개입)

---

## 위험 등급 정의 (Risk Tier)

### Low (저위험)
- **risk_score**: 0-30
- **특성**: 규칙적 학습 패턴, 이탈 징후 미미
- **지표 조건**:
  - ninactive ≤ 1
  - npomodoro ≥ 5
  - eye_count ≤ 1

### Medium (중위험)
- **risk_score**: 31-60
- **특성**: 간헐적 이탈, 관리 가능 수준
- **지표 조건**:
  - ninactive: 2-3
  - npomodoro: 2-4
  - eye_count ≥ 2

### High (고위험)
- **risk_score**: 61-100
- **특성**: 지속적 이탈, 적극적 개입 필요
- **지표 조건**:
  - ninactive ≥ 4 OR
  - npomodoro < 2 OR
  - tlaststroke_min ≥ 30

---

## 이탈 원인 정의 (Dropout Cause)

### M - Motivation (동기 저하)
- **코드**: M
- **설명**: 학습 동기나 의욕이 저하된 상태
- **감지 지표**:
  - 감정 키워드: "싫어요", "재미없어요", "왜 해야 해요", "포기"
  - pomodoro 완료율 급락
  - 학습 시간 대비 휴식 비율 증가
- **주요 원인**:
  - 목표 불명확
  - 학습 효능감 저하
  - 보상 체계 부재

### R - Routine (루틴 붕괴)
- **코드**: R
- **설명**: 기존 학습 루틴이 무너진 상태
- **감지 지표**:
  - nlazy_blocks ≥ 3
  - 학습 시작 시간 불규칙 (표준편차 > 2시간)
  - pomodoro 시작-중단 반복
- **주요 원인**:
  - 일정 변화 (시험, 방학)
  - 수면 패턴 변화
  - 외부 활동 증가

### S - Start Barrier (시작 장벽)
- **코드**: S
- **설명**: 학습을 시작하는 데 어려움을 겪는 상태
- **감지 지표**:
  - tlaststroke_min ≥ 30 (로그인 후 무활동)
  - 첫 포모도로 시작까지 시간 > 30분
  - eye_count 높음 (보기만 하고 시작 안 함)
- **주요 원인**:
  - 과제 난이도 인식 (너무 어려워 보임)
  - 완벽주의 성향
  - 에너지 부족

### E - External (외부 요인)
- **코드**: E
- **설명**: 외부 환경 요인으로 인한 이탈
- **감지 지표**:
  - 특정 시간대에만 이탈 집중
  - 학원 수업 후 이탈 증가
  - 과제 부담 지표 높음
- **주요 원인**:
  - 학원/학교 일정
  - 가정 환경
  - 체력/건강 문제

---

## 12개 혼합형 페르소나 상세

### Low Risk 페르소나 (예방적 관리)

#### L_M - 저위험 동기저하형
```yaml
persona_id: "L_M"
persona_name: "예방적 동기 케어"
risk_tier: Low
dropout_cause: M
risk_score_range: [0, 30]
coaching_tone: supportive
intervention_mode: encourage
message_style: "성취감 강화, 작은 목표 축하"
priority: 10
```
**특성**: 전반적으로 양호하나 동기 저하 징후 초기 감지
**개입 전략**:
- 학습 성과 시각화 제공
- 작은 성취 즉시 축하
- 흥미로운 도전 과제 제안

#### L_R - 저위험 루틴이탈형
```yaml
persona_id: "L_R"
persona_name: "루틴 유지 도우미"
risk_tier: Low
dropout_cause: R
risk_score_range: [0, 30]
coaching_tone: supportive
intervention_mode: remind
message_style: "부드러운 리마인드, 루틴 강화"
priority: 10
```
**특성**: 양호한 상태이나 루틴 불규칙 징후 감지
**개입 전략**:
- 학습 시간 알림 최적화
- 루틴 유지 칭찬
- 일정 조정 지원

#### L_S - 저위험 시작장벽형
```yaml
persona_id: "L_S"
persona_name: "워밍업 가이드"
risk_tier: Low
dropout_cause: S
risk_score_range: [0, 30]
coaching_tone: supportive
intervention_mode: guide
message_style: "쉬운 시작점 제시, 부담 완화"
priority: 10
```
**특성**: 시작 지연이 있으나 일단 시작하면 잘 진행
**개입 전략**:
- 5분 미니 과제로 시작 유도
- 쉬운 문제로 워밍업
- 시작 성공 시 즉시 격려

#### L_E - 저위험 외부요인형
```yaml
persona_id: "L_E"
persona_name: "환경 조율자"
risk_tier: Low
dropout_cause: E
risk_score_range: [0, 30]
coaching_tone: supportive
intervention_mode: adapt
message_style: "유연한 일정 제안, 환경 고려"
priority: 10
```
**특성**: 외부 일정에 따른 간헐적 이탈
**개입 전략**:
- 유연한 학습 시간 제안
- 짧은 세션 옵션 제공
- 환경 요인 파악 및 적응

---

### Medium Risk 페르소나 (적극적 관리)

#### M_M - 중위험 동기저하형
```yaml
persona_id: "M_M"
persona_name: "동기 부스터"
risk_tier: Medium
dropout_cause: M
risk_score_range: [31, 60]
coaching_tone: encouraging
intervention_mode: motivate
message_style: "구체적 목표 설정, 보상 연결"
priority: 20
```
**특성**: 동기 저하가 이탈로 이어지는 패턴
**개입 전략**:
- 명확한 단기 목표 설정
- 완료 시 보상 시각화
- Agent05 연계 (감정 지원)
- "왜" 학습하는지 재확인

#### M_R - 중위험 루틴붕괴형
```yaml
persona_id: "M_R"
persona_name: "루틴 복구 코치"
risk_tier: Medium
dropout_cause: R
risk_score_range: [31, 60]
coaching_tone: encouraging
intervention_mode: restructure
message_style: "새 루틴 제안, 단계적 복구"
priority: 20
```
**특성**: 기존 루틴이 무너져 이탈 빈도 증가
**개입 전략**:
- 새로운 학습 시간표 제안
- Agent12 연계 (휴식 루틴)
- 10분 미니 세션으로 재구축
- 일관성 회복 단계별 목표

#### M_S - 중위험 시작장벽형
```yaml
persona_id: "M_S"
persona_name: "시작 도우미"
risk_tier: Medium
dropout_cause: S
risk_score_range: [31, 60]
coaching_tone: encouraging
intervention_mode: scaffold
message_style: "첫 발걸음 지원, 과제 분해"
priority: 20
```
**특성**: 시작 장벽이 높아 자주 포기
**개입 전략**:
- 과제를 작은 단위로 분해
- "딱 5분만" 접근법
- 쉬운 첫 문제 자동 제시
- 시작 성공률 추적 및 피드백

#### M_E - 중위험 외부요인형
```yaml
persona_id: "M_E"
persona_name: "환경 적응 매니저"
risk_tier: Medium
dropout_cause: E
risk_score_range: [31, 60]
coaching_tone: understanding
intervention_mode: accommodate
message_style: "상황 공감, 대안 제시"
priority: 20
```
**특성**: 외부 요인이 학습을 지속적으로 방해
**개입 전략**:
- 현재 상황 파악 질문
- 맞춤형 학습 일정 재조정
- 짧은 집중 세션 옵션
- 학원/학교 일정 연동 고려

---

### High Risk 페르소나 (긴급 개입)

#### H_M - 고위험 동기저하형
```yaml
persona_id: "H_M"
persona_name: "동기 회복 전문가"
risk_tier: High
dropout_cause: M
risk_score_range: [61, 100]
coaching_tone: caring
intervention_mode: reconnect
message_style: "공감 우선, 학습 의미 재발견"
priority: 30
```
**특성**: 심각한 동기 저하로 이탈 위험 높음
**개입 전략**:
- Agent05 즉시 연계 (감정 지원)
- 학습 목적 재확인 대화
- 성공 경험 회상 유도
- 아주 쉬운 과제로 자신감 회복

#### H_R - 고위험 루틴붕괴형
```yaml
persona_id: "H_R"
persona_name: "루틴 재건 전문가"
risk_tier: High
dropout_cause: R
risk_score_range: [61, 100]
coaching_tone: coaching
intervention_mode: rebuild
message_style: "체계적 재구축, 작은 약속부터"
priority: 30
```
**특성**: 완전히 무너진 루틴으로 지속적 이탈
**개입 전략**:
- Agent12 긴급 연계 (휴식/수면 루틴)
- 매일 5분 약속으로 시작
- 하루 1개 포모도로 목표
- 일관성 복구 중점

#### H_S - 고위험 시작장벽형
```yaml
persona_id: "H_S"
persona_name: "시작 불안 해소 전문가"
risk_tier: High
dropout_cause: S
risk_score_range: [61, 100]
coaching_tone: gentle
intervention_mode: hand_hold
message_style: "함께 시작, 완벽주의 완화"
priority: 30
```
**특성**: 시작 자체를 극도로 회피하는 상태
**개입 전략**:
- "지금 바로 같이 시작해요" 접근
- 가장 쉬운 1문제만 제시
- 완료 여부보다 시작 자체 칭찬
- 시작 불안 원인 파악 대화

#### H_E - 고위험 외부요인형
```yaml
persona_id: "H_E"
persona_name: "환경 위기 관리자"
risk_tier: High
dropout_cause: E
risk_score_range: [61, 100]
coaching_tone: understanding
intervention_mode: adapt_urgent
message_style: "상황 인정, 최소 유지 목표"
priority: 30
```
**특성**: 심각한 외부 요인으로 학습 불가능 상태
**개입 전략**:
- 현재 상황 완전 파악
- 학습 중단도 옵션으로 인정
- 최소한의 연결 유지 (하루 1분)
- 상황 호전 시 복귀 계획 수립

---

## Critical 에스컬레이션

### 자동 트리거 조건
```yaml
critical_escalation:
  trigger: consecutive_high_days >= 2
  action:
    - notify_teacher: true
    - notify_parents: true
    - agent05_emergency_connect: true
    - pause_automated_messages: true
  message: "며칠째 어려움이 계속되고 있어요. 선생님과 함께 상담해볼까요?"
```

### Critical 상태 처리
- 모든 페르소나와 무관하게 최우선 처리
- 자동화된 개입 일시 중단
- 인간(교사/학부모) 개입 요청
- Agent05 감정 위기 프로토콜 연계

---

## 원인 감지 로직

### 원인 판별 우선순위
```
1. E (외부요인) - 시간대/일정 패턴 분석
2. S (시작장벽) - 로그인 후 무활동 시간
3. R (루틴붕괴) - 학습 시간 불규칙성
4. M (동기저하) - 감정 키워드/포기 표현 (기본값)
```

### 원인 점수 계산
```php
cause_scores = [
    'M' => calculateMotivationScore($context),  // 감정 분석 기반
    'R' => calculateRoutineScore($context),     // 루틴 붕괴 지표
    'S' => calculateStartBarrierScore($context), // 시작 지연 지표
    'E' => calculateExternalScore($context)     // 외부 요인 지표
];

// 최고 점수 원인 선택
$primary_cause = array_keys($cause_scores, max($cause_scores))[0];
```

---

## 위험 점수 계산 (기존 유지)

### 점수 산정 공식
```php
risk_score =
    (ninactive / 6) * 35              // 이탈 이벤트 (최대 35점)
  + (1 - npomodoro / 8) * 25          // 포모도로 역점수 (최대 25점)
  + (tlaststroke_min / 60) * 20       // 무입력 시간 (최대 20점)
  + (eye_count / 5) * 10              // 지연 시청 (최대 10점)
  + consecutive_high_days * 5         // 연속 고위험 (최대 10점)
```

### 위험 등급 판별
```
risk_score: 0-30 → Low
risk_score: 31-60 → Medium
risk_score: 61-100 → High
consecutive_high_days >= 2 → Critical (에스컬레이션)
```

---

## 코칭 메시지 템플릿

### Low Risk 메시지

#### L_M (동기저하)
```
"오늘도 꾸준히 잘하고 있어요! 🎯
지금까지 {npomodoro}번의 포모도로를 완료했네요.
다음 목표: {next_goal} - 충분히 해낼 수 있어요!"
```

#### L_R (루틴이탈)
```
"오늘 학습 시간이 평소보다 조금 늦었네요. ⏰
괜찮아요, 지금 시작하는 것만으로도 훌륭해요!
평소 루틴대로 {usual_time}에 시작하면 더 수월할 거예요."
```

#### L_S (시작장벽)
```
"시작이 반이에요! 🌟
딱 5분만 쉬운 문제부터 풀어볼까요?
시작하고 나면 금방 흐름을 탈 수 있을 거예요."
```

#### L_E (외부요인)
```
"오늘 바쁜 하루였나 봐요. 📚
남은 시간에 짧게라도 학습하면 좋겠어요.
10분 미니 세션 어떠세요?"
```

---

### Medium Risk 메시지

#### M_M (동기저하)
```
"요즘 학습하기 좀 힘들었나요? 😊
완전히 이해해요. 잠깐 쉬었다 와도 괜찮아요.
오늘 목표: 딱 1개 문제만 풀어보는 건 어떨까요?"
```

#### M_R (루틴붕괴)
```
"최근 학습 패턴이 불규칙해졌네요. 🔄
새로운 루틴을 만들어볼까요?
제안: 매일 {suggested_time}에 15분씩 시작하기!"
```

#### M_S (시작장벽)
```
"시작하기 어려울 때가 있죠. 💪
지금 가장 쉬운 문제 1개만 풀어봐요.
{easy_problem} - 이 문제로 워밍업 해볼까요?"
```

#### M_E (외부요인)
```
"요즘 많이 바쁜 것 같아요. 📆
학원 끝나고 지칠 수 있어요.
오늘은 복습 10분만 해도 충분해요!"
```

---

### High Risk 메시지

#### H_M (동기저하)
```
"많이 지쳤나 봐요. 마음이 힘들 때도 있죠. 🤗
학습은 잠시 쉬어도 괜찮아요.
혹시 이야기하고 싶은 게 있으면 말해줘요."
```

#### H_R (루틴붕괴)
```
"학습 루틴이 많이 흔들렸네요. 🌿
처음부터 다시 시작해도 괜찮아요.
오늘 약속: 딱 5분만 책상 앞에 앉아보기!"
```

#### H_S (시작장벽)
```
"시작하는 게 너무 어렵게 느껴지죠? 🌈
완벽하게 하지 않아도 돼요.
지금 바로 같이 시작해볼까요? 제가 도와줄게요."
```

#### H_E (외부요인)
```
"지금 상황이 많이 힘든 것 같아요. 💙
학습보다 지금 상태가 더 중요해요.
상황이 나아지면 그때 다시 시작해도 괜찮아요."
```

---

## 에이전트 간 협력

### Agent05 (학습 감정) 연계
- **트리거**: M_M, M_S, H_M, H_S 또는 감정 키워드 감지
- **전달 정보**: 위험 수준, 원인 유형, 감정 키워드
- **목적**: 감정적 지원 병행

### Agent12 (휴식 루틴) 연계
- **트리거**: M_R, H_R 또는 tlaststroke_min ≥ 30
- **전달 정보**: 루틴 붕괴 지표, 수면 패턴 추정
- **목적**: 루틴 재구축 지원

### 브로드캐스트 이벤트
```php
// 페르소나 전환 시 알림
$bus->broadcast([
    'type' => 'persona_identified',
    'userId' => $userId,
    'personaId' => 'M_M',
    'riskTier' => 'Medium',
    'dropoutCause' => 'M',
    'riskScore' => $score
]);

// Critical 에스컬레이션 시
$bus->broadcast([
    'type' => 'dropout_risk_critical',
    'userId' => $userId,
    'riskScore' => $score,
    'consecutiveDays' => $days,
    'urgency' => 'high'
]);
```

---

## DB 스키마

### mdl_at_agent13_dropout_risk
위험 평가 기록 테이블

| 필드 | 타입 | 설명 |
|------|------|------|
| id | BIGINT PK | 기본 키 |
| user_id | BIGINT | 사용자 ID |
| risk_tier | VARCHAR(20) | Low/Medium/High/Critical |
| dropout_cause | VARCHAR(10) | M/R/S/E |
| risk_score | DECIMAL(5,2) | 위험 점수 (0-100) |
| persona_code | VARCHAR(10) | 혼합형 페르소나 코드 (예: M_M) |
| indicators_snapshot | JSON | 지표 스냅샷 |
| intervention_suggested | VARCHAR(50) | 권장 개입 |
| timecreated | INT | 생성 시간 |

### mdl_at_agent13_intervention_log
개입 기록 테이블

| 필드 | 타입 | 설명 |
|------|------|------|
| id | BIGINT PK | 기본 키 |
| user_id | BIGINT | 사용자 ID |
| intervention_type | VARCHAR(50) | 개입 유형 |
| persona_code | VARCHAR(10) | 적용 페르소나 |
| dropout_cause | VARCHAR(10) | 이탈 원인 |
| message_sent | TEXT | 발송 메시지 |
| risk_score_before | DECIMAL(5,2) | 개입 전 점수 |
| response_type | VARCHAR(50) | 사용자 반응 유형 |
| timecreated | INT | 생성 시간 |

---

## 설정 상수

```php
// 시간 윈도우
const ROLLING_WINDOW_SECONDS = 86400;    // 24시간
const COOLDOWN_SECONDS = 300;            // 이탈 이벤트 쿨다운 5분

// 위험 임계값
const RISK_LOW_MAX = 30;
const RISK_MEDIUM_MAX = 60;
const RISK_HIGH_MAX = 100;
const CONSECUTIVE_CRITICAL_DAYS = 2;     // Critical 연속일 기준

// 원인 감지 임계값
const START_BARRIER_MINUTES = 30;        // 시작장벽 판별 무입력 시간
const ROUTINE_VARIANCE_HOURS = 2;        // 루틴붕괴 판별 표준편차
const LAZY_BLOCKS_THRESHOLD = 3;         // 루틴붕괴 판별 지연 블록 수

// 점수 가중치
const WEIGHT_INACTIVE = 0.35;
const WEIGHT_POMODORO = 0.25;
const WEIGHT_LASTSTROKE = 0.20;
const WEIGHT_EYE_COUNT = 0.10;
const WEIGHT_CONSECUTIVE = 0.10;
```

---

## 버전 정보

- **버전**: 2.0.0
- **생성일**: 2025-12-03
- **작성자**: Claude Code
- **변경사항**: 혼합형 페르소나 시스템으로 확장 (4개 → 12개)
- **기반 문서**: agent13_learning_dropout.md, DB_REPORT.md

---

## 관련 파일

| 파일 | 설명 |
|------|------|
| Agent13PersonaEngine.php | 페르소나 엔진 메인 클래스 |
| Agent13DataContext.php | 데이터 접근 레이어 |
| api/chat.php | 채팅 API 엔드포인트 |
| test.php | 단위 테스트 |
| ../rules/rules.yaml | 규칙 정의 파일 |
