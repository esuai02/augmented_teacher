<?php
/**
 * Agent Garden Controller
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.controller.php
 * 
 * 에이전트 가든 UI 컨트롤러
 */

// 전역 에러 핸들러 설정 (디버깅용)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[AgentGarden Fatal] Error {$errno}: {$errstr} in {$errfile}:{$errline}");
    if (error_reporting() !== 0) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'PHP Error',
            'message' => "{$errstr} in {$errfile}:{$errline}",
            'errno' => $errno
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// 예외 핸들러 설정
set_exception_handler(function($e) {
    error_log("[AgentGarden Exception] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 실행 시간 제한 증가 (Python 스크립트 실행 시간 및 리포트 생성 시간 고려)
set_time_limit(120); // 120초 (리포트 생성 시간 고려)
ini_set('max_execution_time', 120);

require_once(__DIR__ . '/agent_garden.service.php');
require_once(__DIR__ . '/agent_garden.model.php');

class AgentGardenController { 
    private $service; 
    private $model;

    public function __construct() {
        $this->service = new AgentGardenService();
        $this->model = new AgentGardenModel();
    }

    /**
     * 에이전트 목록 조회
     */
    public function getAgentList() {
        try {
            $agents = $this->model->getAllAgents();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $agents
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log(sprintf(
                '[Agent Garden] File: %s, Line: %d, Error: %s',
                __FILE__,
                __LINE__,
                $e->getMessage()
            ));
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => '에이전트 목록 조회 실패',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 에이전트 실행
     */
    public function executeAgent() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['agent_id']) || !isset($input['request'])) {
                throw new Exception('필수 파라미터가 누락되었습니다. [File: ' . __FILE__ . ', Line: ' . __LINE__ . ']');
            }

            $agentId = $input['agent_id'];
            $request = $input['request'];
            
            // 디버깅: 입력값 확인
            error_log(sprintf(
                '[Agent Garden] Debug - GET userid: %s, POST student_id: %s, USER->id: %s [File: %s, Line: %d]',
                isset($_GET['userid']) ? $_GET['userid'] : 'not set',
                isset($input['student_id']) ? $input['student_id'] : 'not set',
                isset($USER->id) ? $USER->id : 'not set',
                __FILE__,
                __LINE__
            ));
            
            // student_id 우선순위: 1. URL 파라미터 userid, 2. POST 요청의 student_id, 3. 현재 로그인한 사용자 ID
            $studentId = null;
            
            // 1. URL 파라미터 userid 확인
            if (isset($_GET['userid']) && $_GET['userid'] !== '' && $_GET['userid'] !== null) {
                $studentId = intval($_GET['userid']);
                error_log(sprintf('[Agent Garden] Using GET userid: %d [File: %s, Line: %d]', $studentId, __FILE__, __LINE__));
            }
            // 2. POST 요청의 student_id 확인
            elseif (isset($input['student_id']) && $input['student_id'] !== '' && $input['student_id'] !== null) {
                $studentId = intval($input['student_id']);
                error_log(sprintf('[Agent Garden] Using POST student_id: %d [File: %s, Line: %d]', $studentId, __FILE__, __LINE__));
            }
            // 3. 현재 로그인한 사용자 ID 확인
            elseif (isset($USER->id) && $USER->id > 0) {
                $studentId = intval($USER->id);
                error_log(sprintf('[Agent Garden] Using USER->id: %d [File: %s, Line: %d]', $studentId, __FILE__, __LINE__));
            }
            
            // 최종 검증 (0보다 큰 정수여야 함)
            if ($studentId === null || $studentId <= 0) {
                $errorMsg = sprintf(
                    'student_id를 확인할 수 없습니다. GET userid: %s, POST student_id: %s, USER->id: %s [File: %s, Line: %d]',
                    isset($_GET['userid']) ? $_GET['userid'] : 'not set',
                    isset($input['student_id']) ? $input['student_id'] : 'not set',
                    isset($USER->id) ? $USER->id : 'not set',
                    __FILE__,
                    __LINE__
                );
                error_log('[Agent Garden] ' . $errorMsg);
                throw new Exception($errorMsg);
            }
        
            error_log(sprintf('[Agent Garden] Final student_id: %d [File: %s, Line: %d]', $studentId, __FILE__, __LINE__));

            $result = $this->service->executeAgent($agentId, $request, $studentId);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            
            error_log(sprintf(
                '[Agent Garden] File: %s, Line: %d, Error: %s, Trace: %s',
                __FILE__,
                __LINE__,
                $e->getMessage(),
                $e->getTraceAsString()
            ));
            
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => '에이전트 실행 실패',
                'message' => $e->getMessage(),
                'error_details' => $errorDetails
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

// 라우팅 처리
$action = $_GET['action'] ?? '';

$controller = new AgentGardenController();

switch ($action) {
    case 'get_agents':
        $controller->getAgentList();
        break;
    case 'execute':
        $controller->executeAgent();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '잘못된 요청'
        ], JSON_UNESCAPED_UNICODE);
        break;
}

