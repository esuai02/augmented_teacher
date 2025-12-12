# Agent 12 - 데이터 수집 가이드

**목적**: 현직 수학선생님 수준의 휴식 루틴 분석을 위한 데이터 수집 방법 및 설문 가이드

---

## 📋 데이터 수집 체크리스트

### ✅ 현재 수집되는 데이터
- 휴식 버튼 클릭 시간 (timecreated)
- 휴식 지속 시간 (duration)
- 사용자 ID (userid)

### 🔴 추가로 수집해야 할 데이터

#### 1. 휴식 활동 유형 정보
**수집 시점**: 휴식 버튼 클릭 시 또는 휴식 종료 시

**수집 방법**: 체크리스트 또는 다중 선택
```
[ ] 눈 휴식 (1분 이상 눈 감기)
[ ] 스트레칭 (목, 어깨, 허리)
[ ] 간식
[ ] 스마트폰 사용
[ ] 산책
[ ] 음악 듣기
[ ] 명상/심호흡
[ ] 기타: ___________
```

**데이터베이스 필드**: `rest_activity_type` (JSON 배열 또는 문자열)

---

#### 2. 휴식 전후 감정 상태
**수집 시점**: 휴식 전 (휴식 버튼 클릭 시), 휴식 후 (휴식 종료 시)

**수집 방법**: 1-5점 척도 또는 이모지 선택
```
휴식 전 감정 상태: 
1(매우 좋음) ⭐⭐⭐⭐⭐ 5(매우 나쁨)
- [ ] 기쁨
- [ ] 평온
- [ ] 몰입
- [ ] 지루함
- [ ] 피로
- [ ] 짜증
- [ ] 불안

휴식 후 감정 상태:
(동일한 항목 반복)
```

**데이터베이스 필드**: 
- `emotional_state_before_rest` (JSON 객체: {emotion: "피로", score: 4})
- `emotional_state_after_rest` (JSON 객체: {emotion: "평온", score: 2})

---

#### 3. 휴식 전 학습 활동 정보
**수집 시점**: 휴식 버튼 클릭 시 자동 수집 또는 학생 입력

**수집 방법**: 자동 수집 (다른 에이전트 연계) + 선택적 학생 확인
```
휴식 전 학습 활동:
- 문제 수: [자동 계산] 문제
- 학습 시간: [자동 계산] 분
- 학습 단원: [선택 또는 자동]
  - 단원명: ___________
  - 단원 난이도: 1(쉬움) ⭐⭐⭐⭐⭐ 5(어려움)
```

**데이터베이스 필드**: 
- `problem_count_before_rest` (정수)
- `study_duration_before_rest` (정수, 분 단위)
- `unit_name_before_rest` (문자열)
- `unit_difficulty_before_rest` (1-5 정수)

**자동 수집 로직**: 
- 학습 활동 로그에서 휴식 직전 30분 이내의 문제 풀이 기록 분석
- 단원 정보는 현재 학습 중인 단원 정보와 연계

---

#### 4. 수면 패턴 정보
**수집 시점**: 일일 체크리스트 (아침 또는 저녁) 또는 주간 설문

**수집 방법**: 간단한 입력 폼
```
수면 패턴 체크:
- 어제 밤 몇 시간 잤나요? [입력] 시간
- 수면의 질은 어떠했나요?
  1(매우 좋음) ⭐⭐⭐⭐⭐ 5(매우 나쁨)
- 잠들기 어려웠나요? [예/아니오]
- 중간에 깼나요? [예/아니오]
```

**데이터베이스 필드**:
- `sleep_hours` (실수, 시간 단위)
- `sleep_quality` (1-5 정수)
- `sleep_difficulty` (불린)
- `sleep_interrupted` (불린)

**수집 주기**: 매일 또는 주 3회 이상

---

#### 5. 외부 일정 정보
**수집 시점**: 주간 설문 또는 학기 시작 시

**수집 방법**: 체크리스트 또는 텍스트 입력
```
이번 주 외부 일정:
- [ ] 학교 시험 (날짜: _______)
- [ ] 학교 행사 (내용: _______)
- [ ] 학원 특별 수업 (내용: _______)
- [ ] 기타 일정 (내용: _______)
```

**데이터베이스 필드**: `external_schedule_info` (JSON 배열)

**자동 연계**: 
- agent01_onboarding에서 학원 정보 수집
- agent02_exam_schedule에서 시험 일정 수집

---

#### 6. 신체 컨디션 정보
**수집 시점**: 일일 체크리스트 또는 주간 설문

**수집 방법**: 간단한 체크리스트
```
오늘 신체 컨디션:
- 목/어깨 통증: 1(없음) ⭐⭐⭐⭐⭐ 5(심함)
- 눈 피로: 1(없음) ⭐⭐⭐⭐⭐ 5(심함)
- 두통: 1(없음) ⭐⭐⭐⭐⭐ 5(심함)
- 허리 통증: 1(없음) ⭐⭐⭐⭐⭐ 5(심함)
```

**데이터베이스 필드**: `physical_condition` (JSON 객체: {neck_shoulder: 3, eye_fatigue: 4, headache: 2, back_pain: 1})

---

#### 7. 학습 스타일 정보
**수집 시점**: 초기 온보딩 (agent01에서 수집) 또는 추가 설문

