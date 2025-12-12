<?php
/**
 * WXSPERTA Chat Verify (관리자용)
 * - 채팅 저장/레이어 추출이 실제로 DB에 반영되는지 확인
 * - 최근 N분간 메시지/레이어/컨텍스트/기존 interactions 로그 확인
 * - 레이어 JSON 파싱 실패 흔적(로그) 일부 확인
 */
require_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/conversation_processor.php");
require_once(__DIR__ . "/philosophy_constants.php");
global $DB, $USER, $CFG;
require_login();

if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

$post_action = $_POST['action'] ?? '';

$minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 30;
$minutes = max(1, min(24 * 60, $minutes));

$agent_key = isset($_GET['agent_key']) ? trim((string)$_GET['agent_key']) : '';
$session_id = isset($_GET['session_id']) ? trim((string)$_GET['session_id']) : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$USER->id;

$since_ts = time() - ($minutes * 60);
$prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
$tables = $DB->get_tables();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function cut($s, $n = 200) {
    $s = (string)$s;
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($s) > $n ? mb_substr($s, 0, $n) . '…' : $s;
    }
    return strlen($s) > $n ? substr($s, 0, $n) . '…' : $s;
}

function table_exists_any($tables, $prefix, $name) {
    return in_array($name, $tables) || in_array($prefix . $name, $tables);
}

// 테이블 존재 체크
$has_ctx = table_exists_any($tables, $prefix, 'wxsperta_conversation_contexts');
$has_msg = table_exists_any($tables, $prefix, 'wxsperta_conversation_messages');
$has_layers = table_exists_any($tables, $prefix, 'wxsperta_conversation_layers');
$has_interactions = table_exists_any($tables, $prefix, 'wxsperta_interactions');

// ==================== 스모크 테스트 (대화 1턴 생성 → 저장 → 레이어 추출) ====================
$smoke_result = null;
if ($post_action === 'smoketest') {
    $smoke_agent_key = trim((string)($_POST['agent_key'] ?? '01_time_capsule'));
    if ($smoke_agent_key === '') $smoke_agent_key = '01_time_capsule';

    $smoke_user_message = trim((string)($_POST['message'] ?? '요즘 목표가 흔들려. 어디서부터 잡아야 할지 모르겠어.'));
    if ($smoke_user_message === '') $smoke_user_message = '요즘 목표가 흔들려. 어디서부터 잡아야 할지 모르겠어.';

    if (!$has_ctx || !$has_msg || !$has_layers) {
        $smoke_result = [
            'success' => false,
            'error' => 'conversation_* 테이블이 아직 준비되지 않았습니다. - ' . __FILE__ . ':' . __LINE__
        ];
    } else {
        // GPT로 응답 생성
        $core = orbit_core_philosophy_text();
        $system = "너는 학생의 멘토야. 반말로 따뜻하게. 2~4문장. 공감→핵심 1개→질문 1개.\\n\\n[핵심 철학]\\n{$core}";
        $reply = call_openai_api([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $smoke_user_message]
        ], 0.6);

        if ($reply === false) {
            $smoke_result = [
                'success' => false,
                'error' => 'OpenAI 호출 실패 - ' . __FILE__ . ':' . __LINE__
            ];
        } else {
            // 저장 + 레이어 추출
            $save = orbit_process_turn($user_id, $smoke_agent_key, $smoke_user_message, $reply, session_id());
            $smoke_result = [
                'success' => $save['success'] ? true : false,
                'agent_key' => $smoke_agent_key,
                'session_id' => $save['session_id'] ?? session_id(),
                'user_message' => $smoke_user_message,
                'ai_message' => $reply,
                'save_result' => $save
            ];
        }
    }
}

