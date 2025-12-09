<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
 
// Get parameters
$studentid = $_GET["id"]; 
$tbegin = $_GET["tb"]; 
$maxtime = time() - $tbegin; 

if($studentid == NULL) $studentid = $USER->id;

// Check user role and permissions
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'"); 
$role = $userrole->data;

if($USER->id != $studentid && $role === 'student') {
    echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
    exit;
}

// Get user data
$timecreated = time(); 
$username = $DB->get_record_sql("SELECT id, hideinput, lastname, firstname, timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1");
$studentname = $username->firstname.$username->lastname;
$tabtitle = "공부결과 - ".$username->lastname;

// Time variables
$timestart2 = time() - $tbegin;
$adayAgo = time() - 43200;
$aweekAgo = time() - 604800;
$timestart3 = time() - 86400 * 14;

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','today','$timecreated')");
}

// Initialize quiz tracking variables
$quizlist00 = '';
$quizlist11 = '';
$quizlist12 = '';
$quizlist21 = '';
$quizlist22 = '';
$quizlist31 = '';
$quizlist32 = '';
$reducetime = 0;

// Get quiz attempts
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz LEFT JOIN mdl_quiz_attempts ON mdl_quiz.id=mdl_quiz_attempts.quiz WHERE mdl_quiz_attempts.timemodified > '$timestart2' AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.id DESC LIMIT 200");
$result = json_decode(json_encode($quizattempts), True);

// Process quiz results
foreach($result as $value) {
    $quizname = $value['name'];
    $quizmoduleid = $value['quiz'];
    $quizgrade = round($value['sumgrades'], 1);
    $timestart = $value['timestart'];
    $timefinish = $value['timefinish'];
    $solvetime = $timefinish - $timestart;
    $maxgrade = round($value['tgrades'], 0);
    $attemptid = $value['id'];
    $attempt = $value['attempt'];
    $state = $value['state'];
    
    if($solvetime > 0) {
        $reducetime += $solvetime;
        
        // Format display values
        $quizstart = date("m/d H:i", $timestart);
        $timefinish_formatted = date("m/d H:i", $timefinish);
        $quiztitle = $quizname;
        
        // Status icon based on grade percentage
        $gradepercent = $maxgrade > 0 ? round(($quizgrade/$maxgrade)*100, 1) : 0;
        if($gradepercent >= 90) {
            $imgstatus = '<span class="status-icon status-excellent" title="우수 (90% 이상)"></span>';
        } elseif($gradepercent >= 80) {
            $imgstatus = '<span class="status-icon status-good" title="양호 (80-89%)"></span>';
        } elseif($gradepercent >= 70) {
            $imgstatus = '<span class="status-icon status-fair" title="보통 (70-79%)"></span>';
        } else {
            $imgstatus = '<span class="status-icon status-poor" title="미흡 (70% 미만)"></span>';
        }
        
        // Tooltip and comment system
        $tooltip_content = '퀴즈: '.$quiztitle.'\n점수: '.$quizgrade.'/'.$maxgrade.' ('.$gradepercent.'%)\n시도: '.$attempt.'회\n상태: '.$state;
        $comment = '<span class="tooltip3">[?]<span class="tooltip3text">'.$tooltip_content.'</span></span>';
        
        // Management controls
        $modifyquiz = '<button onclick="addquiztime('.$attemptid.')" class="btn-modify" title="시간연장">⏰</button> <button onclick="deletequiz('.$attemptid.')" class="btn-delete" title="삭제">🗑️</button>';
        
        // Complex categorization logic from original
        $qnum_result = $DB->get_record_sql("SELECT count(*) as count FROM mdl_quiz_slots WHERE quizid='$quizmoduleid'");
        $qnum = $qnum_result ? $qnum_result->count : 0;
        
        $review_result = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$studentid' AND fieldid='3'");
        $review = ($review_result && $review_result->data) ? $review_result->data : 0;
        
        // Category 1: Review-based classification (review==3)
        if($review == 3) {
            if(strpos($quiztitle, '개념') !== false) {
                $quizlist11 .= '<tr><td>'.$imgstatus.'</td><td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</b></td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
            } elseif(strpos($quiztitle, '심화') !== false || strpos($quiztitle, '해결') !== false) {
                $quizlist12 .= '<tr><td>'.$imgstatus.'</td><td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</b></td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
            }
        }
        // Category 2: Title-based classification (내신 pattern)
        elseif(strpos($quiztitle, '내신') !== false) {
            if($timestart > $adayAgo || $timefinish > $adayAgo) {
                $quizlist21 .= '<tr><td>'.$imgstatus.'</td><td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</b></td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
            } else {
                $quizlist22 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
            }
        }
        // Category 3: Question count-based classification (qnum>9)
        elseif($qnum > 9) {
            if($timestart > $adayAgo || $timefinish > $adayAgo) {
                $quizlist21 .= '<tr><td>'.$imgstatus.'</td><td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</b></td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
            } else {
                $quizlist22 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
            }
        }
        // Category 4: Default classification
        else {
            $checkbox = '<input type="checkbox" name="checkAccount" onClick="AddReview(1111,\''.$studentid.'\',\''.$attemptid.'\', this.checked)"/>';
            if($timestart > $adayAgo || $timefinish > $adayAgo) {
                $quizlist31 .= '<tr><td>'.$imgstatus.'</td><td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</b></td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$checkbox.'</td><td>'.$modifyquiz.'</td></tr>';
            } else {
                $quizlist32 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$checkbox.'</td><td>'.$modifyquiz.'</td></tr>';
            }
        }
        
        // Handle special categories
        if(strpos($quiztitle, 'SW') !== false || strpos($quiztitle, 'sw') !== false) {
            $quizlist00 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.$quiztitle.'</a>...('.$attempt.'회)</td><td>'.$quizstart.'</td><td><span style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank">'.$state.'</a></td><td>'.$timefinish_formatted.'</td><td>'.$comment.'</td><td>'.$modifyquiz.'</td></tr>';
        }
    }
}

