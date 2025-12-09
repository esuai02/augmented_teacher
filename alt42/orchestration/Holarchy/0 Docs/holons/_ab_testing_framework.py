#!/usr/bin/env python3
"""
A/B Testing Framework for Quantum Orchestrator

양자 모델 vs 기존 모델 비교를 위한 A/B 테스트 프레임워크

Components:
- TestGroupAssigner: 학생을 테스트 그룹에 할당
- MetricCollector: 학습 결과 지표 수집
- StatisticalAnalyzer: 통계 분석 및 유의성 검정
- ABTestReport: 결과 리포트 생성

Usage:
    from _ab_testing_framework import ABTestManager

    manager = ABTestManager(test_id="quantum_v1")
    group = manager.assign_group(student_id=12345)
    manager.record_outcome(student_id, group, metrics)
    report = manager.generate_report()
"""

import json
import hashlib
import math
from datetime import datetime
from enum import Enum
from typing import Dict, List, Optional, Any, Tuple


# ==============================================================================
# Enums and Constants
# ==============================================================================

class TestGroup(Enum):
    """A/B 테스트 그룹"""
    CONTROL = "control"           # 기존 모델 (순차적/랜덤 에이전트 선택)
    TREATMENT = "treatment"       # 양자 모델 (최적화된 에이전트 선택)


class MetricType(Enum):
    """측정 지표 유형"""
    LEARNING_GAIN = "learning_gain"           # 학습 향상도
    ENGAGEMENT_RATE = "engagement_rate"       # 참여율
    COMPLETION_RATE = "completion_rate"       # 완료율
    DROPOUT_RATE = "dropout_rate"             # 이탈률
    TIME_ON_TASK = "time_on_task"            # 과제 소요 시간
    AGENT_EFFECTIVENESS = "agent_effectiveness"  # 에이전트 효과성


# 8D 차원 이름 (Phase 7/8과 일치)
DIMENSION_NAMES = [
    'cognitive_clarity',
    'emotional_stability',
    'engagement_level',
    'concept_mastery',
    'routine_strength',
    'metacognitive_awareness',
    'dropout_risk',
    'intervention_readiness'
]


# ==============================================================================
# Test Group Assignment
# ==============================================================================

class TestGroupAssigner:
    """
    학생을 테스트 그룹에 할당하는 클래스

    특징:
    - 결정론적 할당 (같은 student_id는 항상 같은 그룹)
    - 해시 기반으로 균등 분배
    - 테스트별 salt로 독립적인 그룹 할당
    """

    def __init__(self, test_id, treatment_ratio=0.5, seed=42):
        # type: (str, float, int) -> None
        """
        Args:
            test_id: 테스트 식별자 (예: "quantum_v1")
            treatment_ratio: Treatment 그룹 비율 (0.0-1.0)
            seed: 랜덤 시드
        """
        self.test_id = test_id
        self.treatment_ratio = treatment_ratio
        self.seed = seed
        self._salt = f"{test_id}_{seed}"

    def assign(self, student_id):
        # type: (int) -> TestGroup
        """
        학생을 테스트 그룹에 할당

        Args:
            student_id: 학생 ID

        Returns:
            TestGroup.CONTROL 또는 TestGroup.TREATMENT
        """
        # 해시 기반 결정론적 할당
        hash_input = f"{self._salt}_{student_id}"
        hash_value = hashlib.md5(hash_input.encode()).hexdigest()

        # 해시를 0-1 사이 값으로 변환
        hash_int = int(hash_value[:8], 16)
        ratio = hash_int / 0xFFFFFFFF

        if ratio < self.treatment_ratio:
            return TestGroup.TREATMENT
        else:
            return TestGroup.CONTROL

    def get_group_info(self, student_id):
        # type: (int) -> Dict[str, Any]
        """학생의 그룹 정보 반환"""
        group = self.assign(student_id)
        return {
            "student_id": student_id,
            "test_id": self.test_id,
            "group": group.value,
            "is_treatment": group == TestGroup.TREATMENT,
            "assigned_at": datetime.now().isoformat()
        }


