<?php
/**
 * Agent19 Response Generator
 * 페르소나 기반 맞춤형 응답 생성 엔진
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Engine
 * @version     1.0.0
 * @author      System
 * @created     2025-12-02
 *
 * 관련 DB 테이블:
 * - mdl_agent19_response_templates: id(BIGINT), template_id(VARCHAR), situation(VARCHAR), persona_code(VARCHAR), tone(VARCHAR), template_content(TEXT), variables(TEXT/JSON), is_active(TINYINT)
 * - mdl_agent19_response_log: id(BIGINT), userid(BIGINT), template_used(VARCHAR), persona_applied(VARCHAR), context_id(VARCHAR), response_content(TEXT), user_feedback(INT), timecreated(BIGINT)
 */

defined('MOODLE_INTERNAL') || die();

class Agent19ResponseGenerator {

    /** @var object Moodle DB instance */
    private $db;

    /** @var int Current user ID */
    private $userid;

    /** @var array Response templates */
    private $templates;

    /** @var array Tone configurations by persona */
    private $toneConfig;

    /** @var string Template base path */
    private $templatePath;

    /**
     * Constructor
     *
     * @param int $userid User ID
     */
    public function __construct($userid) {
        global $DB;
        $this->db = $DB;
        $this->userid = $userid;
        $this->templatePath = dirname(__FILE__) . '/../templates';
        $this->loadToneConfig();
        $this->loadTemplates();
    }

    /**
     * Load tone configuration for each persona dimension
     */
    private function loadToneConfig() {
        // 인지적 차원별 톤
        $this->toneConfig['cognitive'] = [
            'C1' => ['tone' => 'challenging', 'pace' => 'fast', 'depth' => 'advanced'],
            'C2' => ['tone' => 'supportive', 'pace' => 'slow', 'depth' => 'simplified'],
            'C3' => ['tone' => 'explanatory', 'pace' => 'moderate', 'depth' => 'conceptual'],
            'C4' => ['tone' => 'guiding', 'pace' => 'adaptive', 'depth' => 'scaffolded'],
            'C5' => ['tone' => 'reinforcing', 'pace' => 'moderate', 'depth' => 'pattern_based'],
            'C6' => ['tone' => 'questioning', 'pace' => 'thoughtful', 'depth' => 'analytical']
        ];

        // 행동적 차원별 톤
        $this->toneConfig['behavioral'] = [
            'B1' => ['interaction' => 'rich', 'feedback' => 'immediate', 'structure' => 'flexible'],
            'B2' => ['interaction' => 'guided', 'feedback' => 'gentle', 'structure' => 'step_by_step'],
            'B3' => ['interaction' => 'varied', 'feedback' => 'quick', 'structure' => 'adaptive'],
            'B4' => ['interaction' => 'structured', 'feedback' => 'detailed', 'structure' => 'sequential'],
            'B5' => ['interaction' => 'continuous', 'feedback' => 'periodic', 'structure' => 'immersive'],
            'B6' => ['interaction' => 'brief', 'feedback' => 'chunked', 'structure' => 'micro']
        ];

        // 감정적 차원별 톤
        $this->toneConfig['emotional'] = [
            'E1' => ['warmth' => 'moderate', 'encouragement' => 'challenging', 'support' => 'confidence_boost'],
            'E2' => ['warmth' => 'high', 'encouragement' => 'reassuring', 'support' => 'anxiety_reduction'],
            'E3' => ['warmth' => 'moderate', 'encouragement' => 'novelty', 'support' => 'engagement_boost'],
            'E4' => ['warmth' => 'moderate', 'encouragement' => 'balanced', 'support' => 'persistence'],
            'E5' => ['warmth' => 'high', 'encouragement' => 'strong', 'support' => 'frustration_relief'],
            'E6' => ['warmth' => 'neutral', 'encouragement' => 'steady', 'support' => 'stability_maintain']
        ];
    }

