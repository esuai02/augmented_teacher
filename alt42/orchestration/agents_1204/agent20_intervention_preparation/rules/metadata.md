# Agent 20 - Intervention Preparation (개입 준비) 데이터 인덱스

개입 준비 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **개입 실행을 위한 계획 수립에 필요한 데이터**가 필요합니다. 아래는 **Agent 20 - Intervention Preparation** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 🎯 1. 개입 트리거 및 타이밍 데이터

### 트리거 유형
- **pomodoro_missing_count**: 포모도로 미작성 횟수
- **goal_completion_rate**: 목표 완료율 (0.0~1.0)
- **learning_delay_detected**: 학습 지연 감지 여부
- **data_based_trigger**: 데이터 기반 트리거 (자동 감지)
- **interface_based_trigger**: 인터페이스 기반 트리거 (사용자 요청)
- **trigger_type_effectiveness**: 트리거 유형 효과성
- **trigger_type**: 트리거 유형
- **trigger_severity**: 트리거 심각도
- **trigger_timing**: 트리거 발생 시점

### 타이밍 및 강도
- **calmness_change**: 침착도 변화량
- **routine_progress_rate**: 루틴 진행률 (0.0~1.0)
- **optimal_intervention_timing**: 최적 개입 타이밍 (즉시/지연/완화) - `InterventionTiming`
- **intervention_intensity**: 개입 강도 (즉시/지연/완화)
- **optimal_execution_timing**: 최적 실행 시점
- **intervention_delivery_time**: 개입 전달 시간
- **intervention_frequency**: 개입 빈도

---

## 📍 2. 개입 위치 및 환경 데이터

- **interface_location**: 인터페이스 위치 (대시보드/학습 화면/알림 등) - `InterventionLocation`
- **intervention_effectiveness_by_location**: 위치별 개입 효과성
- **intervention_location_recommendation**: 개입 위치 추천
- **academy_class_time**: 학원 수업 시간
- **focus_time_window**: 집중 시간대 - `FocusTimeWindow`
- **fatigue_level**: 피로도 수준 (0.0~1.0) - `FatigueLevel`
- **environment_data**: 환경 데이터
- **next_concentration_time_slot**: 다음 집중 시간대 시작 시점
- **current_time**: 현재 시간
- **time_until_concentration**: 집중 시간대까지 남은 시간

---

## 📊 3. 학습 흐름 상태 데이터

- **learning_flow_status**: 학습 흐름 상태 (정상/이탈/복귀) - `LearningFlowState`
- **routine_compliance_rate**: 루틴 준수율 (0.0~1.0)
- **goal_deviation**: 목표 이탈도
- **recovery_indicator**: 복귀 지표
- **learning_delay_pattern**: 학습 지연 패턴
- **intervention_need_prediction**: 개입 필요성 예측
- **calmness_trend**: 침착도 추이
- **learning_activity_data**: 학습 활동 데이터
- **intervention_effectiveness_by_time**: 시간대별 개입 효과성
- **time_window_analysis**: 시간 윈도우 분석

---

## 💬 4. 개입 방식 및 메시지 데이터

### 개입 방식
- **intervention_method_type**: 개입 방식 유형 (알림/메시지/채팅/호출) - `InterventionMethod`
- **intervention_method_effectiveness**: 개입 방식 효과성
- **delivery_method**: 전달 방식 (메시지·채팅·알림)
- **optimal_execution_method**: 최적 실행 방식
- **execution_method**: 실행 방식

### 메시지 톤 및 표현
- **message_tone_type**: 메시지 톤 유형 (격려형/코치형/공감형/경고형) - `MessageTone`
- **message_tone_effectiveness**: 메시지 톤 효과성
- **message_tone**: 메시지 톤
- **message_length**: 메시지 길이
- **next_intervention_tone**: 다음 개입 톤
- **visual_expression_effectiveness**: 시각적 표현 효과성 - `VisualExpression`
- **emoji_usage**: 이모지 사용
- **color_scheme**: 색상 구성

---

## 🎯 5. 개입 목적 및 리스크 데이터

### 개입 목적
- **intervention_purpose**: 개입 목적 (정서 회복/루틴 복귀/집중 강화) - `InterventionPurpose`
- **emotional_recovery_needed**: 정서 회복 필요 여부
- **routine_recovery_needed**: 루틴 복귀 필요 여부
- **focus_enhancement_needed**: 집중 강화 필요 여부

