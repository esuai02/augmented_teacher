<?php
/**
 * generate_faqtext.php - 점층상호작용(faqtext) 생성 API
 * 파일 위치: alt42/teachingsupport/api/generate_faqtext.php
 *
 * narration_text에서 @로 구분된 각 단계별로 6가지 점층적 반복 강조 멘트를 생성합니다.
 * 
 * faqtext 구조 (JSON):
 * [
 *   {
 *     "step_index": 1,
 *     "step_label": "문제 파악",
 *     "original": "원본 TTS 텍스트",
 *     "faqtext": [
 *       "1. 단축형 - 핵심만 간결하게",
 *       "2. 함축형 - 의미 압축",
 *       "3. 변형A - 다른 표현",
 *       "4. 변형B - 다른 관점/비유",
 *       "5. 강조형 - 핵심 키워드 부각",
 *       "6. 확정형 - 확실한 의미 각인"
 *     ]
 *   },
 *   ...
 * ]
 */

// 출력 버퍼링 시작
ob_start();

// JSON 헤더 설정
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// 에러 출력을 로그로만
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// 메모리 및 실행시간 제한 증가
ini_set('memory_limit', '512M');
set_time_limit(180);

try {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    require_login();
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Config 로드 실패: ' . $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
    exit;
}

// POST 데이터 받기
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'JSON 파싱 실패: ' . json_last_error_msg(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
    exit;
}

