<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid = $_GET["studentid"]; 
$type = $_GET["type"]; 
$nowhiteboard='nowboardid';
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

// 자세 피드백을 통하여 사용법을 교정
$setcolor1='lightgrey';$setcolor2='lightgrey';$setcolor3='lightgrey';$setcolor4='lightgrey';$setcolor5='lightgrey';$setcolor6='lightgrey';$setcolor7='lightgrey';$setcolor8='lightgrey';
if($type==='목표')
	{
	$ntype=1;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>목표 집착도</b> : 분기, 주간, 오늘목표에 대한 성찰과 성공의지의 크기. 목표를 학습동력으로 활용하는 정도.';$typeurl='https://www.youtube.com/embed/CfPxlb8-ZQ0';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>목표를 입력할 때 성공을 위한 결심을 함께 한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>학습의 중간과정에서 목표를 성공하기 위해 의식적인 노력을 기울인다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>목표설정 과정에서 실행 시 발생할 어려운 점들을 미리 고민하고 준비한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>오늘 목표를 실패하더라도 주간목표를 성공하기 위한 계획 재정비를 한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>주간목표를 성공하지 못하더라도 분기목표를 성공하기 위해 계획을 재정비한다.</td></tr></table>';
	$setcolor1='black';
	}
elseif($type==='순서')
	{
	$ntype=2;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>순서 최적화</b> : 상황에 맞게 활동순서를 유연하게 조절하는 능력. 순서조절을 통하여 학습흐름을 촉진.';$typeurl='https://www.youtube.com/embed/DPJL488cfRw';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>테스트를 응시하기 전 필요한 개념이 준비가 되어 있는지 확인을 하고 시작한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>테스트를 응시하기 전 필요한 경우 기존 테스트에 대한 부분 재시도 등을 통하여 상태를 점검한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>새로운 테스트를 시작하기 전 오답노트를 꼼꼼하게 마무리하는 편이다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>이전 과정에 대한 복습이 필요하다고 판단되는 경우 계획에 없었던 복습을 진행하기도 한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>테스트를 마무리 한 다음 오답문항들을 전체적으로 검토하면서 취약 개념이나 단원에 대해 생각하고 분석한 다음 후속학습을 재설정한다.</td></tr></table>';
	$setcolor2='black';
	}
elseif($type==='기억')
	{
	$ntype=3;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>기억 영구화</b> : 복습예약을 활용하여 자신에게 맞는 복습패턴을 찾고 장기기억 루틴을 확립하였지를 평가.';$typeurl='https://www.youtube.com/embed/DPJL488cfRw';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>복습예약을 사용하지 않는다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>복습예약을 하기 때문에 오답활동을 적당히 하는 경향이 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>복습예약을 하는 문제도 그렇지 않은 문제와 같은 정도로 체화한다. </td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>복습실행 시 기억상태를 점검하고 새로운 복습예약 시 활동을 개선하는데 활용한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>복습실행 시 좀 전에 푼 문제와 같이 능숙하게 푸는 것을 목표로 복습예약 시 준비하는 과정을 지속적으로 개선하고 있다..</td></tr></table>';
	$setcolor3='black';
	}
elseif($type==='개념')
	{
	$ntype=4;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>개념 구조화</b> : 자신만의 논리체계를 구성해 나가려는 노력의 크기. 개념집착 노트 활용도.';$typeurl='https://www.youtube.com/embed/DPJL488cfRw';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>도움이 된다고 느끼지만 습관화에 어려움이 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>마무리 활동 시 규칙적으로 사용하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>개념집착노트를 탭에 열어두고 수시로 업데이트 한다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>개념집착노트를 활용하여 영역별 개념들에 대한 정리활동을 하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>주제단위의 성찰활동을 위하여 개념집착노트를 활용한다.</td></tr></table>';
	$setcolor4='black';
	}
elseif($type==='발상')
	{
	$ntype=5;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>발상 자동화</b> : 동작기억 활용도 및 숙달기준 마무리 습관 안정화 정도와 이를 통한 자동발상 공부법 활용수준.';$typeurl='https://www.youtube.com/embed/DPJL488cfRw';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>동작기억의 원리를 이해하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>동작기억을 스스로 조절하여 발상하는 방법을 활용하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>문제가 눈으로 전달되는 상태와 두뇌 속으로 완전히 옮긴 이후의 상태의 차이를 식별할 수 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>숙련기준 학습을 통한 자동발상 공부법을 이해하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>자동발상 공부법이 점진적으로 습관화되는 과정에 있다고 느낀다.</td></tr></table>';
	$setcolor5='black';
	}
