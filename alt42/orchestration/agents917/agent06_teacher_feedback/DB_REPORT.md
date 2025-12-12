# Agent 06 - Teacher Feedback DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 06 - Teacher Feedback (교사 피드백)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 교사의 피드백 데이터(수학일기)를 분석하여 학습 지도 전략 제공

**주요 기능**:
- 수학일기 데이터 분석
- 교사 피드백 패턴 파악
- 학습 계획 조정 제안

---

## 데이터베이스 구조

### 1. 수학일기 테이블: `mdl_abessi_todayplans`

**목적**: 교사 피드백 데이터(수학일기) 저장

#### 테이블 스키마

```sql
-- mdl_abessi_todayplans 테이블 구조
-- 수학일기 데이터 (교사 피드백 포함)

주요 필드:
- userid: 사용자 ID (FK)
- plan1 ~ plan16: 계획 내용 (1~16번)
- status01 ~ status16: 상태 (매우만족/만족/불만족 등)
- timecreated: 생성 시간
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `userid` | INT | 사용자 ID (FK) | 1603 |
| `plan1` ~ `plan16` | VARCHAR | 계획 내용 (1~16번) | "수학 문제 10개 풀기" |
| `status01` ~ `status16` | VARCHAR | 상태 | "매우만족", "만족", "불만족" |
| `timecreated` | INT | 생성 시간 (Unix timestamp) | 1735689600 |

---

## 데이터 흐름

### 1. 교사 피드백 분석 프로세스

```
[교사 피드백 입력]
  ↓
[mdl_abessi_todayplans] → 수학일기 데이터 저장
  ↓
[Agent 06 분석 요청]
  ↓
[피드백 패턴 분석]
  ├─→ 계획별 만족도 분석
  ├─→ 피드백 빈도 분석
  └─→ 학습 조언 생성
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `feedback_status` | mdl_abessi_todayplans | status01~status16 | 피드백 상태 |
| `plan_content` | mdl_abessi_todayplans | plan1~plan16 | 계획 내용 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent06_teacher_feedback.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 06 Teacher Feedback System  
**문서 위치**: `alt42/orchestration/agents/agent06_teacher_feedback/DB_REPORT.md`

