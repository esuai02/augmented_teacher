<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 교사 권한 확인
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
}

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"); 
$role = $userrole ? $userrole->role : 'student';
if ($role !== 'student') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다.</h2>");
}

echo "<h1>교사별 담당 학생 테스트</h1>";

// 현재 교사 정보
echo "<h2>현재 교사 정보</h2>";
echo "<p>ID: {$USER->id}</p>";
echo "<p>이름: {$USER->firstname} {$USER->lastname}</p>";

// 교사 심볼 추출
$tsymbol = '';
$tsymbol1 = '';
$tsymbol2 = '';
$tsymbol3 = '';

if ($USER->firstname) {
    // 교사 이름에서 이모티콘 찾기
    preg_match_all('/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{27BF}]/u', $USER->firstname, $matches);
    $emojis = $matches[0];
    
    if (count($emojis) > 0) {
        $tsymbol = $emojis[0];
        echo "<p>교사 firstname에서 찾은 이모티콘: $tsymbol</p>";
    } else {
        // 교사 ID 기반 기본 심볼 할당
        $teacherId = $USER->id;
        $symbols = array('🌟', '⭐', '✨', '🎯', '🔥', '💫', '🌈', '🎨', '🎪', '🎭');
        $symbolIndex = $teacherId % count($symbols);
        $tsymbol = $symbols[$symbolIndex];
        echo "<p>할당된 기본 심볼: $tsymbol (교사 ID 기반)</p>";
    }
    
    $tsymbol1 = $tsymbol;
    $tsymbol2 = $tsymbol;
    $tsymbol3 = $tsymbol;
}

echo "<p><strong>사용할 심볼: $tsymbol</strong></p>";

// mdl_abessi_teacher 테이블 확인
echo "<h2>교사 테이블 정보</h2>";
$teacherInfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher 
                                    WHERE userid = ? 
                                    ORDER BY id DESC LIMIT 1", array($USER->id));
if ($teacherInfo) {
    echo "<pre>";
    print_r($teacherInfo);
    echo "</pre>";
} else {
    echo "<p>mdl_abessi_teacher 테이블에 정보가 없습니다.</p>";
}

// 담당 학생 목록
echo "<h2>담당 학생 목록 (이름에 '$tsymbol' 포함)</h2>";

$sql = "SELECT u.id, u.firstname, u.lastname, u.email
        FROM mdl_user u
        INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
        WHERE uid.fieldid = 22 AND uid.data = 'student'
        AND u.deleted = 0 AND u.suspended = 0
        AND (u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ?)
        ORDER BY u.firstname ASC";

$params = array(
    '%' . $tsymbol . '%',
    '%' . $tsymbol1 . '%',
    '%' . $tsymbol2 . '%',
    '%' . $tsymbol3 . '%'
);

$students = $DB->get_records_sql($sql, $params);

if ($students) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>이름</th><th>성</th><th>전체 이름</th><th>이메일</th></tr>";
    
    foreach ($students as $student) {
        // 이름에서 이모티콘 표시
        preg_match_all('/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{27BF}]/u', $student->firstname, $studentEmojis);
        $emojisStr = implode(' ', $studentEmojis[0]);
        
        echo "<tr>";
        echo "<td>{$student->id}</td>";
        echo "<td>{$student->firstname}</td>";
        echo "<td>{$student->lastname}</td>";
        echo "<td>{$student->firstname} {$student->lastname}</td>";
        echo "<td>{$student->email}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>총 " . count($students) . "명의 담당 학생</p>";
} else {
    echo "<p>담당 학생이 없습니다.</p>";
}

// 전체 학생 중 이모티콘이 있는 학생 통계
echo "<h2>전체 학생 이모티콘 통계</h2>";

$allStudents = $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname
                                     FROM mdl_user u
                                     INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                                     WHERE uid.fieldid = 22 AND uid.data = 'student'
                                     AND u.deleted = 0 AND u.suspended = 0");

$emojiStats = array();
$noEmojiCount = 0;

foreach ($allStudents as $student) {
    preg_match_all('/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{27BF}]/u', $student->firstname, $matches);
    if (count($matches[0]) > 0) {
        foreach ($matches[0] as $emoji) {
            if (!isset($emojiStats[$emoji])) {
                $emojiStats[$emoji] = 0;
            }
            $emojiStats[$emoji]++;
        }
    } else {
        $noEmojiCount++;
    }
}

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>이모티콘</th><th>학생 수</th></tr>";
foreach ($emojiStats as $emoji => $count) {
    $highlight = ($emoji == $tsymbol) ? "style='background: yellow;'" : "";
    echo "<tr $highlight>";
    echo "<td>$emoji</td>";
    echo "<td>$count</td>";
    echo "</tr>";
}
echo "<tr><td>이모티콘 없음</td><td>$noEmojiCount</td></tr>";
echo "</table>";

// AJAX 테스트 링크
echo "<h2>AJAX 엔드포인트 테스트</h2>";
echo "<p><a href='attendance_teacher.php?ajax=students' target='_blank'>학생 목록 JSON (필터링됨)</a></p>";
echo "<p><a href='attendance_teacher.php?ajax=alerts' target='_blank'>알림 JSON (필터링됨)</a></p>";
?>