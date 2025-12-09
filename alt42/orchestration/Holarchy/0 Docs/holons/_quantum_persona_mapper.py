#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Quantum Persona Mapper - Phase 1 Implementation
================================================
54개 페르소나 → StateVector 매핑 테이블

목적: 기존 페르소나 시스템의 심리/행동 특성을
      Quantum StateVector의 64차원으로 변환

참조:
- agents/agent01_onboarding/persona_system/personas.md
- Quantum Orchestration Hybrid Integration Plan (Phase 1)
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

from typing import Dict, List, Optional, Tuple
from enum import Enum
import math

# Python 3.6 호환: dataclasses 대신 일반 클래스 사용

# ==============================================================================
# 1. StateVector 차원 정의 (8개 주요 심리 차원)
# ==============================================================================

class PsychDimension(Enum):
    """학생 심리 상태의 8개 핵심 차원"""
    METACOGNITION = "metacognition"           # 메타인지 (자기 학습 인식)
    SELF_EFFICACY = "self_efficacy"           # 자기효능감
    HELP_SEEKING = "help_seeking"             # 도움 요청 성향
    EMOTIONAL_REGULATION = "emotional_reg"    # 정서 조절
    ANXIETY = "anxiety"                       # 불안 수준
    CONFIDENCE = "confidence"                 # 자신감
    ENGAGEMENT = "engagement"                 # 참여도/몰입
    MOTIVATION = "motivation"                 # 동기 수준


class StateVector:
    """
    학생 심리 상태 벡터 (8차원) - Python 3.6 호환
    각 차원은 0.0 ~ 1.0 범위
    """

    def __init__(
        self,
        metacognition=0.5,
        self_efficacy=0.5,
        help_seeking=0.5,
        emotional_regulation=0.5,
        anxiety=0.5,
        confidence=0.5,
        engagement=0.5,
        motivation=0.5
    ):
        # type: (float, float, float, float, float, float, float, float) -> None
        self.metacognition = metacognition
        self.self_efficacy = self_efficacy
        self.help_seeking = help_seeking
        self.emotional_regulation = emotional_regulation
        self.anxiety = anxiety
        self.confidence = confidence
        self.engagement = engagement
        self.motivation = motivation

    def to_dict(self):
        return {
            "metacognition": self.metacognition,
            "self_efficacy": self.self_efficacy,
            "help_seeking": self.help_seeking,
            "emotional_regulation": self.emotional_regulation,
            "anxiety": self.anxiety,
            "confidence": self.confidence,
            "engagement": self.engagement,
            "motivation": self.motivation
        }

    def magnitude(self) -> float:
        """벡터 크기 (L2 norm)"""
        values = list(self.to_dict().values())
        return math.sqrt(sum(v**2 for v in values))

    def normalize(self) -> 'StateVector':
        """정규화된 벡터 반환"""
        mag = self.magnitude()
        if mag == 0:
            return StateVector()
        d = self.to_dict()
        return StateVector(**{k: v/mag for k, v in d.items()})


# ==============================================================================
# 2. 54개 페르소나 → StateVector 매핑 테이블
# ==============================================================================

# S0: 수학 특화 정보 수집 단계 (6개)
S0_PERSONAS = {
    "S0_P1": StateVector(  # 솔직한 자기 분석가
        metacognition=0.85,
        self_efficacy=0.65,
        help_seeking=0.80,
        emotional_regulation=0.70,
        anxiety=0.30,
        confidence=0.60,
        engagement=0.75,
        motivation=0.70
    ),
    "S0_P2": StateVector(  # 방어적 최소 응답자
        metacognition=0.40,
        self_efficacy=0.35,
        help_seeking=0.20,
        emotional_regulation=0.50,
        anxiety=0.70,
        confidence=0.30,
        engagement=0.25,
        motivation=0.35
    ),
    "S0_P3": StateVector(  # 과대 포장형 자신감
        metacognition=0.35,
        self_efficacy=0.80,
        help_seeking=0.30,
        emotional_regulation=0.55,
        anxiety=0.40,
        confidence=0.90,
        engagement=0.60,
        motivation=0.65
    ),
    "S0_P4": StateVector(  # 불안한 완벽주의자
        metacognition=0.70,
        self_efficacy=0.45,
        help_seeking=0.55,
        emotional_regulation=0.35,
        anxiety=0.85,
        confidence=0.40,
        engagement=0.70,
        motivation=0.75
    ),
    "S0_P5": StateVector(  # 무관심한 수동적 참여자
        metacognition=0.30,
        self_efficacy=0.40,
        help_seeking=0.20,
        emotional_regulation=0.60,
        anxiety=0.25,
        confidence=0.45,
        engagement=0.15,
        motivation=0.20
    ),
    "S0_P6": StateVector(  # 호기심 많은 탐색자
        metacognition=0.75,
        self_efficacy=0.70,
        help_seeking=0.85,
        emotional_regulation=0.75,
        anxiety=0.25,
        confidence=0.70,
        engagement=0.90,
        motivation=0.85
    ),
}

