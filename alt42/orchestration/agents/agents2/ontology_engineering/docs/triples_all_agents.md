# Agent01~Agent22 통합 Triple 생성 문서

생성일: 2025-01-27
기준 문서: 
- `priciples_주어.md`: 주어 선택 기준
- `priciples_서술어.md`: 서술어 설계 기준
- 각 agent의 `rules/mission.md`, `rules/questions.md`: 룰 파일들

---

## 📋 Triple 생성 원칙

### 주어 선택 기준 (우선순위 순)
1. **행동 주체성(agency)**: 대화의 주도자, 감정·의도 표현자
2. **의미 에너지 중심**: 상태 변화나 인지 변곡점의 핵심 개체
3. **시간 지속성**: 장기 맥락에서 재사용될 개체
4. **관계 생성력**: 많은 다른 노드와 연결될 가능성
5. **시스템 목적 적합성**: 학습·이해·정서 루프에 기여 여부

### 서술어 계층
- **Cognitive**: `hasPart`, `requires`, `isPrerequisiteOf`, `extends`
- **Affective**: `causes`, `affects`, `correlatesWith`, `reduces`, `enhances`
- **Behavioral**: `leadsTo`, `supports`, `resultsIn`, `suggests`, `recommends`
- **Meta**: `isSubtypeOf`, `contradicts`, `coOccursWith`

---

## 🎯 Agent01_Onboarding Triples

### 1. 수학 학습 스타일 관련 Triples

```
(Student, hasAttribute, MathLearningStyle)
(MathLearningStyle, isSubtypeOf, LearningStyle)
(MathLearningStyle, hasValue, "계산형")
(MathLearningStyle, hasValue, "개념형")
(MathLearningStyle, hasValue, "응용형")
(MathLearningStyle, affects, TeachingMethod)
(MathLearningStyle, requires, MathProblemSolvingApproach)
```

### 2. 학원 정보 관련 Triples

```
(Student, attends, Academy)
(Academy, hasAttribute, AcademyName)
(Academy, hasAttribute, AcademyGrade)
(Academy, hasAttribute, AcademySchedule)
(Academy, affects, StudySchedule)
(Academy, coOccursWith, SchoolCurriculum)
(Academy, supports, MathLearningProgress)
```

### 3. 수학 성적/수준 관련 Triples

```
(Student, hasAttribute, MathLevel)
(MathLevel, isSubtypeOf, AcademicLevel)
(MathLevel, hasValue, "수학이 어려워요")
(MathLevel, hasValue, "중위권")
(MathLevel, hasValue, "상위권")
(MathLevel, hasValue, "상위권 이상")
(MathLevel, affects, RoutineDesign)
(MathLevel, causes, ConfidenceLevel)
(MathLevel, requires, AppropriateTeachingMethod)
```

### 4. 수학 자신감 관련 Triples

```
(Student, hasAttribute, MathConfidence)
(MathConfidence, isSubtypeOf, Confidence)
(MathConfidence, hasRange, "1-10")
(MathConfidence, affects, LearningMotivation)
(MathConfidence, causes, LowMotivation)
(MathConfidence, reduces, LearningAnxiety)
(MathConfidence, enhances, ProblemSolvingAttempt)
(LowMathConfidence, causes, LowMotivation)
(HighMathConfidence, suggests, ChallengeProblem)
```

### 5. 시험 스타일 관련 Triples

```
(Student, hasAttribute, ExamStyle)
(ExamStyle, isSubtypeOf, StudyPattern)
(ExamStyle, hasValue, "벼락치기")
(ExamStyle, hasValue, "꾸준한 준비")
(ExamStyle, hasValue, "전략적 집중")
(ExamStyle, affects, RoutineType)
(ExamStyle, leadsTo, StudySchedule)
(벼락치기, requires, ShortTermIntensiveRoutine)
(꾸준한준비, requires, LongTermSustainedRoutine)
```

### 6. 부모 스타일 관련 Triples

```
(Student, hasParent, Parent)
(Parent, hasAttribute, ParentStyle)
(ParentStyle, isSubtypeOf, InvolvementStyle)
(ParentStyle, hasValue, "적극 개입")
(ParentStyle, hasValue, "부분 지원")
(ParentStyle, hasValue, "자율 존중")
(ParentStyle, affects, FeedbackRoutine)
(ParentStyle, requires, CommunicationMode)
(적극개입, requires, FrequentFeedbackChannel)
(자율존중, requires, MinimalInterventionMode)
```

### 7. 학습 시간 관련 Triples

```
(Student, hasAttribute, StudyHoursPerWeek)
(StudyHoursPerWeek, isSubtypeOf, TimeResource)
(StudyHoursPerWeek, affects, RoutineFeasibility)
(StudyHoursPerWeek, requires, MinimumThreshold)
(StudyHoursPerWeek, lessThan, "10")
(StudyHoursPerWeek, suggests, TimeWarning)
(StudyHoursPerWeek, coOccursWith, LearningEfficiency)
```

### 8. 학습 스타일 관련 Triples

```
(Student, hasAttribute, StudyStyle)
(StudyStyle, isSubtypeOf, LearningPreference)
(StudyStyle, hasValue, "개념 정리 위주")
(StudyStyle, hasValue, "문제풀이 위주")
(StudyStyle, affects, TeachingApproach)
(StudyStyle, requires, ContentType)
(개념정리위주, requires, ConceptLectureFirst)
(문제풀이위주, requires, ProblemSolvingFirst)
```

### 9. 진도 관련 Triples

```
(Student, hasAttribute, ConceptProgress)
(Student, hasAttribute, AdvancedProgress)
(ConceptProgress, isSubtypeOf, LearningProgress)
(AdvancedProgress, isSubtypeOf, LearningProgress)
(ConceptProgress, affects, AdvancedProgress)
(ConceptProgress, requires, PrerequisiteUnit)
(AdvancedProgress, requires, ConceptProgress)
(ConceptProgress, coOccursWith, AdvancedProgress)
(ConceptProgress, contradicts, AdvancedProgress)
(ProgressGap, causes, FoundationInstability)
(ProgressGap, suggests, ConceptReinforcement)
```

### 10. 단원별 마스터링 관련 Triples

```
(Student, hasMastery, MathUnit)
(MathUnit, hasAttribute, UnitStatus)
(UnitStatus, hasValue, "완료")
(UnitStatus, hasValue, "진행중")
(UnitStatus, hasValue, "미완료")
(MathUnit, requires, PrerequisiteUnit)
(MathUnit, isPrerequisiteOf, NextUnit)
(MathUnit, affects, ConceptProgress)
(MathUnit, affects, AdvancedProgress)
```

### 11. 교재 정보 관련 Triples

```
(Student, uses, Textbook)
(Textbook, isSubtypeOf, LearningMaterial)
(Textbook, hasType, "학교교과서")
(Textbook, hasType, "학원교재")
(Textbook, hasType, "문제집")
(Textbook, supports, LearningProgress)
(Textbook, requires, AppropriateLevel)
(Textbook, coOccursWith, Curriculum)
```

### 12. 목표 관련 Triples

```
(Student, hasGoal, LongTermGoal)
(LongTermGoal, isSubtypeOf, Goal)
(LongTermGoal, hasValue, "경시대회 준비해 보기")
(LongTermGoal, hasValue, "심화 문제도 풀 수 있는 실력 쌓기")
(LongTermGoal, hasValue, "수학을 잘해서 원하는 학교 가기")
(LongTermGoal, requires, CurrentState)
(LongTermGoal, requires, FeasibilityCheck)
(LongTermGoal, affects, RoutineDesign)
(LongTermGoal, leadsTo, MilestonePlan)
(경시대회준비, requires, HighMathLevel)
(경시대회준비, requires, SufficientStudyHours)
```

### 13. 루틴 관련 Triples

```
(Student, hasRoutine, Routine)
(Routine, isSubtypeOf, LearningPlan)
(Routine, hasType, "개념이해중심")
(Routine, hasType, "문제풀이중심")
(Routine, hasType, "균형잡힌")
(Routine, hasType, "단기집중")
(Routine, hasType, "장기지속")
(Routine, requires, MathLevel)
(Routine, requires, MathConfidence)
(Routine, requires, ExamStyle)
(Routine, supports, LearningProgress)
(Routine, leadsTo, GoalAchievement)
```

### 14. 교수법 관련 Triples

```
(TeachingMethod, isSubtypeOf, EducationalApproach)
(TeachingMethod, hasType, "개념강의우선")
(TeachingMethod, hasType, "문제풀이우선")
(TeachingMethod, hasType, "균형접근")
(TeachingMethod, requires, MathLevel)
(TeachingMethod, requires, MathLearningStyle)
(TeachingMethod, requires, MathConfidence)
(TeachingMethod, supports, Student)
(개념강의우선, requires, LowMathLevel)
(개념강의우선, requires, LowMathConfidence)
(문제풀이우선, requires, HighMathLevel)
(문제풀이우선, requires, HighMathConfidence)
```

### 15. 피드백 루틴 관련 Triples

```
(Student, hasFeedbackRoutine, FeedbackRoutine)
(FeedbackRoutine, isSubtypeOf, CommunicationPlan)
(FeedbackRoutine, requires, ParentStyle)
(FeedbackRoutine, requires, MathLevel)
(FeedbackRoutine, affects, ParentStudentRelationship)
(FeedbackRoutine, supports, LearningProgress)
(FrequentFeedbackRoutine, requires, 적극개입)
(FrequentFeedbackRoutine, requires, LowMathLevel)
(MinimalFeedbackRoutine, requires, 자율존중)
(MinimalFeedbackRoutine, requires, HighMathLevel)
```

### 16. 복합 상황 관련 Triples

```
(StrugglingStudent, isSubtypeOf, Student)
(StrugglingStudent, hasAttribute, LowMathLevel)
(StrugglingStudent, hasAttribute, LowMathConfidence)
(StrugglingStudent, requires, ComprehensiveSupportRoutine)
(StrugglingStudent, requires, ConceptLectureFirst)
(StrugglingStudent, requires, EmotionalSupport)
(StrugglingStudent, requires, ShortCycleFeedback)

(HighPotentialStudent, isSubtypeOf, Student)
(HighPotentialStudent, hasAttribute, HighMathLevel)
(HighPotentialStudent, hasAttribute, LowStudyHours)
(HighPotentialStudent, requires, EfficientLearningRoutine)
(HighPotentialStudent, requires, ConcentrationEnhancement)

(CrammingStudent, isSubtypeOf, Student)
(CrammingStudent, hasAttribute, 벼락치기)
(CrammingStudent, hasAttribute, WeakFoundation)
(CrammingStudent, requires, ShortTermIntensiveRoutine)
(CrammingStudent, requires, FoundationBuilding)
```

### 17. 규칙 우선순위 관련 Triples

```
(Rule, hasPriority, Priority)
(Priority, hasRange, "75-99")
(Priority, affects, RuleExecutionOrder)
(HighPriorityRule, requires, CriticalCondition)
(HighPriorityRule, affects, ImmediateAction)
```

### 18. 신뢰도 관련 Triples

```
(Rule, hasConfidence, Confidence)
(Confidence, hasRange, "0.0-1.0")
(Confidence, affects, DecisionReliability)
(HighConfidence, requires, StrongEvidence)
(LowConfidence, requires, AdditionalValidation)
```

### 19. 액션 관련 Triples

```
(Rule, leadsTo, Action)
(Action, isSubtypeOf, SystemBehavior)
(Action, hasType, "initialize_support_mode")
(Action, hasType, "recommend_path")
(Action, hasType, "display_message")
(Action, hasType, "analyze")
(Action, hasType, "generate_description")
(Action, hasType, "alert")
(Action, resultsIn, SystemResponse)
```

### 20. 온보딩 프로필 관련 Triples

```
(Student, hasProfile, OnboardingProfile)
(OnboardingProfile, isSubtypeOf, StudentProfile)
(OnboardingProfile, requires, MathLearningStyle)
(OnboardingProfile, requires, AcademyInfo)
(OnboardingProfile, requires, MathPerformance)
(OnboardingProfile, requires, TextbookInfo)
(OnboardingProfile, requires, MathUnitMastery)
(OnboardingProfile, supports, CustomizedLearningPlan)
(OnboardingProfile, leadsTo, InitialClassPreparation)
```

---

## 🎯 Agent02_ExamSchedule Triples

### 1. 시험 일정 및 준비 관련 Triples

```
(Student, hasExam, Exam)
(Exam, hasAttribute, ExamDate)
(Exam, hasAttribute, ExamType)
(ExamType, hasValue, "학교시험")
(ExamType, hasValue, "학원모의고사")
(Exam, requires, PreparationPeriod)
(PreparationPeriod, hasDuration, "8주")
(Exam, affects, StudySchedule)
(Exam, affects, RoutineDesign)
```

### 2. 학원-학교-집 학습 연계 관련 Triples

```
(Student, attends, Academy)
(Student, attends, School)
(Student, studiesAt, Home)
(Academy, hasProgress, AcademyProgress)
(School, hasProgress, SchoolProgress)
(Home, hasProgress, HomeProgress)
(AcademyProgress, affects, SchoolProgress)
(AcademyProgress, affects, HomeProgress)
(SchoolProgress, affects, HomeProgress)
(AlignmentPlan, requires, AcademyProgress)
(AlignmentPlan, requires, SchoolProgress)
(AlignmentPlan, requires, HomeProgress)
```

