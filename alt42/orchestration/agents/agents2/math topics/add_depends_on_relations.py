#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OWL 파일의 소주제들 간 논리적 의존관계(dependsOn)를 자동으로 추가하는 스크립트
내용의 상관관계를 분석하여 이전 과정의 소주제들과 유기적으로 연결
"""

import re
import os
from collections import defaultdict

def extract_keywords(text):
    """텍스트에서 수학 개념 키워드 추출"""
    keywords = []
    text_lower = text.lower()
    
    # 기본 개념 키워드
    concept_keywords = [
        '일차', '이차', '삼차', '고차',
        '방정식', '부등식', '함수',
        '순열', '조합', '확률', '경우의수', '경우의 수',
        '미분', '적분', '극한',
        '벡터', '좌표', '평면', '공간',
        '집합', '명제', '수열',
        '도형', '원', '삼각형', '사각형',
        '통계', '평균', '분산', '표준편차'
    ]
    
    for keyword in concept_keywords:
        if keyword in text_lower:
            keywords.append(keyword)
    
    # 복합 개념
    if '연립' in text_lower:
        keywords.append('연립')
    if '활용' in text_lower:
        keywords.append('활용')
    if '그래프' in text_lower:
        keywords.append('그래프')
    if '성질' in text_lower:
        keywords.append('성질')
    
    return keywords

def extract_core_concept(text):
    """텍스트에서 핵심 개념 키워드 추출"""
    concepts = []
    text_lower = text.lower()
    
    # 핵심 개념 키워드
    core_keywords = {
        '경우의수': ['경우의수', '경우의 수'],
        '확률': ['확률'],
        '순열': ['순열'],
        '조합': ['조합'],
        '일차': ['일차'],
        '이차': ['이차'],
        '방정식': ['방정식'],
        '부등식': ['부등식'],
        '함수': ['함수'],
        '미분': ['미분'],
        '적분': ['적분'],
        '벡터': ['벡터'],
        '도형': ['도형', '삼각형', '사각형', '원'],
        '통계': ['통계', '평균', '분산']
    }
    
    for concept, keywords in core_keywords.items():
        if any(kw in text_lower for kw in keywords):
            concepts.append(concept)
    
    return concepts

def find_dependencies(current_topic, all_topics, current_index):
    """현재 토픽이 의존해야 할 이전 토픽들 찾기"""
    dependencies = []
    current_label = current_topic['label'].lower()
    current_desc = current_topic.get('description', '').lower()
    current_text = current_label + ' ' + current_desc
    current_stage = current_topic['stage']
    current_concepts = extract_core_concept(current_text)
    
    # 모든 이전 토픽 검토 (같은 단계도 포함, 단지 순서상 이전만)
    for i, prev_topic in enumerate(all_topics[:current_index]):
        prev_label = prev_topic['label'].lower()
        prev_desc = prev_topic.get('description', '').lower()
        prev_text = prev_label + ' ' + prev_desc
        prev_stage = prev_topic['stage']
        prev_concepts = extract_core_concept(prev_text)
        
        dependency_score = 0
        
        # 1. 같은 핵심 개념을 가진 토픽 (중등 -> 고등 연결)
        common_concepts = set(current_concepts) & set(prev_concepts)
        if common_concepts:
            # 같은 개념이면 강한 의존
            if prev_stage < current_stage:
                # 중등 -> 고등 같은 개념 연결
                dependency_score += 20
            elif prev_stage == current_stage - 1:
                # 바로 이전 단계의 같은 개념
                dependency_score += 15
            else:
                # 같은 개념이지만 단계 차이가 있음
                dependency_score += 10
        
        # 2. 확률 -> 경우의 수 (매우 강한 의존)
        if '확률' in current_concepts and '경우의수' in prev_concepts:
            dependency_score += 18
            # 특히 유사한 패턴이면 더 높은 점수
            if '사건' in current_text and '사건' in prev_text:
                dependency_score += 5
            if '뽑' in current_text and '뽑' in prev_text:
                dependency_score += 5
        
        # 3. 순열/조합 -> 경우의 수 (중등 -> 고등 연결)
        if ('순열' in current_concepts or '조합' in current_concepts) and '경우의수' in prev_concepts:
            dependency_score += 15
            # "일렬로 세우는 경우의 수" -> "순열"
            if '순열' in current_concepts and ('일렬' in prev_text or '세우' in prev_text):
                dependency_score += 10
            # "대표를 뽑는 경우의 수" -> "조합"
            if '조합' in current_concepts and ('대표' in prev_text or '뽑' in prev_text):
                dependency_score += 10
        
        # 4. 같은 개념의 심화 버전 (유사한 패턴)
        if '확률' in current_concepts and '경우의수' in prev_concepts:
            # 유사한 패턴 매칭
            current_pattern = current_text.replace('확률', '').replace('의', '').strip()
            prev_pattern = prev_text.replace('경우의수', '').replace('경우의 수', '').replace('의', '').strip()
            # 공통 키워드가 많으면 높은 점수
            common_words = set(current_pattern.split()) & set(prev_pattern.split())
            if len(common_words) >= 2:
                dependency_score += 12
        
        # 5. 합의 법칙/곱의 법칙 -> 기본 경우의 수
        if ('합의' in current_text or '곱의' in current_text) and '법칙' in current_text:
            if '경우의수' in prev_concepts:
                dependency_score += 12
        
        # 6. 같은 개념 계열 (일차 -> 이차 등)
        if '이차' in current_concepts and '일차' in prev_concepts:
            if any(k in current_concepts for k in ['방정식', '부등식', '함수']):
                if any(k in prev_concepts for k in ['방정식', '부등식', '함수']):
                    dependency_score += 15
        
        # 7. 연립 -> 단일
        if '연립' in current_text:
            if '일차' in prev_concepts and any(k in prev_concepts for k in ['방정식', '부등식']) and '연립' not in prev_text:
                dependency_score += 12
        
        # 8. 활용 -> 기본 개념
        if '활용' in current_text:
            base_keywords = [k for k in current_text.split() if k not in ['활용', '의'] and len(k) > 1]
            if any(k in prev_text for k in base_keywords):
                dependency_score += 10
        
        # 9. 중등 -> 고등 같은 개념 계열 (stage 1 -> stage 3 등)
        if prev_stage < current_stage - 1:
            # 단계 차이가 2 이상인 경우, 같은 개념이면 연결
            if common_concepts:
                dependency_score += 8
        
        # 10. 바로 이전 단계의 관련 토픽들
        if prev_stage == current_stage - 1:
            # 같은 개념 키워드가 있으면 의존
            if common_concepts:
                dependency_score += 8
            # 관련 개념 키워드
            related_concepts = ['사건', '경우', '확률', '순열', '조합', '법칙']
            if any(concept in current_text and concept in prev_text for concept in related_concepts):
                dependency_score += 5
        
        # 11. 바로 이전 단계의 마지막 토픽 (단계 간 기본 연결)
        if prev_stage == current_stage - 1:
            # 같은 단계의 마지막 토픽인지 확인
            is_last_in_stage = True
            for j in range(i + 1, current_index):
                if all_topics[j]['stage'] == prev_stage:
                    is_last_in_stage = False
                    break
            
            if is_last_in_stage:
                dependency_score += 4
        
        if dependency_score >= 5:
            dependencies.append({
                'topic_id': prev_topic['id'],
                'score': dependency_score,
                'label': prev_topic['label'],
                'stage': prev_stage
            })
    
    # 점수 순으로 정렬하고 상위 5개 선택 (더 많은 연결 허용)
    dependencies.sort(key=lambda x: x['score'], reverse=True)
    return [d['topic_id'] for d in dependencies[:5]]

def parse_owl_file(filepath):
    """OWL 파일을 파싱하여 토픽 정보 추출"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    topics = []
    
    # Subtopic 패턴 찾기
    pattern = r'<owl:NamedIndividual rdf:about="([^"]+)">.*?<rdf:type rdf:resource="[^"]*#Subtopic"/>.*?<rdfs:label xml:lang="ko">([^<]+)</rdfs:label>.*?<ar:stage rdf:datatype="[^"]+">(\d+)</ar:stage>.*?</owl:NamedIndividual>'
    
    matches = re.finditer(pattern, content, re.DOTALL)
    for match in matches:
        topic_id = match.group(1)
        label = match.group(2)
        stage = int(match.group(3))
        
        # Default 타입 제외
        if 'Default' in topic_id:
            continue
        
        # description 추출
        description_match = re.search(r'<ar:description xml:lang="ko">([^<]+)</ar:description>', match.group(0))
        description = description_match.group(1) if description_match else ''
        
        topics.append({
            'id': topic_id,
            'label': label,
            'stage': stage,
            'description': description,
            'position': match.start()
        })
    
    # position 순서대로 정렬
    topics.sort(key=lambda x: x['position'])
    
    return topics, content

