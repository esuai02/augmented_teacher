<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$vmode = $_GET["vmode"];
 
$timecreated=time();
$userid = $_GET["userid"]; 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;


if($userid==NULL)$userid=$USER->id;
$getcnt=$DB->get_record_sql("SELECT * FROM mdl_abessi_blog where userid='$userid'  ORDER BY id DESC LIMIT 1");  
   
if($cntid==NULL)$cntid=$getcnt->id; 
if($getcnt->id==NULL)$DB->execute("INSERT INTO {abessi_blog} (userid,timecreated) VALUES('$userid','$timecreated')");	
$cnt=$DB->get_record_sql("SELECT * FROM mdl_abessi_blog where id='$cntid'  ORDER BY id DESC LIMIT 1");  
  
$image1=$cnt->img1; 
$image2=$cnt->img2; 
$image3=$cnt->img3; 
$image4=$cnt->img4; 
$image5=$cnt->img5; 
$image6=$cnt->img6;
$image7=$cnt->img7;
$image8=$cnt->img8;
$image9=$cnt->img9;
$image10=$cnt->img10;
$image11=$cnt->img11;
$image12=$cnt->img12;

$guidetext1=$cnt->prompt1; 
$guidetext2=$cnt->prompt2; 
$guidetext3=$cnt->prompt3; 
$guidetext4=$cnt->prompt4; 
$guidetext5=$cnt->prompt5; 
$guidetext6=$cnt->prompt6;
$guidetext7=$cnt->prompt7;
$guidetext8=$cnt->prompt8;
$guidetext9=$cnt->prompt9;
$guidetext10=$cnt->prompt10;
$guidetext11=$cnt->prompt11;
$guidetext12=$cnt->prompt12;

if($userid==NULL)$userid=$USER->id;
 
if($vmode==='view')
  {
  $changemode='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/bloggenerator.php?vmode=edit&cntid='.$cntid.'">🎨 Edit</a>';
  $textareas.='<table align=left width=100%><tr><td valign=top width=3%><img src=https://jandi-box.com/files-profile/660c3c24516e85cef1277816d9565677?size=80>  </td><td><br>블로그 컨텐츠 파이프라인을 통해 생성된 글들은 온라인으로 잠재고객들에게 전파됩니다 ! '.$changemode.'</td></tr></table><hr><table align=center width=100% height=10%> ';
  if (!empty($guidetext1))$textareas.='<tr><td valign=top>'.$image1.'[그림1]<br>[설명1] '.$guidetext1.'<hr> </td></tr>';
  if (!empty($guidetext2))$textareas.='<tr><td valign=top>'.$image2.'[그림2]<br>[설명2] '.$guidetext2.'<hr> </td></tr>';
  if (!empty($guidetext3))$textareas.='<tr><td valign=top>'.$image3.'[그림3]<br>[설명3] '.$guidetext3.'<hr> </td></tr>';
  if (!empty($guidetext4))$textareas.='<tr><td valign=top>'.$image4.'[그림4]<br>[설명4] '.$guidetext4.'<hr> </td></tr>';
  if (!empty($guidetext5))$textareas.='<tr><td valign=top>'.$image5.'[그림5]<br>[설명5] '.$guidetext5.'<hr> </td></tr>';
  if (!empty($guidetext6))$textareas.='<tr><td valign=top>'.$image6.'[그림6]<br>[설명6] '.$guidetext6.'<hr> </td></tr>';
  if (!empty($guidetext7))$textareas.='<tr><td valign=top>'.$image7.'[그림7]<br>[설명7] '.$guidetext7.'<hr> </td></tr>';
  if (!empty($guidetext8))$textareas.='<tr><td valign=top>'.$image8.'[그림8]<br>[설명8] '.$guidetext8.'<hr> </td></tr>';
  if (!empty($guidetext9))$textareas.='<tr><td valign=top>'.$image9.'[그림9]<br>[설명9] '.$guidetext9.'<hr> </td></tr>';
  if (!empty($guidetext10))$textareas.='<tr><td valign=top>'.$image10.'[그림10]<br>[설명10] '.$guidetext10.'<hr> </td></tr>';
  if (!empty($guidetext11))$textareas.='<tr><td valign=top>'.$image11.'[그림11]<br>[설명11] '.$guidetext11.'<hr> </td></tr>';
  if (!empty($guidetext12))$textareas.='<tr><td valign=top>'.$image12.'[그림12]<br>[설명12] '.$guidetext12.'<hr> </td></tr>';


  if (!empty($guidetext1))$textareas2.='[그림1]<br>[설명1]'.$guidetext1.'<br>';
  if (!empty($guidetext2))$textareas2.='[그림2]<br>[설명2]'.$guidetext2.'<br>';
  if (!empty($guidetext3))$textareas2.='[그림3]<br>[설명3]'.$guidetext3.'<br>';
  if (!empty($guidetext4))$textareas2.='[그림4]<br>[설명4]'.$guidetext4.'<br>';
  if (!empty($guidetext5))$textareas2.='[그림5]<br>[설명5]'.$guidetext5.'<br>';
  if (!empty($guidetext6))$textareas2.='[그림6]<br>[설명6]'.$guidetext6.'<br>';
  if (!empty($guidetext7))$textareas2.='[그림7]<br>[설명7]'.$guidetext7.'<br>';
  if (!empty($guidetext8))$textareas2.='[그림8]<br>[설명8]'.$guidetext8.'<br>';
  if (!empty($guidetext9))$textareas2.='[그림9]<br>[설명9]'.$guidetext9.'<br>';
  if (!empty($guidetext10))$textareas2.='[그림10]<br>[설명10]'.$guidetext10.'<br>';
  if (!empty($guidetext11))$textareas2.='[그림11]<br>[설명11]'.$guidetext11.'<br>';
  if (!empty($guidetext12))$textareas2.='[그림12]<br>[설명12]'.$guidetext12.'<br>';
 
  $textareas.='</table>';  
  }  
