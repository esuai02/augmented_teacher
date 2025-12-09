# 룰 평가 전략 가이드

**작성일**: 2025-01-27  
**대상 환경**: PHP 7.1, MySQL 5.7  
**버전**: 1.0.0

---

## 🎯 핵심 원칙

### 3계층 하이브리드 접근

```
┌─────────────────────────────────────────────────────────┐
│ 1. 주기적 체크 (Heartbeat)                              │
│    → 시나리오 그룹 단위 평가 (30분 주기)                │
│    → 전체 상태 재평가                                   │
└─────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 2. 이벤트 체크 (User Action)                            │
│    → 워크플로우 이벤트 단위 평가 (즉시)                  │
│    → 관련 시나리오 그룹만 선별 평가                      │
└─────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 3. 상태 변화 체크 (ΔState Detection)                    │
│    → 변화된 상태 필드 기반 최적화 평가                   │
│    → 영향받는 룰만 선별 평가                             │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 접근 방식 비교

| 구분 | 점검 주체 | 트리거 | 평가 단위 | 결과 |
|------|----------|--------|----------|------|
| **주기적 체크** | 앱 센서 | 일정 시간 (30분) | **시나리오 그룹** | 최신 상태 업데이트 |
| **이벤트 체크** | 사용자 행동 | 클릭/입력/정지 | **워크플로우 이벤트** | 룰 즉시 평가 |
| **상태 변화 체크** | 서버 룰엔진 | Δstate 감지 | **변화 필드 기반 룰** | 상태 재분류 + 피드백 호출 |

---

## 🏗️ 권장 구조: 워크플로우 이벤트 단위 접근

### 왜 워크플로우 이벤트 단위인가?

1. **의미 있는 단위**: 단일 룰이 아닌 관련 룰들의 조합으로 실제 워크플로우를 구성
2. **성능 최적화**: PHP 7.1 환경에서 불필요한 룰 평가 최소화
3. **유지보수성**: 워크플로우 단위로 관리하여 변경 영향 범위 명확화
4. **확장성**: 새로운 워크플로우 추가가 용이

### 구조 예시

```php
// 워크플로우 이벤트 정의
$workflowEvents = [
    'learning.problem_submitted' => [
        'scenarios' => ['S1', 'S3'],  // 학습 이탈, 스트레스 관리
        'affected_fields' => ['problem_count', 'last_activity'],
        'evaluation_mode' => 'priority_first',
        'workflow_group' => 'learning_activity'
    ],
    'bio.stress_spike' => [
        'scenarios' => ['S3', 'S4'],  // 스트레스 관리, 감정 관리
        'affected_fields' => ['stress_level', 'emotion_state'],
        'evaluation_mode' => 'priority_first',
        'workflow_group' => 'bio_feedback'
    ]
];
```

---

## 🔄 3가지 체크 방식 상세

### 1. 주기적 체크 (Heartbeat) - 시나리오 그룹 단위

**목적**: 전체 상태 재평가 및 누락된 이벤트 보완

**구현**:
```php
// heartbeat.php (기존 구조 유지)
private function evaluateScenario(string $scenarioId, string $studentId, array $studentState): array {
    // 시나리오 그룹의 모든 룰 평가
    $scenarioConfig = $this->mapper->getRulesForScenario($scenarioId);
    $rules = $scenarioConfig['rules'] ?? [];
    
    // 시나리오 그룹 단위 평가
    $result = $this->ruleEvaluator->evaluateScenario(
        $scenarioId,
        $rules,
        $context,
        'priority_first'  // 첫 매칭 룰에서 중단
    );
    
    return $result;
}
```

**특징**:
- ✅ 전체 시나리오 그룹 평가
- ✅ 30분 주기로 안정적 상태 유지
- ✅ 이벤트 누락 보완

---

### 2. 이벤트 체크 (User Action) - 워크플로우 이벤트 단위

**목적**: 사용자 행동에 대한 즉시 반응

**구현**:
```php
// events.php (개선)
class EventProcessor {
    /**
     * 워크플로우 이벤트 단위 처리
     */
    public function processWorkflowEvent(array $event): array {
        $eventType = $event['topic'] ?? '';
        
        // 워크플로우 그룹 조회
        $workflowConfig = $this->getWorkflowConfig($eventType);
        
        if (!$workflowConfig) {
            return ['success' => false, 'error' => 'Unknown workflow event'];
        }
        
        $studentId = $event['student_id'] ?? null;
        $studentState = $this->getStudentState($studentId);
        
        $results = [];
        
        // 워크플로우 그룹의 시나리오들만 평가
        foreach ($workflowConfig['scenarios'] as $scenarioId) {
            $scenarioResult = $this->evaluateScenario(
                $scenarioId,
                $studentId,
                $studentState,
                $workflowConfig['evaluation_mode'] ?? 'priority_first'
            );
            
            $results[$scenarioId] = $scenarioResult;
        }
        
        return [
            'workflow_group' => $workflowConfig['workflow_group'],
            'scenarios_evaluated' => count($workflowConfig['scenarios']),
            'results' => $results
        ];
    }
}
```

**특징**:
- ✅ 관련 시나리오만 선별 평가 (성능 최적화)
- ✅ 즉시 반응 (사용자 경험 향상)
- ✅ 워크플로우 단위 관리

---

### 3. 상태 변화 체크 (ΔState Detection) - 변화 필드 기반 룰

**목적**: 상태 변화 감지 시 영향받는 룰만 평가

**구현**:
```php
// state_change_detector.php (신규)
class StateChangeDetector {
    /**
     * 상태 변화 감지 및 최적화 평가
     */
    public function detectAndEvaluate(array $oldState, array $newState, string $studentId): array {
        // 변화된 필드 추출
        $changedFields = $this->getChangedFields($oldState, $newState);
        
        if (empty($changedFields)) {
            return ['changed' => false];
        }
        
        // 변화 필드에 영향받는 룰만 조회
        $affectedRules = $this->getAffectedRules($changedFields);
        
        if (empty($affectedRules)) {
            return [
                'changed' => true,
                'affected_rules' => 0,
                'message' => 'No rules affected by state change'
            ];
        }
        
        // 영향받는 룰만 평가
        $evaluationResults = [];
        foreach ($affectedRules as $ruleGroup) {
            $scenarioId = $ruleGroup['scenario_id'];
            $ruleIds = $ruleGroup['rule_ids'];
            
            $result = $this->ruleEvaluator->evaluateScenario(
                $scenarioId,
                $ruleIds,  // 영향받는 룰만
                $newState,
                'priority_first'
            );
            
            $evaluationResults[$scenarioId] = $result;
        }
        
        // 상태 재분류
        $reclassifiedState = $this->reclassifyState($newState, $evaluationResults);
        
        // 피드백 호출 (필요시)
        if ($this->shouldTriggerFeedback($evaluationResults)) {
            $this->triggerFeedback($studentId, $evaluationResults);
        }
        
        return [
            'changed' => true,
            'changed_fields' => $changedFields,
            'affected_rules' => count($affectedRules),
            'evaluation_results' => $evaluationResults,
            'reclassified_state' => $reclassifiedState
        ];
    }
    
