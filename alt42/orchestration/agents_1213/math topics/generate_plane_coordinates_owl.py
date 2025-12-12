# -*- coding: utf-8 -*-
"""12 평면좌표.md를 기반으로 OWL 파일 생성"""

import re
import html

def escape_xml(text):
    """XML 특수문자 이스케이프"""
    return html.escape(text)

def to_iri(name):
    """한글 주제명을 IRI로 변환"""
    # 특수문자 제거 및 언더스코어로 변환
    name = name.replace(' ', '_').replace('(', '_').replace(')', '_').replace('，', '_').replace(',', '_')
    name = name.replace('×', '_').replace('÷', '_').replace('^', '').replace('ⁿ', 'n')
    name = name.replace('-', '_').replace('_', '_')
    # 앞뒤 언더스코어 제거
    name = name.strip('_')
    # 연속된 언더스코어를 하나로
    while '__' in name:
        name = name.replace('__', '_')
    # 한글은 그대로 유지 (IRI는 한글 지원)
    return name

def generate_description(topic_name, stage_name):
    """description 생성"""
    # stage_name에서 학년 정보 추출
    if "고등수학" in stage_name or "기하와 벡터" in stage_name:
        level = "고등"
    else:
        level = "고등"  # 기본값
    
    base_desc = f"{stage_name}({level}) — {topic_name}의 개념을 이해하고 문제를 해결할 수 있다."
    return base_desc

# 마크다운 파일 파싱
md_file = "12 평면좌표.md"
with open(md_file, 'r', encoding='utf-8') as f:
    content = f.read()

# 주제 추출
topics = []
current_stage = None
current_stage_num = None

lines = content.split('\n')
i = 0
while i < len(lines):
    line = lines[i].strip()
    
    # 단계 감지
    stage_match = re.match(r'(\d+)단계\s*:\s*(.+?)\s*\((.+?)\)', line)
    if stage_match:
        current_stage_num = int(stage_match.group(1))
        current_stage = stage_match.group(2).strip()
        i += 1
        continue
    
    # 목차 섹션 감지
    if line == '# 목차':
        i += 1
        # 다음 줄부터 주제 읽기
        while i < len(lines):
            topic_line = lines[i].strip()
            if not topic_line or topic_line.startswith('#'):
                break
            
            # 주제명과 URL 추출
            match = re.match(r'(.+?)\s*:\s*개념노트\s*\((https://[^\s)]+)\)', topic_line)
            if match:
                topic_name = match.group(1).strip()
                url = match.group(2).strip()
                topics.append({
                    'name': topic_name,
                    'stage': current_stage_num,
                    'stage_name': current_stage,
                    'url': url
                })
            i += 1
        continue
    
    i += 1

# OWL 파일 생성
owl_file = "12 plane_coordinates_ontology.owl"
with open(owl_file, 'w', encoding='utf-8') as f:
    # 헤더
    f.write('<?xml version="1.0" encoding="UTF-8"?>\n')
    f.write('<rdf:RDF\n')
    f.write('    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"\n')
    f.write('    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"\n')
    f.write('    xmlns:owl="http://www.w3.org/2002/07/owl#"\n')
    f.write('    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"\n')
    f.write('    xmlns:ar="http://example.org/adaptive-review#"\n')
    f.write('    xml:base="http://example.org/adaptive-review">\n\n')
    
    # Ontology 선언
    f.write('  <owl:Ontology rdf:about="http://example.org/adaptive-review">\n')
    f.write('    <rdfs:comment xml:lang="ko">대한민국 평면좌표 기반 탄력적 복습 온톨로지 (학년 순서 보장, 활동 포함, 상세 설명)</rdfs:comment>\n')
    f.write('  </owl:Ontology>\n\n')
    
    # Schema 정의
    f.write('  <!-- Schema (minimal, 재사용) -->\n')
    f.write('  <owl:Class rdf:about="http://example.org/adaptive-review#Subtopic"/>\n')
    f.write('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#precedes"/>\n')
    f.write('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#dependsOn"/>\n')
    f.write('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#includes"/>\n')
    f.write('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#stage"/>\n')
    f.write('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#hasURL"/>\n')
    f.write('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#description"/>\n\n')
    
    # 표준 학습활동 인스턴스
    f.write('  <!-- 표준 학습활동 인스턴스 (모든 주제에 포함) -->\n')
    f.write('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRemind_Default"><rdfs:label xml:lang="ko">개념요약</rdfs:label></owl:NamedIndividual>\n')
    f.write('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRebuild_Default"><rdfs:label xml:lang="ko">개념이해하기</rdfs:label></owl:NamedIndividual>\n')
    f.write('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptCheck_Default"><rdfs:label xml:lang="ko">개념체크</rdfs:label></owl:NamedIndividual>\n')
    f.write('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ExampleQuiz_Default"><rdfs:label xml:lang="ko">예제퀴즈</rdfs:label></owl:NamedIndividual>\n')
    f.write('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#RepresentativeType_Default"><rdfs:label xml:lang="ko">대표유형</rdfs:label></owl:NamedIndividual>\n\n')
    
    # 각 주제 정의
    for topic in topics:
        iri_name = to_iri(topic['name'])
        description = generate_description(topic['name'], topic['stage_name'])
        # URL의 &를 &amp;로 변환 (escape_xml 전에)
        url_escaped = topic['url'].replace('&', '&amp;')
        
        f.write(f'  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#{iri_name}">\n')
        f.write(f'    <rdf:type rdf:resource="http://example.org/adaptive-review#Subtopic"/>\n')
        f.write(f'    <rdfs:label xml:lang="ko">{escape_xml(topic["name"])}</rdfs:label>\n')
        f.write(f'    <ar:stage rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">{topic["stage"]}</ar:stage>\n')
        f.write(f'    <ar:hasURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">{url_escaped}</ar:hasURL>\n')
        f.write(f'    <ar:description xml:lang="ko">{escape_xml(description)}</ar:description>\n')
        f.write(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRemind_Default"/>\n')
        f.write(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRebuild_Default"/>\n')
        f.write(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptCheck_Default"/>\n')
        f.write(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ExampleQuiz_Default"/>\n')
        f.write(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#RepresentativeType_Default"/>\n')
        f.write(f'  </owl:NamedIndividual>\n\n')
    
    # precedes 관계 (동일 단원 내 순서)
    f.write('\n  <!-- precedes 관계 (동일 단원 내 순서) -->\n')
    prev_topic = None
    for topic in topics:
        if prev_topic and prev_topic['stage'] == topic['stage']:
            prev_iri = to_iri(prev_topic['name'])
            curr_iri = to_iri(topic['name'])
            f.write(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{prev_iri}">\n')
            f.write(f'    <ar:precedes rdf:resource="http://example.org/adaptive-review#{curr_iri}"/>\n')
            f.write(f'  </rdf:Description>\n\n')
        prev_topic = topic
    
    # dependsOn 관계 (단계 간 선행 학습)
    f.write('\n  <!-- dependsOn 관계 (선행 학습 필요) -->\n')
    for i in range(1, len(topics)):
        if topics[i]['stage'] > topics[i-1]['stage']:
            prev_iri = to_iri(topics[i-1]['name'])
            curr_iri = to_iri(topics[i]['name'])
            f.write(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{curr_iri}">\n')
            f.write(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{prev_iri}"/>\n')
            f.write(f'  </rdf:Description>\n\n')
    
    f.write('</rdf:RDF>\n')

print(f"OWL 파일 생성 완료: {owl_file}")
print(f"총 {len(topics)}개 주제 처리됨")

