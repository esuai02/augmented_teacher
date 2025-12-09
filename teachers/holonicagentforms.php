<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

// 추가: 표준업무 전환/해제 서버측 처리
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'toggleStandardTask':
            $taskid = isset($_POST['taskid']) ? intval($_POST['taskid']) : 0;
            $mode = $_POST['mode'] ?? 'apply'; // 'apply': 표준업무 적용, 'revert': 해제
            $record = $DB->get_record('agent_tasks', ['id' => $taskid, 'user_id' => $USER->id]);
            if ($record) {
                if ($mode === 'apply') {
                    $selectedRole = $_POST['role'] ?? '';
                    $record->role = $selectedRole;
                    $record->type = 'standard';
                } else {
                    $record->type = 'user';
                }
                $record->timemodified = time();
                $DB->update_record('agent_tasks', $record);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Task not found']);
            }
            exit();
            
        case 'getUserRoles':
            // 사용자의 역할 정보를 mdl_agent_user 테이블에서 가져오기
            $user_id = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;
            $sql = "SELECT * FROM {agent_user} WHERE user_id = ? ORDER BY timecreated DESC LIMIT 1";
            $userRoles = $DB->get_record_sql($sql, [$user_id]);
            
            $roles = [];
            if ($userRoles) {
                for ($i = 1; $i <= 12; $i++) {
                    $roleKey = 'role' . $i;
                    if (!empty($userRoles->$roleKey)) {
                        $roles[] = $userRoles->$roleKey;
                    }
                }
            }
            
            echo json_encode(['success' => true, 'roles' => $roles]);
            exit();
    }
}

// ---------------------------
// 1) GET 파라미터 및 사용자 정보
// ---------------------------
$userid = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;
$taskid = isset($_GET['taskid']) ? intval($_GET['taskid']) : 0;
$title  = isset($_GET['title']) ? htmlspecialchars($_GET['title'], ENT_QUOTES, 'UTF-8') : '업무 일정';
$currentRoles = [];
if(isset($_GET['role'])) {
    if(is_array($_GET['role'])) {
        $currentRoles = array_map('trim', $_GET['role']);
    } else {
        $currentRoles = array_map('trim', explode(',', $_GET['role']));
    }
}
if(empty($currentRoles)) {
    $currentRoles = ['teacher'];
}
$roleOptions = [];
foreach($currentRoles as $role) {
    if($role === 'admin') {
        $roleOptions[$role] = '관리자';
    } else if($role === 'teacher') {
        $roleOptions[$role] = '교사';
    } else if($role === 'projectmanager') {
        $roleOptions[$role] = '프로젝트 매니저';
    } else if($role === 'student') {
        $roleOptions[$role] = '학생';
    } else {
        $roleOptions[$role] = $role;
    }
}

// 예: 사용자 정보(이름/성) 조회
$sql_user = "SELECT lastname, firstname FROM {user} WHERE id = ?";
$thisuser = $DB->get_record_sql($sql_user, array($userid));
$username = isset($thisuser->lastname) ? $thisuser->lastname : '';

// ---------------------------
// 2) DB에서 기존 데이터 로드
//    (예: mdl_agent_dashboard_memos 테이블)
// ---------------------------
$sql_record = "SELECT * FROM {agent_dashboard_memos} WHERE user_id = ? AND taskid = ?";
$existing = $DB->get_record_sql($sql_record, array($userid, $taskid));

// 컬럼별로 초기 값 세팅 (null 방지)
$okrVal      = $existing ? $existing->okr      : '';
$kpiVal      = $existing ? $existing->kpi      : '';
$wxspertVal  = $existing ? $existing->wxspert  : '';
$memoVal     = $existing ? $existing->memo     : '';
$qstnVal     = $existing ? $existing->qstn     : '';
$prompt1Val  = $existing ? $existing->prompt1  : '';
$prompt2Val  = $existing ? $existing->prompt2  : '';
$prompt3Val  = $existing ? $existing->prompt3  : '';
$jsonfileVal = $existing ? $existing->jsonfile : '';

// 연관리소스 rsc1 ~ rsc9 데이터 로드
$rsc1Val = $existing && isset($existing->rsc1) ? $existing->rsc1 : '';
$rsc2Val = $existing && isset($existing->rsc2) ? $existing->rsc2 : '';
$rsc3Val = $existing && isset($existing->rsc3) ? $existing->rsc3 : '';
$rsc4Val = $existing && isset($existing->rsc4) ? $existing->rsc4 : '';
$rsc5Val = $existing && isset($existing->rsc5) ? $existing->rsc5 : '';
$rsc6Val = $existing && isset($existing->rsc6) ? $existing->rsc6 : '';
$rsc7Val = $existing && isset($existing->rsc7) ? $existing->rsc7 : '';
$rsc8Val = $existing && isset($existing->rsc8) ? $existing->rsc8 : '';
$rsc9Val = $existing && isset($existing->rsc9) ? $existing->rsc9 : '';

