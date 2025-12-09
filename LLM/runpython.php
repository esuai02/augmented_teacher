<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$ltype=$_POST['ltype'];
$imageurl = $_POST['imageurl'];
$wboardid = $_POST['wboardid'];
$contentstype= $_POST['contentstype'];
$contentsid= $_POST['contentsid'];
$studentid = $_POST['studentid'];

$timecreated=time();
if($wboardid!=NULL) 
    {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
    $sql_strokes = "SELECT * FROM boarddb where encryption_id='$wboardid' AND shape_data LIKE '%pencil_begin%' ";
    //$sql_strokes = "SELECT * FROM boarddb where encryption_id='$wboardid'";
    $rs_strokes = mysqli_query($conn, $sql_strokes);
    
    $shape_data = array();
    // sql query data binding
    while ($info = mysqli_fetch_array($rs_strokes)) {
        $shape_data[] = json_decode($info['shape_data'], true);
    }
    
    $strokes = [
        "strokes" => [
            "x" => [],
            "y" => []
        ]
    ];
    
    foreach ($shape_data as $strokeData) {
        $x = [];
        $y = [];
    
        $x_last = null;
        $y_last = null;
    
        foreach ($strokeData as $command) {
            if (strpos($command, "line") === 0) {
                $coords = explode(" ", substr($command, 5));
                $x1 = intval(floatval($coords[0]));
                $y1 = intval(floatval($coords[1]));
                $x2 = intval(floatval($coords[2]));
                $y2 = intval(floatval($coords[3]));
    
                if ($x_last !== null && $y_last !== null) {
                    if ($x1 !== $x_last || $y1 !== $y_last) {
                        $x[] = $x1;
                        $y[] = $y1;
                    }
                }
    
                $x[] = $x2;
                $y[] = $y2;
    
                $x_last = $x2;
                $y_last = $y2;
            }
        }
    
        if (!empty($x)) {
            $strokes["strokes"]["x"][] = $x;
        }
    
        if (!empty($y)) {
            $strokes["strokes"]["y"][] = $y;
        }
    }
    $image = imagecreatetruecolor(1200, 1200);
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    foreach ($strokes["strokes"]["x"] as $index => $x_values) {
        $y_values = $strokes["strokes"]["y"][$index];
        for ($i = 1; $i < count($x_values); $i++) {
            imageline($image, 2*$x_values[$i-1], 2*$y_values[$i-1], 2*$x_values[$i], 2*$y_values[$i], $black);
        }
    }
   
    $jsonData = json_encode($strokes, JSON_PRETTY_PRINT);
    $command = "python sendstrokes.py " . escapeshellarg($jsonData);
    /*
    if($ltype==='questions')
        { 
        $image = imagecreatetruecolor(2400, 2400);
        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        foreach ($strokes["strokes"]["x"] as $index => $x_values) {
            $y_values = $strokes["strokes"]["y"][$index];
            for ($i = 1; $i < count($x_values); $i++) {
                imageline($image, 2*$x_values[$i-1], 2*$y_values[$i-1], 2*$x_values[$i], 2*$y_values[$i], $black);
            }
        }
        $imagefilename='usr'.$USER->id.'_'.$timecreated.'.png';
        imagepng($image, './imagefolder/'.$imagefilename);
        imagedestroy($image);
        $imageurl='https://mathking.kr/moodle/local/augmented_teacher/LLM/imagefolder/'.$imagefilename;
        $command = "python sendimagefile.py " . escapeshellarg($imageurl);
        }
    else 
        {
        $jsonData = json_encode($strokes, JSON_PRETTY_PRINT);
        $command = "python sendstrokes.py " . escapeshellarg($jsonData);
        }
    */
    }
else
    {
    $command = "python sendimagefile.py " . escapeshellarg($imageurl);
    }
$output = shell_exec($command);
$result = json_decode($output, true);  // JSON 문자열을 배열로 변환

$convertedinfo = $result['text']; 

if($contentstype==1) // 개념
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
    $cnttext1=$cnttext->maintext;  
    $reflections=$cnttext->reflections;  
    
    if($ltype==='questions')
        {
        $prompt='다음은 개념을 설명한 내용입니다 : '.$cnttext1.'. 이를 이해했는지 점검하기 위한 3가지 성찰질문 '.$reflections.'와 같이 주어졌고 이에 대한 다음의 답변을 평가해 주세요. 제출된 답안 : ('.$convertedinfo.'). 답안을 평가해 주세요. 각문항별로 빠짐없이 정확하게 설명이 되었는지를 중점적으로 평가해주세요. A+,A,B+,B,C+,C,D+,D,F 중 하나를 선택해주세요. ';
        //$prompt='다음은 수학의 개념을 설명한 내용입니다 : '.$cnttext1.'. 핵심 주제 3가지를 선정해주세요. 제출된 답안 : ('.$convertedinfo.'). 답안을 3가지 주제 3가지를 빠짐없이 정확하게 설명이 되었는지를 중점적으로 평가해주세요. letter ratings을 부여. ';
        $title='기억인출 평가';
        //$showthis=$convertedinfo; include("../showthis.php");
        }
    elseif(strpos($wboardid, 'retrievalNote')!==false)
        {
        $prompt=' 제출된 답안은 ('.$convertedinfo.')입니다. 제출된 설명을 표시하고 평가기준은  '.$cnttext1.'. 입니다. 일치성을 %로 표시해 주세요.';
        //$prompt='다음은 수학의 개념을 설명한 내용입니다 : '.$cnttext1.'. 핵심 주제 3가지를 선정해주세요.  제출된 답안 : ('.$convertedinfo.'). 답안을 3가지 주제 3가지를 빠짐없이 정확하게 설명이 되었는지를 중점적으로 평가해주세요. letter ratings을 부여. ';
        $title='기억인출 평가...';
        //$showthis=$title.$convertedinfo; include("../showthis.php");
        }
    }
