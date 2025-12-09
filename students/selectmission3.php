<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 필요한 변수 정의
$mtid = required_param('mtid', PARAM_INT);
$subject = required_param('cid', PARAM_INT);
$studentid = required_param('id', PARAM_INT);
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid'");
include("navbar.php");

$timecreated = time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid, page, timecreated) VALUES('$studentid', 'studentselectmission', '$timecreated')");
$selectdate = date("Y-m-d", time());

// 미션 타입에 따른 설정
switch ($mtid) {
    case 1:
    case 7:
        $missiontype = '개념';
        $unit = '단원';
        break;
    case 2:
        $missiontype = '심화';
        $unit = '단원';
        break;
    default:
        $missiontype = '미션';
        $unit = '단계별 테스트';
        break;
}

// 과목 리스트 가져오기
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_curriculum WHERE mtid = '$mtid' ORDER BY norder ASC");
$subjects = [
    'elementary' => [],
    'middle' => [],
    'high' => []
];

foreach ($missionlist as $value) {
    $subject_name = $value->name;
    if (strpos($subject_name, '초등') !== false) {
        $subjects['elementary'][] = '<option value="'.$value->id.'">'.$subject_name.'</option>';
    } elseif (strpos($subject_name, '중등') !== false) {
        $subjects['middle'][] = '<option value="'.$value->id.'">'.$subject_name.'</option>';
    } else {
        $subjects['high'][] = '<option value="'.$value->id.'">'.$subject_name.'</option>';
    }
}

