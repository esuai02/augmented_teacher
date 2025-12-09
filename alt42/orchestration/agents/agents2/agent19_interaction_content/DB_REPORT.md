# Agent 19 - Interaction Content DB 리포트

**생성일**: 2025-01-27  
**에이전트**: Agent 19 - Interaction Content (상호작용 컨텐츠)  
**버전**: 1.0

---

## 목차

1. [에이전트 개요](#에이전트-개요)
2. [데이터베이스 구조](#데이터베이스-구조)
3. [데이터 흐름](#데이터-흐름)
4. [필드 매핑](#필드-매핑)

---

## 에이전트 개요

**목적**: 학생과의 상호작용 컨텐츠 생성

**주요 기능**:
- 학생 프로필 기반 컨텐츠 생성
- MBTI 기반 컨텐츠 맞춤화
- 개인화된 상호작용 컨텐츠 제공

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

## 데이터 흐름

```
[상호작용 컨텐츠 생성 요청]
  ├─→ mdl_user (학생 기본 정보)
  └─→ mdl_abessi_mbtilog (MBTI 정보)
  ↓
[컨텐츠 생성]
  ├─→ 학생 프로필 기반 맞춤화
  └─→ MBTI 기반 톤앤매너 조정
  ↓
[개인화된 컨텐츠 제공]
```

---

## 필드 매핑

### Rules.yaml 필드 → DB 필드 매핑

| Rules.yaml 필드 | DB 테이블 | DB 필드 | 설명 |
|----------------|-----------|---------|------|
| `student_name` | mdl_user | firstname, lastname | 학생 이름 |
| `mbti_type` | mdl_abessi_mbtilog | mbti | MBTI 유형 |

---

## 참고 파일

- **Rules 정의**: `rules/rules.yaml`
- **에이전트 문서**: `agent19_interaction_content.md`

---

## 버전 정보

- **리포트 버전**: 1.0
- **생성일**: 2025-01-27
- **호환성**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
- **마지막 업데이트**: 2025-01-27

---

**문서 작성자**: Agent 19 Interaction Content System  
**문서 위치**: `alt42/orchestration/agents/agent19_interaction_content/DB_REPORT.md`

