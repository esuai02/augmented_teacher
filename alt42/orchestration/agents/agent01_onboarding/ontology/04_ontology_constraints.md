# Agent01 온톨로지 제약 조건 명세서 (Ontology Constraints Specification)

**문서 버전**: 1.0  
**생성일**: 2025-01-27  
**에이전트**: Agent 01 - Onboarding  
**목적**: 온톨로지 데이터 무결성 제약 조건 정의

---

## 1. 문서 범위

이 문서는 **Agent01 온톨로지의 모든 제약 조건(Constraints)**을 정의합니다.

**포함 내용**:
- 필수 속성 제약
- 카디널리티 제약
- 타입 범위 제약
- 관계 제약
- 크로스 스키마 제약
- 비즈니스 규칙 제약

**제외 내용**:
- 클래스 정의 (01_ONTOLOGY_SPEC.md 참조)
- 타입 정의 (02_ONTOLOGY_TYPES.md 참조)
- 관계 정의 (03_ONTOLOGY_RELATIONS.md 참조)

---

## 2. 제약 조건 분류

Agent01 온톨로지의 제약 조건은 다음 6가지로 분류됩니다:

1. **필수 속성 제약 (Required Property Constraints)**
2. **카디널리티 제약 (Cardinality Constraints)**
3. **타입 범위 제약 (Type Range Constraints)**
4. **관계 제약 (Relationship Constraints)**
5. **크로스 스키마 제약 (Cross-Schema Constraints)**
6. **비즈니스 규칙 제약 (Business Rule Constraints)**

---

## 3. 필수 속성 제약 (Required Property Constraints)

### 3.1 OnboardingContext 필수 속성

**제약 규칙**:
```json
{
  "constraint_id": "C001",
  "constraint_type": "RequiredProperty",
  "target_class": "mk:OnboardingContext",
  "required_properties": [
    "mk:hasStudentGrade",
    "mk:hasSchool",
    "mk:hasAcademy",
    "mk:hasStage"
  ],
  "severity": "error",
  "message": "OnboardingContext는 반드시 hasStudentGrade, hasSchool, hasAcademy, hasStage를 가져야 합니다"
}
```

**검증 규칙**:
```
function validateOnboardingContext(instance):
  required = ["hasStudentGrade", "hasSchool", "hasAcademy", "hasStage"]
  for prop in required:
    if not instance.hasProperty(prop):
      return ValidationError("Missing required property: " + prop)
  return ValidationSuccess()
```

**JSON-LD 검증 예시**:
```json
// ✅ 유효한 인스턴스
{
  "@id": "mk:OnboardingContext/instance_001",
  "@type": "mk:OnboardingContext",
  "mk:hasStudentGrade": "중2",
  "mk:hasSchool": "OO중학교",
  "mk:hasAcademy": "OO수학학원",
  "mk:hasStage": "Context"
}

// ❌ 유효하지 않은 인스턴스 (hasSchool 누락)
{
  "@id": "mk:OnboardingContext/instance_002",
  "@type": "mk:OnboardingContext",
  "mk:hasStudentGrade": "중2",
  "mk:hasAcademy": "OO수학학원",
  "mk:hasStage": "Context"
  // hasSchool 누락 → 검증 실패
}
```

---

### 3.2 LearningContextIntegration 필수 속성

**제약 규칙**:
```json
{
  "constraint_id": "C002",
  "constraint_type": "RequiredProperty",
  "target_class": "mk:LearningContextIntegration",
  "required_properties": [
    "mk:hasParent",
    "mk:hasConceptProgress",
    "mk:hasUnitMastery",
    "mk:hasStage"
  ],
  "severity": "error"
}
```

**검증 규칙**:
```
function validateLearningContextIntegration(instance):
  required = ["hasParent", "hasConceptProgress", "hasUnitMastery", "hasStage"]
  for prop in required:
    if not instance.hasProperty(prop):
      return ValidationError("Missing required property: " + prop)
  
  // hasParent는 반드시 OnboardingContext를 참조해야 함
  if instance.hasParent.type != "mk:OnboardingContext":
    return ValidationError("hasParent must reference OnboardingContext")
  
  return ValidationSuccess()
```

---

### 3.3 FirstClassDecisionModel 필수 속성