# ==============================================================================
# Metric Collection
# ==============================================================================

class MetricCollector:
    """
    학습 결과 지표 수집 클래스

    수집 지표:
    - 학습 향상도 (pre-post 비교)
    - 참여율 (세션 완료 비율)
    - 이탈률 (중도 포기 비율)
    - 에이전트 효과성 (개입 후 상태 변화)
    """

    def __init__(self):
        # type: () -> None
        self.records = []  # type: List[Dict[str, Any]]
        self._metric_definitions = {
            MetricType.LEARNING_GAIN: {
                "description": "Pre-Post 학습 향상도",
                "unit": "percentage",
                "higher_is_better": True
            },
            MetricType.ENGAGEMENT_RATE: {
                "description": "세션 참여율",
                "unit": "percentage",
                "higher_is_better": True
            },
            MetricType.COMPLETION_RATE: {
                "description": "과제 완료율",
                "unit": "percentage",
                "higher_is_better": True
            },
            MetricType.DROPOUT_RATE: {
                "description": "이탈률",
                "unit": "percentage",
                "higher_is_better": False
            },
            MetricType.TIME_ON_TASK: {
                "description": "과제 소요 시간",
                "unit": "minutes",
                "higher_is_better": None  # 컨텍스트 의존
            },
            MetricType.AGENT_EFFECTIVENESS: {
                "description": "에이전트 개입 효과성",
                "unit": "score",
                "higher_is_better": True
            }
        }

    def record(self, student_id, group, metrics, session_id=None):
        # type: (int, TestGroup, Dict[str, float], Optional[str]) -> Dict[str, Any]
        """
        학습 결과 기록

        Args:
            student_id: 학생 ID
            group: 테스트 그룹
            metrics: 측정 지표 딕셔너리
            session_id: 세션 ID (선택)

        Returns:
            기록된 데이터
        """
        record = {
            "student_id": student_id,
            "group": group.value,
            "session_id": session_id or f"session_{len(self.records)}",
            "metrics": metrics,
            "recorded_at": datetime.now().isoformat()
        }
        self.records.append(record)
        return record

    def record_state_change(self, student_id, group, pre_state, post_state, agent_sequence):
        # type: (int, TestGroup, List[float], List[float], List[int]) -> Dict[str, Any]
        """
        8D 상태 변화 기록 (에이전트 개입 전후)

        Args:
            student_id: 학생 ID
            group: 테스트 그룹
            pre_state: 개입 전 8D StateVector
            post_state: 개입 후 8D StateVector
            agent_sequence: 적용된 에이전트 순서

        Returns:
            상태 변화 분석 결과
        """
        # 차원별 변화 계산
        dimension_changes = {}
        for i, name in enumerate(DIMENSION_NAMES):
            change = post_state[i] - pre_state[i]
            # dropout_risk는 감소가 좋음
            if name == 'dropout_risk':
                improvement = -change
            else:
                improvement = change
            dimension_changes[name] = {
                "pre": pre_state[i],
                "post": post_state[i],
                "change": change,
                "improvement": improvement
            }

        # 전체 효과성 점수
        total_improvement = sum(
            d["improvement"] for d in dimension_changes.values()
        ) / len(dimension_changes)

        record = {
            "student_id": student_id,
            "group": group.value,
            "agent_sequence": agent_sequence,
            "dimension_changes": dimension_changes,
            "effectiveness_score": total_improvement,
            "recorded_at": datetime.now().isoformat()
        }
        self.records.append(record)
        return record

    def get_group_metrics(self, group):
        # type: (TestGroup) -> Dict[str, List[float]]
        """특정 그룹의 모든 지표 반환"""
        group_records = [r for r in self.records if r.get("group") == group.value]

        metrics = {}
        for record in group_records:
            if "metrics" in record:
                for key, value in record["metrics"].items():
                    if key not in metrics:
                        metrics[key] = []
                    metrics[key].append(value)
            if "effectiveness_score" in record:
                if "effectiveness_score" not in metrics:
                    metrics["effectiveness_score"] = []
                metrics["effectiveness_score"].append(record["effectiveness_score"])

        return metrics


