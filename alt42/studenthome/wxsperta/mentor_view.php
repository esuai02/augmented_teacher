<?php
/**
 * 🎯 21개 에이전트 멘토링 시스템 - 메인 뷰
 * 퀵테스트 → 에이전트 매칭 → AI 대화 → 자동 순차 연결
 *
 * 파일: mentor_view.php
 * 위치: /alt42/studenthome/wxsperta/
 * URL: wxsperta.php?view=mentor 또는 직접 접근
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$userid = isset($_GET["userid"]) ? intval($_GET["userid"]) : $USER->id;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎯 AI 멘토링 - 21 에이전트</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep: #0f0f23;
            --bg-card: #1a1a2e;
            --bg-input: #252541;
            --accent-purple: #7c4dff;
            --accent-blue: #448aff;
            --accent-pink: #ff4081;
            --accent-green: #00e676;
            --accent-orange: #ff9100;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
            --text-muted: #6b6b80;
            --border-color: rgba(255,255,255,0.1);
            --glow-purple: 0 0 20px rgba(124,77,255,0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: var(--bg-deep);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 배경 효과 */
        .bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--accent-purple);
            border-radius: 50%;
            opacity: 0.3;
            animation: float 20s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.3; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        /* 메인 컨테이너 */
        .mentor-container {
            position: relative;
            z-index: 1;
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 헤더 */
        .mentor-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .mentor-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .mentor-header p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* 뒤로가기 버튼 */
        .back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .back-btn:hover {
            background: var(--bg-input);
            color: var(--text-primary);
        }

        /* 콘텐츠 영역 */
        .mentor-content {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
        }

        /* 퀵테스트 섹션 */
        .quicktest-section {
            display: none;
        }

        .quicktest-section.active {
            display: block;
        }

        .quicktest-intro {
            text-align: center;
            padding: 2rem 1rem;
            margin-bottom: 1.5rem;
        }

        .quicktest-intro .emoji {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .quicktest-intro h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .quicktest-intro p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* 질문 카드 */
        .question-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .question-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .question-card.answered {
            opacity: 0.6;
            transform: scale(0.98);
        }

        .question-number {
            font-size: 0.75rem;
            color: var(--accent-purple);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        /* 옵션 버튼 */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .options-grid.single-col {
            grid-template-columns: 1fr;
        }

        .option-btn {
            background: var(--bg-input);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 1rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .option-btn:hover {
            background: rgba(124,77,255,0.1);
            border-color: var(--accent-purple);
        }

        .option-btn.selected {
            background: rgba(124,77,255,0.2);
            border-color: var(--accent-purple);
            box-shadow: var(--glow-purple);
        }

        .option-btn .emoji {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        /* 채팅 섹션 */
        .chat-section {
            display: none;
            flex-direction: column;
            height: calc(100vh - 100px);
        }

        .chat-section.active {
            display: flex;
        }

        /* 에이전트 정보 */
        .agent-info {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-input));
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .agent-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(124,77,255,0.2);
            border-radius: 50%;
        }

        .agent-details h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .agent-details p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .agent-objective {
            font-size: 0.75rem;
            color: var(--accent-purple);
            margin-top: 0.25rem;
        }

        /* 채팅 메시지 영역 */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 85%;
            padding: 1rem;
            border-radius: 16px;
            animation: messageIn 0.3s ease;
        }

        @keyframes messageIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.assistant {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .message.user {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .message-content {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* 타이핑 인디케이터 */
        .typing-indicator {
            display: none;
            align-self: flex-start;
            padding: 1rem;
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }

        .typing-indicator.active {
            display: flex;
            gap: 0.3rem;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--text-secondary);
            border-radius: 50%;
            animation: typingBounce 1.4s infinite;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typingBounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }

        /* 입력 영역 */
        .chat-input-area {
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
        }

        /* 선택지 버튼들 */
        .suggestions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .suggestion-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 0.6rem 1rem;
            color: var(--text-primary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .suggestion-btn:hover {
            background: rgba(124,77,255,0.15);
            border-color: var(--accent-purple);
        }

        /* 직접 입력 */
        .input-row {
            display: flex;
            gap: 0.5rem;
        }

        .chat-input {
            flex: 1;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 0.75rem 1.25rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
        }

        .chat-input:focus {
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(124,77,255,0.1);
        }

        .chat-input::placeholder {
            color: var(--text-muted);
        }

        .send-btn {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: var(--glow-purple);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* 에이전트 전환 카드 */
        .transition-card {
            background: linear-gradient(135deg, rgba(124,77,255,0.2), rgba(68,138,255,0.2));
            border: 1px solid var(--accent-purple);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            margin: 1rem 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(124,77,255,0.4); }
            50% { box-shadow: 0 0 0 10px rgba(124,77,255,0); }
        }

        .transition-card h4 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .transition-card p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .transition-btns {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .transition-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 24px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .transition-btn.primary {
            background: var(--accent-purple);
            color: white;
        }

        .transition-btn.secondary {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .transition-btn:hover {
            transform: translateY(-2px);
        }

        /* 진행 상태 */
        .progress-bar {
            height: 4px;
            background: var(--bg-input);
            border-radius: 2px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-blue));
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        /* 방문 에이전트 표시 */
        .visited-agents {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
            overflow-x: auto;
            margin-bottom: 1rem;
        }

        .visited-agent {
            width: 36px;
            height: 36px;
            background: var(--bg-card);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            border: 2px solid var(--border-color);
            flex-shrink: 0;
        }

        .visited-agent.current {
            border-color: var(--accent-purple);
            box-shadow: var(--glow-purple);
        }

        /* 로딩 상태 */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15,15,35,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid var(--bg-input);
            border-top-color: var(--accent-purple);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 반응형 */
        @media (max-width: 480px) {
            .mentor-container {
                max-width: 100%;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }

            .message {
                max-width: 90%;
            }
        }

        /* 스크롤바 커스텀 */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-deep);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--bg-input);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-purple);
        }
    </style>
</head>
<body>
    <!-- 배경 파티클 -->
    <div class="bg-particles" id="particles"></div>

    <!-- 로딩 오버레이 -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- 메인 컨테이너 -->
    <div class="mentor-container">
        <!-- 뒤로가기 -->
        <a href="wxsperta.php" class="back-btn">← 돌아가기</a>

        <!-- 헤더 -->
        <header class="mentor-header">
            <h1>🎯 AI 멘토링</h1>
            <p>21개의 멘토가 너를 기다리고 있어!</p>
        </header>

        <!-- 콘텐츠 영역 -->
        <div class="mentor-content">
            <!-- 퀵테스트 섹션 -->
            <div class="quicktest-section active" id="quicktestSection">
                <div class="quicktest-intro" id="quicktestIntro">
                    <div class="emoji">👋</div>
                    <h2>안녕! 오늘 너에게 딱 맞는 멘토를 찾아줄게</h2>
                    <p>4가지만 빠르게 물어볼게!</p>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>

                <div id="questionsContainer">
                    <!-- 질문들이 동적으로 추가됨 -->
                </div>
            </div>

            <!-- 채팅 섹션 -->
            <div class="chat-section" id="chatSection">
                <!-- 방문한 에이전트들 -->
                <div class="visited-agents" id="visitedAgents">
                    <!-- 방문한 에이전트 아이콘들 -->
                </div>

                <!-- 현재 에이전트 정보 -->
                <div class="agent-info" id="agentInfo">
                    <div class="agent-icon" id="agentIcon">📡</div>
                    <div class="agent-details">
                        <h3 id="agentName">미래 통신</h3>
                        <p id="agentDescription">5년 후의 나와 대화해보자</p>
                        <div class="agent-objective" id="agentObjective">미션: 미래 자아 시각화</div>
                    </div>
                </div>

                <!-- 채팅 메시지 -->
                <div class="chat-messages" id="chatMessages">
                    <!-- 메시지들이 동적으로 추가됨 -->
                </div>

                <!-- 타이핑 인디케이터 -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>

                <!-- 입력 영역 -->
                <div class="chat-input-area">
                    <!-- 선택지 버튼 -->
                    <div class="suggestions-container" id="suggestionsContainer">
                        <!-- 선택지들이 동적으로 추가됨 -->
                    </div>

                    <!-- 직접 입력 -->
                    <div class="input-row">
                        <input type="text" class="chat-input" id="chatInput" placeholder="직접 입력해도 돼!" />
                        <button class="send-btn" id="sendBtn" onclick="sendMessage()">➤</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // 전역 상태
        // ============================================================
        const MentorApp = {
            userId: <?php echo $userid; ?>,
            apiUrl: 'mentor_api.php',
            questions: [],
            answers: {},
            currentQuestionIndex: 0,
            sessionData: null,
            currentAgent: null,
            chatHistory: [],
            isLoading: false
        };

        // ============================================================
        // 초기화
        // ============================================================
        document.addEventListener('DOMContentLoaded', async () => {
            createParticles();
            await checkExistingSession();
        });

        // 배경 파티클 생성
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        // 기존 세션 확인
        async function checkExistingSession() {
            try {
                const response = await fetch(`${MentorApp.apiUrl}?action=get_session_state`);
                const data = await response.json();

                if (data.success && data.has_session) {
                    // 기존 세션이 있으면 바로 채팅으로
                    MentorApp.sessionData = data.session_data;
                    MentorApp.currentAgent = data.session_data.selected_agent;
                    await startChat();
                } else {
                    // 새 세션 시작
                    await loadQuickTest();
                }
            } catch (error) {
                console.error('세션 확인 오류:', error);
                await loadQuickTest();
            }
        }

        // ============================================================
        // 퀵테스트
        // ============================================================
        async function loadQuickTest() {
            showLoading(true);

            try {
                const response = await fetch(`${MentorApp.apiUrl}?action=get_quicktest`);
                const data = await response.json();

                if (data.success) {
                    MentorApp.questions = data.questions;
                    renderQuestions();
                } else {
                    alert('퀵테스트 로드 실패: ' + data.error);
                }
            } catch (error) {
                console.error('퀵테스트 로드 오류:', error);
                alert('서버 연결 오류');
            }

            showLoading(false);
        }

        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';

            MentorApp.questions.forEach((q, index) => {
                const card = document.createElement('div');
                card.className = 'question-card';
                card.id = `question-${index}`;

                const optionsClass = q.options.length > 4 ? 'single-col' : '';

                card.innerHTML = `
                    <div class="question-number">Q${index + 1}</div>
                    <div class="question-text">${q.question}</div>
                    <div class="options-grid ${optionsClass}">
                        ${q.options.map(opt => `
                            <button class="option-btn" data-question="${q.id}" data-value="${opt.value}" onclick="selectOption(this, '${q.id}', '${opt.value}', ${index})">
                                <span class="emoji">${opt.emoji}</span>
                                ${opt.label.replace(opt.emoji, '').trim()}
                            </button>
                        `).join('')}
                    </div>
                `;

                container.appendChild(card);

                // 순차적으로 나타나게
                setTimeout(() => {
                    if (index === 0 || MentorApp.answers[MentorApp.questions[index - 1]?.id]) {
                        card.classList.add('visible');
                    }
                }, index * 100);
            });

            // 첫 번째 질문 표시
            setTimeout(() => {
                document.getElementById('question-0')?.classList.add('visible');
            }, 300);
        }

        function selectOption(btn, questionId, value, index) {
            // 같은 질문의 다른 옵션 선택 해제
            const card = btn.closest('.question-card');
            card.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');

            // 답변 저장
            MentorApp.answers[questionId] = value;

            // 진행률 업데이트
            const progress = ((Object.keys(MentorApp.answers).length) / MentorApp.questions.length) * 100;
            document.getElementById('progressFill').style.width = progress + '%';

            // 현재 카드 완료 표시
            setTimeout(() => {
                card.classList.add('answered');

                // 다음 질문 표시
                const nextCard = document.getElementById(`question-${index + 1}`);
                if (nextCard) {
                    setTimeout(() => {
                        nextCard.classList.add('visible');
                        nextCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 200);
                }

                // 모든 질문 완료 시
                if (Object.keys(MentorApp.answers).length === MentorApp.questions.length) {
                    setTimeout(submitQuickTest, 500);
                }
            }, 150);
        }

        async function submitQuickTest() {
            showLoading(true);

            try {
                const formData = new FormData();
                formData.append('action', 'submit_quicktest');
                formData.append('answers', JSON.stringify(MentorApp.answers));

                const response = await fetch(MentorApp.apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    MentorApp.sessionData = data.session_data;
                    MentorApp.currentAgent = data.selected_agent;

                    // 매칭 결과 표시 후 채팅 시작
                    showMatchResult(data);
                } else {
                    alert('매칭 실패: ' + data.error);
                }
            } catch (error) {
                console.error('퀵테스트 제출 오류:', error);
                alert('서버 연결 오류');
            }

            showLoading(false);
        }

        function showMatchResult(data) {
            const intro = document.getElementById('quicktestIntro');
            const container = document.getElementById('questionsContainer');

            // 기존 내용 숨기기
            container.style.opacity = '0';

            setTimeout(() => {
                container.innerHTML = `
                    <div class="question-card visible" style="text-align: center; padding: 2rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">${data.agent_info.icon}</div>
                        <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">${data.agent_info.name}</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">${data.match_reason}</p>
                        <p style="font-size: 0.85rem; color: var(--accent-purple);">미션: ${data.agent_info.objective}</p>
                        <button class="transition-btn primary" style="margin-top: 1.5rem;" onclick="startChat()">
                            만나러 가기 →
                        </button>
                    </div>
                `;
                container.style.opacity = '1';
            }, 300);
        }

        // ============================================================
        // 채팅
        // ============================================================
        async function startChat() {
            document.getElementById('quicktestSection').classList.remove('active');
            document.getElementById('chatSection').classList.add('active');

            // 에이전트 정보 로드
            await loadAgentInfo();

            // 진입 메시지 표시
            await showEntryMessage();
        }

        async function loadAgentInfo() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_agent_entry');
                formData.append('agent_num', MentorApp.currentAgent);

                const response = await fetch(MentorApp.apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // UI 업데이트
                    document.getElementById('agentIcon').textContent = data.agent.icon;
                    document.getElementById('agentName').textContent = data.agent.name;
                    document.getElementById('agentObjective').textContent = '미션: ' + data.agent.objective;

                    // 방문 에이전트 표시 업데이트
                    updateVisitedAgents();

                    return data;
                }
            } catch (error) {
                console.error('에이전트 정보 로드 오류:', error);
            }
        }

        function updateVisitedAgents() {
            const container = document.getElementById('visitedAgents');
            const visited = MentorApp.sessionData?.visited_agents || [MentorApp.currentAgent];

            // 에이전트 아이콘 매핑 (간단히)
            const agentIcons = {
                1: '📡', 2: '🗺️', 3: '📊', 4: '⭐', 5: '⚡',
                6: '🔍', 7: '🎯', 8: '💎', 9: '🔬', 10: '📦',
                11: '🤖', 12: '📢', 13: '🏕️', 14: '🛡️', 15: '🗼',
                16: '🌱', 17: '🔗', 18: '📡', 19: '🌌', 20: '💎', 21: '⚙️'
            };

            container.innerHTML = visited.map(num => `
                <div class="visited-agent ${num === MentorApp.currentAgent ? 'current' : ''}">
                    ${agentIcons[num] || '🎯'}
                </div>
            `).join('');
        }

        async function showEntryMessage() {
            // 에이전트 진입 메시지 가져오기
            const formData = new FormData();
            formData.append('action', 'get_agent_entry');
            formData.append('agent_num', MentorApp.currentAgent);

            try {
                const response = await fetch(MentorApp.apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // 진입 메시지 표시
                    addMessage('assistant', data.entry_message);

                    // 초기 선택지 표시
                    updateSuggestions(data.suggestions);

                    // 히스토리에 추가
                    MentorApp.chatHistory.push({
                        role: 'assistant',
                        content: data.entry_message
                    });
                }
            } catch (error) {
                console.error('진입 메시지 오류:', error);
                addMessage('assistant', '안녕! 무엇을 도와줄까?');
            }
        }

        function addMessage(role, content) {
            const container = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}`;
            messageDiv.innerHTML = `<div class="message-content">${content}</div>`;
            container.appendChild(messageDiv);

            // 스크롤
            container.scrollTop = container.scrollHeight;
        }

        function updateSuggestions(suggestions) {
            const container = document.getElementById('suggestionsContainer');

            if (!suggestions || suggestions.length === 0) {
                container.innerHTML = '';
                return;
            }

            container.innerHTML = suggestions.map(s => `
                <button class="suggestion-btn" onclick="selectSuggestion('${escapeHtml(s)}')">${s}</button>
            `).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/'/g, "\\'");
        }

        function selectSuggestion(text) {
            document.getElementById('chatInput').value = text;
            sendMessage();
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();

            if (!message || MentorApp.isLoading) return;

            // 사용자 메시지 표시
            addMessage('user', message);
            input.value = '';

            // 히스토리에 추가
            MentorApp.chatHistory.push({ role: 'user', content: message });

            // 선택지 숨기기
            document.getElementById('suggestionsContainer').innerHTML = '';

            // 타이핑 인디케이터 표시
            showTyping(true);
            MentorApp.isLoading = true;

            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('agent_num', MentorApp.currentAgent);
                formData.append('message', message);
                formData.append('history', JSON.stringify(MentorApp.chatHistory));
                formData.append('session_data', JSON.stringify(MentorApp.sessionData));

                const response = await fetch(MentorApp.apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                showTyping(false);

                if (data.success) {
                    // AI 응답 표시
                    addMessage('assistant', data.message);

                    // 히스토리에 추가
                    MentorApp.chatHistory.push({ role: 'assistant', content: data.message });

                    // 세션 업데이트
                    if (data.session_update) {
                        MentorApp.sessionData = { ...MentorApp.sessionData, ...data.session_update };
                    }

                    // 에이전트 전환 여부 확인
                    if (data.should_transition && data.next_agent) {
                        showTransitionCard(data.next_agent);
                    } else {
                        // 선택지 업데이트
                        updateSuggestions(data.suggestions);
                    }
                } else {
                    addMessage('assistant', '앗, 잠시 문제가 생겼어. 다시 말해줄래? 😅');
                    console.error('API 오류:', data.error);
                }
            } catch (error) {
                showTyping(false);
                addMessage('assistant', '연결이 불안정해. 다시 시도해볼래?');
                console.error('전송 오류:', error);
            }

            MentorApp.isLoading = false;
        }

        function showTyping(show) {
            const indicator = document.getElementById('typingIndicator');
            if (show) {
                indicator.classList.add('active');
                document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
            } else {
                indicator.classList.remove('active');
            }
        }

        function showTransitionCard(nextAgent) {
            const container = document.getElementById('chatMessages');

            const card = document.createElement('div');
            card.className = 'transition-card';
            card.innerHTML = `
                <h4>${nextAgent.transition_message}</h4>
                <p>다음 미션: ${nextAgent.objective}</p>
                <div class="transition-btns">
                    <button class="transition-btn primary" onclick="transitionToAgent(${nextAgent.number})">
                        ${nextAgent.icon} 만나기
                    </button>
                    <button class="transition-btn secondary" onclick="continueCurrentAgent()">
                        좀 더 얘기할래
                    </button>
                </div>
            `;

            container.appendChild(card);
            container.scrollTop = container.scrollHeight;
        }

        async function transitionToAgent(agentNum) {
            showLoading(true);

            try {
                const formData = new FormData();
                formData.append('action', 'get_next_agent');
                formData.append('current_agent', MentorApp.currentAgent);
                formData.append('session_data', JSON.stringify(MentorApp.sessionData));

                const response = await fetch(MentorApp.apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // 상태 업데이트
                    MentorApp.currentAgent = data.next_agent.number;
                    MentorApp.sessionData = data.session_data;
                    MentorApp.chatHistory = []; // 히스토리 리셋

                    // UI 초기화
                    document.getElementById('chatMessages').innerHTML = '';

                    // 새 에이전트 시작
                    await loadAgentInfo();
                    await showEntryMessage();
                }
            } catch (error) {
                console.error('에이전트 전환 오류:', error);
            }

            showLoading(false);
        }

        function continueCurrentAgent() {
            // 전환 카드 제거
            const cards = document.querySelectorAll('.transition-card');
            cards.forEach(c => c.remove());

            // 계속 대화 선택지
            updateSuggestions(['더 얘기해줘', '다른 주제로 넘어가자', '정리해줘']);
        }

        // ============================================================
        // 유틸리티
        // ============================================================
        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }

        // 엔터키로 전송
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>
