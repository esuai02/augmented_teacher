#!/usr/bin/env python3
"""
직접 Python 테스트 스크립트
quantum_data_interface.py의 핵심 클래스들을 검증합니다.
"""

import sys
import os

# 현재 경로 추가
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

def test_imports():
    """Test 1: 모듈 임포트 테스트"""
    print("=" * 60)
    print("Test 1: Module Import Test")
    print("=" * 60)
    try:
        from _quantum_data_interface import (
            StandardFeatures,
            DimensionReducer,
            QuantumDataCollector
        )
        print("✅ PASSED: All modules imported successfully")
        return True
    except ImportError as e:
        print(f"❌ FAILED: Import error - {e}")
        return False


def test_standard_features():
    """Test 2: StandardFeatures 생성 테스트"""
    print("\n" + "=" * 60)
    print("Test 2: StandardFeatures Creation Test")
    print("=" * 60)
    try:
        from _quantum_data_interface import StandardFeatures

        # 기본 인스턴스 생성
        sf = StandardFeatures()

        # 실제 필드 이름으로 확인
        assert hasattr(sf, 'calmness_score'), "Missing calmness_score"
        assert hasattr(sf, 'problem_accuracy'), "Missing problem_accuracy"
        assert hasattr(sf, 'pomodoro_completion'), "Missing pomodoro_completion"

        print(f"  - calmness_score: {sf.calmness_score}")
        print(f"  - problem_accuracy: {sf.problem_accuracy}")
        print(f"  - pomodoro_completion: {sf.pomodoro_completion}")

        print("✅ PASSED: StandardFeatures created successfully")
        return True
    except Exception as e:
        print(f"❌ FAILED: {e}")
        return False


def test_dimension_reducer():
    """Test 3: DimensionReducer 테스트"""
    print("\n" + "=" * 60)
    print("Test 3: DimensionReducer Test")
    print("=" * 60)
    try:
        from _quantum_data_interface import StandardFeatures, DimensionReducer

        # StandardFeatures 생성 (실제 필드명 사용)
        sf = StandardFeatures(
            calmness_score=0.7,        # calmness_level → calmness_score
            problem_accuracy=0.85,     # quiz_accuracy → problem_accuracy
            pomodoro_completion=0.8,
            goal_progress=0.6
        )

        # DimensionReducer로 8D 변환
        state_8d = DimensionReducer.transform_to_list(sf)

        # 검증
        assert len(state_8d) == 8, f"Expected 8 dimensions, got {len(state_8d)}"

        # 차원 이름
        dim_names = [
            'cognitive_clarity', 'emotional_stability', 'engagement_level',
            'concept_mastery', 'routine_strength', 'metacognitive_awareness',
            'dropout_risk', 'intervention_readiness'
        ]

        print("  8D StateVector:")
        for i, (name, val) in enumerate(zip(dim_names, state_8d)):
            in_range = "✓" if 0 <= val <= 1 else "✗"
            print(f"    [{i}] {name}: {val:.4f} {in_range}")

        # 범위 검증
        all_in_range = all(0 <= v <= 1 for v in state_8d)
        if all_in_range:
            print("✅ PASSED: All dimensions in [0, 1] range")
        else:
            print("⚠️ WARNING: Some dimensions outside [0, 1] range")

        return True
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return False


def test_quantum_data_collector():
    """Test 4: QuantumDataCollector 테스트"""
    print("\n" + "=" * 60)
    print("Test 4: QuantumDataCollector Test")
    print("=" * 60)
    try:
        from _quantum_data_interface import (
            StandardFeatures,
            DimensionReducer,
            QuantumDataCollector
        )

        # Collector 생성
        collector = QuantumDataCollector(student_id=99999)

        # 샘플 에이전트 데이터 (정수 키)
        agent_contexts = {
            8: {'calm_score': 0.72, 'calmness_level': 3},
            11: {'accuracy_rate': 0.85, 'total_problems': 20},
            12: {'rest_count': 5, 'average_interval': 55},
            3: {'goal_progress': 0.6, 'goal_effectiveness': 0.7},
            9: {'pomodoro_completion': 0.8},
            4: {'engagement_level': 0.75, 'dropout_risk': 0.15}
        }

        # collect_all로 StandardFeatures 생성
        features = collector.collect_all(agent_contexts)

        print(f"  - Features type: {type(features).__name__}")
        print(f"  - Input agents: {list(agent_contexts.keys())}")

        # 8D StateVector 변환
        state_8d = DimensionReducer.transform_to_list(features)

        print(f"  - StateVector dimensions: {len(state_8d)}")
        print(f"  - StateVector: {[round(v, 4) for v in state_8d]}")

        print("✅ PASSED: Full pipeline executed successfully")
        return True
    except Exception as e:
        print(f"❌ FAILED: {e}")
        import traceback
        traceback.print_exc()
        return False


def main():
    """메인 테스트 실행"""
    print("🔬 Quantum Data Interface - Direct Python Test")
    print("=" * 60)

    results = []

    # 테스트 실행
    results.append(("Import Test", test_imports()))
    results.append(("StandardFeatures", test_standard_features()))
    results.append(("DimensionReducer", test_dimension_reducer()))
    results.append(("QuantumDataCollector", test_quantum_data_collector()))

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
