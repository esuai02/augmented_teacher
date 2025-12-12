# Agent 15 - Problem Redefinition DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 15 - Problem Redefinition (문제 재정의)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 문제를 재정의하여 학습 효과 향상

**주요 기능**:
- 시험 일정 기반 문제 재정의
- 목표 분석 기반 문제 재정의
- 교사 피드백 기반 문제 재정의

---

## 데이터베이스 구조

### 1. 시험 일정 테이블: `mdl_alt42_exam_schedule`

**목적**: 시험 일정 정보 (Agent 02와 공유)

**주요 필드**:
- `userid`: 사용자 ID
- `exam_date`: 시험 날짜
- `d_day`: D-day
- `exam_name`: 시험명

---

### 2. 목표 분석 테이블: `mdl_alt42g_goal_analysis`

**목적**: 목표 분석 결과 (Agent 03과 공유)

**주요 필드**:
- `userid`: 사용자 ID
- `created_at`: 생성 시간

---

### 3. 수학일기 테이블: `mdl_abessi_todayplans`

**목적**: 교사 피드백 데이터

**주요 필드**:
- `userid`: 사용자 ID
- `timecreated`: 생성 시간

---

## 데이터 흐름

```
[문제 재정의 요청]
  ├─→ mdl_alt42_exam_schedule (시험 일정)
  ├─→ mdl_alt42g_goal_analysis (목표 분석)
  └─→ mdl_abessi_todayplans (교사 피드백)
  ↓
[문제 재정의 분석]
  ↓
[재정의된 문제 제안]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `exam_schedule` | mdl_alt42_exam_schedule | exam_date, d_day | 시험 일정 |
| `goal_analysis` | mdl_alt42g_goal_analysis | created_at | 목표 분석 |
| `teacher_feedback` | mdl_abessi_todayplans | timecreated | 교사 피드백 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent15_problem_redefinition.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 15 Problem Redefinition System  
**문서 위치**: `alt42/orchestration/agents/agent15_problem_redefinition/DB_REPORT.md`