**수집 방법**: 선택형 질문
```
수학 문제를 풀 때 어떤 방식이 편하신가요?
- (A) 계산을 정확하게 하는 것이 중요해요 (계산형)
- (B) 개념을 이해하고 연결하는 것이 중요해요 (개념형)
- (C) 다양한 문제 유형을 풀어보는 것이 중요해요 (응용형)
```

**데이터베이스 필드**: `learning_style` (문자열: "계산형", "개념형", "응용형")

**자동 연계**: agent01_onboarding에서 수집

---

#### 8. 최적 집중 시간대 정보
**수집 시점**: 초기 설문 또는 패턴 분석 기반 추론

**수집 방법**: 선택형 질문 또는 패턴 분석
```
언제 공부할 때 가장 집중이 잘 되나요?
- 오전 (6시-12시)
- 오후 (12시-18시)
- 저녁 (18시-24시)
- 특정 시간: ___________
```

**데이터베이스 필드**: `optimal_focus_time` (문자열 또는 JSON 배열)

**패턴 분석 기반 추론**: 
- 시간대별 학습 효율 데이터 수집
- 시간대별 집중도 변화 분석
- 자동으로 최적 시간대 추론

---

## 🔄 데이터 수집 우선순위

### Phase 1: 필수 데이터 (즉시 수집 필요)
1. ✅ 휴식 활동 유형 (휴식 버튼 클릭 시)
2. ✅ 휴식 전후 감정 상태 (휴식 전후)
3. ✅ 휴식 전 학습 활동 정보 (자동 수집 + 확인)

### Phase 2: 중요 데이터 (1주일 이내 수집)
4. ⚠️ 수면 패턴 정보 (일일 체크리스트)
5. ⚠️ 신체 컨디션 정보 (일일 체크리스트)

### Phase 3: 보조 데이터 (자동 연계 또는 주간 설문)
6. 📋 외부 일정 정보 (agent01, agent02 연계)
7. 📋 학습 스타일 정보 (agent01 연계)
8. 📋 최적 집중 시간대 (패턴 분석 기반 추론)

---

## 📊 데이터베이스 스키마 제안

### 새로운 테이블: `mdl_abessi_rest_routine_detail`

```sql
CREATE TABLE mdl_abessi_rest_routine_detail (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    rest_log_id BIGINT,  -- mdl_abessi_breaktimelog와 연결
    rest_activity_type TEXT,  -- JSON 배열
    emotional_state_before_rest TEXT,  -- JSON 객체
    emotional_state_after_rest TEXT,  -- JSON 객체
    problem_count_before_rest INT,
    study_duration_before_rest INT,  -- 분 단위
    unit_name_before_rest VARCHAR(255),
    unit_difficulty_before_rest INT,  -- 1-5
    learning_efficiency_before_rest DECIMAL(3,2),  -- 0.00-1.00
    learning_efficiency_after_rest DECIMAL(3,2),  -- 0.00-1.00
    recovery_effectiveness_index DECIMAL(5,2),  -- 회복 효과 지수
    timecreated BIGINT NOT NULL,
    INDEX idx_userid_timecreated (userid, timecreated),
    INDEX idx_rest_log_id (rest_log_id)
);
```

### 새로운 테이블: `mdl_abessi_daily_condition`

```sql
CREATE TABLE mdl_abessi_daily_condition (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    date_value DATE NOT NULL,  -- YYYY-MM-DD
    sleep_hours DECIMAL(3,1),  -- 시간 단위
    sleep_quality INT,  -- 1-5
    sleep_difficulty BOOLEAN,
    sleep_interrupted BOOLEAN,
    physical_condition TEXT,  -- JSON 객체
    external_schedule_info TEXT,  -- JSON 배열
    fatigue_index DECIMAL(3,1),  -- 0-10
    timecreated BIGINT NOT NULL,
    UNIQUE KEY unique_user_date (userid, date_value),
    INDEX idx_userid_date (userid, date_value)
);
```

---

## 💡 학생 친화적 수집 방법 제안

### 방법 1: 간단한 체크리스트 (추천)
- 복잡한 입력 최소화
- 이모지나 그림 사용
- 1-2분 이내 완료 가능

### 방법 2: 자동 수집 + 간단한 확인
- 가능한 한 자동으로 수집
- 학생은 간단히 확인만
- "맞나요?" 형태의 확인 질문

### 방법 3: 게이미피케이션
- 체크리스트 완료 시 포인트 적립
- 연속 기록 보상
- 개인 기록 경신 알림

---

## 🎯 데이터 수집 목표

### 최소 데이터셋 (기본 분석 가능)
- 휴식 활동 유형: 80% 이상 수집률
- 휴식 전후 감정 상태: 70% 이상 수집률
- 휴식 전 학습 활동: 자동 수집 90% 이상

### 권장 데이터셋 (고급 분석 가능)
- 수면 패턴: 주 5일 이상 수집
- 신체 컨디션: 주 3일 이상 수집
- 외부 일정: 주 1회 이상 업데이트

### 완전 데이터셋 (최적 분석 가능)
- 모든 데이터 항목 80% 이상 수집률
- 최소 30일 이상 연속 데이터
- 주간 패턴 분석 가능한 데이터량

---

**작성일**: 2025-01-27  
**다음 단계**: 데이터 수집 UI/UX 설계 및 구현

