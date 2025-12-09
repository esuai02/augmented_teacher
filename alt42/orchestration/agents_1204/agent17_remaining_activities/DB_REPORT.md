# Agent 17 - Remaining Activities DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 17 - Remaining Activities (남은 활동)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 남은 활동 분석 및 관리

**주요 기능**:
- 수학일기 데이터 분석
- 남은 계획 파악
- 활동 완료율 계산

---

## 데이터베이스 구조

### 1. 수학일기 테이블: `mdl_abessi_todayplans`

**목적**: 수학일기 데이터

**주요 필드**:
- `userid`: 사용자 ID
- `plan1` ~ `plan16`: 계획 내용 (1~16번)
- `tend01` ~ `tend16`: 종료 시간 (timestamp)
- `timecreated`: 생성 시간

---

### 2. 목표 분석 테이블: `mdl_alt42g_goal_analysis`

**목적**: 목표 분석 결과 (참조)

---

### 3. 학생 목표 테이블: `mdl_alt42g_student_goals`

**목적**: 학생 목표 정보 (참조)

---

### 4. 학생 활동 테이블: `mdl_alt42_student_activity`

**목적**: 학생 활동 정보 (참조, Agent 04와 공유)

---

## 데이터 흐름

```
[남은 활동 분석 요청]
  ├─→ mdl_abessi_todayplans (수학일기)
  ├─→ mdl_alt42g_goal_analysis (목표 분석)
  ├─→ mdl_alt42g_student_goals (학생 목표)
  └─→ mdl_alt42_student_activity (학생 활동)
  ↓
[남은 활동 계산]
  ├─→ 완료된 계획 파악
  └─→ 남은 계획 파악
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `remaining_plans` | mdl_abessi_todayplans | plan1~plan16 (미완료) | 남은 계획 |
| `completed_plans` | mdl_abessi_todayplans | plan1~plan16 (완료) | 완료된 계획 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent17_remaining_activities.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 17 Remaining Activities System  
**문서 위치**: `alt42/orchestration/agents/agent17_remaining_activities/DB_REPORT.md`

