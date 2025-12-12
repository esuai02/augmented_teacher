<?php
/**
 * Agent03 Goals Analysis Persona System 테스트 페이지
 *
 * 페르소나 엔진 및 API 테스트를 위한 간단한 UI
 *
 * @package AugmentedTeacher\Agent03\PersonaSystem
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 현재 파일 경로
$currentFile = __FILE__;
$basePath = dirname($currentFile);
$baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent03 Goals Analysis - Persona System Test</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .full-width {
            grid-column: 1 / -1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        button.secondary {
            background: #95a5a6;
        }
        button.secondary:hover {
            background: #7f8c8d;
        }
        button.danger {
            background: #e74c3c;
        }
        button.danger:hover {
            background: #c0392b;
        }
        .result {
            background: #ecf0f1;
            border-radius: 4px;
            padding: 15px;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .success {
            border-left: 4px solid #27ae60;
        }
        .error {
            border-left: 4px solid #e74c3c;
        }
        .context-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 2px;
        }
        .context-G0 { background: #3498db; color: white; }
        .context-G1 { background: #27ae60; color: white; }
        .context-G2 { background: #f39c12; color: white; }
        .context-G3 { background: #9b59b6; color: white; }
        .context-CRISIS { background: #e74c3c; color: white; }
        .info-box {
            background: #e8f4fd;
            border: 1px solid #3498db;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .quick-messages {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        .quick-message {
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .quick-message:hover {
            background: #d5dbdb;
        }
        .response-text {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin: 10px 0;
            line-height: 1.6;
        }
        .meta-info {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>🎯 Agent03 Goals Analysis - Persona System Test</h1>

    <div class="info-box">
        <strong>컨텍스트 코드:</strong><br>
        <span class="context-badge context-G0">G0: 목표 설정</span>
        <span class="context-badge context-G1">G1: 목표 진행</span>
        <span class="context-badge context-G2">G2: 정체/위기</span>
        <span class="context-badge context-G3">G3: 목표 재설정</span>
        <span class="context-badge context-CRISIS">CRISIS: 위기 개입</span>
    </div>

    <div class="container">
        <!-- 채팅 테스트 패널 -->
        <div class="panel">
            <h2>💬 채팅 테스트</h2>

            <label>빠른 메시지 선택:</label>
            <div class="quick-messages">
                <span class="quick-message" onclick="setMessage('이번 학기 목표를 세우고 싶어요')">목표 설정 요청</span>
                <span class="quick-message" onclick="setMessage('내 목표 달성률이 어떻게 되나요?')">진행 상황 확인</span>
                <span class="quick-message" onclick="setMessage('목표가 너무 많아서 힘들어요')">과부하 상황</span>
                <span class="quick-message" onclick="setMessage('진전이 없어서 포기하고 싶어요')">정체 상황</span>
                <span class="quick-message" onclick="setMessage('목표를 달성했어요!')">목표 달성</span>
                <span class="quick-message" onclick="setMessage('전교 1등 하고 싶어요')">야심찬 목표</span>
                <span class="quick-message" onclick="setMessage('뭘 해야 할지 모르겠어요')">방향 상실</span>
                <span class="quick-message" onclick="setMessage('너무 힘들어서 못 견디겠어요')" style="background:#ffe0e0;">위기 테스트</span>
            </div>

            <label for="message">메시지:</label>
            <textarea id="message" placeholder="목표 관련 메시지를 입력하세요...">이번 학기 목표를 세우고 싶어요</textarea>

            <label for="context">컨텍스트 (선택):</label>
            <select id="context">
                <option value="">자동 감지</option>
                <option value="G0">G0: 목표 설정</option>
                <option value="G1">G1: 목표 진행</option>
                <option value="G2">G2: 정체/위기</option>
                <option value="G3">G3: 목표 재설정</option>
                <option value="CRISIS">CRISIS: 위기 개입</option>
            </select>

            <label for="userId">사용자 ID:</label>
            <input type="text" id="userId" value="<?php echo isset($USER->id) ? $USER->id : 1; ?>">

            <button onclick="sendChat()">📤 메시지 전송</button>
            <button class="secondary" onclick="clearResult('chatResult')">🗑️ 결과 지우기</button>

            <h3>응답:</h3>
            <div id="chatResult" class="result"></div>
        </div>

        <!-- API 정보 패널 -->
        <div class="panel">
            <h2>📡 API 정보</h2>

            <button onclick="getApiInfo()">API 정보 조회</button>
            <button class="secondary" onclick="testApiGet()">GET 테스트</button>

            <h3>API 엔드포인트:</h3>
            <code style="display:block; background:#2c3e50; color:#ecf0f1; padding:10px; border-radius:4px; margin:10px 0; word-break:break-all;">
                <?php echo $baseUrl; ?>/api/goals_chat.php
            </code>

            <h3>결과:</h3>
            <div id="apiResult" class="result"></div>
        </div>

        <!-- 템플릿 테스트 패널 -->
        <div class="panel full-width">
            <h2>📋 템플릿 테스트</h2>

            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="personaId">페르소나 ID:</label>
                    <select id="personaId">
                        <optgroup label="G0: 목표 설정">
                            <option value="G0_P1">G0_P1: 야심찬 과목표 설정자</option>
                            <option value="G0_P2">G0_P2: 목표 회피형</option>
                            <option value="G0_P3">G0_P3: 모호한 목표 설정자</option>
                            <option value="G0_P4">G0_P4: 의존적 목표 설정자</option>
                            <option value="G0_P5">G0_P5: 균형 잡힌 목표 설정자</option>
                            <option value="G0_P6">G0_P6: 두려움 기반 회피자</option>
                        </optgroup>
                        <optgroup label="G1: 목표 진행">
                            <option value="G1_P1">G1_P1: 꾸준한 진행자</option>
                            <option value="G1_P2">G1_P2: 급진적 진행자</option>
                            <option value="G1_P3">G1_P3: 불규칙 진행자</option>
                            <option value="G1_P4">G1_P4: 외부 장애 경험자</option>
                            <option value="G1_P5">G1_P5: 동기 저하 경험자</option>
                        </optgroup>
                        <optgroup label="G2: 정체/위기">
                            <option value="G2_P1">G2_P1: 일시적 좌절자</option>
                            <option value="G2_P2">G2_P2: 만성적 정체자</option>
                            <option value="G2_P3">G2_P3: 포기 선언자</option>
                            <option value="G2_P4">G2_P4: 번아웃 경험자</option>
                        </optgroup>
                        <optgroup label="G3: 목표 재설정">
                            <option value="G3_P1">G3_P1: 성공적 달성자</option>
                            <option value="G3_P2">G3_P2: 전략적 조정자</option>
                        </optgroup>
                        <optgroup label="CRISIS: 위기 개입">
                            <option value="CRISIS_P1">CRISIS_P1: 즉시 개입 필요</option>
                            <option value="CRISIS_P2">CRISIS_P2: 안정화 필요</option>
                        </optgroup>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label for="templateType">템플릿 타입:</label>
                    <select id="templateType">
                        <option value="initial">initial (초기 응답)</option>
                        <option value="progress">progress (진행 관련)</option>
                        <option value="stagnation">stagnation (정체 관련)</option>
                        <option value="achievement">achievement (달성 관련)</option>
                        <option value="adjustment">adjustment (조정 관련)</option>
                        <option value="level_0">level_0 (위기 레벨 0)</option>
                        <option value="level_1">level_1 (위기 레벨 1)</option>
                        <option value="level_2">level_2 (위기 레벨 2)</option>
                        <option value="level_3">level_3 (위기 레벨 3)</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label for="userName">사용자 이름:</label>
                    <input type="text" id="userName" value="김철수" style="margin-bottom:0;">
                </div>
            </div>

            <button onclick="testTemplate()" style="margin-top: 15px;">📝 템플릿 테스트</button>

            <h3>템플릿 결과:</h3>
            <div id="templateResult" class="result"></div>
        </div>
    </div>

    <script>
        const apiUrl = '<?php echo $baseUrl; ?>/api/goals_chat.php';

        function setMessage(text) {
            document.getElementById('message').value = text;
        }

        function clearResult(elementId) {
            document.getElementById(elementId).innerHTML = '';
            document.getElementById(elementId).className = 'result';
        }

        async function sendChat() {
            const message = document.getElementById('message').value;
            const context = document.getElementById('context').value;
            const userId = document.getElementById('userId').value;

            if (!message.trim()) {
                alert('메시지를 입력해주세요.');
                return;
            }

            const resultDiv = document.getElementById('chatResult');
            resultDiv.innerHTML = '처리 중...';
            resultDiv.className = 'result';

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        context: context,
                        user_id: parseInt(userId)
                    })
                });

                const data = await response.json();

                // 응답 표시
                let html = '';

                if (data.success) {
                    html += '<div class="response-text">' + (data.response?.text || '응답 없음') + '</div>';
                    html += '<div class="meta-info">';
                    html += '<strong>컨텍스트:</strong> ' + (data.context?.detected || 'N/A');
                    html += ' | <strong>페르소나:</strong> ' + (data.persona?.persona_id || 'N/A');
                    html += ' | <strong>톤:</strong> ' + (data.persona?.tone || 'N/A');
                    html += ' | <strong>개입:</strong> ' + (data.persona?.intervention || 'N/A');
                    html += ' | <strong>처리시간:</strong> ' + (data.meta?.processing_time_ms || 0) + 'ms';
                    html += '</div>';
                    html += '<hr style="margin: 15px 0; border-color: #ddd;">';
                }

                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';

                resultDiv.innerHTML = html;
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');

            } catch (error) {
                resultDiv.innerHTML = 'Error: ' + error.message;
                resultDiv.className = 'result error';
            }
        }

        async function getApiInfo() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '로딩 중...';

            try {
                const response = await fetch(apiUrl);
                const data = await response.json();
                resultDiv.innerHTML = JSON.stringify(data, null, 2);
                resultDiv.className = 'result success';
            } catch (error) {
                resultDiv.innerHTML = 'Error: ' + error.message;
                resultDiv.className = 'result error';
            }
        }

        async function testApiGet() {
            const message = encodeURIComponent(document.getElementById('message').value);
            const context = document.getElementById('context').value;
            const url = apiUrl + '?message=' + message + (context ? '&context=' + context : '');

            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '로딩 중...\n\nURL: ' + url;

            try {
                const response = await fetch(url);
                const data = await response.json();
                resultDiv.innerHTML = 'URL: ' + url + '\n\n' + JSON.stringify(data, null, 2);
                resultDiv.className = 'result success';
            } catch (error) {
                resultDiv.innerHTML = 'Error: ' + error.message;
                resultDiv.className = 'result error';
            }
        }

        function testTemplate() {
            const personaId = document.getElementById('personaId').value;
            const templateType = document.getElementById('templateType').value;
            const userName = document.getElementById('userName').value;

            const resultDiv = document.getElementById('templateResult');

            // 여기서는 클라이언트에서 간단한 시뮬레이션
            // 실제로는 서버에서 템플릿을 로드해야 함
            const templates = {
                'G0_P1_initial': {
                    tone: 'Gentle',
                    intervention: 'GapAnalysis',
                    text: userName + '님, 높은 목표를 세우시는 열정이 느껴져요! 다만, 큰 목표를 작은 단계로 나누면 더 달성하기 쉬워요. 먼저 이번 주에 할 수 있는 작은 목표부터 시작해볼까요?'
                },
                'G0_P2_initial': {
                    tone: 'Warm',
                    intervention: 'EmotionalSupport',
                    text: userName + '님, 목표를 세우는 게 부담스러우신가요? 괜찮아요. 아주 작은 것부터 시작해도 돼요. 오늘 하루 동안 하고 싶은 작은 일이 있나요?'
                },
                'G1_P1_progress': {
                    tone: 'Encouraging',
                    intervention: 'InformationProvision',
                    text: userName + '님, 꾸준히 잘 하고 계시네요! 현재 65% 달성했어요. 이 페이스를 유지하면 목표 달성이 눈앞이에요!'
                },
                'G2_P3_stagnation': {
                    tone: 'Empathetic',
                    intervention: 'EmotionalSupport',
                    text: userName + '님, 포기하고 싶은 마음 이해해요. 하지만 잠깐만요, 이 목표를 처음 세웠을 때를 기억해보세요. 왜 이걸 원했나요?'
                },
                'CRISIS_P1_level_0': {
                    tone: 'Calm',
                    intervention: 'CrisisIntervention',
                    text: userName + '님, 지금 많이 힘드시군요. 당신의 안전이 가장 중요해요.\n\n📞 자살예방상담전화: 1393 (24시간)\n📞 정신건강위기상담전화: 1577-0199'
                }
            };

            const key = personaId + '_' + templateType;
            const template = templates[key];

            if (template) {
                let html = '<div class="response-text">' + template.text + '</div>';
                html += '<div class="meta-info">';
                html += '<strong>톤:</strong> ' + template.tone;
                html += ' | <strong>개입 패턴:</strong> ' + template.intervention;
                html += '</div>';
                resultDiv.innerHTML = html;
                resultDiv.className = 'result success';
            } else {
                resultDiv.innerHTML = '해당 템플릿을 찾을 수 없습니다.\n\n요청: ' + key + '\n\n참고: 이 테스트 페이지에서는 일부 템플릿만 시뮬레이션됩니다.\n실제 템플릿은 서버의 goal_templates.php에서 로드됩니다.';
                resultDiv.className = 'result';
            }
        }

        // 페이지 로드 시 API 정보 조회
        document.addEventListener('DOMContentLoaded', function() {
            getApiInfo();
        });
    </script>

    <footer style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #7f8c8d; font-size: 12px;">
        <p>
            <strong>파일 위치:</strong> <?php echo $currentFile; ?><br>
            <strong>API URL:</strong> <a href="<?php echo $baseUrl; ?>/api/goals_chat.php" target="_blank"><?php echo $baseUrl; ?>/api/goals_chat.php</a><br>
            <strong>관련 문서:</strong>
            <a href="<?php echo $baseUrl; ?>/personas.md" target="_blank">personas.md</a> |
            <a href="<?php echo $baseUrl; ?>/contextlist.md" target="_blank">contextlist.md</a> |
            <a href="<?php echo $baseUrl; ?>/rules.yaml" target="_blank">rules.yaml</a>
        </p>
    </footer>
</body>
</html>
<?php
/*
 * 테스트 페이지 URL:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/test.php
 *
 * 관련 DB 테이블:
 * - at_user_goals: 사용자 목표 정보
 * - at_goal_activities: 목표 활동 로그
 * - at_agent_persona_state: 페르소나 상태
 *
 * 파일 위치:
 * /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/test.php:380
 */
?>
