<?php
/**
 * Standalone UI API (Moodle UI와 분리된 화면에서 사용)
 * - 인증/DB는 Moodle 세션 그대로 사용 (require_login)
 * - 에러 메시지는 파일 경로 + 라인 번호 포함
 */
include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . "/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_user_state':
            echo json_encode(get_user_state());
            break;

        case 'get_agents':
            echo json_encode(get_agents());
            break;

        case 'create_or_resume_conversation':
            $agent_key = trim((string)($_GET['agent_key'] ?? $_POST['agent_key'] ?? 'global'));
            echo json_encode(create_or_resume_conversation($agent_key));
            break;

        case 'get_conversation_messages':
            $conversation_id = trim((string)($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? ''));
            echo json_encode(get_conversation_messages($conversation_id));
            break;

        case 'get_pending_layer_approvals':
            $agent_key = trim((string)($_GET['agent_key'] ?? $_POST['agent_key'] ?? ''));
            echo json_encode(get_pending_layer_approvals($agent_key));
            break;

        case 'submit_layer_approval':
            $approval_id = (int)($_POST['approval_id'] ?? 0);
            $decision = trim((string)($_POST['decision'] ?? ''));
            $text = trim((string)($_POST['text'] ?? ''));
            echo json_encode(submit_layer_approval($approval_id, $decision, $text));
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action - ' . __FILE__ . ':' . __LINE__
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() . ' - ' . __FILE__ . ':' . __LINE__
    ]);
}
exit;

function get_user_state() {
    global $DB, $USER;

    // role
    $userrole = $DB->get_record_sql(
        "SELECT data FROM mdl_user_info_data where userid=? AND fieldid=?",
        [$USER->id, 22]
    );
    $role = $userrole ? $userrole->data : 'student';

    return [
        'success' => true,
        'user' => [
            'id' => (int)$USER->id,
            'role' => $role
        ]
    ];
}

function get_agents() {
    // UI용 에이전트 목록은 cards_data.php를 소스오브트루스로 사용 (agent_chat_api와 ID 일치)
    global $cards_data;
    if (!isset($cards_data) || !is_array($cards_data)) {
        include_once(__DIR__ . '/ai_agents/cards_data.php');
    }
    if (!isset($cards_data) || !is_array($cards_data)) {
        return [
            'success' => false,
            'error' => 'cards_data load failed - ' . __FILE__ . ':' . __LINE__
        ];
    }

    $agents = array_map(function($card) {
        return [
            'id' => $card['id'],          // 예: 01_time_capsule (문자열 키)
            'number' => $card['number'],  // 1~21
            'name' => $card['name'],
            'icon' => $card['icon'] ?? '🎯',
            'color' => $card['color'] ?? '#666',
            'category' => $card['category'] ?? 'execution',
            'description' => $card['description'] ?? '',
            'subtitle' => $card['subtitle'] ?? '',
            'connections' => $card['connections'] ?? []
        ];
    }, $cards_data);

    // 글로벌 멘토(최상단 고정)
    array_unshift($agents, [
        'id' => 'global',
        'number' => 0,
        'name' => '🌌 마이 궤도(글로벌 멘토)',
        'icon' => '🌌',
        'color' => '#6366f1',
        'category' => 'future_design',
        'description' => '너의 “진짜 나”를 찾는 여정을 같이 걷는 전체 멘토야.',
        'subtitle' => '글로벌 멘토링',
        'connections' => []
    ]);

    return [
        'success' => true,
        'agents' => array_values($agents)
    ];
}

