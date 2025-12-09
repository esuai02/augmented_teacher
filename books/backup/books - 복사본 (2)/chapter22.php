<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$cid=$_GET["cid"]; 
$chnum=$_GET["nch"]; 
$type=$_GET["type"]; 
$mode=$_GET["mode"]; 
$stage=$_GET["stage"]; // 개념, 중급노트, 유형, 심화노트, 심화, 내신T, 수능, 경시
$thiscntid=$_GET["cntid"]; 
$studentid=$_GET["studentid"]; 
if($studentid==NULL)$studentid=$USER->id;
$timecreated=time(); 
$halfdayago=time()-43200;
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$chnum0=$chnum;
if($cid==106)$cid0=59;
elseif($cid==107)$cid0=60;
$lastchapter=$DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
if($cid==NULL)$cid=$lastchapter->cid;

if($mode==NULL || $mode==='default')
	{
	$modechange='mode=review&';
	$modeinfo='';
	$modetext='복습선택';
	}
elseif($mode==='review') 
	{
	$modetext='공부시작';

	$modechange='';
	$modeinfo='mode=review&';
	}

//$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id ='$studentid' ");
if($USER->id==$studentid)include("../message.php");

$indic= $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");

$createmode=$indic->aistep; 
if($createmode==7)$cmodeimg='createcontents';
else $cmodeimg='timefolding';
$checklog=$DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid' AND cid='$cid' ORDER BY id DESC LIMIT 1 ");
if($type==='init' && $checklog->id!=NULL)
	{
	$chnum=$checklog->nch;
	$thiscntid=$checklog->cntid;
	}
$timeback=$timecreated-43200;
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1 ");
if($checkgoal->id!=NULL)$todaygoal='🕸️ '.$checkgoal->text;
else $todaygoal='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=1491"> 목표 입력하기 </a>';

$lstyle=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='90'  ORDER BY id DESC LIMIT 1"); 
$learningstyle=$lstyle->data;
if($thiscntid==NULL)$thiscntid=0;

if($USER->id==$studentid)$DB->execute("INSERT INTO {abessi_chapterlog} (userid,cid,nch,cntid,timecreated) VALUES('$studentid','$cid','$chnum','$thiscntid','$timecreated')");
	
//include("gpttalk.php");
	
for($ndm=120;$ndm<=136;$ndm++)
	{
	$dminfo= $DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$ndm' ");
	for($ncid=1;$ncid<=20;$ncid++)
		{
		$cidstr='cid'.$ncid;
		if($cid0==$dminfo->$cidstr)
			{
			$nchstr='nch'.$ncid;
			if($dminfo->$nchstr==$chnum)
				{
				$domain=$ndm;
				break 2;
				}
			}
		else continue;
		}
	}

if($studentid==NULL)$studentid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($role==='student')
	{	
	echo ' <head><title>'.$studentname.' 개념노트</title></head><body>'; 	
	}
else 
	{

	echo ' <head><title>개념노트</title></head><body>';
	}

// 시계 아이콘 버튼 추가
echo '<button id="callbackButton" class="btn btn-primary" title="알림 설정" style="position: fixed; top: 0; right: 10px; z-index: 1000; padding: 10px 15px; font-weight: bold; border-radius: 0 0 15px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); cursor: pointer; background-color: #007bff; border: none;"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></button>';

$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
if($curri->id>=80 && $curri->id<=94)$dmn='science';
else $dmn='math';
$ankisbjt=$curri->sbjt;
$domainname=$curri->subject;
$subjectname=$curri->name;
$chapnum=$curri->nch;

