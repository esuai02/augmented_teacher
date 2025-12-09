<?php
/**
 * ExamFocus 실사용 통합 코드
 * 기존 페이지에 붙여넣기 - DB 기반 실제 추천
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ========================================
// 이 코드를 기존 페이지에 복사해서 사용하세요
// ========================================

// 사용자 ID 가져오기 (기존 로그인 시스템과 연동)
$examfocus_userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 
                   (isset($USER) ? $USER->id : 
                   (isset($_GET['user_id']) ? intval($_GET['user_id']) : null));

// ExamFocus 실제 추천 함수
function get_examfocus_real_recommendation($userid) {
    if (!$userid) return ['show' => false];
    
    try {
        // 데이터베이스 연결
        $dsn = "mysql:host=58.180.27.46;dbname=mathking;charset=utf8mb4";
        $pdo = new PDO($dsn, 'moodle', '@MCtrigd7128', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        // 1. 사용자 정보 확인
        $stmt = $pdo->prepare("
            SELECT id, username, firstname, lastname 
            FROM mdl_user 
            WHERE id = :userid AND deleted = 0
        ");
        $stmt->execute(['userid' => $userid]);
        $user = $stmt->fetch();
        
        if (!$user) return ['show' => false];
        
        // 2. Alt42t DB에서 시험 정보 조회
        $exam_data = null;
        try {
            // Alt42t 연결 시도
            $alt_hosts = ['127.0.0.1', '58.180.27.46', 'localhost'];
            foreach ($alt_hosts as $host) {
                try {
                    $alt_dsn = "mysql:host={$host};port=3306;dbname=alt42t;charset=utf8mb4";
                    $alt_pdo = new PDO($alt_dsn, 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 2
                    ]);
                    
                    // 가장 가까운 시험 조회
                    $stmt = $alt_pdo->prepare("
                        SELECT math_exam_date, exam_type, exam_scope, school, grade
                        FROM student_exam_settings
                        WHERE user_id = :userid
                        AND exam_status IN ('confirmed', 'expected')
                        AND math_exam_date >= CURDATE()
                        ORDER BY math_exam_date ASC
                        LIMIT 1
                    ");
                    $stmt->execute(['userid' => $userid]);
                    $exam = $stmt->fetch();
                    
                    if ($exam && $exam['math_exam_date']) {
                        $days_until = floor((strtotime($exam['math_exam_date']) - time()) / 86400);
                        $exam_data = [
                            'exam_date' => $exam['math_exam_date'],
                            'exam_type' => $exam['exam_type'] ?: '정기고사',
                            'exam_scope' => $exam['exam_scope'],
                            'school' => $exam['school'],
                            'grade' => $exam['grade'],
                            'days_until' => $days_until
                        ];
                    }
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
        } catch (Exception $e) {
            // Alt42t 실패는 무시
        }
        
        // 3. 학습 통계 조회
        $week_ago = time() - (7 * 86400);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as week_count
            FROM mdl_abessi_missionlog
            WHERE userid = :userid AND timecreated > :weekago
        ");
        $stmt->execute(['userid' => $userid, 'weekago' => $week_ago]);
        $stats = $stmt->fetch();
        $week_hours = round(($stats['week_count'] ?: 0) * 0.5, 1);
        
        // 4. 추천 로직
        if ($exam_data && $exam_data['days_until'] > 0) {
            $days = $exam_data['days_until'];
            
            if ($days <= 7) {
                return [
                    'show' => true,
                    'mode' => 'concept_summary',
                    'title' => '🚨 D-' . $days . ' 개념요약 집중!',
                    'message' => '시험이 ' . $days . '일 앞으로 다가왔습니다. 개념요약과 대표유형에 집중하세요.',
                    'priority' => 'danger',
                    'exam_info' => $exam_data,
                    'study_hours' => $week_hours,
                    'user_name' => trim($user['firstname'] . ' ' . $user['lastname'])
                ];
            } elseif ($days <= 30) {
                return [
                    'show' => true,
                    'mode' => 'review_errors',
                    'title' => '📚 D-' . $days . ' 오답 회독 모드',
                    'message' => '시험 준비의 황금 시기입니다. 체계적인 오답 복습으로 실력을 다지세요.',
                    'priority' => 'warning',
                    'exam_info' => $exam_data,
                    'study_hours' => $week_hours,
                    'user_name' => trim($user['firstname'] . ' ' . $user['lastname'])
                ];
            }
        }
        
        return ['show' => false];
        
    } catch (Exception $e) {
        error_log("ExamFocus Integration Error: " . $e->getMessage());
        return ['show' => false];
    }
}

// 쿨다운 체크 (24시간)
$examfocus_cooldown_key = 'examfocus_shown_' . $examfocus_userid;
$examfocus_last_shown = isset($_SESSION[$examfocus_cooldown_key]) ? $_SESSION[$examfocus_cooldown_key] : 0;
$examfocus_show_banner = (time() - $examfocus_last_shown) > 86400; // 24시간

// 추천 정보 가져오기
$examfocus_recommendation = null;
if ($examfocus_userid && $examfocus_show_banner) {
    $examfocus_recommendation = get_examfocus_real_recommendation($examfocus_userid);
    
    // 배너 표시했음을 기록
    if ($examfocus_recommendation && $examfocus_recommendation['show']) {
        $_SESSION[$examfocus_cooldown_key] = time();
    }
}

// ========================================
// HTML 출력 (페이지 적절한 위치에 배치)
// ========================================

if ($examfocus_recommendation && $examfocus_recommendation['show']):
?>

<!-- ExamFocus 실시간 추천 배너 -->
<div class="examfocus-real-banner alert alert-<?php echo $examfocus_recommendation['priority']; ?>" 
     id="examfocus-banner" 
     style="margin: 20px 0; padding: 25px; border-left: 5px solid; border-radius: 10px; position: relative;">
     
    <button type="button" class="btn-close" style="position: absolute; top: 10px; right: 15px;" 
            onclick="dismissExamFocus()" aria-label="Close">×</button>
    
    <!-- 헤더 -->
    <div class="row align-items-center mb-3">
        <div class="col-md-8">
            <h4 class="mb-1" style="color: inherit; font-weight: bold;">
                <?php echo $examfocus_recommendation['title']; ?>
            </h4>
            <small class="text-muted">
                👤 <?php echo htmlspecialchars($examfocus_recommendation['user_name']); ?> | 
                📚 이번 주 <?php echo $examfocus_recommendation['study_hours']; ?>시간 학습
            </small>
        </div>
        <div class="col-md-4 text-end">
            <div class="badge bg-light text-dark fs-6 p-2">
                📅 <?php echo $examfocus_recommendation['exam_info']['exam_date']; ?>
            </div>
        </div>
    </div>
    
    <!-- 메시지 -->
    <p class="mb-3" style="font-size: 1.1em; line-height: 1.5;">
        <?php echo $examfocus_recommendation['message']; ?>
    </p>
    
    <!-- 시험 정보 -->
    <div class="row mb-3">
        <div class="col-md-3">
            <strong>시험 종류</strong><br>
            <span class="text-muted"><?php echo htmlspecialchars($examfocus_recommendation['exam_info']['exam_type']); ?></span>
        </div>
        <div class="col-md-3">
            <strong>시험까지</strong><br>
            <span class="fw-bold" style="color: #dc3545;">D-<?php echo $examfocus_recommendation['exam_info']['days_until']; ?></span>
        </div>
        <?php if ($examfocus_recommendation['exam_info']['school']): ?>
        <div class="col-md-3">
            <strong>학교</strong><br>
            <span class="text-muted"><?php echo htmlspecialchars($examfocus_recommendation['exam_info']['school']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($examfocus_recommendation['exam_info']['grade']): ?>
        <div class="col-md-3">
            <strong>학년</strong><br>
            <span class="text-muted"><?php echo htmlspecialchars($examfocus_recommendation['exam_info']['grade']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($examfocus_recommendation['exam_info']['exam_scope']): ?>
    <!-- 시험 범위 -->
    <div class="mb-3">
        <strong>📖 시험 범위</strong><br>
        <div class="bg-light p-2 rounded mt-1" style="font-size: 0.9em;">
            <?php echo nl2br(htmlspecialchars(substr($examfocus_recommendation['exam_info']['exam_scope'], 0, 200))); ?>
            <?php if (strlen($examfocus_recommendation['exam_info']['exam_scope']) > 200): ?>...<?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 액션 버튼 -->
    <div class="d-flex gap-2">
        <button class="btn btn-success" onclick="startExamFocusMode('<?php echo $examfocus_recommendation['mode']; ?>')">
            ✨ <?php echo $examfocus_recommendation['mode']; ?> 모드 시작
        </button>
        <button class="btn btn-outline-primary" onclick="viewExamFocusDetails()">
            📋 자세히 보기
        </button>
        <button class="btn btn-outline-secondary" onclick="dismissExamFocus()">
            나중에
        </button>
    </div>
</div>

<!-- ExamFocus 스타일 -->
<style>
.examfocus-real-banner {
    animation: examfocus-slideDown 0.5s ease-out;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.examfocus-real-banner.alert-danger {
    background-color: #fee;
    border-left-color: #dc3545 !important;
}

.examfocus-real-banner.alert-warning {
    background-color: #fff8e1;
    border-left-color: #ffc107 !important;
}

.examfocus-real-banner.alert-success {
    background-color: #f0fff4;
    border-left-color: #28a745 !important;
}

@keyframes examfocus-slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.examfocus-real-banner .btn-close {
    background: none;
    border: none;
    font-size: 1.2em;
    font-weight: bold;
    cursor: pointer;
    opacity: 0.6;
}

.examfocus-real-banner .btn-close:hover {
    opacity: 1;
}
</style>

<!-- ExamFocus JavaScript -->
<script>
// ExamFocus 모드 시작
function startExamFocusMode(mode) {
    // 서버에 모드 시작 요청
    fetch('/moodle/local/augmented_teacher/alt42/omniui/local/examfocus/actions/start_mode.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            user_id: <?php echo $examfocus_userid; ?>,
            mode: mode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 로컬 스토리지에 선택한 모드 저장
            localStorage.setItem('examfocus_selected_mode', mode);
            localStorage.setItem('examfocus_started_at', Date.now());
            
            // 성공 알림
            alert('🎉 ' + data.message);
            
            // 리다이렉트 URL이 있으면 이동
            if (data.redirect_url) {
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1000);
            }
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('서버와의 통신 중 오류가 발생했습니다.');
    });
    
    // 배너 숨기기
    dismissExamFocus();
}

// ExamFocus 상세 보기
function viewExamFocusDetails() {
    // 상세 페이지로 이동
    window.open('/moodle/local/augmented_teacher/alt42/omniui/local/examfocus/quickstart_real.php?user_id=<?php echo $examfocus_userid; ?>', 
                '_blank', 
                'width=1000,height=800,scrollbars=yes');
}

// ExamFocus 배너 닫기
function dismissExamFocus() {
    const banner = document.getElementById('examfocus-banner');
    if (banner) {
        banner.style.animation = 'examfocus-slideUp 0.3s ease-in';
        setTimeout(() => {
            banner.style.display = 'none';
        }, 300);
    }
    
    // 세션 스토리지에 기록 (1시간 동안 재표시 안 함)
    sessionStorage.setItem('examfocus_dismissed_at', Date.now());
}

// 페이지 로드 시 세션 체크
document.addEventListener('DOMContentLoaded', function() {
    const dismissedAt = sessionStorage.getItem('examfocus_dismissed_at');
    if (dismissedAt && (Date.now() - dismissedAt) < 3600000) { // 1시간
        const banner = document.getElementById('examfocus-banner');
        if (banner) banner.style.display = 'none';
    }
});

// 슬라이드업 애니메이션
const style = document.createElement('style');
style.textContent = `
@keyframes examfocus-slideUp {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-20px);
    }
}
`;
document.head.appendChild(style);
</script>

<?php endif; ?>

<!-- ======================================== -->
<!-- 여기까지가 ExamFocus 실사용 통합 코드입니다 -->
<!-- ======================================== -->