**제약 규칙**:
```json
{
  "constraint_id": "C003",
  "constraint_type": "RequiredProperty",
  "target_class": "mk:FirstClassDecisionModel",
  "required_properties": [
    "mk:hasParent",
    "mk:usesContext",
    "mk:hasProblem",
    "mk:hasDecision",
    "mk:hasDifficultyLevel",
    "mk:hasAlignmentStrategy",
    "mk:hasStage"
  ],
  "severity": "error"
}
```

**추가 제약**:
- `mk:usesContext`는 최소 2개 요소를 가져야 함
- `mk:usesContext`는 반드시 `mk:OnboardingContext`와 `mk:LearningContextIntegration`을 포함해야 함

**검증 규칙**:
```
function validateFirstClassDecisionModel(instance):
  required = ["hasParent", "usesContext", "hasProblem", "hasDecision", 
              "hasDifficultyLevel", "hasAlignmentStrategy", "hasStage"]
  for prop in required:
    if not instance.hasProperty(prop):
      return ValidationError("Missing required property: " + prop)
  
  // usesContext 최소 2개
  if instance.usesContext.length < 2:
    return ValidationError("usesContext must have at least 2 elements")
  
  // OnboardingContext와 LearningContextIntegration 포함 확인
  contextTypes = instance.usesContext.map(c => c.type)
  if not contextTypes.contains("mk:OnboardingContext"):
    return ValidationError("usesContext must include OnboardingContext")
  if not contextTypes.contains("mk:LearningContextIntegration"):
    return ValidationError("usesContext must include LearningContextIntegration")
  
  return ValidationSuccess()
```

---

### 3.4 FirstClassExecutionPlan 필수 속성

**제약 규칙**:
```json
{
  "constraint_id": "C004",
  "constraint_type": "RequiredProperty",
  "target_class": "mk:FirstClassExecutionPlan",
  "required_properties": [
    "mk:hasParent",
    "mk:referencesDecision",
    "mk:hasAction",
    "mk:hasMeasurement",
    "mk:hasStage"
  ],
  "severity": "error"
}
```

**추가 제약**:
- `mk:hasAction`은 최소 1개 요소를 가져야 함
- `mk:hasMeasurement`는 최소 1개 요소를 가져야 함
- `mk:referencesDecision`과 `mk:hasParent`는 같은 노드를 참조해야 함

**검증 규칙**:
```
function validateFirstClassExecutionPlan(instance):
  required = ["hasParent", "referencesDecision", "hasAction", 
              "hasMeasurement", "hasStage"]
  for prop in required:
    if not instance.hasProperty(prop):
      return ValidationError("Missing required property: " + prop)
  
  // hasAction 최소 1개
  if instance.hasAction.length < 1:
    return ValidationError("hasAction must have at least 1 element")
  
  // hasMeasurement 최소 1개
  if instance.hasMeasurement.length < 1:
    return ValidationError("hasMeasurement must have at least 1 element")
  
  // referencesDecision과 hasParent 일치 확인
  if instance.referencesDecision != instance.hasParent:
    return ValidationError("referencesDecision and hasParent must reference the same node")
  
  return ValidationSuccess()
```

---

## 4. 카디널리티 제약 (Cardinality Constraints)

### 4.1 단일 값 제약 (Single Value Constraints)

| 속성 | 클래스 | 최소 | 최대 | 설명 |
|------|--------|------|------|------|
| `mk:hasParent` | `mk:LearningContextIntegration` | 1 | 1 | 단일 부모만 허용 |
| `mk:hasParent` | `mk:FirstClassDecisionModel` | 1 | 1 | 단일 부모만 허용 |
| `mk:hasParent` | `mk:FirstClassExecutionPlan` | 1 | 1 | 단일 부모만 허용 |
| `mk:referencesDecision` | `mk:FirstClassExecutionPlan` | 1 | 1 | 단일 Decision만 참조 |
| `mk:hasStage` | 모든 노드 | 1 | 1 | 단일 Stage 값만 허용 |
| `mk:hasDifficultyLevel` | `mk:FirstClassDecisionModel` | 1 | 1 | 단일 난이도만 허용 |
| `mk:hasAlignmentStrategy` | `mk:FirstClassDecisionModel` | 1 | 1 | 단일 전략만 허용 |

