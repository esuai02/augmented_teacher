<?php
/**
 * Agent04 Persona System - Type Learning Chat Interface
 *
 * rules02.yaml 기반 유형학습 인지관성 패턴 탐지를 위한 대화형 인터페이스
 *
 * 특징:
 * - 3탭 구조: 학생 대화 / 선생님 입력 / 시스템 데이터
 * - 아코디언 스타일의 순차 질문 진행
 * - 타이핑 효과 애니메이션
 * - 대화체 질문 (간접적, 친근한 톤)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04_InspectWeakpoints
 * @version     3.0.0
 * @author      Augmented Teacher Team
 * @created     2025-12-04
 *
 * 파일 위치: /alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/chat02.php
 */

// Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  ");
$role=$userrole->data;

// 현재 파일 정보 (에러 추적용)
define('CURRENT_FILE', __FILE__);

/**
 * 유형학습 대화형 질문 데이터 - 간접적이고 친근한 톤
 */
class TypeLearningQuestions {

    /**
     * 학생 대화 질문 - rules02.yaml 기반
     */
    public static $studentQuestions = [
        // S1: 대표유형 효능감 인식 (type_efficacy)
        [
            'id' => 'new_type_confidence',
            'category' => '유형 효능감',
            'icon' => '💪',
            'preview' => '새로운 유형에 대한 자신감',
            'message' => '처음 보는 문제 유형을 만나면 어떤 느낌이 드세요? 솔직하게 말해줘요.',
            'type' => 'buttons',
            'options' => [
                'afraid' => '무서워요, 시도 전에 포기하고 싶어요 😰',
                'nervous' => '긴장되지만 해볼게요',
                'okay' => '괜찮아요, 일단 시도해봐요',
                'confident' => '새로운 거 좋아요! 🎯'
            ]
        ],
        [
            'id' => 'past_failure_feeling',
            'category' => '유형 효능감',
            'icon' => '😔',
            'preview' => '예전에 틀렸던 유형',
            'message' => '예전에 틀렸던 유형을 다시 만나면 "나는 이거 못해..."라고 생각하나요?',
            'type' => 'buttons',
            'options' => [
                'always' => '항상 그래요 😢',
                'often' => '자주 그래요',
                'sometimes' => '가끔요',
                'never' => '아니요, 다시 도전해요!'
            ]
        ],
        [
            'id' => 'self_assessment_accuracy',
            'category' => '유형 효능감',
            'icon' => '🤔',
            'preview' => '실력 자기평가',
            'message' => '유형 문제 푸는 실력이 어느 정도라고 생각하세요?',
            'type' => 'scale',
            'options' => [
                1 => '전혀 못해요',
                3 => '좀 부족해요',
                5 => '보통이에요',
                7 => '꽤 잘해요',
                10 => '자신 있어요!'
            ]
        ],

        // S2: 주간목표 (weekly_goal)
        [
            'id' => 'weekly_goal_setting',
            'category' => '주간목표',
            'icon' => '📅',
            'preview' => '주간 목표 설정',
            'message' => '이번 주 유형학습 목표를 세웠나요?',
            'type' => 'buttons',
            'options' => [
                'none' => '목표 없이 그냥 해요',
                'vague' => '대충 "많이 풀자" 정도요',
                'specific' => '구체적으로 세웠어요',
                'smart' => 'SMART하게 세웠어요! 📋'
            ]
        ],
        [
            'id' => 'weekly_goal_achievement',
            'category' => '주간목표',
            'icon' => '📊',
            'preview' => '목표 달성률',
            'message' => '지난주 세운 목표는 얼마나 달성했어요?',
            'type' => 'scale',
            'options' => [
                0 => '아예 못 했어요 😅',
                25 => '조금 했어요',
                50 => '반 정도요',
                75 => '대부분 했어요',
                100 => '다 달성했어요! 🎉'
            ]
        ],
        [
            'id' => 'goal_flexibility',
            'category' => '주간목표',
            'icon' => '🔄',
            'preview' => '목표 조정',
            'message' => '상황이 바뀌면 목표를 유연하게 조정하나요?',
            'type' => 'buttons',
            'options' => [
                'rigid' => '처음 목표 고집해요',
                'rarely' => '거의 안 바꿔요',
                'sometimes' => '가끔 조정해요',
                'flexible' => '상황에 맞게 조정해요'
            ]
        ],

        // S3: 오늘목표 (daily_goal)
        [
            'id' => 'daily_plan_habit',
            'category' => '오늘목표',
            'icon' => '📝',
            'preview' => '오늘 계획 세우기',
            'message' => '오늘 공부 시작할 때 뭘 할지 정하고 시작하나요?',
            'type' => 'buttons',
            'options' => [
                'no_plan' => '그냥 책 펴요',
                'mental' => '머릿속으로만요',
                'simple' => '간단히 정해요',
                'detailed' => '구체적으로 적어요! ✍️'
            ]
        ],
        [
            'id' => 'daily_type_count',
            'category' => '오늘목표',
            'icon' => '📚',
            'preview' => '하루 새 유형 수',
            'message' => '하루에 새로운 유형을 몇 개나 공부하려고 하세요?',
            'type' => 'buttons',
            'options' => [
                'many' => '많이요! 5개 이상',
                'some' => '3-4개 정도요',
                'few' => '1-2개요',
                'depends' => '컨디션 봐서요'
            ]
        ],
        [
            'id' => 'completion_check',
            'category' => '오늘목표',
            'icon' => '✅',
            'preview' => '완료 확인',
            'message' => '공부 끝나고 오늘 목표를 달성했는지 확인하나요?',
            'type' => 'buttons',
            'options' => [
                'never' => '안 해요',
                'sometimes' => '가끔 해요',
                'usually' => '보통 해요',
                'always' => '항상 체크해요! ✓'
            ]
        ],

        // S4: 포모도로 (pomodoro_selection)
        [
            'id' => 'pomodoro_break',
            'category' => '포모도로',
            'icon' => '⏰',
            'preview' => '휴식 시간',
            'message' => '공부하다 휴식 시간을 잘 지키나요?',
            'type' => 'buttons',
            'options' => [
                'skip' => '휴식 건너뛰고 계속해요',
                'sometimes' => '가끔 쉬어요',
                'usually' => '대체로 지켜요',
                'always' => '규칙적으로 쉬어요! 😊'
            ]
        ],
        [
            'id' => 'focus_duration',
            'category' => '포모도로',
            'icon' => '🎯',
            'preview' => '집중 지속 시간',
            'message' => '한 번에 얼마나 집중할 수 있어요?',
            'type' => 'buttons',
            'options' => [
                'short' => '10분도 힘들어요',
                'medium' => '15-20분 정도요',
                'good' => '25분은 괜찮아요',
                'long' => '30분 이상도 OK!'
            ]
        ],
        [
            'id' => 'distraction_handling',
            'category' => '포모도로',
            'icon' => '📱',
            'preview' => '방해 요소 처리',
            'message' => '공부할 때 핸드폰이나 다른 방해가 자주 있나요?',
            'type' => 'buttons',
            'options' => [
                'very' => '계속 방해받아요 😵',
                'often' => '자주요',
                'sometimes' => '가끔요',
                'rarely' => '잘 차단해요 🔕'
            ]
        ],

        // S5: 방법선택 (method_selection)
        [
            'id' => 'solving_method_variety',
            'category' => '방법선택',
            'icon' => '🔧',
            'preview' => '풀이 방법 다양성',
            'message' => '같은 유형을 여러 방법으로 풀어본 적 있나요?',
            'type' => 'buttons',
            'options' => [
                'one_way' => '한 가지로만 풀어요',
                'rarely' => '거의 안 해봤어요',
                'sometimes' => '가끔 해봐요',
                'often' => '자주 비교해봐요! 📊'
            ]
        ],
        [
            'id' => 'method_stuck',
            'category' => '방법선택',
            'icon' => '😕',
            'preview' => '방법 선택 고민',
            'message' => '어떤 방법으로 풀지 결정 못 해서 시간 낭비한 적 있나요?',
            'type' => 'buttons',
            'options' => [
                'often' => '자주 그래요',
                'sometimes' => '가끔 그래요',
                'rarely' => '별로 없어요',
                'never' => '바로 결정해요'
            ]
        ],
        [
            'id' => 'new_method_trial',
            'category' => '방법선택',
            'icon' => '✨',
            'preview' => '새 방법 시도',
            'message' => '새로운 풀이 방법을 배우면 실제로 써보나요?',
            'type' => 'buttons',
            'options' => [
                'no' => '익숙한 걸로 해요',
                'hesitate' => '망설여져요',
                'try' => '한 번은 해봐요',
                'active' => '적극적으로 써봐요! 💪'
            ]
        ],

        // S6: 순서선택 (order_selection)
        [
            'id' => 'problem_order',
            'category' => '순서선택',
            'icon' => '📋',
            'preview' => '문제 풀이 순서',
            'message' => '문제 풀 때 순서를 어떻게 정하나요?',
            'type' => 'buttons',
            'options' => [
                'sequential' => '순서대로 풀어요',
                'easy_first' => '쉬운 것부터요',
                'hard_first' => '어려운 것부터요',
                'strategic' => '전략적으로 섞어요 🎯'
            ]
        ],
        [
            'id' => 'hard_type_avoidance',
            'category' => '순서선택',
            'icon' => '😰',
            'preview' => '어려운 유형 회피',
            'message' => '어려운 유형은 자꾸 미루게 되나요?',
            'type' => 'buttons',
            'options' => [
                'always' => '항상 미뤄요',
                'often' => '자주 그래요',
                'sometimes' => '가끔요',
                'never' => '먼저 해치워요! 💪'
            ]
        ],
        [
            'id' => 'stuck_handling',
            'category' => '순서선택',
            'icon' => '🚧',
            'preview' => '막혔을 때 대처',
            'message' => '문제에 막히면 어떻게 하나요?',
            'type' => 'buttons',
            'options' => [
                'stuck' => '계속 붙잡고 있어요',
                'give_up' => '포기해요',
                'skip' => '표시하고 넘어가요',
                'hint' => '힌트 보고 다시 해요'
            ]
        ],

        // S7: 시간간격 선택 (interval_selection)
        [
            'id' => 'study_pattern',
            'category' => '시간간격',
            'icon' => '📅',
            'preview' => '공부 패턴',
            'message' => '유형학습을 어떤 패턴으로 하고 있나요?',
            'type' => 'buttons',
            'options' => [
                'cramming' => '몰아서 해요',
                'irregular' => '불규칙하게요',
                'regular' => '규칙적으로요',
                'spaced' => '간격 두고 반복해요 📈'
            ]
        ],
        [
            'id' => 'effective_focus_ratio',
            'category' => '시간간격',
            'icon' => '⏱️',
            'preview' => '실제 집중 비율',
            'message' => '공부 시간 중 실제로 집중하는 시간은 어느 정도예요?',
            'type' => 'scale',
            'options' => [
                20 => '20% 정도 😅',
                40 => '40% 정도',
                60 => '60% 정도',
                80 => '80% 정도',
                100 => '거의 다요! 💯'
            ]
        ],
        [
            'id' => 'review_schedule',
            'category' => '시간간격',
            'icon' => '🔄',
            'preview' => '복습 일정',
            'message' => '배운 유형을 언제 복습하나요?',
            'type' => 'buttons',
            'options' => [
                'never' => '따로 안 해요',
                'before_test' => '시험 전에요',
                'irregular' => '생각날 때요',
                'scheduled' => '1일, 3일, 7일 간격으로요! 📅'
            ]
        ],

        // S8: 보충학습 (supplementary_learning)
        [
            'id' => 'weak_area_study',
            'category' => '보충학습',
            'icon' => '📖',
            'preview' => '약한 부분 공부',
            'message' => '부족한 유형을 따로 보충하고 있나요?',
            'type' => 'buttons',
            'options' => [
                'avoid' => '피하게 돼요 😅',
                'later' => '나중에 하려고요',
                'sometimes' => '가끔 해요',
                'priority' => '우선적으로 해요! 💪'
            ]
        ],
        [
            'id' => 'supplementary_method',
            'category' => '보충학습',
            'icon' => '🎯',
            'preview' => '보충 방법',
            'message' => '보충학습은 어떻게 하고 있나요?',
            'type' => 'buttons',
            'options' => [
                'no_method' => '그냥 다시 풀어요',
                'read_again' => '개념만 다시 읽어요',
                'example' => '예제부터 다시 해요',
                'systematic' => '개념→예제→문제 순으로요! 📚'
            ]
        ],
        [
            'id' => 'supplementary_scope',
            'category' => '보충학습',
            'icon' => '🔍',
            'preview' => '보충 범위',
            'message' => '보충할 때 범위를 어떻게 정하나요?',
            'type' => 'buttons',
            'options' => [
                'all' => '다 해야 할 것 같아요',
                'too_much' => '많이 하는 편이에요',
                'necessary' => '필요한 것만요',
                'focused' => '가장 약한 3개만요! 🎯'
            ]
        ],

        // S9: 힌트설정 (hint_setting)
        [
            'id' => 'hint_dependency',
            'category' => '힌트설정',
            'icon' => '💡',
            'preview' => '힌트 의존도',
            'message' => '힌트 없이 문제를 시도하나요?',
            'type' => 'buttons',
            'options' => [
                'need_hint' => '힌트 없으면 못해요',
                'quick_hint' => '빨리 힌트 봐요',
                'try_first' => '먼저 시도해봐요',
                'independent' => '5분은 혼자 해봐요! ⏰'
            ]
        ],
        [
            'id' => 'hint_usage',
            'category' => '힌트설정',
            'icon' => '🎫',
            'preview' => '힌트 사용량',
            'message' => '문제당 힌트를 얼마나 사용하나요?',
            'type' => 'buttons',
            'options' => [
                'many' => '여러 개 봐요',
                'some' => '2-3개 정도요',
                'one' => '1개만 봐요',
                'minimal' => '정말 필요할 때만요'
            ]
        ],
        [
            'id' => 'stuck_without_hint',
            'category' => '힌트설정',
            'icon' => '🤔',
            'preview' => '힌트 없이 막힐 때',
            'message' => '막혔는데 힌트 안 보고 버티는 편인가요?',
            'type' => 'buttons',
            'options' => [
                'long' => '오래 붙잡고 있어요',
                'medium' => '한참 버텨요',
                'short' => '잠깐 시도하고 봐요',
                'efficient' => '10분 규칙 써요! ⏱️'
            ]
        ],

        // S10: 질의응답 (qa_interaction)
        [
            'id' => 'question_habit',
            'category' => '질의응답',
            'icon' => '🙋',
            'preview' => '질문 습관',
            'message' => '모르는 게 있으면 질문하나요?',
            'type' => 'buttons',
            'options' => [
                'afraid' => '질문하기 무서워요',
                'later' => '나중에 하려다 안 해요',
                'search' => '혼자 찾아봐요',
                'ask' => '바로 질문해요! 🙋'
            ]
        ],
        [
            'id' => 'question_clarity',
            'category' => '질의응답',
            'icon' => '📢',
            'preview' => '질문 명확성',
            'message' => '질문할 때 뭐가 모르는지 명확하게 말하나요?',
            'type' => 'buttons',
            'options' => [
                'vague' => '"이거 모르겠어요"라고만요',
                'somewhat' => '대충 설명해요',
                'clear' => '꽤 명확히 해요',
                'structured' => '"[유형]에서 [부분]이 왜 이런지"로요! 📝'
            ]
        ],
        [
            'id' => 'question_timing',
            'category' => '질의응답',
            'icon' => '⏰',
            'preview' => '질문 타이밍',
            'message' => '궁금한 게 생기면 언제 질문하나요?',
            'type' => 'buttons',
            'options' => [
                'never' => '결국 안 해요',
                'much_later' => '한참 뒤에요',
                'next_day' => '다음 날이요',
                'within_24h' => '24시간 내로요! ⚡'
            ]
        ]
    ];

