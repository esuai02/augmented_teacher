<?php
/**
 * BrainAgentBridge.php - Brain Layer ↔ 에이전트 시스템 브릿지
 * 
 * QuantumDecisionEngine의 결정을 에이전트 시스템으로 전달하고
 * 에이전트들로부터 이벤트를 수신하여 Brain Layer로 전달
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/BrainAgentBridge.php
 */

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Brain Layer 컴포넌트
require_once(__DIR__ . '/QuantumDecisionEngine.php');
require_once(__DIR__ . '/StateCollector.php');
require_once(__DIR__ . '/WavefunctionCalculator.php');

// 에이전트 시스템 컴포넌트 (존재 시 로드)
$agentBusPath = __DIR__ . '/../orchestration/agents/engine_core/communication/InterAgentBus.php';
$agentMessagePath = __DIR__ . '/../orchestration/agents/engine_core/communication/AgentMessage.php';
$engineConfigPath = __DIR__ . '/../orchestration/agents/engine_core/config/engine_config.php';

// Moodle 내부 플래그 설정 (에이전트 시스템용)
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// 에이전트 시스템 로드 시도
$agentSystemAvailable = false;
if (file_exists($engineConfigPath) && file_exists($agentBusPath) && file_exists($agentMessagePath)) {
    require_once($engineConfigPath);
    require_once($agentMessagePath);
    require_once($agentBusPath);
    $agentSystemAvailable = true;
}

// LLM/TTS 클라이언트
require_once(__DIR__ . '/../shared/lib/LLMClient.php');
require_once(__DIR__ . '/../shared/lib/TTSClient.php');

/**
 * Class BrainAgentBridge
 * 
 * Brain Layer와 에이전트 시스템 간의 통신 브릿지
 * 
 * 주요 기능:
 * - Brain 결정 → 에이전트 메시지 변환 및 전송
 * - 에이전트 이벤트 → Brain Layer 전달
 * - 실시간 개입 오케스트레이션
 */
class BrainAgentBridge
{
    /** @var BrainAgentBridge|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var QuantumDecisionEngine 양자 판단 엔진 */
    private $decisionEngine;
    
    /** @var StateCollector 상태 수집기 */
    private $stateCollector;
    
    /** @var InterAgentBus|null 에이전트 통신 버스 */
    private $agentBus;
    
    /** @var LLMClient LLM 클라이언트 */
    private $llmClient;
    
    /** @var TTSClient TTS 클라이언트 */
    private $ttsClient;
    
    /** @var bool 에이전트 시스템 가용 여부 */
    private $agentSystemAvailable;
    
    /** @var array 최근 결정 로그 */
    private $decisionLog = [];
    
    /** @var int 마지막 개입 시간 */
    private $lastInterventionTime = 0;
    
    /** @var int 최소 개입 간격 (초) */
    private $minInterventionInterval = 10;

    // Brain Layer 전용 에이전트 번호 (가상)
    const BRAIN_AGENT_ID = 0;  // Brain은 시스템으로 취급
    
    // 메시지 타입
    const MSG_INTERVENTION_REQUIRED = 'brain_intervention_required';
    const MSG_MICRO_HINT = 'brain_micro_hint';
    const MSG_OBSERVE = 'brain_observe';
    const MSG_STATE_UPDATE = 'brain_state_update';
    const MSG_EMERGENCY = 'brain_emergency';

