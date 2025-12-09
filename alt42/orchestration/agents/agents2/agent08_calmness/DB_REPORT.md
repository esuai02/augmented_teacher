# Agent 08 - Calmness DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 08 - Calmness (침착도)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 침착도 계산 및 관리

**주요 기능**:
- 오늘목표/검사요청/주간목표 데이터 기반 침착도 계산
- 침착도 점수 추적
- 침착도 기반 학습 조언 제공

---

## 데이터베이스 구조

### 1. 오늘 목표/검사 테이블: `mdl_abessi_today`

**목적**: 오늘 목표, 검사 요청, 주간 목표 데이터 저장

#### 테이블 스키마

```sql
-- mdl_abessi_today 테이블 구조
-- 침착도 계산에 사용되는 데이터

주요 필드:
- id: 레코드 ID (PK)
- userid: 사용자 ID (FK)
- type: 유형 (오늘목표/검사요청/주간목표)
- score: 점수
- timecreated: 생성 시간
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `id` | INT | 레코드 ID (PK) | 12345 |
| `userid` | INT | 사용자 ID (FK) | 1603 |
| `type` | VARCHAR | 유형 | "오늘목표", "검사요청", "주간목표" |
| `score` | DECIMAL | 점수 | 85.5 |
| `timecreated` | INT | 생성 시간 (Unix timestamp) | 1735689600 |

---

## 데이터 흐름

### 1. 침착도 계산 프로세스

```
[학생 활동]
  ↓
[mdl_abessi_today] → 오늘목표/검사/주간목표 데이터 저장
  ↓
[Agent 08 분석 요청]
  ↓
[침착도 계산]
  ├─→ 오늘목표 점수 분석
  ├─→ 검사요청 점수 분석
  └─→ 주간목표 점수 분석
  ↓
[침착도 점수 산출]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `calmness_score` | 계산 필드 | - | 침착도 점수 (계산) |
| `today_score` | mdl_abessi_today | score (type='오늘목표') | 오늘 목표 점수 |
| `exam_score` | mdl_abessi_today | score (type='검사요청') | 검사 점수 |
| `weekly_score` | mdl_abessi_today | score (type='주간목표') | 주간 목표 점수 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent08_calmness.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 08 Calmness System  
**문서 위치**: `alt42/orchestration/agents/agent08_calmness/DB_REPORT.md`

