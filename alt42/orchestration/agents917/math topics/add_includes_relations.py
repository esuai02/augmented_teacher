#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OWL 파일의 모든 Subtopic에 5개의 기본 includes 관계를 추가하는 스크립트
"""

import re
import os

# 5개의 기본 includes 관계
REQUIRED_INCLUDES = [
    "http://example.org/adaptive-review#ConceptRemind_Default",
    "http://example.org/adaptive-review#ConceptRebuild_Default",
    "http://example.org/adaptive-review#ConceptCheck_Default",
    "http://example.org/adaptive-review#ExampleQuiz_Default",
    "http://example.org/adaptive-review#RepresentativeType_Default"
]

def add_missing_includes(filepath):
    """OWL 파일의 모든 Subtopic에 누락된 includes 관계 추가"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    modified = False
    
    # Subtopic 블록을 찾아서 수정
    # 패턴: <owl:NamedIndividual ...> ... </owl:NamedIndividual>
    pattern = r'(<owl:NamedIndividual rdf:about="([^"]+)">.*?<rdf:type rdf:resource="[^"]*#Subtopic"/>.*?</owl:NamedIndividual>)'
    
    def replace_subtopic(match):
        nonlocal modified
        full_block = match.group(1)
        topic_id = match.group(2)
        
        # Default 타입 제외
        if 'Default' in topic_id:
            return full_block
        
        # 현재 includes 추출
        current_includes = set(re.findall(r'<ar:includes rdf:resource="([^"]+)"/>', full_block))
        
        # 누락된 includes 찾기
        missing_includes = []
        for include in REQUIRED_INCLUDES:
            if include not in current_includes:
                missing_includes.append(include)
        
        if not missing_includes:
            return full_block
        
        # includes 라인 생성
        includes_lines = []
        for include in missing_includes:
            includes_lines.append(f'    <ar:includes rdf:resource="{include}"/>')
        includes_text = '\n' + '\n'.join(includes_lines)
        
        # includes를 추가할 위치 결정
        # 기존 includes가 있으면 마지막 includes 뒤에, 없으면 description 뒤에
        if '<ar:includes' in full_block:
            # 마지막 includes 라인 뒤에 추가
            last_include_match = list(re.finditer(r'<ar:includes rdf:resource="[^"]+"/>', full_block))[-1]
            insert_pos = last_include_match.end()
            # 줄바꿈 확인
            if full_block[insert_pos:insert_pos+1] == '\n':
                new_block = full_block[:insert_pos] + includes_text + full_block[insert_pos:]
            else:
                new_block = full_block[:insert_pos] + includes_text + '\n' + full_block[insert_pos:]
        else:
            # description 뒤에 추가
            description_match = re.search(r'</ar:description>', full_block)
            if description_match:
                insert_pos = description_match.end()
                # 줄바꿈 확인
                if full_block[insert_pos:insert_pos+1] == '\n':
                    new_block = full_block[:insert_pos] + includes_text + full_block[insert_pos:]
                else:
                    new_block = full_block[:insert_pos] + '\n' + includes_text + full_block[insert_pos:]
            else:
                # description이 없으면 closing tag 앞에
                closing_tag_pos = full_block.rfind('</owl:NamedIndividual>')
                new_block = full_block[:closing_tag_pos] + includes_text + '\n' + full_block[closing_tag_pos:]
        
        modified = True
        topic_name = topic_id.split('#')[-1] if '#' in topic_id else topic_id
        print(f"  - {topic_name}: {len(missing_includes)}개 includes 추가")
        
        return new_block
    
    # 모든 Subtopic 블록을 찾아서 수정
    new_content = re.sub(pattern, replace_subtopic, content, flags=re.DOTALL)
    
    if modified:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"{filepath}: 수정 완료")
    else:
        print(f"{filepath}: 모든 Subtopic에 필요한 includes가 이미 있습니다.")

if __name__ == '__main__':
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # 모든 OWL 파일 찾기
    owl_files = []
    for filename in os.listdir(script_dir):
        if filename.endswith('_ontology.owl'):
            owl_files.append(os.path.join(script_dir, filename))
    
    owl_files.sort()
    
    print(f"총 {len(owl_files)}개의 OWL 파일을 처리합니다.\n")
    
    for filepath in owl_files:
        try:
            print(f"처리 중: {os.path.basename(filepath)}")
            add_missing_includes(filepath)
            print()
        except Exception as e:
            import traceback
            print(f"{filepath} 처리 중 오류: {e}")
            traceback.print_exc()
            print()

