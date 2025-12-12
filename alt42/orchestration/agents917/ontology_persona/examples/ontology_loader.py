#!/usr/bin/env python3
"""
온톨로지 로더 (Ontology Loader)

온톨로지 파일을 로드하고 필요한 정보를 추출하는 모듈

사용 예:
    from ontology_loader import OntologyLoader

    loader = OntologyLoader('01_minimal_ontology.json')
    ontology = loader.load()
    rules = loader.extract_rules()
    emotions = loader.extract_emotions()
"""

import json
from typing import Dict, List, Any, Optional


class OntologyLoader:
    """
    온톨로지 파일 로더

    JSON-LD 형식의 온톨로지 파일을 로드하고
    추론에 필요한 정보를 추출합니다.
    """

    def __init__(self, ontology_path: str):
        """
        Args:
            ontology_path: 온톨로지 파일 경로
        """
        self.ontology_path = ontology_path
        self.ontology: Optional[Dict[str, Any]] = None

    def load(self) -> Dict[str, Any]:
        """
        온톨로지 파일 로드

        Returns:
            로드된 온톨로지 데이터 (dict)

        Raises:
            FileNotFoundError: 파일이 존재하지 않음
            json.JSONDecodeError: JSON 파싱 실패
        """
        try:
            with open(self.ontology_path, 'r', encoding='utf-8') as f:
                self.ontology = json.load(f)
            return self.ontology
        except FileNotFoundError:
            raise FileNotFoundError(
                f"온톨로지 파일을 찾을 수 없습니다: {self.ontology_path}"
            )
        except json.JSONDecodeError as e:
            raise json.JSONDecodeError(
                f"온톨로지 파일 파싱 실패: {e.msg}",
                e.doc,
                e.pos
            )

    def extract_rules(self) -> List[Dict[str, Any]]:
        """
        온톨로지에서 InferenceRule 추출

        Returns:
            추론 규칙 목록 (priority 내림차순 정렬)

        Example:
            [
                {
                    'id': 'rule_frustrated',
                    'name': '좌절 → 격려',
                    'condition': {...},
                    'conclusion': '격려 필요',
                    'priority': 1.0
                },
                ...
            ]
        """
        if self.ontology is None:
            self.load()

        rules = []
        graph = self.ontology.get('@graph', [])

        for item in graph:
            if item.get('@type') == 'InferenceRule':
                rules.append({
                    'id': item['@id'],
                    'name': item['ruleName'],
                    'condition': item['condition'],
                    'conclusion': item['conclusion'],
                    'priority': item.get('priority', 1.0)
                })

        # 우선순위 내림차순 정렬 (높은 우선순위가 먼저)
        rules.sort(key=lambda x: x['priority'], reverse=True)

        return rules

    def extract_emotions(self) -> List[Dict[str, str]]:
        """
        온톨로지에서 감정 목록 추출

        Returns:
            감정 목록 (id, label, comment)

        Example:
            [
                {
                    'id': 'Frustrated',
                    'label': '좌절',
                    'comment': '문제를 해결하지 못해 느끼는 감정'
                },
                ...
            ]
        """
        if self.ontology is None:
            self.load()

        emotions = []
        graph = self.ontology.get('@graph', [])

        for item in graph:
            if item.get('@type') == 'Emotion' and '@id' in item:
                emotions.append({
                    'id': item['@id'],
                    'label': item.get('rdfs:label', ''),
                    'comment': item.get('rdfs:comment', '')
                })

        return emotions

    def extract_classes(self) -> List[Dict[str, str]]:
        """
        온톨로지에서 클래스 목록 추출

        Returns:
            클래스 목록 (id, label, comment)
        """
        if self.ontology is None:
            self.load()

        classes = []
        graph = self.ontology.get('@graph', [])

        for item in graph:
            if item.get('@type') == 'rdfs:Class':
                classes.append({
                    'id': item['@id'],
                    'label': item.get('rdfs:label', ''),
                    'comment': item.get('rdfs:comment', '')
                })

        return classes

    def get_rule_by_id(self, rule_id: str) -> Optional[Dict[str, Any]]:
        """
        ID로 규칙 조회

        Args:
            rule_id: 규칙 ID (예: 'rule_frustrated')

        Returns:
            규칙 정보 또는 None
        """
        rules = self.extract_rules()
        for rule in rules:
            if rule['id'] == rule_id:
                return rule
        return None

    def get_emotion_by_id(self, emotion_id: str) -> Optional[Dict[str, str]]:
        """
        ID로 감정 조회

        Args:
            emotion_id: 감정 ID (예: 'Frustrated')

        Returns:
            감정 정보 또는 None
        """
        emotions = self.extract_emotions()
        for emotion in emotions:
            if emotion['id'] == emotion_id:
                return emotion
        return None


def main():
    """
    테스트용 메인 함수
    """
    print("="*60)
    print("온톨로지 로더 테스트")
    print("="*60)
    print()

    # 로더 초기화
    loader = OntologyLoader('01_minimal_ontology.json')

    # 온톨로지 로드
    ontology = loader.load()
    print(f"✅ 온톨로지 로드 완료")
    print()

    # 클래스 추출
    classes = loader.extract_classes()
    print(f"📚 클래스 ({len(classes)}개):")
    for cls in classes:
        print(f"  - {cls['id']}: {cls['label']}")
    print()

    # 감정 추출
    emotions = loader.extract_emotions()
    print(f"😊 감정 ({len(emotions)}개):")
    for emotion in emotions:
        print(f"  - {emotion['id']}: {emotion['label']}")
    print()

    # 규칙 추출
    rules = loader.extract_rules()
    print(f"📋 규칙 ({len(rules)}개):")
    for rule in rules:
        print(f"  - {rule['id']}: {rule['name']} (우선순위: {rule['priority']})")
    print()

    print("="*60)
    print("✅ 테스트 완료!")
    print("="*60)


if __name__ == "__main__":
    main()
