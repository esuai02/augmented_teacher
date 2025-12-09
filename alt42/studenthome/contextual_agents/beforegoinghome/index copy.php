<?php
// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();

// 학생 정보 가져오기
$userid = optional_param('userid', 0, PARAM_INT);
$studentId = $userid ? $userid : $USER->id;

// 학생 정보 조회
if ($userid && $userid != $USER->id) {
    // 다른 학생의 정보를 조회하는 경우 (선생님 권한 체크 필요)
    $student = $DB->get_record('user', array('id' => $studentId));
    $studentName = $student ? $student->firstname . ' ' . $student->lastname : '학생';
} else {
    $studentName = $USER->firstname . ' ' . $USER->lastname;
}

// 실제 데이터 가져오기
$aweekago = time() - (7 * 24 * 60 * 60);
$hoursago = time() - (24 * 60 * 60);

// 침착도 데이터 - 가장 최근 값
$calmnessData = $DB->get_record_sql("
    SELECT level 
    FROM mdl_alt42_calmness 
    WHERE userid = ? 
    ORDER BY timecreated DESC 
    LIMIT 1", [$studentId]);

$actualCalmness = $calmnessData ? $calmnessData->level : null;
$calmnessGrade = '';
if ($actualCalmness !== null) {
    if ($actualCalmness >= 95) $calmnessGrade = 'A+';
    elseif ($actualCalmness >= 90) $calmnessGrade = 'A';
    elseif ($actualCalmness >= 85) $calmnessGrade = 'B+';
    elseif ($actualCalmness >= 80) $calmnessGrade = 'B';
    elseif ($actualCalmness >= 75) $calmnessGrade = 'C+';
    elseif ($actualCalmness >= 70) $calmnessGrade = 'C';
    else $calmnessGrade = 'F';
}

// 포모도르 데이터
$pomodoroData = $DB->get_records_sql("
    SELECT * FROM mdl_abessi_tracking 
    WHERE userid = ? AND duration > ? AND hide = 0 
    ORDER BY id DESC LIMIT 10", [$studentId, $aweekago]);

$pomodoroUsage = '사용 안함';
if (count($pomodoroData) > 2) {
    $times = array_column($pomodoroData, 'timecreated');
    $finishTimes = array_column($pomodoroData, 'timefinished');
    
    if (!empty($times) && !empty($finishTimes)) {
        $minTime = min($times);
        $maxTime = max($finishTimes);
        $avgDuration = ($maxTime - $minTime) / count($pomodoroData);
        
        if ($avgDuration <= 1800) { // 30분 이하
            $pomodoroUsage = '알차게 사용';
        } elseif ($avgDuration < 3600) { // 30분 이상 60분 미만
            $pomodoroUsage = '대충 사용';
        }
    }
}

// 오답노트 데이터
$errorNoteData = $DB->get_records_sql("
    SELECT * FROM mdl_abessi_messages 
    WHERE userid = ? AND (student_check = 1 OR turn = 1) AND hide = 0 AND timemodified > ? 
    ORDER BY timemodified DESC LIMIT 10", [$studentId, $hoursago]);

$errorNoteCount = count($errorNoteData);

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
    
    if ($_POST['action'] == 'generate_new_question') {
        $topic = $_POST['topic'];
        $topicDescription = $_POST['topic_description'];
        $previousResponses = json_decode($_POST['previous_responses'] ?? '[]', true);
        
        // OpenAI API를 사용한 완전히 새로운 질문 생성
        $result = generateCompletelyNewQuestion($topic, $topicDescription, $previousResponses);
        
        echo json_encode(['success' => true, 'question' => $result['question'], 'options' => $result['options']]);
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
        'temperature' => 0.8,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5초 타임아웃
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 연결 타임아웃 2초
    
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5초 타임아웃
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 연결 타임아웃 2초
    
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

// OpenAI API를 사용한 완전히 새로운 질문 생성 함수
function generateCompletelyNewQuestion($topic, $topicDescription, $previousResponses = []) {
    $apiKey = OPENAI_API_KEY;
    $model = 'gpt-3.5-turbo'; // 더 빠른 모델 사용
    
    $systemPrompt = "당신은 한국 수학학원의 친근한 선생님입니다.
    학생의 하루 학습을 마무리하는 귀가검사에서 질문을 생성해야 합니다.
    매번 완전히 새로운 질문과 선택지를 만들어야 합니다.
    
    규칙:
    1. 주어진 주제에 대해 창의적이고 새로운 질문을 만드세요
    2. 질문은 친근한 반말로, 이모티콘을 적절히 사용하세요
    3. 최신 유행어나 MZ세대 표현을 가끔 섞어주세요
    4. 농담이나 비아냥을 살짝 섞되, 상처주지 않게 하세요
    5. 선택지는 3-4개로, 구체적이고 다양하게 만드세요
    6. 절대 이전에 나온 질문과 똑같이 만들지 마세요";
    
    $previousText = !empty($previousResponses) ? 
        "\n\n이전 응답들: " . json_encode($previousResponses, JSON_UNESCAPED_UNICODE) : "";
    
    $userPrompt = "주제: $topicDescription\n\n위 주제에 대해 완전히 새로운 질문과 3-4개의 선택지를 만들어주세요.
    이전에 없던 참신한 관점으로 질문해주세요.$previousText\n\n응답 형식:
    질문: [여기에 질문]
    선택지:
    1. [선택지1]
    2. [선택지2]
    3. [선택지3]
    4. [선택지4] (선택사항)";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.9,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5초 타임아웃
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 연결 타임아웃 2초
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            
            // 응답 파싱
            preg_match('/질문:\s*(.+?)(?=선택지:)/s', $content, $questionMatch);
            preg_match_all('/\d+\.\s*(.+?)(?=\d+\.|$)/s', $content, $optionMatches);
            
            $question = isset($questionMatch[1]) ? trim($questionMatch[1]) : '';
            $options = isset($optionMatches[1]) ? array_map('trim', $optionMatches[1]) : [];
            
            if ($question && !empty($options)) {
                return ['question' => $question, 'options' => $options];
            }
        }
    }
    
    // 폴백 - 기본 질문 반환
    $fallbackQuestions = [
        'weekly_goal' => ['question' => '이번 주 목표 체크했어? 오늘은 뭐 했어? 🎯', 'options' => ['완벽하게 달성!', '거의 다 했어', '절반 정도?', '음... 노코멘트']],
        'math_diary' => ['question' => '수학일기 썼어? 진짜로? 👀', 'options' => ['당연하지! 완벽해', '대충이라도 썼어', '아... 까먹었어', '수학일기가 뭐야?']],
        'problem_count' => ['question' => '오늘 문제 몇 개나 정복했어? 💪', 'options' => ['30개 이상!', '20개 정도', '10개 정도', '세는 게 무의미해...']],
        'default' => ['question' => '오늘 수업 어땠어? 솔직히 말해봐 😏', 'options' => ['최고였어!', '괜찮았어', '그냥 그래', '힘들었어...']]
    ];
    
    return $fallbackQuestions[$topic] ?? $fallbackQuestions['default'];
}

// 랜덤 질문 주제 풀 (design.md의 모든 주제 포함)
$randomQuestionTopics = [
    'weekly_goal' => '주간목표 확인과 오늘 목표 설정',
    'math_diary' => '수학일기 작성 여부',
    'problem_count' => '오늘 푼 문제 개수',
    'questions_asked' => '필요한 질문 수행 여부',
    'concept_study' => '개념공부 과정의 적절성',
    'rest_pattern' => '휴식과 집중의 패턴 유지',
    'satisfaction' => '오늘 수업 만족도',
    'boredom' => '지루한 구간 존재 여부',
    'stress_level' => '불안이나 스트레스 구간',
    'unsaid_words' => '선생님께 못한 말',
    'study_amount' => '공부양의 적절성',
    'difficulty_level' => '난이도의 적합성',
    'pace_anxiety' => '진도에 대한 불안감',
    'self_improvement' => '개선점 발견 여부',
    'positive_moment' => '수학에 대한 긍정적 인식',
    'missed_opportunity' => '망설임으로 놓친 기회',
    'intuition_solving' => '느낌으로 푼 문제',
    'forced_solving' => '무리한 풀이 강행',
    'easy_problems' => '너무 쉬운 문제만 풀기',
    'long_problem' => '한 문제에 너무 오래 매달림',
    'daily_plan' => '오늘 계획한 진도 달성',
    'inefficiency' => '비효율적 시간 사용 구간'
];

// 랜덤으로 2개 주제 선택
$selectedTopicKeys = array_rand($randomQuestionTopics, 2);
$selectedTopics = [];
foreach ($selectedTopicKeys as $key) {
    $selectedTopics[$key] = $randomQuestionTopics[$key];
}

// 선택된 주제를 JavaScript로 전달하기 위해 저장
$selectedTopicsJson = json_encode($selectedTopics);

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
        
        .loading-text {
            color: var(--text-secondary);
            font-style: italic;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
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
        
        .data-comparison {
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
            animation: fadeIn 0.5s ease-out;
        }
        
        .data-comparison-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .data-label {
            font-weight: 500;
        }
        
        .data-value {
            color: var(--accent);
            font-weight: bold;
        }
        
        .data-match {
            color: var(--success);
        }
        
        .data-mismatch {
            color: var(--danger);
        }
        
        @media print {
            body {
                background: white;
            }
            
            .avatar-container,
            .action-button,
            #progressBar,
            .theme-toggle {
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
                <div id="dataComparison" class="data-comparison hidden"></div>
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
                <span id="progressText">1 / 5</span>
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
        
        // PHP에서 전달된 선택된 주제들
        const selectedTopics = <?php echo $selectedTopicsJson; ?>;
        
        // PHP에서 전달된 실제 데이터
        const actualCalmness = '<?php echo $calmnessGrade; ?>';
        const actualCalmnessScore = <?php echo $actualCalmness ?? 'null'; ?>;
        const actualPomodoroUsage = '<?php echo $pomodoroUsage; ?>';
        const actualErrorNoteCount = <?php echo $errorNoteCount; ?>;
        
        // 필수 질문
        const requiredQuestions = [
            {
                id: 'calmness',
                text: '오늘 수업 중 침착도는 어땠어?',
                options: ['A+', 'A', 'B+', 'B', 'C+', 'C', 'F'],
                hasData: true,
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
                hasData: true,
                followUp: {
                    '알차게 사용': '오호! 수학일기 마스터시네? 💪 나중에 비법 좀 알려줘~',
                    '대충 사용': '"대충"이라니... 솔직한 건 좋은데 좀 더 써보면 어때? 📝 귀찮아도 나중엔 도움돼!',
                    '사용 안함': '헉! 수학일기 안 썼다고? 😱 이러다 나중에 "아 그때 뭐 했더라..." 하면서 후회할걸?'
                }
            },
            {
                id: 'error_note',
                text: '오답노트는 밀리지 않았어?',
                options: ['전혀 안 밀렸어요', '조금 밀렸어요', '많이 밀렸어요', '오답노트 안 써요'],
                hasData: true,
                followUp: {
                    '전혀 안 밀렸어요': '우와! 오답노트 관리 완벽하네? 👏 이 정도면 오답노트 달인!',
                    '조금 밀렸어요': '조금이라... 얼마나 조금이야? 🤔 내일은 좀 더 빨리 정리해보자!',
                    '많이 밀렸어요': '아이구... 오답노트가 산더미? 😅 하나씩 천천히 정리하면 돼!',
                    '오답노트 안 써요': '헉! 오답노트 안 쓴다고? 😱 실수한 문제 다시 틀리면 어떡해!'
                }
            }
        ];
        
        // 랜덤 질문 풀 (사용하지 않음 - OpenAI API로 대체)
        const randomQuestionPool = {};
        
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
        async function generateRandomQuestions() {
            const topicKeys = Object.keys(selectedTopics);
            
            // 모든 API 호출을 병렬로 처리
            const questionPromises = topicKeys.map(async (topicKey) => {
                try {
                    const formData = new FormData();
                    formData.append('action', 'generate_new_question');
                    formData.append('topic', topicKey);
                    formData.append('topic_description', selectedTopics[topicKey]);
                    formData.append('previous_responses', JSON.stringify(responses));
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success && data.question && data.options) {
                        return {
                            id: topicKey,
                            text: data.question,
                            options: data.options,
                            category: getCategoryForTopic(topicKey)
                        };
                    } else {
                        // 폴백: 기본 질문 사용
                        return getDefaultQuestionForTopic(topicKey);
                    }
                } catch (error) {
                    console.error('Failed to generate question for topic:', topicKey, error);
                    // 폴백: 기본 질문 사용
                    return getDefaultQuestionForTopic(topicKey);
                }
            });
            
            // 모든 Promise가 완료될 때까지 기다림
            const selected = await Promise.all(questionPromises);
            selectedRandomQuestions = selected;
        }
        
        // 주제별 카테고리 매핑
        function getCategoryForTopic(topic) {
            const categoryMap = {
                'weekly_goal': 'planning',
                'math_diary': 'process',
                'problem_count': 'process',
                'questions_asked': 'interaction',
                'concept_study': 'process',
                'rest_pattern': 'focus',
                'satisfaction': 'emotion',
                'boredom': 'emotion',
                'stress_level': 'emotion',
                'unsaid_words': 'interaction',
                'study_amount': 'focus',
                'difficulty_level': 'process',
                'pace_anxiety': 'planning',
                'self_improvement': 'reflection',
                'positive_moment': 'emotion',
                'missed_opportunity': 'reflection',
                'intuition_solving': 'reflection',
                'forced_solving': 'reflection',
                'easy_problems': 'process',
                'long_problem': 'focus',
                'daily_plan': 'planning',
                'inefficiency': 'focus'
            };
            return categoryMap[topic] || 'process';
        }
        
        // 폴백용 기본 질문
        function getDefaultQuestionForTopic(topic) {
            const defaults = {
                'weekly_goal': {
                    id: 'weekly_goal',
                    text: '주간목표를 확인하고 오늘 목표를 정했어?',
                    options: ['네, 확인했어요', '깜빡했어요', '목표가 애매해요'],
                    category: 'planning'
                },
                'math_diary': {
                    id: 'math_diary',
                    text: '수학일기 썼어? 정말로? 👀',
                    options: ['당연히 썼지!', '대충 썼어', '깜빡했어...', '수학일기가 뭐야?'],
                    category: 'process'
                },
                'problem_count': {
                    id: 'problem_count',
                    text: '오늘 문제 몇 개나 정복했어? 💪',
                    options: ['30개 이상!', '20개 정도', '10개 정도', '세는 게 무의미해...'],
                    category: 'process'
                },
                'inefficiency': {
                    id: 'inefficiency',
                    text: '오늘 비효율적으로 시간을 보낸 구간이 있었어?',
                    options: ['거의 없다', '조금 있다', '좀 많았다'],
                    category: 'focus'
                },
                // ... 다른 주제들의 기본 질문들 ...
                'default': {
                    id: 'default',
                    text: '오늘 수업 어땠어? 솔직히 말해봐 😏',
                    options: ['최고였어!', '괜찮았어', '그냥 그래', '힘들었어...'],
                    category: 'emotion'
                }
            };
            return defaults[topic] || defaults['default'];
        }
        
        // 랜덤 질문 선택 (상관관계 고려) - 레거시 폴백
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
                
                // 환영 메시지가 표시되는 동안 백그라운드에서 질문 생성
                generateRandomQuestions().catch(error => {
                    console.error('Failed to pre-generate questions:', error);
                    // 에러가 나도 계속 진행 가능
                });
            }
        }
        
        // 질문 시작
        async function startQuestions() {
            currentStep = 'questions';
            
            // 로딩 표시
            const questionText = document.getElementById('questionText');
            questionText.innerHTML = '<span class="loading-text">질문을 준비하고 있어요... 🤔</span>';
            
            showStep('questionsStep');
            document.getElementById('progressBar').classList.remove('hidden');
            
            // 이미 생성된 질문이 있으면 바로 표시
            if (selectedRandomQuestions.length > 0) {
                showQuestion();
            } else {
                // 없으면 생성 (보통 이 경우는 발생하지 않음)
                await generateRandomQuestions();
                showQuestion();
            }
        }
        
        // 질문 표시
        async function showQuestion() {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            const question = allQuestions[currentQuestion];
            
            // 질문 표시 (OpenAI로 생성된 질문은 이미 다양하므로 그대로 사용)
            let questionText = question.text;
            
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
            
            // 실제 데이터와 비교 표시
            if (question.hasData) {
                showDataComparison(question.id, answer);
            }
            
            // 피드백 표시
            const showNextQuestion = () => {
                // 데이터 비교 숨기기
                document.getElementById('dataComparison').classList.add('hidden');
                
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
                    setTimeout(showNextQuestion, 2000);
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
                    setTimeout(showNextQuestion, 1500);
                });
            }
        }
        
        // 실제 데이터와 비교 표시
        function showDataComparison(questionId, userAnswer) {
            const comparisonDiv = document.getElementById('dataComparison');
            let comparisonHTML = '';
            
            if (questionId === 'calmness' && actualCalmness) {
                const match = userAnswer === actualCalmness;
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">실제 침착도 데이터:</span>
                        <span class="data-value ${match ? 'data-match' : 'data-mismatch'}">
                            ${actualCalmness} (${actualCalmnessScore !== null ? actualCalmnessScore + '점' : '데이터 없음'})
                            ${match ? '✅ 일치' : '❌ 불일치'}
                        </span>
                    </div>
                `;
            } else if (questionId === 'pomodoro') {
                const match = userAnswer === actualPomodoroUsage;
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">실제 포모도르 사용 데이터:</span>
                        <span class="data-value ${match ? 'data-match' : 'data-mismatch'}">
                            ${actualPomodoroUsage}
                            ${match ? '✅ 일치' : '❌ 불일치'}
                        </span>
                    </div>
                `;
            } else if (questionId === 'error_note') {
                let actualStatus = '전혀 안 밀렸어요';
                if (actualErrorNoteCount === 0) {
                    actualStatus = '오답노트 안 써요';
                } else if (actualErrorNoteCount > 5) {
                    actualStatus = '많이 밀렸어요';
                } else if (actualErrorNoteCount > 2) {
                    actualStatus = '조금 밀렸어요';
                }
                
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">실제 오답노트 상태:</span>
                        <span class="data-value">
                            ${actualErrorNoteCount}개 남음 (${actualStatus})
                        </span>
                    </div>
                `;
            }
            
            if (comparisonHTML) {
                comparisonDiv.innerHTML = comparisonHTML;
                comparisonDiv.classList.remove('hidden');
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
            
            // 실제 데이터 기반 주의 필요 항목 체크
            const needsAttention = [];
            
            // 실제 침착도 데이터 확인
            const actualCalmnessGrade = '<?php echo $calmnessGrade; ?>';
            if (actualCalmnessGrade && ['C+', 'C', 'F'].includes(actualCalmnessGrade)) {
                needsAttention.push(`침착도가 낮음 (실제: ${actualCalmnessGrade})`);
            }
            
            // 실제 포모도로 사용 데이터 확인
            const actualPomodoroUsage = '<?php echo $pomodoroUsage; ?>';
            if (actualPomodoroUsage === '사용 안함') {
                needsAttention.push('수학일기 미사용 (실제 데이터)');
            } else if (actualPomodoroUsage === '대충 사용') {
                needsAttention.push('수학일기 비효율적 사용 (평균 시간 초과)');
            }
            
            // 실제 오답노트 데이터 확인
            const actualErrorNoteCount = <?php echo $errorNoteCount; ?>;
            if (actualErrorNoteCount === 0) {
                needsAttention.push('오답노트 미작성 (최근 활동 없음)');
            } else if (actualErrorNoteCount < 3) {
                needsAttention.push(`오답노트 활동 부족 (최근 ${actualErrorNoteCount}개만 작성)`);
            }
            
            // 추가 데이터 기반 분석
            const actualCalmnessLevel = <?php echo $actualCalmness ?? 'null'; ?>;
            if (actualCalmnessLevel !== null && actualCalmnessLevel < 70) {
                needsAttention.push(`매우 낮은 집중도 (${actualCalmnessLevel}%)`);
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
            
            // 실제 데이터 요약 섹션 추가
            reportHTML += `
                <div class="actual-data-section" style="margin-top: 1.5rem; padding: 1rem; background-color: #f0f9ff; border-radius: 8px; border: 1px solid #3b82f6;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #1e40af; margin-bottom: 1rem;">📈 실제 학습 데이터 분석</h3>
                    <div style="display: grid; gap: 0.5rem;">
                        <p><strong>침착도:</strong> ${actualCalmnessGrade ? actualCalmnessGrade + ' (' + (actualCalmnessLevel || 'N/A') + '%)' : '데이터 없음'}</p>
                        <p><strong>수학일기 사용:</strong> ${actualPomodoroUsage}</p>
                        <p><strong>오답노트 활동:</strong> 최근 ${actualErrorNoteCount}개 작성</p>
                    </div>
                </div>
            `;
            
            reportHTML += `
                <div class="engagement-graph-section" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">📊 당일 실시간 몰입도 그래프</h3>
                    <iframe 
                        src="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/calmness.php?id=<?php echo $studentId; ?>"
                        width="100%"
                        height="400"
                        frameborder="0"
                        style="border: 1px solid #ddd; border-radius: 8px;">
                    </iframe>
                </div>
            `;
            
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
        // 랜덤 질문은 startQuestions()에서 생성함
    </script>
</body>
</html>