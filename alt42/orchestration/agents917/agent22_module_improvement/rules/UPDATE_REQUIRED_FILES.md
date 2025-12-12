# Agent 22 Module Improvement - Rules 폴더 업데이트 필요 파일 식별

**작성일**: 2025-01-27  
**목적**: data_based_questions.js의 질문 세트와 alphatutor_ontology.owl을 기준으로 업데이트가 필요한 파일 식별

---

## 📋 업데이트 필요 파일 목록

### 1. **rules.yaml** (우선순위: 높음)
**현재 상태**: ontology_mapping 섹션이 없음  
**필요 작업**:
- `ontology_mapping` 섹션 추가
- data_based_questions.js의 질문 세트에 정의된 온톨로지 클래스 매핑
- 각 데이터 소스를 온톨로지 클래스로 매핑

**추가해야 할 온톨로지 매핑**:
```yaml
ontology_mapping:
  core_ontology: "ModuleImprovement"
  related_ontologies:
    - "SystemInefficiencyInstability"
    - "RuleNetworkOptimization"
    - "AutoEvolutionStructure"
    - "VulnerabilityAnalysis"
    - "SelfUpgradeIdea"
    - "ThreeFileSystemDocument"
  data_sources:
    agent_execution_log: "AgentExecutionLog"
    rule_activation_frequency: "RuleActivationFrequency"
    resource_usage: "ResourceUsage"
    failure_pattern: "FailurePattern"
    rule_condition_duplication: "RuleConditionDuplication"
    rule_conflict_possibility: "RuleConflictPossibility"
    edge_case_missing: "EdgeCaseMissing"
    data_collection: "DataCollection"
    vulnerability_diagnosis: "VulnerabilityAnalysis"
    file_generation: "ThreeFileSystemDocument"
    ai_review: "AIReview"
    verification: "Verification"
    deployment: "Deployment"
    priority_check_modules: "PriorityCheckModules"
    response_procedures: "ResponseProcedures"
    system_inefficiency: "SystemInefficiencyInstability"
    system_instability: "SystemInefficiencyInstability"
    impact_effort_matrix: "ImpactEffortMatrix"
    improvement_priority_map: "ImprovementPriorityMap"
    rule_structure_comparison: "RuleStructureComparison"
    analysis_pattern_comparison: "AnalysisPatternComparison"
    optimal_improvement_direction: "OptimalImprovementDirection"
    continuous_performance_improvement: "ContinuousPerformanceImprovement"
    error_self_recovery: "ErrorSelfRecovery"
    automated_verification_pipeline: "AutomatedVerificationPipeline"
    upgrade_loop: "UpgradeLoop"
    verification_system: "VerificationSystem"
    long_term_stability: "LongTermStability"
```

---

### 2. **metadata.md** (우선순위: 높음)
**현재 상태**: 온톨로지 매핑 정보가 매우 간단함 (1줄만 존재)  
**필요 작업**:
- data_based_questions.js의 3개 포괄형 질문 세트에 대한 상세 온톨로지 매핑 정보 추가
- 각 질문 세트별 데이터 소스 → 온톨로지 클래스 매핑 상세 설명
- gendata.md의 100개 데이터 항목과 온톨로지 매핑 정보 추가

