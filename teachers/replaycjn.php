<?PHP
header("Content-Type:text/html");
include_once("./dbcon.php");
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$id = $_GET["id"];
$userid=$USER->id; 
$srcid=$_GET["srcid"];
$studentid=$_GET["studentid"];
$contentstype= $_GET["contentstype"];
$contentsid= $_GET["contentsid"]; //이부분은 사용하면 안됨.
$timecreated=time(); 

if($srcid==NULL && strpos($id, 'cjnNotepageid')!==false)
	{
	$srcid=str_replace('cjnNotepageid','pageid',$id);   
	$srcid=str_replace('jnrsorksqcrark','jnrsorksqcrark_user'.$studentid,$srcid);   
	}
 
if(($contentsid==NULL || $contentsid==0) && strpos($id, 'cjnNotepageid')!==false)
	{
	$contentsid=substr($id, 0, strpos($id, 'jnrsorksqcrark')); // 문자 이후 삭제
	$contentsid=str_replace("jnrsorksqcrark","",$contentsid);  
	$contentsid=str_replace("cjnNotepageid","",$contentsid);  
	}
 
$editid=$id;
$expid = $_GET["expid"]; // 원본 복사용
$expgid= $_GET["expgid"]; // 원본 복사용
$nboost= $_GET["Nboost"]; // 원본 복사용 
if(strpos($id, 'explainid')!==false)$editid=$id.'&expid='.$expid.'&expgid='.$expgid;
//require_login();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$speed = !empty($_GET["speed"])?$_GET["speed"] : 3;
$playindex = !empty($_GET["playindex"])?$_GET["playindex"] : 0;
$playstate = !empty($_GET["playstate"])?$_GET["playstate"] : 0;
$sketchstate =$_GET["sketchstate"];
if($sketchstate==NULL)$sketchstate=1;
//$sketchstate = !empty($_GET["sketchstate"])?$_GET["sketchstate"] : 1;
$bookmarkgid=$_GET["bmkgid"]; 
$addexpgid=$_GET["addexpgid"]; 
  
include("style_replay.php");   

// 3. 사용법 4. 커리큘럼 5. 공부법 6. 메타인지
if(strpos($id, 'jnrsorksqcrark')!==false)$cnttype='topic';
elseif(strpos($id, 'Interpret')!==false)$cnttype='interpret';
elseif(strpos($id, 'type2')!==false)$cnttype='question';
else $cnttype='general';
$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid='$srcid' ORDER BY id DESC LIMIT 1    ");
//if($mode==='addexp' && $thisboard->onair!=1)$DB->execute("UPDATE {abessi_messages} SET onair=1, present=1,status='present',tlaststroke='$timecreated' WHERE wboardid='$srcid' ORDER BY id DESC LIMIT 1 ");

$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_orchestration where wboardid='$id' ORDER BY id DESC LIMIT 1    ");
if($thisboard->id==NULL)
	{
	echo '<div style="display:none;"><iframe  src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$id.'&srcid='.$srcid.'" ></iframe></div>';
	$DB->execute("INSERT INTO {abessi_orchestration} (userid,wboardid,type,status,timemodified,timecreated) VALUES('$USER->id','$id','$cnttype','new','$timecreated','$timecreated')");
	header("refresh:0");
	} 
$title=$thisboard->title;
if($thisboard->contentstype==3)echo '<button type="text"  style ="z-index:3;  position:fixed;  top: 0%;left:0%;background-color:#03fc20;color:black;width:100%;height:40px;font-size:20;"  data-toggle="modal" data-target="#message"><b>사용법 - '.$title.'</b></button>';
elseif($thisboard->contentstype==4)echo '<button type="text"  style ="z-index:3;  position:fixed;  top: 0%;left:0%;background-color:#03fc20;color:black;width:100%;height:40px;font-size:20;"  data-toggle="modal" data-target="#message"><b>커리큘럼 - '.$title.'</b></button>';
elseif($thisboard->contentstype==5)echo '<button type="text"  style ="z-index:3;  position:fixed;  top: 0%;left:0%;background-color:#03fc20;color:black;width:100%;height:40px;font-size:20;"  data-toggle="modal" data-target="#message"><b>공부법 - '.$title.'</b></button>';
elseif($thisboard->contentstype==6)echo '<button type="text"  style ="z-index:3;  position:fixed;  top: 0%;left:0%;background-color:#03fc20;color:black;width:100%;height:40px;font-size:20;"  data-toggle="modal" data-target="#message"><b>성찰하기 - '.$title.'</b></button>';
 
