<?php
/**
 * 학습 과제 관리 API (기존 DB 사용)
 * mdl_abessi_today 테이블 활용
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once 'config.php';

try {
    // 데이터베이스 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 요청 데이터 파싱
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];

    // GET 파라미터도 확인
    $action = $data['action'] ?? $_GET['action'] ?? 'list';
    $user_id = $data['user_id'] ?? $_GET['user_id'] ?? $_SESSION['userid'] ?? null;

    if (!$user_id) {
        throw new Exception('사용자 ID가 필요합니다.');
    }

    $response = [];

    // 액션별 처리
    switch ($action) {
        case 'list':
            $response = listTasks($pdo, $user_id);
            break;

        case 'add':
            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';
            $due_date = $data['due_date'] ?? null;
            $priority = $data['priority'] ?? 'medium';

            if (empty($title)) {
                throw new Exception('과제 제목이 필요합니다.');
            }

            $response = addTask($pdo, $user_id, $title, $description, $due_date, $priority);
            break;

        case 'toggle':
            $task_id = $data['task_id'] ?? 0;
            $completed = $data['completed'] ?? false;

            if (!$task_id) {
                throw new Exception('과제 ID가 필요합니다.');
            }

            $response = toggleTask($pdo, $task_id, $user_id, $completed);
            break;

        case 'delete':
            $task_id = $data['task_id'] ?? 0;

            if (!$task_id) {
                throw new Exception('과제 ID가 필요합니다.');
            }

            $response = deleteTask($pdo, $task_id, $user_id);
            break;

        case 'update':
            $task_id = $data['task_id'] ?? 0;
            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';
            $due_date = $data['due_date'] ?? null;
            $priority = $data['priority'] ?? 'medium';

            if (!$task_id) {
                throw new Exception('과제 ID가 필요합니다.');
            }

            $response = updateTask($pdo, $task_id, $user_id, $title, $description, $due_date, $priority);
            break;

        default:
            throw new Exception('알 수 없는 액션입니다: ' . $action);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Learning tasks API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 과제 목록 조회 (mdl_abessi_today 사용)
 */
function listTasks($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT
            id,
            text,
            type,
            deadline,
            goallevel,
            complete,
            status,
            timecreated,
            timemodified
        FROM mdl_abessi_today
        WHERE userid = ?
        AND type = '학습과제'
        ORDER BY
            CASE WHEN complete = '1' THEN 1 ELSE 0 END,
            CASE
                WHEN goallevel = 3 THEN 1
                WHEN goallevel = 2 THEN 2
                WHEN goallevel = 1 THEN 3
                ELSE 4
            END,
            deadline ASC,
            timecreated DESC
    ");
    $stmt->execute([$user_id]);

    $tasks = [];
    while ($row = $stmt->fetch()) {
        // JSON 파싱 시도
        $task_data = json_decode($row['text'], true);

        if ($task_data && isset($task_data['title'])) {
            // JSON 형식 데이터
            $title = $task_data['title'];
            $description = $task_data['description'] ?? '';
        } else {
            // 일반 텍스트
            $title = $row['text'] ?: '제목 없음';
            $description = '';
        }

        // 우선순위 매핑 (goallevel: 3=high, 2=medium, 1=low)
        $priority_map = [3 => 'high', 2 => 'medium', 1 => 'low'];
        $priority = $priority_map[$row['goallevel']] ?? 'medium';

        $tasks[] = [
            'id' => $row['id'],
            'title' => $title,
            'description' => $description,
            'due_date' => $row['deadline'],
            'priority' => $priority,
            'completed' => $row['complete'] == '1',
            'created' => date('Y-m-d H:i:s', $row['timecreated'] ?? time()),
            'modified' => date('Y-m-d H:i:s', $row['timemodified'] ?? time())
        ];
    }

    return [
        'success' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ];
}

/**
 * 과제 추가 (mdl_abessi_today에 저장)
 */
function addTask($pdo, $user_id, $title, $description, $due_date, $priority) {
    $now = time();

    // 우선순위를 goallevel로 변환 (high=3, medium=2, low=1)
    $priority_map = ['high' => 3, 'medium' => 2, 'low' => 1];
    $goallevel = $priority_map[$priority] ?? 2;

    // JSON 형식으로 데이터 저장
    $task_data = json_encode([
        'title' => $title,
        'description' => $description
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_today
        (userid, type, text, deadline, goallevel, complete, timecreated, timemodified)
        VALUES (?, '학습과제', ?, ?, ?, '0', ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $task_data,
        $due_date,
        $goallevel,
        $now,
        $now
    ]);

    $task_id = $pdo->lastInsertId();

    // 활동 로그 기록
    logActivity($pdo, $user_id, 'task_created');

    return [
        'success' => true,
        'task_id' => $task_id,
        'message' => '과제가 추가되었습니다.'
    ];
}

/**
 * 과제 완료 상태 토글
 */
function toggleTask($pdo, $task_id, $user_id, $completed) {
    $now = time();

    $stmt = $pdo->prepare("
        UPDATE mdl_abessi_today
        SET complete = ?, timemodified = ?
        WHERE id = ? AND userid = ? AND type = '학습과제'
    ");

    $result = $stmt->execute([
        $completed ? '1' : '0',
        $now,
        $task_id,
        $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('과제를 찾을 수 없습니다.');
    }

    // 활동 로그 기록
    logActivity($pdo, $user_id, $completed ? 'task_completed' : 'task_uncompleted');

    return [
        'success' => true,
        'message' => $completed ? '과제를 완료했습니다.' : '과제를 미완료로 변경했습니다.'
    ];
}

/**
 * 과제 삭제
 */
function deleteTask($pdo, $task_id, $user_id) {
    $stmt = $pdo->prepare("
        DELETE FROM mdl_abessi_today
        WHERE id = ? AND userid = ? AND type = '학습과제'
    ");

    $stmt->execute([$task_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('과제를 찾을 수 없습니다.');
    }

    // 활동 로그 기록
    logActivity($pdo, $user_id, 'task_deleted');

    return [
        'success' => true,
        'message' => '과제가 삭제되었습니다.'
    ];
}

/**
 * 과제 수정
 */
function updateTask($pdo, $task_id, $user_id, $title, $description, $due_date, $priority) {
    $now = time();

    // 우선순위를 goallevel로 변환
    $priority_map = ['high' => 3, 'medium' => 2, 'low' => 1];
    $goallevel = $priority_map[$priority] ?? 2;

    // JSON 형식으로 데이터 저장
    $task_data = json_encode([
        'title' => $title,
        'description' => $description
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        UPDATE mdl_abessi_today
        SET text = ?, deadline = ?, goallevel = ?, timemodified = ?
        WHERE id = ? AND userid = ? AND type = '학습과제'
    ");

    $stmt->execute([
        $task_data,
        $due_date,
        $goallevel,
        $now,
        $task_id,
        $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('과제를 찾을 수 없거나 변경사항이 없습니다.');
    }

    // 활동 로그 기록
    logActivity($pdo, $user_id, 'task_updated');

    return [
        'success' => true,
        'message' => '과제가 수정되었습니다.'
    ];
}

/**
 * 활동 로그 기록
 */
function logActivity($pdo, $user_id, $page) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $page, time()]);
    } catch (Exception $e) {
        // 로그 기록 실패는 무시
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>
