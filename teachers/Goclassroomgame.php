<?php
/////////////////////////////// PHP 초기 설정 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 사용자 ID 결정
$userid = $USER->id;  
$timecreated = time(); 
$halfdayago = $timecreated - 43200;

// 자정에 실행되었는지 확인하기 위한 플래그 (쿠키)
$midnightCheckCookie = 'midnight_check_' . date('Ymd');
$lastCheckedDay = isset($_COOKIE[$midnightCheckCookie]) ? $_COOKIE[$midnightCheckCookie] : '';
$currentDay = date('Ymd');

// 오늘 아직 체크하지 않았다면 자정 이후 작업 이월 처리
if ($lastCheckedDay != $currentDay) {
    // 쿠키 설정 (24시간 유효)
    setcookie($midnightCheckCookie, $currentDay, time() + 86400, '/');
    
    // 어제 날짜의 시작과 끝 타임스탬프
    $yesterdayStart = strtotime('yesterday midnight');
    $yesterdayEnd = strtotime('today midnight');
    
    // 어제의 미완료 작업을 가져와서 오늘로 이월
    carryOverUncompletedTasks($DB, $userid, $yesterdayStart, $yesterdayEnd);
}

require_login();

// 사용자 정보 조회
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", [$userid]);
$username = $thisuser->lastname ?? '';

// 사용자 역할 정보 조회 (예: user_info_data)
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22", [$USER->id]);
$role = $userrole->role ?? '';

//echo '<table align="center"><tr><td align="center"><iframe style="height:1%;" src="https://www.youtube.com/embed/9jK-NcRmVcw?autoplay=1&mute=0" frameborder="0" allow="autoplay; encrypted-media"></iframe></td></tr></table>'; 
// -------------------------------------------------------------
// AJAX 요청 처리: action 파라미터에 따라 DB 연동
// -------------------------------------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    switch($action) {

        // A) 가장 최근에 저장된 mdl_agent_user 레코드 불러오기
        case 'getLatestRoles':
            $latest = $DB->get_record_sql(
                "SELECT *
                   FROM mdl_agent_user
                  WHERE user_id = ?
               ORDER BY timecreated DESC
                  LIMIT 1", 
                [$userid]
            );
            if ($latest) {
                echo json_encode([
                    'role1'  => $latest->role1,
                    'role2'  => $latest->role2,
                    'role3'  => $latest->role3,
                    'role4'  => $latest->role4,
                    'role5'  => $latest->role5,
                    'role6'  => $latest->role6,
                    'role7'  => $latest->role7,
                    'role8'  => $latest->role8,
                    'role9'  => $latest->role9,
                    'role10' => $latest->role10,
                    'role11' => $latest->role11,
                    'role12' => $latest->role12
                ]);
            } else {
                echo json_encode(null);
            }
            exit();

        // B) 역할 저장 (role1~role12)
        case 'saveRoles':
            $roles = [];
            for ($i = 1; $i <= 12; $i++) {
                $param = 'role' . $i;
                if (isset($_POST[$param]) && $_POST[$param] !== '') {
                    $roles[$i] = $_POST[$param];
                }
            }
            $newdata = new stdClass();
            $newdata->user_id = $userid;
            for ($i = 1; $i <= 12; $i++) {
                $colName = 'role' . $i;
                $newdata->$colName = isset($roles[$i]) ? $roles[$i] : null;
            }
            $newdata->timecreated = time();
            $DB->insert_record('agent_user', $newdata);

            echo json_encode(['success' => true]);
            exit();

        // *** [새로 추가] 역할들에 해당하는 업무 목록 가져오기 ***
        case 'getRoleTasks':
          $rolesArr = $_POST['roles'] ?? [];
          if (!is_array($rolesArr) || count($rolesArr) === 0) {
              echo json_encode([]);
              exit();
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
          
          $tasks = [];
          foreach ($records as $r) {
              $tasks[] = $r->task;
          }
          echo json_encode($tasks);
          exit();
      

        // 1) 현재 사용자(userid)의 모든 작업 불러와 상태별 분류 (done도 추가)
        case 'getTasks':
          $startOfDay = strtotime("today midnight");
          $endOfDay = strtotime("tomorrow midnight");
          $records = $DB->get_records_sql(
              "SELECT * FROM mdl_agent_tasks 
               WHERE user_id = ? AND timemodified >= ? AND timemodified < ?",
               [$userid, $startOfDay, $endOfDay]
          );
            $brainDump = [];
            $todo = [];
            $timePlan = [];
            $doneList = []; // 완료 목록

            foreach ($records as $r) {
                $item = [
                    'id'       => $r->id,
                    'title'    => $r->title,
                    'content'  => $r->content,
                    'url'      => $r->url,
                    'completed'=> (bool)$r->completed,
                    'hour'     => $r->scheduled_hour,
                    'minute'   => $r->scheduled_minute,
                    'color'    => $r->color
                ];
                if ($r->status === 'brain_dump') {
                    $brainDump[] = $item;
                } else if ($r->status === 'todo') {
                    $todo[] = $item;
                } else if ($r->status === 'time_plan') {
                    $timePlan[] = $item;
                } else if ($r->status === 'done') { 
                    $doneList[] = $item;
                }
            }

            echo json_encode([
                'brainDumpItems' => $brainDump,
                'todoList'       => $todo,
                'timePlan'       => $timePlan,
                'doneList'       => $doneList
            ]);
            exit();

        // 2) 새 브레인덤프 항목 추가 - 안정성 강화 버전
        case 'addBrainDumpItem':
            try {
                // [예외 처리 1] POST 데이터 검증
                $title   = isset($_POST['title']) ? trim($_POST['title']) : '';
                $content = isset($_POST['content']) ? trim($_POST['content']) : '';
                
                // [예외 처리 2] 제목과 내용 모두 비어있는 경우
                if (empty($title) && empty($content)) {
                    echo json_encode([
                        'success' => false, 
                        'message' => '제목 또는 내용을 입력해주세요.'
                    ]);
                    exit();
                }
                
                // [예외 처리 3] 제목이 없으면 내용의 첫 부분을 제목으로 사용
                if (empty($title)) {
                    $title = mb_substr($content, 0, 50, 'UTF-8');
                    if (mb_strlen($content, 'UTF-8') > 50) {
                        $title .= '...';
                    }
                }
                
                // [예외 처리 4] 제목 길이 제한 (DB 제약 고려)
                $title = mb_substr($title, 0, 255, 'UTF-8');
                
                // [예외 처리 5] 사용자 ID 검증
                if (empty($userid) || $userid <= 0) {
                    error_log("[Goclassroomgame.php:175] 잘못된 사용자 ID: " . $userid);
                    echo json_encode([
                        'success' => false, 
                        'message' => '사용자 인증 오류가 발생했습니다.'
                    ]);
                    exit();
                }
                
                // 데이터 객체 생성
                $newdata = new stdClass();
                $newdata->user_id       = $userid;
                $newdata->title         = $title;
                $newdata->content       = $content;
                $newdata->url           = '';
                $newdata->status        = 'brain_dump';
                $newdata->completed     = 0;
                $newdata->scheduled_hour   = null;
                $newdata->scheduled_minute = null;
                $newdata->color         = null;
                $newdata->timecreated   = time();
                $newdata->timemodified  = time();
                
                // [예외 처리 6] DB 삽입 시도
                $insertId = $DB->insert_record('agent_tasks', $newdata);
                
                if ($insertId === false) {
                    error_log("[Goclassroomgame.php:200] DB 삽입 실패 - 사용자 ID: " . $userid . ", 제목: " . $title);
                    echo json_encode([
                        'success' => false, 
                        'message' => '데이터베이스에 저장하는 중 오류가 발생했습니다.'
                    ]);
                    exit();
                }
                
                // 성공 응답
                echo json_encode([
                    'success' => true,
                    'id' => $insertId,
                    'message' => '포스트잇이 성공적으로 추가되었습니다.'
                ]);
                
            } catch (Exception $e) {
                // [예외 처리 7] 예상치 못한 오류 처리
                error_log("[Goclassroomgame.php:215] 예외 발생: " . $e->getMessage() . " | 파일: " . $e->getFile() . " | 라인: " . $e->getLine());
                echo json_encode([
                    'success' => false, 
                    'message' => '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
                ]);
            }
            exit();

        // 2-1) 기존 task ID로 브레인덤프 항목 추가 (memo 데이터도 복사)
        case 'addBrainDumpItemWithTaskId':
            $title   = $_POST['title']   ?? '';
            $content = $_POST['content'] ?? '';
            $sourceTaskId = $_POST['source_task_id'] ?? 0;
            
            // 새 task 항목 생성
            $newdata = new stdClass();
            $newdata->user_id       = $userid;
            $newdata->title         = $title;
            $newdata->content       = $content;
            $newdata->url           = '';
            $newdata->status        = 'brain_dump';
            $newdata->completed     = 0;
            $newdata->scheduled_hour   = null;
            $newdata->scheduled_minute = null;
            $newdata->color         = null;
            $newdata->timecreated   = time();
            $newdata->timemodified  = time();
            $newTaskId = $DB->insert_record('agent_tasks', $newdata);
            
            // 소스 태스크의 메모 데이터 조회
            $sourceMemo = $DB->get_record('agent_dashboard_memos', ['user_id' => $userid, 'taskid' => $sourceTaskId]);
            
            if ($sourceMemo) {
                // 새 메모 데이터 생성 (동일한 taskid로)
                $newMemo = clone $sourceMemo;
                $newMemo->id = null; // 자동 생성되도록 ID는 null로
                $newMemo->taskid = $newTaskId;
                $newMemo->timecreated = time();
                $newMemo->timemodified = time();
                $DB->insert_record('agent_dashboard_memos', $newMemo);
            }
            
            echo json_encode(['success' => true, 'id' => $newTaskId]);
            exit();

        // 3) 특정 항목을 'todo' 상태로 이동
        case 'moveToTodo':
            $id = $_POST['id'] ?? 0;
            $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
            if ($record) {
                $record->status       = 'todo';
                $record->completed    = 0;
                $record->timemodified = time();
                // 초기화
                $record->scheduled_hour   = null;
                $record->scheduled_minute = null;
                $record->color            = null;
                $DB->update_record('agent_tasks', $record);
            }
            echo json_encode(['success' => true]);
            exit();

        // 4) 특정 항목을 'time_plan' 상태로 이동 + 시간/색상 지정
        case 'moveToTimePlan':
            $id     = $_POST['id']     ?? 0;
            $hour   = $_POST['hour']   ?? 0;
            $minute = $_POST['minute'] ?? 0;
            $color  = $_POST['color']  ?? 'bg-blue-200';
            $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
            if ($record) {
                $record->status           = 'time_plan';
                $record->completed        = 0;
                $record->scheduled_hour   = $hour;
                $record->scheduled_minute = $minute;
                $record->color            = $color;
                $record->timemodified     = time();
                $DB->update_record('agent_tasks', $record);
            }
            echo json_encode(['success' => true]);
            exit();

        // *** 일정표 항목 -> BrainDump 드래그 => status='done'
        case 'moveToDone':
            $id = $_POST['id'] ?? 0;
            $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
            if ($record) {
                $record->status       = 'done';
                $record->completed    = 1;
                $record->timemodified = time();
                $DB->update_record('agent_tasks', $record);
            }
            echo json_encode(['success' => true]);
            exit();

        // 5) 특정 항목 삭제
        case 'deleteItem':
            $id = $_POST['id'] ?? 0;
            $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
            if ($record) {
                $DB->delete_records('agent_tasks', ['id' => $id]);
            }
            echo json_encode(['success' => true]);
            exit();

        // 7) 특정 항목의 제목/내용 업데이트
        case 'updateTask':
            $id = $_POST['id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
            if ($record) {
                $record->title = $title;
                $record->content = $content;
                $record->timemodified = time();
                $DB->update_record('agent_tasks', $record);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '항목을 찾을 수 없습니다.']);
            }
            exit();

        // 6) 특정 항목을 완료 처리
        case 'completeItem':
            $id = $_POST['id'] ?? 0;
            $record = $DB->get_record('agent_tasks', ['id' => $id, 'user_id' => $userid]);
            if ($record) {
                $record->completed    = 1;
                $record->timemodified = time();
                $DB->update_record('agent_tasks', $record);
            }
            echo json_encode(['success' => true]);
            exit();

        // *** 직접 입력된 TASK를 role='my' 로 mdl_agent_toolsettings 에 추가
        case 'addMyTask':
            $task = $_POST['task'] ?? '';
            if ($task) {
                $newTool = new stdClass();
                $newTool->role         = 'my';
                $newTool->task         = $task;
                $newTool->description  = '';
                $newTool->url          = '';
                $newTool->timecreated  = time();
                $newTool->timemodified = time();
                $DB->insert_record('agent_toolsettings', $newTool);
            }
            echo json_encode(['success' => true]);
            exit();

        // [추가] usertoolsettings DB에서 checked=1 인 항목만 조회
        case 'getCheckedTools':
            // 예: 테이블 구조
            // mdl_agent_usertoolsettings(user_id, agent_toolsetting_id, checked, timecreated)
            // mdl_agent_toolsettings(id, role, task, description, url, ...)
            $sql = "
                SELECT s.id, s.role, s.task, s.description, s.url
                  FROM mdl_agent_toolsettings s
                  JOIN mdl_agent_usertoolsettings u
                    ON s.id = u.agent_toolsetting_id
                 WHERE u.user_id = ?
                   AND u.checked = 1
              ORDER BY s.id ASC
            ";
            $records = $DB->get_records_sql($sql, [$userid]);
            $result = [];
            foreach ($records as $r) {
                $result[] = [
                    'id'          => $r->id,
                    'role'        => $r->role,
                    'task'        => $r->task,
                    'description' => $r->description,
                    'url'         => $r->url
                ];
            }
            echo json_encode($result);
            exit();

        // [추가] 모든 항목 15분 뒤로 이동
        case 'moveAllItemsForward15':
            $startOfDay = strtotime("today midnight");
            $endOfDay = strtotime("tomorrow midnight");
            
            // time_plan 상태의 모든 항목을 가져옴
            $records = $DB->get_records_sql(
                "SELECT * FROM mdl_agent_tasks 
                 WHERE user_id = ? AND status = 'time_plan' AND timemodified >= ? AND timemodified < ?",
                 [$userid, $startOfDay, $endOfDay]
            );
            
            foreach ($records as $record) {
                // 현재 시간과 분 가져오기
                $hour = $record->scheduled_hour;
                $minute = $record->scheduled_minute;
                
                // 15분 추가
                $minute += 15;
                
                // 60분 이상이면 시간 조정
                if ($minute >= 60) {
                    $hour += 1;
                    $minute -= 60;
                }
                
                // 24시 이상이면 0시로 조정
                if ($hour >= 24) {
                    $hour = $hour % 24;
                }
                
                // DB 업데이트
                $record->scheduled_hour = $hour;
                $record->scheduled_minute = $minute;
                $record->timemodified = time();
                $DB->update_record('agent_tasks', $record);
            }
            
            echo json_encode(['success' => true]);
            exit();

        // [추가] 특정 날짜의 작업 가져오기
        case 'getTasksByDate':
            $targetDate = isset($_GET['date']) ? intval($_GET['date']) : time();
            $startOfDay = strtotime("midnight", $targetDate);
            $endOfDay = strtotime("tomorrow midnight", $targetDate);
            
            $records = $DB->get_records_sql(
                "SELECT * FROM mdl_agent_tasks 
                 WHERE user_id = ? AND timemodified >= ? AND timemodified < ?",
                 [$userid, $startOfDay, $endOfDay]
            );
            
            $brainDump = [];
            $todo = [];
            $timePlan = [];
            $doneList = [];

            foreach ($records as $r) {
                $item = [
                    'id'       => $r->id,
                    'title'    => $r->title,
                    'content'  => $r->content,
                    'url'      => $r->url,
                    'completed'=> (bool)$r->completed,
                    'hour'     => $r->scheduled_hour,
                    'minute'   => $r->scheduled_minute,
                    'color'    => $r->color
                ];
                if ($r->status === 'brain_dump') {
                    $brainDump[] = $item;
                } else if ($r->status === 'todo') {
                    $todo[] = $item;
                } else if ($r->status === 'time_plan') {
                    $timePlan[] = $item;
                } else if ($r->status === 'done') { 
                    $doneList[] = $item;
                }
            }

            echo json_encode([
                'brainDumpItems' => $brainDump,
                'todoList'       => $todo,
                'timePlan'       => $timePlan,
                'doneList'       => $doneList,
                'date'           => date('Y-m-d', $targetDate)
            ]);
            exit();

        // [추가] 완료되지 않은 작업을 다음 날로 이월
        case 'carryOverTasks':
            $yesterday = isset($_GET['date']) ? intval($_GET['date']) : strtotime('yesterday midnight');
            $yesterdayStart = strtotime('midnight', $yesterday);
            $yesterdayEnd = strtotime('tomorrow midnight', $yesterday);
            
            $result = carryOverUncompletedTasks($DB, $userid, $yesterdayStart, $yesterdayEnd);
            echo json_encode(['success' => true, 'carried_tasks' => $result]);
            exit();

        default:
            echo json_encode(['error' => 'Unknown action']);
            exit();
    }
}

