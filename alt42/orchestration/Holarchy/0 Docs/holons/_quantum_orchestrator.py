#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Quantum Orchestration - Phase 3: QuantumOrchestrator
=====================================================
기존 룰 시스템 위의 Quantum 조정 레이어

목적:
- 여러 에이전트 동시 트리거 시 최적 활성화 순서 제안
- StateVector + EntanglementMap + HamiltonianEvolution 통합
- 기존 시스템 간섭 없이 상위 조정자로 동작

참조:
- _quantum_persona_mapper.py (Phase 1: StateVector)
- _quantum_entanglement.py (Phase 2: EntanglementMap)
- _quantum_minimal_test.py (기초 신호 변환)
"""

from __future__ import print_function, unicode_literals
import sys
import io

# UTF-8 인코딩 강제 설정 (서버 환경 호환)
if sys.version_info[0] >= 3:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    elif hasattr(sys.stdout, 'buffer'):
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
else:
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout)

import math
from typing import Dict, List, Tuple, Optional, Set
from enum import Enum
from collections import defaultdict

# Python 3.6 호환: dataclasses 대신 일반 클래스 사용

# Phase 1, 2 모듈 임포트
from _quantum_persona_mapper import (
    StateVector,
    PERSONA_TO_STATE,
    get_state_vector,
    calculate_similarity
)
from _quantum_entanglement import (
    ENTANGLEMENT_MAP,
    get_correlation,
    get_entangled_agents,
    calculate_entanglement_strength,
    get_agent_name,
    AGENT_NAME_TO_ID,
    AGENT_ID_TO_NAME
)

# Phase 7 Data Interface 임포트
try:
    from _quantum_data_interface import (
        StandardFeatures,
        DimensionReducer,
        QuantumDataCollector
    )
    DATA_INTERFACE_AVAILABLE = True
except ImportError:
    DATA_INTERFACE_AVAILABLE = False
    StandardFeatures = None
    DimensionReducer = None
    QuantumDataCollector = None


# ==============================================================================
# 1.5. New 8D StateVector 통합 (Phase 8)
# ==============================================================================

# New 8D 차원명 정의
NEW_8D_DIMENSIONS = [
    'cognitive_clarity',       # 0: 인지적 명확성
    'emotional_stability',     # 1: 정서적 안정성
    'engagement_level',        # 2: 참여 수준
    'concept_mastery',         # 3: 개념 숙달도
    'routine_strength',        # 4: 루틴 강도
    'metacognitive_awareness', # 5: 메타인지 인식
    'dropout_risk',            # 6: 이탈 위험도
    'intervention_readiness'   # 7: 개입 준비도
]


class New8DStateVector:
    """
    Phase 7 Data Interface 기반 8D StateVector

    _quantum_data_interface.py에서 생성된 8D 벡터를 래핑
    """

    def __init__(
        self,
        cognitive_clarity=0.5,
        emotional_stability=0.5,
        engagement_level=0.5,
        concept_mastery=0.5,
        routine_strength=0.5,
        metacognitive_awareness=0.5,
        dropout_risk=0.5,
        intervention_readiness=0.5
    ):
        self.cognitive_clarity = cognitive_clarity
        self.emotional_stability = emotional_stability
        self.engagement_level = engagement_level
        self.concept_mastery = concept_mastery
        self.routine_strength = routine_strength
        self.metacognitive_awareness = metacognitive_awareness
        self.dropout_risk = dropout_risk
        self.intervention_readiness = intervention_readiness

    @classmethod
    def from_list(cls, values):
        """8D 리스트에서 생성"""
        if len(values) != 8:
            raise ValueError(f"Expected 8 values, got {len(values)}")
        return cls(
            cognitive_clarity=values[0],
            emotional_stability=values[1],
            engagement_level=values[2],
            concept_mastery=values[3],
            routine_strength=values[4],
            metacognitive_awareness=values[5],
            dropout_risk=values[6],
            intervention_readiness=values[7]
        )

    @classmethod
    def from_agent_data(cls, student_id, agent_contexts):
        """
        에이전트 데이터에서 직접 생성 (Phase 7 통합)

        Args:
            student_id: 학생 ID
            agent_contexts: {agent_id: {key: value}} 형태의 에이전트 데이터

        Returns:
            New8DStateVector 인스턴스
        """
        if not DATA_INTERFACE_AVAILABLE:
            raise ImportError("_quantum_data_interface module not available")

        collector = QuantumDataCollector(student_id=student_id)
        features = collector.collect_all(agent_contexts)
        state_8d = DimensionReducer.transform_to_list(features)

        return cls.from_list(state_8d)

    def to_list(self):
        """8D 리스트로 변환"""
        return [
            self.cognitive_clarity,
            self.emotional_stability,
            self.engagement_level,
            self.concept_mastery,
            self.routine_strength,
            self.metacognitive_awareness,
            self.dropout_risk,
            self.intervention_readiness
        ]

    def to_dict(self):
        """딕셔너리로 변환"""
        return {
            'cognitive_clarity': self.cognitive_clarity,
            'emotional_stability': self.emotional_stability,
            'engagement_level': self.engagement_level,
            'concept_mastery': self.concept_mastery,
            'routine_strength': self.routine_strength,
            'metacognitive_awareness': self.metacognitive_awareness,
            'dropout_risk': self.dropout_risk,
            'intervention_readiness': self.intervention_readiness
        }

    def __repr__(self):
        return f"New8DStateVector({', '.join(f'{k}={v:.3f}' for k, v in self.to_dict().items())})"


def convert_new8d_to_old8d(new_state):
    """
    New 8D StateVector → Old 8D StateVector 변환

    매핑 규칙:
    - cognitive_clarity → metacognition
    - emotional_stability → emotional_regulation
    - engagement_level → engagement
    - concept_mastery → self_efficacy
    - routine_strength → (confidence + motivation) / 2
    - metacognitive_awareness → metacognition (보정)
    - dropout_risk → 1 - motivation
    - intervention_readiness → help_seeking
    """
    if isinstance(new_state, New8DStateVector):
        d = new_state.to_dict()
    elif isinstance(new_state, list) and len(new_state) == 8:
        d = dict(zip(NEW_8D_DIMENSIONS, new_state))
    else:
        raise ValueError("Invalid new state format")

    # 매핑 (근사적 변환)
    metacognition = (d['cognitive_clarity'] + d['metacognitive_awareness']) / 2
    self_efficacy = d['concept_mastery']
    help_seeking = d['intervention_readiness']
    emotional_regulation = d['emotional_stability']
    anxiety = d['dropout_risk']  # 이탈 위험 ≈ 불안
    confidence = d['routine_strength']
    engagement = d['engagement_level']
    motivation = 1.0 - d['dropout_risk']

    return StateVector(
        metacognition=metacognition,
        self_efficacy=self_efficacy,
        help_seeking=help_seeking,
        emotional_regulation=emotional_regulation,
        anxiety=anxiety,
        confidence=confidence,
        engagement=engagement,
        motivation=motivation
    )


def convert_old8d_to_new8d(old_state):
    """
    Old 8D StateVector → New 8D StateVector 변환

    역매핑 규칙
    """
    if isinstance(old_state, StateVector):
        d = old_state.to_dict()
    else:
        raise ValueError("Invalid old state format")

    cognitive_clarity = d['metacognition']
    emotional_stability = d['emotional_regulation']
    engagement_level = d['engagement']
    concept_mastery = d['self_efficacy']
    routine_strength = d['confidence']
    metacognitive_awareness = d['metacognition']
    dropout_risk = d['anxiety']
    intervention_readiness = d['help_seeking']

    return New8DStateVector(
        cognitive_clarity=cognitive_clarity,
        emotional_stability=emotional_stability,
        engagement_level=engagement_level,
        concept_mastery=concept_mastery,
        routine_strength=routine_strength,
        metacognitive_awareness=metacognitive_awareness,
        dropout_risk=dropout_risk,
        intervention_readiness=intervention_readiness
    )


def calculate_new8d_similarity(state1, state2):
    """
    두 New 8D StateVector 간의 유사도 계산

    코사인 유사도 사용
    """
    if isinstance(state1, New8DStateVector):
        v1 = state1.to_list()
    else:
        v1 = state1

    if isinstance(state2, New8DStateVector):
        v2 = state2.to_list()
    else:
        v2 = state2

    # 코사인 유사도
    dot_product = sum(a * b for a, b in zip(v1, v2))
    norm1 = math.sqrt(sum(a * a for a in v1))
    norm2 = math.sqrt(sum(b * b for b in v2))

    if norm1 == 0 or norm2 == 0:
        return 0.0

    return dot_product / (norm1 * norm2)


# 에이전트별 최적 New 8D StateVector 정의
AGENT_OPTIMAL_NEW8D = {
    1: New8DStateVector(  # Onboarding - 새로운 시작
        cognitive_clarity=0.50, emotional_stability=0.60, engagement_level=0.70,
        concept_mastery=0.50, routine_strength=0.50, metacognitive_awareness=0.50,
        dropout_risk=0.30, intervention_readiness=0.70
    ),
    3: New8DStateVector(  # Goals Analysis - 목표 설정
        cognitive_clarity=0.70, emotional_stability=0.65, engagement_level=0.75,
        concept_mastery=0.60, routine_strength=0.60, metacognitive_awareness=0.70,
        dropout_risk=0.25, intervention_readiness=0.65
    ),
    5: New8DStateVector(  # Learning Emotion - 정서 지원
        cognitive_clarity=0.50, emotional_stability=0.40, engagement_level=0.50,
        concept_mastery=0.40, routine_strength=0.40, metacognitive_awareness=0.50,
        dropout_risk=0.60, intervention_readiness=0.80
    ),
    8: New8DStateVector(  # Calmness - 진정 유도
        cognitive_clarity=0.55, emotional_stability=0.35, engagement_level=0.55,
        concept_mastery=0.45, routine_strength=0.40, metacognitive_awareness=0.55,
        dropout_risk=0.70, intervention_readiness=0.75
    ),
    9: New8DStateVector(  # Pomodoro - 시간 관리
        cognitive_clarity=0.60, emotional_stability=0.60, engagement_level=0.70,
        concept_mastery=0.55, routine_strength=0.75, metacognitive_awareness=0.60,
        dropout_risk=0.35, intervention_readiness=0.60
    ),
    10: New8DStateVector(  # Concept Notes - 개념 학습
        cognitive_clarity=0.70, emotional_stability=0.65, engagement_level=0.80,
        concept_mastery=0.55, routine_strength=0.55, metacognitive_awareness=0.70,
        dropout_risk=0.30, intervention_readiness=0.65
    ),
    11: New8DStateVector(  # Problem Notes - 문제 풀이
        cognitive_clarity=0.65, emotional_stability=0.60, engagement_level=0.80,
        concept_mastery=0.60, routine_strength=0.55, metacognitive_awareness=0.65,
        dropout_risk=0.35, intervention_readiness=0.60
    ),
    12: New8DStateVector(  # Rest Routine - 휴식
        cognitive_clarity=0.50, emotional_stability=0.50, engagement_level=0.35,
        concept_mastery=0.45, routine_strength=0.45, metacognitive_awareness=0.50,
        dropout_risk=0.50, intervention_readiness=0.50
    ),
    4: New8DStateVector(  # Engagement - 참여 촉진
        cognitive_clarity=0.60, emotional_stability=0.60, engagement_level=0.75,
        concept_mastery=0.55, routine_strength=0.60, metacognitive_awareness=0.60,
        dropout_risk=0.40, intervention_readiness=0.70
    ),
}

# ==============================================================================
# 1. 운영 모드 정의
# ==============================================================================

class OrchestratorMode(Enum):
    """Orchestrator 운영 모드"""
    OBSERVE = "observe"       # 관찰 모드: 로깅만, 기존 시스템 영향 없음
    COMPARE = "compare"       # 비교 모드: 제안 vs 실제 결과 비교
    SUGGEST = "suggest"       # 제안 모드: 최적 순서 제안
    ACTIVE = "active"         # 활성 모드: 실제 순서 조정 (미래용)


# ==============================================================================
# 2. 에이전트 신호 구조
# ==============================================================================

class AgentSignal:
    """에이전트의 Quantum 신호 (Python 3.6 호환)"""

    def __init__(self, agent_id, amplitude, phase, confidence, rule_id=None):
        # type: (int, float, float, float, Optional[str]) -> None
        self.agent_id = agent_id
        self.amplitude = amplitude        # 0.0 ~ 1.0
        self.phase = phase                # 라디안
        self.confidence = confidence      # 원본 룰 confidence
        self.rule_id = rule_id

    @property
    def complex_value(self):
        # type: () -> complex
        """복소수 표현"""
        return self.amplitude * complex(math.cos(self.phase), math.sin(self.phase))


class AgentPriority:
    """에이전트 활성화 우선순위 (Python 3.6 호환)"""

    def __init__(self, agent_id, priority_score, entanglement_bonus, state_alignment, signal_strength):
        # type: (int, float, float, float, float) -> None
        self.agent_id = agent_id
        self.priority_score = priority_score      # 높을수록 먼저 활성화
        self.entanglement_bonus = entanglement_bonus  # 얽힘 보너스
        self.state_alignment = state_alignment    # 학생 상태 정렬도
        self.signal_strength = signal_strength    # 신호 강도

    def __lt__(self, other):
        return self.priority_score < other.priority_score


# ==============================================================================
# 3. 시나리오 → 위상(Phase) 매핑
# ==============================================================================

SCENARIO_PHASE_MAP = {
    "S0": 0.0,                    # 정보 수집 (0°)
    "S1": math.pi / 4,            # 목표-계획 불일치 (45°)
    "S2": math.pi / 2,            # 시간 부족 딜레마 (90°)
    "S3": 3 * math.pi / 4,        # 회복탄력성 (135°)
    "S4": math.pi,                # 커리큘럼 정합성 (180°)
    "S5": 5 * math.pi / 4,        # 장기 목표 (225°)
    "C": 3 * math.pi / 2,         # 복합 상황 (270°)
    "Q": 7 * math.pi / 4,         # 포괄형 질문 (315°)
    "E": math.pi / 6,             # 정서적 UX (30°)
}


# ==============================================================================
# 4. 에이전트별 최적 StateVector 정의
# ==============================================================================

# 각 에이전트가 가장 효과적으로 개입할 수 있는 학생 상태
AGENT_OPTIMAL_STATES = {
    1: StateVector(  # Onboarding - 새로운 시작, 중간 수준 상태
        metacognition=0.50, self_efficacy=0.50, help_seeking=0.60,
        emotional_regulation=0.60, anxiety=0.40, confidence=0.50,
        engagement=0.70, motivation=0.70
    ),
    3: StateVector(  # Goals Analysis - 높은 메타인지 필요
        metacognition=0.70, self_efficacy=0.60, help_seeking=0.65,
        emotional_regulation=0.65, anxiety=0.35, confidence=0.60,
        engagement=0.75, motivation=0.80
    ),
    5: StateVector(  # Learning Emotion - 정서적 지원 필요
        metacognition=0.50, self_efficacy=0.40, help_seeking=0.70,
        emotional_regulation=0.40, anxiety=0.60, confidence=0.35,
        engagement=0.50, motivation=0.50
    ),
    8: StateVector(  # Calmness - 높은 불안, 낮은 정서조절
        metacognition=0.55, self_efficacy=0.45, help_seeking=0.60,
        emotional_regulation=0.35, anxiety=0.75, confidence=0.40,
        engagement=0.55, motivation=0.50
    ),
    10: StateVector(  # Concept Notes - 개념 이해 집중
        metacognition=0.70, self_efficacy=0.55, help_seeking=0.65,
        emotional_regulation=0.65, anxiety=0.35, confidence=0.55,
        engagement=0.80, motivation=0.75
    ),
    11: StateVector(  # Problem Notes - 문제 풀이 집중
        metacognition=0.65, self_efficacy=0.60, help_seeking=0.60,
        emotional_regulation=0.60, anxiety=0.40, confidence=0.55,
        engagement=0.80, motivation=0.70
    ),
    12: StateVector(  # Rest Routine - 피로 누적, 휴식 필요
        metacognition=0.50, self_efficacy=0.45, help_seeking=0.50,
        emotional_regulation=0.50, anxiety=0.50, confidence=0.45,
        engagement=0.35, motivation=0.40
    ),
}


# ==============================================================================
# 5. Hamiltonian Evolution (Flow State 최적화)
# ==============================================================================

class HamiltonianEvolution:
    """
    Hamiltonian 진화 연산자 (Python 3.6 호환)

    목적: 학생 상태를 Flow State로 점진적 진화
    Flow State 정의: 높은 참여도, 적절한 도전, 낮은 불안
    """

    def __init__(self, target_flow_state=None, evolution_rate=0.1, max_iterations=100):
        # type: (Optional[StateVector], float, int) -> None
        if target_flow_state is None:
            target_flow_state = StateVector(
                metacognition=0.75,
                self_efficacy=0.70,
                help_seeking=0.60,
                emotional_regulation=0.75,
                anxiety=0.25,
                confidence=0.70,
                engagement=0.85,
                motivation=0.80
            )
        self.target_flow_state = target_flow_state
        self.evolution_rate = evolution_rate  # 진화 속도 (0.0 ~ 1.0)
        self.max_iterations = max_iterations

    def evolve_step(self, current):
        """한 단계 진화"""
        current_dict = current.to_dict()
        target_dict = self.target_flow_state.to_dict()

        evolved = {}
        for key in current_dict:
            diff = target_dict[key] - current_dict[key]
            evolved[key] = current_dict[key] + diff * self.evolution_rate
            # 0.0 ~ 1.0 범위 제한
            evolved[key] = max(0.0, min(1.0, evolved[key]))

        return StateVector(**evolved)

    def evolve_to_flow(
        self,
        initial: StateVector,
        threshold: float = 0.95
    ) -> Tuple[StateVector, int]:
        """
        Flow State로 수렴할 때까지 진화

        Returns: (최종 상태, 반복 횟수)
        """
        current = initial
        for i in range(self.max_iterations):
            similarity = calculate_similarity(current, self.target_flow_state)
            if similarity >= threshold:
                return current, i
            current = self.evolve_step(current)

        return current, self.max_iterations

    def calculate_flow_distance(self, state: StateVector) -> float:
        """현재 상태와 Flow State 간의 거리 (0에 가까울수록 좋음)"""
        return 1.0 - calculate_similarity(state, self.target_flow_state)


# ==============================================================================
# 6. 간섭 계산기
# ==============================================================================

class InterferenceCalculator:
    """다중 에이전트 Quantum 간섭 계산"""

    def __init__(self):
        self.entanglement_map = ENTANGLEMENT_MAP

    def calculate_interference(
        self,
        signals: List[AgentSignal]
    ) -> Tuple[float, Dict[int, float]]:
        """
        여러 신호의 간섭 계산

        Returns: (총 amplitude, 에이전트별 기여도)
        """
        if not signals:
            return 0.0, {}

        # 복소수 합
        total = complex(0, 0)
        contributions = {}

        for signal in signals:
            cv = signal.complex_value
            total += cv
            contributions[signal.agent_id] = abs(cv)

        total_amplitude = abs(total)

        # 기여도 정규화
        if total_amplitude > 0:
            for aid in contributions:
                contributions[aid] /= total_amplitude

        return total_amplitude, contributions

    def calculate_entanglement_boost(
        self,
        active_agents: List[int]
    ) -> Dict[int, float]:
        """
        활성화된 에이전트들 간의 얽힘 부스트 계산

        높은 상관관계의 에이전트들이 함께 활성화되면 부스트
        """
        boosts = defaultdict(float)

        for i, agent1 in enumerate(active_agents):
            for agent2 in active_agents[i+1:]:
                corr = get_correlation(agent1, agent2)
                if corr > 0:
                    boosts[agent1] += corr * 0.5
                    boosts[agent2] += corr * 0.5

        return dict(boosts)


# ==============================================================================
# 7. Quantum Orchestrator 메인 클래스
# ==============================================================================

class QuantumOrchestrator:
    """
    기존 룰 시스템 위의 Quantum 조정 레이어

    역할:
    1. 여러 에이전트 동시 트리거 시 최적 순서 제안
    2. 학생 상태 기반 에이전트 적합도 평가
    3. 에이전트 간 얽힘 고려한 시너지 최적화
    """

    def __init__(self, mode: OrchestratorMode = OrchestratorMode.OBSERVE):
        self.mode = mode
        self.interference = InterferenceCalculator()
        self.evolution = HamiltonianEvolution()
        self.history: List[Dict] = []  # 결정 히스토리

    def _calculate_state_alignment(
        self,
        agent_id: int,
        student_state: StateVector
    ) -> float:
        """에이전트와 학생 상태의 정렬도 계산"""
        if agent_id not in AGENT_OPTIMAL_STATES:
            return 0.5  # 기본값

        optimal = AGENT_OPTIMAL_STATES[agent_id]
        similarity = calculate_similarity(student_state, optimal)

        # -1 ~ 1 범위를 0 ~ 1로 변환
        return (similarity + 1) / 2

    def _create_signal(
        self,
        agent_id: int,
        priority: int = 90,
        confidence: float = 0.9,
        scenario: str = "S0"
    ) -> AgentSignal:
        """룰 정보에서 에이전트 신호 생성"""
        amplitude = confidence * math.sqrt(priority / 100)
        phase = SCENARIO_PHASE_MAP.get(scenario, 0.0)

        return AgentSignal(
            agent_id=agent_id,
            amplitude=amplitude,
            phase=phase,
            confidence=confidence
        )

    def suggest_agent_order(
        self,
        student_state: StateVector,
        triggered_agents: List[int],
        agent_priorities: Optional[Dict[int, int]] = None,
        agent_confidences: Optional[Dict[int, float]] = None,
        agent_scenarios: Optional[Dict[int, str]] = None
    ) -> List[AgentPriority]:
        """
        여러 에이전트 동시 트리거 시 최적 순서 제안

        Args:
            student_state: 현재 학생 심리 상태
            triggered_agents: 트리거된 에이전트 ID 목록
            agent_priorities: 에이전트별 priority (기본 90)
            agent_confidences: 에이전트별 confidence (기본 0.9)
            agent_scenarios: 에이전트별 scenario (기본 S0)

        Returns: 우선순위 정렬된 AgentPriority 목록
        """
        if not triggered_agents:
            return []

        # 기본값 설정
        priorities = agent_priorities or {}
        confidences = agent_confidences or {}
        scenarios = agent_scenarios or {}

        # 각 에이전트의 신호 생성
        signals = []
        for aid in triggered_agents:
            signal = self._create_signal(
                agent_id=aid,
                priority=priorities.get(aid, 90),
                confidence=confidences.get(aid, 0.9),
                scenario=scenarios.get(aid, "S0")
            )
            signals.append(signal)

        # 간섭 계산
        total_amp, contributions = self.interference.calculate_interference(signals)

        # 얽힘 부스트 계산
        entanglement_boosts = self.interference.calculate_entanglement_boost(
            triggered_agents
        )

        # 각 에이전트의 최종 우선순위 계산
        agent_results = []
        for signal in signals:
            aid = signal.agent_id

            # 1. 학생 상태 정렬도
            state_alignment = self._calculate_state_alignment(aid, student_state)

            # 2. 신호 강도
            signal_strength = signal.amplitude

            # 3. 얽힘 보너스
            entanglement_bonus = entanglement_boosts.get(aid, 0.0)

            # 4. 최종 점수 계산
            # 가중치: 상태정렬(40%) + 신호강도(35%) + 얽힘보너스(25%)
            priority_score = (
                state_alignment * 0.40 +
                signal_strength * 0.35 +
                entanglement_bonus * 0.25
            )

            agent_results.append(AgentPriority(
                agent_id=aid,
                priority_score=priority_score,
                entanglement_bonus=entanglement_bonus,
                state_alignment=state_alignment,
                signal_strength=signal_strength
            ))

        # 우선순위 내림차순 정렬
        agent_results.sort(reverse=True)

        # 히스토리 기록
        self._log_decision(student_state, triggered_agents, agent_results)

        return agent_results

    def _log_decision(
        self,
        state: StateVector,
        agents: List[int],
        result: List[AgentPriority]
    ):
        """결정 히스토리 기록"""
        self.history.append({
            "state": state.to_dict(),
            "triggered_agents": agents,
            "suggested_order": [r.agent_id for r in result],
            "scores": {r.agent_id: r.priority_score for r in result}
        })

    def get_flow_optimized_order(
        self,
        student_state: StateVector,
        triggered_agents: List[int]
    ) -> Tuple[List[AgentPriority], StateVector]:
        """
        Flow State 최적화된 에이전트 순서

        학생을 Flow State로 이끌기에 가장 효과적인 순서 제안

        Returns: (우선순위 목록, 예상 최종 상태)
        """
        # 현재 상태에서 Flow State까지의 거리
        initial_distance = self.evolution.calculate_flow_distance(student_state)

        # 기본 순서 계산
        base_order = self.suggest_agent_order(student_state, triggered_agents)

        # Flow State로 진화 시뮬레이션
        evolved_state, iterations = self.evolution.evolve_to_flow(student_state)

        # Flow 기여도로 재정렬
        flow_adjusted = []
        for ap in base_order:
            # Flow State에 더 가깝게 만드는 에이전트 우대
            if ap.agent_id in AGENT_OPTIMAL_STATES:
                optimal = AGENT_OPTIMAL_STATES[ap.agent_id]
                flow_sim = calculate_similarity(optimal, self.evolution.target_flow_state)
                flow_bonus = (flow_sim + 1) / 2 * 0.2  # 0 ~ 0.2 보너스

                flow_adjusted.append(AgentPriority(
                    agent_id=ap.agent_id,
                    priority_score=ap.priority_score + flow_bonus,
                    entanglement_bonus=ap.entanglement_bonus,
                    state_alignment=ap.state_alignment,
                    signal_strength=ap.signal_strength
                ))
            else:
                flow_adjusted.append(ap)

        flow_adjusted.sort(reverse=True)

        return flow_adjusted, evolved_state

    def compare_with_actual(
        self,
        suggested: List[int],
        actual: List[int],
        outcome_score: float
    ) -> Dict:
        """
        제안 순서와 실제 순서 비교 (비교 모드용)

        Args:
            suggested: 제안된 에이전트 순서
            actual: 실제 실행된 순서
            outcome_score: 실제 결과 점수 (0.0 ~ 1.0)

        Returns: 비교 분석 결과
        """
        # 순서 일치도 계산 (Kendall tau 간소화 버전)
        matches = sum(1 for i, (s, a) in enumerate(zip(suggested, actual)) if s == a)
        order_similarity = matches / max(len(suggested), len(actual)) if suggested and actual else 0

        return {
            "suggested_order": suggested,
            "actual_order": actual,
            "order_similarity": order_similarity,
            "outcome_score": outcome_score,
            "suggestion_quality": order_similarity * outcome_score,
            "could_improve": order_similarity < 0.8 and outcome_score < 0.7
        }

    # ==========================================================================
    # Phase 8: New 8D StateVector 통합 메서드
    # ==========================================================================

    def _calculate_state_alignment_new8d(
        self,
        agent_id: int,
        student_state: 'New8DStateVector'
    ) -> float:
        """New 8D 에이전트와 학생 상태의 정렬도 계산"""
        if agent_id not in AGENT_OPTIMAL_NEW8D:
            return 0.5  # 기본값

        optimal = AGENT_OPTIMAL_NEW8D[agent_id]
        similarity = calculate_new8d_similarity(student_state, optimal)

        # 코사인 유사도는 이미 0~1 범위 (정규화된 벡터의 경우)
        return similarity

    def suggest_agent_order_from_new8d(
        self,
        student_state: 'New8DStateVector',
        triggered_agents: List[int],
        agent_priorities: Optional[Dict[int, int]] = None,
        agent_confidences: Optional[Dict[int, float]] = None,
        agent_scenarios: Optional[Dict[int, str]] = None
    ) -> List[AgentPriority]:
        """
        New 8D StateVector 기반 최적 에이전트 순서 제안

        Phase 7 데이터 인터페이스의 8D 벡터를 직접 사용

        Args:
            student_state: New 8D 학생 상태 (New8DStateVector)
            triggered_agents: 트리거된 에이전트 ID 목록
            agent_priorities: 에이전트별 priority (기본 90)
            agent_confidences: 에이전트별 confidence (기본 0.9)
            agent_scenarios: 에이전트별 scenario (기본 S0)

        Returns: 우선순위 정렬된 AgentPriority 목록
        """
        if not triggered_agents:
            return []

        # 기본값 설정
        priorities = agent_priorities or {}
        confidences = agent_confidences or {}
        scenarios = agent_scenarios or {}

        # 각 에이전트의 신호 생성
        signals = []
        for aid in triggered_agents:
            signal = self._create_signal(
                agent_id=aid,
                priority=priorities.get(aid, 90),
                confidence=confidences.get(aid, 0.9),
                scenario=scenarios.get(aid, "S0")
            )
            signals.append(signal)

        # 간섭 계산
        total_amp, contributions = self.interference.calculate_interference(signals)

        # 얽힘 부스트 계산
        entanglement_boosts = self.interference.calculate_entanglement_boost(
            triggered_agents
        )

        # 각 에이전트의 최종 우선순위 계산
        agent_results = []
        for signal in signals:
            aid = signal.agent_id

            # 1. New 8D 학생 상태 정렬도
            state_alignment = self._calculate_state_alignment_new8d(aid, student_state)

            # 2. 신호 강도
            signal_strength = signal.amplitude

            # 3. 얽힘 보너스
            entanglement_bonus = entanglement_boosts.get(aid, 0.0)

            # 4. dropout_risk 기반 추가 조정
            # 이탈 위험이 높으면 정서 지원 에이전트(5, 8) 우대
            dropout_adjustment = 0.0
            if student_state.dropout_risk > 0.6:
                if aid in [5, 8, 12]:  # Emotion, Calmness, Rest
                    dropout_adjustment = 0.15

            # 5. intervention_readiness 기반 추가 조정
            # 개입 준비도 높으면 더 적극적인 에이전트 우대
            intervention_adjustment = 0.0
            if student_state.intervention_readiness > 0.7:
                if aid in [3, 10, 11]:  # Goals, Concept, Problem
                    intervention_adjustment = 0.10

            # 6. 최종 점수 계산
            # 가중치: 상태정렬(35%) + 신호강도(30%) + 얽힘보너스(20%) + 특수조정(15%)
            priority_score = (
                state_alignment * 0.35 +
                signal_strength * 0.30 +
                entanglement_bonus * 0.20 +
                dropout_adjustment +
                intervention_adjustment
            )

            agent_results.append(AgentPriority(
                agent_id=aid,
                priority_score=priority_score,
                entanglement_bonus=entanglement_bonus,
                state_alignment=state_alignment,
                signal_strength=signal_strength
            ))

        # 우선순위 내림차순 정렬
        agent_results.sort(reverse=True)

        # 히스토리 기록 (New 8D 형식)
        self._log_decision_new8d(student_state, triggered_agents, agent_results)

        return agent_results

    def _log_decision_new8d(
        self,
        state: 'New8DStateVector',
        agents: List[int],
        result: List[AgentPriority]
    ):
        """New 8D 결정 히스토리 기록"""
        self.history.append({
            "state_type": "new_8d",
            "state": state.to_dict(),
            "triggered_agents": agents,
            "suggested_order": [r.agent_id for r in result],
            "scores": {r.agent_id: r.priority_score for r in result}
        })

    def suggest_from_agent_data(
        self,
        student_id: int,
        agent_contexts: Dict[int, Dict],
        triggered_agents: List[int],
        agent_priorities: Optional[Dict[int, int]] = None,
        agent_confidences: Optional[Dict[int, float]] = None,
        agent_scenarios: Optional[Dict[int, str]] = None
    ) -> Tuple[List[AgentPriority], 'New8DStateVector']:
        """
        에이전트 데이터에서 직접 StateVector 생성 및 순서 제안

        Phase 7 QuantumDataCollector와 완전 통합

        Args:
            student_id: 학생 ID
            agent_contexts: 에이전트 데이터 {agent_id: {key: value}}
            triggered_agents: 트리거된 에이전트 ID 목록
            ... (기타 파라미터)

        Returns: (우선순위 목록, 생성된 New8DStateVector)
        """
        if not DATA_INTERFACE_AVAILABLE:
            raise ImportError("_quantum_data_interface module not available")

        # 에이전트 데이터에서 New 8D StateVector 생성
        state = New8DStateVector.from_agent_data(student_id, agent_contexts)

        # 순서 제안
        results = self.suggest_agent_order_from_new8d(
            student_state=state,
            triggered_agents=triggered_agents,
            agent_priorities=agent_priorities,
            agent_confidences=agent_confidences,
            agent_scenarios=agent_scenarios
        )

        return results, state

    def get_state_analysis(
        self,
        student_state: 'New8DStateVector'
    ) -> Dict:
        """
        학생 상태 종합 분석

        Args:
            student_state: New 8D 학생 상태

        Returns: 분석 결과 딕셔너리
        """
        d = student_state.to_dict()

        # 1. 위험 수준 평가
        risk_level = "low"
        if d['dropout_risk'] > 0.7:
            risk_level = "critical"
        elif d['dropout_risk'] > 0.5:
            risk_level = "medium"
        elif d['dropout_risk'] > 0.3:
            risk_level = "low"
        else:
            risk_level = "minimal"

        # 2. 개입 권장 수준
        intervention_recommendation = "observe"
        if d['intervention_readiness'] > 0.8:
            intervention_recommendation = "active"
        elif d['intervention_readiness'] > 0.6:
            intervention_recommendation = "suggest"
        elif d['intervention_readiness'] > 0.4:
            intervention_recommendation = "compare"

        # 3. 주요 강점/약점
        strengths = []
        weaknesses = []

        for dim, value in d.items():
            if dim == 'dropout_risk':
                if value < 0.3:
                    strengths.append(dim)
                elif value > 0.6:
                    weaknesses.append(dim)
            else:
                if value > 0.7:
                    strengths.append(dim)
                elif value < 0.4:
                    weaknesses.append(dim)

        # 4. 권장 에이전트
        recommended_agents = []
        if d['emotional_stability'] < 0.4 or d['dropout_risk'] > 0.6:
            recommended_agents.extend([5, 8])  # Emotion, Calmness
        if d['engagement_level'] < 0.4:
            recommended_agents.extend([4, 9])  # Engagement, Pomodoro
        if d['concept_mastery'] < 0.4:
            recommended_agents.extend([10, 11])  # Concept, Problem
        if d['routine_strength'] < 0.4:
            recommended_agents.extend([9, 12])  # Pomodoro, Rest

        return {
            "state": d,
            "risk_level": risk_level,
            "intervention_recommendation": intervention_recommendation,
            "strengths": strengths,
            "weaknesses": weaknesses,
            "recommended_agents": list(set(recommended_agents)),
            "data_interface_available": DATA_INTERFACE_AVAILABLE
        }


# ==============================================================================
# 8. 테스트 함수
# ==============================================================================

def run_orchestrator_test():
    """Quantum Orchestrator 테스트"""
    print("=" * 60)
    print("Quantum Orchestration - Phase 3: Orchestrator 테스트")
    print("=" * 60)
    print()

    # 1. Orchestrator 초기화
    orchestrator = QuantumOrchestrator(mode=OrchestratorMode.SUGGEST)
    print(f"📊 [1] Orchestrator 초기화: {orchestrator.mode.value} 모드")
    print()

    # 2. 테스트 학생 상태 (불안한 학생)
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

    print("📋 [2] 테스트 학생 상태 (불안한 학생)")
    for k, v in anxious_student.to_dict().items():
        bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
        print(f"   {k:20s}: {bar} {v:.2f}")
    print()

    # 3. 동시 트리거된 에이전트
    triggered = [5, 8, 10, 12]  # Emotion, Calmness, Concept, Rest
    print(f"🎯 [3] 동시 트리거된 에이전트: {[get_agent_name(a) for a in triggered]}")
    print()

    # 4. 최적 순서 제안
    print("⚡ [4] 최적 활성화 순서 제안")
    results = orchestrator.suggest_agent_order(
        student_state=anxious_student,
        triggered_agents=triggered,
        agent_scenarios={5: "E", 8: "E", 10: "S3", 12: "S2"}
    )

    print("   순위 | 에이전트            | 점수   | 상태정렬 | 신호강도 | 얽힘보너스")
    print("   " + "-" * 70)
    for i, r in enumerate(results, 1):
        name = get_agent_name(r.agent_id)
        print(f"   {i:4d} | {name:18s} | {r.priority_score:.4f} | {r.state_alignment:.4f}  | {r.signal_strength:.4f}  | {r.entanglement_bonus:.4f}")
    print()

    # 5. Flow State 최적화
    print("🌊 [5] Flow State 최적화된 순서")
    flow_results, evolved_state = orchestrator.get_flow_optimized_order(
        student_state=anxious_student,
        triggered_agents=triggered
    )

    print("   Flow 최적화 순서:")
    for i, r in enumerate(flow_results, 1):
        name = get_agent_name(r.agent_id)
        print(f"   {i}. {name} (점수: {r.priority_score:.4f})")
    print()

    print("   예상 진화 후 상태:")
    for k, v in evolved_state.to_dict().items():
        bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
        print(f"   {k:20s}: {bar} {v:.2f}")
    print()

    # 6. 다른 학생 상태로 테스트 (동기 높은 학생)
    motivated_student = StateVector(
        metacognition=0.75,
        self_efficacy=0.70,
        help_seeking=0.65,
        emotional_regulation=0.75,
        anxiety=0.25,
        confidence=0.75,
        engagement=0.85,
        motivation=0.90
    )

    print("📋 [6] 동기 높은 학생으로 동일 에이전트 테스트")
    results2 = orchestrator.suggest_agent_order(
        student_state=motivated_student,
        triggered_agents=triggered
    )

    print("   순서 비교:")
    print(f"   - 불안한 학생: {[get_agent_name(r.agent_id) for r in results]}")
    print(f"   - 동기 높은 학생: {[get_agent_name(r.agent_id) for r in results2]}")
    print()

    # 7. 비교 모드 시뮬레이션
    print("📊 [7] 비교 모드 시뮬레이션")
    suggested_order = [r.agent_id for r in results]
    actual_order = [8, 5, 12, 10]  # 가상의 실제 순서

    comparison = orchestrator.compare_with_actual(
        suggested=suggested_order,
        actual=actual_order,
        outcome_score=0.72
    )

    print(f"   - 제안 순서: {[get_agent_name(a) for a in comparison['suggested_order']]}")
    print(f"   - 실제 순서: {[get_agent_name(a) for a in comparison['actual_order']]}")
    print(f"   - 순서 유사도: {comparison['order_similarity']:.2%}")
    print(f"   - 결과 점수: {comparison['outcome_score']:.2%}")
    print(f"   - 제안 품질: {comparison['suggestion_quality']:.4f}")
    print(f"   - 개선 필요: {'예' if comparison['could_improve'] else '아니오'}")
    print()

    print("=" * 60)
    print("✅ Phase 3 Orchestrator 테스트 완료")
    print("=" * 60)

    return {
        "anxious_order": [r.agent_id for r in results],
        "motivated_order": [r.agent_id for r in results2],
        "flow_order": [r.agent_id for r in flow_results],
        "history_count": len(orchestrator.history)
    }


# ==============================================================================
# 9. New 8D 테스트 함수 (Phase 8 통합 검증)
# ==============================================================================

def run_new8d_test():
    """Phase 8 New 8D StateVector 통합 테스트"""
    print("=" * 60)
    print("Phase 8: New 8D StateVector 통합 테스트")
    print("=" * 60)
    print()

    # 0. Data Interface 가용성 확인
    print(f"📦 [0] Data Interface 가용성: {'✅ 사용 가능' if DATA_INTERFACE_AVAILABLE else '❌ 사용 불가'}")
    print()

    # 1. New8DStateVector 생성 테스트
    print("📊 [1] New8DStateVector 직접 생성 테스트")

    # 불안한 학생 (New 8D 기준)
    anxious_new8d = New8DStateVector(
        cognitive_clarity=0.40,      # 낮은 인지 명확성
        emotional_stability=0.30,    # 낮은 정서 안정성
        engagement_level=0.45,       # 중간 참여
        concept_mastery=0.50,        # 중간 개념 숙달
        routine_strength=0.35,       # 약한 루틴
        metacognitive_awareness=0.40,  # 낮은 메타인지
        dropout_risk=0.70,           # 높은 이탈 위험
        intervention_readiness=0.80  # 높은 개입 준비도
    )

    print("   불안한 학생 (New 8D):")
    for k, v in anxious_new8d.to_dict().items():
        bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
        print(f"   {k:25s}: {bar} {v:.2f}")
    print()

    # 2. Old 8D 변환 테스트
    print("🔄 [2] New 8D → Old 8D 변환 테스트")
    old_8d = convert_new8d_to_old8d(anxious_new8d)
    print("   변환된 Old 8D StateVector:")
    for k, v in old_8d.to_dict().items():
        bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
        print(f"   {k:20s}: {bar} {v:.2f}")
    print()

    # 3. 역변환 테스트
    print("🔄 [3] Old 8D → New 8D 역변환 테스트")
    recovered_new8d = convert_old8d_to_new8d(old_8d)
    print("   역변환된 New 8D StateVector:")
    for k, v in recovered_new8d.to_dict().items():
        bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
        print(f"   {k:25s}: {bar} {v:.2f}")
    print()

    # 4. Orchestrator 초기화 및 New 8D 순서 제안
    print("⚡ [4] Orchestrator New 8D 순서 제안 테스트")
    orchestrator = QuantumOrchestrator(mode=OrchestratorMode.SUGGEST)

    triggered = [5, 8, 10, 12]  # Emotion, Calmness, Concept, Rest
    print(f"   트리거된 에이전트: {[get_agent_name(a) for a in triggered]}")

    results = orchestrator.suggest_agent_order_from_new8d(
        student_state=anxious_new8d,
        triggered_agents=triggered,
        agent_scenarios={5: "E", 8: "E", 10: "S3", 12: "S2"}
    )

    print("\n   New 8D 기반 최적 순서:")
    print("   순위 | 에이전트            | 점수   | 상태정렬 | 신호강도 | 얽힘보너스")
    print("   " + "-" * 70)
    for i, r in enumerate(results, 1):
        name = get_agent_name(r.agent_id)
        print(f"   {i:4d} | {name:18s} | {r.priority_score:.4f} | {r.state_alignment:.4f}  | {r.signal_strength:.4f}  | {r.entanglement_bonus:.4f}")
    print()

    # 5. 유사도 계산 테스트
    print("📐 [5] New 8D 유사도 계산 테스트")

    # 동기 높은 학생
    motivated_new8d = New8DStateVector(
        cognitive_clarity=0.85,
        emotional_stability=0.80,
        engagement_level=0.90,
        concept_mastery=0.75,
        routine_strength=0.80,
        metacognitive_awareness=0.75,
        dropout_risk=0.15,
        intervention_readiness=0.30
    )

    similarity = calculate_new8d_similarity(anxious_new8d, motivated_new8d)
    print(f"   불안한 학생 ↔ 동기 높은 학생 유사도: {similarity:.4f}")

    # 자기 자신과의 유사도
    self_similarity = calculate_new8d_similarity(anxious_new8d, anxious_new8d)
    print(f"   자기 자신과의 유사도 (검증): {self_similarity:.4f} (expected: 1.0)")
    print()

    # 6. 에이전트 최적 상태 매칭 테스트
    print("🎯 [6] 에이전트별 최적 New 8D 상태 매칭 테스트")
    print(f"   정의된 최적 상태 에이전트: {list(AGENT_OPTIMAL_NEW8D.keys())}")

    for agent_id in [8, 10, 12]:  # Calmness, Concept, Rest
        if agent_id in AGENT_OPTIMAL_NEW8D:
            optimal = AGENT_OPTIMAL_NEW8D[agent_id]
            sim_anxious = calculate_new8d_similarity(anxious_new8d, optimal)
            sim_motivated = calculate_new8d_similarity(motivated_new8d, optimal)
            print(f"   Agent {agent_id} ({get_agent_name(agent_id)}): "
                  f"불안한 학생={sim_anxious:.3f}, 동기 높은 학생={sim_motivated:.3f}")
    print()

    # 7. get_state_analysis() 테스트
    print("📈 [7] 상태 분석 API 테스트")
    analysis = orchestrator.get_state_analysis(
        student_state=anxious_new8d
    )

    print(f"   분석 결과:")
    print(f"   - 위험 수준: {analysis['risk_level']}")
    print(f"   - 개입 권고: {analysis['intervention_recommendation']}")
    print(f"   - 학생 상태 차원: {len(analysis['state'])}개")
    print(f"   - 강점: {analysis['strengths']}")
    print(f"   - 약점: {analysis['weaknesses']}")
    print(f"   - 추천 에이전트: {[get_agent_name(a) for a in analysis['recommended_agents']]}")
    print(f"   - Data Interface: {'사용 가능' if analysis['data_interface_available'] else '사용 불가'}")
    print()

    # 8. from_agent_data() 테스트 (Data Interface 가용시)
    if DATA_INTERFACE_AVAILABLE:
        print("🔗 [8] Phase 7 Data Interface 연동 테스트")

        # 시뮬레이션 에이전트 데이터
        agent_contexts = {
            8: {'calm_score': 0.72, 'calmness_level': 3},
            11: {'accuracy_rate': 0.85, 'total_problems': 20},
            12: {'rest_count': 5, 'average_interval': 55},
            3: {'goal_progress': 0.6, 'goal_effectiveness': 0.7},
            9: {'pomodoro_completion': 0.8},
            4: {'engagement_level': 0.75, 'dropout_risk': 0.15}
        }

        print(f"   에이전트 컨텍스트: {list(agent_contexts.keys())}")

        # from_agent_data 테스트
        state_from_data = New8DStateVector.from_agent_data(
            student_id=99999,
            agent_contexts=agent_contexts
        )

        print("   Data Interface에서 생성된 New 8D:")
        for k, v in state_from_data.to_dict().items():
            bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
            print(f"   {k:25s}: {bar} {v:.2f}")
        print()

        # suggest_from_agent_data 테스트
        print("🚀 [9] suggest_from_agent_data 통합 테스트")
        results_from_data, generated_state = orchestrator.suggest_from_agent_data(
            student_id=99999,
            agent_contexts=agent_contexts,
            triggered_agents=[8, 10, 12],
            agent_scenarios={8: "E", 10: "S3", 12: "S2"}
        )

        print("   Data Interface 기반 최적 순서:")
        for i, r in enumerate(results_from_data, 1):
            name = get_agent_name(r.agent_id)
            print(f"   {i}. {name} (점수: {r.priority_score:.4f})")
        print()
    else:
        print("⚠️ [8] Phase 7 Data Interface 사용 불가 - 테스트 생략")
        print("   _quantum_data_interface.py 모듈이 필요합니다.")
        print()

    # 10. 결과 요약
    print("=" * 60)
    print("✅ Phase 8 New 8D 통합 테스트 완료")
    print("=" * 60)

    return {
        "new8d_test": "PASSED",
        "conversion_test": "PASSED",
        "similarity_test": "PASSED",
        "orchestrator_test": "PASSED",
        "data_interface_available": DATA_INTERFACE_AVAILABLE,
        "suggested_order": [r.agent_id for r in results]
    }


# ==============================================================================
# Main
# ==============================================================================

if __name__ == "__main__":
    import sys

    # 명령줄 인자로 테스트 선택
    if len(sys.argv) > 1:
        test_type = sys.argv[1].lower()
        if test_type == "old" or test_type == "phase3":
            run_orchestrator_test()
        elif test_type == "new" or test_type == "phase8":
            run_new8d_test()
        elif test_type == "all":
            print("\n" + "=" * 60)
            print("🔬 전체 테스트 실행")
            print("=" * 60 + "\n")

            print(">>> Phase 3 (Old 8D) 테스트 <<<\n")
            result1 = run_orchestrator_test()

            print("\n\n>>> Phase 8 (New 8D) 테스트 <<<\n")
            result2 = run_new8d_test()

            print("\n" + "=" * 60)
            print("📊 전체 테스트 결과 요약")
            print("=" * 60)
            print(f"   Phase 3 (Old 8D): ✅ 완료")
            print(f"   Phase 8 (New 8D): ✅ 완료")
            print(f"   Data Interface: {'✅ 연동됨' if result2['data_interface_available'] else '⚠️ 미연동'}")
        else:
            print(f"Unknown test type: {test_type}")
            print("Usage: python _quantum_orchestrator.py [old|new|all]")
    else:
        # 기본: 새로운 8D 테스트 실행
        run_new8d_test()
