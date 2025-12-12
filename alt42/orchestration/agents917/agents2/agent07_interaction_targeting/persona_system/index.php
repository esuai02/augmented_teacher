<?php
/**
 * Agent07 Persona System - Test Interface
 *
 * 페르소나 기반 채팅 테스트 인터페이스
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/index.php
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 인증 확인
require_login();

$pageTitle = "Agent07 Persona System Test";
$apiUrl = "api/chat.php";
$dbSetupUrl = "api/db_setup.php";

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px 16px 0 0;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .header .user-info {
            color: #666;
            font-size: 0.9rem;
        }

        .header .links {
            margin-top: 10px;
        }

        .header .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 0.85rem;
        }

        .header .links a:hover {
            text-decoration: underline;
        }

        .chat-container {
            background: #f8f9fa;
            min-height: 400px;
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
        }

        .message {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
        }

        .message.user {
            align-items: flex-end;
        }

        .message.assistant {
            align-items: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 16px;
            position: relative;
        }

        .message.user .message-bubble {
            background: #667eea;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.assistant .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .message-meta {
            font-size: 0.75rem;
            color: #888;
            margin-top: 4px;
            padding: 0 4px;
        }

        .message.assistant .message-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .persona-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .situation-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1565c0;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .confidence-badge {
            display: inline-block;
            background: #fff3e0;
            color: #e65100;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .input-container {
            background: white;
            border-radius: 0 0 16px 16px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .context-panel {
            background: #f0f0f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .context-panel h3 {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 10px;
        }

        .context-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 8px;
        }

        .context-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .context-item label {
            font-size: 0.8rem;
            color: #666;
        }

        .context-item select,
        .context-item input[type="number"] {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .context-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }

        .input-row {
            display: flex;
            gap: 10px;
        }

        .input-row input[type="text"] {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 24px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-row input[type="text"]:focus {
            border-color: #667eea;
        }

        .input-row button {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .input-row button:hover {
            background: #5a6fd6;
        }

        .input-row button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .debug-panel {
            margin-top: 20px;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .debug-panel h3 {
            font-size: 1rem;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .debug-panel .toggle-btn {
            font-size: 0.8rem;
            padding: 4px 12px;
            background: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .debug-content {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 12px;
            font-family: monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }

        .debug-content.show {
            display: block;
        }

        .loading {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .loading-dots {
            display: flex;
            gap: 4px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $pageTitle; ?></h1>
            <div class="user-info">
                User ID: <?php echo $USER->id; ?> |
                Name: <?php echo fullname($USER); ?>
            </div>
            <div class="links">
                <a href="<?php echo $dbSetupUrl; ?>">DB Setup</a>
                <a href="<?php echo $apiUrl; ?>">API Status</a>
                <a href="docs/personas.md" target="_blank">Personas Doc</a>
            </div>
        </div>

        <div class="chat-container" id="chatContainer">
            <div class="message assistant">
                <div class="message-bubble">
                    안녕하세요! Agent07 페르소나 시스템 테스트 인터페이스입니다.<br>
                    아래 컨텍스트를 설정하고 메시지를 보내보세요.
                </div>
            </div>
        </div>

        <div class="input-container">
            <div class="context-panel">
                <h3>Context Settings</h3>
                <div class="context-row">
                    <div class="context-item">
                        <label>Activity:</label>
                        <select id="ctxActivity">
                            <option value="idle">idle</option>
                            <option value="learning" selected>learning</option>
                            <option value="pomodoro">pomodoro</option>
                            <option value="reviewing">reviewing</option>
                            <option value="planning">planning</option>
                        </select>
                    </div>
                    <div class="context-item">
                        <label>Pomodoro:</label>
                        <input type="checkbox" id="ctxPomodoro">
                    </div>
                    <div class="context-item">
                        <label>Focus:</label>
                        <input type="number" id="ctxFocus" value="0.5" min="0" max="1" step="0.1" style="width: 60px;">
                    </div>
                    <div class="context-item">
                        <label>Motivation:</label>
                        <input type="number" id="ctxMotivation" value="0.5" min="0" max="1" step="0.1" style="width: 60px;">
                    </div>
                </div>
                <div class="context-row">
                    <div class="context-item">
                        <label>Time of Day:</label>
                        <select id="ctxTimeOfDay">
                            <option value="morning">morning</option>
                            <option value="afternoon" selected>afternoon</option>
                            <option value="evening">evening</option>
                            <option value="night">night</option>
                        </select>
                    </div>
                    <div class="context-item">
                        <label>Session Duration (min):</label>
                        <input type="number" id="ctxDuration" value="30" min="0" max="480" style="width: 60px;">
                    </div>
                    <div class="context-item">
                        <label>Recent Errors:</label>
                        <input type="number" id="ctxErrors" value="0" min="0" max="100" style="width: 60px;">
                    </div>
                </div>
            </div>

            <div class="input-row">
                <input type="text" id="messageInput" placeholder="메시지를 입력하세요..." autofocus>
                <button id="sendBtn" onclick="sendMessage()">보내기</button>
            </div>
        </div>

        <div class="debug-panel">
            <h3>
                Debug Info
                <button class="toggle-btn" onclick="toggleDebug()">Toggle</button>
            </h3>
            <div class="debug-content" id="debugContent"></div>
        </div>
    </div>

    <script>
        const chatContainer = document.getElementById('chatContainer');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const debugContent = document.getElementById('debugContent');

        // Enter key로 전송
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function getContext() {
            return {
                current_activity: document.getElementById('ctxActivity').value,
                pomodoro_active: document.getElementById('ctxPomodoro').checked,
                focus_score: parseFloat(document.getElementById('ctxFocus').value),
                motivation_score: parseFloat(document.getElementById('ctxMotivation').value),
                time_of_day: document.getElementById('ctxTimeOfDay').value,
                session_duration: parseInt(document.getElementById('ctxDuration').value),
                recent_error_count: parseInt(document.getElementById('ctxErrors').value)
            };
        }

        function addMessage(content, type, meta = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + type;

            let metaHtml = '';
            if (meta && type === 'assistant') {
                metaHtml = '<div class="message-meta">';
                if (meta.persona_name) {
                    metaHtml += '<span class="persona-badge">' + meta.persona_id + ': ' + meta.persona_name + '</span>';
                }
                if (meta.situation_name) {
                    metaHtml += '<span class="situation-badge">' + meta.situation_id + ': ' + meta.situation_name + '</span>';
                }
                if (meta.confidence !== undefined) {
                    metaHtml += '<span class="confidence-badge">신뢰도: ' + (meta.confidence * 100).toFixed(0) + '%</span>';
                }
                metaHtml += '</div>';
            }

            messageDiv.innerHTML = `
                <div class="message-bubble">${content.replace(/\n/g, '<br>')}</div>
                ${metaHtml}
            `;

            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function addLoading() {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message assistant';
            loadingDiv.id = 'loadingMessage';
            loadingDiv.innerHTML = `
                <div class="message-bubble">
                    <div class="loading">
                        <div class="loading-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        응답 생성 중...
                    </div>
                </div>
            `;
            chatContainer.appendChild(loadingDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function removeLoading() {
            const loading = document.getElementById('loadingMessage');
            if (loading) {
                loading.remove();
            }
        }

        function updateDebug(data) {
            debugContent.textContent = JSON.stringify(data, null, 2);
        }

        function toggleDebug() {
            debugContent.classList.toggle('show');
        }

        async function sendMessage() {
            const message = messageInput.value.trim();

            // 빈 메시지도 허용 (인사/시작 시점)
            addMessage(message || '(빈 메시지 - 인사/시작)', 'user');
            messageInput.value = '';
            sendBtn.disabled = true;

            addLoading();

            try {
                const requestData = {
                    message: message,
                    context: getContext()
                };

                const response = await fetch('<?php echo $apiUrl; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                removeLoading();

                if (result.success) {
                    addMessage(result.data.response, 'assistant', {
                        persona_id: result.data.persona_id,
                        persona_name: result.data.persona_name,
                        situation_id: result.data.situation_id,
                        situation_name: result.data.situation_name,
                        confidence: result.data.confidence
                    });

                    updateDebug({
                        request: requestData,
                        response: result
                    });
                } else {
                    addMessage('오류: ' + result.error, 'assistant');
                    updateDebug({
                        request: requestData,
                        error: result
                    });
                }
            } catch (error) {
                removeLoading();
                addMessage('네트워크 오류: ' + error.message, 'assistant');
                updateDebug({ error: error.message });
            }

            sendBtn.disabled = false;
            messageInput.focus();
        }

        // 초기 디버그 정보
        updateDebug({
            status: 'ready',
            api_url: '<?php echo $apiUrl; ?>',
            user_id: <?php echo $USER->id; ?>
        });
    </script>
</body>
</html>
