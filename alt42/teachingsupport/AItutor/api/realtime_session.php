<?php
/**
 * Realtime API 세션 생성 엔드포인트
 * 문제/해설 이미지 사전 분석 후 Realtime 세션 생성 및 client_secret 발급
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// PHP 부동소수점 JSON 인코딩 정밀도 문제 해결
ini_set('serialize_precision', 14);
ini_set('precision', 14);

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
    
    // 입력 파라미터 검증
    $studentId = $input['student_id'] ?? $USER->id;
    $contentId = $input['content_id'] ?? null;
    $questionImage = $input['question_image'] ?? null;  // 문제 이미지 URL
    $solutionImage = $input['solution_image'] ?? null;  // 해설 이미지 URL
    $currentStep = $input['current_step'] ?? 1;
    $currentEmotion = $input['current_emotion'] ?? 'neutral';
    $unitName = $input['unit_name'] ?? '수학';
    
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
    
    // 학생 정보 조회
    $studentInfo = $DB->get_record('user', ['id' => $studentId]);
    if (!$studentInfo) {
        throw new Exception('학생 정보를 찾을 수 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 404);
    }
    
    // ========== 1단계: 문제/해설 이미지 사전 분석 ==========
    $questionAnalysis = null;
    $solutionAnalysis = null;
    
    if (!empty($questionImage) || !empty($solutionImage)) {
        $analysisContent = [];
        
        if (!empty($questionImage)) {
            $analysisContent[] = [
                'type' => 'text',
                'text' => '다음은 학생이 풀어야 할 수학 문제입니다. 문제의 내용, 난이도, 핵심 개념, 풀이 단계를 상세히 분석해주세요.'
            ];
            $analysisContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $questionImage,
                    'detail' => 'high'  // 수학 문제는 상세 분석 필요
                ]
            ];
        }
        
        if (!empty($solutionImage)) {
            $analysisContent[] = [
                'type' => 'text',
                'text' => '다음은 문제의 정답 해설입니다. 해설을 참고하여 올바른 풀이 과정, 단계별 설명 방법, 학생이 자주 틀리는 부분을 파악해주세요. (이 해설은 학생에게 직접 보여주지 않고, 선생님의 가이드로만 사용합니다)'
            ];
            $analysisContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $solutionImage,
                    'detail' => 'high'
                ]
            ];
        }
        
        $analysisPrompt = <<<PROMPT
당신은 수학 문제 분석 전문가입니다. 제공된 문제와 해설을 분석하여 다음 정보를 JSON 형식으로 제공해주세요:

{
    "question_analysis": {
        "topic": "주제 (예: 이차함수, 미적분)",
        "difficulty": "easy | medium | hard",
        "concepts": ["필요한 개념들"],
        "steps": [
            {
                "step_number": 1,
                "step_name": "문제해석",
                "description": "이 단계에서 해야 할 일"
            },
            {
                "step_number": 2,
                "step_name": "식세우기",
                "description": "이 단계에서 해야 할 일"
            }
        ],
        "common_mistakes": ["학생들이 자주 틀리는 부분"],
        "key_points": ["핵심 포인트들"]
    },
    "solution_analysis": {
        "solution_steps": ["해설의 단계별 설명"],
        "teaching_tips": ["가르칠 때 강조할 점"],
        "hint_strategy": ["힌트 제공 전략"]
    }
}
PROMPT;
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o',  // Vision API 지원 모델
                'messages' => [
                    ['role' => 'system', 'content' => $analysisPrompt],
                    ['role' => 'user', 'content' => $analysisContent]
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 2000,
                'temperature' => 1
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $analysisResponse = curl_exec($ch);
        $analysisHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($analysisResponse === false || !empty($curlError)) {
            error_log("Vision API cURL Error in " . __FILE__ . ":" . __LINE__ . " - " . $curlError);
            // 분석 실패해도 세션은 생성 가능하도록 계속 진행
        } elseif ($analysisHttpCode === 200) {
            $analysisData = json_decode($analysisResponse, true);
            if (isset($analysisData['choices'][0]['message']['content'])) {
                $analysisResult = json_decode($analysisData['choices'][0]['message']['content'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $questionAnalysis = $analysisResult['question_analysis'] ?? null;
                    $solutionAnalysis = $analysisResult['solution_analysis'] ?? null;
                }
            }
        }
    }
    
    // ========== 2단계: 분석 결과를 포함한 튜터 instructions 생성 ==========
    $instructions = buildTutorInstructions(
        $studentInfo,
        $unitName,
        $contentId,
        $questionAnalysis,
        $solutionAnalysis,
        $currentStepLabel,
        $emotionLabel
    );
    
    // ========== 3단계: Realtime 세션 생성 ==========
    // 부동소수점 정밀도 문제 방지를 위해 정수 사용 (temperature: 1)
    $sessionConfig = [
        'model' => 'gpt-4o-realtime-preview-2024-12-17',
        'modalities' => ['text', 'audio'],
        'instructions' => $instructions,
        'voice' => 'alloy', // 또는 'echo', 'shimmer', 'nova', 'fable', 'onyx'
        'temperature' => 1,
        'max_response_output_tokens' => 4096,
        'turn_detection' => [
            'type' => 'server_vad',
            'threshold' => 1,
            'prefix_padding_ms' => 300,
            'silence_duration_ms' => 500
        ]
    ];
    
    $ch = curl_init('https://api.openai.com/v1/realtime/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($sessionConfig),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'OpenAI-Beta: realtime=v1'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || !empty($curlError)) {
        error_log("Realtime API cURL Error in " . __FILE__ . ":" . __LINE__ . " - " . $curlError);
        throw new Exception('Realtime API 호출 실패: ' . $curlError . ' - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
        error_log("Realtime API Error in " . __FILE__ . ":" . __LINE__ . " - " . $errorMessage);
        throw new Exception('Realtime API 오류: ' . $errorMessage . ' - ' . basename(__FILE__) . ':' . __LINE__, $httpCode);
    }
    
    $sessionData = json_decode($response, true);
    
    if (!isset($sessionData['id']) || !isset($sessionData['client_secret']['value'])) {
        throw new Exception('Realtime 세션 응답 형식 오류 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionData['id'],
        'client_secret' => $sessionData['client_secret']['value'],
        'expires_at' => $sessionData['client_secret']['expires_at'] ?? null,
        'question_analysis' => $questionAnalysis,  // 분석 결과도 반환 (디버깅용)
        'solution_analysis' => $solutionAnalysis
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    error_log("Realtime Session Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    
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

/**
 * 분석 결과를 포함한 튜터 instructions 생성
 */