$chaptertitle='<a style="font-size:20px;text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">'.$studentname.'</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$studentid.'"><img style="margin-bottom:10px;" src=https://mathking.kr/Contents/IMAGES/pomodorologo.png width=40></a>';
//
for($nch=1;$nch<=$chapnum;$nch++)
	{
	$chname='ch'.$nch;
	$title=$curri->$chname;
	$qid='qid'.$nch;
	$qid=$curri->$qid;
	if($title==NULL)continue;
	$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$qid'  ");
	$attemptlog=$DB->get_record_sql("SELECT id,quiz,sumgrades,attempt,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$timefinish=date("m/d | H:i",$attemptlog->timefinish);  
	$quiz=$DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'  ");
	$quizgrade=round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
	$quizresult='';
	if($quizgrade!=NULL)$quizresult='<span style="color:lightgrey;">'.$quizgrade.'점 ('.$attemptlog->attempt.'회)</span>';
	
	if($nch==$chnum)
		{ 
		$thischtitle=$curri->$chname;
		$cntstr='cnt'.$nch;
		$checklistid=$curri->$cntstr;
		
		$ankilink='<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$dmn.'&sbjt='.$ankisbjt.'&studentid='.$studentid.'&nch='.$nch.'"><img src="https://ankiweb.net/logo.png" width=20></a>';
		$gptstr='gpt'.$nch;
		$gpturl=$curri->$gptstr;

		$chapterlist.='<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>'.$title.'</b> </a> '.$quizresult.'  '.$ankilink.'</td></tr>';
		$wboardid='obsnote'.$cid.'_ch'.$chnum.'_user'.$studentid;
		}
	else $chapterlist.='<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid='.$cid.'&nch='.$nch.'&studentid='.$studentid.'">'.$title.'</a>'.$quizresult.'</td></tr>';
	}
 
$chlist=$DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$domain'  ");
$domaintitle=$chlist->title;
$chapnum=$chlist->chnum;

for($nch=1;$nch<=$chapnum;$nch++) 
	{
	$cidstr='cid'.$nch;  
	$chstr='nch'.$nch;
	$cid2=$chlist->$cidstr;
	$nchapter=$chlist->$chstr;
	echo 'ktm123';
	$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid2'  ");
	$chname='ch'.$nchapter;
	$title=$curri->$chname;

	$qid='qid'.$nch;
	$qid=$curri->$qid;

	$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$qid'  ");
	$attemptlog=$DB->get_record_sql("SELECT id,quiz,sumgrades,attempt,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$timefinish=date("m/d | H:i",$attemptlog->timefinish);  
	$quiz=$DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'  ");
	$quizgrade=round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
	$quizresult='';
	if($quizgrade!=NULL)$quizresult='<span style="color:lightgrey;">'.$quizgrade.'점 ('.$attemptlog->attempt.'회)</span>';
	
	
	if($cid==$cid2 && $nchapter==$chnum) 
		{
		$cntstr='cnt'.$nchapter;
		$checklistid=$curri->$cntstr;
		$wboardid='obsnote'.$cid2.'_ch'.$nchapter.'_user'.$studentid;
		$notetitle='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&cid='.$cid2.'&nch='.$nchapter.'&mode=map"target="_blank">개념집착</a> : '.$domaintitle;
		$obsnotelist.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>'.$title.'</b> </a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=fix&domain='.$domain.'"target="_blank"><img  style="margin-bottom:8px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667755172.png width=20></a></td></tr>';
		} 
	else $obsnotelist.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' <a style="color:#a9aab0;" href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'"target="_blank">'.$title.'</a></span> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=domain&domain='.$domain.'"target="_blank"><img  style="margin-bottom:8px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667755172.png width=20></a></td></tr>';	
	}
$domchapters='<table width=100%>'.$obsnotelist.'<tr><td><hr></td></tr>'.$dmprinciples.'</table>';

$contextid='introcid'.$cid.'ch'.$chnum;
include("gptrecord.php");

$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1 "); 
if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_gptultratalk} (creator,role,gpttalk,contextid,context,url,status,timecreated) VALUES('$USER->id','$role','','$contextid','$context','$url','connected','$timecreated')");

$gpteventname='단원도입';
$defaulttalk='이 단원은 <b>'.$thischtitle.'</b>입니다. <br>시작하기 전 필요한 이전 과정 내용들에 대한 <b>기억상태를 점검</b>해 보고 필요한 경우 간단한 <b>복습</b>을 진행하는 것을 권장합니다. <br><br># 복습방법을 선택하는데 어려움이 있으시다면 선생님과 대화해 주세요 ~<br>';
$chapterintro='<a href="https://mathking.kr/moodle/local/augmented_teacher/books/edit.php?cntid='.$gptlog->id.'"target="_blank"><img   style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt.png width=18></a> '.$defaulttalk.'<hr>'.$gpttalk;	


$chklist=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$checklistid' ORDER BY id DESC LIMIT 1");
$topics=$DB->get_records_sql("SELECT * FROM mdl_checklist_item where checklist='$chklist->instance' ORDER BY position ASC   ");  //AND  title NOT LIKE '%Approach%' 
$result = json_decode(json_encode($topics), True);

$ntopic=1;$nchk=0;$npassed=0;$nstage=0; $nanki=0;$topicchosen=0;
 
unset($value);
foreach($result as $value)
	{
	$chkitemid=$value['id']; 
	$checkstatus='';
	$nview=0;

	$chkitem=$DB->get_record_sql("SELECT usertimestamp FROM mdl_checklist_check where item='$chkitemid' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
	$classname='collapse';

	if($chkitem->usertimestamp>1)
		{
		$checkstatus="checked";
		$npassed++;
		$classname='collapse';
		} 
 
	$ncolap=$value['position']; 
	$linkurl=$value['linkurl']; 
	$displaytext=$value['displaytext']; 
	$thismenutext=$displaytext;
	if(strpos($displaytext, '마무리')!= false)$displaytext='단원 마무리 T: '.$thischtitle;
	$url_components = parse_url($linkurl);
	parse_str($url_components['query'], $params);

	$scriptontopic=$value['script'];


	$quizresult='';
	$retrieval='';
	$setgoal=' <span onclick="setGoal(\''.$displaytext.'\');">➕</span>';
	//$setgoal = '<span onclick="setGoal(' . json_encode($displaytext) . ');">➕</span>';
	if(strpos($linkurl, 'icontent')!= false)
		{
		$nchk++;
		if(($chkitem->usertimestamp==0 || $chkitem->usertimestamp==NULL) && $topicchosen==0)
			{
			$classname='collapse show';
			$topicchosen++;
			$gmset=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages_rating WHERE  userid LIKE '$studentid' AND status='begin' AND timemodified > '$halfdayago' ORDER BY id DESC LIMIT 1");
			if($gmset->id!=NULL && $gmset->timemodified < time()-1200 && $lmode->data==='능동')
			{
				echo '<script>
				document.addEventListener("DOMContentLoaded", function() {
					Swal.fire({
						html: `<iframe src="https://mathking.kr/moodle/local/augmented_teacher/books/inspiregrowth.php?userid='.$studentid.'" style="width:100%; height:90vh; border:0;"></iframe>`,
						width: "90vw",
						height: "85vh",
						customClass: {
							popup: "swal-maximized"
						}, 
						showCloseButton: false,
						scrollbarPadding: false,
						showConfirmButton: false,
					});
				});
				</script>';
				}
			}
		
		$cntid=$params['id']; 
		$quizid=$params['quizid'];
 		$todaystr = date("Y");
	
		$contextid='cid'.$cid.'nch'.$chnum.'cmid'.$cntid;
		$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1 "); 
		if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_gptultratalk} (creator,role,gpttalk,contextid,context,url,status,timecreated) VALUES('$USER->id','$role','','$contextid','$context','$url','connected','$timecreated')");

		$wboard_retrieval='retrievalNote_'.$todaystr.'topic'.$cntid.'_user'.$studentid;
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid ='$cntid' "); // 전자책에서 가져오기
		$getpagenum=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid ='$cntid' ORDER BY pagenum DESC LIMIT 1");  
		$pagenum=$getpagenum->pagenum;
		$gameurl=$getimg->gameurl;
		if($USER->id==2)$ankicntid=$getimg->id;
		$topicwbid='jnrsorksqcrark'.$getimg->id.'_user'.$studentid;
		$papertest='';
		
		if($USER->id==13 ||$USER->id==2) // 추후제거
			{
			$DB->execute("UPDATE {icontent_pages} SET chapter='$chnum', ntopic='$ntopic'  WHERE cmid ='$cntid' ");	
			}  

		$noteurl='cid='.$cid.'&nch='.$chnum.'&cmid='.$cntid.'&page=1&quizid='.$quizid;
		if($gameurl==NULL)$gameurl='<a style="font-size:18px;color:lightgray;" href="https://mathking.kr/moodle/local/augmented_teacher/books/addgames.php?cntid='.$cntid.'"target="_blank"> ꙰</a>';
		else $gameurl='<a  style="font-size:18px;color:red;" href="'.$gameurl.'?fullscreen=true"target="_blank"> ꙰</a>';
		$retrieval=' <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_retrieval.php?id='.$wboard_retrieval.'&contentsid='.$getimg->id.'&topicname='.$displaytext.'&cid='.$cid.'&nch='.$chnum.'&cntid='.$cntid.'&page=1&studentid='.$studentid.'"target="_blank"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/IMAGES/createnote.png" width=18></a>  '.$gameurl.' ('.$pagenum.')'.$papertest;
		include("gptrecord.php");
		$gpteventname='주제개요';

		$ctext=$getimg->pageicontent;
		 $ctitle=$getimg->title;
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
		
		$defaulttalk='<img  loading="lazy" src='.$imgSrc.' width=800px>';
		$scriptontopic=$defaulttalk;	
 
		if(strpos($displaytext, '노트_중급')!==false)
			{
			$cmid; 
			$notetitle='지면평가'; 
			$retrieval='_<a href="https://mathking.kr/moodle/local/augmented_teacher/students/examplenote.php?userid='.$studentid.'&cntid='.$cntid.'&title='.$notetitle.'"target="_blank"><b>'.$notetitle.'</b></a> ';
			$notewbid='keytopic3'.$cntid.'_user'.$studentid;
			$exist=$DB->get_record_sql("SELECT id,timecreated FROM mdl_abessi_messages WHERE  wboardid LIKE '$notewbid' ORDER BY id DESC LIMIT 1");
			if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_messages} (wboardid,userid,userto,userrole,status,active,contentstitle,contentsid,timemodified,timecreated) VALUES('$notewbid','$studentid','2','student','examplenote','1','$notetitle','$cntid','$timecreated','$timecreated')");
			elseif($exist->timecreated <$timecreated-43200) $DB->execute("UPDATE {abessi_messages} SET timecreated='$timecreated'  WHERE wboardid='$notewbid' ");
			$scriptontopic='<img  loading="lazy" src="https://mathking.kr/Contents/IMAGES/mathnote1.jpg" width=40%><br><br>공부 후  <b>심화수업 바로가기</b>의 <b>보강학습 중급</b>을 응시해 주세요.<br>';
			}
		elseif(strpos($displaytext, '노트_심화')!== false)
			{
			$notewbid='keytopic4'.$cntid.'_user'.$studentid;
			$notetitle='지면평가'; 
			$retrieval='_<a href="https://mathking.kr/moodle/local/augmented_teacher/students/examplenote.php?userid='.$studentid.'&cntid='.$cntid.'&title='.$notetitle.'"target="_blank"><b>'.$notetitle.'</b></a> ';
			$exist=$DB->get_record_sql("SELECT id,timecreated FROM mdl_abessi_messages WHERE  wboardid LIKE '$notewbid' ORDER BY id DESC LIMIT 1");
			if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_messages} (wboardid,userid,userto,userrole,status,active,contentstitle,contentsid,timemodified,timecreated) VALUES('$notewbid','$studentid','2','student','examplenote','1','$notetitle','$cntid','$timecreated','$timecreated')");
			elseif($exist->timecreated <$timecreated-43200) $DB->execute("UPDATE {abessi_messages} SET timecreated='$timecreated'  WHERE wboardid='$notewbid' ");
			$scriptontopic='<img  loading="lazy" src="https://mathking.kr/Contents/IMAGES/mathnote2.jpg" width=40%><br><br>공부 후 하단의  <b>심화수업 바로가기</b>의 <b>보강학습 심화</b>를 응시해 주세요.<br>';
			}

		
		if(strpos($displaytext, '도약')!= false )
			{			
			if($checkstatus==='checked')$nstage=0;  
			$nview=1;
			//if($thiscntid==$cntid)$classname='collapse show';
			$todoitem='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$chnum.'&cmid='.$cntid.'&page=1&studentid='.$studentid.'&quizid='.$quizid.'"><button  class="stylish-button">NEXT</button></a> ';
			 
			}
		elseif(strpos($displaytext, '유형')!==false)
			{						
			if($checkstatus==='checked')$nstage=2;  
			$nview=1;
			//if($thiscntid==$cntid)$classname='collapse show';
			$todoitem='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/KeyPatternsGame.php?&cid='.$cid.'&cntid='.$cntid.'&studentid='.$studentid.'"><img  loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$chnum.'&cmid='.$cntid.'&page=1&studentid='.$studentid.'&quizid='.$quizid.'"><button  class="stylish-button">서술평가 준비</button></a> &nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_test.php?dmn='.$domain.'&cid='.$cid.'&nch='.$chnum.'&cmid='.$cntid.'&page=1&studentid='.$studentid.'&quizid='.$quizid.'">✏ 서술평가</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/smartguide/index.php?dmn='.$domain.'&cid='.$cid.'&nch='.$chnum.'&cntid='.$cntid.'&page=1&studentid='.$studentid.'&quizid='.$quizid.'">✏ 시간조절</a>';
			}
 
		$ntopic++;
					
		}
	elseif(strpos($displaytext, '정복')!= false && $learningstyle!=='도제')
		{
		if(($chkitem->usertimestamp==0 || $chkitem->usertimestamp==NULL) && $topicchosen==0){$classname='collapse show';$topicchosen++;}
		$nchk++;
		$nview=1;
		$quizid=$params['id'];
		$contextid='quiz'.$quizid;
		$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$quizid'  ");
		$attemptlog=$DB->get_record_sql("SELECT id,quiz,attempt,sumgrades,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
		$timefinish=date("m/d | H:i",$attemptlog->timefinish);  
		$quiz=$DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'  ");
		$quizgrade=round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
		//include("gptrecord.php");
		$gpteventname='유형정복';
		$topicwbid='quiz_'.$quizid.'_user'.$studentid;
		
		$noteurl='id='.$quizid;
		//if($role!=='student')$papertest='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span onClick="ChangeCheckBox(216,\''.$studentid.'\',\''.$quizid.'\',\''.$topicwbid.'\',\''.$noteurl.'\')">출제</span>';	 	

		$defaulttalk='<img  loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/exercise.png" width=60%><br><br>시작하기 전 <b>대표유형 문항들을 여러 번 풀고 숙달</b>시켜 주세요<br>';
 		
		$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1 "); 
		if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_gptultratalk} (creator,role,gpttalk,contextid,context,url,status,timecreated) VALUES('$USER->id','$role','','$contextid','$context','$url','connected','$timecreated')");
		
		$scriptontopic=$defaulttalk;
					
		$displaytext='<b> '.$displaytext.'</b>';
		$displaytext =$displaytext.' <img  loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944443001.png width=18>';

		$quizresult=$quizgrade.'점 ('.$timefinish.' | '.$attemptlog->attempt.'회)'.$papertest;
		 
		$todoitem='<img   loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic2.png width=20> <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'"target="_blank"><button  class="stylish-button">NEXT</button></a> (랜덤 2문항 | 8분 | 3연속 100점 통과)';
		}
	elseif(strpos($displaytext, '마무리')!= false && $learningstyle!=='도제')
		{
		$nchk++;
		$nview=1;
		$quizid=$params['id'];
		if(($chkitem->usertimestamp==0 || $chkitem->usertimestamp==NULL) && $topicchosen==0){$classname='collapse show';$topicchosen++;}
		if($checkstatus==='checked')$nstage=1;	
		$displaytext='단원 마무리 T : '.$thischtitle;
		$displaytext ='<span style="font-size:18px;color:#1F55F7;"><b>'.$displaytext.'</b></span> <img  loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944443001.png width=18>';


		$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$quizid'  ");
		$attemptlog=$DB->get_record_sql("SELECT id,quiz,sumgrades,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
		$timefinish=date("m/d | H:i",$attemptlog->timefinish);  
		$quiz=$DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'  ");
		$quizgrade=round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
	 
		$topicwbid='quiz_'.$quizid.'_user'.$studentid;
		$noteurl='id='.$quizid;
		//if($role!=='student')$papertest='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span onClick="ChangeCheckBox(216,\''.$studentid.'\',\''.$quizid.'\',\''.$topicwbid.'\',\''.$noteurl.'\')">출제</span>';//ChangeCheckBox(140016,310,\''.$studentid.'\',this.checked)


		$quizresult=$quizgrade.'점 ('.$timefinish.' | '.$attemptlog->attempt.'회)'.$papertest;
		$contextid='quiz'.$quizid;
		//include("gptrecord.php");
		$gpteventname='단원마무리';

		$defaulttalk='<img  loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/ilovemath.png" width=60%><br><br>시작하기 전 <b>유형정복 문제들을 모두 통과</b>해 주세요<br>';

		if(strpos($linkurl, 'checklist')!= false)$finishingchapterquiz='<a href="'.$linkurl.'"target="_blank"><button  class="stylish-button">NEXT</button></a>'; 
		else $finishingchapterquiz='<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'"target="_blank"><button  class="stylish-button">NEXT</button></a>';
		$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1 "); 
		if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_gptultratalk} (creator,role,gpttalk,contextid,context,url,status,timecreated) VALUES('$USER->id','$role','','$contextid','$context','$url','connected','$timecreated')");
		$scriptontopic=$defaulttalk;
	 
		$todoitem='<img   loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic3.png width=20> '.$finishingchapterquiz.' (90점 통과 | 50분)'; 
			
		} 
	elseif(strpos($displaytext, '심유')!= false && strpos($linkurl, 'checklist')!= false  )
		{
		$nchk++;
		$nview=1;
		$cntid=$params['id']; 
		if($checkstatus==='checked')$nstage=3;
		$displaytext ='<span style="font-size:18px;color:#000000;"><b>'.$displaytext.'</b></span> <img  loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944443001.png width=18>';
		if(($chkitem->usertimestamp==0 || $chkitem->usertimestamp==NULL) && $topicchosen==0){$classname='collapse show';$topicchosen++;}
		$scriptontopic='<img  loading="lazy" src="https://mathking.kr/Contents/IMAGES/keypatterns.png" width=40%><br><br><table><tr><td>핵심유형 훈련은  <b>심화학습을 시작하기 전 유형에 대한 능숙도를 향상시키는 것</b>이 목적입니다.</td></tr><tr><td><br><br> </td></tr>
		<tr><td>루틴1 - 순서대로 진행</td></tr>
		<tr><td>루틴2 - Check Test부터 진행</td></tr>
		<tr><td>루틴3 - Check Test + 핵심유형 재시도 + Review Test 진행</td></tr>
		<tr><td>루틴4 - Check Test + Review Test 진행</td></tr>
		<tr><td>루틴5 - Review Test 진행</td></tr>
		</table><br>';

		$todoitem='<img   loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic3.png width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cntid.'&studentid='.$studentid.'"target="_blank"><button  class="stylish-button">NEXT</button></a>';
		} 
	elseif(strpos($displaytext, '화수업')!= false && strpos($linkurl, 'checklist')!= false  )
		{
		$nchk++;
		$nview=1;
		$cntid=$params['id']; 
		if($checkstatus==='checked')$nstage=5;
		$displaytext ='<span style="font-size:18px;color:#000000;"><b>'.$displaytext.'</b></span> <img  loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944443001.png width=18>';
		if(($chkitem->usertimestamp==0 || $chkitem->usertimestamp==NULL) && $topicchosen==0){$classname='collapse show';$topicchosen++;}
		$scriptontopic='<img  loading="lazy" src="https://mathking.kr/Contents/IMAGES/advancedmathcourse.jpg" width=50%><br><br>심화수업은  <b>단원별 심화인증시험</b>을 통과하는 것을 목표로 나에게 맞는 코스를 설계하여 공부할 수 있습니다.<br>';

		$todoitem='<img   loading="lazy" style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic3.png width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cntid.'&studentid='.$studentid.'"target="_blank"><button  class="stylish-button">NEXT</button></a>';
		}
	else continue;
	$personaBtn = '';
	if(strpos($displaytext, '도약') !== false && isset($cntid)) {
		$personaBtn = '<span  style="font-size:30px;float:right; width: 100%;"   onclick="openPersonaPopup(\''.$getimg->id.'\', \''.$studentid.'\');">🎭</span>';
	}
	// 새로 추가: collapse 상태에 따라 링크 클래스를 결정함
	if(strpos($classname, 'show') !== false) {
		$link_class = 'card-link';
	} else {
		$link_class = 'collapsed card-link';
	}
	if($mode==='review')$topiclist.='
	<div class="card"  style="font-size:16;">
	  <div class="card-header" style="white-space: nowrap;">
		<input type="checkbox" name="checkAccount" onClick="addReview(\''.$thismenutext.'\')"/> <a class="'.$link_class.'" style="color:#4287f5;" data-toggle="collapse" href="#collapse'.$ncolap.'"> <span style="color:black;font-size:18;">'.$displaytext.'</span></a>
	  </div>
	  <div id="collapse'.$ncolap.'" class="'.$classname.'" data-parent="#accordion">
		<div class="card-body">
		 '.$scriptontopic.'
		</div>
	  </div>
	</div> ';
	elseif($nview==1)$topiclist.='
				<div class="card"  style="font-size:16;">
				  <div class="card-header" style="white-space: nowrap;">
					<input type="checkbox" name="checkAccount" '.$checkstatus.' onClick="CheckProgress(2,\''.$studentid.'\',\''.$chkitemid.'\', this.checked)"/> <a class="'.$link_class.'" style="color:#4287f5;" data-toggle="collapse" href="#collapse'.$ncolap.'"> <span style="color:black;font-size:18;">'.$displaytext.'</span></a>'.$setgoal.$retrieval.$quizresult.'  
				  </div>
				  <div id="collapse'.$ncolap.'" class="'.$classname.'" data-parent="#accordion">
					<div class="card-body">
					 '.$scriptontopic.'<br> <table><tr><td>'.$todoitem.' </td><td width=10%></td><td>  '.$personaBtn.'</td></tr></table><br>
					</div>
				  </div>
				</div> ';
	}

