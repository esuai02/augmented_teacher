<?php
/**
 * WavefunctionCalculator.php - 13종 파동함수 계산기
 * 
 * 학생 상태 데이터를 기반으로 13종의 인지/감정 파동함수를 계산
 * quantum-learning-model.md에 정의된 이론을 구현
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/WavefunctionCalculator.php
 */

/**
 * Class WavefunctionCalculator
 * 
 * 13종 파동함수 정의 (quantum-learning-model.md 기반):
 * 
 * [코어 상태]
 * 1. ψ_Core     - 핵심 개념 이해 상태
 * 2. ψ_Align    - 정렬도 (현재 vs 목표)
 * 
 * [감정 상태]
 * 3. ψ_Affect   - 감정 상태 (Valence-Arousal)
 * 4. ψ_Trust    - 튜터에 대한 신뢰도
 * 
 * [인지 상태]
 * 5. ψ_WM       - 작업 기억 (집중/산만)
 * 6. ψ_Schema   - 스키마 활성화 상태
 * 7. ψ_Transfer - 전이 가능성
 * 
 * [동기 상태]
 * 8. ψ_Reward   - 보상 기대 상태
 * 9. ψ_Aha      - "아하!" 임박 상태
 * 10. ψ_Flow    - 몰입 상태
 * 
 * [위험 상태]
 * 11. ψ_Dropout  - 이탈 위험
 * 12. ψ_Confuse  - 혼란 상태
 * 13. ψ_Tunnel   - 터널링 가능성
 */
class WavefunctionCalculator
{
    /** @var WavefunctionCalculator|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var array 계산된 파동함수 캐시 */
    private $cache = [];
    
    /** @var int 캐시 유효 시간 (초) */
    private $cacheTTL = 3;
    
    /** @var int 마지막 계산 시간 */
    private $lastCalculateTime = 0;

    /**
     * Private 생성자
     */
    private function __construct() {}

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
     * 모든 파동함수 계산
     * 
     * @param array $state StateCollector에서 수집한 상태
     * @return array 13종 파동함수 값 (0~1 정규화)
     */
    public function calculateAll(array $state): array
    {
        // 캐시 확인
        $now = time();
        if (!empty($this->cache) && ($now - $this->lastCalculateTime) < $this->cacheTTL) {
            return $this->cache;
        }
        
        $normalized = $state['normalized'] ?? [];
        $emotion = $state['emotion'] ?? [];
        $cognitive = $state['cognitive'] ?? [];
        $behavior = $state['behavior'] ?? [];
        $context = $state['context'] ?? [];
        
        $wavefunctions = [
            // 코어 상태
            'psi_core' => $this->calculatePsiCore($cognitive, $normalized),
            'psi_align' => $this->calculatePsiAlign($state),
            
            // 감정 상태
            'psi_affect' => $this->calculatePsiAffect($emotion),
            'psi_trust' => $this->calculatePsiTrust($state),
            
            // 인지 상태
            'psi_wm' => $this->calculatePsiWM($behavior, $cognitive),
            'psi_schema' => $this->calculatePsiSchema($cognitive),
            'psi_transfer' => $this->calculatePsiTransfer($cognitive, $normalized),
            
            // 동기 상태
            'psi_reward' => $this->calculatePsiReward($emotion, $cognitive),
            'psi_aha' => $this->calculatePsiAha($state),
            'psi_flow' => $this->calculatePsiFlow($state),
            
            // 위험 상태
            'psi_dropout' => $state['dropout_risk'] ?? 0.2,
            'psi_confusion' => $this->calculatePsiConfusion($state),
            'psi_tunnel' => $this->calculatePsiTunnel($state),
            
            // 추가: 에너지 (종합)
            'psi_energy' => $this->calculatePsiEnergy($state)
        ];
        
        // 캐시 업데이트
        $this->cache = $wavefunctions;
        $this->lastCalculateTime = $now;
        
        return $wavefunctions;
    }

    /**
     * 특정 파동함수만 계산
     */
    public function calculate(string $name, array $state): float
    {
        $all = $this->calculateAll($state);
        return $all[$name] ?? 0.5;
    }

    // =========================================================================
    // 개별 파동함수 계산 메서드
    // =========================================================================

    /**
     * ψ_Core: 핵심 개념 이해 상태
     * 
     * 학생이 현재 문제의 핵심 개념을 얼마나 이해하고 있는지
     */
    private function calculatePsiCore(array $cognitive, array $normalized): float
    {
        $accuracy = $cognitive['recent_accuracy'] ?? 0.5;
        $understanding = $normalized['metacognition'] ?? 0.5;
        
        // 정답률과 메타인지의 가중 평균
        return 0.6 * $accuracy + 0.4 * $understanding;
    }

    /**
     * ψ_Align: 정렬도 (현재 상태 vs 목표 상태)
     * 
     * 학생의 현재 위치가 학습 목표에 얼마나 가까운지
     */
    private function calculatePsiAlign(array $state): float
    {
        // TODO: Agent03(목표분석), Agent14(현재위치) 연동
        // 현재는 간단한 추정
        
        $accuracy = $state['cognitive']['recent_accuracy'] ?? 0.5;
        $sessionProgress = min(1.0, ($state['context']['session_duration_minutes'] ?? 0) / 60);
        
        return 0.7 * $accuracy + 0.3 * $sessionProgress;
    }

