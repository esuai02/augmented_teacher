# Agent 16 - Interaction Preparation DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 16 - Interaction Preparation (상호작용 준비)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생과의 상호작용 시나리오 생성 및 저장

**주요 기능**:
- 상호작용 시나리오 생성
- 가이드 모드(세계관) 설정
- 시나리오 저장 및 관리

---

## 데이터베이스 구조

### 1. 상호작용 시나리오 테이블: `mdl_agent16_interaction_scenarios`

**목적**: 생성된 상호작용 시나리오 저장

#### 테이블 스키마

```sql
CREATE TABLE IF NOT EXISTS mdl_agent16_interaction_scenarios (
    id INT(10) NOT NULL AUTO_INCREMENT,
    userid INT(10) NOT NULL COMMENT 'Moodle 사용자 ID',
    guide_mode VARCHAR(50) NOT NULL COMMENT '가이드 모드 (세계관)',
    vibe_coding_prompt TEXT DEFAULT NULL COMMENT 'VibeCoding 컨텍스트 프롬프트',
    db_tracking_prompt TEXT DEFAULT NULL COMMENT 'DBTracking 데이터 프롬프트',
    scenario TEXT NOT NULL COMMENT '생성된 시나리오 (Markdown 형식)',
    created_at INT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    updated_at INT(10) DEFAULT NULL COMMENT '수정 시간 (Unix timestamp)',
    
    PRIMARY KEY (id),
    INDEX userid_idx (userid),
    INDEX guide_mode_idx (guide_mode),
    INDEX created_at_idx (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Agent16: 상호작용 시나리오';
```

#### 주요 필드 설명

| 필드명 | 타입 | 설명 | 값 예시 |
|--------|------|------|---------|
| `id` | INT(10) | 레코드 ID (PK) | 12345 |
| `userid` | INT(10) | 사용자 ID (FK) | 1603 |
| `guide_mode` | VARCHAR(50) | 가이드 모드 (세계관) | "coaching", "mentoring" |
| `vibe_coding_prompt` | TEXT | VibeCoding 컨텍스트 프롬프트 | "학생의 현재 상태..." |
| `db_tracking_prompt` | TEXT | DBTracking 데이터 프롬프트 | "데이터 추적 정보..." |
| `scenario` | TEXT | 생성된 시나리오 (Markdown) | "# 상호작용 시나리오..." |
| `created_at` | INT(10) | 생성 시간 (Unix timestamp) | 1735689600 |
| `updated_at` | INT(10) | 수정 시간 (Unix timestamp) | 1735689600 |

---

## 데이터 흐름

```
[상호작용 준비 요청]
  ↓
[가이드 모드 선택]
  ↓
[데이터 수집]
  ├─→ VibeCoding 컨텍스트 생성
  └─→ DBTracking 데이터 수집
  ↓
[시나리오 생성 (GPT)]
  ↓
[mdl_agent16_interaction_scenarios] → 시나리오 저장
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `guide_mode` | mdl_agent16_interaction_scenarios | guide_mode | 가이드 모드 |
| `scenario` | mdl_agent16_interaction_scenarios | scenario | 생성된 시나리오 |
| `vibe_coding` | mdl_agent16_interaction_scenarios | vibe_coding_prompt | VibeCoding 프롬프트 |
| `db_tracking` | mdl_agent16_interaction_scenarios | db_tracking_prompt | DBTracking 프롬프트 |

---

## 참고 파일

- **DB 마이그레이션**: `db/migration_create_scenarios_table.php`
- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent16_interaction_preparation.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 16 Interaction Preparation System  
**문서 위치**: `alt42/orchestration/agents/agent16_interaction_preparation/DB_REPORT.md`

