<?php
/**
 * 양자 모델링 API
 * 다양한 문제풀이 방법 및 오개념 탐색을 위한 OpenAI API 호출
 * 탐색 결과를 DB에 저장하고 양자 붕괴 회로 상태 업데이트
 *
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 *
 * 관련 DB 테이블:
 * - mdl_alt42_quantum_solutions (id INT AUTO_INCREMENT PRIMARY KEY, content_id INT, student_id INT, solution_type VARCHAR(50), solution_data JSON, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
 * - mdl_alt42_quantum_misconceptions (id INT AUTO_INCREMENT PRIMARY KEY, content_id INT, student_id INT, misconception_type VARCHAR(50), misconception_data JSON, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
 * - mdl_alt42_quantum_collapse_circuit (id INT AUTO_INCREMENT PRIMARY KEY, content_id INT, circuit_state JSON, solution_count INT DEFAULT 0, misconception_count INT DEFAULT 0, last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . '/../../config.php'); // OpenAI API 키 설정
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_REQUEST;
}

$action = $input['action'] ?? null;
$contentId = $input['content_id'] ?? null;
$studentId = $input['student_id'] ?? $USER->id;
$questionImage = $input['question_image'] ?? null;
$solutionImage = $input['solution_image'] ?? null;

try {
    if (!$action) {
        throw new Exception("[quantum_api.php] action 파라미터가 필요합니다.");
    }

    switch ($action) {
        case 'explore_solutions':
            $result = exploreSolutions($contentId, $studentId, $questionImage, $solutionImage);
            break;

        case 'explore_misconceptions':
            $result = exploreMisconceptions($contentId, $studentId, $questionImage, $solutionImage);
            break;

        case 'get_circuit_state':
            $result = getCircuitState($contentId);
            break;

        case 'update_circuit':
            $circuitState = $input['circuit_state'] ?? [];
            $result = updateCircuitState($contentId, $circuitState);
            break;

        default:
            throw new Exception("[quantum_api.php] 알 수 없는 action: " . $action);
    }

    echo json_encode([
        'success' => true,
        'action' => $action,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("[quantum_api.php] 오류: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 다양한 풀이 방법 탐색
 */
function exploreSolutions($contentId, $studentId, $questionImage, $solutionImage) {
    global $DB;

    if (!$contentId) {
        throw new Exception("[quantum_api.php:exploreSolutions] content_id가 필요합니다.");
    }

    // 이미지 URL이 없으면 DB에서 가져오기
    if (!$questionImage || !$solutionImage) {
        $qtext = $DB->get_record_sql(
            "SELECT questiontext, generalfeedback FROM mdl_question WHERE id = ? LIMIT 1",
            [$contentId]
        );

        if ($qtext) {
            if (!$questionImage) {
                $questionImage = extractImageFromHtml($qtext->questiontext);
            }
            if (!$solutionImage) {
                $solutionImage = extractImageFromHtml($qtext->generalfeedback);
            }
        }
    }

    if (!$questionImage) {
        throw new Exception("[quantum_api.php:exploreSolutions] 문제 이미지를 찾을 수 없습니다.");
    }

    // OpenAI API로 다양한 풀이 탐색
    $prompt = buildSolutionExplorationPrompt();
    $apiResult = callOpenAIVision($prompt, $questionImage, $solutionImage);

    // 결과 파싱
    $solutions = parseOpenAIResponse($apiResult, 'solutions');

    // DB에 저장
    $savedSolutions = [];
    foreach ($solutions as $solution) {
        $record = new stdClass();
        $record->content_id = $contentId;
        $record->student_id = $studentId;
        $record->solution_type = $solution['type'] ?? 'general';
        $record->solution_data = json_encode($solution, JSON_UNESCAPED_UNICODE);
        $record->created_at = date('Y-m-d H:i:s');

        try {
            $insertId = $DB->insert_record('alt42_quantum_solutions', $record);
            $solution['id'] = $insertId;
            $savedSolutions[] = $solution;
        } catch (Exception $e) {
            error_log("[quantum_api.php:exploreSolutions] DB 저장 실패: " . $e->getMessage());
        }
    }

    // 양자 붕괴 회로 업데이트
    updateQuantumCircuit($contentId, 'solutions', count($savedSolutions));

    return [
        'solutions' => $savedSolutions,
        'total_count' => count($savedSolutions),
        'circuit_updated' => true
    ];
}

