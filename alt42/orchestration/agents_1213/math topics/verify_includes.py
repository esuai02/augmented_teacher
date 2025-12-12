#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""모든 Subtopic에 5개의 includes가 있는지 확인"""

import re
import os

REQUIRED_INCLUDES = [
    "ConceptRemind_Default",
    "ConceptRebuild_Default",
    "ConceptCheck_Default",
    "ExampleQuiz_Default",
    "RepresentativeType_Default"
]

def verify_file(filepath):
    """파일의 모든 Subtopic에 5개 includes가 있는지 확인"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Subtopic 블록 찾기
    pattern = r'<owl:NamedIndividual rdf:about="([^"]+)">.*?<rdf:type rdf:resource="[^"]*#Subtopic"/>.*?</owl:NamedIndividual>'
    matches = re.finditer(pattern, content, re.DOTALL)
    
    issues = []
    total_subtopics = 0
    
    for match in matches:
        topic_id = match.group(1)
        block = match.group(0)
        
        # Default 제외
        if 'Default' in topic_id:
            continue
        
        total_subtopics += 1
        
        # includes 개수 확인
        includes = re.findall(r'<ar:includes rdf:resource="[^"]*#([^"]+)"/>', block)
        includes_set = set(includes)
        
        missing = []
        for req in REQUIRED_INCLUDES:
            if req not in includes_set:
                missing.append(req)
        
        if missing:
            topic_name = topic_id.split('#')[-1] if '#' in topic_id else topic_id
            issues.append((topic_name, len(includes), missing))
    
    return total_subtopics, issues

if __name__ == '__main__':
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    owl_files = [f for f in os.listdir(script_dir) if f.endswith('_ontology.owl')]
    owl_files.sort()
    
    total_issues = 0
    for filename in owl_files:
        filepath = os.path.join(script_dir, filename)
        total, issues = verify_file(filepath)
        
        if issues:
            print(f"\n{filename}: {len(issues)}/{total} Subtopic에 문제 발견")
            for topic_name, count, missing in issues:
                print(f"  - {topic_name}: {count}개 includes (누락: {', '.join(missing)})")
            total_issues += len(issues)
        else:
            print(f"{filename}: 모든 {total}개 Subtopic에 5개 includes 있음")
    
    if total_issues == 0:
        print("\n✅ 모든 파일의 모든 Subtopic에 5개의 includes가 정상적으로 연결되어 있습니다!")
    else:
        print(f"\n⚠️ 총 {total_issues}개의 Subtopic에 includes가 누락되어 있습니다.")

