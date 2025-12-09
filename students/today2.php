<?php 
// 오류 출력 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 성능 측정 시작
$GLOBALS['performance_start'] = microtime(true);

// Moodle 설정 불러오기
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 네비게이션 바 및 기본 변수
include("navbar.php");
$tbegin = isset($_GET["tb"]) ? $_GET["tb"] : 604800; 
$maxtime = time() - $tbegin;
$timecreated = time();

// 기본 변수 확인 및 설정
if (!isset($studentid)) {
    $studentid = isset($_GET['id']) ? $_GET['id'] : $USER->id;
}

if (!isset($role)) {
    $role = ($USER->id == $studentid) ? 'student' : 'teacher';
}

// 캐싱 관련 변수 설정
$cache_file = sys_get_temp_dir() . '/student_' . $studentid . '_cache_' . $tbegin . '.json';
$cache_lifetime = 300; // 캐시 유효 시간 (초)
$refresh_cache = false;
$diff_only = false; // diff 방식 사용 여부
$use_cache = false;
$last_cache_time = 0;

// 캐시 관리 함수 정의
function invalidate_student_cache($studentid) {
    $pattern = sys_get_temp_dir() . '/student_' . $studentid . '_cache_*.json';
    $files = glob($pattern);
    
    if ($files) {
        foreach ($files as $file) {
            @unlink($file);
        }
    }
    return count($files);
}

// 캐시 갱신 플래그 확인
if (isset($_GET['refresh']) && $_GET['refresh'] == 'true') {
    $refresh_cache = true;
    invalidate_student_cache($studentid);
}

// diff 방식 사용 확인
if (isset($_GET['diff']) && $_GET['diff'] == 'true') {
    $diff_only = true;
}

// 캐시 파일 존재 및 유효성 검사
if (!$refresh_cache && file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_lifetime)) {
    $cached_data = json_decode(file_get_contents($cache_file), true);
    if ($cached_data && isset($cached_data['timestamp']) && isset($cached_data['data'])) {
        $use_cache = true;
        $last_cache_time = $cached_data['timestamp'];
    }
}

// diff 모드 메시지 작성
$diff_mode_ui = '';
if ($role !== 'student') {
    // 관리자에게만 diff 모드 옵션 표시
    $diff_mode_ui = '
    <div style="background-color: #f8f9fa; padding: 5px 10px; margin-bottom: 15px; border-radius: 5px;">
        <label style="font-weight: bold; margin-right: 15px;">데이터 로딩 방식:</label>
        <a href="' . $_SERVER['PHP_SELF'] . '?tb=' . $tbegin . '&id=' . $studentid . '" 
           style="margin-right: 10px; ' . (!$diff_only ? 'font-weight: bold; color: #007bff;' : '') . '">
           전체 데이터 로딩
        </a>
        <a href="' . $_SERVER['PHP_SELF'] . '?tb=' . $tbegin . '&id=' . $studentid . '&diff=true" 
           style="margin-right: 10px; ' . ($diff_only ? 'font-weight: bold; color: #007bff;' : '') . '">
           Diff 방식 (변경된 데이터만)
        </a>
        <a href="' . $_SERVER['PHP_SELF'] . '?tb=' . $tbegin . '&id=' . $studentid . '&refresh=true" 
           style="margin-right: 10px; color: #dc3545;">
           캐시 비우기
        </a>';
    
    // 캐시 사용 중일 때 캐시 정보 표시
    if ($use_cache) {
        $cache_time = date('Y-m-d H:i:s', $last_cache_time);
        $cache_age = round((time() - $last_cache_time) / 60, 1);
        $diff_mode_ui .= '
        <span style="margin-left: 15px; color: #6c757d; font-size: 0.9em;">
            캐시: ' . $cache_time . ' (' . $cache_age . '분 전)
        </span>';
    }
    
    $diff_mode_ui .= '</div>';
}

