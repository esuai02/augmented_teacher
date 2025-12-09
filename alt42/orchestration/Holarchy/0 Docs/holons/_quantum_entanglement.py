#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Quantum Orchestration - Phase 2: EntanglementMap
=================================================
에이전트 간 연동 룰(recommend_path)에서 상관계수 추출

목표: 기존 rules.yaml의 recommend_path 액션에서 에이전트 간 관계 추출
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
from collections import defaultdict

# Python 3.6 호환: dataclasses 대신 일반 클래스 사용

# ==============================================================================
# 1. 에이전트 이름 → ID 매핑
# ==============================================================================

AGENT_NAME_TO_ID = {
    # 코어 에이전트 (폴더명 기반)
    "onboarding_agent": 1,
    "exam_schedule_agent": 2,
    "goals_analysis_agent": 3,
    "inspect_weakpoints_agent": 4,
    "learning_emotion_agent": 5,
    "teacher_feedback_agent": 6,
    "interaction_targeting_agent": 7,
    "calmness_agent": 8,
    "learning_management_agent": 9,
    "concept_note_agent": 10,
    "problem_note_agent": 11,
    "rest_routine_agent": 12,
    "learning_dropout_agent": 13,
    "current_position_agent": 14,
    "problem_redefinition_agent": 15,
    "interaction_preparation_agent": 16,
    "remaining_activities_agent": 17,
    "signature_routine_agent": 18,
    "interaction_content_agent": 19,
    "intervention_preparation_agent": 20,
    "intervention_execution_agent": 21,

    # 별칭 (rules.yaml에서 사용되는 이름)
    "type_learning_agent": 4,       # inspect_weakpoints 유형 학습
    "error_note_agent": 11,         # problem_note의 일부 (오답노트)
    "qa_agent": 7,                  # interaction_targeting Q&A 기능
    "review_routine_agent": 12,     # rest_routine의 일부 (복습 루틴)
    "pomodoro_agent": 12,           # rest_routine의 일부 (집중/휴식)
    "home_check_agent": 6,          # teacher_feedback 가정 확인
    "fatigue_analysis_agent": 8,    # calmness 피로도 분석
    "advanced_learning": 4,         # inspect_weakpoints 심화 학습
    "curriculum_innovation_agent": 14,  # current_position 커리큘럼
}

AGENT_ID_TO_NAME = {
    1: "Onboarding",
    2: "Exam Schedule",
    3: "Goals Analysis",
    4: "Inspect Weakpoints",
    5: "Learning Emotion",
    6: "Teacher Feedback",
    7: "Interaction Targeting",
    8: "Calmness",
    9: "Learning Management",
    10: "Concept Notes",
    11: "Problem Notes",
    12: "Rest Routine",
    13: "Learning Dropout",
    14: "Current Position",
    15: "Problem Redefinition",
    16: "Interaction Preparation",
    17: "Remaining Activities",
    18: "Signature Routine",
    19: "Interaction Content",
    20: "Intervention Preparation",
    21: "Intervention Execution",
}

# ==============================================================================
# 2. 데이터 구조
# ==============================================================================

class AgentConnection:
    """에이전트 간 연결 정보 (Python 3.6 호환)"""

    def __init__(self, source_id, target_id, rule_id, priority, confidence, count=1):
        # type: (int, int, str, int, float, int) -> None
        self.source_id = source_id
        self.target_id = target_id
        self.rule_id = rule_id
        self.priority = priority
        self.confidence = confidence
        self.count = count  # 동일 연결 발생 횟수


