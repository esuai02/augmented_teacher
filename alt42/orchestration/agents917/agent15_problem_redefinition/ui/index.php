<?php
/**
 * Agent 15: 문제 재정의 & 개선방안 - 통합 예제 페이지
 *
 * 이 파일은 problem_redefinition_panel.php 컴포넌트를 사용하는 예제입니다.
 * orchestration_hs2의 기능을 agent15 폴더에서 독립적으로 실행할 수 있습니다.
 *
 * 사용법:
 * 1. 카드를 클릭하면 우측 패널이 나타남
 * 2. "문제 재정의 가져오기" 버튼 클릭 시 GPT API로 자동 생성
 * 3. 생성된 내용을 수정 가능
 * 4. "저장" 버튼으로 로컬 스토리지에 저장
 *
 * File: alt42/orchestration/agents/agent15_problem_redefinition/ui/index.php
 * Error: line number in errors
 */

// Moodle 설정 불러오기
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

// 사용자 ID 설정
$userid = optional_param('userid', $USER->id, PARAM_INT);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 15: 문제 재정의 & 개선방안</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }

        /* iframe에서 사용될 때 스타일 조정 */
        body.in-iframe {
            background: white;
            padding: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 20px;
            height: calc(100vh - 40px);
        }

        .left-panel {
            flex: 0 0 300px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .right-panel {
            flex: 1;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: none; /* 기본적으로 숨김 */
        }

        .right-panel.active {
            display: block; /* 카드 클릭 시 표시 */
        }

        .card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        .card.active {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
            border: 2px solid #fff;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        h1 {
            font-size: 24px;
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 좌측 패널: 카드 목록 -->
        <div class="left-panel">
            <h1>🎯 Agent 15</h1>

            <div class="card" onclick="showRightPanel(this)">
                <div class="card-title">🔄 문제 재정의 & 개선방안</div>
                <div class="card-subtitle">GPT API로 자동 생성</div>
            </div>
        </div>

        <!-- 우측 패널: 문제 재정의 컴포넌트 -->
        <div class="right-panel" id="rightPanel">
            <?php include 'problem_redefinition_panel.php'; ?>
        </div>
    </div>

    <!-- JavaScript 함수 포함 -->
    <script>
        // currentUserId 전역 설정
        window.currentUserId = <?php echo json_encode($userid); ?>;
        console.log('[ui/index.php:139] currentUserId:', window.currentUserId);

        // iframe에서 실행 중인지 감지
        const isInIframe = window.self !== window.top;
        console.log('[ui/index.php:144] Running in iframe:', isInIframe);

        if (isInIframe) {
            // iframe 스타일 적용
            document.body.classList.add('in-iframe');

            // 좌측 패널 숨기기 (iframe에서는 우측 패널만 표시)
            const leftPanel = document.querySelector('.left-panel');
            if (leftPanel) {
                leftPanel.style.display = 'none';
            }

            // 우측 패널을 전체 화면으로 표시
            const rightPanel = document.getElementById('rightPanel');
            if (rightPanel) {
                rightPanel.style.display = 'block';
                rightPanel.style.width = '100%';
                rightPanel.style.flex = '1';
            }

            // container 스타일 조정
            const container = document.querySelector('.container');
            if (container) {
                container.style.maxWidth = '100%';
                container.style.display = 'block';
            }

            console.log('[ui/index.php:171] Iframe layout adjusted');
        }

        // iframe 부모로부터 postMessage 받기
        window.addEventListener('message', function(event) {
            console.log('[ui/index.php:176] Received postMessage:', event.data);

            if (event.data && event.data.type === 'setUserId') {
                window.currentUserId = event.data.userId;
                console.log('[ui/index.php:180] Updated currentUserId from parent:', window.currentUserId);
            }
        });
    </script>

    <script src="problem_redefinition_functions.js?v=<?php echo time(); ?>"></script>

    <script>
        /**
         * 우측 패널 표시
         * @param {HTMLElement} cardElement - 클릭된 카드 요소
         */
        function showRightPanel(cardElement) {
            console.log('카드 클릭: 우측 패널 표시');

            // 모든 카드의 active 클래스 제거
            const allCards = document.querySelectorAll('.card');
            allCards.forEach(card => card.classList.remove('active'));

            // 클릭된 카드에 active 클래스 추가
            cardElement.classList.add('active');

            // 우측 패널 표시
            const rightPanel = document.getElementById('rightPanel');
            rightPanel.classList.add('active');

            console.log('우측 패널 활성화 완료 (file: index.php, line: 147)');
        }
    </script>
</body>
</html>
