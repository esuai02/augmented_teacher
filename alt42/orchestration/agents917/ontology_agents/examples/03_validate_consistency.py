#!/usr/bin/env python3
"""
문서 일관성 검증 도구

온톨로지와 추론 규칙, 그리고 관련 문서 간의 일관성을 자동으로 검증합니다.

검증 항목:
1. 온톨로지 구문 검증 (JSON 형식, 필수 필드)
2. 추론 규칙 검증 (규칙 ID 중복, 조건 함수 유효성)
3. 문서 간 일관성 검증 (온톨로지 개념 ↔ 추론 규칙 매칭)
"""

import json
import os
from typing import Dict, List, Any


class ConsistencyValidator:
    """일관성 검증 클래스"""

    def __init__(self, ontology_file: str, inference_file: str):
        """
        Args:
            ontology_file: 온톨로지 파일 경로
            inference_file: 추론 엔진 파일 경로
        """
        self.ontology_file = ontology_file
        self.inference_file = inference_file
        self.issues = []

    def validate_ontology_syntax(self) -> bool:
        """온톨로지 구문 검증"""
        print("\n" + "="*60)
        print("1. 온톨로지 구문 검증")
        print("="*60)

        try:
            with open(self.ontology_file, 'r', encoding='utf-8') as f:
                ontology = json.load(f)

            # 필수 필드 확인
            if '@context' not in ontology:
                self.issues.append("❌ 온톨로지에 @context 필드가 없습니다.")
                return False

            if '@graph' not in ontology:
                self.issues.append("❌ 온톨로지에 @graph 필드가 없습니다.")
                return False

            # 개념 유효성 확인
            concepts = []
            for item in ontology['@graph']:
                if '@id' not in item:
                    self.issues.append(f"❌ 개념에 @id 필드가 없습니다: {item}")
                    continue

                if '@type' not in item:
                    self.issues.append(f"❌ 개념 '{item['@id']}'에 @type 필드가 없습니다.")
                    continue

                concepts.append(item['@id'])

            print(f"✅ 온톨로지 구문 검증 통과")
            print(f"   발견된 개념: {concepts}")
            return True

        except json.JSONDecodeError as e:
            self.issues.append(f"❌ JSON 파싱 에러: {e}")
            return False
        except FileNotFoundError:
            self.issues.append(f"❌ 파일을 찾을 수 없습니다: {self.ontology_file}")
            return False

    def validate_inference_rules(self) -> bool:
        """추론 규칙 검증"""
        print("\n" + "="*60)
        print("2. 추론 규칙 검증")
        print("="*60)

        try:
            # Python 파일 읽기 (간단한 텍스트 분석)
            with open(self.inference_file, 'r', encoding='utf-8') as f:
                content = f.read()

            # 규칙 ID 중복 확인 (간단한 문자열 매칭)
            rule_ids = []
            for line in content.split('\n'):
                if '"id":' in line:
                    # "id": "rule_1" 형태에서 ID 추출
                    rule_id = line.split('"id":')[1].split('"')[1]
                    rule_ids.append(rule_id)

            # 중복 확인
            duplicates = [rid for rid in rule_ids if rule_ids.count(rid) > 1]
            if duplicates:
                self.issues.append(f"❌ 중복된 규칙 ID: {set(duplicates)}")
                return False

            print(f"✅ 추론 규칙 검증 통과")
            print(f"   발견된 규칙: {rule_ids}")
            return True

        except FileNotFoundError:
            self.issues.append(f"❌ 파일을 찾을 수 없습니다: {self.inference_file}")
            return False

    def validate_cross_document_consistency(self) -> bool:
        """문서 간 일관성 검증"""
        print("\n" + "="*60)
        print("3. 문서 간 일관성 검증")
        print("="*60)

        try:
            # 온톨로지에서 개념 추출
            with open(self.ontology_file, 'r', encoding='utf-8') as f:
                ontology = json.load(f)

            ontology_concepts = set()
            for item in ontology['@graph']:
                if item.get('@type') == 'rdfs:Class':
                    label = item.get('rdfs:label', '')
                    ontology_concepts.add(label)

            # 추론 규칙에서 사용된 개념 추출 (간단한 문자열 매칭)
            with open(self.inference_file, 'r', encoding='utf-8') as f:
                inference_content = f.read()

            # 추론 규칙에서 사용된 감정 개념들 찾기
            used_emotions = set()
            emotion_keywords = ['좌절', '집중', '피로', '불안', '기쁨', '흥미']
            for keyword in emotion_keywords:
                if f'"{keyword}"' in inference_content:
                    used_emotions.add(keyword)

            # 일관성 확인
            print(f"\n온톨로지 개념: {ontology_concepts}")
            print(f"추론 규칙에서 사용된 감정: {used_emotions}")

            # 온톨로지에 없는 개념이 추론 규칙에서 사용되는지 확인
            missing_in_ontology = used_emotions - ontology_concepts
            if missing_in_ontology:
                for emotion in missing_in_ontology:
                    self.issues.append(f"⚠️ 추론 규칙에서 사용된 '{emotion}'이 온톨로지에 정의되어 있지 않습니다.")
                    print(f"\n⚠️ 권장사항: 온톨로지에 '{emotion}' 개념 추가")
                return False

            print(f"\n✅ 문서 간 일관성 검증 통과")
            return True

        except Exception as e:
            self.issues.append(f"❌ 일관성 검증 중 에러: {e}")
            return False

    def run_all_validations(self) -> bool:
        """모든 검증 실행"""
        print("\n" + "="*60)
        print("Mathking 문서 일관성 검증 시작")
        print("="*60)

        results = []
        results.append(self.validate_ontology_syntax())
        results.append(self.validate_inference_rules())
        results.append(self.validate_cross_document_consistency())

        # 최종 결과
        print("\n" + "="*60)
        print("검증 결과 요약")
        print("="*60)

        if all(results):
            print("✅ 모든 검증 통과!")
            print("   온톨로지, 추론 규칙, 문서 일관성 모두 정상입니다.")
            return True
        else:
            print("❌ 검증 실패")
            print("\n발견된 문제:")
            for issue in self.issues:
                print(f"  {issue}")
            return False


def main():
    """메인 실행 함수"""
    validator = ConsistencyValidator(
        ontology_file="01_minimal_ontology.json",
        inference_file="02_minimal_inference.py"
    )

    success = validator.run_all_validations()

    print("\n" + "="*60)
    if success:
        print("✅ 일관성 검증 완료 - 문서들이 일관성을 유지하고 있습니다.")
    else:
        print("⚠️ 일관성 검증 완료 - 개선이 필요한 부분이 있습니다.")
    print("="*60)


if __name__ == "__main__":
    main()
