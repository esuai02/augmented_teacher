<?php
/**
 * 단축 URL 생성 API
 * 파일: students/create_short_url.php
 * 에러 출력 위치: create_short_url.php
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

try {
    // 현재 URL 가져오기
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // POST 요청인 경우 JSON 응답
        header('Content-Type: application/json; charset=utf-8');
        $original_url = isset($_POST['url']) ? $_POST['url'] : '';
    } else {
        // GET 요청인 경우 HTML 응답
        $original_url = isset($_GET['url']) ? urldecode($_GET['url']) : '';
    }
    
    if (empty($original_url)) {
        // POST/GET로 전달되지 않으면 현재 페이지 URL 사용
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $original_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    if (empty($original_url)) {
        throw new Exception('URL이 제공되지 않았습니다. [create_short_url.php:25]');
    }
    
    // 기존 단축 URL이 있는지 확인
    $existing = $DB->get_record_sql("
        SELECT hash 
        FROM mdl_short_urls 
        WHERE original_url = ? 
        AND (expired_at IS NULL OR expired_at > NOW())
        ORDER BY id DESC 
        LIMIT 1
    ", array($original_url));
    
    if ($existing) {
    // 서버 루트에 s.php 파일이 없으면 생성 (한 번만 생성)
    $server_root_public = '/home/moodle/public_html';
    $server_root_s_file = $server_root_public . '/s.php';
    
    if (!file_exists($server_root_s_file)) {
        $server_s_php_content = '<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = \'\';
if (isset($_GET[\'h\']) && !empty($_GET[\'h\'])) {
    $hash = trim($_GET[\'h\']);
} else if (isset($_GET[\'hash\']) && !empty($_GET[\'hash\'])) {
    $hash = trim($_GET[\'hash\']);
} else if (isset($_GET[\'id\']) && !empty($_GET[\'id\'])) {
    $hash = trim($_GET[\'id\']);
}
if (empty($hash)) {
    http_response_code(400);
    echo \'해시 파라미터가 없습니다. 사용법: s.php?h=해시\';
    exit;
}
$record = $DB->get_record_sql("SELECT original_url FROM mdl_short_urls WHERE hash = ?", array($hash));
if ($record) {
    $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE hash = ?", array($hash));
    header(\'Location: \' . $record->original_url);
    exit;
}
http_response_code(404);
echo \'단축 URL을 찾을 수 없습니다.\';
?>';
        
        if (is_writable($server_root_public) || !file_exists($server_root_s_file)) {
            file_put_contents($server_root_s_file, $server_s_php_content);
            chmod($server_root_s_file, 0644);
        }
    }
        
        // 기존 단축 URL 반환 (moodle/s.php?h=해시 형식)
        $short_url = 'https://mathking.kr/moodle/s.php?h=' . $existing->hash;
        
        // 서버 루트에 s.php 파일이 없으면 생성 (한 번만 생성)
        $server_root = '/home/moodle/public_html/moodle';
        $s_php_file = $server_root . '/s.php';
        
        if (!file_exists($s_php_file)) {
            $s_php_content = '<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = \'\';
if (isset($_GET[\'h\']) && !empty($_GET[\'h\'])) {
    $hash = trim($_GET[\'h\']);
} else if (isset($_GET[\'hash\']) && !empty($_GET[\'hash\'])) {
    $hash = trim($_GET[\'hash\']);
} else if (isset($_GET[\'id\']) && !empty($_GET[\'id\'])) {
    $hash = trim($_GET[\'id\']);
}
if (empty($hash)) {
    http_response_code(400);
    echo \'해시 파라미터가 없습니다. 사용법: s.php?h=해시\';
    exit;
}
$record = $DB->get_record_sql("SELECT original_url FROM mdl_short_urls WHERE hash = ?", array($hash));
if ($record) {
    $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE hash = ?", array($hash));
    header(\'Location: \' . $record->original_url);
    exit;
}
http_response_code(404);
echo \'단축 URL을 찾을 수 없습니다.\';
?>';
            
            if (is_writable($server_root) || !file_exists($s_php_file)) {
                file_put_contents($s_php_file, $s_php_content);
                chmod($s_php_file, 0644);
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(array(
                'success' => true,
                'short_url' => $short_url,
                'hash' => $existing->hash
            ));
        } else {
            // GET 요청인 경우 HTML 페이지로 리다이렉트
            displayShortUrlPage($short_url, $original_url);
        }
        exit;
    }
    
    // 새로운 해시 생성 (4자리)
    $hash = '';
    $max_attempts = 50; // 4자리는 충돌 가능성이 높으므로 시도 횟수 증가
    $attempt = 0;
    
    do {
        // 랜덤 문자열 생성 (영문 대소문자 + 숫자)
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $hash = '';
        for ($i = 0; $i < 4; $i++) {
            $hash .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        
        // 중복 확인
        $duplicate = $DB->get_record_sql("SELECT id FROM mdl_short_urls WHERE hash = ?", array($hash));
        $attempt++;
        
        if ($attempt >= $max_attempts) {
            throw new Exception('고유한 해시를 생성할 수 없습니다. [create_short_url.php:58]');
        }
    } while ($duplicate);
    
    // 데이터베이스에 저장
    $record = new stdClass();
    $record->hash = $hash;
    $record->original_url = $original_url;
    $record->created_at = date('Y-m-d H:i:s');
    $record->expired_at = null; // 만료일 없음
    $record->click_count = 0;
    
    $insert_id = $DB->insert_record('short_urls', $record);
    
    if (!$insert_id) {
        throw new Exception('단축 URL 저장에 실패했습니다. [create_short_url.php:72]');
    }
    
        // 서버 루트에 s.php 파일이 없으면 생성 (한 번만 생성)
        $server_root = '/home/moodle/public_html/moodle';
        $s_php_file = $server_root . '/s.php';
        
        if (!file_exists($s_php_file)) {
            // 간단한 s.php 파일 생성 (h, hash 또는 id 파라미터 지원)
            $s_php_content = '<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = \'\';
if (isset($_GET[\'h\']) && !empty($_GET[\'h\'])) {
    $hash = trim($_GET[\'h\']);
} else if (isset($_GET[\'hash\']) && !empty($_GET[\'hash\'])) {
    $hash = trim($_GET[\'hash\']);
} else if (isset($_GET[\'id\']) && !empty($_GET[\'id\'])) {
    $hash = trim($_GET[\'id\']);
}
if (empty($hash)) {
    http_response_code(400);
    echo \'해시 파라미터가 없습니다. 사용법: s.php?h=해시\';
    exit;
}
$record = $DB->get_record_sql("SELECT original_url FROM mdl_short_urls WHERE hash = ?", array($hash));
if ($record) {
    $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE hash = ?", array($hash));
    header(\'Location: \' . $record->original_url);
    exit;
}
http_response_code(404);
echo \'단축 URL을 찾을 수 없습니다.\';
?>';
        
        if (is_writable($server_root) || !file_exists($s_php_file)) {
            file_put_contents($s_php_file, $s_php_content);
            chmod($s_php_file, 0644);
        }
    }
    
    // 서버 루트에 short.php 파일이 없으면 생성 (한 번만 생성)
    $server_root_public = '/home/moodle/public_html';
    $server_root_short_file = $server_root_public . '/short.php';
    
    if (!file_exists($server_root_short_file)) {
        $server_short_php_content = '<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = \'\';
if (isset($_GET[\'hash\']) && !empty($_GET[\'hash\'])) {
    $hash = trim($_GET[\'hash\']);
} else if (isset($_GET[\'id\']) && !empty($_GET[\'id\'])) {
    $hash = trim($_GET[\'id\']);
}
if (empty($hash)) {
    http_response_code(400);
    echo \'해시 파라미터가 없습니다. 사용법: short.php?hash=해시 또는 short.php?id=해시\';
    exit;
}
$record = $DB->get_record_sql("SELECT original_url FROM mdl_short_urls WHERE hash = ?", array($hash));
if ($record) {
    $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE hash = ?", array($hash));
    header(\'Location: \' . $record->original_url);
    exit;
}
http_response_code(404);
echo \'단축 URL을 찾을 수 없습니다.\';
?>';
        
        if (is_writable($server_root_public) || !file_exists($server_root_short_file)) {
            file_put_contents($server_root_short_file, $server_short_php_content);
            chmod($server_root_short_file, 0644);
        }
    }
    
    // 단축 URL 생성 (moodle/s.php?h=해시 형식)
    $short_url = 'https://mathking.kr/moodle/s.php?h=' . $hash;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(array(
            'success' => true,
            'short_url' => $short_url,
            'hash' => $hash
        ));
    } else {
        // GET 요청인 경우 HTML 페이지로 리다이렉트
        displayShortUrlPage($short_url, $original_url);
    }
    
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
    } else {
        displayErrorPage($e->getMessage());
    }
}

/**
 * 단축 URL 결과 페이지 표시
 */
