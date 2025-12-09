<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$timecreated=time(); 
 
$mode = $_GET['mode'];
$wboardid = $_GET['wboardid'];
$contentstype= $_GET['contentstype'];
$contentsid= $_GET['contentsid'];
$studentid = $_GET['studentid']; 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

$recordlog=$DB->get_records_sql("SELECT * FROM mdl_abessi_solutionlog WHERE  wboardid LIKE '$wboardid'  ORDER BY timecreated DESC LIMIT 30 ");
  
$result = json_decode(json_encode($recordlog), True);
unset($value);
$npresent=1;
foreach($result as $value) 
    { 
    $timestamp=round((time()-$value['timecreated'])/3600,1);
    $bgcolor='';
    if($npresent==1)$bgcolor='background-color:#FFE5E1;';
    $recordlist.='<tr style="'.$bgcolor.'"><td style="word-break: break-all;overflow-wrap: break-word;" valign=top><hr>'.$timestamp.'시간 전</td><td style="word-break: break-all;overflow-wrap: break-word;"  valign=top><hr>'.$value['mathexpression'].'</td><td  valign=top><hr>'.$value['gptresult'].'</td></tr>';    
    $npresent++;
    }

if($contentstype==1)
    { 
    $getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
    $ctext=$getimg->pageicontent;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctext);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $extractedImages = array();
    $nimg=0;
    foreach($imageTags as $imageTag)
        {
        $nimg++;
        $imgSrc = $imageTag->getAttribute('src');
        $imgSrc = str_replace(' ', '%20', $imgSrc); 
        if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
        }
    echo '<hr><table align=center width=90%><tr><td width=10%>생성시간</td><td width=45% valign=top><img src="'.$imgSrc.'" width=90%></td><td width=45% valign=top>'.$getimg->reflections.'</td></tr>'.$recordlist.'</table>';
    }
elseif($contentstype==2)
    {
    $qtext = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1 ");
    $htmlDom1 = new DOMDocument;@$htmlDom1->loadHTML($qtext->generalfeedback); $imageTags1 = $htmlDom1->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags1 as $imageTag1)
        {
        $nimg++; $imgSrc1 = $imageTag1->getAttribute('src'); $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
        if(strpos($imgSrc1, 'MATRIX/MATH')!= false && strpos($imgSrc1, 'hintimages')==false)break;
        }
    $htmlDom2 = new DOMDocument;@$htmlDom2->loadHTML($qtext->questiontext); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags2 as $imageTag2)
        {
        $nimg++; $imgSrc2 = $imageTag2->getAttribute('src'); $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
        if(strpos($imgSrc2, 'hintimages')!= true && (strpos($imgSrc2, '.png')!= false || strpos($imgSrc2, '.jpg')!= false))break;
        }
    echo '<hr><table align=center width=90%><tr><th width=5%>생성시간</th><th width=45% valign=top><img src="'.$imgSrc2.'" width=90%></th><th width=45% valign=top><img src="'.$imgSrc1.'" width=90%></th></tr>'.$recordlist.'</table>';
    }
if($USER->id==2)echo '<hr><table align=center>
    <tr><td># 음성인식 결과를 교정하여 출력. 중요한 부분 진한 글씨로 표시. (선생님은 단지 발표를 텍스트로 처리하는 것만 사용)</td></tr>
    <tr><td># 이전 발표로부터 개선된 부분 확인. 또는 선생님의 추가요청를 잘 수행했는지 여부를 자동으로 확인 또는 진하게 표시</td></tr>
    <tr><td></td></tr>
    <tr><td></td></tr>
    </table>'; 
?>