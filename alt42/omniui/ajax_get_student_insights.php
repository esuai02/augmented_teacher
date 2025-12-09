<?php
/**
 * 학생 인사이트 조회 AJAX 핸들러
 * OpenAI API를 활용한 맞춤형 학습 조언 생성
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// 설정 파일 포함
require_once('config.php');

// 사용자 확인
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$insight_type = isset($_GET['type']) ? $_GET['type'] : 'general';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

// 데이터베이스 연결
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $dsn_alt = "mysql:host=" . ALT42T_DB_HOST . ";dbname=" . ALT42T_DB_NAME . ";charset=utf8mb4";
    $pdo_alt = new PDO($dsn_alt, ALT42T_DB_USER, ALT42T_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

/**
 * OpenAI API 호출 함수
 */
function callOpenAI($prompt, $systemPrompt = null) {
    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'your-api-key-here') {
        return [
            'success' => false,
            'message' => 'OpenAI API key not configured'
        ];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ];

    $messages = [];
    if ($systemPrompt) {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];

    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE
    ];

    $ch = curl_init(OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => 'API request failed',
            'http_code' => $httpCode
        ];
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'content' => $result['choices'][0]['message']['content']
        ];
    }

    return [
        'success' => false,
        'message' => 'Invalid API response'
    ];
}

/**
 * 학생 데이터 수집 함수
 */
