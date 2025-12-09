<?php
/**
 * 시험 자료 및 팁 조회 API
 * exam_id 기반으로 file_url과 tip_text 정보를 반환
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

    // GET 또는 POST 데이터 받기
    $school = $_GET['school'] ?? $_POST['school'] ?? '';
    $grade = intval($_GET['grade'] ?? $_POST['grade'] ?? 0);
    $examType = $_GET['examType'] ?? $_POST['examType'] ?? '';

    if (empty($school) || $grade < 1 || $grade > 3 || empty($examType)) {
        throw new Exception("필수 파라미터가 누락되었습니다");
    }

    // examType 매핑
    $examTypeMap = [
        '1mid' => '1학기 중간고사',
        '1final' => '1학기 기말고사',
        '2mid' => '2학기 중간고사',
        '2final' => '2학기 기말고사'
    ];

    $examTypeName = $examTypeMap[$examType] ?? $examType;

    // exam_id 조회
    $stmt = $pdo->prepare("SELECT exam_id FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? AND exam_type = ?");
    $stmt->execute([$school, $grade, $examTypeName]);
    $exam_info = $stmt->fetch();

    if (!$exam_info) {
        echo json_encode([
            'success' => true,
            'exam_id' => null,
            'files' => [],
            'tips' => [],
            'message' => '시험 정보를 찾을 수 없습니다.'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $exam_id = $exam_info['exam_id'];

    // file_url과 tip_text 조회
    $stmt = $pdo->prepare("
        SELECT 
            resource_id,
            file_url,
            tip_text,
            user_id,
            created_at
        FROM mdl_alt42t_exam_resources 
        WHERE exam_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$exam_id]);
    $resources = $stmt->fetchAll();

    $files = [];
    $tips = [];

    foreach ($resources as $resource) {
        if (!empty($resource['file_url'])) {
            $files[] = [
                'id' => $resource['resource_id'],
                'url' => $resource['file_url'],
                'created_at' => $resource['created_at'],
                'user_id' => $resource['user_id']
            ];
        }
        
        if (!empty($resource['tip_text'])) {
            $tips[] = [
                'id' => $resource['resource_id'],
                'text' => $resource['tip_text'],
                'created_at' => $resource['created_at'],
                'user_id' => $resource['user_id']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'exam_id' => $exam_id,
        'files' => $files,
        'tips' => $tips,
        'total_files' => count($files),
        'total_tips' => count($tips)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in get_exam_resources.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>