/**
 * 오개념 풀이 탐색
 */
function exploreMisconceptions($contentId, $studentId, $questionImage, $solutionImage) {
    global $DB;

    if (!$contentId) {
        throw new Exception("[quantum_api.php:exploreMisconceptions] content_id가 필요합니다.");
    }

    // 이미지 URL이 없으면 DB에서 가져오기
    if (!$questionImage || !$solutionImage) {
        $qtext = $DB->get_record_sql(
            "SELECT questiontext, generalfeedback FROM mdl_question WHERE id = ? LIMIT 1",
            [$contentId]
        );

        if ($qtext) {
            if (!$questionImage) {
                $questionImage = extractImageFromHtml($qtext->questiontext);
            }
            if (!$solutionImage) {
                $solutionImage = extractImageFromHtml($qtext->generalfeedback);
            }
        }
    }

    if (!$questionImage) {
        throw new Exception("[quantum_api.php:exploreMisconceptions] 문제 이미지를 찾을 수 없습니다.");
    }

    // OpenAI API로 오개념 탐색
    $prompt = buildMisconceptionExplorationPrompt();
    $apiResult = callOpenAIVision($prompt, $questionImage, $solutionImage);

    // 결과 파싱
    $misconceptions = parseOpenAIResponse($apiResult, 'misconceptions');

    // DB에 저장
    $savedMisconceptions = [];
    foreach ($misconceptions as $misconception) {
        $record = new stdClass();
        $record->content_id = $contentId;
        $record->student_id = $studentId;
        $record->misconception_type = $misconception['type'] ?? 'general';
        $record->misconception_data = json_encode($misconception, JSON_UNESCAPED_UNICODE);
        $record->created_at = date('Y-m-d H:i:s');

        try {
            $insertId = $DB->insert_record('alt42_quantum_misconceptions', $record);
            $misconception['id'] = $insertId;
            $savedMisconceptions[] = $misconception;
        } catch (Exception $e) {
            error_log("[quantum_api.php:exploreMisconceptions] DB 저장 실패: " . $e->getMessage());
        }
    }

    // 양자 붕괴 회로 업데이트
    updateQuantumCircuit($contentId, 'misconceptions', count($savedMisconceptions));

    return [
        'misconceptions' => $savedMisconceptions,
        'total_count' => count($savedMisconceptions),
        'circuit_updated' => true
    ];
}

/**
 * 양자 붕괴 회로 상태 조회
 */
function getCircuitState($contentId) {
    global $DB;

    if (!$contentId) {
        throw new Exception("[quantum_api.php:getCircuitState] content_id가 필요합니다.");
    }

    $circuit = $DB->get_record_sql(
        "SELECT * FROM {alt42_quantum_collapse_circuit} WHERE content_id = ? LIMIT 1",
        [$contentId]
    );

    if (!$circuit) {
        return [
            'exists' => false,
            'state' => getDefaultCircuitState(),
            'solution_count' => 0,
            'misconception_count' => 0
        ];
    }

    return [
        'exists' => true,
        'state' => json_decode($circuit->circuit_state, true),
        'solution_count' => $circuit->solution_count ?? 0,
        'misconception_count' => $circuit->misconception_count ?? 0,
        'last_updated' => $circuit->last_updated
    ];
}

/**
 * 양자 붕괴 회로 상태 업데이트
 */