function collectStudentData($pdo, $pdo_alt, $user_id) {
    $data = [];

    try {
        // 학생 기본 정보
        $stmt = $pdo->prepare("
            SELECT firstname, lastname FROM mdl_user
            WHERE id = ? AND deleted = 0
        ");
        $stmt->execute([$user_id]);
        $data['student'] = $stmt->fetch();

        // 시험 정보
        if ($pdo_alt) {
            $stmt = $pdo_alt->prepare("
                SELECT exam_type, exam_start_date, study_level, exam_scope
                FROM student_exam_settings
                WHERE user_id = ?
                ORDER BY exam_start_date DESC LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $data['exam'] = $stmt->fetch();
        }

        // 최근 학습 활동 통계
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timecreated))) as active_days,
                   COUNT(*) as total_activities,
                   MAX(timecreated) as last_activity
            FROM mdl_abessi_missionlog
            WHERE userid = ? AND timecreated > ?
        ");
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        $stmt->execute([$user_id, $sevenDaysAgo]);
        $data['activity_stats'] = $stmt->fetch();

        // 최근 진행 상황
        $stmt = $pdo->prepare("
            SELECT progress_data FROM mdl_abessi_progress
            WHERE userid = ?
            ORDER BY timecreated DESC LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $progress = $stmt->fetch();
        if ($progress) {
            $data['progress'] = json_decode($progress['progress_data'], true);
        }

        // 오늘의 AI 분석 결과
        $stmt = $pdo->prepare("
            SELECT agent_type, analysis_data, confidence_score
            FROM mdl_abessi_ai_analysis
            WHERE userid = ? AND created_date = ?
            ORDER BY agent_level
            LIMIT 6
        ");
        $stmt->execute([$user_id, date('Y-m-d')]);
        $data['ai_analysis'] = $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Data collection error: " . $e->getMessage());
    }

    return $data;
}

/**
 * 인사이트 생성 함수
 */
function generateInsight($studentData, $insightType) {
    // 학생 데이터를 텍스트로 정리
    $context = "학생 정보:\n";

    if (!empty($studentData['student'])) {
        $context .= "- 이름: " . $studentData['student']['firstname'] . " " . $studentData['student']['lastname'] . "\n";
    }

    if (!empty($studentData['exam'])) {
        $daysUntil = max(0, floor((strtotime($studentData['exam']['exam_start_date']) - time()) / 86400));
        $context .= "- 시험: " . $studentData['exam']['exam_type'] . " (D-$daysUntil)\n";
        $context .= "- 학습 단계: " . $studentData['exam']['study_level'] . "\n";
        $context .= "- 시험 범위: " . $studentData['exam']['exam_scope'] . "\n";
    }

    if (!empty($studentData['activity_stats'])) {
        $context .= "- 최근 7일 활동: " . $studentData['activity_stats']['active_days'] . "일 활동, ";
        $context .= "총 " . $studentData['activity_stats']['total_activities'] . "회 학습\n";
    }

    if (!empty($studentData['progress'])) {
        if (isset($studentData['progress']['current_score'])) {
            $context .= "- 현재 점수: " . $studentData['progress']['current_score'] . "점\n";
        }
        if (isset($studentData['progress']['target_score'])) {
            $context .= "- 목표 점수: " . $studentData['progress']['target_score'] . "점\n";
        }
    }

    // AI 분석 결과 요약
    if (!empty($studentData['ai_analysis'])) {
        $context .= "\nAI 분석 요약:\n";
        foreach ($studentData['ai_analysis'] as $analysis) {
            $analysisData = json_decode($analysis['analysis_data'], true);
            $context .= "- " . $analysis['agent_type'] . ": " . ($analysisData['summary'] ?? '') . "\n";
        }
    }

    // 인사이트 타입별 프롬프트 생성
    $prompts = [
        'general' => "위 학생 데이터를 바탕으로 오늘 가장 중요한 학습 조언 3가지를 제시해주세요. 구체적이고 실행 가능한 조언을 주세요.",
        'exam_prep' => "위 학생의 시험 준비 상황을 분석하고, 남은 기간 동안의 효과적인 학습 전략을 제시해주세요.",
        'motivation' => "위 학생의 학습 동기를 높일 수 있는 격려의 메시지와 구체적인 동기부여 방법을 제시해주세요.",
        'routine' => "위 학생에게 적합한 '시그니처 루틴'을 제안해주세요. 현재 문제점과 개선 방법을 포함해주세요.",
        'weakness' => "위 학생 데이터를 분석하여 취약점을 파악하고, 이를 개선하기 위한 구체적인 방법을 제시해주세요."
    ];

    $prompt = $context . "\n\n" . ($prompts[$insightType] ?? $prompts['general']);

    // 시스템 프롬프트
    $systemPrompt = "당신은 한국 중학생들의 수학 학습을 돕는 전문 AI 튜터입니다.
학생의 현재 상황을 정확히 파악하고, 실질적이고 구체적인 조언을 제공합니다.
응답은 친근하고 격려적인 톤으로 작성하되, 이모지를 적절히 사용합니다.
모든 조언은 즉시 실행 가능한 구체적인 행동으로 제시합니다.";

    // OpenAI API 호출
    $apiResponse = callOpenAI($prompt, $systemPrompt);

    if ($apiResponse['success']) {
        return [
            'success' => true,
            'insight' => $apiResponse['content'],
            'type' => $insightType,
            'context' => $context
        ];
    } else {
        // API 실패 시 기본 인사이트 제공
        return generateDefaultInsight($studentData, $insightType);
    }
}

/**
 * 기본 인사이트 생성 (OpenAI API 실패 시)
 */
function generateDefaultInsight($studentData, $insightType) {
    $insights = [
        'general' => "📚 오늘의 학습 포인트:\n\n" .
                     "1. **30초 사고 루틴 실천하기** - 문제를 보고 바로 풀지 말고 30초 동안 전략을 생각해보세요.\n" .
                     "2. **취약 단원 집중 공략** - 가장 자신 없는 단원을 하루에 30분씩 집중 학습하세요.\n" .
                     "3. **오답노트 작성** - 오늘 틀린 문제를 정리하고 왜 틀렸는지 분석해보세요.",

        'exam_prep' => "🎯 시험 대비 전략:\n\n" .
                       "• **D-7 이내**: 새로운 내용보다 기출문제와 오답 복습에 집중\n" .
                       "• **D-14 이내**: 취약 단원 집중 공략 + 실전 모의고사\n" .
                       "• **D-30 이내**: 전체 범위 1회독 + 핵심 개념 정리",

        'motivation' => "💪 힘내세요!\n\n" .
                        "작은 성취가 모여 큰 성장이 됩니다. 오늘 한 문제라도 더 이해했다면, 그것만으로도 충분히 잘하고 있는 거예요!\n\n" .
                        "**오늘의 도전**: 가장 어려워 보이는 문제 하나를 골라 도전해보세요. 못 풀어도 괜찮아요. 도전했다는 것 자체가 성장입니다!",

        'routine' => "🏆 추천 시그니처 루틴:\n\n" .
                     "**'30초 사고 마스터' 루틴**\n" .
                     "1. 문제 읽기 (10초) - 핵심 키워드 찾기\n" .
                     "2. 멈추고 생각 (30초) - 관련 개념 3개 떠올리기\n" .
                     "3. 전략 선택 (10초) - 풀이 방법 결정\n" .
                     "4. 실행 (남은 시간) - 차근차근 풀어가기",

        'weakness' => "🔍 취약점 분석:\n\n" .
                      "• **주요 취약점**: 문제 읽기 속도와 이해력\n" .
                      "• **개선 방법**: \n" .
                      "  1. 문제를 소리내어 읽기\n" .
                      "  2. 핵심 단어에 밑줄 긋기\n" .
                      "  3. 문제를 다시 말로 설명해보기"
    ];

    return [
        'success' => true,
        'insight' => $insights[$insightType] ?? $insights['general'],
        'type' => $insightType,
        'source' => 'default'
    ];
}

// 메인 처리
try {
    // 캐시된 인사이트 확인 (1시간 유효)
    $stmt = $pdo->prepare("
        SELECT insight_data, timecreated
        FROM mdl_abessi_learning_insights
        WHERE userid = ? AND insight_type = ?
        AND is_active = 1 AND timeexpired > ?
        ORDER BY timecreated DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $insight_type, time()]);
    $cached = $stmt->fetch();

    if ($cached && (time() - $cached['timecreated']) < 3600) {
        // 캐시된 인사이트 반환
        $insightData = json_decode($cached['insight_data'], true);

        // 조회수 증가
        $stmt = $pdo->prepare("
            UPDATE mdl_abessi_learning_insights
            SET view_count = view_count + 1
            WHERE userid = ? AND insight_type = ? AND timecreated = ?
        ");
        $stmt->execute([$user_id, $insight_type, $cached['timecreated']]);

        echo json_encode([
            'success' => true,
            'data' => $insightData,
            'cached' => true
        ]);
        exit;
    }

    // 새로운 인사이트 생성
    $studentData = collectStudentData($pdo, $pdo_alt, $user_id);
    $insight = generateInsight($studentData, $insight_type);

    if ($insight['success']) {
        // 인사이트 저장
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_learning_insights
            (userid, insight_type, insight_data, generated_by, validity_period,
             is_active, view_count, timecreated, timeexpired)
            VALUES (?, ?, ?, 'ai', 1, 1, 1, ?, ?)
        ");

        $insightData = json_encode($insight);
        $now = time();
        $expireTime = $now + (24 * 60 * 60); // 24시간 후 만료

        $stmt->execute([
            $user_id,
            $insight_type,
            $insightData,
            $now,
            $expireTime
        ]);

        echo json_encode([
            'success' => true,
            'data' => $insight,
            'cached' => false
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate insight'
        ]);
    }

} catch (Exception $e) {
    error_log("Insight generation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error generating insight',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>