class EntanglementMap:
    """
    에이전트 간 Quantum Entanglement 맵 (Python 3.6 호환)

    상관계수 계산:
    - 연결 빈도 (frequency): 몇 개의 룰에서 연결되는가
    - 연결 강도 (strength): 룰의 priority × confidence
    - 양방향성 (bidirectional): A→B와 B→A 모두 존재하면 보강
    """

    def __init__(self, connections=None, connection_count=None, agent_activity=None):
        # type: (Optional[Dict], Optional[Dict], Optional[Dict]) -> None
        self.connections = connections if connections is not None else {}
        self.connection_count = connection_count if connection_count is not None else defaultdict(int)
        self.agent_activity = agent_activity if agent_activity is not None else defaultdict(int)

    def add_connection(
        self,
        source: int,
        target: int,
        priority: int = 90,
        confidence: float = 0.9
    ) -> None:
        """연결 추가 및 상관계수 계산"""
        if source == target:
            return  # 자기 자신 연결은 제외

        key = (min(source, target), max(source, target))

        # 연결 강도 계산: priority와 confidence 결합
        strength = confidence * math.sqrt(priority / 100)

        # 기존 값과 평균 (연결이 많을수록 강화)
        if key in self.connections:
            old_strength = self.connections[key]
            self.connection_count[key] += 1
            # 보강 효과: 연결이 많을수록 상관계수 증가
            self.connections[key] = min(1.0, old_strength + strength * 0.1)
        else:
            self.connections[key] = strength
            self.connection_count[key] = 1

        # 에이전트 활동도 추적
        self.agent_activity[source] += 1
        self.agent_activity[target] += 1

    def get_correlation(self, agent1: int, agent2: int) -> float:
        """두 에이전트 간 상관계수 조회"""
        key = (min(agent1, agent2), max(agent1, agent2))
        return self.connections.get(key, 0.0)

    def get_correlated_agents(self, agent_id: int, threshold: float = 0.5) -> List[Tuple[int, float]]:
        """특정 에이전트와 상관관계가 높은 에이전트 목록"""
        results = []
        for (a1, a2), corr in self.connections.items():
            if a1 == agent_id and corr >= threshold:
                results.append((a2, corr))
            elif a2 == agent_id and corr >= threshold:
                results.append((a1, corr))
        return sorted(results, key=lambda x: x[1], reverse=True)

    def get_connection_matrix(self) -> Dict[int, Dict[int, float]]:
        """전체 상관계수 행렬 반환"""
        matrix = defaultdict(dict)
        for (a1, a2), corr in self.connections.items():
            matrix[a1][a2] = corr
            matrix[a2][a1] = corr
        return dict(matrix)

# ==============================================================================
# 3. 실측 데이터 기반 연결 정의 (rules.yaml 분석 결과)
# ==============================================================================

# Agent 05 (Learning Emotion) → 다른 에이전트 연결
AGENT05_CONNECTIONS = [
    # (target_name, count, avg_priority, avg_confidence)
    ("concept_note_agent", 12, 90, 0.89),       # 개념노트 연결 빈도 높음
    ("problem_note_agent", 10, 88, 0.88),       # 문제노트 연결
    ("rest_routine_agent", 14, 85, 0.87),       # 휴식루틴 연결 매우 빈번
    ("calmness_agent", 8, 90, 0.90),            # 침착도 연결
    ("qa_agent", 6, 86, 0.86),                  # Q&A 연결
    ("goals_analysis_agent", 5, 88, 0.88),      # 목표분석 연결
    ("type_learning_agent", 4, 87, 0.87),       # 유형학습 연결
    ("exam_schedule_agent", 3, 92, 0.90),       # 시험일정 연결
    ("learning_management_agent", 3, 87, 0.87), # 학습관리 연결
    ("signature_routine_agent", 2, 88, 0.88),   # 시그니처 루틴
    ("error_note_agent", 4, 86, 0.86),          # 오답노트
    ("review_routine_agent", 4, 85, 0.85),      # 복습루틴
    ("fatigue_analysis_agent", 2, 84, 0.84),    # 피로도 분석
    ("pomodoro_agent", 2, 84, 0.84),            # 포모도로
    ("home_check_agent", 2, 82, 0.82),          # 가정확인
]

# Agent 02 (Exam Schedule) → 다른 에이전트 연결 (내부 경로가 대부분)
AGENT02_CONNECTIONS = [
    ("goals_analysis_agent", 3, 92, 0.92),
    ("learning_management_agent", 2, 88, 0.88),
    ("calmness_agent", 2, 85, 0.85),
]

# Agent 03 (Goals Analysis) → 다른 에이전트 연결
AGENT03_CONNECTIONS = [
    ("exam_schedule_agent", 4, 95, 0.92),
    ("learning_emotion_agent", 3, 90, 0.90),
    ("learning_management_agent", 2, 88, 0.88),
    ("inspect_weakpoints_agent", 2, 90, 0.90),
]

