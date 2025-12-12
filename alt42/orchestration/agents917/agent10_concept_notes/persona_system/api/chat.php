<?php
/**
 * Agent10 개념노트 페르소나 채팅 API 엔드포인트
 *
 * POST /api/chat.php
 * - message: 사용자 메시지 (필수)
 * - user_id: 사용자 ID (선택, 미입력시 현재 로그인 사용자)
 * - note_id: 분석 대상 노트 ID (선택)
 * - situation: 상황 코드 N1-N5 (선택, 기본 N1)
 * - ai_enabled: AI 사용 여부 (선택, 기본 false)
 *
 * @package AugmentedTeacher\Agents\Agent10\PersonaSystem
 * @version 1.0
 * @created 2025-12-02
 */

// 현재 파일 정보 (에러 로깅용)
define('AGENT10_API_FILE', __FILE__);
define('AGENT10_API_DIR', __DIR__);

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// CORS 헤더
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청 처리 (테스트/정보용)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['message'])) {
        echo json_encode([
            'success' => true,
            'api' => 'Agent10 Concept Notes Persona Chat API',
            'agent_id' => 'agent10',
            'agent_name' => '개념노트 코칭 에이전트',
            'version' => '1.0',
            'situations' => [
                'N1' => '노트 탐색 시작',
                'N2' => '개념 이해도 분석',
                'N3' => '학습 흐름 해석',
                'N4' => '복습 권장 판단',
                'N5' => '노트 활용 전략'
            ],
            'usage' => [
                'POST' => '/api/chat.php with JSON body {"message": "텍스트", "situation": "N1"}',
                'GET' => '/api/chat.php?message=텍스트&situation=N1 (테스트용)'
            ],
            'parameters' => [
                'message' => '(필수) 사용자 메시지',
                'user_id' => '(선택) 사용자 ID',
                'note_id' => '(선택) 분석할 노트 ID',
                'situation' => '(선택) 상황 코드 N1-N5',
                'ai_enabled' => '(선택) AI 응답 사용 여부'
            ],
            'test_url' => str_replace('/api/chat.php', '/test.php', $_SERVER['REQUEST_URI']),
            'file' => AGENT10_API_FILE
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    // GET 파라미터를 input으로 변환
    $input = $_GET;
}

// 엔진 로드
require_once(AGENT10_API_DIR . '/../engine/Agent10PersonaEngine.php');

// 설정 로드
$config = require(AGENT10_API_DIR . '/../engine/config/agent_config.php');

try {
    // 입력 파싱 (GET이 아닌 경우만)
    if (!isset($input)) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // form-data 폴백
            $input = $_POST;
        }
    }

    // 필수 파라미터 검증
    $message = trim($input['message'] ?? '');
    if (empty($message)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'message 파라미터가 필요합니다',
            'error_location' => AGENT10_API_FILE . ':' . __LINE__
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 사용자 ID 결정
    $userId = (int)($input['user_id'] ?? 0);
    if ($userId <= 0 && isset($USER->id)) {
        $userId = (int)$USER->id;
    }
    if ($userId <= 0) {
        $userId = 1; // 게스트 폴백
    }

    // 상황 코드 검증
    $situation = strtoupper(trim($input['situation'] ?? 'N1'));
    $validSituations = ['N1', 'N2', 'N3', 'N4', 'N5'];
    if (!in_array($situation, $validSituations)) {
        $situation = 'N1'; // 기본값
    }

    // 노트 ID (선택)
    $noteId = isset($input['note_id']) ? (int)$input['note_id'] : null;

    // AI 설정
    $aiEnabled = isset($input['ai_enabled']) ? (bool)$input['ai_enabled'] : false;

    // 노트 메트릭 조회 (노트 ID가 있는 경우)
    $noteMetrics = null;
    if ($noteId && $noteId > 0) {
        $noteMetrics = fetchNoteMetrics($noteId, $userId);
    }

    // 사용자 전체 노트 통계
    $userNoteStats = fetchUserNoteStats($userId);

    // 세션 데이터 구성
    $sessionData = [
        'current_situation' => $situation,
        'current_persona' => $input['persona'] ?? null,
        'note_id' => $noteId,
        'note_metrics' => $noteMetrics,
        'user_note_stats' => $userNoteStats,
        'request_source' => 'api'
    ];

    // 엔진 초기화
    $engineConfig = array_merge($config, [
        'ai_enabled' => $aiEnabled,
        'debug_mode' => (bool)($input['debug'] ?? false)
    ]);

    $engine = new Agent10PersonaEngine($engineConfig);

    // 규칙 로드
    $rulesPath = AGENT10_API_DIR . '/../rules.yaml';
    if (file_exists($rulesPath)) {
        $engine->loadRules($rulesPath);
    }

    // 프로세스 실행
    $result = $engine->process($userId, $message, $sessionData);

    // 노트 컨텍스트 추가
    $result['note_context'] = [
        'note_id' => $noteId,
        'note_metrics' => $noteMetrics,
        'user_stats' => $userNoteStats,
        'situation' => $situation
    ];

    // 응답
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_location' => AGENT10_API_FILE . ':' . __LINE__,
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 특정 노트의 메트릭 조회
 *
 * @param int $noteId 노트 ID
 * @param int $userId 사용자 ID
 * @return array|null 노트 메트릭
 */
function fetchNoteMetrics(int $noteId, int $userId): ?array {
    global $DB;

    try {
        // local_augteacher_notes 테이블에서 조회
        $note = $DB->get_record('local_augteacher_notes', [
            'id' => $noteId,
            'userid' => $userId
        ]);

        if (!$note) {
            return null;
        }

        // 메트릭 계산
        $now = time();
        $daysSinceLastStroke = isset($note->tlaststroke) && $note->tlaststroke > 0
            ? floor(($now - $note->tlaststroke) / 86400)
            : null;

        return [
            'note_id' => $noteId,
            'stroke_count' => (int)($note->nstroke ?? 0),
            'used_time' => (int)($note->usedtime ?? 0),
            'last_stroke_time' => $note->tlaststroke ?? null,
            'days_since_last_stroke' => $daysSinceLastStroke,
            'title' => $note->title ?? '',
            'topic' => $note->topic ?? '',
            'created_at' => $note->timecreated ?? null
        ];
    } catch (Exception $e) {
        error_log("[Agent10 API] fetchNoteMetrics error: " . $e->getMessage() . " at " . AGENT10_API_FILE . ":" . __LINE__);
        return null;
    }
}

/**
 * 사용자의 전체 노트 통계 조회
 *
 * @param int $userId 사용자 ID
 * @return array 사용자 노트 통계
 */
function fetchUserNoteStats(int $userId): array {
    global $DB;

    try {
        $stats = [
            'total_notes' => 0,
            'total_strokes' => 0,
            'total_used_time' => 0,
            'avg_strokes' => 0,
            'avg_used_time' => 0,
            'recent_notes_count' => 0,
            'old_notes_count' => 0,
            'last_note_date' => null,
            'top_topic' => null
        ];

        // 총 노트 수 및 합계
        $sql = "SELECT
                    COUNT(*) as total_notes,
                    COALESCE(SUM(nstroke), 0) as total_strokes,
                    COALESCE(SUM(usedtime), 0) as total_used_time,
                    COALESCE(AVG(nstroke), 0) as avg_strokes,
                    COALESCE(AVG(usedtime), 0) as avg_used_time,
                    MAX(tlaststroke) as last_stroke_time
                FROM {local_augteacher_notes}
                WHERE userid = ?";

        $result = $DB->get_record_sql($sql, [$userId]);

        if ($result) {
            $stats['total_notes'] = (int)$result->total_notes;
            $stats['total_strokes'] = (int)$result->total_strokes;
            $stats['total_used_time'] = (int)$result->total_used_time;
            $stats['avg_strokes'] = round((float)$result->avg_strokes, 2);
            $stats['avg_used_time'] = round((float)$result->avg_used_time, 2);
            $stats['last_note_date'] = $result->last_stroke_time ? date('Y-m-d', $result->last_stroke_time) : null;
        }

        // 최근 7일 노트 수
        $recentThreshold = time() - (7 * 86400);
        $stats['recent_notes_count'] = $DB->count_records_select(
            'local_augteacher_notes',
            "userid = ? AND tlaststroke >= ?",
            [$userId, $recentThreshold]
        );

        // 30일 이상 오래된 노트 수
        $oldThreshold = time() - (30 * 86400);
        $stats['old_notes_count'] = $DB->count_records_select(
            'local_augteacher_notes',
            "userid = ? AND tlaststroke < ? AND tlaststroke > 0",
            [$userId, $oldThreshold]
        );

        // 가장 많은 주제
        $topicSql = "SELECT topic, COUNT(*) as cnt
                     FROM {local_augteacher_notes}
                     WHERE userid = ? AND topic IS NOT NULL AND topic != ''
                     GROUP BY topic
                     ORDER BY cnt DESC
                     LIMIT 1";
        $topTopic = $DB->get_record_sql($topicSql, [$userId]);
        if ($topTopic) {
            $stats['top_topic'] = $topTopic->topic;
        }

        return $stats;

    } catch (Exception $e) {
        error_log("[Agent10 API] fetchUserNoteStats error: " . $e->getMessage() . " at " . AGENT10_API_FILE . ":" . __LINE__);
        return [
            'total_notes' => 0,
            'total_strokes' => 0,
            'total_used_time' => 0,
            'avg_strokes' => 0,
            'avg_used_time' => 0,
            'recent_notes_count' => 0,
            'old_notes_count' => 0,
            'last_note_date' => null,
            'top_topic' => null,
            'error' => $e->getMessage()
        ];
    }
}

/*
 * 사용 예시:
 *
 * 1. 기본 요청:
 * curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent10_concept_notes/persona_system/api/chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "내 노트 분석해줘", "situation": "N2"}'
 *
 * 2. 특정 노트 분석:
 * curl -X POST https://mathking.kr/.../api/chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "이 노트 어떻게 개선하면 좋을까?", "note_id": 123, "situation": "N5"}'
 *
 * 3. 복습 권장 요청:
 * curl -X POST https://mathking.kr/.../api/chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "복습해야 할 노트 알려줘", "situation": "N4"}'
 *
 * 응답 예시:
 * {
 *   "success": true,
 *   "user_id": 123,
 *   "agent_id": "agent10",
 *   "persona": {
 *     "persona_id": "N2_P1",
 *     "persona_name": "깊이 있는 이해자",
 *     "confidence": 0.85,
 *     "tone": "Analytical",
 *     "intervention": "InformationProvision"
 *   },
 *   "response": {
 *     "text": "노트를 분석해 보았습니다...",
 *     "template_id": "concept_understanding",
 *     "tone": "Analytical"
 *   },
 *   "note_context": {
 *     "note_id": 123,
 *     "note_metrics": {
 *       "stroke_count": 150,
 *       "used_time": 1200,
 *       "days_since_last_stroke": 5
 *     },
 *     "user_stats": {
 *       "total_notes": 45,
 *       "avg_strokes": 78.5
 *     },
 *     "situation": "N2"
 *   },
 *   "meta": {
 *     "processing_time_ms": 125.5,
 *     "timestamp": 1701484800
 *   }
 * }
 *
 * 관련 DB 테이블:
 * - local_augteacher_notes
 *   - id: bigint(10) PRIMARY KEY
 *   - userid: bigint(10) NOT NULL
 *   - title: varchar(255)
 *   - topic: varchar(100)
 *   - nstroke: int(10) - 필기 획 수
 *   - tlaststroke: bigint(10) - 마지막 필기 시간
 *   - usedtime: int(10) - 사용 시간 (초)
 *   - timecreated: bigint(10)
 *   - timemodified: bigint(10)
 *
 * - at_agent_persona_state
 *   - id: bigint(10) PRIMARY KEY
 *   - userid: bigint(10) NOT NULL
 *   - agent_id: varchar(50) NOT NULL
 *   - persona_id: varchar(50) NOT NULL
 *   - state_data: longtext
 *   - timecreated: bigint(10)
 *   - timemodified: bigint(10)
 *
 * 파일 정보:
 * - 경로: agent10_concept_notes/persona_system/api/chat.php
 * - 의존: ../engine/Agent10PersonaEngine.php
 * - 설정: ../engine/config/agent_config.php
 */
