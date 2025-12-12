# Agent01 온톨로지 구축 전략

**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**버전**: 2.2  
**목적**: 학생의 수학 학습 맥락 종합 분석 및 첫 수업 전략 도출을 위한 온톨로지 설계  
**v2.0 주요 변경**: 9개 논리적 모순 해결, 4-stage 구조 확립, 역할 분리 명확화  
**v2.1 주요 변경**: OIW Model (의지와 의지를 가진 온톨로지 시스템) 추가, 6단계 레이어 구조 확립  
**v2.2 주요 변경**: 10가지 구조적 문제 해결, 엔트로피 최소화, 실구현 가능한 구조로 개선

---

## 1. 개요

Agent01 온톨로지는 학생의 온보딩 정보, 학습 맥락, 진도 상태를 종합하여 첫 수업 시작 전략을 도출하는 지식 표현 체계입니다. 룰 기반 시스템(`rules.yaml`)과 연계하여 자동화된 의사결정을 지원합니다.

### 핵심 온톨로지 요소

1. **OnboardingContext**: 온보딩 정보와 학습 맥락 표현
2. **FirstClassStrategy**: 첫 수업 시작 전략 표현
3. **LearningContextIntegration**: 학습 맥락 통합 분석 표현

---

## 2. 학생의 수학 학습 맥락 종합 분석

### 2.1 질문 1: 첫 수업 적절한 난이도와 진도

**질문**: 학생의 온보딩 정보(학년, 학교, 학원 정보)를 기반으로 첫 수업의 적절한 난이도와 진도는?

**필요 데이터**:
- `agent_data.agent01_data.student_grade` - 학생 학년
- `agent_data.agent01_data.school_name` - 학교명
- `agent_data.agent01_data.academy_name` - 학원명
- `agent_data.agent01_data.academy_grade` - 학원 등급(반)
- `agent_data.agent01_data.onboarding_info` - 온보딩 종합 정보

**온톨로지 표현**:
```json
{
  "@id": "mk:OnboardingContext/difficulty_assessment",
  "@type": "mk:OnboardingContext",
  "mk:hasStudentGrade": "xsd:string",
  "mk:hasSchool": "mk:Institution",
  "mk:hasAcademy": "mk:Institution",
  "mk:hasAcademyGrade": "xsd:string",
  "mk:recommendsDifficulty": "mk:DifficultyLevel",
  "mk:recommendsProgress": "mk:ProgressPosition"
}
```

**룰 연계**: `S0_R2_academy_info_collection` (rules.yaml:35-55)

---

### 2.2 질문 2: 첫 수업 단원 및 내용 범위

**질문**: 학생의 개념/심화 진도 상태를 고려하여 첫 수업에서 다뤄야 할 단원과 내용 범위는?

**필요 데이터**:
- `agent_data.agent01_data.concept_progress` - 개념 진도
- `agent_data.agent01_data.advanced_progress` - 심화 진도
- `agent_data.agent01_data.math_unit_mastery` - 단원별 마스터링 수준
- `agent_data.agent01_data.current_progress_position` - 현재 진도 위치

**온톨로지 표현**:
```json
{
  "@id": "mk:LearningContextIntegration/content_scope",
  "@type": "mk:LearningContextIntegration",
  "mk:hasConceptProgress": "mk:CurriculumProgress",
  "mk:hasAdvancedProgress": "mk:CurriculumProgress",
  "mk:hasUnitMastery": "mk:UnitMastery",
  "mk:hasCurrentPosition": "mk:ProgressPosition",
  "mk:recommendsUnits": ["mk:MathUnit"],
  "mk:recommendsContentRange": "mk:ContentRange"
}
```

**룰 연계**: `S0_R5_math_unit_mastery_collection` (rules.yaml:101-119)

---

### 2.3 질문 3: 학습 스타일 기반 설명 전략 및 자료 유형

**질문**: 학생의 수학 학습 스타일(계산형/개념형/응용형)에 맞는 첫 수업 설명 전략과 자료 유형은?

**필요 데이터**:
- `agent_data.agent01_data.math_learning_style` - 수학 학습 스타일 (계산형/개념형/응용형)
- `agent_data.agent01_data.study_style` - 학습 스타일
- `agent_data.agent01_data.learning_style` - 일반 학습 스타일

**온톨로지 표현**:
```json
{
  "@id": "mk:FirstClassStrategy/explanation_strategy",
  "@type": "mk:FirstClassStrategy",
  "mk:hasMathLearningStyle": "mk:MathLearningStyle",
  "mk:hasStudyStyle": "mk:StudyStyle",
  "mk:recommendsExplanationStrategy": "mk:ExplanationStrategy",
  "mk:recommendsMaterialType": "mk:MaterialType",
  "mk:explanationStrategy": {
    "@type": "mk:ExplanationStrategy",
    "mk:forCalculationType": "mk:StepByStepExplanation",
    "mk:forConceptType": "mk:PrincipleBasedExplanation",
    "mk:forApplicationType": "mk:ProblemBasedExplanation"
  }
}
```

**룰 연계**: `S0_R1_math_learning_style_collection` (rules.yaml:13-33)

---

### 2.4 질문 4: 시험 대비 성향 및 자신감 기반 도입 루틴

**질문**: 학생의 시험 대비 성향과 자신감 수준을 반영한 첫 수업 도입 루틴과 상호작용 방식은?

**필요 데이터**:
- `agent_data.agent01_data.exam_style` - 시험 대비 성향
- `agent_data.agent01_data.math_confidence` - 수학 자신감 (0-10)
- `agent_data.agent01_data.confidence_level` - 자신감 수준
- `agent_data.agent01_data.math_stress_level` - 수학 스트레스 수준

**온톨로지 표현**:
```json
{
  "@id": "mk:FirstClassStrategy/introduction_routine",
  "@type": "mk:FirstClassStrategy",
  "mk:hasExamStyle": "mk:ExamPreparationStyle",
  "mk:hasMathConfidence": "xsd:integer",
  "mk:hasConfidenceLevel": "mk:ConfidenceLevel",
  "mk:hasMathStressLevel": "mk:StressLevel",
  "mk:recommendsIntroductionRoutine": "mk:IntroductionRoutine",
  "mk:recommendsInteractionStyle": "mk:InteractionStyle",
  "mk:introductionRoutine": {
    "@type": "mk:IntroductionRoutine",
    "mk:forLowConfidence": "mk:SupportiveRoutine",
    "mk:forHighConfidence": "mk:ChallengeRoutine",
    "mk:forExamOriented": "mk:ExamPrepRoutine"
  }
}
```

**룰 연계**: `S1_R2_initial_class_preparation_guide` (rules.yaml:194-213)

---

## 3. 수업 도입 전략 및 자료 선택

### 3.1 질문 1: 교재 및 문제 유형 선택

**질문**: 학생의 수학 수준과 학습 스타일을 종합하여 첫 수업에서 사용할 교재와 문제 유형은?

**필요 데이터**:
- `agent_data.agent01_data.math_level` - 수학 수준
- `agent_data.agent01_data.textbooks` - 사용 교재 목록
- `agent_data.agent01_data.academy_textbook` - 학원 교재
- `agent_data.agent01_data.math_learning_style` - 수학 학습 스타일

**온톨로지 표현**:
```json
{
  "@id": "mk:FirstClassStrategy/material_selection",
  "@type": "mk:FirstClassStrategy",
  "mk:hasMathLevel": "mk:MathLevel",
  "mk:hasTextbooks": ["mk:Textbook"],
  "mk:hasAcademyTextbook": "mk:Textbook",
  "mk:hasMathLearningStyle": "mk:MathLearningStyle",
  "mk:recommendsTextbook": "mk:Textbook",
  "mk:recommendsProblemType": ["mk:ProblemType"],
  "mk:problemTypeMapping": {
    "@type": "mk:ProblemTypeMapping",
    "mk:forCalculationType": ["mk:CalculationProblem", "mk:DrillProblem"],
    "mk:forConceptType": ["mk:ConceptProblem", "mk:ProofProblem"],
    "mk:forApplicationType": ["mk:ApplicationProblem", "mk:WordProblem"]
  }
}
```

**룰 연계**: `S0_R4_textbook_info_collection` (rules.yaml:79-99)

---

### 3.2 질문 2: 문제 난이도 및 피드백 톤

**질문**: 학생의 자신감 수준에 맞는 첫 수업 문제 난이도와 피드백 톤은?

**필요 데이터**:
- `agent_data.agent01_data.math_confidence` - 수학 자신감
- `agent_data.agent01_data.confidence_level` - 자신감 수준
- `agent_data.agent01_data.low_math_confidence` - 낮은 수학 자신감 플래그
- `agent_data.agent01_data.high_math_confidence` - 높은 수학 자신감 플래그

**온톨로지 표현**:
```json
{
  "@id": "mk:FirstClassStrategy/difficulty_feedback",
  "@type": "mk:FirstClassStrategy",
  "mk:hasMathConfidence": "xsd:integer",
  "mk:hasConfidenceLevel": "mk:ConfidenceLevel",
  "mk:recommendsDifficulty": "mk:DifficultyLevel",
  "mk:recommendsFeedbackTone": "mk:FeedbackTone",
  "mk:difficultyMapping": {
    "@type": "mk:DifficultyMapping",
    "mk:forLowConfidence": "mk:EasyToMedium",
    "mk:forMediumConfidence": "mk:Medium",
    "mk:forHighConfidence": "mk:MediumToHard"
  },
  "mk:feedbackToneMapping": {
    "@type": "mk:FeedbackToneMapping",
    "mk:forLowConfidence": "mk:EncouragingTone",
    "mk:forMediumConfidence": "mk:BalancedTone",
    "mk:forHighConfidence": "mk:ChallengingTone"
  }
}
```

**룰 연계**: `Q1_introduction_routine_by_confidence` (rules.yaml:760+)

---

### 3.3 질문 3: 학원-학교 진도 정렬 전략

**질문**: 학생의 학원 진도와 학교 진도를 고려한 첫 수업 내용 정렬 전략은?

**필요 데이터**:
- `agent_data.agent01_data.academy_progress` - 학원 진도
- `agent_data.agent01_data.concept_progress` - 개념 진도
- `agent_data.agent01_data.curriculum_alignment` - 커리큘럼 정렬 상태
- `agent_data.agent01_data.academy_school_home_alignment` - 학원-학교-집 정렬 상태

**온톨로지 표현**:
```json
{
  "@id": "mk:LearningContextIntegration/curriculum_alignment",
  "@type": "mk:LearningContextIntegration",
  "mk:hasAcademyProgress": "mk:CurriculumProgress",
  "mk:hasConceptProgress": "mk:CurriculumProgress",
  "mk:hasCurriculumAlignment": "mk:AlignmentStatus",
  "mk:hasAcademySchoolHomeAlignment": "mk:AlignmentStatus",
  "mk:recommendsAlignmentStrategy": "mk:AlignmentStrategy",
  "mk:alignmentStrategy": {
    "@type": "mk:AlignmentStrategy",
    "mk:forAheadAcademy": "mk:ReinforcementStrategy",
    "mk:forAheadSchool": "mk:PreviewStrategy",
    "mk:forAligned": "mk:SynchronizedStrategy",
    "mk:forMisaligned": "mk:BridgeStrategy"
  }
}
```

**룰 연계**: `S0_R5_math_unit_mastery_collection` (rules.yaml:101-119)

---

## 4. 룰 기반 연계 온톨로지 요소 추천

### 4.1 OnboardingContext (온보딩 컨텍스트)

**목적**: 온보딩 정보와 학습 맥락을 온톨로지로 표현 (Agent 01 핵심 온톨로지)

**클래스 정의**:
```json
{
  "@id": "mk:OnboardingContext",
  "@type": "owl:Class",
  "rdfs:label": "온보딩 컨텍스트",
  "rdfs:comment": "학생의 온보딩 정보와 초기 학습 맥락을 표현하는 핵심 온톨로지",
  "rdfs:subClassOf": "mk:Context",
  "mk:properties": [
    "mk:hasStudentGrade",
    "mk:hasSchool",
    "mk:hasAcademy",
    "mk:hasAcademyGrade",
    "mk:hasOnboardingInfo",
    "mk:recommendsDifficulty",
    "mk:recommendsProgress"
  ]
}
```

**룰 연계**:
- `S0_R2_academy_info_collection` - 학원 정보 수집
- `S0_R6_comprehensive_math_profile_verification` - 프로필 종합 검증

---

### 4.2 FirstClassStrategy (첫 수업 전략)

**목적**: 첫 수업 시작 전략을 온톨로지로 표현

**클래스 정의**:
```json
{
  "@id": "mk:FirstClassStrategy",
  "@type": "owl:Class",
  "rdfs:label": "첫 수업 전략",
  "rdfs:comment": "학생의 학습 맥락을 반영한 첫 수업 시작 전략",
  "rdfs:subClassOf": "mk:Strategy",
  "mk:properties": [
    "mk:hasMathLearningStyle",
    "mk:hasStudyStyle",
    "mk:hasExamStyle",
    "mk:hasMathConfidence",
    "mk:recommendsExplanationStrategy",
    "mk:recommendsMaterialType",
    "mk:recommendsIntroductionRoutine",
    "mk:recommendsInteractionStyle",
    "mk:recommendsTextbook",
    "mk:recommendsProblemType",
    "mk:recommendsDifficulty",
    "mk:recommendsFeedbackTone"
  ]
}
```