# Agent 08 (Calmness) → 다른 에이전트 연결
AGENT08_CONNECTIONS = [
    ("learning_emotion_agent", 5, 90, 0.90),
    ("rest_routine_agent", 4, 88, 0.88),
    ("concept_note_agent", 2, 85, 0.85),
    ("learning_management_agent", 2, 86, 0.86),
]

# Agent 01 (Onboarding) → 초기 연결
AGENT01_CONNECTIONS = [
    ("goals_analysis_agent", 5, 99, 0.97),      # 온보딩 → 목표설정
    ("exam_schedule_agent", 4, 98, 0.96),       # 온보딩 → 시험일정
    ("learning_emotion_agent", 3, 95, 0.94),    # 온보딩 → 감정진단
]

# ==============================================================================
# 4. EntanglementMap 생성 함수
# ==============================================================================

def build_entanglement_map() -> EntanglementMap:
    """전체 룰 기반 EntanglementMap 구축"""
    emap = EntanglementMap()

    def add_connections(source_id: int, connections: list):
        for target_name, count, priority, confidence in connections:
            target_id = AGENT_NAME_TO_ID.get(target_name)
            if target_id:
                for _ in range(count):
                    emap.add_connection(source_id, target_id, priority, confidence)

    # 각 에이전트의 연결 추가
    add_connections(5, AGENT05_CONNECTIONS)
    add_connections(2, AGENT02_CONNECTIONS)
    add_connections(3, AGENT03_CONNECTIONS)
    add_connections(8, AGENT08_CONNECTIONS)
    add_connections(1, AGENT01_CONNECTIONS)

    return emap

# ==============================================================================
# 5. 전역 EntanglementMap 인스턴스
# ==============================================================================

ENTANGLEMENT_MAP = build_entanglement_map()

# ==============================================================================
# 6. 유틸리티 함수
# ==============================================================================

def get_agent_id(name: str) -> Optional[int]:
    """에이전트 이름 → ID"""
    return AGENT_NAME_TO_ID.get(name)

def get_agent_name(agent_id: int) -> str:
    """에이전트 ID → 이름"""
    return AGENT_ID_TO_NAME.get(agent_id, f"Agent{agent_id:02d}")

def get_correlation(agent1: int, agent2: int) -> float:
    """두 에이전트 간 상관계수 조회"""
    return ENTANGLEMENT_MAP.get_correlation(agent1, agent2)

def get_entangled_agents(agent_id: int, threshold: float = 0.5) -> List[Tuple[int, float]]:
    """특정 에이전트와 얽힌(entangled) 에이전트 목록"""
    return ENTANGLEMENT_MAP.get_correlated_agents(agent_id, threshold)

def calculate_entanglement_strength(agents: List[int]) -> float:
    """
    여러 에이전트 동시 활성화 시 총 얽힘 강도 계산

    공식: Σ(correlation_ij) for all pairs (i,j) in agents
    """
    if len(agents) < 2:
        return 0.0

    total = 0.0
    for i in range(len(agents)):
        for j in range(i + 1, len(agents)):
            total += get_correlation(agents[i], agents[j])

    return total


# ==============================================================================
# 8. 비국소적 상관관계 (Non-local Correlation)
# ==============================================================================

