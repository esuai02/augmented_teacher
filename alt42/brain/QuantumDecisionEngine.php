<?php
/**
 * QuantumDecisionEngine.php - 양자 판단 엔진
 * 
 * 13종 파동함수를 기반으로 개입 여부를 판단하는 Brain Layer의 핵심
 * 붕괴 확률(CP)을 계산하여 개입/비개입/미세개입을 결정
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/QuantumDecisionEngine.php
 */

require_once(__DIR__ . '/../config/ai_services.config.php');
require_once(__DIR__ . '/StateCollector.php');
require_once(__DIR__ . '/WavefunctionCalculator.php');

/**
 * Class InterventionDecision
 * 
 * 개입 결정 결과를 담는 DTO
 */
class InterventionDecision
{
    /** @var string 결정 타입: 'intervene', 'micro_hint', 'observe', 'none' */
    public $type;
    
    /** @var array 활성화할 에이전트 ID 목록 */
    public $agents;
    
    /** @var float 붕괴 확률 */
    public $collapseProb;
    
    /** @var string 결정 근거 */
    public $reason;
    
    /** @var array 추천 스타일 */
    public $style;
    
    /** @var int 긴급도 (1-5) */
    public $urgency;

    public function __construct(
        string $type,
        array $agents = [],
        float $collapseProb = 0,
        string $reason = '',
        array $style = [],
        int $urgency = 3
    ) {
        $this->type = $type;
        $this->agents = $agents;
        $this->collapseProb = $collapseProb;
        $this->reason = $reason;
        $this->style = $style;
        $this->urgency = $urgency;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'agents' => $this->agents,
            'collapse_probability' => $this->collapseProb,
            'reason' => $this->reason,
            'style' => $this->style,
            'urgency' => $this->urgency
        ];
    }
}

/**
 * Class QuantumDecisionEngine
 * 
 * 양자 역학 원리를 적용한 튜터 개입 판단 엔진
 * 
 * 핵심 원리:
 * 1. 학생의 인지 상태는 '중첩' 상태로 존재 (알 수도, 모를 수도)
 * 2. 튜터의 개입은 '관측'으로, 상태를 붕괴시킴
 * 3. 너무 빠른 개입은 학생의 자기 발견 기회를 빼앗음
 * 4. 너무 느린 개입은 좌절을 야기함
 */
class QuantumDecisionEngine
{
    /** @var QuantumDecisionEngine|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var StateCollector 상태 수집기 */
    private $stateCollector;
    
    /** @var WavefunctionCalculator 파동함수 계산기 */
    private $wavefunctionCalc;

    // 임계값 상수
    const THRESHOLD_INTERVENTION = 0.7;   // 즉시 개입 임계값
    const THRESHOLD_MICRO_HINT = 0.4;     // 미세 힌트 임계값
    const THRESHOLD_OBSERVATION = 0.2;    // 관찰 모드 임계값
    
    // 골든 타임 (초)
    const GOLDEN_TIME_MIN = 15;           // 최소 대기 시간
    const GOLDEN_TIME_MAX = 45;           // 최대 대기 시간
    
    // 에이전트 그룹
    const BRAIN_AGENTS = [7, 8, 9, 10, 11, 13, 14];   // α-Estimator 핵심
    const INTERVENTION_AGENTS = [20, 21];             // 개입 실행
    const CONTENT_AGENTS = [16, 19];                  // 컨텐츠 생성

    /**
     * Private 생성자
     */
    private function __construct()
    {
        $this->stateCollector = StateCollector::getInstance();
        $this->wavefunctionCalc = WavefunctionCalculator::getInstance();
    }

    /**
     * Singleton 인스턴스 반환
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 개입 결정 수행
     * 
     * @param int $studentId 학생 ID
     * @param array $additionalContext 추가 컨텍스트
     * @return InterventionDecision
     */
    public function decide(int $studentId, array $additionalContext = []): InterventionDecision
    {
        // 1. 현재 상태 수집
        $state = $this->stateCollector->setStudent($studentId)->collectRealtime();
        
        // 2. 파동함수 계산
        $wavefunctions = $this->wavefunctionCalc->calculateAll($state);
        
        // 3. 붕괴 확률 계산
        $collapseProb = $this->calculateCollapseProb($wavefunctions, $state);
        
        // 4. 결정 수행
        return $this->makeDecision($collapseProb, $wavefunctions, $state, $additionalContext);
    }

