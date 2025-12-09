<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$cid=$_GET["cid"];
$nch=$_GET["nch"]; 
$cmid=$_GET["cmid"]; 
$nthispage=$_GET["page"];
$pgtype=$_GET["pgtype"];
$quizid=$_GET["quizid"];
$studentid=$_GET["studentid"]; 
$timecreated=time(); 
//include("gpttalk.php");


if($studentid==NULL)$studentid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
$lstyle=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='90'  ORDER BY id DESC LIMIT 1"); 
$learningstyle=$lstyle->data;

$userinfo= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$username=$userinfo->firstname.$userinfo->lastname;

$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
if($role==='student')$tabtitle='G : '.$weeklyGoal->text;
else $tabtitle=$username.'의 수학노트';

$mynoteurl= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
$mynotecontextid=substr($mynoteurl, 0, strpos($mynoteurl, '?')); // 문자 이후 삭제
$mynoteurl=strstr($mynoteurl, '?');  //before
$mynoteurl=str_replace("?","",$mynoteurl); 

$cntpages=$DB->get_records_sql("SELECT * FROM mdl_icontent_pages where cmid='$cmid' ORDER BY pagenum ASC   ");  //AND  title NOT LIKE '%Approach%' 
$result = json_decode(json_encode($cntpages), True);
 
unset($value);
foreach($result as $value)
	{
	$title=$value['title']; 
	$npage=$value['pagenum']; 
  $contentsid=$value['id'];
  $srcid='jnrsorksqcrark'.$contentsid;	
  $wboardid='jnrsorksqcrark'.$contentsid.'_user'.$studentid;
 
  $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY timemodified DESC LIMIT 1"); 
  $thiscnt=$DB->get_record_sql("SELECT milestone FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
  $milestone=$thiscnt->milestone;
  if($milestone==NULL)$milestone=0;
  if($thisboard->wboardid==NULL && $USER->id==$studentid)$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,student_check,status,contentstype,wboardid,contentstitle,contentsid,url,timemodified,timecreated) VALUES('$studentid','2','$role','2','0','$milestone','0','begintopic','1','$wboardid','inspecttopic','$contentsid','$mynoteurl','$timecreated','$timecreated')");
  
	if($npage==1)$headimg='<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg1.png width=15>';
	elseif(strpos($title, 'Check')!= false)$headimg='<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png width=15>';
	elseif(strpos($title, '유형')!= false)$headimg='<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg3.png width=15>';
	else $headimg='<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png width=15>';
  $cjnfblist='';
  $attemptresult='';
  $presetfunction='ConnectNeurons';
  
  if($pgtype==='quiz')
    {
    $showpage='https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid;
    if($learningstyle==='도제' && strpos($title, '대표')!==false)echo '';
    elseif(strpos($title, '유형')!= false)$contentslist2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.' '.$title.'</a> '.$attemptresult.'</td></tr>'; 
    else $contentslist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.' '.$title.'</a> '.$attemptresult.'</td></tr>';
      
    $nnextpage=$nthispage+1;
    $nextpage=$DB->get_record_sql("SELECT id,title FROM mdl_icontent_pages where cmid='$cmid' AND pagenum='$nnextpage' ORDER BY id DESC LIMIT 1");  
    
 
    if(strpos($nextpage->title, '유형')!= false && $quizid!=NULL)$nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$nnextpage.'&studentid='.$studentid;
    elseif($quizid!=NULL)$nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&cntid='.($cmid+1).'&studentid='.$studentid;
   
 //$nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page=1&studentid='.$studentid;
      //$nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page=1&studentid='.$studentid;
    $rule='<a style="text-decoration:none;color:white;" href="'.$nextlearningurl.'"><button class="stylish-button">NEXT</button></a>';     
    }
  elseif($npage==$nthispage)
		{
		$topictitle=$value['title'];
    $audiocnt='';
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
    $maintext=$cnttext->maintext;
    
		$thispage=$npage; 
    $bessiboard='cjnNotepageid'.$contentsid.'jnrsorksqcrark';
 
    $thiswbid=$bessiboard.'_user'.$studentid;
    $thisstamp=$DB->get_record_sql("SELECT id FROM mdl_abessi_questionstamp where wboardid='$bessiboard' ORDER BY id DESC LIMIT 1 ");
    $showpage='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'&contentsid='.$contentsid.'&studentid='.$studentid.'&quizid='.$quizid.'&'.$mynotecurrenturl;
    $showpage2=$showpage;
    if($thisstamp->id==NULL)
      {
      $viewcnticon='<img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/nocontent.jpg" height=0>';
      $editmode='board';
      }
    else 
      {
      $viewcnticon='<img loading="lazy"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659013455.png" height=20>';
      if(strpos($topictitle, '이해')!== false || strpos($topictitle, '특강')!== false)
        {
        $showpage='https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$bessiboard.'&srcid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&studentid='.$studentid;
        }
        $editmode='replay';
      }
		$gpteventname='개념노트';
		$contextid='mynote_cid'.$cid.'nch'.$nch.'cmid'.$cmid.'page'.$npage;
	 
  
	}
  

if($quizid!=NULL)
  {
  if($pgtype==='quiz')$attemptquiz='<tr><td style="background-color:lightblue;">'.$headimg.'  개념체크 퀴즈  '.$attemptresult.' <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'"target="_blank">(<b style="color:#E4167D;">시도</b>)</a> </td></tr>';
  else $attemptquiz='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&pgtype=quiz&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.'개념체크 퀴즈</a>  <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'"target="_blank">(<b style="color:#E4167D;">시도</b>)</a>'.$attemptresult.'</td></tr>'; 
  }

$activities=''; 


echo '
<head>
  <title>'.$tabtitle.'</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
 </head>
 <body><table align=center><tr><td width=80% valign=top>';

if(strpos($topictitle, '특강')!==true &&	$npage==11111) echo '<iframe loading="lazy"  style="border: 1px none; z-index:2; width:80vw; height:50vh;  margin-left:-0px; margin-top: 0px; "  src="'.$showpage.'" ></iframe>';
else echo '<iframe loading="lazy"  style="border: 1px none; z-index:2; width:80vw; height:100vh;  margin-left:-0px; margin-top: 0px; "  src="'.$showpage.'" ></iframe></td><td width=2%></td><td valign=top width=20%> <br><br><table>'.$contentslist.$attemptquiz.'<tr><td><br></td></tr>'.$contentslist2.'</table><br><table><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&cntid='.($cmid+1).'&studentid='.$studentid.'"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944121001.png width=20> 목차</a>'.$singleref.$cntlink.'</td></tr>
  <tr><td align=left width=22vw style="color:#347aeb;" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><br>'.$rule.' <br><br> </td></tr>
  <tr><td align=center width=22vw><img loading="lazy" src=http://ojsfile.ohmynews.com/STD_IMG_FILE/2015/0307/IE001806909_STD.jpg width=200> <br><br> 기억방으로(클릭)</td></tr></table><hr><table>'.$stepbystepcnt.'</table><table><tr><td><br>'.$activities.'</td></tr></table></td></tr></table>
</body>';
	 

echo '	

<script>

function ConnectNeurons(Contentsid)
	{
		var Userid= \''.$studentid.'\';	
 
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:1200,
		   showClass: {
   		 popup: "animate__animated animate__fadeInDown"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutUp"
		  },
		  html:
		    \'<iframe loading="lazy"   class="foo" style="border: 0px none; z-index:2; width:1180; height:90vh;margin-left: -20px;margin-bottom: -10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid=\'+Contentsid+\'&cnttype=1" ></iframe>\',
		  showConfirmButton: true,
		  })
	}
  function InputAnswers()
	{ 
 		Swal.fire({
		backdrop:false,position:"top",showCloseButton: true,width:500,
		   showClass: {
   		 popup: "animate__animated animate__fadeInDown"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutUp"
		  },
		  html:
		    \'<iframe loading="lazy"   class="foo" style="border: 0px none; z-index:2; width:470; height:30vh;margin-left: -20px;margin-bottom: -10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/inputanswers.php?srcid='.$srcid.'" ></iframe>\',
		  showConfirmButton: true,
		        })
	}
  
