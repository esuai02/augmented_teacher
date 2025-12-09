<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$cnttype=$_GET['type'];
$namestr=$_GET['name'];

//$cnttype='question';  //solution, topic
//$namestr='MXM1FC03 LS'; // 01,02,03, 04
$timecreated=time();
echo '시작합니다. <hr>';
if($cnttype==='question' && $namestr!=NULL)
    {
    $cntbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE name LIKE '%$namestr%' AND mathexpression IS NULL ");   
    //$cntbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE  (id=521352 OR id=521353) AND mathexpression IS NULL ");  
    $result = json_decode(json_encode($cntbank), True);
  
    unset($value);
    foreach($result as $value)
        {
        $qid=$value['id'];
		$qtext=$value['questiontext'];
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
        $exist=$DB->get_record_sql("SELECT * FROM mdl_question WHERE questiontext LIKE '%$imgSrc%' AND mathexpression IS NOT NULL ORDER BY id DESC LIMIT 1");  
        if($exist->id==NULL)
            {
            $command = "python sendimagefile.py " . escapeshellarg($imgSrc);
            $output = shell_exec($command);
            $result = json_decode($output, true);  // JSON 문자열을 배열로 변환
            $convertedinfo = $result['text'];              
            }
        else $convertedinfo = $exist->mathexpression.'_'; 
    
        echo $qid.' | ';

        $record = new stdClass();
        $record->id = $qid;
        $record->mathexpression = $convertedinfo;
        $record->timemodified = $timecreated;
        $DB->update_record('question', $record);
        }
    }
elseif($cnttype==='solution')
    {
    
   // $cntbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE name LIKE '%$namestr%' AND ans1 IS NULL ORDER BY id DESC LIMIT 200");  
   $cntbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE name LIKE '%$namestr%' AND ans1 IS NULL ORDER BY id");   
    //$cntbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE  id=520608  AND ans1 IS NULL ");  
    $result = json_decode(json_encode($cntbank), True);
    
    unset($value);
    foreach($result as $value)
        {
        $qid=$value['id'];
        $generalfeedback=$value['generalfeedback'];
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
            if((strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'HintIMG')!= false) && strpos($imgSrc, 'https://mathking.kr/Contents/hintimages') === false)break;
            }
        $exit=$DB->get_record_sql("SELECT * FROM mdl_question WHERE generalfeedback LIKE '%$imgSrc%' AND ans1 IS NOT NULL ORDER BY id DESC LIMIT 1");  
        if($exit->id==NULL)
            {
            $command = "python sendimagefile.py " . escapeshellarg($imgSrc);
            $output = shell_exec($command);
            $result = json_decode($output, true);  // JSON 문자열을 배열로 변환
            $convertedinfo = $result['text'];              
            }
        else $convertedinfo = $exit->ans1.'_'; 
    
        echo $qid.' | ';

        $record = new stdClass();
        $record->id = $qid;
        $record->ans1 = $convertedinfo;
        $record->timemodified = $timecreated;
        $DB->update_record('question', $record);
        }
    }
elseif($cnttype==='topic')
    {
    $cntbank=$DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE title LIKE '%$namestr%' AND (mathexpression IS NULL OR ans1 IS NULL) ");   
    unset($value);
    foreach($result as $value)
        {
         $cntpageid=$value['id'];
     
        }
    }
    echo '완료하였습니다. <hr>';


 
?>