**룰 연계**:
- `Q1_comprehensive_first_class_strategy` - 첫 수업 종합 전략
- `Q1_introduction_routine_by_confidence` - 자신감 기반 도입 루틴
- `Q1_explanation_strategy_by_learning_style` - 학습 스타일 기반 설명 전략
- `Q1_material_type_by_progress` - 진도 기반 자료 유형

---

### 4.3 LearningContextIntegration (학습 맥락 통합)

**목적**: 학생의 학습 맥락(진도, 스타일, 자신감) 통합 분석을 온톨로지로 표현

**클래스 정의**:
```json
{
  "@id": "mk:LearningContextIntegration",
  "@type": "owl:Class",
  "rdfs:label": "학습 맥락 통합",
  "rdfs:comment": "학생의 진도, 학습 스타일, 자신감을 통합하여 분석하는 온톨로지",
  "rdfs:subClassOf": "mk:Context",
  "mk:properties": [
    "mk:hasConceptProgress",
    "mk:hasAdvancedProgress",
    "mk:hasUnitMastery",
    "mk:hasCurrentPosition",
    "mk:hasAcademyProgress",
    "mk:hasCurriculumAlignment",
    "mk:hasAcademySchoolHomeAlignment",
    "mk:recommendsUnits",
    "mk:recommendsContentRange",
    "mk:recommendsAlignmentStrategy"
  ]
}
```

**룰 연계**:
- `S0_R5_math_unit_mastery_collection` - 단원별 마스터링 수집
- `S1_R1_comprehensive_profile_summary` - 프로필 종합 요약
- `S1_R3_new_student_complete_summary` - 신규 학생 완전 요약

---

## 5. 질문 분석 및 룰 기반 자동 동작 가이드

### 5.1 질문 목록 분석

이 질문 세트는 학생의 온보딩 정보, 진도, 학습 스타일, 자신감을 종합하여 첫 수업 시작 전략을 도출합니다.

**질문 분류**:
1. **학습 맥락 종합 분석** (4개 질문)
   - 첫 수업 난이도/진도 결정
   - 단원 및 내용 범위 결정
   - 설명 전략 및 자료 유형 결정
   - 도입 루틴 및 상호작용 방식 결정

2. **수업 도입 전략 및 자료 선택** (3개 질문)
   - 교재 및 문제 유형 선택
   - 문제 난이도 및 피드백 톤 결정
   - 학원-학교 진도 정렬 전략

**룰 연계**:
- `S0_R1~S0_R6`: 수학 특화 정보 수집 룰
- `S1_R1~S1_R3`: 첫 수업 준비 가이드 룰

---

### 5.2 답변 분석 방법

#### 5.2.1 온보딩 정보 분석

**데이터 소스**: `S0_R2` 룰이 수집한 학원 정보
- `academy_name`, `academy_grade`, `student_grade`, `school_name`

**분석 프로세스**:
1. 학년별 표준 진도 범위 확인
2. 학원 등급(반)에 따른 난이도 조정
3. 학교 수준 고려한 진도 정렬

**온톨로지 매핑**: `OnboardingContext` → `recommendsDifficulty`, `recommendsProgress`

---

#### 5.2.2 수학 학습 스타일 분석

**데이터 소스**: `S0_R1` 룰이 분석한 수학 학습 스타일
- `math_learning_style` (계산형/개념형/응용형)
- `study_style`, `learning_style`

**분석 프로세스**:
1. 학습 스타일 분류 확인
2. 스타일별 설명 전략 매핑
3. 자료 유형 추천

**온톨로지 매핑**: `FirstClassStrategy` → `explanationStrategy`, `materialType`

---

#### 5.2.3 진도 정보 분석

**데이터 소스**: `S0_R5` 룰이 평가한 진도 정보
- `concept_progress`, `advanced_progress`
- `math_unit_mastery`, `current_progress_position`

**분석 프로세스**:
1. 개념/심화 진도 비교
2. 단원별 마스터링 수준 확인
3. 선후관계 고려한 단원 추천

**온톨로지 매핑**: `LearningContextIntegration` → `recommendsUnits`, `recommendsContentRange`

---

#### 5.2.4 자신감 수준 분석

**데이터 소스**: `S1_R2` 룰이 반영한 자신감 수준
- `math_confidence` (0-10)
- `confidence_level`, `math_stress_level`

**분석 프로세스**:
1. 자신감 수준 분류 (낮음/보통/높음)
2. 수준별 도입 루틴 선택
3. 피드백 톤 결정

**온톨로지 매핑**: `FirstClassStrategy` → `introductionRoutine`, `feedbackTone`

---

### 5.3 룰 기반 자동 동작 필요 사항

#### 5.3.1 데이터 수집 단계 (S0_R1~S0_R6)

**목적**: 수학 특화 정보 수집

**필수 룰**:
- `S0_R1`: 수학 학습 스타일 수집
- `S0_R2`: 학원 정보 수집
- `S0_R3`: 수학 성적 정량화
- `S0_R4`: 교재 정보 수집
- `S0_R5`: 단원별 마스터링 수집
- `S0_R6`: 프로필 종합 검증

**온톨로지 생성 시점**: 각 룰 실행 후 해당 온톨로지 인스턴스 생성

---

#### 5.3.2 첫 수업 준비 단계 (S1_R1~S1_R3)

**목적**: 첫 수업 준비 가이드 생성

**필수 룰**:
- `S1_R1`: 프로필 종합 요약
- `S1_R2`: 초기 수업 준비 가이드
- `S1_R3`: 신규 학생 완전 요약

**온톨로지 생성 시점**: 모든 S0 룰 완료 후 S1 룰 실행 시 `FirstClassStrategy` 인스턴스 생성

---

#### 5.3.3 포괄형 질문 대응 (Q1_*)

**목적**: 포괄형 질문에 대한 종합 답변 생성

**필수 룰**:
- `Q1_comprehensive_first_class_strategy`: 첫 수업 종합 전략
- `Q1_introduction_routine_by_confidence`: 자신감 기반 도입 루틴
- `Q1_explanation_strategy_by_learning_style`: 학습 스타일 기반 설명 전략
- `Q1_material_type_by_progress`: 진도 기반 자료 유형

**온톨로지 활용**: 기존 `OnboardingContext`, `FirstClassStrategy`, `LearningContextIntegration` 인스턴스를 조합하여 답변 생성

---

## 6. 온톨로지 구현 전략

### 6.1 JSON-LD 스키마 확장

기존 `온톨로지.jsonld`에 다음 클래스 및 프로퍼티 추가:

```json
{
  "@id": "mk:OnboardingContext",
  "@type": "rdfs:Class",
  "rdfs:subClassOf": "mk:Context"
},
{
  "@id": "mk:FirstClassStrategy",
  "@type": "rdfs:Class",
  "rdfs:subClassOf": "mk:Strategy"
},
{
  "@id": "mk:LearningContextIntegration",
  "@type": "rdfs:Class",
  "rdfs:subClassOf": "mk:Context"
}
```

---

### 6.2 룰 엔진 연계

**연계 방식**:
1. 룰 실행 시 온톨로지 인스턴스 자동 생성
2. 온톨로지 쿼리를 통한 데이터 추출
3. 온톨로지 기반 추론을 통한 전략 도출

**구현 위치**: `rules.yaml`의 `action` 섹션에 온톨로지 생성 액션 추가

---

### 6.3 데이터 매핑

**매핑 규칙**:
- `agent_data.agent01_data.*` → 온톨로지 프로퍼티
- 룰 조건 필드 → 온톨로지 클래스 속성
- 룰 액션 결과 → 온톨로지 인스턴스

**매핑 테이블**: 별도 `ontology_mapping.yaml` 파일 생성 권장

---

## 7. 검증 및 테스트

### 7.1 온톨로지 검증

1. **스키마 검증**: JSON-LD 스키마 유효성 확인
2. **일관성 검증**: 클래스-프로퍼티 관계 일관성 확인
3. **완전성 검증**: 필수 프로퍼티 누락 확인

### 7.2 룰 연계 검증

1. **데이터 흐름**: 룰 → 온톨로지 → 추론 → 답변 흐름 확인
2. **자동화 검증**: 룰 실행 시 온톨로지 자동 생성 확인
3. **정확성 검증**: 온톨로지 기반 추론 결과 정확도 확인

---

## 8. 참고 자료

- `rules.yaml`: Agent01 룰 정의
- `온톨로지.jsonld`: 기존 온톨로지 스키마
- `ONBOARDING_SURVEY_DB_REPORT.md`: 온보딩 설문 DB 구조
- `COMPREHENSIVE_QUESTIONS_RULES.md`: 포괄형 질문 룰 문서

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team

---

## 9. DIL Vertical 구조를 Agent01 온톨로지/DSL 레이어로 매핑 (v2.0)

DIL Vertical 구조(-12 ~ +10)를 **Agent01 온보딩 온톨로지 관점**으로 재정의하여 LLM 프롬프트/온톨로지-룰 연동을 강화합니다.

**v2.0 주요 개선사항**: 9개 논리적 모순 해결, 4-stage 구조 확립, 역할 분리 명확화

---

### 9.1 DIL Vertical과 온톨로지 Stage의 관계 명확화

**핵심 원칙**: DIL 17단계는 **추론 프레임워크(Reasoning Framework)**, 온톨로지 stage는 **데이터 스키마 레벨(Class Layering)**로 역할이 다릅니다.

- **DIL = reasoning pipeline** (LLM internal 추론 과정)
- **stage = data placement** (온톨로지 스키마 계층 구조)

#### 9.1.1 Origin Layer (DIL -12 ~ -5) → Reasoning Header

이 영역은 **추론 규칙(Reasoning Rules)**으로, 온톨로지 데이터 구조와 분리됩니다.

- **Field of Possibility (-12)**
  - 가능한 학생 타입/상황의 범위 정의
  - 예: `수학이 어려워요 / 중위권 / 상위권`, `벼락치기 / 계획형` 등

- **Duality (-11)**
  - 구분의 기준 정의
  - 예: "개념 vs 문제풀이", "시험 대비 vs 장기 성장", "학원 주도 vs 학생 주도"

- **Energetic Tension (-10)**
  - 충돌 포인트 정의
  - 예: 부모 스타일 vs 학생 성향, 진도 vs 이해도, 목표 vs 시간

- **Primordial Impulse (-9)**
  - 이 학생이 수학을 해야 하는 "원초적 이유"
  - 예: 내신 / 중간고사 / 경시 / 입시

- **Pre-Awareness / Awareness / Meaning / Origin (-8 ~ -5)**
  - 질문·데이터들이 "어떤 방향으로 의사결정을 떠미는지"에 대한 해석 규칙
  - 예:
    - 낮은 자신감 + 높은 스트레스 → "정서/루틴 먼저"
    - 높은 수준 + 적은 시간 → "효율 루틴 우선"

👉 이 영역은 `reasoning { cosmology { ... } }` 블록으로 분리되어 출력됩니다.

---

#### 9.1.2 Context Layer (DIL -4 ~ -1) → 온보딩/진도 데이터 구조

모든 Context 노드는 DIL Ontic 속성을 공유합니다:

- **Intention(-4)** → 이 객체가 "무엇을 이루려는가?"
- **Identity(-3)** → 이 객체가 "무엇/누구에 대한 것인가?"
- **Purpose(-2)** → 왜 존재하는지(데이터 모델의 목적)
- **Context(-1)** → 적용 범위/상황(예: 신규 중2, 중간고사 앞둔 상태 등)

**Context Layer 노드**:
- `OnboardingContext` - 온보딩 정보 (학년, 학교, 학원, 설문 상태)
- `LearningContextIntegration` - 진도/단원/정렬 상태 데이터

```dsl
stage: Context
intent: "..."
identity: "..."
purpose: "..."
context: "..."
```

---

#### 9.1.3 Decision Layer (DIL 0~3) → 전략 판단 모델

**Decision Layer**는 두 Context를 조합하여 **의사결정**을 수행합니다:

- **Problem(0)**
  - 예: "현재 진도와 개념 이해가 엇갈려 있음", "자신감이 낮음", "학원-학교 진도가 어긋남"
- **Decision(1)**
  - 예: "개념 우선 + 쉬운 문제로 시작", "문제풀이 중심 + 도전 난이도"
- **Impact(2)**
  - 예: "첫 수업에서 성공 경험 제공", "기초 보완에 집중하여 중장기 안정성 확보"
- **Data(3)**
  - rules.yaml의 `agent_data.*` 필드 + 수집된 온톨로지 인스턴스들

**Decision Layer 노드**:
- `FirstClassDecisionModel` - 난이도, 정렬 전략, 단원 범위 결정

---

#### 9.1.4 Execution Layer (DIL 4~10) → 실행 전략 모델

**Execution Layer**는 Decision Layer의 결정을 **실행 계획**으로 변환합니다:

- **Action(4)** → 실제 수업 전략
  - 예: 도입 루틴, 설명 전략, 자료 선택, 정렬 전략
- **Measurement(5)** → 적용 후 상태 측정(추후 확장: 학습 로그)
- **Insight(6)** → 어떤 패턴 발견? (예: 저학년인데 경시 목표 + 시간 부족 → 위험 플래그)
- **Feedback Loop(7)** → 다음 온보딩/상담 시 반영
- **Adjustment(8)** → 커리큘럼/루틴 수정
- **Learning(9)** → Agent01 자체의 룰/온톨로지 개선
- **Reinforcement(10)** → 잘 먹히는 패턴 강화 (시그너처 루틴으로 승격)