# S1: 신규 학생 등록 직후 (6개)
S1_PERSONAS = {
    "S1_P1": StateVector(  # 기대에 찬 새출발형
        metacognition=0.60,
        self_efficacy=0.65,
        help_seeking=0.75,
        emotional_regulation=0.70,
        anxiety=0.35,
        confidence=0.70,
        engagement=0.85,
        motivation=0.90
    ),
    "S1_P2": StateVector(  # 과거 트라우마형 긴장자
        metacognition=0.55,
        self_efficacy=0.25,
        help_seeking=0.40,
        emotional_regulation=0.35,
        anxiety=0.85,
        confidence=0.20,
        engagement=0.40,
        motivation=0.45
    ),
    "S1_P3": StateVector(  # 부모 눈치형 의무 참여자
        metacognition=0.45,
        self_efficacy=0.40,
        help_seeking=0.35,
        emotional_regulation=0.55,
        anxiety=0.50,
        confidence=0.35,
        engagement=0.30,
        motivation=0.25
    ),
    "S1_P4": StateVector(  # 테스트 경계형
        metacognition=0.65,
        self_efficacy=0.60,
        help_seeking=0.25,
        emotional_regulation=0.50,
        anxiety=0.45,
        confidence=0.65,
        engagement=0.55,
        motivation=0.50
    ),
    "S1_P5": StateVector(  # 목표 명확형 실용주의자
        metacognition=0.80,
        self_efficacy=0.75,
        help_seeking=0.60,
        emotional_regulation=0.75,
        anxiety=0.30,
        confidence=0.80,
        engagement=0.80,
        motivation=0.85
    ),
    "S1_P6": StateVector(  # 사교적 관계 지향형
        metacognition=0.55,
        self_efficacy=0.60,
        help_seeking=0.80,
        emotional_regulation=0.70,
        anxiety=0.35,
        confidence=0.65,
        engagement=0.75,
        motivation=0.70
    ),
}

# S2: 수업 전 학습 설계 단계 (6개)
S2_PERSONAS = {
    "S2_P1": StateVector(  # 계획 수용형 따르는 학습자
        metacognition=0.50,
        self_efficacy=0.55,
        help_seeking=0.70,
        emotional_regulation=0.70,
        anxiety=0.35,
        confidence=0.55,
        engagement=0.65,
        motivation=0.60
    ),
    "S2_P2": StateVector(  # 자기주도형 협상가
        metacognition=0.85,
        self_efficacy=0.80,
        help_seeking=0.55,
        emotional_regulation=0.75,
        anxiety=0.25,
        confidence=0.80,
        engagement=0.80,
        motivation=0.80
    ),
    "S2_P3": StateVector(  # 과부하 회피형 최소주의자
        metacognition=0.55,
        self_efficacy=0.45,
        help_seeking=0.40,
        emotional_regulation=0.60,
        anxiety=0.55,
        confidence=0.40,
        engagement=0.35,
        motivation=0.40
    ),
    "S2_P4": StateVector(  # 완벽주의 과다 계획형
        metacognition=0.75,
        self_efficacy=0.50,
        help_seeking=0.45,
        emotional_regulation=0.40,
        anxiety=0.80,
        confidence=0.45,
        engagement=0.85,
        motivation=0.80
    ),
    "S2_P5": StateVector(  # 시험 중심 전략가
        metacognition=0.75,
        self_efficacy=0.70,
        help_seeking=0.50,
        emotional_regulation=0.65,
        anxiety=0.45,
        confidence=0.70,
        engagement=0.70,
        motivation=0.75
    ),
    "S2_P6": StateVector(  # 유연한 적응형
        metacognition=0.70,
        self_efficacy=0.70,
        help_seeking=0.65,
        emotional_regulation=0.80,
        anxiety=0.25,
        confidence=0.70,
        engagement=0.70,
        motivation=0.70
    ),
}

