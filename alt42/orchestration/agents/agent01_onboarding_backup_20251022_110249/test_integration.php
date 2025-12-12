<?php
/**
 * Integration Test Page
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/test_integration.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Agent01 Panel Integration Test</title>
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 20px;
        }
        .user-info {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .user-info p {
            margin: 8px 0;
            color: #374151;
        }
        .test-card {
            width: 250px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            color: white;
            margin: 0 auto;
        }
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .test-card h3 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }
        .test-card p {
            margin: 5px 0;
            opacity: 0.9;
        }
        .test-card small {
            opacity: 0.7;
        }
        .instructions {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .instructions h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
            color: #374151;
        }
        .instructions li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Agent01 Onboarding Panel Test</h1>

        <div class="user-info">
            <p><strong>Current User ID:</strong> <?php echo $USER->id; ?></p>
            <p><strong>Name:</strong> <?php echo $USER->firstname . ' ' . $USER->lastname; ?></p>
            <p><strong>Email:</strong> <?php echo $USER->email; ?></p>
        </div>

        <div class="instructions">
            <h3>📋 Test Instructions</h3>
            <ol>
                <li>Click the agent card below</li>
                <li>Panel should slide in from the right</li>
                <li>If no report exists, you'll see "Generate Report" button</li>
                <li>Click to generate, wait for completion</li>
                <li>Report should display with your data</li>
                <li>Try "Regenerate Report" button</li>
                <li>Test close button, click outside, and ESC key</li>
            </ol>
        </div>

        <div class="test-card" data-agent-id="agent01_onboarding" onclick="testPanelOpen()">
            <h3>🎯 Agent 01</h3>
            <p>Onboarding</p>
            <p><small>Click to test panel</small></p>
        </div>
    </div>

    <script src="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js"></script>
    <script>
        window.currentUserId = <?php echo $USER->id; ?>;

        function testPanelOpen() {
            console.log('=== Test: Opening panel ===');
            console.log('User ID:', window.currentUserId);
            console.log('OnboardingPanel object:', window.OnboardingPanel);

            if (!window.OnboardingPanel) {
                console.error('ERROR: OnboardingPanel not initialized!');
                alert('OnboardingPanel이 초기화되지 않았습니다. 브라우저 콘솔을 확인하세요.');
                return;
            }

            try {
                OnboardingPanel.open(window.currentUserId);
                console.log('Panel opened successfully');
            } catch (error) {
                console.error('ERROR opening panel:', error);
                alert('패널 열기 오류: ' + error.message);
            }
        }

        // Log initialization
        console.log('=== Agent01 Panel Test Page ===');
        console.log('Current user:', window.currentUserId);
        console.log('OnboardingPanel object:', window.OnboardingPanel);

        // Wait for full initialization
        setTimeout(() => {
            console.log('After 1 second - OnboardingPanel:', window.OnboardingPanel);
            if (window.OnboardingPanel) {
                console.log('✅ OnboardingPanel initialized successfully');
                console.log('Panel element:', window.OnboardingPanel.panelElement);
            } else {
                console.error('❌ OnboardingPanel NOT initialized!');
                console.error('Check if panel.js loaded correctly');
            }
        }, 1000);

        console.log('Test ready!');
    </script>
</body>
</html>
