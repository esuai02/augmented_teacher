<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
require_login();
global $DB, $USER;

// PHP 오류 출력 설정 (개발 중에만 사용)
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
// URL에서 userid 파라미터 가져오기
$userid = required_param('userid', PARAM_INT);

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ?", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';

// 학생 이름 가져오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($userid));
$firstname = isset($username->firstname) ? $username->firstname : '';
$lastname = isset($username->lastname) ? $username->lastname : '';
$studentname = htmlspecialchars($firstname, ENT_QUOTES) . ' ' . htmlspecialchars($lastname, ENT_QUOTES);


$instructions=$DB->get_records_sql("SELECT  * FROM mdl_abessi_tracking WHERE userid='$userid' AND timecreated > '$tbegin' AND timecreated < '$tend'   ORDER BY id DESC LIMIT 100");
 

$result = json_decode(json_encode($instructions), True);
unset($value);
 
foreach($result as $value) 
	{	 
	$pomodorolist.='<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
    }

// 주차 정보 계산 (현재 날짜 기준)
$year = date('Y');
$month = date('n'); // 월 (1~12)
$week = ceil(date('j') / 7); // 해당 월의 주차 계산

// 발송 요청 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_letter') {
    require_sesskey(); // CSRF 방지

    // 데이터 수신
    $studentid = required_param('userid', PARAM_INT);
    $feedback = optional_param('feedback', '', PARAM_TEXT);
    $rating1 = optional_param('rating1', 0, PARAM_INT);
    $rating2 = optional_param('rating2', 0, PARAM_INT);
    $rating3 = optional_param('rating3', 0, PARAM_INT);
    function getWeekNumberBasedOnMondays($date) {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        $currentDate = strtotime($date);
    
        // 오늘 날짜가 속한 주의 월요일을 찾습니다.
        $weekday = date('N', $currentDate); // 1 (월요일)부터 7 (일요일)까지
        $mondayOfWeek = strtotime('-' . ($weekday - 1) . ' days', $currentDate);
        $mondayOfWeekFormatted = date('Y-m-d', $mondayOfWeek);
    
        // 만약 해당 월요일이 현재 월에 속하지 않으면 주차를 1로 설정합니다.
        if (date('n', $mondayOfWeek) != $month) {
            $weekNumber = 1;
        } else {
            // 현재 월의 모든 월요일을 구합니다.
            $mondaysInMonth = array();
            $firstMonday = strtotime("first monday of $year-$month");
    
            // 첫 번째 월요일이 현재 월에 속하는지 확인합니다.
            if (date('n', $firstMonday) != $month) {
                $firstMonday = strtotime("next monday", strtotime("$year-$month-01"));
            }
    
            $dateIterator = $firstMonday;
            while (date('n', $dateIterator) == $month) {
                $mondaysInMonth[] = date('Y-m-d', $dateIterator);
                $dateIterator = strtotime("+1 week", $dateIterator);
            }
    
            // 오늘이 속한 월요일이 몇 번째인지 찾습니다.
            $weekIndex = array_search($mondayOfWeekFormatted, $mondaysInMonth);
            if ($weekIndex === false) {
                $weekNumber = 1;
            } else {
                $weekNumber = $weekIndex + 1;
            }
        }
    
        return $weekNumber;
    }
    
    function getTotalWeeksInMonth($year, $month) {
        $mondaysInMonth = array();
        $firstMonday = strtotime("first monday of $year-$month");
    
        // 첫 번째 월요일이 현재 월에 속하는지 확인합니다.
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
                            <p>1. 자녀의 말에 귀 기울이는 것이 가장 중요합니다.</p>
                            <p>2. 판단하지 않고 공감하는 자세로 대화하세요.</p>
                            <p>3. 자녀의 감정을 인정해주고 존중해주세요...</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-blue-500 hover:text-blue-600 cursor-pointer" onclick="toggleContent()">
                                더보기
                                <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor">
                                    <use xlink:href="#chevron-right"></use>
                                </svg>
                            </div>
                            <div class="text-sm text-gray-500">
                                제공: (주) 에듀그라운드
                            </div>
                        </div>
                        <!-- 추가 내용 (숨겨진 상태) -->
                        <div id="extraContent" class="space-y-2 text-gray-600 hidden">
                            <p>4. 열린 질문을 통해 자녀의 생각을 이끌어내세요.</p>
                            <p>5. 긍정적인 피드백으로 자녀를 격려하세요.</p>
                            <!-- ...더 많은 내용 -->
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- 자녀의 모습 피드백 -->
            <?php
            $feedbackItems = [
                ['title' => '자녀의 이번주 학습 컨디션', 'rating' => 0],
                ['title' => '자녀의 최근 공부 분위기', 'rating' => 0],
                ['title' => '자녀의 수면상태', 'rating' => 0],
            ];
            ?>
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
                            onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/parental%20observations.php', '_blank')" >
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
                            <div class="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm" data-index="<?= $index ?>" data-rating="0">
                                <span class="text-gray-700"><?= $item['title'] ?></span>
                                <div class="flex items-center gap-1">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <svg class="w-6 h-6 cursor-pointer text-gray-300" fill="currentColor" data-star="<?= $star ?>" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <!-- 한줄 피드백 입력 -->
                        <div class="p-3 bg-white rounded-lg shadow-sm">
                            <input
                                type="text"
                                placeholder="한줄 피드백을 입력해 주세요"
                                class="w-full px-3 py-2 border rounded"
                                id="feedbackInput"
                            />
                        </div>
                    </div>
                </div>
            </div>
        
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

        // 별점 평가 기능
        document.addEventListener('DOMContentLoaded', function() {
            var feedbackItems = document.querySelectorAll('[data-index]');
            feedbackItems.forEach(function(item) {
                var index = item.getAttribute('data-index');
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
            }
        });

        // 더보기 기능
        function toggleContent() {
            var extraContent = document.getElementById('extraContent');
            extraContent.classList.toggle('hidden');
        }

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
        <!-- 여기에 필요한 SVG 아이콘들을 추가하세요 -->
        <!-- 예시로 이전에 제공된 아이콘들을 포함합니다 -->
        <symbol id="sun" viewBox="0 0 24 24">
            <!-- Sun icon paths -->
            <circle cx="12" cy="12" r="5"></circle>
            <path d="M12 1v2"></path>
            <path d="M12 21v2"></path>
            <path d="M4.22 4.22l1.42 1.42"></path>
            <path d="M18.36 18.36l1.42 1.42"></path>
            <path d="M1 12h2"></path>
            <path d="M21 12h2"></path>
            <path d="M4.22 19.78l1.42-1.42"></path>
            <path d="M18.36 5.64l1.42-1.42"></path>
        </symbol>
        <!-- 나머지 아이콘들도 동일하게 추가 -->
        <!-- ... -->
        <symbol id="check-circle" viewBox="0 0 24 24">
            <!-- CheckCircle icon paths -->
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9 12l2 2 4-4"></path>
        </symbol>
    </svg>
</body>
</html>
