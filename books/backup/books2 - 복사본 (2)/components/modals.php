<?php
/**
 * 모달 관리 컴포넌트
 * SweetAlert2와 Bootstrap 5 모달을 통합 관리
 */

/**
 * 모달 설정 기본값
 */
class ModalConfig {
    public static $defaults = [
        'confirmButtonColor' => '#007bff',
        'cancelButtonColor' => '#6c757d',
        'confirmButtonText' => '확인',
        'cancelButtonText' => '취소',
        'allowOutsideClick' => true,
        'allowEscapeKey' => true,
        'showCloseButton' => true,
        'animation' => true,
        'customClass' => [
            'container' => 'modal-sweetalert-container',
            'popup' => 'modal-sweetalert-popup',
            'header' => 'modal-sweetalert-header',
            'content' => 'modal-sweetalert-content'
        ]
    ];
}

/**
 * 콜백 모달 렌더링 함수
 * 
 * @param array $options 모달 옵션
 * @return string JavaScript 코드
 */
function renderCallbackModal($options = []) {
    $defaults = [
        'studentId' => 0,
        'chapterId' => 0,
        'showCustomTime' => true,
        'timeOptions' => [5, 10, 15, 30, 60],
        'maxCustomTime' => 480
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <script>
    function showCallbackModal() {
        const timeOptions = <?php echo json_encode($options['timeOptions']); ?>;
        const maxCustomTime = <?php echo $options['maxCustomTime']; ?>;
        
        let timeButtonsHtml = '';
        timeOptions.forEach(time => {
            const label = time < 60 ? `${time}분` : `${time/60}시간`;
            timeButtonsHtml += `
                <button class="btn btn-primary m-1" onclick="saveCallback(${time}, '${label} 후 복귀')">
                    ${label}
                </button>
            `;
        });
        
        Swal.fire({
            title: '콜백 시간 설정',
            html: `
                <div class="callback-modal-content">
                    <p class="mb-3">언제 다시 돌아오시겠습니까?</p>
                    <div class="time-buttons d-flex flex-wrap justify-content-center">
                        ${timeButtonsHtml}
                    </div>
                    <?php if ($options['showCustomTime']): ?>
                    <div class="custom-time-input mt-4">
                        <div class="input-group">
                            <input type="number" 
                                   id="customCallbackTime" 
                                   class="form-control" 
                                   placeholder="직접 입력" 
                                   min="1" 
                                   max="${maxCustomTime}"
                                   aria-label="콜백 시간 (분)">
                            <span class="input-group-text">분</span>
                            <button class="btn btn-outline-primary" 
                                    type="button"
                                    onclick="saveCustomCallback()">
                                설정
                            </button>
                        </div>
                        <small class="text-muted">1분 ~ ${maxCustomTime}분 사이로 입력하세요</small>
                    </div>
                    <?php endif; ?>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: '취소',
            width: '500px',
            ...<?php echo json_encode(ModalConfig::$defaults); ?>
        });
    }
    
    function saveCallback(minutes, description) {
        ChapterModule.saveCallbackGeneral(minutes, description);
        Swal.close();
    }
    
    function saveCustomCallback() {
        const customTime = document.getElementById('customCallbackTime').value;
        if (customTime && customTime > 0 && customTime <= <?php echo $options['maxCustomTime']; ?>) {
            saveCallback(parseInt(customTime), `${customTime}분 후 복귀`);
        } else {
            Swal.fire({
                icon: 'warning',
                title: '알림',
                text: `1분에서 <?php echo $options['maxCustomTime']; ?>분 사이의 값을 입력해주세요.`,
                ...<?php echo json_encode(ModalConfig::$defaults); ?>
            });
        }
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * 목표 설정 모달 렌더링 함수
 * 
 * @return string JavaScript 코드
 */
function renderGoalModal() {
    ob_start();
    ?>
    <script>
    function showGoalModal() {
        Swal.fire({
            title: '학습 목표 설정',
            html: `
                <div class="goal-modal-content">
                    <p class="mb-4">오늘의 학습 목표를 설정하세요</p>
                    <div class="goal-options">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-success btn-lg" onclick="setGoalLevel('A')">
                                <i class="fas fa-trophy"></i> 100% 완주 목표
                            </button>
                            <button class="btn btn-outline-primary btn-lg" onclick="setGoalLevel('B')">
                                <i class="fas fa-star"></i> 75% 달성 목표
                            </button>
                            <button class="btn btn-outline-info btn-lg" onclick="setGoalLevel('C')">
                                <i class="fas fa-check"></i> 50% 진행 목표
                            </button>
                            <button class="btn btn-outline-warning btn-lg" onclick="setGoalLevel('D')">
                                <i class="fas fa-play"></i> 25% 시작 목표
                            </button>
                        </div>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: '닫기',
            width: '400px',
            ...<?php echo json_encode(ModalConfig::$defaults); ?>
        });
    }
    
    function setGoalLevel(level) {
        ChapterModule.setGoal(level);
        Swal.close();
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * 진행상황 확인 모달 렌더링 함수
 * 
 * @param float $progress 현재 진행률
 * @param array $stats 통계 정보
 * @return string JavaScript 코드
 */
function renderProgressModal($progress = 0, $stats = []) {
    $defaultStats = [
        'completed' => 0,
        'total' => 0,
        'timeSpent' => 0,
        'lastAccess' => null
    ];
    
    $stats = array_merge($defaultStats, $stats);
    
    ob_start();
    ?>
    <script>
    function showProgressModal() {
        const progress = <?php echo $progress; ?>;
        const stats = <?php echo json_encode($stats); ?>;
        
        let progressClass = 'bg-danger';
        let progressIcon = 'fa-battery-quarter';
        
        if (progress >= 80) {
            progressClass = 'bg-success';
            progressIcon = 'fa-battery-full';
        } else if (progress >= 60) {
            progressClass = 'bg-primary';
            progressIcon = 'fa-battery-three-quarters';
        } else if (progress >= 40) {
            progressClass = 'bg-info';
            progressIcon = 'fa-battery-half';
        } else if (progress >= 20) {
            progressClass = 'bg-warning';
            progressIcon = 'fa-battery-quarter';
        }
        
        Swal.fire({
            title: '학습 진행 상황',
            html: `
                <div class="progress-modal-content">
                    <div class="text-center mb-4">
                        <i class="fas ${progressIcon} fa-3x text-primary"></i>
                    </div>
                    
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar ${progressClass} progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: ${progress}%;"
                             aria-valuenow="${progress}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            ${progress.toFixed(1)}%
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="stat-card p-3 border rounded">
                                    <i class="fas fa-check-circle text-success mb-2"></i>
                                    <h5>${stats.completed}</h5>
                                    <small class="text-muted">완료한 토픽</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-card p-3 border rounded">
                                    <i class="fas fa-list text-info mb-2"></i>
                                    <h5>${stats.total}</h5>
                                    <small class="text-muted">전체 토픽</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card p-3 border rounded">
                                    <i class="fas fa-clock text-warning mb-2"></i>
                                    <h5>${Math.floor(stats.timeSpent / 60)}분</h5>
                                    <small class="text-muted">학습 시간</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card p-3 border rounded">
                                    <i class="fas fa-calendar text-primary mb-2"></i>
                                    <h5>${stats.lastAccess || '오늘'}</h5>
                                    <small class="text-muted">마지막 접속</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <p class="mb-0 text-muted">
                            ${progress < 100 ? '계속 진행하여 목표를 달성하세요!' : '축하합니다! 모든 학습을 완료했습니다!'}
                        </p>
                    </div>
                </div>
            `,
            icon: progress >= 100 ? 'success' : 'info',
            confirmButtonText: '확인',
            width: '500px',
            ...<?php echo json_encode(ModalConfig::$defaults); ?>
        });
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * 복습 추가 모달 렌더링 함수
 * 
 * @return string JavaScript 코드
 */
function renderReviewModal() {
    ob_start();
    ?>
    <script>
    function showReviewModal() {
        Swal.fire({
            title: '복습 항목 추가',
            html: `
                <div class="review-modal-content">
                    <p class="mb-3">복습이 필요한 항목을 선택하세요</p>
                    <div class="form-group">
                        <label for="reviewTopic" class="form-label">토픽 선택</label>
                        <select id="reviewTopic" class="form-select mb-3">
                            <option value="">토픽을 선택하세요</option>
                            <!-- 동적으로 토픽 목록 로드 -->
                        </select>
                        
                        <label for="reviewNote" class="form-label">메모 (선택사항)</label>
                        <textarea id="reviewNote" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="복습할 내용에 대한 메모를 작성하세요"></textarea>
                        
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="reviewReminder">
                            <label class="form-check-label" for="reviewReminder">
                                알림 설정
                            </label>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: '추가',
            showCancelButton: true,
            cancelButtonText: '취소',
            width: '400px',
            preConfirm: () => {
                const topic = document.getElementById('reviewTopic').value;
                const note = document.getElementById('reviewNote').value;
                const reminder = document.getElementById('reviewReminder').checked;
                
                if (!topic) {
                    Swal.showValidationMessage('토픽을 선택해주세요');
                    return false;
                }
                
                return { topic, note, reminder };
            },
            ...<?php echo json_encode(ModalConfig::$defaults); ?>
        }).then((result) => {
            if (result.isConfirmed) {
                ChapterModule.addReview(JSON.stringify(result.value));
            }
        });
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * 확인 모달 렌더링 함수 (범용)
 * 
 * @param string $title 모달 제목
 * @param string $message 모달 메시지
 * @param array $options 추가 옵션
 * @return string JavaScript 코드
 */
function renderConfirmModal($title, $message, $options = []) {
    $defaults = [
        'icon' => 'question',
        'confirmText' => '확인',
        'cancelText' => '취소',
        'confirmCallback' => '',
        'cancelCallback' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <script>
    function showConfirmModal() {
        Swal.fire({
            title: '<?php echo addslashes($title); ?>',
            text: '<?php echo addslashes($message); ?>',
            icon: '<?php echo $options['icon']; ?>',
            showCancelButton: true,
            confirmButtonText: '<?php echo addslashes($options['confirmText']); ?>',
            cancelButtonText: '<?php echo addslashes($options['cancelText']); ?>',
            ...<?php echo json_encode(ModalConfig::$defaults); ?>
        }).then((result) => {
            if (result.isConfirmed) {
                <?php if ($options['confirmCallback']): ?>
                <?php echo $options['confirmCallback']; ?>
                <?php endif; ?>
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                <?php if ($options['cancelCallback']): ?>
                <?php echo $options['cancelCallback']; ?>
                <?php endif; ?>
            }
        });
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * 모달 스타일 CSS
 * 
 * @return string CSS 코드
 */
function getModalStyles() {
    ob_start();
    ?>
    <style>
    /* SweetAlert2 커스텀 스타일 */
    .modal-sweetalert-container {
        z-index: 10000 !important;
    }
    
    .modal-sweetalert-popup {
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    
    .modal-sweetalert-header {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 15px;
    }
    
    .modal-sweetalert-content {
        padding: 20px;
    }
    
    /* 콜백 모달 스타일 */
    .callback-modal-content .time-buttons {
        gap: 10px;
    }
    
    .callback-modal-content .btn {
        min-width: 80px;
    }
    
    .custom-time-input {
        border-top: 1px solid #e9ecef;
        padding-top: 15px;
    }
    
    /* 목표 모달 스타일 */
    .goal-modal-content .btn {
        text-align: left;
        padding: 15px;
        transition: all 0.3s ease;
    }
    
    .goal-modal-content .btn:hover {
        transform: translateX(5px);
    }
    
    .goal-modal-content .fas {
        width: 30px;
        text-align: center;
    }
    
    /* 진행상황 모달 스타일 */
    .progress-modal-content .stat-card {
        background: #f8f9fa;
        transition: all 0.3s ease;
    }
    
    .progress-modal-content .stat-card:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    .progress-modal-content .fas {
        font-size: 24px;
    }
    
    /* 복습 모달 스타일 */
    .review-modal-content .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    
    /* 반응형 디자인 */
    @media (max-width: 576px) {
        .swal2-popup {
            width: 90% !important;
            margin: 10px;
        }
        
        .goal-modal-content .btn {
            font-size: 14px;
            padding: 12px;
        }
        
        .progress-modal-content .col-6 {
            font-size: 14px;
        }
    }
    
    /* 애니메이션 */
    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .swal2-show {
        animation: modalSlideIn 0.3s ease-out;
    }
    </style>
    <?php
    
    return ob_get_clean();
}

/**
 * 모달 초기화 스크립트
 * 
 * @return string JavaScript 코드
 */
function initModalScript() {
    ob_start();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // SweetAlert2 기본 설정 적용
        if (typeof Swal !== 'undefined') {
            // 기본 설정 병합
            const defaultOptions = <?php echo json_encode(ModalConfig::$defaults); ?>;
            
            // SweetAlert2 mixin 생성
            const ChapterModal = Swal.mixin(defaultOptions);
            
            // 전역으로 사용 가능하도록 설정
            window.ChapterModal = ChapterModal;
        }
        
        // ESC 키 처리
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && Swal.isVisible()) {
                const allowEscape = Swal.getPopup().querySelector('.swal2-cancel');
                if (allowEscape) {
                    Swal.close();
                }
            }
        });
        
        // 모달 접근성 개선
        const improveModalAccessibility = () => {
            const modal = Swal.getPopup();
            if (modal) {
                // ARIA 속성 추가
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                
                const title = modal.querySelector('.swal2-title');
                if (title) {
                    modal.setAttribute('aria-labelledby', 'swal2-title');
                }
                
                const content = modal.querySelector('.swal2-content');
                if (content) {
                    modal.setAttribute('aria-describedby', 'swal2-content');
                }
                
                // 포커스 트랩 설정
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                }
            }
        };
        
        // SweetAlert2 이벤트 리스너
        if (typeof Swal !== 'undefined') {
            // 모달이 열릴 때
            document.addEventListener('swal2:open', improveModalAccessibility);
        }
    });
    </script>
    <?php
    
    return ob_get_clean();
}
?>