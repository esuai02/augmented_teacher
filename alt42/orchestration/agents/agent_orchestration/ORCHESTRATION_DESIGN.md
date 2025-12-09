# 🎯 22개 에이전트 오케스트레이션 설계

> **생성일**: 2025-12-06  
> **버전**: 1.0  
> **목적**: 미완성 에이전트들의 순환적 완성 전략 및 오케스트레이션 설계

---

## 📊 1. 에이전트 상태 요약

### 완성도 현황

| Phase | 에이전트 | 완성도 | 표준화 | 우선순위 |
|-------|---------|-------|--------|---------|
| **Phase 1** | Agent 01 온보딩 | 95% | ✅ | - |
| | Agent 02 시험일정 | 40% | ❌ | 🔴 HIGH |
| | Agent 03 목표분석 | 60% | ⚠️ | 🟡 MEDIUM |
| | Agent 04 취약점검사 | 80% | ✅ | - |
| | Agent 05 학습감정 | 95% | ✅ | - |
| | Agent 06 교사피드백 | 40% | ❌ | 🟡 MEDIUM |
| **Phase 2** | Agent 07 상호작용타겟 | 80% | ⚠️ | 🟡 MEDIUM |
| | Agent 08 침착도 | 95% | ✅ | - |
| | Agent 09 학습관리 | 95% | ✅ | - |
| | Agent 10 개념노트 | 60% | ⚠️ | 🟡 MEDIUM |
| | Agent 11 문제노트 | 95% | ✅ | - |
| | Agent 12 휴식루틴 | 95% | ✅ | - |
| | Agent 13 학습이탈 | 95% | ✅ | - |
| **Phase 3** | Agent 14 현재위치 | 95% | ✅ | - |
| | Agent 15 문제재정의 | 95% | ✅ | - |
| | Agent 16 상호작용준비 | 95% | ✅ | - |
| | Agent 17 잔여활동 | 95% | ✅ | - |
| | Agent 18 시그너처루틴 | 95% | ✅ | - |
| | Agent 19 상호작용컨텐츠 | 95% | ✅ | - |
| **Phase 4** | Agent 20 개입준비 | 95% | ✅ | - |
| | Agent 21 개입실행 | 95% | ✅ | - |
| | Agent 22 모듈개선 | 95% | ✅ | - |

---

## 🕐 2. 시간 스케일별 오케스트레이션

### 2.1 실시간 (Real-time: 0-500ms)

**Critical Path**: 긴급 개입이 필요한 상황

```
Agent 05 (학습감정)
    │ emotion_changed
    ▼
Agent 07 (상호작용타겟) ←── Agent 13 (학습이탈): dropout_risk
    │ target_determined
    ▼
Agent 20 (개입준비)
    │ intervention_ready
    ▼
Agent 21 (개입실행)
    │ intervention_complete
    ▼
Agent 05, 14 (피드백 루프)
```

**트리거 조건**:
- `emotion_changed` (frustrated, anxious, discouraged)
- `dropout_risk_detected` (risk_score >= 0.7)

---

### 2.2 단기 (Short-term: 500ms - 5min)

**모니터링 및 분석 루프**

```
                    ┌─────────────────────────────────┐
                    │ activity_completed              │
                    │ problem_submitted               │
                    └─────────────────────────────────┘
                                  │
                    ┌─────────────┼─────────────┐
                    ▼             ▼             ▼
             ┌──────────┐  ┌──────────┐  ┌──────────┐
             │ Agent 08 │  │ Agent 12 │  │ Agent 14 │
             │ 침착도    │  │ 휴식루틴  │  │ 현재위치  │
             └──────────┘  └──────────┘  └──────────┘
                    │             │             │
                    └─────────────┼─────────────┘
                                  ▼
                           ┌──────────┐
                           │ Agent 15 │
                           │ 문제재정의 │
                           └──────────┘
```

---

### 2.3 중기 (Medium-term: 5min - 1hour)

**학습 세션 관리 루프**

```
session_start / pomodoro_complete
              │
              ▼
       ┌──────────┐
       │ Agent 09 │ ◀─────────────────┐
       │ 학습관리  │                   │
       └──────────┘                   │
              │                       │
    ┌─────────┼─────────┐             │
    ▼         ▼         ▼             │
┌──────┐ ┌──────┐ ┌──────┐            │
│Ag 04 │ │Ag 10 │ │Ag 17 │            │
│취약점 │ │개념노트│ │잔여활동│            │
└──────┘ └──────┘ └──────┘            │
    │         │         │             │
    └─────────┼─────────┘             │
              ▼                       │
       ┌──────────┐                   │
       │ Agent 18 │ ──────────────────┘
       │시그너처루틴│
       └──────────┘
```

---

### 2.4 장기 (Long-term: Daily/Weekly)

**계획 및 전략 루프**