class NonLocalCorrelation:
    """
    비국소적 상관관계를 관리하는 클래스.

    양자역학에서 얽힌 입자들이 거리와 상관없이 즉각적으로
    상관관계를 보이듯이, 연결된 에이전트들 간의 즉각적인
    영향 전파를 모델링합니다.

    주요 기능:
    ---------
    1. 즉각적 효과 전파 (Instantaneous Effect Propagation)
    2. 상관관계 기반 활성화 부스트
    3. Temporal Entanglement 통합

    Attributes:
        entanglement_map (EntanglementMap): 기존 얽힘 맵
        propagation_decay (float): 전파 시 감쇠율 (0.0 ~ 1.0)
        min_correlation_threshold (float): 최소 상관관계 임계값
    """

    # 전파 감쇠율 (직접 연결 = 1.0, 간접 연결은 감쇠)
    DEFAULT_PROPAGATION_DECAY = 0.7

    # 최소 상관관계 임계값 (이 이상이어야 전파)
    MIN_CORRELATION_THRESHOLD = 0.2

    def __init__(
        self,
        entanglement_map=None,  # type: Optional[EntanglementMap]
        propagation_decay=None,  # type: Optional[float]
        min_threshold=None       # type: Optional[float]
    ):
        # type: (...) -> None
        """NonLocalCorrelation 초기화."""
        self.entanglement_map = entanglement_map or ENTANGLEMENT_MAP
        self.propagation_decay = (
            propagation_decay if propagation_decay is not None
            else self.DEFAULT_PROPAGATION_DECAY
        )
        self.min_correlation_threshold = (
            min_threshold if min_threshold is not None
            else self.MIN_CORRELATION_THRESHOLD
        )

    def propagate_activation(
        self,
        source_agent_id,   # type: int
        activation_strength,  # type: float
        max_depth=2        # type: int
    ):
        # type: (...) -> Dict[int, float]
        """
        에이전트 활성화를 연결된 에이전트들에게 전파합니다.

        양자역학의 비국소적 상관관계처럼, 소스 에이전트가 활성화되면
        연결된 에이전트들도 상관관계에 비례하여 활성화됩니다.

        Args:
            source_agent_id: 활성화된 소스 에이전트 ID
            activation_strength: 소스 활성화 강도 (0.0 ~ 1.0)
            max_depth: 최대 전파 깊이 (1 = 직접 연결만, 2 = 간접 연결 포함)

        Returns:
            에이전트별 전파된 활성화 강도 딕셔너리
        """
        propagated = {}  # type: Dict[int, float]
        visited = {source_agent_id}  # type: Set[int]

        # BFS 방식으로 전파
        current_level = [(source_agent_id, activation_strength)]
        depth = 0

        while current_level and depth < max_depth:
            next_level = []

            for agent_id, strength in current_level:
                # 연결된 에이전트들 찾기
                correlated = self.entanglement_map.get_correlated_agents(
                    agent_id, threshold=self.min_correlation_threshold
                )

                for target_id, correlation in correlated:
                    if target_id in visited:
                        continue

                    # 전파 강도 계산
                    propagated_strength = (
                        strength *
                        correlation *
                        (self.propagation_decay ** depth)
                    )

                    if propagated_strength >= 0.01:  # 최소 임계값
                        # 기존 값과 합산 (중복 경로로 인한 보강 효과)
                        if target_id in propagated:
                            propagated[target_id] = min(
                                1.0,
                                propagated[target_id] + propagated_strength * 0.5
                            )
                        else:
                            propagated[target_id] = propagated_strength

                        next_level.append((target_id, propagated_strength))
                        visited.add(target_id)

            current_level = next_level
            depth += 1

        return propagated

    def get_instantaneous_correlation(
        self,
        agent_ids  # type: List[int]
    ):
        # type: (...) -> float
        """
        여러 에이전트들의 순간적 상관관계 강도를 계산합니다.

        모든 에이전트 쌍의 상관관계를 기하평균으로 결합하여
        전체 그룹의 "양자 결맞음(coherence)" 수준을 측정합니다.

        Args:
            agent_ids: 에이전트 ID 리스트

        Returns:
            순간적 상관관계 강도 (0.0 ~ 1.0)
        """
        if len(agent_ids) < 2:
            return 0.0

        correlations = []
        for i in range(len(agent_ids)):
            for j in range(i + 1, len(agent_ids)):
                corr = self.entanglement_map.get_correlation(
                    agent_ids[i], agent_ids[j]
                )
                if corr > 0:
                    correlations.append(corr)

        if not correlations:
            return 0.0

        # 기하평균으로 전체 결맞음 계산
        product = 1.0
        for c in correlations:
            product *= c

        return product ** (1.0 / len(correlations))

    def find_correlation_cluster(
        self,
        seed_agent_id,  # type: int
        min_correlation=0.4  # type: float
    ):
        # type: (...) -> List[int]
        """
        특정 에이전트를 중심으로 강하게 연결된 클러스터를 찾습니다.

        양자역학의 "얽힘 네트워크"처럼, 서로 강하게 연결된
        에이전트 그룹을 식별합니다.

        Args:
            seed_agent_id: 시드 에이전트 ID
            min_correlation: 클러스터 포함 최소 상관관계

        Returns:
            클러스터에 포함된 에이전트 ID 리스트
        """
        cluster = [seed_agent_id]
        candidates = set()

        # 시드와 연결된 에이전트들 수집
        connected = self.entanglement_map.get_correlated_agents(
            seed_agent_id, threshold=min_correlation
        )

        for target_id, _ in connected:
            candidates.add(target_id)

        # 클러스터 확장: 클러스터 내 모든 멤버와 높은 상관관계를 가진 에이전트만 포함
        while candidates:
            best_candidate = None
            best_avg_correlation = 0.0

            for candidate in candidates:
                # 클러스터 멤버들과의 평균 상관관계 계산
                total_corr = 0.0
                for member in cluster:
                    total_corr += self.entanglement_map.get_correlation(
                        candidate, member
                    )
                avg_corr = total_corr / len(cluster)

                if avg_corr >= min_correlation and avg_corr > best_avg_correlation:
                    best_candidate = candidate
                    best_avg_correlation = avg_corr

            if best_candidate is None:
                break

            cluster.append(best_candidate)
            candidates.remove(best_candidate)

            # 새 멤버의 연결 에이전트도 후보에 추가
            new_connected = self.entanglement_map.get_correlated_agents(
                best_candidate, threshold=min_correlation
            )
            for target_id, _ in new_connected:
                if target_id not in cluster:
                    candidates.add(target_id)

        return cluster

    def calculate_bell_inequality(
        self,
        agent_a,  # type: int
        agent_b,  # type: int
        agent_c   # type: int
    ):
        # type: (...) -> Dict[str, float]
        """
        세 에이전트 간의 벨 부등식(Bell Inequality) 위반 여부를 검사합니다.

        양자역학에서 벨 부등식 위반은 진정한 양자 얽힘의 증거입니다.
        이를 학습 시스템에 적용하여 에이전트 간 연결의 "양자적 특성"을
        측정합니다.

        벨 부등식: |P(a,b) - P(a,c)| ≤ 1 + P(b,c)
        위반 시: 양자 상관관계가 고전적 상관관계보다 강함

        Args:
            agent_a, agent_b, agent_c: 세 에이전트 ID

        Returns:
            벨 부등식 분석 결과 딕셔너리
        """
        # 쌍별 상관관계
        p_ab = self.entanglement_map.get_correlation(agent_a, agent_b)
        p_ac = self.entanglement_map.get_correlation(agent_a, agent_c)
        p_bc = self.entanglement_map.get_correlation(agent_b, agent_c)

        # CHSH 형태의 벨 부등식 검사
        left_side = abs(p_ab - p_ac)
        right_side = 1 + p_bc

        # 양자 시스템에서는 최대 2√2 ≈ 2.83까지 가능
        # 우리 시스템에서는 정규화된 값 사용
        violation = left_side - right_side
        is_violated = violation > 0

        return {
            'correlation_ab': p_ab,
            'correlation_ac': p_ac,
            'correlation_bc': p_bc,
            'bell_left_side': left_side,
            'bell_right_side': right_side,
            'violation_amount': max(0, violation),
            'is_violated': is_violated,
            'quantum_strength': (left_side / right_side) if right_side > 0 else 0.0
        }