# ==============================================================================
# Statistical Analysis
# ==============================================================================

class StatisticalAnalyzer:
    """
    통계 분석 클래스

    분석 기능:
    - 평균, 표준편차 계산
    - 독립 t-검정 (그룹 간 차이 유의성)
    - 효과 크기 (Cohen's d)
    - 신뢰 구간
    """

    @staticmethod
    def mean(values):
        # type: (List[float]) -> float
        """평균 계산"""
        if not values:
            return 0.0
        return sum(values) / len(values)

    @staticmethod
    def std(values):
        # type: (List[float]) -> float
        """표준편차 계산"""
        if len(values) < 2:
            return 0.0
        m = StatisticalAnalyzer.mean(values)
        variance = sum((x - m) ** 2 for x in values) / (len(values) - 1)
        return math.sqrt(variance)

    @staticmethod
    def independent_t_test(group1, group2):
        # type: (List[float], List[float]) -> Dict[str, float]
        """
        독립 표본 t-검정

        Args:
            group1: Control 그룹 값들
            group2: Treatment 그룹 값들

        Returns:
            t-statistic, p-value (근사), degrees of freedom
        """
        n1, n2 = len(group1), len(group2)
        if n1 < 2 or n2 < 2:
            return {"t_statistic": 0.0, "p_value": 1.0, "df": 0}

        m1 = StatisticalAnalyzer.mean(group1)
        m2 = StatisticalAnalyzer.mean(group2)
        s1 = StatisticalAnalyzer.std(group1)
        s2 = StatisticalAnalyzer.std(group2)

        # Pooled standard error
        se = math.sqrt((s1**2 / n1) + (s2**2 / n2))
        if se == 0:
            return {"t_statistic": 0.0, "p_value": 1.0, "df": n1 + n2 - 2}

        t_stat = (m2 - m1) / se
        df = n1 + n2 - 2

        # p-value 근사 (정규 분포 근사)
        # 정확한 값을 위해서는 scipy 필요
        p_value = 2 * (1 - StatisticalAnalyzer._normal_cdf(abs(t_stat)))

        return {
            "t_statistic": t_stat,
            "p_value": p_value,
            "df": df,
            "mean_control": m1,
            "mean_treatment": m2,
            "std_control": s1,
            "std_treatment": s2
        }

    @staticmethod
    def cohens_d(group1, group2):
        # type: (List[float], List[float]) -> float
        """
        Cohen's d 효과 크기 계산

        해석:
        - |d| < 0.2: 작은 효과
        - 0.2 <= |d| < 0.5: 중간 효과
        - 0.5 <= |d| < 0.8: 큰 효과
        - |d| >= 0.8: 매우 큰 효과
        """
        if len(group1) < 2 or len(group2) < 2:
            return 0.0

        m1 = StatisticalAnalyzer.mean(group1)
        m2 = StatisticalAnalyzer.mean(group2)
        s1 = StatisticalAnalyzer.std(group1)
        s2 = StatisticalAnalyzer.std(group2)

        # Pooled standard deviation
        n1, n2 = len(group1), len(group2)
        pooled_std = math.sqrt(
            ((n1 - 1) * s1**2 + (n2 - 1) * s2**2) / (n1 + n2 - 2)
        )

        if pooled_std == 0:
            return 0.0

        return (m2 - m1) / pooled_std

    @staticmethod
    def effect_size_interpretation(d):
        # type: (float) -> str
        """효과 크기 해석"""
        abs_d = abs(d)
        if abs_d < 0.2:
            return "negligible"
        elif abs_d < 0.5:
            return "small"
        elif abs_d < 0.8:
            return "medium"
        else:
            return "large"

    @staticmethod
    def _normal_cdf(x):
        # type: (float) -> float
        """정규분포 CDF 근사 (Abramowitz and Stegun)"""
        # 표준 정규 분포의 CDF
        a1 = 0.254829592
        a2 = -0.284496736
        a3 = 1.421413741
        a4 = -1.453152027
        a5 = 1.061405429
        p = 0.3275911

        sign = 1 if x >= 0 else -1
        x = abs(x) / math.sqrt(2)

        t = 1.0 / (1.0 + p * x)
        y = 1.0 - (((((a5 * t + a4) * t) + a3) * t + a2) * t + a1) * t * math.exp(-x * x)

        return 0.5 * (1.0 + sign * y)

    @staticmethod
    def confidence_interval(values, confidence=0.95):
        # type: (List[float], float) -> Tuple[float, float]
        """신뢰 구간 계산"""
        if len(values) < 2:
            m = StatisticalAnalyzer.mean(values) if values else 0
            return (m, m)

        m = StatisticalAnalyzer.mean(values)
        s = StatisticalAnalyzer.std(values)
        n = len(values)

        # z-값 (95% = 1.96, 99% = 2.576)
        z = 1.96 if confidence == 0.95 else 2.576
        margin = z * (s / math.sqrt(n))

        return (m - margin, m + margin)


