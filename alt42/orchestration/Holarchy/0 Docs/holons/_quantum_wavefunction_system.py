"""
Phase 6: 13종 파동함수 시스템 (Quantum Wavefunction System)
=============================================================

quantum-learning-model.md Part II 섹션 4-6 기반 구현

13종 파동함수:
- ψ_core: 핵심 3상태 (α, β, γ)
- ψ_align: 정렬 파동함수
- ψ_fluct: 요동 파동함수
- ψ_tunnel: 터널링 파동함수
- ψ_WM: 작업기억 안정도
- ψ_affect: 정서 파동함수
- ψ_routine: 루틴 강화
- ψ_engage: 이탈/복귀
- ψ_concept: 개념 구조
- ψ_cascade: 연쇄 붕괴
- ψ_meta: 메타인지
- ψ_context: 상황문맥
- ψ_predict: 예측

Hamiltonian 진화: dΨ(t)/dt = H_total · Ψ(t)
"""

import math
from typing import Dict, List, Optional, Tuple, Any
from dataclasses import dataclass, field
from enum import Enum
from datetime import datetime
import json


# ============================================================================
# numpy 대체 유틸리티 (순수 Python 구현)
# ============================================================================

class SimpleArray:
    """numpy.ndarray 대체 클래스 (순수 Python)"""

    def __init__(self, data: List[float]):
        self.data = list(data)

    def __len__(self):
        return len(self.data)

    def __getitem__(self, key):
        return self.data[key]

    def __setitem__(self, key, value):
        self.data[key] = value

    def __iter__(self):
        return iter(self.data)

    def __repr__(self):
        return f"SimpleArray({self.data})"

    def copy(self) -> 'SimpleArray':
        return SimpleArray(self.data.copy())

    def tolist(self) -> List[float]:
        return self.data.copy()

    def __add__(self, other):
        if isinstance(other, SimpleArray):
            return SimpleArray([a + b for a, b in zip(self.data, other.data)])
        return SimpleArray([a + other for a in self.data])

    def __radd__(self, other):
        return self.__add__(other)

    def __mul__(self, other):
        if isinstance(other, (int, float)):
            return SimpleArray([a * other for a in self.data])
        return SimpleArray([a * b for a, b in zip(self.data, other.data)])

    def __rmul__(self, other):
        return self.__mul__(other)

    def __matmul__(self, other):
        """행렬-벡터 곱 (self가 행렬일 때)"""
        if isinstance(other, SimpleArray):
            return self._matrix_vector_multiply(other)
        return NotImplemented

    def _matrix_vector_multiply(self, vec: 'SimpleArray') -> 'SimpleArray':
        """3x3 행렬과 3D 벡터의 곱"""
        if len(self.data) == 9 and len(vec) == 3:  # 3x3 행렬
            result = []
            for i in range(3):
                row_sum = sum(self.data[i*3 + j] * vec[j] for j in range(3))
                result.append(row_sum)
            return SimpleArray(result)
        return vec  # fallback


class SimpleMatrix:
    """간단한 행렬 클래스"""

    def __init__(self, rows: int, cols: int, data: Optional[List[List[float]]] = None):
        self.rows = rows
        self.cols = cols
        if data:
            self.data = [row.copy() for row in data]
        else:
            self.data = [[0.0] * cols for _ in range(rows)]

    @classmethod
    def eye(cls, n: int) -> 'SimpleMatrix':
        """단위행렬"""
        m = cls(n, n)
        for i in range(n):
            m.data[i][i] = 1.0
        return m

    @classmethod
    def from_flat(cls, flat: List[float], rows: int, cols: int) -> 'SimpleMatrix':
        """1D 리스트에서 행렬 생성"""
        m = cls(rows, cols)
        for i in range(rows):
            for j in range(cols):
                m.data[i][j] = flat[i * cols + j]
        return m

    def __getitem__(self, key):
        if isinstance(key, tuple):
            i, j = key
            return self.data[i][j]
        return self.data[key]

    def __setitem__(self, key, value):
        if isinstance(key, tuple):
            i, j = key
            self.data[i][j] = value
        else:
            self.data[key] = value

    def copy(self) -> 'SimpleMatrix':
        return SimpleMatrix(self.rows, self.cols, self.data)

    def __add__(self, other: 'SimpleMatrix') -> 'SimpleMatrix':
        result = SimpleMatrix(self.rows, self.cols)
        for i in range(self.rows):
            for j in range(self.cols):
                result.data[i][j] = self.data[i][j] + other.data[i][j]
        return result

    def __mul__(self, scalar: float) -> 'SimpleMatrix':
        result = SimpleMatrix(self.rows, self.cols)
        for i in range(self.rows):
            for j in range(self.cols):
                result.data[i][j] = self.data[i][j] * scalar
        return result

    def __rmul__(self, scalar: float) -> 'SimpleMatrix':
        return self.__mul__(scalar)

    def __matmul__(self, vec: SimpleArray) -> SimpleArray:
        """행렬-벡터 곱"""
        result = []
        for i in range(self.rows):
            row_sum = sum(self.data[i][j] * vec[j] for j in range(self.cols))
            result.append(row_sum)
        return SimpleArray(result)

    def to_flat(self) -> List[float]:
        """1D 리스트로 변환"""
        return [self.data[i][j] for i in range(self.rows) for j in range(self.cols)]


# numpy 호환 함수들
def array(data: List[float]) -> SimpleArray:
    """numpy.array 대체"""
    return SimpleArray(data)


def zeros(n: int) -> SimpleArray:
    """numpy.zeros 대체"""
    return SimpleArray([0.0] * n)


def ones(n: int) -> SimpleArray:
    """numpy.ones 대체"""
    return SimpleArray([1.0] * n)


def eye(n: int) -> SimpleMatrix:
    """numpy.eye 대체"""
    return SimpleMatrix.eye(n)


def dot(a: SimpleArray, b: SimpleArray) -> float:
    """numpy.dot 대체"""
    return sum(x * y for x, y in zip(a.data, b.data))


def norm(a: SimpleArray) -> float:
    """numpy.linalg.norm 대체"""
    return math.sqrt(sum(x * x for x in a.data))


def std(data: List[float]) -> float:
    """numpy.std 대체"""
    if len(data) < 2:
        return 0.0
    mean = sum(data) / len(data)
    variance = sum((x - mean) ** 2 for x in data) / len(data)
    return math.sqrt(variance)


def mean(data: List[float]) -> float:
    """numpy.mean 대체"""
    if not data:
        return 0.0
    return sum(data) / len(data)


def clip(value: float, min_val: float, max_val: float) -> float:
    """numpy.clip 대체"""
    return max(min_val, min(max_val, value))


def concatenate(arrays: List[SimpleArray]) -> SimpleArray:
    """numpy.concatenate 대체"""
    result = []
    for arr in arrays:
        result.extend(arr.data)
    return SimpleArray(result)


