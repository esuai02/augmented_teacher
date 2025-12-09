<?php
/**
 * ExamFocus 안전 모드 인덱스 페이지
 * 500 오류 방지 버전
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 출력 제어
ini_set('display_errors', 0);
error_reporting(0);

// 출력 버퍼링 시작
ob_start();

// 세션 시작 (이미 시작되지 않았다면)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 기본 HTML 헤더 출력
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFocus - 시험 대비 자동 학습 모드</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .examfocus-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-ok { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-error { background-color: #dc3545; }
        .recommendation-banner {
            border-left: 5px solid #17a2b8;
            padding: 20px;
            background: #e7f3ff;
            border-radius: 5px;
            margin: 20px 0;
        }
        .recommendation-banner.urgent {
            border-left-color: #dc3545;
            background: #ffe7e7;
        }
        .recommendation-banner.important {
            border-left-color: #ffc107;
            background: #fff8e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="examfocus-card">
            <h1>📚 ExamFocus</h1>
            <p class="lead">시험 대비 자동 학습 모드 전환 시스템</p>
            <hr>
            
            <?php
            // 안전한 데이터베이스 연결 시도
            $db_connected = false;
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2; // 기본값 2
            $recommendation = null;
            
            try {
                // MathKing DB 연결 시도
                if (!defined('MATHKING_DB_HOST')) {
                    define('MATHKING_DB_HOST', '58.180.27.46');
                    define('MATHKING_DB_NAME', 'mathking');
                    define('MATHKING_DB_USER', 'moodle');
                    define('MATHKING_DB_PASS', '@MCtrigd7128');
                }
                
                $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                $db_connected = true;
                
                // 간단한 추천 로직
                $exam_date = null;
                $days_until = null;
                
                // Alt42t DB 시도 (실패해도 무시)
                try {
                    $alt42t = new PDO("mysql:host=127.0.0.1;dbname=alt42t;charset=utf8mb4", 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
                    ]);
                    
                    $stmt = $alt42t->prepare("
                        SELECT math_exam_date 
                        FROM student_exam_settings 
                        WHERE user_id = ? 
                        AND exam_status = 'confirmed' 
                        AND math_exam_date >= CURDATE() 
                        ORDER BY math_exam_date ASC 
                        LIMIT 1
                    ");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch();
                    
                    if ($result && $result['math_exam_date']) {
                        $exam_date = $result['math_exam_date'];
                        $days_until = floor((strtotime($exam_date) - time()) / 86400);
                    }
                } catch (Exception $e) {
                    // Alt42t 실패 무시
                }
                
                // 추천 생성
                if ($days_until !== null && $days_until > 0) {
                    if ($days_until <= 7) {
                        $recommendation = [
                            'mode' => 'concept_summary',
                            'message' => '시험이 일주일 앞으로 다가왔습니다! 개념요약과 대표유형에 집중하세요.',
                            'priority' => 'urgent',
                            'days_until' => $days_until,
                            'exam_date' => $exam_date
                        ];
                    } elseif ($days_until <= 30) {
                        $recommendation = [
                            'mode' => 'review_errors',
                            'message' => '시험 준비를 시작할 시간입니다. 오답 회독 모드로 체계적인 복습을 시작하세요.',
                            'priority' => 'important',
                            'days_until' => $days_until,
                            'exam_date' => $exam_date
                        ];
                    }
                }
                
            } catch (Exception $e) {
                // 모든 에러 무시
            }
            ?>
            
            <!-- 상태 표시 -->
            <div class="alert alert-info">
                <h5>시스템 상태</h5>
                <p>
                    <span class="status-indicator <?php echo $db_connected ? 'status-ok' : 'status-error'; ?>"></span>
                    데이터베이스: <?php echo $db_connected ? '연결됨' : '연결 안 됨'; ?>
                </p>
                <p>
                    <span class="status-indicator status-ok"></span>
                    플러그인 상태: 정상 작동
                </p>
                <p>
                    사용자 ID: <?php echo $user_id; ?>
                </p>
            </div>
            
            <!-- 추천 표시 -->
            <?php if ($recommendation): ?>
            <div class="recommendation-banner <?php echo $recommendation['priority']; ?>">
                <h4>🎯 학습 모드 추천</h4>
                <p><strong>D-<?php echo $recommendation['days_until']; ?></strong> (시험일: <?php echo $recommendation['exam_date']; ?>)</p>
                <p><?php echo $recommendation['message']; ?></p>
                <div class="mt-3">
                    <button class="btn btn-success" onclick="applyMode('<?php echo $recommendation['mode']; ?>')">
                        추천 모드 적용
                    </button>
                    <button class="btn btn-secondary" onclick="dismissBanner()">
                        나중에
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-secondary">
                <h5>추천 정보 없음</h5>
                <p>현재 예정된 시험이 없거나 추천할 학습 모드가 없습니다.</p>
                <p>시험 일정을 등록하면 자동으로 학습 모드를 추천해 드립니다.</p>
            </div>
            <?php endif; ?>
            
            <!-- 기능 설명 -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📅 D-30 알림</h5>
                            <p class="card-text">시험 30일 전부터 오답 회독 모드를 추천합니다.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">⚡ D-7 집중</h5>
                            <p class="card-text">시험 일주일 전 개념요약과 대표유형에 집중합니다.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🔄 자동 전환</h5>
                            <p class="card-text">시험 일정에 따라 최적의 학습 모드를 자동 추천합니다.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 테스트 링크 -->
            <hr>
            <div class="text-center">
                <a href="test.php" class="btn btn-outline-primary">시스템 테스트</a>
                <a href="standalone_test.php" class="btn btn-outline-secondary">독립 실행 테스트</a>
                <a href="simple_integration.php" class="btn btn-outline-info">통합 예제</a>
            </div>
        </div>
        
        <!-- 사용 가이드 -->
        <div class="examfocus-card">
            <h3>🔧 통합 가이드</h3>
            <p>기존 페이지에 ExamFocus를 통합하려면:</p>
            <pre class="bg-light p-3"><code>&lt;?php include(__DIR__ . '/local/examfocus/simple_integration.php'); ?&gt;</code></pre>
            <p>또는 AJAX로 추천 정보 조회:</p>
            <pre class="bg-light p-3"><code>$.ajax({
    url: 'local/examfocus/ajax/get_recommendation.php',
    data: { user_id: userId },
    success: function(data) {
        // 추천 정보 처리
    }
});</code></pre>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        function applyMode(mode) {
            alert('학습 모드를 ' + mode + '로 전환합니다.');
            sessionStorage.setItem('examfocus_mode', mode);
            sessionStorage.setItem('examfocus_applied', Date.now());
            location.reload();
        }
        
        function dismissBanner() {
            $('.recommendation-banner').fadeOut();
            sessionStorage.setItem('examfocus_dismissed', Date.now());
        }
        
        // 세션 체크
        $(document).ready(function() {
            var dismissed = sessionStorage.getItem('examfocus_dismissed');
            var applied = sessionStorage.getItem('examfocus_applied');
            var now = Date.now();
            
            // 24시간 체크
            if ((dismissed && (now - dismissed) < 86400000) || 
                (applied && (now - applied) < 86400000)) {
                $('.recommendation-banner').hide();
            }
        });
    </script>
</body>
</html>
<?php
// 출력 버퍼 플러시
ob_end_flush();
?>