<?php
/**
 * 테이블 존재 확인
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;

echo "<h1>테이블 존재 확인</h1>";

$tables = [
    'mdl_alt42_experiments',
    'mdl_alt42_hypotheses',
    'mdl_alt42_experiment_logs',
    'mdl_alt42_field_descriptions'
];

echo "<h2>테이블 존재 여부</h2>";
foreach ($tables as $table) {
    try {
        $result = $DB->get_records_sql("SHOW TABLES LIKE '$table'");
        if (empty($result)) {
            echo "<p style='color: red;'>❌ $table - 존재하지 않음</p>";
        } else {
            echo "<p style='color: green;'>✅ $table - 존재함</p>";
            
            // 테이블 구조 확인
            $structure = $DB->get_records_sql("DESCRIBE $table");
            echo "<details>";
            echo "<summary>$table 구조 보기</summary>";
            echo "<pre>";
            foreach ($structure as $field) {
                echo $field->field . " - " . $field->type . " - " . ($field->null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
            }
            echo "</pre>";
            echo "</details>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $table - 오류: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>샘플 데이터 확인</h2>";

// 실험 데이터 확인
try {
    $experiments = $DB->get_records_sql("SELECT * FROM mdl_alt42_experiments ORDER BY timecreated DESC LIMIT 3");
    echo "<h3>최근 실험 3개:</h3>";
    if (empty($experiments)) {
        echo "<p>실험 데이터가 없습니다.</p>";
    } else {
        echo "<pre>";
        foreach ($experiments as $exp) {
            echo "ID: {$exp->id}, 이름: {$exp->experiment_name}, 생성일: " . date('Y-m-d H:i:s', $exp->timecreated) . "\n";
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>실험 데이터 조회 오류: " . $e->getMessage() . "</p>";
}

// 가설 데이터 확인
try {
    $hypotheses = $DB->get_records_sql("SELECT * FROM mdl_alt42_hypotheses ORDER BY timecreated DESC LIMIT 3");
    echo "<h3>최근 가설 3개:</h3>";
    if (empty($hypotheses)) {
        echo "<p>가설 데이터가 없습니다.</p>";
    } else {
        echo "<pre>";
        foreach ($hypotheses as $hyp) {
            echo "ID: {$hyp->id}, 실험ID: {$hyp->experiment_id}, 가설: {$hyp->hypothesis_text}, 생성일: " . date('Y-m-d H:i:s', $hyp->timecreated) . "\n";
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>가설 데이터 조회 오류: " . $e->getMessage() . "</p>";
}

echo "<h2>DB 연결 정보</h2>";
echo "<p>Host: " . $CFG->dbhost . "</p>";
echo "<p>Database: " . $CFG->dbname . "</p>";
echo "<p>User: " . $CFG->dbuser . "</p>";
echo "<p>Current User ID: " . $USER->id . "</p>";

echo "<h2>테이블 생성 SQL (필요시 사용)</h2>";
echo "<textarea style='width: 100%; height: 300px;'>";
echo "-- 가설 테이블이 없는 경우 실행하세요
CREATE TABLE IF NOT EXISTS mdl_alt42_hypotheses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    hypothesis_text TEXT NOT NULL COMMENT '가설 내용',
    hypothesis_type ENUM('primary', 'secondary', 'exploratory') DEFAULT 'primary' COMMENT '가설 유형',
    status ENUM('proposed', 'tested', 'confirmed', 'rejected') DEFAULT 'proposed' COMMENT '가설 상태',
    evidence TEXT DEFAULT NULL COMMENT '증거/근거',
    author_id INT NOT NULL COMMENT '작성자 ID',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_hypothesis_type (hypothesis_type),
    INDEX idx_status (status),
    INDEX idx_author_id (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='가설 기록';";
echo "</textarea>";
?>