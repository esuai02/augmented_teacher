<?php
header('Content-Type: text/html; charset=utf-8');

// Moodle 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE;
require_login();

// 임베드 모드 파라미터 처리
$embed = optional_param('embed', 0, PARAM_BOOL);
if ($embed) {
    $PAGE->set_pagelayout('embedded');
    $PAGE->set_cacheable(false);
}

// 학생 정보 가져오기 
$userid = optional_param('userid', 0, PARAM_INT);
$studentid = $userid ? $userid : $USER->id;

// 목표 타입 가져오기 (index.php에서 전달되는 type 매개변수)
$goal_type = optional_param('type', '', PARAM_TEXT);

// 학생 정보 조회
$student = $DB->get_record('user', array('id' => $studentid));
$studentName = $student ? $student->firstname . ' ' . $student->lastname : '학생';

// 시간 표시 함수
function getTimeAgo($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return '방금';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . '분 전';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . '시간 전';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . '일 전';
    } else {
        $weeks = floor($diff / 604800);
        return $weeks . '주 전';
    }
}

// 시간 계산
$now = time();
$today_start = strtotime('today 00:00:00');
$halfdayago = $now - (12 * 60 * 60);
$wtimestart1 = strtotime('monday this week 00:00:00');

// 분기 목표
$termplan = $DB->get_record_sql("SELECT id, deadline, memo, dreamchallenge, dreamtext, dreamurl 
                                  FROM mdl_abessi_progress 
                                  WHERE userid='$studentid' AND plantype='분기목표' AND hide=0 
                                  ORDER BY id DESC LIMIT 1");

// 주간 목표
$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
                                   WHERE userid='$studentid' AND timecreated>'$wtimestart1' 
                                   AND type LIKE '주간목표' 
                                   ORDER BY id DESC LIMIT 1");

// 오늘 목표
$todayGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
                                  WHERE userid='$studentid' 
                                  AND (type LIKE '오늘목표' OR type LIKE '검사요청') 
                                  AND timecreated>'$halfdayago' 
                                  ORDER BY id DESC LIMIT 1");

// 목표 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = optional_param('action', '', PARAM_TEXT);
    $goal_text = optional_param('goal_text', '', PARAM_TEXT);
    
    if ($action === 'save_term_goal') {
        // 분기 목표 저장
        $record = new stdClass();
        $record->userid = $studentid;
        $record->plantype = '분기목표';
        $record->memo = $goal_text;
        $record->deadline = time() + (90 * 24 * 60 * 60); // 90일 후
        $record->timecreated = time();
        $record->hide = 0;
        
        $DB->insert_record('abessi_progress', $record);
        echo json_encode(['success' => true]);
        exit;
    }
     
    if ($action === 'save_weekly_goal' || $action === 'save_today_goal') {
        // 주간/오늘 목표 저장
        $record = new stdClass();
        $record->userid = $studentid;
        $record->type = ($action === 'save_weekly_goal') ? '주간목표' : '오늘목표';
        $record->text = $goal_text;
        $record->timecreated = time();
        
        $DB->insert_record('abessi_today', $record);
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>목표 관리 - <?php echo htmlspecialchars($studentName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 700;
        }
        
        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        
        .goal-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .goal-section:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .goal-section.empty {
            background: #fff5f5;
            border-color: #ffc4c4;
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .goal-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .goal-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9em;
        }
        
        .goal-content {
            color: #555;
            line-height: 1.8;
            font-size: 1.05em;
            padding: 15px;
            background: white;
            border-radius: 10px;
            min-height: 60px;
            display: flex;
            align-items: center;
        }
        
        .empty-message {
            color: #999;
            font-style: italic;
        }
        
        .goal-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.3s ease;
        }
        
        .goal-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .btn-edit {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 8px 20px;
        }
        
        .btn-edit:hover {
            background: #667eea;
            color: white;
        }
        
        .progress-bar {
            margin-top: 40px;
            padding: 20px;
            background: #f0f4ff;
            border-radius: 15px;
        }
        
        .progress-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .progress-items {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .progress-item {
            flex: 1;
            text-align: center;
        }
        
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
            color: white;
        }
        
        .progress-complete {
            background: linear-gradient(135deg, #4ade80, #22c55e);
        }
        
        .progress-incomplete {
            background: #e0e0e0;
            color: #999;
        }
        
        .progress-label {
            font-size: 0.9em;
            color: #666;
        }
        
        .save-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4ade80;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 222, 128, 0.3);
            display: none;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .edit-mode {
            display: none;
        }
        
        .view-mode {
            display: block;
        }
        
        .goal-section.editing .edit-mode {
            display: block;
        }
        
        .goal-section.editing .view-mode {
            display: none;
        }
        
        /* 인라인 에디팅 스타일 */
        .goal-content-editable {
            min-height: 60px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .goal-content-editable:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }
        
        .goal-input-inline {
            width: 100%;
            padding: 15px;
            border: 2px solid #667eea;
            border-radius: 10px;
            font-size: 1em;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            background: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .goal-input-inline:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .goal-timestamp {
            font-size: 0.75em;
            color: #999;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        // 목표 타입에 따라 제목 변경
        $page_title = '🎯 목표 입력하기';
        $page_subtitle = htmlspecialchars($studentName) . '님의 학습 목표 관리';
        
        if ($goal_type == 'term') {
            $page_title = '🎯 분기 목표 설정';
            $page_subtitle = '이번 분기에 달성하고 싶은 목표를 설정하세요';
        } elseif ($goal_type == 'weekly') {
            $page_title = '📅 주간 목표 설정';
            $page_subtitle = '이번 주에 집중할 목표를 설정하세요';
        } elseif ($goal_type == 'today') {
            $page_title = '⭐ 오늘 목표 설정';
            $page_subtitle = '오늘 반드시 달성할 목표를 설정하세요';
        }
        ?>
        <h1><?php echo $page_title; ?></h1>
        <p class="subtitle"><?php echo $page_subtitle; ?></p>
        
        
        <?php if (empty($goal_type) || $goal_type == 'term'): ?>
        <!-- 분기 목표 -->
        <div class="goal-section <?php echo !$termplan || $termplan->deadline < $now ? 'empty' : ''; ?>" id="termGoalSection">
            <div class="goal-header">
                <div class="goal-title">
                    <div class="goal-icon">분</div>
                    <span>분기 목표</span>
                </div>
                <?php if ($termplan && $termplan->deadline > $now): ?>
                    <div class="goal-timestamp">
                        <span id="termGoalTime"><?php echo getTimeAgo($termplan->timecreated); ?></span> 입력
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="goal-content-editable" onclick="startInlineEdit('term')" id="termGoalContent">
                <?php if ($termplan && $termplan->deadline > $now): ?>
                    <?php echo htmlspecialchars($termplan->memo); ?>
                <?php else: ?>
                    <span class="empty-message">분기 목표를 클릭하여 입력하세요</span>
                <?php endif; ?>
            </div>
            
            <textarea class="goal-input-inline" id="termGoalInput" 
                      placeholder="이번 분기에 달성하고 싶은 목표를 입력하세요..."
                      onblur="saveInlineGoal('term')"
                      onkeydown="handleInlineKeydown(event, 'term')"
                      style="display: none;"><?php echo $termplan ? htmlspecialchars($termplan->memo) : ''; ?></textarea>
        </div>
        <?php endif; ?>
        
        <?php if (empty($goal_type) || $goal_type == 'weekly'): ?>
        <!-- 주간 목표 -->
        <div class="goal-section <?php echo !$weeklyGoal ? 'empty' : ''; ?>" id="weeklyGoalSection">
            <div class="goal-header">
                <div class="goal-title">
                    <div class="goal-icon">주</div>
                    <span>주간 목표</span>
                </div>
                <?php if ($weeklyGoal): ?>
                    <div class="goal-timestamp">
                        <span id="weeklyGoalTime"><?php echo getTimeAgo($weeklyGoal->timecreated); ?></span> 입력
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="goal-content-editable" onclick="startInlineEdit('weekly')" id="weeklyGoalContent">
                <?php if ($weeklyGoal): ?>
                    <?php echo htmlspecialchars($weeklyGoal->text); ?>
                <?php else: ?>
                    <span class="empty-message">주간 목표를 클릭하여 입력하세요</span>
                <?php endif; ?>
            </div>
            
            <textarea class="goal-input-inline" id="weeklyGoalInput" 
                      placeholder="이번 주에 집중할 목표를 입력하세요..."
                      onblur="saveInlineGoal('weekly')"
                      onkeydown="handleInlineKeydown(event, 'weekly')"
                      style="display: none;"><?php echo $weeklyGoal ? htmlspecialchars($weeklyGoal->text) : ''; ?></textarea>
        </div>
        <?php endif; ?>
        
        <?php if (empty($goal_type) || $goal_type == 'today'): ?>
        <!-- 오늘 목표 -->
        <div class="goal-section <?php echo !$todayGoal ? 'empty' : ''; ?>" id="todayGoalSection">
            <div class="goal-header">
                <div class="goal-title">
                    <div class="goal-icon">오</div>
                    <span>오늘 목표</span>
                </div>
                <?php if ($todayGoal): ?>
                    <div class="goal-timestamp">
                        <span id="todayGoalTime"><?php echo getTimeAgo($todayGoal->timecreated); ?></span> 입력
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="goal-content-editable" onclick="startInlineEdit('today')" id="todayGoalContent">
                <?php if ($todayGoal): ?>
                    <?php echo htmlspecialchars($todayGoal->text); ?>
                <?php else: ?>
                    <span class="empty-message">오늘 목표를 클릭하여 입력하세요</span>
                <?php endif; ?>
            </div>
            
            <textarea class="goal-input-inline" id="todayGoalInput" 
                      placeholder="오늘 반드시 달성할 목표를 입력하세요..."
                      onblur="saveInlineGoal('today')"
                      onkeydown="handleInlineKeydown(event, 'today')"
                      style="display: none;"><?php echo $todayGoal ? htmlspecialchars($todayGoal->text) : ''; ?></textarea>
        </div>
        <?php endif; ?>
        
        <?php if (empty($goal_type)): ?>
        <!-- 진행 상황 -->
        <div class="progress-bar">
            <div class="progress-title">📊 목표 설정 현황</div>
            <div class="progress-items">
                <div class="progress-item">
                    <div class="progress-circle <?php echo ($termplan && $termplan->deadline > $now) ? 'progress-complete' : 'progress-incomplete'; ?>">
                        <?php echo ($termplan && $termplan->deadline > $now) ? '✓' : '○'; ?>
                    </div>
                    <div class="progress-label">분기 목표</div>
                </div>
                <div class="progress-item">
                    <div class="progress-circle <?php echo $weeklyGoal ? 'progress-complete' : 'progress-incomplete'; ?>">
                        <?php echo $weeklyGoal ? '✓' : '○'; ?>
                    </div>
                    <div class="progress-label">주간 목표</div>
                </div>
                <div class="progress-item">
                    <div class="progress-circle <?php echo $todayGoal ? 'progress-complete' : 'progress-incomplete'; ?>">
                        <?php echo $todayGoal ? '✓' : '○'; ?>
                    </div>
                    <div class="progress-label">오늘 목표</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="save-notification" id="saveNotification">
        ✓ 목표가 저장되었습니다!
    </div>
    
    <script>
        const studentId = <?php echo $studentid; ?>;
        let currentEditingType = null;
        
        // 인라인 에디팅 시작
        function startInlineEdit(type) {
            // 다른 편집중인 요소가 있다면 저장
            if (currentEditingType && currentEditingType !== type) {
                saveInlineGoal(currentEditingType);
            }
            
            currentEditingType = type;
            
            const contentDiv = document.getElementById(type + 'GoalContent');
            const textArea = document.getElementById(type + 'GoalInput');
            
            // 현재 텍스트를 textarea에 설정 (empty-message 제외)
            const currentText = contentDiv.querySelector('.empty-message') ? '' : contentDiv.textContent.trim();
            textArea.value = currentText;
            
            // 요소 전환
            contentDiv.style.display = 'none';
            textArea.style.display = 'block';
            textArea.focus();
            
            // 텍스트 전체 선택
            textArea.select();
        }
        
        // 키보드 이벤트 처리
        function handleInlineKeydown(event, type) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                saveInlineGoal(type);
            } else if (event.key === 'Escape') {
                cancelInlineEdit(type);
            }
        }
        
        // 인라인 에디팅 취소
        function cancelInlineEdit(type) {
            const contentDiv = document.getElementById(type + 'GoalContent');
            const textArea = document.getElementById(type + 'GoalInput');
            
            textArea.style.display = 'none';
            contentDiv.style.display = 'flex';
            
            currentEditingType = null;
        }
        
        // 인라인 목표 저장
        function saveInlineGoal(type) {
            const textArea = document.getElementById(type + 'GoalInput');
            const contentDiv = document.getElementById(type + 'GoalContent');
            const goalText = textArea.value.trim();
            
            // 빈 값이면 취소
            if (!goalText) {
                cancelInlineEdit(type);
                return;
            }
            
            // AJAX로 저장
            const formData = new FormData();
            formData.append('action', 'save_' + type + '_goal');
            formData.append('goal_text', goalText);
            formData.append('userid', studentId);
            
            fetch('goals.php?userid=' + studentId + '&embed=1', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 성공시 UI 업데이트
                    updateGoalContent(type, goalText);
                    showNotification();
                    
                    // 부모 페이지에 알림
                    if (window.parent && window.parent.postMessage) {
                        setTimeout(() => {
                            window.parent.postMessage({ type: 'goalSaved' }, '*');
                        }, 1000);
                    }
                } else {
                    alert('저장 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('저장 중 오류가 발생했습니다.');
            });
        }
        
        // 목표 내용 업데이트
        function updateGoalContent(type, goalText) {
            const contentDiv = document.getElementById(type + 'GoalContent');
            const textArea = document.getElementById(type + 'GoalInput');
            const timestampSpan = document.getElementById(type + 'GoalTime');
            
            // 내용 업데이트
            contentDiv.innerHTML = goalText;
            
            // 타임스탬프 업데이트
            if (timestampSpan) {
                timestampSpan.textContent = '방금';
            } else {
                // 타임스탬프가 없는 경우 (새로 생성된 목표) 페이지 새로고침을 통해 전체 구조 업데이트
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
            
            // 요소 전환
            textArea.style.display = 'none';
            contentDiv.style.display = 'flex';
            
            currentEditingType = null;
        }
        
        function showNotification() {
            const notification = document.getElementById('saveNotification');
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
        
        // 페이지 떠날 때 편집중인 내용 저장
        window.addEventListener('beforeunload', function() {
            if (currentEditingType) {
                saveInlineGoal(currentEditingType);
            }
        });
    </script>
</body>
</html>