# Agent 15 - Ontology 추천 영역

이 문서는 Agent 15 (문제재정의)에서 사용해야 할 ontology 영역을 식별하고 추천합니다.
Agent 03의 구조를 참고하여 작성되었습니다.

## 📋 현재 구현된 Ontology

### 포괄형 질문 1: 반복 패턴 기반 문제 재정의
1. **ProblemRedefinition** (핵심)
   - 문제 재정의(근본 원인/진단코드)를 온톨로지로 표현
   - 활용: 모든 문제 재정의의 기반

2. **MathUnitVulnerability**
   - 수학 단원별 취약점(단원명, 취약 유형, 빈도, 심각도)
   - 활용: 단원별 문제 재정의

3. **MathErrorTypeClassification**
   - 수학 오류 유형 분류(계산 실수/개념 오류/응용 실패)
   - 활용: 오류 유형별 맞춤형 대응 전략 수립

4. **StudentLevelDifferentiation**
   - 학생 수준별 차별화(하위권/중위권/상위권)
   - 활용: 수준별 문제 재정의

5. **AcademyContext**
   - 학원 맥락 정보(수업 이해도, 과제 완료율, 진도)
   - 활용: 학원 특화 문제 재정의

6. **RootCauseAnalysis**
   - 근본 원인 분석(인지·정서·습관·환경 요인)
   - 활용: 문제 재정의의 핵심 근거

### 포괄형 질문 2: 루틴 붕괴 원인 진단
1. **RoutineCollapseRecovery** (핵심)
   - 루틴 붕괴 원인 진단 및 회복
   - 활용: 루틴 회복 전략 수립

2. **MathStudyTimeAllocation**
   - 수학 학습 시간 배분(단원별 학습 시간)
   - 활용: 시간 배분 문제 재정의

3. **TimeManagementFailure**
   - 시간관리 실패(계획-실행 시간 차이)
   - 활용: 실행 습관 재설계

4. **MathLearningStyleMismatch**
   - 수학 학습 스타일 불일치(설정 스타일 vs 실제 행동)
   - 활용: 전략 정렬

5. **StandardCodeClassification**
   - 표준코드 분류(전략 불일치/시간관리/정서 리듬)
   - 활용: 루틴 회복 전략 수립

### 포괄형 질문 3: 구조적 문제 진단
1. **StructuralProblemDiagnosis** (핵심)
   - 구조적 문제 진단
   - 활용: 구조적 문제 재정의

2. **ComprehensiveMathRedefinition**
   - 수학 특화 종합 문제 재정의(모든 데이터 통합)
   - 활용: 현직 선생님 수준의 통합 분석

3. **CollaborativeProblemSolving**
   - AI·교사·학생 협력 문제 해결(핵심 진단코드 3개)
   - 활용: 협력 해결 방안 수립

4. **PrerequisiteUnitAnalysis**
   - 선행 단원 분석(단원 선후관계 확인)
   - 활용: 개념 이해 부진 문제 재정의

5. **MathAnxietyRedefinition**
   - 수학 불안 재정의(불안 완화 전략)
   - 활용: 정서 문제 재정의

6. **MathRecoveryFailure**
   - 수학 학습 회복 실패(회복 루틴 재설계)
   - 활용: 회복 전략 수립

---

## 🎯 추가 추천 Ontology 영역

### 1. **DiagnosticCodeMapping** (우선순위: 높음)
- **목적**: 표준 진단코드(C01~C09)와 문제 재정의 결과를 매핑
- **구조**:
  - 진단코드 (C01: 개념 이해 부족, C02: 계산 실수, C03: 학습 스타일 불일치, C04: 동기 저하, C05: 시간 관리 실패, C06: 정서 문제, C07: 단원별 취약점, C08: 루틴 불안정, C09: 회복 실패)
  - 문제 재정의 결과
  - 우선순위 (1~3)
  - 개선 시나리오
- **활용**: rules.yaml의 COMP_R2 룰과 연계하여 표준진단 항목 매핑에 활용
- **관련 룰**: COMP_R2_standard_diagnosis_mapping

### 2. **ImprovementScenarioGeneration** (우선순위: 높음)
- **목적**: 실행 가능한 개선 시나리오 자동 생성
- **구조**:
  - 문제 재정의 결과
  - 학생 수준
  - 단원별 취약점
  - 오류 유형
  - 개선 시나리오 (단계별 실행 계획)
- **활용**: rules.yaml의 COMP_R3 룰과 연계하여 학생 맞춤형 개선 아이디어 생성에 활용
- **관련 룰**: COMP_R3_customized_improvement_ideas

### 3. **MonitoringVariableTracking** (우선순위: 중간)
- **목적**: 각 진단코드별 모니터링 변수 추적
- **구조**:
  - 진단코드
  - 모니터링 변수 목록
  - 예상 리스크
  - 개입 효과 측정 지표
  - 추적 주기
- **활용**: 구조적 문제 해결 후 지속적인 모니터링에 활용
- **관련 데이터**: agent_data.agent14_data.monitoring_data

### 4. **MathUnitRelations** (우선순위: 높음)
- **목적**: 수학 단원 간 선후관계 및 연관성 표현
- **구조**:
  - 단원명
  - 선행 단원 목록
  - 후속 단원 목록
  - 관련 단원 목록
  - 개념 연계도
