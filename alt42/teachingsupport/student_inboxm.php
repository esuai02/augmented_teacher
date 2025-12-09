<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get parameters - URL에 studentid 또는 id가 없으면 로그인 사용자 정보 사용
$studentid = null;
if(isset($_GET['studentid']) && !empty($_GET['studentid'])) {
    $studentid = intval($_GET['studentid']);
} elseif(isset($_GET['id']) && !empty($_GET['id'])) {
    $studentid = intval($_GET['id']);
} else {
    $studentid = $USER->id;
}

// Check permissions
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid=? AND fieldid=?", array($USER->id, 22)); 
$role = $userrole->data;

if($USER->id != $studentid && $role === 'student') {
    echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
    exit;
}

// Get student info
$student = $DB->get_record('user', array('id' => $studentid));
if (!$student) {
    echo "학생 정보를 찾을 수 없습니다.";
    exit;
}
$studentname = $student->firstname . $student->lastname;

// Get messages statistics
$stats = new stdClass();
$stats->total_messages = 0;
$stats->unread_messages = 0;

if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    $sql = "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
            WHERE userid = :studentid 
            AND status = 'completed' 
            AND solution_text IS NOT NULL";
    $stats->total_messages = $DB->count_records_sql($sql, array('studentid' => $studentid));
    
    // Check for read status table
    if ($DB->get_manager()->table_exists('ktm_interaction_read_status')) {
        $sql_read = "SELECT COUNT(DISTINCT ti.id) 
                     FROM {ktm_teaching_interactions} ti
                     JOIN {ktm_interaction_read_status} rs ON ti.id = rs.interaction_id
                     WHERE ti.userid = :studentid 
                     AND ti.status = 'completed' 
                     AND ti.solution_text IS NOT NULL
                     AND rs.is_read = 1";
        $read_count = $DB->count_records_sql($sql_read, array('studentid' => $studentid));
        $stats->unread_messages = $stats->total_messages - $read_count;
    } else {
        $stats->unread_messages = $stats->total_messages;
    }
}

// Get recent messages
$messages = array();
if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    $sql = "SELECT * FROM {ktm_teaching_interactions} 
            WHERE userid = :studentid 
            AND status = 'completed' 
            AND solution_text IS NOT NULL
            ORDER BY timecreated DESC LIMIT 20";
    $messages = $DB->get_records_sql($sql, array('studentid' => $studentid));
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>📬 나의 메세지함</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        .header {
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            color: white;
            padding: 16px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 { font-size: 18px; font-weight: 600; }
        .content { padding: 16px; }
        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            flex: 1;
            background: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-icon { font-size: 24px; margin-bottom: 8px; }
        .stat-value { font-size: 20px; font-weight: 700; color: #333; }
        .stat-label { font-size: 11px; color: #666; margin-top: 4px; }
        .card {
            background: white;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .message-item {
            padding: 16px;
            border-bottom: 1px solid #eee;
        }
        .message-item:last-child { border-bottom: none; }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .message-type {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            background: #dbeafe;
            color: #2563eb;
            font-weight: 600;
        }
        .message-time {
            font-size: 11px;
            color: #999;
        }
        .message-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            line-height: 1.4;
        }
        .message-preview {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .message-unread {
            border-left: 4px solid #8b5cf6;
        }
        .empty-state {
            text-align: center;
            padding: 48px 16px;
            color: #999;
        }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state-text { font-size: 14px; }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 10px;
            padding: 4px 8px;
        }
        .nav-item.active { color: #8b5cf6; }
        .nav-item span { font-size: 20px; margin-bottom: 2px; }
        
        /* 세로 방향 (Portrait) */
        @media screen and (orientation: portrait) {
            .stats-row {
                flex-direction: column;
                gap: 12px;
            }
            .stat-card {
                padding: 20px;
            }
            .stat-icon { font-size: 28px; }
            .stat-value { font-size: 24px; }
            .stat-label { font-size: 13px; }
            .header h1 { font-size: 20px; }
            .message-item { padding: 20px; }
            .message-title { font-size: 16px; margin-bottom: 8px; }
            .message-preview { font-size: 14px; -webkit-line-clamp: 3; }
            .message-type { font-size: 13px; padding: 5px 10px; }
            .message-time { font-size: 12px; }
        }
        
        /* 가로 방향 (Landscape) */
        @media screen and (orientation: landscape) {
            .stats-row { flex-direction: row; gap: 10px; }
            .stat-card { padding: 12px; }
            .stat-icon { font-size: 20px; margin-bottom: 4px; }
            .stat-value { font-size: 18px; }
            .content { padding: 12px; }
            .message-item { padding: 14px; }
            body { padding-bottom: 60px; }
            .bottom-nav { padding: 6px 0; }
            .nav-item { font-size: 9px; padding: 2px 6px; }
            .nav-item span { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📬 <?php echo $studentname; ?>님의 메세지함</h1>
    </div>
    
    <div class="content">
        <!-- 통계 -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">📩</div>
                <div class="stat-value"><?php echo $stats->total_messages; ?></div>
                <div class="stat-label">전체 메세지</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔔</div>
                <div class="stat-value"><?php echo $stats->unread_messages; ?></div>
                <div class="stat-label">읽지 않음</div>
            </div>
        </div>
        
        <!-- 메세지 목록 -->
        <?php if(count($messages) > 0): ?>
            <div class="card">
            <?php foreach($messages as $msg): 
                $preview = mb_substr(strip_tags($msg->solution_text ?: ''), 0, 80, 'UTF-8');
                $title = $msg->question_text ? mb_substr($msg->question_text, 0, 40, 'UTF-8') : '풀이 피드백';
            ?>
                <div class="message-item">
                    <div class="message-header">
                        <span class="message-type"><?php echo $msg->interaction_type ?: '피드백'; ?></span>
                        <span class="message-time"><?php echo date('m/d H:i', $msg->timecreated); ?></span>
                    </div>
                    <div class="message-title"><?php echo htmlspecialchars($title); ?></div>
                    <div class="message-preview"><?php echo htmlspecialchars($preview); ?>...</div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-text">아직 받은 메세지가 없습니다.<br>학습을 하면 선생님의 피드백이 도착합니다!</div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 하단 네비게이션 -->
    <div class="bottom-nav">
        <a href="../../students/index42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>🏠</span>홈
        </a>
        <a href="../../students/today42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>📝</span>오늘
        </a>
        <a href="../../students/schedule42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>📅</span>일정
        </a>
        <a href="../../students/goals42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>🎯</span>목표
        </a>
        <a href="student_inboxm.php?studentid=<?php echo $studentid; ?>" class="nav-item active">
            <span>📩</span>메세지
        </a>
        <a href="AItutor/ui/math-persona-systemm.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>🤖</span>AI
        </a>
    </div>
</body>
</html>

