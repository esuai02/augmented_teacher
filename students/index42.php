<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get parameters
$studentid = $_GET["id"]; 
$cid = $_GET["cid"]; 
$access = $_GET["access"];
if($studentid == NULL) $studentid = $USER->id;

// Check user role and permissions
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'"); 
$role = $userrole->data;

if($USER->id != $studentid && $role === 'student') {
    echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
    exit; 
}

// Get user data
$timecreated = time();
$username = $DB->get_record_sql("SELECT id, hideinput, lastname, firstname, timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1");
$studentname = $username->firstname.$username->lastname;
$tabtitle = $username->lastname;

// Get user additional data
$userdata2 = $DB->get_records_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$studentid' AND (fieldid='107' OR fieldid='88' OR fieldid='89' OR fieldid='82' OR fieldid='90' OR fieldid='64')"); 
$thisuser = json_decode(json_encode($userdata2), True);
foreach($thisuser as $value) {
    if($value['fieldid'] == 107) $usersex = $value['data'];
    if($value['fieldid'] == 88) $institute = $value['data'];
    if($value['fieldid'] == 89) $birthyear = $value['data'];
    if($value['fieldid'] == 82) $AutopilotMode = $value['data'];
    if($value['fieldid'] == 90) $usrdata = $value['data'];
    if($value['fieldid'] == 64) $tsymbol = $value['data'];			
} 

// Redirect for teacher accessing via 'my'
if($access === 'my' && $role !== 'student') {
    header('Location: https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$USER->id.'&tb=7');
}

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentindex','$timecreated')");
}

// Time calculations
$timestart = time() - 604800 * 2;
$aweekago = time() - 604800;
$timestart2 = time() - 43200;
$timestart3 = time() - 86400;

// Get review notes
$reviewnotes = $DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND timemodified > '$aweekago' AND mtype='audio' ORDER BY timemodified DESC LIMIT 30"); 
$rvresult = json_decode(json_encode($reviewnotes), True);
$reviewhistory = '';
foreach($rvresult as $rvvalue) {
    $url = $rvvalue['url'];
    $cnttitle = $rvvalue['contentstitle'];
    $nreview = $rvvalue['nreview'];
    $nlastview = round(($timecreated - $rvvalue['timemodified']) / 86400, 0);
    $reviewhistory .= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$url.'" target="_blank">🎧 복습 : '.$cnttitle.' ('.$nreview.') </a></td></tr>';
}

// Get mission lists
$trecent2 = time() - 31104000;  // 1 year ago
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE timecreated>'$trecent2' AND userid='$studentid' ORDER by norder ASC");
$result = json_decode(json_encode($missionlist), True);

// Initialize mission type arrays
$mt01 = $mt02 = $mt03 = $mt04 = $mt05 = $mt06 = $mt07 = $mt08 = '';

// Counters for completed missions (limit to 3)
$completed_concept_count = 0;
$completed_advanced_count = 0;
$completed_exam_count = 0;
$completed_sat_count = 0;

// Arrays for additional missions beyond 3
$mt06_additional = '';
$mt07_additional = '';
$mt08_additional = '';

// Counters for active missions (limit to 3)
$active_concept_count = 0;
$active_advanced_count = 0;
$active_exam_count = 0;
$active_sat_count = 0;

// Arrays for additional active missions beyond 3
$mt01_additional = '';
$mt02_additional = '';
$mt03_additional = '';
$mt04_additional = '';

foreach($result as $value) {
    $mtid = 0;
    $mid = $value['id'];
    $subject = $value['subject'];	
    $deadline = $value['deadline']; 	
    $unixtimedeadline = strtotime($deadline);	
    if($unixtimedeadline > time() + 31536000 || $unixtimedeadline < time() - 31536000) continue;
    $passgrade = $value['grade'];
    $mtname = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject'");
    $contentslist = $mtname->contentslist;
    $subjectname = $mtname->name;
    $mtid = $mtname->mtid;
    $subjectname = str_replace("개념 :", "", $subjectname);
    $subjectname = str_replace("심화 :", "", $subjectname);
    $subjectname = str_replace("내신 :", "", $subjectname);
    $subjectname = str_replace("수능 :", "", $subjectname);
    
    if($value['complete'] == 0) {
        if($mtid == 1 || $mtid == 7) {
            // Limit active concept missions to 3
            $mission_row = '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$subject.'&nch=1&studentid='.$studentid.'&type=init" target="_blank"><div class="tooltip-container"><span class="image-placeholder">GPT</span><div class="tooltip-text"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png" width="60" style="display:block;margin:0 auto 8px;">GPT 학습 도우미</div></div> '.$subjectname.' </a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=math&sbjt=h3&studentid='.$studentid.'&nch=9" target="_blank"><div class="tooltip-container"><span class="image-placeholder">Anki</span><div class="tooltip-text"><img src="https://ankiweb.net/logo.png" width="60" style="display:block;margin:0 auto 8px;">Anki 암기 카드</div></div></a></td>
            <td width="4%" style=""></td><td width="30%" align="left" style="font-size:12pt">  </td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td>
            <td width="4%"><div class="form-check"> 완료 &nbsp;<label style="margin-bottom:5px;" class="form-check-label"><input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
            
            if($active_concept_count < 3) {
                $mt01 .= $mission_row;
                $active_concept_count++;
            } else {
                $mt01_additional .= $mission_row;
            }
        } elseif($mtid == 2) {
            // Limit active advanced missions to 3
            $mission_row = '';
            if(strpos($subjectname, '초등') !== false) {
                $mission_row = '<tr><td width="30%" align="left" style="font-size:12pt"><div class="tooltip-container"><span class="image-placeholder">📚</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width="60" style="display:block;margin:0 auto 8px;">학습 미션</div></div> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
            } else {
                $mission_row = '<tr><td width="30%" align="left" style="font-size:12pt"><div class="tooltip-container"><span class="image-placeholder">📚</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width="60" style="display:block;margin:0 auto 8px;">학습 미션</div></div> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
            }
            
            if($active_advanced_count < 3) {
                $mt02 .= $mission_row;
                $active_advanced_count++;
            } else {
                $mt02_additional .= $mission_row;
            }
        } elseif($mtid == 3) {
            // Limit active exam missions to 3
            $mission_row = '<tr><td width="30%" align="left" style="font-size:12pt"><div class="tooltip-container"><span class="image-placeholder">📚</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width="60" style="display:block;margin:0 auto 8px;">학습 미션</div></div> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 완료 &nbsp;<label style="margin-bottom:5px;" class="form-check-label"><input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            
            if($active_exam_count < 3) {
                $mt03 .= $mission_row;
                $active_exam_count++;
            } else {
                $mt03_additional .= $mission_row;
            }
        } elseif($mtid == 4) {
            // Limit active SAT missions to 3
            $mission_row = '<tr><td width="30%" align="left" style="font-size:12pt"><div class="tooltip-container"><span class="image-placeholder">📚</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width="60" style="display:block;margin:0 auto 8px;">학습 미션</div></div> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            
            if($active_sat_count < 3) {
                $mt04 .= $mission_row;
                $active_sat_count++;
            } else {
                $mt04_additional .= $mission_row;
            }
        }
    } else {
        if($mtid == 1 || $mtid == 7) {
            // Limit completed concept missions to 3
            if($completed_concept_count < 3) {
                $mt05 .= '<tr><td width="30%" align="left" style="color:grey;font-size:10pt"><div class="tooltip-container"><span class="image-placeholder">✅</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width="60" style="display:block;margin:0 auto 8px;">완료된 미션</div></div> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">개념 : '.$subjectname.'</a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=math&sbjt=h3&studentid='.$studentid.'&nch=9" target="_blank"><div class="tooltip-container"><span class="image-placeholder">Anki</span><div class="tooltip-text"><img src="https://ankiweb.net/logo.png" width="60" style="display:block;margin:0 auto 8px;">Anki 암기 카드</div></div></a></td><td width="4%" style=""></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td>
                <td width="4%"><div class="form-check"> 재개&nbsp;<label style="" class="form-check-label"><input type="checkbox" onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
                $completed_concept_count++;
            }
        } elseif($mtid == 2) {
            // Limit completed advanced missions to 3
            $mission_row = '';
            if(strpos($subjectname, '초등') !== false) {
                $mission_row = '<tr><td width="30%" align="left" style="font-size:10pt"><div class="tooltip-container"><span class="image-placeholder">✅</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width="60" style="display:block;margin:0 auto 8px;">완료된 미션</div></div> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">심화 : '.$subjectname.'</td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 재개&nbsp;<label class="form-check-label"><input type="checkbox" onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
            } else {
                $mission_row = '<tr><td width="30%" align="left" style="color:grey;font-size:10pt"><div class="tooltip-container"><span class="image-placeholder">✅</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width="60" style="display:block;margin:0 auto 8px;">완료된 미션</div></div> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">심화 : '.$subjectname.'</td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 재개&nbsp;<label class="form-check-label"><input type="checkbox" onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
            }
            
            if($completed_advanced_count < 3) {
                $mt06 .= $mission_row;
                $completed_advanced_count++;
            } else {
                $mt06_additional .= $mission_row;
            }
        } elseif($mtid == 3) {
            // Limit completed exam missions to 3
            $mission_row = '<tr><td width="30%" align="left" style="color:grey;font-size:10pt"><div class="tooltip-container"><span class="image-placeholder">✅</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width="60" style="display:block;margin:0 auto 8px;">완료된 미션</div></div> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">내신 : '.$subjectname.'</td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 재개&nbsp;<label style="" class="form-check-label"><input type="checkbox" onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            
            if($completed_exam_count < 3) {
                $mt07 .= $mission_row;
                $completed_exam_count++;
            } else {
                $mt07_additional .= $mission_row;
            }
        } elseif($mtid == 4) {
            // Limit completed SAT missions to 3
            $mission_row = '<tr><td width="30%" align="left" style="color:grey;font-size:10pt"><div class="tooltip-container"><span class="image-placeholder">✅</span><div class="tooltip-text"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width="60" style="display:block;margin:0 auto 8px;">완료된 미션</div></div> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">수능 : '.$subjectname.'</td><td width="4%"></td><td width="20%" style="font-size:10pt">합격 : '.$passgrade.'점</td><td width="4%"><div class="form-check"> 재개&nbsp;<label class="form-check-label"><input type="checkbox" onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
            
            if($completed_sat_count < 3) {
                $mt08 .= $mission_row;
                $completed_sat_count++;
            } else {
                $mt08_additional .= $mission_row;
            }
        }
    }
}

