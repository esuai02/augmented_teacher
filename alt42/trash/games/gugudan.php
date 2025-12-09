<?php
// 에러 표시 설정 (개발 단계에서만 사용)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 게임 ID, 과목명, 단원명 설정 (실제 값으로 설정해야 합니다)
$game_id = 1; // 실제 게임 ID로 설정하세요
$subject_name = '수학'; // 실제 과목명으로 설정하세요
$unit_name = '구구단'; // 실제 단원명으로 설정하세요

// 게임 결과 저장 처리 (AJAX 요청)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON 입력 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 디버깅을 위한 로그 출력
    // var_dump($data);

    if (isset($data['action']) && $data['action'] === 'save_game_result') {
        $score = intval($data['score']);
        $stage = intval($data['stage']);
        $time = intval($data['time']);
        $user_id = $USER->id;

        // 게임 ID, 과목명, 단원명 받아오기
        $game_id = intval($data['game_id']);
        $subject_name = $data['subject_name'];
        $unit_name = $data['unit_name'];

        // games_user_records 테이블에 기록 삽입 또는 업데이트
        $existingRecord = $DB->get_record('{games_user_records}', array('game_id' => $game_id, 'user_id' => $user_id));

        if ($existingRecord) {
            // 기존 기록 업데이트 (더 높은 점수인 경우에만 업데이트)
            if ($score > $existingRecord->score) {
                $existingRecord->score = $score;
                $existingRecord->last_played = time();
                $DB->update_record('games_user_records', $existingRecord);
            }
        } else {
            // 새로운 기록 추가
            $newRecord->score = $score;
            $newRecord->last_played = time();
            $DB->insert_record('games_user_records', $newRecord);
        }

        // 전체 랭킹 계산 및 업데이트
        // 해당 단원과 과목의 모든 게임에 대한 유저의 총 점수를 계산
        $sql = "SELECT user_id, SUM(score) as total_score
        FROM {games_user_records}
        WHERE unit_name = ? AND subject_name = ?
        GROUP BY user_id
        ORDER BY total_score DESC";
        $params = array($unit_name, $subject_name);
        $rankings = $DB->get_records_sql($sql, $params);

        $rank = 1;
        $userRank = null;
        $userTotalScore = 0;
        foreach ($rankings as $record) {
            if ($record->user_id == $user_id) {
                $userTotalScore = $record->total_score;
                $userRank = $rank;
                break;
            }
            $rank++;
        }

        // games_unit_rankings 테이블 업데이트
        $existingRanking = $DB->get_record('games_unit_rankings', array(
            'unit_name' => $unit_name,
            'subject_name' => $subject_name,
            'user_name' => $USER->username
        ));

        if ($existingRanking) {
            $existingRanking->rank = $userRank;
            $existingRanking->score = $userTotalScore;
            $existingRanking->created_at = time();
            $DB->update_record('games_unit_rankings', $existingRanking);
        } else {
            // 새로운 랭킹 기록 추가
            $newRanking = new stdClass();
            $newRanking->unit_name = $unit_name;
            $newRanking->subject_name = $subject_name;
            $newRanking->user_name = $USER->username;
            $newRanking->user_avatar = ''; // 필요에 따라 사용자 아바타 설정
            $newRanking->rank = $userRank;
            $newRanking->score = $userTotalScore;
            $newRanking->created_at = time();
            $DB->insert_record('games_unit_rankings', $newRanking);
        }

        // 성공 응답
        echo json_encode(['success' => true]);
        exit();
    }
}

