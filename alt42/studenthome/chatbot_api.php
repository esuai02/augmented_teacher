<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php");
global $DB, $USER;
require_login();

// Set JSON response header
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$student_id = isset($input['student_id']) ? intval($input['student_id']) : $USER->id;

// Verify student access
if ($student_id !== $USER->id) {
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
    $role = $userrole ? $userrole->data : 'student';
    
    if ($role === 'student') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
}

switch ($action) {
    case 'send_message':
        handleSendMessage($input, $student_id);
        break;
    
    case 'get_history':
        handleGetHistory($student_id);
        break;
    
    case 'clear_history':
        handleClearHistory($student_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

/**
 * Handle sending a message and getting AI response
 */
function handleSendMessage($input, $student_id) {
    global $DB;
    
    $message = isset($input['message']) ? trim($input['message']) : '';
    
    // Get the actual selected mode from persona_modes table
    $persona_mode = $DB->get_record_sql(
        "SELECT * FROM {persona_modes} WHERE student_id = :studentid ORDER BY timecreated DESC LIMIT 1",
        array('studentid' => $student_id)
    );
    
    // Use the selected student_mode from database, fallback to input or default
    if ($persona_mode && !empty($persona_mode->student_mode)) {
        $learning_mode = $persona_mode->student_mode;
    } else {
        $learning_mode = isset($input['learning_mode']) ? $input['learning_mode'] : 'curriculum';
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        return;
    }
    
    // Store user message
    $user_message = new stdClass();
    $user_message->student_id = $student_id;
    $user_message->learning_mode = $learning_mode;
    $user_message->message_type = 'user';
    $user_message->message = $message;
    $user_message->timestamp = time();
    
    try {
        // Check if table exists first
        $table_exists = $DB->get_manager()->table_exists('chatbot_messages');
        if (!$table_exists) {
            echo json_encode(['success' => false, 'message' => 'Database table not found. Please run setup script.']);
            return;
        }
        
        $DB->insert_record('chatbot_messages', $user_message);
    } catch (Exception $e) {
        error_log("Failed to insert user message: " . $e->getMessage());
        // Continue anyway to provide response even if logging fails
    }
    
    // Get recent conversation history for context
    $recent_messages = [];
    try {
        $recent_messages = $DB->get_records_sql(
            "SELECT * FROM {chatbot_messages} 
             WHERE student_id = :studentid 
             ORDER BY timestamp DESC 
             LIMIT 10",
            array('studentid' => $student_id)
        );
    } catch (Exception $e) {
        // If table doesn't exist, continue without history
        error_log("Could not fetch message history: " . $e->getMessage());
    }
    
    // Build conversation context
    $conversation = !empty($recent_messages) ? array_reverse($recent_messages) : [];
    
    // Get AI response
    $ai_response = getAIResponse($message, $learning_mode, $conversation, $student_id);
    
    // Store bot response
    $bot_message = new stdClass();
    $bot_message->student_id = $student_id;
    $bot_message->learning_mode = $learning_mode;
    $bot_message->message_type = 'bot';
    $bot_message->message = $ai_response;
    $bot_message->timestamp = time();
    
    try {
        $DB->insert_record('chatbot_messages', $bot_message);
    } catch (Exception $e) {
        error_log("Failed to insert bot message: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'response' => $ai_response]);
}

/**
 * Get AI response using OpenAI API with learning mode context
 */
function getAIResponse($message, $learning_mode, $conversation, $student_id) {
    global $DB;
    
    // Get student info
    $student = $DB->get_record('user', array('id' => $student_id));
    $student_name = $student ? $student->firstname : '학생';
    
    // Define comprehensive learning mode worldviews with W-X-S-P-E-R-T-A framework
    $mode_personalities = [
        'curriculum' => [
            'name' => '📚 체계적 진도형 학습 도우미',
            'worldview' => '진도는 전략, 보정은 일상',
            'personality' => '교과-단원 선형 진도와 주간 진단-보정 루프를 중시하는 체계적 학습 전문가입니다. 단원 마스터리 → 누적 복습(7:3) → 월간 커리 리셋의 순환 구조로 안내합니다.',
            'approach' => '주간 진도달성 ≥90%, 단원 마스터리 ≥80%, 오답감소율 주차당 ≥20%, 스터디타임 ≥12h/주',
            'execution' => '매일 정시 학습, 주간 진도 체크, 7:3 복습 유지, 월간 점검',
            'switching_triggers' => 'D-30 시험모드 전환, 진도이탈 >10% 시 맞춤형 병행'
        ],
        'exam' => [
            'name' => '✏️ 성과 집중형 학습 도우미',
            'worldview' => '시험은 전투, 출제자는 상대',
            'personality' => '단기 고득점 달성을 위한 전략적 학습 코치입니다. 오답률 10% 미만, 시간관리 최적화, 출제빈도 분석을 통해 효율적인 시험 대비를 지원합니다.',
            'approach' => '3주 로드맵: W1 기출분석 → W2 약점집중 → W3 실전연습, 일일 50문항 처리',
            'execution' => '기출 3회독, 오답노트 2회독, 시간압박 훈련, D-3 컨디션 조절',
            'switching_triggers' => '정답률 <60% 시 기초모드, 시간초과 빈발 시 속도훈련'
        ],
        'custom' => [
            'name' => '🎯 개인맞춤형 학습 도우미',
            'worldview' => '모든 학생은 고유한 학습 DNA를 가진다',
            'personality' => '개인별 학습 스타일과 속도에 최적화된 맞춤형 튜터입니다. 강점 극대화, 약점 보완, 개인화된 학습 경로를 설계합니다.',
            'approach' => '주 2회 진단평가, 강약점 매트릭스 관리, 개인별 속도 조절, 맞춤형 콘텐츠 큐레이션',
            'execution' => 'MBTI별 학습법 적용, 시간대별 집중도 분석, 선호 문제유형 우선배치',
            'switching_triggers' => '진도 격차 발생 시 속도조절, 흥미 저하 시 선호유형 전환'
        ],
        'mission' => [
            'name' => '⚡ 목표달성형 학습 도우미',
            'worldview' => '작은 승리가 큰 성공을 만든다',
            'personality' => '게이미피케이션과 단기 목표 달성을 통한 동기부여 전문가입니다. 일일미션, 주간퀘스트, 월간챌린지로 학습을 재미있게 만듭니다.',
            'approach' => '일 5미션, 주 보스전, 월 레벨업, 포인트/배지 시스템, 연속기록 관리',
            'execution' => '아침 데일리 체크인, 미션 완료 즉시보상, 주간 랭킹 공유',
            'switching_triggers' => '3일 연속 미달성 시 난이도 하향, 7일 연속 달성 시 보너스 챌린지'
        ],
        'reflection' => [
            'name' => '🧠 성찰피드백 중심 학습 도우미',
            'worldview' => '이해 없는 정답은 무의미하다',
            'personality' => '깊이 있는 이해와 메타인지 발달을 돕는 소크라테스식 멘토입니다. 왜?를 통한 근본 이해, 학습 과정 성찰, 사고력 확장을 지원합니다.',
            'approach' => '개념맵 작성, 백지복습법, 설명하기 연습, 오답 원인분석, 학습일지 작성',
            'execution' => '매 세션 후 5분 성찰, 주간 학습로그 분석, 월간 성장리포트',
            'switching_triggers' => '암기 위주 학습 감지 시 이해도 점검, 반복 오답 시 개념 재정립'
        ],
        'selfled' => [
            'name' => '🚀 자율학습형 도우미',
            'worldview' => '스스로 설계한 길이 가장 빠른 길',
            'personality' => '학생의 자기주도성과 독립성을 최대한 존중하는 코치입니다. 최소한의 가이드로 최대한의 자율성을 보장하며, 자기 설계 능력을 키웁니다.',
            'approach' => '주간 목표 자율설정, 학습방법 자유선택, 진도 자율조절, 피드백 요청시에만 개입',
            'execution' => '월요일 계획수립, 금요일 자가평가, 필요시 도움요청, 포트폴리오 관리',
            'switching_triggers' => '목표 미달 2주 연속 시 가이드 제공, 과부하 신호 시 페이스 조언'
        ],
        'cognitive' => [
            'name' => '🔍 인지적 도제형 학습 도우미',
            'worldview' => '마스터의 사고를 모방하며 성장한다',
            'personality' => '전문가의 사고 과정을 단계별로 시연하고 모델링하는 장인정신 멘토입니다. 관찰 → 모방 → 연습 → 독립의 과정을 안내합니다.',
            'approach' => '사고과정 시연, 단계별 스캐폴딩, 점진적 난이도 상승, 독립적 문제해결 유도',
            'execution' => 'Think-aloud 시연, 가이드 연습, 독립 수행, 피드백 루프',
            'switching_triggers' => '이해도 정체 시 더 세밀한 시연, 숙련도 상승 시 독립과제 증가'
        ],
        'timecentered' => [
            'name' => '🕒 시간 피드백 중심형 학습 도우미',
            'worldview' => '시간은 학습의 생명선이자 성과의 가속기',
            'personality' => '시간 사용 효율과 학습 밀도를 극대화하는 타임 매니저입니다. 집중블록 설계, 반복주기 최적화, 골든타임 활용을 통해 학습효율을 높입니다.',
            'approach' => '25분 집중/5분 휴식, 1-3-7-14일 반복주기, 시간당 18문항 목표, 시간밀도지수 ≥0.8',
            'execution' => '타이머 세팅, 시간/문항/정답률 기록, 주간 시간 리포트, 효율 개선',
            'switching_triggers' => '집중시간 급감 시 블록 축소, 효율저하 2주 지속 시 구조 개편'
        ],
        'curiositycentered' => [
            'name' => '💡 호기심 중심형 학습 도우미',
            'worldview' => '궁금증이 최고의 선생님',
            'personality' => '학생의 자연스러운 호기심과 탐구욕을 연료로 삼는 탐험 가이드입니다. 질문 생성, 가설 검증, 발견의 즐거움을 통해 깊은 학습을 유도합니다.',
            'approach' => '왜? 어떻게? 만약? 질문법, 실험적 학습, 프로젝트 기반, 창의적 문제해결',
            'execution' => '일일 궁금증 노트, 주간 탐구 프로젝트, 발견 공유, 질문 토론',
            'switching_triggers' => '수동적 학습 감지 시 질문 유도, 흥미 포인트 발견 시 깊이 탐구'
        ]
    ];
    
    $mode_info = isset($mode_personalities[$learning_mode]) ? 
                 $mode_personalities[$learning_mode] : 
                 $mode_personalities['curriculum'];
    
    // Build conversation history for context with comprehensive worldview
    $messages = [
        [
            'role' => 'system',
            'content' => "당신은 {$mode_info['name']}입니다.

【세계관】 {$mode_info['worldview']}

【정체성】 {$mode_info['personality']}

【학습 접근법】 {$mode_info['approach']}

【실행 전략】 {$mode_info['execution']}

【전환 트리거】 {$mode_info['switching_triggers']}

【학생 정보】
- 이름: {$student_name}
- 현재 모드: {$learning_mode}

【대화 원칙】
1. 위의 세계관과 정체성을 일관되게 유지하며 대화하세요.
2. 해당 모드의 핵심 가치관과 방법론을 자연스럽게 녹여 조언하세요.
3. KPI와 실행 전략을 염두에 두고 구체적인 학습 가이드를 제공하세요.
4. 전환 트리거 상황을 감지하면 적절한 모드 전환을 제안하세요.
5. 친근하고 격려하는 톤을 유지하되, 각 모드의 특성에 맞는 어조를 사용하세요.

답변시 해당 모드의 세계관에 충실하게 답변하고, 필요시 구체적인 실행 방법을 제시하세요."
        ]
    ];
    
    // Add recent conversation history
    if (!empty($conversation)) {
        foreach ($conversation as $msg) {
            if ($msg->message_type === 'user') {
                $messages[] = ['role' => 'user', 'content' => $msg->message];
            } else {
                $messages[] = ['role' => 'assistant', 'content' => $msg->message];
            }
        }
    }
    
    // Add current message
    $messages[] = ['role' => 'user', 'content' => $message];
    
    // Call OpenAI API
    try {
        $response = callOpenAI($messages);
        return $response;
    } catch (Exception $e) {
        error_log("OpenAI API error: " . $e->getMessage());
        
        // Fallback response based on mode's worldview
        $fallback_responses = [
            'curriculum' => "📚 진도는 전략입니다! 오늘의 학습 목표를 확인하고, 주간 진도 체크를 해봐요. 단원 마스터리 80% 달성을 향해 7:3 비율로 복습하며 나아가요!",
            'exam' => "✏️ 시험은 전투! 오늘 50문항 목표 중 몇 문제나 풀었나요? 기출 3회독과 오답노트 2회독, 시간압박 훈련을 잊지 마세요. D-day까지 전략적으로!",
            'custom' => "🎯 당신만의 학습 DNA를 찾아가요! MBTI 학습법과 개인 속도에 맞춰 진행하고, 강점은 극대화하고 약점은 보완해나가요.",
            'mission' => "⚡ 작은 승리가 큰 성공을 만듭니다! 오늘의 5개 미션 중 몇 개를 완료했나요? 연속 달성 기록을 이어가며 레벨업해요!",
            'reflection' => "🧠 이해 없는 정답은 무의미! '왜?'라고 질문하며 근본을 파악해봐요. 백지복습법으로 진짜 이해를 확인하고, 학습일지에 오늘의 깨달음을 기록해요.",
            'selfled' => "🚀 스스로 설계한 길이 가장 빠른 길! 이번 주 목표는 무엇인가요? 자율적으로 진도를 조절하고, 필요할 때만 도움을 요청하세요.",
            'cognitive' => "🔍 마스터의 사고를 모방하며 성장! Think-aloud로 문제 해결 과정을 말로 표현해보고, 단계별로 연습한 후 독립적으로 도전해봐요.",
            'timecentered' => "🕒 시간은 학습의 생명선! 25분 집중/5분 휴식 리듬을 지키고, 시간당 18문항 처리를 목표로! 1-3-7-14일 반복 주기도 체크하세요.",
            'curiositycentered' => "💡 궁금증이 최고의 선생님! '왜? 어떻게? 만약?'으로 질문을 만들고, 오늘의 호기심을 탐구 프로젝트로 발전시켜봐요!"
        ];
        
        return isset($fallback_responses[$learning_mode]) ? 
               $fallback_responses[$learning_mode] : 
               "학습을 도와드릴게요! 무엇이든 물어보세요. 😊";
    }
}

/**
 * Call OpenAI API
 */
function callOpenAI($messages) {
    $api_key = OPENAI_API_KEY;
    $model = OPENAI_MODEL;
    
    // Check if API key is set
    if (empty($api_key) || $api_key === 'your-api-key-here') {
        error_log("OpenAI API key not configured");
        throw new Exception("API key not configured");
    }
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 500,
        'presence_penalty' => 0.3,
        'frequency_penalty' => 0.3
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error) {
        error_log("CURL error: " . $curl_error);
        throw new Exception("Network error: " . $curl_error);
    }
    
    if ($http_code !== 200) {
        error_log("OpenAI API HTTP error: " . $http_code . " Response: " . $response);
        
        // Parse error message if available
        $error_data = json_decode($response, true);
        if (isset($error_data['error']['message'])) {
            throw new Exception("OpenAI API error: " . $error_data['error']['message']);
        }
        
        throw new Exception("OpenAI API returned status code: " . $http_code);
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        error_log("Invalid OpenAI response structure: " . json_encode($result));
        throw new Exception("Invalid OpenAI API response");
    }
    
    return $result['choices'][0]['message']['content'];
}

/**
 * Get chat history
 */
function handleGetHistory($student_id) {
    global $DB;
    
    $messages = $DB->get_records_sql(
        "SELECT * FROM {chatbot_messages} 
         WHERE student_id = :studentid 
         ORDER BY timestamp DESC 
         LIMIT 50",
        array('studentid' => $student_id)
    );
    
    $formatted_messages = [];
    foreach (array_reverse($messages) as $msg) {
        $formatted_messages[] = [
            'type' => $msg->message_type,
            'message' => $msg->message,
            'timestamp' => date('Y-m-d H:i:s', $msg->timestamp)
        ];
    }
    
    echo json_encode(['success' => true, 'messages' => $formatted_messages]);
}

/**
 * Clear chat history
 */
function handleClearHistory($student_id) {
    global $DB;
    
    try {
        $DB->delete_records('chatbot_messages', array('student_id' => $student_id));
        echo json_encode(['success' => true, 'message' => 'History cleared']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to clear history']);
    }
}
?>