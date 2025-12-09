<?php
/**
 * Student Manual System - Manual Item Management API
 * File: alt42/orchestration/agents/studentmanual/api/manage_item.php
 *
 * 메뉴얼 항목 CRUD API
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Include error handler
require_once(__DIR__ . '/../includes/error_handler.php');

header('Content-Type: application/json; charset=utf-8');

try {
    // 사용자 역할 확인 (교사만 접근 가능)
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
    $role = $userrole->data ?? 'student';

    if (!in_array($role, ['teacher', 'admin'])) {
        echo StudentManualErrorHandler::jsonError(
            "권한이 없습니다. 교사만 메뉴얼 항목을 관리할 수 있습니다.",
            403,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 요청 메서드 확인
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    switch ($method) {
        case 'GET':
            handleGet($action);
            break;
        case 'POST':
            handlePost($action);
            break;
        case 'PUT':
        case 'PATCH':
            handleUpdate($action);
            break;
        case 'DELETE':
            handleDelete($action);
            break;
        default:
            echo StudentManualErrorHandler::jsonError(
                "지원하지 않는 HTTP 메서드입니다.",
                405,
                ['file' => __FILE__, 'line' => __LINE__]
            );
    }

} catch (Exception $e) {
    $file = __FILE__;
    $line = $e->getLine();
    echo StudentManualErrorHandler::jsonError(
        "오류가 발생했습니다: " . $e->getMessage(),
        500,
        ['file' => $file, 'line' => $line]
    );
}

// GET 요청 처리 (목록 조회 또는 단일 항목 조회)
function handleGet($action) {
    global $DB;

    if ($action === 'list') {
        // 전체 목록 조회
        $items = $DB->get_records('at42_studentmanual_items', null, 'created_at DESC');
        $result = [];
        
        foreach ($items as $item) {
            // 연결된 컨텐츠 조회
            $sql = "SELECT c.*, ic.display_order 
                    FROM {at42_stumanual_item_cnts} ic
                    JOIN {at42_studentmanual_contents} c ON ic.content_id = c.id
                    WHERE ic.item_id = ?
                    ORDER BY ic.display_order ASC";
            $contents = $DB->get_records_sql($sql, [$item->id]);
            
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
        
        echo json_encode([
            'success' => true,
            'count' => count($result),
            'data' => $result
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif (isset($_GET['id'])) {
        // 단일 항목 조회
        $itemId = intval($_GET['id']);
        $item = StudentManualErrorHandler::safeGetRecord($DB, 'at42_studentmanual_items', ['id' => $itemId]);
        
        if (!$item) {
            echo StudentManualErrorHandler::jsonError(
                "메뉴얼 항목을 찾을 수 없습니다.",
                404,
                ['file' => __FILE__, 'line' => __LINE__]
            );
            exit;
        }
        
        // 연결된 컨텐츠 조회
        $sql = "SELECT c.*, ic.display_order 
                FROM {at42_studentmanual_item_contents} ic
                JOIN {at42_studentmanual_contents} c ON ic.content_id = c.id
                WHERE ic.item_id = ?
                ORDER BY ic.display_order ASC";
        $contents = $DB->get_records_sql($sql, [$itemId]);
        
        $item->contents = array_values($contents);
        
        echo json_encode([
            'success' => true,
            'data' => $item
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo StudentManualErrorHandler::jsonError(
            "잘못된 요청입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
    }
}

// POST 요청 처리 (생성)
function handlePost($action) {
    global $DB, $USER;

    if ($action !== 'create') {
        echo StudentManualErrorHandler::jsonError(
            "잘못된 액션입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 필수 필드 확인
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $agentId = isset($_POST['agent_id']) ? trim($_POST['agent_id']) : '';

    if (empty($title) || empty($agentId)) {
        echo StudentManualErrorHandler::jsonError(
            "제목과 에이전트 ID는 필수입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 메뉴얼 항목 생성
    $item = new stdClass();
    $item->title = $title;
    $item->description = $description;
    $item->agent_id = $agentId;
    $item->created_at = time();
    $item->updated_at = null;
    $item->created_by = $USER->id;

    $itemId = StudentManualErrorHandler::safeInsertRecord($DB, 'at42_studentmanual_items', $item);
    
    if (!$itemId) {
        echo StudentManualErrorHandler::jsonError(
            "메뉴얼 항목 생성에 실패했습니다.",
            500,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 컨텐츠 연결 처리
    if (isset($_POST['content_ids']) && is_array($_POST['content_ids'])) {
        foreach ($_POST['content_ids'] as $index => $contentId) {
            $contentId = intval($contentId);
            if ($contentId > 0) {
                $link = new stdClass();
                $link->item_id = $itemId;
                $link->content_id = $contentId;
                $link->display_order = $index;
                StudentManualErrorHandler::safeInsertRecord($DB, 'at42_stumanual_item_cnts', $link);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'item_id' => $itemId,
        'message' => '메뉴얼 항목이 성공적으로 생성되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

// UPDATE 요청 처리
function handleUpdate($action) {
    global $DB, $USER;

    if ($action !== 'update') {
        echo StudentManualErrorHandler::jsonError(
            "잘못된 액션입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // PUT/PATCH 데이터 파싱
    parse_str(file_get_contents('php://input'), $putData);
    $data = array_merge($_POST, $putData);

    $itemId = isset($data['id']) ? intval($data['id']) : 0;
    if ($itemId <= 0) {
        echo StudentManualErrorHandler::jsonError(
            "유효하지 않은 항목 ID입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 기존 항목 조회
    $item = StudentManualErrorHandler::safeGetRecord($DB, 'at42_studentmanual_items', ['id' => $itemId]);
    if (!$item) {
        echo StudentManualErrorHandler::jsonError(
            "메뉴얼 항목을 찾을 수 없습니다.",
            404,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 업데이트할 필드 설정
    if (isset($data['title'])) $item->title = trim($data['title']);
    if (isset($data['description'])) $item->description = trim($data['description']);
    if (isset($data['agent_id'])) $item->agent_id = trim($data['agent_id']);
    $item->updated_at = time();

    $success = StudentManualErrorHandler::safeUpdateRecord($DB, 'at42_studentmanual_items', $item);
    
    if (!$success) {
        echo StudentManualErrorHandler::jsonError(
            "메뉴얼 항목 업데이트에 실패했습니다.",
            500,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 컨텐츠 연결 업데이트
    if (isset($data['content_ids']) && is_array($data['content_ids'])) {
        // 기존 연결 삭제
        StudentManualErrorHandler::safeDeleteRecord($DB, 'at42_stumanual_item_cnts', ['item_id' => $itemId]);
        
        // 새로운 연결 생성
        foreach ($data['content_ids'] as $index => $contentId) {
            $contentId = intval($contentId);
            if ($contentId > 0) {
                $link = new stdClass();
                $link->item_id = $itemId;
                $link->content_id = $contentId;
                $link->display_order = $index;
                StudentManualErrorHandler::safeInsertRecord($DB, 'at42_stumanual_item_cnts', $link);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'item_id' => $itemId,
        'message' => '메뉴얼 항목이 성공적으로 업데이트되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

// DELETE 요청 처리
function handleDelete($action) {
    global $DB;

    if ($action !== 'delete') {
        echo StudentManualErrorHandler::jsonError(
            "잘못된 액션입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    $itemId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($itemId <= 0) {
        echo StudentManualErrorHandler::jsonError(
            "유효하지 않은 항목 ID입니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 연결된 컨텐츠 연결 삭제
    StudentManualErrorHandler::safeDeleteRecord($DB, 'at42_studentmanual_item_contents', ['item_id' => $itemId]);
    
    // 메뉴얼 항목 삭제
    $success = StudentManualErrorHandler::safeDeleteRecord($DB, 'at42_studentmanual_items', ['id' => $itemId]);
    
    if (!$success) {
        echo StudentManualErrorHandler::jsonError(
            "메뉴얼 항목 삭제에 실패했습니다.",
            500,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => '메뉴얼 항목이 성공적으로 삭제되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

