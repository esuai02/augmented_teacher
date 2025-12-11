# AI Tutor 데이터베이스 스키마 설계

## 개요

AI Tutor 시스템의 MySQL 데이터베이스 스키마입니다.
- MySQL 버전: 5.7
- PHP 버전: 7.1.9
- 테이블 접두사: `mdl_alt42_`
- 문자셋: utf8mb4

## 테이블 목록 (14개)

### 핵심 테이블

| # | 테이블명 | 설명 | 주요 용도 |
|---|----------|------|-----------|
| 1 | `mdl_alt42_analysis_results` | 분석 결과 | 컨텐츠 분석 결과 저장 (메인) |
| 2 | `mdl_alt42_interactions` | 상호작용 히스토리 | 학생-튜터 대화 기록 |
| 3 | `mdl_alt42_sessions` | 세션 | 학습 세션 관리 |

### 룰/온톨로지 테이블

| # | 테이블명 | 설명 | 주요 용도 |
|---|----------|------|-----------|
| 4 | `mdl_alt42_generated_rules` | 생성된 룰 | 교수법 룰 저장 |
| 5 | `mdl_alt42_rule_contents` | 룰 컨텐츠 | 룰 검증/시나리오/테스트 케이스 |
| 6 | `mdl_alt42_ontology_data` | 온톨로지 데이터 | OIW 모델 노드 저장 |

### 학생 컨텍스트 테이블

| # | 테이블명 | 설명 | 주요 용도 |
|---|----------|------|-----------|
| 7 | `mdl_alt42_student_contexts` | 학생 컨텍스트 | 학생별 학습 상태 |
| 8 | `mdl_alt42_student_personas` | 학생-페르소나 매칭 | 현재 적용 페르소나 |
| 9 | `mdl_alt42_persona_switches` | 페르소나 스위칭 | 페르소나 변경 이력 |

### 페르소나/개입 활동 테이블

| # | 테이블명 | 설명 | 주요 용도 |
|---|----------|------|-----------|
| 10 | `mdl_alt42_personas` | 페르소나 | 12가지 학습자 페르소나 |
| 11 | `mdl_alt42_intervention_activities` | 개입 활동 | 42가지 개입 활동 |
| 12 | `mdl_alt42_intervention_executions` | 개입 활동 실행 | 개입 활동 실행 기록 |

### 필기 기반 튜터 테이블

| # | 테이블명 | 설명 | 주요 용도 |
|---|----------|------|-----------|
| 13 | `mdl_alt42_writing_patterns` | 필기 패턴 | 필기 패턴 분석 데이터 |
| 14 | `mdl_alt42_non_intrusive_questions` | 비침습적 질문 | 비침습적 질문 기록 |

---

## 테이블 상세 설계

### 1. mdl_alt42_analysis_results (분석 결과)

**목적**: 컨텐츠(텍스트/이미지) 분석 결과 저장

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT AUTO_INCREMENT | PK |
| analysis_id | VARCHAR(100) | 분석 고유 ID (ANALYSIS_timestamp_random) |
| student_id | BIGINT | 학생 ID (FK → mdl_user.id) |
| created_by | BIGINT | 생성자 ID |
| text_content | LONGTEXT | 분석된 텍스트 |
| image_data | LONGTEXT | Base64 이미지 |
| dialogue_analysis | LONGTEXT | JSON: 대화 분석 |
| comprehensive_questions | LONGTEXT | JSON: 포괄적 질문 |
| detailed_questions | LONGTEXT | JSON: 세부 질문 |
| teaching_rules | LONGTEXT | JSON: 교수법 룰 |
| ontology | LONGTEXT | JSON: 온톨로지 |
| rule_contents | LONGTEXT | JSON: 룰 컨텐츠 |
| metadata | LONGTEXT | JSON: 메타데이터 |
| created_at | DATETIME | 생성 시간 |
| updated_at | DATETIME | 수정 시간 |

