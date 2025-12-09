<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
require_login();
global $DB, $USER;

 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 

// URL에서 userid 파라미터 가져오기
$userid = required_param('userid', PARAM_INT);

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ?", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';

// 학생 이름 가져오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM {user} WHERE id = ?", array($userid));
$firstname = isset($username->firstname) ? $username->firstname : '';
$lastname = isset($username->lastname) ? $username->lastname : '';
$studentname = htmlspecialchars($firstname, ENT_QUOTES) . ' ' . htmlspecialchars($lastname, ENT_QUOTES);

$school_type = 'middle';
$grade = '3학년';
$month = date('n'); // 현재 월
$week = ceil(date('j') / 7); // 현재 주차 계산
$status = 'published';

// 컨텐츠 가져오기
$content = $DB->get_record_sql("SELECT * FROM {alt42_weeklycuration} WHERE school_type = ? AND grade = ? AND month = ? AND week = ? AND status = ? ORDER BY id DESC LIMIT 1", array($school_type, $grade, $month, $week, $status));

if ($content) {
    $introtext = $content->introtext;
    $content_link = $content->link;
    $content_title = $content->title;
} else {
    // 컨텐츠가 없을 경우 기본값 설정
    $introtext = '이번 주에 해당하는 컨텐츠가 없습니다.';
    $content_link = '#';
    $content_title = '';
}

// 피드백 레코드 가져오기
$feedbackRecord = $DB->get_record_sql("SELECT * FROM {alt42_homefeedback} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1", array($userid));

if ($feedbackRecord) {
    $existingFeedback = $feedbackRecord->feedback; // 기존의 한줄 피드백
    $feedbackItems = [
        ['title' => $feedbackRecord->topic1, 'rating' => $feedbackRecord->rate1],
        ['title' => $feedbackRecord->topic2, 'rating' => $feedbackRecord->rate2],
        ['title' => $feedbackRecord->topic3, 'rating' => $feedbackRecord->rate3],
    ];
} else {
    // 피드백 레코드가 없을 경우 기본값 설정
    $existingFeedback = '';
    $feedbackItems = [];
}

// 주차 정보 계산 (현재 날짜 기준)
$year = date('Y');
$month = date('n'); // 월 (1~12)
$week = ceil(date('j') / 7); // 해당 월의 주차 계산
// 발송 요청 처리 및 AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    require_sesskey(); // CSRF 방지

    $action = required_param('action', PARAM_ALPHA);

    if ($action == 'update_rating') {
        // 별점 업데이트 처리
        $studentid = required_param('userid', PARAM_INT);
        $ratingField = required_param('ratingField', PARAM_ALPHA);
        $ratingValue = required_param('ratingValue', PARAM_INT);

        // 유효한 ratingField인지 확인
        if (!in_array($ratingField, ['rate1', 'rate2', 'rate3'])) {
            echo '잘못된 요청입니다.';
            exit();
        }

        // 해당 사용자의 가장 최근 레코드 가져오기
        $existingRecord = $DB->get_record_sql("SELECT * FROM {alt42_homefeedback} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1", array($studentid));

        if ($existingRecord) {
            // 레코드 업데이트
            $existingRecord->$ratingField = $ratingValue;
            $existingRecord->timemodified = time();
            $DB->update_record('alt42_homefeedback', $existingRecord);
            echo '성공적으로 업데이트되었습니다.';
        } else {
            echo '레코드를 찾을 수 없습니다.';
        }
        exit();
    } else if ($action == 'update_feedback') {
        // 한줄 피드백 업데이트 처리
        $studentid = required_param('userid', PARAM_INT);
        $feedbackText = required_param('feedback', PARAM_TEXT);

        // 해당 사용자의 가장 최근 레코드 가져오기
        $existingRecord = $DB->get_record_sql("SELECT * FROM {alt42_homefeedback} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1", array($studentid));

        if ($existingRecord) {
            // 레코드 업데이트
            $existingRecord->fbtext = $feedbackText; // 여기서 'feedback'을 'fbtext'로 변경했습니다.
            $existingRecord->timemodified = time();
            $DB->update_record('alt42_homefeedback', $existingRecord);
            echo '피드백이 성공적으로 저장되었습니다.';
        } else {
            echo '레코드를 찾을 수 없습니다.';
        }
        exit();
    } else if ($action == 'send_letter') {
        // 데이터 수신
        $studentid = required_param('userid', PARAM_INT);
        $feedback = optional_param('feedback', '', PARAM_TEXT);
        $rating1 = optional_param('rating1', 0, PARAM_INT);
        $rating2 = optional_param('rating2', 0, PARAM_INT);
        $rating3 = optional_param('rating3', 0, PARAM_INT);

        // 주차 계산 함수들
        function getWeekNumberBasedOnMondays($date) {
            // 주차 계산 로직
        }

        function getTotalWeeksInMonth($year, $month) {
            // 총 주차 수 계산 로직
        }

        // 현재 날짜를 기준으로 주차 계산
        $today = date('Y-m-d');
        $year = date('Y', strtotime($today));
        $month = date('n', strtotime($today));
        $week = getWeekNumberBasedOnMondays($today);

        // 해당 월의 총 주차 수 계산
        $totalWeeks = getTotalWeeksInMonth($year, $month);

        // 데이터베이스에 저장
        $newletter = new stdClass();
        $newletter->senderid = $USER->id;
        $newletter->studentid = $studentid;
        $newletter->week = $week;
        $newletter->month = $month;
        $newletter->year = $year;
        $newletter->rating1 = $rating1;
        $newletter->rating2 = $rating2;
        $newletter->rating3 = $rating3;
        $newletter->feedback = $feedback;
        $newletter->timesent = time();

        $DB->insert_record('abessi_weekly_letters', $newletter);

        // 응답 메시지 생성
        echo '
        <div class="p-6 bg-white rounded-lg shadow text-center">
            <svg class="h-16 w-16 mx-auto text-green-500" fill="none" stroke="currentColor" stroke-width="2">
                <use xlink:href="#check-circle"></use>
            </svg>
            <h2 class="text-2xl font-bold mt-4">발송이 완료되었습니다!</h2>
            <p class="text-gray-600 mt-2">주간 안내장이 발송되었습니다. 학부모님께는 발송일 기준 다음 날 오전 10시에 전달됩니다.</p>
            <button class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                onclick="window.location.reload()">
                돌아가기
            </button>
        </div>
        ';
        exit();
    }
}

