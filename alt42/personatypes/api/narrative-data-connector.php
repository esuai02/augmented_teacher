<?php
/**
 * 🌌 개인화 내러티브 데이터 연결 API
 * 채팅 분석과 시스템 데이터를 연결하여 맞춤형 우주 서사 생성
 */

require_once('../config.php');
require_once('../classes/Agent.php');
require_once('../includes/functions.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

global $DB, $USER;

// 현재 사용자 확인
if (!$USER || !$USER->id) {
    ss_send_error('인증이 필요합니다', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim($_SERVER['PATH_INFO'] ?? '', '/');
$segments = array_filter(explode('/', $path));

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($segments);
            break;
        case 'POST':
            handlePostRequest($segments);
            break;
        case 'PUT':
            handlePutRequest($segments);
            break;
        default:
            ss_send_error('지원하지 않는 HTTP 메소드', 405);
    }
} catch (Exception $e) {
    ss_log_error('API 오류', [
        'method' => $method,
        'path' => $path,
        'error' => $e->getMessage(),
        'user_id' => $USER->id
    ]);
    ss_send_error('서버 오류가 발생했습니다', 500);
}

/**
 * 📊 GET 요청 처리
 */
function handleGetRequest($segments) {
    global $DB, $USER;
    
    if (empty($segments)) {
        ss_send_error('엔드포인트를 지정해주세요', 400);
    }
    
    $endpoint = $segments[0];
    $userId = $segments[1] ?? $USER->id;
    
    switch ($endpoint) {
        case 'user-profile':
            getUserProfile($userId);
            break;
            
        case 'chat-history':
            getChatHistory($userId, $_GET);
            break;
            
        case 'system-data':
            getSystemData($userId, $_GET);
            break;
            
        case 'bias-profile':
            getBiasProfile($userId);
            break;
            
        case 'narrative-history':
            getNarrativeHistory($userId, $_GET);
            break;
            
        case 'cosmic-archetype':
            getCosmicArchetype($userId);
            break;
            
        default:
            ss_send_error('알 수 없는 엔드포인트', 404);
    }
}

/**
 * 📝 POST 요청 처리
 */
function handlePostRequest($segments) {
    global $DB, $USER;
    
    if (empty($segments)) {
        ss_send_error('엔드포인트를 지정해주세요', 400);
    }
    
    $endpoint = $segments[0];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ss_send_error('유효한 JSON 데이터를 보내주세요', 400);
    }
    
    switch ($endpoint) {
        case 'analyze-user':
            analyzeUserProfile($input);
            break;
            
        case 'save-chat-message':
            saveChatMessage($input);
            break;
            
        case 'record-interaction':
            recordUserInteraction($input);
            break;
            
        case 'save-narrative':
            saveNarrativeGeneration($input);
            break;
            
        case 'update-bias-profile':
            updateBiasProfile($input);
            break;
            
        case 'generate-personalized-story':
            generatePersonalizedStory($input);
            break;
            
        default:
            ss_send_error('알 수 없는 엔드포인트', 404);
    }
}

/**
 * 👤 사용자 프로필 조회
 */
