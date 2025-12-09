<?php
/**
 * 수학 학습 패턴 데이터 API
 * DB에서 패턴 목록을 가져와 JSON으로 반환
 */

// CORS 헤더 설정 (먼저 설정)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Moodle 설정 포함
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER, $CFG;

// 로그인 확인
require_login();

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : $USER->id;

try {
    // 1. 카테고리 데이터 가져오기
    $categories = $DB->get_records('alt42i_pattern_categories', null, 'id ASC');
    $category_data = [];
    
    // 카테고리 코드 매핑
    $category_codes = [
        1 => 'cognitive_overload',
        2 => 'confidence_distortion',
        3 => 'mistake_patterns',
        4 => 'approach_errors',
        5 => 'study_habits',
        6 => 'time_pressure',
        7 => 'verification_absence',
        8 => 'other_obstacles'
    ];
    
    foreach ($categories as $cat) {
        $code = isset($category_codes[$cat->id]) ? $category_codes[$cat->id] : 'other_obstacles';
        $category_data[] = [
            'id' => $cat->id,
            'code' => $code,
            'name' => $cat->category_name,
            'order' => $cat->id,
            'color' => getCategoryColor($code),
            'emoji' => getCategoryEmoji($code)
        ];
    }
    
    // 2. 패턴 데이터 가져오기
    $patterns = $DB->get_records_sql("
        SELECT 
            p.id,
            p.id as pattern_id,
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
        ORDER BY p.id ASC
    ");
    
    $pattern_data = [];
    
    foreach ($patterns as $pattern) {
        // 오디오 파일 URL 생성
        $pattern_id_padded = str_pad($pattern->pattern_id, 2, '0', STR_PAD_LEFT);
        $audio_url = "http://mathking.kr/Contents/personas/mathlearning/thinkinginertia{$pattern_id_padded}.mp3";
        
        // 카테고리 코드 가져오기
        $category_code = isset($category_codes[$pattern->category_id]) ? $category_codes[$pattern->category_id] : 'other_obstacles';
        
        $pattern_data[] = [
            'id' => $pattern->id,
            'pattern_id' => intval($pattern->pattern_id),
            'pattern_name' => $pattern->pattern_name,
            'pattern_desc' => $pattern->pattern_desc,
            'category_id' => $pattern->category_id,
            'category_name' => $pattern->category_name,
            'category_code' => $category_code,
            'icon' => $pattern->icon,
            'priority' => $pattern->priority,
            'audio_time' => $pattern->audio_time,
            'action' => $pattern->action,
            'check_method' => $pattern->check_method,
            'audio_script' => $pattern->audio_script,
            'teacher_dialog' => $pattern->teacher_dialog,
            'audio_url' => $audio_url
        ];
    }
    
    // 3. 사용자 진행 상황 가져오기 (테이블이 존재하는 경우)
    $progress_data = [];
    if ($user_id > 0) {
        try {
            // 먼저 테이블 존재 확인
            if ($DB->get_manager()->table_exists('alt42i_user_pattern_progress')) {
                $progress_records = $DB->get_records('alt42i_user_pattern_progress', ['user_id' => $user_id]);
                
                foreach ($progress_records as $progress) {
                    $progress_data[$progress->pattern_id] = [
                        'is_collected' => (bool)$progress->is_collected,
                        'mastery_level' => intval($progress->mastery_level),
                        'practice_count' => intval($progress->practice_count),
                        'last_practice_at' => $progress->last_practice_at,
                        'notes' => $progress->notes
                    ];
                }
            }
        } catch (Exception $e) {
            // 사용자 진행 상황이 없어도 계속 진행
            error_log("User progress fetch error: " . $e->getMessage());
        }
    }
    
    // 응답 데이터 구성
    $response = [
        'success' => true,
        'categories' => $category_data,
        'patterns' => $pattern_data,
        'progress' => $progress_data
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Math patterns API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'debug' => [
            'user_id' => $user_id,
            'input' => $input
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 카테고리별 색상 반환
 */
function getCategoryColor($code) {
    $colors = [
        'cognitive_overload' => '#667eea',
        'confidence_distortion' => '#764ba2',
        'mistake_patterns' => '#f59e0b',
        'approach_errors' => '#ef4444',
        'study_habits' => '#10b981',
        'time_pressure' => '#3b82f6',
        'verification_absence' => '#8b5cf6',
        'other_obstacles' => '#6b7280'
    ];
    return isset($colors[$code]) ? $colors[$code] : '#667eea';
}

/**
 * 카테고리별 이모지 반환
 */
function getCategoryEmoji($code) {
    $emojis = [
        'cognitive_overload' => '🧠',
        'confidence_distortion' => '😰',
        'mistake_patterns' => '❌',
        'approach_errors' => '🎯',
        'study_habits' => '📚',
        'time_pressure' => '⏰',
        'verification_absence' => '✔️',
        'other_obstacles' => '🔧'
    ];
    return isset($emojis[$code]) ? $emojis[$code] : '📚';
}
?>