$progressfilled=round($npassed/$nchk*100,1);

if($progressfilled<20)$bgtype='alert';
elseif($progressfilled<40)$bgtype='info';
elseif($progressfilled<60)$bgtype='primary';
elseif($progressfilled<80)$bgtype='danger';
else $bgtype='success';

if($nstage>=2)$chapterlist.='<tr><td><hr></td><td><hr></td></tr><tr><td> ✔ </td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>중간고사 내신T</b> </a> '.$quizresult.'</td></tr><tr><td> ✔ </td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>기말고사 내신T</b> </a> '.$quizresult.'</td></tr>';

$timefolding='<img style="margin-top:5px;" onclick="ImmersiveSession(4,\''.$studentid.'\',\''.$curri->id.'\',\''.$domain.'\',\''.$chnum.'\',\''.$thiscntid.'\')" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/'.$cmodeimg.'.png" width=40>'; 
 
if($curri->subject==='수학' && ($curri->mtid==7 || $curri->mtid==10))$subjectlist='<div id="tableContainer" style="background-color:#F0F1F4;"> <br>  <table width=100%><tr><td><img style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/createtimefolding.png" width=40>&nbsp;&nbsp; </td><td style="color:black"> 
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=73&nch=1&studentid='.$studentid.'&type=init">초등 4-1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=74&nch=1&studentid='.$studentid.'&type=init">초등 4-2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=75&nch=1&studentid='.$studentid.'&type=init">초등 5-1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=76&nch=1&studentid='.$studentid.'&type=init">초등 5-2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=78&nch=1&studentid='.$studentid.'&type=init">초등 6-1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=79&nch=1&studentid='.$studentid.'&type=init">초등 6-2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=66&nch=1&studentid='.$studentid.'&type=init">중 1-1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=67&nch=1&studentid='.$studentid.'&type=init">중 1-2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=68&nch=1&studentid='.$studentid.'&type=init">중 2-1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=69&nch=1&studentid='.$studentid.'&type=init">중 2-2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=71&nch=1&studentid='.$studentid.'&type=init">중 3-1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=72&nch=1&studentid='.$studentid.'&type=init">중 3-2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=106&nch=1&studentid='.$studentid.'&type=init">공통수학 1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=107&nch=1&studentid='.$studentid.'&type=init">공통수학 2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=59&nch=1&studentid='.$studentid.'&type=init">수 상</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=60&nch=1&studentid='.$studentid.'&type=init">수 하</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=61&nch=1&studentid='.$studentid.'&type=init">수 1</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=62&nch=1&studentid='.$studentid.'&type=init">수 2</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=64&nch=1&studentid='.$studentid.'&type=init">확통</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=63&nch=1&studentid='.$studentid.'&type=init">미적</a> |
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid=65&nch=1&studentid='.$studentid.'&type=init">기하</a></td></tr></table> <br> </div>';
else $subjectlist='<div  style="background-color:#F0F1F4;"> <br>  <table width=100%><tr><td width=5%><img style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/createtimefolding.png" width=40>&nbsp;&nbsp; </td><td style="font-size:20px;color:black">
<a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$curri->cntitem1.'&type=init"target="_blank">보충학습 ###</a>
</td></tr></table> <br> </div>';
 

