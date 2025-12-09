<?php
// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();

// 학생 정보 가져오기
$studentName = $USER->firstname . ' ' . $USER->lastname;
$studentId = $USER->id;

// AJAX 요청 처리
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'save_report') {
        $responses = json_decode($_POST['responses'], true);
        $reportId = 'REPORT_' . time() . '_' . substr(md5(uniqid()), 0, 9);
        
        // 리포트 데이터 저장 (실제로는 DB에 저장)
        $report = new stdClass();
        $report->student_id = $studentId;
        $report->student_name = $studentName;
        $report->responses = $responses;
        $report->report_id = $reportId;
        $report->created_at = time();
        $report->date = date('Y년 n월 j일');
        
        // DB에 저장
        try {
            $record = new stdClass();
            $record->userid = $studentId;
            $record->text = json_encode($report);
            $record->timecreated = time();
            
            $DB->insert_record('alt42_goinghome', $record);
        } catch (Exception $e) {
            error_log('Error saving goinghome report: ' . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'report_id' => $reportId]);
        exit;
    }
    
    if ($_POST['action'] == 'transform_message') {
        $message = $_POST['message'];
        $context = $_POST['context'] ?? '';
        
        // OpenAI API를 사용한 메시지 변환
        $transformedMessage = transformWithOpenAI($message, $context);
        
        echo json_encode(['success' => true, 'transformed' => $transformedMessage]);
        exit;
    }
    
    if ($_POST['action'] == 'generate_question') {
        $originalQuestion = $_POST['original_question'];
        $previousResponses = json_decode($_POST['previous_responses'] ?? '[]', true);
        
        // OpenAI API를 사용한 질문 재생성
        $newQuestion = generateCreativeQuestion($originalQuestion, $previousResponses);
        
        echo json_encode(['success' => true, 'question' => $newQuestion]);
        exit;
    }
}

// OpenAI API를 사용한 메시지 변환 함수
function transformWithOpenAI($message, $context = '') {
    $apiKey = OPENAI_API_KEY;
    $model = OPENAI_MODEL;
    
    $systemPrompt = "당신은 친근하고 격려하는 AI 교사입니다. 학생의 귀가 전 체크를 도와주고 있습니다.
    학생의 답변에 대해 공감하고 격려하는 피드백을 제공해주세요. 이모지를 적절히 사용하여 친근감을 표현해주세요.
    가끔은 살짝 장난스럽게, 때로는 약간의 비아냥(하지만 상처주지 않게)을 섞어서 자연스럽고 인간적인 대화를 만들어주세요.
    매번 같은 패턴의 답변을 피하고, 다양한 어투와 표현을 사용해주세요.";
    
    $userPrompt = "학생의 답변: $message\n맥락: $context\n\n위 답변에 대한 짧고 격려하는 피드백을 제공해주세요.";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 150
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
    }
    
    // 폴백 응답
    return "잘했어! 👍";
}

// OpenAI API를 사용한 창의적 질문 생성 함수
function generateCreativeQuestion($originalQuestion, $previousResponses = []) {
    $apiKey = OPENAI_API_KEY;
    $model = OPENAI_MODEL;
    
    $systemPrompt = "당신은 재치 있고 친근한 학원 선생님입니다. 
    학생에게 귀가 전 질문을 하는데, 같은 내용을 매번 다른 표현으로 물어봐야 합니다.
    재미있고 새로운 표현을 사용하되, 학생이 편하게 답할 수 있도록 해주세요.
    이모티콘을 적절히 사용하고, 가끔 트렌디한 표현이나 유행어도 섞어주세요.
    너무 딱딱하지 않고 친근한 반말로 물어봐주세요.";
    
    $previousText = !empty($previousResponses) ? 
        "\n\n이전 대화 내용: " . json_encode($previousResponses, JSON_UNESCAPED_UNICODE) : "";
    
    $userPrompt = "원래 질문: $originalQuestion\n\n위 질문을 전혀 다른 표현으로 재미있게 바꿔주세요. 
    의미는 같아야 하지만 표현은 완전히 달라야 합니다.$previousText";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.9,
        'max_tokens' => 100
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
    }
    
    // 폴백 - 원래 질문 반환
    return $originalQuestion;
}