// 최근 컨텍스트 1개
$latest_ctx = null;
if ($has_ctx) {
    $params = [$user_id];
    $sql = "
        SELECT *
        FROM {wxsperta_conversation_contexts}
        WHERE user_id = ?
        ORDER BY last_updated DESC
        LIMIT 1
    ";
    $latest_ctx = $DB->get_record_sql($sql, $params);
}

// 조회 조건 빌더
function build_where_params($since_ts, $user_id, $agent_key, $session_id, $has_created_at = true) {
    $where = [];
    $params = [];

    if ($has_created_at) {
        $where[] = "created_at >= ?";
        $params[] = date('Y-m-d H:i:s', $since_ts); // TIMESTAMP 컬럼 기준
    }
    $where[] = "user_id = ?";
    $params[] = $user_id;
    if ($agent_key !== '') {
        $where[] = "agent_key = ?";
        $params[] = $agent_key;
    }
    if ($session_id !== '') {
        $where[] = "session_id = ?";
        $params[] = $session_id;
    }
    return [$where, $params];
}

// conversation_messages (최근)
$recent_messages = [];
if ($has_msg) {
    [$where, $params] = build_where_params($since_ts, $user_id, $agent_key, $session_id, true);
    $sql = "
        SELECT *
        FROM {wxsperta_conversation_messages}
        WHERE " . implode(" AND ", $where) . "
        ORDER BY created_at DESC
        LIMIT 20
    ";
    $recent_messages = $DB->get_records_sql($sql, $params);
}

// conversation_layers (최근)
$recent_layers = [];
if ($has_layers) {
    [$where, $params] = build_where_params($since_ts, $user_id, $agent_key, $session_id, true);
    $sql = "
        SELECT *
        FROM {wxsperta_conversation_layers}
        WHERE " . implode(" AND ", $where) . "
        ORDER BY created_at DESC
        LIMIT 50
    ";
    $recent_layers = $DB->get_records_sql($sql, $params);
}

// interactions (기존 시스템) — created_at이 TIMESTAMP이므로 필터 가능
$recent_interactions = [];
if ($has_interactions) {
    // interactions는 agent_id(int) 기준이라 agent_key 필터는 생략 (참고용)
    $params = [$user_id, date('Y-m-d H:i:s', $since_ts)];
    $sql = "
        SELECT *
        FROM {wxsperta_interactions}
        WHERE user_id = ? AND created_at >= ?
        ORDER BY created_at DESC
        LIMIT 10
    ";
    $recent_interactions = $DB->get_records_sql($sql, $params);
}

