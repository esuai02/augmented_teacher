<?php
/**
 * Agent05 학습감정 분석 에이전트
 * 활동별 학습 감정 유형 분석 및 선택
 *
 * File: alt42/orchestration/agents/agent05_learning_emotion/index.php
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? 'student';

// 사용자 ID
$userid = $USER->id;
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : $userid;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎭 Agent05 학습감정 분석</title>
    <link rel="stylesheet" href="assets/css/agent05.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="agent05-container">
        <header class="agent05-header">
            <h1>🎭 학습감정 분석 (Agent05)</h1>
            <p class="subtitle">활동을 선택하고 학습 감정 유형을 파악하세요</p>
        </header>

        <main class="agent05-main">
            <!-- 활동 선택 카드 그리드 -->
            <div id="activity-cards-grid" class="activity-grid">
                <!-- JavaScript로 동적 생성 -->
            </div>
        </main>

        <!-- 선택된 활동 정보 패널 -->
        <aside id="selection-panel" class="selection-panel hidden">
            <h2 id="panel-title">선택 정보</h2>
            <div id="panel-content"></div>
        </aside>
    </div>

    <!-- 모달 오버레이 -->
    <div id="modal-overlay" class="modal-overlay hidden"></div>

    <!-- JavaScript -->
    <script>
        window.currentUserId = <?php echo json_encode($studentid); ?>;
    </script>
    <script src="assets/js/activity_categories_data.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/emotion_workflow.js?v=<?php echo time(); ?>"></script>
</body>
</html>
