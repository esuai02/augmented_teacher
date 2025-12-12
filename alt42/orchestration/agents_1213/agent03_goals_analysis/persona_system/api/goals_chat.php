<?php
/**
 * Agent03 목표분석 페르소나 채팅 API 엔드포인트
 *
 * POST /api/goals_chat.php
 * - message: 사용자 메시지 (필수)
 * - user_id: 사용자 ID (선택, 미입력시 현재 로그인 사용자)
 * - context: 목표 컨텍스트 (선택, G0/G1/G2/G3/CRISIS)
 * - goal_id: 특정 목표 ID (선택)
 *
 * @package AugmentedTeacher\Agent03\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// CORS 헤더
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청 처리 (API 정보 또는 테스트)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['message'])) {
        echo json_encode([
            'success' => true,
            'api' => 'Agent03 Goals Analysis Persona Chat API',
            'version' => '1.0',
            'agent' => 'agent03_goals_analysis',
            'description' => '목표 설정, 진행 상황, 조정에 관한 대화 처리',
            'contexts' => [
                'G0' => '목표 설정 단계',
                'G1' => '목표 진행 단계',
                'G2' => '정체/위기 단계',
                'G3' => '목표 재설정 단계',
                'CRISIS' => '위기 개입 필요'
            ],
            'usage' => [
                'POST' => '/api/goals_chat.php with JSON body {"message": "텍스트", "context": "G1"}',
                'GET' => '/api/goals_chat.php?message=텍스트&context=G1 (테스트용)'
            ],
            'test_page' => str_replace('/api/goals_chat.php', '/test.php', $_SERVER['REQUEST_URI'])
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    // GET 파라미터를 input으로 변환
    $input = $_GET;
}

// 엔진 로드
require_once(__DIR__ . '/../engine/Agent03PersonaEngine.php');

try {
    // 입력 파싱 (GET이 아닌 경우만)
    if (!isset($input)) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // form-data 폴백
            $input = $_POST;
        }
    }

    // 필수 파라미터 검증
    $message = trim($input['message'] ?? '');
    if (empty($message)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'message 파라미터가 필요합니다',
            'error_code' => 'MISSING_MESSAGE',
            'file' => __FILE__,
            'line' => __LINE__
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 사용자 ID 결정
    $userId = (int)($input['user_id'] ?? 0);
    if ($userId <= 0 && isset($USER->id)) {
        $userId = (int)$USER->id;
    }
    if ($userId <= 0) {
        $userId = 1; // 게스트 폴백
    }

    // 위기 신호 우선 검사 (Critical Priority)
    $crisisResult = checkCrisisSignals($message);
    if ($crisisResult['detected']) {
        // 위기 상황 즉시 응답
        $crisisResponse = generateCrisisResponse($crisisResult, $userId);
        echo json_encode($crisisResponse, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 세션/컨텍스트 데이터
    $contextCode = strtoupper(trim($input['context'] ?? ''));
    $goalId = (int)($input['goal_id'] ?? 0);

    $sessionData = [
        'current_context' => $contextCode ?: 'auto',
        'current_goal_id' => $goalId,
        'source' => 'api'
    ];

    // 엔진 초기화
    $engine = new Agent03PersonaEngine('agent03');

    // 규칙 로드
    $rulesPath = __DIR__ . '/../rules.yaml';
    if (file_exists($rulesPath)) {
        $engine->loadRules($rulesPath);
    }

    // 프로세스 실행
    $startTime = microtime(true);
    $result = $engine->process($userId, $message, $sessionData);
    $processingTime = (microtime(true) - $startTime) * 1000;

    // 응답 구성
    $response = [
        'success' => $result['success'] ?? false,
        'user_id' => $userId,
        'context' => [
            'detected' => $result['context']['detected_context'] ?? 'G1',
            'sub_context' => $result['context']['sub_context'] ?? null,
            'confidence' => $result['context']['confidence'] ?? 0.5
        ],
        'persona' => [
            'persona_id' => $result['persona']['id'] ?? null,
            'persona_name' => $result['persona']['name'] ?? null,
            'tone' => $result['persona']['tone'] ?? 'Professional',
            'intervention' => $result['persona']['intervention'] ?? 'InformationProvision'
        ],
        'response' => [
            'text' => $result['response']['text'] ?? '',
            'source' => $result['response']['source'] ?? 'template',
            'follow_up_questions' => $result['response']['follow_up'] ?? []
        ],
        'goal_analysis' => [
            'goal_intent' => $result['analysis']['goal_intent'] ?? 'general',
            'emotional_state' => $result['analysis']['emotional_state'] ?? 'neutral',
            'topics' => $result['analysis']['detected_topics'] ?? []
        ],
        'meta' => [
            'agent' => 'agent03_goals_analysis',
            'processing_time_ms' => round($processingTime, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'INTERNAL_ERROR',
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 위기 신호 검사
 *
 * @param string $message 사용자 메시지
 * @return array 위기 감지 결과
 */
function checkCrisisSignals($message) {
    $crisisKeywords = [
        'level_0' => ['죽고 싶', '자살', '자해', '사라지고 싶', '끝내고 싶', '살기 싫'],
        'level_1' => ['못 견디겠', '미치겠', '무너질 것 같', '너무 힘들', '더 이상 못'],
        'level_2' => ['아무도 없', '혼자야', '외로워', '이해 못 해', '소용없어'],
        'level_3' => ['힘들어', '지쳤어', '스트레스', '우울해', '불안해']
    ];

    foreach ($crisisKeywords as $level => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                return [
                    'detected' => true,
                    'level' => $level,
                    'keyword' => $keyword,
                    'confidence' => $level === 'level_0' ? 0.95 : ($level === 'level_1' ? 0.85 : 0.7),
                    'immediate_action' => in_array($level, ['level_0', 'level_1'])
                ];
            }
        }
    }

    return ['detected' => false];
}

