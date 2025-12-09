 


위험 진단 보고서를 작성했습니다. 주요 위험 요소는 다음과 같습니다:

## 치명적 위험 요소 (P0)

### 1. 데이터 소스 검증 부재
- `data_based_questions.js`에 `dataSources`가 정의되어 있지만, 실제 DB 존재 여부나 NULL 허용 여부를 검증하는 로직이 없습니다.
- 영향: 데이터가 없으면 빈 응답 또는 오류 발생, 잘못된 개입 실행 가능

### 2. 온톨로지 자동 생성 메커니즘 부재
- 질문 세트마다 `ontology` 배열이 정의되어 있지만, 실제 OWL 파일 생성/검증/업데이트 로직이 없습니다.
- 영향: 온톨로지가 문서에만 존재하고 실제 시스템에서 활용되지 않음

### 3. 에이전트 실행 순서 및 의존성 관리 부재
- 21개 에이전트 간 의존성이 있지만 실행 순서를 강제하는 메커니즘이 없습니다.
- 영향: 데이터 불일치, 순환 의존성 위험, 예측 불가능한 동작

## 높은 우선순위 위험 (P1)

4. 실시간 트리거 처리의 경쟁 조건: 동시 트리거 시 중복 개입 가능
5. 데이터 소스 매핑 불일치: `systemdata.md`와 실제 `dataSources` 불일치 가능
6. 에러 처리 및 복구 메커니즘 부족: 예외 발생 시 복구 로직 없음

## 중간 우선순위 위험 (P2)

7. 성능 및 확장성: 21개 에이전트 순차 실행 시 SLA 초과 가능
8. 온톨로지 일관성 검증 부재: 중복 정의 또는 충돌 가능
9. 데이터 프라이버시 및 보안: 민감한 데이터 처리 시 보안 위험

---

상세한 진단 보고서는 `alt42/orchestration/docs/RISK_ASSESSMENT_REPORT.md`에 저장했습니다.

즉시 조치가 필요한 항목:
1. 데이터 소스 검증 시스템 구축 (1주일 내)
2. 에이전트 실행 순서 강제 메커니즘 (1주일 내)
3. 에러 처리 강화 (1주일 내)

이 위험 요소들을 해결하지 않으면 잘못된 개입 실행, 시스템 불안정성 증가, 학생 경험 저하가 발생할 수 있습니다.




# AI 튜터 시스템 위험 진단 보고서

**작성일**: 2025-01-27  
**분석 대상**: 데이터 기반 질문 영역 및 온톨로지 기반 AI 튜터 시스템  
**분석 범위**: questions.html의 데이터 기반 질문, 온톨로지 생성, 에이전트 실행 시스템


---

## 📋 Executive Summary

현재 시스템은 **21개 에이전트**가 **복잡한 데이터 기반 질문**을 통해 **온톨로지 기반 의사결정**을 수행하는 구조입니다. "사람과 같은 방식으로 동작하는 AI 튜터"를 목표로 하고 있으나, 다음과 같은 **심각한 위험 요소들**이 발견되었습니다.

### 위험도 요약

| 위험 영역 | 심각도 | 영향도 | 우선순위 |
|---------|--------|--------|---------|
| 데이터 무결성 및 검증 | 🔴 높음 | 높음 | P0 |
| 온톨로지 생성 자동화 | 🔴 높음 | 높음 | P0 |
| 에이전트 간 의존성 관리 | 🟠 중간 | 높음 | P1 |
| 실시간 트리거 처리 | 🟠 중간 | 중간 | P1 |
| 에러 복구 및 안전장치 | 🟡 낮음 | 높음 | P2 |
| 성능 및 확장성 | 🟡 낮음 | 중간 | P2 |

---

## 🔴 P0: 치명적 위험 요소

### 1. 데이터 기반 질문의 데이터 소스 검증 부재

