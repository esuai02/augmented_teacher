<?php
/**
 * 데이터베이스 테이블 구조 조정 스크립트
 * mdl_alt42i_math_patterns와 mdl_alt42i_pattern_solutions 테이블을
 * insert SQL 파일들과 일치하도록 수정합니다.
 */

// Moodle 설정 파일 로드
require_once(__DIR__ . '/../../../../../../config.php');

// 관리자 권한 확인
require_login();
require_capability('moodle/site:config', context_system::instance());

// 데이터베이스 매니저 가져오기
$dbman = $DB->get_manager();

echo "<h2>데이터베이스 테이블 구조 조정</h2>";

// 1. mdl_alt42i_math_patterns 테이블 수정
echo "<h3>1. mdl_alt42i_math_patterns 테이블 조정</h3>";

try {
    // 현재 테이블 구조 확인
    $table = new xmldb_table('alt42i_math_patterns');
    
    if ($dbman->table_exists($table)) {
        echo "<p>테이블이 존재합니다. 필드 조정을 시작합니다...</p>";
        
        // pattern_id 필드를 id로 사용하도록 변경
        // 먼저 기존 데이터 백업이 필요하다면 여기서 처리
        
        // pattern_name -> name 으로 변경
        $field_pattern_name = new xmldb_field('pattern_name');
        $field_name = new xmldb_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        
        if ($dbman->field_exists($table, $field_pattern_name)) {
            $dbman->rename_field($table, $field_pattern_name, 'name');
            echo "<p>✓ pattern_name을 name으로 변경했습니다.</p>";
        }
        
        // pattern_desc -> description 으로 변경
        $field_pattern_desc = new xmldb_field('pattern_desc');
        $field_description = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        
        if ($dbman->field_exists($table, $field_pattern_desc)) {
            $dbman->rename_field($table, $field_pattern_desc, 'description');
            echo "<p>✓ pattern_desc를 description으로 변경했습니다.</p>";
        }
        
        // pattern_id 필드 제거 (id를 직접 사용)
        $field_pattern_id = new xmldb_field('pattern_id');
        if ($dbman->field_exists($table, $field_pattern_id)) {
            // 먼저 unique key 제거
            $key = new xmldb_key('uk_pattern_id', XMLDB_KEY_UNIQUE, array('pattern_id'));
            if ($dbman->find_key_name($table, $key)) {
                $dbman->drop_key($table, $key);
            }
            
            // pattern_id 필드 제거
            $dbman->drop_field($table, $field_pattern_id);
            echo "<p>✓ pattern_id 필드를 제거했습니다. (id 필드를 직접 사용)</p>";
        }
        
        // is_active 필드 제거 (SQL에서 사용하지 않음)
        $field_is_active = new xmldb_field('is_active');
        if ($dbman->field_exists($table, $field_is_active)) {
            $dbman->drop_field($table, $field_is_active);
            echo "<p>✓ is_active 필드를 제거했습니다.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ mdl_alt42i_math_patterns 테이블이 존재하지 않습니다.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류 발생: " . $e->getMessage() . "</p>";
}

// 2. mdl_alt42i_pattern_solutions 테이블 수정
echo "<h3>2. mdl_alt42i_pattern_solutions 테이블 조정</h3>";

try {
    $table = new xmldb_table('alt42i_pattern_solutions');
    
    if ($dbman->table_exists($table)) {
        echo "<p>테이블이 존재합니다. 필드 조정을 시작합니다...</p>";
        
        // check_method 필드가 없으면 추가
        $field_check_method = new xmldb_field('check_method', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'action');
        if (!$dbman->field_exists($table, $field_check_method)) {
            $dbman->add_field($table, $field_check_method);
            echo "<p>✓ check_method 필드를 추가했습니다.</p>";
        }
        
        // example_problem 필드 제거 (SQL에서 사용하지 않음)
        $field_example_problem = new xmldb_field('example_problem');
        if ($dbman->field_exists($table, $field_example_problem)) {
            $dbman->drop_field($table, $field_example_problem);
            echo "<p>✓ example_problem 필드를 제거했습니다.</p>";
        }
        
        // practice_guide 필드 제거 (SQL에서 사용하지 않음)
        $field_practice_guide = new xmldb_field('practice_guide');
        if ($dbman->field_exists($table, $field_practice_guide)) {
            $dbman->drop_field($table, $field_practice_guide);
            echo "<p>✓ practice_guide 필드를 제거했습니다.</p>";
        }
        
        // id 필드 제거하고 pattern_id를 PRIMARY KEY로 변경하는 것은 위험할 수 있으므로
        // 현재 구조를 유지하되, INSERT 시 pattern_id 값만 사용하도록 함
        
    } else {
        echo "<p style='color: red;'>❌ mdl_alt42i_pattern_solutions 테이블이 존재하지 않습니다.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류 발생: " . $e->getMessage() . "</p>";
}

// 3. 최종 테이블 구조 확인
echo "<h3>3. 최종 테이블 구조 확인</h3>";

// mdl_alt42i_math_patterns 구조 확인
$columns = $DB->get_columns('mdl_alt42i_math_patterns');
echo "<h4>mdl_alt42i_math_patterns 테이블 컬럼:</h4>";
echo "<ul>";
foreach ($columns as $column) {
    echo "<li>{$column->name} ({$column->type})</li>";
}
echo "</ul>";

// mdl_alt42i_pattern_solutions 구조 확인
$columns = $DB->get_columns('mdl_alt42i_pattern_solutions');
echo "<h4>mdl_alt42i_pattern_solutions 테이블 컬럼:</h4>";
echo "<ul>";
foreach ($columns as $column) {
    echo "<li>{$column->name} ({$column->type})</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><strong>참고:</strong> 이제 다음과 같이 데이터를 삽입할 수 있습니다:</p>";
echo "<pre>";
echo "INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES ...
INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES ...";
echo "</pre>";

echo "<p><a href='./'>돌아가기</a></p>";