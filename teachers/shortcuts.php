<?php  
    $stayfocused1=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
    $url1=$stayfocused1->context.'?'.$stayfocused1->currenturl;
    $stayfocused3=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$studentid' AND status=3 ORDER BY id DESC LIMIT 1 ");
    $url3=$stayfocused3->context.'?'.$stayfocused3->currenturl;
    echo '<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/almtyroutine.php?inputsrc=1" accesskey="a"></a><a href=https://mathking.kr/moodle/local/augmented_teacher/managers/timetablem.php?id='.$USER->id.'&tb=7 accesskey="l"></a><a href=https://mathking.kr/moodle/local/augmented_teacher/teachers/psclass.php?id='.$USER->id.'&tb=7&mode=today  accesskey="p"></a><a href=https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$USER->id.'&tb=7 accesskey="t"></a><a href=https://mathking.kr/moodle/mod/hsuforum/view.php?id=86500 accesskey="b"></a><a href='.$url1.' accesskey="q"></a><a href='.$url3.' accesskey="w"></a><a href="https://docs.google.com/document/d/1l3ETfhu8PbUy4WPxsOvA_SO8iZsrnvfb1iJl-5bCRRg/edit?usp=sharing" target="_blank" accesskey="i"></a><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/restore_hp.php?id='.$USER->id.'" target="_blank" accesskey="h"></a><a href="https://mathking.kr/moodle/local/augmented_teacher/managers/timetable.php?id='.$USER->id.'&tb=180" target="_blank" accesskey="j"></a>';
?>