### 3. 학원 교재 및 과제 관련 Triples

```
(Academy, uses, AcademyTextbook)
(AcademyTextbook, isSubtypeOf, Textbook)
(AcademyTextbook, hasType, "쎈")
(AcademyTextbook, hasType, "개념원리")
(AcademyTextbook, hasType, "RPM")
(AcademyTextbook, hasType, "블랙라벨")
(AcademyTextbook, hasType, "일품")
(Student, hasAssignment, AcademyAssignment)
(AcademyAssignment, requires, AcademyTextbook)
(AcademyAssignment, affects, StudyTime)
(AcademyAssignment, affects, CompletionRate)
```

### 4. 학습 단계 및 전략 관련 Triples

```
(StudyStrategy, hasStep, ConceptStudy)
(StudyStrategy, hasStep, TypePractice)
(StudyStrategy, hasStep, AdvancedLearning)
(StudyStrategy, hasStep, PastExamSolving)
(ConceptStudy, isPrerequisiteOf, TypePractice)
(TypePractice, isPrerequisiteOf, AdvancedLearning)
(AdvancedLearning, isPrerequisiteOf, PastExamSolving)
(ConceptStudy, requires, Textbook)
(ConceptStudy, requires, ConceptExplanation)
(TypePractice, requires, AcademyTextbook)
(AdvancedLearning, requires, AdvancedTextbook)
```

### 5. 시험 대비 계획 관련 Triples

```
(ExamPreparation, requires, TimeBudget)
(ExamPreparation, requires, ContentTimeEstimate)
(ExamPreparation, requires, PrioritySetting)
(ExamPreparation, leadsTo, StudyPlan)
(StudyPlan, requires, AcademySchoolHomeAlignment)
(StudyPlan, requires, AssignmentPriority)
(StudyPlan, requires, MockExamSchedule)
```

---

## 🎯 Agent03_GoalsAnalysis Triples

### 1. 목표 계층 구조 관련 Triples

```
(Student, hasGoal, LongTermGoal)
(Student, hasGoal, QuarterlyGoal)
(Student, hasGoal, WeeklyGoal)
(Student, hasGoal, TodayGoal)
(LongTermGoal, isPrerequisiteOf, QuarterlyGoal)
(QuarterlyGoal, isPrerequisiteOf, WeeklyGoal)
(WeeklyGoal, isPrerequisiteOf, TodayGoal)
(LongTermGoal, requires, CurriculumAlignment)
(QuarterlyGoal, requires, ExamSchedule)
(WeeklyGoal, requires, WeeklySchedule)
(TodayGoal, requires, DailySchedule)
```

### 2. 목표-계획 연계 관련 Triples

```
(Goal, hasPlan, Plan)
(Plan, requires, FeasibilityCheck)
(Plan, requires, ResilienceDesign)
(Plan, affects, ExecutionRate)
(GoalPlanMismatch, causes, ExecutionFailure)
(GoalPlanMismatch, suggests, PlanRevision)
(GoalPlanMismatch, suggests, GoalAdjustment)
```

### 3. 시간 부족 딜레마 관련 Triples

```
(TimeConstraint, causes, GoalOverload)
(TimeConstraint, requires, PriorityAdjustment)
(TimeConstraint, requires, FlexibleAlternative)
(GoalOverload, affects, LearningMotivation)
(GoalOverload, suggests, ActivityReduction)
(GoalOverload, suggests, ActivityPostponement)
```

### 4. 회복탄력성 관련 Triples

```
(Plan, hasAttribute, Resilience)
(Resilience, affects, RecoverySpeed)
(Resilience, affects, RoutineContinuity)
(ScheduleDisruption, requires, RecoveryStrategy)
(RecoveryStrategy, requires, BufferTime)
(RecoveryStrategy, requires, AlternativePlan)
```

### 5. 커리큘럼 정합성 관련 Triples

```
(Student, follows, Curriculum)
(Curriculum, isSubtypeOf, LongTermPlan)
(Curriculum, requires, GoalAlignment)
(Curriculum, affects, QuarterlyGoal)
(Curriculum, affects, WeeklyGoal)
(CurriculumMismatch, causes, GoalDeviation)
(CurriculumMismatch, suggests, CurriculumAdjustment)
(CurriculumMismatch, suggests, GoalRevision)
```

### 6. 수업준비 및 포모도르 관련 Triples

```
(TodayGoal, requires, ClassPreparation)
(ClassPreparation, isSubtypeOf, WarmUpActivity)
(ClassPreparation, supports, DailyActivity)
(ClassPreparation, requires, Pomodoro)
(Pomodoro, supports, FocusMaintenance)
(Pomodoro, affects, LearningRhythm)
```

### 7. 귀가검사 관련 Triples

```
(DailyActivity, endsWith, ReturnCheck)
(ReturnCheck, isSubtypeOf, DailyReview)
(ReturnCheck, requires, ActivityAnalysis)
(ReturnCheck, requires, RoutineResistanceAnalysis)
(ReturnCheck, leadsTo, NextDayPreparation)
(RoutineResistance, affects, RoutineAdjustment)
```

---

## 🎯 Agent04_InspectWeakpoints Triples

### 1. 페르소나 관련 Triples

```
(Student, hasPersona, Persona)
(Persona, isSubtypeOf, BehaviorPattern)
(Persona, affects, LearningActivity)
(Persona, affects, FeedbackMethod)
(Persona, requires, PersonaMatching)
(PersonaMatching, requires, BehaviorLog)
(PersonaMatching, requires, EmotionData)
(PersonaMatching, requires, SurveyResponse)
```

### 2. 개념이해 활동 관련 Triples

```
(Student, performs, ConceptUnderstanding)
(ConceptUnderstanding, isSubtypeOf, LearningActivity)
(ConceptUnderstanding, hasPart, ConceptSummary)
(ConceptUnderstanding, hasPart, ConceptExplanation)
(ConceptUnderstanding, hasPart, ConceptCheck)
(ConceptUnderstanding, hasPart, ExampleQuiz)
(ConceptUnderstanding, hasPart, RepresentativeType)
(ConceptUnderstanding, hasPart, TopicTest)
(ConceptUnderstanding, hasPart, UnitTest)
(ConceptUnderstanding, requires, TTS)
(ConceptUnderstanding, requires, WhiteboardWriting)
(ConceptUnderstanding, requires, QnA)
(ConceptUnderstanding, affects, Persona)
```

### 3. 유형학습 활동 관련 Triples

```
(Student, performs, TypeLearning)
(TypeLearning, isSubtypeOf, LearningActivity)
(TypeLearning, hasPart, IntermediateType)
(TypeLearning, hasPart, AdvancedType)
(TypeLearning, hasPart, DescriptiveAssessment)
(TypeLearning, requires, TTSSystem)
(TypeLearning, requires, SimilarProblemSystem)
(TypeLearning, requires, HintSystem)
(TypeLearning, affects, Persona)
```

### 4. 문제풀이 활동 관련 Triples

```
(Student, performs, ProblemSolving)
(ProblemSolving, isSubtypeOf, LearningActivity)
(ProblemSolving, hasPart, ProblemInterpretation)
(ProblemSolving, hasPart, SolutionStart)
(ProblemSolving, hasPart, SolutionProcess)
(ProblemSolving, hasPart, SolutionCompletion)
(ProblemSolving, hasPart, Review)
(ProblemSolving, hasAttribute, Persona)
(ProblemSolving, affects, Persona)
```

### 5. 오답노트 활동 관련 Triples

```
(Student, creates, ErrorNote)
(ErrorNote, isSubtypeOf, LearningActivity)
(ErrorNote, hasPart, SolutionNote)
(ErrorNote, hasPart, EvaluationPreparation)
(ErrorNote, hasPart, DescriptiveAssessment)
(ErrorNote, requires, TTSCoaching)
(ErrorNote, requires, CognitiveApprenticeship)
(ErrorNote, affects, Persona)
(ErrorNote, leadsTo, BehaviorChange)
```

### 6. 질의응답 활동 관련 Triples

```
(Student, performs, QnA)
(QnA, isSubtypeOf, LearningActivity)
(QnA, hasPart, QuestionGeneration)
(QnA, hasPart, QuestionDecision)
(QnA, hasPart, AnswerReception)
(QnA, requires, ConceptNoteAnalysis)
(QnA, requires, ProblemNoteAnalysis)
(QnA, affects, Persona)
(QnA, leadsTo, MetacognitiveFeedback)
```

### 7. 복습활동 관련 Triples

```
(Student, performs, ReviewActivity)
(ReviewActivity, isSubtypeOf, LearningActivity)
(ReviewActivity, requires, Timing)
(ReviewActivity, requires, Volume)
(ReviewActivity, requires, ContentSuitability)
(ReviewActivity, affects, Persona)
(ReviewActivity, leadsTo, SignatureRoutine)
```

### 8. 포모도르 활동 관련 Triples

```
(Student, performs, Pomodoro)
(Pomodoro, isSubtypeOf, LearningActivity)
(Pomodoro, requires, FocusTime)
(Pomodoro, requires, BreakTime)
(Pomodoro, affects, Persona)
(Pomodoro, leadsTo, Reflection)
(Pomodoro, leadsTo, EmotionExpression)
```

### 9. 귀가검사 활동 관련 Triples

```
(Student, performs, ReturnCheck)
(ReturnCheck, isSubtypeOf, LearningActivity)
(ReturnCheck, requires, DailyActivityAnalysis)
(ReturnCheck, requires, PomodoroDataAnalysis)
(ReturnCheck, requires, PersonalRoutineAnalysis)
(ReturnCheck, affects, NextDayRoutine)
(ReturnCheck, leadsTo, RoutineAdjustment)
```

### 10. 시그너처 루틴 관련 Triples

```
(Student, hasRoutine, SignatureRoutine)
(SignatureRoutine, isSubtypeOf, Routine)
(SignatureRoutine, requires, Persona)
(SignatureRoutine, requires, Immersion)
(SignatureRoutine, requires, BehaviorGuidance)
(SignatureRoutine, affects, LearningEfficiency)
(SignatureRoutine, leadsTo, BehaviorChange)
```

---

## 🎯 Agent05_LearningEmotion Triples

### 1. 감정 패턴 관련 Triples

```
(Student, hasEmotion, EmotionPattern)
(EmotionPattern, isSubtypeOf, EmotionalState)
(EmotionPattern, affects, LearningActivity)
(EmotionPattern, affects, Persona)
(EmotionPattern, requires, EmotionMapping)
(EmotionMapping, requires, BehaviorLog)
(EmotionMapping, requires, EmotionSurvey)
(EmotionMapping, requires, ReactionData)
```

### 2. 감정 설문 관련 Triples

```
(Student, respondsTo, EmotionSurvey)
(EmotionSurvey, isSubtypeOf, Survey)
(EmotionSurvey, requires, ActivityContext)
(EmotionSurvey, leadsTo, PersonaIdentification)
(EmotionSurvey, leadsTo, EmotionVector)
```

### 3. 감정 벡터 및 페르소나 매칭 관련 Triples

```
(EmotionPattern, generates, EmotionVector)
(EmotionVector, isSubtypeOf, PersonaVector)
(EmotionVector, requires, ResponsePattern)
(EmotionVector, leadsTo, PersonaIdentification)
(PersonaIdentification, affects, FeedbackMethod)
(PersonaIdentification, affects, InteractionContent)
```

### 4. 활동별 감정 패턴 관련 Triples

```
(ConceptUnderstanding, hasEmotion, ConceptEmotion)
(TypeLearning, hasEmotion, TypeEmotion)
(ProblemSolving, hasEmotion, ProblemEmotion)
(ErrorNote, hasEmotion, ErrorEmotion)
(QnA, hasEmotion, QnAEmotion)
(ReviewActivity, hasEmotion, ReviewEmotion)
(Pomodoro, hasEmotion, PomodoroEmotion)
(ReturnCheck, hasEmotion, ReturnEmotion)
```

### 5. 감정 기반 피드백 연동 관련 Triples

```
(EmotionPattern, leadsTo, FeedbackCommand)
(FeedbackCommand, isSubtypeOf, AgentCommand)
(FeedbackCommand, requires, TargetAgent)
(FeedbackCommand, requires, FeedbackType)
(HighTension, leadsTo, RestRoutineCommand)
(AnxietyAvoidance, leadsTo, MetacognitiveFeedback)
```

### 6. 시그너처 루틴 도출 관련 Triples

```
(EmotionPattern, leadsTo, Achievement)
(Achievement, coOccursWith, EmotionPattern)
(RepeatedPattern, leadsTo, SignatureRoutine)
(SignatureRoutine, requires, EmotionPattern)
(SignatureRoutine, requires, BehaviorPattern)
(SignatureRoutine, requires, Reinforcement)
```

---

## 🎯 Agent06_TeacherFeedback Triples

### 1. 선생님 피드백 관련 Triples

