<?php
/**
 * AgentDispatcher - 에이전트 실행 및 관리 시스템
 * 사용자 요청에 따라 적절한 에이전트를 선택, 실행, 모니터링
 */

require_once 'AgentLoader.php';
require_once 'AgentCore.php';

class AgentDispatcher {
    private $loader;
    private $activeAgents = [];
    private $sessionData = [];
    private $logFile;
    private $configFile;
    
    public function __construct($configFile = null) {
        $this->loader = new AgentLoader();
        $this->configFile = $configFile ?: __DIR__ . '/config/dispatcher_config.json';
        $this->logFile = __DIR__ . '/logs/agent_dispatcher.log';
        $this->initializeDirectories();
        $this->loadConfiguration();
    }
    
    /**
     * 필요한 디렉토리 생성
     */
    private function initializeDirectories() {
        $dirs = [
            __DIR__ . '/config',
            __DIR__ . '/logs',
            __DIR__ . '/states',
            __DIR__ . '/sessions'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * 설정 파일 로드
     */
    private function loadConfiguration() {
        if (file_exists($this->configFile)) {
            $this->sessionData = json_decode(file_get_contents($this->configFile), true) ?? [];
        } else {
            $this->sessionData = $this->getDefaultConfiguration();
            $this->saveConfiguration();
        }
    }
    
    /**
     * 기본 설정 반환
     */
    private function getDefaultConfiguration() {
        return [
            'max_active_agents' => 3,
            'session_timeout' => 3600, // 1시간
            'auto_save_interval' => 300, // 5분
            'logging_level' => 'info',
            'default_mode' => 'curriculumcentered',
            'blending_enabled' => true,
            'auto_switching' => true
        ];
    }
    
    /**
     * 설정 저장
     */
    private function saveConfiguration() {
        file_put_contents($this->configFile, json_encode($this->sessionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 에이전트 요청 처리 (메인 엔드포인트)
     */
    public function handleRequest($request) {
        $this->log("Request received: " . json_encode($request));
        
        try {
            $response = $this->processRequest($request);
            $this->log("Request processed successfully");
            return $this->formatResponse($response, 'success');
        } catch (Exception $e) {
            $this->log("Error processing request: " . $e->getMessage(), 'error');
            return $this->formatResponse(['error' => $e->getMessage()], 'error');
        }
    }
    
    /**
     * 요청 처리 로직
     */
    private function processRequest($request) {
        $action = $request['action'] ?? 'get_recommendation';
        $userId = $request['user_id'] ?? 'anonymous';
        $userData = $request['user_data'] ?? [];
        
        switch ($action) {
            case 'get_recommendation':
                return $this->getAgentRecommendation($userData);
                
            case 'start_agent':
                $mode = $request['mode'] ?? $this->sessionData['default_mode'];
                return $this->startAgent($mode, $userId, $userData);
                
            case 'execute_agent':
                $agentId = $request['agent_id'] ?? null;
                $input = $request['input'] ?? null;
                return $this->executeAgent($agentId, $input);
                
            case 'get_status':
                $agentId = $request['agent_id'] ?? null;
                return $this->getAgentStatus($agentId);
                
            case 'stop_agent':
                $agentId = $request['agent_id'] ?? null;
                return $this->stopAgent($agentId);
                
            case 'blend_agents':
                $modes = $request['modes'] ?? [];
                $situation = $request['situation'] ?? '';
                return $this->blendAgents($modes, $situation, $userId, $userData);
                
            case 'get_available_agents':
                return $this->loader->getAvailableAgents();
                
            case 'validate_agent':
                $mode = $request['mode'] ?? '';
                return $this->loader->validateAgent($mode);
                
            default:
                throw new Exception("Unknown action: {$action}");
        }
    }
    
    /**
     * 사용자 데이터 기반 에이전트 추천
     */
    public function getAgentRecommendation($userData) {
        $recommendations = [];
        $availableAgents = $this->loader->getAvailableAgents();
        
        // 시험 임박도 체크
        $daysToExam = $this->calculateDaysToExam($userData);
        if ($daysToExam <= 30 && $daysToExam > 0) {
            $recommendations[] = [
                'mode' => 'examcentered',
                'priority' => 'high',
                'reason' => "시험까지 {$daysToExam}일 남음 - 성과집중형 모드 권장",
                'confidence' => 0.9
            ];
        }
        
        // 기초 점수 체크
        $baseScore = $userData['current_score'] ?? 70;
        if ($baseScore < 60) {
            $recommendations[] = [
                'mode' => 'adaptationcentered',
                'priority' => 'high',
                'reason' => '기초 점수가 낮음 - 개인맞춤형 집중 보강 필요',
                'confidence' => 0.85
            ];
        }
        
        // 학습 패턴 분석
        $studyPattern = $userData['study_pattern'] ?? '';
        $autonomyRate = $userData['autonomy_rate'] ?? 0.5;
        
        if ($autonomyRate >= 0.7) {
            $recommendations[] = [
                'mode' => 'selfdriven',
                'priority' => 'medium',
                'reason' => '높은 자율성 - 자기주도 학습 모드 적합',
                'confidence' => 0.75
            ];
        }
        
        // 동기 부족 체크
        $motivationLevel = $userData['motivation_level'] ?? 0.7;
        if ($motivationLevel < 0.5) {
            $recommendations[] = [
                'mode' => 'missioncentered',
                'priority' => 'medium',
                'reason' => '동기 부족 - 단기 목표 달성형 모드로 회복',
                'confidence' => 0.7
            ];
        }
        
        // 기본 추천 (데이터 부족시)
        if (empty($recommendations)) {
            $recommendations[] = [
                'mode' => 'curriculumcentered',
                'priority' => 'medium',
                'reason' => '체계적인 학습 진행 - 커리큘럼 중심 안정적 접근',
                'confidence' => 0.6
            ];
        }
        
        // 우선순위별 정렬
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            if ($priorityOrder[$a['priority']] !== $priorityOrder[$b['priority']]) {
                return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
            }
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return [
            'recommendations' => $recommendations,
            'available_agents' => array_keys(array_filter($availableAgents, function($agent) { 
                return $agent['ready']; 
            })),
            'analysis' => $this->analyzeUserData($userData)
        ];
    }
    
    /**
     * 에이전트 시작
     */
    public function startAgent($mode, $userId, $userData = []) {
        $agentId = $this->generateAgentId($mode, $userId);
        
        try {
            $agent = $this->loader->loadAgent($mode, $userData);
            
            $this->activeAgents[$agentId] = [
                'agent' => $agent,
                'mode' => $mode,
                'user_id' => $userId,
                'start_time' => time(),
                'last_activity' => time(),
                'status' => 'active',
                'execution_count' => 0
            ];
            
            $this->log("Agent started: {$agentId} (mode: {$mode}, user: {$userId})");
            
            return [
                'agent_id' => $agentId,
                'mode' => $mode,
                'status' => 'started',
                'config' => $this->loader->extractMDComponents($mode),
                'initial_state' => $agent->execute(['action' => 'initialize'])
            ];
            
        } catch (Exception $e) {
            $this->log("Failed to start agent {$agentId}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 에이전트 실행
     */
    public function executeAgent($agentId, $input) {
        if (!isset($this->activeAgents[$agentId])) {
            throw new Exception("Agent not found: {$agentId}");
        }
        
        $agentData = &$this->activeAgents[$agentId];
        $agent = $agentData['agent'];
        
        // 활동 시간 업데이트
        $agentData['last_activity'] = time();
        $agentData['execution_count']++;
        
        try {
            $result = $agent->execute($input);
            
            // 자동 전환 체크
            if ($this->sessionData['auto_switching']) {
                $switchingTriggers = $result['context']['switching_triggers'] ?? [];
                if (!empty($switchingTriggers)) {
                    $result['switching_suggestions'] = $switchingTriggers;
                }
            }
            
            $this->log("Agent executed: {$agentId}, execution #{$agentData['execution_count']}");
            
            return [
                'agent_id' => $agentId,
                'execution_id' => $agentData['execution_count'],
                'result' => $result,
                'status' => 'executed',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->log("Agent execution failed: {$agentId} - " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 에이전트 상태 조회
     */
    public function getAgentStatus($agentId = null) {
        if ($agentId) {
            if (!isset($this->activeAgents[$agentId])) {
                throw new Exception("Agent not found: {$agentId}");
            }
            
            $agentData = $this->activeAgents[$agentId];
            return [
                'agent_id' => $agentId,
                'mode' => $agentData['mode'],
                'user_id' => $agentData['user_id'],
                'status' => $agentData['status'],
                'uptime' => time() - $agentData['start_time'],
                'last_activity' => $agentData['last_activity'],
                'execution_count' => $agentData['execution_count']
            ];
        } else {
            // 모든 활성 에이전트 상태
            $statuses = [];
            foreach ($this->activeAgents as $id => $data) {
                $statuses[$id] = $this->getAgentStatus($id);
            }
            return [
                'active_agents' => count($this->activeAgents),
                'agents' => $statuses,
                'system_uptime' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
            ];
        }
    }
    
    /**
     * 에이전트 중지
     */
    public function stopAgent($agentId) {
        if (!isset($this->activeAgents[$agentId])) {
            throw new Exception("Agent not found: {$agentId}");
        }
        
        $agentData = $this->activeAgents[$agentId];
        
        // 상태 저장
        $agent = $agentData['agent'];
        $agent->saveState(__DIR__ . "/states/{$agentId}_final.json");
        
        unset($this->activeAgents[$agentId]);
        
        $this->log("Agent stopped: {$agentId}");
        
        return [
            'agent_id' => $agentId,
            'status' => 'stopped',
            'final_stats' => [
                'uptime' => time() - $agentData['start_time'],
                'execution_count' => $agentData['execution_count']
            ]
        ];
    }
    
    /**
     * 에이전트 블렌딩
     */
    public function blendAgents($modes, $situation, $userId, $userData) {
        if (!$this->sessionData['blending_enabled']) {
            throw new Exception("Agent blending is disabled");
        }
        
        $blendedId = $this->generateBlendedAgentId($modes, $userId);
        $agents = [];
        
        // 각 모드의 에이전트 로드
        foreach ($modes as $mode) {
            try {
                $agents[$mode] = $this->loader->loadAgent($mode, $userData);
            } catch (Exception $e) {
                $this->log("Failed to load agent for blending: {$mode} - " . $e->getMessage(), 'error');
                continue;
            }
        }
        
        if (empty($agents)) {
            throw new Exception("No agents could be loaded for blending");
        }
        
        // 블렌딩 전략 결정
        $blendingStrategy = $this->determineBlendingStrategy($situation, $agents);
        
        $this->activeAgents[$blendedId] = [
            'agents' => $agents,
            'modes' => $modes,
            'user_id' => $userId,
            'situation' => $situation,
            'strategy' => $blendingStrategy,
            'start_time' => time(),
            'last_activity' => time(),
            'status' => 'blended',
            'execution_count' => 0
        ];
        
        $this->log("Agents blended: {$blendedId} (modes: " . implode(',', $modes) . ")");
        
        return [
            'blended_id' => $blendedId,
            'modes' => $modes,
            'situation' => $situation,
            'strategy' => $blendingStrategy,
            'status' => 'blended'
        ];
    }
    
    /**
     * 블렌딩 전략 결정
     */
    private function determineBlendingStrategy($situation, $agents) {
        // 각 에이전트의 블렌딩 규칙 조합
        $strategies = [];
        foreach ($agents as $mode => $agent) {
            $recommendation = $agent->recommendBlending($situation);
            if ($recommendation) {
                $strategies[$mode] = $recommendation;
            }
        }
        
        return [
            'primary_mode' => $this->selectPrimaryMode($situation, array_keys($agents)),
            'support_modes' => array_diff(array_keys($agents), [$this->selectPrimaryMode($situation, array_keys($agents))]),
            'execution_order' => $this->determineExecutionOrder($situation, array_keys($agents)),
            'weight_distribution' => $this->calculateModeWeights($situation, array_keys($agents))
        ];
    }
    
    /**
     * 로그 기록
     */
    private function log($message, $level = 'info') {
        if (!in_array($level, ['debug', 'info', 'warning', 'error'])) {
            $level = 'info';
        }
        
        if ($this->sessionData['logging_level'] === 'error' && $level !== 'error') {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 응답 포맷팅
     */
    private function formatResponse($data, $status = 'success') {
        return [
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
    }
    
    // 헬퍼 메서드들
    private function generateAgentId($mode, $userId) {
        return "agent_{$mode}_{$userId}_" . time();
    }
    
    private function generateBlendedAgentId($modes, $userId) {
        return "blended_" . implode('_', $modes) . "_{$userId}_" . time();
    }
    
    private function calculateDaysToExam($userData) {
        if (!isset($userData['exam_date'])) {
            return 999;
        }
        
        $examDate = strtotime($userData['exam_date']);
        $today = time();
        return max(0, ceil(($examDate - $today) / (24 * 60 * 60)));
    }
    
    private function analyzeUserData($userData) {
        return [
            'completeness' => count($userData) / 10, // 가정: 10개 필드가 완전
            'quality_score' => $this->calculateDataQuality($userData),
            'recommendations' => $this->generateDataRecommendations($userData)
        ];
    }
    
    private function calculateDataQuality($userData) {
        $score = 0;
        $checks = [
            'has_current_score' => isset($userData['current_score']),
            'has_study_pattern' => isset($userData['study_pattern']),
            'has_weak_areas' => isset($userData['weak_areas']),
            'has_exam_date' => isset($userData['exam_date']),
            'has_motivation_level' => isset($userData['motivation_level'])
        ];
        
        return array_sum($checks) / count($checks);
    }
    
    private function generateDataRecommendations($userData) {
        $recommendations = [];
        
        if (!isset($userData['current_score'])) {
            $recommendations[] = '현재 성적 정보 입력 필요';
        }
        if (!isset($userData['weak_areas'])) {
            $recommendations[] = '취약 영역 진단 필요';
        }
        if (!isset($userData['exam_date'])) {
            $recommendations[] = '목표 시험일 설정 권장';
        }
        
        return $recommendations;
    }
    
    private function selectPrimaryMode($situation, $modes) {
        // 상황별 우선순위 로직
        if (strpos($situation, 'exam') !== false) {
            return in_array('examcentered', $modes) ? 'examcentered' : $modes[0];
        }
        if (strpos($situation, 'basic') !== false) {
            return in_array('adaptationcentered', $modes) ? 'adaptationcentered' : $modes[0];
        }
        
        return $modes[0];
    }
    
    private function determineExecutionOrder($situation, $modes) {
        // 실행 순서 결정 로직
        return $modes;
    }
    
    private function calculateModeWeights($situation, $modes) {
        // 모드별 가중치 계산
        $weights = [];
        $equalWeight = 1.0 / count($modes);
        
        foreach ($modes as $mode) {
            $weights[$mode] = $equalWeight;
        }
        
        return $weights;
    }
    
    /**
     * 세션 정리 (타임아웃된 에이전트 정리)
     */
    public function cleanupSessions() {
        $timeout = $this->sessionData['session_timeout'];
        $now = time();
        $cleaned = 0;
        
        foreach ($this->activeAgents as $agentId => $agentData) {
            if (($now - $agentData['last_activity']) > $timeout) {
                $this->stopAgent($agentId);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            $this->log("Cleaned up {$cleaned} timed-out agents");
        }
        
        return $cleaned;
    }
}