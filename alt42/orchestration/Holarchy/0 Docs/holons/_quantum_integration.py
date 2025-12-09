#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Quantum Orchestration - Phase 4: Integration Layer
===================================================
기존 룰 시스템과 Quantum 레이어 연결

목표: 기존 시스템에 영향 없이 Quantum 제안을 관찰/비교
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

import json
import logging
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Tuple, Any
from enum import Enum

# Python 3.6 호환: dataclasses 대신 일반 클래스 사용

# Phase 1-3 모듈 임포트
from _quantum_persona_mapper import (
    StateVector,
    PERSONA_TO_STATE,
    get_state_vector,
    calculate_similarity
)
from _quantum_entanglement import (
    ENTANGLEMENT_MAP,
    get_agent_name,
    get_agent_id,
    get_correlation,
    get_entangled_agents
)
from _quantum_orchestrator import (
    QuantumOrchestrator,
    OrchestratorMode,
    AgentPriority
)

# ==============================================================================
# 1. 로깅 설정
# ==============================================================================

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(name)s: %(message)s'
)
logger = logging.getLogger("quantum.integration")

# ==============================================================================
# 2. 통합 기록 데이터 구조
# ==============================================================================

class IntegrationRecord:
    """통합 실행 기록 (Python 3.6 호환)"""

    def __init__(
        self,
        timestamp,
        mode,
        student_id,
        student_state,
        triggered_agents,
        quantum_suggestion,
        actual_order=None,
        actual_outcome=None,
        comparison=None
    ):
        # type: (str, str, str, Dict[str, float], List[int], List[Dict], Optional[List[int]], Optional[float], Optional[Dict]) -> None
        self.timestamp = timestamp
        self.mode = mode
        self.student_id = student_id
        self.student_state = student_state
        self.triggered_agents = triggered_agents
        self.quantum_suggestion = quantum_suggestion
        self.actual_order = actual_order
        self.actual_outcome = actual_outcome
        self.comparison = comparison

    def to_dict(self):
        # type: () -> dict
        return {
            "timestamp": self.timestamp,
            "mode": self.mode,
            "student_id": self.student_id,
            "student_state": self.student_state,
            "triggered_agents": self.triggered_agents,
            "quantum_suggestion": self.quantum_suggestion,
            "actual_order": self.actual_order,
            "actual_outcome": self.actual_outcome,
            "comparison": self.comparison
        }


class IntegrationMetrics:
    """통합 성능 메트릭 (Python 3.6 호환)"""

    def __init__(
        self,
        total_runs=0,
        observe_runs=0,
        compare_runs=0,
        avg_similarity=0.0,
        avg_outcome_score=0.0,
        suggestion_accuracy=0.0,
        records=None
    ):
        # type: (int, int, int, float, float, float, Optional[List[IntegrationRecord]]) -> None
        self.total_runs = total_runs
        self.observe_runs = observe_runs
        self.compare_runs = compare_runs
        self.avg_similarity = avg_similarity
        self.avg_outcome_score = avg_outcome_score
        self.suggestion_accuracy = suggestion_accuracy
        self.records = records if records is not None else []

# ==============================================================================
# 3. Quantum Integration 클래스
# ==============================================================================

