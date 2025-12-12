# Agent 03 - Goals Analysis DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 03 - Goals Analysis (목표 분석)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)
5. [분석 유형](#분석-유형)

---

## 에이전트 개요

**목적**: 학생의 목표 설정 및 달성률을 분석하여 효과적인 목표 관리 전략 제공

**주요 기능**:
- 분기목표, 주간목표, 오늘목표 분석
- 목표 달성률 및 효과성 점수 계산
- 목표 유형별 균형 분석
- 목표 분석 결과 저장 및 추적

**분석 유형**:
- `quarter`: 분기목표 분석
- `weekly`: 주간목표 분석
- `today`: 오늘목표 분석
- `correlation`: 목표 간 상관관계 분석

---

## 데이터베이스 구조

### 1. 학생 목표 테이블: `mdl_alt42g_student_goals`

**목적**: 학생이 설정한 목표 정보 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42g_student_goals (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    goal_type VARCHAR(50) NOT NULL COMMENT '목표 유형 (quarter/weekly/today)',
    goal_text TEXT DEFAULT NULL COMMENT '목표 내용',
    status VARCHAR(20) DEFAULT 'pending' COMMENT '상태 (pending/in_progress/completed/cancelled)',
    progress DECIMAL(5,2) DEFAULT 0.00 COMMENT '진행률 (0-100)',
    target_date INT(11) DEFAULT NULL COMMENT '목표 날짜 (Unix timestamp)',
    timecreated BIGINT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    timemodified BIGINT(10) DEFAULT NULL COMMENT '수정 시간 (Unix timestamp)',
    
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_goal_type (goal_type),
    INDEX idx_status (status),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 목표 정보';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `userid` | BIGINT(10) | Moodle 사용자 ID (FK) | 1603 |
| `goal_type` | VARCHAR(50) | 목표 유형 | "quarter", "weekly", "today" |
| `goal_text` | TEXT | 목표 내용 | "수학 90점 이상 달성" |
| `status` | VARCHAR(20) | 목표 상태 | "pending", "in_progress", "completed", "cancelled" |
| `progress` | DECIMAL(5,2) | 진행률 (0-100) | 75.50 |
| `target_date` | INT(11) | 목표 날짜 (Unix timestamp) | 1735689600 |

---

### 2. 목표 분석 결과 테이블: `mdl_alt42g_goal_analysis`

**목적**: 목표 분석 결과 및 효과성 점수 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42g_goal_analysis (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    analysis_type VARCHAR(50) NOT NULL COMMENT '분석 유형 (quarter/weekly/today/correlation)',
    raw_data LONGTEXT DEFAULT NULL COMMENT '원본 데이터 (JSON)',
    gpt_prompt TEXT DEFAULT NULL COMMENT 'GPT 프롬프트',
    analysis_result TEXT DEFAULT NULL COMMENT '분석 결과',
    statistics TEXT DEFAULT NULL COMMENT '통계 데이터 (JSON)',
    effectiveness_score DECIMAL(5,2) DEFAULT NULL COMMENT '효과성 점수 (0-100)',
    created_at INT(11) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_analysis_type (analysis_type),
    INDEX idx_created_at (created_at),
    INDEX idx_effectiveness_score (effectiveness_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='목표 분석 결과';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 |
|--------|------|------|
| `analysis_type` | VARCHAR(50) | 분석 유형 |
| `raw_data` | LONGTEXT | 분석에 사용된 원본 데이터 (JSON) |
| `gpt_prompt` | TEXT | GPT 분석에 사용된 프롬프트 |
| `analysis_result` | TEXT | GPT 분석 결과 |
| `statistics` | TEXT | 통계 데이터 (JSON) |
| `effectiveness_score` | DECIMAL(5,2) | 목표 효과성 점수 (0-100) |

---

### 3. 포모도르 세션 테이블: `mdl_alt42g_pomodoro_sessions`

**목적**: 포모도르 학습 세션 기록 (목표 달성과 연계)

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42g_pomodoro_sessions (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    status VARCHAR(20) DEFAULT 'completed' COMMENT '상태 (completed/incomplete/cancelled)',
    duration INT(11) DEFAULT 1500 COMMENT '지속 시간 (초, 기본 25분)',
    goal_id BIGINT(10) DEFAULT NULL COMMENT '연관된 목표 ID (FK)',
    timecreated BIGINT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_status (status),
    INDEX idx_goal_id (goal_id),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='포모도르 세션 기록';
```

---

### 4. 학습 세션 테이블: `mdl_alt42g_learning_sessions`

**목적**: 학습 세션 기록 (참조용, 상세 스키마 미확인)

**참조 필드**:
- `userid`: 사용자 ID
- `session_start`: 세션 시작 시간
- `session_end`: 세션 종료 시간
- `goal_id`: 연관된 목표 ID

---

### 5. 커리큘럼 진행도 테이블: `mdl_alt42g_curriculum_progress`

**목적**: 커리큘럼 진행도 추적 (참조용, 상세 스키마 미확인)

**참조 필드**:
- `userid`: 사용자 ID
- `curriculum_id`: 커리큘럼 ID
- `progress`: 진행률

---

### 6. 완료된 단원 테이블: `mdl_alt42g_completed_units`

**목적**: 완료된 단원 기록 (참조용, 상세 스키마 미확인)

**참조 필드**:
- `userid`: 사용자 ID
- `unit_id`: 단원 ID
- `completed_at`: 완료 시간

---

## 데이터 흐름

### 1. 목표 분석 프로세스

```
[학생 목표 설정]
  ↓
[mdl_alt42g_student_goals] → 목표 저장
  ↓
[Agent 03 분석 요청]
  ↓
[goal_analysis_executor.php]
  ├─→ collectDataByType() - 목표 데이터 수집
  ├─→ generatePromptByType() - GPT 프롬프트 생성
  ├─→ analyzeWithGPT() - GPT 분석 수행
  ├─→ calculateStatistics() - 통계 계산
  └─→ calculateEffectiveness() - 효과성 점수 계산
  ↓
[mdl_alt42g_goal_analysis] → 분석 결과 저장
```

### 2. 목표 달성률 계산

```php
// 목표 달성률 계산 로직
$totalGoals = count($goals);
$completedGoals = 0;

foreach ($goals as $goal) {
    if ($goal->status === 'completed') {
        $completedGoals++;
    }
}

$completionRate = $totalGoals > 0 
    ? round(($completedGoals / $totalGoals) * 100, 1) 
    : 0;
```

### 3. 목표 유형별 균형 분석

```php
// 목표 유형별 개수 집계
$categoryCount = [];
foreach ($goals as $goal) {
    $type = $goal->goal_type;
    $categoryCount[$type] = ($categoryCount[$type] ?? 0) + 1;
}
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `goal_type` | mdl_alt42g_student_goals | goal_type | 목표 유형 |
| `status` | mdl_alt42g_student_goals | status | 목표 상태 |
| `progress` | mdl_alt42g_student_goals | progress | 진행률 |
| `analysis_type` | mdl_alt42g_goal_analysis | analysis_type | 분석 유형 |
| `effectiveness_score` | mdl_alt42g_goal_analysis | effectiveness_score | 효과성 점수 |
| `completion_rate` | 계산 필드 | - | 목표 달성률 (계산) |

### 연계 에이전트 데이터

| 에이전트 | 테이블 | 필드 | 용도 |
|---------|--------|------|------|
| Agent 02 | mdl_alt42g_exam_strategies | goal_analysis_data | 시험 전략 생성 시 목표 분석 활용 |
| Agent 09 | mdl_alt42g_goal_analysis | effectiveness_score | 학습 관리에서 목표 달성률 활용 |
| Agent 15 | mdl_alt42g_goal_analysis | created_at | 문제 재정의 시 목표 분석 참조 |
| Agent 17 | mdl_alt42g_student_goals | - | 남은 활동 분석 시 목표 참조 |

---

## 분석 유형

### 1. 분기목표 분석 (quarter)

**데이터 소스**: `mdl_alt42g_student_goals` (goal_type='quarter')

**분석 항목**:
- 목표 구체성 점수 (0-5)
- 목표 달성 가능성 평가
- 분기 목표와 주간/오늘 목표의 연계성

**저장 필드**: `analysis_type='quarter'`

---

### 2. 주간목표 분석 (weekly)

**데이터 소스**: `mdl_alt42g_student_goals` (goal_type='weekly')

**분석 항목**:
- 주간 목표 구체성 점수
- 주간 목표 달성률
- 주간 목표와 오늘 목표의 연계성

**저장 필드**: `analysis_type='weekly'`

---

### 3. 오늘목표 분석 (today)

**데이터 소스**: `mdl_alt42g_student_goals` (goal_type='today')

**분석 항목**:
- 오늘 목표 구체성 점수
- 오늘 목표 달성률
- 실시간 진행 상황

**저장 필드**: `analysis_type='today'`

---

### 4. 상관관계 분석 (correlation)

**데이터 소스**: 분기/주간/오늘 목표 전체

**분석 항목**:
- 분기→주간→오늘 목표 간 상관관계 점수 (0-5)
- 목표 간 일관성 평가
- 목표 계층 구조 분석

**저장 필드**: `analysis_type='correlation'`

---

## 데이터 접근 함수

### 주요 함수 위치

- **`rules/data_access.php::getGoalsAnalysisContext($studentid)`**: 목표 분석 컨텍스트 수집
- **`api/goal_analysis_executor.php::executeAnalysis($type, $userid)`**: 목표 분석 실행
- **`api/goal_analysis_executor.php::collectDataByType($type, $userid, $DB)`**: 유형별 데이터 수집
- **`api/goal_analysis_executor.php::calculateEffectiveness($rawData, $analysisResult, $type)`**: 효과성 점수 계산

### 데이터 조회 예시

```php
// 학생 목표 조회
$goals = $DB->get_records_sql(
    "SELECT * FROM {alt42g_student_goals} 
     WHERE userid = ? 
     ORDER BY timecreated DESC 
     LIMIT 10",
    [$studentid]
);

// 최근 목표 분석 결과 조회
$latestAnalysis = $DB->get_record_sql(
    "SELECT * FROM {alt42g_goal_analysis} 
     WHERE userid = ? 
     ORDER BY created_at DESC 
     LIMIT 1",
    [$studentid]
);

// 포모도르 세션 조회
$pomodoroSessions = $DB->get_records('alt42g_pomodoro_sessions', 
    ['userid' => $userid, 'status' => 'completed']
);
```

---

## 참고 파일

- **데이터 접근**: `rules/data_access.php`
- **분석 실행**: `api/goal_analysis_executor.php`
- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent03_goals_analysis.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 03 Goals Analysis System  
**문서 위치**: `alt42/orchestration/agents/agent03_goals_analysis/DB_REPORT.md`

