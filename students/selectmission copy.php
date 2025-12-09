<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
// use studentid input
$mtid=required_param('mtid', PARAM_INT);
$subject=required_param('cid', PARAM_INT);
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");
 
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentselectmission','$timecreated')");
$selectdate=date("Y:m:d",time());
if($mtid==1)
{
$missiontype='개념';
$unit='단원';
}
if($mtid==7)
{
$missiontype='개념';
$unit='단원';
}
if($mtid==2)
{
$missiontype='심화';
$unit='단원';
}
if($mtid==3)
{
$missiontype='내신';
$unit='단계별 테스트';
}
if($mtid==4)
{
$missiontype='모의';
$unit='단계별 테스트';
}
if($mtid==5)
{
$missiontype='특목';
$unit='단계별 테스트';
}
if($mtid==6)
{
$missiontype='인증';
$unit='단계별 테스트';
}

$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_curriculum WHERE mtid LIKE '$mtid' ORDER BY norder ASC"); // missiontype으로 mission 종류 선택
$result = json_decode(json_encode($missionlist), True);
unset($value);
foreach($result as $value)
	{
	$subjects.='<option value="'.$value['id'].'">'.$value['name'].'</option>';   // input curriculum id !! use this for accessing curriculum info.
	} 

if($mtid==1) // 개념
{
if($subject==0)
	{
	echo ' <hr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" > 개념미션 선택하기 </div><hr>
	<table align="center"><tr><th width="40%">  </th><th width="20%"></th><th width="40%"></th></tr>
	<tr><td align="right"> 현재 공부하려는 과목은  </td><td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">선택하기</option>'.$subjects.'</select></div></td><td>입니다. </td></tr>
	<tr><td align="right">시작 단원을</td><td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="1" selected="selected">1단원</option><option value="2">2단원</option><option value="3">3단원</option><option value="4">4단원</option><option value="5">5단원</option><option value="6">6단원</option><option value="7">7단원</option><option value="8">8단원</option><option value="9">9단원</option><option value="10">10단원</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right"> 한 단원을 마스터하기 위해서 대략</td><td><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> <option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option></select></div> </td><td>정도 필요할 것으로 예상합니다.</td></tr>
	<tr><td align="right">현재 활동을 위하여 일주일에 총 </td><td><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option>
	 <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div> </td><td>을 사용할 계획입니다.</td></tr>
	<tr><td align="right">단원별 활동을 마무리하기 위한 통과점수를 </td><td><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right">첫 활동시작 일을</td><td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></td><td>로 정하겠습니다.</td></tr>
	<tr><td>&nbsp;</td><td></td><td></td></tr>
	<tr><td> </td><td align="center"><button type="button" onclick="inputmission(11,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">저장하기<img src="http://mathking.kr/Contents/Moodle/save.gif" width=30></a></button> </td><td></td></tr>
	</table><hr>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
	 }else
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

	<th scope="col" style="width: 5%;"><button type="button" id="alert_updatemission" onclick="inputmission2(14,'.$studentid.','.$mtid.','.$idcreated.',$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></th>
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
if($mtid==7) // 개념 NEW
{
if($subject==0)
	{
	echo ' <hr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" > 개념미션 선택하기 </div><hr>
	<table align="center"><tr><th width="40%">  </th><th width="20%"></th><th width="40%"></th></tr>
	<tr><td align="right"> 현재 공부하려는 과목은  </td><td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">선택하기</option>'.$subjects.'</select></div></td><td>입니다. </td></tr>
	<tr><td align="right">시작 단원을</td><td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="1" selected="selected">1단원</option><option value="2">2단원</option><option value="3">3단원</option><option value="4">4단원</option><option value="5">5단원</option><option value="6">6단원</option><option value="7">7단원</option><option value="8">8단원</option><option value="9">9단원</option><option value="10">10단원</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right"> 한 단원을 마스터하기 위해서 대략</td><td><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> <option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option></select></div> </td><td>정도 필요할 것으로 예상합니다.</td></tr>
	<tr><td align="right">현재 활동을 위하여 일주일에 총 </td><td><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option>
	 <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div> </td><td>을 사용할 계획입니다.</td></tr>
	<tr><td align="right">단원별 활동을 마무리하기 위한 통과점수를 </td><td><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right">첫 활동시작 일을</td><td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></td><td>로 정하겠습니다.</td></tr>
	<tr><td>&nbsp;</td><td></td><td></td></tr>
	<tr><td> </td><td align="center"><button type="button" onclick="inputmission(11,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">저장하기<img src="http://mathking.kr/Contents/Moodle/save.gif" width=30></a></button> </td><td></td></tr>
	</table><hr>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
	 }else
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
if($mtid==2) //심화 ... 개념과 or || 연산자로 통합해도 무방.. 단원 기준이므로.. 내신 등 나머지는 최적화 필요..
{
if($subject==0)
	{

	echo ' <hr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" > 심화미션 선택하기 </div><hr>
	<table align="center"><tr><th width="40%">  </th><th width="20%"></th><th width="40%"></th></tr>
	<tr><td align="right"> 현재 공부하려는 과목은  </td><td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">선택하기</option>'.$subjects.'</select></div></td><td>입니다. </td></tr>
	<tr><td align="right">시작 단원을</td><td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="1" selected="selected">1단원</option><option value="2">2단원</option><option value="3">3단원</option><option value="4">4단원</option><option value="5">5단원</option><option value="6">6단원</option><option value="7">7단원</option><option value="8">8단원</option><option value="9">9단원</option><option value="10">10단원</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right"> 한 단원을 마스터하기 위해서 대략</td><td><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> <option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option></select></div> </td><td>정도 필요할 것으로 예상합니다.</td></tr>
	<tr><td align="right">현재 활동을 위하여 일주일에 총 </td><td><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option>
	 <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div> </td><td>을 사용할 계획입니다.</td></tr>
	<tr><td align="right">단원별 활동을 마무리하기 위한 통과점수를 </td><td><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right">첫 활동시작 일을</td><td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></td><td>로 정하겠습니다.</td></tr>
	<tr><td>&nbsp;</td><td></td><td></td></tr>
	<tr><td> </td><td align="center"><button type="button" onclick="inputmission(11,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">저장하기<img src="http://mathking.kr/Contents/Moodle/save.gif" width=30></a></button> </td><td></td></tr>
	</table><hr>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
 
	 }else
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
if($mtid==3) //내신 : 시험기간, D day counter , 단계별 내신테스트, 과목별 체크리스트  
{
if($subject==0)
	{
	echo ' <hr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" >내신미션 선택하기 </div><hr>

	<table align="center"><tr><th width="40%">  </th><th width="20%"></th><th width="40%"></th></tr>
	<tr><td align="right">준비하려는 시험과목은 </td><td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">선택하기</option>'.$subjects.'</select></div></td><td>입니다.</td></tr>
	<tr><td align="right">이번 시험은 </td><td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></td><td>에 시행되는 것으로 알고 있습니다.</td></tr>
	<tr><td align="right">현재 과목에 대한 시험을 준비하기 위하여 일주일에 총</td><td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option>
	 <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></td><td>을 사용할 예정입니다.</td></tr>
	<tr><td align="right">이번 시험에서 목표로 하는 점수는</td><td><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></td><td>입니다.</td></tr>
	<tr><td align="right"></td><td align="center"><button type="button" onclick="inputmission3(15,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">저장하기 <img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></td><td></td></tr>
	</table><hr>                                                                                                     

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
	 }else
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
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value="'.$grade.'">'.$grade.'점</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="'.$weekhour.'">'.$weekhour.'시간</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></th>
	<th scope="col" style="width: 20%;"><input type="text" class="form-control" id="datepicker" value="'.$startdate.'" ></th>

	<th scope="col" style="width: 5%;"><button type="button" onclick="inputmission4(16,'.$studentid.','.$mtid.','.$idcreated.',$(\'#basic2\').val(),$(\'#basic1\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></th>
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
if($mtid==4) // 수능
{
if($subject==0)
	{
	echo ' <hr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" > 수능미션 선택하기 </div><hr>
	<table align="center"><tr><th width="40%">  </th><th width="20%"></th><th width="40%"></th></tr>
	<tr><td align="right"> 현재 공부하려는 과목은  </td><td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">선택하기</option>'.$subjects.'</select></div></td><td>입니다. </td></tr>
	<tr><td align="right">현재 활동을 위하여 일주일에 총 </td><td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option>
	 <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div> </td><td>을 사용할 계획입니다.</td></tr>
	<tr><td align="right">활동을 마무리하기 위한 통과점수를 </td><td><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div> </td><td>으로 설정합니다.</td></tr>
	<tr><td align="right">첫 활동시작 일을</td><td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></td><td>로 정하겠습니다.</td></tr>
	<tr><td>&nbsp;</td><td></td><td></td></tr>
	<tr><td> </td><td align="center"><button type="button" onclick="inputmissione(11,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">저장하기<img src="http://mathking.kr/Contents/Moodle/save.gif" width=30></a></button> </td><td></td></tr>
	</table><hr>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
 	 }else
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
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value="'.$grade.'">'.$grade.'점</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="'.$weekhour.'">'.$weekhour.'시간</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></th>
	<th scope="col" style="width: 20%;"><input type="text" class="form-control" id="datepicker" value="'.$startdate.'" ></th>

	<th scope="col" style="width: 5%;"><button type="button" onclick="inputmissione2(14,'.$studentid.','.$mtid.','.$idcreated.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></th>
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
if($mtid==5) // 특목
{
if($subject==0)
	{
	echo ' 
	<table class="table"><thead><tr>
	<th scope="col" style="width: 20%;"><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">과목선택</option>'.$subjects.'</select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="">시작단원</option><option value="1" selected="selected">1단원</option><option value="2">2단원</option><option value="3">3단원</option><option value="4">4단원</option><option value="5">5단원</option><option value="6">6단원</option><option value="7">7단원</option><option value="8">8단원</option><option value="9">9단원</option><option value="10">10단원</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="">시간/단원</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> <option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="0">시간/주</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="">합격점수</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></th>
	<th scope="col" style="width: 20%;"><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></th>

	<th scope="col" style="width: 5%;"><button type="button" onclick="inputmission(11,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></th>
	</tr></thead><tr><div class="card-footer "></tr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" >&nbsp;&nbsp;&nbsp; '.$missiontype.'미션을 선택해 주세요 <hr></div></table>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
	 }else
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
if($mtid==6)  // 인증시험
{
if($subject==0)
	{
	echo ' 
	<table class="table"><thead><tr>
	<th scope="col" style="width: 20%;"><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value=" ">과목선택</option>'.$subjects.'</select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="">시작단원</option><option value="1" selected="selected">1단원</option><option value="2">2단원</option><option value="3">3단원</option><option value="4">4단원</option><option value="5">5단원</option><option value="6">6단원</option><option value="7">7단원</option><option value="8">8단원</option><option value="9">9단원</option><option value="10">10단원</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="">시간/단원</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> <option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option></select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="0">시간/주</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10" selected="selected">10시간</option> <option value="11">11시간</option> 
	<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></th>
	<th scope="col" style="width: 10%;"><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="">합격점수</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90" selected="selected">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></th>
	<th scope="col" style="width: 20%;"><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$selectdate.'" value="'.$selectdate.'"></th>

	<th scope="col" style="width: 5%;"><button type="button" onclick="inputmission(11,'.$studentid.','.$mtid.',$(\'#basic1\').val(),$(\'#basic4\').val(),$(\'#basic3\').val(),$(\'#basic2\').val(),$(\'#basic5\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://www.iconarchive.com/download/i103415/paomedia/small-n-flat/floppy.ico" width=30></a></button></th>
	</tr></thead><tr><div class="card-footer "></tr><div style="font: bold 1.5em/1.0em 맑은고딕체;text-align: center ;color:red;" >&nbsp;&nbsp;&nbsp; '.$missiontype.'미션을 선택해 주세요 <hr></div></table>

	<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><div class="card-head-row"><div class="card-title">단원별 공부시간</div>
	<div class="card-tools"><a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2"><span class="btn-label"><i class="la la-pencil"></i></span>Export</a><a href="#" class="btn btn-info btn-border btn-round btn-sm">
	<span class="btn-label"><i class="la la-print"></i></span>Print</a></div></div></div><div class="card-body"><div class="chart-container"><canvas id="statisticsChart"></canvas></div><div id="myChartLegend"></div>
	</div></div></div><div class="col-md-5"><div class="card"><div class="card-header"><h4 class="card-title">합격점수</h4><p class="card-category">Users percentage this month</p>
	</div><div class="card-body"><div class="chart-container"><canvas id="usersChart"></canvas>
	</div></div></div></div></div></div></div></div>';
	 }else
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
include("quicksidebar.php");
echo '
	<!--   Core JS Files   -->
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
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, dont include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>


<!--  END   -->

  	<!--   Core JS Files   -->
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

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<!-- eventid,userid,mtid,과목,점수,시간,메모,마감일-->	
	<script>
		function inputmission(Eventid,Userid,Mtype,Subject,Grade,Chhours,Chstart,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "subject":Subject,
		       	  "grade":Grade,
			  "hours":Chhours,
			  "chstart":Chstart,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        }) 
		swal("적용되었습니다. 내 공부방으로 이동 중입니다.", {buttons: false,timer: 5000});
		}
		function inputmissione(Eventid,Userid,Mtype,Subject,Weekhours,Grade,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "subject":Subject,
		       	  "grade":Grade,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        }) 
		swal("적용되었습니다. 내 공부방으로 이동 중입니다.", {buttons: false,timer: 5000});
		}
		function inputmission2(Eventid,Userid,Mtype,Idcreated,Grade,Chhours,Chstart,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "idcreated":Idcreated,
		       	  "grade":Grade,
			  "hours":Chhours,
			  "chstart":Chstart,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })
		swal("적용되었습니다. 내 공부방으로 이동 중입니다.", {buttons: false,timer: 5000});
		}
		function inputmissione2(Eventid,Userid,Mtype,Idcreated,Grade,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "idcreated":Idcreated,
		       	  "grade":Grade,
			  "hours":Chhours,
			  "chstart":Chstart,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })
		swal("적용되었습니다. 내 공부방으로 이동 중입니다.", {buttons: false,timer: 5000});
		}   
 		function inputmission3(Eventid,Userid,Mtype,Subject,Weekhours,Grade,Startdate){  
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "subject":Subject,
		       	  "grade":Grade,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })
		swal("적용되었습니다. 내 공부방으로 이동 중입니다.", {buttons: false,timer: 5000}); 			
		}
		function inputmission4(Eventid,Userid,Mtype,Idcreated,Weekhours,Grade,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "idcreated":Idcreated,
		       	  "grade":Grade,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })
		swal("적용되었습니다. 내 공부방으로 이동 중입니다.", {buttons: false,timer: 5000});
		}
		function ChangeCheckBox(Eventid,Userid, Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
		                "missionid":Questionid,
		                "attemptid":Missionid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		}
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});
 
		$("#basic").select2({
			theme: "bootstrap"
		});
		$("#basic1").select2({
			theme: "bootstrap"
		});
		$("#basic2").select2({
			theme: "bootstrap"
		});
		$("#basic3").select2({
			theme: "bootstrap"
		});
		$("#basic4").select2({
			theme: "bootstrap"
		});
		$("#basic5").select2({
			theme: "bootstrap"
		});
		$("#basic6").select2({
			theme: "bootstrap"
		});
		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});
		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );

	</script>
';

?>