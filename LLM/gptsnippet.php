

<?php  

if($pagetype==='dialogue' || $pagetype==='whiteboard')
	{ 
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-end",showCloseButton: true,width:750,
			showClass: {
			popup: "animate__animated animate__fadeInRight"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOutRight"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:740; height:40vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	</script>';	  
	}
elseif($pagetype==='wbtype1')//풀이노트
	{ 
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-end",showCloseButton: true,width:750,
			showClass: {
			popup: "animate__animated animate__fadeInRight"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOutRight"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:740; height:40vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	</script>';	  
	}
elseif($pagetype==='wbtype2')//평가준비
	{ 
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-end",showCloseButton: true,width:750,
			showClass: {
			popup: "animate__animated animate__fadeInRight"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOutRight"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:740; height:40vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	</script>';	  
	}
elseif($pagetype==='wbtype1')//서술평가
	{ 
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-end",showCloseButton: true,width:750,
			showClass: {
			popup: "animate__animated animate__fadeInRight"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOutRight"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:740; height:40vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	</script>';	  
	}
elseif($pagetype==='wbtype4')//개념 인출활동
	{ 
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-end",showCloseButton: true,width:750,
			showClass: {
			popup: "animate__animated animate__fadeInRight"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOutRight"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:740; height:40vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	</script>';	  
	}
elseif($pagetype==='copilot') // 오늘활동 등
	{ 
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');" onload="document.getElementById(\'alert_wait\').click()"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"bottom",showCloseButton: true,width:1000,backgroundcolor: "black",
			showClass: {
			popup: "animate__animated animate__fadeInUp"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOut"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:950; height:20vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chatmentor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
		setTimeout(function() {
				document.getElementById("alert_wait").click();
			  }, 1000);
	</script>';
	}

elseif($pagetype==='mctalk')
	{
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-right",showCloseButton: true,width:550,backgroundcolor: "black",
			showClass: {
			popup: "animate__animated animate__fadeInUp"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOut"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:500; height:80vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	window.addEventListener("DOMContentLoaded", function() {
  	document.getElementById("alert_wait").click();
	});
	</script>';
	}
elseif($pagetype==='topic')
	{
		//$currentAnswer='aaa';
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');"><i class="fas fa-comment">CD</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"top-right",showCloseButton: true,width:550,backgroundcolor: "black",
			showClass: {
			popup: "animate__animated animate__fadeInUp"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOut"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:500; height:80vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	window.addEventListener("DOMContentLoaded", function() {
  	document.getElementById("alert_wait").click();
	});
	</script>';
	}
elseif($pagetype==='replay')
	{
	echo '
	<div class="chat-icon">
	<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$answerShort.'\',\''.$count.'\',\''.$currentAnswer.'\',\''.$rolea.'\',\''.$roleb.'\',\''.$talka1.'\',\''.$talkb1.'\',\''.$talka2.'\',\''.$talkb2.'\',\''.$tone1.'\',\''.$tone2.'\',\''.$pagetype.'\');" onload="document.getElementById(\'alert_wait\').click()"><i class="fas fa-comment">C</i></span>
	</div>
	<script>
	function dragChatbox(Userid,answerShort,count,currentAnswer,rolea,roleb,talka1,talkb1,talka2,talkb2,tone1,tone2,Pagetype)
			{
			Swal.fire({
			backdrop:false,position:"bottom",showCloseButton: true,width:1000,backgroundcolor: "black",
			showClass: {
			popup: "animate__animated animate__fadeInUp"
			},
			hideClass: {
			popup: "animate__animated animate__fadeOut"
			},
			html:
				\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:950; height:20vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor.php?userid=\'+ Userid+ \'&answerShort=\'+answerShort+ \'&count=\'+ count+ \'&currentAnswer=\'+currentAnswer+ \'&rolea=\'+ rolea+ \'&roleb=\'+ roleb+ \'&talka1=\'+ talka1 + \'&talkb1=\'+talkb1+ \'&talka2=\'+ talka2 + \'&talkb2=\'+talkb2+ \'&tone1=\'+ tone1+ \'&tone2=\'+tone2+ \'&pagetype=\'+Pagetype+\'" ></iframe>\',
			showConfirmButton: false,
					})
			} 
	 
	</script>';
	}
elseif($pagetype==='replay0')
	{ 
	echo '
		<div class="chat-icon">
		  <span class="chat-button" id="startTalk" onclick=""><i class="fas fa-comment">C</i></span>
		</div> ';
	include("chattutor_embed.php");
	}
elseif($pagetype==='assessment')
	{
		echo '
		<div class="chat-icon">
		<span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$contentstype.'\',\''.$contentsid.'\');"><i class="fas fa-comment">C</i></span>
		</div>
		<script>
 
		function dragChatbox(Userid,Contentstype,Contentsid)
				{
				Swal.fire({
				backdrop:false,position:"top-end",showCloseButton: true,width:750,
				showClass: {
				popup: "animate__animated animate__fadeInRight"
				},
				hideClass: {
				popup: "animate__animated animate__fadeOutRight"
				},
				html:
					\'<iframe  class="foo"  style="border: 0px none; z-index:2; width:740; height:40vh;margin-left: -20px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/chattutor_assessment.php?userid=\'+ Userid+ \'&contentstype=\'+Contentstype+ \'&contentsid=\'+ Contentsid+ \'" ></iframe>\',
				showConfirmButton: false,
						})
				} 
		</script>';	  		
	}
else // popup 등 나먼지 요청타입
	{
	echo '
<div class="chat-icon">
  <span class="chat-button" id="alert_wait" onclick="dragChatbox(\''.$userid.'\',\''.$initialtalk.'\',\''.$finetuning.'\',\''.$pagetype.'\');"><i class="fas fa-comment">C</i></span>
</div>
<script>
function dragChatbox(Userid,Initialtalk,Finetuning,Pagetype)
		{
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:750,
		   showClass: {
   		 popup: "animate__animated animate__fadeInRight"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutRight"
		  },
		  html:
		    \'<iframe  class="foo"  style="border: 0px none; z-index:2; width:680; height:100vh;margin-left: -40px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid=\'+ Userid+ \'&type=\'+ Pagetype+ \'&initialtalk=\'+ Initialtalk + \'&finetuning=\'+Finetuning+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
</script>';
	}

echo '
<style>
.chat-icon {
	position: fixed;
	bottom: 20px;
	right: 20px;
	z-index: 9999;
  }
  
  .chat-button {
	display: block;
	width: 50px;
	height: 50px;
	background-color: #007bff;
	color: #fff;
	border-radius: 50%;
	text-align: center;
	line-height: 50px;
	font-size: 24px;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
	transition: all 0.3s ease-in-out;
  }
  
  .chat-button:hover {
	transform: scale(1.1);
  }
  
  .chat-iframe {
	position: fixed;
	bottom: 80px;
	right: 20px;
	width: 350px;
	height: 450px;
	border: none;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
	transition: all 0.3s ease-in-out;
	transform: scale(0);
	opacity: 0;
	pointer-events: none;
  }
  
  .chat-iframe.active {
	transform: scale(1);
	opacity: 1;
	pointer-events: auto;
  }
  </style>';
?>