// 마지막 DB 호출 이후 변경사항 확인용 타임스탬프
$last_updated = 0;
if ($use_cache) {
    $last_updated = $cached_data['timestamp'];
}

// diff 체크: 마지막 업데이트 이후 변경된 레코드만 가져오기
$changes_detected = false;
$changed_data = [];
$change_logs = [];

if ($use_cache) {
    // 변경 감지 쿼리 - 세분화하여 변경된 데이터만 식별
    $change_logs['check_start'] = microtime(true);
    
    // 1. 새로운 퀴즈 시도 또는 변경된 시도 확인
    $changed_quiz_attempts = $DB->get_records_sql("SELECT qa.id 
                                       FROM mdl_quiz_attempts qa
                                       WHERE qa.userid = ? AND qa.timemodified > ?", 
                                       [$studentid, $last_updated]);
    $changed_data['quiz_attempts'] = count($changed_quiz_attempts) > 0;
    
    // 2. 화이트보드 변경 확인
    $changed_whiteboards = $DB->get_records_sql("SELECT id 
                                   FROM mdl_abessi_messages 
                                   WHERE userid = ? AND timemodified > ?", 
                                   [$studentid, $last_updated]);
    $changed_data['whiteboards'] = count($changed_whiteboards) > 0;
    
    // 3. 다른 필요한 변경 사항들 확인
    // [필요한 다른 변경 감지 쿼리들을 추가]
    
    $change_logs['check_end'] = microtime(true);
    $change_logs['check_time'] = $change_logs['check_end'] - $change_logs['check_start'];
    
    // 어떤 데이터든 변경이 있으면 변경 감지
    $changes_detected = $changed_data['quiz_attempts'] || $changed_data['whiteboards'];
}

// 데이터 로딩 시작 (캐시 또는 DB)
$data_load_start = microtime(true);