if($bookmarkgid!=NULL)$sql = "SELECT * FROM boarddb_trans where encryption_id='$id' AND (generate_id<='1' OR  generate_id='$bookmarkgid' ) ORDER BY generate_id ASC";  // bessiOVc4lRh
elseif($addexpgid!=NULL)$sql = "SELECT * FROM boarddb_trans where encryption_id='$id' AND  generate_id<='$addexpgid' ORDER BY generate_id ASC";  // bessiOVc4lRh
else $sql = "SELECT * FROM boarddb_trans where encryption_id='$id'  ORDER BY generate_id ASC";
 
if($nboost!=NULL)
	{
	$checkbooster=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecomlog WHERE userid='$studentid' AND bessiboardid ='$id' AND norder='$nboost' ORDER BY id DESC LIMIT 1 "); 
	if($checkbooster->id==NULL && $role==='student')
		{
		$getlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecom WHERE bessiboardid ='$id' AND norder='$nboost' ORDER BY id DESC LIMIT 1 "); 
		$recomid=$getlog->id;
		$DB->execute("INSERT INTO {abessi_autorecomlog} (userid,recomid,status,memory,nconnected,tspent,nstrokes,nrestore,ndelay,timemodified,timecreated) VALUES('$USER->id','$recomid','begin','100','0','0','0','1','0','$timecreated','$timecreated')");	
		}
	elseif(time()-$checkbooster->timecreated>43200  && $role==='student') 
		{
		$getlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecom WHERE bessiboardid ='$id' AND norder='$nboost' ORDER BY id DESC LIMIT 1 "); 
		$nrestore=$checkbooster->nrestore+1;
		$DB->execute("UPDATE {abessi_autorecomlog} SET userid='$USER->id',nrestore='$nrestore', timemodified='$timecreated' WHERE recomid='$checkbooster->id' ORDER BY id DESC LIMIT 1 ");	
		//$DB->execute("INSERT INTO {abessi_autorecomlog} (userid,recomid,status,memory,nconnected,tspent,nstrokes,nrestore,ndelay,timecreated) VALUES('$USER->id','$getlog->id','begin','100','0','0','0','1','0','$timecreated')");	
		}
	}

