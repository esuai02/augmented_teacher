#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OWL 파일에 precedes 관계를 추가하는 스크립트
"""

import re
import xml.etree.ElementTree as ET
from collections import defaultdict

def parse_owl_file(filepath):
    """OWL 파일을 파싱하여 토픽 정보 추출"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 토픽 정보 추출 (Subtopic 타입만)
    topics = []
    
    # owl:NamedIndividual 패턴 찾기 (Subtopic 타입만)
    pattern = r'<owl:NamedIndividual rdf:about="([^"]+)">.*?<rdf:type rdf:resource="[^"]*#Subtopic"/>.*?<ar:stage rdf:datatype="[^"]+">(\d+)</ar:stage>'
    
    matches = re.finditer(pattern, content, re.DOTALL)
    for match in matches:
        topic_id = match.group(1)
        stage = int(match.group(2))
        
        # Default 타입 제외
        if 'Default' in topic_id:
            continue
        
        # 토픽의 위치 찾기
        topic_start = match.start()
        topics.append({
            'id': topic_id,
            'stage': stage,
            'position': topic_start
        })
    
    # position 순서대로 정렬 (파일에서 정의된 순서)
    topics.sort(key=lambda x: x['position'])
    
    return topics, content

def add_precedes_relations(filepath):
    """OWL 파일에 precedes 관계 추가"""
    topics, content = parse_owl_file(filepath)
    
    if len(topics) < 2:
        print(f"{filepath}: 토픽이 2개 미만이어서 관계를 추가할 수 없습니다.")
        return
    
    # 관계 정의 생성
    relations = []
    for i in range(len(topics) - 1):
        current_topic = topics[i]
        next_topic = topics[i + 1]
        
        # 토픽 ID에서 마지막 부분만 추출 (예: #x→a일_때의_함수의_수렴)
        current_id = current_topic['id'].split('#')[-1] if '#' in current_topic['id'] else current_topic['id']
        next_id = next_topic['id'].split('#')[-1] if '#' in next_topic['id'] else next_topic['id']
        
        relation_block = f'''  <rdf:Description rdf:about="{current_topic['id']}">
    <ar:precedes rdf:resource="{next_topic['id']}"/>
  </rdf:Description>
'''
        relations.append(relation_block)
    
    # 마지막 토픽 정의 뒤에 관계 추가
    # </rdf:RDF> 앞에 추가
    if '</rdf:RDF>' in content:
        # 이미 관계가 있는지 확인
        if '<rdf:Description rdf:about=' in content and '<ar:precedes' in content:
            print(f"{filepath}: 이미 관계가 존재합니다. 건너뜁니다.")
            return
        
        # 마지막 토픽의 </owl:NamedIndividual> 찾기
        # 여러 패턴 시도
        patterns = [
            r'(</owl:NamedIndividual>\s*\n\s*</rdf:RDF>)',
            r'(</owl:NamedIndividual>\s*\n\s*\n\s*</rdf:RDF>)',
            r'(</owl:NamedIndividual>\s*</rdf:RDF>)',
        ]
        
        new_content = None
        for pattern in patterns:
            match = re.search(pattern, content)
            if match:
                relations_text = '\n'.join(relations)
                new_content = re.sub(
                    pattern,
                    f'</owl:NamedIndividual>\n\n{relations_text}</rdf:RDF>',
                    content,
                    count=1
                )
                break
        
        if new_content is None:
            # 마지막 방법: </rdf:RDF> 앞에 직접 추가
            relations_text = '\n'.join(relations)
            # 빈 줄이 있으면 유지, 없으면 추가
            if content.rstrip().endswith('</rdf:RDF>'):
                new_content = content.replace('</rdf:RDF>', f'\n{relations_text}</rdf:RDF>', 1)
            else:
                new_content = content.replace('</rdf:RDF>', f'\n\n{relations_text}</rdf:RDF>', 1)
    else:
        print(f"{filepath}: </rdf:RDF> 태그를 찾을 수 없습니다.")
        return
    
    # 파일 저장
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print(f"{filepath}: {len(relations)}개의 관계를 추가했습니다.")

if __name__ == '__main__':
    import os
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    files_to_fix = [
        os.path.join(script_dir, '7 부등식_ontology.owl'),
        os.path.join(script_dir, '8 functions_ontology.owl'),
        os.path.join(script_dir, '11 평면도형_ontology.owl'),
        os.path.join(script_dir, '12 plane_coordinates_ontology.owl'),
        os.path.join(script_dir, '13 solid_figures_ontology.owl'),
        os.path.join(script_dir, '14 space_coordinates_ontology.owl'),
        os.path.join(script_dir, '15 vector_ontology.owl'),
        os.path.join(script_dir, '17 statistics_ontology.owl'),
    ]
    
    for filepath in files_to_fix:
        try:
            print(f"처리 중: {filepath}")
            add_precedes_relations(filepath)
        except Exception as e:
            import traceback
            print(f"{filepath} 처리 중 오류: {e}")
            traceback.print_exc()