# 전역 NonLocalCorrelation 인스턴스
NON_LOCAL_CORRELATION = NonLocalCorrelation()


# ==============================================================================
# 9. NonLocalCorrelation 편의 함수
# ==============================================================================

def propagate_agent_activation(
    source_agent_id,   # type: int
    activation_strength=1.0,  # type: float
    max_depth=2        # type: int
):
    # type: (...) -> Dict[int, float]
    """
    에이전트 활성화를 비국소적으로 전파합니다.

    Args:
        source_agent_id: 소스 에이전트 ID
        activation_strength: 활성화 강도
        max_depth: 전파 깊이

    Returns:
        에이전트별 전파된 활성화 강도
    """
    return NON_LOCAL_CORRELATION.propagate_activation(
        source_agent_id, activation_strength, max_depth
    )


def get_quantum_coherence(agent_ids):
    # type: (List[int]) -> float
    """
    에이전트 그룹의 양자 결맞음(coherence)을 계산합니다.

    Args:
        agent_ids: 에이전트 ID 리스트

    Returns:
        결맞음 강도 (0.0 ~ 1.0)
    """
    return NON_LOCAL_CORRELATION.get_instantaneous_correlation(agent_ids)


def find_entanglement_cluster(seed_agent_id, min_correlation=0.4):
    # type: (int, float) -> List[int]
    """
    에이전트 얽힘 클러스터를 찾습니다.

    Args:
        seed_agent_id: 시드 에이전트 ID
        min_correlation: 최소 상관관계

    Returns:
        클러스터 에이전트 ID 리스트
    """
    return NON_LOCAL_CORRELATION.find_correlation_cluster(
        seed_agent_id, min_correlation
    )

