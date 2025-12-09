<?php
/**
 * Agent02 Exam Schedule - Chat API (Persona System)
 *
 * D-Day 기반 페르소나 시스템을 사용한 대화 API
 * - 시험 일정 기반 D-Day 계산
 * - 학생 유형 (P1-P6) 감지
 * - 33개 페르소나 매칭 및 맞춤 응답 생성
 *
 * 실행: POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/api/chat.php
 *
 * @package AugmentedTeacher\Agent02\API
 * @version 1.0
 */

// 에러 출력 설정
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 전역 JSON 종료 플래그
$GLOBALS['__json_emitted'] = false;

// 에러 핸들러
set_error_handler(function($severity, $message, $file, $line) {
    error_log("[Agent02 Chat API] PHP error($severity): $message @ $file:$line");
    return true;
});

// 종료 핸들러
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && empty($GLOBALS['__json_emitted'])) {
        if (ob_get_length()) { @ob_clean(); }
        echo json_encode([
            'success' => false,
            'error' => 'fatal: ' . $err['message'],
            'file' => $err['file'] ?? __FILE__,
            'line' => $err['line'] ?? __LINE__
        ]);
    }
});

try {
    // Moodle 환경 로드
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    require_login();

    // 페르소나 시스템 로드
    $personaSystemPath = dirname(__DIR__) . '/persona_system/engine/';
    require_once($personaSystemPath . 'Agent02DataContext.php');
    require_once($personaSystemPath . 'Agent02PersonaRuleEngine.php');

    // POST 데이터 받기
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON 파싱 오류: ' . json_last_error_msg() . ' | ' . __FILE__ . ':' . __LINE__);
    }

    // 필수 파라미터
    $message = trim($input['message'] ?? '');
    $userId = intval($input['user_id'] ?? $USER->id);
    $sessionKey = $input['session_key'] ?? null;
    $examId = intval($input['exam_id'] ?? 0);

    if (empty($message)) {
        throw new Exception('메시지가 비어있습니다 | ' . __FILE__ . ':' . __LINE__);
    }

    // 세션 키 생성/확인
    if (empty($sessionKey)) {
        $sessionKey = 'agent02_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8));
    }

    // 사용자 정보 조회
    $userInfo = $DB->get_record('user', ['id' => $userId], 'id, firstname, lastname, email');
    if (!$userInfo) {
        throw new Exception('사용자를 찾을 수 없습니다 | ' . __FILE__ . ':' . __LINE__);
    }

    // 시험 일정 데이터 조회
    $examData = null;
    $dday = null;

    if ($examId > 0) {
        // 특정 시험 조회
        $examData = $DB->get_record('at_exam_schedules', [
            'id' => $examId,
            'user_id' => $userId,
            'status' => 'active'
        ]);
    } else {
        // 가장 가까운 활성 시험 조회
        $examData = $DB->get_record_sql(
            "SELECT * FROM {at_exam_schedules}
             WHERE user_id = ? AND status = 'active' AND exam_date >= CURDATE()
             ORDER BY exam_date ASC
             LIMIT 1",
            [$userId]
        );
    }

    // D-Day 계산
    if ($examData && !empty($examData->exam_date)) {
        $today = new DateTime('today');
        $examDate = new DateTime($examData->exam_date);
        $diff = $today->diff($examDate);
        $dday = $diff->invert ? -$diff->days : $diff->days;
    }

    // DataContext 구성
    $dataContext = new Agent02DataContext([
        'user_id' => $userId,
        'user_message' => $message,
        'exam_id' => $examData ? $examData->id : null,
        'exam_date' => $examData ? $examData->exam_date : null,
        'dday' => $dday,
        'exam_name' => $examData ? $examData->exam_name : null,
        'exam_subject' => $examData ? $examData->exam_subject : null,
        'exam_type' => $examData ? $examData->exam_type : null,
        'target_score' => $examData ? $examData->target_score : null,
        'current_readiness' => $examData ? $examData->current_readiness : 0,
        'session_key' => $sessionKey
    ]);

    // 페르소나 엔진 초기화 및 실행
    $rulesPath = dirname(__DIR__) . '/persona_system/rules.yaml';
    $engine = new Agent02PersonaRuleEngine($rulesPath);

    // 컨텍스트 데이터 준비
    $context = $dataContext->toArray();

    // 기존 세션 컨텍스트 로드
    $existingSession = $DB->get_record('augmented_teacher_sessions', [
        'session_key' => $sessionKey,
        'agent_id' => 'agent02'
    ]);

    if ($existingSession) {
        $sessionContext = json_decode($existingSession->context_data, true);
        if ($sessionContext) {
            // 이전 세션 데이터 병합
            $context['previous_persona'] = $existingSession->current_persona;
            $context['previous_situation'] = $existingSession->current_situation;
            $context['message_count'] = $existingSession->message_count;
            $context['session_history'] = $sessionContext['history'] ?? [];
        }
    }

    // 페르소나 식별
    $personaResult = $engine->identifyPersona($context);

    // 응답 생성 가이드라인 적용
    $responseGuidelines = $engine->getResponseGuidelines($context);

    // 응답 데이터 구성
    $response = [
        'success' => true,
        'session_key' => $sessionKey,
        'user_id' => $userId,

        // 페르소나 정보
        'persona' => [
            'id' => $personaResult['persona_id'],
            'situation' => $personaResult['situation'],
            'student_type' => $personaResult['student_type'] ?? null,
            'confidence' => $personaResult['confidence'],
            'matched_rule' => $personaResult['matched_rule'] ?? null
        ],

        // D-Day 정보
        'dday_info' => [
            'dday' => $dday,
            'situation' => $dataContext->getSituation(),
            'urgency_level' => $dataContext->getUrgencyLevel(),
            'study_mode' => $dataContext->getStudyMode(),
            'study_ratio' => $dataContext->getStudyRatio()
        ],

        // 시험 정보
        'exam_info' => $examData ? [
            'id' => $examData->id,
            'name' => $examData->exam_name,
            'subject' => $examData->exam_subject,
            'date' => $examData->exam_date,
            'type' => $examData->exam_type,
            'target_score' => $examData->target_score,
            'readiness' => $examData->current_readiness
        ] : null,

        // 응답 가이드라인
        'response_guidelines' => $responseGuidelines,

        // 실행된 액션
        'actions' => $personaResult['actions'] ?? [],

        // 메타데이터
        'metadata' => [
            'processed_at' => date('Y-m-d H:i:s'),
            'message_count' => ($existingSession ? $existingSession->message_count : 0) + 1,
            'is_new_session' => !$existingSession
        ]
    ];

    // AI 응답 생성 (OpenAI API 연동 - 선택적)
    $aiResponse = null;
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
        try {
            $aiResponse = generatePersonaResponse($message, $personaResult, $responseGuidelines, $context);
            $response['ai_response'] = $aiResponse;
        } catch (Exception $e) {
            // AI 응답 실패 시 템플릿 응답 사용
            error_log("[Agent02 Chat] AI 응답 생성 실패: " . $e->getMessage() . " | " . __FILE__ . ":" . __LINE__);
            $response['ai_response'] = generateTemplateResponse($personaResult, $responseGuidelines, $dday);
        }
    } else {
        // API 키 없으면 템플릿 응답
        $response['ai_response'] = generateTemplateResponse($personaResult, $responseGuidelines, $dday);
    }

    // 세션 저장/업데이트
    saveSession($DB, $userId, $sessionKey, $personaResult, $context, $message, $response['ai_response']);

    // 페르소나 식별 로그 저장
    savePersonaLog($DB, $userId, $personaResult, $context);

    // D-Day 스냅샷 저장 (하루 1회)
    saveDdaySnapshot($DB, $userId, $examData, $dday, $personaResult);

    // 응답 출력
    if (ob_get_length()) { ob_clean(); }
    $GLOBALS['__json_emitted'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if (ob_get_length()) { ob_clean(); }
    $GLOBALS['__json_emitted'] = true;
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * AI 기반 페르소나 응답 생성
 */
function generatePersonaResponse($userMessage, $personaResult, $guidelines, $context) {
    // 설정 로드
    @include_once('/home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/config.php');
    if (!defined('OPENAI_API_KEY')) {
        @include_once(__DIR__ . '/../../common/api/gpt_config.php');
    }

    $personaId = $personaResult['persona_id'];
    $situation = $personaResult['situation'];
    $studentType = $personaResult['student_type'] ?? 'unknown';
    $dday = $context['dday'] ?? null;

    // 시스템 프롬프트 구성
    $systemPrompt = buildSystemPrompt($personaId, $situation, $studentType, $guidelines, $dday);

    // 컨텍스트 정보 추가
    $contextInfo = buildContextInfo($context);

    $data = [
        'model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $contextInfo . "\n\n학생 메시지: " . $userMessage]
        ],
        'max_tokens' => defined('OPENAI_MAX_TOKENS') ? OPENAI_MAX_TOKENS : 800,
        'temperature' => 0.7
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, defined('OPENAI_API_URL') ? OPENAI_API_URL : 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('OpenAI API 호출 실패: HTTP ' . $httpCode);
    }

    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('OpenAI API 응답 형식 오류');
    }

    return [
        'content' => $result['choices'][0]['message']['content'],
        'model' => $data['model'],
        'generated_by' => 'openai'
    ];
}

