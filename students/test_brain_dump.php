<?php
// Brain Dump 테이블 생성 및 테스트 페이지
// URL: http://localhost/path/to/students/test_brain_dump.php?id=학생번호

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

echo "<h2>🔧 Brain Dump 테이블 생성 및 테스트</h2>";

// 테이블 생성
$sql = "CREATE TABLE IF NOT EXISTS mdl_abessi_brain_dump (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    tags LONGTEXT,
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY userid (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $DB->execute($sql);
    echo "<p style='color: green;'>✅ mdl_abessi_brain_dump 테이블이 성공적으로 생성되었습니다!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 테이블 생성 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 테이블 존재 확인
try {
    $tableExists = $DB->get_manager()->table_exists('abessi_brain_dump');
    if ($tableExists) {
        echo "<p style='color: green;'>✅ 테이블이 존재함을 확인했습니다.</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ 테이블 존재 여부를 확인할 수 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: orange;'>⚠️ 테이블 확인 중 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 테스트 데이터 삽입
if (isset($_GET['id'])) {
    $userid = intval($_GET['id']);
    echo "<h3>테스트 데이터 삽입 (사용자 ID: $userid)</h3>";
    
    $testData = new stdClass();
    $testData->userid = $userid;
    $testData->tags = '["테스트1", "테스트2", "수학"]';
    $testData->timecreated = time();
    
    try {
        // 기존 데이터 삭제
        $DB->delete_records('mdl_abessi_brain_dump', array('userid' => $userid));
        
        // 새 데이터 삽입
        $result = $DB->insert_record('mdl_abessi_brain_dump', $testData);
        if ($result) {
            echo "<p style='color: green;'>✅ 테스트 데이터가 성공적으로 삽입되었습니다! (ID: $result)</p>";
            
            // 데이터 조회 테스트
            $retrieved = $DB->get_record('mdl_abessi_brain_dump', array('userid' => $userid));
            if ($retrieved) {
                echo "<p style='color: green;'>✅ 데이터 조회 성공: " . htmlspecialchars($retrieved->tags) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ 테스트 데이터 삽입 실패</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 테스트 데이터 처리 중 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>테스트 데이터를 삽입하려면 URL에 ?id=학생번호를 추가하세요.</p>";
    echo "<p>예: test_brain_dump.php?id=123</p>";
}

echo "<hr>";
echo "<p><a href='integrated_goals.php" . (isset($_GET['id']) ? "?id=" . $_GET['id'] : "") . "'>← 통합 목표 관리로 돌아가기</a></p>";
?> 