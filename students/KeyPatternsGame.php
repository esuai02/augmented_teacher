<?php
// Include Moodle configuration files to get access to the $DB object and other configurations
include_once('/home/moodle/public_html/moodle/config.php'); // Adjust the path accordingly
include_once('/home/moodle/public_html/moodle/configwhiteboard.php');

global $DB, $USER;

session_start();

// Get parameters from the URL
$studentid = isset($_GET['userid']) ? $_GET['userid'] : null;
$cntid = isset($_GET['cntid']) ? $_GET['cntid'] : null;
$notetitle = isset($_GET['title']) ? $_GET['title'] : null;
// 추가된 부분
$cid = isset($_GET['cid']) ? $_GET['cid'] : null;
$nch = isset($_GET['nch']) ? $_GET['nch'] : null;

$fullMenuUrl = 'https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch=1&type=init&studentid='.$studentid; 

// Get the student's name
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", [$studentid]);
$stdname = $thisuser->firstname . ' ' . $thisuser->lastname;

// Initialize session variables
if (!isset($_SESSION['currentProblem'])) {
    $_SESSION['currentProblem'] = 0;
    $_SESSION['score'] = 0;
    $_SESSION['timeSpent'] = [];
    $_SESSION['startTime'] = time();
    $_SESSION['feedback'] = null;
    $_SESSION['problems'] = [];
    $_SESSION['showTimeGraph'] = false;
    $_SESSION['showCurrentAnalysis'] = false; // NEW VARIABLE

    // Build the problems array from the database
    $cntpages = $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid = '$cntid' ORDER BY pagenum ASC", [$cntid]);

    $problems = [];

    foreach ($cntpages as $cntpage) {
        // Extract necessary data
        $title = $cntpage->title;
        $npage = $cntpage->pagenum;
        $contentsid = $cntpage->id;
        $wboardid = 'jnrsorksqcrark' . $contentsid . '_user' . $studentid;

        // Build the problem array
        $problems[] = [
            'id' => $contentsid,
            'wboardid' => $wboardid,
            'contentsid' => $contentsid,
            'userid' => $studentid,
            'title' => $title,
            'pagenum' => $npage,
        ];
    }

    // Store problems in session
    $_SESSION['problems'] = $problems;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentProblem = $_SESSION['currentProblem'];

    if (isset($_POST['complete'])) {
        $timeElapsed = time() - $_SESSION['startTime'];

        // Record time spent on the problem
        $_SESSION['timeSpent'][] = [
            'problem' => $currentProblem + 1,
            'time' => $timeElapsed
        ];

        // When 'Complete' button is pressed, record whiteboard activity
        $wboardid = $_SESSION['problems'][$currentProblem]['wboardid'];
        $userid = $_SESSION['problems'][$currentProblem]['userid'];

        // Update feedback and score
        $_SESSION['feedback'] = 'completed';
        $_SESSION['score'] += 1; // Increment score or handle as needed
        $_SESSION['showCurrentAnalysis'] = true; // SHOW ANALYSIS FOR CURRENT PROBLEM
    }

    // Handle "Next Problem" action
    if (isset($_POST['next'])) {
        $_SESSION['currentProblem'] += 1;
        $_SESSION['feedback'] = null;
        $_SESSION['startTime'] = time();
        $_SESSION['showCurrentAnalysis'] = false; // HIDE CURRENT ANALYSIS
    }

    // Handle "Show Time Analysis" action
    if (isset($_POST['toggleTimeGraph'])) {
        $_SESSION['showTimeGraph'] = !$_SESSION['showTimeGraph'];
    }

    // Reset the game
    if (isset($_POST['reset'])) {
        session_unset();
        header("Location: " . $_SERVER['PHP_SELF'] . "?userid=$studentid&cntid=$cntid&title=" . urlencode($notetitle));
        exit();
    }
}

// Get current problem
$currentProblemIndex = $_SESSION['currentProblem'];
$problemCount = count($_SESSION['problems']);

if ($currentProblemIndex < $problemCount) {
    $problem = $_SESSION['problems'][$currentProblemIndex];
} else {
    // Game completed
    $gameCompleted = true;
}