# S3: 개념/심화 진도 판단 (6개)
S3_PERSONAS = {
    "S3_P1": StateVector(  # 진도 불안형 조급자
        metacognition=0.60,
        self_efficacy=0.45,
        help_seeking=0.55,
        emotional_regulation=0.35,
        anxiety=0.85,
        confidence=0.40,
        engagement=0.70,
        motivation=0.75
    ),
    "S3_P2": StateVector(  # 기초 회피형 점프러
        metacognition=0.40,
        self_efficacy=0.70,
        help_seeking=0.30,
        emotional_regulation=0.55,
        anxiety=0.40,
        confidence=0.75,
        engagement=0.55,
        motivation=0.60
    ),
    "S3_P3": StateVector(  # 겸손한 과소평가형
        metacognition=0.70,
        self_efficacy=0.35,
        help_seeking=0.65,
        emotional_regulation=0.65,
        anxiety=0.55,
        confidence=0.30,
        engagement=0.60,
        motivation=0.65
    ),
    "S3_P4": StateVector(  # 갭 인정 수용형
        metacognition=0.80,
        self_efficacy=0.60,
        help_seeking=0.75,
        emotional_regulation=0.75,
        anxiety=0.35,
        confidence=0.55,
        engagement=0.75,
        motivation=0.75
    ),
    "S3_P5": StateVector(  # 방어적 합리화형
        metacognition=0.45,
        self_efficacy=0.55,
        help_seeking=0.30,
        emotional_regulation=0.50,
        anxiety=0.60,
        confidence=0.60,
        engagement=0.45,
        motivation=0.45
    ),
    "S3_P6": StateVector(  # 분석적 이해 추구형
        metacognition=0.90,
        self_efficacy=0.70,
        help_seeking=0.70,
        emotional_regulation=0.75,
        anxiety=0.30,
        confidence=0.65,
        engagement=0.85,
        motivation=0.80
    ),
}

# S4: 학부모 상담 정보 (6개)
S4_PERSONAS = {
    "S4_P1": StateVector(  # 투명성 선호형 공개자
        metacognition=0.70,
        self_efficacy=0.65,
        help_seeking=0.80,
        emotional_regulation=0.75,
        anxiety=0.30,
        confidence=0.65,
        engagement=0.70,
        motivation=0.70
    ),
    "S4_P2": StateVector(  # 프라이버시 수호형
        metacognition=0.60,
        self_efficacy=0.55,
        help_seeking=0.30,
        emotional_regulation=0.65,
        anxiety=0.55,
        confidence=0.50,
        engagement=0.50,
        motivation=0.55
    ),
    "S4_P3": StateVector(  # 부모 눈치형 긴장자
        metacognition=0.50,
        self_efficacy=0.40,
        help_seeking=0.45,
        emotional_regulation=0.40,
        anxiety=0.80,
        confidence=0.35,
        engagement=0.55,
        motivation=0.50
    ),
    "S4_P4": StateVector(  # 부모-자녀 갈등형 중재 요청자
        metacognition=0.65,
        self_efficacy=0.45,
        help_seeking=0.85,
        emotional_regulation=0.45,
        anxiety=0.70,
        confidence=0.40,
        engagement=0.60,
        motivation=0.55
    ),
    "S4_P5": StateVector(  # 무관심형 단절자
        metacognition=0.40,
        self_efficacy=0.50,
        help_seeking=0.20,
        emotional_regulation=0.55,
        anxiety=0.35,
        confidence=0.50,
        engagement=0.25,
        motivation=0.30
    ),
    "S4_P6": StateVector(  # 부모 기대 부응형 성취자
        metacognition=0.65,
        self_efficacy=0.55,
        help_seeking=0.50,
        emotional_regulation=0.50,
        anxiety=0.65,
        confidence=0.55,
        engagement=0.80,
        motivation=0.85
    ),
}

