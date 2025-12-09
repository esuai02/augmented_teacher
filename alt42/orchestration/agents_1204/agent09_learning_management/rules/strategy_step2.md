# Agent 09 - Learning Management Strategy Step 2
## 이탈 예방 및 개인화 루틴 설계 전략

**목적**: 이탈 예방, 수학 특화 관리, 개인화 루틴 설계 및 개선 피드백 루프

---

## 1. 이탈 예방 및 개입 전략

### 1.1 이탈 징후 탐지 지표

#### 연속 로그 공백
```
consecutive_log_gaps = count(consecutive_days_without_log)
- 3일 연속: 주의
- 7일 연속: 경고
- 14일 연속: 긴급
```

#### 포모도로 미작성
```
pomodoro_non_completion_rate = 
  (incomplete_sessions / total_sessions) * 100
- 30% 이상: 주의
- 50% 이상: 경고
- 70% 이상: 긴급
```

#### 목표 포기
```
goal_abandonment_rate = 
  (abandoned_goals / total_goals) * 100
- 20% 이상: 주의
- 40% 이상: 경고
- 60% 이상: 긴급
```

#### 활동 데이터 희박도
```
activity_sparsity = 1 - (activity_days / analysis_days)
- 0.5 이상: 주의
- 0.7 이상: 경고
- 0.9 이상: 긴급
```

### 1.2 이탈 위험도 점수 계산

```
dropout_risk_score = (
  consecutive_log_gaps_weight * 0.3 +
  pomodoro_non_completion_weight * 0.25 +
  goal_abandonment_weight * 0.25 +
  activity_sparsity_weight * 0.2
)

위험도 등급:
- 낮음: risk_score < 0.3
- 주의: 0.3 <= risk_score < 0.5
- 경고: 0.5 <= risk_score < 0.7
- 긴급: risk_score >= 0.7
```

### 1.3 단계별 개입 전략

#### 주의 단계 (1단계) 개입
**전략**:
- 격려 메시지 발송
- 쉬운 목표 제시
- 성취 경험 제공
- 학습 동기 유지

**메시지 예시**:
- "최근 학습 패턴이 조금 불안정해 보입니다. 작은 목표부터 다시 시작해보세요."
- "지금까지의 노력을 인정합니다. 쉬운 성취부터 경험해보세요."

#### 경고 단계 (2단계) 개입
**전략**:
- 개별 상담 권장
- 루틴 조정 제안
- 원인 분석 요청
- 선생님 알림

**메시지 예시**:
- "학습 참여가 감소하고 있습니다. 개별 상담을 통해 원인을 파악해보세요."
- "현재 루틴이 부담스러울 수 있습니다. 더 현실적인 계획으로 조정해보세요."

#### 긴급 단계 (3단계) 개입
**전략**:
- 긴급 상담 필수
- 재진입 경로 설계
- 쉬운 목표부터 시작
- 성취 경험 단계적 제공
- 관리자 에스컬레이션

**메시지 예시**:
- "학습 참여가 중단되었습니다. 즉시 상담이 필요합니다."
- "재진입을 위한 단계별 계획을 제시합니다. 작은 성취부터 시작하세요."

### 1.4 이탈 원인 분석 (다차원)

#### 학습 어려움 원인
- 개념 이해 어려움
- 문제 풀이 어려움
- 학습 속도 부적합
- 난이도 불일치

#### 동기 저하 원인
- 목표 불명확
- 성취감 부족
- 피드백 부족
- 학습 의미 상실

#### 시간 부족 원인
- 일정 과다
- 우선순위 미설정
- 시간 관리 실패
- 외부 요인 (과외, 학원 등)

#### 환경 변화 원인
- 가정 환경 변화
- 학교 환경 변화
- 친구 관계 변화
- 건강 문제

#### 원인 우선순위 평가
```
cause_priority_score = (
  impact_score * 0.4 +
  frequency_score * 0.3 +
  controllability_score * 0.3
)
```