try {
    $action = $input['action'] ?? 'generate_faqtext';
    $interactionId = $input['interaction_id'] ?? null;
    $contentId = $input['content_id'] ?? null;
    $studentId = $input['student_id'] ?? $USER->id;
    
    error_log("[generate_faqtext.php:Line" . __LINE__ . "] 요청 수신 - interactionId: {$interactionId}, contentId: {$contentId}");
    
    // interaction_id가 없으면 content_id로 조회
    if (!$interactionId && $contentId) {
        $existing = $DB->get_record_sql(
            "SELECT id, narration_text, faqtext FROM {ktm_teaching_interactions} WHERE contentsid = ? AND narration_text IS NOT NULL ORDER BY id DESC LIMIT 1",
            [$contentId]
        );
        if ($existing) {
            $interactionId = $existing->id;
        }
    }
    
    if (!$interactionId) {
        throw new Exception('interaction_id 또는 content_id가 필요합니다.');
    }
    
    // 상호작용 레코드 조회
    $interaction = $DB->get_record('ktm_teaching_interactions', ['id' => $interactionId]);
    if (!$interaction) {
        throw new Exception('상호작용 레코드를 찾을 수 없습니다. ID: ' . $interactionId);
    }
    
    // narration_text 확인
    $narrationText = $interaction->narration_text ?? '';
    if (empty($narrationText)) {
        throw new Exception('narration_text가 비어있습니다. TTS를 먼저 생성해주세요.');
    }
    
    error_log("[generate_faqtext.php:Line" . __LINE__ . "] narration_text 길이: " . strlen($narrationText));
    
    // @ 구분자로 단계 분리
    $sections = array_values(array_filter(array_map('trim', explode('@', $narrationText))));
    $sectionCount = count($sections);
    
    error_log("[generate_faqtext.php:Line" . __LINE__ . "] 단계 수: {$sectionCount}");
    
    if ($sectionCount === 0) {
        throw new Exception('narration_text에서 단계를 추출할 수 없습니다.');
    }
    
    // 각 단계별로 6가지 점층적 표현 생성
    $faqtextData = [];
    
    foreach ($sections as $idx => $sectionText) {
        $stepNum = $idx + 1;
        $stepLabel = getStepLabel($stepNum, $sectionCount);
        
        error_log("[generate_faqtext.php:Line" . __LINE__ . "] 단계 {$stepNum} 처리 중: " . mb_substr($sectionText, 0, 50) . "...");
        
        // OpenAI API로 6가지 점층적 표현 생성
        $faqResult = generateProgressiveFaq($sectionText, $stepNum, $stepLabel);
        
        if ($faqResult['success']) {
            $faqtextData[] = [
                'step_index' => $stepNum,
                'step_label' => $stepLabel,
                'original' => $sectionText,
                'faqtext' => $faqResult['faqtext']
            ];
            error_log("[generate_faqtext.php:Line" . __LINE__ . "] 단계 {$stepNum} 생성 완료");
        } else {
            error_log("[generate_faqtext.php:Line" . __LINE__ . "] 단계 {$stepNum} 생성 실패: " . ($faqResult['error'] ?? 'Unknown'));
            // 실패해도 기본값으로 추가
            $faqtextData[] = [
                'step_index' => $stepNum,
                'step_label' => $stepLabel,
                'original' => $sectionText,
                'faqtext' => generateDefaultFaq($sectionText)
            ];
        }
    }
    
    // faqtext를 JSON으로 변환
    $faqtextJson = json_encode($faqtextData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // DB에 faqtext 저장
    $saveResult = saveFaqtext($interactionId, $faqtextJson);
    
    if (!$saveResult['success']) {
        error_log("[generate_faqtext.php:Line" . __LINE__ . "] faqtext 저장 실패: " . $saveResult['error']);
    }
    
    // 미리보기 데이터 (첫 2개 단계만)
    $previewData = array_slice($faqtextData, 0, 2);
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'interaction_id' => $interactionId,
        'sections_count' => $sectionCount,
        'faqtext_preview' => $previewData,
        'saved' => $saveResult['success'],
        'message' => "총 {$sectionCount}개 단계의 점층상호작용이 생성되었습니다."
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("[generate_faqtext.php:Line" . __LINE__ . "] Exception: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}

/**
 * 단계 라벨 생성
 */
function getStepLabel($stepNum, $totalSteps) {
    // 5단계 기준 라벨
    $defaultLabels = [
        1 => '문제 파악',
        2 => '풀이 전략',
        3 => '풀이 과정',
        4 => '정답 확인',
        5 => '장기기억화'
    ];
    
    if ($totalSteps <= 5 && isset($defaultLabels[$stepNum])) {
        return $defaultLabels[$stepNum];
    }
    
    // 6단계 이상인 경우
    if ($stepNum === 1) return '문제 파악';
    if ($stepNum === 2) return '풀이 전략';
    if ($stepNum === $totalSteps) return '정답 확인';
    if ($stepNum === $totalSteps - 1) return '검산';
    return '풀이 과정 ' . ($stepNum - 2);
}

/**
 * OpenAI API로 6가지 점층적 표현 생성
 */
function generateProgressiveFaq($sectionText, $stepNum, $stepLabel) {
    require_once(__DIR__ . '/../config.php');
    
    if (!defined('OPENAI_API_KEY')) {
        return ['success' => false, 'error' => 'OPENAI_API_KEY 미설정'];
    }
    
    // 고정된 프롬프트 템플릿 - 일관된 형태로 생성되도록
    $systemPrompt = "당신은 수학 학습 콘텐츠 전문가입니다. 주어진 수학 풀이 단계의 핵심 내용을 학생이 장기기억에 저장할 수 있도록 점층적으로 강조하는 6가지 버전을 만들어주세요.

# 6가지 점층적 표현 규칙 (반드시 이 순서와 형식을 지켜주세요)

1. **단축형**: 10자 내외로 핵심만 짧게 요약 (예: \"분모 통일이 핵심!\")
2. **함축형**: 20자 내외로 의미를 압축 (예: \"분모를 같게 만들면 계산 가능\")
3. **변형A**: 다른 단어/표현으로 재표현 (예: \"분모 맞추기 = 통분하기\")
4. **변형B**: 비유나 예시 활용 (예: \"케이크 조각 수 맞추듯 분모도 맞춰요\")
5. **강조형**: 핵심 키워드를 반복 강조 (예: \"통분! 통분이 먼저! 분모를 통일하세요\")
6. **확정형**: 확신 있는 문장으로 마무리 (예: \"분모를 같게 하면 분수 덧셈은 끝난 거야\")

# 응답 형식
반드시 아래 JSON 형식으로만 응답하세요. 다른 텍스트 없이 JSON만 출력:
{
  \"faqtext\": [
    \"단축형 내용\",
    \"함축형 내용\",
    \"변형A 내용\",
    \"변형B 내용\",
    \"강조형 내용\",
    \"확정형 내용\"
  ]
}";

    $userPrompt = "다음은 수학 문제 풀이의 [{$stepLabel}] 단계입니다. 이 내용을 6가지 점층적 표현으로 변환해주세요:\n\n" . $sectionText;
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o-mini',  // 비용 효율적인 모델 사용
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.3,  // 일관성을 위해 낮은 temperature
        'response_format' => ['type' => 'json_object']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("[generate_faqtext.php] OpenAI API 오류: HTTP " . $httpCode . ", Response: " . $response);
        return ['success' => false, 'error' => 'OpenAI API 호출 실패: HTTP ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    
    if (empty($content)) {
        return ['success' => false, 'error' => 'OpenAI 응답 없음'];
    }
    
    // JSON 파싱
    $faqData = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($faqData['faqtext'])) {
        error_log("[generate_faqtext.php] JSON 파싱 실패: " . $content);
        return ['success' => false, 'error' => 'JSON 파싱 실패'];
    }
    
    // 6개가 아닌 경우 기본값으로 채우기
    $faqtext = $faqData['faqtext'];
    while (count($faqtext) < 6) {
        $faqtext[] = generateDefaultFaqItem($sectionText, count($faqtext) + 1);
    }
    
    return ['success' => true, 'faqtext' => array_slice($faqtext, 0, 6)];
}

/**
 * 기본 faqtext 생성 (API 실패 시 폴백)
 */
function generateDefaultFaq($sectionText) {
    $short = mb_substr($sectionText, 0, 15) . '...';
    return [
        "핵심: " . $short,
        "요약: " . mb_substr($sectionText, 0, 25),
        "다시 말하면: " . mb_substr($sectionText, 0, 30),
        "쉽게 말해서: " . mb_substr($sectionText, 0, 30),
        "중요! " . $short . " 기억하세요!",
        "결론: " . mb_substr($sectionText, 0, 20) . " - 확실히 기억!"
    ];
}

/**
 * 기본 faqtext 항목 생성
 */
function generateDefaultFaqItem($sectionText, $index) {
    $labels = ['단축', '함축', '변형', '비유', '강조', '확정'];
    $label = $labels[$index - 1] ?? '추가';
    return "[{$label}] " . mb_substr($sectionText, 0, 20 + ($index * 5));
}

/**
 * faqtext를 DB에 저장
 */
function saveFaqtext($interactionId, $faqtextJson) {
    global $DB, $CFG;
    
    try {
        // faqtext 필드 존재 확인
        try {
            $fieldCheckSql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions LIKE 'faqtext'";
            $fieldExists = $DB->get_record_sql($fieldCheckSql);
            
            if (!$fieldExists) {
                error_log('[generate_faqtext.php] faqtext 필드 추가 시도');
                $alterSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN faqtext LONGTEXT DEFAULT NULL";
                $DB->execute($alterSql);
                error_log('[generate_faqtext.php] faqtext 필드 추가 완료');
            }
        } catch (Exception $e) {
            error_log('[generate_faqtext.php] 필드 확인/추가 오류: ' . $e->getMessage());
        }
        
        // SQL 직접 업데이트
        $sql = "UPDATE {$CFG->prefix}ktm_teaching_interactions 
                SET faqtext = :faqtext, timemodified = :timemodified 
                WHERE id = :id";
        
        $params = [
            'faqtext' => $faqtextJson,
            'timemodified' => time(),
            'id' => $interactionId
        ];
        
        $updateResult = $DB->execute($sql, $params);
        
        // 저장 확인
        $checkSql = "SELECT faqtext FROM {$CFG->prefix}ktm_teaching_interactions WHERE id = :id";
        $savedRecord = $DB->get_record_sql($checkSql, ['id' => $interactionId]);
        $savedLength = strlen($savedRecord->faqtext ?? '');
        
        error_log("[generate_faqtext.php] faqtext 저장 확인 - 입력: " . strlen($faqtextJson) . ", 저장: " . $savedLength);
        
        return ['success' => $savedLength > 0, 'saved_length' => $savedLength];
        
    } catch (Exception $e) {
        error_log('[generate_faqtext.php] DB 저장 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>

