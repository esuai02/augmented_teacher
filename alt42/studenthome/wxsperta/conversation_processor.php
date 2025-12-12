<?php
/**
 * Conversation Processor
 * - Standalone UI / agent_chat_api.php에서 호출
 * - 대화 메시지 저장 + 컨텍스트 요약 + WXSPERTA 레이어 추출 저장
 *
 * 주의: 이 파일은 Moodle UI와 무관. 인증은 호출 측(require_login)에서 수행.
 */
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/wxsperta_extractor.php");
require_once(__DIR__ . "/philosophy_constants.php");

function orbit_strpos_any_cp($haystack, $needle) {
    $haystack = (string)$haystack;
    $needle = (string)$needle;
    if ($needle === '') return false;
    if (function_exists('mb_strpos')) return mb_strpos($haystack, $needle);
    return strpos($haystack, $needle);
}

function orbit_substr_any_cp($s, $start, $len) {
    $s = (string)$s;
    if (function_exists('mb_substr')) return mb_substr($s, $start, $len);
    return substr($s, $start, $len);
}

function orbit_generate_conversation_id($session_id, $user_id, $agent_key) {
    // legacy fallback (세션 기반). 장기 스레드는 orbit_get_or_create_conversation_id() 사용.
    $seed = (string)$session_id . '|' . (string)$user_id . '|' . (string)$agent_key;
    return 'c_' . substr(md5($seed), 0, 24);
}

function orbit_random_conversation_id() {
    if (function_exists('random_bytes')) {
        return 'c_' . bin2hex(random_bytes(12));
    }
    return 'c_' . substr(md5(uniqid('', true)), 0, 24);
}

