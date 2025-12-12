# Agent17 컨텍스트 목록

## 개요

Agent17(잔여 활동 조정)의 페르소나 식별 및 응답 생성에 사용되는 컨텍스트 변수 목록입니다.

---

## 1. 사용자 기본 정보

| 변수명 | 타입 | 설명 | 예시 값 |
|--------|------|------|---------|
| `user_id` | int | 사용자 ID | 12345 |
| `user_name` | string | 사용자 이름 | "김학생" |
| `grade_level` | int | 학년 | 3 |
| `class_id` | int | 반 ID | 201 |

---

## 2. 학습 진행 상태

| 변수명 | 타입 | 설명 | 범위/예시 |
|--------|------|------|-----------|
| `completion_rate` | float | 활동 완료율 | 0.0 ~ 100.0 |
| `on_time_rate` | float | 정시 완료율 | 0.0 ~ 100.0 |
| `attempt_count` | int | 시도 횟수 | 0 ~ ∞ |
| `consecutive_failures` | int | 연속 실패 횟수 | 0 ~ ∞ |
| `last_activity_gap_minutes` | int | 마지막 활동 후 경과 시간(분) | 0 ~ ∞ |

### 진행 상태 판단 기준

```
R1 (원활): completion_rate >= 80 AND on_time_rate >= 90
R2 (적절): completion_rate 50-80 OR on_time_rate 70-90
R3 (지연): completion_rate 30-50 OR on_time_rate 50-70 OR attempt_count > 5
R4 (정체): completion_rate < 30 OR on_time_rate < 50 OR consecutive_failures >= 3
R5 (붕괴): last_activity_gap_minutes > 60 OR consecutive_failures >= 5
```

---

## 3. 학습 스타일

| 변수명 | 타입 | 설명 | 가능 값 |
|--------|------|------|---------|
| `learning_style` | string | 학습 유형 | visual, auditory, kinesthetic, reading |
| `autonomy_preference` | string | 자율성 선호도 | low, medium, high |
| `pace_preference` | string | 학습 속도 선호 | slow, moderate, fast |
| `feedback_preference` | string | 피드백 선호 | immediate, periodic, minimal |

---

## 4. 감정 상태

| 변수명 | 타입 | 설명 | 가능 값 |
|--------|------|------|---------|
| `emotional_state` | string | 현재 감정 상태 | neutral, positive, confused, frustrated, anxious, exhausted |
| `confidence_level` | string | 자신감 수준 | low, medium, high |
| `stress_level` | string | 스트레스 수준 | low, medium, high, critical |
| `engagement_level` | string | 참여도 | disengaged, passive, active, highly_engaged |

---

## 5. 메시지 분석

| 변수명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| `user_message` | string | 원본 메시지 | "이거 어떻게 해요?" |
| `intent` | string | 감지된 의도 | help_request, progress_report, frustration_expression, general |
| `message_length` | int | 메시지 길이 | 15 |
| `has_question` | bool | 질문 포함 여부 | true |

### 의도 감지 키워드

| 의도 | 감지 키워드 |
|------|------------|
| help_request | 도와, 모르겠, 어떻게, 힘들, 어려워 |
| frustration_expression | 포기, 그만, 싫어, 못하겠, 짜증 |
| progress_report | 했어요, 완료, 끝났, 다음 |
| general | (기본값) |

---

## 6. 잔여 활동 정보

| 변수명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| `total_remaining` | int | 남은 활동 총 수 | 5 |
| `overdue_count` | int | 기한 초과 활동 수 | 2 |
| `due_soon` | int | 7일 내 마감 활동 수 | 3 |
| `avg_time_needed` | float | 평균 필요 시간(분) | 30.5 |
| `urgency_level` | string | 긴급도 레벨 | low, medium, high, critical |

### 긴급도 레벨 계산

```
critical: overdue_ratio >= 0.5
high: overdue_ratio >= 0.3 OR due_soon > 5
medium: overdue_ratio >= 0.1 OR due_soon > 2
low: 기본값
```

---

## 7. 세션 정보

| 변수명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| `session_id` | string | 세션 식별자 | "sess_abc123" |
| `session_start_time` | datetime | 세션 시작 시간 | "2025-12-02 10:30:00" |
| `session_duration_minutes` | int | 세션 지속 시간(분) | 45 |
| `interaction_count` | int | 상호작용 횟수 | 12 |
| `course_id` | int | 현재 과목 ID | 101 |
| `current_activity_id` | int | 현재 활동 ID | 456 |

---

## 8. 페르소나 식별 결과

| 변수명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| `situation` | string | 결정된 상황 코드 | "R3" |
| `persona_id` | string | 선택된 페르소나 ID | "R3_P1" |
| `persona_name` | string | 페르소나 이름 | "인내심 있는 멘토" |
| `confidence` | float | 식별 신뢰도 | 0.85 |
| `matched_rule` | string | 매칭된 규칙 ID | "rule_003" |
| `tone` | string | 응답 톤 | "Supportive" |
| `intervention` | string | 개입 유형 | "Demonstration" |

---

## 9. 전략 관련

| 변수명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| `selected_strategy` | string | 선택된 전략 코드 | "ST2" |
| `strategy_name` | string | 전략 이름 | "도제학습_전환" |
| `recommended_actions` | array | 권장 액션 목록 | ["demonstrate_process", "provide_example"] |

---

## 10. 응답 생성

| 변수명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| `template_key` | string | 사용된 템플릿 키 | "R3_default" |
| `response_text` | string | 생성된 응답 텍스트 | "함께 천천히 해볼게요..." |
| `processing_time_ms` | float | 처리 시간(ms) | 125.5 |

---

## 컨텍스트 흐름도

```
[사용자 메시지 입력]
        ↓
[1. 사용자 기본 정보 로드]
        ↓
[2. 학습 진행 상태 조회]
        ↓
[3. 메시지 분석 (의도/감정)]
        ↓
[4. 상황 코드 결정 (R1-R5)]
        ↓
[5. 전략 선택 (ST1-ST5)]
        ↓
[6. 페르소나 식별]
        ↓
[7. 응답 생성]
        ↓
[8. 컨텍스트 저장]
```

---

## 버전 정보

- **버전**: 1.0
- **최종 수정**: 2025-12-02
- **관련 파일**:
  - `engine/Agent17PersonaEngine.php`
  - `persona_rules.yaml`
