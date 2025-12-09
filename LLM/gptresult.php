<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$logid = $_GET["logid"];
 
$solutionlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_solutionlog WHERE id='$logid' ORDER BY id DESC LIMIT 1");

echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body> <table width=100%><tr><td valign=top>
'.$solutionlog->mathexpression.'<hr>'.$solutionlog->gptresult.'</td></tr></table> 
<script type="text/x-mathjax-config">
MathJax.Hub.Config({
  tex2jax: {
    inlineMath:[ ["$","$"], ["\\[","\\]"] ],
   // displayMath: [ ["$","$"], ["\\[","\\]"] ]
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
function saveContent(Eventid,Cntid)
  {
    var editor = tinymce.get("mytextarea");   
    var htmlContent = editor.getContent();
    var NewHtml = htmlContent;    
   // NewHtml = NewHtml.replace(/(<([^>]+)>)/ig,"");

    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
    var NewHtml2 = htmlContent2;    
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
            },
            success:function(data){
                    var Cntid2=data.cntid;
                    swal("OK !", "저장되었습니다.", {buttons: false,timer: 100});
                    setTimeout(function(){location.reload();} , 100); 
                    }
             })
}
 
</script>
 
';

?>