/**
 * 시스템 프롬프트 구성
 */
function buildSystemPrompt($personaId, $situation, $studentType, $guidelines, $dday) {
    $tone = $guidelines['tone'] ?? 'Warm';
    $pace = $guidelines['pace'] ?? 'normal';
    $interventionType = $guidelines['intervention_type'] ?? 'InformationProvision';
    $responseStyle = $guidelines['response_style'] ?? 'conversational';

    // 학생 유형별 특성
    $studentTypeTraits = [
        'P1' => '계획형 학생 - 체계적이고 구조화된 정보 선호, 구체적 일정과 목표 제시',
        'P2' => '불안형 학생 - 정서적 안정감 우선, 작은 성공 경험 강조, 불안 완화',
        'P3' => '회피형 학생 - 작은 시작점 제시, 부담 낮추기, 성취감 유도',
        'P4' => '자신감과잉 학생 - 현실적 피드백, 객관적 데이터 기반 조언',
        'P5' => '혼란형 학생 - 명확한 방향 제시, 단순화된 가이드, 우선순위 정리',
        'P6' => '외부의존형 학생 - 점진적 자립심 훈련, 자기결정 유도'
    ];

    // 상황별 특성
    $situationTraits = [
        'D_URGENT' => "D-Day 3일 이하 긴급 상황. 핵심만 집중, 즉각 실행 가능한 조언 제공. 불안 관리 병행.",
        'D_BALANCED' => "D-Day 4-10일 균형 학습 기간. 개념과 문제풀이 균형, 실전 연습 시작.",
        'D_CONCEPT' => "D-Day 11-30일 개념 중심 기간. 기초 개념 확립, 유형 파악, 약점 보완.",
        'D_FOUNDATION' => "D-Day 31일 이상 기초 기간. 여유있는 계획 수립, 학습 습관 형성, 장기 전략.",
        'C' => "첫 대화 (Cold Start). 시험 일정 파악, 학생 상태 진단, 관계 형성 우선.",
        'E' => "시험 완료 후. 결과 회고, 다음 시험 준비 연계, 성장 포인트 확인.",
        'Q' => "질문 모드. 구체적이고 명확한 답변, 추가 학습 자료 안내."
    ];

    $studentTrait = $studentTypeTraits[$studentType] ?? '';
    $situationTrait = $situationTraits[$situation] ?? '';

    $ddayText = $dday !== null ? "현재 D-{$dday}" : "시험 일정 미설정";

    $prompt = <<<PROMPT
당신은 시험 준비를 돕는 AI 학습 코치입니다.

[현재 페르소나: {$personaId}]
- 상황: {$situation} ({$ddayText})
- 학생 유형: {$studentType}

[상황 특성]
{$situationTrait}

[학생 유형 특성]
{$studentTrait}

[응답 가이드라인]
- 톤: {$tone}
- 페이스: {$pace}
- 개입 유형: {$interventionType}
- 응답 스타일: {$responseStyle}

[지침]
1. 학생의 감정 상태를 먼저 공감하고 인정합니다.
2. {$situation} 상황에 맞는 구체적이고 실행 가능한 조언을 제공합니다.
3. {$studentType} 유형에 맞게 메시지를 조절합니다.
4. 한국어로 자연스럽고 친근하게 응답합니다.
5. 응답은 200-400자 내외로 간결하게 유지합니다.
6. 필요시 다음 행동을 명확하게 제안합니다.
PROMPT;

    return $prompt;
}

