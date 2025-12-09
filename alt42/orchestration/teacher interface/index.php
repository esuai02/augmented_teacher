<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 수학 수업 모니터링</title>
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: rgba(255, 255, 255, 0.03);
            --bg-tertiary: rgba(255, 255, 255, 0.05);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --text-tertiary: rgba(255, 255, 255, 0.4);
            --accent-purple: #8b5cf6;
            --accent-pink: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow: rgba(0, 0, 0, 0.5);
        }
        
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #e9ecef;
            --border-color: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #495057;
            --text-tertiary: #6c757d;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            color: var(--text-primary);
            overflow: hidden;
            transition: background 0.3s, color 0.3s;
        }
        
        .layout {
            display: flex;
            height: 100vh;
        }
        
        /* Left Sidebar - Minimal Student List */
        .sidebar {
            width: 300px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-title {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .live-dot {
            width: 6px;
            height: 6px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }
        
        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .theme-toggle:hover {
            transform: scale(1.05);
            background: var(--bg-tertiary);
        }
        
        .students-sidebar {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .students-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        
        .students-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .students-sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }
        
        .student-card {
            padding: 12px;
            margin-bottom: 8px;
            background: var(--bg-primary);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        
        .student-card:hover {
            background: var(--bg-tertiary);
            transform: translateX(4px);
        }
        
        .student-card.active {
            border-color: var(--accent-purple);
            background: var(--bg-tertiary);
        }
        
        .student-card.critical {
            border-left: 3px solid var(--danger);
        }
        
        .student-card.warning {
            border-left: 3px solid var(--warning);
        }
        
        .student-card.normal {
            border-left: 3px solid var(--success);
        }
        
        .student-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }
        
        .student-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
        }
        
        .avatar-critical {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        
        .avatar-warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }
        
        .avatar-normal {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }
        
        .student-name {
            font-size: 14px;
            font-weight: 500;
        }
        
        .student-priority {
            margin-left: auto;
            font-size: 10px;
            color: var(--text-tertiary);
        }
        
        .student-status {
            font-size: 11px;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        /* Main Content - Interaction Focus */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .top-bar {
            padding: 20px 30px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .timer-info {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .timer-value {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .interaction-area {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        
        .interaction-area::-webkit-scrollbar {
            width: 6px;
        }
        
        .interaction-area::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .interaction-area::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }
        
        .interaction-card {
            max-width: 800px;
            width: 100%;
            margin-bottom: 40px;
        }
        
        .student-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .student-main-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 600;
            position: relative;
            flex-shrink: 0;
        }
        
        .student-main-avatar::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            border: 2px solid;
            border-color: inherit;
            opacity: 0.2;
        }
        
        .student-header-info {
            flex: 1;
        }
        
        .student-main-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .student-main-desc {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* Feedback Section - Main Focus */
        .feedback-section {
            margin-bottom: 35px;
        }
        
        .feedback-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-tertiary);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        /* Key Metrics - Compact */
        .key-metrics {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .key-metric {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .key-metric-icon {
            font-size: 20px;
        }
        
        .key-metric-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .key-metric-value {
            font-size: 18px;
            font-weight: 700;
        }
        
        .key-metric-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
        }
        
        /* Recommendation Card - Prominent Main Content */
        .recommendation {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(236, 72, 153, 0.15));
            border: 2px solid var(--accent-purple);
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
        }
        
        [data-theme="light"] .recommendation {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(236, 72, 153, 0.08));
        }
        
        .rec-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent-purple);
            margin-bottom: 20px;
        }
        
        .rec-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 18px;
            color: var(--text-primary);
        }
        
        .rec-description {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        
        /* Action Buttons - Large and Clear */
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .action-btn {
            flex: 1;
            padding: 20px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .action-btn-primary {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
            color: white;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
        }
        
        .action-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
        }
        
        .action-btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .action-btn-secondary:hover {
            background: var(--bg-tertiary);
            transform: translateY(-2px);
        }
        
        /* Bottom Detail Panel - Minimal Bar */
        .detail-panel {
            margin-top: 30px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .detail-handle {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            background: var(--bg-secondary);
            transition: background 0.2s;
        }
        
        .detail-handle:hover {
            background: var(--bg-tertiary);
        }
        
        .handle-bar {
            width: 50px;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            position: absolute;
            top: 15px;
        }
        
        .handle-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .handle-arrow {
            transition: transform 0.3s;
        }
        
        .detail-panel:not(.collapsed) .handle-arrow {
            transform: rotate(180deg);
        }
        
        .detail-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .detail-panel:not(.collapsed) .detail-content {
            max-height: 40vh;
            overflow-y: auto;
        }
        
        .detail-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .detail-content::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .detail-content::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }
        
        .detail-content-inner {
            padding: 30px;
        }
        
        .detail-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .detail-content::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .detail-content::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }
        
        .detail-section {
            margin-bottom: 30px;
        }
        
        .detail-section-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
            margin-bottom: 15px;
        }
        
        .metrics-detail {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
        }
        
        .metric-detail-box {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .metric-detail-icon {
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .metric-detail-value {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .metric-detail-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
        }
        
        .issues-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .issue-badge {
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .issue-badge.critical {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .issue-badge.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        [data-theme="light"] .issue-badge.critical {
            background: rgba(239, 68, 68, 0.08);
        }
        
        [data-theme="light"] .issue-badge.warning {
            background: rgba(245, 158, 11, 0.08);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-text {
            font-size: 16px;
            color: var(--text-tertiary);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            
            .metrics-detail {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -300px;
                z-index: 200;
                box-shadow: 5px 0 20px var(--shadow);
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .key-metrics {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <div class="live-dot"></div>
                    학생 모니터링
                </div>
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="themeIcon">🌙</span>
                </button>
            </div>
            
            <div class="students-sidebar" id="studentsSidebar">
                <!-- Students will be populated by JavaScript -->
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="timer-info">
                    <span>다음 스캔: <span class="timer-value" id="countdown">9:45</span></span>
                </div>
                <div class="timer-info">
                    <span>수업 경과: <span class="timer-value">32:15</span></span>
                </div>
            </div>
            
            <div class="interaction-area" id="interactionArea">
                <div class="empty-state">
                    <div class="empty-icon">👈</div>
                    <div class="empty-text">학생을 선택하여 상호작용을 시작하세요</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Students data
        const students = [
            {
                id: 1,
                name: '이서연',
                priority: 95,
                status: 'critical',
                description: '문제 3번에서 15분째 진행 없음. 화면 전환이 잦고 필기가 멈춰있습니다.',
                metrics: {
                    ai: 95,
                    writing: 78,
                    calm: 35,
                    pomodoro: 45,
                    learning: 38
                },
                issues: [
                    { type: 'critical', text: '📝 5분 이상 필기 없음' },
                    { type: 'critical', text: '🔄 화면 전환 잦음' },
                    { type: 'warning', text: '⏱️ 포모도로 미완료' }
                ],
                recommendation: {
                    type: '1:1 개별 지도',
                    effectiveness: 92,
                    description: '화면 공유를 통해 현재 막힌 부분을 파악하고, 단계별 힌트를 제공하여 스스로 문제를 해결할 수 있도록 유도합니다. 작은 성공 경험을 통해 자신감을 회복시킵니다.'
                }
            },
            {
                id: 2,
                name: '강민서',
                priority: 88,
                status: 'critical',
                description: '이차방정식 개념 이해 부족',
                metrics: {
                    ai: 85,
                    writing: 65,
                    calm: 42,
                    pomodoro: 60,
                    learning: 30
                },
                issues: [
                    { type: 'critical', text: '📚 학습 진도 30% 지연' },
                    { type: 'warning', text: '😰 스트레스 지수 상승' },
                    { type: 'warning', text: '🔁 반복 오답' }
                ],
                recommendation: {
                    type: '시각적 개념 설명',
                    effectiveness: 88,
                    description: '그래프와 애니메이션을 활용한 시각적 설명으로 이차방정식 개념을 재설명하고, 실생활 예제를 통해 이해도를 높입니다.'
                }
            },
            {
                id: 3,
                name: '박지호',
                priority: 72,
                status: 'warning',
                description: '집중력 저하 시작',
                metrics: {
                    ai: 70,
                    writing: 82,
                    calm: 65,
                    pomodoro: 75,
                    learning: 68
                },
                issues: [
                    { type: 'warning', text: '⏱️ 포모도로 1회 스킵' },
                    { type: 'warning', text: '📉 속도 감소' }
                ],
                recommendation: {
                    type: '짧은 휴식 권장',
                    effectiveness: 85,
                    description: '2분간의 스트레칭 휴식 후 난이도를 조절한 문제로 재시작하여 학습 흐름을 회복합니다.'
                }
            },
            {
                id: 4,
                name: '최수빈',
                priority: 65,
                status: 'warning',
                description: '필기 속도 감소',
                metrics: {
                    ai: 62,
                    writing: 45,
                    calm: 70,
                    pomodoro: 80,
                    learning: 72
                },
                issues: [
                    { type: 'warning', text: '✍️ 필기 빈도 감소' }
                ],
                recommendation: {
                    type: '능동적 필기 유도',
                    effectiveness: 82,
                    description: '핵심 개념을 정리하는 마인드맵 작성을 유도하여 능동적인 학습 참여를 촉진합니다.'
                }
            },
            {
                id: 5,
                name: '정예은',
                priority: 15,
                status: 'normal',
                description: '우수한 진행 상태',
                metrics: {
                    ai: 95,
                    writing: 92,
                    calm: 88,
                    pomodoro: 90,
                    learning: 94
                },
                issues: [],
                recommendation: {
                    type: '심화 문제 제공',
                    effectiveness: 95,
                    description: '현재 수준보다 높은 도전 과제를 제공하여 학습 동기를 유지하고 실력을 향상시킵니다.'
                }
            },
            {
                id: 6,
                name: '윤서준',
                priority: 20,
                status: 'normal',
                description: '정상 학습 중',
                metrics: {
                    ai: 85,
                    writing: 88,
                    calm: 82,
                    pomodoro: 85,
                    learning: 86
                },
                issues: [],
                recommendation: null
            },
            {
                id: 7,
                name: '오시우',
                priority: 78,
                status: 'critical',
                description: '계산 실수 반복',
                metrics: {
                    ai: 75,
                    writing: 60,
                    calm: 38,
                    pomodoro: 55,
                    learning: 42
                },
                issues: [
                    { type: 'critical', text: '🔢 계산 실수 3회 연속' },
                    { type: 'warning', text: '😓 자신감 저하' }
                ],
                recommendation: {
                    type: '단계별 문제 분해',
                    effectiveness: 86,
                    description: '복잡한 문제를 작은 단계로 나누고 각 단계마다 즉각적인 긍정 피드백을 제공합니다.'
                }
            },
            {
                id: 8,
                name: '임하은',
                priority: 58,
                status: 'warning',
                description: '포모도로 지연',
                metrics: {
                    ai: 68,
                    writing: 75,
                    calm: 72,
                    pomodoro: 40,
                    learning: 70
                },
                issues: [
                    { type: 'warning', text: '⏱️ 휴식 시간 초과' }
                ],
                recommendation: {
                    type: '시간 관리 코칭',
                    effectiveness: 80,
                    description: '포모도로 타이머를 재설정하고 작은 목표 달성시 보상을 제공합니다.'
                }
            }
        ];
        
        let selectedStudent = null;
        let detailPanelOpen = false;
        
        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            const icon = document.getElementById('themeIcon');
            
            html.setAttribute('data-theme', newTheme);
            icon.textContent = newTheme === 'light' ? '☀️' : '🌙';
            localStorage.setItem('theme', newTheme);
        }
        
        // Initialize theme
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const icon = document.getElementById('themeIcon');
            document.documentElement.setAttribute('data-theme', savedTheme);
            icon.textContent = savedTheme === 'light' ? '☀️' : '🌙';
        }
        
        // Toggle detail panel
        function toggleDetailPanel() {
            const panel = document.getElementById('detailPanel');
            detailPanelOpen = !detailPanelOpen;
            
            if (detailPanelOpen) {
                panel.classList.remove('collapsed');
                
                // Scroll to detail panel after animation starts
                setTimeout(() => {
                    panel.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'nearest'
                    });
                }, 100);
            } else {
                panel.classList.add('collapsed');
            }
        }
        
        // Render students sidebar
        function renderStudentsSidebar() {
            const sidebar = document.getElementById('studentsSidebar');
            
            // Sort by priority
            const sortedStudents = [...students].sort((a, b) => b.priority - a.priority);
            
            sidebar.innerHTML = sortedStudents.map((student, index) => `
                <div class="student-card ${student.status} ${selectedStudent?.id === student.id ? 'active' : ''}"
                     onclick="selectStudent(${student.id})">
                    <div class="student-card-header">
                        <div class="student-avatar avatar-${student.status}">
                            ${student.name.substring(0, 1)}
                        </div>
                        <div class="student-name">${student.name}</div>
                        <div class="student-priority">#${index + 1}</div>
                    </div>
                    <div class="student-status">${student.description}</div>
                </div>
            `).join('');
        }
        
        // Select student
        function selectStudent(studentId) {
            selectedStudent = students.find(s => s.id === studentId);
            renderStudentsSidebar();
            renderInteractionArea();
            
            // Reset detail panel to collapsed when selecting new student
            detailPanelOpen = false;
        }
        
        // Render interaction area
        function renderInteractionArea() {
            const area = document.getElementById('interactionArea');
            
            if (!selectedStudent) {
                area.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">👈</div>
                        <div class="empty-text">학생을 선택하여 상호작용을 시작하세요</div>
                    </div>
                `;
                return;
            }
            
            const statusColor = selectedStudent.status === 'critical' ? '#ef4444' :
                              selectedStudent.status === 'warning' ? '#f59e0b' : '#10b981';
            
            // Get top 4 metrics for brief display
            const metricsArray = Object.entries(selectedStudent.metrics)
                .sort((a, b) => a[1] - b[1])
                .slice(0, 4);
            
            const metricIcons = {
                ai: '🤖',
                writing: '✍️',
                calm: '😌',
                pomodoro: '⏱️',
                learning: '📚'
            };
            
            const metricLabels = {
                ai: 'AI 추천도',
                writing: '필기 활성도',
                calm: '침착도',
                pomodoro: '시간 관리',
                learning: '학습 진도'
            };
            
            area.innerHTML = `
                <div class="interaction-card">
                    <!-- Student Header - Left Aligned -->
                    <div class="student-header">
                        <div class="student-main-avatar avatar-${selectedStudent.status}" style="background: rgba(${statusColor === '#ef4444' ? '239, 68, 68' : statusColor === '#f59e0b' ? '245, 158, 11' : '16, 185, 129'}, 0.15); color: ${statusColor}; border-color: ${statusColor};">
                            ${selectedStudent.name.substring(0, 1)}
                        </div>
                        <div class="student-header-info">
                            <div class="student-main-name">${selectedStudent.name}</div>
                            <div class="student-main-desc">📍 ${selectedStudent.description}</div>
                        </div>
                    </div>
                    
                    <!-- Main Feedback Section -->
                    <div class="feedback-section">
                        <div class="feedback-label">🤖 AI 추천 상호작용</div>
                        
                        ${selectedStudent.recommendation ? `
                            <div class="recommendation">
                                <div class="rec-badge">
                                    예상 효과 ${selectedStudent.recommendation.effectiveness}%
                                </div>
                                <div class="rec-title">${selectedStudent.recommendation.type}</div>
                                <div class="rec-description">${selectedStudent.recommendation.description}</div>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="action-btn action-btn-secondary" onclick="modifyAction()">
                                    ✏️ 수정하기
                                </button>
                                <button class="action-btn action-btn-primary" onclick="applyAction()">
                                    ⚡ 바로 적용
                                </button>
                            </div>
                        ` : `
                            <div class="recommendation" style="text-align: center; padding: 40px;">
                                <div class="rec-badge" style="margin: 0 auto 15px;">
                                    ✅ 순조로운 학습
                                </div>
                                <div class="rec-description">
                                    현재 특별한 조치가 필요하지 않습니다. 계속해서 모니터링하겠습니다.
                                </div>
                            </div>
                        `}
                    </div>
                    
                    <!-- Brief Metrics -->
                    <div class="feedback-label" style="margin-bottom: 12px;">📊 주요 지표</div>
                    <div class="key-metrics">
                        ${metricsArray.map(([key, value]) => {
                            const color = value < 40 ? '#ef4444' : value < 70 ? '#f59e0b' : '#10b981';
                            return `
                                <div class="key-metric">
                                    <div class="key-metric-icon">${metricIcons[key]}</div>
                                    <div class="key-metric-content">
                                        <div class="key-metric-value" style="color: ${color}">${value}%</div>
                                        <div class="key-metric-label">${metricLabels[key]}</div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                    
                    <!-- Detail Panel -->
                    <div class="detail-panel collapsed" id="detailPanel">
                        <div class="detail-handle" onclick="toggleDetailPanel()">
                            <div class="handle-bar"></div>
                            <div class="handle-text">
                                <span>상세 정보 보기</span>
                                <span class="handle-arrow">▼</span>
                            </div>
                        </div>
                        <div class="detail-content">
                            <div class="detail-content-inner" id="detailContent">
                                ${renderDetailContent()}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Render detail content (returns HTML string)
        function renderDetailContent() {
            if (!selectedStudent) {
                return '';
            }
            
            const metricIcons = {
                ai: '🤖',
                writing: '✍️',
                calm: '😌',
                pomodoro: '⏱️',
                learning: '📚'
            };
            
            const metricLabels = {
                ai: 'AI 추천도',
                writing: '필기 활성도',
                calm: '침착도',
                pomodoro: '시간 관리',
                learning: '학습 진도'
            };
            
            return `
                <div class="detail-section">
                    <div class="detail-section-title">전체 지표</div>
                    <div class="metrics-detail">
                        ${Object.entries(selectedStudent.metrics).map(([key, value]) => {
                            const color = value < 40 ? '#ef4444' : value < 70 ? '#f59e0b' : '#10b981';
                            return `
                                <div class="metric-detail-box">
                                    <div class="metric-detail-icon">${metricIcons[key]}</div>
                                    <div class="metric-detail-value" style="color: ${color}">${value}%</div>
                                    <div class="metric-detail-label">${metricLabels[key]}</div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                
                ${selectedStudent.issues.length > 0 ? `
                    <div class="detail-section">
                        <div class="detail-section-title">감지된 문제점</div>
                        <div class="issues-list">
                            ${selectedStudent.issues.map(issue => `
                                <div class="issue-badge ${issue.type}">
                                    ${issue.text}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            `;
        }
        
        // Apply action
        function applyAction() {
            if (!selectedStudent || !selectedStudent.recommendation) return;
            
            alert(`✅ "${selectedStudent.name}" 학생에게 "${selectedStudent.recommendation.type}" 상호작용을 시작했습니다.`);
            
            // Move to next priority student
            const currentIndex = students.findIndex(s => s.id === selectedStudent.id);
            const sortedStudents = [...students].sort((a, b) => b.priority - a.priority);
            const currentSortedIndex = sortedStudents.findIndex(s => s.id === selectedStudent.id);
            
            if (currentSortedIndex < sortedStudents.length - 1) {
                selectStudent(sortedStudents[currentSortedIndex + 1].id);
            }
        }
        
        // Modify action
        function modifyAction() {
            if (!selectedStudent) return;
            alert(`"${selectedStudent.name}" 학생의 상호작용 방법을 수정할 수 있는 상세 설정으로 이동합니다.`);
        }
        
        // Update countdown
        function updateCountdown() {
            const now = new Date();
            const nextScan = new Date(now.getTime() + (10 - now.getMinutes() % 10) * 60000);
            const diff = Math.floor((nextScan - now) / 1000);
            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            
            document.getElementById('countdown').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Initialize
        initTheme();
        renderStudentsSidebar();
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Auto-select first critical student
        const criticalStudent = students.find(s => s.priority >= 70);
        if (criticalStudent) {
            selectStudent(criticalStudent.id);
        }
    </script>
</body>
</html>