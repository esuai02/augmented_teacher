# -*- coding: utf-8 -*-
"""
Phase 5: Temporal-Orchestrator Integration
==========================================

시간적 얽힘(Temporal Entanglement)과 Quantum Orchestrator의 통합.

주요 기능:
---------
1. 시간 감쇠 가중 우선순위 (Time-Decay Weighted Priority)
   - 최근 활동 에이전트에 부스트 적용
   - 잔류파 감쇠율 기반 priority 조정

2. 학습 모멘텀 팩터 (Learning Momentum Factor)
   - accelerating 트렌드 에이전트 우대
   - declining 에이전트 패널티 적용

3. 비국소적 상관 전파 (Non-local Correlation Propagation)
   - 활성화된 에이전트의 효과를 연결된 에이전트에 전파
   - 양자 얽힘 기반 시너지 최적화

Python 3.6+ 호환 (dataclasses 미사용)

Author: Quantum Learning System
Version: 1.0.0
"""

from __future__ import print_function, unicode_literals
import sys
import io
import math
import time
from typing import Dict, List, Tuple, Optional, Any
from enum import Enum

# UTF-8 인코딩 강제 설정 (서버 환경 호환)
if sys.version_info[0] >= 3:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    elif hasattr(sys.stdout, 'buffer'):
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
else:
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout)

# Phase 1-3 모듈 임포트
from _quantum_persona_mapper import (
    StateVector,
    calculate_similarity
)
from _quantum_entanglement import (
    ENTANGLEMENT_MAP,
    get_agent_name,
    get_correlation,
    get_entangled_agents
)
from _quantum_orchestrator import (
    QuantumOrchestrator,
    OrchestratorMode,
    AgentPriority,
    AgentSignal,
    HamiltonianEvolution,
    InterferenceCalculator,
    AGENT_OPTIMAL_STATES,
    SCENARIO_PHASE_MAP
)

# Phase 4 모듈 임포트
from _quantum_temporal import (
    TemporalEntanglement,
    TemporalEvent,
    TEMPORAL_ENTANGLEMENT,
    DEFAULT_DECAY_RATE,
    SECONDS_PER_WEEK,
    MIN_AMPLITUDE_THRESHOLD,
    NON_LOCAL_PROPAGATION_RATE,
    record_learning_event,
    get_agent_memory_strength
)

# ============================================================================
# Constants
# ============================================================================

# 시간 가중치 계수들
TEMPORAL_WEIGHT = 0.15       # 전체 우선순위에서 시간 요소 비중
MOMENTUM_WEIGHT = 0.10       # 모멘텀 요소 비중
NON_LOCAL_WEIGHT = 0.05      # 비국소적 전파 요소 비중

# 모멘텀 계수
MOMENTUM_BOOST_ACCELERATING = 0.20  # accelerating 트렌드 부스트
MOMENTUM_BOOST_GROWING = 0.10       # growing 트렌드 부스트
MOMENTUM_PENALTY_DECLINING = -0.10  # declining 패널티

# 비국소적 전파 최대 홉
MAX_PROPAGATION_HOPS = 2

# 최근 활동 윈도우 (주)
RECENT_ACTIVITY_WINDOW_WEEKS = 2


# ============================================================================
# TemporalAgentPriority Class
# ============================================================================

