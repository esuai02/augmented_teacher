<?php
/**
 * 패턴 상세 정보 API
 * 특정 패턴의 모든 정보를 가져옴
 */

// Moodle 설정 포함
require_once(__DIR__ . '/../../../../../../../config.php');
global $DB, $USER, $CFG;

// 로그인 확인
require_login();

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$pattern_id = isset($input['pattern_id']) ? intval($input['pattern_id']) : 0;
$user_id = isset($input['user_id']) ? intval($input['user_id']) : $USER->id;

try {
    // 패턴 정보 가져오기
    $pattern = $DB->get_record_sql("
        SELECT 
            p.id,
            p.name as pattern_name,
            p.description as pattern_desc,
            p.category_id,
            p.icon,
            p.priority,
            p.audio_time,
            c.category_name,
            s.action,
            s.check_method,
            s.audio_script,
            s.teacher_dialog
        FROM {alt42i_math_patterns} p
        JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
        JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
        WHERE p.id = ?
    ", [$pattern_id]);
    
    if (!$pattern) {
        throw new Exception('Pattern not found');
    }
    
    // 사용자 진행 상황 가져오기
    $progress = $DB->get_record('alt42i_user_pattern_progress', [
        'user_id' => $user_id,
        'pattern_id' => $pattern_id
    ]);
    
    // 응답 데이터 구성
    $response = [
        'success' => true,
        'pattern' => [
            'id' => $pattern->id,
            'pattern_name' => $pattern->pattern_name,
            'pattern_desc' => $pattern->pattern_desc,
            'category_id' => $pattern->category_id,
            'category_name' => $pattern->category_name,
            'icon' => $pattern->icon,
            'priority' => $pattern->priority,
            'audio_time' => $pattern->audio_time,
            'action' => $pattern->action,
            'check_method' => $pattern->check_method,
            'audio_script' => $pattern->audio_script,
            'teacher_dialog' => $pattern->teacher_dialog
        ],
        'progress' => $progress ? [
            'is_collected' => (bool)$progress->is_collected,
            'mastery_level' => (int)$progress->mastery_level,
            'practice_count' => (int)$progress->practice_count,
            'last_practice_at' => $progress->last_practice_at,
            'notes' => $progress->notes
        ] : null
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}