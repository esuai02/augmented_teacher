<?php
/**
 * reflections3 필드 디버그 스크립트
 * File: debug_reflections3.php
 *
 * 용도: mynote_test.php에서 단계별 재생 UI가 안 나오는 문제 진단
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// URL에서 contentsid 받기
$contentsid = optional_param('contentsid', 0, PARAM_INT);

if(!$contentsid) {
    echo "<h1>Error: contentsid 파라미터가 필요합니다</h1>";
    echo "<p>사용법: debug_reflections3.php?contentsid=123</p>";
    exit;
}

// DB에서 데이터 조회
$record = $DB->get_record('icontent_pages', ['id' => $contentsid],
    'id, audiourl, audiourl2, reflections0, reflections1, reflections3');

if(!$record) {
    echo "<h1>Error: contentsid={$contentsid} 레코드를 찾을 수 없습니다</h1>";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>reflections3 디버그</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 5px;
        }
        .field-name {
            font-weight: bold;
            color: #1976D2;
        }
        .field-value {
            background: #f9f9f9;
            padding: 10px;
            margin: 5px 0;
            border-left: 3px solid #4CAF50;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .empty {
            color: #999;
            font-style: italic;
        }
        .json-parsed {
            background: #e3f2fd;
            padding: 10px;
            margin: 10px 0;
            border-left: 3px solid #2196F3;
        }
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-left: 3px solid #c62828;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-left: 3px solid #4CAF50;
        }
    </style>
</head>
<body>
    <h1>🔍 reflections3 필드 디버그 (contentsid: <?php echo $contentsid; ?>)</h1>

    <div class="section">
        <h2>📊 기본 정보</h2>
        <div><span class="field-name">ID:</span> <?php echo $record->id; ?></div>
    </div>

    <div class="section">
        <h2>🎵 오디오 URL</h2>
        <div class="field-name">audiourl:</div>
        <div class="field-value"><?php echo $record->audiourl ?: '<span class="empty">(비어있음)</span>'; ?></div>

        <div class="field-name">audiourl2:</div>
        <div class="field-value"><?php echo $record->audiourl2 ?: '<span class="empty">(비어있음)</span>'; ?></div>
    </div>

    <div class="section">
        <h2>📝 reflections0 (전체 텍스트)</h2>
        <div class="field-value">
            <?php
            if($record->reflections0) {
                echo htmlspecialchars(mb_substr($record->reflections0, 0, 500));
                if(mb_strlen($record->reflections0) > 500) {
                    echo '... (총 ' . mb_strlen($record->reflections0) . '자)';
                }
            } else {
                echo '<span class="empty">(비어있음)</span>';
            }
            ?>
        </div>
    </div>

    <div class="section">
        <h2>🔢 reflections1 (절차기억용)</h2>
        <div class="field-value">
            <?php
            if($record->reflections1) {
                echo htmlspecialchars($record->reflections1);

                // JSON 파싱 시도
                $decoded1 = json_decode($record->reflections1, true);
                if($decoded1) {
                    echo '<div class="json-parsed">';
                    echo '<strong>JSON 파싱 성공:</strong><br>';
                    echo 'mode: ' . ($decoded1['mode'] ?? '없음') . '<br>';
                    echo 'section_count: ' . ($decoded1['section_count'] ?? '없음') . '<br>';
                    echo '</div>';
                } else {
                    echo '<div class="error">JSON 파싱 실패: ' . json_last_error_msg() . '</div>';
                }
            } else {
                echo '<span class="empty">(비어있음)</span>';
            }
            ?>
        </div>
    </div>

    <div class="section">
        <h2>🎯 reflections3 (서술평가용) - 핵심!</h2>
        <div class="field-value">
            <?php
            if($record->reflections3) {
                echo htmlspecialchars($record->reflections3);

                // JSON 파싱 시도
                $decoded3 = json_decode($record->reflections3, true);
                if($decoded3) {
                    echo '<div class="json-parsed">';
                    echo '<strong>✅ JSON 파싱 성공:</strong><br>';
                    echo 'mode: ' . ($decoded3['mode'] ?? '없음') . '<br>';
                    echo 'section_count: ' . ($decoded3['section_count'] ?? '없음') . '<br>';

                    if($decoded3['mode'] === 'listening_test') {
                        echo '<div class="success">';
                        echo '✅ 서술평가 모드 활성화 조건 충족!<br>';
                        echo '구간 개수: ' . count($decoded3['sections'] ?? []) . '<br>';
                        echo '</div>';
                    } else {
                        echo '<div class="error">';
                        echo '❌ mode가 "listening_test"가 아님<br>';
                        echo '</div>';
                    }

                    // sections 배열 출력
                    if(isset($decoded3['sections']) && is_array($decoded3['sections'])) {
                        echo '<br><strong>sections 배열:</strong><br>';
                        foreach($decoded3['sections'] as $i => $url) {
                            echo ($i+1) . '. ' . htmlspecialchars($url) . '<br>';
                        }
                    }

                    echo '</div>';
                } else {
                    echo '<div class="error">❌ JSON 파싱 실패: ' . json_last_error_msg() . '</div>';
                }
            } else {
                echo '<span class="empty">❌ (비어있음) - 이것이 문제입니다!</span>';
                echo '<div class="error">';
                echo '<strong>진단:</strong> reflections3 필드가 비어있어서 단계별 재생 UI가 표시되지 않습니다.<br>';
                echo '<strong>해결방법:</strong> generate_essay_instruction.php를 실행하여 데이터를 생성하세요.';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="section">
        <h2>🧪 mynote_test.php 로직 시뮬레이션</h2>
        <?php
        $isListeningTest = false;
        $sectionData = null;

        if(!empty($record->reflections3)) {
            $decoded = json_decode($record->reflections3, true);
            if(isset($decoded['mode']) && $decoded['mode'] === 'listening_test') {
                $isListeningTest = true;
                $sectionData = $decoded;
            }
        }

        if($isListeningTest && $sectionData) {
            echo '<div class="success">';
            echo '✅ 조건 만족: 단계별 재생 UI가 표시됩니다!<br>';
            echo 'if($isListeningTest && $sectionData) → TRUE';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '❌ 조건 불만족: 전체 재생 UI가 표시됩니다!<br>';
            echo 'if($isListeningTest && $sectionData) → FALSE<br>';
            echo '<br><strong>원인:</strong><br>';
            if(empty($record->reflections3)) {
                echo '- reflections3 필드가 비어있음<br>';
            } elseif(!isset($decoded['mode'])) {
                echo '- JSON에 mode 키가 없음<br>';
            } elseif($decoded['mode'] !== 'listening_test') {
                echo '- mode 값이 "listening_test"가 아님: ' . $decoded['mode'] . '<br>';
            }
            echo '</div>';
        }
        ?>
    </div>

    <div class="section">
        <h2>💡 다음 단계</h2>
        <ol>
            <li>만약 reflections3가 비어있다면: <strong>mynote_test.php에서 "절차기억 생성" 버튼을 클릭</strong>하세요.</li>
            <li>버튼 클릭 후 이 페이지를 새로고침하여 reflections3가 생성되었는지 확인하세요.</li>
            <li>reflections3가 올바르게 생성되었다면, mynote_test.php를 새로고침하여 단계별 UI가 나오는지 확인하세요.</li>
        </ol>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <a href="mynote_test.php?dmn=&cid=106&nch=9&cmid=87718&quizid=&page=1&studentid=2"
           style="display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">
            mynote_test.php로 돌아가기
        </a>
    </div>
</body>
</html>
