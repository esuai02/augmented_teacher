<?php
/**
 * Diagnostic tool to check DB records for specific nstep values
 *
 * @file books/check_db_nstep.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 29566;
$ctype = isset($_GET['ctype']) ? intval($_GET['ctype']) : 1;
$nstep = isset($_GET['nstep']) ? intval($_GET['nstep']) : 2;

?>
<!DOCTYPE html>
<html>
<head>
    <title>DB nstep 진단</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .empty { color: #999; font-style: italic; }
        .found { color: #4CAF50; font-weight: bold; }
        .notfound { color: #f44336; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 DB nstep 진단 도구</h1>

    <h2>검색 파라미터</h2>
    <ul>
        <li><strong>contentsid:</strong> <?php echo $cid; ?></li>
        <li><strong>contentstype:</strong> <?php echo $ctype; ?></li>
        <li><strong>nstep:</strong> <?php echo $nstep; ?></li>
    </ul>

    <?php
    // 특정 nstep 레코드 조회
    $record = $DB->get_record('abessi_tailoredcontents', array(
        'contentsid' => $cid,
        'contentstype' => $ctype,
        'nstep' => $nstep
    ));

    if ($record) {
        echo '<h2 class="found">✅ 레코드 발견 (id=' . $record->id . ')</h2>';

        echo '<table>';
        echo '<tr><th>필드</th><th>상태</th><th>내용 미리보기</th></tr>';

        $fields = ['qstn0', 'qstn1', 'ans1', 'qstn2', 'ans2', 'qstn3', 'ans3'];
        foreach ($fields as $field) {
            $value = isset($record->$field) ? $record->$field : '';
            $isEmpty = empty($value);
            $status = $isEmpty ? '<span class="empty">EMPTY</span>' : '<span class="found">있음</span>';
            $preview = $isEmpty ? '-' : htmlspecialchars(mb_substr($value, 0, 100)) . '...';

            echo "<tr>";
            echo "<td><strong>$field</strong></td>";
            echo "<td>$status</td>";
            echo "<td>$preview</td>";
            echo "</tr>";
        }
        echo '</table>';

        // 전체 레코드 JSON 출력
        echo '<h3>전체 레코드 (JSON)</h3>';
        echo '<pre>' . json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</pre>';

    } else {
        echo '<h2 class="notfound">❌ 레코드 없음</h2>';
        echo '<p>해당 조건으로 DB에 레코드가 존재하지 않습니다.</p>';
    }

    // 같은 cid, ctype의 모든 nstep 조회
    echo '<h2>📊 같은 컨텐츠의 모든 nstep 레코드</h2>';

    $allRecords = $DB->get_records('abessi_tailoredcontents', array(
        'contentsid' => $cid,
        'contentstype' => $ctype
    ), 'nstep ASC');

    if ($allRecords && count($allRecords) > 0) {
        echo '<table>';
        echo '<tr><th>nstep</th><th>id</th><th>qstn0</th><th>qstn1</th><th>ans1</th><th>qstn2</th><th>ans2</th><th>qstn3</th><th>ans3</th></tr>';

        foreach ($allRecords as $rec) {
            echo '<tr>';
            echo '<td><strong>' . ($rec->nstep ?? 'NULL') . '</strong></td>';
            echo '<td>' . $rec->id . '</td>';

            $fields = ['qstn0', 'qstn1', 'ans1', 'qstn2', 'ans2', 'qstn3', 'ans3'];
            foreach ($fields as $field) {
                $value = isset($rec->$field) ? $rec->$field : '';
                $isEmpty = empty($value);
                $cell = $isEmpty ? '<span class="empty">EMPTY</span>' : '✓ (' . mb_strlen($value) . ' chars)';
                echo "<td>$cell</td>";
            }
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="notfound">해당 컨텐츠에 대한 레코드가 전혀 없습니다.</p>';
    }
    ?>

    <h2>🔗 테스트 링크</h2>
    <ul>
        <li><a href="?cid=29566&ctype=1&nstep=1">nstep=1 확인</a></li>
        <li><a href="?cid=29566&ctype=1&nstep=2">nstep=2 확인</a></li>
        <li><a href="?cid=29566&ctype=1&nstep=3">nstep=3 확인</a></li>
        <li><a href="?cid=29566&ctype=1&nstep=4">nstep=4 확인</a></li>
    </ul>
</body>
</html>