$dashbordtitle=$chnum.'단원. '.$thischtitle;
if($role!=='student')$alt42game='<a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/games/dashboard.php?cid='.$cid.'&studentid='.$studentid.'&title='.$dashbordtitle.'" target="_blank"><img style="margin-bottom:7px;" src=https://mathking.kr/Contents/IMAGES/joystick.png width=35></a>&nbsp;&nbsp;<a style="font-size:28px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1" accesskey="w">🎙️</a>';

$progressbar='<div class="progress-card">
<div class="demo">
  <div class="progress-card">
	<div class="progress-status"></div>
	<div class="progress" style="background-color:#bdbdbd; height:15px;">
	  <div class="progress-bar progress-bar-striped bg-'.$bgtype.'" role="progressbar" style="width: '.$progressfilled.'%; height: 15px;" aria-valuenow="'.$progressfilled.'" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.$progressfilled.'%"></div>
	</div>
  </div>
</div>
</div>
';

echo '<!DOCTYPE html>
<html>
<head>
  <title>Bootstrap Example</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
  <style>
      * {
      -webkit-user-drag: none; /* Chrome, Safari */
      -moz-user-drag: none;    /* Firefox */
      -ms-user-drag: none;     /* Edge */
      user-drag: none;         /* Standard */
    }

  img {
	user-drag: none; /* for WebKit browsers including Chrome */
	user-select: none; /* for standard-compliant browsers */
	-webkit-user-drag: none; /* for Safari and Chrome */
	-webkit-user-select: none; /* for Safari */
	-moz-user-select: none; /* for Firefox */
	-ms-user-select: none; /* for Internet Explorer/Edge */
  }
  a {
	user-drag: none; /* for WebKit browsers including Chrome */
	user-select: none; /* for standard-compliant browsers */
	-webkit-user-drag: none; /* for Safari and Chrome */
	-webkit-user-select: none; /* for Safari */
	-moz-user-select: none; /* for Firefox */
	-ms-user-select: none; /* for Internet Explorer/Edge */
  }