function create_or_resume_conversation($agent_key) {
    global $DB, $USER, $CFG;

    if ($agent_key === '') $agent_key = 'global';
    $session_id = session_id();

    // 테이블 존재 확인
    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $has_ctx = in_array('wxsperta_conversation_contexts', $tables) || in_array($prefix . 'wxsperta_conversation_contexts', $tables);
    $has_conv = in_array('wxsperta_conversations', $tables) || in_array($prefix . 'wxsperta_conversations', $tables);
    if (!$has_ctx) {
        return ['success' => false, 'error' => 'Missing table: wxsperta_conversation_contexts - ' . __FILE__ . ':' . __LINE__];
    }

    // ✅ 장기 스레드: conversations 테이블이 있으면 "최근 스레드 재개", 없으면 임시 conversation_id 생성(최소 동작)
    $conversation_id = '';
    if ($has_conv) {
        $latest = $DB->get_record_sql("
            SELECT conversation_id
            FROM {wxsperta_conversations}
            WHERE user_id = ? AND agent_key = ?
            ORDER BY last_updated DESC
            LIMIT 1
        ", [(int)$USER->id, $agent_key]);
        $conversation_id = $latest ? (string)$latest->conversation_id : '';

        if ($conversation_id === '') {
            $conversation_id = 'c_' . (function_exists('random_bytes') ? bin2hex(random_bytes(12)) : substr(md5(uniqid('', true)), 0, 24));
            $c = new stdClass();
            $c->conversation_id = $conversation_id;
            $c->user_id = (int)$USER->id;
            $c->agent_key = $agent_key;
            $c->title = null;
            $DB->insert_record('wxsperta_conversations', $c);
        }
    } else {
        // conversations 테이블이 없으면 세션 기반 임시 id(호환)
        $conversation_id = 'c_' . substr(md5($session_id . '|' . $USER->id . '|' . $agent_key), 0, 24);
    }

    // context upsert (conversation_id 컬럼이 없을 수 있어 try/catch)
    try {
        $ctx = $DB->get_record('wxsperta_conversation_contexts', ['conversation_id' => $conversation_id]);
        if (!$ctx) {
            $ctx = new stdClass();
            $ctx->conversation_id = $conversation_id;
            $ctx->session_id = $session_id;
            $ctx->user_id = (int)$USER->id;
            $ctx->agent_key = $agent_key;
            $ctx->context_summary = '';
            $ctx->emotion_state = 'neutral';
            $ctx->conversation_phase = 'exploration';
            $ctx->mentoring_year = 1;
            $ctx->self_clarity_score = 0;
            $ctx->direction_confidence = 0;
            $ctx->exploration_breadth = 0;
            $ctx->ai_era_competencies = '';
            $ctx->quantum_state = '';
            $ctx->core_philosophy = orbit_core_philosophy_text();
            $ctx->id = $DB->insert_record('wxsperta_conversation_contexts', $ctx);
        }
    } catch (Exception $e) {
        // 구 스키마면 session_id로만 운영(최소 동작)
        $ctx = $DB->get_record('wxsperta_conversation_contexts', ['session_id' => $session_id]);
    }

    if ($has_conv) {
        // 재개 시 last_updated 갱신을 위해 UPDATE 한 번
        try {
            $c = $DB->get_record('wxsperta_conversations', ['conversation_id' => $conversation_id]);
            if ($c) $DB->update_record('wxsperta_conversations', $c);
        } catch (Exception $e) {}
    }

    return [
        'success' => true,
        'conversation_id' => $conversation_id,
        'agent_key' => $agent_key,
        'session_id' => $session_id
    ];
}

function get_conversation_messages($conversation_id) {
    global $DB, $USER, $CFG;

    if ($conversation_id === '') {
        return ['success' => false, 'error' => 'conversation_id required - ' . __FILE__ . ':' . __LINE__];
    }

    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $has_msg = in_array('wxsperta_conversation_messages', $tables) || in_array($prefix . 'wxsperta_conversation_messages', $tables);
    if (!$has_msg) {
        return ['success' => false, 'error' => 'Missing table: wxsperta_conversation_messages - ' . __FILE__ . ':' . __LINE__];
    }

    // conversation_id 컬럼이 없을 수 있어 fallback
    try {
        $rows = $DB->get_records_sql("
            SELECT id, role, content, created_at
            FROM {wxsperta_conversation_messages}
            WHERE user_id = ? AND conversation_id = ?
            ORDER BY created_at ASC
            LIMIT 200
        ", [(int)$USER->id, $conversation_id]);
    } catch (Exception $e) {
        $rows = [];
    }

    $msgs = array_map(function($r) {
        return [
            'id' => (int)$r->id,
            'role' => (string)$r->role,
            'content' => (string)$r->content,
            'created_at' => (string)$r->created_at
        ];
    }, array_values($rows));

    return ['success' => true, 'messages' => $msgs];
}

function get_pending_layer_approvals($agent_key = '') {
    global $DB, $USER, $CFG;

    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $has = in_array('wxsperta_layer_approvals', $tables) || in_array($prefix . 'wxsperta_layer_approvals', $tables);
    if (!$has) return ['success' => true, 'approvals' => []];

    $params = [(int)$USER->id];
    $where = "user_id = ? AND status = 'pending'";
    if ($agent_key !== '') { $where .= " AND agent_key = ?"; $params[] = $agent_key; }

    $rows = $DB->get_records_sql("
        SELECT id, conversation_id, agent_key, layer, proposed_text, created_at
        FROM {wxsperta_layer_approvals}
        WHERE $where
        ORDER BY created_at DESC
        LIMIT 20
    ", $params);

    $out = array_map(function($r){
        return [
            'id' => (int)$r->id,
            'conversation_id' => (string)$r->conversation_id,
            'agent_key' => (string)$r->agent_key,
            'layer' => (string)$r->layer,
            'proposed_text' => (string)$r->proposed_text,
            'created_at' => (string)$r->created_at
        ];
    }, array_values($rows));

    return ['success' => true, 'approvals' => $out];
}

function submit_layer_approval($approval_id, $decision, $text = '') {
    global $DB, $USER, $CFG;

    $approval_id = (int)$approval_id;
    if ($approval_id <= 0) return ['success' => false, 'error' => 'approval_id required - ' . __FILE__ . ':' . __LINE__];

    $decision = strtolower(trim((string)$decision));
    if (!in_array($decision, ['approved','rejected','skipped'], true)) {
        return ['success' => false, 'error' => 'decision must be approved|rejected|skipped - ' . __FILE__ . ':' . __LINE__];
    }

    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $has = in_array('wxsperta_layer_approvals', $tables) || in_array($prefix . 'wxsperta_layer_approvals', $tables);
    if (!$has) return ['success' => false, 'error' => 'Missing table: wxsperta_layer_approvals - ' . __FILE__ . ':' . __LINE__];

    $row = $DB->get_record('wxsperta_layer_approvals', ['id' => $approval_id, 'user_id' => (int)$USER->id]);
    if (!$row) return ['success' => false, 'error' => 'approval not found - ' . __FILE__ . ':' . __LINE__];

    $row->status = $decision;
    $row->responded_at = date('Y-m-d H:i:s');
    if ($decision === 'approved') {
        $row->approved_text = $text !== '' ? $text : (string)$row->proposed_text;
    }
    $DB->update_record('wxsperta_layer_approvals', $row);

    // ✅ 승인되면 "승인본"을 conversation_layers에 추가 저장해서 뉴런/상태 조회에서 최신으로 잡히게
    if ($decision === 'approved') {
        try {
            $tables2 = $DB->get_tables();
            $prefix2 = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
            $hasLayers = in_array('wxsperta_conversation_layers', $tables2) || in_array($prefix2 . 'wxsperta_conversation_layers', $tables2);
            if ($hasLayers) {
                $r = new stdClass();
                // conversation_id 컬럼이 없을 수 있어 try/catch로 보호
                $r->conversation_id = (string)$row->conversation_id;
                $r->session_id = (string)($row->session_id ?? session_id());
                $r->user_id = (int)$USER->id;
                $r->agent_key = (string)$row->agent_key;
                $r->message_id = (int)($row->message_id ?? 0);
                $r->layer = (string)$row->layer;
                $r->layer_content = (string)$row->approved_text;
                $r->extracted_from = "학생이 확인함(승인): " . date('Y-m-d H:i:s');
                $r->confidence_score = 1.00;
                $r->is_approved = 1;
                $DB->insert_record('wxsperta_conversation_layers', $r);
            }
        } catch (Exception $e) {
            // 승인본 저장 실패는 승인 결과 자체를 막지 않음
        }
    }

    return ['success' => true];
}


