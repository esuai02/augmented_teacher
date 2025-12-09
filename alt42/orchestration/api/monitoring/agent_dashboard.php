<?php
/**
 * Agent Status Dashboard
 * 에이전트 현황 모니터링 대시보드
 * 
 * @package ALT42\Monitoring
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

require_login();

$PAGE->set_url('/local/augmented_teacher/alt42/orchestration/api/monitoring/agent_dashboard.php');
$PAGE->set_title('에이전트 현황 모니터링');
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>에이전트 현황 모니터링</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Malgun Gothic', '맑은 고딕', sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .dashboard-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .dashboard-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .control-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .btn-primary:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .btn-active {
            background: white;
            color: #667eea;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.active {
            background: #10b981;
        }
        
        .status-indicator.inactive {
            background: #6b7280;
            animation: none;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .agents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .agent-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .agent-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .agent-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .agent-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .agent-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .agent-category {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .category-analysis {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .category-support {
            background: #fef3c7;
            color: #92400e;
        }
        
        .category-execution {
            background: #d1fae5;
            color: #065f46;
        }
        
        .agent-status {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-normal {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* 이벤트 타입별 색상 */
        .event-heartbeat {
            border-left: 4px solid #3b82f6;
        }
        
        .event-state-change {
            border-left: 4px solid #10b981;
        }
        
        .event-scenario {
            border-left: 4px solid #f59e0b;
        }
        
        .event-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .event-type-heartbeat {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .event-type-state-change {
            background: #d1fae5;
            color: #065f46;
        }
        
        .event-type-scenario {
            background: #fef3c7;
            color: #92400e;
        }
        
        .agent-metrics {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .metric {
            text-align: center;
            padding: 10px;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .metric-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .metric-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            margin-top: 20px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .close-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .modal-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .log-table th,
        .log-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .log-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>🤖 에이전트 현황 모니터링</h1>
        <p>22개 에이전트의 실시간 상태 및 성능 모니터링</p>
        
        <div class="dashboard-controls">
            <div class="control-group">
                <span class="status-indicator" id="connectionStatus"></span>
                <span id="connectionText">연결 중...</span>
            </div>
            
            <div class="control-group">
                <button class="btn btn-primary" id="realtimeBtn" onclick="setUpdateMode('realtime')">
                    실시간 모드
                </button>
                <button class="btn btn-primary" id="pollingBtn" onclick="setUpdateMode('polling')">
                    5분 모드
                </button>
            </div>
            
            <div class="control-group">
                <button class="btn btn-primary" onclick="refreshData()">새로고침</button>
            </div>
        </div>
    </div>
    
    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterCategory('all')">전체</button>
        <button class="filter-btn" onclick="filterCategory('analysis')">분석 에이전트</button>
        <button class="filter-btn" onclick="filterCategory('support')">지원 에이전트</button>
        <button class="filter-btn" onclick="filterCategory('execution')">실행 에이전트</button>
        <input type="text" class="search-box" id="searchBox" placeholder="에이전트 검색..." onkeyup="filterAgents()">
    </div>
    
    <div class="stats-grid" id="statsGrid"></div>
    
    <div class="agents-grid" id="agentsGrid">
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            데이터 로딩 중...
        </div>
    </div>
    
    <!-- 모달 -->
    <div class="modal" id="agentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">에이전트 상세 정보</h2>
                <button class="close-btn" onclick="closeModal()">닫기</button>
            </div>
            
            <div class="modal-tabs">
                <button class="tab-btn active" onclick="switchTab('logs')">실행 로그</button>
                <button class="tab-btn" onclick="switchTab('stats')">성능 통계</button>
                <button class="tab-btn" onclick="switchTab('events')">관련 이벤트</button>
                <button class="tab-btn" onclick="switchTab('errors')">에러 내역</button>
            </div>
            
            <div id="tabLogs" class="tab-content active"></div>
            <div id="tabStats" class="tab-content"></div>
            <div id="tabEvents" class="tab-content"></div>
            <div id="tabErrors" class="tab-content"></div>
        </div>
    </div>
    
    <script>
        let updateMode = 'realtime';
        let eventSource = null;
        let pollingInterval = null;
        let currentAgentId = null;
        
        // 초기화
        document.addEventListener('DOMContentLoaded', function() {
            loadAgentStatus();
            setUpdateMode('realtime');
        });
        
        // 업데이트 모드 설정
        function setUpdateMode(mode) {
            updateMode = mode;
            
            // 기존 연결 종료
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            
            // 새 모드 설정
            if (mode === 'realtime') {
                document.getElementById('realtimeBtn').classList.add('btn-active');
                document.getElementById('pollingBtn').classList.remove('btn-active');
                startRealtimeUpdates();
            } else {
                document.getElementById('realtimeBtn').classList.remove('btn-active');
                document.getElementById('pollingBtn').classList.add('btn-active');
                startPollingUpdates();
            }
        }
        
        // 실시간 업데이트 시작
        function startRealtimeUpdates() {
            updateConnectionStatus(true);
            eventSource = new EventSource('agent_status_sse.php?interval=5&last_id=0');
            
            eventSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                
                if (data.type === 'update') {
                    updateAgentCards(data.agents);
                } else if (data.type === 'heartbeat') {
                    updateConnectionStatus(true);
                } else if (data.type === 'error') {
                    console.error('SSE Error:', data.message);
                    updateConnectionStatus(false);
                }
            };
            
            eventSource.onerror = function() {
                updateConnectionStatus(false);
                // 재연결 시도
                setTimeout(() => {
                    if (updateMode === 'realtime') {
                        startRealtimeUpdates();
                    }
                }, 5000);
            };
        }
        
        // 폴링 업데이트 시작
        function startPollingUpdates() {
            updateConnectionStatus(true);
            loadAgentStatus();
            pollingInterval = setInterval(loadAgentStatus, 300000); // 5분
        }
        
        // 연결 상태 업데이트
        function updateConnectionStatus(connected) {
            const indicator = document.getElementById('connectionStatus');
            const text = document.getElementById('connectionText');
            
            if (connected) {
                indicator.classList.add('active');
                indicator.classList.remove('inactive');
                text.textContent = updateMode === 'realtime' ? '실시간 연결됨' : '5분 모드';
            } else {
                indicator.classList.remove('active');
                indicator.classList.add('inactive');
                text.textContent = '연결 끊김';
            }
        }
        
        // API 기본 경로
        const apiBase = 'agent_status_api.php';
        
        // 에이전트 현황 로드
        function loadAgentStatus() {
            fetch(apiBase + '?action=status')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAgents(data.data);
                        loadStats();
                    }
                })
                .catch(error => {
                    console.error('Error loading agent status:', error);
                    updateConnectionStatus(false);
                });
        }
        
        // 통계 로드
        function loadStats() {
            fetch(apiBase + '?action=stats&period=24h')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderStats(data.data);
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }
        
        // 통계 렌더링
        function renderStats(stats) {
            const grid = document.getElementById('statsGrid');
            grid.innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${stats.total.events.toLocaleString()}</div>
                    <div class="stat-label">총 이벤트 (24h)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total.completed.toLocaleString()}</div>
                    <div class="stat-label">성공 이벤트</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total.failed}</div>
                    <div class="stat-label">실패 이벤트</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total.avg_duration_ms.toFixed(2)}ms</div>
                    <div class="stat-label">평균 실행 시간</div>
                </div>
            `;
        }
        
        // 에이전트 카드 렌더링
        function renderAgents(agents) {
            const grid = document.getElementById('agentsGrid');
            
            if (agents.length === 0) {
                grid.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">에이전트 데이터가 없습니다.</div>';
                return;
            }
            
            grid.innerHTML = agents.map(agent => `
                <div class="agent-card" onclick="showAgentDetail('${agent.id}')">
                    <div class="agent-card-header">
                        <div>
                            <div class="agent-number">Agent ${String(agent.number).padStart(2, '0')}</div>
                            <div class="agent-name">${agent.name}</div>
                        </div>
                        <span class="agent-category category-${agent.category}">
                            ${agent.category === 'analysis' ? '분석' : agent.category === 'support' ? '지원' : '실행'}
                        </span>
                    </div>
                    
                    <div class="agent-status">
                        <span class="status-badge status-${agent.status}">
                            ${agent.status === 'normal' ? '정상' : agent.status === 'warning' ? '경고' : '오류'}
                        </span>
                    </div>
                    
                    <div class="agent-metrics">
                        <div class="metric">
                            <div class="metric-label">총 실행</div>
                            <div class="metric-value">${agent.total_executions}</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">성공률</div>
                            <div class="metric-value">${agent.success_rate}%</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">오류</div>
                            <div class="metric-value">${agent.error_count}</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">평균 시간</div>
                            <div class="metric-value">${agent.avg_duration_ms}ms</div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // 카테고리 필터
        function filterCategory(category) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const cards = document.querySelectorAll('.agent-card');
            cards.forEach(card => {
                if (category === 'all') {
                    card.style.display = 'block';
                } else {
                    const agentCategory = card.querySelector('.agent-category').textContent.trim();
                    const categoryMap = {
                        'analysis': '분석',
                        'support': '지원',
                        'execution': '실행'
                    };
                    if (agentCategory === categoryMap[category]) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
        
        // 에이전트 검색
        function filterAgents() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            const cards = document.querySelectorAll('.agent-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // 에이전트 상세 정보 표시
        function showAgentDetail(agentId) {
            currentAgentId = agentId;
            document.getElementById('agentModal').classList.add('active');
            
            fetch(apiBase + `?action=detail&agent_id=${agentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAgentDetail(data.data);
                    }
                })
                .catch(error => console.error('Error loading agent detail:', error));
        }
        
        // 에이전트 상세 정보 렌더링
        function renderAgentDetail(data) {
            const agent = data.agent;
            document.getElementById('modalTitle').textContent = `Agent ${String(agent.number).padStart(2, '0')}: ${agent.name}`;
            
            // 실행 로그
            renderLogs(data.execution_logs);
            
            // 성능 통계
            renderPerformanceStats(data.daily_stats);
            
            // 관련 이벤트
            renderEvents(data.related_events);
            
            // 에러 내역
            renderErrors(data.errors);
        }
        
        // 이벤트 타입 분류
        function getEventType(eventType) {
            if (eventType.includes('heartbeat') || eventType.includes('cron.heartbeat')) {
                return { type: 'heartbeat', label: 'Heartbeat', class: 'event-type-heartbeat' };
            } else if (eventType.includes('state_change') || eventType.includes('state.change')) {
                return { type: 'state-change', label: 'State Change', class: 'event-type-state-change' };
            } else if (eventType.includes('scenario') || eventType.includes('evaluation')) {
                return { type: 'scenario', label: 'Scenario', class: 'event-type-scenario' };
            }
            return { type: 'other', label: 'Other', class: 'event-type-heartbeat' };
        }
        
        // 실행 로그 렌더링
        function renderLogs(logs) {
            const content = document.getElementById('tabLogs');
            if (logs.length === 0) {
                content.innerHTML = '<p>실행 로그가 없습니다.</p>';
                return;
            }
            
            content.innerHTML = `
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>이벤트 타입</th>
                            <th>학생 ID</th>
                            <th>상태</th>
                            <th>실행 시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${logs.map(log => {
                            const eventInfo = getEventType(log.event_type);
                            return `
                                <tr class="event-${eventInfo.type}">
                                    <td>${new Date(log.created_at).toLocaleString('ko-KR')}</td>
                                    <td>
                                        <span class="event-type-badge ${eventInfo.class}">${eventInfo.label}</span>
                                        ${log.event_type}
                                    </td>
                                    <td>${log.student_id || '-'}</td>
                                    <td><span class="status-badge status-${log.status}">${log.status}</span></td>
                                    <td>${log.duration_ms ? parseFloat(log.duration_ms).toFixed(2) + 'ms' : '-'}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            `;
        }
        
        // 성능 통계 렌더링
        function renderPerformanceStats(stats) {
            const content = document.getElementById('tabStats');
            if (stats.length === 0) {
                content.innerHTML = '<p>통계 데이터가 없습니다.</p>';
                return;
            }
            
            content.innerHTML = `
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>날짜</th>
                            <th>실행 수</th>
                            <th>성공</th>
                            <th>실패</th>
                            <th>평균 시간</th>
                            <th>최대 시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stats.map(stat => `
                            <tr>
                                <td>${stat.date}</td>
                                <td>${stat.executions}</td>
                                <td>${stat.successes}</td>
                                <td>${stat.errors}</td>
                                <td>${stat.avg_duration_ms ? stat.avg_duration_ms.toFixed(2) + 'ms' : '-'}</td>
                                <td>${stat.max_duration_ms ? stat.max_duration_ms.toFixed(2) + 'ms' : '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
        
        // 관련 이벤트 렌더링
        function renderEvents(events) {
            const content = document.getElementById('tabEvents');
            if (events.length === 0) {
                content.innerHTML = '<p>관련 이벤트가 없습니다.</p>';
                return;
            }
            
            content.innerHTML = `
                <div class="timeline">
                    ${events.map(event => {
                        const eventInfo = getEventType(event.event_type);
                        return `
                            <div class="timeline-item event-${eventInfo.type}">
                                <span class="event-type-badge ${eventInfo.class}">${eventInfo.label}</span>
                                <strong>${event.event_type}</strong>
                                <div style="color: #6b7280; font-size: 12px; margin-top: 5px;">
                                    ${new Date(event.created_at).toLocaleString('ko-KR')} | 
                                    학생: ${event.student_id || '-'} | 
                                    상태: <span class="status-badge status-${event.status}">${event.status}</span>
                                    ${event.scenarios_evaluated ? `| 시나리오: ${event.scenarios_evaluated}개` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }
        
        // 에러 내역 렌더링
        function renderErrors(errors) {
            const content = document.getElementById('tabErrors');
            if (errors.length === 0) {
                content.innerHTML = '<p>에러 내역이 없습니다.</p>';
                return;
            }
            
            content.innerHTML = `
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>이벤트 타입</th>
                            <th>학생 ID</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${errors.map(error => `
                            <tr>
                                <td>${new Date(error.created_at).toLocaleString('ko-KR')}</td>
                                <td>${error.event_type}</td>
                                <td>${error.student_id || '-'}</td>
                                <td><span class="status-badge status-error">실패</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
        
        // 탭 전환
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.add('active');
        }
        
        // 모달 닫기
        function closeModal() {
            document.getElementById('agentModal').classList.remove('active');
        }
        
        // 데이터 새로고침
        function refreshData() {
            loadAgentStatus();
        }
        
        // 페이지 언로드 시 연결 종료
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        });
    </script>
</body>
</html>

<?php
echo $OUTPUT->footer();
?>

