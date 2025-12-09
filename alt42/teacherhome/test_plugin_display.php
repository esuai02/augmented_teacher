<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
require_login();
$userid=$_GET["userid"]; 
if($userid==NULL)$userid=$USER->id;

// DB에서 직접 플러그인 데이터 조회
$mysqli = new mysqli('localhost', 'root', '', 'alt42db');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// 플러그인 데이터 조회
$result = $mysqli->query("SELECT * FROM mdl_alt42DB_card_plugin_settings WHERE user_id = '$userid' ORDER BY category, card_title, display_order");
$plugins = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $plugins[] = $row;
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>플러그인 테스트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .info-box {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .plugin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        .plugin-card {
            background: #333;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #444;
        }
        .plugin-card h3 {
            margin-top: 0;
            color: #4CAF50;
        }
        .plugin-detail {
            margin: 5px 0;
            font-size: 14px;
        }
        .plugin-detail strong {
            color: #FFA500;
        }
        .test-button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .test-button:hover {
            background: #45a049;
        }
        .error {
            color: #ff6b6b;
        }
        .success {
            color: #51cf66;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>플러그인 디스플레이 테스트</h1>
        
        <div class="info-box">
            <h2>사용자 정보</h2>
            <p><strong>User ID (PHP):</strong> <?php echo $userid; ?></p>
            <p><strong>User ID (JS):</strong> <span id="jsUserId">로딩중...</span></p>
            <p><strong>Plugin Client User ID:</strong> <span id="clientUserId">로딩중...</span></p>
        </div>
        
        <div class="info-box">
            <h2>DB에 저장된 플러그인 (총 <?php echo count($plugins); ?>개)</h2>
            <?php if (empty($plugins)): ?>
                <p class="error">⚠️ 데이터베이스에 플러그인이 없습니다!</p>
            <?php else: ?>
                <div class="plugin-grid">
                    <?php foreach ($plugins as $plugin): ?>
                        <div class="plugin-card">
                            <h3><?php echo htmlspecialchars($plugin['plugin_name'] ?? '이름 없음'); ?></h3>
                            <div class="plugin-detail"><strong>ID:</strong> <?php echo $plugin['id']; ?></div>
                            <div class="plugin-detail"><strong>카테고리:</strong> <?php echo htmlspecialchars($plugin['category']); ?></div>
                            <div class="plugin-detail"><strong>카드 제목:</strong> <?php echo htmlspecialchars($plugin['card_title']); ?></div>
                            <div class="plugin-detail"><strong>플러그인 타입:</strong> <?php echo htmlspecialchars($plugin['plugin_id']); ?></div>
                            <div class="plugin-detail"><strong>활성화:</strong> <?php echo $plugin['is_active'] ? '✅ 활성' : '❌ 비활성'; ?></div>
                            <div class="plugin-detail"><strong>순서:</strong> <?php echo $plugin['display_order']; ?></div>
                            <div class="plugin-detail"><strong>생성일:</strong> <?php echo date('Y-m-d H:i:s', $plugin['timecreated']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h2>API 테스트</h2>
            <button class="test-button" onclick="testAPI()">API 테스트 실행</button>
            <div id="apiResult" style="margin-top: 15px;"></div>
        </div>
        
        <div class="info-box">
            <h2>클라이언트 로드 테스트</h2>
            <button class="test-button" onclick="testClientLoad()">클라이언트 로드 테스트</button>
            <div id="clientResult" style="margin-top: 15px;"></div>
        </div>
    </div>
    
    <script src="plugin_settings_client.js"></script>
    <script>
        // PHP에서 전달된 사용자 ID
        window.currentUserId = <?php echo json_encode($userid); ?>;
        
        // 페이지 로드 시 정보 표시
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('jsUserId').textContent = window.currentUserId;
            
            // 플러그인 클라이언트 초기화
            window.ktmPluginClient = new KTMPluginSettingsClient('plugin_settings_api_real.php');
            
            setTimeout(() => {
                document.getElementById('clientUserId').textContent = window.ktmPluginClient.currentUserId;
            }, 500);
        });
        
        // API 테스트
        async function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>테스트 중...</p>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'getCardSettings');
                formData.append('user_id', window.currentUserId);
                
                const response = await fetch('plugin_settings_api_real.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Raw API response:', text);
                
                const result = JSON.parse(text);
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <p class="success">✅ API 응답 성공!</p>
                        <p><strong>데이터 개수:</strong> ${result.data ? result.data.length : 0}개</p>
                        <pre style="background: #222; padding: 10px; border-radius: 5px; overflow-x: auto;">
${JSON.stringify(result.data, null, 2)}
                        </pre>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="error">❌ API 오류: ${result.error}</p>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">❌ 테스트 실패: ${error.message}</p>`;
                console.error('API test error:', error);
            }
        }
        
        // 클라이언트 로드 테스트
        async function testClientLoad() {
            const resultDiv = document.getElementById('clientResult');
            resultDiv.innerHTML = '<p>테스트 중...</p>';
            
            try {
                // 카드 설정 로드
                await window.ktmPluginClient.loadCardSettings();
                
                // 모든 카드 가져오기
                const allCards = window.ktmPluginClient.getCardSettings();
                
                let html = `<p class="success">✅ 클라이언트 로드 성공!</p>`;
                
                // 카테고리별로 표시
                for (const [category, tabData] of Object.entries(allCards)) {
                    html += `<h4>카테고리: ${category}</h4>`;
                    for (const [tabTitle, cards] of Object.entries(tabData)) {
                        html += `<p style="margin-left: 20px;"><strong>${tabTitle}:</strong> ${cards.length}개 카드</p>`;
                    }
                }
                
                html += `<pre style="background: #222; padding: 10px; border-radius: 5px; overflow-x: auto;">
${JSON.stringify(allCards, null, 2)}
                </pre>`;
                
                resultDiv.innerHTML = html;
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">❌ 클라이언트 로드 실패: ${error.message}</p>`;
                console.error('Client load test error:', error);
            }
        }
    </script>
</body>
</html>