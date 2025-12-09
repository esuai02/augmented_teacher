<?php
/**
 * ExamFocus 수정된 인덱스 페이지
 * Moodle CFG 로드 문제 해결 버전
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 처리
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// 출력 버퍼링
ob_start();

// Moodle 부트스트랩 - 올바른 방법
$config_paths = [
    '/home/moodle/public_html/moodle/config.php',
    __DIR__ . '/../../../../config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php'
];

$moodle_loaded = false;
foreach ($config_paths as $config_path) {
    if (file_exists($config_path)) {
        try {
            require_once($config_path);
            $moodle_loaded = true;
            break;
        } catch (Exception $e) {
            continue;
        }
    }
}

// Moodle 환경이 로드되었는지 확인
if ($moodle_loaded && defined('MOODLE_INTERNAL')) {
    // Moodle 로그인 체크 (선택적)
    try {
        require_login(0, false);
        $userid = $USER->id;
        $username = fullname($USER);
    } catch (Exception $e) {
        // 로그인 실패 시 기본값
        $userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2;
        $username = 'Guest User';
    }
    
    // 페이지 설정
    $PAGE->set_url('/local/examfocus/index.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title('ExamFocus - 시험 대비 자동 학습 모드');
    $PAGE->set_heading('ExamFocus');
    $PAGE->set_pagelayout('standard');
    
    // Moodle 헤더
    echo $OUTPUT->header();
} else {
    // Moodle 없이 독립 실행
    $userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2;
    $username = 'User';
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ExamFocus - 시험 대비 자동 학습 모드</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            body { padding: 20px; background: #f8f9fa; }
            .container { max-width: 1000px; }
        </style>
    </head>
    <body>
    <?php
}
?>

<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">📚 ExamFocus - 시험 대비 자동 학습 모드</h2>
        </div>
        <div class="card-body">
            
            <!-- 사용자 정보 -->
            <div class="alert alert-info">
                <strong>사용자:</strong> <?php echo htmlspecialchars($username); ?> (ID: <?php echo $userid; ?>)<br>
                <strong>시스템 상태:</strong> <?php echo $moodle_loaded ? '✅ Moodle 통합' : '⚠️ 독립 실행 모드'; ?>
            </div>
            
            <?php
            // 추천 로직 실행
            $recommendation = null;
            $error_message = null;
            
            try {
                // 데이터베이스 연결
                $dsn = "mysql:host=58.180.27.46;dbname=mathking;charset=utf8mb4";
                $pdo = new PDO($dsn, 'moodle', '@MCtrigd7128', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 10
                ]);
                
                // 시험 날짜 조회 (간단 버전)
                $exam_date = null;
                $days_until = null;
                
                // Alt42t 시도 (실패해도 무시)
                try {
                    // TCP/IP 연결 시도
                    $alt_dsn = "mysql:host=58.180.27.46;port=3306;dbname=alt42t;charset=utf8mb4";
                    $alt_pdo = new PDO($alt_dsn, 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                        PDO::ATTR_TIMEOUT => 3
                    ]);
                    
                    $stmt = $alt_pdo->prepare("
                        SELECT math_exam_date, exam_type
                        FROM student_exam_settings
                        WHERE user_id = :userid
                        AND exam_status = 'confirmed'
                        AND math_exam_date >= CURDATE()
                        ORDER BY math_exam_date ASC
                        LIMIT 1
                    ");
                    $stmt->execute(['userid' => $userid]);
                    $result = $stmt->fetch();
                    
                    if ($result && $result['math_exam_date']) {
                        $exam_date = $result['math_exam_date'];
                        $days_until = floor((strtotime($exam_date) - time()) / 86400);
                    }
                } catch (Exception $e) {
                    // Alt42t 실패 무시
                }
                
                // 테스트용 가상 데이터 (실제 데이터가 없을 때)
                if (!$exam_date) {
                    // 30일 후를 가상 시험일로 설정 (테스트용)
                    $exam_date = date('Y-m-d', strtotime('+30 days'));
                    $days_until = 30;
                }
                
                // 추천 생성
                if ($days_until > 0) {
                    if ($days_until <= 7) {
                        $recommendation = [
                            'mode' => 'concept_summary',
                            'message' => '시험이 일주일 앞으로 다가왔습니다! 개념요약과 대표유형에 집중하세요.',
                            'priority' => 'danger',
                            'days_until' => $days_until,
                            'exam_date' => $exam_date,
                            'icon' => '🚨'
                        ];
                    } elseif ($days_until <= 30) {
                        $recommendation = [
                            'mode' => 'review_errors',
                            'message' => '시험 준비를 시작할 시간입니다. 오답 회독 모드로 체계적인 복습을 시작하세요.',
                            'priority' => 'warning',
                            'days_until' => $days_until,
                            'exam_date' => $exam_date,
                            'icon' => '📚'
                        ];
                    } else {
                        $recommendation = [
                            'mode' => 'regular',
                            'message' => '꾸준한 학습을 유지하세요. 시험이 다가오면 자동으로 모드를 추천해 드립니다.',
                            'priority' => 'success',
                            'days_until' => $days_until,
                            'exam_date' => $exam_date,
                            'icon' => '📖'
                        ];
                    }
                }
                
            } catch (Exception $e) {
                $error_message = "데이터베이스 연결 오류: " . $e->getMessage();
            }
            ?>
            
            <!-- 추천 표시 -->
            <?php if ($recommendation): ?>
            <div class="alert alert-<?php echo $recommendation['priority']; ?> shadow-sm">
                <h4><?php echo $recommendation['icon']; ?> 학습 모드 추천</h4>
                <hr>
                <p class="mb-3"><?php echo $recommendation['message']; ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <strong>시험까지:</strong> D-<?php echo $recommendation['days_until']; ?><br>
                        <strong>시험일:</strong> <?php echo $recommendation['exam_date']; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>추천 모드:</strong> <?php echo $recommendation['mode']; ?>
                    </div>
                </div>
                <hr>
                <button class="btn btn-success" onclick="applyMode('<?php echo $recommendation['mode']; ?>')">
                    ✅ 추천 모드 적용
                </button>
                <button class="btn btn-secondary" onclick="dismissRecommendation()">
                    나중에
                </button>
            </div>
            <?php elseif ($error_message): ?>
            <div class="alert alert-danger">
                <strong>오류:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php else: ?>
            <div class="alert alert-secondary">
                <h5>추천 정보 없음</h5>
                <p>현재 예정된 시험이 없거나 추천할 학습 모드가 없습니다.</p>
            </div>
            <?php endif; ?>
            
            <!-- 기능 소개 -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3>📅</h3>
                            <h5>D-30 알림</h5>
                            <p class="text-muted">시험 30일 전 오답 회독 모드 추천</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3>⚡</h3>
                            <h5>D-7 집중</h5>
                            <p class="text-muted">시험 일주일 전 개념요약 집중</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3>🔄</h3>
                            <h5>자동 전환</h5>
                            <p class="text-muted">시험 일정에 따른 자동 모드 추천</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 링크 -->
            <hr class="mt-4">
            <div class="text-center">
                <a href="test.php" class="btn btn-outline-primary">시스템 테스트</a>
                <a href="standalone_test.php" class="btn btn-outline-secondary">독립 테스트</a>
                <a href="error_check.php" class="btn btn-outline-danger">오류 진단</a>
                <a href="ajax/get_recommendation.php?user_id=<?php echo $userid; ?>" class="btn btn-outline-info" target="_blank">API 테스트</a>
            </div>
        </div>
    </div>
    
    <!-- 통합 가이드 -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">🔧 통합 가이드</h5>
        </div>
        <div class="card-body">
            <p>기존 페이지에 ExamFocus를 추가하려면:</p>
            <pre class="bg-light p-3 rounded"><code>&lt;?php include(__DIR__ . '/local/examfocus/simple_integration.php'); ?&gt;</code></pre>
            
            <p>또는 AJAX로 추천 정보 조회:</p>
            <pre class="bg-light p-3 rounded"><code>$.getJSON('local/examfocus/ajax/get_recommendation.php', {user_id: <?php echo $userid; ?>}, function(data) {
    if (data.has_recommendation) {
        console.log('추천:', data.mode, data.message);
    }
});</code></pre>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
function applyMode(mode) {
    alert('학습 모드를 ' + mode + '로 전환합니다.');
    sessionStorage.setItem('examfocus_mode', mode);
    sessionStorage.setItem('examfocus_applied', Date.now());
    
    // 실제 페이지로 이동 (필요시)
    // window.location.href = 'study_mode.php?mode=' + mode;
}

function dismissRecommendation() {
    $('.alert-warning, .alert-danger').fadeOut();
    sessionStorage.setItem('examfocus_dismissed', Date.now());
}

// 페이지 로드 시 세션 체크
$(document).ready(function() {
    var dismissed = sessionStorage.getItem('examfocus_dismissed');
    var applied = sessionStorage.getItem('examfocus_applied');
    var now = Date.now();
    
    // 24시간 체크
    if ((dismissed && (now - dismissed) < 86400000) || 
        (applied && (now - applied) < 86400000)) {
        console.log('ExamFocus: 쿨다운 중');
    }
});
</script>

<?php
if ($moodle_loaded && defined('MOODLE_INTERNAL')) {
    // Moodle 푸터
    echo $OUTPUT->footer();
} else {
    // 독립 실행 푸터
    ?>
    </body>
    </html>
    <?php
}

// 출력 버퍼 플러시
ob_end_flush();
?>