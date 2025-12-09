# Agent05 Learning Emotion Persona System - Architecture Overview

> **Version**: 1.0.0
> **Last Updated**: 2025-06-03
> **Author**: Augmented Teacher Development Team

---

## 1. 시스템 개요

Agent05 Learning Emotion Persona System은 학습자의 감정을 실시간으로 분석하고, 적절한 페르소나 기반 응답을 생성하는 시스템입니다.

### 1.1 핵심 목표

- **감정 감지**: 학습자의 텍스트 입력에서 8가지 학습 감정을 실시간 감지
- **페르소나 선택**: 22개 이상의 페르소나 중 상황에 맞는 최적 페르소나 자동 선택
- **적응형 응답**: 감정 강도와 학습 활동에 따른 개인화된 응답 생성
- **에이전트 협업**: Agent06, 07, 08, 09와의 감정 정보 공유

### 1.2 지원 감정 유형

| 감정 타입 | 한국어 | 설명 |
|----------|--------|------|
| anxiety | 불안 | 학습 내용이나 시험에 대한 걱정 |
| frustration | 좌절 | 반복적 실패나 이해 불능에서 오는 답답함 |
| confidence | 자신감 | 학습 성과에 대한 긍정적 확신 |
| curiosity | 호기심 | 새로운 지식에 대한 탐구욕 |
| boredom | 지루함 | 학습 동기 저하 상태 |
| fatigue | 피로 | 집중력 저하 및 지침 상태 |
| achievement | 성취감 | 목표 달성 후 만족감 |
| confusion | 혼란 | 개념 이해의 어려움 |

### 1.3 지원 학습 활동

| 활동 타입 | 한국어 | 설명 |
|----------|--------|------|
| concept_understanding | 개념이해 | 새로운 개념 학습 |
| type_learning | 유형학습 | 문제 유형별 학습 |
| problem_solving | 문제풀이 | 실제 문제 해결 |
| error_note | 오답노트 | 틀린 문제 복습 |
| qa | Q&A | 질문 답변 |
| review | 복습 | 이전 학습 내용 복습 |
| pomodoro | 포모도로 | 시간 관리 학습 |
| home_check | 홈체크 | 가정 학습 확인 |

---

## 2. 아키텍처 다이어그램