function orbit_get_or_create_conversation_id($session_id, $user_id, $agent_key, $conversation_id = null) {
    global $DB, $CFG;

    $conversation_id = trim((string)$conversation_id);
    if ($conversation_id !== '') return $conversation_id;

    // conversations 테이블이 있으면: 최근 스레드 재개(세션과 무관)
    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $hasConv = in_array('wxsperta_conversations', $tables) || in_array($prefix . 'wxsperta_conversations', $tables);

    if ($hasConv) {
        $latest = $DB->get_record_sql("
            SELECT conversation_id
            FROM {wxsperta_conversations}
            WHERE user_id = ? AND agent_key = ?
            ORDER BY last_updated DESC
            LIMIT 1
        ", [(int)$user_id, (string)$agent_key]);
        if ($latest && trim((string)$latest->conversation_id) !== '') {
            return (string)$latest->conversation_id;
        }
        $cid = orbit_random_conversation_id();
        try { orbit_ensure_conversation_row($cid, $user_id, $agent_key); } catch (Exception $e) {}
        return $cid;
    }

    // fallback: 구버전(세션 기반)
    return orbit_generate_conversation_id($session_id, $user_id, $agent_key);
}

function orbit_ensure_conversation_row($conversation_id, $user_id, $agent_key) {
    global $DB, $CFG;

    // 테이블 존재 확인(prefix 고려)
    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $ok = in_array('wxsperta_conversations', $tables) || in_array($prefix . 'wxsperta_conversations', $tables);
    if (!$ok) return false;

    $existing = $DB->get_record('wxsperta_conversations', ['conversation_id' => $conversation_id]);
    if ($existing) return $existing;

    $c = new stdClass();
    $c->conversation_id = $conversation_id;
    $c->user_id = $user_id;
    $c->agent_key = $agent_key;
    $c->title = null;
    $c->id = $DB->insert_record('wxsperta_conversations', $c);
    return $c;
}

function orbit_ensure_conversation_context($session_id, $user_id, $agent_key, $conversation_id = null) {
    global $DB;

    if (!$conversation_id) {
        $conversation_id = orbit_generate_conversation_id($session_id, $user_id, $agent_key);
    }

    // 1) 새 스키마: conversation_id가 있으면 그것으로 우선 조회
    try {
        $existing = $DB->get_record('wxsperta_conversation_contexts', ['conversation_id' => $conversation_id]);
        if ($existing) return $existing;
    } catch (Exception $e) {
        // 구 스키마(컬럼 없음)일 수 있음 → session_id로 fallback
    }

    // 2) 구 스키마 fallback
    $existing = $DB->get_record('wxsperta_conversation_contexts', ['session_id' => $session_id]);
    if ($existing) return $existing;

    $ctx = new stdClass();
    $ctx->conversation_id = $conversation_id;
    $ctx->session_id = $session_id;
    $ctx->user_id = $user_id;
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

    // 대화방 메타도 있으면 생성(없어도 대화는 계속)
    try { orbit_ensure_conversation_row($conversation_id, $user_id, $agent_key); } catch (Exception $e) {}
    return $ctx;
}

function orbit_append_context_summary($existing_summary, $user_message, $ai_message) {
    $summary = (string)$existing_summary;
    if (strlen($summary) > 3000) {
        $summary = substr($summary, -1500);
    }
    $t = date('Y-m-d H:i');
    $u = orbit_substr_any_cp($user_message, 0, 160);
    $a = orbit_substr_any_cp($ai_message, 0, 160);
    return $summary . "\n[$t] 학생: $u\n[$t] AI: $a\n";
}

function orbit_save_message($session_id, $user_id, $agent_key, $role, $content, $conversation_id = null) {
    global $DB;

    $m = new stdClass();
    if ($conversation_id) $m->conversation_id = $conversation_id;
    $m->session_id = $session_id;
    $m->user_id = $user_id;
    $m->agent_key = $agent_key;
    $m->role = $role;
    $m->content = $content;
    return $DB->insert_record('wxsperta_conversation_messages', $m);
}

function orbit_save_layers($session_id, $user_id, $agent_key, $message_id, $user_message, $ai_message, $conversation_id = null) {
    global $DB;

    [$layers, $err] = wxsperta_extract_layers_from_turn($user_message, $ai_message);
    if ($layers === false) {
        wxsperta_log("Layer extract failed: $err", 'WARNING');
        return [];
    }

    $saved = [];
    foreach ($layers as $layer => $content) {
        $r = new stdClass();
        if ($conversation_id) $r->conversation_id = $conversation_id;
        $r->session_id = $session_id;
        $r->user_id = $user_id;
        $r->agent_key = $agent_key;
        $r->message_id = $message_id;
        $r->layer = $layer;
        $r->layer_content = $content;
        $r->extracted_from = "학생: $user_message\nAI: $ai_message";
        $r->confidence_score = 0.70;
        $r->is_approved = 0;
        $saved[] = $DB->insert_record('wxsperta_conversation_layers', $r);

        // 선택적 승인 생성(worldView/abstraction) - 테이블이 있으면만
        if ($conversation_id && ($layer === 'worldView' || $layer === 'abstraction')) {
            try {
                $tables = $DB->get_tables();
                $prefix = (isset($GLOBALS['CFG']) && isset($GLOBALS['CFG']->prefix) && $GLOBALS['CFG']->prefix) ? $GLOBALS['CFG']->prefix : 'mdl_';
                $hasApproval = in_array('wxsperta_layer_approvals', $tables) || in_array($prefix . 'wxsperta_layer_approvals', $tables);
                if ($hasApproval) {
                    $exists = $DB->get_record('wxsperta_layer_approvals', [
                        'conversation_id' => $conversation_id,
                        'user_id' => $user_id,
                        'agent_key' => $agent_key,
                        'layer' => $layer,
                        'status' => 'pending'
                    ]);
                    if (!$exists) {
                        $ap = new stdClass();
                        $ap->conversation_id = $conversation_id;
                        $ap->session_id = $session_id;
                        $ap->user_id = $user_id;
                        $ap->agent_key = $agent_key;
                        $ap->message_id = $message_id;
                        $ap->layer = $layer;
                        $ap->proposed_text = $content;
                        $ap->status = 'pending';
                        $DB->insert_record('wxsperta_layer_approvals', $ap);
                    }
                }
            } catch (Exception $e) {
                // 승인 생성 실패는 무시(대화는 계속)
            }
        }
    }
    return $saved;
}

/**
 * 핵심 처리: 대화 턴 저장 + 요약 업데이트 + 레이어 추출 저장
 */
function orbit_process_turn($user_id, $agent_key, $user_message, $ai_message, $session_id = null, $conversation_id = null) {
    global $DB, $CFG;

    if (!$session_id) $session_id = session_id();
    $conversation_id = orbit_get_or_create_conversation_id($session_id, $user_id, $agent_key, $conversation_id);

    // 테이블 존재 여부 체크 (설치 전이라도 채팅이 죽지 않게)
    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $need = [
        'wxsperta_conversation_contexts',
        'wxsperta_conversation_messages',
        'wxsperta_conversation_layers',
    ];
    foreach ($need as $t) {
        $prefixed = $prefix . $t;
        if (!in_array($t, $tables) && !in_array($prefixed, $tables)) {
            return [
                'success' => false,
                'error' => "Missing table: {$t} - " . __FILE__ . ":" . __LINE__
            ];
        }
    }

    $ctx = orbit_ensure_conversation_context($session_id, $user_id, $agent_key, $conversation_id);

    // 메시지 저장 (user, assistant)
    $user_msg_id = orbit_save_message($session_id, $user_id, $agent_key, 'user', $user_message, $conversation_id);
    $ai_msg_id = orbit_save_message($session_id, $user_id, $agent_key, 'assistant', $ai_message, $conversation_id);

    // 컨텍스트 요약 업데이트
    $ctx->context_summary = orbit_append_context_summary($ctx->context_summary, $user_message, $ai_message);
    $DB->update_record('wxsperta_conversation_contexts', $ctx);

    // 레이어 추출/저장 (AI 메시지 ID 기준)
    $layer_ids = orbit_save_layers($session_id, $user_id, $agent_key, $ai_msg_id, $user_message, $ai_message, $conversation_id);

    return [
        'success' => true,
        'conversation_id' => $conversation_id,
        'session_id' => $session_id,
        'message_ids' => ['user' => $user_msg_id, 'assistant' => $ai_msg_id],
        'layer_ids' => $layer_ids
    ];
}


