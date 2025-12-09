<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$studentid = $_GET["userid"]; 
$aweekago=time()-604800;
$lastthread=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where studentid LIKE '$studentid' AND status='begin' AND timecreated>'$aweekago' ORDER BY id DESC LIMIT 1 ");  
if($lastthread->id==NULL)$showstatus='완료됨';
else $showstatus='제출하기';

if($lastthread->quiz1!=NULL)$showanki .= '<tr><td><hr></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz1.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 1</h5></a></td></tr>' ; 
if($lastthread->quiz2!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz2.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 2</h5></a></td></tr>' ; 
if($lastthread->quiz3!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz3.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 3</h5></a></td></tr>' ; 
if($lastthread->quiz4!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz4.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 4</h5></a></td></tr>' ; 
if($lastthread->quiz5!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz5.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 5</h5></a></td></tr>' ; 
if($lastthread->quiz6!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz6.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 6</h5></a></td></tr>' ; 
if($lastthread->quiz7!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz7.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작7</h5></a></td></tr>' ; 
if($lastthread->quiz8!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz8.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 8</h5></a></td></tr>' ; 
if($lastthread->quiz9!=NULL)$showanki .= '<tr><td></td></tr><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz9.'&studentid='.$lastthread->studentid.'"target="_blank"><h5>퀴즈시작 9</h5></a></td></tr>' ; 

echo ' 
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
 <!DOCTYPE html><html><head><script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ANKI 활동</title>  
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

 
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
 
	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
<style> 


    .header {
        text-align: left;
        margin-bottom: 20px;
    }
    .problem-statement {
        background-color: #fff;
        padding: 20px;
        text-align: left; font-size: 1.2em;
        border-left: 5px solid #ffffff;
        margin-top:-20px; margin-bottom:-20px;
        box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    .instructions {
        background-color: #fff;
        text-align: left;
        padding: 40px;
        font-size: 1em;
        margin-top:-50px;  margin-bottom: 0px;
       // box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    .instruction {
        margin-bottom: 10px;margin-top:-50px; 
        text-align: left;
    }
    .footer {
        text-align: center;
        margin-top: 0px;
        font-size: 0.85em;
        color: #666;
    }
    @media only screen and (max-width: 600px) {

        .problem-statement, .instructions {
            border-left: none;
        }
    }
    
</style>
<style>
body {
    display: flex;
    justify-content: top;
    align-items: center;
    width:80vw;
    height: 100vh;
    margin: 0;
    background-color: #f0f0f0;
} 
</style>

</head> 
<body> 
<table align=center>
<tr><td valign=top align=center>활동결과는 귀가검사 시 점검 예정입니다.<br><br><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/ankiquiz.png width=400> </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td align=center valign=top><table align=center>'.$showanki.'</table> <br> <br><button onclick="completeAnki(\''.$lastthread->id.'\');"><h4>'.$showstatus.'</h4></button></td></tr></table>
 
</body>
 
</html> 
  ';

 
echo ' 

<style>a[href]:after { content: none !important; }
@media print {
  .no-print {
      display: none;
  }
 
}
</style>
<script>
function completeAnki(Threadid)
    {
    swal("활동을 완료합니다.",{buttons: false,timer: 1000});
    $.ajax({
    url:"check.php",
    type: "POST",
    dataType:"json",
    data : {
    "eventid":\'25\',
    "threadid":Threadid,	 			 
    },
success:function(data){
    }
    })
location.reload(); 
}
</script>
';
 
?>