    /**
     * ψ_Affect: 감정 상태 (Valence-Arousal 모델)
     * 
     * 긍정/부정 (Valence)과 활성화 수준 (Arousal)의 조합
     * 반환값: 0 = 매우 부정적, 0.5 = 중립, 1 = 매우 긍정적
     */
    private function calculatePsiAffect(array $emotion): float
    {
        $valence = $emotion['valence'] ?? 0.5;
        $frustration = $emotion['frustration'] ?? 0;
        $anxiety = $emotion['anxiety'] ?? 0;
        $confidence = $emotion['confidence'] ?? 0.5;
        
        // 부정적 감정이 있으면 감소
        $negativeEffect = 0.3 * $frustration + 0.3 * $anxiety;
        
        // 긍정적 감정(자신감)이 있으면 증가
        $positiveEffect = 0.3 * $confidence;
        
        return max(0, min(1, $valence + $positiveEffect - $negativeEffect));
    }

    /**
     * ψ_Trust: 튜터에 대한 신뢰도
     * 
     * AI 튜터의 조언을 얼마나 신뢰하는지
     */
    private function calculatePsiTrust(array $state): float
    {
        // TODO: 튜터 상호작용 이력 기반 계산
        // 현재는 기본값 + 세션 시간 기반 추정
        
        $sessionMinutes = $state['context']['session_duration_minutes'] ?? 0;
        $baseTrue = 0.6;
        
        // 세션이 길어질수록 신뢰도 약간 증가 (최대 0.2 추가)
        $sessionBonus = min(0.2, $sessionMinutes / 120 * 0.2);
        
        return min(1.0, $baseTrue + $sessionBonus);
    }

    /**
     * ψ_WM: 작업 기억 (Working Memory)
     * 
     * 현재 집중 상태. 20초 윈도우 개념 적용
     * 높을수록 집중, 낮을수록 산만
     */
    private function calculatePsiWM(array $behavior, array $cognitive): float
    {
        $idleSeconds = $behavior['idle_seconds'] ?? 0;
        $cognitiveLoad = $cognitive['cognitive_load'] ?? 0.5;
        
        // 비활성 시간이 길면 집중도 감소
        // 20초 이상 비활성이면 집중 파괴
        $idleFactor = max(0, 1 - ($idleSeconds / 20));
        
        // 인지 부하가 너무 높으면 작업 기억 용량 감소
        $loadFactor = 1 - max(0, $cognitiveLoad - 0.7);
        
        return 0.6 * $idleFactor + 0.4 * $loadFactor;
    }

    /**
     * ψ_Schema: 스키마 활성화 상태
     * 
     * 관련 배경지식이 얼마나 활성화되어 있는지
     */
    private function calculatePsiSchema(array $cognitive): float
    {
        $accuracy = $cognitive['recent_accuracy'] ?? 0.5;
        $problemsAttempted = $cognitive['problems_attempted'] ?? 0;
        
        // 문제를 많이 풀수록 스키마 활성화
        $attemptFactor = min(1.0, $problemsAttempted / 10);
        
        return 0.5 * $accuracy + 0.5 * $attemptFactor;
    }

    /**
     * ψ_Transfer: 전이 가능성
     * 
     * 현재 학습이 다른 문맥으로 전이될 가능성
     */
    private function calculatePsiTransfer(array $cognitive, array $normalized): float
    {
        $understanding = $normalized['metacognition'] ?? 0.5;
        $accuracy = $cognitive['recent_accuracy'] ?? 0.5;
        
        // 높은 이해도와 정답률이 함께 있어야 전이 가능
        return sqrt($understanding * $accuracy);  // 기하 평균
    }

    /**
     * ψ_Reward: 보상 기대 상태
     * 
     * 다음 성공에 대한 기대감. 도파민 경로와 연관
     */
    private function calculatePsiReward(array $emotion, array $cognitive): float
    {
        $confidence = $emotion['confidence'] ?? 0.5;
        $recentAccuracy = $cognitive['recent_accuracy'] ?? 0.5;
        
        // 최근 성공 경험이 보상 기대를 높임
        return 0.4 * $confidence + 0.6 * $recentAccuracy;
    }

    /**
     * ψ_Aha: "아하!" 임박 상태
     * 
     * 깨달음의 순간이 임박했는지 감지
     * 혼란도가 높지만 에너지가 남아있고 포기하지 않은 상태
     */
    private function calculatePsiAha(array $state): float
    {
        $confusion = $this->calculatePsiConfusion($state);
        $energy = $this->calculatePsiEnergy($state);
        $dropout = $state['dropout_risk'] ?? 0.2;
        
        // 혼란 + 에너지 + 비포기 = 아하 임박
        if ($confusion > 0.4 && $energy > 0.5 && $dropout < 0.5) {
            return min(1.0, $confusion * $energy * (1 - $dropout));
        }
        
        return 0.1;  // 기본값
    }

