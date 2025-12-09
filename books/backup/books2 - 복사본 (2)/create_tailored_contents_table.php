<?php
/**
 * File: create_tailored_contents_table.php
 * Purpose: 맞춤형 컨텐츠 저장을 위한 테이블 생성
 * Location: /mnt/c/1 Project/augmented_teacher/books/create_tailored_contents_table.php
 *
 * Usage: 브라우저에서 직접 실행하여 테이블 생성
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

echo '<h2>맞춤형 컨텐츠 테이블 생성 스크립트</h2>';
echo '<pre>';

try {
    $tableName = 'abessi_tailoredcontents';

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

        // Moodle XMLDB API를 사용한 테이블 생성
        $dbman = $DB->get_manager();

        // XMLDB 테이블 정의
        $table = new xmldb_table($tableName);

        // 필드 정의
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contentstype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('contentsid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('nstep', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('qstn0', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('qstn1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('qstn2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('qstn3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('ans0', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('ans1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('ans2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('ans3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // 키 정의
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // 인덱스 정의
        $table->add_index('unique_content_step', XMLDB_INDEX_UNIQUE, array('contentsid', 'contentstype', 'nstep'));
        $table->add_index('idx_contentsid', XMLDB_INDEX_NOTUNIQUE, array('contentsid'));
        $table->add_index('idx_contentstype', XMLDB_INDEX_NOTUNIQUE, array('contentstype'));
        $table->add_index('idx_nstep', XMLDB_INDEX_NOTUNIQUE, array('nstep'));
        $table->add_index('idx_timecreated', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));
        $table->add_index('idx_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // 테이블 생성
        $dbman->create_table($table);

        echo "[Success] 테이블 '{$tableName}'이(가) 성공적으로 생성되었습니다.\n";
        echo "\n테이블 구조:\n";
        echo "- id: 고유 ID (자동 증가)\n";
        echo "- contentstype: 컨텐츠 타입 (1=icontent, 2=question)\n";
        echo "- contentsid: 컨텐츠 ID\n";
        echo "- nstep: 구간 번호 (1, 2, 3...)\n";
        echo "- qstn0: 자세히 생각하기 내용\n";
        echo "- qstn1~3: 추가 질문 3개\n";
        echo "- ans0: 자세히 생각하기 답변\n";
        echo "- ans1~3: 추가 질문 답변 3개\n";
        echo "- timemodified: 수정 시간 (unixtime)\n";
        echo "- timecreated: 생성 시간 (unixtime)\n";
    }

    // 테이블 정보 출력
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "테이블 정보:\n";
    echo str_repeat('=', 60) . "\n";

    $columns = $DB->get_columns($tableName);
    foreach ($columns as $column) {
        echo sprintf(
            "- %-20s %s(%s)\n",
            $column->name,
            $column->meta_type,
            $column->max_length
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
echo '<p><a href="drillingmath.php?cid=29566&ctype=1&section=0&nstep=1">← 돌아가기</a></p>';
