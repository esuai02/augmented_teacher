<?php 
/**
 * 모바일 버전 학습 일지 (학부모용)
 * 파일: teachers/mathpomodoro.php
 * 작성일: 2025-01-XX
 */ 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 페이지 방문 카운트
include("../pagecount.php");

$studentid = $_GET["userid"] ?? $USER->id;
$tperiod = $_GET["tp"] ?? 604800; // 기본 1주일
$timecreated = time(); 
$tbegin = $timecreated - $tperiod;

// 학생 정보 조회
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", [$studentid]);
if (!$thisuser) {
    die("학생 정보를 찾을 수 없습니다. (파일: mathpomodoro.php, 라인: " . __LINE__ . ")");
}
$stdname = $thisuser->lastname;
$studentname = $thisuser->firstname . $thisuser->lastname;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", [$USER->id]); 
$role = $userrole->role ?? '';

// 학습 활동 기록 조회
$instructions = $DB->get_records_sql(
    "SELECT * FROM mdl_abessi_tracking 
     WHERE userid = ? AND duration > ? AND hide = 0 
     ORDER BY id DESC LIMIT 100",
    [$studentid, $tbegin]
);

$result = json_decode(json_encode($instructions), true);
$activities = [];

foreach($result as $value) {
    if($value['status'] === 'context') continue;
    
    $statustext = $value['status'];
    $trackingtext = $value['text'];
    
    // 활동 유형 아이콘
    $headingtext = '';
    if($statustext === 'waiting') {
        $headingtext = '🔒 대기';
    } elseif(strpos($trackingtext, '개념') !== false) {
        $headingtext = '🌱 준비';
    } elseif(strpos($trackingtext, '유형') !== false || strpos($trackingtext, '단원') !== false || strpos($trackingtext, '도약') !== false) {
        $headingtext = '🍎 응시';
    } elseif(strpos($trackingtext, '오답') !== false) {
        $headingtext = '📝 오답';
    } elseif(strpos($trackingtext, '과제') !== false) {
        $headingtext = '📚 과제';
    } elseif(strpos($trackingtext, '시험') !== false) {
        $headingtext = '🏬 시험';
    } else {
        $headingtext = '🌈 기타';
    }
    
    // 시간 계산
    $tresult = $value['timefinished'] - $value['timecreated'];
    $tamount = $value['duration'] - $value['timecreated'];
    if($tresult < 0) $tresult = 0;
    
    $planMinutes = round($tamount / 60, 0);
    $actualMinutes = round($tresult / 60, 0);
    
    // 만족도
    $satisfaction = '';
    if($value['result'] == 3) {
        $satisfaction = '<span style="color:#4CAF50;font-weight:bold;">매우 만족</span>';
    } elseif($value['result'] == 2) {
        $satisfaction = '<span style="color:#757575;">만족</span>';
    } elseif($value['result'] == 1) {
        $satisfaction = '<span style="color:#FF9800;">불만족</span>';
    }
    
    // 완료된 활동만 표시
    if($statustext === 'complete' && $value['timefinished'] > 0) {
        $activities[] = [
            'date' => date("m/d H:i", $value['timecreated']),
            'type' => $headingtext,
            'content' => $trackingtext,
            'plan_time' => $planMinutes,
            'actual_time' => $actualMinutes,
            'satisfaction' => $satisfaction,
            'timecreated' => $value['timecreated']
        ];
    }
}

// 메모 조회 (최근 3개월)
$threeMonthsAgo = time() - (90 * 24 * 60 * 60);
$notes = $DB->get_records_sql(
    "SELECT sn.*, uid.data AS author_role 
     FROM mdl_abessi_stickynotes sn 
     LEFT JOIN mdl_user_info_data uid ON sn.authorid = uid.userid AND uid.fieldid = 22
     WHERE sn.userid = ? AND sn.hide = 0 AND sn.type = 'timescaffolding' 
     AND sn.created_at >= ? 
     ORDER BY sn.created_at DESC 
     LIMIT 20", 
    [$studentid, $threeMonthsAgo]
);

