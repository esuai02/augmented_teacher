<?php
// 개발 단계에서만 사용 (배포 시 주석 처리 혹은 제거)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Moodle 환경 설정
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// GET 파라미터로부터 게임 ID 획득
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
if ($game_id <= 0) {
    die(json_encode(['error' => '유효한 game_id가 필요합니다.']));
}

// 해당 게임 정보 가져오기
$game = $DB->get_record('alt42_games_info', ['id' => $game_id]);
if (!$game) {
    die(json_encode(['error' => '존재하지 않는 게임입니다.']));
}

// savefile 필드의 코드 읽기
$game_code = $game->savefile;

// 보안 필터링 패턴
$unsafe_patterns = [
    '/eval\s*\(/i', 
    '/exec\s*\(/i', 
    '/shell_exec\s*\(/i', 
    '/system\s*\(/i', 
    '/passthru\s*\(/i', 
    '/proc_open\s*\(/i'
];

// 보안 체크
if (preg_match_any($unsafe_patterns, $game_code)) {
    die(json_encode(['error' => '보안상 안전하지 않은 코드입니다.']));
}

// 코드 실행
ob_start();
eval("?>".$game_code);
$output = ob_get_clean();

// 실행 결과 반환 (JSON 형식 예시)
echo json_encode(['success' => true, 'output' => $output]);

// 보안 패턴 검사 함수
function preg_match_any($patterns, $subject) {
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $subject)) {
            return true;
        }
    }
    return false;
}
