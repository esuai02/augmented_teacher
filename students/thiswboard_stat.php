<!DOCTYPE html>
<html>
<style>
* {
  box-sizing: border-box;
}
@media print  
{
    div {
        page-break-inside: avoid;
    }
}
img {
border: 1px solid #555;
 
}
body {
  margin: 0;
  font-family: Arial;
  overflow-x:hidden;
}

.header {
  text-align: center;
  padding: 32px;
}

.row {
  display: -ms-flexbox; /* IE10 */
  display: flex;
  -ms-flex-wrap: wrap; /* IE10 */
  flex-wrap: wrap;
  padding: 0 4px;
}

/* Create four equal columns that sits next to each other */
.column {
  -ms-flex: 25%; /* IE10 */
  flex: 25%;
  max-width: 25%;
  padding: 0 4px;
}

.column img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

/* Responsive layout - makes a two column-layout instead of four columns */
@media screen and (max-width: 1000px) {
  .column {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

/* Responsive layout - makes the two columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
}
 
</style>


<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 

global $DB, $USER;
$studentid = $_GET["id"]; 
$contentsid = $_GET["contentsid"]; 
$contentstype = $_GET["contentstype"]; 
$wboardid = $_GET["wboardid"]; 
$tfinish = $_GET["tfinish"]; 
$timecreated=time(); 


$adayago=$timecreated-86400;
$halfdayago=$timecreated-43200;

$mode= $_GET["mode"]; //풀이노트
if($studentid==NULL)$studentid=$USER->id;


$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$stdtname->firstname.$stdtname->lastname;

$tabtitle=$studentname;
echo ' <head><title>'.$tabtitle.'A</title></head><body>';
	
$replay3=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='2' AND boardtype LIKE 'test'  ORDER BY nstroke DESC LIMIT 10");  
$result3 = json_decode(json_encode($replay3), True);

unset($value3);
foreach( $result3 as $value3)
	{
	$creatorid=$value3['userid'];
	$thisuser= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id=' $creatorid' ");
	$thisusername=$thisuser->firstname.$thisuser->lastname;

		if($creatorid==$studentid)$view3.='<div   class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$value3['wboardid'].'&studentid='.$studentid.'&mode=peer"><b style="color:blue;">My</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>나의 풀이</td></tr></table></span></div>  &nbsp;';
		elseif($wboardid===$value3['wboardid'])$view3.='<div  class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$value3['wboardid'].'&studentid='.$studentid.'&mode=peer"><b style="color:red;">'.$value3['nstroke'].'획</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>크리에이터 '.$creatorid.' : '.$thisusername.'</td></tr></table></span></div>  &nbsp;';
		else $view3.='<div  class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$value3['wboardid'].'&studentid='.$studentid.'&mode=peer">'.$value3['nstroke'].'획</a><span class="tooltiptext3"><table style="" align=center><tr><td>크리에이터 '.$creatorid.' : '.$thisusername.'</td></tr></table></span></div> &nbsp; ';
	}
echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
</table><table align=center><tr><th align=left>'.$studentname.' | 피어러닝  </th><th valign=top>&nbsp;&nbsp; '.$view3.'  </th></tr></table>';
echo '						<li class="nav-item">
						<a href="#" class="nav-link quick-sidebar-toggler">
							<i class="flaticon-envelope-1"></i>
						</a>
					</li>';


 
echo ' <div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12"><table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$sharelist.'</tbody></table> ';
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										 
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';

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
</style>';



/* 
echo '
<script>	
function reportData(Userid,Sid,Username)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "Talk2us (" + Username +")",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: "공유된 의견과 데이터를 토대로 의견을 입력해 주세요",
  	inputAttributes: {
   	 "aria-label": "Type your message here"
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'11\',
		"inputtext":text,	
		"userid":Userid,
		"sid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
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
		"eventid":Eventid,
            		"fbid":Fbid,
            	 	"checkimsi":checkimsi,
            	 	  },
 	  	 success: function (data){  
		var Teacherid=data.teacherid
		setTimeout(function() {location.reload(); },100);	
  	   	   }
		  });
		}

function Edittext(Itemid,Inputtext)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "내용 수정하기",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputValue: Inputtext,
  	inputAttributes: {
   	 "aria-label": "Type your message here"
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'19\',
		"itemid":Itemid,
		"inputtext":text,	
		},
		success:function(data){
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
	}
</script> ';
*/
 
echo ' 
<style>
.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip3 .tooltiptext3 {
  visibility: hidden;
 
  background-color: white;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 0px ;

 bottom:8%;
 left:40%;
  position: fixed;
   z-index: 2;
}
 
.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position:fixed;
  display: inline;
}
</style>';
 
?>


</body>
</html>
