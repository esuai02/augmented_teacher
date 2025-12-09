<?php
header('Content-Type: application/json; charset=utf-8');

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$school = optional_param('school', '', PARAM_TEXT);

$response = array('success' => false);

try {
    if (empty($school)) {
        throw new Exception('학교명이 필요합니다');
    }
    
    // 학교 홈페이지 DB 조회 (alt42t_schools 테이블이 있다고 가정)
    $schoolRecord = $DB->get_record_sql(
        "SELECT homepage_url FROM {alt42t_schools} WHERE school_name LIKE ? LIMIT 1",
        array('%' . $school . '%')
    );
    
    if ($schoolRecord && $schoolRecord->homepage_url) {
        $response['success'] = true;
        $response['homepage_url'] = $schoolRecord->homepage_url;
    } else {
        // 학교 홈페이지를 찾을 수 없는 경우 - 추후 크롤링 기능 구현 예정
        // 임시로 네이버 검색 링크 제공
        $response['success'] = true;
        $response['homepage_url'] = 'https://search.naver.com/search.naver?query=' . urlencode($school . ' 홈페이지');
        $response['is_search'] = true;
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>