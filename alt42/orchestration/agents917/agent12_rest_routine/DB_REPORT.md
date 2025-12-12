# Agent 12 - Rest Routine DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 12 - Rest Routine (휴식 루틴)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생의 휴식 패턴 분석 및 관리

**주요 기능**:
- 휴식 버튼 클릭 데이터 추적
- 휴식 시간 분석
- 휴식 루틴 최적화 제안

---

## 데이터베이스 구조

### 1. 휴식 시간 로그 테이블: `mdl_abessi_breaktimelog`

**목적**: 휴식 버튼 클릭 데이터 저장

#### 테이블 스키마

```sql
-- mdl_abessi_breaktimelog 테이블 구조
-- 휴식 시간 로그

주요 필드:
- userid: 사용자 ID (FK)
- duration: 휴식 시간 (초)
- timecreated: 생성 시간 (Unix timestamp)
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `userid` | INT | 사용자 ID (FK) | 1603 |
| `duration` | INT | 휴식 시간 (초) | 300 (5분) |
| `timecreated` | INT | 생성 시간 (Unix timestamp) | 1735689600 |

---

## 데이터 흐름

```
[학생 휴식 버튼 클릭]
  ↓
[mdl_abessi_breaktimelog] → 휴식 시간 로그 저장
  ↓
[Agent 12 분석 요청]
  ↓
[휴식 패턴 분석]
  ├─→ 휴식 빈도 분석
  ├─→ 휴식 시간 분석
  └─→ 휴식 루틴 최적화 제안
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `rest_count` | mdl_abessi_breaktimelog | COUNT(*) | 휴식 횟수 |
| `total_rest_time` | mdl_abessi_breaktimelog | SUM(duration) | 총 휴식 시간 |
| `average_rest_time` | mdl_abessi_breaktimelog | AVG(duration) | 평균 휴식 시간 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent12_rest_routine.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 12 Rest Routine System  
**문서 위치**: `alt42/orchestration/agents/agent12_rest_routine/DB_REPORT.md`

