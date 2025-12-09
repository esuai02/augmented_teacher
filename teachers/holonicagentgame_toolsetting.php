<?php
// 예) moodle 환경 불러오기
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 1) GET 파라미터 처리
$role = isset($_GET['role']) ? $_GET['role'] : '기본 역할';

// userid 파라미터가 있으면 사용, 없으면 로그인 유저($USER->id)
$userid = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;

// 2) AJAX 요청 처리 (체크 상태 업데이트 및 표준 작업 추가)
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_GET['action'] === 'updateSelect') {
        $toolsetting_id = intval($_POST['agent_toolsetting_id'] ?? 0);
        $checked = intval($_POST['checked'] ?? 0);

        if ($toolsetting_id > 0 && $userid > 0) {
            // 기존 레코드가 있는지 확인
            $record = $DB->get_record('agent_usertoolsettings', [
                'user_id' => $userid,
                'agent_toolsetting_id' => $toolsetting_id
            ]);

            if ($record) {
                // update
                $record->checked = $checked;
                $record->timecreated = time();
                $DB->update_record('agent_usertoolsettings', $record);
            } else {
                // insert
                $newrec = new stdClass();
                $newrec->user_id = $userid;
                $newrec->agent_toolsetting_id = $toolsetting_id;
                $newrec->checked = $checked;
                $newrec->timecreated = time();
                $DB->insert_record('agent_usertoolsettings', $newrec);
            }

            // checked = 1이고 이 항목이 todolist에 추가되는 경우
            if ($checked == 1) {
                // toolsetting 정보 가져오기
                $toolsetting = $DB->get_record('agent_toolsettings', ['id' => $toolsetting_id]);
                if ($toolsetting && !empty($toolsetting->taskid)) {
                    // 현재 task에 연결된 메모 확인
                    $memos = $DB->get_records('agent_dashboard_memos', ['taskid' => $toolsetting->taskid]);
                    
                    // todolist에 task 추가 (이 부분은 실제 todolist에 추가하는 로직에 맞게 수정 필요)
                    // 예시: 새로운 task 생성
                    $new_task = new stdClass();
                    $new_task->title = $toolsetting->task;
                    $new_task->description = $toolsetting->description;
                    $new_task->userid = $userid;
                    $new_task->timecreated = time();
                    // 필요한 필드 추가
                    
                    $new_task_id = $DB->insert_record('agent_tasks', $new_task); // 실제 테이블명으로 수정 필요
                    
                    // 찾은 메모들을 새 task에 복사
                    foreach ($memos as $memo) {
                        $new_memo = new stdClass();
                        $new_memo->taskid = $new_task_id;
                        $new_memo->userid = $userid;
                        $new_memo->content = $memo->content;
                        $new_memo->timecreated = time();
                        // 필요한 필드 추가
                        
                        $DB->insert_record('agent_dashboard_memos', $new_memo);
                    }
                }
            }

            // checked = 1이고 이 항목이 todolist에 추가되는 경우
            if ($checked == 1) {
                // toolsetting 정보 가져오기
                $toolsetting = $DB->get_record('agent_toolsettings', ['id' => $toolsetting_id]);
                if ($toolsetting && !empty($toolsetting->taskid)) {
                    // 원본 task ID
                    $original_task_id = intval($toolsetting->taskid);
                    
                    // Todo List에 새 task 추가
                    $new_task = new stdClass();
                    $new_task->title = $toolsetting->task;
                    $new_task->description = $toolsetting->description;
                    $new_task->userid = $userid;
                    $new_task->status = 'open'; // 상태 필드가 있다면
                    $new_task->priority = 'medium'; // 우선순위 필드가 있다면
                    $new_task->timecreated = time();
                    $new_task->timemodified = time();
                    
                    // Todo List 테이블에 새 task 추가
                    $new_task_id = $DB->insert_record('agent_todos', $new_task); // Todo List 테이블명에 맞게 수정
                    
                    if ($new_task_id) {
                        // 메모 복사: 원본 task ID와 연결된 모든 메모 조회
                        $memos_sql = "
                            SELECT * FROM mdl_agent_dashboard_memos 
                            WHERE taskid = ?
                        ";
                        $memos = $DB->get_records_sql($memos_sql, [$original_task_id]);
                        
                        // 각 메모를 새 task ID로 복사
                        foreach ($memos as $memo) {
                            $new_memo = new stdClass();
                            $new_memo->taskid = $new_task_id; // 새 task ID로 연결
                            $new_memo->userid = $userid;
                            $new_memo->content = $memo->content;
                            $new_memo->color = $memo->color ?? ''; // 색상 필드가 있다면
                            $new_memo->position = $memo->position ?? ''; // 위치 필드가 있다면
                            $new_memo->timecreated = time();
                            $new_memo->timemodified = time();
                            
                            // 새 메모 저장
                            $DB->insert_record('agent_dashboard_memos', $new_memo);
                        }

                        $memos = $DB->get_records_sql($memos_sql, [$original_task_id]);
                        
                        // 연관된 메모가 있으면 각 메모를 새 task ID로 복사
                        if (!empty($memos)) {
                            foreach ($memos as $memo) {
                                $new_memo = new stdClass();
                                $new_memo->taskid = $new_task_id; // 새 task ID로 연결
                                $new_memo->userid = $userid;
                                $new_memo->content = $memo->content;
                                $new_memo->color = $memo->color ?? ''; // 색상 필드가 있다면
                                $new_memo->position = $memo->position ?? ''; // 위치 필드가 있다면
                                $new_memo->timecreated = time();
                                $new_memo->timemodified = time();
                                
                                // 새 메모 저장
                                $DB->insert_record('agent_dashboard_memos', $new_memo);
                                
                                // 디버깅용 로그
                                error_log("메모가 복사됨: 원본 taskid={$original_task_id}, 새 taskid={$new_task_id}, 내용={$memo->content}");
                            }
                        } else {
                            // 디버깅용 로그
                            error_log("복사할 메모가 없음: 원본 taskid={$original_task_id}");
                        }
                    }
                }
            }

            echo json_encode(['success' => true]);
            exit();
        } else {
            echo json_encode(['success' => false, 'msg' => 'invalid params']);
            exit();
        }
    } else if ($_GET['action'] === 'addStandardTask') {
        // 표준 작업을 toolsettings에 추가
        $taskid = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $task_title = isset($_POST['task_title']) ? trim($_POST['task_title']) : '';
        $task_description = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
        
        if ($task_title && $role) {
            $newdata = new stdClass();
            $newdata->role = $role;
            $newdata->task = $task_title;
            $newdata->description = $task_description;
            $newdata->url = ''; // 기본 URL은 비워둠
            $newdata->taskid = $taskid; // taskid 저장
            $newdata->timecreated = time();
            $newdata->timemodified = time();
            
            $new_id = $DB->insert_record('agent_toolsettings', $newdata);
            
            if ($new_id) {
                // 1. agent_tasks 테이블에 신규 task 생성
                $new_task = new stdClass();
                $new_task->title = $task_title;
                $new_task->description = $task_description;
                $new_task->userid = $userid;
                $new_task->status = 'open'; // 기본 상태
                $new_task->priority = 'medium'; // 기본 우선순위
                $new_task->timecreated = time();
                $new_task->timemodified = time();
                
                // agent_tasks 테이블에 새 레코드 추가하고 ID 받기
                $new_task_id = $DB->insert_record('agent_tasks', $new_task);
                
                // 2. 새로 생성된 task ID를 toolsettings에 저장 (참조용)
                if ($new_task_id) {
                    $toolsetting = $DB->get_record('agent_toolsettings', ['id' => $new_id]);
                    if ($toolsetting) {
                        $toolsetting->task_ref_id = $new_task_id; // 새 task ID 참조 저장
                        $DB->update_record('agent_toolsettings', $toolsetting);
                    }
                    
                    // 3. 원본 taskid가 있으면 관련 메모 복사
                    if ($taskid > 0) {
                        // 원본 task ID와 연결된 메모 조회
                        $memos_sql = "
                            SELECT * FROM mdl_agent_dashboard_memos 
                            WHERE taskid = ?
                        ";
                        $memos = $DB->get_records_sql($memos_sql, [$taskid]);
                        
                        // 연관된 메모가 있으면 각 메모를 새 task ID로 복사
                        if (!empty($memos)) {
                            foreach ($memos as $memo) {
                                $new_memo = new stdClass();
                                $new_memo->taskid = $new_task_id; // 새 task ID로 연결
                                $new_memo->userid = $userid;
                                $new_memo->content = $memo->content;
                                
                                // 선택적 필드도 복사 (있는 경우에만)
                                if (isset($memo->color)) $new_memo->color = $memo->color;
                                if (isset($memo->position)) $new_memo->position = $memo->position;
                                
                                // 타임스탬프 필드
                                $new_memo->timecreated = time();
                                $new_memo->timemodified = time();
                                
                                // 새 메모 저장
                                $DB->insert_record('agent_dashboard_memos', $new_memo);
                                
                                // 디버깅용 로그
                                error_log("Brain Dump 추가 시 메모가 복사됨: 원본 taskid={$taskid}, 새 taskid={$new_task_id}, 내용={$memo->content}");
                            }
                        }
                    }
                }
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'msg' => '추가 실패']);
            }
            exit();
        } else {
            echo json_encode(['success' => false, 'msg' => '유효하지 않은 파라미터']);
            exit();
        }
    }
}

