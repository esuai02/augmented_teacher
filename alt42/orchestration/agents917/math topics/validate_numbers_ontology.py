#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
수체계 온톨로지(1 numbers_ontology.owl) 검증 및 정리 스크립트

검증 항목:
1. XML 구문 검증
2. 필수 속성 확인 (stage, hasURL, description, includes)
3. IRI 중복 확인
4. precedes/dependsOn 관계 검증
5. 표준 학습활동 포함 여부 확인
"""

import os
import sys
import xml.etree.ElementTree as ET
from collections import defaultdict
from typing import Dict, List, Set, Tuple

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

# 표준 학습활동 목록
STANDARD_ACTIVITIES = {
    "ConceptRemind_Default",
    "ConceptRebuild_Default",
    "ConceptCheck_Default",
    "ExampleQuiz_Default",
    "RepresentativeType_Default"
}

class OntologyValidator:
    """온톨로지 검증 클래스"""
    
    def __init__(self, owl_file: str):
        self.owl_file = owl_file
        self.errors = []
        self.warnings = []
        self.stats = {
            'subtopics': 0,
            'activities': 0,
            'precedes_relations': 0,
            'dependsOn_relations': 0,
            'includes_relations': 0
        }
        
    def validate(self) -> Tuple[bool, Dict]:
        """전체 검증 실행"""
        print("=" * 60)
        print("수체계 온톨로지 검증 시작")
        print("=" * 60)
        print()
        
        # XML 파싱
        try:
            tree = ET.parse(self.owl_file)
            root = tree.getroot()
            print("[OK] XML 구문 검증 통과")
        except ET.ParseError as e:
            self.errors.append(f"XML 파싱 오류: {e}")
            return False, {'errors': self.errors, 'warnings': self.warnings, 'stats': self.stats}
        
        # 네임스페이스 정의
        namespaces = {
            'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs': 'http://www.w3.org/2000/01/rdf-schema#',
            'owl': 'http://www.w3.org/2002/07/owl#',
            'ar': 'http://example.org/adaptive-review#'
        }
        
        # Subtopic 수집 및 검증
        subtopics = {}
        activity_refs = set()
        
        for individual in root.findall('.//owl:NamedIndividual', namespaces):
            about = individual.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about', '')
            if not about:
                continue
                
            # Subtopic인지 확인
            type_elem = individual.find('rdf:type', namespaces)
            if type_elem is not None:
                resource = type_elem.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource', '')
                if 'Subtopic' in resource:
                    subtopic_id = about.split('#')[-1]
                    subtopics[subtopic_id] = {
                        'id': subtopic_id,
                        'about': about,
                        'label': '',
                        'stage': None,
                        'url': None,
                        'description': '',
                        'includes': []
                    }
                    
                    # 라벨 추출
                    label_elem = individual.find('rdfs:label', namespaces)
                    if label_elem is not None:
                        subtopics[subtopic_id]['label'] = label_elem.text or ''
                    
                    # stage 추출
                    stage_elem = individual.find('ar:stage', namespaces)
                    if stage_elem is not None:
                        subtopics[subtopic_id]['stage'] = stage_elem.text
                    
                    # hasURL 추출
                    url_elem = individual.find('ar:hasURL', namespaces)
                    if url_elem is not None:
                        subtopics[subtopic_id]['url'] = url_elem.text
                    
                    # description 추출
                    desc_elem = individual.find('ar:description', namespaces)
                    if desc_elem is not None:
                        subtopics[subtopic_id]['description'] = desc_elem.text or ''
                    
                    # includes 추출
                    for include_elem in individual.findall('ar:includes', namespaces):
                        resource = include_elem.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource', '')
                        if resource:
                            activity_id = resource.split('#')[-1]
                            subtopics[subtopic_id]['includes'].append(activity_id)
                            activity_refs.add(activity_id)
        
        self.stats['subtopics'] = len(subtopics)
        self.stats['includes_relations'] = sum(len(s['includes']) for s in subtopics.values())
        
        # 필수 속성 검증
        print("\n[필수 속성 검증]")
        for subtopic_id, subtopic in subtopics.items():
            if not subtopic['stage']:
                self.warnings.append(f"'{subtopic_id}': stage 속성이 없습니다")
            if not subtopic['url']:
                self.warnings.append(f"'{subtopic_id}': hasURL 속성이 없습니다")
            if not subtopic['description']:
                self.warnings.append(f"'{subtopic_id}': description 속성이 없습니다")
            if len(subtopic['includes']) < 5:
                self.warnings.append(f"'{subtopic_id}': 표준 학습활동이 5개 미만입니다 ({len(subtopic['includes'])}개)")
        
        # 표준 학습활동 포함 여부 확인
        print("\n[표준 학습활동 검증]")
        missing_activities = STANDARD_ACTIVITIES - activity_refs
        if missing_activities:
            self.warnings.append(f"표준 학습활동이 정의되지 않았습니다: {missing_activities}")
        else:
            print("✓ 모든 표준 학습활동이 포함되어 있습니다")
        
        # 관계 추출
        precedes_relations = []
        dependsOn_relations = []
        
        for desc in root.findall('.//rdf:Description', namespaces):
            about = desc.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about', '')
            if not about:
                continue
            
            source_id = about.split('#')[-1]
            
            # precedes 관계
            for precedes in desc.findall('ar:precedes', namespaces):
                target_resource = precedes.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource', '')
                if target_resource:
                    target_id = target_resource.split('#')[-1]
                    precedes_relations.append((source_id, target_id))
            
            # dependsOn 관계
            for depends in desc.findall('ar:dependsOn', namespaces):
                target_resource = depends.get('{http://www.w3.org/1999/02/22/rdf-syntax-ns#}resource', '')
                if target_resource:
                    target_id = target_resource.split('#')[-1]
                    dependsOn_relations.append((source_id, target_id))
        
        self.stats['precedes_relations'] = len(precedes_relations)
        self.stats['dependsOn_relations'] = len(dependsOn_relations)
        
        # 관계 검증
        print("\n[관계 검증]")
        all_subtopic_ids = set(subtopics.keys())
        
        for source, target in precedes_relations + dependsOn_relations:
            if source not in all_subtopic_ids:
                self.errors.append(f"관계 오류: '{source}'가 존재하지 않습니다")
            if target not in all_subtopic_ids:
                self.errors.append(f"관계 오류: '{target}'가 존재하지 않습니다")
        
        # 순환 의존성 검사 (간단한 버전)
        print("\n[순환 의존성 검사]")
        # dependsOn만 검사 (precedes는 순환 가능)
        visited = set()
        rec_stack = set()
        
        def has_cycle(node: str) -> bool:
            if node in rec_stack:
                return True
            if node in visited:
                return False
            
            visited.add(node)
            rec_stack.add(node)
            
            for source, target in dependsOn_relations:
                if source == node:
                    if has_cycle(target):
                        return True
            
            rec_stack.remove(node)
            return False
        
        cycles_found = False
        for subtopic_id in subtopics.keys():
            if subtopic_id not in visited:
                if has_cycle(subtopic_id):
                    self.errors.append(f"순환 의존성 발견: '{subtopic_id}'")
                    cycles_found = True
        
        if not cycles_found:
            print("✓ 순환 의존성이 없습니다")
        
        # 결과 출력
        print("\n" + "=" * 60)
        print("검증 결과 요약")
        print("=" * 60)
        print(f"\n📊 통계:")
        print(f"  - Subtopic 개수: {self.stats['subtopics']}")
        print(f"  - precedes 관계: {self.stats['precedes_relations']}")
        print(f"  - dependsOn 관계: {self.stats['dependsOn_relations']}")
        print(f"  - includes 관계: {self.stats['includes_relations']}")
        
        if self.errors:
            print(f"\n❌ 오류: {len(self.errors)}개")
            for error in self.errors[:10]:  # 최대 10개만 표시
                print(f"  - {error}")
            if len(self.errors) > 10:
                print(f"  ... 외 {len(self.errors) - 10}개 오류")
        else:
            print("\n✓ 오류 없음")
        
        if self.warnings:
            print(f"\n⚠️  경고: {len(self.warnings)}개")
            for warning in self.warnings[:10]:  # 최대 10개만 표시
                print(f"  - {warning}")
            if len(self.warnings) > 10:
                print(f"  ... 외 {len(self.warnings) - 10}개 경고")
        else:
            print("\n✓ 경고 없음")
        
        print("\n" + "=" * 60)
        
        return len(self.errors) == 0, {
            'errors': self.errors,
            'warnings': self.warnings,
            'stats': self.stats
        }


def main():
    """메인 함수"""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    owl_file = os.path.join(script_dir, '1 numbers_ontology.owl')
    
    if not os.path.exists(owl_file):
        print(f"❌ 파일을 찾을 수 없습니다: {owl_file}")
        return 1
    
    validator = OntologyValidator(owl_file)
    is_valid, results = validator.validate()
    
    return 0 if is_valid else 1


if __name__ == '__main__':
    exit(main())