```
(Teacher, provides, TeacherFeedback)
(TeacherFeedback, isSubtypeOf, Feedback)
(TeacherFeedback, requires, TeacherIntention)
(TeacherFeedback, requires, TeacherPersona)
(TeacherFeedback, requires, StudentContext)
(TeacherFeedback, affects, StudentBehavior)
(TeacherFeedback, leadsTo, LongTermImprovement)
```

### 2. 선생님 의도 추출 관련 Triples

```
(Teacher, hasIntention, TeacherIntention)
(TeacherIntention, isSubtypeOf, Intention)
(TeacherIntention, requires, TeacherMemo)
(TeacherIntention, requires, ConversationTranscript)
(TeacherIntention, requires, ReturnCheckData)
(TeacherIntention, requires, DecisionInfo)
(TeacherIntention, requires, ExpressionInfo)
(TeacherIntention, affects, FeedbackContent)
```

### 3. 선생님 페르소나 관련 Triples

```
(Teacher, hasPersona, TeacherPersona)
(TeacherPersona, isSubtypeOf, Persona)
(TeacherPersona, requires, Personality)
(TeacherPersona, requires, Preference)
(TeacherPersona, affects, InteractionStyle)
(TeacherPersona, affects, FeedbackTone)
```

### 4. 전문 지식 관련 Triples

```
(Teacher, hasKnowledge, ProfessionalKnowledge)
(ProfessionalKnowledge, isSubtypeOf, Knowledge)
(ProfessionalKnowledge, hasType, "시험대비")
(ProfessionalKnowledge, hasType, "입시준비")
(ProfessionalKnowledge, requires, LatestInformation)
(ProfessionalKnowledge, affects, FeedbackQuality)
```

### 5. 상호작용 컨텐츠 생성 관련 Triples

```
(TeacherFeedback, generates, InteractionContent)
(InteractionContent, isSubtypeOf, Content)
(InteractionContent, requires, Context)
(InteractionContent, requires, TeacherPersona)
(InteractionContent, requires, StudentPersona)
(InteractionContent, affects, StudentInteraction)
```

### 6. 피드백 검토 및 일관성 유지 관련 Triples

```
(TeacherFeedback, requires, Review)
(Review, isSubtypeOf, QualityCheck)
(Review, requires, ConsistencyCheck)
(Review, affects, TeacherPersona)
(Review, leadsTo, PersonaAdjustment)
```

---

## 🎯 Agent07_InteractionTargeting Triples

### 1. 상호작용 상황 관련 Triples

```
(Student, hasInteraction, Interaction)
(Interaction, requires, InteractionContext)
(InteractionContext, hasType, "분기목표")
(InteractionContext, hasType, "주간목표")
(InteractionContext, hasType, "오늘목표")
(InteractionContext, hasType, "수업준비")
(InteractionContext, hasType, "포모도르")
(InteractionContext, hasType, "실시간고민")
(InteractionContext, hasType, "귀가검사준비")
(InteractionContext, hasType, "귀가검사")
(InteractionContext, hasType, "커리큘럼설계")
```

### 2. 타게팅 의사결정 관련 Triples

```
(InteractionTargeting, requires, RuleEvaluation)
(RuleEvaluation, isSubtypeOf, DecisionMaking)
(RuleEvaluation, requires, AgentInformation)
(RuleEvaluation, requires, StudentContext)
(RuleEvaluation, leadsTo, TargetSelection)
(TargetSelection, affects, InteractionDelivery)
```

### 3. 최적 타겟 선정 관련 Triples

```
(InteractionTargeting, selects, OptimalTarget)
(OptimalTarget, isSubtypeOf, Target)
(OptimalTarget, requires, ContextAnalysis)
(OptimalTarget, requires, TimingAnalysis)
(OptimalTarget, requires, StudentState)
(OptimalTarget, affects, InteractionEffectiveness)
```

---

## 🎯 Agent08_Calmness Triples

### 1. 침착도 관련 Triples

```
(Student, hasAttribute, Calmness)
(Calmness, isSubtypeOf, EmotionalState)
(Calmness, hasRange, "0-100")
(Calmness, affects, LearningFocus)
(Calmness, affects, ProblemSolving)
(Calmness, affects, AnswerSelection)
(Calmness, requires, CalmnessRoutine)
(CalmnessRoutine, supports, LearningActivity)
(HighCalmness, suggests, AdvancedContent)
(HighCalmness, suggests, ChallengingProblem)
(MediumCalmness, suggests, StandardLearning)
(MediumCalmness, suggests, LightReview)
(LowCalmness, requires, Rest)
(LowCalmness, requires, BreathingExercise)
(LowCalmness, requires, Stretching)
(CriticalCalmness, requires, EmergencyRecovery)
(CriticalCalmness, requires, WaterIntake)
```

### 2. 침착도 측정 관련 Triples

```
(Calmness, measuredBy, AnswerAccuracy)
(AnswerAccuracy, isSubtypeOf, Measurement)
(AnswerAccuracy, calculatedFrom, CorrectAnswerRatio)
(CorrectAnswerRatio, requires, ProblemSolving)
(CorrectAnswerRatio, requires, AnswerSelection)
(Calmness, comparedWith, DailyCalmness)
(Calmness, comparedWith, MonthlyCalmness)
(CalmnessComparison, affects, ConditionAssessment)
```

### 3. 침착도 패턴 분석 관련 Triples

```
(Calmness, hasPattern, CalmnessPattern)
(CalmnessPattern, isSubtypeOf, Pattern)
(CalmnessPattern, hasType, "BaselineComparison")
(BaselineComparison, requires, BaselineCalmness)
(BaselineComparison, requires, CurrentCalmness)
(HighEfficiencyState, requires, CalmnessIncrease)
(HighEfficiencyState, suggests, AdvancedContent)
(FatigueAccumulation, requires, CalmnessDecrease)
(FatigueAccumulation, requires, RestRoutine)
```

### 4. 침착도 피드백 관련 Triples

```
(Calmness, leadsTo, CalmnessFeedback)
(CalmnessFeedback, isSubtypeOf, Feedback)
(CalmnessFeedback, requires, WhiteboardData)
(CalmnessFeedback, requires, ProblemAnalysis)
(CalmnessFeedback, affects, LearningPsychology)
(CalmnessFeedback, leadsTo, BehaviorChange)
```

---

## 🎯 Agent09_LearningManagement Triples

### 1. 학습 관리 관련 Triples

```
(Student, hasManagement, LearningManagement)
(LearningManagement, isSubtypeOf, Management)
(LearningManagement, requires, AttendanceAnalysis)
(LearningManagement, requires, GoalAnalysis)
(LearningManagement, requires, PomodoroAnalysis)
(LearningManagement, requires, ErrorNoteAnalysis)
(LearningManagement, requires, ExamPatternAnalysis)
(LearningManagement, affects, LearningEfficiency)
(LearningManagement, leadsTo, ManagementStrategy)
```

### 2. 출결 분석 관련 Triples

```
(AttendanceAnalysis, isSubtypeOf, Analysis)
(AttendanceAnalysis, requires, DailyPattern)
(AttendanceAnalysis, requires, WeeklyPattern)
(AttendanceAnalysis, requires, LatenessData)
(AttendanceAnalysis, requires, AbsenceData)
(AttendanceAnalysis, requires, TimeWeight)
(AttendanceAnalysis, affects, RoutineDesign)
```

### 3. 목표 분석 관련 Triples

```
(GoalAnalysis, isSubtypeOf, Analysis)
(GoalAnalysis, requires, AchievementRate)
(GoalAnalysis, requires, AverageDuration)
(GoalAnalysis, requires, CategoryBalance)
(GoalAnalysis, affects, GoalAdjustment)
```

### 4. 포모도르 분석 관련 Triples

```
(PomodoroAnalysis, isSubtypeOf, Analysis)
(PomodoroAnalysis, requires, CompletionRate)
(PomodoroAnalysis, requires, TotalStudyTime)
(PomodoroAnalysis, requires, FocusTimeSlot)
(PomodoroAnalysis, affects, RoutineOptimization)
```

### 5. 오답노트 분석 관련 Triples

```
(ErrorNoteAnalysis, isSubtypeOf, Analysis)
(ErrorNoteAnalysis, requires, ErrorTypeRatio)
(ErrorNoteAnalysis, requires, ReviewCycle)
(ErrorNoteAnalysis, requires, MasterySpeed)
(ErrorNoteAnalysis, affects, ReviewStrategy)
```

### 6. 시험 패턴 분석 관련 Triples

```
(ExamPatternAnalysis, isSubtypeOf, Analysis)
(ExamPatternAnalysis, requires, AverageScore)
(ExamPatternAnalysis, requires, HighestScore)
(ExamPatternAnalysis, requires, LowestScore)
(ExamPatternAnalysis, requires, DifficultyTimeManagement)
(ExamPatternAnalysis, requires, SubjectDeviation)
(ExamPatternAnalysis, affects, ExamPreparationStrategy)
```

### 7. 학습 관리 휴리스틱 관련 Triples

```
(AttendanceDecrease, coOccursWith, PomodoroIncomplete)
(AttendanceDecrease, coOccursWith, PomodoroIncomplete)
(AttendanceDecrease, suggests, RoutineRedesign)
(LowGoalAchievement, coOccursWith, LongDuration)
(LowGoalAchievement, suggests, GoalSegmentation)
(ConceptMisunderstanding, requires, ConceptRelearning)
(ConceptMisunderstanding, requires, VisualizationResource)
(LowTestScore, requires, ReviewPlan)
```

---

## 🎯 Agent10_ConceptNotes Triples

### 1. 개념 노트 관련 Triples

```
(Student, creates, ConceptNote)
(ConceptNote, isSubtypeOf, Note)
(ConceptNote, requires, ConceptUnderstanding)
(ConceptNote, requires, ConceptAnalysis)
(ConceptNote, affects, ConceptMastery)
(ConceptNote, supports, ReviewActivity)
(ConceptNote, hasAttribute, TotalStrokes)
(ConceptNote, hasAttribute, LastStrokeTime)
(ConceptNote, hasAttribute, CreatedTime)
(ConceptNote, hasAttribute, UsedTime)
(ConceptNote, hasAttribute, ContentTitle)
```

### 2. 개념 노트 분석 관련 Triples

```
(ConceptNote, analyzedBy, ConceptNoteAnalysis)
(ConceptNoteAnalysis, isSubtypeOf, Analysis)
(ConceptNoteAnalysis, requires, WritingAmount)
(ConceptNoteAnalysis, requires, PageStayTime)
(ConceptNoteAnalysis, requires, RevisitPattern)
(ConceptNoteAnalysis, requires, TTSUsage)
(ConceptNoteAnalysis, requires, StepwiseWritingData)
(ConceptNoteAnalysis, leadsTo, FeedbackReport)
```

### 3. 개념 노트 해석 관련 Triples

```
(TotalStrokes, indicates, ActivityIntensity)
(TotalStrokes, indicates, ImmersionLevel)
(LastStrokeTime, indicates, SessionEndProximity)
(LastStrokeTime, indicates, Recency)
(CreatedTime, indicates, Timeline)
(UsedTime, indicates, TotalTimeSpent)
(ContentTitle, indicates, UnitContext)
(ContentTitle, indicates, TopicContext)
```

### 4. 개념 노트 휴리스틱 관련 Triples

```
(HighWritingAmount, coOccursWith, Concentration)
(HighWritingAmount, coOccursWith, Difficulty)
(RecentLastStroke, coOccursWith, LowWritingAmount)
(RecentLastStroke, suggests, LightExploration)
(RecentLastStroke, suggests, Reinforcement)
(OldNote, coOccursWith, MediumWritingAmount)
(OldNote, suggests, RecallReview)
```

### 5. 개념 오답 분석 관련 Triples

```
(ConceptTestError, requires, ErrorCauseAnalysis)
(ErrorCauseAnalysis, requires, PreviousStepWritingData)
(ErrorCauseAnalysis, requires, ActivityLog)
(ErrorCauseAnalysis, leadsTo, FeedbackReport)
(FeedbackReport, affects, OtherAgents)
```

### 6. 개념 학습 단계 최적화 관련 Triples

```
(ConceptNote, optimizedFor, ConceptLearningSteps)
(ConceptLearningSteps, hasPart, ConceptSummary)
(ConceptLearningSteps, hasPart, ConceptUnderstanding)
(ConceptLearningSteps, hasPart, ConceptCheck)
(ConceptLearningSteps, hasPart, ExampleQuiz)
(ConceptLearningSteps, hasPart, RepresentativeType)
(ConceptLearningSteps, hasPart, TopicTest)
(ConceptLearningSteps, optimizedFor, PersonalRoutine)
```

---

## 🎯 Agent11_ProblemNotes Triples

### 1. 문제 노트 관련 Triples

```
(Student, creates, ProblemNote)
(ProblemNote, isSubtypeOf, Note)
(ProblemNote, requires, ProblemSolving)
(ProblemNote, requires, ProblemAnalysis)
(ProblemNote, affects, ProblemMastery)
(ProblemNote, supports, ErrorNote)
(ProblemNote, hasStructure, SolutionNote)
(ProblemNote, hasStructure, PreparationNote)
(ProblemNote, hasStructure, DescriptiveAssessment)
```