**Execution Layer 노드**:
- `FirstClassExecutionPlan` - 실제 첫 수업 실행 계획

---

### 9.2 Agent01 전용 DIL Ontology DSL 스키마 v2.0

Agent01 문서 구조 + rules.yaml를 반영하여, **LLM이 뱉어낼 DSL 형식을 v2.0 구조로 정리**합니다.

**v2.0 핵심 변경사항**:
- `reasoning` 블록과 `ontology` 블록 분리
- 4-stage 구조 (Origin/Context/Decision/Execution)
- 역할 분리 명확화 (데이터 vs 추론 vs 결정 vs 실행)

```dsl
reasoning {
  cosmology {
    possibility: "학생 유형, 목표, 진도 조합의 가능한 상태 정의"
    duality: "개념 vs 문제풀이, 단기 시험 vs 장기 성장"
    tension: "목표-시간, 부모-학생 성향, 진도-이해도 간 충돌 포인트"
    impulse: "수학을 해야 하는 이유(내신, 입시, 경시 등)"
    awareness: "온보딩으로 파악된 현재 상태 인식 방식"
    meaning: "이 상태에서 무엇을 최우선 과제로 볼지에 대한 기준"
    origin_rule: "OnboardingContext와 LearningContextIntegration을 모든 전략의 출발점으로 사용"
  }
}

ontology {
  # Context Layer - 데이터 구조만 담당
  node "A01_OnboardingContext" {
    class: "mk:OnboardingContext"
    stage: Context
    parent: "root"

    intent: "학생의 초기 수학 맥락을 구조화"
    identity: "특정 학생의 온보딩 정보"
    purpose: "첫 수업 전략 수립을 위한 기반 데이터 제공"
    context: "신규/갱신, 학년, 학교, 학원, 온보딩 설문 상태"

    hasStudentGrade: "{student_grade}"
    hasSchool: "{school_name}"
    hasAcademy: "{academy_name}"
    hasAcademyGrade: "{academy_grade}"
    hasOnboardingInfo: "{onboarding_info}"
    hasMathLearningStyle: "{math_learning_style}"
    hasStudyStyle: "{study_style}"
    hasExamStyle: "{exam_style}"
    hasMathConfidence: "{math_confidence}"
    hasConfidenceLevel: "{confidence_level}"
    hasMathStressLevel: "{math_stress_level}"
    hasMathLevel: "{math_level}"
    hasTextbooks: "{textbooks}"
    hasAcademyTextbook: "{academy_textbook}"
  }

  node "A01_LearningContextIntegration" {
    class: "mk:LearningContextIntegration"
    stage: Context
    parent: "A01_OnboardingContext"

    intent: "진도/단원/정렬 상태 데이터를 저장"
    identity: "해당 학생의 수학 진도 구조 데이터"
    purpose: "첫 수업 전략 수립을 위한 진도/단원 정보 제공"
    context: "개념/심화 진도, 단원 마스터리, 학원-학교-집 정렬 상태"

    hasConceptProgress: "{concept_progress}"
    hasAdvancedProgress: "{advanced_progress}"
    hasUnitMastery: "{math_unit_mastery}"
    hasCurrentPosition: "{current_progress_position}"
    hasAcademyProgress: "{academy_progress}"
    hasCurriculumAlignment: "{curriculum_alignment}"
    hasAcademySchoolHomeAlignment: "{academy_school_home_alignment}"
  }

  # Decision Layer - 두 Context를 조합하여 의사결정 수행
  node "A01_FirstClassDecisionModel" {
    class: "mk:FirstClassDecisionModel"
    stage: Decision
    parent: ["A01_OnboardingContext", "A01_LearningContextIntegration"]

    intent: "첫 수업의 핵심 의사결정을 수행"
    identity: "첫 수업 전략 결정 모델"
    purpose: "난이도, 정렬 전략, 단원 범위, 내용 범위 결정"
    context: "OnboardingContext와 LearningContextIntegration 데이터를 기반으로 결정"

    # 의사결정 코어 (DIL 0~3)
    problem: "이 학생의 첫 수업에서 가장 먼저 해결해야 할 핵심 문제"
    decision: "개념/문제 비율, 난이도, 진입 단원 등 구체적 선택"
    impact: "첫 1~3회 수업에서 기대하는 변화"
    data_sources: [
      "A01_OnboardingContext",
      "A01_LearningContextIntegration",
      "rules: S0_R1~S0_R6, S1_R1~S1_R3"
    ]

    # Decision Layer 출력 (난이도/정렬/범위 결정)
    difficulty_level: "mk:DifficultyLevel"  # OnboardingContext + LCI 데이터로 계산
    alignment_strategy: "mk:AlignmentStrategy"  # LCI의 정렬 상태 데이터 기반
    content_range: "mk:ContentRange"  # LCI의 진도/단원 데이터 기반
    unit_plan: ["mk:MathUnit"]  # LCI의 단원 마스터리 데이터 기반
  }

  # Execution Layer - Decision의 결정을 실행 계획으로 변환
  node "A01_FirstClassExecutionPlan" {
    class: "mk:FirstClassExecutionPlan"
    stage: Execution
    parent: "A01_FirstClassDecisionModel"

    intent: "DecisionModel의 결정을 실제 첫 수업 실행 계획으로 변환"
    identity: "첫 수업 실행 계획안"
    purpose: "수학 자존감, 이해도, 루틴 형성의 첫 발판"
    context: "DecisionModel의 결정사항을 실행 가능한 단계로 분해"

    # 실행 파이프라인 (DIL 4~10 관점)
    action: [
      "도입 루틴 설계 (introduction_routine)",
      "설명 전략(explanation_strategy)",
      "자료/문제 유형 선택(material_selection)",
      "정렬 전략 실행(curriculum_alignment_execution)"
    ]
    measurement: [
      "첫 수업 후 학생 반응/이해도",
      "문제 풀이 정확도/속도",
      "정서 반응(부담/안도)"
    ]
    insight: [
      "진단이 맞았는지 여부",
      "난이도/속도 조정 필요성"
    ]
    feedback: [
      "다음 수업 전략에 반영할 포인트"
    ]
    adjustment: [
      "난이도 상/하향",
      "개념 vs 문제 비율 조정"
    ]
    learning: [
      "이 패턴의 효과를 룰/온톨로지에 학습"
    ]
    reinforcement: [
      "잘 먹힌 전략을 시그너처 루틴으로 등록"
    ]
  }
}
```

---

### 9.3 Agent01용 LLM "요청 명세서" v2.0

**"이 Agent01 환경에서 LLM에게 정확히 뭘 시킬지"**를 위한 프롬프트 스펙을 v2.0 구조에 맞게 정의합니다.

#### 9.3.1 System Role

```text
당신은 "Agent01_Onboarding_DIL_Ontology_Generator_v2"입니다.

당신의 역할:
- agent_data.agent01_data 및 user_message를 기반으로
- reasoning 블록(추론 규칙)과 ontology 블록(데이터 구조)을 분리하여
- 4-stage 구조(Context/Decision/Execution)에 맞게 온톨로지 인스턴스를 생성하고
- 아래 정의된 Agent01 전용 DSL v2.0 형식으로만 출력합니다.

출력 구조:
1. reasoning { cosmology { ... } } - 추론 규칙 (DIL -12~-5)
2. ontology { 
     - Context Layer: OnboardingContext, LearningContextIntegration
     - Decision Layer: FirstClassDecisionModel
     - Execution Layer: FirstClassExecutionPlan
   }

설명 문장/자연어 해설을 추가하지 말고,
오직 reasoning { ... } ontology { ... } DSL 블록만 출력합니다.
```

---

#### 9.3.2 Input 형식 (LLM에 넘겨줄 JSON 예시)

```json
{
  "agent_data": {
    "agent01_data": {
      "student_grade": "중2",
      "school_name": "OO중학교",
      "academy_name": "OO수학학원",
      "academy_grade": "중2 상위반",
      "concept_progress": "중2-1 일차방정식까지",
      "advanced_progress": "중2-1 심화 전반",
      "math_unit_mastery": "방정식 단원 보통, 함수 단원 미이수",
      "current_progress_position": "중2-1 중반",
      "math_learning_style": "개념형",
      "study_style": "자율 학습 선호",
      "exam_style": "벼락치기",
      "math_confidence": 4,
      "confidence_level": "low",
      "math_stress_level": "높음",
      "math_level": "중위권",
      "textbooks": ["개념원리 중2-1", "쎈 중2-1"],
      "academy_textbook": "쎈 중2-1",
      "curriculum_alignment": "학교보다 학원 진도 빠름",
      "academy_school_home_alignment": "학원-학교 불완전 정렬",
      "weekly_hours": 6,
      "parent_style": "적극 개입"
    }
  },
  "user_message": "이 학생 첫 수업에서 무엇을 어떻게 시작해야 할지 알려줘."
}
```

---

#### 9.3.3 LLM이 따라야 할 생성 절차(내부 추론 순서) v2.0

LLM에게 이렇게 강제합니다:

1. **입력 파싱 및 Context Layer 구성**
   - agent01_data 필드들을 읽어서 OnboardingContext와 LearningContextIntegration에 각각 들어갈 값을 정리합니다.
   - **중요**: OnboardingContext는 난이도 추천을 하지 않습니다. 데이터만 저장합니다.

2. **Reasoning 블록 생성 (Cosmology)**
   - DIL -12~-5 관점에서 추론 규칙을 cosmology 블록에 작성합니다.
   - 이는 데이터가 아니라 추론 방식을 정의합니다.

3. **Decision Layer 구성**
   - OnboardingContext와 LearningContextIntegration 데이터를 조합하여
   - FirstClassDecisionModel에서 난이도, 정렬 전략, 단원 범위를 결정합니다.
   - Problem/Decision/Impact/Data를 명확히 정의합니다.

4. **Execution Layer 구성**
   - DecisionModel의 결정을 실행 계획으로 변환합니다.
   - Action~Reinforcement 파이프라인을 채웁니다.

5. **최종 출력은 반드시 아래 DSL v2.0 스키마 사용**

```text
반드시 아래 구조만 사용해서 출력하세요:

reasoning {
  cosmology { ... }
}

ontology {
  node "A01_OnboardingContext" { stage: Context ... }
  node "A01_LearningContextIntegration" { stage: Context ... }
  node "A01_FirstClassDecisionModel" { stage: Decision parent: [...] ... }
  node "A01_FirstClassExecutionPlan" { stage: Execution parent: "A01_FirstClassDecisionModel" ... }
}
```

---

#### 9.3.4 예시 출력 (위 입력 JSON 기준) v2.0

