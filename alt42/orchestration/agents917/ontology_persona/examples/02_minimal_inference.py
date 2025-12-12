#!/usr/bin/env python3
"""
최소 온톨로지 추론 엔진 - Hello World 버전

개념:
- Student (학생)
- Emotion (감정)
- hasEmotion (관계)

규칙 3개:
1. 학생이 "좌절" 감정이면 → "격려 필요"
2. 학생이 "집중" 감정이면 → "학습 진행"
3. 학생이 "피로" 감정이면 → "휴식 필요"
"""

import json
from typing import Dict, List, Any


class MinimalOntology:
    """최소 온톨로지 클래스"""

    def __init__(self, ontology_file: str):
        """온톨로지 로드"""
        with open(ontology_file, 'r', encoding='utf-8') as f:
            self.ontology = json.load(f)
        print(f"✅ 온톨로지 로드 완료: {ontology_file}")

    def get_concepts(self) -> List[str]:
        """온톨로지에서 개념 목록 추출"""
        concepts = []
        for item in self.ontology.get('@graph', []):
            if item.get('@type') == 'rdfs:Class':
                concepts.append(item['@id'])
        return concepts


class MinimalInferenceEngine:
    """최소 추론 엔진 - IF-THEN 규칙만 사용"""

    def __init__(self):
        """규칙 초기화"""
        self.rules = [
            {
                "id": "rule_1",
                "name": "좌절 → 격려",
                "condition": lambda facts: facts.get("emotion") == "좌절",
                "action": "격려 필요"
            },
            {
                "id": "rule_2",
                "name": "집중 → 학습",
                "condition": lambda facts: facts.get("emotion") == "집중",
                "action": "학습 진행"
            },
            {
                "id": "rule_3",
                "name": "피로 → 휴식",
                "condition": lambda facts: facts.get("emotion") == "피로",
                "action": "휴식 필요"
            }
        ]
        print(f"✅ 추론 규칙 {len(self.rules)}개 로드 완료")

    def infer(self, facts: Dict[str, Any]) -> List[str]:
        """
        주어진 사실(facts)에서 결론 추론

        Args:
            facts: 입력 사실 (예: {"student": "철수", "emotion": "좌절"})

        Returns:
            추론된 결론 목록
        """
        conclusions = []

        print(f"\n🔍 추론 시작")
        print(f"입력 사실: {facts}")

        for rule in self.rules:
            if rule["condition"](facts):
                conclusions.append(rule["action"])
                print(f"  ✓ 규칙 적용: {rule['name']} → {rule['action']}")

        if not conclusions:
            print(f"  ℹ️ 적용된 규칙 없음")

        return conclusions


def main():
    """메인 실행 함수"""

    print("="*60)
    print("Mathking 최소 온톨로지 추론 엔진 - Hello World")
    print("="*60)
    print()

    # 1. 온톨로지 로드
    ontology = MinimalOntology("01_minimal_ontology.json")
    concepts = ontology.get_concepts()
    print(f"온톨로지 개념: {concepts}")
    print()

    # 2. 추론 엔진 초기화
    engine = MinimalInferenceEngine()
    print()

    # 3. 테스트 케이스 3개
    test_cases = [
        {"student": "철수", "emotion": "좌절"},
        {"student": "영희", "emotion": "집중"},
        {"student": "민수", "emotion": "피로"}
    ]

    for i, facts in enumerate(test_cases, 1):
        print(f"\n{'─'*60}")
        print(f"테스트 케이스 {i}")
        print(f"{'─'*60}")

        conclusions = engine.infer(facts)

        print(f"\n📊 결과:")
        if conclusions:
            for conclusion in conclusions:
                print(f"  → {conclusion}")
        else:
            print(f"  (결론 없음)")

    print()
    print("="*60)
    print("✅ 추론 완료")
    print("="*60)


if __name__ == "__main__":
    main()
