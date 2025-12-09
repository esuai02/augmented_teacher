<?php

// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// cid 고정
$studentid=$_GET["userid"]; 
$cid = 7128;
$studentid = isset($_GET["studentid"]) ? intval($_GET["studentid"]) : $USER->id;
$subjectname = 'KTM Apps';

// 유저 역할 가져오기
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = ?", array($USER->id, 22));
$role = $userrole->role;

// 관리자 이상의 역할일 때만 gametools 링크 표시
if ($role !== 'student') {
    $gametools = 'https://chatgpt.com/g/g-675fbd348b148191b39451411ac63f80';
}

// AJAX 처리 부분
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'save_game_result') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (isset($data['action']) && $data['action'] === 'save_game_result') {
            $score = intval($data['score']);
            $stage = intval($data['stage']);
            $time = intval($data['time']);
            $user_id = $USER->id;

            // 앱 결과 삽입
            $gameResult = new stdClass();
            $gameResult->user_id = $user_id;
            $gameResult->score = $score;
            $gameResult->stage = $stage;
            $gameResult->time = $time;
            $gameResult->played_at = time();
            $DB->insert_record('game_results', $gameResult);

            echo json_encode(['success' => true]);
            exit();
        }

    } else if ($action === 'save_php_code') {
        // 파일 저장 처리 (file, savefile, appurl 세 개 필드)
        $file_input = isset($_POST['file']) ? $_POST['file'] : '';
        $savefile_input = isset($_POST['savefile']) ? $_POST['savefile'] : '';
        $appurl_input = isset($_POST['appurl']) ? $_POST['appurl'] : '';
        $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;

        // 유효한 game_id인지 확인
        if ($game_id > 0) {
            $record = $DB->get_record('alt42_games_info', array('id' => $game_id));
            if ($record) {
                // file, savefile, appurl 칼럼에 값 저장
                $record->file = $file_input;
                $record->savefile = $savefile_input;
                $record->appurl = $appurl_input; // appurl 업데이트
                $record->updated_at = time(); // updated_at 칼럼이 존재한다고 가정
                $DB->update_record('alt42_games_info', $record);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '해당 앱을 찾을 수 없습니다.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '앱ID가 유효하지 않습니다.']);
        }
        exit();
    }
}

// 단원 정보 가져오기
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id = ?", array($cid));

// 새로운 앱 추가 처리
if (isset($_POST['add_game']) && $role !== 'student') {
    $newGame = new stdClass();
    $newGame->name = $_POST['game_name'];
    $newGame->subject_name = $subjectname;
    $newGame->unit_name = $curri->unit_name; // 실제 단원명을 가져왔다고 가정
    $newGame->category = $_POST['category'];
    $newGame->icon = $_POST['icon'];
    $newGame->stage = $_POST['stage'];
    $newGame->created_at = time();
    $newGame->updated_at = time();
    $DB->insert_record('alt42_games_info', $newGame);

    header("Location: " . $_SERVER['PHP_SELF'] . "?cid={$cid}&title=" . urlencode($subjectname));
    exit();
}

