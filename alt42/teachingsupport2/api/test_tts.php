<?php
/**
 * test_tts.php - TTS 생성 테스트 페이지
 * 파일 위치: alt42/teachingsupport/api/test_tts.php
 * 
 * 사용법: https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/api/test_tts.php
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>TTS 생성 테스트</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; background: #1a1a2e; color: #eee; }
h1 { color: #00d4ff; }
.section { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #00d4ff; }
.success { border-left-color: #00ff88; }
.error { border-left-color: #ff4444; }
.warning { border-left-color: #ffaa00; }
code { background: #0f0f23; padding: 2px 6px; border-radius: 4px; }
pre { background: #0f0f23; padding: 10px; border-radius: 4px; overflow-x: auto; }
.btn { background: #00d4ff; color: #000; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
.btn:hover { background: #00a0cc; }
</style></head><body>";

echo "<h1>🔊 TTS 생성 진단 테스트</h1>";

// 1. Moodle 설정 로드
echo "<div class='section'>";
echo "<h3>1. Moodle 설정 로드</h3>";
try {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    require_login();
    echo "<p class='success'>✅ Moodle 설정 로드 성공</p>";
    echo "<p>현재 사용자: <code>" . $USER->username . " (ID: " . $USER->id . ")</code></p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Moodle 설정 로드 실패: " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// 2. OpenAI API 키 확인
echo "<div class='section'>";
echo "<h3>2. OpenAI API 키 확인</h3>";
require_once(__DIR__ . '/../config.php');
if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
    $keyPreview = substr(OPENAI_API_KEY, 0, 15) . '...' . substr(OPENAI_API_KEY, -5);
    echo "<p class='success'>✅ API 키 설정됨: <code>{$keyPreview}</code></p>";
} else {
    echo "<p class='error'>❌ OPENAI_API_KEY 미설정</p>";
}
echo "</div>";

// 3. 테이블 확인
echo "<div class='section'>";
echo "<h3>3. ktm_teaching_interactions 테이블 확인</h3>";
try {
    $dbman = $DB->get_manager();
    if ($dbman->table_exists('ktm_teaching_interactions')) {
        echo "<p class='success'>✅ 테이블 존재함</p>";
        
        // 필드 확인
        $requiredFields = ['wboardid', 'type', 'narration_text', 'audio_url'];
        foreach ($requiredFields as $field) {
            if ($dbman->field_exists('ktm_teaching_interactions', $field)) {
                echo "<p class='success'>✅ 필드 존재: <code>{$field}</code></p>";
            } else {
                echo "<p class='warning'>⚠️ 필드 누락: <code>{$field}</code></p>";
            }
        }
        
        // 레코드 수 확인
        $count = $DB->count_records('ktm_teaching_interactions');
        echo "<p>📊 총 레코드 수: <code>{$count}</code></p>";
        
    } else {
        echo "<p class='error'>❌ 테이블이 존재하지 않음</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ 테이블 확인 오류: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Audio 디렉토리 확인
echo "<div class='section'>";
echo "<h3>4. Audio 디렉토리 확인</h3>";
$audioDir = __DIR__ . '/../audio/';
if (file_exists($audioDir)) {
    echo "<p class='success'>✅ 디렉토리 존재: <code>{$audioDir}</code></p>";
    if (is_writable($audioDir)) {
        echo "<p class='success'>✅ 쓰기 권한 있음</p>";
    } else {
        echo "<p class='error'>❌ 쓰기 권한 없음</p>";
    }
    
    // 기존 오디오 파일 확인
    $files = glob($audioDir . 'tts_*.mp3');
    echo "<p>📁 기존 TTS 파일 수: <code>" . count($files) . "</code></p>";
    if (count($files) > 0) {
        echo "<p>최근 파일: <code>" . basename(end($files)) . "</code></p>";
    }
} else {
    echo "<p class='warning'>⚠️ 디렉토리 없음 - 생성 시도...</p>";
    if (mkdir($audioDir, 0755, true)) {
        echo "<p class='success'>✅ 디렉토리 생성 성공</p>";
    } else {
        echo "<p class='error'>❌ 디렉토리 생성 실패</p>";
    }
}
echo "</div>";

// 5. OpenAI TTS API 테스트
echo "<div class='section'>";
echo "<h3>5. OpenAI TTS API 테스트</h3>";

if (isset($_GET['test_tts'])) {
    echo "<p>🔄 TTS 생성 테스트 중...</p>";
    
    $testText = "안녕하세요. 이것은 TTS 생성 테스트입니다.";
    
    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'tts-1',
        'input' => $testText,
        'voice' => 'alloy',
        'response_format' => 'mp3',
        'speed' => 1.0
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $startTime = microtime(true);
    $audioData = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $duration = round($endTime - $startTime, 2);
    
    echo "<p>응답 시간: <code>{$duration}초</code></p>";
    echo "<p>HTTP 상태: <code>{$httpCode}</code></p>";
    
    if ($httpCode === 200 && !empty($audioData)) {
        echo "<p class='success'>✅ TTS 생성 성공!</p>";
        echo "<p>오디오 데이터 크기: <code>" . strlen($audioData) . " bytes</code></p>";
        
        // 테스트 파일 저장
        $testFilename = 'tts_test_' . time() . '.mp3';
        $testFilepath = $audioDir . $testFilename;
        $writeResult = file_put_contents($testFilepath, $audioData);
        
        if ($writeResult !== false) {
            echo "<p class='success'>✅ 파일 저장 성공: <code>{$testFilename}</code></p>";
            $audioUrl = '/moodle/local/augmented_teacher/alt42/teachingsupport/audio/' . $testFilename;
            echo "<audio controls src='{$audioUrl}'></audio>";
        } else {
            echo "<p class='error'>❌ 파일 저장 실패</p>";
        }
    } else {
        echo "<p class='error'>❌ TTS 생성 실패</p>";
        if ($curlError) {
            echo "<p>CURL 오류: <code>{$curlError}</code></p>";
        }
        if ($httpCode !== 200) {
            echo "<p>응답: <pre>" . htmlspecialchars(substr($audioData, 0, 500)) . "</pre></p>";
        }
    }
} else {
    echo "<p><a href='?test_tts=1' class='btn'>🔊 TTS 테스트 실행</a></p>";
}
echo "</div>";

// 6. OpenAI Vision API 테스트
echo "<div class='section'>";
echo "<h3>6. OpenAI Vision API 테스트</h3>";

if (isset($_GET['test_vision'])) {
    echo "<p>🔄 Vision API 테스트 중...</p>";
    
    // 간단한 테스트 메시지
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'user', 'content' => '안녕하세요. 1+1=? 간단히 대답해주세요.']
        ],
        'max_tokens' => 100
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $duration = round($endTime - $startTime, 2);
    
    echo "<p>응답 시간: <code>{$duration}초</code></p>";
    echo "<p>HTTP 상태: <code>{$httpCode}</code></p>";
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? 'N/A';
        echo "<p class='success'>✅ Vision API 연결 성공!</p>";
        echo "<p>응답: <code>{$content}</code></p>";
    } else {
        echo "<p class='error'>❌ Vision API 연결 실패</p>";
        if ($curlError) {
            echo "<p>CURL 오류: <code>{$curlError}</code></p>";
        }
        echo "<p>응답: <pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre></p>";
    }
} else {
    echo "<p><a href='?test_vision=1' class='btn'>🔬 Vision API 테스트 실행</a></p>";
}
echo "</div>";

// 7. 최근 상호작용 확인
echo "<div class='section'>";
echo "<h3>7. 최근 TTS 생성 상호작용</h3>";
try {
    $recentInteractions = $DB->get_records_sql(
        "SELECT id, userid, type, status, audio_url, timecreated FROM {ktm_teaching_interactions} ORDER BY id DESC LIMIT 5"
    );
    
    if ($recentInteractions) {
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background:#0f0f23;'><th>ID</th><th>User</th><th>Type</th><th>Status</th><th>Audio</th><th>Time</th></tr>";
        foreach ($recentInteractions as $i) {
            $hasAudio = !empty($i->audio_url) ? '✅' : '❌';
            $time = date('Y-m-d H:i', $i->timecreated);
            echo "<tr style='border-bottom:1px solid #333;'>";
            echo "<td>{$i->id}</td><td>{$i->userid}</td><td>{$i->type}</td><td>{$i->status}</td><td>{$hasAudio}</td><td>{$time}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>최근 상호작용 없음</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>조회 오류: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>📋 결론</h3>";
echo "<p>모든 테스트를 통과하면 TTS 생성이 정상적으로 동작해야 합니다.</p>";
echo "<p>문제가 있다면 서버 로그(<code>/var/log/apache2/error.log</code>)를 확인하세요.</p>";
echo "</div>";

echo "</body></html>";