// 3) action(add/edit) 및 edit_index 처리
$action = $_GET['action'] ?? '';
$edit_index = isset($_GET['edit']) ? intval($_GET['edit']) : -1;

// 4) DB에서 role이 일치하는 레코드 + user별 체크 상태 조회
$sql = "
    SELECT t.id AS dbid,
           t.task,
           t.description,
           t.url,
           COALESCE(u.checked, 0) AS checked
      FROM mdl_agent_toolsettings t
 LEFT JOIN mdl_agent_usertoolsettings u
        ON t.id = u.agent_toolsetting_id
       AND u.user_id = ?
     WHERE t.role = ?
  ORDER BY t.id ASC
";
$records = $DB->get_records_sql($sql, [$userid, $role]);

// 5) 현재 role이면서 type이 standard인 작업 항목을 조회
$standard_tasks_sql = "
    SELECT * 
    FROM mdl_agent_tasks
    WHERE role = ? 
    AND type LIKE 'standard'
    ORDER BY id ASC
";
$standard_tasks = $DB->get_records_sql($standard_tasks_sql, [$role]);

// 이미 추가된 표준 작업 목록 조회 (taskid 기준)
$added_tasks_sql = "
    SELECT taskid 
    FROM mdl_agent_toolsettings 
    WHERE role = ? 
    AND taskid > 0