### 2. 문제 노트 구조 관련 Triples

```
(SolutionNote, isSubtypeOf, Note)
(SolutionNote, createdDuring, Exam)
(PreparationNote, isSubtypeOf, Note)
(PreparationNote, createdAfter, ErrorOccurrence)
(PreparationNote, requires, SolutionNote)
(PreparationNote, requires, Explanation)
(PreparationNote, requires, ErrorCauseRecording)
(DescriptiveAssessment, isSubtypeOf, Assessment)
(DescriptiveAssessment, createdAfter, PreparationNote)
(DescriptiveAssessment, requires, "10MinutesDelay")
(DescriptiveAssessment, requires, NoExplanation)
(DescriptiveAssessment, requires, ReProblemSolving)
```

### 3. 문제 노트 분석 관련 Triples

```
(ProblemNote, analyzedBy, ProblemNoteAnalysis)
(ProblemNoteAnalysis, isSubtypeOf, Analysis)
(ProblemNoteAnalysis, requires, TotalStrokes)
(ProblemNoteAnalysis, requires, LastStrokeTime)
(ProblemNoteAnalysis, requires, CreatedTime)
(ProblemNoteAnalysis, requires, UsedTime)
(ProblemNoteAnalysis, requires, ContentTitle)
(ProblemNoteAnalysis, leadsTo, ErrorPatternAnalysis)
(ProblemNoteAnalysis, leadsTo, WeakAreaIdentification)
(ProblemNoteAnalysis, leadsTo, ReviewStrategy)
```

### 4. 오답 패턴 분석 관련 Triples

```
(ErrorPattern, isSubtypeOf, Pattern)
(ErrorPattern, analyzedBy, ErrorPatternAnalysis)
(ErrorPatternAnalysis, requires, ErrorNote)
(ErrorPatternAnalysis, leadsTo, WeakAreaIdentification)
(ErrorPatternAnalysis, leadsTo, ReviewStrategy)
(ErrorNote, analyzedBy, ErrorNoteAnalysis)
(ErrorNoteAnalysis, evaluates, ErrorNoteQuality)
(ErrorNoteQuality, affects, ReflectionDepth)
(ErrorNoteQuality, affects, BehaviorChange)
```

### 5. 문제 노트 휴리스틱 관련 Triples

```
(GoodErrorCauseWriting, enhances, ReflectionDepth)
(PracticalSolution, enhances, Speed)
(Speed, enhances, ContentConnection)
(SolutionHabit, hasPattern, FixedPattern)
(FixedPattern, identifiedBy, BehaviorObservation)
(FixedPattern, identifiedBy, WritingObservation)
```

### 6. 시그너처 루틴 발견 관련 Triples

```
(ProblemNote, analyzedFor, SignatureRoutine)
(SignatureRoutine, requires, ErrorNoteAnalysis)
(SignatureRoutine, requires, BehaviorPattern)
(SignatureRoutine, requires, WritingPattern)
(SignatureRoutine, leadsTo, BehaviorChange)
```

---

## 🎯 Agent12_RestRoutine Triples

### 1. 휴식 루틴 관련 Triples

```
(Student, hasRoutine, RestRoutine)
(RestRoutine, isSubtypeOf, Routine)
(RestRoutine, requires, RestTiming)
(RestRoutine, requires, RestDuration)
(RestRoutine, requires, RestActivity)
(RestRoutine, supports, LearningRecovery)
(RestRoutine, affects, LearningEfficiency)
(RestRoutine, analyzedBy, RestButtonClickAnalysis)
```

### 2. 휴식 패턴 분석 관련 Triples

```
(RestButtonClickAnalysis, isSubtypeOf, Analysis)
(RestButtonClickAnalysis, requires, RestButtonClickData)
(RestButtonClickAnalysis, identifies, RegularRestType)
(RestButtonClickAnalysis, identifies, ActivityCenteredRestType)
(RestButtonClickAnalysis, identifies, NoRestButtonType)
(RegularRestType, hasAverageTime, "60MinutesOrLess")
(ActivityCenteredRestType, hasAverageTime, "60-90Minutes")
(ConcentrationImmersionType, hasAverageTime, "90MinutesOrMore")
(NoRestButtonType, hasAttribute, NoRestClick)
```

### 3. 휴식 패턴 휴리스틱 관련 Triples

```
(RegularRestType, suggests, ActivityInterruptionHabit)
(NoRestButtonType, suggests, NoRestStudyDistinction)
(NoRestButtonType, suggests, ConditionDeterioration)
(UnestablishedRestRoutine, suggests, LongTermSlump)
```

### 4. 수업 중 휴식 분석 관련 Triples

```
(RestRoutine, analyzedFor, ClassRestEffectiveness)
(ClassRestEffectiveness, requires, PeriodicRestButtonHistory)
(ClassRestEffectiveness, requires, PreRestLearningFlow)
(ClassRestEffectiveness, requires, PostRestLearningFlow)
(ClassRestEffectiveness, analyzes, PsychologicalIntensity)
(ClassRestEffectiveness, affects, ReturnCheck)
```

### 5. 주단위 피로감 분석 관련 Triples

```
(RestRoutine, analyzedFor, WeeklyFatigue)
(WeeklyFatigue, requires, SchoolSchedule)
(WeeklyFatigue, requires, ExternalScheduleChange)
(WeeklyFatigue, analyzes, TemporaryChange)
(WeeklyFatigue, analyzes, AccumulatedChange)
(AccumulatedChange, requires, CauseAnalysis)
(AccumulatedChange, requires, ActiveReadjustment)
```

### 6. 휴식 루틴 최적화 관련 Triples

```
(RestRoutine, optimizedFor, EnergyRecovery)
(RestRoutine, optimizedFor, FocusMaintenance)
(RestRoutine, requires, RestPatternAnalysis)
(RestPatternAnalysis, leadsTo, OptimalRest)
(RestPatternAnalysis, leadsTo, EnergyLevel)
```

---

## 🎯 Agent13_LearningDropout Triples

### 1. 학습 이탈 관련 Triples

```
(Student, hasRisk, LearningDropout)
(LearningDropout, isSubtypeOf, Risk)
(LearningDropout, requires, DropoutDetection)
(LearningDropout, requires, Intervention)
(DropoutDetection, requires, EngagementAnalysis)
(DropoutDetection, requires, EmotionAnalysis)
(Intervention, leadsTo, Reengagement)
(DropoutDetection, usesWindow, "24HourRolling")
```

### 2. 학습 이탈 지표 관련 Triples

```
(DropoutDetection, requires, InactiveEventCount)
(DropoutDetection, requires, DelayedViewing)
(DropoutDetection, requires, NoInputDuration)
(DropoutDetection, requires, RoutineDelay)
(DropoutDetection, requires, PomodoroState)
(InactiveEventCount, measuredBy, "5MinuteCooldown")
(DelayedViewing, measuredBy, "5MinutesOrMore")
(NoInputDuration, measuredBy, LastStrokeTime)
(RoutineDelay, measuredBy, "20MinutesPerBlock")
```

### 3. 수학 특화 이탈 지표 관련 Triples

```
(DropoutDetection, requires, UnitDropoutFrequency)
(DropoutDetection, requires, DifficultyDropoutFrequency)
(DropoutDetection, requires, LearningStageDropoutFrequency)
(DropoutDetection, requires, AcademyContext)
(DropoutDetection, requires, MathPerformanceLevel)
(UnitDropoutFrequency, measuredBy, CurrentUnit)
(DifficultyDropoutFrequency, measuredBy, ProblemDifficulty)
(LearningStageDropoutFrequency, measuredBy, LearningStage)
(AcademyContext, requires, AcademyClassUnderstanding)
(AcademyContext, requires, AcademyAssignmentBurden)
```

### 4. 위험 등급 관련 Triples

```
(LearningDropout, hasRiskLevel, RiskLevel)
(RiskLevel, hasValue, "Low")
(RiskLevel, hasValue, "Medium")
(RiskLevel, hasValue, "High")
(LowRisk, requires, LowInactiveEvents)
(LowRisk, requires, HighPomodoroCount)
(MediumRisk, requires, MediumInactiveEvents)
(MediumRisk, requires, MediumPomodoroCount)
(MediumRisk, requires, MultipleDelayedViewing)
(HighRisk, requires, HighInactiveEvents)
(HighRisk, requires, LowPomodoroCount)
(HighRisk, requires, LongNoInputDuration)
```

### 5. 수학 특화 위험 등급 관련 Triples

```
(UnitRiskLevel, affectedBy, DifficultUnit)
(DifficultyRiskLevel, affectedBy, AdvancedProblem)
(AcademyContextRiskLevel, affectedBy, AcademyClassUnderstanding)
(AcademyContextRiskLevel, affectedBy, AcademyAssignmentBurden)
(DifficultUnit, includes, "Function")
(DifficultUnit, includes, "Geometry")
(DifficultUnit, includes, "Quadratic")
```

### 6. 학습 이탈 휴리스틱 관련 Triples

```
(RepeatedWritingDelay, suggests, MotivationDecrease)
(RepeatedWritingDelay, suggests, ActivityDecrease)
(LowPomodoroCount, suggests, ShortTermGoalRoutine)
(LoginGoalDelay, suggests, HighStartBarrier)
(UnitDropoutPattern, suggests, DifficultyAdjustment)
(DifficultyDropoutPattern, suggests, ProblemTypeChange)
(LearningStageDropoutPattern, suggests, StageSpecificIntervention)
(AcademyContextDropoutPattern, suggests, AcademySpecificIntervention)
```

### 7. 개입 액션 관련 Triples

```
(Intervention, hasAction, ImmediateIntervention)
(Intervention, hasAction, RoutineCorrection)
(Intervention, hasAction, Escalation)
(ImmediateIntervention, requires, RefocusMessage)
(ImmediateIntervention, requires, EasyWinTask)
(RoutineCorrection, requires, SessionLengthReduction)
(RoutineCorrection, requires, RestAlarmAdjustment)
(Escalation, requires, HighRiskConsecutiveDays)
(Escalation, requires, ParentNotification)
(Escalation, requires, TeacherNotification)
```

### 8. 수학 특화 개입 관련 Triples

```
(UnitIntervention, requires, DifficultUnitDropout)
(UnitIntervention, suggests, BasicConceptRelearning)
(DifficultyIntervention, requires, AdvancedProblemDropout)
(DifficultyIntervention, suggests, BasicProblemTypeChange)
(LearningStageIntervention, requires, ConceptLearningDropout)
(LearningStageIntervention, suggests, VisualMaterial)
(LearningStageIntervention, suggests, ExampleUtilization)
(ProblemSolvingIntervention, requires, ProblemSolvingDropout)
(ProblemSolvingIntervention, suggests, EasierProblemChange)
(AcademyContextIntervention, requires, AcademyClassUnderstanding)
(AcademyContextIntervention, suggests, AcademyTextbookRelearning)
(AcademyAssignmentIntervention, requires, AcademyAssignmentOverload)
(AcademyAssignmentIntervention, suggests, AssignmentPriorityAdjustment)
```

### 9. 수준별 개입 관련 Triples

```
(LowerLevelIntervention, requires, LowerLevelStudent)
(LowerLevelIntervention, suggests, EasiestBasicProblem)
(LowerLevelIntervention, suggests, AchievementFeeling)
(MediumLevelIntervention, requires, MediumLevelStudent)
(MediumLevelIntervention, suggests, "10MinuteSession")
(MediumLevelIntervention, suggests, RoutineStrengthening)
(UpperLevelIntervention, requires, UpperLevelStudent)
(UpperLevelIntervention, suggests, CreativeProblem)
(UpperLevelIntervention, suggests, VariantProblem)
(UpperLevelIntervention, suggests, InterestInduction)
```

---

## 🎯 Agent14_CurrentPosition Triples

### 1. 현재 위치 분석 관련 Triples

```
(Student, hasPosition, CurrentPosition)
(CurrentPosition, isSubtypeOf, Position)
(CurrentPosition, requires, ProgressAnalysis)
(CurrentPosition, requires, LevelAnalysis)
(CurrentPosition, affects, GoalSetting)
(CurrentPosition, affects, PlanAdjustment)
(CurrentPosition, calculatedFrom, PlanVsActual)
(CurrentPosition, calculatedFrom, TimeTrajectory)
(CurrentPosition, calculatedFrom, ActivityLog)
(CurrentPosition, calculatedFrom, EmotionalTrajectory)
```

### 2. 진행 상태 분석 관련 Triples

```
(ProgressAnalysis, isSubtypeOf, Analysis)
(ProgressAnalysis, requires, ExpectedCompletionTime)
(ProgressAnalysis, requires, ActualProgressTime)
(ProgressAnalysis, calculates, Deviation)
(Deviation, affects, ProgressStatus)
(ProgressStatus, hasValue, "Smooth")
(ProgressStatus, hasValue, "Appropriate")
(ProgressStatus, hasValue, "Delayed")
(ProgressStatus, hasValue, "Stagnant")
(Deviation, greaterThanOrEqual, "30Minutes")
(Deviation, indicates, Delay)
(Deviation, greaterThanOrEqual, "60Minutes")
(Deviation, indicates, SeriousDelay)
```

