<?php
/**
 * Scenario Response Templates for Agent15 Problem Redefinition
 *
 * 10개 트리거 시나리오(S1-S10)별 응답 템플릿 정의
 * {variable} 형식의 플레이스홀더는 런타임에 치환됩니다.
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

return [
    /**
     * S1: 학습 성과 하락
     */
    'S1' => [
        'name' => '학습 성과 하락',
        'description' => '최근 성과가 이전 대비 15% 이상 하락',
        'primary_cause_layer' => 'cognitive',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 최근 성과가 조금 떨어진 것 같아요. 무슨 일이 있으셨나요?',
                'analysis' => '성과가 {change_rate}% 정도 변화했어요. 아직 크게 걱정할 수준은 아니에요.',
                'cause_exploration' => '혹시 최근에 공부 방식에 변화가 있었나요? 아니면 다른 일이 있으셨나요?',
                'action_suggestion' => '지금 상황을 파악하고, 작은 조정만 해도 금방 회복할 수 있어요.',
                'encouragement' => '일시적인 변동은 자연스러운 거예요. 함께 살펴보면 금방 해결할 수 있어요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 성과가 좀 많이 떨어졌네요. 함께 원인을 찾아볼까요?',
                'analysis' => '성과가 {change_rate}% 하락했어요. 단순한 변동이라기보다 원인이 있을 것 같아요.',
                'cause_exploration' => '최근 몇 주 동안 학습 패턴이나 환경에 변화가 있었나요?',
                'cognitive_check' => '혹시 특정 개념이나 유형에서 특히 어려움을 느끼셨나요?',
                'action_suggestion' => '원인을 찾고 나면, 맞춤형 해결책을 드릴 수 있어요.',
                'encouragement' => '지금 발견한 건 좋은 일이에요. 함께 해결해나가요!'
            ],
            'severe' => [
                'opening' => '{student_name}님, 성과 변화가 꽤 크네요. 걱정이 되시겠어요. 하지만 함께 해결해볼 수 있어요.',
                'empathy' => '이 정도 변화면 많이 답답하셨을 것 같아요. 그 마음 충분히 이해해요.',
                'analysis' => '성과가 {change_rate}% 하락했어요. 근본적인 원인을 찾아야 할 것 같아요.',
                'comprehensive_check' => '학습 방식, 개념 이해, 시간 관리, 동기... 여러 측면에서 살펴봐야 해요.',
                'action_suggestion' => '큰 변화가 필요할 수 있지만, 단계별로 접근하면 충분히 회복할 수 있어요.',
                'support' => '혼자 감당하지 마세요. 제가 옆에서 도와드릴게요.'
            ]
        ],

        'cause_analysis_prompts' => [
            'cognitive' => '최근 학습한 내용 중 이해가 어려웠던 부분이 있나요?',
            'behavioral' => '공부 습관이나 루틴에 변화가 있었나요?',
            'motivational' => '공부에 대한 흥미나 의욕이 예전과 다른가요?',
            'environmental' => '학습 환경이나 외부 상황에 변화가 있었나요?'
        ],

        'action_templates' => [
            'concept_review' => '**{weak_concept}** 개념을 다시 복습하고, 기본 문제부터 풀어보세요.',
            'diagnostic_test' => '진단 테스트를 통해 정확히 어디서 막혔는지 확인해볼게요.',
            'study_method_adjustment' => '현재 학습 방법을 조금 수정해볼게요. {new_method_suggestion}',
            'schedule_optimization' => '학습 스케줄을 조정해서 효율을 높여볼게요.'
        ]
    ],

    /**
     * S2: 학습 이탈 경고
     */
    'S2' => [
        'name' => '학습 이탈 경고',
        'description' => '이탈 위험 점수 또는 비활성 일수 기준 초과',
        'primary_cause_layer' => 'motivational',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 요즘 학습 빈도가 좀 줄은 것 같아요. 괜찮으세요?',
                'gentle_inquiry' => '바쁘셨거나, 다른 일이 있으셨나요?',
                'normalization' => '누구나 쉬어가는 시간이 필요해요. 그건 자연스러운 거예요.',
                'reconnection' => '다시 시작하기 좋은 때예요. 편하게 돌아오세요!',
                'encouragement' => '작은 것부터 다시 시작하면 돼요. 부담 갖지 마세요.'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 오랜만이에요. 보고 싶었어요!',
                'empathy' => '{inactive_days}일 동안 접속이 없었네요. 혹시 무슨 일이 있으셨나요?',
                'concern' => '학습을 계속하기 어려운 상황이셨나요? 도움이 필요하시면 말씀해주세요.',
                'gentle_probe' => '공부가 힘드셨는지, 아니면 다른 이유가 있었는지 궁금해요.',
                'support_offer' => '어떤 이유든, 함께 방법을 찾아볼 수 있어요.',
                'encouragement' => '다시 시작하는 게 가장 어려운 법이에요. 그런데 지금 여기 계시잖아요!'
            ],
            'severe' => [
                'opening' => '{student_name}님, 정말 오래 연락이 없었네요. 괜찮으세요?',
                'deep_empathy' => '많이 힘드셨나 봐요. 그 마음이 충분히 이해돼요.',
                'no_judgment' => '어떤 이유든, 아무 문제 없어요. 중요한 건 지금이에요.',
                'gentle_reconnection' => '부담 없이, 그냥 안부만 나눠도 괜찮아요.',
                'crisis_check' => '혹시 특별히 힘든 일이 있으셨나요? 학습 외의 부분에서도 도움이 필요하시면 말씀해주세요.',
                'unconditional_support' => '어떤 상황이든 함께 해결책을 찾을 수 있어요. 혼자 감당하지 마세요.'
            ]
        ],

        're_engagement_strategies' => [
            'low_barrier' => '오늘 딱 5분만, 쉬운 문제 하나 풀어볼까요?',
            'interest_based' => '가장 재미있었던 주제부터 다시 시작해볼까요?',
            'goal_reminder' => '{original_goal}을 향해 가고 있었잖아요. 다시 그 길로 돌아가볼까요?',
            'fresh_start' => '지금부터가 새로운 시작이에요. 과거는 신경 쓰지 말아요.'
        ]
    ],

    /**
     * S3: 동일 오답 반복
     */
    'S3' => [
        'name' => '동일 오답 반복',
        'description' => '동일 유형 오답 3회 이상 반복',
        'primary_cause_layer' => 'cognitive',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, {error_type} 유형에서 같은 실수가 반복되고 있어요.',
                'normalization' => '반복되는 실수는 보통 특정 개념이 완전히 익지 않아서 그래요. 흔한 일이에요!',
                'specific_feedback' => '특히 {specific_concept}에서 혼란이 있는 것 같아요.',
                'simple_fix' => '이 부분만 짚고 넘어가면 금방 해결될 거예요!',
                'encouragement' => '같은 실수를 인식한 것 자체가 발전이에요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, {error_type} 관련 오답이 여러 번 나오고 있어요. 한번 살펴볼까요?',
                'pattern_analysis' => '오답 패턴을 보면, {pattern_description}',
                'root_cause_probe' => '혹시 이 개념을 처음 배울 때 헷갈렸던 부분이 있었나요?',
                'misconception_check' => '{common_misconception}이라고 이해하고 계신 건 아닌가요?',
                'correction' => '실제로는 {correct_understanding}이에요.',
                'practice_suggestion' => '비슷한 유형 문제를 몇 개 더 풀어보면서 확실하게 익혀볼까요?'
            ],
            'severe' => [
                'opening' => '{student_name}님, {error_type} 유형에서 계속 막히고 계시네요. 함께 해결해봐요!',
                'empathy' => '같은 실수가 반복되면 정말 답답하시죠? 그 마음 알아요.',
                'deep_analysis' => '이 오류의 근본 원인을 찾아야 해요. 선수 학습에서 빠진 부분이 있을 수 있어요.',
                'prerequisite_check' => '{prerequisite_concept}는 확실히 이해하고 계신가요?',
                'structured_remediation' => '기초부터 차근차근 다시 쌓아볼게요. 시간이 좀 걸려도 괜찮아요.',
                'encouragement' => '제대로 이해하면 오히려 더 튼튼해져요. 함께 해볼까요?'
            ]
        ],

        'error_type_responses' => [
            'conceptual' => '개념 자체에 대한 이해가 필요해요.',
            'procedural' => '풀이 과정에서 놓치는 단계가 있어요.',
            'careless' => '계산 실수예요. 검토 습관을 들이면 좋겠어요.',
            'application' => '개념은 알지만 적용하는 방법이 익숙하지 않아요.'
        ]
    ],

    /**
     * S4: 루틴 불안정
     */
    'S4' => [
        'name' => '루틴 불안정',
        'description' => '학습 루틴 일관성 저하',
        'primary_cause_layer' => 'behavioral',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 요즘 학습 패턴이 조금 불규칙해진 것 같아요.',
                'gentle_observation' => '학습 시간이나 빈도가 예전과 달라졌네요.',
                'understanding' => '생활 패턴이 바뀌셨거나, 바쁘셨나요?',
                'suggestion' => '작은 루틴부터 다시 만들어볼까요?',
                'encouragement' => '완벽한 루틴보다 지킬 수 있는 루틴이 중요해요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 학습 루틴이 많이 흔들린 것 같아요.',
                'data_share' => '최근 {period} 동안 학습 시간 편차가 컸어요.',
                'impact_explanation' => '불규칙한 학습은 효율을 떨어뜨릴 수 있어요.',
                'root_cause_inquiry' => '루틴이 깨진 이유가 있을까요?',
                'restructure_offer' => '현실적으로 지킬 수 있는 새 루틴을 함께 만들어볼까요?',
                'flexibility_note' => '완벽하지 않아도 괜찮아요. 80%만 지켜도 성공이에요!'
            ],
            'severe' => [
                'opening' => '{student_name}님, 학습 패턴이 많이 불안정해요. 함께 살펴볼까요?',
                'empathy' => '루틴을 유지하기 어려운 상황이셨나 봐요.',
                'holistic_view' => '학습 루틴만의 문제가 아닐 수도 있어요. 전반적인 생활 패턴은 어떠세요?',
                'support_offer' => '어떤 어려움이 있으신지 말씀해주시면, 맞춤형으로 도와드릴게요.',
                'minimal_start' => '일단 아주 작은 것부터 시작해봐요. 하루 10분이라도요.',
                'encouragement' => '완전히 새로 시작해도 괜찮아요. 중요한 건 다시 시작하는 거예요!'
            ]
        ],

        'routine_building_tips' => [
            'anchor_habit' => '기존 습관(예: 식사 후)에 학습을 연결해보세요.',
            'time_boxing' => '정해진 시간에 짧게라도 규칙적으로 학습하세요.',
            'environment_cue' => '특정 장소를 학습 전용으로 지정해보세요.',
            'accountability' => '학습 기록을 남기면 루틴 유지에 도움이 돼요.'
        ]
    ],

    /**
     * S5: 시간관리 실패
     */
    'S5' => [
        'name' => '시간관리 실패',
        'description' => '계획 대비 실제 학습 시간 효율 저하',
        'primary_cause_layer' => 'behavioral',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 계획한 것보다 실제 학습 시간이 좀 적었어요.',
                'data' => '목표 대비 {efficiency_rate}% 정도 달성하셨네요.',
                'normalization' => '계획대로 안 되는 날도 있어요. 괜찮아요!',
                'adjustment' => '계획이 너무 빡빡했을 수도 있어요. 조금 조정해볼까요?',
                'tip' => '작은 버퍼 시간을 두면 계획 달성이 더 쉬워져요.'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 계획과 실제 학습 시간 차이가 좀 크네요.',
                'analysis' => '시간이 어디로 새는지 함께 파악해볼까요?',
                'distraction_check' => '학습 중 방해 요소가 있었나요?',
                'priority_assessment' => '해야 할 일들의 우선순위가 명확한가요?',
                'time_audit' => '하루 시간 사용을 한번 점검해보면 좋겠어요.',
                'strategy' => '시간 관리 전략을 몇 가지 알려드릴게요.'
            ],
            'severe' => [
                'opening' => '{student_name}님, 시간 관리에 많이 어려움을 겪고 계신 것 같아요.',
                'empathy' => '시간이 부족하면 정말 스트레스 받죠. 그 마음 이해해요.',
                'root_cause' => '시간 관리가 어려운 근본적인 이유가 있을까요?',
                'comprehensive_approach' => '단순히 계획을 세우는 것 이상의 접근이 필요해 보여요.',
                'priority_reset' => '일단 가장 중요한 것만 남기고 나머지는 과감히 줄여볼까요?',
                'support' => '함께 현실적인 계획을 세워볼게요. 혼자 고민하지 마세요.'
            ]
        ],

        'time_management_strategies' => [
            'pomodoro' => '포모도로 기법: 25분 집중 + 5분 휴식',
            'time_blocking' => '시간 블록킹: 특정 시간대에 특정 작업만',
            'priority_matrix' => '중요도-긴급도 매트릭스로 우선순위 정하기',
            'two_minute_rule' => '2분 안에 끝나는 일은 바로 처리하기'
        ]
    ],

    /**
     * S6: 정서/동기 저하
     */
    'S6' => [
        'name' => '정서/동기 저하',
        'description' => '동기 수준 저하 또는 부정적 감정 감지',
        'primary_cause_layer' => 'motivational',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 요즘 학습 의욕이 좀 떨어지셨나요?',
                'normalization' => '누구나 그런 시기가 있어요. 자연스러운 거예요.',
                'gentle_inquiry' => '혹시 특별히 힘든 점이 있으세요?',
                'small_motivation' => '작은 목표를 세우고 달성하면 다시 의욕이 생길 수 있어요.',
                'encouragement' => '지금 느끼는 감정은 지나가요. 조금만 버텨봐요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 많이 지치셨나 봐요. 괜찮으세요?',
                'empathy' => '학습이 힘들게 느껴지는 건 정말 힘든 일이에요.',
                'emotion_exploration' => '지금 어떤 기분이세요? 편하게 말씀해주세요.',
                'validation' => '그렇게 느끼시는 건 충분히 이해돼요.',
                'gradual_approach' => '무리하지 말고, 아주 작은 것부터 해보면 어떨까요?',
                'connection' => '공부가 왜 중요한지, 목표를 다시 한번 생각해보면 어떨까요?'
            ],
            'severe' => [
                'opening' => '{student_name}님, 지금 많이 힘드신 것 같아요. 제가 여기 있어요.',
                'deep_empathy' => '학습에 대한 의욕이 완전히 사라진 느낌이신가요?',
                'emotional_support' => '지금은 공부보다 {student_name}님의 마음이 더 중요해요.',
                'no_pressure' => '억지로 하지 않아도 괜찮아요. 쉬어가도 돼요.',
                'resource_offer' => '혹시 다른 도움이 필요하시면 말씀해주세요.',
                'unconditional_support' => '어떤 상태든 괜찮아요. 함께 천천히 해나가요.',
                'hope' => '지금은 힘들어도, 반드시 나아지는 날이 올 거예요.'
            ]
        ],

        'motivation_boosters' => [
            'purpose_reconnection' => '처음 공부를 시작한 이유를 떠올려보세요.',
            'progress_visualization' => '지금까지 해온 것들을 보세요. 많이 왔어요!',
            'reward_system' => '작은 성취에 스스로 보상을 주세요.',
            'peer_connection' => '같이 공부하는 친구가 있으면 도움이 돼요.'
        ]
    ],

    /**
     * S7: 개념 이해 부진
     */
    'S7' => [
        'name' => '개념 이해 부진',
        'description' => '전체 개념 이해도 저하 또는 취약 영역 다수',
        'primary_cause_layer' => 'cognitive',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 몇 가지 개념에서 어려움이 있는 것 같아요.',
                'specific_areas' => '특히 {weak_concepts} 부분이 좀 약해 보여요.',
                'normalization' => '이 부분은 많은 학생들이 어려워해요. 정상이에요!',
                'approach' => '기초부터 차근차근 다시 정리해볼까요?',
                'encouragement' => '조금만 집중하면 금방 이해할 수 있을 거예요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 개념 이해도가 전반적으로 낮아져 있어요.',
                'analysis' => '여러 개념에서 어려움이 있어요: {weak_areas}',
                'prerequisite_check' => '혹시 선수 학습에서 놓친 부분이 있을 수 있어요.',
                'diagnostic_suggestion' => '어디서부터 막혔는지 정확히 찾아볼까요?',
                'remediation_plan' => '기초부터 단계별로 다시 학습하면 확실해질 거예요.',
                'encouragement' => '시간이 좀 걸려도 제대로 이해하면 오히려 나중에 더 쉬워져요.'
            ],
            'severe' => [
                'opening' => '{student_name}님, 개념 이해에 큰 어려움이 있으신 것 같아요.',
                'empathy' => '많이 답답하셨을 것 같아요. 그 마음 충분히 이해해요.',
                'reassurance' => '하지만 괜찮아요. 지금부터 차근차근 쌓아가면 돼요.',
                'foundation_focus' => '가장 기초가 되는 {foundation_concept}부터 다시 시작해볼까요?',
                'pacing' => '서두르지 말고, 하나씩 확실하게 이해해가요.',
                'support' => '제가 옆에서 도와드릴게요. 혼자 고민하지 마세요.',
                'hope' => '지금 어려워도, 꾸준히 하면 반드시 이해하게 돼요!'
            ]
        ],

        'concept_teaching_strategies' => [
            'analogy' => '일상생활의 예시로 설명해드릴게요.',
            'visualization' => '그림이나 도표로 보면 더 쉬워요.',
            'step_by_step' => '단계별로 하나씩 짚어볼게요.',
            'practice_problems' => '쉬운 문제부터 풀어보면서 익혀볼까요?'
        ]
    ],

    /**
     * S8: 교사 피드백 경고
     */
    'S8' => [
        'name' => '교사 피드백 경고',
        'description' => '교사로부터 경고성 피드백 수신',
        'primary_cause_layer' => 'environmental',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 선생님께서 {feedback_summary}에 대해 말씀하셨어요.',
                'context' => '선생님이 관심을 갖고 지켜보고 계신 거예요.',
                'positive_frame' => '이건 개선의 기회예요!',
                'action' => '선생님 피드백을 참고해서 {improvement_area}을 개선해볼까요?',
                'encouragement' => '선생님도 {student_name}님이 잘했으면 하는 마음이에요.'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 선생님께서 몇 가지 우려를 표현하셨어요.',
                'feedback_detail' => '특히 {concern_areas}에 대해 걱정하고 계세요.',
                'understanding' => '선생님 입장에서는 그렇게 보일 수 있어요.',
                'collaborative_approach' => '선생님 피드백을 바탕으로 개선 계획을 세워볼까요?',
                'communication_suggestion' => '필요하면 선생님과 직접 이야기해보는 것도 좋아요.',
                'support' => '제가 도와드릴게요. 함께 해결해봐요!'
            ],
            'severe' => [
                'opening' => '{student_name}님, 선생님께서 긴급한 관심이 필요하다고 하셨어요.',
                'seriousness' => '이건 꼭 해결해야 하는 상황이에요.',
                'empathy' => '선생님 피드백을 받으면 기분이 좋지 않을 수 있어요. 이해해요.',
                'action_plan' => '지금 바로 대응 계획을 세워볼까요?',
                'teacher_engagement' => '선생님과 소통하면서 상황을 개선해 나갈 수 있어요.',
                'support' => '혼자 감당하지 마세요. 함께 해결해봐요.',
                'hope' => '적극적으로 대응하면 분명 좋아질 거예요!'
            ]
        ],

        'teacher_feedback_responses' => [
            'academic' => '학업 관련 피드백은 학습 방법 개선으로 해결할 수 있어요.',
            'behavioral' => '행동 관련 피드백은 의식적인 노력이 필요해요.',
            'engagement' => '참여도 관련 피드백은 적극성을 높이면 돼요.',
            'attendance' => '출석 관련 피드백은 루틴 개선이 도움이 돼요.'
        ]
    ],

    /**
     * S9: 전략 불일치
     */
    'S9' => [
        'name' => '전략 불일치',
        'description' => '현재 학습 전략의 효과성 저하',
        'primary_cause_layer' => 'cognitive',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 현재 학습 방법이 최적이 아닌 것 같아요.',
                'observation' => '열심히 하시는데 결과가 기대만큼 안 나오고 있어요.',
                'strategy_check' => '지금 어떤 방식으로 공부하고 계세요?',
                'adjustment' => '작은 조정만으로도 효과가 달라질 수 있어요.',
                'encouragement' => '방법을 바꾸면 같은 노력으로 더 좋은 결과를 얻을 수 있어요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 학습 전략을 점검해볼 필요가 있어요.',
                'mismatch_analysis' => '현재 방법이 {student_name}님의 학습 스타일과 안 맞을 수 있어요.',
                'style_assessment' => '{student_name}님은 {learning_style} 스타일인 것 같아요.',
                'new_approach' => '이런 스타일에는 {recommended_strategy}가 더 효과적이에요.',
                'experiment_proposal' => '새로운 방법을 일주일 정도 시도해볼까요?',
                'encouragement' => '맞는 방법을 찾으면 학습이 훨씬 수월해져요!'
            ],
            'severe' => [
                'opening' => '{student_name}님, 지금 학습 방법이 많이 비효율적인 것 같아요.',
                'honest_assessment' => '열심히 하시는 건 보이는데, 방법이 안 맞아요.',
                'complete_overhaul' => '학습 전략을 전면 재검토해볼 필요가 있어요.',
                'personalized_approach' => '{student_name}님에게 맞는 맞춤형 전략을 함께 만들어볼까요?',
                'patience_reminder' => '새 방법에 적응하는 데 시간이 좀 걸릴 수 있어요.',
                'support' => '제가 옆에서 도와드릴게요. 함께 최적의 방법을 찾아봐요!',
                'hope' => '제대로 된 방법을 찾으면 학습이 즐거워질 거예요!'
            ]
        ],

        'strategy_recommendations' => [
            'visual' => '시각적 학습: 마인드맵, 다이어그램, 색깔 코딩',
            'auditory' => '청각적 학습: 강의 듣기, 소리 내어 읽기, 녹음',
            'kinesthetic' => '체험적 학습: 직접 해보기, 실습, 움직이면서 학습',
            'reading_writing' => '읽기/쓰기 학습: 노트 정리, 요약, 글쓰기'
        }
    ],

    /**
     * S10: 회복 실패
     */
    'S10' => [
        'name' => '회복 실패',
        'description' => '이전 개입 시도 후 회복 실패',
        'primary_cause_layer' => 'environmental',

        'severity_templates' => [
            'mild' => [
                'opening' => '{student_name}님, 이전에 시도한 방법이 효과가 덜했네요.',
                'learning' => '이것도 중요한 정보예요. 뭐가 안 맞았는지 알게 됐으니까요.',
                'adjustment' => '방법을 조금 수정해볼까요?',
                'persistence' => '한 번에 안 되는 건 정상이에요. 다시 시도해봐요!',
                'encouragement' => '실패도 배움이에요. 다음엔 더 나아질 거예요!'
            ],
            'moderate' => [
                'opening' => '{student_name}님, 몇 번 시도했는데 아직 개선이 안 되고 있어요.',
                'empathy' => '노력해도 안 되면 정말 지치죠. 그 마음 알아요.',
                'root_cause_revisit' => '원인을 다시 살펴볼 필요가 있어요.',
                'different_approach' => '완전히 다른 접근법이 필요할 수도 있어요.',
                'external_factors' => '혹시 우리가 놓친 외부 요인이 있을까요?',
                'collaborative_problem_solving' => '함께 머리를 맞대고 새로운 방법을 찾아볼까요?'
            ],
            'severe' => [
                'opening' => '{student_name}님, 여러 번 시도했는데도 어려움이 계속되고 있어요.',
                'deep_empathy' => '정말 답답하고 지치셨을 거예요. 충분히 이해해요.',
                'no_blame' => '이건 {student_name}님 잘못이 아니에요.',
                'comprehensive_review' => '지금까지 시도한 모든 것을 다시 살펴보고, 왜 안 됐는지 분석해볼게요.',
                'fresh_perspective' => '완전히 새로운 관점에서 접근해볼 필요가 있어요.',
                'additional_support' => '추가적인 도움이나 자원이 필요할 수도 있어요.',
                'unconditional_support' => '포기하지 마세요. 방법은 반드시 있어요. 함께 찾아볼게요.',
                'hope' => '지금까지 버텨온 것만으로도 대단해요. 분명 해결책을 찾을 수 있어요!'
            ]
        ],

        'recovery_strategies' => [
            'restart' => '처음부터 다시 시작하는 것도 방법이에요.',
            'expert_help' => '전문가의 도움을 받는 것을 고려해보세요.',
            'peer_support' => '비슷한 경험을 한 친구의 조언이 도움이 될 수 있어요.',
            'environment_change' => '학습 환경을 완전히 바꿔보는 건 어떨까요?'
        ]
    ]
];
