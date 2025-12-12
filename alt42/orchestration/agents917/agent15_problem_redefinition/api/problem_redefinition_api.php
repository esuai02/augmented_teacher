<?php
/**
 * Problem Redefinition API (GPT-based)
 * File: orchestration/api/problem_redefinition_api.php:1
 *
 * 수집된 워크플로우 데이터를 GPT API로 분석하여
 * 문제 재정의 및 개선방안을 생성합니다.
 */

header('Content-Type: application/json');

include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . '/../../common/api/gpt_helper.php');
require_once(__DIR__ . '/../../common/api/gpt_config.php');

global $DB, $USER;
require_login();

try {
    // POST 데이터 받기
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!$input) {
        throw new Exception('Invalid JSON input (file: problem_redefinition_api.php, line: 24)');
    }

    $userId = $input['userId'] ?? $USER->id;
    $collectedData = $input['data'] ?? null;
    $guidanceMode = $input['guidanceMode'] ?? null;

    if (!$userId) {
        throw new Exception('User ID is required (file: problem_redefinition_api.php, line: 32)');
    }

    if (!$collectedData) {
        throw new Exception('Collected data is required (file: problem_redefinition_api.php, line: 36)');
    }

    // GPT 프롬프트 생성
    $prompt = buildProblemRedefinitionPrompt($collectedData, $guidanceMode);

    // GPT API 호출
    $gptResponse = callGPTAPI($prompt, [
        'model' => GPT_MODEL_DEFAULT,
        'max_tokens' => 2000,
        'temperature' => 0.7
    ]);

    if (!$gptResponse || !isset($gptResponse['content'])) {
        throw new Exception('GPT API call failed (file: problem_redefinition_api.php, line: 51)');
    }

    $redefinitionContent = $gptResponse['content'];

    // 성공 응답
    echo json_encode([
        'success' => true,
        'redefinition' => $redefinitionContent,
        'metadata' => [
            'userId' => $userId,
            'guidanceMode' => $guidanceMode,
            'timestamp' => date('Y-m-d H:i:s'),
            'model' => GPT_MODEL_DEFAULT
        ],
        'file' => 'problem_redefinition_api.php',
        'line' => 66
    ]);

} catch (Exception $e) {
    // 에러 응답
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => 'problem_redefinition_api.php',
        'line' => __LINE__
    ]);
}

/**
 * 문제 재정의 프롬프트 생성
 *
 * @param array $data 수집된 워크플로우 데이터
 * @param string|null $guidanceMode 지도 모드 (선택적)
 * @return string GPT 프롬프트
 */
function buildProblemRedefinitionPrompt($data, $guidanceMode = null) {
    $prompt = "당신은 학습 분석 전문가입니다. 다음 학생의 학습 데이터를 분석하여 문제를 재정의하고 개선방안을 제시해주세요.\n\n";

    // 기본 정보
    $prompt .= "## 학생 기본 정보\n";
    $prompt .= "- User ID: " . ($data['userId'] ?? 'N/A') . "\n";
    $prompt .= "- 데이터 수집 시간: " . ($data['timestamp'] ?? 'N/A') . "\n\n";

    // Step 2: 시험 일정
    if (isset($data['steps']['step2']['data'])) {
        $step2 = $data['steps']['step2']['data'];
        $prompt .= "## 시험 일정 정보\n";
        $prompt .= "- 시험 이름: " . ($step2['exam_name'] ?? 'N/A') . "\n";
        $prompt .= "- 시험 날짜: " . ($step2['exam_date'] ?? 'N/A') . "\n";
        $prompt .= "- D-Day: " . ($step2['d_day'] ?? 'N/A') . "\n";
        $prompt .= "- 목표 점수: " . ($step2['target_score'] ?? 'N/A') . "\n\n";
    }

    // Step 3: 목표 분석
    if (isset($data['steps']['step3']['data']) && !empty($data['steps']['step3']['data'])) {
        $prompt .= "## 목표 분석 데이터\n";
        $prompt .= "- 분석 건수: " . count($data['steps']['step3']['data']) . "건\n";
        foreach ($data['steps']['step3']['data'] as $idx => $goal) {
            $prompt .= "  " . ($idx + 1) . ". 타입: " . ($goal->analysis_type ?? 'N/A') . "\n";
        }
        $prompt .= "\n";
    }

    // Step 4: 활동 유형
    if (isset($data['steps']['step4']['data'])) {
        $step4 = $data['steps']['step4']['data'];
        $prompt .= "## 학습 활동 유형\n";
        $prompt .= "- 활동 타입: " . ($step4['activity_type'] ?? 'N/A') . "\n\n";
    }

    // Step 5: 학습 감정 상태
    if (isset($data['steps']['step5']['data'])) {
        $step5 = $data['steps']['step5']['data'];
        $prompt .= "## 학습 감정 상태\n";
        $prompt .= "- 감정 상태: " . ($step5['emotion'] ?? 'N/A') . "\n";
        $prompt .= "- 스트레스 레벨: " . ($step5['stress_level'] ?? 'N/A') . "/10\n\n";
    }

    // Step 6: 교사 피드백
    if (isset($data['steps']['step6']['data']) && !empty($data['steps']['step6']['data'])) {
        $prompt .= "## 교사 피드백\n";
        $prompt .= "- 피드백 건수: " . count($data['steps']['step6']['data']) . "건\n\n";
    }

    // Step 14: 오답 노트
    if (isset($data['steps']['step14']['data'])) {
        $step14 = $data['steps']['step14']['data'];
        $prompt .= "## 현재 위치 (오답 분석)\n";
        $prompt .= "- 오답 개수: " . ($step14['wrong_answers_count'] ?? 0) . "개\n\n";
    }

    // 지도 모드가 있으면 추가
    if ($guidanceMode) {
        $prompt .= "## 선택된 지도 모드\n";
        $prompt .= "- 모드: " . $guidanceMode . "\n\n";
    }

    // 요청 사항
    $prompt .= "## 요청 사항\n\n";
    $prompt .= "위 데이터를 종합하여 다음 항목을 분석해주세요:\n\n";
    $prompt .= "1. **현재 상황 요약**: 학생의 학습 상태를 3-4문장으로 요약\n";
    $prompt .= "2. **주요 문제점**: 발견된 문제점을 3가지 이내로 정리\n";
    $prompt .= "3. **개선방안**: 구체적이고 실행 가능한 개선방안 3-5가지\n";
    $prompt .= "4. **우선순위**: 어떤 개선방안을 먼저 실행해야 하는지 순서 제시\n\n";
    $prompt .= "답변은 한글로 작성하고, 명확하고 실용적인 조언을 제공해주세요.";

    return $prompt;
}

/**
 * Database Tables Used:
 * - GPT API 호출만 수행하므로 직접적인 DB 테이블 사용 없음
 * - 수집된 데이터는 collect_workflow_data.php에서 제공됨
 */
