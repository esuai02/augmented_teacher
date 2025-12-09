<?php
// 진행 상황 관리 API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 데이터베이스 연결 (실제 환경에서는 별도 설정 파일 사용)
function getDB() {
    // 임시로 로컬 스토리지 시뮬레이션
    return null;
}

// 요청 메서드 확인
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// 세션에서 사용자 ID 가져오기 (실제 환경에서는 인증 구현 필요)
session_start();
$userId = $_SESSION['user_id'] ?? 1; // 테스트용 기본값

switch ($method) {
    case 'GET':
        // 진행 상황 조회
        if (isset($_GET['card_id'])) {
            $cardId = $_GET['card_id'];
            $progress = getCardProgress($userId, $cardId);
            echo json_encode(['success' => true, 'data' => $progress]);
        } else {
            $progress = getAllProgress($userId);
            echo json_encode(['success' => true, 'data' => $progress]);
        }
        break;
        
    case 'POST':
        // 진행 상황 업데이트
        if (isset($input['project_id']) && isset($input['is_completed'])) {
            $result = updateProgress($userId, $input['project_id'], $input['is_completed'], $input['item_type'] ?? 'subproject');
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
        }
        break;
        
    case 'PUT':
        // 진행 상황 일괄 업데이트
        if (isset($input['updates']) && is_array($input['updates'])) {
            $results = [];
            foreach ($input['updates'] as $update) {
                $results[] = updateProgress($userId, $update['project_id'], $update['is_completed'], $update['item_type'] ?? 'subproject');
            }
            echo json_encode(['success' => true, 'results' => $results]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
        }
        break;
        
    case 'DELETE':
        // 진행 상황 초기화
        if (isset($_GET['card_id'])) {
            $result = resetCardProgress($userId, $_GET['card_id']);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Card ID required']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

// 함수 구현 (실제 환경에서는 데이터베이스 쿼리 사용)
function getCardProgress($userId, $cardId) {
    // 임시 구현 - 로컬 스토리지에서 데이터 가져오기 시뮬레이션
    $progressFile = __DIR__ . "/../data/progress_{$userId}_{$cardId}.json";
    if (file_exists($progressFile)) {
        return json_decode(file_get_contents($progressFile), true);
    }
    return [];
}

function getAllProgress($userId) {
    // 임시 구현
    $progressFile = __DIR__ . "/../data/progress_{$userId}_all.json";
    if (file_exists($progressFile)) {
        return json_decode(file_get_contents($progressFile), true);
    }
    return [];
}

function updateProgress($userId, $projectId, $isCompleted, $itemType) {
    // 임시 구현 - 실제로는 데이터베이스에 저장
    $dataDir = __DIR__ . "/../data";
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    
    $progressFile = $dataDir . "/progress_{$userId}_all.json";
    $progress = [];
    
    if (file_exists($progressFile)) {
        $progress = json_decode(file_get_contents($progressFile), true);
    }
    
    $progress[$projectId] = [
        'is_completed' => $isCompleted,
        'item_type' => $itemType,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return file_put_contents($progressFile, json_encode($progress)) !== false;
}

function resetCardProgress($userId, $cardId) {
    // 임시 구현
    $progressFile = __DIR__ . "/../data/progress_{$userId}_{$cardId}.json";
    if (file_exists($progressFile)) {
        return unlink($progressFile);
    }
    return true;
}
?>