function getWhiteboardUrl($problem) {
    return "https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id={$problem['wboardid']}&contentsid={$problem['contentsid']}&studentid={$problem['userid']}";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($notetitle); ?> - <?php echo htmlspecialchars($stdname); ?></title>
    <!-- Include stylesheets and scripts as needed -->
    <style>
        /* Basic styling */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevent body scrollbars */
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            display: flex;
            height: 100%;
            max-width: 100%;
            margin: 0 auto;
            position: relative;
        }
        .left-column {
            flex: 1; /* Take full width when right column is hidden */
            display: flex;
            flex-direction: column;
        }
        .right-column {
            width: 300px; /* Fixed width for the right column */
            padding: 10px;
            overflow-y: auto;
            background-color: #ffffff;
            border-left: 1px solid #ccc;
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            transition: transform 0.3s ease-in-out;
            /* Initially visible */
            transform: translateX(0);
        }
        /* Hidden state */
        .right-column.hidden {
            transform: translateX(100%);
        }
        /* Adjust left column when right column is visible */
        .right-column:not(.hidden) ~ .left-column {
            margin-right: 300px; /* Same as right column width */
        }
        .iframe-container {
            flex: 1;
            position: relative;
        }
        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .progress-bar {
            background-color: #e0e0e0;
            border-radius: 13px;
            overflow: hidden;
            margin-bottom: 20px;
            width: 100%;
        }
        .progress-bar-fill {
            height: 10px;
            background-color: #76c7c0;
            width: <?php echo ($currentProblemIndex / $problemCount) * 100; ?>%;
        }
        .feedback {
            margin-top: 10px;
        }
        .feedback.completed {
            color: green;
        }
        .time-graph {
            margin-top: 20px;
        }
        .time-graph table {
            width: 100%;
            border-collapse: collapse;
        }
        .time-graph th, .time-graph td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .time-graph th {
            background-color: #f2f2f2;
        }
        .game-complete {
            text-align: center;
            padding: 20px;
            background-color: #e0ffe0;
            margin: 20px;
            border-radius: 8px;
        }
        .game-complete h2 {
            margin-top: 0;
        }
        .button {
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #76c7c0;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .button-outline {
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #fff;
            color: #76c7c0;
            border: 2px solid #76c7c0;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .button-outline:hover {
            background-color: #76c7c0;
            color: #fff;
        }
        .button:hover {
            background-color: #5aa5a0;
        }
        .header {
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 18px;
        }
        /* Scrollbar Styling */
        /* WebKit Browsers */
        .right-column::-webkit-scrollbar {
            width: 12px;
        }
        .right-column::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }
        .right-column::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 6px;
        }
        .right-column::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        /* Firefox */
        .right-column {
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }
        /* Menu Toggle Button */
        .menu-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background-color: #76c7c0;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .menu-toggle:hover {
            background-color: #5aa5a0;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .right-column {
                max-width: none;
                width: 100%;
                border-left: none;
                border-top: 1px solid #ccc;
                position: relative;
                transform: translateX(0);
            }
            .left-column {
                height: 50%;
                margin-right: 0;
            }
            .menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- 수정된 부분: right-column에 'visible' 클래스를 추가하여 페이지 로딩 시 메뉴가 펼쳐진 상태로 표시됩니다. -->
<div class="container">
    <!-- Left Column: Whiteboard -->
    <div class="left-column" id="left-column">
        <?php if (isset($gameCompleted) && $gameCompleted): ?>
            <div class="game-complete">
                <h2>활동 완료!</h2>
                <p>총 문제 수: <?php echo $problemCount; ?></p>
                <p>완료한 문제 수: <?php echo $_SESSION['score']; ?></p>
                <form method="post">
                    <button type="submit" name="reset" class="button">처음부터 다시 시작</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Whiteboard Iframe -->
            <div class="iframe-container">
                <iframe src="<?php echo getWhiteboardUrl($problem); ?>" id="whiteboard-iframe"></iframe>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Controls and Information -->
    <!-- 여기에서 'visible' 클래스를 추가하여 메뉴를 펼쳐진 상태로 만듭니다. -->
    <div class="right-column visible" id="right-column">
        <div class="header">
        <h1><?php echo htmlspecialchars($problem['title']); ?></h1>
        <h2>학생: <?php echo htmlspecialchars($stdname); ?></h2>
        </div>

        <?php if (!isset($gameCompleted) || !$gameCompleted): ?>
            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-bar-fill"></div>
            </div>

            <!-- Problem Info -->
            <div style="margin-bottom: 20px;">
                <strong>문제 <?php echo $currentProblemIndex + 1; ?> / <?php echo $problemCount; ?></strong>
            </div>