// 나머지 PHP 코드 (게임 데이터 가져오기 등)
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 구구단 퀴즈 게임</title>
    <style>
        /* 기존 스타일 그대로 유지 */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-image: url('https://mathking.kr/Contents/IMAGES/quizshow.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* 수직 중앙 정렬 */
            height: 90vh; /* 화면 전체 높이 사용 */
            overflow: hidden; /* 수직 스크롤 제거 */
        }
        #gameContainer {
            position: relative;
            width: 90vw;
            max-width: 800px;
            height: 70vw;
            max-height: 700px;
            aspect-ratio: 1 / 1; /* 가로세로 비율을 1:1로 유지 */
            border: 1px solid #ccc;
            overflow: hidden;
            flex-shrink: 0; /* 컨테이너 크기 축소 방지 */
            background-color: rgba(255, 255, 255, 0.7); /* 배경 투명도 적용 */
        }
        .player, .enemy, .boss, .bullet {
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: 2px solid black;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.7); /* 투명도 추가 */
        }
        .player {
            width: 5%;
            height: 5%;
            background-color: blue;
            color: white;
            font-size: calc(0.5vw + 0.5vh);
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            border-radius: 50%; /* 플레이어를 원으로 */
        }
        .enemy, .boss {
            width: 5%;
            height: 5%;
            background-color: red;
            color: white;
            font-size: calc(0.4vw + 0.4vh);
            min-font-size: 10px;
            border-radius: 50%; /* 적들도 원형으로 변경 */
        }
        .boss {
            width: 7%;
            height: 7%;
            background-color: purple;
        }
        .bullet {
            width: 1%;
            height: 1%;
            background-color: yellow;
            border-radius: 50%;
            background-color: rgba(255, 255, 0, 0.8); /* 투명도 추가 */
        }
        .explosion {
            position: absolute;
            width: 3%;
            height: 3%;
            background-color: orange;
            border-radius: 50%;
            opacity: 0.7;
            animation: explode 0.5s ease-out forwards;
        }
        @keyframes explode {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(5);
                opacity: 0;
            }
        }
        h1 {
            margin-bottom: 10px; /* 제목과 버튼 사이 간격 조정 */
            color: white; /* 제목 색상을 흰색으로 변경 */
            text-shadow: 1px 1px 2px black; /* 텍스트 그림자 추가 */
        }
        #answerInput {
            width: 100%;
            max-width: 120px;
            padding: 10px;
            font-size: 18px;
            box-sizing: border-box;
            text-align: center;
        }
        #status {
            margin-bottom: 10px; /* 상태 표시줄과 게임 컨테이너 사이 간격 */
            display: none;
            color: white; /* 상태 표시줄 색상 변경 */
            text-shadow: 1px 1px 2px black; /* 텍스트 그림자 추가 */
        }
        #startButton, #restartButton, #leaderboardButton {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin: 5px;
        }
        #restartButton, #leaderboardButton {
            display: none;
        }
        #leaderboard {
            display: none;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
        }
        /* 숫자 버튼 스타일 */
        #tenkeyContainer {
            position: fixed;
            bottom: 0;
            center: 0;
            width: 50%;
            display: flex; /* 플렉스 레이아웃으로 변경 */
            gap: 5px;
            padding: 5px;
            background-color: rgba(255, 255, 255, 0.9);
            border-top: 1px solid #ccc;
            box-sizing: border-box;
            align-items: center; /* 세로 가운데 정렬 */
        }
        #keypad {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 5px;
            flex-grow: 1; /* 남은 공간을 차지하도록 */
        }
        #tenkeyContainer button {
            padding: 10px;
            font-size: 18px;
            cursor: pointer;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            color: #333;
        }
        #tenkeyContainer button:active {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <h1>수학 구구단 퀴즈 게임</h1>
    <button id="startButton">게임 시작</button>
    <div id="status">
        <span>체력: <span id="health">♥♥♥♥♥</span></span> | 
        <span>단계: <span id="stage">1</span></span> | 
        <span>타이머: <span id="timer">0</span>초</span> | 
        <span>스코어: <span id="score">0</span></span>
    </div>
    <div id="gameContainer">
        <div class="player"></div>
    </div>
    <div id="buttonContainer">
        <button id="restartButton">게임 다시 시작</button>
        <button id="leaderboardButton">순위표 보기</button>
    </div>
    <div id="leaderboard">
        <h2>순위표</h2>
        <div id="leaderboardContent"></div>
        <button onclick="goToMainMenu()">메인 화면으로 돌아가기</button>
    </div>
    <div id="tenkeyContainer">
        <div id="keypad">
            <button onclick="handleTenkeyInput('1')">1</button>
            <button onclick="handleTenkeyInput('2')">2</button>
            <button onclick="handleTenkeyInput('3')">3</button>
            <button onclick="handleTenkeyInput('4')">4</button>
            <button onclick="handleTenkeyInput('5')">5</button>
            <button onclick="handleTenkeyInput('6')">6</button>
            <button onclick="handleTenkeyInput('7')">7</button>
            <button onclick="handleTenkeyInput('8')">8</button>
            <button onclick="handleTenkeyInput('9')">9</button>
            <button onclick="handleTenkeyInput('0')">0</button>
            <button onclick="handleTenkeyInput('Enter')">Enter</button>
            <button onclick="handleTenkeyInput('Backspace')">←</button>
        </div>
        <input type="number" id="answerInput" placeholder="정답" autocomplete="off" />
    </div>
    <script>
        const gameContainer = document.getElementById('gameContainer');
        const player = document.querySelector('.player');
        const answerInput = document.getElementById('answerInput');
        const healthDisplay = document.getElementById('health');
        const stageDisplay = document.getElementById('stage');
        const timerDisplay = document.getElementById('timer');
        const scoreDisplay = document.getElementById('score');
        const statusContainer = document.getElementById('status');
        const startButton = document.getElementById('startButton');
        const restartButton = document.getElementById('restartButton');
        const leaderboardButton = document.getElementById('leaderboardButton');
        const leaderboard = document.getElementById('leaderboard');
        const leaderboardContent = document.getElementById('leaderboardContent');
        const tenkeyContainer = document.getElementById('tenkeyContainer');

        let health = 5;
        let stage = 1;
        let timer = 0;
        let score = 0;
        let enemySpawnInterval;
        let bossSpawned = false;
        let totalElapsedTime = 0;
        let totalStageTime = 0;
        let gameRunning = false;
        let stageStartTime = 0;
        let leaderboardData = JSON.parse(localStorage.getItem('leaderboardData')) || [];

        // PHP에서 JavaScript로 변수 전달
        var GAME_ID = <?php echo intval($game_id); ?>;
        var SUBJECT_NAME = "<?php echo addslashes($subject_name); ?>";
        var UNIT_NAME = "<?php echo addslashes($unit_name); ?>";

        startButton.addEventListener('click', startGame);
        restartButton.addEventListener('click', startGame);
        leaderboardButton.addEventListener('click', showLeaderboard);

        // 플레이어의 고정 위치 설정 (게임 컨테이너의 중앙)
        const playerX = gameContainer.clientWidth / 2 - player.clientWidth / 2;
        const playerY = gameContainer.clientHeight / 2 - player.clientHeight / 2;
        player.style.left = `${playerX}px`;
        player.style.top = `${playerY}px`;

        function startGame() {
            gameRunning = true;
            health = 5;
            stage = 1;
            timer = 0;
            score = 0;
            totalElapsedTime = 0;
            totalStageTime = 0;
            bossSpawned = false;
            clearEnemies();

            healthDisplay.textContent = '♥'.repeat(health);
            stageDisplay.textContent = stage;
            timerDisplay.textContent = timer;
            scoreDisplay.textContent = score;
            statusContainer.style.display = 'block';
            answerInput.style.display = 'block';
            startButton.style.display = 'none';
            restartButton.style.display = 'none';
            leaderboardButton.style.display = 'none';

            stageStartTime = Date.now();
            updateTimer();
            spawnEnemies();
            answerInput.value = '';
            answerInput.focus();

            answerInput.addEventListener('keydown', handleAnswer);
            answerInput.addEventListener('input', autoSubmitAnswer); // 입력 이벤트 추가
        }

        // 나머지 게임 코드 (handleTenkeyInput, updateTimer 등)는 변경 없이 그대로 사용하시면 됩니다.

        function endGame() {
            clearInterval(enemySpawnInterval);
            gameRunning = false;

            const totalGameTime = totalElapsedTime + totalStageTime;
            alert(`게임 종료! 총 걸린 시간: ${totalGameTime}초, 최고 도달 단계: ${stage}, 스코어: ${score}`);
            saveHighScore();
            statusContainer.style.display = 'none';
            restartButton.style.display = 'block';
            leaderboardButton.style.display = 'block';
            answerInput.value = '';
            answerInput.removeEventListener('keydown', handleAnswer);
            answerInput.removeEventListener('input', autoSubmitAnswer);

            // 게임 결과를 서버로 전송
            saveGameResult();
        }
        function saveGameResult() {
    const data = {
        action: 'save_game_result',
        score: score,
        stage: stage,
        time: totalElapsedTime + totalStageTime,
        game_id: GAME_ID,
        subject_name: SUBJECT_NAME,
        unit_name: UNIT_NAME
    };

    fetch(location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin', // 세션 쿠키를 포함하여 사용자 인증 정보 전송
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('게임 결과 저장 성공');
        } else {
            console.error('게임 결과 저장 실패:', result.message);
        }
    })
    .catch(error => {
        console.error('에러 발생:', error);
    });
}

        function handleTenkeyInput(value) {
            if (value === 'Enter') {
                const event = new KeyboardEvent('keydown', { key: 'Enter' });
                answerInput.dispatchEvent(event);
            } else if (value === 'Backspace') {
                answerInput.value = answerInput.value.slice(0, -1);
            } else {
                answerInput.value += value;
                autoSubmitAnswer(); // 숫자 입력 시에도 자동 제출 검사
            }
            answerInput.focus();
        }

        function updateTimer() {
            const timerInterval = setInterval(() => {
                if (!gameRunning) {
                    clearInterval(timerInterval);
                    return;
                }

                timer = Math.floor((Date.now() - stageStartTime) / 1000);
                timerDisplay.textContent = `${timer}초`;
                totalStageTime = timer;

                if (timer >= 30 && gameRunning && !bossSpawned) {
                    spawnBoss();
                }
            }, 1000);
        }

        function spawnEnemies() {
            let spawnRate;
            switch (stage) {
                case 1:
                    spawnRate = 3000;
                    break;
                case 2:
                    spawnRate = 2000;
                    break;
                case 3:
                    spawnRate = 1000;
                    break;
                case 4:
                    spawnRate = 500;
                    break;
                case 5:
                    spawnRate = 333;
                    break;
                default:
                    spawnRate = 1000;
            }

            if (enemySpawnInterval) clearInterval(enemySpawnInterval);
            enemySpawnInterval = setInterval(() => {
                if (gameRunning && !bossSpawned) {
                    spawnEnemy();
                }
            }, spawnRate);
        }
 
        function saveHighScore() {
            const newRecord = { score, stage, time: totalElapsedTime + totalStageTime };
            leaderboardData.push(newRecord);
            leaderboardData.sort((a, b) => b.score - a.score || b.stage - a.stage);
            leaderboardData = leaderboardData.slice(0, 20);
            localStorage.setItem('leaderboardData', JSON.stringify(leaderboardData));

            if (leaderboardData.includes(newRecord)) {
                const name = prompt("하이스코어 달성! 이름을 입력하세요:");
                if (name) {
                    newRecord.name = name;
                }
            }
        }

        function showLeaderboard() {
            leaderboard.style.display = 'block';
            leaderboardContent.innerHTML = leaderboardData.map((record, index) => 
                `<div>${index + 1}위: ${record.name || '익명'} - 스코어: ${record.score}, 단계: ${record.stage}, 시간: ${record.time}초</div>`
            ).join('');
        }

        function goToMainMenu() {
            leaderboard.style.display = 'none';
            startButton.style.display = 'block';
            leaderboardButton.style.display = 'block';
        }

        function clearEnemies() {
            const enemies = document.querySelectorAll('.enemy, .boss');
            enemies.forEach(enemy => {
                if (enemy.parentNode) {
                    gameContainer.removeChild(enemy);
                }
            });
        }

        function spawnEnemy() {
            const randomFactor1 = Math.floor(Math.random() * 8) + 2;
            const randomFactor2 = Math.floor(Math.random() * 8) + 2;
            const newAnswer = randomFactor1 * randomFactor2;

            const enemy = document.createElement('div');
            enemy.className = 'enemy';
            enemy.textContent = `${randomFactor1} x ${randomFactor2}`;
            enemy.dataset.answer = newAnswer;

            const edge = Math.floor(Math.random() * 4);
            let x, y;
            if (edge === 0) {
                x = Math.random() * gameContainer.clientWidth;
                y = 0;
            } else if (edge === 1) {
                x = Math.random() * gameContainer.clientWidth;
                y = gameContainer.clientHeight - 50;
            } else if (edge === 2) {
                x = 0;
                y = Math.random() * gameContainer.clientHeight;
            } else {
                x = gameContainer.clientWidth - 50;
                y = Math.random() * gameContainer.clientHeight;
            }

            enemy.style.left = `${x}px`;
            enemy.style.top = `${y}px`;

            adjustFontSize(enemy);
            gameContainer.appendChild(enemy);
            moveEnemy(enemy);
        }

        function adjustFontSize(element) {
            const baseSize = Math.min(element.clientWidth, element.clientHeight) * 0.3;
            element.style.fontSize = `${Math.max(baseSize, 12)}px`;
        }

        function moveEnemy(enemy) {
            const speed = Math.random() * 1.5 + 0.5;
            const moveInterval = setInterval(() => {
                if (!gameRunning) {
                    clearInterval(moveInterval);
                    return;
                }

                const enemyRect = enemy.getBoundingClientRect();
                const playerRect = player.getBoundingClientRect();
                const dx = (playerRect.left + playerRect.width / 2) - (enemyRect.left + enemyRect.width / 2);
                const dy = (playerRect.top + playerRect.height / 2) - (enemyRect.top + enemyRect.height / 2);
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 25) {
                    health--;
                    healthDisplay.textContent = '♥'.repeat(health);
                    if (enemy.parentNode) {
                        gameContainer.removeChild(enemy);
                    }
                    clearInterval(moveInterval);
                    if (health <= 0) {
                        endGame();
                    }
                } else {
                    const angle = Math.atan2(dy, dx);
                    enemy.style.left = `${parseFloat(enemy.style.left) + Math.cos(angle) * speed}px`;
                    enemy.style.top = `${parseFloat(enemy.style.top) + Math.sin(angle) * speed}px`;
                }
            }, 50);
        }

        function spawnBoss() {
            bossSpawned = true;
            const boss = document.createElement('div');
            boss.className = 'boss';
            const randomFactor1 = Math.floor(Math.random() * 89) + 11;
            const randomFactor2 = Math.floor(Math.random() * 8) + 2;
            boss.textContent = `${randomFactor1} x ${randomFactor2}`;
            boss.dataset.answer = randomFactor1 * randomFactor2;

            positionBossAtEdge(boss);
            adjustFontSize(boss);
            gameContainer.appendChild(boss);
            moveBoss(boss);
        }

        function positionBossAtEdge(boss) {
            const edge = Math.floor(Math.random() * 4);
            let x, y;
            if (edge === 0) {
                x = Math.random() * gameContainer.clientWidth;
                y = 0;
            } else if (edge === 1) {
                x = Math.random() * gameContainer.clientWidth;
                y = gameContainer.clientHeight - 70;
            } else if (edge === 2) {
                x = 0;
                y = Math.random() * gameContainer.clientHeight;
            } else {
                x = gameContainer.clientWidth - 70;
                y = Math.random() * gameContainer.clientHeight;
            }

            boss.style.left = `${x}px`;
            boss.style.top = `${y}px`;
        }

        function moveBoss(boss) {
            const baseSpeed = 0.2;
            const speed = baseSpeed * stage;
            const moveInterval = setInterval(() => {
                if (!gameRunning) {
                    clearInterval(moveInterval);
                    return;
                }

                const bossRect = boss.getBoundingClientRect();
                const playerRect = player.getBoundingClientRect();
                const dx = (playerRect.left + playerRect.width / 2) - (bossRect.left + bossRect.width / 2);
                const dy = (playerRect.top + playerRect.height / 2) - (bossRect.top + bossRect.height / 2);
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 35) {
                    health -= 2;
                    healthDisplay.textContent = '♥'.repeat(health);
                    if (boss.parentNode) {
                        gameContainer.removeChild(boss);
                    }
                    clearInterval(moveInterval);

                    if (health <= 0) {
                        endGame();
                    } else {
                        spawnBoss();
                    }
                } else {
                    const angle = Math.atan2(dy, dx);
                    boss.style.left = `${parseFloat(boss.style.left) + Math.cos(angle) * speed}px`;
                    boss.style.top = `${parseFloat(boss.style.top) + Math.sin(angle) * speed}px`;
                }
            }, 50);
        }

        function handleAnswer(e) {
            if (e.key !== 'Enter') return;

            processAnswer();
        }

        // 사용자가 입력한 답 처리
        function processAnswer() {
            const answer = parseInt(answerInput.value, 10);
            if (isNaN(answer)) {
                answerInput.value = '';
                return;
            }
            let correct = false;

            const targetEnemies = document.querySelectorAll(`.enemy[data-answer='${answer}']`);
            if (targetEnemies.length > 0) {
                targetEnemies.forEach(target => {
                    fireBullet(target);
                    score++;
                    scoreDisplay.textContent = score;
                });
                correct = true;
            }

            if (bossSpawned) {
                const boss = document.querySelector('.boss');
                if (boss && boss.dataset.answer == answer) {
                    fireBullet(boss);
                    score += 100;
                    scoreDisplay.textContent = score;
                    correct = true;
                    handleBossDefeat();
                }
            }

            answerInput.value = '';
            if (!correct) {
                health--;
                healthDisplay.textContent = '♥'.repeat(health);
                showExplosion();
                if (health <= 0) {
                    endGame();
                }
            }
        }

        // 입력 값이 두 자리가 되면 자동으로 Enter 처리