else 
  {
  $changemode='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/bloggenerator.php?vmode=view&cntid='.$cntid.'">🧐 Read</a>';
  $textareas.='<table align=left width=100%><tr><td valign=top width=3%><br><img src=https://jandi-box.com/files-profile/660c3c24516e85cef1277816d9565677?size=80>  </td><td> 블로그 컨텐츠 파이프라인을 통해 생성된 글들은 온라인으로 잠재고객들에게 전파됩니다 ! '.$changemode.'</td></tr></table><hr><table align=left width=100% height=10%> 
  <tr><td><textarea id="guidetext1">'.$guidetext1.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea1">'.$image1.'</textarea></td></tr>
  <tr><td><textarea id="guidetext2">'.$guidetext2.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea2">'.$image2.'</textarea></td></tr>
  <tr><td><textarea id="guidetext3">'.$guidetext3.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea3">'.$image3.'</textarea></td></tr>
  <tr><td><textarea id="guidetext4">'.$guidetext4.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea4">'.$image4.'</textarea></td></tr>';

  //if($image4!==NULL || $guidetext4!==NULL)
  $textareas.='
  <tr><td><textarea id="guidetext5">'.$guidetext5.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea5">'.$image5.'</textarea></td></tr> 
  <tr><td><textarea id="guidetext6">'.$guidetext6.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea6">'.$image6.'</textarea></td></tr>
  <tr><td><textarea id="guidetext7">'.$guidetext7.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea7">'.$image7.'</textarea></td></tr>
  <tr><td><textarea id="guidetext8">'.$guidetext8.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea8">'.$image8.'</textarea></td></tr>';
 
  //if($image8!==NULL ||$guidetext8!==NULL)
  $textareas.='
  <tr><td><textarea id="guidetext9">'.$guidetext9.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea9">'.$image9.'</textarea></td></tr>
  <tr><td><textarea id="guidetext10">'.$guidetext10.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea10">'.$image10.'</textarea></td></tr>
  <tr><td><textarea id="guidetext11">'.$guidetext11.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea11">'.$image11.'</textarea></td></tr>
  <tr><td><textarea id="guidetext12">'.$guidetext12.'</textarea></td><td valign=top width-70^=%><textarea id="mytextarea12">'.$image12.'</textarea></td></tr>';
 
  $textareas.='</table><table align=center><tr><td> <button onclick="saveContent(3,\''.$userid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>';
  }
 
  $copytoclipboard='<hr><table align=center width=100%> 
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-aCcpWn3Qj"target="_blank">수학공부 챌린지 블로그</a></td></tr>
 
  <tr><td style="font-size:14px;" align=center><hr><a href="https://chatgpt.com/g/g-JoRxRKhm3"target="_blank">교육철학 블로그</a></td></tr>
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-REpDnjhGh"target="_blank">수학교수법 블로그(수정)</a></td></tr>
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-qN8HE2zFZ"target="_blank">습관교정 챌린지</a></td></tr>
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-pSvNBQKvo"target="_blank">예실성전 활용법</a></td></tr>

  <tr><td style="font-size:14px;" align=center><hr><a href="https://chatgpt.com/g/g-d3THUD9of"target="_blank">사용방법 블로그</a></td></tr>
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-N66c9G0ha"target="_blank">필기분석 블로그</a></td></tr>
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-REpDnjhGh"target="_blank">지면평가 블로그</a> (V)</td></tr>

  <tr><td style="font-size:14px;" align=center><hr><a href="https://chatgpt.com/g/g-CpF7pdAef"target="_blank">학부모 체험수기</a></td></tr>
  <tr><td style="font-size:14px;" align=center><a href="https://chatgpt.com/g/g-Z6m03T6DX"target="_blank">KTM 챌린지 자동화 글쓰기</a></td></tr>
  <tr><td style="font-size:14px;" align=center><hr><a href="https://blog.naver.com/esuai02/223497851487"target="_blank">(blog) 챌린지 블로그 바로가기</a></td></tr>
  <tr><td style="font-size:16px;" align=center><hr></td></tr><tr><td class="copyable" style="font-size:10px;background-color:lightgreen;">'.$textareas2.'</td></tr>
  </table>';