    /**
     * 붕괴 확률 CP(t) 계산
     * 
     * CP(t) = f(혼란도, 에너지, 시간, 이탈 위험)
     * 
     * @param array $wavefunctions 계산된 파동함수들
     * @param array $state 현재 상태
     * @return float 0~1 사이의 붕괴 확률
     */
    private function calculateCollapseProb(array $wavefunctions, array $state): float
    {
        // 핵심 파동함수 값 추출
        $confusion = $wavefunctions['psi_confusion'] ?? 0.3;      // 혼란도
        $energy = $wavefunctions['psi_energy'] ?? 0.5;            // 남은 에너지
        $affect = $wavefunctions['psi_affect'] ?? 0.5;            // 감정
        $dropout = $state['dropout_risk'] ?? 0.2;                 // 이탈 위험
        $idleTime = $state['behavior']['idle_seconds'] ?? 0;      // 비활성 시간
        
        // 에너지 장벽 V 계산 (문제 난이도 반영)
        $barrier = $this->calculateEnergyBarrier($state);
        
        // 터널링 확률 (에너지가 장벽보다 낮아도 통과 가능)
        $tunnelingProb = $this->calculateTunnelingProb($energy, $barrier);
        
        // 시간 요소: 골든 타임 경과 비율
        $timeDecay = min(1.0, $idleTime / self::GOLDEN_TIME_MAX);
        
        // 종합 붕괴 확률 계산
        // CP = w1*혼란 + w2*(1-에너지) + w3*이탈위험 + w4*시간감쇠 - w5*터널링
        $cp = (
            0.25 * $confusion +
            0.20 * (1 - $energy) +
            0.25 * $dropout +
            0.15 * $timeDecay +
            0.15 * (1 - $affect)  // 부정적 감정
        );
        
        // 터널링 보정 (스스로 해결 가능성)
        $cp = $cp * (1 - 0.3 * $tunnelingProb);
        
        // 긴급 상황 체크 (임계값 오버라이드)
        if ($this->isEmergency($state, $wavefunctions)) {
            $cp = max($cp, self::THRESHOLD_INTERVENTION);
        }
        
        return min(1.0, max(0.0, $cp));
    }

    /**
     * 에너지 장벽 계산
     */
    private function calculateEnergyBarrier(array $state): float
    {
        $difficulty = $state['context']['difficulty_level'] ?? 'medium';
        $cognitiveLoad = $state['cognitive']['cognitive_load'] ?? 0.5;
        
        $difficultyFactor = [
            'easy' => 0.3,
            'medium' => 0.5,
            'hard' => 0.8
        ][$difficulty] ?? 0.5;
        
        return ($difficultyFactor + $cognitiveLoad) / 2;
    }

    /**
     * 터널링 확률 계산 (스스로 장벽을 넘을 확률)
     */
    private function calculateTunnelingProb(float $energy, float $barrier): float
    {
        if ($energy >= $barrier) {
            return 1.0;  // 에너지가 충분하면 100% 통과
        }
        
        // 터널링 공식: exp(-2 * (V-E))
        $gap = $barrier - $energy;
        return exp(-2 * $gap);
    }

    /**
     * 긴급 상황 판단
     */
    private function isEmergency(array $state, array $wavefunctions): bool
    {
        $config = REALTIME_TUTOR_CONFIG;
        
        // 좌절 임계값 초과
        if (($state['emotion']['frustration'] ?? 0) > $config['frustration_threshold']) {
            return true;
        }
        
        // 불안 임계값 초과
        if (($state['emotion']['anxiety'] ?? 0) > $config['anxiety_threshold']) {
            return true;
        }
        
        // 이탈 위험 높음
        if (($state['dropout_risk'] ?? 0) > 0.8) {
            return true;
        }
        
        return false;
    }

