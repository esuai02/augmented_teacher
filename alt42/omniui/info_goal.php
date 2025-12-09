<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get parameters
$studentid = $_GET["id"]; 
$mode = $_GET["mode"]; 
$gtype = $_GET["gtype"]; 
$inputtext = $_GET["cntinput"]; 
$nweek = $_GET["nweek"];  
$newdream = $_GET["newdream"]; 
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
$tabtitle = "목표관리 - ".$username->lastname;
$hideinput = $username->hideinput;
                      
// Time variables  
$timestart = $timecreated - 604800 * 2;
$aweekago = $timecreated - 604800;
$adayAgo = time() - 43200;

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','goals','$timecreated')");
}
                                             
// Set defaults
if($nweek == NULL) $nweek = 15;
if(strpos($gtype, '주간목표') !== false) $selectgtype2 = 'selected';
else $selectgtype1 = 'selected';
    
// Get today's and weekly goals
$todayGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1");
$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
$lastGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated<='$aweekago' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");

// Get recent goals for history
$goals = $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY id DESC");
$goalresult = json_decode(json_encode($goals), True);

// Process goal history
$goalhistory0 = '';
$goalhistory1 = '';
foreach($goalresult as $value) {
    $att = gmdate("m월 d일 ", $value['timecreated']+32400);
    $date = gmdate("d", $value['timecreated']+32400);
    $goaltype = $value['type'];
    $daterecord = date('Y_m_d', $value['timecreated']);  	 
    $tend = $value['timecreated'];
    
    if($goaltype === '오늘목표' || $goaltype === '검사요청') {
        $goaltype = '<span style="color:black;">오늘목표</span>';
        $notetype = 'summary';
    } elseif($goaltype === '주간목표') {
        $goaltype = '<b style="color:#bf04e0;">주간목표</b>';
        $notetype = 'weekly';
    } elseif($goaltype === '시험목표') {
        $goaltype = '<b style="color:blue;">분기목표</b>';
        $notetype = 'examplan';
    }
    
    $planwboardid = '_user'.$studentid.'_date'.$daterecord;
    
    if($value['type'] === '오늘목표' || $value['type'] === '검사요청') {
        $goalhistory0 .= '<tr><td>'.$att.'</td><td>'.$goaltype.'</td><td>'.$value['text'].'</td></tr>';
    } elseif($value['type'] === '주간목표') {
        $goalhistory1 .= '<tr><td>'.$att.'</td><td>'.$goaltype.'</td><td>'.$value['text'].'</td></tr>';
    }
}

// Get mission list (roadmap)
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by id DESC LIMIT 3");
$missionresult = json_decode(json_encode($missionlist), True);

// Get quiz attempts
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz LEFT JOIN mdl_quiz_attempts ON mdl_quiz.id=mdl_quiz_attempts.quiz WHERE (mdl_quiz_attempts.timefinish > '$aweekago' OR mdl_quiz_attempts.timestart > '$aweekago' OR (state='inprogress' AND mdl_quiz_attempts.timestart > '$aweekago')) AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.timestart");
$quizresult = json_decode(json_encode($quizattempts), True);

// Process quiz data
$nquiz = count($quizresult);
$quizlist11 = '';
$quizlist12 = '';
$quizlist21 = '';
$quizlist22 = '';
$quizlist31 = '';
$quizlist32 = '';
$todayGrade = 0;
$ntodayquiz = 0;
$weekGrade = 0;
$nweekquiz = 0;

foreach(array_reverse($quizresult) as $value) {
    $qnum = substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');
    $quizgrade = round($value['sumgrades']/$value['tgrades']*100,0);
    
    if($quizgrade > 89.99) {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
    } elseif($quizgrade > 69.99) {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
    } else {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
    }
    
    $quizid = $value['quiz'];
    $moduleid = $DB->get_record_sql("SELECT id FROM mdl_course_modules where instance='$quizid'"); 
    $quizmoduleid = $moduleid->id;
    $attemptid = $value['id'];
    
    $quiztitle = $value['name'];
    if(strpos($value['name'], 'ifminteacher') !== false) {
        $quiztitle = strstr($value['name'], '{ifminteacher', true);
    }
    
    if(strpos($quiztitle, '내신') != false) {
        if($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist11 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $todayGrade += $quizgrade;
            $ntodayquiz++;
        } else {
            $quizlist12 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $weekGrade += $quizgrade;
            $nweekquiz++;
        }
    } elseif($qnum > 9) {
        if($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist21 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $todayGrade += $quizgrade;
            $ntodayquiz++;
        } else {
            $quizlist22 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $weekGrade += $quizgrade;
            $nweekquiz++;
        }
    } else {
        if($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist31 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
        } else {
            $quizlist32 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
        }
    }
}

// Calculate averages
$avgToday = ($ntodayquiz > 0) ? round($todayGrade / $ntodayquiz) : 0;
$avgWeek = ($nweekquiz > 0) ? round($weekGrade / $nweekquiz) : 0;

// Set placeholder text
$placeholder = 'placeholder="※ 최대한 구체적인 목표를 입력해 주세요"';
$presettext = '';
if($inputtext != NULL) {
    $presettext = 'value="'.$inputtext.'"';
}

