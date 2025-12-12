<?php
/**
 * Learning Assessment Questions Data
 * File: includes/questions_data.php
 * Extracted from onboarding_learningtype.php
 *
 * Contains 16 questions across 3 categories:
 * - 인지 (Cognitive): 6 questions
 * - 감정 (Emotional): 4 questions
 * - 행동 (Behavioral): 6 questions
 */

function getQuestionsArray() {
    return [
    [
        'id' => 'reading',
        'category' => '인지',
        'question' => '수학 문제를 풀 때, 문제를 어떻게 읽나요?',
        'options' => [
            ['value' => 5, 'label' => '끝까지 꼼꼼히 여러 번 읽어요'],
            ['value' => 4, 'label' => '한 번은 천천히 끝까지 읽어요'],
            ['value' => 3, 'label' => '대충 읽고 바로 풀기 시작해요'],
            ['value' => 2, 'label' => '긴 문제는 읽다가 포기할 때가 많아요']
        ]
    ],
    [
        'id' => 'persistence',
        'category' => '행동',
        'question' => '어려운 문제를 만났을 때 보통 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '끝까지 붙잡고 꼭 풀어내려고 해요'],
            ['value' => 4, 'label' => '30분 정도는 고민해봐요'],
            ['value' => 3, 'label' => '10분 정도 시도하다가 답지를 봐요'],
            ['value' => 2, 'label' => '어려워 보이면 바로 넘겨요']
        ]
    ],
    [
        'id' => 'questioning',
        'category' => '행동',
        'question' => '모르는 내용이 있을 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '바로 선생님께 질문해요'],
            ['value' => 4, 'label' => '정리해서 나중에 물어봐요'],
            ['value' => 3, 'label' => '친구한테만 물어봐요'],
            ['value' => 2, 'label' => '그냥 넘어가는 편이에요']
        ]
    ],
    [
        'id' => 'timeManagement',
        'category' => '행동',
        'question' => '하루 중 수학 공부 시간을 어떻게 관리하고 있나요?',
        'options' => [
            ['value' => 5, 'label' => '계획표를 만들어서 규칙적으로 해요'],
            ['value' => 4, 'label' => '대략적인 시간은 정해두고 해요'],
            ['value' => 3, 'label' => '기분 내킬 때 해요'],
            ['value' => 2, 'label' => '시험 기간에만 몰아서 해요']
        ]
    ],
    [
        'id' => 'conceptUnderstanding',
        'category' => '인지',
        'question' => '새로운 수학 개념을 배울 때 어떤 스타일인가요?',
        'options' => [
            ['value' => 5, 'label' => '원리를 이해하려고 "왜?"를 계속 물어봐요'],
            ['value' => 4, 'label' => '예제를 통해 패턴을 찾아요'],
            ['value' => 3, 'label' => '공식을 외워서 문제를 풀어요'],
            ['value' => 2, 'label' => '이해가 안 되면 그냥 외워요']
        ]
    ],
    [
        'id' => 'errorAnalysis',
        'category' => '인지',
        'question' => '틀린 문제를 다시 볼 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '왜 틀렸는지 분석하고 비슷한 문제를 더 풀어요'],
            ['value' => 4, 'label' => '풀이를 보고 이해하려고 노력해요'],
            ['value' => 3, 'label' => '답만 확인하고 넘어가요'],
            ['value' => 2, 'label' => '틀린 문제는 잘 안 봐요']
        ]
    ],
    [
        'id' => 'logicalThinking',
        'category' => '인지',
        'question' => '문제를 풀 때 어떤 방식을 선호하나요?',
        'options' => [
            ['value' => 5, 'label' => '여러 방법으로 풀어보고 가장 좋은 걸 찾아요'],
            ['value' => 4, 'label' => '단계별로 차근차근 풀어나가요'],
            ['value' => 3, 'label' => '아는 방법 하나로만 풀어요'],
            ['value' => 2, 'label' => '감으로 푸는 경우가 많아요']
        ]
    ],
    [
        'id' => 'mathExpression',
        'category' => '인지',
        'question' => '수학 풀이를 쓸 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '과정을 깔끔하게 정리해서 써요'],
            ['value' => 4, 'label' => '중요한 과정은 다 써요'],
            ['value' => 3, 'label' => '머릿속으로 계산하고 답만 써요'],
            ['value' => 2, 'label' => '풀이 과정 쓰는 게 귀찮아요']
        ]
    ],
    [
        'id' => 'mathAnxiety',
        'category' => '감정',
        'question' => '수학 시험을 앞두고 어떤 기분이 드나요?',
        'options' => [
            ['value' => 5, 'label' => '자신 있어요! 빨리 보고 싶어요'],
            ['value' => 4, 'label' => '조금 긴장되지만 잘 볼 수 있을 거예요'],
            ['value' => 3, 'label' => '많이 떨리고 불안해요'],
            ['value' => 2, 'label' => '너무 무서워서 피하고 싶어요']
        ]
    ],
    [
        'id' => 'resilience',
        'category' => '감정',
        'question' => '문제를 틀렸을 때 당신의 마음은 어떤가요?',
        'options' => [
            ['value' => 5, 'label' => '다음엔 꼭 맞춰야지! 하고 의욕이 생겨요'],
            ['value' => 4, 'label' => '아쉽지만 다시 도전해요'],
            ['value' => 3, 'label' => '속상해서 잠깐 쉬어요'],
            ['value' => 2, 'label' => '자신감이 떨어지고 포기하고 싶어요']
        ]
    ],
    [
        'id' => 'motivation',
        'category' => '감정',
        'question' => '수학 공부를 하는 가장 큰 이유는 무엇인가요?',
        'options' => [
            ['value' => 5, 'label' => '수학이 재미있고 더 잘하고 싶어서요'],
            ['value' => 4, 'label' => '원하는 진로에 필요해서요'],
            ['value' => 3, 'label' => '부모님이 시켜서요'],
            ['value' => 2, 'label' => '안 하면 혼나니까요']
        ]
    ],
    [
        'id' => 'stressManagement',
        'category' => '감정',
        'question' => '수학 공부가 스트레스일 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '잠깐 쉬었다가 다시 집중해요'],
            ['value' => 4, 'label' => '쉬운 문제부터 다시 시작해요'],
            ['value' => 3, 'label' => '그날은 수학 공부를 안 해요'],
            ['value' => 2, 'label' => '며칠씩 수학을 피해요']
        ]
    ],
    [
        'id' => 'studyHabits',
        'category' => '행동',
        'question' => '평소 수학 공부 패턴은 어떤가요?',
        'options' => [
            ['value' => 5, 'label' => '매일 정해진 시간에 꾸준히 해요'],
            ['value' => 4, 'label' => '일주일에 4-5일은 해요'],
            ['value' => 3, 'label' => '숙제 있을 때만 해요'],
            ['value' => 2, 'label' => '시험 전에만 벼락치기해요']
        ]
    ],
    [
        'id' => 'concentration',
        'category' => '행동',
        'question' => '수학 문제 하나를 집중해서 풀 수 있는 시간은?',
        'options' => [
            ['value' => 5, 'label' => '1시간 이상도 가능해요'],
            ['value' => 4, 'label' => '30분 정도는 집중할 수 있어요'],
            ['value' => 3, 'label' => '15분 정도면 힘들어요'],
            ['value' => 2, 'label' => '5분만 지나도 딴 생각을 해요']
        ]
    ],
    [
        'id' => 'collaboration',
        'category' => '행동',
        'question' => '친구들과 함께 수학 공부할 때는 어떤가요?',
        'options' => [
            ['value' => 5, 'label' => '서로 가르치고 배우면서 함께 성장해요'],
            ['value' => 4, 'label' => '모르는 것만 물어보고 도움을 줘요'],
            ['value' => 3, 'label' => '혼자 하는 게 더 편해요'],
            ['value' => 2, 'label' => '같이 하면 집중이 안 돼요']
        ]
    ],
    [
        'id' => 'selfDirected',
        'category' => '인지',
        'question' => '마지막 질문이에요! 자신의 수학 실력을 어떻게 생각하나요?',
        'options' => [
            ['value' => 5, 'label' => '내 강점과 약점을 정확히 알고 있어요'],
            ['value' => 4, 'label' => '대략적으로는 알고 있어요'],
            ['value' => 3, 'label' => '잘 모르겠어요'],
            ['value' => 2, 'label' => '생각해본 적이 없어요']
        ]
    ]
];
