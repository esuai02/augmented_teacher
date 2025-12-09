# 🚨 Quantum IDE 구현 Critical 문제 목록

> **Intervention Decision Engine(IDE) 구현 시 발생 가능한 17개 핵심 문제와 해결 방향**

**버전**: 1.1  
**작성일**: 2025-12-09  
**최종 수정**: 2025-12-09  
**상태**: 🔴 분석 완료, 해결책 설계 필요

### 📚 관련 문서

| 문서 | 역할 | 바로가기 |
|------|------|---------|
| [00-INDEX.md](./00-INDEX.md) | 문서 허브 | 전체 탐색 |
| [SYSTEM_STATUS.yaml](./SYSTEM_STATUS.yaml) | SSOT | IDE critical_issues 섹션 |
| [quantum-orchestration-design.md](./quantum-orchestration-design.md) | IDE 설계 | **§5.4 IDE 7단계** |
| [quantum-learning-model.md](./quantum-learning-model.md) | 이론 기반 | Part VIII Brain/Mind/Mouth |
| [wavefunction-agent-mapping.md](./wavefunction-agent-mapping.md) | 매핑 규칙 | 파동함수↔문제 연결 |
| [PRD](../../../tasks/0005-prd-quantum-modeling-completion.md) | 구현 로드맵 | **Phase 4: Critical Issues** |

---

## 📋 목차

