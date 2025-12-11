<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

echo "<h2>get_new_requests.php 디버깅</h2>";

$teacherid = optional_param('teacherid', $USER->id, PARAM_INT);

echo "<p>현재 사용자 ID: {$USER->id}</p>";
echo "<p>요청된 교사 ID: {$teacherid}</p>";

// 1. 테이블 확인
echo "<h3>1. 테이블 존재 확인</h3>";
$table_exists = $DB->get_manager()->table_exists('ktm_teaching_interactions');
echo "<p>ktm_teaching_interactions 테이블: " . ($table_exists ? "✓ 존재" : "✗ 없음") . "</p>";

if (!$table_exists) {
    echo "<p style='color:red'>테이블이 없습니다. 테이블을 생성해야 합니다.</p>";
    exit;
}

// 2. 테이블 구조 확인
echo "<h3>2. 테이블 구조</h3>";
try {
    $columns = $DB->get_columns('ktm_teaching_interactions');
    echo "<ul>";
    foreach ($columns as $column => $info) {
        echo "<li>{$column}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>테이블 구조 확인 실패: " . $e->getMessage() . "</p>";
}

// 3. 전체 레코드 수 확인
echo "<h3>3. 전체 레코드 수</h3>";
try {
    $total = $DB->count_records('ktm_teaching_interactions');
    echo "<p>전체 레코드 수: {$total}</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>레코드 수 확인 실패: " . $e->getMessage() . "</p>";
}

// 4. 간단한 쿼리 테스트
echo "<h3>4. 간단한 쿼리 테스트</h3>";
try {
    $simple_sql = "SELECT * FROM {ktm_teaching_interactions} LIMIT 5";
    $simple_records = $DB->get_records_sql($simple_sql);
    echo "<p>간단한 쿼리 성공: " . count($simple_records) . "개 레코드</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>간단한 쿼리 실패: " . $e->getMessage() . "</p>";
}

// 5. JOIN 쿼리 테스트
echo "<h3>5. JOIN 쿼리 테스트</h3>";
try {
    $join_sql = "SELECT ti.*, u.firstname, u.lastname 
                 FROM {ktm_teaching_interactions} ti
                 JOIN {user} u ON ti.userid = u.id
                 LIMIT 5";
    $join_records = $DB->get_records_sql($join_sql);
    echo "<p>JOIN 쿼리 성공: " . count($join_records) . "개 레코드</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>JOIN 쿼리 실패: " . $e->getMessage() . "</p>";
}

// 6. 실제 쿼리 테스트
echo "<h3>6. 실제 쿼리 테스트</h3>";
try {
    $sql = "SELECT ti.*, u.firstname, u.lastname 
            FROM {ktm_teaching_interactions} ti
            JOIN {user} u ON ti.userid = u.id
            WHERE (
                (ti.teacherid = :teacherid1 AND ti.status IN ('pending', 'processing'))
                OR (ti.teacherid = 0 OR ti.teacherid IS NULL)
            )
            AND (ti.solution_text IS NULL OR ti.solution_text = '')
            AND ti.problem_image IS NOT NULL
            AND ti.timecreated > :recent_time
            ORDER BY 
                CASE WHEN ti.modification_prompt IS NOT NULL AND ti.modification_prompt != '' THEN 0 ELSE 1 END,
                ti.timecreated DESC
            LIMIT 20";
    
    $params = array(
        'teacherid1' => $teacherid,
        'recent_time' => time() - (24 * 3600)
    );
    
    $requests = $DB->get_records_sql($sql, $params);
    echo "<p style='color:green'>실제 쿼리 성공: " . count($requests) . "개 요청 발견</p>";
    
    if (count($requests) > 0) {
        echo "<h4>발견된 요청들:</h4>";
        echo "<ul>";
        foreach ($requests as $req) {
            echo "<li>ID: {$req->id}, 학생: {$req->firstname} {$req->lastname}, 상태: {$req->status}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>실제 쿼리 실패: " . $e->getMessage() . "</p>";
    echo "<p>SQL: <pre>" . htmlspecialchars($sql) . "</pre></p>";
    echo "<p>Parameters: <pre>" . print_r($params, true) . "</pre></p>";
}

// 7. API 직접 호출 테스트
echo "<h3>7. API 직접 호출 테스트</h3>";
echo "<p><a href='get_new_requests.php?teacherid={$teacherid}' target='_blank'>get_new_requests.php 직접 호출하기</a></p>";

?>