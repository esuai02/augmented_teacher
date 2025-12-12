# AlphaTutor 온톨로지 Triple 생성 요약 문서

생성일: 2025-01-27
기준 문서: 
- `priciples_주어.md`: 주어 선택 기준
- `priciples_서술어.md`: 서술어 설계 기준
- 각 agent의 `rules/*.md`: 룰 파일들

---

## 📋 생성 개요

### Triple 생성 원칙

#### 주어 선택 기준 (우선순위 순)
1. **행동 주체성(agency)**: 대화의 주도자, 감정·의도 표현자
2. **의미 에너지 중심**: 상태 변화나 인지 변곡점의 핵심 개체
3. **시간 지속성**: 장기 맥락에서 재사용될 개체
4. **관계 생성력**: 많은 다른 노드와 연결될 가능성
5. **시스템 목적 적합성**: 학습·이해·정서 루프에 기여 여부

#### 서술어 계층
- **Cognitive**: `hasPart`, `requires`, `isPrerequisiteOf`, `extends`
- **Affective**: `causes`, `affects`, `correlatesWith`, `reduces`, `enhances`
- **Behavioral**: `leadsTo`, `supports`, `resultsIn`, `suggests`, `recommends`
- **Meta**: `isSubtypeOf`, `contradicts`, `coOccursWith`

---

## 📊 Agent별 Triple 통계

| Agent | 주요 도메인 | Triple 수 | 핵심 개념 |
|-------|-----------|----------|----------|
| Agent01 | 온보딩 | ~150 | Student, MathLevel, MathConfidence, ExamStyle, ParentStyle, Routine, TeachingMethod |
| Agent02 | 시험 일정 | ~30 | Exam, AcademyProgress, SchoolProgress, StudyPlan, AlignmentPlan |
| Agent03 | 목표 분석 | ~40 | Goal, QuarterlyGoal, WeeklyGoal, TodayGoal, Curriculum, Plan |
| Agent04 | 약점 분석 | ~60 | Persona, ConceptUnderstanding, TypeLearning, ProblemSolving, ErrorNote, QnA |
| Agent05 | 학습 감정 | ~30 | EmotionPattern, EmotionVector, PersonaIdentification, SignatureRoutine |
| Agent06 | 선생님 피드백 | ~25 | TeacherFeedback, TeacherIntention, TeacherPersona, InteractionContent |
| Agent07 | 상호작용 타게팅 | ~15 | Interaction, InteractionContext, InteractionTargeting |
| Agent08 | 침착도 | ~25 | Calmness, CalmnessRoutine, CalmnessPattern |
| Agent09 | 학습 관리 | ~35 | LearningManagement, AttendanceAnalysis, GoalAnalysis, PomodoroAnalysis |
| Agent10 | 개념 노트 | ~30 | ConceptNote, ConceptNoteAnalysis, ConceptLearningSteps |
| Agent11 | 문제 노트 | ~30 | ProblemNote, ProblemNoteAnalysis, ErrorPattern |
| Agent12 | 휴식 루틴 | ~30 | RestRoutine, RestButtonClickAnalysis, RestPatternAnalysis |
| Agent13 | 학습 이탈 | ~50 | LearningDropout, DropoutDetection, Intervention, RiskLevel |
| Agent14 | 현재 위치 | ~45 | CurrentPosition, ProgressAnalysis, RhythmAnalysis, EmotionalAnalysis |
| Agent15 | 문제 재정의 | ~40 | ProblemRedefinition, ComprehensiveAnalysis, RootCauseHypothesis |
| Agent16 | 상호작용 준비 | ~35 | InteractionPreparation, LearningUniverse, StorytellingTheme |
| Agent17 | 남은 활동 | ~50 | RemainingActivities, RhythmRecovery, ActivityAdjustmentStrategy |
| Agent18 | 시그너처 루틴 | ~40 | SignatureRoutine, Drilling, CorePsychologicalFactor |
| Agent19 | 상호작용 컨텐츠 | ~40 | InteractionContent, InteractionTemplate, PackagedTemplate |
| Agent20 | 개입 준비 | ~35 | InterventionPreparation, InterventionLocation, InterventionMethod |
| Agent21 | 개입 실행 | ~50 | InterventionExecution, StrategyExecution, PersonalInterventionList |
| Agent22 | 모듈 개선 | ~70 | ModuleImprovement, VulnerabilityAnalysis, SelfUpgradeIdea, ThreeFileSystemDocument |
| **총계** | - | **~950** | - |

---

## 🎯 핵심 Triple 카테고리

### 1. 학생 프로필 관련 (Agent01)

