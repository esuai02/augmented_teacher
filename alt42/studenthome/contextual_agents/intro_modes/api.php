<?php
/**
 * Agent System API - 에이전트 시스템의 HTTP API 엔드포인트
 * 웹 클라이언트에서 에이전트 시스템과 상호작용하기 위한 RESTful API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// CORS preflight 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'AgentDispatcher.php';

try {
    $dispatcher = new AgentDispatcher();
    
    // HTTP 메서드에 따른 처리
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? '/';
    
    // 입력 데이터 파싱
    $input = [];
    if ($method === 'POST' || $method === 'PUT') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? [];
    }
    $input = array_merge($input, $_GET); // GET 파라미터도 포함
    
    // API 라우팅
    switch ($path) {
        case '/':
        case '/status':
            handleStatus($dispatcher);
            break;
            
        case '/agents':
            if ($method === 'GET') {
                handleGetAgents($dispatcher);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/agents/recommend':
            if ($method === 'POST') {
                handleRecommendAgent($dispatcher, $input);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/agents/start':
            if ($method === 'POST') {
                handleStartAgent($dispatcher, $input);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/agents/execute':
            if ($method === 'POST') {
                handleExecuteAgent($dispatcher, $input);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/agents/stop':
            if ($method === 'POST') {
                handleStopAgent($dispatcher, $input);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/agents/blend':
            if ($method === 'POST') {
                handleBlendAgents($dispatcher, $input);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/agents/validate':
            if ($method === 'POST') {
                handleValidateAgent($dispatcher, $input);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        case '/cleanup':
            if ($method === 'POST') {
                handleCleanup($dispatcher);
            } else {
                sendError(405, 'Method not allowed');
            }
            break;
            
        default:
            sendError(404, 'Endpoint not found');
            break;
    }
    
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}

/**
 * 시스템 상태 조회
 */
function handleStatus($dispatcher) {
    try {
        $status = $dispatcher->getAgentStatus();
        sendResponse($status);
    } catch (Exception $e) {
        sendError(500, $e->getMessage());
    }
}

/**
 * 사용 가능한 에이전트 목록 조회
 */
function handleGetAgents($dispatcher) {
    try {
        $request = ['action' => 'get_available_agents'];
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(500, $e->getMessage());
    }
}

/**
 * 에이전트 추천 요청
 */
function handleRecommendAgent($dispatcher, $input) {
    try {
        $userData = $input['user_data'] ?? [];
        
        $request = [
            'action' => 'get_recommendation',
            'user_data' => $userData
        ];
        
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

/**
 * 에이전트 시작 요청
 */
function handleStartAgent($dispatcher, $input) {
    try {
        $mode = $input['mode'] ?? '';
        $userId = $input['user_id'] ?? 'anonymous';
        $userData = $input['user_data'] ?? [];
        
        if (empty($mode)) {
            sendError(400, 'Mode is required');
            return;
        }
        
        $request = [
            'action' => 'start_agent',
            'mode' => $mode,
            'user_id' => $userId,
            'user_data' => $userData
        ];
        
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

/**
 * 에이전트 실행 요청
 */
function handleExecuteAgent($dispatcher, $input) {
    try {
        $agentId = $input['agent_id'] ?? '';
        $executionInput = $input['input'] ?? null;
        
        if (empty($agentId)) {
            sendError(400, 'Agent ID is required');
            return;
        }
        
        $request = [
            'action' => 'execute_agent',
            'agent_id' => $agentId,
            'input' => $executionInput
        ];
        
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

/**
 * 에이전트 중지 요청
 */
function handleStopAgent($dispatcher, $input) {
    try {
        $agentId = $input['agent_id'] ?? '';
        
        if (empty($agentId)) {
            sendError(400, 'Agent ID is required');
            return;
        }
        
        $request = [
            'action' => 'stop_agent',
            'agent_id' => $agentId
        ];
        
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

/**
 * 에이전트 블렌딩 요청
 */
function handleBlendAgents($dispatcher, $input) {
    try {
        $modes = $input['modes'] ?? [];
        $situation = $input['situation'] ?? '';
        $userId = $input['user_id'] ?? 'anonymous';
        $userData = $input['user_data'] ?? [];
        
        if (empty($modes) || !is_array($modes)) {
            sendError(400, 'Modes array is required');
            return;
        }
        
        if (count($modes) < 2) {
            sendError(400, 'At least 2 modes are required for blending');
            return;
        }
        
        $request = [
            'action' => 'blend_agents',
            'modes' => $modes,
            'situation' => $situation,
            'user_id' => $userId,
            'user_data' => $userData
        ];
        
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

/**
 * 에이전트 검증 요청
 */
function handleValidateAgent($dispatcher, $input) {
    try {
        $mode = $input['mode'] ?? '';
        
        if (empty($mode)) {
            sendError(400, 'Mode is required');
            return;
        }
        
        $request = [
            'action' => 'validate_agent',
            'mode' => $mode
        ];
        
        $response = $dispatcher->handleRequest($request);
        sendResponse($response);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

/**
 * 시스템 정리 요청
 */
function handleCleanup($dispatcher) {
    try {
        $cleanedCount = $dispatcher->cleanupSessions();
        sendResponse([
            'message' => 'Cleanup completed',
            'cleaned_agents' => $cleanedCount
        ]);
    } catch (Exception $e) {
        sendError(500, $e->getMessage());
    }
}

/**
 * 성공 응답 전송
 */
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 에러 응답 전송
 */
function sendError($status, $message) {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $status,
            'message' => $message
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}