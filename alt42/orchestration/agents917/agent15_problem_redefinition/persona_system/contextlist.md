# Agent15 Problem Redefinition - Context List

## 개요

이 문서는 Agent15 문제 재정의 시스템에서 의사결정에 사용되는 모든 컨텍스트 변수를 정의합니다.
각 변수는 DataContext.php에서 수집되어 룰 엔진과 페르소나 시스템에서 활용됩니다.

---

## 1. 학생 프로필 컨텍스트 (Student Profile)

### 1.1 기본 정보
| 변수명 | 타입 | 설명 | 소스 테이블 |
|--------|------|------|-------------|
| `student.id` | int | 학생 고유 ID | mdl_user |
| `student.name` | string | 학생 이름 | mdl_user |
| `student.grade` | int | 학년 | mdl_user_info_data |
| `student.enrollment_date` | datetime | 등록일 | mdl_user |

### 1.2 학습 수준
| 변수명 | 타입 | 설명 | 계산 방식 |
|--------|------|------|-----------|
| `student.level` | enum | 학습 수준 (beginner/intermediate/advanced) | 최근 성과 기반 |
| `student.mastery_score` | float | 전체 마스터리 점수 (0-100) | 최근 30일 평균 |
| `student.learning_velocity` | float | 학습 속도 지표 | 주간 진도 비율 |

### 1.3 학생 특성 (Characteristics)
| 변수명 | 타입 | 설명 | 판단 기준 |
|--------|------|------|-----------|
| `student.characteristic` | enum | 주요 특성 | 행동 패턴 분석 |

**특성 값:**
- `avoidant` (회피형): 도전 회피, 쉬운 문제 선호
- `defensive` (방어형): 피드백 거부 경향, 자기합리화
- `anxious` (불안형): 과도한 확인, 높은 스트레스
- `confident` (자신감형): 적극적 참여, 도전 수용

---

## 2. 학습 성과 컨텍스트 (Performance)

### 2.1 성과 지표
| 변수명 | 타입 | 설명 | 계산 방식 |
|--------|------|------|-----------|
| `performance.current_score` | float | 현재 성과 점수 | 최근 7일 평균 |
| `performance.previous_score` | float | 이전 성과 점수 | 8-14일 전 평균 |
| `performance.trend` | enum | 성과 추이 (improving/stable/declining) | 비교 분석 |
| `performance.change_rate` | float | 변화율 (%) | (current-previous)/previous*100 |

### 2.2 성과 세부 분석
| 변수명 | 타입 | 설명 | 임계값 |
|--------|------|------|--------|
| `performance.accuracy_rate` | float | 정답률 (0-1) | < 0.6 = 경고 |
| `performance.completion_rate` | float | 과제 완료율 (0-1) | < 0.7 = 경고 |
| `performance.time_efficiency` | float | 시간 효율성 | < 0.5 = 비효율 |

### 2.3 트리거 조건 (S1: 학습 성과 하락)
```yaml
trigger_s1:
  condition: performance.change_rate < -15
  severity:
    mild: -15 ~ -25%
    moderate: -25 ~ -40%
    severe: < -40%
```

---

## 3. 학습 이탈 컨텍스트 (Dropout)

### 3.1 이탈 위험 지표
| 변수명 | 타입 | 설명 | 임계값 |
|--------|------|------|--------|
| `dropout.risk_score` | float | 이탈 위험 점수 (0-1) | > 0.7 = 고위험 |
| `dropout.inactive_days` | int | 비활성 일수 | > 7일 = 경고 |
| `dropout.engagement_score` | float | 참여도 점수 (0-1) | < 0.3 = 저참여 |
| `dropout.warning_level` | int | 경고 단계 (1-5) | - |

### 3.2 이탈 이벤트
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `dropout.last_activity` | datetime | 마지막 활동 시간 |
| `dropout.session_frequency` | float | 주간 세션 빈도 |
| `dropout.session_duration_avg` | int | 평균 세션 길이 (분) |

### 3.3 트리거 조건 (S2: 학습 이탈 경고)
```yaml
trigger_s2:
  condition: dropout.risk_score > 0.6 OR dropout.inactive_days > 5
  severity:
    mild: risk_score 0.6-0.7 OR inactive 5-7일
    moderate: risk_score 0.7-0.85 OR inactive 8-14일
    severe: risk_score > 0.85 OR inactive > 14일
```

---

## 4. 오답 패턴 컨텍스트 (Error Patterns)