def triu_sum(m: SimpleMatrix, k: int = 1) -> float:
    """상삼각 행렬 합 (대각선 제외)"""
    total = 0.0
    for i in range(m.rows):
        for j in range(i + k, m.cols):
            total += m.data[i][j]
    return total


def matrix(data: List[List[float]]) -> SimpleMatrix:
    """numpy.array (2D) 대체 - 2D 리스트에서 SimpleMatrix 생성"""
    rows = len(data)
    cols = len(data[0]) if rows > 0 else 0
    return SimpleMatrix(rows, cols, data)


# ============================================================================
# 상수 정의
# ============================================================================

# 파동함수 차원 기본값
DEFAULT_ALIGN_DIM = 10      # 정렬 feature 수
DEFAULT_FLUCT_DIM = 8       # 요동 feature 수
DEFAULT_CONCEPT_DIM = 15    # 개념 얽힘 feature 수
DEFAULT_CONTEXT_DIM = 12    # 상황 맥락 feature 수

# 시간 상수
TIME_WINDOW_SECONDS = 20    # 20초 윈도우 (작업기억 기준)
DECAY_RATE_SHORT = 0.1      # 단기 감쇠율
DECAY_RATE_LONG = 0.01      # 장기 감쇠율

# Hamiltonian 결합 상수
K1_ALIGN_CORE = 0.15        # 정렬 → 코어 결합 강도
K2_CALM_EFFECT = 0.12       # 침착 → 코어 결합 강도
K3_OVERLOAD_EFFECT = 0.08   # 과부하 → 코어 결합 강도
K4_WM_EFFECT = 0.10         # 작업기억 → 코어 결합 강도
K5_ROUTINE_EFFECT = 0.05    # 루틴 → 코어 결합 강도


# ============================================================================
# Enum 및 기본 타입
# ============================================================================

class CoreState(Enum):
    """핵심 3상태 기저"""
    CORRECT = 0       # |C⟩ 정답
    MISCONCEPTION = 1 # |M⟩ 오개념
    CONFUSION = 2     # |X⟩ 혼란


class EngageState(Enum):
    """이탈/복귀 상태"""
    FOCUS = 0   # 집중
    DRIFT = 1   # 이탈
    DROP = 2    # 포기


class MetaState(Enum):
    """메타인지 상태"""
    CAN_DO = 0      # 할 수 있다
    UNCERTAIN = 1   # 불확실


# ============================================================================
# 1. ψ_core: 핵심 3상태 파동함수
# ============================================================================

@dataclass
class PsiCore:
    """
    핵심 3상태 파동함수

    ψ_core(t) = [α(t), β(t), γ(t)]ᵀ
    기저: |Correct⟩, |Misconception⟩, |Confusion⟩

    정규화 조건: α² + β² + γ² = 1
    """
    alpha: float = 0.33  # 정답 확률 진폭
    beta: float = 0.33   # 오개념 확률 진폭
    gamma: float = 0.34  # 혼란 확률 진폭
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        self._normalize()

    def _normalize(self):
        """정규화"""
        total = math.sqrt(self.alpha**2 + self.beta**2 + self.gamma**2)
        if total > 0:
            self.alpha /= total
            self.beta /= total
            self.gamma /= total

    def to_vector(self) -> SimpleArray:
        """3D 벡터로 변환"""
        return array([self.alpha, self.beta, self.gamma])

    @classmethod
    def from_vector(cls, vec) -> 'PsiCore':
        """벡터에서 생성 (SimpleArray 또는 list)"""
        if isinstance(vec, SimpleArray):
            return cls(alpha=vec[0], beta=vec[1], gamma=vec[2])
        return cls(alpha=vec[0], beta=vec[1], gamma=vec[2])

    def get_probabilities(self) -> Dict[str, float]:
        """각 상태의 확률 반환 (|진폭|²)"""
        return {
            'correct': self.alpha ** 2,
            'misconception': self.beta ** 2,
            'confusion': self.gamma ** 2
        }

    def collapse_probability(self, alignment: float = 1.0) -> float:
        """
        붕괴 확률 계산
        CP(t) = α(t) · dα/dt · Align(t)
        (dα/dt는 외부에서 계산 필요, 여기선 단순화)
        """
        return self.alpha * alignment * (1 - self.gamma)

    def evolve(self, h_matrix: SimpleMatrix, dt: float = 1.0) -> 'PsiCore':
        """
        Hamiltonian으로 시간 진화
        ψ(t+dt) = ψ(t) + H · ψ(t) · dt
        """
        current = self.to_vector()
        delta = h_matrix @ current
        delta = delta * dt
        new_vec = current + delta
        return PsiCore.from_vector(new_vec)


# ============================================================================
# 2. ψ_align: 정렬 파동함수
# ============================================================================

@dataclass
class PsiAlign:
    """
    정렬 파동함수

    ψ_align(t) ∈ ℝⁿᴬ
    에이전트들의 신호가 정답 방향으로 정렬되는 정도

    Align = Σᵢ cos(θᵢ) / n
    """
    features: SimpleArray = field(default_factory=lambda: zeros(DEFAULT_ALIGN_DIM))
    weights: SimpleArray = field(default_factory=lambda: ones(DEFAULT_ALIGN_DIM) * (1.0 / DEFAULT_ALIGN_DIM))
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        if len(self.features) != len(self.weights):
            n = len(self.features)
            self.weights = ones(n) * (1.0 / n)

    def to_vector(self) -> SimpleArray:
        return SimpleArray(self.features.data[:])

    def get_alignment_score(self) -> float:
        """
        전체 정렬 스코어 계산 (0~1)
        가중 평균으로 계산
        """
        return clip(dot(self.features, self.weights), 0, 1)

    def update_feature(self, index: int, value: float):
        """특정 feature 업데이트"""
        if 0 <= index < len(self.features):
            self.features.data[index] = clip(value, 0, 1)
            self.timestamp = datetime.now()

    def get_direction_cosine(self, target_direction) -> float:
        """목표 방향과의 코사인 유사도"""
        if isinstance(target_direction, SimpleArray):
            target = target_direction
        else:
            target = array(list(target_direction))
        norm_f = norm(self.features)
        norm_t = norm(target)
        if norm_f > 0 and norm_t > 0:
            return dot(self.features, target) / (norm_f * norm_t)
        return 0.0


# ============================================================================
# 3. ψ_fluct: 요동 파동함수
# ============================================================================