**검증 규칙**:
```
function validateSingleValue(instance, property):
  value = instance.getProperty(property)
  if value is Array and value.length > 1:
    return ValidationError(property + " must have exactly one value")
  return ValidationSuccess()
```

---

### 4.2 다중 값 제약 (Multiple Value Constraints)

| 속성 | 클래스 | 최소 | 최대 | 설명 |
|------|--------|------|------|------|
| `mk:usesContext` | `mk:FirstClassDecisionModel` | 2 | 무제한 | 최소 2개 Context 참조 |
| `mk:hasAction` | `mk:FirstClassExecutionPlan` | 1 | 무제한 | 최소 1개 행동 |
| `mk:hasMeasurement` | `mk:FirstClassExecutionPlan` | 1 | 무제한 | 최소 1개 측정 방법 |
| `mk:hasTextbooks` | `mk:OnboardingContext` | 0 | 무제한 | 교재 목록 (빈 배열 허용) |
| `mk:hasUnitPlan` | `mk:FirstClassDecisionModel` | 1 | 무제한 | 최소 1개 단원 계획 |
| `mk:hasDataSources` | `mk:FirstClassDecisionModel` | 1 | 무제한 | 최소 1개 데이터 소스 |

**검증 규칙**:
```
function validateMultipleValue(instance, property, minCount, maxCount):
  value = instance.getProperty(property)
  if not value is Array:
    return ValidationError(property + " must be an array")
  
  if value.length < minCount:
    return ValidationError(property + " must have at least " + minCount + " elements")
  
  if maxCount != null and value.length > maxCount:
    return ValidationError(property + " must have at most " + maxCount + " elements")
  
  return ValidationSuccess()
```

---

## 5. 타입 범위 제약 (Type Range Constraints)

### 5.1 정수 범위 제약

| 속성 | 클래스 | 타입 | 최소값 | 최대값 | 설명 |
|------|--------|------|--------|--------|------|
| `mk:hasMathConfidence` | `mk:OnboardingContext` | `xsd:integer` | 0 | 10 | 자신감 점수 범위 |

**검증 규칙**:
```json
{
  "constraint_id": "C101",
  "constraint_type": "TypeRange",
  "target_property": "mk:hasMathConfidence",
  "target_class": "mk:OnboardingContext",
  "type": "xsd:integer",
  "min_value": 0,
  "max_value": 10,
  "inclusive": true,
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateIntegerRange(instance, property, minValue, maxValue):
  value = instance.getProperty(property)
  if not isinstance(value, int):
    return ValidationError(property + " must be an integer")
  
  if value < minValue or value > maxValue:
    return ValidationError(property + " must be between " + minValue + " and " + maxValue)
  
  return ValidationSuccess()
```

---

### 5.2 문자열 길이 제약

| 속성 | 클래스 | 타입 | 최소 길이 | 최대 길이 | 설명 |
|------|--------|------|----------|----------|------|
| `mk:hasSchool` | `mk:OnboardingContext` | `xsd:string` | 1 | 100 | 학교명 길이 |
| `mk:hasAcademy` | `mk:OnboardingContext` | `xsd:string` | 1 | 100 | 학원명 길이 |
| `mk:hasProblem` | `mk:FirstClassDecisionModel` | `xsd:string` | 10 | 500 | 문제 설명 길이 |
| `mk:hasDecision` | `mk:FirstClassDecisionModel` | `xsd:string` | 10 | 500 | 결정 내용 길이 |

**검증 규칙**:
```
function validateStringLength(instance, property, minLength, maxLength):
  value = instance.getProperty(property)
  if not isinstance(value, str):
    return ValidationError(property + " must be a string")
  
  length = len(value)
  if length < minLength:
    return ValidationError(property + " must be at least " + minLength + " characters")
  
  if length > maxLength:
    return ValidationError(property + " must be at most " + maxLength + " characters")
  
  return ValidationSuccess()
```

---

### 5.3 열거형 값 제약