.stylish-button {
	background-color: #FF69B4; /* 네온 핑크 색상 */
	color: white;
	padding: 5px 5px;
	width:6vw;
	border: none;
	cursor: pointer;
	font-family: "Arial Rounded MT Bold", sans-serif;
	font-size: 16px;
	transition: background-color 0.3s ease;
  }
  
  .stylish-button:hover {
	background-color: #FF1493; /* 색상을 조금 더 진하게 */
  }
  
  .stylish-button:active {
	transform: translateY(2px);
  }
  
  .stylish-button:focus {
	outline: none;
  }

  
  #tableContainer {
	opacity: 0;
	transition: opacity 0.5s ease;
  }
  #tableContainer.active {
	opacity: 1;
  } 
  
.container {
  display: flex;
}

.left-column{
  width: 20%;
  padding: 16px;
}
.right-column {
  width: 80%;
  padding: 0px;
}
    /* Left sidebar */
    .left-sidebar {
      width: 20%;
      height: 100%;
      position: fixed;
      left: 0;
      top: 0;
      background-color: #f1f1f1;
      padding: 20px;
    }

    /* Main body */
    .main-body {
      width: 20%;
      height: 100%;
	  
    } 

    /* Collapsible button */
    .collapsible {
      background-color: #eee;
      color: #444;
      cursor: pointer;
      padding: -0px;
      width: 80%;
      border: none;
      text-align: left;
      outline: none;
      font-family: Arial, sans-serif;
      font-size: 16px;
    }
    .colsection {
      width: 79%;
      height: 100%;
      position: absolute;
      left: 20%;
      top: 0;
      background-color: #f1f1f1;
      padding: 0px;
    }
    /* Add a background color to the button if it is clicked on (add the .active class with JS), and when you move the mouse over it (hover) */
    .active, .collapsible:hover {
      background-color: #ccc;
    }

    /* Style the collapsible content */
    .content {
      padding: 0 18px;
      display: none; 
      overflow: hidden;
      background-color: #f1f1f1;
      font-family: Arial, sans-serif;
      font-size: 16px;
    }
  </style>
 <script>
  // 드래그 이벤트 방지
  document.addEventListener("dragstart", function(e) {
    e.preventDefault();
  });
  // 텍스트 선택 이벤트 방지
  document.addEventListener("selectstart", function(e) {
    e.preventDefault();
  });
