<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>시간 피드백 중심형 - Welcome Session</title>
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
            overflow: hidden;
            position: relative;
        }

        /* 배경 시계 애니메이션 */
        .bg-clock {
            position: fixed;
            width: 500px;
            height: 500px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.03;
            animation: rotateClock 60s linear infinite;
        }

        @keyframes rotateClock {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .story-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px;
            max-width: 800px;
            width: 90%;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 타이핑 효과 */
        .typing-text {
            overflow: hidden;
            white-space: pre-wrap;
            animation: typing 2s steps(40, end);
            letter-spacing: 0.05em;
            line-height: 1.8;
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        .chapter {
            display: none;
            animation: chapterFade 0.8s ease-in-out;
        }

        .chapter.active {
            display: block;
        }

        @keyframes chapterFade {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .time-visual {
            width: 200px;
            height: 200px;
            margin: 30px auto;
            position: relative;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .clock-face {
            width: 100%;
            height: 100%;
            border: 8px solid #667eea;
            border-radius: 50%;
            position: relative;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }

        .clock-hand {
            position: absolute;
            width: 4px;
            height: 60px;
            background: #764ba2;
            left: 50%;
            top: 50%;
            transform-origin: bottom center;
            transform: translateX(-50%) translateY(-100%) rotate(0deg);
            animation: rotateHand 4s ease-in-out infinite;
        }

        @keyframes rotateHand {
            0% { transform: translateX(-50%) translateY(-100%) rotate(0deg); }
            100% { transform: translateX(-50%) translateY(-100%) rotate(360deg); }
        }

        .focus-block {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            margin: 5px;
            animation: blockPulse 1.5s ease-in-out infinite;
            font-weight: bold;
        }

        @keyframes blockPulse {
            0%, 100% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        .time-flow {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            padding: 20px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 20px;
        }

        .time-node {
            text-align: center;
            opacity: 0;
            animation: nodeAppear 0.5s forwards;
        }

        .time-node:nth-child(1) { animation-delay: 0.2s; }
        .time-node:nth-child(2) { animation-delay: 0.4s; }
        .time-node:nth-child(3) { animation-delay: 0.6s; }
        .time-node:nth-child(4) { animation-delay: 0.8s; }

        @keyframes nodeAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
            from {
                opacity: 0;
                transform: translateY(20px);
            }
        }

        .time-node .day {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .time-node .label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .btn-next {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1em;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.5s ease;
        }

        .highlight {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            padding: 2px 8px;
            border-radius: 8px;
            display: inline-block;
            animation: highlightPulse 2s ease-in-out infinite;
        }

        @keyframes highlightPulse {
            0%, 100% { background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2)); }
            50% { background: linear-gradient(135deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3)); }
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            opacity: 0;
            animation: slideIn 0.8s forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .metric-icon {
            font-size: 2.5em;
        }

        .metric-content h3 {
            color: #667eea;
            margin-bottom: 5px;
        }

        .final-message {
            text-align: center;
            padding: 40px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 20px;
            margin-top: 30px;
        }

        .sparkle {
            display: inline-block;
            animation: sparkle 1.5s ease-in-out infinite;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        /* 파티클 효과 */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #667eea;
            border-radius: 50%;
            animation: particleFloat 6s ease-in-out infinite;
        }

        @keyframes particleFloat {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(50px);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- 배경 시계 -->
    <svg class="bg-clock" viewBox="0 0 200 200">
        <circle cx="100" cy="100" r="95" fill="none" stroke="white" stroke-width="2"/>
        <line x1="100" y1="100" x2="100" y2="30" stroke="white" stroke-width="3"/>
        <line x1="100" y1="100" x2="150" y2="100" stroke="white" stroke-width="2"/>
    </svg>

    <!-- 파티클 효과 -->
    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 30%; animation-delay: 1s;"></div>
    <div class="particle" style="left: 50%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 70%; animation-delay: 3s;"></div>
    <div class="particle" style="left: 90%; animation-delay: 4s;"></div>

    <div class="container">
        <div class="story-card">
            <div class="progress-bar" id="progress"></div>
            
            <!-- Chapter 1: 인트로 -->
            <div class="chapter active" id="chapter1">
                <h1>⏰ 시간의 마법사가 되어보세요</h1>
                <p class="typing-text" style="font-size: 1.2em; color: #555; margin-bottom: 20px;">
                    당신이 선택한 <span class="highlight">시간 피드백 중심형</span> 학습법...
                </p>
                <div class="time-visual">
                    <div class="clock-face">
                        <div class="clock-hand"></div>
                    </div>
                </div>
                <p style="font-size: 1.1em; line-height: 1.8; color: #666;">
                    누군가는 하루 24시간으로 기적을 만들고,<br>
                    누군가는 같은 시간을 흘려보냅니다.<br><br>
                    <strong style="color: #667eea;">차이는 단 하나.</strong><br>
                    시간을 '관리'하는가, '설계'하는가의 차이입니다.
                </p>
                <button class="btn-next" onclick="nextChapter(2)">시간의 비밀 알아보기 →</button>
            </div>

            <!-- Chapter 2: 핵심 철학 -->
            <div class="chapter" id="chapter2">
                <h1>🧠 당신의 뇌는 골든타임을 기다립니다</h1>
                <p style="font-size: 1.15em; color: #555; margin-bottom: 30px;">
                    <span class="sparkle">✨</span> <strong>"시간은 학습의 생명선이자 성과의 가속기"</strong>
                </p>
                
                <div class="metric-card" style="animation-delay: 0.2s;">
                    <div class="metric-icon">🎯</div>
                    <div class="metric-content">
                        <h3>집중 블록 설계</h3>
                        <p>25분 집중 + 5분 휴식의 황금비율로<br>당신의 뇌를 최적 상태로 유지합니다</p>
                    </div>
                </div>

                <div class="metric-card" style="animation-delay: 0.4s;">
                    <div class="metric-icon">📊</div>
                    <div class="metric-content">
                        <h3>시간 밀도 지수</h3>
                        <p>같은 1시간도 0.8배 더 밀도있게<br>압축적 학습으로 효율을 극대화합니다</p>
                    </div>
                </div>

                <div class="metric-card" style="animation-delay: 0.6s;">
                    <div class="metric-icon">🔄</div>
                    <div class="metric-content">
                        <h3>과학적 반복 주기</h3>
                        <p>1-3-7-14일 간격의 복습으로<br>장기기억 전환율 85% 달성</p>
                    </div>
                </div>

                <button class="btn-next" onclick="nextChapter(3)">나만의 시간 설계 보기 →</button>
            </div>

            <!-- Chapter 3: 반복 간격 시스템 -->
            <div class="chapter" id="chapter3">
                <h1>📈 망각곡선을 이기는 비밀</h1>
                <p style="font-size: 1.1em; color: #666; margin-bottom: 30px;">
                    에빙하우스의 망각곡선을 역이용한 <strong style="color: #667eea;">최적 반복 시스템</strong>
                </p>
                
                <div class="time-flow">
                    <div class="time-node">
                        <div class="day">1일</div>
                        <div class="label">즉시복습</div>
                    </div>
                    <div class="time-node">
                        <div class="day">3일</div>
                        <div class="label">단기강화</div>
                    </div>
                    <div class="time-node">
                        <div class="day">7일</div>
                        <div class="label">중기정착</div>
                    </div>
                    <div class="time-node">
                        <div class="day">14일</div>
                        <div class="label">장기전환</div>
                    </div>
                </div>

                <div style="background: rgba(102, 126, 234, 0.05); padding: 25px; border-radius: 15px; margin: 20px 0;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">🎯 당신만의 시간 블록 예시</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <span class="focus-block">📚 수학 25분</span>
                        <span class="focus-block">☕ 휴식 5분</span>
                        <span class="focus-block">📖 영어 25분</span>
                        <span class="focus-block">☕ 휴식 5분</span>
                        <span class="focus-block">🧪 과학 25분</span>
                    </div>
                    <p style="margin-top: 15px; color: #666;">
                        <strong>시간당 성취율:</strong> 18문항 / <strong>정답률:</strong> 85% / <strong>밀도지수:</strong> 0.82
                    </p>
                </div>

                <button class="btn-next" onclick="nextChapter(4)">실행 시스템 확인하기 →</button>
            </div>

            <!-- Chapter 4: 실행 시스템 -->
            <div class="chapter" id="chapter4">
                <h1>⚙️ 당신을 위한 자동화 시스템</h1>
                
                <div style="display: grid; gap: 20px; margin: 30px 0;">
                    <div style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 20px; border-radius: 15px;">
                        <h3 style="color: #667eea; margin-bottom: 10px;">📱 실시간 추적</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 5px 0;">✓ 집중 블록 타이머 자동 설정</li>
                            <li style="padding: 5px 0;">✓ 시간당 문항수 실시간 계산</li>
                            <li style="padding: 5px 0;">✓ 효율 저하시 즉시 알림</li>
                        </ul>
                    </div>

                    <div style="background: linear-gradient(135deg, rgba(118, 75, 162, 0.1), rgba(102, 126, 234, 0.1)); padding: 20px; border-radius: 15px;">
                        <h3 style="color: #764ba2; margin-bottom: 10px;">📊 스마트 분석</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 5px 0;">✓ 주간 시간 밀도 리포트</li>
                            <li style="padding: 5px 0;">✓ 과목별 최적 집중시간 분석</li>
                            <li style="padding: 5px 0;">✓ 개인 맞춤 휴식 패턴 제안</li>
                        </ul>
                    </div>

                    <div style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 20px; border-radius: 15px;">
                        <h3 style="color: #667eea; margin-bottom: 10px;">🔔 복습 큐 시스템</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 5px 0;">✓ 1-3-7-14일 자동 알림</li>
                            <li style="padding: 5px 0;">✓ 복습 미완료시 재스케줄링</li>
                            <li style="padding: 5px 0;">✓ 기억 유지율 추적</li>
                        </ul>
                    </div>
                </div>

                <button class="btn-next" onclick="nextChapter(5)">나의 성장 곡선 보기 →</button>
            </div>

            <!-- Chapter 5: 성장 비전 -->
            <div class="chapter" id="chapter5">
                <h1>🚀 당신의 J-커브가 시작됩니다</h1>
                
                <div style="text-align: center; margin: 30px 0;">
                    <svg width="300" height="200" viewBox="0 0 300 200">
                        <path d="M 20 180 Q 100 170 150 140 T 280 20" 
                              stroke="#667eea" stroke-width="3" fill="none"
                              stroke-dasharray="500"
                              stroke-dashoffset="500">
                            <animate attributeName="stroke-dashoffset" 
                                     values="500;0" dur="2s" 
                                     begin="0s" fill="freeze"/>
                        </path>
                        <text x="150" y="195" text-anchor="middle" fill="#666" font-size="14">시간 (주)</text>
                        <text x="10" y="100" fill="#666" font-size="14" transform="rotate(-90 10 100)">성취도</text>
                    </svg>
                </div>

                <div class="final-message">
                    <h2 style="color: #667eea; margin-bottom: 20px;">
                        <span class="sparkle">🎯</span> 2주 후 당신의 변화
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; text-align: left; max-width: 500px; margin: 0 auto;">
                        <div style="padding: 10px;">
                            <strong style="color: #764ba2;">집중 지속 시간</strong><br>
                            15분 → 50분
                        </div>
                        <div style="padding: 10px;">
                            <strong style="color: #764ba2;">시간당 문항수</strong><br>
                            10개 → 25개
                        </div>
                        <div style="padding: 10px;">
                            <strong style="color: #764ba2;">기억 유지율</strong><br>
                            40% → 85%
                        </div>
                        <div style="padding: 10px;">
                            <strong style="color: #764ba2;">학습 효율</strong><br>
                            0.5 → 0.9
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <p style="font-size: 1.3em; color: #667eea; font-weight: bold; margin-bottom: 20px;">
                        "시간을 지배하는 자가 성적을 지배한다"
                    </p>
                    <button class="btn-next" style="background: linear-gradient(135deg, #ff6b6b, #ffd93d); font-size: 1.2em; padding: 18px 50px;"
                            onclick="startJourney()">
                        🚀 지금 시작하기
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        let currentChapter = 1;
        const totalChapters = 5;

        function nextChapter(chapter) {
            // 현재 챕터 숨기기
            document.getElementById(`chapter${currentChapter}`).classList.remove('active');
            
            // 다음 챕터 표시
            setTimeout(() => {
                document.getElementById(`chapter${chapter}`).classList.add('active');
                currentChapter = chapter;
                
                // 프로그레스 바 업데이트
                const progress = (chapter / totalChapters) * 100;
                document.getElementById('progress').style.width = `${progress}%`;
                
                // 챕터별 특수 효과
                if (chapter === 3) {
                    animateTimeNodes();
                } else if (chapter === 5) {
                    animateGrowthCurve();
                }
            }, 300);
        }

        function animateTimeNodes() {
            const nodes = document.querySelectorAll('.time-node');
            nodes.forEach((node, index) => {
                setTimeout(() => {
                    node.style.animation = 'nodeAppear 0.5s forwards';
                }, index * 200);
            });
        }

        function animateGrowthCurve() {
            // SVG 애니메이션은 CSS로 처리됨
        }

        function startJourney() {
            // 화려한 종료 애니메이션
            document.querySelector('.story-card').style.animation = 'fadeOutScale 1s forwards';
            
            setTimeout(() => {
                // 시작 메시지
                document.body.innerHTML = `
                    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
                        <h1 style="color: white; font-size: 3em; animation: fadeInUp 1s;">
                            🕒 시간 마스터의 여정이 시작됩니다
                        </h1>
                        <p style="color: rgba(255,255,255,0.8); font-size: 1.5em; margin-top: 20px; animation: fadeInUp 1s 0.3s both;">
                            첫 번째 25분 집중 블록을 시작해보세요!
                        </p>
                    </div>
                `;
            }, 1000);
        }

        // 초기 프로그레스 설정
        document.getElementById('progress').style.width = '20%';

        // 타이핑 효과 재생성
        document.querySelectorAll('.typing-text').forEach(el => {
            const text = el.textContent;
            el.textContent = '';
            let i = 0;
            const typing = setInterval(() => {
                if (i < text.length) {
                    el.textContent += text.charAt(i);
                    i++;
                } else {
                    clearInterval(typing);
                }
            }, 30);
        });

        // 추가 CSS 애니메이션
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOutScale {
                to {
                    opacity: 0;
                    transform: scale(0.9);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>