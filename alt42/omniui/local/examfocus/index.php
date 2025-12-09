<?php
/**
 * ExamFocus 플러그인 인덱스 페이지
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Moodle 설정 파일 찾기
$config_paths = [
    __DIR__ . '/../../../../config.php',
    __DIR__ . '/../../../config.php', 
    __DIR__ . '/../../config.php',
    '/home/moodle/public_html/moodle/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php'
];

$config_found = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $config_found = true;
        break;
    }
}

if (!$config_found) {
    die('Moodle config.php not found. Please check the installation.');
}

require_login();

$PAGE->set_url('/local/examfocus/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_examfocus'));
$PAGE->set_heading(get_string('pluginname', 'local_examfocus'));

// 권한 체크
$context = context_system::instance();
if (!has_capability('local/examfocus:view_recommendations', $context)) {
    print_error('nopermissions', 'error', '', 'view recommendations');
}

echo $OUTPUT->header();

// ExamFocus 서비스 로드
require_once($CFG->dirroot . '/local/examfocus/classes/service/exam_focus_service.php');
$service = new \local_examfocus\service\exam_focus_service();
$recommendation = $service->get_recommendation_for_user($USER->id);

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2><?php echo get_string('pluginname', 'local_examfocus'); ?></h2>
            
            <?php if ($recommendation['has_recommendation']): ?>
            <div class="alert alert-<?php echo ($recommendation['priority'] === 'high') ? 'danger' : 'warning'; ?>">
                <h4><?php echo get_string('recommendation_title', 'local_examfocus'); ?></h4>
                <p><?php echo $recommendation['message']; ?></p>
                <p>
                    <strong><?php echo get_string('days_remaining', 'local_examfocus', $recommendation['days_until']); ?></strong>
                </p>
                <div class="mt-3">
                    <button class="btn btn-success" id="apply-recommendation" data-mode="<?php echo $recommendation['mode']; ?>">
                        <?php echo get_string('apply_recommendation', 'local_examfocus'); ?>
                    </button>
                    <button class="btn btn-secondary" id="dismiss-recommendation">
                        <?php echo get_string('dismiss', 'local_examfocus'); ?>
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <?php 
                if ($recommendation['reason'] === 'no_exam_scheduled') {
                    echo get_string('no_exam_scheduled', 'local_examfocus');
                } elseif ($recommendation['reason'] === 'disabled_by_user') {
                    echo get_string('feature_disabled', 'local_examfocus');
                } else {
                    echo '현재 추천할 학습 모드가 없습니다.';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>설정</h5>
                </div>
                <div class="card-body">
                    <p>시험 대비 자동 학습 모드 전환 기능을 설정하려면 관리자 페이지를 이용하세요.</p>
                    <?php if (has_capability('local/examfocus:manage_settings', $context)): ?>
                    <a href="<?php echo $CFG->wwwroot; ?>/admin/settings.php?section=local_examfocus" class="btn btn-primary">
                        설정 페이지로 이동
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
require(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    $('#apply-recommendation').on('click', function() {
        var mode = $(this).data('mode');
        var button = $(this);
        button.prop('disabled', true);
        
        Ajax.call([{
            methodname: 'local_examfocus_accept_recommendation',
            args: {
                userid: <?php echo $USER->id; ?>,
                mode: mode
            }
        }])[0].done(function(response) {
            Notification.addNotification({
                message: '학습 모드가 변경되었습니다.',
                type: 'success'
            });
            setTimeout(function() {
                location.reload();
            }, 2000);
        }).fail(function(error) {
            button.prop('disabled', false);
            Notification.exception(error);
        });
    });
    
    $('#dismiss-recommendation').on('click', function() {
        Ajax.call([{
            methodname: 'local_examfocus_dismiss_recommendation',
            args: {
                userid: <?php echo $USER->id; ?>
            }
        }])[0].done(function() {
            location.reload();
        });
    });
});
</script>

<?php
echo $OUTPUT->footer();