function updateCircuitState($contentId, $circuitState) {
    global $DB;

    if (!$contentId) {
        throw new Exception("[quantum_api.php:updateCircuitState] content_id가 필요합니다.");
    }

    $existing = $DB->get_record_sql(
        "SELECT id FROM {alt42_quantum_collapse_circuit} WHERE content_id = ? LIMIT 1",
        [$contentId]
    );

    if ($existing) {
        $record = new stdClass();
        $record->id = $existing->id;
        $record->circuit_state = json_encode($circuitState, JSON_UNESCAPED_UNICODE);
        $record->last_updated = date('Y-m-d H:i:s');

        $DB->update_record('alt42_quantum_collapse_circuit', $record);
    } else {
        $record = new stdClass();
        $record->content_id = $contentId;
        $record->circuit_state = json_encode($circuitState, JSON_UNESCAPED_UNICODE);
        $record->solution_count = 0;
        $record->misconception_count = 0;
        $record->last_updated = date('Y-m-d H:i:s');

        $DB->insert_record('alt42_quantum_collapse_circuit', $record);
    }

    return ['success' => true, 'state' => $circuitState];
}

/**
 * 양자 붕괴 회로 내부 업데이트
 */
function updateQuantumCircuit($contentId, $type, $count) {
    global $DB;

    $existing = $DB->get_record_sql(
        "SELECT * FROM {alt42_quantum_collapse_circuit} WHERE content_id = ? LIMIT 1",
        [$contentId]
    );

    $currentState = $existing ? json_decode($existing->circuit_state, true) : getDefaultCircuitState();

    // 상태 업데이트
    if ($type === 'solutions') {
        $currentState['explore']['status'] = 'collapsed';
        $currentState['explore']['count'] = ($currentState['explore']['count'] ?? 0) + $count;
        $currentState['model']['status'] = 'active';
    } elseif ($type === 'misconceptions') {
        $currentState['explore']['misconception_count'] = ($currentState['explore']['misconception_count'] ?? 0) + $count;
    }

    if ($existing) {
        $record = new stdClass();
        $record->id = $existing->id;
        $record->circuit_state = json_encode($currentState, JSON_UNESCAPED_UNICODE);

        if ($type === 'solutions') {
            $record->solution_count = ($existing->solution_count ?? 0) + $count;
        } else {
            $record->misconception_count = ($existing->misconception_count ?? 0) + $count;
        }

        $record->last_updated = date('Y-m-d H:i:s');
        $DB->update_record('alt42_quantum_collapse_circuit', $record);
    } else {
        $record = new stdClass();
        $record->content_id = $contentId;
        $record->circuit_state = json_encode($currentState, JSON_UNESCAPED_UNICODE);
        $record->solution_count = $type === 'solutions' ? $count : 0;
        $record->misconception_count = $type === 'misconceptions' ? $count : 0;
        $record->last_updated = date('Y-m-d H:i:s');

        $DB->insert_record('alt42_quantum_collapse_circuit', $record);
    }
}

/**
 * 기본 회로 상태
 */
function getDefaultCircuitState() {
    return [
        'input' => ['status' => 'collapsed', 'timestamp' => null],
        'parse' => ['status' => 'collapsed', 'timestamp' => null],
        'explore' => ['status' => 'pending', 'count' => 0, 'misconception_count' => 0],
        'model' => ['status' => 'pending', 'timestamp' => null],
        'collapse' => ['status' => 'pending', 'timestamp' => null],
        'output' => ['status' => 'pending', 'timestamp' => null]
    ];
}

/**
 * 풀이 탐색 프롬프트
 */
function buildSolutionExplorationPrompt() {
    return <<<PROMPT
당신은 수학 교육 전문가입니다. 주어진 수학 문제의 다양한 풀이 방법을 탐색해주세요.

**분석 요청**:
1. 문제 이미지와 해설 이미지를 분석하세요
2. 최소 3가지 이상의 서로 다른 풀이 접근법을 제시하세요
3. 각 풀이 방법의 장단점을 설명하세요

**출력 형식 (JSON)**:
{
    "solutions": [
        {
            "title": "풀이 방법 제목",
            "type": "algebraic|geometric|computational|conceptual|shortcut",
            "content": "상세한 풀이 설명",
            "difficulty": "easy|medium|hard",
            "pros": ["장점1", "장점2"],
            "cons": ["단점1", "단점2"]
        }
    ],
    "recommended": "가장 추천하는 방법의 title",
    "problem_type": "문제 유형",
    "key_concepts": ["핵심개념1", "핵심개념2"]
}

JSON 형식으로만 출력하세요.
PROMPT;
}

