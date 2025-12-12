<?php
/**
 * Agent19 Persona Engine
 * 학습 콘텐츠 상호작용을 위한 3차원 페르소나 엔진
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Engine
 * @version     1.0.0
 * @author      System
 * @created     2025-12-02
 *
 * 관련 DB 테이블:
 * - mdl_agent19_persona_state: userid(BIGINT), cognitive(VARCHAR), behavioral(VARCHAR), emotional(VARCHAR), composite(VARCHAR), confidence(DECIMAL), timecreated(BIGINT)
 * - mdl_agent19_persona_history: id(BIGINT), userid(BIGINT), persona_before(VARCHAR), persona_after(VARCHAR), trigger_reason(TEXT), timecreated(BIGINT)
 */

defined('MOODLE_INTERNAL') || die();

class Agent19PersonaEngine {

    /** @var object Moodle DB instance */
    private $db;

    /** @var int Current user ID */
    private $userid;

    /** @var array Persona definitions from personas.md */
    private $personaDefinitions;

    /** @var array Context definitions from contextlist.md */
    private $contextDefinitions;

    /** @var float Confidence threshold for AI enhancement */
    const AI_CONFIDENCE_THRESHOLD = 0.7;

    /** @var string Base path for persona system */
    private $basePath;

    /**
     * Constructor
     *
     * @param int $userid User ID
     */
    public function __construct($userid) {
        global $DB;
        $this->db = $DB;
        $this->userid = $userid;
        $this->basePath = dirname(__FILE__) . '/..';
        $this->loadDefinitions();
    }

    /**
     * Load persona and context definitions
     */
    private function loadDefinitions() {
        // 인지적 페르소나 (C1-C6)
        $this->personaDefinitions['cognitive'] = [
            'C1' => [
                'name' => '활성 인지',
                'name_en' => 'Active Cognition',
                'indicators' => ['high_engagement', 'fast_response', 'complex_problem_attempt'],
                'strategy' => 'challenge_oriented'
            ],
            'C2' => [
                'name' => '피로 인지',
                'name_en' => 'Fatigued Cognition',
                'indicators' => ['slow_response', 'accuracy_decline', 'skip_increase'],
                'strategy' => 'support_oriented'
            ],
            'C3' => [
                'name' => '개념 지향',
                'name_en' => 'Concept Oriented',
                'indicators' => ['explanation_seeking', 'why_questions', 'connection_making'],
                'strategy' => 'explanation_rich'
            ],
            'C4' => [
                'name' => '문제 해결',
                'name_en' => 'Problem Solving',
                'indicators' => ['trial_and_error', 'strategy_variation', 'persistence'],
                'strategy' => 'scaffolded_hints'
            ],
            'C5' => [
                'name' => '패턴 인식',
                'name_en' => 'Pattern Recognition',
                'indicators' => ['similar_problem_success', 'routine_formation', 'efficiency_increase'],
                'strategy' => 'pattern_reinforcement'
            ],
            'C6' => [
                'name' => '추론 지향',
                'name_en' => 'Reasoning Oriented',
                'indicators' => ['logical_progression', 'inference_making', 'deep_analysis'],
                'strategy' => 'socratic_method'
            ]
        ];

        // 행동적 페르소나 (B1-B6)
        $this->personaDefinitions['behavioral'] = [
            'B1' => [
                'name' => '적극적 참여자',
                'name_en' => 'Active Engager',
                'indicators' => ['high_click_rate', 'exploration', 'proactive_action'],
                'style' => 'interactive_rich'
            ],
            'B2' => [
                'name' => '수동적 관찰자',
                'name_en' => 'Passive Observer',
                'indicators' => ['low_interaction', 'content_consumption', 'minimal_response'],
                'style' => 'guided_step_by_step'
            ],
            'B3' => [
                'name' => '즉흥적 학습자',
                'name_en' => 'Spontaneous Learner',
                'indicators' => ['irregular_pattern', 'quick_decisions', 'varied_paths'],
                'style' => 'flexible_adaptive'
            ],
            'B4' => [
                'name' => '신중한 학습자',
                'name_en' => 'Deliberate Learner',
                'indicators' => ['careful_reading', 'review_before_answer', 'systematic_approach'],
                'style' => 'structured_sequential'
            ],
            'B5' => [
                'name' => '지속 몰입형',
                'name_en' => 'Sustained Flow Learner',
                'indicators' => ['long_session', 'consistent_pace', 'deep_engagement'],
                'style' => 'immersive_continuous'
            ],
            'B6' => [
                'name' => '간헐적 학습자',
                'name_en' => 'Intermittent Learner',
                'indicators' => ['short_sessions', 'frequent_breaks', 'irregular_timing'],
                'style' => 'micro_learning'
            ]
        ];

        // 감정적 페르소나 (E1-E6)
        $this->personaDefinitions['emotional'] = [
            'E1' => [
                'name' => '자신감 상태',
                'name_en' => 'Confident State',
                'indicators' => ['quick_answers', 'challenge_seeking', 'positive_persistence'],
                'support' => 'maintain_challenge'
            ],
            'E2' => [
                'name' => '불안 상태',
                'name_en' => 'Anxious State',
                'indicators' => ['hesitation', 'answer_changes', 'avoidance_behavior'],
                'support' => 'reassurance_scaffolding'
            ],
            'E3' => [
                'name' => '권태 상태',
                'name_en' => 'Bored State',
                'indicators' => ['fast_skipping', 'low_engagement', 'pattern_answers'],
                'support' => 'novelty_injection'
            ],
            'E4' => [
                'name' => '도전 상태',
                'name_en' => 'Challenged State',
                'indicators' => ['struggle_with_effort', 'retry_attempts', 'focused_attention'],
                'support' => 'balanced_support'
            ],
            'E5' => [
                'name' => '좌절 상태',
                'name_en' => 'Frustrated State',
                'indicators' => ['repeated_failures', 'long_pauses', 'giving_up_signs'],
                'support' => 'immediate_support'
            ],
            'E6' => [
                'name' => '안정 상태',
                'name_en' => 'Stable State',
                'indicators' => ['consistent_performance', 'steady_pace', 'neutral_engagement'],
                'support' => 'maintain_stability'
            ]
        ];
    }

