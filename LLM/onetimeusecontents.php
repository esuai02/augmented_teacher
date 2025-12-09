<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$studentid = $_GET["studentid"];
$nadaptive = $_GET["nadaptive"];
$print = $_GET["print"];

$cnttext=$DB->get_record_sql("SELECT * FROM mdl_abessi_onetimeusecontents where contentsid='$cntid' AND contentstype='$cnttype'  ORDER BY id DESC LIMIT 1");  

$cnttextinfo='cnttext'.$nadaptive;
$wboardid='AdaptiveCNTTYPE'.$cnttype.'ID'.$cntid.'TYPE'.$nadaptive;
$adaptiveinfo=$cnttext->$cnttextinfo;

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
 

'; //<script> alert("과거 학습데이터를 토대로 최적의 보충학습을 찾고 있습니다");    </script>
include("../bestnext.php");

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;


$Hippocampustest='';
if($cnttext->reflections1!=NULL)$Hippocampustest='&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=1"><button>확인테스트</button></a>';

//if($print==1)$

echo '<!DOCTYPE html><html><head><script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<style>
    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        margin: 20px;
        color: #333;
        background-color: #ffffff;
    }
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
        body {
            margin: 10px;
        }
        .problem-statement, .instructions {
            border-left: none;
        }
    }
</style>
<style>
    .collapsible {
      cursor: pointer;
      padding: 10px;
      width: 100%;
      text-align: left;
      border: none;
      outline: none;
      transition: 0.4s;
    }
    
    .active, .collapsible:hover {
      background-color: #f1f1f1;
    }
    
    .content {
      padding: 0 18px;
      display: none;
      overflow: hidden;
      transition: max-height 0.2s ease-out;
    }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
  var coll = document.getElementsByClassName("collapsible");
  for (var i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
      var content = this.nextElementSibling;
      if (content.style.display === "none" || content.style.display === "") {
        content.style.display = "block";
      } else {
        content.style.display = "none";
      }
    });
  }
});
</script>

</head><body><div width=70% style="background-color: white;">
<table  id="content-to-capture"  width=80% ><tr><td valign=top  class="no-print" width=2%><br><br>&nbsp;&nbsp;&nbsp;</td><td valign=top>'.$adaptiveinfo.'</td><td valign=top><br><br><a href="https://chat.openai.com/g/g-apWVmPMop-hogisim-haegyeolsa"target="_blank"><img class="no-print" src="https://mathking.kr/Contents/IMAGES/curiosity.png" width=30px></a></td></tr></table>
</div>
<div><table align=center><tr><td valign=top style="font-size:12px;"  class="no-print">  <button id="createwhiteboard" onclick="convertToImageAndUpload(\''.$studentid.'\',\''.$cntid.'\',\''.$print.'\')">화이트보드로 이동</button> &nbsp;&nbsp;&nbsp;&nbsp;'.$Hippocampustest.'</td></tr></table></div><br><br><br><br><br><br><br></body></html> 


<script type="text/x-mathjax-config">
MathJax.Hub.Config({
  tex2jax: {
    inlineMath:[["$","$"],["$$","$$"],["\(","\)"]],
    //displayMath: [ ["$","$"], ["\\[","\\]"],["\(","\)"]]
  }
});
</script>
<script type="text/javascript" async
  src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML">
</script>  
<script>
    tinymce.init({
      selector: "textarea",
      plugins: "anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount ",
      toolbar: "undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat",
      tinycomments_mode: "embedded",
      tinycomments_author: "Author name",
      mergetags_list: [
        { value: "First.Name", title: "First Name" },
        { value: "Email", title: "Email" },
      ]
    });
  </script> ';

echo ' 
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script> 
async function convertToImageAndUpload(studentid, contentsid, print) {
  try {
      const canvas = await html2canvas(document.getElementById("content-to-capture"), {
          ignoreElements: function (element) {
              return element.tagName === "DETAILS" || element.closest("details");
              }
          });
  
      canvas.toBlob(async blob => {
          const formData = new FormData();
          formData.append("image", blob, "image.png");
          formData.append("studentid", studentid); // studentid 값을 FormData에 추가합니다.
          formData.append("contentsid", contentsid); // contentsid 값을 FormData에 추가합니다.
          formData.append("print", print); // contentsid 값을 FormData에 추가합니다.

          try {
              const response = await fetch("uploadimage.php", { // 업로드 스크립트 경로를 지정합니다.
                  method: "POST",
                  body: formData,
              });
 
           
              const result = await response.json();
              if (result.success) {
                  // 업로드 성공 시, 필요한 후속 조치를 수행합니다.
                  window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/create_SPECWB.php?wboardid='.$wboardid.'&studentid='.$studentid.'&contentsid='.$cntid.'&contentstype=1&print='.$print.'&imageurl=${result.url}`;
              } else {
                window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/create_SPECWB.php?wboardid='.$wboardid.'&studentid='.$studentid.'&contentsid='.$cntid.'&contentstype=1&print='.$print.'&imageurl=${result.url}`;
              }
          } catch (error) {
              console.error("업로드 실패:", error);
              alert("이미지 업로드에 실패했습니다.");
          }
      }, "image/png");
  } catch (error) {
      console.error("이미지 생성 실패:", error);
      alert("이미지 생성에 실패했습니다.");
  }
}

</script>
';
echo '<script>   
function saveContent(Eventid,Cntid)
  {
    var editor = tinymce.get("mytextarea");   
    var htmlContent = editor.getContent();
    var NewHtml = htmlContent;    
   // NewHtml = NewHtml.replace(/(<([^>]+)>)/ig,"");

    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
    var NewHtml2 = htmlContent2;    

    var editor3 = tinymce.get("mytextarea3");   
    var htmlContent3 = editor3.getContent();
    var NewHtml3 = htmlContent3;    
   // NewHtml2 = NewHtml2.replace(/(<([^>]+)>)/ig,"");

        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "cntid":Cntid,		 
              "inputtext":NewHtml, 
              "inputtext2":NewHtml2, 
              "inputtext3":NewHtml3, 
            },
            success:function(data){
                    var Cntid2=data.cntid;
                    swal("OK !", "저장되었습니다.", {buttons: false,timer: 100});
                    setTimeout(function(){location.reload();} , 100); 
                    }
             })
}
 
</script>

<style>a[href]:after { content: none !important; }
@media print {
  .no-print {
      display: none;
  }
 
}
</style>
';




if ($role === 'student') {
  echo '<script>
  swal("보충학습을 위한 화이트보드가 생성 중입니다.", {buttons: false,timer: 2000});
  window.addEventListener("load", (event) => {
    setTimeout(function() {
      document.getElementById("createwhiteboard").click();
      }, 2000); 
  });
  </script>';
}
?>
