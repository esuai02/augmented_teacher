<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php"); // OpenAI API 설정 포함
global $DB,$USER;
require_login();

// GET 파라미터에서 userid 가져오기, 없으면 현재 로그인한 사용자 ID 사용
$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
$student_id = isset($_GET["student_id"]) ? $_GET["student_id"] : $userid;
$teacher_id = $USER->id;

// mid 파라미터 가져오기 (기본값: 6)
$mid = isset($_GET["mid"]) ? intval($_GET["mid"]) : 6;
// mid 범위 검증 (1-6)
if ($mid < 1 || $mid > 6) {
    $mid = 6; // 범위를 벗어나면 기본값 6 사용
}

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student'; // 기본값은 student

// 페르소나 모드 가져오기
$persona_modes = $DB->get_record('persona_modes', 
    array('teacher_id' => $teacher_id, 'student_id' => $student_id));

// AJAX 요청 처리 - 메시지 전송
if (isset($_POST['action']) && $_POST['action'] == 'send_chat_message') {
    header('Content-Type: application/json');
    
    if (!$persona_modes) {
        echo json_encode(['success' => false, 'message' => '페르소나 모드가 설정되지 않았습니다.']);
        exit;
    }
    
    $message = $_POST['message'];
    $room_id = $teacher_id . '_' . $student_id;
    
    try {
        // 테이블 존재 여부 확인
        $table_exists = false;
        try {
            $DB->count_records('alt42_chat_messages');
            $table_exists = true;
        } catch (Exception $e) {
            // 테이블이 없으면 생성
            $sql_create = "CREATE TABLE IF NOT EXISTS {alt42_chat_messages} (
                id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
                room_id VARCHAR(100) NOT NULL,
                sender_id BIGINT(10) NOT NULL,
                receiver_id BIGINT(10) NOT NULL,
                message_type ENUM('original', 'transformed') DEFAULT 'original',
                message_content TEXT NOT NULL,
                sent_at BIGINT(10) NOT NULL,
                read_at BIGINT(10) DEFAULT NULL,
                INDEX idx_room_id (room_id),
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $DB->execute($sql_create);
        }
        
        // 원본 메시지 저장
        $original_msg = new stdClass();
        $original_msg->room_id = $room_id;
        $original_msg->sender_id = (int)$teacher_id;
        $original_msg->receiver_id = (int)$student_id;
        $original_msg->message_type = 'original';
        $original_msg->message_content = $message;
        $original_msg->sent_at = time();
        
        $original_id = $DB->insert_record('alt42_chat_messages', $original_msg);
        
        // 메시지 변환 함수
        if (!function_exists('transformMessageWithOpenAI')) {
            function transformMessageWithOpenAI($message, $teacher_mode, $student_mode) {
                $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
                $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';
                
                if (!$api_key) {
                    return $message; // API 키가 없으면 원본 반환
                }
                
                $mode_descriptions = [
                    'curriculum' => '체계적이고 계획적인 어조',
                    'exam' => '긴장감 있고 동기부여적인 어조',
                    'custom' => '친근하고 격려하는 어조',
                    'mission' => '게임처럼 도전적이고 즉각적인 어조',
                    'reflection' => '사려깊고 질문을 유도하는 어조',
                    'selfled' => '자율성을 존중하는 제안형 어조'
                ];
                
                $system_prompt = "당신은 선생님의 메시지를 학생의 학습 스타일에 맞게 변환하는 전문 AI입니다.\n\n선생님 모드: {$teacher_mode} ({$mode_descriptions[$teacher_mode]})\n학생 모드: {$student_mode} ({$mode_descriptions[$student_mode]})\n\n변환 원칙:\n1. 핵심 메시지와 의도는 완전히 유지\n2. 학생 모드에 맞는 어조와 표현으로 변경\n3. 구체적이고 실용적인 표현 사용\n4. 한국어로 자연스럽게 표현\n5. 변환된 메시지만 출력 (설명 없이)\n\n원본 메시지를 학생에게 맞게 변환해주세요:";
                
                $data = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system_prompt],
                        ['role' => 'user', 'content' => $message]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ];
                
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $response = curl_exec($ch);
                curl_close($ch);
                
                if ($response) {
                    $result = json_decode($response, true);
                    if (isset($result['choices'][0]['message']['content'])) {
                        return trim($result['choices'][0]['message']['content']);
                    }
                }
                
                return $message; // 실패 시 원본 반환
            }
        }
        
        $transformed_message = transformMessageWithOpenAI($message, $persona_modes->teacher_mode, $persona_modes->student_mode);
        
        // 변환된 메시지 저장
        $transformed_msg = new stdClass();
        $transformed_msg->room_id = $room_id;
        $transformed_msg->sender_id = (int)$teacher_id;
        $transformed_msg->receiver_id = (int)$student_id;
        $transformed_msg->message_type = 'transformed';
        $transformed_msg->message_content = $transformed_message;
        $transformed_msg->sent_at = time();
        
        $transformed_id = $DB->insert_record('alt42_chat_messages', $transformed_msg);
        
        echo json_encode(['success' => true, 'transformed_message' => $transformed_message]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '메시지 처리 중 오류: ' . $e->getMessage()]);
    }
    exit;
}