**인덱스**:
- `idx_analysis_id` (UNIQUE)
- `idx_student_id`
- `idx_created_by`
- `idx_created_at`

---

### 2. mdl_alt42_interactions (상호작용)

**목적**: 학생-튜터 대화 기록

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT AUTO_INCREMENT | PK |
| interaction_id | VARCHAR(100) | 상호작용 ID |
| analysis_id | VARCHAR(100) | 관련 분석 ID |
| student_id | BIGINT | 학생 ID |
| session_id | VARCHAR(100) | 세션 ID |
| user_input | TEXT | 학생 입력 |
| response_text | TEXT | AI 응답 |
| response_data | LONGTEXT | JSON: 전체 응답 |
| matched_rules | TEXT | JSON: 매칭된 룰 |
| persona_id | VARCHAR(50) | 적용된 페르소나 |
| intervention_id | VARCHAR(50) | 적용된 개입 활동 |
| context_data | LONGTEXT | JSON: 컨텍스트 |
| understanding_level | ENUM | 이해도 레벨 |
| confidence | DECIMAL(3,2) | 신뢰도 |
| created_at | DATETIME | 생성 시간 |

---

### 3. mdl_alt42_personas (페르소나)

**목적**: 12가지 학습자 페르소나 정의

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT AUTO_INCREMENT | PK |
| persona_id | VARCHAR(50) | 페르소나 ID (P001-P012) |
| name | VARCHAR(100) | 페르소나 이름 |
| name_en | VARCHAR(100) | 영문 이름 |
| description | TEXT | 설명 |
| situation | TEXT | 상황 |
| behavior | TEXT | 행동 패턴 |
| hidden_cause | TEXT | 숨은 원인 |
| intervention_strategy | LONGTEXT | JSON: 개입 전략 |
| trigger_patterns | LONGTEXT | JSON: 트리거 패턴 |
| recommended_interventions | LONGTEXT | JSON: 추천 개입 ID |
| is_active | TINYINT(1) | 활성화 여부 |
| display_order | INT | 표시 순서 |

**기본 데이터**: 12개 페르소나 (P001-P012)

---

### 4. mdl_alt42_intervention_activities (개입 활동)

**목적**: AlphaTutor42 개입 시스템 (42가지)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT AUTO_INCREMENT | PK |
| activity_id | VARCHAR(50) | 활동 ID (INT_1_1 ~ INT_7_6) |
| category | ENUM | 카테고리 (7가지) |
| category_order | INT | 카테고리 순서 (1-7) |
| activity_order | INT | 카테고리 내 순서 |
| name | VARCHAR(100) | 활동명 |
| description | TEXT | 설명 |
| trigger_signals | LONGTEXT | JSON: 트리거 신호 |
| persona_mapping | LONGTEXT | JSON: 페르소나 매핑 |
| priority | INT | 우선순위 (1-3) |
| duration | VARCHAR(50) | 예상 시간 |
| method | VARCHAR(50) | 실행 방식 |
| is_active | TINYINT(1) | 활성화 여부 |
| execution_count | INT | 실행 횟수 |
| success_rate | DECIMAL(5,4) | 성공률 |

**기본 데이터**: 42개 개입 활동

**카테고리 (7개)**:
1. pause_wait (멈춤/대기) - 5개
2. repeat_rephrase (재설명) - 6개
3. alternative_explanation (전환 설명) - 7개
4. emphasis_alerting (강조/주의환기) - 5개
5. questioning_probing (질문/탐색) - 7개
6. immediate_intervention (즉시 개입) - 6개
7. emotional_regulation (정서 조절) - 6개

---

## ER 다이어그램 (관계)

