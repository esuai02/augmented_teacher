선생님 피드백 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **선생님의 피드백과 의도 도출, 상호작용 컨텐츠 생성에 필요한 데이터**가 필요합니다. 아래는 **Agent 06 - Teacher Feedback** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🎯 1. 핵심 의도 및 페르소나 톤 (8)

1. 핵심 의도 (CoreIntention: 성장/도전/안정/회복)
2. 최근 상호작용 (RecentInteractions)
3. 교사 메모 (TeacherMemo)
4. 교사 피드백 스타일 (TeacherFeedbackStyle)
5. 페르소나 톤 (PersonaTone)
6. 위험 신호 (RiskSignals)
7. 피드백 템플릿 (FeedbackTemplates)
8. 대체 스크립트 (AlternativeScripts)

---

## 📝 2. 상호작용 이력 및 패턴 (6)

9. 상호작용 이력 (InteractionHistory)
10. 피드백 수용률 (FeedbackAcceptanceRate)
11. 상호작용 톤 선호도 (InteractionTonePreference)
12. 귀가검사 데이터 (DepartureCheckData)
13. 피드백 방식 (칭찬, 지적 등, FeedbackMethod)
14. 상호작용 효과성 (InteractionEffectiveness)

---

## 🔄 3. 학생 변화 신호 (6)

15. 집중도 변화 (ConcentrationChange)
16. 감정 변화 (EmotionChange)
17. 진도 변화 (ProgressChange)
18. 다가오는 문맥 (UpcomingContext: 시험·모의·진학 상담)
19. 변화 신호 분석 (ChangeSignalAnalysis)
20. 맥락 해석 (ContextInterpretation)

---

## 📋 4. 3단계 개입 시나리오 (8)

21. 오프닝 전략 (OpeningStrategy)
22. 코칭 전략 (CoachingStrategy)
23. 핵심 메시지 (CoreMessage)
24. 톤 스위치 규칙 (ToneSwitchRules)
25. 클로징 전략 (ClosingStrategy)
26. 과제 할당 (TaskAssignment)
27. 리마인드 카드 (ReminderCard)
28. 보호자 안내 문구 (ParentCommunication)

---

## 📊 5. 정보 수집 및 완전성 (8)

29. 누락 정보 (MissingInfo)
30. 누락 정보 우선순위 (MissingInfoPriority)
31. 수집 메시지 템플릿 (CollectionMessageTemplate)
32. 정보 수집 전략 (InfoCollectionStrategy)
33. 자동 업데이트 규칙 (AutoUpdateRules)
34. 전/후 비교 리포트 (BeforeAfterComparison)
35. 정보 완전성 리포트 (InfoCompletenessReport)
36. 정보 수집 절차 (InfoCollectionProcedure)

---

---

## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 06의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 A: 핵심 의도 및 페르소나 톤 도출
- **핵심 온톨로지**: `TeacherPersonaTone`, `CoreIntention`
- **데이터 소스 → 온톨로지 매핑**:
  - `recent_interactions` → `RecentInteractions`
  - `teacher_memo` → `TeacherMemo`
  - `departure_check_data` → `DepartureCheckData` (Agent 04)
  - `core_intention` → `CoreIntention`
  - `teacher_feedback_style` → `TeacherFeedbackStyle`
  - `interaction_tone_preference` → `InteractionTonePreference` (Agent 05)
  - `feedback_acceptance_rate` → `FeedbackAcceptanceRate`
  - `persona_tone` → `PersonaTone`
  - `risk_signals` → `RiskSignals`
  - `feedback_templates` → `FeedbackTemplates`
  - `interaction_history` → `InteractionHistory`
  - `alternative_scripts` → `AlternativeScripts`

### 포괄형 질문 B: 3단계 개입 시나리오 설계
- **핵심 온톨로지**: `InteractionScenario`, `ToneSwitchRules`
- **데이터 소스 → 온톨로지 매핑**:
  - `concentration_change` → `ConcentrationChange`
  - `emotion_change` → `EmotionChange` (Agent 05)
  - `progress_change` → `ProgressChange`
  - `upcoming_context` → `UpcomingContext` (Agent 02)
  - `opening_strategy` → `OpeningStrategy`
  - `core_message` → `CoreMessage`
  - `tone_switch_rules` → `ToneSwitchRules`
  - `coaching_strategy` → `CoachingStrategy`
  - `task_assignment` → `TaskAssignment`
  - `reminder_card` → `ReminderCard`
  - `parent_communication` → `ParentCommunication`
  - `closing_strategy` → `ClosingStrategy`

### 포괄형 질문 C: 누락 정보 자동 수집
- **핵심 온톨로지**: `InformationCollection`, `InfoCompleteness`
- **데이터 소스 → 온톨로지 매핑**:
  - `goals` → `Goal` (Agent 03)
  - `exam_schedule` → `ExamSchedule` (Agent 02)
  - `tone_preference` → `InteractionTonePreference` (Agent 05)
  - `recent_emotion_pattern` → `EmotionPattern` (Agent 05)
  - `missing_info` → `MissingInfo`
  - `missing_info_priority` → `MissingInfoPriority`
  - `collection_message_template` → `CollectionMessageTemplate`
  - `info_collection_strategy` → `InfoCollectionStrategy`
  - `auto_update_rules` → `AutoUpdateRules`
  - `before_after_comparison` → `BeforeAfterComparison`
  - `info_completeness_report` → `InfoCompletenessReport`

### 온톨로지 관계 (Triples)
- `TeacherFeedback` → requires → `TeacherIntention`, `TeacherPersona`, `RecentInteractions`, `TeacherMemo`
- `TeacherIntention` → isSubtypeOf → `CoreIntention`
- `TeacherPersona` → hasPart → `TeacherPersonaTone`
- `TeacherPersonaTone` → requires → `PersonaTone`, `TeacherFeedbackStyle`, `InteractionTonePreference`
- `InteractionScenario` → hasPart → `OpeningStrategy`, `CoachingStrategy`, `ClosingStrategy`
- `InformationCollection` → requires → `MissingInfo`, `MissingInfoPriority`, `CollectionMessageTemplate`
- `InfoCompleteness` → generates → `InfoCompletenessReport`

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 목표 관련 데이터는 Agent 03에서 관리됩니다.
- 시험일정 관련 데이터는 Agent 02에서 관리됩니다.
- 감정 관련 데이터는 Agent 05에서 관리됩니다.
- 귀가검사 관련 데이터는 Agent 04에서 관리됩니다.
- 모든 온톨로지 클래스는 `alphatutor_ontology.owl` 파일에 정의되어 있습니다.