if ($use_cache && !$changes_detected) {
    // 캐시된 데이터 사용 - 변경 없음
    $data_source = 'cache_full';
    extract($cached_data['data']);
} 
elseif ($use_cache && $changes_detected && $diff_only) {
    // Diff 방식: 변경된 데이터만 가져와서 캐시와 병합
    $data_source = 'cache_diff';
    
    // 1. 기본 데이터는 캐시에서 가져오기
    extract($cached_data['data']);
    
    // 2. 변경된 데이터만 DB에서 가져와 업데이트
    if ($changed_data['quiz_attempts']) {
        $diff_quizattempts = $DB->get_records_sql("SELECT qa.id, qa.userid, qa.quiz, qa.attempt, qa.timestart, qa.timefinish, 
                                          qa.state, qa.sumgrades, qa.timemodified, qa.layout, qa.modified, qa.comment,
                                          q.name, q.id as qid, q.sumgrades as tgrades, q.instruction, q.review, q.comment as qcomment
                                          FROM mdl_quiz_attempts qa
                                          JOIN mdl_quiz q ON qa.quiz = q.id
                                          WHERE qa.timemodified > ? AND qa.userid = ?
                                          ORDER BY qa.id DESC",
                                         [$last_updated, $studentid]);
        
        // 기존 퀴즈 결과와 새 결과 병합
        $diff_result = json_decode(json_encode($diff_quizattempts), true);
        foreach ($diff_result as $key => $value) {
            // 이미 존재하는 ID의 레코드 업데이트 또는 새 레코드 추가
            $found = false;
            foreach ($quizresult as $i => $existing) {
                if ($existing['id'] == $value['id']) {
                    $quizresult[$i] = $value; // 기존 레코드 갱신
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $quizresult[] = $value; // 새 레코드 추가
            }
        }
        
        // 모듈 ID 매핑 업데이트
        $diff_quiz_ids = array_map(function($item) { return $item['quiz']; }, $diff_result);
        if (!empty($diff_quiz_ids)) {
            $diff_quiz_ids_str = implode(',', $diff_quiz_ids);
            $diff_module_records = $DB->get_records_sql("SELECT instance, id FROM mdl_course_modules WHERE instance IN ($diff_quiz_ids_str)");
            
            foreach ($diff_module_records as $record) {
                $module_ids[$record->instance] = $record->id;
            }
        }
    }
    
    if ($changed_data['whiteboards']) {
        // 화이트보드 데이터 업데이트 (변경된 것만)
        $diff_handwriting = $DB->get_records_sql("SELECT * FROM mdl_abessi_messages 
                                         WHERE userid = ? AND timemodified > ? AND status NOT LIKE 'attempt' 
                                         AND contentstype = 2 AND (active = 1 OR status = 'flag') 
                                         ORDER BY tlaststroke DESC",
                                        [$studentid, $last_updated]);
        
        $diff_result = json_decode(json_encode($diff_handwriting), true);
        foreach ($diff_result as $key => $value) {
            $found = false;
            foreach ($result1 as $i => $existing) {
                if ($existing['id'] == $value['id']) {
                    $result1[$i] = $value; // 기존 레코드 갱신
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result1[] = $value; // 새 레코드 추가
            }
        }
    }
    
    // 업데이트된 캐시 저장
    $cache_data = [
        'timestamp' => time(),
        'data' => compact('quizresult', 'module_ids', 'result1', 'handwriting')
    ];
    file_put_contents($cache_file, json_encode($cache_data));
} 
else {
    // 전체 데이터 새로 가져오기
    $data_source = 'db_full';
    
    // 기존 데이터베이스 조회 코드...
    $timestart2=time()-$tbegin;
    $adayAgo=time()-43200;
    $aweekAgo=time()-604800;
    $timestart3=time()-86400*14;
    
    // 퀴즈 시도 정보 가져오기 - 인덱스 활용 최적화
    $quizattempts = $DB->get_records_sql("SELECT qa.id, qa.userid, qa.quiz, qa.attempt, qa.timestart, qa.timefinish, 
                                      qa.state, qa.sumgrades, qa.timemodified, qa.layout, qa.modified, qa.comment,
                                      q.name, q.id as qid, q.sumgrades as tgrades, q.instruction, q.review, q.comment as qcomment
                                      FROM mdl_quiz_attempts qa
                                      JOIN mdl_quiz q ON qa.quiz = q.id
                                      WHERE qa.timemodified > ? AND qa.userid = ?
                                      ORDER BY qa.id DESC LIMIT 200",
                                     [$timestart2, $studentid]);
    
    $quizresult = json_decode(json_encode($quizattempts), true);
    
    // 모듈 ID 매핑 - 한 번의 쿼리로 여러 퀴즈의 모듈 ID 가져오기
    $quiz_ids = array_map(function($item) { return $item['quiz']; }, $quizresult);
    $module_ids = [];
    
    if (!empty($quiz_ids)) {
        $quiz_ids_str = implode(',', $quiz_ids);
        $module_records = $DB->get_records_sql("SELECT instance, id FROM mdl_course_modules WHERE instance IN ($quiz_ids_str)");
        
        foreach ($module_records as $record) {
            $module_ids[$record->instance] = $record->id;
        }
    }
    
    // 화이트보드 데이터 가져오기
    $handwriting = $DB->get_records_sql("SELECT * FROM mdl_abessi_messages 
                                     WHERE userid = ? AND status NOT LIKE 'attempt' 
                                     AND tlaststroke > ? AND contentstype = 2 
                                     AND (active = 1 OR status = 'flag') 
                                     ORDER BY tlaststroke DESC LIMIT 100",
                                    [$studentid, $timestart2]);
    
    $result1 = json_decode(json_encode($handwriting), true);
    
    // 캐시 파일 작성
    $cache_data = [
        'timestamp' => time(),
        'data' => [
            'quizresult' => $quizresult,
            'module_ids' => $module_ids,
            'result1' => $result1,
            'handwriting' => $handwriting
        ]
    ];
    file_put_contents($cache_file, json_encode($cache_data));
}

$data_load_end = microtime(true);
$data_load_time = $data_load_end - $data_load_start;

// 성능 정보
$page_load_info = [
    'data_source' => $data_source,
    'load_time' => $data_load_time,
    'cache_check_time' => isset($change_logs['check_time']) ? $change_logs['check_time'] : 0,
    'total_time' => microtime(true) - $GLOBALS['performance_start']
];

// 성능 정보 HTML 출력
$performance_info = '';
if ($role !== 'student') {
    $performance_info = '<div style="background-color: #f8f9fa; padding: 5px 10px; margin-top: 10px; border-radius: 5px; font-size: 0.85em;">
        <span style="font-weight: bold;">성능 정보:</span>
        <span>데이터 소스: ' . $data_source . '</span> | 
        <span>로드 시간: ' . round($data_load_time * 1000, 1) . 'ms</span> | 
        <span>총 실행 시간: ' . round((microtime(true) - $GLOBALS['performance_start']) * 1000, 1) . 'ms</span>
    </div>';
}

// 이하는 데이터 처리 변수들 초기화
$nsynapse=0;
$sumSynapse=0;
$nreview=0;
$nreview2=0;
$ncomplete=0;
$nappraise=0;
$totalappraise=0;
$wboardScore=0;  
$nwboard=0;
$nrecovery=0;
$nask=0;
$nflag=0; 
$ntotal=0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학생 정보</title>
    <style>
    .my-background-color .swal2-container {
      background-color: black;
    }
    
    .feel {
      margin: 0px 5px;
      background-color: white;
      height:30px;
    }
    </style>
</head>
<body>

<?php
// diff 모드 UI 출력
echo $diff_mode_ui;

// 여기에 기존 데이터 출력 코드 추가...
echo "<p>학생 정보를 불러오는 중입니다...</p>";
echo "<p>학생 ID: $studentid</p>";
if (isset($quizresult)) {
    echo "<p>불러온 퀴즈 데이터: " . count($quizresult) . "개</p>";
}
if (isset($result1)) {
    echo "<p>불러온 화이트보드 데이터: " . count($result1) . "개</p>";
}

// 성능 정보 출력
echo $performance_info;
?>

<script type="text/javascript">
// 주기적 데이터 갱신 기능
function setupAutoRefresh() {
    // 현재 URL에서 refresh 파라미터 제거
    var currentUrl = window.location.href;
    var cleanUrl = currentUrl.replace(/[?&]refresh=true/, '');
    
    // 5분(300,000ms) 후에 페이지 새로고침 (캐시 만료 시점)
    setTimeout(function() {
        // 관리자일 경우 새로고침 알림 표시
        if (isAdmin) {
            if (confirm('데이터가 5분 이상 지났습니다. 페이지를 새로고침 하시겠습니까?')) {
                window.location.href = cleanUrl + (cleanUrl.indexOf('?') > -1 ? '&' : '?') + 'refresh=true';
            }
        } else {
            // 학생인 경우 자동 새로고침
            window.location.href = cleanUrl;
        }
    }, 300000);
}

// 자동 갱신 설정
var isAdmin = <?php echo ($role !== 'student' ? 'true' : 'false'); ?>;
var useCache = <?php echo ($use_cache && !$refresh_cache ? 'true' : 'false'); ?>;
var isDiffMode = <?php echo ($diff_only ? 'true' : 'false'); ?>;

if (useCache) {
    setupAutoRefresh();
}

// 페이지 로드 정보 표시 (관리자 전용)
<?php if ($role !== 'student'): ?>
console.log('페이지 로드 정보:', <?php echo json_encode($page_load_info); ?>);
<?php endif; ?>
</script>
</body>
</html>
