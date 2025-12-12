# Agent 04 - Problem Activity DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 04 - Problem Activity (문제 활동)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)
5. [활동 카테고리](#활동-카테고리)

---

## 에이전트 개요

**목적**: 학생의 학습 활동 유형을 식별하고 선택 데이터를 저장

**주요 기능**:
- 7개 주요 활동 카테고리 제공
- 각 카테고리별 4개 하위 활동 항목
- 선택 데이터 실시간 저장
- 향후 행동 유형 설문 확장 예정

**활동 카테고리**:
1. 개념이해 (concept_understanding)
2. 유형학습 (pattern_learning)
3. 문제풀이 (problem_solving)
4. 오답노트 (error_notes)
5. 질의응답 (qna)
6. 복습활동 (review)
7. 포모도르 (pomodoro)

---

## 데이터베이스 구조

### 1. 학생 활동 테이블: `mdl_alt42_student_activity`

**목적**: 학생의 학습 활동 선택 및 행동 유형 데이터 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42_student_activity (
    id BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) UNSIGNED NOT NULL COMMENT 'Moodle 사용자 ID',
    main_category VARCHAR(100) NOT NULL COMMENT '주요 활동 카테고리',
    sub_activity VARCHAR(200) DEFAULT NULL COMMENT '하위 활동 항목',
    behavior_type VARCHAR(50) DEFAULT NULL COMMENT '행동 유형 (향후 설문 확장용)',
    survey_responses TEXT DEFAULT NULL COMMENT '설문 응답 데이터 (JSON)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
    
    INDEX idx_userid (userid),
    INDEX idx_category (main_category),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Agent04: 학생 활동 선택 및 행동 유형 데이터';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `userid` | BIGINT(10) UNSIGNED | Moodle 사용자 ID (FK) | 1603 |
| `main_category` | VARCHAR(100) | 주요 활동 카테고리 | "problem_solving", "concept_understanding" |
| `sub_activity` | VARCHAR(200) | 하위 활동 항목 | "기출문제 풀이", "개념 정리" |
| `behavior_type` | VARCHAR(50) | 행동 유형 (향후 확장) | NULL |
| `survey_responses` | TEXT | 설문 응답 데이터 (JSON) | '{"question1": "answer1"}' |

---

## 데이터 흐름

### 1. 활동 선택 프로세스

```
[학생] 
  ↓
[활동 카테고리 선택] → main_category
  ↓
[하위 활동 선택] → sub_activity
  ↓
[API 호출] → save_activity.php
  ↓
[mdl_alt42_student_activity] → 활동 데이터 저장
```

### 2. 활동 이력 조회 프로세스

```
[Agent 04 요청]
  ↓
[get_activity.php]
  ├─→ mdl_alt42_student_activity (활동 이력 조회)
  └─→ 최근 N개 활동 반환
```

---

## 필드 매핑

### 활동 카테고리 → DB 필드 매핑

| 활동 카테고리 ID | main_category 값 | 설명 |
|-----------------|------------------|------|
| concept_understanding | "concept_understanding" | 개념이해 |
| pattern_learning | "pattern_learning" | 유형학습 |
| problem_solving | "problem_solving" | 문제풀이 |
| error_notes | "error_notes" | 오답노트 |
| qna | "qna" | 질의응답 |
| review | "review" | 복습활동 |
| pomodoro | "pomodoro" | 포모도르 |

### 연계 에이전트 데이터

| 에이전트 | 테이블 | 필드 | 용도 |
|---------|--------|------|------|
| Agent 17 | mdl_alt42_student_activity | - | 남은 활동 분석 시 활동 이력 참조 |

---

## 활동 카테고리

### 1. 개념이해 (concept_understanding)

**하위 활동**:
- 개념 정리
- 개념 설명 듣기
- 개념 예제 풀이
- 개념 연결하기

---

### 2. 유형학습 (pattern_learning)

**하위 활동**:
- 유형별 문제 분류
- 유형별 풀이법 학습
- 유형별 연습 문제
- 유형별 정리

---

### 3. 문제풀이 (problem_solving)

**하위 활동**:
- 기출문제 풀이
- 실전 문제 풀이
- 심화 문제 풀이
- 시간 제한 문제 풀이

---

### 4. 오답노트 (error_notes)

**하위 활동**:
- 오답 정리
- 오답 분석
- 오답 재풀이
- 오답 패턴 분석

---

### 5. 질의응답 (qna)

**하위 활동**:
- 선생님께 질문
- 친구와 토론
- 온라인 질문
- 개념 확인

---

### 6. 복습활동 (review)

**하위 활동**:
- 이전 학습 복습
- 단원 복습
- 전체 복습
- 시험 전 복습

---

### 7. 포모도르 (pomodoro)

**하위 활동**:
- 25분 집중 학습
- 5분 휴식
- 포모도르 완료
- 포모도르 통계 확인

---

## API 엔드포인트

### 1. 활동 저장 API

**파일**: `api/save_activity.php`

**기능**: 학생의 활동 선택 저장

**요청 파라미터**:
- `userid`: 사용자 ID
- `main_category`: 주요 활동 카테고리
- `sub_activity`: 하위 활동 항목 (선택사항)
- `behavior_type`: 행동 유형 (선택사항)
- `survey_responses`: 설문 응답 (JSON, 선택사항)

**응답 데이터**:
```json
{
    "success": true,
    "message": "Activity saved successfully",
    "activity_id": 123
}
```

---

### 2. 활동 이력 조회 API

**파일**: `api/get_activity.php`

**기능**: 학생의 활동 이력 조회

**요청 파라미터**:
- `userid`: 사용자 ID
- `limit`: 조회할 최대 개수 (기본값: 10)
- `category`: 필터링할 카테고리 (선택사항)

**응답 데이터**:
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "main_category": "problem_solving",
            "sub_activity": "기출문제 풀이",
            "created_at": "2025-01-27 10:30:00"
        }
    ]
}
```

---

## 데이터 접근 함수

### 주요 함수 위치

- **`ui/activity_categories.js::saveSelection()`**: 활동 선택 저장
- **`ui/activity_categories.js::getHistory()`**: 활동 이력 조회
- **`api/save_activity.php`**: 활동 저장 API
- **`api/get_activity.php`**: 활동 이력 조회 API

### 데이터 조회 예시

```php
// 학생 활동 이력 조회
$activities = $DB->get_records('alt42_student_activity', 
    ['userid' => $userid], 
    'created_at DESC', 
    '*', 
    0, 
    10
);

// 특정 카테고리 활동 조회
$categoryActivities = $DB->get_records('alt42_student_activity', 
    ['userid' => $userid, 'main_category' => 'problem_solving'], 
    'created_at DESC'
);
```

---

## 참고 파일

- **DB 확인**: `api/check_db.php`
- **활동 저장**: `api/save_activity.php`
- **활동 조회**: `api/get_activity.php`
- **UI 컴포넌트**: `ui/activity_categories.js`
- **README**: `README.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 04 Problem Activity System  
**문서 위치**: `alt42/orchestration/agents/agent04_problem_activity/DB_REPORT.md`

