<?php
/////////////////////////////// PHP 초기 설정 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 사용자 ID 결정 - 보안 향상을 위해 intval 적용
$userid = isset($_GET["userid"]) ? intval($_GET["userid"]) : $USER->id;
$timecreated = time(); 
$halfdayago = $timecreated - 43200;

// 사용자 정보 캐싱 메커니즘 추가
$cache_key = "user_info_" . $userid;
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", [$userid]);
$username = $thisuser->lastname ?? '';

// 사용자 역할 정보 조회 (예: user_info_data)
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22", [$USER->id]);
$role = $userrole->role ?? '';

// -------------------------------------------------------------
// AJAX 요청 처리: action 파라미터에 따라 DB 연동
// -------------------------------------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    try {
        switch($action) {
            // A) 가장 최근에 저장된 mdl_agent_user 레코드 불러오기
            case 'getLatestRoles':
                $latest = $DB->get_record_sql(
                    "SELECT role1, role2, role3, role4, role5, role6, role7, role8, role9, role10, role11, role12
                       FROM mdl_agent_user
                      WHERE user_id = ?
                   ORDER BY timecreated DESC
                      LIMIT 1", 
                    [$userid]
                );
                
                echo json_encode($latest ?: null);
                break;

            // B) 역할 저장 (role1~role12)
            case 'saveRoles':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Invalid request method');
                }
                
                $newdata = new stdClass();
                $newdata->user_id = $userid;
                $newdata->timecreated = time();
                
                // 효율적인 루프 처리
                for ($i = 1; $i <= 12; $i++) {
                    $param = 'role' . $i;
                    $newdata->$param = isset($_POST[$param]) && $_POST[$param] !== '' ? $_POST[$param] : null;
                }
                
                $DB->insert_record('agent_user', $newdata);
                echo json_encode(['success' => true]);
                break;

            // *** [새로 추가] 역할들에 해당하는 업무 목록 가져오기 ***
            case 'getRoleTasks':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Invalid request method');
                }
                
                $rolesArr = $_POST['roles'] ?? [];
                if (!is_array($rolesArr) || empty($rolesArr)) {
                    echo json_encode([]);
                    break;
                }
                
                $placeholders = implode(',', array_fill(0, count($rolesArr), '?'));
                $sql = "SELECT DISTINCT s.task
                          FROM mdl_agent_toolsettings s
                          JOIN mdl_agent_usertoolsettings u ON s.id = u.agent_toolsetting_id
                         WHERE s.role IN ($placeholders)
                           AND u.user_id = ?
                           AND u.checked = 1
                      ORDER BY s.task";
                $params = array_merge($rolesArr, [$userid]);
                $records = $DB->get_records_sql($sql, $params);
                
                // 배열 변환 최적화
                $tasks = array_map(function($r) { return $r->task; }, $records);
                echo json_encode($tasks);
                break;

            // 1) 현재 사용자(userid)의 모든 작업 불러와 상태별 분류 (done도 추가)
            case 'getTasks':
                // 한 번의 쿼리로 모든 데이터 가져오기
                $sql = "SELECT id, title, content, url, completed, status, 
                               scheduled_hour, scheduled_minute, color
                          FROM mdl_agent_tasks 
                         WHERE user_id = ?
                      ORDER BY timecreated DESC";
                $records = $DB->get_records_sql($sql, [$userid]);
                
                // 초기화 - 필요한 배열만 미리 생성
                $result = [
                    'brainDumpItems' => [],
                    'todoList' => [],
                    'timePlan' => [],
                    'doneList' => []
                ];
                
                // 한 번의 루프로 모든 항목 처리
                foreach ($records as $r) {
                    $item = [
                        'id' => $r->id,
                        'title' => $r->title,
                        'content' => $r->content,
                        'url' => $r->url,
                        'completed' => (bool)$r->completed,
                        'hour' => $r->scheduled_hour,
                        'minute' => $r->scheduled_minute,
                        'color' => $r->color
                    ];
                    
                    // 적절한 배열에 추가
                    if ($r->status === 'brain_dump') {
                        $result['brainDumpItems'][] = $item;
                    } else if ($r->status === 'todo') {
                        $result['todoList'][] = $item;
                    } else if ($r->status === 'time_plan') {
                        $result['timePlan'][] = $item;
                    } else if ($r->status === 'done') {
                        $result['doneList'][] = $item;
                    }
                }
                
                echo json_encode($result);
                break;

            // 2) 새 브레인덤프 항목 추가 - 입력 검증 강화
            case 'addBrainDumpItem':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Invalid request method');
                }
                
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                
                if (empty($title)) {
                    throw new Exception('Title is required');
                }
                
                $newdata = new stdClass();
                $newdata->user_id = $userid;
                $newdata->title = $title;
                $newdata->content = $content;
                $newdata->url = '';
                $newdata->status = 'brain_dump';
                $newdata->completed = 0;
                $newdata->scheduled_hour = null;
                $newdata->scheduled_minute = null;
                $newdata->color = null;
                $newdata->timecreated = $newdata->timemodified = time();
                
                $id = $DB->insert_record('agent_tasks', $newdata);
                echo json_encode(['success' => true, 'id' => $id]);
                break;

            // 나머지 케이스들도 유사한 패턴으로 최적화
            case 'moveToTodo':
            case 'moveToTimePlan':
            case 'moveToDone':
            case 'deleteItem':
            case 'completeItem':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Invalid request method');
                }
                
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                if ($id <= 0) {
                    throw new Exception('Invalid ID');
                }
                
                $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
                if (!$record) {
                    throw new Exception('Record not found');
                }
                
                // 각 액션에 따른 처리
                switch ($action) {
                    case 'moveToTodo':
                        $record->status = 'todo';
                        $record->completed = 0;
                        $record->scheduled_hour = null;
                        $record->scheduled_minute = null;
                        $record->color = null;
                        break;
                        
                    case 'moveToTimePlan':
                        $hour = isset($_POST['hour']) ? intval($_POST['hour']) : 0;
                        $minute = isset($_POST['minute']) ? intval($_POST['minute']) : 0;
                        $color = $_POST['color'] ?? 'bg-blue-200';
                        
                        // 값 검증
                        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
                            throw new Exception('Invalid time');
                        }
                        
                        $record->status = 'time_plan';
                        $record->completed = 0;
                        $record->scheduled_hour = $hour;
                        $record->scheduled_minute = $minute;
                        $record->color = $color;
                        break;
                        
                    case 'moveToDone':
                        $record->status = 'done';
                        $record->completed = 1;
                        break;
                        
                    case 'deleteItem':
                        $DB->delete_records('agent_tasks', ['id' => $id]);
                        echo json_encode(['success' => true]);
                        break;
                        
                    case 'completeItem':
                        $record->completed = 1;
                        break;
                }
                
                // deleteItem은 이미 처리됨
                if ($action !== 'deleteItem') {
                    $record->timemodified = time();
                    $DB->update_record('agent_tasks', $record);
                    echo json_encode(['success' => true]);
                }
                break;

            // *** 직접 입력된 TASK를 role='my' 로 mdl_agent_toolsettings 에 추가
            case 'addMyTask':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Invalid request method');
                }
                
                $task = trim($_POST['task'] ?? '');
                if (empty($task)) {
                    throw new Exception('Task is required');
                }
                
                $newTool = new stdClass();
                $newTool->role = 'my';
                $newTool->task = $task;
                $newTool->description = '';
                $newTool->url = '';
                $newTool->timecreated = $newTool->timemodified = time();
                
                $id = $DB->insert_record('agent_toolsettings', $newTool);
                echo json_encode(['success' => true, 'id' => $id]);
                break;

            // [추가] usertoolsettings DB에서 checked=1 인 항목만 조회
            case 'getCheckedTools':
                // 최적화된 SQL 쿼리 - 필요한 필드만 선택
                $sql = "
                    SELECT s.id, s.role, s.task, s.description, s.url
                      FROM mdl_agent_toolsettings s
                      JOIN mdl_agent_usertoolsettings u ON s.id = u.agent_toolsetting_id
                     WHERE u.user_id = ? AND u.checked = 1
                  ORDER BY s.id ASC
                ";
                $records = $DB->get_records_sql($sql, [$userid]);
                
                // 배열 변환 최적화
                $result = array_map(function($r) {
                    return [
                        'id' => $r->id,
                        'role' => $r->role,
                        'task' => $r->task,
                        'description' => $r->description,
                        'url' => $r->url
                    ];
                }, $records);
                
                echo json_encode($result);
                break;

            default:
                throw new Exception('Unknown action: ' . $action);
        }
    } catch (Exception $e) {
        // 오류 로깅 및 응답
        error_log('Error in Time Catcher Game: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Time Catcher Game</title>
  <style>
    /* 전체 스타일 최적화 - 불필요한 중복 제거 */
    :root {
      --primary-color: #3b82f6;
      --bg-color: #f3f4f6;
      --card-bg: #fff;
      --border-color: #e5e7eb;
      --text-color: #333;
      --shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: Arial, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
    }
    
    .container {
      max-width: 72rem;
      margin: 0 auto;
      padding: 1.5rem;
      background-color: var(--card-bg);
      border-radius: 0.5rem;
      box-shadow: var(--shadow);
    }
    
    /* 헤더 영역 */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .title {
      font-size: 1.5rem;
      font-weight: bold;
    }
    
    .header-buttons {
      margin-left: auto;
      display: flex;
    }
    
    .header-buttons button {
      margin-right: 10px;
      padding: 0.3rem 0.6rem;
      font-size: 0.9rem;
      cursor: pointer;
      border: none;
      background-color: #f2f2f2;
      color: black;
      border-radius: 0.25rem;
      transition: background-color 0.2s;
    }
    
    .header-buttons button:hover {
      background-color: #e0e0e0;
    }
    
    .header-buttons button .icon {
      margin-right: 5px;
    }
    
    .time-display {
      display: flex;
      align-items: center;
    }
    
    .time-display span {
      margin-left: 0.5rem;
    }
    
    /* 알림 영역 */
    .alert {
      border: 1px solid red;
      padding: 0.5rem;
      border-radius: 0.5rem;
      margin-bottom: 1rem;
      color: red;
      display: none;
    }
    
    /* 그리드 레이아웃 */
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }
    
    .section {
      background-color: #f7fafc;
      padding: 1rem;
      border-radius: 0.5rem;
    }
    
    .section h2 {
      font-size: 1.125rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    
    /* Brain Dump 영역 */
    #brainDumpContainer {
      position: relative;
      height: 384px;
      border: 1px dashed #aaa;
      overflow: hidden;
      border-radius: 0.5rem;
    }
    
    .brain-dump-item {
      position: absolute;
      background-color: var(--card-bg);
      padding: 0.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      cursor: pointer;
      z-index: 1;
      transition: transform 0.3s, box-shadow 0.3s;
      user-select: none;
    }
    
    .brain-dump-item:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 8px rgba(0,0,0,0.15);
    }
    
    .gpt-icon, .check-icon {
      margin-left: 5px;
      font-size: 1rem;
      cursor: pointer;
    }
    
    .gpt-icon:hover, .check-icon:hover {
      color: var(--primary-color);
    }
    
    /* ToDo 영역 */
    .todo-slot {
      height: 3rem;
      border: 2px dashed var(--border-color);
      border-radius: 0.5rem;
      background-color: #f9fafb;
      display: flex;
      align-items: center;
      padding: 0 1rem;
      margin-bottom: 0.5rem;
      transition: all 0.2s;
    }
    
    .todo-slot.filled {
      border: 1px solid #ccc;
      background-color: var(--card-bg);
    }
    
    .todo-slot:hover {
      transform: scale(1.02);
    }
    
    .todo-form {
      display: flex;
      gap: 5px;
      margin-top: 0.5rem;
    }
    
    .todo-form input {
      padding: 0.25rem 0.5rem;
      border: 1px solid #ccc;
      font-size: 0.875rem;
      border-radius: 0.25rem;
    }
    
    .todo-form input:focus {
      outline: 1px solid var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .content-input {
      width: 60%;
      height: 2rem;
    }
    
    .todo-form button {
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 0.25rem;
      font-size: 0.875rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .todo-form button:hover {
      background-color: #2563eb;
    }
    
    .title-input {
      width: 30%;
      height: 2rem;
    }
    
    #todoTitleButton {
      width: 100%;
      height: 100%;
      font-size: 0.875rem;
      cursor: pointer;
    }
    
    /* 타임플랜 영역 */
    #timePlanContainer {
      background-color: #f7fafc;
      padding: 1rem;
      border-radius: 0.5rem;
      max-height: 800px;
      overflow-y: auto;
    }
    
    .time-slot {
      display: flex;
      align-items: center;
      font-size: 0.875rem;
      border-bottom: 1px solid var(--border-color);
      padding: 0.25rem 0;
      padding-left: 10px;
    }
    
    .time-label {
      width: 4rem;
      font-weight: 500;
    }
    
    .time-slot-content {
      flex: 1;
      min-height: 2rem;
      display: flex;
      align-items: center;
      flex-wrap: nowrap;
    }
    
    .time-plan-item {
      padding: 0.5rem;
      margin: 2px;
      border-radius: 0.25rem;
      transition: transform 0.2s;
    }
    
    .time-plan-item:hover {
      transform: scale(1.05);
    }
    
    /* 색상 클래스 통합 */
    .bg-pink-200 { background-color: #fbcfe8; }
    .bg-yellow-200 { background-color: #fef08a; }
    .bg-purple-200 { background-color: #e9d5ff; }
    .bg-blue-200 { background-color: #bfdbfe; }
    .bg-green-200 { background-color: #bbf7d0; }
    
    /* 모달 스타일 */
    #modalOverlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    
    #modalDialog {
      background: var(--card-bg);
      padding: 1.5rem;
      border-radius: 0.5rem;
      width: 320px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    #modalDialog h2 {
      margin-top: 0;
      margin-bottom: 1rem;
    }
    
    #modalDialog .modal-buttons {
      margin-top: 1.5rem;
      text-align: right;
    }
    
    #modalDialog button {
      padding: 0.5rem 1rem;
      margin-left: 0.5rem;
      border: none;
      border-radius: 0.25rem;
      cursor: pointer;
    }
    
    #cancelDelete {
      background-color: #f3f4f6;
    }
    
    #confirmDelete {
      background-color: #ef4444;
      color: white;
    }
    
    /* 체크된 도구 컨테이너 */
    #checkedToolsContainer {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 0.5rem;
      min-height: 50px;
      background-color: #fafafa;
      margin-top: 1rem;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="container">
    <!-- 상단 헤더 -->
    <div class="header">
      <div class="title">Brain Dump & Time Catcher Game</div>
      &nbsp;&nbsp;&nbsp; <span id="selectedRoles"></span>
      <div class="header-buttons">
        <button id="fetchJournalButton"><span class="icon">📄</span>일지</button>
        <button id="fetchRecommendedButton"><span class="icon">⭐</span>업무</button>
        <button id="changeRoleButton"><span class="icon">🎭</span>역할</button>
        <!-- [추가] 체크메뉴 버튼 -->
        <button id="fetchCheckedToolsButton"><span class="icon">✔️</span>체크메뉴</button>
      </div>
      <div class="time-display">
        <span id="clockIcon">🕒</span>
        <span id="currentTime"></span>
      </div>
    </div>

    <div id="alert" class="alert">⚠️ Todo List가 가득 찼습니다. 기존 항목을 완료한 후 새로운 항목을 추가해주세요.</div>
    <div class="grid">
      <!-- 좌측: Brain Dump & ToDo -->
      <div>
        <div class="section">
          <h2>Brain Dump</h2>
          <div id="brainDumpContainer"></div>
        </div>
        <div class="section" style="margin-top: 1rem;">
          <h2>To Do List (<span id="todoCount">0</span>/3)</h2>
          <div id="todoListContainer"></div>
          <form id="todoForm" class="todo-form">
            <div id="titleContainer" class="title-input">
              <button type="button" id="todoTitleButton">TASK 선택</button>
            </div>
            <input type="text" id="todoContent" class="content-input" placeholder="내용입력" required>
            <button type="submit">추가</button>
          </form>
        </div>
      </div>

      <!-- 우측: Time Plan -->
      <div id="timePlanContainer">
        <h2>Time Boxing Planner</h2>
        <div id="timeSlotsContainer"></div>
      </div>
    </div>

    <!-- [추가] 체크된 도구 표시 섹션 -->
    <div class="section" style="margin-top:1rem;">
      <h2>Checked Tools</h2>
      <div id="checkedToolsContainer">체크된 항목을 확인하려면 [체크메뉴] 버튼을 클릭하세요.</div>
    </div>
  </div>

  <!-- 삭제 확인 모달 -->
  <div id="modalOverlay">
    <div id="modalDialog">
      <h2>항목 삭제</h2>
      <p id="modalMessage"></p>
      <div class="modal-buttons">
        <button id="cancelDelete">취소</button>
        <button id="confirmDelete">삭제</button>
      </div>
    </div>
  </div>

  <script>
    // 성능 최적화된 JavaScript
    document.addEventListener("DOMContentLoaded", function() {
      // ----- 상태 관리 변수들 -----
      let brainDumpItems = [];
      let todoList = [];
      let timePlan = [];
      let doneList = [];
      let checkedTools = [];
      
      let selectedTitle = "";
      let currentTime = new Date();
      let currentTimeSlot = 0;
      let dragItem = null;
      let deleteCandidate = null;
      let animationFrameId = null;
      
      // 메모리 최적화를 위한 캐싱된 DOM 요소들
      const DOM = {
        currentTime: document.getElementById("currentTime"),
        alert: document.getElementById("alert"),
        brainDumpContainer: document.getElementById("brainDumpContainer"),
        todoListContainer: document.getElementById("todoListContainer"),
        todoCount: document.getElementById("todoCount"),
        todoForm: document.getElementById("todoForm"),
        todoContent: document.getElementById("todoContent"),
        todoTitleButton: document.getElementById("todoTitleButton"),
        timeSlotsContainer: document.getElementById("timeSlotsContainer"),
        modalOverlay: document.getElementById("modalOverlay"),
        modalMessage: document.getElementById("modalMessage"),
        cancelDelete: document.getElementById("cancelDelete"),
        confirmDelete: document.getElementById("confirmDelete"),
        timePlanContainer: document.getElementById("timePlanContainer"),
        checkedToolsContainer: document.getElementById("checkedToolsContainer"),
        selectedRoles: document.getElementById("selectedRoles"),
        fetchCheckedToolsButton: document.getElementById("fetchCheckedToolsButton"),
        changeRoleButton: document.getElementById("changeRoleButton")
      };
      
      // 색상 배열 - 외부로 이동하여 메모리 절약
      const colors = ['bg-pink-200', 'bg-yellow-200', 'bg-purple-200', 'bg-blue-200', 'bg-green-200'];
      
      // ----- 유틸리티 함수들 -----
      
      // 무작위 색상 선택
      function getRandomColor() {
        return colors[Math.floor(Math.random() * colors.length)];
      }
      
      // Fetch API를 사용한 데이터 요청 함수 (중복 코드 제거)
      async function fetchData(action, method = 'GET', data = null) {
        try {
          const options = {
            method: method
          };
          
          // POST 요청일 경우 FormData 추가
          if (method === 'POST' && data) {
            options.body = data;
          }
          
          const url = `?action=${action}${method === 'GET' && data ? '&' + new URLSearchParams(data).toString() : ''}`;
          const response = await fetch(url, options);
          
          if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status}`);
          }
          
          return await response.json();
        } catch (error) {
          console.error(`Error fetching ${action}:`, error);
          // SweetAlert2로 사용자에게 오류 표시
          Swal.fire({
            icon: 'error',
            title: '오류가 발생했습니다',
            text: error.message || '서버와 통신 중 문제가 발생했습니다.',
            confirmButtonText: '확인'
          });
          return null;
        }
      }
      
      // 스로틀링 함수 (이벤트 제한)
      function throttle(func, delay) {
        let lastCall = 0;
        return function(...args) {
          const now = new Date().getTime();
          if (now - lastCall < delay) {
            return;
          }
          lastCall = now;
          return func.apply(this, args);
        };
      }
      
      // 디바운스 함수 (이벤트 제한)
      function debounce(func, delay) {
        let timerId;
        return function(...args) {
          clearTimeout(timerId);
          timerId = setTimeout(() => {
            func.apply(this, args);
          }, delay);
        };
      }
      
      // ----- 데이터 페칭 함수들 -----
      
      // 역할 가져오기
      async function fetchRoles() {
        const data = await fetchData('getLatestRoles');
        if (!data) return;
        
        let roles = [];
        for (let i = 1; i <= 12; i++) {
          const key = 'role' + i;
          if (data[key]) roles.push(data[key]);
        }
        
        // 역할 표시
        renderRoles(roles);
      }
      
      // 역할 렌더링
      function renderRoles(roles) {
        DOM.selectedRoles.innerHTML = "";
        
        if (!roles.length) return;
        
        // DocumentFragment 사용으로 DOM 조작 최적화
        const fragment = document.createDocumentFragment();
        
        roles.forEach(role => {
          const span = document.createElement('span');
          span.textContent = ` 👱🏻 ${role}`;
          span.style.cursor = "pointer";
          span.style.marginRight = "10px";
          span.addEventListener("click", () => {
            window.location.href = `Goclassroomgame_toolsetting.php?role=${encodeURIComponent(role)}`;
          });
          fragment.appendChild(span);
        });
        
        DOM.selectedRoles.appendChild(fragment);
      }
      
      // 모든 작업 가져오기
      async function fetchAllTasks() {
        const data = await fetchData('getTasks');
        if (!data) return;
        
        brainDumpItems = data.brainDumpItems || [];
        todoList = data.todoList || [];
        timePlan = data.timePlan || [];
        doneList = data.doneList || [];
        
        renderAll();
      }
      
      // 체크된 도구 가져오기
      async function fetchCheckedTools() {
        const data = await fetchData('getCheckedTools');
        if (!data) return;
        
        checkedTools = data;
        renderCheckedTools();
      }
      
      // 역할에 해당하는 작업 가져오기
      async function fetchRoleTasks(rolesArr) {
        if (!rolesArr || !rolesArr.length) return [];
        
        const formData = new FormData();
        rolesArr.forEach(role => formData.append('roles[]', role));
        
        return await fetchData('getRoleTasks', 'POST', formData);
      }
      
      // ----- 데이터 변경 함수들 -----
      
      // BrainDump 항목 추가
      async function addBrainDumpItem(title, content) {
        const formData = new FormData();
        formData.append("title", title);
        formData.append("content", content);
        
        const result = await fetchData('addBrainDumpItem', 'POST', formData);
        if (result && result.success) {
          await fetchAllTasks();
        }
      }
      
      // ToDo로 이동
      async function moveToTodo(id) {
        const formData = new FormData();
        formData.append("id", id);
        
        const result = await fetchData('moveToTodo', 'POST', formData);
        if (result && result.success) {
          await fetchAllTasks();
        }
      }
      
      // TimePlan으로 이동
      async function moveToTimePlan(id, hour, minute, color) {
        const formData = new FormData();
        formData.append("id", id);
        formData.append("hour", hour);
        formData.append("minute", minute);
        formData.append("color", color);
        
        const result = await fetchData('moveToTimePlan', 'POST', formData);
        if (result && result.success) {
          await fetchAllTasks();
        }
      }
      
      // Done으로 이동
      async function moveToDone(id) {
        const formData = new FormData();
        formData.append("id", id);
        
        const result = await fetchData('moveToDone', 'POST', formData);
        if (result && result.success) {
          await fetchAllTasks();
        }
      }
      
      // 항목 삭제
      async function deleteItem(id) {
        const formData = new FormData();
        formData.append("id", id);
        
        const result = await fetchData('deleteItem', 'POST', formData);
        if (result && result.success) {
          await fetchAllTasks();
        }
      }
      
      // 직접 입력한 작업 추가
      async function addMyTask(task) {
        const formData = new FormData();
        formData.append("task", task);
        
        const result = await fetchData('addMyTask', 'POST', formData);
        if (result && result.success) {
          console.log("Add my task done", result);
        }
      }
      
      // 역할 저장
      async function saveRoles(rolesInEnglish) {
        const formData = new FormData();
        for (let i = 0; i < rolesInEnglish.length; i++) {
          formData.append('role' + (i + 1), rolesInEnglish[i]);
        }
        
        const result = await fetchData('saveRoles', 'POST', formData);
        if (result && result.success) {
          console.log("Roles saved successfully");
        }
      }
      
      // ----- 렌더링 함수들 -----
      
      // 모든 컴포넌트 렌더링
      function renderAll() {
        renderBrainDump();
        renderTodoList();
        renderTimePlan();
      }
      
      // BrainDump 렌더링 - DocumentFragment 최적화
      function renderBrainDump() {
        const fragment = document.createDocumentFragment();
        DOM.brainDumpContainer.innerHTML = "";
        
        // 1) status='brain_dump' 항목
        brainDumpItems.forEach((item, idx) => {
          const div = document.createElement("div");
          div.className = "brain-dump-item";
          div.dataset.index = idx;
          div.setAttribute("title", item.content || "");
          
          const titleSpan = document.createElement("span");
          titleSpan.textContent = item.title;
          if (item.completed) {
            titleSpan.style.color = "gray";
          }
          div.appendChild(titleSpan);
          
          if (!item.completed) {
            const toolIcon = document.createElement("span");
            toolIcon.className = "gpt-icon";
            toolIcon.textContent = "🔗";
            toolIcon.addEventListener("click", function(e) {
              e.stopPropagation();
              const url = item.url || "https://chatgpt.com/?model=o3-mini";
              window.open(url, "_blank");
            });
            div.appendChild(toolIcon);
          } else {
            const completedIcon = document.createElement("span");
            completedIcon.className = "check-icon";
            completedIcon.textContent = "✓";
            div.appendChild(completedIcon);
          }
          
          div.style.left = "50%";
          div.style.top = "50%";
          
          // 상태 데이터 속성
          div.dataset.paused = "false";
          
          // 이벤트 위임을 통한 이벤트 처리
          div.addEventListener("mouseover", function() {
            this.dataset.paused = "true";
            this.style.zIndex = "1000";
          });
          
          div.addEventListener("mouseout", function() {
            this.dataset.paused = "false";
            this.style.zIndex = "1";
          });
          
          // 클릭 -> TODO
          div.addEventListener("click", function() {
            if (todoList.length >= 3) {
              showTemporaryAlert();
              return;
            }
            moveToTodo(item.id);
          });
          
          fragment.appendChild(div);
        });
        
        // 2) status='done' 항목을 BrainDump 하단에 쌓기
        doneList.forEach((item, idx) => {
          const div = document.createElement("div");
          div.className = "brain-dump-item";
          div.style.backgroundColor = "#ddd";
          div.style.color = "#555";
          div.style.left = "10px";
          div.style.bottom = (idx * 45) + "px";
          div.style.position = "absolute";
          div.style.width = "auto";
          div.textContent = item.title + "(완료)";
          
          div.addEventListener("click", function() {
            moveToTodo(item.id);
          });
          
          fragment.appendChild(div);
        });
        
        DOM.brainDumpContainer.appendChild(fragment);
        
        // 애니메이션 프레임 시작 (중복 방지)
        if (!animationFrameId) {
          animationFrameId = requestAnimationFrame(updateBrainDumpAnimation);
        }
      }
      
      // TodoList 렌더링
      function renderTodoList() {
        const fragment = document.createDocumentFragment();
        DOM.todoListContainer.innerHTML = "";
        
        for (let i = 0; i < 3; i++) {
          const slot = document.createElement("div");
          slot.className = "todo-slot";
          
          if (todoList[i]) {
            slot.classList.add("filled");
            slot.textContent = todoList[i].title;
            
            // 이벤트 리스너 최적화
            slot.addEventListener("click", function() {
              const slotInfo = findNextAvailableSlot();
              const color = getRandomColor();
              moveToTimePlan(todoList[i].id, slotInfo.hour, slotInfo.minute, color);
            });
          }
          
          fragment.appendChild(slot);
        }
        
        DOM.todoListContainer.appendChild(fragment);
        DOM.todoCount.textContent = todoList.length;
      }
      
      // TimePlan 렌더링 - 최적화된 DOM 조작 및 가상화
      function renderTimePlan() {
        const fragment = document.createDocumentFragment();
        DOM.timeSlotsContainer.innerHTML = "";
        
        // 현재 시간 기준으로 계산
        const nowMinutes = currentTime.getHours() * 60 + currentTime.getMinutes();
        
        // 최적화: 타임슬롯을 필요할 때 생성 (가상화)
        const timeSlots = getTimeSlots();
        
        // 현재 보이는 화면에 필요한 시간 슬롯만 렌더링 (가상화)
        timeSlots.forEach((slot, slotIndex) => {
          const slotMinutes = slot.hour * 60 + slot.minute;
          
          // 현재 시간 1시간 이전보다 이전인 경우 렌더링하지 않음
          if (slotMinutes < nowMinutes - 60) {
            return;
          }
          
          const row = document.createElement("div");
          row.className = "time-slot";
          
          const label = document.createElement("span");
          label.className = "time-label";
          label.textContent = `${String(slot.hour).padStart(2, '0')}:${String(slot.minute).padStart(2, '0')}`;
          row.appendChild(label);
          
          if (slotIndex < currentTimeSlot - 4) {
            row.style.display = "none";
          }
          
          if (nowMinutes >= slotMinutes && nowMinutes < slotMinutes + 15) {
            row.style.backgroundColor = "#ffe6e6";
          } else if (slotMinutes < nowMinutes) {
            row.style.backgroundColor = row.style.display === "none" ? "transparent" : "#f2f2f2";
          }
          
          const contentArea = document.createElement("div");
          contentArea.className = "time-slot-content";
          contentArea.dataset.hour = slot.hour;
          contentArea.dataset.minute = slot.minute;
          
          // 이벤트 위임을 통한 최적화
          contentArea.addEventListener("dragover", e => e.preventDefault());
          contentArea.addEventListener("drop", e => {
            e.preventDefault();
            if (dragItem) {
              moveToTimePlan(dragItem.id, slot.hour, slot.minute, getRandomColor());
              dragItem = null;
            }
          });
          
          // 해당 시간 슬롯에 있는 항목만 필터링하여 렌더링
          const slotItems = timePlan.filter(item => 
            Number(item.hour) === slot.hour && Number(item.minute) === slot.minute
          );
          
          if (slotItems.length > 0) {
            const itemsFragment = document.createDocumentFragment();
            
            slotItems.forEach(item => {
              const itemDiv = document.createElement("div");
              itemDiv.className = "time-plan-item " + (item.color || 'bg-blue-200');
              itemDiv.setAttribute("title", item.content || "");
              itemDiv.draggable = true;
              itemDiv.dataset.id = item.id;
              
              const titleSpan = document.createElement("span");
              titleSpan.textContent = item.title;
              itemDiv.appendChild(titleSpan);
              
              const toolIcon = document.createElement("span");
              toolIcon.className = "gpt-icon";
              toolIcon.textContent = "🔗";
              toolIcon.addEventListener("click", function(e) {
                e.stopPropagation();
                const url = item.url || "https://chatgpt.com/?model=o3-mini";
                window.open(url, "_blank");
              });
              itemDiv.appendChild(toolIcon);
              
              // 이벤트 리스너 설정
              itemDiv.addEventListener("dragstart", function() {
                dragItem = item;
              });
              
              itemDiv.addEventListener("dblclick", function(e) {
                e.stopPropagation();
                window.location.href = `Goclassroomgame_detail.php?title=${encodeURIComponent(item.title)}&userid=<?php echo $userid; ?>`;
              });
              
              itemsFragment.appendChild(itemDiv);
            });
            
            contentArea.appendChild(itemsFragment);
          }
          
          row.appendChild(contentArea);
          fragment.appendChild(row);
        });
        
        DOM.timeSlotsContainer.appendChild(fragment);
      }
      
      // 체크된 도구 렌더링
      function renderCheckedTools() {
        const fragment = document.createDocumentFragment();
        DOM.checkedToolsContainer.innerHTML = "";
        
        if (!checkedTools || checkedTools.length === 0) {
          const emptyMsg = document.createElement("div");
          emptyMsg.textContent = "체크된 항목이 없습니다.";
          fragment.appendChild(emptyMsg);
        } else {
          checkedTools.forEach(tool => {
            const div = document.createElement("div");
            div.textContent = `[${tool.role}] ${tool.task} - ${tool.description}`;
            
            // URL이 있으면 링크 추가
            if (tool.url) {
              const link = document.createElement("a");
              link.href = tool.url;
              link.target = "_blank";
              link.style.marginLeft = "10px";
              link.textContent = "링크";
              div.appendChild(link);
            }
            
            fragment.appendChild(div);
          });
        }
        
        DOM.checkedToolsContainer.appendChild(fragment);
      }
      
      // ----- 유틸리티 함수들 -----
      
      // 다음 사용 가능한 시간 슬롯 찾기
      function findNextAvailableSlot() {
        const timeSlots = getTimeSlots();
        
        if (currentTime.getHours() >= 23) {
          return { hour: 23, minute: 45 };
        }
        
        for (let i = currentTimeSlot; i < timeSlots.length; i++) {
          const slot = timeSlots[i];
          const count = timePlan.filter(item =>
            Number(item.hour) === slot.hour && 
            Number(item.minute) === slot.minute && 
            !item.completed
          ).length;
          
          if (count < 3) return slot;
        }
        
        return timeSlots[currentTimeSlot];
      }
      
      // 시간 슬롯 생성 함수 (메모리 최적화)
      function getTimeSlots() {
        // 96개의 타임슬롯 (24시간 * 4개 15분 단위)
        return Array.from({ length: 96 }, (_, i) => ({
          hour: Math.floor(i / 4),
          minute: (i % 4) * 15
        }));
      }
      
      // TimePlan 항목 자동 이동 (미래 시간으로)
      function autoMoveTimePlanItems() {
        const nowMinutes = currentTime.getHours() * 60 + currentTime.getMinutes();
        
        // 모든 항목을 한번에 처리할 배치 업데이트 준비
        const updates = [];
        
        timePlan.forEach(item => {
          if (!item.completed && item.hour !== null && item.minute !== null) {
            const itemMin = Number(item.hour) * 60 + Number(item.minute);
            if (nowMinutes > itemMin) {
              const nextSlot = findNextAvailableSlot();
              if (nextSlot) {
                updates.push({
                  id: item.id,
                  hour: nextSlot.hour,
                  minute: nextSlot.minute,
                  color: item.color || 'bg-blue-200'
                });
              }
            }
          }
        });
        
        // 배치 업데이트 실행
        if (updates.length > 0) {
          // 최적화: 업데이트가 여러 개면 순차적으로 처리하되,
          // 마지막 업데이트 후에만 전체 다시 가져오기
          const processUpdates = async (index) => {
            if (index >= updates.length) {
              await fetchAllTasks();
              return;
            }
            
            const update = updates[index];
            await moveToTimePlan(update.id, update.hour, update.minute, update.color);
            
            // 마지막 업데이트인 경우에만 전체 다시 가져오기
            if (index === updates.length - 1) {
              await fetchAllTasks();
            } else {
              processUpdates(index + 1);
            }
          };
          
          processUpdates(0);
        }
      }
      
      // 시간 업데이트
      function updateTime() {
        // KST 시간대 계산 최적화
        const now = new Date();
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        const kst = new Date(utc + (9 * 60 * 60 * 1000));
        currentTime = kst;
        
        DOM.currentTime.textContent = kst.toLocaleTimeString('ko-KR', { hour12: false });
        
        const hour = kst.getHours();
        const minute = kst.getMinutes();
        currentTimeSlot = hour * 4 + Math.floor(minute / 15);
      }
      
      // 임시 알림 표시
      function showTemporaryAlert() {
        DOM.alert.style.display = "block";
        setTimeout(() => {
          DOM.alert.style.display = "none";
        }, 3000);
      }
      
      // BrainDump 애니메이션 업데이트 - requestAnimationFrame 사용
      function updateBrainDumpAnimation() {
        const items = DOM.brainDumpContainer.getElementsByClassName("brain-dump-item");
        
        if (items.length === 0) {
          animationFrameId = requestAnimationFrame(updateBrainDumpAnimation);
          return;
        }
        
        const now = Date.now() / 1000;
        const period = 20;
        const angularSpeed = (2 * Math.PI) / period;
        
        for (let i = 0; i < items.length; i++) {
          const itemDiv = items[i];
          
          // 일시정지된 아이템은 건너뛰기
          if (itemDiv.dataset.paused === "true") continue;
          
          const index = parseInt(itemDiv.dataset.index);
          if (isNaN(index)) continue;
          
          const baseAngle = (2 * Math.PI / brainDumpItems.length) * index;
          const angle = baseAngle + angularSpeed * (now - index * 2);
          
          // transform 속성 사용 (top/left 변경보다 성능 좋음)
          const x = Math.cos(angle) * 120;
          const y = Math.sin(angle) * 80;
          
          itemDiv.style.transform = `translate(${x}px, ${y}px)`;
        }
        
        animationFrameId = requestAnimationFrame(updateBrainDumpAnimation);
      }
      
      // 한국어 역할명을 영어로 변환
      function toEnglishRole(role) {
        const roleMapping = {
          '선생님': 'Teacher',
          '컨텐츠 연구원': 'Content Researcher',
          '앱 개발자': 'App Developer',
          '프로젝트 메니저': 'Project Manager',
          '컨텐츠 크리에이터': 'Content Creator',
          '학원 관리자': 'Academy Manager'
        };
        return roleMapping[role] || role;
      }
      
      // ToDo 제목 선택 팝업
      function chooseTodoTitle() {
        // 항상 'my' 추가
        const roleSpans = document.querySelectorAll('#selectedRoles span');
        const rolesArr = ['my'];
        
        roleSpans.forEach(sp => {
          const txt = sp.textContent.trim().replace('👱🏻', '').trim();
          if (txt) rolesArr.push(txt);
        });
        
        fetchRoleTasks(rolesArr).then(taskList => {
          showTodoTitlePopup(taskList || []);
        });
      }
      
      // ToDo 제목 선택 팝업 표시
      function showTodoTitlePopup(taskList) {
        let htmlContent = "";
        
        taskList.forEach((task, idx) => {
          htmlContent += `
            <button class="swal2-confirm swal2-styled"
                    id="taskButton${idx}"
                    style="display:block;width:100%;margin:5px 0;">${task}</button>
          `;
        });
        
        htmlContent += `
          <button class="swal2-confirm swal2-styled"
                  id="optionDirect"
                  style="display:block;width:100%;margin:5px 0;">직접입력</button>
        `;
        
        Swal.fire({
          title: '업무 선택',
          html: htmlContent,
          showConfirmButton: false
        });
        
        // 이벤트 리스너 설정
        taskList.forEach((task, idx) => {
          const btn = document.getElementById(`taskButton${idx}`);
          if (btn) {
            btn.addEventListener("click", function() {
              selectedTitle = task;
              Swal.close();
              updateTitleButton();
            });
          }
        });
        
        const directBtn = document.getElementById("optionDirect");
        if (directBtn) {
          directBtn.addEventListener("click", function() {
            Swal.close();
            Swal.fire({
              title: '직접 입력',
              input: 'text',
              inputPlaceholder: '업무 이름 입력',
              showCancelButton: true,
              confirmButtonText: '입력'
            }).then((result) => {
              if (result.isConfirmed && result.value) {
                selectedTitle = result.value;
                addMyTask(selectedTitle);
                updateTitleButton();
              }
            });
          });
        }
      }
      
      // ToDo 제목 버튼 업데이트
      function updateTitleButton() {
        DOM.todoTitleButton.innerText = selectedTitle || "업무 선택";
      }
      
      // 삭제 모달 열기
      function openDeleteDialog(item) {
        deleteCandidate = item;
        DOM.modalMessage.textContent = `"${item.title}"을(를) 삭제하시겠습니까?`;
        DOM.modalOverlay.style.display = "flex";
      }
      
      // 삭제 모달 닫기
      function closeDeleteDialog() {
        deleteCandidate = null;
        DOM.modalOverlay.style.display = "none";
      }
      
      // 삭제 확인
      function confirmDeletion() {
        if (deleteCandidate) {
          deleteItem(deleteCandidate.id);
          closeDeleteDialog();
        }
      }
      
      // ----- 이벤트 리스너 설정 -----
      
      // 스크롤 위치 저장
      DOM.timePlanContainer.addEventListener("scroll", throttle(function() {
        localStorage.setItem("timePlanScrollTop", DOM.timePlanContainer.scrollTop);
      }, 100));
      
      // 체크메뉴 버튼 클릭
      DOM.fetchCheckedToolsButton.addEventListener("click", fetchCheckedTools);
      
      // 역할 변경 버튼 클릭
      DOM.changeRoleButton.addEventListener("click", function() {
        Swal.fire({
          title: '역할 선택',
          html: `
            <div style="text-align:left;">
              <label><input type="checkbox" value="선생님"> 선생님</label><br>
              <label><input type="checkbox" value="컨텐츠 연구원"> 컨텐츠 연구원</label><br>
              <label><input type="checkbox" value="앱 개발자"> 앱 개발자</label><br>
              <label><input type="checkbox" value="프로젝트 메니저"> 프로젝트 메니저</label><br>
              <label><input type="checkbox" value="컨텐츠 크리에이터"> 컨텐츠 크리에이터</label><br>
              <label><input type="checkbox" value="학원 관리자"> 학원 관리자</label><br>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: '선택 완료'
        }).then((result) => {
          if (result.isConfirmed) {
            const selectedRoles = [];
            const checkboxes = Swal.getPopup().querySelectorAll('input[type="checkbox"]');
            
            checkboxes.forEach(checkbox => {
              if (checkbox.checked) selectedRoles.push(checkbox.value);
            });
            
            if (selectedRoles.length > 0) {
              const rolesInEnglish = selectedRoles.map(role => toEnglishRole(role));
              saveRoles(rolesInEnglish);
              renderRoles(rolesInEnglish);
            } else {
              DOM.selectedRoles.textContent = "";
            }
          }
        });
      });
      
      // ToDo 제목 버튼 클릭
      DOM.todoTitleButton.addEventListener("click", function(e) {
        e.preventDefault();
        const contentVal = DOM.todoContent.value.trim();
        
        if (contentVal !== "") {
          selectedTitle = contentVal;
          updateTitleButton();
        } else {
          chooseTodoTitle();
        }
      });
      
      // ToDo 폼 제출
      DOM.todoForm.addEventListener("submit", function(e) {
        e.preventDefault();
        
        if (!selectedTitle) {
          DOM.todoTitleButton.click();
          return;
        }
        
        const title = selectedTitle;
        const content = DOM.todoContent.value.trim();
        
        if (title && content) {
          addBrainDumpItem(title, content);
          selectedTitle = "";
          updateTitleButton();
          DOM.todoContent.value = "";
        }
      });
      
      // 삭제 모달 버튼
      DOM.cancelDelete.addEventListener("click", closeDeleteDialog);
      DOM.confirmDelete.addEventListener("click", confirmDeletion);
      
      // BrainDump 드래그앤드롭 - 이벤트 위임
      DOM.brainDumpContainer.addEventListener("dragover", e => e.preventDefault());
      DOM.brainDumpContainer.addEventListener("drop", e => {
        e.preventDefault();
        if (dragItem) {
          moveToDone(dragItem.id);
          dragItem = null;
        }
      });
      
      // ----- 초기화 및 주기적 작업 -----
      
      // 초기 데이터 로드
      fetchRoles();
      fetchAllTasks();
      
      // 저장된 스크롤 위치 복원
      const savedScrollTop = localStorage.getItem("timePlanScrollTop");
      if (savedScrollTop !== null) {
        DOM.timePlanContainer.scrollTop = parseInt(savedScrollTop, 10);
      }
      
      // 타이머 설정 - 성능 최적화 버전
      let lastAutoMoveCheck = 0;
      
      // 메인 루프 함수 - 매 초마다 실행
      function mainLoop() {
        updateTime();
        
        // 자동 이동은 30초마다만 수행 (성능 최적화)
        const now = Date.now();
        if (now - lastAutoMoveCheck >= 30000) {
          autoMoveTimePlanItems();
          lastAutoMoveCheck = now;
        }
      }
      
      // 초당 타이머
      setInterval(mainLoop, 1000);
      
      // 최초 애니메이션 프레임 요청
      animationFrameId = requestAnimationFrame(updateBrainDumpAnimation);
      
      // 최초 시간 설정
      updateTime();
    });
  </script>
</body>
</html>