    /**
     * 선생님 판단 질문
     */
    public static $teacherQuestions = [
        [
            'id' => 'pattern_confirmed',
            'category' => '패턴 확정',
            'icon' => '🎯',
            'preview' => 'AI 탐지 패턴 확인',
            'message' => '시스템이 탐지한 학생의 유형학습 인지관성 패턴을 확인해주세요.',
            'type' => 'buttons',
            'options' => [
                'confirmed' => '확정합니다',
                'modified' => '수정이 필요해요',
                'rejected' => '해당 없음'
            ]
        ],
        [
            'id' => 'pattern_override',
            'category' => '패턴 지정',
            'icon' => '✏️',
            'preview' => '패턴 수동 지정',
            'message' => '관찰하신 유형학습 패턴이 있다면 선택해주세요.',
            'type' => 'select',
            'options' => [
                '' => '선택 안함',
                'type_fear' => '새유형 두려움형',
                'past_failure_fixation' => '과거실패 고착형',
                'self_underestimation' => '자기과소평가형',
                'unrealistic_goal' => '비현실적 목표형',
                'goal_absence' => '목표 부재형',
                'goal_rigidity' => '목표 경직형',
                'overload_goal' => '과부하 목표형',
                'planless_start' => '무계획 시작형',
                'break_skip' => '휴식 건너뛰기형',
                'pomodoro_abandonment' => '포모도로 포기형',
                'blind_repetition' => '무작정 반복형',
                'familiar_method_fixation' => '익숙함 고집형',
                'method_confusion' => '방법 혼란형',
                'easy_first_only' => '쉬운것만 먼저형',
                'cramming' => '벼락치기형',
                'supplementary_avoidance' => '보충 회피형',
                'hint_dependency' => '힌트 의존형',
                'question_fear' => '질문 두려움형',
                'question_procrastination' => '질문 미루기형'
            ]
        ],
        [
            'id' => 'confidence_adjustment',
            'category' => '신뢰도',
            'icon' => '📊',
            'preview' => '신뢰도 조정',
            'message' => '패턴 탐지의 정확도를 어떻게 평가하시나요?',
            'type' => 'scale',
            'options' => [
                50 => '50% - 낮음',
                65 => '65%',
                75 => '75%',
                85 => '85% - 기본',
                95 => '95%',
                100 => '100% - 확실'
            ]
        ],
        [
            'id' => 'intervention_effectiveness',
            'category' => '개입 평가',
            'icon' => '📈',
            'preview' => '개입 효과',
            'message' => '지금까지의 개입이 얼마나 효과적이었나요?',
            'type' => 'scale',
            'options' => [
                1 => '전혀 효과 없음',
                2 => '약간 효과 있음',
                3 => '보통',
                4 => '효과적',
                5 => '매우 효과적'
            ]
        ],
        [
            'id' => 'intervention_notes',
            'category' => '관찰 기록',
            'icon' => '📝',
            'preview' => '관찰 노트',
            'message' => '학생의 유형학습에 대한 추가 관찰 사항을 기록해주세요.',
            'type' => 'textarea',
            'placeholder' => '학생의 유형 학습 패턴, 변화, 반응, 특이사항 등...'
        ],
        [
            'id' => 'custom_action',
            'category' => '맞춤 지도',
            'icon' => '🎓',
            'preview' => '맞춤형 활동',
            'message' => '이 학생의 유형학습 개선을 위해 추천하는 활동이 있나요?',
            'type' => 'textarea',
            'placeholder' => '예: 하루 새 유형 3개 규칙, 샌드위치 순서법, 간격 반복 일정표 등...'
        ]
    ];

