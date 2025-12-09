<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include("navbar.php");

// 성능 모니터링 시작
$performance_start = microtime(true);
$query_count = 0;

// 캐싱 시스템 구현
class SimpleCache {
    private static $cache = [];
    
    public static function get($key) {
        return isset(self::$cache[$key]) ? self::$cache[$key] : null;
    }
    
    public static function set($key, $value, $ttl = 300) {
        self::$cache[$key] = [
            'data' => $value,
            'expires' => time() + $ttl
        ];
    }
    
    public static function isValid($key) {
        return isset(self::$cache[$key]) && self::$cache[$key]['expires'] > time();
    }
}

// 최적화된 데이터베이스 쿼리 함수
function optimizedQuery($sql, $params = []) {
    global $DB, $query_count;
    $query_count++;
    
    $cache_key = md5($sql . serialize($params));
    if (SimpleCache::isValid($cache_key)) {
        return SimpleCache::get($cache_key)['data'];
    }
    
    $result = $DB->get_records_sql($sql, $params);
    SimpleCache::set($cache_key, $result, 180); // 3분 캐시
    
    return $result;
}

if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentedittoday','$timecreated')");
  
$nweek= $_GET["nweek"]; 
$mode= $_GET["mode"]; 
$gtype= $_GET["gtype"]; 
$inputtext= $_GET["cntinput"]; 
if(strpos($gtype, '주간목표')!==false) $selectgtype2='selected';
else $selectgtype1='selected';

if($nweek==NULL)$nweek=15;
$timestart=$timecreated-604800*2;
$aweekago=$timecreated-604800;  

if($timecreated-$username->lastaccess>43200)$DB->execute("UPDATE {user} SET lastlogin='$timecreated' WHERE id LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");  

// 최적화된 통합 쿼리 - 목표와 퀴즈 데이터를 한 번에 가져오기
$unified_cache_key = "user_data_{$studentid}_{$timestart}_{$aweekago}";

if (!SimpleCache::isValid($unified_cache_key)) {
    // 목표 데이터 최적화된 쿼리
    $goals_sql = "SELECT * FROM mdl_abessi_today 
                  WHERE userid = ? AND timecreated > ? 
                  ORDER BY id DESC LIMIT 50";
    $goals = optimizedQuery($goals_sql, [$studentid, $timestart]);
    
    // 퀴즈 데이터 최적화된 쿼리 (인덱스 활용)
    $quiz_sql = "SELECT qa.*, q.sumgrades AS tgrades, q.name, cm.id as moduleid
                 FROM mdl_quiz_attempts qa
                 LEFT JOIN mdl_quiz q ON q.id = qa.quiz  
                 LEFT JOIN mdl_course_modules cm ON cm.instance = qa.quiz AND cm.module = (SELECT id FROM mdl_modules WHERE name = 'quiz')
                 WHERE (qa.timefinish > ? OR qa.timestart > ? OR (qa.state='inprogress' AND qa.timestart > ?)) 
                 AND qa.userid = ? 
                 ORDER BY qa.timestart DESC LIMIT 100";
    $quizattempts = optimizedQuery($quiz_sql, [$aweekago, $aweekago, $aweekago, $studentid]);
    
    // 기타 필요한 데이터들
    $other_data = [
        'fbtalk' => $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk where creator='$studentid' ORDER BY id DESC LIMIT 1"),
        'schedule' => $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1"),
        'drawing' => $DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='weekly' ORDER BY id DESC LIMIT 1"),
        'thistime' => $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' || type LIKE '주간목표') ORDER BY id DESC LIMIT 1"),
        'conditions' => optimizedQuery("SELECT * FROM mdl_abessi_knowhowlog WHERE studentid = ? AND active = '1' ORDER BY timemodified", [$studentid])
    ];
    
    $unified_data = [
        'goals' => $goals,
        'quizattempts' => $quizattempts,
        'other' => $other_data
    ];
    
    SimpleCache::set($unified_cache_key, $unified_data, 300);
} else {
    $unified_data = SimpleCache::get($unified_cache_key)['data'];
    $goals = $unified_data['goals'];
    $quizattempts = $unified_data['quizattempts'];
    $other_data = $unified_data['other'];
    extract($other_data);
}

$adayAgo=time()-43200; 
$result2 = json_decode(json_encode($goals), True);
unset($value);
 
