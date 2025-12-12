# Agent 18 - Signature Routine DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 18 - Signature Routine (시그너처 루틴)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 시그너처 루틴(고유 학습 패턴) 분석

**주요 기능**:
- MBTI 기반 루틴 분석
- 포모도르 세션 패턴 분석
- 개인화된 루틴 제안

---

## 데이터베이스 구조

### 1. 사용자 테이블: `mdl_user`

**목적**: 학생 기본 정보

**주요 필드**:
- `id`: 사용자 ID
- `firstname`: 이름
- `lastname`: 성

---

### 2. MBTI 로그 테이블: `mdl_abessi_mbtilog`

**목적**: MBTI 정보

**주요 필드**:
- `userid`: 사용자 ID
- `mbti`: MBTI 유형
- `timecreated`: 생성 시간

---

### 3. 포모도르 세션 테이블: `mdl_alt42g_pomodoro_sessions`

**목적**: 포모도르 세션 데이터

**주요 필드**:
- `userid`: 사용자 ID
- `duration`: 지속 시간 (초)
- `timecreated`: 생성 시간

---

## 데이터 흐름

```
[시그너처 루틴 분석 요청]
  ├─→ mdl_user (학생 기본 정보)
  ├─→ mdl_abessi_mbtilog (MBTI 정보)
  └─→ mdl_alt42g_pomodoro_sessions (포모도르 세션)
  ↓
[루틴 패턴 분석]
  ├─→ MBTI 기반 루틴 분석
  └─→ 포모도르 패턴 분석
  ↓
[시그너처 루틴 제안]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `mbti_type` | mdl_abessi_mbtilog | mbti | MBTI 유형 |
| `pomodoro_duration` | mdl_alt42g_pomodoro_sessions | duration | 포모도르 지속 시간 |
| `pomodoro_pattern` | mdl_alt42g_pomodoro_sessions | timecreated | 포모도르 패턴 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent18_signature_routine.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 18 Signature Routine System  
**문서 위치**: `alt42/orchestration/agents/agent18_signature_routine/DB_REPORT.md`

