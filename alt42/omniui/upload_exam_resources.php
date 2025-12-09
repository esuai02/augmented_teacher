<?php
/**
 * 시험 자료 및 정보 업로드 처리 API
 * 파일 업로드와 텍스트 정보를 mdl_alt42t_exam_resources 테이블에 저장
 */

header("Content-Type: application/json; charset=UTF-8");
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

    $current_time = time();
    $current_datetime = date('Y-m-d H:i:s');

    // 업로드 유형 확인
    $upload_type = $_POST['upload_type'] ?? $_GET['upload_type'] ?? '';
    
    if (!$upload_type) {
        // JSON 데이터 확인
        $json_input = json_decode(file_get_contents("php://input"), true);
        $upload_type = $json_input['upload_type'] ?? '';
    }

    if (empty($upload_type)) {
        throw new Exception("업로드 유형이 지정되지 않았습니다");
    }

    // examType 매핑
    $examTypeMap = [
        '1mid' => '1학기 중간고사',
        '1final' => '1학기 기말고사',
        '2mid' => '2학기 중간고사',
        '2final' => '2학기 기말고사'
    ];

    if ($upload_type === 'file') {
        // ===== 파일 업로드 처리 =====
        
        $school = $_POST['school'] ?? '';
        $grade = intval($_POST['grade'] ?? 0);
        $examType = $_POST['examType'] ?? '';
        
        if (empty($school) || $grade < 1 || $grade > 3 || empty($examType)) {
            throw new Exception("필수 정보가 누락되었습니다");
        }

        if (empty($_FILES['files']['name'][0])) {
            throw new Exception("업로드할 파일이 없습니다");
        }

        $examTypeName = $examTypeMap[$examType] ?? $examType;

        // 기존 시험 정보 조회 (school_name, grade, exam_type 조합으로)
        $stmt = $pdo->prepare("SELECT exam_id FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? AND exam_type = ?");
        $stmt->execute([$school, $grade, $examTypeName]);
        $exam_info = $stmt->fetch();

        if (!$exam_info) {
            throw new Exception("기본 정보가 먼저 입력되어야 합니다");
        }

        $exam_id = $exam_info['exam_id'];

        // 사용자 정보 조회
        $stmt = $pdo->prepare("SELECT user_id FROM mdl_alt42t_users WHERE school_name = ? AND grade = ?");
        $stmt->execute([$school, $grade]);
        $user_info = $stmt->fetch();

        if (!$user_info) {
            throw new Exception("기본 정보가 먼저 입력되어야 합니다");
        }

        $user_id = $user_info['user_id'];

        // 업로드 디렉토리 생성
        $upload_dir = "uploads/{$school}_{$grade}_{$examType}/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $uploaded_files = [];
        $file_count = count($_FILES['files']['name']);

        // 트랜잭션 시작
        $pdo->beginTransaction();

        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_error = $_FILES['files']['error'][$i];

            if ($file_error !== UPLOAD_ERR_OK) {
                throw new Exception("파일 업로드 오류: " . $file_name);
            }

            // 파일명 안전하게 처리
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $safe_filename = date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $safe_filename;

            // 파일 업로드
            if (move_uploaded_file($file_tmp, $file_path)) {
                // DB에 파일 정보 저장 (AUTO_INCREMENT 사용)
                $stmt = $pdo->prepare("INSERT INTO mdl_alt42t_exam_resources (exam_id, user_id, file_url, tip_text, created_at, userid, timecreated, timemodified) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
                $stmt->execute([$exam_id, $user_id, $file_path, "파일: " . $file_name, $current_datetime, $current_time, $current_time]);
                
                $resource_id = $pdo->lastInsertId();
                
                $uploaded_files[] = [
                    'resource_id' => $resource_id,
                    'original_name' => $file_name,
                    'file_path' => $file_path,
                    'size' => $file_size
                ];
            } else {
                throw new Exception("파일 저장 실패: " . $file_name);
            }
        }

        // 자료 집계 업데이트
        updateAggregatedResources($pdo, $exam_id, $current_time);
        
        // 알림 생성
        createNotification($pdo, $exam_id, $user_id, 'file', $resource_id);

        // 커밋
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => count($uploaded_files) . '개 파일이 성공적으로 업로드되었습니다',
            'uploaded_files' => $uploaded_files
        ]);

    } elseif ($upload_type === 'text') {
        // ===== 텍스트 정보 업로드 처리 =====
        
        $json_input = json_decode(file_get_contents("php://input"), true);
        
        $school = $json_input['school'] ?? '';
        $grade = intval($json_input['grade'] ?? 0);
        $examType = $json_input['examType'] ?? '';
        $tip_type = $json_input['tip_type'] ?? '';
        $tip_content = $json_input['tip_content'] ?? '';
        
        if (empty($school) || $grade < 1 || $grade > 3 || empty($examType) || empty($tip_content)) {
            throw new Exception("필수 정보가 누락되었습니다");
        }

        $examTypeName = $examTypeMap[$examType] ?? $examType;

        // 기존 시험 정보 조회 (school_name, grade, exam_type 조합으로)
        $stmt = $pdo->prepare("SELECT exam_id FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? AND exam_type = ?");
        $stmt->execute([$school, $grade, $examTypeName]);
        $exam_info = $stmt->fetch();

        if (!$exam_info) {
            throw new Exception("기본 정보가 먼저 입력되어야 합니다");
        }

        $exam_id = $exam_info['exam_id'];

        // 사용자 정보 조회
        $stmt = $pdo->prepare("SELECT user_id FROM mdl_alt42t_users WHERE school_name = ? AND grade = ?");
        $stmt->execute([$school, $grade]);
        $user_info = $stmt->fetch();

        if (!$user_info) {
            throw new Exception("기본 정보가 먼저 입력되어야 합니다");
        }

        $user_id = $user_info['user_id'];

        // 정보 유형별 접두사
        $type_prefixes = [
            'tip' => '💡 시험 팁',
            'warning' => '⚠️ 주의사항',
            'trend' => '📊 출제 경향',
            'scope' => '📋 시험 범위',
            'etc' => '📌 기타 정보'
        ];

        $prefix = $type_prefixes[$tip_type] ?? '📌 정보';
        $formatted_content = $prefix . ': ' . $tip_content;

        // 트랜잭션 시작
        $pdo->beginTransaction();

        // DB에 텍스트 정보 저장 (AUTO_INCREMENT 사용)
        $stmt = $pdo->prepare("INSERT INTO mdl_alt42t_exam_resources (exam_id, user_id, file_url, tip_text, created_at, userid, timecreated, timemodified) VALUES (?, ?, NULL, ?, ?, 0, ?, ?)");
        $stmt->execute([$exam_id, $user_id, $formatted_content, $current_datetime, $current_time, $current_time]);
        
        $resource_id = $pdo->lastInsertId();
        
        // 자료 집계 업데이트
        updateAggregatedResources($pdo, $exam_id, $current_time);
        
        // 알림 생성
        createNotification($pdo, $exam_id, $user_id, 'tip', $resource_id);
        
        // 커밋
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '정보가 성공적으로 업로드되었습니다',
            'resource_id' => $resource_id,
            'tip_type' => $tip_type,
            'content_preview' => mb_substr($tip_content, 0, 50) . (mb_strlen($tip_content) > 50 ? '...' : '')
        ]);

    } elseif ($upload_type === 'link') {
        // ===== 링크 업로드 처리 =====
        
        $json_input = json_decode(file_get_contents("php://input"), true);
        
        $school = $json_input['school'] ?? '';
        $grade = intval($json_input['grade'] ?? 0);
        $examType = $json_input['examType'] ?? '';
        $resource_link = $json_input['resource_link'] ?? '';
        $resource_description = $json_input['resource_description'] ?? '';
        
        if (empty($school) || $grade < 1 || $grade > 3 || empty($examType) || empty($resource_link)) {
            throw new Exception("필수 정보가 누락되었습니다");
        }

        // URL 유효성 검사
        if (!filter_var($resource_link, FILTER_VALIDATE_URL)) {
            throw new Exception("올바른 URL 형식이 아닙니다");
        }

        $examTypeName = $examTypeMap[$examType] ?? $examType;

        // 기존 시험 정보 조회 (school_name, grade, exam_type 조합으로)
        $stmt = $pdo->prepare("SELECT exam_id FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? AND exam_type = ?");
        $stmt->execute([$school, $grade, $examTypeName]);
        $exam_info = $stmt->fetch();

        if (!$exam_info) {
            throw new Exception("기본 정보가 먼저 입력되어야 합니다");
        }

        $exam_id = $exam_info['exam_id'];

        // 사용자 정보 조회
        $stmt = $pdo->prepare("SELECT user_id FROM mdl_alt42t_users WHERE school_name = ? AND grade = ?");
        $stmt->execute([$school, $grade]);
        $user_info = $stmt->fetch();

        if (!$user_info) {
            throw new Exception("기본 정보가 먼저 입력되어야 합니다");
        }

        $user_id = $user_info['user_id'];

        // 링크 설명 포맷
        $link_description = !empty($resource_description) ? $resource_description : '링크 자료';

        // 트랜잭션 시작
        $pdo->beginTransaction();

        // DB에 링크 정보 저장 (AUTO_INCREMENT 사용, file_url에 링크 저장)
        $stmt = $pdo->prepare("INSERT INTO mdl_alt42t_exam_resources (exam_id, user_id, file_url, tip_text, created_at, userid, timecreated, timemodified) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
        $stmt->execute([$exam_id, $user_id, $resource_link, "링크: " . $link_description, $current_datetime, $current_time, $current_time]);
        
        $resource_id = $pdo->lastInsertId();
        
        // 자료 집계 업데이트
        updateAggregatedResources($pdo, $exam_id, $current_time);
        
        // 알림 생성
        createNotification($pdo, $exam_id, $user_id, 'link', $resource_id);
        
        // 커밋
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '링크가 성공적으로 업로드되었습니다',
            'resource_id' => $resource_id,
            'resource_link' => $resource_link,
            'resource_description' => $link_description
        ]);

    } else {
        throw new Exception("지원하지 않는 업로드 유형입니다: " . $upload_type);
    }

} catch (Exception $e) {
    // 롤백
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 에러 응답
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * 알림 생성 함수
 */
function createNotification($pdo, $exam_id, $user_id, $resource_type, $resource_id = null) {
    try {
        // 같은 exam_id를 가진 다른 사용자들 조회
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id 
            FROM mdl_alt42t_exam_dates ed
            JOIN mdl_alt42t_users u ON ed.user_id = u.user_id
            WHERE ed.exam_id = ? AND u.user_id != ?
        ");
        $stmt->execute([$exam_id, $user_id]);
        $users = $stmt->fetchAll();
        
        // 메시지 생성
        $message = "새로운 시험 정보가 업데이트 되었습니다";
        if ($resource_type === 'file') {
            $message = "새로운 시험 자료가 업로드되었습니다";
        } elseif ($resource_type === 'tip') {
            $message = "새로운 시험 팁이 등록되었습니다";
        }
        
        // 각 사용자에게 알림 생성
        foreach ($users as $target_user) {
            // 중복 알림 체크 (최근 5분 이내 같은 exam_id에 대한 알림이 있는지)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM mdl_alt42t_notifications 
                WHERE exam_id = ? 
                AND user_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                AND notification_type = 'resource_update'
            ");
            $stmt->execute([$exam_id, $target_user['user_id']]);
            $recent = $stmt->fetch();
            
            if ($recent['count'] == 0) {
                // 알림 생성
                $stmt = $pdo->prepare("
                    INSERT INTO mdl_alt42t_notifications 
                    (exam_id, user_id, notification_type, message, resource_type, resource_id, is_read, created_at) 
                    VALUES (?, ?, 'resource_update', ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$exam_id, $target_user['user_id'], $message, $resource_type, $resource_id]);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("알림 생성 실패: " . $e->getMessage());
        return false;
    }
}

/**
 * 자료 집계 함수 - mdl_alt42t_aggregated_resources 테이블 업데이트
 */
function updateAggregatedResources($pdo, $exam_id, $current_time) {
    // 같은 exam_id의 모든 자료 조회
    $stmt = $pdo->prepare("SELECT file_url, tip_text FROM mdl_alt42t_exam_resources WHERE exam_id = ? ORDER BY created_at ASC");
    $stmt->execute([$exam_id]);
    $resources = $stmt->fetchAll();
    
    $compiled_file_urls = [];
    $compiled_tips = [];
    
    foreach ($resources as $resource) {
        if (!empty($resource['file_url'])) {
            $compiled_file_urls[] = $resource['file_url'];
        }
        if (!empty($resource['tip_text'])) {
            $compiled_tips[] = $resource['tip_text'];
        }
    }
    
    // JSON 변환
    $compiled_file_urls_json = json_encode($compiled_file_urls, JSON_UNESCAPED_UNICODE);
    $compiled_tips_json = json_encode($compiled_tips, JSON_UNESCAPED_UNICODE);
    $last_updated = date('Y-m-d H:i:s');
    
    // aggregated_id 생성 (exam_id와 동일)
    $aggregated_id = $exam_id;
    
    // 기존 집계 데이터 확인
    $stmt = $pdo->prepare("SELECT aggregated_id FROM mdl_alt42t_aggregated_resources WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $existing_aggregated = $stmt->fetch();
    
    if ($existing_aggregated) {
        // 기존 집계 데이터 업데이트
        $stmt = $pdo->prepare("UPDATE mdl_alt42t_aggregated_resources SET compiled_file_urls = ?, compiled_tips = ?, last_updated = ?, timemodified = ? WHERE exam_id = ?");
        $stmt->execute([$compiled_file_urls_json, $compiled_tips_json, $last_updated, $current_time, $exam_id]);
    } else {
        // 새 집계 데이터 생성
        $stmt = $pdo->prepare("INSERT INTO mdl_alt42t_aggregated_resources (aggregated_id, exam_id, compiled_file_urls, compiled_tips, last_updated, userid, timecreated, timemodified) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
        $stmt->execute([$aggregated_id, $exam_id, $compiled_file_urls_json, $compiled_tips_json, $last_updated, $current_time, $current_time]);
    }
}
?>