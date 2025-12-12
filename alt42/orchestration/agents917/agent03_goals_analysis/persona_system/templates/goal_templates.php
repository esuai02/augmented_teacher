<?php
/**
 * Agent03 Goals Analysis - 응답 템플릿 정의
 *
 * 목표 분석 관련 상황별 응답 템플릿
 * 각 상황(G0, G1, G2, G3, CRISIS)에 맞는 템플릿 제공
 *
 * @package AugmentedTeacher\Agent03\PersonaSystem
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

/**
 * Agent03 응답 템플릿 클래스
 */
class Agent03ResponseTemplates {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    private $currentFile = __FILE__;

    /** @var array 템플릿 저장소 */
    private $templates = [];

    /**
     * 생성자 - 모든 템플릿 초기화
     */
    public function __construct() {
        $this->initializeG0Templates();  // 목표 설정
        $this->initializeG1Templates();  // 목표 진행
        $this->initializeG2Templates();  // 정체/위기
        $this->initializeG3Templates();  // 목표 재설정
        $this->initializeCrisisTemplates(); // 위기 개입
        $this->initializeCommonTemplates(); // 공통 템플릿
    }

    /**
     * G0: 목표 설정 단계 템플릿
     */
    private function initializeG0Templates(): void {
        $this->templates['G0'] = [
            // === G0_P1: 야심찬 과목표 설정자 ===
            'G0_P1_initial' => [
                'tone' => 'Gentle',
                'intervention' => 'GapAnalysis',
                'templates' => [
                    '{{user_name}}{{honorific}}, 높은 목표를 세우시는 열정이 느껴져요! 다만, 큰 목표를 작은 단계로 나누면 더 달성하기 쉬워요. 먼저 이번 주에 할 수 있는 작은 목표부터 시작해볼까요?',
                    '멋진 목표네요! {{user_name}}{{honorific}}, 이 목표를 달성하기 위한 중간 마일스톤을 함께 세워볼까요? 한 번에 다 이루려고 하면 지칠 수 있어요.',
                    '{{user_name}}{{honorific}}의 의지가 대단해요! 이 목표들 중에서 가장 먼저 집중하고 싶은 것은 무엇인가요?'
                ]
            ],

            // === G0_P2: 목표 회피형 ===
            'G0_P2_initial' => [
                'tone' => 'Warm',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 목표를 세우는 게 부담스러우신가요? 괜찮아요. 아주 작은 것부터 시작해도 돼요. 오늘 하루 동안 하고 싶은 작은 일이 있나요?',
                    '목표라고 하면 거창하게 느껴질 수 있어요. {{user_name}}{{honorific}}, "이번 주에 한 가지만 해본다면?" 이라고 생각해보면 어떨까요?',
                    '{{user_name}}{{honorific}}, 목표를 세우지 않아도 괜찮아요. 대신, 요즘 관심 있는 것이나 해보고 싶은 것에 대해 이야기해 볼까요?'
                ]
            ],

            // === G0_P3: 모호한 목표 설정자 ===
            'G0_P3_initial' => [
                'tone' => 'Professional',
                'intervention' => 'GoalSetting',
                'templates' => [
                    '{{user_name}}{{honorific}}, 좋은 시작이에요! "{{goal_text}}"를 좀 더 구체적으로 만들어볼까요? 예를 들어, 언제까지, 어느 정도를 목표로 하시나요?',
                    '{{user_name}}{{honorific}}, 목표를 SMART하게 만들어봐요. 구체적으로(S), 측정 가능하게(M), 달성 가능하게(A), 관련성 있게(R), 기한이 있게(T). 어떤 부분을 더 명확히 해볼까요?',
                    '"잘하고 싶다"는 마음이 느껴져요! {{user_name}}{{honorific}}, "잘한다"가 어떤 모습인지 구체적으로 상상해볼까요?'
                ]
            ],

            // === G0_P4: 의존적 목표 설정자 ===
            'G0_P4_initial' => [
                'tone' => 'Encouraging',
                'intervention' => 'SkillBuilding',
                'templates' => [
                    '{{user_name}}{{honorific}}, 다른 사람의 의견도 중요하지만, 본인이 정말 원하는 것이 무엇인지도 생각해보면 좋겠어요. {{user_name}}{{honorific}}만의 목표는 무엇인가요?',
                    '{{user_name}}{{honorific}}, 이 목표가 다른 사람 때문이 아니라 {{user_name}}{{honorific}} 자신을 위한 것인지 한번 생각해볼까요? 본인이 진짜 원하는 것이 뭔지 알면 동기부여가 더 잘 돼요.',
                    '좋아요! 그런데 {{user_name}}{{honorific}}, 만약 아무도 신경 쓰지 않는다면 어떤 목표를 세우고 싶으세요?'
                ]
            ],

            // === G0_P5: 균형 잡힌 목표 설정자 ===
            'G0_P5_initial' => [
                'tone' => 'Professional',
                'intervention' => 'GoalSetting',
                'templates' => [
                    '{{user_name}}{{honorific}}, 좋은 목표를 세우셨네요! 이 목표를 더욱 효과적으로 달성하기 위한 세부 계획을 함께 세워볼까요?',
                    '현실적이고 균형 잡힌 목표네요, {{user_name}}{{honorific}}! 첫 번째 마일스톤으로 삼을 수 있는 작은 목표는 무엇일까요?',
                    '{{user_name}}{{honorific}}, 멋진 목표예요! 이 목표를 달성했을 때 어떤 기분일지 상상해보셨나요? 그 모습을 그려보면 동기부여가 더 잘 돼요.'
                ]
            ],

            // === G0_P6: 두려움 기반 회피자 ===
            'G0_P6_initial' => [
                'tone' => 'Empathetic',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 실패가 두려우신 마음 이해해요. 하지만 실패도 배움의 일부예요. 작은 시도부터 해보는 건 어떨까요?',
                    '{{user_name}}{{honorific}}, "못할 것 같아서"라는 생각이 드시나요? 그 마음 충분히 공감해요. 우리 함께 "할 수 있는" 작은 것부터 찾아볼까요?',
                    '두려운 마음이 드는 건 자연스러워요, {{user_name}}{{honorific}}. 목표를 향해 가는 과정 자체가 성장이에요. 첫 발걸음만 내디뎌 봐요.'
                ]
            ],

