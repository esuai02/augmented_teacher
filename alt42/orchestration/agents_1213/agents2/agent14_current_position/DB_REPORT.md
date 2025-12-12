# Agent 14 - Current Position DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 14 - Current Position (현재 위치)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 현재 학습 진행 위치 파악

**주요 기능**:
- 수학일기 데이터 분석 (12시간 내)
- 진행 중인 계획 파악
- 현재 위치 기반 학습 조언 제공

---

## 데이터베이스 구조

### 1. 수학일기 테이블: `mdl_abessi_todayplans`

**목적**: 수학일기 데이터 (12시간 내)

**주요 필드**:
- `userid`: 사용자 ID
- `tbegin`: 시작 시간 (timestamp)
- `plan1` ~ `plan16`: 계획 내용 (1~16번)
- `due1` ~ `due16`: 예상 소요 시간 (분)
- `tend01` ~ `tend16`: 종료 시간 (timestamp)
- `status01` ~ `status16`: 상태 (매우만족/만족/불만족 등)
- `timecreated`: 생성 시간

---

## 데이터 흐름

```
[수학일기 데이터 조회 (12시간 내)]
  ↓
[mdl_abessi_todayplans] → 현재 진행 중인 계획 파악
  ↓
[Agent 14 분석 요청]
  ↓
[현재 위치 분석]
  ├─→ 진행 중인 계획 파악
  ├─→ 완료된 계획 파악
  └─→ 남은 계획 파악
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `current_plans` | mdl_abessi_todayplans | plan1~plan16 | 현재 계획 목록 |
| `plan_status` | mdl_abessi_todayplans | status01~status16 | 계획 상태 |
| `plan_due_time` | mdl_abessi_todayplans | due1~due16 | 예상 소요 시간 |
| `plan_end_time` | mdl_abessi_todayplans | tend01~tend16 | 종료 시간 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent14_current_position.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 14 Current Position System  
**문서 위치**: `alt42/orchestration/agents/agent14_current_position/DB_REPORT.md`

