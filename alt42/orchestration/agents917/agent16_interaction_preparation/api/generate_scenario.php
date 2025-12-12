<?php
/**
 * Agent 16 Interaction Preparation - Scenario Generation API
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/generate_scenario.php
 *
 * Purpose: Generate personalized interaction scenarios using GPT-4o API
 * Input: userid, guideMode, vibeCodingPrompt, dbTrackingPrompt
 * Output: JSON with scenario markdown or error
 */

// Moodle integration
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input - Line: ' . __LINE__);
    }

    // Validate required fields
    $userid = $input['userid'] ?? null;
    $guideMode = $input['guideMode'] ?? null;
    $vibeCodingPrompt = $input['vibeCodingPrompt'] ?? null;
    $dbTrackingPrompt = $input['dbTrackingPrompt'] ?? null;

    if (!$userid || !$guideMode || !$vibeCodingPrompt || !$dbTrackingPrompt) {
        throw new Exception('Missing required fields: userid, guideMode, vibeCodingPrompt, dbTrackingPrompt - Line: ' . __LINE__);
    }

    // Get GPT API key from config (you need to set this)
    $gptApiKey = get_config('local_augmented_teacher', 'gpt_api_key');

    if (!$gptApiKey) {
        error_log('❌ GPT API key not configured - File: ' . __FILE__ . ' Line: ' . __LINE__);
        throw new Exception('GPT API not configured. Using fallback scenario generation.');
    }

    // Construct system prompt
    $systemPrompt = buildSystemPrompt($guideMode);

    // Construct user prompt
    $userPrompt = buildUserPrompt($guideMode, $vibeCodingPrompt, $dbTrackingPrompt);

    // Call GPT-4o API
    $scenario = callGPTAPI($gptApiKey, $systemPrompt, $userPrompt);

    // Log successful generation
    error_log('✅ Scenario generated successfully for user: ' . $userid . ', mode: ' . $guideMode . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return success response
    echo json_encode([
        'success' => true,
        'scenario' => $scenario,
        'guideMode' => $guideMode,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('❌ Scenario generation error: ' . $e->getMessage() . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'fallback' => true
    ]);
}

/**
 * Build system prompt based on guide mode
 */
function buildSystemPrompt($guideMode) {
    $modeDescriptions = [
        '커리큘럼' => '커리큘럼 중심 모드: 학생이 선생님이 제시한 순서를 따라가며 메인 교재를 기준으로 진행하도록 돕습니다.',
        '맞춤학습' => '맞춤성장 중심 모드: 학생의 강점과 약점, 학습 성향을 정밀 분석하여 개인화된 학습 경로를 설계합니다.',
        '시험대비' => '시험대비 중심 모드: D-Day 기반으로 시간을 역산하여 분량을 나누고 매일 확인 가능한 수치적 목표를 제시합니다.',
        '단기미션' => '단기미션 중심 모드: 일일/주간 단위로 달성 가능한 구체적 미션을 제시하고 즉각적인 피드백과 보상을 제공합니다.',
        '자기성찰' => '자기성찰 중심 모드: 학습 이후 행동 피드백, 감정 반응, 인지 성찰을 순차적으로 정리하도록 돕습니다.',
        '자기주도' => '자기주도 중심 모드: 학생이 자신의 장단점, 에너지 흐름, 집중 스타일을 반영해 학습 환경과 리듬을 스스로 설계하도록 돕습니다.',
        '도제학습' => '도제학습 중심 모드: 교사나 선배의 사고 흐름을 따라가되, 복사가 아니라 변형을 목표로 합니다.',
        '시간성찰' => '시간성찰 중심 모드: 공부 시간 자체보다 집중 상태와 시간 활용 밀도를 기준으로 하루를 평가합니다.',
        '탐구학습' => '탐구학습 중심 모드: 질문을 주도적으로 생성하며 학습 동기를 호기심으로부터 끌어냅니다.'
    ];

    $modeDesc = $modeDescriptions[$guideMode] ?? '일반 학습 모드';

    return <<<SYSTEM
당신은 학생 맞춤형 학습 상호작용 시나리오를 생성하는 전문 교육 AI입니다.

현재 선택된 가이드 모드: {$guideMode}
모드 설명: {$modeDesc}

당신의 역할:
1. 학생의 현재 상태(감정, 맥락, 성향)를 깊이 이해합니다.
2. 학생의 학습 데이터(이력, 패턴, 진도)를 분석합니다.
3. 선택된 가이드 모드의 원칙에 따라 최적의 상호작용 전략을 수립합니다.
4. 구체적이고 실행 가능한 시나리오를 마크다운 형식으로 작성합니다.

출력 형식:
- 마크다운 문법 사용 (헤더, 리스트, 강조 등)
- 명확한 섹션 구분 (기본 정보, 상황 분석, 상호작용 전략, 실행 계획, 모니터링 포인트)
- 구체적이고 실천 가능한 조언
- 학생 중심의 공감적 톤
SYSTEM;
}

/**
 * Build user prompt with context
 */
function buildUserPrompt($guideMode, $vibeCodingPrompt, $dbTrackingPrompt) {
    return <<<PROMPT
다음 정보를 바탕으로 학생 맞춤형 상호작용 시나리오를 생성해주세요.

## VibeCoding 맥락 (감정/맥락/성향)
{$vibeCodingPrompt}

## DBTracking 데이터 (학습 이력/패턴/진도)
{$dbTrackingPrompt}

## 요청사항
위 정보를 종합하여 '{$guideMode}' 모드에 최적화된 상호작용 시나리오를 작성해주세요.

시나리오에는 다음 내용이 포함되어야 합니다:
1. 기본 정보 (생성 시각, 모드, 학생 ID 등)
2. 현재 상황 분석 (VibeCoding + DBTracking 통합 분석)
3. 추천 상호작용 전략 (초기 접근, 목표 설정, 실행 계획, 피드백 전략)
4. 모니터링 포인트 (추적할 핵심 지표들)
5. 예상 시나리오 (구체적인 대화 예시 1-2개)

마크다운 형식으로 작성하되, 교사가 바로 실행할 수 있을 정도로 구체적으로 작성해주세요.
PROMPT;
}

/**
 * Call GPT-4o API
 */
function callGPTAPI($apiKey, $systemPrompt, $userPrompt) {
    $apiUrl = 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL error: ' . $curlError . ' - Line: ' . __LINE__);
    }

    if ($httpCode !== 200) {
        throw new Exception('GPT API returned HTTP ' . $httpCode . ' - Line: ' . __LINE__);
    }

    $result = json_decode($response, true);

    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid GPT API response - Line: ' . __LINE__);
    }

    return $result['choices'][0]['message']['content'];
}
