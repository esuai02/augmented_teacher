<?php
/**
 * ExamFocus 간단 통합 예제
 * 기존 페이지에 복사해서 사용하세요
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ========================================
// 이 코드를 기존 페이지 상단에 추가하세요
// ========================================

// 데이터베이스 연결 (이미 있다면 생략)
if (!defined('MATHKING_DB_HOST')) {
    define('MATHKING_DB_HOST', '58.180.27.46');
    define('MATHKING_DB_NAME', 'mathking');
    define('MATHKING_DB_USER', 'moodle');
    define('MATHKING_DB_PASS', '@MCtrigd7128');
}

// 간단한 추천 함수
function get_exam_recommendation($userid) {
    try {
        $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS);
        
        // 1. Alt42t에서 시험 날짜 조회 (가능한 경우)
        $exam_date = null;
        $days_until = null;
        
        try {
            $alt42t = new PDO("mysql:host=127.0.0.1;dbname=alt42t;charset=utf8mb4", 'root', '');
            $stmt = $alt42t->prepare("
                SELECT math_exam_date 
                FROM student_exam_settings 
                WHERE user_id = ? AND exam_status = 'confirmed' 
                AND math_exam_date >= CURDATE() 
                ORDER BY math_exam_date ASC LIMIT 1
            ");
            $stmt->execute([$userid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['math_exam_date']) {
                $exam_date = $result['math_exam_date'];
                $days_until = floor((strtotime($exam_date) - time()) / 86400);
            }
        } catch (Exception $e) {
            // Alt42t 연결 실패 시 무시
        }
        
        // 2. 추천 로직
        if ($days_until !== null) {
            if ($days_until <= 7 && $days_until > 0) {
                return [
                    'show' => true,
                    'mode' => 'concept_summary',
                    'message' => '🚨 시험 D-' . $days_until . '! 개념요약과 대표유형에 집중하세요.',
                    'priority' => 'danger',
                    'exam_date' => $exam_date
                ];
            } elseif ($days_until <= 30 && $days_until > 7) {
                return [
                    'show' => true,
                    'mode' => 'review_errors',
                    'message' => '📚 시험 D-' . $days_until . '! 오답 회독을 시작하세요.',
                    'priority' => 'warning',
                    'exam_date' => $exam_date
                ];
            }
        }
        
    } catch (Exception $e) {
        error_log("ExamFocus Error: " . $e->getMessage());
    }
    
    return ['show' => false];
}

// ========================================
// HTML 부분 (페이지 본문에 추가)
// ========================================

// 로그인한 사용자 ID (세션에서 가져오기)
$userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2; // 테스트용 기본값 2

// 추천 정보 가져오기
$recommendation = get_exam_recommendation($userid);

// 추천이 있으면 배너 표시
if ($recommendation['show']) {
?>

<!-- ExamFocus 추천 배너 -->
<div class="alert alert-<?php echo $recommendation['priority']; ?> examfocus-banner" style="margin: 20px 0; padding: 20px; border-left: 5px solid;">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="font-size: 1.5em;">
        <span aria-hidden="true">&times;</span>
    </button>
    
    <h4 style="margin-top: 0;">📌 시험 대비 학습 모드 추천</h4>
    
    <p style="font-size: 1.1em; margin: 15px 0;">
        <?php echo $recommendation['message']; ?>
    </p>
    
    <?php if (isset($recommendation['exam_date'])): ?>
    <p style="color: #666;">
        시험일: <?php echo $recommendation['exam_date']; ?>
    </p>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <button class="btn btn-success" onclick="applyRecommendation('<?php echo $recommendation['mode']; ?>')">
            ✅ 추천 모드로 전환
        </button>
        <button class="btn btn-secondary" onclick="dismissRecommendation()">
            나중에
        </button>
    </div>
</div>

<script>
// 추천 모드 적용
function applyRecommendation(mode) {
    // 모드별 페이지 이동 또는 처리
    var modePages = {
        'review_errors': 'review_errors.php',
        'concept_summary': 'concept_summary.php',
        'practice_problems': 'practice.php'
    };
    
    if (modePages[mode]) {
        // 해당 페이지로 이동
        alert('학습 모드를 ' + mode + '로 전환합니다.');
        // window.location.href = modePages[mode];
    }
    
    // 배너 숨기기
    document.querySelector('.examfocus-banner').style.display = 'none';
    
    // 세션 스토리지에 기록 (24시간 동안 다시 표시 안 함)
    sessionStorage.setItem('examfocus_applied', Date.now());
}

// 추천 무시
function dismissRecommendation() {
    document.querySelector('.examfocus-banner').style.display = 'none';
    sessionStorage.setItem('examfocus_dismissed', Date.now());
}

// 페이지 로드 시 이미 처리했는지 확인
window.addEventListener('DOMContentLoaded', function() {
    var dismissed = sessionStorage.getItem('examfocus_dismissed');
    var applied = sessionStorage.getItem('examfocus_applied');
    var now = Date.now();
    var dayInMs = 24 * 60 * 60 * 1000;
    
    // 24시간 이내에 이미 처리했으면 배너 숨기기
    if ((dismissed && (now - dismissed) < dayInMs) || 
        (applied && (now - applied) < dayInMs)) {
        var banner = document.querySelector('.examfocus-banner');
        if (banner) banner.style.display = 'none';
    }
});
</script>

<style>
.examfocus-banner {
    position: relative;
    animation: slideDown 0.5s ease-out;
}

.examfocus-banner.alert-danger {
    background-color: #f8d7da;
    border-left-color: #dc3545 !important;
}

.examfocus-banner.alert-warning {
    background-color: #fff3cd;
    border-left-color: #ffc107 !important;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.examfocus-banner .btn {
    margin-right: 10px;
}
</style>

<?php
} // if ($recommendation['show'])
?>

<!-- ======================================== -->
<!-- 여기까지가 ExamFocus 통합 코드입니다     -->
<!-- ======================================== -->