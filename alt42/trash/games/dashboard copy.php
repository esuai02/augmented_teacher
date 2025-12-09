<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
require_login();

// 1. 사용자 정보 및 게임 ID 확인
$user_id = $USER->id; 
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0; 

if ($game_id <= 0) {
    die("유효한 게임 ID가 필요합니다.");
}

// 2. 게임 코드 가져오기
$game = $DB->get_record('alt42_games_info', ['id' => $game_id]);
if (!$game) {
    die("존재하지 않는 게임입니다.");
}

// 3. 최근 플레이 기록 확인 (1주일 이내)
$one_week_ago = time() - (7 * 24 * 60 * 60);
$last_play = $DB->get_record_sql(
    "SELECT * FROM {alt42_games_unit_user_records} 
     WHERE user_id = ? AND game_id = ? AND last_played >= ? 
     ORDER BY last_played DESC LIMIT 1",
    [$user_id, $game_id, $one_week_ago]
);

// 4. 게임 상태: Intro 또는 이어하기
if (!$last_play) {
    // Intro 화면 표시
    echo "<h2>게임에 오신 것을 환영합니다!</h2>";
    echo "<p>최근 기록이 없습니다. 새로 게임을 시작합니다.</p>";
    $new_game = true;
} else {
    // 이어하기 화면 표시
    echo "<h2>게임 이어하기</h2>";
    echo "<p>마지막 기록을 이어서 플레이합니다.</p>";
    $new_game = false;
}

// 5. 코드 검증 및 실행 (보안 강화)
$unsafe_patterns = ['/eval\s*\(/i', '/exec\s*\(/i', '/shell_exec\s*\(/i', '/system\s*\(/i', '/passthru\s*\(/i', '/proc_open\s*\(/i'];
$game_code = $game->file;
if (preg_match_any($unsafe_patterns, $game_code)) {
    die("보안상 안전하지 않은 코드입니다.");
}

// 6. 안전한 코드 출력 및 실행
if (!empty($game_code)) {
    echo "<div class='game-container'>";
    echo "<h3>{$game->name} - {$game->unit_name}</h3>";
    echo "<hr>";
    
    // 게임 코드 출력 (HTML 및 JS 안전 렌더링)
    echo "<div id='game-content'>";
    echo htmlspecialchars($game_code, ENT_QUOTES, 'UTF-8');
    echo "</div>";

    echo "</div>";
} else {
    echo "게임 코드가 비어있습니다.";
}

// 7. 보안 패턴 검사 함수
function preg_match_any($patterns, $subject) {
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $subject)) {
            return true;
        }
    }
    return false;
}

?>

<!-- 게임 CSS 및 JS 추가 -->
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
}

/* 코드 입력 창 스타일 */
#code-input-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}
#code-input-content {
    background-color: white;
    padding: 20px;
    width: 90%;
    height: 80%;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
textarea {
    width: 100%;
    height: 70%;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    font-family: monospace;
}
</style>

<!-- 코드 입력 버튼 및 모달 -->
<div class="p-4 text-center">
    <button id="code-input-button" class="px-4 py-2 bg-blue-500 text-white rounded">코드 입력</button>
</div>
<div id="code-input-modal" class="flex">
    <div id="code-input-content">
        <h2 class="text-xl font-bold mb-4">PHP 코드 입력</h2>
        <textarea id="code-textarea" placeholder="PHP 코드를 입력하세요..."></textarea>
        <button id="save-code-button" class="px-4 py-2 bg-green-500 text-white rounded">저장</button>
        <button id="close-modal-button" class="px-4 py-2 bg-red-500 text-white rounded">닫기</button>
    </div>
</div>

<!-- JavaScript 코드 -->
<script>
// 모달 열기/닫기 기능
const modal = document.getElementById('code-input-modal');
const openModalButton = document.getElementById('code-input-button');
const closeModalButton = document.getElementById('close-modal-button');
const saveCodeButton = document.getElementById('save-code-button');
const codeTextarea = document.getElementById('code-textarea');

openModalButton.addEventListener('click', () => {
    modal.style.display = 'flex';
});
closeModalButton.addEventListener('click', () => {
    modal.style.display = 'none';
});

// AJAX를 통해 코드 저장
saveCodeButton.addEventListener('click', () => {
    const code = codeTextarea.value;

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'save_code', game_id: <?php echo $game_id; ?>, code: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('코드가 성공적으로 저장되었습니다!');
            modal.style.display = 'none';
        } else {
            alert('저장 실패: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('저장 중 오류가 발생했습니다.');
    });
});
</script>
