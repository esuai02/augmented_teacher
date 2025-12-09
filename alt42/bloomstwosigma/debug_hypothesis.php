<?php
/**
 * 가설 저장 디버깅
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;

header('Content-Type: application/json');

// POST 요청인 경우 API 테스트
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once(__DIR__ . '/src/api/database_api.php');
    exit;
}

// GET 요청인 경우 디버깅 정보 표시
?>
<!DOCTYPE html>
<html>
<head>
    <title>가설 저장 디버깅</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>가설 저장 디버깅</h1>
    
    <div class="test-section">
        <h2>1. 데이터베이스 연결 확인</h2>
        <?php
        try {
            // 테이블 존재 확인
            $tables = [
                'mdl_alt42_experiments',
                'mdl_alt42_hypotheses',
                'mdl_alt42_experiment_logs'
            ];
            
            foreach ($tables as $table) {
                $result = $DB->get_records_sql("SHOW TABLES LIKE '$table'");
                if (empty($result)) {
                    echo "<p class='error'>❌ 테이블 '$table'이 존재하지 않습니다.</p>";
                } else {
                    echo "<p class='success'>✅ 테이블 '$table'이 존재합니다.</p>";
                }
            }
            
            echo "<p class='success'>✅ 데이터베이스 연결 성공</p>";
            echo "<p>DB Host: " . $CFG->dbhost . "</p>";
            echo "<p>DB Name: " . $CFG->dbname . "</p>";
            echo "<p>Current User ID: " . $USER->id . "</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ DB 연결 실패: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. 실험 생성 테스트</h2>
        <button onclick="testExperimentCreation()">실험 생성 테스트</button>
        <div id="experiment-result"></div>
    </div>
    
    <div class="test-section">
        <h2>3. 가설 저장 테스트</h2>
        <input type="text" id="experiment-id" placeholder="실험 ID (위에서 생성된 ID 입력)">
        <textarea id="hypothesis-text" placeholder="가설 내용 입력">메타인지 피드백이 학습 성과를 향상시킬 것이다.</textarea>
        <br>
        <button onclick="testHypothesisSaving()">가설 저장 테스트</button>
        <div id="hypothesis-result"></div>
    </div>
    
    <div class="test-section">
        <h2>4. 저장된 데이터 확인</h2>
        <button onclick="checkSavedData()">저장된 데이터 확인</button>
        <div id="data-result"></div>
    </div>

    <script>
        // PHP 사용자 정보를 JavaScript로 전달
        window.USER_ID = <?php echo $USER->id; ?>;
        
        async function testExperimentCreation() {
            const resultDiv = document.getElementById('experiment-result');
            resultDiv.innerHTML = '<p>실험 생성 중...</p>';
            
            try {
                const response = await fetch('debug_hypothesis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'save_experiment',
                        experiment_name: '디버깅 테스트 실험 ' + new Date().toLocaleString(),
                        description: '가설 저장 테스트를 위한 실험',
                        start_date: new Date().toISOString().split('T')[0],
                        duration_weeks: 8,
                        status: 'planned',
                        created_by: window.USER_ID
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <p>✅ 실험 생성 성공!</p>
                            <p>실험 ID: ${result.experiment_id}</p>
                            <pre>${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                    document.getElementById('experiment-id').value = result.experiment_id;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <p>❌ 실험 생성 실패</p>
                            <pre>${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <p>❌ 오류 발생: ${error.message}</p>
                        <pre>${error.stack}</pre>
                    </div>
                `;
            }
        }
        
        async function testHypothesisSaving() {
            const experimentId = document.getElementById('experiment-id').value;
            const hypothesisText = document.getElementById('hypothesis-text').value;
            const resultDiv = document.getElementById('hypothesis-result');
            
            if (!experimentId) {
                resultDiv.innerHTML = '<div class="error">❌ 먼저 실험을 생성하거나 실험 ID를 입력하세요.</div>';
                return;
            }
            
            resultDiv.innerHTML = '<p>가설 저장 중...</p>';
            
            try {
                const response = await fetch('debug_hypothesis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'save_hypothesis',
                        experiment_id: experimentId,
                        hypothesis_text: hypothesisText,
                        hypothesis_type: 'primary',
                        author_id: window.USER_ID
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <p>✅ 가설 저장 성공!</p>
                            <p>가설 ID: ${result.hypothesis_id}</p>
                            <pre>${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <p>❌ 가설 저장 실패</p>
                            <pre>${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <p>❌ 오류 발생: ${error.message}</p>
                        <pre>${error.stack}</pre>
                    </div>
                `;
            }
        }
        
        async function checkSavedData() {
            const resultDiv = document.getElementById('data-result');
            resultDiv.innerHTML = '<p>데이터 확인 중...</p>';
            
            try {
                // 실험 목록 확인
                const experimentsResponse = await fetch('debug_hypothesis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_experiments_list',
                        limit: 5,
                        offset: 0
                    })
                });
                
                const experiments = await experimentsResponse.json();
                
                let html = '<h3>최근 실험 목록:</h3>';
                if (experiments.success && experiments.experiments.length > 0) {
                    html += '<pre>' + JSON.stringify(experiments.experiments, null, 2) + '</pre>';
                    
                    // 첫 번째 실험의 가설 확인
                    const firstExperimentId = experiments.experiments[0].id;
                    html += `<h3>실험 ID ${firstExperimentId}의 가설:</h3>`;
                    
                    // 가설 데이터는 직접 SQL로 확인 (API가 없으므로)
                    html += '<p>가설 확인을 위해 데이터베이스를 직접 확인하세요:</p>';
                    html += `<code>SELECT * FROM mdl_alt42_hypotheses WHERE experiment_id = ${firstExperimentId};</code>`;
                } else {
                    html += '<p>저장된 실험이 없습니다.</p>';
                }
                
                resultDiv.innerHTML = html;
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <p>❌ 데이터 확인 실패: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        console.log('디버깅 페이지 로드됨. 사용자 ID:', window.USER_ID);
    </script>
</body>
</html>