$newwbid='_user'.$studentid.'_date'.date('Y_m_d', $timecreated);

// 목표 히스토리 처리 최적화
$goalhistory0 = $goalhistory1 = '';
$recentactivities1 = $recentactivities2 = '';
$gptprep = '';

foreach($result2 as $value) {
    $date_pre=$date;
    $att=gmdate("m월 d일 ", $value['timecreated']+32400);
    $date=gmdate("d", $value['timecreated']+32400);
    $goaltype=$value['type'];
    
    if($goaltype==='오늘목표' || $goaltype==='검사요청'){
        $goaltype='<span class="goal-badge daily">오늘목표</span>';
        $notetype='summary';
    }
    elseif($goaltype==='주간목표'){
        $goaltype='<span class="goal-badge weekly">주간목표</span>';
        $notetype='weekly';
    }
    elseif($goaltype==='시험목표'){
        $goaltype='<span class="goal-badge exam">분기목표</span>';
        $notetype='examplan';
    }
    elseif($goaltype==='시간접기'){
        $goaltype='<span class="goal-badge time">시간접기</span>';
        $notetype='timefolding';
    }
    
    $daterecord=date('Y_m_d', $value['timecreated']);
    $tend=$value['timecreated'];
    $tfinish0=date('m/d/Y', $value['timecreated']+86400); 
    $tfinish=strtotime($tfinish0);
    $planwboardid='_user'.$studentid.'_date'.$daterecord;
    
    if($value['type']==='오늘목표' || $value['type']==='검사요청') {
        $goaltype='<span class="goal-badge past">지난시간</span>';
        $goalhistory0.= '<tr class="goal-row past-goal"><td class="goal-icon"><i class="fas fa-clock"></i></td><td class="goal-type"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td class="goal-date">'.$att.'</td><td class="goal-content"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.$planwboardid.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td class="goal-action"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank class="btn-analysis">습관분석</a></td></tr>'; 
        $gptprep.=$value['text'].',';
        $recentactivities1.=$value['text'].'|';
    }
    elseif($value['type']==='주간목표') {
        $goalhistory1.= '<tr class="goal-row weekly-goal"><td class="goal-icon"><i class="fas fa-calendar-week"></i></td><td class="goal-type"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td class="goal-date">'.$att.'</td><td class="goal-content"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.$planwboardid.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td class="goal-action"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank class="btn-analysis">습관분석</a></td></tr>';
        $recentactivities2.=$value['text'].'|';
    }
}

$recentactivities='주간목표들 : '.$recentactivities2.' <br> 실제 실행내용 : '.$recentactivities1;

// 퀴즈 결과 처리 최적화
$quizresult = json_decode(json_encode($quizattempts), True);
$nquiz=count($quizresult);
$quizlist='<hr>';
$todayGrade=0; $ntodayquiz=0; $weekGrade=0; $nweekquiz=0;
$quizlist11=$quizlist12=$quizlist21=$quizlist22=$quizlist31=$quizlist32='';