### 4.1 오답 분석
| 변수명 | 타입 | 설명 | 계산 방식 |
|--------|------|------|-----------|
| `errors.total_count` | int | 총 오답 수 (최근 30일) | 집계 |
| `errors.repeated_count` | int | 반복 오답 수 | 동일 문제 2회 이상 |
| `errors.repeat_ratio` | float | 반복 오답 비율 | repeated/total |
| `errors.concept_errors` | array | 개념별 오답 통계 | 분류별 집계 |

### 4.2 오답 유형
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `errors.type_distribution` | object | 오답 유형 분포 |
| - `careless` | float | 단순 실수 비율 |
| - `conceptual` | float | 개념 오류 비율 |
| - `procedural` | float | 절차 오류 비율 |
| - `calculation` | float | 계산 실수 비율 |

### 4.3 트리거 조건 (S3: 동일 오답 반복)
```yaml
trigger_s3:
  condition: errors.repeat_ratio > 0.3 OR specific_error_count > 3
  severity:
    mild: repeat_ratio 0.3-0.4 OR count 3-4
    moderate: repeat_ratio 0.4-0.5 OR count 5-6
    severe: repeat_ratio > 0.5 OR count > 6
```

---

## 5. 학습 루틴 컨텍스트 (Study Routine)

### 5.1 루틴 지표
| 변수명 | 타입 | 설명 | 판단 기준 |
|--------|------|------|-----------|
| `routine.consistency_score` | float | 일관성 점수 (0-1) | 패턴 분석 |
| `routine.preferred_time` | string | 선호 학습 시간대 | 빈도 분석 |
| `routine.avg_duration` | int | 평균 학습 시간 (분) | - |
| `routine.weekly_sessions` | int | 주간 학습 횟수 | - |

### 5.2 루틴 변화
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `routine.stability` | enum | 안정성 (stable/unstable/erratic) |
| `routine.change_detected` | bool | 급격한 변화 감지 |
| `routine.variance` | float | 학습 패턴 분산 |

### 5.3 트리거 조건 (S4: 루틴 불안정)
```yaml
trigger_s4:
  condition: routine.consistency_score < 0.5 OR routine.stability == 'erratic'
  severity:
    mild: consistency 0.4-0.5
    moderate: consistency 0.25-0.4
    severe: consistency < 0.25
```

---

## 6. 시간 관리 컨텍스트 (Time Management)

### 6.1 시간 효율성
| 변수명 | 타입 | 설명 | 계산 방식 |
|--------|------|------|-----------|
| `time.planned_hours` | float | 계획된 학습 시간 | 설정값 |
| `time.actual_hours` | float | 실제 학습 시간 | 측정값 |
| `time.efficiency_ratio` | float | 효율성 비율 | actual/planned |
| `time.deadline_miss_count` | int | 마감 미준수 횟수 | 최근 30일 |

### 6.2 시간 배분
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `time.focus_duration` | int | 집중 시간 (분) |
| `time.break_frequency` | float | 휴식 빈도 |
| `time.distraction_events` | int | 산만함 이벤트 수 |

### 6.3 트리거 조건 (S5: 시간관리 실패)
```yaml
trigger_s5:
  condition: time.efficiency_ratio < 0.6 OR time.deadline_miss_count > 3
  severity:
    mild: efficiency 0.5-0.6 OR miss 3-4
    moderate: efficiency 0.35-0.5 OR miss 5-7
    severe: efficiency < 0.35 OR miss > 7
```

---

## 7. 감정/동기 컨텍스트 (Emotion & Motivation)

### 7.1 감정 상태
| 변수명 | 타입 | 설명 | 값 범위 |
|--------|------|------|---------|
| `emotion.current` | enum | 현재 감정 | 아래 참조 |
| `emotion.intensity` | float | 감정 강도 (0-1) | - |
| `emotion.trend` | enum | 감정 추이 | improving/stable/declining |

**감정 값:**
- `frustration` (좌절감)
- `anxiety` (불안)
- `confusion` (혼란)
- `boredom` (지루함)
- `hopelessness` (무력감)
- `motivation` (동기 부여됨)
- `confidence` (자신감)
- `neutral` (중립)

### 7.2 동기 지표
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `motivation.level` | float | 동기 수준 (0-1) |
| `motivation.self_efficacy` | float | 자기효능감 (0-1) |
| `motivation.goal_clarity` | float | 목표 명확성 (0-1) |
| `motivation.intrinsic_score` | float | 내재적 동기 점수 |

### 7.3 트리거 조건 (S6: 정서/동기 저하)
```yaml
trigger_s6:
  condition: motivation.level < 0.4 OR emotion.current IN ['frustration', 'hopelessness']
  severity:
    mild: motivation 0.35-0.4 OR mild_negative_emotion
    moderate: motivation 0.2-0.35 OR moderate_negative_emotion
    severe: motivation < 0.2 OR strong_hopelessness
```

