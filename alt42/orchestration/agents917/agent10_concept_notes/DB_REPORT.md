# Agent 10 - Concept Notes DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 10 - Concept Notes (개념 노트)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 개념 노트 작성 패턴 분석

**주요 기능**:
- 개념 노트 작성 활동 추적
- 개념 노트 품질 분석
- 개념 학습 효율성 평가

---

## 데이터베이스 구조

### 1. 메시지 테이블: `mdl_abessi_messages`

**목적**: 개념 노트 데이터 저장 (contentstype=1)

#### 테이블 스키마

```sql
-- mdl_abessi_messages 테이블 구조
-- 개념 노트: contentstype = 1

주요 필드:
- id: 레코드 ID (PK)
- userid: 사용자 ID (FK)
- contentstype: 콘텐츠 유형 (1=개념노트)
- nstroke: 총 필기량
- tlaststroke: 마지막 필기 시점
- timecreated: 생성 시간
- contentstitle: 콘텐츠 제목
- url: URL
- usedtime: 사용 시간 (초)
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `id` | INT | 레코드 ID (PK) | 12345 |
| `userid` | INT | 사용자 ID (FK) | 1603 |
| `contentstype` | INT | 콘텐츠 유형 | 1 (개념노트) |
| `nstroke` | INT | 총 필기량 | 500 |
| `tlaststroke` | INT | 마지막 필기 시점 (Unix timestamp) | 1735689600 |
| `timecreated` | INT | 생성 시간 (Unix timestamp) | 1735689600 |
| `contentstitle` | VARCHAR | 콘텐츠 제목 | "함수의 개념" |
| `url` | VARCHAR | URL | "/concept/123" |
| `usedtime` | INT | 사용 시간 (초) | 1800 |

---

## 데이터 흐름

```
[학생 개념 노트 작성]
  ↓
[mdl_abessi_messages] → 개념 노트 데이터 저장 (contentstype=1)
  ↓
[Agent 10 분석 요청]
  ↓
[개념 노트 패턴 분석]
  ├─→ 필기량 분석 (nstroke)
  ├─→ 사용 시간 분석 (usedtime)
  └─→ 작성 빈도 분석
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `concept_note_count` | mdl_abessi_messages | COUNT(*) (contentstype=1) | 개념 노트 개수 |
| `total_strokes` | mdl_abessi_messages | SUM(nstroke) | 총 필기량 |
| `total_used_time` | mdl_abessi_messages | SUM(usedtime) | 총 사용 시간 |
| `last_note_time` | mdl_abessi_messages | MAX(tlaststroke) | 마지막 노트 시간 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent10_concept_notes.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 10 Concept Notes System  
**문서 위치**: `alt42/orchestration/agents/agent10_concept_notes/DB_REPORT.md`