    /**
     * 시스템 데이터 필드 정의
     */
    public static $systemDataFields = [
        // AI 탐지 결과
        [
            'id' => 'ai_detected_pattern',
            'category' => 'AI 탐지 결과',
            'icon' => '🤖',
            'label' => '탐지된 패턴',
            'type' => 'display',
            'source' => 'ai_model'
        ],
        [
            'id' => 'ai_confidence',
            'category' => 'AI 탐지 결과',
            'icon' => '📊',
            'label' => 'AI 신뢰도',
            'type' => 'gauge',
            'source' => 'ai_model'
        ],
        [
            'id' => 'detection_timestamp',
            'category' => 'AI 탐지 결과',
            'icon' => '🕐',
            'label' => '탐지 시각',
            'type' => 'timestamp',
            'source' => 'system'
        ],

        // 유형학습 분석 데이터
        [
            'id' => 'type_completion_rate',
            'category' => '유형학습 분석',
            'icon' => '📈',
            'label' => '유형 완료율',
            'type' => 'percentage',
            'source' => 'learning_analytics'
        ],
        [
            'id' => 'weekly_goal_achievement',
            'category' => '유형학습 분석',
            'icon' => '🎯',
            'label' => '주간 목표 달성률',
            'type' => 'percentage',
            'source' => 'learning_analytics'
        ],
        [
            'id' => 'method_variety_score',
            'category' => '유형학습 분석',
            'icon' => '🔧',
            'label' => '방법 다양성 점수',
            'type' => 'score',
            'source' => 'learning_analytics'
        ],
        [
            'id' => 'hint_dependency_rate',
            'category' => '유형학습 분석',
            'icon' => '💡',
            'label' => '힌트 의존도',
            'type' => 'percentage',
            'source' => 'learning_analytics'
        ],

        // 패턴 모델링 결과
        [
            'id' => 'pattern_history',
            'category' => '패턴 모델링',
            'icon' => '📉',
            'label' => '패턴 변화 추이',
            'type' => 'chart',
            'source' => 'pattern_model'
        ],
        [
            'id' => 'risk_factors',
            'category' => '패턴 모델링',
            'icon' => '⚠️',
            'label' => '위험 요소',
            'type' => 'list',
            'source' => 'pattern_model'
        ],
        [
            'id' => 'improvement_areas',
            'category' => '패턴 모델링',
            'icon' => '🎯',
            'label' => '개선 필요 영역',
            'type' => 'list',
            'source' => 'pattern_model'
        ],
        [
            'id' => 'recommended_interventions',
            'category' => '패턴 모델링',
            'icon' => '💡',
            'label' => '추천 개입 방법',
            'type' => 'list',
            'source' => 'pattern_model'
        ]
    ];
}

