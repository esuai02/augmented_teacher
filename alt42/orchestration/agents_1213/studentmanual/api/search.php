<?php
/**
 * Student Manual System - Search API
 * File: alt42/orchestration/agents/studentmanual/api/search.php
 *
 * 메뉴얼 항목 검색 API 엔드포인트
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Include error handler
require_once(__DIR__ . '/../includes/error_handler.php');

header('Content-Type: application/json; charset=utf-8');

try {
    // 요청 메서드 확인
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'GET' && $method !== 'POST') {
        echo StudentManualErrorHandler::jsonError(
            "지원하지 않는 HTTP 메서드입니다.",
            405,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 파라미터 가져오기
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    $agentId = isset($_REQUEST['agent_id']) ? trim($_REQUEST['agent_id']) : '';
    $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'created_at'; // created_at, title
    $order = isset($_REQUEST['order']) ? strtoupper($_REQUEST['order']) : 'DESC'; // ASC, DESC

    // 정렬 필드 검증
    $allowedSorts = ['created_at', 'title', 'updated_at'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'created_at';
    }

    // 정렬 순서 검증
    if ($order !== 'ASC' && $order !== 'DESC') {
        $order = 'DESC';
    }

    // 기본 쿼리 구성
    $sql = "SELECT i.* 
            FROM {at42_studentmanual_items} i
            WHERE 1=1";
    $params = [];

    // 키워드 검색
    if (!empty($keyword)) {
        $sql .= " AND (i.title LIKE ? OR i.description LIKE ?)";
        $searchTerm = '%' . $keyword . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // 에이전트 필터
    if (!empty($agentId) && $agentId !== 'all') {
        $sql .= " AND i.agent_id = ?";
        $params[] = $agentId;
    }

    // 정렬
    $sql .= " ORDER BY i.{$sort} {$order}";

    // 쿼리 실행
    $items = $DB->get_records_sql($sql, $params);

    // 각 항목의 컨텐츠 조회
    $result = [];
    foreach ($items as $item) {
        // 연결된 컨텐츠 조회
        $contentSql = "SELECT c.*, ic.display_order 
                        FROM {at42_stumanual_item_cnts} ic
                        JOIN {at42_studentmanual_contents} c ON ic.content_id = c.id
                        WHERE ic.item_id = ?
                        ORDER BY ic.display_order ASC";
        $contents = $DB->get_records_sql($contentSql, [$item->id]);

        $result[] = [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'agent_id' => $item->agent_id,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'created_by' => $item->created_by,
            'contents' => array_values($contents)
        ];
    }

    // 성공 응답
    echo json_encode([
        'success' => true,
        'count' => count($result),
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $file = __FILE__;
    $line = $e->getLine();
    echo StudentManualErrorHandler::jsonError(
        "검색 중 오류가 발생했습니다: " . $e->getMessage(),
        500,
        ['file' => $file, 'line' => $line]
    );
}

