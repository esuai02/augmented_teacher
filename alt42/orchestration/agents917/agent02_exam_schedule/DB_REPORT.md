# Agent 02 - Exam Schedule DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 02 - Exam Schedule (시험 일정)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)
5. [API 엔드포인트](#api-엔드포인트)

---

## 에이전트 개요

**목적**: 학생의 시험 일정을 관리하고, 시험까지 남은 기간에 따라 맞춤형 학습 전략을 제공

**주요 기능**:
- 시험 일정 등록 및 관리
- D-day 계산 및 시험 타임라인 분류
- 시험 타임라인별 맞춤형 학습 전략 생성
- 목표 분석 데이터와 연계한 전략 수립

**시험 타임라인 분류**:
- 🏖️ 방학
- 📅 D-2개월
- 📆 D-1개월
- ⏰ D-2주
- 🚨 D-1주
- 🔥 D-3일
- 💯 D-1일
- 📖 시험없음

---

## 데이터베이스 구조

### 1. 시험 일정 테이블: `mdl_alt42_exam_schedule`

**목적**: 학생의 시험 일정 정보 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42_exam_schedule (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    exam_date INT(11) NOT NULL COMMENT '시험 날짜 (Unix timestamp)',
    exam_name VARCHAR(255) NOT NULL COMMENT '시험명',
    target_score INT(3) DEFAULT NULL COMMENT '목표 점수',
    d_day INT(11) DEFAULT NULL COMMENT 'D-day (시험까지 남은 일수)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_exam_date (exam_date),
    INDEX idx_d_day (d_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시험 일정 정보';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 사용 예시 |
|--------|------|------|-----------|
| `userid` | BIGINT(10) | Moodle 사용자 ID (FK) | 1603 |
| `exam_date` | INT(11) | 시험 날짜 (Unix timestamp) | 1735689600 |
| `exam_name` | VARCHAR(255) | 시험명 | "중간고사", "수능 모의고사" |
| `target_score` | INT(3) | 목표 점수 | 90 |
| `d_day` | INT(11) | D-day (시험까지 남은 일수) | 30 |

---

### 2. 시험 전략 테이블: `mdl_alt42g_exam_strategies`

**목적**: 생성된 시험 준비 전략 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42g_exam_strategies (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    exam_timeline VARCHAR(50) NOT NULL COMMENT '시험 타임라인 (D-2개월, D-1주 등)',
    goal_analysis_data LONGTEXT DEFAULT NULL COMMENT '목표 분석 데이터 (JSON)',
    generated_strategy LONGTEXT DEFAULT NULL COMMENT '생성된 전략 내용',
    strategy_summary TEXT DEFAULT NULL COMMENT '전략 요약',
    gpt_model VARCHAR(50) DEFAULT 'gpt-4o' COMMENT '사용된 GPT 모델',
    generation_time_ms INT DEFAULT 0 COMMENT '생성 소요 시간 (밀리초)',
    timecreated BIGINT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    timemodified BIGINT(10) NOT NULL COMMENT '수정 시간 (Unix timestamp)',
    
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_exam_timeline (exam_timeline),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시험 준비 전략';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 |
|--------|------|------|
| `exam_timeline` | VARCHAR(50) | 시험 타임라인 분류 |
| `goal_analysis_data` | LONGTEXT | Agent 03의 목표 분석 결과 (JSON) |
| `generated_strategy` | LONGTEXT | GPT로 생성된 맞춤형 학습 전략 |
| `strategy_summary` | TEXT | 전략 요약 (최대 900자) |
| `gpt_model` | VARCHAR(50) | 사용된 GPT 모델 버전 |
| `generation_time_ms` | INT | 전략 생성 소요 시간 |

---

### 3. 시험 전략 메타데이터 테이블: `mdl_alt42g_exam_strategy_meta`

**목적**: 시험 전략 유형별 메타데이터 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42g_exam_strategy_meta (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    strategy_type VARCHAR(100) NOT NULL COMMENT '전략 유형',
    description TEXT DEFAULT NULL COMMENT '전략 설명',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    timecreated BIGINT(10) NOT NULL COMMENT '생성 시간',
    timemodified BIGINT(10) NOT NULL COMMENT '수정 시간',
    
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시험 전략 메타데이터';
```

---

## 데이터 흐름

### 1. 시험 일정 등록 프로세스

```
[학생] 
  ↓
[시험 일정 입력] → exam_date, exam_name, target_score
  ↓
[mdl_alt42_exam_schedule] → 시험 일정 저장
  ↓
[D-day 계산] → d_day 필드 업데이트
```

### 2. 시험 전략 생성 프로세스

```
[Agent 02 요청]
  ↓
[exam_strategy_api.php]
  ├─→ mdl_alt42_exam_schedule (시험 일정 조회)
  ├─→ mdl_alt42g_goal_analysis (목표 분석 데이터 조회 - Agent 03)
  └─→ GPT API 호출 (맞춤형 전략 생성)
  ↓
[mdl_alt42g_exam_strategies] → 생성된 전략 저장
```

### 3. 시험 타임라인 분류 로직

```php
// D-day에 따른 타임라인 분류
if ($d_day > 60) {
    $timeline = '🏖️ 방학';
} elseif ($d_day > 30) {
    $timeline = '📅 D-2개월';
} elseif ($d_day > 14) {
    $timeline = '📆 D-1개월';
} elseif ($d_day > 7) {
    $timeline = '⏰ D-2주';
} elseif ($d_day > 3) {
    $timeline = '🚨 D-1주';
} elseif ($d_day > 1) {
    $timeline = '🔥 D-3일';
} elseif ($d_day == 1) {
    $timeline = '💯 D-1일';
} else {
    $timeline = '📖 시험없음';
}
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `exam_date` | mdl_alt42_exam_schedule | exam_date | 시험 날짜 |
| `exam_name` | mdl_alt42_exam_schedule | exam_name | 시험명 |
| `target_score` | mdl_alt42_exam_schedule | target_score | 목표 점수 |
| `d_day` | mdl_alt42_exam_schedule | d_day | D-day |
| `exam_timeline` | mdl_alt42g_exam_strategies | exam_timeline | 시험 타임라인 |
| `goal_analysis_data` | mdl_alt42g_exam_strategies | goal_analysis_data | 목표 분석 데이터 (Agent 03 연계) |

### 연계 에이전트 데이터

| 에이전트 | 테이블 | 필드 | 용도 |
|---------|--------|------|------|
| Agent 03 | mdl_alt42g_goal_analysis | analysis_result | 목표 분석 결과를 전략 생성에 활용 |

---

## API 엔드포인트

### 1. 시험 전략 생성 API

**파일**: `api/exam_strategy_api.php`

**기능**: 시험 타임라인에 맞는 맞춤형 학습 전략 생성

**요청 파라미터**:
- `userid`: 사용자 ID
- `exam_timeline`: 시험 타임라인 (선택사항, 자동 계산 가능)

**응답 데이터**:
```json
{
    "success": true,
    "strategy": {
        "id": 123,
        "exam_timeline": "📅 D-2개월",
        "strategy_summary": "전략 요약...",
        "generated_strategy": "상세 전략 내용...",
        "generation_time_ms": 2500
    }
}
```

**프로세스**:
1. 시험 일정 조회 (`mdl_alt42_exam_schedule`)
2. 목표 분석 데이터 조회 (`mdl_alt42g_goal_analysis`)
3. GPT API 호출하여 전략 생성
4. 생성된 전략 저장 (`mdl_alt42g_exam_strategies`)

---

## 데이터 접근 함수

### 주요 함수 위치

- **`api/exam_strategy_api.php::generateExamStrategy()`**: 시험 전략 생성
- **`api/exam_strategy_api.php::getExamSchedule()`**: 시험 일정 조회
- **`api/exam_strategy_api.php::calculateTimeline()`**: D-day 기반 타임라인 계산

### 데이터 조회 예시

```php
// 시험 일정 조회
$examSchedule = $DB->get_record('alt42_exam_schedule', 
    ['userid' => $userid], 
    '*', 
    IGNORE_MISSING
);

// 시험 전략 조회
$strategy = $DB->get_record('alt42g_exam_strategies', 
    ['userid' => $userid, 'exam_timeline' => $timeline], 
    '*', 
    IGNORE_MISSING
);
```

---

## 참고 파일

- **API 파일**: `api/exam_strategy_api.php`
- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent02_exam_schedule.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 02 Exam Schedule System  
**문서 위치**: `alt42/orchestration/agents/agent02_exam_schedule/DB_REPORT.md`

