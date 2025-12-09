<?php  
$minutesago=$timecreated-600;
$hoursago3=$timecreated-10800;
$aweekago=$timecreated-604800;

$adayago=$timecreated-43200;
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid' AND type LIKE '오늘목표' AND timecreated>'$adayago' ORDER BY id DESC LIMIT 1 ");
$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$USER->id'  AND active=1 AND (status LIKE  'begin'  OR (status LIKE  'exam' AND timereviewed<'$minutesago') OR status LIKE  'sequence'  OR status LIKE  'evidence'  OR status LIKE  'modify'  OR status LIKE  'explain'  OR status LIKE  'direct'  OR student_check=1 ) AND hide=0 AND timemodified>'$aweekago' ORDER BY timemodified DESC LIMIT 1");
//$tracking= $DB->get_record_sql("SELECT  * FROM mdl_abessi_tracking WHERE userid='$USER->id' AND status='begin' AND timecreated >'$hoursago3' ORDER BY id DESC LIMIT 1 ");
$lmode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='90' "); // 신규,자율,지도,도제


$callback = $DB->get_record_sql("SELECT * FROM  mdl_abessi_callback WHERE userid='$studentid' AND status LIKE 'monitoring' AND ncall <=3 AND timefinish < '$timecreated'  ORDER BY id DESC LIMIT 1 ");
$lastanki=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where studentid LIKE '$USER->id' AND status LIKE 'begin' AND timecreated>'$adayago' ORDER BY id DESC LIMIT 1 ");  

if($checkgoal->id==NULL && $role==='student')  
	{ 
	echo '<script>
	var Userid=\''.$USER->id.'\'; 
	
	var url="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id="+Userid; 
	 
			 swal({
				title: \'오늘목표를 입력해주세요\', 
				type: \'warning\',
				buttons:{
					confirm: { 
						visible: true,
						text : \'새창으로\',
						className : \'btn btn-danger\'
					},
					cancel: {
						text : \'이동하기\',
						className: \'btn btn-success\'
					}      			
				},
			}).then((willDelete) => {
				if (willDelete) {	 
					window.open(url, "_blank");
				} else {
					window.open(url, "_blank");  
				}
			});
</script>'; 
	} 
elseif($callback->id!=NULL && $role==='student')
{ 
$DB->execute("UPDATE {abessi_callback} SET  ncall=ncall+1 WHERE  userid='$USER->id'  AND status LIKE 'monitoring'  ");
echo '<script>
var Userid=\''.$USER->id.'\';  
var url="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid="+Userid; 
 
		 swal({
			title: \'선생님과 대화가 필요합니다. 선생님 자리로 와주세요 !\', 
			type: \'warning\',
			buttons:{
				confirm: { 
					visible: true,
					text : \'새창으로\',
					className : \'btn btn-danger\'
				},
				cancel: {
					text : \'이동하기\',
					className: \'btn btn-success\'
				}      			
			},
		}).then((willDelete) => {
			if (willDelete) {	 
				window.open(url, "_blank");
			} else {
				window.open(url, "_blank");  
			}
		});
</script>'; 
} 
/*
elseif($tracking->id==NULL && $role==='student' && $lmode->data==='능동') //지도학습 발견
	{ 
	echo '<script>
	var Userid=\''.$USER->id.'\'; 
	
	var url="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid="+Userid; 
	 
			 swal({
				title: \'다음 활동을 구체화해 주세요\', 
				type: \'warning\',
				buttons:{
					confirm: { 
						visible: true,
						text : \'입력하기\',
						className : \'btn btn-danger\'
					},
					cancel: {
						text : \'이동하기\',
						className: \'btn btn-success\'
					}      			
				},
			}).then((willDelete) => {
				if (willDelete) {	 
					window.open(url, "_blank");
				} else {
					window.open(url, "_blank");  
				}
			});

</script>'; 
	} 
 
elseif($lastanki->id!=NULL && $lastanki->status==='begin') //지도학습 발견
	{ 
	echo '<script>
	var Userid=\''.$USER->id.'\'; 
	
	var url="https://mathking.kr/moodle/local/augmented_teacher/teachers/displayanki.php?userid="+Userid; 
	 
			 swal({
				title: \'새로운 ANKI 활동이 있습니다.\', 
				type: \'warning\',
				buttons:{
					confirm: { 
						visible: true,
						text : \'시작하기\',
						className : \'btn btn-danger\'
					},
					cancel: {
						text : \'취소하기\',
						className: \'btn btn-success\'
					}      			
				},
			}).then((willDelete) => {
				if (willDelete) {	 
					window.open(url, "_blank");
				} else {
					window.open(url, "_blank");  
				}
			});

</script>'; 
	} 
	
elseif($note->student_check==1 && $note->turn!=1) //지면평가 발견  
	{ 
	
	$thisinstruction=$note->instruction;
	//var url="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?userid="+Userid;
	if($thisinstruction==NULL)$thisinstruction='보충학습이 있습니다.';
	if($note->boardtype==='complementary' && $note->status==='complete') echo '';
	else echo '<script>	 
		var Userid=\''.$USER->id.'\'; 
		var Instruction=\''.$thisinstruction.'\'; 
		Wboardid=\''.$note->wboardid.'\'; 
		//var url="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+Userid;
		swal("보충학습이 있습니다.", {buttons: false,timer: 5000});
	</script>'; 
	
	} 
	*/
