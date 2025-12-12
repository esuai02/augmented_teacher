#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
6 방정식.md를 기반으로 ontology 파일 생성 스크립트
"""

import re
from urllib.parse import quote

# 방정식.md의 내용을 파싱하여 ontology 생성
equations_data = {
    "1단계": {
        "name": "일차방정식의 풀이",
        "level": "중등수학 1-1",
        "stage": 1,
        "subtopics": [
            {"name": "등식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53178&page=1&quizid=86105"},
            {"name": "방정식과 항등식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53179&page=1&quizid=86106"},
            {"name": "등식의 성질", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53180&page=1&quizid=86107"},
            {"name": "등식의 성질을 이용한 방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53181&page=1&quizid=86108"},
            {"name": "이항", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53182&page=1&quizid=86109"},
            {"name": "일차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53183&page=1&quizid=86110"},
            {"name": "일차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53184&page=1&quizid=86111"},
            {"name": "복잡한 일차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53185&page=1&quizid=86112"},
        ]
    },
    "2단계": {
        "name": "일차방정식의 활용",
        "level": "중등수학 1-1",
        "stage": 2,
        "subtopics": [
            {"name": "일차방정식의 활용(주제)", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53186&page=1&quizid=86113"},
            {"name": "거리, 속력, 시간에 대한 문제", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53187&page=1&quizid=86114"},
            {"name": "소금물의 농도에 대한 문제", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53188&page=1&quizid=86115"},
            {"name": "규칙을 찾는 문제", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=7&cmid=53189&page=1&quizid=86116"},
        ]
    },
    "3단계": {
        "name": "연립일차방정식의 풀이",
        "level": "중등수학 2-1",
        "stage": 3,
        "subtopics": [
            {"name": "미지수가 2개인 일차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53310&page=1&quizid=86227"},
            {"name": "미지수가 2개인 일차방정식의 해", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53311&page=1&quizid=86228"},
            {"name": "미지수가 2개인 연립일차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53312&page=1&quizid=86229"},
            {"name": "대입법", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53313&page=1&quizid=86230"},
            {"name": "가감법", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53314&page=1&quizid=86231"},
            {"name": "복잡한 연립방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53315&page=1&quizid=86232"},
            {"name": "A=B=C 꼴의 연립방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53316&page=1&quizid=86233"},
            {"name": "해가 특수한 연립방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=7&cmid=53317&page=1&quizid=86234"},
        ]
    },
    "4단계": {
        "name": "연립일차방정식의 활용",
        "level": "중등수학 2-1",
        "stage": 4,
        "subtopics": [
            {"name": "연립일차방정식의 활용(주제)", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=8&cmid=53318&page=1&quizid=86235"},
            {"name": "거리, 속력, 시간에 대한 문제", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=8&cmid=53319&page=1&quizid=86236"},
            {"name": "농도에 대한 문제", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=68&nch=8&cmid=53320&page=1&quizid=86237"},
        ]
    },
    "5단계": {
        "name": "이차방정식의 풀이",
        "level": "중등수학 3-1",
        "stage": 5,
        "subtopics": [
            {"name": "이차방정식의 뜻", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53464&page=1&quizid=86359"},
            {"name": "이차방정식의 해", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53465&page=1&quizid=86360"},
            {"name": "인수분해를 이용한 이차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53466&page=1&quizid=86361"},
            {"name": "이차방정식의 중근", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53467&page=1&quizid=86362"},
            {"name": "제곱근을 이용한 이차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53468&page=1&quizid=86363"},
            {"name": "완전제곱식을 이용한 이차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53469&page=1&quizid=86364"},
            {"name": "이차방정식의 근의 공식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53470&page=1&quizid=86365"},
            {"name": "x의 계수가 짝수인 이차방정식의 근의 공식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53471&page=1&quizid=86366"},
            {"name": "복잡한 이차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53472&page=1&quizid=86367"},
            {"name": "공통부분이 있는 이차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=7&cmid=53473&page=1&quizid=86368"},
        ]
    },
    "6단계": {
        "name": "이차방정식의 활용",
        "level": "중등수학 3-1",
        "stage": 6,
        "subtopics": [
            {"name": "이차방정식의 근의 개수", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=8&cmid=53474&page=1&quizid=86369"},
            {"name": "이차방정식의 근과 계수의 관계", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=8&cmid=53475&page=1&quizid=86370"},
            {"name": "계수가 유리수인 이차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=8&cmid=53476&page=1&quizid=86371"},
            {"name": "이차방정식 구하기", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=8&cmid=53477&page=1&quizid=86372"},
            {"name": "이차방정식의 활용", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=71&nch=8&cmid=53478&page=1&quizid=86373"},
        ]
    },
    "7단계": {
        "name": "이차방정식",
        "level": "고등수학 상",
        "stage": 7,
        "subtopics": [
            {"name": "방정식과 일차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48497&page=1&quizid=84469"},
            {"name": "방정식 ax=b의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48498&page=1&quizid=84470"},
            {"name": "절댓값 기호를 포함한 방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48499&page=1&quizid=84471"},
            {"name": "이차방정식의 뜻과 근의 종류", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48500&page=1&quizid=84472"},
            {"name": "이차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48501&page=1&quizid=84473"},
            {"name": "이차방정식의 근의 판별", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48502&page=1&quizid=84474"},
            {"name": "계수가 허수인 이차방정식의 근의 판별", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48503&page=1&quizid=84475"},
            {"name": "이차식이 완전제곱식이 되도록 하는 조건", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48504&page=1&quizid=84476"},
            {"name": "이차방정식의 근과 계수의 관계", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48505&page=1&quizid=84477"},
            {"name": "이차식의 인수분해", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48506&page=1&quizid=84478"},
            {"name": "두 수를 근으로 갖는 이차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48507&page=1&quizid=84479"},
            {"name": "이차방정식의 켤레식의 성질", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48508&page=1&quizid=84480"},
            {"name": "이차방정식의 실근의 부호", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=4&cmid=48509&page=1&quizid=84481"},
        ]
    },
    "8단계": {
        "name": "이차방정식과 이차함수",
        "level": "고등수학 상",
        "stage": 8,
        "subtopics": [
            {"name": "다항함수", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48510&page=1&quizid=84482"},
            {"name": "이차함수의 그래프", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48511&page=1&quizid=84483"},
            {"name": "이차함수의 계수의 부호 결정", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48512&page=1&quizid=84484"},
            {"name": "이차함수의 식의 결정", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48513&page=1&quizid=84485"},
            {"name": "이차방정식과 이차함수의 관계", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48514&page=1&quizid=84486"},
            {"name": "이차방정식의 해와 이차함수의 그래프", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48515&page=1&quizid=84487"},
            {"name": "이차함수의 그래프와 직선의 위치 관계", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48516&page=1&quizid=84488"},
            {"name": "방정식의 실근과 그래프의 교점의 관계", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48517&page=1&quizid=84489"},
            {"name": "이차방정식의 근의 분리", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48518&page=1&quizid=84490"},
            {"name": "이차함수의 최대·최소", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48519&page=1&quizid=84491"},
            {"name": "제한된 범위에서의 이차함수의 최대·최소", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48520&page=1&quizid=84492"},
            {"name": "완전제곱식 또는 판별식을 이용한 최대·최소", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=5&cmid=48521&page=1&quizid=84493"},
        ]
    },
    "9단계": {
        "name": "삼차방정식과 사차방정식",
        "level": "고등수학 상",
        "stage": 9,
        "subtopics": [
            {"name": "고차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48522&page=1&quizid=84494"},
            {"name": "인수정리, 치환을 이용한 고차방정식의 풀이", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48523&page=1&quizid=84495"},
            {"name": "복이차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48524&page=1&quizid=84496"},
            {"name": "상반방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48525&page=1&quizid=84497"},
            {"name": "삼차방정식의 근과 계수의 관계", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48526&page=1&quizid=84498"},
            {"name": "세 수를 근으로 갖는 삼차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48527&page=1&quizid=84499"},
            {"name": "켤레근의 성질", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48885&page=1&quizid=84500"},
            {"name": "방정식 x³=1의 허근 ω의 성질", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=6&cmid=48886&page=1&quizid=84501"},
        ]
    },
    "10단계": {
        "name": "연립방정식",
        "level": "고등수학 상",
        "stage": 10,
        "subtopics": [
            {"name": "미지수가 2개인 연립일차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48887&page=1&quizid=84502"},
            {"name": "미지수가 3개인 연립일차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48888&page=1&quizid=84503"},
            {"name": "미지수가 2개인 연립이차방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48889&page=1&quizid=84504"},
            {"name": "대칭식으로 이루어진 연립방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48890&page=1&quizid=84505"},
            {"name": "공통근", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48891&page=1&quizid=84506"},
            {"name": "부정방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48892&page=1&quizid=84507"},
            {"name": "정수 조건의 부정방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48893&page=1&quizid=84508"},
            {"name": "실수 조건의 부정방정식", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48894&page=1&quizid=84509"},
            {"name": "이차방정식의 정수근", "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=59&nch=7&cmid=48895&page=1&quizid=84510"},
        ]
    },
}

def to_iri(name):
    """한글 이름을 IRI-safe한 형태로 변환"""
    # 공백을 언더스코어로, 특수문자 제거
    iri = name.replace(" ", "_").replace("(", "").replace(")", "").replace("·", "_")
    iri = iri.replace(",", "").replace("=", "").replace("x", "x").replace("³", "3")
    return iri

def escape_xml(text):
    """XML 특수문자 이스케이프"""
    return text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")

def generate_description(name, level):
    """description 생성"""
    if "일차방정식" in name and "풀이" in name:
        return f"방정식(중등) — 일차방정식의 기본 개념과 등식의 성질을 이용한 풀이 방법을 이해하고 적용할 수 있다."
    elif "일차방정식" in name and "활용" in name:
        return f"방정식(중등) — 일차방정식을 실생활 문제에 적용하여 해결할 수 있다."
    elif "연립일차방정식" in name and "풀이" in name:
        return f"방정식(중등) — 연립일차방정식의 개념과 대입법, 가감법을 이용한 풀이 방법을 이해하고 적용할 수 있다."
    elif "연립일차방정식" in name and "활용" in name:
        return f"방정식(중등) — 연립일차방정식을 실생활 문제에 적용하여 해결할 수 있다."
    elif "이차방정식" in name and "풀이" in name and level == "중등수학 3-1":
        return f"방정식(중등) — 이차방정식의 개념과 다양한 풀이 방법(인수분해, 제곱근, 완전제곱식, 근의 공식)을 이해하고 적용할 수 있다."
    elif "이차방정식" in name and "활용" in name and level == "중등수학 3-1":
        return f"방정식(중등) — 이차방정식의 근과 계수의 관계를 이해하고 실생활 문제에 적용할 수 있다."
    elif "이차방정식" in name and level == "고등수학 상":
        return f"방정식(고등) — 이차방정식의 고등 수준 개념과 복소수 해를 포함한 풀이 방법을 이해하고 적용할 수 있다."
    elif "이차함수" in name:
        return f"방정식(고등) — 이차방정식과 이차함수의 관계를 이해하고 그래프를 활용하여 문제를 해결할 수 있다."
    elif "삼차방정식" in name or "사차방정식" in name:
        return f"방정식(고등) — 고차방정식의 개념과 인수정리, 치환을 이용한 풀이 방법을 이해하고 적용할 수 있다."
    elif "연립방정식" in name and level == "고등수학 상":
        return f"방정식(고등) — 다양한 형태의 연립방정식과 부정방정식을 이해하고 풀이할 수 있다."
    else:
        return f"방정식({level}) — {name}의 개념과 풀이 방법을 이해하고 적용할 수 있다."

# Ontology 파일 생성
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
output.append('    <rdfs:comment xml:lang="ko">대한민국 방정식 영역 기반 탄력적 복습 온톨로지 (학년 순서 보장, 활동 포함, 상세 설명)</rdfs:comment>')
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

# 모든 소주제 생성
all_subtopics = []
for stage_key, stage_data in equations_data.items():
    for subtopic in stage_data["subtopics"]:
        iri_name = to_iri(subtopic["name"])
        all_subtopics.append({
            "iri": iri_name,
            "name": subtopic["name"],
            "url": subtopic["url"],
            "stage": stage_data["stage"],
            "level": stage_data["level"]
        })

# 각 소주제를 ontology로 변환
for subtopic in all_subtopics:
    iri = subtopic["iri"]
    name = subtopic["name"]
    url = escape_xml(subtopic["url"])
    stage = subtopic["stage"]
    level = subtopic["level"]
    description = generate_description(name, level)
    
    output.append(f'  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#{iri}">')
    output.append(f'    <rdf:type rdf:resource="http://example.org/adaptive-review#Subtopic"/>')
    output.append(f'    <rdfs:label xml:lang="ko">{escape_xml(name)}</rdfs:label>')
    output.append(f'    <ar:stage rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">{stage}</ar:stage>')
    output.append(f'    <ar:hasURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">{url}</ar:hasURL>')
    output.append(f'    <ar:description xml:lang="ko">{escape_xml(description)}</ar:description>')
    output.append(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRemind_Default"/>')
    output.append(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRebuild_Default"/>')
    output.append(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptCheck_Default"/>')
    output.append(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#ExampleQuiz_Default"/>')
    output.append(f'    <ar:includes rdf:resource="http://example.org/adaptive-review#RepresentativeType_Default"/>')
    output.append('  </owl:NamedIndividual>')
    output.append('')

# precedes 관계 생성 (동일 단원 내 순서)
for stage_key, stage_data in equations_data.items():
    subtopics = stage_data["subtopics"]
    for i in range(len(subtopics) - 1):
        current_iri = to_iri(subtopics[i]["name"])
        next_iri = to_iri(subtopics[i + 1]["name"])
        output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{next_iri}">')
        output.append(f'    <ar:precedes rdf:resource="http://example.org/adaptive-review#{current_iri}"/>')
        output.append('  </rdf:Description>')
        output.append('')

# dependsOn 관계 생성 (선행 학습 필요)
# 일차방정식의 풀이 -> 일차방정식의 활용
if len(equations_data["1단계"]["subtopics"]) > 0 and len(equations_data["2단계"]["subtopics"]) > 0:
    last_1 = to_iri(equations_data["1단계"]["subtopics"][-1]["name"])
    first_2 = to_iri(equations_data["2단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_2}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_1}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 일차방정식의 활용 -> 연립일차방정식의 풀이
if len(equations_data["2단계"]["subtopics"]) > 0 and len(equations_data["3단계"]["subtopics"]) > 0:
    last_2 = to_iri(equations_data["2단계"]["subtopics"][-1]["name"])
    first_3 = to_iri(equations_data["3단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_3}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_2}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 연립일차방정식의 풀이 -> 연립일차방정식의 활용
if len(equations_data["3단계"]["subtopics"]) > 0 and len(equations_data["4단계"]["subtopics"]) > 0:
    last_3 = to_iri(equations_data["3단계"]["subtopics"][-1]["name"])
    first_4 = to_iri(equations_data["4단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_4}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_3}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 연립일차방정식의 활용 -> 이차방정식의 풀이
if len(equations_data["4단계"]["subtopics"]) > 0 and len(equations_data["5단계"]["subtopics"]) > 0:
    last_4 = to_iri(equations_data["4단계"]["subtopics"][-1]["name"])
    first_5 = to_iri(equations_data["5단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_5}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_4}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 이차방정식의 풀이 -> 이차방정식의 활용
if len(equations_data["5단계"]["subtopics"]) > 0 and len(equations_data["6단계"]["subtopics"]) > 0:
    last_5 = to_iri(equations_data["5단계"]["subtopics"][-1]["name"])
    first_6 = to_iri(equations_data["6단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_6}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_5}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 이차방정식의 활용 -> 이차방정식 (고등)
if len(equations_data["6단계"]["subtopics"]) > 0 and len(equations_data["7단계"]["subtopics"]) > 0:
    last_6 = to_iri(equations_data["6단계"]["subtopics"][-1]["name"])
    first_7 = to_iri(equations_data["7단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_7}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_6}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 이차방정식 (고등) -> 이차방정식과 이차함수
if len(equations_data["7단계"]["subtopics"]) > 0 and len(equations_data["8단계"]["subtopics"]) > 0:
    last_7 = to_iri(equations_data["7단계"]["subtopics"][-1]["name"])
    first_8 = to_iri(equations_data["8단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_8}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_7}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 이차방정식과 이차함수 -> 삼차방정식과 사차방정식
if len(equations_data["8단계"]["subtopics"]) > 0 and len(equations_data["9단계"]["subtopics"]) > 0:
    last_8 = to_iri(equations_data["8단계"]["subtopics"][-1]["name"])
    first_9 = to_iri(equations_data["9단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_9}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_8}"/>')
    output.append('  </rdf:Description>')
    output.append('')

# 삼차방정식과 사차방정식 -> 연립방정식 (고등)
if len(equations_data["9단계"]["subtopics"]) > 0 and len(equations_data["10단계"]["subtopics"]) > 0:
    last_9 = to_iri(equations_data["9단계"]["subtopics"][-1]["name"])
    first_10 = to_iri(equations_data["10단계"]["subtopics"][0]["name"])
    output.append(f'  <rdf:Description rdf:about="http://example.org/adaptive-review#{first_10}">')
    output.append(f'    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{last_9}"/>')
    output.append('  </rdf:Description>')
    output.append('')

output.append('</rdf:RDF>')

# 파일 저장
with open('6 방정식_ontology.owl', 'w', encoding='utf-8') as f:
    f.write('\n'.join(output))

print("Ontology 파일이 생성되었습니다: 6 방정식_ontology.owl")

