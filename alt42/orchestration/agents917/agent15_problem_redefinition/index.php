<?php
/**
 * Agent 15 Problem Redefinition - Main UI
 * File: agents/agent15_problem_redefinition/index.php:1
 *
 * 문제 재정의 & 개선방안 메인 페이지
 * 우측 패널에 iframe으로 문제 재정의 UI 표시
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 학생 ID 가져오기
$studentid = $_GET["userid"] ?? $USER->id;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 15 - 문제 재정의 & 개선방안</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #1e293b;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #64748b;
            font-size: 16px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            height: calc(100vh - 220px);
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        .left-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .left-panel h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
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
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .card.active {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            border: 2px solid #fff;
        }

        .card-icon {
            font-size: 32px;
            margin-bottom: 10px;
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

        .right-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            display: none;
        }

        .right-panel.active {
            display: block;
        }

        .panel-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h2 {
            font-size: 20px;
            margin: 0;
        }

        .close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
        }

        .close-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .iframe-container {
            width: 100%;
            height: calc(100% - 70px);
            position: relative;
        }

        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .info-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #495057;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <h1>🔄 Agent 15 - 문제 재정의 & 개선방안</h1>
            <p>GPT 기반 자동 문제 재정의 및 개선방안 생성 시스템</p>
        </div>

        <div class="main-content">
            <!-- 좌측 패널: 기능 카드 -->
            <div class="left-panel">
                <h2>📋 기능 선택</h2>

                <div class="info-box">
                    <h3>ℹ️ 사용 방법</h3>
                    <p>아래 카드를 클릭하면 우측 패널에 해당 기능이 표시됩니다.</p>
                </div>

                <div class="card" onclick="showPanel('problem-redefinition')">
                    <div class="card-icon">📊</div>
                    <div class="card-title">문제 재정의 & 개선방안</div>
                    <div class="card-subtitle">GPT API로 자동 생성</div>
                </div>

                <div class="info-box" style="margin-top: 20px;">
                    <h3>📌 현재 학생 정보</h3>
                    <p><strong>Student ID:</strong> <?php echo $studentid; ?></p>
                    <p><strong>User ID:</strong> <?php echo $USER->id; ?></p>
                </div>
            </div>

            <!-- 우측 패널: iframe -->
            <div class="right-panel" id="rightPanel">
                <div class="panel-header">
                    <h2 id="panelTitle">문제 재정의 & 개선방안</h2>
                    <button class="close-btn" onclick="closePanel()" title="닫기">×</button>
                </div>
                <div class="iframe-container">
                    <iframe
                        id="contentFrame"
                        src=""
                        frameborder="0"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Agent 15 Main Script
         * @file index.php:224
         */

        const studentId = <?php echo $studentid; ?>;
        const userId = <?php echo $USER->id; ?>;

        console.log('[index.php:229] Student ID:', studentId);
        console.log('[index.php:230] User ID:', userId);

        /**
         * 우측 패널 표시
         * @param {string} panelType 패널 타입
         */
        function showPanel(panelType) {
            console.log('[index.php:238] showPanel called:', panelType);

            const rightPanel = document.getElementById('rightPanel');
            const iframe = document.getElementById('contentFrame');
            const panelTitle = document.getElementById('panelTitle');

            // 모든 카드의 active 클래스 제거
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => card.classList.remove('active'));

            // 클릭된 카드에 active 클래스 추가
            event.currentTarget.classList.add('active');

            // 패널 타입에 따라 iframe src 설정
            let iframeSrc = '';
            let title = '';

            switch(panelType) {
                case 'problem-redefinition':
                    iframeSrc = `ui/index.php?userid=${studentId}`;
                    title = '📊 문제 재정의 & 개선방안';
                    break;
                default:
                    console.error('[index.php:261] Unknown panel type:', panelType);
                    return;
            }

            // iframe src 설정
            iframe.src = iframeSrc;
            panelTitle.textContent = title;

            // 우측 패널 표시
            rightPanel.classList.add('active');

            console.log('[index.php:272] Panel shown:', {
                panelType: panelType,
                iframeSrc: iframeSrc,
                title: title
            });
        }

        /**
         * 우측 패널 닫기
         */
        function closePanel() {
            console.log('[index.php:283] closePanel called');

            const rightPanel = document.getElementById('rightPanel');
            const iframe = document.getElementById('contentFrame');

            // 패널 숨기기
            rightPanel.classList.remove('active');

            // iframe src 초기화 (리소스 절약)
            iframe.src = '';

            // 모든 카드의 active 클래스 제거
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => card.classList.remove('active'));

            console.log('[index.php:298] Panel closed');
        }

        /**
         * iframe 로드 완료 이벤트
         */
        document.getElementById('contentFrame').addEventListener('load', function() {
            console.log('[index.php:305] Iframe loaded:', this.src);

            // iframe에 currentUserId 전달 (postMessage 사용)
            try {
                this.contentWindow.postMessage({
                    type: 'setUserId',
                    userId: studentId
                }, '*');
                console.log('[index.php:313] Posted userId to iframe:', studentId);
            } catch (e) {
                console.error('[index.php:315] Failed to post message to iframe:', e);
            }
        });

        /**
         * ESC 키로 패널 닫기
         */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const rightPanel = document.getElementById('rightPanel');
                if (rightPanel.classList.contains('active')) {
                    closePanel();
                    console.log('[index.php:328] Panel closed by ESC key');
                }
            }
        });
    </script>
</body>
</html>

<!--
Database Tables Used:
- mdl_user: 사용자 기본 정보
  Fields: id, firstname, lastname, email
- mdl_alt42_workflow_data: 워크플로우 데이터 (Step 2-14)
  Fields: userid, step, data, created_at
-->