# S5: 장기 목표 기반 설계 (6개)
S5_PERSONAS = {
    "S5_P1": StateVector(  # 야망찬 꿈꾸는 자
        metacognition=0.60,
        self_efficacy=0.75,
        help_seeking=0.60,
        emotional_regulation=0.65,
        anxiety=0.40,
        confidence=0.85,
        engagement=0.80,
        motivation=0.90
    ),
    "S5_P2": StateVector(  # 현실적 계획가
        metacognition=0.85,
        self_efficacy=0.70,
        help_seeking=0.65,
        emotional_regulation=0.80,
        anxiety=0.30,
        confidence=0.70,
        engagement=0.75,
        motivation=0.75
    ),
    "S5_P3": StateVector(  # 목표 미정형 탐색자
        metacognition=0.50,
        self_efficacy=0.50,
        help_seeking=0.60,
        emotional_regulation=0.60,
        anxiety=0.45,
        confidence=0.45,
        engagement=0.55,
        motivation=0.50
    ),
    "S5_P4": StateVector(  # 외압형 목표 수용자
        metacognition=0.45,
        self_efficacy=0.40,
        help_seeking=0.40,
        emotional_regulation=0.55,
        anxiety=0.55,
        confidence=0.40,
        engagement=0.40,
        motivation=0.35
    ),
    "S5_P5": StateVector(  # 목표-현실 괴리 인식자
        metacognition=0.75,
        self_efficacy=0.45,
        help_seeking=0.70,
        emotional_regulation=0.55,
        anxiety=0.65,
        confidence=0.40,
        engagement=0.60,
        motivation=0.60
    ),
    "S5_P6": StateVector(  # 성장 마인드셋 보유자
        metacognition=0.80,
        self_efficacy=0.75,
        help_seeking=0.75,
        emotional_regulation=0.80,
        anxiety=0.25,
        confidence=0.75,
        engagement=0.85,
        motivation=0.90
    ),
}

# C-Series: 복합 상황 (6개)
C_PERSONAS = {
    "C_P1": StateVector(  # 다중 어려움 압도형
        metacognition=0.45,
        self_efficacy=0.25,
        help_seeking=0.55,
        emotional_regulation=0.25,
        anxiety=0.90,
        confidence=0.20,
        engagement=0.35,
        motivation=0.30
    ),
    "C_P2": StateVector(  # 저항적 복합 문제 보유자
        metacognition=0.40,
        self_efficacy=0.45,
        help_seeking=0.20,
        emotional_regulation=0.35,
        anxiety=0.70,
        confidence=0.50,
        engagement=0.30,
        motivation=0.35
    ),
    "C_P3": StateVector(  # 적극적 해결 추구형
        metacognition=0.75,
        self_efficacy=0.65,
        help_seeking=0.90,
        emotional_regulation=0.60,
        anxiety=0.55,
        confidence=0.60,
        engagement=0.80,
        motivation=0.80
    ),
    "C_P4": StateVector(  # 외부 귀인형 책임 회피자
        metacognition=0.35,
        self_efficacy=0.50,
        help_seeking=0.25,
        emotional_regulation=0.45,
        anxiety=0.50,
        confidence=0.55,
        engagement=0.35,
        motivation=0.40
    ),
    "C_P5": StateVector(  # 무기력 학습 포기자
        metacognition=0.30,
        self_efficacy=0.15,
        help_seeking=0.15,
        emotional_regulation=0.30,
        anxiety=0.60,
        confidence=0.15,
        engagement=0.10,
        motivation=0.10
    ),
    "C_P6": StateVector(  # 상황적 일시 저조형
        metacognition=0.60,
        self_efficacy=0.50,
        help_seeking=0.60,
        emotional_regulation=0.50,
        anxiety=0.55,
        confidence=0.45,
        engagement=0.45,
        motivation=0.45
    ),
}

# Q-Series: 포괄형 질문 상황 (6개)
Q_PERSONAS = {
    "Q_P1": StateVector(  # 전체 그림 파악형
        metacognition=0.80,
        self_efficacy=0.65,
        help_seeking=0.70,
        emotional_regulation=0.70,
        anxiety=0.30,
        confidence=0.65,
        engagement=0.75,
        motivation=0.70
    ),
    "Q_P2": StateVector(  # 세부 사항 집중형
        metacognition=0.75,
        self_efficacy=0.60,
        help_seeking=0.60,
        emotional_regulation=0.65,
        anxiety=0.45,
        confidence=0.60,
        engagement=0.80,
        motivation=0.70
    ),
    "Q_P3": StateVector(  # 관계 연결형
        metacognition=0.85,
        self_efficacy=0.70,
        help_seeking=0.75,
        emotional_regulation=0.75,
        anxiety=0.30,
        confidence=0.70,
        engagement=0.80,
        motivation=0.75
    ),
    "Q_P4": StateVector(  # 즉각 실행형
        metacognition=0.55,
        self_efficacy=0.75,
        help_seeking=0.50,
        emotional_regulation=0.65,
        anxiety=0.35,
        confidence=0.75,
        engagement=0.85,
        motivation=0.80
    ),
    "Q_P5": StateVector(  # 비교 분석형
        metacognition=0.85,
        self_efficacy=0.65,
        help_seeking=0.55,
        emotional_regulation=0.70,
        anxiety=0.40,
        confidence=0.65,
        engagement=0.75,
        motivation=0.70
    ),
    "Q_P6": StateVector(  # 피드백 수용형
        metacognition=0.70,
        self_efficacy=0.60,
        help_seeking=0.85,
        emotional_regulation=0.75,
        anxiety=0.35,
        confidence=0.55,
        engagement=0.75,
        motivation=0.75
    ),
}