```
weekly_start / exam_announced / profile_updated
                        │
                        ▼
                 ┌──────────┐
                 │ Agent 01 │
                 │ 온보딩    │
                 └──────────┘
                        │
           ┌────────────┼────────────┐
           ▼            ▼            ▼
    ┌──────────┐ ┌──────────┐ ┌──────────┐
    │ Agent 02 │ │ Agent 03 │ │ Agent 06 │
    │ 시험일정  │ │ 목표분석  │ │ 교사피드백 │
    └──────────┘ └──────────┘ └──────────┘
           │            │            │
           └────────────┼────────────┘
                        ▼
                 ┌──────────┐
                 │ Agent 04 │
                 │ 취약점검사 │
                 └──────────┘
```

---

### 2.5 연속 (Continuous: Background)

**자가 진화 루프**

```
┌─────────────────────────────────────────────────────┐
│                  Agent 22 (모듈개선)                  │
│                                                      │
│  트리거: system_idle, performance_degradation,       │
│          error_pattern, weekly_review               │
│                                                      │
│  입력: 모든 에이전트의 실행 로그, 룰 파일, 성능 지표    │
│  출력: 개선 제안, 룰 업데이트, 업그레이드 문서          │
│                                                      │
│  ┌────────────────────────────────────────────────┐ │
│  │  Analysis → Identify Weakness → Propose Fix   │ │
│  │      ↑                              ↓         │ │
│  │      └──────── Verify & Apply ◀─────┘         │ │
│  └────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────┘
```

---

## 🎯 3. 사건 기반 오케스트레이션

### 3.1 학생 로그인 / 수업 시작

```yaml
Event: student_session_start
Sequence:
  - Agent 01: 프로필 로드 (0ms)
  - Agent 14: 현재 위치 계산 (100ms)
  - Agent 02: 시험일정 확인 (200ms)
  - Agent 03: 오늘 목표 확인 (300ms)
  - Agent 07: 초기 상호작용 결정 (400ms)
Timeout: 500ms
Fallback: 기본 학습 화면 표시
```

### 3.2 문제 풀이 완료

```yaml
Event: problem_completed
Sequence:
  - Agent 11: 정오답 기록 (0ms)
  - Agent 08: 침착도 업데이트 (50ms)
  - Agent 04: 취약점 패턴 분석 (100ms)
  - Agent 14: 진도 업데이트 (150ms)
Parallel:
  - Agent 10: 개념 이해도 분석
  - Agent 15: 문제 재정의 필요성 판단
Timeout: 200ms
```

### 3.3 감정 위기 감지

```yaml
Event: emotional_crisis_detected
Priority: CRITICAL
Sequence:
  - Agent 05: 감정 상태 확인 (0ms, 즉시)
  - Agent 07: 긴급 상호작용 타겟 결정 (50ms)
  - Agent 16: 위로/격려 세계관 선택 (100ms)
  - Agent 19: 컨텐츠 생성 (150ms)
  - Agent 20: 개입 준비 (200ms)
  - Agent 21: 즉시 개입 실행 (250ms)
Timeout: 300ms (하드 데드라인)
Escalation: 교사 알림
```

### 3.4 학습 이탈 위험 감지

```yaml
Event: dropout_risk_high
Priority: CRITICAL
Condition: risk_score >= 0.7
Sequence:
  - Agent 13: 이탈 원인 분석 (0ms)
  - Agent 12: 휴식 필요성 판단 (50ms)
  - Agent 07: Quick Win 전략 결정 (100ms)
  - Agent 19: 재진입 유도 컨텐츠 생성 (150ms)
  - Agent 21: 개입 실행 (200ms)
Fallback: 쉬운 문제 제시 또는 휴식 제안
```

### 3.5 시험 8주 전 진입

```yaml
Event: exam_d_minus_56
Priority: HIGH
Trigger: D-56일 도달 시 (매일 체크)
Sequence:
  - Agent 02: 전체 시험 전략 수립 (0ms)
  - Agent 03: 목표 재설정 (500ms)
  - Agent 17: 잔여 활동 우선순위 재조정 (1000ms)
  - Agent 09: 학습 계획 업데이트 (1500ms)
Output: 시험 대비 학습 로드맵
Notify: 학생, 교사
```

---

## 🔄 4. 순환적 완성 전략

### 4.1 Cycle 1: Foundation Fix (Week 1-2)

**목표**: Agent 02 표준화 완료

```bash
# 작업 체크리스트
[ ] checkDataAccessUsage() 함수 추가
[ ] DB 실제 데이터 확인 로직 추가
[ ] inRulesNotInDataAccess 계산 로직 변경
[ ] HTML 출력 섹션 추가
[ ] 린터 에러 확인

# 테스트
[ ] Agent 02 → Agent 09 연결 테스트
[ ] Agent 02 → Agent 17 연결 테스트
[ ] exam_plan_created 이벤트 전파 테스트

# 장기 영향 검증
[ ] Phase 2 전체 에이전트 테스트
[ ] 시험일정 기반 학습계획 생성 E2E 테스트
```

### 4.2 Cycle 2: Hub Stabilization (Week 3-4)

**목표**: Agent 07, Agent 06 완성

