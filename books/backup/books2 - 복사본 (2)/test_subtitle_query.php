<?php
/**
 * Subtitle Data Verification Script
 * File: test_subtitle_query.php
 * Purpose: Verify mdl_abrainalignment_gptresults subtitle data for mynote2.php
 *
 * Usage: https://mathking.kr/moodle/local/augmented_teacher/books/test_subtitle_query.php?cid=106
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get contentsid from URL parameter
$contentsid = $_GET['cid'] ?? null;

if (!$contentsid) {
    die('Error: contentsid (cid) parameter is required. Usage: test_subtitle_query.php?cid=106');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Subtitle Data Verification</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #4CAF50;
            margin-top: 30px;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin: 15px 0;
        }
        .success-box {
            background: #e8f5e9;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            margin: 15px 0;
        }
        .error-box {
            background: #ffebee;
            padding: 15px;
            border-left: 4px solid #f44336;
            margin: 15px 0;
        }
        .warning-box {
            background: #fff3e0;
            padding: 15px;
            border-left: 4px solid #ff9800;
            margin: 15px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .data-table th {
            background: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .data-table tr:hover {
            background: #f5f5f5;
        }
        .subtitle-preview {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        code {
            background: #272822;
            color: #f8f8f2;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .query-box {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Subtitle Data Verification Report</h1>";
echo "<div class='info-box'><strong>contentsid:</strong> $contentsid</div>";

// Query 1: Check mdl_abrainalignment_gptresults
echo "<h2>1️⃣ mdl_abrainalignment_gptresults 테이블 확인</h2>";

$gptQuery = "SELECT id, type, contentsid, contentstype, gid, outputtext, timecreated, timemodified
             FROM mdl_abrainalignment_gptresults
             WHERE type = ?
               AND contentsid = ?
               AND contentstype = 1
               AND gid = 71280
             ORDER BY id DESC
             LIMIT 1";

echo "<div class='query-box'><strong>Query:</strong><br>" . htmlspecialchars($gptQuery) . "<br><strong>Parameters:</strong> ['conversation', $contentsid]</div>";

try {
    $gptSubtitle = $DB->get_record_sql($gptQuery, ['conversation', $contentsid]);

    if ($gptSubtitle) {
        echo "<div class='success-box'>✅ <strong>GPT 자막 데이터 발견!</strong></div>";

        echo "<table class='data-table'>
            <tr>
                <th>Field</th>
                <th>Value</th>
            </tr>
            <tr>
                <td><strong>ID</strong></td>
                <td>{$gptSubtitle->id}</td>
            </tr>
            <tr>
                <td><strong>Type</strong></td>
                <td>{$gptSubtitle->type}</td>
            </tr>
            <tr>
                <td><strong>Contents ID</strong></td>
                <td>{$gptSubtitle->contentsid}</td>
            </tr>
            <tr>
                <td><strong>Contents Type</strong></td>
                <td>{$gptSubtitle->contentstype}</td>
            </tr>
            <tr>
                <td><strong>GID</strong></td>
                <td>{$gptSubtitle->gid}</td>
            </tr>
            <tr>
                <td><strong>Output Text Length</strong></td>
                <td>" . strlen($gptSubtitle->outputtext) . " characters</td>
            </tr>
            <tr>
                <td><strong>Created</strong></td>
                <td>" . date('Y-m-d H:i:s', $gptSubtitle->timecreated) . "</td>
            </tr>
            <tr>
                <td><strong>Modified</strong></td>
                <td>" . date('Y-m-d H:i:s', $gptSubtitle->timemodified) . "</td>
            </tr>
        </table>";

        echo "<h3>📝 Output Text Preview (first 500 characters):</h3>";
        echo "<div class='subtitle-preview'>" . htmlspecialchars(mb_substr($gptSubtitle->outputtext, 0, 500)) . "...</div>";

    } else {
        echo "<div class='warning-box'>⚠️ <strong>GPT 자막 데이터가 없습니다.</strong> Fallback으로 reflections0을 사용합니다.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error-box'>❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Query 2: Check mdl_icontent_pages reflections0 (fallback)
echo "<h2>2️⃣ mdl_icontent_pages.reflections0 (Fallback) 확인</h2>";

$reflectionsQuery = "SELECT id, title, reflections0
                     FROM mdl_icontent_pages
                     WHERE id = ?
                     ORDER BY id DESC
                     LIMIT 1";

echo "<div class='query-box'><strong>Query:</strong><br>" . htmlspecialchars($reflectionsQuery) . "<br><strong>Parameters:</strong> [$contentsid]</div>";

try {
    $cnttext = $DB->get_record_sql($reflectionsQuery, [$contentsid]);

    if ($cnttext && !empty($cnttext->reflections0)) {
        echo "<div class='success-box'>✅ <strong>reflections0 데이터 발견!</strong></div>";

        echo "<table class='data-table'>
            <tr>
                <th>Field</th>
                <th>Value</th>
            </tr>
            <tr>
                <td><strong>ID</strong></td>
                <td>{$cnttext->id}</td>
            </tr>
            <tr>
                <td><strong>Title</strong></td>
                <td>{$cnttext->title}</td>
            </tr>
            <tr>
                <td><strong>Reflections0 Length</strong></td>
                <td>" . strlen($cnttext->reflections0) . " characters</td>
            </tr>
        </table>";

        echo "<h3>📝 Reflections0 Preview (first 500 characters):</h3>";
        echo "<div class='subtitle-preview'>" . htmlspecialchars(mb_substr($cnttext->reflections0, 0, 500)) . "...</div>";

    } else {
        echo "<div class='error-box'>❌ <strong>reflections0 데이터가 없습니다.</strong></div>";
    }
} catch (Exception $e) {
    echo "<div class='error-box'>❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Final Decision Logic
echo "<h2>3️⃣ mynote2.php 자막 선택 로직 결과</h2>";

$finalSubtitle = '';
$source = '';

if (!empty($gptSubtitle->outputtext)) {
    $finalSubtitle = $gptSubtitle->outputtext;
    $source = 'mdl_abrainalignment_gptresults (Primary)';
    echo "<div class='success-box'>✅ <strong>선택된 자막:</strong> mdl_abrainalignment_gptresults.outputtext<br><strong>길이:</strong> " . strlen($finalSubtitle) . " characters</div>";
} elseif (!empty($cnttext->reflections0)) {
    $finalSubtitle = $cnttext->reflections0;
    $source = 'mdl_icontent_pages.reflections0 (Fallback)';
    echo "<div class='warning-box'>⚠️ <strong>선택된 자막:</strong> reflections0 (Fallback)<br><strong>길이:</strong> " . strlen($finalSubtitle) . " characters</div>";
} else {
    echo "<div class='error-box'>❌ <strong>사용 가능한 자막 데이터가 없습니다!</strong></div>";
}

if ($finalSubtitle) {
    echo "<h3>📄 최종 자막 미리보기:</h3>";
    echo "<div class='subtitle-preview'>" . htmlspecialchars($finalSubtitle) . "</div>";
}

// Test URL
echo "<h2>4️⃣ 테스트 URL</h2>";
$testUrl = "https://mathking.kr/moodle/local/augmented_teacher/books/mynote2.php?dmn=&cid=$contentsid&nch=2&cmid=87712&quizid=&page=5&studentid=2";
echo "<div class='info-box'>
    <strong>실제 페이지에서 테스트:</strong><br>
    <a href='$testUrl' target='_blank' style='color: #2196F3; text-decoration: none; font-weight: bold;'>
        🔗 mynote2.php 열기
    </a>
    <br><br>
    <strong>테스트 방법:</strong><br>
    1. 전체 재생 모드 활성화<br>
    2. 📄 버튼 클릭하여 자막 표시<br>
    3. 자막이 '$source'에서 로드되는지 확인<br>
    4. 브라우저 개발자 도구 콘솔에서 에러 로그 확인
</div>";

echo "</div>
</body>
</html>";

// DB 정보 (마지막 추가)
echo "
<!--
DB 테이블 정보:
- mdl_abrainalignment_gptresults
  * type: VARCHAR (e.g., 'conversation')
  * contentsid: INT
  * contentstype: INT (1 = icontent)
  * gid: INT (71280 = 고정값)
  * outputtext: TEXT (자막 데이터)
  * timecreated: INT (Unix timestamp)
  * timemodified: INT (Unix timestamp)

- mdl_icontent_pages
  * id: INT
  * title: VARCHAR
  * reflections0: TEXT (Fallback 자막 데이터)
-->
";
?>