function autoSubmitAnswer() {
    const inputValue = answerInput.value;
    const inputLength = inputValue.length;

    if (inputLength >= 2) {
        // 현재 화면에 등장한 적들의 정답과 비교
        const enemies = document.querySelectorAll('.enemy, .boss');
        let answerExists = false;

        enemies.forEach(enemy => {
            if (enemy.dataset.answer === inputValue) {
                answerExists = true;
            }
        });

        if (answerExists || inputLength >= 3) {
            processAnswer();
        }
    }
}

        function fireBullet(target) {
            const bullet = document.createElement('div');
            bullet.className = 'bullet';
            const playerRect = player.getBoundingClientRect();
            const playerCenterX = playerRect.left + playerRect.width / 2 - gameContainer.getBoundingClientRect().left;
            const playerCenterY = playerRect.top + playerRect.height / 2 - gameContainer.getBoundingClientRect().top;
            bullet.style.left = `${playerCenterX - bullet.clientWidth / 2}px`;
            bullet.style.top = `${playerCenterY - bullet.clientHeight / 2}px`;
            gameContainer.appendChild(bullet);

            const bulletInterval = setInterval(() => {
                if (!gameRunning) {
                    clearInterval(bulletInterval);
                    if (bullet.parentNode) {
                        gameContainer.removeChild(bullet);
                    }
                    return;
                }

                const bulletRect = bullet.getBoundingClientRect();
                const targetRect = target.getBoundingClientRect();
                const dx = targetRect.left + targetRect.width / 2 - bulletRect.left - bulletRect.width / 2;
                const dy = targetRect.top + targetRect.height / 2 - bulletRect.top - bulletRect.height / 2;

                if (
                    bulletRect.left < targetRect.right &&
                    bulletRect.right > targetRect.left &&
                    bulletRect.top < targetRect.bottom &&
                    bulletRect.bottom > targetRect.top
                ) {
                    if (target.parentNode) {
                        gameContainer.removeChild(target);
                    }
                    if (bullet.parentNode) {
                        gameContainer.removeChild(bullet);
                    }
                    clearInterval(bulletInterval);
                } else {
                    const angle = Math.atan2(dy, dx);
                    bullet.style.left = `${parseFloat(bullet.style.left) + Math.cos(angle) * 25}px`;
                    bullet.style.top = `${parseFloat(bullet.style.top) + Math.sin(angle) * 25}px`;
                }
            }, 20);
        }

        function handleBossDefeat() {
            bossSpawned = false;

            totalElapsedTime += totalStageTime;
            stage++;
            health += 3;
            healthDisplay.textContent = '♥'.repeat(health);
            stageDisplay.textContent = stage;
            timer = 0;
            stageStartTime = Date.now();

            if (stage > 5) {
                endGame();
            } else {
                spawnEnemies();
            }
        }

        function showExplosion() {
            const explosion = document.createElement('div');
            explosion.className = 'explosion';
            const playerRect = player.getBoundingClientRect();
            const playerCenterX = playerRect.left + playerRect.width / 2 - gameContainer.getBoundingClientRect().left;
            const playerCenterY = playerRect.top + playerRect.height / 2 - gameContainer.getBoundingClientRect().top;
            explosion.style.left = `${playerCenterX - 15}px`;
            explosion.style.top = `${playerCenterY - 15}px`;
            gameContainer.appendChild(explosion);

            setTimeout(() => {
                if (explosion.parentNode) {
                    gameContainer.removeChild(explosion);
                }
            }, 500);
        }
 
 
    </script>
</body>
</html>