// HTML 안전 출력
function safe($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>BrainDumpingPlanner</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* 레이아웃 & 디자인 */
    html, body { height: 100%; margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6; }
    .container { display: flex; height: calc(100vh - 60px); max-width: 1800px; margin: 0 auto; padding: 1.5rem; gap: 1.5rem; box-sizing: border-box; }
    .header { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 1.5rem; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header .title { font-size: 1.8rem; font-weight: bold; }
    .header .home-icon { margin-left: 10px; font-size: 1.2rem; cursor: pointer; color: #3b82f6; }
    .header .home-icon:hover { color: #2563eb; }
    .time-display { display: flex; align-items: center; }
    .time-display span { margin-left: 0.5rem; }
    .sidebar { flex: 1; background-color: #f7fafc; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); overflow-y: auto; }
    .sidebar h2 { font-size: 1.3rem; margin-bottom: 0.75rem; }
    .poem-container { background-color: #eef2f7; border-left: 4px solid #3b82f6; padding: 1rem; margin-bottom: 1.5rem; font-style: italic; }
    .poem-container h2 { margin-top: 0; color: #3b82f6; font-size: 1.2rem; }
    .poem-container p { margin: 0.5rem 0; line-height: 1.4; }
    .poem-container .quote { margin-top: 1rem; text-align: right; font-weight: bold; color: #555; }
    .time-plan { margin-bottom: 1rem; }
    .time-slot { display: flex; align-items: center; border-bottom: 1px solid #e5e7eb; padding: 0.5rem 0; padding-left: 10px; }
    .time-label { width: 80px; font-weight: bold; }
    .time-slot-content { flex: 1; }
    .fold-toggle { margin-top: 0.75rem; cursor: pointer; color: #3b82f6; text-decoration: underline; }
    .main-column { flex: 3; background-color: #fff; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); overflow-y: auto; }
    .main-column h1 { font-size: 1.8rem; margin-bottom: 1rem; }
    .okr-kpi { margin-bottom: 1rem; }
    .okr-kpi input { width: 100%; padding: 0.5rem; font-size: 1rem; border: 1px solid #ccc; border-radius: 0.25rem; box-sizing: border-box; margin-bottom: 0.5rem; }
    .json-upload-container { display: flex; align-items: center; gap: 0.5rem; margin: 1rem 0; }
    .json-upload-container input[type="text"] { flex: 1; padding: 0.5rem; font-size: 0.9rem; }
    .json-upload-container button { white-space: nowrap; padding: 0.6rem 1rem; font-size: 0.9rem; background-color: #3b82f6; color: #fff; border: none; border-radius: 0.25rem; cursor: pointer; }
    .json-upload-container button:hover { background-color: #2563eb; }
    .tool-column { flex: 2; background-color: #f7fafc; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); overflow-y: auto; }
    .tool-column h2 { font-size: 1.3rem; margin-bottom: 0.75rem; }
    .draggable-section { margin-bottom: 1rem; }
    .draggable-section h3 { margin: 0 0 0.5rem 0; font-size: 1.1rem; }
    .draggable-list { list-style: none; padding: 0; }
    .draggable-list li { padding: 0.5rem; background-color: #fff; border: 1px solid #ddd; border-radius: 0.25rem; cursor: move; margin-bottom: 0.5rem; transition: all 0.3s ease; display: flex; align-items: center; justify-content: space-between; }
    .draggable-list li.over { background-color: #f0f8ff; }
    .item-input {
      flex: 1;
      border: none;
      font-size: 1rem;
      min-height: 24px;
      white-space: normal;
      height: auto;
      overflow: visible;
      line-height: 1.5;
      width: 100%;
      word-break: break-word;
      resize: vertical;
    }
    .item-input:focus {
      outline: none;
      background-color: #f9f9f9;
    }
    .btn-group {
      display: flex;
      margin-left: 8px;
      white-space: nowrap;
    }
    .btn-icon {
      width: 28px;
      height: 28px;
      border-radius: 4px;
      border: 1px solid #ddd;
      background-color: #f9fafb;
      color: #3b82f6;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-left: 4px;
      transition: all 0.2s ease;
    }
    .btn-icon:hover {
      background-color: #e5e7eb;
      transform: translateY(-2px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn-group button {
      margin-left: 4px;
      padding: 0.2rem 0.5rem;
      font-size: 0.8rem;
      cursor: pointer;
      border: 1px solid #ccc;
      border-radius: 0.2rem;
      background-color: #f9fafb;
    }
    .btn-group button:hover {
      background-color: #e5e7eb;
    }
    .brain-dump-memo {
      width: 100%;
      min-height: 150px;
      overflow: hidden;
      padding: 1rem;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 0.25rem;
      box-sizing: border-box;
      resize: vertical;
    }
    .tool-link { margin-left: 10px; cursor: pointer; color: #3b82f6; }
    .tool-link:hover { text-decoration: underline; }
    .saveIndicator { margin-left: 10px; font-size: 0.9rem; }
    
    /* 연관리소스 추가 버튼 스타일 */
    .add-resource-btn {
      display: inline-block;
      width: 24px;
      height: 24px;
      line-height: 20px;
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      color: #3b82f6;
      background-color: #f0f4ff;
      border: 1px solid #3b82f6;
      border-radius: 4px;
      cursor: pointer;
      margin-left: 8px;
      vertical-align: middle;
    }
    .add-resource-btn:hover {
      background-color: #3b82f6;
      color: white;
    }
    
    /* 리소스 항목 스타일 */
    .resource-item, .default-resource {
      padding: 8px 12px;
      margin-bottom: 5px;
      border-radius: 4px;
      cursor: move;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #fff;
      border: 1px solid #ddd;
      transition: all 0.2s ease;
    }
    
    .resource-item {
      border-left: 3px solid #3b82f6; /* 사용자 추가 항목은 파란색 테두리 */
      background-color: #f0f7ff;
    }
    
    .default-resource {
      border-left: 3px solid #6b7280; /* 기본 항목은 회색 테두리 */
    }
    
    .resource-item:hover, .default-resource:hover {
      transform: translateY(-2px);
      box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
    
    .resource-link {
      color: #3b82f6;
      text-decoration: none;
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .resource-link:hover {
      text-decoration: underline;
    }
    
    .delete-btn {
      margin-left: 8px;
      font-size: 18px;
      color: #ef4444;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .delete-btn:hover {
      transform: scale(1.2);
      color: #b91c1c;
    }
    
    /* 편집 가능한 영역 스타일 */
    .editable-area {
      position: relative;
      margin-bottom: 15px;
    }
    
    .display-area {
      padding: 10px;
      min-height: 30px;
      border: 1px dashed #ddd;
      border-radius: 4px;
      background-color: #f8fafc;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .display-area:hover {
      background-color: #f0f7ff;
      border-color: #3b82f6;
    }
    
    .display-area:hover::after {
      content: "✏️";
      position: absolute;
      right: 10px;
      top: 10px;
      font-size: 12px;
      color: #3b82f6;
      background-color: rgba(255, 255, 255, 0.8);
      padding: 2px 6px;
      border-radius: 4px;
    }
    
    .edit-area {
      width: 100%;
    }
    
    .edit-area input[type="text"],
    .edit-area textarea {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #3b82f6;
      border-radius: 4px;
      font-size: 1rem;
      box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
      outline: none;
    }
    
    /* 연관리소스 입력 폼 스타일 */
    .resource-input-row {
      display: flex;
      gap: 10px;
      margin-bottom: 8px;
    }
    
    .resource-input-group {
      flex: 1;
    }
    
    .resource-title-input, 
    .resource-url-input {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }
    
    .resource-title-input:focus, 
    .resource-url-input:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
      outline: none;
    }
    
    .resource-btn {
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      border: 1px solid #ccc;
      background-color: #f9fafb;
      transition: all 0.2s ease;
    }
    
    .resource-btn.save {
      background-color: #3b82f6;
      color: white;
      border-color: #2563eb;
      margin-right: 5px;
    }
    
    .resource-btn.cancel {
      background-color: #f9fafb;
      color: #374151;
    }
    
    .resource-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .resource-btn.save:hover {
      background-color: #2563eb;
    }
    
    .resource-btn.cancel:hover {
      background-color: #f3f4f6;
    }
    
    /* Agent Type Select 스타일 */
    #agentTypeSelect, #solidicityLevelSelect {
      font-size: 0.9rem;
      padding: 6px 12px;
      border: 1px solid #3b82f6;
      border-radius: 6px;
      background-color: white;
      color: #1f2937;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    #agentTypeSelect:hover, #solidicityLevelSelect:hover {
      border-color: #2563eb;
      box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    }
    
    #agentTypeSelect:focus, #solidicityLevelSelect:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    
    #agentTypeSelect option, #solidicityLevelSelect option {
      padding: 8px;
    }
  </style>
</head>
<body>
  <!-- 헤더 -->
  <div class="header">
    <div>
      <table width="100%"><tr><td><span class="home-icon" onclick="location.href='https://mathking.kr/moodle/local/augmented_teacher/teachers/holonicagentgame.php?userid=<?php echo $userid; ?>'">🏠</span> &nbsp;&nbsp; </td><td><a style="font-size:16px; color:blue;text-decoration:none;" href="https://chatgpt.com/g/g-67cdb8444ad481919cd13d03c63c6524-molibhwangyeong-setinghagi" target="_blank">WXSPERT GPT</a></td><td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://claude.ai/new" target="_blank">CLAUDE</a></td><td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://cjn7128.jandi.com/app/#!/room/" target="_blank">잔디</a></td><td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://notebooklm.google.com/" target="_blank">노트북</a></td>
      <td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://app.napkin.ai/" target="_blank">NAPKIN</a></td>
      <td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://www.perplexity.ai/" target="_blank">perplexity</a></td>
      <td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://aistudio.google.com/live" target="_blank">AI Studio</a></td>
      <td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://grok.com/?referrer=website" target="_blank">Grok</a></td>
      <td> | <a style="font-size:16px; color:blue;text-decoration:none;" href="https://gamma.app/create" target="_blank">GAMMA</a></td></tr></table>
    </div>
    <div class="time-display">
      <span id="clockIcon">🕒</span>  
      <span id="currentTime"></span>
    </div>
  </div>
  
  <div class="container">
    <!-- 좌측 사이드바 -->
    <div class="sidebar">
      <div class="poem-container">
        <h2>에이전트와 춤을</h2>
        <p>시간을 접어 압축성장!</p>
        <p>
          해야 할 일이 쌓여도<br>
          복잡한 과정에 매이지 않는다<br>
          반복되는 일은 자동으로,<br>
          우리는 더 중요한 것에 집중한다
        </p>
        <p>
          한 번의 클릭, 간단한 설정<br>
          흐름을 만들면 일이 자연스럽게 진행된다<br>
          에이전트가 도와주니<br>
          작업 속도는 빨라지고 실수는 줄어든다
        </p>
        <p>
          불필요한 고민 없이,<br>
          더 스마트하게, 더 효율적으로<br>
          우리는 더 나은 결과를 만들어간다
        </p>
        <p>
          일하는 방식을 바꾸면<br>
          시간은 더 자유로워지고<br>
          집중할 곳에 에너지를 쏟을 수 있다
        </p>
        <p class="quote">💡 "불필요한 복잡함을 덜어내면, 더 중요한 것에 집중할 수 있다."</p>
      </div>
      <h2>타임폴딩 일정표</h2>
      <div id="timePlanDashboard" class="time-plan">
        <div class="time-slot">
          <span class="time-label">09:00</span>
          <div class="time-slot-content">
            회의 준비 <span class="tool-link" onclick="openTool('https://chatgpt.com/?model=o3-mini')">🔗</span>
          </div>
        </div>
        <div class="time-slot">
          <span class="time-label">10:00</span>
          <div class="time-slot-content">
            보고서 작성 <span class="tool-link" onclick="openTool('https://chatgpt.com/?model=o3-mini')">🔗</span>
          </div>
        </div>
      </div>
      <div class="fold-toggle" onclick="toggleTimePlan()">숨기기</div>
    </div>
    
    <!-- 중앙 메인 영역 -->
    <div class="main-column">
      <h1 style="display: flex; align-items: center; justify-content: space-between;">
         <span><?php echo $title; ?></span>
         <div id="roleToggleContainer" style="display: flex; align-items: center;"> 
            <button id="standardTaskBtn" style="font-size: 1rem; padding: 0.5rem;">프로젝트 등록</button>
         </div>
      </h1>
      <input type="hidden" id="roleInput" name="role" value="">
      <input type="hidden" id="typeInput" name="taskType" value="">
      <div class="memo-area">
        <!-- OKR/KPI 영역 (메인 컬럼 내) -->
        <div class="okr-kpi">
          <input type="text" id="okrInput" name="okr" data-field="okr" onblur="autoSaveField(this)"
                 placeholder="OKR 입력"
                 value="<?php echo safe($okrVal); ?>">
          <span id="saveIndicator_okr" class="saveIndicator" style="display:none;"></span>
          
          <!-- KPI 영역 (클릭하면 편집 가능) -->
          <div class="editable-area" id="kpiContainer" style="margin-top:10px; position: relative;">
            <div id="kpiDisplay" class="display-area" onclick="showKpiEditor()"></div>
            <div id="kpiEditArea" class="edit-area" style="display: none;">
              <input type="text" id="kpiInput" name="kpi" data-field="kpi" 
                     onblur="hideKpiEditor()" 
                     placeholder="KPI 입력"
                     value="<?php echo safe($kpiVal); ?>">
              <span id="saveIndicator_kpi" class="saveIndicator" style="display:none;"></span>
            </div>
          </div>
        </div>
     
 

    

        <!-- WXSPERT 영역 (클릭하면 편집 가능) -->
        <div>
          <h3>WXSPERT 분석 리포트</h3>
          <div class="editable-area" id="wxspertContainer" style="position: relative;">
            <div id="wxspertDisplay" class="display-area" onclick="showWxspertEditor()"></div>
            <div id="wxspertEditArea" class="edit-area" style="display: none;">
              <textarea id="memoField" name="wxspert" data-field="wxspert" 
                        onblur="hideWxspertEditor()"
                        style="width:100%; min-height:150px; resize:vertical;" 
                        placeholder="이곳에서 WXSPERT 분석 리포트를 수정하세요"><?php echo safe($wxspertVal); ?></textarea>
              <span id="saveIndicator_wxspert" class="saveIndicator" style="display:none;"></span>
            </div>
          </div>
        </div>
        <!-- 임의 표시 -->
        <table>
          <tr>
            <td>WXS<b style="color:red;">PER</b>TA intelligence</td>
            <td><b style="color:#0066ff;">QPAR_DEFI_NE</b></td>
          </tr>
        </table>
        <table>
          <tr>
            <td><b style="color:#0066ff;">Q</b>uestion</td>
            <td><b style="color:#0066ff;">P</b>rompt design</td>
            <td><b style="color:#0066ff;">A</b>sk</td>
            <td><b style="color:#0066ff;">R</b>ead</td>
            <td>_</td>
            <td><b style="color:#0066ff;">D</b>evelop</td>
            <td><b style="color:#0066ff;">E</b>xecute</td>
            <td><b style="color:#0066ff;">F</b>eedback</td>
            <td><b style="color:#0066ff;">I</b>mprove</td>
            <td>_</td>
            <td><b style="color:#0066ff;">N</b>avigate</td>
            <td><b style="color:#0066ff;">E</b>valuate</td>
          </tr>
        </table>
                   
                     <div class="json-upload-container">
          <input type="text" id="jsonFileInput" name="jsonFile" placeholder="JSON 데이터를 입력하세요"
                 value="<?php echo safe($jsonfileVal); ?>">
          <button onclick="uploadJsonFile()">JSON 업로드</button>
          <button onclick="copyPrompt()" style="margin-left:10px;">프롬프트 복사</button>
        </div>
      </div>
    </div>
    
    <!-- 우측 사이드바 -->
    <div class="tool-column">
      <h2 style="display: flex; align-items: center; justify-content: space-between;">
        <span>Holonic 프레임워크</span>
        <div style="display: flex; gap: 10px; align-items: center;">
          <select id="agentTypeSelect" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd; background-color: white;">
            <option value="unitas">Unitas</option>
            <option value="astral">Astral</option>
            <option value="business">Business</option>
            <option value="project">Project</option>
            <option value="task">Task</option>
            <option value="drilling">Drilling</option>
            <option value="holonictool">Holonic Tool</option>
            <option value="moldingframe">moldingframe</option>
          </select>
          <select id="solidicityLevelSelect" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd; background-color: white;">
            <option value="level1">Level 1</option>
            <option value="level2">Level 2</option>
            <option value="level3">Level 3</option>
            <option value="level4">Level 4</option>
            <option value="level5">Level 5</option>
            <option value="level6">Level 6</option>
          </select>
        </div>
      </h2>
      <!-- 질문들: DB 값 세팅 -->
      <div class="draggable-section" id="questionsSection">
        <h3>본질적 질문</h3>
        <ul class="draggable-list">
          <li draggable="true" title="핵심 업무 목적 파악">
            <textarea class="item-input" name="qstn" data-field="qstn" onblur="autoSaveField(this)" 
                      placeholder="질문"><?php echo safe($qstnVal); ?></textarea>
            <div class="btn-group">
              <button type="button" class="btn-icon copy-btn" title="복사">📋</button>
            </div>
            <span id="saveIndicator_qstn" class="saveIndicator" style="display:none;"></span>
          </li>
        </ul>
      </div>
      
      <!-- GPT 프롬프트: DB 값 세팅 -->
      <div class="draggable-section" id="promptsSection">
        <h3>GPT 프롬프트</h3>
        <ul class="draggable-list">
          <li draggable="true" title="업무 목적 재정의">
            <textarea class="item-input" name="prompt1" data-field="prompt1" onblur="autoSaveField(this)" 
                      placeholder="프롬프트 1"><?php echo safe($prompt1Val); ?></textarea>
            <div class="btn-group">
              <button type="button" class="btn-icon copy-btn" title="복사">📋</button>
            </div>
            <span id="saveIndicator_prompt1" class="saveIndicator" style="display:none;"></span>
          </li>
          <li draggable="true" title="핵심 전략 도출">
            <textarea class="item-input" name="prompt2" data-field="prompt2" onblur="autoSaveField(this)" 
                      placeholder="프롬프트 2"><?php echo safe($prompt2Val); ?></textarea>
            <div class="btn-group">
              <button type="button" class="btn-icon copy-btn" title="복사">📋</button>
            </div>
            <span id="saveIndicator_prompt2" class="saveIndicator" style="display:none;"></span>
          </li>
          <li draggable="true" title="시장 분석 인사이트">
            <textarea class="item-input" name="prompt3" data-field="prompt3" onblur="autoSaveField(this)" 
                      placeholder="프롬프트 3"><?php echo safe($prompt3Val); ?></textarea>
            <div class="btn-group">
              <button type="button" class="btn-icon copy-btn" title="복사">📋</button>
            </div>
            <span id="saveIndicator_prompt3" class="saveIndicator" style="display:none;"></span>
          </li>
        </ul>
      </div>
      
      <!-- Brain Dumping Memo: DB 값 세팅 -->
      <div class="draggable-section" id="brainDumpSection">
        <h3>Brain dumping memo</h3>
        <textarea id="dashboardMemo" name="memo" class="brain-dump-memo" data-field="memo" onblur="autoSaveField(this)" placeholder="Brain dumping memo"><?php echo safe($memoVal); ?></textarea>
        <span id="saveIndicator_memo" class="saveIndicator" style="display:none;"></span>
      </div>
      
      <!-- 리소스 URL 저장을 위한 숨겨진 필드 -->
      <div style="display:none;">
        <input type="hidden" data-field="rsc1" name="rsc1" id="rsc1" value="<?php echo safe($rsc1Val); ?>">
        <span id="saveIndicator_rsc1" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc2" name="rsc2" id="rsc2" value="<?php echo safe($rsc2Val); ?>">
        <span id="saveIndicator_rsc2" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc3" name="rsc3" id="rsc3" value="<?php echo safe($rsc3Val); ?>">
        <span id="saveIndicator_rsc3" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc4" name="rsc4" id="rsc4" value="<?php echo safe($rsc4Val); ?>">
        <span id="saveIndicator_rsc4" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc5" name="rsc5" id="rsc5" value="<?php echo safe($rsc5Val); ?>">
        <span id="saveIndicator_rsc5" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc6" name="rsc6" id="rsc6" value="<?php echo safe($rsc6Val); ?>">
        <span id="saveIndicator_rsc6" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc7" name="rsc7" id="rsc7" value="<?php echo safe($rsc7Val); ?>">
        <span id="saveIndicator_rsc7" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc8" name="rsc8" id="rsc8" value="<?php echo safe($rsc8Val); ?>">
        <span id="saveIndicator_rsc8" class="saveIndicator"></span>
        
        <input type="hidden" data-field="rsc9" name="rsc9" id="rsc9" value="<?php echo safe($rsc9Val); ?>">
        <span id="saveIndicator_rsc9" class="saveIndicator"></span>
      </div>
      
      <div class="draggable-section" id="toolsSection">
        <h3>연관리소스 <button id="addResourceBtn" class="add-resource-btn">+</button></h3>
        <div id="resourceInputForm" style="display:none; margin-bottom:10px;">
          <div class="resource-input-row">
            <div class="resource-input-group">
              <input type="text" id="resourceTitleInput" placeholder="제목 입력" class="resource-title-input">
            </div>
            <div class="resource-input-group" style="flex: 2;">
              <input type="text" id="resourceUrlInput" placeholder="URL 입력 (https:// 포함)" class="resource-url-input">
            </div>
          </div>
          <div style="margin-top:8px; text-align: right;">
            <button id="saveResourceBtn" class="resource-btn save">저장</button>
            <button id="cancelResourceBtn" class="resource-btn cancel">취소</button>
          </div>
        </div>
        <ul class="draggable-list" id="resourcesList">
        
        </ul>
      </div>
    </div>
  </div>

  <script>
    // 외부 링크 열기
    function openTool(url) {
      window.open(url, "_blank");
    }

    // 시계
    function updateTime() {
      const now = new Date();
      document.getElementById("currentTime").textContent = now.toLocaleTimeString();
    }
    setInterval(updateTime, 1000);
    updateTime();
    
    // autoSaveField: 특정 필드 blur 시 서버에 저장
    function autoSaveField(el) {
      const fieldName = el.getAttribute('data-field');
      const value = el.value;
      
      // 텍스트영역 높이 자동 조절
      if (el.tagName === "TEXTAREA") {
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight) + 'px';
      }
      
      // KPI나 WXSPERT 필드인 경우 자동으로 읽기 모드 업데이트
      if (fieldName === "kpi") {
        updateKpiDisplay();
      } else if (fieldName === "wxspert") {
        updateWxspertDisplay();
      }
      
      autoSaveFieldValue(fieldName, value);
    }
    
    // autoSaveFieldValue: 서버에 AJAX 저장
    function autoSaveFieldValue(field, value) {
      console.log("[디버깅] " + field + " 저장 시도, 값:", value);
      
      const indicator = document.getElementById("saveIndicator_" + field);
      if (indicator) {
        indicator.textContent = "저장 중...";
        indicator.style.color = "orange";
        indicator.style.display = "inline";
      }
      
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "/moodle/local/augmented_teacher/teachers/update_field.php", true);
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      
      xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              if (indicator) {
                indicator.textContent = "저장됨";
                indicator.style.color = "green";
                setTimeout(() => { indicator.style.display = "none"; }, 3000);
              }
            } else {
              console.error("[디버깅] 저장 실패:", response.message);
              if (indicator) {
                indicator.textContent = "저장 실패";
                indicator.style.color = "red";
              }
            }
          } catch (e) {
            console.error("[디버깅] 응답 파싱 오류:", e);
            if (indicator) {
              indicator.textContent = "응답 오류";
              indicator.style.color = "red";
            }
          }
        } else {
          console.error("[디버깅] HTTP 오류:", xhr.status);
          if (indicator) {
            indicator.textContent = "저장 오류: " + xhr.status;
            indicator.style.color = "red";
          }
        }
      };
      
      const postData = {
        userid: <?php echo $userid; ?>,
        taskid: <?php echo $taskid; ?>,
        field: field,
        value: value
      };
      
      xhr.send(JSON.stringify(postData));
    }

    // 페이지 로드 시 실행
    document.addEventListener("DOMContentLoaded", function() {
      console.log("[단순화] DOM 로드 완료");
      
      // 페이지 로드 시 KPI와 WXSPERT 표시 업데이트
      updateKpiDisplay();
      updateWxspertDisplay();
      
      // 드래그 앤 드롭 설정
      setupDragAndDrop();
      
      // 리소스 로드
      setTimeout(loadResourcesSimple, 300);
      
      // 복사 버튼 이벤트 설정
      setupCopyButtons();
      
      // 텍스트 영역 자동 높이 조절
      adjustTextareaHeight();
      
      // + 버튼 클릭 이벤트
      const addBtn = document.getElementById("addResourceBtn");
      if (addBtn) {
        addBtn.addEventListener("click", function() {
          const form = document.getElementById("resourceInputForm");
          if (form) {
            form.style.display = "block";
          }
        });
      }
      
      // 저장 버튼
      const saveBtn = document.getElementById("saveResourceBtn");
      if (saveBtn) {
        saveBtn.addEventListener("click", function() {
          const urlInput = document.getElementById("resourceUrlInput");
          const titleInput = document.getElementById("resourceTitleInput");
          
          if (!urlInput || !titleInput) return;
          
          const url = urlInput.value.trim();
          let title = titleInput.value.trim();
          
          if (!url) {
            alert("URL을 입력하세요.");
            return;
          }
          if (!url.startsWith("http://") && !url.startsWith("https://")) {
            alert("URL은 http:// 또는 https:// 로 시작해야 합니다.");
            return;
          }
          if (!title) {
            title = url;
          }
          
          saveResource(url, title);
          
          // 입력 초기화
          urlInput.value = "";
          titleInput.value = "";
          
          // 폼 숨기기
          const form = document.getElementById("resourceInputForm");
          if (form) {
            form.style.display = "none";
          }
        });
      }
      
      // 취소 버튼
      const cancelBtn = document.getElementById("cancelResourceBtn");
      if (cancelBtn) {
        cancelBtn.addEventListener("click", function() {
          const urlInput = document.getElementById("resourceUrlInput");
          const titleInput = document.getElementById("resourceTitleInput");
          const form = document.getElementById("resourceInputForm");
          
          if (urlInput) urlInput.value = "";
          if (titleInput) titleInput.value = "";
          if (form) form.style.display = "none";
        });
      }
    });
    
    // 드래그 앤 드롭 설정
    function setupDragAndDrop() {
      const resourcesList = document.getElementById("resourcesList");
      if (!resourcesList) return;
      
      // 드래그 시작
      resourcesList.addEventListener("dragstart", function(e) {
        if (e.target.nodeName === "LI") {
          e.dataTransfer.effectAllowed = "move";
          e.dataTransfer.setData("text/plain", e.target.dataset.field || "default");
          e.target.style.opacity = "0.5";
          console.log("[드래그] 시작:", e.target.textContent);
        }
      });
      
      // 드래그 종료
      resourcesList.addEventListener("dragend", function(e) {
        if (e.target.nodeName === "LI") {
          e.target.style.opacity = "1";
          console.log("[드래그] 종료");
        }
      });
      
      // 드래그 오버
      resourcesList.addEventListener("dragover", function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
      });
      
      // 드랍
      resourcesList.addEventListener("drop", function(e) {
        e.preventDefault();
        const targetEl = e.target.closest("li");
        const draggedId = e.dataTransfer.getData("text/plain");
        
        if (targetEl && draggedId) {
          const draggedEl = draggedId === "default" 
            ? document.querySelector(`li.default-resource`) 
            : document.querySelector(`[data-field="${draggedId}"]`);
            
          if (draggedEl && draggedEl !== targetEl) {
            // 다른 항목 위에 드롭하면 해당 항목 앞에 삽입
            resourcesList.insertBefore(draggedEl, targetEl);
            console.log("[드래그] 위치 변경:", draggedEl.textContent);
          }
        }
      });
    }
    
    function saveResource(url, title) {
      // 비어있는 rsc 필드 찾기
      let availableField = null;
      for (let i = 1; i <= 9; i++) {
        const fieldName = "rsc" + i;
        const input = document.getElementById(fieldName);
        if (input && (!input.value || input.value.trim() === "")) {
          availableField = fieldName;
          break;
        }
      }
      if (!availableField) {
        alert("최대 9개의 리소스만 저장할 수 있습니다.");
        return;
      }
      
      // JSON 구성
      const jsonData = JSON.stringify({ url: url, title: title });
      
      // 서버 저장
      autoSaveFieldValue(availableField, jsonData);
      
      // 로컬 값
      const inputField = document.getElementById(availableField);
      inputField.value = jsonData;
      
      // UI 반영
      addResourceToUISimple(availableField, url, title);
    }
    
    // 단순화된 UI 추가 함수
    function addResourceToUISimple(fieldName, url, title) {
      console.log("[단순화] UI에 추가:", fieldName, url, title);
      
      const resourcesList = document.getElementById("resourcesList");
      if (!resourcesList) {
        console.error("[단순화] resourcesList 요소를 찾을 수 없음");
        return;
      }
      
      // 기존 항목 있으면 제거
      const existingItem = document.querySelector(`[data-field="${fieldName}"]`);
      if (existingItem) {
        existingItem.remove();
      }
      
      // 새 항목 생성
      const li = document.createElement("li");
      li.className = "resource-item"; // 이 클래스로 사용자 추가 항목 구분
      li.draggable = true;
      li.dataset.field = fieldName;
      
      li.innerHTML = `
        <a href="${url}" class="resource-link" target="_blank">${title}</a>
        <span class="delete-btn" onclick="deleteResource('${fieldName}')">&times;</span>
      `;
      
      // 기본 항목 위에 추가 (맨 위)
      const firstDefault = resourcesList.querySelector(".default-resource");
      if (firstDefault) {
        resourcesList.insertBefore(li, firstDefault);
      } else {
        resourcesList.appendChild(li);
      }
    }
    
    function deleteResource(fieldName) {
      if (!confirm("이 리소스를 삭제하시겠습니까?")) return;
      
      // 서버에 빈 값 저장
      autoSaveFieldValue(fieldName, "");
      
      // 로컬 필드 비우기
      const inputField = document.getElementById(fieldName);
      if (inputField) {
        inputField.value = "";
      }
      
      // UI 제거
      const item = document.querySelector(`[data-field="${fieldName}"]`);
      if (item) {
        item.remove();
      }
    }
    
    // 단순화된 리소스 로드 함수
    function loadResourcesSimple() {
      console.log("[단순화] 리소스 로드 시작");
      
      const resourcesList = document.getElementById("resourcesList");
      if (!resourcesList) {
        console.error("[단순화] resourcesList 요소를 찾을 수 없음");
        return;
      }
      
      // 기존 사용자 추가 항목만 제거 (기본 항목은 유지)
      const existingItems = resourcesList.querySelectorAll(".resource-item");
      existingItems.forEach(item => item.remove());
      console.log("[단순화] 사용자 추가 리소스 항목 제거됨");
      
      // 기존 기본 항목 확인
      const defaultItems = resourcesList.querySelectorAll(".default-resource");
      console.log("[단순화] 기본 항목 수:", defaultItems.length);
      
      // rsc1 ~ rsc9 필드 처리
      let foundResources = 0;
      for (let i = 1; i <= 9; i++) {
        const fieldName = "rsc" + i;
        const inputField = document.getElementById(fieldName);
        
        if (inputField && inputField.value && inputField.value.trim() !== "") {
          console.log("[단순화] 필드 값 발견:", fieldName, inputField.value);
          
          try {
            const data = JSON.parse(inputField.value);
            if (data && data.url && data.title) {
              console.log("[단순화] 데이터 파싱 성공:", data);
              addResourceToUISimple(fieldName, data.url, data.title);
              foundResources++;
            }
          } catch (e) {
            console.error("[단순화] 파싱 오류:", fieldName, e, "원본값:", inputField.value);
          }
        }
      }
      console.log("[단순화] 총 로드된 리소스 수:", foundResources);
    }
    
    // KPI 읽기 모드 업데이트
    function updateKpiDisplay() {
      var kpiValue = document.getElementById("kpiInput").value;
      var displayDiv = document.getElementById("kpiDisplay");
      
      if (!displayDiv) {
        console.error("[KPI] 표시 영역을 찾을 수 없음");
        return;
      }
      
      try {
        // JSON 형식인지 확인 시도
        var kpiData = JSON.parse(kpiValue);
        
        // KPI가 배열인 경우 목록 형식으로 출력
        if (Array.isArray(kpiData)) {
          var html = "<ul style='margin-left: 20px;'>";
          for (var i = 0; i < kpiData.length; i++) {
            html += "<li>" + kpiData[i] + "</li>";
          }
          html += "</ul>";
          displayDiv.innerHTML = html;
        } else {
          // JSON 파싱 결과 배열이 아니면 JSON 문자열 포맷으로 출력
          displayDiv.innerHTML = "<pre style='background:#f5f5f5; padding:10px; border-radius:4px;'>" + 
            JSON.stringify(kpiData, null, 2) + "</pre>";
        }
      } catch (e) {
        // JSON 파싱 실패 시, 일반 텍스트 출력
        if (kpiValue.trim() === "") {
          displayDiv.innerHTML = "<em style='color:#888;'>KPI 데이터가 없습니다. 이 영역을 클릭하여 입력하세요.</em>";
        } else {
          displayDiv.innerHTML = kpiValue.replace(/\n/g, "<br>");
        }
      }
    }
    
    // WXSPERT 읽기 모드 업데이트
    function updateWxspertDisplay() {
      var wxspertValue = document.getElementById("memoField").value;
      var displayDiv = document.getElementById("wxspertDisplay");
      
      if (!displayDiv) {
        console.error("[WXSPERT] 표시 영역을 찾을 수 없음");
        return;
      }
      
      try {
        // JSON 형식인지 확인 시도
        var wxspertData = JSON.parse(wxspertValue);
        
        // WXSPERT가 객체인 경우 키/값 테이블로 출력
        if (typeof wxspertData === "object" && wxspertData !== null) {
          // WXSPERT 항목에 대한 색상과 설명 정의
          const wxspertColors = {
            'W': {color: '#4285F4', desc: '세계관(Worldview)'},
            'X': {color: '#34A853', desc: '문맥지능(conteXt)'},
            'S': {color: '#FBBC05', desc: '구조지능(Structure))'},
            'P': {color: '#EA4335', desc: '절차지능(Process)'},
            'E': {color: '#9C27B0', desc: '실행지능(Execution)'},
            'R': {color: '#FF9800', desc: '성찰지능(Reflection)'},
            'T': {color: '#795548', desc: '전파지능(Transfer)'},
            'A': {color: '#607D8B', desc: '추상화(Abstraction)'}
          };
          
          // 테이블 스타일 개선
          var html = "<table style='width:100%; border-collapse:collapse; margin-top:10px; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);'>";
          
          // WXSPERT 항목이 있는 경우 전용 테이블 레이아웃 사용
          if (Object.keys(wxspertData).some(key => key.length === 1 && wxspertColors[key])) {
            // WXSPERT 전용 헤더
            html += "<tr style='background:#f0f8ff;'>" +
                    "<th colspan='2' style='padding:12px; text-align:center; font-size:16px; border-bottom:2px solid #ddd;'>" +
                    "WXSPERT 분석 결과</th></tr>";
            
            // WXSPERT 항목 출력
            Object.keys(wxspertData).forEach(key => {
              if (key.length === 1 && wxspertColors[key]) {
                const value = wxspertData[key];
                const colorInfo = wxspertColors[key];
                
                html += "<tr style='border-bottom:1px solid #eee;'>" +
                        "<td style='padding:12px; background:" + colorInfo.color + "20; width:120px;'>" +
                        "<div style='font-weight:bold; color:" + colorInfo.color + "; font-size:16px;'>" + key + "</div>" +
                        "<div style='font-size:12px; color:#666;'>" + colorInfo.desc + "</div></td>" +
                        "<td style='padding:12px;'>" + value + "</td></tr>";
              }
            });
            
            // 다른 키가 있으면 추가 섹션으로 출력
            const otherKeys = Object.keys(wxspertData).filter(key => !(key.length === 1 && wxspertColors[key]));
            if (otherKeys.length > 0) {
              html += "<tr><th colspan='2' style='padding:12px; background:#f5f5f5; text-align:left; border-top:2px solid #ddd;'>추가 정보</th></tr>";
              
              otherKeys.forEach(key => {
                let value = wxspertData[key];
                // 값이 객체인 경우 JSON 문자열로 변환
                if (typeof value === "object" && value !== null) {
                  value = JSON.stringify(value);
                }
                html += "<tr style='border-bottom:1px solid #eee;'>" +
                        "<td style='padding:12px; background:#f9f9f9; font-weight:bold;'>" + key + "</td>" +
                        "<td style='padding:12px;'>" + value + "</td></tr>";
              });
            }
          } else {
            // 일반 객체인 경우 기본 테이블 형식 출력
            html += "<tr style='background:#f0f7ff;'>" +
                    "<th style='padding:10px; border:1px solid #ddd; text-align:left; width:30%;'>항목</th>" +
                    "<th style='padding:10px; border:1px solid #ddd; text-align:left;'>내용</th></tr>";
            
            for (var key in wxspertData) {
              if (wxspertData.hasOwnProperty(key)) {
                let value = wxspertData[key];
                // 값이 객체인 경우 JSON 문자열로 변환
                if (typeof value === "object" && value !== null) {
                  value = JSON.stringify(value, null, 2);
                }
                html += "<tr style='border-bottom:1px solid #eee;'>" +
                        "<td style='padding:10px; border:1px solid #ddd; background:#f9f9f9; font-weight:bold;'>" + key + 
                        "</td><td style='padding:10px; border:1px solid #ddd;'>" + value + "</td></tr>";
              }
            }
          }
          
          html += "</table>";
          displayDiv.innerHTML = html;
        } else {
          // 객체가 아니면 JSON 문자열 포맷으로 출력
          displayDiv.innerHTML = "<pre style='background:#f5f5f5; padding:10px; border-radius:4px;'>" + 
            JSON.stringify(wxspertData, null, 2) + "</pre>";
        }
      } catch (e) {
        // JSON 파싱 실패 시, 일반 텍스트 출력
        if (wxspertValue.trim() === "") {
          displayDiv.innerHTML = "<em style='color:#888;'>WXSPERT 분석 데이터가 없습니다. 이 영역을 클릭하여 입력하세요.</em>";
        } else {
          // 줄바꿈을 <br>로 변환하여 출력
          displayDiv.innerHTML = wxspertValue.replace(/\n/g, "<br>");
        }
      }
    }

    // KPI 편집기 표시
    function showKpiEditor() {
      const displayArea = document.getElementById('kpiDisplay');
      const editArea = document.getElementById('kpiEditArea');
      const kpiInput = document.getElementById('kpiInput');
      
      // 표시 영역 숨기기, 편집 영역 표시
      displayArea.style.display = 'none';
      editArea.style.display = 'block';
      
      // 입력 필드에 포커스
      kpiInput.focus();
    }
    
    // KPI 편집기 숨기기
    function hideKpiEditor() {
      const displayArea = document.getElementById('kpiDisplay');
      const editArea = document.getElementById('kpiEditArea');
      const kpiInput = document.getElementById('kpiInput');
      
      // 저장 및 표시 업데이트
      autoSaveField(kpiInput);
      updateKpiDisplay();
      
      // 편집 영역 숨기기, 표시 영역 보이기
      editArea.style.display = 'none';
      displayArea.style.display = 'block';
    }
    
    // WXSPERT 편집기 표시
    function showWxspertEditor() {
      const displayArea = document.getElementById('wxspertDisplay');
      const editArea = document.getElementById('wxspertEditArea');
      const memoField = document.getElementById('memoField');
      
      // 표시 영역 숨기기, 편집 영역 표시
      displayArea.style.display = 'none';
      editArea.style.display = 'block';
      
      // 입력 필드에 포커스
      memoField.focus();
    }
    
    // WXSPERT 편집기 숨기기
    function hideWxspertEditor() {
      const displayArea = document.getElementById('wxspertDisplay');
      const editArea = document.getElementById('wxspertEditArea');
      const memoField = document.getElementById('memoField');
      
      // 저장 및 표시 업데이트
      autoSaveField(memoField);
      updateWxspertDisplay();
      
      // 편집 영역 숨기기, 표시 영역 보이기
      editArea.style.display = 'none';
      displayArea.style.display = 'block';
    }

    // JSON 업로드 예시
    function uploadJsonFile() {
      const jsonInput = document.getElementById("jsonFileInput");
      if (!jsonInput.value.trim()) {
        alert("JSON 데이터를 입력하세요.");
        return;
      }
      
      try {
        // JSON 문자열을 파싱하여 유효성 검사
        const jsonData = JSON.parse(jsonInput.value.trim());
        
        // 각 필드에 데이터 적용
        if (jsonData.okr) autoSaveFieldValue("okr", jsonData.okr);
        if (jsonData.kpi) autoSaveFieldValue("kpi", jsonData.kpi);
        if (jsonData.qstn) autoSaveFieldValue("qstn", jsonData.qstn);
        if (jsonData.prompt1) autoSaveFieldValue("prompt1", jsonData.prompt1);
        if (jsonData.prompt2) autoSaveFieldValue("prompt2", jsonData.prompt2);
        if (jsonData.prompt3) autoSaveFieldValue("prompt3", jsonData.prompt3);
        
        // WXSPERT 데이터가 있는 경우 처리
        if (jsonData.wxspert) {
          let wxspertText = '';
          
          // wxspert가 객체인 경우 JSON 문자열로 변환
          if (typeof jsonData.wxspert === 'object') {
            wxspertText = JSON.stringify(jsonData.wxspert, null, 2);
          } else {
            wxspertText = jsonData.wxspert;
          }
          
          autoSaveFieldValue("wxspert", wxspertText);
          updateWxspertDisplay(); // WXSPERT 표시 업데이트
        }
        
        // JSON 데이터 자체도 저장
        autoSaveFieldValue("jsonfile", jsonInput.value.trim());
        
        // 성공 메시지 표시
        Swal.fire({
          title: '데이터 적용 완료!',
          text: 'JSON 데이터가 성공적으로 적용되었습니다.',
          icon: 'success',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000
        });
        
        // 페이지 데이터 새로고침
        setTimeout(function() {
          location.reload();
        }, 2000);
        
      } catch (e) {
        // JSON 파싱 오류 발생 시
        Swal.fire({
          title: '오류 발생',
          text: 'JSON 형식이 올바르지 않습니다. 확인 후 다시 시도해주세요.',
          icon: 'error'
        });
      }
    }
    
    // 타임폴딩 일정표 접기/펼치기
    function toggleTimePlan() {
      const dashboard = document.getElementById("timePlanDashboard");
      const toggleLink = document.querySelector(".fold-toggle");
      
      if (dashboard.style.display === "none") {
        dashboard.style.display = "block";
        toggleLink.textContent = "숨기기";
      } else {
        dashboard.style.display = "none";
        toggleLink.textContent = "펼치기";
      }
    }

    // 복사 버튼 기능 설정
    function setupCopyButtons() {
      console.log("[복사] 복사 버튼 이벤트 설정");
      
      // 모든 복사 버튼에 이벤트 리스너 추가
      document.querySelectorAll('.copy-btn').forEach(function(button) {
        button.addEventListener('click', function() {
          // 해당 버튼의 부모 요소에서 input 필드 찾기
          const inputField = this.closest('li').querySelector('.item-input');
          
          if (inputField && inputField.value) {
            // 텍스트 복사하기
            navigator.clipboard.writeText(inputField.value)
              .then(function() {
                // 성공 시 SweetAlert2 알림
                Swal.fire({
                  title: '복사 완료!',
                  text: '클립보드에 복사되었습니다.',
                  icon: 'success',
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 1500
                });
              })
              .catch(function(err) {
                // 실패 시 SweetAlert2 알림
                console.error('복사 실패:', err);
                Swal.fire({
                  title: '복사 실패',
                  text: '클립보드 접근에 실패했습니다. 다시 시도해주세요.',
                  icon: 'error'
                });
              });
          } else {
            // 내용이 없을 때 SweetAlert2 알림
            Swal.fire({
              title: '복사할 내용 없음',
              text: '복사할 텍스트를 먼저 입력해주세요.',
              icon: 'warning'
            });
          }
        });
      });
    }

    // 텍스트 영역 자동 높이 조절 함수
    function adjustTextareaHeight() {
      document.querySelectorAll('textarea.item-input').forEach(function(textarea) {
        // 초기 높이를 설정
        textarea.style.height = 'auto';
        // 스크롤 높이에 따라 높이 조절 (최소 24px)
        textarea.style.height = Math.max(24, textarea.scrollHeight) + 'px';
        
        // 입력 시 높이 자동 조절
        textarea.addEventListener('input', function() {
          this.style.height = 'auto';
          this.style.height = Math.max(24, this.scrollHeight) + 'px';
        });
      });
    }

    // 새 프롬프트 생성 및 복사 함수 추가
    function copyPrompt() {
      const okr = document.getElementById('okrInput').value.trim();
      const kpi = document.getElementById('kpiInput').value.trim();
      const wxspert = document.getElementById('memoField').value.trim();
      const questionElem = document.querySelector('textarea[name="qstn"]');
      const question = questionElem ? questionElem.value.trim() : '';
      const prompt1Elem = document.querySelector('textarea[name="prompt1"]');
      const prompt1 = prompt1Elem ? prompt1Elem.value.trim() : '';
      const prompt2Elem = document.querySelector('textarea[name="prompt2"]');
      const prompt2 = prompt2Elem ? prompt2Elem.value.trim() : '';
      const prompt3Elem = document.querySelector('textarea[name="prompt3"]');
      const prompt3 = prompt3Elem ? prompt3Elem.value.trim() : '';
      const memo = document.getElementById('dashboardMemo').value.trim();
      const jsonFile = document.getElementById('jsonFileInput').value.trim();
      
      const promptText = `아래는 사용자가 입력한 정보입니다.

[OKR]
${okr || '[입력 없음]'}

[KPI]
${kpi || '[입력 없음]'}

[WXSPERT 분석]
${wxspert || '[입력 없음]'}

[본질적 질문]
${question || '[입력 없음]'}

[GPT 프롬프트]
1. 프롬프트 1: ${prompt1 || '[입력 없음]'}
2. 프롬프트 2: ${prompt2 || '[입력 없음]'}
3. 프롬프트 3: ${prompt3 || '[입력 없음]'}

[Brain Dumping Memo]
${memo || '[입력 없음]'}

[JSON 데이터]
${jsonFile || '[입력 없음]'}

위의 내용을 바탕으로 본질적 질문에 답하기 위해 보다 심층적인 전문가 관점으로 내용을 재 작성해 주세요`;
      
      navigator.clipboard.writeText(promptText)
        .then(function() {
          Swal.fire({
            title: '프롬프트 복사 완료!',
            text: '생성된 프롬프트가 클립보드에 복사되었습니다.',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        })
        .catch(function(error) {
          Swal.fire({
            title: '복사 실패',
            text: '프롬프트 복사에 실패하였습니다. 다시 시도해주세요.',
            icon: 'error'
          });
          console.error('프롬프트 복사 실패:', error);
        });
    }

    // 표준업무 버튼 토글 기능 추가
    var isStandardMode = false;
    document.getElementById("standardTaskBtn").addEventListener("click", function() {
      var taskid = <?php echo $taskid; ?>;
      if (!taskid) {
        alert("Task ID가 없습니다.");
        return;
      }
      
      if (!isStandardMode) {
        // 표준업무 모드로 전환
        // getUserRoles 액션을 통해 사용자 역할 정보 가져오기
        fetch("?action=getUserRoles&userid=<?php echo $userid; ?>")
          .then(function(res) { return res.json(); })
          .then(function(data) {
            if (data.success) {
              // 사용자 역할 목록
              var userRoles = data.roles || [];
              
              // 역할 목록이 비어있으면 기본 역할 사용
              if (userRoles.length === 0) {
                userRoles = ["Teacher", "Content Researcher", "App Developer", "Project Manager", "Content Creator", "Academy Manager"];
              }
              
              // SweetAlert2 팝업으로 역할 선택 UI 표시
              Swal.fire({
                title: '표준업무 적용을 위한 역할 선택',
                html: createRoleSelectionHtml(userRoles),
                showCancelButton: true,
                confirmButtonText: '적용',
                cancelButtonText: '취소',
                focusConfirm: false,
                preConfirm: () => {
                  // 선택된 역할 확인
                  const selectedRole = document.querySelector('input[name="role-option"]:checked');
                  if (!selectedRole) {
                    Swal.showValidationMessage('역할을 선택해주세요');
                    return false;
                  }
                  return selectedRole.value;
                }
              }).then((result) => {
                if (result.isConfirmed) {
                  // 선택된 역할로 표준업무 적용
                  const selectedRole = result.value;
                  isStandardMode = true;
                  
                  // 버튼 텍스트 변경
                  document.getElementById("standardTaskBtn").textContent = "프로젝트 해제";
                  
                  // 서버에 역할 업데이트 요청
                  var fd = new FormData();
                  fd.append("taskid", taskid);
                  fd.append("mode", "apply");
                  fd.append("role", selectedRole);
                  
                  fetch("?action=toggleStandardTask", { method: "POST", body: fd })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                      if (data.success) {
                        Swal.fire({
                          title: '적용 완료',
                          text: '표준업무로 설정되었습니다.',
                          icon: 'success',
                          timer: 1500,
                          showConfirmButton: false
                        });
                      } else {
                        console.error("오류: ", data.error);
                        Swal.fire({
                          title: '오류 발생',
                          text: '표준업무 적용 중 문제가 발생했습니다.',
                          icon: 'error'
                        });
                      }
                    })
                    .catch(function(err) { 
                      console.error(err);
                      Swal.fire({
                        title: '오류 발생',
                        text: '서버 통신 중 문제가 발생했습니다.',
                        icon: 'error'
                      });
                    });
                }
              });
            }
          })
          .catch(function(err) { 
            console.error("역할 정보 가져오기 실패:", err);
            Swal.fire({
              title: '오류 발생',
              text: '역할 정보를 가져오는 중 문제가 발생했습니다.',
              icon: 'error'
            });
          });
      } else {
        // 표준업무 모드 해제
        isStandardMode = false;
        this.textContent = "프로젝트로 등록";
        
        // 서버에 type을 user로 업데이트
        var fd = new FormData();
        fd.append("taskid", taskid);
        fd.append("mode", "revert");
        
        fetch("?action=toggleStandardTask", { method: "POST", body: fd })
          .then(function(res) { return res.json(); })
          .then(function(data) {
            if (data.success) {
              Swal.fire({
                title: '해제 완료',
                text: '표준업무가 해제되었습니다.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              });
            } else {
              console.error("표준업무 해제 오류:", data.error);
              Swal.fire({
                title: '오류 발생',
                text: '표준업무 해제 중 문제가 발생했습니다.',
                icon: 'error'
              });
            }
          })
          .catch(function(err) { 
            console.error(err);
            Swal.fire({
              title: '오류 발생',
              text: '서버 통신 중 문제가 발생했습니다.',
              icon: 'error'
            });
          });
      }
    });
    
    // 역할 선택 HTML 생성 함수
    function createRoleSelectionHtml(roles) {
      let html = '<div style="text-align:left; max-height:300px; overflow-y:auto;">';
      
      roles.forEach((role, index) => {
        html += `
          <div class="role-option" style="margin:10px 0; padding:10px; border:1px solid #eee; border-radius:5px; cursor:pointer; transition: all 0.2s ease;" 
               onclick="document.getElementById('role-${index}').checked = true;">
            <input type="radio" id="role-${index}" name="role-option" value="${role}" style="margin-right:10px;" ${index === 0 ? 'checked' : ''}>
            <label for="role-${index}" style="cursor:pointer; font-weight:${index === 0 ? 'bold' : 'normal'}; display:inline-block; width:calc(100% - 30px);">${role}</label>
          </div>
        `;
      });
      
      html += '</div>';
      
      // 스타일 추가
      html += `
        <style>
          .role-option:hover {
            background-color: #f3f9ff;
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
          }
          .role-option input[type="radio"]:checked + label {
            font-weight: bold;
            color: #3b82f6;
          }
        </style>
      `;
      
      return html;
    }
    
    // Agent Type 선택 이벤트 처리
    document.addEventListener("DOMContentLoaded", function() {
      const agentTypeSelect = document.getElementById("agentTypeSelect");
      const solidicityLevelSelect = document.getElementById("solidicityLevelSelect");
      
      if (agentTypeSelect) {
        agentTypeSelect.addEventListener("change", function() {
          const selectedType = this.value;
          console.log("선택된 Agent Type:", selectedType);
          
          // 선택된 타입에 따른 UI 업데이트
          updateUIByAgentType(selectedType);
          
          // SweetAlert2로 선택 알림
          Swal.fire({
            title: 'Agent Type 변경',
            text: `${selectedType} 모드로 전환되었습니다.`,
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        });
      }
      
      if (solidicityLevelSelect) {
        solidicityLevelSelect.addEventListener("change", function() {
          const selectedLevel = this.value;
          console.log("선택된 Solidicity Level:", selectedLevel);
          
          // 선택된 레벨에 따른 UI 업데이트
          updateUIBySolidicityLevel(selectedLevel);
          
          // SweetAlert2로 선택 알림
          Swal.fire({
            title: 'Solidicity Level 변경',
            text: `${selectedLevel} 레벨로 설정되었습니다.`,
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        });
      }
    });
    
    // Solidicity Level에 따른 UI 업데이트 함수
    function updateUIBySolidicityLevel(level) {
      // 레벨별 특수 처리
      switch(level) {
        case "level1":
          // Level 1 특화 UI
          break;
        case "level2":
          // Level 2 특화 UI
          break;
        case "level3":
          // Level 3 특화 UI
          break;
        case "level4":
          // Level 4 특화 UI
          break;
        case "level5":
          // Level 5 특화 UI
          break;
        case "level6":
          // Level 6 특화 UI
          break;
      }
    }
    
    // Agent Type에 따른 UI 업데이트 함수
    function updateUIByAgentType(type) {
      // 각 타입별로 다른 UI 요소 표시/숨김 처리
      const sections = {
        questionsSection: document.getElementById("questionsSection"),
        promptsSection: document.getElementById("promptsSection"),
        brainDumpSection: document.getElementById("brainDumpSection")
      };
      
      // 기본적으로 모든 섹션 표시
      Object.values(sections).forEach(section => {
        if (section) section.style.display = "block";
      });
      
      // 타입별 특수 처리
      switch(type) {
        case "unitas":
          // Unitas 특화 UI
          break;
        case "astral":
          // Astral 특화 UI
          break;
        case "business":
          // Business 특화 UI
          break;
        case "project":
          // Project 특화 UI
          break;
        case "task":
          // Task 특화 UI
          break;
        case "drilling":
          // Drilling 특화 UI
          break;
        case "moldingtool":
          // Molding Tool 특화 UI
          break;
        case "holonic":
          // Holonic 특화 UI
          break;
      }
    }
  </script>
</body>
</html>
