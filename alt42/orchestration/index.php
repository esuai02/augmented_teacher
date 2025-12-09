<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;
require_login();

// Extract student ID from request or use current user 
$studentid = $_GET["userid"] ?? null;
if ($studentid == NULL) {
    $studentid = $USER->id;
}

// Get target student information from database
try {
    $target_student = $DB->get_record('user', ['id' => $studentid], 'id, firstname, lastname', MUST_EXIST);
    $student_name = $target_student->firstname . ' ' . $target_student->lastname;
} catch (Exception $e) {
    // Fallback to current user if target student not found - File: index.php, Line: 13
    $student_name = $USER->firstname . ' ' . $USER->lastname;
    $studentid = $USER->id;
}

// Determine user role
$context = context_system::instance();
$is_teacher = has_capability('moodle/site:config', $context);
$role = $is_teacher ? 'teacher' : 'student';

// Initialize persona mode variables
$current_mode = 'default';
$mode_display = '일반 모드';
?>
<?php
// ---- AJAX handler for in-page API (report generation) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'];
  $uid = isset($_POST['userid']) ? intval($_POST['userid']) : 0;
  if ($uid <= 0) { $uid = intval($studentid); }

  if ($action === 'generateReportGPT') {
    require_once __DIR__ . '/agents/agent01_onboarding/report_generator.php';
    $gpt = generateReportWithGPT($uid);
    if ($gpt['success']) {
      echo json_encode([
        'success' => true,
        'reportHTML' => $gpt['reportHTML'],
        'reportType' => 'gpt'
      ]);
    } else {
      echo json_encode([
        'success' => false,
        'error' => $gpt['error'] ?? 'GPT generation failed'
      ]);
    }
    exit;
  }

  echo json_encode([
    'success' => false,
    'error' => 'Unknown action (index.php)'
  ]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🚀 ALT42 자동개입 시스템 v1.1</title>

  <!-- Agent Popup Enhancement Styles -->
  <link rel="stylesheet" href="assets/css/agent_popup_enhancements.css?v=<?php echo time(); ?>">

  <!-- Agent 05 Learning Emotion Analysis -->
  <link rel="stylesheet" href="agents/agent05_learning_emotion/assets/css/agent05.css?v=<?php echo time(); ?>">

  <!-- Agent 07 Guidance Mode Selection -->
  <link rel="stylesheet" href="agents/agent07_interaction_targeting/styles.css?v=<?php echo time(); ?>">

  <!-- Agent 16 Interaction Preparation Panel -->
  <link rel="stylesheet" href="agents/agent16_interaction_preparation/ui/panel.css?v=<?php echo time(); ?>">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
    }

    #app {
      max-width: 100%;
      margin: 0;
    }

    .header {
      background: white;
      padding: 20px 30px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      text-align: center;
    }

    .header h1 {
      font-size: 24px;
      color: #1f2937;
      margin-bottom: 10px;
    }

    .header .info {
      color: #6b7280;
      font-size: 14px;
    }

    /* 레이아웃: 좌측 1열 메뉴 + 우측 상세 패널 */
    .layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 16px;
      align-items: start;
      max-width: 1400px;
      margin: 0 auto;
    }

    .sidebar {
      position: sticky;
      top: 20px;
      max-height: calc(100vh - 40px);
      overflow: auto;
    }

    .workflow-container {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .step-card {
      background: #ffffff;
      border-radius: 10px;
      padding: 10px 12px 40px 12px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.08);
      transition: all 0.2s ease;
      position: relative;
      overflow: visible;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      min-height: 72px;
    }

    .step-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.12); }

    .step-card.active {
      border: 2px solid #3b82f6;
      background: #eef2ff; /* 단색 배경 */
      box-shadow: 0 2px 8px rgba(59,130,246,0.2);
    }

    .step-card.completed {
      opacity: 0.8;
    }

    .step-left { display: flex; flex-direction: column; align-items: center; width: 48px; flex: 0 0 48px; }

    .step-icon { font-size: 22px; line-height: 22px; }

    .step-number { font-size: 10px; font-weight: 700; color: #64748b; margin-top: 4px; }

    .step-title { font-size: 13px; font-weight: 700; color: #1f2937; }

    .step-description { font-size: 11px; color: #6b7280; line-height: 1.2; }

    .step-badge { position: absolute; top: 8px; right: 8px; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; }

    .step-badge.completed {
      background: #10b981;
      color: white;
    }

    .step-badge.active {
      background: #3b82f6;
      color: white;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: white;
      border-radius: 12px;
      padding: 30px;
      max-width: 1000px;
      width: 90%;
      max-height: 90vh;
      overflow: hidden;
    }

    /* 우측 상세 패널 */
    .detail-panel {
      background: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.08);
      min-height: 480px;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-header h2 {
      font-size: 20px;
      color: #1f2937;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 28px;
      cursor: pointer;
      color: #9ca3af;
      line-height: 1;
    }

    .modal-close:hover {
      color: #1f2937;
    }

    .prompt-display-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: white;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      max-width: 400px;
      display: none;
    }

    .prompt-display-container.active {
      display: block;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background: #3b82f6;
      color: white;
    }

    .btn-primary:hover {
      background: #2563eb;
    }

    .btn-secondary {
      background: #e5e7eb;
      color: #374151;
    }

    .btn-secondary:hover {
      background: #d1d5db;
    }

    /* Step 3 Goal Analysis Panel Styles */
    .goal-analysis-panel { padding: 20px; }
    .goal-analysis-panel h3 { font-size: 20px; color: #111827; margin-bottom: 8px; }
    .goal-analysis-panel .description { color: #6b7280; font-size: 14px; margin-bottom: 16px; }
    .goal-type-buttons { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
    .goal-type-btn {
      background: white; border: 1px solid #e5e7eb; border-radius: 8px;
      padding: 12px 16px; cursor: pointer; transition: all 0.2s; display: flex;
      align-items: center; gap: 12px; text-align: left;
    }
    .goal-type-btn:hover { background: #f9fafb; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .goal-type-btn.active { background: #eef2ff; border-color: #6366f1; }
    .goal-type-btn .icon { font-size: 24px; }
    .goal-type-btn .name { font-weight: 600; color: #1f2937; font-size: 15px; }
    .selected-type-info { margin-bottom: 16px; padding: 12px; background: #f0fdf4; border-radius: 6px; }
    .selected-badge { display: flex; align-items: center; gap: 8px; }
    .selected-badge .badge-icon { font-size: 20px; }
    .selected-badge .badge-text { font-weight: 600; color: #059669; }
    .execute-btn {
      width: 100%; padding: 12px; background: #3b82f6; color: white; border: none;
      border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;
      transition: all 0.2s; margin-bottom: 20px;
    }
    .execute-btn:hover:not(:disabled) { background: #2563eb; }
    .execute-btn:disabled { background: #9ca3af; cursor: not-allowed; }
    .loading-indicator { text-align: center; padding: 20px; }
    .spinner {
      width: 40px; height: 40px; margin: 0 auto 12px; border: 4px solid #e5e7eb;
      border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .analysis-result { background: #f9fafb; padding: 16px; border-radius: 8px; margin-top: 16px; }
    .analysis-result h4 { font-size: 16px; color: #111827; margin-bottom: 12px; }
    .analysis-text { color: #374151; line-height: 1.6; margin-bottom: 12px; white-space: pre-wrap; }
    .result-stats { display: flex; flex-direction: column; gap: 8px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
    .stat-item { display: flex; justify-content: space-between; }
    .stat-label { color: #6b7280; font-size: 14px; }
    .stat-value { color: #111827; font-weight: 600; font-size: 14px; }
    /* Knowledge file buttons */
    .knowledge-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white; border: none; border-radius: 6px; padding: 6px 12px;
      font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;
      box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }
    .knowledge-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 8px rgba(102, 126, 234, 0.5);
    }
    .knowledge-btn-small {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white; border: none; border-radius: 6px; padding: 12px 16px;
      font-size: 18px; cursor: pointer; transition: all 0.2s;
      box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
      min-width: 50px;
    }
    .knowledge-btn-small:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 8px rgba(102, 126, 234, 0.5);
    }

    
  </style>
</head>
<body>

  <!-- 메인 앱 컨테이너 -->
  <div id="app">
    <div class="header">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <!-- 좌측: 네비게이션 드롭다운 + 제목 -->
        <div style="display:flex;align-items:center;gap:16px;">
          <!-- 네비게이션 드롭다운 (이미지 스타일) -->
          <div class="nav-dropdown" style="position:relative;">
            <button onclick="toggleNavDropdown()" style="background:white;border:1px solid #e5e7eb;border-radius:6px;padding:8px 14px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#374151;transition:all 0.2s;min-width:180px;justify-content:space-between;">
              <span id="navDropdownLabel">1. 에이전트 미션</span>
              <span style="font-size:12px;color:#9ca3af;">▼</span>
            </button>
            <div id="navDropdownMenu" class="nav-dropdown-menu" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.12);min-width:200px;z-index:1000;overflow:hidden;">
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#agent01" class="nav-item active" style="display:block;padding:10px 16px;text-decoration:none;color:#1f2937;font-size:14px;background:#fef3c7;border-left:3px solid #f59e0b;">
                1. 에이전트 미션
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#requests" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                2. 주요 요청들
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#data" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                3. 데이터 통합
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#rules" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                4. 에이전트 룰들
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#mathking" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                5. Mathking AI 조교
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#heartbeat" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                6. Heartbeat Dashboard
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#gardening" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                7. 에이전트 가드닝
              </a>
              <a href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/agentmission.html#persona" class="nav-item" style="display:block;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-left:3px solid transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                8. 페르소나 테스트
              </a>
            </div>
          </div>
          <h1 style="margin:0;font-size:20px;">🚀 ALT42 자동개입 시스템 v1.1</h1>
        </div>
        <!-- 우측: 메뉴 드롭다운 -->
        <div class="header-dropdown" style="position:relative;">
          <button onclick="toggleHeaderDropdown()" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;border-radius:8px;padding:10px 16px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:white;transition:all 0.2s;box-shadow:0 2px 8px rgba(102,126,234,0.3);">
            <span style="font-size:16px;">☰</span>
            <span>바로가기</span>
          </button>
          <div id="headerDropdownMenu" class="header-dropdown-menu" style="display:none;position:absolute;top:100%;right:0;margin-top:8px;background:white;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);min-width:220px;z-index:1000;overflow:hidden;">
            <a href="/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/ignition/index.php" class="dropdown-item" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#374151;font-size:14px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
              <span>🚀</span> 시작화면
            </a>
            <a href="/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo htmlspecialchars($studentid); ?>" class="dropdown-item" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#374151;font-size:14px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
              <span>👤</span> 학생 인터페이스
            </a>
            <a href="/moodle/local/augmented_teacher/teachers/Goclassroomgame.php?userid=<?php echo htmlspecialchars($studentid); ?>" class="dropdown-item" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#374151;font-size:14px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
              <span>⏱️</span> 타임캐칭 인터페이스
            </a>
            <a href="https://claude.ai/public/artifacts/7808e400-b2b5-44de-b53b-8db699ffbdf3" target="_blank" class="dropdown-item" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#374151;font-size:14px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
              <span>💬</span> 실시간 상호작용
            </a>
            <a href="/moodle/local/augmented_teacher/teachers/realtime_dashboard.php?userid=<?php echo htmlspecialchars($studentid); ?>" class="dropdown-item" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#374151;font-size:14px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
              <span>📊</span> 실시간 모니터링
            </a>
            <div style="border-top:1px solid #e5e7eb;margin:4px 0;"></div>
            <a href="/moodle/local/augmented_teacher/alt42/studenthome/wxsperta/wxsperta.php" class="dropdown-item" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#374151;font-size:14px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
              <span>🔮</span> 퓨쳐셀프
            </a>
          </div>
        </div>
      </div>
      <div class="info" style="margin-top:10px;">
        학생: <?php echo htmlspecialchars($student_name); ?> (ID: <?php echo htmlspecialchars($studentid); ?>) |
        역할: <?php echo htmlspecialchars($role); ?> |
        모드: <?php echo htmlspecialchars($mode_display); ?>
      </div>
    </div>

    <div class="layout">
      <aside class="sidebar">
        <div class="workflow-container" id="workflow-container">
          <!-- 좌측 1열 메뉴(카드) 렌더링 -->
        </div>
      </aside>
      <main id="detail-panel" class="detail-panel">
        <div id="detail-content">
          <div style="color:#6b7280;">좌측 단계(카드)를 클릭하면 상세가 여기에 표시됩니다.</div>
        </div>
      </main>
    </div>
  </div>

  <!-- 모달 오버레이 -->
  <div id="modal-overlay" class="modal-overlay">
    <div id="modal-content-wrapper"></div>
  </div>

  <!-- 프롬프트 표시 영역 -->
  <div id="prompt-display-container" class="prompt-display-container"></div>

  <!-- Agent Problem Popup System -->
  <script src="assets/js/agent_problems.js?v=<?php echo time(); ?>"></script>
  <script src="assets/js/agent_popup.js?v=<?php echo time(); ?>"></script>
  <script src="assets/js/agent_analysis.js?v=<?php echo time(); ?>"></script>

  <!-- Agent 07 (상호작용 타게팅) - Guidance Mode Selection -->
  <script src="agents/agent07_interaction_targeting/guidance_modes_data.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent07_interaction_targeting/panel_renderer.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent07_interaction_targeting/modal_popup.js?v=<?php echo time(); ?>"></script>

  <!-- Agent UI Scripts -->
  <script src="agents/agent01_onboarding/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent02_exam_schedule/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent05_learning_emotion/assets/js/activity_categories_data.js?v=<?php echo time(); ?>"></script>
  <script src="assets/js/agent05_handlers.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent08_calmness/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent09_learning_management/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent10_concept_notes/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent11_problem_notes/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent12_rest_routine/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent13_learning_dropout/ui/agent.js?v=<?php echo time(); ?>"></script>
  <script src="agents/agent14_current_position/ui/agent.js?v=<?php echo time(); ?>"></script>

  <!-- Agent 15 Problem Redefinition Panel -->
  <script src="agents/agent15_problem_redefinition/ui/agent.js?v=<?php echo time(); ?>"></script>

  <!-- Agent 16 Interaction Preparation Panel -->
  <script src="agents/agent16_interaction_preparation/ui/panel.js?v=<?php echo time(); ?>"></script>

  <!-- Step 3 Goal Analysis -->
  <script src="assets/js/step3_goal_analysis.js?v=<?php echo time(); ?>"></script>

  <script>
    // PHP 데이터를 JavaScript로 전달
    const phpData = {
      studentId: <?php echo json_encode($studentid, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
      studentName: <?php echo json_encode($student_name, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
      userRole: <?php echo json_encode($role, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
      currentMode: <?php echo json_encode($current_mode, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
      modeDisplay: <?php echo json_encode($mode_display, JSON_HEX_TAG | JSON_HEX_AMP); ?>
    };

    // 전역으로 phpData 노출
    window.phpData = phpData;

    // 워크플로우 단계 정의
    const steps = [
      { id:1, title:"온보딩", icon:"👤", color:"#3b82f6", description:"학생 프로필 및 학습 이력 로드" },
      { id:2, title:"시험일정 식별", icon:"📅", color:"#ec4899", description:"학습 맥락 및 긴급도 파악" },
      { id:3, title:"목표 및 계획 분석", icon:"🎯", color:"#14b8a6", description:"목표 정렬도 및 진행률 확인" },
      { id:4, title:"문제활동 식별", icon:"📚", color:"#10b981", description:"학습 활동 선택 및 특성 파악" },
      { id:5, title:"학습감정 분석", icon:"😊", color:"#f97316", description:"감정 상태 및 집중도 분석" },
      { id:6, title:"선생님 피드백", icon:"👨‍🏫", color:"#e11d48", description:"교사 지도사항 반영" },
      { id:7, title:"상호작용 타게팅", icon:"🔍", color:"#ef4444", description:"문제 정의 및 우선순위 결정" },
      { id:8, title:"침착도 분석", icon:"😌", color:"#0ea5e9", description:"생체신호 기반 침착도 평가" },
      { id:9, title:"학습관리 분석", icon:"📈", color:"#06b6d4", description:"학습 성과 및 보완점 파악" },
      { id:10, title:"개념노트 분석", icon:"📝", color:"#7c3aed", description:"노트 품질 및 개선사항 분석" },
      { id:11, title:"문제노트 분석", icon:"📋", color:"#9333ea", description:"오답 패턴 및 취약영역 파악" },
      { id:12, title:"휴식루틴 분석", icon:"☕", color:"#a855f7", description:"휴식 패턴 및 에너지 레벨" },
      { id:13, title:"학습이탈 분석", icon:"📊", color:"#84cc16", description:"이탈 패턴 및 예방 전략" },
      { id:14, title:"현재위치 평가", icon:"📍", color:"#059669", description:"진행 상태 및 리스크 평가" },
      { id:15, title:"문제 재정의 & 개선방안", icon:"🎯", color:"#f59e0b", description:"핵심 이슈 및 해결방향 도출" },
      { id:16, title:"상호작용 준비", icon:"🧭", color:"#6366f1", description:"학습 모드 및 전략 선택" },
      { id:17, title:"잔여활동 조정", icon:"🚀", color:"#dc2626", description:"계획 조정 및 부스터 활동" },
      { id:18, title:"시그너처 루틴 찾기", icon:"🔍", color:"#8b5cf6", description:"최적 학습 루틴 발굴" },
      { id:19, title:"상호작용 컨텐츠 생성", icon:"✨", color:"#8b5cf6", description:"맞춤형 컨텐츠 제작" },
      { id:20, title:"개입준비", icon:"🎬", color:"#0f172a", description:"개입 계획 수립" },
      { id:21, title:"개입실행", icon:"🚀", color:"#dc2626", description:"최종 개입 실행" },
      { id:22, title:"모듈성능 개선 제안", icon:"🔬", color:"#16a34a", description:"시스템 최적화 방안" }
    ];

    // 상태 관리
    const state = {
      currentStep: 1,
      completedSteps: new Set([]),
      stepData: {},
      selectedMBTI: null
    };

    // 전역으로 state 노출
    window.state = state;

    // 워크플로우 렌더링
    function renderWorkflow() {
      const container = document.getElementById('workflow-container');
      container.innerHTML = steps.map(step => {
        const isCompleted = state.completedSteps.has(step.id);
        const isActive = step.id === state.currentStep;
        const classes = ['step-card'];
        if (isCompleted) classes.push('completed');
        if (isActive) classes.push('active');

        return `
          <div class="${classes.join(' ')}" style="border-left: 4px solid ${step.color}; position: relative;">
            <div onclick="handleStepClick(${step.id})" style="display: flex; align-items: center; gap: 10px; flex: 1; cursor: pointer;">
              <div class="step-left">
                <div class="step-icon">${step.icon}</div>
                <div class="step-number">Step ${step.id}</div>
              </div>
              <div style="display:flex;flex-direction:column;gap:2px;">
                <div class="step-title">${step.title}</div>
                <div class="step-description">${step.description}</div>
              </div>
            </div>
            <button
              onclick="event.stopPropagation(); window.showAgentProblemPopup(${step.id - 1})"
              style="
                position: absolute;
                bottom: 8px;
                right: 8px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                padding: 4px 10px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
                z-index: 10;
              "
              onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 8px rgba(102, 126, 234, 0.5)'"
              onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(102, 126, 234, 0.3)'"
              title="이 에이전트의 문제 타게팅 보기"
            >
              🎯 문제 타게팅
            </button>
            <div class="step-badge ${isCompleted ? 'completed' : isActive ? 'active' : ''}" style="top: 8px;">
              ${isCompleted ? '✓' : isActive ? '●' : ''}
            </div>
          </div>
        `;
      }).join('');
    }

    // 단계 클릭 핸들러
    function handleStepClick(stepId) {
      console.log('Step clicked:', stepId);
      // 동일 스텝 재클릭 시에도 우측 패널을 재렌더하여 최신 내용 반영
      const wasSame = state.currentStep === stepId;
      state.currentStep = stepId;
      renderWorkflow();
      clearDetail();
      renderDetail(stepId);
    }

    // 모달 표시
    function renderDetail(stepId) {
      const step = steps.find(s => s.id === stepId);
      if (!step) return;
      const panel = document.getElementById('detail-panel');
      const content = document.getElementById('detail-content') || panel;

      try {
        // 에이전트 전용 UI가 있는 경우 우측 패널로 렌더링 시도
        if (stepId === 1 && typeof renderAgent01Panel === 'function') {
          return renderAgent01Panel(content);
        }
        if (stepId === 2 && typeof renderAgent02Panel === 'function') {
          return renderAgent02Panel(content);
        }
        if (stepId === 4 && typeof renderAgent04Panel === 'function') {
          return renderAgent04Panel(content);
        }
        // Step 3 - Goal Analysis UI
        if (stepId === 3 && typeof renderGoalAnalysisUI === 'function') {
          return renderGoalAnalysisUI(content);
        }
        if (stepId === 5 && typeof renderAgent05Panel === 'function') {
          return renderAgent05Panel(content);
        }
        if (stepId === 6 && typeof renderAgent06Panel === 'function') {
          return renderAgent06Panel(content);
        }
        // Agent 07 - Guidance Mode Selection Panel
        if (stepId === 7 && typeof renderAgent07Panel === 'function') {
          content.innerHTML = renderAgent07Panel();
          panel.classList.add('active');
          return;
        }
        if (stepId === 8 && typeof renderAgent08Panel === 'function') {
          return renderAgent08Panel(content);
        }
        if (stepId === 9 && typeof renderAgent09Panel === 'function') {
          return renderAgent09Panel(content);
        }
        if (stepId === 10 && typeof renderAgent10Panel === 'function') {
          return renderAgent10Panel(content);
        }
        if (stepId === 11 && typeof renderAgent11Panel === 'function') {
          return renderAgent11Panel(content);
        }
        if (stepId === 12 && typeof renderAgent12Panel === 'function') {
          return renderAgent12Panel(content);
        }
        if (stepId === 13 && typeof renderAgent13Panel === 'function') {
          return renderAgent13Panel(content);
        }
        if (stepId === 14 && typeof renderAgent14Panel === 'function') {
          return renderAgent14Panel(content);
        }
    // Agent 15 - Problem Redefinition Panel (iframe)
    if (stepId === 15 && typeof renderAgent15Panel === 'function') {
      return renderAgent15Panel(content);
    }
        if (stepId === 16 && typeof renderAgent16Panel === 'function') {
          return renderAgent16Panel(content);
        }
        if (stepId === 17 && typeof renderAgent17Panel === 'function') {
          return renderAgent17Panel(content);
        }
        if (stepId === 19 && typeof renderAgent19Panel === 'function') {
          return renderAgent19Panel(content);
        }
        // Agent 16 - Interaction Mode (embedded iframe from orchestration91)
        if (stepId === 16 && typeof renderAgent16Panel === 'function') {
          return renderAgent16Panel(content);
        }

        // 안전장치: 패널 함수가 아직 없다면 모달 함수를 직접 패널에 어댑트해 호출
        const stepIdStr = (stepId < 10 ? '0' + stepId : '' + stepId);
        const modalFnName = 'showAgent' + stepIdStr + 'Modal';
        if (typeof window[modalFnName] === 'function') {
          return renderModalIntoPanel(() => window[modalFnName]());
        }
      } catch (e) {
        console.error('renderDetail error:', e);
      }

      // 기본 상세 콘텐츠
      content.innerHTML = `
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
          <div style="font-size:28px;">${step.icon}</div>
          <div>
            <div style="font-weight:700;color:#111827;font-size:18px;">${step.title}</div>
            <div style="color:#6b7280;font-size:13px;">Step ${step.id}</div>
          </div>
        </div>
        <p style="color:#374151;line-height:1.6;">${step.description}</p>
        <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end;">
          <button class="btn btn-secondary" onclick="clearDetail()">비우기</button>
          <button class="btn btn-primary" onclick="completeStep(${stepId})">완료</button>
        </div>
      `;
    }

    function clearDetail(){
      const panel = document.getElementById('detail-panel');
      const content = document.getElementById('detail-content') || panel;
      if (!panel) return;
      let wrapper = document.getElementById('modal-content-wrapper');
      if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.id = 'modal-content-wrapper';
        content.appendChild(wrapper);
      }
      wrapper.innerHTML = '';
    }

    // 모달 닫기
    function closeModal() {
      document.getElementById('modal-overlay').classList.remove('active');
    }

    // 모달 열기 (HTML 콘텐츠 표시)
    function openModalWithContent(title, html) {
      const overlay = document.getElementById('modal-overlay');
      let wrapper = document.getElementById('modal-content-wrapper');
      if (!overlay) return;
      if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.id = 'modal-content-wrapper';
        overlay.appendChild(wrapper);
      }
      // 모달 컨테이너로 이동 보장
      if (wrapper.parentElement !== overlay) {
        overlay.appendChild(wrapper);
      }
      wrapper.innerHTML = `
        <div class="modal-content">
          <div class="modal-header">
            <h2>${title}</h2>
            <div style="display:flex;align-items:center;gap:8px;">
              <button id="modal-print" class="btn btn-secondary" title="인쇄">인쇄</button>
              <button id="modal-open-new" class="btn btn-secondary" title="새 창에서 열기">새 창</button>
              <button id="modal-fullscreen" class="btn btn-secondary" title="전체화면 전환">전체화면</button>
              <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
          </div>
          <div class="modal-body" id="modal-body-content" style="max-height: calc(90vh - 90px); overflow: auto; padding: 12px 16px; background:#f7fafc;">${html}</div>
        </div>
      `;
      overlay.style.display = 'flex';
      overlay.classList.add('active');
      // 버튼 액션 바인딩
      const contentEl = wrapper.querySelector('.modal-content');
      const bodyEl = wrapper.querySelector('#modal-body-content');
      const fsBtn = wrapper.querySelector('#modal-fullscreen');
      const newBtn = wrapper.querySelector('#modal-open-new');
      const printBtn = wrapper.querySelector('#modal-print');
      if (fsBtn && contentEl && bodyEl) {
        fsBtn.addEventListener('click', () => {
          const isFull = contentEl.classList.toggle('fullscreen');
          if (isFull) {
            contentEl.style.maxWidth = '95vw';
            contentEl.style.width = '95vw';
            contentEl.style.maxHeight = '95vh';
            bodyEl.style.maxHeight = 'calc(95vh - 90px)';
            fsBtn.textContent = '창 축소';
          } else {
            contentEl.style.maxWidth = '1000px';
            contentEl.style.width = '90%';
            contentEl.style.maxHeight = '90vh';
            bodyEl.style.maxHeight = 'calc(90vh - 90px)';
            fsBtn.textContent = '전체화면';
          }
        });
      }
      if (newBtn && bodyEl) {
        newBtn.addEventListener('click', () => {
          const win = window.open('', '_blank');
          if (win && win.document) {
            const doc = `
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>
    body { margin: 0; padding: 20px; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#fafafa; color:#111827; }
    .container { max-width: 1100px; margin: 0 auto; background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.06); padding:24px; }
    .container h1,.container h2,.container h3 { color:#111827; }
    .container table { width:100%; border-collapse: collapse; }
    .container table td, .container table th { border:1px solid #e5e7eb; padding:8px; vertical-align: top; }
    .container details { margin-bottom: 10px; }
    .container pre { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; white-space: pre-wrap; word-break: break-word; }
  </style>
</head>
<body>
  <div class="container">
    <h2 style="margin-top:0;">${title}</h2>
    ${bodyEl.innerHTML}
  </div>
</body>
</html>`;
            win.document.open();
            win.document.write(doc);
            win.document.close();
          }
        });
      }
      if (printBtn && bodyEl) {
        printBtn.addEventListener('click', () => {
          const win = window.open('', '_blank');
          if (win && win.document) {
            const doc = `
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>
    @media print {
      .no-print { display: none; }
    }
    body { margin: 0; padding: 20px; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#fff; color:#111827; }
    .container { max-width: 1100px; margin: 0 auto; padding:24px; }
    .container h1,.container h2,.container h3 { color:#111827; }
    .container table { width:100%; border-collapse: collapse; }
    .container table td, .container table th { border:1px solid #e5e7eb; padding:8px; vertical-align: top; }
    .container details { margin-bottom: 10px; }
    .container pre { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; white-space: pre-wrap; word-break: break-word; }
  </style>
</head>
<body>
  <div class="container">
    <div class="no-print" style="text-align:right;margin-bottom:10px;">
      <button onclick="window.print()">인쇄</button>
    </div>
    <h2 style="margin-top:0;">${title}</h2>
    ${bodyEl.innerHTML}
  </div>
</body>
</html>`;
            win.document.open();
            win.document.write(doc);
            win.document.close();
            setTimeout(() => { try { win.print(); } catch(e) {} }, 500);
          }
        });
      }
    }

    // 단계 완료
    function completeStep(stepId) {
      state.completedSteps.add(stepId);
      if (stepId < steps.length) {
        state.currentStep = stepId + 1;
      }
      renderWorkflow();
      closeModal();
    }

    // 네비게이션 드롭다운 메뉴 토글
    function toggleNavDropdown() {
      const menu = document.getElementById('navDropdownMenu');
      const headerMenu = document.getElementById('headerDropdownMenu');
      // 다른 드롭다운 닫기
      if (headerMenu) headerMenu.style.display = 'none';
      
      if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
      } else {
        menu.style.display = 'none';
      }
    }
    
    // 헤더 드롭다운 메뉴 토글
    function toggleHeaderDropdown() {
      const menu = document.getElementById('headerDropdownMenu');
      const navMenu = document.getElementById('navDropdownMenu');
      // 다른 드롭다운 닫기
      if (navMenu) navMenu.style.display = 'none';
      
      if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
      } else {
        menu.style.display = 'none';
      }
    }
    
    // 드롭다운 외부 클릭시 닫기
    document.addEventListener('click', function(e) {
      const headerDropdown = document.querySelector('.header-dropdown');
      const headerMenu = document.getElementById('headerDropdownMenu');
      const navDropdown = document.querySelector('.nav-dropdown');
      const navMenu = document.getElementById('navDropdownMenu');
      
      if (headerDropdown && headerMenu && !headerDropdown.contains(e.target)) {
        headerMenu.style.display = 'none';
      }
      if (navDropdown && navMenu && !navDropdown.contains(e.target)) {
        navMenu.style.display = 'none';
      }
    });
    
    // 네비게이션 아이템 클릭 시 라벨 업데이트
    document.addEventListener('DOMContentLoaded', function() {
      const navItems = document.querySelectorAll('.nav-dropdown-menu .nav-item');
      navItems.forEach(item => {
        item.addEventListener('click', function() {
          const label = document.getElementById('navDropdownLabel');
          if (label) {
            label.textContent = this.textContent.trim();
          }
          // 활성 상태 업데이트
          navItems.forEach(i => {
            i.style.background = 'transparent';
            i.style.borderLeftColor = 'transparent';
          });
          this.style.background = '#fef3c7';
          this.style.borderLeftColor = '#f59e0b';
        });
      });
    });

    // 초기화
    document.addEventListener('DOMContentLoaded', function() {
      console.log('✅ ALT42 Orchestration System loaded');
      console.log('PHP Data:', phpData);
      renderWorkflow();
      initPanelAdapters();
      // 페이지 진입 시 온보딩(Step 1)을 즉시 표시
      renderDetail(1);
    });

    // 모달 오버레이 클릭시 닫기
    document.getElementById('modal-overlay').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });
  </script>

  <script>
    // 팝업(모달) UI를 우측 패널에 그대로 렌더링하기 위한 어댑터
    function mountWrapperToPanel() {
      const panel = document.getElementById('detail-panel');
      const content = document.getElementById('detail-content') || panel;
      const overlay = document.getElementById('modal-overlay');
      let wrapper = document.getElementById('modal-content-wrapper');
      if (!panel || !overlay) return { overlay:null, wrapper:null };
      if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.id = 'modal-content-wrapper';
        content.appendChild(wrapper);
      }

      // 우측 패널로 모달 컨테이너를 이동
      if (wrapper.parentElement !== content) {
        content.innerHTML = '';
        content.appendChild(wrapper);
      }
      // 백드롭 숨김 처리 (모달이 아닌 패널 렌더)
      overlay.classList.remove('active');
      overlay.style.display = 'none';
      return { overlay, wrapper };
    }

    function renderModalIntoPanel(invokerFn) {
      const { overlay, wrapper } = mountWrapperToPanel();
      if (!overlay) { invokerFn(); return; }

      const originalClose = window.closeModal;
      window.closeModal = function() {
        if (typeof clearDetail === 'function') clearDetail();
      };

      // 모달 호출 실행 (모달 내부 HTML이 wrapper에 주입됨)
      if (wrapper) { wrapper.innerHTML = ''; }
      invokerFn();
      // 즉시 백드롭 제거 및 패널 유지
      overlay.classList.remove('active');
      overlay.style.display = 'none';
    }

    // Agent 01 패널: 온보딩 UI를 그대로 임베드 (iframe)
    function renderAgent01Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/omniui/student_onboarding.php?userid=${encodeURIComponent(studentId)}`;
        // MBTI 선택칸을 온보딩 iframe 위에 표시
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">👤</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">온보딩</div>
                <div style="color:#6b7280;font-size:13px;">Step 1</div>
              </div>
              <div style="margin-left:auto;display:flex;gap:8px;">
                <button id="btn-generate-onboarding-report" class="knowledge-btn" title="온보딩 리포트 생성">📄 리포트 생성</button>
                <button id="btn-refresh-onboarding-data" class="knowledge-btn" title="저장된 온보딩 데이터 새로고침">🔄 데이터 새로고침</button>
              </div>
            </div>
            <div id="mbti-inline-wrapper"></div>
            <div id="onboarding-data-display" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:12px;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <h3 style="font-size:16px;font-weight:600;color:#111827;margin:0;">📋 저장된 온보딩 정보</h3>
                <div id="onboarding-data-status" style="font-size:12px;color:#6b7280;">로딩 중...</div>
              </div>
              <div id="onboarding-data-content" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;">
                <div style="text-align:center;padding:20px;color:#9ca3af;">데이터를 불러오는 중...</div>
              </div>
            </div>
            <iframe src="${src}" style="width:100%;min-height:80vh;border:0;border-radius:12px;background:#fff;" allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
          </div>
        `;
        
        // 온보딩 데이터 로드 함수
        async function loadOnboardingData() {
          const contentEl = document.getElementById('onboarding-data-content');
          const statusEl = document.getElementById('onboarding-data-status');
          if (!contentEl || !statusEl) return;
          
          try {
            statusEl.textContent = '불러오는 중...';
            statusEl.style.color = '#6b7280';
            
            const response = await fetch(`/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/api/get_onboarding_data.php?userid=${encodeURIComponent(studentId)}`, {
              method: 'GET',
              headers: { 'Accept': 'application/json' },
              credentials: 'same-origin'
            });
            
            if (!response.ok) {
              throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.context) {
              const ctx = data.context;
              const fields = [
                { label: '수학 수준', value: ctx.math_level || '미입력', key: 'math_level' },
                { label: '수학 자신감', value: ctx.math_confidence ? `${ctx.math_confidence}/10` : '미입력', key: 'math_confidence' },
                { label: '학습 스타일', value: ctx.study_style || '미입력', key: 'study_style' },
                { label: '수학 학습 스타일', value: ctx.math_learning_style || '미입력', key: 'math_learning_style' },
                { label: '학원명', value: ctx.academy_name || '미입력', key: 'academy_name' },
                { label: '학원 등급/반', value: ctx.academy_grade || '미입력', key: 'academy_grade' },
                { label: '최근 수학 점수', value: ctx.math_recent_score || '미입력', key: 'math_recent_score' },
                { label: '개념 진도', value: ctx.concept_progress || '미입력', key: 'concept_progress' },
                { label: '심화 진도', value: ctx.advanced_progress || '미입력', key: 'advanced_progress' },
                { label: '시험 대비 성향', value: ctx.exam_style || '미입력', key: 'exam_style' },
                { label: '부모 스타일', value: ctx.parent_style || '미입력', key: 'parent_style' },
                { label: 'MBTI', value: ctx.mbti_type || '미입력', key: 'mbti_type' }
              ];
              
              contentEl.innerHTML = fields.map(field => {
                const hasValue = field.value !== '미입력' && field.value !== null && field.value !== '';
                return `
                  <div style="background:white;border:1px solid #e5e7eb;border-radius:6px;padding:10px;">
                    <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">${field.label}</div>
                    <div style="font-size:14px;font-weight:500;color:${hasValue ? '#111827' : '#9ca3af'};word-break:break-word;">
                      ${hasValue ? field.value : '<span style="font-style:italic;">미입력</span>'}
                    </div>
                  </div>
                `;
              }).join('');
              
              const filledCount = fields.filter(f => f.value !== '미입력' && f.value !== null && f.value !== '').length;
              statusEl.textContent = `${filledCount}/${fields.length}개 필드 입력됨`;
              statusEl.style.color = filledCount > 0 ? '#10b981' : '#f59e0b';
            } else {
              throw new Error(data.error || '데이터 로드 실패');
            }
          } catch (error) {
            console.error('온보딩 데이터 로드 오류:', error);
            contentEl.innerHTML = `
              <div style="text-align:center;padding:20px;color:#ef4444;">
                <div>❌ 데이터를 불러올 수 없습니다.</div>
                <div style="font-size:12px;margin-top:8px;color:#6b7280;">오류: ${error.message || error}</div>
              </div>
            `;
            statusEl.textContent = '오류 발생';
            statusEl.style.color = '#ef4444';
          }
        }
        
        // 데이터 새로고침 버튼 핸들러
        const refreshBtn = panelEl.querySelector('#btn-refresh-onboarding-data');
        if (refreshBtn) {
          refreshBtn.addEventListener('click', () => {
            loadOnboardingData();
          });
        }
        
        // 초기 데이터 로드
        loadOnboardingData();

        // 리포트 생성 버튼 핸들러
        const reportBtn = panelEl.querySelector('#btn-generate-onboarding-report');
        if (reportBtn) {
          reportBtn.addEventListener('click', async () => {
            const originalText = reportBtn.textContent;
            reportBtn.disabled = true;
            reportBtn.textContent = '생성 중...';
            try {
              const params = new URLSearchParams();
              params.append('action', 'generateReportGPT');
              params.append('userid', String(studentId || ''));
              const endpoint = '/moodle/local/augmented_teacher/alt42/orchestration7/api/generate_onboarding_report.php';
              let res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                  'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: params.toString()
              });
              let json;
              try {
                json = await res.json();
              } catch (parseErr) {
                // POST 실패 시 GET으로 폴백 시도
                try {
                  const url = `${endpoint}?userid=${encodeURIComponent(String(studentId||''))}`;
                  res = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                  json = await res.json();
                } catch (e2) {
                  let preview = '';
                  try {
                    const txt = await res.text();
                    preview = txt.slice(0, 800).replace(/[\s\S]{200,}$/,'...').replace(/</g,'&lt;');
                  } catch(_) {}
                  const helpUrl = `${endpoint}?userid=${encodeURIComponent(String(studentId||''))}`;
                  openModalWithContent('온보딩 리포트', `
                    <div style=\"color:#ef4444;\">JSON 파싱 실패 (HTTP ${res && res.status ? res.status : 'n/a'}).<br>
                      아래 링크를 새 창에서 열어 응답을 확인해 주세요:<br>
                      <a href=\"${helpUrl}\" target=\"_blank\" style=\"color:#2563eb;\">API 응답 열기</a>
                      ${preview ? `<pre style=\"white-space:pre-wrap;word-break:break-word;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-top:8px;\">${preview}</pre>` : ''}
                    </div>
                  `);
                  throw e2;
                }
              }
              if (json && json.success && json.reportHTML) {
                openModalWithContent('온보딩 리포트', json.reportHTML);
              } else {
                const err = (json && (json.error || json.message)) ? String(json.error || json.message) : '알 수 없는 오류';
                const debugHtml = `
                  <div style=\"color:#ef4444;\">리포트 생성 실패: ${err}
                    <div style=\"margin-top:8px;color:#374151;\">요청: POST ${endpoint}</div>
                    <div style=\"margin-top:8px;\">
                      <a href=\"${endpoint}?userid=${encodeURIComponent(String(studentId||''))}\" target=\"_blank\" style=\"color:#2563eb;\">GET으로 직접 응답 확인</a>
                    </div>
                  </div>`;
                openModalWithContent('온보딩 리포트', debugHtml);
              }
            } catch (e) {
              const endpoint = '/moodle/local/augmented_teacher/alt42/orchestration7/api/generate_onboarding_report.php';
              openModalWithContent('온보딩 리포트', `
                <div style=\"color:#ef4444;\">리포트 요청 중 오류가 발생했습니다.<br>
                  오류: ${(e && (e.message || e))}
                  <div style=\"margin-top:8px;\">
                    <a href=\"${endpoint}?userid=${encodeURIComponent(String(studentId||''))}\" target=\"_blank\" style=\"color:#2563eb;\">API 응답 직접 열기</a>
                  </div>
                </div>
              `);
            } finally {
              reportBtn.disabled = false;
              reportBtn.textContent = originalText;
            }
          });
        }

        // 기존 모달용 MBTI UI를 패널 상단으로 렌더링
        if (typeof window.showAgent01Modal === 'function') {
          const overlay = document.getElementById('modal-overlay');
          const wrapper = document.getElementById('modal-content-wrapper') || (function(){const w=document.createElement('div');w.id='modal-content-wrapper';document.body.appendChild(w);return w;})();
          // 모달 백드롭 비활성화
          overlay.classList.remove('active');
          overlay.style.display = 'none';
          // 모달 호출로 내부 HTML 생성
          wrapper.innerHTML = '';
          window.showAgent01Modal();
          // 생성된 모달 콘텐츠를 추출하여 인라인 영역으로 이동
          const modalContent = wrapper.querySelector('.modal-content');
          const inline = document.getElementById('mbti-inline-wrapper');
          if (modalContent && inline) {
            // 헤더 텍스트 축소 및 불필요한 푸터 버튼 제거
            const footer = modalContent.querySelector('.modal-footer');
            if (footer) footer.remove();
            // 스크롤 높이 조정
            const bodyEl = modalContent.querySelector('.modal-body');
            if (bodyEl) bodyEl.style.maxHeight = 'none';
            // 컨테이너 스타일 단순화
            modalContent.style.width = '100%';
            modalContent.style.maxWidth = '100%';
            modalContent.style.padding = '16px';
            // 인라인 영역에 삽입
            inline.appendChild(modalContent);
          }
        }
      } catch (e) {
        console.error('renderAgent01Panel error:', e);
        panelEl.innerHTML = `<div style="padding: 20px; text-align: center; color: #ef4444;">❌ 온보딩 패널 임베드 실패</div>`;
      }
    }

    // Agent 02 패널: 시험일정 식별(오케스트레이션91 타임라인) 임베드
    function renderAgent02Panel(panelEl) {
      try {
        // 에이전트 전용 UI가 있으면 우선 사용 (버튼 클릭으로 전략 생성 지원)
        if (typeof window.renderAgent02PanelUI === 'function') {
          return window.renderAgent02PanelUI(panelEl);
        }

        // 폴백: 기존 오케스트레이션91 타임라인 임베드
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/2-exam-timeline/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">📅</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">시험일정 식별</div>
                <div style="color:#6b7280;font-size:13px;">Step 2</div>
              </div>
            </div>
            <iframe src="${src}" style="width:100%;min-height:80vh;border:0;border-radius:12px;background:#fff;" allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent02Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 시험일정 패널 임베드 실패</div>`;
      }
    }

    // Agent 06 패널 렌더 함수
    function renderAgent06Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/14-teacher-feedback/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">👨‍🏫</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">선생님 피드백</div>
                <div style="color:#6b7280;font-size:13px;">Step 6</div>
              </div>
            </div>
            <iframe src="${src}"
              style="width:100%;min-height:80vh;border:0;border-radius:12px;background:#fff;"
              allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
          </div>
        `;
      } catch (error) {
        console.error('[Agent06] Failed to embed orchestration91 teacher feedback:', error);
        panelEl.innerHTML = `
          <div style="padding: 20px; text-align: center; color: #ef4444;">
            <p>❌ 선생님 피드백 패널 임베드 실패</p>
            <p style="font-size: 12px; color: #6b7280;">File: index.php, Error: ${error && (error.message || error)}</p>
          </div>
        `;
      }
    }

    // Agent 07 패널: 상호작용 타게팅(오케스트레이션91) 임베드
    function renderAgent07Panel() {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/6-interaction-targeting/index.php?userid=${encodeURIComponent(studentId)}`;
        return `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">🔍</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">상호작용 타게팅</div>
                <div style="color:#6b7280;font-size:13px;">Step 7</div>
              </div>
            </div>
            <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling: touch;">
              <iframe src="${src}"
                style="width:1400px; min-height:80vh; border:0; border-radius:12px; background:#fff; display:block;"
                allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
            </div>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent07Panel error:', e);
        return `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 상호작용 타게팅 패널 임베드 실패</div>`;
      }
    }

    // Agent 15 패널: 문제 재정의 & 개선방안(오케스트레이션91) 임베드
    function renderAgent15Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/15-problem-redefinition/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">🎯</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">문제 재정의 & 개선방안</div>
                <div style="color:#6b7280;font-size:13px;">Step 15</div>
              </div>
            </div>
            <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling: touch;">
              <iframe src="${src}"
                style="width:1400px; min-height:80vh; border:0; border-radius:12px; background:#fff; display:block;"
                allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
            </div>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent15Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 문제 재정의 패널 임베드 실패</div>`;
      }
    }

    // Agent 16 패널: 상호작용 준비(오케스트레이션91) 임베드
    function renderAgent16Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/16-interaction-mode/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">🧭</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">상호작용 준비</div>
                <div style="color:#6b7280;font-size:13px;">Step 16</div>
              </div>
            </div>
            <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling: touch;">
              <iframe src="${src}"
                style="width:1400px; min-height:80vh; border:0; border-radius:12px; background:#fff; display:block;"
                allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
            </div>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent16Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 상호작용 준비 패널 임베드 실패</div>`;
      }
    }

    // Agent 17 패널: 잔여활동 조정(오케스트레이션91) 임베드
    function renderAgent17Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/17-remaining-activities/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">🚀</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">잔여활동 조정</div>
                <div style="color:#6b7280;font-size:13px;">Step 17</div>
              </div>
            </div>
            <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling: touch;">
              <iframe src="${src}"
                style="width:1400px; min-height:80vh; border:0; border-radius:12px; background:#fff; display:block;"
                allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
            </div>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent17Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 잔여활동 조정 패널 임베드 실패</div>`;
      }
    }

    // Agent 19 패널: 상호작용 컨텐츠 생성(오케스트레이션91) 임베드
    function renderAgent19Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/18-interaction-content/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">✨</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">상호작용 컨텐츠 생성</div>
                <div style="color:#6b7280;font-size:13px;">Step 19</div>
              </div>
            </div>
            <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling: touch;">
              <iframe src="${src}"
                style="width:1400px; min-height:80vh; border:0; border-radius:12px; background:#fff; display:block;"
                allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
            </div>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent19Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 상호작용 컨텐츠 생성 패널 임베드 실패</div>`;
      }
    }

    // Agent 04 패널: 문제활동 식별(오케스트레이션91) 임베드
    function renderAgent04Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/4-activity-identification/index.php?userid=${encodeURIComponent(studentId)}`;
        const ontologyUrl = `/moodle/local/augmented_teacher/alt42/orchestration/agents/math%20topics/ontology_visualizer.php?file=1%20numbers_ontology.owl`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">📚</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">문제활동 식별</div>
                <div style="color:#6b7280;font-size:13px;">Step 4</div>
              </div>
              <a href="${ontologyUrl}" target="_blank" 
                style="margin-left:auto;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;border:none;border-radius:6px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;box-shadow:0 2px 4px rgba(102, 126, 234, 0.3);transition:all 0.2s;"
                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 8px rgba(102, 126, 234, 0.5)'"
                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(102, 126, 234, 0.3)'"
                title="Math Topic Ontology 열기">
                🔗 Math Topic Ontology
              </a>
            </div>
            <iframe src="${src}"
              style="width:100%;min-height:80vh;border:0;border-radius:12px;background:#fff;"
              allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent04Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 문제활동 식별 패널 임베드 실패</div>`;
      }
    }

    // Agent 05 패널: 학습감정 분석(오케스트레이션91) 임베드
    function renderAgent05Panel(panelEl) {
      try {
        const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/5-emotion-analysis/index.php?userid=${encodeURIComponent(studentId)}`;
        panelEl.innerHTML = `
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:28px;">😊</div>
              <div>
                <div style="font-weight:700;color:#111827;font-size:18px;">학습감정 분석</div>
                <div style="color:#6b7280;font-size:13px;">Step 5</div>
              </div>
            </div>
            <iframe src="${src}"
              style="width:100%;min-height:80vh;border:0;border-radius:12px;background:#fff;"
              allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
          </div>
        `;
      } catch (e) {
        console.error('renderAgent05Panel error:', e);
        panelEl.innerHTML = `<div style=\"padding: 20px; text-align: center; color: #ef4444;\">❌ 학습감정 분석 패널 임베드 실패</div>`;
      }
    }
    function initPanelAdapters() {
      // 각 에이전트 모달 함수를 패널 렌더 함수로 어댑트
      const adapters = [
        { id:1,  modal:'showAgent01Modal', panel:'renderAgent01Panel' },
        { id:2,  modal:'showAgent02Modal', panel:'renderAgent02Panel' },
        { id:6,  modal:'showAgent06Modal', panel:'renderAgent06Panel' },
        { id:8,  modal:'showAgent08Modal', panel:'renderAgent08Panel' },
        { id:9,  modal:'showAgent09Modal', panel:'renderAgent09Panel' },
        { id:10, modal:'showAgent10Modal', panel:'renderAgent10Panel' },
        { id:11, modal:'showAgent11Modal', panel:'renderAgent11Panel' },
        { id:12, modal:'showAgent12Modal', panel:'renderAgent12Panel' },
        { id:13, modal:'showAgent13Modal', panel:'renderAgent13Panel' },
        { id:14, modal:'showAgent14Modal', panel:'renderAgent14Panel' }
      ];

      adapters.forEach(({ modal, panel }) => {
        if (typeof window[modal] === 'function' && typeof window[panel] !== 'function') {
          window[panel] = function(panelEl) {
            renderModalIntoPanel(() => window[modal]());
          };
        }
      });
    }

    // 상단 컨트롤 바 제거됨 (MBTI 선택 UI 유지)
  </script>

</body>
</html>