elseif($note->status==='begin' && $note->timecreated>$aweekago) //오답노트 발견
	{ 
	echo '<script>swal("새로운 오답노트가 있습니다.", {buttons: false,timer: 5000});</script>';
	/*
 	echo '<script>
		var Userid=\''.$USER->id.'\'; 
		var Wboardid=\''.$note->wboardid.'\'; 
 		var url1="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+Wboardid;	 
		var url2="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+Userid;
 				swal({
					title: \'서술평가 준비활동이 발견되었습니다 !\', 
					type: \'warning\',
					buttons:{
						confirm: { 
							visible: true,
							text : \'노트보기\',
							className : \'btn btn-danger\'
						},
						cancel: {
							text : \'목록보기\',
							className: \'btn btn-success\'
						}      			
					},
				}).then((willDelete) => {
					if (willDelete) {	 
						//window.open(url2, "_blank");
					} else {
						//window.open(url2, "_blank");  
					}
				});

	</script>'; */
	} 
elseif($note->status==='exam') //오답노트 발견
	{ 
	echo '<script>swal("새로운 서술평가 있습니다.", {buttons: false,timer: 5000});</script>';
	/*
 	echo '<script>
		var Userid=\''.$USER->id.'\'; 
		var Wboardid=\''.$note->wboardid.'\'; 
 		var url1="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+Wboardid;	
		var url2="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+Userid;	 	 
 				swal({
					title: \'서술평가가 발견되었습니다.\', 
					type: \'warning\',
					buttons:{
						confirm: { 
							visible: true,
							text : \'노트보기\',
							className : \'btn btn-danger\'
						},
						cancel: {
							text : \'목록보기\',
							className: \'btn btn-success\'
						}      			
					},
				}).then((willDelete) => {
					if (willDelete) {	 
						window.open(url2, "_blank");
					} else {
						//window.open(url2, "_blank");  
					}
				});

	</script>';  */
	} 
elseif($note->status==='sequence' || $note->status==='evidence' || $note->status==='modify' || $note->status==='explain' ||$note->status==='direct') //오답노트 발견
	{ 
 	echo '<script>
		var Userid=\''.$USER->id.'\'; 
		var Wboardid=\''.$note->wboardid.'\'; 
 		var url1="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+Wboardid;	
		var url2="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+Userid;	 	 
 				swal({
					title: \'노트 수정요청이 있습니다.\', 
					type: \'warning\',
					buttons:{
						confirm: { 
							visible: true,
							text : \'노트보기\',
							className : \'btn btn-danger\'
						},
						cancel: {
							text : \'목록보기\',
							className: \'btn btn-success\'
						}      			
					},
				}).then((willDelete) => {
					if (willDelete) {	 
						window.open(url2, "_blank");
					} else {
						//window.open(url2, "_blank");  
					}
				});

	</script>'; 
	}  
?>

