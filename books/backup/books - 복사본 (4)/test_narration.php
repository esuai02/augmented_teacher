<?php
/**
 * 나레이션 생성 기능 테스트 스크립트
 * 브라우저에서 직접 접속하여 테스트 가능
 */

// 설정 파일 포함
require_once(dirname(__FILE__) . '/api_config.php');
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 로그인 체크
require_login();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나레이션 생성 테스트</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
</head>
<body>
    <h1>나레이션 생성 테스트</h1>

    <div style="margin: 20px;">
        <h2>테스트 환경 점검</h2>
        <ul>
            <li>API 키 설정: <?php
                if (defined('OPENAI_API_KEY')) {
                    $key = OPENAI_API_KEY;
                    if (strpos($key, 'YOUR-NEW-API-KEY') !== false) {
                        echo '⚠️ 기본값 - 실제 API 키로 교체 필요';
                    } elseif (!empty($key)) {
                        echo '✅ 설정됨 (길이: ' . strlen($key) . ')';
                    } else {
                        echo '❌ 비어있음';
                    }
                } else {
                    echo '❌ 미설정';
                }
            ?></li>
            <li>오디오 디렉토리: <?php
                $audioPath = defined('AUDIO_UPLOAD_PATH') ? AUDIO_UPLOAD_PATH : '/home/moodle/public_html/audiofiles/';
                echo file_exists($audioPath) ? '✅ 존재함' : '❌ 없음';
                echo " ({$audioPath})";
            ?></li>
            <li>디렉토리 쓰기 권한: <?php
                echo file_exists($audioPath) && is_writable($audioPath) ? '✅ 쓰기 가능' : '❌ 쓰기 불가';
            ?></li>
            <li>PHP 메모리 제한: <?php echo ini_get('memory_limit'); ?></li>
            <li>PHP 실행시간 제한: <?php echo ini_get('max_execution_time'); ?>초</li>
            <li>디버그 모드: <?php echo defined('DEBUG_MODE') && DEBUG_MODE ? '✅ 활성' : '❌ 비활성'; ?></li>
        </ul>
    </div>

    <div style="margin: 20px;">
        <h2>나레이션 생성 테스트</h2>
        <p>콘텐츠 ID를 입력하고 테스트하세요.</p>

        <form id="testForm">
            <label for="contentsid">콘텐츠 ID:</label>
            <input type="number" id="contentsid" name="contentsid" required min="1" value="1">
            <br><br>

            <label for="generateTTS">TTS 생성:</label>
            <input type="checkbox" id="generateTTS" name="generateTTS" checked>
            <br><br>

            <label for="audioType">오디오 타입:</label>
            <select id="audioType" name="audioType">
                <option value="audiourl2" selected>audiourl2 (절차기억)</option>
                <option value="audiourl">audiourl (수업 엿듣기)</option>
            </select>
            <br><br>

            <button type="submit">테스트 실행</button>
        </form>
    </div>

    <div style="margin: 20px;">
        <h2>결과</h2>
        <div id="result" style="background: #f0f0f0; padding: 10px; min-height: 100px;">
            <p>테스트를 실행하면 여기에 결과가 표시됩니다.</p>
        </div>
    </div>

    <div style="margin: 20px;">
        <h2>최근 나레이션 생성 로그</h2>
        <pre style="background: #333; color: #fff; padding: 10px; max-height: 300px; overflow-y: auto;">
<?php
$logFile = dirname(__FILE__) . '/narration_error.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $recentLines = array_slice($logLines, -20); // 최근 20줄만 표시
    echo htmlspecialchars(implode("\n", $recentLines));
} else {
    echo "로그 파일이 없습니다.";
}
?>
        </pre>
    </div>

    <script>
    $(document).ready(function() {
        $('#testForm').on('submit', function(e) {
            e.preventDefault();

            var contentsid = $('#contentsid').val();
            var generateTTS = $('#generateTTS').is(':checked');
            var audioType = $('#audioType').val();

            $('#result').html('<p>⏳ 테스트 진행 중...</p>');

            $.ajax({
                url: 'generate_narration.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    contentsid: contentsid,
                    contentstype: 1,
                    generateTTS: generateTTS ? 'true' : 'false',
                    audioType: audioType
                },
                success: function(response) {
                    var resultHtml = '<h3>✅ 성공</h3>';
                    resultHtml += '<p><strong>메시지:</strong> ' + response.message + '</p>';

                    if (response.narration) {
                        resultHtml += '<p><strong>나레이션 미리보기:</strong> ' + response.narration + '</p>';
                    }

                    if (response.fullNarration) {
                        resultHtml += '<details>';
                        resultHtml += '<summary>전체 나레이션 보기</summary>';
                        resultHtml += '<pre>' + response.fullNarration + '</pre>';
                        resultHtml += '</details>';
                    }

                    if (response.audioUrl) {
                        resultHtml += '<p><strong>생성된 오디오 URL:</strong> ';
                        resultHtml += '<a href="' + response.audioUrl + '" target="_blank">' + response.audioUrl + '</a></p>';
                        resultHtml += '<audio controls src="' + response.audioUrl + '"></audio>';
                    }

                    $('#result').html(resultHtml);

                    Swal.fire({
                        icon: 'success',
                        title: '테스트 성공',
                        text: response.message
                    });
                },
                error: function(xhr, status, error) {
                    var errorMsg = '';

                    if (xhr.responseText) {
                        try {
                            var errorData = JSON.parse(xhr.responseText);
                            errorMsg = errorData.message || '알 수 없는 오류';
                        } catch (e) {
                            errorMsg = xhr.responseText.substring(0, 500);
                        }
                    } else {
                        errorMsg = error;
                    }

                    var resultHtml = '<h3>❌ 오류 발생</h3>';
                    resultHtml += '<p><strong>상태:</strong> ' + xhr.status + ' ' + status + '</p>';
                    resultHtml += '<p><strong>오류:</strong> ' + error + '</p>';
                    resultHtml += '<p><strong>메시지:</strong> ' + errorMsg + '</p>';
                    resultHtml += '<details>';
                    resultHtml += '<summary>전체 응답 보기</summary>';
                    resultHtml += '<pre>' + xhr.responseText + '</pre>';
                    resultHtml += '</details>';

                    $('#result').html(resultHtml);

                    Swal.fire({
                        icon: 'error',
                        title: '테스트 실패',
                        text: errorMsg
                    });
                }
            });
        });
    });
    </script>
</body>
</html>