    /**
     * Load response templates
     */
    private function loadTemplates() {
        $this->templates = [
            // S1: 이탈 감지 응답
            'S1_return' => [
                'E1' => "돌아온 것을 환영해요! 🎯 바로 이어서 도전해볼까요?",
                'E2' => "괜찮아요, 천천히 다시 시작해봐요. 어디서부터 할지 같이 정해볼까요?",
                'E3' => "다시 와줬네요! 이번엔 좀 더 재미있는 걸 해볼까요?",
                'E4' => "잘 돌아왔어요! 마지막 도전을 이어서 해볼까요?",
                'E5' => "쉬고 왔군요. 오늘은 가볍게 시작해볼까요?",
                'E6' => "다시 시작해볼 준비가 되셨나요?"
            ],

            // S2: 지연 응답
            'S2_hint' => [
                'C3' => "이 문제의 핵심 개념을 다시 한번 살펴볼까요? 💡",
                'C4' => "다른 방법으로 접근해볼까요? 힌트를 드릴게요.",
                'C6' => "어떤 부분이 막히는지 생각해보세요. 첫 단계부터 같이 해볼까요?"
            ],

            // S3: 휴식 권유
            'S3_break' => [
                'fatigue' => "잠깐 쉬어가는 건 어때요? 🌟 5분 후에 더 집중할 수 있을 거예요.",
                'achievement' => "대단해요! 목표를 달성했어요! 🎉 잠시 쉬면서 성취감을 느껴보세요.",
                'scheduled' => "예정된 휴식 시간이에요. 스트레칭 한번 하고 올까요?"
            ],

            // S4: 오류 패턴 응답
            'S4_concept' => [
                'gentle' => "이 부분이 헷갈리는 것 같아요. 기본 개념부터 다시 살펴볼까요?",
                'direct' => "같은 유형에서 실수가 반복되고 있어요. 핵심 개념을 복습해봐요.",
                'encouraging' => "실수는 배움의 기회예요! 어디서 막혔는지 같이 찾아볼까요?"
            ],
            'S4_careless' => [
                'gentle' => "살짝 서두른 것 같아요. 천천히 다시 읽어볼까요?",
                'direct' => "문제를 꼼꼼히 읽어보면 답을 찾을 수 있을 거예요.",
                'encouraging' => "거의 다 맞았어요! 조금만 더 집중해볼까요?"
            ],
            'S4_application' => [
                'gentle' => "기본은 잘 이해했어요! 응용하는 방법을 같이 연습해볼까요?",
                'direct' => "개념을 어떻게 적용하는지 단계별로 살펴봐요.",
                'encouraging' => "기초가 탄탄해요! 이제 한 단계 더 나아가볼까요?"
            ],

            // S5: 정서적 응답
            'S5_confident' => "훌륭해요! 🌟 더 어려운 문제에 도전해볼까요?",
            'S5_anxious' => "괜찮아요. 천천히 생각해보세요. 틀려도 전혀 문제없어요. 💪",
            'S5_frustrated' => "힘들죠? 괜찮아요. 잠시 쉬거나 더 쉬운 문제부터 해볼까요?",
            'S5_bored' => "조금 지루한가요? 새로운 도전을 해볼까요? 🎯",

            // S6: 활동 균형 권유
            'S6_balance' => [
                'activity' => "다양한 활동을 해보면 더 재미있을 거예요! 이번엔 %s을(를) 해볼까요?",
                'difficulty' => "%s 난이도 문제도 도전해볼까요? 실력이 쑥쑥 늘 거예요!",
                'type' => "학습과 연습을 균형있게 하면 더 효과적이에요. %s을(를) 해볼까요?"
            ],

            // S7: 루틴 응답
            'S7_start' => "좋은 시작이에요! 오늘도 화이팅! 💪",
            'S7_review' => "복습하는 습관이 정말 좋아요! 계속 이렇게 해봐요.",
            'S7_end' => "오늘도 수고했어요! 🎉 내일 또 만나요."
        ];
    }