```
┌─────────────────────────────────────────────────────────────────────┐
│                     Agent05 Persona System                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────────────┐    ┌──────────────────────┐              │
│  │  User Input          │───▶│  EmotionAnalyzer     │              │
│  │  (Text/Emoticon)     │    │  - 감정 감지         │              │
│  └──────────────────────┘    │  - 강도 분석         │              │
│                              │  - NLU 패턴 매칭     │              │
│                              └──────────┬───────────┘              │
│                                         │                          │
│                                         ▼                          │
│  ┌──────────────────────┐    ┌──────────────────────┐              │
│  │ LearningActivity     │───▶│  Agent05PersonaEngine │              │
│  │ Detector             │    │  - 페르소나 선택     │              │
│  │ - 활동 유형 감지     │    │  - 응답 생성 조율    │              │
│  └──────────────────────┘    │  - 전환 관리         │              │
│                              └──────────┬───────────┘              │
│                                         │                          │
│                 ┌───────────────────────┼───────────────────┐      │
│                 ▼                       ▼                   ▼      │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ Agent05Data      │  │ Agent05Response  │  │ InterAgent       │  │
│  │ Context          │  │ Generator        │  │ Communicator     │  │
│  │ - 상태 수집      │  │ - 템플릿 적용    │  │ - 감정 공유      │  │
│  │ - 히스토리 조회  │  │ - 응답 생성      │  │ - 알림 전송      │  │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘  │
│           │                     │                     │            │
│           ▼                     ▼                     ▼            │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                  EmotionStateRepository                     │   │
│  │  - 감정 로그 저장/조회                                      │   │
│  │  - 전환 히스토리 관리                                       │   │
│  │  - 패턴 분석                                                │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                │                                   │
└────────────────────────────────┼───────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         Database Layer                              │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐             │
│  │ emotion_log   │ │ transition_log│ │ activity_log  │             │
│  └───────────────┘ └───────────────┘ └───────────────┘             │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐             │
│  │ agent_share   │ │ response_log  │ │ pattern       │             │
│  └───────────────┘ └───────────────┘ └───────────────┘             │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. 컴포넌트 상세

### 3.1 Engine Layer

#### Agent05PersonaEngine.php
- **역할**: 페르소나 시스템의 중앙 오케스트레이터
- **위치**: `engine/Agent05PersonaEngine.php`
- **주요 기능**:
  - 감정 분석 → 페르소나 선택 → 응답 생성 파이프라인 관리
  - 페르소나 전환 로직 처리
  - 에이전트간 통신 조율

```php
// 기본 사용 예시
$engine = new Agent05PersonaEngine();
$response = $engine->processAndRespond($userId, $userMessage, $activityType);
```

### 3.2 Analysis Layer

#### EmotionAnalyzer.php
- **역할**: 텍스트 기반 감정 감지 및 분석
- **위치**: `engine/EmotionAnalyzer.php`
- **주요 기능**:
  - 한국어 NLU 패턴 기반 감정 감지
  - 이모티콘 분석
  - 강도(high/medium/low) 판별
  - 복합 감정 처리

#### LearningActivityDetector.php
- **역할**: 학습 활동 유형 감지
- **위치**: `engine/LearningActivityDetector.php`
- **주요 기능**:
  - 8가지 학습 활동 유형 자동 감지
  - 컨텍스트 기반 활동 추론
  - 활동별 감정 상관관계 분석

### 3.3 Data Layer

#### Agent05DataContext.php
- **역할**: 페르소나 선택에 필요한 데이터 수집
- **위치**: `engine/Agent05DataContext.php`
- **주요 기능**:
  - 사용자 감정 상태 조회
  - 학습 활동 이력 수집
  - 페르소나 효과성 데이터 제공

#### Agent05ResponseGenerator.php
- **역할**: YAML 템플릿 기반 응답 생성
- **위치**: `engine/Agent05ResponseGenerator.php`
- **주요 기능**:
  - emotion_templates.yaml 기반 응답 생성
  - 변수 치환 처리
  - 톤 조절

### 3.4 Database Layer

#### schema.php
- **역할**: DB 테이블 스키마 관리
- **위치**: `db/schema.php`
- **테이블**: 6개 테이블 생성/관리

#### EmotionStateRepository.php
- **역할**: DB CRUD 및 분석 쿼리
- **위치**: `db/EmotionStateRepository.php`
- **주요 기능**:
  - 감정 상태 저장/조회
  - 전환 히스토리 관리
  - 통계 및 패턴 분석

#### InterAgentCommunicator.php
- **역할**: 에이전트간 감정 정보 공유
- **위치**: `db/InterAgentCommunicator.php`
- **연동 에이전트**: Agent06, 07, 08, 09

### 3.5 Configuration Layer

#### personas.yaml
- **역할**: 22개 페르소나 정의
- **위치**: `templates/personas.yaml`

#### emotion_templates.yaml
- **역할**: 72개 감정 응답 템플릿
- **위치**: `templates/emotion_templates.yaml`

#### rules.yaml
- **역할**: 페르소나 선택/전환 규칙
- **위치**: `templates/rules.yaml`

---

## 4. 데이터 흐름

### 4.1 요청 처리 흐름

```
1. 사용자 입력 수신
   └─▶ Agent05PersonaEngine.processAndRespond()

2. 감정 분석
   └─▶ EmotionAnalyzer.analyze()
       ├─▶ 키워드 패턴 매칭
       ├─▶ 이모티콘 분석
       └─▶ 강도 판별

3. 학습 활동 감지
   └─▶ LearningActivityDetector.detect()

4. 데이터 컨텍스트 수집
   └─▶ Agent05DataContext.getContext()
       ├─▶ 감정 히스토리
       └─▶ 페르소나 효과성

5. 페르소나 선택
   └─▶ rules.yaml 기반 선택 로직
       ├─▶ 감정 기반 1차 선택
       ├─▶ 활동 기반 2차 조정
       └─▶ 전환 규칙 적용

