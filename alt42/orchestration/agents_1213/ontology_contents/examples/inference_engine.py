#!/usr/bin/env python3
"""
추론 엔진 (Inference Engine)

온톨로지 기반 규칙을 사용하여 학생 상태를 분석하고
적절한 결론을 도출하는 추론 엔진

사용 예:
    from inference_engine import InferenceEngine

    engine = InferenceEngine('01_minimal_ontology.json')
    student_state = {'emotion': 'Frustrated'}
    results = engine.infer(student_state)

    for result in results:
        print(f"규칙: {result['rule_name']}")
        print(f"결론: {result['conclusion']}")
"""

from typing import Dict, List, Any, Optional
from ontology_loader import OntologyLoader


class InferenceEngine:
    """
    온톨로지 기반 추론 엔진

    학생의 현재 상태를 입력받아 온톨로지 규칙을 평가하고
    매칭되는 규칙의 결론을 반환합니다.
    """

    def __init__(self, ontology_path: str):
        """
        Args:
            ontology_path: 온톨로지 파일 경로
        """
        self.loader = OntologyLoader(ontology_path)
        self.ontology = self.loader.load()
        self.rules = self.loader.extract_rules()

    def evaluate_condition(
        self,
        condition: Dict[str, Any],
        student_state: Dict[str, Any]
    ) -> bool:
        """
        조건 평가

        Args:
            condition: 규칙의 조건 (예: {'emotionEquals': 'Frustrated'})
            student_state: 학생 상태 (예: {'emotion': 'Frustrated'})

        Returns:
            조건이 만족되면 True, 아니면 False
        """
        # emotionEquals 조건 평가
        if 'emotionEquals' in condition:
            required_emotion = condition['emotionEquals']
            current_emotion = student_state.get('emotion', '')
            return current_emotion == required_emotion

        # 향후 다른 조건 타입 추가 가능
        # - scoreGreaterThan
        # - attemptsGreaterThan
        # - timeElapsedGreaterThan
        # - AND, OR, NOT 연산자

        return False

    def infer(self, student_state: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        추론 실행

        학생 상태를 기반으로 모든 규칙을 평가하고
        매칭되는 규칙들의 결론을 우선순위 순으로 반환

        Args:
            student_state: 학생의 현재 상태
                예: {'emotion': 'Frustrated', 'score': 60}

        Returns:
            매칭된 규칙 결과 리스트 (우선순위 내림차순)
            [
                {
                    'rule_id': 'rule_frustrated',
                    'rule_name': '좌절 → 격려',
                    'condition': {...},
                    'conclusion': '격려 필요',
                    'priority': 1.0,
                    'matched': True
                },
                ...
            ]
        """
        results = []

        for rule in self.rules:
            condition = rule['condition']
            is_matched = self.evaluate_condition(condition, student_state)

            if is_matched:
                results.append({
                    'rule_id': rule['id'],
                    'rule_name': rule['name'],
                    'condition': rule['condition'],
                    'conclusion': rule['conclusion'],
                    'priority': rule['priority'],
                    'matched': True
                })

        # 이미 우선순위로 정렬되어 있지만 명시적으로 재정렬
        results.sort(key=lambda x: x['priority'], reverse=True)

        return results

    def infer_best(self, student_state: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """
        최우선 규칙만 반환

        여러 규칙이 매칭되는 경우 우선순위가 가장 높은 규칙만 반환

        Args:
            student_state: 학생의 현재 상태

        Returns:
            최우선 규칙 결과 또는 None (매칭 없음)
        """
        results = self.infer(student_state)
        return results[0] if results else None

    def explain_reasoning(self, student_state: Dict[str, Any]) -> str:
        """
        추론 과정 설명

        Args:
            student_state: 학생의 현재 상태

        Returns:
            추론 과정에 대한 텍스트 설명
        """
        results = self.infer(student_state)

        explanation = []
        explanation.append(f"📊 학생 상태: {student_state}")
        explanation.append(f"📋 평가된 규칙 수: {len(self.rules)}개")
        explanation.append(f"✅ 매칭된 규칙 수: {len(results)}개")
        explanation.append("")

        if results:
            explanation.append("🎯 매칭된 규칙 (우선순위 순):")
            for i, result in enumerate(results, 1):
                explanation.append(f"  {i}. [{result['priority']}] {result['rule_name']}")
                explanation.append(f"     → {result['conclusion']}")
        else:
            explanation.append("⚠️  매칭된 규칙이 없습니다.")

        return "\n".join(explanation)


def main():
    """
    테스트용 메인 함수
    """
    print("="*60)
    print("추론 엔진 테스트")
    print("="*60)
    print()

    # 엔진 초기화
    engine = InferenceEngine('01_minimal_ontology.json')
    print(f"✅ 추론 엔진 초기화 완료 ({len(engine.rules)}개 규칙 로드)")
    print()

    # 테스트 케이스 1: 좌절 상태
    print("🧪 테스트 1: 좌절 상태")
    print("-" * 40)
    student_state_1 = {'emotion': 'Frustrated'}
    print(engine.explain_reasoning(student_state_1))
    print()

    # 테스트 케이스 2: 집중 상태
    print("🧪 테스트 2: 집중 상태")
    print("-" * 40)
    student_state_2 = {'emotion': 'Focused'}
    print(engine.explain_reasoning(student_state_2))
    print()

    # 테스트 케이스 3: 피로 상태
    print("🧪 테스트 3: 피로 상태")
    print("-" * 40)
    student_state_3 = {'emotion': 'Tired'}
    print(engine.explain_reasoning(student_state_3))
    print()

    # 테스트 케이스 4: 알 수 없는 감정
    print("🧪 테스트 4: 알 수 없는 감정")
    print("-" * 40)
    student_state_4 = {'emotion': 'Unknown'}
    print(engine.explain_reasoning(student_state_4))
    print()

    # 최우선 규칙 테스트
    print("🧪 테스트 5: 최우선 규칙만 가져오기 (좌절)")
    print("-" * 40)
    best_result = engine.infer_best(student_state_1)
    if best_result:
        print(f"최우선 규칙: {best_result['rule_name']}")
        print(f"결론: {best_result['conclusion']}")
        print(f"우선순위: {best_result['priority']}")
    else:
        print("매칭된 규칙이 없습니다.")
    print()

    print("="*60)
    print("✅ 테스트 완료!")
    print("="*60)


if __name__ == "__main__":
    main()
