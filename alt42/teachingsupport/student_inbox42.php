<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? $USER->id;
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;

// 학생 정보 가져오기
$student = $DB->get_record('user', array('id' => $studentid));
if (!$student) {
    print_error('학생 정보를 찾을 수 없습니다.');
} 

// 권한 확인 (본인이거나 관리자)
$context = context_system::instance();
//if ($studentid != $USER->id && !has_capability('moodle/site:config', $context)) {
//    print_error('접근 권한이 없습니다.');
//}

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

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📬 나의 풀이 메시지함</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
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
            padding: 0 20px;
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
            transform: translateY(-1px);
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
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
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

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-item {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .message-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .message-item.unread {
            background: #ebf8ff;
            border-color: #90cdf4;
        }

        .message-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #4299e1;
            border-radius: 2px 0 0 2px;
        }

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



        /* 모달 스타일 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            display: flex;
            flex: 1;
            overflow: hidden;
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

        .audio-controls {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        }

        .play-pause-btn {
            background: #4299e1;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .play-pause-btn:hover {
            background: #3182ce;
            transform: scale(1.1);
        }

        .progress-container {
            flex: 1;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            cursor: pointer;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: #4299e1;
            border-radius: 3px;
            transition: width 0.1s;
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            right: -6px;
            top: -3px;
            width: 12px;
            height: 12px;
            background: #4299e1;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .time-display {
            color: #718096;
            font-size: 14px;
            min-width: 100px;
            text-align: center;
        }

        .speed-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .speed-btn {
            background: #edf2f7;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .speed-btn:hover {
            background: #e2e8f0;
        }

        .speed-btn.active {
            background: #4299e1;
            color: white;
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
        
        .tab-btn {
            transition: all 0.3s ease;
        }
        
        .tab-btn:hover {
            background: #f3f4f6 !important;
        }

        @media (max-width: 768px) {
            .container {
                margin: 0;
                border-radius: 0;
            }

            .dashboard {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

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
    </style>
</head>
<body>
    <div class="nav-top">
        <div class="content-container">
            <div class="nav-controls">
                <div class="header-nav">
                    <a href="../../students/index42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                        <i class="fas fa-home"></i> 내공부방
                    </a>
                    <a href="../../students/today42.php?id=<?php echo $studentid; ?>&tb=604800" class="nav-btn">
                        <i class="fas fa-chart-bar"></i> 공부결과
                    </a>
                    <a href="student_inbox42.php?studentid=<?php echo $studentid; ?>" class="nav-btn active">
                        <i class="fas fa-envelope"></i> 메세지함
                    </a>
                    <a href="../../students/goals42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                        <i class="fas fa-target"></i> 목표설정
                    </a>
                    <a href="../../students/schedule42.php?id=<?php echo $studentid; ?>&eid=1&nweek=12" class="nav-btn">
                        <i class="fas fa-clock"></i> 수업시간
                    </a>
                    <a href="../../teachers/timescaffolding42.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                        <i class="fas fa-book-open"></i> 수학일기
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div style="padding: 20px;">
    <div class="container">
        <div class="header">
            <h1>
                <span>📬</span>
                나의 풀이 메시지함
            </h1>
            <p><?php echo fullname($student); ?>님의 개인 메시지함</p>
        </div>
        

        <div class="dashboard">
            <!-- 탭 메뉴 -->
            <div style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0;">
                <button id="receivedTab" class="tab-btn active" onclick="switchTab('received')" style="padding: 12px 24px; background: none; border: none; font-size: 16px; cursor: pointer; border-bottom: 3px solid #4299e1; color: #4299e1; font-weight: bold;">
                    📬 받은 메시지함
                </button>
                <button id="sentTab" class="tab-btn" onclick="switchTab('sent')" style="padding: 12px 24px; background: none; border: none; font-size: 16px; cursor: pointer; color: #718096;">
                    📤 보낸 메시지함
                </button>
            </div>
            
            <!-- 통계 카드 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📨</div>
                    <div class="stat-number" id="totalMessagesCount"><?php echo $stats->total_messages; ?></div>
                    <div class="stat-label" id="totalMessagesLabel">전체 메시지</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔵</div>
                    <div class="stat-number"><?php echo $stats->unread_messages; ?></div>
                    <div class="stat-label">읽지 않음</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $stats->read_messages; ?></div>
                    <div class="stat-label">읽음</div>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button class="refresh-btn" onclick="location.reload()">
                    🔄 새로고침
                </button>
                <button class="refresh-btn" style="background: #e74c3c;" onclick="showRequestForm()">
                    📤 풀이 요청하기
                </button>
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

            <!-- 메시지 목록 -->
            <div class="message-list">
                <h2 class="section-title" id="messageListTitle">
                    <span>📬</span>
                    받은 메시지
                </h2>
                <div id="messageList">
                    <!-- 메시지 목록이 여기에 동적으로 생성됩니다 -->
                </div>
            </div>

        </div>
    </div>

    <!-- 강의 재생 모달 -->
    <div class="modal-overlay" id="lectureModal">
        <div class="modal-content" style="display: flex; flex-direction: column; height: 90vh;">
            <div class="modal-header">
                <h2 class="modal-title">📚 문제 해설 강의</h2>
                <button class="modal-close" onclick="closeLectureModal()">&times;</button>
            </div>
            <div class="modal-body" style="display: flex; flex: 1; overflow: hidden; height: calc(100% - 80px);">
                <div class="problem-section" style="flex: 0 0 40%; min-width: 350px; padding: 30px; background: #f8fafc; border-right: 1px solid #e2e8f0; overflow-y: auto; display: block;">
                    <h3 style="margin-bottom: 20px; color: #2d3748;">문제</h3>
                    <img id="modalProblemImage" class="problem-image" src="" alt="문제 이미지" style="display: none; width: 100%; height: auto; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <div id="modalProblemText" style="font-size: 16px; line-height: 1.6;"></div>
                </div>
                <div class="solution-section" style="flex: 1; padding: 30px; overflow-y: auto; position: relative; display: flex; flex-direction: column;">
                    <h3 style="margin-bottom: 20px; color: #2d3748;">해설</h3>
                    <div id="solutionContent" class="solution-content" style="flex: 1; overflow-y: auto;"></div>
                    <div class="audio-controls" style="position: sticky; bottom: 0; background: white; border-top: 1px solid #e2e8f0; padding: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 -4px 12px rgba(0,0,0,0.05); margin: -30px -30px 0 -30px;">
                        <button class="play-pause-btn" id="playPauseBtn" onclick="togglePlayPause()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path id="playIcon" d="M8 5v14l11-7z"/>
                                <path id="pauseIcon" d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" style="display: none;"/>
                            </svg>
                        </button>
                        <div class="progress-container" onclick="seekAudio(event)">
                            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
                        </div>
                        <div class="time-display">
                            <span id="currentTime">0:00</span> / <span id="totalTime">0:00</span>
                        </div>
                        <div class="speed-control">
                            <button class="speed-btn" onclick="setSpeed(0.75)">0.75x</button>
                            <button class="speed-btn active" onclick="setSpeed(1)">1x</button>
                            <button class="speed-btn" onclick="setSpeed(1.25)">1.25x</button>
                            <button class="speed-btn" onclick="setSpeed(1.5)">1.5x</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <audio id="modalAudioPlayer" style="display: none;"></audio>
    
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
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        const studentId = <?php echo $studentid; ?>;
        const teacherId = <?php echo $_GET['userid'] ?? 0; ?>;
        let currentTab = 'received'; // 현재 선택된 탭

        // 페이지 로드 시 메시지 목록 가져오기
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            loadMessages();
            
            // 폼이 제대로 존재하는지 확인
            const form = document.getElementById('requestForm');
            if (form) {
                console.log('Form found:', form);
            } else {
                console.error('Form not found!');
            }
        });
        
        // 탭 전환 함수
        function switchTab(tab) {
            currentTab = tab;
            
            // 탭 버튼 스타일 업데이트
            const receivedTab = document.getElementById('receivedTab');
            const sentTab = document.getElementById('sentTab');
            
            if (tab === 'received') {
                receivedTab.style.borderBottom = '3px solid #4299e1';
                receivedTab.style.color = '#4299e1';
                receivedTab.style.fontWeight = 'bold';
                sentTab.style.borderBottom = 'none';
                sentTab.style.color = '#718096';
                sentTab.style.fontWeight = 'normal';
                
                // 제목 변경
                document.getElementById('messageListTitle').innerHTML = '<span>📬</span> 받은 메시지';
                
                // 풀이 요청 버튼 보이기
                const requestFormBtn = document.querySelector('[onclick="showRequestForm()"]');
                if (requestFormBtn) requestFormBtn.style.display = 'block';
            } else {
                sentTab.style.borderBottom = '3px solid #4299e1';
                sentTab.style.color = '#4299e1';
                sentTab.style.fontWeight = 'bold';
                receivedTab.style.borderBottom = 'none';
                receivedTab.style.color = '#718096';
                receivedTab.style.fontWeight = 'normal';
                
                // 제목 변경
                document.getElementById('messageListTitle').innerHTML = '<span>📤</span> 보낸 메시지';
                
                // 풀이 요청 버튼 숨기기
                const requestFormBtn = document.querySelector('[onclick="showRequestForm()"]');
                if (requestFormBtn) requestFormBtn.style.display = 'none';
                
                // 요청 폼 숨기기
                const requestForm = document.getElementById('requestFormContainer');
                if (requestForm) requestForm.style.display = 'none';
            }
            
            // 메시지 다시 로드
            if (tab === 'received') {
                loadMessages();
            } else {
                loadSentMessages();
            }
        }

        // 메시지 목록 로드
        async function loadMessages() {
            try {
                const response = await fetch(`get_student_messages.php?studentid=${studentId}&page=0&perpage=10`);
                const data = await response.json();
                
                if (data.success) {
                    displayMessages(data.messages);
                    updateStats(data.messages.length, '받은');
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
                    updateStats(data.requests.length, '보낸');
                } else {
                    showError(data.error);
                }
            } catch (error) {
                console.error('Error loading sent messages:', error);
                showError('보낸 메시지를 불러오는 중 오류가 발생했습니다.');
            }
        }
        
        // 통계 업데이트
        function updateStats(count, type) {
            document.getElementById('totalMessagesCount').textContent = count;
            document.getElementById('totalMessagesLabel').textContent = type + ' 메시지';
        }

        // 메시지 목록 표시
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
                    <div class="message-header">
                        <div class="teacher-info">
                            <div class="teacher-avatar">
                                ${message.teacher_name.charAt(0)}
                            </div>
                            <div>
                                <div class="teacher-name">${message.teacher_name}</div>
                                <div class="teacher-role">담당 선생님</div>
                            </div>
                        </div>
                        <div class="message-meta">
                            <div class="message-time">${formatTime(message.timecreated)}</div>
                            <div class="message-type">문제 해설</div>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        <div class="message-preview" id="preview-${message.id}">
                            ${message.fullmessage.replace(/\\n/g, '<br>')}
                        </div>
                        <button class="expand-btn" onclick="toggleMessage(${message.id})">
                            <span id="expand-text-${message.id}">전체 보기</span>
                        </button>
                    </div>
                    
                    <div class="message-actions">
                        <button class="action-btn btn-primary" onclick="openLectureModal(${message.interaction_id})">
                            📖 강의 보기
                        </button>
                        <button class="action-btn btn-secondary" onclick="showReRequestModal(${message.interaction_id})">
                            🔄 재요청
                        </button>
                        ${message.is_read ? 
                            `<button class="action-btn btn-success" disabled>
                                ✅ 읽음
                            </button>` : 
                            `<button class="action-btn btn-secondary" onclick="markAsRead(${message.id})">
                                ✅ 읽음 표시
                            </button>`
                        }
                    </div>
                </div>
            `).join('');
        }

        // 메시지 펼치기/접기
        function toggleMessage(messageId) {
            const preview = document.getElementById(`preview-${messageId}`);
            const expandText = document.getElementById(`expand-text-${messageId}`);
            
            if (preview.classList.contains('expanded')) {
                preview.classList.remove('expanded');
                expandText.textContent = '전체 보기';
            } else {
                preview.classList.add('expanded');
                expandText.textContent = '접기';
            }
        }

        // 읽음 표시
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
                        messageItem.classList.remove('unread');
                    }
                    
                    // 버튼 상태 변경
                    const button = event.target;
                    button.textContent = '✅ 읽음';
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-success');
                    button.disabled = true;
                    
                    // 통계 업데이트를 위한 새로고침 (3초 후)
                    setTimeout(() => location.reload(), 3000);
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
        
        // 보낸 메시지 표시
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
                    if (request.problemImage.startsWith('http') || request.problemImage.startsWith('data:')) {
                        imageUrl = request.problemImage;
                    } else if (request.problemImage.startsWith('images/')) {
                        imageUrl = request.problemImage;
                    } else {
                        imageUrl = 'images/' + request.problemImage;
                    }
                }
                
                return `
                <div class="message-item sent-message" id="request-${request.id}">
                    <div class="message-header">
                        <div class="teacher-info">
                            <div class="teacher-avatar" style="background: ${request.statusColor};">
                                ${request.statusLabel.charAt(0)}
                            </div>
                            <div>
                                <div class="teacher-name">선생님: ${request.teacherName}</div>
                                <div class="teacher-role">상태: ${request.statusLabel}</div>
                            </div>
                        </div>
                        <div class="message-meta">
                            <div class="message-time">${request.timeAgo}</div>
                            <div class="message-type">${request.problemType || '일반'}</div>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        ${imageUrl ? `
                            <div style="margin: 10px 0;">
                                <img src="${imageUrl}" alt="문제 이미지" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                        ` : ''}
                        ${request.modificationPrompt ? `
                            <div style="margin: 10px 0; padding: 10px; background: #f3f4f6; border-radius: 8px;">
                                <strong>추가 요청:</strong> ${request.modificationPrompt}
                            </div>
                        ` : ''}
                        <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                            <span style="padding: 4px 12px; background: ${request.statusColor}20; color: ${request.statusColor}; border-radius: 20px; font-size: 14px; font-weight: 500;">
                                ${request.statusLabel}
                            </span>
                            ${request.hasSolution ? '<span style="color: #10b981;">✅ 해설 완료</span>' : ''}
                            ${request.hasAudio ? '<span style="color: #3b82f6;">🔊 음성 완료</span>' : ''}
                        </div>
                    </div>
                    
                    <div class="message-actions">
                        ${request.status === 'sent' || request.status === 'completed' ? `
                            <button class="action-btn btn-primary" onclick="openLectureModal(${request.id})">
                                📖 해설 보기
                            </button>
                        ` : ''}
                        ${request.status === 'pending' || request.status === 'processing' ? `
                            <button class="action-btn btn-secondary" disabled>
                                ⏳ 처리 중...
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            }).join('');
        }

        // 강의 모달 관련 변수
        let audioPlayer = null;
        let dialogueLines = [];
        let currentLineIndex = 0;
        let isPlaying = false;
        let syncTimer = null;

        // 강의 모달 열기
        async function openLectureModal(interactionId) {
            const modal = document.getElementById('lectureModal');
            modal.classList.add('active');
            
            // 오디오 플레이어 초기화
            audioPlayer = document.getElementById('modalAudioPlayer');
            
            // 초기화
            document.getElementById('modalProblemImage').style.display = 'none';
            document.getElementById('modalProblemText').innerHTML = '문제를 불러오는 중...';
            document.getElementById('solutionContent').innerHTML = '해설을 불러오는 중...';
            
            // 자동으로 읽음 처리
            markAsRead(interactionId);
            
            // 데이터 로드
            try {
                const response = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction`);
                const data = await response.json();
                
                console.log('Loaded data:', data); // 디버깅용
                
                if (data.success) {
                    // 문제 이미지 표시
                    if (data.problemImage) {
                        const problemImage = document.getElementById('modalProblemImage');
                        let fullImageUrl;
                        
                        // 이미지 경로 처리
                        if (data.problemImage.startsWith('http')) {
                            fullImageUrl = data.problemImage;
                        } else if (data.problemImage.startsWith('data:')) {
                            // base64 이미지인 경우
                            fullImageUrl = data.problemImage;
                        } else if (data.problemImage.startsWith('images/')) {
                            // images 폴더 상대경로
                            fullImageUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' + data.problemImage;
                        } else {
                            // 파일명만 있는 경우
                            fullImageUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/images/' + data.problemImage;
                        }
                        
                        problemImage.src = fullImageUrl;
                        problemImage.style.display = 'block';
                        problemImage.onerror = function() {
                            console.error('Failed to load image:', fullImageUrl);
                            this.style.display = 'none';
                        };
                    }
                    
                    // 문제 텍스트 표시
                    if (data.problemText) {
                        document.getElementById('modalProblemText').innerHTML = data.problemText;
                        // MathJax 렌더링
                        if (window.MathJax) {
                            window.MathJax.typesetPromise([document.getElementById('modalProblemText')]);
                        }
                    } else {
                        document.getElementById('modalProblemText').innerHTML = '<em>문제 텍스트가 없습니다.</em>';
                    }
                    
                    // 대화 파싱 및 표시
                    // DB에서 가져온 HTML 해설 내용 사용
                    const solutionHtml = data.solutionText || data.narrationText || '';
                    if (solutionHtml) {
                        parseDialogue(solutionHtml);
                        // MathJax 렌더링 (수식이 포함된 경우)
                        if (window.MathJax) {
                            setTimeout(() => {
                                window.MathJax.typesetPromise([document.getElementById('solutionContent')]);
                            }, 100);
                        }
                    } else {
                        document.getElementById('solutionContent').innerHTML = '<em>해설이 없습니다.</em>';
                    }
                    
                    // 오디오 설정
                    if (data.audioUrl) {
                        // 전체 URL 경로 구성
                        const fullAudioUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' + data.audioUrl;
                        audioPlayer.src = fullAudioUrl;
                        audioPlayer.addEventListener('loadedmetadata', () => {
                            document.getElementById('totalTime').textContent = formatTime(audioPlayer.duration);
                        });
                        audioPlayer.addEventListener('timeupdate', updateProgress);
                        audioPlayer.addEventListener('ended', onAudioEnded);
                    }
                } else {
                    console.error('Failed to load interaction data:', data.error);
                    document.getElementById('modalProblemText').innerHTML = '<em style="color: red;">데이터를 불러오는데 실패했습니다.</em>';
                    document.getElementById('solutionContent').innerHTML = '<em style="color: red;">' + (data.error || '오류가 발생했습니다.') + '</em>';
                }
            } catch (error) {
                console.error('Error loading interaction:', error);
                document.getElementById('modalProblemText').innerHTML = '<em style="color: red;">오류가 발생했습니다.</em>';
                document.getElementById('solutionContent').innerHTML = '<em style="color: red;">서버 연결 오류</em>';
            }
        }

        // 모달 닫기
        function closeLectureModal() {
            const modal = document.getElementById('lectureModal');
            modal.classList.remove('active');
            
            // 오디오 정지
            if (audioPlayer) {
                audioPlayer.pause();
                audioPlayer.currentTime = 0;
            }
            
            // 타이머 클리어
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
            
            // 초기화
            isPlaying = false;
            currentLineIndex = 0;
            dialogueLines = [];
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

        // 재생/일시정지 토글
        function togglePlayPause() {
            if (isPlaying) {
                pauseAudio();
            } else {
                playAudio();
            }
        }

        // 오디오 재생
        function playAudio() {
            if (!audioPlayer) return;
            
            audioPlayer.play();
            isPlaying = true;
            
            // 아이콘 변경
            document.getElementById('playIcon').style.display = 'none';
            document.getElementById('pauseIcon').style.display = 'block';
            
            // 텍스트 싱크 시작
            startTextSync();
        }

        // 오디오 일시정지
        function pauseAudio() {
            if (!audioPlayer) return;
            
            audioPlayer.pause();
            isPlaying = false;
            
            // 아이콘 변경
            document.getElementById('playIcon').style.display = 'block';
            document.getElementById('pauseIcon').style.display = 'none';
            
            // 텍스트 싱크 중지
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
        }

        // 텍스트 싱크 시작
        function startTextSync() {
            if (!dialogueLines.length || !audioPlayer.duration) return;
            
            const totalDuration = audioPlayer.duration;
            
            // 각 라인의 누적 시간 계산
            let cumulativeTime = 0;
            const lineTimings = dialogueLines.map((line, index) => {
                const start = cumulativeTime;
                const duration = line.duration || (totalDuration / dialogueLines.length);
                cumulativeTime += duration;
                return { start, end: cumulativeTime };
            });
            
            // 전체 시간에 맞게 조정
            const scaleFactor = totalDuration / cumulativeTime;
            lineTimings.forEach(timing => {
                timing.start *= scaleFactor;
                timing.end *= scaleFactor;
            });
            
            // 현재 재생 위치에 맞는 라인 찾기
            const currentTime = audioPlayer.currentTime;
            currentLineIndex = 0;
            for (let i = 0; i < lineTimings.length; i++) {
                if (currentTime >= lineTimings[i].start) {
                    dialogueLines[i].element.classList.add('visible');
                    currentLineIndex = i;
                } else {
                    break;
                }
            }
            
            // 싱크 타이머 시작
            syncTimer = setInterval(() => {
                const currentTime = audioPlayer.currentTime;
                
                while (currentLineIndex < dialogueLines.length && 
                       currentTime >= lineTimings[currentLineIndex].start) {
                    const line = dialogueLines[currentLineIndex];
                    line.element.classList.add('visible');
                    
                    // 부드러운 스크롤 (현재 라인을 뷰포트 중앙으로)
                    const container = line.element.parentElement;
                    const containerRect = container.getBoundingClientRect();
                    const lineRect = line.element.getBoundingClientRect();
                    const scrollTop = container.scrollTop;
                    const targetScroll = scrollTop + (lineRect.top - containerRect.top) - (containerRect.height / 2) + (lineRect.height / 2);
                    
                    container.scrollTo({
                        top: targetScroll,
                        behavior: 'smooth'
                    });
                    
                    currentLineIndex++;
                }
            }, 50); // 더 자주 체크하여 부드러운 싱크
        }

        // 진행률 업데이트
        function updateProgress() {
            if (!audioPlayer || !audioPlayer.duration) return;
            
            const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentTime').textContent = formatTime(audioPlayer.currentTime);
        }

        // 오디오 종료 시
        function onAudioEnded() {
            isPlaying = false;
            document.getElementById('playIcon').style.display = 'block';
            document.getElementById('pauseIcon').style.display = 'none';
            
            // 모든 텍스트 표시
            dialogueLines.forEach(line => line.element.classList.add('visible'));
            
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
        }

        // 시크 기능
        function seekAudio(event) {
            if (!audioPlayer || !audioPlayer.duration) return;
            
            const progressContainer = event.currentTarget;
            const clickX = event.offsetX;
            const width = progressContainer.offsetWidth;
            const percentage = clickX / width;
            
            audioPlayer.currentTime = percentage * audioPlayer.duration;
            
            // 텍스트 싱크 재조정
            if (isPlaying) {
                if (syncTimer) clearInterval(syncTimer);
                startTextSync();
            }
        }

        // 재생 속도 설정
        function setSpeed(speed) {
            if (!audioPlayer) return;
            
            audioPlayer.playbackRate = speed;
            
            // 버튼 활성화 상태 변경
            document.querySelectorAll('.speed-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // 시간 포맷
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
        
        // 풀이 요청 폼 표시/숨기기
        function showRequestForm() {
            const section = document.getElementById('requestSection');
            section.style.display = 'block';
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
                        
                        // 메시지 목록 새로고침
                        setTimeout(loadMessages, 1000);
                        
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
                    
                    // 메시지 목록 새로고침
                    setTimeout(loadMessages, 1000);
                } else {
                    alert('재요청 전송에 실패했습니다: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('Error submitting re-request:', error);
                alert('재요청 전송 중 오류가 발생했습니다.');
            }
        }
        
    </script>
    </div>
</body>
</html>