?> 
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= $month ?>월 <?= $week ?>주차 KTM LETTER</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- sesskey를 JavaScript로 전달 -->
    <script>
        var sesskey = '<?= sesskey(); ?>';
    </script>
</head>
<body class="bg-white">
    <div id="content">
        <div class="max-w-3xl mx-auto p-4 space-y-6">
            <!-- 헤더 -->
            <div class="relative">
                <!-- 발송 버튼 및 자동 발송 토글 -->
                <div class="absolute right-0 top-0 flex items-center gap-2">
                    <!-- 상담 요청 버튼 -->
                    <button
                        class="flex items-center gap-1 bg-blue-600 text-white hover:bg-blue-700 px-3 py-1 rounded"
                        onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/buildingtrust.php?userid=<?= $userid; ?>', '_blank')" >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor">
                            <use xlink:href="#send"></use>
                        </svg>
                        상담요청
                    </button>
                    <!-- 자동 발송 토글 -->
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" id="autoSendToggle" class="sr-only" onchange="toggleAutoSend(this)">
                            <div class="block bg-gray-600 w-10 h-6 rounded-full"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                        </div>
                        <div class="ml-3 text-gray-700">
                            자동발송
                        </div>
                    </label>
                    <!-- 발송 버튼 -->
                    <button class="flex items-center gap-1 bg-blue-600 text-white hover:bg-blue-700 px-3 py-1 rounded" onclick="handleSend();">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor">
                            <use xlink:href="#send"></use>
                        </svg>
                        발송
                    </button>
                </div>
                <?php
                // 주 계산 로직
                $currentWeek = $week;

                // 해당 월의 총 월요일 수 계산
                $firstMonday = strtotime("first monday of $year-$month");
                $lastDayOfMonth = strtotime("$year-$month-" . date('t', strtotime("$year-$month-01")));

                $totalWeeks = 0;
                $currentMonday = $firstMonday;

                while ($currentMonday <= $lastDayOfMonth) {
                    $totalWeeks++;
                    $currentMonday = strtotime('+1 week', $currentMonday);
                }
                ?>
                <div class="text-left space-y-3">
                    <h1 class="text-2xl font-bold text-blue-600"><?= $month ?>월 <?= $week ?>주차 KTM LETTER</h1>
                    <div class="flex items-left justify-left gap-4">
                        <?php for ($i = 1; $i <= $totalWeeks; $i++): ?>
                            <div class="h-0.5 <?= ($i == $week) ? 'w-32 bg-blue-600' : 'w-16 bg-blue-200' ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- 학습 일지 -->
            <?php
            // 학습 일지 데이터베이스에서 가져오기 (예시 데이터 사용)
            $studyLogs = [
                [
                    'time' => '11/21 04:17',
                    'title' => '계획 : 오답노트',
                    'duration' => 10,
                    'comments' => 4
                ],
                [
                    'time' => '11/19 05:01',
                    'title' => '유형정복 02 : 도형의 평행이동',
                    'duration' => 95,
                    'comments' => 5
                ],
                [
                    'time' => '11/19 04:56',
                    'title' => '개념도약: 129. 공통접선',
                    'duration' => 15,
                    'comments' => 4
                ],
                [
                    'time' => '11/19 04:48',
                    'title' => '개념도약: 127. 원 밖의 점에서 원에 그은 접선의 방정식',
                    'duration' => 20,
                    'comments' => 3
                ],
                [
                    'time' => '11/19 04:19',
                    'title' => '복습',
                    'duration' => 60,
                    'comments' => 5
                ]
            ];
            $totalDuration = array_sum(array_column($studyLogs, 'duration'));
            ?>
            <div class="border rounded-lg shadow">
                <div class="border-b p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-blue-600 flex items-center">
                            <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor">
                                <use xlink:href="#book-open"></use>
                            </svg>
                            학습 일지
                        </h2>
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-500">
                                총 학습시간: <?= $totalDuration ?>분
                            </div>
                            <button
                                class="flex items-center gap-1 text-gray-500 hover:text-blue-600"
                                onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?= $userid; ?>', '_blank')" >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor">
                                    <use xlink:href="#edit"></use>
                                </svg>
                                수정
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-1">
                        <?php foreach ($studyLogs as $log): ?>
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border-b last:border-b-0">
                                <div class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor">
                                        <use xlink:href="#sun"></use>
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-500"><?= $log['time'] ?></p>
                                        <p class="font-medium"><?= $log['title'] ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center text-gray-500">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor">
                                            <use xlink:href="#clock"></use>
                                        </svg>
                                        <span class="text-sm"><?= $log['duration'] ?>분</span>
                                    </div>
                                    <?php if ($log['comments'] > 0): ?>
                                        <div class="flex items-center text-gray-500">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor">
                                                <use xlink:href="#message-circle"></use>
                                            </svg>
                                            <span class="text-sm">만족도 (<?= $log['comments'] ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 자녀교육에 좋은 글 -->
            <div class="border rounded-lg shadow">
                <div class="border-b p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor">
                                <use xlink:href="#book-marked"></use>
                            </svg>
                            <span>
                                <span class="text-blue-600">자녀교육에 좋은 글</span>
                                <span class="text-sm text-gray-500">(중학교 3학년 <?= $month ?>월 <?= $week ?>주차)</span>
                            </span>
                        </h2>
                        <button
                            class="flex items-center gap-1 text-gray-500 hover:text-blue-600"
                            onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20curation.php', '_blank')" >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor">
                                <use xlink:href="#edit"></use>
                            </svg>
                            수정
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        <div class="space-y-2 text-gray-600">
                            <?= nl2br(htmlspecialchars($introtext)); ?>
                        </div>
                        <div class="flex items-center justify-between">
                            <a href="<?= htmlspecialchars($content_link); ?>" target="_blank" class="flex items-center text-blue-500 hover:text-blue-600 cursor-pointer">
                                더보기
                                <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor">
                                    <use xlink:href="#chevron-right"></use>
                                </svg>
                            </a>
                            <div class="text-sm text-gray-500">
                                제공: (주) 에듀그라운드
                            </div>
                        </div>

                        <!-- 추가 내용 (숨겨진 상태) -->
                        <div id="extraContent" class="space-y-2 text-gray-600 hidden">
                            <!-- 여기에 추가적인 내용을 넣을 수 있습니다 -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 자녀의 모습 피드백 -->
            <?php if (!empty($feedbackItems)): ?>
            <div class="border rounded-lg shadow bg-gray-50">
                <div class="border-b p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-blue-600 flex items-center gap-2">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor">
                                <use xlink:href="#heart"></use>
                            </svg>
                            요즘 자녀의 모습은 어떤가요?
                        </h2>
                        <button
                            class="flex items-center gap-1 text-gray-500 hover:text-blue-600"
                            onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/parental%20observations.php?userid=<?= $userid; ?>', '_blank')" >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor">
                                <use xlink:href="#edit"></use>
                            </svg>
                            수정
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        학부모님의 관찰하신 내용을 공유해주세요.
                    </p>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        <?php foreach ($feedbackItems as $index => $item): ?>
                            <div class="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm" data-index="<?= $index ?>" data-rating="<?= $item['rating'] ?>">
                                <span class="text-gray-700"><?= $item['title'] ?></span>
                                <div class="flex items-center gap-1">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <svg class="w-6 h-6 cursor-pointer <?= ($star <= $item['rating']) ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" data-star="<?= $star ?>" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <!-- 한줄 피드백 입력 -->
                       <!-- 한줄 피드백 입력 -->
                        <div class="p-3 bg-white rounded-lg shadow-sm flex items-center">
                            <input
                                type="text"
                                placeholder="한줄 피드백을 입력해 주세요"
                                class="flex-grow px-3 py-2 border rounded"
                                id="feedbackInput"
                                value="<?= htmlspecialchars($existingFeedback); ?>"
                            />
                            <button
                                class="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                id="sendFeedbackButton"
                            >
                                전달
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- 피드백 항목이 없을 경우 -->
            <div class="border rounded-lg shadow bg-gray-50 p-4">
                <p class="text-gray-600">피드백 항목이 없습니다. 피드백을 추가하려면 <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/parental%20observations.php?userid=<?= $userid; ?>" class="text-blue-500 hover:text-blue-600">여기</a>를 클릭하세요.</p>
            </div>
            <?php endif; ?>

            <!-- 하단 정보 -->
            <div class="text-center text-sm text-gray-500 space-y-1">
                <p>* 학부모님의 피드백은 더 좋은 수업을 만들기 위해 활용됩니다.</p>
                <p>* 문의: 042-489-7447 (평일 오후 2시~10시, 토 오후 12시~6시)</p>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // 발송 버튼 클릭 시 호출되는 함수
        function handleSend() {
            // 발송 확인 메시지를 표시할 요소를 생성합니다.
            var confirmDiv = document.createElement('div');
            confirmDiv.id = 'confirmDiv';
            confirmDiv.innerHTML = `
                <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="p-6 bg-white rounded-lg shadow text-center">
                        <p class="text-gray-800 mt-2">발송을 클릭하면 현재 주차의 주간 안내장이 발송됩니다.<br>발송일 기준 다음 날 오전 10시에 학부모님에게 전달이 됩니다.</p>
                        <div class="mt-6 flex justify-center space-x-4">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="confirmSend()">
                                확인
                            </button>
                            <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400" onclick="cancelSend()">
                                취소
                            </button>
                        </div>
                    </div>
                </div>
            `;
            // 페이지에 추가
            document.body.appendChild(confirmDiv);
        }

        // 확인 버튼 클릭 시 호출되는 함수
        function confirmSend() {
            console.log('confirmSend 함수 실행');

            // 피드백과 별점 평가 데이터를 수집합니다.
            var feedback = document.getElementById('feedbackInput').value;

            var ratings = {};
            var feedbackItems = document.querySelectorAll('[data-index]');
            feedbackItems.forEach(function(item) {
                var index = item.getAttribute('data-index');
                var rating = item.getAttribute('data-rating') || 0;
                ratings['rating' + (parseInt(index) + 1)] = parseInt(rating);
            });

            // 서버로 데이터 전송
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true); // 현재 페이지로 요청 전송
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            var params = `action=send_letter&userid=<?= $userid ?>&feedback=${encodeURIComponent(feedback)}&rating1=${ratings.rating1 || 0}&rating2=${ratings.rating2 || 0}&rating3=${ratings.rating3 || 0}&sesskey=${sesskey}`;

            xhr.onload = function () {
                if (xhr.status === 200) {
                    // 발송 완료 메시지 표시
                    var contentDiv = document.getElementById('content');
                    contentDiv.innerHTML = xhr.responseText;
                } else {
                    alert('오류가 발생했습니다. 다시 시도해주세요.');
                }
            };
            xhr.send(params);

            // 확인 메시지 제거
            var confirmDiv = document.getElementById('confirmDiv');
            if (confirmDiv) {
                confirmDiv.remove();
            }
        }

        // 취소 버튼 클릭 시 호출되는 함수
        function cancelSend() {
            // 확인 메시지 제거
            var confirmDiv = document.getElementById('confirmDiv');
            if (confirmDiv) {
                confirmDiv.remove();
            }
        }

        // 별점 평가 기능 및 한줄 피드백 자동 저장 기능
        document.addEventListener('DOMContentLoaded', function() {
            var feedbackItems = document.querySelectorAll('[data-index]');
            feedbackItems.forEach(function(item) {
                var index = item.getAttribute('data-index');
                var rating = item.getAttribute('data-rating') || 0;
                resetStars(item); // 초기 별점 설정

                var stars = item.querySelectorAll('svg[data-star]');
                stars.forEach(function(star) {
                    var starValue = star.getAttribute('data-star');
                    star.addEventListener('mouseenter', function() {
                        highlightStars(item, starValue);
                    });
                    star.addEventListener('mouseleave', function() {
                        resetStars(item);
                    });
                    star.addEventListener('click', function() {
                        setRating(item, starValue);
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

                // 별점 변경 시 서버에 업데이트 요청
                var index = item.getAttribute('data-index');
                var ratingField = 'rate' + (parseInt(index) + 1);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true); // 현재 페이지로 요청 전송
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                var params = `action=update_rating&userid=<?= $userid ?>&ratingField=${ratingField}&ratingValue=${starValue}&sesskey=${sesskey}`;

                xhr.onload = function () {
                    if (xhr.status !== 200) {
                        alert('별점 업데이트 중 오류가 발생했습니다.');
                    } else {
                        console.log(xhr.responseText);
                    }
                };
                xhr.send(params);
            }

           // 피드백 전달 버튼 클릭 시
           var sendFeedbackButton = document.getElementById('sendFeedbackButton');
           // 피드백 전달 버튼 클릭 시
            sendFeedbackButton.addEventListener('click', function() {
                var feedbackText = document.getElementById('feedbackInput').value;

                var ratings = {};
                var feedbackItems = document.querySelectorAll('[data-index]');
                feedbackItems.forEach(function(item) {
                    var index = item.getAttribute('data-index');
                    var rating = item.getAttribute('data-rating') || 0;
                    ratings['rating' + (parseInt(index) + 1)] = parseInt(rating);
                });

                // 서버로 데이터 전송
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true); // 현재 페이지로 요청 전송
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                // 액션 이름을 'update_feedback'으로 변경하여 PHP 코드와 일치시킴
                var params = `action=update_feedback&userid=<?= $userid ?>&feedback=${encodeURIComponent(feedbackText)}&rating1=${ratings.rating1 || 0}&rating2=${ratings.rating2 || 0}&rating3=${ratings.rating3 || 0}&sesskey=${sesskey}`;

                xhr.onload = function () {
                    if (xhr.status !== 200) {
                        alert('피드백 저장 중 오류가 발생했습니다.');
                    } else {
                        alert('피드백과 별점이 저장되었습니다.');
                    }
                };
                xhr.send(params);
            });


        // 자동 발송 토글 기능 (필요 시 구현)
        function toggleAutoSend(checkbox) {
            var isChecked = checkbox.checked;
            // 서버에 자동 발송 상태를 업데이트하는 코드를 여기에 추가하세요.
        }
    </script>
    <!-- 스타일 (필요 시 추가) -->
    <style>
        .toggle-input:checked ~ .toggle-bg {
            background-color: #86efac; /* 밝은 녹색 (green-200) */
        }
        .toggle-input:checked ~ .toggle-bg .toggle-dot {
            transform: translateX(1.25rem); /* 오른쪽으로 이동 */
            background-color: #22c55e; /* 녹색 (green-500) */
        }
    </style>
    <!-- SVG Symbols for Icons -->
    <svg style="display: none;">
        <!-- 필요한 SVG 아이콘들 추가 -->
        <!-- 예시로 이전에 제공된 아이콘들을 포함합니다 -->
        <!-- ... (SVG 아이콘 코드 생략) -->
    </svg>
</body>
</html>
