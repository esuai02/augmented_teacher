<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 체계적 진도형 - Welcome Session</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .container {
            width: 100%;
            max-width: 900px;
            height: 100vh;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .scene {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transform: translateY(50px);
            transition: all 1s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 40px;
        }

        .scene.active {
            opacity: 1;
            transform: translateY(0);
        }

        .scene.past {
            opacity: 0;
            transform: translateY(-50px);
        }

        /* Scene 1: Opening */
        .opening-text {
            font-size: 2.5rem;
            color: white;
            text-align: center;
            line-height: 1.5;
            font-weight: 300;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .typing-cursor {
            display: inline-block;
            width: 3px;
            height: 1.2em;
            background: white;
            animation: blink 1s infinite;
            margin-left: 5px;
            vertical-align: text-bottom;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        /* Scene 2: Journey Map */
        .journey-container {
            width: 100%;
            max-width: 700px;
            background: white;
            border-radius: 30px;
            padding: 50px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .journey-title {
            font-size: 2rem;
            color: #333;
            text-align: center;
            margin-bottom: 40px;
            opacity: 0;
            animation: fadeInUp 1s forwards;
        }

        .path-visual {
            position: relative;
            height: 400px;
            margin: 40px 0;
        }

        .path-line {
            position: absolute;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            animation: growDown 2s forwards 0.5s;
        }

        @keyframes growDown {
            from {
                height: 0;
                opacity: 0;
            }
            to {
                height: 100%;
                opacity: 1;
            }
        }

        .milestone {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            opacity: 0;
        }

        .milestone.left {
            flex-direction: row-reverse;
            right: 50%;
            left: auto;
            transform: translateX(50%);
        }

        .milestone-dot {
            width: 20px;
            height: 20px;
            background: white;
            border: 4px solid #667eea;
            border-radius: 50%;
            margin: 0 20px;
            position: relative;
            z-index: 2;
        }

        .milestone-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            max-width: 250px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .milestone-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .milestone-desc {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Scene 3: Framework Introduction */
        .framework-container {
            background: white;
            border-radius: 30px;
            padding: 50px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .framework-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .framework-title {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .framework-subtitle {
            font-size: 1.2rem;
            color: #666;
        }

        .intelligence-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 40px 0;
        }

        .intelligence-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            transform: scale(0.8);
            position: relative;
        }

        .intelligence-card:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .intelligence-card.revealed {
            animation: cardReveal 0.5s forwards;
        }

        @keyframes cardReveal {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .intelligence-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .intelligence-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .intelligence-desc {
            font-size: 0.8rem;
            color: #666;
        }

        /* Modal for Intelligence Details */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 30px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            transform: scale(0.8);
            transition: transform 0.3s ease;
            position: relative;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: #e0e0e0;
            transform: rotate(90deg);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }

        .modal-subtitle {
            font-size: 1.1rem;
            color: #666;
            font-style: italic;
        }

        .modal-body {
            color: #444;
            line-height: 1.8;
        }

        .modal-section {
            margin-bottom: 25px;
        }

        .modal-section-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .modal-list {
            margin-left: 20px;
        }

        .modal-list li {
            margin-bottom: 8px;
            list-style: none;
            position: relative;
            padding-left: 20px;
        }

        .modal-list li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #764ba2;
        }

        .modal-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        /* Scene 4: Personal Promise */
        .promise-container {
            background: white;
            border-radius: 30px;
            padding: 60px;
            width: 100%;
            max-width: 700px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .promise-icon {
            font-size: 4rem;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .promise-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }

        .promise-text {
            font-size: 1.2rem;
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .promise-features {
            display: flex;
            justify-content: space-around;
            margin: 40px 0;
        }

        .feature-item {
            opacity: 0;
            transform: translateY(20px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .feature-text {
            font-size: 0.9rem;
            color: #666;
        }

        /* Scene 5: Start Journey */
        .start-container {
            text-align: center;
        }

        .start-title {
            font-size: 3rem;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .start-subtitle {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 50px;
        }

        .start-button {
            background: white;
            color: #667eea;
            font-size: 1.3rem;
            padding: 20px 60px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            opacity: 0;
            transform: scale(0.8);
            animation: buttonReveal 1s forwards 2s;
        }

        @keyframes buttonReveal {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .start-button:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        /* Navigation */
        .nav-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            background: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            opacity: 0;
            animation: fadeIn 1s forwards 3s;
        }

        .nav-btn:hover {
            transform: scale(1.1);
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        /* Progress Indicator */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.2);
            z-index: 100;
        }

        .progress-fill {
            height: 100%;
            background: white;
            transition: width 0.5s ease;
            width: 20%;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Particle Background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            from {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            to {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="progress-bar">
        <div class="progress-fill" id="progressBar"></div>
    </div>

    <div class="container">
        <!-- Scene 1: Opening -->
        <div class="scene active" id="scene1">
            <h1 class="opening-text">
                <span id="typingText"></span>
                <span class="typing-cursor"></span>
            </h1>
        </div>

        <!-- Scene 2: Journey Map -->
        <div class="scene" id="scene2">
            <div class="journey-container">
                <h2 class="journey-title">당신만의 학습 여정이 시작됩니다</h2>
                <div class="path-visual">
                    <div class="path-line"></div>
                    <div class="milestone" style="top: 0%;" data-delay="1.5">
                        <div class="milestone-dot"></div>
                        <div class="milestone-content">
                            <div class="milestone-title">📍 현재 위치 진단</div>
                            <div class="milestone-desc">당신의 시작점을 정확히 파악합니다</div>
                        </div>
                    </div>
                    <div class="milestone left" style="top: 25%;" data-delay="2">
                        <div class="milestone-dot"></div>
                        <div class="milestone-content">
                            <div class="milestone-title">🗺️ 맞춤 로드맵</div>
                            <div class="milestone-desc">체계적인 진도 계획을 설계합니다</div>
                        </div>
                    </div>
                    <div class="milestone" style="top: 50%;" data-delay="2.5">
                        <div class="milestone-dot"></div>
                        <div class="milestone-content">
                            <div class="milestone-title">⚡ 실시간 보정</div>
                            <div class="milestone-desc">매주 진단하고 즉시 조정합니다</div>
                        </div>
                    </div>
                    <div class="milestone left" style="top: 75%;" data-delay="3">
                        <div class="milestone-dot"></div>
                        <div class="milestone-content">
                            <div class="milestone-title">📈 성장 가속화</div>
                            <div class="milestone-desc">시험 D-30 집중 모드로 전환</div>
                        </div>
                    </div>
                    <div class="milestone" style="top: 100%;" data-delay="3.5">
                        <div class="milestone-dot"></div>
                        <div class="milestone-content">
                            <div class="milestone-title">🎯 목표 달성</div>
                            <div class="milestone-desc">당신의 성공을 함께 만듭니다</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scene 3: Framework Introduction -->
        <div class="scene" id="scene3">
            <div class="framework-container">
                <div class="framework-header">
                    <h2 class="framework-title">W·X·S·P·E·R·T·A</h2>
                    <p class="framework-subtitle">8가지 지능이 당신의 학습을 설계합니다</p>
                </div>
                <div class="intelligence-grid">
                    <div class="intelligence-card" data-delay="0.5" onclick="showIntelligenceDetail('world')">
                        <div class="intelligence-icon">🌍</div>
                        <div class="intelligence-name">World</div>
                        <div class="intelligence-desc">세계관 정렬</div>
                    </div>
                    <div class="intelligence-card" data-delay="0.7" onclick="showIntelligenceDetail('context')">
                        <div class="intelligence-icon">🧭</div>
                        <div class="intelligence-name">conteXt</div>
                        <div class="intelligence-desc">문맥 지능</div>
                    </div>
                    <div class="intelligence-card" data-delay="0.9" onclick="showIntelligenceDetail('structure')">
                        <div class="intelligence-icon">🏗️</div>
                        <div class="intelligence-name">Structure</div>
                        <div class="intelligence-desc">구조 설계</div>
                    </div>
                    <div class="intelligence-card" data-delay="1.1" onclick="showIntelligenceDetail('procedure')">
                        <div class="intelligence-icon">⚙️</div>
                        <div class="intelligence-name">Procedure</div>
                        <div class="intelligence-desc">절차 최적화</div>
                    </div>
                    <div class="intelligence-card" data-delay="1.3" onclick="showIntelligenceDetail('execution')">
                        <div class="intelligence-icon">🚀</div>
                        <div class="intelligence-name">Execution</div>
                        <div class="intelligence-desc">실행 지능</div>
                    </div>
                    <div class="intelligence-card" data-delay="1.5" onclick="showIntelligenceDetail('reflection')">
                        <div class="intelligence-icon">🔍</div>
                        <div class="intelligence-name">Reflection</div>
                        <div class="intelligence-desc">성찰 지능</div>
                    </div>
                    <div class="intelligence-card" data-delay="1.7" onclick="showIntelligenceDetail('traffic')">
                        <div class="intelligence-icon">📊</div>
                        <div class="intelligence-name">Traffic</div>
                        <div class="intelligence-desc">트래픽 관리</div>
                    </div>
                    <div class="intelligence-card" data-delay="1.9" onclick="showIntelligenceDetail('aftermath')">
                        <div class="intelligence-icon">♾️</div>
                        <div class="intelligence-name">Aftermath</div>
                        <div class="intelligence-desc">추상화 지능</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scene 4: Personal Promise -->
        <div class="scene" id="scene4">
            <div class="promise-container">
                <div class="promise-icon">🤝</div>
                <h2 class="promise-title">당신과의 약속</h2>
                <p class="promise-text">
                    "진도는 전략, 보정은 일상"<br>
                    매주 당신의 상태를 진단하고,<br>
                    최적의 경로를 재설계합니다.
                </p>
                <div class="promise-features">
                    <div class="feature-item" data-delay="1">
                        <div class="feature-icon">📚</div>
                        <div class="feature-text">체계적 진도</div>
                    </div>
                    <div class="feature-item" data-delay="1.3">
                        <div class="feature-icon">🎯</div>
                        <div class="feature-text">개인 맞춤</div>
                    </div>
                    <div class="feature-item" data-delay="1.6">
                        <div class="feature-icon">✏️</div>
                        <div class="feature-text">시험 대비</div>
                    </div>
                    <div class="feature-item" data-delay="1.9">
                        <div class="feature-icon">🧠</div>
                        <div class="feature-text">사고력 향상</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scene 5: Start Journey -->
        <div class="scene" id="scene5">
            <div class="start-container">
                <h1 class="start-title">준비되셨나요?</h1>
                <p class="start-subtitle">당신만의 학습 혁명이 시작됩니다</p>
                <button class="start-button" onclick="startJourney()">
                    시작하기 →
                </button>
            </div>
        </div>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn" id="prevBtn" onclick="previousScene()" disabled>←</button>
        <button class="nav-btn" id="nextBtn" onclick="nextScene()">→</button>
    </div>

    <!-- Intelligence Detail Modal -->
    <div class="modal-overlay" id="intelligenceModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">×</button>
            <div class="modal-header">
                <div class="modal-icon" id="modalIcon"></div>
                <h3 class="modal-title" id="modalTitle"></h3>
                <p class="modal-subtitle" id="modalSubtitle"></p>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        let currentScene = 1;
        const totalScenes = 5;

        // Intelligence Details Data
        const intelligenceData = {
            world: {
                icon: '🌍',
                title: 'World - 세계관 정렬',
                subtitle: '"진도는 전략, 보정은 일상"',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">핵심 신념</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">진도는 전략, 보정은 일상</span> - 계획과 실행의 균형</li>
                            <li>교과-단원 선형 진도 + 주간 진단-보정 루프</li>
                            <li>학습 한계 상황을 적시에 해소</li>
                            <li>매주 진단 후 계획 재조정</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">기본 전개</div>
                        <ul class="modal-list">
                            <li>단원 마스터리 80% 이상 달성</li>
                            <li>선행:복습 = 7:3 황금비율 유지</li>
                            <li>월간 커리큘럼 리셋으로 최적화</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">상황별 전환 규칙</div>
                        <ul class="modal-list">
                            <li>✏️ 시험 D-30: 성과집중형으로 자동 전환</li>
                            <li>🎯 기초 결손 감지: 개인맞춤형 즉시 보완</li>
                            <li>⚡ 동기저하: 목표달성형 미션으로 회복</li>
                            <li>🧠 이해도 부족: 사고력중심형 블록 삽입</li>
                        </ul>
                    </div>
                `
            },
            context: {
                icon: '🧭',
                title: 'conteXt - 문맥 지능',
                subtitle: '당신의 학습 상황을 실시간으로 파악합니다',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">필수 컨텍스트 수집 (주 1회)</div>
                        <ul class="modal-list">
                            <li>학년/범위 및 최근 단원 스코어</li>
                            <li>오답 패턴 분석 및 경향성 파악</li>
                            <li>선행/복습 비율 최적화 상태</li>
                            <li>시험 캘린더 및 D-day 관리</li>
                            <li>학습시간 로그 및 집중도 분석</li>
                            <li>결손 개념 리스트 실시간 업데이트</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">스위칭 트리거</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">D-30</span>: 시험모드 자동 활성화</li>
                            <li><span class="modal-highlight">진도이탈 >10%</span>: 맞춤형 보정 개입</li>
                            <li><span class="modal-highlight">달성률 <80%</span>: 미션 난이도 조정</li>
                            <li><span class="modal-highlight">사고질문 증가</span>: 도제/사고력 블록 주입</li>
                            <li><span class="modal-highlight">자율실행률 ≥70%</span>: 자율학습 권한 위임</li>
                        </ul>
                    </div>
                `
            },
            structure: {
                icon: '🏗️',
                title: 'Structure - 구조 지능',
                subtitle: '체계적인 학습 구조를 설계합니다',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">표준 변수 관리</div>
                        <ul class="modal-list">
                            <li>주차별 단원 목록 및 진도 관리</li>
                            <li>목표 숙련도 (A/B/C 등급)</li>
                            <li>선행:복습 = 7:3 비율 유지</li>
                            <li>주간 테스트 및 월간 리셋 주기</li>
                            <li>이탈 임계치 10% 설정</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">핵심 KPI</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">주간 진도달성 ≥90%</span></li>
                            <li><span class="modal-highlight">단원 마스터리 ≥80%</span></li>
                            <li><span class="modal-highlight">오답감소율 주차당 ≥20%</span></li>
                            <li><span class="modal-highlight">집중 학습시간 ≥12h/주</span></li>
                        </ul>
                    </div>
                `
            },
            procedure: {
                icon: '⚙️',
                title: 'Procedure - 절차 지능',
                subtitle: '최적화된 학습 프로세스를 운영합니다',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">4주 스프린트 루프</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">주0</span>: 진단 및 로드맵 설계</li>
                            <li><span class="modal-highlight">주1-3</span>: 주간 스프린트 실행</li>
                            <li><span class="modal-highlight">주4</span>: 통합 리뷰 및 리셋</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">매 세션 루틴</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">Before (5분)</span>: 전차시 오답 체크, 목표 명시</li>
                            <li><span class="modal-highlight">During (40분)</span>: 핵심개념 → 유형 스텝 → 미니퀴즈</li>
                            <li><span class="modal-highlight">After (15분)</span>: 백지복습, 오답 라벨링, 숙제 큐</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">시험 대비 절차</div>
                        <ul class="modal-list">
                            <li>D-30: 계획 정렬 및 우선순위 재설정</li>
                            <li>D-14: 실전세트 주 2회 실시</li>
                            <li>D-7: 파이널 리뷰 집중</li>
                            <li>D-2: 컨디션 조절 루틴</li>
                        </ul>
                    </div>
                `
            },
            execution: {
                icon: '🚀',
                title: 'Execution - 실행 지능',
                subtitle: '계획을 현실로 만드는 실행력',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">교사 체크리스트</div>
                        <ul class="modal-list">
                            <li>주차별 진도표 업데이트 및 확인</li>
                            <li>단원별 마스터리 채점 및 피드백</li>
                            <li>이탈 학생 알림 및 보정안 전달</li>
                            <li>시험모드 스위치 점검</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">학생 실행 루틴</div>
                        <ul class="modal-list">
                            <li>매일 정시 학습 습관화</li>
                            <li>주간 진도 체크 및 자가진단</li>
                            <li>7:3 선행:복습 비율 유지</li>
                            <li>월간 종합 점검 참여</li>
                            <li>번아웃 시 페이스 다운 요청</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">자동화 도구</div>
                        <ul class="modal-list">
                            <li>진도율 분석 및 로드맵 자동생성</li>
                            <li>이탈 탐지 및 리마인더 발송</li>
                            <li>출제빈도/예상문제 트래킹</li>
                            <li>사고과정 기록 및 분석</li>
                        </ul>
                    </div>
                `
            },
            reflection: {
                icon: '🔍',
                title: 'Reflection - 성찰 지능',
                subtitle: '지속적인 개선을 위한 성찰',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">주간 리플렉션 6문</div>
                        <ul class="modal-list">
                            <li>가장 많은 시간을 잡아먹은 개념/유형은? 왜?</li>
                            <li>"계획→실행" 갭 %와 원인 1가지</li>
                            <li>가장 효과적이었던 복습 방식 1개</li>
                            <li>전이된 문제/전이 실패 문제 각 1개</li>
                            <li>다음 주 제거할 활동 1개, 증가할 활동 1개</li>
                            <li>시험모드 스위치 필요성(Y/N) 및 근거</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">개선 규칙</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">갭 >20%</span>: 범위 축소, 미션 난이도 재조정</li>
                            <li><span class="modal-highlight">전이 실패 지속</span>: 도제 블록 2배 증량</li>
                            <li><span class="modal-highlight">번아웃 신호</span>: 페이스 80%로 1주 감속</li>
                        </ul>
                    </div>
                `
            },
            traffic: {
                icon: '📊',
                title: 'Traffic - 트래픽 지능',
                subtitle: '정보 흐름과 전이를 관리합니다',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">정보흐름 설계</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">월간</span>: 커리큘럼 리셋 및 대시보드</li>
                            <li><span class="modal-highlight">주간</span>: 로드맵 업데이트 및 진단</li>
                            <li><span class="modal-highlight">일간</span>: 미션 배포 및 숙제 관리</li>
                            <li><span class="modal-highlight">세션</span>: 실시간 오답 피드백</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">J-커브 대비</div>
                        <ul class="modal-list">
                            <li>D-30 시험 트래픽 폭증 대비</li>
                            <li>파이널 큐 및 오답은행 정렬</li>
                            <li>리마인더 자동화 시스템 가동</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">단절지점 탐지</div>
                        <ul class="modal-list">
                            <li>'무지 노트' 공란 지속 모니터링</li>
                            <li>보고 누락 2회 시 즉시 개입</li>
                            <li>알림 및 개입 세션 자동 스케줄링</li>
                        </ul>
                    </div>
                `
            },
            aftermath: {
                icon: '♾️',
                title: 'Aftermath - 추상화 지능',
                subtitle: '경험을 자산화하고 미래를 준비합니다',
                content: `
                    <div class="modal-section">
                        <div class="modal-section-title">분기 회고</div>
                        <ul class="modal-list">
                            <li>단원 전이 지도 재정의 (개념→유형→통합)</li>
                            <li>템플릿 및 오답태깅 규칙 표준화</li>
                            <li>학습 패턴 분석 및 최적화</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">재사용 자산</div>
                        <ul class="modal-list">
                            <li><span class="modal-highlight">파이널리뷰 리스트</span>: 핵심 개념 정리</li>
                            <li><span class="modal-highlight">기출 빈도 매트릭스</span>: 출제 경향 분석</li>
                            <li><span class="modal-highlight">학생별 UX 로그</span>: 개인화 데이터 축적</li>
                        </ul>
                    </div>
                    <div class="modal-section">
                        <div class="modal-section-title">미래 준비</div>
                        <ul class="modal-list">
                            <li>다음 분기 커리큘럼 사전 설계</li>
                            <li>학습 효율성 지표 개선 방안</li>
                            <li>개인맞춤형 모델 고도화</li>
                        </ul>
                    </div>
                `
            }
        };

        // Modal Functions
        function showIntelligenceDetail(type) {
            const modal = document.getElementById('intelligenceModal');
            const data = intelligenceData[type];
            
            if (data) {
                document.getElementById('modalIcon').textContent = data.icon;
                document.getElementById('modalTitle').textContent = data.title;
                document.getElementById('modalSubtitle').textContent = data.subtitle;
                document.getElementById('modalBody').innerHTML = data.content;
                
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal() {
            const modal = document.getElementById('intelligenceModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('intelligenceModal');
            if (e.target === modal) {
                closeModal();
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Typing effect
        const texts = [
            "매일 같은 공부법으로",
            "같은 실패를 반복하고 계신가요?",
            "",
            "이제는 달라질 때입니다."
        ];

        let textIndex = 0;
        let charIndex = 0;
        const typingElement = document.getElementById('typingText');

        function typeText() {
            if (textIndex < texts.length) {
                if (texts[textIndex] === "") {
                    setTimeout(() => {
                        textIndex++;
                        charIndex = 0;
                        typeText();
                    }, 1000);
                    return;
                }

                if (charIndex < texts[textIndex].length) {
                    typingElement.innerHTML += texts[textIndex][charIndex];
                    charIndex++;
                    setTimeout(typeText, 50);
                } else {
                    if (textIndex < texts.length - 1) {
                        setTimeout(() => {
                            typingElement.innerHTML += '<br>';
                            textIndex++;
                            charIndex = 0;
                            typeText();
                        }, 1000);
                    }
                }
            }
        }

        // Scene navigation
        function showScene(sceneNumber) {
            const scenes = document.querySelectorAll('.scene');
            
            scenes.forEach(scene => {
                scene.classList.remove('active', 'past');
            });

            const currentSceneEl = document.getElementById(`scene${sceneNumber}`);
            currentSceneEl.classList.add('active');

            // Trigger scene-specific animations
            if (sceneNumber === 2) {
                animateMilestones();
            } else if (sceneNumber === 3) {
                animateIntelligenceCards();
            } else if (sceneNumber === 4) {
                animateFeatures();
            }

            // Update progress bar
            const progress = (sceneNumber / totalScenes) * 100;
            document.getElementById('progressBar').style.width = `${progress}%`;

            // Update navigation buttons
            document.getElementById('prevBtn').disabled = sceneNumber === 1;
            document.getElementById('nextBtn').disabled = sceneNumber === totalScenes;
        }

        function nextScene() {
            if (currentScene < totalScenes) {
                document.getElementById(`scene${currentScene}`).classList.add('past');
                currentScene++;
                showScene(currentScene);
            }
        }

        function previousScene() {
            if (currentScene > 1) {
                currentScene--;
                showScene(currentScene);
            }
        }

        // Milestone animation
        function animateMilestones() {
            const milestones = document.querySelectorAll('.milestone');
            milestones.forEach(milestone => {
                const delay = parseFloat(milestone.dataset.delay) * 1000;
                setTimeout(() => {
                    milestone.style.opacity = '1';
                    milestone.style.animation = 'fadeInUp 0.8s forwards';
                }, delay);
            });
        }

        // Intelligence cards animation
        function animateIntelligenceCards() {
            const cards = document.querySelectorAll('.intelligence-card');
            cards.forEach(card => {
                const delay = parseFloat(card.dataset.delay) * 1000;
                setTimeout(() => {
                    card.classList.add('revealed');
                }, delay);
            });
        }

        // Features animation
        function animateFeatures() {
            const features = document.querySelectorAll('.feature-item');
            features.forEach(feature => {
                const delay = parseFloat(feature.dataset.delay) * 1000;
                setTimeout(() => {
                    feature.style.opacity = '1';
                    feature.style.transform = 'translateY(0)';
                    feature.style.transition = 'all 0.8s ease';
                }, delay);
            });
        }

        // Start journey function
        function startJourney() {
            alert('🎉 환영합니다! 체계적 진도형 학습이 시작됩니다.\n\n첫 주차 진단을 통해 당신만의 로드맵을 설계하겠습니다.');
        }

        // Create particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (20 + Math.random() * 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' && currentScene < totalScenes) {
                nextScene();
            } else if (e.key === 'ArrowLeft' && currentScene > 1) {
                previousScene();
            }
        });

        // Initialize
        window.onload = () => {
            createParticles();
            setTimeout(() => {
                typeText();
            }, 500);
        };
    </script>
</body>
</html>