| 속성 | 클래스 | 타입 | 허용 값 | 설명 |
|------|--------|------|---------|------|
| `mk:hasDifficultyLevel` | `mk:FirstClassDecisionModel` | `mk:DifficultyLevel` | Easy, EasyToMedium, Medium, MediumToHard, Hard, VeryHard | 난이도 열거형 |
| `mk:hasAlignmentStrategy` | `mk:FirstClassDecisionModel` | `mk:AlignmentStrategy` | BridgeStrategy, ReinforcementStrategy, PreviewStrategy, SynchronizedStrategy | 정렬 전략 열거형 |
| `mk:hasStage` | 모든 노드 | `xsd:string` | Context, Decision, Execution | Stage 열거형 |
| `mk:hasMathStressLevel` | `mk:OnboardingContext` | `mk:StressLevel` | Low, Medium, High, VeryHigh | 스트레스 수준 열거형 |

**검증 규칙**:
```json
{
  "constraint_id": "C201",
  "constraint_type": "EnumerationValue",
  "target_property": "mk:hasDifficultyLevel",
  "target_class": "mk:FirstClassDecisionModel",
  "allowed_values": [
    "mk:Easy",
    "mk:EasyToMedium",
    "mk:Medium",
    "mk:MediumToHard",
    "mk:Hard",
    "mk:VeryHard"
  ],
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateEnumerationValue(instance, property, allowedValues):
  value = instance.getProperty(property)
  if value not in allowedValues:
    return ValidationError(property + " must be one of: " + str(allowedValues))
  return ValidationSuccess()
```

---

## 6. 관계 제약 (Relationship Constraints)

### 6.1 순환 참조 금지

**제약 규칙**:
```json
{
  "constraint_id": "C301",
  "constraint_type": "NoCircularReference",
  "target_relation": "mk:hasParent",
  "description": "노드는 자신의 조상(ancestor)을 parent로 가질 수 없음",
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateNoCircularReference(node, parent):
  if parent == null:
    return ValidationSuccess()
  
  if node.id == parent.id:
    return ValidationError("Circular reference detected: node cannot be its own parent")
  
  // 재귀적으로 조상 확인
  ancestors = []
  current = parent
  while current != null:
    if current.id == node.id:
      return ValidationError("Circular reference detected: node cannot reference its ancestor")
    ancestors.append(current.id)
    current = current.hasParent
  
  return ValidationSuccess()
```

---

### 6.2 역관계 일관성

**제약 규칙**:
```json
{
  "constraint_id": "C302",
  "constraint_type": "InverseRelationConsistency",
  "relation_pair": [
    {"relation": "mk:hasParent", "inverse": "mk:hasChild"}
  ],
  "description": "A hasChild B이면 반드시 B hasParent A여야 함",
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateInverseConsistency(parent, child):
  // parent.hasChild에 child가 포함되어 있는지 확인
  if parent.hasChild.contains(child.id):
    if child.hasParent != parent.id:
      return ValidationError("Inverse relation inconsistency: parent.hasChild but not child.hasParent")
  
  // child.hasParent가 parent를 가리키는지 확인
  if child.hasParent == parent.id:
    if not parent.hasChild.contains(child.id):
      return ValidationError("Inverse relation inconsistency: child.hasParent but not parent.hasChild")
  
  return ValidationSuccess()
```

---

### 6.3 관계 타입 일치

**제약 규칙**:
```json
{
  "constraint_id": "C303",
  "constraint_type": "RelationTypeMatch",
  "target_relation": "mk:usesContext",
  "source_class": "mk:FirstClassDecisionModel",
  "target_classes": ["mk:OnboardingContext", "mk:LearningContextIntegration"],
  "description": "usesContext는 반드시 Context Layer 노드만 참조해야 함",
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateRelationTypeMatch(instance, relation, allowedTargetTypes):
  targets = instance.getProperty(relation)
  if not isinstance(targets, list):
    targets = [targets]
  
  for target in targets:
    targetInstance = getOntologyInstance(target)
    if targetInstance.type not in allowedTargetTypes:
      return ValidationError(
        relation + " must reference one of: " + str(allowedTargetTypes) + 
        ", but found: " + targetInstance.type
      )
  
  return ValidationSuccess()
```

---

## 7. 크로스 스키마 제약 (Cross-Schema Constraints)

### 7.1 Stage 일관성 제약

