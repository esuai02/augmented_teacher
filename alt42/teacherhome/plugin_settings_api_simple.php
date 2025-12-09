<?php
/**
 * 간단한 플러그인 설정 API (GET 요청용)
 * 작성일: 2025-01-16
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 에러 표시 (개발용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // DB 설정 파일 포함
    require_once 'plugin_db_config.php';
    
    // GET 파라미터 확인
    $action = $_GET['action'] ?? '';
    $user_id = $_GET['user_id'] ?? 1;
    $category = $_GET['category'] ?? null;
    
    // DB 연결
    $pdo = getDBConnection();
    
    if ($action === 'load') {
        // 카드 플러그인 설정 조회
        $sql = "SELECT * FROM mdl_alt42DB_card_plugin_settings WHERE user_id = ?";
        $params = [$user_id];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY display_order, card_index";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'cards' => $cards,
            'count' => count($cards),
            'user_id' => $user_id,
            'category' => $category
        ]);
        
    } elseif ($action === 'save') {
        // POST 데이터로 저장 (필요시 구현)
        echo json_encode([
            'success' => false,
            'error' => 'Save action requires POST method'
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>