class TemporalAgentPriority(object):
    """
    시간적 요소가 포함된 에이전트 우선순위 (Python 3.6 호환)

    AgentPriority를 확장하여 시간적 요소를 추가합니다.
    """

    def __init__(
        self,
        agent_id,              # type: int
        priority_score,        # type: float
        entanglement_bonus,    # type: float
        state_alignment,       # type: float
        signal_strength,       # type: float
        temporal_boost=0.0,    # type: float
        momentum_factor=0.0,   # type: float
        non_local_boost=0.0,   # type: float
        momentum_trend='stable'  # type: str
    ):
        # type: (...) -> None
        self.agent_id = agent_id
        self.priority_score = priority_score
        self.entanglement_bonus = entanglement_bonus
        self.state_alignment = state_alignment
        self.signal_strength = signal_strength
        self.temporal_boost = temporal_boost
        self.momentum_factor = momentum_factor
        self.non_local_boost = non_local_boost
        self.momentum_trend = momentum_trend

        # 최종 점수 계산
        self.final_score = (
            priority_score +
            temporal_boost * TEMPORAL_WEIGHT +
            momentum_factor * MOMENTUM_WEIGHT +
            non_local_boost * NON_LOCAL_WEIGHT
        )

    def __lt__(self, other):
        return self.final_score < other.final_score

    def to_dict(self):
        # type: () -> Dict[str, Any]
        return {
            'agent_id': self.agent_id,
            'agent_name': get_agent_name(self.agent_id),
            'final_score': round(self.final_score, 4),
            'priority_score': round(self.priority_score, 4),
            'entanglement_bonus': round(self.entanglement_bonus, 4),
            'state_alignment': round(self.state_alignment, 4),
            'signal_strength': round(self.signal_strength, 4),
            'temporal_boost': round(self.temporal_boost, 4),
            'momentum_factor': round(self.momentum_factor, 4),
            'momentum_trend': self.momentum_trend,
            'non_local_boost': round(self.non_local_boost, 4)
        }


# ============================================================================
# TemporalQuantumOrchestrator Class
# ============================================================================

