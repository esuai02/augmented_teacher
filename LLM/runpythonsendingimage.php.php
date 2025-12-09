<?php
// 파이썬 파일 실행

$imageurl = $_POST['imageurl'];
#$output = shell_exec('sendimagefile.py');

#$output = shell_exec('python sendimagefile.py');

 
$command = "python sendimagefile.py " . escapeshellarg($imageurl);
$output = shell_exec($command);
# 수식인식 결과를 gpt에 보내고 결과를 $analysisresult로 넘기기
$analysisresult='';
 
echo json_encode( array("output" =>$analysisresult) );
?>