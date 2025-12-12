# 온톨로지 엔진 연계 메커니즘 (통합 가이드)

**생성일**: 2025-01-27  
**버전**: 1.0  
**목적**: 룰 + 온톨로지 결합 시 실제 작동 방식을 정의 (전체 에이전트 공통)

---

## 1. 개요

룰 엔진과 온톨로지가 결합되면, 단순 정보 수집/탐지에서 **의미 기반 추론 → 종합 판단 → 전략/계획 생성 → 실행**까지 가능한 의사결정 시스템으로 업그레이드됩니다.

### 핵심 변화

| 구분 | 룰만 있을 때 | 룰 + 온톨로지 결합 후 |
|------|------------|-------------------|
| **질문/활동** | 단일 정보/활동 처리 | 의미 기반 연결, 구조적 분석, 전략적 판단 |
| **액션 종류** | collect_info, display_message | create_instance, reason_over, generate_strategy, generate_procedure |
| **추론 방식** | 필드 기반 조건 판단 | 그래프 탐색 + 의미 추론 |
| **출력 형태** | 텍스트 메시지 | 전략 객체, 절차 시나리오, 보강 방안 |

---

## 2. 온톨로지 액션 종류 (공통)

### 2.1 create_instance (지식 객체 생성)

**목적**: 룰이 데이터를 온톨로지 인스턴스로 변환

**형식**:
```yaml
- "create_instance: 'Namespace:ClassName'"
- "set_property: ('Namespace:PropertyName', '{Variable}')"
```

**예시 (Agent 01)**:
```yaml
- "create_instance: 'mk:OnboardingContext'"
- "set_property: ('mk:hasStudentGrade', '{gradeLevel}')"
```

**예시 (Agent 04)**:
```yaml
- "create_instance: 'mk-a04:WeakpointDetectionContext'"
- "set_property: ('mk-a04:hasStudentId', '{studentId}')"
```

---

### 2.2 reason_over (의미 기반 추론)

**목적**: 온톨로지 그래프를 탐색하여 의미 기반 추론 수행

**형식**:
```yaml
- "reason_over: 'Namespace:ClassName'"
```

**동작 방식**:
1. 지정된 온톨로지 클래스의 인스턴스를 찾음
2. 연결된 프로퍼티와 관계를 그래프 탐색
3. 의미 기반 규칙 적용
4. 추론 결과를 룰 엔진에 반환

**예시**:
- Agent 01: `concept_progress` + `advanced_progress` → `recommendsUnits`
- Agent 04: `pause_frequency` + `attention_score` → `inferredSeverity`

---

### 2.3 generate_strategy / generate_reinforcement_plan (전략/계획 생성)

**목적**: 온톨로지 기반으로 종합 전략 또는 보강 방안 생성

**형식**:
```yaml
# Agent 01
- "generate_strategy: 'mk:FirstClassStrategy'"

# Agent 04
- "generate_reinforcement_plan: 'mk-a04:WeakpointAnalysisDecisionModel'"
```

**동작 방식**:
1. 전략/계획 클래스 인스턴스 생성
2. 관련 온톨로지 인스턴스들을 연결
3. 추론 결과를 프로퍼티에 설정
4. 완성된 객체 반환

---

### 2.4 generate_procedure (시나리오 자동 생성)

**목적**: 온톨로지 구조를 읽고 수업 절차 시나리오 자동 생성 (주로 Agent 01)

**형식**:
```yaml
- "generate_procedure: 'mk:LessonProcedure'"
```

---

## 3. 룰 실행 흐름 (표준)

### 3.1 전체 흐름도

```
[사용자 입력 / 학습 활동]
    ↓
[룰 조건 평가]
    ↓
[온톨로지 인스턴스 생성] ← create_instance
    ↓
[의미 기반 추론] ← reason_over
    ↓
[전략/계획 생성] ← generate_strategy / generate_reinforcement_plan
    ↓
[절차/실행계획 생성] ← generate_procedure (Optional)
    ↓
[최종 응답 생성]
```

---

## 4. 구현 가이드

### 4.1 온톨로지 엔진 인터페이스 (PHP)

```php
class OntologyEngine {
    /**
     * 온톨로지 인스턴스 생성
     */
    public function createInstance(string $class, array $properties = []): string;
    
    /**
     * 프로퍼티 설정
     */
    public function setProperty(string $instanceId, string $property, $value): void;
    
    /**
     * 의미 기반 추론
     */
    public function reasonOver(string $class, ?string $instanceId = null): array;
    
    /**
     * 전략/계획 생성 (범용)
     */
    public function generateStrategy(string $strategyClass, array $context): array;
    
    /**
     * 절차 생성
     */
    public function generateProcedure(string $procedureClass, string $strategyId): array;
}
```

### 4.2 룰 엔진 연계 (Action Parser)

```php
// Action Parser에서 정규식으로 액션 파싱
if (preg_match('/^create_instance:\s*[\'"](.+?)[\'"]$/', $action, $matches)) { ... }
if (preg_match('/^reason_over:\s*[\'"](.+?)[\'"]$/', $action, $matches)) { ... }
// ...
```

---

## 5. 에이전트별 특이사항

### Agent 01 (Onboarding)
- **Namespace**: `mk`
- **주요 기능**: 학생 프로필 분석, 첫 수업 전략 수립, 수업 절차 생성
- **특징**: `generate_procedure`를 통해 구체적인 수업 시나리오를 생성함

### Agent 04 (Weakpoints)
- **Namespace**: `mk-a04`
- **주요 기능**: 취약점 탐지, 심각도 분석, 보강 방안 수립
- **특징**: `generate_reinforcement_plan`을 통해 분석적 의사결정 모델을 생성함

---

## 6. 참고 자료

- `rules.yaml`: 룰 정의 (온톨로지 액션 포함)
- `principles.md`: 온톨로지 구축 전략
- `ONTOLOGY_INTEGRATION_CHECKLIST.md`: 통합 체크리스트