- **활용**: 선행 단원 미완료 확인 및 단원별 취약점 분석에 활용
- **관련 룰**: S7_R1_unit_prerequisite_based_redefinition, S3_R3_unit_specific_error_redefinition
- **관련 데이터**: math_unit_relations.yaml

### 5. **InteractionToneDecision** (우선순위: 중간)
- **목적**: 문제 유형별 적절한 상호작용 톤 결정
- **구조**:
  - 문제 유형
  - 감정 상태
  - 학생 수준
  - 추천 톤 (격려형/코치형/공감형)
  - 개입 시점
- **활용**: Agent 16과 연계하여 상호작용 준비에 활용
- **관련 데이터**: agent_data.agent05_data.emotion_state, agent_data.agent16_data.interaction_preparation

### 6. **PatternRecognition** (우선순위: 높음)
- **목적**: 반복 패턴(오답, 진도 지연, 감정 변동) 자동 인식
- **구조**:
  - 패턴 유형 (오답 패턴, 진도 지연 패턴, 감정 변동 패턴)
  - 패턴 빈도
  - 패턴 심각도
  - 패턴 발생 시점
  - 패턴 연관성
- **활용**: 문제 재정의의 초기 단계에서 패턴 자동 감지에 활용
- **관련 데이터**: repeated_error_pattern, progress_delay_pattern, emotion_fluctuation_pattern

### 7. **RiskAssessment** (우선순위: 중간)
- **목적**: 각 진단코드별 예상 리스크 평가
- **구조**:
  - 진단코드
  - 리스크 유형 (단기/중기/장기)
  - 리스크 심각도
  - 리스크 발생 확률
  - 리스크 완화 전략
- **활용**: 구조적 문제 해결 시 리스크 예측 및 대응에 활용

### 8. **TeacherFeedbackIntegration** (우선순위: 높음)
- **목적**: 교사 피드백을 수학 특화 문제로 통합
- **구조**:
  - 교사 피드백 내용
  - 피드백 유형 (기본기 부족, 집중력 저하, 패턴 반복)
  - 관련 활동 데이터
  - 문제 재정의 결과
  - 진단코드 매핑
- **활용**: rules.yaml의 S8_R1 룰과 연계하여 교사 피드백 기반 문제 재정의에 활용
- **관련 룰**: S8_R1_teacher_feedback_math_specific_redefinition
- **관련 데이터**: agent_data.agent06_data.teacher_feedback

---

## 🔗 Agent 간 Ontology 연계

### Agent 03 (목표분석)과의 연계
- **GoalPlanAlignment**: 목표-계획 불일치가 문제 재정의의 원인일 수 있음
- **TimeResourceManagement**: 시간 자원 관리 실패가 루틴 붕괴의 원인일 수 있음
- **ResiliencePattern**: 회복탄력성 패턴이 문제 재정의의 핵심 근거가 될 수 있음

### Agent 04 (취약점검사)와의 연계
- **ConceptWeakpoint**: 개념 이해 취약점이 문제 재정의의 핵심 근거
- **TypeLearningRoutine**: 유형학습 루틴 문제가 루틴 붕괴의 원인일 수 있음

### Agent 05 (학습감정)와의 연계
- **EmotionFlowPattern**: 감정 흐름 패턴이 정서 문제 재정의의 핵심 근거
- **MathAnxietyRedefinition**: 수학 불안이 동기 저하의 원인일 수 있음

### Agent 06 (교사피드백)와의 연계
- **TeacherFeedbackIntegration**: 교사 피드백이 문제 재정의의 핵심 입력

### Agent 07 (상호작용타게팅)과의 연계
- **InteractionToneDecision**: 상호작용 톤 결정이 문제 재정의 후 개입 전략에 활용

### Agent 10 (개념노트)와의 연계
- **ConceptUnderstanding**: 개념 이해도가 문제 재정의의 핵심 근거

### Agent 11 (문제노트)와의 연계
- **ErrorPattern**: 오답 패턴이 문제 재정의의 핵심 근거

### Agent 12 (휴식루틴)와의 연계
- **RecoveryPattern**: 회복 패턴이 회복 실패 문제 재정의의 핵심 근거

### Agent 13 (학습이탈)과의 연계
- **DropoutPattern**: 학습이탈 패턴이 문제 재정의의 핵심 근거

### Agent 14 (현재위치)와의 연계
- **CurrentPosition**: 현재 학습 위치가 문제 재정의의 맥락 정보

---

## 📊 구현 우선순위

### Phase 1 (즉시 구현 필요)
1. **DiagnosticCodeMapping** - 표준 진단코드 매핑의 핵심
2. **MathUnitRelations** - 단원별 취약점 분석의 기반
3. **PatternRecognition** - 문제 재정의의 초기 단계

### Phase 2 (단기 구현)
4. **ImprovementScenarioGeneration** - 개선 시나리오 자동 생성
5. **TeacherFeedbackIntegration** - 교사 피드백 통합
6. **RiskAssessment** - 리스크 평가

### Phase 3 (중기 구현)
7. **MonitoringVariableTracking** - 모니터링 변수 추적
8. **InteractionToneDecision** - 상호작용 톤 결정

---

## 🎓 참고 사항

- 모든 ontology는 수학 학습 특화 분석을 지원해야 함
- 학원 맥락 정보를 반영할 수 있어야 함
- 학생 수준별 차별화를 지원해야 함
- Agent 간 데이터 연계를 고려하여 설계해야 함
- rules.yaml의 룰과 직접 연계되어야 함

