<?php
header("Content-Type: text/html; charset=UTF-8");
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// 사용자 정보 및 앱 ID 확인
$user_id = $USER->id; 
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0; 

if ($game_id <= 0) {
    die("유효한 앱 ID가 필요합니다.");
}

// 앱 코드 가져오기
$game = $DB->get_record('alt42_games_info', ['id' => $game_id]);
if (!$game) {
    die("존재하지 않는 앱입니다.");
}

// 최근 플레이 기록 확인 (1주일 이내)
$one_week_ago = time() - (7 * 24 * 60 * 60);
$last_play = $DB->get_record_sql(
    "SELECT * FROM {alt42_games_user_records} 
     WHERE user_id = ? AND game_id = ? AND last_played >= ? 
     ORDER BY last_played DESC LIMIT 1",
    [$user_id, $game_id, $one_week_ago]
);

// 앱 상태: Intro 또는 이어하기
$intro_message = '';
if (!$last_play) {
    $intro_message .= "<h2>앱에 오신 것을 환영합니다!</h2>";
    $intro_message .= "<p>최근 기록이 없습니다. 새로 앱을 시작합니다.</p>";
} else {
    $intro_message .= "<h2>앱 이어하기</h2>";
    $intro_message .= "<p>마지막 기록을 이어서 플레이합니다.</p>";
}

// 보안 패턴 검사 함수
function preg_match_any($patterns, $subject) {
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $subject)) {
            return true;
        }
    }
    return false;
}

// 코드 검증 (보안 강화)
$unsafe_patterns = [
    '/eval\s*\(/i', 
    '/exec\s*\(/i', 
    '/shell_exec\s*\(/i', 
    '/system\s*\(/i', 
    '/passthru\s*\(/i', 
    '/proc_open\s*\(/i'
];
$game_code = $game->file;

// PHP 태그 제거
$game_code = preg_replace('/<\?php.*?\?>/s', '', $game_code);

if (preg_match_any($unsafe_patterns, $game_code)) {
    die("보안상 안전하지 않은 코드입니다.");
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>앱 화면</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
    .game-container {
        font-family: Arial, sans-serif;
        padding: 20px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
        max-width: 800px;
        margin: 20px auto;
        text-align: center;
    }
    #game-content {
        margin-top: 15px;
        padding: 10px;
        background: #ffffff;
        border: 1px solid #ccc;
        border-radius: 5px;
        text-align: left;
    }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="game-container">
    <?php echo $intro_message; ?>
    <div id="game-content">
        <?php
        // HTML/JS 코드 출력
        echo $game_code; 
        ?>
    </div>
</div>

 

</body>
</html>
