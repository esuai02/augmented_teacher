<?php
/**
 * 필기 분석 API
 * 화이트보드 캡처 이미지를 OpenAI Vision으로 분석하여 피드백 생성
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }
    
    $whiteboardImage = $input['whiteboard_image'] ?? null; // base64 이미지
    $questionImage = $input['question_image'] ?? null;     // 문제 이미지 URL
    $solutionImage = $input['solution_image'] ?? null;     // 해설 이미지 URL
    $studentId = $input['student_id'] ?? $USER->id;
    $contentId = $input['content_id'] ?? null;
    $currentStep = $input['current_step'] ?? 1;
    $currentEmotion = $input['current_emotion'] ?? 'neutral';
    $personaType = $input['persona_type'] ?? null;
    $pauseDuration = $input['pause_duration'] ?? 5;        // 멈춘 시간(초)
    
    if (empty($whiteboardImage)) {
        throw new Exception('화이트보드 이미지가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }
    
    // OpenAI API 키 로드
    $apiKey = null;
    $configPath = __DIR__ . '/../../config.php';
    if (file_exists($configPath)) {
        require_once($configPath);
        if (defined('OPENAI_API_KEY')) {
            $apiKey = OPENAI_API_KEY;
        }
    }
    
    if (!$apiKey) {
        // 대안: Moodle 설정에서 로드
        $apiKey = get_config('local_augmented_teacher', 'openai_api_key');
    }
    
    if (!$apiKey) {
        throw new Exception('OpenAI API 키가 설정되지 않았습니다 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    // 풀이 단계 매핑
    $stepLabels = [
        1 => '문제해석',
        2 => '식세우기',
        3 => '풀이과정',
        4 => '점검',
        5 => '장기기억화'
    ];
    $currentStepLabel = $stepLabels[$currentStep] ?? '풀이과정';
    
    // 감정 상태 매핑
    $emotionLabels = [
        'confident' => '자신있음',
        'neutral' => '보통',
        'confused' => '헷갈림',
        'stuck' => '막힘',
        'anxious' => '불안함'
    ];
    $emotionLabel = $emotionLabels[$currentEmotion] ?? '보통';
    
    // 시스템 프롬프트 구성
    $systemPrompt = <<<PROMPT
당신은 수학 AI 튜터입니다. 학생의 화이트보드 필기 상태를 분석하여 즉각적인 피드백을 제공합니다.

## 분석 컨텍스트
- 현재 풀이 단계: {$currentStepLabel}
- 학생 감정 상태: {$emotionLabel}
- 필기 멈춤 시간: {$pauseDuration}초

## 분석 지침
1. 화이트보드 이미지에서 학생의 현재 필기 상태를 파악하세요
2. 문제 이미지(제공된 경우)와 비교하여 풀이 진행 상황을 판단하세요
3. 해설 이미지(제공된 경우)를 참고하여 올바른 방향으로 유도하세요
4. 필기가 멈춘 이유를 추론하세요 (막힘, 생각 중, 검토 중 등)

## 출력 형식 (JSON)
{
    "writing_analysis": {
        "current_progress": "현재까지 작성된 내용 요약",
        "progress_percent": 0-100,
        "pause_reason": "stuck | thinking | reviewing | completed | unknown",
        "identified_errors": ["발견된 오류들"],
        "next_step_needed": "다음에 해야 할 것"
    },
    "feedback": {
        "type": "encouragement | hint | correction | guidance | praise",
        "message": "학생에게 보여줄 피드백 메시지 (한국어, 친근한 말투, 50자 이내)",
        "detailed_hint": "필요시 상세 힌트 (100자 이내)",
        "intervention_id": "INT_X_Y 형식의 개입 활동 ID (선택)"
    },
    "next_action": {
        "type": "wait | proceed | review | explain | encourage",
        "target_step": 1-5,
        "confidence": 0.0-1.0
    }
}

## 개입 활동 ID 참조
- INT_1_1: 인지 부하 대기 (3-5초 침묵)
- INT_1_3: 사고 여백 제공 ("한번 생각해봐")
- INT_5_1: 확인 질문 ("여기까지 이해됐어?")
- INT_5_2: 예측 질문 ("다음엔 뭘 해야 할 것 같아?")
- INT_5_5: 힌트 질문 ("만약 여기가 0이면?")
- INT_6_2: 부분 인정 확장 ("거기까진 맞아, 근데...")
- INT_7_1: 노력 인정 ("열심히 생각했네")
- INT_7_4: 작은 성공 만들기

## 중요
- 감정 상태에 맞는 피드백 톤 조절
- 막힘(stuck) 상태면 힌트 제공
- 자신있음(confident) 상태면 검증 유도
- JSON만 출력, 추가 설명 없음
PROMPT;

    // 메시지 구성
    $userContent = [
        [
            'type' => 'text',
            'text' => '학생의 현재 화이트보드 필기 상태를 분석하고 피드백을 생성해주세요.'
        ],
        [
            'type' => 'image_url',
            'image_url' => [
                'url' => $whiteboardImage,
                'detail' => 'high'
            ]
        ]
    ];
    
    // 문제 이미지 추가
    if (!empty($questionImage)) {
        $userContent[] = [
            'type' => 'text',
            'text' => '문제 이미지:'
        ];
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $questionImage,
                'detail' => 'low'
            ]
        ];
    }
    
    // 해설 이미지 추가
    if (!empty($solutionImage)) {
        $userContent[] = [
            'type' => 'text',
            'text' => '해설 이미지 (참고용, 학생에게 직접 보여주지 않음):'
        ];
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $solutionImage,
                'detail' => 'low'
            ]
        ];
    }
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userContent]
    ];
    
    // OpenAI API 호출
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $postData = [
        'model' => 'gpt-4o',
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'response_format' => ['type' => 'json_object']
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || !empty($curlError)) {
        error_log("OpenAI API cURL Error in " . __FILE__ . ":" . __LINE__ . " - " . $curlError);
        throw new Exception('OpenAI API 호출 실패: ' . $curlError . ' - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
        error_log("OpenAI API Error in " . __FILE__ . ":" . __LINE__ . " - " . $errorMessage);
        throw new Exception('OpenAI API 오류: ' . $errorMessage . ' - ' . basename(__FILE__) . ':' . __LINE__, $httpCode);
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('OpenAI 응답 형식 오류 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    $analysisResult = json_decode($data['choices'][0]['message']['content'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON 파싱 오류 in " . __FILE__ . ":" . __LINE__ . " - " . json_last_error_msg());
        // 기본 응답 생성
        $analysisResult = [
            'writing_analysis' => [
                'current_progress' => '분석 중',
                'progress_percent' => 50,
                'pause_reason' => 'thinking',
                'identified_errors' => [],
                'next_step_needed' => '계속 진행'
            ],
            'feedback' => [
                'type' => 'encouragement',
                'message' => '잘하고 있어! 천천히 생각해봐 🤔',
                'detailed_hint' => null,
                'intervention_id' => 'INT_1_3'
            ],
            'next_action' => [
                'type' => 'wait',
                'target_step' => $currentStep,
                'confidence' => 0.5
            ]
        ];
    }
    
    // 분석 로그 저장
    require_once(__DIR__ . '/../includes/db_manager.php');
    $dbManager = new DBManager();
    
    $logData = [
        'student_id' => $studentId,
        'content_id' => $contentId,
        'current_step' => $currentStep,
        'emotion' => $currentEmotion,
        'pause_duration' => $pauseDuration,
        'analysis_result' => $analysisResult,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // 상호작용 로그에 저장
    try {
        $dbManager->saveInteraction([
            'interaction_id' => 'WRITING_' . time() . '_' . uniqid(),
            'student_id' => $studentId,
            'user_input' => 'writing_pause_' . $pauseDuration . 's',
            'response' => $analysisResult['feedback']['message'] ?? '',
            'matched_rules' => [$analysisResult['feedback']['intervention_id'] ?? 'INT_1_3'],
            'context' => $logData,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("로그 저장 실패 in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'data' => $analysisResult,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    error_log("Writing Analysis Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();

