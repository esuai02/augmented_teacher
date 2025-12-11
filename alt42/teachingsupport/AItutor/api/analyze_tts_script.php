<?php
/**
 * TTS 대본 분석 API
 * OpenAI API로 TTS 대본을 분석하여 인지맵 노드 순서 추출
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
    
    $ttsScript = $input['tts_script'] ?? null;
    $contentId = $input['content_id'] ?? null;
    $contentsType = $input['contents_type'] ?? null;
    $nodes = $input['nodes'] ?? [];
    
    if (empty($ttsScript)) {
        throw new Exception('TTS 대본이 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }
    
    if (empty($contentId)) {
        throw new Exception('content_id가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }
    
    if (empty($nodes) || !is_array($nodes)) {
        throw new Exception('노드 정보가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }
    
    // 기존 분석 결과 확인
    $existingAnalysis = null;
    if (!empty($contentId)) {
        try {
            $existingAnalysis = $DB->get_record_sql(
                "SELECT * FROM {at_quantum_tts_analyses} 
                 WHERE content_id = ? AND status = 'completed' 
                 ORDER BY created_at DESC LIMIT 1",
                [$contentId]
            );
            
            // 기존 분석의 TTS 대본과 현재 대본이 동일하면 재사용
            if ($existingAnalysis && $existingAnalysis->tts_script === $ttsScript) {
                error_log("[analyze_tts_script.php] 기존 분석 결과 재사용 - analysis_id: {$existingAnalysis->analysis_id}");
                
                echo json_encode([
                    'success' => true,
                    'from_cache' => true,
                    'analysis_id' => $existingAnalysis->analysis_id,
                    'nodeSequence' => json_decode($existingAnalysis->node_sequence, true),
                    'matchingDetails' => json_decode($existingAnalysis->matching_details, true),
                    'analysisSummary' => $existingAnalysis->analysis_summary,
                    'confidenceScore' => $existingAnalysis->confidence_score
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } catch (Exception $e) {
            error_log("[analyze_tts_script.php] 기존 분석 조회 오류: " . $e->getMessage());
        }
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
        $apiKey = get_config('local_augmented_teacher', 'openai_api_key');
    }
    
    if (!$apiKey) {
        throw new Exception('OpenAI API 키가 설정되지 않았습니다 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    // 노드 정보를 문자열로 변환
    $nodesInfo = array_map(function($node) {
        $desc = isset($node['desc']) ? $node['desc'] : '';
        return "- {$node['id']}: {$node['label']} (단계{$node['stage']}, 타입: {$node['type']}) - {$desc}";
    }, $nodes);
    
    $nodesInfoText = implode("\n", $nodesInfo);
    
    // TTS 대본을 섹션으로 분리
    $ttsSections = array_filter(array_map('trim', explode('@', $ttsScript)));
    $ttsSections = array_values($ttsSections);
    
    // 시스템 프롬프트 구성
    $systemPrompt = <<<PROMPT
당신은 수학 문제 풀이 해설을 분석하는 전문가입니다.

## 사용 가능한 노드 목록:
{$nodesInfoText}

## 작업:
제공된 TTS 음성해설 대본을 분석하여, 해설 내용에 따라 노드를 순서대로 나열해주세요.

## 매칭 규칙:
1. **의미적 유사도**: TTS 대본의 내용과 노드의 label, desc를 비교하여 가장 유사한 노드 선택
2. **단계 순서**: stage 번호를 고려하여 논리적 순서 유지 (낮은 단계부터 높은 단계로)
3. **타입 일치**: 
   - "먼저", "시작", "읽어" → start 노드
   - "정답", "맞다", "올바르" → correct/success 노드
   - "틀렸다", "오류", "실수" → wrong/fail 노드
   - "부분적으로", "일부" → partial 노드
   - "헷갈림", "혼란" → confused 노드

## 출력 형식 (JSON):
{
    "nodeSequence": ["start", "node_id1", "node_id2", "node_id3", ...],
    "matchingDetails": [
        {
            "sectionIndex": 0,
            "sectionText": "먼저 문제를 읽어봅시다",
            "matchedNodeId": "start",
            "confidence": 0.95,
            "reason": "시작 단계와 의미적으로 일치"
        },
        ...
    ],
    "analysisSummary": "대본 분석 요약 (한국어, 100자 이내)"
}

## 중요:
- 대본의 풀이 순서에 맞게 노드 ID를 배열로 나열
- 각 노드의 label, desc, stage를 참고하여 적절한 노드 선택
- 시작 노드는 항상 "start"로 시작
- confidence는 0.0-1.0 사이의 값
- 모든 섹션이 노드와 매칭되지 않아도 가능한 만큼 매칭
PROMPT;
    
    // 사용자 프롬프트 구성
    $userPrompt = "다음 TTS 대본을 분석하여 노드 순서를 추출해주세요:\n\n" . $ttsScript;
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ];
    
    // OpenAI API 호출
    $startTime = microtime(true);
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $postData = [
        'model' => 'gpt-4o',
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => 2000,
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
    
    $processingTime = round((microtime(true) - $startTime) * 1000);
    
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
        throw new Exception('분석 결과 파싱 실패 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    // 필수 필드 확인
    if (!isset($analysisResult['nodeSequence']) || !is_array($analysisResult['nodeSequence'])) {
        throw new Exception('노드 순서가 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    
    $nodeSequence = $analysisResult['nodeSequence'];
    $matchingDetails = $analysisResult['matchingDetails'] ?? [];
    $analysisSummary = $analysisResult['analysisSummary'] ?? 'TTS 대본 분석 완료';
    
    // 신뢰도 계산 (평균)
    $confidenceScore = 0.0;
    if (!empty($matchingDetails)) {
        $totalConfidence = 0;
        foreach ($matchingDetails as $detail) {
            $totalConfidence += $detail['confidence'] ?? 0.5;
        }
        $confidenceScore = round($totalConfidence / count($matchingDetails), 2);
    } else {
        $confidenceScore = 0.5; // 기본값
    }
    
    // 토큰 사용량 추출
    $tokensUsed = $data['usage']['total_tokens'] ?? 0;
    
    // 분석 결과 DB 저장
    $analysisId = 'TTS_ANALYSIS_' . time() . '_' . uniqid();
    
    try {
        $record = new stdClass();
        $record->analysis_id = $analysisId;
        $record->content_id = $contentId;
        $record->contents_type = $contentsType;
        $record->user_id = $USER->id;
        $record->tts_script = $ttsScript;
        $record->tts_sections = json_encode($ttsSections, JSON_UNESCAPED_UNICODE);
        $record->interaction_id = $input['interaction_id'] ?? null;
        $record->node_sequence = json_encode($nodeSequence, JSON_UNESCAPED_UNICODE);
        $record->matching_details = json_encode($matchingDetails, JSON_UNESCAPED_UNICODE);
        $record->analysis_summary = $analysisSummary;
        $record->confidence_score = $confidenceScore;
        $record->openai_model = 'gpt-4o';
        $record->openai_tokens_used = $tokensUsed;
        $record->openai_request = json_encode($postData, JSON_UNESCAPED_UNICODE);
        $record->openai_response = json_encode($data, JSON_UNESCAPED_UNICODE);
        $record->status = 'completed';
        $record->processing_time_ms = $processingTime;
        
        $DB->insert_record('at_quantum_tts_analyses', $record);
        
        error_log("[analyze_tts_script.php] 분석 결과 저장 완료 - analysis_id: {$analysisId}");
    } catch (Exception $e) {
        error_log("[analyze_tts_script.php] DB 저장 오류: " . $e->getMessage());
        // DB 저장 실패해도 분석 결과는 반환
    }
    
    echo json_encode([
        'success' => true,
        'from_cache' => false,
        'analysis_id' => $analysisId,
        'nodeSequence' => $nodeSequence,
        'matchingDetails' => $matchingDetails,
        'analysisSummary' => $analysisSummary,
        'confidenceScore' => $confidenceScore,
        'tokensUsed' => $tokensUsed,
        'processingTimeMs' => $processingTime
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    error_log("TTS Script Analysis Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    
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

