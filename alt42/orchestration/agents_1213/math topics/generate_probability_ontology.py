#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
경우의 수와 확률 영역 OWL 파일 생성 스크립트
"""
import os

# 주제 데이터 구조
topics = [
    # 1단계: 경우의 수 (중등수학 2-2)
    {
        "stage": 1,
        "name": "사건과_경우의_수",
        "label": "사건과 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53356&page=1&quizid=86264",
        "description": "경우의 수(중등) — 사건과 경우의 수의 정의를 이해하고, 주어진 상황에서 가능한 모든 경우의 수를 체계적으로 세는 방법을 익힌다."
    },
    {
        "stage": 1,
        "name": "사건_A_또는_사건_B가_일어나는_경우의_수",
        "label": "사건 A 또는 사건 B가 일어나는 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53357&page=1&quizid=86265",
        "description": "경우의 수(중등) — 합의 법칙을 이해하고, 두 사건 중 하나가 일어나는 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 1,
        "name": "두_사건_A_B가_동시에_일어나는_경우의_수",
        "label": "두 사건 A, B가 동시에 일어나는 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53358&page=1&quizid=86266",
        "description": "경우의 수(중등) — 곱의 법칙을 이해하고, 두 사건이 동시에 일어나는 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 1,
        "name": "최단_거리로_가는_경우의_수",
        "label": "최단 거리로 가는 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53359&page=1&quizid=86267",
        "description": "경우의 수(중등) — 격자점을 이용한 최단 경로 문제에서 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 1,
        "name": "일렬로_세우는_경우의_수",
        "label": "일렬로 세우는 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53360&page=1&quizid=86268",
        "description": "경우의 수(중등) — 서로 다른 n개를 일렬로 배열하는 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 1,
        "name": "이웃하여_세우는_경우의_수",
        "label": "이웃하여 세우는 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53361&page=1&quizid=86269",
        "description": "경우의 수(중등) — 특정 원소들이 이웃하도록 배열하는 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 1,
        "name": "정수의_개수",
        "label": "정수의 개수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53362&page=1&quizid=86270",
        "description": "경우의 수(중등) — 주어진 조건을 만족하는 정수의 개수를 경우의 수로 계산할 수 있다."
    },
    {
        "stage": 1,
        "name": "대표를_뽑는_경우의_수",
        "label": "대표를 뽑는 경우의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=9&cmid=53363&page=1&quizid=86271",
        "description": "경우의 수(중등) — 주어진 조건에 맞는 대표를 뽑는 경우의 수를 계산할 수 있다."
    },
    
    # 2단계: 확률 (중등수학 2-2)
    {
        "stage": 2,
        "name": "확률의_뜻",
        "label": "확률의 뜻",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53364&page=1&quizid=86272",
        "description": "확률(중등) — 확률의 기본 개념을 이해하고, 수학적 확률의 정의를 알고 계산할 수 있다."
    },
    {
        "stage": 2,
        "name": "확률의_성질",
        "label": "확률의 성질",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53365&page=1&quizid=86273",
        "description": "확률(중등) — 확률의 기본 성질(0 ≤ P(A) ≤ 1, P(S) = 1)을 이해하고 적용할 수 있다."
    },
    {
        "stage": 2,
        "name": "어떤_사건이_일어나지_않을_확률",
        "label": "어떤 사건이 일어나지 않을 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53366&page=1&quizid=86274",
        "description": "확률(중등) — 여사건의 확률을 이해하고 P(A^c) = 1 - P(A)를 활용할 수 있다."
    },
    {
        "stage": 2,
        "name": "도형에서의_확률",
        "label": "도형에서의 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53367&page=1&quizid=86275",
        "description": "확률(중등) — 기하학적 확률의 개념을 이해하고, 도형의 넓이를 이용하여 확률을 계산할 수 있다."
    },
    {
        "stage": 2,
        "name": "사건_A_또는_사건_B가_일어날_확률",
        "label": "사건 A 또는 사건 B가 일어날 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53368&page=1&quizid=86276",
        "description": "확률(중등) — 확률의 덧셈정리를 이해하고, 두 사건 중 하나가 일어날 확률을 계산할 수 있다."
    },
    {
        "stage": 2,
        "name": "두_사건_A_B가_동시에_일어날_확률",
        "label": "두 사건 A, B가 동시에 일어날 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53369&page=1&quizid=86277",
        "description": "확률(중등) — 두 사건이 동시에 일어날 확률을 계산하고, 독립사건과 종속사건을 구분할 수 있다."
    },
    {
        "stage": 2,
        "name": "연속하여_뽑을_때의_확률",
        "label": "연속하여 뽑을 때의 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=69&nch=10&cmid=53370&page=1&quizid=86278",
        "description": "확률(중등) — 복원 추출과 비복원 추출의 차이를 이해하고, 각각의 확률을 계산할 수 있다."
    },
    
    # 3단계: 순열과 조합 (고등수학 하)
    {
        "stage": 3,
        "name": "합의_법칙",
        "label": "합의 법칙",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=60&nch=7&cmid=48741&page=1&quizid=85077",
        "description": "순열과 조합(고등) — 합의 법칙을 이해하고, 두 사건이 동시에 일어나지 않을 때 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 3,
        "name": "곱의_법칙",
        "label": "곱의 법칙",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=60&nch=7&cmid=48742&page=1&quizid=85078",
        "description": "순열과 조합(고등) — 곱의 법칙을 이해하고, 두 사건이 순차적으로 일어날 때 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 3,
        "name": "순열_확통",
        "label": "순열(확통)",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=60&nch=7&cmid=48743&page=1&quizid=85079",
        "description": "순열과 조합(고등) — 순열의 개념을 이해하고 nPr의 의미와 계산 방법을 알고 있다."
    },
    {
        "stage": 3,
        "name": "P의_계산",
        "label": "P의 계산",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=60&nch=7&cmid=48744&page=1&quizid=85080",
        "description": "순열과 조합(고등) — 순열의 계산 공식을 이해하고 nPr = n!/(n-r)!을 활용할 수 있다."
    },
    {
        "stage": 3,
        "name": "조합_확통",
        "label": "조합(확통)",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=60&nch=7&cmid=48749&page=1&quizid=85085",
        "description": "순열과 조합(고등) — 조합의 개념을 이해하고 nCr의 의미와 계산 방법을 알고 있다."
    },
    
    # 4단계: 순열 (확률과 통계)
    {
        "stage": 4,
        "name": "합의_법칙_확통",
        "label": "합의 법칙",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=1&cmid=48741&page=1&quizid=85077",
        "description": "순열(확통) — 합의 법칙을 이해하고 적용하여 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 4,
        "name": "곱의_법칙_확통",
        "label": "곱의 법칙",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=1&cmid=48742&page=1&quizid=85078",
        "description": "순열(확통) — 곱의 법칙을 이해하고 적용하여 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 4,
        "name": "순열_확통2",
        "label": "순열",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=1&cmid=48743&page=1&quizid=85079",
        "description": "순열(확통) — 순열의 개념과 계산 방법을 이해하고 문제에 적용할 수 있다."
    },
    {
        "stage": 4,
        "name": "P의_계산_확통",
        "label": "P의 계산",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=1&cmid=48744&page=1&quizid=85080",
        "description": "순열(확통) — 순열의 계산 공식을 활용하여 문제를 해결할 수 있다."
    },
    
    # 5단계: 여러가지 순열 (확률과 통계)
    {
        "stage": 5,
        "name": "원순열",
        "label": "원순열",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=2&cmid=48745&page=1&quizid=85081",
        "description": "여러가지 순열(확통) — 원순열의 개념을 이해하고 (n-1)!을 이용하여 계산할 수 있다."
    },
    {
        "stage": 5,
        "name": "다각형으로_배열하는_방법의_수",
        "label": "다각형으로 배열하는 방법의 수",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=2&cmid=48746&page=1&quizid=85082",
        "description": "여러가지 순열(확통) — 다각형의 꼭짓점에 원소를 배열하는 경우의 수를 계산할 수 있다."
    },
    {
        "stage": 5,
        "name": "중복순열",
        "label": "중복순열",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=2&cmid=48747&page=1&quizid=85083",
        "description": "여러가지 순열(확통) — 중복순열의 개념을 이해하고 n^r을 이용하여 계산할 수 있다."
    },
    {
        "stage": 5,
        "name": "같은_것이_있는_순열",
        "label": "같은 것이 있는 순열",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=2&cmid=48748&page=1&quizid=85084",
        "description": "여러가지 순열(확통) — 같은 것이 있는 순열의 개념을 이해하고 n!/p!q!...을 이용하여 계산할 수 있다."
    },
    
    # 6단계: 조합 (확률과 통계)
    {
        "stage": 6,
        "name": "조합_확통2",
        "label": "조합(확통)",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=3&cmid=48749&page=1&quizid=85085",
        "description": "조합(확통) — 조합의 개념과 계산 방법을 이해하고 문제에 적용할 수 있다."
    },
    {
        "stage": 6,
        "name": "중복조합",
        "label": "중복조합",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=3&cmid=48750&page=1&quizid=85086",
        "description": "조합(확통) — 중복조합의 개념을 이해하고 nHr = n+r-1Cr을 이용하여 계산할 수 있다."
    },
    {
        "stage": 6,
        "name": "순열_중복순열_조합_중복조합의_비교",
        "label": "순열, 중복순열, 조합, 중복조합의 비교",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=3&cmid=48751&page=1&quizid=85087",
        "description": "조합(확통) — 순열, 중복순열, 조합, 중복조합의 차이를 이해하고 문제 상황에 맞게 선택할 수 있다."
    },
    
    # 7단계: 이항정리와 분할 (확률과 통계)
    {
        "stage": 7,
        "name": "이항정리",
        "label": "이항정리",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=4&cmid=48752&page=1&quizid=85088",
        "description": "이항정리와 분할(확통) — 이항정리를 이해하고 (a+b)^n의 전개식을 구할 수 있다."
    },
    {
        "stage": 7,
        "name": "a_b_c_n의_전개식의_일반항",
        "label": "(a+b+c)ⁿ의 전개식의 일반항",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=4&cmid=48753&page=1&quizid=85089",
        "description": "이항정리와 분할(확통) — 세 항 이상의 전개식의 일반항을 구할 수 있다."
    },
    {
        "stage": 7,
        "name": "파스칼의_삼각형",
        "label": "파스칼의 삼각형",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=4&cmid=48754&page=1&quizid=85090",
        "description": "이항정리와 분할(확통) — 파스칼의 삼각형을 이해하고 이항계수를 구하는데 활용할 수 있다."
    },
    {
        "stage": 7,
        "name": "파스칼의_삼각형의_성질",
        "label": "파스칼의 삼각형의 성질",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=4&cmid=48755&page=1&quizid=85091",
        "description": "이항정리와 분할(확통) — 파스칼의 삼각형의 성질을 이해하고 문제 해결에 활용할 수 있다."
    },
    {
        "stage": 7,
        "name": "이항계수의_성질",
        "label": "이항계수의 성질",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=4&cmid=48756&page=1&quizid=85092",
        "description": "이항정리와 분할(확통) — 이항계수의 성질을 이해하고 문제 해결에 활용할 수 있다."
    },
    
    # 8단계: 확률의 뜻과 활용 (확률과 통계)
    {
        "stage": 8,
        "name": "시행과_사건",
        "label": "시행과 사건",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48763&page=1&quizid=85099",
        "description": "확률의 뜻과 활용(확통) — 시행과 사건의 개념을 이해하고 표본공간과 사건을 구할 수 있다."
    },
    {
        "stage": 8,
        "name": "합사건_곱사건_배반사건_여사건",
        "label": "합사건, 곱사건, 배반사건, 여사건",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48764&page=1&quizid=85100",
        "description": "확률의 뜻과 활용(확통) — 합사건, 곱사건, 배반사건, 여사건의 개념을 이해하고 구분할 수 있다."
    },
    {
        "stage": 8,
        "name": "수학적_확률",
        "label": "수학적 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48765&page=1&quizid=85101",
        "description": "확률의 뜻과 활용(확통) — 수학적 확률의 정의를 이해하고 계산할 수 있다."
    },
    {
        "stage": 8,
        "name": "통계적_확률",
        "label": "통계적 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48766&page=1&quizid=85102",
        "description": "확률의 뜻과 활용(확통) — 통계적 확률의 개념을 이해하고 상대도수를 이용하여 확률을 추정할 수 있다."
    },
    {
        "stage": 8,
        "name": "기하학적_확률",
        "label": "기하학적 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48767&page=1&quizid=85103",
        "description": "확률의 뜻과 활용(확통) — 기하학적 확률의 개념을 이해하고 도형의 넓이를 이용하여 확률을 계산할 수 있다."
    },
    {
        "stage": 8,
        "name": "확률의_기본_성질",
        "label": "확률의 기본 성질",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48768&page=1&quizid=85104",
        "description": "확률의 뜻과 활용(확통) — 확률의 기본 성질을 이해하고 문제 해결에 활용할 수 있다."
    },
    {
        "stage": 8,
        "name": "확률의_덧셈정리",
        "label": "확률의 덧셈정리",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48769&page=1&quizid=85105",
        "description": "확률의 뜻과 활용(확통) — 확률의 덧셈정리를 이해하고 두 사건의 합사건의 확률을 계산할 수 있다."
    },
    {
        "stage": 8,
        "name": "여사건의_확률",
        "label": "여사건의 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=5&cmid=48770&page=1&quizid=85106",
        "description": "확률의 뜻과 활용(확통) — 여사건의 확률을 이해하고 P(A^c) = 1 - P(A)를 활용할 수 있다."
    },
    
    # 9단계: 조건부확률 (확률과 통계)
    {
        "stage": 9,
        "name": "조건부확률_개념",
        "label": "조건부확률(개념)",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48771&page=1&quizid=85107",
        "description": "조건부확률(확통) — 조건부확률의 개념을 이해하고 P(A|B) = P(A∩B)/P(B)를 계산할 수 있다."
    },
    {
        "stage": 9,
        "name": "확률의_곱셈정리",
        "label": "확률의 곱셈정리",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48772&page=1&quizid=85108",
        "description": "조건부확률(확통) — 확률의 곱셈정리를 이해하고 P(A∩B) = P(A|B)P(B)를 활용할 수 있다."
    },
    {
        "stage": 9,
        "name": "사건의_독립과_종속",
        "label": "사건의 독립과 종속",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48773&page=1&quizid=85109",
        "description": "조건부확률(확통) — 사건의 독립과 종속을 구분하고, 독립사건일 때 P(A∩B) = P(A)P(B)를 활용할 수 있다."
    },
    {
        "stage": 9,
        "name": "배반사건과_독립사건의_관계",
        "label": "배반사건과 독립사건의 관계",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48774&page=1&quizid=85110",
        "description": "조건부확률(확통) — 배반사건과 독립사건의 차이를 이해하고 구분할 수 있다."
    },
    {
        "stage": 9,
        "name": "독립이기_위한_필요충분조건",
        "label": "독립이기 위한 필요충분조건",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48775&page=1&quizid=85111",
        "description": "조건부확률(확통) — 사건이 독립이기 위한 필요충분조건을 이해하고 판단할 수 있다."
    },
    {
        "stage": 9,
        "name": "두_사건_A_B가_독립일_때_A와_B^c_A^c와_B_A^c와_B^c의_관계",
        "label": "두 사건 A，B가 독립일 때， A와 B^c，A^c와 B，A^c와 B^c의 관계",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48776&page=1&quizid=85112",
        "description": "조건부확률(확통) — 두 사건이 독립일 때, 여사건들 간의 독립성을 이해하고 판단할 수 있다."
    },
    {
        "stage": 9,
        "name": "독립시행의_확률",
        "label": "독립시행의 확률",
        "url": "https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=64&nch=6&cmid=48777&page=1&quizid=85113",
        "description": "조건부확률(확통) — 독립시행의 확률을 이해하고 n번의 독립시행에서 r번 성공할 확률을 계산할 수 있다."
    },
]

# OWL 파일 생성
owl_content = '''<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
    xmlns:ar="http://example.org/adaptive-review#"
    xml:base="http://example.org/adaptive-review">

  <owl:Ontology rdf:about="http://example.org/adaptive-review">
    <rdfs:comment xml:lang="ko">대한민국 경우의 수와 확률 영역 기반 탄력적 복습 온톨로지 (학년 순서 보장, 활동 포함, 상세 설명)</rdfs:comment>
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

# 각 주제 추가
for topic in topics:
    owl_content += f'''  <owl:NamedIndividual rdf:about="http://example.org/adaptive-review#{topic['name']}">
    <rdf:type rdf:resource="http://example.org/adaptive-review#Subtopic"/>
    <rdfs:label xml:lang="ko">{topic['label']}</rdfs:label>
    <ar:stage rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">{topic['stage']}</ar:stage>
    <ar:hasURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">{topic['url'].replace('&', '&amp;')}</ar:hasURL>
    <ar:description xml:lang="ko">{topic['description']}</ar:description>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRemind_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptRebuild_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ConceptCheck_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#ExampleQuiz_Default"/>
    <ar:includes rdf:resource="http://example.org/adaptive-review#RepresentativeType_Default"/>
  </owl:NamedIndividual>


'''

# precedes 관계 추가 (같은 stage 내에서 순서대로)
prev_topic = None
for topic in topics:
    if prev_topic and prev_topic['stage'] == topic['stage']:
        owl_content += f'''  <rdf:Description rdf:about="http://example.org/adaptive-review#{prev_topic['name']}">
    <ar:precedes rdf:resource="http://example.org/adaptive-review#{topic['name']}"/>
  </rdf:Description>

'''
    prev_topic = topic

owl_content += '</rdf:RDF>\n'

# 파일 저장
script_dir = os.path.dirname(os.path.abspath(__file__))
output_file = os.path.join(script_dir, '16 경우의_수와_확률_ontology.owl')
with open(output_file, 'w', encoding='utf-8') as f:
    f.write(owl_content)

print(f"OWL 파일이 생성되었습니다: {output_file}")
print(f"총 {len(topics)}개의 주제가 포함되었습니다.")