    /**
     * Detect current persona based on user behavior data
     *
     * @param array $behaviorData User behavior metrics
     * @return array Detected persona with confidence scores
     */
    public function detectPersona($behaviorData) {
        $result = [
            'cognitive' => $this->detectCognitivePersona($behaviorData),
            'behavioral' => $this->detectBehavioralPersona($behaviorData),
            'emotional' => $this->detectEmotionalPersona($behaviorData),
            'confidence' => 0.0,
            'needs_ai_enhancement' => false
        ];

        // Calculate overall confidence
        $result['confidence'] = ($result['cognitive']['confidence'] +
                                 $result['behavioral']['confidence'] +
                                 $result['emotional']['confidence']) / 3;

        // Check if AI enhancement is needed
        if ($result['confidence'] < self::AI_CONFIDENCE_THRESHOLD) {
            $result['needs_ai_enhancement'] = true;
        }

        // Generate composite persona code
        $result['composite'] = $result['cognitive']['code'] . '-' .
                              $result['behavioral']['code'] . '-' .
                              $result['emotional']['code'];

        return $result;
    }

    /**
     * Detect cognitive persona
     *
     * @param array $data Behavior data
     * @return array Cognitive persona detection result
     */
    private function detectCognitivePersona($data) {
        $scores = [];

        foreach ($this->personaDefinitions['cognitive'] as $code => $persona) {
            $score = 0;
            $matchCount = 0;

            foreach ($persona['indicators'] as $indicator) {
                if ($this->checkIndicator($indicator, $data)) {
                    $matchCount++;
                }
            }

            $scores[$code] = $matchCount / count($persona['indicators']);
        }

        arsort($scores);
        $topCode = key($scores);

        return [
            'code' => $topCode,
            'name' => $this->personaDefinitions['cognitive'][$topCode]['name'],
            'confidence' => $scores[$topCode],
            'all_scores' => $scores
        ];
    }