# ==============================================================================
# A/B Test Report
# ==============================================================================

class ABTestReport:
    """
    A/B 테스트 결과 리포트 생성 클래스
    """

    def __init__(self, test_id, collector, analyzer=None):
        # type: (str, MetricCollector, Optional[StatisticalAnalyzer]) -> None
        self.test_id = test_id
        self.collector = collector
        self.analyzer = analyzer or StatisticalAnalyzer()

    def generate(self):
        # type: () -> Dict[str, Any]
        """전체 리포트 생성"""
        control_metrics = self.collector.get_group_metrics(TestGroup.CONTROL)
        treatment_metrics = self.collector.get_group_metrics(TestGroup.TREATMENT)

        # 모든 지표에 대해 분석
        metric_analysis = {}
        all_metrics = set(control_metrics.keys()) | set(treatment_metrics.keys())

        for metric_name in all_metrics:
            control_values = control_metrics.get(metric_name, [])
            treatment_values = treatment_metrics.get(metric_name, [])

            if control_values and treatment_values:
                t_test = self.analyzer.independent_t_test(control_values, treatment_values)
                effect_size = self.analyzer.cohens_d(control_values, treatment_values)

                metric_analysis[metric_name] = {
                    "control": {
                        "n": len(control_values),
                        "mean": self.analyzer.mean(control_values),
                        "std": self.analyzer.std(control_values),
                        "ci_95": self.analyzer.confidence_interval(control_values)
                    },
                    "treatment": {
                        "n": len(treatment_values),
                        "mean": self.analyzer.mean(treatment_values),
                        "std": self.analyzer.std(treatment_values),
                        "ci_95": self.analyzer.confidence_interval(treatment_values)
                    },
                    "comparison": {
                        "t_statistic": t_test["t_statistic"],
                        "p_value": t_test["p_value"],
                        "significant": t_test["p_value"] < 0.05,
                        "effect_size": effect_size,
                        "effect_interpretation": self.analyzer.effect_size_interpretation(effect_size)
                    }
                }

        # 전체 결론
        significant_improvements = [
            name for name, data in metric_analysis.items()
            if data["comparison"]["significant"] and data["comparison"]["effect_size"] > 0
        ]

        return {
            "test_id": self.test_id,
            "generated_at": datetime.now().isoformat(),
            "sample_sizes": {
                "control": len([r for r in self.collector.records if r.get("group") == "control"]),
                "treatment": len([r for r in self.collector.records if r.get("group") == "treatment"])
            },
            "metrics": metric_analysis,
            "conclusion": {
                "significant_improvements": significant_improvements,
                "recommendation": self._generate_recommendation(metric_analysis)
            }
        }

    def _generate_recommendation(self, metric_analysis):
        # type: (Dict[str, Any]) -> str
        """분석 결과 기반 권장 사항 생성"""
        significant_positive = 0
        significant_negative = 0
        total_metrics = len(metric_analysis)

        for data in metric_analysis.values():
            if data["comparison"]["significant"]:
                if data["comparison"]["effect_size"] > 0:
                    significant_positive += 1
                else:
                    significant_negative += 1

        if significant_positive > significant_negative and significant_positive >= total_metrics * 0.5:
            return "ADOPT: 양자 모델이 유의미한 개선을 보임. 전체 적용 권장."
        elif significant_negative > significant_positive:
            return "REJECT: 양자 모델이 기존 모델보다 성능이 낮음. 추가 연구 필요."
        else:
            return "CONTINUE: 결과가 혼재됨. 더 많은 데이터 수집 후 재분석 필요."

    def to_json(self):
        # type: () -> str
        """JSON 형식으로 반환"""
        return json.dumps(self.generate(), indent=2, ensure_ascii=False)


