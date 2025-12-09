<?php 
header('Content-Type: text/html; charset=utf-8');

$studentid = isset($_GET["id"]) ? $_GET["id"] : '';
$contentsid =  $_GET["contentsid"]; 
$contentstype =  $_GET["contentstype"];
$json_input = isset($_GET["json"]) ? $_GET["json"] : '';

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$username = $DB->get_record_sql("SELECT id,hideinput,lastname, firstname,timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1 ");
$studentname = isset($username->firstname) ? $username->firstname.$username->lastname : '';

// DB에서 데이터 가져오기
$narration = "";
if($contentstype == 1 && !empty($contentsid)) {
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
    if($cnttext) {
        $narration = $cnttext->reflections0;
    }
} elseif($contentstype == 2 && !empty($contentsid)) {
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
    if($cnttext) {
        $narration = $cnttext->reflections0;
    }
}

// 사용자가 직접 제공한 JSON이 있다면 이를 $narration에 설정
if(isset($_GET['manual_json']) && !empty($_GET['manual_json'])) {
    $narration = $_GET['manual_json'];
}

// JSON 형식인지 확인하고 파싱
$narration_data = null;

// 1. URL 파라미터로 전달된 JSON이 있는 경우 (테스트용)
if(!empty($json_input)) {
    $narration_data = json_decode($json_input, true);
}

// 2. DB에서 가져온 값에서 JSON 파싱 시도
if($narration_data === null && !empty($narration)) {
    // 예외적인 문자들 제거하여 정제
    $narration_clean = trim($narration);
    
    // JSON 형식 여부 확인 및 정제
    // 가끔 앞뒤에 불필요한 문자가 붙는 경우를 처리
    if (preg_match('/({.+})/', $narration_clean, $matches)) {
        $narration_clean = $matches[1];
    }
    
    // 처음부터 JSON 파싱 시도 (전처리 없이)
    $narration_data = json_decode($narration_clean, true);
    
    // 파싱 실패시 일반적인 문제 해결 시도
    if($narration_data === null) {
        // 슬래시 이스케이프 문제가 있는지 확인하고 수정
        $narration_clean = stripslashes($narration_clean);
        $narration_data = json_decode($narration_clean, true);
        
        // HTML 엔티티 디코딩 시도
        if($narration_data === null) {
            $narration_clean = html_entity_decode($narration_clean);
            $narration_data = json_decode($narration_clean, true);
            
            // 특수 문자나 이스케이프 문제 해결 시도
            if($narration_data === null) {
                $narration_clean = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $narration_clean);
                $narration_data = json_decode($narration_clean, true);
                
                // 여전히 실패한 경우 HTML 태그 제거 후 시도
                if($narration_data === null) {
                    $narration_clean = strip_tags($narration);
                    $narration_data = json_decode($narration_clean, true);
                }
            }
        }
    }
    
    // 마지막 방법: 직접 JSON 패턴 추출
    if($narration_data === null && (strpos($narration, '"title"') !== false || strpos($narration, '"content"') !== false)) {
        // 정규식으로 부분 추출 시도
        $title = "제목 없음";
        $content = [];
        $quote = "";
        $keywords = [];
        
        // 각 필드를 정규식으로 추출
        if(preg_match('/"title"\s*:\s*"([^"]+)"/', $narration, $matches)) {
            $title = $matches[1];
        }
        
        if(preg_match('/"growthmindset_quote"\s*:\s*"([^"]+)"/', $narration, $matches)) {
            $quote = $matches[1];
        }
        
        // 배열(keywords, content) 추출 시도
        if(preg_match('/"content"\s*:\s*\[(.*?)\]/', $narration, $matches)) {
            $content_str = $matches[1];
            preg_match_all('/"([^"]+)"/', $content_str, $content_matches);
            if(!empty($content_matches[1])) {
                $content = $content_matches[1];
            }
        }
        
        if(preg_match('/"keywords"\s*:\s*\[(.*?)\]/', $narration, $matches)) {
            $keywords_str = $matches[1];
            preg_match_all('/"([^"]+)"/', $keywords_str, $keywords_matches);
            if(!empty($keywords_matches[1])) {
                $keywords = $keywords_matches[1];
            }
        }
        
        // 추출된 데이터로 배열 구성
        $narration_data = [
            'title' => $title,
            'content' => !empty($content) ? $content : ["내용을 불러올 수 없습니다."],
            'keywords' => $keywords,
            'growthmindset_quote' => $quote
        ];
    }
}

