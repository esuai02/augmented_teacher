<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$mode = $_GET["mode"];

// URL 파라미터
$userid = required_param('userid', PARAM_INT);

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ?", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';

// 학생 정보
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($userid));
$firstname = isset($username->firstname) ? $username->firstname : '';
$lastname  = isset($username->lastname) ? $username->lastname : '';
$studentname = htmlspecialchars($firstname, ENT_QUOTES) . ' ' . htmlspecialchars($lastname, ENT_QUOTES);

// 자동발송 여부
$subscribe = $DB->get_field('user', 'subscribe', array('id' => $userid));
$isChecked = ($subscribe === 'ON') ? 'checked' : '';

// 주차/학년 정보
$school_type = 'middle';
$grade = '3학년';
$month = date('n');
$week  = ceil(date('j') / 7);
$status = 'published';

// 피드백 레코드
$feedbackRecord = $DB->get_record_sql("
    SELECT *
    FROM {alt42_homefeedback}
    WHERE userid = ?
    ORDER BY timemodified DESC LIMIT 1
", array($userid));

// 주간 컨텐츠 (스토리용)
$content = $DB->get_record_sql("
    SELECT *
    FROM {alt42_weeklycuration}
    WHERE school_type = ?
      AND grade = ?
      AND month = ?
      AND week = ?
      AND status = ?
    ORDER BY id DESC LIMIT 1
", array($school_type, $grade, $month, $week, $status));

if ($content) {
    $introtext = $content->introtext;
    $content_link = $content->link;
    $content_title = $content->title;
} else {
    $introtext = '이번 주에 해당하는 컨텐츠가 없습니다.';
    $content_link = '#';
    $content_title = '';
}

// 최근 1주일 학습 추적
$tbegin = time() - 604800;
$tend   = time();
$instructions = $DB->get_records_sql("
    SELECT *
    FROM mdl_abessi_tracking
    WHERE userid='$userid'
      AND timecreated > '$tbegin'
      AND timecreated < '$tend'
    ORDER BY id DESC
    LIMIT 100
");
$result = json_decode(json_encode($instructions), true);

// ----------------------------
// 학습 일지 항목 구성 (stripe 디자인 적용)
// 각 항목은 두 줄 구성: 
//  - 첫 줄: 요일, 시간, 학습 시간(총시간/실제시간)
//  - 두번째 줄: 학습 내용
// 6개 이상의 항목은 기본적으로 숨기고, "더보기" 버튼 클릭 시 토글
// ----------------------------
$directionlist = '<div class="space-y-2">';
$counter = 0;
foreach ($result as $value) {
    // 시간 계산
    $tresult = $value['timefinished'] - $value['timecreated'];
    $tamount = $value['duration'] - $value['timecreated'];
    if ($tresult < 0) { $tresult = 0; }
    // 실제 학습 시간에 따라 색상 표시 (초과면 빨간색, 아니면 초록색)
    $tresult_display = ($tresult > $tamount)
        ? '<span class="text-red-400">'.round($tresult/60, 0).'분</span>'
        : '<span class="text-green-500">'.round($tresult/60, 0).'분</span>';
    $tamount_display = '<span>'.round($tamount/60, 0).'분</span>';
    
    // 날짜, 요일, 시간 포맷팅 (한글 로케일)
    setlocale(LC_TIME, 'ko_KR.UTF-8');
    $daystr = strftime("%A", $value['timecreated']);
    $timestr = strftime("%H:%M", $value['timecreated']);
    
    // 6개 이상 항목은 기본적으로 숨김 처리
    $hiddenClass = ($counter >= 6) ? 'hidden' : '';
    
    // 구성: 첫 줄에 날짜/시간 및 학습 시간, 두번째 줄에 학습 내용
    $directionlist .= '<div class="log-item p-4 border-b ' . $hiddenClass . '">';
    $directionlist .= '  <div class="flex justify-between items-center text-sm text-gray-700">';
    $directionlist .= '    <div class="font-semibold">' . $daystr . ' ' . $timestr . '</div>';
    $directionlist .= '    <div>' . $tamount_display . ' / ' . $tresult_display . '</div>';
    $directionlist .= '  </div>';
    $directionlist .= '  <div class="mt-1 text-sm text-gray-800">✔️ ' . htmlspecialchars($value['text'], ENT_QUOTES) . '</div>';
    $directionlist .= '</div>';
    
    $counter++;
}
$directionlist .= '</div>';
if ($counter > 6) {
    $directionlist .= '<div id="showMoreBar" class="bg-gray-100 text-center py-2 cursor-pointer mt-2 text-sm" onclick="toggleHiddenRows()">더보기</div>';
}

// 자동발송 토글 AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_autosend') {
    require_sesskey();
    $enabled = required_param('enabled', PARAM_INT);
    if ($enabled === 1) {
        $DB->set_field('user', 'subscribe', 'ON', ['id' => $userid]);
    } else {
        $DB->set_field('user', 'subscribe', 'OFF', ['id' => $userid]);
    }
    echo 'ok';
    exit();
}

// 발송 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_letter') {
    require_sesskey();
    $userid = required_param('userid', PARAM_INT);
    $rating1   = optional_param('rating1', 0, PARAM_INT);
    $rating2   = optional_param('rating2', 0, PARAM_INT);
    $rating3   = optional_param('rating3', 0, PARAM_INT);
    $feedback  = optional_param('feedback', '', PARAM_TEXT);
    
    function getWeekNumberBasedOnMondays($date) {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        $currentDate = strtotime($date);
        $weekday = date('N', $currentDate); // 1(월)~7(일)
        $mondayOfWeek = strtotime('-' . ($weekday - 1) . ' days', $currentDate);
        $mondayOfWeekFormatted = date('Y-m-d', $mondayOfWeek);
        
        if (date('n', $mondayOfWeek) != $month) {
            return 1;
        } else {
            $mondaysInMonth = array();
            $firstMonday = strtotime("first monday of $year-$month");
            if (date('n', $firstMonday) != $month) {
                $firstMonday = strtotime("next monday", strtotime("$year-$month-01"));
            }
            $dateIterator = $firstMonday;
            while (date('n', $dateIterator) == $month) {
                $mondaysInMonth[] = date('Y-m-d', $dateIterator);
                $dateIterator = strtotime("+1 week", $dateIterator);
            }
            $weekIndex = array_search($mondayOfWeekFormatted, $mondaysInMonth);
            return ($weekIndex === false) ? 1 : $weekIndex + 1;
        }
    }
    function getTotalWeeksInMonth($year, $month) {
        $mondaysInMonth = array();
        $firstMonday = strtotime("first monday of $year-$month");
        if (date('n', $firstMonday) != $month) {
            $firstMonday = strtotime("next monday", strtotime("$year-$month-01"));
        }
        $dateIterator = $firstMonday;
        while (date('n', $dateIterator) == $month) {
            $mondaysInMonth[] = date('Y-m-d', $dateIterator);
            $dateIterator = strtotime("+1 week", $dateIterator);
        }
        return count($mondaysInMonth);
    }
    
    $today = date('Y-m-d');
    $year  = date('Y', strtotime($today));
    $month = date('n', strtotime($today));
    $week  = getWeekNumberBasedOnMondays($today);
    
    $existingRecord = $DB->get_record('alt42_weekly_letters', array(
        'studentid' => $userid,
        'year'      => $year,
        'month'     => $month,
        'week'      => $week
    ), '*', IGNORE_MISSING);
    
    if ($existingRecord) {
        $existingRecord->feedback = $feedback;
        $existingRecord->rating1 = $rating1;
        $existingRecord->rating2 = $rating2;
        $existingRecord->rating3 = $rating3;
        $existingRecord->timesent = time();
        $DB->update_record('alt42_weekly_letters', $existingRecord);
        echo 'feedback_updated';
    } else {
        $newletter = new stdClass();
        $newletter->senderid  = $USER->id;
        $newletter->studentid = $userid;
        $newletter->week      = $week;
        $newletter->month     = $month;
        $newletter->year      = $year;
        $newletter->rating1   = $rating1;
        $newletter->rating2   = $rating2;
        $newletter->rating3   = $rating3;
        $newletter->feedback  = $feedback;
        $newletter->homefbid  = $feedbackRecord ? $feedbackRecord->id : 0;
        $newletter->timesent  = time();
        
        $DB->insert_record('alt42_weekly_letters', $newletter);
        echo 'feedback_inserted';
    }
    
    echo '
    <div class="p-6 bg-white rounded-lg shadow text-center">
        <svg class="h-16 w-16 mx-auto text-green-500" fill="none" stroke="currentColor" stroke-width="2">
            <use xlink:href="#check-circle"></use>
        </svg>
        <h2 class="text-2xl font-bold mt-4">발송이 완료되었습니다!</h2>
        <p class="text-gray-600 mt-2">주간 안내장이 발송되었습니다. 학부모님께는 발송일 기준 다음 날 오전 10시에 전달됩니다.</p>
        <button class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="window.location.reload()">돌아가기</button>
    </div>
    ';
    exit();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= $month ?>월 <?= $week ?>주차 KTM LETTER</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script> var sesskey = '<?= sesskey(); ?>'; </script>
    <!-- 부트스트랩 CSS (선택사항) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 고정 헤더 (드롭다운 메뉴 포함) -->
    <header class="fixed top-0 left-0 right-0 bg-blue-600 text-white py-3 px-4 shadow z-50">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold">KTM LETTER</h1>
            <div class="relative">
                <button id="menuButton" class="focus:outline-none">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div id="dropdownMenu" class="absolute right-0 mt-2 w-40 bg-white text-blue-600 shadow-lg rounded hidden">
                    <ul>
                        <li class="border-b">
                            <div class="flex items-center px-4 py-2">
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" id="autoSendToggle" class="sr-only peer" <?= $isChecked; ?> onchange="toggleAutoSend(this)">
                                        <div class="block w-10 h-6 rounded-full bg-gray-300 transition-colors duration-300 peer-checked:bg-green-500"></div>
                                        <div class="dot absolute left-1 top-1 w-4 h-4 rounded-full bg-white transition-transform duration-300 peer-checked:translate-x-full"></div>
                                    </div>
                                    <span class="ml-2 text-sm">자동발송</span>
                                </label>
                            </div>
                        </li>
                        <li class="border-b">
                            <button class="w-full text-left px-4 py-2 text-sm hover:bg-blue-100" onclick="handleSend()">발송</button>
                        </li>
                        <li>
                            <button class="w-full text-left px-4 py-2 text-sm hover:bg-blue-100" onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/buildingtrust.php?userid=<?= $userid; ?>', '_blank')">상담도구</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    
    <!-- 메인 콘텐츠 -->
    <main class="pt-20 pb-20 px-4">
        <!-- [일지 섹션] -->
        <section id="sec1" class="mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-blue-600">일지</h2>
                    <button class="text-blue-600 text-sm" onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?= $userid; ?>', '_blank')">수정</button>
                </div>
                <div>
                    <?= $directionlist; ?>
                </div>
            </div>
        </section>
        
        <!-- [스토리 섹션] -->
        <section id="sec2" class="mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-blue-600">스토리</h2>
                    <button class="text-blue-600 text-sm" onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20curation.php', '_blank')">수정</button>
                </div>
                <div class="text-gray-700 text-sm whitespace-pre-line">
                    <?= nl2br(htmlspecialchars($introtext)); ?>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <a href="<?= htmlspecialchars($content_link); ?>" target="_blank" class="text-blue-500 text-sm flex items-center">
                        더보기
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor">
                            <use xlink:href="#chevron-right"></use>
                        </svg>
                    </a>
                    <span class="text-xs text-gray-500">제공: (주) 에듀그라운드</span>
                </div>
            </div>
        </section>
        
        <!-- [피드백 섹션] -->
        <section id="sec3" class="mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <?php
                if ($feedbackRecord) {
                    $existingFeedback = $feedbackRecord->feedback;
                    $feedbackItems = [
                        ['title' => $feedbackRecord->topic1, 'rating' => 0],
                        ['title' => $feedbackRecord->topic2, 'rating' => 0],
                        ['title' => $feedbackRecord->topic3, 'rating' => 0],
                    ];
                } else {
                    $existingFeedback = '';
                    $feedbackItems = [];
                }
                ?>
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-bold text-blue-600">피드백</h2>
                    <button class="text-blue-600 text-sm" onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/parental%20observations.php?userid=<?= $userid; ?>', '_blank')">수정</button>
                </div>
                <p class="text-xs text-gray-500 mb-3">자녀의 가정 내 면학 분위기 관련 내용을 공유해주세요.</p>
                <?php foreach ($feedbackItems as $index => $item): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-100 rounded mb-2" data-index="<?= $index ?>" data-rating="0">
                        <span class="text-gray-700 text-sm"><?= $item['title'] ?></span>
                        <div class="flex items-center gap-1">
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <svg class="w-5 h-5 cursor-pointer text-gray-300" fill="currentColor" data-star="<?= $star ?>" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="mt-3">
                    <input type="text" placeholder="한줄 피드백을 입력해 주세요" class="w-full p-2 border rounded text-sm" id="feedbackInput">
                </div>
                <button class="mt-4 w-full bg-blue-600 text-white py-2 rounded text-sm" onclick="ParentalSend()">
                    담임 선생님에게 전달하기
                </button>
                <div class="mt-2 text-center text-xs text-gray-500">
                    <p>* 학부모님의 피드백은 더 좋은 수업을 위해 활용됩니다.</p>
                    <p>* 문의: 042-489-7447 (평일 오후 2시~10시, 토 오후 12시~6시)</p>
                </div>
            </div>
        </section>
    </main>
    
    <!-- 고정 바텀 내비게이션 -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t shadow z-50">
        <div class="flex justify-around">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id=<?= $userid; ?>&eid=1" class="flex flex-col items-center py-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor">
                    <use xlink:href="#calendar"></use>
                </svg>
                <span class="text-xs">일정</span>
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id=<?= $userid; ?>&tb=604800" class="flex flex-col items-center py-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor">
                    <use xlink:href="#clock"></use>
                </svg>
                <span class="text-xs">계획</span>
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?= $userid; ?>&mode=parental" class="flex flex-col items-center py-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor">
                    <use xlink:href="#book-open"></use>
                </svg>
                <span class="text-xs">일지</span>
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id=<?= $userid; ?>&tb=43200" class="flex flex-col items-center py-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor">
                    <use xlink:href="#today"></use>
                </svg>
                <span class="text-xs">오늘</span>
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20letter.php?userid=<?= $userid; ?>" class="flex flex-col items-center py-2">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor">
                    <use xlink:href="#chat"></use>
                </svg>
                <span class="text-xs">상담</span>
            </a>
        </div>
    </nav>
    
    <!-- 아이콘 정의 -->
    <svg style="display: none;">
        <symbol id="chevron-right" viewBox="0 0 24 24">
            <polyline points="9 18 15 12 9 6"></polyline>
        </symbol>
        <symbol id="book-open" viewBox="0 0 24 24">
            <path d="M2 19V5a2 2 0 0 1 2-2h6v16H4a2 2 0 0 1-2-2Z"></path>
            <path d="M22 19V5a2 2 0 0 0-2-2h-6v16h6a2 2 0 0 0 2-2Z"></path>
        </symbol>
        <symbol id="calendar" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </symbol>
        <symbol id="clock" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </symbol>
        <symbol id="today" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </symbol>
        <symbol id="chat" viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </symbol>
        <symbol id="check-circle" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9 12l2 2 4-4"></path>
        </symbol>
    </svg>
    
    <!-- JS 로직 -->
    <script>
    // 메뉴 버튼 클릭 시 드롭다운 메뉴 토글
    document.getElementById('menuButton').addEventListener('click', function() {
        var dropdown = document.getElementById('dropdownMenu');
        dropdown.classList.toggle('hidden');
    });
    
    // 토글: 숨겨진 일지 항목 보여주기 / 숨기기
    function toggleHiddenRows() {
        const items = document.querySelectorAll('.log-item');
        const showMoreBar = document.getElementById('showMoreBar');
        const isMore = showMoreBar.innerText.trim() === '더보기';
        
        items.forEach((item, index) => {
            if (index >= 6) { // 첫 6개는 항상 표시
                if (isMore) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            }
        });
        showMoreBar.innerText = isMore ? '접기' : '더보기';
    }
    
    // 자동발송 토글 AJAX
    function toggleAutoSend(checkbox) {
        var isChecked = checkbox.checked ? 1 : 0;
        var xhr = new XMLHttpRequest();
        // 현재 페이지 URL로 POST 요청
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        var params = 'action=toggle_autosend&enabled=' + isChecked + '&sesskey=' + encodeURIComponent(sesskey);
        xhr.onload = function() {
            if (xhr.status === 200 && xhr.responseText.trim() === 'ok') {
                console.log('자동발송 설정 업데이트');
            } else {
                alert('자동발송 설정 변경 실패');
                checkbox.checked = !checkbox.checked;
            }
        };
        xhr.send(params);
    }
    
    // 발송 확인 다이얼로그
    function handleSend() {
        var confirmDiv = document.createElement('div');
        confirmDiv.id = 'confirmDiv';
        confirmDiv.innerHTML = `
            <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div class="p-6 bg-white rounded-lg shadow text-center">
                    <p class="text-gray-800 mt-2">
                        발송을 클릭하면 현재 주차의 주간 안내장이 발송됩니다.<br>
                        발송일 기준 다음 날 오전 10시에 학부모님께 전달됩니다.
                    </p>
                    <div class="mt-6 flex justify-center space-x-4">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="confirmSend()">확인</button>
                        <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400" onclick="cancelSend()">취소</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(confirmDiv);
    }
    
    // 피드백 발송 다이얼로그
    function ParentalSend() {
        var confirmDiv = document.createElement('div');
        confirmDiv.id = 'confirmDiv';
        confirmDiv.innerHTML = `
            <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div class="p-6 bg-white rounded-lg shadow text-center">
                    <p class="text-gray-800 mt-2">
                        담임 선생님에게 피드백이 전달됩니다.<br>
                        발송일 기준 다음 날 오후 2시 이후 전달됩니다.
                    </p>
                    <div class="mt-6 flex justify-center space-x-4">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="confirmSend()">확인</button>
                        <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400" onclick="cancelSend()">취소</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(confirmDiv);
    }
    
    // 발송 처리
    function confirmSend() {
        var feedback = document.getElementById('feedbackInput') ? document.getElementById('feedbackInput').value : '';
        var ratings = {};
        var feedbackItems = document.querySelectorAll('[data-index]');
        feedbackItems.forEach(function(item) {
            var index = item.getAttribute('data-index');
            var rating = item.getAttribute('data-rating') || 0;
            ratings['rating' + (parseInt(index) + 1)] = parseInt(rating);
        });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        var params = `action=send_letter&userid=<?= $userid ?>&feedback=${encodeURIComponent(feedback)}` +
                     `&rating1=${ratings.rating1 || 0}&rating2=${ratings.rating2 || 0}&rating3=${ratings.rating3 || 0}` +
                     `&sesskey=${sesskey}`;
        xhr.onload = function () {
            if (xhr.status === 200) {
                var contentDiv = document.querySelector('main');
                contentDiv.innerHTML = xhr.responseText;
            } else {
                alert('오류 발생, 다시 시도해주세요.');
            }
        };
        xhr.send(params);
        cancelSend();
    }
    
    function cancelSend() {
        var confirmDiv = document.getElementById('confirmDiv');
        if (confirmDiv) {
            confirmDiv.remove();
        }
    }
    
    // 별점 평가 기능
    document.addEventListener('DOMContentLoaded', function() {
        var feedbackItems = document.querySelectorAll('[data-index]');
        feedbackItems.forEach(function(item) {
            var stars = item.querySelectorAll('svg[data-star]');
            stars.forEach(function(star) {
                star.addEventListener('mouseenter', function() {
                    highlightStars(item, star.getAttribute('data-star'));
                });
                star.addEventListener('mouseleave', function() {
                    resetStars(item);
                });
                star.addEventListener('click', function() {
                    setRating(item, star.getAttribute('data-star'));
                });
            });
        });
        function highlightStars(item, starValue) {
            var stars = item.querySelectorAll('svg[data-star]');
            stars.forEach(function(star) {
                if (star.getAttribute('data-star') <= starValue) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-400');
                } else {
                    star.classList.remove('text-yellow-400');
                    star.classList.add('text-gray-300');
                }
            });
        }
        function resetStars(item) {
            var rating = item.getAttribute('data-rating') || 0;
            var stars = item.querySelectorAll('svg[data-star]');
            stars.forEach(function(star) {
                if (star.getAttribute('data-star') <= rating) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-400');
                } else {
                    star.classList.remove('text-yellow-400');
                    star.classList.add('text-gray-300');
                }
            });
        }
        function setRating(item, starValue) {
            item.setAttribute('data-rating', starValue);
            resetStars(item);
            console.log('Item ' + item.getAttribute('data-index') + ' rating set to ' + starValue);
        }
    });
    </script>
</body>
</html>