**추가해야 할 섹션**:
```markdown
## 🗺️ 온톨로지 매핑 (alphatutor_ontology.owl)

Agent 22의 데이터 기반 질문에서 사용하는 모든 데이터 소스는 온톨로지에 매핑되어 있습니다:

### 포괄형 질문 1: 시스템 비효율 및 불안정성 진단
- **핵심 온톨로지**: `SystemInefficiencyInstability`
- **데이터 소스 → 온톨로지 매핑**:
  - `agent_execution_log` → `AgentExecutionLog`
  - `rule_activation_frequency` → `RuleActivationFrequency`
  - `resource_usage` → `ResourceUsage`
  - `failure_pattern` → `FailurePattern`
  - `priority_check_modules` → `PriorityCheckModules`
  - `response_procedures` → `ResponseProcedures`
  - `all_agent_execution_data` → `AllAgentExecutionData`
  - `system_inefficiency` → `SystemInefficiencyInstability`
  - `system_instability` → `SystemInefficiencyInstability`

### 포괄형 질문 2: 룰 네트워크 최적화
- **핵심 온톨로지**: `RuleNetworkOptimization`
- **데이터 소스 → 온톨로지 매핑**:
  - `rule_condition_duplication` → `RuleConditionDuplication`
  - `rule_conflict_possibility` → `RuleConflictPossibility`
  - `edge_case_missing` → `EdgeCaseMissing`
  - `impact_effort_matrix` → `ImpactEffortMatrix`
  - `improvement_priority_map` → `ImprovementPriorityMap`
  - `rule_structure_comparison` → `RuleStructureComparison`
  - `analysis_pattern_comparison` → `AnalysisPatternComparison`
  - `optimal_improvement_direction` → `OptimalImprovementDirection`

### 포괄형 질문 3: 자동 진화 구조 설계
- **핵심 온톨로지**: `AutoEvolutionStructure`
- **데이터 소스 → 온톨로지 매핑**:
  - `data_collection` → `DataCollection`
  - `vulnerability_diagnosis` → `VulnerabilityAnalysis`
  - `file_generation` → `ThreeFileSystemDocument`
  - `ai_review` → `AIReview`
  - `verification` → `Verification`
  - `deployment` → `Deployment`
  - `continuous_performance_improvement` → `ContinuousPerformanceImprovement`
  - `error_self_recovery` → `ErrorSelfRecovery`
  - `automated_verification_pipeline` → `AutomatedVerificationPipeline`
  - `upgrade_loop` → `UpgradeLoop`
  - `verification_system` → `VerificationSystem`
  - `long_term_stability` → `LongTermStability`
```

---

### 3. **gendata.md** (우선순위: 중간)
**현재 상태**: 100개 데이터 항목이 나열되어 있으나 온톨로지 매핑 정보 없음  
**필요 작업**:
- 각 데이터 항목에 대한 온톨로지 클래스 매핑 정보 추가
- data_based_questions.js의 질문 세트에서 사용하는 데이터 소스와의 연결 정보 추가

---

### 4. **questions.md** (우선순위: 중간)
**현재 상태**: 포괄형 질문과 상황별 질문이 정의되어 있으나 온톨로지 매핑 정보 없음  
**필요 작업**:
- 각 질문에 대한 데이터 소스 → 온톨로지 매핑 정보 추가
- data_based_questions.js의 질문 세트와의 일치성 확인 및 동기화

---

### 5. **alphatutor_ontology.owl** (우선순위: 낮음 - 필요시에만)
**현재 상태**: ModuleImprovement, VulnerabilityAnalysis, SelfUpgradeIdea, ThreeFileSystemDocument 클래스가 존재  
**필요 작업** (필요한 경우):
- data_based_questions.js에서 참조하는 데이터 소스 중 온톨로지에 없는 클래스가 있는지 확인
- 누락된 클래스가 있으면 추가:
  - `SystemInefficiencyInstability`
  - `RuleNetworkOptimization`
  - `AutoEvolutionStructure`
  - `AgentExecutionLog`
  - `RuleActivationFrequency`
  - `ResourceUsage`
  - `FailurePattern`
  - `RuleConditionDuplication`
  - `RuleConflictPossibility`
  - `EdgeCaseMissing`
  - `DataCollection`
  - `AIReview`
  - `Verification`
  - `Deployment`
  - `PriorityCheckModules`
  - `ResponseProcedures`
  - `ImpactEffortMatrix`
  - `ImprovementPriorityMap`
  - `RuleStructureComparison`
  - `AnalysisPatternComparison`
  - `OptimalImprovementDirection`
  - `ContinuousPerformanceImprovement`
  - `ErrorSelfRecovery`
  - `AutomatedVerificationPipeline`
  - `UpgradeLoop`
  - `VerificationSystem`
  - `LongTermStability`

