<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$mtid=required_param('mtid', PARAM_INT);
$subject=required_param('cid', PARAM_INT);
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");

$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentselectmission','$timecreated')");
$selectdate=date("Y:m:d",time());

// 미션 타입/단원 설정
if($mtid==1 || $mtid==7) {
    $missiontype='개념';
    $unit='단원';
} else if($mtid==2) {
    $missiontype='심화';
    $unit='단원';
} else if($mtid==3) {
    $missiontype='내신';
    $unit='단계';
} else if($mtid==4) {
    $missiontype='모의';
    $unit='단계';
} else if($mtid==5) {
    $missiontype='특목';
    $unit='단계';
} else if($mtid==6) {
    $missiontype='인증';
    $unit='단계';
} else {
    $missiontype='기타';
    $unit='단원';
}

$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_curriculum WHERE mtid LIKE '$mtid' ORDER BY norder ASC");
$result = json_decode(json_encode($missionlist), True);

$subjects_options = '<option value="">선택</option>';
if(!empty($result)){
    foreach($result as $value) {
        $subjects_options .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
    }
}

function render_unit_select($selected=1) {
    $options='';
    for($i=1; $i<=10; $i++){
        $sel = ($i==$selected) ? 'selected':'';
        $options .= '<option value="'.$i.'" '.$sel.'>'.$i.'단원</option>';
    }
    return $options;
}

function render_hours_per_unit($selected=10) {
    $arr = [3,4,5,6,7,8,9,10,11,12,13,14];
    $options='';
    foreach($arr as $h) {
        $sel = ($h==$selected)?'selected':'';
        $options .= '<option value="'.$h.'" '.$sel.'>'.$h.'시간</option>';
    }
    return $options;
}

function render_week_hours($selected=10) {
    $options='';
    for($i=3; $i<=30; $i++){
        $sel = ($i==$selected)?'selected':'';
        $options .= '<option value="'.$i.'" '.$sel.'>'.$i.'시간</option>';
    }
    return $options;
}

function render_grade_select($selected=90) {
    $grades = [70,75,80,85,90,95,100];
    $options='';
    foreach($grades as $g){
        $sel=($g==$selected)?'selected':'';
        $options .= '<option value="'.$g.'" '.$sel.'>'.$g.'점</option>';
    }
    return $options;
}

function render_form($subject, $mtid, $name='', $chstart=1, $hour=10, $weekhour=10, $grade=90, $startdate='', $studentid){
    global $selectdate, $subjects_options, $unit, $missiontype;
    if(empty($startdate)) {
        $startdate=$selectdate;
    }

    $subject_field = '';
    if($subject==0){
        $subject_field = '<select id="basic1" class="form-control">'.$subjects_options.'</select>';
    } else {
        $subject_field = '<input type="text" class="form-control" value="'.$name.'" readonly>';
    }

    $chstart_field = '<select id="basic2" class="form-control">'.render_unit_select($chstart).'</select>';
    $chhours_field = '<select id="basic3" class="form-control">'.render_hours_per_unit($hour).'</select>';
    $weekhours_field = '<select id="basic5" class="form-control">'.render_week_hours($weekhour).'</select>';
    $grade_field = '<select id="basic4" class="form-control">'.render_grade_select($grade).'</select>';
    $date_field = '<input type="text" class="form-control" id="datepicker" value="'.$startdate.'">';

	return '
	<div class="container mt-1">
	  <div class="row justify-content-center">
		<div class="col-md-8">
		  <div class="card">
			<div class="card-header" style="font-weight:bold;font-size:1.5em;padding:1.5em 1em;">
				'.$missiontype.'미션
			</div>
			<div class="card-body p-2">
			  <form>
				<div class="row mb-1 justify-content-center align-items-center">
				  <label class="col-md-4 col-form-label text-end pe-2" style="font-size:1.5em;">과목선택</label>
				  <div class="col-md-4">
					'.$subject_field.'
				  </div>
				</div>
				<div class="row mb-1 justify-content-center align-items-center">
				  <label class="col-md-4 col-form-label text-end pe-2" style="font-size:1.5em;">시작'.$unit.'</label>
				  <div class="col-md-4">
					'.$chstart_field.'
				  </div>
				</div>
				<div class="row mb-1 justify-content-center align-items-center">
				  <label class="col-md-4 col-form-label text-end pe-2" style="font-size:1.5em;">소요시간</label>
				  <div class="col-md-4">
					'.$chhours_field.'
				  </div>
				</div>
				<div class="row mb-1 justify-content-center align-items-center">
				  <label class="col-md-4 col-form-label text-end pe-2" style="font-size:1.5em;">주별시간</label>
				  <div class="col-md-4">
					'.$weekhours_field.'
				  </div>
				</div>
				<div class="row mb-1 justify-content-center align-items-center">
				  <label class="col-md-4 col-form-label text-end pe-2" style="font-size:1.5em;">합격점수</label>
				  <div class="col-md-4">
					'.$grade_field.'
				  </div>
				</div>
				<div class="row mb-1 justify-content-center align-items-center">
				  <label class="col-md-4 col-form-label text-end pe-2" style="font-size:1.5em;">시작날짜</label>
				  <div class="col-md-4">
					'.$date_field.'
				  </div>
				</div>
				<div class="text-center" style="margin-top:0.5em;">
				  <button type="button" class="btn btn-primary btn-sm" onclick="saveAndRedirect()" style="font-size:1em;padding:0.3em 1em;">
					저장하기
				  </button>
				</div>
			  </form>
			</div>
		  </div>
		</div>
	  </div>
	</div>';
	


}

