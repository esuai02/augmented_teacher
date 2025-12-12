# Agent01 Onboarding 데이터베이스 테이블 매핑 문서

**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**버전**: 1.0

---

## 목차

1. [개요](#개요)
2. [MATHKING DB 테이블 (mdl_alt42g_*)](#mathking-db-테이블-mdl_alt42g_)
3. [Moodle DB 테이블 (mdl_alt42o_*)](#moodle-db-테이블-mdl_alt42o_)
4. [Moodle DB 테이블 (mdl_alt42_*)](#moodle-db-테이블-mdl_alt42_)
5. [기타 테이블](#기타-테이블)
6. [필드 매핑 요약](#필드-매핑-요약)

---

## 개요

Agent01 Onboarding은 여러 데이터베이스 테이블에서 데이터를 수집합니다:

- **MATHKING DB**: 온보딩 UI에서 입력되는 데이터 (`mdl_alt42g_*`)
- **Moodle DB**: 온보딩 시스템 데이터 (`mdl_alt42o_*`)
- **Moodle DB**: 일반 시스템 데이터 (`mdl_alt42_*`, `mdl_abessi_*`)

---

## MATHKING DB 테이블 (mdl_alt42g_*)

### 1. mdl_alt42g_learning_progress

**목적**: 학습 진도 정보 저장

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `math_level` | VARCHAR(50) | 수학 수준 | `math_level` | report_service.php:112 |
| `concept_level` | VARCHAR(50) | 개념 레벨 | `concept_level` | report_service.php:113 |
| `concept_progress` | INT(2) | 개념 진도 (0-6) | `concept_progress` | report_service.php:114 |
| `advanced_level` | VARCHAR(50) | 심화 레벨 | `advanced_level` | report_service.php:115 |
| `advanced_progress` | INT(2) | 심화 진도 (0-6) | `advanced_progress` | report_service.php:116 |
| `notes` | TEXT | 학습 메모 | `notes` | report_service.php:117 |
| `weekly_hours` | INT(3) | 주별 공부 시간 | `weekly_hours` | report_service.php:118 |
| `academy_experience` | TEXT | 학원 경험 | `academy_experience` | report_service.php:119 |

**데이터 소스**: MATHKING DB  
**조회 함수**: `report_service.php::getOnboardingData()` (line 109-120)

---

### 2. mdl_alt42g_learning_style

**목적**: 학습 스타일 정보 저장

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `problem_preference` | VARCHAR(50) | 문제풀이 선호도 | `problem_preference` | report_service.php:125 |
| `exam_style` | VARCHAR(50) | 시험 대비 성향 | `exam_style` | report_service.php:126 |
| `math_confidence` | INT(2) | 수학 자신감 (0-10) | `math_confidence` | report_service.php:127 |

**데이터 소스**: MATHKING DB  
**조회 함수**: `report_service.php::getOnboardingData()` (line 122-128)

---

### 3. mdl_alt42g_learning_method

**목적**: 학습 방식 정보 저장

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `parent_style` | VARCHAR(50) | 부모 지도 스타일 | `parent_style` | report_service.php:133 |
| `stress_level` | VARCHAR(20) | 학습 스트레스 수준 | `stress_level` | report_service.php:134 |
| `feedback_preference` | VARCHAR(50) | 피드백 선호도 | `feedback_preference` | report_service.php:135 |

**데이터 소스**: MATHKING DB  
**조회 함수**: `report_service.php::getOnboardingData()` (line 130-136)

---

### 4. mdl_alt42g_learning_goals

**목적**: 학습 목표 정보 저장


| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `short_term_goal` | VARCHAR(255) | 단기 목표 | `short_term_goal` | report_service.php:141 |
| `mid_term_goal` | VARCHAR(255) | 중기 목표 | `mid_term_goal` | report_service.php:142 |
| `long_term_goal` | VARCHAR(255) | 장기 목표 | `long_term_goal` | report_service.php:143 |
| `goal_note` | TEXT | 목표 관련 메모 | `goal_note` | report_service.php:144 |

**데이터 소스**: MATHKING DB  
**조회 함수**: `report_service.php::getOnboardingData()` (line 138-145)

---

### 5. mdl_alt42g_additional_info

**목적**: 추가 개인 정보 저장

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `favorite_food` | VARCHAR(255) | 좋아하는 음식 | `favorite_food` | report_service.php:150 |
| `favorite_fruit` | VARCHAR(255) | 좋아하는 과일 | `favorite_fruit` | report_service.php:151 |
| `favorite_snack` | VARCHAR(255) | 좋아하는 과자 | `favorite_snack` | report_service.php:152 |
| `hobbies_interests` | TEXT | 취미/관심사 | `hobbies_interests` | report_service.php:153 |
| `fandom_yn` | TINYINT(1) | 팬덤 여부 | `fandom_yn` | report_service.php:154 |
| `data_consent` | TINYINT(1) | 데이터 수집 동의 | `data_consent` | report_service.php:155 |

**데이터 소스**: MATHKING DB  
**조회 함수**: `report_service.php::getOnboardingData()` (line 147-156)  
**매핑 상태**: ✅ `data_access.php`에 추가됨 (2025-01-27)

---

## Moodle DB 테이블 (mdl_alt42o_*)

### 1. mdl_alt42o_onboarding

**목적**: 온보딩 메인 데이터 저장

**주요 필드**: [ONBOARDING_SURVEY_DB_REPORT.md](./ONBOARDING_SURVEY_DB_REPORT.md#1-메인-온보딩-테이블-mdl_alt42o_onboarding) 참조

**데이터 소스**: Moodle DB  
**조회 함수**: `data_access.php::getOnboardingContext()` (line 272-331)

---

### 2. mdl_alt42o_learning_assessment_results

**목적**: 학습 평가 결과 저장 (16개 설문 질문)

**주요 필드**: [ONBOARDING_SURVEY_DB_REPORT.md](./ONBOARDING_SURVEY_DB_REPORT.md#2-학습-평가-결과-테이블-alt42o_learning_assessment_results) 참조

**데이터 소스**: Moodle DB  
**조회 함수**: `report_service.php::getOnboardingData()` (line 166-198)

---

### 3. mdl_alt42o_onboarding_reports

**목적**: 생성된 온보딩 리포트 저장

**주요 필드**: [ONBOARDING_SURVEY_DB_REPORT.md](./ONBOARDING_SURVEY_DB_REPORT.md#4-리포트-저장-테이블-alt42o_onboarding_reports) 참조

**데이터 소스**: Moodle DB  
**조회 함수**: `report_service.php::getExistingReport()` (line 224-260)

---

### 4. mdl_alt42o_learning_history

**목적**: 학습 이력 저장

| 필드명 | 타입 | 설명 | 매핑 필드 | 상태 |
|--------|------|------|-----------|------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `academy_name` | VARCHAR(255) | 학원/과외 이름 | `academy_name` | ⚠️ 미매핑 |
| `academy_grade` | VARCHAR(100) | 학원 등급/반 | `academy_grade` | ⚠️ 미매핑 |
| `academy_schedule` | VARCHAR(255) | 학원 수업 일정 | `academy_schedule` | ⚠️ 미매핑 |

**데이터 소스**: Moodle DB  
**상태**: ⚠️ 현재 사용되지 않음 (mdl_alt42o_onboarding에 통합됨)

---

## Moodle DB 테이블 (mdl_alt42_*)

### 1. mdl_alt42_student_profiles

**목적**: 학생 프로필 정보 저장 (백업 소스)

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `user_id` | INT(10) | 사용자 ID | - | - |
| `learning_style` | VARCHAR(50) | 학습 스타일 | `learning_style` | report_service.php:54 |
| `interests` | JSON | 관심사 | `interests` | report_service.php:55 |
| `goals` | JSON | 학습 목표 | `goals` | report_service.php:56 |
| `mbti_type` | VARCHAR(4) | MBTI 유형 | `mbti_type` | report_service.php:57 |
| `preferred_motivator` | VARCHAR(50) | 동기 유형 | `preferred_motivator` | report_service.php:58 |
| `daily_active_time` | VARCHAR(20) | 활동 시간대 | `daily_active_time` | report_service.php:59 |
| `streak_days` | INT(10) | 연속 학습 일수 | `streak_days` | report_service.php:60 |
| `total_interactions` | INT(10) | 총 상호작용 횟수 | `total_interactions` | report_service.php:61 |
| `last_active` | DATE | 마지막 활동일 | `last_active` | report_service.php:62 |
| `profile_data` | JSON | 프로필 데이터 (JSON) | - | data_access.php:342-348 |

**데이터 소스**: Moodle DB  
**조회 함수**: 
- `report_service.php::getOnboardingData()` (line 46-63)
- `data_access.php::getOnboardingContext()` (line 335-350)

---

### 2. mdl_alt42_goinghome

**목적**: 하교 전 리포트 데이터 저장 (JSON)

| 필드명 | 타입 | 설명 | 매핑 필드 | 상태 |
|--------|------|------|-----------|------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `text` | LONGTEXT | JSON 데이터 | - | ⚠️ 미매핑 |

**데이터 소스**: Moodle DB  
**상태**: ⚠️ dataindex_user.php에서만 조회 (line 145-162)

---

## 기타 테이블

### 1. mdl_user

**목적**: Moodle 사용자 기본 정보

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `id` | BIGINT(10) | 사용자 ID | `userid` | - |
| `firstname` | VARCHAR(100) | 이름 | `student_name` (firstname + lastname) | report_service.php:36 |
| `lastname` | VARCHAR(100) | 성 | `student_name` (firstname + lastname) | report_service.php:36 |
| `email` | VARCHAR(100) | 이메일 | `email` | report_service.php:37 |
| `phone1` | VARCHAR(20) | 전화번호 | `phone` | report_service.php:38 |
| `city` | VARCHAR(120) | 도시 | `city` | report_service.php:39 |
| `country` | VARCHAR(2) | 국가 | `country` | report_service.php:40 |
| `timecreated` | BIGINT(10) | 가입일 | `new_student_flag` 계산용 | data_access.php:258 |
| `lastaccess` | BIGINT(10) | 마지막 접속 | `last_access` | data_access.php:255 |

**데이터 소스**: Moodle DB  
**조회 함수**: 
- `report_service.php::getOnboardingData()` (line 29-41)
- `data_access.php::getOnboardingContext()` (line 252-259)

---

### 2. mdl_abessi_mbtilog

**목적**: MBTI 로그 저장

| 필드명 | 타입 | 설명 | 매핑 필드 | 사용 위치 |
|--------|------|------|-----------|----------|
| `userid` | BIGINT(10) | 사용자 ID | - | - |
| `mbti` | VARCHAR(4) | MBTI 유형 | `mbti_type` | report_service.php:83 |
| `timecreated` | BIGINT(10) | 생성 시간 | `mbti_timecreated` | report_service.php:84 |
| `id` | BIGINT(10) | 로그 ID | `mbti_log_id` | report_service.php:85 |

**데이터 소스**: Moodle DB  
**조회 함수**: 
- `report_service.php::getOnboardingData()` (line 68-86)
- `data_access.php::getOnboardingContext()` (line 262-270)

---

## 필드 매핑 요약

### 현재 매핑된 필드 (data_access.php)

| 필드명 | 테이블 | 상태 |
|--------|--------|------|
| `math_level` | mdl_alt42o_onboarding | ✅ |
| `concept_progress` | mdl_alt42o_onboarding | ✅ |
| `advanced_progress` | mdl_alt42o_onboarding | ✅ |
| `math_confidence` | mdl_alt42o_onboarding | ✅ |
| `exam_style` | mdl_alt42o_onboarding | ✅ |
| `parent_style` | mdl_alt42o_onboarding | ✅ |
| `study_style` | mdl_alt42o_onboarding | ✅ |
| `mbti_type` | mdl_abessi_mbtilog | ✅ |
| `favorite_food` | mdl_alt42g_additional_info | ✅ (2025-01-27 추가) |
| `favorite_fruit` | mdl_alt42g_additional_info | ✅ (2025-01-27 추가) |
| `favorite_snack` | mdl_alt42g_additional_info | ✅ (2025-01-27 추가) |
| `hobbies_interests` | mdl_alt42g_additional_info | ✅ (2025-01-27 추가) |

### 미매핑 필드 (추가 필요)

| 필드명 | 테이블 | 우선순위 | 비고 |
|--------|--------|----------|------|
| `weekly_hours` | mdl_alt42g_learning_progress | 높음 | report_service.php에서 사용 중 |
| `academy_experience` | mdl_alt42g_learning_progress | 높음 | report_service.php에서 사용 중 |
| `notes` | mdl_alt42g_learning_progress | 중간 | report_service.php에서 사용 중 |
| `short_term_goal` | mdl_alt42g_learning_goals | 높음 | report_service.php에서 사용 중 |
| `mid_term_goal` | mdl_alt42g_learning_goals | 높음 | report_service.php에서 사용 중 |
| `long_term_goal` | mdl_alt42g_learning_goals | 높음 | report_service.php에서 사용 중 |
| `goal_note` | mdl_alt42g_learning_goals | 중간 | report_service.php에서 사용 중 |
| `fandom_yn` | mdl_alt42g_additional_info | 낮음 | report_service.php에서 사용 중 |
| `data_consent` | mdl_alt42g_additional_info | 낮음 | report_service.php에서 사용 중 |

---

## 다음 단계

1. ✅ `favorite_food`, `favorite_fruit`, `favorite_snack`, `hobbies_interests` 매핑 완료 (2025-01-27)
2. ✅ `weekly_hours`, `academy_experience`, `notes` 매핑 완료 (2025-01-27)
3. ✅ `short_term_goal`, `mid_term_goal`, `long_term_goal`, `goal_note` 매핑 완료 (2025-01-27)
4. ✅ `fandom_yn`, `data_consent` 매핑 완료 (2025-01-27)

## 매핑 완료 상태

### data_access.php
- ✅ `getOnboardingContext()` 함수에 모든 필드 추가 완료
- ✅ MATHKING DB 3개 테이블 조회 로직 추가 완료:
  - `mdl_alt42g_additional_info`
  - `mdl_alt42g_learning_progress`
  - `mdl_alt42g_learning_goals`

### dataindex_user.php
- ✅ `getUserFieldValue()` 함수에 MATHKING DB 조회 로직 추가 완료
- ✅ 테이블별 분기 처리 로직 추가 완료

## 추가 테이블 목록 (향후 매핑 가능)

사용자가 제공한 전체 테이블 목록 중 agent01_onboarding과 관련될 수 있는 테이블들:

### MATHKING DB (mdl_alt42g_*)
- `mdl_alt42g_activity_categories` - 활동 카테고리
- `mdl_alt42g_activity_items` - 활동 항목
- `mdl_alt42g_activity_selections` - 활동 선택
- `mdl_alt42g_emotion_categories` - 감정 카테고리
- `mdl_alt42g_emotion_items` - 감정 항목
- `mdl_alt42g_emotion_selections` - 감정 선택
- `mdl_alt42g_emotion_surveys` - 감정 설문
- `mdl_alt42g_exam_strategies` - 시험 전략
- `mdl_alt42g_exam_strategy_meta` - 시험 전략 메타
- `mdl_alt42g_goal_analysis` - 목표 분석
- `mdl_alt42g_interaction_scenarios` - 상호작용 시나리오
- `mdl_alt42g_onboarding_status` - 온보딩 상태
- `mdl_alt42g_problem_redefinitions` - 문제 재정의
- `mdl_alt42g_teacher_discussions` - 교사 토론
- `mdl_alt42g_teacher_feedback` - 교사 피드백

### Moodle DB (mdl_alt42o_*)
- `mdl_alt42o_goal_tracking` - 목표 추적
- `mdl_alt42o_learning_history` - 학습 이력
- `mdl_alt42o_learning_style_analysis` - 학습 스타일 분석
- `mdl_alt42o_presets` - 프리셋
- `mdl_alt42o_promptsummary` - 프롬프트 요약
- `mdl_alt42o_sync_log` - 동기화 로그

### Moodle DB (mdl_alt42_*)
- `mdl_alt42_students` - 학생 기본 정보
- `mdl_alt42_student_activity` - 학생 활동
- `mdl_alt42_student_biometrics` - 학생 생체정보
- `mdl_alt42_student_state_cache` - 학생 상태 캐시
- `mdl_alt42_user_goals` - 사용자 목표
- `mdl_alt42_goinghome` - 하교 전 리포트 (JSON)
- `mdl_alt42_goinghome_reports` - 하교 전 리포트 저장

### 기타 (mdl_abessi_*)
- `mdl_abessi_mbtilog` - ✅ 이미 매핑됨
- 기타 abessi 테이블들은 다른 에이전트에서 관리

**참고**: 위 테이블들은 필요에 따라 추가 매핑이 가능합니다.

---

**문서 작성자**: Agent 01 Onboarding System  
**문서 위치**: `alt42/orchestration/agents/agent01_onboarding/DATABASE_TABLE_MAPPING.md`  
**마지막 업데이트**: 2025-01-27