$deadline = date("Y:m:d", time());

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo $tabtitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3383FF;
            --secondary-color: #E05D22;
            --accent-color: #0082D8;
            --success-color: #059669;
            --warning-color: #ea580c;
            --info-color: #0891b2;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --shadow: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', '맑은 고딕', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        /* Navigation */
        .nav-top {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
            padding: 15px 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-menu {
            list-style: none;
            display: flex;
            justify-content: center;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        
        .nav-menu li {
            margin: 0 20px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .nav-menu a:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        /* Container */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* View Switcher */
        .view-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .view-info {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .view-switcher {
            display: flex;
            background: var(--card-bg);
            border-radius: 30px;
            padding: 4px;
            box-shadow: var(--shadow);
        }
        
        .view-btn {
            padding: 8px 20px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .view-btn:hover {
            color: var(--text-primary);
        }
        
        .view-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(51, 131, 255, 0.3);
        }
        
        .view-btn i {
            font-size: 16px;
        }
        
        /* Tab View Styles */
        .tab-container {
            display: none;
        }
        
        .tab-container.active {
            display: block;
        }
        
        .tab-nav {
            display: flex;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
        }
        
        .tab-btn:hover {
            color: var(--text-primary);
            background: rgba(0,0,0,0.03);
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(51, 131, 255, 0.3);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Scroll View Styles */
        .scroll-container {
            display: none;
        }
        
        .scroll-container.active {
            display: block;
        }
        
        /* Card Styles */
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 0;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #dc2626 100%);
            color: white;
            padding: 16px 24px;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
        }
        
        .section-header.secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #dc2626 100%);
        }
        
        .section-header.success {
            background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
        }
        
        .section-header.info {
            background: linear-gradient(135deg, var(--info-color) 0%, #0e7490 100%);
        }
        
        .section-header.warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #dc2626 100%);
        }
        
        .card-body {
            padding: 20px 24px;
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: var(--bg-color);
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
        }
        
        .data-table td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .data-table a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Form Styles */
        .goal-form {
            background: rgba(51, 131, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(51, 131, 255, 0.2);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(51, 131, 255, 0.1);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .form-row > * {
            flex: 1;
            min-width: 200px;
        }
        
        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(51, 131, 255, 0.3);
        }
        
        /* Stats Box */
        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: var(--bg-color);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content-container {
                padding: 15px;
            }
            
            .nav-menu li {
                margin: 0 10px;
            }
            
            .nav-menu a {
                font-size: 14px;
                padding: 6px 12px;
            }
            
            .tab-nav {
                padding: 5px;
            }
            
            .tab-btn {
                padding: 10px 16px;
                font-size: 14px;
            }
            
            .section-header {
                font-size: 16px;
                padding: 14px 20px;
            }
            
            .view-controls {
                justify-content: center;
            }
            
            .stats-box {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .form-row > * {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="nav-top">
        <div class="content-container">
            <ul class="nav-menu">
                <li><a href="index.php?id=<?php echo $studentid; ?>">홈</a></li>
                <li><a href="roadmap.php?id=<?php echo $studentid; ?>">로드맵</a></li>
                <li><a href="goals.php?id=<?php echo $studentid; ?>">목표관리</a></li>
                <li><a href="today.php?id=<?php echo $studentid; ?>&tb=604800">주간평가</a></li>
                <li><a href="schedule.php?id=<?php echo $studentid; ?>">스케줄</a></li>
            </ul>
        </div>
    </div>

    <div class="content-container">
        <!-- View Controls -->
        <div class="view-controls">
            <div class="view-info">
                목표 설정 및 로드맵
            </div>
            <div class="view-switcher">
                <button class="view-btn active" onclick="switchView('tab')">
                    <i class="fas fa-folder"></i> 탭 뷰
                </button>
                <button class="view-btn" onclick="switchView('scroll')">
                    <i class="fas fa-stream"></i> 스크롤 뷰
                </button>
            </div>
        </div>
        
        
        <!-- Tab View Container -->
        <div id="tabView" class="tab-container active">
            <div class="tab-nav">
                <button class="tab-btn active" onclick="openTab('today')">오늘목표</button>
                <button class="tab-btn" onclick="openTab('weekly')">주간목표</button>
                <button class="tab-btn" onclick="openTab('roadmap')">분기목표</button>
                <button class="tab-btn" onclick="openTab('quiz')">테스트현황</button>
            </div>
            
            <!-- 오늘목표 Tab -->
            <div id="today" class="tab-content active">
                <div class="card">
                    <div class="section-header primary">
                        <span>오늘 목표</span>
                        <span><?php echo ($todayGoal) ? date("m월 d일", $todayGoal->timecreated) : '미설정'; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if($todayGoal): ?>
                        <h3><?php echo $todayGoal->text; ?></h3>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <p>오늘 목표가 설정되지 않았습니다.</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($hideinput == 0 || $role !== 'student'): ?>
                        <div class="goal-form" style="margin-top: 20px;">
                            <h4 style="margin-bottom: 15px;">오늘 목표 설정</h4>
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <input type="text" class="form-control" id="todayGoalInput" placeholder="오늘의 목표를 입력해 주세요">
                                </div>
                                <div class="form-group">
                                    <button class="btn-submit" onclick="saveTodayGoal()">저장</button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="section-header info">
                        <span>최근 일일 목표 기록</span>
                    </div>
                    <div class="card-body">
                        <?php if($goalhistory0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="20%">날짜</th>
                                    <th width="20%">유형</th>
                                    <th width="60%">목표</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $goalhistory0; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>최근 일일 목표 기록이 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 주간목표 Tab -->
            <div id="weekly" class="tab-content">
                <div class="card">
                    <div class="section-header secondary">
                        <span>주간 목표</span>
                        <span><?php echo ($weeklyGoal) ? date("m월 d일", $weeklyGoal->timecreated) : '미설정'; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if($weeklyGoal): ?>
                        <h3><?php echo $weeklyGoal->text; ?></h3>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-week"></i>
                            <p>주간 목표가 설정되지 않았습니다.</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($hideinput == 0 || $role !== 'student'): ?>
                        <div class="goal-form" style="margin-top: 20px;">
                            <h4 style="margin-bottom: 15px;">주간 목표 설정</h4>
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <input type="text" class="form-control" id="weeklyGoalInput" placeholder="이번 주의 목표를 입력해 주세요">
                                </div>
                                <div class="form-group">
                                    <button class="btn-submit" onclick="saveWeeklyGoal()">저장</button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="section-header warning">
                        <span>최근 주간 목표 기록</span>
                    </div>
                    <div class="card-body">
                        <?php if($goalhistory1): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="20%">날짜</th>
                                    <th width="20%">유형</th>
                                    <th width="60%">목표</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $goalhistory1; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>최근 주간 목표 기록이 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 로드맵 Tab -->
            <div id="roadmap" class="tab-content">
                <div class="card">
                    <div class="section-header success">
                        <span>분기목표 설정</span>
                        <span><?php echo ($missionresult) ? 'D-'.round(($missionresult[0]['deadline'] - time()) / 86400 + 1).'일' : ''; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if(count($missionresult) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="20%">유형</th>
                                    <th width="50%">목표</th>
                                    <th width="20%">데드라인</th>
                                    <th width="10%">D-Day</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach($missionresult as $mission) {
                                    $dday = round(($mission['deadline'] - time()) / 86400) + 1;
                                    $dateString = date("Y-m-d", $mission['deadline']);
                                    $plantype = ($mission['plantype'] === '분기목표') ? '<b style="color:purple;">분기목표</b>' : '<b style="color:red;">방향설정</b>';
                                    
                                    echo '<tr>';
                                    echo '<td>'.$plantype.'</td>';
                                    echo '<td>'.iconv_substr($mission['memo'], 0, 70, "utf-8").'</td>';
                                    echo '<td>'.$dateString.'</td>';
                                    echo '<td>D-'.$dday.'</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-route"></i>
                            <p>설정된 분기목표가 없습니다.</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($hideinput == 0 || $role !== 'student'): ?>
                        <?php
                        // Get random dream challenge
                                                
                        $randomDreamList = [
                            "인공지능 개발자",
                            "환경 보호 전문가",
                            "가상현실 게임 디자이너",
                            "우주 탐사자",
                            "유전공학 연구원",
                            "스마트팜 기술자",
                            "해양 생물학자",
                            "신재생 에너지 엔지니어",
                            "드론 파일럿",
                            "사이버 보안 전문가",
                            "데이터 과학자",
                            "로봇공학 기술자",
                            "콘텐츠 크리에이터",
                            "의료 기술 혁신가",
                            "지속 가능한 패션 디자이너",
                            "가상 교육자",
                            "우주 식민지 설계자",
                            "인공장기 개발자",
                            "디지털 마케터",
                            "바이오인포매틱스 전문가",
                            "청정 에너지 컨설턴트",
                            "증강 현실 경험 디자이너",
                            "암호화폐 분석가",
                            "미래학 연구원",
                            "나노기술 엔지니어",
                            "스마트 도시 계획가",
                            "인간-기계 인터페이스 디자이너",
                            "디지털 윤리학자",
                            "양자 컴퓨터 개발자",
                            "자율 주행 차량 엔지니어",
                            "생명공학 연구원",
                            "모바일 앱 개발자",
                            "인공지능 법률 고문",
                            "스페이스 호텔 매니저",
                            "디지털 복원 전문가",
                            "신경과학자",
                            "미생물 에너지 생산자",
                            "스마트 웨어러블 디자이너",
                            "3D 프린팅 전문가",
                            "무인 항공 교통 관리자",
                            "가상 현실 치료사",
                            "블록체인 개발자",
                            "음성 인식 기술 개발자",
                            "클라우드 컴퓨팅 전문가",
                            "인터넷 오브 싱스(IoT) 개발자",
                            "게임 이론 분석가",
                            "스마트 홈 시스템 디자이너",
                            "텔레프레즌스 로봇 조종사",
                            "웨어러블 헬스 기기 개발자",
                            "식품 과학자",
                            "디지털 아트 큐레이터",
                            "생태계 복원 전문가",
                            "미래 도시 건축가",
                            "인공지능 음악 작곡가",
                            "크립토 아트 작가",
                            "전염병 예방 전문가",
                            "심우주 통신 엔지니어",
                            "지속 가능한 관광 개발자",
                            "양자 암호화 전문가",
                            "빅 데이터 분석가",
                            "첨단 농업 기술자",
                            "가상 현실 아키텍트",
                            "뇌-컴퓨터 인터페이스 연구원",
                            "홀로그램 콘텐츠 제작자",
                            "인간 행동 연구원",
                            "테라포밍 엔지니어",
                            "초지능 시스템 디자이너",
                            "멸종 위기 동물 보호 전문가",
                            "스포츠 과학자",
                            "스마트 교통 시스템 개발자",
                            "도시 농업 전문가",
                            "신경 조직 공학자",
                            "모바일 헬스케어 서비스 개발자",
                            "핵융합 에너지 연구원",
                            "글로벌 웜링 해결 전략가",
                            "인터스텔라 메시지 디자이너",
                            "디지털 명상 지도자",
                            "우주 광물학자",
                            "스마트 그리드 기술자",
                            "환경 데이터 과학자",
                            "미래 학교 교육가",
                            "디지털 디톡스 전문가",
                            "가상 동물원 설계자",
                            "스마트 패션 기술자",
                            "항노화 연구원",
                            "비디오 게임 스토리텔러",
                            "지능형 건축 재료 개발자",
                            "마이크로바이옴 연구원",
                            "어반 에어 모빌리티 디자이너",
                            "소셜 미디어 심리학자",
                            "디지털 노마드 컨설턴트",
                            "인공지능 윤리위원",
                            "소리 치유사",
                            "우주 날씨 예보자",
                            "생체 모방 기술 개발자",
                            "디지털 인문학자",
                            "챗봇 스크립트 작가",
                            "스마트 재난 대응 시스템 개발자",
                            "가상 박물관 디자이너",
                            "우주 법률 전문가",
                            "스마트 재활 기기 개발자",
                            "언더워터 호텔 디자이너",
                            "증강 현실 교육 콘텐츠 제작자",
                            "마이크로그래비티 요리사",
                            "우주 쓰레기 관리 전문가",
                            "바이오센서 개발자",
                            "디지털 정신 건강 치료사",
                            "가상 현실 스포츠 코치",
                            "자율주행 자동차 디자이너",
                            "심해 탐사 장비 엔지니어",
                            "지능형 비즈니스 분석가",
                            "클라우드 베이스드 교육 플랫폼 개발자",
                            "소셜 임팩트 투자자",
                            "3D 생체 인쇄 전문가",
                            "스마트 패브릭 디자이너",
                            "어반 푸드 시스템 혁신가",
                            "디지털 저작권 관리자",
                            "글로벌 로지스틱스 최적화 전문가",
                            "공중 부양 교통 시스템 개발자",
                            "식물 기반 식품 과학자",
                            "지속 가능한 도시 농업 설계자",
                            "인간 확장 기술 연구원",
                            "사이버범죄 수사관",
                            "스마트 재난 경보 시스템 개발자",
                            "가상 현실 여행 에이전트",
                            "인공지능 조교",
                            "디지털 포렌식 전문가",
                            "스마트 에너지 저장 솔루션 개발자",
                            "초현실적 예술가",
                            "바이러스 억제 연구원",
                            "가상 인간 상호작용 디자이너",
                            "나노메디슨 연구원",
                            "생태계 기능 디자이너",
                            "양자 통신 전문가",
                            "디지털 아카이브 전문가",
                            "인터랙티브 도서관 컨설턴트",
                            "친환경 건축 자재 개발자",
                            "모바일 결제 시스템 혁신가",
                            "인공지능 기반 교육 컨텐츠 개발자",
                            "미래 의학 연구원",
                            "심리적 건강 모바일 앱 개발자",
                            "공기 정화 기술 개발자",
                            "디지털 농업 컨설턴트",
                            "스마트 헬멧 개발자",
                            "공간 데이터 분석가",
                            "의료용 로봇 기술자",
                            "가상 현실 치료 기기 개발자",
                            "자연어 처리 연구원",
                            "인공 지능 스타일리스트",
                            "우주 관광 가이드",
                            "퍼스널 데이터 프라이버시 어드바이저",
                            "스마트 컨트랙트 개발자",
                            "가상 아이돌 제작자",
                            "지속 가능한 수자원 관리 전문가",
                            "인공지능 기반 퍼스널 쇼퍼",
                            "로우코드 애플리케이션 개발자",
                            "지능형 교통 시스템 분석가",
                            "미세먼지 저감 기술 연구원",
                            "디지털 콘텐츠 권리 관리 전문가",
                            "가상 현실 영화 제작자",
                            "인공지능 화상 회의 퍼실리테이터",
                            "신경망 칩 설계자",
                            "언어학습 앱 개발자",
                            "에코 컨셔스 패션 브랜드 창립자",
                            "디지털 복원 기술자",
                            "소셜 미디어 인플루언서 전략가",
                            "양자 컴퓨팅 애플리케이션 개발자",
                            "스마트 물류 시스템 설계자",
                            "공중보건 위기 대응 전문가",
                            "에코테크 스타트업 창업가",
                            "디지털 이벤트 플래너",
                            "가상 스포츠 리그 관리자",
                            "인공지능 법률 분석가",
                            "심해 연구 및 탐사 전문가",
                            "우주 농업 연구원",
                            "공간정보 시스템 개발자",
                            "첨단 의료 이미징 기술자",
                            "자동화 테스트 엔지니어",
                            "스마트 시티 보안 전문가",
                            "가상 교실 교육 기획자",
                            "디지털 장례 서비스 제공자",
                            "우주 환경 엔지니어",
                            "스타트업 인큐베이터 멘토",
                            "가상 현실 기반 심리 치료사",
                            "에너지 효율성 컨설턴트",
                            "스마트 센서 네트워크 개발자",
                            "게이미피케이션 전략가",
                            "빛 오염 해결 전문가",
                            "디지털 노마드 커뮤니티 매니저",
                            "지속 가능한 에너지 솔루션 디자이너",
                            "인공지능 기반 식물 성장 모니터",
                            "무인 배송 시스템 운영자",
                            "디지털 감정 표현 연구원",
                            "핀테크 솔루션 개발자",
                            "스마트 건축물 에너지 관리자",
                            "가상 현실 컨텐츠 큐레이터",
                            "생체모방 로봇 디자이너",
                            "디지털 건강 모니터링 시스템 개발자",
                            "우주 관측 데이터 분석가",
                            "바이오디지털 콘텐츠 크리에이터",
                            "스마트 의복 제작자",
                            "가상 현실 테마파크 디자이너",
                            "디지털 웰빙 코치",
                            "지속 가능한 에코빌리지 개발자",
                            "식용 곤충 농장 운영자",
                            "해저 도시 건축가",
                            "인공지능 재난 대응 조정자",
                            "스페이스 데브리 클리너",
                            "스마트 도로 시스템 설계자",
                            "바이오필릭 디자인 컨설턴트",
                            "디지털 유산 컨설턴트",
                            "사이버펑크 소설가",
                            "미래식 식단 개발자",
                            "가상 패션 쇼 오거나이저",
                            "스마트 공기질 모니터",
                            "우주 식량 생산자",
                            "생체 적응형 게임 개발자",
                            "디지털 통화 디자이너",
                            "마이크로리빙 공간 디자이너",
                            "가상 현실 교육 컨텐츠 개발자",
                            "빛 기반 통신 기술자",
                            "디지털 유물 보존 전문가",
                            "인공지능 기반 작곡가",
                            "바이오메트릭 데이터 분석가",
                            "3D 프린트 의류 디자이너",
                            "윤리적 AI 개발자",
                            "스마트 약물 전달 시스템 디자이너",
                            "재생 가능 에너지 벤처 캐피털리스트",
                            "초연결 사회 분석가",
                            "스팀(STEM) 교육 콘텐츠 크리에이터",
                            "가상 현실 심리 치료 연구원",
                            "환경 데이터 비주얼라이제이션 전문가",
                            "나노봇 연구 개발자",
                            "스마트 교통 체계 해커",
                            "지속 가능한 관광 기획자",
                            "어린이를 위한 프로그래밍 교육가",
                            "증강 현실 쇼핑 어드바이저",
                            "인터랙티브 디지털 아트워크 크리에이터",
                            "모바일 건강 진단 개발자",
                            "디지털 콘텐츠 저작권 관리자",
                            "로봇 윤리 컨설턴트", 
                            "스마트 시티 데이터 분석가",
                            "퍼소널 브랜딩 전문가",
                            "가상 현실 피트니스 트레이너",
                            "홀로그래픽 데이터 시각화 전문가",
                            "사이버 안전 교육가",
                            "디지털 음악 배포자",
                            "클라우드 기반 팀워크 플랫폼 개발자",
                            "인공지능 패션 컨설턴트",
                            "미래 도시 생활 컨설턴트",
                            "디지털 인권 변호사",
                            "가상 실감 콘텐츠 프로듀서",
                            "친환경 건축 기술자",
                            "인공지능 기반 도시 계획가",
                            "식물 기반 식품 혁신가",
                            "스마트 장난감 개발자",
                            "지속 가능한 생활 스타일 코치",
                            "소셜 미디어 데이터 분석가",
                            "초소형 위성 개발자",
                            "디지털 북 큐레이터",
                            "가상 현실 미술관 큐레이터",
                            "스마트 환경 모니터링 시스템 개발자",
                            "바이오피드백 테라피스트",
                            "우주 여행 가이드",
                            "심해 탐사 기술 개발자",
                            "디지털 윤리 컨설턴트",
                            "가상 멘토링 서비스 개발자",
                            "스마트 시티 생활 실험가",
                            "에너지 하베스팅 기술 연구원",
                            "사이버펑크 게임 디자이너",
                            "가상 현실 치료 연구 개발자",
                            "인공지능 기반 개인 건강 조언가",
                            "지속 가능한 패션 블로거",
                            "디지털 보안 컨설턴트",
                            "3D 바이오 프린팅 연구원",
                            "자율주행 도시 버스 시스템 디자이너",
                            "가상 현실 역사 교육가",
                            "인터넷 사물(IoT) 장난감 디자이너",
                            "스마트 농업 컨설턴트",
                            "로봇 공학 교육 전문가",
                            "디지털 인문학 연구자",
                            "가상 현실 스포츠 분석가",
                            "스마트 워터 관리 시스템 엔지니어",
                            "인공지능 기반 아트 테라피스트",
                            "지구 외 생명체 연구원",
                            "디지털 정체성 보호 전문가",
                            "자연 언어 처리 기술 개발자",
                            "가상 현실 여행 기획자",
                            "바이오리듬 분석가",
                            "스마트 교육 플랫폼 개발자",
                            "디지털 푸드 디자이너",
                            "가상 현실 콘서트 기획자",
                            "실시간 데이터 분석가",
                            "스마트 건강 진단 키트 개발자",
                            "인공지능 기반 재난 경보 시스템 개발자",
                            "디지털 커뮤니티 매니저",
                            "친환경 도시 디자인 전문가",
                            "가상 현실 교통 시스템 설계자",
                            "디지털 자산 관리자",
                            "스마트 홈 인테리어 디자이너"
                        ];
                        $randomDreamUrlList = [
                        "https://gamma.app/docs/-5dvdwrou2385tda",
                        "https://gamma.app/docs/-57oe1106fexvovx",
                        "https://gamma.app/docs/-w060d7y8nzrq6z1",
                        "https://gamma.app/docs/-xl03qnlzbhw0l3d",
                        "https://gamma.app/docs/Untitled-ekp8hywee87lsw8",
                        "https://gamma.app/docs/-ggn6grxhpvp0tdj",
                        "https://gamma.app/docs/-xieocbvr1u6hyd0",
                        "https://gamma.app/docs/-lp6kn8pqg1aqmec",
                        "https://gamma.app/docs/-fsuhnwucw8546bj",
                        "https://gamma.app/docs/-t55yu127yjsi9fo",
                        "https://gamma.app/docs/-8sln8zzhe487myk",
                        "https://gamma.app/docs/-62mq1zcgmekj0xw",
                        "https://gamma.app/docs/-80707aa8tnf1d8u",
                        "https://gamma.app/docs/-kep6ua7le4tcsup",
                        "https://gamma.app/docs/-xhdx8mkbak325bj",
                        "https://gamma.app/docs/-x9nfq80il9glyiz",
                        "https://gamma.app/docs/-020t0h8i64qt3ji",
                        "https://gamma.app/docs/-m3j16vvgfw4c2c3",
                        "https://gamma.app/docs/-o6e5u148e9n3hy0",
                        "https://gamma.app/docs/-vf3my60eukzau3p",
                        "https://gamma.app/docs/-s7945kxk45fptap",
                        "https://gamma.app/docs/-eatbhq1xto25lmc",
                        "https://gamma.app/docs/-ar1ok42v4guq3gr",
                        "https://gamma.app/docs/-vmhpuzstpj6z9iv",
                        "https://gamma.app/docs/-0vp4rijjzmxr5lb",
                        "https://gamma.app/docs/-xp3lp0v1pldkxke",
                        "https://gamma.app/docs/-irf6r12mpq21jxw",
                        "https://gamma.app/docs/-7lcr5rezdf6k9br",
                        "https://gamma.app/docs/-8u0i6dikdcq7r8q",
                        "https://gamma.app/docs/-8gfvga11by9e2so",
                        "https://gamma.app/docs/-bjb3fkradx5emgg",
                        "https://gamma.app/docs/-786otp42dq41g6i",
                        "https://gamma.app/docs/-s8ls52dgg1afk60",
                        "https://gamma.app/docs/-l1sbevclt9fnm2g",
                        "https://gamma.app/docs/-ojj0fz3q639r666",
                        "https://gamma.app/docs/-2i5ufv5j73nw010",
                        "https://gamma.app/docs/-y89z5ysjvw5292q",
                        "https://gamma.app/docs/-yuie5rba52v21os",
                        "https://gamma.app/docs/3D--ogt66n18dhu18ug",
                        "https://gamma.app/docs/-85vj1hcg4t3gk5a",
                        "https://gamma.app/docs/-gaycqrijcv024kp",
                        "https://gamma.app/docs/-d9c1i0e27m95mgi",
                        "https://gamma.app/docs/-fues7156ylaywrl",
                        "https://gamma.app/docs/-lt5ywf8tlrtqy96",
                        "https://gamma.app/docs/IoT--k5eard364ar18s2",
                        "https://gamma.app/docs/-jpm4pqw09kavgmn",
                        "https://gamma.app/docs/-aglumil3f2fhsyr",
                        "https://gamma.app/docs/-kxaz0e1sdoa7v3o",
                        "https://gamma.app/docs/-woyqxqy2jslwpn5",
                        "https://gamma.app/docs/-76e8minqsvpg0cy",
                        "https://gamma.app/docs/-0ieun0b7ocwfbne",
                        "https://gamma.app/docs/-1f6svi6cdmz504q",
                        "https://gamma.app/docs/-vqfbi2u1hoji2el",
                        "https://gamma.app/docs/-im8xxfov6cnhihy",
                        "https://gamma.app/docs/-mibiqp8hcuu7awc",
                        "https://gamma.app/docs/-bmarhtojhahq1j1",
                        "https://gamma.app/docs/-p2hfkaafbsm16hl",
                        "https://gamma.app/docs/-y8kdy750rryglya",
                        "https://gamma.app/docs/-7xlekxf04ouvn0d",
                        "https://gamma.app/docs/-gy5salsqbe1aclw",
                        "https://gamma.app/docs/-yn0m1sxume2atmu",
                        "https://gamma.app/docs/-l9o8mxlxbxnd857",
                        "https://gamma.app/docs/-pfvjxxck7buzkb3",
                        "https://gamma.app/docs/-9ys3rl17dte5han",
                        "https://gamma.app/docs/-va3ahhi49o4zt1y",
                        "https://gamma.app/docs/-yjt635pommyqnjw",
                        "https://gamma.app/docs/-smo5bdqm2kiim3i",
                        "https://gamma.app/docs/-0ogmzeyq5nzsgmx",
                        "https://gamma.app/docs/-23cqvaztlrgmhet",
                        "https://gamma.app/docs/-c8yqn0opzp4sf1i",
                        "https://gamma.app/docs/-irvfx6onndwlzsf",
                        "https://gamma.app/docs/-gdu3cpvjsatdjui",
                        "https://gamma.app/docs/-ji0vwzrqkbikrmn",
                        "https://gamma.app/docs/-qa8mndk27l5aomo",
                        "https://gamma.app/docs/-bur9fxba6i1x8d1",
                        "https://gamma.app/docs/-hzvnowwvabccbwq",
                        "https://gamma.app/docs/-r1o4o6i2epbkqca",
                        "https://gamma.app/docs/-3sztxs20giuz113",
                        "https://gamma.app/docs/-dw9yjujsfyxc6nf",
                        "https://gamma.app/docs/-arxf1nb6oc3cd90",
                        "https://gamma.app/docs/-0xdhc2gct6w50ex",
                        "https://gamma.app/docs/-sdxz58fnmthdzne",
                        "https://gamma.app/docs/-ow67c0m0cc2hz9w",
                        "https://gamma.app/docs/-s9yyaztanyp8jmm",
                        "https://gamma.app/docs/-m1di07ecxkzaci9",
                        "https://gamma.app/docs/-9wjv8fwtckqlslo",
                        "https://gamma.app/docs/-qzw0tepi62lt9mw",
                        "https://gamma.app/docs/-ek53gbeha0ddxpt",
                        "https://gamma.app/docs/-pd2cmjyv0g1zgdn",
                        "https://gamma.app/docs/-jdk8ofesnbubh3x",
                        "https://gamma.app/docs/-5z90lqmihqelfee",
                        "https://gamma.app/docs/-z09uxt4wt06t0yj",
                        "https://gamma.app/docs/-hpudiex8evcard0",
                        "https://gamma.app/docs/-35w0x4e4sh6e1kj",
                        "https://gamma.app/docs/-99kuwwh41xp7ekb",
                        "https://gamma.app/docs/-n5m1rxp195f7i2g",
                        "https://gamma.app/docs/-sazybl9byoh1fyg",
                        "https://gamma.app/docs/-974u0unjy1rqelq",
                        "https://gamma.app/docs/-jvjeu9uwc0ftmkh",
                        "https://gamma.app/docs/-hpp1f3azv2r349x",
                        "https://gamma.app/docs/-4aqckebehpskl59",
                        "https://gamma.app/docs/-zrml04adt5wey73",
                        "https://gamma.app/docs/-kl1sb32tn0sxewh",
                        "https://gamma.app/docs/-zfwln3s9ugm0evt",
                        "https://gamma.app/docs/-uwgll8wuguxfbmw",
                        "https://gamma.app/docs/-5alqycnuvc19f6r",
                        "https://gamma.app/docs/-ok9kxdjxygn3rvc",
                        "https://gamma.app/docs/-gsrtc9l54d0pqnr",
                        "https://gamma.app/docs/-qi1vcxkpezgvke7",
                        "https://gamma.app/docs/-ov1qo1vsw4x8uui",
                        "https://gamma.app/docs/-zngnj3lpxotv04u",
                        "https://gamma.app/docs/-nwcwn0b225b7bca",
                        "https://gamma.app/docs/-furx4dgvbi4xf51",
                        "https://gamma.app/docs/3D--ean6ri9hgok5n95",
                        "https://gamma.app/docs/-ehs98d8rlqy8pmg",
                        "https://gamma.app/docs/-thv0e2qqiie28s9",
                        "https://gamma.app/docs/-sk1ylzw8j4l9l39",
                        "https://gamma.app/docs/-euslasa7gfuxrku",
                        "https://gamma.app/docs/-s4wtoj4o6rqnopc",
                        "https://gamma.app/docs/-780pgeei0qx25h8",
                        "https://gamma.app/docs/-44wyuyxioo7277f",
                        "https://gamma.app/docs/-w0e2gg0nvmecf0r",
                        "https://gamma.app/docs/-n0ecytk4ir2l3q0",
                        "https://gamma.app/docs/-tl4ev3qjscvno36",
                        "https://gamma.app/docs/-9o6p0jm95ma09rc",
                        "https://gamma.app/docs/-xr2qnk3sp6vajso",
                        "https://gamma.app/docs/-v5814mccretdisl",
                        "https://gamma.app/docs/-zm5sxdwve0dfy1w",
                        "https://gamma.app/docs/-tej2n6x0lrcn6jh",
                        "https://gamma.app/docs/-a9rti7t9r8ftoz8",
                        "https://gamma.app/docs/-g1fcwyjgqurig5p",
                        "https://gamma.app/docs/-cerh1y5s7ahqhb8",
                        "https://gamma.app/docs/-vigfsykbazobo0f",
                        "https://gamma.app/docs/-fbw4ghwx9ykckrs",
                        "https://gamma.app/docs/-y1np44iewv8dc3i",
                        "https://gamma.app/docs/-rbasvcsnn7ubb0n",
                        "https://gamma.app/docs/-eqk70dczaysywqm",
                        "https://gamma.app/docs/-zfq2iycdrlgi8ei",
                        "https://gamma.app/docs/AI--70up9dn6u4w2qif",
                        "https://gamma.app/docs/-av83z8lubexyvau",
                        "https://gamma.app/docs/-n3vbdyrqcwfgmr4",
                        "https://gamma.app/docs/-0pyfsqapoinpe5e",
                        "https://gamma.app/docs/-rcret9petbw6j4u",
                        "https://gamma.app/docs/-88y7o3m0tegcyaf",
                        "https://gamma.app/docs/-0dz3tdtve83hj9e",
                        "https://gamma.app/docs/-ar3wpbiecpqwt7t",
                        "https://gamma.app/docs/-1llco26yb7574s9",
                        "https://gamma.app/docs/-3jpj0s3zrbge35w",
                        "https://gamma.app/docs/-fo7aqkpv03my2h1",
                        "https://gamma.app/docs/-48o1dsqqg2tfzke",
                        "https://gamma.app/docs/-smrrs3k0xbb4f8c",
                        "https://gamma.app/docs/-40oys8w4o3iomcg",
                        "https://gamma.app/docs/-u42vb63744f7tbf",
                        "https://gamma.app/docs/-ayupuc51t4mqk8g",
                        "https://gamma.app/docs/-bwm6i1s2w4zoqy6",
                        "https://gamma.app/docs/-l8w49otlnl6op6m",
                        "https://gamma.app/docs/-wq5duc8l59bc3m4",
                        "https://gamma.app/docs/-no3473h2otca72v",
                        "https://gamma.app/docs/-tk01witpmfknxcs",
                        "https://gamma.app/docs/-zh0dqtrvekx5dgw",
                        "https://gamma.app/docs/-c0o5fptdmgb6qui",
                        "https://gamma.app/docs/-5wxo1qeix524i00",
                        "https://gamma.app/docs/-hgz318oy0i3z5py",
                        "https://gamma.app/docs/-5a7holiv5a8kots",
                        "https://gamma.app/docs/-s6by5uwvo4md71m",
                        "https://gamma.app/docs/-nfacws7qmo90whm",
                        "https://gamma.app/docs/-8yisry5lvbwa276",
                        "https://gamma.app/docs/-m0mvyrbgsp6i0id",
                        "https://gamma.app/docs/-zsqtr12lzs915bx",
                        "https://gamma.app/docs/-ddwaym785qvf7jz",
                        "https://gamma.app/docs/-1mz9nx0x3y5u71t",
                        "https://gamma.app/docs/-u8ofrmkyde0ywvg",
                        "https://gamma.app/docs/-ld75wf9wiurtivi",
                        "https://gamma.app/docs/-tht5mo8sebz6qoq",
                        "https://gamma.app/docs/-ku4hffhzfauxnr4",
                        "https://gamma.app/docs/-vrjfv2r6nhczroi",
                        "https://gamma.app/docs/-6oj9cd457ci3bbp",
                        "https://gamma.app/docs/-2dipwswg7b1ialm",
                        "https://gamma.app/docs/-l9wmjx25ra15uve",
                        "https://gamma.app/docs/-v1njqusg5df74iq",
                        "https://gamma.app/docs/-1p339xhawye47sk",
                        "https://gamma.app/docs/-0tn3lev1b2j53q0",
                        "https://gamma.app/docs/-wnaqow3l2w184y9",
                        "https://gamma.app/docs/-sirxql7pzrtjn0y",
                        "https://gamma.app/docs/-mqzjq5h0g6b0s4h",
                        "https://gamma.app/docs/-5o516w25he0czvm",
                        "https://gamma.app/docs/-x6dk4b3omffsu6s",
                        "https://gamma.app/docs/-j3442t7fphfkzes",
                        "https://gamma.app/docs/-2nbehuf6v0klncz",
                        "https://gamma.app/docs/-ukdtlnqska8shc6",
                        "https://gamma.app/docs/-7jtolc4vsruchqd",
                        "https://gamma.app/docs/-a0eahumuaiob698",
                        "https://gamma.app/docs/-f73jlwiaus04tw8",
                        "https://gamma.app/docs/-coably0qug18ude",
                        "https://gamma.app/docs/-hci0vqp1xpelbe2",
                        "https://gamma.app/docs/-otq04ruv3f5a05i",
                        "https://gamma.app/docs/-z7aggnyryk7x3tu",
                        "https://gamma.app/docs/-6f4p9sm2n9ztwiu",
                        "https://gamma.app/docs/-w4puioqbeub828a",
                        "https://gamma.app/docs/-2lcpwhk99phlw7g",
                        "https://gamma.app/docs/-f9z76ssyqhlizrj",
                        "https://gamma.app/docs/-0bsoaujm17p6dal",
                        "https://gamma.app/docs/-0prfrtnuwl0s9e0",
                        "https://gamma.app/docs/-8bee923pa12g5mj",
                        "https://gamma.app/docs/-3dw8qbzqww3zc0k",
                        "https://gamma.app/docs/-k5rcd050v4nta1h",
                        "https://gamma.app/docs/-t4j0ezy2u4dnhqr",
                        "https://gamma.app/docs/-soi31stkix1f7y3",
                        "https://gamma.app/docs/-o9wxhxm1nw9sma5",
                        "https://gamma.app/docs/-5z14zciln3u2b8h",
                        "https://gamma.app/docs/-5u8cv8qubldmoan",
                        "https://gamma.app/docs/-odj6m1jh5p76bah",
                        "https://gamma.app/docs/-ujm1q396y91mih8",
                        "https://gamma.app/docs/-jfgosssv4y92wg2",
                        "https://gamma.app/docs/-dtm0jtyflgnybmf",
                        "https://gamma.app/docs/-g8djh80xbasd2kq",
                        "https://gamma.app/docs/-mlse7lpwkmt1aga",
                        "https://gamma.app/docs/-drffurx6tt3sjtd",
                        "https://gamma.app/docs/-pmmly3etukq8eyy",
                        "https://gamma.app/docs/-sb73aoic39wpdev",
                        "https://gamma.app/docs/-37lc8t3ajx09xyq",
                        "https://gamma.app/docs/-0f6alctotc8kdg8",
                        "https://gamma.app/docs/-xtiil4tuhmynq73",
                        "https://gamma.app/docs/-z9s4904gru83euq",
                        "https://gamma.app/docs/3D--b78zrohehtx1soq",
                        "https://gamma.app/docs/AI--l0xk4jegi5zelfd",
                        "https://gamma.app/docs/-mcyeio63ohaaxc8",
                        "https://gamma.app/docs/-2bxqnz8sr2y6k7q",
                        "https://gamma.app/docs/-hk1d6usnb86kmur",
                        "https://gamma.app/docs/STEM--t0o671d8jcl7hh6",
                        "https://gamma.app/docs/-lq0e6hji6y0hf11",
                        "https://gamma.app/docs/-o59uc1nem7kdapn",
                        "https://gamma.app/docs/-fcplxerug5qktrb",
                        "https://gamma.app/docs/-vqz9xycvlct18hi",
                        "https://gamma.app/docs/-i1uhs4bhr3m8w52",
                        "https://gamma.app/docs/-dzjbyjipbck9xkd",
                        "https://gamma.app/docs/-t9pwhbcgef0ay0b",
                        "https://gamma.app/docs/-xz1jb3ndll6nwwm",
                        "https://gamma.app/docs/-klo0zim3gda2bkg",
                        "https://gamma.app/docs/-6zu5oowhwcjqyta",
                        "https://gamma.app/docs/-7r5wooqdp1lup83",
                        "https://gamma.app/docs/-r8fe3krrcirtbr2",
                        "https://gamma.app/docs/-5w47hzlhmksor8x",
                        "https://gamma.app/docs/-hea7rkt8c75xsz9",
                        "https://gamma.app/docs/-bidqj1suf8wjxxg",
                        "https://gamma.app/docs/-ea8qnwtiqzxkycd",
                        "https://gamma.app/docs/-c7z4xxrfk8nfsaa",
                        "https://gamma.app/docs/-fr84pplewqmq5y4",
                        "https://gamma.app/docs/-poan9q9ti03458y",
                        "https://gamma.app/docs/-jopl4d6mcjp96ng",
                        "https://gamma.app/docs/-czm4xyvwa8crhrt",
                        "https://gamma.app/docs/-ihfgzhbcarh10q0",
                        "https://gamma.app/docs/-ibaakdxo12f4u2b",
                        "https://gamma.app/docs/-yr9ubk8zgqxem7q",
                        "https://gamma.app/docs/-l9tovcnqzjaej07",
                        "https://gamma.app/docs/-y842ux8dzrdg3id",
                        "https://gamma.app/docs/-uit55beir3cz4p9",
                        "https://gamma.app/docs/-kub6tvvn0oerko2",
                        "https://gamma.app/docs/-p41y9kcg0yrq7wu",
                        "https://gamma.app/docs/-qthgzsigpuvryzb",
                        "https://gamma.app/docs/-piceqvzb261cii2",
                        "https://gamma.app/docs/-3hjhkq9r58mjfv5",
                        "https://gamma.app/docs/-k1yb8827tf7qmy2",
                        "https://gamma.app/docs/-n9zsk22ad7hts5j",
                        "https://gamma.app/docs/-0oo8x9wyg4vfchh",
                        "https://gamma.app/docs/-edvqzvsoyty1h0o",
                        "https://gamma.app/docs/-6iz6f0iix4psp9e",
                        "https://gamma.app/docs/-lo1n49f498u6sbm",
                        "https://gamma.app/docs/-im201p4ih10xfo2",
                        "https://gamma.app/docs/-taqkek9v5260m6d",
                        "https://gamma.app/docs/-m7eqz1zlf2sjo1r",
                        "https://gamma.app/docs/-rof8j779av6x6bg",
                        "https://gamma.app/docs/-4qnc6omk3d9k0au",
                        "https://gamma.app/docs/-5lemyq26jesegle",
                        "https://gamma.app/docs/3D--vredyazv3l3ixca",
                        "https://gamma.app/docs/-qgf9tnshsruhxtp",
                        "https://gamma.app/docs/-9w47pwn4dxdbkkb",
                        "https://gamma.app/docs/IoT--u8rh591u9o3oawd",
                        "https://gamma.app/docs/-ldsxn2i7r3z5koi",
                        "https://gamma.app/docs/-qij7be7s7fk0wgw",
                        "https://gamma.app/docs/-t6ihe80b0il2s2i",
                        "https://gamma.app/docs/-6gd9bw5reyff55x",
                        "https://gamma.app/docs/-pkovx47mw4di70k",
                        "https://gamma.app/docs/-rsq3538k9a3ke54",
                        "https://gamma.app/docs/-fwie4lhsndundlh",
                        "https://gamma.app/docs/-0quvqevx9znbthk",
                        "https://gamma.app/docs/-hks23k6es0smskr",
                        "https://gamma.app/docs/-pdyuwwav7huqhlr",
                        "https://gamma.app/docs/-oq70dh1r7uemiig",
                        "https://gamma.app/docs/-eq5k6uhrw786li0",
                        "https://gamma.app/docs/-iwj56vtf9h11ixg",
                        "https://gamma.app/docs/-7tsttez08fxgdpx",
                        "https://gamma.app/docs/-fym0tsusvwsnb42",
                        "https://gamma.app/docs/-3kuckp7o9dcgoxt",
                        "https://gamma.app/docs/-w2pmd490v9p8fq1",
                        "https://gamma.app/docs/-oyve39k43dddtkx",
                        "https://gamma.app/docs/-18uti4ah6wddwha",
                        "https://gamma.app/docs/-ksvto4dpib2ka5l",
                        "https://gamma.app/docs/-5pgvq8vxgdy7tmf",
                        "https://gamma.app/docs/-ea7j989edc17xrk"
                        ];
                        // 기존에 저장된 꿈 챌린지 확인
                        $savedDream = $DB->get_record_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
                        
                        if($savedDream && $savedDream->dreamchallenge !== NULL && $savedDream->dreamchallenge !== '') {
                            // 저장된 꿈이 있으면 그것을 사용
                            $randomDream = $savedDream->dreamchallenge;
                            $randomDreamUrl = $savedDream->dreamurl ? $savedDream->dreamurl : $randomDreamUrlList[0];
                        } else {
                            // 저장된 꿈이 없으면 랜덤 선택
                            $index = array_rand($randomDreamList);
                            $randomDream = $randomDreamList[$index];
                            $randomDreamUrl = $randomDreamUrlList[$index];
                        }
                        ?>
                        <div class="goal-form" style="margin-top: 20px;">
                            <h4 style="margin-bottom: 15px;">분기목표 설정</h4>
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <input type="text" class="form-control" id="quarterGoalInput" placeholder="선생님과 상의하여 다음 분기까지의 목표를 입력해 주세요">
                                </div>
                                <div class="form-group">
                                    <input type="date" class="form-control" id="quarterDeadline" value="<?php echo date('Y-m-d', time() + 86400 * 90); ?>">
                                </div>
                                <div class="form-group">
                                    <button class="btn-submit" onclick="inputgoalstep(8, <?php echo $studentid; ?>, '분기목표', document.getElementById('quarterDeadline').value, document.getElementById('quarterGoalInput').value, document.getElementById('randomDreamText').innerText, currentDreamUrl)">저장</button>
                                </div>
                            </div>
                            <div style="margin-top: 10px; padding: 10px; background: rgba(51, 131, 255, 0.1); border-radius: 8px;">
                                <p style="margin: 0; font-size: 14px; color: var(--info-color); display: flex; align-items: center; justify-content: space-between;">
                                    <span>
                                        🌟 랜덤 꿈 챌린지: <strong id="randomDreamText"><?php echo $randomDream; ?></strong>
                                        <a href="<?php echo $randomDreamUrl; ?>" target="_blank" style="margin-left: 10px; font-size: 12px; color: var(--info-color);" id="dreamDetailLink">자세히 보기</a>
                                    </span>
                                    <button type="button" class="btn btn-sm" style="background: var(--info-color); color: white; border: none; padding: 4px 12px; border-radius: 4px; font-size: 12px;" onclick="changeRandomDream()">바꾸기</button>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 테스트현황 Tab -->
            <div id="quiz" class="tab-content">
                <!-- Stats Overview -->
                <div class="stats-box">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $nquiz; ?></div>
                        <div class="stat-label">전체 퀴즈</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $ntodayquiz; ?></div>
                        <div class="stat-label">오늘 완료</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $avgToday; ?>점</div>
                        <div class="stat-label">오늘 평균</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $avgWeek; ?>점</div>
                        <div class="stat-label">주간 평균</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="section-header info">
                        <span>내신 테스트</span>
                    </div>
                    <div class="card-body">
                        <?php if($quizlist11 || $quizlist12): ?>
                        <h4>오늘 완료</h4>
                        <?php if($quizlist11): ?>
                        <table class="data-table">
                            <tbody>
                                <?php echo $quizlist11; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                        
                        <?php if($quizlist12): ?>
                        <h4 style="margin-top: 20px;">이전 기록</h4>
                        <table class="data-table">
                            <tbody>
                                <?php echo $quizlist12; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>내신 테스트 기록이 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="section-header warning">
                        <span>표준 테스트</span>
                    </div>
                    <div class="card-body">
                        <?php if($quizlist21 || $quizlist22): ?>
                        <?php if($quizlist21): ?>
                        <h4>오늘 완료</h4>
                        <table class="data-table">
                            <tbody>
                                <?php echo $quizlist21; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                        
                        <?php if($quizlist22): ?>
                        <h4 style="margin-top: 20px;">이전 기록</h4>
                        <table class="data-table">
                            <tbody>
                                <?php echo $quizlist22; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap"></i>
                            <p>표준 테스트 기록이 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll View Container -->
        <div id="scrollView" class="scroll-container">
            <!-- 오늘 목표 섹션 -->
            <div class="card">
                <div class="section-header primary">
                    <span>오늘 목표</span>
                </div>
                <div class="card-body">
                    <h3 style="margin-bottom: 20px;">오늘 목표</h3>
                    <?php if($todayGoal): ?>
                    <p style="font-size: 18px; margin-bottom: 20px;"><?php echo $todayGoal->text; ?></p>
                    <?php else: ?>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">오늘 목표가 설정되지 않았습니다.</p>
                    <?php endif; ?>
                    
                    <?php if($hideinput == 0 || $role !== 'student'): ?>
                    <div class="goal-form">
                        <h4 style="margin-bottom: 15px;">오늘 목표 설정</h4>
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <input type="text" class="form-control" id="todayGoalInput2" placeholder="오늘의 목표를 입력해 주세요">
                            </div>
                            <div class="form-group">
                                <button class="btn-submit" onclick="saveTodayGoal2()">저장</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 분기목표 섹션 -->
            <div class="card">
                <div class="section-header success">
                    <span>분기목표</span>
                </div>
                <div class="card-body">
                    <?php if(count($missionresult) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="20%">유형</th>
                                <th width="50%">목표</th>
                                <th width="20%">데드라인</th>
                                <th width="10%">D-Day</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($missionresult as $mission) {
                                $dday = round(($mission['deadline'] - time()) / 86400) + 1;
                                $dateString = date("Y-m-d", $mission['deadline']);
                                $plantype = ($mission['plantype'] === '분기목표') ? '<b style="color:purple;">분기목표</b>' : '<b style="color:red;">방향설정</b>';
                                
                                echo '<tr>';
                                echo '<td>'.$plantype.'</td>';
                                echo '<td>'.iconv_substr($mission['memo'], 0, 70, "utf-8").'</td>';
                                echo '<td>'.$dateString.'</td>';
                                echo '<td>D-'.$dday.'</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-route"></i>
                        <p>설정된 분기목표가 없습니다.</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($hideinput == 0 || $role !== 'student'): ?>
                    <?php
                    // 스크롤 뷰에서도 같은 랜덤 꿈 데이터 사용
                    ?>
                    <div class="goal-form" style="margin-top: 20px;">
                        <h4 style="margin-bottom: 15px;">분기목표 설정</h4>
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <input type="text" class="form-control" id="quarterGoalInput2" placeholder="선생님과 상의하여 다음 분기까지의 목표를 입력해 주세요">
                            </div>
                            <div class="form-group">
                                <input type="date" class="form-control" id="quarterDeadline2" value="<?php echo date('Y-m-d', time() + 86400 * 90); ?>">
                            </div>
                            <div class="form-group">
                                <button class="btn-submit" onclick="inputgoalstep(8, <?php echo $studentid; ?>, '분기목표', document.getElementById('quarterDeadline2').value, document.getElementById('quarterGoalInput2').value, document.getElementById('randomDreamText2').innerText, currentDreamUrl)">저장</button>
                            </div>
                        </div>
                        <div style="margin-top: 10px; padding: 10px; background: rgba(51, 131, 255, 0.1); border-radius: 8px;">
                            <p style="margin: 0; font-size: 14px; color: var(--info-color); display: flex; align-items: center; justify-content: space-between;">
                                <span>
                                    🌟 랜덤 꿈 챌린지: <strong id="randomDreamText2"><?php echo $randomDream; ?></strong>
                                    <a href="<?php echo $randomDreamUrl; ?>" target="_blank" style="margin-left: 10px; font-size: 12px; color: var(--info-color);" id="dreamDetailLink2">자세히 보기</a>
                                </span>
                                <button type="button" class="btn btn-sm" style="background: var(--info-color); color: white; border: none; padding: 4px 12px; border-radius: 4px; font-size: 12px;" onclick="changeRandomDream2()">바꾸기</button>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 퀴즈 현황 섹션 -->
            <div class="card">
                <div class="section-header info">
                    <span>테스트 현황</span>
                </div>
                <div class="card-body">
                    <div class="stats-box">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $nquiz; ?></div>
                            <div class="stat-label">전체 퀴즈</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $ntodayquiz; ?></div>
                            <div class="stat-label">오늘 완료</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $avgToday; ?>점</div>
                            <div class="stat-label">오늘 평균</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $avgWeek; ?>점</div>
                            <div class="stat-label">주간 평균</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Core JS Files -->
<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// View Switching Functions
function switchView(viewType) {
    const tabView = document.getElementById('tabView');
    const scrollView = document.getElementById('scrollView');
    const viewBtns = document.querySelectorAll('.view-btn');
    
    viewBtns.forEach(btn => btn.classList.remove('active'));
    
    if(viewType === 'tab') {
        tabView.classList.add('active');
        scrollView.classList.remove('active');
        viewBtns[0].classList.add('active');
        localStorage.setItem('goalsPreferredView', 'tab');
    } else {
        tabView.classList.remove('active');
        scrollView.classList.add('active');
        viewBtns[1].classList.add('active');
        localStorage.setItem('goalsPreferredView', 'scroll');
    }
}

// Tab Switching Functions
function openTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabContents.forEach(content => content.classList.remove('active'));
    tabBtns.forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    
    const activeBtn = Array.from(tabBtns).find(btn => 
        btn.textContent.includes(getTabTitle(tabName))
    );
    if(activeBtn) activeBtn.classList.add('active');
    
    localStorage.setItem('goalsActiveTab', tabName);
}

function getTabTitle(tabName) {
    const titles = {
        'today': '오늘목표',
        'weekly': '주간목표',
        'roadmap': '분기목표',
        'quiz': '테스트현황'
    };
    return titles[tabName] || tabName;
}

// Save Today Goal Function
function saveTodayGoal() {
    const goalInput = document.getElementById('todayGoalInput').value;
    
    if(!goalInput) {
        Swal.fire('알림', '오늘의 목표를 입력해주세요.', 'warning');
        return;
    }
    
    Swal.fire({
        text: '오늘 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "오늘목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d'); ?>"
        },
        success: function(data) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}

// Save Weekly Goal Function
function saveWeeklyGoal() {
    const goalInput = document.getElementById('weeklyGoalInput').value;
    
    if(!goalInput) {
        Swal.fire('알림', '주간 목표를 입력해주세요.', 'warning');
        return;
    }
    
    Swal.fire({
        text: '주간 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "주간목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d', time() + 604800); ?>"
        },
        success: function(data) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}

// Save Quarter Goal Function
function saveQuarterGoal(randomDream) {
    const goalInput = document.getElementById('quarterGoalInput').value;
    const deadline = document.getElementById('quarterDeadline').value;
    
    if(!goalInput) {
        Swal.fire('알림', '분기 목표를 입력해주세요.', 'warning');
        return;
    }
    
    Swal.fire({
        text: '분기목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    // Save to mdl_abessi_progress table like roadmap.php does
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 8,  // Event ID for mission/progress
            "userid": <?php echo $studentid; ?>,
            "plantype": "분기목표",
            "deadline": deadline,
            "memo": goalInput,
            "dreamchallenge": randomDream
        },
        success: function(data) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}


// Load user preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load preferred view
    const preferredView = localStorage.getItem('goalsPreferredView');
    if(preferredView) {
        switchView(preferredView);
    }
    
    // Load last active tab
    const activeTab = localStorage.getItem('goalsActiveTab');
    if(activeTab) {
        openTab(activeTab);
    }
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Add loading animation
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Enter key to save goals
    const todayInput = document.getElementById('todayGoalInput');
    if(todayInput) {
        todayInput.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                saveTodayGoal();
            }
        });
    }
    
    const weeklyInput = document.getElementById('weeklyGoalInput');
    if(weeklyInput) {
        weeklyInput.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                saveWeeklyGoal();
            }
        });
    }
    
    const quarterInput = document.getElementById('quarterGoalInput');
    if(quarterInput) {
        quarterInput.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                const btn = this.parentElement.parentElement.querySelector('.btn-submit');
                btn.click();
            }
        });
    }
    
    // Enter key listeners for scroll view
    const todayInput2 = document.getElementById('todayGoalInput2');
    if(todayInput2) {
        todayInput2.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                saveTodayGoal2();
            }
        });
    }
    
    const weeklyInput2 = document.getElementById('weeklyGoalInput2');
    if(weeklyInput2) {
        weeklyInput2.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                saveWeeklyGoal2();
            }
        });
    }
    
    const quarterInput2 = document.getElementById('quarterGoalInput2');
    if(quarterInput2) {
        quarterInput2.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                const btn = this.parentElement.parentElement.querySelector('.btn-submit');
                btn.click();
            }
        });
    }
});

