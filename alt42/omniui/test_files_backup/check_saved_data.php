<?php
// 저장된 데이터 확인 스크립트

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);

echo "<h2>저장된 데이터 확인 (User ID: $userid)</h2>";

// 1. alt42t_users 확인
echo "<h3>1. mdl_alt42t_users</h3>";
$alt42t_user = $DB->get_record('alt42t_users', array('userid' => $userid));
if ($alt42t_user) {
    echo "<pre>";
    print_r($alt42t_user);
    echo "</pre>";
    echo "alt42t user ID: " . $alt42t_user->id . "<br>";
    echo "School: " . $alt42t_user->school_name . "<br>";
    echo "Grade: " . $alt42t_user->grade . "<br>";
} else {
    echo "데이터 없음<br>";
}

// 2. alt42t_exams 확인
if ($alt42t_user) {
    echo "<h3>2. mdl_alt42t_exams</h3>";
    $exams = $DB->get_records('alt42t_exams', array(
        'school_name' => $alt42t_user->school_name,
        'grade' => $alt42t_user->grade
    ));
    if ($exams) {
        foreach ($exams as $exam) {
            echo "<pre>";
            print_r($exam);
            echo "</pre>";
        }
    } else {
        echo "데이터 없음<br>";
    }
    
    // 3. alt42t_exam_dates 확인
    echo "<h3>3. mdl_alt42t_exam_dates</h3>";
    $exam_dates = $DB->get_records('alt42t_exam_dates', array('user_id' => $alt42t_user->id));
    if ($exam_dates) {
        foreach ($exam_dates as $date) {
            echo "<pre>";
            print_r($date);
            echo "</pre>";
        }
    } else {
        echo "데이터 없음<br>";
    }
    
    // 4. alt42t_exam_resources 확인
    echo "<h3>4. mdl_alt42t_exam_resources</h3>";
    $resources = $DB->get_records('alt42t_exam_resources', array('user_id' => $alt42t_user->id));
    if ($resources) {
        foreach ($resources as $resource) {
            echo "<pre>";
            print_r($resource);
            echo "</pre>";
        }
    } else {
        echo "데이터 없음<br>";
    }
    
    // 5. alt42t_study_status 확인
    echo "<h3>5. mdl_alt42t_study_status</h3>";
    $status = $DB->get_records('alt42t_study_status', array('user_id' => $alt42t_user->id));
    if ($status) {
        foreach ($status as $s) {
            echo "<pre>";
            print_r($s);
            echo "</pre>";
        }
    } else {
        echo "데이터 없음<br>";
    }
}

// 6. JOIN 쿼리 테스트
echo "<h3>6. JOIN 쿼리 테스트</h3>";
try {
    $joinData = $DB->get_record_sql("
        SELECT u.*, e.exam_type, ed.start_date, ed.end_date, ed.math_date as math_exam_date, 
               er.tip_text as exam_scope, ed.status as exam_status, ss.status as study_status
        FROM {alt42t_users} u
        LEFT JOIN {alt42t_exams} e ON u.school_name = e.school_name AND u.grade = e.grade
        LEFT JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id AND u.id = ed.user_id
        LEFT JOIN {alt42t_exam_resources} er ON e.exam_id = er.exam_id AND u.id = er.user_id
        LEFT JOIN {alt42t_study_status} ss ON u.id = ss.user_id AND e.exam_id = ss.exam_id
        WHERE u.userid = ?
        LIMIT 1", array($userid));
    
    if ($joinData) {
        echo "<pre>";
        print_r($joinData);
        echo "</pre>";
    } else {
        echo "JOIN 결과 없음<br>";
    }
} catch (Exception $e) {
    echo "JOIN 쿼리 오류: " . $e->getMessage() . "<br>";
}

// 7. 최근 저장된 모든 데이터
echo "<h3>7. 최근 저장된 모든 데이터 (최근 10개)</h3>";
$recent = $DB->get_records_sql("
    SELECT u.userid, u.name, u.school_name, u.grade, 
           e.exam_type, ed.start_date, ed.math_date
    FROM {alt42t_users} u
    LEFT JOIN {alt42t_exams} e ON u.school_name = e.school_name AND u.grade = e.grade
    LEFT JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id AND u.id = ed.user_id
    ORDER BY u.timemodified DESC
    LIMIT 10
");

if ($recent) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>UserID</th><th>이름</th><th>학교</th><th>학년</th><th>시험</th><th>시작일</th><th>수학시험일</th></tr>";
    foreach ($recent as $r) {
        echo "<tr>";
        echo "<td>{$r->userid}</td>";
        echo "<td>{$r->name}</td>";
        echo "<td>{$r->school_name}</td>";
        echo "<td>{$r->grade}</td>";
        echo "<td>{$r->exam_type}</td>";
        echo "<td>{$r->start_date}</td>";
        echo "<td>{$r->math_date}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<br><a href='exam_preparation_system.php?userid=$userid'>시험 대비 시스템으로 돌아가기</a>";
?>