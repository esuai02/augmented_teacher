<?php
if($contentstype==1)$checkimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
elseif($contentstype==2)$checkimg=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id ='$contentsid' ");

if($checkimg->maintext==NULL && $contentstype==1)  //&& strpos($wboardidc, 'MATRIX')!= false
    {
    $ctext=$checkimg->pageicontent;
    $htmlDom = new DOMDocument; 
    @$htmlDom->loadHTML($ctext);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $extractedImages = array();
    $nimg=0;
    foreach($imageTags as $imageTag)
        {
        $nimg++;
        $imgSrc = $imageTag->getAttribute('src');break;
        if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'Contents')!= false)break;
        }
     
    $command = "python ../LLM/sendimagefile.py " . escapeshellarg($imgSrc);
    $mathoutput = shell_exec($command);
    $mathresult = json_decode($mathoutput, true);  // JSON 문자열을 배열로 변환
    $convertedinfo = $mathresult['text'];                        

    $record = new stdClass();
    $record->id = $contentsid;
    $record->maintext = $convertedinfo;
    $record->timemodified = $timecreated;
    $DB->update_record('icontent_pages', $record);
    } 
elseif($checkimg->reflections==NULL && $contentstype==1)
    {  
    $gptprompt=$checkimg->maintext.'을 이해했는지 평가하기 위한 성찰질문 3가지 생성. 번호 붙이고 HTML 서술평가 형식으로';
    //$gptprompt='이해했는지 평가하기 위한 성찰질문 3가지 생성. 질문마다 줄바꿈';
    $gptcommand = "python3.10 ../LLM/sendgptinput.py " . escapeshellarg($gptprompt);
    $gptoutput = shell_exec($gptcommand);
    // Decode the JSON output
    $gptresponse = json_decode($gptoutput, true);
    $gptresult = $gptresponse['result'];

    $record = new stdClass();
    $record->id = $contentsid;
    $record->reflections =$gptresult;
    $record->timemodified = $timecreated;
    $DB->update_record('icontent_pages', $record);   
    } 
elseif($checkimg->mathexpression==NULL && $contentstype==2)
    {
    $qid=$checkimg->id;
    $qtext=$checkimg->questiontext;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($qtext);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $extractedImages = array();
    
    $nimg=0;
    foreach($imageTags as $imageTag)
        {
        $nimg++;
        $imgSrc = $imageTag->getAttribute('src');
        //$imgSrc = str_replace(' ', '%20', $imgSrc); 
        if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
        }
    //echo $imgSrc;
    $command = "python ../LLM/sendimagefile.py " . escapeshellarg($imgSrc);
    $mathoutput = shell_exec($command);
    $mathresult = json_decode($mathoutput, true);  // JSON 문자열을 배열로 변환
    $convertedinfo = $mathresult['text'];              
        
    $record = new stdClass();
    $record->id = $qid;
    $record->mathexpression = $convertedinfo;
    $record->timemodified = $timecreated;
    $DB->update_record('question', $record);
    }
elseif($checkimg->ans1==NULL && $contentstype==2)
    {
    $qid=$checkimg->id;
    $generalfeedback=$checkimg->generalfeedback;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($generalfeedback);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $extractedImages = array();

    $nimg=0;
    foreach($imageTags as $imageTag)
        {
        $nimg++;
        $imgSrc = $imageTag->getAttribute('src');
        //$imgSrc = str_replace(' ', '%20', $imgSrc); 
        if((strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'HintIMG')!= false) && strpos($imgSrc, 'hintimages') === false)break;
        }
    $command = "python ../LLM/sendimagefile.py " . escapeshellarg($imgSrc);
    $mathoutput = shell_exec($command);
    $mathresult = json_decode($mathoutput, true);  // JSON 문자열을 배열로 변환
    $convertedinfo = $mathresult['text'];                        

    $record = new stdClass();
    $record->id = $qid;
    $record->ans1 = $convertedinfo;
    $record->timemodified = $timecreated;
    $DB->update_record('question', $record);
    }
elseif($checkimg->reflections1==NULL && $contentstype==2)
    {
    $qid=$checkimg->id;
   
    //$prompt='hello';
     // Decode the JSON output
   

    $gptprompt='1. 문제 :'.$checkimg->mathexpression.' . 2. 해설지 :'.$checkimg->ans1.'. 를 토대로 성찰질문 3가지를 만들어 주세요. ';
 
    $gptcommand = "python3.10 ../LLM/sendgptinput.py " . escapeshellarg($gptprompt);
     
    $gptoutput = shell_exec($gptcommand);
  
    $gptresponse = json_decode($gptoutput, true);
    $gptresult = $gptresponse['result']; 
 
    $record = new stdClass();
    $record->id = $qid;
    $record->reflections1 = $gptresult;
    $record->timemodified = $timecreated;
    $DB->update_record('question', $record);
    }
?>