<?php
/**
 * RealtimeTutor.php - 실시간 AI 튜터 통합 컨트롤러
 * 
 * Brain/Mind/Mouth 3레이어를 통합하여
 * "완전히 사람과 같은" AI 튜터 경험 제공
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/RealtimeTutor.php
 * 
 * 3레이어 아키텍처:
 * - Brain (양자 판단 엔진): 개입 여부 결정
 * - Mind (LLM 컨텍스트 생성): 대사 생성
 * - Mouth (TTS 엔진): 감정 실린 음성
 */

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Brain Layer 컴포넌트
require_once(__DIR__ . '/BrainAgentBridge.php');
require_once(__DIR__ . '/QuantumDecisionEngine.php');
require_once(__DIR__ . '/StateCollector.php');
require_once(__DIR__ . '/WavefunctionCalculator.php');

// Shared 컴포넌트
require_once(__DIR__ . '/../shared/lib/LLMClient.php');
require_once(__DIR__ . '/../shared/lib/TTSClient.php');

// 설정
require_once(__DIR__ . '/../config/ai_services.config.php');

/**
 * Class RealtimeTutor
 * 
 * 실시간 AI 튜터의 메인 컨트롤러
 * 모든 레이어를 오케스트레이션
 */
class RealtimeTutor
{
    /** @var RealtimeTutor|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var BrainAgentBridge Brain-Agent 브릿지 */
    private $brainBridge;
    
    /** @var QuantumDecisionEngine 양자 판단 엔진 */
    private $brainEngine;
    
    /** @var StateCollector 상태 수집기 */
    private $stateCollector;
    
    /** @var WavefunctionCalculator 파동함수 계산기 */
    private $wavefunctionCalc;
    
    /** @var LLMClient LLM 클라이언트 (Mind) */
    private $mind;
    
    /** @var TTSClient TTS 클라이언트 (Mouth) */
    private $mouth;
    
    /** @var int 현재 학생 ID */
    private $studentId;
    
    /** @var array 현재 세션 상태 */
    private $sessionState = [];
    
    /** @var array 튜터 설정 */
    private $config;

    // 튜터 모드
    const MODE_ACTIVE = 'active';       // 적극적 개입
    const MODE_GUIDE = 'guide';         // 가이드 모드
    const MODE_OBSERVE = 'observe';     // 관찰 모드
    const MODE_SILENT = 'silent';       // 묵음 모드

