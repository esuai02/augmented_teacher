#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Quantum Orchestration - Minimal Working Case
=============================================
Agent03 R1a 룰 기반 최소 동작 케이스

목표: 기존 룰 시스템 → Quantum 신호 변환 → 결과 확인
"""

from __future__ import print_function, unicode_literals
import sys
import io

# UTF-8 인코딩 강제 설정 (서버 환경 호환)
if sys.version_info[0] >= 3:
    # Python 3: stdout을 UTF-8로 재설정
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    elif hasattr(sys.stdout, 'buffer'):
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
else:
    # Python 2: UTF-8 writer 사용
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout)

import math
from typing import Dict, List, Tuple, Optional

# Python 3.6 호환을 위한 dataclass 대체
try:
    from dataclasses import dataclass
except ImportError:
    # Python 3.6 이하: dataclass 없이 동작
    def dataclass(cls):
        return cls

# ==============================================================================
# 1. 데이터 구조 정의
# ==============================================================================

class RuleDefinition:
    """기존 rules.yaml의 룰 정의"""
    def __init__(self, rule_id, priority, confidence, scenario, description):
        self.rule_id = rule_id          # str
        self.priority = priority        # int: 80-99
        self.confidence = confidence    # float: 0.0-1.0
        self.scenario = scenario        # str: S0, S1, S2, ...
        self.description = description  # str

class AgentSignal:
    """Quantum 신호 (룰에서 변환됨)"""
    def __init__(self, agent_id, rule_id, amplitude, phase, confidence):
        self.agent_id = agent_id        # int
        self.rule_id = rule_id          # str
        self.amplitude = amplitude      # float: 0.0-1.0 (priority * confidence에서 계산)
        self.phase = phase              # float: 라디안 (시나리오에서 계산)
        self.confidence = confidence    # float

class StudentContext:
    """학생 상태 컨텍스트 (간소화)"""
    def __init__(self, weekly_completion_rate, quarterly_goal_id=None, weekly_goal_id=None):
        self.weekly_completion_rate = weekly_completion_rate  # float: 0-100
        self.quarterly_goal_id = quarterly_goal_id            # Optional[str]
        self.weekly_goal_id = weekly_goal_id                  # Optional[str]

# ==============================================================================
# 2. 시나리오 → Phase 매핑
# ==============================================================================

SCENARIO_PHASE_MAP = {
    "S0": 0.0,              # 정보 수집 단계
    "S1": math.pi / 4,      # 목표-계획 불일치 (45도)
    "S2": math.pi / 2,      # 시간 부족 딜레마 (90도)
    "S3": 3 * math.pi / 4,  # 회복탄력성 (135도)
    "S4": math.pi,          # 커리큘럼 정합성 (180도)
}

# ==============================================================================
# 3. 핵심 변환 함수
# ==============================================================================

def rule_to_signal(rule: RuleDefinition, agent_id: int) -> AgentSignal:
    """
    기존 룰의 priority/confidence → Quantum amplitude/phase

    공식:
    - amplitude = confidence × √(priority / 100)
    - phase = SCENARIO_PHASE_MAP[scenario]
    """
    # amplitude 계산: confidence와 priority 결합
    normalized_priority = rule.priority / 100.0  # 0.95 for priority=95
    amplitude = rule.confidence * math.sqrt(normalized_priority)

    # phase 계산: 시나리오에서 결정
    phase = SCENARIO_PHASE_MAP.get(rule.scenario, 0.0)

    return AgentSignal(
        agent_id=agent_id,
        rule_id=rule.rule_id,
        amplitude=amplitude,
        phase=phase,
        confidence=rule.confidence
    )

def check_conditions(context: StudentContext, rule_id: str) -> bool:
    """
    룰 조건 체크 (R1a 전용 간소화 버전)

    R1a 조건:
    - weekly_completion_rate < 70
    - quarterly_goal_id != null
    - weekly_goal_id != null
    """
    if rule_id == "R1a_weekly_completion_rate_analysis":
        return (
            context.weekly_completion_rate < 70 and
            context.quarterly_goal_id is not None and
            context.weekly_goal_id is not None
        )
    return False

def calculate_interference(signals: List[AgentSignal]) -> Tuple[float, Dict]:
    """
    간섭 계산 (간소화)

    복소수 합: Σ (amplitude × e^(i×phase))
    """
    if not signals:
        return 0.0, {}

    real_sum = 0.0
    imag_sum = 0.0
    details = {}

    for signal in signals:
        # 복소수 변환
        real_part = signal.amplitude * math.cos(signal.phase)
        imag_part = signal.amplitude * math.sin(signal.phase)

        real_sum += real_part
        imag_sum += imag_part

        details[signal.rule_id] = {
            "amplitude": signal.amplitude,
            "phase_deg": math.degrees(signal.phase),
            "real": real_part,
            "imag": imag_part
        }

    # 총 amplitude (복소수 크기)
    total_amplitude = math.sqrt(real_sum**2 + imag_sum**2)

    return total_amplitude, details

# ==============================================================================
# 4. Agent03 R1a 룰 정의
# ==============================================================================

AGENT03_R1A = RuleDefinition(
    rule_id="R1a_weekly_completion_rate_analysis",
    priority=95,
    confidence=0.92,
    scenario="S1",  # 목표-계획 불일치
    description="주간 목표 달성률 저하 시 분기목표와 주간목표 불일치 진단"
)

# ==============================================================================
# 5. 테스트 실행
# ==============================================================================

def run_minimal_test():
    """최소 동작 테스트 실행"""
    print("=" * 60)
    print("Quantum Orchestration - Minimal Working Case")
    print("Agent03 R1a 룰 기반 테스트")
    print("=" * 60)
    print()

    # 1. 테스트 컨텍스트 생성 (R1a 트리거되는 상황)
    context = StudentContext(
        weekly_completion_rate=55.0,  # 70% 미만 → 트리거
        quarterly_goal_id="Q2025_MATH_TOP10",
        weekly_goal_id="W202512_MATH_REVIEW"
    )

    print("📊 [Step 1] 학생 컨텍스트")
    print(f"   - 주간 달성률: {context.weekly_completion_rate}%")
    print(f"   - 분기 목표 ID: {context.quarterly_goal_id}")
    print(f"   - 주간 목표 ID: {context.weekly_goal_id}")
    print()

    # 2. 조건 체크
    triggered = check_conditions(context, AGENT03_R1A.rule_id)
    print(f"📋 [Step 2] 조건 체크: {'✅ 트리거됨' if triggered else '❌ 트리거 안됨'}")
    print(f"   - weekly_completion_rate < 70: {context.weekly_completion_rate < 70}")
    print(f"   - quarterly_goal_id != null: {context.quarterly_goal_id is not None}")
    print(f"   - weekly_goal_id != null: {context.weekly_goal_id is not None}")
    print()

    if not triggered:
        print("❌ 룰이 트리거되지 않음. 테스트 종료.")
        return

    # 3. Quantum 신호 변환
    signal = rule_to_signal(AGENT03_R1A, agent_id=3)
    print("⚡ [Step 3] Quantum 신호 변환")
    print(f"   Rule: {AGENT03_R1A.rule_id}")
    print(f"   - priority: {AGENT03_R1A.priority}")
    print(f"   - confidence: {AGENT03_R1A.confidence}")
    print(f"   - scenario: {AGENT03_R1A.scenario}")
    print()
    print(f"   ↓ 변환 공식: amplitude = confidence × √(priority/100)")
    print(f"   ↓           phase = SCENARIO_PHASE_MAP['{AGENT03_R1A.scenario}']")
    print()
    print(f"   Signal:")
    print(f"   - amplitude: {signal.amplitude:.4f}")
    print(f"   - phase: {signal.phase:.4f} rad ({math.degrees(signal.phase):.1f}°)")
    print()

    # 4. 간섭 계산 (단일 신호)
    total_amp, details = calculate_interference([signal])
    print("🌊 [Step 4] 간섭 계산 (단일 신호)")
    print(f"   - 총 amplitude: {total_amp:.4f}")
    print(f"   - 상세:")
    for rule_id, d in details.items():
        print(f"     - {rule_id}:")
        print(f"       amplitude={d['amplitude']:.4f}, phase={d['phase_deg']:.1f}°")
        print(f"       real={d['real']:.4f}, imag={d['imag']:.4f}")
    print()

    # 5. 결과 해석
    print("📊 [Step 5] 결과 해석")
    if total_amp > 0.8:
        priority_level = "🔴 높음 (즉시 개입 권장)"
    elif total_amp > 0.6:
        priority_level = "🟡 중간 (주의 필요)"
    else:
        priority_level = "🟢 낮음"

    print(f"   - Quantum Priority Score: {total_amp:.4f}")
    print(f"   - 개입 수준: {priority_level}")
    print()

    # 6. 액션 추천
    print("🎯 [Step 6] 추천 액션")
    print(f"   - analyze: 'goal_plan_mismatch_diagnosis'")
    print(f"   - message: '분기 목표와 주간 목표가 어디서 엇나갔는지 분석 중입니다.'")
    print()

    print("=" * 60)
    print("✅ 최소 동작 테스트 완료")
    print("=" * 60)

    return {
        "context": context,
        "rule": AGENT03_R1A,
        "signal": signal,
        "total_amplitude": total_amp,
        "triggered": triggered
    }

# ==============================================================================
# 6. 추가 테스트: 트리거 안되는 케이스
# ==============================================================================

def run_non_trigger_test():
    """트리거 안되는 케이스 테스트"""
    print()
    print("=" * 60)
    print("🔍 추가 테스트: 트리거 안되는 케이스")
    print("=" * 60)

    # 달성률 75% → 트리거 안됨
    context = StudentContext(
        weekly_completion_rate=75.0,  # 70% 이상 → 트리거 안됨
        quarterly_goal_id="Q2025_MATH_TOP10",
        weekly_goal_id="W202512_MATH_REVIEW"
    )

    triggered = check_conditions(context, AGENT03_R1A.rule_id)
    print(f"   - 주간 달성률: {context.weekly_completion_rate}%")
    print(f"   - 조건 체크: {'✅ 트리거됨' if triggered else '❌ 트리거 안됨'}")
    print(f"   - 이유: weekly_completion_rate({context.weekly_completion_rate}) >= 70")

# ==============================================================================
# Main
# ==============================================================================

# ==============================================================================
# 7. 다중 에이전트 간섭 테스트
# ==============================================================================

# Agent01~04에서 동시 트리거 가능한 룰 정의
MULTI_AGENT_RULES = {
    1: RuleDefinition(
        rule_id="S0_R1_math_learning_style_collection",
        priority=99,
        confidence=0.97,
        scenario="S0",  # 정보 수집
        description="수학 학습 스타일 분류 수집"
    ),
    2: RuleDefinition(
        rule_id="S0_R1_student_grade_collection",
        priority=99,
        confidence=0.98,
        scenario="S0",  # 정보 수집
        description="학생의 학년 정보 수집"
    ),
    3: RuleDefinition(
        rule_id="R1a_weekly_completion_rate_analysis",
        priority=95,
        confidence=0.92,
        scenario="S1",  # 목표-계획 불일치
        description="주간 목표 달성률 저하 시 불일치 진단"
    ),
    4: RuleDefinition(
        rule_id="CU_A1_weak_point_detection",
        priority=95,
        confidence=0.92,
        scenario="S1",  # 동일 시나리오 (보강 효과)
        description="개념이해 단계별 취약구간 탐지"
    ),
}

def run_multi_agent_test():
    """다중 에이전트 간섭 테스트"""
    print()
    print("=" * 60)
    print("🌊 다중 에이전트 간섭 테스트")
    print("=" * 60)
    print()

    # 케이스 1: Agent03 + Agent04 동시 트리거 (같은 시나리오 S1)
    print("📊 [케이스 1] Agent03 + Agent04 (같은 시나리오 S1)")
    print("   → 보강 간섭 (Constructive Interference) 예상")
    print()

    signals_case1 = [
        rule_to_signal(MULTI_AGENT_RULES[3], 3),
        rule_to_signal(MULTI_AGENT_RULES[4], 4),
    ]

    total1, details1 = calculate_interference(signals_case1)
    print(f"   Signals:")
    for s in signals_case1:
        print(f"   - Agent{s.agent_id}: amplitude={s.amplitude:.4f}, phase={math.degrees(s.phase):.1f}°")
    print()
    print(f"   총 Amplitude: {total1:.4f}")
    print(f"   (개별 합: {sum(s.amplitude for s in signals_case1):.4f})")
    if total1 >= sum(s.amplitude for s in signals_case1) * 0.9:
        print(f"   → ✅ 보강 간섭 확인! (같은 방향으로 정렬)")
    print()

    # 케이스 2: Agent01 + Agent03 동시 트리거 (다른 시나리오 S0 vs S1)
    print("📊 [케이스 2] Agent01 + Agent03 (다른 시나리오 S0 vs S1)")
    print("   → 부분 간섭 예상 (위상 차이 45°)")
    print()

    signals_case2 = [
        rule_to_signal(MULTI_AGENT_RULES[1], 1),
        rule_to_signal(MULTI_AGENT_RULES[3], 3),
    ]

    total2, details2 = calculate_interference(signals_case2)
    print(f"   Signals:")
    for s in signals_case2:
        print(f"   - Agent{s.agent_id}: amplitude={s.amplitude:.4f}, phase={math.degrees(s.phase):.1f}°")
    print()
    print(f"   총 Amplitude: {total2:.4f}")
    print(f"   (개별 합: {sum(s.amplitude for s in signals_case2):.4f})")
    individual_sum2 = sum(s.amplitude for s in signals_case2)
    print(f"   → 간섭 효율: {total2/individual_sum2*100:.1f}% (위상 차이로 인한 손실)")
    print()

    # 케이스 3: 4개 에이전트 모두 트리거
    print("📊 [케이스 3] Agent01~04 모두 트리거")
    print()

    all_signals = [rule_to_signal(r, aid) for aid, r in MULTI_AGENT_RULES.items()]
    total3, details3 = calculate_interference(all_signals)

    print(f"   Signals:")
    for s in all_signals:
        print(f"   - Agent{s.agent_id}: amp={s.amplitude:.4f}, phase={math.degrees(s.phase):.1f}°, scenario={MULTI_AGENT_RULES[s.agent_id].scenario}")
    print()
    print(f"   총 Amplitude: {total3:.4f}")
    individual_sum3 = sum(s.amplitude for s in all_signals)
    print(f"   (개별 합: {individual_sum3:.4f})")
    print(f"   → 간섭 효율: {total3/individual_sum3*100:.1f}%")
    print()

    # 결과 해석
    print("📋 [결과 해석]")
    print(f"   - 케이스 1 (같은 S1): {total1:.4f} → 효율 ~100% (보강 간섭)")
    print(f"   - 케이스 2 (S0+S1):   {total2:.4f} → 효율 ~93% (45° 차이)")
    print(f"   - 케이스 3 (혼합):     {total3:.4f} → 전체 시스템 신호 강도")
    print()

    # 에이전트 우선순위 추천
    print("🎯 [에이전트 활성화 순서 추천]")
    sorted_signals = sorted(all_signals, key=lambda s: s.amplitude, reverse=True)
    for i, s in enumerate(sorted_signals, 1):
        print(f"   {i}. Agent{s.agent_id}: {s.amplitude:.4f} ({MULTI_AGENT_RULES[s.agent_id].description[:30]}...)")

    print()
    print("=" * 60)
    print("✅ 다중 에이전트 간섭 테스트 완료")
    print("=" * 60)

if __name__ == "__main__":
    result = run_minimal_test()
    run_non_trigger_test()
    run_multi_agent_test()