class TemporalQuantumOrchestrator(object):
    """
    시간적 얽힘을 통합한 Quantum Orchestrator

    기존 QuantumOrchestrator에 다음 기능을 추가:
    1. 시간 감쇠 가중 우선순위
    2. 학습 모멘텀 팩터
    3. 비국소적 상관 전파

    Attributes:
        base_orchestrator: 기본 QuantumOrchestrator
        temporal: TemporalEntanglement 인스턴스
        mode: 운영 모드
        history: 결정 히스토리
    """

    def __init__(
        self,
        mode=OrchestratorMode.OBSERVE,  # type: OrchestratorMode
        temporal=None,                   # type: Optional[TemporalEntanglement]
        decay_rate=DEFAULT_DECAY_RATE    # type: float
    ):
        # type: (...) -> None
        """
        Args:
            mode: 운영 모드 (OBSERVE, COMPARE, SUGGEST, ACTIVE)
            temporal: TemporalEntanglement 인스턴스 (None이면 전역 인스턴스 사용)
            decay_rate: 감쇠율 (전역 인스턴스 사용 시 무시)
        """
        self.mode = mode
        self.base_orchestrator = QuantumOrchestrator(mode=mode)

        # Temporal 인스턴스 설정
        if temporal is not None:
            self.temporal = temporal
        else:
            self.temporal = TEMPORAL_ENTANGLEMENT

        # 히스토리 (시간 정보 포함)
        self.history = []  # type: List[Dict]

        # 캐시 (성능 최적화)
        self._momentum_cache = {}  # type: Dict[int, Dict]
        self._cache_time = 0.0
        self._cache_ttl = 60.0  # 캐시 유효 시간 (초)

    def _clear_cache_if_expired(self, current_time):
        # type: (float) -> None
        """만료된 캐시 클리어"""
        if current_time - self._cache_time > self._cache_ttl:
            self._momentum_cache.clear()
            self._cache_time = current_time

    def _get_temporal_boost(
        self,
        agent_id,         # type: int
        current_time      # type: float
    ):
        # type: (...) -> float
        """
        에이전트의 시간 기반 부스트 계산

        최근 활동이 많은 에이전트에게 부스트를 부여합니다.
        """
        agent_str = str(agent_id)

        # 누적 진폭 기반 부스트
        cumulative_amp = self.temporal.get_cumulative_amplitude(
            agent_id=agent_str,
            current_time=current_time
        )

        # 0 ~ 1 범위로 정규화 (로그 스케일)
        if cumulative_amp > 0:
            # log(1 + amp) / log(1 + max_expected)
            normalized = math.log(1 + cumulative_amp) / math.log(1 + 5.0)
            return min(1.0, normalized)

        return 0.0

    def _get_momentum_factor(
        self,
        agent_id,         # type: int
        current_time      # type: float
    ):
        # type: (...) -> Tuple[float, str]
        """
        에이전트의 학습 모멘텀 팩터 계산

        Returns:
            (모멘텀 팩터, 트렌드)
        """
        agent_str = str(agent_id)

        # 캐시 확인
        self._clear_cache_if_expired(current_time)
        if agent_id in self._momentum_cache:
            cached = self._momentum_cache[agent_id]
            return cached['factor'], cached['trend']

        # 모멘텀 계산
        momentum_info = self.temporal.get_learning_momentum(
            agent_id=agent_str,
            window_weeks=RECENT_ACTIVITY_WINDOW_WEEKS,
            current_time=current_time
        )

        trend = momentum_info.get('trend', 'inactive')

        # 트렌드에 따른 팩터 결정
        if trend == 'accelerating':
            factor = MOMENTUM_BOOST_ACCELERATING
        elif trend == 'growing':
            factor = MOMENTUM_BOOST_GROWING
        elif trend == 'declining':
            factor = MOMENTUM_PENALTY_DECLINING
        else:
            factor = 0.0

        # 캐시 저장
        self._momentum_cache[agent_id] = {'factor': factor, 'trend': trend}

        return factor, trend

    def _get_non_local_boost(
        self,
        agent_id,             # type: int
        active_agents,        # type: List[int]
        current_time          # type: float
    ):
        # type: (...) -> float
        """
        비국소적 상관관계 기반 부스트 계산

        이미 활성화된 에이전트들로부터 전파되는 부스트
        """
        if not active_agents:
            return 0.0

        agent_str = str(agent_id)
        total_boost = 0.0

        for active_id in active_agents:
            if active_id == agent_id:
                continue

            active_str = str(active_id)

            # 시간적 상관관계
            temporal_corr = self.temporal.get_non_local_correlation(
                source_agent_id=active_str,
                target_agent_id=agent_str,
                current_time=current_time
            )

            # 구조적 상관관계 (EntanglementMap)
            structural_corr = get_correlation(active_id, agent_id)

            # 두 상관관계의 기하평균
            if temporal_corr > 0 and structural_corr > 0:
                combined = math.sqrt(temporal_corr * structural_corr)
            else:
                combined = max(temporal_corr, structural_corr) * 0.5

            total_boost += combined * NON_LOCAL_PROPAGATION_RATE

        # 최대 부스트 제한
        return min(0.5, total_boost)

    def suggest_temporal_order(
        self,
        student_state,         # type: StateVector
        triggered_agents,      # type: List[int]
        agent_priorities=None,   # type: Optional[Dict[int, int]]
        agent_confidences=None,  # type: Optional[Dict[int, float]]
        agent_scenarios=None,    # type: Optional[Dict[int, str]]
        current_time=None        # type: Optional[float]
    ):
        # type: (...) -> List[TemporalAgentPriority]
        """
        시간적 요소를 포함한 최적 에이전트 순서 제안

        Args:
            student_state: 현재 학생 상태 벡터
            triggered_agents: 트리거된 에이전트 ID 목록
            agent_priorities: 에이전트별 priority (기본 90)
            agent_confidences: 에이전트별 confidence (기본 0.9)
            agent_scenarios: 에이전트별 scenario (기본 S0)
            current_time: 현재 시간 (None이면 현재)

        Returns:
            시간 가중 우선순위로 정렬된 TemporalAgentPriority 목록
        """
        if not triggered_agents:
            return []

        if current_time is None:
            current_time = time.time()

        # 1. 기본 순서 계산 (기존 Orchestrator 사용)
        base_results = self.base_orchestrator.suggest_agent_order(
            student_state=student_state,
            triggered_agents=triggered_agents,
            agent_priorities=agent_priorities,
            agent_confidences=agent_confidences,
            agent_scenarios=agent_scenarios
        )

        # 2. 시간적 요소 추가
        temporal_results = []
        processed_agents = []  # 비국소적 부스트 계산용

        for base_priority in base_results:
            aid = base_priority.agent_id

            # 시간 부스트
            temporal_boost = self._get_temporal_boost(aid, current_time)

            # 모멘텀 팩터
            momentum_factor, momentum_trend = self._get_momentum_factor(aid, current_time)

            # 비국소적 부스트 (이전에 처리된 에이전트들로부터)
            non_local_boost = self._get_non_local_boost(aid, processed_agents, current_time)

            # TemporalAgentPriority 생성
            temporal_priority = TemporalAgentPriority(
                agent_id=aid,
                priority_score=base_priority.priority_score,
                entanglement_bonus=base_priority.entanglement_bonus,
                state_alignment=base_priority.state_alignment,
                signal_strength=base_priority.signal_strength,
                temporal_boost=temporal_boost,
                momentum_factor=momentum_factor,
                non_local_boost=non_local_boost,
                momentum_trend=momentum_trend
            )

            temporal_results.append(temporal_priority)
            processed_agents.append(aid)

        # 3. 최종 점수로 재정렬
        temporal_results.sort(reverse=True)

        # 4. 히스토리 기록
        self._log_temporal_decision(
            student_state=student_state,
            triggered_agents=triggered_agents,
            results=temporal_results,
            current_time=current_time
        )

        return temporal_results

    def _log_temporal_decision(
        self,
        student_state,      # type: StateVector
        triggered_agents,   # type: List[int]
        results,            # type: List[TemporalAgentPriority]
        current_time        # type: float
    ):
        # type: (...) -> None
        """시간적 결정 히스토리 기록"""
        self.history.append({
            'timestamp': current_time,
            'state': student_state.to_dict(),
            'triggered_agents': triggered_agents,
            'suggested_order': [r.agent_id for r in results],
            'final_scores': {r.agent_id: r.final_score for r in results},
            'temporal_boosts': {r.agent_id: r.temporal_boost for r in results},
            'momentum_trends': {r.agent_id: r.momentum_trend for r in results}
        })

    def record_agent_activation(
        self,
        agent_id,             # type: int
        amplitude=0.8,        # type: float
        tags=None,            # type: Optional[List[str]]
        context=None,         # type: Optional[Dict[str, Any]]
        current_time=None     # type: Optional[float]
    ):
        # type: (...) -> TemporalEvent
        """
        에이전트 활성화를 기록합니다.

        Orchestrator가 에이전트를 활성화할 때 호출하여
        시간적 이력을 축적합니다.

        Args:
            agent_id: 활성화된 에이전트 ID
            amplitude: 활성화 강도 (기본 0.8)
            tags: 관련 태그 (학습 주제 등)
            context: 추가 컨텍스트
            current_time: 활성화 시간 (None이면 현재)

        Returns:
            생성된 TemporalEvent
        """
        if current_time is None:
            current_time = time.time()

        return self.temporal.record_event(
            agent_id=str(agent_id),
            amplitude=amplitude,
            tags=tags,
            context=context,
            timestamp=current_time
        )

    def get_flow_optimized_temporal_order(
        self,
        student_state,         # type: StateVector
        triggered_agents,      # type: List[int]
        current_time=None      # type: Optional[float]
    ):
        # type: (...) -> Tuple[List[TemporalAgentPriority], StateVector]
        """
        Flow State + 시간적 요소 최적화된 에이전트 순서

        Returns:
            (우선순위 목록, 예상 최종 상태)
        """
        if current_time is None:
            current_time = time.time()

        # Flow 최적화 순서 (기본 Orchestrator)
        flow_results, evolved_state = self.base_orchestrator.get_flow_optimized_order(
            student_state=student_state,
            triggered_agents=triggered_agents
        )

        # 시간적 요소 추가
        temporal_results = []
        processed_agents = []

        for flow_priority in flow_results:
            aid = flow_priority.agent_id

            temporal_boost = self._get_temporal_boost(aid, current_time)
            momentum_factor, momentum_trend = self._get_momentum_factor(aid, current_time)
            non_local_boost = self._get_non_local_boost(aid, processed_agents, current_time)

            temporal_priority = TemporalAgentPriority(
                agent_id=aid,
                priority_score=flow_priority.priority_score,
                entanglement_bonus=flow_priority.entanglement_bonus,
                state_alignment=flow_priority.state_alignment,
                signal_strength=flow_priority.signal_strength,
                temporal_boost=temporal_boost,
                momentum_factor=momentum_factor,
                non_local_boost=non_local_boost,
                momentum_trend=momentum_trend
            )

            temporal_results.append(temporal_priority)
            processed_agents.append(aid)

        temporal_results.sort(reverse=True)

        return temporal_results, evolved_state

    def propagate_activation_effects(
        self,
        activated_agent_id,   # type: int
        amplitude=1.0,        # type: float
        current_time=None     # type: Optional[float]
    ):
        # type: (...) -> Dict[int, float]
        """
        활성화 효과를 연결된 에이전트들에게 전파합니다.

        양자 얽힘의 비국소성처럼, 한 에이전트의 활성화가
        연결된 에이전트들에게 즉각적으로 영향을 미칩니다.

        Args:
            activated_agent_id: 활성화된 에이전트 ID
            amplitude: 전파 진폭
            current_time: 현재 시간

        Returns:
            에이전트별 전파된 효과 (int agent_id -> float boost)
        """
        if current_time is None:
            current_time = time.time()

        agent_str = str(activated_agent_id)

        # 시간적 전파
        temporal_propagation = self.temporal.propagate_non_local_effect(
            source_agent_id=agent_str,
            amplitude_boost=amplitude,
            current_time=current_time
        )

        # 구조적 전파 (EntanglementMap)
        # get_entangled_agents returns List[Tuple[int, float]] = [(agent_id, correlation), ...]
        entangled_agents = get_entangled_agents(activated_agent_id)
        structural_propagation = {}

        for entangled_id, existing_corr in entangled_agents:
            # existing_corr는 이미 threshold 이상인 상관관계
            if existing_corr > 0:
                structural_propagation[entangled_id] = amplitude * existing_corr * 0.1

        # 두 전파 효과 병합
        merged = {}

        # 시간적 전파 (문자열 키 → 정수 키)
        for agent_str_key, boost in temporal_propagation.items():
            try:
                aid = int(agent_str_key)
                merged[aid] = merged.get(aid, 0.0) + boost
            except (ValueError, TypeError):
                pass

        # 구조적 전파 추가
        for aid, boost in structural_propagation.items():
            merged[aid] = merged.get(aid, 0.0) + boost

        return merged

    def get_optimal_activation_sequence(
        self,
        student_state,         # type: StateVector
        triggered_agents,      # type: List[int]
        max_activations=3,     # type: int
        current_time=None      # type: Optional[float]
    ):
        # type: (...) -> List[Dict[str, Any]]
        """
        최적의 에이전트 활성화 시퀀스를 계산합니다.

        각 활성화 후 전파 효과를 고려하여
        전체 시퀀스를 최적화합니다.

        Args:
            student_state: 현재 학생 상태
            triggered_agents: 트리거된 에이전트들
            max_activations: 최대 활성화 수
            current_time: 현재 시간

        Returns:
            활성화 시퀀스 (각 단계별 정보 포함)
        """
        if current_time is None:
            current_time = time.time()

        sequence = []
        remaining_agents = list(triggered_agents)
        cumulative_boost = {}  # 누적 부스트 추적

        for step in range(min(max_activations, len(triggered_agents))):
            # 현재 시점에서 최적 순서 계산
            results = self.suggest_temporal_order(
                student_state=student_state,
                triggered_agents=remaining_agents,
                current_time=current_time
            )

            if not results:
                break

            # 최고 우선순위 에이전트 선택
            best = results[0]

            # 누적 부스트 적용
            adjusted_score = best.final_score + cumulative_boost.get(best.agent_id, 0.0)

            # 시퀀스에 추가
            step_info = {
                'step': step + 1,
                'agent_id': best.agent_id,
                'agent_name': get_agent_name(best.agent_id),
                'base_score': best.final_score,
                'adjusted_score': adjusted_score,
                'momentum_trend': best.momentum_trend,
                'propagation_preview': {}
            }

            # 전파 효과 미리보기
            propagation = self.propagate_activation_effects(
                best.agent_id, 0.8, current_time
            )
            step_info['propagation_preview'] = {
                get_agent_name(aid): round(boost, 4)
                for aid, boost in propagation.items()
            }

            # 누적 부스트 업데이트
            for aid, boost in propagation.items():
                cumulative_boost[aid] = cumulative_boost.get(aid, 0.0) + boost

            sequence.append(step_info)
            remaining_agents.remove(best.agent_id)

        return sequence

    def get_statistics(self, current_time=None):
        # type: (Optional[float]) -> Dict[str, Any]
        """시스템 통계 반환"""
        if current_time is None:
            current_time = time.time()

        temporal_stats = self.temporal.get_statistics(current_time)

        return {
            'mode': self.mode.value,
            'history_count': len(self.history),
            'cache_size': len(self._momentum_cache),
            'temporal_stats': temporal_stats,
            'base_orchestrator_history': len(self.base_orchestrator.history)
        }