";
$added_tasks = $DB->get_records_sql($added_tasks_sql, [$role]);
$added_task_ids = array_keys($added_tasks);

// $entries 배열 구성
$entries = [];
foreach ($records as $r) {
    $entries[] = [
        'dbid'       => $r->dbid,
        'task'       => $r->task,
        'description'=> $r->description,
        'url'        => $r->url,
        'checked'    => $r->checked
    ];
}

// 5) POST 요청 처리 (신규 추가 / 편집)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // (a) 신규 항목 추가
    if (isset($_POST['new_entry'])) {
        $task        = trim($_POST['task'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $url         = trim($_POST['url'] ?? '');

        if ($role && $task && $description && $url) {
            $newdata = new stdClass();
            $newdata->role         = $role;
            $newdata->task         = $task;
            $newdata->description  = $description;
            $newdata->url          = $url;
            $newdata->timecreated  = time();
            $newdata->timemodified = time();
            $DB->insert_record('agent_toolsettings', $newdata);
        }
        // 리다이렉트
        header("Location: " . $_SERVER['PHP_SELF'] . "?role=" . urlencode($role) . "&userid=" . $userid);
        exit();
    }

    // (b) 기존 항목 편집
    if (isset($_POST['edit_index'])) {
        $index = intval($_POST['edit_index']);
        $task        = trim($_POST['task'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $url         = trim($_POST['url'] ?? '');

        if (isset($entries[$index]) && $task && $description && $url) {
            $dbid = $entries[$index]['dbid'];
            $record = $DB->get_record('agent_toolsettings', ['id' => $dbid]);
            if ($record) {
                $record->task         = $task;
                $record->description  = $description;
                $record->url          = $url;
                $record->timemodified = time();
                $DB->update_record('agent_toolsettings', $record);
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?role=" . urlencode($role) . "&userid=" . $userid);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>업무 및 도구 설정</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 90%;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        col.check { width: 5%; }
        col.task { width: 20%; }
        col.desc { width: 60%; }
        col.url  { width: 20%; }
        col.edit { width: 5%; }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            box-sizing: border-box;
        }
        th {
            background-color: #f2f2f2;
        }
        input[type="text"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            border: none;
            background-color: transparent;
            cursor: pointer;
        }
        .edit-btn {
            font-size: 1rem;
            padding: 5px;
        }
        .add-button {
            padding: 8px 12px;
            border: none;
            background-color: #3b82f6;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-button:hover {
            background-color: #2563eb;
        }
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>역할: <?php echo htmlspecialchars($role); ?> - 환경설정 (UserID: <?php echo $userid; ?>)</h1>
    <table>
        <colgroup>
            <col class="check">
            <col class="task">
            <col class="desc">
            <col class="url">
            <col class="edit">
        </colgroup>
        <thead>
            <tr>
                <th>선택</th>
                <th>업무 (task)</th>
                <th>도구 설명 (description)</th>
                <th>도구 URL</th>
                <th>편집</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $index => $entry): ?>
                <?php if ($edit_index === $index): ?>
                    <!-- 편집 모드 -->
                    <tr>
                        <td></td>
                        <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?role=' . urlencode($role) . '&userid=' . $userid; ?>">
                            <td>
                                <input type="text" name="task"
                                       value="<?php echo htmlspecialchars($entry['task']); ?>" required>
                            </td>
                            <td>
                                <input type="text" name="description"
                                       value="<?php echo htmlspecialchars($entry['description']); ?>" required>
                            </td>
                            <td>
                                <input type="text" name="url"
                                       value="<?php echo htmlspecialchars($entry['url']); ?>" required>
                            </td>
                            <td style="text-align: center;">
                                <input type="hidden" name="edit_index" value="<?php echo $index; ?>">
                                <button type="submit" class="edit-btn">💾</button>
                            </td>
                        </form>
                    </tr>
                <?php else: ?>
                    <!-- 읽기 모드 -->
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox"
                                   data-toolsetting-id="<?php echo $entry['dbid']; ?>"
                                   <?php echo $entry['checked'] ? 'checked' : ''; ?>
                                   onchange="updateSelectState(this)">
                        </td>
                        <td><?php echo htmlspecialchars($entry['task']); ?></td>
                        <td><?php echo htmlspecialchars($entry['description']); ?></td>
                        <td style="text-align: center;">
                            <a href="<?php echo htmlspecialchars($entry['url']); ?>" target="_blank">도구링크</a>
                        </td>
                        <td style="text-align: center;">
                            <a href="<?php echo $_SERVER['PHP_SELF'] . '?role=' . urlencode($role) . '&userid=' . $userid . '&edit=' . $index; ?>">
                                <button type="button" class="edit-btn">✎</button>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($action === 'add'): ?>
                <!-- 신규 입력 행 -->
                <tr>
                    <td></td>
                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?role=' . urlencode($role) . '&userid=' . $userid; ?>">
                        <td><input type="text" name="task" placeholder="업무 입력" required></td>
                        <td><input type="text" name="description" placeholder="도구 설명 입력" required></td>
                        <td><input type="text" name="url" placeholder="도구 URL 입력" required></td>
                        <td style="text-align: center;">
                            <input type="hidden" name="new_entry" value="1">
                            <button type="submit" class="edit-btn">💾</button>
                        </td>
                    </form>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- 추가하기 버튼 -->
    <?php if ($action !== 'add'): ?>
        <a href="<?php echo $_SERVER['PHP_SELF'] . '?role=' . urlencode($role) . '&userid=' . $userid . '&action=add'; ?>">
            <button type="button" class="add-button">추가하기</button>
        </a>
    <?php endif; ?>
</div>

<!-- 표준 작업 목록 표시 -->
<div class="container" style="margin-top: 20px;">
    <h2>표준 작업 목록</h2>
    <?php if (!empty($standard_tasks)): ?>
    <table>
        <colgroup>
            <col style="width: 30%;">
            <col style="width: 60%;">
            <col style="width: 10%;">
        </colgroup>
        <thead>
            <tr>
                <th>작업명</th>
                <th>설명</th>
                <th>작업</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($standard_tasks as $task): ?>
            <tr>
                <td>
                <?php 
                    // 작업명 출력 (우선순위: title, label, task_name, name 순서로 확인)
                    if (isset($task->title)) echo htmlspecialchars($task->title);
                    else if (isset($task->label)) echo htmlspecialchars($task->label);
                    else if (isset($task->task_name)) echo htmlspecialchars($task->task_name);
                    else if (isset($task->name)) echo htmlspecialchars($task->name);
                    else echo "(작업명 없음)";
                ?>
                </td>
                <td>
                <?php 
                    // 설명 출력 (우선순위: description, content, details, text 순서로 확인)
                    if (isset($task->description)) echo htmlspecialchars($task->description);
                    else if (isset($task->content)) echo htmlspecialchars($task->content);
                    else if (isset($task->details)) echo htmlspecialchars($task->details);
                    else if (isset($task->text)) echo htmlspecialchars($task->text);
                    else echo "(설명 없음)";
                ?>
                </td>
                <td style="text-align: center;">
                    <?php if (in_array($task->id, $added_task_ids)): ?>
                        <button type="button" class="add-button" 
                               style="font-size: 0.8rem; padding: 4px 8px; background-color: lightgreen; cursor: default;"
                               disabled>
                            추가됨
                        </button>
                    <?php else: ?>
                        <button type="button" class="add-button" style="font-size: 0.8rem; padding: 4px 8px;"
                                onclick="addStandardTask(<?php echo $task->id; ?>, 
                                '<?php 
                                    // 작업명 JavaScript 문자열로 준비
                                    $title = '';
                                    if (isset($task->title)) $title = $task->title;
                                    else if (isset($task->label)) $title = $task->label;
                                    else if (isset($task->task_name)) $title = $task->task_name;
                                    else if (isset($task->name)) $title = $task->name;
                                    echo addslashes($title);
                                ?>', 
                                '<?php 
                                    // 설명 JavaScript 문자열로 준비
                                    $desc = '';
                                    if (isset($task->description)) $desc = $task->description;
                                    else if (isset($task->content)) $desc = $task->content;
                                    else if (isset($task->details)) $desc = $task->details;
                                    else if (isset($task->text)) $desc = $task->text;
                                    echo addslashes($desc);
                                ?>')">
                            추가
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>현재 역할에 대한 표준 작업이 없습니다.</p>
    <?php endif; ?>
</div>

<script>
// 체크박스 변경 -> AJAX로 updateSelect
function updateSelectState(checkbox) {
    const toolsetting_id = checkbox.getAttribute('data-toolsetting-id');
    const checked = checkbox.checked ? 1 : 0;

    const formData = new FormData();
    formData.append('agent_toolsetting_id', toolsetting_id);
    formData.append('checked', checked);

    // userid를 GET 파라미터로 함께 보냄
    fetch('?action=updateSelect&userid=<?php echo $userid; ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert("업데이트 실패: " + (data.msg || ""));
        }
    })
    .catch(err => {
        console.error(err);
        alert("에러 발생");
    });
}

// 표준 작업 추가하기
function addStandardTask(taskId, taskTitle, taskDescription) {
    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('task_title', taskTitle);
    formData.append('task_description', taskDescription);

    fetch('?action=addStandardTask&userid=<?php echo $userid; ?>&role=<?php echo urlencode($role); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("작업이 추가되었습니다.");
            // 페이지 새로고침
            window.location.reload();
        } else {
            alert("추가 실패: " + (data.msg || ""));
        }
    })
    .catch(err => {
        console.error(err);
        alert("에러 발생");
    });
}
</script>
</body>
</html>
