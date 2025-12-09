# Agent14 Persona System - 교육과정 혁신 에이전트

> 버전: 1.0 | 최종 수정: 2025-12-02

## 📋 목차

1. [개요](#개요)
2. [아키텍처](#아키텍처)
3. [설치 및 설정](#설치-및-설정)
4. [API 레퍼런스](#api-레퍼런스)
5. [페르소나 정의](#페르소나-정의)
6. [개발자 가이드](#개발자-가이드)

---

## 개요

Agent14 Persona System은 교육과정 혁신을 지원하는 AI 에이전트의 페르소나 기반 응답 시스템입니다.

### 핵심 기능
- **페르소나 식별**: 사용자 메시지와 컨텍스트 기반 자동 페르소나 선택
- **응답 생성**: 페르소나 특성에 맞는 맞춤형 응답 생성
- **에이전트 간 통신**: DB 기반 에이전트 간 상태 공유 및 메시지 교환
- **상황별 적응**: C1~C5 상황 코드에 따른 적응형 페르소나 전환

### 도메인: 교육과정 혁신 (Curriculum Innovation)
- **C1**: 교육과정 분석 (Curriculum Analysis)
- **C2**: 콘텐츠 설계 (Content Design)
- **C3**: 교수법 혁신 (Pedagogy Innovation)
- **C4**: 평가 설계 (Assessment Design)
- **C5**: 적용 및 피드백 (Application & Feedback)

---

## 아키텍처

### 디렉터리 구조
```
persona_system/
├── api/
│   └── process.php              # API 엔드포인트
├── docs/
│   └── README.md                # 이 문서
├── engine/
│   ├── Agent14PersonaEngine.php # 메인 엔진
│   ├── config/
│   │   └── rules.yaml           # 페르소나 규칙 정의
│   ├── impl/
│   │   ├── Agent14ActionExecutor.php
│   │   ├── Agent14ConditionEvaluator.php
│   │   ├── Agent14DataContext.php
│   │   ├── Agent14ResponseGenerator.php
│   │   └── Agent14RuleParser.php
│   └── templates/
│       └── default/             # 응답 템플릿
└── tests/                       # 테스트 파일
```

### 클래스 상속 구조
```
AbstractPersonaEngine (공통 추상 클래스)
    └── Agent14PersonaEngine (Agent14 구현체)
         ├── uses: Agent14RuleParser
         ├── uses: Agent14ConditionEvaluator
         ├── uses: Agent14ActionExecutor
         ├── uses: Agent14DataContext
         └── uses: Agent14ResponseGenerator
```

### 의존성
- `ontology_engineering/persona_engine/`: 공통 인터페이스 및 추상 클래스
- `AgentCommunicator`: 에이전트 간 통신

---

## 설치 및 설정

### 1. DB 테이블 설치
```bash
# 브라우저에서 접속
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/db/install.php
```

### 2. 테이블 목록
| 테이블명 | 설명 |
|---------|------|
| `mdl_at_agent_persona_state` | 에이전트별 페르소나 상태 |
| `mdl_at_agent_messages` | 에이전트 간 메시지 큐 |
| `mdl_at_persona_log` | 처리 로그 |
| `mdl_at_agent_config` | 에이전트 설정 |

### 3. 헬스 체크
```bash
curl https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/api/process.php?action=health
```

---

## API 레퍼런스

### Base URL
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/api/process.php
```

### 엔드포인트 목록

#### 1. 페르소나 식별
```
POST /process.php?action=identify
Content-Type: application/json

{
  "user_message": "현재 교육과정을 분석해주세요",
  "context": {
    "situation": "C1"
  }
}
```

**응답:**
```json
{
  "success": true,
  "data": {
    "identification": {
      "persona_id": "C1_P1",
      "persona_name": "교육과정 분석가",
      "confidence": 0.85,
      "tone": "Professional",
      "intervention": "GapAnalysis"
    }
  }
}
```

#### 2. 응답 생성
```
POST /process.php?action=respond
Content-Type: application/json

{
  "user_message": "학습 콘텐츠를 설계하고 싶어요",
  "template_key": "C2_design"
}
```

#### 3. 메시지 분석
```
POST /process.php?action=analyze
Content-Type: application/json

{
  "user_message": "교수법을 혁신적으로 바꾸고 싶습니다"
}
```

#### 4. 상태 조회
```
GET /process.php?action=status&user_id=123
```

#### 5. 페르소나 목록
```
GET /process.php?action=personas
```

---

## 페르소나 정의

### C1: 교육과정 분석
| ID | 이름 | 톤 | 개입 유형 |
|----|-----|-----|----------|
| C1_P1 | 교육과정 분석가 | Professional | GapAnalysis |
| C1_P2 | 교육과정 안내자 | Warm | InformationProvision |

### C2: 콘텐츠 설계
| ID | 이름 | 톤 | 개입 유형 |
|----|-----|-----|----------|
| C2_P1 | 콘텐츠 설계자 | Professional | PlanDesign |
| C2_P2 | 콘텐츠 창작자 | Encouraging | SkillBuilding |

### C3: 교수법 혁신
| ID | 이름 | 톤 | 개입 유형 |
|----|-----|-----|----------|
| C3_P1 | 교수법 혁신가 | Encouraging | BehaviorModification |
| C3_P2 | 교수법 코치 | Calm | SkillBuilding |

### C4: 평가 설계
| ID | 이름 | 톤 | 개입 유형 |
|----|-----|-----|----------|
| C4_P1 | 평가 설계자 | Professional | AssessmentDesign |
| C4_P2 | 평가 안내자 | Warm | InformationProvision |

### C5: 적용 및 피드백
| ID | 이름 | 톤 | 개입 유형 |
|----|-----|-----|----------|
| C5_P1 | 적용 분석가 | Professional | GapAnalysis |
| C5_P2 | 피드백 코치 | Empathetic | EmotionalSupport |

### 톤 스타일
- **Professional**: 전문적이고 명확한 어조
- **Warm**: 따뜻하고 친근한 어조
- **Encouraging**: 격려하는 어조
- **Calm**: 차분하고 안정적인 어조
- **Empathetic**: 공감하는 어조

---

## 개발자 가이드

### 새 페르소나 추가하기

1. `rules.yaml`에 페르소나 정의 추가
```yaml
C6_P1:
  name: "새 페르소나"
  situation: C6
  tone: Professional
  activation_conditions:
    and:
      - field: situation
        operator: "=="
        value: C6
```

2. `Agent14PersonaEngine.php`의 `$personas` 배열에 추가

3. 응답 템플릿 생성
```
templates/default/C6_default.txt
```

### 커스텀 액션 핸들러 등록

```php
$executor = new Agent14ActionExecutor();
$executor->registerHandler('custom_action', function($params, $context) {
    // 처리 로직
    return ['result' => 'success'];
});
```

### 도메인 키워드 확장

```php
$evaluator = new Agent14ConditionEvaluator();
$evaluator->addDomainKeywords('new_domain', ['키워드1', '키워드2']);
```

---

## 관련 문서

- [ontology_engineering/persona_engine/ - 공통 엔진](../../../../ontology_engineering/persona_engine/)
- [Agent01 Persona System - 참조 구현](../../../../agents/agent01_adaptive_diagnosis/persona_system/)
- [DB 스키마](../../../../ontology_engineering/persona_engine/db/schema.sql)

---

## 지원 및 문의

문제 발생 시 다음을 확인하세요:
1. DB 테이블 설치 여부
2. 파일 경로 및 권한
3. PHP 에러 로그

**로그 위치**: `/var/log/php_errors.log` 또는 Moodle 디버그 모드