class QuantumIntegration:
    """
    기존 시스템 - Quantum 레이어 통합 인터페이스

    사용 예시:
    ```python
    # 초기화
    qi = QuantumIntegration(mode="observe")

    # 학생 상태 생성 (기존 시스템에서 추출)
    student_state = qi.create_state_from_context({
        "weekly_completion_rate": 55,
        "emotion_score": 0.3,
        "anxiety_level": "high"
    })

    # Quantum 제안 받기 (기존 시스템에 영향 없음)
    suggestion = qi.get_suggestion(
        student_id="user123",
        student_state=student_state,
        triggered_agents=[5, 8, 10, 12]
    )

    # 관찰 모드: 로그만 기록
    # 비교 모드: 실제 결과와 비교
    ```
    """

    def __init__(
        self,
        mode: str = "observe",
        log_path: Optional[str] = None
    ):
        """
        Args:
            mode: "observe" (관찰만) 또는 "compare" (비교 분석)
            log_path: 로그 파일 경로 (기본: ./quantum_logs/)
        """
        self.mode = OrchestratorMode(mode) if mode != "observe" else OrchestratorMode.OBSERVE
        self.orchestrator = QuantumOrchestrator(mode=self.mode)
        self.metrics = IntegrationMetrics()

        # 로그 경로 설정
        if log_path:
            self.log_path = Path(log_path)
        else:
            self.log_path = Path(__file__).parent / "quantum_logs"
        self.log_path.mkdir(parents=True, exist_ok=True)

        logger.info(f"QuantumIntegration 초기화: mode={mode}")

    # ==========================================================================
    # 4. 학생 상태 변환 (기존 시스템 → StateVector)
    # ==========================================================================

    def create_state_from_context(
        self,
        context: Dict[str, Any],
        persona_id: Optional[str] = None
    ) -> StateVector:
        """
        기존 시스템의 컨텍스트 → StateVector 변환

        지원 필드:
        - weekly_completion_rate: 0-100 → engagement/motivation
        - emotion_score: 0-1 → emotional_regulation
        - anxiety_level: "low"/"medium"/"high" → anxiety
        - confidence_level: 0-1 → confidence/self_efficacy
        - help_requests: 0+ → help_seeking
        - metacognition_score: 0-1 → metacognition

        또는 persona_id로 직접 매핑
        """
        # 페르소나 ID가 있으면 직접 매핑
        if persona_id and persona_id in PERSONA_TO_STATE:
            return get_state_vector(persona_id)

        # 컨텍스트에서 StateVector 생성
        state = StateVector()

        # 주간 완료율 → engagement, motivation
        if "weekly_completion_rate" in context:
            rate = context["weekly_completion_rate"] / 100.0
            state.engagement = 0.3 + rate * 0.6  # 0.3-0.9 범위
            state.motivation = 0.3 + rate * 0.6

        # 감정 점수 → emotional_regulation
        if "emotion_score" in context:
            state.emotional_regulation = context["emotion_score"]

        # 불안 수준 → anxiety
        if "anxiety_level" in context:
            anxiety_map = {"low": 0.2, "medium": 0.5, "high": 0.8}
            state.anxiety = anxiety_map.get(context["anxiety_level"], 0.5)

        # 자신감 수준 → confidence, self_efficacy
        if "confidence_level" in context:
            state.confidence = context["confidence_level"]
            state.self_efficacy = context["confidence_level"] * 0.9

        # 도움 요청 빈도 → help_seeking
        if "help_requests" in context:
            # 0-10회 → 0.2-0.9 범위
            state.help_seeking = min(0.9, 0.2 + context["help_requests"] * 0.07)

        # 메타인지 점수
        if "metacognition_score" in context:
            state.metacognition = context["metacognition_score"]

        return state

    # ==========================================================================
    # 5. Quantum 제안 API
    # ==========================================================================

    def get_suggestion(
        self,
        student_id: str,
        student_state: StateVector,
        triggered_agents: List[int],
        agent_priorities: Optional[Dict[int, int]] = None,
        agent_confidences: Optional[Dict[int, float]] = None,
        agent_scenarios: Optional[Dict[int, str]] = None
    ) -> Dict[str, Any]:
        """
        Quantum 기반 에이전트 순서 제안

        Returns:
            {
                "suggested_order": [(agent_id, priority_score), ...],
                "flow_optimized": [(agent_id, score), ...],
                "expected_state": {...},
                "reasoning": {...}
            }
        """
        # Orchestrator에서 제안 받기
        suggestions = self.orchestrator.suggest_agent_order(
            student_state=student_state,
            triggered_agents=triggered_agents,
            agent_priorities=agent_priorities,
            agent_confidences=agent_confidences,
            agent_scenarios=agent_scenarios
        )

        # Flow 최적화된 순서도 계산
        flow_order, expected_state = self.orchestrator.get_flow_optimized_order(
            student_state=student_state,
            triggered_agents=triggered_agents
        )

        # 결과 구조화
        result = {
            "suggested_order": [
                {
                    "agent_id": s.agent_id,
                    "agent_name": get_agent_name(s.agent_id),
                    "priority_score": round(s.priority_score, 4),
                    "state_alignment": round(s.state_alignment, 4),
                    "signal_strength": round(s.signal_strength, 4),
                    "entanglement_bonus": round(s.entanglement_bonus, 4)
                }
                for s in suggestions
            ],
            "flow_optimized": [
                {
                    "agent_id": f.agent_id,
                    "agent_name": get_agent_name(f.agent_id),
                    "score": round(f.priority_score, 4)
                }
                for f in flow_order
            ],
            "expected_state": {
                "metacognition": round(expected_state.metacognition, 3),
                "self_efficacy": round(expected_state.self_efficacy, 3),
                "help_seeking": round(expected_state.help_seeking, 3),
                "emotional_regulation": round(expected_state.emotional_regulation, 3),
                "anxiety": round(expected_state.anxiety, 3),
                "confidence": round(expected_state.confidence, 3),
                "engagement": round(expected_state.engagement, 3),
                "motivation": round(expected_state.motivation, 3)
            },
            "reasoning": {
                "mode": self.mode.value,
                "triggered_count": len(triggered_agents),
                "top_agent": get_agent_name(suggestions[0].agent_id) if suggestions else None,
                "entanglement_boost": any(s.entanglement_bonus > 1.0 for s in suggestions)
            }
        }

        # 기록 저장
        self._record_suggestion(
            student_id=student_id,
            student_state=student_state,
            triggered_agents=triggered_agents,
            result=result
        )

        return result

    # ==========================================================================
    # 6. 비교 모드 API
    # ==========================================================================

    def record_actual_result(
        self,
        student_id: str,
        actual_order: List[int],
        outcome_score: float
    ) -> Dict[str, Any]:
        """
        실제 결과 기록 및 비교 분석 (compare 모드)

        Args:
            student_id: 학생 ID
            actual_order: 실제 실행된 에이전트 순서
            outcome_score: 결과 점수 (0-1)

        Returns:
            비교 분석 결과
        """
        if self.mode != OrchestratorMode.COMPARE:
            logger.warning("record_actual_result는 compare 모드에서만 유효합니다")
            return {"error": "Not in compare mode"}

        # 최근 기록 찾기
        recent_record = None
        for record in reversed(self.metrics.records):
            if record.student_id == student_id and record.actual_order is None:
                recent_record = record
                break

        if not recent_record:
            return {"error": f"No pending record found for student {student_id}"}

        # 비교 분석
        suggested_list = [s["agent_id"] for s in recent_record.quantum_suggestion]
        comparison = self.orchestrator.compare_with_actual(
            suggested=suggested_list,
            actual=actual_order,
            outcome_score=outcome_score
        )

        # 기록 업데이트
        recent_record.actual_order = actual_order
        recent_record.actual_outcome = outcome_score
        recent_record.comparison = comparison

        # 메트릭 업데이트
        self._update_metrics(comparison)

        # 로그 파일 저장
        self._save_comparison_log(recent_record)

        return comparison

    # ==========================================================================
    # 7. 내부 헬퍼 메서드
    # ==========================================================================

    def _record_suggestion(
        self,
        student_id: str,
        student_state: StateVector,
        triggered_agents: List[int],
        result: Dict
    ):
        """제안 기록 저장"""
        record = IntegrationRecord(
            timestamp=datetime.now().isoformat(),
            mode=self.mode.value,
            student_id=student_id,
            student_state={
                "metacognition": student_state.metacognition,
                "self_efficacy": student_state.self_efficacy,
                "help_seeking": student_state.help_seeking,
                "emotional_regulation": student_state.emotional_regulation,
                "anxiety": student_state.anxiety,
                "confidence": student_state.confidence,
                "engagement": student_state.engagement,
                "motivation": student_state.motivation
            },
            triggered_agents=triggered_agents,
            quantum_suggestion=result["suggested_order"]
        )

        self.metrics.records.append(record)
        self.metrics.total_runs += 1

        if self.mode == OrchestratorMode.OBSERVE:
            self.metrics.observe_runs += 1
        else:
            self.metrics.compare_runs += 1

        # 관찰 모드 로그
        logger.info(
            f"[{self.mode.value}] student={student_id}, "
            f"triggered={len(triggered_agents)}, "
            f"top_suggestion={result['reasoning']['top_agent']}"
        )

    def _update_metrics(self, comparison: Dict):
        """비교 결과로 메트릭 업데이트"""
        n = self.metrics.compare_runs

        # 이동 평균 계산
        if n > 1:
            self.metrics.avg_similarity = (
                self.metrics.avg_similarity * (n - 1) + comparison["order_similarity"]
            ) / n
            self.metrics.avg_outcome_score = (
                self.metrics.avg_outcome_score * (n - 1) + comparison["outcome_score"]
            ) / n
        else:
            self.metrics.avg_similarity = comparison["order_similarity"]
            self.metrics.avg_outcome_score = comparison["outcome_score"]

        # 제안 정확도 (유사도 > 70%면 정확)
        accurate_count = sum(
            1 for r in self.metrics.records
            if r.comparison and r.comparison.get("order_similarity", 0) > 0.7
        )
        self.metrics.suggestion_accuracy = accurate_count / n if n > 0 else 0

    def _save_comparison_log(self, record: IntegrationRecord):
        """비교 로그 파일 저장"""
        date_str = datetime.now().strftime("%Y-%m-%d")
        log_file = self.log_path / f"quantum_compare_{date_str}.jsonl"

        with open(log_file, "a", encoding="utf-8") as f:
            f.write(json.dumps(record.to_dict(), ensure_ascii=False) + "\n")

    # ==========================================================================
    # 8. 통계 및 리포트
    # ==========================================================================

    def get_metrics_summary(self) -> Dict[str, Any]:
        """현재 세션 메트릭 요약"""
        return {
            "total_runs": self.metrics.total_runs,
            "observe_runs": self.metrics.observe_runs,
            "compare_runs": self.metrics.compare_runs,
            "avg_similarity": round(self.metrics.avg_similarity, 4),
            "avg_outcome_score": round(self.metrics.avg_outcome_score, 4),
            "suggestion_accuracy": round(self.metrics.suggestion_accuracy, 4)
        }

    def generate_report(self) -> str:
        """분석 리포트 생성"""
        summary = self.get_metrics_summary()

        report = f"""
========================================
Quantum Integration Report
========================================
Mode: {self.mode.value}
Total Runs: {summary['total_runs']}
  - Observe: {summary['observe_runs']}
  - Compare: {summary['compare_runs']}

Performance Metrics:
  - Avg Order Similarity: {summary['avg_similarity']:.2%}
  - Avg Outcome Score: {summary['avg_outcome_score']:.2%}
  - Suggestion Accuracy: {summary['suggestion_accuracy']:.2%}

Recent Records: {len(self.metrics.records)}
========================================
"""
        return report

