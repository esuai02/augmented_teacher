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

// Time variables with comprehensive setup
$timestart2 = time() - $tbegin;
$adayAgo = time() - 43200;
$aweekAgo = time() - 604800;
$timestart3 = time() - 86400 * 14;
$amonthago = $timecreated - 604800 * 4;
$time2 = time() - 43200;

// Calendar and scheduling variables
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0);
if($nday == 0) $nday = 7;

$wtimestart = time() - 86400 * ($nday + 3);

// Weekday mapping
$weekdays = array(
    'Sun' => '7',
    'Mon' => '1', 
    'Tue' => '2',
    'Wed' => '3',
    'Thu' => '4',
    'Fri' => '5',
    'Sat' => '6'
);

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','today','$timecreated')");
}

// Initialize quiz tracking variables with comprehensive setup
$quizlist = '<hr>';
$quizlist00 = '';
$quizlist11 = '';
$quizlist12 = '';
$quizlist21 = '';
$quizlist22 = '';
$quizlist31 = '';
$quizlist32 = '';

// Initialize grade tracking variables
$todayGrade = 0;
$ntodayquiz = 0;
$weekGrade = 0;
$nweekquiz = 0;
$nweekquizall = 0;
$totalquizgrade1 = 0;
$totalmaxgrade1 = 0;
$nmaxgrade1 = 0;
$totalquizgrade2 = 0;
$totalmaxgrade2 = 0;
$nmaxgrade2 = 0;
$totalquizgrade3 = 0;
$totalmaxgrade3 = 0;
$nmaxgrade3 = 0;
$reducetime = 0;
$eventtext = '';
$gptprep = '';

// Initialize whiteboard tracking variables
$wboardlist0 = '';
$wboardlist1 = '';
$wboardlist2 = '';
$reviewwb = '';
$reviewwb2 = '';
$reviewwb0 = '';
$nsynapse = 0;
$sumSynapse = 0;
$nreview = 0;
$nreview2 = 0;
$ncomplete = 0;
$nappraise = 0;
$totalappraise = 0;
$wboardScore = 0;
$nwboard = 0;
$nrecovery = 0;
$nask = 0;
$nflag = 0;
$nright = 0;
$nwrong = 0;
$ngaveup = 0;
$ntotal = $nright + $nwrong + $ngaveup;

// Get quiz attempts with proper error handling
try {
    $quizattempts = $DB->get_records_sql(
        "SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, 
         mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, 
         mdl_quiz.sumgrades AS tgrades 
         FROM mdl_quiz 
         LEFT JOIN mdl_quiz_attempts ON mdl_quiz.id=mdl_quiz_attempts.quiz 
         WHERE mdl_quiz_attempts.timemodified > ? AND mdl_quiz_attempts.userid=? 
         ORDER BY mdl_quiz_attempts.id DESC LIMIT 200", 
        [$timestart2, $studentid]
    );
    $quizresult = json_decode(json_encode($quizattempts), True);
    $nquiz = count($quizresult);
} catch (Exception $e) {
    error_log("Quiz query error: " . $e->getMessage());
    $quizresult = [];
    $nquiz = 0;
}

// Get whiteboard activities with proper error handling
try {
    $handwriting = $DB->get_records_sql(
        "SELECT * FROM mdl_abessi_messages 
         WHERE userid=? AND status NOT LIKE 'attempt' AND tlaststroke>? 
         AND contentstype=2 AND (active=1 OR status='flag') 
         ORDER BY tlaststroke DESC LIMIT 100", 
        [$studentid, $timestart2]
    );
    $result1 = json_decode(json_encode($handwriting), True);
} catch (Exception $e) {
    error_log("Whiteboard query error: " . $e->getMessage());
    $handwriting = [];
    $result1 = [];
}

// Get schedule and engagement data with error handling
try {
    $schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid=? ORDER BY id DESC LIMIT 1", [$studentid]);
    $engagement = $DB->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid=? ORDER BY id DESC LIMIT 1", [$studentid]);
    $weeklyGoal = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_today 
         WHERE userid=? AND timecreated>? AND type LIKE '주간목표' 
         ORDER BY id DESC LIMIT 1", 
        [$studentid, time() - 604800]
    );
} catch (Exception $e) {
    error_log("Schedule/engagement query error: " . $e->getMessage());
    $schedule = null;
    $engagement = null;
    $weeklyGoal = null;
}

// Ensure proper null checking
if (!$schedule) {
    $schedule = (object)['duration1'=>0, 'duration2'=>0, 'duration3'=>0, 'duration4'=>0, 'duration5'=>0, 'duration6'=>0, 'duration7'=>0, 'lastday'=>''];
}
if (!$engagement) {
    $engagement = (object)['totaltime'=>0, 'nask'=>0, 'nreply'=>0];
}
if (!$weeklyGoal) {
    $weeklyGoal = (object)['penalty'=>0];
}

// Additional time tracking and attendance variables
try {
    $Timelastaccess = $DB->get_record_sql(
        "SELECT timecreated AS maxtc FROM mdl_logstore_standard_log 
         WHERE userid=? ORDER BY id DESC LIMIT 1", 
        [$studentid]
    );
    $lastaction = $Timelastaccess ? time() - $Timelastaccess->maxtc : 0;
    
    $attendtoday = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_missionlog 
         WHERE userid=? AND page='studenttoday' 
         ORDER BY id DESC LIMIT 1", 
        [$studentid]
    );
} catch (Exception $e) {
    error_log("Time tracking query error: " . $e->getMessage());
    $Timelastaccess = null;
    $lastaction = 0;
    $attendtoday = null;
}

