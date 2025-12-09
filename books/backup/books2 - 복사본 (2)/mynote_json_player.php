<?php
/////////////////////////////// JSON 절차기억 플레이어 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 파라미터 받기 (cid/ctype 또는 contentsid/contentstype 모두 지원)
$contentsid = $_GET["contentsid"] ?? $_GET["cid"] ?? null;
$contentstype = $_GET["contentstype"] ?? $_GET["ctype"] ?? "1";
$studentid = $_GET["studentid"] ?? $USER->id;

if(!$contentsid) {
    die("ERROR [mynote_json_player.php:13]: contentsid (또는 cid) 파라미터가 필요합니다.<br>예: mynote_json_player.php?contentsid=TEST001&contentstype=1");
}

// DB에서 절차기억 데이터 로드
// mdl_icontent_pages 테이블의 reflections1 필드에서 데이터 로드
$proceduralRecord = $DB->get_record_sql(
    "SELECT id, reflections1 FROM mdl_icontent_pages
     WHERE id=?
     LIMIT 1",
    [$contentsid]
);

$proceduralData = null;
$audioFiles = [];
$jsonStructure = null;

if($proceduralRecord && !empty($proceduralRecord->reflections1)) {
    // JSON 데이터 파싱 시도
    $jsonData = json_decode($proceduralRecord->reflections1, true);

    if($jsonData && isset($jsonData['mode']) && $jsonData['mode'] === 'procedural_json') {
        $proceduralData = $jsonData;
        $audioFiles = $jsonData['files'] ?? [];
        $jsonStructure = $jsonData['json_structure'] ?? null;
    }
}

