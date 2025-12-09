# -*- coding: utf-8 -*-
"""
개입 의사결정 엔진 (Intervention Decision Engine, IDE)

7단계 파이프라인으로 개입 여부와 방식을 자동 결정합니다.

파이프라인:
1. Trigger 식별 - 22개 에이전트 트리거 감지
2. BCE 체크 - 경계조건 검증
3. 시나리오 생성 - 후보군 생성
4. 우선순위 결정 - 가중치 기반 정렬
5. 필수조건 체크 - 전제 조건 검증
6. 최종 선택 - 최적 시나리오 선택
7. 개입 실행 - Mind → Mouth

참조: quantum-orchestration-design.md > §5.4
문제점: quantum-ide-critical-issues.md
"""

# 구현 후 import 추가
# from ._ide_trigger import AgentTrigger
# from ._ide_boundary import BoundaryConditionEngine
# from ._ide_scenario import ScenarioGenerator
# from ._ide_priority import PriorityCalculator
# from ._ide_prerequisite import PrerequisiteChecker
# from ._ide_selector import InterventionSelector
# from ._ide_executor import InterventionExecutor
# from ._intervention_decision_engine import InterventionDecisionEngine