# ==============================================================================
# A/B Test Manager (Main Interface)
# ==============================================================================

class ABTestManager:
    """
    A/B 테스트 관리자 (메인 인터페이스)

    사용법:
        manager = ABTestManager(test_id="quantum_v1")

        # 1. 그룹 할당
        group = manager.assign_group(student_id=12345)

        # 2. 에이전트 선택 (그룹에 따라 다른 로직)
        if group == TestGroup.TREATMENT:
            agents = quantum_select_agents(state)
        else:
            agents = default_select_agents()

        # 3. 결과 기록
        manager.record_outcome(student_id, {
            "learning_gain": 0.15,
            "engagement_rate": 0.85
        })

        # 4. 리포트 생성
        report = manager.generate_report()
    """

    def __init__(self, test_id, treatment_ratio=0.5, seed=42):
        # type: (str, float, int) -> None
        self.test_id = test_id
        self.assigner = TestGroupAssigner(test_id, treatment_ratio, seed)
        self.collector = MetricCollector()
        self.analyzer = StatisticalAnalyzer()

        # 학생별 그룹 캐시
        self._group_cache = {}  # type: Dict[int, TestGroup]

    def assign_group(self, student_id):
        # type: (int) -> TestGroup
        """학생을 테스트 그룹에 할당 (캐시됨)"""
        if student_id not in self._group_cache:
            self._group_cache[student_id] = self.assigner.assign(student_id)
        return self._group_cache[student_id]

    def get_group(self, student_id):
        # type: (int) -> TestGroup
        """학생의 현재 그룹 반환"""
        return self.assign_group(student_id)

    def is_treatment(self, student_id):
        # type: (int) -> bool
        """학생이 Treatment 그룹인지 확인"""
        return self.assign_group(student_id) == TestGroup.TREATMENT

    def record_outcome(self, student_id, metrics, session_id=None):
        # type: (int, Dict[str, float], Optional[str]) -> Dict[str, Any]
        """학습 결과 기록"""
        group = self.assign_group(student_id)
        return self.collector.record(student_id, group, metrics, session_id)

    def record_state_change(self, student_id, pre_state, post_state, agent_sequence):
        # type: (int, List[float], List[float], List[int]) -> Dict[str, Any]
        """8D 상태 변화 기록"""
        group = self.assign_group(student_id)
        return self.collector.record_state_change(
            student_id, group, pre_state, post_state, agent_sequence
        )

    def generate_report(self):
        # type: () -> Dict[str, Any]
        """A/B 테스트 리포트 생성"""
        report = ABTestReport(self.test_id, self.collector, self.analyzer)
        return report.generate()

    def get_statistics(self, metric_name):
        # type: (str) -> Dict[str, Any]
        """특정 지표의 통계 반환"""
        control = self.collector.get_group_metrics(TestGroup.CONTROL).get(metric_name, [])
        treatment = self.collector.get_group_metrics(TestGroup.TREATMENT).get(metric_name, [])

        return {
            "metric": metric_name,
            "control": {
                "n": len(control),
                "mean": self.analyzer.mean(control),
                "std": self.analyzer.std(control)
            },
            "treatment": {
                "n": len(treatment),
                "mean": self.analyzer.mean(treatment),
                "std": self.analyzer.std(treatment)
            },
            "t_test": self.analyzer.independent_t_test(control, treatment),
            "effect_size": self.analyzer.cohens_d(control, treatment)
        }


