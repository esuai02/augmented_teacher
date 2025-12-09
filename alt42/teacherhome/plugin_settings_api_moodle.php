<?php
/**
 * ALT42 플러그인 설정 API - Moodle 연동 버전
 * 작성일: 2025-01-15
 * 설명: Moodle config.php를 사용하여 플러그인 설정을 관리하는 API
 */

// Moodle 설정 파일 include
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 로그인 확인
require_login();

// 요청 메서드 및 액션 확인
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user_id = $USER->id; // 현재 로그인한 사용자 ID 사용

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $user_id);
            break;
        case 'POST':
            handlePostRequest($action, $user_id);
            break;
        case 'PUT':
            handlePutRequest($action, $user_id);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $user_id);
            break;
        default:
            throw new Exception('지원하지 않는 메서드입니다.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * GET 요청 처리
 */
function handleGetRequest($action, $user_id) {
    global $DB;
    
    switch ($action) {
        case 'plugin_types':
            // 모든 플러그인 타입 조회
            $plugins = $DB->get_records('alt42DB_plugin_types', ['is_active' => 1]);
            echo json_encode(array_values($plugins));
            break;
            
        case 'user_settings':
            // 사용자별 플러그인 설정 조회
            $plugin_id = $_GET['plugin_id'] ?? null;
            $category = $_GET['category'] ?? null;
            
            $conditions = ['user_id' => $user_id];
            if ($plugin_id) $conditions['plugin_id'] = $plugin_id;
            if ($category) $conditions['category'] = $category;
            
            $settings = $DB->get_records('alt42DB_user_plugin_settings', $conditions);
            echo json_encode(array_values($settings));
            break;
            
        case 'card_settings':
            // 카드별 플러그인 설정 조회
            $category = $_GET['category'] ?? null;
            $card_title = $_GET['card_title'] ?? null;
            
            $conditions = ['user_id' => $user_id];
            if ($category) $conditions['category'] = $category;
            if ($card_title) $conditions['card_title'] = $card_title;
            
            $settings = $DB->get_records('alt42DB_card_plugin_settings', $conditions);
            echo json_encode(array_values($settings));
            break;
            
        case 'usage_stats':
            // 플러그인 사용 통계 조회
            $plugin_id = $_GET['plugin_id'] ?? null;
            
            $conditions = ['user_id' => $user_id];
            if ($plugin_id) $conditions['plugin_id'] = $plugin_id;
            
            $stats = $DB->get_records('alt42DB_plugin_usage_stats', $conditions);
            echo json_encode(array_values($stats));
            break;
            
        default:
            throw new Exception('잘못된 액션입니다.');
    }
}

/**
 * POST 요청 처리
 */
function handlePostRequest($action, $user_id) {
    global $DB;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'user_setting':
            // 사용자 플러그인 설정 저장
            $record = new stdClass();
            $record->user_id = $user_id;
            $record->plugin_id = $data['plugin_id'];
            $record->setting_name = $data['setting_name'];
            $record->setting_value = json_encode($data['setting_value']);
            $record->category = $data['category'] ?? null;
            $record->is_enabled = $data['is_enabled'] ?? 1;
            $record->timecreated = time();
            $record->timemodified = time();
            
            // 중복 체크
            $existing = $DB->get_record('alt42DB_user_plugin_settings', [
                'user_id' => $user_id,
                'plugin_id' => $data['plugin_id'],
                'setting_name' => $data['setting_name'],
                'category' => $data['category'] ?? null
            ]);
            
            if ($existing) {
                // 업데이트
                $record->id = $existing->id;
                $record->timecreated = $existing->timecreated;
                
                // 히스토리 저장
                saveHistory($user_id, $data['plugin_id'], 'user_setting', $existing->id, 
                           $existing->setting_value, $record->setting_value);
                
                $DB->update_record('alt42DB_user_plugin_settings', $record);
                $id = $existing->id;
            } else {
                // 신규 저장
                $id = $DB->insert_record('alt42DB_user_plugin_settings', $record);
            }
            
            echo json_encode(['success' => true, 'id' => $id]);
            break;
            
        case 'card_setting':
            // 카드별 플러그인 설정 저장
            $record = new stdClass();
            $record->user_id = $user_id;
            $record->category = $data['category'];
            $record->card_title = $data['card_title'];
            $record->card_index = $data['card_index'] ?? 0;
            $record->plugin_id = $data['plugin_id'];
            $record->plugin_config = json_encode($data['plugin_config']);
            $record->is_active = $data['is_active'] ?? 1;
            $record->display_order = $data['display_order'] ?? 0;
            $record->timecreated = time();
            $record->timemodified = time();
            
            // 중복 체크
            $existing = $DB->get_record('alt42DB_card_plugin_settings', [
                'user_id' => $user_id,
                'category' => $data['category'],
                'card_title' => $data['card_title'],
                'plugin_id' => $data['plugin_id']
            ]);
            
            if ($existing) {
                // 업데이트
                $record->id = $existing->id;
                $record->timecreated = $existing->timecreated;
                
                // 히스토리 저장
                saveHistory($user_id, $data['plugin_id'], 'card_setting', $existing->id, 
                           $existing->plugin_config, $record->plugin_config);
                
                $DB->update_record('alt42DB_card_plugin_settings', $record);
                $id = $existing->id;
            } else {
                // 신규 저장
                $id = $DB->insert_record('alt42DB_card_plugin_settings', $record);
            }
            
            echo json_encode(['success' => true, 'id' => $id]);
            break;
            
        case 'track_usage':
            // 플러그인 사용 통계 업데이트
            $conditions = [
                'user_id' => $user_id,
                'plugin_id' => $data['plugin_id'],
                'category' => $data['category'] ?? null,
                'card_title' => $data['card_title'] ?? null
            ];
            
            $stats = $DB->get_record('alt42DB_plugin_usage_stats', $conditions);
            
            if ($stats) {
                // 기존 통계 업데이트
                $stats->execution_count++;
                $stats->last_execution = time();
                $stats->execution_data = json_encode($data['execution_data'] ?? []);
                $stats->timemodified = time();
                
                $DB->update_record('alt42DB_plugin_usage_stats', $stats);
            } else {
                // 새 통계 생성
                $stats = new stdClass();
                $stats->user_id = $user_id;
                $stats->plugin_id = $data['plugin_id'];
                $stats->category = $data['category'] ?? null;
                $stats->card_title = $data['card_title'] ?? null;
                $stats->execution_count = 1;
                $stats->last_execution = time();
                $stats->execution_data = json_encode($data['execution_data'] ?? []);
                $stats->timecreated = time();
                $stats->timemodified = time();
                
                $DB->insert_record('alt42DB_plugin_usage_stats', $stats);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('잘못된 액션입니다.');
    }
}

/**
 * PUT 요청 처리
 */
function handlePutRequest($action, $user_id) {
    global $DB;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'toggle_user_setting':
            // 사용자 설정 활성화/비활성화 토글
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('ID가 필요합니다.');
            
            $setting = $DB->get_record('alt42DB_user_plugin_settings', ['id' => $id, 'user_id' => $user_id]);
            if (!$setting) throw new Exception('설정을 찾을 수 없습니다.');
            
            $setting->is_enabled = $setting->is_enabled ? 0 : 1;
            $setting->timemodified = time();
            
            $DB->update_record('alt42DB_user_plugin_settings', $setting);
            
            echo json_encode(['success' => true, 'is_enabled' => $setting->is_enabled]);
            break;
            
        case 'toggle_card_setting':
            // 카드 설정 활성화/비활성화 토글
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('ID가 필요합니다.');
            
            $setting = $DB->get_record('alt42DB_card_plugin_settings', ['id' => $id, 'user_id' => $user_id]);
            if (!$setting) throw new Exception('설정을 찾을 수 없습니다.');
            
            $setting->is_active = $setting->is_active ? 0 : 1;
            $setting->timemodified = time();
            
            $DB->update_record('alt42DB_card_plugin_settings', $setting);
            
            echo json_encode(['success' => true, 'is_active' => $setting->is_active]);
            break;
            
        default:
            throw new Exception('잘못된 액션입니다.');
    }
}