# ============================================================================
# Global Instance
# ============================================================================

# 전역 TemporalQuantumOrchestrator 인스턴스
TEMPORAL_ORCHESTRATOR = TemporalQuantumOrchestrator(mode=OrchestratorMode.SUGGEST)


# ============================================================================
# Convenience Functions
# ============================================================================

def suggest_agent_order_with_temporal(
    student_state,         # type: StateVector
    triggered_agents,      # type: List[int]
    current_time=None      # type: Optional[float]
):
    # type: (...) -> List[TemporalAgentPriority]
    """
    시간적 요소를 포함한 에이전트 순서 제안 편의 함수.

    Example:
        >>> from _quantum_persona_mapper import StateVector
        >>> state = StateVector(anxiety=0.8, confidence=0.3)
        >>> results = suggest_agent_order_with_temporal(state, [5, 8, 10, 12])
        >>> for r in results:
        ...     print(f"{r.agent_name}: {r.final_score:.3f}")
    """
    return TEMPORAL_ORCHESTRATOR.suggest_temporal_order(
        student_state=student_state,
        triggered_agents=triggered_agents,
        current_time=current_time
    )


def record_activation(agent_id, amplitude=0.8, tags=None):
    # type: (int, float, Optional[List[str]]) -> TemporalEvent
    """
    에이전트 활성화 기록 편의 함수.

    Example:
        >>> record_activation(5, 0.9, tags=['emotion', 'support'])
    """
    return TEMPORAL_ORCHESTRATOR.record_agent_activation(
        agent_id=agent_id,
        amplitude=amplitude,
        tags=tags
    )