$hasData = !empty($audioFiles);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>절차기억 학습 플레이어</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Malgun Gothic', sans-serif;
            background: #1a1a1a;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: #2d2d2d;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            max-width: 800px;
            width: 100%;
            padding: 30px;
            border: 1px solid #3d3d3d;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .info-bar {
            background: #3d3d3d;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #a0a0a0;
            border: 1px solid #4d4d4d;
        }

        /* 레벨 선택 UI */
        .level-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .level-btn {
            padding: 10px 20px;
            border: 2px solid #667eea;
            background: #3d3d3d;
            color: #667eea;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .level-btn.active {
            background: #667eea;
            color: white;
        }

        .level-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* 오디오 버튼 공통 스타일 */
        .audio-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }

        .button-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .audio-btn {
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .audio-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .audio-btn.playing {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* 타입별 버튼 스타일 */
        .audio-btn-Q {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .audio-btn-B {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .audio-btn-E {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a8a8a8 0%, #d0d0d0 100%);
            color: white;
            font-size: 12px;
        }

        .audio-btn-T,
        .audio-btn-K,
        .audio-btn-S {
            min-width: 80px;
            height: 50px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0 15px;
        }

        /* 오디오 플레이어 */
        .audio-player-wrapper {
            background: #3d3d3d;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #4d4d4d;
        }

        audio {
            width: 100%;
            outline: none;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #a0a0a0;
        }

        .section-label {
            font-size: 12px;
            color: #a0a0a0;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .sub-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-left: 60px;
        }

        /* 자막 표시 영역 */
        #subtitle-container {
            background: #3d3d3d;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #667eea;
            min-height: 100px;
            max-height: 200px;
            overflow-y: auto;
        }

        #subtitle-text {
            font-size: 16px;
            line-height: 1.8;
            color: #e0e0e0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* 반응형 */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 20px;
            }

            .level-btn {
                font-size: 14px;
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 절차기억 학습 플레이어</h1>

        <div class="info-bar">
            📌 Contents ID: <?php echo htmlspecialchars($contentsid); ?> |
            👤 Student ID: <?php echo htmlspecialchars($studentid); ?>
            <?php if($hasData): ?>
            | 🎵 총 <?php echo count($audioFiles); ?>개 음성 파일
            <?php endif; ?>
        </div>

        <?php if($hasData): ?>
            <!-- 레벨 선택 UI -->
            <div class="level-selector">
                <button class="level-btn active" id="basic-mode-btn" onclick="switchLevel('basic')">
                    📘 기본 모드
                </button>
                <button class="level-btn" id="detail-mode-btn" onclick="switchLevel('detail')">
                    📗 상세 모드
                </button>
                <button class="level-btn" id="subtitle-toggle-btn" onclick="toggleSubtitle()">
                    👁️ 자막 보기
                </button>
            </div>

            <!-- 오디오 버튼들 -->
            <div class="audio-buttons" id="audio-buttons-container">
                <?php
                // 버튼 그룹화
                $groups = [];
                $currentGroup = null;

                foreach($audioFiles as $file) {
                    $key = $file['key'];
                    $type = substr($key, 0, 1); // Q, B, E, T, K, S

                    if($type === 'E') {
                        // 보조지시문 - 현재 그룹에 추가
                        if($currentGroup !== null) {
                            $groups[$currentGroup]['sub'][] = $file;
                        }
                    } else {
                        // 새 그룹 시작
                        $groupKey = $key;
                        $groups[$groupKey] = [
                            'main' => $file,
                            'sub' => []
                        ];
                        $currentGroup = $groupKey;
                    }
                }

                // 버튼 렌더링
                foreach($groups as $groupKey => $group) {
                    $mainFile = $group['main'];
                    $subFiles = $group['sub'];
                    $key = $mainFile['key'];
                    $type = $mainFile['type'];
                    $typePrefix = substr($key, 0, 1);

                    echo '<div class="button-row">';

                    // 섹션 라벨
                    $label = '';
                    if($typePrefix === 'Q') $label = '문제설명';
                    elseif($typePrefix === 'B') $label = '기본지시';
                    elseif($typePrefix === 'T') $label = '전체정리';
                    elseif($typePrefix === 'K') $label = '핵심정리';
                    elseif($typePrefix === 'S') $label = '구조기억';

                    echo '<div style="width:100%;">';
                    if($label) {
                        echo '<div class="section-label">' . htmlspecialchars($label) . '</div>';
                    }

                    // 메인 버튼
                    echo '<button class="audio-btn audio-btn-' . $typePrefix . '" ';
                    echo 'data-url="' . htmlspecialchars($mainFile['url']) . '" ';
                    echo 'data-key="' . htmlspecialchars($key) . '" ';
                    echo 'onclick="playAudio(this)">';
                    echo htmlspecialchars($key);
                    echo '</button>';

                    // 보조지시문 버튼들 (별도 줄)
                    if(!empty($subFiles)) {
                        echo '<div class="sub-buttons" style="margin-top:8px;">';
                        foreach($subFiles as $subFile) {
                            $subKey = $subFile['key'];
                            echo '<button class="audio-btn audio-btn-E sub-instruction-btn" ';
                            echo 'data-url="' . htmlspecialchars($subFile['url']) . '" ';
                            echo 'data-key="' . htmlspecialchars($subKey) . '" ';
                            echo 'onclick="playAudio(this)">';
                            echo htmlspecialchars($subKey);
                            echo '</button>';
                        }
                        echo '</div>';
                    }

                    echo '</div>'; // width:100% div
                    echo '</div>'; // button-row
                }
                ?>
            </div>

            <!-- 자막 표시 영역 -->
            <div id="subtitle-container" style="display:none;">
                <div id="subtitle-text"></div>
            </div>

            <!-- 오디오 플레이어 -->
            <div class="audio-player-wrapper">
                <div id="current-playing" style="margin-bottom:10px; color:#a0a0a0; font-weight:bold;">
                    재생 대기 중...
                </div>
                <audio id="audio-player" controls style="filter: invert(0.9);">
                    <source id="audio-source" src="" type="audio/mpeg">
                    브라우저가 오디오 재생을 지원하지 않습니다.
                </audio>
            </div>

        <?php else: ?>
            <div class="no-data">
                <h2>📭 데이터 없음</h2>
                <p>이 콘텐츠에 대한 절차기억 나레이션이 없습니다.</p>
                <p style="margin-top:10px; font-size:14px;">
                    <a href="openai_tts_pmemory.php?cid=<?php echo htmlspecialchars($contentsid); ?>&ctype=<?php echo htmlspecialchars($contentstype); ?>"
                       style="color:#667eea;">
                        나레이션 생성 페이지로 이동 →
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let currentLevel = 'basic'; // basic | detail
        let subtitleVisible = false; // 자막 표시 상태
        const audioPlayer = document.getElementById('audio-player');
        const audioSource = document.getElementById('audio-source');
        const currentPlayingDiv = document.getElementById('current-playing');
        const subtitleContainer = document.getElementById('subtitle-container');
        const subtitleText = document.getElementById('subtitle-text');
        const subtitleToggleBtn = document.getElementById('subtitle-toggle-btn');
        let currentButton = null;

        // 오디오 파일 데이터를 JavaScript 객체로 저장
        const audioData = <?php echo json_encode($audioFiles, JSON_UNESCAPED_UNICODE); ?>;

        // 자막 보기/숨김 토글
        function toggleSubtitle() {
            subtitleVisible = !subtitleVisible;

            if(subtitleVisible) {
                subtitleContainer.style.display = 'block';
                subtitleToggleBtn.textContent = '👁️‍🗨️ 자막 숨김';
                subtitleToggleBtn.classList.add('active');
            } else {
                subtitleContainer.style.display = 'none';
                subtitleToggleBtn.textContent = '👁️ 자막 보기';
                subtitleToggleBtn.classList.remove('active');
            }

            console.log('[mynote_json_player.php] 자막 토글:', subtitleVisible);
        }

        // 레벨 전환
        function switchLevel(level) {
            currentLevel = level;

            // 버튼 활성화 상태 업데이트
            document.getElementById('basic-mode-btn').classList.toggle('active', level === 'basic');
            document.getElementById('detail-mode-btn').classList.toggle('active', level === 'detail');

            // 보조지시문 버튼 표시/숨김
            const subButtons = document.querySelectorAll('.sub-instruction-btn');
            subButtons.forEach(btn => {
                btn.style.display = (level === 'detail') ? 'inline-flex' : 'none';
            });

            console.log('[mynote_json_player.php:350] 레벨 전환:', level);
        }

        // 오디오 재생
        function playAudio(button) {
            const url = button.getAttribute('data-url');
            const key = button.getAttribute('data-key');

            if(!url) {
                alert('[mynote_json_player.php:358] ERROR: 오디오 URL이 없습니다.');
                return;
            }

            console.log('[mynote_json_player.php:363] 재생:', key, url);

            // 이전 버튼 playing 클래스 제거
            if(currentButton) {
                currentButton.classList.remove('playing');
            }

            // 현재 버튼 playing 클래스 추가
            button.classList.add('playing');
            currentButton = button;

            // 오디오 소스 변경 및 재생
            audioSource.src = url;
            audioPlayer.load();
            audioPlayer.play();

            // 재생 중 표시
            currentPlayingDiv.textContent = '🎵 재생 중: ' + key;
            currentPlayingDiv.style.color = '#667eea';

            // 자막 업데이트
            updateSubtitle(key);
        }

        // 자막 업데이트 함수
        function updateSubtitle(key) {
            // audioData 배열에서 해당 key의 text 찾기
            const fileData = audioData.find(item => item.key === key);

            if(fileData && fileData.text) {
                subtitleText.textContent = fileData.text;
            } else {
                subtitleText.textContent = '자막 정보가 없습니다.';
            }
        }

        // 오디오 종료 이벤트
        audioPlayer.addEventListener('ended', function() {
            if(currentButton) {
                currentButton.classList.remove('playing');
            }
            currentPlayingDiv.textContent = '✅ 재생 완료';
            currentPlayingDiv.style.color = '#4caf50';
        });

        // 오디오 에러 이벤트
        audioPlayer.addEventListener('error', function(e) {
            console.error('[mynote_json_player.php:393] 오디오 재생 오류:', e);
            alert('오디오 파일을 로드할 수 없습니다: ' + audioSource.src);
            if(currentButton) {
                currentButton.classList.remove('playing');
            }
        });

        // 페이지 로드 시 기본 모드로 초기화
        window.addEventListener('DOMContentLoaded', function() {
            switchLevel('basic');
            console.log('[mynote_json_player.php:404] 플레이어 초기화 완료');
        });
    </script>
</body>
</html>