</script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';

echo '<style>
.stylish-button {
  background-color: #FF69B4; /* 네온 핑크 색상 */
  color: white;
  padding: 5px 5px;
  width:6vw;
  border: none;
  cursor: pointer;
  font-family: "Arial Rounded MT Bold", sans-serif;
  font-size: 16px;
  transition: background-color 0.3s ease;
}

.stylish-button:hover {
  background-color: #FF1493; /* 색상을 조금 더 진하게 */
}

.stylish-button:active {
  transform: translateY(2px);
}

.stylish-button:focus {
  outline: none;
}

.icon {
  padding-left: 5px;
}
#typing-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    padding: 0px;
  }
   
  #typing-box {
    width: 90%;
    padding:0px;
    border-radius: 10px;
    background-color: #f5f5f5;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
 
  #typing-cursor {
    width: 5px;
    height: 20px;
    background-color: #000;
    animation: cursor-blink 1s infinite;
  }
  
  @keyframes cursor-blink {
    0% {
      opacity: 0;
    }
    50% {
      opacity: 1;
    }
    100% {
      opacity: 0;
    }
  } 
  #typing-text {
    font-size: 20px;
    line-height: 1.5;
    margin-left:0px;
    margin-top: 5px;
  }
  
  @media (max-width: 767px) {
    /* Set font size for screens smaller than 768px (smartphones) */
    #typing-text {
      font-size: 20px;
    }
  }
  
</style>
  
<script>
var text = "'.$gpttalk.'";
var lines = text.split("\n");
var lineIndex = 0;
var charIndex = 0;
var speed = 50;
var typingTimer;

