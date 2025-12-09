<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// 학생이 자신의 정보를 보는 경우
$studentid = $_GET['studentid'] ?? null;

// studentid만 있고 teacherid가 없으면 담당 선생님을 찾아서 리다이렉트
if($studentid && !isset($_GET['teacherid']) && !isset($_GET['userid'])) {
    // 방법 1: mdl_abessi_indicators에서 teacherid 가져오기
    $indicator = $DB->get_record_sql("SELECT teacherid FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
    if($indicator && $indicator->teacherid) {
        $foundTeacherid = $indicator->teacherid;
    } else {
        // 방법 2: 학생의 firstname에서 선생님 symbol 추출하여 선생님 찾기
        $student = $DB->get_record_sql("SELECT firstname FROM mdl_user WHERE id='$studentid'");
        if($student && $student->firstname) {
            // firstname의 처음 3글자를 symbol로 사용 (chainreactionOn.php 방식 참고)
            $symbol = substr($student->firstname, 0, 3);
            $teacher = $DB->get_record_sql("SELECT userid FROM mdl_user_info_data WHERE data='$symbol' AND fieldid='79' ORDER BY id DESC LIMIT 1");
            if($teacher) {
                $foundTeacherid = $teacher->userid;
            } else {
                // 찾지 못하면 현재 사용자 ID 사용
                $foundTeacherid = $USER->id;
            }
        } else {
            $foundTeacherid = $USER->id; 
        }
    }
    
    // URL에 teacherid 추가하여 리다이렉트
    $redirectUrl = "https://mathking.kr/moodle/local/augmented_teacher/teachers/waitinglist.php?teacherid=" . $foundTeacherid . "&studentid=" . $studentid;
    header("Location: " . $redirectUrl);
    exit();
}

// 선생님 ID 가져오기
if(isset($_GET['teacherid'])) {
    $teacherid = $_GET['teacherid'];
} elseif(isset($_GET['userid'])) {
    // userid가 명시적으로 전달된 경우 (선생님 화면)
    $teacherid = $_GET['userid'];
} elseif($studentid) {
    // studentid만 있는 경우, 해당 학생의 담당 선생님 찾기
    // 방법 1: mdl_abessi_indicators에서 teacherid 가져오기
    $indicator = $DB->get_record_sql("SELECT teacherid FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
    if($indicator && $indicator->teacherid) {
        $teacherid = $indicator->teacherid;
    } else {
        // 방법 2: 학생의 firstname에서 선생님 symbol 추출하여 선생님 찾기
        $student = $DB->get_record_sql("SELECT firstname FROM mdl_user WHERE id='$studentid'");
        if($student && $student->firstname) {
            // firstname의 처음 3글자를 symbol로 사용 (chainreactionOn.php 방식 참고)
            $symbol = substr($student->firstname, 0, 3);
            $teacher = $DB->get_record_sql("SELECT userid FROM mdl_user_info_data WHERE data='$symbol' AND fieldid='79' ORDER BY id DESC LIMIT 1");
            if($teacher) {
                $teacherid = $teacher->userid;
            } else {
                // 찾지 못하면 현재 사용자 ID 사용
                $teacherid = $USER->id;
            }
        } else {
            $teacherid = $USER->id;
        }
    }
} else {
    // 둘 다 없으면 현재 사용자 ID 사용
    $teacherid = $USER->id;
}

$timecreated = time();
$halfdayago = $timecreated - 43200;
$aweekago = $timecreated - 604800;

// 담당 학생 목록 가져오기
$collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol = $teacher->symbol ?? '##';
$teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1 = $teacher1->symbol ?? '##';
$teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2 = $teacher2->symbol ?? '##';
$teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3 = $teacher3->symbol ?? '##';

$mystudents = $DB->get_records_sql("SELECT id,firstname,lastname FROM mdl_user WHERE suspended=0 AND lastaccess> '$halfdayago' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%') ORDER BY id DESC ");  

$result = json_decode(json_encode($mystudents), True);

// 질문한 학생 목록 수집
$waitingList = array();
unset($user);
foreach($result as $user) {
    $userid = $user['id'];
    $goal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated >'$halfdayago' AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1 ");
    
    // alerttime이 있고 최근 12시간 이내인 경우만 포함
    if($goal && $goal->alerttime && $goal->alerttime > $halfdayago) {
        $studentname = $user['firstname'].$user['lastname'];
        $eventtime = round(($timecreated - $goal->alerttime) / 60, 0); // 질문한 시간으로부터 경과 시간(분)
        
        $waitingList[] = array(
            'userid' => $userid,
            'studentname' => $studentname,
            'alerttime' => $goal->alerttime,
            'eventtime' => $eventtime,
            'goalid' => $goal->id
        );
    }
    
    // 새 질문 방식 (askquestion boardtype) 추가
    $askquestion = $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$userid' AND boardtype='askquestion' AND status='ask' AND timemodified >'$halfdayago' ORDER BY timemodified DESC LIMIT 1 ");
    if($askquestion && $askquestion->id) {
        $studentname = $user['firstname'].$user['lastname'];
        $eventtime = round(($timecreated - $askquestion->timemodified) / 60, 0);
        
        // 중복 체크 (이미 목록에 있는지 확인)
        $exists = false;
        foreach($waitingList as $item) {
            if($item['userid'] == $userid) {
                $exists = true;
                break;
            }
        }
        
        if(!$exists) {
            $waitingList[] = array(
                'userid' => $userid,
                'studentname' => $studentname,
                'alerttime' => $askquestion->timemodified,
                'eventtime' => $eventtime,
                'goalid' => 0,
                'wboardid' => $askquestion->wboardid
            );
        }
    }
}

// alerttime 기준으로 정렬 (오래된 질문이 먼저)
usort($waitingList, function($a, $b) {
    return $a['alerttime'] - $b['alerttime'];
});

// 현재 학생의 순서 찾기
$currentStudentPosition = null;
$currentStudentWaitTime = null;
if($studentid) {
    foreach($waitingList as $index => $item) {
        if($item['userid'] == $studentid) {
            $currentStudentPosition = $index + 1;
            $currentStudentWaitTime = $item['eventtime'];
            break;
        }
    }
}

// 타이틀 설정
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
if($studentid) {
    // 학생이 자신의 정보를 보는 경우
    $studentinfo = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $pagetitle = ($studentinfo->lastname ?? '').($studentinfo->firstname ?? '')."님의 질문 대기 현황";
} else {
    // 선생님이 전체 목록을 보는 경우
    $teacherName = ($username->lastname ?? '').($username->firstname ?? '');
    $pagetitle = "질문 대기 목록 (".$teacherName.")";
}

// 사용자 역할 확인 (student가 아닌 경우에만 iframe 표시)
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';
$isNotStudent = ($role !== 'student');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $pagetitle; ?></title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .iframe-section {
            margin-top: 30px;
            border-top: 2px solid #ddd;
            padding-top: 20px;
        }
        .iframe-header {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .iframe-container {
            height: 600px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            overflow: hidden;
        }
        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .waiting-display {
            margin-top: 20px;
        }
        .waiting-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .waiting-item:hover {
            background: #e9ecef;
        }
        .waiting-item.current {
            background: #fff3cd;
            border-left-color: #ff6b6b;
        }
        .waiting-item.next {
            background: #ffeaa7;
            border-left-color: #ffa500;
        }
        .ticket-number {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            min-width: 50px;
            text-align: center;
        }
        .waiting-item.current .ticket-number {
            color: #ff6b6b;
        }
        .waiting-item.next .ticket-number {
            color: #ffa500;
        }
        .student-name {
            flex: 1;
            font-size: 16px;
            margin-left: 15px;
        }
        .student-name a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }
        .student-name a:hover {
            color: #667eea;
            text-decoration: underline;
        }
        .wait-time {
            font-size: 14px;
            color: #666;
            min-width: 100px;
            text-align: right;
            margin-right: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            min-width: 80px;
            text-align: center;
        }
        .status-waiting {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .status-current {
            background-color: #ff6b6b;
            color: white;
            animation: pulse 1.5s infinite;
        }
        .status-next {
            background-color: #ffa500;
            color: white;
        }
        .onair-btn {
            margin-left: 15px;
            padding: 6px 15px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .onair-btn:hover {
            background-color: #45a049;
        }
        .release-btn {
            margin-left: 10px;
            padding: 6px 15px;
            background-color: grey;
            color: orange;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .release-btn:hover {
            background-color: #666;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .tab-notification {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            background-color: #ff0000;
            border-radius: 50%;
            display: none;
            animation: blink 1s infinite;
            z-index: 9999;
            box-shadow: 0 0 10px rgba(255,0,0,0.5);
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .empty-message {
            text-align: center;
            padding: 50px;
            color: #999;
            font-size: 18px;
        }
        .header-info {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="tab-notification" id="tabNotification"></div>
    
    <div class="container">
        <?php if(!$studentid): ?>
            <h1 style="color: #333; text-align: center; margin-bottom: 20px; font-size: 24px;">
                질문 대기 목록 (<?php echo ($username->lastname ?? '').($username->firstname ?? ''); ?>)
            </h1>
        <?php endif; ?>
        <div class="header-info">
            <?php if($studentid): ?>
                <?php if($currentStudentPosition): ?>
                    순서 : <strong><?php echo $currentStudentPosition; ?>번째(<?php echo $currentStudentWaitTime; ?>분)</strong>
                <?php else: ?>
                    대기중인 질문없습니다.
                <?php endif; ?>
            <?php else: ?>
                총 <?php echo count($waitingList); ?>명의 학생이 질문 대기 중입니다.
            <?php endif; ?>
        </div>
        
        <?php if(empty($waitingList)): ?>
            <div class="empty-message">
                <?php if($studentid): ?>
                    <div style="text-align: center; margin-bottom: 10px;">대기중인 질문없습니다.</div>
                    <div style="text-align: center;">질문 대기자가 없습니다.</div>
                <?php else: ?>
                    <div style="text-align: center; margin-bottom: 10px;">대기중인 질문없습니다.</div>
                    <div style="text-align: center;">질문 대기자가 없습니다.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="waiting-display">
                <?php foreach($waitingList as $index => $item): 
                    $position = $index + 1;
                    $isCurrent = ($studentid && $item['userid'] == $studentid);
                    $isNext = ($index == 0); // 첫 번째가 다음 순서
                    $statusClass = $isCurrent ? 'status-current' : ($isNext ? 'status-next' : 'status-waiting');
                    $itemClass = $isCurrent ? 'current' : ($isNext ? 'next' : '');
                    // studentid가 있을 때는 상태 텍스트를 숨김
                    if($studentid && $isCurrent) {
                        $statusText = '';
                    } else {
                        $statusText = $isCurrent ? '곧 답변 예정' : ($isNext ? '다음 순서' : '');
                    }
                ?>
                    <div class="waiting-item <?php echo $itemClass; ?>" data-student-id="<?php echo $item['userid']; ?>">
                        <div class="ticket-number"><?php echo str_pad($position, 3, '0', STR_PAD_LEFT); ?></div>
                        <div class="student-name">
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $item['userid']; ?>&tb=604800" 
                               target="_blank">
                                <?php echo htmlspecialchars($item['studentname']); ?>(<?php echo $item['eventtime']; ?>분)
                            </a>
                        </div>
                        <?php if(!$studentid): ?>
                            <div class="wait-time"><?php echo $item['eventtime']; ?>분</div>
                        <?php endif; ?>
                        <?php if(!$studentid && $statusText !== ''): ?>
                            <div class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                        <?php endif; ?>
                        <?php if(!$studentid): ?>
                            <?php if(isset($item['wboardid']) && $item['wboardid']): ?>
                                <!-- 새 질문 방식 (askquestion) -->
                                <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_askquestion.php?id=<?php echo $item['wboardid']; ?>&studentid=<?php echo $item['userid']; ?>" 
                                   target="_blank" 
                                   class="onair-btn">화이트보드</a>
                            <?php else: ?>
                                <!-- 기존 질문 방식 -->
                                <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid=<?php echo $item['userid']; ?>&mode=1" 
                                   target="_blank" 
                                   class="onair-btn">ONAIR</a>
                            <?php endif; ?>
                            <button type="button" 
                                    class="release-btn" 
                                    onclick="quickReply(313, <?php echo $item['userid']; ?>, <?php echo $item['goalid']; ?>)">
                                해제
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if($isNotStudent && !$studentid): ?>
            <!-- AI 답변 섹션 (student가 아니고, 슬라이더가 아닌 경우에만 표시) -->
            <div class="iframe-section">
                <div class="iframe-header">AI로 답변하기</div>
                <div class="iframe-container">
                    <iframe src="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/teachingagent.php?userid=<?php echo $teacherid; ?>" 
                            frameborder="0" 
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // SweetAlert2가 제대로 로드되었는지 확인
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2가 로드되지 않았습니다.');
        }
        
        // quickReply 함수 구현 (chainreactionOn.php와 동일)
        function quickReply(Eventid, Userid, Goalid) {
            $.ajax({ 
                url: "../students/check.php",
                type: "POST",
                data: {
                    "userid": Userid,       
                    "goalid": Goalid,
                    "eventid": Eventid
                },
                success: function(data) {
                    // 성공 시 SweetAlert2로 메시지 표시 (2초 동안)
                    Swal.fire({
                        title: "",
                        text: "해제되었습니다",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    }).then(function() {
                        location.reload();
                    });
                },
                error: function(xhr, status, error) {
                    // JSON 파싱 오류는 무시하고 성공으로 처리 (해제 기능은 정상 동작)
                    if (error === "parsererror" || error.indexOf("JSON") !== -1) {
                        Swal.fire({
                            title: "",
                            text: "해제되었습니다",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false,
                            allowOutsideClick: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        console.error("quickReply error:", error);
                        Swal.fire({
                            title: "오류",
                            text: "오류가 발생했습니다: " + error,
                            icon: "error",
                            confirmButtonText: "확인"
                        });
                    }
                }
            });
        }
        
        // 현재 학생의 순서 정보를 전역 변수로 설정 (부모 페이지에서 접근 가능하도록)
        window.currentStudentPosition = <?php echo $currentStudentPosition ? json_encode($currentStudentPosition) : 'null'; ?>;
        window.currentStudentWaitTime = <?php echo $currentStudentWaitTime ? json_encode($currentStudentWaitTime) : 'null'; ?>;
        
        // 현재 학생의 순서가 1-3번째일 때 탭 알림 표시
        <?php if($currentStudentPosition && $currentStudentPosition <= 3): ?>
            var tabNotification = document.getElementById('tabNotification');
            tabNotification.style.display = 'block';
            
            // 페이지 타이틀에도 알림 표시
            var originalTitle = document.title;
            var showDot = true;
            setInterval(function() {
                if(showDot) {
                    document.title = '🔴 ' + originalTitle;
                } else {
                    document.title = originalTitle;
                }
                showDot = !showDot;
            }, 1000);
            
            // 페이지가 보일 때만 타이틀 깜빡임
            var isPageVisible = true;
            document.addEventListener('visibilitychange', function() {
                isPageVisible = !document.hidden;
                if(!isPageVisible) {
                    document.title = originalTitle;
                }
            });
        <?php endif; ?>
        
        // 60초마다 자동 새로고침
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