# ============================================================================
# Module Documentation
# ============================================================================

__all__ = [
    # Classes
    'TemporalAgentPriority',
    'TemporalQuantumOrchestrator',

    # Constants
    'TEMPORAL_WEIGHT',
    'MOMENTUM_WEIGHT',
    'NON_LOCAL_WEIGHT',
    'MOMENTUM_BOOST_ACCELERATING',
    'MOMENTUM_BOOST_GROWING',
    'MOMENTUM_PENALTY_DECLINING',

    # Global Instance
    'TEMPORAL_ORCHESTRATOR',

    # Convenience Functions
    'suggest_agent_order_with_temporal',
    'record_activation',
]


# ============================================================================
# Test Function
# ============================================================================

def run_temporal_orchestrator_test():
    """Temporal-Orchestrator 통합 테스트"""
    print("=" * 70)
    print("Phase 5: Temporal-Orchestrator Integration Test")
    print("=" * 70)
    print()

    # 테스트용 인스턴스 생성
    temporal = TemporalEntanglement(decay_rate=0.3)
    orchestrator = TemporalQuantumOrchestrator(
        mode=OrchestratorMode.SUGGEST,
        temporal=temporal
    )

    # 현재 시간
    now = time.time()

    # 1. 과거 학습 이력 시뮬레이션
    print("[1] Simulating past learning events...")
    print("-" * 50)

    # 4주 전 - Agent 5 (Learning Emotion) 활동
    temporal.record_event(
        agent_id='5',
        amplitude=0.9,
        tags=['emotion', 'support'],
        timestamp=now - 4 * SECONDS_PER_WEEK
    )

    # 2주 전 - Agent 8 (Calmness) 활동
    temporal.record_event(
        agent_id='8',
        amplitude=0.85,
        tags=['calmness', 'anxiety'],
        timestamp=now - 2 * SECONDS_PER_WEEK
    )

    # 1주 전 - Agent 5 다시 활동 (accelerating 트렌드)
    temporal.record_event(
        agent_id='5',
        amplitude=0.95,
        tags=['emotion', 'motivation'],
        timestamp=now - 1 * SECONDS_PER_WEEK
    )

    # 3일 전 - Agent 10 (Concept Notes) 활동
    temporal.record_event(
        agent_id='10',
        amplitude=0.8,
        tags=['concept', 'math'],
        timestamp=now - 3 * 24 * 60 * 60
    )

    # 오늘 - Agent 5 또 활동
    temporal.record_event(
        agent_id='5',
        amplitude=0.9,
        tags=['emotion', 'engagement'],
        timestamp=now
    )

    print("   Recorded 5 learning events")
    print()

    # 2. 학습 모멘텀 확인
    print("[2] Learning Momentum Analysis")
    print("-" * 50)

    for agent_id in [5, 8, 10, 12]:
        momentum = temporal.get_learning_momentum(
            agent_id=str(agent_id),
            window_weeks=4,
            current_time=now
        )
        print("   Agent {:2d} ({:18s}): {} (momentum: {:.2f})".format(
            agent_id,
            get_agent_name(agent_id),
            momentum['trend'],
            momentum['momentum']
        ))
    print()

    # 3. 불안한 학생 상태
    print("[3] Test Student State (Anxious Student)")
    print("-" * 50)

    anxious_student = StateVector(
        metacognition=0.50,
        self_efficacy=0.35,
        help_seeking=0.60,
        emotional_regulation=0.30,
        anxiety=0.80,
        confidence=0.30,
        engagement=0.45,
        motivation=0.50
    )

    for k, v in anxious_student.to_dict().items():
        bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
        print("   {:20s}: {} {:.2f}".format(k, bar, v))
    print()

    # 4. 시간적 우선순위 제안
    print("[4] Temporal Priority Suggestion")
    print("-" * 50)

    triggered = [5, 8, 10, 12]
    print("   Triggered agents: {}".format([get_agent_name(a) for a in triggered]))
    print()

    results = orchestrator.suggest_temporal_order(
        student_state=anxious_student,
        triggered_agents=triggered,
        current_time=now
    )

    print("   Rank | Agent                | Final   | Base    | Temporal | Momentum  | Trend")
    print("   " + "-" * 80)

    for i, r in enumerate(results, 1):
        print("   {:4d} | {:20s} | {:.4f} | {:.4f} | {:.4f}   | {:.4f}    | {}".format(
            i,
            get_agent_name(r.agent_id),
            r.final_score,
            r.priority_score,
            r.temporal_boost,
            r.momentum_factor,
            r.momentum_trend
        ))
    print()

    # 5. 활성화 시퀀스 최적화
    print("[5] Optimal Activation Sequence")
    print("-" * 50)

    sequence = orchestrator.get_optimal_activation_sequence(
        student_state=anxious_student,
        triggered_agents=triggered,
        max_activations=3,
        current_time=now
    )

    for step_info in sequence:
        print("   Step {}: {} (score: {:.4f}, trend: {})".format(
            step_info['step'],
            step_info['agent_name'],
            step_info['adjusted_score'],
            step_info['momentum_trend']
        ))
        if step_info['propagation_preview']:
            print("      → Propagates to: {}".format(
                ", ".join("{}: {:.3f}".format(k, v)
                         for k, v in list(step_info['propagation_preview'].items())[:3])
            ))
    print()

    # 6. 전파 효과 테스트
    print("[6] Propagation Effects")
    print("-" * 50)

    propagation = orchestrator.propagate_activation_effects(
        activated_agent_id=5,
        amplitude=1.0,
        current_time=now
    )

    print("   Source: Agent 5 (Learning Emotion)")
    print("   Propagated effects:")
    for aid, boost in sorted(propagation.items(), key=lambda x: -x[1])[:5]:
        print("      → Agent {:2d} ({:18s}): {:.4f}".format(
            aid, get_agent_name(aid), boost
        ))
    print()

    # 7. 통계
    print("[7] System Statistics")
    print("-" * 50)

    stats = orchestrator.get_statistics(now)
    print("   Mode: {}".format(stats['mode']))
    print("   History count: {}".format(stats['history_count']))
    print("   Temporal events: {}".format(stats['temporal_stats']['total_events']))
    print("   Active amplitude: {:.3f}".format(stats['temporal_stats']['total_amplitude']))
    print()

    print("=" * 70)
    print("✅ Phase 5 Temporal-Orchestrator Integration Test Complete")
    print("=" * 70)

    return {
        'order': [r.agent_id for r in results],
        'sequence': sequence,
        'propagation': propagation,
        'stats': stats
    }


# ============================================================================
# Main
# ============================================================================

if __name__ == "__main__":
    run_temporal_orchestrator_test()
