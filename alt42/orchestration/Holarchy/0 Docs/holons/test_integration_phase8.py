#!/usr/bin/env python3
"""
Phase 8 Integration Test - Quantum Orchestrator + Data Interface
테스트: 8D StateVector 생성 → 에이전트 순서 제안 파이프라인
"""

import sys
import os

# 현재 경로 추가
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def test_phase7_data_interface():
    """Test 1: Phase 7 Data Interface 검증"""
    print("=" * 60)
    print("Test 1: Phase 7 Data Interface")
    print("=" * 60)
    try:
        from _quantum_data_interface import (
            StandardFeatures,
            DimensionReducer,
            QuantumDataCollector
        )

        # 에이전트 데이터 시뮬레이션
        agent_contexts = {
            8: {'calm_score': 0.72, 'calmness_level': 3},
            11: {'accuracy_rate': 0.85, 'total_problems': 20},
            12: {'rest_count': 5, 'average_interval': 55},
            3: {'goal_progress': 0.6, 'goal_effectiveness': 0.7},
            9: {'pomodoro_completion': 0.8},
            4: {'engagement_level': 0.75, 'dropout_risk': 0.15}
        }

        collector = QuantumDataCollector(student_id=99999)
        features = collector.collect_all(agent_contexts)
        state_8d = DimensionReducer.transform_to_list(features)

        print(f"  Input agents: {list(agent_contexts.keys())}")
        print(f"  Output 8D vector: {[round(v, 4) for v in state_8d]}")
        print("✅ PASSED: Phase 7 Data Interface")
        return state_8d
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return None


def test_new8d_state_vector(state_8d_list):
    """Test 2: New8DStateVector 생성 검증"""
    print("\n" + "=" * 60)
    print("Test 2: New8DStateVector Creation")
    print("=" * 60)
    try:
        from _quantum_orchestrator import New8DStateVector

        # 방법 1: 개별 파라미터로 생성
        state1 = New8DStateVector(
            cognitive_clarity=0.9,
            emotional_stability=0.7,
            engagement_level=0.8,
            concept_mastery=0.6,
            routine_strength=0.5,
            metacognitive_awareness=0.7,
            dropout_risk=0.2,
            intervention_readiness=0.8
        )

        # 방법 2: 리스트에서 생성
        state2 = New8DStateVector.from_list(state_8d_list)

        print(f"  State1 (manual): {state1.to_list()[:4]}... (8D)")
        print(f"  State2 (from_list): {state2.to_list()[:4]}... (8D)")
        print("✅ PASSED: New8DStateVector creation")
        return state2
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return None


def test_quantum_orchestrator():
    """Test 3: QuantumOrchestrator 초기화 검증"""
    print("\n" + "=" * 60)
    print("Test 3: QuantumOrchestrator Initialization")
    print("=" * 60)
    try:
        from _quantum_orchestrator import QuantumOrchestrator, OrchestratorMode

        # 기본 모드로 생성
        orchestrator = QuantumOrchestrator(mode=OrchestratorMode.SUGGEST)

        print(f"  Mode: {orchestrator.mode}")
        print(f"  Has interference: {orchestrator.interference is not None}")
        print(f"  Has evolution: {orchestrator.evolution is not None}")
        print("✅ PASSED: QuantumOrchestrator initialization")
        return orchestrator
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return None


def test_suggest_agent_order(orchestrator, state_8d):
    """Test 4: suggest_agent_order_from_new8d 검증"""
    print("\n" + "=" * 60)
    print("Test 4: Agent Order Suggestion (New 8D)")
    print("=" * 60)
    try:
        # 트리거된 에이전트 목록 (가정)
        triggered_agents = [3, 8, 11, 4]  # Goal, Calmness, Quiz, Engagement

        # 에이전트 순서 제안
        ordered = orchestrator.suggest_agent_order_from_new8d(
            student_state=state_8d,
            triggered_agents=triggered_agents,
            agent_priorities={3: 90, 8: 85, 11: 80, 4: 75},
            agent_confidences={3: 0.9, 8: 0.85, 11: 0.8, 4: 0.75}
        )

        print(f"  Triggered agents: {triggered_agents}")
        print(f"  Suggested order:")
        for i, agent in enumerate(ordered[:5]):  # 상위 5개만 표시
            print(f"    [{i+1}] Agent {agent.agent_id}: priority={agent.priority_score:.2f}, "
                  f"alignment={agent.state_alignment:.2f}")

        print("✅ PASSED: Agent order suggestion")
        return ordered
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return None


def test_full_pipeline():
    """Test 5: Full Pipeline (에이전트 데이터 → 8D → 순서 제안)"""
    print("\n" + "=" * 60)
    print("Test 5: Full Pipeline Integration")
    print("=" * 60)
    try:
        from _quantum_orchestrator import (
            QuantumOrchestrator,
            OrchestratorMode,
            New8DStateVector
        )

        # 에이전트 데이터
        agent_contexts = {
            8: {'calm_score': 0.72},       # Calmness
            11: {'accuracy_rate': 0.85},   # Quiz
            3: {'goal_progress': 0.6},     # Goal
            9: {'pomodoro_completion': 0.8}, # Pomodoro
            4: {'engagement_level': 0.75}  # Engagement
        }

        # from_agent_data로 직접 8D StateVector 생성
        state_8d = New8DStateVector.from_agent_data(
            student_id=12345,
            agent_contexts=agent_contexts
        )

        # Orchestrator로 순서 제안
        orchestrator = QuantumOrchestrator(mode=OrchestratorMode.SUGGEST)

        triggered = list(agent_contexts.keys())
        ordered = orchestrator.suggest_agent_order_from_new8d(
            student_state=state_8d,
            triggered_agents=triggered
        )

        print(f"  Input: {len(agent_contexts)} agents")
        print(f"  8D StateVector: {[round(v, 3) for v in state_8d.to_list()]}")
        print(f"  Output order: {[a.agent_id for a in ordered[:5]]}")
        print("✅ PASSED: Full pipeline integration")
        return True
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return False


def main():
    """메인 테스트 실행"""
    print("🔬 Phase 8 Integration Test - Quantum Orchestrator")
    print("=" * 60)

    results = []

    # Test 1: Data Interface
    state_8d_list = test_phase7_data_interface()
    results.append(("Phase 7 Data Interface", state_8d_list is not None))

    if state_8d_list is None:
        state_8d_list = [0.5] * 8  # 폴백 값

    # Test 2: New8DStateVector
    state_8d = test_new8d_state_vector(state_8d_list)
    results.append(("New8DStateVector", state_8d is not None))

    # Test 3: Orchestrator
    orchestrator = test_quantum_orchestrator()
    results.append(("QuantumOrchestrator", orchestrator is not None))

    # Test 4: Agent Order
    if orchestrator and state_8d:
        ordered = test_suggest_agent_order(orchestrator, state_8d)
        results.append(("Agent Order Suggestion", ordered is not None))
    else:
        results.append(("Agent Order Suggestion", False))

    # Test 5: Full Pipeline
    full_result = test_full_pipeline()
    results.append(("Full Pipeline", full_result))

    # 결과 요약
    print("\n" + "=" * 60)
    print("📊 Test Summary")
    print("=" * 60)

    passed = sum(1 for _, r in results if r)
    total = len(results)

    for name, result in results:
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"  {status}: {name}")

    print(f"\n  Total: {passed}/{total} tests passed")
    print("=" * 60)

    return passed == total


if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