if($subject!=0) {
    $mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND userid='$studentid' AND subject='$subject' ");
    $name_obj=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' LIMIT 1");
    $name=$name_obj->name;
    $chstart=$mission->chstart;
    $hour=$mission->hours;
    $weekhour=$mission->weekhours;
    $grade=$mission->grade;
    $startdate=$mission->startdate;
    echo render_form($subject, $mtid, $name, $chstart, $hour, $weekhour, $grade, $startdate, $studentid);
} else {
    echo render_form($subject, $mtid, '', 1, 10, 10, 90, $selectdate, $studentid);
}

include("quicksidebar.php");
?>

<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
<script src="../assets/js/plugin/moment/moment.min.js"></script>
<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>
<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
<script src="../assets/js/plugin/select2/select2.full.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

<script>
$("#datepicker").datetimepicker({
    format: "YYYY/MM/DD",
});
$("#basic1, #basic2, #basic3, #basic4, #basic5").select2({theme:"bootstrap"});

function saveAndRedirect(){
    var Userid=<?php echo $studentid; ?>;
    var Mtype=<?php echo $mtid; ?>;
    var Subject=$("#basic1").length>0? $("#basic1").val():"<?php echo $subject;?>";
    if(!Subject) Subject="<?php echo $subject;?>";
    var Grade=$("#basic4").val();
    var Chhours=$("#basic3").val();
    var Chstart=$("#basic2").val();
    var Weekhours=$("#basic5").val();
    var Startdate=$("#datepicker").val();

    var Eventid=11; 
    if(Mtype==3 && <?php echo $subject; ?>==0) {
        Eventid=15;
    } else if(Mtype==3 && <?php echo $subject; ?>!=0) {
        Eventid=16;
    } else if(Mtype==4 && <?php echo $subject; ?>!=0) {
        Eventid=14;
    } else if(Mtype!=3 && Mtype!=4 && <?php echo $subject; ?>!=0) {
        Eventid=14;
    } else if(Mtype==4 && <?php echo $subject; ?>==0) {
        Eventid=11; 
    }

    var dataObj = {
      "eventid":Eventid,
      "userid":Userid,
      "msntype":Mtype,
      "subject":Subject,
      "grade":Grade,
      "hours":Chhours,
      "chstart":Chstart,
      "weekhours":Weekhours,
      "startdate":Startdate
    };

    <?php if($subject!=0) { ?>
    dataObj.idcreated="<?php 
        $mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND userid='$studentid' AND subject='$subject' ");
        echo $mission->id; 
    ?>";
    <?php } ?>

    $.ajax({
        url:"database.php",
        type: "POST",
        dataType:"json",
        data : dataObj,
        success:function(data){
        
        }
    });
	  // swal로 알림 후 확인 시 페이지 이동
	  swal("저장완료!", "내 공부방으로 이동합니다.", "success")
          .then((value) => {
            window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+Userid;
          });
}
</script>
