# System Data Dictionary
## ALT42 Orchestration System - 데이터베이스 테이블 및 필드 매핑

**생성일**: 2025-01-27  
**버전**: 1.0  
**설명**: ALT42 Orchestration 시스템의 모든 에이전트가 사용하는 실제 DB 테이블과 필드 정보를 정리한 문서입니다.

---

## 목차
1. [공통 테이블](#공통-테이블)
2. [에이전트별 데이터 매핑](#에이전트별-데이터-매핑)
3. [테이블별 상세 필드 정보](#테이블별-상세-필드-정보)

---

## 공통 테이블

### Moodle 기본 테이블
- **mdl_user**: 사용자 기본 정보
- **mdl_logstore_standard_log**: 시스템 로그 (일부 에이전트에서 활용)

---

## 에이전트별 데이터 매핑

### Agent 01 - Onboarding (온보딩)
**파일**: `agent01_onboarding/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_user` | `id`, `firstname`, `lastname`, `email`, `lastaccess` | 학생 기본 정보 |
| `mdl_abessi_mbtilog` | `userid`, `mbti`, `timecreated` | MBTI 정보 |
| `mdl_alt42_student_profiles` | `user_id`, `profile_data` (JSON), `math_level`, `math_confidence`, `study_style` | 학생 프로필 정보 |
| `mdl_alt42_onboarding` | `user_id`, `math_level`, `math_confidence`, `exam_style`, `parent_style`, `study_hours_per_week`, `concept_progress`, `advanced_progress`, `study_style`, `goals` (JSON) | 온보딩 정보 |

---

### Agent 02 - Exam Schedule (시험 일정)
**파일**: `agent02_exam_schedule/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_alt42_exam_schedule` | `userid`, `exam_date`, `exam_name`, `target_score`, `d_day` | 시험 일정 정보 |

---

### Agent 03 - Goals Analysis (목표 분석)
**파일**: `agent03_goals_analysis/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_alt42g_student_goals` | `userid`, `goal_type`, `status`, `progress`, `timecreated` | 학생 목표 정보 |
| `mdl_alt42g_goal_analysis` | `userid`, `analysis_type`, `analysis_result`, `effectiveness_score`, `created_at` | 목표 분석 결과 |
| `mdl_alt42g_learning_sessions` | (참조됨, 상세 필드 미확인) | 학습 세션 기록 |
| `mdl_alt42g_pomodoro_sessions` | (참조됨, 상세 필드 미확인) | 포모도르 세션 기록 |
| `mdl_alt42g_curriculum_progress` | (참조됨, 상세 필드 미확인) | 커리큘럼 진행도 |
| `mdl_alt42g_completed_units` | (참조됨, 상세 필드 미확인) | 완료된 단원 |

---

### Agent 04 - Problem Activity (문제 활동)
**파일**: `agent04_problem_activity/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_alt42_student_activity` | (참조됨, 상세 필드 미확인) | 학생 활동 정보 |

---

### Agent 05 - Learning Emotion (학습 감정)
**파일**: `agent05_learning_emotion/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| (데이터 소스 확인 필요) | - | 학습 감정 데이터 |

---

### Agent 06 - Teacher Feedback (교사 피드백)
**파일**: `agent06_teacher_feedback/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_todayplans` | `userid`, `plan1`~`plan16`, `status01`~`status16`, `timecreated` | 교사 피드백 데이터 (수학일기) |

---

### Agent 07 - Interaction Targeting (개입 타겟팅)
**파일**: `agent07_interaction_targeting/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| (다른 에이전트 데이터 종합 사용) | - | 다른 에이전트들의 분석 결과를 종합하여 사용 |

---

### Agent 08 - Calmness (침착도)
**파일**: `agent08_calmness/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_today` | `userid`, `type`, `score`, `timecreated`, `id` | 침착도 계산 데이터 (오늘목표/검사요청/주간목표) |

---

### Agent 09 - Learning Management (학습 관리)
**파일**: `agent09_learning_management/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_user` | `id`, `firstname`, `lastname` | 학생 기본 정보 |
| `mdl_alt42g_goal_analysis` | `userid`, `effectiveness_score`, `created_at` | 목표 달성률 |
| `mdl_alt42g_pomodoro_sessions` | `userid`, `status`, `timecreated`, `duration` | 포모도르 완성률 |
| `mdl_abessi_messages` | `userid`, `contentstype`, `status`, `timecreated` | 오답노트 패턴 (contentstype=2) |

---

### Agent 10 - Concept Notes (개념 노트)
**파일**: `agent10_concept_notes/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_messages` | `userid`, `contentstype`, `nstroke`, `tlaststroke`, `timecreated`, `contentstitle`, `url`, `usedtime` | 개념노트 데이터 (contentstype=1) |

---

### Agent 11 - Problem Notes (문제 노트)
**파일**: `agent11_problem_notes/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_messages` | `userid`, `contentstype`, `nstroke`, `tlaststroke`, `timecreated`, `contentstitle`, `wboardid`, `usedtime`, `status` | 문제노트 데이터 (contentstype=2, status: attempt/begin/exam/complete/review) |

---

### Agent 12 - Rest Routine (휴식 루틴)
**파일**: `agent12_rest_routine/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_breaktimelog` | `userid`, `duration`, `timecreated` | 휴식 버튼 클릭 데이터 |

---

### Agent 13 - Learning Dropout (학습 이탈)
**파일**: `agent13_learning_dropout/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_today` | `userid`, `ninactive`, `nlazy`, `activetime`, `checktime`, `status`, `type`, `timecreated`, `timemodified` | 목표/검사 데이터 (24시간 내) |
| `mdl_abessi_messages` | `userid`, `timemodified`, `tlaststroke` | 보드/노트 활동 데이터 (24시간 내) |
| `mdl_abessi_tracking` | `userid`, `status`, `timecreated`, `duration`, `text` | 타임스캐폴딩 데이터 |
| `mdl_abessi_indicators` | `userid`, `npomodoro`, `kpomodoro`, `pmresult`, `timecreated` | 포모도르 요약 데이터 |

---

### Agent 14 - Current Position (현재 위치)
**파일**: `agent14_current_position/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_todayplans` | `userid`, `tbegin`, `plan1`~`plan16`, `due1`~`due16`, `tend01`~`tend16`, `status01`~`status16`, `timecreated` | 수학일기 데이터 (12시간 내) |

---

### Agent 15 - Problem Redefinition (문제 재정의)
**파일**: `agent15_problem_redefinition/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_alt42_exam_schedule` | `userid`, `exam_date`, `d_day`, `exam_name` | 시험 일정 |
| `mdl_alt42g_goal_analysis` | `userid`, `created_at` | 목표 분석 |
| `mdl_abessi_todayplans` | `userid`, `timecreated` | 교사 피드백 |

---

### Agent 16 - Interaction Preparation (상호작용 준비)
**파일**: `agent16_interaction_preparation/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_agent16_interaction_scenarios` | `id`, `userid`, `guide_mode`, `vibe_coding_prompt`, `db_tracking_prompt`, `scenario`, `created_at`, `updated_at` | 상호작용 시나리오 |

---

### Agent 17 - Remaining Activities (남은 활동)
**파일**: `agent17_remaining_activities/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_todayplans` | `userid`, `plan1`~`plan16`, `tend01`~`tend16`, `timecreated` | 수학일기 데이터 |
| `mdl_alt42g_goal_analysis` | (참조됨) | 목표 분석 |
| `mdl_alt42g_student_goals` | (참조됨) | 학생 목표 |
| `mdl_alt42_student_activity` | (참조됨) | 학생 활동 |

---

### Agent 18 - Signature Routine (시그너처 루틴)
**파일**: `agent18_signature_routine/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_user` | `id`, `firstname`, `lastname` | 학생 기본 정보 |
| `mdl_abessi_mbtilog` | `userid`, `mbti`, `timecreated` | MBTI 정보 |
| `mdl_alt42g_pomodoro_sessions` | `userid`, `duration`, `timecreated` | 포모도르 세션 데이터 |

---

### Agent 19 - Interaction Content (상호작용 컨텐츠)
**파일**: `agent19_interaction_content/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_user` | `id`, `firstname`, `lastname` | 학생 기본 정보 |
| `mdl_abessi_mbtilog` | `userid`, `mbti`, `timecreated` | MBTI 정보 |

---

### Agent 20 - Intervention Preparation (개입 준비)
**파일**: `agent20_intervention_preparation/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_user` | `id`, `firstname`, `lastname` | 학생 기본 정보 |
| (다른 에이전트 데이터 종합 사용) | - | 이전 단계의 모든 분석 결과 종합 |

---

### Agent 21 - Intervention Execution (개입 실행)
**파일**: `agent21_intervention_execution/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_abessi_today` | `userid`, `ninactive`, `npomodoro`, `type`, `timecreated` | 이탈 위험도 (24시간 내) |
| `mdl_abessi_todayplans` | `userid`, `timecreated` | 현재 진행 위치 (12시간 내) |
| `mdl_user` | `id`, `firstname`, `lastname` | 학생 기본 정보 |
| `mdl_abessi_mbtilog` | `userid`, `mbti` | MBTI 정보 |

---

### Agent 22 - Module Improvement (모듈 개선)
**파일**: `agent22_module_improvement/rules/data.php`

| 테이블명 | 주요 필드 | 용도 |
|---------|---------|------|
| `mdl_user` | `id`, `firstname`, `lastname` | 학생 기본 정보 |
| (로그 기반, 직접 DB 테이블 없음) | - | 에이전트 실행 로그 및 룰 파일 분석 |

---

## 테이블별 상세 필드 정보

### mdl_user
**용도**: Moodle 기본 사용자 테이블

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `id` | INT | 사용자 ID (PK) | Agent 01, 09, 18, 19, 20, 21, 22 |
| `firstname` | VARCHAR | 이름 | Agent 01, 09, 18, 19, 20, 21, 22 |
| `lastname` | VARCHAR | 성 | Agent 01, 09, 18, 19, 20, 21, 22 |
| `email` | VARCHAR | 이메일 | Agent 01 |
| `lastaccess` | INT | 마지막 접근 시간 | Agent 01 |

---

### mdl_abessi_mbtilog
**용도**: MBTI 로그 정보

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 01, 18, 19, 21 |
| `mbti` | VARCHAR | MBTI 유형 | Agent 01, 18, 19, 21 |
| `timecreated` | INT | 생성 시간 | Agent 01, 18, 19, 21 |

---

### mdl_alt42_student_profiles
**용도**: 학생 프로필 정보

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `user_id` | INT | 사용자 ID (FK) | Agent 01 |
| `profile_data` | JSON | 프로필 데이터 (JSON) | Agent 01 |
| `math_level` | VARCHAR | 수학 수준 | Agent 01 |
| `math_confidence` | DECIMAL | 수학 자신감 | Agent 01 |
| `study_style` | VARCHAR | 학습 스타일 | Agent 01 |

---

### mdl_alt42_onboarding
**용도**: 온보딩 정보

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `user_id` | INT | 사용자 ID (FK) | Agent 01 |
| `math_level` | VARCHAR | 수학 수준 | Agent 01 |
| `math_confidence` | DECIMAL | 수학 자신감 | Agent 01 |
| `exam_style` | VARCHAR | 시험 스타일 | Agent 01 |
| `parent_style` | VARCHAR | 부모 스타일 | Agent 01 |
| `study_hours_per_week` | INT | 주당 학습 시간 | Agent 01 |
| `concept_progress` | VARCHAR | 개념 진도 | Agent 01 |
| `advanced_progress` | VARCHAR | 심화 진도 | Agent 01 |
| `study_style` | VARCHAR | 학습 스타일 | Agent 01 |
| `goals` | JSON | 목표 정보 (JSON) | Agent 01 |

---

### mdl_alt42_exam_schedule
**용도**: 시험 일정 정보

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 02, 15 |
| `exam_date` | INT | 시험 날짜 (timestamp) | Agent 02, 15 |
| `exam_name` | VARCHAR | 시험명 | Agent 02, 15 |
| `target_score` | INT | 목표 점수 | Agent 02 |
| `d_day` | INT | D-day | Agent 02, 15 |

---

### mdl_alt42g_student_goals
**용도**: 학생 목표 정보

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 03, 17 |
| `goal_type` | VARCHAR | 목표 유형 | Agent 03 |
| `status` | VARCHAR | 상태 (completed 등) | Agent 03 |
| `progress` | DECIMAL | 진행률 | Agent 03 |
| `timecreated` | INT | 생성 시간 | Agent 03 |

---

### mdl_alt42g_goal_analysis
**용도**: 목표 분석 결과

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 03, 09, 15, 17 |
| `analysis_type` | VARCHAR | 분석 유형 | Agent 03 |
| `analysis_result` | TEXT | 분석 결과 | Agent 03 |
| `effectiveness_score` | DECIMAL | 효과성 점수 | Agent 03, 09 |
| `created_at` | INT | 생성 시간 | Agent 03, 15 |

---

### mdl_alt42g_pomodoro_sessions
**용도**: 포모도르 세션 기록

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 03, 09, 18 |
| `status` | VARCHAR | 상태 (completed 등) | Agent 09 |
| `timecreated` | INT | 생성 시간 | Agent 09, 18 |
| `duration` | INT | 지속 시간 (초) | Agent 09, 18 |

---

### mdl_abessi_todayplans
**용도**: 수학일기/교사 피드백 데이터

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 06, 14, 15, 17, 21 |
| `tbegin` | INT | 시작 시간 (timestamp) | Agent 14 |
| `plan1` ~ `plan16` | VARCHAR | 계획 내용 (1~16번) | Agent 06, 14, 17 |
| `due1` ~ `due16` | INT | 예상 소요 시간 (분) | Agent 14 |
| `tend01` ~ `tend16` | INT | 종료 시간 (timestamp) | Agent 14, 17 |
| `status01` ~ `status16` | VARCHAR | 상태 (매우만족/만족/불만족 등) | Agent 06, 14 |
| `timecreated` | INT | 생성 시간 | Agent 06, 14, 15, 17, 21 |

---

### mdl_abessi_today
**용도**: 오늘 목표/검사 데이터

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `id` | INT | 레코드 ID (PK) | Agent 08, 13 |
| `userid` | INT | 사용자 ID (FK) | Agent 08, 13 |
| `type` | VARCHAR | 유형 (오늘목표/검사요청/주간목표) | Agent 08, 13 |
| `score` | DECIMAL | 점수 | Agent 08 |
| `ninactive` | INT | 비활성 횟수 | Agent 13 |
| `nlazy` | INT | 게으름 횟수 | Agent 13 |
| `activetime` | INT | 활동 시간 | Agent 13 |
| `checktime` | INT | 확인 시간 | Agent 13 |
| `status` | VARCHAR | 상태 | Agent 13 |
| `timecreated` | INT | 생성 시간 | Agent 08, 13 |
| `timemodified` | INT | 수정 시간 | Agent 13 |

---

### mdl_abessi_messages
**용도**: 화이트보드 메시지/노트 데이터

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `id` | INT | 레코드 ID (PK) | Agent 10, 11 |
| `userid` | INT | 사용자 ID (FK) | Agent 09, 10, 11, 13 |
| `contentstype` | INT | 콘텐츠 유형 (1=개념노트, 2=문제노트) | Agent 09, 10, 11 |
| `nstroke` | INT | 총 필기량 | Agent 10, 11 |
| `tlaststroke` | INT | 마지막 필기 시점 (timestamp) | Agent 10, 11, 13 |
| `timecreated` | INT | 생성 시간 | Agent 10, 11 |
| `timemodified` | INT | 수정 시간 | Agent 13 |
| `contentstitle` | VARCHAR | 콘텐츠 제목 | Agent 10, 11 |
| `url` | VARCHAR | URL | Agent 10 |
| `wboardid` | INT | 화이트보드 ID | Agent 11 |
| `usedtime` | INT | 사용 시간 (초) | Agent 10, 11 |
| `status` | VARCHAR | 상태 (attempt/begin/exam/complete/review) | Agent 09, 11 |

---

### mdl_abessi_breaktimelog
**용도**: 휴식 시간 로그

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 12 |
| `duration` | INT | 휴식 시간 (초) | Agent 12 |
| `timecreated` | INT | 생성 시간 | Agent 12 |

---

### mdl_abessi_tracking
**용도**: 타임스캐폴딩 데이터

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 13 |
| `status` | VARCHAR | 상태 | Agent 13 |
| `timecreated` | INT | 생성 시간 | Agent 13 |
| `duration` | INT | 지속 시간 | Agent 13 |
| `text` | TEXT | 텍스트 내용 | Agent 13 |

---

### mdl_abessi_indicators
**용도**: 포모도르 요약 데이터

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `userid` | INT | 사용자 ID (FK) | Agent 13 |
| `npomodoro` | INT | 포모도르 횟수 | Agent 13 |
| `kpomodoro` | INT | 완료 포모도르 횟수 | Agent 13 |
| `pmresult` | VARCHAR | 포모도르 결과 | Agent 13 |
| `timecreated` | INT | 생성 시간 | Agent 13 |

---

### mdl_agent16_interaction_scenarios
**용도**: 상호작용 시나리오

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| `id` | INT | 레코드 ID (PK) | Agent 16 |
| `userid` | INT | 사용자 ID (FK) | Agent 16 |
| `guide_mode` | VARCHAR | 가이드 모드 (세계관) | Agent 16 |
| `vibe_coding_prompt` | TEXT | 바이브 코딩 프롬프트 | Agent 16 |
| `db_tracking_prompt` | TEXT | DB 추적 프롬프트 | Agent 16 |
| `scenario` | TEXT | 시나리오 내용 | Agent 16 |
| `created_at` | INT | 생성 시간 | Agent 16 |
| `updated_at` | INT | 수정 시간 | Agent 16 |

---

### mdl_alt42_student_activity
**용도**: 학생 활동 정보

| 필드명 | 타입 | 설명 | 사용 에이전트 |
|--------|------|------|--------------|
| (상세 필드 미확인) | - | 학생 활동 데이터 | Agent 04, 17 |

---

## 데이터 흐름 요약

### 주요 데이터 소스
1. **학생 기본 정보**: `mdl_user`, `mdl_abessi_mbtilog`
2. **학습 활동**: `mdl_abessi_today`, `mdl_abessi_todayplans`, `mdl_abessi_messages`
3. **목표 및 분석**: `mdl_alt42g_student_goals`, `mdl_alt42g_goal_analysis`
4. **시험 및 일정**: `mdl_alt42_exam_schedule`
5. **온보딩**: `mdl_alt42_onboarding`, `mdl_alt42_student_profiles`
6. **상호작용**: `mdl_agent16_interaction_scenarios`

### 에이전트 간 데이터 공유 패턴
- **Agent 01 (온보딩)**: 다른 에이전트들이 학생 프로필 정보를 참조
- **Agent 13 (학습 이탈)**: 여러 테이블의 데이터를 종합하여 위험도 계산
- **Agent 14 (현재 위치)**: `mdl_abessi_todayplans`를 활용하여 진행 상태 파악
- **Agent 15~22**: 이전 단계 에이전트들의 분석 결과를 종합하여 사용

---

## 참고사항

1. **JSON 필드**: 일부 테이블은 JSON 필드를 사용하여 유연한 데이터 저장
   - `mdl_alt42_student_profiles.profile_data`
   - `mdl_alt42_onboarding.goals`

2. **동적 필드**: `mdl_abessi_todayplans` 테이블은 `plan1`~`plan16`, `status01`~`status16` 등 동적 필드 사용

3. **타임스탬프**: 대부분의 테이블은 `timecreated` 필드를 사용하여 생성 시간 기록

4. **에이전트별 특화 테이블**: 
   - `mdl_agent16_interaction_scenarios`: Agent 16 전용
   - `mdl_alt42g_*`: 목표 관련 에이전트들 전용

5. **TODO 항목**: 일부 에이전트는 아직 구현되지 않은 테이블을 참조하고 있음 (주석으로 표시됨)

---

**마지막 업데이트**: 2025-01-27  
**작성자**: AI Assistant  
**검토 필요**: 각 테이블의 실제 스키마와 일치 여부 확인 필요

