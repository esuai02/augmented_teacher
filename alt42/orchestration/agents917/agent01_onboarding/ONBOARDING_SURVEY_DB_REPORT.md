# 온보딩 설문 영역 및 DB 정보 상세 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**버전**: 1.0

---

## 목차

1. [설문 영역 개요](#설문-영역-개요)
2. [설문 질문 상세 (16개)](#설문-질문-상세-16개)
3. [데이터베이스 구조](#데이터베이스-구조)
4. [데이터 흐름](#데이터-흐름)
5. [필드 매핑](#필드-매핑)
6. [Rules.yaml 연계](#rulesyaml-연계)

---

## 설문 영역 개요

온보딩 설문은 **3개 영역**으로 구성되어 있으며, 총 **16개 질문**을 포함합니다.

### 영역 분류

| 영역 | 질문 수 | 설명 | 점수 범위 |
|------|---------|------|-----------|
| **인지 (Cognitive)** | 6개 | 수학 학습 시 인지적 처리 방식 | 0-5 |
| **감정 (Emotional)** | 4개 | 수학에 대한 감정적 반응 및 동기 | 0-5 |
| **행동 (Behavioral)** | 6개 | 수학 학습 행동 패턴 및 습관 | 0-5 |

### 점수 계산 방식

- 각 질문: 2점 ~ 5점 (4단계 척도)
- 영역별 점수: 해당 영역 질문들의 평균
- 종합 점수: 3개 영역 점수의 평균

---

## 설문 질문 상세 (16개)

### 인지 영역 (Cognitive) - 6개 질문

#### Q1. reading (문제 읽기 방식)
- **질문**: "수학 문제를 풀 때, 문제를 어떻게 읽나요?"
- **선택지**:
  - 5점: 끝까지 꼼꼼히 여러 번 읽어요
  - 4점: 한 번은 천천히 끝까지 읽어요
  - 3점: 대충 읽고 바로 풀기 시작해요
  - 2점: 긴 문제는 읽다가 포기할 때가 많아요
- **DB 필드**: `qa01` (alt42o_learning_assessment_results)

#### Q2. conceptUnderstanding (개념 이해 방식)
- **질문**: "새로운 수학 개념을 배울 때 어떤 스타일인가요?"
- **선택지**:
  - 5점: 원리를 이해하려고 "왜?"를 계속 물어봐요
  - 4점: 예제를 통해 패턴을 찾아요
  - 3점: 공식을 외워서 문제를 풀어요
  - 2점: 이해가 안 되면 그냥 외워요
- **DB 필드**: `qa05` (alt42o_learning_assessment_results)

#### Q3. errorAnalysis (오류 분석 방식)
- **질문**: "틀린 문제를 다시 볼 때 어떻게 하나요?"
- **선택지**:
  - 5점: 왜 틀렸는지 분석하고 비슷한 문제를 더 풀어요
  - 4점: 풀이를 보고 이해하려고 노력해요
  - 3점: 답만 확인하고 넘어가요
  - 2점: 틀린 문제는 잘 안 봐요
- **DB 필드**: `qa06` (alt42o_learning_assessment_results)

#### Q4. logicalThinking (논리적 사고 방식)
- **질문**: "문제를 풀 때 어떤 방식을 선호하나요?"
- **선택지**:
  - 5점: 여러 방법으로 풀어보고 가장 좋은 걸 찾아요
  - 4점: 단계별로 차근차근 풀어나가요
  - 3점: 아는 방법 하나로만 풀어요
  - 2점: 감으로 푸는 경우가 많아요
- **DB 필드**: `qa07` (alt42o_learning_assessment_results)

#### Q5. mathExpression (수식 표현 방식)
- **질문**: "수학 풀이를 쓸 때 어떻게 하나요?"
- **선택지**:
  - 5점: 과정을 깔끔하게 정리해서 써요
  - 4점: 중요한 과정은 다 써요
  - 3점: 머릿속으로 계산하고 답만 써요
  - 2점: 풀이 과정 쓰는 게 귀찮아요
- **DB 필드**: `qa08` (alt42o_learning_assessment_results)

#### Q6. selfDirected (자기 주도성)
- **질문**: "마지막 질문이에요! 자신의 수학 실력을 어떻게 생각하나요?"
- **선택지**:
  - 5점: 내 강점과 약점을 정확히 알고 있어요
  - 4점: 대략적으로는 알고 있어요
  - 3점: 잘 모르겠어요
  - 2점: 생각해본 적이 없어요
- **DB 필드**: `qa16` (alt42o_learning_assessment_results)

---

### 감정 영역 (Emotional) - 4개 질문

#### Q7. mathAnxiety (수학 불안감)
- **질문**: "수학 시험을 앞두고 어떤 기분이 드나요?"
- **선택지**:
  - 5점: 자신 있어요! 빨리 보고 싶어요
  - 4점: 조금 긴장되지만 잘 볼 수 있을 거예요
  - 3점: 많이 떨리고 불안해요
  - 2점: 너무 무서워서 피하고 싶어요
- **DB 필드**: `qa09` (alt42o_learning_assessment_results)

#### Q8. resilience (회복력)
- **질문**: "문제를 틀렸을 때 당신의 마음은 어떤가요?"
- **선택지**:
  - 5점: 다음엔 꼭 맞춰야지! 하고 의욕이 생겨요
  - 4점: 아쉽지만 다시 도전해요
  - 3점: 속상해서 잠깐 쉬어요
  - 2점: 자신감이 떨어지고 포기하고 싶어요
- **DB 필드**: `qa10` (alt42o_learning_assessment_results)

#### Q9. motivation (동기)
- **질문**: "수학 공부를 하는 가장 큰 이유는 무엇인가요?"
- **선택지**:
  - 5점: 수학이 재미있고 더 잘하고 싶어서요
  - 4점: 원하는 진로에 필요해서요
  - 3점: 부모님이 시켜서요
  - 2점: 안 하면 혼나니까요
- **DB 필드**: `qa11` (alt42o_learning_assessment_results)

#### Q10. stressManagement (스트레스 관리)
- **질문**: "수학 공부가 스트레스일 때 어떻게 하나요?"
- **선택지**:
  - 5점: 잠깐 쉬었다가 다시 집중해요
  - 4점: 쉬운 문제부터 다시 시작해요
  - 3점: 그날은 수학 공부를 안 해요
  - 2점: 며칠씩 수학을 피해요
- **DB 필드**: `qa12` (alt42o_learning_assessment_results)

---

### 행동 영역 (Behavioral) - 6개 질문

#### Q11. persistence (지속성)
- **질문**: "어려운 문제를 만났을 때 보통 어떻게 하나요?"
- **선택지**:
  - 5점: 끝까지 붙잡고 꼭 풀어내려고 해요
  - 4점: 30분 정도는 고민해봐요
  - 3점: 10분 정도 시도하다가 답지를 봐요
  - 2점: 어려워 보이면 바로 넘겨요
- **DB 필드**: `qa02` (alt42o_learning_assessment_results)

#### Q12. questioning (질문하기)
- **질문**: "모르는 내용이 있을 때 어떻게 하나요?"
- **선택지**:
  - 5점: 바로 선생님께 질문해요
  - 4점: 정리해서 나중에 물어봐요
  - 3점: 친구한테만 물어봐요
  - 2점: 그냥 넘어가는 편이에요
- **DB 필드**: `qa03` (alt42o_learning_assessment_results)

#### Q13. timeManagement (시간 관리)
- **질문**: "하루 중 수학 공부 시간을 어떻게 관리하고 있나요?"
- **선택지**:
  - 5점: 계획표를 만들어서 규칙적으로 해요
  - 4점: 대략적인 시간은 정해두고 해요
  - 3점: 기분 내킬 때 해요
  - 2점: 시험 기간에만 몰아서 해요
- **DB 필드**: `qa04` (alt42o_learning_assessment_results)

#### Q14. studyHabits (학습 습관)
- **질문**: "평소 수학 공부 패턴은 어떤가요?"
- **선택지**:
  - 5점: 매일 정해진 시간에 꾸준히 해요
  - 4점: 일주일에 4-5일은 해요
  - 3점: 숙제 있을 때만 해요
  - 2점: 시험 전에만 벼락치기해요
- **DB 필드**: `qa13` (alt42o_learning_assessment_results)

#### Q15. concentration (집중력)
- **질문**: "수학 문제 하나를 집중해서 풀 수 있는 시간은?"
- **선택지**:
  - 5점: 1시간 이상도 가능해요
  - 4점: 30분 정도는 집중할 수 있어요
  - 3점: 15분 정도면 힘들어요
  - 2점: 5분만 지나도 딴 생각을 해요
- **DB 필드**: `qa14` (alt42o_learning_assessment_results)

#### Q16. collaboration (협업)
- **질문**: "친구들과 함께 수학 공부할 때는 어떤가요?"
- **선택지**:
  - 5점: 서로 가르치고 배우면서 함께 성장해요
  - 4점: 모르는 것만 물어보고 도움을 줘요
  - 3점: 혼자 하는 게 더 편해요
  - 2점: 같이 하면 집중이 안 돼요
- **DB 필드**: `qa15` (alt42o_learning_assessment_results)

---

## 데이터베이스 구조

### 1. 메인 온보딩 테이블: `mdl_alt42o_onboarding`

**목적**: 학생의 기본 온보딩 정보 및 수학 학습 맥락 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42o_onboarding (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    
    -- 기본 정보
    school VARCHAR(255) DEFAULT NULL COMMENT '학교명',
    birth_year INT(4) DEFAULT NULL COMMENT '출생년도',
    course_level VARCHAR(10) DEFAULT NULL COMMENT '과정: 초등/중등/고등',
    grade_detail VARCHAR(10) DEFAULT NULL COMMENT '학년: 1학년/2학년/3학년',
    
    -- 학습 진도
    concept_level VARCHAR(10) DEFAULT NULL COMMENT '개념 진도 레벨',
    concept_progress INT(2) DEFAULT NULL COMMENT '개념 진도 진행도',
    advanced_level VARCHAR(10) DEFAULT NULL COMMENT '심화 진도 레벨',
    advanced_progress INT(2) DEFAULT NULL COMMENT '심화 진도 진행도',
    learning_notes TEXT DEFAULT NULL COMMENT '학습 메모',
    
    -- 학습 스타일
    problem_preference VARCHAR(50) DEFAULT NULL COMMENT '문제풀이 선호도',
    exam_style VARCHAR(50) DEFAULT NULL COMMENT '시험 대비 성향',
    math_confidence INT(2) DEFAULT NULL COMMENT '수학 자신감 (0-10)',
    
    -- 학습 방식
    parent_style VARCHAR(50) DEFAULT NULL COMMENT '부모 지도 스타일',
    stress_level VARCHAR(20) DEFAULT NULL COMMENT '학습 스트레스 수준',
    feedback_preference VARCHAR(50) DEFAULT NULL COMMENT '피드백 선호도',
    
    -- 수학학원 시스템 특화 필드 (rules.yaml S0 요구사항)
    math_learning_style VARCHAR(50) DEFAULT NULL COMMENT '수학 학습 스타일 (계산형/개념형/응용형)',
    academy_name VARCHAR(255) DEFAULT NULL COMMENT '현재 학원명',
    academy_grade VARCHAR(100) DEFAULT NULL COMMENT '현재 학원 등급/반',
    academy_schedule VARCHAR(255) DEFAULT NULL COMMENT '학원 수업 일정',
    math_recent_score VARCHAR(100) DEFAULT NULL COMMENT '최근 수학 점수 및 등수',
    math_weak_units TEXT DEFAULT NULL COMMENT '취약 단원 목록',
    textbooks TEXT DEFAULT NULL COMMENT '사용 중인 교재 목록',
    math_unit_mastery JSON DEFAULT NULL COMMENT '단원별 마스터링 수준 (완료/진행중/미완료)',
    
    -- 메타데이터
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY userid_unique (userid),
    INDEX idx_math_style (math_learning_style),
    INDEX idx_academy (academy_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 온보딩 메인 데이터';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 출처 |
|--------|------|------|------|
| `userid` | BIGINT(10) | Moodle 사용자 ID (FK) | mdl_user.id |
| `course_level` | VARCHAR(10) | 과정 레벨 (초등/중등/고등) | onboarding_info.php |
| `grade_detail` | VARCHAR(10) | 학년 상세 (1학년/2학년/3학년) | onboarding_info.php |
| `concept_progress` | INT(2) | 개념 진도 진행도 | onboarding_info.php |
| `advanced_progress` | INT(2) | 심화 진도 진행도 | onboarding_info.php |
| `math_confidence` | INT(2) | 수학 자신감 (0-10) | onboarding_info.php |
| `exam_style` | VARCHAR(50) | 시험 대비 성향 | onboarding_info.php |
| `parent_style` | VARCHAR(50) | 부모 지도 스타일 | onboarding_info.php |
| `math_learning_style` | VARCHAR(50) | 수학 학습 스타일 (계산형/개념형/응용형) | rules.yaml S0_R1 |
| `academy_name` | VARCHAR(255) | 학원명 | rules.yaml S0_R2 |
| `math_recent_score` | VARCHAR(100) | 최근 수학 점수 및 등수 | rules.yaml S0_R3 |
| `textbooks` | TEXT | 사용 중인 교재 목록 | rules.yaml S0_R4 |
| `math_unit_mastery` | JSON | 단원별 마스터링 수준 | rules.yaml S0_R5 |

---

### 2. 학습 평가 결과 테이블: `alt42o_learning_assessment_results`

**목적**: 16개 설문 질문의 응답 및 점수 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS alt42o_learning_assessment_results (
    id INT(10) PRIMARY KEY AUTO_INCREMENT,
    userid INT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    
    -- 영역별 점수
    cognitive_score DECIMAL(5,2) DEFAULT NULL COMMENT '인지 영역 점수 (0-5)',
    emotional_score DECIMAL(5,2) DEFAULT NULL COMMENT '감정 영역 점수 (0-5)',
    behavioral_score DECIMAL(5,2) DEFAULT NULL COMMENT '행동 영역 점수 (0-5)',
    overall_total DECIMAL(5,2) DEFAULT NULL COMMENT '종합 점수 (0-5)',
    
    -- 질문-답변 쌍 (16개)
    qa01 TEXT DEFAULT NULL COMMENT 'Q1: reading (문제 읽기 방식)',
    qa02 TEXT DEFAULT NULL COMMENT 'Q2: persistence (지속성)',
    qa03 TEXT DEFAULT NULL COMMENT 'Q3: questioning (질문하기)',
    qa04 TEXT DEFAULT NULL COMMENT 'Q4: timeManagement (시간 관리)',
    qa05 TEXT DEFAULT NULL COMMENT 'Q5: conceptUnderstanding (개념 이해)',
    qa06 TEXT DEFAULT NULL COMMENT 'Q6: errorAnalysis (오류 분석)',
    qa07 TEXT DEFAULT NULL COMMENT 'Q7: logicalThinking (논리적 사고)',
    qa08 TEXT DEFAULT NULL COMMENT 'Q8: mathExpression (수식 표현)',
    qa09 TEXT DEFAULT NULL COMMENT 'Q9: mathAnxiety (수학 불안감)',
    qa10 TEXT DEFAULT NULL COMMENT 'Q10: resilience (회복력)',
    qa11 TEXT DEFAULT NULL COMMENT 'Q11: motivation (동기)',
    qa12 TEXT DEFAULT NULL COMMENT 'Q12: stressManagement (스트레스 관리)',
    qa13 TEXT DEFAULT NULL COMMENT 'Q13: studyHabits (학습 습관)',
    qa14 TEXT DEFAULT NULL COMMENT 'Q14: concentration (집중력)',
    qa15 TEXT DEFAULT NULL COMMENT 'Q15: collaboration (협업)',
    qa16 TEXT DEFAULT NULL COMMENT 'Q16: selfDirected (자기 주도성)',
    
    -- 메타데이터
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '평가 생성 시간',
    session_id VARCHAR(255) DEFAULT NULL COMMENT '세션 식별자',
    
    INDEX idx_userid (userid),
    INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 질문-필드 매핑

| 질문 ID | 필드명 | 영역 | 질문 내용 |
|---------|--------|------|-----------|
| reading | `qa01` | 인지 | 문제 읽기 방식 |
| persistence | `qa02` | 행동 | 어려운 문제 대응 |
| questioning | `qa03` | 행동 | 질문하기 방식 |
| timeManagement | `qa04` | 행동 | 시간 관리 |
| conceptUnderstanding | `qa05` | 인지 | 개념 이해 방식 |
| errorAnalysis | `qa06` | 인지 | 오류 분석 |
| logicalThinking | `qa07` | 인지 | 논리적 사고 |
| mathExpression | `qa08` | 인지 | 수식 표현 |
| mathAnxiety | `qa09` | 감정 | 수학 불안감 |
| resilience | `qa10` | 감정 | 회복력 |
| motivation | `qa11` | 감정 | 동기 |
| stressManagement | `qa12` | 감정 | 스트레스 관리 |
| studyHabits | `qa13` | 행동 | 학습 습관 |
| concentration | `qa14` | 행동 | 집중력 |
| collaboration | `qa15` | 행동 | 협업 |
| selfDirected | `qa16` | 인지 | 자기 주도성 |

---

### 3. 학생 프로필 테이블: `mdl_alt42_student_profiles`

**목적**: 학생의 학습 프로필 및 MBTI 정보 저장 (선택적 백업 소스)

#### 주요 필드

| 필드명 | 타입 | 설명 |
|--------|------|------|
| `id` | INT(10) | 프로필 ID |
| `user_id` | INT(10) | Moodle 사용자 ID |
| `learning_style` | VARCHAR(50) | 학습 스타일 |
| `mbti_type` | VARCHAR(4) | MBTI 유형 |
| `preferred_motivator` | VARCHAR(50) | 동기 유형 |
| `daily_active_time` | VARCHAR(20) | 활동 시간대 |
| `streak_days` | INT(10) | 연속 학습 일수 |
| `total_interactions` | INT(10) | 총 상호작용 횟수 |
| `interests` | JSON | 관심사 |
| `goals` | JSON | 학습 목표 |

---

### 4. 리포트 저장 테이블: `alt42o_onboarding_reports`

**목적**: 생성된 온보딩 리포트 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS alt42o_onboarding_reports (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    userid INT(11) NOT NULL COMMENT 'Moodle 사용자 ID',
    report_type VARCHAR(50) DEFAULT NULL COMMENT '리포트 유형: initial, regenerated, gpt',
    info_data LONGTEXT DEFAULT NULL COMMENT 'JSON: 기본 정보 데이터',
    assessment_id INT(11) DEFAULT NULL COMMENT 'FK: alt42o_learning_assessment_results.id',
    report_content LONGTEXT DEFAULT NULL COMMENT '생성된 리포트 HTML/JSON',
    generated_at INT(11) DEFAULT NULL COMMENT '생성 시각 (Unix timestamp)',
    generated_by VARCHAR(100) DEFAULT NULL COMMENT '생성자 식별자 (agent01_onboarding)',
    status VARCHAR(20) DEFAULT 'draft' COMMENT '상태: draft, published, archived',
    metadata LONGTEXT DEFAULT NULL COMMENT 'JSON: 추가 메타데이터',
    
    INDEX idx_userid (userid),
    INDEX idx_generated_at (generated_at DESC),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 데이터 흐름

### 1. 온보딩 프로세스

```
[학생] 
  ↓
[onboarding_info.php] → mdl_alt42o_onboarding 테이블 저장
  ↓
[onboarding_learningtype.php] → alt42o_learning_assessment_results 테이블 저장
  ↓
[Agent 01 패널 클릭] → 리포트 생성 트리거
  ↓
[report_generator.php] → 데이터 조합 및 리포트 생성
  ↓
[alt42o_onboarding_reports] → 리포트 저장
```

### 2. 데이터 조회 흐름

```
[Agent 01 요청]
  ↓
[data_access.php::getOnboardingContext()]
  ├─→ mdl_user (기본 정보)
  ├─→ mdl_alt42o_onboarding (온보딩 정보)
  ├─→ mdl_abessi_mbtilog (MBTI 정보)
  └─→ mdl_alt42_student_profiles (프로필 백업)
  ↓
[report_service.php::getOnboardingData()]
  ├─→ mdl_alt42_student_profiles (기본 프로필)
  └─→ alt42o_learning_assessment_results (평가 결과)
  ↓
[report_generator.php::generateReportWithGPT()]
  └─→ GPT API 호출하여 리포트 생성
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `math_learning_style` | mdl_alt42o_onboarding | math_learning_style | 수학 학습 스타일 |
| `academy_name` | mdl_alt42o_onboarding | academy_name | 학원명 |
| `academy_grade` | mdl_alt42o_onboarding | academy_grade | 학원 등급/반 |
| `academy_schedule` | mdl_alt42o_onboarding | academy_schedule | 학원 수업 일정 |
| `math_recent_score` | mdl_alt42o_onboarding | math_recent_score | 최근 수학 점수 |
| `math_weak_units` | mdl_alt42o_onboarding | math_weak_units | 취약 단원 |
| `textbooks` | mdl_alt42o_onboarding | textbooks | 교재 정보 |
| `math_unit_mastery` | mdl_alt42o_onboarding | math_unit_mastery | 단원 마스터리 |
| `math_level` | mdl_alt42o_onboarding | course_level + grade_detail | 수학 수준 |
| `concept_progress` | mdl_alt42o_onboarding | concept_progress | 개념 진도 |
| `advanced_progress` | mdl_alt42o_onboarding | advanced_progress | 심화 진도 |
| `study_style` | mdl_alt42o_onboarding | problem_preference | 학습 스타일 |
| `exam_style` | mdl_alt42o_onboarding | exam_style | 시험 대비 성향 |
| `math_confidence` | mdl_alt42o_onboarding | math_confidence | 수학 자신감 |
| `parent_style` | mdl_alt42o_onboarding | parent_style | 부모 지도 스타일 |
| `mbti_type` | mdl_abessi_mbtilog | mbti | MBTI 유형 |

### 설문 질문 → DB 필드 매핑

| 질문 ID | 영역 | DB 필드 | 점수 계산 |
|---------|------|---------|-----------|
| reading | 인지 | qa01 | cognitive_score 평균 |
| conceptUnderstanding | 인지 | qa05 | cognitive_score 평균 |
| errorAnalysis | 인지 | qa06 | cognitive_score 평균 |
| logicalThinking | 인지 | qa07 | cognitive_score 평균 |
| mathExpression | 인지 | qa08 | cognitive_score 평균 |
| selfDirected | 인지 | qa16 | cognitive_score 평균 |
| mathAnxiety | 감정 | qa09 | emotional_score 평균 |
| resilience | 감정 | qa10 | emotional_score 평균 |
| motivation | 감정 | qa11 | emotional_score 평균 |
| stressManagement | 감정 | qa12 | emotional_score 평균 |
| persistence | 행동 | qa02 | behavioral_score 평균 |
| questioning | 행동 | qa03 | behavioral_score 평균 |
| timeManagement | 행동 | qa04 | behavioral_score 평균 |
| studyHabits | 행동 | qa13 | behavioral_score 평균 |
| concentration | 행동 | qa14 | behavioral_score 평균 |
| collaboration | 행동 | qa15 | behavioral_score 평균 |

---

## Rules.yaml 연계

### S0: 수학학원 시스템 특화 필수 정보 수집

| Rule ID | 필드 | DB 테이블 | 우선순위 |
|---------|------|-----------|----------|
| S0_R1 | math_learning_style | mdl_alt42o_onboarding | 99 |
| S0_R2 | academy_name, academy_grade, academy_schedule | mdl_alt42o_onboarding | 98 |
| S0_R3 | math_recent_score, math_weak_units | mdl_alt42o_onboarding | 97 |
| S0_R4 | textbooks | mdl_alt42o_onboarding | 96 |
| S0_R5 | math_unit_mastery | mdl_alt42o_onboarding | 95 |

### S1: 신규 학생 등록 직후 관련 룰

| Rule ID | 설명 | 사용 필드 |
|---------|------|-----------|
| S1_R0 | 첫 수업 관련 질문 대응 | concept_progress, math_learning_style |
| S1_R1 | 종합 프로필 요약 | concept_progress, advanced_progress, study_style |
| S1_R2 | 초기 수업 준비 가이드 | math_level, concept_progress, study_style |
| S1_R3 | 신규 학생 완전 종합 요약 | math_level, concept_progress, advanced_progress, study_style, exam_style, math_confidence, math_learning_style, academy_name |

### S2: 수업 전 학습 설계 관련 룰

| Rule ID | 설명 | 사용 필드 |
|---------|------|-----------|
| S2_R1 | 통합 루틴 추천 | exam_style, math_confidence, parent_style |
| S2_R2 | 개념 vs 문제풀이 판단 | math_level, study_style, math_confidence, math_learning_style |
| S2_R3 | 어려운 학생 개념 우선 | math_level, math_confidence |
| S2_R4 | 상위권 학생 문제풀이 우선 | math_level, math_confidence |
| S2_R5 | 중위권 균형 접근 | math_level, study_style |

### S3: 개념/심화 진도 판단 관련 룰

| Rule ID | 설명 | 사용 필드 |
|---------|------|-----------|
| S3_R1 | 진도 밸런스 분석 | concept_progress, advanced_progress, math_unit_mastery |
| S3_R2 | 혼란 개념 예측 | concept_progress, math_level, math_confidence |
| S3_R3 | 진도 격차 경고 | concept_progress, advanced_progress |
| S3_R4 | 적절한 진도 밸런스 확인 | concept_progress, advanced_progress |

---

## 데이터 접근 함수

### 주요 함수 위치

- **`data_access.php::getOnboardingContext($studentid)`**: 온보딩 컨텍스트 수집
- **`report_service.php::getOnboardingData($userid)`**: 리포트용 데이터 조합
- **`report_generator.php::generateReportWithGPT($userid)`**: GPT 기반 리포트 생성

### 데이터 조회 예시

```php
// 온보딩 컨텍스트 조회
$context = getOnboardingContext($studentid);

// 리포트 데이터 조회
$data = getOnboardingData($userid);

// 평가 결과 조회
$assessment = $DB->get_record('alt42o_learning_assessment_results', 
    ['userid' => $userid], 
    '*', 
    IGNORE_MISSING
);
```

---

## 참고 파일

- **설문 질문 정의**: `includes/questions_data.php`
- **DB 스키마**: `rules/1_db_schema_sync.sql`
- **데이터 접근**: `rules/data_access.php`
- **리포트 생성**: `report_generator.php`
- **리포트 서비스**: `report_service.php`
- **Rules 정의**: `rules/rules.yaml`
- **DB 스키마 문서**: `db_schema.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 01 Onboarding System  
**문서 위치**: `alt42/orchestration/agents/agent01_onboarding/ONBOARDING_SURVEY_DB_REPORT.md`

---

## 파일 검증 결과

**검증일**: 2025-01-27  
**검증 상태**: ✅ 완료

### 참고 파일 존재 확인

| 파일 경로 | 상태 | 확인 내용 |
|----------|------|----------|
| `includes/questions_data.php` | ✅ 존재 | 16개 질문 정의 확인, 리포트 내용과 일치 |
| `rules/1_db_schema_sync.sql` | ✅ 존재 | DB 스키마 정의 확인, 리포트 내용과 일치 |
| `rules/data_access.php` | ✅ 존재 | `getOnboardingContext()` 함수 확인 |
| `report_generator.php` | ✅ 존재 | `generateReportWithGPT()` 함수 확인 |
| `report_service.php` | ✅ 존재 | `getOnboardingData()` 함수 확인, qa01-qa16 필드 사용 확인 |
| `rules/rules.yaml` | ✅ 존재 | S0_R1~S0_R5 룰 정의 확인 |
| `db_schema.md` | ✅ 존재 | 리포트 내용과 일치 |

### 코드-리포트 일치성 검증

#### 1. 설문 질문 정의
- ✅ `questions_data.php`의 16개 질문이 리포트의 질문 상세와 일치
- ✅ 질문 ID, 영역 분류, 선택지 점수 모두 일치

#### 2. 데이터베이스 스키마
- ✅ `mdl_alt42o_onboarding` 테이블 스키마 일치
- ✅ `alt42o_learning_assessment_results` 테이블 스키마 일치 (qa01-qa16 필드 확인)
- ✅ `alt42o_onboarding_reports` 테이블 스키마 일치

#### 3. 함수 구현
- ✅ `getOnboardingContext($studentid)` - `data_access.php`에 구현 확인
- ✅ `getOnboardingData($userid)` - `report_service.php`에 구현 확인, qa01-qa16 필드 조회 확인
- ✅ `generateReportWithGPT($userid)` - `report_generator.php`에 구현 확인

#### 4. Rules.yaml 연계
- ✅ S0_R1~S0_R5 룰 정의 확인
- ✅ 필드 매핑 정보 일치

### 검증 완료 항목

- [x] 설문 질문 정의 (16개) - `questions_data.php`와 일치
- [x] DB 테이블 스키마 (3개 테이블) - 실제 스키마와 일치
- [x] 데이터 접근 함수 (3개) - 구현 확인
- [x] 필드 매핑 정보 - 코드에서 사용하는 방식과 일치
- [x] Rules.yaml 연계 정보 - 실제 룰 정의와 일치
- [x] 참고 파일 경로 - 모든 파일 존재 확인

**결론**: 리포트의 모든 내용이 실제 코드베이스와 일치하며, 참고 파일들이 모두 존재합니다.

