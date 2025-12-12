# Agent20 Context List - 컨텍스트 변수 정의

## 개요
Agent20이 개입 준비를 위해 사용하는 모든 컨텍스트 변수를 정의합니다.

---

## 1. 학생 기본 정보 (Student Info)

| 변수명 | 타입 | 설명 | 소스 |
|--------|------|------|------|
| `user_id` | int | 사용자 고유 ID | mdl_user |
| `firstname` | string | 이름 | mdl_user |
| `lastname` | string | 성 | mdl_user |
| `grade_level` | int | 학년 | mdl_user_info_data |
| `learning_style` | string | 학습 스타일 | 분석 결과 |

---

## 2. 감정 상태 (Emotional State)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `emotion` | string | 현재 감정 상태 | neutral, anxiety, frustration, confusion, joy, curiosity, boredom |
| `emotion_intensity` | float | 감정 강도 | 0.0 ~ 1.0 |
| `emotion_duration` | int | 감정 지속 시간(초) | 0 ~ ∞ |
| `emotion_trend` | string | 감정 변화 추세 | improving, stable, declining |

---

## 3. 인지 상태 (Cognitive State)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `cognitive_load` | float | 인지 부하 | 0.0 ~ 1.0 |
| `confusion_level` | float | 혼란 수준 | 0.0 ~ 1.0 |
| `comprehension` | float | 이해도 | 0.0 ~ 1.0 |
| `focus_level` | float | 집중도 | 0.0 ~ 1.0 |

---

## 4. 행동 지표 (Behavioral Metrics)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `engagement` | float | 참여도 | 0.0 ~ 1.0 |
| `time_on_task` | int | 현재 태스크 소요 시간(초) | 0 ~ ∞ |
| `response_time` | float | 평균 응답 시간(초) | 0.0 ~ ∞ |
| `error_rate` | float | 오류율 | 0.0 ~ 1.0 |
| `help_requests` | int | 도움 요청 횟수 | 0 ~ ∞ |
| `interaction_count` | int | 상호작용 횟수 | 0 ~ ∞ |

---

## 5. 학습 진행 (Learning Progress)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `current_activity` | string | 현재 활동 | quiz, lesson, practice, review |
| `current_topic` | string | 현재 학습 주제 | 자유 텍스트 |
| `progress_percent` | float | 진행률 | 0.0 ~ 100.0 |
| `performance_trend` | string | 성과 추세 | improving, stable, declining |
| `mastery_level` | float | 숙달도 | 0.0 ~ 1.0 |

---

## 6. 세션 정보 (Session Info)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `session_id` | string | 세션 고유 ID | UUID |
| `session_start` | int | 세션 시작 시간 | timestamp |
| `session_duration` | int | 세션 지속 시간(초) | 0 ~ ∞ |
| `intervention_count` | int | 이번 세션 개입 횟수 | 0 ~ 5 |
| `last_intervention` | int | 마지막 개입 시간 | timestamp |

---

## 7. 위험 신호 (Risk Signals)

| 변수명 | 타입 | 설명 | 활성화 조건 |
|--------|------|------|------------|
| `anxiety` | bool | 불안 감지 | emotion == 'anxiety' |
| `frustration` | bool | 좌절 감지 | emotion == 'frustration' |
| `confusion` | bool | 혼란 감지 | confusion_level > 0.7 |
| `low_engagement` | bool | 참여 저하 | engagement < 0.3 |
| `high_error_rate` | bool | 높은 오류율 | error_rate > 0.5 |
| `stuck` | bool | 진행 불가 | time_on_task > 300 AND progress == 0 |
| `help_needed` | bool | 도움 필요 | help_requests >= 3 |

---

## 8. 개입 관련 (Intervention Related)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `intervention_score` | float | 개입 필요성 점수 | 0.0 ~ 1.0 |
| `intervention_type` | string | 권장 개입 유형 | emotional, cognitive, motivational, behavioral |
| `target_agent` | string | 대상 에이전트 | agent17 ~ agent21 |
| `intervention_priority` | int | 개입 우선순위 | 1 ~ 10 |
| `cooldown_remaining` | int | 쿨다운 남은 시간(초) | 0 ~ 600 |

---

## 9. 에이전트 상태 (Agent State)

| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `current_persona` | string | 현재 활성 페르소나 | P20_ANALYZER, P20_STRATEGIST, P20_COORDINATOR, P20_MONITOR |
| `previous_persona` | string | 이전 페르소나 | 동일 |
| `agent_state` | string | 에이전트 상태 | idle, analyzing, preparing, coordinating, monitoring |

---

## 컨텍스트 우선순위

개입 결정 시 컨텍스트 우선순위:

1. **Critical (즉시 개입)**
   - `stuck == true`
   - `help_requests >= 5`
   - `emotion == 'frustration' AND emotion_intensity > 0.8`

2. **High (높은 우선순위)**
   - `anxiety == true`
   - `high_error_rate == true`
   - `low_engagement == true`

3. **Medium (보통 우선순위)**
   - `confusion == true`
   - `cognitive_load > 0.7`
   - `performance_trend == 'declining'`

4. **Low (낮은 우선순위)**
   - `emotion == 'boredom'`
   - `engagement < 0.5`

---

## 관련 DB 테이블

- `mdl_user` - 사용자 기본 정보
- `mdl_user_info_data` - 사용자 추가 정보
- `mdl_at_persona_session` - 페르소나 세션
- `mdl_at_persona_events` - 이벤트 기록