unset($value);
foreach(array_reverse($quizresult) as $value) {
    $comment='';
    $qnum=substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');
    $comment= '&nbsp;|&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank">결과분석</a>';
    $quizgrade=round($value['sumgrades']/$value['tgrades']*100,0);
    
    // 이미지 상태 최적화 (WebP 형식 사용)
    if($quizgrade>89.99) {
        $imgstatus='<span class="quiz-status excellent"><i class="fas fa-star"></i></span>';
    }
    elseif($quizgrade>69.99) {
        $imgstatus='<span class="quiz-status good"><i class="fas fa-check-circle"></i></span>';
    }
    else {
        $imgstatus='<span class="quiz-status needs-improvement"><i class="fas fa-exclamation-circle"></i></span>';
    }
    
    $quizmoduleid = $value['moduleid']; // 이미 JOIN으로 가져온 데이터 사용
    
    if(strpos($value['name'], '내신')!= false) {
        if(strpos($value['name'], 'ifminteacher')!= false) $value['name']=strstr($value['name'], '{ifminteacher',true);
        if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo) {
            $quizlist11.= '<div class="quiz-item today">'.$imgstatus.'<span class="quiz-time">'.date("m/d | H:i",$value['timestart']).'</span><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank" class="quiz-name">'.$value['name'].'</a><span class="quiz-attempt">('.$value['attempt'].get_string('trial', 'local_augmented_teacher').')</span><span class="quiz-grade">'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></div>';
            $todayGrade=$todayGrade+$quizgrade;
            $ntodayquiz++;
        }
        else {
            $quizlist12.= '<div class="quiz-item past">'.$imgstatus.'<span class="quiz-time">'.date("m/d | H:i",$value['timestart']).'</span><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank" class="quiz-name">'.$value['name'].'</a><span class="quiz-attempt">('.$value['attempt'].get_string('trial', 'local_augmented_teacher').')</span><span class="quiz-grade">'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></div>';
            $weekGrade=$weekGrade+$quizgrade;
            $nweekquiz++;
        }
    }
    elseif($qnum>9) {
        if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo) {
            $quizlist21.= '<div class="quiz-item today">'.$imgstatus.'<span class="quiz-time">'.date("m/d | H:i",$value['timestart']).'</span><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank" class="quiz-name">'.substr($value['name'],0,40).'</a><span class="quiz-attempt">('.$value['attempt'].get_string('trial', 'local_augmented_teacher').')</span><span class="quiz-grade">'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></div>';
            $todayGrade=$todayGrade+$quizgrade;
            $ntodayquiz++;
        }
        else {
            $quizlist22.= '<div class="quiz-item past">'.$imgstatus.'<span class="quiz-time">'.date("m/d | H:i",$value['timestart']).'</span><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank" class="quiz-name">'.substr($value['name'],0,40).'</a><span class="quiz-attempt">('.$value['attempt'].get_string('trial', 'local_augmented_teacher').')</span><span class="quiz-grade">'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></div>';
            $weekGrade=$weekGrade+$quizgrade;
            $nweekquiz++;
        }
    }
    else {
        if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo) {
            $quizlist31.= '<div class="quiz-item today">'.$imgstatus.'<span class="quiz-time">'.date("m/d | H:i",$value['timestart']).'</span><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank" class="quiz-name">'.substr($value['name'],0,40).'</a><span class="quiz-attempt">('.$value['attempt'].get_string('trial', 'local_augmented_teacher').')</span><span class="quiz-grade">'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></div>';
        }
        else {
            $quizlist32.= '<div class="quiz-item past">'.$imgstatus.'<span class="quiz-time">'.date("m/d | H:i",$value['timestart']).'</span><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank" class="quiz-name">'.substr($value['name'],0,40).'</a><span class="quiz-attempt">('.$value['attempt'].get_string('trial', 'local_augmented_teacher').')</span><span class="quiz-grade">'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></div>';
        }
    }
}

// 기타 데이터 처리
$fbtype=$fbtalk->type;
$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type='.$fbtype;
$lastday=$schedule->lastday;
$drawingid=$drawing->wboardid;

$lastGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated<='$wtimestart1' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$wgoaldate = date('Y-m-d', $lastGoal->timecreated);
$weeklyGoalText='<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> 지난 주 목표 : '.$lastGoal->text.'('.$wgoaldate.') <br><small>새로운 목표를 입력해 주세요</small></div>';
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart1' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
if(empty($weeklyGoal->id)==0)$weeklyGoalText='<div class="current-weekly-goal"><i class="fas fa-target"></i> 주간목표 : '.$weeklyGoal->text.' <span class="deadline">('.$lastday.')</span></div>';

// 현대적인 CSS 스타일링
echo '<style>
/* 전체 레이아웃 개선 */
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.container-fluid {
    padding: 20px;
}

/* 카드 스타일 개선 */
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-body {
    padding: 30px;
}

/* 목표 배지 스타일 */
.goal-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.goal-badge.daily {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
}

