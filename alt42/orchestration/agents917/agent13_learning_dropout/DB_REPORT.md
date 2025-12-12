# Agent 13 - Learning Dropout DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 13 - Learning Dropout (학습 이탈)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학습 이탈 위험도 예측 및 방지

**주요 기능**:
- 24시간 내 활동 데이터 분석
- 이탈 위험도 계산
- 이탈 방지 전략 제안

---

## 데이터베이스 구조

### 1. 오늘 목표/검사 테이블: `mdl_abessi_today`

**목적**: 목표/검사 데이터 (24시간 내)

**주요 필드**:
- `userid`: 사용자 ID
- `ninactive`: 비활성 횟수
- `nlazy`: 게으름 횟수
- `activetime`: 활동 시간
- `checktime`: 확인 시간
- `status`: 상태
- `type`: 유형
- `timecreated`: 생성 시간
- `timemodified`: 수정 시간

---

### 2. 메시지 테이블: `mdl_abessi_messages`

**목적**: 보드/노트 활동 데이터 (24시간 내)

**주요 필드**:
- `userid`: 사용자 ID
- `timemodified`: 수정 시간
- `tlaststroke`: 마지막 필기 시점

---

### 3. 타임스캐폴딩 테이블: `mdl_abessi_tracking`

**목적**: 타임스캐폴딩 데이터

**주요 필드**:
- `userid`: 사용자 ID
- `status`: 상태
- `timecreated`: 생성 시간
- `duration`: 지속 시간
- `text`: 텍스트 내용

---

### 4. 포모도르 요약 테이블: `mdl_abessi_indicators`

**목적**: 포모도르 요약 데이터

**주요 필드**:
- `userid`: 사용자 ID
- `npomodoro`: 포모도르 횟수
- `kpomodoro`: 완료 포모도르 횟수
- `pmresult`: 포모도르 결과
- `timecreated`: 생성 시간

---

## 데이터 흐름

```
[24시간 내 활동 데이터 수집]
  ├─→ mdl_abessi_today (목표/검사 데이터)
  ├─→ mdl_abessi_messages (보드/노트 활동)
  ├─→ mdl_abessi_tracking (타임스캐폴딩)
  └─→ mdl_abessi_indicators (포모도르 요약)
  ↓
[이탈 위험도 계산]
  ├─→ 비활성 횟수 분석
  ├─→ 활동 시간 분석
  └─→ 포모도르 완성률 분석
  ↓
[이탈 방지 전략 제안]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `inactive_count` | mdl_abessi_today | ninactive | 비활성 횟수 |
| `lazy_count` | mdl_abessi_today | nlazy | 게으름 횟수 |
| `active_time` | mdl_abessi_today | activetime | 활동 시간 |
| `pomodoro_count` | mdl_abessi_indicators | npomodoro | 포모도르 횟수 |
| `pomodoro_completed` | mdl_abessi_indicators | kpomodoro | 완료 포모도르 횟수 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent13_learning_dropout.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 13 Learning Dropout System  
**문서 위치**: `alt42/orchestration/agents/agent13_learning_dropout/DB_REPORT.md`