// 요청 처리
$studentId = optional_param('student_id', $USER->id, PARAM_INT);
$activeTab = optional_param('tab', 'student', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);

// AJAX 데이터 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    header('Content-Type: application/json');

    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $record = new stdClass();
        $record->userid = $studentId;
        $record->data_type = ($data['type'] ?? 'student_chat') . '_type_learning';
        $record->data_json = json_encode($data['answers'], JSON_UNESCAPED_UNICODE);
        $record->created_at = time();
        $record->created_by = $USER->id;

        $DB->insert_record('agent04_chat_data', $record);

        echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
    } catch (Exception $e) {
        error_log("chat02.php:" . __LINE__ . " - 저장 오류: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '저장 중 오류: ' . $e->getMessage()]);
    }
    exit;
}

// 시스템 데이터 조회 (실제 구현 시 DB에서 가져옴)
function getSystemData($studentId) {
    global $DB;

    // 샘플 데이터 (실제로는 DB에서 조회)
    return [
        'ai_detected_pattern' => '새유형 두려움형',
        'ai_confidence' => 82,
        'detection_timestamp' => time() - 86400,
        'type_completion_rate' => 45,
        'weekly_goal_achievement' => 38,
        'method_variety_score' => 3.2,
        'hint_dependency_rate' => 72,
        'pattern_history' => [
            ['date' => '11/25', 'score' => 35],
            ['date' => '11/27', 'score' => 42],
            ['date' => '11/29', 'score' => 38],
            ['date' => '12/01', 'score' => 48],
            ['date' => '12/03', 'score' => 55]
        ],
        'risk_factors' => ['새 유형 시도 전 포기', '목표 설정 부재', '힌트 과의존'],
        'improvement_areas' => ['점진적 유형 노출', 'SMART 목표 설정', '5분 먼저 시도 규칙'],
        'recommended_interventions' => [
            '쉬운 문제 3개 먼저 풀기',
            '70% 목표 규칙',
            '하루 새 유형 3개 제한'
        ]
    ];
}