### 1.5 재진입 유도 전략

#### 단계별 재진입 경로
1. **1단계: 접촉 및 동기 확인** (1-3일)
   - 격려 메시지
   - 쉬운 질문
   - 관심 확인

2. **2단계: 쉬운 목표 설정** (4-7일)
   - 매우 쉬운 목표 제시
   - 성취 경험 제공
   - 동기 재활성화

3. **3단계: 점진적 참여 증가** (8-14일)
   - 목표 난이도 점진적 증가
   - 학습 시간 점진적 증가
   - 루틴 재구축

4. **4단계: 정상화** (15일 이후)
   - 정상 루틴 복귀
   - 지속성 모니터링
   - 개선 피드백 루프

---

## 2. 수학 교과 특화 관리 전략

### 2.1 수학 단원별 오류 분석

#### 단원별 오류 분포 분석
```
unit_error_distribution = {
  unit_name: error_count / total_errors
}
```

#### 개념별 오류 매핑
```
concept_error_mapping = {
  concept_name: {
    error_count: int,
    error_types: [concept_misunderstanding, calculation_error, ...],
    mastery_status: "weak" | "moderate" | "strong"
  }
}
```

#### 취약 단원/개념 식별
```
weak_units = units WHERE error_rate > threshold
weak_concepts = concepts WHERE mastery_status == "weak"
```

#### 단원별 복습 우선순위
```
review_priority = (
  error_rate * 0.4 +
  unit_importance * 0.3 +
  prerequisite_dependency * 0.3
)
```

### 2.2 수학 학습 루틴 특화

#### 수학 학습 단계 순서
1. **개념 이해** (Concept Understanding)
   - 교과서 개념 읽기
   - 개념 설명 듣기
   - 개념 정리하기

2. **유형 연습** (Type Practice)
   - 기본 유형 문제
   - 유형별 연습
   - 유형 정리

3. **심화 문제** (Advanced Problems)
   - 변형 문제
   - 창의 문제
   - 종합 문제

#### 단원별 학습 시간 산정

**하위권 학생**:
```
concept_time = unit_pages * 0.5 hours
type_time = unit_pages * 0.8 hours
advanced_time = unit_pages * 0.3 hours
total_time = concept_time + type_time + advanced_time
```

**중위권 학생**:
```
concept_time = unit_pages * 0.3 hours
type_time = unit_pages * 0.6 hours
advanced_time = unit_pages * 0.4 hours
total_time = concept_time + type_time + advanced_time
```

**상위권 학생**:
```
concept_time = unit_pages * 0.2 hours
type_time = unit_pages * 0.4 hours
advanced_time = unit_pages * 0.8 hours
total_time = concept_time + type_time + advanced_time
```

#### 수학 복습 주기

**개념 복습 주기**:
- 1일 후: 즉시 복습
- 3일 후: 단기 복습
- 7일 후: 중기 복습
- 14일 후: 장기 복습

**유형 복습 주기**:
- 3일 후: 단기 복습
- 7일 후: 중기 복습
- 14일 후: 장기 복습
- 30일 후: 총정리 복습

### 2.3 수학 학습 효율성 지표

#### 개념 마스터링 속도
```
concept_mastery_speed = days_to_master / concept_complexity
- 하위권: 평균 7일/개념
- 중위권: 평균 4일/개념
- 상위권: 평균 2일/개념
```

#### 문제 풀이 속도
```
problem_solving_speed = time_per_problem / problem_difficulty
- 기본형: 평균 5분/문제
- 유형: 평균 10분/문제
- 심화: 평균 20분/문제
```

#### 오류 감소율
```
error_reduction_rate = 
  (initial_error_rate - current_error_rate) / initial_error_rate * 100
- 목표: 주당 10% 이상 감소
```

#### 수학 학습 효율성 점수
```
math_efficiency_score = (
  concept_mastery_speed_score * 0.3 +
  problem_solving_speed_score * 0.3 +
  error_reduction_rate_score * 0.4
)
```