# E-Series: 정서적 UX 상황 (6개)
E_PERSONAS = {
    "E_P1": StateVector(  # 수학 불안형 공포자
        metacognition=0.50,
        self_efficacy=0.20,
        help_seeking=0.45,
        emotional_regulation=0.25,
        anxiety=0.95,
        confidence=0.15,
        engagement=0.30,
        motivation=0.35
    ),
    "E_P2": StateVector(  # 자신감 회복 중인 도전자
        metacognition=0.65,
        self_efficacy=0.50,
        help_seeking=0.70,
        emotional_regulation=0.55,
        anxiety=0.50,
        confidence=0.45,
        engagement=0.70,
        motivation=0.75
    ),
    "E_P3": StateVector(  # 좌절 직전 위기형
        metacognition=0.55,
        self_efficacy=0.25,
        help_seeking=0.50,
        emotional_regulation=0.30,
        anxiety=0.85,
        confidence=0.20,
        engagement=0.35,
        motivation=0.30
    ),
    "E_P4": StateVector(  # 안정적 균형형
        metacognition=0.70,
        self_efficacy=0.70,
        help_seeking=0.65,
        emotional_regulation=0.80,
        anxiety=0.25,
        confidence=0.70,
        engagement=0.70,
        motivation=0.70
    ),
    "E_P5": StateVector(  # 흥미 기반 동기형
        metacognition=0.65,
        self_efficacy=0.70,
        help_seeking=0.60,
        emotional_regulation=0.70,
        anxiety=0.30,
        confidence=0.70,
        engagement=0.90,
        motivation=0.85
    ),
    "E_P6": StateVector(  # 외적 인정 추구형
        metacognition=0.55,
        self_efficacy=0.55,
        help_seeking=0.50,
        emotional_regulation=0.55,
        anxiety=0.55,
        confidence=0.60,
        engagement=0.75,
        motivation=0.80
    ),
}


# ==============================================================================
# 3. 통합 매핑 테이블
# ==============================================================================

PERSONA_TO_STATE: Dict[str, StateVector] = {
    **S0_PERSONAS,
    **S1_PERSONAS,
    **S2_PERSONAS,
    **S3_PERSONAS,
    **S4_PERSONAS,
    **S5_PERSONAS,
    **C_PERSONAS,
    **Q_PERSONAS,
    **E_PERSONAS,
}

# 시나리오별 그룹핑
SCENARIO_GROUPS = {
    "S0": list(S0_PERSONAS.keys()),
    "S1": list(S1_PERSONAS.keys()),
    "S2": list(S2_PERSONAS.keys()),
    "S3": list(S3_PERSONAS.keys()),
    "S4": list(S4_PERSONAS.keys()),
    "S5": list(S5_PERSONAS.keys()),
    "C": list(C_PERSONAS.keys()),
    "Q": list(Q_PERSONAS.keys()),
    "E": list(E_PERSONAS.keys()),
}


# ==============================================================================
# 4. 유틸리티 함수
# ==============================================================================

def get_state_vector(persona_id: str) -> Optional[StateVector]:
    """페르소나 ID로 StateVector 조회"""
    return PERSONA_TO_STATE.get(persona_id)


def get_scenario_from_persona(persona_id: str) -> Optional[str]:
    """페르소나 ID에서 시나리오 추출"""
    for scenario, personas in SCENARIO_GROUPS.items():
        if persona_id in personas:
            return scenario
    return None


def calculate_similarity(v1: StateVector, v2: StateVector) -> float:
    """
    두 StateVector 간의 코사인 유사도 계산

    Returns: -1.0 ~ 1.0 (1.0이 가장 유사)
    """
    d1 = v1.to_dict()
    d2 = v2.to_dict()

    dot_product = sum(d1[k] * d2[k] for k in d1.keys())
    mag1 = v1.magnitude()
    mag2 = v2.magnitude()

    if mag1 == 0 or mag2 == 0:
        return 0.0

    return dot_product / (mag1 * mag2)


