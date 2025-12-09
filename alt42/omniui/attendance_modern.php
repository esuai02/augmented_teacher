<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 교사 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"); 
$role = $userrole ? $userrole->role : 'student';

// 교사 권한 확인 - lastname에 T가 있거나 role이 student가 아닌 경우
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
} elseif ($role !== 'student') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다. 교사 계정으로 로그인해주세요.</h2>");
}

// URL 파라미터
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
$view = isset($_GET['view']) ? $_GET['view'] : 'list';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// 학생 목록 조회
$students = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, u.email, u.phone1 as phone
    FROM mdl_user u
    INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
    WHERE uid.fieldid = 22 AND uid.data = 'student'
    ORDER BY u.firstname, u.lastname
    LIMIT 100
");

// 선택된 학생 정보
$selected_student = null;
if ($studentid > 0) {
    $selected_student = $DB->get_record_sql("SELECT * FROM mdl_user WHERE id = ?", array($studentid));
}

// 출결 데이터 계산 함수
function calculateAttendanceData($DB, $studentid) {
    $threeWeeksAgo = strtotime("-3 weeks");
    
    // 휴강 시간
    $sqlAbsence = "SELECT SUM(amount) as total_absence 
                   FROM mdl_abessi_classtimemanagement 
                   WHERE userid = ? AND event = 'absence' AND hide = 0 AND due >= ?";
    $absenceRecord = $DB->get_record_sql($sqlAbsence, array($studentid, $threeWeeksAgo));
    $totalAbsence = $absenceRecord ? floatval($absenceRecord->total_absence) : 0;
    
    // 보강 시간
    $sqlMakeup = "SELECT SUM(amount) as total_makeup 
                  FROM mdl_abessi_classtimemanagement 
                  WHERE userid = ? AND event = 'makeup' AND hide = 0 AND due >= ?";
    $makeupRecord = $DB->get_record_sql($sqlMakeup, array($studentid, $threeWeeksAgo));
    $totalMakeup = $makeupRecord ? floatval($makeupRecord->total_makeup) : 0;
    
    $neededMakeup = max(0, $totalAbsence - $totalMakeup);
    
    return array(
        'totalAbsence' => $totalAbsence,
        'totalMakeup' => $totalMakeup,
        'neededMakeup' => $neededMakeup
    );
}