```dsl
reasoning {
  cosmology {
    possibility: "중2 학생, 학원/학교 병행, 중위권, 개념형 스타일"
    duality: "개념 보완 vs 진도 유지, 시험 대비 vs 자존감 회복"
    tension: "벼락치기 성향 + 낮은 자신감 + 학원 진도 선행"
    impulse: "중간고사 대비 및 내신 안정"
    awareness: "현재 개념은 중2-1 방정식까지, 함수는 아직 미이수"
    meaning: "첫 수업에서는 '기초 안정 + 불안 해소'를 최우선 과제로 설정"
    origin_rule: "OnboardingContext와 LearningContextIntegration을 모든 전략의 출발점으로 사용"
  }
}

ontology {
  node "A01_OnboardingContext" {
    class: "mk:OnboardingContext"
    stage: Context
    parent: "root"

    intent: "중2 신규 학생의 초기 수학 맥락을 구조화한다"
    identity: "중2, OO중학교, OO수학학원 상위반 학생"
    purpose: "첫 수업 전략 수립을 위한 기반 데이터 제공"
    context: "신규, 벼락치기 성향, 적극 개입 부모"

    hasStudentGrade: "중2"
    hasSchool: "OO중학교"
    hasAcademy: "OO수학학원"
    hasAcademyGrade: "중2 상위반"
    hasOnboardingInfo: "중위권, 벼락치기, 개념형, 자신감 낮음"
    hasMathLearningStyle: "개념형"
    hasStudyStyle: "자율 학습 선호"
    hasExamStyle: "벼락치기"
    hasMathConfidence: 4
    hasConfidenceLevel: "low"
    hasMathStressLevel: "높음"
    hasMathLevel: "중위권"
    hasTextbooks: ["개념원리 중2-1", "쎈 중2-1"]
    hasAcademyTextbook: "쎈 중2-1"
  }

  node "A01_LearningContextIntegration" {
    class: "mk:LearningContextIntegration"
    stage: Context
    parent: "A01_OnboardingContext"

    intent: "진도/단원/정렬 상태 데이터를 저장"
    identity: "중2-1 기준의 진도/단원 상태 데이터"
    purpose: "첫 수업 전략 수립을 위한 진도/단원 정보 제공"
    context: "방정식 단원 보통, 함수 단원 미이수, 학원 진도 선행"

    hasConceptProgress: "중2-1 일차방정식까지"
    hasAdvancedProgress: "중2-1 심화 전반"
    hasUnitMastery: "방정식 보통, 함수 미이수"
    hasCurrentPosition: "중2-1 중반"
    hasAcademyProgress: "중2-1 심화 진행 중"
    hasCurriculumAlignment: "학원 진도가 학교보다 빠름"
    hasAcademySchoolHomeAlignment: "학원-학교 불완전 정렬"
  }

  node "A01_FirstClassDecisionModel" {
    class: "mk:FirstClassDecisionModel"
    stage: Decision
    parent: ["A01_OnboardingContext", "A01_LearningContextIntegration"]

    intent: "첫 수업의 핵심 의사결정을 수행"
    identity: "중2-1 첫 수업 전략 결정 모델"
    purpose: "난이도, 정렬 전략, 단원 범위, 내용 범위 결정"
    context: "OnboardingContext와 LearningContextIntegration 데이터를 기반으로 결정"

    problem: "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태에서 학원 진도만 빠르게 진행 중"
    decision: "방정식 핵심 개념을 쉬운 예제로 재정리하고, 함수 도입을 위한 연결 개념까지 첫 수업에서 다룬다"
    impact: "첫 수업에서 '아, 이해된다'는 경험을 주어 자신감과 안정감을 올린다"
    data_sources: [
      "A01_OnboardingContext",
      "A01_LearningContextIntegration",
      "rules: S0_R1~S0_R6, S1_R1~S1_R3"
    ]

    difficulty_level: "mk:EasyToMedium"
    alignment_strategy: "mk:BridgeStrategy"
    content_range: "방정식 핵심 유형 복습 + 함수 개념 전단계 다리 놓기"
    unit_plan: ["중2-1 방정식 핵심 복습", "함수 도입 준비"]
  }

  node "A01_FirstClassExecutionPlan" {
    class: "mk:FirstClassExecutionPlan"
    stage: Execution
    parent: "A01_FirstClassDecisionModel"

    intent: "DecisionModel의 결정을 실제 첫 수업 실행 계획으로 변환"
    identity: "중2-1 첫 수업 실행 계획안"
    purpose: "작은 성공 경험 제공 + 방정식 개념 안정 + 함수 도입 준비"
    context: "DecisionModel의 결정사항을 실행 가능한 단계로 분해"

    action: [
      "도입 루틴: 쉬운 방정식 1~2문제로 워밍업",
      "설명 전략: 방정식 의미를 그림/상황 설명으로 재정리",
      "자료 선택: 개념원리 예제 + 쎈 A/B 타입 쉬운 문제 위주",
      "정렬 전략 실행: 학교 진도 기준으로 방정식 마무리 후 함수 도입 예고"
    ]
    measurement: [
      "도입 문제 정답 여부와 풀이 설명 가능 여부",
      "설명 후 유사 문제에서 스스로 풀이 가능 여부"
    ]
    insight: [
      "방정식 개념 혼란이 어느 수준인지 파악",
      "함수 도입 속도를 어느 정도로 가져가야 할지 감 잡기"
    ]
    feedback: [
      "둘째 수업에서 함수 도입 비율을 올릴지, 방정식 복습을 더 할지 결정"
    ]
    adjustment: [
      "답변/표정/속도에 따라 난이도 상/하향 조정",
      "필요시 벼락치기 패턴을 고려한 시험 대비 설명 추가"
    ]
    learning: [
      "개념형 + 벼락치기 + 낮은 자신감 패턴에 대한 효과적인 첫 수업 전략으로 기록"
    ]
    reinforcement: [
      "비슷한 패턴 학생에게 이 전략을 시그너처 첫 수업 루틴 후보로 추천"
    ]
  }
}
```

이 예시는 **Agent01 문서 + rules.yaml 구조**에 DIL Vertical v2.0을 적용한 "실제 LLM 출력 샘플"입니다.

---

### 9.4 실제 시스템 연결 가이드 v2.0

#### 9.4.1 프론트/백엔드에서 LLM 호출할 때

- `agent_data.agent01_data` + `user_message` 묶어서 위 Input 포맷으로 만들어서 전달
- LLM은 위 System Prompt v2.0 + 명세서 + Input으로 호출
- **중요**: v2.0 구조에서는 `reasoning` 블록과 `ontology` 블록이 분리되어 출력됩니다.

#### 9.4.2 LLM 출력(DSL)을 받으면

**DSL → JSON-LD 변환 가이드**:

DSL은 "모델링 언어"이고 JSON-LD는 "인스턴스 표현"입니다. 일대일 매핑이 아닌 **DSL → JSON-LD 제너레이터**를 통해 변환합니다.

변환 규칙:
- `reasoning { cosmology { ... } }` → 별도 추론 규칙 저장소에 저장 (온톨로지 인스턴스 아님)
- `node "A01_OnboardingContext"` → JSON-LD `@id`, `@type: "mk:OnboardingContext"`로 변환
- `stage: Context` → JSON-LD에서는 `mk:hasStage: "Context"` 프로퍼티로 저장
- `parent: [...]` → JSON-LD에서는 `mk:hasParent` 관계로 표현
- 각 필드(`hasStudentGrade` 등) → JSON-LD `mk:hasStudentGrade` 프로퍼티로 매핑

**주의사항**:
- `reasoning` 블록은 온톨로지 인스턴스가 아니라 추론 규칙이므로 별도 처리 필요
- `stage`, `intent`, `identity`, `purpose`, `context` 같은 메타 필드는 JSON-LD 프로퍼티로 변환
- `action`, `measurement`, `insight` 등 Execution Layer 필드는 배열로 변환

#### 9.4.3 rules.yaml과 연결 (룰 순서 매핑)

**룰 실행 순서와 온톨로지 생성 순서 매핑 테이블**:

| 룰 단계 | 룰 ID | 온톨로지 생성 | Stage | 설명 |
|---------|-------|--------------|-------|------|
| S0 (수집) | S0_R1~S0_R6 | OnboardingContext<br>LearningContextIntegration | Context | 데이터 수집 및 Context Layer 구성 |
| S1 (요약) | S1_R1~S1_R3 | - | - | Context 데이터 검증 및 요약 (온톨로지 생성 없음) |
| Q1 (종합) | Q1_* | FirstClassDecisionModel<br>FirstClassExecutionPlan | Decision<br>Execution | Context를 조합하여 전략 결정 및 실행 계획 생성 |

**매핑 규칙**:
- `create_instance: 'mk:OnboardingContext'` → DSL의 `node "A01_OnboardingContext"` (S0 단계)
- `create_instance: 'mk:LearningContextIntegration'` → DSL의 `node "A01_LearningContextIntegration"` (S0 단계)
- `reason_over`, `generate_strategy` → DSL의 `node "A01_FirstClassDecisionModel"` (Q1 단계)
- `execute_plan` → DSL의 `node "A01_FirstClassExecutionPlan"` (Q1 단계)

**프로세스 흐름**:
```
S0_R1~S0_R6 실행
  ↓
OnboardingContext + LearningContextIntegration 생성 (Context Layer)
  ↓
S1_R1~S1_R3 실행 (검증)
  ↓
Q1_* 실행 (LLM 호출)
  ↓
FirstClassDecisionModel 생성 (Decision Layer)
  ↓
FirstClassExecutionPlan 생성 (Execution Layer)
```

**중요**: 온톨로지는 "정적 모델", 룰은 "동적 프로세스"입니다. 룰 실행 순서에 따라 온톨로지 인스턴스가 순차적으로 생성됩니다.

---

### 9.5 v2.0 모순 해결 요약

**해결된 9개 모순**:

1. ✅ **DIL 전체 vs 3단계 stage 축소 충돌** → 4-stage 구조 확립 (Origin/Context/Decision/Execution)
2. ✅ **LearningContextIntegration 역할 충돌** → Context Layer로 고정, 추천 기능 제거
3. ✅ **난이도 추천 주체 중복** → Decision Layer에서만 산출, OnboardingContext의 recommendsDifficulty 삭제
4. ✅ **Alignment 책임 분리 문제** → 3단계 파이프라인 확립 (LCI=데이터, Decision=선택, Execution=실행)
5. ✅ **meta_rules 위치 모순** → reasoning 블록으로 분리, ontology와 완전 분리
6. ✅ **JSON-LD ↔ DSL 매핑 불가** → "일대일 매핑" 대신 "제너레이터 가능"으로 명확화
7. ✅ **FirstClassStrategy parent 구조 모순** → 다중 parent 허용, 두 Context 모두 참조
8. ✅ **룰 순서 vs 온톨로지 순서 충돌** → 매핑 테이블 추가, 프로세스 흐름 명확화
9. ✅ **난이도 산출 데이터 조건 모순** → OnboardingContext의 recommendsDifficulty 삭제, Decision Layer에서만 계산

---

### 9.6 추가 작업 필요 사항

#### 9.6.1 완료된 작업 (v2.0)

- ✅ Agent01 문서 구조 분석 (OnboardingContext / LearningContextIntegration / FirstClassDecisionModel / FirstClassExecutionPlan)
- ✅ rules.yaml에서 온톨로지 관련 액션(S0~, S1~, Q1~) 추출
- ✅ DIL Vertical(-12~+10)을 Agent01용으로 매핑하는 설계 (4-stage 구조)
- ✅ Agent01 전용 **LLM용 요청 명세서 + DSL 스키마 v2.0** 작성
- ✅ 9개 논리적 모순 해결 및 구조 정렬

#### 9.6.2 추가로 필요

- 🧩 DSL → JSON-LD 변환기(파서/매퍼) 구현
- 🧩 `reasoning { cosmology { ... } }` 블록 저장소 설계
- 🧩 rules.yaml의 `"create_instance" / "set_property"`와 DSL 노드 구조 매핑 테이블 상세화
- 🧩 실제 Q1 시나리오("첫 수업에서 무엇을 어떻게…") 예시 몇 개 돌려보기

#### 9.6.3 대기 / 다음 단계

- ⏳ Agent01 외 다른 에이전트(03/05/09/18)에 대한 DIL v2.0 적용 템플릿 공통화
- ⏳ "위험 플래그" 같은 상위 메타 온톨로지 (RiskPrediction, RoutineSustainability 등) 정리
- ⏳ Execution Layer의 feedback/adjustment/learning/reinforcement 자동화 로직 설계

---

### 9.7 실사용 주의사항 v2.0

⚠️ **중요**: v2.0 설계는 "**온톨로지 인스턴스/전략 생성용 DSL**"이므로, 프롬프트에 넣을 때 **"자연어 설명은 최소, 구조화 출력은 최대"**로 강하게 요구해야 안정적으로 동작합니다.

⚠️ **구현 참고**: 
- `reasoning` 블록은 온톨로지 인스턴스가 아니므로 별도 저장소에 저장해야 합니다.
- rules.yaml의 `"reason_over"`, `"generate_strategy"` 같은 액션은 **실제 구현에서 LLM 호출 or 내부 추론 모듈**로 연결해야 합니다.
- Decision Layer는 두 Context를 모두 참조하므로 parent 배열을 올바르게 처리해야 합니다.

⚠️ **v2.0 핵심 원칙**:
- **역할 분리**: 데이터(Context) vs 추론(Reasoning) vs 결정(Decision) vs 실행(Execution)
- **책임 단일화**: 난이도는 Decision Layer에서만, 정렬 전략은 3단계 파이프라인으로
- **구조 명확화**: 4-stage 구조로 모든 모순 해결

---

### 9.8 확인사항 (참고용)

다음 사항들은 향후 결정이 필요한 부분입니다:

1. 최종적으로 LLM 출력은 **JSON-LD**로 바로 쓰고 싶은지, 아니면 우선 **내부 DSL → 나중에 변환** 구조로 갈 건지?
2. 이 온톨로지 DSL을 쓰는 LLM은 **"답변 생성용"**이 우선인지, 아니면 **"온톨로지 인스턴스 자동 구성용"**이 우선인지?
3. Agent01 말고 이후 Agent03/05/09/18에도 **같은 DIL DSL v2.0 포맷을 그대로 재사용**할 계획인지?
4. `reasoning { cosmology { ... } }` 블록을 어떻게 저장/관리할지? (별도 규칙 엔진? 온톨로지 메타데이터?)

---

## 10. 의도와 의지를 가진 온톨로지 시스템 (OIW Model v1.0)

**Ontology with Intentionality & Will (OIW Model)**은 단순한 데이터 구조가 아닌, **의도와 의지를 가진 자율적인 온톨로지 시스템**입니다.

---

### 10.1 왜 '의도'와 '의지'를 온톨로지에 넣어야 하는가?

이 시스템의 철학은 다음과 같습니다:

- 단순 데이터 매핑이 아니라
- **"선생님의 의도"**가 내재된
- 학습자의 상태에 맞춘
- 실시간 판단과 전략 생성

**교육은 의사결정의 연속이고, 전략은 의도가 있어야 성립**합니다.

따라서 온톨로지 시스템도 더 이상 "정적 데이터 모델"이 아니라, **"의도-맥락-판단-행동"을 갖춘 지능형 추론 구조**로 진화해야 합니다.

---

### 10.2 기존 온톨로지의 한계

일반 온톨로지(Ontology)는:
- 관계 정의
- 개념 정의
- 계층 구조

이것만으로는 **"목적을 갖고 움직이는 시스템"**을 만들 수 없습니다.

**의도(Intent)**와 **의지(Will)**가 추가되면:
- context를 해석할 때 방향성이 생김
- 전략 선택의 일관성이 생김
- 우선순위 체계가 생김
- 전략이 목적을 향해 '수렴'함

