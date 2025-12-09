<?php
// Brain Dump 테이블 생성 스크립트
// 이 파일을 한 번 실행하여 테이블을 생성하세요

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

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
    echo "✅ mdl_abessi_brain_dump 테이블이 성공적으로 생성되었습니다!\n";
    echo "이제 이 파일을 삭제하거나 다른 곳으로 이동하세요.\n";
} catch (Exception $e) {
    echo "❌ 테이블 생성 실패: " . $e->getMessage() . "\n";
}
?> 