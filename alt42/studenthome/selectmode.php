<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();
$studentid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;

// 데이터베이스에서 사용자 역할 가져오기
$userrole = $DB->get_record_sql(
    "SELECT data FROM {user_info_data} WHERE userid = :userid AND fieldid = :fieldid",
    array('userid' => $USER->id, 'fieldid' => '22')
);
$role = $userrole->data;

// Fetch user's MBTI using parameterized query
$mymbti = $DB->get_record_sql(
    "SELECT * FROM {abessi_mbtilog} WHERE userid = :userid AND type = :type ORDER BY id DESC LIMIT 1",
    array('userid' => $studentid, 'type' => 'present')
);
$userMbti = $mymbti ? $mymbti->mbti : null;

// 모드 저장 처리 (POST 요청 핸들러)
if (isset($_POST['action']) && $_POST['action'] == 'save_modes') {
    header('Content-Type: application/json');
    
    try {
        $teacher_mode = $_POST['teacher_mode'];
        $student_mode = $_POST['student_mode']; 
        $teacher_id = $USER->id;
        $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : $studentid;
        
        // 디버그 정보
        error_log("Saving modes - Teacher: $teacher_id, Student: $student_id, T-Mode: $teacher_mode, S-Mode: $student_mode");
        
        // 기존 설정 확인 - Moodle의 get_record 사용
        $existing = $DB->get_record('persona_modes', 
            array('teacher_id' => $teacher_id, 'student_id' => $student_id));
        
        if ($existing) {
            // 업데이트 - Moodle의 update_record 사용
            $update = new stdClass();
            $update->id = $existing->id;
            $update->teacher_mode = $teacher_mode;
            $update->student_mode = $student_mode;
            $update->timecreated = time();
            
            $DB->update_record('persona_modes', $update);
            error_log("Updated existing record ID: " . $existing->id);
        } else {
            // 새로 삽입 - Moodle의 insert_record 사용
            $insert = new stdClass();
            $insert->teacher_id = $teacher_id;
            $insert->student_id = $student_id;
            $insert->teacher_mode = $teacher_mode;
            $insert->student_mode = $student_mode;
            $insert->timecreated = time();
            
            $newid = $DB->insert_record('persona_modes', $insert);
            error_log("Inserted new record ID: " . $newid);
        }
        
        echo json_encode(['success' => true, 'message' => '모드가 성공적으로 저장되었습니다.']);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '데이터베이스 쓰기 오류: ' . $e->getMessage()]);
    }
    exit;
}