<!-- Complete Button -->
<?php if (!isset($_SESSION['feedback'])): ?>
    <form method="post">
        <button type="submit" name="complete" class="button">완료</button>
    </form>
    <!-- 새로운 버튼 추가 -->
    <a href="<?php echo $fullMenuUrl; ?>" class="button-outline">목차보기</a>
<?php endif; ?>


            <!-- Feedback and Current Time Analysis -->
            <?php if (isset($_SESSION['feedback']) && $_SESSION['feedback'] == 'completed'): ?>
                <div class="feedback completed">
                    ✅ 현재 문제에 대한 활동이 기록되었습니다.
                </div>
                <?php if ($_SESSION['showCurrentAnalysis']): ?>
                    <!-- Display Time Analysis for Current Problem -->
                    <div class="time-graph">
                        <h3>현재 문제 소요 시간 (초)</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>문제 번호</th>
                                    <th>소요 시간 (초)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get the last entry from timeSpent array
                                $lastTimeData = end($_SESSION['timeSpent']);
                                ?>
                                <tr>
                                    <td><?php echo $lastTimeData['problem']; ?></td>
                                    <td><?php echo $lastTimeData['time']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Actions -->
            <?php if (isset($_SESSION['feedback'])): ?>
                <form method="post">
                    <?php if ($currentProblemIndex < $problemCount - 1): ?>
                        <button type="submit" name="next" class="button">다음 문제</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- Toggle Time Analysis -->
            <form method="post">
                <button type="submit" name="toggleTimeGraph" class="button-outline">
                    <?php echo $_SESSION['showTimeGraph'] ? '전체 시간 분석 숨기기' : '전체 시간 분석 보기'; ?>
                </button>
            </form>

            <!-- Overall Time Analysis -->
            <?php if ($_SESSION['showTimeGraph'] && !empty($_SESSION['timeSpent'])): ?>
                <div class="time-graph">
                    <h3>전체 문제별 소요 시간 (초)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>문제 번호</th>
                                <th>소요 시간 (초)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['timeSpent'] as $timeData): ?>
                                <tr>
                                    <td><?php echo $timeData['problem']; ?></td>
                                    <td><?php echo $timeData['time']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Menu Toggle Icon -->
<button class="menu-toggle" id="menu-toggle">&#9776;</button>

<script>
    // JavaScript to handle the menu show/hide functionality
    let rightColumn = document.getElementById('right-column');
    let menuToggle = document.getElementById('menu-toggle');
    let leftColumn = document.getElementById('left-column');
    let iframe = document.getElementById('whiteboard-iframe');

    // 초기에는 메뉴가 펼쳐진 상태이므로 'hidden' 클래스를 제거합니다.
    function hideRightColumn() {
        rightColumn.classList.add('hidden');
    }

    function showRightColumn() {
        rightColumn.classList.remove('hidden');
    }

    // 아이콘에 마우스 오버 시 메뉴가 펼쳐집니다.
    menuToggle.addEventListener('mouseover', function() {
        showRightColumn();
    });

    // 화이트보드 영역 클릭 또는 필기 시 메뉴가 닫힙니다.
    function addIframeEventListener() {
        iframe.contentWindow.document.addEventListener('mousedown', function() {
            hideRightColumn();
        });
    }

    // Iframe이 로드된 후 이벤트 리스너를 추가합니다.
    iframe.addEventListener('load', function() {
        addIframeEventListener();
    });

    // 메뉴 영역 바깥을 클릭하면 메뉴가 닫힙니다.
    leftColumn.addEventListener('click', function() {
        hideRightColumn();
    });

    // 메뉴가 열려 있을 때 메뉴 바깥을 클릭하면 메뉴가 닫힙니다.
    document.addEventListener('click', function(event) {
        if (!rightColumn.contains(event.target) && !menuToggle.contains(event.target)) {
            hideRightColumn();
        }
    });

    // 메뉴 영역에 마우스 오버 시 메뉴가 닫히지 않도록 합니다.
    rightColumn.addEventListener('mouseenter', function() {
        // 메뉴 닫힘 방지
    });

    // 메뉴 영역에서 마우스가 떠나면 메뉴를 닫습니다.
    rightColumn.addEventListener('mouseleave', function() {
        hideRightColumn();
    });
</script>

</body>
</html>