// 현재 날짜
$today = date('Y년 n월 j일');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 귀가검사 도우미</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-card: #242424;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --border: #333333;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body.light-mode {
            --bg-primary: #f9fafb;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 1rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .container {
            max-width: 1024px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
            text-shadow: 0 0 20px var(--accent);
        }
        
        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 0.5rem;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px var(--accent);
        }
        
        .avatar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .avatar {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .avatar:hover {
            transform: scale(1.05);
        }
        
        .avatar.wave {
            animation: bounce 0.5s ease-in-out;
        }
        
        .avatar.talk {
            animation: pulse 1s infinite;
        }
        
        .avatar.celebrate {
            animation: spin 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .main-content {
            background: var(--bg-card);
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }
        
        .message-area {
            min-height: 100px;
            margin-bottom: 1.5rem;
        }
        
        .message-text {
            font-size: 1.5rem;
            color: var(--text-primary);
            line-height: 1.8;
            font-weight: 500;
        }
        
        .typing-cursor {
            animation: blink 1s infinite;
            margin-left: 0.25rem;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .option-button {
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            background: var(--bg-secondary);
            border-radius: 0.75rem;
            font-size: 1.2rem;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .option-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: var(--accent);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .option-button:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .option-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .action-button {
            padding: 1rem 2rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 auto;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .action-button:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }
        
        .action-button.green {
            background: var(--success);
        }
        
        .action-button.green:hover {
            background: #059669;
        }
        
        .progress-bar {
            background: var(--bg-card);
            border-radius: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            padding: 1rem;
            border: 1px solid var(--border);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .progress-track {
            width: 100%;
            height: 0.5rem;
            background: var(--bg-secondary);
            border-radius: 9999px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--accent), var(--accent-hover));
            border-radius: 9999px;
            transition: width 0.5s ease-out;
        }
        
        .report {
            background: var(--bg-card);
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            padding: 1.5rem;
            max-width: 768px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
        }
        
        .report h2 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .report-info {
            background: var(--bg-secondary);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .report-info p {
            margin: 0.25rem 0;
        }
        
        .attention-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .attention-box h3 {
            color: var(--danger);
            margin-bottom: 0.5rem;
        }
        
        .attention-box ul {
            color: var(--danger);
            margin-left: 1.5rem;
        }
        
        .response-item {
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .response-question {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .response-answer {
            color: var(--accent);
            margin-top: 0.25rem;
        }
        
        .hidden {
            display: none;
        }
        
        .name-input-container {
            display: flex;
            gap: 0.5rem;
            max-width: 320px;
            margin: 0 auto;
        }
        
        .name-input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .name-input:focus {
            outline: none;
            ring: 2px solid var(--accent);
            border-color: var(--accent);
        }
        
        .celebration-container {
            margin: 2rem 0;
            text-align: center;
        }
        
        .confetti-wrapper {
            position: relative;
            height: 100px;
            overflow: hidden;
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--accent);
            animation: confetti-fall 3s linear infinite;
        }
        
        .confetti:nth-child(1) { left: 10%; animation-delay: 0s; background: #ff6b6b; }
        .confetti:nth-child(2) { left: 30%; animation-delay: 0.5s; background: #4ecdc4; }
        .confetti:nth-child(3) { left: 50%; animation-delay: 1s; background: #ffe66d; }
        .confetti:nth-child(4) { left: 70%; animation-delay: 1.5s; background: #a8e6cf; }
        .confetti:nth-child(5) { left: 90%; animation-delay: 2s; background: #ff8cc8; }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(calc(100vh + 100px)) rotate(720deg);
                opacity: 0;
            }
        }
        
        .completion-stats {
            background: var(--bg-secondary);
            border-radius: 1rem;
            padding: 2rem;
            margin: 2rem 0;
            border: 2px solid var(--accent);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.3);
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 1.2rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
        }
        
        .stat-value {
            color: var(--accent);
            font-weight: bold;
        }
        
        .pulse {
            animation: pulse-glow 2s infinite;
        }
        
        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(99, 102, 241, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }
        
        @media print {
            body {
                background: white;
            }
            
            .avatar-container,
            .action-button,
            #progressBar {
                display: none !important;
            }
            
            .report {
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                padding: 1rem;
            }
            
            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .attention-box {
                background: #f9f9f9;
                border: 2px solid #333;
            }
        }
    </style>
</head>
<body class="dark-mode">
    <button class="theme-toggle" onclick="toggleTheme()" title="테마 전환">
        <span id="themeIcon">🌙</span>
    </button>
    
    <div class="container">
        <h1>🎓 AI 귀가검사 도우미</h1>
        
        <div class="avatar-container">
            <div class="avatar" id="avatar">
                <div style="color: white; font-size: 3rem;">👩‍🏫</div>
            </div>
        </div>
        
        <div class="main-content" id="mainContent">
            <!-- 초기 화면 -->
            <div id="introStep" class="step">
                <div class="message-area">
                    <p class="message-text">안녕! 이름이 뭐야?</p>
                </div>
                <div class="name-input-container">
                    <input type="text" id="nameInput" class="name-input" placeholder="이름을 입력해줘" value="<?php echo $studentName; ?>">
                    <button onclick="handleNameSubmit()" class="action-button">시작</button>
                </div>
            </div>
            
            <!-- 환영 메시지 -->
            <div id="welcomeStep" class="step hidden">
                <div class="message-area">
                    <p class="message-text" id="welcomeMessage"></p>
                </div>
                <button onclick="startQuestions()" class="action-button">검사 시작하기 →</button>
            </div>
            
            <!-- 질문 단계 -->
            <div id="questionsStep" class="step hidden">
                <div class="message-area">
                    <p class="message-text" id="questionText"></p>
                </div>
                <div class="options-grid" id="optionsGrid"></div>
            </div>
            
            <!-- 완료 단계 -->
            <div id="completeStep" class="step hidden">
                <div class="message-area">
                    <p class="message-text" id="completeMessage"></p>
                </div>
                <div id="celebrationContainer" class="celebration-container">
                    <div class="confetti-wrapper">
                        <div class="confetti"></div>
                        <div class="confetti"></div>
                        <div class="confetti"></div>
                        <div class="confetti"></div>
                        <div class="confetti"></div>
                    </div>
                    <div class="completion-stats" id="completionStats">
                        <!-- 동적으로 생성됨 -->
                    </div>
                </div>
                <button onclick="generateReport()" class="action-button green pulse">
                    🎆 리포트 생성하기
                </button>
            </div>
        </div>
        
        <!-- 진행 상황 표시 -->
        <div id="progressBar" class="progress-bar hidden">
            <div class="progress-header">
                <span>진행 상황</span>
                <span id="progressText">1 / 6</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>
        </div>
        
        <!-- 리포트 -->
        <div id="reportSection" class="hidden"></div>
    </div>
    
    <script>
        // 전역 변수
        let currentStep = 'intro';
        let currentQuestion = 0;
        let responses = {};
        let studentName = '<?php echo $studentName; ?>';
        let selectedRandomQuestions = [];
        let typingTimeout = null;
        
        // 필수 질문
        const requiredQuestions = [
            {
                id: 'calmness',
                text: '오늘 수업 중 침착도는 어땠어?',
                options: ['A+', 'A', 'B+', 'B', 'C+', 'C', 'F'],
                followUp: {
                    'A+': '오~ A+라니! 너 혹시 명상이라도 하고 왔어? 👏 진짜 대단하다!',
                    'A': '좋아! 침착도 A라니, 이 정도면 거의 수학 수도승 아니야? 😌',
                    'B+': '에이, B+도 나쁘지 않지~ 다음엔 A 도전해보자! 할 수 있어! 💪',
                    'B': '음... B라... 오늘 좀 정신없었구나? 그래도 괜찮아, 다들 그런 날 있어~',
                    'C+': '흠... C+... 혹시 오늘 점심에 뭐 먹었어? 🤔 졸렸어?',
                    'C': '아이고... C라니... 너무 솔직한 거 아니야? 😅 내일은 좀 더 화이팅!',
                    'F': '헐... F... 무슨 일 있었어? 짝사랑하는 애가 옆에 앉았어? 🤭'
                }
            },
            {
                id: 'pomodoro',
                text: '포모도르 수학일기는 어떻게 사용했어?',
                options: ['알차게 사용', '대충 사용', '사용 안함'],
                followUp: {
                    '알차게 사용': '오호! 수학일기 마스터시네? 💪 나중에 비법 좀 알려줘~',
                    '대충 사용': '"대충"이라니... 솔직한 건 좋은데 좀 더 써보면 어때? 📝 귀찮아도 나중엔 도움돼!',
                    '사용 안함': '헉! 수학일기 안 썼다고? 😱 이러다 나중에 "아 그때 뭐 했더라..." 하면서 후회할걸?'
                }
            },
            {
                id: 'inefficiency',
                text: '오늘 비효율적으로 시간을 보낸 구간이 있었어?',
                options: ['거의 없다', '조금 있다', '좀 많았다'],
                followUp: {
                    '거의 없다': '와~ 시간 관리의 신이네? ⏰ 비결이 뭐야? 타이머라도 달고 다녀?',
                    '조금 있다': '에이~ "조금"이라고? 🤨 진짜 조금이야? 뭐 하다가 시간 날렸어?',
                    '좀 많았다': '아... "좀 많았다"... 😬 혹시 유튜브 shorts 보다가... 아니지? 아니겠지?'
                }
            }
        ];
        
        // 랜덤 질문 풀
        const randomQuestionPool = [
            // 계획 관련
            {
                id: 'weekly_goal',
                text: '주간목표를 확인하고 오늘 목표를 정했어?',
                options: ['네, 확인했어요', '깜빡했어요', '목표가 애매해요'],
                category: 'planning'
            },
            {
                id: 'daily_plan',
                text: '오늘 계획한 진도는 다 나갔어?',
                options: ['계획보다 더 했어요', '딱 맞게 했어요', '조금 못했어요', '많이 못했어요'],
                category: 'planning'
            },
            {
                id: 'pace_anxiety',
                text: '진도가 느려서 불안하지는 않았어?',
                options: ['전혀 불안 안 해요', '조금 불안해요', '많이 불안해요'],
                category: 'planning'
            },
            
            // 감정 관련
            {
                id: 'satisfaction',
                text: '오늘 수업에 대한 만족도는 어때?',
                options: ['매우 만족', '만족', '보통', '불만족'],
                category: 'emotion'
            },
            {
                id: 'boredom',
                text: '공부하다가 지루한 구간은 없었어?',
                options: ['전혀 없었어요', '조금 있었어요', '꽤 있었어요', '너무 지루했어요'],
                category: 'emotion'
            },
            {
                id: 'stress_level',
                text: '공부하다가 불안하거나 스트레스가 커진 구간은 없었어?',
                options: ['전혀 없었어요', '잠깐 있었어요', '좀 있었어요', '많이 스트레스 받았어요'],
                category: 'emotion'
            },
            {
                id: 'positive_moment',
                text: '수학공부에 대한 긍정적 인식이 생긴 장면이 있었어?',
                options: ['여러 번 있었어요', '한두 번 있었어요', '잘 모르겠어요', '없었어요'],
                category: 'emotion'
            },
            
            // 학습 과정 관련
            {
                id: 'problem_count',
                text: '오늘 몇 문제나 풀었어?',
                options: ['20문제 이상', '10-19문제', '5-9문제', '5문제 미만'],
                category: 'process'
            },
            {
                id: 'error_note',
                text: '오답노트는 밀리지 않았어?',
                options: ['전혀 안 밀렸어요', '조금 밀렸어요', '많이 밀렸어요', '오답노트 안 써요'],
                category: 'process'
            },
            {
                id: 'concept_study',
                text: '개념공부 과정은 적절했어?',
                options: ['매우 적절했어요', '괜찮았어요', '조금 부족했어요', '많이 부족했어요'],
                category: 'process'
            },
            {
                id: 'difficulty_level',
                text: '오늘 공부한 난이도가 시험대비를 고려할 때 적합했어?',
                options: ['딱 맞았어요', '조금 쉬웠어요', '조금 어려웠어요', '너무 쉽거나 어려웠어요'],
                category: 'process'
            },
            {
                id: 'easy_problems',
                text: '너무 쉬운 문제만 풀고 있는 건 아니야?',
                options: ['다양한 난이도로 풀었어요', '약간 쉬운 편이었어요', '너무 쉬운 것만 풀었어요'],
                category: 'process'
            },
            
            // 자기 관찰 관련
            {
                id: 'self_improvement',
                text: '스스로 고치고 싶은 부분이 발견됐어?',
                options: ['여러 개 발견했어요', '한두 개 있어요', '특별히 없어요'],
                category: 'reflection'
            },
            {
                id: 'missed_opportunity',
                text: '스스로 망설이다 기회를 놓친 경우는 없었어?',
                options: ['없었어요', '한두 번 있었어요', '여러 번 있었어요'],
                category: 'reflection'
            },
            {
                id: 'intuition_solving',
                text: '느낌으로 푼 문제는 없었어?',
                options: ['전부 논리적으로 풀었어요', '한두 문제 있었어요', '꽤 있었어요', '많았어요'],
                category: 'reflection'
            },
            {
                id: 'forced_solving',
                text: '무리해서 확인없이 풀이를 강행한 경우는 없었어?',
                options: ['없었어요', '한두 번 있었어요', '여러 번 있었어요'],
                category: 'reflection'
            },
            
            // 상호작용 관련
            {
                id: 'questions_asked',
                text: '필요한 질문들은 모두 했어?',
                options: ['다 물어봤어요', '대부분 물어봤어요', '조금만 물어봤어요', '거의 안 물어봤어요'],
                category: 'interaction'
            },
            {
                id: 'unsaid_words',
                text: '선생님께 할 말이 있었는데 참거나 넘어간 경우는 없었어?',
                options: ['없었어요', '한두 번 있었어요', '여러 번 있었어요'],
                category: 'interaction'
            },
            
            // 집중력 관련
            {
                id: 'rest_pattern',
                text: '휴식시간은 쉬고 공부할 때는 집중하는 패턴이 유지됐어?',
                options: ['완벽하게 유지했어요', '대체로 잘했어요', '조금 흐트러졌어요', '많이 흐트러졌어요'],
                category: 'focus'
            },
            {
                id: 'long_problem',
                text: '한 문제를 너무 오래 풀다가 집중력이 떨어진 경우는 없었어?',
                options: ['없었어요', '한두 번 있었어요', '여러 번 있었어요'],
                category: 'focus'
            },
            {
                id: 'study_amount',
                text: '오늘 공부양이 적절했다고 생각해?',
                options: ['딱 적절했어요', '조금 많았어요', '조금 적었어요', '너무 많거나 적었어요'],
                category: 'focus'
            }
        ];
        
        // 타이핑 효과
        function typeText(elementId, text, callback) {
            if (typingTimeout) {
                clearTimeout(typingTimeout);
            }
            
            const element = document.getElementById(elementId);
            element.innerHTML = '';
            let index = 0;
            
            function typeNextChar() {
                if (index < text.length) {
                    element.innerHTML += text[index];
                    index++;
                    typingTimeout = setTimeout(typeNextChar, 30);
                } else {
                    element.innerHTML += '<span class="typing-cursor">|</span>';
                    setTimeout(() => {
                        const cursor = element.querySelector('.typing-cursor');
                        if (cursor) cursor.remove();
                        if (callback) callback();
                    }, 500);
                }
            }
            
            typeNextChar();
        }
        
        // 아바타 애니메이션
        function triggerAvatarAnimation(animation) {
            const avatar = document.getElementById('avatar');
            avatar.classList.remove('wave', 'talk', 'celebrate');
            setTimeout(() => {
                avatar.classList.add(animation);
                setTimeout(() => {
                    avatar.classList.remove(animation);
                }, 2000);
            }, 10);
        }
        
        // 랜덤 질문 선택 (상관관계 고려)
        function selectRandomQuestions() {
            const selected = [];
            const allCategories = ['planning', 'emotion', 'process', 'reflection', 'interaction', 'focus'];
            
            // 첫 번째 질문은 완전 랜덤
            const firstCategory = allCategories[Math.floor(Math.random() * allCategories.length)];
            const firstQuestions = randomQuestionPool.filter(q => q.category === firstCategory);
            const firstQuestion = firstQuestions[Math.floor(Math.random() * firstQuestions.length)];
            selected.push(firstQuestion);
            
            // 두 번째 질문은 첫 번째와 연관성 있게
            let secondCategory;
            const relatedCategories = {
                'planning': ['process', 'focus'],
                'emotion': ['reflection', 'interaction'],
                'process': ['planning', 'focus'],
                'reflection': ['emotion', 'interaction'],
                'interaction': ['emotion', 'reflection'],
                'focus': ['process', 'planning']
            };
            
            const possibleCategories = relatedCategories[firstCategory];
            secondCategory = possibleCategories[Math.floor(Math.random() * possibleCategories.length)];
            const secondQuestions = randomQuestionPool.filter(q => 
                q.category === secondCategory && q.id !== firstQuestion.id
            );
            const secondQuestion = secondQuestions[Math.floor(Math.random() * secondQuestions.length)];
            selected.push(secondQuestion);
            
            // 세 번째 질문은 앞의 두 카테고리와 다른 것으로
            const usedCategories = [firstCategory, secondCategory];
            const remainingCategories = allCategories.filter(cat => !usedCategories.includes(cat));
            const thirdCategory = remainingCategories[Math.floor(Math.random() * remainingCategories.length)];
            const thirdQuestions = randomQuestionPool.filter(q => 
                q.category === thirdCategory && 
                q.id !== firstQuestion.id && 
                q.id !== secondQuestion.id
            );
            const thirdQuestion = thirdQuestions[Math.floor(Math.random() * thirdQuestions.length)];
            selected.push(thirdQuestion);
            
            selectedRandomQuestions = selected;
        }
        
        // 이름 제출
        function handleNameSubmit() {
            const nameInput = document.getElementById('nameInput').value.trim();
            if (nameInput) {
                studentName = nameInput;
                showStep('welcomeStep');
                const welcomeMsg = `안녕, ${studentName}! <?php echo $today; ?> 카이스트 터치수학 귀가 검사를 시작하겠습니다. 오늘 하루 어땠어? 😊`;
                typeText('welcomeMessage', welcomeMsg);
                triggerAvatarAnimation('wave');
            }
        }
        
        // 질문 시작
        function startQuestions() {
            currentStep = 'questions';
            showStep('questionsStep');
            document.getElementById('progressBar').classList.remove('hidden');
            showQuestion();
        }
        
        // 질문 표시
        async function showQuestion() {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            const question = allQuestions[currentQuestion];
            
            // 랜덤 질문의 경우 OpenAI API로 재생성
            let questionText = question.text;
            if (question.category) { // 랜덤 질문인 경우
                try {
                    const formData = new FormData();
                    formData.append('action', 'generate_question');
                    formData.append('original_question', question.text);
                    formData.append('previous_responses', JSON.stringify(responses));
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success && data.question) {
                        questionText = data.question;
                    }
                } catch (error) {
                    console.error('Failed to generate creative question:', error);
                }
            }
            
            typeText('questionText', questionText, () => {
                showOptions(question.options);
            });
            triggerAvatarAnimation('talk');
            updateProgress();
        }
        
        // 옵션 표시
        function showOptions(options) {
            const grid = document.getElementById('optionsGrid');
            grid.innerHTML = '';
            
            options.forEach((option, index) => {
                setTimeout(() => {
                    const button = document.createElement('button');
                    button.className = 'option-button';
                    button.textContent = option;
                    button.onclick = () => handleAnswer(option);
                    grid.appendChild(button);
                }, index * 100);
            });
        }
        
        // 답변 처리
        function handleAnswer(answer) {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            const question = allQuestions[currentQuestion];
            
            responses[question.id] = answer;
            
            // 옵션 숨기기
            document.getElementById('optionsGrid').innerHTML = '';
            
            // 피드백 표시
            const showNextQuestion = () => {
                if (currentQuestion < allQuestions.length - 1) {
                    currentQuestion++;
                    showQuestion();
                } else {
                    // 완료
                    showStep('completeStep');
                    showCompletionScreen();
                    document.getElementById('progressBar').classList.add('hidden');
                }
            };
            
            if (question.followUp && question.followUp[answer]) {
                typeText('questionText', question.followUp[answer], () => {
                    setTimeout(showNextQuestion, 1000);
                });
            } else {
                // 더 다양하고 자연스러운 랜덤 응답
                const genericResponses = [
                    '오~ 그렇구나! 다음 질문 갈게~',
                    '음음, 알겠어! 메모해둘게 📝',
                    '아하! 그랬구나~ 이해했어!',
                    '오케이~ 다음 거!',
                    '흠... 흥미롭네? 🤔',
                    '그래그래~ 알겠어!',
                    '오호라~ 그렇군!',
                    '알았어 알았어~ 다음!',
                    '음... 나름 괜찮네? 계속 가보자!',
                    '좋아좋아~ 잘하고 있어!',
                    '오~ 의외인데? 😮',
                    '그렇겠지... 그럴 수 있지!',
                    '아 정말? 재밌네~',
                    '오케바리~ 다음 질문!',
                    '음... 뭐 그럴 수도 있지 뭐~'
                ];
                const randomResponse = genericResponses[Math.floor(Math.random() * genericResponses.length)];
                typeText('questionText', randomResponse, () => {
                    setTimeout(showNextQuestion, 800);
                });
            }
        }
        
        // 진행 상황 업데이트
        function updateProgress() {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            const progress = ((currentQuestion + 1) / allQuestions.length) * 100;
            
            document.getElementById('progressText').textContent = `${currentQuestion + 1} / ${allQuestions.length}`;
            document.getElementById('progressFill').style.width = `${progress}%`;
        }
        
        // 리포트 생성
        function generateReport() {
            // AJAX로 리포트 저장
            const formData = new FormData();
            formData.append('action', 'save_report');
            formData.append('responses', JSON.stringify(responses));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showReport(data.report_id);
                } else {
                    console.error('리포트 저장 실패');
                    alert('리포트 저장에 실패했습니다. 다시 시도해주세요.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('리포트 생성 중 오류가 발생했습니다.');
            });
        }
        
        // 리포트 표시
        function showReport(reportId) {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            
            // 주의 필요 항목 체크
            const needsAttention = [];
            if (responses.calmness && ['C+', 'C', 'F'].includes(responses.calmness)) {
                needsAttention.push('침착도가 낮음');
            }
            if (responses.pomodoro === '사용 안함') {
                needsAttention.push('수학일기 미사용');
            }
            if (responses.inefficiency === '좀 많았다') {
                needsAttention.push('비효율적 시간 많음');
            }
            
            let reportHTML = `
                <div class="report">
                    <h2>📋 귀가검사 리포트</h2>
                    <div class="report-info">
                        <p>👤 학생: ${studentName}</p>
                        <p>🕐 날짜: <?php echo $today; ?></p>
                        <p>리포트 ID: ${reportId}</p>
                    </div>
            `;
            
            if (needsAttention.length > 0) {
                reportHTML += `
                    <div class="attention-box">
                        <h3>⚠️ 주의 필요 사항</h3>
                        <ul>
                            ${needsAttention.map(item => `<li>${item}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            reportHTML += '<div style="margin-top: 1.5rem;"><h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">📝 응답 내용</h3>';
            
            allQuestions.forEach(q => {
                if (responses[q.id]) {
                    reportHTML += `
                        <div class="response-item">
                            <p class="response-question">${q.text}</p>
                            <p class="response-answer">→ ${responses[q.id]}</p>
                        </div>
                    `;
                }
            });
            
            reportHTML += `</div>
                <div style="text-align: center; margin-top: 2rem;">
                    <button onclick="window.print()" class="action-button green">
                        🖨️ 리포트 인쇄하기
                    </button>
                </div>
            </div>`;
            
            document.getElementById('mainContent').classList.add('hidden');
            document.getElementById('reportSection').innerHTML = reportHTML;
            document.getElementById('reportSection').classList.remove('hidden');
            
            triggerAvatarAnimation('celebrate');
        }
        
        // 단계 표시
        function showStep(stepId) {
            document.querySelectorAll('.step').forEach(step => {
                step.classList.add('hidden');
            });
            document.getElementById(stepId).classList.remove('hidden');
        }
        
        // Enter 키 처리
        document.getElementById('nameInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleNameSubmit();
            }
        });
        
        // 테마 전환
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('themeIcon');
            
            if (body.classList.contains('dark-mode')) {
                body.classList.remove('dark-mode');
                body.classList.add('light-mode');
                icon.textContent = '☀️';
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.remove('light-mode');
                body.classList.add('dark-mode');
                icon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // 완료 화면 표시
        function showCompletionScreen() {
            const messages = [
                `대박! ${studentName}, 오늘 진짜 열심히 했네! 🎆`,
                `와우! ${studentName}, 너 오늘 진짜 멋있었어! 🎉`,
                `최고야! ${studentName}, 오늘도 성공적인 하루! 🎊`,
                `짱이야! ${studentName}, 오늘 공부 완전 정복! 🚀`
            ];
            
            const randomMsg = messages[Math.floor(Math.random() * messages.length)];
            typeText('completeMessage', randomMsg);
            triggerAvatarAnimation('celebrate');
            
            // 통계 표시
            setTimeout(() => {
                showCompletionStats();
            }, 1000);
        }
        
        // 완료 통계 표시
        function showCompletionStats() {
            const stats = {
                '오늘 푼 문제 수': Math.floor(Math.random() * 15) + 10,
                '집중도 점수': Math.floor(Math.random() * 30) + 70,
                '학습 효율성': Math.floor(Math.random() * 20) + 80,
                '오늘의 MVP 지수': '⭐'.repeat(Math.floor(Math.random() * 3) + 3)
            };
            
            let statsHTML = '';
            for (const [label, value] of Object.entries(stats)) {
                statsHTML += `
                    <div class="stat-item">
                        <span class="stat-label">${label}</span>
                        <span class="stat-value">${value}${typeof value === 'number' ? '%' : ''}</span>
                    </div>
                `;
            }
            
            document.getElementById('completionStats').innerHTML = statsHTML;
        }
        
        // 테마 불러오기
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') {
            document.body.classList.remove('dark-mode');
            document.body.classList.add('light-mode');
            document.getElementById('themeIcon').textContent = '☀️';
        }
        
        // 초기화
        selectRandomQuestions();
    </script>
</body>
</html>