$rs = mysqli_query($conn, $sql);
$point=array();
$len=array();
$memos = array();
$replayRect=[];
$nmemo=1;
while($info = mysqli_fetch_array($rs)){
	$infomemo=$info['memo'];
	array_push($point,$info['shape_data']);
	array_push($memos, $infomemo);
	if($info['origin_rect']){
		$replayRect[$info['generate_id']] = $info['origin_rect'];
	}
	array_push($len,$info['generate_id']);

	if($infomemo!=NULL && $nboost==NULL)
		{
		$checkexist=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecom WHERE bessiboardid ='$id' AND norder='$nmemo' ORDER BY id DESC LIMIT 1 ");
		if($checkexist->id==NULL)$DB->execute("INSERT INTO {abessi_autorecom} (userid,bessiboardid,norder,type,title,domain,chapter,weight,frequency,timecreated) VALUES('$USER->id','$id','$nmemo','type','$infomemo','domain','chapter','3','0','$timecreated')");
		else $DB->execute("UPDATE {abessi_autorecom} SET userid='$USER->id', norder='$nmemo', title='$infomemo', timemodified='$timecreated' WHERE bessiboardid ='$id' AND norder='$nmemo' ORDER BY id DESC LIMIT 1 ");			

		$checkexist2=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecomlog WHERE bessiboardid ='$id' AND norder='$nmemo' ORDER BY id DESC LIMIT 1 "); 
		if($checkexist2->id==NULL && $role==='student')
			{
			$getlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecom WHERE bessiboardid ='$id' AND norder='$nmemo' ORDER BY id DESC LIMIT 1 "); 
			$recomid=$getlog->id;
			//if($recomid==NULL)$recomid=0;
			$DB->execute("INSERT INTO {abessi_autorecomlog} (userid,recomid,status,memory,nconnected,tspent,nstrokes,nrestore,ndelay,timecreated) VALUES('$USER->id','$recomid','begin','100','0','0','0','1','0','$timecreated')");	
			}
		elseif(time()-$checkexist2->timecreated>43200  && $role==='student') 
			{
			$getlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_autorecom WHERE bessiboardid ='$id' AND norder='$nmemo' ORDER BY id DESC LIMIT 1 "); 
			$nrestore=$checkexist2->nrestore+1;
			$DB->execute("INSERT INTO {abessi_autorecomlog} (userid,recomid,status,memory,nconnected,tspent,nstrokes,nrestore,ndelay,timecreated) VALUES('$USER->id','$getlog->id','begin','100','0','0','0','1','0','$timecreated')");	
			}

		$nmemo++;
		}
	if(strpos($id, '_user')!== true)
		{
		if($infomemo==NULL && strpos($info['shape_data'], 'type 2')!== false && strpos($info['shape_data'], 'bcolor rgba(0,0,0,0)')!== false)
			{
			$thisgid=$info['generate_id'];
			$inputtext='#연습';
		 
			$sql_memo = "UPDATE boarddb_trans SET memo='{$inputtext}', shape_data=REPLACE(shape_data, 'null', 'https://mathking.kr/moodle/local/augmented_teacher/bessiboard/uploads/comment.png') WHERE generate_id='{$thisgid}' AND encryption_id='{$id}'";
			if($conn->query($sql_memo) === TRUE) { }
			else {echo "Error: " . $sql_memo . "<br>" . $conn->error;}
		 	}

		}
	elseif(strpos($id, '_user')!== false)
		{
		if($infomemo==NULL && strpos($info['shape_data'], 'type 2')!== false &&  strpos($info['shape_data'], 'bcolor rgba(0,0,0,0)')!== false)
			{
			$thisgid=$info['generate_id'];
			$inputtext='▶';
		 
			$sql_memo = "UPDATE boarddb_trans SET memo='{$inputtext}', shape_data=REPLACE(shape_data, 'null', 'https://mathking.kr/moodle/local/augmented_teacher/bessiboard/uploads/comment.png') WHERE generate_id='{$thisgid}' AND encryption_id='{$id}'";
			if($conn->query($sql_memo) === TRUE) { }
			else {echo "Error: " . $sql_memo . "<br>" . $conn->error;}
		 	}
		}
}
$replayRect = str_replace( "\"","", $replayRect);
$replayArr = array();
foreach ($replayRect as $k => $v) {
	$replayArr[$k] = explode("],[",substr($v,2,-2));
}
$resultData = str_replace( "\"","", $point );
$index_max=max($len);
/*
// 폴더명 지정
$dir = getcwd()."/recoder/".$id;
if(!is_dir($dir)){ 
    umask(0);
    if(!mkdir($dir, 0777, true)){
        die("can't create dir".error_get_last()); 
    }
}
*/
?>
<html lang="ko">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" href="./reset.css"/>
	<link rel="stylesheet" href="./style.css"/>
	<title>타임스톤 ◈</title>
	<script language="JavaScript">
	var speedArr = new Array();
 
	speedArr[5] = 0.1;   // 빠름
	speedArr[4] = 2;
	speedArr[3] = 3;
	speedArr[2] = 4;
	speedArr[1] = 5;   // 느림
	var recoderLog = "";
	//
	var speed = speedArr[<?php echo $speed?>];
	//
	var rec;
	var editid = "<?php echo $editid; ?>";
	var srcid = "<?php echo $srcid; ?>";
	function boardlink(){
		location.href = './board.php?id='+editid+'&srcid='+srcid;
	}
	var index = <?= json_encode($len)?>;
	</script>
	<script type="module" src="replayindex.js"></script>
	<script src="https://code.jquery.com/pep/0.4.3/pep.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 


	<script type="text/javascript" src="html2canvas.js"></script>
	<!--https://stackoverflow.com/questions/36530123/html-with-tex-formulas-to-images-->
	<script language="JavaScript">
	var id = "<?php echo $id;?>"; 
	
	//
	var playindex = Number("<?php echo $playindex;?>")*0.01;
	var playstate = "<?php echo $playstate;?>";
	var sketchstate = "<?php echo $sketchstate;?>";
	//
	var MEMOS = <?= json_encode($memos) ?>;
	var dbdata = <?= json_encode($resultData) ?>;
	var replayArr = <?= json_encode($replayArr) ?>;
	var index_len = Number(<?= json_encode($index_max)?>);
	var points = new Array();
	for(var i =0; i<dbdata.length; ++i){
		points.push(dbdata[i].split('|'));
	}
	for(var i =1; i<points.length; ++i){
		if(points[i][0].substring(0,2)=='[[')
		{
			points[i][0]=points[i][0].substring(1)
		}

		if(points[i][6]!=null)
		{
			if(points[i][1].split(' ')[1]==6)
			{
				points[i][7]=points[i][7].slice(2,-3)
			}
			else{
				points[i][6]=points[i][6].slice(2,-3)
			}
		}
	}
	function changespeed(obj) {
		speed = speedArr[obj.value];
	}
	$(".recoderList").find("li").on("click",function(){
		
	})
	</script>