### 2.4 수학 학습 습관 평가

#### 풀이 과정 기록 여부
- 풀이 과정 상세 기록: 점수 높음
- 풀이 과정 간략 기록: 점수 보통
- 답만 기록: 점수 낮음

#### 개념 정리 패턴
- 체계적 개념 정리: 점수 높음
- 부분적 개념 정리: 점수 보통
- 개념 정리 없음: 점수 낮음

#### 오답 분석 깊이
- 원인 분석 + 해결책: 점수 높음
- 원인 분석만: 점수 보통
- 분석 없음: 점수 낮음

#### 수학 노트 품질
- 체계적 정리 + 시각화: 점수 높음
- 기본 정리: 점수 보통
- 정리 미흡: 점수 낮음

---

## 3. 개인화 루틴 설계 전략

### 3.1 개인 특성 분석

#### 학습 스타일 선호도
- 시각형: 그래프, 다이어그램 선호
- 청각형: 음성 설명, 토론 선호
- 독서형: 텍스트 기반 학습 선호
- 운동형: 실습, 조작 선호

#### 최적 학습 시간대 식별
```
optimal_time_slots = time_slots WHERE 
  pomodoro_completion_rate > 0.8 AND
  test_performance > average
```

#### 목표 선호도 패턴
- 단기 목표 선호: 1주일 이내
- 중기 목표 선호: 2-4주일
- 장기 목표 선호: 1개월 이상

#### 집중 지속 시간 용량
```
focus_duration_capacity = 
  average_completed_pomodoro_duration
- 단기 집중: 15-20분
- 중기 집중: 25-30분
- 장기 집중: 45분 이상
```

### 3.2 개인화 루틴 생성

#### 일일 루틴 템플릿
```
daily_routine = {
  morning_slot: {
    time: "06:00-09:00",
    activity: "경미한 학습 활동",
    duration: focus_duration_capacity * 0.5
  },
  peak_slot: {
    time: optimal_time_slots[0],
    activity: "핵심 학습 활동",
    duration: focus_duration_capacity
  },
  afternoon_slot: {
    time: "15:00-18:00",
    activity: "보조 학습 활동",
    duration: focus_duration_capacity * 0.7
  },
  evening_slot: {
    time: "19:00-22:00",
    activity: "복습 활동",
    duration: focus_duration_capacity * 0.5
  }
}
```

#### 주간 루틴 템플릿
```
weekly_routine = {
  monday: "주간 목표 설정",
  tuesday_thursday: "핵심 학습",
  wednesday: "중간 점검",
  friday: "주간 리뷰",
  weekend: "보완 학습 및 휴식"
}
```

### 3.3 루틴 조정 시점 판단

#### 패턴 안정도 평가
```
pattern_stability = 1 - (weekly_variance / mean)
- 안정적: stability > 0.8 (루틴 고정)
- 불안정: stability < 0.6 (계속 조정)
```

#### 루틴 효과성 계산
```
routine_effectiveness = (
  execution_rate * 0.4 +
  outcome_improvement * 0.4 +
  student_satisfaction * 0.2
)
```

#### 조정 필요성 판단
```
IF pattern_stability < 0.6 OR 
   routine_effectiveness < 0.7 THEN
  adjustment_needed = true
```

#### 최적 조정 주기
- 불안정 패턴: 주 1회 조정
- 보통 패턴: 격주 1회 조정
- 안정 패턴: 월 1회 조정

### 3.4 루틴 실행 가능성 검증

#### 일일 시간 예산 계산
```
daily_time_budget = (
  24 hours - 
  sleep_time (8 hours) -
  school_time (6 hours) -
  meal_time (2 hours) -
  rest_time (2 hours)
) = 6 hours
```

#### 루틴 시간 요구량 계산
```
routine_time_requirement = 
  sum(all_activity_durations)
```