</script>

</head>
<body>
 
	<div class="container">
		<div class="left-column">
			<div class="left-sidebar">'.$chaptertitle.'
			<h3><b>▶ '.$subjectname.'</b></h3><br>
			<table>'.$chapterlist.'</table>   
			
			<hr><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modechange.'cid='.$cid.'&nch=1&studentid='.$studentid.'&type=init "> <img style="margin-bottom:5px;" loading="lazy"  src="https://mathking.kr/Contents/IMAGES/learningpath.png" width=20>&nbsp; <span style="font-size:16px;">'.$modetext.'</span></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"><img style="margin-bottom:10px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40></a><br><br>'.$domchapters.' 
			</div>
		</div>
		<div class="right-column">
		<div class="colsection"><br>
		<table width=80%><tr><td width=5%>'.$timefolding.'</td><td><h3> &nbsp;'.$thischtitle.'</h3> </td><td>'.$todaygoal.'</td> <td width=1%><a href="'.$gpturl.'"target="_blank"><img   style="margin-bottom:0px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt.png width=25></a>  </td> <td align=center>'.$alt42game.'</td></tr></table><table><tr><td>( 진행률 : '.$progressfilled.'% ) ... 제목만 보고 내용을 떠올려 보세요. 기억인출은 탁월한 장기기억 효과가 있습니다. <a href="https://m.blog.naver.com/PostView.naver?isHttpsRedirect=true&blogId=mediator79&logNo=221480721220"target="_blank">참고자료</a> '.$progressbar.'</td></tr></table>
			<div id="accordion">
			'.$topiclist.'  
			<p style="background-color:lightgrey;">'.$subjectlist.'<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></p>
			</div>
		 
		</div>
		</div>
	</div>

</body>
</html>
';
 
echo '	
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<script>
document.addEventListener("DOMContentLoaded", function() {
	const tableContainer = document.getElementById("tableContainer");
	
	document.addEventListener("mousemove", function(event) {
	  const rect = tableContainer.getBoundingClientRect();
	  const x = event.clientX, y = event.clientY;

	  if (x > rect.left && x < rect.right && y > rect.top && y < rect.bottom) {
		tableContainer.classList.add("active");
	  } else {
		tableContainer.classList.remove("active");
	  }
	});
  });

 // //(Eventid,Userid,Cid,Domainid,Chapterid,Topicid)