</head>
<body>
<input class="jb" id="jb"  type="range"/>
	<div class ="sidenav">
		<ul>
			<li class="w_100"><img  width="90" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1579695730.png"/></li>
		</ul>
		<ul>
			<li class="w_100"><img alt="" name="play" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642969677.png"  id="play"  onclick=""  /></li>
			<li><img name="stop" id='stop' src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642969781.png" width=0 onClick="pause()" /></li>  
		</ul>
 
		<ul>
			<li class="w_100">
				<select name="play_speed" onchange="javascript:changespeed(this);" id = "play_speed" style = "width:98%;height=50;text-align-last:center;">
				<?php for($i = 1; $i <= 5; $i++){?>
					<option value="<?php echo $i?>" <?php echo ($i == $speed)? " selected" : "" ?> ><?php echo $i?></option>
				<?php }?>
				</select>
			</li>

		</ul>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

<?php 
 
if($role!=='student') echo '<button id="board" type="button" onclick=boardlink() >수정하기</button><label><input type="checkbox" name="sketch" > 밑그림숨기기</label><label><input type="checkbox" name="autoplay" > 자동재생</label>';
echo '<button type="button" id = "linkclipboard" onclick="">질문발송</button>

<li class="w_100"><span  type="button"  onClick="ConnectNeurons1()"><img src="https://mathking.kr/Contents/IMAGES/preparememory.png" width=90></span></li>
<li class="w_100"><span  type="button"  onClick="ConnectNeurons2()"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651711310.png" width=90></span></li>
<li class="w_100"><span  type="button"  id="alert_comment" onClick=""><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656222044.png" width=90></span></li>
<br><br>
<button id="recordButton"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617238913001.png" width=50></button>
<div id="timer" style="color:white; text-align:center;" >00:00:00</div>   
<button id="pauseButton" disabled>일시정지</button>	<button id="stopButton" disabled>발표종료</button>';
if($role!=='student') echo '<button type="button"   style = "color:black;width:88px;height:30px;"  onclick="resetRecording();" >녹화삭제</button>'; 

if(strpos($id, '_user')!==false)echo '<br><span  type="button" style = "z-index:3;  position:fixed;  margin-bottom:10px; top: 0%;right:10%;width:200;height:100px;"  onClick="ConnectNeurons3()"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656023330.png" width=70></span>';
elseif($role!=='student') echo '<br><span  type="button" style = "z-index:3;  position:fixed;  margin-bottom:10px; top: 0%;right:10%;width:200;height:100px;"  onClick="ConnectNeurons3()"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655945853.png" width=70></span>';

echo '<br><button type="button"   style = "color:black;width:88px;height:30px;"  onclick="location.href =\'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$srcid.'\';submitRecording();">노트보기</button>';
 
  

$repeatinfo=$DB->get_record_sql("SELECT data  FROM mdl_user_info_data where userid='$studentid' AND fieldid='86' "); 
$cjnrepeat=$repeatinfo->data;
if($cjnrepeat==NULL)$cjnrepeat=3;
  


echo '
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
 
<style>
.foo {
  width: 200px;
  height: 200px;
  overflow-y: hidden;
}

