# AI 튜터 시스템 사용 가이드

**버전**: 2.0 (MySQL 버전)  
**기반**: Agent01 Onboarding 설계 원리  
**OpenAI API**: teachingagent.php와 동일한 방식 사용  
**데이터베이스**: MySQL 5.7 (Moodle DB API)

---

## 개요

AI 튜터 시스템은 선생님-학생 대화나 수학 문제를 입력받아, Agent01의 설계 원리를 기반으로:
1. **포괄적 질문** 생성
2. **세부 질문** 생성
3. **교수법 룰** 생성
4. **온톨로지** 생성
5. **라이브 튜터링** 제공
6. **12가지 학습자 페르소나** 식별 및 맞춤 지도
7. **42가지 개입 활동** 적용

---

## 설치 방법

### 1. 데이터베이스 테이블 생성

```bash
# MySQL 콘솔에서 실행
mysql -u username -p moodle_database < db/schema.sql
```

또는 phpMyAdmin에서 `db/schema.sql` 파일 내용을 실행하세요.

### 2. 설치 확인

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/db_install.php?action=check
```

### 3. 기본 데이터 확인

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/db_install.php?action=init_data
```

---

## 접속 방법

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/index.php?studentid={학생ID}
```

### 저장된 분석 결과 로드

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/index.php?id={분석ID}
```

---

## 사용 방법

### 1. 대화 내용 입력

**예시 입력**:
```
선생님: 자, 오늘은 이차방정식의 근의 분리 문제를 풀어볼 거예요...

학생: 음... 그래프를 그리면 좋을 것 같아요.

선생님: 맞아요! 이 문제는 그래프를 활용해서 조건을 확인하는 것이 편리해요...
```

### 2. 이미지 업로드 (선택사항)

- 이미지를 클릭하거나 드래그 앤 드롭으로 업로드
- 수학 문제 이미지, 그래프, 도형 등 지원

### 3. 분석 시작

"분석 및 튜터링 시작" 버튼 클릭

### 4. 결과 확인

분석 결과는 자동으로 MySQL 데이터베이스에 저장됩니다.
- 저장된 분석 ID로 언제든 다시 로드 가능
- URL을 공유하여 다른 사람과 결과 공유 가능

---

## 데이터베이스 스키마

### 테이블 목록 (14개)

| 테이블명 | 설명 | 레코드 |
|----------|------|--------|
| `mdl_alt42_analysis_results` | 분석 결과 | - |
| `mdl_alt42_interactions` | 상호작용 히스토리 | - |
| `mdl_alt42_sessions` | 학습 세션 | - |
| `mdl_alt42_generated_rules` | 생성된 룰 | - |
| `mdl_alt42_rule_contents` | 룰 컨텐츠 | - |
| `mdl_alt42_ontology_data` | 온톨로지 데이터 | - |
| `mdl_alt42_student_contexts` | 학생 컨텍스트 | - |
| `mdl_alt42_personas` | 페르소나 정의 | 12개 |
| `mdl_alt42_student_personas` | 학생-페르소나 매핑 | - |
| `mdl_alt42_persona_switches` | 페르소나 스위칭 기록 | - |
| `mdl_alt42_intervention_activities` | 개입 활동 | 42개 |
| `mdl_alt42_intervention_executions` | 개입 실행 기록 | - |
| `mdl_alt42_writing_patterns` | 필기 패턴 | - |
| `mdl_alt42_non_intrusive_questions` | 비침습적 질문 | - |

자세한 스키마는 `db/SCHEMA_DESIGN.md` 참조

---

## 시스템 구조

```
AItutor/
├── index.php                    # 메인 진입점
├── db/
│   ├── schema.sql              # MySQL 스키마 (DDL + 기본 데이터)
│   └── SCHEMA_DESIGN.md        # 스키마 설계 문서
├── api/
│   ├── analyze_content.php     # 분석 API (OpenAI 호출)
│   ├── load_analysis.php       # 분석 결과 로드 API
│   ├── interact.php            # 상호작용 API
│   ├── persona_api.php         # 페르소나 API
│   ├── intervention_api.php    # 개입 활동 API
│   ├── db_status.php           # DB 상태 확인 API
│   └── db_install.php          # DB 설치 확인 API
├── includes/
│   ├── db_manager.php          # DB 매니저 (Moodle DB API)
│   ├── openai_analyzer.php     # OpenAI 분석기
│   ├── persona_manager.php     # 페르소나 매니저
│   ├── persona_based_tutoring.php  # 페르소나 기반 튜터링
│   ├── intervention_manager.php    # 개입 활동 매니저
│   ├── interaction_engine.php  # 상호작용 엔진
│   ├── rule_evaluator.php      # 룰 평가기
│   ├── rule_content_generator.php  # 룰 컨텐츠 생성기
│   └── content_loader.php      # 컨텐츠 로더
├── docs/
│   ├── INTERVENTION_SYSTEM.md      # 42개 개입 활동 문서
│   └── WRITING_BASED_TUTOR_DESIGN.md  # 필기 기반 튜터 설계
└── ui/
    ├── unit_tutor.js           # 클라이언트 로직
    └── unit_tutor.css          # 스타일시트
```