```
mdl_user (Moodle)
    │
    ├── 1:N ── mdl_alt42_analysis_results
    │              │
    │              ├── 1:N ── mdl_alt42_interactions
    │              ├── 1:N ── mdl_alt42_generated_rules
    │              └── 1:N ── mdl_alt42_ontology_data
    │
    ├── 1:1 ── mdl_alt42_student_contexts
    │
    ├── 1:N ── mdl_alt42_student_personas
    │              └── N:1 ── mdl_alt42_personas
    │
    ├── 1:N ── mdl_alt42_persona_switches
    │
    ├── 1:N ── mdl_alt42_sessions
    │              └── 1:N ── mdl_alt42_interactions
    │
    ├── 1:N ── mdl_alt42_writing_patterns
    │
    ├── 1:N ── mdl_alt42_non_intrusive_questions
    │
    └── 1:N ── mdl_alt42_intervention_executions
                   └── N:1 ── mdl_alt42_intervention_activities
```

---

## 사용 예시

### 분석 결과 저장

```php
global $DB;

$record = new stdClass();
$record->analysis_id = 'ANALYSIS_' . time() . '_' . rand(1000, 9999);
$record->student_id = $USER->id;
$record->created_by = $USER->id;
$record->text_content = $textContent;
$record->dialogue_analysis = json_encode($dialogueAnalysis);
$record->comprehensive_questions = json_encode($comprehensiveQuestions);
$record->teaching_rules = json_encode($teachingRules);
$record->ontology = json_encode($ontology);
$record->created_at = date('Y-m-d H:i:s');
$record->updated_at = date('Y-m-d H:i:s');

$DB->insert_record('alt42_analysis_results', $record);
```

### 분석 결과 조회

```php
global $DB;

$result = $DB->get_record('alt42_analysis_results', 
    array('analysis_id' => $analysisId));

if ($result) {
    $dialogueAnalysis = json_decode($result->dialogue_analysis, true);
    $teachingRules = json_decode($result->teaching_rules, true);
}
```

### 상호작용 기록

```php
global $DB;

$interaction = new stdClass();
$interaction->interaction_id = 'INT_' . time() . '_' . rand(1000, 9999);
$interaction->analysis_id = $analysisId;
$interaction->student_id = $studentId;
$interaction->user_input = $userInput;
$interaction->response_text = $responseText;
$interaction->persona_id = $personaId;
$interaction->intervention_id = $interventionId;
$interaction->created_at = date('Y-m-d H:i:s');

$DB->insert_record('alt42_interactions', $interaction);
```

---

## 마이그레이션 가이드

### FileDB → MySQL 마이그레이션

1. **스키마 실행**
   ```bash
   mysql -u username -p database_name < schema.sql
   ```

2. **기존 데이터 마이그레이션**
   - `data/analysis_results/*.json` → `mdl_alt42_analysis_results`
   - `data/interactions/*.json` → `mdl_alt42_interactions`
   - 등...

3. **코드 변경**
   - `DBManager` 클래스를 Moodle DB API 사용하도록 수정
   - `FileDB` 참조를 `$DB` 참조로 변경

---

## 인덱스 전략

| 테이블 | 인덱스 | 용도 |
|--------|--------|------|
| analysis_results | idx_analysis_id | ID로 조회 |
| analysis_results | idx_student_id | 학생별 조회 |
| analysis_results | idx_created_at | 최신순 조회 |
| interactions | idx_session_id | 세션별 조회 |
| interactions | idx_created_at | 시간순 조회 |
| intervention_activities | idx_category | 카테고리별 조회 |
| intervention_activities | idx_priority | 우선순위별 조회 |

---

## 데이터 정리 정책

| 테이블 | 보관 기간 | 정리 주기 |
|--------|-----------|-----------|
| analysis_results | 1년 | 월간 |
| interactions | 6개월 | 주간 |
| writing_patterns | 3개월 | 일간 |
| non_intrusive_questions | 3개월 | 일간 |
| intervention_executions | 1년 | 월간 |

---

## 버전 히스토리

| 버전 | 날짜 | 변경 내용 |
|------|------|-----------|
| 1.0 | 2024-01-01 | 초기 스키마 설계 |