// 기존 모드 설정 가져오기
$existing_modes = null;
if ($role !== 'student' && $studentid) {
    try {
        // Moodle의 get_record 사용 (테이블명에서 mdl_ 제거)
        $existing_modes = $DB->get_record('persona_modes', 
            array('teacher_id' => $USER->id, 'student_id' => $studentid));
    } catch (Exception $e) {
        error_log("Error getting existing modes: " . $e->getMessage());
        $existing_modes = null;
    }
} 
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 페르소나 매칭 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            padding-left: 100px; /* Space for sidebar */
        }

        /* Left Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 80px;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            gap: 20px;
            z-index: 1000;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            background: rgba(255,255,255,0.1);
            width: 60px;
            height: 60px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            text-decoration: none;
            border: none;
        }

        .sidebar-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
            color: white;
        }

        .sidebar-btn.active {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            color: white;
        }

        .sidebar-btn span:first-child {
            font-size: 24px;
        }
        
        .sidebar-btn span:last-child {
            font-size: 11px;
        }
        
        .sidebar-divider {
            width: 40px;
            height: 1px;
            background: rgba(255,255,255,0.2);
            margin: 10px 0;
        }
        
        /* View Toggle Button - Simplified */
        .view-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .scroll-toggle-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 25px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-size: 14px;
        }
        
        .scroll-toggle-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        
        .scroll-toggle-btn.scroll-active {
            background: rgba(96, 165, 250, 0.3);
            border-color: #60a5fa;
        }

        /* Notification Toast - Remove this style as it's no longer needed */

        /* Removed old view-btn styles */
        .view-btn {
            padding: 8px 16px;
            background: transparent;
            border: none;
            color: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-btn.active {
            background: rgba(255,255,255,0.2);
        }

        .view-btn:hover {
            background: rgba(255,255,255,0.15);
        }

        /* Title Section */
        .main-title {
            text-align: center;
            font-size: clamp(28px, 5vw, 48px);
            margin-bottom: 15px;
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: pulse 2s ease-in-out infinite;
        }

        /* Status Message Area */
        .status-message {
            text-align: center;
            min-height: 30px;
            margin-bottom: 30px;
            font-size: 16px;
            color: #60a5fa;
            font-weight: 500;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .status-message.show {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .subtitle {
            text-align: center;
            font-size: 20px;
            color: #9ca3af;
            margin-bottom: 40px;
        }

        /* Tab Navigation (Tab View) */
        .tab-navigation {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .tab-navigation.hidden {
            display: none;
        }

        .tab-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 25px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            border-color: transparent;
        }

        .tab-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .tab-btn.active:hover {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
        }

        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .content-section.active {
            display: block;
        }

        /* Scroll View Container */
        .scroll-view-container {
            display: none;
        }

        .scroll-view-container.active {
            display: block;
        }

        .section-divider {
            margin: 60px 0;
            text-align: center;
            position: relative;
        }

        .section-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.3), transparent);
        }

        .section-divider h2 {
            display: inline-block;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 10px 30px;
            position: relative;
            font-size: 24px;
            color: #60a5fa;
        }

        /* Mode Cards Grid */
        .modes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
            margin-top: 40px;
        }

        .mode-card {
            border-radius: 20px;
            padding: 30px 20px 80px 20px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }

        .mode-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        }

        .mode-card.selected {
            border: 3px solid #22c55e !important;
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.4) !important;
        }

        .mode-card.selected::after {
            content: '✓';
            position: absolute;
            top: 15px;
            right: 15px;
            background: #22c55e;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .approach-label {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.3);
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Mode Card Colors */
        .mode-card.curriculum { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .mode-card.exam { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .mode-card.custom { background: linear-gradient(135deg, #10b981, #059669); }
        .mode-card.mission { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .mode-card.reflection { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .mode-card.selfled { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .mode-card.cognitive { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .mode-card.timecentered { background: linear-gradient(135deg, #ec4899, #db2777); }
        .mode-card.curiositycentered { background: linear-gradient(135deg, #84cc16, #65a30d); }

        .mode-icon {
            font-size: 72px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        .mode-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .mode-target {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.4;
        }

        /* Buttons */
        .detail-button {
            position: absolute;
            bottom: 15px;
            right: 15px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .detail-button:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .agent-button {
            position: absolute;
            bottom: 15px;
            left: 15px;
            padding: 8px 14px;
            background: rgba(76, 175, 80, 0.85);
            border: 1px solid rgba(76, 175, 80, 0.4);
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .agent-button:hover {
            background: rgba(76, 175, 80, 0.9);
            transform: scale(1.05);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-button.primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .action-button.secondary {
            background: #4b5563;
            color: white;
        }

        .action-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* AI Button */
        .ai-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 0 auto;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.4);
        }

        .ai-button:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 30px rgba(168, 85, 247, 0.6);
        }

        /* Save Button */
        .save-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px auto;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
        }

        .save-button:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 30px rgba(16, 185, 129, 0.6);
        }

        .save-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .button-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }

        /* Info Boxes */
        .info-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box.warning {
            background: rgba(251, 191, 36, 0.1);
            border-color: rgba(251, 191, 36, 0.3);
        }

        .info-box.success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .info-box h3 {
            font-size: 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .execution-list {
            list-style: none;
            padding-left: 0;
            margin-top: 15px;
        }

        .execution-list li {
            margin-bottom: 12px;
            padding-left: 30px;
            position: relative;
            line-height: 1.5;
        }

        .execution-list li::before {
            content: counter(item) ".";
            counter-increment: item;
            position: absolute;
            left: 0;
            font-weight: bold;
            color: #22c55e;
            font-size: 18px;
        }

        .execution-list {
            counter-reset: item;
        }

        /* Period Selection Buttons */
        .period-btn {
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            margin: 5px;
        }
        
        .period-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .period-btn.active {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            border-color: transparent;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.4);
        }
        
        .period-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
        }
        
        .period-content {
            animation: fadeIn 0.3s ease-out;
            margin-top: 20px;
        }
        
        .period-content h3 {
            color: #60a5fa;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .period-content .execution-points {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
        }

        /* Iframe Modal */
        .iframe-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .iframe-modal.show {
            display: flex;
            opacity: 1;
        }

        .iframe-container {
            position: relative;
            width: 90%;
            height: 85%;
            max-width: 1200px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transform: translateY(100vh);
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .iframe-modal.show .iframe-container {
            transform: translateY(0);
        }

        .iframe-header {
            background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .iframe-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .iframe-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .iframe-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .iframe-body {
            width: 100%;
            height: calc(100% - 70px);
            position: relative;
            background: #f8f9fa;
        }

        .iframe-content {
            width: 100%;
            height: 100%;
            border: none;
        }

        .iframe-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 48px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* MBTI Explanation Card */
        .mbti-explanation-card {
            position: fixed;
            top: 50%;
            right: -35%;
            transform: translateY(-50%);
            width: 33%;
            max-width: 450px;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: -10px 0 40px rgba(0, 0, 0, 0.5);
            z-index: 1001;
            transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 4px solid;
            border-image: linear-gradient(to bottom, #60a5fa, #a78bfa) 1;
        }

        .mbti-explanation-card.show {
            right: 20px;
        }

        .mbti-explanation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .mbti-explanation-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mbti-badge-large {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            border-radius: 20px;
            font-size: 18px;
            font-weight: bold;
            color: white;
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
        }

        .mbti-type-name {
            font-size: 20px;
            color: #60a5fa;
            font-weight: 600;
        }

        .mbti-explanation-close {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .mbti-explanation-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .mbti-explanation-content {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.8;
            font-size: 15px;
        }

        .mbti-mode-match {
            background: rgba(96, 165, 250, 0.1);
            border-left: 3px solid #60a5fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
        }

        .mbti-mode-match h4 {
            color: #60a5fa;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .mbti-fun-fact {
            background: rgba(167, 139, 250, 0.1);
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
        }

        .mbti-fun-fact-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        /* MBTI Highlighting */
        .mbti-badge {
            display: inline-block;
            padding: 3px 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mbti-badge:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .mbti-badge.highlighted {
            background: rgba(255, 235, 59, 0.8) !important;
            border: 2px solid rgba(255, 215, 0, 1) !important;
            color: #333 !important;
            font-weight: 700 !important;
            box-shadow: 0 0 10px rgba(255, 235, 59, 0.5);
            animation: pulse 2s infinite;
        }

        .mbti-badge.highlighted:hover {
            background: rgba(255, 235, 59, 0.9) !important;
            transform: scale(1.1);
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 10px rgba(255, 235, 59, 0.5); }
            50% { box-shadow: 0 0 20px rgba(255, 235, 59, 0.8); }
            100% { box-shadow: 0 0 10px rgba(255, 235, 59, 0.5); }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInRight {
            from { right: -35%; opacity: 0; }
            to { right: 20px; opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .modes-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .mbti-explanation-card {
                width: 45%;
                max-width: none;
            }
            
            .mbti-explanation-card.show {
                right: 15px;
            }
        }

        @media (max-width: 768px) {
            /* Adjust sidebar for mobile */
            .sidebar {
                width: 60px;
                z-index: 1000;
            }
            
            .sidebar-btn {
                padding: 10px 5px;
            }
            
            .sidebar-btn span:first-child {
                font-size: 20px;
            }
            
            .sidebar-btn span:last-child {
                font-size: 9px;
            }
            
            .container {
                padding-left: 70px;
            }
            
            .modes-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .mode-card {
                aspect-ratio: auto;
                padding: 25px 15px 70px 15px;
            }
            
            .mode-icon {
                font-size: 56px;
            }
            
            .mode-title {
                font-size: 24px;
            }
            
            .view-toggle {
                top: 10px;
                right: 10px;
            }
            
            .scroll-toggle-btn {
                width: 40px;
                height: 40px;
                padding: 8px;
            }
            
            .tab-navigation {
                flex-direction: column;
                gap: 10px;
            }
            
            .tab-btn {
                width: 100%;
            }
            
            .mbti-explanation-card {
                width: 90%;
                max-width: none;
                right: -100%;
                padding: 20px;
            }
            
            .mbti-explanation-card.show {
                right: 5%;
                left: 5%;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <button class="sidebar-btn" onclick="window.location.href='/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo $studentid; ?>'">
                <span>🏠</span>
                <span>홈</span>
            </button>
            
            <div class="sidebar-divider"></div>
            
            <?php if ($role !== 'student'): ?>
            <button class="sidebar-btn" id="teacherBtn" onclick="switchToMode('teacher')">
                <span>👨‍🏫</span>
                <span>선생님</span>
            </button>
            <?php endif; ?>
            
            <button class="sidebar-btn active" id="studentBtn" onclick="switchToMode('student')">
                <span>👨‍🎓</span>
                <span>학생</span>
            </button>
        </div>
        
        <!-- View Toggle (Top Right) -->
        <div class="view-toggle">
            <button class="scroll-toggle-btn" id="scrollToggle" onclick="toggleScrollView()">
                <span id="viewIcon">📜</span>
                <span id="viewText">스크롤 뷰</span>
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">

        <!-- Title -->
        <h1 class="main-title">AI 페르소나 매칭 시스템</h1>
        
        <!-- Status Message Area -->
        <div class="status-message" id="statusMessage"></div>

        <!-- Tab View -->
        <div id="tabView">
            <!-- Teacher Mode Content -->
            <div id="teacherContent" class="content-section active">
                <div class="modes-grid" id="teacherGrid">
                    <!-- Teacher mode cards will be generated by JavaScript -->
                </div>
            </div>

            <!-- Student Mode Content -->
            <div id="studentContent" class="content-section">
                <div class="modes-grid" id="studentGrid">
                    <!-- Student mode cards will be generated by JavaScript -->
                </div>
                
                <div class="button-container" style="display: flex; justify-content: center; margin-top: 40px;">
                    <button class="ai-button" onclick="goToMBTITest()" style="margin: 0 auto;">
                        <span>🧩</span>
                        <span>MBTI 검사/업데이트</span>
                        <span>→</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Scroll View -->
        <div id="scrollView" class="scroll-view-container">
            <?php if ($role !== 'student'): ?>
            <!-- Teacher Section -->
            <div class="section-divider">
                <h2>👨‍🏫 선생님</h2>
            </div>
            <div class="modes-grid" id="teacherGridScroll">
                <!-- Teacher mode cards for scroll view -->
            </div>
            <?php endif; ?>

            <!-- Student Section -->
            <div class="section-divider">
                <h2>👨‍🎓 학생</h2>
            </div>
            <div class="modes-grid" id="studentGridScroll">
                <!-- Student mode cards for scroll view -->
            </div>

            <div class="button-container" style="display: flex; justify-content: center; margin-top: 40px;">
                <button class="ai-button" onclick="goToMBTITest()" style="margin: 0 auto;">
                    <span>🧩</span>
                    <span>MBTI 검사/업데이트</span>
                    <span>→</span>
                </button>
            </div>
        </div>
        </div> <!-- End of main-content -->
    </div> <!-- End of container -->

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <div id="modalContent"></div>
        </div>
    </div>

    <!-- Iframe Modal -->
    <div id="iframeModal" class="iframe-modal">
        <div class="iframe-container">
            <div class="iframe-header">
                <div class="iframe-title" id="iframeTitle">
                    <span id="iframeTitleIcon">📚</span>
                    <span id="iframeTitleText">Loading...</span>
                </div>
                <button class="iframe-close" onclick="closeIframeModal()">&times;</button>
            </div>
            <div class="iframe-body">
                <div class="iframe-loading" id="iframeLoading">⏳</div>
                <iframe id="iframeContent" class="iframe-content" style="display: none;"></iframe>
            </div>
        </div>
    </div>

    <!-- MBTI Explanation Card -->
    <div id="mbtiExplanationCard" class="mbti-explanation-card">
        <div class="mbti-explanation-header">
            <div class="mbti-explanation-title">
                <span class="mbti-badge-large" id="mbtiTypeBadge">INTJ</span>
                <span class="mbti-type-name" id="mbtiTypeName">전략가형</span>
            </div>
            <button class="mbti-explanation-close" onclick="closeMBTIExplanation()">&times;</button>
        </div>
        <div class="mbti-explanation-content" id="mbtiExplanationContent">
            <!-- Content will be dynamically filled -->
        </div>
    </div>

    <script>
        // Pass PHP variables to JavaScript
        const userRole = '<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>';
        const studentId = <?php echo json_encode($studentid); ?>;
        const existingModes = <?php echo json_encode($existing_modes); ?>;
        const userMbti = <?php echo json_encode($userMbti); ?>;
        
        // Mode to PHP file URL mapping
        const modeToUrlMap = {
            curriculum: 'contextual_agents/intro_modes/curriculumcentered.php',
            exam: 'contextual_agents/intro_modes/examcentered.php',
            custom: 'contextual_agents/intro_modes/adaptationcentered.php',
            mission: 'contextual_agents/intro_modes/missioncentered.php',
            reflection: 'contextual_agents/intro_modes/reflectioncentered.php',
            selfled: 'contextual_agents/intro_modes/selfdriven.php',
            cognitive: 'contextual_agents/intro_modes/apprentice.php',
            timecentered: 'contextual_agents/intro_modes/timecentered.php',
            curiositycentered: 'contextual_agents/intro_modes/curiositycentered.php'
        };
        
        // 모든 모드 데이터를 학생 모드와 동일하게 통일
        const studentModes = {
            curriculum: {
                title: '커리큘럼 중심모드',
                icon: '📚',
                target: '상위권, 목표 대학 있는 유형',
                description: '고강도 선행과 개념 완성 루트 설계',
                mathking: '학습 로드맵 자동생성, 진도율 분석',
                management: '진도이탈 탐지 → 일정 리마인드, 선행과 복습 균형 관리',
                heavyMessage: '너는 이제 대학이라는 목표를 향해 달리는 마라토너다. 중간에 멈추면 그 자리가 네 무덤이 된다.',
                mbti: ['ISTJ', 'ESTJ', 'INTJ'],
                executionPoints: [
                    '매일 정해진 시간에 학습 시작 - 예외는 없다',
                    '주간 진도 체크를 통한 자기 검증 필수',
                    '선행과 복습의 황금비율 7:3 유지',
                    '월 1회 전체 커리큘럼 점검 및 수정',
                    '번아웃 징조 발견 시 즉시 페이스 조절'
                ]
            },
            custom: {
                title: '맞춤학습 중심모드',
                icon: '🎯',
                target: '기초 부족, 스스로 학습이 익숙하지 않은 학생',
                description: '개별 수준 맞춤 문제 배치와 진단 루프 활용',
                mathking: '진단평가 → 맞춤 콘텐츠 자동 제공',
                management: '학습 이탈 경보 활용, 히스토리 기반 개입 시점 자동화',
                heavyMessage: '기초가 없는 건물은 무너진다. 너의 부족함을 인정하는 것부터가 시작이다. 부끄러움은 사치다.',
                mbti: ['ISFJ', 'ISFP', 'INFP'],
                executionPoints: [
                    '진단 결과를 있는 그대로 받아들이기',
                    '하루 최소 2시간 기초 개념 반복 학습',
                    '모르는 것을 적는 "무지 노트" 작성',
                    '주 3회 이상 AI 튜터와 1:1 세션',
                    '작은 성취도 기록하며 자신감 쌓기'
                ]
            },
            exam: {
                title: '시험대비 중심모드',
                icon: '✏️',
                target: '시험에 죽고 사는 유형, 동기부여 자가발전 타입',
                description: '내신 분석 → 파이널 기억인출 구조 세팅',
                mathking: '단원별 출제 빈도 분석, Final리뷰 구성',
                management: '시험 3~4주 전 계획 리마인드, 예상문제 정확도 추적',
                heavyMessage: '시험은 전쟁이고, 성적은 네 무기다. 1점에 울고 웃는 게 현실이면, 그 1점에 목숨을 걸어라.',
                mbti: ['ENTJ', 'ESTP', 'ENTP'],
                executionPoints: [
                    'D-30부터 시작하는 철저한 시험 대비',
                    '매일 밤 그날 배운 내용 백지 복습',
                    '기출문제는 3회독 - 틀릴 때까지',
                    '시험 당일 컨디션 관리 루틴 확립',
                    '시험 후 오답 분석은 48시간 내 완료'
                ]
            },
            mission: {
                title: '단기미션 중심모드',
                icon: '⚡',
                target: '집중력 낮고 루틴이 없는 학생',
                description: '짧은 목표 → 성취 → 피드백 → 반복 학습 루프',
                mathking: '미션 과제 단위로 제공 + 피드백 자동 누적',
                management: '미션 완료율 체크, 짧은 주기 성취 기록 강조',
                heavyMessage: '넌 지금 게임 중독자처럼 공부에 중독되어야 한다. 도파민을 학습으로 채워라. 그게 네 구원이다.',
                mbti: ['ESFP', 'ESTP', 'ENFP'],
                executionPoints: [
                    '하루 5개 미션 - 실패 시 다음날 7개',
                    '미션 클리어 스트릭 최소 7일 유지',
                    '10분 집중, 5분 휴식 포모도로 기법',
                    '달성률 80% 미만 시 난이도 재조정',
                    '주간 보상 시스템으로 동기 유지'
                ]
            },
            reflection: {
                title: '자기성찰 중심모드',
                icon: '🧠',
                target: '고민은 많고 생각은 깊은데 실행은 없는 학생',
                description: '학습 후 자기평가 → 피드백 기록 → 학습전략 수정',
                mathking: '학습일지 작성 기능, 자기 피드백 작성',
                management: '일지 작성 여부 주기적 확인, 내용 키워드 분석',
                heavyMessage: '생각만 하는 자는 아무것도 이루지 못한다. 네 머릿속 계획이 현실이 되지 않으면 그건 망상일 뿐이다.',
                mbti: ['INFJ', 'INFP', 'INTJ'],
                executionPoints: [
                    '매일 밤 10분 학습 일지 작성 의무화',
                    '주간 메타인지 체크리스트 작성',
                    '실행하지 않은 계획은 "실패 기록"에',
                    '월 1회 학습 전략 전면 재검토',
                    '생각과 행동의 갭 측정 및 개선'
                ]
            },
            selfled: {
                title: '자기주도 중심모드',
                icon: '🚀',
                target: '자율성 높은 중·상위권, "나만의 공부법" 선호자',
                description: '수업 시나리오를 본인이 직접 설계하고 주도',
                mathking: '수업 플랜 템플릿 제공 + 커스텀 루트 설계',
                management: '시나리오 목표와 실제 실행 비교, 피드백 순환 설계',
                heavyMessage: '네가 직접 설계한 수업이 망하면, 그건 선생님 탓이 아니라 네가 만든 실패야. 주인공이면 책임도 지는 거야.',
                mbti: ['INTJ', 'ENTJ', 'INTP', 'ENTP'],
                executionPoints: [
                    '주간 학습 계획 직접 수립 및 공유',
                    '실패한 계획은 원인 분석 후 수정',
                    '자기 주도 학습 시간 최소 70% 확보',
                    '멘토/동료와 월 2회 피드백 세션',
                    '분기별 학습 포트폴리오 제작'
                ]
            },
            cognitive: {
                title: '도제학습 중심모드',
                icon: '🔍',
                target: '중상위권 이상, 수학을 "이해하고 싶어하는" 유형',
                description: '사고하는 법을 가르치는 수업, 결과보다 과정 중심',
                mathking: '풀이 과정 시뮬레이션, 다양한 풀이 방법 비교',
                management: '사고 과정 기록 → 피드백, 학생별 사고 패턴 분석',
                heavyMessage: '너는 이제 사고력 훈련장의 조교다. 개념을 외우는 게 아니라 "어떻게 생각해야 하는지"를 길러야 한다.',
                mbti: ['ISFJ', 'ISTJ', 'ESFJ', 'ESTJ'],
                executionPoints: [
                    '모델링: 교사의 생각 과정 시연 관찰',
                    '코칭: 풀이 이유를 말로 표현하기',
                    '스캐폴딩: 점진적 지원 제거',
                    '명료화: 정기적 되돌아보기와 전략 성찰',
                    '탐색: 열린 문제와 다양한 풀이 허용'
                ]
            },
            timecentered: {
                title: '시간성찰 중심모드',
                icon: '🕒',
                target: '집중력 관리, 시간 밀도 최적화, 반복 학습 주기 설계',
                description: '시간 관리와 학습 밀도를 최적화하여 효율을 극대화',
                mathking: '집중 시간 분석, 최적 학습 패턴 도출',
                management: '시간별 효율성 추적, 피로도 관리',
                heavyMessage: '시간은 네가 가진 유일한 자원이다. 1분 1초를 헛되이 쓰면 그만큼 네 미래가 사라진다.',
                mbti: ['INFJ', 'ISFP', 'INFP', 'ENFJ'],
                executionPoints: [
                    '매일 학습 시간 기록 및 분석',
                    '집중력 최고 구간 파악 및 활용',
                    '15-30-15 학습 사이클 적용',
                    '주간 시간 효율성 리포트 작성',
                    '비효율 구간 제거 및 개선'
                ]
            },
            curiositycentered: {
                title: '탐구학습 중심모드',
                icon: '🔭',
                target: '순수 탐구 동기, 질문 중심 학습, GPT 활용 확장',
                description: '호기심과 질문을 중심으로 탐구적 학습을 진행',
                mathking: '질문 기반 탐구 가이드, AI 활용 심화 학습',
                management: '탐구 주제 추적, 학습 깊이 측정',
                heavyMessage: '호기심을 잃으면 죽은 거나 다름없다. 질문하지 않는 자는 성장하지 않는다.',
                mbti: ['ENFP', 'ESFP', 'ISTP', 'ESTP'],
                executionPoints: [
                    '매일 3개 이상 "왜?" 질문 생성',
                    '질문에 대한 탐구 과정 기록',
                    'AI 도구를 활용한 심화 탐구',
                    '주간 탐구 결과 발표 및 공유',
                    '호기심 지도 작성 및 확장'
                ]
            }
        };

        // 시기별 실행 포인트 정의 (중고등 수학학원 선행학습 & 내신대비 특화)
        const periodExecutionPoints = {
            vacation: {
                name: '방학',
                icon: '🏖️',
                curriculum: [
                    '선행학습 집중 - 다음 학기 수학 전 과정 1회독 완료',
                    '중등: 고1 수학(상) 선행 / 고등: 수1, 수2 또는 미적분 선행',
                    '매일 4시간 수학 (선행 2시간 + 현행 복습 2시간)',
                    '쎈/RPM/블랙라벨 단계별 완성 (B단계까지 필수)',
                    '주 2회 선행 진도 점검 테스트 + 오답 클리닉'
                ],
                custom: [
                    '현재 학년 취약 단원 완벽 보강 (중등: 함수, 도형 / 고등: 수열, 벡터)',
                    '개인별 수준에 맞춘 선행 속도 조절 (3개월~6개월 선행)',
                    '중등→고등 전환 학생: 고등 수학 기초 개념 브릿지 수업',
                    '내신 1등급 목표: 심화 문제집 추가 (일품, 블랙라벨)',
                    '중하위권: 개념원리 + 쎈 A단계 반복 학습'
                ],
                exam: [
                    '지난 학기 중간/기말고사 기출문제 완벽 복습',
                    '목표 고등학교/대학 기출문제 분석 (특목고, 자사고 포함)',
                    '중3: 고입 대비 심화 문제 / 고3: 수능 기출 4점 문항 도전',
                    '학교별 내신 출제 경향 파악 및 예상 문제 제작',
                    '전국연합 모의고사 기출 주 1회 실전 연습'
                ],
                mission: [
                    '매일 선행 진도 체크리스트 100% 달성',
                    '수학 개념 설명 영상 만들어 학원 SNS 업로드',
                    '스터디 그룹 내 Best 문제 풀이 선정 (주 1회)',
                    '틀린 문제 다시 풀어 만점 받기 챌린지',
                    '수학 등급 향상 프로젝트 (현재 등급 → 목표 등급)'
                ],
                reflection: [
                    '선행 진도율 vs 이해도 균형 점검',
                    '중등: 고등 수학 준비도 자가 진단',
                    '고등: 현재 모의고사 등급 vs 목표 등급 갭 분석',
                    '효과적인 문제집 선택 여부 재평가',
                    '다음 학기 내신 목표 설정 (등급/점수 구체화)'
                ],
                selfled: [
                    '나만의 선행 로드맵 작성 (중1→고3 수학 전 과정)',
                    '자습실 활용 - 매일 2시간 추가 자율 학습',
                    '수학 스터디 그룹 리더/부리더 활동',
                    '인강(EBS, 메가스터디) + 학원 수업 병행 전략',
                    '오답노트 체계화 (단원별, 유형별 분류)'
                ],
                cognitive: [
                    '중등: 고등 수학으로 연결되는 개념 파악',
                    '고등: 수능 킬러 문항 접근법 훈련',
                    '다양한 풀이법 마스터 (대수적/기하적/좌표 풀이)',
                    '증명 문제 및 논술형 문제 대비',
                    '수학적 귀납법, 귀류법 등 논리적 사고 훈련'
                ],
                timecentered: [
                    '선행 vs 복습 시간 배분 (6:4 비율 권장)',
                    '문제 유형별 시간 관리 (객관식 3분, 주관식 5분)',
                    '내신 시험 100분 실전 시간 배분 연습',
                    '효율적 학습 시간대 파악 (아침형/저녁형)',
                    '집중력 지속 시간 늘리기 훈련'
                ],
                curiositycentered: [
                    '수학과 관련된 이공계 진로 탐색',
                    '수학 올림피아드 문제 도전',
                    '대학 수학 미리보기 (미적분학, 선형대수)',
                    'GeoGebra, Desmos 등 수학 도구 활용',
                    '수학 관련 도서 읽기 (수학의 정석 저자 인터뷰 등)'
                ]
            },
            exam2months: {
                name: '중간/기말 2개월 전',
                icon: '📅',
                curriculum: [
                    '시험 범위 확정 및 진도 계획표 작성',
                    '교과서 + 학교 프린트 1회독 완료',
                    '쎈 B단계까지 1회독 목표',
                    '주 3회 학원 정규 수업 + 주 1회 보충',
                    '단원별 개념 정리 노트 작성 시작'
                ],
                custom: [
                    '현재 등급 진단 테스트 (목표 등급과 비교)',
                    '학교별 시험 특징 파악 (서술형 비중, 난이도)',
                    '개인별 취약 유형 분석 및 집중 보강',
                    '중위권: 쎈 A, B 반복 / 상위권: C단계 + 일품',
                    '1:1 질문 클리닉 시간 예약 및 활용'
                ],
                exam: [
                    '작년 같은 범위 기출문제 입수 및 분석',
                    '학교 선생님별 출제 스타일 파악',
                    '빈출 유형 TOP 20 선정 및 집중 학습',
                    '변형 문제 대비 - 숫자 바꾸기, 조건 바꾸기',
                    '첫 실전 모의고사 실시 (학원 자체 제작)'
                ],
                mission: [
                    '2개월 카운트다운 시작 - D-60 계획표',
                    '매일 필수 30문제 풀이 인증',
                    '단원 마스터 뱃지 획득 (단원평가 90점 이상)',
                    '주말 집중 특강 참여율 100% 달성',
                    '학원 자습실 이용 시간 주 10시간 이상'
                ],
                reflection: [
                    '현재 예상 등급 vs 목표 등급 차이 분석',
                    '지난 시험 실수 패턴 점검 (계산 실수, 시간 부족 등)',
                    '문제집 진도율 체크 (쎈 60% 이상 완료 목표)',
                    '학원 수업 이해도 자가 평가',
                    '시험 스트레스 관리 방법 찾기'
                ],
                selfled: [
                    '시험 대비 8주 계획표 작성',
                    '학원 자습실 좌석 확보 및 활용 계획',
                    '같은 학교 친구들과 시험 대비 스터디',
                    '인강 병행 - 약점 단원 EBS 강의 수강',
                    '주간 모의고사 자체 실시 및 채점'
                ],
                cognitive: [
                    '개념 간 연결고리 파악 (단원 통합 문제 대비)',
                    '문제 읽기 능력 향상 - 조건 파악 연습',
                    '풀이 과정 논리적 서술 연습',
                    '실수 줄이기 - 검산 습관화',
                    '고난도 문항 접근 전략 수립'
                ],
                timecentered: [
                    '수학 학습 시간 하루 3시간 확보',
                    '단원별 중요도에 따른 시간 배분',
                    '문제 풀이 속도 향상 훈련 (중등: 50분, 고등: 100분)',
                    '학원-집-학교 연계 학습 시간표',
                    'SNS, 게임 시간 최소화 약속'
                ],
                curiositycentered: [
                    '시험 범위 내 심화 탐구 주제 선정',
                    '수행평가 연계 프로젝트 준비',
                    '수학사 또는 실생활 활용 사례 조사',
                    '어려운 문제 질문 리스트 작성',
                    'YouTube 수학 강의 활용법 익히기'
                ]
            },
            exam1month: {
                name: '시험 1개월 전',
                icon: '📆',
                curriculum: [
                    '시험 범위 2회독 완료 + 3회독 시작',
                    '쎈 C단계까지 완료 (상위권은 일품/블랙라벨 추가)',
                    '주 4회 정규 수업 + 주 2회 특강',
                    '모의고사 주 2회 실시 및 즉시 해설',
                    '서술형 대비 풀이 과정 쓰기 훈련'
                ],
                custom: [
                    '취약 유형 집중 클리닉 (주 3회)',
                    '중위권: 쎈 B, C 반복 / 상위권: 블랙라벨 집중',
                    '학교별 예상 문제 제공 및 풀이',
                    '오답 클리닉 - 빈출 오답 유형 집중 공략',
                    '1:1 질문 시간 매일 30분 확보'
                ],
                exam: [
                    '최근 3년 기출문제 완벽 분석',
                    '학교 선생님별 출제 패턴 정리',
                    '예상 변형 문제 30선 제작',
                    '실전 모의고사 주 2회 + 오답 노트 정리',
                    '시험 시간 배분 전략 연습 (OMR 마킹 포함)'
                ],
                mission: [
                    'D-30 카운트다운 공부법',
                    '매일 필수 50문제 풀이 인증',
                    '빈출 유형 완벽 정복 챌린지',
                    '오답 0개 만들기 프로젝트',
                    '학원 주말 특강 100% 참석'
                ],
                reflection: [
                    '목표 등급 달성 가능성 평가',
                    '문제집 진도율 체크 (쎈 80% 이상 필수)',
                    '실전 모의고사 성적 분석',
                    '취약 유형 완복 여부 점검',
                    '마지막 1개월 전략 수정'
                ],
                selfled: [
                    '시험 대비 4주 타이트 계획',
                    '학원 자습실 매일 3시간 이상 활용',
                    '시험 대비 스터디 그룹 운영',
                    '취약 단원 인강 집중 수강',
                    '주말 자체 모의고사 실시'
                ],
                cognitive: [
                    '난이도 상/최상 문항 집중 훈련',
                    '단계별 풀이 전략 정립',
                    '서술형 논리 전개 연습',
                    '실수 패턴 분석 및 개선',
                    '시험 중 막힌 문제 넘기기 전략'
                ],
                timecentered: [
                    '수학 학습 하루 4시간 확보',
                    '단원별 중요도 재배분 (핵심 단원 70%)',
                    '시험 시간 실전 연습 (중등 50분, 고등 100분)',
                    '아침 학습 루틴 확립 (6시 기상)',
                    '휴대폰 사용 시간 하루 1시간 제한'
                ],
                curiositycentered: [
                    '시험 범위 내 최고난도 문제 도전',
                    '경시대회 기출문제 풀어보기',
                    '다양한 풀이법 비교 연구',
                    '수학 유튜버 해설 비교 분석',
                    '시험 후 진로 탐색'
                ]
            },
            exam2weeks: {
                name: '시험 2주 전',
                icon: '⏰',
                curriculum: [
                    '전 범위 3회독 마무리',
                    '핵심 요약 노트 완성',
                    '빈출 문제 집중 연습',
                    '실전 시뮬레이션',
                    '최종 점검 리스트 작성'
                ],
                custom: [
                    '개인 약점 최종 보강',
                    '맞춤형 문제 집중 풀이',
                    '실수 유형 체크리스트',
                    '자신감 회복 프로그램',
                    'AI 튜터 최종 점검'
                ],
                exam: [
                    '기출문제 2회독',
                    '오답노트 집중 복습',
                    '실전 모의고사 매일',
                    '시간 배분 전략 확정',
                    '컨디션 관리 시작'
                ],
                mission: [
                    '파이널 스퍼트 미션',
                    '핵심 미션만 선별 수행',
                    '일일 달성률 100% 도전',
                    '취약점 극복 미션',
                    '자신감 상승 미션'
                ],
                reflection: [
                    '2주 전 상태 점검',
                    '남은 과제 명확화',
                    '멘탈 상태 체크',
                    '최종 전략 수립',
                    '실행 계획 구체화'
                ],
                selfled: [
                    '2주 마스터 플랜',
                    '자율 복습 시간표',
                    '핵심 정리 노트 작성',
                    '스터디 최종 점검',
                    '개인별 마무리 전략'
                ],
                cognitive: [
                    '문제 해결 속도 향상',
                    '실전 감각 익히기',
                    '사고 과정 최적화',
                    '실수 방지 훈련',
                    '자신감 있는 풀이'
                ],
                timecentered: [
                    '2주 타임테이블',
                    '과목별 최종 배분',
                    '황금 시간 활용',
                    '집중력 유지 전략',
                    '시험 당일 시뮬레이션'
                ],
                curiositycentered: [
                    '마지막 궁금증 해결',
                    '재미있는 복습법',
                    '창의적 암기법',
                    '스트레스 해소 탐구',
                    '동기부여 콘텐츠'
                ]
            },
            exam1week: {
                name: '시험 1주 전',
                icon: '🚨',
                curriculum: [
                    '시험 범위 최종 정리 및 암기',
                    '학원 최종 예상문제 풀이',
                    '매일 오전/오후 실전 모의고사',
                    '취약 단원 최종 특강',
                    '시험 시간 관리 전략 확정'
                ],
                custom: [
                    '개인별 취약 유형 최종 클리닉',
                    '상위권: 블랙라벨 난이도 상 / 중위권: 쎈 C단계',
                    '실수 방지 체크리스트 활용',
                    '학교별 예상문제 최종 점검',
                    '1:1 질문 시간 무제한 제공'
                ],
                exam: [
                    '최근 5년 기출문제 최종 점검',
                    '오답노트 최종 복습',
                    '시험 시간 배분 연습 (50분/100분)',
                    '학교 선생님별 출제 경향 최종 확인',
                    '컨디션 관리 (수면, 영양, 운동)'
                ],
                mission: [
                    'D-7 최종 점검 미션',
                    '매일 필수 100문제 풀이',
                    '실수 0개 만들기 챌린지',
                    '자습실 매일 5시간 이상',
                    '멘탈 관리 - 긍정 마인드 세트'
                ],
                reflection: [
                    '목표 등급 달성 가능성 최종 평가',
                    '문제집 진도율 100% 확인',
                    '모의고사 성적 추이 분석',
                    '시험 불안 관리 및 자신감 회복',
                    '시험 당일 시뮬레이션'
                ],
                selfled: [
                    '시험 1주전 타이트 계획',
                    '학원 자습실 거주 모드 실행',
                    '핵심 공식 및 개념 최종 암기',
                    '컨디션 관리 (수면 8시간 확보)',
                    '시험 준비물 점검 (컴퍼스, 계산기 등)'
                ],
                cognitive: [
                    '문제 유형별 접근 전략 최종 정리',
                    '빠른 문제 해결 판단력 훈련',
                    '서술형 문제 논리 전개 연습',
                    '실수 패턴 최종 제거',
                    '시험 중 막히면 넘기기 전략'
                ],
                timecentered: [
                    '수학 학습 하루 6시간 이상',
                    '단원별 최종 시간 배분',
                    '시험 시간 실전 연습 매일 2회',
                    '수면 8시간 확보 필수',
                    '휴대폰 사용 최소화'
                ],
                curiositycentered: [
                    '시험 범위 내 최고난도 문제 도전',
                    '수학 유튜버 해설 방법 정리',
                    '시험 전 긴장 완화 방법',
                    '성공적인 시험 사례 연구',
                    '시험 후 진로 계획'
                ]
            },
            exam3days: {
                name: '시험 3일 전',
                icon: '🔥',
                curriculum: [
                    '수학 핵심 개념 총정리 - 단원별 핵심 공식 암기',
                    '기출문제 최종 점검 - 최근 3개년 빈출 유형',
                    '실전 모의고사 1회 - 시간 엄수하며 풀이',
                    '계산 실수 방지 훈련 - 검산 습관화',
                    '킬러 문제 전략 재확인 - 21, 29, 30번 대비'
                ],
                custom: [
                    '개인별 오답노트 최종 복습 - 반복 실수 패턴 체크',
                    '취약 단원 집중 보완 - 함수, 미적분, 확통 중점',
                    '수학적 자신감 회복 - 풀 수 있는 문제부터',
                    '개념 암기 카드 활용 - 공식 빠른 복습',
                    '맞춤형 문제 선별 학습 - 개인 수준별 문제'
                ],
                exam: [
                    '수능/내신 기출 패턴 분석 - 최다 출제 유형 정리',
                    '시간 배분 전략 최종 점검 - 2점 5분, 3점 7분, 4점 10분',
                    '계산 실수 체크리스트 - 부호, 계산, 조건 확인',
                    '문제 풀이 순서 확정 - 쉬운 것부터 어려운 것으로',
                    '마킹 실수 방지 훈련 - OMR 연습'
                ],
                mission: [
                    'D-3 수학 집중 미션 - 3시간 집중 학습',
                    '핵심 공식 암기 테스트 - 100% 정답 목표',
                    '실전 모의고사 도전 - 목표 점수 달성',
                    '오답 완벽 정복 - 틀린 문제 다시 풀기',
                    '멘탈 관리 미션 - 긍정 확언 10회'
                ],
                reflection: [
                    '수학 시험 대비 최종 점검 - 준비도 자가 평가',
                    '문제 풀이 전략 정리 - 과정 중심 vs 답 중심',
                    '실수 패턴 최종 분석 - 반복 실수 방지책',
                    '멘탈 관리 전략 - 긴장도 조절',
                    '목표 등급 달성 시각화'
                ],
                selfled: [
                    '자기 주도 3일 수학 계획 - 개인 전략 수행',
                    '효율적 문제 복습 - 취약 유형 중심',
                    '컨디션 최적화 - 수학 두뇌 활성화',
                    '시험 도구 준비 - 계산기, 컴퍼스, 자',
                    '자신감 관리 - 풀 수 있는 문제 중심'
                ],
                cognitive: [
                    '수학적 사고 패턴 최종 정리 - 문제 해결 단계',
                    '문제 접근법 확정 - 유형별 최적 전략',
                    '침착한 문제 해결 - 당황하지 않기',
                    '빠른 판단 연습 - 시간 부족 대비',
                    '확신 있는 풀이 - 검산 습관화'
                ],
                timecentered: [
                    '3일 수학 시간 관리 - 하루 4시간 집중',
                    '충분한 휴식 계획 - 수학 두뇌 회복',
                    '시험 당일 시간 배분 - 100분 활용법',
                    '여유 시간 확보 - 문제 검토 시간',
                    '생체 리듬 조절 - 수학 시험 시간대'
                ],
                curiositycentered: [
                    '수학 문제의 본질 탐구 - 출제 의도 파악',
                    '흥미로운 암기법 - 스토리텔링 공식',
                    '스트레스 해소법 - 수학 불안 극복',
                    '성공 사례 연구 - 선배들의 비법',
                    '자신감 충전 - 풀 수 있는 문제 확인'
                ]
            },
            exam1day: {
                name: '시험 1일 전',
                icon: '💯',
                curriculum: [
                    '수학 핵심 공식만 최종 확인 - 10분 점검',
                    '시험 도구 최종 체크 - 계산기, 컴퍼스, 자',
                    '가벼운 문제 풀이만 - 자신감 유지',
                    '충분한 휴식 - 수학 두뇌 휴식',
                    '시험 시간표 최종 확인'
                ],
                custom: [
                    '수학 자신감 최대 충전 - 풀 수 있는 문제 확인',
                    '핵심 공식만 최종 체크 - 5분 간단 복습',
                    '멘탈 안정 - 수학 불안 극복',
                    '긍정적 마인드 - 목표 등급 달성 확신',
                    '성공 확신 - 준비한 만큼 나온다'
                ],
                exam: [
                    '수학 시험 전략 최종 확인 - 문제 풀이 순서',
                    '준비물 체크 - 신분증, 필기구, 계산기',
                    '시간 배분 암기 - 2점 5분, 3점 7분, 4점 10분',
                    '실수 방지 다짐 - 검산 필수',
                    '당일 루틴 확정 - 아침 식사, 이동 시간'
                ],
                mission: [
                    'D-1 수학 파이널 - 가벼운 문제 10개',
                    '자신감 충만 - 풀 수 있는 문제 복습',
                    '컨디션 최고 - 조기 취침',
                    '긴장 제로 - 명상 10분',
                    '성공 확신 - 목표 등급 시각화'
                ],
                reflection: [
                    '그동안의 노력 인정',
                    '자신에 대한 믿음',
                    '긍정적 결과 상상',
                    '마음의 평화',
                    '최선을 다할 각오'
                ],
                selfled: [
                    '하루 전 루틴',
                    '가벼운 복습',
                    '조기 취침 준비',
                    '내일 준비 완료',
                    '마음가짐 정리'
                ],
                cognitive: [
                    '자신감 있는 사고',
                    '침착한 마음가짐',
                    '문제 해결 확신',
                    '긍정적 접근',
                    '최상의 컨디션'
                ],
                timecentered: [
                    '당일 시간표 확인',
                    '충분한 수면 계획',
                    '아침 루틴 계획',
                    '여유롭게 출발',
                    '시험 시간 관리'
                ],
                curiositycentered: [
                    '수학 성공 동기부여 - 목표 달성의 기쁨',
                    '수학 성공 사례 - 선배들의 경험',
                    '수학 불안 완화 - 명상과 호흡',
                    '자신감 증진 - 풀 수 있는 문제',
                    '준비 완료 확인 - 모든 준비 끝'
                ]
            },
            noExam: {
                name: '시험 없음',
                icon: '📖',
                curriculum: [
                    '정규 커리큘럼 진행',
                    '꾸준한 일일 학습',
                    '선행학습 병행',
                    '복습 주기 유지',
                    '장기 목표 추진'
                ],
                custom: [
                    '개인 속도로 학습',
                    '기초 탄탄히 다지기',
                    '약점 천천히 보완',
                    '흥미 위주 학습',
                    '부담 없는 진도'
                ],
                exam: [
                    '평소 실력 쌓기',
                    '문제 풀이 습관화',
                    '모의고사 정기 실시',
                    '오답 정리 생활화',
                    '실력 점진적 향상'
                ],
                mission: [
                    '일상 미션 수행',
                    '재미있는 도전',
                    '습관 만들기 미션',
                    '성취감 쌓기',
                    '꾸준함이 힘'
                ],
                reflection: [
                    '일일 학습 돌아보기',
                    '주간 성찰',
                    '월간 평가',
                    '성장 기록',
                    '목표 재설정'
                ],
                selfled: [
                    '자율 학습 계획',
                    '관심사 탐구',
                    '프로젝트 진행',
                    '스터디 운영',
                    '포트폴리오 제작'
                ],
                cognitive: [
                    '수학적 사고 깊이 - 논리적 사고',
                    '다양한 풀이 접근 - 여러 방법 시도',
                    '창의적 수학 사고 - 새로운 해법',
                    '논리력 향상 - 증명 훈련',
                    '문제해결 능력 - 단계적 접근'
                ],
                timecentered: [
                    '일상 수학 루틴 - 매일 2시간',
                    '효율적 시간 활용 - 집중 학습',
                    '균형잡힌 학습 - 개념+문제',
                    '여가 시간 확보 - 수학 외 활동',
                    '지속가능한 학습 - 꾸준함'
                ],
                curiositycentered: [
                    '수학 자유 탐구 - 주제별 학습',
                    '수학 관심사 심화 - 특정 분야',
                    '수학 프로젝트 - 탐구 활동',
                    '창의적 수학 - 새로운 시각',
                    '즐거운 수학 학습 - 재미있는 수학'
                ]
            }
        };

        // 선생님 모드도 학생 모드와 동일하게 설정
        const teacherModes = { ...studentModes };

        // State
        let currentView = 'tab';
        let currentMode = 'teacher';
        let selectedTeacherMode = existingModes ? existingModes.teacher_mode : null;
        let selectedStudentMode = existingModes ? existingModes.student_mode : null;

        // Show status message
        function showStatusMessage(message) {
            const statusEl = document.getElementById('statusMessage');
            statusEl.textContent = message;
            statusEl.classList.add('show');
            
            // Keep the message visible (don't auto-hide)
        }

        // Switch to mode (from sidebar buttons)
        function switchToMode(mode) {
            // Prevent students from switching to teacher mode
            if (userRole === 'student' && mode === 'teacher') {
                // Simply return without any error message for smooth UX
                return;
            }
            
            currentMode = mode;
            
            // Update sidebar buttons
            if (mode === 'teacher') {
                document.getElementById('teacherBtn').classList.add('active');
                document.getElementById('studentBtn').classList.remove('active');
                document.getElementById('teacherContent').classList.add('active');
                document.getElementById('studentContent').classList.remove('active');
                
                showStatusMessage('선생님의 수업 스타일 선택하기');
            } else {
                if (document.getElementById('teacherBtn')) {
                    document.getElementById('teacherBtn').classList.remove('active');
                }
                document.getElementById('studentBtn').classList.add('active');
                if (document.getElementById('teacherContent')) {
                    document.getElementById('teacherContent').classList.remove('active');
                }
                document.getElementById('studentContent').classList.add('active');
                
                showStatusMessage('학생의 학습모드 선택하기');
            }
        }
        
        // Toggle scroll view
        function toggleScrollView() {
            const tabView = document.getElementById('tabView');
            const scrollView = document.getElementById('scrollView');
            const toggleBtn = document.getElementById('scrollToggle');
            const viewIcon = document.getElementById('viewIcon');
            const viewText = document.getElementById('viewText');
            
            if (currentView === 'tab') {
                // Switch to scroll view
                currentView = 'scroll';
                tabView.style.display = 'none';
                scrollView.classList.add('active');
                toggleBtn.classList.add('scroll-active');
                viewIcon.textContent = '📑';
                viewText.textContent = '탭 뷰';
            } else {
                // Switch to tab view
                currentView = 'tab';
                tabView.style.display = 'block';
                scrollView.classList.remove('active');
                toggleBtn.classList.remove('scroll-active');
                viewIcon.textContent = '📜';
                viewText.textContent = '스크롤 뷰';
            }
        }

        // MBTI Mode Match Explanations
        const mbtiModeExplanations = {
            curriculum: {
                'ISTJ': '체계적이고 꼼꼼한 ISTJ는 커리큘럼을 한 치의 오차도 없이 따라가는 걸 좋아해요. 계획표가 삶의 낙이죠!',
                'ESTJ': '목표 달성의 제왕 ESTJ! 대학이라는 목표를 정했다면 탱크처럼 직진하는 당신에게 딱이에요.',
                'INTJ': '전략가 INTJ는 대입을 하나의 거대한 체스판으로 봅니다. 모든 수를 계산하며 전진!'
            },
            custom: {
                'ISFJ': '따뜻한 보호자 ISFJ는 자신의 속도에 맞춰 차근차근 성장하는 걸 선호해요. 급하게 가다 넘어지느니 천천히 확실하게!',
                'ISFP': '예민한 예술가 ISFP는 자신만의 학습 리듬이 필요해요. 남들과 다른 속도? 그게 바로 당신의 매력!',
                'INFP': '이상주의자 INFP는 자신의 가치관에 맞는 학습법을 찾아야 해요. 맞춤형이 아니면 의미가 없죠!'
            },
            exam: {
                'ENTJ': '지휘관 ENTJ에게 시험은 정복해야 할 산! 1점이라도 더 높이 올라가는 게 인생의 목표.',
                'ESTP': '모험가 ESTP는 시험을 스릴 넘치는 게임으로 봐요. 벼락치기의 달인이지만, 이번엔 제대로 준비해볼까요?',
                'ENTP': '토론가 ENTP는 시험 문제와 논쟁하고 싶어해요. "이 문제 출제자와 한번 붙어보고 싶다!"'
            },
            mission: {
                'ESFP': '자유로운 연예인 ESFP는 짧고 굵게! 긴 목표는 지루해요. 오늘의 미션을 클리어하는 재미가 최고!',
                'ESTP': '행동파 ESTP는 생각보다 행동! 일단 미션 하나씩 깨면서 레벨업하는 게 딱 맞아요.',
                'ENFP': '열정적인 활동가 ENFP는 새로운 도전을 사랑해요. 매일 다른 미션? 지루할 틈이 없네요!'
            },
            reflection: {
                'INFJ': '통찰력의 제왕 INFJ는 깊은 성찰을 통해 성장해요. 일기 쓰듯 학습을 돌아보는 시간이 보약!',
                'INFP': '몽상가 INFP는 자기 내면과의 대화를 즐겨요. 오늘 배운 건 내 삶에 어떤 의미일까?',
                'INTJ': '완벽주의 INTJ는 자신의 학습을 분석하고 개선점을 찾아요. 더 나은 내일을 위한 오늘의 성찰!'
            },
            selfled: {
                'INTJ': '독립적인 INTJ는 남의 간섭 없이 자기 방식대로! 내가 만든 계획이 최고의 계획.',
                'ENTJ': 'CEO형 ENTJ는 자신의 학습 帝국을 건설해요. 남이 만든 길? 난 내 길을 만들어!',
                'INTP': '논리왕 INTP는 자신만의 학습 시스템을 구축해요. 남들이 이해 못해도 괜찮아, 이게 내 방식!',
                'ENTP': '혁신가 ENTP는 기존 학습법을 뒤집어요. 왜 꼭 그렇게 해야 해? 내 방식이 더 효율적인데!'
            },
            cognitive: {
                'ISFJ': '세심한 ISFJ는 선생님의 사고 과정을 그대로 흡수해요. 모방은 창조의 어머니!',
                'ISTJ': '성실한 ISTJ는 단계별로 차근차근 배워가요. 기초부터 탄탄하게, 그게 진짜 실력!',
                'ESFJ': '친화적인 ESFJ는 선생님과의 상호작용을 통해 배워요. 함께 성장하는 기쁨!',
                'ESTJ': '실용적인 ESTJ는 배운 걸 바로 적용해요. 이론만? NO! 실전에서 써먹어야 진짜!'
            },
            timecentered: {
                'INFJ': '계획적인 INFJ는 시간을 아름답게 설계해요. 매 순간이 의미 있는 퍼즐 조각!',
                'ISFP': '유연한 ISFP는 자신의 바이오리듬에 맞춰요. 아침형? 저녁형? 내 시간은 내가 정해!',
                'INFP': '자유로운 INFP지만 시간 관리는 철저히! 방황하는 시간도 계획된 방황이어야.',
                'ENFJ': '리더형 ENFJ는 시간을 통제하며 목표를 향해요. 시간은 내 편, 효율은 내 무기!'
            },
            curiositycentered: {
                'ENFP': '호기심 폭발 ENFP! "왜?"라는 질문이 입에서 떠나지 않아요. 세상 모든 게 궁금해!',
                'ESFP': '즐거운 탐험가 ESFP는 재미있는 것부터 파고들어요. 공부도 놀이처럼 신나게!',
                'ISTP': '분석가 ISTP는 원리를 파헤쳐요. 이게 왜 작동하지? 분해하고 조립하며 이해!',
                'ESTP': '실험가 ESTP는 직접 해보며 배워요. 책으로만? 지루해! 손으로 만지고 눈으로 봐야!'
            }
        };

        // Show MBTI Explanation
        function showMBTIExplanation(mbtiType, modeKey, modeTitle) {
            const card = document.getElementById('mbtiExplanationCard');
            const typeBadge = document.getElementById('mbtiTypeBadge');
            const typeName = document.getElementById('mbtiTypeName');
            const content = document.getElementById('mbtiExplanationContent');
            
            // Set MBTI type and name
            typeBadge.textContent = mbtiType;
            typeName.textContent = mbtiDescriptions[mbtiType] || mbtiType;
            
            // Get explanation for this MBTI-mode combination
            const explanation = mbtiModeExplanations[modeKey]?.[mbtiType] || 
                `${mbtiType}와 ${modeTitle}의 환상적인 조합! 당신만의 특별한 학습 여정이 시작됩니다.`;
            
            // Create content with fun tone
            content.innerHTML = `
                <div class="mbti-mode-match">
                    <h4>🎯 ${modeTitle}와 찰떡궁합인 이유</h4>
                    <p>${explanation}</p>
                </div>
                
                <div class="mbti-fun-fact">
                    <div class="mbti-fun-fact-icon">💡</div>
                    <strong>재미있는 사실:</strong><br>
                    ${getFunFact(mbtiType, modeKey)}
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px;">
                    <p style="font-size: 14px; opacity: 0.9;">
                        🎓 이 조합으로 공부하면, ${getMotivationalMessage(mbtiType, modeKey)}
                    </p>
                </div>
            `;
            
            // Show the card with animation
            setTimeout(() => {
                card.classList.add('show');
            }, 10);
        }

        // Close MBTI Explanation
        function closeMBTIExplanation() {
            const card = document.getElementById('mbtiExplanationCard');
            card.classList.remove('show');
        }

        // Get fun fact based on MBTI and mode
        function getFunFact(mbtiType, modeKey) {
            const funFacts = {
                'INTJ': '당신 같은 INTJ는 전체 인구의 2%! 희귀 포켓몬급 존재예요.',
                'ISFJ': 'ISFJ는 은근히 완벽주의자. 대충? 그런 건 사전에 없어요!',
                'ENFP': 'ENFP는 아이디어 제조기! 공부하다가도 100가지 생각이 떠오르죠.',
                'ESTP': 'ESTP는 체험형 학습의 달인. 백문이 불여일견? 백견이 불여일행!',
                'INFJ': 'INFJ는 미래를 내다보는 예언자. 이미 대학 졸업 후까지 그려놨죠?',
                'ISTJ': 'ISTJ는 계획표 없으면 불안해요. 플래너가 인생의 동반자!',
                'ENTP': 'ENTP는 모든 것에 "왜?"를 붙여요. 선생님도 당황하게 만드는 질문왕!',
                'ISFP': 'ISFP는 조용히 강한 스타일. 겉으론 순하지만 속은 단단해요!'
            };
            
            return funFacts[mbtiType] || `${mbtiType}는 자신만의 독특한 학습 스타일을 가지고 있어요. 그게 바로 당신의 강점!`;
        }

        // Get motivational message
        function getMotivationalMessage(mbtiType, modeKey) {
            const messages = [
                '목표 대학 합격률이 23% 상승한다는 연구 결과가 있어요! (농담이에요, 하지만 진짜 효과는 있을 거예요!)',
                '스트레스는 반으로, 효율은 두 배로! 이게 바로 MBTI 맞춤 학습의 마법!',
                '당신의 숨겨진 잠재력이 200% 발휘될 거예요. 준비되셨나요?',
                '공부가 게임처럼 재미있어질 수도...? 최소한 덜 지루해질 거예요!',
                '이 조합을 선택한 당신, 이미 반은 성공한 거예요. 나머지 반은 실천만 하면 돼요!'
            ];
            
            return messages[Math.floor(Math.random() * messages.length)];
        }

        // Initialize
        function init() {
            renderModeCards();
            
            // Role-based initialization
            if (userRole === 'student') {
                // For students: Only show student content
                document.getElementById('teacherContent').style.display = 'none';
                document.getElementById('studentContent').classList.add('active');
                
                // Hide teacher section in scroll view too
                const teacherScrollSection = document.getElementById('teacherGridScroll');
                if (teacherScrollSection) {
                    teacherScrollSection.parentElement.style.display = 'none'; // Hide entire teacher section
                    const teacherDivider = teacherScrollSection.previousElementSibling;
                    if (teacherDivider && teacherDivider.classList.contains('section-divider')) {
                        teacherDivider.style.display = 'none';
                    }
                }
                
                showStatusMessage('학습모드를 선택해주세요');
            } else {
                // For teachers/admins: Show both but default to student tab
                document.getElementById('studentBtn').classList.add('active');
                if (document.getElementById('teacherBtn')) {
                    document.getElementById('teacherBtn').classList.remove('active');
                }
                document.getElementById('teacherContent').classList.remove('active');
                document.getElementById('studentContent').classList.add('active');
                
                showStatusMessage('학생의 학습모드 선택하기');
                
                // If existing modes are loaded, show them
                if (existingModes) {
                    let message = '기존 설정: ';
                    if (existingModes.teacher_mode && teacherModes[existingModes.teacher_mode]) {
                        message += `선생님 - ${teacherModes[existingModes.teacher_mode].title}, `;
                    }
                    if (existingModes.student_mode && studentModes[existingModes.student_mode]) {
                        message += `학생 - ${studentModes[existingModes.student_mode].title}`;
                    }
                    showStatusMessage(message);
                }
                
                // Show user's MBTI if available
                if (userMbti) {
                    setTimeout(() => {
                        showStatusMessage(`나의 MBTI: ${userMbti} ${mbtiDescriptions[userMbti] || ''}`);
                    }, 1500);
                }
            }
        }

        // Render Mode Cards
        function renderModeCards() {
            // Tab View Cards
            renderCards('teacherGrid', teacherModes, 'teacher');
            renderCards('studentGrid', studentModes, 'student');
            
            // Scroll View Cards
            renderCards('teacherGridScroll', teacherModes, 'teacher');
            renderCards('studentGridScroll', studentModes, 'student');
        }

        function renderCards(containerId, modes, type) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            container.innerHTML = '';
            
            Object.entries(modes).forEach(([key, mode]) => {
                const card = document.createElement('div');
                card.className = `mode-card ${key}`;
                if ((type === 'teacher' && selectedTeacherMode === key) || 
                    (type === 'student' && selectedStudentMode === key)) {
                    card.classList.add('selected');
                }
                
                card.onclick = () => {
                    selectMode(key, type);
                    // Auto-save if teacher role and both modes are selected
                    if (userRole !== 'student' && selectedTeacherMode && selectedStudentMode) {
                        saveModes();
                    }
                };
                
                // Determine the approach label based on mode key
                let approachLabel = '';
                if (key === 'curriculum' || key === 'exam') {
                    approachLabel = 'TopDown';
                } else if (key === 'cognitive' || key === 'custom' || key === 'reflection' || key === 'curiositycentered') {
                    approachLabel = 'BottomUp';
                } else if (key === 'mission' || key === 'timecentered' || key === 'selfled') {
                    approachLabel = 'Hybrid';
                }
                
                card.innerHTML = `
                    ${approachLabel ? `<div class="approach-label">${approachLabel}</div>` : ''}
                    <div class="mode-icon">${mode.icon}</div>
                    <div class="mode-title">${mode.title.replace(' ', '<br>')}</div>
                    <div class="mode-target">${mode.target}</div>
                    ${mode.mbti ? `
                    <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; justify-content: center;">
                        ${mode.mbti.map(mbtiType => {
                            const isHighlighted = userMbti && userMbti === mbtiType;
                            return `
                                <span 
                                    class="mbti-badge ${isHighlighted ? 'highlighted' : ''}"
                                    onclick="event.stopPropagation(); showMBTIExplanation('${mbtiType}', '${key}', '${mode.title}')"
                                >
                                    ${isHighlighted ? '⭐ ' : ''}${mbtiType}
                                </span>
                            `;
                        }).join('')}
                    </div>
                    ` : ''}
                    <button class="agent-button" onclick="event.stopPropagation(); showAgent('${key}', '${type}')">
                        🤖 에이전트
                    </button>
                    <button class="detail-button" onclick="event.stopPropagation(); showDetail('${key}', '${type}')">
                        자세히
                    </button>
                `;
                
                container.appendChild(card);
            });
        }

        // Removed old switchView function - now using toggleScrollView

        // Remove unused tab switching function and update other references

        // Mode Selection
        function selectMode(mode, type) {
            if (type === 'teacher') {
                selectedTeacherMode = mode;
                const modeName = teacherModes[mode].title;
                showStatusMessage(`선생님 스타일이 "${modeName}"로 선택되었습니다`);
            } else {
                selectedStudentMode = mode;
                const modeName = studentModes[mode].title;
                showStatusMessage(`학습모드가 "${modeName}"로 선택되었습니다`);
            }
            
            // Re-render cards to update selection
            renderModeCards();
        }

        // Reset Selection
        function resetSelection() {
            selectedTeacherMode = null;
            selectedStudentMode = null;
            renderModeCards();
        }

        // MBTI Type Descriptions
        const mbtiDescriptions = {
            'INTJ': '전략가형',
            'INTP': '논리술사형',
            'ENTJ': '통솔자형',
            'ENTP': '변론가형',
            'INFJ': '옹호자형',
            'INFP': '중재자형',
            'ENFJ': '선도자형',
            'ENFP': '활동가형',
            'ISTJ': '현실주의자형',
            'ISFJ': '수호자형',
            'ESTJ': '경영자형',
            'ESFJ': '집정관형',
            'ISTP': '장인형',
            'ISFP': '모험가형',
            'ESTP': '사업가형',
            'ESFP': '연예인형'
        };

        // Get MBTI with descriptions
        function getMBTIWithDescriptions(mbtiArray) {
            if (!mbtiArray || mbtiArray.length === 0) return '';
            return mbtiArray.map(type => {
                const desc = mbtiDescriptions[type] || type;
                return `${type} (${desc})`;
            }).join(', ');
        }

        // Show Detail
        function showDetail(mode, type) {
            // 선생님 모드에서는 시기 선택 옵션을 포함
            if (type === 'teacher') {
                showDetailWithPeriod(mode, type);
                return;
            }
            
            // For student mode, show iframe modal with corresponding PHP file
            if (type === 'student' && modeToUrlMap[mode]) {
                showIframeModal(mode);
            } else {
                // For teacher mode or fallback, show existing detail modal
                const modeData = type === 'teacher' ? teacherModes[mode] : studentModes[mode];
                const modal = document.getElementById('detailModal');
                const content = document.getElementById('modalContent');
                
                content.innerHTML = `
                    <h2 style="margin-bottom: 20px;">
                        <span style="font-size: 48px; margin-right: 15px;">${modeData.icon}</span>
                        ${modeData.title}
                    </h2>
                    <div class="info-box">
                        <h3>대상</h3>
                        <p>${modeData.target}</p>
                    </div>
                    ${modeData.mbti ? `
                    <div class="info-box">
                        <h3>🧩 MBTI 매칭</h3>
                        <p style="font-size: 14px; line-height: 1.6;">
                            ${modeData.mbti.map(type => {
                                const desc = mbtiDescriptions[type];
                                return `<strong>${type}</strong> - ${desc || type}`;
                            }).join('<br>')}
                        </p>
                    </div>
                    ` : ''}
                    <div class="info-box">
                        <h3>설명</h3>
                        <p>${modeData.description}</p>
                    </div>
                    <div class="info-box warning">
                        <h3>💡 현실조언</h3>
                        <p style="font-weight: bold;">${modeData.heavyMessage}</p>
                    </div>
                    <div class="info-box success">
                        <h3>✅ 실행 포인트</h3>
                        <ul class="execution-list">
                            ${modeData.executionPoints.map(point => `<li>${point}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="info-box">
                        <h3>🤖 Mathking 활용법</h3>
                        <p>${modeData.mathking}</p>
                    </div>
                    <div class="info-box">
                        <h3>📊 관리 포인트</h3>
                        <p>${modeData.management}</p>
                    </div>
                `;
                
                modal.classList.add('show');
            }
        }

        // Show Iframe Modal
        function showIframeModal(mode) {
            const url = modeToUrlMap[mode];
            if (!url) return;
            
            const modeData = studentModes[mode];
            const modal = document.getElementById('iframeModal');
            const iframe = document.getElementById('iframeContent');
            const loading = document.getElementById('iframeLoading');
            const titleIcon = document.getElementById('iframeTitleIcon');
            const titleText = document.getElementById('iframeTitleText');
            
            // Set title
            titleIcon.textContent = modeData.icon;
            titleText.textContent = modeData.title;
            
            // Show modal with animation
            modal.classList.add('show');
            
            // Show loading
            loading.style.display = 'block';
            iframe.style.display = 'none';
            
            // Load iframe content
            iframe.onload = function() {
                loading.style.display = 'none';
                iframe.style.display = 'block';
            };
            
            // Construct full URL with student ID parameter
            const fullUrl = `/moodle/local/augmented_teacher/alt42/studenthome/${url}?userid=${studentId}`;
            iframe.src = fullUrl;
        }

        // Close Iframe Modal
        function closeIframeModal() {
            const modal = document.getElementById('iframeModal');
            const iframe = document.getElementById('iframeContent');
            
            // Hide modal
            modal.classList.remove('show');
            
            // Clear iframe after animation
            setTimeout(() => {
                iframe.src = '';
            }, 500);
        }

        // Close Modal
        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }

        // Show Agent
        // GPT Agent Links
        const gptAgentLinks = {
            teacher: {
                curriculum: 'https://chatgpt.com/g/g-68ac30293ca08191812d5005018220d6',
                custom: 'https://chatgpt.com/g/g-68ac302a3bdc81918a5a6925b5d2b3d4',
                mission: 'https://chatgpt.com/g/g-68ac302b44788191a1ece0a7d5c86ce5',
                exam: 'https://chatgpt.com/g/g-68ac302aa5008191b16185863c7cd67a',
                reflection: 'https://chatgpt.com/g/g-68ac302c1074819195c1c6372d2a6c9c',
                selfled: 'https://chatgpt.com/g/g-68ac302c8d1c8191918c2e3762c16a5b',
                cognitive: 'https://chatgpt.com/g/g-68ac302d9040819186b20e86a7e4e59f',
                timecentered: 'https://chatgpt.com/g/g-68ac302e63a08191834e9720e4266b1e',
                curiositycentered: 'https://chatgpt.com/g/g-68ac3032008481918cbafc905ed85552'
            },
            student: {
                curriculum: 'https://chatgpt.com/g/g-68ac3032ea988191967b64767b991212',
                custom: 'https://chatgpt.com/g/g-68ac3033be28819194c23872ec0f0c96',
                mission: 'https://chatgpt.com/g/g-68ac3035323481919313943efc3e759c',
                exam: 'https://chatgpt.com/g/g-68ac303455d0819189cea96cf8dcd880',
                reflection: 'https://chatgpt.com/g/g-68ac3035c20c8191a14a895092c866ca',
                selfled: 'https://chatgpt.com/g/g-68ac3036564c819186e4b55fb45e2932',
                cognitive: 'https://chatgpt.com/g/g-68ac3036e3ac8191bcdc86cf3747f5e0',
                timecentered: 'https://chatgpt.com/g/g-68ac3037bfb08191841b70986b495d4e',
                curiositycentered: 'https://chatgpt.com/g/g-68ac303885bc819182244907d98fe42b'
            }
        };

        function showAgent(mode, type) {
            const agentUrl = gptAgentLinks[type]?.[mode];
            if (agentUrl) {
                window.open(agentUrl, '_blank');
            } else {
                alert(`${type === 'teacher' ? '선생님' : '학생'} 모드 "${studentModes[mode].title}"의 에이전트가 준비 중입니다.`);
            }
        }

        // Start Chat
        function startChat() {
            if (!selectedTeacherMode || !selectedStudentMode) {
                alert('선생님 모드와 학생 모드를 모두 선택해주세요.');
                return;
            }
            
            alert(`선택한 모드:\n선생님: ${teacherModes[selectedTeacherMode].title}\n학생: ${studentModes[selectedStudentMode].title}\n\n채팅을 시작합니다!`);
        }

        // Go to MBTI Test
        function goToMBTITest() {
            window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/students/mbti_types.php?studentid=${studentId}`;
        }

        // Save Modes to Database (Auto-save)
        async function saveModes() {
            if (!selectedTeacherMode || !selectedStudentMode) {
                return; // Silently return if not both selected
            }

            // Show loading state
            showStatusMessage('자동 저장 중...');
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_modes');
                formData.append('teacher_mode', selectedTeacherMode);
                formData.append('student_mode', selectedStudentMode);
                formData.append('student_id', studentId);
                
                const response = await fetch('selectmode.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatusMessage('✅ 자동 저장 완료!');
                    // Auto-hide success message after 2 seconds
                    setTimeout(() => {
                        const statusEl = document.getElementById('statusMessage');
                        if (statusEl.textContent.includes('자동 저장 완료')) {
                            statusEl.classList.remove('show');
                        }
                    }, 2000);
                } else {
                    showStatusMessage('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Save error:', error);
                // Silently fail for auto-save
            }
        }

        // Show Detail with Period Selection for Teacher
        function showDetailWithPeriod(mode, type) {
            const modeData = teacherModes[mode];
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('modalContent');
            
            // 시기 선택 UI 생성
            content.innerHTML = `
                <h2 style="margin-bottom: 20px;">
                    <span style="font-size: 48px; margin-right: 15px;">${modeData.icon}</span>
                    ${modeData.title}
                </h2>
                
                <!-- 시기 선택 섹션 -->
                <div class="info-box" style="background: linear-gradient(135deg, rgba(96,165,250,0.1), rgba(167,139,250,0.1)); border: 1px solid rgba(96,165,250,0.3); padding: 25px;">
                    <h3 style="color: #60a5fa; margin-bottom: 20px; font-size: 22px; display: flex; align-items: center; gap: 10px;">
                        <span>📅</span>
                        <span>시기별 실행 포인트</span>
                        <span style="font-size: 14px; color: rgba(255,255,255,0.6); margin-left: auto;">원하는 시기를 선택하세요</span>
                    </h3>
                    <div class="period-selector" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px;">
                        <button class="period-btn active" data-period="vacation" onclick="selectPeriod('${mode}', 'vacation', this)">
                            🏖️ 방학
                        </button>
                        <button class="period-btn" data-period="exam2months" onclick="selectPeriod('${mode}', 'exam2months', this)">
                            📅 시험 2개월 전
                        </button>
                        <button class="period-btn" data-period="exam1month" onclick="selectPeriod('${mode}', 'exam1month', this)">
                            📆 시험 1개월 전
                        </button>
                        <button class="period-btn" data-period="exam2weeks" onclick="selectPeriod('${mode}', 'exam2weeks', this)">
                            ⏰ 시험 2주 전
                        </button>
                        <button class="period-btn" data-period="exam1week" onclick="selectPeriod('${mode}', 'exam1week', this)">
                            🚨 시험 1주 전
                        </button>
                        <button class="period-btn" data-period="exam3days" onclick="selectPeriod('${mode}', 'exam3days', this)">
                            🔥 시험 3일 전
                        </button>
                        <button class="period-btn" data-period="exam1day" onclick="selectPeriod('${mode}', 'exam1day', this)">
                            💯 시험 1일 전
                        </button>
                        <button class="period-btn" data-period="noExam" onclick="selectPeriod('${mode}', 'noExam', this)">
                            📖 시험 없음
                        </button>
                    </div>
                    <div id="periodExecutionPoints" style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; transition: opacity 0.3s ease;">
                        <h3 style="color: #60a5fa; margin-bottom: 15px;">🌴 방학 기간</h3>
                        <ul class="execution-list">
                            ${periodExecutionPoints.vacation[mode].map(point => `<li>${point}</li>`).join('')}
                        </ul>
                    </div>
                </div>
                
                <!-- 기본 정보 섹션들 -->
                <div class="info-box">
                    <h3>대상</h3>
                    <p>${modeData.target}</p>
                </div>
                ${modeData.mbti ? `
                <div class="info-box">
                    <h3>🧩 MBTI 매칭</h3>
                    <p style="font-size: 14px; line-height: 1.6;">
                        ${modeData.mbti.map(type => {
                            const desc = mbtiDescriptions[type];
                            return `<strong>${type}</strong> - ${desc || type}`;
                        }).join('<br>')}
                    </p>
                </div>
                ` : ''}
                <div class="info-box">
                    <h3>설명</h3>
                    <p>${modeData.description}</p>
                </div>
                <div class="info-box warning">
                    <h3>💡 현실조언</h3>
                    <p style="font-weight: bold;">${modeData.heavyMessage}</p>
                </div>
                <div class="info-box">
                    <h3>🤖 Mathking 활용법</h3>
                    <p>${modeData.mathking}</p>
                </div>
                <div class="info-box">
                    <h3>📊 관리 포인트</h3>
                    <p>${modeData.management}</p>
                </div>
            `;
            
            modal.classList.add('show');
        }
        
        // Period Selection Function
        function selectPeriod(mode, period, button) {
            // Update active button
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            // Update execution points with animation
            const pointsContainer = document.getElementById('periodExecutionPoints');
            const points = periodExecutionPoints[period][mode];
            
            // Period titles for better context
            const periodTitles = {
                vacation: '🌴 방학 기간',
                exam2months: '📅 시험 2개월 전',
                exam1month: '📆 시험 1개월 전',
                exam2weeks: '⏰ 시험 2주 전',
                exam1week: '🚨 시험 1주 전',
                exam3days: '🔥 시험 3일 전',
                exam1day: '💯 시험 1일 전',
                noExam: '📖 시험 없음'
            };
            
            // Add fade effect
            pointsContainer.style.opacity = '0';
            
            setTimeout(() => {
                pointsContainer.innerHTML = `
                    <h3 style="color: #60a5fa; margin-bottom: 15px;">${periodTitles[period]}</h3>
                    <ul class="execution-list">
                        ${points.map(point => `<li>${point}</li>`).join('')}
                    </ul>
                `;
                pointsContainer.style.opacity = '1';
            }, 150);
        }
        
        // Initialize on load
        window.onload = init;
    </script>
</body>
</html>