// 포스트잇 HTML 생성 함수
function createStickyNoteHTML($note, $type) {
    $color = isset($note['color']) ? $note['color'] : 'yellow';
    $rawContent = isset($note['content']) ? $note['content'] : '';
    $createdAt = isset($note['created_at']) ? (int)$note['created_at'] : time();
    $dateStr = date('Y-m-d', $createdAt);
    $timeStr = date('H:i', $createdAt);
    
    // 이미지 태그가 있으면 HTML 그대로 사용, 없으면 이스케이프 처리
    if (strpos($rawContent, '<img') !== false || strpos($rawContent, '<') !== false) {
        $content = $rawContent;
    } else {
        $content = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8');
    }
    
    return '<div class="sticky-note-item sticky-note-' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '">
        <div class="sticky-note-header">
            <span class="sticky-note-date">' . htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8') . '</span>
        </div>
        <div class="sticky-note-content">' . $content . '</div>
    </div>';
}

$teacherNotesHTML = '';
$studentNotesHTML = '';

if ($notes) {
    foreach ($notes as $note) {
        $noteArray = (array)$note;
        if (isset($noteArray['author_role']) && $noteArray['author_role'] === 'student') {
            $studentNotesHTML .= createStickyNoteHTML($noteArray, 'student');
        } else {
            $teacherNotesHTML .= createStickyNoteHTML($noteArray, 'teacher');
        }
    }
}

if (empty($teacherNotesHTML)) {
    $teacherNotesHTML = '<div class="empty-notes">선생님 메모가 없습니다.</div>';
}

if (empty($studentNotesHTML)) {
    $studentNotesHTML = '<div class="empty-notes">학생 메모가 없습니다.</div>';
}

// 통계 데이터 (최근 완료된 활동 기준)
$totalActivities = count($activities);
$totalPlanTime = 0;
$totalActualTime = 0;
$satisfactionCount = ['very' => 0, 'normal' => 0, 'low' => 0];

foreach($activities as $act) {
    $totalPlanTime += $act['plan_time'];
    $totalActualTime += $act['actual_time'];
    if(strpos($act['satisfaction'], '매우 만족') !== false) {
        $satisfactionCount['very']++;
    } elseif(strpos($act['satisfaction'], '만족') !== false) {
        $satisfactionCount['normal']++;
    } elseif(strpos($act['satisfaction'], '불만족') !== false) {
        $satisfactionCount['low']++;
    }
}