$systemData = getSystemData($studentId);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent04 - 유형학습 진단</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-light: #a78bfa;
            --primary-dark: #7c3aed;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #0f172a;
            --bg-light: #1e293b;
            --bg-card: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #475569;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding: 30px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* 탭 네비게이션 */
        .tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            background: var(--bg-light);
            padding: 8px;
            border-radius: 12px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .tab-btn:hover {
            background: var(--bg-card);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn .icon {
            font-size: 1.2rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 진행률 바 */
        .progress-bar {
            height: 4px;
            background: var(--bg-card);
            border-radius: 2px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            width: 0;
            transition: width 0.5s ease;
        }

        .progress-text {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        /* 질문 카드 */
        .question-card {
            background: var(--bg-light);
            border-radius: 16px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .question-card.locked {
            opacity: 0.5;
            pointer-events: none;
        }

        .question-card.completed {
            opacity: 0.7;
        }

        .question-card.completed .question-status::after {
            content: '✓';
            color: var(--success);
        }

        .question-button {
            width: 100%;
            padding: 16px 20px;
            background: transparent;
            border: none;
            color: var(--text);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }

        .question-icon {
            font-size: 1.5rem;
        }

        .question-preview {
            flex: 1;
        }

        .question-preview .category {
            font-size: 0.75rem;
            color: var(--primary-light);
            margin-bottom: 2px;
        }

        .question-preview .title {
            font-size: 0.95rem;
            color: var(--text);
        }

        .question-status {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .question-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding: 0 20px;
        }

        .question-card.active .question-content {
            max-height: 500px;
            padding: 0 20px 20px;
        }

        /* 타이핑 영역 */
        .typing-area {
            background: var(--bg-card);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            min-height: 60px;
        }

        .typing-text {
            color: var(--text);
            line-height: 1.6;
        }

        .typing-cursor {
            display: inline-block;
            width: 2px;
            height: 1em;
            background: var(--primary);
            margin-left: 2px;
            animation: blink 0.7s infinite;
        }

        @keyframes blink {
            50% { opacity: 0; }
        }

        /* 답변 옵션 */
        .answer-options {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s;
        }

        .answer-options.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .answer-btn, .scale-btn, .multi-btn {
            display: block;
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 8px;
            background: var(--bg-card);
            border: 2px solid transparent;
            border-radius: 12px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
            text-align: left;
        }

        .answer-btn:hover, .scale-btn:hover, .multi-btn:hover {
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.1);
        }

        .answer-btn.selected, .scale-btn.selected, .multi-btn.selected {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* 스케일 옵션 */
        .scale-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .scale-btn {
            flex: 1;
            min-width: calc(50% - 4px);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px;
        }

        .scale-btn .value {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .scale-btn span:last-child {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .scale-btn.selected span:last-child {
            color: white;
        }

        /* 멀티셀렉트 */
        .multiselect-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .multi-btn {
            flex: 1;
            min-width: calc(50% - 4px);
            text-align: center;
        }

        .multi-submit {
            margin-top: 12px;
            width: 100%;
            padding: 14px;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .multi-submit:hover {
            background: var(--primary-dark);
        }

        /* 텍스트 입력 */
        .text-input-area {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .text-input {
            width: 100%;
            padding: 14px;
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 0.95rem;
            resize: none;
            min-height: 100px;
        }

        .text-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .text-submit {
            padding: 14px;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }

        /* 셀렉트 */
        .select-input {
            width: 100%;
            padding: 14px;
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .select-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .select-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }

        /* 완료 화면 */
        .completion-screen {
            display: none;
            text-align: center;
            padding: 60px 20px;
        }

        .completion-screen.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .completion-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .completion-screen h2 {
            color: var(--success);
            margin-bottom: 10px;
        }

        .completion-screen p {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .restart-btn {
            padding: 14px 30px;
            background: var(--bg-card);
            border: 2px solid var(--primary);
            border-radius: 12px;
            color: var(--primary);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .restart-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* 시스템 데이터 탭 */
        .system-section {
            margin-bottom: 24px;
        }

        .system-section-title {
            font-size: 1rem;
            color: var(--primary-light);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .system-card {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .system-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .system-card-icon {
            font-size: 1.2rem;
        }

        .system-card-label {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .system-card-value {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .system-card-value.success { color: var(--success); }
        .system-card-value.warning { color: var(--warning); }
        .system-card-value.danger { color: var(--danger); }

        /* 게이지 */
        .gauge-container {
            margin-top: 8px;
        }

        .gauge-bar {
            height: 8px;
            background: var(--bg-card);
            border-radius: 4px;
            overflow: hidden;
        }

        .gauge-fill {
            height: 100%;
            transition: width 0.5s ease;
        }

        .gauge-fill.high { background: var(--success); }
        .gauge-fill.medium { background: var(--warning); }
        .gauge-fill.low { background: var(--danger); }

        /* 미니 차트 */
        .mini-chart {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 60px;
            margin-top: 12px;
        }

        .mini-chart-bar {
            flex: 1;
            background: var(--primary);
            border-radius: 2px 2px 0 0;
            min-height: 4px;
            transition: height 0.3s;
        }

        /* 리스트 */
        .system-list {
            list-style: none;
            padding: 0;
        }

        .system-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .system-list li:last-child {
            border-bottom: none;
        }

        .system-list li::before {
            content: '•';
            color: var(--primary);
            margin-right: 8px;
        }

        /* 파일 전환 드랍업 메뉴 */
        .file-switcher {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }

        .file-switcher-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary, #4f46e5), var(--primary-dark, #3730a3));
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .file-switcher-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }

        .file-switcher-btn.active {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .file-switcher-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .file-switcher-menu.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .file-switcher-menu-header {
            padding: 12px 16px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .file-switcher-menu-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #4b5563;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
        }

        .file-switcher-menu-item:hover {
            background: #f3f4f6;
        }

        .file-switcher-menu-item.current {
            background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(79,70,229,0.05));
            color: var(--primary, #4f46e5);
            font-weight: 600;
        }

        .file-switcher-menu-item .num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .file-switcher-menu-item.current .num {
            background: var(--primary, #4f46e5);
            color: white;
        }

        .file-switcher-menu-item:last-child {
            border-radius: 0 0 12px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📐 유형학습 진단</h1>
            <p>유형별 문제 학습 패턴을 분석해볼게요</p>
        </div>

        <!-- 탭 네비게이션 -->
        <div class="tab-nav">
            <button class="tab-btn <?php echo $activeTab === 'student' ? 'active' : ''; ?>" onclick="switchTab('student')">
                <span class="icon">💬</span>
                <span>학생 대화</span>
            </button>
            <button class="tab-btn <?php echo $activeTab === 'teacher' ? 'active' : ''; ?>" onclick="switchTab('teacher')">
                <span class="icon">👨‍🏫</span>
                <span>선생님 입력</span>
            </button>
            <button class="tab-btn <?php echo $activeTab === 'system' ? 'active' : ''; ?>" onclick="switchTab('system')">
                <span class="icon">📊</span>
                <span>시스템 데이터</span>
            </button>
        </div>

        <!-- 학생 대화 탭 -->
        <div class="tab-content <?php echo $activeTab === 'student' ? 'active' : ''; ?>" id="studentTab">
            <div class="progress-bar">
                <div class="progress-fill" id="studentProgressFill"></div>
            </div>
            <div class="progress-text" id="studentProgressText">0 / <?php echo count(TypeLearningQuestions::$studentQuestions); ?> 완료</div>

            <div class="questions-container" id="studentQuestionsContainer">
                <?php foreach (TypeLearningQuestions::$studentQuestions as $index => $q): ?>
                <div class="question-card <?php echo $index === 0 ? 'active' : 'locked'; ?>"
                     data-index="<?php echo $index; ?>"
                     data-id="<?php echo $q['id']; ?>"
                     data-type="<?php echo $q['type']; ?>"
                     data-tab="student">

                    <button class="question-button" onclick="toggleQuestion(<?php echo $index; ?>, 'student')">
                        <span class="question-icon"><?php echo $q['icon']; ?></span>
                        <div class="question-preview">
                            <div class="category"><?php echo htmlspecialchars($q['category']); ?></div>
                            <div class="title"><?php echo htmlspecialchars($q['preview']); ?></div>
                        </div>
                        <span class="question-status"></span>
                    </button>

                    <div class="question-content">
                        <div class="typing-area">
                            <span class="typing-text" data-message="<?php echo htmlspecialchars($q['message']); ?>"></span>
                            <span class="typing-cursor"></span>
                        </div>

                        <div class="answer-options" data-type="<?php echo $q['type']; ?>">
                            <?php if ($q['type'] === 'buttons'): ?>
                                <?php foreach ($q['options'] as $value => $label): ?>
                                <button class="answer-btn" data-value="<?php echo htmlspecialchars($value); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </button>
                                <?php endforeach; ?>

                            <?php elseif ($q['type'] === 'scale'): ?>
                                <div class="scale-options">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <button class="scale-btn" data-value="<?php echo $value; ?>">
                                        <span class="value"><?php echo $value; ?></span>
                                        <span><?php echo htmlspecialchars($label); ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($q['type'] === 'multiselect'): ?>
                                <div class="multiselect-options">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <button class="multi-btn" data-value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                <button class="multi-submit">선택 완료</button>

                            <?php elseif ($q['type'] === 'text' || $q['type'] === 'textarea'): ?>
                                <div class="text-input-area">
                                    <textarea class="text-input"
                                              placeholder="<?php echo htmlspecialchars($q['placeholder'] ?? '입력해주세요...'); ?>"></textarea>
                                    <button class="text-submit">입력 완료</button>
                                </div>

                            <?php elseif ($q['type'] === 'select'): ?>
                                <select class="select-input">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="select-submit">선택 완료</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="completion-screen" id="studentCompletionScreen">
                <div class="completion-icon">🎉</div>
                <h2>유형학습 대화 완료!</h2>
                <p>답변이 저장되었습니다. 감사합니다!</p>
                <button class="restart-btn" onclick="restartChat('student')">처음부터 다시</button>
            </div>
        </div>

        <!-- 선생님 입력 탭 -->
        <div class="tab-content <?php echo $activeTab === 'teacher' ? 'active' : ''; ?>" id="teacherTab">
            <?php if ($role !== 'student'): ?>
            <div class="progress-bar">
                <div class="progress-fill" id="teacherProgressFill"></div>
            </div>
            <div class="progress-text" id="teacherProgressText">0 / <?php echo count(TypeLearningQuestions::$teacherQuestions); ?> 완료</div>

            <div class="questions-container" id="teacherQuestionsContainer">
                <?php foreach (TypeLearningQuestions::$teacherQuestions as $index => $q): ?>
                <div class="question-card <?php echo $index === 0 ? 'active' : 'locked'; ?>"
                     data-index="<?php echo $index; ?>"
                     data-id="<?php echo $q['id']; ?>"
                     data-type="<?php echo $q['type']; ?>"
                     data-tab="teacher">

                    <button class="question-button" onclick="toggleQuestion(<?php echo $index; ?>, 'teacher')">
                        <span class="question-icon"><?php echo $q['icon']; ?></span>
                        <div class="question-preview">
                            <div class="category"><?php echo htmlspecialchars($q['category']); ?></div>
                            <div class="title"><?php echo htmlspecialchars($q['preview']); ?></div>
                        </div>
                        <span class="question-status"></span>
                    </button>

                    <div class="question-content">
                        <div class="typing-area">
                            <span class="typing-text" data-message="<?php echo htmlspecialchars($q['message']); ?>"></span>
                            <span class="typing-cursor"></span>
                        </div>

                        <div class="answer-options" data-type="<?php echo $q['type']; ?>">
                            <?php if ($q['type'] === 'buttons'): ?>
                                <?php foreach ($q['options'] as $value => $label): ?>
                                <button class="answer-btn" data-value="<?php echo htmlspecialchars($value); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </button>
                                <?php endforeach; ?>

                            <?php elseif ($q['type'] === 'scale'): ?>
                                <div class="scale-options">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <button class="scale-btn" data-value="<?php echo $value; ?>">
                                        <span class="value"><?php echo $value; ?></span>
                                        <span><?php echo htmlspecialchars($label); ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($q['type'] === 'textarea'): ?>
                                <div class="text-input-area">
                                    <textarea class="text-input"
                                              placeholder="<?php echo htmlspecialchars($q['placeholder'] ?? '입력해주세요...'); ?>"></textarea>
                                    <button class="text-submit">입력 완료</button>
                                </div>

                            <?php elseif ($q['type'] === 'select'): ?>
                                <select class="select-input">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="select-submit">선택 완료</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="completion-screen" id="teacherCompletionScreen">
                <div class="completion-icon">✅</div>
                <h2>선생님 입력 완료!</h2>
                <p>판단 내용이 저장되었습니다.</p>
                <button class="restart-btn" onclick="restartChat('teacher')">처음부터 다시</button>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 3rem; margin-bottom: 20px;">🔒</div>
                <h2 style="color: var(--text-muted);">선생님 전용</h2>
                <p style="color: var(--text-muted);">이 탭은 선생님만 접근할 수 있습니다.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- 시스템 데이터 탭 -->
        <div class="tab-content <?php echo $activeTab === 'system' ? 'active' : ''; ?>" id="systemTab">
            <!-- AI 탐지 결과 -->
            <div class="system-section">
                <div class="system-section-title">🤖 AI 탐지 결과</div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🎯</span>
                        <span class="system-card-label">탐지된 패턴</span>
                    </div>
                    <div class="system-card-value warning">
                        <?php echo htmlspecialchars($systemData['ai_detected_pattern']); ?>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">📊</span>
                        <span class="system-card-label">AI 신뢰도</span>
                    </div>
                    <div class="system-card-value"><?php echo $systemData['ai_confidence']; ?>%</div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['ai_confidence'] >= 80 ? 'high' : ($systemData['ai_confidence'] >= 60 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['ai_confidence']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🕐</span>
                        <span class="system-card-label">탐지 시각</span>
                    </div>
                    <div class="system-card-value" style="font-size: 1rem;">
                        <?php echo date('Y-m-d H:i', $systemData['detection_timestamp']); ?>
                    </div>
                </div>
            </div>

            <!-- 유형학습 분석 -->
            <div class="system-section">
                <div class="system-section-title">📐 유형학습 분석 데이터</div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">📈</span>
                        <span class="system-card-label">유형 완료율</span>
                    </div>
                    <div class="system-card-value <?php echo $systemData['type_completion_rate'] >= 70 ? 'success' : ($systemData['type_completion_rate'] >= 50 ? '' : 'danger'); ?>">
                        <?php echo $systemData['type_completion_rate']; ?>%
                    </div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['type_completion_rate'] >= 70 ? 'high' : ($systemData['type_completion_rate'] >= 50 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['type_completion_rate']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🎯</span>
                        <span class="system-card-label">주간 목표 달성률</span>
                    </div>
                    <div class="system-card-value <?php echo $systemData['weekly_goal_achievement'] >= 70 ? 'success' : ($systemData['weekly_goal_achievement'] >= 50 ? '' : 'danger'); ?>">
                        <?php echo $systemData['weekly_goal_achievement']; ?>%
                    </div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['weekly_goal_achievement'] >= 70 ? 'high' : ($systemData['weekly_goal_achievement'] >= 50 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['weekly_goal_achievement']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🔧</span>
                        <span class="system-card-label">방법 다양성 점수</span>
                    </div>
                    <div class="system-card-value"><?php echo $systemData['method_variety_score']; ?> <span style="font-size: 0.8rem; color: var(--text-muted);">/ 10</span></div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">💡</span>
                        <span class="system-card-label">힌트 의존도</span>
                    </div>
                    <div class="system-card-value <?php echo $systemData['hint_dependency_rate'] <= 30 ? 'success' : ($systemData['hint_dependency_rate'] <= 60 ? '' : 'danger'); ?>">
                        <?php echo $systemData['hint_dependency_rate']; ?>%
                    </div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['hint_dependency_rate'] <= 30 ? 'high' : ($systemData['hint_dependency_rate'] <= 60 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['hint_dependency_rate']; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 패턴 모델링 -->
            <div class="system-section">
                <div class="system-section-title">🔮 패턴 모델링 결과</div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">📉</span>
                        <span class="system-card-label">패턴 점수 변화 추이</span>
                    </div>
                    <div class="mini-chart">
                        <?php foreach ($systemData['pattern_history'] as $point): ?>
                        <div class="mini-chart-bar"
                             style="height: <?php echo $point['score']; ?>%"
                             data-label="<?php echo $point['date']; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">⚠️</span>
                        <span class="system-card-label">위험 요소</span>
                    </div>
                    <ul class="system-list">
                        <?php foreach ($systemData['risk_factors'] as $factor): ?>
                        <li><?php echo htmlspecialchars($factor); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🎯</span>
                        <span class="system-card-label">개선 필요 영역</span>
                    </div>
                    <ul class="system-list">
                        <?php foreach ($systemData['improvement_areas'] as $area): ?>
                        <li><?php echo htmlspecialchars($area); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">💡</span>
                        <span class="system-card-label">추천 개입 방법</span>
                    </div>
                    <ul class="system-list">
                        <?php foreach ($systemData['recommended_interventions'] as $intervention): ?>
                        <li><?php echo htmlspecialchars($intervention); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 상태 관리
        const state = {
            student: {
                currentIndex: 0,
                answers: {},
                totalQuestions: <?php echo count(TypeLearningQuestions::$studentQuestions); ?>
            },
            teacher: {
                currentIndex: 0,
                answers: {},
                totalQuestions: <?php echo count(TypeLearningQuestions::$teacherQuestions); ?>
            },
            studentId: <?php echo $studentId; ?>,
            activeTab: '<?php echo $activeTab; ?>'
        };

        // 탭 전환
        function switchTab(tabName) {
            // URL 업데이트
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);

            // 탭 버튼 업데이트
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.tab-btn').classList.add('active');

            // 컨텐츠 전환
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tabName + 'Tab').classList.add('active');

            state.activeTab = tabName;

            // 첫 질문 타이핑 시작
            if (tabName !== 'system') {
                const container = document.getElementById(tabName + 'QuestionsContainer');
                if (container) {
                    const firstCard = container.querySelector('.question-card.active');
                    if (firstCard && !firstCard.classList.contains('completed')) {
                        startTyping(firstCard);
                    }
                }
            }
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            // 활성 탭의 첫 질문 타이핑 시작
            const activeTab = state.activeTab;
            if (activeTab !== 'system') {
                const container = document.getElementById(activeTab + 'QuestionsContainer');
                if (container) {
                    const firstCard = container.querySelector('.question-card.active');
                    if (firstCard) {
                        setTimeout(() => startTyping(firstCard), 500);
                    }
                }
            }
            initAnswerHandlers();
        });

        // 질문 토글
        function toggleQuestion(index, tab) {
            const container = document.getElementById(tab + 'QuestionsContainer');
            const card = container.querySelector(`[data-index="${index}"]`);

            if (card.classList.contains('locked') || card.classList.contains('completed')) {
                return;
            }

            // 다른 질문 닫기
            container.querySelectorAll('.question-card.active').forEach(c => {
                if (c !== card) {
                    c.classList.remove('active');
                }
            });

            // 현재 질문 열기
            if (!card.classList.contains('active')) {
                card.classList.add('active');
                startTyping(card);
            }
        }

        // 타이핑 효과
        function startTyping(card) {
            const typingText = card.querySelector('.typing-text');
            const answerOptions = card.querySelector('.answer-options');
            const cursor = card.querySelector('.typing-cursor');
            const message = typingText.dataset.message;

            // 리셋
            typingText.textContent = '';
            answerOptions.classList.remove('visible');
            cursor.style.display = 'inline-block';

            let i = 0;
            const speed = 30;

            function type() {
                if (i < message.length) {
                    typingText.textContent += message.charAt(i);
                    i++;
                    setTimeout(type, speed);
                } else {
                    cursor.style.display = 'none';
                    setTimeout(() => {
                        answerOptions.classList.add('visible');
                    }, 300);
                }
            }

            type();
        }

        // 답변 핸들러 초기화
        function initAnswerHandlers() {
            // 단일 선택 버튼
            document.querySelectorAll('.answer-btn, .scale-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const options = this.closest('.answer-options');
                    const tab = card.dataset.tab;

                    options.querySelectorAll('.answer-btn, .scale-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');

                    const questionId = card.dataset.id;
                    const value = this.dataset.value;
                    saveAnswer(tab, questionId, value);

                    setTimeout(() => completeQuestion(card, tab), 400);
                });
            });

            // 멀티셀렉트
            document.querySelectorAll('.multi-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.classList.toggle('selected');
                });
            });

            document.querySelectorAll('.multi-submit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const tab = card.dataset.tab;
                    const selected = Array.from(card.querySelectorAll('.multi-btn.selected'))
                        .map(b => b.dataset.value);

                    if (selected.length > 0) {
                        saveAnswer(tab, card.dataset.id, selected);
                        completeQuestion(card, tab);
                    }
                });
            });

            // 텍스트 입력
            document.querySelectorAll('.text-submit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const tab = card.dataset.tab;
                    const input = card.querySelector('.text-input');
                    const value = input.value.trim();

                    if (value) {
                        saveAnswer(tab, card.dataset.id, value);
                        completeQuestion(card, tab);
                    }
                });
            });

            // 셀렉트
            document.querySelectorAll('.select-submit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const tab = card.dataset.tab;
                    const select = card.querySelector('.select-input');
                    const value = select.value;

                    if (value) {
                        saveAnswer(tab, card.dataset.id, value);
                        completeQuestion(card, tab);
                    }
                });
            });
        }

        // 답변 저장
        function saveAnswer(tab, questionId, value) {
            state[tab].answers[questionId] = value;
        }

        // 질문 완료 처리
        function completeQuestion(card, tab) {
            const index = parseInt(card.dataset.index);
            const container = document.getElementById(tab + 'QuestionsContainer');

            card.classList.remove('active');
            card.classList.add('completed');

            const completedCount = container.querySelectorAll('.question-card.completed').length;
            updateProgress(tab, completedCount);

            const nextIndex = index + 1;
            const nextCard = container.querySelector(`[data-index="${nextIndex}"]`);

            if (nextCard) {
                nextCard.classList.remove('locked');
                setTimeout(() => {
                    nextCard.classList.add('active');
                    startTyping(nextCard);
                    nextCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            } else {
                saveAllAnswers(tab);
            }
        }

        // 진행률 업데이트
        function updateProgress(tab, completed) {
            const total = state[tab].totalQuestions;
            const percent = (completed / total) * 100;
            document.getElementById(tab + 'ProgressFill').style.width = percent + '%';
            document.getElementById(tab + 'ProgressText').textContent = `${completed} / ${total} 완료`;
        }

        // 전체 저장
        function saveAllAnswers(tab) {
            const dataType = tab === 'teacher' ? 'teacher_input' : 'student_chat';

            fetch('?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: dataType,
                    student_id: state.studentId,
                    answers: state[tab].answers
                })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById(tab + 'QuestionsContainer').style.display = 'none';
                document.getElementById(tab + 'CompletionScreen').classList.add('show');
            })
            .catch(err => {
                console.error('저장 오류:', err);
                alert('저장 중 오류가 발생했습니다.');
            });
        }

        // 다시 시작
        function restartChat(tab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.location.href = url.toString();
        }
    </script>

    <!-- 파일 전환 드랍업 메뉴 -->
    <div class="file-switcher">
        <div class="file-switcher-menu" id="fileSwitcherMenu">
            <div class="file-switcher-menu-header">📂 Chat Files</div>
            <?php
            $currentFile = basename(__FILE__, '.php');
            $currentTabValue = $activeTab;
            for ($i = 1; $i <= 7; $i++):
                $fileNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                $fileName = "chat{$fileNum}";
                $isCurrent = ($fileName === $currentFile);
            ?>
            <a href="<?php echo $fileName; ?>.php?tab=<?php echo $currentTabValue; ?>"
               class="file-switcher-menu-item <?php echo $isCurrent ? 'current' : ''; ?>">
                <span class="num"><?php echo $i; ?></span>
                <span class="label">Chat <?php echo $fileNum; ?></span>
            </a>
            <?php endfor; ?>
        </div>
        <button class="file-switcher-btn" id="fileSwitcherBtn" onclick="toggleFileSwitcher()" title="파일 전환">
            <span id="fileSwitcherIcon">📁</span>
        </button>
    </div>

    <script>
    function toggleFileSwitcher() {
        const menu = document.getElementById('fileSwitcherMenu');
        const btn = document.getElementById('fileSwitcherBtn');
        const icon = document.getElementById('fileSwitcherIcon');

        menu.classList.toggle('open');
        btn.classList.toggle('active');
        icon.textContent = menu.classList.contains('open') ? '✕' : '📁';
    }

    document.addEventListener('click', function(e) {
        const switcher = document.querySelector('.file-switcher');
        if (!switcher.contains(e.target)) {
            const menu = document.getElementById('fileSwitcherMenu');
            const btn = document.getElementById('fileSwitcherBtn');
            const icon = document.getElementById('fileSwitcherIcon');
            menu.classList.remove('open');
            btn.classList.remove('active');
            icon.textContent = '📁';
        }
    });
    </script>
</body>
</html>
<?php
/**
 * =============================================================================
 * 관련 DB 테이블 정보
 * =============================================================================
 *
 * 테이블명: mdl_agent04_chat_data
 *
 * | 필드명      | 데이터 타입      | 설명                                    |
 * |------------|-----------------|----------------------------------------|
 * | id         | BIGINT(10)      | Primary Key, Auto Increment            |
 * | userid     | BIGINT(10)      | 학생 ID (FK: mdl_user.id)               |
 * | data_type  | VARCHAR(50)     | 'student_chat_type_learning', 'teacher_input_type_learning' |
 * | data_json  | LONGTEXT        | JSON 형태의 데이터                        |
 * | created_at | BIGINT(10)      | 생성 시간 (Unix timestamp)               |
 * | created_by | BIGINT(10)      | 생성자 ID (FK: mdl_user.id)             |
 *
 * =============================================================================
 */
?>