$total_study_time = round($reducetime/60, 0);

// Whiteboard activity tracking (using original query structure)
$handwriting = $DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND status NOT LIKE 'attempt' AND tlaststroke>'$timestart2' AND contentstype=2 AND (active=1 OR status='flag') ORDER BY tlaststroke DESC LIMIT 100");

$whiteboard_statistics = array(
    'total_messages' => 0,
    'total_time' => 0,
    'course_activities' => array(),
    'recent_activities' => array(),
    'past_activities' => array()
);

$activities_today = '';
$activities_past = '';

foreach($handwriting as $activity) {
    $whiteboard_statistics['total_messages']++;
    $coursename = $activity->contentstitle ? $activity->contentstitle : '기본';
    $message = $activity->message ? $activity->message : 'Whiteboard Activity';
    $wboardid = $activity->wboardid;
    $activitytime_timestamp = $activity->tlaststroke ? $activity->tlaststroke : $activity->timemodified;
    $activitytime = date("m/d H:i", $activitytime_timestamp);
    $nstroke = $activity->nstroke ? $activity->nstroke : 0;
    $status = $activity->status ? $activity->status : '';
    
    // Create activity display with more details
    $activity_display = $coursename;
    if($status) $activity_display .= ' ('.$status.')';
    if($nstroke > 0) $activity_display .= ' - '.$nstroke.' strokes';
    
    // Categorize by time
    if($activitytime_timestamp > $adayAgo) {
        $activities_today .= '<tr><td>'.$activitytime.'</td><td>'.$activity_display.'</td><td>'.substr($message, 0, 50).'...</td><td><button onclick="showWboard(\''.$wboardid.'\')" class="btn-view">보기</button></td></tr>';
        $whiteboard_statistics['recent_activities'][] = array(
            'time' => $activitytime,
            'course' => $coursename,
            'message' => $message,
            'id' => $wboardid
        );
    } else {
        $activities_past .= '<tr><td>'.$activitytime.'</td><td>'.$activity_display.'</td><td>'.substr($message, 0, 50).'...</td><td><button onclick="showWboard(\''.$wboardid.'\')" class="btn-view">보기</button></td></tr>';
        $whiteboard_statistics['past_activities'][] = array(
            'time' => $activitytime,
            'course' => $coursename,
            'message' => $message,
            'id' => $wboardid
        );
    }
    
    // Course-wise statistics  
    if(!isset($whiteboard_statistics['course_activities'][$coursename])) {
        $whiteboard_statistics['course_activities'][$coursename] = 0;
    }
    $whiteboard_statistics['course_activities'][$coursename]++;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tabtitle; ?> - 학습 홈</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        /* Navigation */
        .nav-top {
            background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .nav-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .nav-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .nav-btn.active {
            background: rgba(255,255,255,0.95);
            color: #7C3AED;
            font-weight: 700;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .nav-btn.active:hover {
            background: rgba(255,255,255,1);
            color: #7C3AED;
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        }
        
        .content-wrapper {
            padding: 30px 20px 0;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nav-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .view-controls {
            display: flex;
            gap: 5px;
        }
        
        .view-toggle-btn {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid transparent;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-toggle-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .view-toggle-btn.scroll-mode {
            background: rgba(255,255,255,0.9);
            color: #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .view-toggle-btn.scroll-mode:hover {
            background: rgba(255,255,255,1);
            color: #667eea;
            transform: none;
        }
        
        /* Tab View Styles */
        .tab-view {
            display: none;
        }
        
        .tab-view.active {
            display: block;
        }
        
        .tab-header {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: #e9ecef;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        /* Scroll View Styles */
        .scroll-view {
            display: block;
        }
        
        .scroll-view.active {
            display: block;
        }
        
        /* Statistics Card */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stats-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .study-time {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .study-time-label {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .time-period {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Quiz Tables */
        .quiz-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .quiz-section h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quiz-section h3 i {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .quiz-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .quiz-table th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .quiz-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #555;
        }
        
        .quiz-table tr:hover {
            background: #f8f9fa;
        }
        
        .quiz-table a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .quiz-table a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 16px;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-nav {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .nav-btn {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .nav-btn i {
                display: none;
            }
            
            .content-wrapper {
                padding: 20px 10px 0;
            }
            
            .quiz-table {
                font-size: 14px;
            }
            
            .quiz-table th,
            .quiz-table td {
                padding: 8px 6px;
            }
            
            .study-time {
                font-size: 36px;
            }
        }
        
        /* Badge Styles for consistency */
        .mission-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-concept {
            background: #667eea;
            color: white;
        }
        
        .badge-advanced {
            background: #764ba2;
            color: white;
        }
        
        .badge-test {
            background: #f093fb;
            color: white;
        }
        
        .badge-exam {
            background: #4facfe;
            color: white;
        }
        
        .badge-english {
            background: #43e97b;
            color: white;
        }
        
        .badge-other {
            background: #38ef7d;
            color: white;
        }
        
        .badge-whiteboard {
            background: #ff6b6b;
            color: white;
        }
        
        /* Whiteboard Styles */
        .whiteboard-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 200px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #ff6b6b;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Status Icons */
        .status-icon {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-excellent {
            background: #28a745;
        }
        
        .status-good {
            background: #17a2b8;
        }
        
        .status-fair {
            background: #ffc107;
        }
        
        .status-poor {
            background: #dc3545;
        }
        
        /* Tooltip3 System */
        .tooltip3 {
            position: relative;
            display: inline-block;
            cursor: pointer;
            color: #667eea;
            font-weight: bold;
        }
        
        .tooltip3 .tooltip3text {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            white-space: pre-line;
        }
        
        .tooltip3 .tooltip3text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #555 transparent transparent transparent;
        }
        
        .tooltip3:hover .tooltip3text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Management Buttons */
        .btn-modify, .btn-delete {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 0 2px;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .btn-modify:hover {
            background: #fff3cd;
        }
        
        .btn-delete:hover {
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="nav-top">
        <div class="content-container">
            <div class="nav-controls">
                    <div class="header-nav">
                        <a href="index42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                            <i class="fas fa-home"></i> 내공부방
                        </a>
                        <a href="today42.php?id=<?php echo $studentid; ?>&tb=604800" class="nav-btn active">
                            <i class="fas fa-chart-bar"></i> 공부결과
                        </a>
                        <a href="../alt42/teachingsupport/student_inbox42.php?studentid=<?php echo $studentid; ?>" class="nav-btn">
                            <i class="fas fa-envelope"></i> 메세지함
                        </a>
                        <a href="goals42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                            <i class="fas fa-target"></i> 목표설정
                        </a>
                        <a href="schedule42.php?id=<?php echo $studentid; ?>&eid=1&nweek=12" class="nav-btn">
                            <i class="fas fa-clock"></i> 수업시간
                        </a>
                        <a href="../teachers/timescaffolding42.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                            <i class="fas fa-book-open"></i> 수학일기
                        </a>
                    </div>
                    <div class="view-controls">
                        <button class="view-toggle-btn" onclick="toggleView()" title="뷰 전환">
                            <i class="fas fa-folder" id="viewIcon"></i>
                        </button>
                    </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <!-- Statistics Card -->
        <div class="stats-card">
            <h2 class="stats-title">총 학습 시간</h2>
            <div class="study-time"><?php echo $total_study_time; ?></div>
            <div class="study-time-label">분</div>
            <div class="time-period">
                <?php 
                if($tbegin == 604800) echo "최근 1주일";
                elseif($tbegin == 1209600) echo "최근 2주일";
                elseif($tbegin == 2592000) echo "최근 1개월";
                else echo "학습 기간";
                ?>
            </div>
        </div>

        <!-- Tab View -->
        <div id="tabView" class="tab-view active">
            <div class="tab-header">
                <?php if($quizlist11): ?>
                <button class="tab-btn <?php echo (!$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>" onclick="openTab('concept')">
                    <span class="mission-badge badge-concept">개념</span> 학습
                </button>
                <?php endif; ?>
                <?php if($quizlist12): ?>
                <button class="tab-btn <?php echo (!$quizlist11 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>" onclick="openTab('advanced')">
                    <span class="mission-badge badge-advanced">심화</span> 학습
                </button>
                <?php endif; ?>
                <?php if($quizlist21): ?>
                <button class="tab-btn <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>" onclick="openTab('test')">
                    <span class="mission-badge badge-test">내신</span> 평가
                </button>
                <?php endif; ?>
                <?php if($quizlist22): ?>
                <button class="tab-btn <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>" onclick="openTab('exam')">
                    <span class="mission-badge badge-exam">수능</span> 시험
                </button>
                <?php endif; ?>
                <?php if($quizlist31): ?>
                <button class="tab-btn <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>" onclick="openTab('english')">
                    <span class="mission-badge badge-english">영어</span>
                </button>
                <?php endif; ?>
                <?php if($quizlist32 || $quizlist00): ?>
                <button class="tab-btn <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31) ? 'active' : ''; ?>" onclick="openTab('other')">
                    <span class="mission-badge badge-other">기타</span>
                </button>
                <?php endif; ?>
                <?php if($activities_today || $activities_past): ?>
                <button class="tab-btn" onclick="openTab('whiteboard')">
                    <span class="mission-badge badge-whiteboard">화이트보드</span>
                </button>
                <?php endif; ?>
                
                <?php if(!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00 && !$activities_today && !$activities_past): ?>
                <button class="tab-btn active" onclick="openTab('empty')">
                    <span class="mission-badge" style="background: #ccc;">학습 기록 없음</span>
                </button>
                <?php endif; ?>
            </div>

            <?php if($quizlist11): ?>
            <!-- Concept Tab -->
            <div id="concept" class="tab-content <?php echo (!$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>">
                <div class="quiz-section">
                    <h3><i class="fas fa-lightbulb"></i>개념 학습 결과</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>상태</th>
                                <th>퀴즈명</th>
                                <th>시작</th>
                                <th>점수</th>
                                <th>상태</th>
                                <th>완료시간</th>
                                <th>분석</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $quizlist11; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if($quizlist12): ?>
            <!-- Advanced Tab -->
            <div id="advanced" class="tab-content <?php echo (!$quizlist11 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>">
                <div class="quiz-section">
                    <h3><i class="fas fa-brain"></i>심화 학습 결과</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>상태</th>
                                <th>퀴즈명</th>
                                <th>시작</th>
                                <th>점수</th>
                                <th>상태</th>
                                <th>완료시간</th>
                                <th>분석</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $quizlist12; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if($quizlist21): ?>
            <!-- Test Tab -->
            <div id="test" class="tab-content <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>">
                <div class="quiz-section">
                    <h3><i class="fas fa-file-alt"></i>내신 평가 결과</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>상태</th>
                                <th>시험명</th>
                                <th>시작</th>
                                <th>점수</th>
                                <th>상태</th>
                                <th>완료시간</th>
                                <th>분석</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $quizlist21; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if($quizlist22): ?>
            <!-- Exam Tab -->
            <div id="exam" class="tab-content <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist31 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>">
                <div class="quiz-section">
                    <h3><i class="fas fa-graduation-cap"></i>수능 시험 결과</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>상태</th>
                                <th>시험명</th>
                                <th>시작</th>
                                <th>점수</th>
                                <th>상태</th>
                                <th>완료시간</th>
                                <th>분석</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $quizlist22; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if($quizlist31): ?>
            <!-- English Tab -->
            <div id="english" class="tab-content <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist32 && !$quizlist00) ? 'active' : ''; ?>">
                <div class="quiz-section">
                    <h3><i class="fas fa-language"></i>영어 학습 결과</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>상태</th>
                                <th>퀴즈명</th>
                                <th>시작</th>
                                <th>점수</th>
                                <th>상태</th>
                                <th>완료시간</th>
                                <th>분석</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $quizlist31; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if($quizlist32 || $quizlist00): ?>
            <!-- Other Tab -->
            <div id="other" class="tab-content <?php echo (!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31) ? 'active' : ''; ?>">
                <div class="quiz-section">
                    <h3><i class="fas fa-ellipsis-h"></i>기타 학습 결과</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>상태</th>
                                <th>퀴즈명</th>
                                <th>시작</th>
                                <th>점수</th>
                                <th>상태</th>
                                <th>완료시간</th>
                                <th>분석</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $quizlist32.$quizlist00; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Whiteboard Tab -->
            <div id="whiteboard" class="tab-content">
                <div class="whiteboard-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $whiteboard_statistics['total_messages']; ?></div>
                        <div class="stat-label">총 활동 수</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($whiteboard_statistics['recent_activities']); ?></div>
                        <div class="stat-label">최근 24시간</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($whiteboard_statistics['course_activities']); ?></div>
                        <div class="stat-label">참여 과목 수</div>
                    </div>
                </div>

                <div class="quiz-section">
                    <h3><i class="fas fa-chalkboard"></i>최근 화이트보드 활동</h3>
                    <?php if($activities_today): ?>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>활동</th>
                                <th>내용 미리보기</th>
                                <th>보기</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $activities_today; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <p>최근 24시간 화이트보드 활동이 없습니다.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if($activities_past): ?>
                <div class="quiz-section">
                    <h3><i class="fas fa-history"></i>이전 화이트보드 활동</h3>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>활동</th>
                                <th>내용 미리보기</th>
                                <th>보기</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $activities_past; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scroll View -->
        <div id="scrollView" class="scroll-view">
            <!-- All quiz results in one scrollable list -->
            <?php if($quizlist11): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-lightbulb"></i>개념 학습</h3>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>날짜/시간</th>
                            <th>퀴즈명</th>
                            <th>점수</th>
                            <th>소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $quizlist11; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if($quizlist12): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-brain"></i>심화 학습</h3>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>날짜/시간</th>
                            <th>퀴즈명</th>
                            <th>점수</th>
                            <th>소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $quizlist12; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if($quizlist21): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-file-alt"></i>내신 평가</h3>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>날짜/시간</th>
                            <th>시험명</th>
                            <th>점수</th>
                            <th>소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $quizlist21; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if($quizlist22): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-graduation-cap"></i>수능 시험</h3>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>날짜/시간</th>
                            <th>시험명</th>
                            <th>점수</th>
                            <th>소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $quizlist22; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if($quizlist31): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-language"></i>영어 학습</h3>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>날짜/시간</th>
                            <th>퀴즈명</th>
                            <th>점수</th>
                            <th>소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $quizlist31; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if($quizlist32 || $quizlist00): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-ellipsis-h"></i>기타 학습</h3>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>날짜/시간</th>
                            <th>퀴즈명</th>
                            <th>점수</th>
                            <th>소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $quizlist32.$quizlist00; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Whiteboard Activities Section -->
            <?php if($activities_today || $activities_past): ?>
            <div class="quiz-section">
                <h3><i class="fas fa-chalkboard"></i>화이트보드 활동</h3>
                <?php if($activities_today): ?>
                <h4 style="margin-top: 20px; margin-bottom: 15px; color: #ff6b6b;">최근 24시간</h4>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>활동</th>
                            <th>내용 미리보기</th>
                            <th>보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $activities_today; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <?php if($activities_past): ?>
                <h4 style="margin-top: 30px; margin-bottom: 15px; color: #666;">이전 활동</h4>
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>활동</th>
                            <th>내용 미리보기</th>
                            <th>보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $activities_past; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if(!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00 && !$activities_today && !$activities_past): ?>
            <div class="quiz-section">
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>학습 기록이 없습니다</h3>
                    <p>선택한 기간 동안의 학습 기록이 없습니다.<br>열심히 공부해보세요!</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Empty State Tab -->
            <?php if(!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00 && !$activities_today && !$activities_past): ?>
            <div id="empty" class="tab-content active">
                <div class="quiz-section">
                    <div class="empty-state">
                        <i class="fas fa-chart-line" style="font-size: 64px; margin-bottom: 20px;"></i>
                        <h3>학습 기록이 없습니다</h3>
                        <p>선택한 기간 동안의 학습 기록이 없습니다.<br>퀴즈를 풀거나 화이트보드를 사용해보세요!</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentView = 'tab';

        function toggleView() {
            const tabView = document.getElementById('tabView');
            const scrollView = document.getElementById('scrollView');
            const viewIcon = document.getElementById('viewIcon');
            const toggleBtn = document.querySelector('.view-toggle-btn');

            if (currentView === 'tab') {
                // Switch to scroll view
                tabView.classList.remove('active');
                scrollView.classList.add('active');
                viewIcon.className = 'fas fa-th-list';
                toggleBtn.classList.add('scroll-mode');
                currentView = 'scroll';
            } else {
                // Switch to tab view
                scrollView.classList.remove('active');
                tabView.classList.add('active');
                viewIcon.className = 'fas fa-folder';
                toggleBtn.classList.remove('scroll-mode');
                currentView = 'tab';
            }
        }

        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.setAttribute('aria-hidden', 'true');
            });

            // Remove active class from all tab buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });

            // Show selected tab content
            const activeTab = document.getElementById(tabName);
            if(activeTab) {
                activeTab.classList.add('active');
                activeTab.setAttribute('aria-hidden', 'false');
            }
            
            // Add active class to clicked tab button
            const activeBtn = event.target.closest('.tab-btn');
            if(activeBtn) {
                activeBtn.classList.add('active');
                activeBtn.setAttribute('aria-selected', 'true');
            }
            
            // Announce tab change to screen readers
            const tabTitle = activeBtn ? activeBtn.textContent.trim() : tabName;
            showNotification(`${tabTitle} 탭으로 전환되었습니다.`, 'info');
        }

        // Enhanced JavaScript functions with AJAX implementation
        function AddReview(type, studentid, attemptid, checked) {
            if(checked) {
                // AJAX call to add review
                fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add_review&type=' + type + '&studentid=' + studentid + '&attemptid=' + attemptid
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log('Review added successfully');
                        showNotification('복습이 추가되었습니다.', 'success');
                    } else {
                        console.error('Failed to add review:', data.message);
                        showNotification('복습 추가에 실패했습니다.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error adding review:', error);
                    showNotification('오류가 발생했습니다.', 'error');
                });
            } else {
                // AJAX call to remove review
                fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=remove_review&type=' + type + '&studentid=' + studentid + '&attemptid=' + attemptid
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log('Review removed successfully');
                        showNotification('복습이 제거되었습니다.', 'success');
                    }
                });
            }
        }
        
        function addquiztime(attemptid) {
            if(confirm('시간을 연장하시겠습니까?\\n연장된 시간은 취소할 수 없습니다.')) {
                // AJAX call to extend time
                fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=extend_time&attemptid=' + attemptid
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log('Time extended successfully');
                        showNotification('시간이 연장되었습니다.', 'success');
                        // Reload the page to show updated data
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        console.error('Failed to extend time:', data.message);
                        showNotification('시간 연장에 실패했습니다: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error extending time:', error);
                    showNotification('오류가 발생했습니다.', 'error');
                });
            }
        }
        
        function deletequiz(attemptid) {
            if(confirm('정말 삭제하시겠습니까?\\n이 작업은 되돌릴 수 없습니다.')) {
                // AJAX call to delete quiz
                fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_quiz&attemptid=' + attemptid
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log('Quiz deleted successfully');
                        showNotification('퀴즈가 삭제되었습니다.', 'success');
                        // Remove the row from the table
                        const row = document.querySelector(`button[onclick=\"deletequiz(${attemptid})\"]`).closest('tr');
                        if(row) {
                            row.style.opacity = '0.5';
                            row.style.textDecoration = 'line-through';
                        }
                        // Reload the page after a delay
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        console.error('Failed to delete quiz:', data.message);
                        showNotification('퀴즈 삭제에 실패했습니다: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting quiz:', error);
                    showNotification('오류가 발생했습니다.', 'error');
                });
            }
        }
        
        function showWboard(wboardid) {
            // Open whiteboard in new window
            const url = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=' + wboardid;
            const popup = window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            if(!popup) {
                showNotification('팝업이 차단되었습니다. 팝업 차단을 해제해주세요.', 'warning');
            }
        }
        
        function ChangeCheckBox2(type, studentid, encryptionid, checked) {
            // AJAX call to update checkbox status
            fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=change_checkbox&type=' + type + '&studentid=' + studentid + '&encryptionid=' + encryptionid + '&checked=' + (checked ? 1 : 0)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('Checkbox status updated');
                } else {
                    console.error('Failed to update checkbox:', data.message);
                }
            })
            .catch(error => {
                console.error('Error updating checkbox:', error);
            });
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            // Style the notification
            Object.assign(notification.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                borderRadius: '5px',
                color: 'white',
                zIndex: '10000',
                fontSize: '14px',
                fontWeight: '500',
                maxWidth: '350px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
                transform: 'translateX(400px)',
                transition: 'transform 0.3s ease'
            });
            
            // Set background color based on type
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            notification.style.backgroundColor = colors[type] || colors.info;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remove after delay
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if(notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Initialize scroll view as hidden and add performance monitoring
        document.addEventListener('DOMContentLoaded', function() {
            const scrollView = document.getElementById('scrollView');
            scrollView.style.display = 'none';
            
            // Add ARIA labels for better accessibility
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Find the first active tab and content
            let firstActiveTabFound = false;
            let firstActiveContentFound = false;
            
            tabBtns.forEach((btn, index) => {
                btn.setAttribute('role', 'tab');
                if(btn.classList.contains('active') && !firstActiveTabFound) {
                    btn.setAttribute('aria-selected', 'true');
                    btn.setAttribute('tabindex', '0');
                    firstActiveTabFound = true;
                } else {
                    btn.setAttribute('aria-selected', 'false');
                    btn.setAttribute('tabindex', '-1');
                }
            });
            
            tabContents.forEach((content, index) => {
                content.setAttribute('role', 'tabpanel');
                if(content.classList.contains('active') && !firstActiveContentFound) {
                    content.setAttribute('aria-hidden', 'false');
                    firstActiveContentFound = true;
                } else {
                    content.setAttribute('aria-hidden', 'true');
                }
            });
            
            // Add keyboard navigation
            tabBtns.forEach(btn => {
                btn.addEventListener('keydown', function(e) {
                    if(e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        btn.click();
                    } else if(e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                        e.preventDefault();
                        const currentIndex = Array.from(tabBtns).indexOf(btn);
                        const nextIndex = e.key === 'ArrowLeft' 
                            ? (currentIndex - 1 + tabBtns.length) % tabBtns.length
                            : (currentIndex + 1) % tabBtns.length;
                        tabBtns[nextIndex].focus();
                        tabBtns[nextIndex].click();
                    }
                });
            });
            
            // Performance monitoring
            console.log('Today.php loaded successfully');
            console.log('Performance metrics:', {
                totalQuizzes: document.querySelectorAll('.quiz-table tr').length - document.querySelectorAll('.quiz-table thead tr').length,
                whiteboardActivities: document.querySelectorAll('[onclick^="showWboard"]').length,
                loadTime: performance.now()
            });
        });
    </script>
</body>
</html>