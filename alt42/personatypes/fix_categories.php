<?php
/**
 * 카테고리 데이터 확인 및 추가
 */
require_once('/home/moodle/public_html/moodle/config.php');
global $DB;
require_login();

if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h1>카테고리 데이터 수정</h1>";

// 기존 카테고리 확인
$existing = $DB->count_records('alt42i_pattern_categories');
echo "<p>현재 카테고리 수: {$existing}</p>";

if ($existing < 8) {
    echo "<p>카테고리를 추가합니다...</p>";
    
    $categories = [
        ['id' => 1, 'category_name' => '인지 과부하', 'description' => '정보 처리 용량 초과로 인한 수학 학습 장애'],
        ['id' => 2, 'category_name' => '자신감 왜곡', 'description' => '수학에 대한 부정적 자아상과 학습된 무기력'],
        ['id' => 3, 'category_name' => '실수 패턴', 'description' => '반복적으로 나타나는 계산 및 개념 오류'],
        ['id' => 4, 'category_name' => '접근 전략 오류', 'description' => '문제 해결 방법 선택의 비효율성'],
        ['id' => 5, 'category_name' => '학습 습관', 'description' => '비효과적인 학습 방법과 습관'],
        ['id' => 6, 'category_name' => '시간/압박 관리', 'description' => '시험 불안과 시간 관리 문제'],
        ['id' => 7, 'category_name' => '검증/확인 부재', 'description' => '답안 확인 및 검토 과정의 부재'],
        ['id' => 8, 'category_name' => '기타 장애', 'description' => '기타 수학 학습 장애 요인']
    ];
    
    foreach ($categories as $cat) {
        $existing_cat = $DB->get_record('alt42i_pattern_categories', ['id' => $cat['id']]);
        if (!$existing_cat) {
            $DB->insert_record_raw('alt42i_pattern_categories', $cat, false, false, true);
            echo "<p>✅ 카테고리 추가: {$cat['category_name']}</p>";
        } else {
            echo "<p>- 카테고리 이미 존재: {$cat['category_name']}</p>";
        }
    }
}

// 결과 확인
$all_categories = $DB->get_records('alt42i_pattern_categories');
echo "<h2>전체 카테고리 목록</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>이름</th><th>설명</th></tr>";
foreach ($all_categories as $cat) {
    echo "<tr><td>{$cat->id}</td><td>{$cat->category_name}</td><td>{$cat->description}</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='check_and_fix_data.php'>데이터 확인으로 돌아가기</a></p>";
echo "<p><a href='insert_60_personas_minimal.php'>최소 패턴 데이터 추가</a></p>";
?>