```
(Student, hasAttribute, MathLevel)
(Student, hasAttribute, MathConfidence)
(Student, hasAttribute, ExamStyle)
(Student, hasAttribute, ParentStyle)
(Student, hasAttribute, StudyHoursPerWeek)
(Student, hasAttribute, StudyStyle)
(Student, hasAttribute, MathLearningStyle)
(Student, attends, Academy)
(Student, hasGoal, LongTermGoal)
(Student, hasRoutine, Routine)
```

### 2. 학습 활동 관련 (Agent04)

```
(Student, performs, ConceptUnderstanding)
(Student, performs, TypeLearning)
(Student, performs, ProblemSolving)
(Student, creates, ErrorNote)
(Student, performs, QnA)
(Student, performs, ReviewActivity)
(Student, performs, Pomodoro)
(Student, performs, ReturnCheck)
```

### 3. 페르소나 및 감정 관련 (Agent04, Agent05)

```
(Student, hasPersona, Persona)
(Persona, affects, LearningActivity)
(Persona, affects, FeedbackMethod)
(Student, hasEmotion, EmotionPattern)
(EmotionPattern, affects, LearningActivity)
(EmotionPattern, leadsTo, PersonaIdentification)
```

### 4. 목표 및 계획 관련 (Agent03)

```
(Student, hasGoal, LongTermGoal)
(Student, hasGoal, QuarterlyGoal)
(Student, hasGoal, WeeklyGoal)
(Student, hasGoal, TodayGoal)
(Goal, hasPlan, Plan)
(Plan, requires, FeasibilityCheck)
(Plan, requires, ResilienceDesign)
```

### 5. 시험 및 준비 관련 (Agent02)

```
(Student, hasExam, Exam)
(Exam, requires, PreparationPeriod)
(Exam, affects, StudySchedule)
(Student, hasPlan, StudyPlan)
(StudyPlan, requires, AcademySchoolHomeAlignment)
```

### 6. 선생님 피드백 관련 (Agent06)

```
(Teacher, provides, TeacherFeedback)
(TeacherFeedback, requires, TeacherIntention)
(TeacherFeedback, requires, TeacherPersona)
(TeacherFeedback, generates, InteractionContent)
```

### 7. 상호작용 관련 (Agent07, Agent16, Agent19)

```
(Student, hasInteraction, Interaction)
(Interaction, requires, InteractionContext)
(Interaction, requires, InteractionPreparation)
(Interaction, hasContent, InteractionContent)
```

### 8. 루틴 관련 (Agent01, Agent12, Agent18)

```
(Student, hasRoutine, Routine)
(Student, hasRoutine, RestRoutine)
(Student, hasRoutine, SignatureRoutine)
(Routine, requires, MathLevel)
(Routine, requires, MathConfidence)
(SignatureRoutine, requires, Persona)
```

---

## 🔗 핵심 Triple 관계망

### 1. 학생 중심 관계망

```
Student
├── hasAttribute → MathLevel → affects → Routine
├── hasAttribute → MathConfidence → causes → LearningMotivation
├── hasAttribute → ExamStyle → affects → StudyPlan
├── hasPersona → Persona → affects → LearningActivity
├── hasEmotion → EmotionPattern → leadsTo → PersonaIdentification
├── hasGoal → Goal → hasPlan → Plan → leadsTo → Execution
├── performs → LearningActivity → affects → Persona
└── hasRoutine → SignatureRoutine → leadsTo → BehaviorChange
```

### 2. 학습 활동 중심 관계망

```
LearningActivity
├── ConceptUnderstanding → requires → TTS, WhiteboardWriting
├── TypeLearning → requires → TTSSystem, SimilarProblemSystem
├── ProblemSolving → hasPart → ProblemInterpretation, SolutionProcess
├── ErrorNote → leadsTo → BehaviorChange
├── QnA → leadsTo → MetacognitiveFeedback
├── ReviewActivity → leadsTo → SignatureRoutine
├── Pomodoro → leadsTo → Reflection
└── ReturnCheck → leadsTo → NextDayPreparation
```

### 3. 목표-계획-실행 관계망

```
LongTermGoal
└── isPrerequisiteOf → QuarterlyGoal
    └── isPrerequisiteOf → WeeklyGoal
        └── isPrerequisiteOf → TodayGoal
            └── hasPlan → Plan
                ├── requires → FeasibilityCheck
                ├── requires → ResilienceDesign
                └── leadsTo → Execution
```

### 4. 감정-페르소나-피드백 관계망

```
EmotionPattern
├── generates → EmotionVector
├── leadsTo → PersonaIdentification
├── leadsTo → FeedbackCommand
└── coOccursWith → Achievement
    └── leadsTo → SignatureRoutine
```