/**
 * 오개념 탐색 프롬프트
 */
function buildMisconceptionExplorationPrompt() {
    return <<<PROMPT
당신은 수학 교육 전문가입니다. 주어진 수학 문제에서 학생들이 흔히 범하는 오개념과 실수를 분석해주세요.

**분석 요청**:
1. 문제 이미지와 해설 이미지를 분석하세요
2. 학생들이 흔히 범하는 오개념과 실수를 3가지 이상 찾아주세요
3. 각 오개념의 원인과 교정 방법을 설명하세요

**출력 형식 (JSON)**:
{
    "misconceptions": [
        {
            "title": "오개념/실수 제목",
            "type": "calculation|concept|interpretation|process|careless",
            "content": "오개념 상세 설명",
            "cause": "발생 원인",
            "frequency": "very_common|common|occasional",
            "correction": "교정 방법",
            "prevention_tip": "예방 팁"
        }
    ],
    "most_common": "가장 흔한 오개념의 title",
    "difficulty_factors": ["난이도 요인1", "난이도 요인2"],
    "teaching_suggestions": ["교수법 제안1", "교수법 제안2"]
}

JSON 형식으로만 출력하세요.
PROMPT;
}

/**
 * OpenAI Vision API 호출
 */
function callOpenAIVision($prompt, $questionImage, $solutionImage = null) {
    $apiKey = OPENAI_API_KEY;
    $model = OPENAI_MODEL;

    $imageContent = [];
    $imageContent[] = [
        'type' => 'text',
        'text' => $prompt
    ];

    // 문제 이미지 추가
    if ($questionImage) {
        $imageContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $questionImage,
                'detail' => 'high'
            ]
        ];
    }

    // 해설 이미지 추가
    if ($solutionImage) {
        $imageContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $solutionImage,
                'detail' => 'high'
            ]
        ];
    }

    $postData = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $imageContent
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 4000,
        'response_format' => ['type' => 'json_object']
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($curlError)) {
        error_log("[quantum_api.php:callOpenAIVision] cURL 오류: " . $curlError);
        throw new Exception("OpenAI API 호출 실패: " . $curlError);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
        error_log("[quantum_api.php:callOpenAIVision] API 오류: " . $errorMessage);
        throw new Exception("OpenAI API 오류: " . $errorMessage);
    }

    $data = json_decode($response, true);

    if (!isset($data['choices'][0]['message']['content'])) {
        error_log("[quantum_api.php:callOpenAIVision] 응답 형식 오류");
        throw new Exception("OpenAI 응답 형식 오류");
    }

    return $data['choices'][0]['message']['content'];
}

/**
 * OpenAI 응답 파싱
 */
function parseOpenAIResponse($response, $type) {
    // JSON 추출
    $jsonText = $response;

    // 마크다운 코드 블록 제거
    if (preg_match('/```json\s*(.*?)\s*```/s', $jsonText, $matches)) {
        $jsonText = $matches[1];
    } elseif (preg_match('/```\s*(.*?)\s*```/s', $jsonText, $matches)) {
        $jsonText = $matches[1];
    }

    $parsed = json_decode(trim($jsonText), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[quantum_api.php:parseOpenAIResponse] JSON 파싱 오류: " . json_last_error_msg());
        error_log("응답 내용: " . substr($response, 0, 500));

        // 기본 결과 반환
        return [
            [
                'title' => '분석 결과',
                'type' => 'general',
                'content' => $response
            ]
        ];
    }

    return $parsed[$type] ?? [];
}

/**
 * HTML에서 이미지 URL 추출
 */
function extractImageFromHtml($html) {
    if (empty($html)) return null;

    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $images = $dom->getElementsByTagName('img');

    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        $src = str_replace(' ', '%20', $src);

        // 유효한 이미지 URL 판단
        if (strpos($src, 'hintimages') === false &&
            (strpos($src, '.png') !== false || strpos($src, '.jpg') !== false)) {
            return $src;
        }
    }

    return null;
}
