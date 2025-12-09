<?php
/**
 * DB Verification Script for audiourl2 Field
 *
 * Purpose: Verify that audiourl2 field is being updated correctly after TTS upload
 * Usage: Access via browser: https://mathking.kr/moodle/local/augmented_teacher/books/verify_db_audiourl2.php?cid=31906&ctype=1
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_login();

// Get parameters
$contentsid = isset($_GET['cid']) ? $_GET['cid'] : null;
$contentstype = isset($_GET['ctype']) ? $_GET['ctype'] : null;

// Verify user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole->data ?? null;

if ($role === 'student') {
    echo '사용권한이 없습니다.';
    exit();
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB audiourl2 필드 검증</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #f5f5f5;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2196F3;
            border-bottom: 3px solid #2196F3;
            padding-bottom: 10px;
        }
        h2 {
            color: #4CAF50;
            margin-top: 30px;
            border-left: 5px solid #4CAF50;
            padding-left: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .query-code {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #000;
        }
        .null {
            color: #f44336;
            font-weight: bold;
        }
        .not-null {
            color: #4CAF50;
            font-weight: bold;
        }
        a.button {
            display: inline-block;
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        a.button:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DB audiourl2 필드 검증</h1>

        <?php if (!$contentsid || !$contentstype): ?>
            <div class="warning-box">
                <strong>⚠️ 파라미터 누락</strong><br>
                URL에 cid와 ctype 파라미터를 포함하세요.<br>
                예: <code>verify_db_audiourl2.php?cid=31906&ctype=1</code>
            </div>
        <?php else: ?>
            <div class="info-box">
                <strong>검증 대상:</strong><br>
                <span class="label">Contents ID:</span> <span class="value"><?php echo $contentsid; ?></span><br>
                <span class="label">Contents Type:</span> <span class="value"><?php echo $contentstype; ?></span>
            </div>

            <h2>1️⃣ icontent_pages 테이블 조회 (audiourl2 필드)</h2>
            <?php
            if ($contentstype == 1) {
                $record = $DB->get_record_sql("SELECT id, title, audiourl, audiourl2 FROM {icontent_pages} WHERE id = ?", array($contentsid));

                if ($record) {
                    echo '<table>';
                    echo '<tr><th>필드</th><th>값</th><th>상태</th></tr>';
                    echo '<tr><td class="label">ID</td><td class="value">' . $record->id . '</td><td>-</td></tr>';
                    echo '<tr><td class="label">Title</td><td class="value">' . htmlspecialchars($record->title) . '</td><td>-</td></tr>';

                    $audiourl_status = ($record->audiourl != NULL) ? '<span class="not-null">✅ 값 존재</span>' : '<span class="null">❌ NULL</span>';
                    echo '<tr><td class="label">audiourl (수업 엿듣기)</td><td class="value">' . htmlspecialchars($record->audiourl ?? 'NULL') . '</td><td>' . $audiourl_status . '</td></tr>';

                    $audiourl2_status = ($record->audiourl2 != NULL) ? '<span class="not-null">✅ 값 존재</span>' : '<span class="null">❌ NULL</span>';
                    echo '<tr><td class="label">audiourl2 (절차기억 나레이션)</td><td class="value">' . htmlspecialchars($record->audiourl2 ?? 'NULL') . '</td><td>' . $audiourl2_status . '</td></tr>';
                    echo '</table>';

                    if ($record->audiourl2 != NULL) {
                        echo '<div class="success-box"><strong>✅ audiourl2 필드에 값이 존재합니다!</strong><br>';
                        echo 'mynote.php에서 🟢 녹색 아이콘이 표시되어야 합니다.</div>';
                    } else {
                        echo '<div class="error-box"><strong>❌ audiourl2 필드가 NULL입니다!</strong><br>';
                        echo 'mynote.php에서 🟡 노란색 아이콘이 표시됩니다.<br>';
                        echo '이는 TTS 업로드 후 DB 업데이트가 실패했음을 의미합니다.</div>';
                    }
                } else {
                    echo '<div class="error-box"><strong>❌ 레코드를 찾을 수 없습니다!</strong><br>';
                    echo 'icontent_pages 테이블에 ID ' . $contentsid . '가 존재하지 않습니다.</div>';
                }
            } else if ($contentstype == 2) {
                $record = $DB->get_record_sql("SELECT id, questiontext, audiourl2 FROM {question} WHERE id = ?", array($contentsid));

                if ($record) {
                    echo '<table>';
                    echo '<tr><th>필드</th><th>값</th><th>상태</th></tr>';
                    echo '<tr><td class="label">ID</td><td class="value">' . $record->id . '</td><td>-</td></tr>';
                    echo '<tr><td class="label">Question Text</td><td class="value">' . htmlspecialchars(substr($record->questiontext, 0, 100)) . '...</td><td>-</td></tr>';

                    $audiourl2_status = ($record->audiourl2 != NULL) ? '<span class="not-null">✅ 값 존재</span>' : '<span class="null">❌ NULL</span>';
                    echo '<tr><td class="label">audiourl2 (절차기억 나레이션)</td><td class="value">' . htmlspecialchars($record->audiourl2 ?? 'NULL') . '</td><td>' . $audiourl2_status . '</td></tr>';
                    echo '</table>';

                    if ($record->audiourl2 != NULL) {
                        echo '<div class="success-box"><strong>✅ audiourl2 필드에 값이 존재합니다!</strong></div>';
                    } else {
                        echo '<div class="error-box"><strong>❌ audiourl2 필드가 NULL입니다!</strong></div>';
                    }
                } else {
                    echo '<div class="error-box"><strong>❌ 레코드를 찾을 수 없습니다!</strong><br>';
                    echo 'question 테이블에 ID ' . $contentsid . '가 존재하지 않습니다.</div>';
                }
            }
            ?>

            <h2>2️⃣ GPT 나레이션 결과 조회</h2>
            <?php
            $gpt_result = $DB->get_record_sql("SELECT * FROM {abrainalignment_gptresults} WHERE type LIKE 'pmemory' AND contentsid LIKE ? AND contentstype LIKE ? ORDER BY id DESC LIMIT 1", array($contentsid, $contentstype));

            if ($gpt_result) {
                echo '<table>';
                echo '<tr><th>필드</th><th>값</th></tr>';
                echo '<tr><td class="label">ID</td><td class="value">' . $gpt_result->id . '</td></tr>';
                echo '<tr><td class="label">Type</td><td class="value">' . $gpt_result->type . '</td></tr>';
                echo '<tr><td class="label">Contents ID</td><td class="value">' . $gpt_result->contentsid . '</td></tr>';
                echo '<tr><td class="label">Contents Type</td><td class="value">' . $gpt_result->contentstype . '</td></tr>';
                echo '<tr><td class="label">Output Text (처음 200자)</td><td class="value">' . htmlspecialchars(substr($gpt_result->outputtext, 0, 200)) . '...</td></tr>';
                echo '<tr><td class="label">Created</td><td class="value">' . date('Y-m-d H:i:s', $gpt_result->timecreated) . '</td></tr>';
                echo '<tr><td class="label">Modified</td><td class="value">' . date('Y-m-d H:i:s', $gpt_result->timemodified) . '</td></tr>';
                echo '</table>';

                $section_count = substr_count($gpt_result->outputtext, '@');
                if ($section_count > 0) {
                    echo '<div class="info-box"><strong>📊 @ 기호로 구분된 구간: ' . ($section_count + 1) . '개</strong><br>';
                    echo '듣기평가 모드로 생성된 나레이션입니다.</div>';
                }
            } else {
                echo '<div class="warning-box"><strong>⚠️ GPT 나레이션 결과를 찾을 수 없습니다.</strong><br>';
                echo '절차기억 나레이션이 아직 생성되지 않았을 수 있습니다.</div>';
            }
            ?>

            <h2>3️⃣ 업로드 로그 확인</h2>
            <?php
            $log_file = '/home/moodle/logs/pmemory_upload.log';
            if (file_exists($log_file)) {
                $log_lines = file($log_file);
                $recent_logs = array_slice($log_lines, -50); // 최근 50줄

                echo '<div class="info-box">';
                echo '<strong>📁 최근 업로드 로그 (마지막 50줄):</strong><br>';
                echo '<div class="query-code">';
                foreach ($recent_logs as $line) {
                    if (strpos($line, $contentsid) !== false) {
                        echo '<span style="background-color:#ffeb3b;color:#000;">' . htmlspecialchars($line) . '</span>';
                    } else {
                        echo htmlspecialchars($line);
                    }
                }
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="warning-box"><strong>⚠️ 로그 파일을 찾을 수 없습니다.</strong><br>';
                echo '경로: ' . $log_file . '</div>';
            }
            ?>

            <h2>4️⃣ 수동 DB 업데이트 쿼리 (필요시)</h2>
            <div class="warning-box">
                <strong>⚠️ 주의:</strong> 아래 쿼리는 수동으로 DB를 업데이트합니다. 신중하게 사용하세요.
            </div>

            <?php if ($contentstype == 1): ?>
                <div class="query-code">
UPDATE mdl_icontent_pages
SET audiourl2 = 'https://mathking.kr/Contents/audiofiles/pmemory/cid<?php echo $contentsid; ?>ct<?php echo $contentstype; ?>_pmemory.wav'
WHERE id = <?php echo $contentsid; ?>;
                </div>
            <?php elseif ($contentstype == 2): ?>
                <div class="query-code">
UPDATE mdl_question
SET audiourl2 = 'https://mathking.kr/Contents/audiofiles/pmemory/cid<?php echo $contentsid; ?>ct<?php echo $contentstype; ?>_pmemory.wav'
WHERE id = <?php echo $contentsid; ?>;
                </div>
            <?php endif; ?>

            <h2>5️⃣ 관련 페이지 링크</h2>
            <div class="info-box">
                <a href="openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=<?php echo $contentstype; ?>" class="button">
                    🎵 TTS 생성 페이지
                </a>
                <a href="mynote.php?cid=<?php echo $contentsid; ?>&ctype=<?php echo $contentstype; ?>" class="button">
                    📝 mynote.php (아이콘 확인)
                </a>
                <a href="verify_db_audiourl2.php?cid=<?php echo $contentsid; ?>&ctype=<?php echo $contentstype; ?>" class="button">
                    🔄 새로고침
                </a>
            </div>

            <h2>6️⃣ 문제 해결 가이드</h2>
            <div class="info-box">
                <strong>🔍 체크리스트:</strong><br><br>

                <strong>1. 파일이 업로드되었는지 확인:</strong><br>
                <div class="query-code">
ls -lh /home/moodle/public_html/Contents/audiofiles/pmemory/cid<?php echo $contentsid; ?>ct<?php echo $contentstype; ?>_pmemory.wav
                </div>

                <strong>2. 로그에서 DB 업데이트 메시지 확인:</strong><br>
                <code>grep "Database updated" /home/moodle/logs/pmemory_upload.log</code><br><br>

                <strong>3. section 파라미터가 전송되지 않았는지 확인:</strong><br>
                로그에서 <code>"section: NOT SENT"</code> 또는 <code>"section: NULL"</code> 확인<br><br>

                <strong>4. DB 권한 확인:</strong><br>
                Moodle $DB 객체가 정상적으로 동작하는지 확인<br><br>

                <strong>5. 브라우저 콘솔 로그 확인:</strong><br>
                F12 개발자 도구 → Console 탭에서 AJAX 응답 확인
            </div>

        <?php endif; ?>

        <hr style="margin: 30px 0;">
        <p style="color: #999; font-size: 12px; text-align: center;">
            이 페이지는 디버깅 목적으로 사용됩니다. 생성 시각: <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>

<?php
/**
 * DB Tables Referenced:
 * - mdl_icontent_pages (audiourl, audiourl2)
 * - mdl_question (audiourl2)
 * - mdl_abrainalignment_gptresults (outputtext)
 * - mdl_user_info_data (user role check)
 *
 * Log File:
 * - /home/moodle/logs/pmemory_upload.log
 *
 * Audio File Path:
 * - /home/moodle/public_html/Contents/audiofiles/pmemory/
 */
?>
