<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();  

$studentid= $_GET["userid"]; 

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$fullname=$username->firstname.$username->lastname;
$timecreated=time(); 
 
$nweek= $_GET["nweek"]; 
if($nweek==NULL)$nweek=15;
$timestart=$timecreated-604800*$nweek;
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY id DESC ");

$result2 = json_decode(json_encode($goals), True);
unset($value);
 
foreach($result2 as $value)
	{
	$date_pre=$date;
	$att=gmdate("20y년 m월 d일 (H 시)", $value['timecreated']+32400);
	$date=gmdate("d", $value['timecreated']+32400);
	 
 
	$given_date=date('m/d/Y', $value['timecreated']+32400);   //2022_10_13
	$timestamp = strtotime($given_date);			 
		 
	$tend=$value['timecreated'];
	$yoil = array("일","월","화","수","목","금","토");

	$datestr=date('Y_m_d', $value['timecreated']); 
	$tfinish0=date('m/d/Y', $value['timecreated']+86400); 
 	$tfinish=strtotime($tfinish0);	

  	$day_kor=$yoil[date('w', strtotime($given_date))];

 
	// echo '<tr><td> </td><td> </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td> </td></tr>';
	$attlog.='<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$att.'</a> ('.$day_kor.')</td><td> </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
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
        .tab { overflow: hidden; border: 1px solid #ccc; background-color: #f1f1f1; }
        .tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; }
        .tab button:hover { background-color: #ddd; }
        .tab button.active { background-color: #32a852; color: white; }
        .tabcontent { display: none; padding: 6px 12px; border: 1px solid #ccc; border-top: none; }
        .radio-group { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .radio-group label { display: inline-flex; align-items: center; margin-right: 10px; }
        .radio-group input[type="radio"] { margin-right: 5px; }
        .subject-group { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
        .ui-datepicker-trigger { margin-left: 5px; vertical-align: middle; cursor: pointer; }
        .date-input-container { display: flex; align-items: center; }
        .date-input-container input { flex-grow: 1; }
        /* Additional styles for the PHP-generated content */
        .row { margin: 20px 0; }
        .card { border: 1px solid #ccc; border-radius: 5px; }
        .card-header { background-color: #f7f7f7; padding: 10px; border-bottom: 1px solid #ccc; }
        .card-category h5 { margin: 0; }
        .card-content { padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 5px; border-bottom: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>수강관리</h1>

    <div class="tab">
		<button class="tablinks" onclick="openTab(event, 'Attlog')" id="defaultOpen">출결현황</button>
        <button class="tablinks" onclick="openTab(event, 'FeeChange')">수강료 변경</button>
        <button class="tablinks" onclick="openTab(event, 'Attendance')">출결 입력</button>
        <button class="tablinks" onclick="openTab(event, 'Enrollment')">수강 생성</button>
    </div>
	<div id="Attlog" class="tabcontent">
	<table><?php echo $attlog; ?></table>
	</div>

    <div id="FeeChange" class="tabcontent">
     

        <h2>수강료 변경</h2>
        <div class="form-group">
            <label>변경 유형:</label>
            <div class="radio-group">
                <label><input type="radio" name="feeChangeType" value="시수변경"> 시수변경</label>
                <label><input type="radio" name="feeChangeType" value="과정변경"> 과정변경</label>
            </div>
        </div>
        <div class="form-group">
            <label>적용날짜:</label>
            <div class="date-input-container">
                <input type="text" class="form-control datepicker" id="datepicker2" name="datepicker2" placeholder="날짜 선택" readonly>
            </div>
        </div>
        <div class="form-group">
            <label>시수:</label>
            <div class="radio-group">
                <label><input type="radio" name="feeChangeHours" value="3"> 3</label>
                <label><input type="radio" name="feeChangeHours" value="6"> 6</label>
                <label><input type="radio" name="feeChangeHours" value="8"> 8</label>
                <label><input type="radio" name="feeChangeHours" value="9"> 9</label>
                <label><input type="radio" name="feeChangeHours" value="12"> 12</label>
                <label><input type="radio" name="feeChangeHours" value="15"> 15</label>
                <label><input type="radio" name="feeChangeHours" value="16"> 16</label>
                <label><input type="radio" name="feeChangeHours" value="20"> 20</label>
                <label><input type="radio" name="feeChangeHours" value="24"> 24</label>
                <label><input type="radio" name="feeChangeHours" value="28"> 28</label>
                <label><input type="radio" name="feeChangeHours" value="32"> 32</label>
            </div>
        </div>
        <div class="form-group">
            <label>메모:</label>
            <input type="text" class="form-control" id="feeChangeMemo" name="feeChangeMemo" placeholder="메모">
        </div>
        <button onclick="saveFeeChange()">저장</button>
        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id=<?php echo $studentid; ?>&eid=1&nweek=4">출결관리</a>
    </div>

    <div id="Attendance" class="tabcontent">
        <h2>출결 입력</h2>
        <div class="form-group">
            <label>유형:</label>
            <div class="radio-group">
                <label><input type="radio" name="attendanceType" value="시간이동"> 시간이동</label>
                <label><input type="radio" name="attendanceType" value="날짜이동"> 날짜이동</label>
                <label><input type="radio" name="attendanceType" value="최종휴강"> 휴강</label>
            </div>
        </div>
        <div class="form-group">
            <label>사유:</label>
            <div class="radio-group">
                <label><input type="radio" name="attendanceReason" value="개인일정"> 개인일정</label>
                <label><input type="radio" name="attendanceReason" value="관심필요"> 관심필요</label>
                <label><input type="radio" name="attendanceReason" value="학교일정"> 학교일정</label>
                <label><input type="radio" name="attendanceReason" value="다른과목"> 다른과목</label>
                <label><input type="radio" name="attendanceReason" value="미확인"> 미확인</label>
            </div>
        </div>
        <div class="form-group">
            <label>시작 날짜:</label>
            <div class="date-input-container">
                <input type="text" class="form-control datepicker" id="datepicker3" name="datepicker3" placeholder="날짜 선택" readonly>
            </div>
        </div>
        <div class="form-group">
            <label>메모:</label>
            <input type="text" class="form-control" id="attendanceMemo" name="attendanceMemo" placeholder="메모">
        </div>
        <button onclick="saveAttendance()">저장</button>
        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id=<?php echo $studentid; ?>&eid=1&nweek=4&mode=new">정보입력</a>
    </div>

    <div id="Enrollment" class="tabcontent">
        <h2>수강 생성</h2>
        <div class="form-group">
            <label>수강과목:</label>
            <div class="subject-group">
                <label><input type="radio" name="enrollmentSubject" value="초등수학"> 초등수학</label>
                <label><input type="radio" name="enrollmentSubject" value="중등수학"> 중등수학</label>
                <label><input type="radio" name="enrollmentSubject" value="중등수학3"> 중등수학3</label>
                <label><input type="radio" name="enrollmentSubject" value="고등수학"> 고등수학</label>
                <label><input type="radio" name="enrollmentSubject" value="고3수학"> 고3수학</label>
                <label><input type="radio" name="enrollmentSubject" value="수능수학"> 수능수학</label>
                <label><input type="radio" name="enrollmentSubject" value="중등과학"> 중등과학</label>
                <label><input type="radio" name="enrollmentSubject" value="고등과학"> 고등과학</label>
                <label><input type="radio" name="enrollmentSubject" value="중등영어"> 중등영어</label>
                <label><input type="radio" name="enrollmentSubject" value="고등영어"> 고등영어</label>
                <label><input type="radio" name="enrollmentSubject" value="중등언어"> 중등언어</label>
                <label><input type="radio" name="enrollmentSubject" value="고등언어"> 고등언어</label>
            </div>
        </div>
        <div class="form-group">
            <label>수강기간:</label>
            <div class="date-input-container">
                <input type="text" class="form-control datepicker" id="datepicker5" name="datepicker5" placeholder="수강 시작일" readonly>
            </div>
        </div>
        <div class="form-group">
            <label>수강료:</label>
            <input type="text" class="form-control" id="enrollmentFee" name="enrollmentFee" placeholder="수강료">
        </div>
        <div class="form-group">
            <label>메모:</label>
            <input type="text" class="form-control" id="enrollmentMemo" name="enrollmentMemo" placeholder="메모">
        </div>
        <div class="form-group">
            <label>정산 기준일:</label>
            <div class="date-input-container">
                <input type="text" class="form-control datepicker" id="datepicker4" name="datepicker4" placeholder="정산 기준일" readonly>
            </div>
        </div>
        <button onclick="saveEnrollment()">저장</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            $(".datepicker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-10:c+10',
                showOn: "button",
                buttonText: "달력",
                buttonImage: "https://jqueryui.com/resources/demos/datepicker/images/calendar.gif",
                buttonImageOnly: true
            });

            $("#datepicker5").change(function() {
                var startDate = new Date($(this).val());
                var settlementDate = new Date(startDate.getTime() + (4 * 7 * 24 * 60 * 60 * 1000));
                $("#datepicker4").datepicker("setDate", settlementDate);
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
            var type = $('input[name="feeChangeType"]:checked').val();
            var date = $('#datepicker2').val();
            var hours = $('input[name="feeChangeHours"]:checked').val();
            var memo = $('#feeChangeMemo').val();
            console.log("Fee Change saved:", {type, date, hours, memo});
            alert("수강료 변경 정보가 저장되었습니다.");
        }

        function saveAttendance() {
            var type = $('input[name="attendanceType"]:checked').val();
            var reason = $('input[name="attendanceReason"]:checked').val();
            var date = $('#datepicker3').val();
            var memo = $('#attendanceMemo').val();
            console.log("Attendance saved:", {type, reason, date, memo});
            alert("출결 정보가 저장되었습니다.");
        }

        function saveEnrollment() {
            var subject = $('input[name="enrollmentSubject"]:checked').val();
            var startDate = $('#datepicker5').val();
            var fee = $('#enrollmentFee').val();
            var memo = $('#enrollmentMemo').val();
            var settlementDate = $('#datepicker4').val();
            console.log("Enrollment saved:", {subject, startDate, fee, memo, settlementDate});
            alert("수강 정보가 저장되었습니다.");
        }
    </script>
</body>
</html>