1. [개요](#개요)
2. [문제 분류 체계](#문제-분류-체계)
3. [Critical Issues (17개)](#critical-issues-17개)
   - [타이밍 문제 (3개)](#-타이밍-문제-timing-issues)
   - [우선순위 충돌 문제 (3개)](#-우선순위-충돌-문제-priority-conflicts)
   - [계산 비용 과대 문제 (2개)](#-계산-비용-과대-문제-computational-cost)
   - [상호작용 과잉 문제 (2개)](#-상호작용-과잉-문제-over-intervention)
   - [예측 실패 문제 (2개)](#-예측-실패-문제-prediction-failures)
   - [파동함수 불안정 문제 (2개)](#-파동함수-불안정-문제-wavefunction-instability)
   - [데이터 매핑 문제 (2개)](#-데이터-매핑-문제-data-mapping)
   - [시스템 충돌 문제 (1개)](#-시스템-충돌-문제-system-conflicts)
4. [우선순위 매트릭스](#우선순위-매트릭스)
5. [해결 로드맵](#해결-로드맵)
6. [관련 파동함수 연결](#관련-파동함수-연결)

---

## 개요

### 시스템 특성

이 시스템은 **"다층 의사결정 + 실시간 데이터 반응성"** 구조를 가진다:

```
┌─────────────────────────────────────────────────────────┐
│                    Quantum IDE                           │
├─────────────────────────────────────────────────────────┤
│  에이전트 레이어 (22개)                                   │
│    └─ 초 단위 (Agent 13: 이탈)                           │
│    └─ 분 단위 (Agent 10/11: 노트류)                      │
│    └─ 일 단위 (Agent 02: 시험일정)                       │
├─────────────────────────────────────────────────────────┤
│  파동함수 레이어 (13종)                                   │
│    └─ ψ_core, ψ_align, ψ_fluct, ψ_affect, ...           │
├─────────────────────────────────────────────────────────┤
│  의사결정 레이어 (7단계 파이프라인)                        │
│    └─ Trigger → BCE → Scenario → Priority → ...         │
└─────────────────────────────────────────────────────────┘
```

### 문제 발생 원인

1. **시간 스케일 불일치**: 에이전트별 반응 속도 차이 (초/분/일)
2. **다중 의존성**: 파동함수 간 상호 영향
3. **실시간 제약**: 밀리초 단위 응답 요구
4. **학생 변동성**: 개인차, 상황 변화에 따른 예측 불확실성

---

## 문제 분류 체계

| 분류 | 문제 수 | 영향 범위 | 긴급도 |
|------|:------:|----------|:------:|
| ⏱️ 타이밍 | 3개 | IDE 전체 | 🔴 Critical |
| ⚔️ 우선순위 충돌 | 3개 | 시나리오 선택 | 🔴 Critical |
| 💻 계산 비용 | 2개 | 성능/확장성 | 🟡 High |
| 🔄 상호작용 과잉 | 2개 | 학생 경험 | 🟡 High |
| 🎯 예측 실패 | 2개 | 개입 품질 | 🟡 High |
| 🌊 파동함수 불안정 | 2개 | 시스템 안정성 | 🔴 Critical |
| 🔗 데이터 매핑 | 2개 | 통합/호환성 | 🟢 Medium |
| 💥 시스템 충돌 | 1개 | 시스템 무결성 | 🔴 Critical |

---

## Critical Issues (17개)

### ⏱️ 타이밍 문제 (Timing Issues)

#### #01 파동함수(ψ) 계산과 에이전트 신호 사이의 시간적 불일치

**문제**

| 에이전트 | 시간 스케일 | 업데이트 빈도 |
|---------|:----------:|:------------:|
| Agent 13 (이탈) | 초 단위 | ~1초 |
| Agent 10/11 (노트류) | 분 단위 | ~1분 |
| Agent 02 (시험일정) | 일 단위 | ~1일 |

이들을 동시에 읽어 **동기 시점(t)**을 만들지 않으면:
- `ψ_align`, `ψ_fluct`, `ψ_predict` 값이 계속 튄다

**결과**
- 개입이 불안정하게 발동
- 예측 그래프가 고빈도 노이즈 발생

**해결 방향**
```python
# Timestamp 정규화 + sliding window
class TemporalNormalizer:
    windows = {
        'immediate': 5,      # 초 단위 → 5초 윈도우
        'short_term': 60,    # 분 단위 → 1분 윈도우
        'long_term': 3600    # 일 단위 → 1시간 윈도우
    }

    def normalize_to_epoch(self, agent_signals):
        """모든 에이전트 신호를 공통 시점으로 정규화"""
        pass
```

**연관 파동함수**: ψ_align, ψ_fluct, ψ_predict
**연관 에이전트**: 전체 (특히 02, 10, 11, 13)

---

#### #15 상황 전환 타이밍 문제

**문제**

개입은 **타이밍이 거의 모든 걸 결정**함:

| 나쁜 타이밍 | 결과 |
|-----------|------|
| 제출 직전 개입 | 집중 방해, 흐름 차단 |
| 문제 읽기 후 0.2초 만에 개입 | 사고 방해 |
| 풀이에 몰입했는데 방향 재정렬 개입 | 학생 거부감 ↑ |

**결과**
- 학생 경험 저하
- 개입 효과 역효과

**해결 방향**
```python
class InterventionTimingGuard:
    """개입 타이밍 보호 장치"""

    safe_windows = {
        'after_submission': (0, 5),      # 제출 후 0~5초
        'after_reading': (10, 30),       # 읽기 후 10~30초
        'during_break': (0, float('inf'))  # 휴식 중 항상
    }

    blocked_states = [
        'active_solving',      # 활발한 풀이 중
        'deep_reading',        # 깊은 읽기 중
        'input_in_progress'    # 입력 중
    ]
```

**연관 파동함수**: ψ_core, ψ_affect
**연관 에이전트**: Agent 13, 08, 07

---

#### #17 서버 부하 관리 실패 시 개입 배치 타이밍 완전 붕괴

**문제**

개입은 **밀리초 단위 처리**가 필요하지만, 서버가 **400ms 지연**되면:
- 시나리오가 엉뚱한 타이밍에 뜸
- 학생 경험 망가짐

**결과**
- 개입 타이밍 완전 무효화
- 시스템 신뢰성 저하

**해결 방향**
```python
class ServerLoadManager:
    """서버 부하 관리자"""

    latency_thresholds = {
        'optimal': 50,      # <50ms: 정상 개입
        'degraded': 200,    # 50~200ms: 간소화 개입
        'critical': 400     # >400ms: 개입 지연/취소
    }

    def adaptive_intervention(self, latency_ms):
        if latency_ms > self.latency_thresholds['critical']:
            return self.defer_or_cancel()
        elif latency_ms > self.latency_thresholds['degraded']:
            return self.simplified_intervention()
        else:
            return self.full_intervention()
```

**연관 파동함수**: 전체
**연관 에이전트**: 전체

---

### ⚔️ 우선순위 충돌 문제 (Priority Conflicts)

#### #03 상호작용 시나리오 우선순위 점수(Priority Score)가 동률 또는 0에 수렴

**문제**

| 발생 원인 | 결과 |
|----------|------|
| α1~α5 가중치 조합 → 비슷한 점수 | 시나리오 선택 불가 |
| 필수조건이 엄격 → 후보군 0개 | 개입 불가 |

```python
# 현재 점수 계산
priority_score = (
    α1 * state_match +      # 상태 매칭
    α2 * severity +         # 긴급도
    α3 * success_history +  # 성공 이력
    α4 * student_pref +     # 학생 선호
    α5 * load_balance       # 부하 균형
)
# 문제: 모든 시나리오가 0.5~0.6 대에 몰림
```

**해결 방향**
```python
class PriorityResolver:
    """우선순위 해결기"""

    def resolve_tie(self, candidates: List[Scenario]) -> Scenario:
        # 1. Fallback 시나리오
        if not candidates:
            return self.get_fallback_scenario()

        # 2. 동점 처리: 랜덤화 10% injection
        if self.is_tied(candidates):
            return self.random_select_with_weight(candidates)

        # 3. 강화학습 기반 선택 (장기)
        return self.rl_select(candidates)
```

**연관 파동함수**: ψ_predict
**연관 에이전트**: Agent 07, 15, 17

---

#### #10 개입 시나리오 간 중복

**문제**

아래 시나리오들이 비슷한 순간에 동시 추천됨:

| 시나리오 | 의도 | 실제 |
|---------|------|------|
| "개념 재정의" | 오개념 교정 | 중복 |
| "핵심 힌트 제공" | 문제 해결 지원 | 중복 |
| "풀이 방향 재정렬" | 방향 제시 | 중복 |

**해결 방향**
```python
class ScenarioDeduplicator:
    """시나리오 중복 제거기"""

    def deduplicate(self, scenarios: List[Scenario]) -> List[Scenario]:
        # 1. Taxonomy 기반 분류
        categorized = self.categorize_by_taxonomy(scenarios)

        # 2. Semantic similarity 기반 dedupe
        unique = []
        for s in scenarios:
            if not self.is_semantically_similar(s, unique, threshold=0.8):
                unique.append(s)

        return unique
```

**연관 파동함수**: ψ_core, ψ_tunnel
**연관 에이전트**: Agent 10, 11, 14, 15

---

#### #11 필수조건이 너무 엄격해서 개입이 안 되는 상황

**문제**

예: 다음 조건이 **모두** 충족되어야 실행:
- `ψ_tunnel < 0.4`
- `ψ_affect.ξ > 0.3`
- `working_memory > 0.5`

→ 사실상 실행 불가 (조건 충족 확률 < 5%)

**해결 방향**
```python
class FlexiblePrerequisite:
    """유연한 필수조건 체크"""

    def check_with_fallback(self, conditions: List[Condition]) -> bool:
        # 1. 모든 조건 충족 → 최적 시나리오
        if all(c.check() for c in conditions):
            return 'optimal'

        # 2. 핵심 조건만 충족 → 간소화 시나리오
        core_conditions = [c for c in conditions if c.is_core]
        if all(c.check() for c in core_conditions):
            return 'simplified'

        # 3. 아무것도 안 됨 → fallback
        return 'fallback'
```

**연관 파동함수**: ψ_tunnel, ψ_affect
**연관 에이전트**: Agent 08, 09, 14

---

### 💻 계산 비용 과대 문제 (Computational Cost)

#### #06 Agent Entanglement에서 실시간 업데이트 시 그래프 부하 과다

**문제**

```
22×22 Matrix = 484개 셀
실시간 업데이트 = 초당 10회 이상
→ CPU 부담 강함 (특히 웹 LMS 환경)
```

**해결 방향**
```python
class SparseEntanglementMap:
    """희소 얽힘 맵"""

    def __init__(self):
        self.sparse_matrix = {}  # {(i,j): weight} 형태
        self.frozen_edges = set()  # 변화 없는 간선

    def update(self, agent_i: int, agent_j: int, weight: float):
        key = (agent_i, agent_j)
        if key in self.frozen_edges:
            return  # 업데이트 스킵

        self.sparse_matrix[key] = weight

    def freeze_stable_edges(self, stability_threshold: float = 0.95):
        """안정적인 간선 동결"""
        for key, weight in self.sparse_matrix.items():
            if self.variance(key) < stability_threshold:
                self.frozen_edges.add(key)
```

**연관 파동함수**: ψ_entangle
**연관 에이전트**: 전체 (22×22)

---

#### #07 Hamiltonian 계산이 LMS 환경에서 너무 무겁다

**문제**

```
64차원 StateVector × 22차원 AgentActivation × iteration 100
= 수백만 연산/초
→ Web LMS에서는 말도 안 되는 CPU 사용
```

**해결 방향**
```python
class LightweightHamiltonian:
    """경량 해밀토니안"""

    # 1. 차원 압축: 64D → 16D
    compressed_dimensions = 16

    # 2. 텐서 연산 제거
    use_simple_matrix = True

    # 3. 계산 빈도 감소
    recompute_interval = 300  # 5분마다 재계산

    def evolve_lightweight(self, state: np.array) -> np.array:
        if self.should_recompute():
            return self.full_evolution(state)
        else:
            return self.cached_evolution(state)
```

**연관 파동함수**: 전체
**연관 에이전트**: 전체

---

### 🔄 상호작용 과잉 문제 (Over-Intervention)

#### #08 이탈(Agent13) 조기 감지가 너무 민감하면 개입 난사 발생

**문제**

| 감지 조건 | 문제점 |
|----------|-------|
| 시선이 2초만 잠시 벗어나도 이탈 감지 | 과민 반응 |
| 클릭 딜레이가 길어도 이탈로 판단 | 오탐 |

**결과**
- 학생이 "귀찮다"는 반응
- 개입 효과 감소

**해결 방향**
```python
class DriftDetectionCalibrator:
    """이탈 감지 보정기"""

    thresholds = {
        'gaze_loss': {
            'min_duration': 5,     # 최소 5초
            'confidence': 0.8      # 80% 확신
        },
        'click_delay': {
            'min_delay': 30,       # 최소 30초
            'context_aware': True  # 문맥 고려
        }
    }

    def is_real_drift(self, signals: Dict) -> Tuple[bool, float]:
        """실제 이탈 여부 판단"""
        # 다중 신호 종합
        score = self.multi_signal_fusion(signals)
        return score > self.threshold, score
```

**연관 파동함수**: ψ_focus
**연관 에이전트**: Agent 13

---

#### #12 학생이 일부러 비정상 행동을 하여 개입을 유도하는 경우

**문제**

| 악용 패턴 | 의도 |
|----------|------|
| 의도적 오개념 패턴 반복 | 힌트 획득 |
| 억지 이탈 발생 | 개입 유도 |

**해결 방향**
```python
class AnomalyDetector:
    """이상 행동 탐지기"""

    def detect_gaming(self, student_id: int, behavior_history: List) -> bool:
        # 1. 패턴 반복 감지
        if self.repetition_rate(behavior_history) > 0.7:
            return True

        # 2. 시간 기반 이상치 감지
        if self.time_anomaly(behavior_history):
            return True

        # 3. 성과 대비 개입 빈도 비교
        if self.intervention_outcome_mismatch(student_id):
            return True

        return False
```

**연관 파동함수**: ψ_pattern
**연관 에이전트**: Agent 12, 13

---

### 🎯 예측 실패 문제 (Prediction Failures)

#### #09 정서 기반 판단(ψ_affect)이 감정 스케일 과도 의존

**문제**

불안/anxiety가 조금만 올라가도 정서안정 개입이 발동하는 문제

```python
# 현재 로직 (과민)
if ψ_affect.anxiety > 0.3:  # 너무 낮은 임계값
    trigger_emotional_support()
```

**해결 방향**
```python
class AffectScaleNormalizer:
    """정서 스케일 정규화기"""

    # 개인별 기준선 설정
    def get_personal_baseline(self, student_id: int) -> Dict:
        history = self.get_affect_history(student_id)
        return {
            'anxiety': np.percentile(history['anxiety'], 50),
            'frustration': np.percentile(history['frustration'], 50)
        }

    def is_significant_change(self, current: float, baseline: float) -> bool:
        """기준선 대비 유의미한 변화인지 판단"""
        return abs(current - baseline) > 1.5 * self.std_deviation
```

**연관 파동함수**: ψ_affect
**연관 에이전트**: Agent 08, 09

---

#### #13 개입 적절성(Receptivity Prediction) 모델의 학습 부족

**문제**

- 초기에는 데이터가 거의 없음
- 예측 품질 낮음 → 개입 품질이 랜덤해진다

**해결 방향**
```python
class ReceptivityPredictor:
    """수용성 예측기"""

    def predict_with_cold_start(self, student_id: int) -> float:
        # 1. 해당 학생 데이터가 충분한지 확인
        data_count = self.get_student_data_count(student_id)

        if data_count < 10:
            # Cold start: 유사 학생 군집 활용
            cluster = self.find_similar_cluster(student_id)
            return self.cluster_average(cluster)

        elif data_count < 50:
            # Warm up: 개인 + 군집 혼합
            personal = self.personal_prediction(student_id)
            cluster = self.cluster_prediction(student_id)
            weight = data_count / 50
            return weight * personal + (1-weight) * cluster

        else:
            # 충분한 데이터: 개인 모델
            return self.personal_prediction(student_id)
```

**연관 파동함수**: ψ_predict
**연관 에이전트**: Agent 07, 15

---

### 🌊 파동함수 불안정 문제 (Wavefunction Instability)

#### #04 학생 선호도 모델이 너무 빠르게 업데이트되어 진동 발생

**문제**

```
선호도 = 최근 상호작용 3회 기준
→ 변동성이 너무 큼
→ 시스템이 톤/방식/시나리오를 갑자기 바꿔버림
```

**해결 방향**
```python
class PreferenceStabilizer:
    """선호도 안정화기"""

    # EMA (지수 이동 평균) 사용
    ema_alpha = 0.2  # 새 데이터 반영률 20%

    # Minimum influence window
    min_window = 10  # 최소 10회 상호작용

    def update_preference(self, current: float, new_observation: float) -> float:
        # EMA 적용
        updated = self.ema_alpha * new_observation + (1 - self.ema_alpha) * current

        # 급격한 변화 억제
        max_change = 0.1  # 최대 10% 변화
        return np.clip(updated, current - max_change, current + max_change)
```

**연관 파동함수**: ψ_pref
**연관 에이전트**: Agent 05, 07

---

#### #05 ψ_wavefunction들 간 상호 의존성에서 발생하는 순환 오류

**문제**

```
ψ_fluct ↑ → ψ_affect.ξ(과부하) ↑
ψ_affect ↑ → ψ_fluct ↑
→ 순환(Ping-pong) 발생
```

**해결 방향**
```python
class WavefunctionStabilityChecker:
    """파동함수 안정성 검사기"""

    # Jacobian 안정성 검사
    def check_jacobian_stability(self, state: np.array) -> bool:
        jacobian = self.compute_jacobian(state)
        eigenvalues = np.linalg.eigvals(jacobian)

        # 모든 고유값의 실수부가 음수여야 안정
        return all(np.real(ev) < 0 for ev in eigenvalues)

    # 상호 영향도 상한치
    max_mutual_influence = 0.3

    def clip_influence(self, influence: float) -> float:
        return np.clip(influence, -self.max_mutual_influence, self.max_mutual_influence)
```

**연관 파동함수**: ψ_fluct, ψ_affect
**연관 에이전트**: Agent 08, 09, 13

---

### 🔗 데이터 매핑 문제 (Data Mapping)

#### #14 변수 정의 불일치 문제

**문제**

StateVector 변수 이름 ↔ LMS 이벤트 이름이 다르면 매핑 실패:

| StateVector | LMS 이벤트 |
|-------------|-----------|
| `engagement_behavior` | `user_activity_score` |
| `curiosity` | `interaction_depth` |
| `focus_level` | `attention_metric` |

**해결 방향**
```python
class VariableMapper:
    """변수 매핑 관리자"""

    mapping_table = {
        # StateVector → LMS
        'engagement_behavior': 'user_activity_score',
        'curiosity': 'interaction_depth',
        'focus_level': 'attention_metric',
        # ... 전체 매핑
    }

    reverse_table = {v: k for k, v in mapping_table.items()}

    def to_state_vector(self, lms_event: Dict) -> Dict:
        return {
            self.reverse_table.get(k, k): v
            for k, v in lms_event.items()
        }
```

**연관 파동함수**: 전체
**연관 에이전트**: 전체

---

#### #02 BCE 경계조건이 개입을 과도하게 막는 상황

**문제**

BCE가 아래처럼 동시에 True 되면:

| 조건 | 상태 |
|------|------|
| 이전상호작용 | 최근 10초 → 막힘 |
| 현활동 | 풀이 중 → 막힘 |
| 선호도 | 개입 싫어함 → 막힘 |
| 수용성 예측 | 0.5 → 미달 |

→ **Dead Zone** 발생 (개입을 아예 못 함)

**해결 방향**
```python
class SoftBCE:
    """Soft-weight 기반 BCE"""

    def check_with_soft_weight(self, conditions: Dict) -> Tuple[bool, float]:
        weights = {
            'recent_interaction': 0.25,
            'current_activity': 0.30,
            'preference': 0.20,
            'receptivity': 0.25
        }

        total_score = sum(
            weights[k] * self.evaluate_condition(k, v)
            for k, v in conditions.items()
        )

        # Override 조건
        if self.is_critical_situation():
            return True, 1.0  # 무조건 개입

        return total_score > 0.5, total_score
```

**연관 파동함수**: ψ_pref, ψ_affect
**연관 에이전트**: Agent 07, 08

---

### 💥 시스템 충돌 문제 (System Conflicts)

#### #16 21단계 시스템과 Quantum Orchestration 엔진 사이의 경쟁 상태(Race condition)

**문제**

```
21단계가 "진도 점검 개입"을 호출함
     ↓ (동시에)
IDE가 "정서 안정 개입"을 호출함
     ↓
충돌 발생
```

**해결 방향**
```python
class InterventionCoordinator:
    """개입 조정자"""

    # 락 메커니즘
    _intervention_lock = threading.Lock()

    def request_intervention(self, source: str, intervention: Dict) -> bool:
        with self._intervention_lock:
            if self.has_pending_intervention():
                # 우선순위 비교
                if self.priority(intervention) > self.priority(self.pending):
                    self.cancel_pending()
                    return self.execute(intervention)
                else:
                    return False  # 대기열에 추가
            else:
                return self.execute(intervention)

    priority_order = {
        'emotional_critical': 100,   # 정서 위기
        'drift_immediate': 90,       # 즉각 이탈
        'misconception': 70,         # 오개념
        'progress_check': 50,        # 진도 점검
        'suggestion': 30             # 일반 제안
    }
```

**연관 파동함수**: 전체
**연관 에이전트**: 전체 (21단계 시스템과 IDE 간 조정)

---

## 우선순위 매트릭스

### 긴급도 × 영향도 분석

```
           ┌───────────────────────────────────────────┐
           │              영향도 (Impact)               │
           │    낮음         중간          높음         │
     ┌─────┼───────────────────────────────────────────┤
  긴 │높음 │ #14 변수매핑  #08 과민감지  #01 시간불일치 │
  급 │     │              #09 정서과민  #15 타이밍     │
  도 │     │              #12 악용탐지  #16 Race조건  │
  ︵ ├─────┼───────────────────────────────────────────┤
  U │중간 │ #04 선호진동  #03 우선순위  #05 순환오류   │
  r │     │ #13 예측부족  #10 시나리오  #06 그래프부하 │
  g │     │              #11 엄격조건  #07 해밀토니안 │
  e ├─────┼───────────────────────────────────────────┤
  n │낮음 │              #02 BCE막힘   #17 서버부하   │
  c │     │                                           │
  y ︶     │                                           │
     └─────┴───────────────────────────────────────────┘
```

### 구현 우선순위

| Phase | 문제 | 이유 |
|:-----:|------|------|
| **P0** | #01, #15, #16 | 시스템 기본 동작에 필수 |
| **P1** | #02, #03, #05, #17 | 안정적인 개입을 위해 필요 |
| **P2** | #06, #07, #10, #11 | 성능 및 품질 개선 |
| **P3** | #04, #08, #09, #12, #13, #14 | 장기 최적화 |

---

## 해결 로드맵

### Phase 0: 기반 안정화 (1주)

| 문제 | 해결책 | 산출물 |
|------|--------|--------|
| #01 | Timestamp 정규화 | `TemporalNormalizer` 클래스 |
| #15 | 타이밍 보호 장치 | `InterventionTimingGuard` 클래스 |
| #16 | 개입 조정자 | `InterventionCoordinator` 클래스 |

### Phase 1: 핵심 안정화 (2주)

| 문제 | 해결책 | 산출물 |
|------|--------|--------|
| #02 | Soft BCE | `SoftBCE` 클래스 |
| #03 | 우선순위 해결기 | `PriorityResolver` 클래스 |
| #05 | 안정성 검사기 | `WavefunctionStabilityChecker` 클래스 |
| #17 | 서버 부하 관리 | `ServerLoadManager` 클래스 |

### Phase 2: 성능 최적화 (2주)

| 문제 | 해결책 | 산출물 |
|------|--------|--------|
| #06 | 희소 얽힘 맵 | `SparseEntanglementMap` 클래스 |
| #07 | 경량 해밀토니안 | `LightweightHamiltonian` 클래스 |
| #10 | 시나리오 중복 제거 | `ScenarioDeduplicator` 클래스 |
| #11 | 유연한 필수조건 | `FlexiblePrerequisite` 클래스 |

### Phase 3: 장기 최적화 (3주)

| 문제 | 해결책 | 산출물 |
|------|--------|--------|
| #04 | 선호도 안정화 | `PreferenceStabilizer` 클래스 |
| #08 | 이탈 감지 보정 | `DriftDetectionCalibrator` 클래스 |
| #09 | 정서 스케일 정규화 | `AffectScaleNormalizer` 클래스 |
| #12 | 이상 행동 탐지 | `AnomalyDetector` 클래스 |
| #13 | 수용성 예측기 | `ReceptivityPredictor` 클래스 |
| #14 | 변수 매핑 관리 | `VariableMapper` 클래스 |

---

## 관련 파동함수 연결

| 파동함수 | 관련 문제 | 역할 |
|---------|:--------:|------|
| ψ_core | #10, #15 | 핵심 학습 상태 표현 |
| ψ_align | #01 | 목표-현재 정렬 상태 |
| ψ_fluct | #01, #05 | 상태 변동성 추적 |
| ψ_affect | #02, #05, #09 | 정서 상태 표현 |
| ψ_tunnel | #10, #11 | 돌파 가능성 예측 |
| ψ_pref | #02, #04 | 학생 선호도 모델링 |
| ψ_focus | #08 | 집중 상태 추적 |
| ψ_pattern | #12 | 행동 패턴 분석 |
| ψ_predict | #01, #03, #13 | 미래 상태 예측 |
| ψ_entangle | #06 | 에이전트 간 상관관계 |

---

## 📝 변경 이력

| 날짜 | 버전 | 변경 내용 |
|------|------|----------|
| 2025-12-09 | 1.0 | 초기 문서 생성 - 17개 Critical 문제 정리 |

---

## 참조

- [quantum-orchestration-design.md](./quantum-orchestration-design.md) - IDE 설계 (섹션 5.4)
- [quantum-learning-model.md](./quantum-learning-model.md) - 13종 파동함수 이론
- [wavefunction-agent-mapping.md](./wavefunction-agent-mapping.md) - 파동함수-에이전트 매핑
- [SYSTEM_STATUS.yaml](./SYSTEM_STATUS.yaml) - 시스템 현황

---

*이 문서는 IDE 구현 시 예상되는 문제점과 해결 방향을 정의합니다.*