    /**
     * 최종 결정 수행
     */
    private function makeDecision(
        float $collapseProb,
        array $wavefunctions,
        array $state,
        array $context
    ): InterventionDecision {
        
        // 스타일 결정
        $style = $this->determineStyle($wavefunctions, $state);
        
        // 즉시 개입
        if ($collapseProb >= self::THRESHOLD_INTERVENTION) {
            return new InterventionDecision(
                'intervene',
                array_merge(self::INTERVENTION_AGENTS, self::CONTENT_AGENTS),
                $collapseProb,
                $this->generateReason('intervene', $wavefunctions, $state),
                $style,
                $this->calculateUrgency($collapseProb, $state)
            );
        }
        
        // 미세 힌트
        if ($collapseProb >= self::THRESHOLD_MICRO_HINT) {
            return new InterventionDecision(
                'micro_hint',
                [21],  // 개입 실행만
                $collapseProb,
                $this->generateReason('micro_hint', $wavefunctions, $state),
                $style,
                3
            );
        }
        
        // 관찰 모드 (추임새 가능)
        if ($collapseProb >= self::THRESHOLD_OBSERVATION) {
            return new InterventionDecision(
                'observe',
                [],
                $collapseProb,
                $this->generateReason('observe', $wavefunctions, $state),
                $style,
                2
            );
        }
        
        // 개입 금지 (학생이 잘하고 있음)
        return new InterventionDecision(
            'none',
            [],
            $collapseProb,
            '학생이 적절한 상태로 학습 중입니다. 관찰만 합니다.',
            $style,
            1
        );
    }

    /**
     * 스타일 결정 (TTS, LLM에 전달)
     */
    private function determineStyle(array $wavefunctions, array $state): array
    {
        $affect = $wavefunctions['psi_affect'] ?? 0.5;
        $energy = $wavefunctions['psi_energy'] ?? 0.5;
        
        // WAVEFUNCTION_STYLE_MAP 활용
        if ($affect < 0.3) {
            $key = ($state['emotion']['frustration'] ?? 0) > 0.5 
                ? 'affect_frustrated' 
                : 'affect_anxious';
        } elseif ($affect > 0.8) {
            $key = 'affect_excited';
        } elseif ($energy < 0.3) {
            $key = 'dropout_risk_high';
        } else {
            $key = 'affect_calm';
        }
        
        return WAVEFUNCTION_STYLE_MAP[$key] ?? WAVEFUNCTION_STYLE_MAP['affect_calm'];
    }

    /**
     * 결정 근거 생성
     */
    private function generateReason(string $type, array $wavefunctions, array $state): string
    {
        $reasons = [];
        
        if (($state['emotion']['frustration'] ?? 0) > 0.5) {
            $reasons[] = '좌절 감지';
        }
        if (($state['dropout_risk'] ?? 0) > 0.5) {
            $reasons[] = '이탈 위험';
        }
        if (($state['behavior']['idle_seconds'] ?? 0) > 30) {
            $reasons[] = '장시간 비활성';
        }
        if (($wavefunctions['psi_confusion'] ?? 0) > 0.6) {
            $reasons[] = '높은 혼란도';
        }
        
        $reasonStr = empty($reasons) ? '종합 판단' : implode(', ', $reasons);
        
        return match($type) {
            'intervene' => "즉시 개입 필요: {$reasonStr}",
            'micro_hint' => "미세 힌트 권장: {$reasonStr}",
            'observe' => "관찰 모드: {$reasonStr}",
            default => "상태 양호"
        };
    }

    /**
     * 긴급도 계산 (1-5)
     */
    private function calculateUrgency(float $collapseProb, array $state): int
    {
        if ($collapseProb > 0.9 || ($state['emotion']['frustration'] ?? 0) > 0.8) {
            return 5;
        }
        if ($collapseProb > 0.8 || ($state['dropout_risk'] ?? 0) > 0.7) {
            return 4;
        }
        if ($collapseProb > 0.7) {
            return 3;
        }
        if ($collapseProb > 0.5) {
            return 2;
        }
        return 1;
    }

    /**
     * 디버그용: 전체 상태 출력
     */
    public function getDebugInfo(int $studentId): array
    {
        $state = $this->stateCollector->setStudent($studentId)->collectRealtime();
        $wavefunctions = $this->wavefunctionCalc->calculateAll($state);
        $decision = $this->decide($studentId);
        
        return [
            'state' => $state,
            'wavefunctions' => $wavefunctions,
            'decision' => $decision->toArray(),
            'thresholds' => [
                'intervention' => self::THRESHOLD_INTERVENTION,
                'micro_hint' => self::THRESHOLD_MICRO_HINT,
                'observation' => self::THRESHOLD_OBSERVATION
            ]
        ];
    }
}