/**
 * 완료되지 않은 작업을 다음 날로 이월하는 함수
 */
function carryOverUncompletedTasks($DB, $userid, $dayStart, $dayEnd) {
    // 해당 날짜의 완료되지 않은 작업들 가져오기 (brain_dump, todo, time_plan 상태)
    $records = $DB->get_records_sql(
        "SELECT * FROM mdl_agent_tasks 
         WHERE user_id = ? 
         AND completed = 0 
         AND status IN ('brain_dump', 'todo', 'time_plan')
         AND timemodified >= ? 
         AND timemodified < ?",
         [$userid, $dayStart, $dayEnd]
    );
    
    $carriedTasks = [];
    $today = time();
    
    foreach ($records as $record) {
        // 작업 복제
        $newTask = clone $record;
        $newTask->id = null; // 새 ID 할당을 위해 null로 설정
        $newTask->timecreated = $today;
        $newTask->timemodified = $today;
        
        // 새로운 작업으로 DB에 삽입
        $newId = $DB->insert_record('agent_tasks', $newTask);
        
        // 복제된 작업 정보 저장
        $carriedTasks[] = [
            'original_id' => $record->id,
            'new_id' => $newId,
            'title' => $record->title
        ];
    }
    
    return $carriedTasks;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Time Catcher Game</title>
  <style>
    /* 전체 스타일 */
    body {
      font-family: Arial, sans-serif;
      margin: 0; padding: 0;
      background-color: #f3f4f6;
    }
    .container {
      padding: 1.5rem;
      max-width: 72rem;
      margin: 0 auto;
      background-color: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    .header {
      display: flex; justify-content: space-between;
      align-items: center; margin-bottom: 1.5rem;
    }
    .title {
      font-size: 1.5rem; font-weight: bold;
    }
    .header-buttons {
      margin-left: auto; display: flex; justify-content: flex-end;
    }
    .header-buttons button {
      margin-right: 10px;
      padding: 0.3rem 0.6rem;
      font-size: 0.9rem;
      cursor: pointer; border: none;
      background-color: #f2f2f2; color: black;
      border-radius: 0.25rem;
    }
    .header-buttons button .icon {
      margin-right: 5px;
    }
    .header-buttons button:hover {
      background-color: #e0e0e0;
    }
    .time-display {
      display: flex; align-items: center;
    }
    .time-display span {
      margin-left: 0.5rem;
    }
    .alert {
      border: 1px solid red; padding: 0.5rem;
      border-radius: 0.5rem; margin-bottom: 1rem;
      color: red; display: none;
    }
    .grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
    }
    .section {
      background-color: #f7fafc; padding: 1rem;
      border-radius: 0.5rem;
    }
    .section h2 {
      font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;
    }
    #brainDumpContainer {
      position: relative; 
      min-height: 384px;
      padding: 1rem;
      border: 1px dashed #aaa;
      background-color: #f9fafb;
      overflow-y: auto;
      overflow-x: hidden;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
      align-content: start;
      /* 스크롤바 스타일 개선 */
      scrollbar-width: thin;
      scrollbar-color: #cbd5e0 #f9fafb;
    }
    /* 웹킷 브라우저용 스크롤바 스타일 */
    #brainDumpContainer::-webkit-scrollbar {
      width: 8px;
    }
    #brainDumpContainer::-webkit-scrollbar-track {
      background: #f9fafb;
      border-radius: 4px;
    }
    #brainDumpContainer::-webkit-scrollbar-thumb {
      background: #cbd5e0;
      border-radius: 4px;
    }
    #brainDumpContainer::-webkit-scrollbar-thumb:hover {
      background: #a0aec0;
    }
    .brain-dump-item {
      position: relative;
      padding: 1rem;
      min-height: 120px;
      max-width: 200px;
      cursor: pointer;
      box-shadow: 2px 2px 8px rgba(0,0,0,0.15);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      transform: rotate(-1deg);
      word-wrap: break-word;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .brain-dump-item:nth-child(3n+1) {
      background-color: #ffeb3b; /* 노란색 포스트잇 */
      transform: rotate(-1deg);
    }
    .brain-dump-item:nth-child(3n+2) {
      background-color: #ffb3ba; /* 분홍색 포스트잇 */
      transform: rotate(1deg);
    }
    .brain-dump-item:nth-child(3n+3) {
      background-color: #bae1ff; /* 파란색 포스트잇 */
      transform: rotate(-0.5deg);
    }
    .brain-dump-item:hover {
      transform: rotate(0deg) scale(1.05) !important;
      box-shadow: 4px 4px 12px rgba(0,0,0,0.25);
      z-index: 10;
    }
    /* 붉은색 시간대를 위한 별도 스타일 */
    .time-slot.current-time {
      background-color: rgba(255, 230, 230, 0.8);
      box-shadow: 0 0 5px rgba(255, 0, 0, 0.2);
      transition: background-color 0.5s ease;
      position: relative;
      z-index: 0; /* Brain dump 아이템과 간섭 방지 */
    }
    /* 지나간 시간대 스타일 */
    .time-slot.past-time {
      background-color: rgba(242, 242, 242, 0.7);
      transition: background-color 0.5s ease;
    }
    .brain-dump-item.done-item {
      opacity: 0.6;
      background-color: #ddd !important;
      transform: rotate(0deg) !important;
    }
    .brain-dump-item.done-item:hover {
      transform: rotate(0deg) scale(1.05) !important;
    }
    .brain-dump-item .postit-title {
      font-weight: bold;
      font-size: 0.95rem;
      margin-bottom: 0.5rem;
      color: #333;
    }
    .brain-dump-item .postit-content {
      font-size: 0.85rem;
      color: #555;
      flex-grow: 1;
      margin-bottom: 0.5rem;
      overflow-wrap: break-word;
      word-break: break-word;
    }
    .brain-dump-item .postit-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
      margin-top: auto;
    }
    .brain-dump-item .postit-action-bar {
      display: flex;
      justify-content: center;
      gap: 0.25rem;
      margin-top: 0.5rem;
      padding-top: 0.5rem;
      border-top: 1px solid rgba(0,0,0,0.1);
    }
    .brain-dump-item .postit-action-btn {
      background: none;
      border: none;
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
      color: #666;
      cursor: pointer;
      border-radius: 0.25rem;
      transition: background-color 0.2s, color 0.2s;
      flex: 1;
      text-align: center;
    }
    .brain-dump-item .postit-action-btn:hover {
      background-color: rgba(0,0,0,0.05);
      color: #333;
    }
    .brain-dump-item.editing {
      transform: rotate(0deg) !important;
      z-index: 100;
      box-shadow: 4px 4px 12px rgba(0,0,0,0.3);
    }
    .brain-dump-item .postit-title-input,
    .brain-dump-item .postit-content-input {
      width: 100%;
      border: none;
      background: transparent;
      font-family: inherit;
      font-size: inherit;
      padding: 0.25rem;
      margin: 0;
      resize: none;
      outline: none;
    }
    .brain-dump-item .postit-title-input {
      font-weight: bold;
      font-size: 0.95rem;
      margin-bottom: 0.5rem;
    }
    .brain-dump-item .postit-content-input {
      font-size: 0.85rem;
      min-height: 60px;
      flex-grow: 1;
      margin-bottom: 0.5rem;
    }
    .gpt-icon, .check-icon {
      margin-left: 5px; font-size: 1rem; cursor: pointer;
    }
    .gpt-icon:hover, .check-icon:hover {
      color: #3b82f6;
    }
    .todo-slot {
      height: 3rem; border: 2px dashed #e5e7eb; border-radius: 0.5rem;
      background-color: #f9fafb; display: flex; align-items: center;
      padding: 0 1rem; margin-bottom: 0.5rem; transition: transform 0.3s;
    }
    .todo-slot.filled {
      border: 1px solid #ccc; background-color: #fff;
    }
    .todo-slot:hover {
      transform: scale(1.02);
    }
    .todo-form {
      display: flex; gap: 5px; margin-top: 0.5rem;
    }
    .todo-form input {
      padding: 0.25rem 0.5rem; border: 1px solid #ccc;
      font-size: 0.875rem;
    }
    .content-input {
      width: 60%; height: 2rem;
    }
    .todo-form button {
      width: 10%; background-color: #3b82f6; color: #fff;
      border: none; border-radius: 0.25rem; font-size: 0.875rem; cursor: pointer;
    }
    .todo-form button:hover {
      background-color: #2563eb;
    }
    .title-input {
      width: 30%; height: 2rem;
    }
    #todoTitleButton {
      width: 100%; height: 100%; font-size: 0.875rem; cursor: pointer;
    }
    #timePlanContainer {
      background-color: #f7fafc; padding: 1rem;
      border-radius: 0.5rem; max-height: 800px; overflow-y: auto;
    }
    .time-slot {
      display: flex; align-items: center; font-size: 0.875rem;
      border-bottom: 1px solid #e5e7eb; padding: 0.25rem 0;
      padding-left: 10px;
    }
    .time-label {
      width: 4rem; font-weight: 500;
    }
    .time-slot-content {
      flex: 1; min-height: 2rem; display: flex; align-items: center; flex-wrap: nowrap;
    }
    .time-plan-item {
      padding: 0.5rem; margin: 2px; border-radius: 0.25rem;
      cursor: move;
      transition: opacity 0.2s, transform 0.2s;
    }
    .time-plan-item.dragging {
      opacity: 0.5;
      transform: scale(0.95);
    }
    .time-slot-content.drag-over {
      background-color: rgba(59, 130, 246, 0.1);
      border: 2px dashed #3b82f6;
      border-radius: 0.25rem;
    }
    .bg-pink-200   { background-color: #fbcfe8; }
    .bg-yellow-200 { background-color: #fef08a; }
    .bg-purple-200 { background-color: #e9d5ff; }
    .bg-blue-200   { background-color: #bfdbfe; }
    .bg-green-200  { background-color: #bbf7d0; }

    /* 삭제 확인 모달 */
    #modalOverlay {
      position: fixed; top:0; left:0; right:0; bottom:0;
      background: rgba(0,0,0,0.5); display: none;
      justify-content: center; align-items: center;
    }
    #modalDialog {
      background: #fff; padding: 1rem; border-radius: 0.5rem; width: 300px;
    }
    #modalDialog h2 {
      margin-top: 0;
    }
    #modalDialog .modal-buttons {
      margin-top: 1rem; text-align: right;
    }
    #modalDialog button {
      margin-left: 0.5rem;
    }

    /* 체크된 도구들 표시 섹션 */
    #checkedToolsContainer {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 0.5rem;
      min-height: 50px;
      background-color: #fafafa;
    }
    .day-button {
      flex: 1; 
      margin: 0 2px; 
      padding: 0.5rem; 
      border: none; 
      border-radius: 0.25rem; 
      cursor: pointer; 
      background-color: #e2e8f0;
      transition: all 0.2s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .day-button:hover {
      background-color: #bfdbfe;
      transform: translateY(-2px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .day-button .date {
      font-size: 0.8rem;
      margin-top: 3px;
      color: #4b5563;
    }
    .day-button.current {
      background-color: #93c5fd;
      font-weight: bold;
      color: #1e40af;
    }
    
    /* 팝 효과 애니메이션 */
    @keyframes popFadeOut {
      0% {
        transform: scale(1);
        opacity: 1;
      }
      20% {
        transform: scale(1.2);
        opacity: 0.9;
      }
      100% {
        transform: scale(0);
        opacity: 0;
      }
    }
    
    .pop-animation {
      animation: popFadeOut 0.5s ease-out forwards;
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

        <button id="changeRoleButton"><span class="icon">🎭</span>역할</button>       

        <!-- [추가] 15분 뒤로 이동 버튼 -->
        <button id="moveAllForward15Button"><span class="icon">⏭️</span>15분 +</button>
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
          <h2>Brain Dump (30분 이내에 끝낼 수 있로 분할)</h2>
          <div id="brainDumpContainer"></div>
        </div>
        <div class="section" style="margin-top: 1rem;">
          <form id="todoForm" class="todo-form">
            <div id="titleContainer" class="title-input">
              <button style="height: 42px;" id="todoTitleButton" type="button">TASK 선택</button>
            </div>
            <input type="text" id="todoContent" class="content-input" placeholder="내용입력 또는 TASK 선택" required>
            <button type="submit">추가</button>
          </form>
        </div>
        <table width="90%"><tr><td align="center"><button style="height: 42px; background-color:rgb(255, 255, 255); color: black; border: none; border-radius: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.875rem; cursor: pointer;" id="fetchDataButton">🔗연결하기 </button></td><td align="center"><span id="fetchJournalButton"><span class="icon">📄</span>일지</span></td><td align="center"><span id="fetchRecommendedButton"><span class="icon">⭐</span>업무</span></td><td align="center"><span id="fetchCheckedToolsButton"><span class="icon">✔️</span>체크메뉴</span></td><td align="center"><span class="icon"></span></td><td align="center"><a style="text-decoration:none; color:black;" href="https://calendar.google.com/calendar/u/0/appointments/schedules/AcZssZ1_2000000000000000" target="_blank"><img src="https://ssl.gstatic.com/calendar/images/dynamiclogo_2020q4/calendar_11_2x.png" width="40" height="40"></a></td></tr></table> 
        
        <!-- [추가] 체크메뉴 버튼 -->
        
      </div>

      <!-- 우측: Time Plan -->
      <div id="timePlanContainer">
        <h2>Time Boxing Planner</h2>
        <div id="timeSlotsContainer"></div>
        
        <!-- 최근 일주일 요일 버튼 추가 -->
        <div class="week-buttons" style="display:flex; justify-content:space-between; margin-top: 1rem; padding: 0.5rem; background-color: #f0f0f0; border-radius: 0.5rem;">
          <button class="day-button" id="day-0">
            <span>일</span>
            <span class="date" id="date-0"></span>
          </button>
          <button class="day-button" id="day-1">
            <span>월</span>
            <span class="date" id="date-1"></span>
          </button>
          <button class="day-button" id="day-2">
            <span>화</span>
            <span class="date" id="date-2"></span>
          </button>
          <button class="day-button" id="day-3">
            <span>수</span>
            <span class="date" id="date-3"></span>
          </button>
          <button class="day-button" id="day-4">
            <span>목</span>
            <span class="date" id="date-4"></span>
          </button>
          <button class="day-button" id="day-5">
            <span>금</span>
            <span class="date" id="date-5"></span>
          </button>
          <button class="day-button" id="day-6">
            <span>토</span>
            <span class="date" id="date-6"></span>
          </button>
        </div>
      </div>
    </div>

    <!-- [추가] 체크된 도구 표시 섹션 -->
    <div class="section" style="margin-top:1rem; display:none;" id="checkedToolsSection">
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
    let brainDumpItems = [];
    let todoList       = [];
    let timePlan       = [];
    let doneList       = [];

    let selectedTitle  = "";
    let currentTime    = new Date();
    let currentTimeSlot= 0;
    let dragItem       = null;
    let deleteCandidate= null;

    // [추가] 체크된 도구 목록
    let checkedTools   = [];
    
    // [추가] 자정 확인 플래그
    let lastMidnightCheck = 0;

    document.addEventListener("DOMContentLoaded", function(){
      const currentTimeEl      = document.getElementById("currentTime");
      const alertEl            = document.getElementById("alert");
      const brainDumpContainer = document.getElementById("brainDumpContainer");
      const timeSlotsContainer = document.getElementById("timeSlotsContainer");
      const modalOverlay       = document.getElementById("modalOverlay");
      const modalMessage       = document.getElementById("modalMessage");
      const cancelDeleteBtn    = document.getElementById("cancelDelete");
      const confirmDeleteBtn   = document.getElementById("confirmDelete");
      
      // [안정성 강화] 포스트잇 추가 관련 요소 확인
      const todoForm = document.getElementById("todoForm");
      const todoContentInput = document.getElementById("todoContent");
      
      // 요소 존재 여부 확인
      if (!todoForm) {
        console.error("[Goclassroomgame.php:1008] todoForm 요소를 찾을 수 없습니다.");
      }
      if (!todoContentInput) {
        console.error("[Goclassroomgame.php:1011] todoContent 입력 필드를 찾을 수 없습니다.");
      }

      // 체크메뉴 버튼
      const fetchCheckedToolsButton = document.getElementById("fetchCheckedToolsButton");
      fetchCheckedToolsButton.addEventListener("click", fetchCheckedTools);

      // 15분 뒤로 이동 버튼
      const moveAllForward15Button = document.getElementById("moveAllForward15Button");
      moveAllForward15Button.addEventListener("click", moveAllItemsForward15);

      // [추가] 자정 체크 함수
      function checkMidnight() {
        const now = new Date();
        const currentDay = now.getDate();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        
        // 자정 직후(0시 0분~5분)에 확인
        if (currentHour === 0 && currentMinute < 5) {
          // 오늘 아직 체크하지 않았다면
          if (lastMidnightCheck !== currentDay) {
            console.log("자정이 지나 작업 이월을 실행합니다.");
            
            // 자정 체크 플래그 업데이트
            lastMidnightCheck = currentDay;
            
            // 어제 날짜의 미완료 작업 이월 실행
            carryOverTasksFromYesterday();
            
            // 이월 완료 메시지
            Swal.fire({
              title: '작업 이월 완료',
              text: '완료되지 않은 작업들이 오늘로 이월되었습니다.',
              icon: 'info',
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 3000
            });
          }
        }
      }
      
      // [추가] 어제의 미완료 작업을 오늘로 이월하는 함수
      function carryOverTasksFromYesterday() {
        fetch('?action=carryOverTasks')
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              console.log("이월된 작업:", data.carried_tasks.length);
              // 작업 목록 새로고침
              fetchAllTasks();
            }
          })
          .catch(err => console.error("작업 이월 중 오류:", err));
      }

      // timePlanContainer 요소 (스크롤 위치 유지 대상)
      const timePlanContainer  = document.getElementById("timePlanContainer");
      // 이전에 저장된 스크롤 위치 복원
      const savedScrollTop = localStorage.getItem("timePlanScrollTop");
      if (savedScrollTop !== null) {
          timePlanContainer.scrollTop = parseInt(savedScrollTop, 10);
      }
      // 스크롤 이벤트 발생 시 스크롤 위치 저장
      timePlanContainer.addEventListener("scroll", function() {
          localStorage.setItem("timePlanScrollTop", timePlanContainer.scrollTop);
      });

      const colors = ['bg-pink-200','bg-yellow-200','bg-purple-200','bg-blue-200','bg-green-200'];
      function getRandomColor() {
        return colors[Math.floor(Math.random()*colors.length)];
      }

      const timeSlots = Array.from({ length:96 }, (_,i) => ({
        hour: Math.floor(i/4),
        minute: (i%4)*15
      }));

      // (A) roles 불러오기
      fetch('?action=getLatestRoles')
        .then(res=>res.json())
        .then(data=>{
          if(!data) return;
          let roles=[];
          for(let i=1;i<=12;i++){
            let key='role'+i;
            if(data[key]) roles.push(data[key]);
          }
          const selectedRolesContainer = document.getElementById('selectedRoles');
          selectedRolesContainer.innerHTML="";
          roles.forEach(r=>{
            let span=document.createElement('span');
            span.textContent=" 👱🏻 "+r;
            span.style.cursor="pointer";
            span.style.marginRight="10px";
            span.addEventListener("click",function(){
              window.location.href="Goclassroomgame_toolsetting.php?role="+encodeURIComponent(r);
            });
            selectedRolesContainer.appendChild(span);
          });
        })
        .catch(err=>console.error(err));

      // (B) 전체 작업 불러오기
      function fetchAllTasks(){
        console.log("작업 불러오기 시작...");
        fetch('?action=getTasks')
          .then(r => r.json())
          .then(data => {
            console.log("데이터 수신:", data);
            brainDumpItems = data.brainDumpItems || [];
            todoList = data.todoList || [];
            timePlan = data.timePlan || [];
            doneList = data.doneList || [];
            console.log("항목 수: Brain Dump=", brainDumpItems.length, 
                      ", Todo=", todoList.length, 
                      ", Time Plan=", timePlan.length, 
                      ", Done=", doneList.length);
            renderAll();
          })
          .catch(err => {
            console.error("작업 불러오기 오류:", err);
            // 오류 발생 시 빈 데이터라도 렌더링
            brainDumpItems = [];
            todoList = [];
            timePlan = [];
            doneList = [];
            renderAll();
          });
      }

      // [추가] 체크된 도구 가져오기
      function fetchCheckedTools(){
        // 체크된 도구 섹션 요소 가져오기
        const checkedToolsSection = document.getElementById("checkedToolsSection");
        
        // 현재 표시 상태 확인 (display: none이면 숨겨진 상태)
        const isHidden = checkedToolsSection.style.display === "none";
        
        if (isHidden) {
          // 숨겨진 상태면 보이게 하고 데이터 가져오기
          checkedToolsSection.style.display = "block";
          
          // 데이터 가져오기
          fetch('?action=getCheckedTools')
            .then(r=>r.json())
            .then(data=>{
              checkedTools = data; 
              renderCheckedTools();
            })
            .catch(err=>console.error(err));
        } else {
          // 이미 보이는 상태면 숨기기
          checkedToolsSection.style.display = "none";
        }
      }

      // [추가] 체크된 도구 표시
      function renderCheckedTools(){
        const container = document.getElementById("checkedToolsContainer");
        container.innerHTML = "";
        if(!checkedTools || checkedTools.length === 0){
          container.textContent = "체크된 항목이 없습니다.";
          return;
        }
        checkedTools.forEach(tool=>{
          const div = document.createElement("div");
          // 원하는 형식대로 표시
          div.textContent = `[${tool.role}] ${tool.task} - ${tool.description}`;
          // URL 링크도 붙이려면:
          if(tool.url){
            const link = document.createElement("a");
            link.href = tool.url;
            link.target = "_blank";
            link.style.marginLeft = "10px";
            link.textContent = "링크";
            div.appendChild(link);
          }
          container.appendChild(div);
        });
      }

      // (C) BrainDump 항목 추가 - 안정성 강화 버전
      let isAddingItem = false; // 중복 제출 방지 플래그
      function addBrainDumpItem(title, content) {
        // [예외 처리 1] 중복 제출 방지
        if (isAddingItem) {
          console.warn("[Goclassroomgame.php:1187] 이미 추가 작업이 진행 중입니다. 중복 요청을 무시합니다.");
          return;
        }
        
        // [예외 처리 2] 입력값 검증
        const trimmedTitle = (title || '').trim();
        const trimmedContent = (content || '').trim();
        
        if (!trimmedTitle && !trimmedContent) {
          console.error("[Goclassroomgame.php:1193] 제목과 내용이 모두 비어있습니다.");
          Swal.fire({
            title: '입력 오류',
            text: '제목 또는 내용을 입력해주세요.',
            icon: 'warning',
            confirmButtonText: '확인'
          });
          return;
        }
        
        // [예외 처리 3] 제목이 없으면 내용의 첫 부분을 제목으로 사용
        const finalTitle = trimmedTitle || trimmedContent.substring(0, 50) || "새 항목";
        const finalContent = trimmedContent || '';
        
        // 제출 중 플래그 설정
        isAddingItem = true;
        
        // [로딩 상태] 사용자에게 피드백 제공
        const submitButton = todoForm ? todoForm.querySelector('button[type="submit"]') : null;
        const originalButtonText = submitButton ? submitButton.textContent : '';
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.textContent = '추가 중...';
        }
        
        const formData = new FormData();
        formData.append("title", finalTitle);
        formData.append("content", finalContent);
        
        // [에러 처리 강화] 타임아웃 설정
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10초 타임아웃
        
        fetch('?action=addBrainDumpItem', {
          method: 'POST', 
          body: formData,
          signal: controller.signal
        })
          .then(r => {
            clearTimeout(timeoutId);
            // [응답 검증] HTTP 상태 코드 확인
            if (!r.ok) {
              throw new Error(`HTTP 오류: ${r.status} ${r.statusText}`);
            }
            return r.json();
          })
          .then(d => { 
            // [응답 검증] JSON 응답 구조 확인
            if (!d || typeof d !== 'object') {
              throw new Error('서버 응답 형식이 올바르지 않습니다.');
            }
            
            if(d.success) {
              // 성공 시 목록 새로고침
              fetchAllTasks();
              
              // 성공 메시지 (간단한 토스트)
              Swal.fire({
                title: '추가 완료',
                text: '포스트잇이 추가되었습니다.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
              });
              
              // 입력 필드 초기화 (성공 후에만)
              if (todoContentInput) {
                todoContentInput.value = "";
              }
              selectedTitle = "";
              updateTitleButton();
            } else {
              throw new Error(d.message || '항목을 추가할 수 없습니다.');
            }
          })
          .catch(err => {
            clearTimeout(timeoutId);
            console.error('[Goclassroomgame.php:1245] 포스트잇 추가 오류:', err);
            
            // 에러 타입별 메시지
            let errorMessage = '항목을 추가하는 중 오류가 발생했습니다.';
            if (err.name === 'AbortError') {
              errorMessage = '요청 시간이 초과되었습니다. 네트워크 연결을 확인해주세요.';
            } else if (err.message) {
              errorMessage = err.message;
            }
            
            Swal.fire({
              title: '추가 실패',
              text: errorMessage,
              icon: 'error',
              confirmButtonText: '확인'
            });
          })
          .finally(() => {
            // 플래그 해제 및 버튼 상태 복원
            isAddingItem = false;
            if (submitButton) {
              submitButton.disabled = false;
              submitButton.textContent = originalButtonText;
            }
          });
      }
      
      // (C-1) 기존 task ID로 BrainDump 항목 추가 (메모도 복사)
      function addBrainDumpItemWithTaskId(title, content, sourceTaskId) {
        const formData = new FormData();
        formData.append("title", title);
        formData.append("content", content);
        formData.append("source_task_id", sourceTaskId);
        fetch('?action=addBrainDumpItemWithTaskId', {method: 'POST', body: formData})
          .then(r => r.json())
          .then(d => { if(d.success) fetchAllTasks(); })
          .catch(err => console.error(err));
      }

      // (D) 항목을 ToDo로
      function moveToTodoInDB(id){
        const fd=new FormData();
        fd.append("id",id);
        fetch('?action=moveToTodo',{method:'POST',body:fd})
          .then(r=>r.json())
          .then(d=>{ if(d.success) fetchAllTasks(); })
          .catch(err=>console.error(err));
      }

      // (E) 항목을 TimePlan으로
      function moveToTimePlanInDB(id,hour,minute,color){
        const fd=new FormData();
        fd.append("id",id);
        fd.append("hour",hour);
        fd.append("minute",minute);
        fd.append("color",color);
        fetch('?action=moveToTimePlan',{method:'POST',body:fd})
          .then(r=>r.json())
          .then(d=>{ if(d.success) fetchAllTasks(); })
          .catch(err=>console.error(err));
      }

      // (F) 항목을 Done으로
      function moveToDoneInDB(id){
        const fd=new FormData();
        fd.append("id",id);
        fetch('?action=moveToDone',{method:'POST',body:fd})
          .then(r=>r.json())
          .then(d=>{ if(d.success) fetchAllTasks(); })
          .catch(err=>console.error(err));
      }

      // (G) 항목 삭제
      function deleteItemInDB(id){
        const fd=new FormData();
        fd.append("id",id);
        fetch('?action=deleteItem',{method:'POST',body:fd})
          .then(r=>r.json())
          .then(d=>{ if(d.success) fetchAllTasks(); })
          .catch(err=>console.error(err));
      }

      // 이미 삭제 중인지 확인하는 플래그
      let isDeleting = false;

      // 팝 애니메이션 실행 후 항목 삭제
      function deleteWithAnimation(element, id) {
        if (isDeleting) return; // 이미 삭제 중이면 중복 실행 방지
        
        isDeleting = true;
        
        // 애니메이션 적용
        element.classList.add('pop-animation');
        
        // 애니메이션 완료 후 삭제 실행
        element.addEventListener('animationend', function() {
          deleteItemInDB(id);
          isDeleting = false;
        }, { once: true }); // 이벤트는 한 번만 실행
      }

      // (H) 직접입력 -> role='my'
      function addMyTaskToDB(task){
        const fd=new FormData();
        fd.append("task",task);
        fetch('?action=addMyTask',{method:'POST',body:fd})
          .then(r=>r.json())
          .then(d=>{console.log("Add my task done",d);})
          .catch(err=>console.error(err));
      }

      // 렌더링
      function renderAll(){
        renderBrainDump();
        renderTimePlan();
      }

      function renderBrainDump(){
        brainDumpContainer.innerHTML="";
        
        // 모든 항목 합치기 (brain_dump + done)
        const allItems = [...brainDumpItems, ...doneList];
        
        allItems.forEach((item, idx) => {
          const isDone = item.completed || doneList.some(d => d.id === item.id);
          
          const div = document.createElement("div");
          div.className = "brain-dump-item";
          div.dataset.itemId = item.id;
          if (isDone) {
            div.classList.add("done-item");
          }
          
          // 제목 영역 (편집 가능)
          const titleDiv = document.createElement("div");
          titleDiv.className = "postit-title";
          titleDiv.textContent = item.title;
          div.appendChild(titleDiv);
          
          // 내용 영역 (편집 가능)
          const contentDiv = document.createElement("div");
          contentDiv.className = "postit-content";
          contentDiv.textContent = item.content || '';
          div.appendChild(contentDiv);
          
          // 액션 버튼 영역 (상단 우측) - 완료된 항목만 체크 아이콘 표시
          const actionsDiv = document.createElement("div");
          actionsDiv.className = "postit-actions";
          
          if (isDone) {
            // 완료된 항목: 체크 아이콘
            const completedIcon = document.createElement("span");
            completedIcon.className = "check-icon";
            completedIcon.textContent = "✓";
            actionsDiv.appendChild(completedIcon);
          }
          
          div.appendChild(actionsDiv);
          
          // 하단 액션 바 (미니멀한 버튼)
          const actionBar = document.createElement("div");
          actionBar.className = "postit-action-bar";
          
          // TODO로 이동 버튼 (우측 Time Boxing Planner로 바로 이동)
          const todoBtn = document.createElement("button");
          todoBtn.className = "postit-action-btn";
          todoBtn.textContent = "TODO";
          todoBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            // Time Plan에 3개 이상이면 경고 표시
            if (timePlan.length >= 3) {
              Swal.fire({
                title: "Problem | Decision | Impact | Data !",
                text: "가장 중요한 일을 하는 것이 가장 중요하다",
                icon: 'warning',
                confirmButtonText: "확인"
              });
              return;
            }
            // 다음 시간대로 바로 이동
            let nextSlot = findNextSectionSlot();
            const color = getRandomColor();
            moveToTimePlanInDB(item.id, nextSlot.hour, nextSlot.minute, color);
          });
          actionBar.appendChild(todoBtn);
          
          // 삭제 버튼
          const deleteBtn = document.createElement("button");
          deleteBtn.className = "postit-action-btn";
          deleteBtn.textContent = "삭제";
          deleteBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            Swal.fire({
              title: '항목 삭제',
              text: `"${item.title}"을(를) 삭제하시겠습니까?`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: '삭제',
              cancelButtonText: '취소'
            }).then((result) => {
              if (result.isConfirmed) {
                // 실제 삭제 실행
                deleteItemInDB(item.id);
              }
            });
          });
          actionBar.appendChild(deleteBtn);
          
          // ChatGPT 링크 버튼 (3번째 위치)
          if (!isDone) {
            const linkBtn = document.createElement("button");
            linkBtn.className = "postit-action-btn";
            linkBtn.textContent = "🔗";
            linkBtn.title = "ChatGPT 열기";
            linkBtn.addEventListener("click", function(e) {
              e.stopPropagation();
              const url = item.url ? item.url : "https://chatgpt.com/?model=o3-mini";
              window.open(url, "_blank");
            });
            actionBar.appendChild(linkBtn);
          }
          
          div.appendChild(actionBar);
          
          // 포스트잇 클릭 시 편집 모드로 전환
          div.addEventListener("click", function(e) {
            // 버튼이나 아이콘 클릭은 제외
            if (e.target.classList.contains('postit-action-btn') || 
                e.target.classList.contains('check-icon') ||
                e.target.classList.contains('postit-title-input') ||
                e.target.classList.contains('postit-content-input')) {
              return;
            }
            
            // 이미 편집 중이면 무시
            if (div.classList.contains('editing')) {
              return;
            }
            
            // 편집 모드로 전환
            enterEditMode(div, item);
          });
          
          brainDumpContainer.appendChild(div);
        });
      }
      
      // 편집 모드 진입 함수
      function enterEditMode(postitDiv, item) {
        postitDiv.classList.add('editing');
        
        const titleDiv = postitDiv.querySelector('.postit-title');
        const contentDiv = postitDiv.querySelector('.postit-content');
        
        // 제목을 input으로 변경
        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.className = 'postit-title-input';
        titleInput.value = item.title || '';
        titleDiv.replaceWith(titleInput);
        
        // 내용을 textarea로 변경
        const contentInput = document.createElement('textarea');
        contentInput.className = 'postit-content-input';
        contentInput.value = item.content || '';
        contentDiv.replaceWith(contentInput);
        
        // 포커스 설정
        titleInput.focus();
        titleInput.select();
        
        // Enter 키로 저장, Escape로 취소
        titleInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            contentInput.focus();
          }
        });
        
        contentInput.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            exitEditMode(postitDiv, item, false);
          }
        });
        
        // 포스트잇 외부 클릭 시 저장
        const saveOnBlur = function(e) {
          if (!postitDiv.contains(e.target)) {
            exitEditMode(postitDiv, item, true);
            document.removeEventListener('click', saveOnBlur);
          }
        };
        
        setTimeout(() => {
          document.addEventListener('click', saveOnBlur);
        }, 100);
      }
      
      // 편집 모드 종료 함수
      function exitEditMode(postitDiv, item, save) {
        const titleInput = postitDiv.querySelector('.postit-title-input');
        const contentInput = postitDiv.querySelector('.postit-content-input');
        
        if (!titleInput || !contentInput) {
          return;
        }
        
        const newTitle = titleInput.value.trim();
        const newContent = contentInput.value.trim();
        
        if (save && (newTitle !== item.title || newContent !== (item.content || ''))) {
          // DB 업데이트
          updateTaskInDB(item.id, newTitle, newContent);
        }
        
        // 원래 형태로 복원
        const titleDiv = document.createElement('div');
        titleDiv.className = 'postit-title';
        titleDiv.textContent = newTitle || item.title;
        titleInput.replaceWith(titleDiv);
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'postit-content';
        contentDiv.textContent = newContent || item.content || '';
        contentInput.replaceWith(contentDiv);
        
        postitDiv.classList.remove('editing');
      }
      
      // DB 업데이트 함수
      function updateTaskInDB(id, title, content) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('title', title);
        formData.append('content', content);
        
        fetch('?action=updateTask', {
          method: 'POST',
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            // 목록 새로고침
            fetchAllTasks();
          } else {
            Swal.fire({
              title: '저장 실패',
              text: data.message || '항목을 저장할 수 없습니다.',
              icon: 'error',
              confirmButtonText: '확인'
            });
          }
        })
        .catch(err => {
          console.error('업데이트 오류:', err);
          Swal.fire({
            title: '저장 실패',
            text: '네트워크 오류가 발생했습니다.',
            icon: 'error',
            confirmButtonText: '확인'
          });
        });
      }

      // renderTodoList 함수 제거됨 - To Do List 섹션 제거됨

      // 현재 시간 기준 다음 섹션으로 이동하기 위한 함수 추가
      function findNextSectionSlot() {
        // 현재 시간을 가져옴
        const nowHour = currentTime.getHours();
        const nowMinute = currentTime.getMinutes();
        
        // 시간을 1시간 단위로 나누어 다음 시간대 계산 (예: 현재 9:20이면 10:00으로 이동)
        let nextHour = nowHour + 1;
        let nextMinute = 0;
        
        // 23시 이후라면 다음날 첫 시간대인 0시로 설정
        if (nextHour >= 24) {
          nextHour = 0;
        }
        
        return { hour: nextHour, minute: nextMinute };
      }

      function renderTimePlan(){
        timeSlotsContainer.innerHTML = "";
        const nowMinutes = currentTime.getHours() * 60 + currentTime.getMinutes();

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

          if(slotIndex < currentTimeSlot - 4){
            row.style.display = "none";
          }

          // 현재 시간대 표시를 위한 배경색 - CSS 클래스 사용
          if (nowMinutes >= slotMinutes && nowMinutes < slotMinutes + 15) {
            row.classList.add("current-time");
          } else if (slotMinutes < nowMinutes) {
            // 지나간 시간은 past-time 클래스 사용
            if (row.style.display !== "none") {
              row.classList.add("past-time");
            }
          }

          const contentArea = document.createElement("div");
          contentArea.className = "time-slot-content";
          contentArea.dataset.hour = slot.hour;
          contentArea.dataset.minute = slot.minute;
          
          // 드래그 오버 시 시각적 피드백
          contentArea.addEventListener("dragover", e => {
            e.preventDefault();
            e.stopPropagation();
            contentArea.classList.add("drag-over");
          });
          
          // 드래그 리브 시 피드백 제거
          contentArea.addEventListener("dragleave", e => {
            e.preventDefault();
            e.stopPropagation();
            // 마우스가 실제로 영역을 벗어났는지 확인
            if (!contentArea.contains(e.relatedTarget)) {
              contentArea.classList.remove("drag-over");
            }
          });
          
          // 드롭 이벤트
          contentArea.addEventListener("drop", e => {
            e.preventDefault();
            e.stopPropagation();
            contentArea.classList.remove("drag-over");
            
            if (dragItem) {
              // 기존 색상 유지 (새 색상으로 변경하지 않음)
              const existingColor = dragItem.color || 'bg-blue-200';
              moveToTimePlanInDB(dragItem.id, slot.hour, slot.minute, existingColor);
              dragItem = null;
            }
          });

          timePlan
            .filter(item => Number(item.hour) === slot.hour && Number(item.minute) === slot.minute)
            .forEach(item => {
              const itemDiv = document.createElement("div");
              itemDiv.className = "time-plan-item " + (item.color || 'bg-blue-200');
              itemDiv.setAttribute("title", item.content || "");
              itemDiv.draggable = true;

              const titleSpan = document.createElement("span");
              titleSpan.textContent = item.title;
              itemDiv.appendChild(titleSpan);

              const toolIcon = document.createElement("span");
              toolIcon.className = "gpt-icon";
              toolIcon.textContent = "🔗";
              toolIcon.addEventListener("click", function(e) {
                e.stopPropagation();
                const url = item.url ? item.url : "https://chatgpt.com/?model=o3-mini";
                window.open(url, "_blank");
              });
              itemDiv.appendChild(toolIcon);

              itemDiv.addEventListener("dragstart", function(e) {
                dragItem = item;
                this.classList.add("dragging");
                e.dataTransfer.effectAllowed = "move";
                e.dataTransfer.setData("text/html", this.innerHTML);
              });
              
              itemDiv.addEventListener("dragend", function(e) {
                this.classList.remove("dragging");
                // 모든 드롭 영역의 drag-over 클래스 제거
                document.querySelectorAll(".time-slot-content").forEach(area => {
                  area.classList.remove("drag-over");
                });
              });
              itemDiv.addEventListener("dblclick", function(e) {
                e.stopPropagation();
                window.location.href = "Goclassroomgame_detail.php?title=" +
                                        encodeURIComponent(item.title) +"&taskid="+item.id
                                        +"&userid=<?php echo $userid;?>";
              });

              contentArea.appendChild(itemDiv);
            });
          row.appendChild(contentArea);
          timeSlotsContainer.appendChild(row);
        });
      }

      function findNextAvailableSlot(){
        if(currentTime.getHours()>=23){
          return {hour:23,minute:45};
        }
        for(let i=currentTimeSlot;i<timeSlots.length;i++){
          const slot=timeSlots[i];
          const count=timePlan.filter(item=>
            Number(item.hour)===slot.hour && Number(item.minute)===slot.minute && !item.completed
          ).length;
          if(count<3) return slot;
        }
        return timeSlots[currentTimeSlot];
      }

      function autoMoveTimePlanItems(){
        const nowMinutes=currentTime.getHours()*60+currentTime.getMinutes();
        timePlan.forEach(item=>{
          if(!item.completed && item.hour!==null && item.minute!==null){
            const itemMin=Number(item.hour)*60+Number(item.minute);
            if(nowMinutes>itemMin){
              const nextSlot=findNextAvailableSlot();
              if(nextSlot){
                moveToTimePlanInDB(item.id,nextSlot.hour,nextSlot.minute,item.color||'bg-blue-200');
              }
            }
          }
        });
      }

      function updateTime(){
        let localTime=new Date();
        let utc=localTime.getTime()+(localTime.getTimezoneOffset()*60000);
        let kst=new Date(utc+(9*60*60*1000));
        currentTime=kst;

        document.getElementById("currentTime").textContent
          = kst.toLocaleTimeString('ko-KR',{hour12:false});
        const hour=kst.getHours();
        const minute=kst.getMinutes();
        currentTimeSlot=hour*4+Math.floor(minute/15);
      }

      function showTemporaryAlert(){
        alertEl.style.display="block";
        setTimeout(()=>{alertEl.style.display="none";},3000);
      }

      // 애니메이션 함수 제거 - 포스트잇 게시판 형식에서는 애니메이션 불필요

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

      function saveRolesToDB(rolesInEnglish) {
        const fd = new FormData();
        for (let i = 0; i < rolesInEnglish.length; i++) {
          fd.append('role' + (i + 1), rolesInEnglish[i]);
        }
        fetch('?action=saveRoles', { method: 'POST', body: fd })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              console.log("Roles saved successfully");
            }
          })
          .catch(err => console.error(err));
      }

      // 역할 선택 버튼
      document.getElementById("changeRoleButton").addEventListener("click",function(){
        Swal.fire({
          title:'역할 선택',
          html:`
            <div style="text-align:left;">
              <label><input type="checkbox" value="선생님"> 선생님</label><br>
              <label><input type="checkbox" value="컨텐츠 연구원"> 컨텐츠 연구원</label><br>
              <label><input type="checkbox" value="앱 개발자"> 앱 개발자</label><br>
              <label><input type="checkbox" value="프로젝트 메니저"> 프로젝트 메니저</label><br>
              <label><input type="checkbox" value="컨텐츠 크리에이터"> 컨텐츠 크리에이터</label><br>
              <label><input type="checkbox" value="학원 관리자"> 학원 관리자</label><br>
            </div>
          `,
          showCancelButton:true,
          confirmButtonText:'선택 완료'
        }).then((result)=>{
          if(result.isConfirmed){
            let selectedRoles=[];
            const cbs=Swal.getPopup().querySelectorAll('input[type="checkbox"]');
            cbs.forEach(chk=>{
              if(chk.checked) selectedRoles.push(chk.value);
            });
            const sel=document.getElementById('selectedRoles');
            sel.innerHTML="";
            if(selectedRoles.length>0){
              let rolesInEnglish=selectedRoles.map(r=>toEnglishRole(r));
              saveRolesToDB(rolesInEnglish);
              rolesInEnglish.forEach(rr=>{
                let sp=document.createElement('span');
                sp.textContent=" 👱🏻 "+rr;
                sp.style.cursor="pointer";
                sp.style.marginRight="10px";
                sp.addEventListener("click",function(){
                  window.location.href="Goclassroomgame_toolsetting.php?role="+encodeURIComponent(rr);
                });
                sel.appendChild(sp);
              });
            }else{
              sel.textContent="";
            }
          }
        });
      });

      // [안정성 강화] TASK 선택 버튼 이벤트
      const todoTitleButton = document.getElementById("todoTitleButton");
      if (todoTitleButton) {
        todoTitleButton.addEventListener("click", function(e) {
          e.preventDefault();
          e.stopPropagation();
          // 항상 메뉴 표시
          chooseTodoTitle();
        });
      } else {
        console.error("[Goclassroomgame.php:1820] todoTitleButton 요소를 찾을 수 없습니다.");
      }

      // [안정성 강화] 폼 제출 이벤트 핸들러
      if (todoForm && todoContentInput) {
        todoForm.addEventListener("submit", function(e) {
          e.preventDefault();
          
          // [예외 처리] 요소가 여전히 존재하는지 확인
          if (!todoContentInput) {
            console.error("[Goclassroomgame.php:1830] todoContent 입력 필드가 존재하지 않습니다.");
            Swal.fire({
              title: '시스템 오류',
              text: '입력 필드를 찾을 수 없습니다. 페이지를 새로고침해주세요.',
              icon: 'error',
              confirmButtonText: '확인'
            });
            return;
          }
          
          const content = todoContentInput.value.trim();
          
          // [검증] 내용과 TASK 모두 없는 경우
          if (!content && !selectedTitle) {
            Swal.fire({
              title: '내용을 입력해주세요',
              text: '내용 입력 필드에 내용을 입력하거나 TASK를 선택해주세요.',
              icon: 'warning',
              confirmButtonText: '확인'
            });
            // 입력 필드에 포커스
            todoContentInput.focus();
            return;
          }
          
          // 포스트잇 추가 실행 (입력 필드 초기화는 addBrainDumpItem 내부에서 성공 시 처리)
          addBrainDumpItem(selectedTitle || content, content);
        });
      } else {
        console.error("[Goclassroomgame.php:1850] todoForm 또는 todoContentInput 요소를 찾을 수 없습니다. 포스트잇 추가 기능이 작동하지 않을 수 있습니다.");
      }

      function fetchRoleTasks(rolesArr){
        if(!rolesArr||rolesArr.length===0) return Promise.resolve([]);
        const fd=new FormData();
        rolesArr.forEach(r=>fd.append('roles[]',r));
        return fetch('?action=getRoleTasks',{method:'POST',body:fd})
          .then(r=>r.json())
          .catch(err=>{
            console.error(err);
            return [];
          });
      }

      function chooseTodoTitle(){
        // 항상 'my' 추가
        const roleSpans=document.querySelectorAll('#selectedRoles span');
        let rolesArr=['my'];
        roleSpans.forEach(sp=>{
          let txt=sp.textContent.trim().replace('👱🏻','').trim();
          if(txt) rolesArr.push(txt);
        });
        fetchRoleTasks(rolesArr).then(taskList=>{
          if(!taskList||taskList.length===0){
            showTodoTitlePopup([]);
          }else{
            showTodoTitlePopup(taskList);
          }
        });
      }

      function showTodoTitlePopup(taskList){
        let htmlContent="";
        taskList.forEach((task,idx)=>{
          htmlContent+=`
            <button class="swal2-confirm swal2-styled"
                    id="taskButton${idx}"
                    style="display:block;width:100%;margin:5px 0;">${task}</button>
          `;
        });
        htmlContent+=`
          <button class="swal2-confirm swal2-styled"
                  id="optionDirect"
                  style="display:block;width:100%;margin:5px 0;">직접입력</button>
        `;
        Swal.fire({
          title:'업무 선택',
          html:htmlContent,
          showConfirmButton:false
        });
        taskList.forEach((task,idx)=>{
          const btn=document.getElementById(`taskButton${idx}`);
          if(btn){
            btn.addEventListener("click",function(){
              selectedTitle=task;
              Swal.close();
              updateTitleButton();
            });
          }
        });
        const directBtn=document.getElementById("optionDirect");
        if(directBtn){
          directBtn.addEventListener("click",function(){
            Swal.close();
            Swal.fire({
              title:'직접 입력',
              input:'text',
              inputPlaceholder:'업무 이름 입력',
              showCancelButton:true,
              confirmButtonText:'입력'
            }).then((result)=>{
              if(result.isConfirmed && result.value){
                selectedTitle=result.value;
                addMyTaskToDB(selectedTitle);
                updateTitleButton();
              }
            });
          });
        }
      }

      function updateTitleButton(){
        document.getElementById("todoTitleButton").innerText=selectedTitle||"업무 선택";
      }

      // 삭제 모달
      cancelDeleteBtn.addEventListener("click",closeDeleteDialog);
      confirmDeleteBtn.addEventListener("click",confirmDeletion);
      function openDeleteDialog(item){
        deleteCandidate=item;
        modalMessage.textContent=`"${item.title}"을(를) 삭제하시겠습니까?`;
        modalOverlay.style.display="flex";
      }
      function closeDeleteDialog(){
        deleteCandidate=null;
        modalOverlay.style.display="none";
      }
      function confirmDeletion(){
        if(deleteCandidate){
          const element = document.querySelector(`.brain-dump-item[data-index="${deleteCandidate.index}"]`);
          if (element) {
            deleteWithAnimation(element, deleteCandidate.id);
          } else {
            deleteItemInDB(deleteCandidate.id);
          }
          closeDeleteDialog();
        }
      }

      // 자동 이동 + 시간 업데이트
      function mainLoop(){
        updateTime();
        autoMoveTimePlanItems();
        
        // [추가] 자정 체크
        checkMidnight();
      }
      setInterval(mainLoop,1000);
      // 애니메이션 제거됨 - 포스트잇 게시판 형식 사용

      // 초기 로드
      fetchAllTasks();

      // [추가] 페이지 로드 시 이월 확인
      // 자정 직후 사용자가 페이지에 방문하면 PHP에서 처리됨
      
      // BrainDump에서도 drop -> done
      brainDumpContainer.addEventListener("dragover",e=>e.preventDefault());
      brainDumpContainer.addEventListener("drop",e=>{
        e.preventDefault();
        if(dragItem){
          moveToDoneInDB(dragItem.id);
          dragItem=null;
        }
      });

      // [추가] 모든 항목 15분 뒤로 이동하는 함수
      function moveAllItemsForward15() {
        Swal.fire({
          title: '모든 항목을 15분 뒤로 이동',
          text: '시간표에 있는 모든 항목을 15분 뒤로 이동하시겠습니까?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: '이동',
          cancelButtonText: '취소'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('?action=moveAllItemsForward15')
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  Swal.fire({
                    title: '완료',
                    text: '모든 항목이 15분 뒤로 이동되었습니다.',
                    icon: 'success',
                    confirmButtonText: '확인'
                  });
                  fetchAllTasks(); // 변경된 데이터로 화면 갱신
                }
              })
              .catch(err => {
                console.error(err);
                Swal.fire({
                  title: '오류',
                  text: '작업 처리 중 오류가 발생했습니다.',
                  icon: 'error',
                  confirmButtonText: '확인'
                });
              });
          }
        });
      }

      // 요일 버튼 이벤트 리스너 추가
      const dayButtons = document.querySelectorAll('.day-button');
      dayButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          const dayIndex = parseInt(this.id.split('-')[1]);
          showTasksForDay(dayIndex);
        });
      });
      
      // 현재 요일 강조 표시
      function highlightCurrentDay() {
        const today = new Date();
        const currentDayIndex = today.getDay(); // 0: 일요일, 1: 월요일, ...
        
        // 요일 버튼에 날짜 표시
        for (let i = 0; i < 7; i++) {
          const date = new Date(today);
          const diff = i - currentDayIndex;
          date.setDate(today.getDate() + diff);
          
          // 날짜 표시
          const dateSpan = document.getElementById(`date-${i}`);
          if (dateSpan) {
            dateSpan.textContent = `${date.getMonth()+1}/${date.getDate()}`;
          }
          
          // 현재 요일이면 클래스 추가
          const dayButton = document.getElementById(`day-${i}`);
          if (dayButton) {
            if (i === currentDayIndex) {
              dayButton.classList.add('current');
            }
          }
        }
      }
      
      // 페이지 로드 시 현재 요일 강조
      highlightCurrentDay();

      // 특정 요일의 작업 가져와서 팝업으로 표시하는 함수
      function showTasksForDay(dayIndex) {
        // 현재 날짜를 기준으로 요일 계산
        const today = new Date();
        const currentDayOfWeek = today.getDay(); // 0: 일요일, 1: 월요일, ...
        
        // 현재 날짜로부터 선택한 요일까지의 차이 계산
        const diff = dayIndex - currentDayOfWeek;
        
        // 선택한 날짜 계산
        const targetDate = new Date(today);
        targetDate.setDate(today.getDate() + diff);
        
        // Unix 타임스탬프로 변환 (초 단위)
        const timestamp = Math.floor(targetDate.getTime() / 1000);
        
        // 해당 날짜의 작업 가져오기
        fetch(`?action=getTasksByDate&date=${timestamp}`)
          .then(r => r.json())
          .then(data => {
            // 팝업으로 작업 목록 표시
            showTasksPopup(data, targetDate);
          })
          .catch(err => console.error('날짜별 작업 가져오기 오류:', err));
      }

      // 작업 목록을 팝업으로 표시
      function showTasksPopup(data, date) {
        // 표시할 데이터 준비
        const allTasks = [
          ...(data.brainDumpItems || []),
          ...(data.todoList || []),
          ...(data.timePlan || []),
          ...(data.doneList || [])
        ];
        
        if (allTasks.length === 0) {
          Swal.fire({
            title: `${date.getFullYear()}년 ${date.getMonth()+1}월 ${date.getDate()}일 작업`,
            text: '이 날짜에 등록된 작업이 없습니다.',
            icon: 'info',
            confirmButtonText: '확인'
          });
          return;
        }
        
        // 작업 목록 HTML 생성
        let tasksHtml = `<div style="max-height: 400px; overflow-y: auto;">`;
        allTasks.forEach((task, index) => {
          const status = task.completed ? '[완료]' : '';
          const timeInfo = (task.hour !== null && task.minute !== null) 
            ? ` (${String(task.hour).padStart(2, '0')}:${String(task.minute).padStart(2, '0')})` 
            : '';
          
          tasksHtml += `
            <div class="task-item" data-id="${task.id}" data-index="${index}" 
                 style="padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; 
                        ${task.color ? `background-color: ${getColorClass(task.color)};` : ''}">
              <strong>${task.title}</strong>${timeInfo} ${status}
              <p style="margin: 2px 0 0 0; font-size: 0.9em;">${task.content || ''}</p>
              <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                <button class="add-to-brain-dump" style="background-color: #3b82f6; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; cursor: pointer;">브레인덤프로</button>
                ${task.completed ? `<button class="delete-task" style="background-color: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; margin-left: 5px; font-size: 0.8em; cursor: pointer;">삭제</button>` : ''}
              </div>
            </div>
          `;
        });
        tasksHtml += `</div>`;
        
        Swal.fire({
          title: `${date.getFullYear()}년 ${date.getMonth()+1}월 ${date.getDate()}일 작업`,
          html: tasksHtml,
          confirmButtonText: '닫기',
          width: 600,
          didOpen: () => {
            // 브레인 덤프 추가 버튼 이벤트
            const addButtons = Swal.getPopup().querySelectorAll('.add-to-brain-dump');
            addButtons.forEach(button => {
              button.addEventListener('click', function(e) {
                e.stopPropagation(); // 이벤트 버블링 방지
                const taskItem = this.closest('.task-item');
                const taskId = taskItem.getAttribute('data-id');
                const taskIndex = taskItem.getAttribute('data-index');
                const task = allTasks[taskIndex];
                
                // taskId를 유지하면서 브레인 덤프에 추가 (메모 데이터도 함께 복사)
                addBrainDumpItemWithTaskId(task.title, task.content || '', taskId);
                
                // 성공 메시지
                Swal.fire({
                  title: '추가 완료',
                  text: '브레인 덤프에 추가되었습니다. (메모 데이터도 함께 복사됨)',
                  icon: 'success',
                  timer: 2000,
                  showConfirmButton: false
                });
              });
            });
            
            // 삭제 버튼 이벤트
            const deleteButtons = Swal.getPopup().querySelectorAll('.delete-task');
            deleteButtons.forEach(button => {
              button.addEventListener('click', function(e) {
                e.stopPropagation(); // 이벤트 버블링 방지
                const taskItem = this.closest('.task-item');
                const taskId = taskItem.getAttribute('data-id');
                const taskIndex = taskItem.getAttribute('data-index');
                const task = allTasks[taskIndex];
                
                // 삭제 확인
                Swal.fire({
                  title: '항목 삭제',
                  text: `"${task.title}"을(를) 삭제하시겠습니까?`,
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: '삭제',
                  cancelButtonText: '취소'
                }).then((result) => {
                  if (result.isConfirmed) {
                    // 애니메이션 적용
                    taskItem.classList.add('pop-animation');
                    
                    // 애니메이션 완료 후 삭제
                    taskItem.addEventListener('animationend', function() {
                      deleteItemInDB(taskId);
                      
                      // 목록에서 제거
                      taskItem.remove();
                      
                      // 모든 항목이 삭제되었는지 확인
                      if (Swal.getPopup().querySelectorAll('.task-item').length === 0) {
                        Swal.close(); // 팝업 닫기
                      }
                    }, { once: true });
                  }
                });
              });
            });
          }
        });
      }
      
      // 색상 클래스를 실제 CSS 색상으로 변환
      function getColorClass(colorClass) {
        const colorMap = {
          'bg-pink-200': '#fbcfe8',
          'bg-yellow-200': '#fef08a',
          'bg-purple-200': '#e9d5ff',
          'bg-blue-200': '#bfdbfe',
          'bg-green-200': '#bbf7d0'
        };
        return colorMap[colorClass] || '#f3f4f6';
      }
    });
  </script>
  
<table align="center"><tr><td align="center"><iframe width="560" height="315" src="https://www.youtube.com/embed/Rd9cWcizizk?si=-a4dbv53YZDERNXW" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></td></tr></table>
</body>
</html>
