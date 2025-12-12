<?php
/**
 * 종합 피드백 API
 * 여러 데이터 소스를 통합하여 GPT API로 종합 분석 제공
 */

// 오류 표시 완전 비활성화
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// 출력 버퍼 정리
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// 이모티콘 제거 함수
function removeEmojis($text) {
    if (!is_string($text)) {
        return $text;
    }
    // 모든 이모티콘 범위 제거
    $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text); // Emoticons
    $text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $text); // Misc Symbols and Pictographs
    $text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $text); // Transport and Map
    $text = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', '', $text); // Flags
    $text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $text);   // Misc symbols
    $text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $text);   // Dingbats
    $text = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $text); // Supplemental Symbols and Pictographs
    $text = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $text); // Symbols and Pictographs Extended-A
    $text = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $text);   // Variation Selectors
    return $text;
}

// JSON 응답 함수
function sendJsonResponse($data) {
    // 출력 버퍼 정리
    if (ob_get_level()) {
        ob_clean();
    }

    // JSON 헤더
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    // JSON 출력
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// 오류 응답 함수
function sendErrorResponse($message = 'An error occurred') {
    sendJsonResponse([
        'success' => false,
        'error' => $message,
        'comprehensive_feedback' => generateDummyResponse(),
        'data_summary' => [
            'teacher_feedback_count' => 0,
            'discussions_count' => 0,
            'surveys_count' => 0,
            'strategies_count' => 0,
            'goals_count' => 0,
            'emotions_count' => 0
        ]
    ]);
}

try {
    // 세션 시작
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }

    // OPTIONS 요청 처리
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        http_response_code(200);
        exit();
    }

    // 입력 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $period = $input['period'] ?? 'week';
    $user_id = $input['user_id'] ?? $_SESSION['user_id'] ?? 2;

    // 한국 시간대 설정
    date_default_timezone_set('Asia/Seoul');

    // 기간 계산
    switch ($period) {
        case 'today':
            $date_from = date('Y-m-d 00:00:00');
            $date_to = date('Y-m-d 23:59:59');
            break;
        case 'week':
            $date_from = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '2weeks':
            $date_from = date('Y-m-d 00:00:00', strtotime('-14 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '3weeks':
            $date_from = date('Y-m-d 00:00:00', strtotime('-21 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '4weeks':
            $date_from = date('Y-m-d 00:00:00', strtotime('-28 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '3months':
            $date_from = date('Y-m-d 00:00:00', strtotime('-3 months'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        default:
            $date_from = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
    }

    $timestamp_from = strtotime($date_from);
    $timestamp_to = strtotime($date_to);

    // Moodle 연결 및 데이터베이스 설정
    try {
        // Moodle config 로드
        $moodle_config_paths = [
            '/home/moodle/public_html/moodle/config.php',
            '/var/www/html/moodle/config.php',
            dirname(__DIR__) . '/../../../config.php'
        ];

        $moodle_loaded = false;
        foreach ($moodle_config_paths as $path) {
            if (@file_exists($path)) {
                @require_once($path);
                $moodle_loaded = true;
                break;
            }
        }

        if ($moodle_loaded && isset($CFG)) {
            // Moodle DB 설정 사용
            $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else {
            // Fallback 연결
            $dsn = "mysql:host=58.180.27.46;dbname=mathking;charset=utf8mb4";
            $pdo = new PDO($dsn, 'moodle', '@MCtrigd7128', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            // Fallback prefix 설정
            if (!isset($CFG)) {
                $CFG = new stdClass();
                $CFG->prefix = 'mdl_';
            }
        }
    } catch (PDOException $e) {
        sendErrorResponse('Database connection failed');
    }

    // 데이터 수집
    $all_data = [
        'teacher_feedback' => [],
        'teacher_discussions' => [],
        'survey_responses' => [],
        'strategies' => [],
        'goals' => [],
        'emotions' => []
    ];

    // 1. 교사 피드백 (mdl_abessi_stickynotes)
    try {
        $memo_types = ['timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today'];
        $sql = "SELECT type, content, created_at FROM mdl_abessi_stickynotes
                WHERE userid = :userid
                AND type IN ('" . implode("','", $memo_types) . "')
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userid' => $user_id]);
        $teacher_feedback = $stmt->fetchAll();

        // 기간 필터링 및 이모티콘 제거
        $filtered_feedback = [];
        foreach ($teacher_feedback as $feedback) {
            $created = is_numeric($feedback['created_at']) ? $feedback['created_at'] : strtotime($feedback['created_at']);
            if ($created >= $timestamp_from && $created <= $timestamp_to) {
                $filtered_feedback[] = [
                    'type' => $feedback['type'],
                    'content' => removeEmojis($feedback['content']),
                    'date' => date('Y-m-d', $created)
                ];
            }
        }
        $all_data['teacher_feedback'] = $filtered_feedback;
    } catch (Exception $e) {
        $all_data['teacher_feedback'] = [];
    }

    // 2. 교사 간 토론 (mdl_alt42g_teacher_discussions)
    try {
        $sql = "SELECT discussion_content, created_at FROM mdl_alt42g_teacher_discussions
                WHERE student_id = :student_id
                AND created_at >= :date_from
                AND created_at <= :date_to
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'student_id' => $user_id,
            'date_from' => $timestamp_from,
            'date_to' => $timestamp_to
        ]);
        $discussions = $stmt->fetchAll();
        $all_data['teacher_discussions'] = array_map(function($d) {
            return [
                'content' => removeEmojis($d['discussion_content']),
                'date' => date('Y-m-d', $d['created_at'])
            ];
        }, $discussions);
    } catch (Exception $e) {
        $all_data['teacher_discussions'] = [];
    }

    // 3. 설문 데이터 (alt42g_activity_selections) - 실제 설문 응답 데이터
    try {
        $sql = "SELECT main_activity, sub_activity, survey_responses, timecreated, survey_submitted_at
                FROM {$CFG->prefix}alt42g_activity_selections
                WHERE userid = :user_id
                AND survey_responses IS NOT NULL
                AND survey_responses != ''
                AND timecreated >= :timestamp_from
                AND timecreated <= :timestamp_to
                ORDER BY timecreated DESC LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        ]);
        $surveys = $stmt->fetchAll();
        $all_data['survey_responses'] = [];

        foreach ($surveys as $s) {
            $survey_data = json_decode($s['survey_responses'], true);
            if ($survey_data) {
                // JSON 설문 응답을 평문으로 변환
                foreach ($survey_data as $question => $answers) {
                    $answer_text = is_array($answers) ? implode(', ', array_column($answers, 'text')) : $answers;
                    $all_data['survey_responses'][] = [
                        'question' => removeEmojis($question),
                        'value' => 0,
                        'text' => removeEmojis($answer_text),
                        'activity' => $s['main_activity'] . ' > ' . $s['sub_activity'],
                        'date' => date('Y-m-d H:i:s', $s['timecreated'])
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $all_data['survey_responses'] = [];
    }

    // 4. 전략 데이터 (alt42g_exam_strategies) - 시험 전략 생성 데이터
    try {
        $sql = "SELECT exam_timeline, strategy_summary, generated_strategy, timecreated
                FROM {$CFG->prefix}alt42g_exam_strategies
                WHERE userid = :user_id
                AND timecreated >= :timestamp_from
                AND timecreated <= :timestamp_to
                ORDER BY timecreated DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        ]);
        $strategies = $stmt->fetchAll();
        $all_data['strategies'] = array_map(function($s) {
            return [
                'type' => 'exam_strategy',
                'name' => removeEmojis($s['exam_timeline'] ?? ''),
                'description' => removeEmojis($s['strategy_summary'] ?? ''),
                'effectiveness' => 0,
                'date' => date('Y-m-d H:i:s', $s['timecreated'])
            ];
        }, $strategies);
    } catch (Exception $e) {
        $all_data['strategies'] = [];
    }

    // 5. 목표 분석 데이터 (alt42g_goal_analysis) - 목표 분석 결과
    try {
        $sql = "SELECT analysis_type, analysis_result, effectiveness_score, timecreated
                FROM {$CFG->prefix}alt42g_goal_analysis
                WHERE userid = :user_id
                AND timecreated >= :timestamp_from
                AND timecreated <= :timestamp_to
                ORDER BY timecreated DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        ]);
        $goals = $stmt->fetchAll();
        $all_data['goals'] = array_map(function($g) {
            return [
                'type' => $g['analysis_type'] ?? '',
                'text' => removeEmojis(mb_substr($g['analysis_result'] ?? '', 0, 100) . '...'),
                'progress' => $g['effectiveness_score'] ?? 0,
                'status' => 'analyzed',
                'date' => date('Y-m-d H:i:s', $g['timecreated'])
            ];
        }, $goals);
    } catch (Exception $e) {
        $all_data['goals'] = [];
    }

    // 6. 감정 데이터 (alt42g_emotion_selections, alt42g_emotion_surveys) - 감정 선택 및 설문
    try {
        $sql = "SELECT es.main_emotion, es.sub_emotion, es.timecreated,
                       ec.category_name, ei.item_name,
                       srv.emotion_state, srv.motivation_factors, srv.stress_factors
                FROM {$CFG->prefix}alt42g_emotion_selections es
                LEFT JOIN {$CFG->prefix}alt42g_emotion_categories ec ON es.category_id = ec.id
                LEFT JOIN {$CFG->prefix}alt42g_emotion_items ei ON es.item_id = ei.id
                LEFT JOIN {$CFG->prefix}alt42g_emotion_surveys srv ON srv.selection_id = es.id
                WHERE es.userid = :user_id
                AND es.timecreated >= :timestamp_from
                AND es.timecreated <= :timestamp_to
                ORDER BY es.timecreated DESC LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        ]);
        $emotions = $stmt->fetchAll();
        $all_data['emotions'] = array_map(function($e) {
            $emotion_state = json_decode($e['emotion_state'] ?? '[]', true);
            $context_parts = array_filter([
                $e['category_name'],
                $e['item_name'],
                is_array($emotion_state) ? implode(', ', $emotion_state) : ''
            ]);

            return [
                'type' => removeEmojis($e['main_emotion'] ?? ''),
                'value' => 0,
                'intensity' => 0,
                'context' => removeEmojis(implode(' - ', $context_parts)),
                'date' => date('Y-m-d H:i:s', $e['timecreated'])
            ];
        }, $emotions);
    } catch (Exception $e) {
        $all_data['emotions'] = [];
    }

    // GPT API 호출 준비
    $prompt = buildPrompt($all_data, $period, $user_id);

    // GPT API 호출 (OpenAI API)
    $gpt_response = callGPTAPI($prompt, $all_data);

    // API 소스 확인
    $api_source = 'dummy';
    if (strpos($gpt_response, '꾸준한 노력과 체계적인 학습으로') !== false) {
        // 스마트 더미 응답 패턴
        $api_source = 'smart_dummy';
    } elseif (strpos($gpt_response, '계속해서 좋은 학습 습관을 유지하세요') !== false) {
        // 기본 더미 응답 패턴
        $api_source = 'basic_dummy';
    } else {
        // GPT API 응답으로 추정
        $api_source = 'gpt_api';
    }

    // 성공 응답
    sendJsonResponse([
        'success' => true,
        'period' => $period,
        'user_id' => $user_id,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'comprehensive_feedback' => $gpt_response,
        'api_source' => $api_source, // API 소스 정보 추가
        'data_summary' => [
            'teacher_feedback_count' => count($all_data['teacher_feedback']),
            'discussions_count' => count($all_data['teacher_discussions']),
            'surveys_count' => count($all_data['survey_responses']),
            'strategies_count' => count($all_data['strategies']),
            'goals_count' => count($all_data['goals']),
            'emotions_count' => count($all_data['emotions'])
        ],
        'raw_data' => $all_data // 디버깅용
    ]);

} catch (Exception $e) {
    sendErrorResponse('An error occurred processing your request');
}

/**
 * GPT 프롬프트 생성
 */
function buildPrompt($data, $period, $user_id) {
    $period_korean = [
        'today' => '오늘',
        'week' => '일주일',
        '2weeks' => '2주일',
        '3weeks' => '3주일',
        '4weeks' => '4주일',
        '3months' => '3개월'
    ];

    $period_text = $period_korean[$period] ?? '일주일';
    $prompt = "학생 ID {$user_id}의 {$period_text} 동안의 학습 데이터를 종합 분석하여 상세한 피드백을 제공해주세요.\n";
    $prompt .= "아래의 모든 데이터를 종합적으로 분석하여 학습 상태, 진행 상황, 개선점 등을 구체적으로 설명해주세요.\n";
    $prompt .= "데이터가 없는 항목도 있을 수 있으니, 있는 데이터를 중심으로 분석해주세요.\n\n";

    // 교사 피드백
    if (!empty($data['teacher_feedback'])) {
        $prompt .= "## 교사 피드백 내역\n";
        foreach ($data['teacher_feedback'] as $fb) {
            $prompt .= "- [{$fb['type']}] {$fb['content']} (날짜: {$fb['date']})\n";
        }
        $prompt .= "\n";
    }

    // 교사 간 토론
    if (!empty($data['teacher_discussions'])) {
        $prompt .= "## 교사 간 토론 내용\n";
        foreach ($data['teacher_discussions'] as $disc) {
            $prompt .= "- {$disc['content']} (날짜: {$disc['date']})\n";
        }
        $prompt .= "\n";
    }

    // 설문 응답
    if (!empty($data['survey_responses'])) {
        $prompt .= "## 설문 응답\n";
        foreach ($data['survey_responses'] as $survey) {
            $prompt .= "- Q: {$survey['question']} → A: {$survey['text']} (점수: {$survey['value']})\n";
        }
        $prompt .= "\n";
    }

    // 학습 전략
    if (!empty($data['strategies'])) {
        $prompt .= "## 학습 전략\n";
        foreach ($data['strategies'] as $strategy) {
            $prompt .= "- [{$strategy['type']}] {$strategy['name']}: {$strategy['description']} (효과: {$strategy['effectiveness']})\n";
        }
        $prompt .= "\n";
    }

    // 목표
    if (!empty($data['goals'])) {
        $prompt .= "## 학습 목표\n";
        foreach ($data['goals'] as $goal) {
            $prompt .= "- [{$goal['type']}] {$goal['text']} (진행률: {$goal['progress']}%, 상태: {$goal['status']})\n";
        }
        $prompt .= "\n";
    }

    // 감정 상태
    if (!empty($data['emotions'])) {
        $prompt .= "## 감정 상태\n";
        $emotion_summary = [];
        foreach ($data['emotions'] as $emotion) {
            $emotion_type = $emotion['type'] ?: 'unknown';
            $emotion_summary[$emotion_type] = ($emotion_summary[$emotion_type] ?? 0) + 1;
        }
        foreach ($emotion_summary as $type => $count) {
            $prompt .= "- {$type}: {$count}회\n";
        }
        $prompt .= "\n";
    }

    $prompt .= "\n위 데이터를 바탕으로 종합적인 분석과 피드백을 제공해주세요.\n";
    $prompt .= "다음 내용을 포함해주세요:\n";
    $prompt .= "1. 교사 피드백과 토론 내용을 반영한 학습 상태 평가\n";
    $prompt .= "2. 설문 응답과 감정 데이터를 기반으로 한 학생의 현재 상태\n";
    $prompt .= "3. 학습 전략과 목표 달성 현황 분석\n";
    $prompt .= "4. 구체적인 개선 방안과 실행 가능한 다음 단계\n";
    $prompt .= "\n각 데이터 소스(교사 피드백, 교사 토론, 설문 응답, 학습 전략, 학습 목표, 감정 데이터)를 모두 고려하여 통합적인 분석을 제공해주세요.\n";
    $prompt .= "한국어로 친근하고 격려하는 톤으로 작성해주세요.";

    return $prompt;
}

/**
 * GPT API 호출
 */
function callGPTAPI($prompt, $data = null) {
    // omniui/config.php에서 OpenAI 설정 로드
    $config_paths = [
        '/mnt/c/alt42/omniui/config.php',
        dirname(__DIR__) . '/../omniui/config.php',
        dirname(__DIR__) . '/../../omniui/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/moodle/local/augmented_teacher/alt42/omniui/config.php',
        '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/config.php'
    ];

    $config_loaded = false;
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $config_loaded = true;
            break;
        }
    }

    // 디버그 로그 파일
    $debug_file = __DIR__ . '/gpt_debug.log';

    // 디버깅 정보 추가
    @file_put_contents($debug_file, "\n" . date('Y-m-d H:i:s') . " - callGPTAPI 함수 호출됨\n", FILE_APPEND);
    @file_put_contents($debug_file, "Config 로드됨: " . ($config_loaded ? "성공" : "실패") . "\n", FILE_APPEND);

    // API 키 상태 확인
    if (defined('OPENAI_API_KEY')) {
        $key_preview = substr(OPENAI_API_KEY, 0, 20) . '...';
        @file_put_contents($debug_file, "API 키 정의됨: $key_preview\n", FILE_APPEND);
    } else {
        @file_put_contents($debug_file, "API 키가 정의되지 않음!\n", FILE_APPEND);
    }

    // OpenAI API 설정 확인 (omniui/config.php에서 가져옴)
    if ($config_loaded && defined('OPENAI_API_KEY') && OPENAI_API_KEY !== 'sk-YOUR-API-KEY-HERE' && !empty(OPENAI_API_KEY)) {
        @file_put_contents($debug_file, date('Y-m-d H:i:s') . " - API 키 유효성 검증 통과\n", FILE_APPEND);

        // OpenAI API 호출
        $api_key = OPENAI_API_KEY;
        $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';
        $max_tokens = defined('OPENAI_MAX_TOKENS') ? OPENAI_MAX_TOKENS : 2000; // 종합 피드백을 위해 토큰 수 증가
        $temperature = defined('OPENAI_TEMPERATURE') ? OPENAI_TEMPERATURE : 0.7;
        $api_url = defined('OPENAI_API_URL') ? OPENAI_API_URL : 'https://api.openai.com/v1/chat/completions';

        @file_put_contents($debug_file, "모델: $model, URL: $api_url\n", FILE_APPEND);

        $request_data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '당신은 한국의 중고등학생들의 학습을 돕는 전문적인 교육 AI 어시스턴트입니다. 학생의 다양한 학습 데이터(교사 피드백, 설문 응답, 학습 전략, 감정 상태 등)를 종합 분석하여 개인화된 피드백을 제공해야 합니다. 다음 지침을 따라주세요:

1. 제공된 모든 데이터를 면밀히 분석하고 연관성을 찾아 종합적인 인사이트 도출
2. 학생의 강점과 개선 필요 영역을 구체적으로 식별
3. 실행 가능한 구체적인 학습 전략과 개선 방안 제시
4. 감정적 지지와 격려를 포함한 따뜻하고 친근한 톤 사용
5. 한국 교육 환경에 맞는 현실적이고 실용적인 조언 제공
6. 마크다운 형식으로 구조화된 응답 작성

응답은 한국어로 작성하며, 학생이 이해하기 쉽고 동기부여가 되도록 해주세요.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];

        // cURL 사용 (더 안정적)
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 종합 분석을 위해 타임아웃 증가
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        @file_put_contents($debug_file, "HTTP 코드: $http_code\n", FILE_APPEND);

        if ($curl_error) {
            @file_put_contents($debug_file, "cURL 오류: $curl_error\n", FILE_APPEND);
        } elseif ($response !== false) {
            @file_put_contents($debug_file, "응답 받음. 원본 응답 길이: " . strlen($response) . "\n", FILE_APPEND);

            // JSON 파싱 시도
            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                @file_put_contents($debug_file, "JSON 파싱 오류: " . json_last_error_msg() . "\n", FILE_APPEND);
                @file_put_contents($debug_file, "원본 응답 일부: " . substr($response, 0, 500) . "\n", FILE_APPEND);
            } else if (isset($result['choices'][0]['message']['content'])) {
                $gpt_response = $result['choices'][0]['message']['content'];
                @file_put_contents($debug_file, "✅ GPT API 성공\n응답 길이: " . strlen($gpt_response) . "자\n", FILE_APPEND);
                @file_put_contents($debug_file, "응답 미리보기: " . substr($gpt_response, 0, 200) . "...\n", FILE_APPEND);
                return $gpt_response;
            } elseif (isset($result['error'])) {
                @file_put_contents($debug_file, "❌ API 오류: " . json_encode($result['error'], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            } else {
                @file_put_contents($debug_file, "예상치 못한 응답 형식\n", FILE_APPEND);
                @file_put_contents($debug_file, "응답 구조: " . json_encode(array_keys($result), JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            }
        } else {
            @file_put_contents($debug_file, "응답을 받지 못함\n", FILE_APPEND);
        }
    } else {
        @file_put_contents($debug_file, date('Y-m-d H:i:s') . " - API 키 없음 또는 더미 키, 스마트 더미 응답 사용\n", FILE_APPEND);
    }

    // API가 실패하거나 사용 불가능한 경우 스마트 더미 응답 생성
    if ($data !== null) {
        return generateSmartDummyResponse($data);
    }

    // 기본 더미 응답
    return generateDummyResponse();
}

/**
 * 스마트 더미 응답 생성 (실제 데이터 기반)
 */
function generateSmartDummyResponse($data) {
    $feedback = "## 종합 학습 피드백 분석\n\n";

    // 데이터 카운트
    $fb_count = count($data['teacher_feedback'] ?? []);
    $disc_count = count($data['teacher_discussions'] ?? []);
    $survey_count = count($data['survey_responses'] ?? []);
    $strategy_count = count($data['strategies'] ?? []);
    $goal_count = count($data['goals'] ?? []);
    $emotion_count = count($data['emotions'] ?? []);
    $total_items = $fb_count + $disc_count + $survey_count + $strategy_count + $goal_count + $emotion_count;

    // 1. 전반적인 학습 상태
    $feedback .= "### 📊 데이터 기반 학습 현황\n\n";

    if ($total_items > 20) {
        $feedback .= "최근 기간 동안 매우 활발한 학습 활동을 보여주고 있습니다. ";
        $feedback .= "총 {$total_items}개의 학습 데이터가 수집되었으며, 이는 꾸준한 노력과 적극적인 참여를 보여줍니다.\n\n";
    } elseif ($total_items > 10) {
        $feedback .= "적절한 수준의 학습 활동을 유지하고 있습니다. ";
        $feedback .= "{$total_items}개의 학습 기록이 있으며, 지속적인 참여가 관찰됩니다.\n\n";
    } else {
        $feedback .= "학습 활동 데이터가 제한적입니다. ";
        $feedback .= "더 적극적인 참여와 다양한 학습 활동이 필요합니다.\n\n";
    }

    // 2. 각 데이터 소스별 분석
    $feedback .= "### 📋 데이터 소스별 상세 분석\n\n";

    // 교사 피드백 분석
    if ($fb_count > 0) {
        $feedback .= "**교사 피드백 ({$fb_count}건)**: ";
        $feedback .= "교사와의 활발한 소통이 이루어지고 있습니다. ";
        if (!empty($data['teacher_feedback'][0])) {
            $latest_fb = $data['teacher_feedback'][0];
            $feedback .= "최근 '{$latest_fb['type']}' 관련 피드백이 있었습니다.\n\n";
        }
    } else {
        $feedback .= "**교사 피드백**: 기록된 피드백이 없습니다. 교사와의 소통을 늘려보세요.\n\n";
    }

    // 교사 토론 분석
    if ($disc_count > 0) {
        $feedback .= "**교사 간 토론 ({$disc_count}건)**: ";
        $feedback .= "여러 교사들이 학생의 학습 상황에 대해 논의하고 있습니다.\n\n";
    } else {
        $feedback .= "**교사 간 토론**: 토론 기록이 없습니다.\n\n";
    }

    // 설문 응답 분석
    if ($survey_count > 0) {
        $feedback .= "**설문 응답 ({$survey_count}건)**: ";
        $feedback .= "정기적인 설문에 참여하고 있습니다.\n\n";
    } else {
        $feedback .= "**설문 응답**: 설문 참여 기록이 없습니다.\n\n";
    }

    // 학습 전략 분석
    if ($strategy_count > 0) {
        $feedback .= "**학습 전략 ({$strategy_count}건)**: ";
        $feedback .= "다양한 학습 전략을 시도하고 있습니다.\n\n";
    } else {
        $feedback .= "**학습 전략**: 등록된 학습 전략이 없습니다.\n\n";
    }

    // 학습 목표 분석
    if ($goal_count > 0) {
        $feedback .= "**학습 목표 ({$goal_count}건)**: ";
        $feedback .= "명확한 학습 목표가 설정되어 있습니다.\n\n";
    } else {
        $feedback .= "**학습 목표**: 구체적인 목표 설정이 필요합니다.\n\n";
    }

    // 감정 데이터 분석
    if ($emotion_count > 0) {
        $feedback .= "**감정 상태 ({$emotion_count}건)**: ";
        $feedback .= "감정 변화가 기록되고 있습니다.\n\n";
    } else {
        $feedback .= "**감정 상태**: 감정 기록이 없습니다.\n\n";
    }

    // 3. 종합 권장사항
    $feedback .= "### 💡 종합 권장사항\n\n";

    if ($fb_count == 0) {
        $feedback .= "• 교사와의 정기적인 피드백 시간을 가져보세요\n";
    }

    if ($goal_count < 2) {
        $feedback .= "• 구체적이고 측정 가능한 학습 목표를 설정해보세요\n";
    }

    if ($strategy_count < 2) {
        $feedback .= "• 다양한 학습 전략을 시도해보세요\n";
    }

    if ($survey_count == 0) {
        $feedback .= "• 설문에 참여하여 자신의 학습 상태를 점검해보세요\n";
    }

    if ($emotion_count == 0) {
        $feedback .= "• 학습 과정에서의 감정 변화를 기록해보세요\n";
    }

    $feedback .= "• 정기적인 자기 성찰 시간을 가져보세요\n";
    $feedback .= "• 동료 학습자들과 학습 경험을 공유해보세요\n\n";

    // 4. 학습 패턴 분석
    $feedback .= "### 4. 학습 패턴 분석\n";

    // 교사 피드백 내용 분석
    if (!empty($data['teacher_feedback'])) {
        $types = array_column($data['teacher_feedback'], 'type');
        $type_counts = array_count_values($types);

        $feedback .= "주로 활용하는 학습 도구:\n";
        foreach ($type_counts as $type => $count) {
            $type_korean = [
                'timescaffolding' => '시간 관리',
                'chapter' => '단원 학습',
                'edittoday' => '오늘의 수정',
                'mystudy' => '나의 학습',
                'today' => '오늘의 학습'
            ];
            $type_name = $type_korean[$type] ?? $type;
            $feedback .= "- {$type_name}: {$count}회\n";
        }
    }

    $feedback .= "\n";

    // 5. 추천 학습 전략
    $feedback .= "### 5. 추천 학습 전략\n";

    if ($total_items < 10) {
        $feedback .= "- 매일 최소 30분 이상 학습 시간 확보\n";
        $feedback .= "- 학습 일지 작성으로 진도 관리\n";
    }

    if (count($data['strategies']) < 3) {
        $feedback .= "- 포모도로 기법 활용하여 집중력 향상\n";
        $feedback .= "- 마인드맵을 통한 개념 정리\n";
    }

    if (count($data['goals']) < 3) {
        $feedback .= "- SMART 목표 설정법 활용\n";
        $feedback .= "- 주간 및 월간 목표 수립\n";
    }

    $feedback .= "- 동료 학습을 통한 상호 피드백\n";
    $feedback .= "- 정기적인 복습 스케줄 수립\n\n";

    // 6. 다음 단계 계획
    $feedback .= "### 6. 다음 단계 계획\n";
    $feedback .= "1. 이번 주 3가지 핵심 학습 목표 설정\n";
    $feedback .= "2. 매일 학습 시작 전 5분 계획 수립\n";
    $feedback .= "3. 주 2회 이상 교사 피드백 요청\n";
    $feedback .= "4. 주말에 한 주 학습 내용 정리 및 복습\n";
    $feedback .= "5. 다음 주 학습 계획 미리 수립\n\n";

    $feedback .= "꾸준한 노력과 체계적인 학습으로 목표를 달성할 수 있습니다. 화이팅!";

    return $feedback;
}

/**
 * 더미 응답 생성 (GPT API가 없을 때)
 */
function generateDummyResponse() {
    $responses = [
        "## 종합 학습 피드백\n\n" .
        "### 1. 전반적인 학습 상태\n" .
        "최근 기간 동안 꾸준한 학습 활동을 보여주고 있습니다. 특히 목표 설정과 실행 면에서 긍정적인 발전이 관찰됩니다.\n\n" .
        "### 2. 강점\n" .
        "- 규칙적인 학습 패턴 유지\n" .
        "- 자기주도적 학습 태도\n" .
        "- 다양한 학습 전략 활용\n\n" .
        "### 3. 개선 필요 영역\n" .
        "- 시간 관리 능력 향상 필요\n" .
        "- 복습 주기 단축 권장\n" .
        "- 어려운 개념에 대한 추가 학습 필요\n\n" .
        "### 4. 추천 학습 전략\n" .
        "- 포모도로 기법을 활용한 집중력 향상\n" .
        "- 마인드맵을 통한 개념 정리\n" .
        "- 동료 학습을 통한 상호 피드백\n\n" .
        "### 5. 다음 단계 계획\n" .
        "1. 일일 학습 목표를 구체적으로 설정하기\n" .
        "2. 주간 복습 시간 확보하기\n" .
        "3. 학습 일지 작성으로 자기 성찰하기\n\n" .
        "계속해서 좋은 학습 습관을 유지하세요! 당신의 노력이 반드시 좋은 결과로 이어질 것입니다."
    ];

    return $responses[0];
}
?>