// 레이어 파싱 실패 로그 탐색 (가능한 경우)
$logHints = [];
$logFile = defined('LOG_DIR') ? LOG_DIR . date('Y-m-d') . '.log' : '';
if ($logFile && file_exists($logFile)) {
    $content = @file_get_contents($logFile);
    if ($content !== false) {
        $lines = explode("\n", $content);
        $matches = [];
        foreach (array_reverse($lines) as $line) {
            if (strpos($line, 'Layer extract failed') !== false || strpos($line, 'JSON 파싱 실패') !== false) {
                $matches[] = $line;
                if (count($matches) >= 10) break;
            }
        }
        $logHints = array_reverse($matches);
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>WXSPERTA Chat Verify</title>
  <style>
    body { font-family: system-ui, -apple-system, "Noto Sans KR", sans-serif; margin: 0; padding: 20px; background: #0b1020; color: #e5e7eb; }
    h1 { margin: 0 0 12px; font-size: 20px; }
    .muted { color: rgba(229,231,235,0.7); font-size: 12px; }
    .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 14px; margin: 12px 0; }
    .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
    label { font-size: 12px; color: rgba(229,231,235,0.8); display:block; margin-bottom: 6px; }
    input { padding: 10px 10px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: rgba(0,0,0,0.25); color: #e5e7eb; width: 220px; }
    button { padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(99,102,241,0.5); background: rgba(99,102,241,0.15); color:#e5e7eb; cursor:pointer; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border-bottom: 1px solid rgba(255,255,255,0.08); padding: 8px; text-align: left; vertical-align: top; }
    th { color: rgba(229,231,235,0.85); font-weight: 600; }
    .ok { color: #10b981; font-weight: 700; }
    .bad { color: #ef4444; font-weight: 700; }
    code { background: rgba(0,0,0,0.25); padding: 2px 6px; border-radius: 6px; }
    pre { background: rgba(0,0,0,0.25); padding: 10px; border-radius: 10px; overflow: auto; }
  </style>
</head>
<body>
  <h1>WXSPERTA Chat Verify</h1>
  <div class="muted">
    최근 <code><?php echo h($minutes); ?>분</code> 기준으로 채팅 저장/레이어 추출 상태를 확인합니다.
    (URL 파라미터: minutes, user_id, agent_key, session_id)
  </div>

  <div class="card">
    <form method="GET">
      <div class="row">
        <div>
          <label>minutes</label>
          <input name="minutes" value="<?php echo h($minutes); ?>"/>
        </div>
        <div>
          <label>user_id</label>
          <input name="user_id" value="<?php echo h($user_id); ?>"/>
        </div>
        <div>
          <label>agent_key (예: 01_time_capsule)</label>
          <input name="agent_key" value="<?php echo h($agent_key); ?>"/>
        </div>
        <div>
          <label>session_id (선택)</label>
          <input name="session_id" value="<?php echo h($session_id); ?>"/>
        </div>
        <div>
          <button type="submit">새로고침</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card">
    <div><strong>스모크 테스트 (클릭 1번으로 저장/레이어 추출 확인)</strong></div>
    <div class="muted">선택한 agent_key로 “대화 1턴 생성 → DB 저장 → 8층 레이어 추출 저장”을 수행합니다.</div>
    <form method="POST" style="margin-top: 10px;">
      <input type="hidden" name="action" value="smoketest"/>
      <div class="row">
        <div>
          <label>agent_key</label>
          <input name="agent_key" value="<?php echo h($agent_key !== '' ? $agent_key : '01_time_capsule'); ?>"/>
        </div>
        <div style="flex:1; min-width: 280px;">
          <label>message</label>
          <input name="message" style="width: 100%;" value="<?php echo h('요즘 목표가 흔들려. 어디서부터 잡아야 할지 모르겠어.'); ?>"/>
        </div>
        <div>
          <button type="submit">스모크 테스트 실행</button>
        </div>
      </div>
    </form>
    <?php if ($smoke_result !== null): ?>
      <div style="margin-top: 10px;">
        <?php if (!empty($smoke_result['success'])): ?>
          <div class="ok">SUCCESS</div>
          <div class="muted">agent_key: <code><?php echo h($smoke_result['agent_key']); ?></code> / session_id: <code><?php echo h($smoke_result['session_id']); ?></code></div>
          <pre><?php echo h("학생: " . $smoke_result['user_message'] . "\nAI: " . $smoke_result['ai_message']); ?></pre>
          <div class="muted">save_result: <code><?php echo h(json_encode($smoke_result['save_result'], JSON_UNESCAPED_UNICODE)); ?></code></div>
        <?php else: ?>
          <div class="bad">FAIL</div>
          <div class="muted"><?php echo h($smoke_result['error'] ?? 'Unknown error'); ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div><strong>테이블 존재 여부</strong></div>
    <ul>
      <li>conversation_contexts: <?php echo $has_ctx ? '<span class="ok">OK</span>' : '<span class="bad">MISSING</span>'; ?></li>
      <li>conversation_messages: <?php echo $has_msg ? '<span class="ok">OK</span>' : '<span class="bad">MISSING</span>'; ?></li>
      <li>conversation_layers: <?php echo $has_layers ? '<span class="ok">OK</span>' : '<span class="bad">MISSING</span>'; ?></li>
      <li>interactions(기존): <?php echo $has_interactions ? '<span class="ok">OK</span>' : '<span class="bad">MISSING</span>'; ?></li>
    </ul>
    <div class="muted">prefix: <code><?php echo h($prefix); ?></code></div>
  </div>

  <div class="card">
    <div><strong>최신 컨텍스트 (wxsperta_conversation_contexts)</strong></div>
    <?php if (!$latest_ctx): ?>
      <div class="muted">데이터 없음</div>
    <?php else: ?>
      <div class="muted">
        session_id: <code><?php echo h($latest_ctx->session_id); ?></code> /
        agent_key: <code><?php echo h($latest_ctx->agent_key); ?></code> /
        last_updated: <code><?php echo h($latest_ctx->last_updated); ?></code>
      </div>
      <pre><?php echo h(cut($latest_ctx->context_summary, 2000)); ?></pre>
    <?php endif; ?>
  </div>

  <div class="card">
    <div><strong>최근 메시지 (wxsperta_conversation_messages)</strong></div>
    <?php if (!$has_msg): ?>
      <div class="muted">테이블 없음</div>
    <?php elseif (!$recent_messages): ?>
      <div class="muted">조건에 맞는 메시지 없음</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>created_at</th>
            <th>session_id</th>
            <th>agent_key</th>
            <th>role</th>
            <th>content</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_messages as $m): ?>
          <tr>
            <td><?php echo h($m->created_at); ?></td>
            <td><?php echo h($m->session_id); ?></td>
            <td><?php echo h($m->agent_key); ?></td>
            <td><?php echo h($m->role); ?></td>
            <td><?php echo h(cut($m->content, 220)); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <div><strong>최근 레이어 추출 (wxsperta_conversation_layers)</strong></div>
    <?php if (!$has_layers): ?>
      <div class="muted">테이블 없음</div>
    <?php elseif (!$recent_layers): ?>
      <div class="muted">조건에 맞는 레이어 없음 (추출 실패/저장 스킵 가능)</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>created_at</th>
            <th>session_id</th>
            <th>agent_key</th>
            <th>layer</th>
            <th>content</th>
            <th>confidence</th>
            <th>approved</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_layers as $r): ?>
          <tr>
            <td><?php echo h($r->created_at); ?></td>
            <td><?php echo h($r->session_id); ?></td>
            <td><?php echo h($r->agent_key); ?></td>
            <td><?php echo h($r->layer); ?></td>
            <td><?php echo h(cut($r->layer_content, 220)); ?></td>
            <td><?php echo h($r->confidence_score); ?></td>
            <td><?php echo h($r->is_approved); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <div><strong>최근 interactions (기존 시스템)</strong></div>
    <?php if (!$has_interactions): ?>
      <div class="muted">테이블 없음</div>
    <?php elseif (!$recent_interactions): ?>
      <div class="muted">조건에 맞는 interactions 없음</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>created_at</th>
            <th>agent_id</th>
            <th>type</th>
            <th>user_input</th>
            <th>agent_response</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_interactions as $r): ?>
          <tr>
            <td><?php echo h($r->created_at); ?></td>
            <td><?php echo h($r->agent_id); ?></td>
            <td><?php echo h($r->interaction_type); ?></td>
            <td><?php echo h(cut($r->user_input, 140)); ?></td>
            <td><?php echo h(cut($r->agent_response, 140)); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <div><strong>레이어 추출 실패 힌트 (오늘 로그에서 검색)</strong></div>
    <div class="muted">log_file: <code><?php echo h($logFile); ?></code></div>
    <?php if (!$logHints): ?>
      <div class="muted">발견된 실패 로그 없음(또는 LOG_DIR 미존재/권한 문제)</div>
    <?php else: ?>
      <pre><?php echo h(implode("\n", $logHints)); ?></pre>
    <?php endif; ?>
  </div>

  <div class="muted">파일: <code><?php echo h(__FILE__); ?></code></div>
</body>
</html>