### 3. 포모도르 리듬 분석 관련 Triples

```
(RhythmAnalysis, isSubtypeOf, Analysis)
(RhythmAnalysis, requires, BlockStartTime)
(RhythmAnalysis, requires, BlockCompletionTime)
(RhythmAnalysis, requires, BlockInterval)
(RhythmAnalysis, requires, MissingBlock)
(RhythmAnalysis, requires, Continuity)
(RhythmAnalysis, requires, FocusInterval)
(RhythmAnalysis, calculates, RhythmScore)
(RhythmScore, hasRange, "0-100")
(RhythmScore, indicates, RhythmBreakage)
```

### 4. 정서 상태 분석 관련 Triples

```
(EmotionalAnalysis, isSubtypeOf, Analysis)
(EmotionalAnalysis, requires, CompletedBlockStatus)
(EmotionalAnalysis, calculates, EmotionalCurve)
(EmotionalCurve, isSubtypeOf, TimeSeries)
(EmotionalCurve, identifies, PositiveInterval)
(EmotionalCurve, identifies, NeutralInterval)
(EmotionalCurve, identifies, NegativeInterval)
(NegativeEmotionInterval, overlapsWith, LearningDelay)
(NegativeEmotionInterval, requires, CrossAnalysis)
```

### 5. 이탈 가능성 예측 관련 Triples

```
(DropoutPrediction, isSubtypeOf, Prediction)
(DropoutPrediction, requires, CompletionRate)
(DropoutPrediction, requires, DelayDegree)
(DropoutPrediction, requires, EmotionIndex)
(DropoutPrediction, requires, InactivityTime)
(DropoutPrediction, calculates, RiskScore)
(RiskScore, affects, RiskLevel)
(RiskLevel, hasValue, "Low")
(RiskLevel, hasValue, "Medium")
(RiskLevel, hasValue, "High")
(RiskLevel, hasValue, "Critical")
```

### 6. 분석 축 관련 Triples

```
(CurrentPosition, analyzedBy, ProgressTrajectory)
(CurrentPosition, analyzedBy, RhythmPattern)
(CurrentPosition, analyzedBy, EmotionalCurve)
(CurrentPosition, analyzedBy, RiskIndex)
(CurrentPosition, analyzedBy, FocusDensity)
(ProgressTrajectory, requires, BeginTime)
(ProgressTrajectory, requires, DueTime)
(ProgressTrajectory, requires, EndTime)
(ProgressTrajectory, requires, Status)
(ProgressTrajectory, outputs, ProgressStatus)
(RhythmPattern, requires, PomodoroInterval)
(RhythmPattern, requires, MissingBlock)
(RhythmPattern, outputs, RhythmScore)
(EmotionalCurve, requires, StatusSatisfactionLog)
(EmotionalCurve, outputs, EmotionalState)
(RiskIndex, requires, ProgressRate)
(RiskIndex, requires, Emotion)
(RiskIndex, requires, AbsenceTime)
(RiskIndex, outputs, RiskLevel)
(FocusDensity, requires, FocusIntervalStayTime)
(FocusDensity, requires, CompletionRate)
(FocusDensity, outputs, ImmersionIndex)
```

### 7. 판단 메커니즘 관련 Triples

```
(ProgressStatusAnalysis, requires, ExpectedVsActual)
(ProgressStatusAnalysis, calculates, Deviation)
(IdleGap, measuredBy, PomodoroInterval)
(IdleGap, indicates, FlowBreakage)
(EmotionalStateAnalysis, requires, StatusDistribution)
(EmotionalStateAnalysis, calculates, EmotionalVector)
(EmotionalVector, hasValue, "Positive")
(EmotionalVector, hasValue, "Neutral")
(EmotionalVector, hasValue, "Negative")
(ActivityContinuityAnalysis, requires, EndTime)
(ActivityContinuityAnalysis, requires, NextBeginTime)
(ActivityContinuityAnalysis, identifies, ActivityDisruption)
(ActivityDisruption, measuredBy, "20MinutesOrMore")
(ConsecutiveDisruption, indicates, RoutineDeviation)
```

### 8. 위험도 점수 계산 관련 Triples

```
(RiskScore, calculatedFrom, CompletionRate)
(RiskScore, calculatedFrom, DelayIndex)
(RiskScore, calculatedFrom, NegativeEmotionRatio)
(RiskScore, calculatedFrom, InactivityTime)
(RiskScore, weightedBy, "0.4")
(RiskScore, weightedBy, "0.3")
(RiskScore, weightedBy, "0.2")
(RiskScore, weightedBy, "0.1")
```

### 9. 출력 리포트 관련 Triples

```
(CurrentPosition, generates, PositionReport)
(PositionReport, isSubtypeOf, Report)
(PositionReport, includes, ProgressStatus)
(PositionReport, includes, CompletionRate)
(PositionReport, includes, EmotionalState)
(PositionReport, includes, RhythmScore)
(PositionReport, includes, DropoutRisk)
(PositionReport, includes, CoreCauseSummary)
(PositionReport, includes, RecommendedResponse)
(PositionReport, deliveredTo, Agent13)
(PositionReport, deliveredTo, Agent09)
(PositionReport, deliveredTo, Agent12)
```

---

## 🎯 Agent15_ProblemRedefinition Triples

### 1. 문제 재정의 관련 Triples

```
(Student, redefines, Problem)
(Problem, hasAttribute, ProblemDefinition)
(ProblemRedefinition, isSubtypeOf, ProblemSolving)
(ProblemRedefinition, requires, ProblemAnalysis)
(ProblemRedefinition, requires, PerspectiveShift)
(ProblemRedefinition, leadsTo, NewSolution)
(ProblemRedefinition, requires, ComprehensiveAnalysis)
(ProblemRedefinition, requires, InitialProblem)
```

### 2. 종합 분석 데이터 관련 Triples

```
(ComprehensiveAnalysis, requires, OnboardingInfo)
(ComprehensiveAnalysis, requires, ProblemDiscovery)
(ComprehensiveAnalysis, requires, SituationType)
(ComprehensiveAnalysis, requires, ActivityType)
(ComprehensiveAnalysis, requires, GuidanceMode)
(ComprehensiveAnalysis, requires, GoalAnalysis)
(ComprehensiveAnalysis, requires, PomodoroJournal)
(ComprehensiveAnalysis, requires, CalmnessAnalysis)
(ComprehensiveAnalysis, requires, LearningDropoutAnalysis)
(ComprehensiveAnalysis, requires, LearningContentAnalysis)
(ComprehensiveAnalysis, requires, RestPatternAnalysis)
(ComprehensiveAnalysis, requires, ProgressAnalysis)
(ComprehensiveAnalysis, requires, SolutionNoteAnalysis)
(ComprehensiveAnalysis, requires, ErrorNoteAnalysis)
(ComprehensiveAnalysis, requires, TeacherFeedback)
```

### 3. 문제 재정의 프레임워크 관련 Triples

```
(ProblemRedefinition, usesFramework, RedefinitionFramework)
(RedefinitionFramework, hasStep, SymptomIdentification)
(RedefinitionFramework, hasStep, RootCauseHypothesis)
(RedefinitionFramework, hasStep, ValidationPlan)
(RedefinitionFramework, hasStep, ActionPlan)
(SymptomIdentification, requires, SurfaceProblem)
(SymptomIdentification, requires, ObservedPattern)
(SymptomIdentification, requires, ConsistencyCheck)
(ObservedPattern, includes, DropoutPattern)
(ObservedPattern, includes, DelayPattern)
(ObservedPattern, includes, SatisfactionDecreasePattern)
(ObservedPattern, includes, ErrorPattern)
(ObservedPattern, includes, CalmnessChangePattern)
```

### 4. 원인 가설 관련 Triples

```
(RootCauseHypothesis, isSubtypeOf, Hypothesis)
(RootCauseHypothesis, requires, RootCauseInference)
(RootCauseHypothesis, requires, MultiLayerCauseAnalysis)
(MultiLayerCauseAnalysis, hasType, "CognitiveCause")
(MultiLayerCauseAnalysis, hasType, "BehavioralCause")
(MultiLayerCauseAnalysis, hasType, "MotivationalCause")
(MultiLayerCauseAnalysis, hasType, "EnvironmentalCause")
(CognitiveCause, includes, ConceptUnderstandingLack)
(CognitiveCause, includes, ProblemSolvingStrategyLack)
(CognitiveCause, includes, MetacognitionLack)
(BehavioralCause, includes, LearningHabitProblem)
(BehavioralCause, includes, TimeManagementFailure)
(BehavioralCause, includes, RoutineNotEstablished)
(MotivationalCause, includes, UnclearGoal)
(MotivationalCause, includes, MotivationDecrease)
(MotivationalCause, includes, SelfEsteemProblem)
(EnvironmentalCause, includes, UnsuitableLearningEnvironment)
(EnvironmentalCause, includes, ExternalPressure)
(EnvironmentalCause, includes, LackOfSupport)
(RootCauseHypothesis, requires, HypothesisPriority)
```

### 5. 검증 계획 관련 Triples

```
(ValidationPlan, isSubtypeOf, Plan)
(ValidationPlan, requires, DataBasedValidation)
(ValidationPlan, requires, AdditionalDataCollection)
(ValidationPlan, requires, ValidationIndicator)
(DataBasedValidation, uses, ExistingAnalysisData)
(AdditionalDataCollection, requires, DataCollectionPlan)
(ValidationIndicator, measures, HypothesisProof)
```

### 6. 조치안 관련 Triples

```
(ActionPlan, isSubtypeOf, Plan)
(ActionPlan, requires, ExecutableImprovement)
(ActionPlan, requires, PrioritySetting)
(ActionPlan, requires, SuccessCriteria)
(ActionPlan, requires, LinkageStrategy)
(ExecutableImprovement, hasPriority, "Immediate")
(ExecutableImprovement, hasPriority, "ShortTerm")
(ExecutableImprovement, hasPriority, "MediumTerm")
(ExecutableImprovement, hasPriority, "LongTerm")
(SuccessCriteria, measures, ActionSuccess)
(LinkageStrategy, connectsTo, StrategyReadjustment)
(LinkageStrategy, connectsTo, InteractionContentGeneration)
```

### 7. 문제 재정의 출력 관련 Triples

```
(ProblemRedefinition, generates, RedefinedProblem)
(RedefinedProblem, includes, InitialProblemSummary)
(RedefinedProblem, includes, AnalysisResultSummary)
(RedefinedProblem, includes, RedefinedProblemDescription)
(RedefinedProblem, includes, CoreSolutionDirection)
(RedefinedProblem, includes, PriorityActions)
(RedefinedProblem, deliveredTo, OtherAgents)
```

### 8. 우선순위 선정 관련 Triples

```
(ProblemRedefinition, selects, Priority)
(Priority, hasValue, "Priority1")
(Priority, hasValue, "Priority2")
(Priority, hasValue, "Priority3")
(Priority, connectedTo, StandardDiagnosisItem)
(Priority, combinedWith, StudentAnalysisInfo)
(Priority, generates, CustomizedImprovementIdea)
(CustomizedImprovementIdea, deliveredTo, OtherAgents)
```

---

## 🎯 Agent16_InteractionPreparation Triples

### 1. 상호작용 준비 관련 Triples

```
(Interaction, requires, InteractionPreparation)
(InteractionPreparation, isSubtypeOf, Preparation)
(InteractionPreparation, requires, ContentPreparation)
(InteractionPreparation, requires, ContextPreparation)
(InteractionPreparation, requires, TimingPreparation)
(InteractionPreparation, affects, InteractionQuality)
(InteractionPreparation, requires, ImprovementIdea)
(InteractionPreparation, convertsTo, Content)
```

### 2. 세계관 선택 관련 Triples

```
(InteractionPreparation, selects, LearningUniverse)
(LearningUniverse, isSubtypeOf, Worldview)
(LearningUniverse, hasType, "CurriculumCentered")
(LearningUniverse, hasType, "PersonalizedLearning")
(LearningUniverse, hasType, "ExamPreparation")
(LearningUniverse, hasType, "ShortTermMission")
(LearningUniverse, hasType, "SelfReflection")
(LearningUniverse, hasType, "SelfDirected")
(LearningUniverse, hasType, "ApprenticeshipLearning")
(LearningUniverse, hasType, "TimeReflection")
(LearningUniverse, hasType, "InquiryLearning")
(LearningUniverse, requires, NaturalStorytelling)
```

### 3. 스토리텔링 테마 관련 Triples

```
(InteractionPreparation, sets, StorytellingTheme)
(StorytellingTheme, isSubtypeOf, Theme)
(StorytellingTheme, reflects, RecentEmotion)
(StorytellingTheme, reflects, ProgressState)
(StorytellingTheme, receivesFrom, Agent05)
(StorytellingTheme, receivesFrom, Agent13)
(StorytellingTheme, receivesFrom, Agent14)
(StorytellingTheme, receivesFrom, Agent15)
```

### 4. 상호작용 연속성 관련 Triples

