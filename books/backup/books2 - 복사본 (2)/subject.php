<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$cid=$_GET["cid"]; 
$chnum=$_GET["nch"]; 
$mode=$_GET["mode"]; 
$domain=$_GET["domain"]; 
$studentid=$_GET["studentid"]; 
$timecreated=time();
$checkitem='d'.$domain.'cid'.$cid.'ch'.$chnum;

if($studentid==NULL)$studentid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$userinfo= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$username=$userinfo->firstname.$userinfo->lastname;

include("domainstrategy.php");

if($role==='student')echo ' <head><title>'.$username.' 마인드맵 ✿</title></head><body>';
else echo ' <head><title>마인드맵 ✿</title></head><body>';
if($mode==='domain')
	{ 
	$chlist=$DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$domain'  ");
	$domaintitle=$chlist->title;
	$chapnum=$chlist->chnum;

	for($nch=1;$nch<=$chapnum;$nch++)
		{
		$cidstr='cid'.$nch; 
		$chstr='nch'.$nch;
		$cid2=$chlist->$cidstr;
		$nchapter=$chlist->$chstr;

		$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid2'  ");
		$chname='ch'.$nchapter;
		$title=$curri->$chname;
	
		$title=$curri->$chname;
		
 		if($cid==$cid2 && $nchapter==$chnum)
			{
			$cntstr='cnt'.$nchapter;
			$checklistid=$curri->$cntstr;
			$wboardid='obsnote'.$cid2.'_ch'.$nchapter.'_user'.$studentid;
			$notetitle='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$username.'</a>의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&cid='.$cid2.'&nch='.$nchapter.'&mode=map">개념집착</a> : '.$domaintitle;
			$obsnotelist.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>'.$title.'</b> </a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=fix&domain='.$domain.'"><img style="margin-bottom:8px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667755172.png width=20></a></td></tr>';
			}
		else $obsnotelist.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=domain&domain='.$domain.'">'.$title.'</a></span> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=fix&domain='.$domain.'"><img style="margin-bottom:8px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667755172.png width=20></a></td></tr>';
		}
	echo '<table align=center>
	<tr><td width=78% valign=top><iframe style="border: 1px none; z-index:2; width:78vw; height:95vh;  margin-left:-0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_online.php?id='.$wboardid.'&studentid='.$studentid.'" ></iframe></td><td width=2%></td><td valign=top width=20%><table align=center><tr><td><img src="https://mathking.kr/Contents/IMAGES/mindmap.png" width=200></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&domain='.$domain.'&mode=note&cid='.$cid.'&nch='.$chnum.'"><img src="https://mathking.kr/Contents/IMAGES/playicon.png" width=50></a></td></tr></table><br>'.$notetitle.'  <hr><table width=100%>'.$obsnotelist.'<tr><td><hr></td></tr><tr><td>[ 학습단계 ]</td></tr>'.$dmprinciples.'<tr><td width=22vw><img src=http://ojsfile.ohmynews.com/STD_IMG_FILE/2015/0307/IE001806909_STD.jpg width=200></td></tr><tr><td>기억에 접속해 보세요</td></tr></table></td></tr></table>';
	}
elseif($mode==='fix')//https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667753928.png
	{
	$chlist=$DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$domain'  ");
	$domaintitle=$chlist->title;
	$chapnum=$chlist->chnum;

	for($nch=1;$nch<=$chapnum;$nch++)
		{
		$cidstr='cid'.$nch;
		$chstr='nch'.$nch;
		$cid2=$chlist->$cidstr;
		$nchapter=$chlist->$chstr;

		$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid2'  ");
		$chname='ch'.$nchapter;
		$title=$curri->$chname;
		
 		if($cid==$cid2 && $nchapter==$chnum)
			{
			$wboardid='fixnote'.$cid2.'_ch'.$nchapter.'_user'.$studentid;
			$notetitle='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$username.'</a>의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&cid='.$cid2.'&nch='.$nchapter.'&mode=fixnote">실수기록</a> : '.$domaintitle;
			$obsnotelist.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span><b>'.$nch.' '.$title.'</span></b> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=domain&domain='.$domain.'"><img style="margin-bottom:8px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667530116.png width=20></a></td></tr>';
			}
		else $obsnotelist.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=fix&domain='.$domain.'">'.$title.'</a></span> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=domain&domain='.$domain.'"><img style="margin-bottom:8px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667530116.png width=20></a></td></tr>';
		}
	echo '<table align=center><tr><td width=78%><iframe style="border: 1px none; z-index:2; width:78vw; height:100vh;  margin-left:-0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_online.php?id='.$wboardid.'&studentid='.$studentid.'" ></iframe></td><td width=2%></td><td valign=top width=20%><table align=center><tr><td align=center><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667756184.png" width=190></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&domain='.$domain.'&mode=note&cid='.$cid.'&nch='.$chnum.'"><img src="https://mathking.kr/Contents/IMAGES/playicon.png" width=50></a></td></tr></table><br>'.$notetitle.'  <hr><table>'.$obsnotelist.'<tr><td width=22vw><img src=http://ojsfile.ohmynews.com/STD_IMG_FILE/2015/0307/IE001806909_STD.jpg width=200></td></tr><tr><td>기억에 접속해 보세요</td></tr></table></td></tr></table>';
	}
else // 과목별 
	{
	$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
	$subjectname=$curri->name;
	$chapnum=$curri->nch;
	$notetitle=$username.'의 기억을';
	for($nch=1;$nch<=$chapnum;$nch++)
		{
		$chname='ch'.$nch;
		$title=$curri->$chname;
		
 		if($nch==$chnum)
			{
			$cntstr='cnt'.$nch;
			$checklistid=$curri->$cntstr;
			$obsnotelist.='<tr><td><b>'.$nch.'<a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>'.$title.'</b> </a> </b></td></tr>';
			$wboardid='obsnote'.$cid.'_ch'.$chnum.'_user'.$studentid;
			}
		else $obsnotelist.='<tr><td><span>'.$nch.' <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid.'&nch='.$nch.'&studentid='.$studentid.'">'.$title.'</a></span></td></tr>';
		}
	echo '<table align=center><tr><td width=78%><iframe style="border: 1px none; z-index:2; width:78vw; height:100vh;  margin-left:-0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_online.php?id='.$wboardid.'&studentid='.$studentid.'" ></iframe></td><td width=2%></td><td valign=top width=20%><br><br><br>'.$notetitle.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=subject&cid='.$cid.'&nch='.$chnum.'">Play</a> <hr><table>'.$obsnotelist.'<tr><td width=22vw><img src=http://ojsfile.ohmynews.com/STD_IMG_FILE/2015/0307/IE001806909_STD.jpg width=200></td></tr><tr><td>기억에 접속해 보세요</td></tr></table></td></tr></table>';
	}

echo '	
	<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';
?>