// 3. 아무 데이터도 없거나 JSON 파싱이 실패한 경우
if($narration_data === null || !is_array($narration_data) || empty($narration_data)) {
    if(!empty($narration)) {
        // JSON 문자열이 아니라면, narration 자체를 content로 사용
        if(strpos($narration, '{') === false || strpos($narration, '}') === false) {
            $narration_data = array(
                'title' => "텍스트 내용",
                'content' => [$narration],
                'growthmindset_quote' => ""
            );
        } else {
            // 최후의 방법: 직접 파싱 실패한 JSON 문자열을 인코딩하여 출력
            $narration_data = array(
                'title' => "JSON 파싱 실패",
                'content' => ["제공된 JSON 데이터를 파싱할 수 없습니다.", $narration],
                'growthmindset_quote' => "다시 시도해 주세요."
            );
        }
    } else {
        // 빈 데이터 경우 최소한의 안내 메시지 표시
        $narration_data = array(
            'title' => '준비된 데이터가 없습니다',
            'content' => ['데이터를 준비 중입니다. 잠시 후 다시 시도해주세요.'],
            'growthmindset_quote' => ""
        );
    }
}

// 디버깅 모드 - 파싱 과정에서 발생한 정보 확인 (필요시 주석 해제)
// echo "<pre style='color:#fff;'>narration: " . htmlspecialchars(substr($narration, 0, 500)) . "...</pre>";
// echo "<pre style='color:#fff;'>data: " . print_r($narration_data, true) . "</pre>";