$treview=$timecreated-604800*4;
$blogcnts=$DB->get_records_sql("SELECT * FROM mdl_abessi_blog where timecreated > '$treview' AND status NOT LIKE 'hidden'  ORDER BY id DESC LIMIT 30");  
 
$result = json_decode(json_encode($blogcnts), True);
unset($value); 
foreach($result as $value)
	{
  $bloglist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/bloggenerator.php?cntid='.$value['id'].'&vmode=view">제목 : '.$value['title'].'</a> <br> ('.date('Y-m-d H:i:s', $value['timecreated']).')<hr></td></tr>';
  } 
$bloglist='<br><br><br><br><table align=center><tr><td style="font-size:20px;">글목록<br></td></tr>'.$bloglist.'<tr><td>'.$copytoclipboard.'</td></tr></table>';

echo '<!DOCTYPE html>
<html> 
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
<table width=95% align=center><tr><td width=10% valign=top>'.$bloglist.'</td><td width=5%></td><td width=85% valign=top>
'.$textareas.'</td></tr></table>
  <script>

  function saveContent(Eventid,Userid,Cntid)
    {
    var editor1 = tinymce.get("mytextarea1");   
    var htmlContent1 = editor1.getContent();
 
    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
      
    var editor3 = tinymce.get("mytextarea3");   
    var htmlContent3 = editor3.getContent();
      
    var editor4 = tinymce.get("mytextarea4");   
    var htmlContent4 = editor4.getContent();
  
    var editor5 = tinymce.get("mytextarea5");   
    var htmlContent5 = editor5.getContent();
   
    var editor6 = tinymce.get("mytextarea6");
    var htmlContent6 = editor6.getContent();

    var editor7 = tinymce.get("mytextarea7");
    var htmlContent7 = editor7.getContent();

    var editor8 = tinymce.get("mytextarea8");
    var htmlContent8 = editor8.getContent();

    var editor9 = tinymce.get("mytextarea9");
    var htmlContent9 = editor9.getContent();

    var editor10 = tinymce.get("mytextarea10");
    var htmlContent10 = editor10.getContent();

    var editor11 = tinymce.get("mytextarea11");
    var htmlContent11 = editor11.getContent();

    var editor12 = tinymce.get("mytextarea12");
    var htmlContent12 = editor12.getContent();


    var prompteditor1 = tinymce.get("guidetext1");   
    var htmlPrompt1 = prompteditor1.getContent();
  
    var prompteditor2 = tinymce.get("guidetext2");   
    var htmlPrompt2 = prompteditor2.getContent();
      
    var prompteditor3 = tinymce.get("guidetext3");   
    var htmlPrompt3 = prompteditor3.getContent();
      
    var prompteditor4 = tinymce.get("guidetext4");   
    var htmlPrompt4 = prompteditor4.getContent();
 
    var prompteditor5 = tinymce.get("guidetext5");   
    var htmlPrompt5 = prompteditor5.getContent();
 
    var prompteditor6 = tinymce.get("guidetext6");
    var htmlPrompt6 = prompteditor6.getContent();

    var prompteditor7 = tinymce.get("guidetext7");
    var htmlPrompt7 = prompteditor7.getContent();

    var prompteditor8 = tinymce.get("guidetext8");
    var htmlPrompt8 = prompteditor8.getContent();

    var prompteditor9 = tinymce.get("guidetext9");
    var htmlPrompt9 = prompteditor9.getContent();

    var prompteditor10 = tinymce.get("guidetext10");
    var htmlPrompt10 = prompteditor10.getContent();
   
    var prompteditor11 = tinymce.get("guidetext11");
    var htmlPrompt11 = prompteditor11.getContent();
   
    var prompteditor12 = tinymce.get("guidetext12");
    var htmlPrompt12 = prompteditor12.getContent();
  
    
        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "userid":Userid,		
              "cntid":Cntid,		

              "image1":htmlContent1, 
              "image2":htmlContent2, 
              "image3":htmlContent3, 
              "image4":htmlContent4, 
              "image5":htmlContent5, 
              "image6":htmlContent6,
              "image7":htmlContent7,
              "image8":htmlContent8,
              "image9":htmlContent9,
              "image10":htmlContent10,
              "image11":htmlContent11,
              "image12":htmlContent12,

              "guidetext1":htmlPrompt1, 
              "guidetext2":htmlPrompt2,
              "guidetext3":htmlPrompt3,
              "guidetext4":htmlPrompt4,
              "guidetext5":htmlPrompt5,
              "guidetext6":htmlPrompt6,
              "guidetext7":htmlPrompt7,
              "guidetext8":htmlPrompt8,
              "guidetext9":htmlPrompt9,
              "guidetext10":htmlPrompt10,
              "guidetext11":htmlPrompt11,
              "guidetext12":htmlPrompt12
            },
            success:function(data){
                    var Cntid=data.cntid;
                   
                    swal(Cntid, "저장되었습니다.", {buttons: false,timer: 2000});
                    setTimeout(function(){location.reload();} , 100); 
                    }
             })
    }
 
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
  </script>
  
  <script> 
  function copyHtmlToClipboard(html) {
      var tempDiv = document.createElement("div");
      tempDiv.innerHTML = html; // HTML을 텍스트로 변환하지 않고 직접 할당
      var tempInput = document.createElement("input");
      tempInput.style = "position: absolute; left: -1000px; top: -1000px";
      document.body.appendChild(tempInput);
      tempInput.value = tempDiv.textContent || tempDiv.innerText; // HTML 내용을 순수 텍스트로 추출
      tempInput.select();
      document.execCommand("copy");
      document.body.removeChild(tempInput);
  }
  
  document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll("td.copyable").forEach(td => {
          td.addEventListener("click", () => {
              copyHtmlToClipboard(td.innerHTML); // innerHTML을 사용하여 HTML 내용을 복사
              swal("복사되었습니다.", {buttons: false,timer: 500});          });
      });
  });
  </script>
</body>
</html> 

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';
 
echo '<script>   
document.getElementById("audio_upload").onclick = function ()
{  
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "audio/*"
    var object = null;
    var Contentsid= \''.$cntid.'\'; 

    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
        var formData = new FormData();
        formData.append("audio", file);
        formData.append("contentsid", Contentsid); 
        
        $.ajax({
            url: "file.php",
            type: "POST",
            cache: false,
            contentType: false,
            processData: false,
            data: formData,
            success: function (data, status, xhr) 
            {
                var parsed_data = JSON.parse(data);
                // View.createAudioObject와 같은 오디오 객체를 생성하는 새 함수가 필요합니다.
                // 이 예에서는 object 변수의 할당을 단순화했습니다.
                object = parsed_data; // 오디오 객체 생성 로직에 맞게 수정 필요
                if (object)
                {
                    // 오디오 객체 처리 로직
                }
            }
        })
    }
    input.click();

}


</script>
 
';

?>
