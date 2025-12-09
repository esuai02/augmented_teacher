<?php
/**
 * 누락된 테이블 생성 스크립트
 * alt42i_audio_files 테이블을 포함한 모든 필요한 테이블 생성
 */

require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $CFG;

require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>누락된 테이블 생성</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
        .warning { background-color: #fff3cd; color: #856404; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>누락된 테이블 생성</h1>
    
    <?php
    $dbman = $DB->get_manager();
    
    // 1. alt42i_audio_files 테이블 생성
    echo "<h2>1. alt42i_audio_files 테이블</h2>";
    
    if (!$dbman->table_exists('alt42i_audio_files')) {
        try {
            // XMLDBTable 객체 생성
            $table = new xmldb_table('alt42i_audio_files');
            
            // 필드 추가
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('pattern_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('file_type', XMLDB_TYPE_CHAR, '50', null, null, null, 'primary');
            $table->add_field('file_path', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('duration', XMLDB_TYPE_CHAR, '10', null, null, null, null);
            
            // 키 추가
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('pattern_id', XMLDB_KEY_FOREIGN, array('pattern_id'), 'alt42i_math_patterns', array('id'));
            
            // 인덱스 추가
            $table->add_index('pattern_type', XMLDB_INDEX_NOTUNIQUE, array('pattern_id', 'file_type'));
            
            // 테이블 생성
            $dbman->create_table($table);
            
            echo "<div class='status success'>✓ alt42i_audio_files 테이블이 생성되었습니다.</div>";
            
        } catch (Exception $e) {
            echo "<div class='status error'>✗ 테이블 생성 실패: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='status info'>- alt42i_audio_files 테이블이 이미 존재합니다.</div>";
    }
    
    // 2. 다른 필요한 테이블들도 확인 및 생성
    echo "<h2>2. 기타 필요한 테이블 확인</h2>";
    
    // alt42i_user_pattern_progress 테이블
    if (!$dbman->table_exists('alt42i_user_pattern_progress')) {
        try {
            $table = new xmldb_table('alt42i_user_pattern_progress');
            
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pattern_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('is_collected', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
            $table->add_field('mastery_level', XMLDB_TYPE_INTEGER, '3', null, null, null, '0');
            $table->add_field('practice_count', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('last_practice_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('improvement_score', XMLDB_TYPE_NUMBER, '5,2', null, null, null, '0');
            
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
            $table->add_key('pattern_id', XMLDB_KEY_FOREIGN, array('pattern_id'), 'alt42i_math_patterns', array('id'));
            
            $table->add_index('user_pattern', XMLDB_INDEX_UNIQUE, array('userid', 'pattern_id'));
            
            $dbman->create_table($table);
            
            echo "<div class='status success'>✓ alt42i_user_pattern_progress 테이블이 생성되었습니다.</div>";
            
        } catch (Exception $e) {
            echo "<div class='status error'>✗ 테이블 생성 실패: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='status info'>- alt42i_user_pattern_progress 테이블이 이미 존재합니다.</div>";
    }
    
    // 3. 모든 테이블 상태 확인
    echo "<h2>3. 전체 테이블 상태</h2>";
    
    $required_tables = [
        'alt42i_pattern_categories' => '카테고리',
        'alt42i_math_patterns' => '수학 패턴',
        'alt42i_pattern_solutions' => '패턴 솔루션',
        'alt42i_audio_files' => '오디오 파일',
        'alt42i_user_pattern_progress' => '사용자 진행상황'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>테이블명</th><th>설명</th><th>상태</th><th>레코드 수</th></tr>";
    
    foreach ($required_tables as $table_name => $description) {
        $exists = $dbman->table_exists($table_name);
        $count = $exists ? $DB->count_records($table_name) : 0;
        
        echo "<tr>";
        echo "<td>{$table_name}</td>";
        echo "<td>{$description}</td>";
        echo "<td>" . ($exists ? '<span style="color: green;">✓ 존재</span>' : '<span style="color: red;">✗ 없음</span>') . "</td>";
        echo "<td>{$count}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 4. SQL로 직접 생성하는 대안
    if (isset($_GET['force_sql'])) {
        echo "<h2>4. SQL 직접 실행</h2>";
        
        $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}alt42i_audio_files (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            pattern_id BIGINT(10) NOT NULL,
            file_type VARCHAR(50) DEFAULT 'primary',
            file_path LONGTEXT,
            duration VARCHAR(10),
            PRIMARY KEY (id),
            KEY pattern_id (pattern_id),
            KEY pattern_type (pattern_id, file_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $DB->execute($sql);
            echo "<div class='status success'>✓ SQL로 테이블 생성 완료</div>";
        } catch (Exception $e) {
            echo "<div class='status error'>✗ SQL 실행 실패: " . $e->getMessage() . "</div>";
            echo "<pre>$sql</pre>";
        }
    }
    ?>
    
    <hr>
    <h3>다음 단계</h3>
    <p>
        <?php if (!$dbman->table_exists('alt42i_audio_files')): ?>
            <a href="?force_sql=1" class="button">SQL로 강제 생성 시도</a><br><br>
        <?php endif; ?>
        
        <a href="insert_60_personas_final.php">데이터 삽입 페이지로 돌아가기</a><br>
        <a href="check_db_status.php">데이터베이스 상태 확인</a>
    </p>
    
    <style>
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .button:hover {
            background: #0056b3;
        }
    </style>
</body>
</html>