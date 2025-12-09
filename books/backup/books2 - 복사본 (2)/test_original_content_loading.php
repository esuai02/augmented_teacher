<?php
/**
 * Test script for original content loading functionality
 *
 * @file books/test_original_content_loading.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 106;
$ctype = isset($_GET['ctype']) ? intval($_GET['ctype']) : 1;

?>
<!DOCTYPE html>
<html>
<head>
    <title>원본 컨텐츠 로딩 테스트</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .section h2 { margin-top: 0; color: #333; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>🧪 원본 컨텐츠 로딩 테스트</h1>

    <div class="section info">
        <h2>테스트 파라미터</h2>
        <table>
            <tr>
                <th>파라미터</th>
                <th>값</th>
            </tr>
            <tr>
                <td>contentsid</td>
                <td><?php echo $cid; ?></td>
            </tr>
            <tr>
                <td>contentstype</td>
                <td><?php echo $ctype; ?> (<?php echo $ctype == 1 ? 'icontent_pages' : 'question'; ?>)</td>
            </tr>
        </table>
    </div>

    <?php
    // Test content loading logic
    $originalContent = '';
    $loadSuccess = false;
    $errorMessage = '';

    try {
        if ($ctype == 1) {
            echo '<div class="section info">';
            echo '<h2>📚 mdl_icontent_pages 테이블 조회</h2>';

            $page = $DB->get_record('icontent_pages', array('id' => $cid), 'id, title, maintext');

            if ($page) {
                $originalContent = $page->maintext;
                $loadSuccess = true;

                echo '<table>';
                echo '<tr><th>필드</th><th>값</th></tr>';
                echo '<tr><td>id</td><td>' . $page->id . '</td></tr>';
                echo '<tr><td>title</td><td>' . htmlspecialchars($page->title ?? 'N/A') . '</td></tr>';
                echo '<tr><td>maintext 길이</td><td>' . strlen($originalContent) . ' bytes</td></tr>';
                echo '</table>';
            } else {
                $errorMessage = "ID {$cid}에 해당하는 레코드를 찾을 수 없습니다.";
            }
            echo '</div>';

        } elseif ($ctype == 2) {
            echo '<div class="section info">';
            echo '<h2>❓ mdl_question 테이블 조회</h2>';

            $question = $DB->get_record('question', array('id' => $cid), 'id, name, mathexpression');

            if ($question) {
                $originalContent = $question->mathexpression;
                $loadSuccess = true;

                echo '<table>';
                echo '<tr><th>필드</th><th>값</th></tr>';
                echo '<tr><td>id</td><td>' . $question->id . '</td></tr>';
                echo '<tr><td>name</td><td>' . htmlspecialchars($question->name ?? 'N/A') . '</td></tr>';
                echo '<tr><td>mathexpression 길이</td><td>' . strlen($originalContent) . ' bytes</td></tr>';
                echo '</table>';
            } else {
                $errorMessage = "ID {$cid}에 해당하는 레코드를 찾을 수 없습니다.";
            }
            echo '</div>';
        }

        // Display results
        if ($loadSuccess) {
            echo '<div class="section success">';
            echo '<h2>✅ 로딩 성공</h2>';
            echo '<p><strong>원본 컨텐츠 길이:</strong> ' . strlen($originalContent) . ' bytes</p>';
            echo '<h3>내용 미리보기 (처음 1000자):</h3>';
            echo '<pre>' . htmlspecialchars(mb_substr($originalContent, 0, 1000)) . '</pre>';
            echo '</div>';

            // Test if content would be included in prompt
            if (!empty($originalContent)) {
                echo '<div class="section success">';
                echo '<h2>📝 AI 프롬프트 포함 여부</h2>';
                echo '<p>✅ 원본 컨텐츠가 AI 프롬프트에 포함됩니다.</p>';
                echo '</div>';
            } else {
                echo '<div class="section error">';
                echo '<h2>⚠️ AI 프롬프트 포함 여부</h2>';
                echo '<p>❌ 원본 컨텐츠가 비어있어 AI 프롬프트에서 제외됩니다.</p>';
                echo '</div>';
            }
        } else {
            echo '<div class="section error">';
            echo '<h2>❌ 로딩 실패</h2>';
            echo '<p>' . htmlspecialchars($errorMessage) . '</p>';
            echo '</div>';
        }

    } catch (Exception $e) {
        echo '<div class="section error">';
        echo '<h2>❌ 오류 발생</h2>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '</div>';
    }
    ?>

    <div class="section info">
        <h2>🔗 테스트 링크</h2>
        <ul>
            <li><a href="?cid=106&ctype=1">contentstype=1 (icontent_pages) - ID 106</a></li>
            <li><a href="?cid=29566&ctype=1">contentstype=1 (icontent_pages) - ID 29566</a></li>
            <li><a href="?cid=100&ctype=2">contentstype=2 (question) - ID 100</a></li>
        </ul>
    </div>

</body>
</html>