### 리스크 및 예측
- **predicted_risks**: 예상 리스크 - `InterventionRisk`
- **risk_factors**: 리스크 요인
- **intervention_success_rate**: 개입 성공률 (0.0~1.0)
- **long_term_pattern_anomaly**: 장기 패턴 이상
- **repeated_failure_factors**: 반복 실패 요인
- **intervention_failure_prevention**: 개입 실패 방지 전략

---

## 📦 6. 개입 리소스 및 준비 데이터

- **required_resources**: 필요한 리소스 - `InterventionResource`
- **message_templates**: 메시지 템플릿
- **content_resources**: 콘텐츠 리소스
- **feedback_materials**: 피드백 자료
- **intervention_responsible_person**: 개입 책임자
- **teacher_availability**: 교사 가용성
- **mentor_availability**: 멘토 가용성
- **cooperation_system**: 협력 체계
- **teacher_cooperation_level**: 교사 협력도
- **parent_cooperation_level**: 보호자 협력도

---

## 📈 7. 모니터링 및 효과성 데이터

- **monitoring_indicators**: 모니터링 지표
- **feedback_collection_method**: 피드백 수집 방식
- **intervention_tracking_metrics**: 개입 추적 지표
- **success_evaluation_criteria**: 성공 평가 기준
- **read_rate**: 읽음률
- **response_time**: 응답 시간
- **effectiveness_score**: 효과성 점수
- **behavior_change**: 행동 변화
- **emotion_recovery_speed**: 감정 회복 속도
- **recent_intervention_read_rate**: 최근 개입 읽음률
- **recent_intervention_response_time**: 최근 개입 응답 시간
- **recent_intervention_effectiveness**: 최근 개입 효과성

---

## 🔄 8. 개입 조합 및 전략 데이터

- **daily_message_limit**: 하루 메시지 제한
- **daily_message_count**: 오늘 발송된 메시지 수
- **limit_exceeded**: 제한 초과 여부
- **chain_trigger**: 연쇄 트리거 여부
- **chain_trigger_detected**: 연쇄 트리거 감지 여부
- **predecessor_successor_relationship**: 선행·후속 개입 관계
- **pending_interventions**: 대기 중인 개입 목록
- **pending_intervention_list**: 대기 중인 개입 목록
- **intervention_priority**: 개입 우선순위
- **planned_priority**: 계획된 우선순위
- **priority_readjustment_needed**: 우선순위 재조정 필요 여부
- **priority_reordering**: 우선순위 재정렬
- **intervention_combination**: 개입 조합
- **fatigue_minimization**: 피로도 최소화
- **concentration_change_minimization**: 집중도 변화 최소화
- **execution_sequence**: 실행 시퀀스
- **natural_execution**: 자연스러운 실행

---

## 📋 9. 개입 시나리오 및 설계 데이터

- **intervention_scenario**: 개입 시나리오 - `InterventionScenario`
- **situation_specific_design**: 상황별 개입 설계
- **exam_preparation_period**: 시험 대비 기간
- **mentor_intervention_needed**: 멘토 개입 필요 여부
- **long_term_pattern**: 장기 패턴
- **intervention_history**: 개입 이력
- **successful_intervention_patterns**: 성공한 개입 패턴
- **failed_intervention_patterns**: 실패한 개입 패턴
- **pattern_learning**: 패턴 학습
- **design_improvement**: 설계 개선
- **pre_intervention_verification**: 개입 실행 전 검증
- **rollback_plan**: 롤백 계획
- **safety_measures**: 안전 조치
- **contingency_plan**: 비상 계획

---

## 🎓 10. 개입 계획 및 전달 데이터

- **intervention_plan_agent20**: Agent 20에서 전달된 개입 계획 - `InterventionPlan`
- **planned_content**: 계획된 내용
- **scheduled_timing**: 예정 시점
- **intervention_list_update**: 개입 목록 업데이트
- **intervention_delivery**: 개입 전달 - `InterventionDelivery`
- **delivery_method_improvement**: 전달 방식 개선
- **expected_response_pattern**: 예상 반응 패턴
- **recent_intervention_response**: 최근 개입 반응
- **recent_intervention_performance**: 최근 개입 성과
- **strategy_adjustment**: 전략 조정
- **next_intervention_time**: 다음 개입 시간
- **next_intervention_cycle**: 다음 개입 주기
- **improvement_loop**: 개선 루프

---