/**
 * 컨텍스트 정보 구성
 */
function buildContextInfo($context) {
    $info = [];

    if (!empty($context['exam_name'])) {
        $info[] = "시험: " . $context['exam_name'];
    }
    if (!empty($context['exam_subject'])) {
        $info[] = "과목: " . $context['exam_subject'];
    }
    if (!empty($context['exam_date'])) {
        $info[] = "시험일: " . $context['exam_date'];
    }
    if ($context['dday'] !== null) {
        $info[] = "D-Day: D-" . max(0, $context['dday']);
    }
    if (!empty($context['target_score'])) {
        $info[] = "목표점수: " . $context['target_score'] . "점";
    }
    if (isset($context['current_readiness'])) {
        $info[] = "현재 준비도: " . $context['current_readiness'] . "%";
    }

    if (empty($info)) {
        return "[학생 컨텍스트 정보 없음]";
    }

    return "[학생 컨텍스트]\n" . implode("\n", $info);
}

/**
 * 템플릿 기반 응답 생성 (AI 없이)
 */
function generateTemplateResponse($personaResult, $guidelines, $dday) {
    $situation = $personaResult['situation'];
    $studentType = $personaResult['student_type'] ?? 'P1';

    // 상황별 기본 응답 템플릿
    $templates = [
        'D_URGENT' => [
            'P1' => "지금은 계획대로 핵심만 집중할 때예요. 오늘 가장 중요한 한 가지만 확실히 마무리해보세요. 시간 분배가 관건이에요!",
            'P2' => "긴장되는 마음 충분히 이해해요. 지금까지 준비한 것을 믿으세요. 깊게 숨 쉬고, 아는 것 위주로 한 번 더 정리하면 돼요.",
            'P3' => "조금 부담스럽죠? 괜찮아요. 10분만 집중해서 가장 자신있는 부분 하나 복습해보세요. 작게 시작하면 돼요.",
            'P4' => "자신감은 좋지만, 마지막까지 방심하지 말고 취약한 부분 한 번 더 점검해보세요.",
            'P5' => "지금은 새로운 것보다 아는 것 정리가 중요해요. 가장 중요한 세 가지만 메모해보세요.",
            'P6' => "스스로 할 수 있어요! 제가 방향만 알려줄게요. 지금 가장 해야 할 건 뭘까요?"
        ],
        'D_BALANCED' => [
            'P1' => "균형잡힌 학습 기간이에요. 개념 정리 40%, 문제 풀이 60% 비율로 진행해보세요.",
            'P2' => "차분하게 진행하면 충분히 할 수 있어요. 매일 조금씩 꾸준히 하는 게 가장 좋아요.",
            'P3' => "지금 시작하면 여유있게 준비할 수 있어요. 오늘 15분만 투자해보는 건 어때요?",
            'P4' => "아직 시간이 있지만 방심은 금물! 실전 연습을 시작해보세요.",
            'P5' => "핵심 개념 위주로 정리하고, 모르는 부분은 표시해두세요. 하나씩 해결해나가면 돼요.",
            'P6' => "스스로 학습 계획을 세워보세요. 막히면 언제든 도움을 요청해도 좋아요."
        ],
        'D_CONCEPT' => [
            'default' => "지금은 기초 개념을 탄탄히 다질 시간이에요. 개념 이해에 집중하고, 기본 문제로 확인해보세요."
        ],
        'D_FOUNDATION' => [
            'default' => "여유있게 학습 습관을 만들어가세요. 매일 일정한 시간에 공부하는 루틴이 중요해요."
        ],
        'C' => [
            'default' => "안녕하세요! 시험 준비를 도와드릴게요. 어떤 시험을 준비하고 계신가요? 시험 날짜와 과목을 알려주시면 맞춤 전략을 세워드릴게요."
        ],
        'E' => [
            'default' => "시험 수고하셨어요! 결과와 상관없이 노력한 자신을 칭찬해주세요. 이번 경험을 다음 시험에 어떻게 활용할 수 있을까요?"
        ],
        'Q' => [
            'default' => "네, 질문해주세요! 궁금한 점이 있으면 구체적으로 알려주시면 더 정확하게 도움드릴 수 있어요."
        ]
    ];

    $situationTemplates = $templates[$situation] ?? $templates['D_BALANCED'];
    $content = $situationTemplates[$studentType] ?? ($situationTemplates['default'] ?? "무엇을 도와드릴까요?");

    return [
        'content' => $content,
        'model' => 'template',
        'generated_by' => 'template'
    ];
}

