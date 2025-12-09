<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// CORS 헤더 설정 (필요시)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// 검색 파라미터 받기
$searchType = isset($_GET['searchType']) ? $_GET['searchType'] : '';
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// 응답 배열 초기화
$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    // 검색 쿼리가 비어있으면 전체 목록 반환
    if (empty($searchQuery)) {
        $sql = "SELECT * FROM students ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
    } else {
        // 검색 타입에 따라 쿼리 작성
        switch($searchType) {
            case 'name':
                $sql = "SELECT * FROM students WHERE name LIKE :query ORDER BY name ASC";
                break;
            case 'school':
                $sql = "SELECT * FROM students WHERE school LIKE :query ORDER BY name ASC";
                break;
            case 'grade':
                $sql = "SELECT * FROM students WHERE grade = :query ORDER BY name ASC";
                break;
            case 'all':
            default:
                $sql = "SELECT * FROM students WHERE name LIKE :query OR school LIKE :query OR grade LIKE :query ORDER BY name ASC";
                break;
        }
        
        $stmt = $pdo->prepare($sql);
        
        // 학년 검색인 경우 정확한 매칭, 나머지는 부분 매칭
        if ($searchType === 'grade') {
            $stmt->bindValue(':query', $searchQuery, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':query', '%' . $searchQuery . '%', PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['data'] = $results;
    $response['message'] = count($results) . '명의 학생을 찾았습니다.';
    
} catch(PDOException $e) {
    $response['success'] = false;
    $response['message'] = '데이터베이스 오류: ' . $e->getMessage();
}

// JSON 응답 출력
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>