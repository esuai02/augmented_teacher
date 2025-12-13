<?php
include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . '/config.php');
global $DB, $USER;
//require_login();

$secret_key = $CFG->openai_api_key;

$studentid = $_GET['studentid'] ?? $USER->id;
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;

// 학생 정보 가져오기
$student = $DB->get_record('user', array('id' => $studentid));
if (!$student) {
    print_error('학생 정보를 찾을 수 없습니다.');
} 

 
$teacher=$DB->get_record_sql("SELECT teacherid FROM mdl_user where id=? ORDER BY id DESC LIMIT 1", array($studentid)); 
$teacherid=$teacher->teacherid;
 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid=? AND fieldid=?", array($USER->id, 22)); 
$role=$userrole->data;

// 통계 데이터 가져오기 (ktm_teaching_interactions 테이블 사용)
$stats = new stdClass();
if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    // 완료된 상호작용 수
    $sql = "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
            WHERE userid = :studentid 
            AND status = 'completed' 
            AND solution_text IS NOT NULL";
    $stats->total_messages = $DB->count_records_sql($sql, array('studentid' => $studentid));
    
    // 읽음 상태 테이블 생성 (없으면)
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('ktm_interaction_read_status')) {
        try {
            $sql_create = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_interaction_read_status (
                id BIGINT(10) NOT NULL AUTO_INCREMENT,
                interaction_id BIGINT(10) NOT NULL,
                student_id BIGINT(10) NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                timeread BIGINT(10) DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_interaction_student (interaction_id, student_id),
                INDEX idx_student_id (student_id),
                INDEX idx_interaction_id (interaction_id)
            )";
            $DB->execute($sql_create);
        } catch (Exception $e) {
            // 테이블 생성 실패시 무시
        }
    }
    
    // 읽은 메시지 수 계산
    if ($dbman->table_exists('ktm_interaction_read_status')) {
        $sql_read = "SELECT COUNT(DISTINCT ti.id) 
                     FROM {ktm_teaching_interactions} ti
                     JOIN {ktm_interaction_read_status} rs ON ti.id = rs.interaction_id
                     WHERE ti.userid = :studentid 
                     AND ti.status = 'completed' 
                     AND ti.solution_text IS NOT NULL
                     AND rs.is_read = 1";
        $stats->read_messages = $DB->count_records_sql($sql_read, array('studentid' => $studentid));
        $stats->unread_messages = $stats->total_messages - $stats->read_messages;
    } else {
        $stats->unread_messages = $stats->total_messages;
        $stats->read_messages = 0;
    }
} else {
    $stats->total_messages = 0;
    $stats->unread_messages = 0;
    $stats->read_messages = 0;
}

// n_aiuse 값 읽어오기 (남은 질문 수)
$halfdayago = time() - (12 * 60 * 60);
$checkgoal = $DB->get_record_sql("SELECT * FROM {abessi_today} WHERE userid=? AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>? ORDER BY id DESC LIMIT 1", array($studentid, $halfdayago));
$n_aiuse = $checkgoal ? $checkgoal->n_aiuse : 0;
$abessi_record_id = $checkgoal ? $checkgoal->id : 0;