def find_similar_personas(
    target: StateVector,
    top_n: int = 5
) -> List[Tuple[str, float]]:
    """
    주어진 StateVector와 가장 유사한 페르소나 찾기

    Returns: [(persona_id, similarity_score), ...]
    """
    similarities = []
    for pid, state in PERSONA_TO_STATE.items():
        sim = calculate_similarity(target, state)
        similarities.append((pid, sim))

    similarities.sort(key=lambda x: x[1], reverse=True)
    return similarities[:top_n]


def get_dimension_extremes(dimension: str) -> Tuple[str, str]:
    """
    특정 차원에서 최고/최저 페르소나 반환

    Returns: (highest_persona_id, lowest_persona_id)
    """
    scores = []
    for pid, state in PERSONA_TO_STATE.items():
        d = state.to_dict()
        if dimension in d:
            scores.append((pid, d[dimension]))

    scores.sort(key=lambda x: x[1])
    return (scores[-1][0], scores[0][0])


def compute_scenario_centroid(scenario: str) -> Optional[StateVector]:
    """
    시나리오의 중심 StateVector 계산 (평균)
    """
    if scenario not in SCENARIO_GROUPS:
        return None

    personas = SCENARIO_GROUPS[scenario]
    if not personas:
        return None

    # 각 차원의 평균 계산
    sums = {
        "metacognition": 0.0,
        "self_efficacy": 0.0,
        "help_seeking": 0.0,
        "emotional_regulation": 0.0,
        "anxiety": 0.0,
        "confidence": 0.0,
        "engagement": 0.0,
        "motivation": 0.0
    }

    for pid in personas:
        state = PERSONA_TO_STATE[pid]
        d = state.to_dict()
        for k in sums:
            sums[k] += d[k]

    n = len(personas)
    return StateVector(**{k: v/n for k, v in sums.items()})


# ==============================================================================
# 5. 테스트 실행
# ==============================================================================

def run_mapper_test():
    """페르소나 매퍼 테스트"""
    print("=" * 60)
    print("Quantum Persona Mapper - Phase 1 테스트")
    print("=" * 60)
    print()

    # 1. 전체 페르소나 수 확인
    total = len(PERSONA_TO_STATE)
    print(f"📊 [1] 총 페르소나 수: {total}개")
    for scenario, personas in SCENARIO_GROUPS.items():
        print(f"   - {scenario}: {len(personas)}개")
    print()

    # 2. 샘플 StateVector 조회
    print("📋 [2] 샘플 StateVector 조회")
    sample_ids = ["S0_P1", "S1_P2", "E_P1", "C_P5"]
    for pid in sample_ids:
        state = get_state_vector(pid)
        if state:
            print(f"   {pid}:")
            d = state.to_dict()
            for k, v in d.items():
                bar = "█" * int(v * 10) + "░" * (10 - int(v * 10))
                print(f"      {k:20s}: {bar} {v:.2f}")
            print()

    # 3. 차원별 극단값 페르소나
    print("📊 [3] 차원별 극단값 페르소나")
    for dim in ["anxiety", "motivation", "metacognition"]:
        high, low = get_dimension_extremes(dim)
        high_val = get_state_vector(high).to_dict()[dim]
        low_val = get_state_vector(low).to_dict()[dim]
        print(f"   {dim}:")
        print(f"      최고: {high} ({high_val:.2f})")
        print(f"      최저: {low} ({low_val:.2f})")
    print()

    # 4. 유사 페르소나 찾기
    print("🔍 [4] E_P1(수학 불안형)과 유사한 페르소나")
    target = get_state_vector("E_P1")
    similar = find_similar_personas(target, top_n=5)
    for pid, sim in similar:
        print(f"   - {pid}: 유사도 {sim:.4f}")
    print()

    # 5. 시나리오 중심점
    print("📍 [5] 시나리오별 중심 StateVector")
    for scenario in ["S0", "S1", "E"]:
        centroid = compute_scenario_centroid(scenario)
        if centroid:
            d = centroid.to_dict()
            print(f"   {scenario} 중심점:")
            print(f"      anxiety: {d['anxiety']:.2f}, motivation: {d['motivation']:.2f}, confidence: {d['confidence']:.2f}")
    print()

    print("=" * 60)
    print("✅ Phase 1 페르소나 매퍼 테스트 완료")
    print("=" * 60)

    return {
        "total_personas": total,
        "scenarios": list(SCENARIO_GROUPS.keys()),
        "sample_state": get_state_vector("S0_P1").to_dict()
    }


if __name__ == "__main__":
    run_mapper_test()
