<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = '';
if (isset($_GET['h']) && !empty($_GET['h'])) {
    $hash = trim($_GET['h']);
} else if (isset($_GET['id']) && !empty($_GET['id'])) {
    $hash = trim($_GET['id']);
}
if (empty($hash)) {
    http_response_code(400);
    echo '해시 파라미터가 없습니다. 사용법: s.php?h=해시';
    exit;
}
$record = $DB->get_record_sql("SELECT original_url FROM mdl_short_urls WHERE hash = ?", array($hash));
if ($record) {
    $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE hash = ?", array($hash));
    header('Location: ' . $record->original_url);
    exit;
}
http_response_code(404);
echo '단축 URL을 찾을 수 없습니다.';
?>