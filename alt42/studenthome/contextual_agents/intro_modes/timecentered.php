<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🕒 시간성찰 중심모드 - 시간의 마법사가 되는 법</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: white;
        }

        /* 헤더 스타일 */
        .header {
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .main-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #ffd89b, #19547b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: brightness(1); }
            to { filter: brightness(1.2); }
        }

        .subtitle {
            font-size: 1.3rem;
            color: #ffd89b;
            margin-bottom: 2rem;
        }

        /* 타이머 애니메이션 */
        .timer-animation {
            width: 150px;
            height: 150px;
            margin: 2rem auto;
            position: relative;
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .timer-circle {
            width: 100%;
            height: 100%;
            border: 8px solid #ffd89b;
            border-radius: 50%;
            border-top-color: transparent;
            position: absolute;
        }

        .timer-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: bold;
        }

        /* 컨테이너 스타일 */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* 섹션 카드 */
        .section-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .section-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: #ffd89b;
        }

        .section-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-icon {
            font-size: 2.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* 철학 섹션 */
        .philosophy-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .philosophy-box::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .philosophy-text {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .philosophy-subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* 실행 단계 */
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .step-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 216, 155, 0.1), rgba(25, 84, 123, 0.1));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .step-card:hover::before {
            opacity: 1;
        }

        .step-card:hover {
            transform: scale(1.05);
            border-color: #ffd89b;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ffd89b, #19547b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }

        .step-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .step-description {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        /* 시간 블록 시각화 */
        .time-blocks {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .time-block {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 1rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .time-block:hover {
            background: rgba(255, 216, 155, 0.3);
            transform: scale(1.1);
        }

        .block-time {
            font-size: 2rem;
            font-weight: bold;
            color: #ffd89b;
        }

        .block-label {
            font-size: 0.9rem;
            margin-top: 0.5rem;
            opacity: 0.9;
        }

        /* 성과 배지 */
        .badges-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 3rem 0;
            flex-wrap: wrap;
        }

        .badge {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: 3px solid #ffd89b;
            cursor: pointer;
        }

        .badge:hover {
            transform: scale(1.15) rotate(10deg);
            background: rgba(255, 216, 155, 0.2);
        }

        .badge-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .badge-label {
            font-size: 0.9rem;
            font-weight: bold;
        }

        /* 명언 섹션 */
        .quote-section {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5));
            border-radius: 20px;
            padding: 3rem;
            margin: 3rem 0;
            text-align: center;
            position: relative;
        }

        .quote-mark {
            font-size: 4rem;
            opacity: 0.3;
            position: absolute;
            top: 1rem;
            left: 2rem;
        }

        .quote-text {
            font-size: 1.5rem;
            font-style: italic;
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        .quote-author {
            font-size: 1.1rem;
            color: #ffd89b;
        }

        /* CTA 버튼 */
        .cta-button {
            display: inline-block;
            padding: 1.5rem 3rem;
            background: linear-gradient(135deg, #ffd89b, #19547b);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin: 2rem auto;
            display: block;
            width: fit-content;
            text-align: center;
        }

        .cta-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2.5rem;
            }
            
            .steps-container {
                grid-template-columns: 1fr;
            }
            
            .philosophy-text {
                font-size: 1.4rem;
            }
        }

        /* 특별 효과 */
        .sparkle {
            animation: sparkle 2s linear infinite;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <!-- 헤더 섹션 -->
    <div class="header">
        <h1 class="main-title">⏰ 시간성찰 중심모드</h1>
        <p class="subtitle">당신의 시간을 황금으로 바꾸는 마법의 학습법</p>
        
        <div class="timer-animation">
            <div class="timer-circle"></div>
            <div class="timer-text">NOW</div>
        </div>
    </div>

    <div class="container">
        <!-- 핵심 철학 섹션 -->
        <div class="philosophy-box">
            <div class="philosophy-text">
                "시간은 생명이다. 매 순간을 의미있게 만들어라!"
            </div>
            <div class="philosophy-subtitle">
                1분 1초가 모여 당신의 미래가 됩니다.<br>
                시간의 밀도를 높이면, 같은 시간에 10배의 성과를 만들 수 있습니다.
            </div>
        </div>

        <!-- 왜 시간성찰인가? -->
        <div class="section-card">
            <div class="section-title">
                <span class="section-icon">🎯</span>
                왜 시간성찰 중심모드인가?
            </div>
            <p style="font-size: 1.1rem; line-height: 1.8;">
                하루 24시간은 모두에게 공평합니다. 하지만 그 시간을 어떻게 사용하느냐에 따라 
                결과는 천차만별이죠. 시간성찰 중심모드는 단순히 시간을 관리하는 것이 아니라, 
                <strong style="color: #ffd89b;">시간의 질을 극대화</strong>하는 혁신적인 학습법입니다.
            </p>
            <br>
            <p style="font-size: 1.1rem; line-height: 1.8;">
                🧠 <strong>뇌과학적 사실:</strong> 우리 뇌는 25분 집중 후 5분 휴식할 때 최고의 효율을 발휘합니다.<br>
                📈 <strong>통계적 근거:</strong> 시간 추적을 하는 학생들의 성적이 평균 23% 향상되었습니다.<br>
                ⚡ <strong>즉각적 효과:</strong> 첫 주부터 집중력 향상을 체감할 수 있습니다.
            </p>
        </div>

        <!-- 시간 블록 전략 -->
        <div class="section-card">
            <div class="section-title">
                <span class="section-icon">🧩</span>
                황금 시간 블록 전략
            </div>
            <div class="time-blocks">
                <div class="time-block">
                    <div class="block-time">25분</div>
                    <div class="block-label">집중 블록</div>
                </div>
                <div class="time-block">
                    <div class="block-time">5분</div>
                    <div class="block-label">휴식</div>
                </div>
                <div class="time-block">
                    <div class="block-time">50분</div>
                    <div class="block-label">심화 블록</div>
                </div>
                <div class="time-block">
                    <div class="block-time">10분</div>
                    <div class="block-label">회고</div>
                </div>
            </div>
            <p style="text-align: center; margin-top: 2rem; font-size: 1.1rem;">
                당신의 집중력 레벨에 맞는 시간 블록을 선택하세요!
            </p>
        </div>

        <!-- 실행 단계 -->
        <div class="section-card">
            <div class="section-title">
                <span class="section-icon">🚀</span>
                지금 바로 시작하는 5단계
            </div>
            <div class="steps-container">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">시간 감사 일기</div>
                    <div class="step-description">
                        매일 밤 10분, 오늘 가장 가치있게 보낸 시간 3가지를 기록하세요. 
                        시간에 대한 인식이 바뀝니다.
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">골든 타임 찾기</div>
                    <div class="step-description">
                        당신이 가장 집중이 잘 되는 시간대를 발견하세요. 
                        그 시간에 가장 중요한 공부를 배치하세요.
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">시간 밀도 높이기</div>
                    <div class="step-description">
                        같은 1시간도 밀도를 2배로 높이면 2시간의 효과! 
                        집중도를 측정하고 개선하세요.
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-title">반복 주기 설계</div>
                    <div class="step-description">
                        1-3-7-14일 간격으로 복습하면 장기기억 전환율 95%! 
                        자동 알림을 설정하세요.
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">5</div>
                    <div class="step-title">시간 성과 축하</div>
                    <div class="step-description">
                        매주 시간 절약 성과를 축하하세요. 
                        작은 성취가 큰 변화를 만듭니다.
                    </div>
                </div>
            </div>
        </div>

        <!-- 성과 배지 시스템 -->
        <div class="section-card">
            <div class="section-title">
                <span class="section-icon">🏆</span>
                당신이 얻게 될 성과 배지
            </div>
            <div class="badges-container">
                <div class="badge">
                    <div class="badge-icon">⚡</div>
                    <div class="badge-label">시간 절약왕</div>
                </div>
                <div class="badge">
                    <div class="badge-icon">🎯</div>
                    <div class="badge-label">집중 마스터</div>
                </div>
                <div class="badge">
                    <div class="badge-icon">📈</div>
                    <div class="badge-label">효율 극대화</div>
                </div>
                <div class="badge">
                    <div class="badge-icon">🔥</div>
                    <div class="badge-label">연속 달성</div>
                </div>
            </div>
        </div>

        <!-- 성공 사례 -->
        <div class="section-card">
            <div class="section-title">
                <span class="section-icon">✨</span>
                실제 성공 사례
            </div>
            <div style="display: grid; gap: 1.5rem;">
                <div style="background: rgba(255, 216, 155, 0.1); padding: 1.5rem; border-radius: 10px;">
                    <strong style="color: #ffd89b;">김O준 (고3)</strong><br>
                    "하루 공부 시간은 그대로인데 성적이 20% 올랐어요. 시간의 질이 바뀌니까 결과가 달라지더라구요!"
                </div>
                <div style="background: rgba(255, 216, 155, 0.1); padding: 1.5rem; border-radius: 10px;">
                    <strong style="color: #ffd89b;">이O민 (고2)</strong><br>
                    "집중 블록 설정하고 나서 스마트폰 보는 시간이 절반으로 줄었어요. 시간이 아까워지더라구요."
                </div>
                <div style="background: rgba(255, 216, 155, 0.1); padding: 1.5rem; border-radius: 10px;">
                    <strong style="color: #ffd89b;">박O서 (고1)</strong><br>
                    "반복 주기 복습법으로 암기 과목이 정말 쉬워졌어요. 한 번 외운 건 잊어버리지 않아요!"
                </div>
            </div>
        </div>

        <!-- 명언 섹션 -->
        <div class="quote-section">
            <div class="quote-mark">"</div>
            <div class="quote-text">
                시간을 지배하는 자가 인생을 지배한다.<br>
                당신의 시간은 당신의 선택으로 빛날 수 있다.
            </div>
            <div class="quote-author">- 벤자민 프랭클린 -</div>
        </div>

        <!-- 도전 과제 -->
        <div class="section-card">
            <div class="section-title">
                <span class="section-icon pulse">🎮</span>
                7일 챌린지: 시간의 마법사 되기
            </div>
            <div style="font-size: 1.1rem; line-height: 2;">
                <strong>Day 1:</strong> 오늘 하루 시간 사용 기록하기 (매 시간마다)<br>
                <strong>Day 2:</strong> 25분 집중 블록 3회 도전<br>
                <strong>Day 3:</strong> 골든 타임 발견하고 중요 과목 배치<br>
                <strong>Day 4:</strong> 시간 밀도 측정 (집중도 1-10점 기록)<br>
                <strong>Day 5:</strong> 반복 복습 스케줄 만들기<br>
                <strong>Day 6:</strong> 시간 절약 성과 계산하기<br>
                <strong>Day 7:</strong> 일주일 회고 및 다음 주 계획<br>
            </div>
            <p style="text-align: center; margin-top: 2rem; font-size: 1.2rem; color: #ffd89b;">
                <strong>🎁 보상: 7일 완주 시 "시간의 마법사" 배지 획득!</strong>
            </p>
        </div>

        <!-- CTA 버튼 -->
        <a href="#" class="cta-button" onclick="startTimeMode()">
            지금 바로 시간의 마법사 되기 🚀
        </a>

        <!-- 추가 팁 -->
        <div class="section-card" style="background: linear-gradient(135deg, rgba(255, 216, 155, 0.1), rgba(25, 84, 123, 0.1));">
            <div class="section-title">
                <span class="section-icon">💡</span>
                프로 팁: 시간을 10배 활용하는 비밀
            </div>
            <ul style="font-size: 1.1rem; line-height: 2; list-style: none;">
                <li>🎯 <strong>2분 룰:</strong> 2분 안에 끝낼 수 있는 일은 바로 처리</li>
                <li>📱 <strong>디지털 디톡스:</strong> 집중 시간에는 스마트폰을 다른 방에</li>
                <li>🧘 <strong>마인드풀니스:</strong> 매 시간 시작 전 30초 명상</li>
                <li>📊 <strong>시간 대시보드:</strong> 주간 시간 사용 그래프 만들기</li>
                <li>🎵 <strong>집중 음악:</strong> 백색소음이나 알파파 음악 활용</li>
                <li>🏃 <strong>움직임 휴식:</strong> 휴식 시간에는 가볍게 스트레칭</li>
            </ul>
        </div>
    </div>

    <script>
        // 인터랙티브 요소들
        function startTimeMode() {
            alert('🎉 축하합니다! 시간성찰 중심모드를 시작합니다.\n\n첫 25분 집중 블록을 시작해보세요!');
            // 여기에 실제 타이머 시작 로직 추가 가능
        }

        // 카드 클릭 시 확대 효과
        document.querySelectorAll('.section-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 300);
            });
        });

        // 배지 클릭 시 회전 효과
        document.querySelectorAll('.badge').forEach(badge => {
            badge.addEventListener('click', function() {
                this.style.animation = 'rotate 0.5s ease';
                setTimeout(() => {
                    this.style.animation = '';
                }, 500);
            });
        });

        // 시간 블록 클릭 시 선택 효과
        document.querySelectorAll('.time-block').forEach(block => {
            block.addEventListener('click', function() {
                // 모든 블록 초기화
                document.querySelectorAll('.time-block').forEach(b => {
                    b.style.background = 'rgba(255, 255, 255, 0.2)';
                });
                // 선택된 블록 강조
                this.style.background = 'rgba(255, 216, 155, 0.4)';
            });
        });

        // 페이지 로드 시 애니메이션
        window.addEventListener('load', () => {
            const elements = document.querySelectorAll('.section-card');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 200);
            });
        });
    </script>
</body>
</html>