**제약 규칙**:
```json
{
  "constraint_id": "C401",
  "constraint_type": "CrossSchemaConsistency",
  "description": "노드의 stage 값은 클래스 타입과 일치해야 함",
  "rules": [
    {
      "class": "mk:OnboardingContext",
      "required_stage": "Context"
    },
    {
      "class": "mk:LearningContextIntegration",
      "required_stage": "Context"
    },
    {
      "class": "mk:FirstClassDecisionModel",
      "required_stage": "Decision"
    },
    {
      "class": "mk:FirstClassExecutionPlan",
      "required_stage": "Execution"
    }
  ],
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateStageConsistency(instance):
  stageMapping = {
    "mk:OnboardingContext": "Context",
    "mk:LearningContextIntegration": "Context",
    "mk:FirstClassDecisionModel": "Decision",
    "mk:FirstClassExecutionPlan": "Execution"
  }
  
  expectedStage = stageMapping[instance.type]
  actualStage = instance.hasStage
  
  if actualStage != expectedStage:
    return ValidationError(
      "Stage mismatch: " + instance.type + " must have stage '" + 
      expectedStage + "', but found '" + actualStage + "'"
    )
  
  return ValidationSuccess()
```

---

### 7.2 Parent-Child Stage 일관성

**제약 규칙**:
```json
{
  "constraint_id": "C402",
  "constraint_type": "ParentChildStageConsistency",
  "description": "자식 노드의 stage는 부모 노드의 stage보다 낮을 수 없음",
  "stage_hierarchy": ["Context", "Decision", "Execution"],
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateParentChildStageConsistency(child):
  if child.hasParent == null:
    return ValidationSuccess()
  
  parent = getOntologyInstance(child.hasParent)
  stageHierarchy = ["Context", "Decision", "Execution"]
  
  parentStageIndex = stageHierarchy.index(parent.hasStage)
  childStageIndex = stageHierarchy.index(child.hasStage)
  
  if childStageIndex <= parentStageIndex:
    return ValidationError(
      "Child stage must be later than parent stage. " +
      "Parent: " + parent.hasStage + ", Child: " + child.hasStage
    )
  
  return ValidationSuccess()
```

---

## 8. 비즈니스 규칙 제약 (Business Rule Constraints)

### 8.1 DecisionModel Context 의존성

**제약 규칙**:
```json
{
  "constraint_id": "C501",
  "constraint_type": "BusinessRule",
  "description": "FirstClassDecisionModel은 반드시 OnboardingContext와 LearningContextIntegration을 모두 참조해야 함",
  "target_class": "mk:FirstClassDecisionModel",
  "rule": "usesContext must contain both OnboardingContext and LearningContextIntegration",
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateDecisionModelContextDependency(instance):
  if instance.type != "mk:FirstClassDecisionModel":
    return ValidationSuccess()
  
  contexts = instance.usesContext
  contextTypes = [getOntologyInstance(c).type for c in contexts]
  
  if "mk:OnboardingContext" not in contextTypes:
    return ValidationError("DecisionModel must reference OnboardingContext")
  
  if "mk:LearningContextIntegration" not in contextTypes:
    return ValidationError("DecisionModel must reference LearningContextIntegration")
  
  return ValidationSuccess()
```

---

### 8.2 ExecutionPlan Decision 참조 일관성

**제약 규칙**:
```json
{
  "constraint_id": "C502",
  "constraint_type": "BusinessRule",
  "description": "ExecutionPlan의 referencesDecision과 hasParent는 같은 노드를 참조해야 함",
  "target_class": "mk:FirstClassExecutionPlan",
  "rule": "referencesDecision == hasParent",
  "severity": "error"
}
```

**검증 알고리즘**:
```
function validateExecutionPlanDecisionConsistency(instance):
  if instance.type != "mk:FirstClassExecutionPlan":
    return ValidationSuccess()
  
  if instance.referencesDecision != instance.hasParent:
    return ValidationError(
      "ExecutionPlan referencesDecision and hasParent must reference the same node. " +
      "referencesDecision: " + instance.referencesDecision + ", " +
      "hasParent: " + instance.hasParent
    )
  
  return ValidationSuccess()
```

---

### 8.3 Action-Measurement 쌍 제약

**제약 규칙**:
```json
{
  "constraint_id": "C503",
  "constraint_type": "BusinessRule",
  "description": "ExecutionPlan의 action과 measurement는 1:1 또는 1:N 관계를 가져야 함",
  "target_class": "mk:FirstClassExecutionPlan",
  "rule": "Each action should have at least one corresponding measurement",
  "severity": "warning"
}
```

