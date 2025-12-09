<?php
/**
 * ExamFocus 개념요약 모드 페이지
 * D-7 긴급 개념 정리 모드
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('/home/moodle/public_html/moodle/config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/omniui/local/examfocus/concept_summary.php');
$PAGE->set_title('개념요약 모드 - ExamFocus');
$PAGE->set_heading('개념요약 모드');
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
    'page' => 'examfocus_concept_summary',
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
        <div class="examfocus-mode-header concept-mode">
            <div class="mode-icon">
                <i class="fa fa-lightbulb fa-3x"></i>
            </div>
            <h1>개념요약 모드</h1>
            <p class="lead"><?php echo $username; ?>님의 긴급 개념 정리</p>
            
            <?php if ($recommendation['has_recommendation'] && $recommendation['mode'] == 'concept_summary'): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>D-<?php echo $recommendation['exam_info']['days_until']; ?></strong>
                <?php echo $recommendation['message']; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 학습 타이머 -->
        <div class="study-timer card mb-4">
            <div class="card-body text-center">
                <h5><i class="fa fa-clock-o"></i> 집중 학습 시간</h5>
                <div class="timer-display" id="timer">00:00:00</div>
                <small class="text-muted">목표: 2시간 집중 학습</small>
            </div>
        </div>
        
        <!-- 핵심 개념 체크리스트 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><i class="fa fa-list-ul"></i> 핵심 개념 체크리스트</h4>
            </div>
            <div class="card-body">
                <div class="concept-checklist" id="conceptList">
                    <div class="concept-item" data-concept="1">
                        <input type="checkbox" class="concept-check" id="concept1">
                        <label for="concept1">함수의 정의와 기본 성질</label>
                    </div>
                    <div class="concept-item" data-concept="2">
                        <input type="checkbox" class="concept-check" id="concept2">
                        <label for="concept2">이차함수의 그래프와 최댓값/최솟값</label>
                    </div>
                    <div class="concept-item" data-concept="3">
                        <input type="checkbox" class="concept-check" id="concept3">
                        <label for="concept3">삼각함수의 기본 공식</label>
                    </div>
                    <div class="concept-item" data-concept="4">
                        <input type="checkbox" class="concept-check" id="concept4">
                        <label for="concept4">수열과 급수의 개념</label>
                    </div>
                    <div class="concept-item" data-concept="5">
                        <input type="checkbox" class="concept-check" id="concept5">
                        <label for="concept5">미분의 기본 공식과 응용</label>
                    </div>
                    <div class="concept-item" data-concept="6">
                        <input type="checkbox" class="concept-check" id="concept6">
                        <label for="concept6">적분의 기본 공식과 응용</label>
                    </div>
                </div>
                
                <div class="progress mt-4">
                    <div class="progress-bar bg-success" id="progressBar" style="width: 0%"></div>
                </div>
                <div class="text-center mt-2">
                    <span id="progressText">0/6 개념 완료</span>
                </div>
            </div>
        </div>
        
        <!-- 대표 유형 문제 -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h4><i class="fa fa-star"></i> 대표 유형 문제</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="problem-card">
                            <h6>유형 1: 이차함수 최댓값</h6>
                            <p class="text-muted">시험에 가장 자주 나오는 유형</p>
                            <button class="btn btn-primary btn-sm practice-btn" data-type="quadratic">
                                <i class="fa fa-pencil"></i> 문제 풀기
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="problem-card">
                            <h6>유형 2: 삼각함수 그래프</h6>
                            <p class="text-muted">그래프 해석이 핵심</p>
                            <button class="btn btn-primary btn-sm practice-btn" data-type="trigonometry">
                                <i class="fa fa-pencil"></i> 문제 풀기
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="problem-card">
                            <h6>유형 3: 미분 응용</h6>
                            <p class="text-muted">접선의 방정식과 극값</p>
                            <button class="btn btn-primary btn-sm practice-btn" data-type="derivative">
                                <i class="fa fa-pencil"></i> 문제 풀기
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="problem-card">
                            <h6>유형 4: 적분 계산</h6>
                            <p class="text-muted">부정적분과 정적분</p>
                            <button class="btn btn-primary btn-sm practice-btn" data-type="integral">
                                <i class="fa fa-pencil"></i> 문제 풀기
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 추천 액션 -->
        <?php if ($recommendation['has_recommendation'] && $recommendation['mode'] == 'concept_summary'): ?>
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
                <button class="btn btn-success btn-lg btn-block w-100" id="startIntensive">
                    <i class="fa fa-rocket"></i> 집중 모드 시작
                </button>
            </div>
            <div class="col-md-4">
                <a href="<?php echo new moodle_url('/local/augmented_teacher/alt42/omniui/local/examfocus/selectmode.php'); ?>" 
                   class="btn btn-secondary btn-lg btn-block w-100">
                    <i class="fa fa-th"></i> 모드 선택
                </a>
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

<style>
.examfocus-mode-container {
    min-height: calc(100vh - 200px);
    padding: 20px 0;
}

.examfocus-mode-header {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.examfocus-mode-header.concept-mode {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.mode-icon {
    margin-bottom: 20px;
}

.study-timer {
    background: #17a2b8;
    color: white;
}

.timer-display {
    font-size: 2.5em;
    font-weight: bold;
    font-family: 'Courier New', monospace;
}

.concept-checklist {
    max-height: 400px;
    overflow-y: auto;
}

.concept-item {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    transition: background 0.3s;
}

.concept-item:hover {
    background: #f8f9fa;
}

.concept-item:last-child {
    border-bottom: none;
}

.concept-check {
    width: 20px;
    height: 20px;
    margin-right: 10px;
    cursor: pointer;
}

.concept-item label {
    cursor: pointer;
    margin-bottom: 0;
}

.concept-item input:checked + label {
    text-decoration: line-through;
    color: #6c757d;
}

.problem-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    height: 100%;
}

.practice-btn {
    margin-top: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let startTime = Date.now();
    let completedConcepts = 0;
    const totalConcepts = 6;
    
    // 타이머 업데이트
    function updateTimer() {
        const elapsed = Date.now() - startTime;
        const hours = Math.floor(elapsed / (1000 * 60 * 60));
        const minutes = Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((elapsed % (1000 * 60)) / 1000);
        
        document.getElementById('timer').textContent = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    // 진행률 업데이트
    function updateProgress() {
        completedConcepts = document.querySelectorAll('.concept-check:checked').length;
        const percentage = (completedConcepts / totalConcepts) * 100;
        document.getElementById('progressBar').style.width = percentage + '%';
        document.getElementById('progressText').textContent = `${completedConcepts}/${totalConcepts} 개념 완료`;
        
        // 모두 완료 시 알림
        if (completedConcepts === totalConcepts) {
            M.core.notification.addNotification({
                message: '🎉 모든 개념을 완료했습니다!',
                type: 'success'
            });
        }
    }
    
    // 체크박스 이벤트
    document.querySelectorAll('.concept-check').forEach(function(checkbox) {
        checkbox.addEventListener('change', updateProgress);
    });
    
    // 집중 모드 시작
    document.getElementById('startIntensive').addEventListener('click', function() {
        if (completedConcepts === 0) {
            M.core.notification.addNotification({
                message: '먼저 개념을 체크해주세요!',
                type: 'warning'
            });
            return;
        }
        
        M.core.notification.addNotification({
            message: '🔥 집중 모드가 시작됩니다! 2시간 동안 집중해보세요!',
            type: 'success'
        });
        
        // 풀스크린 요청
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        }
    });
    
    // 문제 풀기 버튼
    document.querySelectorAll('.practice-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            M.core.notification.addNotification({
                message: `${type} 유형 문제로 이동합니다.`,
                type: 'info'
            });
            // 실제로는 해당 문제 페이지로 이동
        });
    });
    
    // 1초마다 타이머 업데이트
    setInterval(updateTimer, 1000);
    
    // 초기 진행률 설정
    updateProgress();
});
</script>

<?php echo $OUTPUT->footer(); ?>