**위험 상황**:
- `data_based_questions.js`에서 각 질문마다 `dataSources` 배열이 정의되어 있음
- 예: `['concept_stage', 'pause_frequency', 'pause_stage', 'concept_progress']`
- **하지만 실제 데이터베이스에서 이 필드들이 존재하는지, NULL 값이 허용되는지 검증하는 로직이 없음**

**영향**:
```javascript
// data_based_questions.js:17
{ text: '학생은 개념이해 단계 중 어떤 부분에서 가장 자주 멈추나요?', 
  dataSources: ['concept_stage', 'pause_frequency', 'pause_stage', 'concept_progress'] }
```
- 질문 실행 시 데이터가 없으면 **빈 응답 또는 오류 발생**
- 에이전트가 **잘못된 판단**을 할 수 있음
- 학생에게 **부적절한 개입**이 실행될 위험

**위치**: 
- `alt42/orchestration/agents/agent_orchestration/data_based_questions.js`
- 각 에이전트의 `rules/data.php` 파일들

**권장 조치**:
```php
// 각 에이전트의 data.php에 추가 필요
function validateDataSources($dataSources, $studentId) {
    $missing = [];
    foreach ($dataSources as $source) {
        if (!hasData($source, $studentId)) {
            $missing[] = $source;
        }
    }
    if (!empty($missing)) {
        throw new DataSourceMissingException(
            "Missing data sources: " . implode(', ', $missing) . 
            " at " . __FILE__ . ":" . __LINE__
        );
    }
}
```

---

### 2. 온톨로지 자동 생성 메커니즘 부재

**위험 상황**:
- `data_based_questions.js`에서 각 질문 세트마다 `ontology` 배열이 정의되어 있음
- 예: `[{ name: 'ConceptWeakpoint', description: '개념 이해 취약점을 온톨로지로 표현' }]`
- **하지만 실제 OWL 파일 생성, 검증, 업데이트 로직이 없음**

**영향**:
- 온톨로지가 **문서에만 존재**하고 실제 시스템에서 활용되지 않음
- 에이전트 간 **지식 공유 불가능**
- **일관성 없는 의사결정** 발생 가능

**위치**:
- `alt42/orchestration/agents/agent_orchestration/data_based_questions.js` (모든 에이전트)
- `alt42/orchestration/agents/math topics/` (OWL 파일들은 수동 생성)

**권장 조치**:
```php
// 온톨로지 생성 및 검증 시스템 구축 필요
class OntologyGenerator {
    public function generateFromQuestionSet($questionSet) {
        // 1. 질문 세트에서 온톨로지 추출
        // 2. OWL 파일 생성
        // 3. 기존 온톨로지와 충돌 검사
        // 4. 검증 및 저장
    }
}
```

---

### 3. 에이전트 실행 순서 및 의존성 관리 부재

**위험 상황**:
- 21개 에이전트가 서로 의존성을 가짐
- 예: Agent 21 (개입실행)은 Agent 20 (개입준비)의 결과에 의존
- **하지만 실행 순서를 강제하는 메커니즘이 없음**

**영향**:
```php
// orchestrator.php:81
$decision_result = $this->executeDecision($metrics);
// Agent 20이 실행되지 않았는데 Agent 21이 실행될 수 있음
```
- **데이터 불일치** 발생
- **순환 의존성** 위험
- **예측 불가능한 동작**

**위치**:
- `alt42/orchestration/mvp_system/orchestrator.php`
- 각 에이전트의 `rules/rules.yaml`

**권장 조치**:
```yaml
# orchestration_flow.yaml 생성 필요
agent_dependencies:
  agent21:
    requires: [agent20, agent19]
    priority: 100
  agent20:
    requires: [agent16, agent17]
    priority: 90
```

---

## 🟠 P1: 높은 우선순위 위험 요소

### 4. 실시간 트리거 처리의 경쟁 조건(Race Condition)