elseif($type==='해석')
	{
	$ntype=6;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>해석 루틴화</b> : 개념이나 문항 해석 중 질문 지점 포착, 구체화 등의 과정을 통한 해석의지.';$typeurl='https://www.youtube.com/embed/DPJL488cfRw';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>해석상태와 풀이상태를 효과적으로 스위칭할 수 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>지문 소거법을 통한 문제풀이를 활용할 수 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>구체화되지 않은 부분을 식별하고 기억소환을 위한 몰입이 가능하다..</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>사용하지 않은 부분에 대하여 선택과 집중을 통한 순환적 문제해석이 가능하다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>내가 할 수 있는 해석의 끝에 도달한 것을 스스로 확인할 수 있다.</td></tr></table>';
	$setcolor6='black';
	}

elseif($type==='숙달')
	{
	$ntype=7;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>숙달 습관화</b> : 기억저장 대상 선택과 활동 방식의 성숙도. 장기기억화를 위한 루틴의 확립정도';$typeurl='https://www.youtube.com/embed/DPJL488cfRw';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>개념공부 시 기억저장 활동을 지속적으로 사용하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>오답노트 시 기억저장 활동을 지속적으로 사용하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>매주 정해진 루틴으로 개념복습을 위한 기억저장활동을 생활화하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>기억저장 활동 시 단순반복이 아니라 차분히 의미를 되새기며 반복저장을 하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>기억저장 활동시 복습이 필요없다는 확신이 든다.</td></tr></table>';
	$setcolor7='black';
	}
elseif($type==='효율')
	{
	$ntype=8;$typetext='<b style="font-size:20px;"><input type="checkbox" id="vehicle1" name="vehicle1" value="Bike" Checked>효율 극대화</b> : 풀이효율 개선을 위한 지속적인 노력과 의지의 크기.';$typeurl='https://www.youtube.com/embed/YCoL_--Ncl4';
	$rubric='<table align=center><tr><td><input style=margin-top:8px; type=checkbox></td><td>문제에 대한 해석이나 풀이에 중점을 두어 속도에 대해 고민할 여유가 부족하다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>평소에 문제를 빠르게 푸는 것에 관심이 있는 편이다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>표시된 고민지점을 집중적으로 연습하여 속도향상에 힘쓰고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>필기양을 최소화하면서도 정확하고 효율적인 풀이를 위해 노력하고 있다.</td></tr><tr><td><input style=margin-top:8px; type=checkbox></td><td>평상시에 풀이가 이해되어도 더 쉬운 풀이를 찾기 위해 노력하고 있다.</td></tr></table>';
	$setcolor8='black';
	}
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE type LIKE '$type' AND creator LIKE '$studentid'  AND hide=0  ORDER BY timemodified ASC LIMIT 10  ");  
$talklist= json_decode(json_encode($share), True);

unset($value);  
foreach($talklist as $value)
	{
	$sharetext=$value['text'];
	$talkcreator=$value['userid'];
	$wboardid=$value['wboardid'];
	$crname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$talkcreator' ");	
	$creatorname=$crname->firstname.$crname->lastname;
	$tcreated1=date("m월d일 h:i A", $value['timecreated']);   

	$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$talkcreator' AND fieldid='22' "); 
	$role2=$userrole2->role;
	if($role2==='student')$bubblestr='bubble';
	else $bubblestr='bubble2';

	$getauthor=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1 "); 
	$contentsid=$getauthor->contentsid;
	$seewb='talk';
 	if($wboardid==='nowboardid')$seewb='talk';
	else $seewb='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'&mode=peer"target="_blank">자세히</a>';
	$sharelist.='<tr><td width=3% style="white-space: nowrap; text-overflow: ellipsis;" valign=top> <span style="color:#3399ff;">'.$creatorname.'</span></td> <td width=1%></td>
	<td style="overflow:auto;" valign=center><div class="'.$bubblestr.'"> &nbsp;&nbsp;&nbsp;'.$sharetext.' </div></td> <td width=10%  valign=top>'.date("m/d", $value['timecreated']).'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top>'.$seewb.'</td></tr>';
	}