    /**
     * Detect behavioral persona
     *
     * @param array $data Behavior data
     * @return array Behavioral persona detection result
     */
    private function detectBehavioralPersona($data) {
        $scores = [];

        foreach ($this->personaDefinitions['behavioral'] as $code => $persona) {
            $score = 0;
            $matchCount = 0;

            foreach ($persona['indicators'] as $indicator) {
                if ($this->checkIndicator($indicator, $data)) {
                    $matchCount++;
                }
            }

            $scores[$code] = $matchCount / count($persona['indicators']);
        }

        arsort($scores);
        $topCode = key($scores);

        return [
            'code' => $topCode,
            'name' => $this->personaDefinitions['behavioral'][$topCode]['name'],
            'confidence' => $scores[$topCode],
            'all_scores' => $scores
        ];
    }

    /**
     * Detect emotional persona
     *
     * @param array $data Behavior data
     * @return array Emotional persona detection result
     */
    private function detectEmotionalPersona($data) {
        $scores = [];

        foreach ($this->personaDefinitions['emotional'] as $code => $persona) {
            $score = 0;
            $matchCount = 0;

            foreach ($persona['indicators'] as $indicator) {
                if ($this->checkIndicator($indicator, $data)) {
                    $matchCount++;
                }
            }

            $scores[$code] = $matchCount / count($persona['indicators']);
        }

        arsort($scores);
        $topCode = key($scores);

        return [
            'code' => $topCode,
            'name' => $this->personaDefinitions['emotional'][$topCode]['name'],
            'confidence' => $scores[$topCode],
            'all_scores' => $scores
        ];
    }

    /**
     * Check if an indicator is present in the data
     *
     * @param string $indicator Indicator name
     * @param array $data Behavior data
     * @return bool True if indicator is detected
     */
    private function checkIndicator($indicator, $data) {
        $thresholds = [
            // Cognitive indicators
            'high_engagement' => ['metric' => 'engagement_rate', 'op' => '>', 'value' => 0.7],
            'fast_response' => ['metric' => 'avg_response_time', 'op' => '<', 'value' => 10],
            'complex_problem_attempt' => ['metric' => 'hard_problem_ratio', 'op' => '>', 'value' => 0.3],
            'slow_response' => ['metric' => 'avg_response_time', 'op' => '>', 'value' => 30],
            'accuracy_decline' => ['metric' => 'accuracy_trend', 'op' => '<', 'value' => -0.1],
            'skip_increase' => ['metric' => 'skip_rate', 'op' => '>', 'value' => 0.2],
            'explanation_seeking' => ['metric' => 'hint_usage', 'op' => '>', 'value' => 0.5],
            'trial_and_error' => ['metric' => 'retry_count', 'op' => '>', 'value' => 2],
            'persistence' => ['metric' => 'completion_rate', 'op' => '>', 'value' => 0.8],

            // Behavioral indicators
            'high_click_rate' => ['metric' => 'click_rate', 'op' => '>', 'value' => 0.5],
            'exploration' => ['metric' => 'unique_paths', 'op' => '>', 'value' => 3],
            'low_interaction' => ['metric' => 'interaction_rate', 'op' => '<', 'value' => 0.3],
            'long_session' => ['metric' => 'session_duration', 'op' => '>', 'value' => 1800],
            'short_sessions' => ['metric' => 'session_duration', 'op' => '<', 'value' => 600],

            // Emotional indicators
            'quick_answers' => ['metric' => 'decision_speed', 'op' => '>', 'value' => 0.7],
            'hesitation' => ['metric' => 'answer_changes', 'op' => '>', 'value' => 2],
            'fast_skipping' => ['metric' => 'skip_rate', 'op' => '>', 'value' => 0.4],
            'repeated_failures' => ['metric' => 'consecutive_errors', 'op' => '>', 'value' => 3],
            'long_pauses' => ['metric' => 'pause_duration', 'op' => '>', 'value' => 60]
        ];

        if (!isset($thresholds[$indicator])) {
            return false;
        }

        $threshold = $thresholds[$indicator];
        $metric = $threshold['metric'];

        if (!isset($data[$metric])) {
            return false;
        }

        $value = $data[$metric];

        switch ($threshold['op']) {
            case '>':
                return $value > $threshold['value'];
            case '<':
                return $value < $threshold['value'];
            case '>=':
                return $value >= $threshold['value'];
            case '<=':
                return $value <= $threshold['value'];
            case '==':
                return $value == $threshold['value'];
            default:
                return false;
        }
    }