**위험 상황**:
- Agent 21의 "데이터 트리거 발생 시" 질문에서 **즉시 개입 실행** 요구
- 여러 트리거가 동시에 발생할 경우 **중복 개입** 또는 **충돌** 가능

**영향**:
```javascript
// data_based_questions.js:1641
{ text: '침착도 하락, 필기 지연, 이탈 위험 등의 실시간 트리거가 감지되었을 때...' }
```
- 학생이 **동일한 개입을 여러 번 받을 수 있음**
- **메시지 과다** 발생 (하루 제한 초과)
- 시스템 **부하 증가**

**위치**:
- `alt42/orchestration/agents/agent21_intervention_execution/`
- `alt42/orchestration/mvp_system/execution/intervention_dispatcher.php`

**권장 조치**:
```php
// 트리거 락 메커니즘 필요
class TriggerLock {
    public function acquireLock($studentId, $triggerType, $ttl = 300) {
        // Redis 또는 DB 락 사용
        // TTL 내 중복 트리거 방지
    }
}
```

---

### 5. 데이터 소스 매핑 불일치

**위험 상황**:
- `systemdata.md`에 정의된 테이블/필드와 `data_based_questions.js`의 `dataSources`가 **일치하지 않을 수 있음**
- 예: `dataSources: ['gaze_attention_score']`가 실제 DB에 존재하는지 불명확

**영향**:
- **런타임 오류** 발생
- **데이터 누락**으로 인한 잘못된 분석

**위치**:
- `alt42/orchestration/docs/systemdata.md`
- `alt42/orchestration/agents/agent_orchestration/data_based_questions.js`

**권장 조치**:
```php
// 데이터 소스 검증 스크립트 생성
class DataSourceValidator {
    public function validateAllQuestions() {
        // 모든 질문의 dataSources를 DB 스키마와 대조
        // 불일치 항목 리포트 생성
    }
}
```

---

### 6. 에러 처리 및 복구 메커니즘 부족

**위험 상황**:
- `orchestrator.php`에서 예외는 catch하지만 **복구 로직이 없음**
- 에이전트 실행 실패 시 **부분적 상태**로 남을 수 있음

**영향**:
```php
// orchestrator.php:159
catch (Exception $e) {
    $result['success'] = false;
    $result['errors'][] = $e->getMessage();
    // 복구 시도 없음
}
```
- **데이터 불일치** 발생
- **재시도 불가능**
- **디버깅 어려움**

**위치**:
- `alt42/orchestration/mvp_system/orchestrator.php`
- 각 에이전트의 실행 로직

**권장 조치**:
```php
// 트랜잭션 및 롤백 메커니즘 추가
class AgentExecutor {
    public function executeWithRollback($agentId, $studentId) {
        $this->db->beginTransaction();
        try {
            // 실행 로직
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Rolled back", $e);
            throw $e;
        }
    }
}
```

---

## 🟡 P2: 중간 우선순위 위험 요소

### 7. 성능 및 확장성 문제

**위험 상황**:
- 21개 에이전트가 순차 실행될 경우 **SLA 3분 초과** 가능
- 각 에이전트가 Python 스크립트를 호출하면 **오버헤드 증가**

**영향**:
```php
// orchestrator.php:206
$command = "python3 $python_script $json_input 2>&1";
$output = shell_exec($command);
// 각 에이전트마다 Python 프로세스 생성 = 느림
```
- **응답 지연**
- **서버 부하 증가**
- **동시 사용자 처리 어려움**

**권장 조치**:
- 병렬 실행 가능한 에이전트 식별
- Python 프로세스 풀링
- 캐싱 전략 도입

---

### 8. 온톨로지 일관성 검증 부재

**위험 상황**:
- 각 에이전트가 독립적으로 온톨로지를 정의
- **중복 정의** 또는 **충돌** 가능

**영향**:
- 같은 개념이 다른 이름으로 정의됨
- **의미 불일치** 발생

