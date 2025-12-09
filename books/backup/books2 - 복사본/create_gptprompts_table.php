<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

$sql = "CREATE TABLE IF NOT EXISTS mdl_gptprompts (
    id BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) NOT NULL,
    type VARCHAR(50) NOT NULL,
    prompttext LONGTEXT,
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) NOT NULL,
    INDEX idx_user_type (userid, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $DB->execute($sql);
    echo "✅ 테이블 생성 완료: mdl_gptprompts<br>";
    echo "<br>테이블 구조:<br>";
    echo "- id: AUTO_INCREMENT PRIMARY KEY<br>";
    echo "- userid: 사용자 ID<br>";
    echo "- type: 프롬프트 유형 (pmemory 등)<br>";
    echo "- prompttext: 프롬프트 내용<br>";
    echo "- timecreated: 생성 시간<br>";
    echo "- timemodified: 수정 시간<br>";
    echo "<br><a href='improveprompt.php'>프롬프트 편집 페이지로 이동</a>";
} catch (Exception $e) {
    echo "❌ 오류 발생: " . $e->getMessage();
}
?>