    /**
     * Generate personalized response
     *
     * @param array $persona Detected persona (cognitive, behavioral, emotional)
     * @param array $context Current context
     * @param array $data Additional data
     * @return array Generated response
     */
    public function generateResponse($persona, $context, $data = []) {
        $situation = $context['situation']['situation'] ?? 'S5';
        $subContext = $context['situation']['context_id'] ?? 'S5_CTX_01';

        $cognitive = $persona['cognitive']['code'] ?? 'C1';
        $behavioral = $persona['behavioral']['code'] ?? 'B1';
        $emotional = $persona['emotional']['code'] ?? 'E6';

        // Get tone configuration
        $tone = $this->determineTone($cognitive, $behavioral, $emotional);

        // Select appropriate template
        $template = $this->selectTemplate($situation, $subContext, $emotional, $tone);

        // Apply personalization
        $response = $this->personalizeResponse($template, $data, $tone);

        // Log response
        $this->logResponse($template['id'] ?? 'dynamic', $persona['composite'] ?? "$cognitive-$behavioral-$emotional", $subContext, $response);

        return [
            'content' => $response,
            'tone' => $tone,
            'situation' => $situation,
            'persona_applied' => "$cognitive-$behavioral-$emotional",
            'interaction_type' => $this->recommendInteractionType($persona, $context),
            'follow_up_actions' => $this->suggestFollowUp($situation, $emotional)
        ];
    }

    /**
     * Determine combined tone from all dimensions
     */
    private function determineTone($cognitive, $behavioral, $emotional) {
        $cogTone = $this->toneConfig['cognitive'][$cognitive] ?? $this->toneConfig['cognitive']['C1'];
        $behTone = $this->toneConfig['behavioral'][$behavioral] ?? $this->toneConfig['behavioral']['B1'];
        $emoTone = $this->toneConfig['emotional'][$emotional] ?? $this->toneConfig['emotional']['E6'];

        return [
            'voice' => $cogTone['tone'],
            'pace' => $cogTone['pace'],
            'depth' => $cogTone['depth'],
            'interaction' => $behTone['interaction'],
            'feedback_style' => $behTone['feedback'],
            'structure' => $behTone['structure'],
            'warmth' => $emoTone['warmth'],
            'encouragement' => $emoTone['encouragement'],
            'support_type' => $emoTone['support']
        ];
    }

    /**
     * Select appropriate template based on context
     */
    private function selectTemplate($situation, $subContext, $emotional, $tone) {
        $templateKey = '';
        $variant = $tone['warmth'] === 'high' ? 'gentle' : ($tone['warmth'] === 'moderate' ? 'encouraging' : 'direct');

        switch ($situation) {
            case 'S1':
                $templateKey = 'S1_return';
                return [
                    'id' => $templateKey,
                    'content' => $this->templates[$templateKey][$emotional] ?? $this->templates[$templateKey]['E6']
                ];

            case 'S2':
                $templateKey = 'S2_hint';
                $cogKey = substr($subContext, 0, 2) === 'S2' ? 'C3' : 'C4';
                return [
                    'id' => $templateKey,
                    'content' => $this->templates[$templateKey][$cogKey] ?? $this->templates[$templateKey]['C4']
                ];

            case 'S3':
                $templateKey = 'S3_break';
                $breakType = 'scheduled';
                if (strpos($subContext, 'CTX_02') !== false) $breakType = 'fatigue';
                if (strpos($subContext, 'CTX_03') !== false) $breakType = 'achievement';
                return [
                    'id' => $templateKey,
                    'content' => $this->templates[$templateKey][$breakType]
                ];

            case 'S4':
                if (strpos($subContext, 'CTX_01') !== false) {
                    $templateKey = 'S4_concept';
                } elseif (strpos($subContext, 'CTX_02') !== false) {
                    $templateKey = 'S4_careless';
                } else {
                    $templateKey = 'S4_application';
                }
                return [
                    'id' => $templateKey,
                    'content' => $this->templates[$templateKey][$variant] ?? $this->templates[$templateKey]['encouraging']
                ];

            case 'S5':
                if ($subContext === 'S5_CTX_01') {
                    return ['id' => 'S5_confident', 'content' => $this->templates['S5_confident']];
                } elseif ($subContext === 'S5_CTX_02') {
                    return ['id' => 'S5_anxious', 'content' => $this->templates['S5_anxious']];
                } elseif ($subContext === 'S5_CTX_03') {
                    return ['id' => 'S5_frustrated', 'content' => $this->templates['S5_frustrated']];
                } else {
                    return ['id' => 'S5_bored', 'content' => $this->templates['S5_bored']];
                }

            case 'S6':
                $balanceType = 'activity';
                if (strpos($subContext, 'CTX_02') !== false) $balanceType = 'difficulty';
                if (strpos($subContext, 'CTX_03') !== false) $balanceType = 'type';
                return [
                    'id' => 'S6_balance',
                    'content' => $this->templates['S6_balance'][$balanceType]
                ];

            case 'S7':
                if (strpos($subContext, 'CTX_01') !== false) {
                    return ['id' => 'S7_start', 'content' => $this->templates['S7_start']];
                } elseif (strpos($subContext, 'CTX_03') !== false) {
                    return ['id' => 'S7_review', 'content' => $this->templates['S7_review']];
                } else {
                    return ['id' => 'S7_end', 'content' => $this->templates['S7_end']];
                }

            default:
                return ['id' => 'default', 'content' => "무엇을 도와드릴까요?"];
        }
    }