---

## 8. 개념 이해 컨텍스트 (Concept Understanding)

### 8.1 이해도 지표
| 변수명 | 타입 | 설명 | 계산 방식 |
|--------|------|------|-----------|
| `concept.mastery_map` | object | 개념별 마스터리 맵 | 개념ID: 점수 |
| `concept.weak_areas` | array | 취약 개념 목록 | 점수 < 0.5 |
| `concept.prerequisite_gaps` | array | 선수 학습 부족 영역 | 분석 결과 |
| `concept.overall_understanding` | float | 전체 이해도 (0-1) | 가중 평균 |

### 8.2 학습 진도
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `concept.current_topic` | string | 현재 학습 주제 |
| `concept.progress_rate` | float | 진도율 (0-1) |
| `concept.stuck_count` | int | 정체 횟수 |

### 8.3 트리거 조건 (S7: 개념 이해 부진)
```yaml
trigger_s7:
  condition: concept.overall_understanding < 0.5 OR concept.weak_areas.length > 3
  severity:
    mild: understanding 0.4-0.5 OR weak_areas 3-4
    moderate: understanding 0.3-0.4 OR weak_areas 5-6
    severe: understanding < 0.3 OR weak_areas > 6
```

---

## 9. 교사 피드백 컨텍스트 (Teacher Feedback)

### 9.1 피드백 정보
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `feedback.recent` | array | 최근 피드백 목록 |
| `feedback.warning_count` | int | 경고성 피드백 수 |
| `feedback.concern_areas` | array | 우려 영역 |
| `feedback.recommendation` | string | 교사 권고사항 |

### 9.2 피드백 분류
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `feedback.type` | enum | 피드백 유형 |
| - `encouragement` | - | 격려 |
| - `guidance` | - | 안내 |
| - `warning` | - | 경고 |
| - `intervention_request` | - | 개입 요청 |

### 9.3 트리거 조건 (S8: 교사 피드백 경고)
```yaml
trigger_s8:
  condition: feedback.warning_count > 2 OR feedback.type == 'intervention_request'
  severity:
    mild: warning_count 2-3
    moderate: warning_count 4-5 OR guidance_needed
    severe: intervention_request OR warning_count > 5
```

---

## 10. 전략 적합성 컨텍스트 (Strategy Fit)

### 10.1 전략 지표
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `strategy.current` | string | 현재 학습 전략 |
| `strategy.effectiveness` | float | 전략 효과성 (0-1) |
| `strategy.alignment_score` | float | 학습 스타일 적합도 |
| `strategy.adaptation_needed` | bool | 전략 변경 필요 여부 |

### 10.2 전략 분석
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `strategy.learning_style` | enum | 학습 스타일 (visual/auditory/kinesthetic/reading) |
| `strategy.preferred_methods` | array | 선호 학습 방법 |
| `strategy.resistance_to_change` | float | 변화 저항도 |

### 10.3 트리거 조건 (S9: 전략 불일치)
```yaml
trigger_s9:
  condition: strategy.effectiveness < 0.4 OR strategy.alignment_score < 0.5
  severity:
    mild: effectiveness 0.35-0.4 OR alignment 0.4-0.5
    moderate: effectiveness 0.25-0.35 OR alignment 0.3-0.4
    severe: effectiveness < 0.25 OR alignment < 0.3
```

---

## 11. 회복 이력 컨텍스트 (Recovery History)

### 11.1 회복 시도
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `recovery.attempt_count` | int | 총 회복 시도 횟수 |
| `recovery.success_count` | int | 성공 횟수 |
| `recovery.success_rate` | float | 성공률 |
| `recovery.last_attempt` | datetime | 마지막 시도 일시 |

### 11.2 회복 분석
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `recovery.effective_interventions` | array | 효과적이었던 개입 목록 |
| `recovery.failed_approaches` | array | 실패한 접근법 목록 |
| `recovery.resilience_score` | float | 회복탄력성 점수 (0-1) |

### 11.3 트리거 조건 (S10: 회복 실패)
```yaml
trigger_s10:
  condition: recovery.success_rate < 0.3 AND recovery.attempt_count > 3
  severity:
    mild: success_rate 0.25-0.3 AND attempts 3-4
    moderate: success_rate 0.15-0.25 AND attempts 4-5
    severe: success_rate < 0.15 OR attempts > 5
```

---

## 12. 세션 컨텍스트 (Session)

### 12.1 현재 세션
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `session.id` | string | 세션 ID |
| `session.start_time` | datetime | 세션 시작 시간 |
| `session.duration` | int | 경과 시간 (분) |
| `session.message_count` | int | 메시지 수 |