---

## 📊 업데이트 우선순위 요약

| 파일 | 우선순위 | 작업 내용 | 예상 시간 |
|------|---------|---------|----------|
| rules.yaml | 높음 | ontology_mapping 섹션 추가 | 30분 |
| metadata.md | 높음 | 상세 온톨로지 매핑 정보 추가 | 1시간 |
| gendata.md | 중간 | 데이터 항목별 온톨로지 매핑 추가 | 1시간 |
| questions.md | 중간 | 질문별 온톨로지 매핑 정보 추가 | 30분 |
| alphatutor_ontology.owl | 낮음 | 누락된 클래스 추가 (필요시) | 1시간 |

---

## ✅ 검증 체크리스트

업데이트 완료 후 다음 사항을 확인해야 합니다:

- [x] rules.yaml에 ontology_mapping 섹션이 추가되었는가? ✅ 완료 (2025-01-27)
- [x] metadata.md에 3개 포괄형 질문 세트별 상세 온톨로지 매핑이 추가되었는가? ✅ 완료 (2025-01-27)
- [x] data_based_questions.js의 모든 dataSources가 온톨로지에 매핑되었는가? ✅ 완료 (2025-01-27)
- [x] gendata.md의 데이터 항목과 온톨로지 매핑이 일치하는가? ✅ 완료 (2025-01-27)
- [x] questions.md의 질문과 data_based_questions.js의 질문이 일치하는가? ✅ 완료 (2025-01-27)
- [x] alphatutor_ontology.owl에 필요한 모든 클래스가 존재하는가? ✅ 완료 (2025-01-27)

---

## 📝 업데이트 완료 내역

**업데이트 일시**: 2025-01-27

### 완료된 작업
1. ✅ **rules.yaml**: ontology_mapping 섹션 추가 (29개 데이터 소스 매핑)
2. ✅ **metadata.md**: 3개 포괄형 질문 세트별 상세 온톨로지 매핑 정보 추가
3. ✅ **questions.md**: 온톨로지 매핑 섹션 추가 (포괄형 질문 3개 + 상황별 질문 8개)
4. ✅ **gendata.md**: 100개 데이터 항목의 온톨로지 매핑 정보 추가
5. ✅ **alphatutor_ontology.owl**: 누락된 23개 클래스 추가 및 Triples 관계 정의

### 추가된 온톨로지 클래스 (23개)
- SystemInefficiencyInstability
- RuleNetworkOptimization
- AutoEvolutionStructure
- AgentExecutionLog
- RuleActivationFrequency
- FailurePattern
- RuleConditionDuplication
- RuleConflictPossibility
- EdgeCaseMissing
- DataCollection
- AIReview
- Verification
- PriorityCheckModules
- ResponseProcedures
- AllAgentExecutionData
- ImprovementPriorityMap
- RuleStructureComparison
- AnalysisPatternComparison
- OptimalImprovementDirection
- ContinuousPerformanceImprovement
- ErrorSelfRecovery
- AutomatedVerificationPipeline
- UpgradeLoop
- VerificationSystem
- LongTermStability

---

## 📝 참고 사항

1. 다른 에이전트들(Agent 01~21)의 rules.yaml과 metadata.md 파일을 참고하여 일관된 형식으로 작성해야 합니다.
2. 특히 Agent 20, Agent 21의 ontology_mapping 구조를 참고하면 좋습니다.
3. data_based_questions.js의 agent22 질문 세트는 3개의 포괄형 질문으로 구성되어 있습니다:
   - 포괄형 질문 1: 시스템 비효율 및 불안정성 진단
   - 포괄형 질문 2: 룰 네트워크 최적화
   - 포괄형 질문 3: 자동 진화 구조 설계

