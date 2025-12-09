<?php
/**
 * Spiral Scheduler Teacher Dashboard
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_spiral\api\plan_api;

// 권한 체크
require_login();
plan_api::require_teacher_capability();

// 페이지 설정
$PAGE->set_url('/local/spiral/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('dashboard', 'local_spiral'));
$PAGE->set_heading(get_string('pluginname', 'local_spiral'));

// CSS/JS 로드
$PAGE->requires->css('/local/spiral/styles.css');
$PAGE->requires->js_call_amd('local_spiral/plan_editor', 'init');

// 현재 교사의 학생 목록 조회 - 이메일 마스킹 처리
$students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname,
           CONCAT(LEFT(u.email, 2), '***@', SUBSTRING_INDEX(u.email, '@', -1)) as email_masked
    FROM {user} u
    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid
    JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
    JOIN {role_assignments} ra ON ra.contextid = ctx.id
    WHERE ra.userid = :teacherid
    AND ra.roleid IN (3, 4, 5)
    AND u.deleted = 0
    ORDER BY u.lastname, u.firstname
", ['teacherid' => $USER->id]);

// 출력 시작
echo $OUTPUT->header();

// KPI 카드 렌더링
try {
    $kpiData = \local_spiral\local\kpi_service::get_current_snapshot();
    $trends = \local_spiral\local\kpi_service::get_trends(7); // 7일 추세
    $kpiCards = new \local_spiral\output\kpi_cards($kpiData, $trends);
    echo $OUTPUT->render_from_template('local_spiral/kpi_cards', $kpiCards->export_for_template($OUTPUT));
} catch (Exception $e) {
    // KPI 로딩 실패 시 간단한 메시지 표시
    echo '<div class="alert alert-warning mb-4">';
    echo '<i class="fa fa-exclamation-triangle mr-2"></i>';
    echo 'KPI 데이터를 로딩하는 중 오류가 발생했습니다.';
    echo '</div>';
}
?>

<div class="spiral-dashboard">
    <h2><?php echo get_string('dashboard', 'local_spiral'); ?></h2>
    
    <!-- 스케줄 생성 폼 -->
    <div class="card mb-4">
        <div class="card-header">
            <h3><?php echo get_string('generate', 'local_spiral'); ?></h3>
        </div>
        <div class="card-body">
            <form id="spiral-generate-form">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="student_id">학생 선택</label>
                            <select class="form-control" id="student_id" name="student_id" required>
                                <option value="">-- 학생을 선택하세요 --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student->id; ?>">
                                        <?php echo s($student->firstname . ' ' . $student->lastname); ?>
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
                                <?php echo get_string('ratio_preview', 'local_spiral'); ?>: 
                                <span id="alpha_value">70</span>%
                            </label>
                            <input type="range" class="form-control-range" id="alpha_slider" 
                                   name="alpha" min="50" max="90" value="70" step="5">
                        </div>
                        
                        <div class="form-group">
                            <label for="beta_display">
                                <?php echo get_string('ratio_review', 'local_spiral'); ?>: 
                                <span id="beta_value">30</span>%
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
                        <i class="fa fa-magic"></i> <?php echo get_string('generate', 'local_spiral'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 생성된 스케줄 표시 영역 -->
    <div id="schedule-display" class="d-none">
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_string('schedule', 'local_spiral'); ?></h3>
                <div class="float-right">
                    <button class="btn btn-success" id="publish-btn">
                        <i class="fa fa-check"></i> <?php echo get_string('publish', 'local_spiral'); ?>
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
    
    <!-- 충돌 알림 영역 -->
    <div id="conflict-alerts" class="mt-3">
        <!-- 동적으로 로드됨 -->
    </div>
    
    <!-- 레거시 시스템 바로가기 링크 -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-link mr-2"></i><?php echo get_string('legacy_links', 'local_spiral'); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- 학생 개인 일정 뷰 링크 -->
                <div class="col-md-4 mb-3">
                    <a href="/local/augmented_teacher/students/schedule.php" 
                       class="btn btn-outline-primary btn-block" 
                       target="_blank">
                        <i class="fa fa-calendar mr-2"></i><?php echo get_string('view_student_schedule', 'local_spiral'); ?>
                    </a>
                </div>
                
                <!-- 출석 기록 뷰 링크 -->
                <div class="col-md-4 mb-3">
                    <a href="/local/augmented_teacher/students/attendancerecords.php" 
                       class="btn btn-outline-info btn-block" 
                       target="_blank">
                        <i class="fa fa-user-check mr-2"></i><?php echo get_string('view_attendance_records', 'local_spiral'); ?>
                    </a>
                </div>
                
                <!-- 메인 대시보드 링크 -->
                <div class="col-md-4 mb-3">
                    <a href="/local/augmented_teacher/alt42/omniui/dashboard.php" 
                       class="btn btn-outline-success btn-block" 
                       target="_blank">
                        <i class="fa fa-tachometer-alt mr-2"></i><?php echo get_string('view_dashboard', 'local_spiral'); ?>
                    </a>
                </div>
            </div>
            <small class="text-muted">기존 시스템의 학생 관리 기능에 바로 접근할 수 있습니다.</small>
        </div>
    </div>
</div>

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

<?php
echo $OUTPUT->footer();