#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
9 미분.md 파일을 기반으로 differential_ontology.owl 파일을 생성하는 스크립트
"""

import re
from urllib.parse import quote

def sanitize_iri(text):
    """한글 텍스트를 IRI-safe한 형태로 변환"""
    # 괄호 제거 및 공백을 언더스코어로
    text = text.replace('(', '').replace(')', '').replace('（', '').replace('）', '')
    text = text.replace(' ', '_').replace('×', '_곱하기_').replace('÷', '_나누기_')
    text = text.replace('→', '_').replace('，', '_').replace(',', '_')
    text = text.replace('∞', 'infinity').replace('/', '_').replace('\\', '_')
    text = text.replace('＞', '_').replace('＜', '_').replace('=', '_')
    text = text.replace('{', '').replace('}', '').replace('｛', '').replace('｝', '')
    # 연속된 언더스코어 제거
    text = re.sub(r'_+', '_', text)
    # 앞뒤 언더스코어 제거
    text = text.strip('_')
    return text

def extract_url_from_line(line):
    """마크다운 라인에서 URL 추출"""
    match = re.search(r'https?://[^\s\)]+', line)
    if match:
        url = match.group(0)
        # XML에서 &를 &amp;로 변환
        url = url.replace('&', '&amp;')
        return url
    return None

def parse_markdown_file(filepath):
    """마크다운 파일을 파싱하여 구조화된 데이터 반환"""
    import os
    print(f"파일 경로: {os.path.abspath(filepath)}")
    print(f"파일 존재: {os.path.exists(filepath)}")
    
    # 파일 크기 확인
    file_size = os.path.getsize(filepath)
    print(f"파일 크기 (바이너리): {file_size} bytes")
    
    # 여러 인코딩 시도
    encodings = ['utf-8', 'utf-8-sig', 'cp949', 'euc-kr']
    content = None
    for enc in encodings:
        try:
            with open(filepath, 'r', encoding=enc) as f:
                content = f.read()
            print(f"성공적으로 읽음 (인코딩: {enc})")
            break
        except Exception as e:
            print(f"인코딩 {enc} 실패: {e}")
            continue
    
    if content is None:
        raise ValueError("파일을 읽을 수 없습니다.")
    
    print(f"읽은 내용 크기: {len(content)} bytes")
    print(f"첫 500자: {repr(content[:500])}")
    
    stages = []
    current_stage = None
    current_subtopics = []
    
    lines = content.split('\n')
    print(f"총 라인 수: {len(lines)}")
    i = 0
    debug_count = 0
    while i < len(lines):
        line = lines[i].strip()
        
        # 단계 감지 (예: "1단계 : 함수의 극한 (수학2)")
        # 더 간단한 패턴으로 시도
        if '단계' in line and ':' in line:
            stage_match = re.match(r'(\d+)단계\s*:\s*(.+?)(?:\s*\([^)]+\))?\s*$', line)
            if stage_match:
                debug_count += 1
            else:
                # 대안 패턴 시도
                stage_match = re.search(r'(\d+)단계\s*:\s*(.+?)(?:\s*\([^)]+\))?', line)
                if stage_match:
                    debug_count += 1
        else:
            stage_match = None
        
        if stage_match:
            # 이전 단계 저장
            if current_stage is not None:
                stages.append({
                    'stage_num': current_stage['stage_num'],
                    'stage_name': current_stage['stage_name'],
                    'subtopics': current_subtopics
                })
            
            stage_num = int(stage_match.group(1))
            stage_name = stage_match.group(2).strip()
            # 괄호 제거 (예: "함수의 극한 (수학2)" -> "함수의 극한")
            stage_name = re.sub(r'\s*\([^)]+\)\s*$', '', stage_name).strip()
            current_stage = {
                'stage_num': stage_num,
                'stage_name': stage_name
            }
            current_subtopics = []
            i += 1
            continue
        
        # 목차 섹션 감지
        if line == '# 목차':
            i += 1
            # 목차 항목들 읽기
            while i < len(lines):
                line = lines[i].strip()
                # 다음 섹션(진단목록 등)을 만나면 중단
                if line.startswith('# 진단목록') or (line.startswith('#') and line != '# 목차'):
                    break
                if line == '---':
                    break
                if not line:
                    i += 1
                    continue
                
                # URL이 있는 라인인지 확인
                url = extract_url_from_line(line)
                if url:
                    # 주제명 추출 (URL 앞의 텍스트)
                    # [[로 시작할 수도 있고, 일반 텍스트일 수도 있음
                    # 패턴: [[주제명 또는 주제명 : 개념노트 (URL)
                    topic_match = re.match(r'^(\[\[)?(.+?)(\]\])?\s*:\s*개념노트', line)
                    if topic_match:
                        topic_name = topic_match.group(2).strip()
                        current_subtopics.append({
                            'name': topic_name,
                            'url': url
                        })
                i += 1
            continue
        
        i += 1
    
    # 마지막 단계 저장
    if current_stage is not None:
        stages.append({
            'stage_num': current_stage['stage_num'],
            'stage_name': current_stage['stage_name'],
            'subtopics': current_subtopics
        })
    
    # 디버깅 정보
    if len(stages) == 0:
        print(f"경고: 파싱된 단계가 없습니다. 디버그 카운트: {debug_count}")
        # 처음 몇 줄 출력
        print("파일 처음 20줄:")
        for idx, l in enumerate(lines[:20]):
            print(f"  {idx+1}: {repr(l)}")
    
    return stages

def generate_description(topic_name, stage_name, stage_num):
    """주제에 대한 description 생성"""
    # 단계별 기본 설명 템플릿
    stage_templates = {
        1: '함수의 극한',
        2: '함수의 연속',
        3: '미분계수와 도함수',
        4: '도함수의 활용',
        5: '도함수의 활용',
        6: '도함수의 활용',
        7: '지수함수와 로그함수의 미분',
        8: '삼각함수의 미분',
        9: '여러가지 미분법',
        10: '여러가지 함수의 도함수의 활용',
        11: '여러가지 함수의 도함수의 활용'
    }
    
    base_name = stage_templates.get(stage_num, stage_name)
    
    # 기본 설명 생성 (ontology_principles.md의 형식에 맞춤)
    # [핵심개념] [성취기준] [학습활동] [적용예시] 형식
    return f'{base_name}(수학2) — {topic_name}의 개념을 이해하고 문제에 적용할 수 있다.'

def generate_ontology(stages=None):
    """OWL 파일 생성"""
    if stages is None:
        import os
        script_dir = os.path.dirname(os.path.abspath(__file__))
        md_file = os.path.join(script_dir, '9 미분.md')
        stages = parse_markdown_file(md_file)
    
    output = []
    output.append('<?xml version="1.0" encoding="UTF-8"?>')
    output.append('<rdf:RDF')
    output.append('    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"')
    output.append('    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"')
    output.append('    xmlns:owl="http://www.w3.org/2002/07/owl#"')
    output.append('    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"')
    output.append('    xmlns:ar="http://example.org/adaptive-review#"')
    output.append('    xml:base="http://example.org/adaptive-review">')
    output.append('')
    output.append('  <owl:Ontology rdf:about="http://example.org/adaptive-review">')
    output.append('    <rdfs:comment xml:lang="ko">대한민국 미분 영역 기반 탄력적 복습 온톨로지 (학년 순서 보장, 활동 포함, 상세 설명)</rdfs:comment>')
    output.append('  </owl:Ontology>')
    output.append('')
    output.append('  <!-- Schema (minimal, 재사용) -->')
    output.append('  <owl:Class rdf:about="http://example.org/adaptive-review#Subtopic"/>')
    output.append('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#precedes"/>')
    output.append('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#dependsOn"/>')
    output.append('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#includes"/>')
    output.append('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#stage"/>')
    output.append('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#hasURL"/>')
    output.append('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#description"/>')
    output.append('')
    output.append('  <!-- 표준 학습활동 인스턴스 (모든 주제에 포함) -->')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRemind_Default"><rdfs:label xml:lang="ko">개념요약</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRebuild_Default"><rdfs:label xml:lang="ko">개념이해하기</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptCheck_Default"><rdfs:label xml:lang="ko">개념체크</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ExampleQuiz_Default"><rdfs:label xml:lang="ko">예제퀴즈</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#RepresentativeType_Default"><rdfs:label xml:lang="ko">대표유형</rdfs:label></owl:NamedIndividual>')
    output.append('')
    
    # 모든 Subtopic 생성
    all_subtopics = []
    for stage in stages:
        stage_num = stage['stage_num']
        stage_name = stage['stage_name']
        for subtopic in stage['subtopics']:
            topic_name = subtopic['name']
            url = subtopic['url']
            iri = sanitize_iri(topic_name)
            description = generate_description(topic_name, stage_name, stage_num)
            
            all_subtopics.append({
                'iri': iri,
                'name': topic_name,
                'stage': stage_num,
                'url': url,
                'description': description
            })
            
            output.append(f'  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#{iri}">')
            output.append(f'    <rdf:type rdf:resource="http://example.org/adaptive-review#Subtopic"/>')
            output.append(f'    <rdfs:label xml:lang="ko">{topic_name}</rdfs:label>')
            output.append(f'    <ar:stage rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">{stage_num}</ar:stage>')
            output.append(f'    <ar:hasURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">{url}</ar:hasURL>')
            output.append(f'    <ar:description xml:lang="ko">{description}</ar:description>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRemind_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRebuild_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptCheck_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ExampleQuiz_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#RepresentativeType_Default"/>')
            output.append('  </owl:NamedIndividual>')
            output.append('')
    
    # precedes 관계 생성 (동일 stage 내 순서)
    output.append('  <!-- precedes 관계 (동일 단원 내 순서) -->')
    output.append('')
    for stage in stages:
        stage_num = stage['stage_num']
        subtopics_in_stage = [s for s in all_subtopics if s['stage'] == stage_num]
        
        for i in range(len(subtopics_in_stage) - 1):
            current_iri = subtopics_in_stage[i]['iri']
            next_iri = subtopics_in_stage[i + 1]['iri']
            
            output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{current_iri}">')
            output.append(f'    <ar:precedes rdf:resource="http://example.org/adaptive-review#{next_iri}"/>')
            output.append('  </rdf:Description>')
            output.append('')
    
    # dependsOn 관계 생성 (단계 간 선행 관계)
    output.append('  <!-- dependsOn 관계 (선행 학습 필요) -->')
    output.append('')
    for i in range(1, len(stages)):
        prev_stage = stages[i-1]
        current_stage = stages[i]
        
        if prev_stage['subtopics'] and current_stage['subtopics']:
            prev_last_iri = sanitize_iri(prev_stage['subtopics'][-1]['name'])
            current_first_iri = sanitize_iri(current_stage['subtopics'][0]['name'])
            
            output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{current_first_iri}">')
            output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{prev_last_iri}"/>')
            output.append('  </rdf:Description>')
            output.append('')
    
    output.append('</rdf:RDF>')
    
    return '\n'.join(output)

if __name__ == '__main__':
    import os
    # 절대 경로 사용
    md_file = r'C:\1 Project\augmented_teacher\alt42\orchestration\agents\math topics\9 미분.md'
    stages = parse_markdown_file(md_file)
    
    # 디버깅: 파싱 결과 확인
    print(f'총 {len(stages)}개 단계 파싱됨')
    for stage in stages:
        print(f"  단계 {stage['stage_num']}: {stage['stage_name']} - {len(stage['subtopics'])}개 주제")
        if len(stage['subtopics']) > 0:
            print(f"    첫 주제: {stage['subtopics'][0]['name']}")
    
    ontology_xml = generate_ontology(stages)
    output_file = '9 differential_ontology.owl'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(ontology_xml)
    print(f'{output_file} 파일이 생성되었습니다.')

