<?php
// add_musicurl_column.php - mdl_icontent_pages 테이블에 musicurl 컬럼 추가
// Location: /mnt/c/1 Project/augmented_teacher/books/add_musicurl_column.php
// Error location: [FILE_PATH:LINE_NUMBER]

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자만 실행 가능
if (!is_siteadmin()) {
    die('[add_musicurl_column.php:12] 권한이 없습니다. 관리자만 실행 가능합니다.');
}

try {
    echo "<h2>mdl_icontent_pages 테이블에 musicurl 컬럼 추가</h2>";

    // 테이블 존재 확인
    $table_exists = $DB->get_record_sql(
        "SELECT COUNT(*) as cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mdl_icontent_pages'"
    );

    if ($table_exists->cnt == 0) {
        throw new Exception('[add_musicurl_column.php:26] mdl_icontent_pages 테이블이 존재하지 않습니다.');
    }

    echo "<p>✓ mdl_icontent_pages 테이블 확인 완료</p>";

    // 컬럼 존재 확인
    $column_exists = $DB->get_record_sql(
        "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'mdl_icontent_pages'
         AND COLUMN_NAME = 'musicurl'"
    );

    if ($column_exists->cnt > 0) {
        echo "<p style='color: orange;'>⚠ musicurl 컬럼이 이미 존재합니다.</p>";
        echo "<p>현재 설정:</p>";

        $column_info = $DB->get_record_sql(
            "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'mdl_icontent_pages'
             AND COLUMN_NAME = 'musicurl'"
        );

        echo "<ul>";
        echo "<li>타입: " . $column_info->COLUMN_TYPE . "</li>";
        echo "<li>NULL 허용: " . $column_info->IS_NULLABLE . "</li>";
        echo "<li>기본값: " . ($column_info->COLUMN_DEFAULT ?? 'NULL') . "</li>";
        echo "</ul>";

    } else {
        // 컬럼 추가
        echo "<p>musicurl 컬럼을 추가합니다...</p>";

        $DB->execute(
            "ALTER TABLE mdl_icontent_pages
             ADD COLUMN musicurl VARCHAR(500) NULL DEFAULT NULL
             COMMENT '배경음악 파일 URL'
             AFTER audiourl2"
        );

        echo "<p style='color: green;'>✓ musicurl 컬럼이 성공적으로 추가되었습니다.</p>";
        echo "<ul>";
        echo "<li>컬럼명: musicurl</li>";
        echo "<li>타입: VARCHAR(500)</li>";
        echo "<li>NULL 허용: YES</li>";
        echo "<li>기본값: NULL</li>";
        echo "<li>위치: audiourl2 다음</li>";
        echo "</ul>";
    }

    // 테이블 구조 확인
    echo "<h3>현재 mdl_icontent_pages 테이블 구조 (오디오 관련 필드)</h3>";
    $columns = $DB->get_records_sql(
        "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'mdl_icontent_pages'
         AND COLUMN_NAME LIKE '%audio%' OR COLUMN_NAME = 'musicurl'
         ORDER BY ORDINAL_POSITION"
    );

    if ($columns) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>컬럼명</th><th>타입</th><th>NULL 허용</th><th>기본값</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col->COLUMN_NAME . "</td>";
            echo "<td>" . $col->COLUMN_TYPE . "</td>";
            echo "<td>" . $col->IS_NULLABLE . "</td>";
            echo "<td>" . ($col->COLUMN_DEFAULT ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✓ 작업이 완료되었습니다!</p>";
    echo "<p><a href='mynote2.php'>mynote2.php로 돌아가기</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ 오류 발생: " . $e->getMessage() . "</p>";
    echo "<p>상세 정보:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