# ==============================================================================
# 10. 테스트 함수
# ==============================================================================

def run_entanglement_test():
    """EntanglementMap 테스트 실행"""
    print("=" * 60)
    print("Quantum Orchestration - Phase 2: EntanglementMap 테스트")
    print("=" * 60)
    print()

    # 1. 총 연결 수
    print(f"📊 [1] EntanglementMap 통계")
    print(f"   - 총 연결 쌍: {len(ENTANGLEMENT_MAP.connections)}개")
    print(f"   - 총 연결 횟수: {sum(ENTANGLEMENT_MAP.connection_count.values())}회")
    print()

    # 2. 상관계수 상위 10개 출력
    print("🔗 [2] 상관계수 상위 10개 연결")
    sorted_connections = sorted(
        ENTANGLEMENT_MAP.connections.items(),
        key=lambda x: x[1],
        reverse=True
    )[:10]

    for (a1, a2), corr in sorted_connections:
        name1 = get_agent_name(a1)
        name2 = get_agent_name(a2)
        count = ENTANGLEMENT_MAP.connection_count[(a1, a2)]
        bar = "█" * int(corr * 20)
        print(f"   {name1:20} ↔ {name2:20}: {bar} {corr:.4f} ({count}회)")
    print()

    # 3. 특정 에이전트 얽힘 조회
    print("🎯 [3] Agent 05 (Learning Emotion)와 얽힌 에이전트")
    entangled = get_entangled_agents(5, threshold=0.3)
    for target_id, corr in entangled:
        name = get_agent_name(target_id)
        bar = "█" * int(corr * 20)
        print(f"   → {name:25}: {bar} {corr:.4f}")
    print()

    # 4. 다중 에이전트 얽힘 강도
    print("🌊 [4] 다중 에이전트 동시 활성화 얽힘 강도")

    test_cases = [
        ([1, 3], "Onboarding + Goals Analysis"),
        ([5, 8], "Learning Emotion + Calmness"),
        ([5, 10, 11], "Emotion + Concept + Problem Notes"),
        ([1, 2, 3], "Onboarding + Exam + Goals"),
        ([5, 8, 12], "Emotion + Calmness + Rest"),
    ]

    for agents, desc in test_cases:
        strength = calculate_entanglement_strength(agents)
        agent_names = [get_agent_name(a) for a in agents]
        print(f"   {desc}")
        print(f"      Agents: {agent_names}")
        print(f"      Entanglement Strength: {strength:.4f}")
        print()

    # 5. 에이전트별 활동도
    print("📈 [5] 에이전트별 연결 활동도 (상위 10)")
    sorted_activity = sorted(
        ENTANGLEMENT_MAP.agent_activity.items(),
        key=lambda x: x[1],
        reverse=True
    )[:10]

    max_activity = sorted_activity[0][1] if sorted_activity else 1
    for agent_id, activity in sorted_activity:
        name = get_agent_name(agent_id)
        bar_len = int(activity / max_activity * 30)
        bar = "█" * bar_len
        print(f"   Agent {agent_id:02d} ({name:20}): {bar} {activity}")
    print()

    # 6. 상관계수 행렬 샘플 출력
    print("📋 [6] 상관계수 행렬 (Agent 1-10)")
    print("      ", end="")
    for j in range(1, 11):
        print(f"A{j:02d}  ", end="")
    print()

    for i in range(1, 11):
        print(f"   A{i:02d}", end=" ")
        for j in range(1, 11):
            corr = get_correlation(i, j)
            if corr > 0:
                print(f"{corr:.2f} ", end="")
            else:
                print("  -  ", end="")
        print()
    print()

    print("=" * 60)
    print("✅ Phase 2 EntanglementMap 테스트 완료")
    print("=" * 60)


