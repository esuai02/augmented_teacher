 <?php 
 /////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$timecreated=time();
$adayago=$timecreated-604800;
$teacherid = $_GET["id"];

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
  
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE contentstype=2 AND student_check=1 AND tlaststroke > '$adayago' ORDER BY id DESC LIMIT 300");

$result = json_decode(json_encode($handwriting), True);
unset($value);
 
foreach($result as $value) 
	{
	$userid=$value['userid'];
	$contentsid=$value['contentsid'];
	$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$stdname=$thisuser->firstname.$thisuser->lastname;
    if(strpos($thisuser->firstname,$tsymbol) !== false)
		{
		$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
			}
		$questiontext='<img loading="lazy" src="'.$questionimg.'" width=500>';


		$papertest.=$stdname.$questiontext.'............<hr>';
		}
	else continue;
	}
echo '<br><br><br><br><table align=center><tr><td>'.$papertest.'</td></tr></table>';
?>