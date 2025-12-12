<?php
/**
 * CalmnessDataContext - Agent08 전용 데이터 컨텍스트
 *
 * BaseDataContext를 확장하여 침착성(Calmness) 점수 기반의 데이터 컨텍스트를 제공합니다.
 * 침착성 점수 범위: 95+, 90-94, 85-89, 80-84, 75-79, <75
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseDataContext.php');

class CalmnessDataContext extends BaseDataContext {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var string 에이전트 ID */
    protected $agentId = 'agent08';

    /** @var array 침착성 관련 감정 키워드 (확장) */
    protected $calmnessKeywords = [
        'calm' => ['차분', '평온', '안정', '여유', '편안', '고요', '평화', '잔잔'],
        'anxious' => ['불안', '초조', '긴장', '떨려', '두근', '조급', '급해', '시간'],
        'stressed' => ['스트레스', '힘들', '지쳐', '피곤', '압박', '부담', '벅차'],
        'frustrated' => ['짜증', '화나', '답답', '속상', '억울', '열받', '빡치'],
        'overwhelmed' => ['막막', '어지러', '혼란', '모르겠', '감당', '버거'],
        'focused' => ['집중', '몰입', '명확', '또렷', '깨끗', '맑은']
    ];

    /** @var array 침착성 점수 범위별 상황 코드 */
    protected $calmnessLevels = [
        'C95' => ['min' => 95, 'max' => 100, 'situation' => 'optimal_calm', 'description' => '최적의 침착 상태'],
        'C90' => ['min' => 90, 'max' => 94, 'situation' => 'good_calm', 'description' => '양호한 침착 상태'],
        'C85' => ['min' => 85, 'max' => 89, 'situation' => 'moderate_calm', 'description' => '적정 침착 상태'],
        'C80' => ['min' => 80, 'max' => 84, 'situation' => 'mild_anxiety', 'description' => '경미한 불안'],
        'C75' => ['min' => 75, 'max' => 79, 'situation' => 'moderate_anxiety', 'description' => '중간 불안'],
        'C_crisis' => ['min' => 0, 'max' => 74, 'situation' => 'high_anxiety', 'description' => '높은 불안/위기']
    ];

    /**
     * 생성자
     */
    public function __construct() {
        parent::__construct($this->agentId);
        $this->extendEmotionKeywords();
    }

    /**
     * 감정 키워드 확장
     */
    protected function extendEmotionKeywords(): void {
        foreach ($this->calmnessKeywords as $emotion => $keywords) {
            $this->addEmotionKeywords($emotion, $keywords);
        }
    }

    /**
     * Agent08 전용 데이터 로드
     *
     * @param int $userId 사용자 ID
     * @param array $baseContext 기본 컨텍스트
     * @return array 확장된 컨텍스트
     */
    public function loadAgentSpecificData(int $userId, array $baseContext): array {
        global $DB;

        try {
            // 침착성 이력 로드
            $calmnessHistory = $DB->get_records_sql(
                "SELECT * FROM {at_calmness_scores}
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT 10",
                [$userId]
            );

            $baseContext['calmness_history'] = $calmnessHistory ? array_values($calmnessHistory) : [];

            // 현재 침착성 점수 계산
            $baseContext['current_calmness_score'] = $this->calculateCurrentCalmnessScore($baseContext);

            // 침착성 레벨 결정
            $baseContext['calmness_level'] = $this->determineCalmnessLevel($baseContext['current_calmness_score']);

            // 침착성 트렌드 분석
            $baseContext['calmness_trend'] = $this->analyzeCalmnessrend($calmnessHistory);

            // 추천 개입 전략
            $baseContext['recommended_intervention'] = $this->recommendIntervention($baseContext);

            return $baseContext;

        } catch (Exception $e) {
            error_log("[CalmnessDataContext] {$this->currentFile}:" . __LINE__ .
                " - Agent 전용 데이터 로드 실패: " . $e->getMessage());
            return $baseContext;
        }
    }

    /**
     * 현재 침착성 점수 계산
     *
     * @param array $context 컨텍스트 데이터
     * @return int 침착성 점수 (0-100)
     */
    public function calculateCurrentCalmnessScore(array $context): int {
        $score = 85; // 기본 점수

        // 감정 상태 기반 조정
        $emotionalState = $context['emotional_state'] ?? 'neutral';
        $emotionModifiers = [
            'calm' => 10,
            'focused' => 8,
            'neutral' => 0,
            'positive' => 5,
            'anxious' => -15,
            'stressed' => -12,
            'frustrated' => -18,
            'overwhelmed' => -20,
            'negative' => -10
        ];
        $score += $emotionModifiers[$emotionalState] ?? 0;

        // 메시지 분석 결과 기반 조정
        if (isset($context['message_analysis'])) {
            $analysis = $context['message_analysis'];

            // 긴급도에 따른 조정
            if (($analysis['urgency'] ?? 'normal') === 'urgent') {
                $score -= 8;
            }

            // 메시지 길이에 따른 미세 조정 (매우 짧거나 길면 불안 징후)
            $length = $analysis['length'] ?? 0;
            if ($length < 5) {
                $score -= 3;
            } elseif ($length > 200) {
                $score -= 5;
            }
        }

        // 이력 기반 조정
        if (!empty($context['calmness_history'])) {
            $recentScore = $context['calmness_history'][0]->score ?? 85;
            // 급격한 변화 방지 (이전 점수의 30% 반영)
            $score = (int)($score * 0.7 + $recentScore * 0.3);
        }

        // 범위 제한
        return max(0, min(100, $score));
    }

    /**
     * 침착성 레벨 결정
     *
     * @param int $score 침착성 점수
     * @return string 레벨 코드 (C95, C90, C85, C80, C75, C_crisis)
     */
    public function determineCalmnessLevel(int $score): string {
        foreach ($this->calmnessLevels as $level => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $level;
            }
        }
        return 'C85'; // 기본값
    }

    /**
     * 상황 코드 결정 (BaseDataContext 구현)
     *
     * @param array $sessionData 세션 데이터
     * @return string 상황 코드
     */
    public function determineSituation(array $sessionData): string {
        // 침착성 점수 기반 상황 결정
        $calmnessScore = $sessionData['current_calmness_score'] ?? 85;
        $calmnessLevel = $this->determineCalmnessLevel($calmnessScore);

        return $this->calmnessLevels[$calmnessLevel]['situation'] ?? 'moderate_calm';
    }

    /**
     * 침착성 트렌드 분석
     *
     * @param array $history 침착성 이력
     * @return array 트렌드 분석 결과
     */
    protected function analyzeCalmnessrend(array $history): array {
        if (count($history) < 2) {
            return ['trend' => 'stable', 'change' => 0, 'volatility' => 'low'];
        }

        $scores = array_map(function($record) {
            return $record->score ?? 85;
        }, $history);

        $recent = $scores[0];
        $previous = $scores[1];
        $change = $recent - $previous;

        // 트렌드 결정
        if ($change > 5) {
            $trend = 'improving';
        } elseif ($change < -5) {
            $trend = 'declining';
        } else {
            $trend = 'stable';
        }

        // 변동성 계산
        $variance = 0;
        if (count($scores) >= 3) {
            $mean = array_sum($scores) / count($scores);
            $variance = array_sum(array_map(function($s) use ($mean) {
                return pow($s - $mean, 2);
            }, $scores)) / count($scores);
        }

        $volatility = $variance > 100 ? 'high' : ($variance > 25 ? 'medium' : 'low');

        return [
            'trend' => $trend,
            'change' => $change,
            'volatility' => $volatility,
            'recent_average' => array_sum(array_slice($scores, 0, 5)) / min(5, count($scores))
        ];
    }

    /**
     * 개입 전략 추천
     *
     * @param array $context 컨텍스트
     * @return array 추천 개입 전략
     */
    protected function recommendIntervention(array $context): array {
        $calmnessLevel = $context['calmness_level'] ?? 'C85';
        $trend = $context['calmness_trend']['trend'] ?? 'stable';

        $interventions = [
            'C95' => ['primary' => 'MindfulnessSupport', 'secondary' => 'SkillBuilding'],
            'C90' => ['primary' => 'FocusGuidance', 'secondary' => 'InformationProvision'],
            'C85' => ['primary' => 'InformationProvision', 'secondary' => 'EmotionalSupport'],
            'C80' => ['primary' => 'CalmnessCoaching', 'secondary' => 'EmotionalSupport'],
            'C75' => ['primary' => 'EmotionalSupport', 'secondary' => 'CalmnessCoaching'],
            'C_crisis' => ['primary' => 'CrisisIntervention', 'secondary' => 'SafetyNet']
        ];

        $recommended = $interventions[$calmnessLevel] ?? $interventions['C85'];

        // 트렌드에 따른 조정
        if ($trend === 'declining') {
            $recommended['urgency'] = 'high';
            $recommended['monitoring'] = true;
        } elseif ($trend === 'improving') {
            $recommended['urgency'] = 'low';
            $recommended['reinforcement'] = true;
        }

        return $recommended;
    }

    /**
     * 메시지 분석 (확장)
     *
     * @param string $message 분석할 메시지
     * @return array 분석 결과
     */
    public function analyzeMessage(string $message): array {
        // 부모 클래스의 기본 분석
        $analysis = parent::analyzeMessage($message);

        // 침착성 관련 추가 분석
        $analysis['calmness_indicators'] = $this->analyzeCalmnessIndicators($message);
        $analysis['breathing_suggestion'] = $this->needsBreathingExercise($message);
        $analysis['grounding_needed'] = $this->needsGroundingExercise($analysis);

        return $analysis;
    }

    /**
     * 침착성 지표 분석
     *
     * @param string $message 메시지
     * @return array 침착성 지표
     */
    protected function analyzeCalmnessIndicators(string $message): array {
        $indicators = [
            'calm_words' => 0,
            'anxiety_words' => 0,
            'stress_words' => 0,
            'focus_words' => 0
        ];

        foreach ($this->calmnessKeywords['calm'] as $word) {
            if (mb_strpos($message, $word) !== false) {
                $indicators['calm_words']++;
            }
        }

        foreach ($this->calmnessKeywords['anxious'] as $word) {
            if (mb_strpos($message, $word) !== false) {
                $indicators['anxiety_words']++;
            }
        }

        foreach ($this->calmnessKeywords['stressed'] as $word) {
            if (mb_strpos($message, $word) !== false) {
                $indicators['stress_words']++;
            }
        }

        foreach ($this->calmnessKeywords['focused'] as $word) {
            if (mb_strpos($message, $word) !== false) {
                $indicators['focus_words']++;
            }
        }

        return $indicators;
    }

    /**
     * 호흡 운동 필요 여부 판단
     *
     * @param string $message 메시지
     * @return bool 호흡 운동 필요 여부
     */
    protected function needsBreathingExercise(string $message): bool {
        $breathingTriggers = ['숨', '호흡', '심장', '두근', '떨려', '긴장'];
        foreach ($breathingTriggers as $trigger) {
            if (mb_strpos($message, $trigger) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 그라운딩 운동 필요 여부 판단
     *
     * @param array $analysis 분석 결과
     * @return bool 그라운딩 필요 여부
     */
    protected function needsGroundingExercise(array $analysis): bool {
        $emotionalState = $analysis['emotional_state'] ?? 'neutral';
        return in_array($emotionalState, ['overwhelmed', 'anxious', 'frustrated']);
    }

    /**
     * 침착성 점수 저장
     *
     * @param int $userId 사용자 ID
     * @param int $score 침착성 점수
     * @param array $metadata 추가 메타데이터
     * @return bool 저장 성공 여부
     */
    public function saveCalmnessScore(int $userId, int $score, array $metadata = []): bool {
        global $DB;

        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->score = $score;
            $record->level = $this->determineCalmnessLevel($score);
            $record->metadata = json_encode($metadata);
            $record->created_at = date('Y-m-d H:i:s');

            $DB->insert_record('at_calmness_scores', $record);
            return true;

        } catch (Exception $e) {
            error_log("[CalmnessDataContext] {$this->currentFile}:" . __LINE__ .
                " - 침착성 점수 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 침착성 레벨 정보 반환
     *
     * @return array 레벨 정보
     */
    public function getCalmnessLevels(): array {
        return $this->calmnessLevels;
    }
}

/*
 * 관련 DB 테이블:
 * - at_calmness_scores (침착성 점수 이력)
 *   - id: bigint(10), PRIMARY KEY
 *   - user_id: bigint(10), Moodle 사용자 ID
 *   - agent_id: varchar(50), 에이전트 식별자
 *   - score: int(3), 침착성 점수 (0-100)
 *   - level: varchar(20), 레벨 코드 (C95, C90, ...)
 *   - metadata: text, JSON 메타데이터
 *   - created_at: datetime, 생성 시간
 *
 * - at_agent_persona_state (에이전트별 페르소나 상태 - 공통)
 */