#### 과부하 방지 확인
```
IF routine_time_requirement > daily_time_budget * 0.8 THEN
  overload = true
  adjust_routine()  # 루틴 조정
```

---

## 4. 개선 피드백 루프 전략

### 4.1 전략 실행 추적

#### 전략 실행률 추적
```
execution_rate = 
  (actual_executed_days / planned_days) * 100
```

#### 성과 지표 측정
- 학습 시간 증가율
- 목표 달성률 향상
- 시험 점수 향상
- 오답 감소율

#### 기준선 vs 현재 비교
```
improvement_rate = 
  (current_score - baseline_score) / baseline_score * 100
```

### 4.2 효과성 평가

#### 효과성 점수 계산
```
effectiveness_score = (
  execution_rate * 0.3 +
  outcome_improvement * 0.4 +
  student_satisfaction * 0.3
)
```

#### 효과성 등급
- 우수: effectiveness_score >= 0.8
- 양호: 0.7 <= effectiveness_score < 0.8
- 보통: 0.6 <= effectiveness_score < 0.7
- 미흡: effectiveness_score < 0.6

### 4.3 개선 피드백 루프

#### 실패 원인 분석
- 실행률 낮음: 루틴 현실성 부족
- 효과성 낮음: 전략 부적합
- 만족도 낮음: 개인 선호도 미반영

#### 개선된 전략 생성
```
improved_strategy = adjust_strategy_based_on(
  failure_reasons,
  student_feedback,
  teacher_observations
)
```

#### 개선 사이클 추적
- 1주일 단위: 단기 효과 확인
- 2주일 단위: 중기 효과 확인
- 1개월 단위: 장기 효과 확인

### 4.4 정기 리뷰 일정

#### 주간 리뷰 (매주)
- 실행률 확인
- 단기 성과 평가
- 미세 조정

#### 격주 리뷰 (2주마다)
- 중기 성과 평가
- 전략 조정
- 피드백 수집

#### 월간 리뷰 (매월)
- 종합 성과 평가
- 전략 재설계
- 장기 목표 재검토

---

## 5. 학생 수준별 차별화 전략

### 5.1 하위권 학생 전략

#### 학습 방법
- 개념 중심 학습 (60%)
- 유형 연습 (30%)
- 기출 문제 (10%)

#### 학습 시간
- 짧은 시간 자주 (20-25분 × 3-4회/일)

#### 목표 설정
- 단기 목표 중심 (1주일 이내)
- 쉬운 목표부터 시작

#### 피드백 강도
- 높은 빈도 피드백
- 격려 중심

### 5.2 중위권 학생 전략

#### 학습 방법
- 개념 (30%)
- 유형 연습 (50%)
- 심화 문제 (10%)
- 기출 문제 (10%)

#### 학습 시간
- 중간 시간 중간 빈도 (30-40분 × 2-3회/일)

#### 목표 설정
- 중기 목표 중심 (2-4주일)
- 적정 난이도 목표

#### 피드백 강도
- 중간 빈도 피드백
- 균형잡힌 피드백

### 5.3 상위권 학생 전략

#### 학습 방법
- 개념 (10%)
- 유형 연습 (30%)
- 심화 문제 (40%)
- 기출 문제 (20%)

#### 학습 시간
- 긴 시간 집중 (45-60분 × 1-2회/일)

#### 목표 설정
- 장기 목표 중심 (1개월 이상)
- 도전적 목표

#### 피드백 강도
- 낮은 빈도 피드백
- 인사이트 중심

### 5.4 수준 변화 감지 및 전략 조정

#### 수준 변화 감지
```
IF current_level != previous_level AND
   consistency_period >= 2_weeks THEN
  level_change_confirmed = true
```

#### 전략 자동 조정
```
IF level_change_confirmed THEN
  adjust_strategy_for_new_level()
  update_learning_routine()
```

---

**작성일**: 2025-01-27  
**목표**: 현직 수학선생님 수준의 90% 달성