// 실시간 알림 데이터 (샘플)
$alerts = array(
    array(
        'id' => 1,
        'type' => 'absence',
        'priority' => 'urgent',
        'studentName' => '김철수',
        'message' => '정규수업 결석 (15분 경과)',
        'time' => '15분 전'
    ),
    array(
        'id' => 2,
        'type' => 'unscheduled',
        'priority' => 'normal',
        'studentName' => '박민수',
        'message' => '예정외 접속 감지',
        'time' => '5분 전'
    )
);

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_absence' && $studentid > 0) {
        $hours = floatval($_POST['hours']);
        $date = $_POST['date'];
        
        $record = new stdClass();
        $record->userid = $studentid;
        $record->event = 'absence';
        $record->hide = 0;
        $record->amount = $hours;
        $record->text = $_POST['reason'] ?? '';
        $record->due = strtotime($date);
        $record->timecreated = time();
        $record->status = 'done';
        $record->role = 'teacher';
        
        $DB->insert_record('abessi_classtimemanagement', $record);
        $_SESSION['success_message'] = '휴강이 등록되었습니다.';
        header("Location: attendance_modern.php?userid=$studentid&view=calendar");
        exit;
    }
    
    if ($action === 'add_makeup' && $studentid > 0) {
        $hours = floatval($_POST['hours']);
        $date = $_POST['date'];
        
        $record = new stdClass();
        $record->userid = $studentid;
        $record->event = 'makeup';
        $record->hide = 0;
        $record->amount = $hours;
        $record->text = $_POST['note'] ?? '';
        $record->due = strtotime($date);
        $record->timecreated = time();
        $record->status = 'done';
        $record->role = 'teacher';
        
        $DB->insert_record('abessi_classtimemanagement', $record);
        $_SESSION['success_message'] = '보강이 등록되었습니다.';
        header("Location: attendance_modern.php?userid=$studentid&view=calendar");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>출결관리 시스템</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/lucide@latest/font/lucide.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .transition-all { transition: all 0.3s ease; }
        .hover-scale:hover { transform: scale(1.02); }
        .shadow-custom { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-4 md:p-6">
        <!-- 헤더 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">출결관리 시스템</h1>
                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> 선생님의 담당 학생 관리</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <input type="date" value="<?php echo date('Y-m-d'); ?>" 
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- 알림 버튼 -->
                    <div class="relative">
                        <button onclick="toggleNotifications()" 
                                class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <?php if (count($alerts) > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse font-medium">
                                <?php echo count($alerts); ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- 알림 드롭다운 -->
                        <div id="notificationDropdown" class="hidden absolute top-full right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 max-h-96 overflow-y-auto">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900">실시간 알림</h3>
                                    <button onclick="toggleNotifications()" class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (empty($alerts)): ?>
                            <div class="p-6 text-center">
                                <svg class="w-8 h-8 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm font-medium text-gray-600">모든 상황이 정상입니다</p>
                            </div>
                            <?php else: ?>
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($alerts as $alert): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start space-x-3">
                                        <div class="p-2 rounded-full flex-shrink-0 <?php echo $alert['priority'] === 'urgent' ? 'bg-red-100' : 'bg-yellow-100'; ?>">
                                            <svg class="w-5 h-5 <?php echo $alert['priority'] === 'urgent' ? 'text-red-600' : 'text-yellow-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($alert['studentName']); ?></div>
                                            <div class="text-xs text-gray-600 mb-2"><?php echo htmlspecialchars($alert['message']); ?></div>
                                            <div class="text-xs text-gray-500 mb-3"><?php echo htmlspecialchars($alert['time']); ?></div>
                                            <button class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                                                처리하기
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-500 hidden md:block">
                        마지막 업데이트: <?php echo date('H:i:s'); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($view === 'calendar' && $selected_student): ?>
        <!-- 캘린더 뷰 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <a href="?view=list" class="flex items-center space-x-2 text-blue-600 hover:text-blue-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="font-medium">목록으로 돌아가기</span>
                    </a>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($selected_student->firstname . ' ' . $selected_student->lastname); ?> 학생 캘린더
                    </h2>
                </div>
            </div>

            <!-- 월 네비게이션 -->
            <div class="flex items-center justify-center space-x-4 mb-6">
                <a href="?userid=<?php echo $studentid; ?>&view=calendar&month=<?php echo date('Y-m', strtotime($month . ' -1 month')); ?>" 
                   class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <span class="text-lg font-semibold min-w-[120px] text-center">
                    <?php echo date('Y년 n월', strtotime($month)); ?>
                </span>
                <a href="?userid=<?php echo $studentid; ?>&view=calendar&month=<?php echo date('Y-m', strtotime($month . ' +1 month')); ?>"
                   class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <!-- 캘린더 그리드 -->
            <div class="grid grid-cols-7 gap-2 mb-4">
                <?php 
                $days = array('일', '월', '화', '수', '목', '금', '토');
                foreach ($days as $index => $day): 
                ?>
                <div class="p-4 text-center font-semibold <?php echo $index === 0 ? 'text-red-500' : ($index === 6 ? 'text-blue-500' : 'text-gray-600'); ?> bg-gray-50 rounded-lg">
                    <?php echo $day; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
            // 캘린더 날짜 생성
            $firstDay = date('w', strtotime($month . '-01'));
            $lastDay = date('t', strtotime($month . '-01'));
            $currentDay = 1;
            $today = date('Y-m-d');
            
            // 출결 기록 조회
            $startDate = strtotime($month . '-01');
            $endDate = strtotime($month . '-' . $lastDay . ' 23:59:59');
            $attendance_records = $DB->get_records_sql(
                "SELECT * FROM mdl_abessi_classtimemanagement 
                 WHERE userid = ? AND hide = 0 AND due BETWEEN ? AND ?
                 ORDER BY due ASC",
                array($studentid, $startDate, $endDate)
            );
            
            // 날짜별 데이터 정리
            $calendar_data = array();
            foreach ($attendance_records as $record) {
                $date_key = date('Y-m-d', $record->due);
                if (!isset($calendar_data[$date_key])) {
                    $calendar_data[$date_key] = array();
                }
                $calendar_data[$date_key][] = $record;
            }
            ?>

            <div class="grid grid-cols-7 gap-2">
                <?php
                // 빈 칸 채우기
                for ($i = 0; $i < $firstDay; $i++) {
                    echo '<div class="p-3 min-h-[100px]"></div>';
                }
                
                // 날짜 표시
                while ($currentDay <= $lastDay):
                    $currentDate = $month . '-' . str_pad($currentDay, 2, '0', STR_PAD_LEFT);
                    $isToday = $currentDate === $today;
                    $hasData = isset($calendar_data[$currentDate]);
                ?>
                <div class="p-3 min-h-[100px] border-2 rounded-xl cursor-pointer hover:bg-gray-50 transition-all <?php echo $isToday ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-sm font-semibold <?php echo date('w', strtotime($currentDate)) == 0 ? 'text-red-500' : (date('w', strtotime($currentDate)) == 6 ? 'text-blue-500' : 'text-gray-900'); ?>">
                            <?php echo $currentDay; ?>
                        </span>
                    </div>
                    
                    <?php if ($hasData): ?>
                    <div class="space-y-1">
                        <?php foreach ($calendar_data[$currentDate] as $record): ?>
                        <div class="text-xs px-2 py-1 rounded-lg font-medium <?php echo $record->event === 'absence' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200'; ?>">
                            <div class="flex items-center space-x-1">
                                <div class="w-2 h-2 rounded-full <?php echo $record->event === 'absence' ? 'bg-red-500' : 'bg-green-500'; ?>"></div>
                                <span><?php echo $record->event === 'absence' ? '휴강' : '보강'; ?> <?php echo $record->amount; ?>h</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                $currentDay++;
                endwhile; 
                ?>
            </div>
        </div>

        <!-- 보강 시간 표시 -->
        <?php 
        $attendanceData = calculateAttendanceData($DB, $studentid);
        ?>
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">출결 현황</h3>
            <div class="flex items-center justify-center space-x-6">
                <div class="text-center p-6 border-2 border-red-200 rounded-xl flex-1 bg-red-50">
                    <div class="text-3xl font-bold text-red-600"><?php echo number_format($attendanceData['neededMakeup'], 1); ?></div>
                    <div class="text-sm text-gray-600 font-medium">보강 필요</div>
                </div>
                <div class="text-center p-6 border-2 border-blue-200 rounded-xl flex-1 bg-blue-50">
                    <div class="text-3xl font-bold text-blue-600"><?php echo number_format($attendanceData['totalMakeup'], 1); ?></div>
                    <div class="text-sm text-gray-600 font-medium">보강 완료</div>
                </div>
                <div class="text-center p-6 border-2 border-gray-200 rounded-xl flex-1 bg-gray-50">
                    <div class="text-3xl font-bold text-gray-600"><?php echo number_format($attendanceData['totalAbsence'], 1); ?></div>
                    <div class="text-sm text-gray-600 font-medium">총 휴강</div>
                </div>
            </div>
        </div>

        <!-- 빠른 입력 -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">빠른 입력</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 휴강 입력 -->
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_absence">
                    <h4 class="font-medium text-gray-900">휴강 추가</h4>
                    <input type="date" name="date" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <select name="hours" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">시간 선택</option>
                        <?php for ($i = 0.5; $i <= 6; $i += 0.5): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?>시간</option>
                        <?php endfor; ?>
                    </select>
                    <input type="text" name="reason" placeholder="사유 (선택)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" 
                            class="w-full px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">
                        휴강 추가
                    </button>
                </form>

                <!-- 보강 입력 -->
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_makeup">
                    <h4 class="font-medium text-gray-900">보강 추가</h4>
                    <input type="date" name="date" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <select name="hours" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">시간 선택</option>
                        <?php for ($i = 0.5; $i <= 6; $i += 0.5): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?>시간</option>
                        <?php endfor; ?>
                    </select>
                    <input type="text" name="note" placeholder="메모 (선택)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" 
                            class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        보강 추가
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- 학생 목록 뷰 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">담당 학생 목록</h2>
                    <span class="text-sm text-gray-500">(<?php echo count($students); ?>명)</span>
                </div>
            </div>
            
            <!-- 검색 바 -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex-1 relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" placeholder="학생 이름으로 검색..." 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <select class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">모든 상태</option>
                        <option value="normal">정상</option>
                        <option value="makeup_needed">보강 필요</option>
                        <option value="in_class">수업 중</option>
                    </select>
                </div>
            </div>

            <!-- 학생 테이블 -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                학생 정보
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                보강 필요
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                보강 완료
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                총 휴강
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                상태
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                관리
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($students as $student): 
                            $attendanceData = calculateAttendanceData($DB, $student->id);
                            $status = $attendanceData['neededMakeup'] > 0 ? '보강 필요' : '정상';
                            $statusColor = $attendanceData['neededMakeup'] > 0 ? 'text-yellow-600 bg-yellow-50 border-yellow-200' : 'text-green-600 bg-green-50 border-green-200';
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12 relative">
                                        <div class="h-12 w-12 rounded-full flex items-center justify-center border-2 bg-blue-100 border-blue-300">
                                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <a href="?userid=<?php echo $student->id; ?>&view=calendar" 
                                           class="text-lg font-semibold text-blue-600 hover:text-blue-800 underline transition-colors">
                                            <?php echo htmlspecialchars($student->firstname . ' ' . $student->lastname); ?>
                                        </a>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($student->email); ?>
                                        </div>
                                        <?php if ($student->phone): ?>
                                        <div class="text-xs text-gray-500">
                                            📞 <?php echo htmlspecialchars($student->phone); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span class="font-semibold text-lg <?php echo $attendanceData['neededMakeup'] > 0 ? 'text-red-600' : ''; ?>">
                                        <?php echo number_format($attendanceData['neededMakeup'], 1); ?>
                                    </span>
                                    <span class="text-gray-500 ml-1">시간</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span class="font-semibold text-lg text-green-600">
                                        <?php echo number_format($attendanceData['totalMakeup'], 1); ?>
                                    </span>
                                    <span class="text-gray-500 ml-1">시간</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span class="font-semibold text-lg">
                                        <?php echo number_format($attendanceData['totalAbsence'], 1); ?>
                                    </span>
                                    <span class="text-gray-500 ml-1">시간</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo $statusColor; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-wrap gap-2">
                                    <a href="?userid=<?php echo $student->id; ?>&view=calendar" 
                                       class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors" title="캘린더 보기">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </a>
                                    <?php if ($student->phone): ?>
                                    <a href="tel:<?php echo $student->phone; ?>" 
                                       class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors" title="학생 연락">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 하단 안내 -->
        <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6">
            <div class="flex">
                <svg class="w-6 h-6 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3">시스템 자동화 안내</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span><strong>정규수업 15분 경과</strong> → 자동 결석 처리 → 교사 알림</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                <span><strong>예정외 접속 감지</strong> → 실시간 알림 → 교사 승인</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span><strong>보강수업 완료</strong> → 자동 시간 차감 → 학부모 알림</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span><strong>매일 자정</strong> → 일일 정산 → 주간/월간 리포트</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('hidden');
        }

        // 외부 클릭시 드롭다운 닫기
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notificationDropdown');
            const button = event.target.closest('button[onclick*="toggleNotifications"]');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>