    /**
     * Private 생성자
     */
    private function __construct()
    {
        $this->brainBridge = BrainAgentBridge::getInstance();
        $this->brainEngine = QuantumDecisionEngine::getInstance();
        $this->stateCollector = StateCollector::getInstance();
        $this->wavefunctionCalc = WavefunctionCalculator::getInstance();
        $this->mind = LLMClient::getInstance();
        $this->mouth = TTSClient::getInstance();
        
        // 설정 로드
        $this->config = defined('REALTIME_TUTOR_CONFIG') ? REALTIME_TUTOR_CONFIG : [
            'latency_target_ms' => 300,
            'intervention_threshold' => 0.7,
            'micro_hint_threshold' => 0.4,
            'backchannel_interval_seconds' => 15
        ];
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
     * 학생 설정 및 세션 시작
     * 
     * @param int $studentId 학생 ID
     * @param array $options 옵션 ['mode' => 'active', ...]
     * @return self
     */
    public function start(int $studentId, array $options = []): self
    {
        $this->studentId = $studentId;
        $this->stateCollector->setStudent($studentId);
        
        $this->sessionState = [
            'student_id' => $studentId,
            'start_time' => time(),
            'mode' => $options['mode'] ?? self::MODE_GUIDE,
            'intervention_count' => 0,
            'backchannel_count' => 0,
            'last_intervention_time' => 0,
            'last_backchannel_time' => 0
        ];
        
        return $this;
    }

    /**
     * 실시간 처리 - 메인 진입점
     * 
     * 학생 행동/상태 변화에 따라 자동으로 판단하고 응답
     * 
     * @param array $event 이벤트 정보 ['type' => 'mouse_idle', 'data' => [...]]
     * @return array ['action' => 'none|speak|hint', 'response' => [...]]
     */
    public function tick(array $event = []): array
    {
        $startTime = microtime(true);
        
        if (!$this->studentId) {
            return $this->errorResponse('세션이 시작되지 않았습니다. start() 호출 필요');
        }
        
        // 묵음 모드면 아무것도 하지 않음
        if ($this->sessionState['mode'] === self::MODE_SILENT) {
            return $this->noActionResponse('묵음 모드');
        }
        
        // 1. Brain 판단
        $brainResult = $this->brainBridge->process($this->studentId, $event);
        $decision = $brainResult['decision'];
        
        // 2. 결정에 따른 응답 생성
        $response = $this->handleDecision($decision, $event, $brainResult);
        
        // 3. 처리 시간 기록
        $processingTime = (microtime(true) - $startTime) * 1000;
        $response['processing_time_ms'] = round($processingTime, 2);
        $response['latency_target_met'] = $processingTime < $this->config['latency_target_ms'];
        
        return $response;
    }

    /**
     * 결정에 따른 응답 핸들링
     */
    private function handleDecision(array $decision, array $event, array $brainResult): array
    {
        $mode = $this->sessionState['mode'];
        
        switch ($decision['type']) {
            case 'intervene':
                // 즉시 개입
                if ($mode === self::MODE_OBSERVE) {
                    // 관찰 모드에서는 경고만
                    return $this->observeResponse($decision);
                }
                return $this->interventionResponse($decision, $brainResult);
                
            case 'micro_hint':
                // 미세 힌트
                if ($mode === self::MODE_OBSERVE) {
                    return $this->observeResponse($decision);
                }
                return $this->microHintResponse($decision);
                
            case 'observe':
                // 관찰 모드: 추임새 가능
                return $this->maybeBackchannel($event);
                
            case 'none':
            default:
                // 개입 금지
                return $this->noActionResponse($decision['reason'] ?? '상태 양호');
        }
    }

    /**
     * 즉시 개입 응답 생성
     */
    private function interventionResponse(array $decision, array $brainResult): array
    {
        $this->sessionState['intervention_count']++;
        $this->sessionState['last_intervention_time'] = time();
        
        // Brain 결과에서 응답 가져오기 (이미 생성됨)
        $response = $brainResult['response'] ?? [];
        
        return [
            'action' => 'speak',
            'type' => 'intervention',
            'text' => $response['text'] ?? '',
            'audio' => $response['audio'] ?? null,
            'audio_format' => $response['audio_format'] ?? 'mp3',
            'style' => $response['style'] ?? $decision['style'],
            'urgency' => $decision['urgency'],
            'collapse_probability' => $decision['collapse_probability'],
            'reason' => $decision['reason']
        ];
    }

    /**
     * 미세 힌트 응답 생성
     */
    private function microHintResponse(array $decision): array
    {
        $this->sessionState['intervention_count']++;
        $this->sessionState['last_intervention_time'] = time();
        
        // Mind: 힌트 텍스트 생성
        $state = $this->stateCollector->collectRealtime($this->studentId);
        $prompt = $this->buildMicroHintPrompt($state);
        
        $hintText = $this->mind->quickResponse($prompt, 'tutor', [
            '감정 상태' => $state['emotion']['current'],
            '자신감' => round($state['emotion']['confidence'] * 100) . '%'
        ]);
        
        // Mouth: 음성 생성
        $audio = null;
        $audioFormat = 'mp3';
        
        if (!empty($hintText)) {
            $ttsResult = $this->mouth->synthesize($hintText, $decision['style']);
            if ($ttsResult['success']) {
                $audio = base64_encode($ttsResult['audio']);
            }
        }
        
        return [
            'action' => 'speak',
            'type' => 'micro_hint',
            'text' => $hintText,
            'audio' => $audio,
            'audio_format' => $audioFormat,
            'style' => $decision['style'],
            'collapse_probability' => $decision['collapse_probability']
        ];
    }

    /**
     * 추임새 (Back-channeling) 응답
     */
    private function maybeBackchannel(array $event): array
    {
        // 최소 간격 체크
        $timeSinceLastBackchannel = time() - $this->sessionState['last_backchannel_time'];
        $interval = $this->config['backchannel_interval_seconds'];
        
        if ($timeSinceLastBackchannel < $interval) {
            return $this->noActionResponse('추임새 간격 미충족');
        }
        
        // 학생 행동에 따른 추임새 타입 결정
        $studentAction = $event['type'] ?? 'progress';
        $backchannelType = $this->mapActionToBackchannelType($studentAction);
        
        // Mouth: 추임새 생성
        $result = $this->mouth->quickFiller($backchannelType);
        
        if (!$result['success']) {
            return $this->noActionResponse('추임새 생성 실패');
        }
        
        $this->sessionState['backchannel_count']++;
        $this->sessionState['last_backchannel_time'] = time();
        
        return [
            'action' => 'backchannel',
            'type' => $backchannelType,
            'text' => $result['text'],
            'audio' => base64_encode($result['audio']),
            'audio_format' => 'mp3'
        ];
    }

    /**
     * 관찰 모드 응답 (경고만)
     */
    private function observeResponse(array $decision): array
    {
        return [
            'action' => 'observe_alert',
            'type' => $decision['type'],
            'message' => '개입이 필요하지만 관찰 모드입니다',
            'collapse_probability' => $decision['collapse_probability'],
            'urgency' => $decision['urgency'],
            'reason' => $decision['reason']
        ];
    }

    /**
     * 액션 없음 응답
     */
    private function noActionResponse(string $reason): array
    {
        return [
            'action' => 'none',
            'reason' => $reason
        ];
    }

    /**
     * 에러 응답
     */
    private function errorResponse(string $error): array
    {
        return [
            'action' => 'error',
            'error' => $error
        ];
    }

    /**
     * 미세 힌트 프롬프트 구성
     */
    private function buildMicroHintPrompt(array $state): string
    {
        $accuracy = round(($state['cognitive']['recent_accuracy'] ?? 0.5) * 100);
        $frustration = $state['emotion']['frustration'] ?? 0;
        
        $prompt = "학생이 수학 문제에서 막혀있습니다. ";
        $prompt .= "최근 정답률 {$accuracy}%입니다. ";
        
        if ($frustration > 0.5) {
            $prompt .= "좌절감을 느끼고 있습니다. ";
        }
        
        $prompt .= "문제를 푸는 방향만 살짝 알려주는 한 문장 힌트를 주세요. 직접적인 답은 주지 마세요.";
        
        return $prompt;
    }

    /**
     * 학생 행동 → 추임새 타입 매핑
     */
    private function mapActionToBackchannelType(string $action): string
    {
        $mapping = [
            'correct' => 'positive',
            'wrong' => 'thinking',
            'typing' => 'thinking',
            'idle' => 'curious',
            'progress' => 'agreement',
            'breakthrough' => 'surprise'
        ];
        
        return $mapping[$action] ?? 'thinking';
    }

    // =========================================================================
    // 수동 컨트롤 메서드
    // =========================================================================

    /**
     * 수동으로 발화
     * 
     * @param string $text 발화 텍스트
     * @param array $style 스타일 옵션
     * @return array
     */
    public function speak(string $text, array $style = []): array
    {
        if (empty($text)) {
            return $this->errorResponse('텍스트가 비어있습니다');
        }
        
        // TTS 생성
        $ttsResult = $this->mouth->synthesize($text, $style);
        
        if (!$ttsResult['success']) {
            return $this->errorResponse($ttsResult['error'] ?? 'TTS 생성 실패');
        }
        
        return [
            'action' => 'speak',
            'type' => 'manual',
            'text' => $text,
            'audio' => base64_encode($ttsResult['audio']),
            'audio_format' => 'mp3',
            'style' => $style
        ];
    }

    /**
     * 모드 변경
     */
    public function setMode(string $mode): self
    {
        $validModes = [self::MODE_ACTIVE, self::MODE_GUIDE, self::MODE_OBSERVE, self::MODE_SILENT];
        
        if (in_array($mode, $validModes)) {
            $this->sessionState['mode'] = $mode;
        }
        
        return $this;
    }

    /**
     * 현재 상태 조회
     */
    public function getState(): array
    {
        if (!$this->studentId) {
            return ['error' => '세션 없음'];
        }
        
        $state = $this->stateCollector->collectRealtime($this->studentId);
        $wavefunctions = $this->wavefunctionCalc->calculateAll($state);
        
        return [
            'session' => $this->sessionState,
            'student_state' => $state,
            'wavefunctions' => $wavefunctions,
            'system_status' => $this->brainBridge->getSystemStatus()
        ];
    }

    /**
     * 세션 종료
     */
    public function stop(): array
    {
        $summary = [
            'session_duration_minutes' => round((time() - $this->sessionState['start_time']) / 60, 1),
            'intervention_count' => $this->sessionState['intervention_count'],
            'backchannel_count' => $this->sessionState['backchannel_count']
        ];
        
        $this->studentId = null;
        $this->sessionState = [];
        
        return $summary;
    }
}