elseif($contentstype==2) // 문제
    {
    // 개인화된 피드백 
    $qstn=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id ='$contentsid' ");
    $qstntext=$qstn->mathexpression;  $soltext=$qstntext->ans1;
    $description=$qstn->description;
    
    if(strpos($wboardid, 'Q7MQFA')!==false )
        {
        $prompt='(1. 문제내용 :'.$qstntext.' 2. 교과 단원 내용 : '.$description.'.    저는 학생입니다. 저의 풀이는 ('.$convertedinfo.')입니다. 다음에 무엇을 해야할 지 모르겠어요. 힌트 하나만 주세요. 수식은 변환된 latex양식을 사용해 주세요. 글자수 30자 제한 ';
        $title='힌트 혹은 함정';
        }
    elseif(strpos($wboardid, 'nx4HQkXq')!==false )
        {
        //문제 : '.$qstntext.'.
        // $prompt=' 해설지 :  '.$soltext.' . 학생답변 :'.$convertedinfo.'. 해설지를 토대로 학생답변을 평가해 주세요.학생이 제시한 답변에 대해, 그의 논리적인 이유 제시와 사실에 기반한 구체적인 내용을 기술했는지 여부를 평가에 포함해주세요. 학생풀이 내용을 언급해주세요. ';
        //$prompt='# 문제 :'.$qstntext.' . # 학생답변 :'.$convertedinfo.'. 문제를 토대로 학생답변을 평가해 주세요.학생이 제시한 답변에 대해, 그의 논리적인 이유 제시와 사실에 기반한 구체적인 내용을 기술했는지 여부를 평가에 포함해주세요. 손글씨를 필기인식한 내용이므로 숫자나 기호가 잘못인식되었을 가능성을 고려하여 실제 학생이 논리적인 풀이를 만들었는지를 중심으로 평가해 주세요. 학생의 풀이는 해설지와 다른 유형의 풀이를 시도했을 수 있어 중간과정이 다를 수도 있습니다. 이를 고려하여 해설지 내용 ('.$soltext.')을 부분적으로 참고해 주세요.마지막으로 풀이의 순서와 형식을 교정할 수 있도록 의견을 주세요. 최종평가를 #순서 (합격/불합격), #논리 (합격/불합격), #정답 (일치/불일치) 의 형식으로 나타내주세요. 마무리는 추천명언으로 해주세요. 명언 앞에는 <hr>을 넣어주세요.';
        #$prompt='가장 먼저 해설지의 내용('.$soltext.')을 요약해 주세요. 다음으로 # 문제 :'.$qstntext.' . # 학생답변 :'.$convertedinfo.'. 문제를 토대로 학생답변을 평가해 주세요.학생이 제시한 답변에 대해, 그의 논리적인 이유 제시와 사실에 기반한 구체적인 내용을 기술했는지 여부를 평가에 포함해주세요. GPT는 수학적 오류를 쉽게 발생시킵니다. 따라서 해설지 내용을 중심으로 모순점을 제거한 다음 글을 써주세요.30자. 마무리는 추천명언으로 해주세요. 명언 앞에는 <hr>을 넣어주세요. ';
        $prompt='#해설지 : '.$soltext.'. # 학생답변 :'.$convertedinfo.'. 해설지의 답과 학생 답변을 분석하여 학생의 답이 해설지와 일치하는지 체크해 주세요. 마무리는 추천명언으로 해주세요. 명언 앞에는 <hr>을 넣어주세요. ';
        $title='서술평가 결과';
        }
    }
//else $prompt='다음은 수학문제의 풀이 또는 해설입니다. 부연설명을 해주세요 '.$convertedinfo;

$command2 = "python3.10 sendgptinput.py " . escapeshellarg($prompt);
$output2 = shell_exec($command2);
 
// Decode the JSON output
$result2 = json_decode($output2, true);
$gptresult = $convertedinfo.'<hr>'.$result2['result'];

if($wboardid!=NULL) 
    {
    $record = new stdClass();
    $record->userid = $studentid;
    $record->wboardid = $wboardid;
    $record->mathexpression = $convertedinfo;
    $record->gptresult = $gptresult;
    $record->timecreated = $timecreated;
    $DB->insert_record('abessi_solutionlog', $record);
    }
$solutionlog=$DB->get_record_sql("SELECT id FROM mdl_abessi_solutionlog WHERE wboardid='$wboardid' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
$logid=$solutionlog->id;
echo json_encode( array("logid" =>$logid,"title" =>$title,) );
//echo json_encode( array("outputtext" =>$gptresult) );
//$conn->close();
?>