/**
 * DELETE 요청 처리
 */
function handleDeleteRequest($action, $user_id) {
    global $DB;
    
    switch ($action) {
        case 'user_setting':
            // 사용자 설정 삭제
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('ID가 필요합니다.');
            
            $setting = $DB->get_record('alt42DB_user_plugin_settings', ['id' => $id, 'user_id' => $user_id]);
            if (!$setting) throw new Exception('설정을 찾을 수 없습니다.');
            
            $DB->delete_records('alt42DB_user_plugin_settings', ['id' => $id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'card_setting':
            // 카드 설정 삭제
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('ID가 필요합니다.');
            
            $setting = $DB->get_record('alt42DB_card_plugin_settings', ['id' => $id, 'user_id' => $user_id]);
            if (!$setting) throw new Exception('설정을 찾을 수 없습니다.');
            
            $DB->delete_records('alt42DB_card_plugin_settings', ['id' => $id]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('잘못된 액션입니다.');
    }
}

/**
 * 히스토리 저장 함수
 */
function saveHistory($user_id, $plugin_id, $setting_type, $reference_id, $old_value, $new_value, $reason = null) {
    global $DB;
    
    $history = new stdClass();
    $history->user_id = $user_id;
    $history->plugin_id = $plugin_id;
    $history->setting_type = $setting_type;
    $history->reference_id = $reference_id;
    $history->old_value = $old_value;
    $history->new_value = $new_value;
    $history->change_reason = $reason;
    $history->timecreated = time();
    
    $DB->insert_record('alt42DB_plugin_settings_history', $history);
}
?>