```bash
# Agent 07 작업
[ ] 8개 입력 에이전트 연결 안정화
[ ] 우선순위 결정 알고리즘 검증
[ ] target_determined 이벤트 정확성 테스트

# Agent 06 작업
[ ] 교사 피드백 수집 로직 완성
[ ] 피드백 전파 메커니즘 구현
[ ] feedback_received 이벤트 테스트

# 통합 테스트
[ ] Agent 16 → Agent 19 파이프라인 테스트
[ ] 상호작용 콘텐츠 생성 품질 검증
```

### 4.3 Cycle 3: Analysis Enhancement (Week 5-6)

**목표**: Agent 03, Agent 10 완성

```bash
# Agent 03 작업
[ ] 페르소나 시스템 통합 완성
[ ] 목표 정렬도 계산 정확성 검증
[ ] goals_updated 이벤트 테스트

# Agent 10 작업
[ ] 화이트보드 데이터 분석 고도화
[ ] 개념 이해도 분석 결과 검증
[ ] concept_updated 이벤트 테스트

# 통합 테스트
[ ] Agent 15 출력 품질 향상 확인
[ ] 전체 학습 경로 추천 정확도 측정
```

### 4.4 Cycle 4: Full Integration (Week 7-8)

**목표**: 전체 시스템 통합 테스트

```bash
# E2E 시나리오 테스트
[ ] 학생 세션 시뮬레이션
    (로그인 → 학습 → 질문 → 개입 → 종료)
[ ] 감정 위기 시나리오 E2E 테스트
[ ] 시험 8주 전 전략 수립 시나리오 테스트

# 성능 테스트
[ ] 동시 접속자 50명 시뮬레이션
[ ] 응답 시간 500ms 이내 확인
[ ] 메모리 사용량 모니터링

# 자가 진화 테스트
[ ] Agent 22 개선 제안 품질 검토
[ ] 시스템 자가 진화 능력 평가
```

---

## 📈 5. 성능 모니터링 지표

### 5.1 응답 시간 목표

| 시간 스케일 | 목표 | 경고 | 위험 |
|-----------|------|------|------|
| Real-time | < 500ms | 500-1000ms | > 1000ms |
| Short-term | < 5sec | 5-10sec | > 10sec |
| Medium-term | < 30sec | 30-60sec | > 60sec |
| Long-term | < 5min | 5-10min | > 10min |

### 5.2 정확도 목표

| 에이전트 유형 | 목표 정확도 |
|-------------|-----------|
| 감정 분석 (Agent 05) | > 85% |
| 취약점 검사 (Agent 04) | > 90% |
| 이탈 예측 (Agent 13) | > 80% |
| 상호작용 타겟팅 (Agent 07) | > 85% |

---

## 🔧 6. 장기적 구조 안정성 검증

### 6.1 의존성 체인 무결성 테스트

```php
// tests/structural/dependency_chain_integrity.php

function testDependencyChainIntegrity() {
    $agents = getAllAgents();
    
    foreach ($agents as $agent) {
        // 직접 의존성 확인
        $directDeps = $agent->getDirectDependencies();
        foreach ($directDeps as $dep) {
            assert($dep->isAvailable(), 
                "Agent {$agent->id}: Dependency {$dep->id} not available");
        }
        
        // 순환 의존성 감지
        $visited = [];
        assert(!hasCyclicDependency($agent, $visited),
            "Agent {$agent->id}: Cyclic dependency detected");
    }
}
```

### 6.2 성능 회귀 테스트

```php
// tests/structural/performance_regression.php

function testPerformanceRegression() {
    $baseline = loadPerformanceBaseline();
    $current = measureCurrentPerformance();
    
    foreach ($current as $metric => $value) {
        $tolerance = 1.1; // 10% 허용
        assert($value <= $baseline[$metric] * $tolerance,
            "Performance regression: {$metric} exceeds baseline by " .
            round(($value / $baseline[$metric] - 1) * 100, 2) . "%");
    }
}
```

### 6.3 데이터 일관성 테스트

```php
// tests/structural/data_consistency.php

function testDataConsistency() {
    $eventTypes = [
        'profile_updated',
        'emotion_changed',
        'plan_created',
        'intervention_complete'
    ];
    
    foreach ($eventTypes as $event) {
        $source = getEventSource($event);
        $consumers = getEventConsumers($event);
        
        foreach ($consumers as $consumer) {
            $sourceData = $source->getLastEmittedData($event);
            $receivedData = $consumer->getReceivedData($event);
            
            assert($sourceData === $receivedData,
                "Data inconsistency: {$event} from {$source->id} to {$consumer->id}");
        }
    }
}
```

---

## 📚 7. 참고 문서

- `AGENT_INTERDEPENDENCY_DOCUMENTATION.md`: 에이전트 간 상세 의존성
- `NEXT_WORK_PLAN.md`: 현재 작업 계획
- `file_flow.md`: 에이전트별 파일 구조
- `ui.map.yaml`: UI-코드 매핑 (프로젝트 루트)

---

## ✅ 8. 변경 이력

| 날짜 | 버전 | 변경 내용 | 작성자 |
|-----|------|---------|--------|
| 2025-12-06 | 1.0 | 초기 문서 생성 | Claude |


