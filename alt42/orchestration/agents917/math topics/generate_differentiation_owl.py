# -*- coding: utf-8 -*-
"""
9 미분.md를 기반으로 differentiation_ontology.owl 파일을 생성하는 스크립트
"""

import re

# 9 미분.md의 내용을 기반으로 주제 데이터 정의
topics_data = [
    # 1단계: 함수의 극한 (수학2)
    {"name": "x→a일_때의_함수의_수렴", "label": "x→a일 때의 함수의 수렴", "stage": 1, 
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49060&page=1&quizid=84705",
     "description": "함수의 극한(수학2) — x가 a에 한없이 가까워질 때 함수값이 특정 값에 수렴하는 개념을 이해하고, 극한의 정의를 활용하여 수렴 여부를 판단할 수 있다."},
    {"name": "x→a일_때의_함수의_발산", "label": "x→a일 때의 함수의 발산", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49061&page=1&quizid=84706",
     "description": "함수의 극한(수학2) — x가 a에 가까워질 때 함수값이 발산하는 경우를 이해하고, 발산의 유형(양의 무한대, 음의 무한대, 진동)을 구분할 수 있다."},
    {"name": "x→∞，x→-∞일_때의_함수의_수렴", "label": "x→∞，x→-∞일 때의 함수의 수렴", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49062&page=1&quizid=84707",
     "description": "함수의 극한(수학2) — x가 양의 무한대 또는 음의 무한대로 갈 때 함수값이 수렴하는 경우를 이해하고 계산할 수 있다."},
    {"name": "x→∞，x→-∞일_때의_함수의_발산", "label": "x→∞，x→-∞일 때의 함수의 발산", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49063&page=1&quizid=84708",
     "description": "함수의 극한(수학2) — x가 무한대로 갈 때 함수값이 발산하는 경우를 이해하고 판단할 수 있다."},
    {"name": "우극한_좌극한", "label": "우극한 좌극한", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49064&page=1&quizid=84709",
     "description": "함수의 극한(수학2) — 우극한과 좌극한의 개념을 이해하고, 양쪽 극한값이 같을 때 극한값이 존재함을 판단할 수 있다."},
    {"name": "극한값의_존재", "label": "극한값의 존재", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49065&page=1&quizid=84710",
     "description": "함수의 극한(수학2) — 극한값이 존재하는 조건을 이해하고, 우극한과 좌극한을 비교하여 극한값의 존재 여부를 판단할 수 있다."},
    {"name": "함수의_극한에_대한_성질", "label": "함수의 극한에 대한 성질", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49066&page=1&quizid=84711",
     "description": "함수의 극한(수학2) — 극한의 사칙연산 성질과 합성함수의 극한 성질을 이해하고 활용하여 극한값을 계산할 수 있다."},
    {"name": "함수의_극한에_대한_성질과_관련된_거짓_명제", "label": "함수의 극한에 대한 성질과 관련된 거짓 명제", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49067&page=1&quizid=84712",
     "description": "함수의 극한(수학2) — 극한의 성질을 잘못 적용하는 경우를 이해하고, 조건을 확인하여 올바르게 극한을 계산할 수 있다."},
    {"name": "0/0꼴의_함수의_극한", "label": "0/0꼴의 함수의 극한", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49068&page=1&quizid=84713",
     "description": "함수의 극한(수학2) — 0/0꼴의 부정형 극한을 인수분해, 유리화, 로피탈의 정리 등을 활용하여 계산할 수 있다."},
    {"name": "∞/∞꼴의_함수의_극한", "label": "∞/∞꼴의 함수의 극한", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49069&page=1&quizid=84714",
     "description": "함수의 극한(수학2) — ∞/∞꼴의 부정형 극한을 최고차항으로 나누거나 로피탈의 정리를 활용하여 계산할 수 있다."},
    {"name": "∞-∞꼴의_함수의_극한", "label": "∞-∞꼴의 함수의 극한", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49070&page=1&quizid=84715",
     "description": "함수의 극한(수학2) — ∞-∞꼴의 부정형 극한을 통분하거나 유리화하여 계산할 수 있다."},
    {"name": "∞×0꼴의_함수의_극한", "label": "∞×0꼴의 함수의 극한", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49071&page=1&quizid=84716",
     "description": "함수의 극한(수학2) — ∞×0꼴의 부정형 극한을 변형하여 0/0꼴이나 ∞/∞꼴로 바꾸어 계산할 수 있다."},
    {"name": "미정계수의_결정", "label": "미정계수의 결정", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49072&page=1&quizid=84717",
     "description": "함수의 극한(수학2) — 극한값이 주어졌을 때 미정계수를 결정하는 방법을 이해하고 적용할 수 있다."},
    {"name": "함수의_극한의_대소_관계", "label": "함수의 극한의 대소 관계", "stage": 1,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=1&cmid=49073&page=1&quizid=84718",
     "description": "함수의 극한(수학2) — 함수의 극한값 사이의 대소 관계를 이해하고, 샌드위치 정리를 활용하여 극한값을 구할 수 있다."},
    
    # 2단계: 함수의 연속 (수학2)
    {"name": "구간", "label": "구간", "stage": 2,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=2&cmid=49075&page=1&quizid=84720",
     "description": "함수의 연속(수학2) — 열린 구간, 닫힌 구간, 반열린 구간의 개념을 이해하고 구간 표기를 정확히 사용할 수 있다."},
    {"name": "연속함수", "label": "연속함수", "stage": 2,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=2&cmid=49076&page=1&quizid=84721",
     "description": "함수의 연속(수학2) — 함수의 연속성 정의를 이해하고, 주어진 점에서 함수가 연속인지 판단할 수 있다."},
    {"name": "연속함수의_성질", "label": "연속함수의 성질", "stage": 2,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=2&cmid=49077&page=1&quizid=84722",
     "description": "함수의 연속(수학2) — 연속함수의 사칙연산과 합성함수의 연속성 성질을 이해하고 활용할 수 있다."},
    {"name": "최대·최소_정리", "label": "최대·최소 정리", "stage": 2,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=2&cmid=49078&page=1&quizid=84723",
     "description": "함수의 연속(수학2) — 닫힌 구간에서 연속인 함수는 최댓값과 최솟값을 가진다는 정리를 이해하고 활용할 수 있다."},
    {"name": "사이값_정리", "label": "사이값 정리", "stage": 2,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=2&cmid=49080&page=1&quizid=84724",
     "description": "함수의 연속(수학2) — 사이값 정리를 이해하고, 방정식의 실근 존재 여부를 판단하는 데 활용할 수 있다."},
    
    # 3단계: 미분계수와 도함수 (수학2)
    {"name": "평균변화율", "label": "평균변화율", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49081&page=1&quizid=84725",
     "description": "미분계수와 도함수(수학2) — 두 점 사이의 평균변화율을 이해하고 계산할 수 있으며, 이것이 미분계수로 이어지는 과정을 이해할 수 있다."},
    {"name": "미분계수", "label": "미분계수", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49082&page=1&quizid=84726",
     "description": "미분계수와 도함수(수학2) — 미분계수의 정의를 이해하고 극한을 이용하여 미분계수를 계산할 수 있다."},
    {"name": "미분계수의_기하학적_의미", "label": "미분계수의 기하학적 의미", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49083&page=1&quizid=84727",
     "description": "미분계수와 도함수(수학2) — 미분계수가 접선의 기울기임을 이해하고, 이를 기하학적으로 해석할 수 있다."},
    {"name": "미분계수를_이용한_극한값의_계산", "label": "미분계수를 이용한 극한값의 계산", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49084&page=1&quizid=84728",
     "description": "미분계수와 도함수(수학2) — 미분계수의 정의를 활용하여 특정 형태의 극한값을 계산할 수 있다."},
    {"name": "미분가능", "label": "미분가능", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49085&page=1&quizid=84729",
     "description": "미분계수와 도함수(수학2) — 함수가 특정 점에서 미분가능한 조건을 이해하고 판단할 수 있다."},
    {"name": "미분가능성과_연속성", "label": "미분가능성과 연속성", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49086&page=1&quizid=84730",
     "description": "미분계수와 도함수(수학2) — 미분가능하면 연속임을 이해하고, 연속이지만 미분가능하지 않은 경우를 구분할 수 있다."},
    {"name": "함수가_미분가능하지_않은_경우", "label": "함수가 미분가능하지 않은 경우", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49087&page=1&quizid=84731",
     "description": "미분계수와 도함수(수학2) — 함수가 미분가능하지 않은 경우(첨점, 불연속점 등)를 이해하고 판별할 수 있다."},
    {"name": "도함수", "label": "도함수", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49088&page=1&quizid=84732",
     "description": "미분계수와 도함수(수학2) — 도함수의 정의를 이해하고, 도함수를 구하는 방법을 알고 활용할 수 있다."},
    {"name": "함수_y=xⁿ과_상수함수의_도함수", "label": "함수 y=xⁿ과 상수함수의 도함수", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49089&page=1&quizid=84733",
     "description": "미분계수와 도함수(수학2) — 거듭제곱 함수와 상수함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    {"name": "함수의_실수배", "label": "함수의 실수배", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49090&page=1&quizid=84734",
     "description": "미분계수와 도함수(수학2) — 상수배 함수의 도함수는 상수배한 도함수와 같다는 성질을 이해하고 활용할 수 있다."},
    {"name": "함수의_곱의_미분법", "label": "함수의 곱의 미분법", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49091&page=1&quizid=84735",
     "description": "미분계수와 도함수(수학2) — 곱의 미분법 공식을 이해하고 두 함수의 곱의 도함수를 구할 수 있다."},
    {"name": "함수_y=｛f(x)｝ⁿ의_도함수", "label": "함수 y=｛f(x)｝ⁿ의 도함수", "stage": 3,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=3&cmid=49092&page=1&quizid=84736",
     "description": "미분계수와 도함수(수학2) — 합성함수의 거듭제곱 형태의 도함수를 구하는 방법을 이해하고 적용할 수 있다."},
    
    # 4단계: 도함수의 활용 (1) (수학2)
    {"name": "접선의_방정식", "label": "접선의 방정식", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49093&page=1&quizid=84737",
     "description": "도함수의 활용(1)(수학2) — 접선의 방정식을 구하는 기본 원리를 이해하고 적용할 수 있다."},
    {"name": "접선의_좌표가_주어질_때_접선의_방정식_구하기", "label": "접선의 좌표가 주어질 때 접선의 방정식 구하기", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49094&page=1&quizid=84738",
     "description": "도함수의 활용(1)(수학2) — 접점의 좌표가 주어졌을 때 접선의 방정식을 구하는 방법을 이해하고 계산할 수 있다."},
    {"name": "기울기가_주어질_때_접선의_방정식_구하기", "label": "기울기가 주어질 때 접선의 방정식 구하기", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49095&page=1&quizid=84739",
     "description": "도함수의 활용(1)(수학2) — 접선의 기울기가 주어졌을 때 접선의 방정식을 구하는 방법을 이해하고 적용할 수 있다."},
    {"name": "곡선_밖의_한_점의_좌표가_주어질_때_접선의_방정식_구하기", "label": "곡선 밖의 한 점의 좌표가 주어질 때 접선의 방정식 구하기", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49096&page=1&quizid=84740",
     "description": "도함수의 활용(1)(수학2) — 곡선 밖의 점을 지나는 접선의 방정식을 구하는 방법을 이해하고 계산할 수 있다."},
    {"name": "두_곡선의_위치_관계에_대한_고찰", "label": "두 곡선의 위치 관계에 대한 고찰", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49097&page=1&quizid=84741",
     "description": "도함수의 활용(1)(수학2) — 두 곡선의 접촉, 교차, 분리 등의 위치 관계를 도함수를 이용하여 분석할 수 있다."},
    {"name": "롤의_정리", "label": "롤의 정리", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49098&page=1&quizid=84742",
     "description": "도함수의 활용(1)(수학2) — 롤의 정리의 조건과 결론을 이해하고, 방정식의 실근 존재 여부를 판단하는 데 활용할 수 있다."},
    {"name": "평균값_정리", "label": "평균값 정리", "stage": 4,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=4&cmid=49099&page=1&quizid=84743",
     "description": "도함수의 활용(1)(수학2) — 평균값 정리를 이해하고, 함수의 증감과 극값을 분석하는 데 활용할 수 있다."},
    
    # 5단계: 도함수의 활용 (2) (수학2)
    {"name": "함수의_증가와_감소", "label": "함수의 증가와 감소", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49100&page=1&quizid=84744",
     "description": "도함수의 활용(2)(수학2) — 도함수의 부호를 이용하여 함수의 증가와 감소 구간을 판단할 수 있다."},
    {"name": "함수의_증가와_감소의_판정", "label": "함수의 증가와 감소의 판정", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49101&page=1&quizid=84745",
     "description": "도함수의 활용(2)(수학2) — 도함수를 구하고 부호를 분석하여 함수의 증가·감소 구간을 정확히 판정할 수 있다."},
    {"name": "함수의_극대와_극소", "label": "함수의 극대와 극소", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49102&page=1&quizid=84746",
     "description": "도함수의 활용(2)(수학2) — 극대값과 극소값의 정의를 이해하고, 도함수를 이용하여 극값을 찾을 수 있다."},
    {"name": "극값과_미분계수", "label": "극값과 미분계수", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49103&page=1&quizid=84747",
     "description": "도함수의 활용(2)(수학2) — 극값에서의 미분계수가 0임을 이해하고, 이를 활용하여 극값을 찾을 수 있다."},
    {"name": "함수의_극대와_극소의_판정", "label": "함수의 극대와 극소의 판정", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49104&page=1&quizid=84748",
     "description": "도함수의 활용(2)(수학2) — 도함수의 부호 변화를 이용하여 극대와 극소를 판정할 수 있다."},
    {"name": "함수의_그래프", "label": "함수의 그래프", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49105&page=1&quizid=84749",
     "description": "도함수의 활용(2)(수학2) — 도함수를 이용하여 함수의 증가·감소와 극값을 파악하고 그래프의 개형을 그릴 수 있다."},
    {"name": "다항함수의_그래프의_개형과_극값을_가질_조건", "label": "다항함수의 그래프의 개형과 극값을 가질 조건", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49106&page=1&quizid=84750",
     "description": "도함수의 활용(2)(수학2) — 다항함수가 극값을 가지는 조건을 이해하고, 그래프의 개형을 분석할 수 있다."},
    {"name": "함수의_최대와_최소", "label": "함수의 최대와 최소", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49107&page=1&quizid=84751",
     "description": "도함수의 활용(2)(수학2) — 닫힌 구간에서 함수의 최댓값과 최솟값을 구하는 방법을 이해하고 계산할 수 있다."},
    {"name": "극값이_하나뿐일_때의_함수의_최대와_최소", "label": "극값이 하나뿐일 때의 함수의 최대와 최소", "stage": 5,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=5&cmid=49108&page=1&quizid=84752",
     "description": "도함수의 활용(2)(수학2) — 극값이 하나뿐인 경우 최댓값과 최솟값을 판단하는 방법을 이해하고 적용할 수 있다."},
    
    # 6단계: 도함수의 활용 (3) (수학2)
    {"name": "방정식의_실근의_개수", "label": "방정식의 실근의 개수", "stage": 6,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=6&cmid=49109&page=1&quizid=84753",
     "description": "도함수의 활용(3)(수학2) — 도함수를 이용하여 방정식의 실근의 개수를 판단하는 방법을 이해하고 적용할 수 있다."},
    {"name": "삼차방정식의_근의_판별", "label": "삼차방정식의 근의 판별", "stage": 6,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=6&cmid=49110&page=1&quizid=84754",
     "description": "도함수의 활용(3)(수학2) — 삼차방정식의 실근의 개수를 도함수를 이용하여 판별할 수 있다."},
    {"name": "모든_실수에_대하여_성립하는_부등식의_증명", "label": "모든 실수에 대하여 성립하는 부등식의 증명", "stage": 6,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=6&cmid=49111&page=1&quizid=84755",
     "description": "도함수의 활용(3)(수학2) — 도함수를 이용하여 모든 실수에 대하여 성립하는 부등식을 증명할 수 있다."},
    {"name": "x＞a에서_성립하는_부등식의_증명", "label": "x＞a에서 성립하는 부등식의 증명", "stage": 6,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=6&cmid=49112&page=1&quizid=84756",
     "description": "도함수의 활용(3)(수학2) — 특정 구간에서 성립하는 부등식을 도함수를 이용하여 증명할 수 있다."},
    {"name": "속도와_가속도", "label": "속도와 가속도", "stage": 6,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=6&cmid=49113&page=1&quizid=84757",
     "description": "도함수의 활용(3)(수학2) — 위치함수의 도함수가 속도, 속도의 도함수가 가속도임을 이해하고 계산할 수 있다."},
    {"name": "시각에_대한_변화율", "label": "시각에 대한 변화율", "stage": 6,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=62&nch=6&cmid=49114&page=1&quizid=84758",
     "description": "도함수의 활용(3)(수학2) — 시간에 대한 변화율 문제를 도함수를 이용하여 해결할 수 있다."},
    
    # 7단계: 지수함수와 로그함수의 미분 (미분과 적분)
    {"name": "지수함수의_극한", "label": "지수함수의 극한", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48686&page=1&quizid=84835",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — 지수함수의 극한을 이해하고 계산할 수 있다."},
    {"name": "로그함수의_극한", "label": "로그함수의 극한", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48687&page=1&quizid=84836",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — 로그함수의 극한을 이해하고 계산할 수 있다."},
    {"name": "무리수_e", "label": "무리수 e", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48688&page=1&quizid=84837",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — 자연상수 e의 정의와 성질을 이해하고 활용할 수 있다."},
    {"name": "자연로그", "label": "자연로그", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48689&page=1&quizid=84838",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — 자연로그의 정의와 성질을 이해하고 활용할 수 있다."},
    {"name": "e의_정의를_이용한_지수함수와_로그함수의_극한", "label": "e의 정의를 이용한 지수함수와 로그함수의 극한", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48690&page=1&quizid=84839",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — e의 정의를 활용하여 지수함수와 로그함수의 극한을 계산할 수 있다."},
    {"name": "지수함수의_도함수", "label": "지수함수의 도함수", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48691&page=1&quizid=84840",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — 지수함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    {"name": "로그함수의_도함수", "label": "로그함수의 도함수", "stage": 7,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=3&cmid=48692&page=1&quizid=84841",
     "description": "지수함수와 로그함수의 미분(미분과 적분) — 로그함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    
    # 8단계: 삼각함수의 미분 (미분과 적분)
    {"name": "삼각함수의_덧셈정리", "label": "삼각함수의 덧셈정리", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48716&page=1&quizid=84873",
     "description": "삼각함수의 미분(미분과 적분) — 삼각함수의 덧셈정리를 이해하고 활용할 수 있다."},
    {"name": "삼각함수의_합성", "label": "삼각함수의 합성", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48717&page=1&quizid=84874",
     "description": "삼각함수의 미분(미분과 적분) — 삼각함수의 합성을 이해하고 활용할 수 있다."},
    {"name": "배각의_공식", "label": "배각의 공식", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48718&page=1&quizid=84875",
     "description": "삼각함수의 미분(미분과 적분) — 배각의 공식을 이해하고 활용할 수 있다."},
    {"name": "삼배각의_공식", "label": "삼배각의 공식", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48719&page=1&quizid=84876",
     "description": "삼각함수의 미분(미분과 적분) — 삼배각의 공식을 이해하고 활용할 수 있다."},
    {"name": "반각의_공식", "label": "반각의 공식", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48720&page=1&quizid=84877",
     "description": "삼각함수의 미분(미분과 적분) — 반각의 공식을 이해하고 활용할 수 있다."},
    {"name": "삼각함수의_극한", "label": "삼각함수의 극한", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48721&page=1&quizid=84878",
     "description": "삼각함수의 미분(미분과 적분) — 삼각함수의 극한을 이해하고 계산할 수 있다."},
    {"name": "함수_sinx/x_tanx/x의_극한", "label": "함수 sinx/x, tanx/x의 극한", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48722&page=1&quizid=84879",
     "description": "삼각함수의 미분(미분과 적분) — sinx/x와 tanx/x의 극한값을 이해하고 활용할 수 있다."},
    {"name": "삼각함수의_도함수_미적", "label": "삼각함수의 도함수", "stage": 8,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=4&cmid=48723&page=1&quizid=84880",
     "description": "삼각함수의 미분(미분과 적분) — 삼각함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    
    # 9단계: 여러가지 미분법 (미분과 적분)
    {"name": "함수의_몫의_미분법", "label": "함수의 몫의 미분법", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48724&page=1&quizid=84881",
     "description": "여러가지 미분법(미분과 적분) — 몫의 미분법 공식을 이해하고 두 함수의 몫의 도함수를 구할 수 있다."},
    {"name": "함수_y=xⁿ_n은_정수_의_도함수", "label": "함수 y=xⁿ (n은 정수)의 도함수", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48725&page=1&quizid=84882",
     "description": "여러가지 미분법(미분과 적분) — 정수 지수를 가진 거듭제곱 함수의 도함수를 구할 수 있다."},
    {"name": "삼각함수의_도함수_미적2", "label": "삼각함수의 도함수", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48726&page=1&quizid=84883",
     "description": "여러가지 미분법(미분과 적분) — 삼각함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    {"name": "합성함수의_미분법", "label": "합성함수의 미분법", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48727&page=1&quizid=84884",
     "description": "여러가지 미분법(미분과 적분) — 연쇄법칙을 이해하고 합성함수의 도함수를 구할 수 있다."},
    {"name": "지수함수의_도함수_미적", "label": "지수함수의 도함수", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48728&page=1&quizid=84885",
     "description": "여러가지 미분법(미분과 적분) — 지수함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    {"name": "로그함수의_도함수_미적", "label": "로그함수의 도함수", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48729&page=1&quizid=84886",
     "description": "여러가지 미분법(미분과 적분) — 로그함수의 도함수를 구하는 공식을 이해하고 적용할 수 있다."},
    {"name": "로그_미분법", "label": "로그 미분법", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48730&page=1&quizid=84887",
     "description": "여러가지 미분법(미분과 적분) — 로그 미분법을 이해하고 복잡한 함수의 도함수를 구하는 데 활용할 수 있다."},
    {"name": "함수_y=xⁿ_n은_실수_의_도함수", "label": "함수 y=xⁿ (n은 실수)의 도함수", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48731&page=1&quizid=84818",
     "description": "여러가지 미분법(미분과 적분) — 실수 지수를 가진 거듭제곱 함수의 도함수를 구할 수 있다."},
    {"name": "음함수의_미분법", "label": "음함수의 미분법", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48828&page=1&quizid=84964",
     "description": "여러가지 미분법(미분과 적분) — 음함수의 미분법을 이해하고 음함수로 표현된 함수의 도함수를 구할 수 있다."},
    {"name": "평면_곡선의_접선의_방정식-접점의_좌표가_주어질_때", "label": "평면 곡선의 접선의 방정식-접점의 좌표가 주어질 때", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48829&page=1&quizid=84965",
     "description": "여러가지 미분법(미분과 적분) — 평면 곡선의 접선의 방정식을 구하는 방법을 이해하고 적용할 수 있다."},
    {"name": "매개변수로_나타낸_함수의_미분법", "label": "매개변수로 나타낸 함수의 미분법", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48835&page=1&quizid=84971",
     "description": "여러가지 미분법(미분과 적분) — 매개변수로 표현된 함수의 도함수를 구하는 방법을 이해하고 적용할 수 있다."},
    {"name": "역함수의_미분법", "label": "역함수의 미분법", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48732&page=1&quizid=84889",
     "description": "여러가지 미분법(미분과 적분) — 역함수의 미분법을 이해하고 역함수의 도함수를 구할 수 있다."},
    {"name": "이계도함수", "label": "이계도함수", "stage": 9,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=5&cmid=48733&page=1&quizid=84890",
     "description": "여러가지 미분법(미분과 적분) — 이계도함수의 개념을 이해하고 구할 수 있으며, 곡선의 오목·볼록을 판단하는 데 활용할 수 있다."},
    
    # 10단계: 여러가지 함수의 도함수의 활용 (1) (미분과 적분)
    {"name": "접선의_방정식_미적", "label": "접선의 방정식", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48734&page=1&quizid=84891",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 다양한 함수의 접선의 방정식을 구하는 방법을 이해하고 적용할 수 있다."},
    {"name": "접점의_좌표가_주어질_때_접선의_방정식_구하기_미적", "label": "접점의 좌표가 주어질 때 접선의 방정식 구하기", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48735&page=1&quizid=84892",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 접점의 좌표가 주어졌을 때 접선의 방정식을 구할 수 있다."},
    {"name": "기울기가_주어질_때_접선의_방정식_구하기_미적", "label": "기울기가 주어질 때 접선의 방정식 구하기", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48736&page=1&quizid=84893",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 접선의 기울기가 주어졌을 때 접선의 방정식을 구할 수 있다."},
    {"name": "곡선_밖의_한_점의_좌표가_주어질_때_접선의_방정식_구하기_미적", "label": "곡선 밖의 한 점의 좌표가 주어질 때 접선의 방정식 구하기", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48737&page=1&quizid=84894",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 곡선 밖의 점을 지나는 접선의 방정식을 구할 수 있다."},
    {"name": "함수의_증가와_감소_미적", "label": "함수의 증가와 감소", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48738&page=1&quizid=84895",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 다양한 함수의 증가·감소 구간을 도함수를 이용하여 판단할 수 있다."},
    {"name": "함수의_극대와_극소_미적", "label": "함수의 극대와 극소", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48739&page=1&quizid=84896",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 다양한 함수의 극대·극소를 도함수를 이용하여 찾을 수 있다."},
    {"name": "함수의_극대와_극소의_판정_미적", "label": "함수의 극대와 극소의 판정", "stage": 10,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=6&cmid=48740&page=1&quizid=84897",
     "description": "여러가지 함수의 도함수의 활용(1)(미분과 적분) — 도함수의 부호 변화를 이용하여 극대·극소를 판정할 수 있다."},
    
    # 11단계: 여러가지 함수의 도함수의 활용 (2) (미분과 적분)
    {"name": "곡선의_오목과_볼록", "label": "곡선의 오목과 볼록", "stage": 11,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=7&cmid=49079&page=1&quizid=84898",
     "description": "여러가지 함수의 도함수의 활용(2)(미분과 적분) — 이계도함수를 이용하여 곡선의 오목·볼록을 판단할 수 있다."},
    {"name": "변곡점", "label": "변곡점", "stage": 11,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=7&cmid=49192&page=1&quizid=84899",
     "description": "여러가지 함수의 도함수의 활용(2)(미분과 적분) — 변곡점의 정의를 이해하고 이계도함수를 이용하여 변곡점을 찾을 수 있다."},
    {"name": "함수의_그래프_미적", "label": "함수의 그래프", "stage": 11,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=7&cmid=49193&page=1&quizid=84900",
     "description": "여러가지 함수의 도함수의 활용(2)(미분과 적분) — 도함수와 이계도함수를 이용하여 함수의 그래프를 정확히 그릴 수 있다."},
    {"name": "함수의_최대와_최소_미적", "label": "함수의 최대와 최소", "stage": 11,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=7&cmid=49194&page=1&quizid=84901",
     "description": "여러가지 함수의 도함수의 활용(2)(미분과 적분) — 다양한 함수의 최댓값과 최솟값을 구할 수 있다."},
    {"name": "방정식의_실근의_개수_미적", "label": "방정식의 실근의 개수", "stage": 11,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=7&cmid=49195&page=1&quizid=84902",
     "description": "여러가지 함수의 도함수의 활용(2)(미분과 적분) — 도함수를 이용하여 방정식의 실근의 개수를 판단할 수 있다."},
    {"name": "부등식의_증명_미적", "label": "부등식의 증명", "stage": 11,
     "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=63&nch=7&cmid=49196&page=1&quizid=84903",
     "description": "여러가지 함수의 도함수의 활용(2)(미분과 적분) — 도함수를 이용하여 부등식을 증명할 수 있다."},
]

def escape_xml(text):
    """XML 특수문자 이스케이프"""
    return text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace('"', "&quot;")

def generate_owl():
    """OWL 파일 생성"""
    header = '''<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
    xmlns:ar="http://example.org/adaptive-review#"
    xml:base="http://example.org/adaptive-review">

  <owl:Ontology rdf:about="http://example.org/adaptive-review">
    <rdfs:comment xml:lang="ko">대한민국 미분 영역 기반 탄력적 복습 온톨로지 (학년 순서 보장, 활동 포함, 상세 설명)</rdfs:comment>
  </owl:Ontology>

  <!-- Schema (minimal, 재사용) -->
  <owl:Class rdf:about="http://example.org/adaptive-review#Subtopic"/>
  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#precedes"/>
  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#dependsOn"/>
  <owl:ObjectProperty rdf:about="http://example.org/adaptive-review#includes"/>
  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#stage"/>
  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#hasURL"/>
  <owl:DatatypeProperty rdf:about="http://example.org/adaptive-review#description"/>

  <!-- 표준 학습활동 인스턴스 (모든 주제에 포함) -->
  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRemind_Default"><rdfs:label xml:lang="ko">개념요약</rdfs:label></owl:NamedIndividual>
  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptRebuild_Default"><rdfs:label xml:lang="ko">개념이해하기</rdfs:label></owl:NamedIndividual>
  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ConceptCheck_Default"><rdfs:label xml:lang="ko">개념체크</rdfs:label></owl:NamedIndividual>
  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#ExampleQuiz_Default"><rdfs:label xml:lang="ko">예제퀴즈</rdfs:label></owl:NamedIndividual>
  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#RepresentativeType_Default"><rdfs:label xml:lang="ko">대표유형</rdfs:label></owl:NamedIndividual>

'''
    
    content = header
    
    # 각 주제를 Subtopic으로 추가
    for topic in topics_data:
        name = topic["name"]
        label = escape_xml(topic["label"])
        stage = topic["stage"]
        url = escape_xml(topic["url"])
        description = escape_xml(topic["description"])
        
        content += f'''  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#{name}">
    <rdf:type rdf:resource="http://example.org/adaptive-review#Subtopic"/>
    <rdfs:label xml:lang="ko">{label}</rdfs:label>
    <ar:stage rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">{stage}</ar:stage>
    <ar:hasURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">{url}</ar:hasURL>
    <ar:description xml:lang="ko">{description}</ar:description>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRemind_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRebuild_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptCheck_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ExampleQuiz_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#RepresentativeType_Default"/>
  </owl:NamedIndividual>


'''
    
    # precedes 관계 추가 (같은 단계 내 순서)
    prev_topic = None
    prev_stage = None
    for topic in topics_data:
        if prev_topic and prev_stage == topic["stage"]:
            content += f'''  <rdf:Description rdf:about="http://example.org/adaptive-review#{prev_topic}">
    <ar:precedes rdf:resource="http://example.org/adaptive-review#{topic['name']}"/>
  </rdf:Description>


'''
        prev_topic = topic["name"]
        prev_stage = topic["stage"]
    
    # dependsOn 관계 추가 (논리적 선행 관계)
    # 2단계는 1단계에 의존
    # 3단계는 2단계에 의존
    # 등등...
    for i, topic in enumerate(topics_data):
        if topic["stage"] > 1:
            # 같은 단계의 첫 번째 주제는 이전 단계의 마지막 주제에 의존
            if i > 0 and topics_data[i-1]["stage"] < topic["stage"]:
                prev_topic = topics_data[i-1]["name"]
                content += f'''  <rdf:Description rdf:about="http://example.org/adaptive-review#{topic['name']}">
    <ar:dependsOn rdf:resource="http://example.org/adaptive-review#{prev_topic}"/>
  </rdf:Description>


'''
    
    content += '</rdf:RDF>\n'
    
    return content

if __name__ == "__main__":
    import os
    owl_content = generate_owl()
    script_dir = os.path.dirname(os.path.abspath(__file__))
    output_path = os.path.join(script_dir, "9 differentiation_ontology.owl")
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(owl_content)
    print(f"OWL 파일이 생성되었습니다: {output_path}")