$history=$DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog where userid='$studentid' ORDER BY id DESC LIMIT 1"); 
$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;
$typestr='flow'.$ntype;
$flowrate=$$typestr;
if($role!=='student')$teacherScore='<button class="btn btn-success"  type="button"  style = "font-size:16;background-color:lightblue;color:black;border:0;height:40px;outline:0;"  onclick="EvaluateFlow()">평가</button>';
echo '<br><table width=100%><tr><td width=2%></td><td><a  style="text-decoration: none; font-size:20px;color:black; white-space: nowrap; text-overflow: ellipsis;"  href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a></td><td width=50% style="white-space: nowrap; text-overflow: ellipsis;"  >'.$typetext.' </td><td width=40%><table width=100% align=right><tr><td width=1%></td><td width=60%><input style="font-size:20px;width:100%;" type="text" id="squareInput" name="squareInput"  placeholder="내용을 입력해 주세요"></td><td width=2%></td><td aglin=right><button style="font-size:20px;"  onClick="Comment(\''.$studentid.'\',\''.$USER->id.'\',\''.$nowhiteboard.'\',$(\'#squareInput\').val(),\''.$type.'\')">입력하기</button></td><td>(+'.$flowrate.')'.$teacherScore.'</td></tr></table></td></tr></table><hr>
<table width=100% height=100% align=left><tr><td width=2%></td>
<td width=48% valign=top>
<table align=center><tr><td><div class="chart-container" style="width: 500px; height:600px;horizontal-align: center;"><canvas  id="radarChart"></canvas></div></td></tr></table>
<br>

</td>
<td width=2%></td><td valign=top style="overflow-y:hidden;"><table align=center><tr><td>CJN <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'">메타인지</a> | <a style="color:'.$setcolor1.';"  href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=목표">목</a> | <a style="color:'.$setcolor2.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=순서">순</a> | <a style="color:'.$setcolor3.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=기억">기</a> | <a style="color:'.$setcolor4.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=개념">개</a>
 | <a style="color:'.$setcolor5.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=발상">발</a> | <a style="color:'.$setcolor6.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=해석">해</a> | <a style="color:'.$setcolor7.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=숙달">숙</a> | <a style="color:'.$setcolor8.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=효율">효</a>  </td></tr></table><hr><table>'.$sharelist.'</table></td><td width=2%></td></tr></table> ';
 //<iframe style="width:45vw;height:45vh;" src="'.$typeurl.'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> 
echo '
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, -->
	<script src="../assets/js/setting-demo.js"></script>

