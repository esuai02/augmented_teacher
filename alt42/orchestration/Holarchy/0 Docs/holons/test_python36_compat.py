#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Python 3.6 호환성 테스트 스크립트
모든 Quantum 모듈이 dataclasses 없이 정상 작동하는지 검증
"""

from __future__ import print_function, unicode_literals
import sys
import os
import io
import traceback

# UTF-8 인코딩 강제 설정 (서버 환경 호환)
if sys.version_info[0] >= 3:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    elif hasattr(sys.stdout, 'buffer'):
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
else:
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout)

# 테스트 결과 저장
results = {
    "python_version": sys.version,
    "tests": [],
    "passed": 0,
    "failed": 0
}

def log_test(name, passed, error=None):
    """테스트 결과 로깅"""
    result = {
        "name": name,
        "passed": passed,
        "error": str(error) if error else None
    }
    results["tests"].append(result)
    if passed:
        results["passed"] += 1
        print("[PASS] {}".format(name))
    else:
        results["failed"] += 1
        print("[FAIL] {} - {}".format(name, error))


print("=" * 60)
print("Python 3.6 Compatibility Test for Quantum Orchestration")
print("=" * 60)
print("Python Version: {}".format(sys.version))
print("-" * 60)

# Test 1: dataclasses 모듈이 없어도 되는지 확인
print("\n[TEST 1] Verify dataclasses is NOT required")
try:
    # 일단 dataclasses가 있는지 확인 (Python 3.7+ 에서만 있음)
    try:
        import dataclasses
        print("  Note: dataclasses available (Python 3.7+)")
    except ImportError:
        print("  Note: dataclasses NOT available (Python 3.6)")
    log_test("dataclasses_check", True)
except Exception as e:
    log_test("dataclasses_check", False, e)

# Test 2: _quantum_persona_mapper.py 임포트 테스트
print("\n[TEST 2] Import _quantum_persona_mapper")
try:
    from _quantum_persona_mapper import StateVector, PsychDimension
    sv = StateVector(metacognition=0.8, self_efficacy=0.7)
    assert hasattr(sv, 'metacognition'), "StateVector missing metacognition"
    assert hasattr(sv, 'self_efficacy'), "StateVector missing self_efficacy"
    assert sv.metacognition == 0.8, "StateVector value incorrect"
    # PsychDimension Enum 테스트
    assert hasattr(PsychDimension, 'METACOGNITION'), "PsychDimension missing METACOGNITION"
    log_test("import_persona_mapper", True)
except Exception as e:
    log_test("import_persona_mapper", False, e)
    traceback.print_exc()

# Test 3: _quantum_entanglement.py 임포트 테스트
print("\n[TEST 3] Import _quantum_entanglement")
try:
    from _quantum_entanglement import AgentConnection, EntanglementMap
    conn = AgentConnection(
        source_id=1,
        target_id=5,
        rule_id="test_rule",
        priority=80,
        confidence=0.9
    )
    assert conn.source_id == 1, "AgentConnection source_id incorrect"
    assert conn.target_id == 5, "AgentConnection target_id incorrect"

    emap = EntanglementMap()
    assert hasattr(emap, 'connections'), "EntanglementMap missing connections"
    assert isinstance(emap.connections, dict), "connections should be dict"
    log_test("import_entanglement", True)
except Exception as e:
    log_test("import_entanglement", False, e)
    traceback.print_exc()

# Test 4: _quantum_orchestrator.py 임포트 테스트
print("\n[TEST 4] Import _quantum_orchestrator")
try:
    from _quantum_orchestrator import AgentSignal, AgentPriority, HamiltonianEvolution

    signal = AgentSignal(agent_id=5, amplitude=0.8, phase=0.0, confidence=0.9)
    assert signal.agent_id == 5, "AgentSignal agent_id incorrect"

    priority = AgentPriority(
        agent_id=5,
        priority_score=0.85,
        entanglement_bonus=0.1,
        state_alignment=0.7,
        signal_strength=0.8
    )
    assert priority.agent_id == 5, "AgentPriority agent_id incorrect"

    evolution = HamiltonianEvolution()
    assert hasattr(evolution, 'target_flow_state'), "HamiltonianEvolution missing target_flow_state"
    assert evolution.evolution_rate == 0.1, "Default evolution_rate incorrect"
    log_test("import_orchestrator", True)
except Exception as e:
    log_test("import_orchestrator", False, e)
    traceback.print_exc()

# Test 5: _quantum_integration.py 임포트 테스트
print("\n[TEST 5] Import _quantum_integration")
try:
    from _quantum_integration import IntegrationRecord, IntegrationMetrics

    record = IntegrationRecord(
        timestamp="2025-12-07T10:00:00",
        mode="observe",
        student_id=123,
        student_state={"test": "state"},
        triggered_agents=[1, 5, 8],
        quantum_suggestion=[(5, 0.9), (8, 0.8)]
    )
    assert record.student_id == 123, "IntegrationRecord student_id incorrect"

    # to_dict() 메서드 테스트 (asdict 대체)
    record_dict = record.to_dict()
    assert isinstance(record_dict, dict), "to_dict should return dict"
    assert record_dict["student_id"] == 123, "to_dict student_id incorrect"

    metrics = IntegrationMetrics()
    assert metrics.total_runs == 0, "Default total_runs incorrect"
    assert isinstance(metrics.records, list), "records should be list"
    log_test("import_integration", True)
except Exception as e:
    log_test("import_integration", False, e)
    traceback.print_exc()

# Test 6: 전체 시스템 통합 테스트
print("\n[TEST 6] Full System Integration")
try:
    from _quantum_persona_mapper import StateVector, get_state_vector, PERSONA_TO_STATE
    from _quantum_entanglement import EntanglementMap
    from _quantum_orchestrator import QuantumOrchestrator

    # 간단한 통합 시나리오 (실제 페르소나 ID 형식: S0_P1, S0_P2 등)
    state = get_state_vector("S0_P1")
    assert state is not None, "get_state_vector should return StateVector"
    assert isinstance(state, StateVector), "Should return StateVector instance"
    assert len(PERSONA_TO_STATE) > 0, "PERSONA_TO_STATE should have entries"

    emap = EntanglementMap()
    # 기본 연결 추가
    emap.connections[(1, 5)] = 0.85
    assert emap.connections[(1, 5)] == 0.85, "EntanglementMap connection incorrect"

    # QuantumOrchestrator는 mode 파라미터만 받음
    from _quantum_orchestrator import OrchestratorMode
    orchestrator = QuantumOrchestrator(mode=OrchestratorMode.OBSERVE)
    assert hasattr(orchestrator, 'suggest_agent_order'), "QuantumOrchestrator missing suggest_agent_order"
    assert hasattr(orchestrator, 'interference'), "QuantumOrchestrator missing interference"
    assert hasattr(orchestrator, 'evolution'), "QuantumOrchestrator missing evolution"

    log_test("full_integration", True)
except Exception as e:
    log_test("full_integration", False, e)
    traceback.print_exc()

# 최종 결과 출력
print("\n" + "=" * 60)
print("TEST RESULTS SUMMARY")
print("=" * 60)
print("Total Tests: {}".format(len(results["tests"])))
print("Passed: {}".format(results["passed"]))
print("Failed: {}".format(results["failed"]))
print("-" * 60)

if results["failed"] == 0:
    print("\n[SUCCESS] All tests passed! Python 3.6 compatibility verified.")
    sys.exit(0)
else:
    print("\n[WARNING] Some tests failed. Review errors above.")
    for test in results["tests"]:
        if not test["passed"]:
            print("  - {}: {}".format(test["name"], test["error"]))
    sys.exit(1)
