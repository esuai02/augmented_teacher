<?php
// 에러 표시 설정 (개발 단계에서만 사용)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// GET 파라미터 받기
$cid = isset($_GET["cid"]) ? intval($_GET["cid"]) : 0;
$studentid = isset($_GET["studentid"]) ? intval($_GET["studentid"]) : $USER->id;
$subjectname = isset($_GET["title"]) ? $_GET["title"] : '';

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = ?", array($USER->id, 22));
$role = $userrole->role;

if ($role !== 'student') {
    $gametools = 'https://chatgpt.com/g/g-673e48acc1d081918d4201f2154a52a1';
}

// 게임 결과 저장 처리 (AJAX 요청)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['action']) && $data['action'] === 'save_game_result') {
        $score = intval($data['score']);
        $stage = intval($data['stage']);
        $time = intval($data['time']);
        $user_id = $USER->id;

        // 게임 결과를 저장하는 테이블에 기록 삽입
        $gameResult = new stdClass();
        $gameResult->user_id = $user_id;
        $gameResult->score = $score;
        $gameResult->stage = $stage;
        $gameResult->time = $time;
        $gameResult->played_at = time();

        // 데이터베이스에 삽입
        $DB->insert_record('game_results', $gameResult);

        // 성공 응답
        echo json_encode(['success' => true]);
        exit();
    }
}
// 단원 정보 가져오기
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id = ?", array($cid));

// 새로운 게임 추가 처리
if (isset($_POST['add_game']) && $role !== 'student') {
    $newGame = new stdClass();
    $newGame->name = $_POST['game_name'];
    $newGame->subject_name = $subjectname;
    $newGame->unit_name = $curri->unit_name; // 단원명은 실제로 가져와야 합니다.
    $newGame->category = $_POST['category'];
    $newGame->icon = $_POST['icon'];
    $newGame->difficulty = $_POST['difficulty'];
    $newGame->created_at = time();
    $newGame->updated_at = time();
    $DB->insert_record('alt42_games_info', $newGame);

    // 폼 재전송 방지를 위한 리다이렉트
    header("Location: " . $_SERVER['PHP_SELF'] . "?cid={$cid}&title=" . urlencode($subjectname));
    exit();
}

// 게임 삭제 처리
if (isset($_POST['delete_game']) && $role !== 'student') {
    $deleteId = intval($_POST['delete_game_id']);
    $DB->delete_records('alt42_games_info', array('id' => $deleteId));

    // 폼 재전송 방지를 위한 리다이렉트
    header("Location: " . $_SERVER['PHP_SELF'] . "?cid={$cid}&title=" . urlencode($subjectname));
    exit();
}

// 게임 데이터 가져오기
$gamesData = $DB->get_records('alt42_games_info', array('subject_name' => $subjectname, 'unit_name' => $curri->unit_name));

// 게임 데이터를 카테고리별로 분류
$unitGames = array(
    'all' => array(),
    'formula' => array(),
    'application' => array(),
    'concept' => array()
);

foreach ($gamesData as $game) {
    // 게임별 사용자 기록 가져오기
    $userRecord = $DB->get_record('alt42_games_user_records', array('game_id' => $game->id, 'user_id' => $studentid));

    $gameInfo = array(
        'id' => $game->id,
        'name' => $game->name,
        'category' => $game->category,
        'icon' => $game->icon,
        'difficulty' => $game->difficulty,
        'myRank' => isset($userRecord->rank) ? $userRecord->rank : null,
        'totalPlayers' => $DB->count_records('alt42_games_user_records', array('game_id' => $game->id)),
        'lastPlayed' => isset($userRecord->last_played) ? date('Y-m-d', $userRecord->last_played) : null,
        'score' => isset($userRecord->score) ? $userRecord->score : 0,
        'file' => isset($game->file) ? $game->file : ''
    );

    $unitGames['all'][] = $gameInfo;
    $unitGames[$game->category][] = $gameInfo;
}

// 추천 학습 데이터 가져오기 (예시로 임의의 데이터 사용)
$recommendedGames = array(
    // 추천 게임 로직을 구현하거나 임의의 데이터를 사용
);

// 단원 전체 랭킹 데이터 가져오기
$unitRankingsData = $DB->get_records('alt42_games_unit_rankings', array('unit_name' => $curri->unit_name, 'subject_name' => $subjectname), 'rank ASC', '*', 0, 10);