if ($mtid == 1 || $mtid == 7) { // 개념 미션
    if ($subject == 0) {
        echo '
        <hr>
        <div class="text-center text-danger font-weight-bold" style="font-size:1.5em;">'.$missiontype.' 미션 선택하기</div>
        <hr>
        <form>
            <div class="row">
                <div class="col-md-3">
                    <label for="elementary_select">초등수학</label>
                    <select id="elementary_select" name="elementary_select" class="form-control">
                        <option value="">과목 선택</option>
                        '.implode('', $subjects['elementary']).'
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="middle_select">중등수학</label>
                    <select id="middle_select" name="middle_select" class="form-control">
                        <option value="">과목 선택</option>
                        '.implode('', $subjects['middle']).'
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="high_select">고등수학</label>
                    <select id="high_select" name="high_select" class="form-control">
                        <option value="">과목 선택</option>
                        '.implode('', $subjects['high']).'
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-block" onclick="inputmission(11, '.$studentid.', '.$mtid.', getSelectedSubject(), $(\'#basic4\').val(), $(\'#basic3\').val(), $(\'#basic2\').val(), \'0\', $(\'#datepicker\').val())">저장하기</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <label for="basic2">시작단원</label>
                    <select id="basic2" name="basic2" class="form-control">
                        <option value="1" selected="selected">1단원</option>
                        <option value="2">2단원</option>
                        <option value="3">3단원</option>
                        <option value="4">4단원</option>
                        <option value="5">5단원</option>
                        <option value="6">6단원</option>
                        <option value="7">7단원</option>
                        <option value="8">8단원</option>
                        <option value="9">9단원</option>
                        <option value="10">10단원</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="basic3">소요시간</label>
                    <select id="basic3" name="basic3" class="form-control">
                        <option value="3">3시간</option>
                        <option value="4">4시간</option>
                        <option value="5">5시간</option>
                        <option value="6">6시간</option>
                        <option value="7">7시간</option>
                        <option value="8">8시간</option>
                        <option value="9">9시간</option>
                        <option value="10" selected="selected">10시간</option>
                        <option value="11">11시간</option>
                        <option value="12">12시간</option>
                        <option value="13">13시간</option>
                        <option value="14">14시간</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="basic4">통과점수</label>
                    <select id="basic4" name="basic4" class="form-control">
                        <option value="70">70점</option>
                        <option value="75">75점</option>
                        <option value="80">80점</option>
                        <option value="85">85점</option>
                        <option value="90" selected="selected">90점</option>
                        <option value="95">95점</option>
                        <option value="100">100점</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="datepicker">시작일</label>
                    <input type="text" class="form-control" id="datepicker" name="datepicker" placeholder="'.$selectdate.'" value="'.$selectdate.'">
                </div>
            </div>
        </form>
        <hr>
        <script>
            function getSelectedSubject() {
                let subject = $(\'#elementary_select\').val() || $(\'#middle_select\').val() || $(\'#high_select\').val();
                return subject;
            }

            // DateTimePicker 초기화
            $("#datepicker").datetimepicker({
                format: "YYYY/MM/DD",
                locale: "ko"
            });
    
            // Select2 초기화
            $("#elementary_select, #middle_select, #high_select, #basic2, #basic3, #basic4").select2({
                theme: "bootstrap"
            });

            // 입력 요소 높이 맞추기
            $(document).ready(function() {
                let maxHeight = Math.max(
                    $("#basic2").outerHeight(),
                    $("#basic3").outerHeight(),
                    $("#basic4").outerHeight(),
                    $("#datepicker").outerHeight()
                );
                $("#datepicker").css("height", maxHeight);
            });
        </script>
        ';
    } else
	{
	$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND userid='$studentid' AND subject='$subject' ");
	$name=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE  id='$subject'  LIMIT 1");
	$name=$name->name;
	$chstart=$mission->chstart;
	$hour=$mission->hours;
	$weekhour=$mission->weekhours;
	$grade=$mission->grade;
	$startdate=$mission->startdate;
	$idcreated=$mission->id;
	echo ' 
	<table class="table">
	<tr><th scope="col" style="width: 20%;" align="center">  '.$name.' </th> 
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="'.$chstart.'">'.$chstart.'단원</option><option value="1" selected="selected">1단원</option><option value="2">2단원</option><option value="3">3단원</option><option value="4">4단원</option><option value="5">5단원</option><option value="6">6단원</option><option value="7">7단원</option><option value="8">8단원</option><option value="9">9단원</option><option value="10">10단원</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="'.$hour.'">'.$hour.'시간</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> <option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="'.$grade.'">'.$grade.'점</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="'.$weekhour.'">'.$weekhour.'시간</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></th>
	<th scope="col" style="width: 20%;"><input type="text" class="form-control" id="datepicker" value="'.$startdate.'" ></th>

	<th scope="col" style="width: 5%;"><button type="button" onclick="inputmission2(14,'.$studentid.','.$mtid.','.$idcreated.',$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></th>
	</tr>
	<tr><td></td><td>시작 단원</td><td>시간/단원</td><td>통과 점수</td><td>시간/주</td><td>시작날짜</td><td></td></tr>
	</table>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
	}
}
// 나머지 코드 (생략하지 말고 필요한 부분 유지)
include("quicksidebar.php");
echo '
    <!-- 필수 JS 파일들 -->
    <script src="../assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery UI -->
    <script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Moment JS -->
    <script src="../assets/js/plugin/moment/moment.min.js"></script>
    <script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>

    <!-- Select2 -->
    <script src="../assets/js/plugin/select2/select2.full.min.js"></script>

    <!-- DateTimePicker -->
    <script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

    <!-- Sweet Alert -->
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Ready Pro JS -->
    <script src="../assets/js/ready.min.js"></script>

    <!-- Custom JS -->
    <script>
        function inputmission(Eventid, Userid, Mtype, Subject, Grade, Chhours, Chstart, Weekhours, Startdate) {
            if (!Subject) {
                swal("과목을 선택해주세요.", { buttons: false, timer: 2000 });
                return;
            }
            $.ajax({
                url: "database.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": Eventid,
                    "userid": Userid,
                    "msntype": Mtype,
                    "subject": Subject,
                    "grade": Grade,
                    "hours": Chhours,
                    "chstart": Chstart,
                    "weekhours": Weekhours,
                    "startdate": Startdate,
                },
                success: function(data) {
                    swal("적용되었습니다. 내 공부방으로 이동 중입니다.", { buttons: false, timer: 2000 }).then(() => {
                        window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id=" + Userid;
                    });
                }
            });
        }

        // DateTimePicker 초기화
        $("#datepicker").datetimepicker({
            format: "YYYY/MM/DD",
            locale: "ko"
        });

        // Select2 초기화
        $("#elementary_select, #middle_select, #high_select, #basic2, #basic3, #basic4").select2({
            theme: "bootstrap"
        });

        // 입력 요소 높이 맞추기
        $(document).ready(function() {
            let maxHeight = Math.max(
                $("#basic2").outerHeight(),
                $("#basic3").outerHeight(),
                $("#basic4").outerHeight(),
                $("#datepicker").outerHeight()
            );
            $("#datepicker").css("height", maxHeight);
        });
    </script>
';
?>