def run_nonlocal_correlation_test():
    """NonLocalCorrelation 테스트 실행"""
    print()
    print("=" * 60)
    print("Phase 4 Extension: Non-local Correlation 테스트")
    print("=" * 60)
    print()

    # 1. 활성화 전파 테스트
    print("🌐 [1] 비국소적 활성화 전파")
    print("   Source: Agent 05 (Learning Emotion), Activation: 1.0")
    print()

    propagated = propagate_agent_activation(5, 1.0, max_depth=2)
    sorted_prop = sorted(propagated.items(), key=lambda x: x[1], reverse=True)

    for agent_id, strength in sorted_prop[:8]:
        name = get_agent_name(agent_id)
        bar = "█" * int(strength * 30)
        print(f"   → {name:25}: {bar} {strength:.4f}")
    print()

    # 2. 양자 결맞음 테스트
    print("⚛️ [2] 양자 결맞음 (Quantum Coherence)")
    coherence_tests = [
        [5, 8],       # Emotion + Calmness
        [5, 10, 11],  # Emotion + Concept + Problem Notes
        [1, 2, 3],    # Onboarding + Exam + Goals
    ]

    for agents in coherence_tests:
        names = [get_agent_name(a) for a in agents]
        coherence = get_quantum_coherence(agents)
        print(f"   Agents: {names}")
        print(f"   Coherence: {coherence:.4f}")
        print()

    # 3. 얽힘 클러스터 테스트
    print("🔮 [3] 얽힘 클러스터 탐색")
    print("   Seed: Agent 05 (Learning Emotion)")
    print()

    cluster = find_entanglement_cluster(5, min_correlation=0.3)
    cluster_names = [get_agent_name(a) for a in cluster]
    print(f"   Cluster size: {len(cluster)}")
    print(f"   Members: {cluster_names}")
    print()

    # 4. 벨 부등식 테스트
    print("🔔 [4] 벨 부등식 검사")
    bell_tests = [
        (5, 8, 12),   # Emotion, Calmness, Rest
        (1, 2, 3),    # Onboarding, Exam, Goals
        (5, 10, 11),  # Emotion, Concept, Problem
    ]

    for a, b, c in bell_tests:
        names = (get_agent_name(a), get_agent_name(b), get_agent_name(c))
        result = NON_LOCAL_CORRELATION.calculate_bell_inequality(a, b, c)
        print(f"   Agents: {names}")
        print(f"   Correlations: AB={result['correlation_ab']:.3f}, "
              f"AC={result['correlation_ac']:.3f}, BC={result['correlation_bc']:.3f}")
        print(f"   Bell Inequality: |{result['bell_left_side']:.3f}| ≤ {result['bell_right_side']:.3f}")
        print(f"   Violated: {result['is_violated']}, Quantum Strength: {result['quantum_strength']:.3f}")
        print()

    print("=" * 60)
    print("✅ Phase 4 Non-local Correlation 테스트 완료")
    print("=" * 60)


# ==============================================================================
# 11. __all__ 내보내기
# ==============================================================================

__all__ = [
    # Classes
    'AgentConnection',
    'EntanglementMap',
    'NonLocalCorrelation',

    # Constants
    'AGENT_NAME_TO_ID',
    'AGENT_ID_TO_NAME',

    # Global Instances
    'ENTANGLEMENT_MAP',
    'NON_LOCAL_CORRELATION',

    # EntanglementMap Functions
    'build_entanglement_map',
    'get_agent_id',
    'get_agent_name',
    'get_correlation',
    'get_entangled_agents',
    'calculate_entanglement_strength',

    # NonLocalCorrelation Functions
    'propagate_agent_activation',
    'get_quantum_coherence',
    'find_entanglement_cluster',

    # Test Functions
    'run_entanglement_test',
    'run_nonlocal_correlation_test',
]


# ==============================================================================
# Main
# ==============================================================================

if __name__ == "__main__":
    run_entanglement_test()
    run_nonlocal_correlation_test()