### 12.2 대화 이력
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `session.history` | array | 대화 이력 |
| `session.current_topic` | string | 현재 대화 주제 |
| `session.intent_history` | array | 의도 분석 이력 |

---

## 13. 페르소나 컨텍스트 (Persona)

### 13.1 현재 상태
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `persona.current_id` | string | 현재 페르소나 ID |
| `persona.series` | enum | 페르소나 시리즈 (R/A/V/S/E) |
| `persona.confidence` | float | 선택 신뢰도 |
| `persona.active_since` | datetime | 활성화 시간 |

### 13.2 전환 이력
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `persona.previous_id` | string | 이전 페르소나 ID |
| `persona.transition_count` | int | 전환 횟수 (세션 내) |
| `persona.transition_reason` | string | 전환 사유 |

---

## 14. 트리거 시나리오 컨텍스트 (Trigger Scenario)

### 14.1 현재 트리거
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `trigger.active` | string | 활성 트리거 ID (S1-S10) |
| `trigger.severity` | enum | 심각도 (mild/moderate/severe) |
| `trigger.detected_at` | datetime | 감지 시간 |
| `trigger.confidence` | float | 감지 신뢰도 |

### 14.2 트리거 이력
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `trigger.history` | array | 트리거 이력 (최근 30일) |
| `trigger.frequency` | object | 트리거별 발생 빈도 |
| `trigger.recurring` | array | 반복 발생 트리거 |

---

## 15. 원인 분석 컨텍스트 (Cause Analysis)

### 15.1 4계층 원인 분석
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `cause.cognitive` | object | 인지적 요인 분석 |
| `cause.behavioral` | object | 행동적 요인 분석 |
| `cause.motivational` | object | 동기적 요인 분석 |
| `cause.environmental` | object | 환경적 요인 분석 |

### 15.2 분석 결과
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `cause.primary` | string | 주요 원인 |
| `cause.secondary` | array | 부차적 원인 목록 |
| `cause.confidence` | float | 분석 신뢰도 |
| `cause.redefined_problem` | string | 재정의된 문제 설명 |

---

## 16. 조치 컨텍스트 (Action)

### 16.1 현재 조치
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `action.current` | object | 현재 실행 중인 조치 |
| `action.status` | enum | 상태 (pending/active/completed/failed) |
| `action.progress` | float | 진행률 (0-1) |

### 16.2 조치 이력
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `action.history` | array | 조치 이력 |
| `action.effective_actions` | array | 효과적 조치 목록 |
| `action.pending_follow_ups` | array | 대기 중인 후속 조치 |

---

## 17. 에이전트 간 통신 컨텍스트 (Inter-Agent)

### 17.1 수신 메시지
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `interagent.received` | array | 다른 에이전트로부터 수신한 메시지 |
| `interagent.pending` | array | 처리 대기 중인 메시지 |

### 17.2 발신 정보
| 변수명 | 타입 | 설명 |
|--------|------|------|
| `interagent.sent` | array | 발신한 메시지 이력 |
| `interagent.coordination_status` | object | 에이전트 간 협력 상태 |

---

## 컨텍스트 우선순위

룰 평가 시 컨텍스트 로딩 우선순위:

1. **Critical (즉시 로드)**
   - trigger.*
   - emotion.current
   - session.*

2. **High (항상 로드)**
   - student.* (기본 정보)
   - performance.*
   - persona.*

3. **Medium (조건부 로드)**
   - errors.* (S3 트리거 시)
   - routine.* (S4 트리거 시)
   - time.* (S5 트리거 시)
   - concept.* (S7 트리거 시)

4. **Low (필요 시 로드)**
   - recovery.*
   - interagent.*
   - action.history

---

## 데이터 소스 매핑

### Moodle DB 테이블
| 컨텍스트 그룹 | 소스 테이블 |
|--------------|-------------|
| student.* | mdl_user, mdl_user_info_data |
| performance.* | mdl_grade_grades, 커스텀 집계 |
| errors.* | 커스텀 로그 테이블 |
| session.* | mdl_sessions, 커스텀 |

### Agent15 전용 테이블
| 테이블명 | 용도 |
|---------|------|
| at_agent_persona_state | persona.*, trigger.* |
| at_persona_transition_log | persona.transition_* |
| at_cause_analysis_log | cause.* |
| at_agent_action_log | action.* |
| at_agent_messages | interagent.* |

---

## 변경 이력

| 버전 | 날짜 | 변경 내용 |
|------|------|----------|
| 1.0 | 2025-12-02 | 초기 버전 생성 |
