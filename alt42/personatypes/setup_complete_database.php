<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $_GET["userid"] ?? $USER->id; 

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$userid' AND fieldid='22'"); 
$role = $userrole->data ?? 'student';


// Page setup
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/shiningstars/setup_complete_database.php');
$PAGE->set_title('Math Pattern Guide - Complete Database Setup');
$PAGE->set_heading('수학인지관성 도감 - 전체 데이터베이스 설정');

echo $OUTPUT->header();

echo '<div style="max-width: 800px; margin: 0 auto; padding: 20px;">';
echo '<h2>데이터베이스 설정</h2>';

// Function to execute SQL file
function execute_sql_file($filepath, $DB) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => "파일을 찾을 수 없습니다: $filepath"];
    }
    
    $sql = file_get_contents($filepath);
    if (empty($sql)) {
        return ['success' => false, 'message' => "빈 SQL 파일: $filepath"];
    }
    
    // Split by semicolon and execute each query
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    $success_count = 0;
    $errors = [];
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            $DB->execute($query);
            $success_count++;
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    if (count($errors) > 0) {
        return [
            'success' => false, 
            'message' => "일부 쿼리 실행 실패", 
            'details' => $errors,
            'success_count' => $success_count
        ];
    }
    
    return [
        'success' => true, 
        'message' => "$success_count 개의 쿼리가 성공적으로 실행되었습니다."
    ];
}

// Step 1: Clean and recreate tables
echo '<h3>Step 1: 테이블 초기화 및 재생성</h3>';
$result = execute_sql_file(__DIR__ . '/sql/simple_clean_install.sql', $DB);
if ($result['success']) {
    echo '<p style="color: green;">✓ ' . $result['message'] . '</p>';
} else {
    echo '<p style="color: red;">✗ ' . $result['message'] . '</p>';
    if (isset($result['details'])) {
        echo '<ul>';
        foreach ($result['details'] as $error) {
            echo '<li style="color: red;">' . $error . '</li>';
        }
        echo '</ul>';
    }
}

// Step 2: Create user progress tables
echo '<h3>Step 2: 사용자 진행 상황 테이블 생성</h3>';
$result = execute_sql_file(__DIR__ . '/sql/create_user_progress_table.sql', $DB);
if ($result['success']) {
    echo '<p style="color: green;">✓ ' . $result['message'] . '</p>';
} else {
    echo '<p style="color: red;">✗ ' . $result['message'] . '</p>';
}

// Step 3: Insert personas 1-10
echo '<h3>Step 3: 페르소나 1-10 데이터 삽입</h3>';
$result = execute_sql_file(__DIR__ . '/sql/insert_personas_1_to_10.sql', $DB);
if ($result['success']) {
    echo '<p style="color: green;">✓ ' . $result['message'] . '</p>';
} else {
    echo '<p style="color: red;">✗ ' . $result['message'] . '</p>';
}

// Step 4: Insert personas 11-60
echo '<h3>Step 4: 페르소나 11-60 데이터 삽입</h3>';
$result = execute_sql_file(__DIR__ . '/sql/insert_personas_11_to_60.sql', $DB);
if ($result['success']) {
    echo '<p style="color: green;">✓ ' . $result['message'] . '</p>';
} else {
    echo '<p style="color: red;">✗ ' . $result['message'] . '</p>';
}

// Step 5: Verify data
echo '<h3>Step 5: 데이터 검증</h3>';
try {
    $pattern_count = $DB->count_records('alt42i_math_patterns');
    $solution_count = $DB->count_records('alt42i_pattern_solutions');
    $category_count = $DB->count_records('alt42i_pattern_categories');
    
    echo '<ul>';
    echo '<li>패턴 수: ' . $pattern_count . ' (목표: 60)</li>';
    echo '<li>솔루션 수: ' . $solution_count . ' (목표: 60)</li>';
    echo '<li>카테고리 수: ' . $category_count . ' (목표: 8)</li>';
    echo '</ul>';
    
    if ($pattern_count == 60 && $solution_count == 60 && $category_count == 8) {
        echo '<p style="color: green; font-weight: bold;">✓ 모든 데이터가 성공적으로 설치되었습니다!</p>';
        
        // Show sample data
        echo '<h4>샘플 데이터:</h4>';
        $sample = $DB->get_record_sql("
            SELECT p.*, c.category_name, s.action 
            FROM {alt42i_math_patterns} p
            JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
            JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
            WHERE p.id = 1
        ");
        
        if ($sample) {
            echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
            echo '<strong>ID 1:</strong> ' . $sample->name . '<br>';
            echo '<strong>카테고리:</strong> ' . $sample->category_name . '<br>';
            echo '<strong>설명:</strong> ' . $sample->description . '<br>';
            echo '<strong>해결방법:</strong> ' . substr($sample->action, 0, 100) . '...<br>';
            echo '</div>';
        }
    } else {
        echo '<p style="color: orange;">⚠️ 일부 데이터가 누락되었을 수 있습니다.</p>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">검증 중 오류 발생: ' . $e->getMessage() . '</p>';
}

echo '<h3>다음 단계:</h3>';
echo '<p>데이터베이스 설정이 완료되었습니다. 이제 다음 페이지에서 수학인지관성 도감을 사용할 수 있습니다:</p>';
echo '<ul>';
echo '<li><a href="index.php">Shining Stars 메인 페이지</a></li>';
echo '<li><a href="math_pattern_guide.php">수학인지관성 도감 바로가기</a></li>';
echo '</ul>';

echo '</div>';

echo $OUTPUT->footer();