body.swal2-shown > [aria-hidden="true"] {
  transition: 0.01s filter;
  filter: blur(20px);
}
</style>
<script> 
 

	function ConnectNeurons1()
		{
		var Userid= \''.$userid.'\';	
 
 		Swal.fire({
		backdrop:false,position:"bottom",showCloseButton: true,width:1200,
		 
  
		  html:
		    \'<iframe  class="foo" scrolling="no" style="border: 0px none; z-index:2; width:1190;height:40vh; margin-left: -40px;margin-top: -10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/createdb.php?wbid='.$srcid.'&boardtype=booster&contentsid='.$contentsid.'&contentstype=1&studentid='.$userid.'&btype=preparem" ></iframe>\',
		  showConfirmButton: false,
		        })
		}
	function ConnectNeurons2()
		{
	              if (document.getElementById("play").src == "https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642972961.png") 
	     	 	{
			document.getElementById("play").click();		
	    		}
		Swal.fire({
			backdrop:true,position:"top-center",showCloseButton: true,width:1200,
			html:\'<iframe class="foo" scrolling="no" style="border: 0px none; z-index:2; width:1190;height:70vh;margin-left: -35px;margin-top: -10px;margin-bottom: -10px;"    src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/createdb.php?wbid='.$srcid.'&boardtype=repeat&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$userid.'&context=replay&nmax='.$cjnrepeat.'" ></iframe>\',
		  	showConfirmButton: false, 
		            });
		}
	function ConnectNeurons3()
	{
		var Userid= \''.$userid.'\';	
 
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:900,
		   showClass: {
   		 popup: "animate__animated animate__fadeInDown"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutUp"
		  },
		  html:
		    \'<iframe  class="foo" scrolling="no" style="border: 0px none; z-index:2; width:890; height:80vh;margin-left: -40px;margin-bottom: -10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/bookmark.php?wboardid='.$id.'&srcid='.$srcid.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'" ></iframe>\',
		  showConfirmButton: false,
		        })
	}

$(\'#alert_comment\').click(function(e) {

	var Userid= \''.$USER->id.'\';
	var Wboardid= \''.$id.'\';
	 
	if (document.getElementById("play").src == "https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642972961.png") 
	     	  	 	{
			 	document.getElementById("play").click();		
	    		    	}
				 
	 Swal.fire({
	  input: "textarea",
	  inputLabel: "Message",
	  backdrop: false,
	 
	  inputPlaceholder: "보충설명을 입력해 주세요.",
	  inputAttributes: {
	    "aria-label": "Type your message here"
	  },
	  showCancelButton: true
	}).then(  	
		function(result) { 
				var Inputtext=result.value;	  			
				swal("", "보충설명 : " + Inputtext, {buttons: false,timer: 500});
					$.ajax({
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'2\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"wboardid":Wboardid,
					 	 
					},
					success:function(data){
					
					 }
					 })
				document.getElementById("play").click();		 
				}  
				);
 		 
		 	 
	});   
</script>
 
<style>
body { 
  font-family: "Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", Helvetica, Arial, sans-serif; 
}
</style>';

?>


<script>
function resetRecording()
{ 
var Wboardid = "<?php echo $id;?>";
var Userid= "<?php echo $userid;?>";
swal("발표내용이 모두 삭제됩니다.", {buttons: false,timer: 500});  
 
	$.ajax({
	url:"deleteRecording.php",
	type: "POST",
	dataType:"json",
 	data : {
	"wboardid":Wboardid,
	"userid":Userid,	
	},
	success:function(data){
	 }
	 })
	 
	setTimeout(function(){
	location.reload();
	},100);  
}
 

function submitRecording()
{ 
var Wboardid = "<?php echo $id;?>";
var Userid= "<?php echo $userid;?>";

swal("내 노트로 이동합니다.", {buttons: false,timer: 500});   

	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":"1",
	"wboardid":Wboardid,
	"userid":Userid,	
	},
	success:function(data){
	 }
	 })
}
</script> 
	</div>
		
	<div class="container">
		<div id= "canvaswindow">
			<canvas id="maincanvas"  width=2000 height="6000" touch-action="none"></canvas>
		</div>
		<div id="recorder">

			<div id="controls">

			</div>
			<div id="formats"></div>
			<p><strong>Recordings:</strong></p>
			<ol id="recordingsList">
				<?php 
				
				$sql = "SELECT filename,log FROM record_log where encryption_id='{$id}' order by reg_dt desc";
				$result = mysqli_query($conn, $sql);
				while($row = mysqli_fetch_array($result)){?>
					<li><?php echo $row['filename']?><audio autoplay src="./recoder/<?php echo $id; ?>/<?php echo $row['filename']?>" controls data-log="<?php echo $row['log']?>"></audio></li>
				<?php }?>
			</ol>
			<!-- inserting these scripts at the end to be able to use all the elements in the DOM -->
			<script src="https://cdn.rawgit.com/mattdiamond/Recorderjs/08e7abd9/dist/recorder.js"></script>
			<input type="hidden" id="copyInput"/>
			<script src="record.js"></script>
		</div>
	</div>

</body>
</html>

