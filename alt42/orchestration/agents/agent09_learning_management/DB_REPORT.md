# Agent 09 - Learning Management DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 09 - Learning Management (학습 관리)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 학습 관리 지표를 종합 분석하여 학습 효율성 평가

**주요 기능**:
- 목표 달성률 분석
- 포모도르 완성률 분석
- 오답노트 패턴 분석
- 학습 효율성 종합 평가

---

## 데이터베이스 구조

### 1. 목표 분석 테이블: `mdl_alt42g_goal_analysis`

**목적**: 목표 달성률 분석 (Agent 03과 공유)

**주요 필드**:
- `userid`: 사용자 ID
- `effectiveness_score`: 효과성 점수
- `created_at`: 생성 시간

---

### 2. 포모도르 세션 테이블: `mdl_alt42g_pomodoro_sessions`

**목적**: 포모도르 완성률 분석

**주요 필드**:
- `userid`: 사용자 ID
- `status`: 상태 (completed/incomplete)
- `timecreated`: 생성 시간
- `duration`: 지속 시간 (초)

---

### 3. 메시지 테이블: `mdl_abessi_messages`

**목적**: 오답노트 패턴 분석 (contentstype=2)

**주요 필드**:
- `userid`: 사용자 ID
- `contentstype`: 콘텐츠 유형 (2=문제노트)
- `status`: 상태 (attempt/begin/exam/complete/review)
- `timecreated`: 생성 시간

---

### 4. 사용자 테이블: `mdl_user`

**목적**: 학생 기본 정보

**주요 필드**:
- `id`: 사용자 ID
- `firstname`: 이름
- `lastname`: 성

---

## 데이터 흐름

```
[학습 활동 데이터 수집]
  ├─→ mdl_alt42g_goal_analysis (목표 달성률)
  ├─→ mdl_alt42g_pomodoro_sessions (포모도르 완성률)
  └─→ mdl_abessi_messages (오답노트 패턴)
  ↓
[학습 효율성 종합 분석]
  ↓
[학습 관리 리포트 생성]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `goal_completion_rate` | mdl_alt42g_goal_analysis | effectiveness_score | 목표 달성률 |
| `pomodoro_completion_rate` | mdl_alt42g_pomodoro_sessions | status='completed' | 포모도르 완성률 |
| `error_note_pattern` | mdl_abessi_messages | contentstype=2, status | 오답노트 패턴 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent09_learning_management.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 09 Learning Management System  
**문서 위치**: `alt42/orchestration/agents/agent09_learning_management/DB_REPORT.md`

