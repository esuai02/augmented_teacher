# Agent 11 - Problem Notes DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 11 - Problem Notes (문제 노트)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 문제 노트 작성 패턴 분석

**주요 기능**:
- 문제 노트 작성 활동 추적
- 문제 풀이 상태 분석
- 문제 학습 효율성 평가

---

## 데이터베이스 구조

### 1. 메시지 테이블: `mdl_abessi_messages`

**목적**: 문제 노트 데이터 저장 (contentstype=2)

#### 테이블 스키마

```sql
-- mdl_abessi_messages 테이블 구조
-- 문제 노트: contentstype = 2

주요 필드:
- id: 레코드 ID (PK)
- userid: 사용자 ID (FK)
- contentstype: 콘텐츠 유형 (2=문제노트)
- nstroke: 총 필기량
- tlaststroke: 마지막 필기 시점
- timecreated: 생성 시간
- contentstitle: 콘텐츠 제목
- wboardid: 화이트보드 ID
- usedtime: 사용 시간 (초)
- status: 상태 (attempt/begin/exam/complete/review)
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `id` | INT | 레코드 ID (PK) | 12345 |
| `userid` | INT | 사용자 ID (FK) | 1603 |
| `contentstype` | INT | 콘텐츠 유형 | 2 (문제노트) |
| `nstroke` | INT | 총 필기량 | 800 |
| `tlaststroke` | INT | 마지막 필기 시점 (Unix timestamp) | 1735689600 |
| `timecreated` | INT | 생성 시간 (Unix timestamp) | 1735689600 |
| `contentstitle` | VARCHAR | 콘텐츠 제목 | "함수 문제 풀이" |
| `wboardid` | INT | 화이트보드 ID | 123 |
| `usedtime` | INT | 사용 시간 (초) | 2400 |
| `status` | VARCHAR | 상태 | "attempt", "begin", "exam", "complete", "review" |

---

## 데이터 흐름

```
[학생 문제 노트 작성]
  ↓
[mdl_abessi_messages] → 문제 노트 데이터 저장 (contentstype=2)
  ↓
[Agent 11 분석 요청]
  ↓
[문제 노트 패턴 분석]
  ├─→ 상태별 분석 (status)
  ├─→ 필기량 분석 (nstroke)
  └─→ 사용 시간 분석 (usedtime)
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `problem_note_count` | mdl_abessi_messages | COUNT(*) (contentstype=2) | 문제 노트 개수 |
| `problem_status` | mdl_abessi_messages | status | 문제 풀이 상태 |
| `total_strokes` | mdl_abessi_messages | SUM(nstroke) | 총 필기량 |
| `total_used_time` | mdl_abessi_messages | SUM(usedtime) | 총 사용 시간 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent11_problem_notes.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 11 Problem Notes System  
**문서 위치**: `alt42/orchestration/agents/agent11_problem_notes/DB_REPORT.md`

