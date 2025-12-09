<?php
/**
 * File: create_thinking_table.php
 * Purpose: '자세히 생각하기' 저장을 위한 테이블 생성
 * Location: /mnt/c/1 Project/augmented_teacher/books/create_thinking_table.php
 *
 * Usage: 브라우저에서 직접 실행하여 테이블 생성
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

echo '<h2>자세히 생각하기 테이블 생성 스크립트</h2>';
echo '<pre>';

try {
    $tableName = 'abrainalignment_thinking';

    // 테이블 존재 여부 확인
    $tableExists = $DB->get_manager()->table_exists($tableName);

    if ($tableExists) {
        echo "[Info] 테이블 '{$tableName}'이(가) 이미 존재합니다.\n";
        echo "기존 테이블 구조 확인 중...\n\n";

        // 기존 레코드 수 확인
        $count = $DB->count_records($tableName);
        echo "현재 레코드 수: {$count}\n";

    } else {
        echo "[Info] 테이블 '{$tableName}'을(를) 생성합니다...\n";

        // 테이블 생성 SQL
        $sql = "CREATE TABLE IF NOT EXISTS mdl_{$tableName} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            contentsid BIGINT(10) NOT NULL DEFAULT 0,
            contentstype TINYINT(2) NOT NULL DEFAULT 0,
            thinking LONGTEXT NOT NULL,
            questions TEXT NOT NULL,
            userid BIGINT(10) NOT NULL DEFAULT 0,
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_contentsid (contentsid),
            KEY idx_contentstype (contentstype),
            KEY idx_userid (userid),
            KEY idx_timecreated (timecreated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='자세히 생각하기 AI 생성 내용 저장';";

        $DB->execute($sql);

        echo "[Success] 테이블 '{$tableName}'이(가) 성공적으로 생성되었습니다.\n";
        echo "\n테이블 구조:\n";
        echo "- id: 고유 ID (자동 증가)\n";
        echo "- contentsid: 컨텐츠 ID\n";
        echo "- contentstype: 컨텐츠 타입 (1=icontent, 2=question)\n";
        echo "- thinking: '자세히 생각하기' AI 생성 내용\n";
        echo "- questions: 추가 질문 3개 (JSON 배열)\n";
        echo "- userid: 사용자 ID\n";
        echo "- timecreated: 생성 시간\n";
    }

    // 테이블 정보 출력
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "테이블 정보:\n";
    echo str_repeat('=', 60) . "\n";

    $columns = $DB->get_columns($tableName);
    foreach ($columns as $column) {
        echo sprintf(
            "- %-20s %s\n",
            $column->name,
            $column->meta_type . '(' . $column->max_length . ')'
        );
    }

    echo "\n[완료] 스크립트 실행이 완료되었습니다.\n";

} catch (Exception $e) {
    echo "[Error] 오류 발생:\n";
    echo "파일: " . basename(__FILE__) . "\n";
    echo "라인: " . $e->getLine() . "\n";
    echo "메시지: " . $e->getMessage() . "\n";
    echo "\n스택 트레이스:\n";
    echo $e->getTraceAsString() . "\n";
}

echo '</pre>';
echo '<p><a href="drillingmath.php?cid=29566&ctype=1&section=0">← 돌아가기</a></p>';