// Tab navigation with keyboard
document.addEventListener('keydown', function(e) {
    if(document.getElementById('tabView').classList.contains('active')) {
        const tabs = ['today', 'weekly', 'roadmap', 'quiz'];
        const currentTab = localStorage.getItem('goalsActiveTab') || 'today';
        const currentIndex = tabs.indexOf(currentTab);
        
        if(e.key === 'ArrowRight' && currentIndex < tabs.length - 1) {
            openTab(tabs[currentIndex + 1]);
        } else if(e.key === 'ArrowLeft' && currentIndex > 0) {
            openTab(tabs[currentIndex - 1]);
        }
    }
});

// Function from roadmap.php for saving quarterly goals
function inputgoalstep(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl) {
    if(!inputtext) {
        Swal.fire('알림', '분기 목표를 입력해주세요.', 'warning');
        return;
    }
    
    // 첫 분기목표인지 확인
    const isFirstGoal = <?php echo ($savedDream ? 'false' : 'true'); ?>;
    
    if(isFirstGoal) {
        // 첫 분기목표 설정 시 특별한 환영 메시지
        const welcomeMessages = [
            "🎯 안녕! 당신의 특별한 랜덤 꿈을 전달합니다...",
            "✨ 짜잔! 운명이 선택한 당신의 꿈을 소개합니다...",
            "🌟 반가워요! 당신만을 위한 특별한 꿈이 도착했습니다...",
            "🎲 두구두구두구... 당신의 랜덤 꿈이 결정되었습니다!",
            "🚀 새로운 시작! 당신의 미래를 위한 꿈을 전달합니다..."
        ];
        
        const encourageMessages = [
            "앞으로 3개월 동안 이 소중한 꿈을 가꾸며 성장하시길 응원합니다! 💪",
            "다음 분기까지 이 꿈을 향해 한 걸음씩 나아가시길 바랍니다! 🌱",
            "이 꿈이 당신의 미래를 밝히는 등대가 되길 희망합니다! 🔦",
            "분기가 끝날 때쯤, 꿈에 한 발짝 더 가까워진 자신을 발견하실 거예요! 🎯",
            "이 꿈과 함께하는 여정이 즐겁고 의미있기를 바랍니다! ✨"
        ];
        
        const randomWelcome = welcomeMessages[Math.floor(Math.random() * welcomeMessages.length)];
        const randomEncourage = encourageMessages[Math.floor(Math.random() * encourageMessages.length)];
        
        Swal.fire({
            title: randomWelcome,
            html: `
                <div style="text-align: center; margin: 20px 0;">
                    <h2 style="color: #3383FF; margin: 20px 0; font-size: 28px;">
                        🌈 ${randomdream} 🌈
                    </h2>
                    <p style="color: #666; line-height: 1.6; margin-top: 20px;">
                        ${randomEncourage}
                    </p>
                    <p style="color: #999; font-size: 14px; margin-top: 15px;">
                        💡 언제든지 '바꾸기' 버튼으로 새로운 꿈을 선택할 수 있어요!
                    </p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: '네, 이 꿈으로 시작하겠습니다!',
            confirmButtonColor: '#3383FF',
            showCancelButton: true,
            cancelButtonText: '다른 꿈으로 바꿀래요',
            cancelButtonColor: '#E05D22'
        }).then((result) => {
            if (result.value) {
                // 현재 꿈으로 시작
                saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl);
                
                // 자세히 보기 링크 열기
                setTimeout(function() {
                    window.open(randomdreamurl, "_blank");
                }, 100);
            } else {
                // 다른 꿈 선택
                changeRandomDream();
                Swal.fire({
                    title: '새로운 꿈을 선택해주세요!',
                    text: '바뀐 꿈을 확인하고 다시 저장 버튼을 눌러주세요.',
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    } else {
        // 기존 사용자의 경우 기존 로직 유지
        Swal.fire({
            title: '현재의 꿈을 유지하시겠습니까?',
            text: "현재 꿈 : " + document.getElementById('randomDreamText').innerText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '예',
            cancelButtonText: '아니요',
            confirmButtonClass: 'btn btn-success',
            cancelButtonClass: 'btn btn-danger'
        }).then((result) => {
            if (result.value) {
                // 현재 꿈 유지
                randomdream = "stay";
                saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl);
                
                // 자세히 보기 링크 열기
                setTimeout(function() {
                    window.open(currentDreamUrl, "_blank");
                }, 100);
            } else {
                // 새로운 꿈으로 변경
                saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl);
                
                // 새로운 꿈의 자세히 보기 링크 열기
                setTimeout(function() {
                    window.open(randomdreamurl, "_blank");
                }, 100);
            }
        });
    }
}

function saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl) {
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": eventid,
            "userid": userid,
            "plantype": plantype,
            "deadline": deadline,
            "inputtext": inputtext,
            "randomdream": randomdream,
            "randomdreamurl": randomdreamurl
        },
        success: function(data) {
            Swal.fire({
                text: '분기목표가 설정되었습니다',
                icon: 'success',
                showConfirmButton: false,
                timer: 1500
            });
            
            setTimeout(function() {
                location.reload();
            }, 1500);
        }
    });
}

// 랜덤 꿈 챌린지 데이터
const dreamData = [
    {name: "인공지능 개발자", url: "https://gamma.app/docs/-5dvdwrou2385tda"},
    {name: "환경 보호 전문가", url: "https://gamma.app/docs/-57oe1106fexvovx"},
    {name: "가상현실 게임 디자이너", url: "https://gamma.app/docs/-w060d7y8nzrq6z1"},
    {name: "우주 탐사자", url: "https://gamma.app/docs/-xl03qnlzbhw0l3d"},
    {name: "유전공학 연구원", url: "https://gamma.app/docs/Untitled-ekp8hywee87lsw8"},
    {name: "스마트팜 기술자", url: "https://gamma.app/docs/-ggn6grxhpvp0tdj"},
    {name: "해양 생물학자", url: "https://gamma.app/docs/-xieocbvr1u6hyd0"},
    {name: "신재생 에너지 엔지니어", url: "https://gamma.app/docs/-lp6kn8pqg1aqmec"},
    {name: "드론 파일럿", url: "https://gamma.app/docs/-fsuhnwucw8546bj"},
    {name: "사이버 보안 전문가", url: "https://gamma.app/docs/-t55yu127yjsi9fo"},
    {name: "데이터 과학자", url: "https://gamma.app/docs/-8sln8zzhe487myk"},
    {name: "로봇공학 기술자", url: "https://gamma.app/docs/-62mq1zcgmekj0xw"},
    {name: "콘텐츠 크리에이터", url: "https://gamma.app/docs/-80707aa8tnf1d8u"},
    {name: "의료 기술 혁신가", url: "https://gamma.app/docs/-kep6ua7le4tcsup"},
    {name: "지속 가능한 패션 디자이너", url: "https://gamma.app/docs/-xhdx8mkbak325bj"}
];

// 현재 꿈의 URL 저장
let currentDreamUrl = '<?php echo $randomDreamUrl; ?>';

// Function to change random dream
function changeRandomDream() {
    const currentDream = document.getElementById('randomDreamText').innerText;
    let newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    
    // 현재와 다른 꿈이 나올 때까지 선택
    while(newDreamData.name === currentDream) {
        newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    }
    
    // 먼저 UI 업데이트
    document.getElementById('randomDreamText').innerText = newDreamData.name;
    currentDreamUrl = newDreamData.url;
    
    // 자세히 보기 링크 업데이트
    const detailLink = document.getElementById('dreamDetailLink');
    if(detailLink) {
        detailLink.href = newDreamData.url;
    }
    
    // Add a little animation effect
    const dreamElement = document.getElementById('randomDreamText');
    dreamElement.style.opacity = '0';
    setTimeout(() => {
        dreamElement.style.transition = 'opacity 0.3s ease';
        dreamElement.style.opacity = '1';
    }, 100);
    
    // 데이터베이스 업데이트 (백그라운드에서 처리)
    $.ajax({
        url: "database.php",
        type: "POST",
        data: {
            "eventid": 301, // 랜덤 꿈 챌린지 업데이트용 이벤트 ID
            "userid": <?php echo $studentid; ?>,
            "randomdream": newDreamData.name,
            "randomdreamurl": newDreamData.url
        },
        success: function(response) {
            console.log("Dream challenge updated successfully");
        },
        error: function(xhr, status, error) {
            console.error("Failed to update dream challenge:", error);
        }
    });
    
    Swal.fire({
        icon: 'success',
        title: '랜덤 꿈이 변경되었습니다!',
        text: newDreamData.name,
        timer: 1500,
        showConfirmButton: false
    });
}

// Function to change random dream for scroll view
function changeRandomDream2() {
    const currentDream = document.getElementById('randomDreamText2').innerText;
    let newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    
    // 현재와 다른 꿈이 나올 때까지 선택
    while(newDreamData.name === currentDream) {
        newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    }
    
    // 먼저 UI 업데이트
    document.getElementById('randomDreamText2').innerText = newDreamData.name;
    currentDreamUrl = newDreamData.url;
    
    // 자세히 보기 링크 업데이트
    const detailLink = document.getElementById('dreamDetailLink2');
    if(detailLink) {
        detailLink.href = newDreamData.url;
    }
    
    // Add a little animation effect
    const dreamElement = document.getElementById('randomDreamText2');
    dreamElement.style.opacity = '0';
    setTimeout(() => {
        dreamElement.style.transition = 'opacity 0.3s ease';
        dreamElement.style.opacity = '1';
    }, 100);
    
    // 데이터베이스 업데이트 (백그라운드에서 처리)
    $.ajax({
        url: "database.php",
        type: "POST",
        data: {
            "eventid": 301, // 랜덤 꿈 챌린지 업데이트용 이벤트 ID
            "userid": <?php echo $studentid; ?>,
            "randomdream": newDreamData.name,
            "randomdreamurl": newDreamData.url
        },
        success: function(response) {
            console.log("Dream challenge updated successfully");
        },
        error: function(xhr, status, error) {
            console.error("Failed to update dream challenge:", error);
        }
    });
    
    Swal.fire({
        icon: 'success',
        title: '랜덤 꿈이 변경되었습니다!',
        text: newDreamData.name,
        timer: 1500,
        showConfirmButton: false
    });
}

// Functions for scroll view goal saving
function saveTodayGoal2() {
    const goalInput = document.getElementById('todayGoalInput2').value;
    
    if(!goalInput) {
        Swal.fire('알림', '오늘의 목표를 입력해주세요.', 'warning');
        return;
    }
    
    Swal.fire({
        text: '오늘 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "오늘목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d'); ?>"
        },
        success: function(data) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}

function saveWeeklyGoal2() {
    const goalInput = document.getElementById('weeklyGoalInput2').value;
    
    if(!goalInput) {
        Swal.fire('알림', '주간 목표를 입력해주세요.', 'warning');
        return;
    }
    
    Swal.fire({
        text: '주간 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "주간목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d', time() + 604800); ?>"
        },
        success: function(data) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}
</script>

<?php
$pagetype = 'goals';
include("../LLM/postit.php");
?>  
</body>
</html>