    /**
     * Personalize response with user data
     */
    private function personalizeResponse($template, $data, $tone) {
        $content = $template['content'];

        // Replace placeholders
        if (isset($data['activity_name'])) {
            $content = sprintf($content, $data['activity_name']);
        }
        if (isset($data['user_name'])) {
            $content = str_replace('%user%', $data['user_name'], $content);
        }
        if (isset($data['difficulty'])) {
            $content = sprintf($content, $data['difficulty']);
        }

        // Adjust based on pace
        if ($tone['pace'] === 'slow') {
            // Add pauses/breaks in text
            $content = str_replace('. ', '.\n\n', $content);
        }

        return $content;
    }

    /**
     * Recommend interaction type based on persona and context
     */
    private function recommendInteractionType($persona, $context) {
        $emotional = $persona['emotional']['code'] ?? 'E6';
        $behavioral = $persona['behavioral']['code'] ?? 'B1';

        // Frustrated or anxious → supportive companion
        if (in_array($emotional, ['E2', 'E5'])) {
            return 'I5'; // Activity Companion
        }

        // Bored → interactive or non-linear
        if ($emotional === 'E3') {
            return $behavioral === 'B1' ? 'I2' : 'I7';
        }

        // Active engager → interactive
        if ($behavioral === 'B1') {
            return 'I2'; // Interactive Content
        }

        // Deliberate learner → multi-turn
        if ($behavioral === 'B4') {
            return 'I6'; // Multi-turn
        }

        // Intermittent learner → timeshifting
        if ($behavioral === 'B6') {
            return 'I4'; // Timeshifting
        }

        return 'I1'; // Default to text-based
    }

    /**
     * Suggest follow-up actions
     */
    private function suggestFollowUp($situation, $emotional) {
        $actions = [];

        switch ($situation) {
            case 'S1': // Dropout
                $actions[] = ['type' => 'engagement', 'action' => 'show_progress_summary'];
                if ($emotional === 'E3') {
                    $actions[] = ['type' => 'novelty', 'action' => 'suggest_new_activity'];
                }
                break;

            case 'S4': // Error patterns
                $actions[] = ['type' => 'learning', 'action' => 'show_concept_review'];
                $actions[] = ['type' => 'practice', 'action' => 'suggest_easier_problem'];
                break;

            case 'S5': // Emotional
                if ($emotional === 'E5') {
                    $actions[] = ['type' => 'support', 'action' => 'offer_break'];
                    $actions[] = ['type' => 'learning', 'action' => 'suggest_success_path'];
                }
                break;
        }

        return $actions;
    }

    /**
     * Log generated response
     */
    private function logResponse($templateId, $personaApplied, $contextId, $content) {
        try {
            $record = new stdClass();
            $record->userid = $this->userid;
            $record->template_used = $templateId;
            $record->persona_applied = $personaApplied;
            $record->context_id = $contextId;
            $record->response_content = substr($content, 0, 1000); // Limit to 1000 chars
            $record->user_feedback = null;
            $record->timecreated = time();

            $this->db->insert_record('agent19_response_log', $record);
        } catch (Exception $e) {
            error_log("[Agent19ResponseGenerator:logResponse] Error at line " . __LINE__ . ": " . $e->getMessage());
        }
    }

    /**
     * Get templates for a specific situation
     *
     * @param string $situation Situation code (S1-S7)
     * @return array Templates for the situation
     */
    public function getTemplates($situation = null) {
        if ($situation) {
            $filtered = [];
            foreach ($this->templates as $key => $template) {
                if (strpos($key, $situation) === 0) {
                    $filtered[$key] = $template;
                }
            }
            return $filtered;
        }
        return $this->templates;
    }
}