function displayShortUrlPage($short_url, $original_url) {
    echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단축 URL 생성 완료</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        h1 {
            color: #1976d2;
            margin-bottom: 1rem;
        }
        .url-box {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            word-break: break-all;
        }
        .short-url {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1976d2;
            margin: 0.5rem 0;
        }
        .original-url {
            font-size: 0.9rem;
            color: #666;
            margin: 0.5rem 0;
        }
        button {
            background: #1976d2;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0.5rem;
        }
        button:hover {
            background: #1565c0;
        }
        .back-btn {
            background: #757575;
        }
        .back-btn:hover {
            background: #616161;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ 단축 URL 생성 완료</h1>
        <div class="url-box">
            <div class="short-url" id="short-url">' . htmlspecialchars($short_url, ENT_QUOTES, 'UTF-8') . '</div>
            <div class="original-url">원본 URL: ' . htmlspecialchars($original_url, ENT_QUOTES, 'UTF-8') . '</div>
        </div>
        <button onclick="copyUrl()">📋 URL 복사</button>
        <button class="back-btn" onclick="window.history.back()">← 돌아가기</button>
    </div>
    <script>
        function copyUrl() {
            var url = document.getElementById("short-url").textContent;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    alert("URL이 클립보드에 복사되었습니다!");
                });
            } else {
                var textarea = document.createElement("textarea");
                textarea.value = url;
                textarea.style.position = "fixed";
                textarea.style.opacity = "0";
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                alert("URL이 클립보드에 복사되었습니다!");
            }
        }
    </script>
</body>
</html>';
}

/**
 * 에러 페이지 표시
 */
function displayErrorPage($error_message) {
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단축 URL 오류</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d32f2f;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            margin-bottom: 1rem;
        }
        button {
            background: #757575;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
        }
        button:hover {
            background: #616161;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>❌ 오류 발생</h1>
        <p>' . htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') . '</p>
        <button onclick="window.history.back()">← 돌아가기</button>
    </div>
</body>
</html>';
}
?>

