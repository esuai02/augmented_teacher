<?php
// Moodle 설정 파일 포함
require_once('/home/moodle/public_html/moodle/config.php');
global $DB;

// 관리자 권한 확인
require_login();
require_capability('moodle/site:config', context_system::instance());

echo "<h2>mdl_abessi_exam_selections 테이블 생성</h2>";

try {
    // 테이블이 이미 존재하는지 확인
    $table_exists = false;
    $tables = $DB->get_tables();
    if (in_array('abessi_exam_selections', $tables)) {
        $table_exists = true;
        echo "<p style='color: orange;'>⚠️ 테이블이 이미 존재합니다.</p>";
    }
    
    if (!$table_exists) {
        // 테이블 생성 SQL
        $sql = "CREATE TABLE IF NOT EXISTS {abessi_exam_selections} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            userid BIGINT(10) NOT NULL,
            cid VARCHAR(100) NOT NULL,
            selections LONGTEXT NOT NULL,
            timecreated BIGINT(10) NOT NULL,
            timemodified BIGINT(10) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY mdl_abes_examsel_usecid_uix (userid, cid),
            KEY mdl_abes_examsel_use_ix (userid),
            KEY mdl_abes_examsel_cid_ix (cid),
            KEY mdl_abes_examsel_tim_ix (timemodified)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // 테이블 생성 실행
        $DB->execute($sql);
        
        echo "<p style='color: green;'>✅ 테이블이 성공적으로 생성되었습니다!</p>";
    }
    
    // 테이블 구조 확인
    echo "<h3>테이블 구조:</h3>";
    echo "<pre>";
    echo "테이블명: mdl_abessi_exam_selections\n\n";
    echo "컬럼:\n";
    echo "- id: 자동 증가 기본키\n";
    echo "- userid: 학생 ID\n";
    echo "- cid: 과목 ID\n";
    echo "- selections: JSON 형식의 선택 데이터\n";
    echo "- timecreated: 생성 시간\n";
    echo "- timemodified: 수정 시간\n\n";
    echo "인덱스:\n";
    echo "- PRIMARY KEY (id)\n";
    echo "- UNIQUE KEY (userid, cid)\n";
    echo "- INDEX (userid)\n";
    echo "- INDEX (cid)\n";
    echo "- INDEX (timemodified)\n";
    echo "</pre>";
    
    // 테이블 상태 확인
    $count = $DB->count_records('abessi_exam_selections');
    echo "<p>현재 저장된 레코드 수: <strong>$count</strong>개</p>";
    
    // 샘플 데이터 확인
    if ($count > 0) {
        echo "<h3>최근 5개 레코드:</h3>";
        $records = $DB->get_records('abessi_exam_selections', null, 'timemodified DESC', '*', 0, 5);
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>User ID</th><th>CID</th><th>Selections (일부)</th><th>Modified</th></tr>";
        foreach ($records as $record) {
            $selections_preview = substr($record->selections, 0, 50) . '...';
            $modified_date = date('Y-m-d H:i:s', $record->timemodified);
            echo "<tr>";
            echo "<td>$record->id</td>";
            echo "<td>$record->userid</td>";
            echo "<td>$record->cid</td>";
            echo "<td>$selections_preview</td>";
            echo "<td>$modified_date</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류 발생: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='missionhome.php?cid=1&mtid=3&tb=0'>내신대비 페이지로 돌아가기</a></p>";
?>