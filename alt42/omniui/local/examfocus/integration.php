<?php
/**
 * ExamFocus 통합 스크립트
 * 기존 페이지에 추가할 코드 조각
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 이 코드를 기존 학생 홈페이지나 학습 모드 선택 페이지에 추가하세요

// 1. PHP 부분 (페이지 상단에 추가)
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php');
} else {
    // Moodle config 경로 조정 필요
    require_once('/home/moodle/public_html/moodle/config.php');
}

require_login();

// ExamFocus 서비스 로드
if (file_exists($CFG->dirroot . '/local/examfocus/classes/service/exam_focus_service.php')) {
    require_once($CFG->dirroot . '/local/examfocus/classes/service/exam_focus_service.php');
    
    $examfocus_service = new \local_examfocus\service\exam_focus_service();
    $recommendation = $examfocus_service->get_recommendation_for_user($USER->id);
}

// 2. JavaScript 부분 (페이지 하단에 추가)
?>

<script type="text/javascript">
// ExamFocus 통합
(function() {
    // Moodle AMD 모듈 로드 확인
    if (typeof require !== 'undefined') {
        require(['local_examfocus/examfocus'], function(ExamFocus) {
            // 초기화
            ExamFocus.init(<?php echo $USER->id; ?>, '#page-content');
        });
    } else {
        // Fallback: jQuery 직접 사용
        $(document).ready(function() {
            // PHP에서 전달받은 추천 정보 처리
            <?php if (!empty($recommendation) && $recommendation['has_recommendation']): ?>
            var recommendation = <?php echo json_encode($recommendation); ?>;
            
            // 배너 HTML 생성
            var bannerHtml = `
                <div class="alert alert-${recommendation.priority === 'high' ? 'danger' : 'warning'} examfocus-banner">
                    <h4>📚 시험 대비 학습 모드 추천</h4>
                    <p>${recommendation.message}</p>
                    <p><strong>시험까지 D-${recommendation.days_until}</strong></p>
                    <button class="btn btn-success" onclick="applyExamFocusMode('${recommendation.mode}')">
                        추천 모드로 전환
                    </button>
                    <button class="btn btn-secondary" onclick="dismissExamFocus()">나중에</button>
                </div>
            `;
            
            // 페이지 상단에 배너 추가
            $('#page-content').prepend(bannerHtml);
            <?php endif; ?>
        });
    }
    
    // 전역 함수 정의
    window.applyExamFocusMode = function(mode) {
        // AJAX로 추천 수락 처리
        $.ajax({
            url: '/local/examfocus/ajax/accept.php',
            method: 'POST',
            data: {
                userid: <?php echo $USER->id; ?>,
                mode: mode,
                sesskey: M.cfg.sesskey
            },
            success: function(response) {
                // 성공 메시지
                alert('학습 모드가 변경되었습니다.');
                
                // 페이지 리로드 또는 모드 전환
                location.reload();
            }
        });
    };
    
    window.dismissExamFocus = function() {
        $('.examfocus-banner').fadeOut();
        
        // 쿨다운 설정
        sessionStorage.setItem('examfocus_dismissed', Date.now());
    };
})();
</script>

<!-- 3. CSS 추가 (선택사항) -->
<style>
.examfocus-banner {
    margin: 20px 0;
    padding: 20px;
    border-left: 5px solid #ffc107;
    position: relative;
}

.examfocus-banner.alert-danger {
    border-left-color: #dc3545;
}

.examfocus-banner h4 {
    margin-top: 0;
}

.examfocus-highlight {
    animation: pulse 2s infinite;
    box-shadow: 0 0 20px rgba(255, 193, 7, 0.8);
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>