# Agent 21 - Intervention Execution DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 21 - Intervention Execution (개입 실행)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 개입 실행 및 효과 추적

**주요 기능**:
- 이탈 위험도 모니터링 (24시간 내)
- 현재 진행 위치 파악 (12시간 내)
- 개입 실행 및 효과 추적

---

## 데이터베이스 구조

### 1. 오늘 목표/검사 테이블: `mdl_abessi_today`

**목적**: 이탈 위험도 분석 (24시간 내)

**주요 필드**:
- `userid`: 사용자 ID
- `ninactive`: 비활성 횟수
- `npomodoro`: 포모도르 횟수
- `type`: 유형
- `timecreated`: 생성 시간

---

### 2. 수학일기 테이블: `mdl_abessi_todayplans`

**목적**: 현재 진행 위치 파악 (12시간 내)

**주요 필드**:
- `userid`: 사용자 ID
- `timecreated`: 생성 시간

---

### 3. 사용자 테이블: `mdl_user`

**목적**: 학생 기본 정보

**주요 필드**:
- `id`: 사용자 ID
- `firstname`: 이름
- `lastname`: 성

---

### 4. MBTI 로그 테이블: `mdl_abessi_mbtilog`

**목적**: MBTI 정보

**주요 필드**:
- `userid`: 사용자 ID
- `mbti`: MBTI 유형

---

## 데이터 흐름

```
[개입 실행 요청]
  ├─→ mdl_abessi_today (이탈 위험도, 24시간 내)
  ├─→ mdl_abessi_todayplans (현재 위치, 12시간 내)
  ├─→ mdl_user (학생 기본 정보)
  └─→ mdl_abessi_mbtilog (MBTI 정보)
  ↓
[개입 실행]
  ├─→ 이탈 위험도 확인
  ├─→ 현재 위치 확인
  └─→ 개입 방법 결정
  ↓
[개입 효과 추적]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `dropout_risk` | mdl_abessi_today | ninactive, npomodoro | 이탈 위험도 |
| `current_position` | mdl_abessi_todayplans | timecreated | 현재 위치 |
| `student_info` | mdl_user | firstname, lastname | 학생 정보 |
| `mbti_type` | mdl_abessi_mbtilog | mbti | MBTI 유형 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent21_intervention_execution.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 21 Intervention Execution System  
**문서 위치**: `alt42/orchestration/agents/agent21_intervention_execution/DB_REPORT.md`

