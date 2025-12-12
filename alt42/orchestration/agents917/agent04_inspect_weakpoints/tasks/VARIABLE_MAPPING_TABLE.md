# Agent04 변수 매핑 테이블

**생성일**: 2025-01-27  
**목적**: Rules.yaml의 변수와 온톨로지 프로퍼티 간 매핑 정의

---

## 변수 매핑 규칙

1. **변수 형식**: `{variable_name}` 또는 `{{variable_name}}`
2. **온톨로지 프로퍼티**: `mk-a04:hasPropertyName`
3. **타입 변환**: 필요시 자동 변환 (문자열 → 열거형 등)

---

## 개념이해 (Concept Understanding) 활동 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{activity_type}` | `mk-a04:hasActivityType` | `mk-a04:ActivityType` | 활동 유형 (예: concept_understanding) |
| `{activity_category}` | `mk-a04:hasActivityCategory` | `xsd:string` | 활동 카테고리 (예: 개념이해) |
| `{pause_frequency}` | `mk-a04:hasPauseFrequency` | `xsd:integer` | 멈춤 빈도 (회수) |
| `{pause_stage}` | `mk-a04:hasPauseStage` | `xsd:string` | 멈춤 단계 (예: understanding, organizing, applying) |
| `{concept_stage}` | `mk-a04:hasConceptStage` | `xsd:string` | 개념 단계 |
| `{gaze_attention_score}` | `mk-a04:hasGazeAttentionScore` | `xsd:float` | 시선 집중도 점수 (0.0-1.0) |
| `{note_taking_pattern_change}` | `mk-a04:hasNoteTakingPatternChange` | `xsd:boolean` | 필기 패턴 변화 여부 |
| `{concept_confusion_detected}` | `mk-a04:hasConceptConfusionDetected` | `xsd:boolean` | 개념 혼동 탐지 여부 |
| `{confusion_type}` | `mk-a04:hasConfusionType` | `mk-a04:ConfusionType` | 혼동 유형 (예: definition_vs_example) |
| `{method_persona_match_score}` | `mk-a04:hasMethodPersonaMatchScore` | `xsd:float` | 방법-페르소나 적합성 점수 (0.0-1.0) |
| `{persona_type}` | `mk-a04:hasPersonaType` | `xsd:string` | 페르소나 유형 |
| `{current_method}` | `mk-a04:hasCurrentMethod` | `xsd:string` | 현재 학습 방법 (예: TTS, note_taking) |
| `{boredom_detected}` | `mk-a04:hasBoredomDetected` | `xsd:boolean` | 지루함 탐지 여부 |
| `{attention_drop_time}` | `mk-a04:hasAttentionDropTime` | `xsd:integer` | 주의 이탈 시간 (초) |
| `{emotion_state}` | `mk-a04:hasEmotionState` | `mk-a04:EmotionState` | 감정 상태 (예: 지루함, 권태) |
| `{attention_score}` | `mk-a04:hasAttentionScore` | `xsd:float` | 주의집중도 점수 (0.0-1.0) |
| `{learning_method}` | `mk-a04:hasLearningMethod` | `mk-a04:LearningMethod` | 학습 방법 (예: TTS) |

---

## 유형학습 (Type Learning) 활동 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{type_learning_stage}` | `mk-a04:hasTypeLearningStage` | `xsd:string` | 유형학습 단계 |
| `{pattern_recognition_score}` | `mk-a04:hasPatternRecognitionScore` | `xsd:float` | 패턴 인식 점수 |

---

## 문제풀이 (Problem Solving) 활동 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{problem_solving_stage}` | `mk-a04:hasProblemSolvingStage` | `xsd:string` | 문제풀이 단계 |
| `{solution_attempt_count}` | `mk-a04:hasSolutionAttemptCount` | `xsd:integer` | 해결 시도 횟수 |
| `{error_type}` | `mk-a04:hasErrorType` | `xsd:string` | 오류 유형 |

---

## 오답노트 (Mistake Note) 활동 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{mistake_pattern}` | `mk-a04:hasMistakePattern` | `xsd:string` | 오류 패턴 |
| `{mistake_frequency}` | `mk-a04:hasMistakeFrequency` | `xsd:integer` | 오류 빈도 |

---

## 질의응답 (QnA) 활동 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{question_type}` | `mk-a04:hasQuestionType` | `xsd:string` | 질문 유형 |
| `{question_frequency}` | `mk-a04:hasQuestionFrequency` | `xsd:integer` | 질문 빈도 |

---

## 복습활동 (Review Activity) 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{review_effectiveness}` | `mk-a04:hasReviewEffectiveness` | `xsd:float` | 복습 효과성 점수 |

---

## 포모도르 (Pomodoro) 활동 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{concentration_score}` | `mk-a04:hasConcentrationScore` | `xsd:float` | 집중도 점수 |
| `{session_duration}` | `mk-a04:hasSessionDuration` | `xsd:integer` | 세션 지속 시간 (분) |

---

## 공통 변수 매핑

| Rules.yaml 변수 | 온톨로지 프로퍼티 | 타입 | 설명 |
|----------------|-----------------|------|------|
| `{student_id}` | `mk-a04:hasStudentId` | `xsd:integer` | 학생 ID |
| `{timestamp}` | `mk-a04:hasTimestamp` | `xsd:integer` | 타임스탬프 (Unix timestamp) |

---

## 타입 변환 규칙

### 문자열 → 열거형
- `{activity_type}`: `"concept_understanding"` → `mk-a04:ConceptUnderstanding`
- `{confusion_type}`: `"definition_vs_example"` → `mk-a04:DefinitionVsExample`

### 숫자 → 열거형
- `{pause_frequency}`: `5` → 심각도 추론 시 `mk-a04:High` (5 이상)

### 불리언 → 온톨로지 값
- `{concept_confusion_detected}`: `true` → `mk-a04:hasConceptConfusionDetected = true`

---

## 사용 예시

### 예시 1: 개념이해 취약점 탐지
```yaml
action:
  - "create_instance: 'mk-a04:WeakpointDetectionContext'"
  - "set_property: ('mk-a04:hasActivityType', '{activity_type}')"
  - "set_property: ('mk-a04:hasPauseFrequency', '{pause_frequency}')"
```

**변수 치환**:
- `{activity_type}` = `"concept_understanding"` → `mk-a04:ConceptUnderstanding`
- `{pause_frequency}` = `5` → `5`

### 예시 2: TTS 주의집중 패턴 분석
```yaml
action:
  - "create_instance: 'mk-a04:ActivityAnalysisContext'"
  - "set_property: ('mk-a04:hasGazeAttentionScore', '{gaze_attention_score}')"
  - "set_property: ('mk-a04:hasNoteTakingPatternChange', '{note_taking_pattern_change}')"
```

**변수 치환**:
- `{gaze_attention_score}` = `0.6` → `0.6`
- `{note_taking_pattern_change}` = `true` → `true`

---

**작성일**: 2025-01-27  
**버전**: 1.0

