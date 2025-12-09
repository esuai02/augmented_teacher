<?php
/**
 * Agent 16 Interaction Preparation - Integration Entry Point
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/index.php
 *
 * Purpose: Standalone page for testing Agent 16 panel
 * Integration: This file loads the panel UI and can be embedded in main orchestration
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get student ID
$studentid = $_GET["userid"] ?? $USER->id;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 16 - 상호작용 준비</title>

    <!-- Agent 16 Panel Stylesheet -->
    <link rel="stylesheet" href="ui/panel.css?v=<?php echo time(); ?>">

    <style>
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .demo-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .demo-header h1 {
            color: #1e293b;
            font-size: 32px;
            margin: 0 0 10px 0;
        }

        .demo-header p {
            color: #64748b;
            font-size: 16px;
            margin: 0;
        }

        .demo-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .demo-card h2 {
            color: #1e293b;
            font-size: 24px;
            margin: 0 0 20px 0;
        }

        .demo-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .demo-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .info-section {
            margin-top: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }

        .info-section h3 {
            color: #1e293b;
            font-size: 18px;
            margin: 0 0 12px 0;
        }

        .info-section ul {
            margin: 0;
            padding-left: 24px;
            color: #475569;
            line-height: 1.8;
        }

        .api-status {
            margin-top: 20px;
            padding: 16px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            color: #856404;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>🎯 Agent 16 - 상호작용 준비</h1>
            <p>학생별 맞춤형 상호작용 모드 선택 및 시나리오 생성 시스템</p>
        </div>

        <div class="demo-card">
            <h2>패널 열기</h2>
            <p style="color: #64748b; margin-bottom: 24px;">
                아래 버튼을 클릭하여 상호작용 준비 패널을 열어보세요.
            </p>
            <button class="demo-button" onclick="openPanel()">
                📋 상호작용 준비 패널 열기
            </button>

            <div class="info-section">
                <h3>패널 기능</h3>
                <ul>
                    <li><strong>상호작용 모드</strong>: 9가지 학습 가이드 모드 선택 (커리큘럼, 맞춤학습, 시험대비 등)</li>
                    <li><strong>시나리오 생성</strong>: GPT-4o API를 활용한 맞춤형 시나리오 생성</li>
                    <li><strong>생성 결과</strong>: 저장된 시나리오 목록 조회, 복사, 삭제</li>
                </ul>
            </div>

            <div class="api-status">
                <strong>⚠️ 참고:</strong> GPT API가 설정되지 않은 경우 폴백 시나리오가 생성됩니다.
                <br>
                완전한 기능을 위해서는 관리자 설정에서 GPT API 키를 설정해주세요.
            </div>
        </div>
    </div>

    <!-- JavaScript - Set current user ID -->
    <script>
        window.currentUserId = <?php echo json_encode($studentid); ?>;
        console.log('Current User ID:', window.currentUserId);
    </script>

    <!-- Agent 16 Panel Script -->
    <script src="ui/panel.js?v=<?php echo time(); ?>"></script>

    <!-- Demo Functions -->
    <script>
        function openPanel() {
            if (typeof InteractionPreparationPanel !== 'undefined') {
                InteractionPreparationPanel.open(window.currentUserId);
                console.log('✅ Panel opened for user:', window.currentUserId);
            } else {
                console.error('❌ InteractionPreparationPanel not loaded');
                alert('패널을 로드하는데 실패했습니다. 페이지를 새로고침 해주세요.');
            }
        }

        // Log panel status on load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Agent 16 Interaction Preparation Panel Demo');
            console.log('Panel loaded:', typeof InteractionPreparationPanel !== 'undefined');
            console.log('User ID:', window.currentUserId);
        });
    </script>
</body>
</html>
