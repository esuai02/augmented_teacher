<?php
/**
 * Conversation Mentoring Schema Setup
 * - 관리자만 실행
 * - conversation_mentoring_schema.sql 실행
 */
require_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . "/config.php");
global $DB, $USER;
require_login();

if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h2>Conversation Mentoring Schema 설정</h2>";

$sql_file = __DIR__ . '/conversation_mentoring_schema.sql';
if (!file_exists($sql_file)) {
    die("SQL 파일을 찾을 수 없습니다: $sql_file");
}

$sql_content = file_get_contents($sql_file);

// 주석 제거(라인 주석 --, #) 후 세미콜론 기준 분리
$lines = preg_split("/\r\n|\n|\r/", $sql_content);
$clean = [];
foreach ($lines as $line) {
    $trim = ltrim($line);
    if ($trim === '') continue;
    if (strpos($trim, '--') === 0) continue;
    if (strpos($trim, '#') === 0) continue;
    $clean[] = $line;
}
$clean_sql = implode("\n", $clean);
$queries = array_filter(array_map('trim', explode(';', $clean_sql)));

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<pre>";
foreach ($queries as $query) {
    if (empty($query)) continue;
    try {
        $DB->execute($query);
        echo "✓ 성공: " . substr($query, 0, 60) . "...\n";
        $success_count++;
    } catch (Exception $e) {
        $error_msg = "✗ 실패: " . $e->getMessage() . " - " . __FILE__ . ":" . __LINE__ . "\n"
            . "SQL: " . substr($query, 0, 300) . "...\n";
        echo $error_msg;
        $errors[] = $error_msg;
        $error_count++;
    }
}
echo "</pre>";

echo "<h3>실행 결과</h3>";
echo "<p>성공: $success_count 개</p>";
echo "<p>실패: $error_count 개</p>";

if (!empty($errors)) {
    echo "<h4>오류 상세:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

wxsperta_log("Conversation schema setup completed. Success: $success_count, Errors: $error_count", 'INFO');

// ==================== 마이그레이션(기존 테이블이 이미 있을 때) ====================
echo "<h3>마이그레이션(기존 테이블 확장)</h3>";

function col_exists($DB, $table, $col) {
    try {
        $cols = $DB->get_columns($table);
        return isset($cols[$col]);
    } catch (Exception $e) {
        return false;
    }
}

function try_exec($DB, $sql) {
    try { $DB->execute($sql); return [true, null]; }
    catch (Exception $e) { return [false, $e->getMessage()]; }
}

// 1) conversation_id 컬럼 추가 (있으면 스킵)
$mig = [];
foreach (['wxsperta_conversation_contexts','wxsperta_conversation_messages','wxsperta_conversation_layers'] as $t) {
    if (!col_exists($DB, $t, 'conversation_id')) {
        $mig[] = [$t, "ALTER TABLE {".$t."} ADD COLUMN conversation_id VARCHAR(64) DEFAULT NULL"];
    }
}
foreach ($mig as [$t, $sql]) {
    [$ok, $err] = try_exec($DB, $sql);
    echo ($ok ? "✓" : "⚠") . " $t: conversation_id " . ($ok ? "추가됨" : "스킵/실패($err)") . "<br/>";
}

// 2) 기존 uniq_session 제거 시도(있으면 제거, 없으면 스킵)
[$okDrop, $errDrop] = try_exec($DB, "ALTER TABLE {wxsperta_conversation_contexts} DROP INDEX uniq_session");
echo ($okDrop ? "✓" : "ℹ") . " wxsperta_conversation_contexts: uniq_session " . ($okDrop ? "삭제됨" : "없음/스킵") . "<br/>";

// 3) conversation_id 유니크 인덱스 추가 시도
[$okUniq, $errUniq] = try_exec($DB, "ALTER TABLE {wxsperta_conversation_contexts} ADD UNIQUE KEY uniq_conversation (conversation_id)");
echo ($okUniq ? "✓" : "ℹ") . " wxsperta_conversation_contexts: uniq_conversation " . ($okUniq ? "추가됨" : "있음/스킵") . "<br/>";

// 4) 기존 데이터 백필: conversation_id가 NULL인 레코드에 session_id 기반으로 채우기
try {
    $ctxs = $DB->get_records_sql("SELECT id, session_id FROM {wxsperta_conversation_contexts} WHERE conversation_id IS NULL OR conversation_id = ''");
    $filled = 0;
    foreach ($ctxs as $c) {
        $sid = (string)$c->session_id;
        $cid = 'c_' . substr(md5($sid), 0, 24);
        $DB->set_field('wxsperta_conversation_contexts', 'conversation_id', $cid, ['id' => (int)$c->id]);
        $DB->execute("UPDATE {wxsperta_conversation_messages} SET conversation_id=? WHERE session_id=? AND (conversation_id IS NULL OR conversation_id='')", [$cid, $sid]);
        $DB->execute("UPDATE {wxsperta_conversation_layers} SET conversation_id=? WHERE session_id=? AND (conversation_id IS NULL OR conversation_id='')", [$cid, $sid]);
        $filled++;
    }
    echo "✓ backfill: contexts {$filled}건<br/>";
} catch (Exception $e) {
    echo "⚠ backfill 실패: " . htmlspecialchars($e->getMessage()) . "<br/>";
}

// 5) conversations 테이블에 최소 메타 생성(가능하면)
try {
    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $hasConv = in_array('wxsperta_conversations', $tables) || in_array($prefix . 'wxsperta_conversations', $tables);
    if ($hasConv) {
        $rows = $DB->get_records_sql("
            SELECT DISTINCT conversation_id, user_id, agent_key
            FROM {wxsperta_conversation_contexts}
            WHERE conversation_id IS NOT NULL AND conversation_id <> ''
        ");
        $added = 0;
        foreach ($rows as $r) {
            $exists = $DB->get_record('wxsperta_conversations', ['conversation_id' => (string)$r->conversation_id]);
            if ($exists) continue;
            $obj = new stdClass();
            $obj->conversation_id = (string)$r->conversation_id;
            $obj->user_id = (int)$r->user_id;
            $obj->agent_key = (string)$r->agent_key;
            $obj->title = null;
            $DB->insert_record('wxsperta_conversations', $obj);
            $added++;
        }
        echo "✓ conversations seed: {$added}건<br/>";
    } else {
        echo "ℹ conversations 테이블 미존재(스킵)<br/>";
    }
} catch (Exception $e) {
    echo "⚠ conversations seed 실패: " . htmlspecialchars($e->getMessage()) . "<br/>";
}

echo "<hr>";
echo "<p><a href='standalone_ui/index.html'>Standalone UI로 이동</a></p>";
?>


