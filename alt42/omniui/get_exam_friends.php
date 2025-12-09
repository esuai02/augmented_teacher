<?php
/**
 * 같은 exam_id의 친구들 정보 조회 API
 * exam_id 기반으로 시험 일정을 입력한 학생들의 정보를 반환
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

    // 디버깅: 받은 파라미터 확인
    error_log("get_exam_friends.php - Parameters: school=$school, grade=$grade, examType=$examType, examTypeName=$examTypeName");
    
    // 기존 시험 정보 조회 (school_name, grade, exam_type 조합으로)
    $stmt = $pdo->prepare("SELECT exam_id FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? AND exam_type = ?");
    $stmt->execute([$school, $grade, $examTypeName]);
    $exam_info = $stmt->fetch();

    // 디버깅: 쿼리 결과 확인
    error_log("get_exam_friends.php - Exam query result: " . json_encode($exam_info));

    if (!$exam_info) {
        // 시험 정보가 없으면 빈 결과 반환
        error_log("get_exam_friends.php - No exam found for: school=$school, grade=$grade, examTypeName=$examTypeName");
        echo json_encode([
            'success' => true,
            'exam_id' => null,
            'friends' => [],
            'resource_summary' => [
                'total_resources' => 0,
                'file_count' => 0,
                'tip_count' => 0,
                'last_updated' => null
            ],
            'aggregated_data' => [
                'files' => [],
                'tips' => []
            ],
            'debug' => [
                'school' => $school,
                'grade' => $grade,
                'examType' => $examType,
                'examTypeName' => $examTypeName
            ]
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $exam_id = $exam_info['exam_id'];
    
    // 디버깅: exam_id 확인
    error_log("get_exam_friends.php - Found exam_id: $exam_id");

    // 같은 exam_id의 시험 일정 정보 조회 (사용자 정보와 조인)
    // exam_resources에서 시험 범위 정보도 함께 조회
    // 주의: mdl_alt42t_users 테이블의 PK는 'id'이고, 'userid'는 Moodle 사용자 ID
    $stmt = $pdo->prepare("
        SELECT 
            ed.exam_date_id,
            ed.start_date,
            ed.end_date, 
            ed.math_date,
            ed.status,
            u.name,
            u.id as user_id,
            ed.created_at,
            er.tip_text as exam_scope
        FROM mdl_alt42t_exam_dates ed
        JOIN mdl_alt42t_users u ON ed.user_id = u.id
        LEFT JOIN mdl_alt42t_exam_resources er ON ed.exam_id = er.exam_id AND ed.user_id = er.user_id
        WHERE ed.exam_id = ?
        ORDER BY ed.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$exam_id]);
    $exam_dates = $stmt->fetchAll();
    
    // 디버깅: 조회된 친구 수 확인
    error_log("get_exam_friends.php - Found " . count($exam_dates) . " friends for exam_id: $exam_id");

    $friends = [];
    $friend_names = ['민준', '서준', '도윤', '예준', '시우', '주원', '하준', '지호', '지우', '준서']; // 익명화용
    $name_index = 0;

    foreach ($exam_dates as $exam_date) {
        // 익명 이름 사용 (사용자 요청에 따라 이름 숨김)
        $display_name = $friend_names[$name_index % count($friend_names)];
        
        // 시험 범위 처리 - "시험 범위: " 접두사 제거
        $examScope = '범위 미입력';
        if (!empty($exam_date['exam_scope'])) {
            // "시험 범위: " 접두사가 있으면 제거
            if (strpos($exam_date['exam_scope'], '시험 범위: ') === 0) {
                $examScope = substr($exam_date['exam_scope'], strlen('시험 범위: '));
            } else {
                $examScope = $exam_date['exam_scope'];
            }
        }
        
        $friends[] = [
            'name' => $display_name,
            'startDate' => $exam_date['start_date'],
            'endDate' => $exam_date['end_date'],
            'examDate' => $exam_date['math_date'],
            'status' => $exam_date['status'] === '확정' ? 'confirmed' : 'expected',
            'scope' => $examScope,
            'user_id' => $exam_date['user_id'],
            'exam_date_id' => $exam_date['exam_date_id']
        ];
        
        $name_index++;
    }

    // 자료 개수 조회
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_resources FROM mdl_alt42t_exam_resources WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $resource_count = $stmt->fetch()['total_resources'];

    // 집계 자료 조회
    $stmt = $pdo->prepare("SELECT compiled_file_urls, compiled_tips, last_updated FROM mdl_alt42t_aggregated_resources WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $aggregated = $stmt->fetch();

    $compiled_files = [];
    $compiled_tips = [];

    if ($aggregated) {
        if (!empty($aggregated['compiled_file_urls'])) {
            $compiled_files = json_decode($aggregated['compiled_file_urls'], true) ?: [];
        }
        if (!empty($aggregated['compiled_tips'])) {
            $compiled_tips = json_decode($aggregated['compiled_tips'], true) ?: [];
        }
    }

    // 디버깅: 최종 친구 목록 확인
    error_log("get_exam_friends.php - Final friends array: " . json_encode($friends, JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'success' => true,
        'exam_id' => $exam_id,
        'friends' => $friends,
        'resource_summary' => [
            'total_resources' => $resource_count,
            'file_count' => count($compiled_files),
            'tip_count' => count($compiled_tips),
            'last_updated' => $aggregated['last_updated'] ?? null
        ],
        'aggregated_data' => [
            'files' => $compiled_files,
            'tips' => $compiled_tips
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'friends' => [],
        'resource_summary' => [
            'total_resources' => 0,
            'file_count' => 0,
            'tip_count' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>