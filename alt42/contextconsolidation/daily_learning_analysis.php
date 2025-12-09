<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학일기 취약지점 분석 - AI 학습 플래너</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 16px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 24px;
            color: white;
        }

        .header h1 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            gap: 16px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #d1d5db;
            color: white;
            font-weight: bold;
        }

        .step.active .step-circle {
            background: #3b82f6;
        }

        .step-text {
            font-weight: 500;
            color: #9ca3af;
        }

        .step.active .step-text {
            color: #3b82f6;
        }

        .step-line {
            width: 48px;
            height: 2px;
            background: #d1d5db;
        }

        /* Main Layout */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        /* Chat Interface */
        .chat-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            padding: 24px;
            height: 600px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .chat-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .help-button {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
        }

        .help-button:hover {
            color: #374151;
        }

        /* Help Box */
        .help-box {
            margin-bottom: 16px;
            padding: 16px;
            background: #dbeafe;
            border-radius: 8px;
            border: 1px solid #60a5fa;
        }

        .help-box h3 {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .help-box ol {
            font-size: 14px;
            color: #2563eb;
            padding-left: 20px;
        }

        .help-box li {
            margin-bottom: 4px;
        }

        /* Messages Area */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 16px;
            padding-right: 8px;
        }

        .message {
            margin-bottom: 16px;
            display: flex;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 80%;
            padding: 16px;
            border-radius: 8px;
            white-space: pre-line;
        }

        .message.assistant .message-bubble {
            background: #f3f4f6;
            color: #1f2937;
        }

        .message.user .message-bubble {
            background: #3b82f6;
            color: white;
        }

        .message-time {
            font-size: 12px;
            margin-top: 8px;
            opacity: 0.7;
        }

        .loading-message {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            width: fit-content;
        }

        /* Input Area */
        .input-area {
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
        }

        .diary-textarea {
            width: 100%;
            height: 128px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            resize: none;
            font-size: 14px;
        }

        .diary-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .analyze-button {
            width: 100%;
            margin-top: 12px;
            padding: 12px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .analyze-button:hover {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
        }

        .analyze-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        /* Right Panel */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Timer Card */
        .timer-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }

        .timer-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timer-display {
            font-size: 48px;
            font-family: monospace;
            font-weight: bold;
            text-align: center;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .timer-select {
            width: 100%;
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 16px;
        }

        .timer-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .timer-button {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
        }

        .timer-button.start {
            background: #10b981;
            color: white;
        }

        .timer-button.start:hover {
            background: #059669;
        }

        .timer-button.pause {
            background: #f59e0b;
            color: white;
        }

        .timer-button.pause:hover {
            background: #d97706;
        }

        .timer-button.reset {
            background: #6b7280;
            color: white;
        }

        .timer-button.reset:hover {
            background: #4b5563;
        }

        .timer-progress {
            margin-top: 16px;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 1s linear;
        }

        /* Study Status Card */
        .status-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }

        .status-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .status-label {
            color: #6b7280;
        }

        .status-value {
            font-weight: 600;
        }

        .overall-progress {
            margin-top: 16px;
        }

        .progress-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        /* Tips Card */
        .tips-card {
            background: linear-gradient(135deg, #e0e7ff, #dbeafe);
            border-radius: 12px;
            padding: 24px;
        }

        .tips-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tips-list {
            font-size: 14px;
            color: #374151;
            list-style: none;
        }

        .tips-list li {
            margin-bottom: 8px;
            padding-left: 16px;
        }

        /* Results Section */
        .results-section {
            margin-top: 32px;
        }

        .student-info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-name {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }

        .student-stats {
            display: flex;
            gap: 16px;
            margin-top: 8px;
            font-size: 14px;
            color: #6b7280;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .new-analysis-button {
            padding: 8px 16px;
            border: 2px solid #3b82f6;
            color: #3b82f6;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .new-analysis-button:hover {
            background: #eff6ff;
        }

        /* Recommendation Cards */
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }

        .recommendation-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid;
            padding: 24px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .recommendation-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .recommendation-card.priority-high {
            border-color: #fca5a5;
            background: #fee2e2;
        }

        .recommendation-card.priority-medium {
            border-color: #fde047;
            background: #fef9c3;
        }

        .recommendation-card.priority-low {
            border-color: #86efac;
            background: #dcfce7;
        }

        .recommendation-card.completed {
            border-color: #86efac;
            background: #dcfce7;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }

        .topic-info h3 {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .topic-info p {
            font-size: 14px;
            color: #6b7280;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        .priority-badge.high {
            background: #fee2e2;
            color: #dc2626;
        }

        .priority-badge.medium {
            background: #fef3c7;
            color: #d97706;
        }

        .priority-badge.low {
            background: #d1fae5;
            color: #065f46;
        }

        /* Study Stats */
        .study-stats {
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 14px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
        }

        .stat-label {
            color: #6b7280;
        }

        .stat-value {
            font-weight: 600;
        }

        .recent-badge {
            margin-top: 8px;
            font-size: 12px;
            color: #2563eb;
            font-weight: 500;
        }

        /* Diagnosis */
        .diagnosis {
            font-style: italic;
            color: #4b5563;
            font-size: 14px;
            margin-bottom: 16px;
            padding: 12px;
            background: white;
            border-radius: 8px;
        }

        /* Recommended Time */
        .recommended-time {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        /* Concept Links */
        .concepts-section {
            margin-top: 16px;
        }

        .concepts-title {
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 8px;
        }

        .concept-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px;
            background: #eff6ff;
            border-radius: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .concept-link:hover {
            background: #dbeafe;
            transform: translateX(4px);
        }

        .concept-name {
            font-size: 14px;
            color: #2563eb;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .expand-button {
            background: none;
            border: none;
            color: #3b82f6;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 4px;
        }

        .expand-button:hover {
            color: #2563eb;
        }

        /* Icons */
        .icon {
            width: 20px;
            height: 20px;
            display: inline-block;
        }

        .icon-sm {
            width: 16px;
            height: 16px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .recommendations-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Utilities */
        .hidden {
            display: none;
        }

        /* Spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                대화형 AI 수학 학습 플래너
            </h1>
            <p>mathking.kr 수학일기를 분석하여 맞춤형 복습 계획을 만들어드려요</p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps" id="progressSteps">
            <div class="step active" id="step1">
                <div class="step-circle">1</div>
                <span class="step-text">수학일기 입력</span>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step2">
                <div class="step-circle">2</div>
                <span class="step-text">AI 분석</span>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step3">
                <div class="step-circle">3</div>
                <span class="step-text">맞춤 추천</span>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left: Chat Interface -->
            <div class="chat-panel">
                <div class="chat-header">
                    <h2 class="chat-title">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        AI 학습 도우미
                    </h2>
                    <button class="help-button" onclick="toggleHelp()">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                </div>

                <!-- Help Box -->
                <div class="help-box hidden" id="helpBox">
                    <h3>사용 방법</h3>
                    <ol>
                        <li>1. mathking.kr에서 수학일기 내용을 복사하세요</li>
                        <li>2. 아래 입력창에 붙여넣기(Ctrl+V) 하세요</li>
                        <li>3. AI가 학습 패턴을 분석합니다</li>
                        <li>4. 맞춤형 복습 계획을 확인하세요</li>
                    </ol>
                </div>

                <!-- Messages Container -->
                <div class="messages-container" id="messagesContainer">
                    <div class="message assistant">
                        <div class="message-bubble">
                            안녕하세요! AI 수학 학습 도우미입니다 🤖
mathking.kr 수학일기를 분석하여 맞춤형 복습 계획을 만들어드릴게요.
                            <div class="message-time"></div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="input-area" id="inputArea">
                    <textarea 
                        id="diaryInput"
                        class="diary-textarea" 
                        placeholder="수학일기 내용을 여기에 붙여넣으세요..."
                    ></textarea>
                    <button class="analyze-button" onclick="analyzeDiary()">
                        <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        수학일기 분석하기
                    </button>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Timer Card -->
                <div class="timer-card">
                    <h3 class="timer-title">
                        <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        학습 타이머
                    </h3>
                    
                    <div class="timer-display" id="timerDisplay">30:00</div>
                    
                    <select class="timer-select" id="timerSelect" onchange="updateTimerDisplay()">
                        <option value="20">20분</option>
                        <option value="30" selected>30분</option>
                        <option value="40">40분</option>
                        <option value="50">50분</option>
                        <option value="60">60분</option>
                    </select>

                    <div class="timer-buttons">
                        <button class="timer-button start" id="startButton" onclick="startTimer()">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            시작
                        </button>
                        <button class="timer-button pause hidden" id="pauseButton" onclick="pauseTimer()">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            일시정지
                        </button>
                        <button class="timer-button reset" onclick="resetTimer()">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            리셋
                        </button>
                    </div>

                    <div class="timer-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="timerProgress" style="width: 100%"></div>
                        </div>
                    </div>
                </div>

                <!-- Study Status (shown after analysis) -->
                <div class="status-card hidden" id="statusCard">
                    <h3 class="status-title">오늘의 학습 상태</h3>
                    <div class="status-item">
                        <span class="status-label">완료한 주제</span>
                        <span class="status-value" id="completedCount">0개</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">남은 주제</span>
                        <span class="status-value" id="remainingCount">0개</span>
                    </div>
                    <div class="overall-progress">
                        <div class="progress-label">진행률</div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="overallProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="tips-card">
                    <h3 class="tips-title">
                        <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        학습 팁
                    </h3>
                    <ul class="tips-list">
                        <li>• 타이머를 설정하여 집중력을 높이세요</li>
                        <li>• 어려운 단원부터 복습하는 것이 효과적입니다</li>
                        <li>• 복습 후에는 꼭 문제를 풀어보세요</li>
                        <li>• 이해가 안 되면 개념부터 다시 확인하세요</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section hidden" id="resultsSection">
            <!-- Student Info Card -->
            <div class="student-info-card">
                <div class="student-header">
                    <div>
                        <h2 class="student-name" id="studentName"></h2>
                        <div class="student-stats">
                            <div class="stat-item">
                                <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>총 학습시간: <span id="totalStudyTime"></span></span>
                            </div>
                            <div class="stat-item">
                                <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                </svg>
                                <span>평균 이탈시간: <span id="avgEscapeTime"></span>분</span>
                            </div>
                        </div>
                    </div>
                    <button class="new-analysis-button" onclick="resetAnalysis()">새로운 분석</button>
                </div>
            </div>

            <!-- Recommendations Grid -->
            <div class="recommendations-grid" id="recommendationsGrid">
                <!-- Recommendation cards will be generated here -->
            </div>
        </div>
    </div>

    <script>
        // State management
        let currentStep = 1;
        let isAnalyzing = false;
        let recommendations = null;
        let completedTopics = [];
        let messages = [{
            type: 'assistant',
            content: '안녕하세요! AI 수학 학습 도우미입니다 🤖\nmathking.kr 수학일기를 분석하여 맞춤형 복습 계획을 만들어드릴게요.',
            timestamp: new Date()
        }];

        // Timer state
        let selectedMinutes = 30;
        let timeLeft = 0;
        let isTimerRunning = false;
        let timerStarted = false;
        let timerInterval = null;

        // Database (simplified version)
        const projectKnowledge = {
            '문자와 식': {
                keywords: ['문자를 사용한 식', '식의 값', '일차식', '동류항', '다항식', '계수'],
                difficulty: '중1',
                diagnosis: '문자를 사용한 식의 표현과 계산에서 실수가 발생하고 있습니다.',
                concepts: [
                    { name: '문자를 사용한 식', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53169&page=1&quizid=86096' },
                    { name: '곱셈 기호와 나눗셈 기호의 생략', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53170&page=1&quizid=86097' },
                    { name: '식의 값', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53171&page=1&quizid=86098' },
                    { name: '다항식', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=5&cmid=53172&page=1&quizid=86099' }
                ]
            },
            '일차방정식': {
                keywords: ['일차방정식', '방정식의 풀이', '등식의 성질', '이항', '방정식', '등식'],
                difficulty: '중1',
                diagnosis: '일차방정식의 이항과 정리 과정에서 실수가 발생합니다.',
                concepts: [
                    { name: '등식', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53178&page=1&quizid=86105' },
                    { name: '방정식과 항등식', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53179&page=1&quizid=86106' },
                    { name: '등식의 성질', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53180&page=1&quizid=86107' },
                    { name: '이항', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=6&cmid=53182&page=1&quizid=86109' }
                ]
            },
            '함수': {
                keywords: ['함수', '함숫값', '함수의 그래프', '좌표평면', 'y=ax', 'y=a/x', '함수의 뜻'],
                difficulty: '중1',
                diagnosis: '함수의 개념과 그래프 해석에 어려움이 있습니다.',
                concepts: [
                    { name: '함수의 뜻', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=8&cmid=53190&page=1&quizid=86117' },
                    { name: '함숫값', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=8&cmid=53192&page=1&quizid=86118' },
                    { name: '여러 가지 함수 관계', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=8&cmid=53191&page=1&quizid=86119' },
                    { name: '좌표평면', url: 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid=66&nch=9&cmid=53195&page=1&quizid=86122' }
                ]
            }
        };

        // Helper functions
        function toggleHelp() {
            const helpBox = document.getElementById('helpBox');
            helpBox.classList.toggle('hidden');
        }

        function updateStep(step) {
            currentStep = step;
            for (let i = 1; i <= 3; i++) {
                const stepEl = document.getElementById(`step${i}`);
                if (i <= step) {
                    stepEl.classList.add('active');
                } else {
                    stepEl.classList.remove('active');
                }
            }
        }

        function addMessage(type, content) {
            messages.push({
                type,
                content,
                timestamp: new Date()
            });
            renderMessages();
        }

        function renderMessages() {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.type}">
                    <div class="message-bubble">
                        ${msg.content}
                        <div class="message-time">${msg.timestamp.toLocaleTimeString()}</div>
                    </div>
                </div>
            `).join('');
            
            if (isAnalyzing) {
                container.innerHTML += `
                    <div class="message assistant">
                        <div class="loading-message">
                            <svg class="icon-sm spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>분석 중...</span>
                        </div>
                    </div>
                `;
            }
            
            container.scrollTop = container.scrollHeight;
        }

        // Timer functions
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function updateTimerDisplay() {
            const select = document.getElementById('timerSelect');
            selectedMinutes = parseInt(select.value);
            if (!timerStarted) {
                document.getElementById('timerDisplay').textContent = formatTime(selectedMinutes * 60);
            }
        }

        function startTimer() {
            if (!timerStarted) {
                timeLeft = selectedMinutes * 60;
                timerStarted = true;
            }
            isTimerRunning = true;
            
            document.getElementById('startButton').classList.add('hidden');
            document.getElementById('pauseButton').classList.remove('hidden');
            document.getElementById('timerSelect').disabled = true;
            
            addMessage('assistant', `${selectedMinutes}분 타이머를 시작했습니다! 집중해서 학습하세요 💪`);
            
            timerInterval = setInterval(() => {
                if (timeLeft > 0) {
                    timeLeft--;
                    document.getElementById('timerDisplay').textContent = formatTime(timeLeft);
                    document.getElementById('timerProgress').style.width = `${(timeLeft / (selectedMinutes * 60)) * 100}%`;
                } else {
                    pauseTimer();
                    addMessage('assistant', '학습 시간이 종료되었습니다! 수고하셨습니다 👏\n다음 복습 주제로 넘어가시겠어요?');
                }
            }, 1000);
        }

        function pauseTimer() {
            isTimerRunning = false;
            clearInterval(timerInterval);
            
            document.getElementById('pauseButton').classList.add('hidden');
            document.getElementById('startButton').classList.remove('hidden');
            
            addMessage('assistant', '타이머를 일시정지했습니다. 준비되면 다시 시작하세요.');
        }

        function resetTimer() {
            isTimerRunning = false;
            timerStarted = false;
            clearInterval(timerInterval);
            
            document.getElementById('pauseButton').classList.add('hidden');
            document.getElementById('startButton').classList.remove('hidden');
            document.getElementById('timerSelect').disabled = false;
            
            updateTimerDisplay();
            document.getElementById('timerProgress').style.width = '100%';
            
            addMessage('assistant', '타이머를 리셋했습니다.');
        }

        // Analysis functions
        function searchProjectKnowledge(keyword) {
            for (const [topic, data] of Object.entries(projectKnowledge)) {
                if (data.keywords.some(kw => keyword.includes(kw))) {
                    return { topic, ...data };
                }
            }
            return null;
        }

        async function analyzeDiary() {
            const diaryText = document.getElementById('diaryInput').value.trim();
            
            if (!diaryText) {
                addMessage('assistant', '수학일기 내용을 붙여넣어 주세요! 📋');
                return;
            }
            
            isAnalyzing = true;
            updateStep(2);
            addMessage('user', '수학일기를 분석해주세요.');
            addMessage('assistant', '네! 지금부터 수학일기를 분석하여 맞춤형 복습 계획을 만들어드릴게요. 잠시만 기다려주세요... 🔍');
            renderMessages();
            
            // Simulate analysis delay
            setTimeout(() => {
                performAnalysis(diaryText);
            }, 2000);
        }

        function performAnalysis(diaryText) {
            const lines = diaryText.split('\n').filter(line => line.trim());
            const studyRecords = [];
            let studentName = '';
            
            // Extract student name
            const nameMatch = diaryText.match(/★\s*([^\s]+)/);
            if (nameMatch) {
                studentName = nameMatch[1];
            }
            
            // Parse study records
            lines.forEach(line => {
                const dateMatch = line.match(/(\d{2}\/\d{2})\s+(\d{2}:\d{2})/);
                const prepMatch = line.match(/🌱\s*준비\s*\|\s*([^|]+)/);
                const examMatch = line.match(/🍎\s*응시\s*\|\s*([^|]+)/);
                const timeMatch = line.match(/(\d+)분\s+(\d+)분/);
                const satisfactionMatch = line.match(/(매우 만족|만족|불만족)/);
                const escapeMatch = line.match(/이탈\s*\((\d+)\)/);
                
                if (dateMatch && (prepMatch || examMatch) && timeMatch) {
                    const topic = (prepMatch || examMatch)[1].trim();
                    studyRecords.push({
                        date: dateMatch[1],
                        time: dateMatch[2],
                        type: prepMatch ? '준비' : '응시',
                        topic: topic,
                        plannedTime: parseInt(timeMatch[1]),
                        actualTime: parseInt(timeMatch[2]),
                        satisfaction: satisfactionMatch ? satisfactionMatch[1] : '',
                        escapeTime: escapeMatch ? parseInt(escapeMatch[1]) : 0
                    });
                }
            });
            
            // Analyze patterns
            const topicAnalysis = {};
            const recentTopics = new Set();
            const lastWeekRecords = studyRecords.slice(0, 20);
            
            studyRecords.forEach(record => {
                const searchResult = searchProjectKnowledge(record.topic);
                
                if (searchResult) {
                    const { topic, ...contentData } = searchResult;
                    
                    if (!topicAnalysis[topic]) {
                        topicAnalysis[topic] = {
                            count: 0,
                            totalTime: 0,
                            escapeTime: 0,
                            dissatisfactionCount: 0,
                            records: [],
                            contentData: contentData
                        };
                    }
                    
                    topicAnalysis[topic].count++;
                    topicAnalysis[topic].totalTime += record.actualTime;
                    topicAnalysis[topic].escapeTime += record.escapeTime;
                    if (record.satisfaction === '불만족') {
                        topicAnalysis[topic].dissatisfactionCount++;
                    }
                    topicAnalysis[topic].records.push(record);
                    
                    if (lastWeekRecords.includes(record)) {
                        recentTopics.add(topic);
                    }
                }
            });
            
            // Generate recommendations
            generateRecommendations({
                studentName,
                totalRecords: studyRecords.length,
                totalStudyTime: studyRecords.reduce((sum, r) => sum + r.actualTime, 0),
                averageEscapeTime: studyRecords.reduce((sum, r) => sum + r.escapeTime, 0) / studyRecords.length,
                topicAnalysis,
                recentTopics: Array.from(recentTopics)
            });
        }

        function generateRecommendations(analysisData) {
            const recs = [];
            
            for (const [topic, data] of Object.entries(analysisData.topicAnalysis)) {
                const avgEscapeRate = data.escapeTime / data.totalTime;
                const dissatisfactionRate = data.dissatisfactionCount / data.count;
                const isRecent = analysisData.recentTopics.includes(topic);
                
                let priority = 'low';
                let score = 0;
                
                if (avgEscapeRate > 0.2) score += 30;
                else if (avgEscapeRate > 0.1) score += 20;
                
                if (dissatisfactionRate > 0.5) score += 40;
                else if (dissatisfactionRate > 0.3) score += 25;
                
                if (isRecent && (avgEscapeRate > 0.1 || dissatisfactionRate > 0.3)) score += 20;
                
                if (data.count > 3) score += 15;
                
                if (score >= 50) priority = 'high';
                else if (score >= 30) priority = 'medium';
                
                recs.push({
                    topic,
                    priority,
                    score,
                    studyCount: data.count,
                    totalTime: data.totalTime,
                    avgEscapeRate: Math.round(avgEscapeRate * 100),
                    dissatisfactionRate: Math.round(dissatisfactionRate * 100),
                    isRecent,
                    ...data.contentData,
                    estimatedTime: priority === 'high' ? 40 : priority === 'medium' ? 30 : 20
                });
            }
            
            recs.sort((a, b) => b.score - a.score);
            
            recommendations = {
                student: {
                    name: analysisData.studentName,
                    totalStudyTime: `${Math.floor(analysisData.totalStudyTime / 60)}시간 ${analysisData.totalStudyTime % 60}분`,
                    avgEscapeTime: Math.round(analysisData.averageEscapeTime)
                },
                recommendations: recs.slice(0, 6)
            };
            
            isAnalyzing = false;
            updateStep(3);
            
            // Add AI message
            addMessage('assistant', `분석이 완료되었습니다! ${analysisData.studentName} 학생님을 위한 맞춤형 복습 계획을 준비했어요 📊

총 ${recs.length}개의 복습이 필요한 단원을 발견했고, 우선순위가 높은 ${Math.min(6, recs.length)}개를 추천드립니다.

특히 "${recs[0]?.topic}" 단원은 이탈률이 ${recs[0]?.avgEscapeRate}%로 높아 집중적인 복습이 필요해 보입니다. 

지금 바로 복습을 시작하시겠어요? 타이머를 설정하고 시작해보세요! ⏰`);
            
            showResults();
        }

        function showResults() {
            document.getElementById('inputArea').classList.add('hidden');
            document.getElementById('progressSteps').classList.add('hidden');
            document.getElementById('resultsSection').classList.remove('hidden');
            document.getElementById('statusCard').classList.remove('hidden');
            
            // Update student info
            document.getElementById('studentName').textContent = recommendations.student.name + ' 학생님의 맞춤 복습 계획';
            document.getElementById('totalStudyTime').textContent = recommendations.student.totalStudyTime;
            document.getElementById('avgEscapeTime').textContent = recommendations.student.avgEscapeTime;
            
            // Update status
            updateStudyStatus();
            
            // Render recommendation cards
            renderRecommendations();
        }

        function renderRecommendations() {
            const grid = document.getElementById('recommendationsGrid');
            grid.innerHTML = recommendations.recommendations.map((rec, index) => {
                const isCompleted = completedTopics.includes(rec.topic);
                
                return `
                    <div class="recommendation-card priority-${rec.priority} ${isCompleted ? 'completed' : ''}" 
                         onclick="toggleTopicExpansion('${rec.topic}')">
                        <div class="card-header">
                            <div class="topic-info">
                                <h3>${rec.topic}</h3>
                                <p>${rec.difficulty}</p>
                            </div>
                            ${isCompleted ? 
                                '<svg class="icon" fill="none" stroke="currentColor" style="color: #10b981;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>' :
                                `<span class="priority-badge ${rec.priority}">${rec.priority === 'high' ? '긴급' : rec.priority === 'medium' ? '보통' : '권장'}</span>`
                            }
                        </div>
                        
                        <div class="study-stats">
                            <div class="stats-grid">
                                <div class="stat-row">
                                    <span class="stat-label">학습 횟수:</span>
                                    <span class="stat-value">${rec.studyCount}회</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">총 시간:</span>
                                    <span class="stat-value">${rec.totalTime}분</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">이탈률:</span>
                                    <span class="stat-value" style="color: #ea580c;">${rec.avgEscapeRate}%</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">불만족:</span>
                                    <span class="stat-value" style="color: #dc2626;">${rec.dissatisfactionRate}%</span>
                                </div>
                            </div>
                            ${rec.isRecent ? '<div class="recent-badge">📌 최근 학습 중</div>' : ''}
                        </div>
                        
                        <div class="diagnosis">"${rec.diagnosis}"</div>
                        
                        <div class="recommended-time">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span>권장 복습시간: ${rec.estimatedTime}분</span>
                        </div>
                        
                        ${rec.concepts && rec.concepts.length > 0 ? `
                            <div class="concepts-section">
                                <p class="concepts-title">추천 학습 콘텐츠</p>
                                <div id="concepts-${rec.topic}">
                                    ${rec.concepts.slice(0, 3).map((concept, idx) => `
                                        <a href="${concept.url}" target="_blank" class="concept-link" 
                                           onclick="event.stopPropagation(); ${!isCompleted ? `completeTopicAndOpen('${rec.topic}')` : ''}">
                                            <span class="concept-name">
                                                <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                                ${concept.name}
                                            </span>
                                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    `).join('')}
                                </div>
                                ${rec.concepts.length > 3 ? `
                                    <button class="expand-button" onclick="event.stopPropagation(); expandConcepts('${rec.topic}')">
                                        +${rec.concepts.length - 3}개 더 보기
                                    </button>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }

        function updateStudyStatus() {
            const total = recommendations.recommendations.length;
            const completed = completedTopics.length;
            const remaining = total - completed;
            const progress = (completed / total) * 100;
            
            document.getElementById('completedCount').textContent = `${completed}개`;
            document.getElementById('remainingCount').textContent = `${remaining}개`;
            document.getElementById('overallProgress').style.width = `${progress}%`;
        }

        function completeTopicAndOpen(topic) {
            if (!completedTopics.includes(topic)) {
                completedTopics.push(topic);
                addMessage('assistant', `${topic} 복습을 완료하셨군요! 훌륭합니다 👏\n이해가 잘 되셨나요? 다음 주제로 넘어가시거나 추가 연습이 필요하시면 말씀해주세요.`);
                updateStudyStatus();
                renderRecommendations();
            }
        }

        function toggleTopicExpansion(topic) {
            // Implementation for expanding topic details if needed
        }

        function expandConcepts(topic) {
            const rec = recommendations.recommendations.find(r => r.topic === topic);
            if (rec) {
                const conceptsDiv = document.getElementById(`concepts-${topic}`);
                conceptsDiv.innerHTML = rec.concepts.map((concept, idx) => `
                    <a href="${concept.url}" target="_blank" class="concept-link" 
                       onclick="event.stopPropagation(); ${!completedTopics.includes(topic) ? `completeTopicAndOpen('${topic}')` : ''}">
                        <span class="concept-name">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            ${concept.name}
                        </span>
                        <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                `).join('');
            }
        }

        function resetAnalysis() {
            recommendations = null;
            completedTopics = [];
            currentStep = 1;
            isAnalyzing = false;
            
            document.getElementById('diaryInput').value = '';
            document.getElementById('inputArea').classList.remove('hidden');
            document.getElementById('progressSteps').classList.remove('hidden');
            document.getElementById('resultsSection').classList.add('hidden');
            document.getElementById('statusCard').classList.add('hidden');
            
            updateStep(1);
            messages = [{
                type: 'assistant',
                content: '새로운 수학일기를 분석할 준비가 되었습니다! 📊',
                timestamp: new Date()
            }];
            renderMessages();
            
            resetTimer();
        }

        // Initialize
        renderMessages();
    </script>
</body>
</html>