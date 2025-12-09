<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$studentid = $_GET["studentid"];
$wboardid = $_GET["wboardid"];
$print = 1;

if($cnttype==1)
{
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $eventid=1;
    $maintext=$cnttext->maintext;
    $cntstr=$cnttext->reflections1;
}
elseif($cnttype==2)
{
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $guidetext=$cnttext->mathexpression;
    $maintext=$cnttext->ans1;
    $cntstr=$cnttext->reflections1;
    $eventid=2; 
}

// 쇼츠 링크까지 포함하도록 정규식 수정
preg_match_all('/(https:\/\/www\.youtube\.com\/watch\?v=[\w-]+(?:&\S*)?|https:\/\/youtu\.be\/[\w-]+(?:\?\S*)?|https:\/\/www\.youtube\.com\/shorts\/[\w-]+)/', $cntstr, $matches);

$youtubeLinks = array_unique($matches[0]); // 중복된 링크 제거

$movieinterface = '    <table style="width: 100%; text-align: center; margin: 0 auto;"><tr>' . PHP_EOL;

foreach ($youtubeLinks as $link) {
    // 비디오 ID 추출( watch / youtu.be / shorts )
    $videoId = '';
    if (preg_match('/watch\?v=([\w-]+)/', $link, $idMatch)) {
        $videoId = $idMatch[1];
    } elseif (preg_match('/youtu\.be\/([\w-]+)/', $link, $idMatch)) {
        $videoId = $idMatch[1];
    } elseif (preg_match('/shorts\/([\w-]+)/', $link, $idMatch)) {
        $videoId = $idMatch[1];
    }

    // 시작 시간 추출
    $start = '';
    if (strpos($link, '?') !== false) {
        parse_str(parse_url($link, PHP_URL_QUERY), $queryParams);
        if (isset($queryParams['t'])) {
            $start = '?start=' . $queryParams['t'];
        } elseif (isset($queryParams['si']) && isset($queryParams['t'])) {
            $start = '?start=' . $queryParams['t'];
        }
    }

    // iframe 코드 생성
    $movieinterface .= '        <td>' . PHP_EOL;
    $movieinterface .= '            <iframe src="https://www.youtube.com/embed/' . $videoId . $start . '" style="width: 400px; height: 300px;" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>' . PHP_EOL;
    $movieinterface .= '        </td>' . PHP_EOL;
}

$movieinterface .= ' </tr>   </table>' . PHP_EOL;

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
<title>동영상 보기</title>
  
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
    justify-content: center;
    align-items: center;
    width:80vw;
    height: 100vh;
    margin: 0;
    background-color: #f0f0f0;
}
table {
    border-collapse: collapse;
    width: 50%;
    background-color: white;
}
td {
    padding: 10px;
    text-align: center;
}
iframe {
    width: 400px;
    height: 315px;
}
</style>

</head> 
<body><div class="header">
'.$movieinterface.'
</div>
</body>
</html> 
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
</script>

<script>   
function saveContent(Eventid,Cntid)
{
    var editor = tinymce.get("mytextarea");   
    var htmlContent = editor.getContent();
    var NewHtml = htmlContent;    

    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
    var NewHtml2 = htmlContent2;    

    var editor3 = tinymce.get("mytextarea3");   
    var htmlContent3 = editor3.getContent();
    var NewHtml3 = htmlContent3;    

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

<style>
a[href]:after { content: none !important; }
@media print {
  .no-print {
      display: none;
  }
}
</style>';
?>