function buildTutorInstructions($studentInfo, $unitName, $contentId, $questionAnalysis, $solutionAnalysis, $currentStep, $emotion) {
    $analysisSection = '';
    
    if ($questionAnalysis) {
        $analysisSection .= "\n## 📖 문제 분석 결과\n";
        $analysisSection .= "- 주제: " . ($questionAnalysis['topic'] ?? '수학') . "\n";
        $analysisSection .= "- 난이도: " . ($questionAnalysis['difficulty'] ?? 'medium') . "\n";
        
        if (!empty($questionAnalysis['concepts'])) {
            $analysisSection .= "- 핵심 개념: " . implode(', ', $questionAnalysis['concepts']) . "\n";
        }
        
        if (!empty($questionAnalysis['steps'])) {
            $analysisSection .= "\n### 풀이 단계:\n";
            foreach ($questionAnalysis['steps'] as $step) {
                $stepNum = $step['step_number'] ?? '';
                $stepName = $step['step_name'] ?? '';
                $stepDesc = $step['description'] ?? '';
                $analysisSection .= "{$stepNum}. {$stepName}: {$stepDesc}\n";
            }
        }
        
        if (!empty($questionAnalysis['common_mistakes'])) {
            $analysisSection .= "\n### 학생들이 자주 틀리는 부분:\n";
            foreach ($questionAnalysis['common_mistakes'] as $mistake) {
                $analysisSection .= "- {$mistake}\n";
            }
        }
        
        if (!empty($questionAnalysis['key_points'])) {
            $analysisSection .= "\n### 핵심 포인트:\n";
            foreach ($questionAnalysis['key_points'] as $point) {
                $analysisSection .= "- {$point}\n";
            }
        }
    }
    
    if ($solutionAnalysis) {
        $analysisSection .= "\n## ✅ 해설 분석 결과\n";
        
        if (!empty($solutionAnalysis['teaching_tips'])) {
            $analysisSection .= "\n### 가르칠 때 강조할 점:\n";
            foreach ($solutionAnalysis['teaching_tips'] as $tip) {
                $analysisSection .= "- {$tip}\n";
            }
        }
        
        if (!empty($solutionAnalysis['hint_strategy'])) {
            $analysisSection .= "\n### 힌트 제공 전략:\n";
            foreach ($solutionAnalysis['hint_strategy'] as $strategy) {
                $analysisSection .= "- {$strategy}\n";
            }
        }
    }
    
    $studentName = ($studentInfo->firstname ?? '') . ' ' . ($studentInfo->lastname ?? '');
    if (trim($studentName) === '') {
        $studentName = '학생';
    }
    
    return <<<PROMPT
당신은 한국 고등학교 수학 전문 과외 선생님입니다. 학생과 1:1 대화하듯이 자연스럽게 설명해주세요.

## 현재 상황
- 단원: {$unitName}
- 학생: {$studentName}
- 현재 풀이 단계: {$currentStep}
- 학생 감정 상태: {$emotion}

{$analysisSection}

## 교수 원칙
1. **청킹**: 한 번에 7±2개 요소만 설명 (인지부하 조절)
2. **단계별 설명**: 문제해석 → 식세우기 → 풀이과정 → 점검 → 장기기억화
3. **실시간 피드백**: 학생이 말하면 즉시 반응, 중간에 끊어도 자연스럽게 대응
4. **감정 케어**: "헷갈릴 수 있어", "잘하고 있어" 등 정서적 완충 제공
5. **메타인지 질문**: "왜 그렇게 생각했어?", "다음엔 뭘 해야 할까?" 등

## 대화 스타일
- 친근하고 따뜻한 말투
- 수학 용어는 정확하게, 설명은 쉽게
- 학생이 이해했는지 확인하며 진행
- 실수해도 괜찮다고 격려
- 위의 문제 분석 결과를 바탕으로 정확하고 구체적으로 설명

## 중요
- 학생이 "잠깐", "다시", "모르겠어"라고 하면 즉시 멈추고 다시 설명
- 문제 분석 결과를 참고하여 학생의 수준에 맞게 설명
- 해설 분석 결과를 참고하되, 학생에게 직접 답을 알려주지 말고 스스로 생각하게 유도
- 현재 풀이 단계({$currentStep})에 맞는 설명 제공
PROMPT;
}

