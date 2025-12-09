<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();  

$studentid= $_GET["id"]; 

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$fullname=$username->firstname.$username->lastname;
$timecreated=time();  
$nweek= $_GET["nweek"]; 
if($nweek==NULL)$nweek=15;
$timestart=$timecreated-604800*$nweek;
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY timecreated DESC ");

$result2 = json_decode(json_encode($goals), True);
unset($value);

// 날짜별로 그룹화하고 중복 제거
$grouped_by_date = array();
$displayed_dates = array();

foreach($result2 as $value) {
    $date_key = date('Y-m-d', $value['timecreated']+32400);
    
    // 같은 날짜가 이미 표시되었는지 확인
    if (!in_array($date_key, $displayed_dates)) {
        $displayed_dates[] = $date_key;
        $grouped_by_date[] = $value;
    }
}

$attlog = '';
$previous_week = null;

foreach($grouped_by_date as $value) {
    $given_date = date('m/d/Y', $value['timecreated'] + 32400);
    //$timestamp = strtotime($given_date);
    
    // 현재 주의 월요일 날짜 계산
    $current_week_monday = date('Y-m-d', strtotime('monday this week', $value['timecreated']+32400));
    
    // 새로운 주가 시작되면 구분선 추가
    if ($previous_week !== null && $previous_week !== $current_week_monday) {
        // 월요일 기준으로 월과 주차 계산
        $monday_timestamp = strtotime($current_week_monday);
        $month = date('n', $monday_timestamp); // 1-12 숫자 형식의 월
        
        // 해당 월의 첫 번째 월요일 찾기
        $first_day_of_month = date('Y-m-01', $monday_timestamp);
        $first_monday = date('Y-m-d', strtotime('first monday of ' . date('Y-m', $monday_timestamp)));
        
        // 만약 첫 번째 월요일이 다음 달이면, 이전 달의 마지막 월요일부터 시작
        if ($first_monday > date('Y-m-t', strtotime($first_day_of_month))) {
            $first_monday = date('Y-m-d', strtotime('last monday of ' . date('Y-m', strtotime('-1 month', $monday_timestamp))));
        }
        
        // 주차 계산 (해당 월의 첫 번째 월요일부터 몇 번째 주인지)
        $week_diff = floor((strtotime($current_week_monday) - strtotime($first_monday)) / (7 * 24 * 60 * 60)) + 1;
        
        $week_label = $month . "월 " . $week_diff . "주";
        $attlog .= '<tr><td colspan="6" style="border-top: 3px solid #32a852; padding: 10px 0; text-align: center; background-color: #f8f9fa; font-weight: bold;">' . $week_label . '</td></tr>';
    }
    $previous_week = $current_week_monday;
    
    $att = gmdate("20y년 m월 d일", $value['timecreated']);
    $tend = $value['timecreated'];
    $yoil = array("일","월","화","수","목","금","토");
    $day_kor = $yoil[ date('w', $value['timecreated']) ];
    
    $datestr = date('Y_m_d', $value['timecreated']); 
    $tfinish0 = date('m/d/Y', $value['timecreated']+86400); 
    $tfinish = strtotime($tfinish0);
    
    $attlog .= '<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$att.'</a> ('.$day_kor.')</td><td> </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
    <td>'.$value['type'].'&nbsp;&nbsp;&nbsp;</td><td>'.$value['text'].'</td> <td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$datestr.'&mode=today" target=_blank">습관분석</a></td></tr>';
}
 
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수강관리</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        body { font-family: Arial, sans-serif; }
        .form-group { margin-bottom: 1rem; }
        .form-control { display: block; width: calc(100% - 30px); padding: 0.375rem 0.75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: 0.25rem; }
        .tab { overflow: hidden; border: 1px solid #ccc; background-color: #f1f1f1; text-align: center; width: 82%; margin: 0 auto; }
		.tab {
        overflow: hidden;
        border: 1px solid #ccc;
        background-color: #f1f1f1;
        text-align: center; /* 탭 컨테이너를 가운데 정렬 */
    }
	 
    .tab button {
        background-color: inherit;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 14px 16px;
        transition: 0.3s;
        display: inline-block; /* 버튼들이 한 줄에 나란히 위치하도록 변경 */
    }
    .tab button:hover {
        background-color: #ddd;
    }
    .tab button.active {
        background-color: #32a852;
        color: white;
    }
        .tabcontent { display: none; padding: 6px 12px; border: 1px solid #ccc; border-top: none;width: 80%; margin: 0 auto;}
        .radio-group { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .radio-group label { display: inline-flex; align-items: center; margin-right: 10px; }
        .radio-group input[type="radio"] { margin-right: 5px; }
        .subject-group { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
        .ui-datepicker-trigger { margin-left: 5px; vertical-align: middle; cursor: pointer; }
        .date-input-container { display: flex; align-items: center; }
        .date-input-container input { flex-grow: 1; max-width: 200px; }
        /* Additional styles for the PHP-generated content */
        .row { margin: 20px 0; }
        .card { border: 1px solid #ccc; border-radius: 5px; }
        .card-header { background-color: #f7f7f7; padding: 10px; border-bottom: 1px solid #ccc; }
        .card-category h5 { margin: 0; }
        .card-content { padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 5px; border-bottom: 1px solid #ccc; }
 
  		  #enrollmentFee {
        width: 150px; /* 수강료 입력 박스의 가로폭을 축소 */
  		  }

        /* 수강 생성 이력 및 접이식 폼 스타일 */
        .enrollment-history {
            margin-bottom: 20px;
        }
        .enrollment-history table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .enrollment-history th,
        .enrollment-history td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .enrollment-history th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .create-enrollment-btn {
            background-color: #32a852;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .create-enrollment-btn:hover {
            background-color: #2a8f47;
        }
        .enrollment-form {
            display: none;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            background-color: #f9f9f9;
            margin-top: 10px;
        }
        .enrollment-form.show {
            display: block;
        }

        /* 시수 선택 드롭다운 스타일 */
        .hours-dropdown {
            width: 80px;
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            margin-left: 10px;
        }

        /* 포스트잇 스타일 메모 */
        .memo-container {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .memo-postit {
            background: linear-gradient(135deg, #ffeb3b 0%, #fff176 100%);
            border: none;
            border-radius: 0;
            padding: 15px;
            width: 200px;
            height: 120px;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            font-size: 14px;
            resize: none;
            box-shadow: 
                0 4px 8px rgba(0,0,0,0.1),
                inset 0 1px 0 rgba(255,255,255,0.5);
            transform: rotate(-2deg);
            transition: transform 0.2s ease;
            position: relative;
        }
        .memo-postit:hover {
            transform: rotate(0deg);
        }
        .memo-postit:focus {
            outline: none;
            transform: rotate(0deg);
            box-shadow: 
                0 6px 12px rgba(0,0,0,0.15),
                inset 0 1px 0 rgba(255,255,255,0.5);
        }
        .memo-postit::before {
            content: '';
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 15px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
        }

        /* 폼 레이아웃 개선 */
        .form-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1rem;
        }
        .form-row .form-group {
            margin-bottom: 0;
            flex: 1;
        }
        .form-row .memo-section {
            flex: 0 0 220px;
        }
    </style>
</head>
<body>
    <?php echo '<h1>수강관리 &nbsp;&nbsp; <a style="text-decoration:none; font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=12">'.$fullname.'</a></h1>'; ?>

    <div class="tab">
		<button class="tablinks" onclick="openTab(event, 'Attlog')" id="defaultOpen">출결현황</button>
		<button class="tablinks" onclick="openTab(event, 'Attendance')">출결 입력</button>
        <button class="tablinks" onclick="openTab(event, 'CourseManagement')">수강 관리</button>
    </div>
	<div id="Attlog" class="tabcontent">
	<table><?php echo $attlog; ?></table>
	</div>

    <div id="CourseManagement" class="tabcontent">
        <h2>수강료 변경</h2>
        <div class="memo-container">
            <div style="flex: 1;">
                <div class="form-row">
                    <div class="form-group">
                        <label>적용날짜:</label>
                        <div class="date-input-container">
                            <input type="text" class="form-control datepicker" id="datepicker2" name="datepicker2" placeholder="날짜 선택" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>시수:</label>
                        <div class="radio-group">
                            <label><input type="radio" name="feeChangeHours" value="1"> 1</label>
                            <label><input type="radio" name="feeChangeHours" value="2"> 2</label>
                            <label><input type="radio" name="feeChangeHours" value="3"> 3</label>
                            <label><input type="radio" name="feeChangeHours" value="4"> 4</label>
                            <label><input type="radio" name="feeChangeHours" value="5"> 5</label>
                            <label><input type="radio" name="feeChangeHours" value="6"> 6</label>
                            <label><input type="radio" name="feeChangeHours" value="7"> 7</label>
                            <label><input type="radio" name="feeChangeHours" value="8"> 8</label>
                            <label><input type="radio" name="feeChangeHours" value="9"> 9</label>
                            <label><input type="radio" name="feeChangeHours" value="10"> 10</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>과정:</label>
                    <div class="subject-group">
                        <label><input type="checkbox" name="feeChangeCourse" value="초등과정"> 초등과정</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="초등심화"> 초등심화</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="중1,2"> 중1,2</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="중등심화"> 중등심화</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="중3~고2과정"> 중3~고2과정</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="고3과정"> 고3과정</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="중등과학"> 중등과학</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="고등과학"> 고등과학</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="중등영어"> 중등영어</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="고등영어"> 고등영어</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="중등언어"> 중등언어</label>
                        <label><input type="checkbox" name="feeChangeCourse" value="고등언어"> 고등언어</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>수강료:</label>
                    <input type="text" class="form-control" id="feeChangeAmount" name="feeChangeAmount" placeholder="수강료" readonly style="background-color: #f8f9fa;">
                </div>
                <button onclick="saveFeeChange()">저장</button>
            </div>
            <div class="memo-section">
                <label>메모:</label>
                <textarea class="memo-postit" id="feeChangeMemo" name="feeChangeMemo" placeholder="메모를 입력하세요..."></textarea>
            </div>
        </div>
        
        <hr style="margin: 30px 0; border: 1px solid #ddd;">
        
        <h2>수강 생성 이력</h2>
        <div class="enrollment-history">
            <?php
            // 수강 생성 이력 조회
            $enrollments = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND type='enrol' AND hide=0 ORDER BY timecreated DESC LIMIT 10");
            
            if ($enrollments) {
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>생성일</th>';
                echo '<th>수강과목</th>';
                echo '<th>수강료</th>';
                echo '<th>수강기간</th>';
                echo '<th>정산일</th>';
                echo '<th>상태</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($enrollments as $enrollment) {
                    $createDate = date('Y-m-d', $enrollment->timecreated);
                    $startDate = date('Y-m-d', $enrollment->doriginal);
                    $endDate = date('Y-m-d', $enrollment->doriginal + (4 * 7 * 24 * 60 * 60)); // 4주 후
                    $settlementDate = date('Y-m-d', $enrollment->dchanged);
                    $status = $enrollment->complete == 1 ? '납부완료' : '미납';
                    $statusColor = $enrollment->complete == 1 ? 'color: green;' : 'color: red;';
                    
                    echo '<tr>';
                    echo '<td>' . $createDate . '</td>';
                    echo '<td>' . htmlspecialchars($enrollment->subject) . '</td>';
                    echo '<td>' . $enrollment->fee . '만원</td>';
                    echo '<td>' . $startDate . ' ~ ' . $endDate . '</td>';
                    echo '<td>' . $settlementDate . '</td>';
                    echo '<td style="' . $statusColor . '">' . $status . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>수강 생성 이력이 없습니다.</p>';
            }
            ?>
        </div>
        
        <button class="create-enrollment-btn" onclick="toggleEnrollmentForm()">+ 새 수강 생성</button>
        
        <div id="enrollmentForm" class="enrollment-form">
            <h3>수강 생성</h3>
            <div class="memo-container">
                <div style="flex: 1;">
                    <div class="form-group">
                        <label>수강과목:</label>
                        <div class="subject-group">
                            <label><input type="checkbox" name="enrollmentSubject" value="초등과정"> 초등과정</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="초등심화"> 초등심화</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="중1,2"> 중1,2</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="중등심화"> 중등심화</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="중3~고2과정"> 중3~고2과정</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="고3과정"> 고3과정</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="중등과학"> 중등과학</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="고등과학"> 고등과학</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="중등영어"> 중등영어</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="고등영어"> 고등영어</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="중등언어"> 중등언어</label>
                            <label><input type="checkbox" name="enrollmentSubject" value="고등언어"> 고등언어</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>수강기간:</label>
                            <div class="date-input-container">
                                <input type="text" class="form-control datepicker" id="datepicker5" name="datepicker5" placeholder="수강 시작일" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>시수:</label>
                            <select class="hours-dropdown" id="enrollmentHoursSelect" name="enrollmentHoursSelect" onchange="calculateTotalFee()">
                                <option value="1">1</option>
                                <option value="2" selected>2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>수강료:</label>
                        <input type="text" class="form-control" id="enrollmentFee" name="enrollmentFee" placeholder="수강료" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>정산 기준일:</label>
                        <div class="date-input-container">
                            <input type="text" class="form-control datepicker" id="datepicker4" name="datepicker4" placeholder="정산 기준일" readonly>
                        </div>
                    </div>
                    <button onclick="saveEnrollment()">저장</button>
                    <button type="button" onclick="toggleEnrollmentForm()" style="margin-left: 10px; background-color: #ccc; color: #333;">취소</button>
                </div>
                <div class="memo-section">
                    <label>메모:</label>
                    <textarea class="memo-postit" id="enrollmentMemo" name="enrollmentMemo" placeholder="메모를 입력하세요..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <div id="Attendance" class="tabcontent">
        <iframe src="https://mathking.kr/moodle/local/augmented_teacher/students/attendancerecords.php?userid=<?php echo $studentid; ?>" 
                width="100%" 
                height="1200" 
                frameborder="0" 
                style="border: none;">
        </iframe>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
// 단위수강료 정의 (숫자로 표기)
var unitFees = {
    "초등과정": 120000,
    "초등심화": 140000,
    "중1,2": 140000,
    "중등심화": 160000,
    "중3~고2과정": 160000,
    "고3과정": 180000,
    "중등과학": 120000,
    "고등과학": 140000,
    "중등영어": 140000,
    "고등영어": 160000,
    "중등언어": 140000,
    "고등언어": 160000
};

function calculateTotalFee() {
    var totalFee = 0;
    var hours = parseInt($("#enrollmentHoursSelect").val()) || 2;
    
    $("input[name='enrollmentSubject']:checked").each(function() {
        var subject = $(this).val();
        if (unitFees[subject]) {
            totalFee += unitFees[subject];
        }
    });
    
    totalFee = totalFee * hours;
    $("#enrollmentFee").val(totalFee.toLocaleString() + '원');
}

function calculateFeeChangeAmount() {
    var totalFee = 0;
    var selectedHours = parseInt($('input[name="feeChangeHours"]:checked').val()) || 0;
    
    // 선택된 과정들의 단위 수강료 합계 계산
    $("input[name='feeChangeCourse']:checked").each(function() {
        var subject = $(this).val();
        if (unitFees[subject]) {
            totalFee += unitFees[subject];
        }
    });
    
    // 총 수강료 = 과정별 단위 수강료 합계 × 시수
    totalFee = totalFee * selectedHours;
    
    $("#feeChangeAmount").val(totalFee.toLocaleString() + '원');
}

$(function() {
    var today = new Date();
    var todayString = today.getFullYear() + '-' + 
                     String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                     String(today.getDate()).padStart(2, '0');
    
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
        yearRange: "c-10:c+10",
        showOn: "both",  // "both"로 설정하여 입력박스 클릭 시에도 달력이 열리도록 함
        buttonText: "달력",
        buttonImage: "https://jqueryui.com/resources/demos/datepicker/images/calendar.gif",
        buttonImageOnly: true
    }).val(todayString);

    $("#datepicker5").change(function() {
        var startDate = new Date($(this).val());
        var settlementDate = new Date(startDate.getTime() + (4 * 7 * 24 * 60 * 60 * 1000));
        $("#datepicker4").datepicker("setDate", settlementDate);
    });

    // 수강과목 체크박스 변경 시 수강료 자동 계산
    $("input[name='enrollmentSubject']").change(function() {
        calculateTotalFee();
    });

    // 수강료 변경 관련 이벤트
    $("input[name='feeChangeHours']").change(function() {
        calculateFeeChangeAmount();
    });
    
    $("input[name='feeChangeCourse']").change(function() {
        calculateFeeChangeAmount();
    });

    document.getElementById("defaultOpen").click();
});

        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        function saveFeeChange() {
            var date = $('#datepicker2').val();
            var hours = $('input[name="feeChangeHours"]:checked').val();
            var courses = [];
            $("input[name='feeChangeCourse']:checked").each(function() {
                courses.push($(this).val());
            });
            var memo = $('#feeChangeMemo').val();
            
            // 입력값 검증
            if (!date) {
                alert("적용날짜를 선택해주세요.");
                return;
            }
            if (!hours) {
                alert("시수를 선택해주세요.");
                return;
            }
            if (courses.length === 0) {
                alert("과정을 선택해주세요.");
                return;
            }
            
            var subject = courses.join(', ');
            var type = "수강료변경";
            
            $.ajax({
                url: "database.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": 95, // 수강료 변경용 새로운 eventid
                    "userid": <?php echo $studentid; ?>,
                    "type": type,
                    "subject": subject,
                    "inputtext": memo,
                    "begintime": date,
                    "selecttime": date
                },
                success: function(data) {
                    alert("수강료 변경 정보가 저장되었습니다.");
                    setTimeout(function() { location.reload(); }, 1000);
                },
                error: function() {
                    alert("저장 중 오류가 발생했습니다.");
                }
            });
        }

        function saveEnrollment() {
            var subjects = [];
            $("input[name='enrollmentSubject']:checked").each(function() {
                subjects.push($(this).val());
            });
            var startDate = $("#datepicker5").val();
            var fee = $("#enrollmentFee").val().replace(/[^0-9]/g, ''); // 숫자만 추출
            var memo = $("#enrollmentMemo").val();
            var settlementDate = $("#datepicker4").val();
            
            // 입력값 검증
            if (subjects.length === 0) {
                alert("수강과목을 선택해주세요.");
                return;
            }
            if (!startDate) {
                alert("수강 시작일을 선택해주세요.");
                return;
            }
            if (!fee) {
                alert("수강료가 계산되지 않았습니다.");
                return;
            }
            if (!settlementDate) {
                alert("정산 기준일을 선택해주세요.");
                return;
            }
            
            // 각 과목별로 개별 저장
            var subjectString = subjects.join(', ');
            
            $.ajax({
                url: "database.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": 91, // 기존 수납정보 저장용 eventid
                    "userid": <?php echo $studentid; ?>,
                    "type": "enrol",
                    "subject": subjectString,
                    "fee": Math.round(fee / 10000), // 만원 단위로 변환
                    "inputtext": memo,
                    "begintime": startDate,
                    "selecttime": settlementDate
                },
                success: function(data) {
                    alert("수강 정보가 저장되었습니다.");
                    setTimeout(function() { location.reload(); }, 1000);
                },
                error: function() {
                    alert("저장 중 오류가 발생했습니다.");
                }
            });
        }

        function toggleEnrollmentForm() {
            var form = document.getElementById('enrollmentForm');
            if (form.classList.contains('show')) {
                form.classList.remove('show');
                // 폼 초기화
                $("input[name='enrollmentSubject']").prop('checked', false);
                $("#datepicker5").val('');
                $("#enrollmentFee").val('');
                $("#enrollmentMemo").val('');
                $("#datepicker4").val('');
                $("#enrollmentHoursSelect").val('2');
            } else {
                form.classList.add('show');
                // 폼이 열릴 때 기본 수강료 계산
                calculateTotalFee();
            }
        }

    </script>
</body>
</html>