function getUserProfile($userId) {
    global $DB;
    
    // 기본 사용자 정보
    $user = $DB->get_record('user', ['id' => $userId]);
    if (!$user) {
        ss_send_error('사용자를 찾을 수 없습니다', 404);
    }
    
    // 학습 통계
    $learningStats = $DB->get_record_sql("
        SELECT 
            COUNT(*) as total_sessions,
            AVG(time_spent) as avg_time_spent,
            MAX(created_at) as last_activity,
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_sessions
        FROM {ss_user_sessions} 
        WHERE user_id = ?
    ", [$userId]);
    
    // 최근 감정 상태
    $recentEmotions = $DB->get_records_sql("
        SELECT emotion_state, confidence_level, created_at
        FROM {ss_emotional_tracking}
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ", [$userId]);
    
    // 편향 프로필
    $biasProfile = $DB->get_record('ss_user_bias_profiles', ['user_id' => $userId]);
    
    ss_send_success([
        'user_info' => [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email
        ],
        'learning_stats' => $learningStats,
        'recent_emotions' => array_values($recentEmotions),
        'bias_profile' => $biasProfile ? json_decode($biasProfile->profile_data, true) : null
    ]);
}

/**
 * 💬 채팅 히스토리 조회
 */
function getChatHistory($userId, $params) {
    global $DB;
    
    $limit = min(intval($params['limit'] ?? 50), 200);
    $offset = intval($params['offset'] ?? 0);
    $from_date = $params['from_date'] ?? null;
    
    $sql = "
        SELECT 
            id,
            message_text,
            message_type,
            emotional_tone,
            bias_indicators,
            created_at
        FROM {ss_chat_messages}
        WHERE user_id = ?
    ";
    $params_array = [$userId];
    
    if ($from_date) {
        $sql .= " AND created_at >= ?";
        $params_array[] = $from_date;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params_array[] = $limit;
    $params_array[] = $offset;
    
    $messages = $DB->get_records_sql($sql, $params_array);
    
    // 메시지 데이터 가공
    $formatted_messages = array_map(function($msg) {
        return [
            'id' => $msg->id,
            'text' => $msg->message_text,
            'type' => $msg->message_type,
            'timestamp' => strtotime($msg->created_at) * 1000,
            'emotional_tone' => $msg->emotional_tone,
            'bias_indicators' => json_decode($msg->bias_indicators ?? '[]', true)
        ];
    }, array_values($messages));
    
    // 전체 메시지 수
    $total = $DB->count_records('ss_chat_messages', ['user_id' => $userId]);
    
    ss_send_success([
        'messages' => $formatted_messages,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * 🖥️ 시스템 데이터 조회
 */
function getSystemData($userId, $params) {
    global $DB;
    
    $data_type = $params['type'] ?? 'all';
    $days = intval($params['days'] ?? 30);
    
    $result = [];
    
    if ($data_type === 'all' || $data_type === 'sessions') {
        // 세션 데이터
        $sessions = $DB->get_records_sql("
            SELECT 
                session_id,
                node_id,
                time_spent,
                completed,
                answer_quality,
                retry_count,
                created_at
            FROM {ss_user_sessions}
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY created_at DESC
        ", [$userId, $days]);
        
        $result['sessions'] = array_values($sessions);
    }
    
    if ($data_type === 'all' || $data_type === 'interactions') {
        // 상호작용 데이터
        $interactions = $DB->get_records_sql("
            SELECT 
                event_type,
                event_data,
                timestamp,
                bias_detected
            FROM {ss_user_interactions}
            WHERE user_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY timestamp DESC
        ", [$userId, $days]);
        
        $result['interactions'] = array_values($interactions);
    }
    
    if ($data_type === 'all' || $data_type === 'progress') {
        // 진행 상황 데이터
        $progress = $DB->get_record_sql("
            SELECT 
                completed_nodes,
                unlocked_nodes,
                total_time_spent,
                achievement_count,
                last_updated
            FROM {ss_user_progress}
            WHERE user_id = ?
        ", [$userId]);
        
        $result['progress'] = $progress;
    }
    
    ss_send_success($result);
}

/**
 * 🧠 편향 프로필 조회
 */
function getBiasProfile($userId) {
    global $DB;
    
    $profile = $DB->get_record('ss_user_bias_profiles', ['user_id' => $userId]);
    
    if (!$profile) {
        ss_send_success([
            'exists' => false,
            'message' => '편향 프로필이 아직 생성되지 않았습니다'
        ]);
        return;
    }
    
    $profileData = json_decode($profile->profile_data, true);
    
    // 최근 편향 감지 이력
    $recentDetections = $DB->get_records_sql("
        SELECT 
            bias_type,
            confidence_score,
            evidence,
            created_at
        FROM {ss_bias_detections}
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ", [$userId]);
    
    ss_send_success([
        'exists' => true,
        'profile' => $profileData,
        'last_updated' => $profile->updated_at,
        'recent_detections' => array_values($recentDetections)
    ]);
}

/**
 * 📚 내러티브 히스토리 조회
 */
function getNarrativeHistory($userId, $params) {
    global $DB;
    
    $limit = min(intval($params['limit'] ?? 20), 100);
    $phase = $params['phase'] ?? null;
    
    $sql = "
        SELECT 
            id,
            narrative_phase,
            narrative_content,
            context_data,
            user_response,
            effectiveness_score,
            created_at
        FROM {ss_narrative_history}
        WHERE user_id = ?
    ";
    $params_array = [$userId];
    
    if ($phase) {
        $sql .= " AND narrative_phase = ?";
        $params_array[] = $phase;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params_array[] = $limit;
    
    $narratives = $DB->get_records_sql($sql, $params_array);
    
    $formatted_narratives = array_map(function($narrative) {
        return [
            'id' => $narrative->id,
            'phase' => $narrative->narrative_phase,
            'content' => $narrative->narrative_content,
            'context' => json_decode($narrative->context_data ?? '{}', true),
            'user_response' => $narrative->user_response,
            'effectiveness' => $narrative->effectiveness_score,
            'timestamp' => strtotime($narrative->created_at) * 1000
        ];
    }, array_values($narratives));
    
    ss_send_success([
        'narratives' => $formatted_narratives,
        'total_count' => count($formatted_narratives)
    ]);
}

/**
 * 🌟 우주적 원형 조회
 */
function getCosmicArchetype($userId) {
    global $DB;
    
    $archetype = $DB->get_record('ss_cosmic_archetypes', ['user_id' => $userId]);
    
    if (!$archetype) {
        ss_send_success([
            'assigned' => false,
            'message' => '우주적 원형이 아직 할당되지 않았습니다'
        ]);
        return;
    }
    
    $archetypeData = json_decode($archetype->archetype_data, true);
    
    ss_send_success([
        'assigned' => true,
        'archetype' => $archetypeData,
        'assigned_at' => $archetype->created_at,
        'last_updated' => $archetype->updated_at
    ]);
}

/**
 * 🔍 사용자 분석 수행
 */
function analyzeUserProfile($input) {
    global $DB, $USER;
    
    $userId = $input['user_id'] ?? $USER->id;
    $forceReanalysis = $input['force_reanalysis'] ?? false;
    
    // 기존 분석 결과 확인
    if (!$forceReanalysis) {
        $existing = $DB->get_record('ss_user_analysis_results', ['user_id' => $userId]);
        if ($existing && (time() - strtotime($existing->updated_at)) < 3600) { // 1시간 이내
            ss_send_success([
                'analysis_id' => $existing->id,
                'cached' => true,
                'results' => json_decode($existing->analysis_data, true)
            ]);
            return;
        }
    }
    
    try {
        // 채팅 히스토리 가져오기
        $chatHistory = $DB->get_records('ss_chat_messages', ['user_id' => $userId]);
        
        // 시스템 데이터 가져오기
        $systemData = [
            'sessions' => $DB->get_records('ss_user_sessions', ['user_id' => $userId]),
            'interactions' => $DB->get_records('ss_user_interactions', ['user_id' => $userId]),
            'progress' => $DB->get_record('ss_user_progress', ['user_id' => $userId])
        ];
        
        // 분석 수행
        $analysisResult = performUserAnalysis($chatHistory, $systemData);
        
        // 결과 저장
        $analysisRecord = [
            'user_id' => $userId,
            'analysis_data' => json_encode($analysisResult),
            'analysis_version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($existing) {
            $analysisRecord['id'] = $existing->id;
            $DB->update_record('ss_user_analysis_results', (object)$analysisRecord);
        } else {
            $analysisId = $DB->insert_record('ss_user_analysis_results', (object)$analysisRecord);
            $analysisRecord['id'] = $analysisId;
        }
        
        ss_send_success([
            'analysis_id' => $analysisRecord['id'],
            'cached' => false,
            'results' => $analysisResult
        ]);
        
    } catch (Exception $e) {
        ss_log_error('사용자 분석 실패', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        ss_send_error('분석 중 오류가 발생했습니다', 500);
    }
}

/**
 * 💬 채팅 메시지 저장
 */
function saveChatMessage($input) {
    global $DB, $USER;
    
    $required = ['message_text', 'message_type'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            ss_send_error("필수 필드가 누락되었습니다: {$field}", 400);
        }
    }
    
    // 감정 톤 분석
    $emotionalTone = analyzeEmotionalTone($input['message_text']);
    
    // 편향 지표 감지
    $biasIndicators = detectBiasIndicators($input['message_text']);
    
    $messageRecord = [
        'user_id' => $input['user_id'] ?? $USER->id,
        'message_text' => $input['message_text'],
        'message_type' => $input['message_type'],
        'emotional_tone' => $emotionalTone,
        'bias_indicators' => json_encode($biasIndicators),
        'context_data' => json_encode($input['context'] ?? []),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $messageId = $DB->insert_record('ss_chat_messages', (object)$messageRecord);
    
    ss_send_success([
        'message_id' => $messageId,
        'emotional_tone' => $emotionalTone,
        'bias_indicators' => $biasIndicators
    ]);
}

/**
 * 🖱️ 사용자 상호작용 기록
 */
function recordUserInteraction($input) {
    global $DB, $USER;
    
    $required = ['event_type', 'event_data'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            ss_send_error("필수 필드가 누락되었습니다: {$field}", 400);
        }
    }
    
    $interactionRecord = [
        'user_id' => $input['user_id'] ?? $USER->id,
        'session_id' => $input['session_id'] ?? session_id(),
        'event_type' => $input['event_type'],
        'event_data' => json_encode($input['event_data']),
        'bias_detected' => json_encode($input['bias_detected'] ?? []),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $interactionId = $DB->insert_record('ss_user_interactions', (object)$interactionRecord);
    
    ss_send_success([
        'interaction_id' => $interactionId,
        'recorded_at' => $interactionRecord['timestamp']
    ]);
}

/**
 * 📖 개인화된 스토리 생성
 */
function generatePersonalizedStory($input) {
    global $DB, $USER;
    
    $userId = $input['user_id'] ?? $USER->id;
    $phase = $input['phase'] ?? 'general';
    $context = $input['context'] ?? [];
    
    try {
        // 사용자 프로필 가져오기
        $userProfile = getUserProfileForStory($userId);
        
        // 내러티브 생성
        $narrative = generateNarrativeContent($userProfile, $phase, $context);
        
        // 생성된 내러티브 저장
        $narrativeRecord = [
            'user_id' => $userId,
            'narrative_phase' => $phase,
            'narrative_content' => $narrative['content'],
            'context_data' => json_encode($context),
            'generation_metadata' => json_encode($narrative['metadata']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $narrativeId = $DB->insert_record('ss_narrative_history', (object)$narrativeRecord);
        
        ss_send_success([
            'narrative_id' => $narrativeId,
            'narrative' => $narrative,
            'generated_at' => $narrativeRecord['created_at']
        ]);
        
    } catch (Exception $e) {
        ss_log_error('스토리 생성 실패', [
            'user_id' => $userId,
            'phase' => $phase,
            'error' => $e->getMessage()
        ]);
        ss_send_error('스토리 생성 중 오류가 발생했습니다', 500);
    }
}

/**
 * 🔧 헬퍼 함수들
 */

function performUserAnalysis($chatHistory, $systemData) {
    // TODO: 실제 분석 로직 구현
    return [
        'personality_type' => 'curious_wanderer',
        'dominant_biases' => ['확증편향', '자기과소평가'],
        'emotional_patterns' => ['curious' => 0.4, 'anxious' => 0.3, 'confident' => 0.3],
        'learning_preferences' => ['visual', 'step_by_step'],
        'cosmic_archetype' => 'reluctant_explorer',
        'analysis_timestamp' => time()
    ];
}

function analyzeEmotionalTone($text) {
    // 간단한 감정 분석
    if (preg_match('/좋|재미|기쁘|즐거/u', $text)) return 'positive';
    if (preg_match('/어려|힘들|불안|걱정/u', $text)) return 'negative';
    return 'neutral';
}

function detectBiasIndicators($text) {
    $indicators = [];
    
    if (preg_match('/역시|당연히|또|늘/u', $text)) {
        $indicators[] = ['type' => '확증편향', 'confidence' => 0.7];
    }
    
    if (preg_match('/끝났|망했|최악|절대/u', $text)) {
        $indicators[] = ['type' => '재앙화사고', 'confidence' => 0.8];
    }
    
    return $indicators;
}

function getUserProfileForStory($userId) {
    global $DB;
    
    // 사용자 분석 결과 가져오기
    $analysis = $DB->get_record('ss_user_analysis_results', ['user_id' => $userId]);
    if ($analysis) {
        return json_decode($analysis->analysis_data, true);
    }
    
    // 기본 프로필 반환
    return [
        'personality_type' => 'reluctant_explorer',
        'dominant_biases' => [],
        'cosmic_archetype' => 'curious_wanderer'
    ];
}

function generateNarrativeContent($userProfile, $phase, $context) {
    // TODO: 실제 내러티브 생성 로직
    return [
        'content' => "당신만의 우주적 여정이 시작됩니다! ✨",
        'metadata' => [
            'archetype_used' => $userProfile['cosmic_archetype'],
            'personalization_level' => 0.8
        ]
    ];
}

/**
 * API 응답 헬퍼 함수들
 */
function ss_send_success($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

function ss_send_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => $message,
            'code' => $code
        ],
        'timestamp' => time()
    ]);
    exit;
}

/**
 * 로깅 함수
 */
function ss_log_error($message, $data = []) {
    error_log('[SHINING_STARS] ' . $message . ' - ' . json_encode($data));
}
?>