# ==============================================================================
# CLI Interface
# ==============================================================================

def main():
    """테스트 실행"""
    print("=" * 60)
    print("A/B Testing Framework - Demo")
    print("=" * 60)

    # 테스트 매니저 생성
    manager = ABTestManager(test_id="quantum_v1", treatment_ratio=0.5)

    # 시뮬레이션 데이터 생성
    import random
    random.seed(42)

    print("\n1. Assigning students to groups...")
    for student_id in range(1, 101):
        group = manager.assign_group(student_id)

        # Treatment 그룹은 평균적으로 더 좋은 결과 (시뮬레이션)
        base_gain = 0.10 if group == TestGroup.CONTROL else 0.15
        base_engagement = 0.70 if group == TestGroup.CONTROL else 0.80

        metrics = {
            "learning_gain": base_gain + random.uniform(-0.05, 0.05),
            "engagement_rate": base_engagement + random.uniform(-0.10, 0.10),
            "completion_rate": 0.75 + random.uniform(-0.15, 0.15),
            "effectiveness_score": 0.5 + (0.1 if group == TestGroup.TREATMENT else 0) + random.uniform(-0.1, 0.1)
        }

        manager.record_outcome(student_id, metrics)

    # 리포트 생성
    print("\n2. Generating report...")
    report = manager.generate_report()

    print(f"\n{'=' * 60}")
    print(f"Test ID: {report['test_id']}")
    print(f"Generated: {report['generated_at']}")
    print(f"{'=' * 60}")

    print(f"\nSample Sizes:")
    print(f"  Control: {report['sample_sizes']['control']}")
    print(f"  Treatment: {report['sample_sizes']['treatment']}")

    print(f"\nMetric Analysis:")
    for metric_name, data in report['metrics'].items():
        print(f"\n  {metric_name}:")
        print(f"    Control:   mean={data['control']['mean']:.4f}, std={data['control']['std']:.4f}")
        print(f"    Treatment: mean={data['treatment']['mean']:.4f}, std={data['treatment']['std']:.4f}")
        sig = "✓" if data['comparison']['significant'] else "✗"
        print(f"    p-value: {data['comparison']['p_value']:.4f} {sig}")
        print(f"    Effect: {data['comparison']['effect_size']:.3f} ({data['comparison']['effect_interpretation']})")

    print(f"\n{'=' * 60}")
    print(f"Conclusion:")
    print(f"  Significant improvements: {report['conclusion']['significant_improvements']}")
    print(f"  Recommendation: {report['conclusion']['recommendation']}")
    print(f"{'=' * 60}")

    return report


if __name__ == "__main__":
    main()


# ==============================================================================
# Database Schema Reference
# ==============================================================================
"""
Database Tables (for persistence):

1. ab_tests
   - id: INT PRIMARY KEY
   - test_id: VARCHAR(64) UNIQUE
   - treatment_ratio: DECIMAL(3,2)
   - seed: INT
   - created_at: DATETIME
   - status: ENUM('active', 'paused', 'completed')

2. ab_test_assignments
   - id: INT PRIMARY KEY
   - test_id: VARCHAR(64)
   - student_id: INT
   - group: ENUM('control', 'treatment')
   - assigned_at: DATETIME

3. ab_test_outcomes
   - id: INT PRIMARY KEY
   - test_id: VARCHAR(64)
   - student_id: INT
   - session_id: VARCHAR(64)
   - metrics: JSON
   - recorded_at: DATETIME

4. ab_test_state_changes
   - id: INT PRIMARY KEY
   - test_id: VARCHAR(64)
   - student_id: INT
   - pre_state: JSON (8D vector)
   - post_state: JSON (8D vector)
   - agent_sequence: JSON
   - effectiveness_score: DECIMAL(5,4)
   - recorded_at: DATETIME
"""