    /**
     * Save detected persona to database
     *
     * @param array $persona Detected persona data
     * @return bool Success status
     */
    public function savePersonaState($persona) {
        try {
            $record = new stdClass();
            $record->userid = $this->userid;
            $record->cognitive = $persona['cognitive']['code'];
            $record->behavioral = $persona['behavioral']['code'];
            $record->emotional = $persona['emotional']['code'];
            $record->composite = $persona['composite'];
            $record->confidence = $persona['confidence'];
            $record->timecreated = time();

            // Check if record exists
            $existing = $this->db->get_record('agent19_persona_state', ['userid' => $this->userid]);

            if ($existing) {
                // Log transition if persona changed
                if ($existing->composite !== $persona['composite']) {
                    $this->logPersonaTransition($existing->composite, $persona['composite'], 'behavior_analysis');
                }

                $record->id = $existing->id;
                $this->db->update_record('agent19_persona_state', $record);
            } else {
                $this->db->insert_record('agent19_persona_state', $record);
            }

            return true;
        } catch (Exception $e) {
            error_log("[Agent19PersonaEngine:savePersonaState] Error at line " . __LINE__ . ": " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log persona transition
     *
     * @param string $before Previous persona composite
     * @param string $after New persona composite
     * @param string $reason Transition reason
     */
    private function logPersonaTransition($before, $after, $reason) {
        $record = new stdClass();
        $record->userid = $this->userid;
        $record->persona_before = $before;
        $record->persona_after = $after;
        $record->trigger_reason = $reason;
        $record->timecreated = time();

        $this->db->insert_record('agent19_persona_history', $record);
    }

    /**
     * Get current persona state for user
     *
     * @return object|null Persona state or null
     */
    public function getCurrentPersona() {
        return $this->db->get_record('agent19_persona_state', ['userid' => $this->userid]);
    }

    /**
     * Get persona definitions
     *
     * @param string|null $dimension Dimension name (cognitive, behavioral, emotional) or null for all
     * @return array Persona definitions
     */
    public function getPersonaDefinitions($dimension = null) {
        if ($dimension && isset($this->personaDefinitions[$dimension])) {
            return $this->personaDefinitions[$dimension];
        }
        return $this->personaDefinitions;
    }

    /**
     * Get recommended interaction type based on persona
     *
     * @param string $composite Composite persona code (e.g., "C1-B1-E1")
     * @return array Recommended interaction types
     */
    public function getRecommendedInteraction($composite) {
        $parts = explode('-', $composite);
        if (count($parts) !== 3) {
            return ['I1']; // Default to text-based
        }

        $cognitive = $parts[0];
        $behavioral = $parts[1];
        $emotional = $parts[2];

        $recommendations = [];

        // Cognitive-based recommendations
        $cognitiveMap = [
            'C1' => ['I2', 'I7'], // Active → Interactive, Non-linear
            'C2' => ['I4', 'I3'], // Fatigued → Timeshifting, Routine
            'C3' => ['I1', 'I6'], // Concept → Text, Multi-turn
            'C4' => ['I6', 'I2'], // Problem-solving → Multi-turn, Interactive
            'C5' => ['I3', 'I7'], // Pattern → Routine, Non-linear
            'C6' => ['I6', 'I1']  // Reasoning → Multi-turn, Text
        ];

        // Behavioral-based recommendations
        $behavioralMap = [
            'B1' => ['I2', 'I5'], // Active → Interactive, Companion
            'B2' => ['I1', 'I5'], // Passive → Text, Companion
            'B3' => ['I7', 'I2'], // Spontaneous → Non-linear, Interactive
            'B4' => ['I6', 'I1'], // Deliberate → Multi-turn, Text
            'B5' => ['I6', 'I2'], // Sustained → Multi-turn, Interactive
            'B6' => ['I4', 'I3']  // Intermittent → Timeshifting, Routine
        ];

        // Combine recommendations
        if (isset($cognitiveMap[$cognitive])) {
            $recommendations = array_merge($recommendations, $cognitiveMap[$cognitive]);
        }
        if (isset($behavioralMap[$behavioral])) {
            $recommendations = array_merge($recommendations, $behavioralMap[$behavioral]);
        }

        // Count frequency and sort
        $frequency = array_count_values($recommendations);
        arsort($frequency);

        return array_keys($frequency);
    }
}
