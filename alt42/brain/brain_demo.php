<?php
/**
 * brain_demo.php - 실시간 AI 튜터 데모 페이지
 * 
 * Brain Layer의 모든 기능을 테스트하고 시연할 수 있는 데모 UI
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/brain_demo.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentId = $USER->id;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧠 Brain Layer Demo - 실시간 AI 튜터</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #334155;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        header p {
            color: var(--text-muted);
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        
        .card-header h2 {
            font-size: 1.2rem;
        }
        
        .card-header .icon {
            font-size: 1.5rem;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: black;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-online {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .status-offline {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .meter {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .meter-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .wavefunction-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .wavefunction-item {
            padding: 10px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 8px;
        }
        
        .wavefunction-item .name {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        
        .wavefunction-item .value {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .log-container {
            max-height: 300px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-time {
            color: var(--text-muted);
            margin-right: 10px;
        }
        
        .log-type {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 10px;
        }
        
        .log-type.speak { background: var(--primary); }
        .log-type.backchannel { background: var(--success); }
        .log-type.interrupt { background: var(--danger); }
        .log-type.decision { background: var(--warning); color: black; }
        
        .tutor-output {
            padding: 20px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));
            border-radius: 12px;
            min-height: 100px;
            margin-top: 15px;
        }
        
        .tutor-text {
            font-size: 1.3rem;
            line-height: 1.6;
        }
        
        .streaming-text {
            display: inline;
        }
        
        .cursor-blink {
            display: inline-block;
            width: 3px;
            height: 1.3em;
            background: var(--primary);
            margin-left: 2px;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .input-group input, .input-group select {
            flex: 1;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 1rem;
        }
        
        .decision-display {
            padding: 15px;
            background: var(--bg);
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .decision-type {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .decision-type.intervene { color: var(--danger); }
        .decision-type.micro_hint { color: var(--warning); }
        .decision-type.observe { color: var(--success); }
        .decision-type.none { color: var(--text-muted); }
        
        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🧠 Brain Layer Demo</h1>
            <p>실시간 AI 튜터 - 양자 판단 엔진 테스트</p>
            <p style="margin-top: 10px;">
                <span class="status-badge status-online" id="status-badge">
                    <span>●</span> Online
                </span>
                Student ID: <?php echo $studentId; ?>
            </p>
        </header>
        
        <div class="grid">
            <!-- 튜터 제어 -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">🎮</span>
                    <h2>튜터 제어</h2>
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-success" onclick="startTutor()">▶️ 시작</button>
                    <button class="btn btn-danger" onclick="stopTutor()">⏹️ 정지</button>
                    <button class="btn btn-primary" onclick="tickOnce()">🔄 Tick</button>
                </div>
                
                <div class="input-group">
                    <select id="mode-select" onchange="changeMode(this.value)">
                        <option value="guide">Guide 모드</option>
                        <option value="active">Active 모드</option>
                        <option value="observe">Observe 모드</option>
                        <option value="silent">Silent 모드</option>
                    </select>
                </div>
                
                <div class="decision-display" id="decision-display">
                    <div class="decision-type none" id="decision-type">대기 중</div>
                    <div id="decision-reason">-</div>
                    <div style="margin-top: 10px;">
                        <span>붕괴 확률: </span>
                        <strong id="collapse-prob">0%</strong>
                    </div>
                    <div class="meter">
                        <div class="meter-fill" id="collapse-meter" style="width: 0%; background: var(--success);"></div>
                    </div>
                </div>
            </div>
            
            <!-- 튜터 출력 -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">🗣️</span>
                    <h2>튜터 발화</h2>
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="streamIntervention()">🎯 개입 스트리밍</button>
                    <button class="btn btn-warning" onclick="testBackchannel()">💬 추임새 테스트</button>
                </div>
                
                <div class="tutor-output">
                    <div class="tutor-text" id="tutor-text">
                        튜터가 말할 내용이 여기에 표시됩니다...
                    </div>
                </div>
                
                <div class="input-group">
                    <input type="text" id="custom-text" placeholder="수동 발화 텍스트...">
                    <button class="btn btn-primary" onclick="speakCustom()">🔊 발화</button>
                </div>
            </div>
            
            <!-- 파동함수 -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">⚛️</span>
                    <h2>파동함수 상태</h2>
                    <button class="btn btn-primary" style="margin-left: auto;" onclick="refreshWavefunctions()">🔄</button>
                </div>
                
                <div class="wavefunction-grid" id="wavefunction-grid">
                    <div class="wavefunction-item">
                        <div class="name">ψ_Affect (감정)</div>
                        <div class="value" id="psi-affect">-</div>
                        <div class="meter"><div class="meter-fill" id="meter-affect" style="width:50%;background:var(--primary);"></div></div>
                    </div>
                    <div class="wavefunction-item">
                        <div class="name">ψ_Energy (에너지)</div>
                        <div class="value" id="psi-energy">-</div>
                        <div class="meter"><div class="meter-fill" id="meter-energy" style="width:50%;background:var(--success);"></div></div>
                    </div>
                    <div class="wavefunction-item">
                        <div class="name">ψ_Confusion (혼란)</div>
                        <div class="value" id="psi-confusion">-</div>
                        <div class="meter"><div class="meter-fill" id="meter-confusion" style="width:50%;background:var(--warning);"></div></div>
                    </div>
                    <div class="wavefunction-item">
                        <div class="name">ψ_Dropout (이탈)</div>
                        <div class="value" id="psi-dropout">-</div>
                        <div class="meter"><div class="meter-fill" id="meter-dropout" style="width:50%;background:var(--danger);"></div></div>
                    </div>
                    <div class="wavefunction-item">
                        <div class="name">ψ_Flow (몰입)</div>
                        <div class="value" id="psi-flow">-</div>
                        <div class="meter"><div class="meter-fill" id="meter-flow" style="width:50%;background:var(--primary);"></div></div>
                    </div>
                    <div class="wavefunction-item">
                        <div class="name">ψ_Aha (아하!)</div>
                        <div class="value" id="psi-aha">-</div>
                        <div class="meter"><div class="meter-fill" id="meter-aha" style="width:50%;background:var(--success);"></div></div>
                    </div>
                </div>
            </div>
            
            <!-- 로그 -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">📋</span>
                    <h2>활동 로그</h2>
                    <button class="btn btn-danger" style="margin-left: auto;" onclick="clearLog()">🗑️</button>
                </div>
                
                <div class="log-container" id="log-container">
                    <div class="log-entry">
                        <span class="log-time">--:--:--</span>
                        <span class="log-type decision">INIT</span>
                        <span>데모 페이지 로드됨</span>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>🧠 Brain Layer v1.0.0 | Phase 0 + 1 + 2 + 3 완료</p>
            <p style="margin-top: 5px;">Powered by Quantum Decision Engine + GPT-4o + OpenAI TTS</p>
        </footer>
    </div>
    
    <script src="brain_client.js"></script>
    <script>
        const studentId = <?php echo $studentId; ?>;
        let tutor = null;
        let eventSource = null;
        
        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            tutor = new BrainClient({ studentId });
            
            // 이벤트 핸들러 등록
            tutor.on('speak', handleSpeak);
            tutor.on('backchannel', handleBackchannel);
            tutor.on('decision', handleDecision);
            tutor.on('token', handleToken);
            tutor.on('chunk', handleChunk);
            tutor.on('stream_complete', handleStreamComplete);
            tutor.on('error', handleError);
            
            addLog('INIT', '클라이언트 초기화 완료');
            refreshWavefunctions();
        });
        
        // 튜터 시작
        async function startTutor() {
            const mode = document.getElementById('mode-select').value;
            const result = await tutor.start();
            
            if (result.success) {
                addLog('START', `세션 시작 (${mode} 모드)`);
                document.getElementById('status-badge').className = 'status-badge status-online';
                document.getElementById('status-badge').innerHTML = '<span>●</span> Active';
            } else {
                addLog('ERROR', result.error);
            }
        }
        
        // 튜터 정지
        async function stopTutor() {
            tutor.stopPolling();
            tutor.closeStream();
            const result = await tutor.stop();
            
            addLog('STOP', `세션 종료`);
            document.getElementById('status-badge').className = 'status-badge status-offline';
            document.getElementById('status-badge').innerHTML = '<span>●</span> Stopped';
        }
        
        // 단일 Tick
        async function tickOnce() {
            const result = await tutor.tick();
            
            if (result.success) {
                updateDecisionDisplay(result.data);
                addLog('TICK', `결정: ${result.data.action}`);
            }
        }
        
        // 모드 변경
        async function changeMode(mode) {
            await tutor.setMode(mode);
            addLog('MODE', `모드 변경: ${mode}`);
        }
        
        // 개입 스트리밍
        function streamIntervention() {
            document.getElementById('tutor-text').innerHTML = '<span class="streaming-text"></span><span class="cursor-blink"></span>';
            tutor.streamIntervention();
            addLog('STREAM', '개입 스트리밍 시작');
        }
        
        // 추임새 테스트
        async function testBackchannel() {
            const result = await tutor.apiCall('brain_api.php', 'test_tts', {
                text: ['그렇지~', '음...', '오?', '잘했어!'][Math.floor(Math.random() * 4)],
                tone: 'excited'
            });
            
            if (result.success && result.data.audio) {
                tutor.queueAudio(result.data.audio);
                document.getElementById('tutor-text').textContent = result.data.text;
                addLog('BACKCHANNEL', result.data.text);
            }
        }
        
        // 수동 발화
        async function speakCustom() {
            const text = document.getElementById('custom-text').value;
            if (!text) return;
            
            const result = await tutor.speak(text);
            
            if (result.success) {
                document.getElementById('tutor-text').textContent = text;
                addLog('SPEAK', text);
            }
            
            document.getElementById('custom-text').value = '';
        }
        
        // 파동함수 새로고침
        async function refreshWavefunctions() {
            const result = await tutor.getWavefunctions();
            
            if (result.success) {
                const wf = result.data.wavefunctions;
                
                updateWavefunction('affect', wf.psi_affect);
                updateWavefunction('energy', wf.psi_energy);
                updateWavefunction('confusion', wf.psi_confusion);
                updateWavefunction('dropout', wf.psi_dropout);
                updateWavefunction('flow', wf.psi_flow);
                updateWavefunction('aha', wf.psi_aha);
            }
        }
        
        function updateWavefunction(name, value) {
            const percent = Math.round(value * 100);
            document.getElementById(`psi-${name}`).textContent = `${percent}%`;
            document.getElementById(`meter-${name}`).style.width = `${percent}%`;
        }
        
        // 이벤트 핸들러
        function handleSpeak(data) {
            document.getElementById('tutor-text').textContent = data.text;
            addLog('SPEAK', data.text, 'speak');
        }
        
        function handleBackchannel(data) {
            document.getElementById('tutor-text').textContent = data.text;
            addLog('BACKCHANNEL', data.text, 'backchannel');
        }
        
        function handleDecision(data) {
            updateDecisionDisplay({ decision: data });
        }
        
        function handleToken(data) {
            const el = document.querySelector('.streaming-text');
            if (el) {
                el.textContent += data.content;
            }
        }
        
        function handleChunk(data) {
            addLog('CHUNK', `청크 ${data.index}: ${data.text?.substring(0, 30)}...`);
        }
        
        function handleStreamComplete(data) {
            const cursor = document.querySelector('.cursor-blink');
            if (cursor) cursor.remove();
            addLog('COMPLETE', '스트리밍 완료');
        }
        
        function handleError(data) {
            addLog('ERROR', data.error || '오류 발생');
        }
        
        // 결정 표시 업데이트
        function updateDecisionDisplay(data) {
            const decision = data.decision || data;
            const type = decision.type || data.action || 'none';
            const prob = decision.collapse_probability || 0;
            const reason = decision.reason || '-';
            
            document.getElementById('decision-type').textContent = type.toUpperCase();
            document.getElementById('decision-type').className = `decision-type ${type}`;
            document.getElementById('decision-reason').textContent = reason;
            document.getElementById('collapse-prob').textContent = `${Math.round(prob * 100)}%`;
            
            const meter = document.getElementById('collapse-meter');
            meter.style.width = `${prob * 100}%`;
            
            if (prob > 0.7) meter.style.background = 'var(--danger)';
            else if (prob > 0.4) meter.style.background = 'var(--warning)';
            else meter.style.background = 'var(--success)';
        }
        
        // 로그 추가
        function addLog(type, message, logType = 'decision') {
            const container = document.getElementById('log-container');
            const time = new Date().toLocaleTimeString();
            
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.innerHTML = `
                <span class="log-time">${time}</span>
                <span class="log-type ${logType}">${type}</span>
                <span>${message}</span>
            `;
            
            container.insertBefore(entry, container.firstChild);
            
            // 최대 50개 유지
            while (container.children.length > 50) {
                container.removeChild(container.lastChild);
            }
        }
        
        // 로그 초기화
        function clearLog() {
            document.getElementById('log-container').innerHTML = '';
            addLog('CLEAR', '로그 초기화됨');
        }
        
        // Enter 키 처리
        document.getElementById('custom-text').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') speakCustom();
        });
    </script>
</body>
</html>

