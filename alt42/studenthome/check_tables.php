<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 로그인 확인
require_login();

echo "<h2>데이터베이스 테이블 확인</h2>";
echo "<pre>";

// 1. 현재 사용자 정보
echo "현재 사용자: " . $USER->username . " (ID: " . $USER->id . ")\n\n";

// 2. 테이블 존재 여부 확인
$tables = ['mdl_persona_modes', 'mdl_message_transformations', 'mdl_chat_messages'];

echo "=== 테이블 존재 여부 확인 ===\n";
foreach ($tables as $table) {
    try {
        // 테이블에서 카운트 시도
        $sql = "SELECT COUNT(*) as cnt FROM {$table}";
        $result = $DB->get_record_sql($sql);
        echo "✓ {$table}: 존재함 (레코드 수: {$result->cnt})\n";
        
        // 테이블 구조 확인
        if ($table == 'mdl_persona_modes') {
            echo "\n  테이블 구조:\n";
            $columns = $DB->get_columns('persona_modes');
            foreach ($columns as $column) {
                echo "  - {$column->name} ({$column->type})\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ {$table}: 존재하지 않음 또는 접근 오류\n";
        echo "  오류: " . $e->getMessage() . "\n";
    }
}

// 3. 샘플 데이터 삽입 테스트
echo "\n=== 샘플 데이터 삽입 테스트 ===\n";
try {
    // 테스트 데이터 준비
    $test_data = new stdClass();
    $test_data->teacher_id = $USER->id;
    $test_data->student_id = 999999; // 테스트용 학생 ID
    $test_data->teacher_mode = 'test_teacher';
    $test_data->student_mode = 'test_student';
    $test_data->created_at = time();
    $test_data->updated_at = time();
    
    // 기존 테스트 데이터 삭제
    $DB->delete_records('persona_modes', array(
        'teacher_id' => $test_data->teacher_id,
        'student_id' => $test_data->student_id
    ));
    
    // 삽입 테스트
    $insert_id = $DB->insert_record('persona_modes', $test_data);
    echo "✓ 삽입 성공! ID: {$insert_id}\n";
    
    // 조회 테스트
    $retrieved = $DB->get_record('persona_modes', array('id' => $insert_id));
    if ($retrieved) {
        echo "✓ 조회 성공!\n";
        echo "  - Teacher Mode: {$retrieved->teacher_mode}\n";
        echo "  - Student Mode: {$retrieved->student_mode}\n";
    }
    
    // 업데이트 테스트
    $retrieved->teacher_mode = 'updated_teacher';
    $retrieved->updated_at = time();
    $DB->update_record('persona_modes', $retrieved);
    echo "✓ 업데이트 성공!\n";
    
    // 삭제 테스트
    $DB->delete_records('persona_modes', array('id' => $insert_id));
    echo "✓ 삭제 성공!\n";
    
} catch (Exception $e) {
    echo "✗ 데이터베이스 작업 실패: " . $e->getMessage() . "\n";
    echo "상세 오류:\n";
    echo $e->getTraceAsString() . "\n";
}

// 4. POST 요청 시뮬레이션 테스트
echo "\n=== AJAX 요청 시뮬레이션 ===\n";
$test_url = "https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/selectmode.php";
echo "테스트 URL: {$test_url}\n";
echo "테스트 데이터:\n";
echo "  - action: save_modes\n";
echo "  - teacher_mode: curriculum\n";
echo "  - student_mode: exam\n";
echo "  - student_id: 123\n";

echo "\n테스트 명령어 (터미널에서 실행):\n";
echo "curl -X POST '{$test_url}' \\\n";
echo "  -H 'Cookie: MoodleSession=YOUR_SESSION_ID' \\\n";
echo "  -d 'action=save_modes&teacher_mode=curriculum&student_mode=exam&student_id=123'\n";

echo "</pre>";

// 5. 기존 데이터 확인
echo "<h3>현재 저장된 페르소나 모드</h3>";
try {
    $existing_modes = $DB->get_records('persona_modes', array('teacher_id' => $USER->id));
    if ($existing_modes) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Teacher ID</th><th>Student ID</th><th>Teacher Mode</th><th>Student Mode</th><th>Created</th><th>Updated</th></tr>";
        foreach ($existing_modes as $mode) {
            echo "<tr>";
            echo "<td style='padding: 5px;'>{$mode->id}</td>";
            echo "<td style='padding: 5px;'>{$mode->teacher_id}</td>";
            echo "<td style='padding: 5px;'>{$mode->student_id}</td>";
            echo "<td style='padding: 5px;'>{$mode->teacher_mode}</td>";
            echo "<td style='padding: 5px;'>{$mode->student_mode}</td>";
            echo "<td style='padding: 5px;'>" . date('Y-m-d H:i:s', $mode->created_at) . "</td>";
            echo "<td style='padding: 5px;'>" . date('Y-m-d H:i:s', $mode->updated_at) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>저장된 데이터가 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>데이터 조회 실패: " . $e->getMessage() . "</p>";
}
?>