def add_depends_on_relations(filepath):
    """OWL 파일에 dependsOn 관계 추가"""
    topics, content = parse_owl_file(filepath)
    
    if len(topics) < 2:
        print(f"{filepath}: 토픽이 2개 미만이어서 관계를 추가할 수 없습니다.")
        return
    
    # 기존 dependsOn 관계 추출 및 통합
    existing_depends_dict = {}  # {topic_id: [dep_id1, dep_id2, ...]}
    depends_pattern = r'<rdf:Description rdf:about="([^"]+)">(.*?)</rdf:Description>'
    
    # 기존 dependsOn 섹션 찾기
    depends_section_start = content.find('<!-- dependsOn 관계')
    depends_section_end = content.find('</rdf:RDF>', depends_section_start) if depends_section_start != -1 else -1
    
    if depends_section_start != -1 and depends_section_end != -1:
        depends_section = content[depends_section_start:depends_section_end]
        for match in re.finditer(depends_pattern, depends_section, re.DOTALL):
            topic_id = match.group(1)
            block_content = match.group(2)
            dep_matches = re.findall(r'<ar:dependsOn rdf:resource="([^"]+)"/>', block_content)
            if dep_matches:
                if topic_id not in existing_depends_dict:
                    existing_depends_dict[topic_id] = []
                for dep_id in dep_matches:
                    if dep_id not in existing_depends_dict[topic_id]:
                        existing_depends_dict[topic_id].append(dep_id)
    
    # 새로운 dependsOn 관계 생성 (토픽별로 그룹화)
    new_depends_dict = existing_depends_dict.copy()  # 기존 것 유지
    
    for i, topic in enumerate(topics):
        if topic['stage'] == 1:  # 첫 단계는 의존관계 없음
            continue
        
        dependencies = find_dependencies(topic, topics, i)
        
        if topic['id'] not in new_depends_dict:
            new_depends_dict[topic['id']] = []
        
        for dep_id in dependencies:
            # 중복 확인
            if dep_id not in new_depends_dict[topic['id']]:
                new_depends_dict[topic['id']].append(dep_id)
    
    if not new_depends_dict:
        print(f"{filepath}: 추가할 dependsOn 관계가 없습니다.")
        return
    
    # dependsOn 관계 블록 생성 (토픽별로 하나의 Description 블록)
    depends_blocks = []
    for topic_id, dep_ids in new_depends_dict.items():
        dep_lines = '\n'.join([f'    <ar:dependsOn rdf:resource="{dep_id}"/>' for dep_id in dep_ids])
        block = f'''  <rdf:Description rdf:about="{topic_id}">
{dep_lines}
  </rdf:Description>'''
        depends_blocks.append(block)
    
    depends_text = '\n\n'.join(depends_blocks)
    
    # 통계 정보
    total_relations = sum(len(deps) for deps in new_depends_dict.values())
    new_depends = [{'from': tid, 'to': did, 'topic_name': next((t['label'] for t in topics if t['id'] == tid), '')} 
                   for tid, deps in new_depends_dict.items() for did in deps]
    
    # 파일에 추가
    # 기존 dependsOn 섹션을 찾아서 교체
    if '<!-- dependsOn 관계' in content:
        # 기존 dependsOn 섹션 찾기
        depends_section_start = content.find('<!-- dependsOn 관계')
        depends_section_end = content.find('</rdf:RDF>', depends_section_start)
        
        if depends_section_start != -1 and depends_section_end != -1:
            # 기존 섹션을 새 것으로 교체
            new_content = (
                content[:depends_section_start] +
                '  <!-- dependsOn 관계 (내용의 논리적 의존관계) -->\n' +
                depends_text + '\n\n' +
                content[depends_section_end:]
            )
        else:
            # </rdf:RDF> 앞에 추가
            new_content = content.replace('</rdf:RDF>', f'\n\n  <!-- dependsOn 관계 (내용의 논리적 의존관계) -->\n{depends_text}\n</rdf:RDF>', 1)
    else:
        # precedes 관계 뒤에 dependsOn 섹션 추가
        if '<!-- precedes 관계' in content or '<ar:precedes' in content:
            # 마지막 precedes 관계 뒤에 추가
            last_precedes = content.rfind('<ar:precedes')
            if last_precedes != -1:
                # 해당 Description 블록의 끝 찾기
                depends_end = content.find('</rdf:Description>', last_precedes) + len('</rdf:Description>')
                # 다음 줄바꿈 찾기
                next_newline = content.find('\n', depends_end)
                if next_newline != -1:
                    insert_pos = next_newline + 1
                else:
                    insert_pos = depends_end
                
                new_content = (
                    content[:insert_pos] +
                    '\n\n  <!-- dependsOn 관계 (내용의 논리적 의존관계) -->\n' +
                    depends_text + '\n' +
                    content[insert_pos:]
                )
            else:
                new_content = content.replace('</rdf:RDF>', f'\n\n  <!-- dependsOn 관계 (내용의 논리적 의존관계) -->\n{depends_text}\n</rdf:RDF>', 1)
        else:
            # </rdf:RDF> 앞에 추가
            new_content = content.replace('</rdf:RDF>', f'\n\n  <!-- dependsOn 관계 (내용의 논리적 의존관계) -->\n{depends_text}\n</rdf:RDF>', 1)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    added_count = total_relations - sum(len(deps) for deps in existing_depends_dict.values())
    print(f"{filepath}: 총 {total_relations}개의 dependsOn 관계 (기존 {sum(len(deps) for deps in existing_depends_dict.values())}개 + 신규 {added_count}개)")
    for topic_id, deps in list(new_depends_dict.items())[:5]:  # 처음 5개만 출력
        topic_name = next((t['label'] for t in topics if t['id'] == topic_id), topic_id.split('#')[-1])
        print(f"  - {topic_name[:30]}: {len(deps)}개 의존관계")

if __name__ == '__main__':
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # 모든 OWL 파일 처리 (또는 특정 파일만)
    import sys
    
    if len(sys.argv) > 1:
        # 명령줄 인자로 파일명 지정
        target_files = [os.path.join(script_dir, sys.argv[1])]
    else:
        # 모든 OWL 파일 찾기
        target_files = []
        for filename in os.listdir(script_dir):
            if filename.endswith('_ontology.owl'):
                target_files.append(os.path.join(script_dir, filename))
        target_files.sort()
    
    print(f"총 {len(target_files)}개의 파일을 처리합니다.\n")
    
    for filepath in target_files:
        try:
            print(f"처리 중: {os.path.basename(filepath)}")
            add_depends_on_relations(filepath)
            print()
        except Exception as e:
            import traceback
            print(f"{filepath} 처리 중 오류: {e}")
            traceback.print_exc()
            print()