// Additional navigation for teachers
if($role !== 'student') {
    $inspect_fixnotes = ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a style="text-decoration:none;color:white;font-size:18px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/beactivelearner.php?userid='.$studentid.'" target="_blank">귀가평가</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a style="text-decoration:none;color:white;font-size:14px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$studentid.'" target="_blank">오답노트 검사</a> ';
}

// Save last accessed course for each mission type
if($cid) {
    // Save to user preferences or session
    $_SESSION['last_advanced_course'] = $cid;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo $tabtitle; ?> - 학습 홈</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Open+Sans:300,400,600,700"]},
            custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands"], urls: ['../assets/css/fonts.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/azzara.min.css">
    <link rel="stylesheet" href="../assets/css/demo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        /* Navigation */
        .nav-top {
            background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        
        .header-nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .nav-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .nav-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .nav-btn.active {
            background: rgba(255,255,255,0.95);
            color: #7C3AED;
            font-weight: 700;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .nav-btn.active:hover {
            background: rgba(255,255,255,1);
            color: #7C3AED;
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        }
        
        .content-wrapper {
            padding: 30px 20px 0;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nav-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
        }
        
        .view-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .view-toggle-btn {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid transparent;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .view-toggle-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .view-toggle-btn.scroll-mode {
            background: rgba(255,255,255,0.95);
            color: #7C3AED;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .view-toggle-btn.scroll-mode:hover {
            background: rgba(255,255,255,1);
            color: #7C3AED;
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        }
        
        /* Tab View Styles */
        .tab-view {
            display: none;
        }
        
        .tab-view.active {
            display: block;
        }
        
        .tab-header {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: #e9ecef;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tab-nav-item {
            flex: 1;
            padding: 12px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            margin: 0 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            color: #495057;
        }
        
        .tab-nav-item:hover {
            background: #e9ecef;
        }
        
        .tab-nav-item.active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        /* Visual Data Tab Styles */
        .visual-nav-tabs {
            display: flex;
            gap: 8px;
            margin: 20px 0;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 8px;
        }

        .visual-tab-btn {
            padding: 10px 16px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #495057;
            transition: all 0.3s;
        }

        .visual-tab-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .visual-tab-btn:hover {
            background: #e9ecef;
        }

        .visual-tab-btn.active:hover {
            background: #0056b3;
        }

        .visual-content-area {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }

        .charts-section {
            flex: 2;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
        }

        .pomodoro-section {
            flex: 1;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
        }

        .visual-tab-content {
            display: none;
        }

        .visual-tab-content.active {
            display: block;
        }

        .chart-container {
            position: relative;
            margin-bottom: 10px;
        }

        .chart-info {
            text-align: center;
            margin-top: 10px;
        }

        .pomodoro-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 15px;
        }

        .pomo-tab {
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            color: #495057;
            transition: all 0.3s;
        }

        .pomo-tab.active {
            background: #ff6b35;
            color: white;
            border-color: #ff6b35;
        }

        .pomo-tab:hover {
            background: #e9ecef;
        }

        .pomo-tab.active:hover {
            background: #e55a2b;
        }

        .pomodoro-chart-container {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .visual-controls {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .time-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            font-size: 12px;
        }

        .time-controls label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .time-controls input[type="radio"],
        .time-controls input[type="checkbox"] {
            margin-right: 3px;
        }

        @media (max-width: 768px) {
            .visual-content-area {
                flex-direction: column;
            }
            
            .visual-nav-tabs {
                flex-wrap: wrap;
            }
            
            .visual-tab-btn {
                flex: 1;
                min-width: 120px;
            }
            
            .time-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Scroll View Styles */
        .scroll-view {
            display: none;
        }
        
        .scroll-view.active {
            display: block;
        }
        
        /* Mission Section Styles */
        .mission-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .mission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f4;
        }
        
        .mission-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mission-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .badge-concept {
            background: #E05D22;
            color: white;
        }
        
        .badge-advanced {
            background: #f093fb;
            color: white;
        }
        
        .badge-exam {
            background: #4facfe;
            color: white;
        }
        
        .badge-sat {
            background: #fa709a;
            color: white;
        }
        
        .add-mission-btn {
            padding: 8px 20px;
            background: #3383FF;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .add-mission-btn:hover {
            background: #2968d6;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .mission-list {
            margin-top: 15px;
        }
        
        .mission-list table {
            width: 100%;
        }
        
        .mission-list tr {
            border-bottom: 1px solid #f1f3f4;
        }
        
        .mission-list tr:last-child {
            border-bottom: none;
        }
        
        .mission-list td {
            padding: 12px 5px;
            vertical-align: middle;
        }
        
        .mission-link {
            color: #3383FF;
            text-decoration: none;
            font-weight: 500;
        }
        
        .mission-link:hover {
            color: #2968d6;
            text-decoration: underline;
        }
        
        .review-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .review-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .review-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .review-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .review-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .review-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .form-check {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        
        .form-check-label {
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state img {
            width: 80px;
            opacity: 0.5;
            margin-bottom: 15px;
        }
        
        /* Additional Info Section */
        .info-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .info-header {
            background: #0082D8;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .info-links {
            display: flex;
            gap: 15px;
        }
        
        .info-links a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            padding: 5px 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            transition: all 0.3s;
        }
        
        .info-links a:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Section View Switching Styles */
        .sections-container {
            transition: all 0.3s ease;
        }

        /* Scroll View (Default) */
        .sections-container.scroll-view {
            display: block;
        }

        .sections-container.scroll-view .section {
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Tab View */
        .sections-container.tab-view {
            display: flex;
            flex-direction: column;
        }

        .sections-container.tab-view .tab-nav {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
            overflow-x: auto;
        }

        .sections-container.tab-view .tab-nav-item {
            padding: 15px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sections-container.tab-view .tab-nav-item:hover {
            background: rgba(0,123,255,0.1);
            color: #007bff;
        }

        .sections-container.tab-view .tab-nav-item.active {
            background: white;
            color: #495057;
            border-bottom-color: #007bff;
            font-weight: 600;
        }

        .sections-container.tab-view .tab-content {
            background: white;
            border-radius: 0 0 8px 8px;
            min-height: 500px;
        }

        .sections-container.tab-view .section {
            display: none;
            padding: 20px;
            margin-bottom: 0;
            box-shadow: none;
            border-radius: 0;
        }

        .sections-container.tab-view .section.active {
            display: block;
        }

        /* Hidden tab nav in scroll view */
        .sections-container.scroll-view .tab-nav {
            display: none;
        }
        
        @media (max-width: 768px) {
            .nav-controls {
                flex-direction: column;
                gap: 15px;
                justify-content: center;
            }
            
            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .sections-container.tab-view .tab-nav {
                flex-wrap: wrap;
            }
            
            .sections-container.tab-view .tab-nav-item {
                flex: 1;
                min-width: 0;
                justify-content: center;
                padding: 12px 8px;
                font-size: 13px;
            }
            
            .tab-btn {
                font-size: 14px;
                padding: 10px 15px;
            }
            
            .tab-nav-item {
                font-size: 12px;
                padding: 10px 15px;
            }
            
            .mission-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        /* Interactive Goal Panel Styles */
        .goal-panel {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100vh;
            z-index: 9999;
            transition: right 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .goal-panel.active {
            right: 0;
        }

        .panel-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            transition: opacity 0.3s;
            cursor: pointer;
        }

        .panel-content {
            position: absolute;
            right: 0;
            width: 550px;
            height: 100%;
            background: linear-gradient(to bottom, #ffffff, #f8f9fa);
            box-shadow: -5px 0 20px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .goal-panel.active .panel-content {
            transform: translateX(0);
        }

        .panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .header-title h3 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
            display: block;
        }

        .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .goal-type-selector {
            display: flex;
            gap: 10px;
            padding: 20px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
        }

        .type-btn {
            flex: 1;
            padding: 15px 10px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .type-btn:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .type-btn.active {
            background: white;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .type-btn i {
            font-size: 24px;
            color: #667eea;
            display: block;
            margin-bottom: 8px;
        }

        .type-btn span {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .type-btn small {
            display: block;
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        .conversation-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .message {
            margin-bottom: 15px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.assistant {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .assistant-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .message-bubble {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .assistant .message-bubble {
            background: #f1f3f4;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .message.user {
            display: flex;
            justify-content: flex-end;
        }

        .user .message-bubble {
            background: #667eea;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .quick-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            padding: 8px 14px;
            background: white;
            border: 1px solid #667eea;
            color: #667eea;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .input-area {
            padding: 15px 20px 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .smart-suggestions {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            min-height: 32px;
        }

        .suggestion-chip {
            padding: 6px 12px;
            background: #f0f4ff;
            color: #667eea;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .suggestion-chip:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .goal-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s;
            outline: none;
        }

        .goal-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-btn {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .progress-indicator {
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .progress-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
            transition: width 0.5s ease;
            width: 25%;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
        }

        .step {
            font-size: 12px;
            color: #999;
            transition: color 0.3s;
        }

        .step.active {
            color: #667eea;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 8px;
        }

        .status-badge.complete {
            background: #d4f4dd;
            color: #059669; 
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-badge.required {
            background: #fee2e2;
            color: #dc2626;
        } 

        @media (max-width: 768px) {
            .panel-content {
                width: 100%;
            }
            
            .goal-type-selector {
                padding: 15px;
            }
            
            .type-btn {
                padding: 12px 8px;
            }
            
            .type-btn small {
                display: none;
            }
        }
        
        /* Tooltip styles */
        .tooltip-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .tooltip-container .tooltip-text {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            width: 200px;
            transition: opacity 0.3s, visibility 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .tooltip-container .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: rgba(0, 0, 0, 0.9) transparent transparent transparent;
        }
        
        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Image placeholder styles */
        .image-placeholder {
            display: inline-block;
            padding: 4px 8px;
            background: #f0f4ff;
            color: #667eea;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .image-placeholder:hover {
            background: #667eea;
            color: white;
        }
        
        /* Tooltip styles for KTM content images */
        .tooltip3 {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .tooltip3 .tooltiptext3 {
            visibility: hidden;
            width: 320px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -160px;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .tooltip3 .tooltiptext3::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #555 transparent transparent transparent;
        }
        
        .tooltip3:hover .tooltiptext3 {
            visibility: visible;
            opacity: 1;
        }
        
        .tooltip3 .tooltiptext3 img {
            max-width: 300px;
            height: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="nav-top">
        <div class="content-container">
            <div class="nav-controls">
                    <div class="header-nav">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                🏠 홈
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/index42.php?id=<?php echo $studentid; ?>" class="nav-btn active"> 
            👩🏻‍🎨‍ 내공부방
            </a>


            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id=<?php echo $studentid; ?>" class="nav-btn" >
            📝 오늘
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                📅 일정
            </a>

            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/goals42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                🎯 목표
            </a>

            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/student_inbox.php?studentid=<?php echo $studentid; ?>" class="nav-btn">
            📩 메세지
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                📅 수학일기
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/index.php" class="nav-btn">
                🚀 AI튜터
            </a>
                    </div>
                    <div class="view-controls">
                        <button class="view-toggle-btn" onclick="toggleView()" title="뷰 전환">
                            <i class="fas fa-folder" id="viewIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">

        <!-- Tab View -->
        <div id="tabView" class="tab-view active">
            <div class="tab-header">
                <button class="tab-btn active" onclick="openTab('concept')">
                    <span class="mission-badge badge-concept">개념</span>
                </button>
                <button class="tab-btn" onclick="openTab('advanced')">
                    <span class="mission-badge badge-advanced">심화</span>
                </button>
                <button class="tab-btn" onclick="openTab('exam')">
                    <span class="mission-badge badge-exam">내신</span>
                </button>
                <button class="tab-btn" onclick="openTab('sat')">
                    <span class="mission-badge badge-sat">수능</span>
                </button>
                <button class="tab-btn" onclick="openTab('ktm')">KTM 서술평가</button>
                <button class="tab-nav-item" onclick="switchTab(0)">
                    <i class="fas fa-chart-bar"></i> 시험결과
                </button>
                <button class="tab-nav-item" onclick="switchTab(1)">
                    <i class="fas fa-info-circle"></i> 정보
                </button>
                <button class="tab-nav-item" onclick="switchTab(2)">
                    <i class="fas fa-chart-line"></i> 시각데이터
                </button>
            </div>

            <!-- Concept Tab -->
            <div id="concept" class="tab-content active">
                <div class="mission-section">
                    <div class="mission-header">
                        <div class="mission-title">
                            <span class="mission-badge badge-concept">개념</span>
                            개념학습 미션
                        </div>
                        <div>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=7&cid=0" class="add-mission-btn">
                                추가 <i class="flaticon-plus"></i>
                            </a>
                            <a href="http://mathking.kr/moodle/local/augmented_teacher/twinery/topiclearning.html" target="_blank" class="add-mission-btn" style="background: #6c757d;">
                                도움말
                            </a>
                        </div>
                    </div>
                    <div class="mission-list">
                        <?php if($mt01 || $mt01_additional): ?>
                            <table>
                                <tbody><?php echo $mt01; ?></tbody>
                                <?php if($mt01_additional): ?>
                                <tbody id="concept-active-more" style="display: none;"><?php echo $mt01_additional; ?></tbody>
                                <?php endif; ?>
                            </table>
                            <?php if($mt01_additional): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <button onclick="toggleConceptActiveMore()" id="concept-active-toggle-btn" class="btn btn-sm" 
                                        style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                    더보기 (진행 중인 모든 미션)
                                </button>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="tooltip-container">
                                    <span class="image-placeholder">📝</span>
                                    <div class="tooltip-text">
                                        <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                        미션이 없을 때 표시되는 이미지
                                    </div>
                                </div>
                                <p>진행중인 개념학습 미션이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($reviewhistory): ?>
                    <div class="review-section">
                        <div class="review-title">📚 개념복습 추천</div>
                        <div class="review-list">
                            <table width="100%"><?php echo $reviewhistory; ?></table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Completed Concept Missions (Limited to 3) -->
                    <?php if($mt05): ?>
                    <div class="mission-section">
                        <div class="mission-title" style="color: #999;">최근 완료</div>
                        <div class="mission-list">
                            <table><?php echo $mt05; ?></table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Advanced Tab -->
            <div id="advanced" class="tab-content">
                <div class="mission-section">
                    <div class="mission-header">
                        <div class="mission-title">
                            <span class="mission-badge badge-advanced">심화</span>
                            심화학습 미션
                        </div>
                        <div>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=2&cid=0" class="add-mission-btn">
                                추가 <i class="flaticon-plus"></i>
                            </a>
                            <a href="http://mathking.kr/moodle/local/augmented_teacher/twinery/deeperlearning.html" target="_blank" class="add-mission-btn" style="background: #6c757d;">
                                도움말
                            </a>
                        </div>
                    </div>
                    <div class="mission-list">
                        <?php if($mt02 || $mt02_additional): ?>
                            <table>
                                <tbody><?php echo $mt02; ?></tbody>
                                <?php if($mt02_additional): ?>
                                <tbody id="advanced-active-more" style="display: none;"><?php echo $mt02_additional; ?></tbody>
                                <?php endif; ?>
                            </table>
                            <?php if($mt02_additional): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <button onclick="toggleAdvancedActiveMore()" id="advanced-active-toggle-btn" class="btn btn-sm" 
                                        style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                    더보기 (진행 중인 모든 미션)
                                </button>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="tooltip-container">
                                    <span class="image-placeholder">📝</span>
                                    <div class="tooltip-text">
                                        <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                        미션이 없을 때 표시되는 이미지
                                    </div>
                                </div>
                                <p>진행중인 심화학습 미션이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed Advanced Missions -->
                <?php if($mt06): ?>
                <div class="mission-section">
                    <div class="mission-title" style="color: #999;">최근 완료 (최대 3개)</div>
                    <div class="mission-list">
                        <table>
                            <tbody><?php echo $mt06; ?></tbody>
                            <?php if($mt06_additional): ?>
                            <tbody id="advanced-more" style="display: none;"><?php echo $mt06_additional; ?></tbody>
                            <?php endif; ?>
                        </table>
                        <?php if($mt06_additional): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <button onclick="toggleAdvancedMore()" id="advanced-toggle-btn" class="btn btn-sm" 
                                    style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                더보기 (최근 완료된 모든 미션)
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Exam Tab -->
            <div id="exam" class="tab-content">
                <div class="mission-section">
                    <div class="mission-header">
                        <div class="mission-title">
                            <span class="mission-badge badge-exam">내신</span>
                            내신대비 미션
                        </div>
                        <div>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=3&cid=0" class="add-mission-btn">
                                추가 <i class="flaticon-plus"></i>
                            </a>
                            <a href="#" class="add-mission-btn" style="background: #6c757d;">
                                도움말
                            </a>
                        </div>
                    </div>
                    <div class="mission-list">
                        <?php if($mt03 || $mt03_additional): ?>
                            <table>
                                <tbody><?php echo $mt03; ?></tbody>
                                <?php if($mt03_additional): ?>
                                <tbody id="exam-active-more" style="display: none;"><?php echo $mt03_additional; ?></tbody>
                                <?php endif; ?>
                            </table>
                            <?php if($mt03_additional): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <button onclick="toggleExamActiveMore()" id="exam-active-toggle-btn" class="btn btn-sm" 
                                        style="background: linear-gradient(135deg, #84fab0, #8fd3f4); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                    더보기 (진행 중인 모든 미션)
                                </button>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="tooltip-container">
                                    <span class="image-placeholder">📝</span>
                                    <div class="tooltip-text">
                                        <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                        미션이 없을 때 표시되는 이미지
                                    </div>
                                </div>
                                <p>진행중인 내신대비 미션이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed Exam Missions -->
                <?php if($mt07): ?>
                <div class="mission-section">
                    <div class="mission-title" style="color: #999;">최근 완료 (최대 3개)</div>
                    <div class="mission-list">
                        <table>
                            <tbody><?php echo $mt07; ?></tbody>
                            <?php if($mt07_additional): ?>
                            <tbody id="exam-more" style="display: none;"><?php echo $mt07_additional; ?></tbody>
                            <?php endif; ?>
                        </table>
                        <?php if($mt07_additional): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <button onclick="toggleExamMore()" id="exam-toggle-btn" class="btn btn-sm" 
                                    style="background: linear-gradient(135deg, #84fab0, #8fd3f4); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                더보기 (최근 완료된 모든 미션)
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- SAT Tab -->
            <div id="sat" class="tab-content">
                <div class="mission-section">
                    <div class="mission-header">
                        <div class="mission-title">
                            <span class="mission-badge badge-sat">수능</span>
                            수능대비 미션
                        </div>
                        <div>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=4&cid=0" class="add-mission-btn">
                                추가 <i class="flaticon-plus"></i>
                            </a>
                            <a href="#" class="add-mission-btn" style="background: #6c757d;">
                                도움말
                            </a>
                        </div>
                    </div>
                    <div class="mission-list">
                        <?php if($mt04 || $mt04_additional): ?>
                            <table>
                                <tbody><?php echo $mt04; ?></tbody>
                                <?php if($mt04_additional): ?>
                                <tbody id="sat-active-more" style="display: none;"><?php echo $mt04_additional; ?></tbody>
                                <?php endif; ?>
                            </table>
                            <?php if($mt04_additional): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <button onclick="toggleSatActiveMore()" id="sat-active-toggle-btn" class="btn btn-sm" 
                                        style="background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                    더보기 (진행 중인 모든 미션)
                                </button>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="tooltip-container">
                                    <span class="image-placeholder">📝</span>
                                    <div class="tooltip-text">
                                        <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                        미션이 없을 때 표시되는 이미지
                                    </div>
                                </div>
                                <p>진행중인 수능대비 미션이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed SAT Missions -->
                <?php if($mt08): ?>
                <div class="mission-section">
                    <div class="mission-title" style="color: #999;">최근 완료 (최대 3개)</div>
                    <div class="mission-list">
                        <table>
                            <tbody><?php echo $mt08; ?></tbody>
                            <?php if($mt08_additional): ?>
                            <tbody id="sat-more" style="display: none;"><?php echo $mt08_additional; ?></tbody>
                            <?php endif; ?>
                        </table>
                        <?php if($mt08_additional): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <button onclick="toggleSatMore()" id="sat-toggle-btn" class="btn btn-sm" 
                                    style="background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;">
                                더보기 (최근 완료된 모든 미션)
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- KTM Tab -->
            <div id="ktm" class="tab-content">
                <div class="info-section">
                    <div class="info-header">
                        <h3>KTM 서술평가</h3>
                        <div class="info-links">
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid=<?php echo $studentid; ?>" target="_blank">출제목록</a>
                            <?php if($role !== 'student'): ?>
                                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/beactivelearner.php?userid=<?php echo $studentid; ?>" target="_blank">귀가평가</a>
                                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid=<?php echo $studentid; ?>" target="_blank">오답노트 검사</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <?php include("SPEC Intelligence.php"); ?>
                    </div>
                </div>
            </div>

            <!-- 시험결과 Tab -->
            <div id="examResults" class="tab-content">
                <div class="info-section">
                    <div class="info-header">
                        <h3><i class="fas fa-chart-bar"></i> 시험결과</h3>
                        <div class="info-links">
                            <a href="#" target="_blank">결과보기</a>
                        </div>
                    </div>
                    <div class="iframe-container" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <iframe src="about:blank" width="100%" height="600" frameborder="0" style="border-radius: 4px;"></iframe>
                    </div>
                </div>
            </div>

            <!-- 정보 Tab -->
            <div id="infoTab" class="tab-content">
                <div class="info-section">
                    <div class="info-header">
                        <h3><i class="fas fa-info-circle"></i> 학생 정보</h3>
                        <div class="info-links">
                            <a href="#" target="_blank">상세보기</a>
                        </div>
                    </div>
                    <div class="iframe-container" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <iframe src="about:blank" width="100%" height="600" frameborder="0" style="border-radius: 4px;"></iframe>
                    </div>
                </div>
            </div>

            <!-- 시각데이터 Tab -->
            <div id="visualDataTab" class="tab-content">
                <div class="info-section">
                    <div class="info-header">
                        <h3><i class="fas fa-chart-line"></i> 시각데이터</h3>
                        <div class="info-links">
                            <a href="javascript:void(0)" onclick="refreshCharts()">새로고침</a>
                            <a href="#" onclick="exportCharts()" style="background: #6c757d;">내보내기</a>
                        </div>
                    </div>
                    
                    <!-- Visual Data Navigation Tabs -->
                    <div class="visual-nav-tabs">
                        <button class="visual-tab-btn active" onclick="showVisualTab('concentration')">집착도(85.9)</button>
                        <button class="visual-tab-btn" onclick="showVisualTab('departure')">이탈(10.4)</button>
                        <button class="visual-tab-btn" onclick="showVisualTab('boredom')">지겨(11.4)</button>
                    </div>

                    <!-- Main Visual Content Area -->
                    <div class="visual-content-area">
                        <!-- Left Side: Charts -->
                        <div class="charts-section">
                            <!-- Concentration Chart -->
                            <div id="concentrationChart" class="visual-tab-content active">
                                <div class="chart-container">
                                    <canvas id="concentrationLineChart" width="400" height="200"></canvas>
                                    <div class="chart-info">
                                        <a href="#" style="color: #007bff; font-size: 12px;">실시간 집착도</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Departure Chart -->
                            <div id="departureChart" class="visual-tab-content">
                                <div class="chart-container">
                                    <canvas id="departureLineChart" width="400" height="200"></canvas>
                                    <div class="chart-info">
                                        <span style="color: #666; font-size: 12px;">이탈 패턴 분석</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Boredom Chart -->
                            <div id="boredomChart" class="visual-tab-content">
                                <div class="chart-container">
                                    <canvas id="boredomLineChart" width="400" height="200"></canvas>
                                    <div class="chart-info">
                                        <span style="color: #666; font-size: 12px;">지루함 지수 변화</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side: Pomodoro Data -->
                        <div class="pomodoro-section">
                            <div class="pomodoro-nav">
                                <button class="pomo-tab active" onclick="showPomoTab('today')">합공수기</button>
                                <button class="pomo-tab" onclick="showPomoTab('week')">세션수/주</button>
                                <button class="pomo-tab" onclick="showPomoTab('month')">만족도</button>
                                <button class="pomo-tab" onclick="showPomoTab('focus')">능동시수</button>
                            </div>
                            
                            <div class="pomodoro-chart-container">
                                <canvas id="pomodoroChart" width="300" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Controls -->
                    <div class="visual-controls">
                        <div class="time-controls">
                            <label>주간 : <input type="radio" name="timeRange" value="week" checked> 30분+</label>
                            <label>오늘까지 : <input type="radio" name="timeRange" value="today"> 출석간</label>
                            <input type="checkbox" id="dmnCounts"> DMN차실
                            <input type="checkbox" id="onlineMode"> 온라인
                            <input type="checkbox" id="timerCheck"> 타이머심
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll View -->
        <div id="scrollView" class="scroll-view">
            <!-- Concept Section -->
            <div class="mission-section">
                <div class="mission-header">
                    <div class="mission-title">
                        <span class="mission-badge badge-concept">개념</span>
                        개념학습 미션
                    </div>
                    <div>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=7&cid=0" class="add-mission-btn">
                            추가 <i class="flaticon-plus"></i>
                        </a>
                        <a href="http://mathking.kr/moodle/local/augmented_teacher/twinery/topiclearning.html" target="_blank" class="add-mission-btn" style="background: #6c757d;">
                            도움말
                        </a>
                    </div>
                </div>
                <div class="mission-list">
                    <?php if($mt01): ?>
                        <table><?php echo $mt01; ?></table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="tooltip-container">
                                <span class="image-placeholder">📝</span>
                                <div class="tooltip-text">
                                    <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                    미션이 없을 때 표시되는 이미지
                                </div>
                            </div>
                            <p>진행중인 개념학습 미션이 없습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($reviewhistory): ?>
                <div class="review-section">
                    <div class="review-title">📚 개념복습 추천</div>
                    <div class="review-list">
                        <table width="100%"><?php echo $reviewhistory; ?></table>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Advanced Section -->
            <div class="mission-section">
                <div class="mission-header">
                    <div class="mission-title">
                        <span class="mission-badge badge-advanced">심화</span>
                        심화학습 미션
                    </div>
                    <div>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=2&cid=0" class="add-mission-btn">
                            추가 <i class="flaticon-plus"></i>
                        </a>
                        <a href="http://mathking.kr/moodle/local/augmented_teacher/twinery/deeperlearning.html" target="_blank" class="add-mission-btn" style="background: #6c757d;">
                            도움말
                        </a>
                    </div>
                </div>
                <div class="mission-list">
                    <?php if($mt02): ?>
                        <table><?php echo $mt02; ?></table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="tooltip-container">
                                <span class="image-placeholder">📝</span>
                                <div class="tooltip-text">
                                    <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                    미션이 없을 때 표시되는 이미지
                                </div>
                            </div>
                            <p>진행중인 심화학습 미션이 없습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Section -->
            <div class="mission-section">
                <div class="mission-header">
                    <div class="mission-title">
                        <span class="mission-badge badge-exam">내신</span>
                        내신대비 미션
                    </div>
                    <div>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=3&cid=0" class="add-mission-btn">
                            추가 <i class="flaticon-plus"></i>
                        </a>
                        <a href="#" class="add-mission-btn" style="background: #6c757d;">
                            도움말
                        </a>
                    </div>
                </div>
                <div class="mission-list">
                    <?php if($mt03): ?>
                        <table><?php echo $mt03; ?></table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="tooltip-container">
                                <span class="image-placeholder">📝</span>
                                <div class="tooltip-text">
                                    <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                    미션이 없을 때 표시되는 이미지
                                </div>
                            </div>
                            <p>진행중인 내신대비 미션이 없습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SAT Section -->
            <div class="mission-section">
                <div class="mission-header">
                    <div class="mission-title">
                        <span class="mission-badge badge-sat">수능</span>
                        수능대비 미션
                    </div>
                    <div>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=<?php echo $studentid; ?>&mtid=4&cid=0" class="add-mission-btn">
                            추가 <i class="flaticon-plus"></i>
                        </a>
                        <a href="#" class="add-mission-btn" style="background: #6c757d;">
                            도움말
                        </a>
                    </div>
                </div>
                <div class="mission-list">
                    <?php if($mt04): ?>
                        <table><?php echo $mt04; ?></table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="tooltip-container">
                                <span class="image-placeholder">📝</span>
                                <div class="tooltip-text">
                                    <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXArti1634548406001.png" width="80" style="display:block;margin:0 auto 8px;">
                                    미션이 없을 때 표시되는 이미지
                                </div>
                            </div>
                            <p>진행중인 수능대비 미션이 없습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Long-term Planning Section -->
            <div class="mission-section">
                <div class="mission-header">
                    <div class="mission-title">
                        <span class="mission-badge" style="background: #6c757d; color: white;">후속</span>
                        장기계획
                    </div>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id=<?php echo $studentid; ?>" class="add-mission-btn">
                        장기계획 설정 <i class="flaticon-plus"></i>
                    </a>
                </div>
                <div class="mission-list">
                    <?php if($mt05 || $mt06 || $mt07 || $mt08): ?>
                        <table><?php echo $mt05.$mt06.$mt07.$mt08.$mt06_additional.$mt07_additional.$mt08_additional; ?></table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>완료된 미션을 후속 계획으로 설정할 수 있습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KTM Section -->
            <div class="info-section">
                <div class="info-header">
                    <h3>KTM 서술평가</h3>
                    <div class="info-links">
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid=<?php echo $studentid; ?>" target="_blank">출제목록</a>
                        <?php if($role !== 'student'): ?>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/beactivelearner.php?userid=<?php echo $studentid; ?>" target="_blank">귀가평가</a>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid=<?php echo $studentid; ?>" target="_blank">오답노트 검사</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php include("SPEC Intelligence.php"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../assets/js/ready.min.js"></script>

    <script>
        // View switching
        let currentView = 'tab'; // 기본값: 탭 뷰
        
        function toggleView() {
            const tabView = document.getElementById('tabView');
            const scrollView = document.getElementById('scrollView');
            const toggleBtn = document.querySelector('.view-toggle-btn');
            const viewIcon = document.getElementById('viewIcon');
            
            if(currentView === 'tab') {
                // 스크롤 뷰로 전환
                currentView = 'scroll';
                tabView.classList.remove('active');
                scrollView.classList.add('active');
                toggleBtn.classList.add('scroll-mode');
                toggleBtn.title = '탭 뷰로 전환';
                viewIcon.className = 'fas fa-stream';
                localStorage.setItem('indexPreferredView', 'scroll');
            } else {
                // 탭 뷰로 전환
                currentView = 'tab';
                tabView.classList.add('active');
                scrollView.classList.remove('active');
                toggleBtn.classList.remove('scroll-mode');
                toggleBtn.title = '스크롤 뷰로 전환';
                viewIcon.className = 'fas fa-folder';
                localStorage.setItem('indexPreferredView', 'tab');
            }
        }

        // Tab switching for main tabs
        function openTab(tabName) {
            const tabContents = document.querySelectorAll('.tab-content');
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabNavItems = document.querySelectorAll('.tab-nav-item');
            
            tabContents.forEach(content => content.classList.remove('active'));
            tabBtns.forEach(btn => btn.classList.remove('active'));
            tabNavItems.forEach(item => item.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            
            // Find and activate the corresponding button
            const tabIndex = {
                'concept': 0,
                'advanced': 1,
                'exam': 2,
                'sat': 3,
                'ktm': 4
            };
            
            if(tabBtns[tabIndex[tabName]]) {
                tabBtns[tabIndex[tabName]].classList.add('active');
            }
            
            localStorage.setItem('indexActiveTab', tabName);
        }

        // Tab switching for new nav items
        function switchTab(tabIndex) {
            const tabContents = document.querySelectorAll('.tab-content');
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabNavItems = document.querySelectorAll('.tab-nav-item');
            
            tabContents.forEach(content => content.classList.remove('active'));
            tabBtns.forEach(btn => btn.classList.remove('active'));
            tabNavItems.forEach(item => item.classList.remove('active'));
            
            // Activate the corresponding tab content and button
            if (tabIndex === 0) {
                document.getElementById('examResults').classList.add('active');
                tabNavItems[0].classList.add('active');
                localStorage.setItem('indexActiveTab', 'examResults');
            } else if (tabIndex === 1) {
                document.getElementById('infoTab').classList.add('active');
                tabNavItems[1].classList.add('active');
                localStorage.setItem('indexActiveTab', 'infoTab');
            } else if (tabIndex === 2) {
                document.getElementById('visualDataTab').classList.add('active');
                tabNavItems[2].classList.add('active');
                localStorage.setItem('indexActiveTab', 'visualDataTab');
                // Initialize charts when visual data tab is opened
                initializeCharts();
            }
        }

        // Visual Data Tab Functions
        function showVisualTab(tabName) {
            const visualTabContents = document.querySelectorAll('.visual-tab-content');
            const visualTabBtns = document.querySelectorAll('.visual-tab-btn');
            
            visualTabContents.forEach(content => content.classList.remove('active'));
            visualTabBtns.forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabName + 'Chart').classList.add('active');
            event.target.classList.add('active');
        }

        function showPomoTab(tabName) {
            const pomoTabs = document.querySelectorAll('.pomo-tab');
            pomoTabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update pomodoro chart based on selected tab
            updatePomodoroChart(tabName);
        }

        function refreshCharts() {
            initializeCharts();
            updatePomodoroChart('today');
        }

        function exportCharts() {
            // Implementation for chart export functionality
            alert('차트 내보내기 기능이 곧 추가될 예정입니다.');
        }

        // Chart initialization functions
        function initializeCharts() {
            // Sample data for demonstration
            const concentrationData = [95, 88, 92, 85, 90, 87, 35, 80, 85, 88, 92, 89, 91, 88, 85];
            const departureData = [5, 8, 12, 15, 10, 13, 65, 20, 15, 12, 8, 11, 9, 12, 15];
            const boredomData = [8, 10, 15, 12, 18, 22, 45, 25, 20, 18, 15, 12, 10, 13, 16];
            
            initConcentrationChart(concentrationData);
            initDepartureChart(departureData);
            initBoredomChart(boredomData);
            updatePomodoroChart('today');
        }

        function initConcentrationChart(data) {
            const canvas = document.getElementById('concentrationLineChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Simple line chart implementation
            drawLineChart(ctx, data, '#8e44ad', canvas.width, canvas.height);
        }

        function initDepartureChart(data) {
            const canvas = document.getElementById('departureLineChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            drawLineChart(ctx, data, '#e74c3c', canvas.width, canvas.height);
        }

        function initBoredomChart(data) {
            const canvas = document.getElementById('boredomLineChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            drawLineChart(ctx, data, '#f39c12', canvas.width, canvas.height);
        }

        function updatePomodoroChart(tabName) {
            const canvas = document.getElementById('pomodoroChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Sample pomodoro data
            const pomoData = tabName === 'today' ? [15, 20, 25, 22, 28, 30, 35] : 
                           tabName === 'week' ? [45, 52, 48, 55, 60, 58, 62] :
                           tabName === 'month' ? [75, 78, 82, 80, 85, 88, 90] :
                           [25, 30, 35, 32, 38, 40, 45];
            
            drawLineChart(ctx, pomoData, '#ff6b35', canvas.width, canvas.height);
        }

        function drawLineChart(ctx, data, color, width, height) {
            const padding = 40;
            const chartWidth = width - padding * 2;
            const chartHeight = height - padding * 2;
            const maxValue = Math.max(...data);
            const minValue = Math.min(...data);
            const range = maxValue - minValue || 1;
            
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            data.forEach((value, index) => {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                const y = padding + (1 - (value - minValue) / range) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Draw grid lines
            ctx.strokeStyle = '#e9ecef';
            ctx.lineWidth = 1;
            
            // Horizontal grid lines
            for (let i = 0; i <= 4; i++) {
                const y = padding + (i / 4) * chartHeight;
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(width - padding, y);
                ctx.stroke();
            }
            
            // Vertical grid lines
            for (let i = 0; i <= 6; i++) {
                const x = padding + (i / 6) * chartWidth;
                ctx.beginPath();
                ctx.moveTo(x, padding);
                ctx.lineTo(x, height - padding);
                ctx.stroke();
            }
        }

        // Show more toggle functions for completed missions
        function toggleAdvancedMore() {
            const moreSection = document.getElementById('advanced-more');
            const toggleBtn = document.getElementById('advanced-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (최근 완료된 모든 미션)';
            }
        }

        function toggleExamMore() {
            const moreSection = document.getElementById('exam-more');
            const toggleBtn = document.getElementById('exam-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (최근 완료된 모든 미션)';
            }
        }

        function toggleSatMore() {
            const moreSection = document.getElementById('sat-more');
            const toggleBtn = document.getElementById('sat-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (최근 완료된 모든 미션)';
            }
        }

        // Show more toggle functions for active missions
        function toggleConceptActiveMore() {
            const moreSection = document.getElementById('concept-active-more');
            const toggleBtn = document.getElementById('concept-active-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (진행 중인 모든 미션)';
            }
        }

        function toggleAdvancedActiveMore() {
            const moreSection = document.getElementById('advanced-active-more');
            const toggleBtn = document.getElementById('advanced-active-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (진행 중인 모든 미션)';
            }
        }

        function toggleExamActiveMore() {
            const moreSection = document.getElementById('exam-active-more');
            const toggleBtn = document.getElementById('exam-active-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (진행 중인 모든 미션)';
            }
        }

        function toggleSatActiveMore() {
            const moreSection = document.getElementById('sat-active-more');
            const toggleBtn = document.getElementById('sat-active-toggle-btn');
            
            if (moreSection.style.display === 'none') {
                moreSection.style.display = 'table-row-group';
                toggleBtn.textContent = '접기';
            } else {
                moreSection.style.display = 'none';
                toggleBtn.textContent = '더보기 (진행 중인 모든 미션)';
            }
        }

        // Checkbox functions
        function changecheckbox(Eventid, Userid, Missionid, Checkvalue) {
            var checkimsi = 0;
            if(Checkvalue == true) {
                checkimsi = 1;
            }
            $.ajax({
                url: "check.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": Eventid,
                    "userid": Userid,
                    "missionid": Missionid,
                    "checkimsi": checkimsi
                },
                success: function(data) {
                    location.reload();
                }
            });
        }

        // Load user preferences on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load preferred view
            const preferredView = localStorage.getItem('indexPreferredView');
            if(preferredView && preferredView === 'scroll') {
                currentView = 'tab'; // 현재 상태를 탭으로 설정해서 토글이 올바르게 작동하도록
                toggleView(); // 스크롤 뷰로 전환
            }
            
            // Load active tab
            const activeTab = localStorage.getItem('indexActiveTab');
            if(activeTab) {
                openTab(activeTab);
            }
        });
    </script>
    
    <!-- Interactive Goal Panel -->
    <div id="goalPanel" class="goal-panel">
        <div class="panel-overlay" onclick="closeGoalPanel()"></div>
        <div class="panel-content">
            <!-- Panel Header -->
            <div class="panel-header">
                <div class="header-title">
                    <h3>목표 설정 도우미</h3>
                    <span class="subtitle">AI가 목표 설정을 도와드려요</span>
                </div>
                <button class="close-btn" onclick="closeGoalPanel()">×</button>
            </div>
            
            <!-- Goal Type Selector -->
            <div class="goal-type-selector">
                <button class="type-btn active" data-type="daily" onclick="switchGoalType('daily')">
                    <i class="fas fa-calendar-day"></i>
                    <span>오늘목표</span>
                    <small>오늘 할 일</small>
                </button>
                <button class="type-btn" data-type="weekly" onclick="switchGoalType('weekly')">
                    <i class="fas fa-calendar-week"></i>
                    <span>주간목표</span>
                    <small>7일 실행 계획</small>
                </button>
                <button class="type-btn" data-type="quarterly" onclick="switchGoalType('quarterly')">
                    <i class="fas fa-calendar-alt"></i>
                    <span>분기목표</span>
                    <small>3개월 장기 계획</small>
                </button>
            </div>
            
            <!-- Conversational Interface -->
            <div class="conversation-container">
                <div class="chat-messages" id="chatMessages">
                    <!-- Dynamic messages will be inserted here -->
                </div>
                
                <!-- Input Area -->
                <div class="input-area">
                    <div class="smart-suggestions" id="smartSuggestions">
                        <!-- Dynamic suggestions -->
                    </div>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="goalInput" 
                               class="goal-input" 
                               placeholder="목표를 입력하세요..."
                               autocomplete="off"
                               onkeypress="if(event.key === 'Enter') processGoalInput()">
                        <button class="send-btn" onclick="processGoalInput()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-steps">
                    <span class="step active">목표 유형</span>
                    <span class="step">내용 입력</span>
                    <span class="step">세부 설정</span>
                    <span class="step">확인</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Interactive Goal Panel JavaScript -->
    <script>
    // Goal Panel State Management
    class GoalPanelManager {
        constructor(studentId) {
            this.studentId = studentId;
            this.state = {
                isOpen: false,
                currentType: 'daily',
                currentStep: 1,
                conversation: [],
                goalData: {
                    daily: { tasks: [], completed: false },
                    weekly: { goals: [], dates: [], completed: false },
                    quarterly: { goal: '', deadline: '', details: [], completed: false }
                },
                existingGoals: {},
                questionIndex: 0
            };
            
            this.templates = {
                daily: {
                    greeting: "안녕하세요! 오늘 하루 목표를 함께 세워볼까요? ☀️",
                    questions: [
                        "오늘 가장 중요한 일 3가지는 무엇인가요?",
                        "각 작업에 얼마나 시간이 필요한가요?",
                        "언제 시작하실 예정인가요?"
                    ],
                    suggestions: [
                        "수학 문제집 10페이지 풀기",
                        "영어 단어 50개 암기",
                        "과학 실험 보고서 작성",
                        "독서 1시간"
                    ]
                },
                weekly: {
                    greeting: "이번 주 계획을 함께 세워볼까요? 📅 일주일간의 목표를 설정해봅시다.",
                    questions: [
                        "이번 주에 꼭 완성해야 할 가장 중요한 것은 무엇인가요?",
                        "월요일부터 금요일까지 각 요일별로 할 일을 나누어볼까요?",
                        "우선순위가 가장 높은 3가지는 무엇인가요?"
                    ],
                    suggestions: [
                        "월: 수학 개념 정리",
                        "화: 영어 에세이 작성",
                        "수: 과학 실험 준비",
                        "목: 역사 발표 준비"
                    ]
                },
                quarterly: {
                    greeting: "안녕하세요! 분기목표 설정을 도와드릴게요. 🎯 앞으로 3개월간의 큰 목표를 세워봅시다.",
                    questions: [
                        "앞으로 3개월 동안 달성하고 싶은 가장 중요한 목표는 무엇인가요?",
                        "이 목표를 달성하기 위한 구체적인 마일스톤을 3가지 알려주세요.",
                        "목표 달성 기한을 언제로 설정하시겠어요?"
                    ],
                    suggestions: [
                        "수학 성적 20점 올리기",
                        "영어 회화 중급 레벨 달성",
                        "코딩 프로젝트 완성하기",
                        "체력 증진 - 5km 달리기"
                    ]
                }
            };
            
            this.init();
        }
        
        init() {
            this.loadExistingGoals();
        }
        
        loadExistingGoals() {
            // Load existing goals from the server
            const self = this;
            $.ajax({
                url: 'get_goals_ajax.php',
                method: 'GET',
                data: {
                    id: this.studentId,
                    type: 'all'
                },
                success: function(response) {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.status === 'success' && result.data) {
                        self.state.existingGoals = result.data;
                        
                        // Update goal data with existing goals
                        if (result.data.daily) {
                            self.state.goalData.daily = {
                                tasks: result.data.daily.text.split(' / '),
                                completed: true
                            };
                        }
                        if (result.data.weekly) {
                            self.state.goalData.weekly = {
                                goals: result.data.weekly.text.split(' / '),
                                dates: [],
                                completed: true
                            };
                        }
                        if (result.data.quarterly) {
                            self.state.goalData.quarterly = {
                                goal: result.data.quarterly.text,
                                deadline: result.data.quarterly.deadline,
                                details: [],
                                completed: true
                            };
                        }
                    }
                },
                error: function() {
                    console.log('Could not load existing goals');
                }
            });
        }
        
        openPanel(type = 'daily') {
            this.state.isOpen = true;
            this.state.currentType = type;
            this.state.currentStep = 1;
            this.state.questionIndex = 0;
            
            document.getElementById('goalPanel').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Set active type button
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.type === type) {
                    btn.classList.add('active');
                }
            });
            
            this.startConversation(type);
            this.updateProgressBar(25);
        }
        
        closePanel() {
            if (this.hasUnsavedChanges()) {
                if (!confirm('저장하지 않은 변경사항이 있습니다. 정말 닫으시겠습니까?')) {
                    return;
                }
            }
            
            this.state.isOpen = false;
            document.getElementById('goalPanel').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        switchGoalType(type) {
            this.state.currentType = type;
            this.state.questionIndex = 0;
            
            // Update active button
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.type === type) {
                    btn.classList.add('active');
                }
            });
            
            this.startConversation(type);
        }
        
        startConversation(type) {
            const template = this.templates[type];
            const chatMessages = document.getElementById('chatMessages');
            
            // Clear previous conversation
            chatMessages.innerHTML = '';
            this.state.conversation = [];
            
            // Add greeting message
            this.addAssistantMessage(template.greeting);
            
            // Check for existing goals
            const hasExisting = this.checkExistingGoal(type);
            if (hasExisting) {
                setTimeout(() => {
                    this.addAssistantMessage(
                        `현재 설정된 ${this.getTypeName(type)}이(가) 있습니다. 수정하시겠습니까, 아니면 새로 만드시겠습니까?`,
                        [
                            {text: '수정하기', action: 'edit'},
                            {text: '새로 만들기', action: 'new'}
                        ]
                    );
                }, 1000);
            } else {
                // Start with first question
                setTimeout(() => {
                    this.askNextQuestion();
                }, 1500);
            }
            
            // Update suggestions
            this.updateSuggestions(template.suggestions);
        }
        
        addAssistantMessage(text, quickActions = []) {
            const message = document.createElement('div');
            message.className = 'message assistant';
            
            const avatar = document.createElement('div');
            avatar.className = 'assistant-avatar';
            avatar.innerHTML = '🤖';
            
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.innerHTML = text;
            
            if (quickActions.length > 0) {
                const actions = document.createElement('div');
                actions.className = 'quick-actions';
                
                quickActions.forEach(action => {
                    const btn = document.createElement('button');
                    btn.className = 'quick-action-btn';
                    btn.textContent = action.text;
                    btn.onclick = () => this.handleQuickAction(action);
                    actions.appendChild(btn);
                });
                
                bubble.appendChild(actions);
            }
            
            message.appendChild(avatar);
            message.appendChild(bubble);
            
            document.getElementById('chatMessages').appendChild(message);
            this.scrollToBottom();
        }
        
        addUserMessage(text) {
            const message = document.createElement('div');
            message.className = 'message user';
            
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = text;
            
            message.appendChild(bubble);
            
            document.getElementById('chatMessages').appendChild(message);
            this.scrollToBottom();
        }
        
        processGoalInput() {
            const input = document.getElementById('goalInput');
            const value = input.value.trim();
            
            if (!value) return;
            
            // Add user message
            this.addUserMessage(value);
            
            // Clear input
            input.value = '';
            
            // Process based on current context
            this.handleUserInput(value);
        }
        
        handleUserInput(input) {
            const type = this.state.currentType;
            
            // Store input based on current question
            this.storeGoalData(type, input);
            
            // Provide feedback
            setTimeout(() => {
                this.provideAIFeedback(input);
            }, 500);
        }
        
        storeGoalData(type, input) {
            const data = this.state.goalData[type];
            
            if (type === 'daily') {
                data.tasks.push(input);
            } else if (type === 'weekly') {
                data.goals.push(input);
            } else if (type === 'quarterly') {
                if (this.state.questionIndex === 0) {
                    data.goal = input;
                } else if (this.state.questionIndex === 1) {
                    data.details.push(input);
                } else if (this.state.questionIndex === 2) {
                    data.deadline = input;
                }
            }
        }
        
        provideAIFeedback(input) {
            const feedback = this.analyzeGoalQuality(input);
            
            if (feedback.needsImprovement) {
                this.addAssistantMessage(
                    `좋은 시작이에요! ${feedback.suggestion}`,
                    [
                        {text: '수정하기', action: 'revise'},
                        {text: '계속하기', action: 'continue'}
                    ]
                );
            } else {
                this.addAssistantMessage(`훌륭해요! ${feedback.praise}`);
                
                // Move to next question or complete
                this.state.questionIndex++;
                const template = this.templates[this.state.currentType];
                
                if (this.state.questionIndex < template.questions.length) {
                    setTimeout(() => {
                        this.askNextQuestion();
                    }, 1000);
                } else {
                    // All questions answered, show summary
                    setTimeout(() => {
                        this.showGoalSummary();
                    }, 1000);
                }
            }
            
            // Update progress
            const progress = Math.min(100, 25 + (this.state.questionIndex * 25));
            this.updateProgressBar(progress);
        }
        
        analyzeGoalQuality(input) {
            const analysis = {
                needsImprovement: false,
                suggestion: '',
                praise: ''
            };
            
            // Check for SMART criteria
            if (input.length < 10) {
                analysis.needsImprovement = true;
                analysis.suggestion = '더 구체적으로 만들어볼까요? 예를 들어, "수학 문제집 2단원 10페이지 완성" 같은 형식은 어떨까요?';
            } else if (!this.containsNumbers(input) && this.state.currentType !== 'quarterly') {
                analysis.needsImprovement = true;
                analysis.suggestion = '측정 가능한 목표를 위해 숫자를 포함시켜보세요. 예: "영어 단어 30개 암기"';
            } else {
                const praises = [
                    '구체적이고 명확한 목표네요!',
                    '아주 잘 설정하셨어요!',
                    '훌륭한 목표입니다!',
                    '실천 가능한 좋은 목표예요!'
                ];
                analysis.praise = praises[Math.floor(Math.random() * praises.length)];
            }
            
            return analysis;
        }
        
        askNextQuestion() {
            const template = this.templates[this.state.currentType];
            const question = template.questions[this.state.questionIndex];
            
            if (question) {
                this.addAssistantMessage(question);
                
                // Update suggestions based on question
                this.updateContextualSuggestions();
            }
        }
        
        updateSuggestions(suggestions) {
            const container = document.getElementById('smartSuggestions');
            container.innerHTML = '';
            
            suggestions.forEach(text => {
                const chip = document.createElement('div');
                chip.className = 'suggestion-chip';
                chip.textContent = text;
                chip.onclick = () => {
                    document.getElementById('goalInput').value = text;
                    this.processGoalInput();
                };
                container.appendChild(chip);
            });
        }
        
        updateContextualSuggestions() {
            const type = this.state.currentType;
            const questionIndex = this.state.questionIndex;
            
            let suggestions = [];
            
            if (type === 'daily') {
                if (questionIndex === 0) {
                    suggestions = [
                        "수학 2단원 복습",
                        "영어 에세이 초안 작성",
                        "과학 실험 보고서",
                        "운동 30분"
                    ];
                } else if (questionIndex === 1) {
                    suggestions = ["30분", "1시간", "2시간", "3시간"];
                } else if (questionIndex === 2) {
                    suggestions = ["오전 9시", "오전 10시", "오후 2시", "오후 4시"];
                }
            } else if (type === 'weekly') {
                if (questionIndex === 0) {
                    suggestions = [
                        "중간고사 대비 완료",
                        "프로젝트 50% 진행",
                        "영어 Chapter 3 마스터",
                        "수학 문제집 1권 완료"
                    ];
                } else if (questionIndex === 1) {
                    suggestions = [
                        "월: 개념 정리",
                        "화: 문제 풀이",
                        "수: 오답 정리",
                        "목: 심화 학습"
                    ];
                }
            } else if (type === 'quarterly') {
                if (questionIndex === 0) {
                    suggestions = [
                        "전 과목 평균 90점 달성",
                        "토익 800점 달성",
                        "코딩 포트폴리오 완성",
                        "대학 입시 준비 완료"
                    ];
                } else if (questionIndex === 1) {
                    suggestions = [
                        "1개월차: 기초 다지기",
                        "2개월차: 심화 학습",
                        "3개월차: 실전 연습",
                        "매주 모의고사"
                    ];
                }
            }
            
            this.updateSuggestions(suggestions);
        }
        
        showGoalSummary() {
            const type = this.state.currentType;
            const data = this.state.goalData[type];
            
            let summaryHtml = `<h4>📝 ${this.getTypeName(type)} 설정 완료!</h4><br>`;
            
            if (type === 'daily') {
                summaryHtml += '<strong>오늘의 목표:</strong><ul>';
                data.tasks.forEach(task => {
                    summaryHtml += `<li>${task}</li>`;
                });
                summaryHtml += '</ul>';
            } else if (type === 'weekly') {
                summaryHtml += '<strong>주간 목표:</strong><ul>';
                data.goals.forEach(goal => {
                    summaryHtml += `<li>${goal}</li>`;
                });
                summaryHtml += '</ul>';
            } else if (type === 'quarterly') {
                summaryHtml += `<strong>분기 목표:</strong> ${data.goal}<br>`;
                if (data.details.length > 0) {
                    summaryHtml += '<strong>세부 계획:</strong><ul>';
                    data.details.forEach(detail => {
                        summaryHtml += `<li>${detail}</li>`;
                    });
                    summaryHtml += '</ul>';
                }
                if (data.deadline) {
                    summaryHtml += `<strong>목표 기한:</strong> ${data.deadline}`;
                }
            }
            
            this.addAssistantMessage(summaryHtml);
            
            // Add save button
            setTimeout(() => {
                this.addAssistantMessage(
                    '목표를 저장하시겠습니까?',
                    [
                        {text: '저장하기', action: 'save'},
                        {text: '수정하기', action: 'edit_summary'}
                    ]
                );
            }, 1000);
            
            this.updateProgressBar(100);
        }
        
        handleQuickAction(action) {
            if (action.action === 'save') {
                this.saveGoals();
            } else if (action.action === 'continue') {
                this.state.questionIndex++;
                this.askNextQuestion();
            } else if (action.action === 'revise') {
                this.addAssistantMessage('다시 입력해주세요. 더 구체적으로 작성해보세요!');
            } else if (action.action === 'new') {
                this.state.goalData[this.state.currentType] = 
                    this.state.currentType === 'daily' ? { tasks: [], completed: false } :
                    this.state.currentType === 'weekly' ? { goals: [], dates: [], completed: false } :
                    { goal: '', deadline: '', details: [], completed: false };
                this.state.questionIndex = 0;
                this.askNextQuestion();
            } else if (action.action === 'edit') {
                this.loadExistingGoalData();
            }
        }
        
        saveGoals() {
            const type = this.state.currentType;
            const data = this.state.goalData[type];
            const studentId = this.studentId;
            
            // Prepare data based on type
            let saveData = {
                eventid: 2,
                userid: studentId,
                type: '',
                inputtext: '',
                deadline: new Date().toISOString().split('T')[0]
            };
            
            if (type === 'daily') {
                saveData.type = '오늘목표';
                saveData.inputtext = data.tasks.join(' / ');
            } else if (type === 'weekly') {
                saveData.type = '주간목표';
                saveData.inputtext = data.goals.join(' / ');
                saveData.deadline = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            } else if (type === 'quarterly') {
                saveData.type = '시험목표';
                saveData.inputtext = data.goal;
                saveData.deadline = data.deadline || new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            }
            
            // Save to server
            $.ajax({
                url: 'save_goal_interactive.php',
                method: 'POST',
                data: saveData,
                success: (response) => {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.status === 'success') {
                        this.addAssistantMessage('목표가 성공적으로 저장되었습니다! 🎉 화이팅하세요!');
                    
                    // Mark as completed
                    data.completed = true;
                    
                    // Close panel after 2 seconds
                    setTimeout(() => {
                        this.closePanel();
                        location.reload();
                    }, 2000);
                },
                error: () => {
                    this.addAssistantMessage('저장 중 오류가 발생했습니다. 다시 시도해주세요.');
                }
            });
        }
        
        checkExistingGoal(type) {
            // Check if there are existing goals for this type
            return this.state.existingGoals && this.state.existingGoals[type] && this.state.existingGoals[type].text;
        }
        
        loadExistingGoalData() {
            // Load and display existing goal data for editing
            const type = this.state.currentType;
            const existing = this.state.existingGoals[type];
            
            if (existing && existing.text) {
                // Display current goal
                let displayText = `현재 설정된 ${this.getTypeName(type)}:\n`;
                
                if (type === 'daily' || type === 'weekly') {
                    const tasks = existing.text.split(' / ');
                    tasks.forEach((task, index) => {
                        displayText += `${index + 1}. ${task}\n`;
                    });
                } else {
                    displayText += existing.text;
                    if (existing.deadline) {
                        displayText += `\n마감일: ${existing.deadline}`;
                    }
                }
                
                this.addAssistantMessage(displayText);
                
                // Show goal status
                const statusDiv = document.getElementById('goalStatus');
                const statusContent = document.getElementById('statusContent');
                
                if (statusDiv && statusContent) {
                    statusDiv.style.display = 'block';
                    
                    let statusHTML = `
                        <div class="goal-item">
                            <span class="goal-label">설정 날짜:</span>
                            <span class="goal-value">${new Date(existing.timecreated * 1000).toLocaleDateString('ko-KR')}</span>
                        </div>`;
                    
                    if (existing.deadline) {
                        const dDay = Math.ceil((new Date(existing.deadline) - new Date()) / (1000 * 60 * 60 * 24));
                        statusHTML += `
                            <div class="goal-item">
                                <span class="goal-label">마감까지:</span>
                                <span class="status-badge ${dDay < 7 ? 'required' : dDay < 30 ? 'pending' : 'complete'}">D-${dDay}</span>
                            </div>`;
                    }
                    
                    statusContent.innerHTML = statusHTML;
                }
                
                setTimeout(() => {
                    this.addAssistantMessage('수정할 내용을 입력해주세요. 그대로 유지하려면 "유지"라고 입력하세요.');
                }, 1000);
            }
        }
        
        updateProgressBar(percentage) {
            document.getElementById('progressFill').style.width = percentage + '%';
            
            // Update step indicators
            const steps = document.querySelectorAll('.step');
            const currentStep = Math.floor((percentage / 100) * 4);
            
            steps.forEach((step, index) => {
                if (index <= currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }
        
        scrollToBottom() {
            const messages = document.getElementById('chatMessages');
            messages.scrollTop = messages.scrollHeight;
        }
        
        getTypeName(type) {
            const names = {
                daily: '오늘목표',
                weekly: '주간목표',
                quarterly: '분기목표'
            };
            return names[type] || type;
        }
        
        containsNumbers(str) {
            return /\d/.test(str);
        }
        
        hasUnsavedChanges() {
            return Object.keys(this.state.goalData).some(type => {
                const data = this.state.goalData[type];
                if (type === 'daily') return data.tasks.length > 0 && !data.completed;
                if (type === 'weekly') return data.goals.length > 0 && !data.completed;
                if (type === 'quarterly') return data.goal !== '' && !data.completed;
                return false;
            });
        }
    }
    
    // Initialize Goal Panel Manager
    let goalManager;
    document.addEventListener('DOMContentLoaded', () => {
        const studentId = <?php echo $studentid; ?>;
        goalManager = new GoalPanelManager(studentId);
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && goalManager.state.isOpen) {
                goalManager.closePanel();
            }
        });
    });
    
    // Global functions for HTML onclick
    function openGoalPanel(type) {
        if (!goalManager) {
            const studentId = <?php echo $studentid; ?>;
            goalManager = new GoalPanelManager(studentId);
        }
        goalManager.openPanel(type);
    }
    
    function closeGoalPanel() {
        if (goalManager) {
            goalManager.closePanel();
        }
    }
    
    function processGoalInput() {
        if (goalManager) {
            goalManager.processGoalInput();
        }
    }
    
    function switchGoalType(type) {
        if (goalManager) {
            goalManager.switchGoalType(type);
        }
    }
    </script>
</body>
</html>