/**
 * 세션 저장/업데이트
 */
function saveSession($DB, $userId, $sessionKey, $personaResult, $context, $message, $aiResponse) {
    global $CFG;

    $existingSession = $DB->get_record('augmented_teacher_sessions', [
        'session_key' => $sessionKey,
        'agent_id' => 'agent02'
    ]);

    // 대화 이력 구성
    $history = [];
    if ($existingSession && !empty($existingSession->context_data)) {
        $existingContext = json_decode($existingSession->context_data, true);
        $history = $existingContext['history'] ?? [];
    }

    // 새 대화 추가 (최근 20개만 유지)
    $history[] = [
        'role' => 'user',
        'content' => $message,
        'timestamp' => time()
    ];
    if (!empty($aiResponse['content'])) {
        $history[] = [
            'role' => 'assistant',
            'content' => $aiResponse['content'],
            'persona' => $personaResult['persona_id'],
            'timestamp' => time()
        ];
    }
    $history = array_slice($history, -40); // 최근 40개 메시지만 유지

    $contextData = json_encode([
        'dday' => $context['dday'] ?? null,
        'exam_id' => $context['exam_id'] ?? null,
        'student_type' => $personaResult['student_type'] ?? null,
        'history' => $history
    ], JSON_UNESCAPED_UNICODE);

    if ($existingSession) {
        // 업데이트
        $existingSession->current_situation = $personaResult['situation'];
        $existingSession->current_persona = $personaResult['persona_id'];
        $existingSession->context_data = $contextData;
        $existingSession->message_count = $existingSession->message_count + 1;
        $existingSession->last_message = substr($message, 0, 500);

        $DB->update_record('augmented_teacher_sessions', $existingSession);
    } else {
        // 새로 생성
        $newSession = new stdClass();
        $newSession->user_id = $userId;
        $newSession->agent_id = 'agent02';
        $newSession->session_key = $sessionKey;
        $newSession->current_situation = $personaResult['situation'];
        $newSession->current_persona = $personaResult['persona_id'];
        $newSession->context_data = $contextData;
        $newSession->message_count = 1;
        $newSession->last_message = substr($message, 0, 500);

        $DB->insert_record('augmented_teacher_sessions', $newSession);
    }
}

