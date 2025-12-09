# Agent 20 - Intervention Preparation DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 20 - Intervention Preparation (개입 준비)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 개입 준비를 위한 종합 데이터 분석

**주요 기능**:
- 이전 단계의 모든 분석 결과 종합
- 개입 전략 수립
- 개입 우선순위 결정

**특징**: 직접적인 DB 테이블 없이 다른 에이전트 데이터를 종합 사용

---

## 데이터베이스 구조

### 참조 테이블 (다른 에이전트 데이터)

Agent 20은 이전 단계의 모든 에이전트 데이터를 종합하여 사용합니다:

| 에이전트 | 테이블 | 용도 |
|---------|--------|------|
| Agent 01 | mdl_alt42o_onboarding | 학생 프로필 정보 |
| Agent 02 | mdl_alt42_exam_schedule | 시험 일정 |
| Agent 03 | mdl_alt42g_goal_analysis | 목표 분석 |
| Agent 05 | (감정 데이터) | 학습 감정 상태 |
| Agent 09 | mdl_alt42g_goal_analysis | 학습 관리 데이터 |
| Agent 13 | mdl_abessi_today | 이탈 위험도 |
| Agent 14 | mdl_abessi_todayplans | 현재 위치 |
| 기타 | - | 기타 분석 결과 |

---

## 데이터 흐름

```
[개입 준비 요청]
  ↓
[모든 에이전트 데이터 수집]
  ├─→ Agent 01~19 데이터 종합
  └─→ 분석 결과 통합
  ↓
[개입 전략 수립]
  ├─→ 우선순위 결정
  └─→ 개입 방법 결정
```

---

## 필드 매핑

**참고**: Agent 20은 직접적인 DB 테이블이 없으며, 다른 에이전트의 데이터를 종합하여 사용합니다.

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent20_intervention_preparation.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 20 Intervention Preparation System  
**문서 위치**: `alt42/orchestration/agents/agent20_intervention_preparation/DB_REPORT.md`

