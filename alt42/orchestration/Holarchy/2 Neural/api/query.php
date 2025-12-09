<?php
/**
 * 홀론 신경망 쿼리 API
 * 쿼리를 받아 연관 홀론을 활성화하고 필요한 정보만 반환
 * 
 * 파일: alt42/orchestration/Holarchy/2 Neural/api/query.php
 * 
 * API 사용법:
 * GET  ?action=query&q=TTS 중복 재생 오류
 * GET  ?action=drill&coord=meeting-2025-007.X.issues[1]
 * GET  ?action=propagate&holon_id=meeting-2025-007
 * GET  ?action=summary
 * POST { "action": "query", "query": "...", "options": {...} }
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once __DIR__ . '/../activation/activation_controller.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

class NeuralQueryAPI {
    private $controller;
    private $baseDir;
    private $indexPath;
    
    public function __construct() {
        $this->baseDir = dirname(__DIR__, 2) . '/0 Docs';
        $this->indexPath = dirname(__DIR__) . '/index/holon_index.json';
        $this->controller = new ActivationController($this->baseDir, $this->indexPath, 4000);
    }
    
    public function handleRequest() {
        try {
            // POST 또는 GET 파라미터 가져오기
            $input = $this->getInput();
            $action = $input['action'] ?? 'query';
            
            switch ($action) {
                case 'query':
                    return $this->handleQuery($input);
                    
                case 'drill':
                    return $this->handleDrill($input);
                    
                case 'propagate':
                    return $this->handlePropagate($input);
                    
                case 'summary':
                    return $this->handleSummary();
                    
                case 'index_status':
                    return $this->handleIndexStatus();
                    
                default:
                    return $this->error('알 수 없는 action: ' . $action);
            }
            
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
    
    private function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?: [];
        } else {
            $input = $_REQUEST;
        }
        
        return $input;
    }
    
    /**
     * 쿼리 처리 - 연관 홀론 활성화
     */
    private function handleQuery($input) {
        $query = $input['q'] ?? $input['query'] ?? '';
        
        if (empty($query)) {
            return $this->error('쿼리가 필요합니다 (q 또는 query 파라미터)');
        }
        
        $options = [
            'threshold' => floatval($input['threshold'] ?? 0.1),
            'topK' => intval($input['topK'] ?? 5),
            'expandSections' => ($input['expand'] ?? 'true') === 'true',
            'maxDepth' => intval($input['maxDepth'] ?? 2)
        ];
        
        $result = $this->controller->activate($query, $options);
        
        return $this->success($result);
    }
    
    /**
     * 드릴다운 - 특정 좌표 조회
     */
    private function handleDrill($input) {
        $coordinate = $input['coord'] ?? $input['coordinate'] ?? '';
        
        if (empty($coordinate)) {
            return $this->error('좌표가 필요합니다 (coord 파라미터)');
        }
        
        $result = $this->controller->drillDown($coordinate);
        
        return $this->success($result);
    }
    
    /**
     * 전파 - 연결된 홀론 탐색
     */
    private function handlePropagate($input) {
        $holonId = $input['holon_id'] ?? $input['id'] ?? '';
        
        if (empty($holonId)) {
            return $this->error('holon_id가 필요합니다');
        }
        
        $depth = intval($input['depth'] ?? 1);
        $result = $this->controller->propagate($holonId, $depth);
        
        return $this->success($result);
    }
    
    /**
     * 요약 - 현재 활성화 상태
     */
    private function handleSummary() {
        $result = $this->controller->getSummary();
        return $this->success($result);
    }
    
    /**
     * 인덱스 상태 확인
     */
    private function handleIndexStatus() {
        if (!file_exists($this->indexPath)) {
            return $this->error('인덱스 파일이 없습니다. build_index.php를 먼저 실행하세요.');
        }
        
        $index = json_decode(file_get_contents($this->indexPath), true);
        
        return $this->success([
            'index_exists' => true,
            'version' => $index['version'] ?? 'unknown',
            'built_at' => $index['built_at'] ?? null,
            'holon_count' => count($index['holons'] ?? []),
            'holons' => array_keys($index['holons'] ?? [])
        ]);
    }
    
    private function success($data) {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }
    
    private function error($message, $file = null, $line = null) {
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ];
        
        if ($file) {
            $response['debug'] = [
                'file' => basename($file),
                'line' => $line
            ];
        }
        
        return $response;
    }
}

// 실행
$api = new NeuralQueryAPI();
$response = $api->handleRequest();

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