/**
 * 페르소나 식별 로그 저장
 */
function savePersonaLog($DB, $userId, $personaResult, $context) {
    try {
        $log = new stdClass();
        $log->user_id = $userId;
        $log->agent_id = 'agent02';
        $log->persona_id = $personaResult['persona_id'];
        $log->situation = $personaResult['situation'];
        $log->confidence = $personaResult['confidence'];
        $log->matched_rule = $personaResult['matched_rule'] ?? null;
        $log->context_snapshot = json_encode([
            'dday' => $context['dday'] ?? null,
            'student_type' => $personaResult['student_type'] ?? null,
            'exam_id' => $context['exam_id'] ?? null
        ], JSON_UNESCAPED_UNICODE);

        $DB->insert_record('augmented_teacher_personas', $log);
    } catch (Exception $e) {
        error_log("[Agent02 Chat] 페르소나 로그 저장 실패: " . $e->getMessage() . " | " . __FILE__ . ":" . __LINE__);
    }
}

/**
 * D-Day 스냅샷 저장 (하루 1회)
 */
function saveDdaySnapshot($DB, $userId, $examData, $dday, $personaResult) {
    if (!$examData || $dday === null) {
        return;
    }

    try {
        $today = date('Y-m-d');

        // 오늘 이미 저장된 스냅샷 확인
        $existing = $DB->get_record_sql(
            "SELECT id FROM {at_agent02_dday_snapshots}
             WHERE user_id = ? AND exam_id = ? AND snapshot_date = ?",
            [$userId, $examData->id, $today]
        );

        if ($existing) {
            return; // 이미 저장됨
        }

        $snapshot = new stdClass();
        $snapshot->user_id = $userId;
        $snapshot->exam_id = $examData->id;
        $snapshot->snapshot_date = $today;
        $snapshot->dday = $dday;
        $snapshot->situation = $personaResult['situation'];
        $snapshot->student_type = $personaResult['student_type'] ?? null;
        $snapshot->persona_id = $personaResult['persona_id'];
        $snapshot->readiness_score = $examData->current_readiness ?? null;

        $DB->insert_record('at_agent02_dday_snapshots', $snapshot);
    } catch (Exception $e) {
        error_log("[Agent02 Chat] D-Day 스냅샷 저장 실패: " . $e->getMessage() . " | " . __FILE__ . ":" . __LINE__);
    }
}

/*
 * ============================================
 * API 사용법
 * ============================================
 *
 * [요청]
 * POST /agents/agent02_exam_schedule/api/chat.php
 * Content-Type: application/json
 *
 * {
 *   "message": "시험이 3일 남았는데 불안해요",
 *   "user_id": 123,           // 선택 (기본: 현재 로그인 사용자)
 *   "session_key": "...",     // 선택 (기본: 자동 생성)
 *   "exam_id": 1              // 선택 (기본: 가장 가까운 활성 시험)
 * }
 *
 * [응답]
 * {
 *   "success": true,
 *   "session_key": "agent02_123_...",
 *   "persona": {
 *     "id": "D_URGENT_P2",
 *     "situation": "D_URGENT",
 *     "student_type": "P2",
 *     "confidence": 0.85
 *   },
 *   "dday_info": {
 *     "dday": 3,
 *     "situation": "D_URGENT",
 *     "urgency_level": "critical",
 *     "study_mode": "intensive",
 *     "study_ratio": "20:80"
 *   },
 *   "ai_response": {
 *     "content": "긴장되는 마음 충분히 이해해요...",
 *     "model": "gpt-4o-mini",
 *     "generated_by": "openai"
 *   }
 * }
 */
