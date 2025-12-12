#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OWL 온톨로지 파일 파서
OWL/RDF XML 파일을 파싱하여 시각화용 JSON으로 변환합니다.

사용법:
    python owl_parser.py <input.owl> [output.json]
"""

import xml.etree.ElementTree as ET
import json
import sys
import os
import re
from urllib.parse import unquote

# 네임스페이스 정의
NS = {
    'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'rdfs': 'http://www.w3.org/2000/01/rdf-schema#',
    'owl': 'http://www.w3.org/2002/07/owl#',
    'xsd': 'http://www.w3.org/2001/XMLSchema#',
    'ar': 'http://example.org/adaptive-review#',
    'xml': 'http://www.w3.org/XML/1998/namespace'  # xml:lang 속성용
}

def extract_id_from_uri(uri):
    """URI에서 ID 추출"""
    if not uri:
        return ''
    if '#' in uri:
        return uri.split('#')[-1]
    return uri.split('/')[-1]

def parse_owl_file(owl_path):
    """
    OWL 파일을 파싱하여 노드와 엣지 정보를 추출합니다.
    
    Returns:
        dict: {
            'nodes': [...],
            'links': [...],
            'metadata': {...}
        }
    """
    try:
        tree = ET.parse(owl_path)
        root = tree.getroot()
    except Exception as e:
        print(f"Error: OWL 파일 파싱 실패 - {owl_path}", file=sys.stderr)
        print(f"Error details: {str(e)}", file=sys.stderr)
        return None
    
    nodes = {}
    links = []
    
    # 온톨로지 메타데이터 추출
    metadata = {
        'filename': os.path.basename(owl_path),
        'title': '',
        'comment': ''
    }
    
    ontology = root.find('.//owl:Ontology', NS)
    if ontology is not None:
        comment_elem = ontology.find('.//rdfs:comment', NS)
        if comment_elem is not None:
            metadata['comment'] = comment_elem.text or ''
            metadata['title'] = comment_elem.text or metadata['filename']
    
    # NamedIndividual로 주제 추출
    for individual in root.findall('.//owl:NamedIndividual', NS):
        about = individual.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about')
        if not about:
            continue
        
        topic_id = extract_id_from_uri(about)
        
        # 라벨 추출 (xml:lang 속성 검색)
        label_elem = None
        for label in individual.findall('.//rdfs:label', NS):
            lang_attr = label.get('{http://www.w3.org/XML/1998/namespace}lang')
            if lang_attr == 'ko':
                label_elem = label
                break
        if label_elem is None:
            label_elem = individual.find('.//rdfs:label', NS)
        label = label_elem.text if label_elem is not None else topic_id
        
        # Stage 추출
        stage_elem = individual.find('.//ar:stage', NS)
        stage = int(stage_elem.text) if stage_elem is not None else 0
        
        # URL 추출
        url_elem = individual.find('.//ar:hasURL', NS)
        url = url_elem.text if url_elem is not None else ''
        
        # Description 추출 (xml:lang 속성 검색)
        desc_elem = None
        for desc in individual.findall('.//ar:description', NS):
            lang_attr = desc.get('{http://www.w3.org/XML/1998/namespace}lang')
            if lang_attr == 'ko':
                desc_elem = desc
                break
        if desc_elem is None:
            desc_elem = individual.find('.//ar:description', NS)
        description = desc_elem.text if desc_elem is not None else ''
        
        # 노드 정보 저장
        nodes[topic_id] = {
            'id': topic_id,
            'label': label,
            'stage': stage,
            'url': url,
            'description': description,
            'group': stage  # D3.js에서 그룹으로 사용
        }
    
    # rdf:Description으로 관계 추출
    for desc in root.findall('.//rdf:Description', NS):
        about = desc.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about')
        if not about:
            continue
        
        source_id = extract_id_from_uri(about)
        
        # precedes 관계
        for precedes in desc.findall('.//ar:precedes', NS):
            resource = precedes.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource')
            if resource:
                target_id = extract_id_from_uri(resource)
                links.append({
                    'source': source_id,
                    'target': target_id,
                    'type': 'precedes',
                    'value': 1
                })
        
        # dependsOn 관계
        for depends in desc.findall('.//ar:dependsOn', NS):
            resource = depends.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource')
            if resource:
                target_id = extract_id_from_uri(resource)
                links.append({
                    'source': source_id,
                    'target': target_id,
                    'type': 'dependsOn',
                    'value': 1
                })
    
    # 노드를 리스트로 변환
    nodes_list = list(nodes.values())
    
    # 존재하지 않는 노드 참조 제거
    node_ids = set(node['id'] for node in nodes_list)
    valid_links = []
    for link in links:
        source_id = link.get('source', '')
        target_id = link.get('target', '')
        if source_id in node_ids and target_id in node_ids:
            valid_links.append(link)
    
    result = {
        'nodes': nodes_list,
        'links': valid_links,
        'metadata': metadata
    }
    
    return result

def main():
    if len(sys.argv) < 2:
        print("사용법: python owl_parser.py <input.owl> [output.json]", file=sys.stderr)
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2] if len(sys.argv) > 2 else None
    
    # 한글 파일명 처리: 받은 경로가 잘못되었을 경우 디렉토리에서 파일 찾기
    if not os.path.exists(input_file) and os.path.dirname(input_file):
        dir_path = os.path.dirname(input_file)
        base_name = os.path.basename(input_file)
        
        # 디렉토리에서 유사한 파일명 찾기
        try:
            dir_files = os.listdir(dir_path)
            # 파일명에서 한글 부분이 누락되었을 수 있으므로 패턴 매칭
            for f in dir_files:
                if f.endswith('.owl') and base_name.replace('_ontology.owl', '') in f.replace('_ontology.owl', ''):
                    # 더 정확한 매칭: 숫자로 시작하고 ontology.owl로 끝나는 파일
                    if f.startswith(base_name.split('_')[0] if '_' in base_name else base_name.split()[0]):
                        input_file = os.path.join(dir_path, f)
                        print(f"DEBUG: 파일명 자동 수정: {input_file}", file=sys.stderr)
                        break
        except Exception as e:
            print(f"DEBUG: 디렉토리 검색 실패: {e}", file=sys.stderr)
    
    if not os.path.exists(input_file):
        print(f"Error: 파일을 찾을 수 없습니다 - {input_file}", file=sys.stderr)
        print(f"DEBUG: 현재 디렉토리: {os.getcwd()}", file=sys.stderr)
        if os.path.dirname(input_file):
            try:
                dir_files = os.listdir(os.path.dirname(input_file))
                owl_files = [f for f in dir_files if f.endswith('.owl')]
                print(f"DEBUG: 디렉토리 내 OWL 파일 목록: {owl_files[:10]}", file=sys.stderr)
            except:
                pass
        sys.exit(1)
    
    # OWL 파일 파싱
    result = parse_owl_file(input_file)
    
    if result is None:
        print("Error: 파싱 실패", file=sys.stderr)
        sys.exit(1)
    
    # JSON 출력
    json_output = json.dumps(result, ensure_ascii=False, indent=2)
    
    if output_file:
        try:
            # 디렉토리 존재 확인 및 생성
            output_dir = os.path.dirname(output_file)
            if output_dir and not os.path.exists(output_dir):
                os.makedirs(output_dir, mode=0o755, exist_ok=True)
            
            # 파일 쓰기 시도 (UTF-8 without BOM)
            with open(output_file, 'w', encoding='utf-8', newline='') as f:
                f.write(json_output)
            print(f"Success: JSON 파일 생성 완료 - {output_file}")
            print(f"  노드 수: {len(result['nodes'])}")
            print(f"  링크 수: {len(result['links'])}")
        except PermissionError as e:
            print(f"Error: 파일 쓰기 권한 없음 - {output_file}", file=sys.stderr)
            print(f"Error details: {str(e)}", file=sys.stderr)
            print(f"디렉토리 권한 확인 필요: {os.path.dirname(output_file)}", file=sys.stderr)
            sys.exit(1)
        except Exception as e:
            print(f"Error: 파일 쓰기 실패 - {output_file}", file=sys.stderr)
            print(f"Error details: {str(e)}", file=sys.stderr)
            sys.exit(1)
    else:
        print(json_output)

if __name__ == '__main__':
    main()