### 5. 선생님-피드백-상호작용 관계망

```
Teacher
└── provides → TeacherFeedback
    ├── requires → TeacherIntention
    ├── requires → TeacherPersona
    └── generates → InteractionContent
        └── affects → StudentResponse
```

---

## 🧩 주요 서술어 사용 빈도

### Cognitive 계층
- `requires`: 가장 많이 사용 (의존성 관계)
- `hasPart`: 구성 관계 표현
- `isPrerequisiteOf`: 선후관계 표현
- `extends`: 확장 관계 표현

### Affective 계층
- `affects`: 영향 관계 (가장 많이 사용)
- `causes`: 원인-결과 관계
- `correlatesWith`: 상관 관계
- `reduces`, `enhances`: 상태 변화

### Behavioral 계층
- `leadsTo`: 행동 결과 (가장 많이 사용)
- `supports`: 보조 관계
- `resultsIn`: 결과 유발
- `suggests`, `recommends`: 추천 관계

### Meta 계층
- `isSubtypeOf`: 계층 관계 (가장 많이 사용)
- `contradicts`: 충돌 관계
- `coOccursWith`: 동시 발생 관계

---

## 📝 Triple 검증 체크리스트

### 주어 검증
- [x] 행동 주체성 기준 적용
- [x] 의미 에너지 중심 기준 적용
- [x] 시간 지속성 기준 적용
- [x] 관계 생성력 기준 적용
- [x] 시스템 목적 적합성 기준 적용

### 서술어 검증
- [x] Cognitive 계층 적절히 사용
- [x] Affective 계층 적절히 사용
- [x] Behavioral 계층 적절히 사용
- [x] Meta 계층 적절히 사용
- [x] 방향성 명확성 확보
- [x] 의미 일관성 확보

### 관계망 검증
- [x] Cross-Agent 관계 명확화
- [x] 순환 참조 방지
- [x] 추론 가능성 확보
- [x] 도메인 적합성 확보

---

## 🚀 다음 단계

### 1. Triple 통합 및 검증
- [x] Agent01과 Agent02~Agent22의 triple 통합 ✅
- [ ] 중복 triple 제거
- [ ] 일관성 검증
- [ ] 완전성 검증

### 2. 온톨로지 파일 생성
- [ ] RDF/OWL 형식으로 변환
- [ ] 네임스페이스 정의
- [ ] 클래스 정의
- [ ] 속성 정의
- [ ] 관계 정의

### 3. 추론 규칙 정의
- [ ] SPARQL 쿼리 작성
- [ ] 추론 규칙 정의
- [ ] 일관성 검사 규칙 정의

### 4. 테스트 및 검증
- [ ] SPARQL 쿼리 테스트
- [ ] 추론 엔진 테스트
- [ ] 실제 데이터로 검증

---

## 📚 참고 문서

- `triples_all_agents.md`: Agent01~Agent22 통합 triple 문서 (✅ 통합 완료)
- `triples_agent01_onboarding.md`: Agent01 온보딩 triple 상세 (원본, 통합됨)
- `triples_agent02_to_agent21.md`: Agent02~Agent22 triple 상세 (원본, 통합됨)
- `priciples_주어.md`: 주어 선택 원칙
- `priciples_서술어.md`: 서술어 설계 원칙

---

## ✅ 완료 사항

1. ✅ Agent01 온보딩 triple 생성 (~150개)
2. ✅ Agent02~Agent22 triple 생성 (~800개)
3. ✅ Agent01~Agent22 통합 문서 생성 (`triples_all_agents.md`)
4. ✅ 핵심 관계망 정의
5. ✅ 서술어 계층 분류
6. ✅ Cross-Agent 관계 명확화

---

**생성 완료일**: 2025-01-27
**통합 완료일**: 2025-01-27
**총 Triple 수**: 약 950개
**상태**: 통합 완료, 검증 및 온톨로지 변환 준비

---

## 🎯 Agent22 추가 Triple 상세

### 모듈 개선 핵심 개념
- 실행 데이터 수집 및 검증
- 룰 취약점 분석 (논리 오류, 불완전 커버리지, 성능 이슈 등)
- 분석 내용 약점 분석 (정확도, 일관성, 해석 가능성 등)
- 자가 업그레이드 아이디어 생성
- 영향도-노력도 매트릭스 기반 우선순위화
- 3 File System 문서 생성 (문제 정의, 개선 설계, 실행 계획)
- AI 코드 업그레이드 프로세스 (최종 진화 단계)
- 개발자 검토 모드 (현재 단계)