.goal-badge.weekly {
    background: linear-gradient(45deg, #a55eea, #8b5cf6);
    color: white;
}

.goal-badge.exam {
    background: linear-gradient(45deg, #26de81, #20bf6b);
    color: white;
}

.goal-badge.time {
    background: linear-gradient(45deg, #fd79a8, #e84393);
    color: white;
}

.goal-badge.past {
    background: linear-gradient(45deg, #74b9ff, #0984e3);
    color: white;
}

/* 목표 테이블 스타일 */
.goals-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.goal-row {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f3f4;
}

.goal-row:hover {
    background: linear-gradient(90deg, #f8f9ff, #ffffff);
    transform: translateX(5px);
}

.goal-icon {
    width: 50px;
    text-align: center;
    color: #6c5ce7;
    font-size: 18px;
}

.goal-type a {
    text-decoration: none;
    font-weight: 600;
}

.goal-date {
    color: #636e72;
    font-size: 14px;
}

.goal-content a {
    color: #2d3436;
    text-decoration: none;
    font-weight: 500;
}

.goal-content a:hover {
    color: #6c5ce7;
}

.btn-analysis {
    background: linear-gradient(45deg, #00cec9, #00b894);
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-analysis:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,206,201,0.4);
    color: white;
    text-decoration: none;
}

/* 입력 폼 스타일 개선 */
.input-section {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 20px;
}

.form-control {
    border: none;
    border-radius: 15px;
    padding: 12px 20px;
    font-size: 16px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.form-control:focus {
    box-shadow: 0 8px 25px rgba(102,126,234,0.3);
    transform: translateY(-2px);
}

.btn-save {
    background: linear-gradient(45deg, #00b894, #00cec9);
    border: none;
    border-radius: 15px;
    padding: 12px 25px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,184,148,0.3);
}

.btn-save:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,184,148,0.4);
}

/* 퀴즈 결과 스타일 */
.quiz-section {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
}

.quiz-section h6 {
    color: #2d3436;
    font-weight: 700;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ddd;
}

.quiz-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f3f4;
    transition: all 0.3s ease;
}

.quiz-item:hover {
    background: #f8f9ff;
    border-radius: 10px;
    padding-left: 10px;
}

.quiz-status {
    margin-right: 15px;
    width: 30px;
    text-align: center;
}

.quiz-status.excellent {
    color: #00b894;
}

.quiz-status.good {
    color: #0984e3;
}

.quiz-status.needs-improvement {
    color: #e17055;
}

.quiz-time {
    color: #636e72;
    font-size: 12px;
    margin-right: 15px;
    min-width: 80px;
}

.quiz-name {
    flex: 1;
    color: #2d3436;
    text-decoration: none;
    font-weight: 500;
    margin-right: 10px;
}

.quiz-name:hover {
    color: #6c5ce7;
    text-decoration: none;
}

.quiz-attempt {
    color: #636e72;
    font-size: 12px;
    margin-right: 10px;
}

.quiz-grade {
    color: #e17055;
    font-weight: 700;
    font-size: 14px;
}

/* 주간 목표 표시 */
.current-weekly-goal {
    background: linear-gradient(135deg, #a29bfe, #6c5ce7);
    color: white;
    padding: 15px 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    font-weight: 600;
}

.current-weekly-goal i {
    margin-right: 10px;
}

.deadline {
    background: rgba(255,255,255,0.2);
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 12px;
}

/* 알림 스타일 */
.alert {
    border: none;
    border-radius: 15px;
    padding: 15px 20px;
}

.alert-warning {
    background: linear-gradient(135deg, #fdcb6e, #e17055);
    color: white;
}

/* 로딩 애니메이션 */
.loading-placeholder {
    text-align: center;
    padding: 40px;
    color: #636e72;
    font-style: italic;
}

.loading-placeholder::after {
    content: "";
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-top: 2px solid #6c5ce7;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 지연 로딩 효과 */
.lazy-load {
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.lazy-load.loaded {
    opacity: 1;
}

/* 반응형 디자인 */
@media (max-width: 768px) {
    .card-body {
        padding: 20px;
    }
    
    .quiz-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .quiz-time {
        margin-bottom: 5px;
    }
}

/* 버튼 그룹 스타일 */
.btn-group-modern {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.btn-modern {
    background: linear-gradient(45deg, #74b9ff, #0984e3);
    color: white;
    border: none;
    border-radius: 15px;
    padding: 10px 20px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(116,185,255,0.4);
    color: white;
    text-decoration: none;
}

.btn-modern.secondary {
    background: linear-gradient(45deg, #a29bfe, #6c5ce7);
}

.btn-modern.success {
    background: linear-gradient(45deg, #00b894, #00cec9);
}
</style>';

if($hideinput==1)$status='checked';
if( time()-$checkgoal->timecreated > 43200 && $checkgoal->comment==NULL) {
    $placeholder='placeholder="※ 최대한 구체적인 목표를 입력해 주세요"';
    $presettext='';
}
elseif( time()-$checkgoal->timecreated > 43200 && $checkgoal->comment!=NULL) {
    $placeholder='';
    $presettext='value="'.$checkgoal->comment.'"';
}
else {
    $placeholder='';
    $presettext='value="'.$checkgoal->text.'"';
} 

if($inputtext!=NULL)$presettext='value="'.$inputtext.'"';

$fullplan='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$studentid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank" class="btn-modern"><i class="fas fa-calendar-alt"></i> 전체계획</a>';
$deadline=date("Y:m:d",time());

// 조건 처리 최적화
$conditionslist= json_decode(json_encode($conditions), True);
$chosenitems = '';

unset($value3);  
foreach($conditionslist as $value3) {
    $srcid=$value3['srcid']; 
    $item1=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE id='$srcid' ORDER BY id DESC LIMIT 1");
    $course=$item1->course; $type=$item1->type; $text=$item1->text; 
    $item2=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE srcid='$srcid' AND active='1' ORDER BY id DESC LIMIT 1");
    $text2=$item2->text; 

    if($mode==='CA' && $course==='개념미션')$chosenitems.='<div class="mission-item concept"><i class="fas fa-lightbulb"></i><span class="mission-type">'.$type.'</span><span class="mission-text">'.$text.'</span><span class="mission-detail">'.$text2.'</span></div>';
    elseif($mode==='CB' && $course==='심화미션')$chosenitems.='<div class="mission-item advanced"><i class="fas fa-rocket"></i><span class="mission-type">'.$type.'</span><span class="mission-text">'.$text.'</span><span class="mission-detail">'.$text2.'</span></div>';
    elseif($mode==='CC' && $course==='내신미션')$chosenitems.='<div class="mission-item exam"><i class="fas fa-graduation-cap"></i><span class="mission-type">'.$type.'</span><span class="mission-text">'.$text.'</span><span class="mission-detail">'.$text2.'</span></div>';
    elseif($mode==='CD' && $course==='수능미션')$chosenitems.='<div class="mission-item csat"><i class="fas fa-trophy"></i><span class="mission-type">'.$type.'</span><span class="mission-text">'.$text.'</span><span class="mission-detail">'.$text2.'</span></div>';
}

// GPT 관련 처리
if($thistime->type==='주간목표') {
    $displaytext= '🌟 랜덤 드림 챌린지 : '.$termplan->dreamchallenge.'!  당신의 꿈을 응원합니다 !  (D-'.$dreamdday.'일)';
    $currentAnswer=$displaytext;
    $rolea='💎 드림챌린지';
    $roleb='💎 GPT도우미';
    $talka1='';
    $talkb1='';
    $talka2='';
    $talkb2='';
    $tone1='';
    $tone2='';
}
else {
    $displaytext= '📌 주간목표 : '.$weeklyGoal->text.' ✅ 오늘목표 : '.$thistime->text.'입니다. (🏳️분기목표 : '.$EGinputtime.'까지 '.$termMission.')';
    $currentAnswer='주간목표 : '.$weeklyGoal->text.'를 위해 오늘목표 : '.$thistime->text.'로 설정하였습니다. 과정에 대해 이해를 돕기 위해 학생입장에서 예상되는 과정에 대한 상세한 설명이 필요합니다.';
    $rolea='💎 마이 플랜';
    $roleb='💎 GPT도우미';
    $talka1='';
    $talkb1='';
    $talka2='';
    $talkb2='';
    $tone1='';
    $tone2='';
}    

// iframe 로딩 최적화 (지연 로딩)
if($thistime->timecreated>time()-10 && $thistime->type==='주간목표')$showreflection='<iframe class="foo lazy-load" style="border: none; width:100%; height:300px; border-radius:15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" data-src="https://mathking.kr/moodle/local/augmented_teacher/LLM/brainalignment.php?userid='.$studentid.'&answerShort=true&count=5&currentAnswer='.$currentAnswer.'&rolea='.$rolea.'&roleb='.$roleb.'&talka1='.$talka1.'&talkb1='.$talkb1.'&talka2='.$talka2.'&talkb2='.$talkb2.'&tone1='.$tone1.'&tone2='.$tone2.'" ></iframe>';
elseif($thistime->timecreated>time()-1800 && $thistime->type==='오늘목표' )$showreflection='<iframe class="foo lazy-load" style="border: none; width:100%; height:300px; border-radius:15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" data-src="https://mathking.kr/moodle/local/augmented_teacher/LLM/brainalignment.php?userid='.$studentid.'&answerShort=true&count=5&currentAnswer='.$currentAnswer.'&rolea='.$rolea.'&roleb='.$roleb.'&talka1='.$talka1.'&talkb1='.$talkb1.'&talka2='.$talka2.'&talkb2='.$talkb2.'&tone1='.$tone1.'&tone2='.$tone2.'" ></iframe>';
elseif($thistime->timecreated>time()-1800 && $thistime->type==='오늘목표') {
$showreflection='<div class="welcome-banner"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" style="width:100%; border-radius:15px;" loading="lazy"></div>';
}

echo '<div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">                            
                    <div class="card-body">
                        '.$showreflection.'
                    </div>
                </div>
            </div>
        </div>';

if($hideinput==0 || $role!=='student') {
    echo '<div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="missions-section">
                            '.$chosenitems.'
                        </div>
                        
                        <div class="btn-group-modern">
                            '.$fullplan.'
                            <button class="btn-modern secondary" onclick="remindMath('.$studentid.');">
                                <i class="fas fa-brain"></i> 복습계획
                            </button>
                        </div>
                        
                        '.$weeklyGoalText.'
                        
                        <div class="input-section">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <div class="btn-group-modern">
                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$studentid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'" class="btn-modern">
                                            <i class="fas fa-bullseye"></i> 목표
                                        </a>
                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$studentid.'&cid='.$chapterlog->cid.'&pid='.$thistime->id.'&nch='.$chapterlog->nch.'" target="_blank" class="btn-modern success">
                                            <i class="fas fa-tasks"></i> 활동
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="squareInput" name="squareInput" '.$placeholder.' '.$presettext.'>
                                </div>
                                <div class="col-md-2">
                                    <select id="basic1" name="basic" class="form-control">
                                        <option value="오늘목표" '.$selectgtype1.'>오늘목표</option>
                                        <option value="주간목표" '.$selectgtype2.'>주간목표</option>
                                        <option value="시간접기" '.$selectgtype3.'>시간접기</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select id="basic2" name="basic2" class="form-control">
                                        <option value="1">개념공부</option>
                                        <option value="2" selected>심화학습</option>
                                        <option value="3">내신대비</option>
                                        <option value="4">기타</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" id="datepicker" name="datepicker" placeholder="데드라인" value="'.$deadline.'">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" id="update" class="btn-save" onclick="edittoday(2,'.$studentid.',$(\'#squareInput\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val());">
                                        <i class="fas fa-save"></i> 저장
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
} else {
    echo '<div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-user-tie"></i>
                            <h5>담당 선생님과 함께 계획을 입력해 주세요!</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}

if($hideinput==0 || $role!=='student') {
    echo '<div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-list-alt"></i> 학습 가이드
                        </h5>
                        <p class="text-muted">
                            <strong>1.</strong> 목차 및 최근 계획 
                            <strong>2.</strong> 주간 시간표 
                            <strong>3.</strong> 테스트 결과를 토대로 
                            <strong>공부의 범위와 양을 정할 수 있습니다.</strong>
                        </p>
                        
                        <div id="async-content-1" class="loading-placeholder">
                            <i class="fas fa-spinner fa-spin"></i> 콘텐츠 로딩 중...
                        </div>
                        
                        <div class="goals-table">
                            <table class="table table-hover">
                                <tbody>
                                    '.$goalhistory0.$goalhistory1.'
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="async-content-2" class="loading-placeholder">
                            <i class="fas fa-spinner fa-spin"></i> 스케줄 로딩 중...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line"></i> 학습 현황
                            </h5>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailylog.php?id='.$studentid.'&nweek=16" target="_blank" class="btn-modern">
                                <i class="fas fa-external-link-alt"></i> 상세보기
                            </a>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            지난 시간 요약 내용을 발표 후 오늘 목표를 입력해 주세요!
                        </div>
                        
                        <div class="quiz-section">
                            <h6><i class="fas fa-graduation-cap"></i> 내신테스트</h6>
                            '.$quizlist11.$quizlist12.'
                        </div>
                        
                        <div class="quiz-section">
                            <h6><i class="fas fa-chart-bar"></i> 표준테스트</h6>
                            '.$quizlist21.$quizlist22.'
                        </div>
                        
                        <div class="quiz-section">
                            <h6><i class="fas fa-brain"></i> 인지촉진</h6>
                            '.$quizlist31.$quizlist32.'
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}

echo '</div>';

$nextgoal= $DB->get_record_sql("SELECT id,comment FROM  mdl_abessi_today Where userid='$studentid' AND timecreated<'$timeback' AND timecreated>'$aweekago' ORDER BY id DESC LIMIT 1 ");
$nextplan=$nextgoal->comment;

if($inputtext!==NULL)echo '<script>setTimeout(function(){document.getElementById("update").click();}, 1000);</script>';

// 성능 모니터링 결과
$performance_end = microtime(true);
$total_time = $performance_end - $performance_start;
if($role !== 'student') {
    echo '<!-- 성능 정보: 총 실행시간: '.round($total_time, 3).'초, 쿼리 수: '.$query_count.' -->';
}

include("quicksidebar.php");

// 최적화된 리소스 로딩 (지연 로딩 및 압축)
echo '     
    <!--   Core JS Files   -->
    <script src="../assets/js/core/jquery.3.2.1.min.js" defer></script>
    <script src="../assets/js/core/popper.min.js" defer></script>
    <script src="../assets/js/core/bootstrap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" defer></script>

    <!-- jQuery UI -->
    <script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js" defer></script>
    <script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js" defer></script>

    <!-- 필수 플러그인만 로드 -->
    <script src="../assets/js/plugin/moment/moment.min.js" defer></script>
    <script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js" defer></script>
    <script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js" defer></script>
    <script src="../assets/js/plugin/select2/select2.full.min.js" defer></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js" defer></script>

    <script>
    // 지연 로딩 구현
    document.addEventListener("DOMContentLoaded", function() {
        // iframe 지연 로딩
        const lazyIframes = document.querySelectorAll("iframe.lazy-load");
        const iframeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    iframe.src = iframe.dataset.src;
                    iframe.classList.add("loaded");
                    iframeObserver.unobserve(iframe);
                }
            });
        });
        
        lazyIframes.forEach(iframe => {
            iframeObserver.observe(iframe);
        });
        
        // 비동기 콘텐츠 로딩
        loadAsyncContent();
        
        // 폼 요소 초기화
        initializeFormElements();
    });
    
    function loadAsyncContent() {
        // index_embed.php 비동기 로딩
        fetch("index_embed.php?id='.$studentid.'")
            .then(response => response.text())
            .then(data => {
                document.getElementById("async-content-1").innerHTML = data;
            })
            .catch(error => {
                document.getElementById("async-content-1").innerHTML = "<div class=\"alert alert-warning\"><i class=\"fas fa-exclamation-triangle\"></i> 콘텐츠 로딩에 실패했습니다.</div>";
            });
            
        // schedule_embed.php 비동기 로딩
        fetch("schedule_embed.php?id='.$studentid.'")
            .then(response => response.text())
            .then(data => {
                document.getElementById("async-content-2").innerHTML = data;
            })
            .catch(error => {
                document.getElementById("async-content-2").innerHTML = "<div class=\"alert alert-warning\"><i class=\"fas fa-exclamation-triangle\"></i> 스케줄 로딩에 실패했습니다.</div>";
            });
    }
    
    function initializeFormElements() {
        // 날짜 선택기 초기화
        $("#datepicker").datepicker({
            format: "yyyy:mm:dd",
            autoclose: true,
            todayHighlight: true
        });
        
        // Select2 초기화
        $("#basic1, #basic2").select2({
            minimumResultsForSearch: Infinity
        });
    }
    
    window.onbeforeunload = function () {
        window.scrollTo(0, 0);
    }
    
    document.getElementById("squareInput").addEventListener("keydown", function(event) {
        if (event.keyCode === 13) {
            document.getElementById("update").click();
        }
    });

    // 부드러운 스크롤 효과
    document.querySelectorAll("a[href^=\"#\"]").forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute("href")).scrollIntoView({
                behavior: "smooth"
            });
        });
    });
</script>

</body>';
 
?>