---

## 학습자 페르소나 (12가지)

| ID | 이름 | 영문 | 설명 |
|----|------|------|------|
| P001 | 막힘-회피형 | Avoider | 문제를 읽다 막히면 바로 포기 |
| P002 | 확인요구형 | Checker | 계속 확인을 요청 |
| P003 | 감정출렁형 | Emotion-driven | 감정 변화가 큼 |
| P004 | 빠른데 허술형 | Speed-but-Miss | 빠르지만 실수가 많음 |
| P005 | 집중 튐형 | Attention Hopper | 집중력이 흐트러짐 |
| P006 | 패턴추론형 | Pattern Seeker | 전체 구조를 먼저 파악 |
| P007 | 최대한 쉬운길 찾기형 | Efficiency Maximizer | 효율적 방법 선호 |
| P008 | 불안과몰입형 | Over-focusing Worrier | 쉬운 문제에도 오래 고민 |
| P009 | 추상-언어 약함형 | Concrete Learner | 예시를 통해 학습 |
| P010 | 상호작용 의존형 | Interactive Dependent | 혼자 풀면 멈춤 |
| P011 | 무기력·저동기형 | Low Drive | 에너지가 없음 |
| P012 | 메타인지 고수형 | Meta-high | 자기 조절력 높음 |

---

## 개입 활동 (42가지)

| 카테고리 | 활동 수 | 설명 |
|----------|---------|------|
| 1. 멈춤/대기 (Pause & Wait) | 5개 | 인지적 공간 확보 |
| 2. 재설명 (Repeat & Rephrase) | 6개 | 동일 내용 재처리 |
| 3. 전환 설명 (Alternative Explanation) | 7개 | 다른 경로로 이해 |
| 4. 강조/주의환기 (Emphasis & Alerting) | 5개 | 중요도 인식 |
| 5. 질문/탐색 (Questioning & Probing) | 7개 | 능동적 사고 유도 |
| 6. 즉시 개입 (Immediate Intervention) | 6개 | 오류 실시간 교정 |
| 7. 정서 조절 (Emotional Regulation) | 6개 | 학습 동기 관리 |

자세한 내용은 `docs/INTERVENTION_SYSTEM.md` 참조

---

## API 사용법

### 분석 결과 저장/조회

```php
require_once('includes/db_manager.php');

$dbManager = new DBManager();

// 분석 결과 저장
$analysisId = $dbManager->saveAnalysisResult([
    'student_id' => $studentId,
    'text_content' => $textContent,
    'dialogue_analysis' => $dialogueAnalysis,
    'teaching_rules' => $teachingRules,
    'ontology' => $ontology
]);

// 분석 결과 조회
$result = $dbManager->getAnalysisResult($analysisId);

// 학생의 분석 결과 목록
$results = $dbManager->getStudentAnalysisResults($studentId, 10);
```

### 상호작용 기록

```php
$dbManager->saveInteraction([
    'student_id' => $studentId,
    'user_input' => $userInput,
    'response_text' => $responseText,
    'persona_id' => 'P003',
    'intervention_id' => 'INT_7_1'
]);
```

### 페르소나 관리

```php
// 모든 페르소나 조회
$personas = $dbManager->getAllPersonas();

// 특정 페르소나 조회
$persona = $dbManager->getPersona('P003');

// 학생-페르소나 매칭 저장
$dbManager->saveStudentPersonaMatch($studentId, 'P003', 0.85);

// 현재 페르소나 조회
$current = $dbManager->getCurrentStudentPersona($studentId);
```

### 개입 활동 관리

```php
// 모든 개입 활동 조회
$interventions = $dbManager->getAllInterventions();

// 카테고리별 조회
$pauseActivities = $dbManager->getInterventionsByCategory('pause_wait');

// 개입 활동 실행 기록
$dbManager->saveInterventionExecution([
    'activity_id' => 'INT_7_1',
    'student_id' => $studentId,
    'trigger_signal' => '좌절 표현',
    'response_type' => 'positive'
]);
```

---

## 문제 해결

### 테이블이 없다는 오류

```
https://...AItutor/api/db_install.php?action=check
```

위 URL로 테이블 존재 여부를 확인하고, 없으면 `db/schema.sql`을 실행하세요.

### 분석 결과를 찾을 수 없음

```
https://...AItutor/api/db_status.php
```

위 URL로 데이터베이스 상태와 최근 분석 결과를 확인하세요.

### OpenAI API 오류

- `config.php` 파일 확인
- API 키 유효성 확인
- 네트워크 연결 확인

---

## 참고 자료

- [Agent01 Onboarding 설계 문서](../orchestration/agents/agent01_onboarding/ontology/principles.md)
- [teachingagent.php](../teachingagent.php) - OpenAI API 사용 예시
- [DESIGN_DOCUMENT.md](./DESIGN_DOCUMENT.md) - 시스템 설계 문서
- [db/SCHEMA_DESIGN.md](./db/SCHEMA_DESIGN.md) - 데이터베이스 스키마 설계

---

**작성일**: 2025-01-27  
**버전**: 2.0 (MySQL 버전)