/**
 * 위기 상황 응답 생성
 *
 * @param array $crisisResult 위기 감지 결과
 * @param int $userId 사용자 ID
 * @return array API 응답
 */
function generateCrisisResponse($crisisResult, $userId) {
    global $DB;

    $level = $crisisResult['level'];

    // 위기 레벨별 응답
    $responses = [
        'level_0' => [
            'text' => "지금 많이 힘드시군요. 당신의 안전이 가장 중요해요. 혼자 감당하지 마시고 전문가의 도움을 받으세요.\n\n" .
                     "📞 자살예방상담전화: 1393 (24시간)\n" .
                     "📞 정신건강위기상담전화: 1577-0199\n\n" .
                     "언제든 이야기 나눌 준비가 되어 있어요.",
            'persona' => 'CRISIS_P1',
            'tone' => 'Calm',
            'intervention' => 'CrisisIntervention'
        ],
        'level_1' => [
            'text' => "정말 힘든 시간을 보내고 계시는군요. 그 마음이 충분히 이해됩니다. " .
                     "지금 느끼는 감정은 일시적일 수 있어요. 전문 상담을 받아보시는 건 어떨까요?\n\n" .
                     "📞 정신건강위기상담전화: 1577-0199\n\n" .
                     "제가 여기 있을게요. 천천히 이야기해 주세요.",
            'persona' => 'CRISIS_P1',
            'tone' => 'Empathetic',
            'intervention' => 'EmotionalSupport'
        ],
        'level_2' => [
            'text' => "외롭고 힘든 마음이 느껴져요. 혼자라고 느껴질 때 정말 힘들죠. " .
                     "하지만 당신 곁에는 도움을 줄 수 있는 사람들이 있어요. " .
                     "지금 어떤 것이 가장 힘드신가요?",
            'persona' => 'CRISIS_P2',
            'tone' => 'Warm',
            'intervention' => 'EmotionalSupport'
        ],
        'level_3' => [
            'text' => "힘드시군요. 그런 감정을 느끼는 건 자연스러운 일이에요. " .
                     "목표에 대한 부담이 있으시다면, 잠시 쉬어가도 괜찮아요. " .
                     "무엇이 가장 마음에 걸리시나요?",
            'persona' => 'CRISIS_P2',
            'tone' => 'Warm',
            'intervention' => 'EmotionalSupport'
        ]
    ];

    $responseData = $responses[$level] ?? $responses['level_3'];

    // 위기 알림 로그 기록
    try {
        $DB->insert_record('at_crisis_alerts', [
            'userid' => $userId,
            'agent_id' => 'agent03',
            'crisis_level' => str_replace('level_', '', $level),
            'detected_keyword' => $crisisResult['keyword'] ?? '',
            'confidence' => $crisisResult['confidence'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("[Agent03 CRISIS] " . __FILE__ . ":" . __LINE__ .
            " - 위기 알림 로그 실패: " . $e->getMessage());
    }

    return [
        'success' => true,
        'user_id' => $userId,
        'context' => [
            'detected' => 'CRISIS',
            'sub_context' => $level,
            'confidence' => $crisisResult['confidence']
        ],
        'persona' => [
            'persona_id' => $responseData['persona'],
            'persona_name' => $level === 'level_0' || $level === 'level_1' ?
                '즉시 개입 필요' : '안정화 필요',
            'tone' => $responseData['tone'],
            'intervention' => $responseData['intervention']
        ],
        'response' => [
            'text' => $responseData['text'],
            'source' => 'crisis_protocol',
            'immediate_action' => $crisisResult['immediate_action']
        ],
        'goal_analysis' => [
            'goal_intent' => 'crisis',
            'emotional_state' => 'crisis',
            'topics' => ['emotional_crisis']
        ],
        'meta' => [
            'agent' => 'agent03_goals_analysis',
            'crisis_detected' => true,
            'crisis_level' => $level,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
}

/*
 * 사용 예시:
 *
 * # 일반 목표 관련 대화
 * curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "이번 학기 목표를 세우고 싶어요", "context": "G0"}'
 *
 * # 진행 상황 확인
 * curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "내 목표 달성률이 어떻게 되나요?"}'
 *
 * 응답 예시:
 * {
 *   "success": true,
 *   "user_id": 123,
 *   "context": {
 *     "detected": "G0",
 *     "sub_context": "G0.1",
 *     "confidence": 0.85
 *   },
 *   "persona": {
 *     "persona_id": "G0_P5",
 *     "persona_name": "균형 잡힌 목표 설정자",
 *     "tone": "Professional",
 *     "intervention": "GoalSetting"
 *   },
 *   "response": {
 *     "text": "이번 학기 목표를 세우려고 하시는군요! 먼저 어떤 분야의 목표인지 알려주세요...",
 *     "source": "template",
 *     "follow_up_questions": ["학업 목표인가요, 개인 성장 목표인가요?"]
 *   },
 *   "goal_analysis": {
 *     "goal_intent": "set_goal",
 *     "emotional_state": "motivated",
 *     "topics": ["goal_setting", "academic"]
 *   },
 *   "meta": {
 *     "agent": "agent03_goals_analysis",
 *     "processing_time_ms": 45.32,
 *     "timestamp": "2025-12-02 10:30:00"
 *   }
 * }
 *
 * 관련 DB 테이블:
 * - at_user_goals: 사용자 목표 정보
 * - at_goal_activities: 목표 활동 로그
 * - at_agent_persona_state: 페르소나 상태
 * - at_crisis_alerts: 위기 알림 기록
 *
 * 파일 위치:
 * /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/api/goals_chat.php:280
 */