// AJAX 요청 처리: n_aiuse 값 증가
if (isset($_POST['action']) && $_POST['action'] === 'increase_aiuse') {
    header('Content-Type: application/json');
    $targetStudentId = intval($_POST['studentid']);
    $halfdayago_ajax = time() - (12 * 60 * 60);
    
    $record = $DB->get_record_sql("SELECT * FROM {abessi_today} WHERE userid=? AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>? ORDER BY id DESC LIMIT 1", array($targetStudentId, $halfdayago_ajax));
    
    if ($record) {
        $new_value = $record->n_aiuse + 3;
        $DB->execute("UPDATE {abessi_today} SET n_aiuse = ? WHERE id = ?", array($new_value, $record->id));
        echo json_encode(['success' => true, 'new_value' => $new_value, 'message' => '질문 수가 3개 추가되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'error' => '레코드를 찾을 수 없습니다. [student_inbox.php:increase_aiuse]']);
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>📬 나의 풀이 메시지함</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../../assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- Fonts and icons -->
    <script src="../../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Open+Sans:300,400,600,700"]},
            custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands"], urls: ['../../assets/css/fonts.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/azzara.min.css">
    <link rel="stylesheet" href="../../assets/css/demo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Step-by-Step TTS Player Styles -->
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/alt42/teachingsupport/css/step_player_modal.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        /* Navigation */
        .nav-top {
            background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .nav-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .nav-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .nav-btn.active {
            background: rgba(255,255,255,0.95);
            color: #7C3AED;
            font-weight: 700;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .nav-btn.active:hover {
            background: rgba(255,255,255,1);
            color: #7C3AED;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        }
        
        .view-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-toggle-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-toggle-btn:hover {
            background: rgba(255,255,255,0.3);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .content-wrapper {
            padding: 30px 20px 0;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .header-content {
            flex: 1;
        }
        
        /* Mini stats styles removed - header cards no longer needed */
        
        @media (max-width: 768px) {
            
            .header {
                flex-direction: column;
            }
        }
        

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .dashboard {
            padding: 30px;
        }


        .refresh-btn {
            background: #4299e1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            margin-bottom: 20px;
        }

        .refresh-btn:hover {
            background: #3182ce;
        }

        .message-list {
            margin-bottom: 30px;
        }
        
        #unifiedMessageList {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        
        #unifiedMessageList .message-item {
            margin-bottom: 12px;
        }
        
        #unifiedMessageList .message-item:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Compact single-line message layout */
        .message-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 50px;
            box-shadow: 0 0 0 rgba(0,0,0,0);
            z-index: 1;
            isolation: isolate;
        }

        .message-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-color: #cbd5e0;
        }

        .message-item.unread:not(.sent-message):not(.received-message) {
            background: #f0f9ff;
            border-color: #7dd3fc;
            border-left: 4px solid #0ea5e9;
        }

        /* Message content in single line */
        .message-content-compact {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .message-text {
            flex: 1;
            color: #374151;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .message-text.unread {
            font-weight: 600;
            color: #1e40af;
        }

        /* Image thumbnail for tooltip */
        .problem-thumbnail {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            object-fit: cover;
            flex-shrink: 0;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .problem-thumbnail:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        /* Tooltip styles */
        .tooltip {
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 8px;
            pointer-events: none;
            max-width: 400px;
            max-height: 300px;
        }

        .tooltip img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 4px;
        }

        /* Compact teacher info */
        .teacher-info-compact {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            flex-shrink: 0;
        }

        /* Clickable teacher info (for alternative solution request) */
        .teacher-info-compact.clickable {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 8px;
            padding: 4px 8px;
            margin: -4px -8px;
        }

        .teacher-info-compact.clickable:hover {
            background-color: rgba(16, 185, 129, 0.1);
            transform: scale(1.05);
        }

        .teacher-info-compact.clickable:active {
            transform: scale(0.98);
        }

        .teacher-avatar-compact {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            flex-shrink: 0;
        }

        .teacher-name-compact {
            font-size: 13px;
            color: #6b7280;
            white-space: nowrap;
        }

        /* Compact time display */
        .message-time-compact {
            font-size: 12px;
            color: #9ca3af;
            min-width: 70px;
            flex-shrink: 0;
            text-align: right;
        }

        /* Compact actions */
        .message-actions-compact {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-shrink: 0;
        }

        /* Status indicator */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-indicator.read {
            background: #10b981;
        }

        .status-indicator.unread {
            background: #f59e0b;
        }

        /* 메시지 타입별 구분 스타일 */
        .message-item.sent-message {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
        }

        .message-item.received-message {
            border-left: 4px solid #0ea5e9;
            background: #f0f9ff;
        }

        /* 읽은 메시지 스타일 */
        .message-item.closed-message {
            border-left: 4px solid #9ca3af;
            background: #f9fafb;
            opacity: 0.8;
        }

        .message-item.closed-message .message-text {
            color: #6b7280;
        }

        .message-item.closed-message .action-btn-compact {
            opacity: 0.7;
        }

        /* 요청 타입 배지 스타일 */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .type-badge.type-capture {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .type-badge.type-textbook {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #60a5fa;
        }

        .type-badge.type-whiteboard {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid #4ade80;
        }

        .type-badge.type-hint {
            background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%);
            color: #854d0e;
            border: 1px solid #facc15;
        }

        .type-badge.type-default {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 1px solid #9ca3af;
        }

        /* 읽은 메시지 토글 효과 */
        .section-title:hover {
            color: #4f46e5;
        }

        #closedMessageList {
            border-top: 1px solid #e5e7eb;
            margin-top: 10px;
            padding-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        
        #closedMessageList .message-item {
            margin-bottom: 12px;
        }
        
        #closedMessageList .message-item:last-child {
            margin-bottom: 0;
        }

        /* 메시지 내용 내 HTML 포맷팅 */
        .message-text strong {
            font-weight: bold;
            color: inherit;
        }

        .message-text em {
            font-style: italic;
            color: inherit;
        }

        .message-text code {
            background: #f1f5f9 !important;
            padding: 2px 4px !important;
            border-radius: 3px !important;
            font-family: 'Courier New', monospace !important;
            font-size: 0.9em !important;
        }

        .message-text del {
            text-decoration: line-through;
            color: #6b7280;
        }

        .message-text u {
            text-decoration: underline;
        }

        .message-text li {
            margin-left: 15px;
            color: inherit;
        }

        .message-text br {
            line-height: 1.4;
        }

        /* Legacy message styles (for expanded view) */
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .teacher-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .teacher-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .teacher-name {
            font-weight: bold;
            color: #2d3748;
            font-size: 16px;
        }

        .teacher-role {
            color: #718096;
            font-size: 12px;
        }

        .message-meta {
            text-align: right;
            font-size: 12px;
            color: #a0aec0;
        }

        .message-time {
            margin-bottom: 5px;
        }

        .message-type {
            background: #e6fffa;
            color: #234e52;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .message-content {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .message-preview {
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }

        .message-preview.expanded {
            max-height: none;
        }

        .message-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(transparent, #f7fafc);
        }

        .message-preview.expanded::after {
            display: none;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        /* Compact action buttons */
        .action-btn-compact {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* 더보기 버튼 스타일 */
        .show-more-btn {
            text-align: center;
            margin-top: 20px;
            padding: 15px 0;
        }

        .show-more-btn button {
            padding: 12px 24px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: #f7fafc;
            color: #4a5568;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .show-more-btn button:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .expand-btn {
            background: none;
            border: none;
            color: #4299e1;
            cursor: pointer;
            font-size: 13px;
            padding: 0;
            margin-top: 10px;
        }

        .expand-btn:hover {
            text-decoration: underline;
        }

        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .no-messages-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }



        /* 모달 스타일 - 전체화면 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            z-index: 1000;
            padding: 0;
        }

        .modal-overlay.active {
            display: block;
        }

        .modal-content {
            background: white;
            border-radius: 0;
            width: 100vw;
            height: 100vh;
            max-width: none;
            max-height: none;
            margin: 0;
            overflow: hidden;
            box-shadow: none;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            z-index: 10;
            height: 60px;
            min-height: 60px;
            max-height: 60px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: bold;
        }

        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0.9;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            opacity: 1;
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .modal-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
            height: calc(100vh - 60px);
            padding: 0;
            margin: 0;
        }
        
        /* Whiteboard iframe container */
        .whiteboard-container {
            width: 100%;
            height: 100%;
            position: relative;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            flex: 1;
            align-self: stretch;
        }
        
        .whiteboard-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        
        .whiteboard-debug-info {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-family: monospace;
            z-index: 1000;
            max-width: 80%;
            word-break: break-all;
        }
        
        .whiteboard-debug-info strong {
            color: #ffd700;
            margin-right: 8px;
        }

        .problem-section {
            flex: 0 0 40%;
            min-width: 350px;
            padding: 30px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            display: block;
        }

        .problem-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .solution-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 2px solid #10b981;
            transition: box-shadow 0.2s ease;
        }

        .solution-image:hover {
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.2);
        }

        #solutionImageSection {
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            margin-top: 20px;
        }

        #solutionImageSection h3 {
            color: #10b981;
            font-weight: 600;
        }

        /* Blur effects for lecture focus mode */
        .lecture-blur {
            filter: blur(5px);
            transition: filter 0.3s ease;
            pointer-events: none;
        }

        .lecture-blur.remove-blur {
            filter: none;
            pointer-events: auto;
        }

        /* Pure blur effect without any overlay messages */

        .solution-section {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            position: relative;
        }

        .solution-content {
            font-size: 16px;
            line-height: 1.8;
            color: #2d3748;
        }

        .solution-line {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.8;
        }

        .solution-line.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .solution-line.teacher {
            background: #ebf8ff;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #4299e1;
        }

        .solution-line.student {
            background: #f0fff4;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #48bb78;
            margin-left: 40px;
        }

        /* 수식 스타일 */
        .solution-line .MathJax {
            font-size: 1.1em !important;
        }

        .solution-line h3 {
            color: #2d3748;
            margin: 20px 0 10px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .solution-line ul, .solution-line ol {
            margin: 10px 0;
            padding-left: 30px;
        }

        .solution-line li {
            margin: 5px 0;
        }

        /* Control Bar Styles */
        .control-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .control-btn:hover {
            background: #e5e7eb;
            color: #1f2937;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .control-btn.active {
            background: #3b82f6;
            color: white;
        }

        /* Question Card Styles (Accordion) */
        .question-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: box-shadow 0.3s ease, border-color 0.3s ease;
            box-shadow: 0 0 0 rgba(0,0,0,0);
            position: relative;
            z-index: 1;
            isolation: isolate;
        }
        
        .question-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .question-header {
            padding: 15px;
            background: #f8fafc;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            color: #2d3748;
        }
        
        .question-header:hover {
            background: #f1f5f9;
        }
        
        .question-answer {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
            background: white;
            display: none;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .question-card.active .question-answer {
            display: block;
        }
        
        .question-card.active .question-header {
            background: #eff6ff;
            color: #2563eb;
        }

        .question-icon {
            margin-right: 10px;
            color: #3b82f6;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .question-card.active .toggle-icon {
            transform: rotate(180deg);
        }
        
        /* 플로팅 헤드폰 아이콘 (mynote.php 스타일 포팅) */
        .listening-test-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto 20px auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 0;
            z-index: 10;
            transition: all 0.3s ease;
            cursor: default;
        }
        
        .listening-test-container.minimized {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px auto;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .listening-test-container.minimized .listening-header {
            display: none;
        }
        
        .listening-test-container.minimized .listening-body {
            display: none;
        }
        
        .listening-test-container.minimized::before {
            content: "🎧";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 28px;
            line-height: 1;
        }
        
        .listening-header {
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
        }
        
        .listening-progress {
            font-size: 13px;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        
        .listening-minimize-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transition: all 0.2s;
        }
        
        .listening-minimize-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .listening-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
        }

        /* 순서 조정: 텍스트(1) → 진행 버튼(2) */
        .listening-body .listening-text-display {
            order: 1;
        }

        .listening-body .listening-progress-dots {
            order: 2;
        }
        
        .listening-text-display {
            background: rgba(255,255,255,0.95);
            border-left: 4px solid #4CAF50;
            padding: 12px;
            margin: 0 0 12px 0;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
            min-height: 120px;
            max-height: 120px;
            overflow-y: auto;
            display: none;
            color: #333;
        }
        
        .listening-text-display.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .listening-progress-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            position: relative;
            height: 32px;
        }
        
        .progress-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .progress-dot:hover {
            background: rgba(255,255,255,0.7);
            transform: scale(1.2);
        }
        
        .progress-dot.active {
            background: white;
            box-shadow: 0 0 8px rgba(255,255,255,0.9);
            transform: scale(1.3);
        }
        
        .progress-dot.completed {
            background: #90EE90;
            box-shadow: 0 0 6px rgba(144,238,144,0.8);
        }

        /* 좌우 화살표 네비게이션 버튼 */
        .nav-arrow {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            transition: all 0.3s ease;
            padding: 0;
            flex-shrink: 0;
        }

        .nav-arrow:hover:not(:disabled) {
            color: rgba(255,255,255,0.8);
            transform: scale(1.3);
        }

        .nav-arrow:disabled {
            color: rgba(255,255,255,0.3);
            cursor: not-allowed;
        }

        /* Search Section Button - 우측 끝에 절대 위치로 배치 */
        .replay-section-btn {
            background: transparent;
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0;
            opacity: 0.9;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .replay-section-btn:hover {
            background: rgba(255,255,255,0.4);
            transform: translateY(-50%) scale(1.2);
            opacity: 1;
            box-shadow: 0 0 8px rgba(255,255,255,0.5);
        }

        .replay-section-btn:active {
            transform: translateY(-50%) scale(0.95);
        }
        
        .speed-control-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 5px 12px;
            border-radius: 14px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 50px;
            text-align: center;
            box-shadow: none;
        }
        
        .speed-control-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.08);
            box-shadow: none;
        }

        .speed-control-btn:active {
            transform: scale(0.96);
            box-shadow: none;
        }

        /* Auto play toggle button styles */
        .auto-play-toggle {
            min-width: 40px;
            height: 26px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            color: white;
            font-weight: bold;
            letter-spacing: 0.5px;
            padding: 0 8px;
            box-shadow: 0 2px 6px rgba(158, 158, 158, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auto-play-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(158, 158, 158, 0.5);
        }

        .auto-play-toggle[data-mode="continuous"] {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            box-shadow: 0 2px 6px rgba(168, 224, 99, 0.4);
        }

        .auto-play-toggle[data-mode="continuous"]:hover {
            box-shadow: 0 3px 10px rgba(168, 224, 99, 0.5);
        }
        
        /* 우측 질문 패널 - 화면의 1/3 크기 */
        .question-panel {
            position: fixed;
            right: 0;
            top: 0;
            width: 33.33vw;
            min-width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0,0,0,0.15);
            z-index: 10001;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            transform: translateX(100%);
        }
        
        .question-panel.show {
            transform: translateX(0);
        }
        
        .question-panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .question-panel-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .question-panel-close {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            font-size: 20px;
            width: 40px;
            height: 40px;
            padding: 0;
            margin: 0;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        
        .question-panel-close:hover {
            background: rgba(255,255,255,0.3);
        }
        
        #btn-regenerate-faq {
            background: rgba(255,255,255,0.2) !important;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.4) !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 20px !important;
            width: 40px;
            height: 40px;
            border-radius: 50% !important;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        
        #btn-regenerate-faq:hover:not(:disabled) {
            background: rgba(255,255,255,0.3) !important;
        }
        
        #btn-regenerate-faq:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        #btn-regenerate-faq i.fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        .question-panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .question-embed-whiteboard {
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
        }
        
        .question-embed-whiteboard iframe {
            width: 100%;
            height: 400px;
            border: none;
            display: block;
        }
        
        .faq-whiteboard-section {
            margin-top: 0;
            padding: 0;
            background: transparent;
        }
        
        .faq-whiteboard-iframe-container {
            width: 100%;
            height: 600px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #f8fafc;
        }
        
        .faq-whiteboard-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .solution-line strong {
            color: #2b6cb0;
            font-weight: bold;
        }

        .solution-line code {
            background: #f0f4f8;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }

        .speaker-label {
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 5px;
        }

        .student .speaker-label {
            color: #276749;
        }


        /* 보낸 메시지 스타일 */
        .sent-message {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .sent-message .teacher-avatar {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }
        

        @media (max-width: 768px) {
            .container {
                margin: 0;
                border-radius: 0;
            }

            .dashboard {
                padding: 20px;
            }


            /* 모바일에서 메시지 항목 조정 */
            .message-item {
                flex-wrap: wrap;
                padding: 10px 12px;
                gap: 8px;
            }

            .message-content-compact {
                order: 1;
                width: 100%;
                margin-bottom: 8px;
            }

            .message-text {
                white-space: normal;
                overflow: visible;
                text-overflow: initial;
                line-height: 1.4;
            }

            .teacher-info-compact {
                order: 2;
                min-width: auto;
            }

            .message-time-compact {
                order: 3;
                min-width: auto;
                text-align: left;
            }

            .message-actions-compact {
                order: 4;
                gap: 4px;
            }

            .action-btn-compact {
                font-size: 10px;
                padding: 4px 8px;
            }

            .problem-thumbnail {
                width: 28px;
                height: 28px;
            }

            .status-indicator {
                order: 5;
            }

            /* Legacy styles for backward compatibility */
            .message-header {
                flex-direction: column;
                gap: 10px;
            }

            .message-meta {
                text-align: left;
            }

            .message-actions {
                flex-direction: column;
            }
        }

        /* Navigation Responsive Styles */
        @media (max-width: 768px) {
            .nav-controls {
                flex-direction: column;
                gap: 15px;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }

            .nav-btn {
                padding: 10px 16px;
                font-size: 13px;
                min-width: auto;
            }

            .content-container {
                padding: 0 15px;
            }

            .content-wrapper {
                padding: 20px 15px 0;
            }

            .nav-top {
                padding: 15px 0;
            }
        }

        @media (max-width: 480px) {
            .header-nav {
                gap: 6px;
            }

            .nav-btn {
                padding: 8px 12px;
                font-size: 12px;
                border-radius: 25px;
            }

            .nav-top {
                padding: 12px 0;
            }

            .content-container {
                padding: 0 10px;
            }

            .content-wrapper {
                padding: 15px 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="nav-top">
        <div class="content-container">
            <div class="nav-controls">
                <div class="header-nav">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                        🏠 홈
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/index42.php?id=<?php echo $studentid; ?>" class="nav-btn"> 
                        👩🏻‍🎨‍ 내공부방
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id=<?php echo $studentid; ?>" class="nav-btn" >
                        📝 오늘
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                        📅 일정
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/goals42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                        🎯 목표
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/student_inbox.php?studentid=<?php echo $studentid; ?>" class="nav-btn active">
                        📩 메세지
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                        📅 수학일기
                    </a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/index.php" class="nav-btn">
                        🚀 AI튜터
                    </a>
                </div>
                <div class="view-controls">
                    <button class="view-toggle-btn" onclick="toggleView()" title="뷰 전환">
                        <i class="fas fa-folder" id="viewIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>
                    <span>📬</span>
                    메시지함
                </h1>
                <p style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <?php echo fullname($student); ?>
                    <span style="background: rgba(255,255,255,0.25); 
                                 backdrop-filter: blur(4px);
                                 padding: 6px 14px; 
                                 border-radius: 20px; 
                                 font-size: 13px;
                                 color: rgba(255,255,255,0.95);
                                 border: 1px solid rgba(255,255,255,0.3);">
                        남은 질문 수 <span id="nAiuseCount" style="font-weight: bold; color: #fff;"><?php echo $n_aiuse; ?></span>
                    </span>
                    <button type="button" onclick="increaseAiuse()" 
                        style="background: rgba(255,255,255,0.3); 
                               color: white; 
                               border: 1px solid rgba(255,255,255,0.4); 
                               border-radius: 50%; 
                               width: 26px; 
                               height: 26px; 
                               cursor: pointer; 
                               font-size: 18px; 
                               font-weight: bold;
                               display: inline-flex;
                               align-items: center;
                               justify-content: center;
                               backdrop-filter: blur(4px);
                               transition: all 0.2s ease;"
                        onmouseover="this.style.background='rgba(255,255,255,0.5)'; this.style.transform='scale(1.1)'" 
                        onmouseout="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='scale(1)'"
                        title="질문 3개 추가">
                        +
                    </button>
                </p>
            </div>
        </div>
        

        <div class="dashboard">
            


            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button class="refresh-btn" onclick="location.reload()">
                    🔄 새로고침
                </button>
                <button class="refresh-btn" style="background: #e74c3c;" onclick="showRequestForm()">
                    📤 풀이 요청하기
                </button>
                <?php if ($role !== 'student'): ?>
                <button class="refresh-btn" style="background: #27ae60;" onclick="openAIAnswer()">
                    🤖 답변생성 페이지 
                </button>
                <?php endif; ?>
            </div>
            
            <!-- 풀이 요청 영역 (펼침/접기) -->
            <div id="requestSection" class="request-section" style="display: none; margin-bottom: 20px; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                <h2 style="margin-bottom: 25px; color: #2d3748; font-size: 20px; font-weight: bold;">
                    <span>📤</span>
                    풀이 요청하기
                </h2>
                <form id="requestForm" onsubmit="submitRequest(event)">
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; color: #2d3748;">
                            문제 업로드
                        </label>
                        <input type="file" id="questionImage" accept="image/*" required style="display: none;">
                        <div id="uploadArea" 
                             onclick="document.getElementById('questionImage').click()" 
                             style="width: 100%; min-height: 250px; border: 3px dashed #e2e8f0; border-radius: 12px; 
                                    display: flex; flex-direction: column; align-items: center; justify-content: center; 
                                    cursor: pointer; background: #f8fafc; transition: all 0.3s; position: relative;"
                             ondragover="event.preventDefault(); this.style.backgroundColor='#edf2f7'; this.style.borderColor='#4299e1';" 
                             ondragleave="this.style.backgroundColor='#f8fafc'; this.style.borderColor='#e2e8f0';"
                             ondrop="handleDrop(event)">
                            <div class="upload-icon" style="font-size: 60px; margin-bottom: 10px;">📷</div>
                            <div class="upload-text" style="color: #718096; text-align: center;">
                                <p style="font-size: 16px; margin-bottom: 5px;">문제 이미지를 드래그하거나 클릭하여 업로드</p>
                                <p style="font-size: 14px;">지원 형식: JPG, PNG, GIF</p>
                            </div>
                            <img id="imagePreview" style="display: none; max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; margin-bottom: 10px; font-weight: bold; color: #2d3748;">
                                문제 유형
                            </label>
                            <select id="problemType" required
                                    style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; background: white; font-size: 15px;">
                                <option value="exam">내신 기출</option>
                                <option value="school">학교 프린트</option>
                                <option value="mathking">MathKing 문제</option>
                                <option value="textbook" selected>시중교재</option>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 10px; font-weight: bold; color: #2d3748;">
                                추가 요청사항 (선택)
                            </label>
                            <input type="text" id="additionalRequest" 
                                   placeholder="예: 더 자세한 설명 부탁드려요"
                                   style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="hideRequestForm()" 
                                style="padding: 12px 24px; font-size: 16px;">
                            취소
                        </button>
                        <button type="submit" class="btn btn-primary" 
                                style="padding: 12px 24px; font-size: 16px; background: #e74c3c;">
                            🚀 풀이 요청 전송
                        </button>
                    </div>
                </form>
            </div>

            <!-- 통합 메시지 목록 -->
            <div class="message-list">
                <h2 class="section-title" id="unifiedMessageListTitle">
                    <span>📨</span>
                    메시지 목록
                    <span id="totalMessageCount" style="margin-left: 10px; background: #e5e7eb; color: #6b7280; padding: 2px 8px; border-radius: 12px; font-size: 12px;">0</span>
                </h2>
                <div id="unifiedMessageList">
                    <!-- 통합 메시지 목록이 여기에 동적으로 생성됩니다 -->
                    <!-- 보낸 메시지(학생 요청)가 먼저 표시되고, 이어서 받은 메시지(교사 응답)가 표시됩니다 -->
                </div>
                <div id="showMoreUnifiedBtn" class="show-more-btn" style="display: none;">
                    <button onclick="showMoreUnifiedMessages()" class="btn-secondary">더보기 📋</button>
                </div>
            </div>

        </div>
    </div>

    <!-- 강의 재생 모달 - 전체화면 -->
    <div class="modal-overlay" id="lectureModal">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                    <h2 class="modal-title" style="margin: 0; font-size: 16px; font-weight: 600;">📚 문제 해설 강의</h2>
                    <button id="btn-solution-view" class="control-btn" title="해설보기" onclick="openSolutionViewFromModal()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); width: 32px; height: 32px; padding: 0; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 16px;">
                        📖
                    </button>
                </div>
                <div id="audioControlsContainer" style="display: flex; gap: 8px; align-items: center; flex: 1; justify-content: center; margin: 0 20px;">
                    <!-- 단계별 재생 컨트롤이 여기에 동적으로 추가됩니다 -->
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-shrink: 0;">
                    <button class="control-btn" id="btn-question-panel" title="자주하는 질문" onclick="initStepQuestions()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); width: 32px; height: 32px; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="modal-close" onclick="closeLectureModal()">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <div class="whiteboard-container">
                    <iframe id="whiteboardFrame" src="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 우측 질문 패널 -->
    <div id="questionPanel" class="question-panel" style="display: none;">
        <div class="question-panel-header">
            <h3>자주하는 질문들</h3>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button class="control-btn" id="btn-regenerate-faq" title="재생성" onclick="regenerateFAQ()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            <button class="question-panel-close" onclick="closeQuestionPanel()">&times;</button>
            </div>
        </div>
        <div id="questionPanelContent" class="question-panel-content"></div>
    </div>
    
    <!-- 우측 해설 패널 -->
    <div id="solutionPanel" class="question-panel">
        <div class="question-panel-header">
            <h3>📖 해설</h3>
            <button class="question-panel-close" onclick="closeSolutionPanel()">&times;</button>
        </div>
        <div id="solutionPanelContent" class="question-panel-content"></div>
    </div>
    
    <!-- 재요청 모달 -->
    <div id="reRequestModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 class="modal-title">🔄 재요청 사유 입력</h2>
                <button class="modal-close" onclick="closeReRequestModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <p style="margin-bottom: 20px; color: #718096;">어떤 부분이 이해가 안 되시나요? 구체적으로 적어주시면 더 자세히 설명해드리겠습니다.</p>
                <textarea id="reRequestReason" rows="5" 
                          placeholder="예: 3번 문제의 풀이 과정이 이해가 안 갑니다. 특히 미분 부분을 더 자세히 설명해주세요."
                          style="width: 100%; padding: 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; resize: vertical;"></textarea>
                <div class="action-buttons" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="closeReRequestModal()">취소</button>
                    <button class="btn btn-primary" onclick="submitReRequest()" style="background: #e74c3c;">재요청 전송</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                ignoreHtmlClass: 'tex2jax_ignore',
                processHtmlClass: 'tex2jax_process'
            }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        const studentId = <?php echo $studentid; ?>;
        const teacherId = <?php echo $_GET['userid'] ?? 0; ?>;
        const apikey = "<?php echo $secret_key; ?>"; // API Key for TTS

        // 남은 질문 수 증가 함수 (+3)
        async function increaseAiuse() {
            try {
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=increase_aiuse&studentid=${studentId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('nAiuseCount').textContent = result.new_value;
                    alert(result.message);
                } else {
                    console.error('[student_inbox.php] increaseAiuse error:', result.error);
                    alert('오류: ' + result.error);
                }
            } catch (error) {
                console.error('[student_inbox.php] increaseAiuse fetch error:', error);
                alert('질문 수 추가 중 오류가 발생했습니다: ' + error.message);
            }
        }

        // 페이지 로드 시 통합 메시지 목록 가져오기
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            loadUnifiedMessages();
            
            // 폼이 제대로 존재하는지 확인
            const form = document.getElementById('requestForm');
            if (form) {
                console.log('Form found:', form);
            } else {
                console.error('Form not found!');
            }
        });
        
        // 통합 메시지 로드 함수 (보낸 메시지 + 받은 메시지 통합)
        async function loadUnifiedMessages() {
            try {
                console.log('🔄 Loading unified messages...');
                
                // 병렬로 두 데이터 모두 로드
                const [receivedResponse, sentResponse] = await Promise.all([
                    fetch(`get_student_messages.php?studentid=${studentId}&page=0&perpage=50`),
                    fetch(`get_sent_requests.php?studentid=${studentId}`)
                ]);
                
                console.log('📡 API Responses received:', {
                    receivedStatus: receivedResponse.status,
                    sentStatus: sentResponse.status
                });
                
                // 각 응답을 개별적으로 처리하여 구체적인 오류 식별
                let receivedData, sentData;
                
                try {
                    receivedData = await receivedResponse.json();
                    console.log('📨 Received messages data:', receivedData);
                } catch (error) {
                    console.error('❌ Error parsing received messages response:', error);
                    receivedData = { success: false, error: '받은 메시지 API 응답 파싱 오류' };
                }
                
                try {
                    sentData = await sentResponse.json();
                    console.log('📤 Sent messages data:', sentData);
                } catch (error) {
                    console.error('❌ Error parsing sent messages response:', error);
                    sentData = { success: false, error: '보낸 메시지 API 응답 파싱 오류' };
                }
                
                // 각 API의 성공/실패 상태 확인
                if (!receivedData.success) {
                    console.warn('⚠️ Received messages API failed:', receivedData.error);
                }
                
                if (!sentData.success) {
                    console.warn('⚠️ Sent messages API failed:', sentData.error);
                }
                
                // 적어도 하나의 API가 성공한 경우 처리 진행
                if (receivedData.success || sentData.success) {
                    const receivedMessages = (receivedData.success ? receivedData.messages : []) || [];
                    const sentMessages = (sentData.success ? sentData.requests : []) || [];
                    
                    console.log('📊 Processing messages:', {
                        receivedCount: receivedMessages.length,
                        sentCount: sentMessages.length
                    });
                    
                    
                    // 통합 메시지 표시 (보낸 + 받은 메시진 통합)
                    console.log('🎯 Displaying unified message list - sent first, then received');
                    
                    displayUnifiedMessages(sentMessages, receivedMessages);
                    
                    // 통계 업데이트
                    const pendingCount = sentMessages.filter(r => r.status === 'pending' || r.status === 'processing').length;
                    updateUnifiedStats(sentMessages.length, receivedMessages.length, pendingCount);
                } else {
                    // 두 API 모두 실패한 경우
                    const errorMessage = `API 호출 실패: 받은메시지(${receivedData.error || 'Unknown'}), 보낸메시지(${sentData.error || 'Unknown'})`;
                    console.error('❌ Both APIs failed:', errorMessage);
                    showError(errorMessage);
                }
            } catch (error) {
                console.error('❌ Critical error in loadUnifiedMessages:', error);
                showError(`메시지를 불러오는 중 시스템 오류가 발생했습니다: ${error.message}`);
            }
        }

        // 통합 메시지 목록 생성 (보낸 메시지와 받은 메시지 분리)
        function createUnifiedMessageList(receivedMessages, sentRequests) {
            console.log('🏗️ Creating unified message list...');
            console.log('📨 Received messages input:', receivedMessages);
            console.log('📤 Sent requests input:', sentRequests);
            
            const activeMessages = []; // 보낸 메시지 (학생 요청)
            const closedMessages = []; // 받은 메시지 (교사 응답)
            
            // 입력 데이터 검증
            const safeReceivedMessages = Array.isArray(receivedMessages) ? receivedMessages : [];
            const safeSentRequests = Array.isArray(sentRequests) ? sentRequests : [];
            
            console.log('✅ Safe arrays:', {
                receivedCount: safeReceivedMessages.length,
                sentCount: safeSentRequests.length
            });
            
            // 받은 메시지 처리 (교사가 학생에게 보낸 응답/해설)
            // 이들은 '받은 메시지' 섹션에 표시됨
            safeReceivedMessages.forEach((message, index) => {
                try {
                    console.log(`📨 Processing received message ${index}:`, message);
                    
                    const messageData = {
                        ...message,
                        type: 'received', // 받은 메시지로 명시
                        timestamp: message.timecreated || Date.now() / 1000,
                        display_time: message.timecreated || Date.now() / 1000,
                        source: 'teacher_response' // 교사 응답임을 명시
                    };
                    
                    // 받은 메시지는 모두 받은 메시지 섹션에 표시
                    closedMessages.push(messageData);
                    console.log(`✅ Received message ${index} added to received messages section`);
                    
                } catch (error) {
                    console.error(`❌ Error processing received message ${index}:`, error);
                }
            });
            
            // 보낸 메시지 처리 (학생이 교사에게 보낸 요청)
            // 모든 상태의 요청을 보낸 메시지 섹션에 표시
            safeSentRequests.forEach((request, index) => {
                try {
                    console.log(`📤 Processing sent request ${index}:`, request);
                    
                    const requestData = {
                        ...request,
                        type: 'sent',
                        timestamp: request.timecreated || Date.now() / 1000,
                        display_time: request.timecreated || Date.now() / 1000,
                        source: 'student_request' // 학생 요청임을 명시
                    };
                    
                    // 모든 학생 요청을 보낸 메시지로 분류 (상태에 관계없이)
                    activeMessages.push(requestData);
                    console.log(`✅ Sent request ${index} (${request.status}) added to sent messages section`);
                    
                } catch (error) {
                    console.error(`❌ Error processing sent request ${index}:`, error);
                }
            });
            
            // 각각 시간순 정렬 (최신순)
            activeMessages.sort((a, b) => b.display_time - a.display_time);
            closedMessages.sort((a, b) => b.display_time - a.display_time);
            
            const result = { activeMessages, closedMessages };
            
            console.log('🎯 Final message classification:', {
                sentMessages: activeMessages.length,
                receivedMessages: closedMessages.length,
                sentTypes: activeMessages.map(m => `${m.type}(${m.status || 'N/A'})`),
                receivedTypes: closedMessages.map(m => `${m.type}(${m.status || 'N/A'})`)
            });
            
            return result;
        }

        // 전역 메시지 저장소 (통합 메시지 리스트를 위해)
        let allUnifiedMessages = [];  // 통합 메시지 (보낸 + 받은)
        let currentUnifiedPage = 0;
        const MESSAGES_PER_PAGE = 5;
        
        // Legacy variables for compatibility (deprecated)
        let allSentMessages = [];
        let allReceivedMessages = [];
        let currentSentPage = 0;
        let currentReceivedPage = 0;

        // 보낸 메시지 표시 (학생이 보낸 풀이 요청)
        function displayUnifiedMessages(messages) {
            console.log('🎨 Displaying sent messages:', messages);
            const messageList = document.getElementById('messageList');
            const showMoreBtn = document.getElementById('showMoreSentBtn');
            
            // 전역 저장소에 저장
            allSentMessages = Array.isArray(messages) ? messages : [];
            currentSentPage = 0;
            
            if (allSentMessages.length === 0) {
                messageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">📤</div>
                        <h3>보낸 메시지가 없습니다</h3>
                        <p>풀이 요청한 메시지가 여기에 표시됩니다.</p>
                    </div>
                `;
                showMoreBtn.style.display = 'none';
                return;
            }
            
            // 처음 5개 메시지만 표시
            displaySentMessagesPage();
        }

        // 보낸 메시지 페이지별 표시
        function displaySentMessagesPage() {
            const messageList = document.getElementById('messageList');
            const showMoreBtn = document.getElementById('showMoreSentBtn');
            
            const startIndex = 0;
            const endIndex = (currentSentPage + 1) * MESSAGES_PER_PAGE;
            const messagesToShow = allSentMessages.slice(startIndex, endIndex);
            
            try {
                messageList.innerHTML = messagesToShow.map((message, index) => {
                    console.log(`🎨 Rendering sent message ${index}:`, message);
                    
                    // 메시지 타입에 따라 다른 카드 렌더링
                    if (message.type === 'received' && message.source === 'teacher_response') {
                        // 교사 응답 메시지 (보낸 메시지에는 나오지 않아야 함)
                        console.warn('⚠️ Teacher response in sent messages - this should not happen');
                        return createReceivedMessageCard(message);
                    } else if (message.type === 'sent' && message.source === 'student_request') {
                        // 학생 요청 메시지
                        return createSentMessageCard(message);
                    } else {
                        console.warn('⚠️ Unknown message type in sent messages:', message);
                        return createGenericMessageCard(message);
                    }
                }).join('');
                
                // 더보기 버튼 표시 여부 결정
                if (endIndex >= allSentMessages.length) {
                    showMoreBtn.style.display = 'none';
                } else {
                    showMoreBtn.style.display = 'block';
                }
                
                console.log('✅ Successfully rendered sent messages page');
                
            } catch (error) {
                console.error('❌ Error rendering sent messages:', error);
                messageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">⚠️</div>
                        <h3>메시지 표시 오류</h3>
                        <p>보낸 메시지를 표시하는 중 오류가 발생했습니다: ${error.message}</p>
                    </div>
                `;
            }
            
            // 수식 렌더링 다시 실행
            setTimeout(rerenderMath, 100);
        }

        // 더보기 버튼 클릭 핸들러 (보낸 메시지)
        function showMoreSentMessages() {
            currentSentPage++;
            displaySentMessagesPage();
        }
        
        // 통합 메시지 표시 (보낸 + 받은 메시지 통합)
        function displayUnifiedMessages(sentMessages, receivedMessages) {
            console.log('🎨 Displaying unified messages - Sent:', sentMessages, 'Received:', receivedMessages);
            
            // 통합 메시지 생성: 보낸 메시지 먼저, 그 다음 받은 메시지
            const unifiedMessages = [];
            
            // 1. 보낸 메시지 (학생 요청) 먼저 추가
            if (Array.isArray(sentMessages)) {
                sentMessages.forEach(message => {
                    unifiedMessages.push({
                        ...message,
                        messageCategory: 'sent',
                        source: 'student_request'
                    });
                });
            }
            
            // 2. 받은 메시지 (교사 응답) 이어서 추가
            if (Array.isArray(receivedMessages)) {
                receivedMessages.forEach(message => {
                    unifiedMessages.push({
                        ...message,
                        messageCategory: 'received',
                        source: 'teacher_response'
                    });
                });
            }
            
            // 전역 저장소에 저장
            allUnifiedMessages = unifiedMessages;
            currentUnifiedPage = 0;
            
            // 렌더링 시작
            displayUnifiedMessagesPage();
        }
        
        // 통합 메시지 페이지별 표시
        function displayUnifiedMessagesPage() {
            const unifiedMessageList = document.getElementById('unifiedMessageList');
            const showMoreBtn = document.getElementById('showMoreUnifiedBtn');
            const totalCountElement = document.getElementById('totalMessageCount');
            
            // 없는 경우 처리
            if (!unifiedMessageList) {
                console.error('❌ unifiedMessageList element not found!');
                return;
            }
            
            const startIndex = 0;
            const endIndex = (currentUnifiedPage + 1) * MESSAGES_PER_PAGE;
            const messagesToShow = allUnifiedMessages.slice(startIndex, endIndex);
            
            // 총 메시지 수 업데이트
            if (totalCountElement) {
                totalCountElement.textContent = allUnifiedMessages.length;
            }
            
            if (allUnifiedMessages.length === 0) {
                unifiedMessageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">📨</div>
                        <h3>메시지가 없습니다</h3>
                        <p>아직 주고받은 메시지가 없습니다.</p>
                    </div>
                `;
                if (showMoreBtn) showMoreBtn.style.display = 'none';
                return;
            }
            
            try {
                unifiedMessageList.innerHTML = messagesToShow.map((message, index) => {
                    console.log(`🎨 Rendering unified message ${index}:`, message);
                    
                    // 메시지 카테고리에 따라 렌더링
                    if (message.messageCategory === 'sent') {
                        // 보낸 메시지 (학생 요청)
                        return createSentMessageCard(message);
                    } else if (message.messageCategory === 'received') {
                        // 받은 메시지 (교사 응답) - 풀이보기 버튼 포함
                        return createReceivedMessageCard(message);
                    } else {
                        console.warn('⚠️ Unknown message category:', message.messageCategory);
                        return createGenericMessageCard(message);
                    }
                }).join('');
                
                // 더보기 버튼 표시 여부 결정
                if (showMoreBtn) {
                    if (endIndex >= allUnifiedMessages.length) {
                        showMoreBtn.style.display = 'none';
                    } else {
                        showMoreBtn.style.display = 'block';
                    }
                }
                
            } catch (error) {
                console.error('❌ Error displaying unified messages:', error);
                unifiedMessageList.innerHTML = `
                    <div class="error-message">
                        <h3>오류 발생</h3>
                        <p>메시지를 표시하는 중 오류가 발생했습니다: ${error.message}</p>
                    </div>
                `;
            }
            
            // MathJax 재렌더링
            setTimeout(rerenderMath, 100);
        }
        
        // 더보기 버튼 클릭 핸들러 (통합 메시지)
        function showMoreUnifiedMessages() {
            currentUnifiedPage++;
            displayUnifiedMessagesPage();
        }

        // 일반 메시지 카드 생성 (fallback)
        function createGenericMessageCard(message) {
            return `
                <div class="message-item generic-message">
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-type">타입: ${message.type || 'Unknown'}</span>
                            <span class="message-time">${formatTime(message.timestamp)}</span>
                        </div>
                        <div class="message-text">
                            ${message.subject || message.problemType || '내용 없음'}
                        </div>
                        <div class="message-status">
                            상태: ${message.status || 'N/A'}
                        </div>
                    </div>
                </div>
            `;
        }

        // 받은 메시지 표시 (교사 응답 메시지)
        function displayClosedMessages(messages) {
            console.log('📥 Displaying received messages:', messages);
            const closedMessageList = document.getElementById('closedMessageList');
            const closedMessageCount = document.getElementById('closedMessageCount');
            const showMoreBtn = document.getElementById('showMoreReceivedBtn');
            
            // 전역 저장소에 저장
            allReceivedMessages = Array.isArray(messages) ? messages : [];
            currentReceivedPage = 0;
            
            // 카운트 업데이트 (방어적 프로그래밍)
            if (closedMessageCount) {
                closedMessageCount.textContent = allReceivedMessages.length;
            } else {
                console.warn('⚠️ closedMessageCount element not found');
            }
            
            if (allReceivedMessages.length === 0) {
                closedMessageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">📥</div>
                        <h3>받은 메시지가 없습니다</h3>
                        <p>교사의 응답 메시지가 여기에 표시됩니다.</p>
                    </div>
                `;
                showMoreBtn.style.display = 'none';
                return;
            }

            // 처음 5개 메시지만 표시
            displayReceivedMessagesPage();
        }

        // 받은 메시지 페이지별 표시
        function displayReceivedMessagesPage() {
            const closedMessageList = document.getElementById('closedMessageList');
            const showMoreBtn = document.getElementById('showMoreReceivedBtn');
            
            const startIndex = 0;
            const endIndex = (currentReceivedPage + 1) * MESSAGES_PER_PAGE;
            const messagesToShow = allReceivedMessages.slice(startIndex, endIndex);
            
            try {
                closedMessageList.innerHTML = messagesToShow.map((message, index) => {
                    console.log(`📥 Rendering received message ${index}:`, message);
                    
                    // 메시지 타입에 따른 카드 렌더링
                    if (message.type === 'received' && message.source === 'teacher_response') {
                        // 교사 응답 메시지 (받은 메시지)
                        return createReceivedMessageCard(message);
                    } else if (message.type === 'sent' && message.source === 'student_request') {
                        // 완료된 학생 요청 메시지 (받은 메시지에는 표시하지 않음)
                        console.warn('⚠️ Student request in received messages - this should not happen');
                        return createClosedSentMessageCard(message);
                    } else {
                        console.warn('⚠️ Unknown message type in received messages:', message);
                        return createGenericMessageCard(message);
                    }
                }).join('');
                
                // 더보기 버튼 표시 여부 결정
                if (endIndex >= allReceivedMessages.length) {
                    showMoreBtn.style.display = 'none';
                } else {
                    showMoreBtn.style.display = 'block';
                }
                
                console.log('✅ Successfully rendered received messages page');
                
            } catch (error) {
                console.error('❌ Error rendering received messages:', error);
                closedMessageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">⚠️</div>
                        <h3>메시지 표시 오류</h3>
                        <p>받은 메시지를 표시하는 중 오류가 발생했습니다: ${error.message}</p>
                    </div>
                `;
            }
            
            // 수식 렌더링 다시 실행
            setTimeout(rerenderMath, 100);
        }

        // 더보기 버튼 클릭 핸들러 (받은 메시지)
        function showMoreReceivedMessages() {
            currentReceivedPage++;
            displayReceivedMessagesPage();
        }


        // 읽은 보낸 메시지 카드 생성
        function createClosedSentMessageCard(request) {
            const imageUrl = request.problemImage ? getProblemImageUrl(request.problemImage) : '';
            const typeLabel = getTypeLabel(request.type);
            
            return `
                <div class="message-item closed-message" id="closed-request-${request.id}">
                    <!-- 문제 이미지 썸네일 (tooltip용) -->
                    ${imageUrl ? `
                        <img class="problem-thumbnail" 
                             src="${imageUrl}" 
                             alt="문제 이미지"
                             onmouseover="showImageTooltip(event, '${imageUrl}')"
                             onmouseout="hideImageTooltip()"
                             onerror="this.style.display='none'">
                    ` : `
                        <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                    `}
                    
                    <!-- 요청 타입 배지 -->
                    <div class="type-badge ${typeLabel.className}" title="${typeLabel.description}">
                        ${typeLabel.icon} ${typeLabel.text}
                    </div>
                    
                    <!-- 메시지 내용 -->
                    <div class="message-content-compact">
                        <div class="message-text">
                            ✅ ${request.modificationPrompt ? 
                                truncateText(processTextContent(request.modificationPrompt), 60) : 
                                request.problemType ? `${request.problemType} 문제 ${request.type === 'askhint' ? '힌트 완료' : '풀이 완료'}` : request.type === 'askhint' ? '힌트 완료' : '풀이 완료'
                            }
                        </div>
                    </div>
                    
                    <!-- 해설 보기 버튼 -->
                    <button class="action-btn-compact btn-primary" onclick="openLectureModal(${request.id})" title="해설 보기">
                        📖 해설보기
                    </button>
                    
                    <!-- 완료 정보 -->
                    <div class="teacher-info-compact clickable" onclick="requestAlternativeSolution(${request.id})" title="클릭하여 다른 풀이 요청">
                        <div class="teacher-avatar-compact" style="background: #10b981;">
                            ✓
                        </div>
                        <span class="teacher-name-compact">완료됨</span>
                    </div>
                    
                    <!-- 시간 -->
                    <div class="message-time-compact">
                        ${request.timeAgo || formatTimeCompact(request.timecreated)}
                    </div>
                    
                    <!-- 완료 상태 인디케이터 -->
                    <div class="status-indicator read" title="완료됨">
                    </div>
                </div>
            `;
        }

        // 받은 메시지 카드 생성
        function createReceivedMessageCard(message) {
            const typeLabel = getTypeLabel(message.type);
            return `
                <div class="message-item received-message unread" id="message-${message.id}">
                    <!-- 문제 이미지 썸네일 (tooltip용, 클릭 시 화이트보드 링크 열기) -->
                    ${message.problem_image ? `
                        <img class="problem-thumbnail" 
                             src="${getProblemImageUrl(message.problem_image)}" 
                             alt="문제 이미지"
                             style="cursor: pointer;"
                             onclick="openWhiteboardLink(${message.interaction_id})"
                             onmouseover="showImageTooltip(event, '${getProblemImageUrl(message.problem_image)}')"
                             onmouseout="hideImageTooltip()"
                             onerror="this.style.display='none'"
                             title="클릭하여 화이트보드 열기">
                    ` : `
                        <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                    `}
                    
                    <!-- 요청 타입 배지 -->
                    <div class="type-badge ${typeLabel.className}" title="${typeLabel.description}">
                        ${typeLabel.icon} ${typeLabel.text}
                    </div>
                    
                    <!-- 메시지 내용 -->
                    <div class="message-content-compact">
                        <div class="message-text unread">
                            📥 ${truncateText(processTextContent(message.fullmessage.replace(/\\n/g, ' ')), 80)}
                        </div>
                    </div>
                    
                    <!-- 풀이보기/힌트보기 버튼 -->
                    <button class="action-btn-compact btn-primary" onclick="handleSolutionView(${message.interaction_id})" title="${message.type === 'askhint' ? '힌트보기' : '풀이보기'}">
                        📖 ${message.type === 'askhint' ? '힌트보기' : '풀이보기'}
                    </button>
                    
                    <!-- 선생님 정보 -->
                    <div class="teacher-info-compact">
                        <div class="teacher-avatar-compact">
                            ${message.teacher_name.charAt(0)}
                        </div>
                        <span class="teacher-name-compact">${message.teacher_name} T</span>
                    </div>
                    
                    <!-- 시간 -->
                    <div class="message-time-compact">
                        ${formatTimeCompact(message.timecreated)}
                    </div>
                    
                    <!-- 재요청 버튼 -->
                    <button class="action-btn-compact btn-secondary" onclick="showReRequestModal(${message.interaction_id})" title="재요청">
                        🔄
                    </button>
                    
                    <!-- 읽음 처리 버튼 -->
                    <button class="action-btn-compact btn-success" onclick="markAsRead(${message.id})" title="읽음 처리">
                        ✓
                    </button>
                </div>
            `;
        }

        // 보낸 메시지 카드 생성
        function createSentMessageCard(request) {
            const imageUrl = request.problemImage ? getProblemImageUrl(request.problemImage) : '';
            const statusIcon = getStatusIcon(request.status);
            const isCompleted = request.status === 'completed' || request.status === 'complete' || request.status === 'sent' || request.hasSolution;
            const typeLabel = getTypeLabel(request.type);
            
            return `
                <div class="message-item sent-message" id="request-${request.id}">
                    <!-- 문제 이미지 썸네일 (tooltip용, 클릭 시 화이트보드 링크 열기) -->
                    ${imageUrl ? `
                        <img class="problem-thumbnail" 
                             src="${imageUrl}" 
                             alt="문제 이미지"
                             style="cursor: ${isCompleted ? 'pointer' : 'default'};"
                             ${isCompleted ? `onclick="openWhiteboardLink(${request.id})"` : ''}
                             onmouseover="showImageTooltip(event, '${imageUrl}')"
                             onmouseout="hideImageTooltip()"
                             onerror="this.style.display='none'"
                             title="${isCompleted ? '클릭하여 화이트보드 열기' : '답변 대기 중'}">
                    ` : `
                        <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                    `}
                    
                    <!-- 요청 타입 배지 -->
                    <div class="type-badge ${typeLabel.className}" title="${typeLabel.description}">
                        ${typeLabel.icon} ${typeLabel.text}
                    </div>
                    
                    <!-- 메시지 내용 -->
                    <div class="message-content-compact" style="flex: 1; min-width: 0;">
                        ${request.whiteboardUrl ? `
                            <a href="${request.whiteboardUrl}" 
                               target="_blank"
                               class="message-text" 
                               style="cursor: pointer; text-decoration: none; color: inherit; display: block;"
                               title="클릭하여 화이트보드 열기">
                                ${isCompleted ? '✅' : '📤'} ${request.modificationPrompt ? 
                                    truncateText(processTextContent(request.modificationPrompt), 60) : 
                                    request.problemType ? `${request.problemType} 문제 ${isCompleted ? (request.type === 'askhint' ? '힌트 완료' : '풀이 완료') : (request.type === 'askhint' ? '힌트 요청' : '풀이 요청')}` : isCompleted ? (request.type === 'askhint' ? '힌트 완료' : '풀이 완료') : (request.type === 'askhint' ? '힌트 요청' : '풀이 요청')
                                }
                            </a>
                        ` : `
                            <div class="message-text" title="화이트보드 ID가 없습니다">
                            ${isCompleted ? '✅' : '📤'} ${request.modificationPrompt ? 
                                truncateText(processTextContent(request.modificationPrompt), 60) : 
                                request.problemType ? `${request.problemType} 문제 ${isCompleted ? (request.type === 'askhint' ? '힌트 완료' : '풀이 완료') : (request.type === 'askhint' ? '힌트 요청' : '풀이 요청')}` : isCompleted ? (request.type === 'askhint' ? '힌트 완료' : '풀이 완료') : (request.type === 'askhint' ? '힌트 요청' : '풀이 요청')
                            }
                        </div>
                        `}
                    </div>
                    
                    ${isCompleted ? `
                        <!-- 풀이보기/힌트보기 버튼 (완료된 경우) -->
                        <button class="action-btn-compact btn-primary" onclick="handleSolutionView(${request.id})" title="${request.type === 'askhint' ? '힌트보기' : '풀이보기'}">
                            📖 ${request.type === 'askhint' ? '힌트보기' : '풀이보기'}
                        </button>
                        
                        <!-- 완료 정보 -->
                        <div class="teacher-info-compact clickable" onclick="requestAlternativeSolution(${request.id})" title="클릭하여 다른 풀이 요청">
                            <div class="teacher-avatar-compact" style="background: #10b981;">
                                ✓
                            </div>
                            <span class="teacher-name-compact">완료됨</span>
                        </div>
                        
                        <!-- 상태 인디케이터 (완료) -->
                        <div class="status-indicator read" title="${request.type === 'askhint' ? '힌트 완료' : '풀이 완료'}">
                        </div>
                    ` : `
                        <!-- 상태 표시 (대기 중) -->
                        <button class="action-btn-compact btn-secondary" disabled title="답변 대기 중">
                            ${statusIcon} ${request.statusLabel || '대기중'}
                        </button>
                        
                        <!-- 요청 시간 -->
                        <div class="teacher-info-compact">
                            <div class="teacher-avatar-compact" style="background: #6b7280;">
                                📤
                            </div>
                            <span class="teacher-name-compact">요청함</span>
                        </div>
                        
                        <!-- 상태 인디케이터 (대기) -->
                        <div class="status-indicator unread" title="답변 대기 중">
                        </div>
                    `}
                    
                    <!-- 시간 -->
                    <div class="message-time-compact">
                        ${request.timeAgo || formatTimeCompact(request.timecreated)}
                    </div>
                </div>
            `;
        }

        // 통계 업데이트
        function updateUnifiedStats(activeCount, closedCount, pendingCount) {
            const totalCount = activeCount + closedCount;
            // 컴팩트 통계 업데이트
            updateCompactStats(activeCount, closedCount, pendingCount);
        }

        // *** 읽은 메시지 토글 기능 제거됨 - 사용자 요청에 따라 삭제 ***

        // 메시지 목록 로드 (Legacy - 호환성 유지)
        async function loadMessages() {
            try {
                const response = await fetch(`get_student_messages.php?studentid=${studentId}&page=0&perpage=10`);
                const data = await response.json();
                
                if (data.success) {
                    displayMessages(data.messages);
                } else {
                    showError(data.error);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                showError('메시지를 불러오는 중 오류가 발생했습니다.');
            }
        }
        
        // 보낸 메시지 목록 로드
        async function loadSentMessages() {
            try {
                const response = await fetch(`get_sent_requests.php?studentid=${studentId}`);
                const data = await response.json();
                
                if (data.success) {
                    displaySentMessages(data.requests);
                } else {
                    showError(data.error);
                }
            } catch (error) {
                console.error('Error loading sent messages:', error);
                showError('보낸 메시지를 불러오는 중 오류가 발생했습니다.');
            }
        }
        
        
        
        // 컴팩트 통계 업데이트 함수 (방어적 프로그래밍)
        function updateCompactStats(activeCount, closedCount, pendingCount) {
            // 각 요소의 존재를 확인하고 안전하게 업데이트
            const compactActiveElement = document.getElementById('compactActiveCount');
            const compactClosedElement = document.getElementById('compactClosedCount');
            const compactPendingElement = document.getElementById('compactPendingCount');
            
            if (compactActiveElement) {
                compactActiveElement.textContent = activeCount;
            } else {
                console.warn('⚠️ compactActiveCount element not found');
            }
            
            if (compactClosedElement) {
                compactClosedElement.textContent = closedCount;
            } else {
                console.warn('⚠️ compactClosedCount element not found');
            }
            
            if (compactPendingElement) {
                compactPendingElement.textContent = pendingCount;
            } else {
                console.warn('⚠️ compactPendingCount element not found');
            }
        }

        // 메시지 목록 표시 (새로운 컴팩트 형식)
        function displayMessages(messages) {
            const messageList = document.getElementById('messageList');
            
            if (messages.length === 0) {
                messageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">📭</div>
                        <h3>메시지가 없습니다</h3>
                        <p>아직 받은 풀이 메시지가 없습니다.</p>
                    </div>
                `;
                return;
            }

            messageList.innerHTML = messages.map(message => `
                <div class="message-item ${message.is_read ? '' : 'unread'}" id="message-${message.id}">
                    <!-- 문제 이미지 썸네일 (tooltip용, 클릭 시 화이트보드 링크 열기) -->
                    ${message.problem_image ? `
                        <img class="problem-thumbnail" 
                             src="${getProblemImageUrl(message.problem_image)}" 
                             alt="문제 이미지"
                             style="cursor: pointer;"
                             onclick="openWhiteboardLink(${message.interaction_id})"
                             onmouseover="showImageTooltip(event, '${getProblemImageUrl(message.problem_image)}')"
                             onmouseout="hideImageTooltip()"
                             onerror="this.style.display='none'"
                             title="클릭하여 화이트보드 열기">
                    ` : `
                        <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                    `}
                    
                    <!-- 메시지 내용 -->
                    <div class="message-content-compact">
                        <div class="message-text ${message.is_read ? '' : 'unread'}">
                            ${truncateText(processTextContent(message.fullmessage.replace(/\\n/g, ' ')), 80)}
                        </div>
                    </div>
                    
                    <!-- AI 설명 보기 버튼 -->
                    <button class="action-btn-compact btn-primary" onclick="openLectureModal(${message.interaction_id})" title="AI 설명 보기">
                        📖 AI 설명
                    </button>
                    
                    <!-- 선생님 정보 -->
                    <div class="teacher-info-compact">
                        <div class="teacher-avatar-compact">
                            ${message.teacher_name.charAt(0)}
                        </div>
                        <span class="teacher-name-compact">${message.teacher_name} T</span>
                    </div>
                    
                    <!-- 시간 -->
                    <div class="message-time-compact">
                        ${formatTimeCompact(message.timecreated)}
                    </div>
                    
                    <!-- 재요청 버튼 -->
                    <button class="action-btn-compact btn-secondary" onclick="showReRequestModal(${message.interaction_id})" title="재요청">
                        🔄
                    </button>
                    
                    <!-- 읽음 상태 -->
                    <div class="status-indicator ${message.is_read ? 'read' : 'unread'}" 
                         title="${message.is_read ? '읽음' : '읽지 않음'}"
                         ${!message.is_read ? `onclick="markAsRead(${message.id})" style="cursor: pointer;"` : ''}>
                    </div>
                </div>
            `).join('');
        }

        // 텍스트 말줄임 함수 (HTML 태그 고려, 스마트 자르기)
        function truncateText(text, maxLength) {
            if (!text) return text;
            
            // HTML 태그가 포함된 경우 순수 텍스트 길이로 계산
            const textOnly = text.replace(/<[^>]*>/g, '');
            
            if (textOnly.length <= maxLength) return text;
            
            // HTML이 포함된 텍스트를 자를 때는 조심스럽게 처리
            let truncated = '';
            let textLength = 0;
            let i = 0;
            
            while (i < text.length && textLength < maxLength) {
                if (text[i] === '<') {
                    // HTML 태그는 길이에 포함하지 않고 그대로 추가
                    const tagEnd = text.indexOf('>', i);
                    if (tagEnd !== -1) {
                        truncated += text.substring(i, tagEnd + 1);
                        i = tagEnd + 1;
                    } else {
                        break;
                    }
                } else {
                    truncated += text[i];
                    textLength++;
                    i++;
                }
            }
            
            // 스마트 자르기: 단어 중간이나 문장 중간에서 자르지 않도록 개선
            if (textLength >= maxLength) {
                const textOnlyTruncated = truncated.replace(/<[^>]*>/g, '');
                
                // 마지막 문장부호나 공백을 찾아서 그곳에서 자르기
                const lastSentenceEnd = Math.max(
                    textOnlyTruncated.lastIndexOf('.'),
                    textOnlyTruncated.lastIndexOf('!'),
                    textOnlyTruncated.lastIndexOf('?'),
                    textOnlyTruncated.lastIndexOf('다'),  // Korean sentence endings
                    textOnlyTruncated.lastIndexOf('요'),
                    textOnlyTruncated.lastIndexOf('니다'),
                    textOnlyTruncated.lastIndexOf(' ')   // Space
                );
                
                // 적절한 자르기 지점이 있고, 너무 짧지 않다면 그곳에서 자르기
                if (lastSentenceEnd > maxLength * 0.7) {
                    // HTML 포함 텍스트에서 해당 위치 찾기
                    let htmlLength = 0;
                    let htmlPos = 0;
                    
                    while (htmlPos < text.length && htmlLength < lastSentenceEnd + 1) {
                        if (text[htmlPos] === '<') {
                            const tagEnd = text.indexOf('>', htmlPos);
                            if (tagEnd !== -1) {
                                htmlPos = tagEnd + 1;
                            } else {
                                break;
                            }
                        } else {
                            htmlLength++;
                            htmlPos++;
                        }
                    }
                    
                    truncated = text.substring(0, htmlPos);
                }
            }
            
            return truncated + '...';
        }

        // 수식 태그를 안전하게 처리하는 함수
        function processMathContent(content) {
            if (!content) return content;
            
            // LaTeX 수식 태그들을 안전하게 처리
            let processedContent = content
                // \text{} 태그 처리
                .replace(/\\text\{([^}]+)\}/g, '$1')
                // \frac{a}{b} 태그를 분수 형태로 변환
                .replace(/\\frac\{([^}]+)\}\{([^}]+)\}/g, '($1)/($2)')
                // \sqrt{} 태그 처리
                .replace(/\\sqrt\{([^}]+)\}/g, '√($1)')
                // \times 태그 처리
                .replace(/\\times/g, '×')
                // \cdot 태그 처리
                .replace(/\\cdot/g, '·')
                // \pm 태그 처리
                .replace(/\\pm/g, '±')
                // \div 태그 처리
                .replace(/\\div/g, '÷')
                // \pi 태그 처리
                .replace(/\\pi/g, 'π')
                // \alpha, \beta 등 그리스 문자 처리
                .replace(/\\alpha/g, 'α')
                .replace(/\\beta/g, 'β')
                .replace(/\\gamma/g, 'γ')
                .replace(/\\theta/g, 'θ')
                // 지수 처리: ^{n} 형태
                .replace(/\^\{([^}]+)\}/g, '^($1)')
                // 아래첨자 처리: _{n} 형태
                .replace(/\_\{([^}]+)\}/g, '_($1)')
                // \left( \right) 괄호 처리
                .replace(/\\left\(/g, '(')
                .replace(/\\right\)/g, ')')
                // 기타 LaTeX 명령어들 제거
                .replace(/\\[a-zA-Z]+\{?/g, '')
                .replace(/\}/g, '');
            
            return processedContent;
        }

        // MathJax 재렌더링 함수
        function rerenderMath() {
            if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                MathJax.typesetPromise().catch((err) => {
                    console.warn('MathJax rendering error:', err);
                });
            }
        }

        // 마크다운 텍스트를 HTML로 변환하는 함수
        function processMarkdownContent(content) {
            if (!content) return content;
            
            let processedContent = content
                // Bold text: **text** -> <strong>text</strong>
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Italic text: *text* -> <em>text</em> (수식 기호와 구분하기 위해 더 정확한 패턴 사용)
                .replace(/\*([^*\s][^*]*[^*\s])\*/g, '<em>$1</em>')
                // Code inline: `code` -> <code>code</code>
                .replace(/`([^`\n]+?)`/g, '<code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px; font-family: monospace;">$1</code>')
                // Strikethrough: ~~text~~ -> <del>text</del>
                .replace(/~~(.*?)~~/g, '<del>$1</del>')
                // Underline: __text__ -> <u>text</u>
                .replace(/__(.*?)__/g, '<u>$1</u>')
                // 줄바꿈 처리: \n -> <br>
                .replace(/\n/g, '<br>')
                // 번호 목록: 1. text -> <ol><li>text</li></ol> (간단한 형태)
                .replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>')
                // 불릿 목록: - text 또는 * text -> <ul><li>text</li></ul>
                .replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>');
                
            return processedContent;
        }

        // 통합 텍스트 처리 함수 (마크다운 + 수식)
        function processTextContent(content) {
            if (!content) return content;
            
            // 1단계: 수식 처리
            let processed = processMathContent(content);
            
            // 2단계: 마크다운 처리
            processed = processMarkdownContent(processed);
            
            return processed;
        }

        // 문제 이미지 URL 생성
        function getProblemImageUrl(imagePath) {
            if (!imagePath) return '';
            if (imagePath.startsWith('http') || imagePath.startsWith('data:')) {
                return imagePath;
            } else if (imagePath.startsWith('images/')) {
                return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' + imagePath;
            } else {
                return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/images/' + imagePath;
            }
        }

        // 시간 표시를 더 컴팩트하게
        function formatTimeCompact(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) {
                return '방금';
            } else if (diff < 3600000) {
                return Math.floor(diff / 60000) + '분';
            } else if (diff < 86400000) {
                return Math.floor(diff / 3600000) + '시간';
            } else if (diff < 86400000 * 7) {
                return Math.floor(diff / 86400000) + '일';
            } else {
                return date.toLocaleDateString('ko-KR', {month: '2-digit', day: '2-digit'});
            }
        }

        // 이미지 툴팁 표시
        function showImageTooltip(event, imageUrl) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.id = 'imageTooltip';
            tooltip.innerHTML = `<img src="${imageUrl}" alt="문제 이미지">`;
            
            document.body.appendChild(tooltip);
            
            // 위치 조정
            const rect = event.target.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            let left = rect.right + 10;
            let top = rect.top;
            
            // 화면 오른쪽 경계 체크
            if (left + tooltipRect.width > window.innerWidth) {
                left = rect.left - tooltipRect.width - 10;
            }
            
            // 화면 아래쪽 경계 체크
            if (top + tooltipRect.height > window.innerHeight) {
                top = window.innerHeight - tooltipRect.height - 10;
            }
            
            // 화면 위쪽 경계 체크
            if (top < 0) {
                top = 10;
            }
            
            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        }

        // 이미지 툴팁 숨기기
        function hideImageTooltip() {
            const tooltip = document.getElementById('imageTooltip');
            if (tooltip) {
                tooltip.remove();
            }
        }

        // 메시지 펼치기/접기 (Legacy - 더 이상 사용되지 않음)
        function toggleMessage(messageId) {
            // 컴팩트 뷰에서는 사용되지 않지만 호환성을 위해 유지
            console.log('toggleMessage called for:', messageId);
        }

        // 읽음 표시 (새로운 컴팩트 레이아웃용)
        async function markAsRead(messageId) {
            try {
                const response = await fetch('mark_message_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        messageId: messageId,
                        studentId: studentId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    // UI 즉시 업데이트
                    const messageItem = document.getElementById(`message-${messageId}`);
                    if (messageItem) {
                        // 읽지 않음 스타일 제거
                        messageItem.classList.remove('unread');
                        
                        // 메시지 텍스트 스타일 업데이트
                        const messageText = messageItem.querySelector('.message-text');
                        if (messageText) {
                            messageText.classList.remove('unread');
                        }
                        
                        // 상태 인디케이터 업데이트
                        const statusIndicator = messageItem.querySelector('.status-indicator');
                        if (statusIndicator) {
                            statusIndicator.classList.remove('unread');
                            statusIndicator.classList.add('read');
                            statusIndicator.title = '읽음';
                            statusIndicator.style.cursor = 'default';
                            statusIndicator.onclick = null;
                        }
                    }
                    
                    // 통합 메시지 목록 새로고침 (읽음 처리 후 해당 메시지 읽은 메시지로 이동)
                    setTimeout(() => loadUnifiedMessages(), 500);
                }
            } catch (error) {
                console.error('Error marking message as read:', error);
            }
        }

        // 시간 포맷
        function formatTime(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) {
                return '방금 전';
            } else if (diff < 3600000) {
                return Math.floor(diff / 60000) + '분 전';
            } else if (diff < 86400000) {
                return Math.floor(diff / 3600000) + '시간 전';
            } else {
                return date.toLocaleDateString('ko-KR') + ' ' + date.toLocaleTimeString('ko-KR', {hour: '2-digit', minute: '2-digit'});
            }
        }

        // 오류 표시
        function showError(message) {
            const messageList = document.getElementById('messageList');
            messageList.innerHTML = `
                <div class="no-messages">
                    <div class="no-messages-icon">⚠️</div>
                    <h3>오류가 발생했습니다</h3>
                    <p>${message}</p>
                </div>
            `;
        }
        
        // 보낸 메시지 표시 (컴팩트 형식)
        function displaySentMessages(requests) {
            const messageList = document.getElementById('messageList');
            
            if (requests.length === 0) {
                messageList.innerHTML = `
                    <div class="no-messages">
                        <div class="no-messages-icon">📭</div>
                        <h3>보낸 메시지가 없습니다</h3>
                        <p>풀이 요청한 메시지가 여기에 표시됩니다.</p>
                    </div>
                `;
                return;
            }

            messageList.innerHTML = requests.map(request => {
                // 이미지 URL 처리
                let imageUrl = '';
                if (request.problemImage) {
                    imageUrl = getProblemImageUrl(request.problemImage);
                }
                
                const statusIcon = getStatusIcon(request.status);
                const isCompleted = request.status === 'sent' || request.status === 'completed' || request.status === 'complete' || request.hasSolution;
                
                return `
                <div class="message-item" id="request-${request.id}">
                    <!-- 문제 이미지 썸네일 (tooltip용, 클릭 시 화이트보드 링크 열기) -->
                    ${imageUrl ? `
                        <img class="problem-thumbnail" 
                             src="${imageUrl}" 
                             alt="문제 이미지"
                             style="cursor: ${isCompleted ? 'pointer' : 'default'};"
                             ${isCompleted ? `onclick="openWhiteboardLink(${request.id})"` : ''}
                             onmouseover="showImageTooltip(event, '${imageUrl}')"
                             onmouseout="hideImageTooltip()"
                             onerror="this.style.display='none'"
                             title="${isCompleted ? '클릭하여 화이트보드 열기' : '답변 대기 중'}">
                    ` : `
                        <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                    `}
                    
                    <!-- 메시지 내용 -->
                    <div class="message-content-compact" style="flex: 1; min-width: 0;">
                        ${request.whiteboardUrl ? `
                            <a href="${request.whiteboardUrl}" 
                               target="_blank"
                               class="message-text" 
                               style="cursor: pointer; text-decoration: none; color: inherit; display: block;"
                               title="클릭하여 화이트보드 열기">
                                ${request.modificationPrompt ? 
                                    truncateText(request.modificationPrompt, 60) : 
                                    request.problemType ? `${request.problemType} 문제 ${request.type === 'askhint' ? '힌트 요청' : '풀이 요청'}` : request.type === 'askhint' ? '힌트 요청' : '풀이 요청'
                                }
                            </a>
                        ` : `
                            <div class="message-text" title="화이트보드 ID가 없습니다">
                            ${request.modificationPrompt ? 
                                truncateText(request.modificationPrompt, 60) : 
                                request.problemType ? `${request.problemType} 문제 ${request.type === 'askhint' ? '힌트 요청' : '풀이 요청'}` : request.type === 'askhint' ? '힌트 요청' : '풀이 요청'
                            }
                        </div>
                        `}
                    </div>
                    
                    <!-- 풀이보기/힌트보기 버튼 (완료된 경우만) -->
                    ${isCompleted ? `
                        <button class="action-btn-compact btn-primary" onclick="handleSolutionView(${request.id})" title="${request.type === 'askhint' ? '힌트보기' : '풀이보기'}">
                            📖 ${request.type === 'askhint' ? '힌트보기' : '풀이보기'}
                        </button>
                    ` : `
                        <button class="action-btn-compact btn-secondary" disabled title="처리 중">
                            ⏳ 처리중
                        </button>
                    `}
                    
                    <!-- 선생님 정보 -->
                    <div class="teacher-info-compact">
                        <div class="teacher-avatar-compact" style="background: ${request.statusColor || '#6b7280'};">
                            ${request.teacherName ? request.teacherName.charAt(0) : statusIcon}
                        </div>
                        <span class="teacher-name-compact">${request.teacherName || request.statusLabel}</span>
                    </div>
                    
                    <!-- 시간 -->
                    <div class="message-time-compact">
                        ${request.timeAgo || formatTimeCompact(request.timecreated)}
                    </div>
                    
                    <!-- 상태 표시 -->
                    <div class="status-indicator ${isCompleted ? 'read' : 'unread'}" 
                         title="${request.statusLabel}">
                    </div>
                </div>
            `;
            }).join('');
        }

        // 상태 아이콘 반환
        function getStatusIcon(status) {
            switch(status) {
                case 'pending': return '⏳';
                case 'processing': return '🔄';
                case 'sent':
                case 'completed': return '✅';
                default: return '📝';
            }
        }

        // 요청 타입 라벨 반환 함수
        function getTypeLabel(type) {
            switch(type) {
                case 'capture':
                    return { text: '캡처', icon: '📷', className: 'type-capture', description: '직접 캡처한 문제' };
                case 'textbook':
                    return { text: '교과서', icon: '📚', className: 'type-textbook', description: '교과서 문제' };
                case 'whiteboard_question':
                    return { text: '화이트보드', icon: '📝', className: 'type-whiteboard', description: '화이트보드 질문' };
                case 'askhint':
                    return { text: '힌트', icon: '💡', className: 'type-hint', description: '힌트 요청' };
                default:
                    return { text: type || '일반', icon: '📄', className: 'type-default', description: type || '일반 요청' };
            }
        }

        // 강의 모달 관련 변수
        let currentInteractionData = null;
        let listeningContainer = null;
        let currentSectionIndex = 0;
        let sectionAudioBuffers = [];
        let sectionAudioSources = [];
        let currentAudioSource = null;
        let audioCtx = null;

        function getAudioContext() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        // 풀이보기 버튼 핸들러 - 화이트보드 팝업 열기 (기존 방식 유지)
        // 전역 함수로 등록하여 버튼에서 접근 가능하도록 함
        window.handleSolutionView = async function(contentsid) {
            console.log('[student_inbox.php] handleSolutionView called with contentsid:', contentsid);

            if (!contentsid || contentsid === 0) {
                console.error('[student_inbox.php] Invalid contentsid:', contentsid);
                alert('유효하지 않은 상호작용 ID입니다.');
                        return;
            }

            // 기존 강의 모달 열기 (화이트보드 팝업)
            console.log('[student_inbox.php] Opening lecture modal with whiteboard');
            if (typeof openLectureModal === 'function') {
            openLectureModal(contentsid);
            } else {
                console.error('[student_inbox.php] openLectureModal function not found');
                alert('모달을 열 수 없습니다. 페이지를 새로고침해주세요.');
        }
        };

        // 강의 모달 열기
        async function openLectureModal(interactionId) {
            console.log('[openLectureModal] 시작, Interaction ID:', interactionId);
            
            const modal = document.getElementById('lectureModal');
            if (!modal) {
                console.error('[openLectureModal] 모달 요소를 찾을 수 없습니다');
                alert('모달을 찾을 수 없습니다.');
                return;
            }
            
            modal.classList.add('active');
            console.log('[openLectureModal] 모달 활성화 완료');
            
            const iframe = document.getElementById('whiteboardFrame');
            if (!iframe) {
                console.error('[openLectureModal] iframe 요소를 찾을 수 없습니다');
                alert('화이트보드 iframe을 찾을 수 없습니다.');
                return;
            }
            
            iframe.src = 'about:blank'; // 초기화
            
            // 자동으로 읽음 처리
            markAsRead(interactionId);
            
            // 데이터 로드
            try {
                const response = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction&studentid=${studentId}`);
                const data = await response.json();
                
                console.log('Loaded data:', data);
                
                if (data.success) {
                    currentInteractionData = data;
                    console.log('[openLectureModal] currentInteractionData 로드 완료:', {
                        hasFaqtext: !!data.faqtext,
                        faqtextLength: data.faqtext ? data.faqtext.length : 0,
                        interactionId: data.interactionData?.id || data.contentsid
                    });
                    
                    // 화이트보드 iframe URL 구성
                    const contentsid = data.contentsid || data.interactionData?.id || interactionId;
                    const contentstype = 2; // student_inbox.php에서는 항상 2
                    const interactionType = data.type || data.interactionData?.type || '';
                    
                    // type=capture인 경우 board_capture.php 사용
                    if (interactionType === 'capture') {
                        console.log('[openLectureModal] type=capture이어서 board_capture.php 사용');
                        // data.wboardid를 사용하거나, 없으면 조회
                        let wboardid = data.wboardid;
                        if (!wboardid) {
                            try {
                                const wbResponse = await fetch(`get_whiteboard_id.php?contentsid=${contentsid}&contentstype=${contentstype}&studentid=${studentId}`);
                                const wbData = await wbResponse.json();
                                if (wbData.success && wbData.wboardid) {
                                    wboardid = wbData.wboardid;
                                } else {
                                    // wboardid가 없으면 interactionId를 기반으로 생성
                                    wboardid = `capture_${interactionId}_${studentId}_${Date.now()}`;
                                }
                            } catch (error) {
                                console.error('[openLectureModal] wboardid 조회 오류:', error);
                                wboardid = `capture_${interactionId}_${studentId}_${Date.now()}`;
                            }
                        }
                        const whiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_capture.php?id=${wboardid}&studentid=${studentId}&interactionid=${interactionId}`;
                        iframe.src = whiteboardUrl;
                        console.log('[openLectureModal] board_capture.php 로드 완료, wboardid:', wboardid, 'URL:', whiteboardUrl);
                        
                        // 단계별 재생 인터페이스 생성
                        console.log('[openLectureModal] 단계별 재생 인터페이스 생성 시작');
                        createFloatingHeadphoneIcon(data);
                        console.log('[openLectureModal] 단계별 재생 인터페이스 생성 완료');
                        
                        // 해설보기 버튼 표시/숨김 처리
                        const solutionBtn = document.getElementById('btn-solution-view');
                        if (solutionBtn) {
                            if (data.solutionText && data.solutionText.trim()) {
                                solutionBtn.style.display = 'flex';
                                solutionBtn.setAttribute('data-solution-text', data.solutionText);
                            } else {
                                solutionBtn.style.display = 'none';
                            }
                        }
                        return;
                    }
                    
                    // contentsid가 없는 경우 board_capture.php 사용
                    if (!contentsid || contentsid === 0 || contentsid === '0') {
                        console.log('[openLectureModal] contentsid가 없어 board_capture.php 사용');
                        // data.wboardid를 사용하거나, 없으면 조회
                        let wboardid = data.wboardid;
                        if (!wboardid) {
                            try {
                                const wbResponse = await fetch(`get_whiteboard_id.php?contentsid=${interactionId}&contentstype=${contentstype}&studentid=${studentId}`);
                                const wbData = await wbResponse.json();
                                if (wbData.success && wbData.wboardid) {
                                    wboardid = wbData.wboardid;
                                } else {
                                    // wboardid가 없으면 interactionId를 기반으로 생성
                                    wboardid = `capture_${interactionId}_${studentId}_${Date.now()}`;
                                }
                            } catch (error) {
                                console.error('[openLectureModal] wboardid 조회 오류:', error);
                                wboardid = `capture_${interactionId}_${studentId}_${Date.now()}`;
                            }
                        }
                        const whiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_capture.php?id=${wboardid}&studentid=${studentId}&interactionid=${interactionId}`;
                        iframe.src = whiteboardUrl;
                        console.log('[openLectureModal] board_capture.php 로드 완료, wboardid:', wboardid, 'URL:', whiteboardUrl);
                        
                        // 단계별 재생 인터페이스 생성
                        console.log('[openLectureModal] 단계별 재생 인터페이스 생성 시작');
                        createFloatingHeadphoneIcon(data);
                        console.log('[openLectureModal] 단계별 재생 인터페이스 생성 완료');
                        
                        // 해설보기 버튼 표시/숨김 처리
                        const solutionBtn = document.getElementById('btn-solution-view');
                        if (solutionBtn) {
                            if (data.solutionText && data.solutionText.trim()) {
                                solutionBtn.style.display = 'flex';
                                solutionBtn.setAttribute('data-solution-text', data.solutionText);
                            } else {
                                solutionBtn.style.display = 'none';
                            }
                        }
                        return;
                    }
                    
                    // get_whiteboard_id.php를 직접 호출하여 abessi_messages에서 wboardid 조회
                    let wboardid = data.wboardid;
                    if (!wboardid) {
                        try {
                            const wbResponse = await fetch(`get_whiteboard_id.php?contentsid=${contentsid}&contentstype=${contentstype}&studentid=${studentId}`);
                            const wbData = await wbResponse.json();
                            if (wbData.success && wbData.wboardid) {
                                wboardid = wbData.wboardid;
                            } else {
                                // 재시도
                                wboardid = await generateWboardId(interactionId, contentsid, contentstype);
                            }
                        } catch (error) {
                            console.error('[openLectureModal] File: student_inbox.php, 화이트보드 ID 조회 오류:', error);
                            console.error('[openLectureModal] Error message:', error.message);
                            console.error('[openLectureModal] Error stack:', error.stack);
                            // 에러 발생 시 사용자에게 알림
                            alert('화이트보드 ID를 조회할 수 없습니다: ' + error.message);
                            iframe.src = 'about:blank';
                            return;
                        }
                    }
                    
                    const whiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=${wboardid}&contentsid=${contentsid}&contentstype=${contentstype}&studentid=${studentId}`;
                    
                    // 콘솔에 상세 로그 출력
                    console.log('[openLectureModal] === 화이트보드 정보 ===');
                    console.log('[openLectureModal] WBoard ID:', wboardid);
                    console.log('[openLectureModal] Contents ID:', contentsid);
                    console.log('[openLectureModal] Content Type:', contentstype);
                    console.log('[openLectureModal] Student ID:', studentId);
                    console.log('[openLectureModal] Full URL:', whiteboardUrl);
                    console.log('[openLectureModal] Data from API:', data);
                    
                    // 디버그 정보가 있으면 콘솔에 출력
                    if (data.debug) {
                        console.log('[openLectureModal] === DB 조회 디버그 정보 ===');
                        console.log('[openLectureModal] 조회 조건:', {
                            contentsid: data.debug.searched_contentsid,
                            contentstype: data.debug.searched_contentstype,
                            userid: data.debug.searched_userid
                        });
                        console.log('[openLectureModal] 찾은 wboardid:', data.debug.found_wboardid);
                        console.log('[openLectureModal] 모든 관련 레코드:', data.debug.all_records);
                    }
                    
                    // iframe에 화이트보드 로드
                    iframe.src = whiteboardUrl;
                    console.log('[openLectureModal] 화이트보드 iframe 로드 완료');
                    
                    // 플로팅 헤드폰 아이콘 생성 (단계별 재생 인터페이스)
                    console.log('[openLectureModal] 단계별 재생 인터페이스 생성 시작');
                    createFloatingHeadphoneIcon(data);
                    console.log('[openLectureModal] 단계별 재생 인터페이스 생성 완료');
                    
                    // 해설보기 버튼 표시/숨김 처리
                    const solutionBtn = document.getElementById('btn-solution-view');
                    if (solutionBtn) {
                        if (data.solutionText && data.solutionText.trim()) {
                            solutionBtn.style.display = 'flex';
                            solutionBtn.setAttribute('data-solution-text', data.solutionText);
                        } else {
                            solutionBtn.style.display = 'none';
                        }
                    }
                    
                } else {
                    console.error('Failed to load interaction data:', data.error);
                    iframe.src = 'about:blank';
                    iframe.style.display = 'flex';
                    iframe.style.alignItems = 'center';
                    iframe.style.justifyContent = 'center';
                    iframe.innerHTML = '<div style="text-align:center; padding:50px;"><p style="color:red;">데이터를 불러오는데 실패했습니다.</p><p>' + (data.error || '오류가 발생했습니다.') + '</p></div>';
                }
            } catch (error) {
                console.error('Error loading interaction:', error);
                iframe.src = 'about:blank';
            }
        }
        
        // wboardid 조회 (abessi_messages에서 실제로 가져오기)
        async function generateWboardId(interactionId, contentsid, contentstype = 2) {
            try {
                // contentsid가 없으면 먼저 조회
                let actualContentsid = contentsid;
                if (!actualContentsid || actualContentsid <= 0) {
                    const dialogueResponse = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction&studentid=${studentId}`);
                    const dialogueData = await dialogueResponse.json();
                    if (dialogueData.success) {
                        actualContentsid = dialogueData.contentsid || dialogueData.interactionData?.id || interactionId;
                    } else {
                        throw new Error('contentsid를 가져올 수 없습니다: ' + (dialogueData.error || '알 수 없음'));
                    }
                }
                
                // get_whiteboard_id.php를 호출하여 abessi_messages에서 wboardid 조회
                const wbUrl = `get_whiteboard_id.php?contentsid=${actualContentsid}&contentstype=${contentstype}&studentid=${studentId}`;
                console.log('[generateWboardId] 조회 URL:', wbUrl);
                
                const wbResponse = await fetch(wbUrl);
                const wbData = await wbResponse.json();
                
                console.log('[generateWboardId] 조회 응답:', wbData);
                
                if (wbData.success && wbData.wboardid) {
                    console.log('[generateWboardId] ✅ DB에서 조회 성공:', wbData.wboardid);
                    return wbData.wboardid;
                } else {
                    // 조회 실패 시 상세 에러 메시지 출력
                    const debugInfo = wbData.debug || {};
                    const errorMsg = `화이트보드 ID 조회 실패: ${wbData.error || '알 수 없음'}\n` +
                        `조회 조건: contentsid=${debugInfo.contentsid}, contentstype=${debugInfo.contentstype}, studentid=${debugInfo.studentid}\n` +
                        `DB에 해당 조건의 레코드가 없습니다.`;
                    
                    console.error('[generateWboardId] ❌', errorMsg);
                    console.error('[generateWboardId] 디버그 정보:', debugInfo);
                    
                    // DB에 있는 레코드 정보 출력
                    if (debugInfo.records_by_contentsid && debugInfo.records_by_contentsid.length > 0) {
                        console.warn('[generateWboardId] 같은 contentsid를 가진 레코드들:', debugInfo.records_by_contentsid);
                    }
                    if (debugInfo.records_by_type_and_user && debugInfo.records_by_type_and_user.length > 0) {
                        console.warn('[generateWboardId] 같은 contentstype과 userid를 가진 레코드들:', debugInfo.records_by_type_and_user);
                    }
                    
                    throw new Error(errorMsg);
                }
            } catch (error) {
                console.error('[generateWboardId] 오류 발생:', error);
                console.error('[generateWboardId] File: student_inbox.php, Line: ' + (error.stack ? error.stack.split('\n')[1] : 'unknown'));
                throw error; // 에러를 다시 throw하여 호출하는 곳에서 처리하도록 함
            }
        }
        
        // 화이트보드 링크 열기 (새 탭) - ktm_teaching_interactions에서 wboardid 직접 읽기
        async function openWhiteboardLink(interactionId) {
            try {
                console.log('[openWhiteboardLink] 호출됨, Interaction ID:', interactionId);
                console.log('[openWhiteboardLink] File: student_inbox.php, Line: 3095');
                
                // ktm_teaching_interactions 테이블에서 직접 wboardid 조회
                const response = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction&studentid=${studentId}`);
                const data = await response.json();
                
                console.log('[openWhiteboardLink] 응답 데이터:', data);
                
                if (!data.success) {
                    console.error('[openWhiteboardLink] Failed to load interaction data:', data.error);
                    alert('화이트보드를 불러올 수 없습니다: ' + (data.error || '알 수 없는 오류'));
                    return;
                }
                
                // ktm_teaching_interactions에 저장된 wboardid 직접 사용
                const wboardid = data.wboardid;
                
                if (!wboardid) {
                    const errorMsg = '화이트보드 ID가 저장되어 있지 않습니다.';
                    console.error('[openWhiteboardLink] ❌', errorMsg);
                    console.error('[openWhiteboardLink] Interaction ID:', interactionId);
                    console.error('[openWhiteboardLink] Response data:', data);
                    alert(errorMsg);
                    return;
                }
                
                console.log('[openWhiteboardLink] ✅ ktm_teaching_interactions에서 wboardid 조회 성공:', wboardid);
                
                // type=capture인 경우 board_capture.php 사용
                const interactionType = data.type || data.interactionData?.type || '';
                let whiteboardUrl;
                
                if (interactionType === 'capture') {
                    whiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_capture.php?id=${wboardid}&studentid=${studentId}&interactionid=${interactionId}`;
                    console.log('[openWhiteboardLink] type=capture이어서 board_capture.php 사용');
                } else {
                    whiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=${wboardid}`;
                }
                
                console.log('[openWhiteboardLink] 화이트보드 URL:', whiteboardUrl);
                
                // 새 탭에서 화이트보드 열기
                window.open(whiteboardUrl, '_blank');
            } catch (error) {
                console.error('[openWhiteboardLink] File: student_inbox.php, Error loading whiteboard:', error);
                console.error('[openWhiteboardLink] Error message:', error.message);
                console.error('[openWhiteboardLink] Error stack:', error.stack);
                alert('화이트보드를 불러오는 중 오류가 발생했습니다: ' + error.message);
            }
        }
        
        // 플로팅 헤드폰 아이콘 생성
        function createFloatingHeadphoneIcon(data) {
            console.log('[createFloatingHeadphoneIcon] 시작, 데이터:', data);
            
            // 해설보기 버튼 표시/숨김 처리
            const solutionBtn = document.getElementById('btn-solution-view');
            if (solutionBtn) {
                if (data.solutionText && data.solutionText.trim()) {
                    solutionBtn.style.display = 'flex';
                    solutionBtn.setAttribute('data-solution-text', data.solutionText);
                } else {
                    solutionBtn.style.display = 'none';
                }
            }
            
            // 기존 컨트롤 제거
            const existingContainer = document.getElementById('audioControlsContainer');
            if (existingContainer) {
                existingContainer.innerHTML = '';
            }
            
            // 섹션 텍스트 준비
            const narrationText = data.solutionText || data.narrationText || '';
            const sections = narrationText ? narrationText.split('@').filter(s => s.trim()) : [];
            
            // 텍스트 표시 영역 HTML 생성 (초기에는 모두 숨김)
            let textDisplayHtml = '';
            sections.forEach((text, idx) => {
                const num = idx + 1;
                // 초기에는 active 클래스 없이 생성 (모두 숨김)
                const displayText = text.trim();
                textDisplayHtml += `<div class="listening-text-display" id="listeningText${num}">${displayText.replace(/\n/g, '<br>')}</div>`;
            });
            
            // 모달 헤더의 컨트롤 컨테이너에 추가
            const controlsContainer = document.getElementById('audioControlsContainer');
            if (!controlsContainer) {
                console.error('[createFloatingHeadphoneIcon] audioControlsContainer를 찾을 수 없습니다');
                return;
            }
            
            // 텍스트 표시 영역은 사용하지 않음 (화이트보드만 표시)
            // 필요시 나중에 추가 가능
            
            // 헤더에 재생 컨트롤 추가
            controlsContainer.innerHTML = `
                <button id="autoPlayToggle" class="auto-play-toggle" data-mode="step" type="button" onclick="event.stopPropagation(); toggleAutoPlay();" title="구간 자동 이어듣기" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                            <span id="autoPlayIcon">auto</span>
                        </button>
                <button class="nav-arrow" id="prevSectionBtn" title="이전 구간" onclick="event.stopPropagation(); playPreviousSection();" disabled style="color: white; font-size: 14px;">◀</button>
                <div class="listening-progress-dots" id="progressDots" style="display: flex; align-items: center; gap: 6px;">
                        <!-- Progress dots will be inserted here -->
                    </div>
                <button class="nav-arrow" id="nextSectionBtn" title="다음 구간" onclick="event.stopPropagation(); playNextSection();" style="color: white; font-size: 14px;">▶</button>
                <button class="speed-control-btn" id="speedControlBtn" onclick="event.stopPropagation(); cyclePlaybackSpeed();" title="재생 속도 조절" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">1.0x</button>
            `;
            
            listeningContainer = controlsContainer;
            console.log('[createFloatingHeadphoneIcon] 헤더에 컨트롤 추가 완료');
            
            // audio_url에서 섹션 정보 확인
            let audioSections = [];
            let audioSectionUrls = [];
            
            console.log('[createFloatingHeadphoneIcon] audioUrl 확인:', data.audioUrl);
            console.log('[createFloatingHeadphoneIcon] narrationText 확인:', data.narrationText);
            console.log('[createFloatingHeadphoneIcon] solutionText 확인:', data.solutionText);
            
            if (data.audioUrl) {
                try {
                    // JSON 형식인지 확인
                    const audioData = JSON.parse(data.audioUrl);
                    
                    // 배열 형식인지 확인 (openai_tts_pmemory.php 방식)
                    if (Array.isArray(audioData)) {
                        audioSectionUrls = audioData;
                        console.log('[createFloatingHeadphoneIcon] ✅ audio_url에서 배열 형식 섹션 정보 로드:', audioSectionUrls.length, '개');
                        console.log('[createFloatingHeadphoneIcon] 섹션 URL들:', audioSectionUrls);
                    }
                    // 객체 형식인지 확인 (기존 방식)
                    else if (audioData.mode === 'listening_test' && audioData.sections) {
                        audioSectionUrls = audioData.sections;
                        audioSections = audioData.text_sections || [];
                        console.log('[createFloatingHeadphoneIcon] ✅ audio_url에서 객체 형식 섹션 정보 로드:', audioSectionUrls.length, '개');
                        console.log('[createFloatingHeadphoneIcon] 섹션 URL들:', audioSectionUrls);
                    } else {
                        console.log('[createFloatingHeadphoneIcon] audio_url이 JSON이지만 알 수 없는 형식');
                    }
                } catch (e) {
                    // JSON이 아니면 단일 URL로 처리
                    console.log('[createFloatingHeadphoneIcon] audio_url이 단일 URL 형식:', data.audioUrl);
                }
            } else {
                console.log('[createFloatingHeadphoneIcon] audioUrl이 없음');
            }
            
            // 나레이션 텍스트가 있으면 @ 기호로 분리하여 준비 (narrationText 우선 사용)
            if (data.narrationText || data.solutionText) {
                // narrationText를 우선 사용 (단계별 TTS 대본이 @ 기호로 분할되어 있음)
                const narrationText = data.narrationText || data.solutionText;
                console.log('[createFloatingHeadphoneIcon] 나레이션 텍스트로 섹션 준비, 길이:', narrationText.length);
                console.log('[createFloatingHeadphoneIcon] 나레이션 텍스트 샘플:', narrationText.substring(0, 200));
                prepareNarrationSections(narrationText, audioSectionUrls, audioSections);
                
                // 첫 번째 섹션으로 명시적으로 초기화 (마지막 단계로 이동하는 문제 방지)
                setTimeout(() => {
                    currentSectionIndex = 0;
                    // 모든 진행 점 초기화
                    document.querySelectorAll('.progress-dot').forEach((dot, i) => {
                        dot.classList.remove('active', 'completed');
                        if (i === 0) {
                            dot.classList.add('active');
                        }
                    });
                    // 모든 텍스트 표시 영역 초기화 (모두 숨김)
                    document.querySelectorAll('.listening-text-display').forEach((display, i) => {
                        display.classList.remove('active');
                    });
                    
                    // 이전/다음 버튼 상태 초기화
                    const prevBtn = document.getElementById('prevSectionBtn');
                    const nextBtn = document.getElementById('nextSectionBtn');
                    if (prevBtn) prevBtn.disabled = true;
                    if (nextBtn && window.audioSections) {
                        nextBtn.disabled = (window.audioSections.length <= 1);
                    }
                    console.log('[createFloatingHeadphoneIcon] 첫 번째 섹션으로 명시적 초기화 완료');
                    
                    // 첫 번째 섹션 자동 재생 (오디오 파일이 있는 경우) - 5초 후 시작
                    if (window.audioSectionUrls && window.audioSectionUrls.length > 0 && window.audioSectionUrls[0]) {
                        console.log('[createFloatingHeadphoneIcon] 첫 번째 섹션 자동 재생 시작 (5초 후)');
                        setTimeout(() => {
                            playSection(0);
                        }, 5000); // 5초 후 재생 시작
                    }
                }, 100);
            } else if (audioSectionUrls.length > 0) {
                // 텍스트가 없어도 음성 파일이 있으면 재생 가능하도록 준비
                console.log('[createFloatingHeadphoneIcon] 음성 파일만으로 섹션 준비');
                prepareNarrationSections('', audioSectionUrls, audioSections);
            } else {
                console.warn('[createFloatingHeadphoneIcon] ⚠️ 나레이션 텍스트와 음성 파일이 모두 없습니다');
            }
        }
        
        // 나레이션 섹션 준비 (@ 기호로 분리 또는 audio_url에서 섹션 정보 사용)
        function prepareNarrationSections(text, audioSectionUrls = [], audioSections = []) {
            console.log('[prepareNarrationSections] 시작');
            console.log('[prepareNarrationSections] 텍스트 길이:', text ? text.length : 0);
            console.log('[prepareNarrationSections] audioSectionUrls 개수:', audioSectionUrls.length);
            console.log('[prepareNarrationSections] audioSections 개수:', audioSections.length);
            
            let sections = [];
            
            // 텍스트가 있으면 @ 기호로 분리
            if (text) {
                sections = text.split('@').filter(s => s.trim());
                console.log('[prepareNarrationSections] 텍스트에서 @ 기호로 분리:', sections.length, '개 섹션');
            } else if (audioSections.length > 0) {
                // 텍스트가 없으면 audio_url의 text_sections 사용
                sections = audioSections;
                console.log('[prepareNarrationSections] audio_url의 text_sections 사용:', sections.length, '개 섹션');
            } else if (audioSectionUrls.length > 0) {
                // 텍스트도 없으면 URL 개수만큼 빈 섹션 생성
                sections = new Array(audioSectionUrls.length).fill('');
                console.log('[prepareNarrationSections] URL 개수만큼 빈 섹션 생성:', sections.length, '개');
            }
            
            const dotsContainer = document.getElementById('progressDots');
            if (!dotsContainer) {
                console.error('[prepareNarrationSections] progressDots 요소를 찾을 수 없습니다');
                return;
            }
            
            // 진행 점만 추가 (화살표 버튼은 이미 HTML에 있음)
            const prevBtn = document.getElementById('prevSectionBtn');
            const nextBtn = document.getElementById('nextSectionBtn');
            
            // 기존 진행 점 제거
            dotsContainer.querySelectorAll('.progress-dot').forEach(dot => dot.remove());
            
            // 진행 점 생성 (첫 번째 섹션만 active로 시작)
            sections.forEach((section, index) => {
                // 첫 번째 섹션만 active 클래스 추가
                const dot = document.createElement('div');
                dot.className = 'progress-dot'; // 기본 클래스만 추가
                if (index === 0) {
                    dot.classList.add('active'); // 첫 번째만 active
                }
                dot.setAttribute('data-section', index);
                dot.setAttribute('title', `구간 ${index + 1}`);
                dot.onclick = (e) => {
                    e.stopPropagation();
                    playSection(index);
                };
                
                // dotsContainer에 직접 추가
                    dotsContainer.appendChild(dot);
            });
            
            console.log('[prepareNarrationSections] 진행 점 생성 완료, 첫 번째 섹션 active 설정');
            
            // 이전/다음 버튼 상태 업데이트
            if (prevBtn) prevBtn.disabled = (sections.length === 0 || currentSectionIndex === 0);
            if (nextBtn) nextBtn.disabled = (sections.length === 0 || currentSectionIndex >= sections.length - 1);
            
            console.log('[prepareNarrationSections] 진행 점 생성 완료:', sections.length, '개');
            
            // 텍스트 표시 영역 업데이트 (전체 텍스트 표시, 초기에는 모두 숨김)
            sections.forEach((section, index) => {
                const textDisplay = document.getElementById(`listeningText${index + 1}`);
                if (textDisplay) {
                    // 전체 텍스트 표시 (mynote.php 방식)
                    textDisplay.innerHTML = section.trim().replace(/\n/g, '<br>');
                    // 초기에는 모두 숨김 (active 클래스 제거)
                        textDisplay.classList.remove('active');
                }
            });
            
            // 전역 변수에 저장
            window.audioSectionUrls = audioSectionUrls;
            window.audioSections = sections;
            sectionAudioBuffers = [];
            currentSectionIndex = 0; // 첫 번째 섹션으로 초기화
            
            console.log('[prepareNarrationSections] 완료');
            console.log('[prepareNarrationSections] 섹션 수:', sections.length);
            console.log('[prepareNarrationSections] 오디오 URL 수:', audioSectionUrls.length);
            
            // 모든 진행 점 초기화 (첫 번째만 active)
            document.querySelectorAll('.progress-dot').forEach((dot, i) => {
                dot.classList.remove('active', 'completed');
                if (i === 0) {
                    dot.classList.add('active');
                }
            });
            
            // 모든 텍스트 표시 영역 초기화 (모두 숨김)
            document.querySelectorAll('.listening-text-display').forEach((display, i) => {
                display.classList.remove('active');
            });
            
            // 이전/다음 버튼 상태 초기화
            if (prevBtn) prevBtn.disabled = true; // 첫 번째이므로 이전 버튼 비활성화
            if (nextBtn) nextBtn.disabled = (sections.length <= 1); // 섹션이 1개 이하면 다음 버튼 비활성화
            
            console.log('[prepareNarrationSections] 첫 번째 섹션으로 초기화 완료');
            
            // 첫 번째 섹션 자동 재생 (오디오 파일이 있는 경우) - 5초 후 시작
            if (audioSectionUrls.length > 0 && audioSectionUrls[0]) {
                console.log('[prepareNarrationSections] 첫 번째 섹션 자동 재생 시작 (5초 후)');
                setTimeout(() => {
                    playSection(0);
                }, 5000); // 5초 후 재생 시작
            } else if (sections.length > 0 && sections[0]) {
                // 오디오 파일이 없어도 첫 번째 섹션 텍스트는 표시
                console.log('[prepareNarrationSections] 오디오 파일 없음, 첫 번째 섹션 텍스트만 표시');
            }
        }
        
        // 섹션 재생
        async function playSection(index) {
            if (!currentInteractionData) return;
            
            const sections = window.audioSections || [];
            if (index >= sections.length && (!window.audioSectionUrls || index >= window.audioSectionUrls.length)) {
                console.warn('[playSection] 인덱스 범위 초과:', index);
                return;
            }
            
            currentSectionIndex = index;
            
            // 첫 번째 단계(index === 0)에서는 텍스트 표시 안 함
            if (index === 0) {
                // 모든 텍스트 표시 영역 숨김
                document.querySelectorAll('.listening-text-display').forEach((display) => {
                    display.classList.remove('active');
                });
            } else {
                // 현재 섹션의 텍스트 표시
            document.querySelectorAll('.listening-text-display').forEach((display, i) => {
                display.classList.remove('active');
                if (i === index) {
                    display.classList.add('active');
                }
            });
            }
            
            // 진행 표시 업데이트
            document.querySelectorAll('.progress-dot').forEach((dot, i) => {
                dot.classList.remove('active', 'completed');
                if (i === index) dot.classList.add('active');
                if (i < index) dot.classList.add('completed');
            });
            
            // 이전/다음 버튼 상태 업데이트
            const prevBtn = document.getElementById('prevSectionBtn');
            const nextBtn = document.getElementById('nextSectionBtn');
            if (prevBtn) prevBtn.disabled = (index === 0);
            if (nextBtn) nextBtn.disabled = (index >= sections.length - 1);
            
            // audio_url에서 섹션 URL이 있으면 직접 재생
            if (window.audioSectionUrls && window.audioSectionUrls.length > 0 && window.audioSectionUrls[index]) {
                // 음성 파일 직접 재생
                try {
                    const audioUrl = window.audioSectionUrls[index];
                    console.log('[playSection] 음성 파일 재생:', audioUrl);
                    
                    // 기존 오디오 정지
                    if (window.currentSectionAudio) {
                        window.currentSectionAudio.pause();
                        window.currentSectionAudio = null;
                    }
                    
                    const audio = new Audio(audioUrl);
                    audio.playbackRate = currentPlaybackSpeed;
                    window.currentSectionAudio = audio;
                    
                    audio.onended = () => {
                        // 재생 완료
                        document.querySelectorAll('.progress-dot')[index].classList.remove('active');
                        document.querySelectorAll('.progress-dot')[index].classList.add('completed');
                        
                        // 자동 재생 모드이면 다음 섹션 재생
                        const autoToggle = document.getElementById('autoPlayToggle');
                        if (autoToggle && autoToggle.getAttribute('data-mode') === 'continuous') {
                            if (index < sections.length - 1) {
                                setTimeout(() => playSection(index + 1), 500);
                            }
                        }
                    };
                    
                    audio.onerror = (e) => {
                        console.error('[playSection] 음성 파일 재생 오류:', e);
                        alert('음성 파일을 재생할 수 없습니다.');
                    };
                    
                    await audio.play();
                    return;
                } catch (e) {
                    console.error('[playSection] 음성 재생 실패:', e);
                    // 실패 시 TTS 생성으로 폴백
                }
            }
            
            // audio_url이 없거나 실패한 경우 TTS 생성 (narrationText 우선 사용)
            const text = currentInteractionData.narrationText || currentInteractionData.solutionText || '';
            const textSections = text.split('@').filter(s => s.trim());
            
            if (index >= textSections.length) return;
            
            const sectionText = textSections[index].trim();
            
            // TTS 생성 및 재생
            try {
                const buffer = await generateSpeech(sectionText, "alloy");
                playAudioBuffer(buffer, () => {
                    // 재생 완료
                    document.querySelectorAll('.progress-dot')[index].classList.remove('active');
                    document.querySelectorAll('.progress-dot')[index].classList.add('completed');
                    
                    // 자동 재생 모드이면 다음 섹션 재생
                    const autoToggle = document.getElementById('autoPlayToggle');
                    if (autoToggle && autoToggle.getAttribute('data-mode') === 'continuous') {
                        if (index < textSections.length - 1) {
                            setTimeout(() => playSection(index + 1), 500);
                        }
                    }
                });
            } catch (e) {
                console.error('[playSection] TTS generation failed:', e);
            }
        }
        
        function playCurrentSection() {
            playSection(currentSectionIndex);
        }
        
        function playNextSection() {
            const sections = window.audioSections || [];
            const maxIndex = Math.max(sections.length, window.audioSectionUrls ? window.audioSectionUrls.length : 0) - 1;
            if (currentSectionIndex < maxIndex) {
                playSection(currentSectionIndex + 1);
            }
        }
        
        function playPreviousSection() {
            if (currentSectionIndex > 0) {
                playSection(currentSectionIndex - 1);
            }
        }
        
        // 재생 속도 순환 함수 (mynote.php 방식: 1.0x → 1.25x → 1.5x → 1.75x → 2.0x → 1.0x)
        let currentPlaybackSpeed = 1.0;
        let currentSpeedIndex = 0;
        const speedOptions = [1.0, 1.25, 1.5, 1.75, 2.0];
        
        function cyclePlaybackSpeed() {
            const speedBtn = document.getElementById('speedControlBtn');
            if (!speedBtn) {
                console.error('[cyclePlaybackSpeed] speedControlBtn 요소를 찾을 수 없습니다');
                return;
            }
            
            // 다음 속도로 전환
            currentSpeedIndex = (currentSpeedIndex + 1) % speedOptions.length;
            currentPlaybackSpeed = speedOptions[currentSpeedIndex];
            
            // 버튼 텍스트 업데이트
            speedBtn.textContent = currentPlaybackSpeed.toFixed(2) + 'x';
            
            // 현재 재생 중인 오디오에 속도 적용
            if (window.currentSectionAudio) {
                window.currentSectionAudio.playbackRate = currentPlaybackSpeed;
            }
            if (currentAudioSource) {
                currentAudioSource.playbackRate.value = currentPlaybackSpeed;
            }
            
            console.log('[cyclePlaybackSpeed] 재생 속도 변경:', currentPlaybackSpeed + 'x');
        }
        
        // 자동 재생 토글 함수
        function toggleAutoPlay() {
            const autoToggle = document.getElementById('autoPlayToggle');
            if (!autoToggle) {
                console.error('[toggleAutoPlay] autoPlayToggle 요소를 찾을 수 없습니다');
                return;
            }
            
            const currentMode = autoToggle.getAttribute('data-mode');
            const newMode = currentMode === 'step' ? 'continuous' : 'step';
            
            autoToggle.setAttribute('data-mode', newMode);
            autoToggle.textContent = newMode === 'continuous' ? 'auto' : 'auto';
            
            console.log('[toggleAutoPlay] 자동 재생 모드 변경:', newMode);
        }
        
        // 섹션 상세보기 함수
        function openSectionDetail() {
            if (!currentInteractionData) return;
            
            const sections = window.audioSections || [];
            if (sections.length === 0) {
                const text = currentInteractionData.solutionText || currentInteractionData.narrationText || '';
                const textSections = text.split('@').filter(s => s.trim());
                if (textSections.length > 0 && textSections[currentSectionIndex]) {
                    // 새 탭에서 텍스트 표시
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(`
                        <html>
                            <head>
                                <title>구간 ${currentSectionIndex + 1} 상세보기</title>
                                <style>
                                    body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
                                    h2 { color: #667eea; }
                                </style>
                            </head>
                            <body>
                                <h2>구간 ${currentSectionIndex + 1}</h2>
                                <p>${textSections[currentSectionIndex].replace(/\n/g, '<br>')}</p>
                            </body>
                        </html>
                    `);
                }
            } else if (sections[currentSectionIndex]) {
                // 새 탭에서 텍스트 표시
                const newWindow = window.open('', '_blank');
                newWindow.document.write(`
                    <html>
                        <head>
                            <title>구간 ${currentSectionIndex + 1} 상세보기</title>
                            <style>
                                body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
                                h2 { color: #667eea; }
                            </style>
                        </head>
                        <body>
                            <h2>구간 ${currentSectionIndex + 1}</h2>
                            <p>${sections[currentSectionIndex].replace(/\n/g, '<br>')}</p>
                        </body>
                    </html>
                `);
            }
        }
        
        const generateSpeech = async (text, voice) => {
            if (!apikey) {
                throw new Error("API Key not found");
            }
            const fetchOptions = {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${apikey}`
                },
                body: JSON.stringify({
                    model: "tts-1",
                    voice: voice,
                    input: text
                }),
            };

            const response = await fetch("https://api.openai.com/v1/audio/speech", fetchOptions);
            if (!response.ok) throw new Error("음성 생성 실패");
            const audioData = await response.arrayBuffer();
            const ctx = getAudioContext();
            return await ctx.decodeAudioData(audioData);
        };
        
        const playAudioBuffer = (buffer, onEnded) => {
            const ctx = getAudioContext();
            if (currentAudioSource) {
                try { currentAudioSource.stop(); } catch(e) {}
            }
            
            currentAudioSource = ctx.createBufferSource();
            currentAudioSource.buffer = buffer;
            currentAudioSource.playbackRate.value = currentPlaybackSpeed;
            currentAudioSource.connect(ctx.destination);
            if (onEnded) {
                currentAudioSource.onended = onEnded;
            }
            currentAudioSource.start();
        };

        // 모달 닫기
        // 해설보기 함수 (모달 헤더에서 호출) - 우측 슬라이더 패널로 표시
        function openSolutionViewFromModal() {
            const solutionBtn = document.getElementById('btn-solution-view');
            if (!solutionBtn) {
                console.error('[openSolutionViewFromModal] 해설보기 버튼을 찾을 수 없습니다');
                return;
            }
            
            const solutionText = solutionBtn.getAttribute('data-solution-text');
            if (!solutionText || solutionText.trim() === '') {
                Swal.fire({
                    title: '해설이 없습니다',
                    text: '등록된 해설이 없습니다.',
                    icon: 'info',
                    confirmButtonText: '확인'
                });
                return;
            }
            
            // 해설 패널 표시
            const solutionPanel = document.getElementById('solutionPanel');
            const solutionPanelContent = document.getElementById('solutionPanelContent');
            
            if (!solutionPanel || !solutionPanelContent) {
                console.error('[openSolutionViewFromModal] 해설 패널 요소를 찾을 수 없습니다');
                return;
            }
            
            // solution_text를 HTML로 렌더링 (줄바꿈 처리)
            // 수식 렌더링을 위해 MathJax 형식 유지
            let formattedText = solutionText.replace(/\n/g, '<br>').replace(/\r/g, '');
            
            // LaTeX 수식 블록 처리 (\[ ... \] 형식)
            formattedText = formattedText.replace(/\\\[/g, '\\[').replace(/\\\]/g, '\\]');
            
            // 해설 내용 설정 (MathJax 처리 클래스 추가)
            solutionPanelContent.innerHTML = '<div class="tex2jax_process" style="text-align:left; padding:20px; font-size:16px; line-height:1.8; color:#333;">' + formattedText + '</div>';
            
            // 패널 표시 (슬라이드 인 애니메이션)
            solutionPanel.classList.add('show');
            
            // MathJax로 수식 렌더링
            if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                setTimeout(() => {
                    MathJax.typesetPromise([solutionPanelContent]).catch((err) => {
                        console.error('[openSolutionViewFromModal] MathJax 렌더링 오류:', err);
                    });
                }, 100);
            }
        }
        
        // 해설 패널 닫기 함수
        function closeSolutionPanel() {
            const solutionPanel = document.getElementById('solutionPanel');
            if (solutionPanel) {
                solutionPanel.classList.remove('show');
            }
        }
        
        function closeLectureModal() {
            const modal = document.getElementById('lectureModal');
            modal.classList.remove('active');
            
            // 오디오 정지
            if (currentAudioSource) {
                try {
                    currentAudioSource.stop();
                } catch(e) {}
                currentAudioSource = null;
            }
            
            // 헤더의 재생 컨트롤 제거
            const controlsContainer = document.getElementById('audioControlsContainer');
            if (controlsContainer) {
                controlsContainer.innerHTML = '';
            }
            
            // 모달 body의 텍스트 표시 영역 제거
            const modalBody = document.querySelector('#lectureModal .modal-body');
            if (modalBody) {
                const textContainer = document.getElementById('listeningTextContainer');
                if (textContainer) textContainer.remove();
            }
            
            // 질문 패널 닫기
            closeQuestionPanel();
            
            // 해설 패널 닫기
            closeSolutionPanel();
            
            // 초기화
            currentInteractionData = null;
            sectionAudioBuffers = [];
            currentSectionIndex = 0;
        }

        // 대화 파싱
        function parseDialogue(text) {
            if (!text) return;
            
            const solutionContent = document.getElementById('solutionContent');
            solutionContent.innerHTML = '';
            dialogueLines = [];
            
            // 해설 내용을 섹션별로 파싱
            const sections = [];
            let currentSection = '';
            
            // 텍스트를 줄바꿈으로 분리
            const lines = text.split('\n');
            
            lines.forEach(line => {
                const trimmedLine = line.trim();
                if (!trimmedLine) return;
                
                // 섹션 헤더 감지 ([문제 분석], [풀이 과정] 등)
                if (trimmedLine.match(/^\[.+\]$/)) {
                    if (currentSection) {
                        sections.push(currentSection);
                    }
                    currentSection = trimmedLine + '\n';
                } else {
                    currentSection += trimmedLine + '\n';
                }
            });
            
            if (currentSection) {
                sections.push(currentSection);
            }
            
            // 섹션별로 처리
            sections.forEach(section => {
                const lines = section.split('\n').filter(line => line.trim());
                
                lines.forEach(line => {
                    const lineDiv = document.createElement('div');
                    lineDiv.className = 'solution-line';
                    lineDiv.setAttribute('data-index', dialogueLines.length);
                    
                    // 섹션 헤더 스타일
                    if (line.match(/^\[.+\]$/)) {
                        lineDiv.innerHTML = `<h3>${line.replace(/[\[\]]/g, '')}</h3>`;
                    }
                    // 선생님/학생 대화 처리
                    else if (line.includes('선생님:') || line.includes('학생:')) {
                        const isTeacher = line.includes('선생님:');
                        lineDiv.className += isTeacher ? ' teacher' : ' student';
                        const speaker = isTeacher ? '선생님:' : '학생:';
                        const content = line.replace(speaker, '').trim();
                        lineDiv.innerHTML = `
                            <div class="speaker-label">${speaker}</div>
                            <div>${content}</div>
                        `;
                    }
                    // 일반 내용
                    else {
                        // 수식 태그 변환 (LaTeX 형식 유지)
                        let formattedLine = line;
                        // 리스트 항목 처리
                        if (formattedLine.match(/^[-*]\s/)) {
                            formattedLine = '• ' + formattedLine.substring(2);
                        }
                        lineDiv.innerHTML = formattedLine;
                    }
                    
                    solutionContent.appendChild(lineDiv);
                    dialogueLines.push({
                        element: lineDiv,
                        text: line,
                        duration: line.replace(/<[^>]*>/g, '').length * 0.05
                    });
                });
            });
        }
        
        // 대화 라인 생성 헬퍼 함수
        function createDialogueLine(line, container) {
            const isTeacher = line.includes('선생님:');
            const isStudent = line.includes('학생:');
            
            const lineDiv = document.createElement('div');
            lineDiv.className = `solution-line ${isTeacher ? 'teacher' : isStudent ? 'student' : ''}`;
            lineDiv.setAttribute('data-index', dialogueLines.length);
            
            if (isTeacher || isStudent) {
                const speaker = isTeacher ? '선생님:' : '학생:';
                const content = line.replace(speaker, '').trim();
                
                lineDiv.innerHTML = `
                    <div class="speaker-label">${speaker}</div>
                    <div>${content}</div>
                `;
            } else {
                // HTML 태그를 유지하면서 표시
                lineDiv.innerHTML = line;
            }
            
            container.appendChild(lineDiv);
            dialogueLines.push({
                element: lineDiv,
                text: line,
                duration: line.replace(/<[^>]*>/g, '').length * 0.05 // HTML 태그 제외한 글자 수로 계산
            });
        }

        
        // 풀이 요청 폼 표시/숨기기
        function showRequestForm() {
            const section = document.getElementById('requestSection');
            section.style.display = 'block';
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // AI답변 생성 버튼 클릭 처리
        function openAIAnswer() {
            // 디버깅 정보
            console.log('AI답변 생성 버튼 클릭됨');
            console.log('User Role:', '<?php echo $role; ?>');
            console.log('Is Student:', <?php echo ($role === 'student') ? 'true' : 'false'; ?>);
            
            // teachingagent.php 링크 생성
            const userid = <?php echo $USER->id; ?>;
            const studentid = <?php echo $studentid; ?>; // 현재 사용자를 학생으로 설정
            
            const teachingAgentUrl = `https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/teachingagent.php?userid=${userid}&studentid=${studentid}`;
            
            console.log('Opening URL:', teachingAgentUrl);
            
            // 새 탭에서 열기
            window.open(teachingAgentUrl, '_blank');
        }
        
        function hideRequestForm() {
            const section = document.getElementById('requestSection');
            section.style.display = 'none';
            clearForm();
        }
        
        // 폼 초기화
        function clearForm() {
            document.getElementById('requestForm').reset();
            const preview = document.getElementById('imagePreview');
            const uploadArea = document.getElementById('uploadArea');
            preview.style.display = 'none';
            preview.src = '';
            uploadArea.querySelector('.upload-icon').style.display = 'block';
            uploadArea.querySelector('.upload-text').style.display = 'block';
        }
        
        // 드래그 앤 드롭 처리
        function handleDrop(event) {
            event.preventDefault();
            const uploadArea = event.currentTarget;
            uploadArea.style.backgroundColor = '#f8fafc';
            uploadArea.style.borderColor = '#e2e8f0';
            
            const files = event.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                const fileInput = document.getElementById('questionImage');
                fileInput.files = files;
                handleImageSelect(files[0]);
            }
        }
        
        // 이미지 선택 처리
        function handleImageSelect(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                const uploadArea = document.getElementById('uploadArea');
                
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadArea.querySelector('.upload-icon').style.display = 'none';
                uploadArea.querySelector('.upload-text').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
        
        // 이미지 미리보기
        document.getElementById('questionImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleImageSelect(file);
            }
        });
        
        // 풀이 요청 제출
        async function submitRequest(event) {
            console.log('submitRequest called');
            event.preventDefault();
            
            const fileInput = document.getElementById('questionImage');
            const problemType = document.getElementById('problemType').value;
            const additionalRequest = document.getElementById('additionalRequest').value;
            
            console.log('File input:', fileInput);
            console.log('Files:', fileInput.files);
            
            if (!fileInput.files[0]) {
                alert('문제 이미지를 업로드해주세요.');
                return;
            }
            
            // 제출 버튼 비활성화
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '🔄 전송 중...';
            
            try {
                const file = fileInput.files[0];
                const reader = new FileReader();
                
                reader.onload = async function(e) {
                    try {
                        console.log('Image loaded, sending to server...');
                        console.log('studentId:', studentId);
                        console.log('teacherId:', teacherId);
                        
                        // 메인 API 호출 (save_interaction.php)
                        const response = await fetch('save_interaction.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'create_interaction',
                                studentId: studentId,
                                teacherId: teacherId || 0, // 특정 선생님 지정 가능
                                problemType: problemType,
                                problemImage: e.target.result,
                                problemText: '',
                                modificationPrompt: additionalRequest
                            })
                        });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // 성공 메시지
                        alert('✅ 풀이요청이 전송되었습니다!\n선생님이 확인 후 답변해 드릴 예정입니다.');
                        
                        // 폼 초기화
                        hideRequestForm();
                        
                        // 통합 메시지 목록 새로고침
                        setTimeout(loadUnifiedMessages, 1000);
                        
                    } else {
                        throw new Error(data.error || '저장 실패');
                    }
                    } catch (innerError) {
                        console.error('Error in reader.onload:', innerError);
                        alert('요청 처리 중 오류가 발생했습니다: ' + innerError.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '🚀 풀이 요청 전송';
                    }
                };
                
                reader.onerror = function() {
                    console.error('File reading failed');
                    alert('이미지 파일을 읽는 중 오류가 발생했습니다.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '🚀 풀이 요청 전송';
                };
                
                reader.readAsDataURL(file);
                
            } catch (error) {
                console.error('Error in submitRequest:', error);
                alert('요청 전송 중 오류가 발생했습니다: ' + error.message);
                // finally 블록이 제대로 작동하지 않을 경우를 대비
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '🚀 풀이 요청 전송';
                }
            }
        }
        
        // 재요청 모달 관련 변수
        let currentReRequestInteractionId = null;
        
        // 재요청 모달 표시
        function showReRequestModal(interactionId) {
            currentReRequestInteractionId = interactionId;
            document.getElementById('reRequestModal').classList.add('active');
            document.getElementById('reRequestReason').value = '';
            document.getElementById('reRequestReason').focus();
        }
        
        // 재요청 모달 닫기
        function closeReRequestModal() {
            document.getElementById('reRequestModal').classList.remove('active');
            currentReRequestInteractionId = null;
        }
        
        // 재요청 제출
        async function submitReRequest() {
            const reason = document.getElementById('reRequestReason').value.trim();
            
            if (!reason) {
                alert('재요청 사유를 입력해주세요.');
                return;
            }
            
            if (!currentReRequestInteractionId) {
                alert('오류가 발생했습니다. 다시 시도해주세요.');
                return;
            }
            
            try {
                // 재요청 API 호출
                const response = await fetch('submit_re_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        interactionId: currentReRequestInteractionId,
                        reason: reason,
                        studentId: studentId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ 재요청이 전송되었습니다!\n선생님이 확인 후 보충 설명을 드릴 예정입니다.');
                    closeReRequestModal();
                    
                    // 통합 메시지 목록 새로고침
                    setTimeout(loadUnifiedMessages, 1000);
                } else {
                    alert('재요청 전송에 실패했습니다: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('Error submitting re-request:', error);
                alert('재요청 전송 중 오류가 발생했습니다.');
            }
        }

        // 다른 풀이 요청 함수 (기존 풀이 복사)
        async function requestAlternativeSolution(requestId) {
            console.log('[requestAlternativeSolution] Called with requestId:', requestId);

            // 확인 다이얼로그 표시
            const confirmed = confirm('다른 풀이를 요청하시겠습니까?\n\n같은 문제에 대해 다른 방식의 풀이를 요청합니다.');
            if (!confirmed) {
                console.log('[requestAlternativeSolution] User cancelled');
                return;
            }

            try {
                console.log('[requestAlternativeSolution] Copying interaction...');

                // copy_interaction 액션을 사용하여 새로운 요청 생성
                const copyResponse = await fetch('save_interaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'copy_interaction',
                        sourceInteractionId: requestId,
                        studentId: studentId,
                        teacherId: 0,  // 선생님은 자동 배정되도록
                        newStatus: 'pending',  // 교사 페이지에 표시되도록 pending 상태로 설정
                        newModificationPrompt: '[다른 풀이 요청] 이전 풀이와 다른 방식으로 설명해주세요'
                    })
                });

                const copyData = await copyResponse.json();
                console.log('[requestAlternativeSolution] Copy response:', copyData);

                if (copyData.success) {
                    alert('✅ 다른 풀이 요청이 전송되었습니다!\n\n선생님이 확인 후 다른 방식으로 답변해 드릴 예정입니다.\n\n새로운 요청 ID: ' + copyData.interactionId);

                    // 메시지 목록 새로고침
                    setTimeout(loadUnifiedMessages, 1000);
                } else {
                    throw new Error(copyData.error || '요청 전송에 실패했습니다. [student_inbox.php:requestAlternativeSolution]');
                }

            } catch (error) {
                console.error('[requestAlternativeSolution] Error:', error);
                alert('⚠️ 다른 풀이 요청 중 오류가 발생했습니다.\n\n' + error.message + '\n\n문제가 계속되면 관리자에게 문의하세요.');
            }
        }

        // Audio Context 관리
        function getAudioContext() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        // --- Feature: Step-by-Step Question Generation ---

        async function initStepQuestions() {
            if (!currentInteractionData) {
                console.error('[initStepQuestions] currentInteractionData가 없습니다');
                return;
            }
            
            // faqtext 확인 (먼저 확인)
            // 여러 경로로 faqtext 확인
            const faqtext = currentInteractionData.faqtext || 
                           currentInteractionData.faqText || 
                           (currentInteractionData.interactionData && currentInteractionData.interactionData.faqtext) || 
                           '';
            
            console.log('[initStepQuestions] FAQ 확인:', {
                hasFaqtext: !!faqtext,
                faqtextLength: faqtext ? faqtext.length : 0,
                interactionId: currentInteractionData.interactionData?.id || currentInteractionData.contentsid,
                currentInteractionDataKeys: Object.keys(currentInteractionData || {}),
                faqtextValue: faqtext ? faqtext.substring(0, 100) : 'empty'
            });
            
            // 우측 질문 패널 열기 (슬라이드 애니메이션)
            const panel = document.getElementById('questionPanel');
            panel.style.display = 'flex';
            setTimeout(() => {
                panel.classList.add('show');
            }, 10);
            
            const content = document.getElementById('questionPanelContent');
            
            // DB에서 직접 FAQ 확인 (currentInteractionData에 없을 수 있음)
            const interactionId = currentInteractionData.interactionData?.id || currentInteractionData.contentsid || 0;
            let savedFaqtext = faqtext;
            
            // currentInteractionData에 faqtext가 없으면 DB에서 직접 조회
            if (!savedFaqtext || savedFaqtext.trim() === '') {
                console.log('[initStepQuestions] currentInteractionData에 FAQ 없음, DB에서 직접 조회 시도');
                try {
                    const faqResponse = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction&studentid=${studentId}`);
                    const faqData = await faqResponse.json();
                    if (faqData.success && faqData.faqtext && faqData.faqtext.trim() !== '') {
                        savedFaqtext = faqData.faqtext;
                        // currentInteractionData 업데이트
                        currentInteractionData.faqtext = savedFaqtext;
                        console.log('[initStepQuestions] DB에서 FAQ 로드 완료, 길이:', savedFaqtext.length);
                    }
                } catch (error) {
                    console.error('[initStepQuestions] DB에서 FAQ 조회 오류:', error);
                }
            }
            
            if (savedFaqtext && savedFaqtext.trim() !== '') {
                // 이미 생성된 FAQ가 있으면 표시 (로딩 없이)
                console.log('[initStepQuestions] 기존 FAQ 로드');
                content.innerHTML = `
                    <div id="questions-container"></div>
                `;
                await displayFAQContent(savedFaqtext);
            } else {
                // FAQ가 없으면 생성 (로딩 메시지 표시)
                console.log('[initStepQuestions] FAQ 생성 시작 (DB에 저장된 FAQ 없음)');
                content.innerHTML = `
                    <div id="questions-loading" style="text-align:center; padding:20px;">
                        <div class="loading-spinner" style="margin: 0 auto 10px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
                        <p>단계별 질문을 생성하고 있습니다...</p>
                    </div>
                    <div id="questions-container"></div>
                    <style>
                        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                    </style>
                `;
                const text = currentInteractionData.solutionText || '';
                await generateQuestionsLogic(text);
            }
        }
        
        function openQuestionPanel() {
            const panel = document.getElementById('questionPanel');
            panel.style.display = 'flex';
            setTimeout(() => {
                panel.classList.add('show');
            }, 10);
        }
        
        // FAQ 재생성 함수
        async function regenerateFAQ() {
            if (!currentInteractionData) {
                console.error('[regenerateFAQ] currentInteractionData가 없습니다');
                alert('데이터를 불러올 수 없습니다.');
                return;
            }
            
            // 확인 대화상자
            if (!confirm('FAQ를 다시 생성하시겠습니까? 기존 내용이 교체됩니다.')) {
                return;
            }
            
            const content = document.getElementById('questionPanelContent');
            const regenerateBtn = document.getElementById('btn-regenerate-faq');
            
            // 버튼 비활성화 및 로딩 표시
            if (regenerateBtn) {
                regenerateBtn.disabled = true;
                regenerateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            // 로딩 메시지 표시
            content.innerHTML = `
                <div id="questions-loading" style="text-align:center; padding:20px;">
                    <div class="loading-spinner" style="margin: 0 auto 10px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
                    <p>FAQ를 다시 생성하고 있습니다...</p>
                </div>
                <div id="questions-container"></div>
                <style>
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                </style>
            `;
            
            try {
                // 기존 FAQ 무시하고 새로 생성
            const text = currentInteractionData.solutionText || '';
                await generateQuestionsLogic(text);
                
                // 재생성 완료 후 currentInteractionData의 faqtext 초기화 (새로 생성된 것으로 업데이트됨)
                console.log('[regenerateFAQ] FAQ 재생성 완료');
            } catch (error) {
                console.error('[regenerateFAQ] 재생성 오류:', error);
                content.innerHTML = `<div style="color:red; text-align:center; padding:20px;">FAQ 재생성 중 오류가 발생했습니다: ${error.message}</div>`;
            } finally {
                // 버튼 복원
                if (regenerateBtn) {
                    regenerateBtn.disabled = false;
                    regenerateBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                }
            }
        }

        async function displayFAQContent(faqtext) {
            try {
                console.log('[displayFAQContent] FAQ 표시 시작, 길이:', faqtext.length);
                const faqData = JSON.parse(faqtext);
                const container = document.getElementById('questions-container');
                if (!container) {
                    console.error('[displayFAQContent] questions-container를 찾을 수 없습니다');
                    return;
                }
                
                if (faqData.qa_pairs && Array.isArray(faqData.qa_pairs)) {
                    console.log('[displayFAQContent] FAQ 질문 개수:', faqData.qa_pairs.length);
                    await displayQuestions(faqData.qa_pairs, container);
                } else {
                    console.error('[displayFAQContent] FAQ 데이터 형식 오류:', faqData);
                    container.innerHTML = `<div style="color:red; text-align:center;">FAQ 데이터 형식이 올바르지 않습니다.</div>`;
                }
            } catch (error) {
                console.error('[displayFAQContent] FAQ 파싱 오류:', error);
                console.error('[displayFAQContent] FAQ 텍스트 샘플:', faqtext.substring(0, 200));
                const container = document.getElementById('questions-container');
                if (container) {
                    container.innerHTML = `<div style="color:red; text-align:center;">FAQ 표시 오류: ${error.message}</div>`;
                }
            }
        }
        
        async function displayQuestions(qaPairs, container) {
            const questionsToShow = qaPairs.slice(0, 3);
                    
                    // 질문별 화이트보드 ID를 비동기로 조회
                    const questionPromises = questionsToShow.map(async (qa, index) => {
                        const contentsid = currentInteractionData.interactionData?.id || currentInteractionData.contentsid || 0;
                const contentstype = 2;
                const questionNumber = index + 1;
                        
                        // DB에서 화이트보드 ID 조회
                        let questionWboardId = null;
                        try {
                            const wbResponse = await fetch(`get_whiteboard_id.php?contentsid=${contentsid}&contentstype=${contentstype}&studentid=${studentId}&questionNumber=${questionNumber}`);
                            const wbData = await wbResponse.json();
                            
                            if (wbData.success) {
                                questionWboardId = wbData.wboardid;
                            }
                        } catch (error) {
                    console.error(`[displayQuestions] 질문 ${questionNumber} 화이트보드 ID 조회 오류:`, error);
                        }
                        
                        // 화이트보드 ID가 있을 때만 iframe 추가
                        let whiteboardHtml = '';
                        if (questionWboardId) {
                            const stepquizUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_stepquiz.php?id=${questionWboardId}&cid=${contentsid}&ctype=${contentstype}&userid=${studentId}&nstep=${questionNumber}`;
                            whiteboardHtml = `
                                <div class="question-embed-whiteboard">
                                    <iframe src="${stepquizUrl}" frameborder="0"></iframe>
                                </div>
                            `;
                        }
                        
                        const card = document.createElement('div');
                        card.className = 'question-card';
                        card.innerHTML = `
                            <div class="question-header" onclick="toggleQuestion(this)">
                                <div style="display:flex; align-items:center;">
                                    <span class="question-icon">Q${questionNumber}</span>
                                    <span>${qa.question}</span>
                                </div>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div class="question-answer">
                                ${qa.answer}
                                ${whiteboardHtml}
                            </div>
                        `;
                        container.appendChild(card);
                    });
                    
                    await Promise.all(questionPromises);
            
            // FAQ 섹션 아래에 전체 화이트보드 추가
            const contentsid = currentInteractionData.interactionData?.id || currentInteractionData.contentsid || 0;
            const contentstype = 2;
            
            // drillingmath.php 방식으로 wboardid 생성
            const faqWboardId = `contentstype${contentstype}_stepquiz_${contentsid}_step0_userid${studentId}`;
            const faqWhiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_stepquiz.php?id=${faqWboardId}&cid=${contentsid}&ctype=${contentstype}&userid=${studentId}&nstep=0`;
            
            const whiteboardSection = document.createElement('div');
            whiteboardSection.className = 'faq-whiteboard-section';
            whiteboardSection.innerHTML = `
                <div class="faq-whiteboard-iframe-container">
                    <iframe 
                        class="faq-whiteboard-iframe" 
                        id="faq-whiteboard-iframe"
                        src="${faqWhiteboardUrl}" 
                        title="FAQ 화이트보드"
                        allow="camera; microphone; fullscreen">
                    </iframe>
                </div>
            `;
            container.appendChild(whiteboardSection);
                    
                    if (window.MathJax) {
                        window.MathJax.typesetPromise([container]);
                    }
        }
        
        function closeQuestionPanel() {
            const panel = document.getElementById('questionPanel');
            if (panel) {
                panel.classList.remove('show');
                setTimeout(() => {
                    panel.style.display = 'none';
                }, 300);
            }
        }

        async function generateQuestionsLogic(nodeContent) {
            try {
                const response = await fetch('../../books/generate_questions_with_answers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nodeContent: nodeContent,
                        nodeType: 'step',
                        fullContext: currentInteractionData.problemText || '',
                        contentsid: currentInteractionData.interactionData?.id || 0,
                        contentstype: 2,
                        nstep: 1,
                        totalSteps: 1,
                        nodeIndex: 0
                    })
                });

                const data = await response.json();
                const container = document.getElementById('questions-container');
                const loading = document.getElementById('questions-loading');
                if (loading) loading.style.display = 'none';

                if (data.success && data.qa_pairs) {
                    // FAQ 데이터를 JSON으로 저장
                    const faqData = {
                        qa_pairs: data.qa_pairs,
                        generated_at: new Date().toISOString()
                    };
                    const faqtextJson = JSON.stringify(faqData);
                    
                    // DB에 FAQ 저장
                    const interactionId = currentInteractionData.interactionData?.id || currentInteractionData.contentsid || 0;
                    console.log('[generateQuestionsLogic] FAQ 저장 시도:', {
                        interactionId: interactionId,
                        faqtextLength: faqtextJson.length,
                        faqtextPreview: faqtextJson.substring(0, 200)
                    });
                    
                    if (interactionId) {
                        try {
                            const savePayload = {
                                action: 'update_faq',
                                interactionId: interactionId,
                                faqtext: faqtextJson
                            };
                            
                            console.log('[generateQuestionsLogic] 저장 요청 전송:', savePayload);
                            
                            const saveResponse = await fetch('save_interaction.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(savePayload)
                            });
                            
                            console.log('[generateQuestionsLogic] 저장 응답 상태:', saveResponse.status, saveResponse.statusText);
                            
                            if (!saveResponse.ok) {
                                const errorText = await saveResponse.text();
                                console.error('[generateQuestionsLogic] HTTP 오류 응답:', errorText);
                                throw new Error(`HTTP ${saveResponse.status}: ${errorText}`);
                            }
                            
                            const saveResult = await saveResponse.json();
                            console.log('[generateQuestionsLogic] 저장 응답 데이터:', saveResult);
                            
                            if (saveResult.success) {
                                console.log('[generateQuestionsLogic] FAQ 저장 완료, ID:', interactionId);
                                console.log('[generateQuestionsLogic] 저장된 FAQ 길이:', saveResult.saved_length || faqtextJson.length);
                                // currentInteractionData 업데이트
                                if (currentInteractionData) {
                                    currentInteractionData.faqtext = faqtextJson;
                                    console.log('[generateQuestionsLogic] currentInteractionData.faqtext 업데이트 완료');
                                }
                            } else {
                                console.error('[generateQuestionsLogic] FAQ 저장 실패:', saveResult.error);
                                console.error('[generateQuestionsLogic] 저장 시도한 데이터:', {
                                    interactionId: interactionId,
                                    faqtextLength: faqtextJson.length,
                                    error: saveResult.error
                                });
                                alert('FAQ 저장에 실패했습니다: ' + (saveResult.error || '알 수 없는 오류'));
                            }
                        } catch (error) {
                            console.error('[generateQuestionsLogic] FAQ 저장 오류:', error);
                            console.error('[generateQuestionsLogic] 에러 상세:', {
                                message: error.message,
                                stack: error.stack,
                                interactionId: interactionId
                            });
                            alert('FAQ 저장 중 오류가 발생했습니다: ' + error.message);
                        }
                    } else {
                        console.error('[generateQuestionsLogic] interactionId가 없어서 저장할 수 없습니다:', {
                            interactionData: currentInteractionData.interactionData,
                            contentsid: currentInteractionData.contentsid
                        });
                    }
                    
                    // 최대 3개의 질문만 표시
                    const questionsToShow = data.qa_pairs.slice(0, 3);
                    
                    // 질문 표시
                    await displayQuestions(questionsToShow, container);
                } else {
                    container.innerHTML = `<div style="color:red; text-align:center;">질문 생성 실패: ${data.error || '알 수 없는 오류'}</div>`;
                }
            } catch (error) {
                console.error(error);
                const loading = document.getElementById('questions-loading');
                if (loading) loading.style.display = 'none';
                
                const container = document.getElementById('questions-container');
                if (container) container.innerHTML = `<div style="color:red; text-align:center;">오류 발생: ${error.message}</div>`;
            }
        }

        function toggleQuestion(header) {
            const card = header.parentElement;
            card.classList.toggle('active');
        }

    </script>

    <!-- Step-by-Step TTS Player Modal Component -->
    <?php
    require_once(__DIR__ . '/components/step_player_modal.php');
    ?>

    <!-- Step-by-Step TTS Player Script -->
    <script src="/moodle/local/augmented_teacher/alt42/teachingsupport/js/step_player.js"></script>
</body>
</html>