**권장 조치**:
- 중앙 온톨로지 레지스트리 구축
- 온톨로지 병합 및 검증 도구 개발

---

### 9. 데이터 프라이버시 및 보안

**위험 상황**:
- 학생의 감정, 학습 패턴 등 **민감한 데이터** 처리
- **로그에 개인정보 노출** 가능성

**영향**:
- **개인정보 보호법 위반** 위험
- **데이터 유출** 가능성

**권장 조치**:
- 데이터 암호화
- 로그 마스킹
- 접근 제어 강화

---

## 📊 위험 요소 종합 분석

### 데이터 흐름 위험도

```
[데이터 수집] → [검증 없음] → [에이전트 실행] → [온톨로지 없음] → [의사결정] → [실행]
     ✅              ❌              ⚠️              ❌              ⚠️          ⚠️
```

### 의존성 위험도

```
Agent 01 (온보딩)
    ↓
Agent 05 (학습감정) ← Agent 04 (취약점검사)
    ↓                    ↓
Agent 07 (상호작용타게팅) ← Agent 06 (교사피드백)
    ↓                    ↓
Agent 16 (상호작용준비) → Agent 19 (상호작용컨텐츠)
    ↓                    ↓
Agent 20 (개입준비) → Agent 21 (개입실행)
```

**문제점**: 의존성 그래프가 복잡하지만 **실행 순서 보장 메커니즘 없음**

---

## 🎯 권장 조치 우선순위

### 즉시 조치 필요 (1주일 내)

1. **데이터 소스 검증 시스템 구축**
   - 모든 `dataSources`를 DB 스키마와 대조
   - 누락된 데이터 소스 식별 및 문서화
   - 런타임 검증 로직 추가

2. **에이전트 실행 순서 강제**
   - `orchestration_flow.yaml` 생성
   - 의존성 그래프 검증
   - 순환 의존성 탐지

3. **에러 처리 강화**
   - 트랜잭션 및 롤백 메커니즘
   - 상세한 에러 로깅 (파일:라인 정보 포함)
   - 자동 복구 시도

### 단기 조치 필요 (1개월 내)

4. **온톨로지 생성 자동화**
   - 질문 세트에서 온톨로지 추출
   - OWL 파일 자동 생성
   - 온톨로지 검증 및 병합

5. **실시간 트리거 락 메커니즘**
   - 중복 트리거 방지
   - 메시지 제한 강제
   - 우선순위 큐 구현

6. **성능 최적화**
   - 병렬 실행 가능 에이전트 식별
   - 캐싱 전략 도입
   - Python 프로세스 풀링

### 중장기 조치 (3개월 내)

7. **온톨로지 통합 시스템**
   - 중앙 온톨로지 레지스트리
   - 온톨로지 버전 관리
   - 자동 일관성 검증

8. **모니터링 및 알림 시스템**
   - 실시간 대시보드
   - 이상 징후 탐지
   - 자동 알림

---

## 📝 결론

현재 시스템은 **"사람과 같은 방식으로 동작하는 AI 튜터"**라는 목표를 달성하기 위한 **견고한 기반**을 가지고 있으나, 다음과 같은 **치명적 위험 요소들**이 발견되었습니다:

1. **데이터 검증 부재**: 질문 실행 시 필요한 데이터가 없을 수 있음
2. **온톨로지 자동화 부재**: 온톨로지가 문서에만 존재하고 실제 활용 안 됨
3. **의존성 관리 부재**: 에이전트 실행 순서가 보장되지 않음

이러한 위험 요소들을 해결하지 않으면:
- **잘못된 개입 실행** 가능성
- **시스템 불안정성** 증가
- **학생 경험 저하**

**즉시 조치가 필요합니다.**

---

**문서 버전**: 1.0  
**다음 검토일**: 2025-02-03  
**담당자**: 개발팀 리더