```
(InteractionPreparation, designs, InteractionContinuity)
(InteractionContinuity, requires, PreviousInteraction)
(InteractionContinuity, requires, EmotionalTone)
(InteractionContinuity, requires, Theme)
(InteractionContinuity, requires, WorldviewData)
(InteractionContinuity, maintains, Character)
(InteractionContinuity, maintains, Setting)
(InteractionContinuity, uses, ContinuousRelationshipTone)
```

### 5. 출력 목표 관련 Triples

```
(InteractionPreparation, generates, StructuredDesignInfo)
(StructuredDesignInfo, includes, SelectedWorldview)
(StructuredDesignInfo, includes, Theme)
(StructuredDesignInfo, includes, InteractionTone)
(StructuredDesignInfo, deliveredTo, ContentGenerator)
(StructuredDesignInfo, deliveredTo, VoiceConverter)
```

### 6. 체크리스트 관련 Triples

```
(InteractionPreparation, requires, Checklist)
(Checklist, includes, StudentCurrentState)
(Checklist, includes, Goal)
(Checklist, includes, TeacherMemo)
(Checklist, includes, TimeWindow)
(StudentCurrentState, includes, Focus)
(StudentCurrentState, includes, Emotion)
(StudentCurrentState, includes, Progress)
```

### 7. 휴리스틱 관련 Triples

```
(InteractionPreparation, usesHeuristic, StateBasedHeuristic)
(StateBasedHeuristic, requires, GoodState)
(StateBasedHeuristic, requires, PoorState)
(GoodState, suggests, AdvancedInquiry)
(GoodState, suggests, ChallengingProblem)
(PoorState, suggests, SummaryConfirmation)
(PoorState, suggests, LowIntensityQuestion)
```

---

## 🎯 Agent17_RemainingActivities Triples

### 1. 남은 활동 관련 Triples

```
(Student, hasActivities, RemainingActivities)
(RemainingActivities, isSubtypeOf, Activities)
(RemainingActivities, requires, ActivityAnalysis)
(RemainingActivities, requires, PrioritySetting)
(RemainingActivities, affects, ScheduleAdjustment)
(RemainingActivities, affects, GoalAchievement)
(RemainingActivities, requires, RhythmRecovery)
(RemainingActivities, requires, FlowRecovery)
```

### 2. 리듬 회복 관련 Triples

```
(RhythmRecovery, isSubtypeOf, Recovery)
(RhythmRecovery, requires, EmotionFirstLoop)
(RhythmRecovery, requires, AdaptationSpeedCorrection)
(RhythmRecovery, requires, RecoveryBasedReadjustment)
(EmotionFirstLoop, requires, EmotionBasedUnderstanding)
(EmotionFirstLoop, requires, "1-2MinuteRecovery")
(EmotionFirstLoop, requires, LearningStructureAdjustment)
(AdaptationSpeedCorrection, requires, ActivityModification)
(AdaptationSpeedCorrection, avoids, CompletelyNewActivity)
(RecoveryBasedReadjustment, requires, RecoveryPossibility)
(RecoveryBasedReadjustment, avoids, RemainingTimeBased)
```

### 3. 목표 관련 데이터 Triples

```
(RemainingActivities, requires, DailyGoal)
(RemainingActivities, requires, WeeklyGoal)
(RemainingActivities, requires, QuarterlyGoal)
(RemainingActivities, requires, GoalAlignment)
(DailyGoal, includes, PlannedActivityList)
(DailyGoal, includes, CompletionStatus)
(WeeklyGoal, includes, WeeklyGoalItems)
(WeeklyGoal, includes, CurrentProgressRate)
(QuarterlyGoal, includes, LongTermGoal)
(QuarterlyGoal, includes, CurrentPosition)
```

### 4. 현재 위치 평가 데이터 Triples

```
(RemainingActivities, requires, CurrentPositionEvaluation)
(CurrentPositionEvaluation, includes, CurrentProgressPosition)
(CurrentPositionEvaluation, includes, CompletionRate)
(CurrentPositionEvaluation, includes, ProgressTrajectory)
(CurrentPositionEvaluation, includes, PomodoroProgressData)
(CurrentPositionEvaluation, includes, RhythmScore)
(CurrentPositionEvaluation, includes, EmotionalState)
(CurrentPositionEvaluation, includes, DropoutRisk)
(CurrentPositionEvaluation, includes, CoreCauseSummary)
(CurrentPositionEvaluation, includes, RecommendedResponse)
```

### 5. 활동 유형 데이터 Triples

```
(RemainingActivities, requires, ActivityTypeData)
(ActivityTypeData, includes, ActivityCategory)
(ActivityTypeData, includes, ActivityDifficulty)
(ActivityTypeData, includes, ActivityCompletionStatus)
(ActivityCategory, hasValue, "ConceptUnderstanding")
(ActivityCategory, hasValue, "TypeLearning")
(ActivityCategory, hasValue, "ProblemSolving")
(ActivityCategory, hasValue, "ErrorNote")
(ActivityCategory, hasValue, "QnA")
(ActivityCategory, hasValue, "ReviewActivity")
(ActivityCategory, hasValue, "Pomodoro")
```

### 6. 시간 제약 데이터 Triples

```
(RemainingActivities, requires, TimeConstraintData)
(TimeConstraintData, includes, AvailableTime)
(TimeConstraintData, includes, TimeWindow)
(TimeConstraintData, includes, FatigueLevel)
(AvailableTime, hasType, "Daily")
(AvailableTime, hasType, "Weekly")
(TimeWindow, measuredBy, NextClassTime)
(FatigueLevel, calculatedFrom, CumulativeStudyTime)
```

### 7. 문제 재정의 데이터 Triples

```
(RemainingActivities, requires, ProblemRedefinitionData)
(ProblemRedefinitionData, includes, CoreImprovementDirection)
(ProblemRedefinitionData, includes, SuccessCriteria)
(CoreImprovementDirection, hasPriority, "Priority1")
(CoreImprovementDirection, hasPriority, "Priority2")
(CoreImprovementDirection, hasPriority, "Priority3")
```

### 8. 활동 조정 전략 관련 Triples

```
(RemainingActivities, usesStrategy, ActivityAdjustmentStrategy)
(ActivityAdjustmentStrategy, hasType, "DailyGoalAdjustment")
(ActivityAdjustmentStrategy, hasType, "WeeklyGoalAdjustment")
(ActivityAdjustmentStrategy, hasType, "BottleneckActivityDivision")
(ActivityAdjustmentStrategy, hasType, "CoreActivitySelection")
(DailyGoalAdjustment, requires, TodayCompletableActivities)
(WeeklyGoalAdjustment, requires, WeeklyGoalAchievement)
(BottleneckActivityDivision, requires, LargeUnitActivity)
(BottleneckActivityDivision, dividesInto, SmallSessions)
(CoreActivitySelection, uses, ParetoPrinciple)
(CoreActivitySelection, focusesOn, "20PercentActivities")
```

### 9. 감정 기반 이해 관련 Triples

```
(RemainingActivities, requires, EmotionBasedUnderstanding)
(EmotionBasedUnderstanding, identifies, BlockageReason)
(EmotionBasedUnderstanding, identifies, ConcentrationBreakage)
(EmotionBasedUnderstanding, identifies, FatigueAccumulation)
(EmotionBasedUnderstanding, identifies, FailureEmotionAccumulation)
(EmotionBasedUnderstanding, requires, "1-2MinuteRecovery")
(EmotionBasedUnderstanding, requires, FamiliarActivity)
(EmotionBasedUnderstanding, requires, RhythmEstablishment)
```

### 10. 활동 변형 관련 Triples

```
(RemainingActivities, uses, ActivityModification)
(ActivityModification, modifies, ExistingActivity)
(ActivityModification, avoids, CompletelyNewActivity)
(ActivityModification, example, "HalfProblemSolving")
(ActivityModification, example, "SolutionDirectionOnly")
(ActivityModification, maintains, AdaptationFeeling)
(ActivityModification, avoids, NewRuleLearningBurden)
```

### 11. 회복도 기준 재조정 관련 Triples

```
(RemainingActivities, uses, RecoveryBasedReadjustment)
(RecoveryBasedReadjustment, requires, RecoveryPossibility)
(RecoveryPossibility, includes, EmotionalEnergy)
(RecoveryPossibility, includes, CognitiveEnergy)
(RecoveryBasedReadjustment, avoids, RemainingTimeBased)
(RecoveryBasedReadjustment, example, "25MinuteFocus")
(RecoveryBasedReadjustment, example, "5MinuteReflection")
(RecoveryBasedReadjustment, example, "10MinuteEasyReview")
```

---

## 🎯 Agent18_SignatureRoutine Triples

### 1. 시그너처 루틴 관련 Triples

```
(Student, hasRoutine, SignatureRoutine)
(SignatureRoutine, isSubtypeOf, Routine)
(SignatureRoutine, requires, Persona)
(SignatureRoutine, requires, Immersion)
(SignatureRoutine, requires, BehaviorPattern)
(SignatureRoutine, affects, LearningEfficiency)
(SignatureRoutine, leadsTo, BehaviorChange)
(SignatureRoutine, discoveredBy, Drilling)
(SignatureRoutine, requires, OnboardingInfo)
(SignatureRoutine, requires, UpdatedPreferenceInfo)
```

### 2. 데이터 결합 관련 Triples

```
(SignatureRoutine, requires, DataIntegration)
(DataIntegration, includes, OnboardingProfile)
(DataIntegration, includes, LatestPreferenceInfo)
(OnboardingProfile, includes, LearningTendency)
(OnboardingProfile, includes, GoalSettingMethod)
(OnboardingProfile, includes, FocusDuration)
(OnboardingProfile, includes, EmotionalReactionPattern)
(LatestPreferenceInfo, includes, RecentStudyMethod)
(LatestPreferenceInfo, includes, PomodoroPattern)
(LatestPreferenceInfo, includes, ImmersionIndex)
(LatestPreferenceInfo, includes, EmotionalReactionLog)
```

### 3. Drilling 분석 관련 Triples

```
(SignatureRoutine, discoveredBy, Drilling)
(Drilling, isSubtypeOf, Analysis)
(Drilling, detects, ImmersionRisePattern)
(Drilling, searches, EmotionalStabilityInterval)
(Drilling, searches, AchievementRiseInterval)
(Drilling, categorizes, SelfConfirmationRoutine)
(Drilling, categorizes, ImmersionEntryRoutine)
(Drilling, categorizes, RecoveryRoutine)
(ImmersionRisePattern, example, "HandwritingSummaryConfirmationProblemLoop")
```

### 4. 핵심 심리요인 도출 관련 Triples

```
(SignatureRoutine, derives, CorePsychologicalFactor)
(CorePsychologicalFactor, hasType, "AchievementCentered")
(CorePsychologicalFactor, hasType, "InquiryType")
(CorePsychologicalFactor, hasType, "RhythmType")
(CorePsychologicalFactor, hasType, "ChallengeType")
(CorePsychologicalFactor, hasType, "MeaningType")
(CorePsychologicalFactor, calculatedFrom, EmotionBehaviorResultCorrelation)
```

### 5. 시그너처 루틴 제안 관련 Triples

```
(SignatureRoutine, proposedAs, RoutineProposal)
(RoutineProposal, includes, RoutineName)
(RoutineProposal, includes, ExecutionCondition)
(RoutineProposal, includes, ReinforcementPoint)
(RoutineProposal, example, "WarmFocusLoop")
(WarmFocusLoop, hasStep, "Handwriting")
(WarmFocusLoop, hasStep, "EyePause")
(WarmFocusLoop, hasStep, "SelfQuestion")
(WarmFocusLoop, hasStep, "ConfirmationProblem")
(ReinforcementPoint, includes, "SelfQuestionAchievementFeedback")
```

### 6. 컨텐츠 가이드라인 생성 관련 Triples

```
(SignatureRoutine, generates, ContentGuideline)
(ContentGuideline, specifies, RoutineInducingElement)
(ContentGuideline, requires, VisualFeedback)
(ContentGuideline, requires, ShortConfirmationQuestion)
(ContentGuideline, requires, ImmediateReflection)
```

### 7. 개인 최적 학습 루틴 관련 Triples

```
(SignatureRoutine, optimizedFor, PersonalOptimalLearningRoutine)
(PersonalOptimalLearningRoutine, requires, TimeSlotPerformance)
(PersonalOptimalLearningRoutine, requires, SessionLength)
(PersonalOptimalLearningRoutine, requires, RestPattern)
(PersonalOptimalLearningRoutine, requires, SubjectSuitability)
(TimeSlotPerformance, matches, OptimalSubject)
(SessionLength, finds, OptimalPoint)
```

### 8. 시그너처 루틴 발견 관련 Triples

```
(SignatureRoutine, discoveredBy, PatternDiscovery)
(PatternDiscovery, requires, OnboardingInfo)
(PatternDiscovery, requires, RecentPreference)
(PatternDiscovery, identifies, NaturalStudyMoment)
(PatternDiscovery, defines, SignatureRoutine)
(SignatureRoutine, generates, ImmersionGuideline)
(ImmersionGuideline, usedFor, InteractionDesign)
```

---

## 🎯 Agent19_InteractionContent Triples