// Calculate daily scheduling variables
$lastday = $schedule->lastday ?? '';
if ($attendtoday && $attendtoday->timecreated < $time2) {
    $start = 'start' . $nday;
    $timestart = isset($schedule->$start) ? $schedule->$start : '09:00';
    
    $todaybegin = strtotime($timestart);
    
    // Log attendance if current user is viewing their own data
    if ($todaybegin < $timecreated && $USER->id == $studentid) {
        try {
            $DB->execute(
                "INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) 
                 VALUES(?,?,?,?)", 
                [$studentid, 'attendance', '지각가능', $timecreated]
            );
        } catch (Exception $e) {
            error_log("Attendance logging error: " . $e->getMessage());
        }
    } elseif ($USER->id == $studentid) {
        try {
            $DB->execute(
                "INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) 
                 VALUES(?,?,?,?)", 
                [$studentid, 'attendance', 'ontime', $timecreated]
            );
        } catch (Exception $e) {
            error_log("Attendance logging error: " . $e->getMessage());
        }
    }
} else {
    $todaybegin = time() - 28800; // Default to 8 hours ago
}

$timeToday = time() - $todaybegin;

// Calculate weekly totals with null safety
$weektotal = ($schedule->duration1 ?? 0) + ($schedule->duration2 ?? 0) + ($schedule->duration3 ?? 0) + 
             ($schedule->duration4 ?? 0) + ($schedule->duration5 ?? 0) + ($schedule->duration6 ?? 0) + 
             ($schedule->duration7 ?? 0) + (($weeklyGoal->penalty ?? 0) / 60);

// Calculate daily progress based on current day
switch($nday) {
    case 1:
        if($timeToday / 3600 > ($schedule->duration1 ?? 0)) $timeToday = ($schedule->duration1 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0);
        break;
    case 2:
        if($timeToday / 3600 > ($schedule->duration2 ?? 0)) $timeToday = ($schedule->duration2 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0) + $timeToday / 3600 + (($weeklyGoal->penalty ?? 0) / 60);
        break;
    case 3:
        if($timeToday / 3600 > ($schedule->duration3 ?? 0)) $timeToday = ($schedule->duration3 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0) + ($schedule->duration2 ?? 0) + $timeToday / 3600 + (($weeklyGoal->penalty ?? 0) / 60);
        break;
    case 4:
        if($timeToday / 3600 > ($schedule->duration4 ?? 0)) $timeToday = ($schedule->duration4 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0) + ($schedule->duration2 ?? 0) + ($schedule->duration3 ?? 0) + $timeToday / 3600 + (($weeklyGoal->penalty ?? 0) / 60);
        break;
    case 5:
        if($timeToday / 3600 > ($schedule->duration5 ?? 0)) $timeToday = ($schedule->duration5 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0) + ($schedule->duration2 ?? 0) + ($schedule->duration3 ?? 0) + ($schedule->duration4 ?? 0) + $timeToday / 3600 + (($weeklyGoal->penalty ?? 0) / 60);
        break;
    case 6:
        if($timeToday / 3600 > ($schedule->duration6 ?? 0)) $timeToday = ($schedule->duration6 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0) + ($schedule->duration2 ?? 0) + ($schedule->duration3 ?? 0) + ($schedule->duration4 ?? 0) + ($schedule->duration5 ?? 0) + $timeToday / 3600 + (($weeklyGoal->penalty ?? 0) / 60);
        break;
    case 7:
        if($timeToday / 3600 > ($schedule->duration7 ?? 0)) $timeToday = ($schedule->duration7 ?? 0) * 3600;
        elseif($timeToday < 0) $timeToday = 0;
        $untiltoday = ($schedule->duration1 ?? 0) + ($schedule->duration2 ?? 0) + ($schedule->duration3 ?? 0) + ($schedule->duration4 ?? 0) + ($schedule->duration5 ?? 0) + ($schedule->duration6 ?? 0) + $timeToday / 3600 + (($weeklyGoal->penalty ?? 0) / 60);
        break;
    default:
        $timeToday = 0;
        $untiltoday = 0;
        break;
}

// Final calculation variables and averages
$todayqAve = -1;
$weekqAve = -1;
$ngrowth = 0;
$wboardScoreAve = 0;

// These will be calculated after quiz processing loops
// unset($value) for clean variable scope in processing loops
unset($value);

