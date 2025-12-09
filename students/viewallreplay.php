<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tabbed Mode Full Example</title>

    <!-- (필요 시) Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
    * {
      box-sizing: border-box;
    }
    @media print {
      div {
        page-break-inside: avoid;
      }
    }
    img {
      border: 1px solid #555;
    }
    body {
      margin: 0;
      font-family: Arial;
      overflow-x: hidden;
    }
    .header {
      text-align: center;
      padding: 32px;
    }
    .row {
      display: -ms-flexbox; /* IE10 */
      display: flex;
      -ms-flex-wrap: wrap; /* IE10 */
      flex-wrap: wrap;
      padding: 0 4px;
    }
    .column {
      -ms-flex: 25%; /* IE10 */
      flex: 25%;
      max-width: 25%;
      padding: 0 4px;
    }
    .column img {
      margin-top: 8px;
      vertical-align: middle;
      width: 100%;
    }
    @media screen and (max-width: 1000px) {
      .column {
        -ms-flex: 50%;
        flex: 50%;
        max-width: 50%;
      }
    }
    @media screen and (max-width: 600px) {
      .column {
        -ms-flex: 100%;
        flex: 100%;
        max-width: 100%;
      }
    }

    /* tooltip3 */
    .tooltip3 {
      position: relative;
      display: inline;
      font-size: 14px;
    }
    .tooltip3 .tooltiptext3 {
      visibility: hidden;
      background-color: white;
      color: #000;
      text-align: center;
      font-size: 14px;
      border-radius: 10px;
      padding: 0px;
      bottom: 8%;
      left: 40%;
      position: fixed;
      z-index: 2;
    }
    .tooltip3:hover .tooltiptext3 {
      visibility: visible;
    }

    /* SweetAlert 등 사용 시 */
    body.swal2-shown > [aria-hidden="true"] {
      transition: 0.01s filter;
      filter: blur(20px);
    }
    </style>
</head>
<body>

<?php
// -----------------------------------
// 원본 코드에 있던 include 부분 유지
// -----------------------------------
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

// -----------------------------------
// URL 파라미터들
// -----------------------------------
$studentid    = $_GET["id"] ?? $USER->id;
$contentsid   = $_GET["contentsid"] ?? null;
$contentstype = $_GET["contentstype"] ?? null;
$wboardid     = $_GET["wboardid"] ?? null;
$tfinish      = $_GET["tfinish"] ?? null;
$timecreated  = time();

$adayago    = $timecreated - 86400;
$halfdayago = $timecreated - 43200;

$mode = $_GET["mode"] ?? '';  // 여러 모드(peer, sol, today ...) 식별
if (!$mode) {
  // mode가 없으면 기본 모드(default)
  $mode = 'default';
}

// -----------------------------------
// 탭 목록(원본 분기별로 구성)
// -----------------------------------
$modeList = [
    'peer'     => '피어러닝',
    'sol'      => '풀이노트',
    'today'    => 'today',
    'mathtown' => 'mathtown',
    'remind'   => 'remind',
    'note'     => 'note',
    'subject'  => 'subject',
    'ltm'      => 'ltm',
    'mysol'    => 'mysol',
    'retry'    => 'retry',
    'default'  => '기본보기'
];
if (!isset($modeList[$mode])) {
    $mode = 'default';
}
?>

<!-- ========================
     탭 메뉴 (Bootstrap 등)
     ======================== -->