### 1. 상호작용 컨텐츠 관련 Triples

```
(Interaction, hasContent, InteractionContent)
(InteractionContent, isSubtypeOf, Content)
(InteractionContent, requires, ContentType)
(InteractionContent, requires, ContentContext)
(InteractionContent, requires, StudentPersona)
(InteractionContent, affects, StudentResponse)
(InteractionContent, generatedBy, Algorithm)
(InteractionContent, packagedAs, PackagedContent)
(InteractionContent, generatedAs, Code)
```

### 2. 상호작용 유형 선택 관련 Triples

```
(InteractionContent, selectsType, InteractionType)
(InteractionType, selectedBy, Algorithm)
(InteractionType, hasType, "TextDelivery")
(InteractionType, hasType, "InteractiveContent")
(InteractionType, hasType, "LearningRoutineImprovement")
(InteractionType, hasType, "TimeShifting")
(InteractionType, hasType, "ActivityRejection")
(InteractionType, hasType, "LearningPointChange")
(InteractionType, hasType, "MultiTurnInteraction")
(InteractionType, hasType, "NonLinearInteractionAlgorithm")
(InteractionType, selectedBy, SituationSuitability)
(InteractionType, selectedBy, UserProfile)
(InteractionType, selectedBy, GoalAlignment)
(InteractionType, selectedBy, EffectivenessPrediction)
(InteractionType, selectedBy, ResourceEfficiency)
```

### 3. 상호작용 템플릿 관련 Triples

```
(InteractionContent, uses, InteractionTemplate)
(InteractionTemplate, isSubtypeOf, Template)
(InteractionTemplate, searchedFrom, TemplateLibrary)
(InteractionTemplate, reusedIf, Suitable)
(InteractionTemplate, createdIf, NotSuitable)
(InteractionTemplate, personalizedBy, UserCharacteristics)
(InteractionTemplate, addedTo, TemplateLibrary)
```

### 4. 템플릿 패키징 관련 Triples

```
(InteractionTemplate, packagedAs, PackagedTemplate)
(PackagedTemplate, optimizedFor, SelectedType)
(PackagedTemplate, generatedAs, Code)
(PackagedTemplate, references, RulesFile)
(PackagedTemplate, integrates, ContentLinks)
(Code, hasType, "HTML")
(Code, hasType, "CSS")
(Code, hasType, "JavaScript")
```

### 5. 발송 준비 관련 Triples

```
(InteractionContent, preparedFor, Delivery)
(Delivery, requires, CodeValidation)
(Delivery, requires, CompletePackage)
(Delivery, requires, DeliveryReadyState)
(CodeValidation, checks, Syntax)
(CodeValidation, checks, Executability)
(CompletePackage, includes, TemplateCode)
(CompletePackage, includes, ContentLinks)
(CompletePackage, includes, Metadata)
(DeliveryReadyState, indicates, ReadyToSend)
```

### 6. 수학 학습 컨텐츠 관련 Triples

```
(InteractionContent, prohibits, MathLearningContentGeneration)
(MathLearningContentGeneration, isSubtypeOf, ContentGeneration)
(MathLearningContentGeneration, prohibitedBy, Principle)
(InteractionContent, provides, ContentLinks)
(ContentLinks, retrievedFrom, RulesFile)
(ContentLinks, linksTo, ContentSystem)
```

### 7. 상호작용 템플릿 생성 관련 Triples

```
(InteractionContent, focusesOn, InteractionTemplateGeneration)
(InteractionTemplateGeneration, supports, InteractionTypes)
(InteractionTemplateGeneration, personalizedBy, MBTI)
(InteractionTemplateGeneration, personalizedBy, Preference)
(InteractionTemplateGeneration, personalizedBy, LearningStyle)
```

### 8. 효과 분석 관련 Triples

```
(InteractionContent, analyzedFor, Effectiveness)
(Effectiveness, measuredBy, TrackingMetrics)
(Effectiveness, analyzedBy, EffectivenessAnalysis)
(TrackingMetrics, tracks, StudentResponse)
(TrackingMetrics, tracks, InteractionResult)
(EffectivenessAnalysis, evaluates, InteractionEffectiveness)
```

---

## 🎯 Agent20_InterventionPreparation Triples

### 1. 개입 준비 관련 Triples

```
(Student, requires, Intervention)
(Intervention, requires, InterventionPreparation)
(InterventionPreparation, isSubtypeOf, Preparation)
(InterventionPreparation, requires, RiskAnalysis)
(InterventionPreparation, requires, StrategyDesign)
(InterventionPreparation, requires, ResourcePreparation)
(InterventionPreparation, affects, InterventionEffectiveness)
(InterventionPreparation, requires, ComprehensiveAnalysisData)
(InterventionPreparation, requires, Priority)
(InterventionPreparation, requires, Constraints)
```

### 2. 개입 위치 선택 관련 Triples

```
(InterventionPreparation, selects, InterventionLocation)
(InterventionLocation, hasType, "InterfaceLocation")
(InterventionLocation, hasType, "DataTrigger")
(InterfaceLocation, hasOption, "MyStudyRoom")
(InterfaceLocation, hasOption, "TodayActivity")
(InterfaceLocation, hasOption, "Schedule")
(InterfaceLocation, hasOption, "GoalSetting")
(InterfaceLocation, hasOption, "MessageBox")
(InterfaceLocation, hasOption, "MathDiary")
(InterfaceLocation, hasOption, "UnitIndex")
(InterfaceLocation, hasOption, "SolutionNote")
(InterfaceLocation, hasOption, "EvaluationPreparation")
(InterfaceLocation, hasOption, "DescriptiveAssessment")
(InterfaceLocation, hasOption, "ConceptMission")
(InterfaceLocation, hasOption, "AdvancedMission")
(InterfaceLocation, hasOption, "SchoolExamMission")
(InterfaceLocation, hasOption, "CSATMission")
(InterfaceLocation, hasOption, "MetacognitionHome")
(InterfaceLocation, hasOption, "QuizStart")
(InterfaceLocation, hasOption, "QuizEnd")
(DataTrigger, hasOption, "CalmnessDrop")
(DataTrigger, hasOption, "WritingDelay")
(DataTrigger, hasOption, "DelayedSolution")
(DataTrigger, hasOption, "PomodoroNotWritten")
(DataTrigger, hasOption, "LowScore")
(DataTrigger, hasOption, "NoRest")
(DataTrigger, hasOption, "TodayActivityRisk")
(DataTrigger, hasOption, "SolutionNoteAbnormal")
(DataTrigger, hasOption, "ConceptNoteAbnormal")
(DataTrigger, hasOption, "Lateness")
(DataTrigger, hasOption, "RestTimeExceeded")
(DataTrigger, hasOption, "PersonalRuleDeviation")
(DataTrigger, hasOption, "ReturnCheckPreparationInsufficient")
(DataTrigger, hasOption, "ErrorNoteMissing")
(DataTrigger, hasOption, "AssignmentNotSubmitted")
```

### 3. 개입 방식 관련 Triples

```
(InterventionPreparation, selects, InterventionMethod)
(InterventionMethod, hasType, "Notification")
(InterventionMethod, hasType, "Message")
(InterventionMethod, hasType, "Chat")
(InterventionMethod, hasType, "Call")
(Notification, displays, CustomIcon)
(Message, delivers, ImportantMessage)
(Chat, provides, RealTimeDialogue)
(Call, requires, ImmediateAttention)
```

### 4. 개입 준비 액션 관련 Triples

```
(InterventionPreparation, performs, PreparationAction)
(PreparationAction, includes, PrepareInterventionChecklist)
(PreparationAction, includes, PrepareResources)
(PreparationAction, includes, PrepareMessages)
(InterventionPreparation, performs, AssignmentAction)
(AssignmentAction, includes, AssignResponsiblePerson)
(AssignmentAction, includes, ScheduleIntervention)
(AssignmentAction, includes, SetTimeWindow)
```

### 5. 개입 계획 수립 관련 Triples

```
(InterventionPreparation, creates, InterventionPlan)
(InterventionPlan, includes, InterventionLocation)
(InterventionPlan, includes, InterventionMethod)
(InterventionPlan, includes, InteractionType)
(InterventionPlan, includes, InterventionContent)
(InterventionPlan, includes, ExpectedTiming)
(InterventionPlan, includes, TargetStudent)
(InterventionPlan, deliveredTo, Agent21)
```

### 6. 추천 이유 관련 Triples

```
(InterventionPreparation, generates, RecommendationReason)
(RecommendationReason, explains, LocationSelection)
(RecommendationReason, explains, MethodSelection)
(RecommendationReason, generatedBy, AI)
```

---

## 🎯 Agent21_InterventionExecution Triples

### 1. 개입 실행 관련 Triples

```
(InterventionPreparation, leadsTo, InterventionExecution)
(InterventionExecution, isSubtypeOf, Execution)
(InterventionExecution, requires, StrategyExecution)
(InterventionExecution, requires, Monitoring)
(InterventionExecution, requires, Adjustment)
(InterventionExecution, leadsTo, Outcome)
(Outcome, affects, StudentState)
(InterventionExecution, receives, InterventionPlan)
(InterventionExecution, manages, PersonalInterventionList)
(InterventionExecution, recalculates, Priority)
(InterventionExecution, targets, OptimalExecutionTime)
```

### 2. 개입방법 수신 관련 Triples

```
(InterventionExecution, receives, InterventionMethod)
(InterventionMethod, receivedFrom, Agent20)
(InterventionMethod, includes, InterventionLocation)
(InterventionMethod, includes, InterventionMethod)
(InterventionMethod, includes, InteractionType)
(InterventionMethod, includes, InterventionContent)
(InterventionMethod, includes, ExpectedTiming)
(InterventionMethod, includes, TargetStudent)
(InterventionMethod, validatedFor, Metadata)
```

### 3. 개인 목록 관리 관련 Triples

```
(InterventionExecution, manages, PersonalInterventionList)
(PersonalInterventionList, includes, WaitingInterventions)
(PersonalInterventionList, includes, ExistingInterventions)
(PersonalInterventionList, adds, NewIntervention)
(NewIntervention, generates, InterventionID)
(NewIntervention, assigns, InitialPriorityScore)
(NewIntervention, sets, ScheduledExecutionTime)
(NewIntervention, setsStatus, "Waiting")
```

### 4. 우선순위 재조정 관련 Triples

```
(InterventionExecution, recalculates, Priority)
(Priority, calculatedBy, RuleBasedAlgorithm)
(Priority, considers, GoalContribution)
(Priority, considers, Urgency)
(Priority, considers, EffectivenessPrediction)
(Priority, integrates, ExistingList)
(Priority, integrates, NewIntervention)
(Priority, creates, UnifiedPriorityList)
```

### 5. 실행 시점 타게팅 관련 Triples

```
(InterventionExecution, targets, OptimalExecutionTime)
(OptimalExecutionTime, calculatedFrom, LearningPattern)
(OptimalExecutionTime, calculatedFrom, FocusTimeSlot)
(OptimalExecutionTime, calculatedFrom, ActivityState)
(OptimalExecutionTime, considers, StudentState)
(OptimalExecutionTime, considers, TimingRules)
```

### 6. 개입 실행 관련 Triples

```
(InterventionExecution, executes, Intervention)
(Intervention, executedAt, OptimalTime)
(Intervention, deliveredTo, Student)
(Intervention, monitoredFor, StudentResponse)
(Intervention, monitoredFor, PerformanceIndicator)
```

### 7. 결과 기록 관련 Triples

```
(InterventionExecution, records, ExecutionResult)
(ExecutionResult, includes, ExecutionResult)
(ExecutionResult, includes, StudentResponse)
(ExecutionResult, includes, PerformanceIndicator)
(ExecutionResult, storedIn, ExecutionHistory)
```

### 8. 데이터 트리거 모니터링 관련 Triples

```
(InterventionExecution, monitors, DataTrigger)
(DataTrigger, monitoredIn, RealTime)
(DataTrigger, triggers, InterventionExecution)
(DataTrigger, addsTo, ExecutionWaitingList)
(DataTrigger, executesAt, ImmediateOrOptimalTime)
```

### 9. 메시지 관리 관련 Triples

```
(InterventionExecution, manages, MessageVolume)
(MessageVolume, limitedBy, DailyMaximum)
(MessageVolume, limitedBy, HourlyMaximum)
(MessageVolume, excessiveWhen, OverLimit)
(MessageVolume, excessiveWhen, RequiresTeacherNotification)
(MessageVolume, excessiveWhen, RequiresDirectAdjustment)
```

### 10. 실행 히스토리 관련 Triples

```
(InterventionExecution, uses, ExecutionHistory)
(ExecutionHistory, includes, PastExecutionRecords)
(ExecutionHistory, includes, EffectivenessData)
(ExecutionHistory, includes, StudentResponsePattern)
(ExecutionHistory, analyzedFor, PatternAnalysis)
```

## 🎯 Agent22_ModuleImprovement Triples

### 1. 모듈 개선 관련 Triples

