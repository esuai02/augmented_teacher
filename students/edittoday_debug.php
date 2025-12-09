<?php
// ===== 디버그 모드 활성화 =====
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_edittoday_error.log');

// 디버그 함수
function debug_log($message, $line) {
    echo "<!-- DEBUG LINE $line: $message -->\n";
    error_log("edittoday.php:$line - $message");
}

try {
    debug_log("Starting edittoday.php", __LINE__);

    // Moodle config 로드
    debug_log("Including moodle config", __LINE__);
    include_once("/home/moodle/public_html/moodle/config.php");
    debug_log("Moodle config loaded successfully", __LINE__);

    // navbar 로드 전 필수 변수 체크
    debug_log("Before navbar.php - Checking required variables", __LINE__);

    include("navbar.php");
    debug_log("navbar.php loaded successfully", __LINE__);

    // navbar.php 후 변수 체크
    debug_log("Checking variables after navbar.php", __LINE__);
    $required_vars = ['role', 'studentid', 'timecreated', 'username', 'checkgoal', 'chapterlog', 'termplan', 'EGinputtime', 'termMission', 'dreamdday', 'hideinput', 'timeback'];
    foreach($required_vars as $var) {
        if(!isset($$var)) {
            debug_log("WARNING: Variable \$$var is not defined", __LINE__);
            // 기본값 설정
            if($var === 'hideinput') $$var = 0;
            if($var === 'checkgoal') $$var = null;
            if($var === 'chapterlog') $$var = (object)['cid' => 0, 'nch' => 0];
            if($var === 'termplan') $$var = (object)['id' => 0, 'dreamchallenge' => ''];
            if($var === 'EGinputtime') $$var = '';
            if($var === 'termMission') $$var = '';
            if($var === 'dreamdday') $$var = 0;
            if($var === 'timeback') $$var = time() - 86400;
        } else {
            debug_log("Variable \$$var is defined", __LINE__);
        }
    }

    // Mission log 삽입
    debug_log("Inserting mission log", __LINE__);
    if(isset($role) && $role==='student' && isset($studentid) && isset($timecreated)) {
        debug_log("Executing mission log insert", __LINE__);
        $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentedittoday','$timecreated')");
        debug_log("Mission log inserted", __LINE__);
    }

    // GET 파라미터 처리
    debug_log("Processing GET parameters", __LINE__);
    $nweek= isset($_GET["nweek"]) ? $_GET["nweek"] : null;
    $mode= isset($_GET["mode"]) ? $_GET["mode"] : null;
    $gtype= isset($_GET["gtype"]) ? $_GET["gtype"] : null;
    $inputtext= isset($_GET["cntinput"]) ? $_GET["cntinput"] : null;
    debug_log("GET params - nweek: $nweek, mode: $mode, gtype: $gtype", __LINE__);

    $selectgtype1 = '';
    $selectgtype2 = '';
    $selectgtype3 = '';
    if($gtype && strpos($gtype, '주간목표')!==false) {
        $selectgtype2='selected';
    } else {
        $selectgtype1='selected';
    }

    if($nweek==NULL) $nweek=15;

    debug_log("Checking timecreated variable", __LINE__);
    if(!isset($timecreated)) {
        debug_log("ERROR: timecreated is not set, using current time", __LINE__);
        $timecreated = time();
    }

    $timestart=$timecreated-604800*2;
    $aweekago=$timecreated-604800;

    debug_log("Checking username variable", __LINE__);
    if(isset($username) && isset($username->lastaccess) && isset($studentid)) {
        if($timecreated-$username->lastaccess>43200) {
            debug_log("Updating lastlogin", __LINE__);
            $DB->execute("UPDATE {user} SET lastlogin='$timecreated' WHERE id LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");
        }
    } else {
        debug_log("WARNING: username or lastaccess not available", __LINE__);
    }

    // 최근 3주 기간 계산
    debug_log("Calculating time ranges", __LINE__);
    $wtimestart1 = strtotime('monday this week 00:00:00');
    $wtimestart2 = strtotime('monday last week 00:00:00');
    $wtimestart3 = strtotime('monday -2 weeks 00:00:00');
    $threeWeeksAgo = strtotime('-3 weeks 00:00:00');

    $adayAgo=time()-43200;
    $goalhistory0 = '';
    $goalhistory1 = '';
    $goalhistory = '';
    $gptprep = '';
    $recentactivities1 = '';
    $recentactivities2 = '';

    $newwbid='_user'.$studentid.'_date'.date('Y_m_d', $timecreated);
    debug_log("newwbid: $newwbid", __LINE__);

    // 데이터베이스 쿼리 시작
    debug_log("Fetching today goals", __LINE__);
    if(!isset($studentid)) {
        throw new Exception("studentid is not defined at line " . __LINE__);
    }

    $todayGoalsRaw = $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type='오늘목표' OR type='검사요청') AND timecreated>='$threeWeeksAgo' ORDER BY timecreated DESC");
    debug_log("Today goals fetched: " . ($todayGoalsRaw ? count($todayGoalsRaw) : 0) . " records", __LINE__);

    $todayGoalsByDate = array();

    if($todayGoalsRaw) {
        foreach($todayGoalsRaw as $goal) {
            $dateKey = date('Y-m-d', $goal->timecreated);
            if(!isset($todayGoalsByDate[$dateKey])) {
                $todayGoalsByDate[$dateKey] = $goal;
            }
        }
    }
    debug_log("Today goals grouped by date: " . count($todayGoalsByDate) . " days", __LINE__);

    echo "<h3 style='color:green;'>디버그 체크포인트 통과: 라인 " . __LINE__ . "</h3>";
    echo "<p>이 메시지가 보이면 여기까지는 정상 실행되었습니다.</p>";
    echo "<pre>";
    echo "studentid: " . (isset($studentid) ? $studentid : 'NOT SET') . "\n";
    echo "timecreated: " . (isset($timecreated) ? date('Y-m-d H:i:s', $timecreated) : 'NOT SET') . "\n";
    echo "todayGoals count: " . count($todayGoalsByDate) . "\n";
    echo "</pre>";

    // 여기서 원본 코드를 계속 실행할 수 있습니다
    // 일단 여기까지만 테스트

    debug_log("Script completed successfully up to checkpoint", __LINE__);

} catch (Exception $e) {
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h2>오류 발생!</h2>";
    echo "<p><b>파일:</b> " . __FILE__ . "</p>";
    echo "<p><b>라인:</b> " . $e->getLine() . "</p>";
    echo "<p><b>메시지:</b> " . $e->getMessage() . "</p>";
    echo "<p><b>스택 트레이스:</b></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    error_log("FATAL ERROR in edittoday.php: " . $e->getMessage() . " at line " . $e->getLine());
}
?>
