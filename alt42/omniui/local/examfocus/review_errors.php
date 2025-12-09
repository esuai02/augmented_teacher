<?php
/**
 * ExamFocus 오답 회독 모드 페이지
 * D-30 체계적 오답 복습 모드
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('/home/moodle/public_html/moodle/config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/omniui/local/examfocus/review_errors.php');
$PAGE->set_title('오답 회독 모드 - ExamFocus');
$PAGE->set_heading('오답 회독 모드');
$PAGE->set_pagelayout('standard');

// 사용자 ID 가져오기
$userid = optional_param('user_id', $USER->id, PARAM_INT);

// 권한 체크
$context = context_system::instance();
if ($userid != $USER->id && !has_capability('moodle/user:viewdetails', $context)) {
    throw new moodle_exception('nopermissions', 'error');
}

// CSS 및 JavaScript 로드
$PAGE->requires->css('/local/augmented_teacher/alt42/omniui/local/examfocus/styles/modes.css');
$PAGE->requires->js_call_amd('local_examfocus/examfocus', 'init', [$userid, '#examfocus-mount']);

echo $OUTPUT->header();

// 사용자 정보
$user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
if (!$user) {
    throw new moodle_exception('usernotfound');
}
$username = fullname($user);

// 활동 로그 기록
$DB->insert_record('abessi_missionlog', [
    'userid' => $userid,
    'page' => 'examfocus_review_errors',
    'timecreated' => time()
]);

// 추천 엔진에서 현재 상태 가져오기
require_once(__DIR__ . '/classes/service/recommendation_engine.php');
$engine = new \local_examfocus\service\recommendation_engine();
$recommendation = $engine->recommend_for_user($userid);

?>

<div id="examfocus-mount"></div>

<div class="examfocus-mode-container">
    <div class="container-fluid">
        <!-- 헤더 -->
        <div class="examfocus-mode-header review-mode">
            <div class="mode-icon">
                <i class="fa fa-redo fa-3x"></i>
            </div>
            <h1>오답 회독 모드</h1>
            <p class="lead"><?php echo $username; ?>님의 체계적 오답 복습</p>
            
            <?php if ($recommendation['has_recommendation'] && $recommendation['mode'] == 'review_errors'): ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>D-<?php echo $recommendation['exam_info']['days_until']; ?></strong>
                <?php echo $recommendation['message']; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 학습 통계 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-danger">24</div>
                    <div class="stat-label">틀린 문제</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-warning">12</div>
                    <div class="stat-label">재복습 필요</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-success">8</div>
                    <div class="stat-label">완료</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-primary">67%</div>
                    <div class="stat-label">개선률</div>
                </div>
            </div>
        </div>
        
        <!-- 취약 단원 분석 -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4><i class="fa fa-chart-bar"></i> 취약 단원 분석</h4>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>함수와 그래프</span>
                        <div>
                            <span class="badge badge-danger">취약</span>
                            <small class="ms-2">5문제 틀림</small>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-danger" style="width: 70%"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>삼각함수</span>
                        <div>
                            <span class="badge badge-warning">주의</span>
                            <small class="ms-2">4문제 틀림</small>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: 60%"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>미분과 적분</span>
                        <div>
                            <span class="badge badge-success">양호</span>
                            <small class="ms-2">2문제 틀림</small>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 30%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 우선순위 오답 문제 -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h4><i class="fa fa-exclamation-triangle"></i> 우선 복습 문제</h4>
            </div>
            <div class="card-body">
                <div class="error-list">
                    <div class="error-item" data-priority="high">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>이차함수의 최댓값 구하기</h6>
                                <small class="text-muted">함수와 그래프 > 이차함수</small>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge badge-danger">High</span>
                                <button class="btn btn-sm btn-primary ms-2 review-btn" data-id="1">
                                    <i class="fa fa-play"></i> 복습
                                </button>
                                <div><small>3회 틀림</small></div>
                            </div>
                        </div>
                        <div class="error-solution mt-2">
                            <strong>핵심 개념:</strong> 이차함수 y = ax² + bx + c의 최댓값은 a < 0일 때 존재하며, x = -b/2a에서 최댓값을 가집니다.
                        </div>
                    </div>
                    
                    <div class="error-item" data-priority="high">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>삼각함수 합성공식</h6>
                                <small class="text-muted">삼각함수 > 삼각함수의 합성</small>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge badge-danger">High</span>
                                <button class="btn btn-sm btn-primary ms-2 review-btn" data-id="2">
                                    <i class="fa fa-play"></i> 복습
                                </button>
                                <div><small>3회 틀림</small></div>
                            </div>
                        </div>
                        <div class="error-solution mt-2">
                            <strong>핵심 개념:</strong> a·sinθ + b·cosθ = √(a² + b²)·sin(θ + α) 형태로 변환하여 최댓값과 최솟값을 구합니다.
                        </div>
                    </div>
                    
                    <div class="error-item" data-priority="medium">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>함수의 연속성</h6>
                                <small class="text-muted">미분 > 함수의 연속성</small>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge badge-warning">Medium</span>
                                <button class="btn btn-sm btn-primary ms-2 review-btn" data-id="3">
                                    <i class="fa fa-play"></i> 복습
                                </button>
                                <div><small>2회 틀림</small></div>
                            </div>
                        </div>
                        <div class="error-solution mt-2">
                            <strong>핵심 개념:</strong> 함수가 x = a에서 연속이려면 lim(x→a) f(x) = f(a)가 성립해야 합니다.
                        </div>
                    </div>
                    
                    <div class="error-item" data-priority="low">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>정적분의 계산</h6>
                                <small class="text-muted">적분 > 정적분</small>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge badge-success">Low</span>
                                <button class="btn btn-sm btn-primary ms-2 review-btn" data-id="4">
                                    <i class="fa fa-play"></i> 복습
                                </button>
                                <div><small>1회 틀림</small></div>
                            </div>
                        </div>
                        <div class="error-solution mt-2">
                            <strong>핵심 개념:</strong> ∫[a,b] f(x)dx = F(b) - F(a), 여기서 F(x)는 f(x)의 부정적분입니다.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 30일 학습 계획 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><i class="fa fa-calendar-alt"></i> 30일 학습 계획</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h5>1주차: 취약 단원 집중</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <input type="checkbox" class="form-check-input me-2">
                                함수와 그래프 완전정복
                            </li>
                            <li class="list-group-item">
                                <input type="checkbox" class="form-check-input me-2">
                                삼각함수 기본기 다지기
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h5>2-3주차: 실전 연습</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <input type="checkbox" class="form-check-input me-2">
                                유형별 문제 풀이
                            </li>
                            <li class="list-group-item">
                                <input type="checkbox" class="form-check-input me-2">
                                시간 단축 연습
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h5>4주차: 최종 점검</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <input type="checkbox" class="form-check-input me-2">
                                모의고사 실전 연습
                            </li>
                            <li class="list-group-item">
                                <input type="checkbox" class="form-check-input me-2">
                                약점 마지막 보완
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h5>목표 달성률</h5>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: 85%">85%</div>
                        </div>
                        <small class="text-muted">정답률 85% 목표</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 추천 액션 -->
        <?php if ($recommendation['has_recommendation'] && $recommendation['mode'] == 'review_errors'): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4><i class="fa fa-tasks"></i> 추천 학습 활동</h4>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($recommendation['actions'] as $action): ?>
                    <li class="list-group-item">
                        <input type="checkbox" class="form-check-input me-2">
                        <?php echo $action; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 액션 버튼 -->
        <div class="row mb-4">
            <div class="col-md-4">
                <button class="btn btn-danger btn-lg btn-block w-100" id="startHighPriority">
                    <i class="fa fa-fire"></i> 고난도 집중
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-warning btn-lg btn-block w-100" id="startSystematic">
                    <i class="fa fa-list"></i> 체계적 복습
                </button>
            </div>
            <div class="col-md-4">
                <a href="<?php echo new moodle_url('/local/augmented_teacher/alt42/omniui/local/examfocus/quickstart.php'); ?>" 
                   class="btn btn-outline-secondary btn-lg btn-block w-100">
                    <i class="fa fa-arrow-left"></i> 돌아가기
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 복습 버튼 이벤트
    document.querySelectorAll('.review-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const problemId = this.dataset.id;
            M.core.notification.addNotification({
                message: `문제 ${problemId}번 복습을 시작합니다.`,
                type: 'info'
            });
            // 실제 구현에서는 문제 페이지로 이동
        });
    });
    
    // 고난도 집중 모드
    document.getElementById('startHighPriority').addEventListener('click', function() {
        const highPriorityProblems = document.querySelectorAll('[data-priority="high"]').length;
        if (highPriorityProblems === 0) {
            M.core.notification.addNotification({
                message: '🎉 고난도 문제가 모두 해결되었습니다!',
                type: 'success'
            });
            return;
        }
        
        if (confirm(`고난도 문제 ${highPriorityProblems}개를 집중적으로 학습하시겠습니까?`)) {
            M.core.notification.addNotification({
                message: '🔥 고난도 집중 모드가 시작됩니다!',
                type: 'warning'
            });
            
            // 고난도 문제만 표시
            document.querySelectorAll('.error-item:not([data-priority="high"])').forEach(item => {
                item.style.display = 'none';
            });
        }
    });
    
    // 체계적 복습 모드
    document.getElementById('startSystematic').addEventListener('click', function() {
        if (confirm('체계적 복습 모드를 시작하시겠습니까?')) {
            M.core.notification.addNotification({
                message: '📚 체계적 복습 모드가 시작됩니다!',
                type: 'info'
            });
            
            // 모든 문제 표시
            document.querySelectorAll('.error-item').forEach(item => {
                item.style.display = 'block';
            });
        }
    });
    
    // 체크박스 진행 상황 저장
    document.querySelectorAll('.form-check-input').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            // 실제 구현에서는 서버에 저장
            console.log('Progress saved');
        });
    });
});
</script>

<?php echo $OUTPUT->footer(); ?>