6. 응답 생성
   └─▶ Agent05ResponseGenerator.generate()
       ├─▶ 템플릿 선택
       ├─▶ 변수 치환
       └─▶ 톤 조절

7. 상태 저장
   └─▶ EmotionStateRepository
       ├─▶ 감정 로그 저장
       └─▶ 응답 로그 저장

8. 에이전트 공유 (필요시)
   └─▶ InterAgentCommunicator
       └─▶ 긴급 알림 / 상태 공유
```

### 4.2 에이전트간 통신 흐름

```
Agent05 (Learning Emotion)
    │
    ├──▶ Agent06 (emotion_alert, approach_recommendation)
    │    └─ 학습 접근법 조정 권장
    │
    ├──▶ Agent07 (learning_emotion, frustration_alert)
    │    └─ 좌절감 고조시 개입 요청
    │
    ├──▶ Agent08 (fatigue_alert, calmness_trigger)
    │    └─ 피로/휴식 필요시 알림
    │
    └──▶ Agent09 (emotion_summary, intervention_needed)
         └─ 학습 관리자에게 요약 전달
```

---

## 5. 확장 포인트

### 5.1 새 감정 유형 추가

1. `EmotionAnalyzer.php`의 `EMOTION_PATTERNS` 배열에 패턴 추가
2. `emotion_templates.yaml`에 템플릿 추가
3. `rules.yaml`에 선택/전환 규칙 추가
4. DB 스키마의 ENUM 타입 수정

### 5.2 새 페르소나 추가

1. `personas.yaml`에 페르소나 정의 추가
2. `rules.yaml`에 매핑 규칙 추가
3. `emotion_templates.yaml`에 관련 템플릿 추가

### 5.3 새 학습 활동 추가

1. `LearningActivityDetector.php`에 패턴 추가
2. `rules.yaml`의 `activity_mapping` 섹션 업데이트

---

## 6. 성능 고려사항

### 6.1 캐싱 전략

- 페르소나 YAML 파일: 서버 재시작시 로드
- 사용자별 최근 감정 상태: 세션 캐시
- 패턴 분석 결과: 24시간 캐시

### 6.2 DB 최적화

- `emotion_log` 테이블: `userid`, `timecreated` 복합 인덱스
- 주기적 아카이빙: 90일 이상 데이터

### 6.3 권장 설정

- 감정 분석 타임아웃: 500ms
- 페르소나 전환 쿨다운: 60초
- 에이전트 공유 배치 크기: 10건

---

## 7. 파일 구조

```
agents/agent05_learning_emotion/persona_system/
├── engine/
│   ├── Agent05PersonaEngine.php      # 중앙 오케스트레이터
│   ├── Agent05DataContext.php        # 데이터 컨텍스트
│   ├── Agent05ResponseGenerator.php  # 응답 생성기
│   ├── EmotionAnalyzer.php           # 감정 분석기
│   └── LearningActivityDetector.php  # 활동 감지기
├── db/
│   ├── schema.php                    # DB 스키마 관리
│   ├── EmotionStateRepository.php    # 감정 상태 저장소
│   └── InterAgentCommunicator.php    # 에이전트간 통신
├── templates/
│   ├── personas.yaml                 # 페르소나 정의
│   ├── emotion_templates.yaml        # 감정 응답 템플릿
│   └── rules.yaml                    # 선택/전환 규칙
└── docs/
    ├── 01_architecture_overview.md   # 본 문서
    ├── 02_api_reference.md           # API 레퍼런스
    ├── 03_integration_guide.md       # 통합 가이드
    └── 04_developer_quickstart.md    # 빠른 시작 가이드
```

---

## 8. 관련 문서

- [02_api_reference.md](02_api_reference.md) - 상세 API 레퍼런스
- [03_integration_guide.md](03_integration_guide.md) - 시스템 통합 가이드
- [04_developer_quickstart.md](04_developer_quickstart.md) - 개발자 빠른 시작

---

**문서 끝**