function ImmersiveSession(Eventid,Userid,Cid,Domainid,Chapterid,Topicid)
	{
	var Createmode= \''.$createmode.'\';
	if(Createmode==7)
		{
		swal("독립세션 설계모드가 종료됩니다.", {buttons: false,timer: 2000}); 
		$.ajax({
			url: "check_status.php", 
			type: "POST",
			dataType: "json",
			data : {
					"eventid":Eventid,
					"createmode":Createmode,
					"userid":Userid,       
					"cid":Cid,
					"domainid":Domainid,
					"chapterid":Chapterid,
					"topicid":Topicid,
					},
			success: function (data){  
			}
			});
		setTimeout(function() {location.reload(); },1000);
		}
	else
		{
		swal("독립세션 설계모드가 시작됩니다.", {buttons: false,timer: 2000}); 
		$.ajax({
			url: "check_status.php", 
			type: "POST",
			dataType: "json",
			data : {
					"eventid":Eventid,
					"createmode":Createmode,
					"userid":Userid,       
					"cid":Cid,
					"domainid":Domainid,
					"chapterid":Chapterid,
					"topicid":Topicid,
					},
			success: function (data){  
			}
			});
		setTimeout(function() {location.reload(); },1000);
		}
	}
function ChangeCheckBox(Eventid,Userid,Contentsid,Wboardid,Noteurl)
	{
	swal("출제되었습니다."+Wboardid, {buttons: false,timer: 2000}); 
   	$.ajax({
		url: "../students/check.php",
		type: "POST",
		dataType: "json",
		data : {
				"eventid":Eventid,
				"userid":Userid,    
				"contentsid":Contentsid,   
				"wboardid":Wboardid,
				"noteurl":Noteurl,	
			   },
			success: function (data){  
			}
		});
	}

	function openPersonaPopup(cntid, studentid) {
		Swal.fire({
		   
			html: `<iframe src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/selectpersona.php?cnttype=1&type=contents&cntid=${cntid}&userid=${studentid}" style="width:100%; height:85vh; border:0;" ></iframe>`,
			width: "100vw",
			height: "90vh",
			customClass: {
				popup: "swal-maximized" 
			},
			showCloseButton: false,
			scrollbarPadding: false,
			confirmButtonText: "닫기"
	
		});
	}
	
function setGoal(Inputtext)
	{
	var Userid= \''.$studentid.'\';
	  Swal.fire({
        
        html: `
<button id="weeklyGoal" class="swal2-confirm swal2-styled" style="margin:5px; font-size:20px;">주간목표</button>
<button id="todayGoal" class="swal2-confirm swal2-styled" style="margin:5px; font-size:20px;">오늘목표</button>
<button id="diary" class="swal2-confirm swal2-styled" style="margin:5px; font-size:20px;">수학일기</button>

        `,
        showConfirmButton: false,
        showCancelButton: false, // 취소 버튼 제거
        didOpen: () => {
            const weeklyGoalBtn = Swal.getPopup().querySelector("#weeklyGoal");
            const todayGoalBtn = Swal.getPopup().querySelector("#todayGoal");
            const diaryBtn = Swal.getPopup().querySelector("#diary");

            weeklyGoalBtn.addEventListener("click", () => {
                Swal.close();
                window.open("https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=" + Userid + "&cntinput=" + Inputtext + "&gtype=%EC%A3%BC%EA%B0%84%EB%AA%A9%ED%91%9C");
            
            });

            todayGoalBtn.addEventListener("click", () => {
                Swal.close();
                window.open("https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=" + Userid + "&cntinput=" + Inputtext);
                
            });

            diaryBtn.addEventListener("click", () => {
                Swal.close();
                window.open("https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Userid + "&cntinput=" + Inputtext);
               
            });
        }
    });
	} 


function addReview(Inputtext)
	{
	var Userid= \''.$studentid.'\';
	swal("복습추가", {buttons: false,timer: 300});
    $.ajax({
		url: "../cjnstudents/check_status.php", 
		type: "POST",
		dataType: "json",
		data : {
				"eventid":5,
				"userid":Userid,       
				"inputtext":Inputtext,
			   },
		success: function (data){  
		}
	});
	 
	} 
function dragChatbox(Cntid)
		{
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:750,
		   showClass: {
   		 popup: "animate__animated animate__fadeInRight"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutRight"
		  },
		  html:
		    \'<iframe  class="foo"  style="border: 0px none; z-index:2; width:680; height:100vh;margin-left: -40px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$studentid.'&type=chapter" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
function CheckProgress(Eventid,Userid,Itemid, Checkvalue){
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
	}
	
   $.ajax({
		url: "check_status.php", 
		type: "POST",
		dataType: "json",
		data : {"userid":Userid,       
				"cntid":Itemid,
				"checkimsi":checkimsi,
				"eventid":Eventid,
			   },
		success: function (data){  
		}
	});
	setTimeout(function() {location.reload(); },100);
}	
</script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- 콜백 기능을 위한 스크립트 -->
<script>
// 전역 변수
let activeCallbacks = [];
let currentUserId = '.$studentid.';

// 페이지 로드 시 실행
$(document).ready(function() {
    checkMonitoringStatus();
    
    // 시계 버튼 클릭 이벤트
    $("#callbackButton").click(function() {
        openCallbackModal();
    });
});

// monitoring 상태 확인
function checkMonitoringStatus() {
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "get_callbacks",
            userid: currentUserId
        },
        dataType: "json",
        success: function(response) {
            if (response.success && response.callbacks) {
                // 현재 시간
                const currentTime = Math.floor(Date.now() / 1000);
                
                // monitoring 상태이고 아직 시간이 지나지 않은 콜백 필터링
                activeCallbacks = response.callbacks.filter(callback => {
                    return callback.status === "monitoring" && callback.timefinish > currentTime;
                });
                
                // 시계 아이콘 색상 변경
                if (activeCallbacks.length > 0) {
                    $("#callbackButton").css("background-color", "#dc3545");
                    $("#callbackButton span").css("animation", "pulse-red 2s infinite");
                } else {
                    $("#callbackButton").css("background-color", "#007bff");
                    $("#callbackButton span").css("animation", "none");
                }
            }
        },
        error: function() {
            console.log("콜백 상태 확인 실패");
        }
    });
}

