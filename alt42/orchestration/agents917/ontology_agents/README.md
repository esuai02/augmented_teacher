# Mathking 자동개입 v1.0

**온톨로지 기반 AI 튜터 의사결정 시스템**

![Phase 1 완료](https://img.shields.io/badge/Phase%201-완료-success)
![Tests](https://img.shields.io/badge/Tests-24%2F24%20passing-success)
![Performance](https://img.shields.io/badge/Performance-0.778ms-brightgreen)
![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)

---

## 🎉 Phase 1 완료! (2025-11-01)

**온톨로지 기반 동적 추론 시스템 구축 완료**

✅ **3개 규칙 → 10개 규칙** (하드코딩 → 온톨로지 기반)
✅ **5개 감정 지원** (Frustrated, Focused, Tired, Anxious, Happy)
✅ **우선순위 시스템** (다중 규칙 매칭)
✅ **E2E 테스트 100% 통과** (5/5)
✅ **성능 목표 100배 초과 달성** (0.778ms E2E)

📊 **완료 보고서**: [docs/PHASE1_COMPLETION_REPORT.md](docs/PHASE1_COMPLETION_REPORT.md)

---

## 🚀 빠른 시작

### 🧪 온톨로지 추론 실험실 v3 (Phase 1)

**대화형 추론 실험 인터페이스** - 실시간으로 온톨로지 기반 추론을 수행하세요!

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v3.php
```

**Phase 1 특징**:
- ✨ **온톨로지 기반 동적 추론** (JSON 파일만 편집 → 즉시 반영)
- 🎯 **5개 감정 지원** (😰 좌절, 😊 집중, 😴 피로, 😟 불안, 😄 기쁨)
- 📊 **10개 규칙, 우선순위 기반 다중 매칭**
- ⚡ **초고속 추론** (0.778ms E2E, 109만회/초)
- 📈 **설명 가능한 추론** (Explainable AI)

**사용 가이드**: [docs/PHASE1_EXECUTION_PLAN.md](docs/PHASE1_EXECUTION_PLAN.md)

### 🎨 온톨로지 시각화 도구

**대화형 RDF 그래프 시각화 인터페이스** - 온톨로지 구조를 시각적으로 탐색하세요!

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/ontology_visualizer/ontology_visualizer.html
```

**주요 기능**:
- 📂 **원클릭 온톨로지 로드** (Phase 1, 백업 파일)
- 🔍 **확장 가능한 노드** (클릭하여 하위 구조 탐색)
- 🎯 **자동 레이아웃** (계층적 그래프 표시)
- 📊 **온톨로지 정보** (규칙 수, 감정 수, 클래스 수)
- ✏️ **수동 편집 지원** (JSON-LD 직접 붙여넣기)

---

## 📋 목차

1. [개요](#개요)
2. [시스템 아키텍처](#시스템-아키텍처)
3. [폴더 구조](#폴더-구조)
4. [핵심 개념](#핵심-개념)
5. [설치 및 설정](#설치-및-설정)
6. [사용 방법](#사용-방법)
7. [📊 문서 상태 대시보드](#-문서-상태-대시보드)
8. [📝 문서 평가 기준](#-문서-평가-기준)
9. [API 문서](#api-문서)
10. [개발 가이드](#개발-가이드)
11. [테스트](#테스트)
12. [로드맵](#로드맵)

---

## 개요

### 목표

이미 독립 개발된 **22개 에이전트**를 **의사결정 지식 베이스**, **온톨로지**, **페르소나 DB**와 연동하여, 학생 개개인에게 최적화된 학습 개입을 자동으로 생성하는 시스템.

### 현재 단계 (v1.0)

✅ **리포트/지시문 생성 및 추적만 수행**
❌ LMS 실제 기능 호출은 **v2에서 구현**

### 설계 원칙

- **규칙 엔진 우선 → LLM 보완**: 명확한 규칙이 있으면 규칙을 따르고, 모호한 경우에만 LLM 판단
- **증거 기반**: 모든 결정은 실제 학습 데이터(Evidence)에 근거
- **페르소나 유사도 기반**: 지시성 강도를 페르소나 유사도에 따라 조절
- **30분 Heartbeat**: 각 에이전트가 주기적으로 실행되며 상황 점검
- **아이들포텐트(Idempotent)**: 동일 조건에서 중복 지시문 생성 방지

---

## 시스템 아키텍처

```
┌─────────────────────────────────────────────────────────┐
│                    Mathking v1.0                        │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐           │
│  │22 Agents │───│Ontology  │───│Persona DB│           │
│  └──────────┘   └──────────┘   └──────────┘           │
│       │              │               │                  │
│       └──────────────┴───────────────┘                  │
│                      │                                  │
│              ┌───────▼────────┐                         │
│              │ Rule Engine    │                         │
│              │ + LLM Reasoner │                         │
│              └───────┬────────┘                         │
│                      │                                  │
│              ┌───────▼────────┐                         │
│              │ Report/         │                         │
│              │ Directive       │                         │
│              │ Generator       │                         │
│              └───────┬────────┘                         │
│                      │                                  │
│              ┌───────▼────────┐                         │
│              │ Tracker/Logger │                         │
│              └────────────────┘                         │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### 데이터 흐름

1. **Evidence 수집**: 학생의 학습 데이터 (진도, 정답률, 시간 등)
2. **Persona State 로드**: 현재 감정 상태, 집중도, 인지 부하
3. **에이전트 선택**: 컨텍스트에 맞는 에이전트 선택
4. **규칙 평가**: 트리거 조건 확인
5. **LLM 추론** (필요시): 모호한 사례 판단
6. **리포트/지시문 생성**: 템플릿 렌더링
7. **우선순위 정렬**: 상황 변수 고려하여 순서 결정
8. **로깅 및 추적**: 의사결정 로그 저장

---

## 폴더 구조

```bash
mathking/
├── agents/                      # 22개 에이전트
│   ├── registry.yaml           # 전역 에이전트 메타
│   ├── agent_curriculum/       # 커리큘럼 에이전트
│   │   ├── config.yaml
│   │   ├── tasks/
│   │   │   ├── task_lagging.yaml
│   │   │   └── ...
│   │   ├── prompts/
│   │   │   ├── report_lagging.md
│   │   │   └── directive_focus.md
│   │   ├── tests/
│   │   └── logs/
│   └── ... (21개 더)
├── ontology/
│   ├── ontology.jsonld         # 온톨로지 (SSOT)
│   ├── relations/              # 관계 정의
│   └── schema/                 # 스키마
├── knowledge/
│   ├── 의사결정_지식.md        # 정책/전략
│   ├── 의사결정_실행.md        # 액션 정의
│   └── policies/               # 규칙 DSL
├── persona/
│   ├── db/                     # 기존 DB 연동
│   ├── mappings/
│   └── analyzer/
├── engine/
│   ├── scheduler/              # Heartbeat
│   ├── rule_engine/            # 규칙 파서/평가
│   ├── reasoning/              # LLM 추론
│   ├── ontology/               # 온톨로지 로더
│   ├── reporting/              # 리포트 생성
│   └── pipeline.py             # 전체 파이프라인
├── interface/
│   ├── api/                    # REST API
│   ├── openapi.yaml            # API 스펙
│   └── cli/                    # CLI 도구
├── config/
│   ├── global.yaml
│   ├── heartbeat.yaml
│   └── secrets.env
├── logs/
│   ├── decisions/              # 의사결정 로그
│   ├── reports/                # 생성 리포트
│   └── audits/                 # 감사 로그
└── tests/
    ├── test_pipeline.py
    └── mock/
```

**원칙**: **API 경로 = 폴더 경로**

예: `GET /agents/agent_curriculum/tasks` → `agents/agent_curriculum/tasks/` 로딩

---

## 핵심 개념

### 1. 온톨로지 (Ontology)

**단일 진실원 (SSOT)**: `ontology/ontology.jsonld`

9개 표준진단 카테고리:
- curriculum (커리큘럼)
- exam_prep (시험대비)
- adaptive (맞춤학습)
- micro_mission (마이크로미션)
- self_reflection (자기성찰)
- self_directed (자기주도학습)
- apprenticeship (도제학습)
- time_reflection (시간성찰)
- inquiry (탐구학습)

### 2. 에이전트 (Agent)

총 22개:
- **9개 핵심**: 표준진단 카테고리별
- **12개 보조**: 감정, 동기, 성격, 학습스타일, 인지, 사회, 습관, 시간관리, 피드백, 목표설정, 메타인지, 창의성
- **1개 시스템**: 개선 제안 (agent_improvement)

각 에이전트는:
- **Heartbeat** 주기로 실행
- **태스크** 여러 개 보유
- **트리거** 조건 평가
- **리포트/지시문** 생성

### 3. 태스크 (Task)

에이전트가 수행하는 작업 단위.

예: `task_lagging` (진도 미달 대응)
- **목표**: 진도 미달 학생에게 시각화 + 집중 지시
- **KPI**: progress_rate, time_on_task
- **트리거**: `cond.progress.below_avg15`
- **템플릿**: report_lagging.md, directive_focus.md

### 3-1. 22개 워크플로우 실행 단계 ↔ 에이전트 1:1 매핑

**22개 시간순 실행 단계와 담당 에이전트의 1:1 매핑** - 각 워크플로우 단계는 해당 에이전트에 의해 실행됨:

#### Phase 1: 일상정보 수집 및 분석 (Daily Information Collection & Analysis)

| # | 워크플로우 단계 | 담당 에이전트 | 에이전트 역할 |
|---|---------------|--------------|--------------|
| 01 | 온보딩 | `agent_self_directed` | 자기주도학습 - 학습자 프로필 초기화 |
| 02 | 시험일정 식별 | `agent_exam_prep` | 시험대비 - 시험 일정 관리 및 대비 계획 |
| 03 | 목표분석 | `agent_goal_setting` | 목표설정 - 학습 목표 설정 및 진도 추적 |
| 04 | 문제활동 식별 | `agent_curriculum` | 커리큘럼 - 문제 세트 추천 및 활동 분석 |
| 05 | 학습감정 분석 | `agent_emotion` | 감정관리 - 감정 상태 감지 및 조절 개입 |
| 06 | 선생님 피드백 | `agent_feedback` | 피드백 - 교사 피드백 수집 및 학습 조정 |

#### Phase 2: 실시간 상호작용 및 학습 관리 (Real-time Interaction & Management)

| # | 워크플로우 단계 | 담당 에이전트 | 에이전트 역할 |
|---|---------------|--------------|--------------|
| 07 | 상호작용 타게팅 | `agent_adaptive` | 맞춤학습 - 맞춤형 학습 상호작용 설계 |
| 08 | 침착도 분석 | `agent_emotion` | 감정관리 - 스트레스 감지 및 안정화 지원 |
| 09 | 학습관리 분석 | `agent_time_management` | 시간관리 - 학습 세션 계획 및 효율성 분석 |
| 10 | 개념노트 분석 | `agent_metacognition` | 메타인지 - 개념 이해도 평가 및 보완 |
| 11 | 문제노트 분석 | `agent_metacognition` | 메타인지 - 문제 풀이 패턴 분석 및 개선 |
| 12 | 휴식루틴 분석 | `agent_habit` | 학습습관 - 휴식 타이밍 최적화 |
| 13 | 학습이탈 분석 | `agent_motivation` | 동기부여 - 이탈 징후 감지 및 재참여 유도 |

#### Phase 3: 진단 및 상호작용 준비 (Diagnosis & Interaction Preparation)

| # | 워크플로우 단계 | 담당 에이전트 | 에이전트 역할 |
|---|---------------|--------------|--------------|
| 14 | 현재위치 평가 | `agent_self_reflection` | 자기성찰 - 학습 진행 상태 종합 평가 |
| 15 | 문제 재정의 & 개선방안 | `agent_cognitive` | 인지능력 - 학습 장애 요인 파악 및 해결책 제시 |
| 16 | 상호작용 준비 | `agent_inquiry` | 탐구학습 - 개입 전 준비 및 컨텍스트 로드 |
| 17 | 잔여활동 조정 | `agent_micro_mission` | 마이크로미션 - 남은 학습 시간 최적화 |
| 18 | 시그너처 루틴 찾기 | `agent_habit` | 학습습관 - 학습자별 최적 학습 패턴 발견 |
| 19 | 상호작용 컨텐츠 생성 | `agent_social` | 사회적학습 - 맞춤형 학습 콘텐츠 생성 |

#### Phase 4: 개입 실행 및 시스템 개선 (Intervention & System Improvement)

| # | 워크플로우 단계 | 담당 에이전트 | 에이전트 역할 |
|---|---------------|--------------|--------------|
| 20 | 개입준비 | `agent_apprenticeship` | 도제학습 - 개입 실행 전 최종 점검 |
| 21 | 개입실행 | `agent_apprenticeship` | 도제학습 - 실제 학습 개입 실행 및 피드백 |
| 22 | 모듈성능 개선 제안 | `agent_improvement` | 개선 제안 - 시스템 성능 모니터링 및 개선 제안 |

**📊 에이전트 중복 매핑 현황**:
- `agent_emotion`: 2회 (단계 05, 08)
- `agent_metacognition`: 2회 (단계 10, 11)
- `agent_habit`: 2회 (단계 12, 18)
- `agent_apprenticeship`: 2회 (단계 20, 21)
- 나머지 14개 에이전트: 각 1회씩 매핑

### 4. 페르소나 (Persona)

학생 유형별 특성 정의.

기존 DB 연동:
- `mdl_prsn_activestate` - 활성 상태
- `mdl_prsn_usermap` - 사용자-페르소나 매핑
- `mdl_prsn_contents` - 페르소나별 콘텐츠
- `mdl_prsn_reaction` - 반응 데이터
- `mdl_prsn_relation` - 관계
- `mdl_prsn_selection` - 선택 이력

**유사도 → 지시성 강도**:
```python
strength = base + alpha * similarity
```

### 5. 규칙 DSL

```yaml
Condition:
  cond.progress.below_avg15: "evidence.metrics.progress_delta <= -0.15"

Rule:
  rule.curriculum.lagging_report:
    when: "cond.progress.below_avg15"
    then:
      suggest:
        - template: "report_lagging"
          params: { chart: "progress_vs_avg" }
```

### 6. Evidence & State

**Evidence**: 학습 데이터
- metrics: 정답률, 재시도, 시간
- window: 집계 기간
- context: 수업 상태 (시작/중반/종료 30분 전)

**PersonaState**: 페르소나 상태
- affect: low/med/high
- focus: 0~1
- load: low/med/high

---

## 설치 및 설정

### 요구사항

- PHP 7.1.9+
- MySQL 5.7+
- Python 3.10+ (엔진 실행용)
- Moodle 3.7+

### 환경 변수 설정

`config/secrets.env`:

```bash
# LLM
OPENAI_API_KEY=sk-...
OPENAI_API_ENDPOINT=https://api.openai.com/v1

# Database
DB_HOST=localhost
DB_NAME=moodle
DB_USER=moodle
DB_PASSWORD=***

# Security
JWT_SECRET=***
ALERT_WEBHOOK_URL=https://...
```

### 설치

```bash
# 1. 폴더 구조 확인
ls -la mathking/

# 2. 의존성 설치 (Python)
pip install -r requirements.txt

# 3. DB 연결 테스트
python engine/test_db_connection.py

# 4. 온톨로지 로드 테스트
python engine/ontology/loader.py
```

---

## 사용 방법

### CLI 사용

```bash
# 에이전트 시뮬레이션
python interface/cli/simulate_agent.py \
  --agent agent_curriculum \
  --task task_lagging \
  --user S123 \
  --evidence tests/mock/sample_events.json

# 리포트 생성
python interface/cli/generate_report.py \
  --decision_id dec_20251029_0001

# 의사결정 재현 (replay)
python interface/cli/replay_decision.py \
  --decision_id dec_20251029_0001
```

### API 사용

```bash
# 전체 에이전트 조회
curl https://mathking.kr/moodle/.../api/v1/agents

# 시뮬레이션 실행
curl -X POST https://mathking.kr/.../api/v1/agents/agent_curriculum/tasks/task_lagging/simulate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "evidence": {
      "user_id": "S123",
      "metrics": { "progress_delta": -0.18, "retry_count": 3 },
      "window": { "start_ts": "2025-10-29T09:00:00Z", "end_ts": "2025-10-29T09:30:00Z" },
      "context": { "class_status": "end_30min" }
    },
    "state": {
      "persona_id": "P_avoidant",
      "affect": "med",
      "focus": 0.65,
      "load": "high",
      "ts": "2025-10-29T09:30:00Z"
    }
  }'

# 리포트 조회
curl https://mathking.kr/.../api/v1/reports/dec_20251029_0001
```

---

## 📊 문서 상태 대시보드

프로젝트의 전체 문서 및 에이전트 상태를 시각적으로 확인할 수 있는 대시보드입니다.

### 빠른 시작

브라우저에서 `dashboard.html` 파일을 열면 됩니다:

```bash
# 방법 1: 직접 파일 열기
open dashboard.html  # macOS
start dashboard.html  # Windows
xdg-open dashboard.html  # Linux

# 방법 2: 로컬 서버 실행
python -m http.server 8000
# 브라우저에서 http://localhost:8000/dashboard.html 접속
```

### 대시보드 기능

#### 1. 전체 요약 카드
- **전체 에이전트**: 22개 (완료 1개, 계획 21개)
- **전체 문서**: 7개 (완료/검증/수정필요 상태)
- **일관성 점수**: 문서 간 정렬 상태 백분율

#### 2. 문서 상태 섹션
각 문서별로:
- 완성도 및 일관성 점수
- 수정 필요 이슈 목록 (Line 번호, 현재값, 수정값)
- 상태 뱃지 (완료/검증완료/수정필요)

#### 3. 에이전트 온톨로지 섹션
22개 에이전트별로:
- 완성도 백분율 (0% / 5% / 100%)
- Config/Tasks/Prompts 구현 여부
- Phase 2 우선순위 표시
- 구현 노트

#### 4. 다음 액션
우선순위 순으로:
1. 04-ONTOLOGY Line 1733 수정
2. 06-INTEGRATION Lines 99, 113, 1525 수정
3. Phase 2 에이전트 구현 시작

### 상태 파일 업데이트

`status/document_status.json` 파일을 직접 수정하여 상태를 업데이트할 수 있습니다:

```json
{
  "agents": {
    "details": [
      {
        "id": "agent_curriculum",
        "name": "커리큘럼 에이전트",
        "status": "completed",
        "completion": 100,
        "has_config": true,
        "has_tasks": true,
        "has_prompts": true
      }
    ]
  },
  "documents": {
    "details": [
      {
        "id": "01-AGENTS_TASK_SPECIFICATION",
        "status": "completed",
        "completion": 100,
        "consistency_score": 100,
        "issues": []
      }
    ]
  }
}
```

### 자동 새로고침

대시보드는 5분마다 자동으로 상태 파일을 다시 읽어 업데이트합니다.
수동 새로고침은 우측 하단의 🔄 버튼을 클릭하세요.

### 상태 파일 구조

`status/document_status.json`:

```
{
  "metadata": {
    "project": "Mathking 자동개입 v1.0",
    "version": "1.0.0",
    "last_updated": "2025-10-30",
    "total_agents": 22,
    "total_documents": 7
  },
  "agents": {
    "completed": 1,
    "in_progress": 0,
    "planned": 21,
    "total": 22,
    "details": [...]
  },
  "documents": {
    "total": 7,
    "completed": 3,
    "needs_update": 2,
    "validated": 2,
    "details": [...]
  },
  "consistency_checks": {
    "agent_count": {...},
    "phase2_priority": {...},
    "orchestration_mapping": {...}
  },
  "next_actions": [...]
}
```

---

## 📝 문서 평가 기준

### 아키텍처 문서 품질 평가표

모든 핵심 아키텍처 문서(01-07)는 아래 기준으로 평가됩니다:

| 항목 | 배점 | 평가 기준 |
|------|------|-----------|
| **구조적 완성도** | 10점 | • 논리적 섹션 구성<br>• 계층 구조의 명확성<br>• 목차와 내용의 일치도<br>• 문서 간 상호참조 정확성 |
| **기능적 명확성** | 10점 | • 기능 정의의 구체성<br>• 사용 사례의 명확성<br>• 입출력 명세의 정확성<br>• 제약사항 명시 |
| **기술적 적합성** | 10점 | • 기술 스택 선택의 타당성<br>• 아키텍처 패턴 적용<br>• 성능/확장성 고려<br>• 표준 준수 |
| **유지보수성** | 10점 | • 코드 가독성 가이드<br>• 명명 규칙 일관성<br>• 주석 및 문서화<br>• 모듈화 수준 |
| **보안/배포 고려** | 10점 | • 보안 요구사항 명시<br>• 인증/권한 설계<br>• 배포 전략 문서화<br>• 롤백 계획 |
| **문서 품질** | 10점 | • 맞춤법/문법 정확성<br>• 다이어그램 품질<br>• 예제 코드의 실행 가능성<br>• 버전 관리 |
| **총점** | **60점 만점** | **50점 이상**: 우수<br>**40-49점**: 양호<br>**30-39점**: 보통<br>**30점 미만**: 개선 필요 |

### 평가 주기

- **초기 작성 시**: 자가 평가 후 동료 리뷰
- **주요 업데이트 시**: 재평가 및 점수 갱신
- **분기별**: 전체 문서 일괄 재평가

### 평가 결과 기록

각 문서의 평가 점수는 `status/document_status.json`의 `quality_score` 필드에 기록됩니다.

**예시:**
```json
{
  "id": "01-AGENTS_TASK_SPECIFICATION",
  "name": "01-AGENTS 에이전트 명세",
  "status": "completed",
  "completion": 100,
  "consistency_score": 100,
  "quality_score": 58,
  "quality_details": {
    "구조적_완성도": 10,
    "기능적_명확성": 10,
    "기술적_적합성": 9,
    "유지보수성": 10,
    "보안배포_고려": 9,
    "문서_품질": 10
  }
}
```

---

## API 문서

전체 API 스펙: `interface/openapi.yaml`

주요 엔드포인트:

| 메서드 | 경로 | 설명 |
|--------|------|------|
| GET | `/agents` | 전체 에이전트 목록 |
| GET | `/agents/{agent_id}` | 에이전트 상세 |
| GET | `/agents/{agent_id}/tasks` | 에이전트 태스크 목록 |
| POST | `/agents/{agent_id}/tasks/{task_id}/simulate` | 시뮬레이션 실행 |
| GET | `/persona/{persona_id}/state` | 페르소나 상태 조회 |
| POST | `/persona/{persona_id}/state` | 페르소나 상태 업데이트 |
| POST | `/ontology/query` | 온톨로지 SPARQL 쿼리 |
| GET | `/reports/{decision_id}` | 리포트 조회 |
| GET | `/decisions` | 의사결정 로그 조회 |

---

## 개발 가이드

### 새 에이전트 추가

1. **폴더 생성**:
   ```bash
   mkdir -p agents/agent_new/{tasks,prompts,tests,logs}
   ```

2. **config.yaml 작성**:
   ```yaml
   agent_id: "agent_new"
   heartbeat: { interval_min: 30 }
   tasks: ["task_example"]
   ```

3. **태스크 정의**: `tasks/task_example.yaml`

4. **템플릿 작성**: `prompts/report_example.md`

5. **registry.yaml 등록**:
   ```yaml
   agents:
     agent_new:
       id: "agent_new"
       status: "active"
   ```

6. **온톨로지 추가**: `ontology/ontology.jsonld`

### 새 규칙 추가

`knowledge/policies/rule_catalog.yaml`:

```yaml
Condition:
  cond.new_condition: "expression"

Rule:
  rule.agent.new_rule:
    when: "cond.new_condition"
    then:
      suggest:
        - template: "new_template"
```

### 새 페르소나 추가

1. **DB 등록**: `mdl_prsn_usermap`에 새 페르소나 추가
2. **온톨로지 등록**: `ontology.jsonld`에 정의
3. **임베딩 생성**: `persona/analyzer/build_embeddings.py` 실행

---

## 테스트

### 단위 테스트

```bash
pytest tests/test_rules.py
pytest tests/test_templates.py
pytest tests/test_ontology_links.py
```

### 통합 테스트

```bash
pytest tests/test_pipeline.py
```

### 커버리지

```bash
pytest --cov=engine --cov-report=html
```

---

## 로드맵

### v1.0 (현재)
✅ 리포트/지시문 생성
✅ 규칙 엔진 + LLM
✅ 페르소나 유사도
✅ Heartbeat 스케줄러
✅ API 인터페이스

### v2.0 (계획)
🔲 **Action Binding Layer**: LMS 실제 기능 호출
🔲 **Action Executor**: 난이도 조정, 콘텐츠 추천 등 실행
🔲 **A/B 테스트**: 정책 효과 검증
🔲 **실시간 대시보드**: 의사결정 모니터링
🔲 **자동 정책 학습**: 효과 데이터 기반 정책 자동 개선

### v3.0 (비전)
🔲 멀티모달 입력 (음성, 표정 분석)
🔲 장기 학습 경로 최적화
🔲 학부모/교사 협력 시스템

---

## 라이선스

© 2025 Mathking. All rights reserved.

---

## 기여

버그 리포트, 기능 제안: [GitHub Issues](https://github.com/mathking/auto-intervention/issues)

---

## 문의

- **개발팀**: dev@mathking.kr
- **문서**: https://docs.mathking.kr
- **Slack**: #mathking-dev

---

**문서 끝**