**검증 알고리즘**:
```
function validateActionMeasurementPair(instance):
  if instance.type != "mk:FirstClassExecutionPlan":
    return ValidationSuccess()
  
  actions = instance.hasAction
  measurements = instance.hasMeasurement
  
  // 각 action에 대해 관련 measurement가 있는지 확인 (경고 수준)
  if len(measurements) < len(actions):
    return ValidationWarning(
      "Some actions may not have corresponding measurements. " +
      "Actions: " + str(len(actions)) + ", Measurements: " + str(len(measurements))
    )
  
  return ValidationSuccess()
```

---

## 9. 제약 조건 검증 우선순위

제약 조건은 다음 순서로 검증됩니다:

1. **필수 속성 제약** (최우선, 에러)
2. **타입 범위 제약** (에러)
3. **카디널리티 제약** (에러)
4. **관계 제약** (에러)
5. **크로스 스키마 제약** (에러)
6. **비즈니스 규칙 제약** (경고)

**검증 파이프라인**:
```
function validateOntologyInstance(instance):
  errors = []
  warnings = []
  
  // 1. 필수 속성 검증
  result = validateRequiredProperties(instance)
  if result.hasError:
    errors.extend(result.errors)
    return ValidationResult(errors, warnings)  // 조기 종료
  
  // 2. 타입 범위 검증
  result = validateTypeRanges(instance)
  errors.extend(result.errors)
  
  // 3. 카디널리티 검증
  result = validateCardinalities(instance)
  errors.extend(result.errors)
  
  // 4. 관계 검증
  result = validateRelationships(instance)
  errors.extend(result.errors)
  
  // 5. 크로스 스키마 검증
  result = validateCrossSchema(instance)
  errors.extend(result.errors)
  
  // 6. 비즈니스 규칙 검증
  result = validateBusinessRules(instance)
  warnings.extend(result.warnings)
  
  return ValidationResult(errors, warnings)
```

---

## 10. 제약 조건 확장 가이드

### 10.1 새로운 제약 조건 추가

새로운 제약 조건을 추가할 때는 다음 구조를 따릅니다:

```json
{
  "constraint_id": "CXXX",
  "constraint_type": "ConstraintType",
  "target_class": "mk:TargetClass",
  "target_property": "mk:targetProperty",  // 선택
  "description": "제약 조건 설명",
  "rule": "제약 규칙 설명",
  "severity": "error|warning",
  "validation_function": "functionName"
}
```

### 10.2 제약 조건 버전 관리

제약 조건이 변경될 때는:
1. 버전 번호 증가
2. 변경 이력 기록
3. 기존 데이터 마이그레이션 계획 수립
4. 하위 호환성 확인

---

## 11. 데이터베이스 제약 조건 매핑

### 11.1 SQL 제약 조건

| 온톨로지 제약 | SQL 제약 | 예시 |
|-------------|---------|------|
| 필수 속성 | `NOT NULL` | `hasStudentGrade VARCHAR(10) NOT NULL` |
| 타입 범위 | `CHECK` | `hasMathConfidence INT CHECK (hasMathConfidence >= 0 AND hasMathConfidence <= 10)` |
| 열거형 값 | `ENUM` 또는 `CHECK` | `hasStage ENUM('Context', 'Decision', 'Execution')` |
| 카디널리티 | `UNIQUE` 또는 외래키 | `hasParent VARCHAR(255) UNIQUE` |

### 11.2 GraphDB 제약 조건

Neo4j의 경우:
- 제약 조건은 애플리케이션 레벨에서 검증
- 또는 APOC 라이브러리를 사용한 제약 조건 정의

---

## 12. 참고 문서

- **01_ONTOLOGY_SPEC.md**: 클래스 및 속성 정의
- **02_ONTOLOGY_TYPES.md**: 타입 정의
- **03_ONTOLOGY_RELATIONS.md**: 관계 정의
- **05_ONTOLOGY_CONTEXT_TREE.md**: 계층 구조 정의
- **06_JSONLD_MAPPING.md**: JSON-LD 변환 규칙

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-01-27  
**작성자**: Agent01 Ontology Team  
**다음 문서**: `05_ontology_context_tree.md`

