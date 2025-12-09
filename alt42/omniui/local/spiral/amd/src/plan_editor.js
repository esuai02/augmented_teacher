/**
 * Spiral Plan Editor JavaScript Module
 * 
 * @module     local_spiral/plan_editor
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/templates', 'jquery'], 
function(Ajax, Notification, Templates, $) {
    
    var currentScheduleId = null;
    var pendingChanges = [];
    var draggedElement = null;
    
    /**
     * Initialize the plan editor
     */
    var init = function() {
        // 폼 제출 이벤트
        $('#spiral-generate-form').on('submit', function(e) {
            e.preventDefault();
            generateSchedule();
        });
        
        // 발행 버튼
        $(document).on('click', '#publish-btn', publishSchedule);
        
        // 드래그앤드롭 초기화
        initDragDrop();
        
        // 변경사항 저장
        $(document).on('click', '#save-changes-btn', saveChanges);
        
        // 충돌 해결
        $(document).on('click', '.resolve-btn', resolveConflict);
    };
    
    /**
     * Generate new schedule
     */
    var generateSchedule = function() {
        var formData = $('#spiral-generate-form').serializeArray();
        var params = {};
        
        // FormData를 객체로 변환
        $.each(formData, function(i, field) {
            params[field.name] = field.value;
        });
        
        // 비율 계산
        params.alpha = parseInt($('#alpha_slider').val()) / 100;
        params.beta = 1 - params.alpha;
        
        // CSRF 토큰 추가
        params.sesskey = M.cfg.sesskey;
        
        // 로딩 표시
        $('#generate-btn').prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> 생성 중...');
        
        // AJAX 요청
        $.ajax({
            url: '/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php',
            method: 'POST',
            data: JSON.stringify(params),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.ok) {
                    currentScheduleId = response.schedule_id;
                    displaySchedule(response);
                    
                    Notification.addNotification({
                        message: '스케줄이 성공적으로 생성되었습니다.',
                        type: 'success'
                    });
                } else {
                    Notification.addNotification({
                        message: '스케줄 생성 실패: ' + (response.error || '알 수 없는 오류'),
                        type: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                Notification.addNotification({
                    message: '서버 오류가 발생했습니다.',
                    type: 'error'
                });
                console.error('Generate error:', error);
            },
            complete: function() {
                $('#generate-btn').prop('disabled', false)
                    .html('<i class="fa fa-magic"></i> 자동 편성');
            }
        });
    };
    
    /**
     * Display generated schedule
     */
    var displaySchedule = function(data) {
        // 테스트 데이터 (실제로는 서버에서 받은 데이터 사용)
        var templateData = {
            student_name: $('#student_id option:selected').text(),
            total_sessions: data.summary?.total_sessions || 0,
            total_hours: data.summary?.total_hours || 0,
            preview_ratio: Math.round((data.summary?.preview_ratio || 0.7) * 100),
            review_ratio: Math.round((data.summary?.review_ratio || 0.3) * 100),
            has_conflicts: (data.conflicts && data.conflicts.length > 0),
            conflict_count: data.conflicts?.length || 0,
            weeks: generateWeeksData(data),
            conflicts: data.conflicts || []
        };
        
        // 템플릿 렌더링
        Templates.render('local_spiral/plan_editor', templateData)
            .then(function(html) {
                $('#schedule-content').html(html);
                $('#schedule-display').removeClass('d-none');
                
                // 드래그앤드롭 재초기화
                initDragDrop();
            })
            .catch(function(error) {
                console.error('Template error:', error);
                // 폴백: 간단한 HTML 표시
                $('#schedule-content').html(
                    '<div class="alert alert-info">스케줄이 생성되었습니다. (ID: ' + 
                    currentScheduleId + ')</div>'
                );
                $('#schedule-display').removeClass('d-none');
            });
    };
    
    /**
     * Generate weeks data for template
     */
    var generateWeeksData = function(data) {
        // 간단한 더미 데이터 생성 (실제로는 서버 데이터 파싱)
        var weeks = [];
        var startDate = new Date($('#start_date').val());
        var endDate = new Date($('#end_date').val());
        
        var currentDate = new Date(startDate);
        var weekNum = 1;
        
        while (currentDate <= endDate) {
            var week = {
                week_number: weekNum,
                week_label: weekNum + '주차',
                days: []
            };
            
            for (var i = 0; i < 7; i++) {
                if (currentDate > endDate) break;
                
                var dayData = {
                    date: formatDate(currentDate),
                    day_label: getDayLabel(currentDate.getDay()),
                    date_formatted: (currentDate.getMonth() + 1) + '/' + currentDate.getDate(),
                    sessions: [],
                    day_total_minutes: 0
                };
                
                // 예제 세션 추가 (실제로는 서버 데이터 사용)
                if (currentDate.getDay() !== 0) { // 일요일 제외
                    dayData.sessions.push({
                        id: Math.floor(Math.random() * 1000),
                        time: '19:00',
                        duration: 40,
                        unit_name: '수학 단원 ' + (i + 1),
                        session_type: Math.random() > 0.3 ? 'preview' : 'review',
                        is_preview: Math.random() > 0.3,
                        is_review: Math.random() <= 0.3,
                        difficulty_stars: Array(Math.floor(Math.random() * 5) + 1).fill(1),
                        has_conflict: Math.random() > 0.9
                    });
                    dayData.day_total_minutes = 40;
                }
                
                week.days.push(dayData);
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            weeks.push(week);
            weekNum++;
        }
        
        return weeks;
    };
    
    /**
     * Initialize drag and drop
     */
    var initDragDrop = function() {
        // 드래그 가능 요소
        $('.session-card').on('dragstart', function(e) {
            draggedElement = this;
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        $('.session-card').on('dragend', function(e) {
            $(this).removeClass('dragging');
            draggedElement = null;
        });
        
        // 드롭 영역
        $('.droppable').on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('drag-over');
        });
        
        $('.droppable').on('dragleave', function(e) {
            $(this).removeClass('drag-over');
        });
        
        $('.droppable').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            if (draggedElement && draggedElement !== this) {
                var sessionId = $(draggedElement).data('session-id');
                var newDate = $(this).data('date');
                
                // 이동 기록
                pendingChanges.push({
                    session_id: sessionId,
                    action: 'move',
                    new_date: newDate
                });
                
                // DOM 업데이트
                $(this).append(draggedElement);
                
                // 변경사항 표시
                showPendingChanges();
            }
        });
    };
    
    /**
     * Save pending changes
     */
    var saveChanges = function() {
        if (pendingChanges.length === 0) {
            Notification.addNotification({
                message: '저장할 변경사항이 없습니다.',
                type: 'info'
            });
            return;
        }
        
        $.ajax({
            url: '/local/augmented_teacher/alt42/omniui/spiral/api/ajax_modify_schedule.php',
            method: 'POST',
            data: JSON.stringify({
                schedule_id: currentScheduleId,
                changes: pendingChanges,
                sesskey: M.cfg.sesskey
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.ok) {
                    Notification.addNotification({
                        message: response.applied + '개의 변경사항이 저장되었습니다.',
                        type: 'success'
                    });
                    pendingChanges = [];
                    hidePendingChanges();
                } else {
                    Notification.addNotification({
                        message: '저장 실패: ' + (response.error || '알 수 없는 오류'),
                        type: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                Notification.addNotification({
                    message: '서버 오류가 발생했습니다.',
                    type: 'error'
                });
            }
        });
    };
    
    /**
     * Publish schedule
     */
    var publishSchedule = function() {
        if (!currentScheduleId) {
            Notification.addNotification({
                message: '발행할 스케줄이 없습니다.',
                type: 'warning'
            });
            return;
        }
        
        if (confirm('스케줄을 발행하시겠습니까? 학생에게 알림이 전송됩니다.')) {
            $.ajax({
                url: '/local/augmented_teacher/alt42/omniui/spiral/api/ajax_publish_schedule.php',
                method: 'POST',
                data: JSON.stringify({
                    schedule_id: currentScheduleId,
                    notify: true,
                    sesskey: M.cfg.sesskey
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.ok) {
                        Notification.addNotification({
                            message: '스케줄이 성공적으로 발행되었습니다.',
                            type: 'success'
                        });
                        $('#publish-btn').prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .html('<i class="fa fa-check"></i> 발행됨');
                    } else {
                        Notification.addNotification({
                            message: '발행 실패: ' + (response.error || '알 수 없는 오류'),
                            type: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Notification.addNotification({
                        message: '서버 오류가 발생했습니다.',
                        type: 'error'
                    });
                }
            });
        }
    };
    
    /**
     * Resolve conflict
     */
    var resolveConflict = function() {
        var conflictId = $(this).data('conflict-id');
        // TODO: 충돌 해결 로직 구현
        Notification.addNotification({
            message: '충돌 해결 기능은 준비 중입니다.',
            type: 'info'
        });
    };
    
    /**
     * Show pending changes indicator
     */
    var showPendingChanges = function() {
        if (pendingChanges.length > 0) {
            $('#save-changes-btn').addClass('btn-warning')
                .html('<i class="fa fa-save"></i> 변경사항 저장 (' + pendingChanges.length + ')');
        }
    };
    
    /**
     * Hide pending changes indicator
     */
    var hidePendingChanges = function() {
        $('#save-changes-btn').removeClass('btn-warning')
            .html('<i class="fa fa-save"></i> 변경사항 저장');
    };
    
    /**
     * Format date
     */
    var formatDate = function(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    
    /**
     * Get day label
     */
    var getDayLabel = function(dayNum) {
        var days = ['일', '월', '화', '수', '목', '금', '토'];
        return days[dayNum];
    };
    
    return {
        init: init
    };
});