<?php
/**
 * icontent_pages와 question 테이블의 audiourl 컬럼 확인
 * File: verify_audiourl_column.php
 * Location: /books/verify_audiourl_column.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

echo '<h2>audiourl 컬럼 확인 스크립트</h2>';
echo '<p>audiourl은 <strong>icontent_pages</strong>와 <strong>question</strong> 테이블에 존재합니다.</p>';

try {
    $dbman = $DB->get_manager();

    // 1. icontent_pages 테이블 확인
    echo '<hr><h3>1. icontent_pages 테이블</h3>';
    $table1 = new xmldb_table('icontent_pages');

    if ($dbman->table_exists($table1)) {
        echo '<p style="color: green;">✅ icontent_pages 테이블이 존재합니다.</p>';

        // 컬럼 정보 확인
        $columns = $DB->get_columns('icontent_pages');
        $hasAudioUrl = false;

        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>컬럼명</th><th>타입</th><th>Null 허용</th><th>기본값</th></tr>';

        foreach ($columns as $column) {
            if ($column->name === 'audiourl') {
                $hasAudioUrl = true;
            }
            $highlight = ($column->name === 'audiourl') ? ' style="background-color: #90EE90;"' : '';
            echo '<tr' . $highlight . '>';
            echo '<td>' . $column->name . '</td>';
            echo '<td>' . $column->type . '</td>';
            echo '<td>' . ($column->not_null ? 'NO' : 'YES') . '</td>';
            echo '<td>' . ($column->default_value ?? 'NULL') . '</td>';
            echo '</tr>';
        }

        echo '</table>';

        if ($hasAudioUrl) {
            echo '<p style="color: green;">✅ audiourl 컬럼이 존재합니다!</p>';

            // 샘플 데이터 확인
            $records = $DB->get_records_sql(
                "SELECT id, title, audiourl
                 FROM {icontent_pages}
                 WHERE audiourl IS NOT NULL AND audiourl != ''
                 ORDER BY id DESC
                 LIMIT 5"
            );

            if ($records) {
                echo '<h4>audiourl 값이 있는 레코드 샘플 (최근 5개):</h4>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<tr><th>ID</th><th>Title</th><th>Audio URL</th></tr>';

                foreach ($records as $record) {
                    echo '<tr>';
                    echo '<td>' . $record->id . '</td>';
                    echo '<td>' . $record->title . '</td>';
                    echo '<td><a href="' . $record->audiourl . '" target="_blank">🔊 재생</a></td>';
                    echo '</tr>';
                }

                echo '</table>';
            } else {
                echo '<p>audiourl 값이 있는 레코드가 없습니다.</p>';
            }
        } else {
            echo '<p style="color: red;">❌ audiourl 컬럼이 없습니다!</p>';
        }
    } else {
        echo '<p style="color: red;">❌ icontent_pages 테이블이 존재하지 않습니다.</p>';
    }

    // 2. question 테이블 확인
    echo '<hr><h3>2. question 테이블</h3>';
    $table2 = new xmldb_table('question');

    if ($dbman->table_exists($table2)) {
        echo '<p style="color: green;">✅ question 테이블이 존재합니다.</p>';

        // 컬럼 정보 확인
        $columns = $DB->get_columns('question');
        $hasAudioUrl = false;

        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>컬럼명</th><th>타입</th><th>Null 허용</th><th>기본값</th></tr>';

        $displayCount = 0;
        foreach ($columns as $column) {
            if ($column->name === 'audiourl') {
                $hasAudioUrl = true;
            }
            // audiourl이거나 처음 10개 컬럼만 표시
            if ($column->name === 'audiourl' || $displayCount < 10) {
                $highlight = ($column->name === 'audiourl') ? ' style="background-color: #90EE90;"' : '';
                echo '<tr' . $highlight . '>';
                echo '<td>' . $column->name . '</td>';
                echo '<td>' . $column->type . '</td>';
                echo '<td>' . ($column->not_null ? 'NO' : 'YES') . '</td>';
                echo '<td>' . ($column->default_value ?? 'NULL') . '</td>';
                echo '</tr>';
                $displayCount++;
            }
        }

        echo '<tr><td colspan="4"><em>... (총 ' . count($columns) . '개 컬럼)</em></td></tr>';
        echo '</table>';

        if ($hasAudioUrl) {
            echo '<p style="color: green;">✅ audiourl 컬럼이 존재합니다!</p>';

            // 샘플 데이터 확인
            $records = $DB->get_records_sql(
                "SELECT id, name, audiourl
                 FROM {question}
                 WHERE audiourl IS NOT NULL AND audiourl != ''
                 ORDER BY id DESC
                 LIMIT 5"
            );

            if ($records) {
                echo '<h4>audiourl 값이 있는 레코드 샘플 (최근 5개):</h4>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<tr><th>ID</th><th>Name</th><th>Audio URL</th></tr>';

                foreach ($records as $record) {
                    echo '<tr>';
                    echo '<td>' . $record->id . '</td>';
                    echo '<td>' . htmlspecialchars(substr($record->name, 0, 50)) . '...</td>';
                    echo '<td><a href="' . $record->audiourl . '" target="_blank">🔊 재생</a></td>';
                    echo '</tr>';
                }

                echo '</table>';
            } else {
                echo '<p>audiourl 값이 있는 레코드가 없습니다.</p>';
            }
        } else {
            echo '<p style="color: red;">❌ audiourl 컬럼이 없습니다!</p>';
        }
    } else {
        echo '<p style="color: red;">❌ question 테이블이 존재하지 않습니다.</p>';
    }

    echo '<hr>';
    echo '<h3>✅ 검증 완료</h3>';
    echo '<p><strong>정리:</strong></p>';
    echo '<ul>';
    echo '<li>audiourl 필드는 <code>mdl_icontent_pages</code>와 <code>mdl_question</code> 테이블에 존재합니다.</li>';
    echo '<li>contentstype이 2가 아닌 경우: icontent_pages 테이블 사용</li>';
    echo '<li>contentstype이 2인 경우: question 테이블 사용</li>';
    echo '</ul>';
    echo '<p><a href="openai_tts.php?cid=1&ctype=1&type=conversation" style="padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">TTS 페이지로 이동 (테스트)</a></p>';

} catch (Exception $e) {
    echo '<p style="color: red;">❌ 오류 발생: ' . $e->getMessage() . '</p>';
    echo '<p>파일: verify_audiourl_column.php, 위치: 예외 처리</p>';
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5; }
    h2 { color: #333; }
    h3 { color: #666; margin-top: 20px; }
    h4 { color: #888; margin-top: 15px; }
    table { border-collapse: collapse; margin-top: 10px; background-color: white; }
    th { background-color: #4CAF50; color: white; padding: 8px; }
    td { padding: 5px; }
    hr { margin: 30px 0; border: none; border-top: 2px solid #ddd; }
    code { background-color: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
</style>