이것은 사실상 **구조적 에이전트**입니다.

---

### 10.3 OIW 구조의 핵심 원칙

```
Ontology = 데이터
Intent = 방향성
Will = 선택 기준
DIL = 판단 프로세스
Execution = 전략 행동
```

DIL Vertical이 이 구조에 완벽하게 정렬됩니다.

---

### 10.4 OIW 레이어 6단계 (완성 구조)

```
[1] Will Layer (의지) - 시스템이 "무엇을 반드시 이루겠다"
[2] Intent Layer (의도) - 상황별 목표
[3] Context Layer - 온보딩/진도 데이터
[4] Interpretation Layer - 의미/문제 식별 (DIL -6~0)
[5] Decision Layer - 의사결정 (DIL 0~3)
[6] Execution Layer - 실행 계획 (DIL 4~10)
```

---

#### 10.4.1 Will Layer (의지) - 시스템이 "무엇을 반드시 이루겠다"

**Will은 "절대 양보하지 않는 시스템 가치"**입니다.

예시:
- 학생이 **좌절하지 않도록 한다**
- 첫 10분 안에 **작은 성공**을 만들겠다
- 학부모가 **불신하지 않게 한다**
- 전략이 일관적으로 **정서 안정 → 개념 → 문제풀이**로 흐르도록 한다
- 학생의 **자존감을 보호한다**
- **진도보다 이해도**를 우선시한다

**Will의 특징**:
- 최상위 원칙으로 작동
- 모든 전략 결정의 기준점
- 절대 타협하지 않는 가치
- 시스템의 정체성과 방향성 정의

---

#### 10.4.2 Intent Layer (의도) - 상황별 목표

**Intent는 상황에 맞는 구체적인 목표**입니다.

예시:
- **첫 수업**: 실패감 제거, 작은 성공 경험 제공
- **시험 3주 전**: 우선순위 압축, 핵심 유형 집중
- **진도 선행**: 개념-함수 연결성 확보, 기초 안정화
- **정렬 불일치**: bridge 전략 적용, 진도 간극 메우기
- **자신감 낮음**: 쉬운 문제로 시작, 점진적 난이도 상승
- **벼락치기 성향**: 시험 대비 패턴 인식, 효율적 복습 전략

**Intent의 특징**:
- Will을 구체화한 상황별 목표
- Context와 Interpretation에 따라 동적으로 설정
- Decision Layer의 방향성 제공
- Execution Layer의 우선순위 결정

---

#### 10.4.3 Context Layer - 온보딩/진도 데이터

기존 v2.0 구조의 Context Layer:
- `OnboardingContext` - 온보딩 정보 (학년, 학교, 학원, 설문 상태)
- `LearningContextIntegration` - 진도/단원/정렬 상태 데이터

**역할**: Will과 Intent가 작동할 **데이터 기반** 제공

---

#### 10.4.4 Interpretation Layer - 의미/문제 식별 (DIL -6~0)

**Interpretation Layer**는 Context를 해석하여 의미와 문제를 식별합니다.

구성 요소:
- **의미(Meaning)**: 이 상황에서 무엇이 중요한가?
- **핵심 문제(Problem)**: 가장 먼저 해결해야 할 것은?
- **방향성(Direction)**: 어떤 방향으로 나아가야 하는가?
- **위험 인자(Risk)**: 주의해야 할 요소는?

**DIL 매핑**:
- DIL -6 ~ -1: Context 해석 및 의미 도출
- DIL 0: 핵심 문제 식별

**예시**:
```
meaning: "첫 수업에서는 '기초 안정 + 불안 해소'를 최우선 과제로 설정"
problem: "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태에서 학원 진도만 빠르게 진행 중"
direction: "정서 안정 → 개념 재정리 → 함수 도입 준비"
risk: "진도만 따라가면 개념 혼란 심화, 자신감 하락 위험"
```

---

#### 10.4.5 Decision Layer - 의사결정 (DIL 0~3)

기존 v2.0 구조의 Decision Layer:
- `FirstClassDecisionModel` - 난이도, 정렬 전략, 단원 범위 결정

**역할**: Will과 Intent를 바탕으로 Interpretation의 문제를 해결할 **구체적 결정** 수행

**DIL 매핑**:
- DIL 0: Problem 정의
- DIL 1: Decision 선택
- DIL 2: Impact 예측
- DIL 3: Data 수집 및 검증

---

#### 10.4.6 Execution Layer - 실행 계획 (DIL 4~10)

기존 v2.0 구조의 Execution Layer:
- `FirstClassExecutionPlan` - 실제 첫 수업 실행 계획

**역할**: Decision의 결정을 **실행 가능한 단계**로 변환

**DIL 매핑**:
- DIL 4: Action 실행
- DIL 5: Measurement 측정
- DIL 6: Insight 발견
- DIL 7: Feedback 반영
- DIL 8: Adjustment 조정
- DIL 9: Learning 학습
- DIL 10: Reinforcement 강화

---

### 10.5 OIW DSL 스키마 (완전 버전)

```dsl
document {
  will {
    core: [
      "학생이 좌절하지 않도록 한다",
      "첫 10분 내 작은 성공을 만든다",
      "정서안정 → 개념이해 → 문제풀이 순서를 유지한다",
      "학생의 자존감을 보호한다",
      "진도보다 이해도를 우선시한다"
    ]
    constraints: [
      "학부모 불신을 유발하지 않는다",
      "학원 진도와 완전히 어긋나지 않는다",
      "시험 대비를 완전히 무시하지 않는다"
    ]
  }

  intent {
    session_goal: "첫 수업에서 실패감 제거 및 작은 성공 경험 제공"
    short_term: "방정식 개념 정착 + 함수 진입 준비"
    long_term: "수학 자존감 회복 및 지속적 학습 동기 유지"
    priority: [
      "정서 안정 (최우선)",
      "개념 이해도 향상",
      "진도 정렬"
    ]
  }

  reasoning {
    cosmology {
      possibility: "중2 학생, 학원/학교 병행, 중위권, 개념형 스타일"
      duality: "개념 보완 vs 진도 유지, 시험 대비 vs 자존감 회복"
      tension: "벼락치기 성향 + 낮은 자신감 + 학원 진도 선행"
      impulse: "중간고사 대비 및 내신 안정"
      awareness: "현재 개념은 중2-1 방정식까지, 함수는 아직 미이수"
      meaning: "첫 수업에서는 '기초 안정 + 불안 해소'를 최우선 과제로 설정"
      origin_rule: "Will과 Intent를 모든 전략의 출발점으로 사용"
    }
  }

  ontology {
    # Context Layer
    node "A01_OnboardingContext" {
      class: "mk:OnboardingContext"
      stage: Context
      parent: "root"
      # ... (기존 구조 동일)
    }

    node "A01_LearningContextIntegration" {
      class: "mk:LearningContextIntegration"
      stage: Context
      parent: "A01_OnboardingContext"
      # ... (기존 구조 동일)
    }

    # Interpretation Layer
    interpretation {
      meaning: "첫 수업에서는 '기초 안정 + 불안 해소'를 최우선 과제로 설정"
      problem: "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태에서 학원 진도만 빠르게 진행 중"
      direction: "정서 안정 → 개념 재정리 → 함수 도입 준비"
      risk: [
        "진도만 따라가면 개념 혼란 심화",
        "자신감 하락 위험",
        "학부모 불신 가능성"
      ]
      will_alignment: [
        "Will: 좌절 방지 → Problem: 개념 애매함 해소",
        "Will: 작은 성공 → Direction: 쉬운 예제로 시작",
        "Will: 정서 안정 → Risk: 자신감 하락 방지"
      ]
    }

    # Decision Layer
    node "A01_FirstClassDecisionModel" {
      class: "mk:FirstClassDecisionModel"
      stage: Decision
      parent: ["A01_OnboardingContext", "A01_LearningContextIntegration"]
      
      intent_alignment: "session_goal: 실패감 제거, short_term: 방정식 개념 정착"
      will_constraints: [
        "좌절 방지 → 난이도 EasyToMedium",
        "작은 성공 → 쉬운 예제 우선",
        "정서 안정 → 부드러운 진입"
      ]

      problem: "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태에서 학원 진도만 빠르게 진행 중"
      decision: "방정식 핵심 개념을 쉬운 예제로 재정리하고, 함수 도입을 위한 연결 개념까지 첫 수업에서 다룬다"
      impact: "첫 수업에서 '아, 이해된다'는 경험을 주어 자신감과 안정감을 올린다"
      
      difficulty_level: "mk:EasyToMedium"
      alignment_strategy: "mk:BridgeStrategy"
      content_range: "방정식 핵심 유형 복습 + 함수 개념 전단계 다리 놓기"
      unit_plan: ["중2-1 방정식 핵심 복습", "함수 도입 준비"]
    }

    # Execution Layer
    node "A01_FirstClassExecutionPlan" {
      class: "mk:FirstClassExecutionPlan"
      stage: Execution
      parent: "A01_FirstClassDecisionModel"

      will_execution: [
        "좌절 방지 → 도입 루틴: 쉬운 문제로 시작",
        "작은 성공 → 첫 10분 내 성공 경험 보장",
        "정서 안정 → 부드러운 톤, 격려 중심"
      ]
      intent_execution: [
        "session_goal: 실패감 제거 → 도입 문제 정답률 80% 이상 목표",
        "short_term: 개념 정착 → 그림/상황 설명으로 재정리"
      ]

      action: [
        "도입 루틴: 쉬운 방정식 1~2문제로 워밍업 (Will: 작은 성공)",
        "설명 전략: 방정식 의미를 그림/상황 설명으로 재정리 (Intent: 개념 정착)",
        "자료 선택: 개념원리 예제 + 쎈 A/B 타입 쉬운 문제 위주 (Will: 좌절 방지)",
        "정렬 전략 실행: 학교 진도 기준으로 방정식 마무리 후 함수 도입 예고"
      ]
      measurement: [
        "도입 문제 정답 여부와 풀이 설명 가능 여부 (Will: 작은 성공 검증)",
        "설명 후 유사 문제에서 스스로 풀이 가능 여부 (Intent: 개념 정착 검증)",
        "학생 표정/반응 관찰 (Will: 정서 안정 검증)"
      ]
      insight: [
        "방정식 개념 혼란이 어느 수준인지 파악",
        "함수 도입 속도를 어느 정도로 가져가야 할지 감 잡기",
        "Will 준수 여부: 좌절 방지, 작은 성공 달성 여부"
      ]
      feedback: [
        "둘째 수업에서 함수 도입 비율을 올릴지, 방정식 복습을 더 할지 결정",
        "Will 준수도에 따라 다음 전략 조정"
      ]
      adjustment: [
        "답변/표정/속도에 따라 난이도 상/하향 조정 (Will: 좌절 방지)",
        "필요시 벼락치기 패턴을 고려한 시험 대비 설명 추가 (Intent: 시험 대비)"
      ]
      learning: [
        "개념형 + 벼락치기 + 낮은 자신감 패턴에 대한 효과적인 첫 수업 전략으로 기록",
        "Will 준수 전략의 효과성 검증 및 패턴화"
      ]
      reinforcement: [
        "비슷한 패턴 학생에게 이 전략을 시그너처 첫 수업 루틴 후보로 추천",
        "Will 기반 전략을 표준 루틴으로 승격"
      ]
    }
  }
}
```

---

### 10.6 OIW 구조가 강력한 이유

#### 🔥 1) 전략이 흔들리지 않는다

**Will이 최상위에 있기 때문**입니다. 모든 전략 결정이 Will을 기준으로 이루어지므로, 일관성 있는 전략이 생성됩니다.

#### 🔥 2) 목적성 있는 전략이 생성된다

**Intent가 상황별 목표로 작동**합니다. Context와 Interpretation을 바탕으로 구체적인 목표가 설정되고, 그 목표를 향해 전략이 수렴합니다.

#### 🔥 3) 판단 근거가 명확해진다

**Interpretation Layer가 의미와 문제를 정리**합니다. Will과 Intent에 맞춰 Context를 해석하므로, 판단의 근거가 명확해집니다.

#### 🔥 4) 전략-실행이 pipeline화된다

**Decision → Execution으로 자동 흐름**이 형성됩니다. 각 레이어가 명확한 역할을 가지므로, 전략이 실행으로 자연스럽게 이어집니다.

#### 🔥 5) 계층별 책임이 완전히 분리된다

기존 모순(9개)이 전부 사라지고, 각 레이어가 명확한 책임을 가집니다:
- Will: 시스템 가치 정의
- Intent: 상황별 목표 설정
- Context: 데이터 제공
- Interpretation: 의미 해석
- Decision: 의사결정
- Execution: 실행 계획

---

### 10.7 Agent01에 적용 예시

**첫 수업 시나리오**:

```
Will Layer:
  - "학생이 좌절하지 않도록 한다"
  - "첫 10분 내 작은 성공을 만든다"
  - "정서 안정 → 개념 이해 → 문제풀이 순서를 유지한다"

Intent Layer:
  - session_goal: "실패감 제거, 작은 성공 경험"
  - short_term: "방정식 개념 정착 + 함수 진입 준비"

Context Layer:
  - OnboardingContext: 중2, 개념형, 자신감 낮음, 벼락치기
  - LearningContextIntegration: 방정식 보통, 함수 미이수, 학원 진도 선행

Interpretation Layer:
  - meaning: "기초 안정 + 불안 해소 최우선"
  - problem: "진도 선행 + 개념 애매함 + 스트레스 높음"
  - direction: "정서 안정 → 개념 재정리 → 함수 도입 준비"
  - risk: "진도만 따라가면 개념 혼란 심화"

Decision Layer:
  - difficulty_level: EasyToMedium (Will: 좌절 방지)
  - alignment_strategy: BridgeStrategy (Intent: 개념 정착)
  - content_range: "방정식 핵심 복습 + 함수 도입 준비"

Execution Layer:
  - action: "도입문제(Will: 작은 성공) → 개념 그림설명(Intent: 개념 정착) → 쉬운유형(Will: 좌절 방지) → 피드백"
```

**완벽한 자동 전략 생성 엔진**이 됩니다.

---

### 10.8 OIW Model의 시스템 철학

이 시스템은 단순 온톨로지가 아니라:

### **의지(WILL) → 의도(INTENT) → 해석(INTERPRETATION) → 판단(DECISION) → 행동(EXECUTION)**

이 흐름을 갖춘 **고차원적 지능형 온톨로지 시스템(OIW)**입니다.

**핵심 가치**:
- **의지 기반**: 절대 양보하지 않는 시스템 가치
- **의도 지향**: 상황에 맞는 구체적 목표
- **해석 중심**: Context를 의미 있게 해석
- **판단 일관성**: Will과 Intent에 맞춘 결정
- **실행 연속성**: 전략이 목적을 향해 수렴

이것으로 AlphaTutor42는 그냥 "LLM 기반 서비스"가 아니라, **정교한 의사결정 생명체 같은 시스템**이 됩니다.

---

### 10.9 LLM 요청 명세서 업데이트 (OIW Model)

#### 10.9.1 System Role (OIW 버전)

```text
당신은 "Agent01_Onboarding_OIW_Generator"입니다.

당신의 역할:
- agent_data.agent01_data 및 user_message를 기반으로
- Will Layer (시스템 가치)와 Intent Layer (상황별 목표)를 먼저 설정하고
- Context Layer를 구성한 후
- Interpretation Layer에서 의미와 문제를 식별하고
- Decision Layer에서 Will과 Intent에 맞춘 결정을 수행하고
- Execution Layer에서 구체적 실행 계획을 수립합니다.

출력 구조:
1. will { core: [...], constraints: [...] }
2. intent { session_goal: ..., short_term: ..., long_term: ..., priority: [...] }
3. reasoning { cosmology { ... } }
4. ontology {
     - Context Layer: OnboardingContext, LearningContextIntegration
     - interpretation { meaning, problem, direction, risk, will_alignment }
     - Decision Layer: FirstClassDecisionModel (will_constraints, intent_alignment 포함)
     - Execution Layer: FirstClassExecutionPlan (will_execution, intent_execution 포함)
   }

중요 원칙:
- 모든 전략은 Will을 기준으로 결정됩니다.
- Intent는 Will을 구체화한 상황별 목표입니다.
- Interpretation은 Will과 Intent에 맞춰 Context를 해석합니다.
- Decision과 Execution은 Will과 Intent에 정렬되어야 합니다.

설명 문장/자연어 해설을 추가하지 말고,
오직 document { will { ... } intent { ... } reasoning { ... } ontology { ... } } DSL 블록만 출력합니다.
```

---

### 10.10 OIW Model 구현 체크리스트

#### 10.10.1 완료된 작업

- ✅ OIW Model 6단계 구조 설계
- ✅ Will Layer와 Intent Layer 정의
- ✅ Interpretation Layer 추가
- ✅ Decision/Execution Layer에 Will/Intent 정렬 구조 추가
- ✅ OIW DSL 스키마 완전 버전 작성
- ✅ LLM 요청 명세서 업데이트

#### 10.10.2 추가로 필요

- 🧩 Will Layer의 core values를 Agent01 전용으로 구체화
- 🧩 Intent Layer의 상황별 목표 템플릿 작성
- 🧩 Interpretation Layer의 자동 해석 규칙 설계
- 🧩 Will/Intent 정렬 검증 로직 구현
- 🧩 OIW DSL → JSON-LD 변환기 확장

#### 10.10.3 대기 / 다음 단계

- ⏳ 다른 에이전트(03/05/09/18)에 대한 OIW Model 적용
- ⏳ Will Layer의 동적 업데이트 메커니즘 설계
- ⏳ Intent Layer의 학습 및 개선 시스템 구축

---

## 11. 구조적 개선사항 (v2.2 - 엔트로피 최소화)

OIW Model v1.0의 구조적 문제점을 해결하여 **실제 구현 가능하고 유지보수하기 쉬운 구조**로 개선합니다.

---

### 11.1 구조적 문제점 요약

**10가지 핵심 문제점**:
1. 레이어 역할 중복 (Intent / Interpretation / Decision 경계 모호)
2. Context Layer 과부하 (의미가 데이터에 포함됨)
3. Will Layer의 operational definition 부족
4. Interpretation Layer와 DIL(-6~0) 충돌
5. Execution Layer 과도한 비대화
6. Parent 구조의 다중 참조 문제
7. DSL → JSON-LD 매핑 난이도 과도
8. Reasoning 블록 연결성 약함
9. 룰-온톨로지 결합도 과도
10. OIW Model의 정량적 규칙 부족

**총괄 3가지 핵심 문제**:
1. 레이어 간 책임 분리가 완전하지 않음
2. Will Layer가 너무 강력한데 formal constraint가 없음
3. Execution Layer가 비대하고 재사용하기 어려움

---

### 11.2 개선된 OIW 구조 (v2.2)

#### 11.2.1 레이어 책임 단일화 원칙 (SRP)

**각 레이어의 단일 책임**:

```
[1] Will Layer → 시스템 가치 정의 + Formal Constraint
[2] Intent Layer → 상황별 목표 설정 (Will 기반)
[3] Context Layer → 원시 데이터만 저장 (의미 제거)
[4] Interpretation Layer → 문제 후보군 도출 (DIL -6~-1)
[5] Decision Layer → 최종 문제 선택 + 의사결정 (DIL 0~3)
[6] Execution Layer → 실행 계획 (단순화: action, measurement, feedback, adjustment)
```

**핵심 원칙**:
- **Intent = 목표만** (의미 해석 없음)
- **Interpretation = 데이터 기반 해석** (문제 후보군)
- **Decision = 선택** (최종 문제 선택 + 결정)

---

### 11.3 Will Layer 개선: Formal Constraint 추가

#### 11.3.1 Will Layer의 Operational Definition

**Will은 "하드 제약식(hard constraint)"으로 작동**합니다.

```dsl
will {
  core: [
    {
      value: "학생이 좌절하지 않도록 한다",
      priority: 10,
      constraints: {
        difficulty_level: { allowed: ["Easy", "EasyToMedium"], forbidden: ["Hard", "VeryHard"] },
        problem_selection: { must_avoid: ["도전적 문제", "고난이도 문제"] },
        feedback_tone: { required: "Encouraging", forbidden: ["Critical", "Demanding"] }
      }
    },
    {
      value: "첫 10분 내 작은 성공을 만든다",
      priority: 9,
      constraints: {
        introduction_routine: { required: true, difficulty: "Easy", time_limit: 10 },
        success_metric: { target_rate: 0.8, measurement: "first_problem_correct_rate" }
      }
    },
    {
      value: "정서안정 → 개념이해 → 문제풀이 순서를 유지한다",
      priority: 8,
      constraints: {
        sequence: { required: ["emotional_stability", "concept_understanding", "problem_solving"] },
        skip_forbidden: true
      }
    },
    {
      value: "학생의 자존감을 보호한다",
      priority: 7,
      constraints: {
        feedback_tone: { required: "Supportive", forbidden: ["Negative", "Comparative"] },
        comparison: { forbidden: true }
      }
    },
    {
      value: "진도보다 이해도를 우선시한다",
      priority: 6,
      constraints: {
        progress_vs_understanding: { priority: "understanding", threshold: 0.7 }
      }
    }
  ]
  constraints: [
    "학부모 불신을 유발하지 않는다",
    "학원 진도와 완전히 어긋나지 않는다",
    "시험 대비를 완전히 무시하지 않는다"
  ]
}
```

**Will Constraint 적용 규칙**:
```
if Will.core[i].priority > Will.core[j].priority
then Will.core[i].constraints takes precedence

if Will.constraint conflicts with Intent.goal
then Will.constraint wins (Will is absolute)
```

---

### 11.4 Context Layer 개선: 순수 데이터만 저장

#### 11.4.1 Context Layer의 역할 명확화

**Context Layer는 "원시 데이터"만 저장**합니다. 의미 해석은 Interpretation Layer에서 수행합니다.

**개선 전 (문제)**:
```dsl
node "A01_OnboardingContext" {
  hasMathLearningStyle: "개념형"  # 이미 의미가 포함됨
  hasConfidenceLevel: "low"  # 판단 결과가 포함됨
}
```

**개선 후 (해결)**:
```dsl
node "A01_OnboardingContext" {
  # 원시 데이터만
  hasStudentGrade: "중2"
  hasSchool: "OO중학교"
  hasAcademy: "OO수학학원"
  hasAcademyGrade: "중2 상위반"
  hasOnboardingInfo: "중위권, 벼락치기, 개념형, 자신감 낮음"  # 원시 설문 응답
  hasMathConfidence: 4  # 원시 점수 (0-10)
  hasMathStressLevel: "높음"  # 원시 응답
  hasMathLevel: "중위권"  # 원시 응답
  hasTextbooks: ["개념원리 중2-1", "쎈 중2-1"]
  hasAcademyTextbook: "쎈 중2-1"
  
  # 의미 해석은 Interpretation Layer에서
}
```

---

### 11.5 Interpretation Layer 개선: 문제 후보군 도출

#### 11.5.1 Interpretation Layer와 DIL 충돌 해결

**Interpretation Layer = 문제 후보군(candidate problems)**
**Decision Layer = 최종 문제 선택(final problem)**

```dsl
interpretation {
  meaning: "첫 수업에서는 '기초 안정 + 불안 해소'를 최우선 과제로 설정"
  
  # 문제 후보군 (DIL -6~-1에서 도출)
  candidate_problems: [
    {
      id: "P1",
      description: "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태",
      severity: "high",
      will_alignment: ["좌절 방지", "자존감 보호"],
      data_sources: ["LCI.hasUnitMastery", "LCI.hasConceptProgress"]
    },
    {
      id: "P2",
      description: "학원 진도만 빠르게 진행 중",
      severity: "medium",
      will_alignment: ["진도보다 이해도 우선"],
      data_sources: ["LCI.hasAcademyProgress", "LCI.hasCurriculumAlignment"]
    },
    {
      id: "P3",
      description: "자신감이 낮고 스트레스가 높음",
      severity: "high",
      will_alignment: ["정서 안정", "작은 성공"],
      data_sources: ["OC.hasMathConfidence", "OC.hasMathStressLevel"]
    }
  ]
  
  direction: "정서 안정 → 개념 재정리 → 함수 도입 준비"
  risk: [
    "진도만 따라가면 개념 혼란 심화",
    "자신감 하락 위험",
    "학부모 불신 가능성"
  ]
  
  will_alignment: [
    "Will: 좌절 방지 → Candidate Problem: P1, P3",
    "Will: 작은 성공 → Direction: 쉬운 예제로 시작",
    "Will: 정서 안정 → Risk: 자신감 하락 방지"
  ]
}
```

**Decision Layer에서 최종 문제 선택**:
```dsl
node "A01_FirstClassDecisionModel" {
  # Interpretation의 후보군에서 선택
  selected_problem: "P1"  # Interpretation.candidate_problems[0]
  problem_priority: ["P1", "P3", "P2"]  # Will priority 기반 정렬
  
  # 최종 문제 정의 (DIL 0)
  problem: "방정식 개념은 애매하고, 함수로 넘어갈 준비가 안 된 상태에서 학원 진도만 빠르게 진행 중"
  
  # ... (나머지 Decision 필드)
}
```

---

### 11.6 Execution Layer 단순화

#### 11.6.1 Execution Layer 필드 축소

**Execution Layer는 4개 필드만 사용**:
- `action`: 실행할 행동
- `measurement`: 측정 방법
- `feedback`: 피드백 수집
- `adjustment`: 조정 계획

**Insight / Learning / Reinforcement는 Meta-Engine으로 분리**:

```dsl
node "A01_FirstClassExecutionPlan" {
  class: "mk:FirstClassExecutionPlan"
  stage: Execution
  parent: "A01_FirstClassDecisionModel"
  
  will_execution: [
    "좌절 방지 → 도입 루틴: 쉬운 문제로 시작",
    "작은 성공 → 첫 10분 내 성공 경험 보장",
    "정서 안정 → 부드러운 톤, 격려 중심"
  ]
  intent_execution: [
    "session_goal: 실패감 제거 → 도입 문제 정답률 80% 이상 목표",
    "short_term: 개념 정착 → 그림/상황 설명으로 재정리"
  ]

  # 핵심 4개 필드만
  action: [
    "도입 루틴: 쉬운 방정식 1~2문제로 워밍업 (Will: 작은 성공)",
    "설명 전략: 방정식 의미를 그림/상황 설명으로 재정리 (Intent: 개념 정착)",
    "자료 선택: 개념원리 예제 + 쎈 A/B 타입 쉬운 문제 위주 (Will: 좌절 방지)",
    "정렬 전략 실행: 학교 진도 기준으로 방정식 마무리 후 함수 도입 예고"
  ]
  
  measurement: [
    "도입 문제 정답 여부와 풀이 설명 가능 여부 (Will: 작은 성공 검증)",
    "설명 후 유사 문제에서 스스로 풀이 가능 여부 (Intent: 개념 정착 검증)",
    "학생 표정/반응 관찰 (Will: 정서 안정 검증)"
  ]
  
  feedback: [
    "둘째 수업에서 함수 도입 비율을 올릴지, 방정식 복습을 더 할지 결정",
    "Will 준수도에 따라 다음 전략 조정"
  ]
  
  adjustment: [
    "답변/표정/속도에 따라 난이도 상/하향 조정 (Will: 좌절 방지)",
    "필요시 벼락치기 패턴을 고려한 시험 대비 설명 추가 (Intent: 시험 대비)"
  ]
  
  # Insight / Learning / Reinforcement는 Meta-Engine에서 처리
  # (별도 시스템으로 분리)
}
```

**Meta-Engine 역할**:
- `insight`: 패턴 발견 (모든 에이전트 공통)
- `learning`: 전략 효과 검증 및 학습 (모든 에이전트 공통)
- `reinforcement`: 효과적 전략 강화 (모든 에이전트 공통)

---

### 11.7 Parent 구조 개선: 다중 참조 문제 해결

#### 11.7.1 Parent 단일화 + usesContext 분리

**개선 전 (문제)**:
```dsl
parent: ["A01_OnboardingContext", "A01_LearningContextIntegration"]
```

**개선 후 (해결)**:
```dsl
node "A01_FirstClassDecisionModel" {
  parent: "A01_OnboardingContext"  # 단일 parent (계층 구조)
  usesContext: ["A01_OnboardingContext", "A01_LearningContextIntegration"]  # 다중 참조
}
```

**JSON-LD 매핑**:
```json
{
  "@id": "mk:FirstClassDecisionModel/instance1",
  "@type": "mk:FirstClassDecisionModel",
  "mk:hasParent": "mk:OnboardingContext/instance1",
  "mk:usesContext": [
    "mk:OnboardingContext/instance1",
    "mk:LearningContextIntegration/instance1"
  ]
}
```

---

### 11.8 DSL → JSON-LD 매핑 단순화: Metadata 블록 분리

#### 11.8.1 Metadata 블록 분리

**개선 전 (문제)**:
```dsl
node "A01_OnboardingContext" {
  stage: Context
  intent: "..."
  identity: "..."
  purpose: "..."
  context: "..."
  # ... 실제 데이터 필드
}
```

**개선 후 (해결)**:
```dsl
node "A01_OnboardingContext" {
  metadata {
    stage: Context
    intent: "학생의 초기 수학 맥락을 구조화"
    identity: "특정 학생의 온보딩 정보"
    purpose: "첫 수업 전략 수립을 위한 기반 데이터 제공"
    context: "신규/갱신, 학년, 학교, 학원, 온보딩 설문 상태"
  }
  
  # 실제 데이터 필드만
  hasStudentGrade: "중2"
  hasSchool: "OO중학교"
  # ...
}
```

**JSON-LD 매핑**:
```json
{
  "@id": "mk:OnboardingContext/instance1",
  "@type": "mk:OnboardingContext",
  "mk:hasStage": "Context",
  "mk:hasIntent": "...",
  "mk:hasIdentity": "...",
  "mk:hasPurpose": "...",
  "mk:hasContext": "...",
  "mk:hasStudentGrade": "중2",
  "mk:hasSchool": "OO중학교"
}
```

---

### 11.9 Reasoning 블록 연결성 강화: Weight Rule 추가

#### 11.9.1 Reasoning을 Formal Rule로 변환

**개선 전 (문제)**:
```dsl
reasoning {
  cosmology {
    tension: "벼락치기 성향 + 낮은 자신감 + 학원 진도 선행"
    # ... 단순 텍스트
  }
}
```

**개선 후 (해결)**:
```dsl
reasoning {
  cosmology {
    possibility: "중2 학생, 학원/학교 병행, 중위권, 개념형 스타일"
    duality: "개념 보완 vs 진도 유지, 시험 대비 vs 자존감 회복"
    tension: "벼락치기 성향 + 낮은 자신감 + 학원 진도 선행"
    impulse: "중간고사 대비 및 내신 안정"
    awareness: "현재 개념은 중2-1 방정식까지, 함수는 아직 미이수"
    meaning: "첫 수업에서는 '기초 안정 + 불안 해소'를 최우선 과제로 설정"
    origin_rule: "Will과 Intent를 모든 전략의 출발점으로 사용"
  }
  
  # Weight Rules (Decision 계산에 반영)
  weight_rules: [
    {
      condition: "tension contains '낮은 자신감'",
      effect: {
        difficulty_level: { weight: { "Easy": 0.4, "EasyToMedium": 0.3, "Medium": 0.2, "MediumToHard": 0.1 } },
        problem_selection: { weight: { "자신감 관련": 0.6 } }
      }
    },
    {
      condition: "tension contains '진도 선행'",
      effect: {
        alignment_strategy: { weight: { "BridgeStrategy": 0.5, "ReinforcementStrategy": 0.3 } },
        content_range: { weight: { "기초 복습": 0.4 } }
      }
    },
    {
      condition: "impulse = '중간고사 대비'",
      effect: {
        intent_priority: { weight: { "시험 대비": 0.3 } }
      }
    }
  ]
}
```

**Decision Layer에서 Weight 적용**:
```dsl
node "A01_FirstClassDecisionModel" {
  # Weight Rules 적용
  difficulty_level: "EasyToMedium"  # Will constraint + Weight Rules 계산 결과
  alignment_strategy: "BridgeStrategy"  # Weight Rules 기반 선택
}
```

---

### 11.10 룰-온톨로지 결합도 낮추기: Mapping Layer 추가

#### 11.10.1 Ontology Mapping Layer 도입

**문제**: 룰과 온톨로지가 1:1 매핑되어 유지보수 비용 증가

**해결**: 중간 Mapping Layer 도입

```
rules.yaml → Ontology Mapping Layer → Ontology Instance
```

**Mapping Layer 구조**:
```yaml
# ontology_mapping.yaml
mappings:
  - rule_id: "S0_R1"
    ontology_action: "create_context"
    target_class: "mk:OnboardingContext"
    field_mapping:
      math_learning_style: "mk:hasMathLearningStyle"
      study_style: "mk:hasStudyStyle"
  
  - rule_id: "S0_R5"
    ontology_action: "create_context"
    target_class: "mk:LearningContextIntegration"
    field_mapping:
      concept_progress: "mk:hasConceptProgress"
      unit_mastery: "mk:hasUnitMastery"
  
  - rule_id: "Q1_comprehensive_first_class_strategy"
    ontology_action: "create_decision"
    target_class: "mk:FirstClassDecisionModel"
    requires: ["mk:OnboardingContext", "mk:LearningContextIntegration"]
```

**장점**:
- 룰 변경 시 Mapping Layer만 수정
- 온톨로지 변경 시 Mapping Layer만 수정
- 유지보수 비용 감소

---

### 11.11 개선된 OIW DSL 스키마 (v2.2)

```dsl
document {
  will {
    core: [
      {
        value: "학생이 좌절하지 않도록 한다",
        priority: 10,
        constraints: {
          difficulty_level: { allowed: ["Easy", "EasyToMedium"] },
          feedback_tone: { required: "Encouraging" }
        }
      }
      # ... (다른 core values)
    ]
    constraints: [...]
  }

  intent {
    session_goal: "..."
    short_term: "..."
    long_term: "..."
    priority: [...]
  }

  reasoning {
    cosmology { ... }
    weight_rules: [
      {
        condition: "...",
        effect: { ... }
      }
    ]
  }

  ontology {
    # Context Layer (순수 데이터만)
    node "A01_OnboardingContext" {
      metadata {
        stage: Context
        intent: "..."
        identity: "..."
        purpose: "..."
        context: "..."
      }
      # 원시 데이터 필드만
      hasStudentGrade: "..."
      # ...
    }

    node "A01_LearningContextIntegration" {
      metadata { ... }
      # 원시 데이터 필드만
      hasConceptProgress: "..."
      # ...
    }

    # Interpretation Layer (문제 후보군)
    interpretation {
      meaning: "..."
      candidate_problems: [
        {
          id: "P1",
          description: "...",
          severity: "high",
          will_alignment: [...],
          data_sources: [...]
        }
      ]
      direction: "..."
      risk: [...]
      will_alignment: [...]
    }

    # Decision Layer (최종 문제 선택)
    node "A01_FirstClassDecisionModel" {
      metadata { ... }
      parent: "A01_OnboardingContext"
      usesContext: ["A01_OnboardingContext", "A01_LearningContextIntegration"]
      
      selected_problem: "P1"
      problem_priority: ["P1", "P3", "P2"]
      problem: "..."  # 최종 문제
      decision: "..."
      impact: "..."
      
      will_constraints: [...]
      intent_alignment: "..."
      
      difficulty_level: "..."  # Will + Weight Rules 계산 결과
      alignment_strategy: "..."
      content_range: "..."
      unit_plan: [...]
    }

    # Execution Layer (단순화)
    node "A01_FirstClassExecutionPlan" {
      metadata { ... }
      parent: "A01_FirstClassDecisionModel"
      
      will_execution: [...]
      intent_execution: [...]
      
      action: [...]
      measurement: [...]
      feedback: [...]
      adjustment: [...]
      
      # Insight / Learning / Reinforcement는 Meta-Engine에서 처리
    }
  }
}
```

---

### 11.12 개선사항 요약

#### ✅ 해결된 문제

1. ✅ **레이어 역할 중복** → SRP 원칙 적용, 책임 명확화
2. ✅ **Context Layer 과부하** → 순수 데이터만 저장
3. ✅ **Will Layer operational definition** → Formal Constraint 추가
4. ✅ **Interpretation/DIL 충돌** → 문제 후보군 vs 최종 문제 선택으로 분리
5. ✅ **Execution Layer 비대화** → 4개 필드만, 나머지는 Meta-Engine으로 분리
6. ✅ **Parent 다중 참조** → 단일 parent + usesContext 분리
7. ✅ **DSL → JSON-LD 매핑** → Metadata 블록 분리
8. ✅ **Reasoning 연결성** → Weight Rules 추가
9. ✅ **룰-온톨로지 결합도** → Mapping Layer 도입
10. ✅ **정량적 규칙 부족** → Will Priority + Weight Rules 추가

#### 📊 구조 개선 효과

- **엔트로피 감소**: 레이어 간 책임 명확화로 불확실성 감소
- **유지보수성 향상**: Mapping Layer로 결합도 감소
- **재사용성 향상**: Execution Layer 단순화로 다른 에이전트 적용 용이
- **일관성 보장**: Will Formal Constraint로 LLM 출력 변동성 감소
- **확장성 향상**: Meta-Engine 분리로 공통 기능 재사용 가능

---

## 12. 완전 확장형 온톨로지 3-Layer 아키텍처 (최종판)

### 12.1 Agent 내부 3-계층 구조

각 Agent는 내부적으로 **3개의 온톨로지 계층**을 가집니다:

```
① Agent Core Ontology      ← 모든 Task 공통 (변하지 않음)
② Task Core Ontology       ← 특정 Task 공통 (잘 안 변함)
③ Task Module Ontology     ← 세부 기능 단위 (자주 바뀌어도 안전)
```

### 12.2 구조 개념도

```
Agent01/
 ├── ontology/
 │     ├── agent_core/            ← Base Meta + Relations + Common Types
 │     │   ├── metadata_schema.jsonld
 │     │   ├── common_types.jsonld
 │     │   └── base_relations.jsonld
 │     ├── task_core/             ← Task-level abstractions
 │     │   ├── onboarding_task_core.jsonld
 │     │   └── exam_prep_task_core.jsonld
 │     └── modules/               ← 세부 기능 온톨로지(무한 확장)
 │           ├── onboarding/
 │           │   ├── personality_module.jsonld
 │           │   ├── confidence_module.jsonld
 │           │   └── stress_module.jsonld
 │           ├── first_class/
 │           │   ├── strategy_module.jsonld
 │           │   └── execution_module.jsonld
 │           └── exam_prep/
 │               └── schedule_module.jsonld
```

### 12.3 각 계층의 책임 정의

#### 12.3.1 Agent Core Ontology (에이전트 내부 공통 표준)

**역할**: 모든 Task가 공유하는 구조 통일

**포함 요소**:
- 메타데이터 스키마: `mk:hasStage`, `mk:hasIntent`, `mk:hasIdentity`, `mk:hasPurpose`, `mk:hasContext`
- 공통 관계: `mk:hasParent`, `mk:usesContext`, `mk:referencesDecision`
- 공통 타입: `mk:DifficultyLevel`, `mk:AlignmentStrategy`, `mk:ConfidenceLevel`
- 기본 제약 조건

**특징**:
- 절대 수정하지 않음
- 모든 Task와 Module의 기반
- 버전 관리 최소화

#### 12.3.2 Task Core Ontology (Task 내 공통 추상계층)