## 👨‍🏫 11. 선생님 개입 관련 데이터

- **emotion_stability**: 감정 안정도
- **emotion_instability_detected**: 감정 불안정 감지 여부
- **auto_intervention_inappropriate**: 자동 개입 부적절 여부
- **teacher_delivery_more_effective**: 선생님 직접 전달이 더 효과적 여부
- **effectiveness_comparison**: 효과성 비교
- **auto_intervention_hold**: 자동 개입 보류
- **teacher_feedback_request**: 선생님 피드백 전달 요청
- **intervention_status_change**: 개입 상태 변경
- **teacher_approval_needed**: 선생님 승인 필요 여부
- **approval_criteria**: 승인 기준

---

## 📊 12. 집중 시간대 및 활동 전환 데이터

- **effective_intervention_types**: 효과적인 개입 유형
- **effectiveness_prediction_score**: 효과성 예측 점수
- **concentration_time_interventions**: 집중 시간대 개입
- **selected_interventions**: 선택된 개입
- **execution_order**: 실행 순서
- **time_offset_calculation**: 시간 오프셋 계산

---

## 📋 Ontology 매핑 요약

### 핵심 온톨로지 클래스
- `InterventionTrigger`: 개입 트리거 유형(데이터/인터페이스)과 타이밍 (Agent 20 핵심 온톨로지)
- `InterventionTiming`: 최적 개입 타이밍과 강도(즉시/지연/완화)
- `InterventionLocation`: 개입 위치(대시보드/학습 화면/알림)와 인터페이스 매핑
- `LearningFlowState`: 학습 흐름 상태(정상/이탈/복귀)
- `InterventionMethod`: 개입 방식(알림/메시지/채팅/호출) 선택
- `MessageTone`: 메시지 톤(격려형/코치형/공감형/경고형)과 어조
- `InterventionDelivery`: 개입 전달 시점, 강도, 빈도
- `VisualExpression`: 시각적 표현(이모지, 색상, 아이콘) 활용
- `InterventionPurpose`: 개입 목적(정서 회복/루틴 복귀/집중 강화)
- `InterventionRisk`: 개입 리스크 예측과 관리
- `InterventionResource`: 개입 사전 준비 리소스와 책임자 배치
- `InterventionScenario`: 상황별 개입 설계 시나리오
- `InterventionHistory`: 개입 이력과 성공/실패 패턴을 온톨로지로 표현하여 학습에 활용
- `InterventionPlan`: 개입 계획 (위치, 방식, 타이밍, 목적 포함)

### 데이터 소스 온톨로지 매핑
모든 데이터 소스는 `alphatutor_ontology.owl` 파일에 정의된 클래스로 매핑됩니다. 각 데이터 소스 옆에 표시된 클래스명을 참조하세요.

#### 주요 데이터 소스 → 온톨로지 매핑
- `intervention_trigger` / `data_based_trigger` / `interface_based_trigger` → `InterventionTrigger`
- `optimal_intervention_timing` / `intervention_intensity` → `InterventionTiming`
- `interface_location` / `intervention_location_recommendation` → `InterventionLocation`
- `learning_flow_status` → `LearningFlowState`
- `intervention_method_type` → `InterventionMethod`
- `message_tone_type` / `message_tone` → `MessageTone`
- `intervention_delivery_time` / `intervention_frequency` → `InterventionDelivery`
- `visual_expression_effectiveness` / `emoji_usage` / `color_scheme` → `VisualExpression`
- `intervention_purpose` → `InterventionPurpose`
- `predicted_risks` / `risk_factors` → `InterventionRisk`
- `required_resources` → `InterventionResource`
- `intervention_scenario` / `situation_specific_design` → `InterventionScenario`
- `intervention_history` / `successful_intervention_patterns` / `failed_intervention_patterns` → `InterventionHistory`
- `intervention_plan_agent20` → `InterventionPlan`

---

## 🧪 13. AI 분석 및 추론용 메타 정보

- **개입 전/후 효과 측정 데이터**: 개입 실행 전후의 효과를 측정하는 데이터

---

**참고**: 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.

**Ontology 파일 위치**: `alt42/orchestration/agents/ontology_engineering/alphatutor_ontology.owl`

**질문 데이터 위치**: `alt42/orchestration/agents/agent_orchestration/data_based_questions.js` (agent20 섹션)

**Rules 파일 위치**: `alt42/orchestration/agents/agent20_intervention_preparation/rules/rules.yaml`