$unitRankings = array();
foreach ($unitRankingsData as $ranking) {
    $unitRankings[] = array(
        'rank' => $ranking->rank,
        'name' => $ranking->user_name,
        'score' => $ranking->score,
        'avatar' => $ranking->user_avatar
    );
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Math Games</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class="p-4 max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($subjectname); ?></h1>
            <div class="h-1 w-24 bg-blue-500"></div>
        </div>
        <!-- 새로운 게임 등록 버튼 (student가 아닌 경우에만 표시) -->
        <?php if ($role !== 'student'): ?>
            <table>
                <tr>
                    <td>
                        <button>
                            <a style="color:white;font-size:1.5rem;" href="<?php echo $gametools; ?>" target="_blank">🤖</a>
                        </button>
                    </td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td>
                        <button id="toggleFormButton" class="px-4 py-2 bg-green-500 text-white rounded">
                            새로운 게임 등록
                        </button>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    </div>

    <!-- 새로운 게임 등록 폼 (student가 아닌 경우에만 표시) -->
    <?php if ($role !== 'student'): ?>
        <div id="newGameForm" class="mb-6 bg-gray-100 p-4 rounded hidden">
            <h2 class="text-xl font-bold mb-2">새로운 게임 등록</h2>
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">게임 이름</label>
                    <input type="text" name="game_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">카테고리</label>
                    <select name="category" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="formula">공식</option>
                        <option value="application">공식적용</option>
                        <option value="concept">개념성찰</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">아이콘</label>
                    <input type="text" name="icon" required placeholder="예: 🎯" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">난이도</label>
                    <select name="difficulty" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="초급">초급</option>
                        <option value="중급">중급</option>
                        <option value="고급">고급</option>
                    </select>
                </div>
                <button type="submit" name="add_game" class="px-4 py-2 bg-blue-500 text-white rounded">게임 추가</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- 왼쪽 영역 -->
        <div class="lg:col-span-3">
            <!-- 탭 네비게이션 -->
            <div>
                <div class="flex space-x-4 mb-4">
                    <button class="tab-button px-4 py-2 bg-blue-500 text-white rounded" data-tab="all">
                        <svg class="w-4 h-4 inline-block mr-2" data-lucide="book-open"></svg>전체
                    </button>
                    <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="formula">
                        <svg class="w-4 h-4 inline-block mr-2" data-lucide="calculator"></svg>공식
                    </button>
                    <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="application">
                        <svg class="w-4 h-4 inline-block mr-2" data-lucide="target"></svg>공식적용
                    </button>
                    <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="concept">
                        <svg class="w-4 h-4 inline-block mr-2" data-lucide="brain-cog"></svg>개념성찰
                    </button>
                </div>

                <!-- 탭 콘텐츠 -->
                <?php foreach ($unitGames as $category => $games): ?>
                    <div class="tab-content <?php echo $category !== 'all' ? 'hidden' : ''; ?>" id="<?php echo $category; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($games as $game): ?>
                                <div class="border rounded hover:shadow-lg transition-shadow">
                                    <div class="flex flex-row items-center space-x-4 p-4">
                                        <div class="text-3xl"><?php echo htmlspecialchars($game['icon']); ?></div>
                                        <div>
                                            <h2 class="text-lg font-bold"><?php echo htmlspecialchars($game['name']); ?></h2>
                                            <?php
                                            $badgeClass = '';
                                            if ($game['difficulty'] === '초급') {
                                                $badgeClass = 'bg-green-500 text-white';
                                            } elseif ($game['difficulty'] === '중급') {
                                                $badgeClass = 'bg-blue-500 text-white';
                                            } else {
                                                $badgeClass = 'bg-red-500 text-white';
                                            }
                                            ?>
                                            <span class="px-2 py-1 text-sm rounded <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($game['difficulty']); ?></span>
                                        </div>
                                    </div>
                                    <div class="px-4 pb-4">
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-500">진행도</span>
                                                <div class="flex items-center">
                                                    <div class="w-32 h-2 bg-gray-200 rounded-full mr-2">
                                                        <div 
                                                            class="h-full bg-blue-500 rounded-full"
                                                            style="width: <?php echo intval($game['score']); ?>%;"
                                                        ></div>
                                                    </div>
                                                    <span class="text-sm"><?php echo intval($game['score']); ?>%</span>
                                                </div>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-500">랭킹</span>
                                                <span class="font-medium"><?php echo isset($game['myRank']) ? intval($game['myRank']) : '-'; ?>/<?php echo intval($game['totalPlayers']); ?></span>
                                            </div>

                                            <?php if (!empty($game['file'])): ?>
                                                <div class="mt-2">
                                                    <audio controls>
                                                        <source src="<?php echo htmlspecialchars($game['file']); ?>" type="audio/mpeg">
                                                        Your browser does not support the audio element.
                                                    </audio>
                                                </div>
                                            <?php endif; ?>

                                            <!-- 파일 업로드 버튼 (student가 아닌 경우에만 표시) -->
                                            <?php if ($role !== 'student'): ?>
                                                <button class="mt-2 px-3 py-1 bg-blue-500 text-white rounded file-upload-button" data-game-id="<?php echo intval($game['id']); ?>">
                                                    파일 업로드
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($games)): ?>
                                <p class="text-gray-500">등록된 게임이 없습니다.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 오른쪽 영역 -->
        <div class="lg:col-span-1 space-y-6">
            <!-- 추천 학습 섹션 -->
            <div class="border rounded">
                <div class="p-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <svg class="w-5 h-5" data-lucide="thumbs-up"></svg>
                        추천 학습
                    </h2>
                </div>
                <div class="px-4 pb-4">
                    <div class="space-y-4">
                        <?php foreach ($recommendedGames as $game): ?>
                            <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors">
                                <div class="font-medium mb-1"><?php echo htmlspecialchars($game['name']); ?></div>
                                <div class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($game['description']); ?></div>
                                <div class="flex justify-between text-sm text-gray-500">
                                    <span><?php echo htmlspecialchars($game['difficulty']); ?></span>
                                    <span><?php echo htmlspecialchars($game['estimatedTime']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recommendedGames)): ?>
                            <p class="text-gray-500">추천 학습이 없습니다.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 단원 전체 랭킹 섹션 -->
            <div class="border rounded">
                <div class="p-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <svg class="w-5 h-5" data-lucide="trophy"></svg>
                        단원 전체 랭킹
                    </h2>
                </div>
                <div class="px-4 pb-4">
                    <ul class="space-y-3">
                        <?php foreach ($unitRankings as $user): ?>
                            <li class="flex items-center">
                                <span class="text-lg font-bold w-8"><?php echo intval($user['rank']); ?></span>
                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                <span class="flex-1"><?php echo htmlspecialchars($user['name']); ?></span>
                                <span class="text-sm text-gray-500"><?php echo intval($user['score']); ?>점</span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($unitRankings)): ?>
                            <p class="text-gray-500">랭킹 정보가 없습니다.</p>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lucide Icons 초기화 및 스크립트 -->