            // === 공통 G0 템플릿 ===
            'G0_welcome' => '{{user_name}}{{honorific}}, 새로운 목표를 세워볼까요? 어떤 분야에서 성장하고 싶으세요?',
            'G0_category_ask' => '{{user_name}}{{honorific}}, 이 목표는 어떤 카테고리에 해당하나요? (학업/기술/건강/성장/기타)',
            'G0_deadline_ask' => '이 목표를 언제까지 달성하고 싶으세요, {{user_name}}{{honorific}}?',
            'G0_confirm' => '{{user_name}}{{honorific}}, 목표가 설정되었어요! "{{goal_title}}" - {{goal_deadline}}까지 함께 달성해봐요!',
            'G0_smart_guide' => "좋은 목표는 SMART 기준을 따르면 좋아요:\n• Specific(구체적)\n• Measurable(측정 가능)\n• Achievable(달성 가능)\n• Relevant(관련성 있는)\n• Time-bound(기한이 있는)"
        ];
    }

    /**
     * G1: 목표 진행 단계 템플릿
     */
    private function initializeG1Templates(): void {
        $this->templates['G1'] = [
            // === G1_P1: 꾸준한 진행자 ===
            'G1_P1_progress' => [
                'tone' => 'Encouraging',
                'intervention' => 'InformationProvision',
                'templates' => [
                    '{{user_name}}{{honorific}}, 꾸준히 잘 하고 계시네요! 현재 {{progress_rate}}% 달성했어요. 이 페이스를 유지하면 목표 달성이 눈앞이에요!',
                    '대단해요, {{user_name}}{{honorific}}! 일관된 노력이 결실을 맺고 있어요. 지금까지의 진행 상황을 자랑스러워하세요!',
                    '{{user_name}}{{honorific}}, {{progress_rate}}% 달성! 이 기세라면 목표 달성까지 순조로워요. 오늘도 한 걸음 더 나아가볼까요?'
                ]
            ],

            // === G1_P2: 급진적 진행자 ===
            'G1_P2_progress' => [
                'tone' => 'Calm',
                'intervention' => 'PlanDesign',
                'templates' => [
                    '{{user_name}}{{honorific}}, 빠른 진행 속도가 인상적이에요! 다만, 지속 가능한 페이스인지 한번 점검해볼까요? 번아웃을 피하는 것도 중요해요.',
                    '열정이 대단해요, {{user_name}}{{honorific}}! 하지만 마라톤처럼 긴 호흡으로 가는 것도 좋아요. 오늘은 잠시 쉬어가는 건 어떨까요?',
                    '{{user_name}}{{honorific}}, 빠른 진행에 박수를 보내요! 중간중간 충전 시간도 계획에 넣어두면 더 오래 달릴 수 있어요.'
                ]
            ],

            // === G1_P3: 불규칙 진행자 ===
            'G1_P3_progress' => [
                'tone' => 'Warm',
                'intervention' => 'BehaviorModification',
                'templates' => [
                    '{{user_name}}{{honorific}}, 진행이 들쭉날쭉한 것 같아요. 작은 루틴을 만들어보면 어떨까요? 예를 들어, 매일 10분씩만이라도요.',
                    '{{user_name}}{{honorific}}, 꾸준함이 조금 부족한 것 같아요. 특별한 이유가 있나요? 함께 해결책을 찾아볼까요?',
                    '목표를 향해 가는 여정이 고르지 않을 수 있어요, {{user_name}}{{honorific}}. 어떤 상황에서 더 잘 되고, 어떤 상황에서 어려운지 파악해볼까요?'
                ]
            ],

            // === G1_P4: 외부 장애 경험자 ===
            'G1_P4_progress' => [
                'tone' => 'Empathetic',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 외부 상황 때문에 힘드셨군요. 그건 {{user_name}}{{honorific}} 잘못이 아니에요. 상황이 나아지면 다시 시작할 수 있어요.',
                    '예상치 못한 일들이 생기죠, {{user_name}}{{honorific}}. 목표를 조금 조정하거나 기한을 늘려보는 건 어떨까요?',
                    '{{user_name}}{{honorific}}, 외부 요인은 통제할 수 없는 것들이 많아요. 지금 할 수 있는 것에 집중해볼까요?'
                ]
            ],

            // === G1_P5: 동기 저하 경험자 ===
            'G1_P5_progress' => [
                'tone' => 'Encouraging',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 동기가 좀 떨어진 것 같아요. 이 목표를 처음 세웠을 때 어떤 마음이었는지 떠올려볼까요?',
                    '지치셨죠, {{user_name}}{{honorific}}? 잠시 쉬어가도 괜찮아요. 다시 시작할 준비가 되면 알려주세요.',
                    '{{user_name}}{{honorific}}, 목표를 향한 여정에서 지치는 건 자연스러워요. 작은 보상을 계획해보는 건 어떨까요?'
                ]
            ],

            // === 공통 G1 템플릿 ===
            'G1_status' => '{{user_name}}{{honorific}}, 현재 {{active_goal_count}}개의 목표가 진행 중이에요. 전체 달성률은 {{progress_rate}}%입니다.',
            'G1_milestone' => '{{user_name}}{{honorific}}, "{{goal_title}}" 목표의 {{milestone}}% 지점을 통과했어요! 축하해요!',
            'G1_reminder' => '{{user_name}}{{honorific}}, "{{goal_title}}" 목표의 마감일이 {{days_remaining}}일 남았어요.',
            'G1_encouragement' => '{{user_name}}{{honorific}}, 조금씩이라도 전진하고 있어요. 그 자체로 대단한 거예요!',
            'G1_check_in' => '{{user_name}}{{honorific}}, 목표 진행은 어떻게 되고 있나요? 어려운 점이 있으면 말씀해주세요.'
        ];
    }

    /**
     * G2: 정체/위기 단계 템플릿
     */
    private function initializeG2Templates(): void {
        $this->templates['G2'] = [
            // === G2_P1: 일시적 좌절자 ===
            'G2_P1_stagnation' => [
                'tone' => 'Empathetic',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 잠시 멈춰 있어도 괜찮아요. 이것도 여정의 일부예요. 무엇이 막혀 있는지 함께 살펴볼까요?',
                    '진행이 멈춘 것 같아 답답하시죠, {{user_name}}{{honorific}}? 지금은 재충전의 시간이라고 생각해봐요.',
                    '{{user_name}}{{honorific}}, 잠시 쉬어가는 것도 전략이에요. 다시 시작할 준비가 되면 함께 해요.'
                ]
            ],

            // === G2_P2: 만성적 정체자 ===
            'G2_P2_stagnation' => [
                'tone' => 'Gentle',
                'intervention' => 'GapAnalysis',
                'templates' => [
                    '{{user_name}}{{honorific}}, 오래 멈춰 있었네요. 목표가 너무 어렵거나 맞지 않는 건 아닐까요? 함께 점검해볼까요?',
                    '{{user_name}}{{honorific}}, 목표와 현재 상황 사이에 간극이 있는 것 같아요. 더 현실적인 목표로 조정해볼까요?',
                    '진행이 어려우신 이유가 있을 거예요, {{user_name}}{{honorific}}. 무엇이 가장 큰 장애물인가요?'
                ]
            ],

            // === G2_P3: 포기 선언자 ===
            'G2_P3_stagnation' => [
                'tone' => 'Empathetic',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 포기하고 싶은 마음 이해해요. 하지만 잠깐만요, 이 목표를 처음 세웠을 때를 기억해보세요. 왜 이걸 원했나요?',
                    '힘드셨죠, {{user_name}}{{honorific}}. 포기해도 괜찮지만, 목표를 조정하는 것은 어떨까요? 완전히 놓기 전에 다른 방법을 찾아봐요.',
                    '{{user_name}}{{honorific}}, 지금 느끼는 좌절감은 일시적일 수 있어요. 목표를 더 작게 나누거나 기한을 늘려보는 건 어떨까요?'
                ]
            ],

            // === G2_P4: 번아웃 경험자 ===
            'G2_P4_stagnation' => [
                'tone' => 'Calm',
                'intervention' => 'SafetyNet',
                'templates' => [
                    '{{user_name}}{{honorific}}, 번아웃이 온 것 같아요. 지금은 쉬는 것이 최선이에요. 목표는 나중에 다시 시작해도 돼요.',
                    '너무 열심히 하셨네요, {{user_name}}{{honorific}}. 지금 가장 필요한 건 휴식이에요. 목표보다 {{user_name}}{{honorific}}의 건강이 더 중요해요.',
                    '{{user_name}}{{honorific}}, 지쳐 있다는 신호를 무시하지 마세요. 충분히 쉰 다음에 천천히 다시 시작해요.'
                ]
            ],

            // === 공통 G2 템플릿 ===
            'G2_check_in' => '{{user_name}}{{honorific}}, 목표 진행이 멈춰 있는 것 같아요. 무슨 일이 있었나요?',
            'G2_support' => '어려운 시간을 보내고 계시는군요, {{user_name}}{{honorific}}. 제가 도와드릴 수 있는 게 있을까요?',
            'G2_adjustment_offer' => '{{user_name}}{{honorific}}, 목표를 조정하거나 기한을 늘려보는 건 어떨까요? 그것도 현명한 선택이에요.',
            'G2_break_suggestion' => '{{user_name}}{{honorific}}, 잠시 목표에서 벗어나 쉬어가는 것도 방법이에요. 재충전 후 다시 시작해요.'
        ];
    }

    /**
     * G3: 목표 재설정 단계 템플릿
     */
    private function initializeG3Templates(): void {
        $this->templates['G3'] = [
            // === G3_P1: 성공적 달성자 ===
            'G3_P1_achievement' => [
                'tone' => 'Encouraging',
                'intervention' => 'GoalSetting',
                'templates' => [
                    '축하해요, {{user_name}}{{honorific}}! "{{goal_title}}" 목표를 달성하셨네요! 정말 대단해요. 새로운 도전을 시작해볼까요?',
                    '{{user_name}}{{honorific}}, 목표 달성을 진심으로 축하드려요! 이 성취감을 기억하세요. 다음 목표는 무엇인가요?',
                    '해내셨어요, {{user_name}}{{honorific}}! 이 경험을 바탕으로 더 높은 목표에 도전해볼까요?'
                ]
            ],

            // === G3_P2: 전략적 조정자 ===
            'G3_P2_adjustment' => [
                'tone' => 'Professional',
                'intervention' => 'PlanDesign',
                'templates' => [
                    '{{user_name}}{{honorific}}, 목표 조정은 현명한 결정이에요. 새로운 목표를 함께 설계해볼까요?',
                    '상황에 맞게 목표를 조정하는 건 포기가 아니라 전략이에요, {{user_name}}{{honorific}}. 어떤 방향으로 수정하고 싶으세요?',
                    '{{user_name}}{{honorific}}, 경험을 바탕으로 더 현실적인 목표를 세워봐요. 이전 목표에서 배운 것이 있나요?'
                ]
            ],

            // === 공통 G3 템플릿 ===
            'G3_completion_summary' => '{{user_name}}{{honorific}}, 지금까지 {{completed_count}}개의 목표를 달성하셨어요! 대단합니다.',
            'G3_new_goal_prompt' => '{{user_name}}{{honorific}}, 다음에 도전하고 싶은 목표가 있나요?',
            'G3_reflection' => '{{user_name}}{{honorific}}, 이전 목표를 통해 배운 점이 있다면 무엇인가요? 그 경험을 다음 목표에 활용해봐요.',
            'G3_level_up' => '{{user_name}}{{honorific}}, 이번엔 조금 더 도전적인 목표를 세워보는 건 어떨까요?',
            'G3_balance_check' => '{{user_name}}{{honorific}}, 새 목표를 세울 때 삶의 균형도 생각해보세요. 학업, 건강, 성장 모두 중요해요.'
        ];
    }

    /**
     * CRISIS: 위기 개입 템플릿
     */
    private function initializeCrisisTemplates(): void {
        $this->templates['CRISIS'] = [
            // === CRISIS_P1: 즉시 개입 필요 ===
            'CRISIS_P1_level_0' => [
                'tone' => 'Calm',
                'intervention' => 'CrisisIntervention',
                'templates' => [
                    "{{user_name}}{{honorific}}, 지금 많이 힘드시군요. 당신의 안전이 가장 중요해요. 혼자 감당하지 마시고 전문가의 도움을 받으세요.\n\n📞 자살예방상담전화: 1393 (24시간)\n📞 정신건강위기상담전화: 1577-0199\n\n언제든 이야기 나눌 준비가 되어 있어요."
                ]
            ],

            'CRISIS_P1_level_1' => [
                'tone' => 'Empathetic',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    "{{user_name}}{{honorific}}, 정말 힘든 시간을 보내고 계시는군요. 그 마음이 충분히 이해됩니다. 지금 느끼는 감정은 일시적일 수 있어요. 전문 상담을 받아보시는 건 어떨까요?\n\n📞 정신건강위기상담전화: 1577-0199\n\n제가 여기 있을게요. 천천히 이야기해 주세요."
                ]
            ],

            // === CRISIS_P2: 안정화 필요 ===
            'CRISIS_P2_level_2' => [
                'tone' => 'Warm',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 외롭고 힘든 마음이 느껴져요. 혼자라고 느껴질 때 정말 힘들죠. 하지만 당신 곁에는 도움을 줄 수 있는 사람들이 있어요. 지금 어떤 것이 가장 힘드신가요?',
                    '{{user_name}}{{honorific}}, 그런 마음이 드시는군요. 충분히 공감해요. 무엇이 가장 마음에 걸리시나요? 함께 이야기해봐요.'
                ]
            ],

            'CRISIS_P2_level_3' => [
                'tone' => 'Warm',
                'intervention' => 'EmotionalSupport',
                'templates' => [
                    '{{user_name}}{{honorific}}, 힘드시군요. 그런 감정을 느끼는 건 자연스러운 일이에요. 목표에 대한 부담이 있으시다면, 잠시 쉬어가도 괜찮아요.',
                    '지쳐 계시는군요, {{user_name}}{{honorific}}. 지금은 목표보다 {{user_name}}{{honorific}}의 마음을 돌보는 게 더 중요해요.'
                ]
            ],

            // === 공통 CRISIS 템플릿 ===
            'CRISIS_acknowledgment' => '{{user_name}}{{honorific}}, 지금 힘든 마음을 표현해주셔서 감사해요. 혼자가 아니에요.',
            'CRISIS_safety_check' => '{{user_name}}{{honorific}}, 지금 안전한 곳에 계신가요?',
            'CRISIS_resource' => "도움이 필요하시면 아래 연락처로 연락해주세요:\n📞 자살예방상담전화: 1393 (24시간)\n📞 정신건강위기상담전화: 1577-0199",
            'CRISIS_follow_up' => '{{user_name}}{{honorific}}, 괜찮으시면 내일 다시 이야기 나눠봐요. 언제든 여기 있을게요.'
        ];
    }

    /**
     * 공통 템플릿 초기화
     */
    private function initializeCommonTemplates(): void {
        $this->templates['COMMON'] = [
            // 인사
            'greeting' => '안녕하세요, {{user_name}}{{honorific}}! 오늘 목표 관련해서 도움이 필요하신가요?',
            'greeting_return' => '다시 오셨네요, {{user_name}}{{honorific}}! 목표 진행은 어떻게 되고 있나요?',

            // 확인
            'confirm_yes' => '알겠어요, {{user_name}}{{honorific}}! 진행할게요.',
            'confirm_no' => '{{user_name}}{{honorific}}, 다른 것이 필요하시면 말씀해주세요.',

            // 에러
            'error_generic' => '{{user_name}}{{honorific}}, 잠시 문제가 발생했어요. 다시 시도해주시겠어요?',
            'error_not_found' => '{{user_name}}{{honorific}}, 요청하신 정보를 찾을 수 없어요.',

            // 기타
            'unknown_intent' => '{{user_name}}{{honorific}}, 무엇을 도와드릴까요? 목표 설정, 진행 확인, 또는 조정이 필요하신가요?',
            'thank_you' => '도움이 되었다니 기뻐요, {{user_name}}{{honorific}}! 언제든 필요하시면 불러주세요.',
            'encouragement_generic' => '{{user_name}}{{honorific}}, 잘 하고 계세요! 조금씩 전진하는 것이 중요해요.'
        ];
    }

    /**
     * 템플릿 조회
     *
     * @param string $context 컨텍스트 코드 (G0, G1, G2, G3, CRISIS, COMMON)
     * @param string $templateKey 템플릿 키
     * @return array|string|null 템플릿 데이터
     */
    public function getTemplate(string $context, string $templateKey) {
        return $this->templates[$context][$templateKey] ?? null;
    }

    /**
     * 컨텍스트의 모든 템플릿 조회
     *
     * @param string $context 컨텍스트 코드
     * @return array 해당 컨텍스트의 모든 템플릿
     */
    public function getContextTemplates(string $context): array {
        return $this->templates[$context] ?? [];
    }

    /**
     * 모든 템플릿 조회
     *
     * @return array 전체 템플릿
     */
    public function getAllTemplates(): array {
        return $this->templates;
    }

    /**
     * 템플릿 렌더링
     *
     * @param string $template 템플릿 문자열
     * @param array $variables 변수 배열
     * @return string 렌더링된 문자열
     */
    public function renderTemplate(string $template, array $variables): string {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
        return $template;
    }

    /**
     * 페르소나별 템플릿 선택 및 렌더링
     *
     * @param string $personaId 페르소나 ID (예: G0_P1)
     * @param string $templateType 템플릿 타입 (예: initial, progress)
     * @param array $variables 변수 배열
     * @return array ['text' => string, 'tone' => string, 'intervention' => string]
     */
    public function getPersonaResponse(string $personaId, string $templateType, array $variables): array {
        // 컨텍스트 추출 (G0_P1 -> G0)
        $context = preg_replace('/_P\d+$/', '', $personaId);
        $templateKey = $personaId . '_' . $templateType;

        $templateData = $this->getTemplate($context, $templateKey);

        if (!$templateData || !isset($templateData['templates'])) {
            return [
                'text' => $this->renderTemplate(
                    $this->getTemplate('COMMON', 'unknown_intent') ?? '',
                    $variables
                ),
                'tone' => 'Professional',
                'intervention' => 'InformationProvision'
            ];
        }

        // 랜덤 템플릿 선택
        $templateText = $templateData['templates'][array_rand($templateData['templates'])];

        return [
            'text' => $this->renderTemplate($templateText, $variables),
            'tone' => $templateData['tone'],
            'intervention' => $templateData['intervention']
        ];
    }
}

/*
 * 사용 예시:
 *
 * $templates = new Agent03ResponseTemplates();
 *
 * // 페르소나별 응답 가져오기
 * $response = $templates->getPersonaResponse('G0_P1', 'initial', [
 *     'user_name' => '김철수',
 *     'honorific' => '님'
 * ]);
 * echo $response['text']; // 렌더링된 응답
 * echo $response['tone']; // Gentle
 * echo $response['intervention']; // GapAnalysis
 *
 * // 직접 템플릿 렌더링
 * $template = $templates->getTemplate('G1', 'G1_status');
 * $rendered = $templates->renderTemplate($template, [
 *     'user_name' => '김철수',
 *     'honorific' => '님',
 *     'active_goal_count' => 3,
 *     'progress_rate' => 65.5
 * ]);
 *
 * 관련 DB 테이블:
 * - at_response_templates: 응답 템플릿 (선택적 DB 저장용)
 *
 * 파일 위치:
 * /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/templates/goal_templates.php:420
 */
