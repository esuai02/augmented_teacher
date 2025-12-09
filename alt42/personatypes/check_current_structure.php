<?php
/**
 * 현재 데이터베이스 테이블 구조 확인 스크립트
 */

// Moodle 설정 파일 로드
require_once(__DIR__ . '/../../../../../../config.php');

// 관리자 권한 확인
require_login();
require_capability('moodle/site:config', context_system::instance());

echo "<h1>데이터베이스 테이블 구조 확인</h1>";

// 1. mdl_alt42i_math_patterns 테이블 확인
echo "<h2>1. mdl_alt42i_math_patterns 테이블</h2>";

// 테이블 존재 여부 확인
$table_exists = $DB->get_manager()->table_exists('alt42i_math_patterns');
if ($table_exists) {
    echo "<p style='color: green;'>✓ 테이블이 존재합니다.</p>";
    
    // 컬럼 정보 가져오기
    $columns = $DB->get_columns('mdl_alt42i_math_patterns');
    
    echo "<h3>현재 컬럼 구조:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL 허용</th><th>기본값</th><th>기타</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column->name}</strong></td>";
        echo "<td>{$column->type}";
        if ($column->max_length) echo "({$column->max_length})";
        echo "</td>";
        echo "<td>" . ($column->not_null ? 'NO' : 'YES') . "</td>";
        echo "<td>" . ($column->has_default ? $column->default_value : '-') . "</td>";
        echo "<td>" . ($column->primary_key ? 'PRIMARY KEY' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 데이터 샘플 확인
    $sample = $DB->get_records('mdl_alt42i_math_patterns', null, 'id', '*', 0, 3);
    if ($sample) {
        echo "<h3>데이터 샘플 (최대 3개):</h3>";
        echo "<pre>";
        foreach ($sample as $row) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "<p>현재 데이터가 없습니다.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ 테이블이 존재하지 않습니다.</p>";
}

// 2. mdl_alt42i_pattern_solutions 테이블 확인
echo "<h2>2. mdl_alt42i_pattern_solutions 테이블</h2>";

$table_exists = $DB->get_manager()->table_exists('alt42i_pattern_solutions');
if ($table_exists) {
    echo "<p style='color: green;'>✓ 테이블이 존재합니다.</p>";
    
    // 컬럼 정보 가져오기
    $columns = $DB->get_columns('mdl_alt42i_pattern_solutions');
    
    echo "<h3>현재 컬럼 구조:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL 허용</th><th>기본값</th><th>기타</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column->name}</strong></td>";
        echo "<td>{$column->type}";
        if ($column->max_length) echo "({$column->max_length})";
        echo "</td>";
        echo "<td>" . ($column->not_null ? 'NO' : 'YES') . "</td>";
        echo "<td>" . ($column->has_default ? $column->default_value : '-') . "</td>";
        echo "<td>" . ($column->primary_key ? 'PRIMARY KEY' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 데이터 샘플 확인
    $sample = $DB->get_records('mdl_alt42i_pattern_solutions', null, 'id', '*', 0, 3);
    if ($sample) {
        echo "<h3>데이터 샘플 (최대 3개):</h3>";
        echo "<pre>";
        foreach ($sample as $row) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "<p>현재 데이터가 없습니다.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ 테이블이 존재하지 않습니다.</p>";
}

// 3. SQL INSERT 문에서 필요한 필드 목록
echo "<h2>3. INSERT SQL 파일에서 필요한 필드</h2>";
echo "<h3>mdl_alt42i_math_patterns:</h3>";
echo "<ul>";
echo "<li>id</li>";
echo "<li>name</li>";
echo "<li>description</li>";
echo "<li>category_id</li>";
echo "<li>icon</li>";
echo "<li>priority</li>";
echo "<li>audio_time</li>";
echo "<li>created_at</li>";
echo "<li>updated_at</li>";
echo "</ul>";

echo "<h3>mdl_alt42i_pattern_solutions:</h3>";
echo "<ul>";
echo "<li>pattern_id</li>";
echo "<li>action</li>";
echo "<li>check_method</li>";
echo "<li>audio_script</li>";
echo "<li>teacher_dialog</li>";
echo "<li>created_at</li>";
echo "<li>updated_at</li>";
echo "</ul>";

// 4. 권장 조치사항 생성
echo "<h2>4. 권장 조치사항</h2>";

$math_patterns_columns = $DB->get_columns('mdl_alt42i_math_patterns');
$pattern_solutions_columns = $DB->get_columns('mdl_alt42i_pattern_solutions');

echo "<h3>필요한 ALTER 명령어:</h3>";
echo "<pre style='background-color: #f0f0f0; padding: 10px;'>";

// mdl_alt42i_math_patterns 테이블 조정
$has_name = isset($math_patterns_columns['name']);
$has_pattern_name = isset($math_patterns_columns['pattern_name']);
$has_description = isset($math_patterns_columns['description']);
$has_pattern_desc = isset($math_patterns_columns['pattern_desc']);

if (!$has_name && $has_pattern_name) {
    echo "ALTER TABLE mdl_alt42i_math_patterns CHANGE COLUMN pattern_name name VARCHAR(100) NOT NULL;\n";
} elseif (!$has_name && !$has_pattern_name) {
    echo "ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN name VARCHAR(100) NOT NULL;\n";
}

if (!$has_description && $has_pattern_desc) {
    echo "ALTER TABLE mdl_alt42i_math_patterns CHANGE COLUMN pattern_desc description TEXT NOT NULL;\n";
} elseif (!$has_description && !$has_pattern_desc) {
    echo "ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN description TEXT NOT NULL;\n";
}

// mdl_alt42i_pattern_solutions 테이블 조정
$has_check_method = isset($pattern_solutions_columns['check_method']);
if (!$has_check_method) {
    echo "ALTER TABLE mdl_alt42i_pattern_solutions ADD COLUMN check_method TEXT NOT NULL AFTER action;\n";
}

echo "</pre>";

echo "<p><a href='./'>돌아가기</a></p>";