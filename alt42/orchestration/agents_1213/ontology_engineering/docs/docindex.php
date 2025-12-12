<?php
/**
 * 온톨로지 문서 관리 시스템 - 독립 웹페이지
 * File: ontology_engineering/docs/docindex.php
 * Moodle 테마 없이 독립적으로 실행되는 페이지
 */

// 출력 버퍼링 시작 (Moodle의 HTML 출력 차단)
ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;

// Moodle 페이지 객체 비활성화 (출력 방지)
if (isset($PAGE)) {
    $PAGE->set_pagelayout('embedded'); // 최소 레이아웃
}

require_login();

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

if ($role !== 'admin' && $role !== 'manager') {
    ob_end_clean();
    die('Access denied. File: docindex.php:15');
}

// Moodle의 모든 출력 버퍼 정리 (여러 레벨의 버퍼가 있을 수 있음)
while (ob_get_level() > 0) {
    ob_end_clean();
}

// 템플릿 파일 경로
$templateFile = __DIR__ . '/docindex.template.html';

if (!file_exists($templateFile)) {
    die('Template file not found. File: docindex.php:25');
}

// 템플릿 파일 읽기
$template = file_get_contents($templateFile);

// API URL 설정
$apiUrl = 'docapi.php';
$baseUrl = '/local/augmented_teacher/alt42/orchestration/agents/ontology_engineering/docs/';

// 템플릿에 변수 주입
$template = str_replace('{{API_URL}}', $apiUrl, $template);
$template = str_replace('{{BASE_URL}}', $baseUrl, $template);

// 완전한 HTML 문서로 출력
header('Content-Type: text/html; charset=UTF-8');
echo $template;
exit;
?>