function typeLine() {
  var line = lines[lineIndex];
  if (charIndex < line.length) {
    document.getElementById("typing-text").innerHTML += line.charAt(charIndex);
    charIndex++;
    typingTimer = setTimeout(typeLine, speed);
  } else if (lineIndex < lines.length - 1) {
    document.getElementById("typing-text").innerHTML += "<br>";
    lineIndex++;
    charIndex = 0;
    typingTimer = setTimeout(typeLine, speed);
  }
}

typeLine();
</script>'; 


//$cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
 
if($role==='student')include("../students/alert.php");
if($userid==NULL)$userid=$studentid;
//if($maintext!=NULL)
  {
 

  /*
  $threshold=5;
  echo '<button style=" z-index:5;" onclick="createQuestions(1,1,\''.$contentsid.'\');"><img style=" z-index:5;position: fixed;bottom: 0;right: 0;transition: transform 2s;" id="growinghint" src="https://mathking.kr/Contents/IMAGES/growinghint.png"></button>
    <script>
    var threshold = \''.$threshold.'\';  // say hello in threshold(분), 개인화 및 문항별...
    var scaleinterval=1/threshold/120;
    (function() {
      let img = document.getElementById("growinghint");
      let scale = 0;
      let intervalId = null;
    
      // 3개의 이미지 URL
      let images = ["https://mathking.kr/Contents/IMAGES/growinghint1.png", "https://mathking.kr/Contents/IMAGES/growinghint2.png", "https://mathking.kr/Contents/IMAGES/growinghint2.png"];
    
      function startGrowing() {
        intervalId = setInterval(function() {
          scale += scaleinterval;
          img.style.transform = "scale(" + scale + ")";
    
          // 스케일에 따라 이미지를 변경
          if (scale < 0.5) {
            img.src = images[0];
          } else if (scale < 0.99) {
            img.src = images[1];
          } else {
            img.src = images[2];
          }
    
          if (scale >= 1) {
            clearInterval(intervalId);
            intervalId = null;
          }
        }, 1000);
      }
    
      function stopGrowing() {
        if (intervalId) {
          clearInterval(intervalId);
          intervalId = null;
        }
      }
    
      document.addEventListener("visibilitychange", function() {
        if (document.hidden) {
          stopGrowing();
        } else {
          startGrowing();
        }
      });
    
      // 시작하기
      startGrowing();
    })();
      
    function createQuestions(Eventid,Contentstype,Contentsid)		
    {
    swal("", "GPT에 요청이 전달되었습니다.", {buttons: false,timer: 2000});
    $.ajax({
    url:"../LLM/createHintsfromGPT.php",
    type: "POST",
    dataType:"json",
    data : {
    "eventid":Eventid,
    "contentstype":Contentstype,
    "contentsid":Contentsid,
    },
    success:function(data){
    Logid=data.logid;
    Title=data.title;
    Swal.fire({
      title: Title,
      position: "top-end",
      backdrop:false,
      width: 500,
      height:1000,
      html: `<iframe loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/LLM/gptresult.php?logid=`+Logid+`" width="100%" height="1000" scrolling="no"></iframe>
      `,
      showCloseButton: true, 
      confirmButtonText: "확인",
      })
    }
    })
    };	
    </script>';	 
  */
  }

  echo '<script> 
window.onload = function() {
    let whiteboard = document.getElementById("canvas"); // 화이트보드 요소의 ID를 "whiteboard"로 가정

    whiteboard.addEventListener("mousedown", function(event) {
        event.preventDefault();
        // 여기에 드래그 시작에 대한 코드를 작성
    });

    whiteboard.addEventListener("mousemove", function(event) {
        event.preventDefault();
        // 여기에 드래그 중에 대한 코드를 작성
    });

    whiteboard.addEventListener("mouseup", function(event) {
        event.preventDefault();
        // 여기에 드래그 끝에 대한 코드를 작성
    });

    let whiteboard2 = document.getElementById("canvas2"); // 화이트보드 요소의 ID를 "whiteboard"로 가정

    whiteboard2.addEventListener("mousedown", function(event) {
        event.preventDefault();
        // 여기에 드래그 시작에 대한 코드를 작성
    });

    whiteboard2.addEventListener("mousemove", function(event) {
        event.preventDefault();
        // 여기에 드래그 중에 대한 코드를 작성
    });

    whiteboard2.addEventListener("mouseup", function(event) {
        event.preventDefault();
        // 여기에 드래그 끝에 대한 코드를 작성
    });
}; 

document.addEventListener("visibilitychange", function() {
  if (document.visibilityState === "visible") {
    var Wboardid= \''.$thiswbid.'\'; 
    var Userid= \''.$studentid.'\';  
    $.ajax({
       url:"../whiteboard/check.php",
       type: "POST", 
       dataType:"json",
       data : {
       "eventid":\'16\', 
       "userid":Userid,
       "wboardid":Wboardid,
       },
       success:function(data){					 
      }
    }) 	   
  }
  });

</script>';
?>
