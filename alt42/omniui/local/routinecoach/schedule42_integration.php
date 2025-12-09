<?php
/**
 * Integration snippet for schedule42.php
 * 
 * Add this code to students/schedule42.php to enable automatic exam detection
 * and Routine Coach integration.
 * 
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 */

// ============================================================================
// STEP 1: Add after require_login() and config includes
// ============================================================================

// Check if Routine Coach is installed
$routinecoach_enabled = false;
if (file_exists($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php')) {
    require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');
    $routinecoach_enabled = true;
}

// ============================================================================
// STEP 2: Add after schedule save/update logic
// ============================================================================

if ($routinecoach_enabled && isset($_POST['save_schedule'])) {
    // Get the schedule that was just saved
    $scheduleid = $_POST['scheduleid'] ?? null;
    
    if ($scheduleid) {
        try {
            // Get schedule details
            $schedule = $DB->get_record('abessi_schedule', ['id' => $scheduleid]);
            
            if ($schedule) {
                // Check if this is an exam-related schedule
                $is_exam_schedule = false;
                $exam_info = null;
                
                // Check condition 1: pinned = 1 (latest pinned schedule)
                if ($schedule->pinned == 1) {
                    $is_exam_schedule = true;
                }
                
                // Check condition 2: type is 임시 or 특강 with date
                if (($schedule->type === '임시' || $schedule->type === '특강') && !empty($schedule->date)) {
                    $is_exam_schedule = true;
                }
                
                // Process if exam schedule detected
                if ($is_exam_schedule) {
                    // Extract exam information from memo
                    $exam_info = extract_exam_from_schedule($schedule);
                    
                    if ($exam_info) {
                        // Call Routine Coach service
                        $service = new \local_routinecoach\service\routine_service();
                        $examid = $service->on_exam_saved(
                            $USER->id,
                            $exam_info['examdate'],
                            $scheduleid,
                            $exam_info['label']
                        );
                        
                        if ($examid) {
                            // Show success notification
                            echo '<script>
                                $(document).ready(function() {
                                    showNotification("시험 일정이 자동으로 등록되었습니다: ' . $exam_info['label'] . '", "success");
                                });
                            </script>';
                        }
                    } else {
                        // Show popup for manual registration
                        echo '<script>
                            $(document).ready(function() {
                                showExamRegistrationPopup(' . $scheduleid . ');
                            });
                        </script>';
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Routine Coach integration error: ' . $e->getMessage());
        }
    }
}

// ============================================================================
// STEP 3: Helper function to extract exam info
// ============================================================================

function extract_exam_from_schedule($schedule) {
    $memo = $schedule->memo ?? '';
    $date = $schedule->date ?? null;
    
    // Exam type patterns
    $exam_types = [
        '중간고사', '기말고사', '모의고사', 
        '월말평가', '단원평가', '수행평가'
    ];
    
    $exam_info = null;
    
    // Check for exam keywords in memo
    foreach ($exam_types as $type) {
        if (stripos($memo, $type) !== false) {
            $exam_info = ['type' => $type];
            break;
        }
    }
    
    // Extract date
    if ($exam_info) {
        // Priority 1: Use schedule date field
        if ($date) {
            $exam_info['examdate'] = strtotime($date);
        } else {
            // Priority 2: Extract from memo
            // Pattern: YYYY-MM-DD or YYYY/MM/DD or YYYY년 MM월 DD일
            if (preg_match('/(\d{4})[-\/년]\s*(\d{1,2})[-\/월]\s*(\d{1,2})[일]?/', $memo, $matches)) {
                $exam_info['examdate'] = strtotime($matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            }
            // Pattern: MM/DD or MM-DD (assume current or next year)
            elseif (preg_match('/(\d{1,2})[-\/](\d{1,2})/', $memo, $matches)) {
                $year = date('Y');
                $month = $matches[1];
                if ($month < date('n')) {
                    $year++; // Next year if month has passed
                }
                $exam_info['examdate'] = strtotime($year . '-' . $month . '-' . $matches[2]);
            }
        }
        
        // Generate label
        if (isset($exam_info['examdate'])) {
            $month = date('n', $exam_info['examdate']);
            $exam_info['label'] = $month . '월 ' . $exam_info['type'];
        }
    }
    
    // Validate
    if ($exam_info && isset($exam_info['examdate']) && isset($exam_info['label'])) {
        return $exam_info;
    }
    
    return null;
}

// ============================================================================
// STEP 4: Add JavaScript for popup and notifications (add to page footer)
// ============================================================================
?>

<!-- Exam Registration Popup HTML -->
<div id="exam-registration-popup" style="display: none; position: fixed; top: 50%; left: 50%; 
     transform: translate(-50%, -50%); background: white; padding: 20px; width: 400px;
     border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 10000;">
    <h3 style="margin-top: 0;">시험 정보 등록</h3>
    <p>스케줄에서 시험 정보를 자동으로 감지할 수 없습니다.<br>
       수동으로 시험 정보를 등록해주세요.</p>
    
    <form id="exam-registration-form">
        <input type="hidden" id="exam-scheduleid" value="">
        
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px;">시험 이름:</label>
            <input type="text" id="exam-label" placeholder="예: 3월 중간고사" required 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px;">시험 날짜:</label>
            <input type="date" id="exam-date" required 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px;">시험 유형:</label>
            <select id="exam-type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="중간고사">중간고사</option>
                <option value="기말고사">기말고사</option>
                <option value="모의고사">모의고사</option>
                <option value="월말평가">월말평가</option>
                <option value="단원평가">단원평가</option>
                <option value="수행평가">수행평가</option>
            </select>
        </div>
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" onclick="closeExamPopup()" 
                    style="padding: 8px 20px; margin-right: 10px; background: #f0f0f0; 
                           border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">취소</button>
            <button type="submit" 
                    style="padding: 8px 20px; background: #667eea; color: white; 
                           border: none; border-radius: 4px; cursor: pointer;">등록</button>
        </div>
    </form>
</div>

<!-- Overlay -->
<div id="exam-popup-overlay" onclick="closeExamPopup()" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); z-index: 9999;"></div>

<!-- JavaScript for popup and exam registration -->
<script>
// Show exam registration popup
function showExamRegistrationPopup(scheduleid) {
    document.getElementById('exam-scheduleid').value = scheduleid || '';
    document.getElementById('exam-registration-popup').style.display = 'block';
    document.getElementById('exam-popup-overlay').style.display = 'block';
}

// Close popup
function closeExamPopup() {
    document.getElementById('exam-registration-popup').style.display = 'none';
    document.getElementById('exam-popup-overlay').style.display = 'none';
}

// Show notification
function showNotification(message, type) {
    type = type || 'info';
    var bgColor = type === 'success' ? '#4CAF50' : '#2196F3';
    
    var notification = document.createElement('div');
    notification.innerHTML = message;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 20px; ' +
                                 'background: ' + bgColor + '; color: white; border-radius: 4px; ' +
                                 'box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 10001; ' +
                                 'animation: slideIn 0.3s ease;';
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(function() {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Handle exam registration form submission
document.getElementById('exam-registration-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var label = document.getElementById('exam-label').value;
    var date = document.getElementById('exam-date').value;
    var type = document.getElementById('exam-type').value;
    var scheduleid = document.getElementById('exam-scheduleid').value;
    
    // AJAX call to register exam
    <?php if ($routinecoach_enabled): ?>
    $.ajax({
        url: '<?php echo $CFG->wwwroot; ?>/local/routinecoach/ajax/register_exam.php',
        type: 'POST',
        data: {
            userid: <?php echo $USER->id; ?>,
            label: label,
            date: date,
            type: type,
            sesskey: M.cfg.sesskey
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('시험이 성공적으로 등록되었습니다: ' + label, 'success');
                closeExamPopup();
                
                // Optionally refresh the page or update UI
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert('시험 등록 실패: ' + response.message);
            }
        },
        error: function() {
            alert('서버 오류가 발생했습니다.');
        }
    });
    <?php else: ?>
    alert('Routine Coach 플러그인이 설치되어 있지 않습니다.');
    <?php endif; ?>
});

// CSS animation
if (!document.getElementById('notification-styles')) {
    var style = document.createElement('style');
    style.id = 'notification-styles';
    style.innerHTML = '@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }';
    document.head.appendChild(style);
}
</script>