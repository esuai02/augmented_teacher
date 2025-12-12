<?php
/**
 * S4 오류 패턴 상황 응답 템플릿
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    // 기본 템플릿
    'default' => [
        'message' => '어려운 부분이 있는 것 같아요. 함께 해결해볼까요?',
        'cta' => '도움 받기',
        'tone' => 'supportive'
    ],

    // 오류 유형별 기본 메시지
    'error_types' => [
        'consecutive' => [
            'message' => '같은 부분에서 어려움을 겪고 계시네요. 다른 방식으로 설명해드릴까요?',
            'cta' => '다른 설명 보기',
            'threshold' => 3
        ],
        'same_type' => [
            'message' => '비슷한 유형의 문제에서 실수가 반복되고 있어요. 해당 개념을 다시 살펴볼까요?',
            'cta' => '개념 복습하기',
            'threshold' => 3
        ],
        'high_rate' => [
            'message' => '지금 좀 어려운 것 같아요. 기초부터 다시 천천히 해볼까요?',
            'cta' => '기초 다지기',
            'threshold' => 0.4
        ]
    ],

    // 인지적 페르소나별 오류 대응
    'cognitive' => [
        'C1' => [ // 탐색적 - 다양한 접근 제안
            'message' => '다른 방법으로 접근해볼까요? 여러 가지 풀이 방법이 있어요.',
            'cta' => '다른 접근법 보기',
            'strategy' => '다양한 해결책 탐색'
        ],
        'C2' => [ // 체계적 - 단계별 분석
            'message' => '어디서 막혔는지 단계별로 확인해볼게요. 차근차근 짚어보면 찾을 수 있어요.',
            'cta' => '단계별 분석',
            'strategy' => '체계적 오류 분석'
        ],
        'C3' => [ // 실용적 - 실제 적용 연결
            'message' => '이 개념이 실제로 어떻게 쓰이는지 예시로 보여드릴까요?',
            'cta' => '실제 예시 보기',
            'strategy' => '실용적 맥락 제공'
        ],
        'C4' => [ // 개념 중심 - 원리 설명
            'message' => '기본 원리부터 다시 짚어볼까요? 원리를 이해하면 응용이 쉬워져요.',
            'cta' => '원리 설명 보기',
            'strategy' => '개념적 토대 강화'
        ],
        'C5' => [ // 반복 - 유사 문제 연습
            'message' => '비슷한 유형의 쉬운 문제부터 연습해볼까요? 반복하다 보면 감이 와요.',
            'cta' => '연습 문제 풀기',
            'strategy' => '점진적 난이도 상승'
        ],
        'C6' => [ // 도전적 - 힌트 제공
            'message' => '좋은 시도예요! 살짝 힌트를 드릴게요. 스스로 해결하는 게 더 기억에 남아요.',
            'cta' => '힌트 보기',
            'strategy' => '최소한의 도움 제공'
        ]
    ],

    // 행동유형별 오류 대응
    'behavioral' => [
        'B1' => [ // 집중형
            'message' => '집중해서 풀고 계시네요. 잠시 다른 문제로 환기 후 다시 도전해볼까요?',
            'strategy' => 'context_switching'
        ],
        'B2' => [ // 탐험형
            'message' => '관련된 다른 콘텐츠를 먼저 살펴보고 다시 도전하는 건 어떨까요?',
            'strategy' => 'exploration_before_retry'
        ],
        'B3' => [ // 완료형
            'message' => '끝까지 하고 싶으시죠! 이 부분만 해결하면 완료할 수 있어요.',
            'strategy' => 'completion_motivation'
        ],
        'B4' => [ // 반복형
            'message' => '천천히 반복해보면 분명 해결될 거예요. 한 번 더 해볼까요?',
            'strategy' => 'encouragement_for_retry'
        ],
        'B5' => [ // 점프형
            'message' => '이 부분은 나중에 다시 와도 괜찮아요. 다른 부분 먼저 할까요?',
            'strategy' => 'skip_and_return'
        ],
        'B6' => [ // 협력형
            'message' => '비슷한 문제를 푼 친구들의 풀이를 참고해볼까요?',
            'strategy' => 'peer_learning'
        ]
    ],

    // 정서적 상태별 오류 대응
    'emotional' => [
        'E1' => [ // 불안/긴장
            'message' => '괜찮아요, 틀리는 건 배움의 과정이에요. 천천히 해봐요. 🌱',
            'cta' => '천천히 다시 하기',
            'support' => 'reassurance',
            'avoid' => ['시간 제한', '순위', '비교']
        ],
        'E2' => [ // 좌절/낙담
            'message' => '어려운 문제였어요. 포기하지 마세요, 같이 해결해봐요! 💪',
            'cta' => '함께 풀기',
            'support' => 'encouragement',
            'show_progress' => true
        ],
        'E3' => [ // 무관심/권태
            'message' => '다른 유형의 문제로 바꿔볼까요? 새로운 도전이 기다려요! ✨',
            'cta' => '새로운 유형 도전',
            'support' => 'novelty',
            'suggest_gamification' => true
        ],
        'E4' => [ // 자신감/열정
            'message' => '좋은 시도였어요! 이 실수에서 배울 점을 찾아볼까요? 🎯',
            'cta' => '오답 분석하기',
            'support' => 'growth_mindset',
            'show_learning_opportunity' => true
        ],
        'E5' => [ // 호기심/흥미
            'message' => '왜 틀렸는지 궁금하시죠? 함께 분석해봐요! 🔍',
            'cta' => '오류 원인 분석',
            'support' => 'intellectual_curiosity',
            'detailed_explanation' => true
        ],
        'E6' => [ // 평온/안정
            'message' => '한 번 더 확인해볼까요? 침착하게 다시 풀어봐요. 😊',
            'cta' => '다시 풀기',
            'support' => 'calm_guidance'
        ]
    ],

    // 복합 상황 특화 템플릿
    'composite' => [
        // 불안 + 연속 오류
        'anxious_consecutive' => [
            'condition' => ['emotional' => ['E1', 'E2'], 'error_type' => 'consecutive'],
            'message' => '어려운 부분이에요. 쉬운 문제로 자신감을 회복하고 다시 도전해봐요!',
            'cta' => '자신감 회복 문제',
            'intervention' => 'reduce_difficulty'
        ],
        // 도전적 + 높은 오류율
        'challenger_high_error' => [
            'condition' => ['cognitive' => ['C6'], 'error_type' => 'high_rate'],
            'message' => '도전 정신이 대단해요! 하지만 기초를 다지면 더 높이 갈 수 있어요.',
            'cta' => '기초 강화 훈련',
            'intervention' => 'foundation_building'
        ],
        // 체계적 + 동일 유형 오류
        'systematic_same_type' => [
            'condition' => ['cognitive' => ['C2'], 'error_type' => 'same_type'],
            'message' => '패턴이 보이네요. 이 개념을 체계적으로 정리해볼까요?',
            'cta' => '개념 정리 노트',
            'intervention' => 'structured_review'
        ]
    ],

    // 오류 분석 및 피드백 구조
    'feedback_structure' => [
        'immediate' => [
            'show_correct' => false,  // 즉시 정답 노출 안함
            'hint_level' => 1,
            'encouragement' => true
        ],
        'after_second_attempt' => [
            'show_partial_answer' => true,
            'hint_level' => 2,
            'offer_explanation' => true
        ],
        'after_third_attempt' => [
            'show_step_by_step' => true,
            'hint_level' => 3,
            'offer_similar_easier' => true
        ],
        'persistent_error' => [
            'show_full_solution' => true,
            'record_for_review' => true,
            'suggest_prerequisite' => true
        ]
    ],

    // 오류 유형별 학습 추천
    'learning_recommendations' => [
        'conceptual_error' => [
            'recommendation' => '관련 개념 강의 재시청',
            'resource_type' => 'video',
            'duration' => 'short'
        ],
        'calculation_error' => [
            'recommendation' => '연산 연습 문제',
            'resource_type' => 'practice',
            'quantity' => 5
        ],
        'comprehension_error' => [
            'recommendation' => '문제 읽기 연습',
            'resource_type' => 'reading',
            'highlight_keywords' => true
        ],
        'application_error' => [
            'recommendation' => '예제 풀이 학습',
            'resource_type' => 'example',
            'step_by_step' => true
        ]
    ]
];

/*
 * 관련 데이터베이스 테이블:
 * - mdl_agent19_error_patterns (id, user_id, content_id, error_type, error_count, context_data, created_at)
 * - mdl_agent19_intervention_log (id, user_id, intervention_type, template_id, outcome, created_at)
 */