    /**
     * Private 생성자
     */
    private function __construct()
    {
        global $agentSystemAvailable;
        
        $this->agentSystemAvailable = $agentSystemAvailable;
        $this->decisionEngine = QuantumDecisionEngine::getInstance();
        $this->stateCollector = StateCollector::getInstance();
        $this->llmClient = LLMClient::getInstance();
        $this->ttsClient = TTSClient::getInstance();
        
        // 에이전트 버스 초기화 (가용 시)
        if ($this->agentSystemAvailable) {
            try {
                $this->agentBus = InterAgentBus::getInstance();
            } catch (Exception $e) {
                $this->agentSystemAvailable = false;
                $this->agentBus = null;
            }
        }
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
     * 학생에 대한 Brain 판단 실행 및 에이전트 오케스트레이션
     * 
     * @param int $studentId 학생 ID
     * @param array $context 추가 컨텍스트 (이벤트 정보 등)
     * @return array ['decision' => ..., 'actions' => ..., 'response' => ...]
     */
    public function process(int $studentId, array $context = []): array
    {
        $startTime = microtime(true);
        
        // 1. 양자 판단 엔진으로 결정
        $decision = $this->decisionEngine->decide($studentId, $context);
        
        // 2. 결정에 따른 액션 수행
        $actions = $this->executeDecision($decision, $studentId, $context);
        
        // 3. 응답 생성 (필요 시)
        $response = $this->generateResponse($decision, $studentId, $context);
        
        // 4. 로그 기록
        $this->logDecision($decision, $studentId, $actions);
        
        $processingTime = (microtime(true) - $startTime) * 1000;  // ms
        
        return [
            'success' => true,
            'decision' => $decision->toArray(),
            'actions' => $actions,
            'response' => $response,
            'processing_time_ms' => round($processingTime, 2),
            'agent_system_available' => $this->agentSystemAvailable
        ];
    }

    /**
     * 결정에 따른 액션 수행
     */
    private function executeDecision(InterventionDecision $decision, int $studentId, array $context): array
    {
        $actions = [];
        
        switch ($decision->type) {
            case 'intervene':
                // 즉시 개입: 에이전트들에게 개입 요청
                $actions = $this->executeIntervention($decision, $studentId, $context);
                $this->lastInterventionTime = time();
                break;
                
            case 'micro_hint':
                // 미세 힌트: Agent21만 활성화
                $actions = $this->executeMicroHint($decision, $studentId, $context);
                break;
                
            case 'observe':
                // 관찰 모드: 추임새 가능 여부 체크
                $actions = $this->executeObserve($decision, $studentId, $context);
                break;
                
            case 'none':
                // 개입 금지
                $actions = ['action' => 'none', 'reason' => '학생이 양호한 상태'];
                break;
        }
        
        return $actions;
    }

    /**
     * 즉시 개입 실행
     */
    private function executeIntervention(InterventionDecision $decision, int $studentId, array $context): array
    {
        $actions = [
            'type' => 'intervention',
            'agents_notified' => [],
            'messages_sent' => 0
        ];
        
        // 에이전트 시스템 사용 가능 시
        if ($this->agentSystemAvailable && $this->agentBus) {
            foreach ($decision->agents as $agentId) {
                $result = $this->sendToAgent($agentId, self::MSG_INTERVENTION_REQUIRED, [
                    'student_id' => $studentId,
                    'collapse_probability' => $decision->collapseProb,
                    'urgency' => $decision->urgency,
                    'style' => $decision->style,
                    'reason' => $decision->reason,
                    'context' => $context
                ], $decision->urgency <= 2 ? 1 : 3);  // 긴급 시 높은 우선순위
                
                if ($result['success']) {
                    $actions['agents_notified'][] = $agentId;
                    $actions['messages_sent']++;
                }
            }
        }
        
        // 긴급 상황 시 브로드캐스트
        if ($decision->urgency >= 4 && $this->agentSystemAvailable && $this->agentBus) {
            $this->broadcastEmergency($studentId, $decision);
            $actions['emergency_broadcast'] = true;
        }
        
        return $actions;
    }

    /**
     * 미세 힌트 실행
     */
    private function executeMicroHint(InterventionDecision $decision, int $studentId, array $context): array
    {
        $actions = [
            'type' => 'micro_hint',
            'hint_generated' => false
        ];
        
        // 최소 간격 체크
        if (time() - $this->lastInterventionTime < $this->minInterventionInterval) {
            $actions['skipped'] = true;
            $actions['reason'] = '최소 개입 간격 미충족';
            return $actions;
        }
        
        // Agent21에만 알림
        if ($this->agentSystemAvailable && $this->agentBus) {
            $result = $this->sendToAgent(21, self::MSG_MICRO_HINT, [
                'student_id' => $studentId,
                'collapse_probability' => $decision->collapseProb,
                'style' => $decision->style,
                'hint_type' => 'micro'
            ]);
            
            $actions['agent_21_notified'] = $result['success'];
        }
        
        $actions['hint_generated'] = true;
        $this->lastInterventionTime = time();
        
        return $actions;
    }

    /**
     * 관찰 모드 실행
     */
    private function executeObserve(InterventionDecision $decision, int $studentId, array $context): array
    {
        $actions = [
            'type' => 'observe',
            'backchannel_sent' => false
        ];
        
        // 추임새 가능 여부 체크 (15초 이상 경과)
        $timeSinceLastIntervention = time() - $this->lastInterventionTime;
        
        if ($timeSinceLastIntervention >= 15 && isset($context['student_action'])) {
            // 추임새 생성
            $backchannelResult = $this->ttsClient->generateBackchannel($context['student_action']);
            if ($backchannelResult['success']) {
                $actions['backchannel_sent'] = true;
                $actions['backchannel_text'] = $backchannelResult['text'] ?? '';
            }
        }
        
        return $actions;
    }

    /**
     * 응답 생성 (LLM + TTS)
     */
    private function generateResponse(InterventionDecision $decision, int $studentId, array $context): array
    {
        $response = [
            'text' => null,
            'audio' => null,
            'style' => $decision->style
        ];
        
        // 개입 또는 미세 힌트 시에만 응답 생성
        if (!in_array($decision->type, ['intervene', 'micro_hint'])) {
            return $response;
        }
        
        // 상황 설명 구성
        $situation = $context['situation'] ?? $this->buildSituation($decision, $studentId);
        
        // 양자 상태 기반 응답 생성
        $state = $this->stateCollector->collectRealtime($studentId);
        $quantumState = [
            'affect' => $state['emotion']['valence'] ?? 0.5,
            'energy' => $state['normalized']['motivation'] ?? 0.5,
            'confusion' => 1 - ($state['cognitive']['understanding_level'] ?? 0.5)
        ];
        
        // LLM으로 텍스트 생성
        $llmResponse = $this->llmClient->generateQuantumResponse($quantumState, $situation);
        $response['text'] = $llmResponse['text'];
        $response['style'] = array_merge($response['style'], [
            'tone' => $llmResponse['tone'],
            'speed' => $llmResponse['speed'],
            'emotion' => $llmResponse['emotion']
        ]);
        
        // TTS로 음성 생성 (텍스트가 있을 때만)
        if (!empty($response['text'])) {
            $ttsResult = $this->ttsClient->synthesize($response['text'], $response['style']);
            if ($ttsResult['success']) {
                $response['audio'] = base64_encode($ttsResult['audio']);
                $response['audio_format'] = 'mp3';
            }
        }
        
        return $response;
    }

    /**
     * 상황 설명 구성
     */
    private function buildSituation(InterventionDecision $decision, int $studentId): string
    {
        $state = $this->stateCollector->collectRealtime($studentId);
        
        $situation = "학생이 수학 문제를 풀고 있습니다. ";
        
        if ($decision->urgency >= 4) {
            $situation .= "긴급 상황입니다. ";
        }
        
        if (($state['emotion']['frustration'] ?? 0) > 0.5) {
            $situation .= "학생이 좌절감을 느끼고 있습니다. ";
        }
        
        if (($state['behavior']['idle_seconds'] ?? 0) > 30) {
            $situation .= "30초 이상 멈춰있습니다. ";
        }
        
        $situation .= "적절한 " . ($decision->type === 'micro_hint' ? '미세 힌트' : '격려 또는 힌트') . "를 주세요.";
        
        return $situation;
    }

    /**
     * 에이전트에 메시지 전송
     */
    private function sendToAgent(int $agentId, string $messageType, array $payload, int $priority = 5): array
    {
        if (!$this->agentSystemAvailable || !$this->agentBus) {
            return ['success' => false, 'error' => '에이전트 시스템 비가용'];
        }
        
        try {
            return $this->agentBus->send(
                self::BRAIN_AGENT_ID,  // Brain에서 발신
                $agentId,
                $messageType,
                $payload,
                $priority,
                300  // 5분 TTL
            );
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 긴급 브로드캐스트
     */
    private function broadcastEmergency(int $studentId, InterventionDecision $decision): array
    {
        if (!$this->agentSystemAvailable || !$this->agentBus) {
            return ['success' => false];
        }
        
        try {
            return $this->agentBus->broadcast(
                self::BRAIN_AGENT_ID,
                self::MSG_EMERGENCY,
                [
                    'student_id' => $studentId,
                    'collapse_probability' => $decision->collapseProb,
                    'urgency' => $decision->urgency,
                    'reason' => $decision->reason
                ],
                [],  // 제외 없음
                1    // 최고 우선순위
            );
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 결정 로그 기록
     */
    private function logDecision(InterventionDecision $decision, int $studentId, array $actions): void
    {
        $this->decisionLog[] = [
            'timestamp' => time(),
            'student_id' => $studentId,
            'decision_type' => $decision->type,
            'collapse_prob' => $decision->collapseProb,
            'urgency' => $decision->urgency,
            'agents' => $decision->agents,
            'actions' => $actions
        ];
        
        // 최근 100개만 유지
        if (count($this->decisionLog) > 100) {
            array_shift($this->decisionLog);
        }
    }

    /**
     * 에이전트 이벤트 수신 처리
     * 
     * @param int $fromAgent 발신 에이전트
     * @param string $eventType 이벤트 타입
     * @param array $payload 이벤트 데이터
     * @return array 처리 결과
     */
    public function handleAgentEvent(int $fromAgent, string $eventType, array $payload): array
    {
        $studentId = $payload['student_id'] ?? 0;
        
        if (!$studentId) {
            return ['success' => false, 'error' => 'student_id 필요'];
        }
        
        // 이벤트에 따른 상태 갱신 및 재판단
        switch ($eventType) {
            case 'emotion_changed':
            case 'frustration_detected':
            case 'dropout_risk_detected':
                // 긴급 재판단
                return $this->process($studentId, [
                    'trigger' => $eventType,
                    'from_agent' => $fromAgent,
                    'urgent' => true
                ]);
                
            case 'problem_completed':
            case 'activity_completed':
                // 일반 상태 업데이트
                return $this->process($studentId, [
                    'trigger' => $eventType,
                    'from_agent' => $fromAgent,
                    'student_action' => 'correct'  // 추임새용
                ]);
                
            default:
                // 기타 이벤트는 로그만
                return ['success' => true, 'action' => 'logged', 'event' => $eventType];
        }
    }

    /**
     * 디버그 정보 반환
     */
    public function getDebugInfo(int $studentId): array
    {
        return [
            'brain_debug' => $this->decisionEngine->getDebugInfo($studentId),
            'agent_system_available' => $this->agentSystemAvailable,
            'last_intervention_time' => $this->lastInterventionTime,
            'recent_decisions' => array_slice($this->decisionLog, -10)
        ];
    }

    /**
     * 시스템 상태 확인
     */
    public function getSystemStatus(): array
    {
        return [
            'brain_layer' => true,
            'agent_system' => $this->agentSystemAvailable,
            'llm_client' => true,
            'tts_client' => true,
            'decision_log_count' => count($this->decisionLog)
        ];
    }
}