```
(System, hasModule, Module)
(Module, improvedBy, ModuleImprovement)
(ModuleImprovement, isSubtypeOf, Improvement)
(ModuleImprovement, requires, ExecutionDataCollection)
(ModuleImprovement, requires, VulnerabilityAnalysis)
(ModuleImprovement, requires, SelfUpgradeIdeaGeneration)
(ModuleImprovement, generates, ImprovementReport)
(ModuleImprovement, generates, ThreeFileSystemDocument)
```

### 2. 실행 데이터 수집 관련 Triples

```
(ModuleImprovement, collects, ExecutionData)
(ExecutionData, collectedFrom, InvolvedAgents)
(ExecutionData, includes, ExecutionTime)
(ExecutionData, includes, ExecutionStatus)
(ExecutionData, includes, InputData)
(ExecutionData, includes, OutputData)
(ExecutionData, includes, ExecutedRules)
(ExecutionData, includes, ErrorOccurrence)
(ExecutionData, includes, ErrorContent)
(ExecutionData, includes, IntermediateCalculation)
(ExecutionData, validatedFor, Completeness)
(ExecutionData, validatedFor, Format)
(ExecutionData, validatedFor, Consistency)
```

### 3. 취약점 분석 관련 Triples

```
(ModuleImprovement, analyzes, Vulnerability)
(Vulnerability, hasType, "RuleVulnerability")
(Vulnerability, hasType, "AnalysisWeakness")
(RuleVulnerability, analyzedBy, RuleLogicVerification)
(RuleVulnerability, analyzedBy, RuleExecutionRecordAnalysis)
(RuleVulnerability, classifiedAs, LogicalError)
(RuleVulnerability, classifiedAs, IncompleteCoverage)
(RuleVulnerability, classifiedAs, PerformanceIssue)
(RuleVulnerability, classifiedAs, MaintainabilityProblem)
(RuleVulnerability, classifiedAs, ConsistencyIssue)
(AnalysisWeakness, analyzedBy, AnalysisAccuracyVerification)
(AnalysisWeakness, analyzedBy, AnalysisConsistencyVerification)
(AnalysisWeakness, evaluatedBy, AnalysisQuality)
```

### 4. 룰 취약점 분석 관련 Triples

```
(RuleVulnerability, analyzedBy, RuleLogicVerification)
(RuleLogicVerification, analyzes, RulesYAML)
(RuleLogicVerification, verifies, ConditionBranchCompleteness)
(RuleLogicVerification, checks, RuleDependency)
(RuleLogicVerification, checks, RuleConflict)
(RuleLogicVerification, checks, EdgeCaseHandling)
(RuleExecutionRecordAnalysis, compares, ExecutedRules)
(RuleExecutionRecordAnalysis, compares, ExpectedRules)
(RuleExecutionRecordAnalysis, identifies, DeadCode)
(RuleExecutionRecordAnalysis, analyzes, ExecutionFrequency)
(RuleExecutionRecordAnalysis, analyzes, ExecutionResultPredictability)
```

### 5. 분석 내용 약점 분석 관련 Triples

```
(AnalysisWeakness, analyzedBy, AnalysisAccuracyVerification)
(AnalysisAccuracyVerification, compares, AnalysisResult)
(AnalysisAccuracyVerification, compares, ActualStudentState)
(AnalysisAccuracyVerification, calculates, PredictionAccuracy)
(AnalysisAccuracyVerification, analyzes, Bias)
(AnalysisConsistencyVerification, compares, MultipleAgentAnalysis)
(AnalysisConsistencyVerification, compares, TimeBasedAnalysis)
(AnalysisConsistencyVerification, evaluates, AnalysisBasisClarity)
(AnalysisQuality, evaluates, Accuracy)
(AnalysisQuality, evaluates, Reliability)
(AnalysisQuality, evaluates, Consistency)
(AnalysisQuality, evaluates, Interpretability)
(AnalysisQuality, evaluates, Usefulness)
```

### 6. 자가 업그레이드 아이디어 생성 관련 Triples

```
(ModuleImprovement, generates, SelfUpgradeIdea)
(SelfUpgradeIdea, hasType, "RuleImprovementIdea")
(SelfUpgradeIdea, hasType, "AnalysisImprovementIdea")
(SelfUpgradeIdea, hasType, "SystemLevelImprovementIdea")
(SelfUpgradeIdea, prioritizedBy, ImpactEffortMatrix)
(RuleImprovementIdea, includes, LogicalErrorCorrection)
(RuleImprovementIdea, includes, PerformanceOptimization)
(RuleImprovementIdea, includes, MaintainabilityImprovement)
(AnalysisImprovementIdea, includes, AccuracyEnhancement)
(AnalysisImprovementIdea, includes, ConsistencyEnhancement)
(AnalysisImprovementIdea, includes, InterpretabilityEnhancement)
(SystemLevelImprovementIdea, includes, PerformanceImprovement)
(SystemLevelImprovementIdea, includes, StabilityImprovement)
(SystemLevelImprovementIdea, includes, ScalabilityImprovement)
```

### 7. 영향도-노력도 매트릭스 관련 Triples

```
(ImpactEffortMatrix, isSubtypeOf, Matrix)
(ImpactEffortMatrix, evaluates, Impact)
(ImpactEffortMatrix, evaluates, Effort)
(Impact, hasLevel, "High")
(Impact, hasLevel, "Medium")
(Impact, hasLevel, "Low")
(Effort, hasLevel, "Low")
(Effort, hasLevel, "Medium")
(Effort, hasLevel, "High")
(HighImpactLowEffort, prioritizedAs, "ImmediateExecution")
(HighImpactMediumEffort, prioritizedAs, "ShortTermPlan")
(MediumImpact, prioritizedAs, "MediumTermPlan")
(HighEffort, prioritizedAs, "LongTermPlan")
```

### 8. 개선 리포트 생성 관련 Triples

```
(ModuleImprovement, generates, ImprovementReport)
(ImprovementReport, includes, ExecutionSummary)
(ImprovementReport, includes, CollectedDataSummary)
(ImprovementReport, includes, VulnerabilityAnalysisResult)
(ImprovementReport, includes, ImprovementProposal)
(ImprovementReport, includes, DetailedAnalysis)
(ImprovementReport, includes, NextSteps)
(ImprovementReport, deliveredTo, Developer)
```

### 9. 3 File System 문서 생성 관련 Triples

```
(ModuleImprovement, generates, ThreeFileSystemDocument)
(ThreeFileSystemDocument, hasFile, "File1_ProblemDefinition")
(ThreeFileSystemDocument, hasFile, "File2_ImprovementDesign")
(ThreeFileSystemDocument, hasFile, "File3_ExecutionPlan")
(File1_ProblemDefinition, defines, VulnerabilityDefinition)
(File1_ProblemDefinition, analyzes, RootCause)
(File1_ProblemDefinition, evaluates, ImpactScope)
(File1_ProblemDefinition, evaluates, Severity)
(File1_ProblemDefinition, identifies, RelatedCodeLocation)
(File2_ImprovementDesign, designs, ImprovementPlan)
(File2_ImprovementDesign, structures, ChangedCodeStructure)
(File2_ImprovementDesign, defines, TestStrategy)
(File2_ImprovementDesign, defines, SuccessCriteria)
(File2_ImprovementDesign, defines, VerificationMethod)
(File3_ExecutionPlan, plans, StepwiseExecution)
(File3_ExecutionPlan, analyzes, Risk)
(File3_ExecutionPlan, plans, RiskResponse)
(File3_ExecutionPlan, plans, RollbackPlan)
(File3_ExecutionPlan, defines, VerificationChecklist)
```

### 10. AI 코드 업그레이드 프로세스 관련 Triples

```
(ThreeFileSystemDocument, reviewedBy, AI)
(AI, validates, DocumentConsistency)
(AI, validates, Executability)
(AI, generates, CodeModificationProposal)
(AI, reviews, CodeQuality)
(AI, reviews, TestCoverage)
(AI, executes, AutomatedTestSuite)
(AI, verifies, PerformanceMetrics)
(AI, approves, Deployment)
(AI, monitors, PostDeployment)
(AI, generates, ResultReport)
```

### 11. 개발자 검토 모드 관련 Triples

```
(ThreeFileSystemDocument, submittedTo, Developer)
(Developer, reviews, Report)
(Developer, approves, Execution)
(Developer, modifies, Code)
(Developer, tests, Code)
(Developer, deploys, Code)
```

### 12. 에이전트 연계 관련 Triples

```
(ModuleImprovement, connectsTo, AllAgents)
(AllAgents, includes, Agent01)
(AllAgents, includes, Agent02)
(AllAgents, includes, Agent03)
(AllAgents, includes, Agent04)
(AllAgents, includes, Agent05)
(AllAgents, includes, Agent06)
(AllAgents, includes, Agent07)
(AllAgents, includes, Agent08)
(AllAgents, includes, Agent09)
(AllAgents, includes, Agent10)
(AllAgents, includes, Agent11)
(AllAgents, includes, Agent12)
(AllAgents, includes, Agent13)
(AllAgents, includes, Agent14)
(AllAgents, includes, Agent15)
(AllAgents, includes, Agent16)
(AllAgents, includes, Agent17)
(AllAgents, includes, Agent18)
(AllAgents, includes, Agent19)
(AllAgents, includes, Agent20)
(AllAgents, includes, Agent21)
(ModuleImprovement, receives, ExecutionData)
(ModuleImprovement, receives, RuleFiles)
(ModuleImprovement, receives, AnalysisResults)
(ModuleImprovement, provides, ImprovementProposal)
(ModuleImprovement, provides, UpgradeDocument)
```

### 13. 성능 분석 관련 Triples

```
(ModuleImprovement, analyzes, Performance)
(Performance, includes, ExecutionTime)
(Performance, includes, ResourceUsage)
(Performance, includes, SuccessRate)
(Performance, analyzedBy, PerformanceAnalysis)
(PerformanceAnalysis, analyzes, AgentExecutionTime)
(PerformanceAnalysis, analyzes, ResourceUsage)
(PerformanceAnalysis, analyzes, SuccessRate)
(PerformanceAnalysis, analyzes, OverallSystemPerformance)
(PerformanceAnalysis, analyzes, AgentCommunication)
(PerformanceAnalysis, analyzes, ResourceUsagePattern)
```

### 14. 업데이트 주기 관련 Triples

```
(ModuleImprovement, executedAfter, WorkflowExecution)
(ModuleImprovement, executedWeekly, WeeklyPerformanceTrend)
(ModuleImprovement, executedMonthly, MonthlyComprehensiveAnalysis)
(ModuleImprovement, executedOnDemand, ManualRequest)
```

---

## 📊 Triple 통계

- **Agent01**: 약 150개 triple
- **Agent02**: 약 30개 triple
- **Agent03**: 약 40개 triple
- **Agent04**: 약 60개 triple
- **Agent05**: 약 30개 triple
- **Agent06**: 약 25개 triple
- **Agent07**: 약 15개 triple
- **Agent08**: 약 25개 triple
- **Agent09**: 약 35개 triple
- **Agent10**: 약 30개 triple
- **Agent11**: 약 30개 triple
- **Agent12**: 약 30개 triple
- **Agent13**: 약 50개 triple
- **Agent14**: 약 45개 triple
- **Agent15**: 약 40개 triple
- **Agent16**: 약 35개 triple
- **Agent17**: 약 50개 triple
- **Agent18**: 약 40개 triple
- **Agent19**: 약 40개 triple
- **Agent20**: 약 35개 triple
- **Agent21**: 약 50개 triple
- **Agent22**: 약 70개 triple
- **총 Triple 수**: 약 950개

---

## 🔗 Cross-Agent Triple 관계망

### 핵심 연결 경로

1. **학생 → 페르소나 → 활동**
   ```
   Student → hasPersona → Persona → affects → LearningActivity
   ```

2. **학생 → 목표 → 계획 → 실행**
   ```
   Student → hasGoal → Goal → hasPlan → Plan → leadsTo → Execution
   ```

3. **학생 → 감정 → 피드백 → 행동변화**
   ```
   Student → hasEmotion → EmotionPattern → leadsTo → FeedbackCommand → leadsTo → BehaviorChange
   ```

4. **선생님 → 피드백 → 상호작용 → 학생반응**
   ```
   Teacher → provides → TeacherFeedback → generates → InteractionContent → affects → StudentResponse
   ```

5. **활동 → 페르소나 → 시그너처 루틴**
   ```
   LearningActivity → affects → Persona → leadsTo → SignatureRoutine
   ```

---

## ✅ 검증 체크리스트

- [x] 주어 선택 기준 적용 (행동 주체성 우선)
- [x] 서술어 계층 분류 (Cognitive/Affective/Behavioral/Meta)
- [x] 방향성 명확성 (단방향/양방향)
- [x] 의미 일관성 (동일 서술어는 동일 의미)
- [x] 추론 가능성 (새로운 triple 유도 가능)
- [x] 도메인 적합성 (수학 학습 온톨로지)
- [x] Cross-Agent 관계 명확화

---

## 📝 다음 단계

1. 생성된 triple들의 일관성 검증
2. 온톨로지 파일로 변환 (RDF/OWL 형식)
3. SPARQL 쿼리 테스트
4. Triple 간 추론 규칙 정의
5. 온톨로지 검증 및 최적화