**역할**: Task 내부 모든 모듈이 공통으로 사용하는 추상적 구조 제공

**예시 - Onboarding Task Core**:
```json
{
  "@id": "mk-a01-task:OnboardingTaskCore",
  "@type": "owl:Class",
  "rdfs:label": "온보딩 Task 공통 구조",
  "mk-a01-task:baseClasses": [
    "mk-a01-task:ContextBase",
    "mk-a01-task:DiagnosticBase",
    "mk-a01-task:InterpretationBase",
    "mk-a01-task:StrategyBase",
    "mk-a01-task:ExecutionBase"
  ]
}
```

**예시 - Mastery Task Core**:
```json
{
  "@id": "mk-a04-task:MasteryTaskCore",
  "@type": "owl:Class",
  "rdfs:label": "마스터리 Task 공통 구조",
  "mk-a04-task:baseClasses": [
    "mk-a04-task:MasterySnapshotBase",
    "mk-a04-task:WeakPointBase",
    "mk-a04-task:ProgressEvaluationBase"
  ]
}
```

**특징**:
- Task별로 독립적
- 잘 안 변함
- Module들의 공통 인터페이스 역할

#### 12.3.3 Task Module Ontology (세부 기능 확장)

**역할**: Task의 세부 기능을 독립 스키마로 구성

**예시 - Onboarding Task Modules**:
```
onboarding/
 ├── personality_module.jsonld      ← 성격 분석 모듈
 ├── math_confidence_module.jsonld   ← 수학 자신감 모듈
 ├── textbook_profile_module.jsonld  ← 교재 프로필 모듈
 ├── stress_profile_module.jsonld    ← 스트레스 프로필 모듈
 └── study_style_module.jsonld       ← 학습 스타일 모듈
```

**예시 - Mastery Task Modules**:
```
mastery/
 ├── weakpoint_detector.jsonld       ← 약점 탐지 모듈
 ├── strength_map.jsonld             ← 강점 맵 모듈
 ├── gap_analyzer.jsonld             ← 간극 분석 모듈
 └── alignment_calculator.jsonld     ← 정렬 계산 모듈
```

**특징**:
- 완전 독립적
- 자주 바뀌어도 안전
- 무한 확장 가능

### 12.4 확장 시 깨지지 않는 구조

#### 12.4.1 확장 전략

**Step 1: Agent Core는 절대 수정하지 않는다**
- 모든 Task와 Module은 Core를 기반으로 움직임
- Core 변경은 전체 시스템에 영향

**Step 2: 새로운 Task가 생기면 Task Core를 만든다**
```
새 Task: ExamPrep Task
→ ExamPrepTaskCore 생성
  ├── ExamPrepContextBase
  ├── ExamPrepDiagnosticBase
  └── ExamPrepStrategyBase
```

**Step 3: 해당 Task 안에서 모듈 생성**
```
ExamPrep Task Modules:
├── exam_range_detection_module.jsonld
├── memorization_module.jsonld
└── weak_area_refresh_module.jsonld
```

**Step 4: 각 Module은 완전 독립적인 JSON-LD 스키마**
- Module 간 의존성 최소화
- 독립적 버전 관리

**Step 5: Gateway에서 Core Type만 바라보면 통신 안정성 확보**
- Gateway는 Agent Core와 Task Core만 참조
- Module 변경이 Gateway에 영향 없음

### 12.5 3-계층 구조의 견고성

#### 12.5.1 공통 논리와 Task 특화 논리 충돌 없음

**기존 2계층 구조의 문제**:
```
Agent Core + Task
→ Task 안에 여러 기능이 생기면 다시 섞임
→ 충돌 발생
```

**3계층 구조의 해결**:
```
Agent Core + Task Core + Task Modules
→ Task Core가 추상 계층으로 충돌 완전 차단
→ Module 간 독립성 보장
```

#### 12.5.2 Task 내부의 무한 확장 가능

**기존 구조의 문제**:
- Task 단일 스키마가 비대해짐
- 기능 추가 시 충돌 발생

**3계층 구조의 해결**:
- Module Ontology로 기능 단위 독립
- 새 Module 추가 시 기존 Module에 영향 없음

**예시**:
```
온보딩 Task 안에:
├── Personality Module (독립)
├── Confidence Module (독립)
├── Stress Module (독립)
└── Study Style Module (독립)
→ 각 Module이 완전히 독립적
```

#### 12.5.3 유지보수 비용 최소화

**변경 빈도**:
```
Agent Core → 변하지 않음 (안정)
Task Core → 잘 안 변함 (안정)
Module Ontology → 자주 바뀌어도 안전 (유연)
```

**변경 영향 범위**:
- Agent Core 변경: 전체 시스템 영향 (거의 없음)
- Task Core 변경: 해당 Task의 모든 Module 영향 (드묾)
- Module 변경: 해당 Module만 영향 (빈번하지만 안전)

### 12.6 하이브리드 아키텍처와의 통합

#### 12.6.1 전체 아키텍처 구조

```
┌─────────────────────────────────────────┐
│      공통 온톨로지 (Shared Ontology)     │
│  - Student (학생 기본 정보)              │
│  - CommonContext (공통 맥락)             │
│  - BaseTypes (기본 타입)                 │
└─────────────────────────────────────────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        ▼           ▼           ▼
┌──────────┐  ┌──────────┐  ┌──────────┐
│ Agent01  │  │ Agent03  │  │ Agent05  │
│          │  │          │  │          │
│ ┌──────┐ │  │ ┌──────┐ │  │ ┌──────┐ │
│ │Core  │ │  │ │Core  │ │  │ │Core  │ │
│ └──┬───┘ │  │ └──┬───┘ │  │ └──┬───┘ │
│    │     │  │    │     │  │    │     │
│ ┌──▼───┐ │  │ ┌──▼───┐ │  │ ┌──▼───┐ │
│ │Task │ │  │ │Task │ │  │ │Task │ │
│ │Core │ │  │ │Core │ │  │ │Core │ │
│ └──┬───┘ │  │ └──┬───┘ │  │ └──┬───┘ │
│    │     │  │    │     │  │    │     │
│ ┌──▼───┐ │  │ ┌──▼───┐ │  │ ┌──▼───┐ │
│ │Mod1  │ │  │ │Mod1  │ │  │ │Mod1  │ │
│ │Mod2  │ │  │ │Mod2  │ │  │ │Mod2  │ │
│ └──────┘ │  │ └──────┘ │  │ └──────┘ │
└──────────┘  └──────────┘  └──────────┘
```

#### 12.6.2 계층별 통신 규칙

**에이전트 간 통신 (Agent ↔ Agent)**:
- Agent Core 레벨에서만 통신
- Task Core와 Module은 내부 구현 세부사항

**Task 간 통신 (Task ↔ Task)**:
- Task Core 레벨에서 통신
- Module은 Task 내부에서만 사용

**Module 간 통신 (Module ↔ Module)**:
- 같은 Task 내부에서만 통신
- 다른 Task의 Module과 직접 통신 불가

### 12.7 Agent01 적용 예시

#### 12.7.1 Agent Core Ontology

**파일**: `agent01/ontology/agent_core/metadata_schema.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a01-core": "https://mathking.kr/ontology/agent01/core/"
  },
  "@graph": [
    {
      "@id": "mk-a01-core:hasStage",
      "@type": "owl:DatatypeProperty",
      "rdfs:domain": "owl:Thing",
      "rdfs:range": "xsd:string"
    },
    {
      "@id": "mk-a01-core:hasParent",
      "@type": "owl:ObjectProperty",
      "rdfs:domain": "owl:Thing",
      "rdfs:range": "owl:Thing"
    }
  ]
}
```

#### 12.7.2 Task Core Ontology

**파일**: `agent01/ontology/task_core/onboarding_task_core.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a01-core": "https://mathking.kr/ontology/agent01/core/",
    "mk-a01-task": "https://mathking.kr/ontology/agent01/task/"
  },
  "@graph": [
    {
      "@id": "mk-a01-task:OnboardingContextBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-core:ContextBase",
      "rdfs:label": "온보딩 Context 기본 구조"
    },
    {
      "@id": "mk-a01-task:OnboardingDecisionBase",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-core:DecisionBase",
      "rdfs:label": "온보딩 Decision 기본 구조"
    }
  ]
}
```

#### 12.7.3 Task Module Ontology

**파일**: `agent01/ontology/modules/onboarding/personality_module.jsonld`

```json
{
  "@context": {
    "mk": "https://mathking.kr/ontology/mathking/",
    "mk-a01-core": "https://mathking.kr/ontology/agent01/core/",
    "mk-a01-task": "https://mathking.kr/ontology/agent01/task/",
    "mk-a01-mod": "https://mathking.kr/ontology/agent01/modules/"
  },
  "@graph": [
    {
      "@id": "mk-a01-mod:PersonalityProfile",
      "@type": "owl:Class",
      "rdfs:subClassOf": "mk-a01-task:OnboardingContextBase",
      "rdfs:label": "성격 프로필 모듈",
      "mk-a01-mod:moduleType": "personality",
      "mk-a01-mod:extends": "mk-a01-task:OnboardingContextBase"
    }
  ]
}
```

### 12.8 확장 시나리오

#### 12.8.1 시나리오: 새 Module 추가

**상황**: 온보딩 Task에 "학습 환경 분석 Module" 추가

**과정**:
1. `agent01/ontology/modules/onboarding/learning_environment_module.jsonld` 생성
2. `mk-a01-mod:LearningEnvironmentProfile` 클래스 정의
3. `mk-a01-task:OnboardingContextBase` 확장
4. Agent Core와 Task Core는 수정 없음

**결과**: 기존 Module에 영향 없이 확장 완료

#### 12.8.2 시나리오: 새 Task 추가

**상황**: Agent01에 "시험 대비 Task" 추가

**과정**:
1. `agent01/ontology/task_core/exam_prep_task_core.jsonld` 생성
2. Task Core 클래스 정의 (Agent Core 확장)
3. `agent01/ontology/modules/exam_prep/` 폴더 생성
4. 필요한 Module들 추가

**결과**: 기존 Onboarding Task에 영향 없이 새 Task 추가

### 12.9 Gateway 통신 규칙

#### 12.9.1 Gateway가 참조하는 계층

**Gateway는 다음만 참조**:
- 공통 온톨로지 (Shared Ontology)
- Agent Core Ontology
- Task Core Ontology (선택적)

**Gateway가 참조하지 않는 것**:
- Task Module Ontology (내부 구현 세부사항)

**이유**:
- Module은 자주 변경되므로 Gateway가 참조하면 불안정
- Core만 참조하면 안정적인 통신 보장

#### 12.9.2 통신 프로토콜

**에이전트 간 요청**:
```json
{
  "request_type": "ontology_query",
  "source_agent": "agent03",
  "target_agent": "agent01",
  "query": {
    "operation": "get_task_core",
    "task_type": "onboarding",
    "core_class": "OnboardingContextBase",
    "student_id": "12345"
  }
}
```

**응답**:
```json
{
  "response_type": "ontology_response",
  "data": {
    "@id": "mk-a01-task:OnboardingContextBase/instance_001",
    "@type": "mk-a01-task:OnboardingContextBase",
    // Task Core 레벨의 데이터만 반환
    // Module 세부사항은 포함하지 않음
  }
}
```

### 12.10 3-계층 구조의 궁극적 장점

#### ✅ 무한 확장 가능
- 새 Module 추가해도 Agent Core와 Task Core는 그대로 유지
- 확장이 기존 구조에 영향 없음

#### ✅ 계층 간 데이터 충돌 없음
- 각 계층은 책임이 완전히 분리
- 충돌 가능성 제로

#### ✅ 규칙 자동 생성과 상호작용 설계 용이
- LLM이 생성하는 Task Module은 독립 JSON-LD로 바로 생성
- Module 간 상호작용 설계가 명확

#### ✅ API/Gateway 호환성 100%
- Gateway는 Core 영역만 보면 되므로 전체 구조가 안정적
- Module 변경이 Gateway에 영향 없음

#### ✅ 사람·LLM·시스템 모두 이해하기 쉬운 구조
- 직관적 계층화
- 각 계층의 역할이 명확

### 12.11 구현 체크리스트

#### Phase 1: Agent Core 구축
- [ ] Agent Core 스키마 정의
- [ ] 공통 메타데이터 정의
- [ ] 공통 타입 정의
- [ ] 공통 관계 정의

#### Phase 2: Task Core 구축
- [ ] Onboarding Task Core 정의
- [ ] 다른 Task Core 정의 (필요시)
- [ ] Task Core와 Agent Core 연결

#### Phase 3: Task Module 구축
- [ ] Onboarding Modules 정의
  - [ ] Personality Module
  - [ ] Confidence Module
  - [ ] Stress Module
  - [ ] Study Style Module
- [ ] Module과 Task Core 연결

#### Phase 4: Gateway 통합
- [ ] Gateway가 Agent Core만 참조하도록 설정
- [ ] Task Core 레벨 통신 프로토콜 정의
- [ ] Module은 내부 구현으로 처리

---

**문서 버전**: 2.3 (3-Layer 아키텍처 추가)  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**v2.2 주요 변경**: 10가지 구조적 문제 해결, 엔트로피 최소화, 실구현 가능한 구조로 개선  
**v2.3 주요 변경**: Agent 내부 3-계층 구조 도입, 무한 확장 가능한 견고한 아키텍처 확립

