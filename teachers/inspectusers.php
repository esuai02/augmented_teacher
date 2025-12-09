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
 
$timecreated=time(); 
$adayago=$timecreated-86400;
$aweekago=$timecreated-604800;
$mode= $_GET["mode"]; //풀이노트
$weeksago2=$timecreated-604800*2;
$halfanhour=$timecreated-1800;


$SEE= $DB->get_record_sql("SELECT id,lastname, firstname FROM mdl_user WHERE lastaccess>'$aweekago' AND (firstname LIKE '%$tsymbol%') AND  suspended=0 ORDER BY rand() LIMIT 1");
$studentid=$SEE->id;
 
$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$stdtname->firstname.$stdtname->lastname;

$tabtitle=$studentname;
$nextuser='학년선택 [    ] | 레벨선택 [    ] | 학교선택 [    ] | 전체목록으로 전환 |  <a href="https://mathking.kr/moodle/local/augmented_teacher/managers/inspectusers.php?id='.$USER->id.'">NEXT</a> ';
echo ' <head><title>SEE</title></head><body>';
 	 
echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:92vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" ></iframe></th></tr></table>
<hr><table align=center><tr><th align=left>'.$nextuser.'</th></tr></table><hr>';

echo '<li class="nav-item"><a href="#" class="nav-link quick-sidebar-toggler"><i class="flaticon-envelope-1"></i></a></li>';
 
 
echo '<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
 
 
echo '<script>
function dragChatbox(Wboardid)
		{
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:700, 
		   showClass: {
   		 popup: "animate__animated animate__fadeInRight"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutRight"
		  },
		  html:
		    \'<iframe  class="foo"  style="border: 0px none; z-index:2; width:680; height:95vh;margin-left: -40px;margin-top:-30px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivetalk.php?wboardid=\'+Wboardid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
	</script>';
 
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

 bottom:6%;
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
