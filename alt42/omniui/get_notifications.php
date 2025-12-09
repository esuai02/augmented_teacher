<?php
/**
 * 사용자 알림 조회 및 관리 API
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB 접속 정보
$CFG = new stdClass();
$CFG->dbhost = '58.180.27.46';
$CFG->dbname = 'mathking';
$CFG->dbuser = 'moodle';
$CFG->dbpass = '@MCtrigd7128';
$CFG->prefix = 'mdl_';

try {
    // PDO 연결
    $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $action = $_GET['action'] ?? $_POST['action'] ?? 'fetch';
    $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        throw new Exception("유효하지 않은 사용자 ID");
    }

    switch ($action) {
        case 'fetch':
            // 알림 조회
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            // 읽지 않은 알림 수 조회
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as unread_count 
                FROM mdl_alt42t_notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $unread = $stmt->fetch();
            
            // 알림 목록 조회
            $stmt = $pdo->prepare("
                SELECT 
                    n.notification_id,
                    n.exam_id,
                    n.message,
                    n.resource_type,
                    n.resource_id,
                    n.is_read,
                    n.created_at,
                    n.read_at,
                    e.school_name,
                    e.grade,
                    e.exam_type
                FROM mdl_alt42t_notifications n
                JOIN mdl_alt42t_exams e ON n.exam_id = e.exam_id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            $notifications = $stmt->fetchAll();
            
            // 시간 포맷팅
            foreach ($notifications as &$notification) {
                $created = new DateTime($notification['created_at']);
                $now = new DateTime();
                $diff = $now->diff($created);
                
                if ($diff->days > 0) {
                    $notification['time_ago'] = $diff->days . '일 전';
                } elseif ($diff->h > 0) {
                    $notification['time_ago'] = $diff->h . '시간 전';
                } elseif ($diff->i > 0) {
                    $notification['time_ago'] = $diff->i . '분 전';
                } else {
                    $notification['time_ago'] = '방금 전';
                }
                
                // 시험 정보 포맷
                $notification['exam_info'] = $notification['school_name'] . ' ' . 
                                            $notification['grade'] . '학년 ' . 
                                            $notification['exam_type'];
            }
            
            echo json_encode([
                'success' => true,
                'unread_count' => $unread['unread_count'],
                'notifications' => $notifications,
                'total' => count($notifications)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'mark_read':
            // 알림을 읽음으로 표시
            $notification_id = intval($_POST['notification_id'] ?? 0);
            
            if ($notification_id > 0) {
                // 특정 알림만 읽음 처리
                $stmt = $pdo->prepare("
                    UPDATE mdl_alt42t_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE notification_id = ? AND user_id = ?
                ");
                $stmt->execute([$notification_id, $user_id]);
            } else {
                // 모든 알림 읽음 처리
                $stmt = $pdo->prepare("
                    UPDATE mdl_alt42t_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE user_id = ? AND is_read = 0
                ");
                $stmt->execute([$user_id]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => '알림이 읽음 처리되었습니다'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'delete':
            // 알림 삭제
            $notification_id = intval($_POST['notification_id'] ?? 0);
            
            if ($notification_id <= 0) {
                throw new Exception("유효하지 않은 알림 ID");
            }
            
            $stmt = $pdo->prepare("
                DELETE FROM mdl_alt42t_notifications 
                WHERE notification_id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => '알림이 삭제되었습니다'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'clear_all':
            // 모든 알림 삭제
            $stmt = $pdo->prepare("
                DELETE FROM mdl_alt42t_notifications 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => '모든 알림이 삭제되었습니다'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("알 수 없는 액션: " . $action);
    }

} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>