$avgPlanTime = $totalActivities > 0 ? round($totalPlanTime / $totalActivities, 0) : 0;
$avgActualTime = $totalActivities > 0 ? round($totalActualTime / $totalActivities, 0) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($studentname); ?> 📒</title>
    
    <!-- 부트스트랩 CSS -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    
    <style>
        /* 모바일 뷰 컨테이너 - PC에서도 모바일처럼 보이게 */
        body {
            font-size: 0.7rem;
            background-color: #f9f9f9;
            padding-top: 60px;
            margin: 0;
        }
        
        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        /* 상단 고정 탭 메뉴 - 모바일 스타일 */
        .top-menu {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: #f8f8f8;
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
            z-index: 50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .top-menu table {
            width: 100%;
            margin: 0;
        }
        
        .top-menu td {
            width: 25%;
            padding: 0.2rem;
        }
        
        .top-menu .btn {
            width: 100%;
            font-size: 0.7rem;
            padding: 0.3rem 0.4rem;
            border-radius: 4px;
            white-space: nowrap;
        }
        
        .wrapper {
            padding: 0;
        }
        
        .content {
            max-width: 100%;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            font-weight: bold;
        }
        
        /* 포스트잇 메모 스타일 */
        .sticky-notes-section {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .sticky-notes-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .notes-column {
            width: 100%;
            min-width: 100%;
        }
        
        .notes-column-title {
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ddd;
        }
        
        .teacher-notes-title {
            color: #0066cc;
        }
        
        .student-notes-title {
            color: #cc6600;
        }
        
        .sticky-notes-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .sticky-note-item {
            position: relative;
            padding: 1rem;
            min-height: 120px;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.15);
            transform: rotate(-1deg);
            transition: transform 0.2s;
            cursor: default;
        }
        
        .sticky-note-item:nth-child(even) {
            transform: rotate(1deg);
        }
        
        .sticky-note-item:hover {
            transform: rotate(0deg) scale(1.02);
            box-shadow: 3px 3px 12px rgba(0,0,0,0.2);
        }
        
        .sticky-note-yellow {
            background: #ffeb3b;
            border-left: 4px solid #fbc02d;
        }
        
        .sticky-note-green {
            background: #c8e6c9;
            border-left: 4px solid #66bb6a;
        }
        
        .sticky-note-blue {
            background: #bbdefb;
            border-left: 4px solid #42a5f5;
        }
        
        .sticky-note-pink {
            background: #f8bbd0;
            border-left: 4px solid #ec407a;
        }
        
        .sticky-note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.3rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .sticky-note-date {
            font-size: 0.75rem;
            color: #666;
            font-weight: 500;
        }
        
        .sticky-note-content {
            font-size: 0.85rem;
            line-height: 1.5;
            color: #333;
            word-wrap: break-word;
        }
        
        .sticky-note-content img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        
        .empty-notes {
            text-align: center;
            padding: 2rem;
            color: #999;
            font-style: italic;
            background: #fff;
            border-radius: 4px;
            border: 1px dashed #ddd;
        }
        
        .goinghome-report-btn {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #87CEEB 0%, #B0E0E6 100%);
            border: 1px solid #87CEEB;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #00688B;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(135, 206, 235, 0.3);
        }
        
        .goinghome-report-btn:hover {
            background: linear-gradient(135deg, #B0E0E6 0%, #87CEEB 100%);
            border-color: #4682B4;
            color: #003366;
            box-shadow: 0 4px 8px rgba(135, 206, 235, 0.5);
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        /* 학습 일지 스타일 */
        .activity-section {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .activity-section-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 0.5rem;
        }
        
        .activity-item {
            border-bottom: 1px solid #e0e0e0;
            padding: 0.75rem 0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .activity-type {
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .activity-date {
            font-size: 0.7rem;
            color: #999;
        }
        
        .activity-content {
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 0.5rem;
            word-break: break-word;
        }
        
        .activity-info {
            display: flex;
            gap: 1rem;
            font-size: 0.7rem;
            color: #666;
            flex-wrap: wrap;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .time-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 0.7rem;
        }
        
        .time-badge.over {
            background: #ffebee;
            color: #c62828;
        }
        
        @media (max-width: 768px) {
            .sticky-notes-container {
                flex-direction: column;
            }
            
            .notes-column {
                min-width: 100%;
            }
            
            .activity-info {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <div class="top-menu">
            <table align="center" style="width: 100%;">
                <tr>
                    <td>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id=<?php echo $studentid; ?>&eid=1" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일정</a>
                    </td>
                    <td>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id=<?php echo $studentid; ?>&tb=604800" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">계획</a>
                    </td>
                    <td>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/mathpomodoro.php?userid=<?php echo $studentid; ?>" class="btn btn-sm btn-danger" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일지</a>
                    </td>
                    <td>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id=<?php echo $studentid; ?>&tb=43200" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">오늘</a>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="wrapper">
            <div class="content">
                <div class="container-fluid py-2 px-2">
                <!-- 상단 제목 -->
                <div class="text-center mb-2">
                    <span style="font-size:0.95rem; font-weight:bold;">
                        <?php echo htmlspecialchars($studentname); ?>의 학습 일지
                    </span>
                </div>
                
                <!-- 귀가검사 리포트 보기 버튼 -->
                <div style="margin-bottom: 1.5rem;">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/dashboard.php?userid=<?php echo $studentid; ?>" target="_blank" class="goinghome-report-btn">
                        귀가검사 리포트 보기
                    </a>
                </div>
                
                <!-- 학습 일지 섹션 -->
                <div class="activity-section">
                    <div class="activity-section-title">📚 학습 일지</div>
                    
                    <?php if (!empty($activities)): ?>
                        <?php 
                        $prevDate = '';
                        foreach($activities as $act): 
                            $currentDate = date("m/d", $act['timecreated']);
                            if($prevDate !== $currentDate):
                                if($prevDate !== '') echo '<hr style="margin: 10px 0; border: none; border-top: 1px solid #e0e0e0;">';
                                $prevDate = $currentDate;
                            endif;
                        ?>
                            <div class="activity-item">
                                <div class="activity-header">
                                    <div class="activity-type"><?php echo $act['type']; ?></div>
                                    <div class="activity-date"><?php echo $act['date']; ?></div>
                                </div>
                                <div class="activity-content"><?php echo htmlspecialchars($act['content']); ?></div>
                                <div class="activity-info">
                                    <div class="info-item">
                                        <span>계획:</span>
                                        <span class="time-badge"><?php echo $act['plan_time']; ?>분</span>
                                    </div>
                                    <div class="info-item">
                                        <span>소요:</span>
                                        <span class="time-badge <?php echo $act['actual_time'] > $act['plan_time'] ? 'over' : ''; ?>">
                                            <?php echo $act['actual_time']; ?>분
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span>만족도:</span>
                                        <?php echo $act['satisfaction']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-notes">학습 기록이 없습니다.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    <!-- Core JS Files -->
    <script src="../assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
</body>
</html>