    /**
     * 변화된 필드 추출
     */
    private function getChangedFields(array $oldState, array $newState): array {
        $changed = [];
        
        foreach ($newState as $key => $value) {
            if (!isset($oldState[$key]) || $oldState[$key] !== $value) {
                $changed[] = $key;
            }
        }
        
        return $changed;
    }
    
    /**
     * 변화 필드에 영향받는 룰 조회
     */
    private function getAffectedRules(array $changedFields): array {
        // 룰의 조건 필드와 매칭되는 룰만 반환
        // 예: stress_level이 변경되면 S3, S4 시나리오의 룰만
        $sql = "
            SELECT DISTINCT scenario_id, rule_id
            FROM mdl_alt42_rule_conditions
            WHERE condition_field IN (" . implode(',', array_fill(0, count($changedFields), '?')) . ")
            AND is_active = 1
        ";
        
        // 실제 구현 시 DB 쿼리 또는 캐시 활용
        return [];
    }
}
```

**특징**:
- ✅ 변화 필드 기반 최적화 평가
- ✅ 불필요한 룰 평가 최소화
- ✅ 상태 재분류 자동화

---

## 📋 구현 우선순위

### Phase 1: 워크플로우 이벤트 단위 평가 (즉시)
1. `event_scenario_mapper.php`에 워크플로우 그룹 정보 추가
2. `events.php`에 워크플로우 이벤트 처리 로직 추가
3. 테스트 및 검증

### Phase 2: 상태 변화 감지 최적화 (단기)
1. `state_change_detector.php` 신규 생성
2. 룰-필드 매핑 테이블 생성
3. Δstate 기반 평가 로직 구현

### Phase 3: 통합 및 최적화 (중기)
1. 3가지 체크 방식 통합
2. 성능 모니터링 및 최적화
3. 문서화 및 가이드 작성

---

## 🎯 결론

### 권장 접근 방식: **워크플로우 이벤트 단위**

**이유**:
1. ✅ **의미 있는 단위**: 실제 워크플로우와 1:1 매핑
2. ✅ **성능 최적화**: PHP 7.1 환경에서 불필요한 평가 최소화
3. ✅ **유지보수성**: 워크플로우 단위 관리로 변경 영향 범위 명확
4. ✅ **확장성**: 새로운 워크플로우 추가 용이

### 3가지 체크 방식 역할 분담

| 체크 방식 | 평가 단위 | 목적 | 빈도 |
|----------|----------|------|------|
| 주기적 체크 | 시나리오 그룹 | 전체 상태 재평가 | 30분 |
| 이벤트 체크 | 워크플로우 이벤트 | 즉시 반응 | 실시간 |
| 상태 변화 체크 | 변화 필드 기반 룰 | 최적화 평가 | 실시간 |

---

## 📝 참고사항

- PHP 7.1 환경이므로 배열 문법 주의 (`array()` vs `[]`)
- MySQL 5.7 버전 제약 고려
- 에러 메시지에 파일 위치 정보 포함 필수
- 서버 환경이므로 로컬 테스트 금지

---

**작성자**: AI Assistant  
**최종 업데이트**: 2025-01-27

