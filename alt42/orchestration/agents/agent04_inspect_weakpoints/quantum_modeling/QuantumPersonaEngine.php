<?php
/**
 * QuantumPersonaEngine - 양자 모델링 기반 페르소나 엔진
 * 
 * 학생의 학습 상태를 양자 파동 함수(Wave Function)로 정의하고,
 * 페르소나의 중첩(Superposition), 간섭(Interference), 붕괴(Collapse)를 
 * 모델링하여 최적의 개입 타이밍과 전략을 계산합니다.
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling
 * @version 1.0.0
 * @since 2025-12-06
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class QuantumPersonaEngine
{
    /** @var string 에이전트 ID */
    private $agentId = 'agent04';
    
    /** @var string 버전 */
    private $version = '1.0.0';
    
    /** @var string 현재 파일 경로 (에러 출력용) */
    private $currentFile = __FILE__;
    
    /** @var int 현재 사용자 ID */
    private $userId;
    
    /**
     * 기저 페르소나 상태 정의 (Basis States)
     * 4가지 기본 학습 성향을 양자 상태의 축으로 설정
     */
    const PERSONA_BASIS = [
        'S' => ['name' => 'Sprinter', 'icon' => '⚡', 'desc' => '속도 중심, 직관적, 실수 잦음'],
        'D' => ['name' => 'Diver', 'icon' => '🤿', 'desc' => '원리 중심, 느림, 완벽주의'],
        'G' => ['name' => 'Gamer', 'icon' => '🎮', 'desc' => '보상/경쟁 중심, 도파민 추구'],
        'A' => ['name' => 'Architect', 'icon' => '🏛️', 'desc' => '계획/안정 중심, 리스크 회피']
    ];
    
    /**
     * 페르소나 전환 비용 행렬 (인접 성향은 낮은 비용, 정반대는 높은 비용)
     */
    const TRANSITION_COSTS = [
        'S' => ['S' => 0, 'D' => 5, 'G' => 1, 'A' => 2],
        'D' => ['S' => 5, 'D' => 0, 'G' => 2, 'A' => 1],
        'G' => ['S' => 1, 'D' => 2, 'G' => 0, 'A' => 5],
        'A' => ['S' => 2, 'D' => 1, 'G' => 5, 'A' => 0]
    ];
    
    /**
     * 생성자
     */
    public function __construct(int $userId = 0)
    {
        global $USER;
        $this->userId = $userId ?: ($USER->id ?? 0);
    }
    
    // ============================================================
    // SECTION 1: 양자 상태 초기화 및 관리
    // ============================================================
    
    /**
     * 학생의 초기 양자 상태 벡터 생성
     * 온보딩 데이터(MBTI, 학습 스타일)를 기반으로 초기 확률 진폭 설정
     *
     * @param array $onboardingData 온보딩 데이터
     * @return array 상태 벡터 [S, D, G, A]
     */
    public function initializeStateVector(array $onboardingData = []): array
    {
        try {
            // 기본 균등 분포 (각 페르소나 25% 확률)
            $stateVector = [
                'S' => 0.5,  // Sprinter
                'D' => 0.5,  // Diver
                'G' => 0.5,  // Gamer
                'A' => 0.5   // Architect
            ];
            
            // MBTI 기반 초기화
            if (!empty($onboardingData['mbti'])) {
                $stateVector = $this->adjustByMBTI($stateVector, $onboardingData['mbti']);
            }
            
            // 학습 스타일 기반 조정
            if (!empty($onboardingData['learning_style'])) {
                $stateVector = $this->adjustByLearningStyle($stateVector, $onboardingData['learning_style']);
            }
            
            // 정규화 (확률 총합 = 1)
            return $this->normalizeStateVector($stateVector);
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] initializeStateVector error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return $this->normalizeStateVector($stateVector ?? ['S' => 0.5, 'D' => 0.5, 'G' => 0.5, 'A' => 0.5]);
        }
    }
    
    /**
     * MBTI 기반 상태 벡터 조정
     */
    private function adjustByMBTI(array $state, string $mbti): array
    {
        $mbti = strtoupper($mbti);
        
        // E/I: 외향/내향 → Gamer/Diver 영향
        if (strpos($mbti, 'E') !== false) {
            $state['G'] += 0.2;
        } else {
            $state['D'] += 0.2;
        }
        
        // S/N: 감각/직관 → Architect/Sprinter 영향
        if (strpos($mbti, 'S') !== false) {
            $state['A'] += 0.15;
        } else {
            $state['S'] += 0.15;
        }
        
        // T/F: 사고/감정 → 분석적/감정적 접근
        if (strpos($mbti, 'T') !== false) {
            $state['D'] += 0.1;
            $state['A'] += 0.1;
        } else {
            $state['G'] += 0.1;
            $state['S'] += 0.1;
        }
        
        // J/P: 판단/인식 → 계획/즉흥
        if (strpos($mbti, 'J') !== false) {
            $state['A'] += 0.2;
        } else {
            $state['S'] += 0.2;
        }
        
        return $state;
    }
    
    /**
     * 학습 스타일 기반 상태 벡터 조정
     */
    private function adjustByLearningStyle(array $state, string $style): array
    {
        switch (strtolower($style)) {
            case 'visual':
                $state['S'] += 0.15;
                $state['G'] += 0.1;
                break;
            case 'auditory':
                $state['D'] += 0.15;
                break;
            case 'kinesthetic':
                $state['S'] += 0.2;
                $state['G'] += 0.15;
                break;
            case 'reading':
                $state['D'] += 0.2;
                $state['A'] += 0.1;
                break;
        }
        return $state;
    }
    
    /**
     * 상태 벡터 정규화 (확률 총합 = 1)
     */
    private function normalizeStateVector(array $state): array
    {
        $sum = 0;
        foreach ($state as $value) {
            $sum += $value * $value;
        }
        $norm = sqrt($sum);
        
        if ($norm == 0) $norm = 1;
        
        foreach ($state as $key => $value) {
            $state[$key] = round($value / $norm, 4);
        }
        
        return $state;
    }
    
    /**
     * 상태 벡터에서 확률 분포 계산
     * |진폭|² = 확률
     */
    public function calculateProbabilities(array $stateVector): array
    {
        $probabilities = [];
        $total = 0;
        
        foreach ($stateVector as $key => $amplitude) {
            $prob = $amplitude * $amplitude;
            $probabilities[$key] = $prob;
            $total += $prob;
        }
        
        // 정규화
        if ($total > 0) {
            foreach ($probabilities as $key => $prob) {
                $probabilities[$key] = round($prob / $total, 4);
            }
        }
        
        return $probabilities;
    }
    
    // ============================================================
    // SECTION 2: 감쇠 진동 모델 (Damped Oscillation)
    // ============================================================
    
    /**
     * 시간에 따른 시너지/역효과 확률 계산
     * 감쇠 진동 모델을 적용하여 학생의 심리 상태 변화 예측
     *
     * @param float $studentResilience 학생 회복탄력성 (0~1)
     * @param float $problemDifficulty 문제 난이도 (0~1)
     * @param int $elapsedSeconds 경과 시간 (초)
     * @return array [synergy, backfire, golden_time]
     */
    public function calculateLearningDynamics(
        float $studentResilience, 
        float $problemDifficulty, 
        int $elapsedSeconds
    ): array {
        try {
            // 파라미터 매핑
            // omega: 인지 진동수 (난이도가 높으면 마음이 급함)
            $omega = 2 * M_PI * (0.1 + $problemDifficulty * 0.2);
            
            // gamma: 감쇠율 (탄력성이 낮으면 빠르게 포기)
            $gamma = 0.05 * (1.5 - $studentResilience);
            
            $t = $elapsedSeconds;
            
            // 시너지 확률 (의욕) - 감쇠 진동 모델
            // 초기값 1.0(의욕 충만)에서 시작하여 시간에 따라 감소 및 진동
            $waveFactor = cos($omega * $t);
            $decayFactor = exp(-$gamma * $t);
            $synergy = 0.5 * (1 + $waveFactor * $decayFactor);
            
            // 역효과 확률 (포기/짜증)
            // 시간이 갈수록 불안감이 스멀스멀 올라옴
            $anxietyRate = 0.01;
            $backfire = (1 - $synergy) + ($anxietyRate * $t);
            $backfire = min($backfire, 1.0);
            
            // 골든 타임 찾기 (시너지보다 역효과가 커지는 첫 지점)
            $goldenTime = $this->findGoldenTime($studentResilience, $problemDifficulty);
            
            return [
                'synergy' => round($synergy, 4),
                'backfire' => round($backfire, 4),
                'golden_time' => $goldenTime,
                'elapsed' => $elapsedSeconds,
                'omega' => round($omega, 4),
                'gamma' => round($gamma, 4),
                'should_intervene' => ($backfire > $synergy) || ($elapsedSeconds >= $goldenTime - 5)
            ];
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] calculateLearningDynamics error at {$this->currentFile}:" . $e->getLine());
            return [
                'synergy' => 0.5,
                'backfire' => 0.5,
                'golden_time' => 60,
                'elapsed' => $elapsedSeconds,
                'should_intervene' => false
            ];
        }
    }
    
    /**
     * 골든 타임 계산 (역효과가 시너지를 압도하기 직전 시점)
     */
    private function findGoldenTime(float $resilience, float $difficulty, int $maxTime = 300): int
    {
        $omega = 2 * M_PI * (0.1 + $difficulty * 0.2);
        $gamma = 0.05 * (1.5 - $resilience);
        
        for ($t = 1; $t <= $maxTime; $t++) {
            $synergy = 0.5 * (1 + cos($omega * $t) * exp(-$gamma * $t));
            $backfire = (1 - $synergy) + (0.01 * $t);
            
            if ($backfire >= $synergy) {
                return $t;
            }
        }
        
        return $maxTime;
    }
    
    // ============================================================
    // SECTION 3: 환경 연산자 (Context Operator)
    // ============================================================
    
    /**
     * 환경 변수에 따른 상태 벡터 변환
     * 시간 압박, 피로도 등의 외부 자극에 의해 페르소나 벡터가 회전
     *
     * @param array $stateVector 현재 상태 벡터
     * @param float $timePressure 시간 압박 (0~1)
     * @param float $fatigue 피로도 (0~1)
     * @param float $emotionScore 감정 점수 (-1~1, 양수=긍정)
     * @return array 변환된 상태 벡터
     */
    public function applyContextOperator(
        array $stateVector, 
        float $timePressure = 0, 
        float $fatigue = 0,
        float $emotionScore = 0
    ): array {
        try {
            // 시간 압박: Diver → Sprinter 전이
            $shift = $timePressure * 0.5;
            $stateVector['S'] += $shift * ($stateVector['D'] * 0.3);
            $stateVector['D'] -= $shift * 0.3;
            
            // 피로도: Gamer(단순 보상 추구) 증가
            $stateVector['G'] += $fatigue * 0.2;
            $stateVector['A'] -= $fatigue * 0.1;
            
            // 감정: 긍정이면 전반적 활성화, 부정이면 Architect(안전 추구) 증가
            if ($emotionScore > 0) {
                $stateVector['S'] += $emotionScore * 0.1;
                $stateVector['G'] += $emotionScore * 0.1;
            } else {
                $stateVector['A'] += abs($emotionScore) * 0.2;
            }
            
            return $this->normalizeStateVector($stateVector);
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] applyContextOperator error at {$this->currentFile}:" . $e->getLine());
            return $stateVector;
        }
    }
    
    /**
     * 간섭 효과 적용 (Interference Effect)
     * 감정과 피로도의 파동이 겹쳐 보강/상쇄 간섭 발생
     *
     * @param float $emotionScore 감정 점수 (0~1)
     * @param float $fatigueScore 피로도 점수 (0~1)
     * @return array 간섭 결과
     */
    public function applyInterference(float $emotionScore, float $fatigueScore): array
    {
        // 감정이 좋으면 위상이 +방향, 피로하면 -방향
        $theta = ($emotionScore * M_PI) - ($fatigueScore * M_PI);
        
        // 결합 에너지 계산 (보강 간섭 vs 상쇄 간섭)
        $constructive = cos($theta); // 보강 간섭 계수 (-1 ~ 1)
        $amplitude = sqrt(pow($emotionScore, 2) + pow($fatigueScore, 2) + 2 * $emotionScore * $fatigueScore * cos($theta));
        
        $interferenceType = ($constructive > 0.3) ? 'constructive' : 
                           (($constructive < -0.3) ? 'destructive' : 'neutral');
        
        return [
            'theta' => round($theta, 4),
            'amplitude' => round($amplitude, 4),
            'constructive_factor' => round($constructive, 4),
            'interference_type' => $interferenceType,
            'recommendation' => $this->getInterferenceRecommendation($interferenceType, $amplitude)
        ];
    }
    
    /**
     * 간섭 유형에 따른 추천 전략
     */
    private function getInterferenceRecommendation(string $type, float $amplitude): string
    {
        switch ($type) {
            case 'constructive':
                return $amplitude > 1.2 
                    ? "학습 에너지가 증폭된 상태입니다. 도전적인 문제를 제시하세요."
                    : "긍정적 모멘텀이 있습니다. 현재 흐름을 유지하세요.";
            case 'destructive':
                return $amplitude < 0.5
                    ? "학습 효율이 0에 가깝습니다. 즉시 휴식을 권장합니다."
                    : "피로와 감정이 상쇄 중입니다. 짧은 휴식 후 재개하세요.";
            default:
                return "안정적인 상태입니다. 일반적인 학습 진행이 가능합니다.";
        }
    }
    
    // ============================================================
    // SECTION 4: 페르소나 측정 및 붕괴 (Measurement & Collapse)
    // ============================================================
    
    /**
     * 현재 지배적 페르소나 측정 (관측)
     * 상태 벡터에서 확률이 가장 높은 페르소나를 반환
     *
     * @param array $stateVector 상태 벡터
     * @return array 측정 결과
     */
    public function measurePersona(array $stateVector): array
    {
        $probabilities = $this->calculateProbabilities($stateVector);
        
        // 지배적 페르소나 찾기
        $dominantKey = array_keys($probabilities, max($probabilities))[0];
        $dominantProb = $probabilities[$dominantKey];
        
        // 중첩 상태 분석
        $superpositionLevel = $this->calculateSuperpositionLevel($probabilities);
        
        return [
            'dominant_persona' => $dominantKey,
            'dominant_name' => self::PERSONA_BASIS[$dominantKey]['name'],
            'dominant_icon' => self::PERSONA_BASIS[$dominantKey]['icon'],
            'dominant_probability' => round($dominantProb, 4),
            'all_probabilities' => $probabilities,
            'superposition_level' => $superpositionLevel,
            'state_description' => $this->describeState($probabilities, $superpositionLevel),
            'ai_response_strategy' => $this->getAIResponseStrategy($dominantKey)
        ];
    }
    
    /**
     * 중첩 수준 계산 (엔트로피 기반)
     * 확률이 균등할수록 높은 중첩 상태
     */
    private function calculateSuperpositionLevel(array $probabilities): string
    {
        $maxProb = max($probabilities);
        
        if ($maxProb > 0.7) return 'collapsed';      // 거의 확정된 상태
        if ($maxProb > 0.5) return 'partial';        // 부분 중첩
        if ($maxProb > 0.35) return 'superposed';    // 완전 중첩
        return 'highly_superposed';                  // 고도 중첩 (불확실)
    }
    
    /**
     * 상태 설명 생성
     */
    private function describeState(array $probabilities, string $level): string
    {
        arsort($probabilities);
        $top = array_slice($probabilities, 0, 2, true);
        $keys = array_keys($top);
        
        switch ($level) {
            case 'collapsed':
                return self::PERSONA_BASIS[$keys[0]]['name'] . " 성향이 확정적입니다.";
            case 'partial':
                return self::PERSONA_BASIS[$keys[0]]['name'] . " 성향이 우세하지만, " . 
                       self::PERSONA_BASIS[$keys[1]]['name'] . " 성향도 있습니다.";
            case 'superposed':
                return self::PERSONA_BASIS[$keys[0]]['name'] . "과 " . 
                       self::PERSONA_BASIS[$keys[1]]['name'] . " 사이에서 요동치고 있습니다.";
            default:
                return "학생의 상태가 매우 불확실합니다. 더 많은 관측이 필요합니다.";
        }
    }
    
    /**
     * AI 대응 전략 반환
     */
    private function getAIResponseStrategy(string $persona): string
    {
        $strategies = [
            'S' => "속도감 있는 숏폼 퀴즈 제공 (설명 최소화)",
            'D' => "심층 개념 유도 질문 & Why 기법 사용",
            'G' => "연속 정답 콤보 보상 & 랭킹 자극",
            'A' => "전체 로드맵 보여주기 & 안정감 제공"
        ];
        return $strategies[$persona] ?? "균형 잡힌 접근 유지";
    }
    
    // ============================================================
    // SECTION 5: 페르소나 스위칭 경로 최적화
    // ============================================================
    
    /**
     * 최적 페르소나 스위칭 경로 계산
     * Dijkstra 알고리즘을 사용하여 심리적 저항이 최소인 경로 탐색
     *
     * @param string $currentPersona 현재 페르소나
     * @param string $targetPersona 목표 페르소나
     * @return array 최적 경로 및 개입 스크립트
     */
    public function getOptimalSwitchingPath(string $currentPersona, string $targetPersona): array
    {
        try {
            $path = $this->dijkstraPath($currentPersona, $targetPersona);
            $totalCost = $this->calculatePathCost($path);
            $script = $this->generateInteractionScript($path);
            
            return [
                'current' => $currentPersona,
                'target' => $targetPersona,
                'path' => $path,
                'path_names' => array_map(function($p) {
                    return self::PERSONA_BASIS[$p]['name'];
                }, $path),
                'total_cost' => $totalCost,
                'difficulty' => $this->getCostLevel($totalCost),
                'interaction_script' => $script,
                'estimated_time' => $totalCost * 30 // 비용 1당 약 30초
            ];
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] getOptimalSwitchingPath error at {$this->currentFile}:" . $e->getLine());
            return [
                'current' => $currentPersona,
                'target' => $targetPersona,
                'path' => [$currentPersona, $targetPersona],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Dijkstra 최단 경로 알고리즘
     */
    private function dijkstraPath(string $start, string $end): array
    {
        $personas = array_keys(self::PERSONA_BASIS);
        $distances = [];
        $previous = [];
        $queue = [];
        
        foreach ($personas as $p) {
            $distances[$p] = ($p === $start) ? 0 : PHP_INT_MAX;
            $previous[$p] = null;
            $queue[$p] = $distances[$p];
        }
        
        while (!empty($queue)) {
            asort($queue);
            $current = key($queue);
            unset($queue[$current]);
            
            if ($current === $end) break;
            
            foreach ($personas as $neighbor) {
                if (!isset($queue[$neighbor])) continue;
                
                $cost = self::TRANSITION_COSTS[$current][$neighbor];
                $alt = $distances[$current] + $cost;
                
                if ($alt < $distances[$neighbor]) {
                    $distances[$neighbor] = $alt;
                    $previous[$neighbor] = $current;
                    $queue[$neighbor] = $alt;
                }
            }
        }
        
        // 경로 재구성
        $path = [];
        $current = $end;
        while ($current !== null) {
            array_unshift($path, $current);
            $current = $previous[$current];
        }
        
        return $path;
    }
    
    /**
     * 경로 총 비용 계산
     */
    private function calculatePathCost(array $path): int
    {
        $cost = 0;
        for ($i = 0; $i < count($path) - 1; $i++) {
            $cost += self::TRANSITION_COSTS[$path[$i]][$path[$i + 1]];
        }
        return $cost;
    }
    
    /**
     * 비용 수준 판정
     */
    private function getCostLevel(int $cost): string
    {
        if ($cost <= 2) return '쉬움';
        if ($cost <= 4) return '보통';
        if ($cost <= 6) return '어려움';
        return '매우 어려움';
    }
    
    /**
     * 상호작용 스크립트 생성
     */
    private function generateInteractionScript(array $path): array
    {
        $script = [];
        $transitions = [
            'S→G' => "🎮 [도전장] '이 문제, 전교생의 80%가 틀렸어. 넌 맞출 수 있을까?'",
            'G→D' => "🤿 [힌트 탐색] '이기려면 무기가 필요해. 개념노트에 숨겨진 공식을 찾아봐.'",
            'D→A' => "🏛️ [조망] '이 문제는 전체 숲에서 보면 작은 나무일 뿐이야. 위치를 확인해보자.'",
            'A→S' => "⚡ [실행] '전략은 섰으니 이제 질주할 차례야. 5분 타임어택!'",
            'S→D' => "🤿 [함정 찾기] '함정은 부등호 방향에 숨어 있어. 개념노트 3줄 요약만 확인하면 보여.'",
            'D→G' => "🎮 [승부욕] '다 이해했으니, 이제 실력으로 증명할 차례야. 연속 정답 도전!'",
            'G→A' => "🏛️ [세이브포인트] '지금까지 얻은 점수가 상위 10%야. 저장(복습)하고 갈래?'",
            'A→D' => "🤿 [깊은 이해] '계획은 완벽해. 이제 왜 이렇게 되는지 파헤쳐볼까?'",
            'S→A' => "🏛️ [전략 구상] '급하게 풀기 전에, 전체 그림을 한번 보자. 어떤 순서가 좋을까?'",
            'G→S' => "⚡ [스피드런] '보상은 충분해. 이제 기록 단축에 도전해볼까?'"
        ];
        
        for ($i = 0; $i < count($path) - 1; $i++) {
            $from = $path[$i];
            $to = $path[$i + 1];
            $key = "{$from}→{$to}";
            
            if (isset($transitions[$key])) {
                $script[] = [
                    'step' => $i + 1,
                    'from' => self::PERSONA_BASIS[$from]['name'],
                    'to' => self::PERSONA_BASIS[$to]['name'],
                    'message' => $transitions[$key]
                ];
            }
        }
        
        return $script;
    }
    
    // ============================================================
    // SECTION 6: 실시간 상태 추적 (필기 데이터 기반)
    // ============================================================
    
    /**
     * 실시간 펜 데이터 분석
     * 필기 속도, 멈춤, 떨림, 엔트로피를 분석하여 상태 업데이트
     *
     * @param float $velocity 필기 속도 (0~3, 1이 정상)
     * @param float $pauseDuration 멈춤 시간 (초)
     * @param float $jitterScore 떨림 점수 (0~1)
     * @param float $entropyScore 엔트로피 점수 (0~1)
     * @param array $currentState 현재 상태
     * @return array 업데이트된 상태
     */
    public function analyzeStrokeData(
        float $velocity,
        float $pauseDuration,
        float $jitterScore,
        float $entropyScore,
        array $currentState
    ): array {
        try {
            // 동적 감쇠율 계산
            $baseGamma = 0.05;
            $dynamicGamma = $baseGamma + (0.5 * $pauseDuration) + (1.2 * $jitterScore);
            
            // 인지 진동수 계산 (속도가 적당하면 몰입)
            $omega = ($velocity > 0.5 && $velocity < 1.5) ? 1.0 : 0.5;
            
            // 상태 분석
            $analysis = [
                'dynamic_gamma' => round($dynamicGamma, 4),
                'omega' => $omega,
                'flow_state' => ($velocity > 0.5 && $velocity < 1.5 && $pauseDuration < 1 && $jitterScore < 0.2),
                'panic_state' => ($velocity > 2.0 || $jitterScore > 0.7 || $entropyScore > 0.8),
                'freeze_state' => ($velocity < 0.1 && $pauseDuration > 2),
                'recommended_action' => $this->getStrokeRecommendation($velocity, $pauseDuration, $jitterScore, $entropyScore)
            ];
            
            // 상태에 따른 페르소나 벡터 조정
            if ($analysis['panic_state']) {
                $currentState['S'] += 0.3;  // 급해짐
                $currentState['A'] -= 0.2;  // 계획 붕괴
            } elseif ($analysis['freeze_state']) {
                $currentState['D'] += 0.2;  // 생각 중이거나
                $currentState['A'] += 0.2;  // 회피 중
            } elseif ($analysis['flow_state']) {
                // 현재 상태 유지 (몰입 보호)
            }
            
            $analysis['updated_state'] = $this->normalizeStateVector($currentState);
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] analyzeStrokeData error at {$this->currentFile}:" . $e->getLine());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 필기 데이터 기반 추천
     */
    private function getStrokeRecommendation(float $v, float $p, float $j, float $e): string
    {
        if ($v > 2.0 && $j > 0.5) {
            return "🚨 패닉 상태 감지. 즉시 진정 개입이 필요합니다.";
        }
        if ($v < 0.1 && $p > 3) {
            return "⚠️ 인지적 교착 상태. 작은 힌트를 제공하세요.";
        }
        if ($e > 0.8) {
            return "📝 필기 엔트로피 높음. 심호흡 루틴을 제안하세요.";
        }
        if ($v > 0.5 && $v < 1.5 && $j < 0.2) {
            return "✅ 몰입 상태. 개입을 자제하세요.";
        }
        return "👀 관망 상태. 변화를 지켜보세요.";
    }
    
    // ============================================================
    // SECTION 7: 데이터베이스 연동
    // ============================================================
    
    /**
     * 학생의 양자 상태 저장
     */
    public function saveQuantumState(int $userId, array $stateData): bool
    {
        global $DB;
        
        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->state_vector = json_encode($stateData['state_vector'] ?? []);
            $record->probabilities = json_encode($stateData['probabilities'] ?? []);
            $record->dominant_persona = $stateData['dominant_persona'] ?? '';
            $record->superposition_level = $stateData['superposition_level'] ?? '';
            $record->synergy = $stateData['synergy'] ?? 0;
            $record->backfire = $stateData['backfire'] ?? 0;
            $record->golden_time = $stateData['golden_time'] ?? 0;
            $record->context_data = json_encode($stateData['context'] ?? []);
            $record->created_at = time();
            
            $DB->insert_record('at_quantum_state', $record);
            return true;
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] saveQuantumState error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 학생의 최근 양자 상태 조회
     */
    public function getRecentQuantumState(int $userId): ?array
    {
        global $DB;
        
        try {
            $sql = "SELECT * FROM {at_quantum_state} 
                    WHERE user_id = ? AND agent_id = ?
                    ORDER BY created_at DESC LIMIT 1";
            
            $record = $DB->get_record_sql($sql, [$userId, $this->agentId]);
            
            if (!$record) return null;
            
            return [
                'id' => $record->id,
                'state_vector' => json_decode($record->state_vector, true),
                'probabilities' => json_decode($record->probabilities, true),
                'dominant_persona' => $record->dominant_persona,
                'superposition_level' => $record->superposition_level,
                'synergy' => (float)$record->synergy,
                'backfire' => (float)$record->backfire,
                'golden_time' => (int)$record->golden_time,
                'context' => json_decode($record->context_data, true),
                'created_at' => $record->created_at
            ];
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] getRecentQuantumState error at {$this->currentFile}:" . $e->getLine());
            return null;
        }
    }
    
    /**
     * 학생의 양자 상태 히스토리 조회
     */
    public function getQuantumStateHistory(int $userId, int $limit = 20): array
    {
        global $DB;
        
        try {
            $sql = "SELECT * FROM {at_quantum_state} 
                    WHERE user_id = ? AND agent_id = ?
                    ORDER BY created_at DESC LIMIT ?";
            
            $records = $DB->get_records_sql($sql, [$userId, $this->agentId, $limit]);
            
            $history = [];
            foreach ($records as $record) {
                $history[] = [
                    'id' => $record->id,
                    'state_vector' => json_decode($record->state_vector, true),
                    'dominant_persona' => $record->dominant_persona,
                    'synergy' => (float)$record->synergy,
                    'backfire' => (float)$record->backfire,
                    'created_at' => date('Y-m-d H:i:s', $record->created_at)
                ];
            }
            
            return $history;
            
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] getQuantumStateHistory error at {$this->currentFile}:" . $e->getLine());
            return [];
        }
    }
    
    // ============================================================
    // SECTION 8: 엔진 정보 및 유틸리티
    // ============================================================
    
    /**
     * 엔진 정보 반환
     */
    public function getEngineInfo(): array
    {
        return [
            'name' => 'QuantumPersonaEngine',
            'version' => $this->version,
            'agent_id' => $this->agentId,
            'basis_personas' => array_keys(self::PERSONA_BASIS),
            'features' => [
                'state_vector_modeling',
                'damped_oscillation',
                'interference_calculation',
                'optimal_path_finding',
                'stroke_data_analysis'
            ]
        ];
    }
    
    /**
     * 전체 시뮬레이션 실행
     */
    public function runFullSimulation(int $userId, array $context = []): array
    {
        // 1. 초기 상태 생성
        $stateVector = $this->initializeStateVector($context['onboarding'] ?? []);
        
        // 2. 환경 연산자 적용
        if (isset($context['time_pressure']) || isset($context['fatigue'])) {
            $stateVector = $this->applyContextOperator(
                $stateVector,
                $context['time_pressure'] ?? 0,
                $context['fatigue'] ?? 0,
                $context['emotion'] ?? 0
            );
        }
        
        // 3. 페르소나 측정
        $measurement = $this->measurePersona($stateVector);
        
        // 4. 학습 역학 계산
        $dynamics = $this->calculateLearningDynamics(
            $context['resilience'] ?? 0.5,
            $context['difficulty'] ?? 0.5,
            $context['elapsed'] ?? 0
        );
        
        // 5. 간섭 효과 계산
        $interference = $this->applyInterference(
            $context['emotion'] ?? 0.5,
            $context['fatigue'] ?? 0.5
        );
        
        // 6. 결과 조합
        $result = [
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'state_vector' => $stateVector,
            'measurement' => $measurement,
            'dynamics' => $dynamics,
            'interference' => $interference,
            'recommendation' => $this->generateRecommendation($measurement, $dynamics, $interference)
        ];
        
        // 7. 상태 저장
        $this->saveQuantumState($userId, array_merge($result, [
            'synergy' => $dynamics['synergy'],
            'backfire' => $dynamics['backfire'],
            'golden_time' => $dynamics['golden_time'],
            'context' => $context
        ]));
        
        return $result;
    }
    
    /**
     * 종합 추천 생성
     */
    private function generateRecommendation(array $measurement, array $dynamics, array $interference): array
    {
        $urgency = 'normal';
        $actions = [];

        // 긴급 상황 체크
        if ($dynamics['should_intervene']) {
            $urgency = 'high';
            $actions[] = "⚠️ 골든 타임 임박. 즉시 개입을 권장합니다.";
        }

        if ($interference['interference_type'] === 'destructive') {
            $urgency = 'critical';
            $actions[] = "🚨 상쇄 간섭 발생. 학습 중단 및 휴식이 필요합니다.";
        }

        // 페르소나 기반 추천
        $actions[] = $measurement['ai_response_strategy'];

        // 중첩 상태 기반 추천
        if ($measurement['superposition_level'] === 'highly_superposed') {
            $actions[] = "🎯 학생 상태가 불확실합니다. 간단한 확인 질문으로 상태를 측정하세요.";
        }

        return [
            'urgency' => $urgency,
            'actions' => $actions,
            'summary' => $measurement['state_description']
        ];
    }

    // ============================================================
    // SECTION 9: Rule-Quantum Bridge Integration
    // 22개 에이전트의 rules.yaml을 양자 파동함수와 연결
    // Phase 1: Agent04-centric expansion
    // ============================================================

    /** @var object RuleYamlLoader 인스턴스 */
    private $ruleLoader = null;

    /** @var object RuleToWaveMapper 인스턴스 */
    private $waveMapper = null;

    /**
     * 4-Layer Probability Weights (HYBRID 방식)
     */
    const LAYER_WEIGHTS = [
        'layer1_rule' => 0.25,    // Rule Confidence
        'layer2_wave' => 0.35,    // Wave Function Probability
        'layer3_corr' => 0.25,    // Correlation Influence
        'layer4_bias' => 0.15     // Bias (contextual adjustment)
    ];

    /**
     * Intervention Decision Thresholds
     */
    const INTERVENTION_THRESHOLDS = [
        'IMMEDIATE_INTERVENTION' => 0.9,   // P ≥ 0.9: 100% 실행
        'PROBABILISTIC_GATING' => 0.7,     // 0.7 ≤ P < 0.9: P 확률로 실행
        'WEIGHT_ADJUSTMENT' => 0.5,        // 0.5 ≤ P < 0.7: 가중치 조정 후 대기
        'OBSERVE_ONLY' => 0.0              // P < 0.5: 관찰만
    ];

    /**
     * 4D → 8D StateVector 변환 매핑
     * 4D Persona (S,D,G,A) → 8D Quantum State
     */
    const PERSONA_TO_8D_MAPPING = [
        'S' => [ // Sprinter: 속도, 직관
            'cognitive_clarity' => 0.6,
            'emotional_stability' => 0.5,
            'attention_level' => 0.8,
            'motivation_strength' => 0.7,
            'energy_level' => 0.9,
            'social_connection' => 0.5,
            'creative_flow' => 0.6,
            'learning_momentum' => 0.9
        ],
        'D' => [ // Diver: 깊이, 완벽
            'cognitive_clarity' => 0.9,
            'emotional_stability' => 0.7,
            'attention_level' => 0.9,
            'motivation_strength' => 0.6,
            'energy_level' => 0.5,
            'social_connection' => 0.4,
            'creative_flow' => 0.8,
            'learning_momentum' => 0.5
        ],
        'G' => [ // Gamer: 보상, 경쟁
            'cognitive_clarity' => 0.5,
            'emotional_stability' => 0.6,
            'attention_level' => 0.7,
            'motivation_strength' => 0.9,
            'energy_level' => 0.8,
            'social_connection' => 0.8,
            'creative_flow' => 0.5,
            'learning_momentum' => 0.7
        ],
        'A' => [ // Architect: 계획, 안정
            'cognitive_clarity' => 0.8,
            'emotional_stability' => 0.9,
            'attention_level' => 0.7,
            'motivation_strength' => 0.5,
            'energy_level' => 0.6,
            'social_connection' => 0.6,
            'creative_flow' => 0.7,
            'learning_momentum' => 0.6
        ]
    ];

    /**
     * Rule-Quantum Bridge 초기화
     * RuleYamlLoader와 RuleToWaveMapper 로드
     *
     * @return bool 초기화 성공 여부
     */
    public function initializeBridge(): bool
    {
        try {
            $sharedQuantumPath = dirname(dirname(dirname(__DIR__))) . '/shared/quantum/';

            // RuleYamlLoader 로드
            $loaderPath = $sharedQuantumPath . 'RuleYamlLoader.php';
            if (!file_exists($loaderPath)) {
                error_log("[QuantumPersonaEngine] RuleYamlLoader not found at {$loaderPath} - File: {$this->currentFile}, Line: " . __LINE__);
                return false;
            }
            require_once($loaderPath);
            $this->ruleLoader = new RuleYamlLoader();

            // RuleToWaveMapper 로드
            $mapperPath = $sharedQuantumPath . 'RuleToWaveMapper.php';
            if (!file_exists($mapperPath)) {
                error_log("[QuantumPersonaEngine] RuleToWaveMapper not found at {$mapperPath} - File: {$this->currentFile}, Line: " . __LINE__);
                return false;
            }
            require_once($mapperPath);
            $this->waveMapper = new RuleToWaveMapper();

            return true;

        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] initializeBridge error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return false;
        }
    }

    /**
     * 4D StateVector를 8D StateVector로 변환
     *
     * @param array $stateVector4D [S, D, G, A] 확률 진폭
     * @return array 8D StateVector
     */
    public function convert4Dto8D(array $stateVector4D): array
    {
        $state8D = [
            'cognitive_clarity' => 0.0,
            'emotional_stability' => 0.0,
            'attention_level' => 0.0,
            'motivation_strength' => 0.0,
            'energy_level' => 0.0,
            'social_connection' => 0.0,
            'creative_flow' => 0.0,
            'learning_momentum' => 0.0
        ];

        // 4D 확률 기반 8D 가중 평균 계산
        $probabilities = $this->calculateProbabilities($stateVector4D);

        foreach (self::PERSONA_TO_8D_MAPPING as $persona => $mapping) {
            $weight = $probabilities[$persona] ?? 0.25;
            foreach ($mapping as $dimension => $value) {
                $state8D[$dimension] += $weight * $value;
            }
        }

        return $state8D;
    }

    /**
     * 특정 에이전트의 규칙을 로드하고 파동 파라미터로 변환
     *
     * @param int $agentId 에이전트 ID (1-22)
     * @return array 변환된 파동 파라미터 배열
     */
    public function loadAgentWaveParams(int $agentId): array
    {
        if (!$this->ruleLoader || !$this->waveMapper) {
            if (!$this->initializeBridge()) {
                return ['error' => 'Bridge initialization failed', 'file' => $this->currentFile, 'line' => __LINE__];
            }
        }

        try {
            return $this->waveMapper->mapAgentRulesToWaves($agentId);
        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] loadAgentWaveParams error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 4-Layer 확률 계산 (HYBRID 방식)
     *
     * Layer 1: P_rule = confidence × (priority/100) × condition_match
     * Layer 2: P_wave = |⟨ψ_agent|ψ_target⟩|²
     * Layer 3: P_corr = Σ(C_ij × P_j) / 21 (향후 구현)
     * Layer 4: P_final = sigmoid(weighted_sum)
     *
     * @param array $rule 규칙 데이터
     * @param array $waveParams 파동 파라미터
     * @param array $state8D 현재 8D 상태 벡터
     * @param int $agentId 에이전트 ID
     * @return array 4-Layer 확률 계산 결과
     */
    public function calculate4LayerProbability(array $rule, array $waveParams, array $state8D, int $agentId = 4): array
    {
        try {
            // Layer 1: Rule Confidence
            $layer1 = $this->calculateLayer1($rule);

            // Layer 2: Wave Function Probability
            $layer2 = $this->calculateLayer2($waveParams, $state8D);

            // Layer 3: Correlation Influence (Phase 2에서 구현, 현재는 기본값)
            $layer3 = $this->calculateLayer3($agentId);

            // Layer 4: Final HYBRID probability
            $layer4 = $this->calculateLayer4Final($layer1, $layer2, $layer3);

            // 개입 유형 결정
            $interventionType = $this->determineInterventionType($layer4);

            return [
                'layer1_rule_conf' => round($layer1, 5),
                'layer2_wave_prob' => round($layer2, 5),
                'layer3_corr_inf' => round($layer3, 5),
                'layer4_final' => round($layer4, 5),
                'intervention_type' => $interventionType,
                'should_execute' => $this->shouldExecuteIntervention($layer4, $interventionType),
                'wave_params' => $waveParams,
                'state_vector' => $state8D,
                'rule_id' => $rule['id'] ?? 'unknown',
                'agent_id' => $agentId
            ];

        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] calculate4LayerProbability error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return [
                'error' => $e->getMessage(),
                'layer1_rule_conf' => 0,
                'layer2_wave_prob' => 0,
                'layer3_corr_inf' => 0,
                'layer4_final' => 0,
                'intervention_type' => 'OBSERVE_ONLY'
            ];
        }
    }

    /**
     * Layer 1: Rule Confidence 계산
     * P_rule = confidence × (priority/100) × condition_match
     */
    private function calculateLayer1(array $rule): float
    {
        $confidence = floatval($rule['confidence'] ?? 0.8);
        $priority = intval($rule['priority'] ?? 50);

        // 조건 일치 비율 (간단 구현)
        $conditionMatch = 1.0;
        if (!empty($rule['conditions'])) {
            // 실제로는 조건을 평가해야 하지만, 현재는 기본값 사용
            $conditionMatch = 0.8;
        }

        return $confidence * ($priority / 100) * $conditionMatch;
    }

    /**
     * Layer 2: Wave Function Probability 계산
     * P_wave = |⟨ψ_agent|ψ_target⟩|² (내적의 제곱)
     */
    private function calculateLayer2(array $waveParams, array $state8D): float
    {
        // 파동 파라미터에서 주요 값 추출
        $amplitude = floatval($waveParams['psi_core']['amplitude'] ?? 0.5);
        $phase = floatval($waveParams['psi_align']['phase'] ?? 0);
        $coherence = floatval($waveParams['psi_align']['coherence'] ?? 0.5);

        // 8D 상태에서 관련 차원 추출
        $cognitiveClarity = $state8D['cognitive_clarity'] ?? 0.5;
        $attentionLevel = $state8D['attention_level'] ?? 0.5;
        $learningMomentum = $state8D['learning_momentum'] ?? 0.5;

        // 내적 계산 (간소화된 양자 내적)
        $dotProduct = $amplitude * $cognitiveClarity * cos($phase) +
                      $coherence * $attentionLevel +
                      $amplitude * $learningMomentum * sin($phase);

        // 확률 = |내적|²
        $probability = pow($dotProduct, 2);

        // 0-1 범위로 정규화
        return min(max($probability / 3, 0), 1);
    }

    /**
     * Layer 3: Correlation Influence 계산
     * P_corr = Σ(C_ij × P_j) / 21
     *
     * Phase 2에서 22x22 상관 매트릭스 구현 예정
     * 현재는 기본값 반환
     */
    private function calculateLayer3(int $agentId): float
    {
        // Phase 2에서 구현 예정
        // 현재는 에이전트 ID 기반 기본값

        // Agent04 (inspect_weakpoints)는 기본 상관 영향력 0.5
        $baseCorrelation = 0.5;

        // 에이전트 클러스터에 따른 보정 (예정된 클러스터 분석 결과 기반)
        // Cluster 2: 개념-학습 (Agent 04, 10, 11, 15) - 높은 상관관계
        if (in_array($agentId, [4, 10, 11, 15])) {
            $baseCorrelation = 0.7;
        }
        // Cluster 1: 감정-동기 (Agent 05, 08, 13)
        elseif (in_array($agentId, [5, 8, 13])) {
            $baseCorrelation = 0.6;
        }

        return $baseCorrelation;
    }

    /**
     * Layer 4: Final HYBRID Probability 계산
     * P_final = sigmoid(0.25×P_rule + 0.35×P_wave + 0.25×P_corr + bias)
     */
    private function calculateLayer4Final(float $layer1, float $layer2, float $layer3, float $bias = 0.0): float
    {
        $weightedSum = self::LAYER_WEIGHTS['layer1_rule'] * $layer1 +
                       self::LAYER_WEIGHTS['layer2_wave'] * $layer2 +
                       self::LAYER_WEIGHTS['layer3_corr'] * $layer3 +
                       self::LAYER_WEIGHTS['layer4_bias'] * $bias;

        // Sigmoid 함수 적용
        // 입력을 -6 ~ 6 범위로 스케일링 (더 민감한 출력)
        $scaledInput = ($weightedSum - 0.5) * 12;

        return 1 / (1 + exp(-$scaledInput));
    }

    /**
     * 개입 유형 결정
     */
    private function determineInterventionType(float $probability): string
    {
        if ($probability >= self::INTERVENTION_THRESHOLDS['IMMEDIATE_INTERVENTION']) {
            return 'IMMEDIATE_INTERVENTION';
        }
        if ($probability >= self::INTERVENTION_THRESHOLDS['PROBABILISTIC_GATING']) {
            return 'PROBABILISTIC_GATING';
        }
        if ($probability >= self::INTERVENTION_THRESHOLDS['WEIGHT_ADJUSTMENT']) {
            return 'WEIGHT_ADJUSTMENT';
        }
        return 'OBSERVE_ONLY';
    }

    /**
     * 개입 실행 여부 결정 (확률적 게이팅 포함)
     */
    private function shouldExecuteIntervention(float $probability, string $interventionType): bool
    {
        switch ($interventionType) {
            case 'IMMEDIATE_INTERVENTION':
                return true; // 100% 실행

            case 'PROBABILISTIC_GATING':
                // P 확률로 실행
                return (mt_rand() / mt_getrandmax()) < $probability;

            case 'WEIGHT_ADJUSTMENT':
                return false; // 실행하지 않고 가중치만 조정

            case 'OBSERVE_ONLY':
            default:
                return false;
        }
    }

    /**
     * Rule-Quantum 상태를 DB에 저장
     *
     * @param int $studentId 학생 ID
     * @param string $sessionId 세션 ID
     * @param array $layerResult 4-Layer 확률 계산 결과
     * @return int|bool 삽입된 레코드 ID 또는 false
     */
    public function saveRuleQuantumState(int $studentId, string $sessionId, array $layerResult)
    {
        global $DB;

        try {
            $record = new stdClass();
            $record->studentid = $studentId;
            $record->sessionid = $sessionId;
            $record->agentid = intval($layerResult['agent_id'] ?? 4);
            $record->ruleid = $layerResult['rule_id'] ?? 'unknown';

            // 4-Layer 값
            $record->layer1_rule_conf = $layerResult['layer1_rule_conf'] ?? 0;
            $record->layer2_wave_prob = $layerResult['layer2_wave_prob'] ?? 0;
            $record->layer3_corr_inf = $layerResult['layer3_corr_inf'] ?? 0;
            $record->layer4_final = $layerResult['layer4_final'] ?? 0;

            // JSON 필드
            $record->wave_params = json_encode($layerResult['wave_params'] ?? []);
            $record->state_vector = json_encode($layerResult['state_vector'] ?? []);

            // 개입 정보
            $record->intervention_type = $layerResult['intervention_type'] ?? null;
            $record->intervention_executed = $layerResult['should_execute'] ? 1 : 0;
            $record->intervention_result = null; // 실행 후 업데이트

            // 규칙 메타데이터 (선택적)
            $record->rule_priority = $layerResult['rule_priority'] ?? 0;
            $record->rule_confidence = $layerResult['rule_confidence'] ?? 0;
            $record->conditions_matched = $layerResult['conditions_matched'] ?? 0;
            $record->conditions_total = $layerResult['conditions_total'] ?? 0;

            // 타임스탬프
            $record->timecreated = time();
            $record->timemodified = time();

            return $DB->insert_record('at_rule_quantum_state', $record);

        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] saveRuleQuantumState error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rule-Quantum Bridge 전체 평가 실행
     * 에이전트의 모든 규칙을 평가하고 최적의 개입 전략 결정
     *
     * @param int $studentId 학생 ID
     * @param string $sessionId 세션 ID
     * @param int $agentId 에이전트 ID (기본: 4)
     * @param array $context 추가 컨텍스트
     * @return array 평가 결과
     */
    public function evaluateRuleQuantumBridge(int $studentId, string $sessionId, int $agentId = 4, array $context = []): array
    {
        try {
            // 1. 브릿지 초기화
            if (!$this->initializeBridge()) {
                return ['error' => 'Bridge initialization failed', 'file' => $this->currentFile, 'line' => __LINE__];
            }

            // 2. 현재 4D 상태 가져오기 또는 초기화
            $stateVector4D = $context['state_vector'] ?? $this->initializeStateVector($context['onboarding'] ?? []);

            // 3. 4D → 8D 변환
            $state8D = $this->convert4Dto8D($stateVector4D);

            // 4. 에이전트 규칙 로드
            $agentRules = $this->ruleLoader->loadAgentRules($agentId);
            if (isset($agentRules['error'])) {
                return $agentRules;
            }

            // 5. 모든 규칙 평가
            $evaluations = [];
            $bestIntervention = null;
            $bestProbability = 0;

            foreach ($agentRules['rules'] ?? [] as $rule) {
                // 규칙을 파동 파라미터로 변환
                $waveParams = $this->waveMapper->mapRuleToWaveParams($rule, $agentId);

                // 4-Layer 확률 계산
                $layerResult = $this->calculate4LayerProbability($rule, $waveParams, $state8D, $agentId);
                $layerResult['rule_priority'] = $rule['priority'] ?? 0;
                $layerResult['rule_confidence'] = $rule['confidence'] ?? 0;
                $layerResult['conditions_total'] = count($rule['conditions'] ?? []);

                $evaluations[] = $layerResult;

                // 최고 확률 추적
                if ($layerResult['layer4_final'] > $bestProbability) {
                    $bestProbability = $layerResult['layer4_final'];
                    $bestIntervention = $layerResult;
                }

                // DB에 저장
                $this->saveRuleQuantumState($studentId, $sessionId, $layerResult);
            }

            // 6. 결과 요약
            return [
                'success' => true,
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'agent_id' => $agentId,
                'state_vector_4d' => $stateVector4D,
                'state_vector_8d' => $state8D,
                'rules_evaluated' => count($evaluations),
                'evaluations' => $evaluations,
                'best_intervention' => $bestIntervention,
                'recommended_action' => $this->generateBridgeRecommendation($bestIntervention),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("[QuantumPersonaEngine] evaluateRuleQuantumBridge error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return ['error' => $e->getMessage(), 'file' => $this->currentFile, 'line' => $e->getLine()];
        }
    }

    /**
     * Bridge 결과 기반 추천 생성
     */
    private function generateBridgeRecommendation(?array $bestIntervention): array
    {
        if (!$bestIntervention) {
            return [
                'action' => 'OBSERVE',
                'message' => '평가된 규칙이 없습니다. 관찰 모드를 유지합니다.',
                'urgency' => 'low'
            ];
        }

        $interventionType = $bestIntervention['intervention_type'];
        $probability = $bestIntervention['layer4_final'];

        $recommendations = [
            'IMMEDIATE_INTERVENTION' => [
                'action' => 'EXECUTE_NOW',
                'message' => "🚨 즉시 개입이 필요합니다. (확률: " . round($probability * 100, 1) . "%)",
                'urgency' => 'critical'
            ],
            'PROBABILISTIC_GATING' => [
                'action' => 'CONDITIONAL_EXECUTE',
                'message' => "⚠️ 조건부 개입을 권장합니다. (확률: " . round($probability * 100, 1) . "%)",
                'urgency' => 'high'
            ],
            'WEIGHT_ADJUSTMENT' => [
                'action' => 'ADJUST_AND_WAIT',
                'message' => "📊 가중치 조정 후 대기합니다. (확률: " . round($probability * 100, 1) . "%)",
                'urgency' => 'medium'
            ],
            'OBSERVE_ONLY' => [
                'action' => 'OBSERVE',
                'message' => "👀 관찰 모드를 유지합니다. (확률: " . round($probability * 100, 1) . "%)",
                'urgency' => 'low'
            ]
        ];

        return $recommendations[$interventionType] ?? $recommendations['OBSERVE_ONLY'];
    }

    /**
     * Bridge 정보 반환 (디버깅용)
     */
    public function getBridgeInfo(): array
    {
        $bridgeReady = $this->ruleLoader !== null && $this->waveMapper !== null;

        return [
            'name' => 'Rule-Quantum Bridge',
            'version' => '1.0.0',
            'phase' => 'Phase 1 - Agent04 Centric',
            'bridge_ready' => $bridgeReady,
            'layer_weights' => self::LAYER_WEIGHTS,
            'intervention_thresholds' => self::INTERVENTION_THRESHOLDS,
            'supported_agents' => range(1, 22),
            'features' => [
                '4_layer_probability',
                '4d_to_8d_conversion',
                'hybrid_intervention',
                'rule_wave_mapping',
                'db_persistence'
            ]
        ];
    }
}

/**
 * 관련 DB 테이블:
 * 
 * CREATE TABLE mdl_at_quantum_state (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   user_id INT NOT NULL,
 *   agent_id VARCHAR(50) NOT NULL,
 *   state_vector TEXT,
 *   probabilities TEXT,
 *   dominant_persona VARCHAR(20),
 *   superposition_level VARCHAR(30),
 *   synergy FLOAT DEFAULT 0,
 *   backfire FLOAT DEFAULT 0,
 *   golden_time INT DEFAULT 0,
 *   context_data TEXT,
 *   created_at INT NOT NULL,
 *   INDEX idx_user_agent (user_id, agent_id),
 *   INDEX idx_created (created_at)
 * );
 */