<script>	
function Comment(Wbcreator,Userid,Wboardid,Text,Type)
	{ 
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'40\',
		"wboardid":Wboardid,
		"wbcreator":Wbcreator,	
		"inputtext":Text,	
		"type":Type,
		"userid":Userid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
 
function Reply(Userid,Wboardid,Sid,Text)
	{ 
	alert(Sid);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'40\',
		"wboardid":Wboardid,	
		"inputtext":Text,	
		"userid":Userid,
		"talkid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
function Edittext(Itemid,Inputtext)
	{
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'42\',
		"wboardid":Wboardid,	
		"inputtext":Text,	
		"userid":Userid,
		"talkid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
function hide(Eventid,Fbid, Checkvalue){
		var checkimsi = 0;
   		if(Checkvalue==true){
        		checkimsi = 1;
    		}
 		swal("체크시 학생에게 보이지 않습니다.", {buttons: false,timer: 500});
  		 $.ajax({
       		 url: "check.php",
        		type: "POST",
        		dataType: "json",
        		data : { 
		"eventid":\'43\',
            		"fbid":Fbid,
            	 	"checkimsi":checkimsi,
            	 	  },
 	  	 success: function (data){  
		var Teacherid=data.teacherid
		setTimeout(function() {location.reload(); },100);	
  	   	   }
		  });
		}
</script> 



<style>
.bubble
{
position: relative;
width: 390px;
height: auto;
min-height:35px;
padding: 5px;
background: #B8FFFF;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: transparent #B8FFFF;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 12px;
}

.bubble2
{
position: relative;
width: 390px;
height: auto;
min-height:35px;
padding: 5px;
background: #99ccff;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble2:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: white;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 12px;
}

a:link {
  color : red;
}
a:visited {
  color :grey;

}
a:hover {
  color : blue;
}
a:active {
  color : purple;
}

.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 800px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip1:hover .tooltiptext1 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 500px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 

.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip3 .tooltiptext3 {
    
  visibility: hidden;
  width:700px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 700px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
}
a.tooltips span:after {
  position: absolute;
  bottom: 100%;
  right: 1%;
  margin-left: -10px;
  width: 0;
  height: 0;
  border-bottom: 8px solid #23ad5f;
  border-right: 8px solid #0a5cf5;
  border-left: 8px solid #0a5cf5;
}
a:hover.tooltips span {
  visibility: visible;
  opacity: 1;
  top: 0px;
  right: 0%;
  margin-left: 10px;
  z-index: 999;
  border-bottom: 1px solid #15ff00;
  border-right: 1px solid #15ff00; 
  border-left: 1px solid #15ff00;
}

 

</style>';
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
 
 ';
?>



<script>

var radarChart = document.getElementById('radarChart').getContext('2d');
var Flow1= "<?php echo $flow1;?>";
var Flow2= "<?php echo $flow2;?>";
var Flow3= "<?php echo $flow3;?>";
var Flow4= "<?php echo $flow4;?>";
var Flow5= "<?php echo $flow5;?>";
var Flow6= "<?php echo $flow6;?>";
var Flow7= "<?php echo $flow7;?>";
var Flow8= "<?php echo $flow8;?>";

var Type= "<?php echo $type;?>";
var Rubric="<?php echo $rubric;?>";

var myRadarChart = new Chart(radarChart, {
			type: 'radar',
			data: {
				labels: ['목표', '순서', '기억', '개념', '해석','발상','숙달','효율'],
				datasets: [{
					data: [Flow1, Flow2, Flow3, Flow4, Flow5, Flow6, Flow7, Flow8],
					borderColor: '#1d7af3',
					backgroundColor : 'rgba(29, 122, 243, 0.25)',
					pointBackgroundColor: "#1d7af3",
					pointHoverRadius: 4,
					pointRadius: 5,
					label: '몰입지표'
				},  
				
				]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: 'bottom'
				}
			}
		});


function EvaluateFlow() 
{
  let wrap = document.createElement('div');
  wrap.setAttribute('class', 'text-muted');
  wrap.innerHTML = '다음의 평가기준을 참고하여 플로우 평가를 선택해 주세요 (갯수선택)<hr>'+ Rubric +'<hr><button onclick="reply(\'level1\',\'1\')" type="button" value="level1" class="btn feel">+1<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009610001.png" width=30 height=30></button><button onclick="reply(\'level2\',\'2\')" type="button" value="level2" class="btn feel">+2 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009642001.png" width=30 height=30></button><button onclick="reply(\'level3\',\'3\')" type="button" value="level3" class="btn feel">+3 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009715001.png" width=30 height=30></button><button onclick="reply(\'level4\',\'4\')" type="button" value="level4" class="btn feel">+4 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009756001.png" width=30 height=30></button><button onclick="reply(\'level5\',\'5\')" type="button" value="level5" class="btn feel">+5 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009790001.png" width=30 height=30></button><hr>' ;
swal({
    title: "플로우 평가 ("+Type+")",
    closeOnClickOutside: false,
    content: {
      element: wrap
    },
    buttons: {
      confirm: {
        text:"취소",
        visible: true,
        className: "btn btn-default",
        closeModal: true,
      }
    },
  }).then((value) => {
    if (value === 'level1') {
      swal("+1이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level2') {
      swal("+2이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level3') {
      swal("+3이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level4') {
      swal("+4이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level5') {
      swal("+5이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
    }
  });
}

function reply(feel,resultValue){
	var Userid= "<?php echo $studentid;?>";
	var Tutorid= "<?php echo $USER->id;?>";
	var Type= "<?php echo $type;?>";
	var Eventid="104";
swal.setActionValue(feel);
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":Eventid,
	"userid":Userid,
	"tutorid":Tutorid,
	"type":Type,
 	"value":resultValue,
	},
	success:function(data){
	 }
	 })
swal("플로우 평가결과가 업데이트 되었습니다.", {buttons: false, timer: 2000, });
setTimeout(function() {location.reload(); },1000);	
}

</script>