# ==============================================================================
# 9. 편의 함수
# ==============================================================================

def quick_observe(
    student_id: str,
    context: Dict[str, Any],
    triggered_agents: List[int]
) -> Dict[str, Any]:
    """
    빠른 관찰 모드 실행

    사용 예시:
    ```python
    result = quick_observe(
        student_id="user123",
        context={"weekly_completion_rate": 55, "anxiety_level": "high"},
        triggered_agents=[5, 8, 10]
    )
    print(result["suggested_order"])
    ```
    """
    qi = QuantumIntegration(mode="observe")
    state = qi.create_state_from_context(context)
    return qi.get_suggestion(student_id, state, triggered_agents)

def quick_compare(
    student_id: str,
    context: Dict[str, Any],
    triggered_agents: List[int],
    actual_order: List[int],
    outcome_score: float
) -> Dict[str, Any]:
    """
    빠른 비교 모드 실행

    Returns:
        비교 분석 결과
    """
    qi = QuantumIntegration(mode="compare")
    state = qi.create_state_from_context(context)
    qi.get_suggestion(student_id, state, triggered_agents)
    return qi.record_actual_result(student_id, actual_order, outcome_score)

# ==============================================================================
# 10. 테스트 함수
# ==============================================================================

def run_integration_test():
    """통합 테스트 실행"""
    print("=" * 60)
    print("Quantum Integration - Phase 4 테스트")
    print("=" * 60)
    print()

    # 1. 관찰 모드 테스트
    print("📊 [1] 관찰 모드 (Observe) 테스트")
    print("-" * 40)

    qi_observe = QuantumIntegration(mode="observe")

    # 테스트 컨텍스트 (불안한 학생)
    context = {
        "weekly_completion_rate": 55,
        "emotion_score": 0.3,
        "anxiety_level": "high",
        "confidence_level": 0.35,
        "help_requests": 5
    }

    state = qi_observe.create_state_from_context(context)
    print(f"   학생 컨텍스트 → StateVector 변환:")
    print(f"   - anxiety: {state.anxiety:.2f}")
    print(f"   - confidence: {state.confidence:.2f}")
    print(f"   - engagement: {state.engagement:.2f}")
    print()

    # 제안 받기
    suggestion = qi_observe.get_suggestion(
        student_id="test_user_001",
        student_state=state,
        triggered_agents=[5, 8, 10, 12]
    )

    print("   Quantum 제안:")
    for i, s in enumerate(suggestion["suggested_order"][:3], 1):
        print(f"   {i}. {s['agent_name']}: {s['priority_score']:.4f}")
    print()

    # 2. 비교 모드 테스트
    print("📊 [2] 비교 모드 (Compare) 테스트")
    print("-" * 40)

    qi_compare = QuantumIntegration(mode="compare")

    # 제안 받기
    qi_compare.get_suggestion(
        student_id="test_user_002",
        student_state=state,
        triggered_agents=[5, 8, 10, 12]
    )

    # 실제 결과 기록 (다른 순서로 실행됨)
    comparison = qi_compare.record_actual_result(
        student_id="test_user_002",
        actual_order=[8, 5, 12, 10],  # 실제로는 Calmness 먼저
        outcome_score=0.72
    )

    print(f"   비교 결과:")
    print(f"   - 순서 유사도: {comparison['order_similarity']:.2%}")
    print(f"   - 결과 점수: {comparison['outcome_score']:.2%}")
    print(f"   - 제안 품질: {comparison['suggestion_quality']:.4f}")
    print()

    # 3. 메트릭 요약
    print("📊 [3] 메트릭 요약")
    print("-" * 40)
    print(qi_compare.generate_report())

    # 4. 빠른 함수 테스트
    print("📊 [4] 편의 함수 테스트")
    print("-" * 40)

    result = quick_observe(
        student_id="quick_test",
        context={"weekly_completion_rate": 80, "anxiety_level": "low"},
        triggered_agents=[3, 5, 9]
    )
    print(f"   quick_observe 결과:")
    print(f"   - Top suggestion: {result['reasoning']['top_agent']}")
    print()

    print("=" * 60)
    print("✅ Phase 4 Integration 테스트 완료")
    print("=" * 60)

# ==============================================================================
# Main
# ==============================================================================

if __name__ == "__main__":
    run_integration_test()
