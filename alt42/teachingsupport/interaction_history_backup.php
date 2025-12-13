<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$userid = $_GET["userid"];
$studentid = $_GET["studentid"];

$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid=? AND fieldid=?", array($USER->id, 22)); 
$role=$userrole->data;

// 선생님 정보 가져오기
$teacher = $DB->get_record('user', array('id' => $userid));
if (!$teacher) {
    print_error('선생님 정보를 찾을 수 없습니다.');
}

// 권한 확인
$current_user = $DB->get_record('user', array('id' => $USER->id));
if (!$current_user) {
    print_error('사용자 정보를 찾을 수 없습니다.');
}

// teacherid 필드가 있는 경우 검증
if (isset($current_user->teacherid) && !empty($current_user->teacherid)) {
    if ($current_user->teacherid != $userid) {
        print_error('접근 권한이 없습니다.');
    }
}

// 학생 정보 가져오기 (선택사항)
$student = null;
if ($studentid) {
    $student = $DB->get_record('user', array('id' => $studentid));
        
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 교수학습 상호작용 히스토리 - <?php echo $student ? fullname($student) : '전체 학생'; ?> (담당: <?php echo fullname($teacher); ?>)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }

        .history-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state h3 {
            color: #374151;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .header {
            background: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #6b7280;
        }

        .user-info strong {
            color: #1f2937;
        }

        .refresh-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .refresh-btn:hover {
            background: #2563eb;
        }

        .inbox-link-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: background 0.3s ease;
            margin-left: 4px;
        }

        .inbox-link-btn:hover {
            background: #059669;
            color: white;
            text-decoration: none;
        }

        .event-log {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .message-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 12px;
            border-left: 3px solid #e5e7eb;
            transition: all 0.2s;
        }

        .message-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .message-item.received-message {
            border-left-color: #3b82f6;
        }

        .message-item.unread {
            background: #fefefe;
            box-shadow: 0 2px 12px rgba(59, 130, 246, 0.1);
        }
        
        .problem-thumbnail {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
            flex-shrink: 0;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 16px;
        }

        .problem-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .message-content-compact {
            flex: 1;
            min-width: 0;
        }
        
        .message-text {
            font-size: 14px;
            color: #374151;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-text.unread {
            font-weight: 600;
            color: #1f2937;
        }
        
        .student-name-link {
            font-weight: 600;
            color: #3b82f6;
            text-decoration: none;
            margin-right: 8px;
        }
        
        .student-name-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        
        .action-btn-compact {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        
        .action-btn-compact.btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .action-btn-compact.btn-primary:hover {
            background: #2563eb;
            color: white;
        }

        .action-btn-compact.btn-secondary {
            background: #6b7280;
            color: white;
        }

        .action-btn-compact.btn-secondary:hover {
            background: #4b5563;
            color: white;
        }

        .action-btn-compact.btn-success {
            background: #10b981;
            color: white;
        }

        .action-btn-compact.btn-success:hover {
            background: #059669;
            color: white;
        }

        /* 상태별 색상 */
        .status-pending {
            color: #f59e0b;
            font-weight: 600;
        }

        .status-completed {
            color: #10b981;
            font-weight: 600;
        }

        .status-in_progress {
            color: #3b82f6;
            font-weight: 600;
        }

        /* Tooltip */
        .tooltip {
            position: absolute;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            pointer-events: none;
            transform: translate(-50%, -100%);
            margin-top: -8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
        }

        .tooltip img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 8px;
        }
        
        .message-time-compact {
            font-size: 12px;
            color: #9ca3af;
            min-width: 70px;
            flex-shrink: 0;
            text-align: right;
        }
        
        /* Modal Styles from student_inbox.php */
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
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 30px;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
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
        }

        .solution-section {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .solution-content {
            flex: 1;
            overflow-y: auto;
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
            margin: -30px -30px 0 -30px;
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

        .time-display {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #4a5568;
            white-space: nowrap;
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

        .dialogue-line {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f7fafc;
            border-left: 4px solid #e2e8f0;
            opacity: 0.3;
            transition: opacity 0.5s;
        }

        .dialogue-line.visible {
            opacity: 1;
            border-left-color: #4299e1;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-indicator.completed { background: #10b981; }
        .status-indicator.pending { background: #f59e0b; }
        .status-indicator.processing { background: #3b82f6; }
        .status-indicator.error { background: #ef4444; }

        .event-content {
            flex: 1;
            min-width: 0;
        }

        .event-type {
            font-weight: 500;
            color: #1f2937;
            margin-right: 8px;
        }

        .problem-badge {
            display: inline-block;
            background: #e5e7eb;
            color: #6b7280;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 12px;
        }

        .problem-badge.exam { background: #e3f2fd; color: #1976d2; }
        .problem-badge.school { background: #f3e5f5; color: #7b1fa2; }
        .problem-badge.mathking { background: #e8f5e9; color: #388e3c; }
        .problem-badge.textbook { background: #fff3e0; color: #f57c00; }

        .event-description {
            color: #4b5563;
            font-size: 14px;
            margin-top: 2px;
        }

        .event-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
            flex-shrink: 0;
            font-size: 12px;
            color: #6b7280;
        }

        .time-stamp {
            color: #9ca3af;
            white-space: nowrap;
        }

        .score-badge {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .score-badge.low { background: #fee2e2; color: #991b1b; }
        .score-badge.medium { background: #fef3c7; color: #92400e; }

        .stats-bar {
            background: #f3f4f6;
            padding: 12px 20px;
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #6b7280;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #10b981;
            font-size: 14px;
            font-weight: 500;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .filter-tabs {
            display: flex;
            gap: 1px;
            background: #e5e7eb;
            border-radius: 6px;
            padding: 2px;
            margin-bottom: 16px;
        }

        .filter-tab {
            background: transparent;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            color: #6b7280;
            transition: all 0.2s;
            text-decoration: none;
        }

        .filter-tab.active {
            background: white;
            color: #1f2937;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .pagination a {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            text-decoration: none;
            color: #4b5563;
            font-size: 14px;
        }

        .pagination a:hover {
            background: #f3f4f6;
        }

        .pagination .current {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 12px;
            color: #4b5563;
            text-decoration: none;
            margin-left: 8px;
        }

        .action-btn:hover {
            background: #e5e7eb;
        }

        .resend-btn {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 4px;
        }

        .resend-btn:hover {
            background: #d97706;
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 10px;
            }

            .user-info {
                font-size: 13px;
                flex-wrap: wrap;
                gap: 5px;
            }

            .inbox-link-btn, .refresh-btn {
                font-size: 10px;
                padding: 3px 6px;
            }

            .resend-btn {
                font-size: 10px;
                padding: 3px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">📊 교수학습 상호작용 히스토리</h1>
            <div class="user-info">
                <span>담당 선생님: <strong><?php echo fullname($teacher); ?></strong></span>
                <?php if ($student): ?>
                    <span>학생: <strong><?php echo fullname($student); ?></strong> (ID: <?php echo $studentid; ?>)</span>
                <?php else: ?>
                    <span>전체 학생 현황</span>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <button class="refresh-btn" onclick="loadInteractionHistory()">새로고침</button>
            </div>
        </div>

        <!-- 필터 탭 -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="setFilter('all')" data-filter="all">전체</button>
            <button class="filter-tab" onclick="setFilter('completed')" data-filter="completed">완료</button>
            <button class="filter-tab" onclick="setFilter('pending')" data-filter="pending">대기중</button>
            <button class="filter-tab" onclick="setFilter('in_progress')" data-filter="in_progress">진행중</button>
        </div>

        <!-- 통계 및 상호작용 목록 -->
        <div class="history-list">
            <!-- 통계 바 -->
            <div class="stats-bar" id="statsBar">
                <div class="stat-item">📊 총 상호작용: <strong id="totalCount">-</strong></div>
                <div class="stat-item">✅ 완료: <strong id="completedCount">-</strong></div>
                <div class="stat-item">⏳ 대기중: <strong id="pendingCount">-</strong></div>
                <div class="stat-item">🔄 진행중: <strong id="progressCount">-</strong></div>
            </div>
            
            <!-- 로딩 상태 -->
            <div class="loading" id="loadingIndicator">
                <div class="spinner"></div>
                <p>상호작용 히스토리를 불러오는 중...</p>
            </div>
            
            <!-- 에러 메시지 -->
            <div class="error-message" id="errorMessage" style="display: none;"></div>
            
            <!-- 상호작용 목록 -->
            <div id="interactionList"></div>
            
            <!-- 빈 상태 -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <h3>📭 상호작용 히스토리가 없습니다</h3>
                <p>학생들과의 교수학습 상호작용이 시작되면 여기에 표시됩니다.</p>
            </div>
        </div>

            <?php
            // 필터 조건 설정 (teacherid가 일치하는 상호작용만 표시)
            $where_condition = $target_userid ? "userid = ? AND teacherid = ?" : "teacherid = ?";
            $base_params = $target_userid ? array($target_userid, $userid) : array($userid);
            
            if ($filter != 'all') {
                if ($filter == 'completed' || $filter == 'pending') {
                    // interactions 테이블에서 데이터 가져오기
                    $sql = "SELECT i.*, 'interaction' as source_type 
                            FROM {ktm_teaching_interactions} i 
                            WHERE $where_condition AND i.status = ?
                            ORDER BY i.timecreated DESC";
                    $params = array_merge($base_params, array($filter));
                } else {
                    // interactions 테이블에서만 데이터 가져오기 (필터링 단순화)
                    $status_filter = $filter == 'question' ? 'pending' : $filter;
                    $sql = "SELECT i.*, 'interaction' as source_type 
                            FROM {ktm_teaching_interactions} i 
                            WHERE $where_condition
                            ORDER BY i.timecreated DESC";
                    $params = $base_params;
                }
            } else {
                // 모든 데이터 가져오기 (teacherid가 일치하는 상호작용만)
                $sql = "SELECT i.id, i.userid, i.teacherid, i.problem_type, i.status as event_type, 
                        i.solution_text as event_description, i.timecreated, i.problem_image,
                        'interaction' as source_type
                        FROM {ktm_teaching_interactions} i 
                        WHERE $where_condition
                        ORDER BY i.timecreated DESC";
                $params = $base_params;
            }
            
            // 전체 레코드 수 계산
            $total_sql = "SELECT COUNT(*) FROM ($sql) as combined";
            $totalcount = $DB->count_records_sql($total_sql, $params);
            
            // 페이지네이션 적용
            $sql .= " LIMIT " . ($page * $perpage) . ", $perpage";
            
            $records = $DB->get_records_sql($sql, $params);
            
            if ($records) {
                foreach ($records as $record) {
                    // 학생 정보 가져오기 (각 레코드마다)
                    $record_student = $DB->get_record('user', array('id' => $record->userid));
                    $student_name = $record_student ? fullname($record_student) : 'Unknown';
                    
                    // 상태 및 메시지 설정
                    $status_class = 'pending';
                    $message_text = '';
                    $problem_type_text = '';
                    
                    switch($record->event_type) {
                        case 'completed':
                            $status_class = 'completed';
                            $message_text = '완료된 해설';
                            break;
                        case 'pending':
                            $status_class = 'pending';
                            $message_text = '해설 대기중';
                            break;
                        case 'processing':
                            $status_class = 'processing';
                            $message_text = '해설 생성중';
                            break;
                        default:
                            $message_text = '문제 풀이 요청';
                    }
                    
                    // 문제 유형 텍스트
                    if (!empty($record->problem_type)) {
                        switch($record->problem_type) {
                            case 'exam': $problem_type_text = '내신 기출'; break;
                            case 'school': $problem_type_text = '학교 프린트'; break;
                            case 'mathking': $problem_type_text = 'MathKing'; break;
                            case 'textbook': $problem_type_text = '시중교재'; break;
                            default: $problem_type_text = $record->problem_type;
                        }
                        $message_text = $problem_type_text . ' ' . $message_text;
                    }
                    
                    // 시간 표시
                    $time_diff = time() - $record->timecreated;
                    if ($time_diff < 60) {
                        $time_text = '방금';
                    } elseif ($time_diff < 3600) {
                        $time_text = floor($time_diff / 60) . '분전';
                    } elseif ($time_diff < 86400) {
                        $time_text = floor($time_diff / 3600) . '시간전';
                    } else {
                        $time_text = date('m/d', $record->timecreated);
                    }
                    
                    // 문제 이미지 URL 생성
                    $image_url = '';
                    if (!empty($record->problem_image)) {
                        if (strpos($record->problem_image, 'http') === 0) {
                            $image_url = $record->problem_image;
                        } elseif (strpos($record->problem_image, 'images/') === 0) {
                            $image_url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' . $record->problem_image;
                        } else {
                            $image_url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/images/' . $record->problem_image;
                        }
                    }
                    ?>
                    <div class="message-item">
                        <!-- 문제 이미지 썸네일 -->
                        <?php if ($image_url): ?>
                            <img class="problem-thumbnail" src="<?php echo $image_url; ?>" alt="문제 이미지" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                        <?php endif; ?>
                        
                        <!-- 메시지 내용 -->
                        <div class="message-content-compact">
                            <div class="message-text">
                                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id=<?php echo $record->userid; ?>&tb=604800" 
                                   class="student-name-link" target="_blank"><?php echo $student_name; ?></a>
                                <?php echo $message_text; ?>
                            </div>
                        </div>
                        
                        <!-- 액션 버튼 -->
                        <?php if ($record->event_type == 'completed'): ?>
                            <button class="action-btn-compact btn-primary" onclick="openLectureModal(<?php echo $record->id; ?>)" title="해설 보기">
                                📚 해설보기
                            </button>
                        <?php else: ?>
                            <button class="action-btn-compact btn-primary" 
                                    onclick="acceptNewRequest(<?php echo $record->id; ?>, '<?php echo addslashes($image_url); ?>')">
                                ✅ 풀이시작
                            </button>
                        <?php endif; ?>
                        
                        <!-- 시간 -->
                        <div class="message-time-compact">
                            <?php echo $time_text; ?>
                        </div>
                        
                        <!-- 상태 인디케이터 -->
                        <div class="status-indicator <?php echo $status_class; ?>" title="<?php echo $status_class; ?>"></div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-data">표시할 데이터가 없습니다.</div>';
            }
            ?>
            
            <?php if ($totalcount > $perpage): ?>
            <div class="pagination">
                <?php
                $pagecount = ceil($totalcount / $perpage);
                
                // 이전 페이지
                if ($page > 0) {
                    echo '<a href="?userid=' . $userid . '&studentid=' . $studentid . '&filter=' . $filter . '&page=' . ($page - 1) . '">이전</a>';
                }
                
                // 페이지 번호
                for ($i = 0; $i < $pagecount; $i++) {
                    if ($i == $page) {
                        echo '<span class="current">' . ($i + 1) . '</span>';
                    } else {
                        echo '<a href="?userid=' . $userid . '&studentid=' . $studentid . '&filter=' . $filter . '&page=' . $i . '">' . ($i + 1) . '</a>';
                    }
                }
                
                // 다음 페이지
                if ($page < $pagecount - 1) {
                    echo '<a href="?userid=' . $userid . '&studentid=' . $studentid . '&filter=' . $filter . '&page=' . ($page + 1) . '">다음</a>';
                }
                ?>
            </div>
            <?php endif; ?>
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

    <script>
        // Modal functionality variables
        let audioPlayer;
        let isPlaying = false;
        let currentLineIndex = 0;
        let dialogueLines = [];
        let syncTimer = null;

        // 새로운 요청 수락하고 풀이 시작
        async function acceptNewRequest(interactionId, problemImage) {
            if (!problemImage) {
                alert('문제 이미지를 찾을 수 없습니다.');
                return;
            }
            
            // teachingagent.php로 리다이렉트
            window.open(
                'teachingagent.php?userid=<?php echo $userid; ?>&studentid=' + 
                '&interactionId=' + interactionId,
                '_blank'
            );
        }
        
        // 메시지 재발송 함수
        async function resendMessage(interactionId, studentId) {
            if (!confirm('이 해설을 학생에게 다시 전송하시겠습니까?')) {
                return;
            }
            
            try {
                // 상호작용 정보 가져오기
                const response = await fetch(`get_interaction_data.php?id=${interactionId}`);
                const data = await response.json();
                
                if (data.success) {
                    // 재발송 메시지 생성
                    const resendMessage = `📢 문제 해설 재발송
                    
선생님이 문제 해설을 다시 전송했습니다.

📚 문제 유형: ${data.problemType || '미지정'}
🎯 재발송 시간: ${new Date().toLocaleString()}
🔊 음성 설명이 포함되어 있습니다.

아래 링크를 클릭하여 상세한 설명을 확인하세요:
${window.location.origin}/moodle/local/augmented_teacher/alt42/teachingsupport/teacher_explanation_interface.php?cid=${interactionId}&ctype=interaction`;

                    // 재발송 API 호출
                    const sendResponse = await fetch('send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            studentId: studentId,
                            teacherId: <?php echo $USER->id; ?>,
                            interactionId: interactionId,
                            message: resendMessage,
                            solutionText: data.solutionText || '',
                            audioUrl: data.audioUrl || ''
                        })
                    });

                    const sendData = await sendResponse.json();
                    if (sendData.success) {
                        alert('✅ 메시지가 성공적으로 재발송되었습니다!');
                        location.reload();
                    } else {
                        throw new Error(sendData.error || '재발송 실패');
                    }
                } else {
                    throw new Error(data.error || '상호작용 데이터 로드 실패');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ 재발송 중 오류가 발생했습니다: ' + error.message);
            }
        }

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
                        document.getElementById('modalProblemText').innerHTML = processTextContent(data.problemText);
                        // MathJax 렌더링
                        if (window.MathJax) {
                            window.MathJax.typesetPromise([document.getElementById('modalProblemText')]).then(() => {
                                console.log('Problem text MathJax rendering completed');
                            }).catch((err) => {
                                console.warn('Problem text MathJax rendering error:', err);
                            });
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
                                window.MathJax.typesetPromise([document.getElementById('solutionContent')]).then(() => {
                                    console.log('MathJax rendering completed');
                                }).catch((err) => {
                                    console.warn('MathJax rendering error:', err);
                                });
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
            
            // 섹션별로 표시
            sections.forEach(section => {
                addDialogueLine(section.trim(), solutionContent);
            });
            
            // 섹션이 없으면 전체 텍스트를 하나로 표시
            if (sections.length === 0) {
                addDialogueLine(text, solutionContent);
            }
        }

        function addDialogueLine(line, container) {
            const lineDiv = document.createElement('div');
            lineDiv.className = 'dialogue-line';
            
            // 섹션 헤더인지 확인
            if (line.match(/^\[.+\]/)) {
                lineDiv.style.fontWeight = 'bold';
                lineDiv.style.fontSize = '18px';
                lineDiv.style.color = '#2d3748';
                lineDiv.style.borderLeftColor = '#4299e1';
                lineDiv.innerHTML = processTextContent(line);
            } else {
                // 텍스트 처리: 마크다운과 수식 태그를 HTML로 변환
                lineDiv.innerHTML = processTextContent(line);
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

        // 자동 새로고침 (30초마다)
        setInterval(() => {
            const statsBar = document.querySelector('.stats-bar');
            if (statsBar) {
                // AJAX로 최신 통계 업데이트 (선택사항)
                // fetch('get_latest_stats.php?userid=<?php echo $userid; ?>&studentid=<?php echo $studentid; ?>')
                //     .then(response => response.json())
                //     .then(data => updateStats(data));
            }
        }, 30000);
        
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
    </script>

    <!-- MathJax Configuration -->
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
</body>
</html>