// AJAX 요청 처리 - 메시지 가져오기
if (isset($_GET['action']) && $_GET['action'] == 'get_chat_messages') {
    header('Content-Type: application/json');
    
    try {
        $room_id = $teacher_id . '_' . $student_id;
        $messages = $DB->get_records_sql("SELECT * FROM {alt42_chat_messages} WHERE room_id = ? ORDER BY sent_at ASC", 
            array($room_id));
        
        echo json_encode(['success' => true, 'messages' => array_values($messages)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '메시지 조회 오류: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>메타인지 홈</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 메인 컨테이너 */
        .main-container {
            display: flex;
            height: 100vh;
            background: white;
            overflow: hidden;
            position: relative;
        }

        /* 좌측 사이드바 */
        .sidebar {
            width: 280px;
            background: #2d3748;
            color: white;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 1000;
        }

        /* 모바일 메뉴 토글 버튼 */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .mobile-menu-toggle:hover {
            background: #5a67d8;
        }

        /* 모바일 오버레이 */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: #1a202c;
            border-bottom: 1px solid #4a5568;
        }

        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .header-buttons button {
            background: none;
            border: none;
            color: #718096;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.3s;
        }

        .header-buttons button:hover {
            color: white;
        }
        
        /* 미니맵 */
        .minimap-button {
            background: none;
            border: none;
            color: #718096;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.3s;
            position: relative;
        }
        
        .minimap-button:hover {
            color: white;
        }
        
        .minimap-dropdown {
            position: absolute;
            top: 60px;
            right: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 1.5rem;
            display: none;
            min-width: 250px;
            z-index: 200;
        }
        
        .minimap-dropdown.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .minimap-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .minimap-item {
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #333;
        }
        
        .minimap-item:hover {
            background: #f0f4ff;
            transform: translateX(5px);
        }
        
        .minimap-item.current {
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        /* 검색 */
        .search-container {
            display: flex;
            align-items: center;
            background: #4a5568;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }

        .search-icon {
            margin-right: 0.5rem;
        }

        .search-input {
            background: none;
            border: none;
            color: white;
            outline: none;
            flex: 1;
            font-size: 0.875rem;
        }

        .search-input::placeholder {
            color: #a0aec0;
        }

        /* 메뉴 카테고리 */
        .menu-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }

        .menu-category {
            margin-bottom: 0.5rem;
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .category-header:hover {
            background: #4a5568;
        }

        .category-header.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .category-header.active::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #fbbf24;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .category-icon {
            font-size: 1.5rem;
        }

        .category-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .category-status.inactive {
            background: #6b7280;
            box-shadow: none;
        }

        /* 우측 콘텐츠 영역 */
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f7fafc;
        }

        /* 콘텐츠 헤더 */
        .content-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .current-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .section-info h2 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .section-info p {
            color: #718096;
            font-size: 0.875rem;
        }

        /* 모드 스위처 */
        .mode-switcher {
            display: flex;
            gap: 0.5rem;
            background: #edf2f7;
            padding: 0.25rem;
            border-radius: 0.5rem;
        }

        .mode-button {
            padding: 0.5rem 1rem;
            border: none;
            background: none;
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 0.375rem;
            transition: all 0.3s;
        }

        .mode-button.active {
            background: white;
            color: #667eea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* 서브카테고리 탭 */
        .subcategory-tabs {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: none;
        }

        .subcategory-tabs.active {
            display: block;
        }

        .tabs-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
        }

        .tab-item {
            padding: 0.5rem 1rem;
            background: #f7fafc;
            border-radius: 0.5rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4a5568;
        }

        .tab-item:hover {
            background: #e2e8f0;
        }

        .tab-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        /* 메타인지 대시보드 */
        .metacognition-dashboard {
            padding: 2rem;
            display: none;
            overflow-y: auto;
        }

        .metacognition-dashboard.active {
            display: block;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: all 0.3s;
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .card-content {
            color: #718096;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .progress-bar {
            margin-top: 1rem;
            background: #e2e8f0;
            border-radius: 0.5rem;
            height: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.5s ease;
        }

        /* 메뉴 탭 */
        .menu-tab-container {
            padding: 2rem;
            display: none;
            overflow-y: auto;
        }

        .menu-tab-container.active {
            display: block;
        }

        .menu-tab-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .menu-tab-item {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .menu-tab-item:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .menu-tab-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        .menu-tab-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .menu-tab-desc {
            font-size: 0.75rem;
            color: #718096;
        }

        /* 채팅 영역 - 슬라이드 패널 */
        .chat-panel {
            position: fixed;
            top: 0;
            right: -25%;
            width: 25%;
            height: 100vh;
            background: #ffffff;
            box-shadow: -4px 0 12px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease-in-out;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .chat-panel.active {
            right: 0;
        }
        .chat-panel-header {
            padding: 1rem;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-panel-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
        }
        .chat-panel-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .chat-panel-close:hover {
            background-color: #e5e7eb;
        }
        .chat-area {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #fafafa;
        }
        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .chat-panel-input {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }
        .chat-panel-input-wrapper {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .chat-panel-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .chat-panel-input input:focus {
            border-color: #3b82f6;
        }
        .chat-panel-input button {
            padding: 0.75rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        .chat-panel-input button:hover {
            background: #2563eb;
        }
        .chat-panel-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
            z-index: 999;
        }
        .chat-panel-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .chat-message {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .message-name {
            font-weight: 600;
            color: #2d3748;
        }

        .message-time {
            font-size: 0.75rem;
            color: #a0aec0;
        }

        .message-text {
            color: #4a5568;
            line-height: 1.6;
        }

        /* 연쇄상호작용 알림 */
        .chain-notification {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* 기존 입력 영역 스타일 제거 - 새로운 채팅 패널 사용 */

        .send-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* 모바일 반응형 스타일 */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                width: 85%;
                max-width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .mobile-menu-toggle {
                display: block;
            }

            .mobile-overlay {
                display: none;
            }

            .mobile-overlay.active {
                display: block;
            }

            .content-header {
                padding-left: 4rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1rem;
            }

            .menu-tab-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                padding: 1rem;
            }

            .header-info {
                flex-direction: column;
                gap: 1rem;
            }

            .mode-switcher {
                width: 100%;
                justify-content: center;
            }

            .tabs-container {
                padding: 0 1rem;
                gap: 0.5rem;
            }

            .tab-item {
                padding: 0.4rem 0.8rem;
                font-size: 0.813rem;
            }

            
            /* 채팅 패널 모바일 스타일 */
            .chat-panel {
                width: 100%;
                right: -100%;
            }
            .chat-panel.active {
                right: 0;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                max-width: none;
            }

            .header-title h1 {
                font-size: 1.25rem;
            }

            .section-info h2 {
                font-size: 1.25rem;
            }

            .section-info p {
                font-size: 0.813rem;
            }

            .mode-button {
                padding: 0.4rem 0.8rem;
                font-size: 0.813rem;
            }

            .dashboard-card {
                padding: 1.25rem;
            }

            .card-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }

            .card-title {
                font-size: 1rem;
            }

            .card-desc {
                font-size: 0.813rem;
            }

            .card-stats {
                font-size: 0.75rem;
            }

            .menu-tab-card {
                padding: 1rem;
            }

            .menu-tab-title {
                font-size: 0.875rem;
            }

            .menu-tab-desc {
                font-size: 0.75rem;
            }

            .chat-message {
                padding: 0.875rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- 모바일 메뉴 토글 버튼 -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <!-- 모바일 오버레이 -->
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>

    <div class="main-container">
        <!-- 좌측 사이드바 -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="header-title">
                    <a href="index.php" style="text-decoration: none; color: inherit;">
                        <h1>🏠 메타인지</h1>
                    </a>
                    <div class="header-buttons">
                        <button><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/wxsperta/wxsperta.php?userid=<?php echo $userid; ?>">🔔</a></button>
                        <button><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/selectmode.php?userid=<?php echo $userid; ?>&student_id=827&role=teacher">⚙️</a></button>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-icon">🔍</div>
                    <input type="text" class="search-input" placeholder="메뉴 검색...">
                </div>
            </div>
            
            <div class="menu-list">
                <!-- 1. 분기활동 -->
                <div class="menu-category" data-category="quarterly">
                    <div class="category-header" onclick="selectCategory('quarterly')">
                        <div class="category-title">
                            <span class="category-icon">📅</span>
                            <span>1. 분기활동</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 2. 주간활동 -->
                <div class="menu-category" data-category="weekly">
                    <div class="category-header" onclick="selectCategory('weekly')">
                        <div class="category-title">
                            <span class="category-icon">📝</span>
                            <span>2. 주간활동</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 3. 오늘활동 -->
                <div class="menu-category" data-category="daily">
                    <div class="category-header" onclick="selectCategory('daily')">
                        <div class="category-title">
                            <span class="category-icon">⏰</span>
                            <span>3. 오늘활동</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 4. 성장관리 -->
                <div class="menu-category" data-category="development">
                    <div class="category-header" onclick="selectCategory('development')">
                        <div class="category-title">
                            <span class="category-icon">🌱</span>
                            <span>4. 성장관리</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 5. 상호작용 관리 -->
                <div class="menu-category" data-category="interaction">
                    <div class="category-header" onclick="selectCategory('interaction')">
                        <div class="category-title">
                            <span class="category-icon">💬</span>
                            <span>5. 상호작용 관리</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 6. 인지관성 개선 (구분선 추가) -->
                <div style="border-top: 2px solid #4a5568; margin: 1rem 0;"></div>
                <div class="menu-category" data-category="concept">
                    <div class="category-header" onclick="selectCategory('concept')">
                        <div class="category-title">
                            <span class="category-icon">🧠</span>
                            <span>6. 인지관성 개선</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 우측 콘텐츠 영역 -->
        <div class="content-area">
            <!-- 헤더 -->
            <div class="content-header">
                <div class="header-info">
                    <div class="current-section">
                        <div class="section-avatar" id="sectionAvatar">🧠</div>
                        <div class="section-info">
                            <h2 id="sectionTitle">메타인지 학습 시스템</h2>
                            <p id="sectionDesc">인지관성을 개선하고 효과적인 학습 환경을 만듭니다</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="mode-switcher">
                            <button class="mode-button active" onclick="switchMode('dashboard')">
                                💡 대시보드
                            </button>
                            <button class="mode-button" onclick="switchMode('menu')">
                                📋 메뉴
                            </button>
                            <button class="mode-button" onclick="switchMode('chat')">
                                💬 상담
                            </button>
                        </div>
                        <div style="position: relative;">
                            <button class="minimap-button" onclick="toggleMinimap()">
                                🗺️ 미니맵
                            </button>
                            <div class="minimap-dropdown" id="minimapDropdown">
                                <h3 class="minimap-title">
                                    <span>🗺️</span>
                                    <span>학습 목차</span>
                                </h3>
                                <a href="index.php" class="minimap-item">
                                    <span>🏠</span>
                                    <span>메인 홈</span>
                                </a>
                                <a href="index1.php" class="minimap-item">
                                    <span>📚</span>
                                    <span>개념학습</span>
                                </a>
                                <a href="index2.php" class="minimap-item">
                                    <span>🎯</span>
                                    <span>심화학습</span>
                                </a>
                                <a href="index3.php" class="minimap-item">
                                    <span>📝</span>
                                    <span>내신준비</span>
                                </a>
                                <a href="index4.php" class="minimap-item">
                                    <span>🎓</span>
                                    <span>수능대비</span>
                                </a>
                                <a href="indexm.php" class="minimap-item current">
                                    <span>🧠</span>
                                    <span>메타인지</span>
                                </a>
                                <div style="border-top: 1px solid #e2e8f0; margin: 0.5rem 0;"></div>
                                <div style="font-size: 0.75rem; color: #718096; padding: 0.5rem 0; margin-left: 1rem;">하부주제</div>
                                <a href="indexm.php?userid=<?php echo $userid; ?>&mid=1" class="minimap-item" style="padding-left: 2rem;">
                                    <span>📅</span>
                                    <span>1. 분기활동</span>
                                </a>
                                <a href="indexm.php?userid=<?php echo $userid; ?>&mid=2" class="minimap-item" style="padding-left: 2rem;">
                                    <span>📝</span>
                                    <span>2. 주간활동</span>
                                </a>
                                <a href="indexm.php?userid=<?php echo $userid; ?>&mid=3" class="minimap-item" style="padding-left: 2rem;">
                                    <span>⏰</span>
                                    <span>3. 오늘활동</span>
                                </a>
                                <a href="indexm.php?userid=<?php echo $userid; ?>&mid=4" class="minimap-item" style="padding-left: 2rem;">
                                    <span>🌱</span>
                                    <span>4. 성장관리</span>
                                </a>
                                <a href="indexm.php?userid=<?php echo $userid; ?>&mid=5" class="minimap-item" style="padding-left: 2rem;">
                                    <span>💬</span>
                                    <span>5. 상호작용 관리</span>
                                </a>
                                <a href="indexm.php?userid=<?php echo $userid; ?>&mid=6" class="minimap-item" style="padding-left: 2rem;">
                                    <span>🧠</span>
                                    <span>6. 인지관성 개선</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

            <!-- 서브카테고리 탭 -->
            <div class="subcategory-tabs" id="subcategoryTabs">
                <div class="tabs-container" id="tabsContainer">
                    <!-- 탭이 동적으로 생성됩니다 -->
                </div>
            </div>

            <!-- 메타인지 대시보드 -->
            <div class="metacognition-dashboard active" id="dashboardMode">
                <div class="dashboard-grid" id="dashboardGrid">
                    <!-- 대시보드 카드들이 동적으로 생성됩니다 -->
                </div>
            </div>

            <!-- 메뉴 탭 -->
            <div class="menu-tab-container" id="menuMode">
                <div class="menu-tab-grid" id="menuTabGrid">
                    <!-- 메뉴 아이템들이 동적으로 생성됩니다 -->
                </div>
            </div>

        </div>
    </div>

    <script>
        // 전역 변수
        let currentCategory = 'quarterly';  // 기본값을 1번 메뉴로 변경
        let currentSubcategory = null;
        let currentMode = 'dashboard';

        // 모바일 메뉴 토글
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        
        // 모바일 메뉴 닫기
        function closeMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }

        // 카테고리별 데이터 (실제 teacherhome 구조 반영)
        const categoryData = {
            // 1. 분기활동 (quarterly)
            quarterly: {
                title: '분기활동',
                icon: '📅',
                desc: '장기적인 학습 목표 설정 및 성과 관리',
                subcategories: {
                    'planning': { name: '계획관리', icon: '📊' },
                    'consultation': { name: '학부모상담', icon: '👨‍👩‍👧' }
                },
                dashboard: [
                    {
                        title: '분기 목표',
                        icon: '🎯',
                        content: '이번 분기 목표 달성률: 75%',
                        progress: 75,
                        subcategory: 'planning'
                    },
                    {
                        title: '성장 전망',
                        icon: '📈',
                        content: '예상 성장률: 상위 20%',
                        progress: 80,
                        subcategory: 'planning'
                    },
                    {
                        title: '상담 예정',
                        icon: '👨‍👩‍👧',
                        content: '다음 상담: 3일 후',
                        progress: 90,
                        subcategory: 'consultation'
                    },
                    {
                        title: '성적 관리',
                        icon: '📊',
                        content: '현재 평균: 85점',
                        progress: 85,
                        subcategory: 'consultation'
                    }
                ],
                menu: {
                    'planning': [
                        { icon: '🎯', title: '분기목표 설정 도우미', desc: '학습 목표 수립' },
                        { icon: '📋', title: '분기목표 요청', desc: '목표 조정 신청' },
                        { icon: '📈', title: '장기적인 성장전망', desc: '성장 예측 분석' },
                        { icon: '📊', title: '주간목표 분석', desc: '주간 성과 점검' },
                        { icon: '🏫', title: '학교생활 도우미', desc: '학교 활동 관리' }
                    ],
                    'consultation': [
                        { icon: '📊', title: '성적관리', desc: '성적 추이 분석' },
                        { icon: '📅', title: '일정관리', desc: '학습 일정 조율' },
                        { icon: '📝', title: '과제관리', desc: '과제 진행 현황' },
                        { icon: '🎯', title: '도전관리', desc: '도전 과제 설정' },
                        { icon: '💬', title: '상담관리', desc: '상담 이력 관리' },
                        { icon: '📱', title: '상담앱 활용', desc: '앱 연동 상담' },
                        { icon: '⏰', title: '상담지연 관리', desc: '일정 조정' },
                        { icon: '🔮', title: '다음 분기 시나리오 관리', desc: '계획 수립' }
                    ]
                }
            },
            // 2. 주간활동 (weekly)
            weekly: {
                title: '주간활동',
                icon: '📝',
                desc: '주간 목표 설정 및 진도 체크',
                subcategories: {
                    'planning': { name: '계획관리', icon: '📊' },
                    'completion': { name: '완성도 관리', icon: '✅' },
                    'diagnosis': { name: '종합진단', icon: '🔍' },
                    'exam': { name: '시험대비 진단', icon: '📚' }
                },
                dashboard: [
                    {
                        title: '주간 목표',
                        icon: '🎯',
                        content: '이번 주 목표 달성률: 82%',
                        progress: 82,
                        subcategory: 'planning'
                    },
                    {
                        title: '완성도',
                        icon: '✅',
                        content: '과제 완성도: 90%',
                        progress: 90,
                        subcategory: 'completion'
                    },
                    {
                        title: '학습 패턴',
                        icon: '🔍',
                        content: '정상 학습 패턴 유지',
                        progress: 95,
                        subcategory: 'diagnosis'
                    },
                    {
                        title: '시험 준비',
                        icon: '📚',
                        content: '시험 대비 진도: 78%',
                        progress: 78,
                        subcategory: 'exam'
                    }
                ],
                menu: {
                    'planning': [
                        { icon: '🎯', title: '주간목표 설정 도우미', desc: '주간 계획 수립' },
                        { icon: '📋', title: '주간목표 요청', desc: '목표 변경 요청' },
                        { icon: '📈', title: '분기단위 성장 전망', desc: '중장기 예측' },
                        { icon: '📊', title: '오늘목표 분석', desc: '일일 성과 체크' },
                        { icon: '📝', title: '주간활동 개선 리포트', desc: '개선점 분석' }
                    ],
                    'completion': [
                        { icon: '📊', title: '테스트 점수', desc: '점수 추이 분석' },
                        { icon: '🔄', title: '복습', desc: '복습 진도 관리' },
                        { icon: '📓', title: '오답노트 실행', desc: '오답 정리' }
                    ],
                    'diagnosis': [
                        { icon: '🚨', title: '이탈감지', desc: '학습 이탈 모니터링' },
                        { icon: '⚠️', title: '이상패턴', desc: '비정상 패턴 감지' },
                        { icon: '📚', title: '시험대비 상황 관리', desc: '시험 준비 점검' },
                        { icon: '⚡', title: '학습모드 최적화', desc: '효율성 개선' }
                    ],
                    'exam': [
                        { icon: '📝', title: '시험대비', desc: '시험 전략 수립' },
                        { icon: '🎯', title: '활동최적화', desc: '학습 효율 극대화' },
                        { icon: '🧠', title: 'Final Retrieval', desc: '최종 점검' }
                    ]
                }
            },
            // 3. 오늘활동 (daily)
            daily: {
                title: '오늘활동',
                icon: '⏰',
                desc: '시험대비, 복습전략, 학습분석',
                subcategories: {
                    'exam': { name: '시험대비', icon: '📝' },
                    'review': { name: '복습전략', icon: '🔄' },
                    'analysis': { name: '학습분석', icon: '📊' }
                },
                dashboard: [
                    {
                        title: '오늘의 학습',
                        icon: '📚',
                        content: '완료: 4/5 과목',
                        progress: 80,
                        subcategory: 'analysis'
                    },
                    {
                        title: '복습 진도',
                        icon: '🔄',
                        content: '복습 완료: 85%',
                        progress: 85,
                        subcategory: 'review'
                    },
                    {
                        title: '시험 준비',
                        icon: '📝',
                        content: '기출문제: 20/25',
                        progress: 80,
                        subcategory: 'exam'
                    },
                    {
                        title: '집중도',
                        icon: '🎯',
                        content: '평균 집중도: 88%',
                        progress: 88,
                        subcategory: 'analysis'
                    }
                ],
                menu: {
                    'exam': [
                        { icon: '📊', title: '학교기출 분석', desc: '기출문제 패턴', hasLink: true, link: '#' },
                        { icon: '📖', title: '교과서 단원별 해설', desc: '단원별 정리', hasLink: true, link: '#' },
                        { icon: '📅', title: '시험일정 관리', desc: '일정 체크', hasLink: true, link: '#' },
                        { icon: '🎯', title: '내신 등급 예측', desc: '예상 등급', hasLink: true, link: '#' }
                    ],
                    'review': [
                        { icon: '🧠', title: '에빙하우스 복습주기', desc: '최적 복습 시점', hasLink: true, link: '#' },
                        { icon: '📓', title: '오답노트 관리', desc: '오답 정리', hasLink: true, link: '#' },
                        { icon: '📈', title: '단원별 취약점 분석', desc: '취약 영역', hasLink: true, link: '#' },
                        { icon: '🎯', title: '복습 우선순위 설정', desc: '중요도 정렬', hasLink: true, link: '#' }
                    ],
                    'analysis': [
                        { icon: '📊', title: '일일 학습량 분석', desc: '학습 시간 통계', hasLink: true, link: '#' },
                        { icon: '🎯', title: '집중도 패턴 분석', desc: '집중력 추이', hasLink: true, link: '#' },
                        { icon: '📈', title: '성취도 추이 그래프', desc: '성과 변화', hasLink: true, link: '#' },
                        { icon: '📝', title: '학습 습관 리포트', desc: '습관 분석', hasLink: true, link: '#' }
                    ]
                }
            },
            // 4. 성장관리 (development)
            development: {
                title: '성장관리',
                icon: '🌱',
                desc: '학생의 전반적인 성장과 발달 관리',
                subcategories: {
                    'growth': { name: '성장추적', icon: '📈' },
                    'skills': { name: '역량개발', icon: '💪' },
                    'habits': { name: '습관형성', icon: '🔄' },
                    'mindset': { name: '마인드셋', icon: '🧠' }
                },
                dashboard: [
                    {
                        title: '성장 지표',
                        icon: '📈',
                        content: '이번 달 성장률: 15%',
                        progress: 85,
                        subcategory: 'growth'
                    },
                    {
                        title: '핵심 역량',
                        icon: '💪',
                        content: '역량 레벨: 상위 10%',
                        progress: 90,
                        subcategory: 'skills'
                    },
                    {
                        title: '학습 습관',
                        icon: '🔄',
                        content: '습관 형성도: 78%',
                        progress: 78,
                        subcategory: 'habits'
                    },
                    {
                        title: '마인드셋',
                        icon: '🧠',
                        content: '성장 마인드셋: 우수',
                        progress: 88,
                        subcategory: 'mindset'
                    }
                ],
                menu: {
                    'growth': [
                        { icon: '📈', title: '성장 추이 분석', desc: '장기 성장 패턴' },
                        { icon: '📊', title: '목표 달성률', desc: '목표 대비 성과' },
                        { icon: '🎯', title: '성장 예측 모델', desc: '미래 성장 전망' },
                        { icon: '📝', title: '성장 리포트', desc: '종합 성장 보고서' }
                    ],
                    'skills': [
                        { icon: '💪', title: '핵심 역량 평가', desc: '역량 수준 진단' },
                        { icon: '🎓', title: '스킬 개발 로드맵', desc: '역량 개발 계획' },
                        { icon: '📚', title: '학습 역량 강화', desc: '학습 능력 향상' },
                        { icon: '🧩', title: '문제해결 능력', desc: '사고력 개발' }
                    ],
                    'habits': [
                        { icon: '🔄', title: '습관 트래커', desc: '일일 습관 기록' },
                        { icon: '📅', title: '루틴 설계', desc: '최적 학습 루틴' },
                        { icon: '⏰', title: '시간 관리', desc: '효율적 시간 활용' },
                        { icon: '🎯', title: '습관 형성 코칭', desc: '맞춤형 습관 설계' }
                    ],
                    'mindset': [
                        { icon: '🧠', title: '마인드셋 진단', desc: '현재 사고방식 분석' },
                        { icon: '💡', title: '동기부여 강화', desc: '내적 동기 개발' },
                        { icon: '🌟', title: '자존감 향상', desc: '자신감 구축' },
                        { icon: '🎯', title: '목표 의식 강화', desc: '비전 설정' }
                    ]
                }
            },
            // 5. 상호작용 관리 (interaction)
            interaction: {
                title: '상호작용 관리',
                icon: '💬',
                desc: '개인화된 학습 경험을 위한 효과적인 의사소통',
                subcategories: {
                    'communication': { name: '소통관리', icon: '💬' },
                    'feedback': { name: '피드백', icon: '📢' },
                    'adaptation': { name: '적응관리', icon: '🔄' }
                },
                dashboard: [
                    {
                        title: '대화 횟수',
                        icon: '💬',
                        content: '오늘: 15회',
                        progress: 75,
                        subcategory: 'communication'
                    },
                    {
                        title: '피드백',
                        icon: '📢',
                        content: '긍정 피드백: 85%',
                        progress: 85,
                        subcategory: 'feedback'
                    },
                    {
                        title: '학습 스타일',
                        icon: '🔄',
                        content: '시각형 학습자',
                        progress: 90,
                        subcategory: 'adaptation'
                    },
                    {
                        title: '만족도',
                        icon: '😊',
                        content: '매우 만족: 92%',
                        progress: 92,
                        subcategory: 'feedback'
                    }
                ],
                menu: {
                    'communication': [
                        { icon: '💬', title: '자연어 대화', desc: '자유로운 대화' },
                        { icon: '❓', title: '질문 응답', desc: 'Q&A 세션' },
                        { icon: '📖', title: '설명 요청', desc: '상세 설명' },
                        { icon: '🗣️', title: '토론 진행', desc: '주제 토론' }
                    ],
                    'feedback': [
                        { icon: '📊', title: '학습 피드백', desc: '성과 피드백' },
                        { icon: '🏆', title: '성과 인정', desc: '칭찬과 격려' },
                        { icon: '💡', title: '개선 제안', desc: '개선점 안내' },
                        { icon: '💪', title: '격려 메시지', desc: '동기 부여' }
                    ],
                    'adaptation': [
                        { icon: '🔍', title: '학습 스타일 분석', desc: '스타일 파악' },
                        { icon: '❤️', title: '선호도 파악', desc: '취향 분석' },
                        { icon: '⚙️', title: '개인화 설정', desc: '맞춤 설정' },
                        { icon: '📚', title: '맞춤 콘텐츠', desc: '개인화 자료' }
                    ]
                }
            },
            // 6. 인지관성 개선 (bias)
            bias: {
                title: '인지관성 개선',
                icon: '🧠',
                desc: '학생들의 인지관성을 개선하고 연쇄상호작용을 통해 효과적인 학습 환경을 조성합니다.',
                subcategories: {
                    'concept_study': { name: '개념공부', icon: '📚' },
                    'problem_solving': { name: '문제풀이', icon: '✏️' },
                    'learning_management': { name: '학습관리', icon: '📊' },
                    'exam_preparation': { name: '시험대비', icon: '📝' },
                    'practical_training': { name: '실전연습', icon: '🎯' },
                    'attendance': { name: '출결관련', icon: '📅' }
                },
                dashboard: [
                    {
                        title: '포모도르 세션',
                        icon: '⏰',
                        content: '집중 시간 설정 및 효율적 학습',
                        progress: 75,
                        subcategory: 'concept_study'
                    },
                    {
                        title: '문제풀이 진도',
                        icon: '✏️',
                        content: '오늘 해결: 15문제',
                        progress: 85,
                        subcategory: 'problem_solving'
                    },
                    {
                        title: '학습 목표',
                        icon: '🎯',
                        content: '주간 목표 달성률: 78%',
                        progress: 78,
                        subcategory: 'learning_management'
                    },
                    {
                        title: '시험 준비',
                        icon: '📝',
                        content: '중간고사 D-14',
                        progress: 65,
                        subcategory: 'exam_preparation'
                    }
                ],
                menu: {
                    'concept_study': [
                        { icon: '⏰', title: '포모도르설정', desc: '집중력 향상을 위한 포모도르 기법', hasChainInteraction: true },
                        { icon: '📓', title: '개념노트 사용법', desc: '효과적인 개념 정리를 위한 노트 작성법', hasChainInteraction: true },
                        { icon: '🎤', title: '음성대화 사용법', desc: 'AI와의 음성 대화를 통한 개념 학습', hasChainInteraction: true },
                        { icon: '✍️', title: '테스트 응시방법', desc: '개념 이해도 테스트 응시 방법', hasChainInteraction: true },
                        { icon: '💬', title: '질의응답 및 지면평가', desc: '개념 학습에 대한 질의응답 및 평가', hasChainInteraction: true }
                    ],
                    'problem_solving': [
                        { icon: '🚀', title: '문제풀이 시작', desc: '효과적인 문제 풀이 시작 전략', hasChainInteraction: true },
                        { icon: '🔄', title: '문제풀이 과정', desc: '체계적인 문제 해결 과정', hasChainInteraction: true },
                        { icon: '✅', title: '문제풀이 마무리', desc: '문제 해결 후 검토 및 정리', hasChainInteraction: true }
                    ],
                    'learning_management': [
                        { icon: '🏠', title: '내공부방', desc: '개인 학습 공간 관리 및 최적화', hasChainInteraction: true },
                        { icon: '📈', title: '공부결과', desc: '학습 성과를 분석하고 피드백', hasChainInteraction: true },
                        { icon: '🎯', title: '목표설정', desc: '효과적인 학습 목표 설정', hasChainInteraction: true },
                        { icon: '📔', title: '수학일기', desc: '수학 학습 과정을 기록하고 성찰', hasChainInteraction: true },
                        { icon: '📅', title: '분기목표', desc: '장기적 학습 목표를 설정하고 관리', hasChainInteraction: true },
                        { icon: '⏰', title: '시간표', desc: '효율적인 학습 시간표 작성 관리', hasChainInteraction: true }
                    ],
                    'exam_preparation': [
                        { icon: '🔍', title: '준비상태 진단', desc: '현재 시험 준비 상태를 진단', hasChainInteraction: true },
                        { icon: '📅', title: '대비 기간을 구간별로 분할하기', desc: '시험까지의 기간을 효과적으로 분할', hasChainInteraction: true },
                        { icon: '⚡', title: '구간별 최적화', desc: '각 구간에 맞는 최적의 학습 전략', hasChainInteraction: true },
                        { icon: '📚', title: '내신테스트, 기출문제 풀이', desc: '내신 및 기출문제를 통한 실전 연습', hasChainInteraction: true },
                        { icon: '🧠', title: '최종적 기억인출 기획', desc: '시험 직전 최종 기억 인출 전략', hasChainInteraction: true }
                    ],
                    'practical_training': [
                        { icon: '⏰', title: '시간관리 (그냥 ... , 빨리 풀기)', desc: '실전에서의 효과적인 시간 관리', hasChainInteraction: true },
                        { icon: '🎯', title: '실수 조절하기', desc: '실전에서 실수를 최소화하는 방법', hasChainInteraction: true },
                        { icon: '📋', title: '문항풀이 순서 정하기', desc: '최적의 문항 풀이 순서 결정', hasChainInteraction: true },
                        { icon: '🎯', title: '초반에 목표점수 수정하기', desc: '시험 초반 상황에 따른 목표점수 조정', hasChainInteraction: true },
                        { icon: '💰', title: '기회비용 계산하기', desc: '문항별 기회비용을 계산하여 최적 선택', hasChainInteraction: true }
                    ],
                    'attendance': [
                        { icon: '📅', title: '출결 현황', desc: '출석 및 결석 현황 관리', hasChainInteraction: true },
                        { icon: '📚', title: '보강 계획', desc: '결석에 따른 보강 학습 계획', hasChainInteraction: true },
                        { icon: '🔄', title: '학습 연속성', desc: '출결과 학습 진도 연계 관리', hasChainInteraction: true }
                    ]
                }
            }
        };

        // mid 값에 따른 카테고리 매핑
        const midToCategoryMap = {
            1: 'quarterly',    // 분기활동
            2: 'weekly',       // 주간활동
            3: 'daily',        // 오늘활동
            4: 'development',  // 성장관리
            5: 'interaction',  // 상호작용 관리
            6: 'bias'          // 인지관성 개선
        };

        // PHP에서 전달받은 mid 값
        const currentMid = <?php echo $mid; ?>;
        const initialCategory = midToCategoryMap[currentMid] || 'bias';

        // 초기화
        window.onload = function() {
            selectCategory(initialCategory);  // mid에 따른 카테고리로 시작
            loadDashboard();
            checkChainInteraction();
            
            // 엔터키 이벤트
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        };

        // 카테고리 선택
        function selectCategory(category) {
            currentCategory = category;
            currentSubcategory = null;
            
            // 사이드바 활성화 상태 업데이트
            document.querySelectorAll('.category-header').forEach(header => {
                header.classList.remove('active');
            });
            // data-category로 해당 카테고리 찾기
            const activeHeader = document.querySelector(`.menu-category[data-category="${category}"] .category-header`);
            if (activeHeader) {
                activeHeader.classList.add('active');
            }
            
            // 섹션 정보 업데이트
            const data = categoryData[category];
            document.getElementById('sectionAvatar').textContent = data.icon;
            document.getElementById('sectionTitle').textContent = data.title;
            document.getElementById('sectionDesc').textContent = data.desc;
            
            // 서브카테고리 탭 업데이트
            updateSubcategoryTabs();
            
            // 현재 모드에 따라 콘텐츠 로드
            if (currentMode === 'dashboard') {
                loadDashboard();
            } else {
                loadMenuTab();
            }
        }

        // 서브카테고리 탭 업데이트
        function updateSubcategoryTabs() {
            const data = categoryData[currentCategory];
            const tabsContainer = document.getElementById('tabsContainer');
            const subcategoryTabs = document.getElementById('subcategoryTabs');
            
            if (data.subcategories && Object.keys(data.subcategories).length > 0) {
                subcategoryTabs.classList.add('active');
                
                tabsContainer.innerHTML = Object.entries(data.subcategories).map(([key, sub]) => `
                    <div class="tab-item ${!currentSubcategory ? 'active' : currentSubcategory === key ? 'active' : ''}" 
                         onclick="selectSubcategory('${key}')">
                        ${sub.icon} ${sub.name}
                    </div>
                `).join('');
            } else {
                subcategoryTabs.classList.remove('active');
            }
        }

        // 서브카테고리 선택
        function selectSubcategory(subcategory) {
            currentSubcategory = subcategory;
            
            // 탭 활성화 상태 업데이트
            document.querySelectorAll('.tab-item').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 현재 모드에 따라 콘텐츠 로드
            if (currentMode === 'dashboard') {
                loadDashboard();
            } else {
                loadMenuTab();
            }
        }

        // 대시보드 로드
        function loadDashboard() {
            const data = categoryData[currentCategory];
            const grid = document.getElementById('dashboardGrid');
                        subcategory: 'process'
                    },
                    {
                        title: '문제 분석',
                        icon: '🔍',
                        content: '취약 유형: 함수',
                        progress: 45,
                        subcategory: 'start'
                    }
                ],
                menu: {
                    'start': [
                        { icon: '📋', title: '문제 분석', desc: '문제 유형 파악' },
                        { icon: '🎯', title: '전략 수립', desc: '풀이 계획 세우기' },
                        { icon: '⏰', title: '시간 배분', desc: '효율적 시간 관리' },
                        { icon: '💡', title: '힌트 활용', desc: '단계별 도움말' }
                    ],
                    'process': [
                        { icon: '✍️', title: '단계별 풀이', desc: '체계적 접근법' },
                        { icon: '🔄', title: '과정 기록', desc: '풀이 과정 저장' },
                        { icon: '💬', title: 'AI 도움', desc: '실시간 피드백' },
                        { icon: '📊', title: '진행 상황', desc: '풀이 진도 체크' }
                    ],
                    'finish': [
                        { icon: '✅', title: '답안 검증', desc: '정답 확인하기' },
                        { icon: '📝', title: '오답 분석', desc: '실수 패턴 찾기' },
                        { icon: '💾', title: '풀이 저장', desc: '나만의 풀이법' },
                        { icon: '🔄', title: '복습 예약', desc: '재학습 스케줄' }
                    ]
                }
            },
            learning: {
                title: '학습관리',
                icon: '📊',
                desc: '체계적인 학습 계획과 실행',
                subcategories: {
                    'studyroom': { name: '내공부방', icon: '🏠' },
                    'results': { name: '공부결과', icon: '📈' },
                    'goals': { name: '목표설정', icon: '🎯' },
                    'diary': { name: '수학일기', icon: '📔' },
                    'quarterly': { name: '분기목표', icon: '📅' },
                    'schedule': { name: '시간표', icon: '⏰' }
                },
                dashboard: [
                    {
                        title: '주간 학습량',
                        icon: '📅',
                        content: '이번 주: 15시간 / 20시간',
                        progress: 75,
                        subcategory: 'schedule'
                    },
                    {
                        title: '목표 달성률',
                        icon: '🎯',
                        content: '이번 달: 88%',
                        progress: 88,
                        subcategory: 'goals'
                    },
                    {
                        title: '학습 일지',
                        icon: '📔',
                        content: '연속 작성: 12일',
                        progress: 100,
                        subcategory: 'diary'
                    },
                    {
                        title: '성과 분석',
                        icon: '📈',
                        content: '상승 추세 지속',
                        progress: 92,
                        subcategory: 'results'
                    }
                ],
                menu: {
                    'studyroom': [
                        { icon: '🏠', title: '나의 학습 공간', desc: '개인화된 환경' },
                        { icon: '📚', title: '학습 자료실', desc: '맞춤 콘텐츠' },
                        { icon: '🎨', title: '공간 꾸미기', desc: '동기부여 환경' },
                        { icon: '🏆', title: '성취 전시실', desc: '학습 성과 기록' }
                    ],
                    'results': [
                        { icon: '📊', title: '성과 대시보드', desc: '종합 성과 분석' },
                        { icon: '📈', title: '성장 그래프', desc: '학습 곡선 추적' },
                        { icon: '🎯', title: '목표 대비 실적', desc: '달성도 분석' },
                        { icon: '📋', title: '상세 리포트', desc: '심층 분석 보고서' }
                    ],
                    'goals': [
                        { icon: '🎯', title: 'SMART 목표', desc: '구체적 목표 설정' },
                        { icon: '📅', title: '장단기 계획', desc: '기간별 목표 관리' },
                        { icon: '✅', title: '체크리스트', desc: '일일 실행 항목' },
                        { icon: '🏆', title: '보상 시스템', desc: '목표 달성 보상' }
                    ]
                }
            },
            exam: {
                title: '시험대비',
                icon: '📝',
                desc: '체계적이고 전략적인 시험 준비',
                subcategories: {
                    'diagnosis': { name: '준비상태 진단', icon: '🔍' },
                    'period': { name: '기간별 전략', icon: '📅' },
                    'optimize': { name: '구간별 최적화', icon: '⚡' },
                    'practice': { name: '내신/기출 연습', icon: '📚' },
                    'memory': { name: '기억인출 전략', icon: '🧠' }
                },
                dashboard: [
                    {
                        title: 'D-Day',
                        icon: '📅',
                        content: '중간고사까지 D-14',
                        progress: 30,
                        subcategory: 'period'
                    },
                    {
                        title: '준비 상태',
                        icon: '🔍',
                        content: '진단 점수: 78점',
                        progress: 78,
                        subcategory: 'diagnosis'
                    },
                    {
                        title: '진도율',
                        icon: '📚',
                        content: '시험범위: 85% 완료',
                        progress: 85,
                        subcategory: 'practice'
                    },
                    {
                        title: '암기 상태',
                        icon: '🧠',
                        content: '공식 암기: 92%',
                        progress: 92,
                        subcategory: 'memory'
                    }
                ],
                menu: {
                    'diagnosis': [
                        { icon: '🔍', title: '실력 진단', desc: '현재 수준 파악' },
                        { icon: '📊', title: '취약점 분석', desc: '보완 필요 영역' },
                        { icon: '🎯', title: '목표 설정', desc: '현실적 목표 수립' },
                        { icon: '📋', title: '준비 체크리스트', desc: '필수 준비 사항' }
                    ],
                    'period': [
                        { icon: '📅', title: '4주 전략', desc: '장기 준비 계획' },
                        { icon: '📆', title: '2주 전략', desc: '집중 학습 기간' },
                        { icon: '🗓️', title: '1주 전략', desc: '최종 정리 기간' },
                        { icon: '⏰', title: 'D-Day 전략', desc: '시험 당일 계획' }
                    ],
                    'optimize': [
                        { icon: '⚡', title: '효율성 극대화', desc: '시간 대비 효과' },
                        { icon: '🎯', title: '핵심 집중', desc: '중요도별 학습' },
                        { icon: '🔄', title: '반복 최적화', desc: '효과적 복습' },
                        { icon: '💪', title: '컨디션 관리', desc: '최상의 상태 유지' }
                    ]
                }
            },
            practice: {
                title: '실전연습',
                icon: '🎯',
                desc: '실제 시험과 동일한 환경에서 연습',
                subcategories: {
                    'time': { name: '시간관리', icon: '⏰' },
                    'mistake': { name: '실수 조절하기', icon: '🎯' },
                    'order': { name: '문항풀이 순서', icon: '📋' },
                    'goal': { name: '목표점수 조정', icon: '🎯' },
                    'cost': { name: '기회비용 계산', icon: '💰' }
                },
                dashboard: [
                    {
                        title: '모의고사 횟수',
                        icon: '📄',
                        content: '이번 달: 8회',
                        progress: 80,
                        subcategory: 'time'
                    },
                    {
                        title: '시간 관리',
                        icon: '⏰',
                        content: '평균 완료: 48분/50분',
                        progress: 96,
                        subcategory: 'time'
                    },
                    {
                        title: '실수율',
                        icon: '🎯',
                        content: '계산 실수: 5%',
                        progress: 95,
                        subcategory: 'mistake'
                    },
                    {
                        title: '전략 점수',
                        icon: '📊',
                        content: '풀이 순서 최적화: 85점',
                        progress: 85,
                        subcategory: 'order'
                    }
                ],
                menu: {
                    'time': [
                        { icon: '⏱️', title: '속도 훈련', desc: '문제별 시간 배분' },
                        { icon: '⚡', title: '빠른 판단', desc: '건너뛰기 결정' },
                        { icon: '📊', title: '시간 분석', desc: '소요 시간 통계' },
                        { icon: '🎯', title: '목표 시간', desc: '적정 속도 찾기' }
                    ],
                    'mistake': [
                        { icon: '🔍', title: '실수 패턴', desc: '반복 실수 분석' },
                        { icon: '✅', title: '검토 방법', desc: '효율적 재검토' },
                        { icon: '🎯', title: '집중력 관리', desc: '실수 방지 전략' },
                        { icon: '📝', title: '실수 노트', desc: '오류 기록 관리' }
                    ],
                    'order': [
                        { icon: '📋', title: '난이도별 순서', desc: '쉬운 문제 먼저' },
                        { icon: '🎯', title: '배점별 전략', desc: '고배점 우선순위' },
                        { icon: '⏰', title: '시간별 배분', desc: '문항당 시간 계획' },
                        { icon: '🔄', title: '유연한 조정', desc: '상황별 전략 변경' }
                    ]
                }
            },
            attendance: {
                title: '인지관성 개선',
                icon: '🧠',
                desc: '학생들의 인지관성을 개선하고 연쇄상호작용을 통해 효과적인 학습 환경 조성',
                subcategories: {
                    'concept_study': { name: '개념공부', icon: '📚' },
                    'problem_solving': { name: '문제풀이', icon: '✏️' },
                    'learning_management': { name: '학습관리', icon: '📊' },
                    'exam_preparation': { name: '시험대비', icon: '📝' },
                    'practice': { name: '실전연습', icon: '🎯' },
                    'attendance': { name: '출결관리', icon: '📅' }
                },
                dashboard: [
                    {
                        title: '인지 패턴 분석',
                        icon: '🧠',
                        content: '현재 학습 패턴: 시각형',
                        progress: 85,
                        subcategory: 'concept_study'
                    },
                    {
                        title: '정답률 향상',
                        icon: '📈',
                        content: '이번 주: 75% → 82%',
                        progress: 82,
                        subcategory: 'problem_solving'
                    },
                    {
                        title: '학습 효율성',
                        icon: '⏱️',
                        content: '평균 집중도: 88%',
                        progress: 88,
                        subcategory: 'learning_management'
                    },
                    {
                        title: '연쇄상호작용',
                        icon: '🔗',
                        content: '학습 그룹 활성도: 높음',
                        progress: 90,
                        subcategory: 'practice'
                    }
                ],
                menu: {
                    'concept_study': [
                        { icon: '🧠', title: '인지유형 진단', desc: '학습 스타일 분석' },
                        { icon: '📚', title: '개념지도 학습', desc: '체계적 개념 이해' },
                        { icon: '🎯', title: '핵심개념 연결', desc: '주요 개념 맵핑' },
                        { icon: '🔍', title: '오개념 교정', desc: '잘못된 이해 바로잡기' }
                    ],
                    'problem_solving': [
                        { icon: '✏️', title: '문제해결 패턴', desc: '유형별 접근법' },
                        { icon: '📋', title: '오답패턴 분석', desc: '반복 실수 개선' },
                        { icon: '🎯', title: '단계별 풀이', desc: '체계적 문제해결' },
                        { icon: '🔄', title: '피드백 학습', desc: '즉각적 교정' }
                    ],
                    'learning_management': [
                        { icon: '📊', title: '학습패턴 분석', desc: '개인별 학습 특성' },
                        { icon: '⏱️', title: '시간관리 최적화', desc: '효율적 학습시간' },
                        { icon: '🎯', title: '목표설정 도구', desc: 'SMART 목표 설정' },
                        { icon: '📈', title: '성과추적 시스템', desc: '진도 모니터링' }
                    ],
                    'exam_preparation': [
                        { icon: '📝', title: '시험대비 전략', desc: '효과적 시험 준비' },
                        { icon: '📊', title: '취약점 분석', desc: '보완 학습 계획' },
                        { icon: '🎯', title: '목표점수 설정', desc: '현실적 목표 수립' },
                        { icon: '🔄', title: '복습주기 관리', desc: '에빙하우스 곡선' }
                    ],
                    'practice': [
                        { icon: '🎯', title: '실전모의고사', desc: '실제 시험 환경' },
                        { icon: '⏱️', title: '시간관리 연습', desc: '시험 시간 배분' },
                        { icon: '📝', title: '문제해결 연습', desc: '다양한 유형 대비' },
                        { icon: '📊', title: '성과분석 도구', desc: '약점 보완 전략' }
                    ],
                    'attendance': [
                        { icon: '📅', title: '출석관리', desc: '규칙적 학습습관' },
                        { icon: '🔔', title: '알림설정', desc: '학습 리마인더' },
                        { icon: '📊', title: '출석통계', desc: '학습 참여도' },
                        { icon: '🏆', title: '보상시스템', desc: '동기부여 프로그램' }
                    ]
                }
            }
        };

        // mid 값에 따른 카테고리 매핑
        const midToCategoryMap = {
            1: 'quarterly',    // 분기활동
            2: 'weekly',       // 주간활동
            3: 'daily',        // 오늘활동
            4: 'development',  // 성장관리
            5: 'interaction',  // 상호작용 관리
            6: 'bias'          // 인지관성 개선
        };

        // PHP에서 전달받은 mid 값
        const currentMid = <?php echo $mid; ?>;
        const initialCategory = midToCategoryMap[currentMid] || 'concept';

        // 초기화
        window.onload = function() {
            selectCategory(initialCategory);  // mid에 따른 카테고리로 시작
            loadDashboard();
            checkChainInteraction();
            
            // 엔터키 이벤트
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        };

        // 카테고리 선택
        function selectCategory(category) {
            currentCategory = category;
            currentSubcategory = null;
            
            // 사이드바 활성화 상태 업데이트
            document.querySelectorAll('.category-header').forEach(header => {
                header.classList.remove('active');
            });
            // data-category로 해당 카테고리 찾기
            const activeHeader = document.querySelector(`.menu-category[data-category="${category}"] .category-header`);
            if (activeHeader) {
                activeHeader.classList.add('active');
            }
            
            // 섹션 정보 업데이트
            const data = categoryData[category];
            document.getElementById('sectionAvatar').textContent = data.icon;
            document.getElementById('sectionTitle').textContent = data.title;
            document.getElementById('sectionDesc').textContent = data.desc;
            
            // 서브카테고리 탭 업데이트
            updateSubcategoryTabs();
            
            // 현재 모드에 따라 콘텐츠 로드
            if (currentMode === 'dashboard') {
                loadDashboard();
            } else if (currentMode === 'menu') {
                loadMenu();
            }
            
            // 모바일에서 카테고리 선택 시 메뉴 닫기
            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        }

        // 서브카테고리 탭 업데이트
        function updateSubcategoryTabs() {
            const data = categoryData[currentCategory];
            const tabsContainer = document.getElementById('tabsContainer');
            const subcategoryTabs = document.getElementById('subcategoryTabs');
            
            if (data.subcategories && Object.keys(data.subcategories).length > 0) {
                subcategoryTabs.classList.add('active');
                
                tabsContainer.innerHTML = Object.entries(data.subcategories).map(([key, sub]) => `
                    <div class="tab-item ${!currentSubcategory ? 'active' : currentSubcategory === key ? 'active' : ''}" 
                         onclick="selectSubcategory('${key}')">
                        ${sub.icon} ${sub.name}
                    </div>
                `).join('');
            } else {
                subcategoryTabs.classList.remove('active');
            }
        }

        // 서브카테고리 선택
        function selectSubcategory(subcategory) {
            currentSubcategory = subcategory;
            
            // 탭 활성화 상태
            document.querySelectorAll('.tab-item').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 콘텐츠 필터링
            if (currentMode === 'dashboard') {
                loadDashboard();
            } else if (currentMode === 'menu') {
                loadMenu();
            }
        }

        // 모드 전환
        function switchMode(mode) {
            currentMode = mode;
            
            // 버튼 활성화 상태
            document.querySelectorAll('.mode-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 콘텐츠 영역 표시/숨김
            document.getElementById('dashboardMode').classList.remove('active');
            document.getElementById('menuMode').classList.remove('active');
            
            if (mode === 'dashboard') {
                document.getElementById('dashboardMode').classList.add('active');
                loadDashboard();
            } else if (mode === 'menu') {
                document.getElementById('menuMode').classList.add('active');
                loadMenu();
            } else if (mode === 'chat') {
                // 채팅 패널 열기
                openChatPanel();
            }
        }
        
        // 채팅 패널 열기
        function openChatPanel() {
            document.getElementById('chatPanel').classList.add('active');
            document.getElementById('chatPanelOverlay').classList.add('active');
            initChat();
        }
        
        // 채팅 패널 닫기
        function closeChatPanel() {
            document.getElementById('chatPanel').classList.remove('active');
            document.getElementById('chatPanelOverlay').classList.remove('active');
            // 대시보드 모드로 돌아가기
            document.querySelector('.mode-button[onclick="switchMode(\'dashboard\')"]').click();
        }

        // 대시보드 로드
        function loadDashboard() {
            const data = categoryData[currentCategory];
            const grid = document.getElementById('dashboardGrid');
            
            let dashboardData = data.dashboard;
            if (currentSubcategory) {
                dashboardData = dashboardData.filter(item => item.subcategory === currentSubcategory);
            }
            
            grid.innerHTML = dashboardData.map(item => `
                <div class="dashboard-card" onclick="handleDashboardClick('${item.subcategory}', '${item.title}')">
                    <div class="card-header">
                        <h3 class="card-title">${item.title}</h3>
                        <div class="card-icon">${item.icon}</div>
                    </div>
                    <div class="card-content">
                        ${item.content}
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${item.progress}%"></div>
                    </div>
                </div>
            `).join('');
        }

        // 메뉴 로드
        function loadMenu() {
            const data = categoryData[currentCategory];
            const grid = document.getElementById('menuTabGrid');
            
            let menuData = [];
            if (currentSubcategory && data.menu[currentSubcategory]) {
                menuData = data.menu[currentSubcategory];
            } else if (!currentSubcategory && data.menu) {
                // 모든 서브카테고리의 메뉴 표시
                Object.values(data.menu).forEach(items => {
                    menuData = menuData.concat(items);
                });
            }
            
            grid.innerHTML = menuData.map(item => `
                <div class="menu-tab-item" onclick="handleMenuClick('${item.title}')">
                    <div class="menu-tab-icon">${item.icon}</div>
                    <div class="menu-tab-title">${item.title}</div>
                    <div class="menu-tab-desc">${item.desc}</div>
                </div>
            `).join('');
        }

        // 연쇄상호작용 체크
        function checkChainInteraction() {
            // 비슷한 학습 패턴을 가진 학생 찾기 시뮬레이션
            const hasChainPartner = Math.random() > 0.7; // 30% 확률로 파트너 있음
            
            if (hasChainPartner && currentMode === 'dashboard') {
                const container = document.getElementById('dashboardGrid');
                const notification = `
                    <div class="chain-notification">
                        <span>🔗</span>
                        <span>비슷한 학습 패턴을 가진 3명의 학생과 연결되었습니다!</span>
                        <button onclick="joinChainSession()" style="margin-left: auto; background: white; color: #f59e0b; border: none; padding: 0.25rem 0.75rem; border-radius: 0.25rem; cursor: pointer;">
                            참여하기
                        </button>
                    </div>
                `;
                container.insertAdjacentHTML('afterbegin', notification);
            }
        }

        // 연쇄상호작용 세션 참여
        function joinChainSession() {
            alert('연쇄상호작용 학습 세션에 참여합니다. 비슷한 수준의 학생들과 함께 학습하세요!');
            switchMode('chat');
            addMessage('ai', '🔗 연쇄상호작용 세션이 시작되었습니다. 현재 3명의 학생이 함께 참여중입니다.');
        }

        // 채팅 초기화
        async function initChat() {
            const container = document.getElementById('chatContainer');
            container.innerHTML = ''; // 기존 내용 클리어
            
            <?php if ($persona_modes): ?>
                // 페르소나 모드 정보 표시
                const modeInfo = document.createElement('div');
                modeInfo.style.cssText = 'padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 0.5rem; margin-bottom: 1rem;';
                modeInfo.innerHTML = `
                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">현재 페르소나 설정</div>
                    <div style="display: flex; gap: 1rem;">
                        <span style="background: rgba(59, 130, 246, 0.2); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">
                            선생님: <?php echo $persona_modes->teacher_mode; ?>
                        </span>
                        <span style="background: rgba(34, 197, 94, 0.2); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">
                            학생: <?php echo $persona_modes->student_mode; ?>
                        </span>
                    </div>
                `;
                container.appendChild(modeInfo);
                
                // 기존 메시지 로드
                await loadChatMessages();
            <?php else: ?>
                addMessage('ai', '페르소나 모드가 설정되지 않았습니다. 먼저 학습 스타일을 설정해주세요.');
            <?php endif; ?>
        }
        
        // 채팅 메시지 로드
        async function loadChatMessages() {
            try {
                const response = await fetch('indexm.php?action=get_chat_messages&student_id=<?php echo $student_id; ?>');
                const result = await response.json();
                
                if (result.success && result.messages) {
                    let currentPair = { original: null, transformed: null };
                    
                    result.messages.forEach((message, index) => {
                        if (message.message_type === 'original') {
                            currentPair.original = message;
                        } else if (message.message_type === 'transformed') {
                            currentPair.transformed = message;
                            
                            // 쌍이 완성되면 표시
                            if (currentPair.original && currentPair.transformed) {
                                addMessagePair(
                                    currentPair.original.message_content,
                                    currentPair.transformed.message_content
                                );
                                currentPair = { original: null, transformed: null };
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('메시지 로드 실패:', error);
            }
        }

        // 메시지 전송
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // 페르소나 모드가 설정되었는지 확인
            <?php if (!$persona_modes): ?>
                alert('페르소나 모드가 설정되지 않았습니다. 설정 페이지로 이동합니다.');
                window.location.href = 'selectmode.php?userid=<?php echo $student_id; ?>';
                return;
            <?php endif; ?>
            
            input.value = '';
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_chat_message');
                formData.append('message', message);
                
                const response = await fetch('indexm.php?student_id=<?php echo $student_id; ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 메시지 쌍 추가 (원본 + 변환)
                    addMessagePair(message, result.transformed_message);
                } else {
                    alert('메시지 전송 실패: ' + result.message);
                }
            } catch (error) {
                console.error('전송 오류:', error);
                alert('오류가 발생했습니다: ' + error.message);
            }
        }

        // 메시지 쌍 추가 (원본 + 변환)
        function addMessagePair(originalMessage, transformedMessage) {
            const container = document.getElementById('chatContainer');
            const timestamp = new Date().toLocaleTimeString();
            
            const messageHTML = `
                <div class="message-pair" style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.5rem;">선생님 (원본)</div>
                    <div class="chat-message" style="background: rgba(107, 114, 128, 0.2); border: 1px solid rgba(107, 114, 128, 0.3); margin-bottom: 0.75rem;">
                        <div class="message-avatar">👤</div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-name">선생님</span>
                                <span class="message-time">${timestamp}</span>
                            </div>
                            <div class="message-text">${originalMessage}</div>
                        </div>
                    </div>
                    
                    <div style="font-size: 0.75rem; color: #10b981; margin-bottom: 0.5rem;">학생에게 전달 (AI 변환)</div>
                    <div class="chat-message" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);">
                        <div class="message-avatar">🤖</div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-name">AI 변환</span>
                                <span class="message-time">${timestamp}</span>
                                <span style="font-size: 0.7rem; color: #10b981; margin-left: 0.5rem;">✓ 전달됨</span>
                            </div>
                            <div class="message-text">${transformedMessage}</div>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', messageHTML);
            container.scrollTop = container.scrollHeight;
        }
        
        // 메시지 추가 (단일 메시지)
        function addMessage(sender, text) {
            const container = document.getElementById('chatContainer');
            const messageHTML = `
                <div class="chat-message">
                    <div class="message-avatar">
                        ${sender === 'ai' ? '🤖' : '👤'}
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-name">${sender === 'ai' ? 'AI 메타인지 도우미' : '나'}</span>
                            <span class="message-time">${new Date().toLocaleTimeString()}</span>
                        </div>
                        <div class="message-text">${text}</div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', messageHTML);
            container.scrollTop = container.scrollHeight;
        }


        // 대시보드 클릭 핸들러
        function handleDashboardClick(subcategory, title) {
            currentSubcategory = subcategory;
            updateSubcategoryTabs();
            switchMode('menu');
        }

        // 메뉴 클릭 핸들러
        function handleMenuClick(title) {
            alert(`${title} 기능을 실행합니다.`);
        }

        // 윈도우 리사이즈 시 모바일 메뉴 초기화
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
        
        // 터치 스와이프로 메뉴 닫기
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar.classList.contains('active')) {
                if (touchEndX < touchStartX - 50) {
                    closeMobileMenu();
                }
            }
        }

        // 모바일에서 스크롤 성능 최적화
        const subcategoryTabs = document.querySelector('.tabs-container');
        if (subcategoryTabs) {
            subcategoryTabs.addEventListener('touchmove', (e) => {
                e.stopPropagation();
            }, { passive: true });
        }
        
        // 미니맵 토글 (이미 정의된 함수와 충돌 방지)
        if (!window.toggleMinimap) {
            window.toggleMinimap = function() {
                const dropdown = document.getElementById('minimapDropdown');
                dropdown.classList.toggle('active');
            }
        }
        
        // 미니맵 닫기 함수
        function closeMinimap() {
            const dropdown = document.getElementById('minimapDropdown');
            if (dropdown) {
                dropdown.classList.remove('active');
            }
        }
        
        // 클릭 외부 영역 감지 (이미 정의된 이벤트와 충돌 방지)
        if (!window.minimapClickHandler) {
            window.minimapClickHandler = true;
            document.addEventListener('click', function(event) {
                const minimap = document.getElementById('minimapDropdown');
                const button = document.querySelector('.minimap-button');
                
                if (minimap && button && !minimap.contains(event.target) && !button.contains(event.target)) {
                    minimap.classList.remove('active');
                }
            });
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // URL 파라미터 확인
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openChat') === 'true') {
                // 채팅 패널 자동 열기
                setTimeout(() => {
                    openChatPanel();
                }, 500);
            }
            
            // 초기 대시보드 로드
            loadDashboard();
        });
    </script>
    
    <!-- 채팅 패널 오버레이 -->
    <div class="chat-panel-overlay" id="chatPanelOverlay" onclick="closeChatPanel()"></div>
    
    <!-- 채팅 패널 -->
    <div class="chat-panel" id="chatPanel">
        <div class="chat-panel-header">
            <h3 class="chat-panel-title">💬 AI 학습 상담</h3>
            <button class="chat-panel-close" onclick="closeChatPanel()">×</button>
        </div>
        <div class="chat-area">
            <div class="chat-container" id="chatContainer">
                <!-- 채팅 메시지들이 동적으로 생성됩니다 -->
            </div>
        </div>
        <div class="chat-panel-input">
            <div class="chat-panel-input-wrapper">
                <input type="text" id="messageInput" placeholder="궁금한 것을 물어보세요..." onkeypress="if(event.key === 'Enter') sendMessage()">
                <button onclick="sendMessage()">전송</button>
            </div>
        </div>
    </div>
</body>
</html>