// 전체 알림 설정
function openCallbackModal() {
    // 활성 모니터링이 있는지 확인
    if (activeCallbacks.length > 0) {
        // 가장 최근 콜백 정보 사용
        const latestCallback = activeCallbacks[0];
        const remainingTime = Math.ceil((latestCallback.timefinish - Math.floor(Date.now() / 1000)) / 60);
        
        // 시간 추가 옵션 생성 (10분부터 60분까지)
        let timeOptions = "";
        for (let i = 10; i <= 60; i += 10) {
            timeOptions += `<option value="${i}">${i}분 추가</option>`;
        }
        
        Swal.fire({
            title: "알림 완료하기",
            html: `
                <div style="text-align: left;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">현재 알림 내용</label>
                        <div style="padding: 10px; background-color: #f8f9fa; border-radius: 5px; color: #495057;">
                            ${latestCallback.text || "선생님에게 점검받기"}
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">남은 시간</label>
                        <div style="padding: 10px; background-color: #f8f9fa; border-radius: 5px; color: #dc3545; font-weight: bold;">
                            약 ${remainingTime}분 남음
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">시간 추가하기</label>
                        <select id="callback-time" class="swal2-input" style="width: 100%; padding: 8px;">
                            ${timeOptions}
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "완료하기",
            cancelButtonText: "추가",
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#007bff",
            reverseButtons: true,
            preConfirm: () => {
                return { action: "complete" };
            }
        }).then(result => {
            if (result.isConfirmed) {
                // 완료하기 클릭
                completeCallback(latestCallback.id);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // 추가 클릭
                const time = document.getElementById("callback-time").value;
                extendCallback(latestCallback.id, parseInt(time));
            }
        });
        return;
    }
    
    // 활성 모니터링이 없을 때는 기존 로직
    // 시간 선택 옵션 생성 (10분부터 60분까지)
    let timeOptions = "";
    for (let i = 10; i <= 60; i += 10) {
        timeOptions += `<option value="${i}">${i}분 후</option>`;
    }
    
    Swal.fire({
        title: "점검 알림 설정",
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">알림 시간 선택</label>
                    <select id="callback-time" class="swal2-input" style="width: 100%; padding: 8px;">
                        ${timeOptions}
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">알림 내용</label>
                    <input type="text" id="callback-content" class="swal2-input" value="선생님에게 점검받기" style="width: 100%; padding: 8px;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "설정",
        cancelButtonText: "취소",
        confirmButtonColor: "#007bff",
        preConfirm: () => {
            const time = document.getElementById("callback-time").value;
            const content = document.getElementById("callback-content").value;
            
            if (!content.trim()) {
                Swal.showValidationMessage("알림 내용을 입력해주세요");
                return false;
            }
            
            return { time: parseInt(time), content };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const { time, content } = result.value;
            saveCallbackGeneral(time, content);
        }
    });
}

// 콜백 저장
function saveCallbackGeneral(timeMinutes, content) {
    const currentTime = Math.floor(Date.now() / 1000);
    const finishTime = currentTime + (timeMinutes * 60);
    
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "save_callback",
            userid: currentUserId,
            status: "monitoring",
            ncall: 0,
            timecreated: currentTime,
            timefinish: finishTime,
            content: content,
            note_id: 0
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "알림 설정 완료",
                    text: `${timeMinutes}분 후에 알림이 설정되었습니다.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                checkMonitoringStatus();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "오류",
                    text: "알림 설정에 실패했습니다.",
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: "error",
                title: "오류",
                text: "서버 연결에 실패했습니다.",
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
}

// 콜백 완료 처리
function completeCallback(callbackId) {
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "update_callback_status",
            userid: currentUserId,
            callback_id: callbackId,
            status: "finish"
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "완료",
                    text: "알림이 완료 처리되었습니다.",
                    timer: 2000,
                    showConfirmButton: false
                });
                checkMonitoringStatus();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "오류",
                    text: "완료 처리에 실패했습니다.",
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: "error",
                title: "오류",
                text: "서버 연결에 실패했습니다.",
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
}

// 콜백 시간 연장
function extendCallback(callbackId, additionalMinutes) {
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "extend_callback",
            userid: currentUserId,
            callback_id: callbackId,
            additional_minutes: additionalMinutes
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "시간 연장",
                    text: `${additionalMinutes}분이 추가되었습니다.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                checkMonitoringStatus();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "오류",
                    text: "시간 연장에 실패했습니다.",
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: "error",
                title: "오류",
                text: "서버 연결에 실패했습니다.",
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
}
</script>

<style>
@keyframes pulse-red {
    0% { 
        opacity: 1;
        transform: scale(1);
    }
    50% { 
        opacity: 0.6;
        transform: scale(1.05);
    }
    100% { 
        opacity: 1;
        transform: scale(1);
    }
}
</style>
';
/*
if($userid==NULL)$userid=$studentid;
$pagetype='dialogue';
$answerShort = false; // 짧게 대답할지
$count = 8; // 대화의 횟수 
$currentAnswer = '예/아니요 로 대답할 수 있도록 '.$thischtitle.'(중고등학교 교과 수학)에 대한 이해도를 묻는 질문을 주세요. 답변을 받으면 설명 후 다음 예/아니오 질문을 생성합니다. 이것을 계속하십시오.'; // 첫 마디
$rolea='학생';
$roleb='선생님';
$talka1='당신은 주어진 주제에 대해 예/아니오 로 답변 가능한 질문을 하는 선생님이다';
$talkb1='당신은 예/아니오로 답변하는 학생이다.';
$talka2='당신은 학생의 답변을 평가한다.';
$talkb2='당신은 선생님에게 예/아니오 질문을 요청한다. ';
 
$tone1='서로 대화를 주고 받는다';
$tone2='서로 감정표현을 한다.';
*/
$pagetype='chapter';
include("../LLM/postit.php");
?>
