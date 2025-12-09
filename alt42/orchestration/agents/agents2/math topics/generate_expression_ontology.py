#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
식의 계산 영역 ontology 생성 스크립트
4 식의 계산.md 파일을 기반으로 ontology_principles.md의 규칙에 따라 생성
"""

import re
import html

def escape_xml(text):
    """XML 특수문자 이스케이프"""
    return html.escape(text)

def sanitize_iri(text):
    """한글 및 특수문자를 IRI 안전한 형태로 변환"""
    # 공백을 언더스코어로
    text = text.replace(' ', '_')
    # 괄호 제거
    text = text.replace('(', '').replace(')', '').replace('（', '').replace('）', '')
    text = text.replace('[', '').replace(']', '')
    text = text.replace('{', '').replace('}', '')
    # 특수문자 제거
    text = re.sub(r'[^\w가-힣_]', '', text)
    return text

def extract_url(line):
    """URL 추출"""
    match = re.search(r'https://[^\s)]+', line)
    return match.group(0) if match else ''

def generate_description(topic_name, stage_info, diagnosis_list):
    """description 생성 - ontology_principles.md 규칙에 따라"""
    stage_name = stage_info.split(' : ')[1] if ' : ' in stage_info else stage_info
    
    # 핵심 개념 추출
    core_concept = topic_name
    
    # 학습활동 추출 (주제에 따라)
    learning_activity = "계산 방법을 이해하고 적용한다"
    if "식" in topic_name or "방정식" in topic_name:
        learning_activity = "식의 구조를 이해하고 계산한다"
    elif "인수분해" in topic_name:
        learning_activity = "인수분해 방법을 이해하고 적용한다"
    elif "공식" in topic_name:
        learning_activity = "공식을 이해하고 활용한다"
    elif "나눗셈" in topic_name or "곱셈" in topic_name:
        learning_activity = "연산 규칙을 이해하고 계산한다"
    
    # 적용 예시
    application = "수학적 문제 해결에 활용한다"
    
    desc = f"{stage_name} — [핵심개념] {core_concept}  [학습활동] {learning_activity}.  [적용예시] {application}."
    
    return desc

# 4 식의 계산.md 파일 내용을 파싱하여 구조화
topics_data = [
    # 1단계: 문자와 식 (중등수학 1-1)
    {
        'stage': 1,
        'stage_name': '문자와 식 (중등수학 1-1)',
        'topics': [
            ('문자를 사용한 식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53169&page=1&quizid=86096'),
            ('곱셈 기호와 나눗셈 기호의 생략', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53170&page=1&quizid=86097'),
            ('식의 값', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53171&page=1&quizid=86098'),
            ('다항식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53172&page=1&quizid=86099'),
            ('일차식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53173&page=1&quizid=86100'),
            ('단항식의 수의 곱셈, 나눗셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53174&page=1&quizid=86101'),
            ('일차식과 수의 곱셈, 나눗셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53175&page=1&quizid=86102'),
            ('동류항의 계산', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53176&page=1&quizid=86103'),
            ('일차식의 덧셈, 뺄셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53177&page=1&quizid=86104'),
        ]
    },
    # 2단계: 일차방정식의 풀이 (중등수학 1-1)
    {
        'stage': 2,
        'stage_name': '일차방정식의 풀이 (중등수학 1-1)',
        'topics': [
            ('등식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53178&page=1&quizid=86105'),
            ('방정식과 항등식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53179&page=1&quizid=86106'),
            ('등식의 성질', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53180&page=1&quizid=86107'),
            ('등식의 성질을 이용한 방정식의 풀이', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53181&page=1&quizid=86108'),
            ('이항', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53182&page=1&quizid=86109'),
            ('일차방정식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53183&page=1&quizid=86110'),
            ('일차방정식의 풀이', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53184&page=1&quizid=86111'),
            ('복잡한 일차방정식의 풀이', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53185&page=1&quizid=86112'),
        ]
    },
    # 3단계: 일차방정식의 활용 (중등수학1-1)
    {
        'stage': 3,
        'stage_name': '일차방정식의 활용 (중등수학1-1)',
        'topics': [
            ('일차방정식의 활용(주제)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53186&page=1&quizid=86113'),
            ('거리, 속력, 시간에 대한 문제', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53187&page=1&quizid=86114'),
            ('소금물의 농도에 대한 문제', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53188&page=1&quizid=86115'),
            ('규칙을 찾는 문제', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53189&page=1&quizid=86116'),
        ]
    },
    # 4단계: 단항식의 계산 (중등수학2-1)
    {
        'stage': 4,
        'stage_name': '단항식의 계산 (중등수학2-1)',
        'topics': [
            ('지수법칙(1)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=2&cmid=53290&page=1&quizid=86207'),
            ('지수법칙(2)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=2&cmid=53291&page=1&quizid=86208'),
            ('지수법칙(3)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=2&cmid=53292&page=1&quizid=86209'),
            ('자릿수 구하기', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=2&cmid=53293&page=1&quizid=86210'),
            ('단항식의 곱셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=2&cmid=53294&page=1&quizid=86211'),
            ('단항식의 나눗셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=2&cmid=53295&page=1&quizid=86212'),
        ]
    },
    # 5단계: 다항식의 계산 (중등수학2-1)
    {
        'stage': 5,
        'stage_name': '다항식의 계산 (중등수학2-1)',
        'topics': [
            ('다항식의 덧셈과 뺄셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=3&cmid=53296&page=1&quizid=86213'),
            ('이차식의 덧셈과 뺄셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=3&cmid=53297&page=1&quizid=86214'),
            ('단항식과 다항식의 곱셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=3&cmid=53298&page=1&quizid=86215'),
            ('다항식과 단항식의 나눗셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=3&cmid=53299&page=1&quizid=86216'),
        ]
    },
    # 6단계: 다항식의 곱셈 (중등수학 3-1)
    {
        'stage': 6,
        'stage_name': '다항식의 곱셈 (중등수학 3-1)',
        'topics': [
            ('단항식과 다항식의 곱셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53298&page=1&quizid=86215'),
            ('다항식과 단항식의 나눗셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53299&page=1&quizid=86216'),
            ('다항식과 다항식의 곱셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53300&page=1&quizid=86217'),
            ('곱셈 공식 （a±b）^2', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53301&page=1&quizid=86218'),
            ('곱셈공식 (a+b)(a-b)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53302&page=1&quizid=86219'),
            ('곱셈 공식 (x+a)(x+b)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53303&page=1&quizid=86220'),
            ('곱셈 공식 (ax+b)(cx+d)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53304&page=1&quizid=86221'),
            ('치환을 이용한 식의 전개', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53305&page=1&quizid=86222'),
            ('곱셈 공식을 이용한 수의 계산', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53306&page=1&quizid=86223'),
            ('식의 대입', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53307&page=1&quizid=86224'),
            ('곱셈 공식의 변형을 이용한 식의 계산', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53309&page=1&quizid=86226'),
            ('등식의 변형', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=1&cmid=53308&page=1&quizid=86225'),
        ]
    },
    # 7단계: 인수분해 (중등수학 3-1)
    {
        'stage': 7,
        'stage_name': '인수분해 (중등수학 3-1)',
        'topics': [
            ('인수분해의 뜻', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53453&page=1&quizid=86348'),
            ('공통인수', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53454&page=1&quizid=86349'),
            ('인수분해 공식; a²±2ab+b²', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53455&page=1&quizid=86350'),
            ('인수분해 공식; a²b²', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53456&page=1&quizid=86351'),
            ('인수분해 공식; x²+(a+b)x+ab', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53457&page=1&quizid=86352'),
            ('인수분해 공식; acx²+(ad+bc)x+bd', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53458&page=1&quizid=86353'),
            ('복잡한 식의 인수분해(1)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53459&page=1&quizid=86354'),
            ('복잡한 식의 인수분해(2)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53460&page=1&quizid=86355'),
            ('복잡한 식의 인수분해(3)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53461&page=1&quizid=86356'),
            ('인수분해 방법', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53462&page=1&quizid=86357'),
            ('인수분해 공식의 활용', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=6&cmid=53463&page=1&quizid=86358'),
        ]
    },
    # 8단계: 다항식의 연산 (고등수학 상)
    {
        'stage': 8,
        'stage_name': '다항식의 연산 (고등수학 상)',
        'topics': [
            ('다항식에 대한 용어', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48458&page=1&studentid=2&quizid=84431'),
            ('다항식의 정리방법', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48459&page=1&studentid=2&quizid=84432'),
            ('다항식의 덧셈과 뺄셈', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48460&page=1&studentid=2&quizid=84433'),
            ('다항식의 덧셈에 대한 연산법칙', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48461&page=1&studentid=2&quizid=84434'),
            ('지수법칙', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48462&page=1&studentid=2&quizid=84435'),
            ('식의 전개', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48463&page=1&studentid=2&quizid=84436'),
            ('다항식의 곱셈에 대한 연산법칙', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48464&page=1&studentid=2&quizid=84437'),
            ('곱셈 공식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48465&page=1&studentid=2&quizid=84438'),
            ('곱셈 공식의 변형', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48466&page=1&studentid=2&quizid=84439'),
            ('(다항식)÷(단항식)의 계산', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48467&page=1&studentid=2&quizid=84440'),
            ('(다항식)÷(다항식)의 계산', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48468&page=1&studentid=2&quizid=84441'),
            ('다항식의 나눗셈에 대한 등식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48469&page=1&studentid=2&quizid=84442'),
            ('조립제법', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48470&page=1&studentid=2&quizid=84443'),
            ('조립제법의 확장', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=1&cmid=48471&page=1&studentid=2&quizid=84444'),
        ]
    },
    # 9단계: 나머지정리와 인수분해 (고등수학 상)
    {
        'stage': 9,
        'stage_name': '나머지정리와 인수분해 (고등수학 상)',
        'topics': [
            ('항등식과 방정식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48472&page=1&quizid=84445'),
            ('항등식의 성질', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48473&page=1&quizid=84446'),
            ('미정계수법', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48474&page=1&quizid=84447'),
            ('나머지정리', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48475&page=1&quizid=84448'),
            ('인수정리', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48476&page=1&quizid=84558'),
            ('인수분해(주제)', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48477&page=1&quizid=84449'),
            ('인수분해 공식', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48478&page=1&quizid=84450'),
            ('공통부분이 있는 식의 인수분해', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48479&page=1&quizid=84451'),
            ('복이차식의 인수분해', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48480&page=1&quizid=84452'),
            ('여러 개의 문자를 포함하고 있는 식의 인수분해', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48481&page=1&quizid=84453'),
            ('인수정리를 이용한 인수분해', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48482&page=1&quizid=84454'),
            ('인수분해 방법의 흐름도', 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=2&cmid=48483&page=1&quizid=84455'),
        ]
    },
]

def generate_ontology():
    """Ontology 파일 생성"""
    output = []
    
    # XML 헤더 및 네임스페이스
    output.append('<?xml version="1.0" encoding="UTF-8"?>')
    output.append('<rdf:RDF')
    output.append('    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"')
    output.append('    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"')
    output.append('    xmlns:owl="http://www.w3.org/2002/07/owl#"')
    output.append('    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"')
    output.append('    xmlns:ar="http://example.org/adaptive-review#"')
    output.append('    xml:base="http://example.org/adaptive-review">')
    output.append('')
    
    # Ontology 선언
    output.append('  <owl:Ontology rdf:about="http://example.org/adaptive-review">')
    output.append('    <rdfs:comment xml:lang="ko">대한민국 식의 계산 영역 기반 탄력적 복습 온톨로지 (학년 순서 보장, 활동 포함, 상세 설명)</rdfs:comment>')
    output.append('  </owl:Ontology>')
    output.append('')
    
    # Schema 정의
    output.append('  <!-- Schema (minimal, 재사용) -->')
    output.append('  <owl:Class rdf:about="http://example.org/adaptive-review#Subtopic"/>')
    output.append('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#precedes"/>')
    output.append('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#dependsOn"/>')
    output.append('  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#includes"/>')
    output.append('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#stage"/>')
    output.append('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#hasURL"/>')
    output.append('  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#description"/>')
    output.append('')
    
    # 표준 학습활동 인스턴스
    output.append('  <!-- 표준 학습활동 인스턴스 (모든 주제에 포함) -->')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRemind_Default"><rdfs:label xml:lang="ko">개념요약</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRebuild_Default"><rdfs:label xml:lang="ko">개념이해하기</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptCheck_Default"><rdfs:label xml:lang="ko">개념체크</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ExampleQuiz_Default"><rdfs:label xml:lang="ko">예제퀴즈</rdfs:label></owl:NamedIndividual>')
    output.append('  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#RepresentativeType_Default"><rdfs:label xml:lang="ko">대표유형</rdfs:label></owl:NamedIndividual>')
    output.append('')
    
    # 각 Subtopic 생성
    all_topics = []
    for stage_data in topics_data:
        stage = stage_data['stage']
        stage_name = stage_data['stage_name']
        
        for topic_name, url in stage_data['topics']:
            iri_name = sanitize_iri(topic_name)
            description = generate_description(topic_name, stage_name, [])
            
            output.append(f'  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#{iri_name}">')
            output.append(f'    <rdf:type rdf:resource="http://example.org/adaptive-review#Subtopic"/>')
            output.append(f'    <rdfs:label xml:lang="ko">{escape_xml(topic_name)}</rdfs:label>')
            output.append(f'    <ar:stage rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">{stage}</ar:stage>')
            output.append(f'    <ar:hasURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">{escape_xml(url)}</ar:hasURL>')
            output.append(f'    <ar:description xml:lang="ko">{escape_xml(description)}</ar:description>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRemind_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRebuild_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptCheck_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#ExampleQuiz_Default"/>')
            output.append('    <ar:includes rdf:resource="http://example.org/adaptive-review#RepresentativeType_Default"/>')
            output.append('  </owl:NamedIndividual>')
            output.append('')
            
            all_topics.append((stage, iri_name, topic_name))
    
    # precedes 관계 생성 (동일 stage 내 순서)
    output.append('  <!-- precedes 관계 (동일 stage 내 순서) -->')
    for stage_data in topics_data:
        stage = stage_data['stage']
        topics_in_stage = [(sanitize_iri(topic_name), topic_name) for topic_name, _ in stage_data['topics']]
        
        for i in range(len(topics_in_stage) - 1):
            current_iri = topics_in_stage[i][0]
            next_iri = topics_in_stage[i + 1][0]
            
            output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{current_iri}">')
            output.append(f'    <ar:precedes rdf:resource="http://example.org/adaptive-review#{next_iri}"/>')
            output.append('  </rdf:Description>')
            output.append('')
    
    # dependsOn 관계 생성 (단계 간 선행 관계)
    output.append('  <!-- dependsOn 관계 (단계 간 선행 학습 필요) -->')
    # 각 stage의 첫 번째 주제가 이전 stage의 마지막 주제에 의존
    for i in range(1, len(topics_data)):
        prev_stage = topics_data[i-1]
        current_stage = topics_data[i]
        
        prev_last_topic = sanitize_iri(prev_stage['topics'][-1][0])
        current_first_topic = sanitize_iri(current_stage['topics'][0][0])
        
        output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{current_first_topic}">')
        output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{prev_last_topic}"/>')
        output.append('  </rdf:Description>')
        output.append('')
    
    # 닫기 태그
    output.append('</rdf:RDF>')
    
    return '\n'.join(output)

if __name__ == '__main__':
    ontology_xml = generate_ontology()
    
    output_file = '4 expression_calculation_ontology.owl'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(ontology_xml)
    
    print(f"Ontology 파일이 생성되었습니다: {output_file}")