<ul class="nav nav-tabs">
  <?php foreach($modeList as $key => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo ($mode === $key) ? 'active' : ''; ?>"
         href="?mode=<?php echo $key; ?>&id=<?php echo $studentid; ?>">
         <?php echo $label; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<!-- ========================
     탭 컨텐츠
     ======================== -->
<div class="tab-content p-3">
  <!-- ========== peer 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='peer') ? 'show active' : ''; ?>" id="peer">
  <?php if($mode==='peer'): ?>
    <?php
    // ----------- (기존 if($mode==='peer') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'A</title></head><body>';
     
    $replay3=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='2' AND boardtype LIKE 'test'  ORDER BY nstroke DESC LIMIT 10");  
    $result3 = json_decode(json_encode($replay3), True);

    $view3='';
    foreach($result3 as $value3)
    {
      $creatorid   = $value3['userid'];
      $thisuser    = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id=' $creatorid' ");
      $thisusername= $thisuser->firstname.$thisuser->lastname;

      if($creatorid==$studentid) {
        $view3.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$value3['wboardid'].'&studentid='.$studentid.'&mode=peer">
                     <b style="color:blue;">My</b>
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>나의 풀이</td></tr></table>
                   </span>
                 </div>  &nbsp;';
      }
      elseif($wboardid===$value3['wboardid']) {
        $view3.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$value3['wboardid'].'&studentid='.$studentid.'&mode=peer">
                     <b style="color:red;">'.$value3['nstroke'].'획</b>
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>크리에이터 '.$creatorid.' : '.$thisusername.'</td></tr></table>
                   </span>
                 </div>  &nbsp;';
      }
      else {
        $view3.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$value3['wboardid'].'&studentid='.$studentid.'&mode=peer">
                     '.$value3['nstroke'].'획
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>크리에이터 '.$creatorid.' : '.$thisusername.'</td></tr></table>
                   </span>
                 </div> &nbsp; ';
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr>
              <th>
                <iframe style="border: 1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;" 
                        src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" >
                </iframe>
              </th>
            </tr> 
          </table>
          <table align=center>
            <tr>
              <th align=left>'.$studentname.' | 피어러닝</th>
              <th valign=top>&nbsp;&nbsp; '.$view3.'</th>
            </tr>
          </table>
          <li class="nav-item">
            <a href="#" class="nav-link quick-sidebar-toggler">
              <i class="flaticon-envelope-1"></i>
            </a>
          </li>';
    ?>
  <?php endif; ?>
  </div><!-- /peer tab-pane -->

  <!-- ========== sol 탭 (풀이노트) ========== -->
  <div class="tab-pane fade <?php echo ($mode==='sol') ? 'show active' : ''; ?>" id="sol">
  <?php if($mode==='sol'): ?>
    <?php
    // ----------- (기존 if($mode==='sol') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';
       
    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND status='attempt' AND tlaststroke >'$halfdayago' ORDER BY nstroke DESC LIMIT 10");
    $result1 = json_decode(json_encode($replay1), True);

    $view1='';
    foreach($result1 as $value1)
    {
      if($wboardid==NULL) $wboardid=$value1['wboardid'];
      $contentsid=$value1['contentsid'];
      $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
      $htmlDom = new DOMDocument; 
      @$htmlDom->loadHTML($qtext->questiontext);
      $imageTags = $htmlDom->getElementsByTagName('img');
      foreach($imageTags as $imageTag)
      {
        $questionimg = $imageTag->getAttribute('src');
        $questionimg = str_replace(' ', '%20', $questionimg);
        if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false) break;
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';
      if($wboardid===$value1['wboardid']){
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=sol">
                     <b style="color:red;">'.$value1['nstroke'].'획</b>
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div>  &nbsp;';
      } else {
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=sol">
                     '.$value1['nstroke'].'획
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div> &nbsp; ';
      }
    }

    $replay2=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND status='attempt' AND tlaststroke >'$halfdayago' AND nstroke NOT LIKE 'NULL' ORDER BY nstroke ASC LIMIT 10");
    $result2 = json_decode(json_encode($replay2), True);

    $view2='';
    foreach($result2 as $value2)
    {
      $contentsid=$value2['contentsid'];
      $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
      $htmlDom = new DOMDocument; 
      @$htmlDom->loadHTML($qtext->questiontext);
      $imageTags = $htmlDom->getElementsByTagName('img');
      foreach($imageTags as $imageTag)
      {
        $questionimg = $imageTag->getAttribute('src');
        $questionimg = str_replace(' ', '%20', $questionimg);
        if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';
      if($wboardid===$value2['wboardid']){
        $view2.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value2['wboardid'].'&mode=sol">
                     <b style="color:red;">'.$value2['nstroke'].'회</b>
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div>   &nbsp;';
      } else {
        $view2.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value2['wboardid'].'&mode=sol">
                     '.$value2['nstroke'].'획
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div>  &nbsp; ';
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border: 1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;" 
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" >
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">
                  '.$studentname.'
                </a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'">풀이노트</a> 
                &nbsp;&nbsp;&nbsp; 많은 &nbsp;&nbsp; '.$view1.'&nbsp;&nbsp;&nbsp;&nbsp; 적은&nbsp;&nbsp; '.$view2.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /sol tab-pane -->

  <!-- ========== today 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='today') ? 'show active' : ''; ?>" id="today">
  <?php if($mode==='today'): ?>
    <?php
    // ----------- (기존 if($mode==='today') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';

    $summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
    if($tfinish==NULL) $tfinish=$timecreated;
    $tbegin=$tfinish-57600;
    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND (contentstype=1 OR contentstype=2 ) AND tlaststroke >'$tbegin' AND tlaststroke <'$tfinish' ORDER BY tlaststroke ASC ");
    $result1 = json_decode(json_encode($replay1), True);
    $ncount=count($result1);
    $nrslt=0;
    $tprev=NULL;
    $view1='';

    foreach($result1 as $value1)
    {
      $nrslt++;
      if($wboardid==NULL && $ncount==$nrslt) $wboardid=$value1['wboardid'];
      $contentsid=$value1['contentsid'];
      if($tprev==NULL) $tprev=$value1['tlaststroke'];
      $tamount=round(($value1['tlaststroke']-$tprev)/60,1);
      $tprev=$value1['tlaststroke'];

      // 원본에서 status_iconsonly.php 등을 통해 $cnticon / $imgstatus 얻는 부분이 있으나,
      // 여기서는 단순화 ( 필요 시 로직 추가 )
      $cnticon = '';  // $imgstatus 변수 등 처리 생략

      // 문제/전자책 이미지 추출
      if($value1['contentstype']==1) {
        // 전자책
        $getimg = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
        $ctext  = $getimg->pageicontent;
        $htmlDom= new DOMDocument;
        @$htmlDom->loadHTML($ctext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        $nimg=0;
        $questionimg='';
        foreach($imageTags as $imageTag) {
          $imgSrc = $imageTag->getAttribute('src');
          $imgSrc = str_replace(' ', '%20', $imgSrc);
          if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false) {
            $questionimg = $imgSrc;
            break;
          }
        }
      }
      elseif($value1['contentstype']==2) {
        // 문항
        $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
        $htmlDom = new DOMDocument;
        @$htmlDom->loadHTML($qtext->questiontext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        $questionimg = '';
        foreach($imageTags as $imageTag) {
          $imgSrc = $imageTag->getAttribute('src');
          $imgSrc = str_replace(' ', '%20', $imgSrc);
          if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false){
            $questionimg = $imgSrc; 
            break;
          }
        }
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';

      // 시간차에 따라 스타일 분기
      // (원본 코드와 동일한 로직)
      if($tamount>10) {
        if($wboardid===$value1['wboardid']){
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 <b style="font-size:24px;color:purple;">'.date("h:i", $value1['tlaststroke']).'</b>
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div>  &nbsp;';
        } else {
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a style="color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 '.date("h:i", $value1['tlaststroke']).'
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div> &nbsp;';
        }
      }
      elseif($tamount>=5) {
        if($wboardid===$value1['wboardid']){
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 <b style="font-size:24px;color:purple;">'.date("h:i", $value1['tlaststroke']).'</b>
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div>  &nbsp;';
        } else {
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a style="color:blue;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 '.date("h:i", $value1['tlaststroke']).'
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div> &nbsp;';
        }
      }
      elseif($tamount>=0.1) {
        if($wboardid===$value1['wboardid']){
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 <b style="font-size:24px;color:purple;">'.date("h:i", $value1['tlaststroke']).'</b>
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div>  &nbsp;';
        } else {
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a style="color:green;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 '.date("h:i", $value1['tlaststroke']).'
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div> &nbsp;';
        }
      }
      else {
        if($wboardid===$value1['wboardid']){
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 <b style="font-size:24px;color:purple;">'.date("h:i", $value1['tlaststroke']).'</b>
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div>  &nbsp;';
        } else {
          $view1.=$cnticon.'<div class="tooltip3"> 
                               <a style="color:#b1b2b5;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=today">
                                 '.date("h:i", $value1['tlaststroke']).'
                               </a>
                               <span class="tooltiptext3">
                                 <table align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                               </span>
                            </div> &nbsp;';
        }
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">
                  '.$studentname.'
                </a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summary->wboardid.'&mode=today">예측</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'">메타인지</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a>
                '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /today tab-pane -->

  <!-- ========== mathtown 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='mathtown') ? 'show active' : ''; ?>" id="mathtown">
  <?php if($mode==='mathtown'): ?>
    <?php
    // ----------- (기존 if($mode==='mathtown') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';

    $summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
    if($tfinish==NULL) $tfinish=$timecreated;
    $tbegin=$tfinish-57600;
    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND nstroke>5 AND contentstype=2 AND tlaststroke >'$tbegin' AND tlaststroke <'$tfinish' ORDER BY nstroke DESC LIMIT 3");
    $result1 = json_decode(json_encode($replay1), True);
    $ncount=count($result1);
    $nrslt=0;
    $tprev=NULL;
    $view1='';

    foreach($result1 as $value1)
    {
      $nrslt++;
      if($wboardid==NULL && $ncount==$nrslt) $wboardid=$value1['wboardid'];
      $contentsid=$value1['contentsid'];
      if($tprev==NULL) $tprev=$value1['tlaststroke'];
      $tamount=round(($value1['tlaststroke']-$tprev)/60,1);
      $tprev=$value1['tlaststroke'];

      // 문제 이미지
      $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
      $htmlDom = new DOMDocument; 
      @$htmlDom->loadHTML($qtext->questiontext);
      $imageTags = $htmlDom->getElementsByTagName('img');
      $questionimg='';
      foreach($imageTags as $imageTag)
      {
        $imgSrc = $imageTag->getAttribute('src');
        $imgSrc = str_replace(' ', '%20', $imgSrc);
        if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false) break;
        $questionimg = $imgSrc;
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';

      if($wboardid===$value1['wboardid']){
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=mathtown&tfinish='.$tfinish.'">
                     <b style="font-size:30px;color:#4287f5;">'.date("h:i", $value1['tlaststroke']).'</b>
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                   </span>
                 </div>  &nbsp;';
      } else {
        $view1.='<div class="tooltip3"> 
                   <a style="font-size:16px;color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=mathtown&tfinish='.$tfinish.'">
                     '.date("h:i", $value1['tlaststroke']).'
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                   </span>
                 </div> &nbsp;';
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 
                | '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /mathtown tab-pane -->

  <!-- ========== remind 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='remind') ? 'show active' : ''; ?>" id="remind">
  <?php if($mode==='remind'): ?>
    <?php
    // ----------- (기존 if($mode==='remind') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';

    $summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
    $tfinish=$timecreated-604800*4;
    $tbegin=$tfinish-604800;
    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND nstroke>5 AND contentstype=2 AND tlaststroke >'$tbegin' AND tlaststroke <'$tfinish' ORDER BY nstroke DESC LIMIT 3");
    $result1 = json_decode(json_encode($replay1), True);
    $ncount=count($result1);
    $nrslt=0;
    $tprev=NULL;
    $view1='';

    foreach($result1 as $value1)
    {
      $nrslt++;
      if($wboardid==NULL && $ncount==$nrslt) $wboardid=$value1['wboardid'];
      $contentsid=$value1['contentsid'];
      if($tprev==NULL)$tprev=$value1['tlaststroke'];
      $tamount=round(($value1['tlaststroke']-$tprev)/60,1);
      $tprev=$value1['tlaststroke'];

      // 문제 이미지
      $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
      $htmlDom = new DOMDocument; 
      @$htmlDom->loadHTML($qtext->questiontext);
      $imageTags = $htmlDom->getElementsByTagName('img');
      $questionimg='';
      foreach($imageTags as $imageTag)
      {
        $imgSrc = $imageTag->getAttribute('src');
        $imgSrc = str_replace(' ', '%20', $imgSrc);
        if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false) break;
        $questionimg = $imgSrc;
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';

      if($wboardid===$value1['wboardid']){
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=remind&tfinish='.$tfinish.'">
                     <b style="font-size:30px;color:#4287f5;">'.date("h:i", $value1['tlaststroke']).'</b>
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                   </span>
                 </div>  &nbsp;';
      } else {
        $view1.='<div class="tooltip3"> 
                   <a style="font-size:16px;color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=remind&tfinish='.$tfinish.'">
                     '.date("h:i", $value1['tlaststroke']).'
                   </a>
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value1['wboardid'].' | '.$tamount.'분 | '.$value1['nstroke'].'획<hr>
                   </span>
                 </div> &nbsp;';
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 
                |  오래된 기억에 대한 인출 활동을 한 다음 공부를 시작하면 해석/발상 메타인지가 향상됩니다.
                '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /remind tab-pane -->

  <!-- ========== note 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='note') ? 'show active' : ''; ?>" id="note">
  <?php if($mode==='note'): ?>
    <?php
    // ----------- (기존 if($mode==='note') {...} 내용) -----------
    $cid    = $_GET["cid"] ?? null;
    $chnum  = $_GET["nch"] ?? null;
    $domain = $_GET["domain"] ?? null;

    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $chlist     = $DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$domain' ");
    $domaintitle= $chlist->title;
    $chapnum    = $chlist->chnum;
    $notetitle  = $studentname.'의 개념집착 : '.$domaintitle;

    $view1='';
    for($nch=1;$nch<=$chapnum;$nch++)
    {
      $cidstr='cid'.$nch;
      $chstr='nch'.$nch;
      $cid2= $chlist->$cidstr;
      $nchapter=$chlist->$chstr;

      $curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid2'");
      $chname='ch'.$nchapter;
      $title=$curri->$chname;

      if($cid==$cid2 && $nchapter==$chnum) {
        $wboardid='obsnote'.$cid2.'_ch'.$nchapter.'_user'.$studentid;
        $view1.='#<div class="tooltip3"> 
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&domain='.$domain.'&mode=note&cid='.$cid2.'&nch='.$nchapter.'">
                      <b style="color:purple;">'.$nch.' '.$title.'</b>
                    </a>
                    <span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span>
                  </div>  &nbsp;';
      } else {
        $view1.='<div class="tooltip3"> 
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&domain='.$domain.'&mode=note&cid='.$cid2.'&nch='.$nchapter.'">
                      <b style="color:purple;">'.$nch.' '.$title.'</b>
                    </a>
                    <span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span>
                  </div>  &nbsp;';
      }
    }

    echo '<head><title>'.$notetitle.'P</title></head><body>';
    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid.'&nch='.$chnum.'&mode=domain&domain='.$domain.'&studentid='.$studentid.'">노트보기</a> 
                | '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /note tab-pane -->

  <!-- ========== subject 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='subject') ? 'show active' : ''; ?>" id="subject">
  <?php if($mode==='subject'): ?>
    <?php
    // ----------- (기존 if($mode==='subject') {...} 내용) -----------
    $cid   = $_GET["cid"] ?? null;
    $chnum = $_GET["nch"] ?? null;

    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
    $subjectname=$curri->name;
    $chapnum=$curri->nch;
    $notetitle=$studentname.'의 개념집착 노트';
    $view1='';

    for($nch=1;$nch<=$chapnum;$nch++)
    {
      $chname='ch'.$nch;
      $title=$curri->$chname;
      if($nch==$chnum) {
        $wboardid='obsnote'.$cid.'_ch'.$chnum.'_user'.$studentid;
        $view1.='#<div class="tooltip3">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=subject&cid='.$cid.'&nch='.$nch.'">
                      <b style="color:purple;">'.$nch.' '.$title.'</b>
                    </a>
                    <span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span>
                  </div>  &nbsp;';
      }
      else {
        $view1.='<div class="tooltip3">
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=subject&cid='.$cid.'&nch='.$nch.'">
                     <b style="color:purple;">'.$nch.' '.$title.'</b>
                   </a>
                   <span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span>
                 </div>  &nbsp;';
      }
    }

    echo ' <head><title>'.$notetitle.'P</title></head><body>';
    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid.'&nch='.$chnum.'&studentid='.$studentid.'">노트보기</a> 
                | '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /subject tab-pane -->

  <!-- ========== ltm 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='ltm') ? 'show active' : ''; ?>" id="ltm">
  <?php if($mode==='ltm'): ?>
    <?php
    // ----------- (기존 if($mode==='ltm') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';
    $tweek1=time()-604800;
    $tweek2=time()-604800*2;

    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND status='attempt' AND tlaststroke >'$tweek2' AND tlaststroke <'$tweek1' ORDER BY nstroke DESC LIMIT 10");
    $result1 = json_decode(json_encode($replay1), True);

    $view1='';
    foreach($result1 as $value1)
    {
      if($wboardid==NULL) $wboardid=$value1['wboardid'];
      $dayspassed=round((time()-$value1['tlaststroke'])/86400,0);
      $contentsid=$value1['contentsid'];
      $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
      $htmlDom = new DOMDocument;
      @$htmlDom->loadHTML($qtext->questiontext);
      $imageTags = $htmlDom->getElementsByTagName('img');
      $questionimg='';
      foreach($imageTags as $imageTag) {
        $src = $imageTag->getAttribute('src');
        $src = str_replace(' ', '%20', $src);
        if(strpos($src, 'MATRIX/MATH')!= false || strpos($src, 'HintIMG')!= false) {
          $questionimg=$src;
          break;
        }
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';
      if($wboardid===$value1['wboardid']){
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm">
                     <b style="color:red;">'.$value1['nstroke'].'획</b>
                   </a>('.$dayspassed.'일)
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div>  &nbsp;';
      } else {
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm">
                     '.$value1['nstroke'].'획
                   </a>('.$dayspassed.'일)
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div> &nbsp; ';
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a> 
                | 기억관찰&nbsp; '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /ltm tab-pane -->

  <!-- ========== mysol 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='mysol') ? 'show active' : ''; ?>" id="mysol">
  <?php if($mode==='mysol'): ?>
    <?php
    // ----------- (기존 if($mode==='mysol') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';

    // 파라미터 $contentsid가 이미 위에서 잡힘
    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentsid='$contentsid' AND contentstype=2 AND boardtype='prep' ORDER BY id DESC LIMIT 10");
    $result1 = json_decode(json_encode($replay1), True);

    $view1='';
    foreach($result1 as $value1)
    {
      if($wboardid==NULL) $wboardid=$value1['wboardid'];
      $dayspassed=round((time()-$value1['tlaststroke'])/86400,0);
      $contentsid=$value1['contentsid'];
      $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
      $htmlDom = new DOMDocument;
      @$htmlDom->loadHTML($qtext->questiontext);
      $imageTags = $htmlDom->getElementsByTagName('img');
      $questionimg='';
      foreach($imageTags as $imageTag) {
        $src = $imageTag->getAttribute('src');
        $src = str_replace(' ', '%20', $src);
        if(strpos($src, 'MATRIX/MATH')!= false || strpos($src, 'HintIMG')!= false) {
          $questionimg=$src;
          break;
        }
      }
      $questiontext='<img src="'.$questionimg.'" width=500>';
      if($wboardid===$value1['wboardid']){
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm">
                     <b style="color:red;">'.$value1['nstroke'].'획</b>
                   </a>('.$dayspassed.'일)
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div>  &nbsp;';
      } else {
        $view1.='<div class="tooltip3"> 
                   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm">
                     '.$value1['nstroke'].'획
                   </a>('.$dayspassed.'일)
                   <span class="tooltiptext3">
                     <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                   </span>
                 </div> &nbsp; ';
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr>
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a> 
                | 기억관찰&nbsp; '.$view1.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /mysol tab-pane -->

  <!-- ========== retry 탭 (재도전) ========== -->
  <div class="tab-pane fade <?php echo ($mode==='retry') ? 'show active' : ''; ?>" id="retry">
  <?php if($mode==='retry'): ?>
    <?php
    // ----------- (기존 if($mode==='retry') {...} 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>Smart Recovery</title></head><body>';

    // 이곳에 문항 추천 알고리즘 적용할 수 있음
    $questionid=73359;

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border:1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'">
              </iframe>
            </th></tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /retry tab-pane -->

  <!-- ========== default(기본) 탭 ========== -->
  <div class="tab-pane fade <?php echo ($mode==='default') ? 'show active' : ''; ?>" id="default">
  <?php if($mode==='default'): ?>
    <?php
    // ----------- (기존 else { ... } 내용) -----------
    $stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
    $studentname=$stdtname->firstname.$stdtname->lastname;

    $tabtitle=$studentname;
    echo ' <head><title>'.$tabtitle.'P</title></head><body>';

    $replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND active=1 AND tlaststroke >'$halfdayago' ORDER BY nstroke DESC LIMIT 20");
    $result1 = json_decode(json_encode($replay1), True);

    $view1='';
    if($result1){
      foreach($result1 as $value1)
      {
        if($wboardid==NULL) $wboardid=$value1['wboardid'];
        $contentsid=$value1['contentsid'];
        $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
        $htmlDom = new DOMDocument; 
        @$htmlDom->loadHTML($qtext->questiontext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        $questionimg='';
        foreach($imageTags as $imageTag) {
          $src = $imageTag->getAttribute('src');
          $src = str_replace(' ', '%20', $src);
          if(strpos($src, 'MATRIX/MATH')!= false || strpos($src, 'HintIMG')!= false) {
            $questionimg = $src;
            break;
          }
        }
        $questiontext='<img src="'.$questionimg.'" width=500>';
        if($wboardid===$value1['wboardid']){
          $view1.='<div class="tooltip3"> 
                     <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'">
                       <b style="color:red;">'.$value1['nstroke'].'획</b>
                     </a>
                     <span class="tooltiptext3">
                       <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                     </span>
                   </div>  &nbsp;';
        } else {
          $view1.='<div class="tooltip3"> 
                     <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'">
                       '.$value1['nstroke'].'획
                     </a>
                     <span class="tooltiptext3">
                       <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                     </span>
                   </div> &nbsp; ';
        }
      }
    }

    $replay2=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND active=1 AND tlaststroke >'$halfdayago' ORDER BY neraser DESC LIMIT 5");
    $result2 = json_decode(json_encode($replay2), True);

    $view2='';
    if($result2){
      foreach($result2 as $value2)
      {
        $contentsid=$value2['contentsid'];
        $qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
        $htmlDom = new DOMDocument; 
        @$htmlDom->loadHTML($qtext->questiontext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        $questionimg='';
        foreach($imageTags as $imageTag) {
          $src = $imageTag->getAttribute('src');
          $src = str_replace(' ', '%20', $src);
          if(strpos($src, 'MATRIX/MATH')!= false || strpos($src, 'HintIMG')!= false) {
            $questionimg = $src;
            break;
          }
        }
        $questiontext='<img src="'.$questionimg.'" width=500>';
        if($wboardid===$value2['wboardid']){
          $view2.='<div class="tooltip3"> 
                     <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value2['wboardid'].'">
                       <b style="color:red;">'.$value2['neraser'].'회</b>
                     </a>
                     <span class="tooltiptext3">
                       <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                     </span>
                   </div>   &nbsp;';
        } else {
          $view2.='<div class="tooltip3"> 
                     <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value2['wboardid'].'">
                       '.$value2['neraser'].'회
                     </a>
                     <span class="tooltiptext3">
                       <table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>
                     </span>
                   </div>  &nbsp; ';
        }
      }
    }

    echo '<table width=100%>
            <tr><th></th></tr>
            <tr><th>
              <iframe style="border: 1px none; z-index:2; width:99vw; height:90vh; margin-left:0; margin-right:0; margin-top:0;"
                      src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9">
              </iframe>
            </th></tr> 
          </table>
          <table align=center>
            <tr>
              <th align=left>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">
                  '.$studentname.'
                </a> 
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a>
                | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=sol">오답노트</a> 
                &nbsp;&nbsp;&nbsp; 필기&nbsp;&nbsp; '.$view1.'&nbsp;&nbsp;&nbsp;&nbsp;지우개&nbsp;&nbsp; '.$view2.'
              </th>
            </tr>
          </table>';
    ?>
  <?php endif; ?>
  </div><!-- /default tab-pane -->

</div><!-- /tab-content -->

<?php
// --------------------------------------------------------------------------------------
// 아래는 원본 코드 끝 부분에 있던 “talk2us” 등 요약/리스트/JS 스크립트 로직
// 필요 시 그대로 삽입. (아래는 원본 코드 맨 끝 일부를 참고 복붙)
// --------------------------------------------------------------------------------------

// url 정보 이용하여 기간, 내용, 학생, 선생님 등 검색 가능하도록 ...
// $tb, $share, $talklist, $sharelist 등등

// 예시로 $tb = 604800 (1주) 같은 값이 있거나 직접 $_GET['tb']에서 가져올 수 있음
$tb = $_GET['tb'] ?? 604800;  // 1주
$tbegin=time()-$tb; //1주 전

$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='7128' AND  timecreated> '$tbegin' ORDER BY timemodified DESC ");  
$talklist= json_decode(json_encode($share), True);

$sharelist='';
if($talklist) {
  foreach($talklist as $value)
  {
    $sid        = $value['id'];
    $studentid2 = $value['studentid'];
    $teacherid2 = $value['teacherid'];
    $sharetext  = $value['text'];

    $stdname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid2' ");
    $studentname2=$stdname->firstname.$stdname->lastname;
    $tchname= $DB->get_record_sql("SELECT institution, lastname, firstname FROM mdl_user WHERE id='$teacherid2' ");
    $teachername2=$tchname->firstname.$tchname->lastname;

    // ... 이하 로직 생략 없이 원본 로직 동일하게...
    // 가령:
    // $analysistext = ...
    // $sharelist .= ... table ...
    // $feedback = ...
    // ...

    // 여기서는 예시로 간단히 출력
    $sharelist .= "<p><b>[$studentname2 → $teachername2]</b> $sharetext</p>";
  }
}

// 출력 예시
echo '<div class="main-panel">
        <div class="content" style="overflow-x:hidden;">
          <div class="row">
            <div class="col-md-12">
              <h4>Talk2us 공유 리스트</h4>
              '.$sharelist.'
            </div>
          </div>
        </div>
      </div>';
?>

<!-- ======================
     JS (Bootstrap, jQuery)
     ====================== -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert 사용 시 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

<!-- 아래는 예시: 숨김처리, Edittext 등 기능 존재 시 그대로 복붙 -->
<script>
function reportData(Userid,Sid,Username){
  // ...
}
function hide(Eventid,Fbid,Checkvalue){
  // ...
}
function Edittext(Itemid,Inputtext){
  // ...
}
</script>

</body>
</html>