// ★ 추가/수정: 앱 업데이트 처리
if (isset($_POST['update_game']) && $role !== 'student') {
    $updateId = intval($_POST['update_game_id']);
    $record = $DB->get_record('alt42_games_info', array('id' => $updateId));
    if ($record) {
        $record->name = $_POST['game_name'];
        $record->category = $_POST['category'];
        $record->icon = $_POST['icon'];
        $record->stage = $_POST['stage'];
        $record->updated_at = time();
        $DB->update_record('alt42_games_info', $record);
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?cid={$cid}&title=" . urlencode($subjectname));
    exit();
}

// 앱 삭제 처리
if (isset($_POST['delete_game']) && $role !== 'student') {
    $deleteId = intval($_POST['delete_game_id']);
    $DB->delete_records('alt42_games_info', array('id' => $deleteId));

    header("Location: " . $_SERVER['PHP_SELF'] . "?cid=<?php echo $cid; ?>&title=" . urlencode($subjectname));
    exit();
}

// 앱 데이터 가져오기
$gamesData = $DB->get_records('alt42_games_info', array(
    'subject_name' => $subjectname,
    'unit_name' => $curri->unit_name
));

// 앱 데이터 분류
$unitGames = array(
    'all' => array(),
    'desk' => array(),
    'teacher' => array(),
    'student' => array(),
    'parent' => array()
);

foreach ($gamesData as $game) {
    $userRecord = $DB->get_record('alt42_games_user_records', array('game_id' => $game->id, 'user_id' => $studentid));

    $gameInfo = array(
        'id' => $game->id,
        'name' => $game->name,
        'category' => $game->category,
        'icon' => $game->icon,
        'stage' => $game->stage,
        'myRank' => isset($userRecord->rank) ? $userRecord->rank : null,
        'totalPlayers' => $DB->count_records('alt42_games_user_records', array('game_id' => $game->id)),
        'lastPlayed' => isset($userRecord->last_played) ? date('Y-m-d', $userRecord->last_played) : null,
        'score' => isset($userRecord->score) ? $userRecord->score : 0,
        'file' => isset($game->file) ? $game->file : '',
        'savefile' => isset($game->savefile) ? $game->savefile : '',
        'appurl' => isset($game->appurl) ? $game->appurl : ''
    );

    $unitGames['all'][] = $gameInfo;
    $unitGames[$game->category][] = $gameInfo;
}

// 추천 학습 데이터 (예시)
$recommendedGames = array();

// 앱사용 빈도
$unitRankingsData = $DB->get_records('alt42_games_unit_rankings', array(
    'unit_name' => $curri->unit_name,
    'subject_name' => $subjectname
), 'rank ASC', '*', 0, 10);

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
    <title>KTM Apps</title>
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
        <?php if ($role !== 'student'): ?>
            <table>
                <tr>
                    <td>
                        <button>
                            <a style="color:black;font-size:1.2rem;" href="<?php echo $gametools; ?>" target="_blank">🤖 개발도구</a>
                        </button>
                    </td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td>
                        <button id="toggleFormButton" class="px-4 py-2 bg-green-500 text-white rounded">
                            새로운 앱 등록
                        </button>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($role !== 'student'): ?>
        <!-- ★ add_game와 update_game를 모두 처리할 폼 -->
        <div id="newGameForm" class="mb-6 bg-gray-100 p-4 rounded hidden">
            <h2 id="formTitle" class="text-xl font-bold mb-2">새로운 앱 등록</h2>
            <form method="post" class="space-y-4" id="appForm">
                <!-- ★ 신규/수정 식별용 hidden 필드 -->
                <input type="hidden" name="update_game_id" id="update_game_id" value="" />

                <div>
                    <label class="block text-sm font-medium text-gray-700">앱 이름</label>
                    <input type="text" name="game_name" id="game_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">카테고리</label>
                    <select name="category" id="category" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="desk">데스크</option>
                        <option value="teacher">선생님</option>
                        <option value="student">학생</option>
                        <option value="parent">학부모</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">아이콘</label>
                    <input type="text" name="icon" id="icon" required placeholder="예: 🎯" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">개발단계</label>
                    <select name="stage" id="stage" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="디자인">디자인</option>
                        <option value="피드백수집">피드백수집</option>
                        <option value="디자인확정">디자인확정</option>
                        <option value="DB연결">DB연결</option>
                        <option value="시범운영">시범운영</option>
                        <option value="배포">배포완료</option> 
                    </select>
                </div>

                <!-- ★ add_game, update_game 구분 -->
                <button type="submit" name="add_game" id="addGameBtn" class="px-4 py-2 bg-blue-500 text-white rounded">앱 추가</button>
                <button type="submit" name="update_game" id="updateGameBtn" class="px-4 py-2 bg-purple-500 text-white rounded hidden">앱 수정</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-3">
            <div>
                <div class="flex space-x-4 mb-4">
                    <!-- ★ 학생(role=student)이면 학생 탭만 보여주기 -->
                    <?php if ($role === 'student'): ?>
                        <button class="tab-button px-4 py-2 bg-blue-500 text-white rounded" data-tab="student">
                            <svg class="w-4 h-4 inline-block mr-2" data-lucide="graduation-cap"></svg>학생
                        </button>
                    <?php else: ?>
                        <button class="tab-button px-4 py-2 bg-blue-500 text-white rounded" data-tab="all">
                            <svg class="w-4 h-4 inline-block mr-2" data-lucide="book-open"></svg>전체
                        </button>
                        <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="desk">
                            <svg class="w-4 h-4 inline-block mr-2" data-lucide="monitor"></svg>데스크
                        </button>
                        <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="teacher">
                            <svg class="w-4 h-4 inline-block mr-2" data-lucide="user-check"></svg>선생님
                        </button>
                        <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="student">
                            <svg class="w-4 h-4 inline-block mr-2" data-lucide="graduation-cap"></svg>학생
                        </button>
                        <button class="tab-button px-4 py-2 bg-gray-200 rounded" data-tab="parent">
                            <svg class="w-4 h-4 inline-block mr-2" data-lucide="home"></svg>학부모
                        </button>
                    <?php endif; ?>
                </div>

                <?php foreach ($unitGames as $category => $games): ?>
                    <div class="tab-content
                        <?php
                            if ($role === 'student') {
                                echo ($category !== 'student') ? 'hidden' : '';
                            } else {
                                echo ($category !== 'all') ? 'hidden' : '';
                            }
                        ?>"
                        id="<?php echo $category; ?>"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($games as $game): ?>
                                <div class="border rounded hover:shadow-lg transition-shadow">
                                    <div class="flex flex-row items-center space-x-4 p-4">
                                        <div class="text-3xl"><?php echo htmlspecialchars($game['icon']); ?></div>
                                        <div>
                                        <h2 class="text-lg font-bold inline-flex items-center gap-2">
                                            <?php
                                            $badgeClass = '';
                                            if ($game['stage'] === '디자인') {
                                                $badgeClass = 'bg-yellow-500 text-black';
                                            } elseif ($game['stage'] === '피드백수집') {
                                                $badgeClass = 'bg-gray-500 text-white';
                                            } elseif ($game['stage'] === '디자인확정') {
                                                $badgeClass = 'bg-green-700 text-white';
                                            } elseif ($game['stage'] === 'DB연결') {
                                                $badgeClass = 'bg-blue-500 text-white';
                                            } elseif ($game['stage'] === '시범운영') {
                                                $badgeClass = 'bg-purple-500 text-white';
                                            } elseif ($game['stage'] === '배포완료') {
                                                $badgeClass = 'bg-red-500 text-white';
                                            } else {
                                                $badgeClass = 'bg-black text-white';
                                            }
                                            ?>
                                            <span class="px-2 py-1 text-sm rounded <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($game['stage']); ?>
                                            </span></h2>
                                        </div>
                                    </div>
                                    <div class="px-4 pb-4">
                                        <div class="space-y-2">
                                            <?php if ($role !== 'student'): ?>
                                                <div class="flex space-x-2 mt-2">
                                                    <button 
                                                        class="px-3 py-1 bg-blue-500 text-white rounded code-input-button" 
                                                        data-game-id="<?php echo intval($game['id']); ?>"
                                                        data-game-file="<?php echo htmlspecialchars($game['file']); ?>"
                                                        data-game-savefile="<?php echo htmlspecialchars($game['savefile']); ?>"
                                                        data-game-appurl="<?php echo htmlspecialchars($game['appurl']); ?>"
                                                        data-name="<?php echo htmlspecialchars($game['name']); ?>"
                                                        data-category="<?php echo htmlspecialchars($game['category']); ?>"
                                                        data-icon="<?php echo htmlspecialchars($game['icon']); ?>"
                                                        data-stage="<?php echo htmlspecialchars($game['stage']); ?>"
                                                    >
                                                        앱 업데이트
                                                    </button>
                                                    <button 
                                                        class="px-3 py-1 bg-purple-500 text-white rounded play-button" 
                                                        data-game-id="<?php echo intval($game['id']); ?>"
                                                        data-game-appurl="<?php echo htmlspecialchars($game['appurl']); ?>"
                                                    >
                                                        앱 실행하기
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex space-x-2 mt-2">
                                                    <button 
                                                        class="px-3 py-1 bg-purple-500 text-white rounded play-button" 
                                                        data-game-id="<?php echo intval($game['id']); ?>"
                                                        data-game-appurl="<?php echo htmlspecialchars($game['appurl']); ?>"
                                                    >
                                                        앱 실행하기
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($games)): ?>
                                <p class="text-gray-500">등록된 앱이 없습니다.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="border rounded">
                <div class="p-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <svg class="w-5 h-5" data-lucide="thumbs-up"></svg>
                        추천 앱
                    </h2>
                </div>
                <div class="px-4 pb-4">
                    <div class="space-y-4">
                        <?php if (empty($recommendedGames)): ?>
                            <p class="text-gray-500">추천 학습이 없습니다.</p>
                        <?php else: ?>
                            <?php foreach ($recommendedGames as $rgame): ?>
                                <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors">
                                    <div class="font-medium mb-1"><?php echo htmlspecialchars($rgame['name']); ?></div>
                                    <div class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($rgame['description']); ?></div>
                                    <div class="flex justify-between text-sm text-gray-500">
                                        <span><?php echo htmlspecialchars($rgame['stage']); ?></span>
                                        <span><?php echo htmlspecialchars($rgame['estimatedTime']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="border rounded">
                <div class="p-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <svg class="w-5 h-5" data-lucide="trophy"></svg>
                        앱 사용 랭킹
                    </h2>
                </div>
                <div class="px-4 pb-4">
                    <ul class="space-y-3">
                        <?php if (empty($unitRankings)): ?>
                            <p class="text-gray-500">랭킹 정보가 없습니다.</p>
                        <?php else: ?>
                            <?php foreach ($unitRankings as $user): ?>
                                <li class="flex items-center">
                                    <span class="text-lg font-bold w-8"><?php echo intval($user['rank']); ?></span>
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                    <span class="flex-1"><?php echo htmlspecialchars($user['name']); ?></span>
                                    <span class="text-sm text-gray-500"><?php echo intval($user['score']); ?>점</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Lucide Icons 초기화
    lucide.createIcons();

    // 탭 기능
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');

            // 모든 탭 내용 숨김
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // 모든 탭 버튼 기본 스타일로 변경
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white');
                btn.classList.add('bg-gray-200');
            });

            // 클릭한 탭만 보여주고 버튼 스타일 변경
            document.getElementById(targetTab).classList.remove('hidden');
            button.classList.remove('bg-gray-200');
            button.classList.add('bg-blue-500', 'text-white');
        });
    });

    <?php if ($role !== 'student'): ?>
    const toggleFormButton = document.getElementById('toggleFormButton');
    const newGameForm = document.getElementById('newGameForm');
    const formTitle = document.getElementById('formTitle');
    const updateGameIdInput = document.getElementById('update_game_id');
    const addGameBtn = document.getElementById('addGameBtn');
    const updateGameBtn = document.getElementById('updateGameBtn');

    const gameNameInput = document.getElementById('game_name');
    const categoryInput = document.getElementById('category');
    const iconInput = document.getElementById('icon');
    const stageInput = document.getElementById('stage');

    toggleFormButton.addEventListener('click', () => {
    formTitle.textContent = '새로운 앱 등록';
    updateGameIdInput.value = '';
    addGameBtn.classList.remove('hidden');
    updateGameBtn.classList.add('hidden');

    // 입력필드 초기화
    gameNameInput.value = '';
    categoryInput.value = 'desk';
    iconInput.value = '🧭'; // ★ 수정 : 기본값 🧭
    stageInput.value = '디자인';

    newGameForm.classList.toggle('hidden');
});
    <?php endif; ?>

    // 앱 업데이트 버튼(파일입력) 모달
    document.querySelectorAll('.code-input-button').forEach(button => {
        button.addEventListener('click', () => {
            const gameId = button.getAttribute('data-game-id');
            const existingFile = button.getAttribute('data-game-file') || '';
            const existingSavefile = button.getAttribute('data-game-savefile') || '';
            const existingAppurl = button.getAttribute('data-game-appurl') || '';

            // ★ 추가: 앱 정보
            const existingName = button.getAttribute('data-name') || '';
            const existingCategory = button.getAttribute('data-category') || '';
            const existingIcon = button.getAttribute('data-icon') || '';
            const existingStage = button.getAttribute('data-stage') || '';

            // 폼 표시(수정모드로)
            newGameForm.classList.remove('hidden');
            formTitle.textContent = '앱 수정';
            updateGameIdInput.value = gameId;
            addGameBtn.classList.add('hidden');
            updateGameBtn.classList.remove('hidden');

            // 앱 기본 정보 세팅
            gameNameInput.value = existingName;
            categoryInput.value = existingCategory;
            iconInput.value = existingIcon;
            stageInput.value = existingStage;

            // 파일 입력 모달 생성
            const modal = document.createElement('div');
            modal.classList = "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50";
            modal.innerHTML = `
                <div class="bg-white p-6 rounded shadow-xl w-11/12 max-w-2xl">
                    <h2 class="text-xl font-bold mb-4">파일입력</h2>
                    <label class="block text-sm font-medium text-gray-700 mb-1">playgame.php (앱 파일)</label>
                    <textarea id="fileInput" class="w-full h-32 border border-gray-300 rounded p-2 mb-4"
                        placeholder="여기에 file 내용을 입력하세요"></textarea>

                    <label class="block text-sm font-medium text-gray-700 mb-1">savefile.php (DB 저장용 파일)</label>
                    <textarea id="savefileInput" class="w-full h-32 border border-gray-300 rounded p-2 mb-4"
                        placeholder="여기에 savefile 내용을 입력하세요"></textarea>

                    <!-- 새로 추가된 appurl 입력 -->
                    <label class="block text-sm font-medium text-gray-700 mb-1">appurl</label>
                    <input type="text" id="appurlInput" class="w-full border border-gray-300 rounded p-2"
                        placeholder="앱 실행 URL을 입력하세요" />

                    <div class="flex justify-end space-x-4 mt-4">
                        <button id="saveCodeButton" class="px-4 py-2 bg-blue-500 text-white rounded">저장</button>
                        <button id="cancelButton" class="px-4 py-2 bg-gray-300 text-black rounded">취소</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const fileTextarea = modal.querySelector('#fileInput');
            const savefileTextarea = modal.querySelector('#savefileInput');
            const appurlInput = modal.querySelector('#appurlInput');

            // 기존 값 적용
            fileTextarea.value = existingFile;
            savefileTextarea.value = existingSavefile;
            appurlInput.value = existingAppurl;

            // 저장 버튼
            const saveButton = modal.querySelector('#saveCodeButton');
            // 취소 버튼
            const cancelButton = modal.querySelector('#cancelButton');

            cancelButton.addEventListener('click', () => {
                document.body.removeChild(modal);
            });

            saveButton.addEventListener('click', () => {
                const fileData = fileTextarea.value;
                const savefileData = savefileTextarea.value;
                const appurlData = appurlInput.value;

                const formData = new FormData();
                formData.append('action', 'save_php_code');
                formData.append('file', fileData);
                formData.append('savefile', savefileData);
                formData.append('appurl', appurlData);
                formData.append('game_id', gameId);

                fetch('<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $cid; ?>&title=<?php echo urlencode($subjectname); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('파일이 성공적으로 저장되었습니다.');
                        location.reload();
                    } else {
                        alert('파일 저장 실패: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('파일 저장 중 오류가 발생했습니다.');
                });
            });
        });
    });

    // 앱 실행하기 버튼
    document.querySelectorAll('.play-button').forEach(button => {
        button.addEventListener('click', () => {
            const gameId = button.getAttribute('data-game-id');
            const appurl = button.getAttribute('data-game-appurl') || '';

            // appurl이 비어있지 않은 경우 appurl로 이동, 없으면 기존 playapp.php 링크
            if (appurl.trim() !== '') {
                window.open(appurl, '_self');
            } else {
                const url = `https://mathking.kr/moodle/local/augmented_teacher/alt42/apps/playapp.php?game_id=${gameId}`;
                window.open(url, '_self');
            }
        });
    });
</script>
</body>
</html>
