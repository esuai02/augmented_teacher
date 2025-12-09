<?php
// 테스트 파일 - 시스템 연결 확인용

// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

echo "<h2>수학킹 시험 대비 시스템 - 연결 테스트</h2>";

// 1. Moodle 로그인 체크
try {
    require_login();
    echo "✅ Moodle 로그인 성공<br>";
    echo "- 사용자 ID: " . $USER->id . "<br>";
    echo "- 사용자명: " . $USER->username . "<br>";
    echo "- 이름: " . $USER->firstname . " " . $USER->lastname . "<br><br>";
} catch (Exception $e) {
    echo "❌ Moodle 로그인 실패: " . $e->getMessage() . "<br>";
    exit;
}

// 2. 데이터베이스 연결 테스트
echo "<h3>데이터베이스 테이블 확인</h3>";

$tables = array(
    'mdl_alt42t_users',
    'mdl_alt42t_exams',
    'mdl_alt42t_exam_dates',
    'mdl_alt42t_exam_resources',
    'mdl_alt42t_study_status'
);

foreach ($tables as $table) {
    try {
        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {" . str_replace('mdl_', '', $table) . "}");
        echo "✅ $table - 레코드 수: $count<br>";
    } catch (Exception $e) {
        echo "❌ $table - 테이블 없음 또는 오류<br>";
    }
}

// 3. study_level 컬럼 확인
echo "<br><h3>study_level 컬럼 확인</h3>";
try {
    $sql = "SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = DATABASE() 
            AND table_name = 'mdl_alt42t_study_status' 
            AND column_name = 'study_level'";
    
    $result = $DB->get_record_sql($sql);
    if ($result) {
        echo "✅ study_level 컬럼이 존재합니다<br>";
    } else {
        echo "❌ study_level 컬럼이 없습니다. add_study_level_column.sql을 실행하세요<br>";
    }
} catch (Exception $e) {
    echo "❌ 컬럼 확인 중 오류: " . $e->getMessage() . "<br>";
}

// 4. 사용자 정보 조회 테스트
echo "<br><h3>사용자 정보 조회 테스트</h3>";
try {
    // mdl_user_info_data에서 정보 조회
    $birthdate = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => 5));
    $institute = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => 4));
    
    echo "- 출생년도: " . ($birthdate ? substr($birthdate, 0, 4) : "없음") . "<br>";
    echo "- 학교: " . ($institute ?: "없음") . "<br>";
    
    // alt42t 테이블에서 정보 조회
    $alt42t_user = $DB->get_record('alt42t_users', array('userid' => $USER->id));
    if ($alt42t_user) {
        echo "<br>✅ alt42t_users 레코드 존재<br>";
        echo "- 학교: " . $alt42t_user->school_name . "<br>";
        echo "- 학년: " . $alt42t_user->grade . "<br>";
    } else {
        echo "<br>ℹ️ alt42t_users에 레코드 없음 (처음 사용하는 경우 정상)<br>";
    }
} catch (Exception $e) {
    echo "❌ 사용자 정보 조회 오류: " . $e->getMessage() . "<br>";
}

echo "<br><hr>";
echo "<a href='exam_preparation_system.php'>시험 대비 시스템으로 이동</a>";
?>