// 추가 디버깅용 변수 - 필요시 주석 해제
// $debug_display = true;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>엔딩 크레딧</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            background-color: #000;
            color: #fff;
            font-family: 'Noto Sans KR', sans-serif;
            overflow: hidden;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .credits-container {
            width: 80%;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        .credits {
            position: absolute;
            width: 100%;
            text-align: center;
            color: #fff;
            padding: 30vh 0;
            transform-origin: center bottom;
            animation: scrollCredits 60s linear forwards;
            will-change: transform, opacity;
            animation-delay: 0s;
            transform: translateY(100vh) translateZ(0);
            opacity: 0;
        }
        
        .title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 40px;
            color: #80d8ff;
            letter-spacing: 2px;
        }
        
        .keywords {
            font-size: 18px;
            font-weight: 300;
            margin-bottom: 60px;
            color: #ffd54f;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .keyword {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .content {
            font-size: 24px;
            line-height: 2.2;
            margin-bottom: 60px;
            font-weight: 300;
        }
        
        .content p {
            margin: 25px 0;
        }
        
        .quote {
            font-size: 26px;
            font-style: italic;
            margin-top: 60px;
            color: #ffd54f;
            font-weight: 400;
            text-shadow: 0 0 5px rgba(255, 213, 79, 0.3);
            padding: 30px 0;
            position: relative;
        }
        
        .quote::before, .quote::after {
            content: "";
            display: block;
            width: 100px;
            height: 1px;
            background-color: rgba(255, 213, 79, 0.5);
            margin: 0 auto;
        }
        
        .quote::before {
            margin-bottom: 30px;
        }
        
        .quote::after {
            margin-top: 30px;
        }
        
        @keyframes scrollCredits {
            0% {
                transform: translateY(100vh) translateZ(0);
                opacity: 0;
            }
            5% {
                opacity: 1;
            }
            95% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100%) translateZ(0);
                opacity: 0;
            }
        }
        
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .star {
            position: absolute;
            background-color: white;
            border-radius: 50%;
            animation: twinkle ease infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 1; }
        }
        
        /* 재시작 버튼 스타일 */
        .restart-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: rgba(128, 216, 255, 0.3);
            color: #fff;
            border: 2px solid rgba(128, 216, 255, 0.7);
            border-radius: 30px;
            padding: 12px 25px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: 0 0 15px rgba(128, 216, 255, 0.5);
            /* 초기에는 버튼 숨김 */
            opacity: 0;
            visibility: hidden;
        }
        
        .restart-button:hover {
            background-color: rgba(128, 216, 255, 0.5);
            box-shadow: 0 0 20px rgba(128, 216, 255, 0.7);
        }
        
        /* 버튼 표시 클래스 */
        .restart-button.visible {
            opacity: 1;
            visibility: visible;
            animation: fadeIn 0.5s ease forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* 프로그레스 바 스타일 개선 */
        .progress-container {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 200px;
            height: 10px;
            background-color: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            z-index: 100;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        
        .progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(to right, #80d8ff, #64b5f6);
            border-radius: 10px;
            transition: width 0.1s linear;
        }
        
        .progress-text {
            position: absolute;
            right: 230px;
            top: 18px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
        }
        
        /* CSS에 선생님 아바타 스타일 추가 */
        .teacher-avatar {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            background-color: #ffb6c1; /* 좀 더 따뜻한 톤의 색상으로 변경 */
            border-radius: 50%;
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 15px rgba(255, 182, 193, 0.7);
            cursor: pointer;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .teacher-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(255, 182, 193, 0.9);
        }
        
        /* 얼굴 캐릭터 스타일 */
        .teacher-face {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* 머리카락 스타일 */
        .teacher-hair {
            position: absolute;
            top: -5%;
            left: 0;
            width: 100%;
            height: 40%;
            background-color: #663300;
            border-radius: 50% 50% 0 0;
            clip-path: ellipse(50% 50% at 50% 0);
        }
        
        /* 앞머리 스타일 */
        .teacher-bangs {
            position: absolute;
            top: 10%;
            width: 100%;
            height: 15%;
            z-index: 2;
        }
        
        .bang {
            position: absolute;
            width: 15px;
            height: 12px;
            background-color: #663300;
            border-radius: 50% 50% 50% 50% / 100% 100% 0% 0%;
        }
        
        .bang:nth-child(1) {
            left: 20%;
            transform: rotate(-10deg);
        }
        
        .bang:nth-child(2) {
            left: 40%;
        }
        
        .bang:nth-child(3) {
            right: 25%;
            transform: rotate(10deg);
        }
        
        /* 눈 스타일 */
        .teacher-eyes {
            position: absolute;
            width: 90%;
            top: 38%;
            display: flex;
            justify-content: space-around;
            padding: 0 5px;
        }
        
        .teacher-eye {
            width: 8px;
            height: 10px;
            background-color: #000;
            border-radius: 50%;
            position: relative;
            animation: blink 4s ease-in-out infinite;
        }
        
        @keyframes blink {
            0%, 96%, 100% { transform: scaleY(1); }
            98% { transform: scaleY(0.1); }
        }
        
        .teacher-eye::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 3px;
            height: 3px;
            background-color: #fff;
            border-radius: 50%;
        }
        
        /* 눈썹 스타일 */
        .teacher-eyebrows {
            position: absolute;
            width: 80%;
            top: 30%;
            display: flex;
            justify-content: space-around;
        }
        
        .eyebrow {
            width: 10px;
            height: 2px;
            background-color: #333;
            border-radius: 3px;
        }
        
        .eyebrow.left {
            transform: rotate(-10deg);
        }
        
        .eyebrow.right {
            transform: rotate(10deg);
        }
        
        /* 입 스타일 */
        .teacher-mouth {
            position: absolute;
            bottom: 28%;
            width: 16px;
            height: 6px;
            border-bottom: 2px solid #333;
            border-radius: 50%;
        }
        
        /* 볼 스타일 */
        .teacher-cheeks {
            position: absolute;
            width: 100%;
            top: 50%;
            display: flex;
            justify-content: space-around;
        }
        
        .cheek {
            width: 8px;
            height: 4px;
            background-color: rgba(255, 105, 180, 0.4);
            border-radius: 50%;
            position: relative;
        }
        
        .cheek.left {
            left: -3px;
        }
        
        .cheek.right {
            right: -3px;
        }
        
        /* 일시정지 버튼 스타일 */
        .pause-button {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background-color: rgba(255, 213, 79, 0.3);
            color: #fff;
            border: 2px solid rgba(255, 213, 79, 0.7);
            border-radius: 30px;
            padding: 12px 25px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: 0 0 15px rgba(27, 27, 27, 0.5);
        }
        
        .pause-button:hover {
            background-color: rgba(34, 33, 31, 0.5);
            box-shadow: 0 0 20px rgba(124, 120, 111, 0.7);
        }
        
        /* 재생 상태일 때 버튼 텍스트 변경 */
        .pause-button.playing::after {
            content: "정지";
        }
        
        /* 일시정지 상태일 때 버튼 텍스트 변경 */
        .pause-button.paused::after {
            content: "재생";
        }
        
        /* 일시정지 상태에서 애니메이션 정지 */
        .credits.paused {
            animation-play-state: paused !important;
        }
    </style>
</head>
<body>
    <?php if(isset($debug_display) && $debug_display): ?>
    <div style="position:fixed; top:10px; left:10px; background:rgba(0,0,0,0.8); color:white; padding:10px; z-index:9999; max-width:80%; max-height:200px; overflow:auto;">
        <p>디버그 정보:</p>
        <p>Narration 길이: <?php echo strlen($narration); ?></p>
        <p>Narration 처음 100자: <?php echo htmlspecialchars(substr($narration, 0, 100)); ?>...</p>
        <p>파싱 결과: <?php echo is_array($narration_data) ? "성공" : "실패"; ?></p>
    </div>
    <?php endif; ?>
    <!-- 선생님 아바타 -->
    <div class="teacher-avatar" id="teacher-avatar">
        <div class="teacher-face">
            <div class="teacher-hair"></div>
            <div class="teacher-bangs">
                <div class="bang"></div>
                <div class="bang"></div>
                <div class="bang"></div>
            </div>
            <div class="teacher-eyebrows">
                <div class="eyebrow left"></div>
                <div class="eyebrow right"></div>
            </div>
            <div class="teacher-eyes">
                <div class="teacher-eye"></div>
                <div class="teacher-eye"></div>
            </div>
            <div class="teacher-cheeks">
                <div class="cheek left"></div>
                <div class="cheek right"></div>
            </div>
            <div class="teacher-mouth"></div>
        </div>
    </div>
    
    <!-- 일시정지 버튼 -->
    <button class="pause-button playing" id="pause-button"></button>
    
    <!-- 재시작 버튼 -->
    <button class="restart-button" id="restart-button" onclick="parent.location.reload()">시작하기</button>
    
    <!-- 프로그레스 바 -->
    <div class="progress-container" id="progress-container">
        <div class="progress-bar" id="progress-bar"></div>
    </div>
    <div class="progress-text" id="progress-text">0%</div>
    
    <div class="stars" id="stars-container"></div>
    <div class="credits-container">
        <div class="credits" id="narration-content">
            <?php if ($narration_data && is_array($narration_data)): ?>
                <?php if (isset($narration_data['title'])): ?>
                    <div class="title"><?php echo $narration_data['title']; ?></div>
                <?php endif; ?>
                
                <?php if (isset($narration_data['keywords']) && is_array($narration_data['keywords']) && !empty($narration_data['keywords'])): ?>
                    <div class="keywords">
                        <?php foreach ($narration_data['keywords'] as $keyword): ?>
                            <span class="keyword"><?php echo $keyword; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($narration_data['content'])): ?>
                    <div class="content">
                        <?php 
                        if(is_array($narration_data['content'])) {
                            foreach ($narration_data['content'] as $line): ?>
                                <p><?php echo $line; ?></p>
                            <?php endforeach;
                        } else {
                            // 배열이 아닌 경우 문자열로 처리
                            echo '<p>' . nl2br($narration_data['content']) . '</p>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($narration_data['growthmindset_quote'])): ?>
                    <div class="quote"><?php echo $narration_data['growthmindset_quote']; ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="content">
                    <?php 
                    // JSON 형식이 아닌 경우 일반 텍스트로 처리
                    echo nl2br($narration); 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // 페이지 로드 즉시 별 생성 및 프로그레스 바 시작 - 더 빠른 처리
        (function() {
            // 즉시 실행 함수로 변경하여 로드 대기 없이 실행
            
            // 요소 가져오기
            const starsContainer = document.getElementById('stars-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const restartButton = document.getElementById('restart-button');
            const creditsContent = document.getElementById('narration-content');
            const pauseButton = document.getElementById('pause-button');
            
            // 초기 설정
            let progressInterval;
            const animationDuration = 60; // 애니메이션 시간(초)
            let buttonShown = false; // 버튼 표시 여부 추적
            let isPaused = false; // 일시정지 상태 추적
            let startTime = 0; // 애니메이션 시작 시간
            let pausedTime = 0; // 일시정지된 시점에서의 경과 시간
            
            // 일시정지 버튼 이벤트 리스너
            pauseButton.addEventListener('click', function() {
                if (isPaused) {
                    // 일시정지 -> 재생
                    resumeAnimation();
                } else {
                    // 재생 -> 일시정지
                    pauseAnimation();
                }
            });
            
            // 일시정지 함수
            function pauseAnimation() {
                isPaused = true;
                
                // 애니메이션 정지
                creditsContent.classList.add('paused');
                
                // 버튼 상태 변경
                pauseButton.classList.remove('playing');
                pauseButton.classList.add('paused');
                
                // 프로그레스 바 정지
                clearInterval(progressInterval);
                
                // 현재까지 경과된 시간 저장
                pausedTime = (Date.now() - startTime) / 1000;
            }
            
            // 재생 함수
            function resumeAnimation() {
                isPaused = false;
                
                // 애니메이션 재개
                creditsContent.classList.remove('paused');
                
                // 버튼 상태 변경
                pauseButton.classList.remove('paused');
                pauseButton.classList.add('playing');
                
                // 시작 시간 재설정 (경과 시간 고려)
                startTime = Date.now() - (pausedTime * 1000);
                
                // 프로그레스 바 재시작
                startProgressBar();
            }
            
            // 컨텐츠가 있는지 확인
            if(creditsContent) {
                // 별 생성 함수 - 더 적은 별로 최적화
                function createStars() {
                    const numStars = 150; // 별 개수 최적화
                    const fragment = document.createDocumentFragment(); // 성능 최적화를 위한 DocumentFragment 사용
                    
                    for (let i = 0; i < numStars; i++) {
                        const star = document.createElement('div');
                        star.classList.add('star');
                        
                        // 랜덤 위치 및 크기 설정
                        star.style.left = Math.random() * 100 + '%';
                        star.style.top = Math.random() * 100 + '%';
                        star.style.width = Math.random() * 2 + 'px';
                        star.style.height = star.style.width;
                        star.style.animationDuration = (3 + Math.random() * 7) + 's';
                        
                        fragment.appendChild(star);
                    }
                    
                    starsContainer.appendChild(fragment);
                }
                
                // 프로그레스 바 시작
                function startProgressBar() {
                    clearInterval(progressInterval); // 이전 인터벌 제거
                    
                    // 인터벌 설정 (0.1초마다 업데이트)
                    progressInterval = setInterval(function() {
                        const elapsedTime = (Date.now() - startTime) / 1000;
                        const percentage = Math.min((elapsedTime / animationDuration) * 100, 100);
                        
                        // 프로그레스 바 및 텍스트 업데이트
                        progressBar.style.width = percentage + '%';
                        progressText.textContent = Math.round(percentage) + '%';
                        
                        // 80% 이상일 때 버튼 표시
                        if (percentage >= 70 && !buttonShown) {
                            restartButton.classList.add('visible');
                            buttonShown = true;
                        }
                        
                        // 애니메이션 종료 조건
                        if (elapsedTime >= animationDuration) {
                            clearInterval(progressInterval);
                            progressText.textContent = '100%';
                            
                            // 애니메이션 종료 후 버튼 확실히 표시
                            if (!buttonShown) {
                                restartButton.classList.add('visible');
                                buttonShown = true;
                            }
                        }
                    }, 100);
                }
                
                // 애니메이션 즉시 시작을 위한 추가 코드
                function forceAnimationStart() {
                    // 애니메이션 속성 직접 설정으로 즉시 시작 보장
                    creditsContent.style.animation = 'none';
                    creditsContent.offsetHeight; // 리플로우 강제로 발생시켜 애니메이션 재설정 준비
                    creditsContent.style.animation = 'scrollCredits 60s linear forwards';
                    creditsContent.style.animationDelay = '0s';
                }
                
                // 즉시 실행
                createStars();
                startTime = Date.now(); // 시작 시간 설정
                startProgressBar();
                forceAnimationStart();
            } else {
                console.error('narration-content 요소를 찾을 수 없습니다.');
            }
        })(); // 즉시 실행 함수로 변경
    </script>
</body>
</html> 