// Process quiz results with comprehensive categorization from original today.php
foreach($quizresult as $value) {
    $comment = '';
    $qnum = substr_count($value['layout'] ?? '', ',') + 1 - substr_count($value['layout'] ?? '', ',0');
    $quizgrade = round($value['sumgrades'] / $value['tgrades'] * 100, 0);

    // Status icon based on grade
    if ($quizgrade > 79.99) {
        $imgstatus = '<img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
    } elseif ($quizgrade > 69.99) {
        $imgstatus = '<img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
    } else {
        $imgstatus = '<img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
    }
    
    $quizid = $value['quiz'];
    try {
        $moduleid = $DB->get_record_sql("SELECT id FROM mdl_course_modules WHERE instance=?", [$quizid]);
        $quizmoduleid = $moduleid ? $moduleid->id : 0;
    } catch (Exception $e) {
        $quizmoduleid = 0;
    }
    
    if (strpos($value['name'], 'ifmin') !== false) {
        $quiztitle0 = substr($value['name'], 0, strpos($value['name'], '{'));
    } else {
        $quiztitle0 = $value['name'];
    }
    $quiztitle = iconv_substr($quiztitle0, 0, 30, "utf-8");
    $quizinstruction = '<b>' . $quiztitle0 . ' </b><br><br> ' . ($value['instruction'] ?? '') . '<hr>' . ($value['comment'] ?? '');
    
    // Comment processing based on maxgrade and comment status
    if ($value['maxgrade'] == NULL) {
        $comment = '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id=' . $studentid . '&attemptid=' . $value['id'] . '" target="_blank"><b style="color:blue">분석</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>' . $quizinstruction . '</td></tr></table></span></div>';
    } elseif (strpos($value['comment'] ?? '', '최선을 다한 결과') !== false) {
        $comment = '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id=' . $studentid . '&attemptid=' . $value['id'] . '" target="_blank"><b style="color:green">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>' . $quizinstruction . '</td></tr></table></span></div>';
    } elseif ($value['comment'] == NULL) {
        $comment = '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id=' . $studentid . '&attemptid=' . $value['id'] . '" target="_blank"><b style="color:grey">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>' . $quizinstruction . '</td></tr></table></span></div>';
    } else {
        $comment = '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id=' . $studentid . '&attemptid=' . $value['id'] . '" target="_blank"><b style="color:red">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>' . $quizinstruction . '</td></tr></table></span></div>';
    }
    
    $attemptid = $value['id'];
    $modifyquiz = '';

    // Role-based quiz modification controls
    if ($value['state'] === 'inprogress' && $role === 'student') {
        $modifyquiz = '<span onclick="addquiztime(\'' . $attemptid . '\')><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/addtime.png" width="25"></span>';
    } elseif ($value['state'] === 'inprogress' && $role !== 'student') {
        $modifyquiz = '<span onclick="deletequiz(\'' . $attemptid . '\')><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png" width="15"></span> <span onclick="addquiztime(\'' . $attemptid . '\')><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/addtime.png" width="25"></span>';
    } elseif ($role !== 'student') {
        $modifyquiz = '<span onclick="deletequiz(\'' . $attemptid . '\')><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png" width="15"></span>';
    }

    $quizstart = date("H:i", $value['timestart']);
    $timefinish = date("m/d | H:i", $value['timefinish']);
    $addtime = '<b style="color:blue;">' . ($value['addtime'] ?? 0) . '</b>분+';
    if (($value['addtime'] ?? 0) > 720) {
        $addtime = '<b style="color:blue;">' . round(($value['addtime'] ?? 0) / 1440) . '일+</b>';
    }
    if (($value['modified'] ?? '') === 'addtime') {
        $timefinish = date("m/d", $value['timefinish']) . ' | ' . $addtime;
    }

    $maxgrade = $value['maxgrade'] ?? 0;
    
    // Quiz categorization logic
    if (($value['review'] ?? 0) == 3) { // 워밍업 활동
        $quizlist00 .= ' <hr> <a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">퀴즈예약</a> | ' . $quiztitle . ' <input type="checkbox" name="checkAccount" onClick="AddReview(11111,\'' . $studentid . '\',\'' . $value['id'] . '\', this.checked)"/>';
    } elseif (strpos($quiztitle, '내신') !== false) {
        if ($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            if ($quizgrade > 89.99) {
                $reducetime = $reducetime + 30;
                $eventtext .= '<tr><td>퀴즈성공 30분</td></tr>';
            } elseif ($quizgrade > 79.99) {
                $reducetime = $reducetime + 10;
                $eventtext .= '<tr><td>퀴즈노력 10분</td></tr>';
            }
            $quizlist11 .= '<tr><td>' . $imgstatus . '&nbsp;' . $quizstart . ' </td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">' . $quiztitle . '</a>...(' . $value['attempt'] . '회)</b></td><td>' . $quizstart . '</td> <td><span class="" style="color: rgb(239, 69, 64);">' . $quizgrade . '점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $value['id'] . '&studentid=' . $studentid . ' " target="_blank">' . $value['state'] . '</a></td> <td>' . $timefinish . '</td><td>' . $comment . '</td> <td>' . $modifyquiz . '</td></tr>';
            $todayGrade = $todayGrade + $quizgrade;
            if ($quizgrade > 79.99) $ntodayquiz++;
            if ($value['maxgrade'] != NULL) {
                $totalmaxgrade1 = $totalmaxgrade1 + $value['maxgrade'];
                $nmaxgrade1++;
                $totalquizgrade1 = $totalquizgrade1 + $quizgrade;
            }
            $gptprep .= $quiztitle . '(' . $quizgrade . '점.) &';
        } else {
            $quizlist12 .= '<tr><td>' . $imgstatus . '</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">' . $quiztitle . '</a>...(' . $value['attempt'] . '회)</td> <td>' . $quizstart . '</td> <td><span class="" style="color: rgb(239, 69, 64);">' . $quizgrade . '점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $value['id'] . '&studentid=' . $studentid . ' " target="_blank">' . $value['state'] . '</a></td> <td>' . $timefinish . '</td><td>' . $comment . '</td> <td>' . $modifyquiz . '</td></tr>';
            $todayGrade = $todayGrade + $quizgrade;
            $weekGrade = $weekGrade + $quizgrade;
            $nweekquizall++;
            if ($quizgrade > 79.99) $nweekquiz++;
            if ($value['maxgrade'] != NULL) {
                $totalmaxgrade1 = $totalmaxgrade1 + $value['maxgrade'];
                $nmaxgrade1++;
                $totalquizgrade1 = $totalquizgrade1 + $quizgrade;
            }
        }
    } elseif ($qnum > 9) {
        if ($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            if ($quizgrade > 89.99) {
                $reducetime = $reducetime + 30;
                $eventtext .= '<tr><td>퀴즈성공 30분 </td></tr> ';
            } elseif ($quizgrade > 79.99) {
                $reducetime = $reducetime + 10;
                $eventtext .= '<tr><td>퀴즈노력 10분</td></tr>';
            }
            $quizlist21 .= '<tr><td>' . $imgstatus . '</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">' . $quiztitle . '</a>...(' . $value['attempt'] . '회)</b></td><td>' . $quizstart . '</td>  <td><span class="" style="color: rgb(239, 69, 64);">' . $quizgrade . '점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $value['id'] . '&studentid=' . $studentid . ' " target="_blank">' . $value['state'] . '</a></td> <td>' . $timefinish . '</td><td>' . $comment . '</td> <td>' . $modifyquiz . '</td></tr>';
            $todayGrade = $todayGrade + $quizgrade;
            $nweekquizall++;
            if ($quizgrade > 79.99) $ntodayquiz++;
            if ($value['maxgrade'] != NULL) {
                $totalmaxgrade2 = $totalmaxgrade2 + $value['maxgrade'];
                $nmaxgrade2++;
                $totalquizgrade2 = $totalquizgrade2 + $quizgrade;
            }
            $gptprep .= $quiztitle . '(' . $quizgrade . '점.) &';
        } else {
            $quizlist22 .= '<tr><td>' . $imgstatus . '</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">' . $quiztitle . '</a>...(' . $value['attempt'] . '회)</td> <td>' . $quizstart . '</td> <td><span class="" style="color: rgb(239, 69, 64);">' . $quizgrade . '점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $value['id'] . '&studentid=' . $studentid . ' " target="_blank">' . $value['state'] . '</a></td> <td>' . $timefinish . '</td><td>' . $comment . '</td> <td>' . $modifyquiz . '</td></tr>';
            $weekGrade = $weekGrade + $quizgrade;
            $nweekquizall++;
            if ($quizgrade > 79.99) $nweekquiz++;
            if ($value['maxgrade'] != NULL) {
                $totalmaxgrade2 = $totalmaxgrade2 + $value['maxgrade'];
                $nmaxgrade2++;
                $totalquizgrade2 = $totalquizgrade2 + $quizgrade;
            }
        }
    } else {
        if ($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist31 .= '<tr><td>' . $imgstatus . '</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">' . $quiztitle . '</a>...(' . $value['attempt'] . '회)</b></td> <td>' . $quizstart . '</td> <td><span class="" style="color: rgb(239, 69, 64);">' . $quizgrade . '점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $value['id'] . '&studentid=' . $studentid . ' " target="_blank">' . $value['state'] . '</a></td> <td>' . $timefinish . '</td><td>' . $comment . '</td> <td><input type="checkbox" name="checkAccount" onClick="AddReview(1111,\'' . $studentid . '\',\'' . $value['id'] . '\', this.checked)"/></td><td>' . $modifyquiz . '</td></tr>';
            $gptprep .= $quiztitle . '(' . $quizgrade . '점.) &';
        } else {
            $quizlist32 .= '<tr><td>' . $imgstatus . '</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizmoduleid . ' " target="_blank">' . $quiztitle . '</a>...(' . $value['attempt'] . '회)</td><td>' . $quizstart . '</td>  <td><span class="" style="color: rgb(239, 69, 64);">' . $quizgrade . '점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $value['id'] . '&studentid=' . $studentid . ' " target="_blank">' . $value['state'] . '</a></td> <td>' . $timefinish . '</td><td>' . $comment . '</td> <td><input type="checkbox" name="checkAccount" onClick="AddReview(1111,\'' . $studentid . '\',\'' . $value['id'] . '\', this.checked)"/></td><td>' . $modifyquiz . '</td></tr>';
        }
        if ($value['maxgrade'] != NULL) {
            $totalmaxgrade3 = $totalmaxgrade3 + $value['maxgrade'];
            $nmaxgrade3++;
            $totalquizgrade3 = $totalquizgrade3 + $quizgrade;
        }
    }
}

// Calculate averages
if ($ntodayquiz != 0) {
    $todayqAve = $todayGrade / ($ntodayquiz);
} else {
    $todayqAve = -1;
}
if ($nweekquizall != 0) {
    $weekqAve = $weekGrade / ($nweekquizall);
} else {
    $weekqAve = -1;
}
$ngrowth = $nweekquiz + $ntodayquiz;

// Process whiteboard activities with comprehensive categorization from original today.php
foreach($result1 as $value) {
    if (($value['synapselevel'] ?? 0) > 0) {
        $nsynapse++;
        $sumSynapse = $sumSynapse + $value['synapselevel'];
    }

    if (($value['status'] ?? '') === 'review') $nreview++;
    if (($value['status'] ?? '') === 'complete') $ncomplete++;
    if (($value['depth'] ?? 0) > 2) $nrecovery++;
    if (($value['status'] ?? '') === 'begin') $nask++;

    $Q_id = $value['contentsid'];
    
    // 화이트보드 평점 계산
    if (($value['timemodified'] ?? 0) > $aweekAgo && $tbegin == 604800 && ($value['star'] ?? 0) > 0) {
        $wboardScore = $wboardScore + $value['star'];
        $nwboard++;
    }
    
    $encryption_id = $value['wboardid'] ?? '';
    $nstroke = (int)($value['nstroke'] ?? 0);
    $ave_stroke = $nstroke > 0 && isset($value['tlast']) && isset($value['tfirst']) ? round($nstroke / (($value['tlast'] - $value['tfirst']) / 60), 1) : 0;
    $contentstype = $value['contentstype'] ?? 2;
    $nstep = $value['nstep'] ?? 0;
    $status = $value['status'] ?? '';
    $contentstitle = $value['contentstitle'] ?? '';
    $contentsid = $value['contentsid'] ?? 0;
    $cmid = $value['cmid'] ?? 0;
    
    if ($value['status'] !== 'complete' && $value['status'] !== 'review') {
        $resultValue = '<b style="color:orange;">검토 중입니다.</b>';
    } elseif (($value['teacher_check'] ?? 0) == 2) {
        $resultValue = '검토완료';
    } else {
        $resultValue = '<span style="color:orange;">검토 중입니다.</span>';
    }

    $bstrate = ($value['nfire'] ?? 0) / (($value['nmax'] ?? 0) + 0.01) * 100;
    if ($bstrate > 99) {
        $bstrateimg = 'https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666457.png';
    } elseif ($bstrate > 70) {
        $bstrateimg = 'https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666432.png';
    } elseif ($bstrate > 40) {
        $bstrateimg = 'https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666363.png';
    } elseif ($bstrate > 10) {
        $bstrateimg = 'https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666336.png';
    } else {
        $bstrateimg = 'https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666304.png';
    }

    if (($value['appraise'] ?? null) != NULL) {
        $nappraise++;
        $totalappraise = $totalappraise + $value['appraise'];
    }
    
    $checkstatus = '';
    $fixhistory = '<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=' . $encryption_id . '" target="_blank">노트 </a><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid=' . $studentid . '&wboardid=' . $encryption_id . '&contentsid=' . $contentsid . '&contentstype=2" target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width="15"></a>';

    $seethiswb = 'Q7MQFA' . $contentsid . '0tsDoHfRT_user' . $studentid . '_' . date("Y_m_d", $value['timemodified'] ?? time());
    if (($value['tracking'] ?? 0) == 6) {
        $resulttype = '<a style="color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=' . $seethiswb . '"target="_blank">오늘</a>';
        $resulttype2 = '<span style="color:red;">지난</span>';
    } elseif (($value['tracking'] ?? 0) == 5) {
        $resulttype = '<a style="color:orange;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=' . $seethiswb . '"target="_blank">오늘</a>';
        $resulttype2 = '<span style="color:orange;">지난</span>';
    } else {
        $resulttype = '<a style="color:#0c0d0d;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=' . $seethiswb . '"target="_blank">오늘</a>';
        $resulttype2 = '<span style="color:#0c0d0d;">지난</span>';
    }
    
    // Get question text and image
    $questiontext = '';
    if ($contentsid > 0) {
        try {
            $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id=?", [$contentsid]);
            if ($qtext && $qtext->questiontext) {
                $htmlDom = new DOMDocument;
                @$htmlDom->loadHTML($qtext->questiontext);
                $imageTags = $htmlDom->getElementsByTagName('img');
                $questionimg = '';
                foreach ($imageTags as $imageTag) {
                    $questionimg = $imageTag->getAttribute('src');
                    $questionimg = str_replace(' ', '%20', $questionimg);
                    if (strpos($questionimg, 'MATRIX/MATH') !== false || strpos($questionimg, 'HintIMG') !== false) break;
                }
                $questiontext = '<img loading="lazy" src="' . $questionimg . '" width="500">';
            }
        } catch (Exception $e) {
            error_log("Question text query error: " . $e->getMessage());
            $questiontext = '';
        }
    }

    if ($nstroke < 3) {
        $ave_stroke = '##';
        $nstroke = '##';
    }

    // Include status icons
    include_once("../whiteboard/status_icons.php");
    
    if ($status === 'exam' && $timecreated - ($value['timereviewed'] ?? 0) > 600) {
        $imgstatus = '<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/exam2.png" width="15"><span style="color: rgb(33, 33, 233);"> 시작</span>';
    } elseif ($status === 'sequence') {
        $imgstatus = '<img src="https://mathking.kr/Contents/IMAGES/sequence.png" width="15"><span style="color: rgb(33, 33, 233);"> 순서</span>';
    } elseif ($status === 'evidence' || $status === 'modify' || $status === 'explain' || $status === 'direct') {
        $imgstatus = '<img src="https://mathking.kr/Contents/IMAGES/logic.png" width="15"><span style="color: rgb(33, 33, 233);"> 논리</span>';
    } elseif ($status === 'fixsol') {
        $imgstatus = '<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/fixsol.png" width="15"><span style="color: rgb(33, 33, 233);"> 다시</span>';
    }
    
    $wbidbooster = 'booststep' . $contentsid . '_user' . $studentid;

    $hidewb = '';
    if ($value['status'] === 'review' && ($value['hide'] ?? 0) == 0) {
        $hidewb = '<input type="checkbox" name="checkAccount" onClick="ChangeCheckBox2(111,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/>';
    } elseif (($value['hide'] ?? 0) == 1 && $value['status'] === 'review' && $role !== 'student') {
        $hidewb = '<input type="checkbox" name="checkAccount" onClick="ChangeCheckBox2(111,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/>  <img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659836193.png" width="20">';
    } elseif ($role !== 'student') {
        $hidewb = '<input type="checkbox" name="checkAccount" onClick="ChangeCheckBox2(111,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/>';
    }
    
    $cntinside = ' (' . $nstroke . '획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id=' . $encryption_id . '&tb=604800" target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="' . $bstrateimg . '" width="15"></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id=' . $encryption_id . '&speed=+9"target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width="15"></a>';
    
    // Get tag titles - placeholder for now
    $tagtitles = substr($contentstitle, 0, 40);

    // Categorize whiteboard activities based on status and timing
    if ($value['status'] === 'flag' && ($value['timemodified'] ?? 0) > $adayAgo && $value['contentstitle'] !== 'incorrect') {
        $nthinktext = 'OK'; // Simplified for now
        
        if ($status === 'review' && ($value['hide'] ?? 0) == 0) {
            $reviewwb .= '<tr><td sytle="font-weight: bold;">' . $imgstatus . '  ' . $fixhistory . '</td><td sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;  <div class="tooltip3"><input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . ' <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote' . $encryption_id . '&srcid=' . $encryption_id . '&studentid=' . $studentid . '&mode=addexp"target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width="25"></a></td><td></td><td sytle="font-weight: bold;"> ' . $nthinktext . ' </td><td>  ' . $hidewb . ' </td></tr> ';
        } elseif ($status === 'review' && ($value['hide'] ?? 0) == 1 && $role !== 'student') {
            $reviewwb2 .= '<tr><td sytle="font-weight: bold;">' . $imgstatus . '  ' . $fixhistory . '</td><td sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;  <div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . ' <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote' . $encryption_id . '&srcid=' . $encryption_id . '&studentid=' . $studentid . '&mode=addexp"target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width="25"></a></td><td></td><td>  ' . $hidewb . ' </td><td sytle="font-weight: bold;"></td></tr> ';
        } elseif (($value['hide'] ?? 0) == 0) {
            $wboardlist0 .= '<tr><td sytle="font-weight: bold;">' . $imgstatus . '  ' . $fixhistory . '</td><td sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . ' <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div>(' . $nstroke . '획)</td><td> <span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td></td><td sytle="font-weight: bold;"> ' . $nthinktext . ' </td><td>  ' . $hidewb . ' </td></tr> ';
        }
        $nflag++;
    } elseif (($value['timemodified'] ?? 0) > $adayAgo && $value['status'] !== 'flag') {
        if ($status === 'review' && ($value['hide'] ?? 0) == 0) {
            $reviewwb .= '<tr><td sytle="font-weight: bold;">' . $resulttype . $imgstatus . ' ' . $fixhistory . '</td><td sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;  <div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . '  <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div></span> <span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td sytle="font-weight: bold;">&nbsp;&nbsp;</td><td sytle="font-weight: bold;"> ' . $resultValue . '   </td><td> ' . $hidewb . '  </td></tr> ';
        } elseif ($status === 'review' && ($value['hide'] ?? 0) == 1 && $role !== 'student') {
            $reviewwb2 .= '<tr><td sytle="font-weight: bold;">' . $resulttype . $imgstatus . ' ' . $fixhistory . '</td><td sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . '  <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div></span><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td sytle="font-weight: bold;">&nbsp;&nbsp;</td><td> ' . $hidewb . '  </td><td sytle="font-weight: bold;"></td></tr> ';
        } elseif (($value['hide'] ?? 0) == 0) {
            $wboardlist1 .= '<tr><td sytle="font-weight: bold;">' . $resulttype . $imgstatus . '  ' . $fixhistory . '</td><td sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . '  <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div></span><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td sytle="font-weight: bold;">&nbsp;&nbsp;</td><td sytle="font-weight: bold;">  ' . $resultValue . '  </td><td> ' . $hidewb . '  </td></tr> ';
        }
    } elseif (($value['timemodified'] ?? 0) <= $adayAgo && $value['status'] !== 'flag' && ($value['helptext'] ?? '') !== '해결') {
        if ($status === 'review' && ($value['hide'] ?? 0) == 0) {
            if ($value['status'] === 'review' && time() > ($value['treview'] ?? 0)) {
                $nreview2++;
                $imgstatus = '<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626450444001.png" width="15">';
                $reviewwb0 .= $imgstatus . ' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id=' . $contentsid . '&studentid=' . $studentid . '" target="_blank" >복습예약 </a> (' . ($value['nreview'] ?? 0) . '회)';
            } else {
                $reviewwb .= '<tr><td>' . $resulttype2 . $imgstatus . ' ' . $fixhistory . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id=' . $contentsid . '&studentid=' . $studentid . '" target="_blank" ><div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . '  <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div></a><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td>&nbsp;&nbsp;</td><td>  ' . $resultValue . ' </td><td> ' . $hidewb . '  </td></tr> ';
            }
        } elseif ($status === 'review' && ($value['hide'] ?? 0) == 1 && $role !== 'student') {
            $reviewwb2 .= '<tr><td>' . $resulttype2 . $imgstatus . ' ' . $fixhistory . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id=' . $contentsid . '&studentid=' . $studentid . '" target="_blank" ><div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . '  <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div></a><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td>&nbsp;&nbsp;</td><td> ' . $hidewb . '  </td><td></td></tr> ';
        } elseif (($value['hide'] ?? 0) == 0) {
            $wboardlist2 .= '<tr><td>' . $resulttype2 . $imgstatus . ' ' . $fixhistory . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id=' . $contentsid . '&studentid=' . $studentid . '" target="_blank" ><div class="tooltip3"> <input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox2(11,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/> ' . substr($tagtitles, 0, 40) . '&nbsp;&nbsp;' . date("m/d | H:i", $value['timemodified']) . '  <span class="tooltiptext3"><table style="" align=center><tr><td>' . $questiontext . '</td></tr></table></span></div></a><span onClick="showWboard(\'' . $encryption_id . '\')">' . $cntinside . '</span></td><td>&nbsp;&nbsp;</td><td> ' . $resultValue . ' </td><td> ' . $hidewb . '  </td></tr> ';
        }
    }
}

// Update database statistics for weekly period
if ($tbegin == 604800) {
    try {
        $DB->execute(
            "UPDATE {abessi_indicators} SET todayquizave=?, ngrowth=?, weekquizave=? 
             WHERE userid=? ORDER BY id DESC LIMIT 1", 
            [$todayqAve, $ngrowth, $weekqAve, $studentid]
        );
    } catch (Exception $e) {
        error_log("Statistics update error: " . $e->getMessage());
    }
    
    $wboardScoreAve = $nwboard > 0 ? (int)($wboardScore / $nwboard / 5 * 100) : 0;
}

// Get weekly goal information with error handling
try {
    $weeklyGoal2 = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_today 
         WHERE userid=? AND timecreated>? AND type LIKE '주간목표' 
         ORDER BY id DESC LIMIT 1", 
        [$studentid, $wtimestart]
    );
    $inputtime = $weeklyGoal2 ? date("m/d", $weeklyGoal2->timecreated) : date("m/d");
} catch (Exception $e) {
    error_log("Weekly goal query error: " . $e->getMessage());
    $weeklyGoal2 = null;
    $inputtime = date("m/d");
}

// Final variable initialization and calculations complete
// All quiz categorization variables: quizlist11, quizlist12, quizlist21, quizlist22, quizlist31, quizlist32, quizlist00
// All whiteboard variables: wboardlist0, wboardlist1, wboardlist2, reviewwb, reviewwb2, reviewwb0
// All grade tracking variables: todayGrade, weekGrade, ntodayquiz, nweekquiz, etc.
// All time calculation variables: timeToday, untiltoday, weektotal, etc.

// Database initialization and core variables migration complete

$total_study_time = round($reducetime/60, 0);

// Initialize progress variables from today.php for progress card section
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday = jddayofweek($jd,0);
if($nday == 0) $nday = 7;

$wtimestart = time() - 86400 * ($nday + 3);
$timefrom = $timestart2;

// Get schedule and goal data
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
$engagement3 = $DB->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1"); 
// Get today's start timestamp (beginning of current day)
$today_start = strtotime('today'); 
$checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type='오늘목표' AND timecreated>='$today_start' ORDER BY id DESC LIMIT 1");

// Initialize with safe defaults
if(!$schedule) $schedule = (object)['duration1'=>0,'duration2'=>0,'duration3'=>0,'duration4'=>0,'duration5'=>0,'duration6'=>0,'duration7'=>0,'lastday'=>date('Y-m-d')];
if(!$weeklyGoal) $weeklyGoal = (object)['penalty'=>0,'text'=>'목표 설정 필요','id'=>0];
if(!$engagement3) $engagement3 = (object)['totaltime'=>0,'nask'=>0,'nreply'=>0];
if(!$checkgoal) $checkgoal = (object)['id'=>0,'inspect'=>0,'drilling'=>0,'text'=>'오늘 목표 설정'];

// Calculate time statistics
$weektotal = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + $schedule->duration6 + $schedule->duration7 + ($weeklyGoal->penalty ?? 0)/60;

$timeToday = time() - strtotime('today');
if($nday == 1) $untiltoday = $schedule->duration1;
elseif($nday == 2) $untiltoday = $schedule->duration1 + min($timeToday/3600, $schedule->duration2) + ($weeklyGoal->penalty ?? 0)/60;
elseif($nday == 3) $untiltoday = $schedule->duration1 + $schedule->duration2 + min($timeToday/3600, $schedule->duration3) + ($weeklyGoal->penalty ?? 0)/60;
elseif($nday == 4) $untiltoday = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + min($timeToday/3600, $schedule->duration4) + ($weeklyGoal->penalty ?? 0)/60;
elseif($nday == 5) $untiltoday = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + min($timeToday/3600, $schedule->duration5) + ($weeklyGoal->penalty ?? 0)/60;
elseif($nday == 6) $untiltoday = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + min($timeToday/3600, $schedule->duration6) + ($weeklyGoal->penalty ?? 0)/60;
elseif($nday == 7) $untiltoday = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + $schedule->duration6 + min($timeToday/3600, $schedule->duration7) + ($weeklyGoal->penalty ?? 0)/60;

$untiltoday = round($untiltoday, 1);
if($untiltoday > 1000) $untiltoday = 1;

// Calculate percentages and progress
$timefilled = round($engagement3->totaltime / ($untiltoday + 0.0001) * 100, 0);
$timefilled2 = round($engagement3->totaltime / ($weektotal + 0.0001) * 100, 0);
if($timefilled > 20000) $timefilled = 100;

// Set progress bar colors
if($timefilled < 60) $bgtype = 'danger';
elseif($timefilled < 80) $bgtype = 'warning';  
else $bgtype = 'success';

if($timefilled2 < 60) $bgtype2 = 'danger';
elseif($timefilled2 < 80) $bgtype2 = 'warning';
else $bgtype2 = 'success';

// Status variables
$goalid = $checkgoal->id ?? 0;
$inspectToday = $checkgoal->inspect ?? 0;
$status4 = ($inspectToday == 2) ? 'checked' : '';
$status5 = ($inspectToday == 3) ? 'checked' : '';

// Additional time display
$addtime = '';
if(($weeklyGoal->penalty ?? 0) > 0) {
    $addtime = '<b style="color:red;"> (보충 '.$weeklyGoal->penalty.'분) </b>';
}

// Goal completion and remaining time
$goalcomplete = '<b>귀가검사</b>&nbsp;<input type="checkbox" name="checkAccount" '.$status5.' onclick="submittoday(21,'.$studentid.',this.checked)"/>';
$tleft = date('H:i'); // Simple time display

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
        
        /* Progress Card Styles from today.php */
        .progress {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .progress-bar {
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            background-color: #007bff;
            transition: width 0.6s ease;
        }
        
        .progress-bar-striped {
            background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
            animation: progress-bar-stripes 1s linear infinite;
        }
        
        @keyframes progress-bar-stripes {
            0% {
                background-position-x: 1rem;
            }
            100% {
                background-position-x: 0;
            }
        }
        
        .bg-success {
            background-color: #28a745 !important;
        }
        
        .bg-warning {
            background-color: #ffc107 !important;
        }
        
        .bg-danger {
            background-color: #dc3545 !important;
        }
        
        .text-muted {
            color: #6c757d !important;
        }
        
        .fw-bold {
            font-weight: 700 !important;
        }
        
        /* Progress Card Demo Styles */
        .demo {
            padding: 15px;
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .progress-status {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .progress-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 10px;
        }
        
        /* Card body styles */
        .card-body {
            padding: 20px;
        }
        
        .row {
            margin: 0;
        }
        
        .col-md-12 {
            width: 100%;
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

            <!-- Analysis iframes from today.php -->
            <div class="quiz-section">
                <h3><i class="fas fa-chart-line"></i>학습 분석 및 수학 일기</h3>
                <table width="100%" style="border: 3px solid skyblue; border-collapse: collapse;" cellspacing="0" cellpadding="0">
                    <tr valign="top">
                        <!-- 왼쪽 여백 -->
                        <td width="5%"></td>
                        <td width="45%" style="vertical-align: top;">
                            <!-- 사용자 분석 iframe -->
                            <h4 style="margin-bottom: 15px; color: #667eea; text-align: center;">📊 학습 분석</h4>
                            <iframe 
                                src="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis/user_analysis.php?userid=<?php echo $studentid; ?>&tbegin=<?php echo ($timecreated-604800*12); ?>&tend=<?php echo $timecreated; ?>"
                                width="100%" 
                                height="400" 
                                style="border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);" 
                                scrolling="yes"
                                frameborder="0">
                            </iframe>
                        </td>
                        
                        <!-- 가운데 구분용 공간 -->
                        <td width="3%"></td>
                        
                        <!-- 오른쪽 iframe 영역 -->
                        <td width="45%" style="vertical-align: top;">
                            <!-- 타임스캐폴딩 통계 iframe -->
                            <h4 style="margin-bottom: 15px; color: #667eea; text-align: center;">📚 수학 일기</h4>
                            <iframe 
                                src="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid=<?php echo $studentid; ?>"
                                width="100%" 
                                height="400" 
                                style="border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);" 
                                scrolling="yes"
                                frameborder="0">
                            </iframe>
                        </td>
                        
                        <!-- 오른쪽 여백 -->
                        <td width="2%"></td>
                    </tr>
                </table>
            </div>
            
            <!-- Progress Card Section from today.php -->
            <div class="quiz-section">
                <h3><i class="fas fa-clock"></i>학습 진도 현황</h3>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="progress-card">
                                <table width="100%">
                                    <tr>
                                        <td width="25%">
                                            <div class="demo">
                                                <div class="progress-card">
                                                    <div class="progress-status">
                                                        <span class="text-muted fw-bold">
                                                            <b>주간 : </b> 총 <?php echo round($weektotal, 1); ?>시간 
                                                            <a href="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=<?php echo $timefrom; ?>&userid=<?php echo $studentid; ?>" target="_blank">
                                                                <img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width="15">
                                                            </a>
                                                        </span> 
                                                    </div>
                                                    <div class="progress" style="margin-top: 10px;">
                                                        <div class="progress-bar progress-bar-striped bg-<?php echo $bgtype2; ?>" role="progressbar"
                                                             style="width: <?php echo $timefilled2; ?>%"
                                                             aria-valuenow="<?php echo $timefilled2; ?>" aria-valuemin="0" aria-valuemax="100"
                                                             data-toggle="tooltip" data-placement="top"
                                                             title="<?php echo round($timefilled2, 1); ?>%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td width="2%"></td>
                                        
                                        <td style="vertical-align: top;" width="25%">  
                                            <div class="demo">
                                                <div class="progress-card">
                                                    <div class="progress-status">
                                                        <span class="text-muted">
                                                            <b>오늘까지</b> : <b> 총 <?php echo $untiltoday; ?>시간  
                                                            <a href="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=<?php echo $timefrom; ?>&userid=<?php echo $studentid; ?>" target="_blank">
                                                                <img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width="15">
                                                            </a><?php echo $addtime; ?> &nbsp;
                                                        </span>   
                                                    </div>
                                                    <div class="progress" style="margin-top: 10px;">
                                                        <div class="progress-bar progress-bar-striped bg-<?php echo $bgtype; ?>" role="progressbar"
                                                             style="width: <?php echo $timefilled; ?>%"
                                                             aria-valuenow="<?php echo $timefilled; ?>" aria-valuemin="0" aria-valuemax="100"
                                                             data-toggle="tooltip" data-placement="top"
                                                             title="<?php echo round($timefilled, 1); ?>%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td width="2%"></td>
                                        
                                        <td width="46%">             
                                            <div class="demo" style="margin-top: 3px; padding: 15px; background: #f8f9fa; border-radius: 10px;"> 
                                                &nbsp; <b>DMN휴식</b> <input type="checkbox" name="checkAccount" <?php echo $status4; ?> onClick="Resttime(33,'<?php echo $studentid; ?>','<?php echo $goalid; ?>', this.checked)"/> 
                                                &nbsp; <b>오프라인</b> <input type="checkbox" name="checkAccount" <?php echo $status5; ?> onClick="ChangeCheckBox(333,'<?php echo $studentid; ?>','<?php echo $goalid; ?>', this.checked)"/>   
                                                &nbsp; <?php echo $goalcomplete; ?>  
                                                <b> &nbsp;&nbsp;&nbsp; <span style="color:blue;"><?php echo $tleft; ?></span></b>  
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if(!$quizlist11 && !$quizlist12 && !$quizlist21 && !$quizlist22 && !$quizlist31 && !$quizlist32 && !$quizlist00 && !$activities_today && !$activities_past): ?>
            <div class="quiz-section">
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>퀴즈 기록이 없습니다</h3>
                    <p>선택한 기간 동안의 퀴즈 기록이 없습니다.<br>열심히 공부해보세요!</p>
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
        
        <?php if($role !== 'student'): ?>
        <!-- Schedule information section -->
        <div class="quiz-section">
            <h3 style="color: white; background-color: #17a2b8; padding: 15px; margin: -25px -25px 20px -25px; border-radius: 15px 15px 0 0;"><i class="fas fa-calendar-alt"></i>📅 스케줄 정보 (선생님용)</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <iframe src="schedule_embed.php" width="100%" height="300" style="border: none; border-radius: 5px;"></iframe>
            </div>
        </div>
        
        <br><br>
        
        <!-- Course information section -->
        <div class="quiz-section">
            <h3 style="color: white; background-color: #28a745; padding: 15px; margin: -25px -25px 20px -25px; border-radius: 15px 15px 0 0;"><i class="fas fa-book"></i>📚 코스 정보 (선생님용)</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <iframe src="index_embed.php" width="100%" height="300" style="border: none; border-radius: 5px;"></iframe>
            </div>
        </div>
        <?php endif; ?>
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
        
        // Additional functions from today.php for progress card functionality
        function Resttime(eventid, userid, goalid, checked) {
            // AJAX call to handle DMN rest time checkbox
            fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=rest_time&eventid=' + eventid + '&userid=' + userid + '&goalid=' + goalid + '&checked=' + (checked ? 1 : 0)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showNotification(checked ? 'DMN 휴식이 설정되었습니다.' : 'DMN 휴식이 해제되었습니다.', 'success');
                } else {
                    showNotification('설정 변경에 실패했습니다.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('오류가 발생했습니다.', 'error');
            });
        }
        
        function ChangeCheckBox(eventid, userid, goalid, checked) {
            // AJAX call to handle offline status checkbox
            fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=change_checkbox&eventid=' + eventid + '&userid=' + userid + '&goalid=' + goalid + '&checked=' + (checked ? 1 : 0)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showNotification(checked ? '오프라인 모드가 설정되었습니다.' : '오프라인 모드가 해제되었습니다.', 'success');
                } else {
                    showNotification('설정 변경에 실패했습니다.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('오류가 발생했습니다.', 'error');
            });
        }
        
        function submittoday(eventid, userid, checked) {
            // AJAX call to handle today submission (home inspection checkbox)
            fetch('https://mathking.kr/moodle/local/augmented_teacher/ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=submit_today&eventid=' + eventid + '&userid=' + userid + '&checked=' + (checked ? 1 : 0)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showNotification(checked ? '귀가검사가 완료되었습니다.' : '귀가검사가 취소되었습니다.', 'success');
                } else {
                    showNotification('검사 상태 변경에 실패했습니다.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('오류가 발생했습니다.', 'error');
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