<script>
    // Lucide Icons 초기화
    lucide.createIcons();

    // 탭 기능 구현
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');

            // 모든 탭 콘텐츠 숨기기
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // 모든 탭 버튼 기본 스타일로 변경
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white');
                btn.classList.add('bg-gray-200');
            });

            // 선택된 탭 콘텐츠 보이기
            document.getElementById(targetTab).classList.remove('hidden');

            // 선택된 탭 버튼 스타일 변경
            button.classList.remove('bg-gray-200');
            button.classList.add('bg-blue-500', 'text-white');
        });
    });

    // 새로운 게임 등록 폼 토글 기능
    <?php if ($role !== 'student'): ?>
    const toggleFormButton = document.getElementById('toggleFormButton');
    const newGameForm = document.getElementById('newGameForm');

    toggleFormButton.addEventListener('click', () => {
        newGameForm.classList.toggle('hidden');
    });
    <?php endif; ?>

    // 파일 업로드 버튼 기능 구현
    document.querySelectorAll('.file-upload-button').forEach(button => {
        button.addEventListener('click', () => {
            const gameId = button.getAttribute('data-game-id');
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'audio/*';
            input.multiple = true; // 여러 파일 선택 가능하도록 설정

            input.onchange = e => {
                const file = e.target.file;
                const formData = new FormData();
                
                for (let i = 0; i < file.length; i++) {
                    formData.append('file[]', file[i]);
                }
                
                formData.append('game_id', gameId);

                fetch('file_upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // 응답 처리
                    if (data.success) {
                        alert('파일 업로드 성공');
                        // 페이지를 새로고침하여 업로드된 파일을 반영
                        location.reload();
                    } else {
                        alert('파일 업로드 실패: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('파일 업로드 중 오류가 발생했습니다.');
                });
            };

            input.click();
        });
    });
</script>
</body>
</html>
