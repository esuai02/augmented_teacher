<?php
// 에러 표시 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP 실행 시작<br>";

// Moodle 설정 파일 포함 시도
$config_path = "/home/moodle/public_html/moodle/config.php";
if (file_exists($config_path)) {
    echo "2. config.php 파일 존재함<br>";
    include_once($config_path);
    echo "3. config.php 포함 완료<br>";
} else {
    echo "2. config.php 파일을 찾을 수 없음: $config_path<br>";
    die();
}

// 전역 변수 확인
if (isset($DB)) {
    echo "4. DB 객체 존재함<br>";
} else {
    echo "4. DB 객체가 없음<br>";
}

if (isset($USER)) {
    echo "5. USER 객체 존재함<br>";
} else {
    echo "5. USER 객체가 없음<br>";
}

// 로그인 확인
try {
    require_login();
    echo "6. 로그인 확인 완료<br>";
} catch (Exception $e) {
    echo "6. 로그인 오류: " . $e->getMessage() . "<br>";
}

// 사용자 정보 출력
if (isset($USER->id)) {
    echo "7. 사용자 ID: " . $USER->id . "<br>";
    echo "8. 사용자 이름: " . $USER->firstname . " " . $USER->lastname . "<br>";
    echo "9. lastname 값: '" . $USER->lastname . "'<br>";
    
    // 교사 권한 확인
    $isTeacher = false;
    if ($USER->lastname === 'T' || 
        trim($USER->lastname) === 'T' || 
        strpos($USER->lastname, 'T') !== false) {
        $isTeacher = true;
        echo "10. lastname에 T가 있음 - 교사로 인식<br>";
    } else {
        echo "10. lastname에 T가 없음 - DB에서 권한 확인 필요<br>";
        
        $userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
        if ($userrole) {
            echo "11. DB 권한: " . $userrole->role . "<br>";
            if ($userrole->role !== 'student') {
                $isTeacher = true;
            }
        } else {
            echo "11. DB에 권한 정보 없음<br>";
        }
    }
    
    if ($isTeacher) {
        echo "<h2 style='color:green;'>✓ 교사 권한 확인됨</h2>";
    } else {
        echo "<h2 style='color:red;'>✗ 교사 권한 없음</h2>";
    }
    
    // 학생 목록 테스트
    echo "<h3>학생 목록 조회 테스트:</h3>";
    $students = $DB->get_records_sql("
        SELECT u.id, u.firstname, u.lastname 
        FROM mdl_user u
        LIMIT 5
    ");
    
    if ($students) {
        echo "학생 수: " . count($students) . "<br>";
        foreach ($students as $student) {
            echo "- " . $student->firstname . " " . $student->lastname . " (ID: " . $student->id . ")<br>";
        }
    } else {
        echo "학생 조회 실패<br>";
    }
    
} else {
    echo "7. 사용자 정보를 가져올 수 없음<br>";
}

echo "<hr>";
echo "<a href='teacher_attendance.php'>출결관리 페이지로 이동</a>";
?>