@dataclass
class PsiFluct:
    """
    요동 파동함수

    ψ_fluct(t) ∈ ℝⁿᶠ
    시도/수정/탐색폭 패턴을 표현

    σ(t) = std(α values over time window)
    """
    features: SimpleArray = field(default_factory=lambda: zeros(DEFAULT_FLUCT_DIM))
    history: List[float] = field(default_factory=list)  # α 히스토리
    window_size: int = 10  # 관측 윈도우 크기
    timestamp: datetime = field(default_factory=datetime.now)

    def to_vector(self) -> SimpleArray:
        return SimpleArray(self.features.data[:])

    def record_alpha(self, alpha: float):
        """α 값 기록"""
        self.history.append(alpha)
        if len(self.history) > self.window_size * 2:
            self.history = self.history[-self.window_size * 2:]
        self._update_features()
        self.timestamp = datetime.now()

    def _update_features(self):
        """요동 feature 업데이트"""
        if len(self.history) >= 2:
            recent = self.history[-self.window_size:] if len(self.history) >= self.window_size else self.history

            # 요동 features 계산
            self.features.data[0] = std(recent) if len(recent) > 1 else 0  # 표준편차
            self.features.data[1] = max(recent) - min(recent)  # 범위
            self.features.data[2] = abs(recent[-1] - recent[0]) / len(recent) if len(recent) > 1 else 0  # 평균 변화율

            # 수렴/발산 판단
            if len(recent) >= 3:
                first_half = std(recent[:len(recent)//2])
                second_half = std(recent[len(recent)//2:])
                self.features.data[3] = first_half - second_half  # 양수면 수렴, 음수면 발산

    def get_fluctuation_level(self) -> str:
        """요동 수준 반환"""
        std = self.features[0] if len(self.features) > 0 else 0
        if std < 0.1:
            return 'stable'
        elif std < 0.25:
            return 'moderate'
        else:
            return 'volatile'

    def is_converging(self) -> bool:
        """수렴 중인지 확인"""
        return self.features[3] > 0.05 if len(self.features) > 3 else False


# ============================================================================
# 4. ψ_tunnel: 터널링 파동함수
# ============================================================================

@dataclass
class PsiTunnel:
    """
    터널링 파동함수

    ψ_tunnel(t) = [E_cog(t), B_concept(t), P_tunnel(t)]

    - E_cog: 인지 에너지
    - B_concept: 개념 장벽 높이
    - P_tunnel: 터널링 확률

    P_tunnel = exp(-2κL) where κ = sqrt(2m(V-E))/ℏ (간소화)
    """
    E_cog: float = 0.5       # 인지 에너지 (0~1)
    B_concept: float = 0.5   # 개념 장벽 (0~1)
    P_tunnel: float = 0.0    # 터널링 확률 (계산됨)
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        self._calculate_tunnel_probability()

    def _calculate_tunnel_probability(self):
        """
        터널링 확률 계산
        P = exp(-k * (B - E)) where k is barrier sensitivity
        """
        barrier_factor = max(0, self.B_concept - self.E_cog)
        k = 3.0  # 장벽 민감도 상수
        self.P_tunnel = math.exp(-k * barrier_factor)

    def to_vector(self) -> SimpleArray:
        return array([self.E_cog, self.B_concept, self.P_tunnel])

    def update_energy(self, delta_E: float):
        """인지 에너지 업데이트"""
        self.E_cog = clip(self.E_cog + delta_E, 0, 1)
        self._calculate_tunnel_probability()
        self.timestamp = datetime.now()

    def update_barrier(self, new_barrier: float):
        """개념 장벽 업데이트"""
        self.B_concept = clip(new_barrier, 0, 1)
        self._calculate_tunnel_probability()
        self.timestamp = datetime.now()

    def can_tunnel(self, threshold: float = 0.3) -> bool:
        """터널링 가능 여부"""
        return self.P_tunnel >= threshold

    def get_barrier_penetration_depth(self) -> float:
        """장벽 침투 깊이"""
        if self.B_concept > self.E_cog:
            return self.E_cog / self.B_concept
        return 1.0  # 에너지가 장벽보다 높으면 완전 통과


# ============================================================================
# 5. ψ_WM: 작업기억 안정도 파동함수
# ============================================================================

@dataclass
class PsiWM:
    """
    작업기억 안정도 파동함수

    ψ_WM(t) = [W(t), dW/dt]ᵀ
    20초 윈도우 기반

    W: 작업기억 용량 활용도
    dW/dt: 변화율 (양수=안정화, 음수=불안정)
    """
    W: float = 0.5           # 작업기억 안정도 (0~1)
    dW_dt: float = 0.0       # 변화율
    history: List[Tuple[datetime, float]] = field(default_factory=list)
    window_seconds: int = TIME_WINDOW_SECONDS
    timestamp: datetime = field(default_factory=datetime.now)

    def to_vector(self) -> SimpleArray:
        return array([self.W, self.dW_dt])

    def record_stability(self, value: float):
        """안정도 기록"""
        now = datetime.now()
        self.history.append((now, value))

        # 윈도우 정리
        cutoff = now.timestamp() - self.window_seconds
        self.history = [(t, v) for t, v in self.history if t.timestamp() > cutoff]

        # W와 dW/dt 계산
        if len(self.history) >= 2:
            self.W = mean([v for _, v in self.history])

            # dW/dt 계산 (최근 vs 이전)
            recent = [v for t, v in self.history if t.timestamp() > now.timestamp() - self.window_seconds/2]
            older = [v for t, v in self.history if t.timestamp() <= now.timestamp() - self.window_seconds/2]

            if recent and older:
                self.dW_dt = mean(recent) - mean(older)
            else:
                self.dW_dt = 0.0
        else:
            self.W = value
            self.dW_dt = 0.0

        self.timestamp = now

    def is_stable(self) -> bool:
        """작업기억이 안정적인지"""
        return self.W > 0.6 and self.dW_dt >= -0.1

    def get_capacity_utilization(self) -> float:
        """작업기억 용량 활용률"""
        return self.W


# ============================================================================
# 6. ψ_affect: 정서 파동함수
# ============================================================================

@dataclass
class PsiAffect:
    """
    정서 파동함수

    ψ_affect(t) = [μ_calm, ν_tension, ξ_overload]

    - μ_calm: 침착도 (0~1)
    - ν_tension: 긴장도 (0~1)
    - ξ_overload: 과부하 (0~1)

    정규화: μ + ν + ξ = 1 (soft constraint)
    """
    mu_calm: float = 0.5      # 침착
    nu_tension: float = 0.3   # 긴장
    xi_overload: float = 0.2  # 과부하
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        self._soft_normalize()

    def _soft_normalize(self):
        """소프트 정규화 (합이 1에 가깝도록)"""
        total = self.mu_calm + self.nu_tension + self.xi_overload
        if total > 0:
            self.mu_calm /= total
            self.nu_tension /= total
            self.xi_overload /= total

    def to_vector(self) -> SimpleArray:
        return array([self.mu_calm, self.nu_tension, self.xi_overload])

    @classmethod
    def from_vector(cls, vec: SimpleArray) -> 'PsiAffect':
        return cls(mu_calm=vec.data[0], nu_tension=vec.data[1], xi_overload=vec.data[2])

    def get_dominant_state(self) -> str:
        """지배적 정서 상태"""
        states = {'calm': self.mu_calm, 'tension': self.nu_tension, 'overload': self.xi_overload}
        return max(states, key=states.get)

    def is_conducive_to_learning(self) -> bool:
        """학습에 유리한 정서 상태인지"""
        return self.mu_calm > 0.4 and self.xi_overload < 0.3

    def get_core_coupling_effect(self) -> Tuple[float, float]:
        """
        Core 파동함수에 대한 결합 효과
        Returns: (calm_effect, overload_effect)
        """
        return (self.mu_calm * K2_CALM_EFFECT,
                self.xi_overload * K3_OVERLOAD_EFFECT)


# ============================================================================
# 7. ψ_routine: 루틴 강화 파동함수
# ============================================================================

@dataclass
class PsiRoutine:
    """
    루틴 강화 파동함수

    ψ_routine(t) = [R_daily, R_weekly, R_long]

    R(t) = Σᵢ routineᵢ(t) · weightᵢ
    장기 α baseline 결정
    """
    R_daily: float = 0.5    # 일간 루틴 강도
    R_weekly: float = 0.5   # 주간 루틴 강도
    R_long: float = 0.5     # 장기 루틴 강도
    weights: Tuple[float, float, float] = (0.3, 0.3, 0.4)  # 가중치
    timestamp: datetime = field(default_factory=datetime.now)

    def to_vector(self) -> SimpleArray:
        return array([self.R_daily, self.R_weekly, self.R_long])

    def get_overall_routine_strength(self) -> float:
        """전체 루틴 강도"""
        return (self.R_daily * self.weights[0] +
                self.R_weekly * self.weights[1] +
                self.R_long * self.weights[2])

    def update_daily(self, completed: bool, intensity: float = 1.0):
        """일간 루틴 업데이트"""
        if completed:
            self.R_daily = min(1.0, self.R_daily + 0.1 * intensity)
        else:
            self.R_daily = max(0.0, self.R_daily - 0.05)
        self.timestamp = datetime.now()

    def decay_routines(self, days_passed: int = 1):
        """루틴 감쇠"""
        self.R_daily *= (1 - DECAY_RATE_SHORT) ** days_passed
        self.R_weekly *= (1 - DECAY_RATE_SHORT / 7) ** days_passed
        self.R_long *= (1 - DECAY_RATE_LONG) ** days_passed

    def get_baseline_contribution(self) -> float:
        """α baseline에 대한 기여도"""
        return self.get_overall_routine_strength() * K5_ROUTINE_EFFECT


# ============================================================================
# 8. ψ_engage: 이탈/복귀 파동함수
# ============================================================================

@dataclass
class PsiEngage:
    """
    이탈/복귀 파동함수

    ψ_engage(t) = [p_focus, q_drift, r_drop]
    |D⟩ = p|Focus⟩ + q|Drift⟩ + r|Drop⟩

    정규화: p² + q² + r² = 1
    """
    p_focus: float = 0.7   # 집중 확률
    q_drift: float = 0.2   # 이탈 확률
    r_drop: float = 0.1    # 포기 확률
    recovery_rate: float = 0.5  # 복귀 속도
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        self._normalize()

    def _normalize(self):
        """정규화"""
        total = math.sqrt(self.p_focus**2 + self.q_drift**2 + self.r_drop**2)
        if total > 0:
            self.p_focus /= total
            self.q_drift /= total
            self.r_drop /= total

    def to_vector(self) -> SimpleArray:
        return array([self.p_focus, self.q_drift, self.r_drop])

    @classmethod
    def from_vector(cls, vec: SimpleArray) -> 'PsiEngage':
        return cls(p_focus=vec.data[0], q_drift=vec.data[1], r_drop=vec.data[2])

    def get_dominant_state(self) -> EngageState:
        """지배적 상태"""
        values = [self.p_focus, self.q_drift, self.r_drop]
        return EngageState(values.index(max(values)))

    def trigger_drift(self, intensity: float = 0.3):
        """이탈 트리거"""
        self.p_focus = max(0.1, self.p_focus - intensity)
        self.q_drift = min(0.9, self.q_drift + intensity * 0.7)
        self.r_drop = min(0.9, self.r_drop + intensity * 0.3)
        self._normalize()
        self.timestamp = datetime.now()

    def trigger_recovery(self, intensity: float = None):
        """복귀 트리거"""
        if intensity is None:
            intensity = self.recovery_rate
        self.p_focus = min(0.95, self.p_focus + intensity)
        self.q_drift = max(0.05, self.q_drift - intensity * 0.6)
        self.r_drop = max(0.0, self.r_drop - intensity * 0.4)
        self._normalize()
        self.timestamp = datetime.now()

    def get_alpha_impact(self) -> float:
        """
        α에 대한 영향
        빠른 복귀 = α 급상승, 느린 복귀 = α baseline 하락
        """
        # focus가 높고 recovery_rate가 빠르면 긍정적
        return (self.p_focus - 0.5) * (1 + self.recovery_rate)


# ============================================================================
# 9. ψ_concept: 개념 구조 파동함수
# ============================================================================

@dataclass
class PsiConcept:
    """
    개념 구조 파동함수

    ψ_concept(t) ∈ ℝⁿᶜ
    |C⟩ = Σ entangle(i,j)

    개념 간 얽힘을 모델링
    """
    features: SimpleArray = field(default_factory=lambda: zeros(DEFAULT_CONCEPT_DIM))
    entanglement_matrix: SimpleMatrix = field(default_factory=lambda: eye(DEFAULT_CONCEPT_DIM))
    concept_labels: List[str] = field(default_factory=list)
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        if len(self.concept_labels) == 0:
            self.concept_labels = [f"concept_{i}" for i in range(len(self.features.data))]

    def to_vector(self) -> SimpleArray:
        return SimpleArray(self.features.data[:])

    def set_entanglement(self, i: int, j: int, strength: float):
        """개념 i-j 간 얽힘 설정"""
        if 0 <= i < len(self.features.data) and 0 <= j < len(self.features.data):
            strength = clip(strength, 0, 1)
            self.entanglement_matrix.data[i][j] = strength
            self.entanglement_matrix.data[j][i] = strength  # 대칭
            self.timestamp = datetime.now()

    def get_entanglement(self, i: int, j: int) -> float:
        """개념 i-j 간 얽힘 강도"""
        if 0 <= i < len(self.features.data) and 0 <= j < len(self.features.data):
            return float(self.entanglement_matrix.data[i][j])
        return 0.0

    def propagate_collapse(self, collapsed_concept: int, collapse_strength: float) -> Dict[int, float]:
        """
        붕괴 전파: 한 개념이 붕괴하면 얽힌 개념들도 영향
        """
        effects = {}
        if 0 <= collapsed_concept < len(self.features.data):
            for j in range(len(self.features.data)):
                if j != collapsed_concept:
                    entangle = self.entanglement_matrix.data[collapsed_concept][j]
                    if entangle > 0.1:
                        effects[j] = collapse_strength * entangle
        return effects

    def get_total_entanglement(self) -> float:
        """전체 얽힘 강도"""
        # 대각선 제외한 상삼각 합
        return triu_sum(self.entanglement_matrix, k=1)


# ============================================================================
# 10. ψ_cascade: 연쇄 붕괴 파동함수
# ============================================================================

@dataclass
class PsiCascade:
    """
    연쇄 붕괴 파동함수

    ψ_cascade(t) = [CC_short, CC_unit, CC_term]

    CC(t) = α₁ · α₂ · α₃ · … · exp(-Δt / k)
    연속적 붕괴 확률
    """
    CC_short: float = 0.0   # 단기 연쇄 (문제 단위)
    CC_unit: float = 0.0    # 단원 연쇄
    CC_term: float = 0.0    # 학기 연쇄
    collapse_history: List[Tuple[datetime, float]] = field(default_factory=list)
    cascade_decay_k: float = 5.0  # 연쇄 감쇠 상수
    timestamp: datetime = field(default_factory=datetime.now)

    def to_vector(self) -> SimpleArray:
        return array([self.CC_short, self.CC_unit, self.CC_term])

    def record_collapse(self, alpha_at_collapse: float, level: str = 'short'):
        """붕괴 기록"""
        now = datetime.now()
        self.collapse_history.append((now, alpha_at_collapse))

        # 최근 붕괴들로 연쇄 확률 계산
        self._update_cascade_probabilities(now)
        self.timestamp = now

    def _update_cascade_probabilities(self, now: datetime):
        """연쇄 확률 업데이트"""
        # 시간별 붕괴 필터링
        short_window = 60  # 1분
        unit_window = 3600  # 1시간
        term_window = 86400 * 7  # 1주

        now_ts = now.timestamp()

        short_collapses = [(t, a) for t, a in self.collapse_history
                          if now_ts - t.timestamp() < short_window]
        unit_collapses = [(t, a) for t, a in self.collapse_history
                         if now_ts - t.timestamp() < unit_window]
        term_collapses = [(t, a) for t, a in self.collapse_history
                         if now_ts - t.timestamp() < term_window]

        # CC = Π αᵢ · exp(-Δt/k)
        self.CC_short = self._calculate_cascade(short_collapses, now_ts, self.cascade_decay_k)
        self.CC_unit = self._calculate_cascade(unit_collapses, now_ts, self.cascade_decay_k * 10)
        self.CC_term = self._calculate_cascade(term_collapses, now_ts, self.cascade_decay_k * 100)

    def _calculate_cascade(self, collapses: List[Tuple[datetime, float]],
                          now_ts: float, k: float) -> float:
        """연쇄 확률 계산"""
        if len(collapses) < 2:
            return 0.0

        product = 1.0
        for i, (t, a) in enumerate(collapses):
            dt = now_ts - t.timestamp()
            decay = math.exp(-dt / k)
            product *= a * decay

        return min(1.0, product)

    def get_cascade_likelihood(self) -> str:
        """연쇄 가능성 레벨"""
        max_cc = max(self.CC_short, self.CC_unit, self.CC_term)
        if max_cc > 0.7:
            return 'high'
        elif max_cc > 0.4:
            return 'moderate'
        else:
            return 'low'


# ============================================================================
# 11. ψ_meta: 메타인지 파동함수
# ============================================================================

@dataclass
class PsiMeta:
    """
    메타인지 파동함수

    ψ_meta(t) = [s_canDo, t_uncertain]
    |M⟩ = s|CanDo⟩ + t|Uncertain⟩

    정규화: s² + t² = 1
    """
    s_canDo: float = 0.5      # "할 수 있다"
    t_uncertain: float = 0.5  # 불확실
    baseline_shift: float = 0.0  # α baseline 영구 변화량
    timestamp: datetime = field(default_factory=datetime.now)

    def __post_init__(self):
        self._normalize()

    def _normalize(self):
        """정규화"""
        total = math.sqrt(self.s_canDo**2 + self.t_uncertain**2)
        if total > 0:
            self.s_canDo /= total
            self.t_uncertain /= total

    def to_vector(self) -> SimpleArray:
        return array([self.s_canDo, self.t_uncertain])

    @classmethod
    def from_vector(cls, vec: SimpleArray) -> 'PsiMeta':
        return cls(s_canDo=vec.data[0], t_uncertain=vec.data[1])

    def get_dominant_state(self) -> MetaState:
        """지배적 상태"""
        return MetaState.CAN_DO if self.s_canDo > self.t_uncertain else MetaState.UNCERTAIN

    def self_acknowledgment(self, success: bool, intensity: float = 0.1):
        """
        자기인정 이벤트
        성공 시 → s_canDo 증가, baseline 영구 상승
        """
        if success:
            self.s_canDo = min(0.99, self.s_canDo + intensity)
            self.t_uncertain = max(0.01, self.t_uncertain - intensity)
            self.baseline_shift += intensity * 0.5  # 영구 상승
        else:
            self.s_canDo = max(0.1, self.s_canDo - intensity * 0.3)
            self.t_uncertain = min(0.9, self.t_uncertain + intensity * 0.3)

        self._normalize()
        self.timestamp = datetime.now()

    def get_efficacy_score(self) -> float:
        """자기효능감 스코어"""
        return self.s_canDo ** 2  # 확률

    def get_baseline_contribution(self) -> float:
        """α baseline에 대한 영구 기여"""
        return self.baseline_shift


# ============================================================================
# 12. ψ_context: 상황문맥 파동함수
# ============================================================================

@dataclass
class PsiContext:
    """
    상황문맥 파동함수

    ψ_context(t) ∈ ℝⁿᶜᵀˣ
    |CTX⟩ = Σ contextᵢ · wᵢ

    같은 문제라도 맥락에 따라 정답률이 달라짐
    """
    features: SimpleArray = field(default_factory=lambda: zeros(DEFAULT_CONTEXT_DIM))
    context_labels: List[str] = field(default_factory=lambda: [
        'time_of_day', 'day_of_week', 'location', 'device',
        'preceding_success', 'fatigue_level', 'motivation',
        'social_context', 'task_type', 'difficulty_perception',
        'time_pressure', 'prior_attempts'
    ])
    weights: SimpleArray = field(default_factory=lambda: ones(DEFAULT_CONTEXT_DIM) * (1.0 / DEFAULT_CONTEXT_DIM))
    timestamp: datetime = field(default_factory=datetime.now)

    def to_vector(self) -> SimpleArray:
        return SimpleArray(self.features.data[:])

    def set_context(self, label: str, value: float):
        """특정 컨텍스트 설정"""
        if label in self.context_labels:
            idx = self.context_labels.index(label)
            self.features.data[idx] = clip(value, 0, 1)
            self.timestamp = datetime.now()

    def get_context(self, label: str) -> float:
        """특정 컨텍스트 값"""
        if label in self.context_labels:
            idx = self.context_labels.index(label)
            return float(self.features.data[idx])
        return 0.0

    def get_context_score(self) -> float:
        """전체 컨텍스트 스코어 (학습에 유리한 정도)"""
        return dot(self.features, self.weights)

    def get_intervention_timing_factor(self) -> float:
        """
        개입 타이밍에 대한 컨텍스트 영향
        반환값이 높을수록 개입에 좋은 타이밍
        """
        # 긍정적 요소: 낮은 피로, 높은 동기, 낮은 시간압박
        favorable = (
            (1 - self.get_context('fatigue_level')) * 0.3 +
            self.get_context('motivation') * 0.4 +
            (1 - self.get_context('time_pressure')) * 0.3
        )
        return favorable


# ============================================================================
# 13. ψ_predict: 예측 파동함수
# ============================================================================

@dataclass
class PsiPredict:
    """
    예측 파동함수

    ψ_predict(t) = [α(t), dα/dt, CP(t)]

    CP(t) = α(t) · dα/dt · Align(t)
    붕괴 시점 예측용
    """
    alpha_current: float = 0.5     # 현재 α
    d_alpha_dt: float = 0.0        # α 변화율
    CP: float = 0.0                # 붕괴 확률
    alpha_history: List[Tuple[datetime, float]] = field(default_factory=list)
    window_seconds: int = 30       # 관측 윈도우
    timestamp: datetime = field(default_factory=datetime.now)

    def to_vector(self) -> SimpleArray:
        return array([self.alpha_current, self.d_alpha_dt, self.CP])

    def update_alpha(self, new_alpha: float, alignment: float = 1.0):
        """α 업데이트 및 예측 재계산"""
        now = datetime.now()
        self.alpha_history.append((now, new_alpha))

        # 윈도우 정리
        cutoff = now.timestamp() - self.window_seconds
        self.alpha_history = [(t, a) for t, a in self.alpha_history
                              if t.timestamp() > cutoff]

        # dα/dt 계산
        if len(self.alpha_history) >= 2:
            times = [t.timestamp() for t, _ in self.alpha_history]
            alphas = [a for _, a in self.alpha_history]

            if times[-1] - times[0] > 0:
                self.d_alpha_dt = (alphas[-1] - alphas[0]) / (times[-1] - times[0])
            else:
                self.d_alpha_dt = 0.0

        self.alpha_current = new_alpha

        # CP 계산: CP(t) = α(t) · dα/dt · Align(t)
        # dα/dt가 양수일 때만 의미있음
        if self.d_alpha_dt > 0:
            self.CP = self.alpha_current * self.d_alpha_dt * alignment
        else:
            self.CP = 0.0

        self.timestamp = now

    def get_collapse_imminent(self, threshold: float = 0.3) -> bool:
        """붕괴 임박 여부"""
        return self.CP >= threshold

    def get_time_to_collapse_estimate(self) -> Optional[float]:
        """
        붕괴까지 예상 시간 (초)
        α가 1에 도달하는 데 걸리는 시간 추정
        """
        if self.d_alpha_dt > 0.001:  # 양의 변화율
            remaining = 1.0 - self.alpha_current
            return remaining / self.d_alpha_dt
        return None

    def get_dashboard_indicator(self) -> Dict[str, Any]:
        """대시보드 표시용 정보"""
        return {
            'alpha': round(self.alpha_current, 3),
            'd_alpha_dt': round(self.d_alpha_dt, 4),
            'collapse_probability': round(self.CP, 3),
            'imminent': self.get_collapse_imminent(),
            'eta_seconds': self.get_time_to_collapse_estimate()
        }


# ============================================================================
# Hamiltonian 결합 행렬
# ============================================================================

class HamiltonianCoupling:
    """
    Hamiltonian 결합 행렬 관리

    H_total = H₀ + Σₖ λₖHₖ + Σᵢ<ⱼ κᵢⱼHᵢⱼ^(couple)

    dΨ(t)/dt = H_total · Ψ(t)
    """

    def __init__(self):
        # H_core: 3×3 기본 전이 행렬
        self.H_core = self._init_H_core()

        # 결합 행렬들
        self.H_align_core = self._init_H_align_core()
        self.H_affect_core = self._init_H_affect_core()
        self.H_WM_core = self._init_H_WM_core()

        # 결합 강도
        self.coupling_strengths = {
            'align_core': K1_ALIGN_CORE,
            'affect_core_calm': K2_CALM_EFFECT,
            'affect_core_overload': K3_OVERLOAD_EFFECT,
            'WM_core': K4_WM_EFFECT,
            'routine_core': K5_ROUTINE_EFFECT
        }

    def _init_H_core(self) -> SimpleMatrix:
        """
        Core 3상태용 H_core (3×3 행렬)
        기저: |C⟩, |M⟩, |X⟩ (Correct, Misconception, Confusion)

        H_core = [
            -a    b    c
             d   -e    f
             g    h   -i
        ]
        """
        return matrix([
            [-0.1,  0.15, 0.05],  # from C: stay(-), to M, to X
            [0.10, -0.15, 0.08],  # from M: to C, stay(-), to X
            [0.05,  0.10, -0.12]  # from X: to C, to M, stay(-)
        ])

    def _init_H_align_core(self) -> SimpleMatrix:
        """
        Alignment-Core 결합 행렬
        Alignment가 높을수록 M→C 전이 가속

        H_align-core = [
            0     k₁·A(t)    0
            0       0        0
            0       0        0
        ]
        """
        return matrix([
            [0, 1, 0],  # A(t)가 곱해짐
            [0, 0, 0],
            [0, 0, 0]
        ])

    def _init_H_affect_core(self) -> SimpleMatrix:
        """
        Affective-Core 결합 행렬
        Calm 높으면 X→C 강화, Overload 높으면 C→X 역전이

        H_affect-core = [
            0         0      k₂·μ_calm
            0         0          0
            k₃·ξ_overload  0      0
        ]
        """
        return matrix([
            [0, 0, 1],  # μ_calm이 곱해짐 (X→C)
            [0, 0, 0],
            [1, 0, 0]   # ξ_overload가 곱해짐 (C→X)
        ])

    def _init_H_WM_core(self) -> SimpleMatrix:
        """
        WM-Core 결합 행렬
        작업기억 안정 시 C 상태 유지 강화
        """
        return matrix([
            [1, 0, 0],  # C 상태 안정화
            [0, 0, 0],
            [0, 0, 0]
        ])

    def compute_effective_H_core(self,
                                  align_score: float,
                                  affect: PsiAffect,
                                  wm_stability: float) -> SimpleMatrix:
        """
        유효 Core Hamiltonian 계산

        H_eff = H_core + k₁·A·H_align + k₂·μ·H_affect + k₄·W·H_WM
        """
        H_eff = self.H_core.copy()

        # Alignment 결합
        H_eff = H_eff + (self.H_align_core * (self.coupling_strengths['align_core'] * align_score))

        # Affective 결합 (calm & overload)
        calm_effect = affect.mu_calm * self.coupling_strengths['affect_core_calm']
        overload_effect = affect.xi_overload * self.coupling_strengths['affect_core_overload']

        H_affect_scaled = self.H_affect_core.copy()
        H_affect_scaled[0, 2] *= calm_effect      # X→C
        H_affect_scaled[2, 0] *= overload_effect  # C→X
        H_eff = H_eff + H_affect_scaled

        # WM 결합
        H_eff = H_eff + (self.H_WM_core * (self.coupling_strengths['WM_core'] * wm_stability))

        return H_eff

    def evolve_core(self,
                    psi_core: PsiCore,
                    align_score: float,
                    affect: PsiAffect,
                    wm_stability: float,
                    dt: float = 1.0) -> PsiCore:
        """
        Core 파동함수 시간 진화

        ψ(t+dt) = ψ(t) + H_eff · ψ(t) · dt
        """
        H_eff = self.compute_effective_H_core(align_score, affect, wm_stability)
        return psi_core.evolve(H_eff, dt)


# ============================================================================
# 전체 파동함수 시스템
# ============================================================================

@dataclass
class QuantumWavefunctionSystem:
    """
    13종 파동함수 통합 시스템

    Ψ(t) = [ψ_core, ψ_align, ψ_fluct, ψ_tunnel, ψ_WM, ψ_affect,
            ψ_routine, ψ_engage, ψ_concept, ψ_cascade, ψ_meta,
            ψ_context, ψ_predict]
    """
    # 13종 파동함수
    psi_core: PsiCore = field(default_factory=PsiCore)
    psi_align: PsiAlign = field(default_factory=PsiAlign)
    psi_fluct: PsiFluct = field(default_factory=PsiFluct)
    psi_tunnel: PsiTunnel = field(default_factory=PsiTunnel)
    psi_WM: PsiWM = field(default_factory=PsiWM)
    psi_affect: PsiAffect = field(default_factory=PsiAffect)
    psi_routine: PsiRoutine = field(default_factory=PsiRoutine)
    psi_engage: PsiEngage = field(default_factory=PsiEngage)
    psi_concept: PsiConcept = field(default_factory=PsiConcept)
    psi_cascade: PsiCascade = field(default_factory=PsiCascade)
    psi_meta: PsiMeta = field(default_factory=PsiMeta)
    psi_context: PsiContext = field(default_factory=PsiContext)
    psi_predict: PsiPredict = field(default_factory=PsiPredict)

    # Hamiltonian 결합
    hamiltonian: HamiltonianCoupling = field(default_factory=HamiltonianCoupling)

    # 메타데이터
    student_id: int = 0
    timestamp: datetime = field(default_factory=datetime.now)

    def get_full_state_vector(self) -> SimpleArray:
        """
        전체 상태 벡터 Ψ(t) 반환
        D = Σₖ dim(ψₖ)
        """
        vectors = [
            self.psi_core.to_vector(),
            self.psi_align.to_vector(),
            self.psi_fluct.to_vector(),
            self.psi_tunnel.to_vector(),
            self.psi_WM.to_vector(),
            self.psi_affect.to_vector(),
            self.psi_routine.to_vector(),
            self.psi_engage.to_vector(),
            self.psi_concept.to_vector(),
            self.psi_cascade.to_vector(),
            self.psi_meta.to_vector(),
            self.psi_context.to_vector(),
            self.psi_predict.to_vector()
        ]
        return concatenate(vectors)

    def get_state_dimension(self) -> int:
        """전체 상태 공간 차원"""
        return len(self.get_full_state_vector())

    def evolve(self, dt: float = 1.0):
        """
        전체 시스템 시간 진화

        dΨ(t)/dt = H_total · Ψ(t)
        """
        # Core 진화 (다른 파동함수들의 영향 반영)
        align_score = self.psi_align.get_alignment_score()
        wm_stability = self.psi_WM.W

        new_core = self.hamiltonian.evolve_core(
            self.psi_core,
            align_score,
            self.psi_affect,
            wm_stability,
            dt
        )

        # Core 업데이트
        self.psi_core = new_core

        # 파생 업데이트
        self.psi_fluct.record_alpha(new_core.alpha)
        self.psi_predict.update_alpha(new_core.alpha, align_score)

        self.timestamp = datetime.now()

    def record_learning_event(self, event_type: str, success: bool,
                              context: Optional[Dict] = None):
        """
        학습 이벤트 기록 및 파동함수 업데이트
        """
        # 메타인지 업데이트
        if event_type == 'problem_solved':
            self.psi_meta.self_acknowledgment(success)

        # 연쇄 붕괴 기록
        if success and self.psi_core.alpha > 0.7:
            self.psi_cascade.record_collapse(self.psi_core.alpha)

        # 컨텍스트 업데이트
        if context:
            for key, value in context.items():
                self.psi_context.set_context(key, value)

        # 루틴 업데이트
        if event_type == 'daily_activity':
            self.psi_routine.update_daily(success)

    def get_collapse_probability(self) -> float:
        """현재 붕괴 확률"""
        return self.psi_predict.CP

    def get_intervention_timing(self) -> Dict[str, Any]:
        """개입 타이밍 권장"""
        cp = self.get_collapse_probability()
        context_factor = self.psi_context.get_intervention_timing_factor()
        engage_state = self.psi_engage.get_dominant_state()

        # 개입 적절성 스코어
        timing_score = cp * context_factor

        # 이탈 상태면 감점
        if engage_state != EngageState.FOCUS:
            timing_score *= 0.5

        return {
            'timing_score': round(timing_score, 3),
            'collapse_probability': round(cp, 3),
            'context_factor': round(context_factor, 3),
            'engage_state': engage_state.name,
            'recommend_intervention': timing_score > 0.3,
            'eta_collapse': self.psi_predict.get_time_to_collapse_estimate()
        }

    def get_system_summary(self) -> Dict[str, Any]:
        """시스템 요약"""
        probs = self.psi_core.get_probabilities()

        return {
            'student_id': self.student_id,
            'timestamp': self.timestamp.isoformat(),
            'state_dimension': self.get_state_dimension(),
            'core_state': {
                'alpha': round(self.psi_core.alpha, 3),
                'beta': round(self.psi_core.beta, 3),
                'gamma': round(self.psi_core.gamma, 3),
                'probabilities': {k: round(v, 3) for k, v in probs.items()}
            },
            'alignment_score': round(self.psi_align.get_alignment_score(), 3),
            'fluctuation_level': self.psi_fluct.get_fluctuation_level(),
            'tunnel_probability': round(self.psi_tunnel.P_tunnel, 3),
            'wm_stable': self.psi_WM.is_stable(),
            'affect_dominant': self.psi_affect.get_dominant_state(),
            'routine_strength': round(self.psi_routine.get_overall_routine_strength(), 3),
            'engage_state': self.psi_engage.get_dominant_state().name,
            'concept_entanglement': round(self.psi_concept.get_total_entanglement(), 3),
            'cascade_likelihood': self.psi_cascade.get_cascade_likelihood(),
            'meta_efficacy': round(self.psi_meta.get_efficacy_score(), 3),
            'collapse_prediction': self.psi_predict.get_dashboard_indicator()
        }

    def to_json(self) -> str:
        """JSON 직렬화"""
        return json.dumps(self.get_system_summary(), indent=2, ensure_ascii=False)


# ============================================================================
# 전역 인스턴스 및 유틸리티
# ============================================================================

# 학생별 파동함수 시스템 저장소
_STUDENT_WAVEFUNCTIONS: Dict[int, QuantumWavefunctionSystem] = {}


def get_wavefunction_system(student_id: int) -> QuantumWavefunctionSystem:
    """학생별 파동함수 시스템 획득"""
    if student_id not in _STUDENT_WAVEFUNCTIONS:
        _STUDENT_WAVEFUNCTIONS[student_id] = QuantumWavefunctionSystem(student_id=student_id)
    return _STUDENT_WAVEFUNCTIONS[student_id]


def reset_wavefunction_system(student_id: int):
    """학생의 파동함수 시스템 리셋"""
    if student_id in _STUDENT_WAVEFUNCTIONS:
        del _STUDENT_WAVEFUNCTIONS[student_id]


# ============================================================================
# 테스트 함수
# ============================================================================

def run_wavefunction_system_test():
    """Phase 6 파동함수 시스템 테스트"""
    print("=" * 60)
    print("Phase 6: 13종 파동함수 시스템 테스트")
    print("=" * 60)

    # 1. 시스템 생성
    print("\n[1] 파동함수 시스템 생성")
    system = get_wavefunction_system(student_id=12345)
    print(f"  - 학생 ID: {system.student_id}")
    print(f"  - 상태 공간 차원: {system.get_state_dimension()}D")

    # 2. 각 파동함수 초기 상태
    print("\n[2] 13종 파동함수 초기 상태")
    summary = system.get_system_summary()
    print(f"  - Core (α,β,γ): ({summary['core_state']['alpha']}, "
          f"{summary['core_state']['beta']}, {summary['core_state']['gamma']})")
    print(f"  - 정렬 스코어: {summary['alignment_score']}")
    print(f"  - 요동 수준: {summary['fluctuation_level']}")
    print(f"  - 터널링 확률: {summary['tunnel_probability']}")
    print(f"  - 작업기억 안정: {summary['wm_stable']}")
    print(f"  - 지배적 정서: {summary['affect_dominant']}")
    print(f"  - 루틴 강도: {summary['routine_strength']}")
    print(f"  - 참여 상태: {summary['engage_state']}")
    print(f"  - 개념 얽힘: {summary['concept_entanglement']}")
    print(f"  - 연쇄 가능성: {summary['cascade_likelihood']}")
    print(f"  - 메타인지 효능감: {summary['meta_efficacy']}")

    # 3. Hamiltonian 진화 테스트
    print("\n[3] Hamiltonian 시간 진화 (5단계)")
    for step in range(5):
        # 정렬 증가 시뮬레이션
        system.psi_align.features[0] = min(1.0, 0.3 + step * 0.15)

        # 진화
        system.evolve(dt=1.0)

        print(f"  Step {step+1}: α={system.psi_core.alpha:.3f}, "
              f"CP={system.get_collapse_probability():.4f}")

    # 4. 학습 이벤트 기록
    print("\n[4] 학습 이벤트 기록")
    system.record_learning_event('problem_solved', success=True,
                                  context={'motivation': 0.8, 'fatigue_level': 0.2})
    system.record_learning_event('problem_solved', success=True)

    print(f"  - 메타인지 효능감: {system.psi_meta.get_efficacy_score():.3f}")
    print(f"  - baseline 기여: {system.psi_meta.get_baseline_contribution():.3f}")

    # 5. 개입 타이밍 권장
    print("\n[5] 개입 타이밍 분석")
    timing = system.get_intervention_timing()
    print(f"  - 타이밍 스코어: {timing['timing_score']}")
    print(f"  - 붕괴 확률: {timing['collapse_probability']}")
    print(f"  - 컨텍스트 요인: {timing['context_factor']}")
    print(f"  - 개입 권장: {'예' if timing['recommend_intervention'] else '아니오'}")

    # 6. 개념 얽힘 테스트
    print("\n[6] 개념 얽힘 테스트")
    system.psi_concept.set_entanglement(0, 1, 0.8)
    system.psi_concept.set_entanglement(1, 2, 0.6)

    effects = system.psi_concept.propagate_collapse(0, 0.9)
    print(f"  - 개념 0 붕괴 시 전파: {effects}")
    print(f"  - 전체 얽힘 강도: {system.psi_concept.get_total_entanglement():.3f}")

    # 7. 터널링 테스트
    print("\n[7] 터널링 테스트")
    system.psi_tunnel.E_cog = 0.6
    system.psi_tunnel.B_concept = 0.7
    system.psi_tunnel._calculate_tunnel_probability()

    print(f"  - 인지 에너지: {system.psi_tunnel.E_cog}")
    print(f"  - 개념 장벽: {system.psi_tunnel.B_concept}")
    print(f"  - 터널링 확률: {system.psi_tunnel.P_tunnel:.3f}")
    print(f"  - 터널링 가능: {'예' if system.psi_tunnel.can_tunnel() else '아니오'}")

    # 8. 최종 요약
    print("\n[8] 최종 시스템 요약")
    final_summary = system.get_system_summary()
    print(f"  - 최종 α: {final_summary['core_state']['alpha']}")
    print(f"  - 붕괴 예측: {final_summary['collapse_prediction']}")

    print("\n" + "=" * 60)
    print("Phase 6 테스트 완료!")
    print("=" * 60)

    return system


# 직접 실행 시
if __name__ == "__main__":
    run_wavefunction_system_test()


# ============================================================================
# DB 참조 정보
# ============================================================================
"""
관련 DB 테이블 (향후 연동용):

1. mdl_quantum_wavefunction_state
   - id: BIGINT PRIMARY KEY
   - student_id: BIGINT (FK → mdl_user.id)
   - wavefunction_type: VARCHAR(20) (core, align, fluct, ...)
   - state_vector: JSON
   - timestamp: DATETIME
   - metadata: JSON

2. mdl_quantum_hamiltonian_params
   - id: BIGINT PRIMARY KEY
   - param_name: VARCHAR(50)
   - param_value: FLOAT
   - coupling_type: VARCHAR(30)
   - updated_at: DATETIME

3. mdl_quantum_collapse_events
   - id: BIGINT PRIMARY KEY
   - student_id: BIGINT
   - collapse_type: VARCHAR(30)
   - alpha_at_collapse: FLOAT
   - cascade_level: VARCHAR(10)
   - timestamp: DATETIME

File: _quantum_wavefunction_system.py
Location: /holons/
"""