    /**
     * ψ_Flow: 몰입 상태
     * 
     * 최적의 학습 상태. 도전과 능력의 균형
     */
    private function calculatePsiFlow(array $state): float
    {
        $affect = $this->calculatePsiAffect($state['emotion'] ?? []);
        $wm = $this->calculatePsiWM($state['behavior'] ?? [], $state['cognitive'] ?? []);
        $dropout = $state['dropout_risk'] ?? 0.2;
        $calmness = $state['calmness'] ?? 0.5;
        
        // 높은 감정 + 높은 집중 + 낮은 이탈 + 적당한 침착 = 플로우
        $flow = ($affect + $wm + (1 - $dropout) + $calmness) / 4;
        
        // 플로우는 중간 영역(0.4~0.6)에서 최대
        // 너무 쉽거나(1.0) 너무 어려우면(0.0) 플로우 감소
        $optimalZone = 1 - abs($flow - 0.5) * 2;
        
        return $flow * $optimalZone;
    }

    /**
     * ψ_Confusion: 혼란 상태 (γ - Gamma)
     * 
     * 인지적 혼란 정도. 적당한 혼란은 학습에 도움
     */
    private function calculatePsiConfusion(array $state): float
    {
        $cognitiveLoad = $state['cognitive']['cognitive_load'] ?? 0.5;
        $accuracy = $state['cognitive']['recent_accuracy'] ?? 0.5;
        $anxiety = $state['emotion']['anxiety'] ?? 0;
        
        // 높은 인지 부하 + 낮은 정답률 + 불안 = 혼란
        $confusion = 0.4 * $cognitiveLoad + 0.4 * (1 - $accuracy) + 0.2 * $anxiety;
        
        return min(1.0, $confusion);
    }

    /**
     * ψ_Tunnel: 터널링 가능성
     * 
     * 에너지가 장벽보다 낮아도 스스로 돌파할 확률
     * 메타인지, 자기효능감, 끈기와 관련
     */
    private function calculatePsiTunnel(array $state): float
    {
        $normalized = $state['normalized'] ?? [];
        
        $metacognition = $normalized['metacognition'] ?? 0.5;
        $selfEfficacy = $normalized['self_efficacy'] ?? 0.5;
        $motivation = $normalized['motivation'] ?? 0.5;
        
        // 메타인지 + 자기효능감 + 동기 = 터널링 능력
        return ($metacognition + $selfEfficacy + $motivation) / 3;
    }

    /**
     * ψ_Energy: 종합 에너지 (E)
     * 
     * 학생이 문제를 풀기 위해 투입할 수 있는 정신적 에너지
     */
    private function calculatePsiEnergy(array $state): float
    {
        $sessionMinutes = $state['context']['session_duration_minutes'] ?? 0;
        $calmness = $state['calmness'] ?? 0.5;
        $motivation = $state['normalized']['motivation'] ?? 0.5;
        $confidence = $state['emotion']['confidence'] ?? 0.5;
        
        // 세션 시간에 따른 피로도
        $fatigueDecay = max(0.3, 1 - ($sessionMinutes / 90));  // 90분 후 최소 30%
        
        // 종합 에너지
        $energy = 0.3 * $calmness + 0.3 * $motivation + 0.2 * $confidence + 0.2 * $fatigueDecay;
        
        return min(1.0, $energy);
    }

    // =========================================================================
    // 유틸리티 메서드
    // =========================================================================

    /**
     * 모든 파동함수 이름 반환
     */
    public function getWavefunctionNames(): array
    {
        return [
            'psi_core', 'psi_align',           // 코어
            'psi_affect', 'psi_trust',          // 감정
            'psi_wm', 'psi_schema', 'psi_transfer',  // 인지
            'psi_reward', 'psi_aha', 'psi_flow',     // 동기
            'psi_dropout', 'psi_confusion', 'psi_tunnel',  // 위험
            'psi_energy'  // 추가
        ];
    }

    /**
     * 파동함수 설명 반환
     */
    public function getWavefunctionDescription(string $name): string
    {
        $descriptions = [
            'psi_core' => '핵심 개념 이해 상태',
            'psi_align' => '학습 목표와의 정렬도',
            'psi_affect' => '감정 상태 (긍정/부정)',
            'psi_trust' => '튜터에 대한 신뢰도',
            'psi_wm' => '작업 기억 (집중/산만)',
            'psi_schema' => '배경지식 활성화',
            'psi_transfer' => '전이 가능성',
            'psi_reward' => '보상 기대감',
            'psi_aha' => '아하! 임박 상태',
            'psi_flow' => '몰입 상태',
            'psi_dropout' => '이탈 위험',
            'psi_confusion' => '혼란도',
            'psi_tunnel' => '자기 돌파 가능성',
            'psi_energy' => '정신적 에너지'
        ];
        
        return $descriptions[$name] ?? '알 수 없음';
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->lastCalculateTime = 0;
    }
}

