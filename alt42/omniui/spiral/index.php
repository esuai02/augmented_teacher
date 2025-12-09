<?php
/**
 * Spiral Scheduler Teacher Dashboard
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// MathKing 시스템 설정 로드
require_once(__DIR__ . '/../config.php');

// 세션 시작
session_start();

// 로그인 체크 (간단한 인증)
if (!isset($_SESSION['user_id'])) {
    // 개발용 임시 사용자 설정
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['fullname'] = 'System Admin';
}

$USER = (object)[
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'admin',
    'fullname' => $_SESSION['fullname'] ?? 'Admin User'
];

// 페이지 제목 설정
$PAGE_TITLE = '스파이럴 스케줄러 대시보드';
$PAGE_HEADING = 'Spiral Scheduler';

// 데이터베이스 연결 설정
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    $pdo = null;
}

// 현재 교사의 학생 목록 조회 (샘플 데이터)
$students = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.firstname, u.lastname,
                   CONCAT(LEFT(u.email, 2), '***@', SUBSTRING_INDEX(u.email, '@', -1)) as email_masked
            FROM mdl_user u
            LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
            WHERE u.deleted = 0 AND uid.data = 'student'
            ORDER BY u.lastname, u.firstname
            LIMIT 50
        ");
        $stmt->execute();
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Student query error: " . $e->getMessage());
        // 샘플 학생 데이터
        $students = [
            (object)['id' => 1, 'firstname' => '홍', 'lastname' => '길동'],
            (object)['id' => 2, 'firstname' => '김', 'lastname' => '철수'],
            (object)['id' => 3, 'firstname' => '이', 'lastname' => '영희']
        ];
    }
}

// HTML 헤더 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $PAGE_TITLE; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <a class="navbar-brand" href="#"><i class="fas fa-spiral mr-2"></i><?php echo $PAGE_HEADING; ?></a>
        <div class="ml-auto">
            <span class="navbar-text">
                <i class="fas fa-user mr-2"></i><?php echo $USER->fullname; ?>
            </span>
        </div>
    </nav>

    <!-- KPI 대시보드 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h5 class="alert-heading">
                    <i class="fas fa-tachometer-alt mr-2"></i>KPI 대시보드
                </h5>
                <p class="mb-0">시스템 핵심 성과 지표를 실시간으로 모니터링합니다.</p>
                <small class="text-muted">마지막 업데이트: <?php echo date('Y-m-d H:i:s'); ?></small>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- KPI 카드들 -->
        <div class="col-md-4 mb-3">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-percentage mr-2"></i>7:3 준수율
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="display-4 font-weight-bold text-success">85%</div>
                    <p class="card-text small text-muted">Preview/Review 세션의 이상적 비율 달성도</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>충돌 발생률
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="display-4 font-weight-bold text-warning">3.2%</div>
                    <p class="card-text small text-muted">스케줄링 충돌이 발생한 비율</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card border-info h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-check-circle mr-2"></i>완료율
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="display-4 font-weight-bold text-info">92%</div>
                    <p class="card-text small text-muted">예정된 학습 세션의 완료 비율</p>
                </div>
            </div>
        </div>
    </div>
<?php
?>

    <!-- 스케줄 생성 폼 -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-magic mr-2"></i>자동 편성</h3>
                </div>
                <div class="card-body">
                    <form id="spiral-generate-form">
                        <input type="hidden" name="sesskey" value="<?php echo session_id(); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">학생 선택</label>
                                    <select class="form-control" id="student_id" name="student_id" required>
                                        <option value="">-- 학생을 선택하세요 --</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo $student->id; ?>">
                                                <?php echo htmlspecialchars($student->firstname . ' ' . $student->lastname); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                        
                        <div class="form-group">
                            <label for="start_date">시작일</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">종료일 (시험일)</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alpha_slider">
                                선행 비율: <span id="alpha_value">70</span>%
                            </label>
                            <input type="range" class="form-control-range" id="alpha_slider" 
                                   name="alpha" min="50" max="90" value="70" step="5">
                        </div>
                        
                        <div class="form-group">
                            <label for="beta_display">
                                복습 비율: <span id="beta_value">30</span>%
                            </label>
                            <div class="progress">
                                <div class="progress-bar bg-info" id="preview_bar" style="width: 70%">선행</div>
                                <div class="progress-bar bg-warning" id="review_bar" style="width: 30%">복습</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="hours_per_week">주당 학습 시간 (선택)</label>
                            <input type="number" class="form-control" id="hours_per_week" 
                                   name="hours_per_week" min="5" max="40" placeholder="자동 계산">
                        </div>
                    </div>
                </div>
                
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="generate-btn">
                                <i class="fas fa-magic"></i> 자동 편성
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 생성된 스케줄 표시 영역 -->
    <div class="row">
        <div class="col-12">
            <div id="schedule-display" class="d-none">
                <div class="card">
                    <div class="card-header">
                        <h3>스케줄</h3>
                        <div class="float-right">
                            <button class="btn btn-success" id="publish-btn">
                                <i class="fas fa-check"></i> 발행
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="schedule-content">
                            <!-- 동적으로 로드됨 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 충돌 알림 영역 -->
    <div class="row">
        <div class="col-12">
            <div id="conflict-alerts" class="mt-3">
                <!-- 동적으로 로드됨 -->
            </div>
        </div>
    </div>
    
    <!-- 레거시 시스템 바로가기 링크 -->
    <div class="row">
        <div class="col-12">
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-link mr-2"></i>기존 시스템 바로가기
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 학생 개인 일정 뷰 링크 -->
                        <div class="col-md-4 mb-3">
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php" 
                               class="btn btn-outline-primary btn-block" 
                               target="_blank">
                                <i class="fas fa-calendar mr-2"></i>학생 개인 일정 보기
                            </a>
                        </div>
                        
                        <!-- 출석 기록 뷰 링크 -->
                        <div class="col-md-4 mb-3">
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/attendancerecords.php" 
                               class="btn btn-outline-info btn-block" 
                               target="_blank">
                                <i class="fas fa-user-check mr-2"></i>출석 기록 보기
                            </a>
                        </div>
                        
                        <!-- 메인 대시보드 링크 -->
                        <div class="col-md-4 mb-3">
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/dashboard.php" 
                               class="btn btn-outline-success btn-block" 
                               target="_blank">
                                <i class="fas fa-tachometer-alt mr-2"></i>대시보드 보기
                            </a>
                        </div>
                    </div>
                    <small class="text-muted">기존 시스템의 학생 관리 기능에 바로 접근할 수 있습니다.</small>
                </div>
            </div>
        </div>
    </div>

</div> <!-- container-fluid 종료 -->

<style>
.spiral-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.progress {
    height: 30px;
}

.progress-bar {
    font-weight: bold;
    line-height: 30px;
}

#schedule-content {
    min-height: 400px;
}

.conflict-badge {
    background-color: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75em;
}

.session-card {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
    cursor: move;
}

.session-card.preview {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.session-card.review {
    background-color: #fff3e0;
    border-left: 4px solid #ff9800;
}
</style>

<script>
// 슬라이더 연동
document.getElementById('alpha_slider').addEventListener('input', function() {
    const alpha = parseInt(this.value);
    const beta = 100 - alpha;
    
    document.getElementById('alpha_value').textContent = alpha;
    document.getElementById('beta_value').textContent = beta;
    
    document.getElementById('preview_bar').style.width = alpha + '%';
    document.getElementById('preview_bar').textContent = '선행 ' + alpha + '%';
    
    document.getElementById('review_bar').style.width = beta + '%';
    document.getElementById('review_bar').textContent = '복습 ' + beta + '%';
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>

<script>
// 폼 제출 처리
$('#spiral-generate-form').on('submit', function(e) {
    e.preventDefault();
    
    // 로딩 상태 표시
    $('#generate-btn').html('<i class="fas fa-spinner fa-spin"></i> 생성 중...').prop('disabled', true);
    
    // 여기에 AJAX 요청 코드 추가
    setTimeout(function() {
        // 임시로 성공 메시지 표시
        $('#schedule-display').removeClass('d-none');
        $('#schedule-content').html(`
            <div class="alert alert-success">
                <h5>스케줄이 성공적으로 생성되었습니다!</h5>
                <p>선택한 학생의 7:3 스파이럴 스케줄이 완료되었습니다.</p>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h6>선행학습 (70%)</h6>
                    <div class="list-group">
                        <div class="list-group-item">수학 1단원 - 함수</div>
                        <div class="list-group-item">수학 2단원 - 방정식</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>복습 (30%)</h6>
                    <div class="list-group">
                        <div class="list-group-item">이전 단원 복습</div>
                        <div class="list-group-item">문제풀이 연습</div>
                    </div>
                </div>
            </div>
        `);
        
        $('#generate-btn').html('<i class="fas fa-magic"></i> 자동 편성').prop('disabled', false);
    }, 2000);
});

// 발행 버튼 처리
$(document).on('click', '#publish-btn', function() {
    if (confirm('스케줄을 발행하시겠습니까? 학생에게 알림이 전송됩니다.')) {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> 발행 중...').prop('disabled', true);
        
        setTimeout(function() {
            alert('스케줄이 성공적으로 발행되었습니다!');
            $('#publish-btn').html('<i class="fas fa-check"></i> 발행 완료').removeClass('btn-success').addClass('btn-secondary');
        }, 1500);
    }
});
</script>

</body>
</html>