<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
require_login();
$cid=$_GET["cid"];
$nch=$_GET["nch"]; 
$cmid=$_GET["cmid"]; 
$domain=$_GET["dmn"]; 
$nthispage=$_GET["page"];
$pgtype=$_GET["pgtype"];
$quizid=$_GET["quizid"];
$studentid=$_GET["studentid"]; 
$timecreated=time(); 
  
if($studentid==NULL) $studentid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1"); 
$role=$userrole->data;
$lstyle=$DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$studentid' AND fieldid='90' ORDER BY id DESC LIMIT 1"); 
$learningstyle=$lstyle->data;

$userinfo= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid'");
$username=$userinfo->firstname.$userinfo->lastname;

$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
if($role==='student') $tabtitle='G : '.$weeklyGoal->text;
else $tabtitle=$username.'의 수학노트';

$mynoteurl= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
$mynotecontextid=substr($mynoteurl, 0, strpos($mynoteurl, '?')); 
$mynoteurl=strstr($mynoteurl, '?');  
$mynoteurl=str_replace("?", "", $mynoteurl); 

$cntpages=$DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cmid' ORDER BY pagenum ASC");
$result = json_decode(json_encode($cntpages), true);
$ntotalpages = count($cntpages);
$progress = ($ntotalpages > 0) ? round(($nthispage / $ntotalpages) * 100) : 0;
unset($value);

foreach($result as $value)
{
  $title=$value['title'];
  $npage=$value['pagenum'];

  if($npage==1) $contentsid0=$value['id'];
  $contentsid=$value['id'];

  // 추후 삭제
  if($npage==$ntotalpages && (strpos($title, '표유형')!= false || strpos($title, 'heck')!= false)) {
    $DB->execute("UPDATE {icontent_pages} SET milestone='1' WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
  }

  $srcid='jnrsorksqcrark'.$contentsid;
  $wboardid='jnrsorksqcrark'.$contentsid.'_user'.$studentid;
  $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY timemodified DESC LIMIT 1");
  $thiscnt=$DB->get_record_sql("SELECT milestone FROM mdl_icontent_pages WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
  $milestone=$thiscnt->milestone;
  if($milestone==NULL) $milestone=0;

  // NEW: Parse audio mode preference from reflections2
  $audioModePreference = 'section'; // default
  if($thisboard && !empty($thisboard->reflections2)) {
      $reflections2 = json_decode($thisboard->reflections2, true);
      if(is_array($reflections2) && isset($reflections2['audio_mode'])) {
          $audioModePreference = $reflections2['audio_mode'];
      }
  }

  // Log preference loading for debugging
  error_log(sprintf(
      '[Audio Mode Load] File: %s, Line: %d, User: %d, Board: %s, Mode: %s',
      basename(__FILE__),
      __LINE__,
      $studentid,
      $wboardid,
      $audioModePreference
  ));

  // 오디오 아이콘과 반복청취 횟수 설정
  $flagicon = ''; // 깃발 아이콘 초기화
  if($value['audiourl']!=NULL || $value['audiourl2']!=NULL) {
    // 헤드폰 아이콘 클릭 시 수업 엿듣기 페이지 열기
    $audioicon=' <span class="regenerate-audio-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); window.open(\'https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$contentsid.'&ctype=1\', \'_blank\');" style="cursor:pointer; font-size:0.9em;" title="수업 엿듣기">🎧</span>';
    // 학생이 아닌 경우 깃발 아이콘 항상 표시 (audiourl2 존재 여부에 따라 색상 변경)
    if($role !== 'student') {
      // audiourl2 존재 여부에 따라 아이콘 색상 결정
      $icon = ($value['audiourl2'] != NULL) ? '🟢' : '🟡';  // 녹색 : 노란색
      $playCount = '';
      // audiourl2가 있고 nreview가 있으면 재생횟수 표시 (작은 크기)
      if($value['audiourl2'] != NULL && $thisboard->nreview > 0) {
        $playCount = '<span style="font-size:0.8em;">('.$thisboard->nreview.')</span>';
      }
      $flagtitle = ($value['audiourl2'] != NULL) ? '절차기억 나레이션 재생성' : '절차기억 나레이션 생성';
      $flagicon=' <span class="generate-dialog-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); handleFlagNarration(\''.$contentsid.'\');" style="cursor:pointer; font-size:0.9em;" title="'.$flagtitle.'">'.$icon.$playCount.'</span>';
    }
  } else {
    // 오디오가 없을 때 클릭 시 수업 엿듣기 페이지 열기
    $audioicon=' <span class="generate-audio-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); window.open(\'https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$contentsid.'&ctype=1\', \'_blank\');" style="cursor:pointer; color:#007bff; font-size:0.9em;" title="수업 엿듣기">🎧</span>';

    // 학생이 아닌 경우에만 깃발 아이콘 생성
    if($role !== 'student') {
      // audiourl2가 없으므로 항상 노란색
      $flagicon=' <span class="generate-dialog-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); handleFlagNarration(\''.$contentsid.'\');" style="cursor:pointer; font-size:0.9em;" title="절차기억 나레이션 생성">🟡</span>';
    }
  }

  // 자동출제
  $lmode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='90' ");
  if(($thisboard->wboardid==NULL && $USER->id==$studentid) || $thisboard->url==NULL)
  {   
    $mynoteurl2='cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&page='.$npage.'&studentid='.$studentid.'&quizid='.$quizid;
    $DB->execute("INSERT INTO {abessi_messages} 
      (userid, userto, userrole, talkid, nstep, turn, student_check, status, contentstype, wboardid, contentstitle, contentsid, url, timemodified, timecreated)
      VALUES ('$studentid','2','$role','2','0','$milestone','0','begintopic','1','$wboardid','inspecttopic','$contentsid','$mynoteurl2','$timecreated','$timecreated')");
  }

  if($npage==1) {
    $headimg='<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg1.png" width=15>';
    $contentstitle=$title;
  }
  elseif(strpos($title, 'Check')!== false) $headimg='<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png" width=15>';
  elseif(strpos($title, '유형')!== false) $headimg='<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg3.png" width=15>';
  else $headimg='<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png" width=15>';

  $cjnfblist='';
  $width1=80;
  $width2=20;
 
  if($pgtype==='quiz')
  {
    $showpage='https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid;

    if($learningstyle==='도제' && strpos($title, '대표')!==false) echo '';
    elseif(strpos($title, '유형')!== false) {
      $contentslist2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank">'.$headimg.'</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
    elseif(strpos($title, '복습')!== false) {
      $contentslist3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$title.'</a>'.$audioicon.$flagicon.' <input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/></td></tr>';
    }
    else {
      $contentslist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank">'.$headimg.'</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
      
    $nnextpage=$nthispage+1;
    $nextpage=$DB->get_record_sql("SELECT id,title FROM mdl_icontent_pages WHERE cmid='$cmid' AND pagenum='$nnextpage' ORDER BY id DESC LIMIT 1");  
     
    if(strpos($nextpage->title, '유형')!= false && $quizid!=NULL) {
      $nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$nnextpage.'&studentid='.$studentid;
    }
    elseif($quizid!=NULL) {
      $nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&cntid='.($cmid+1).'&studentid='.$studentid;
    }
 
    $rule='<a style="text-decoration:none;color:white;" href="'.$nextlearningurl.'"><button class="stylish-button">NEXT</button></a>';     
  }
  elseif($npage==$nthispage)
  {
    $topictitle=$value['title'];
    $audiocnt='';
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");  
    $maintext=$cnttext->maintext; 
    $milestone=$cnttext->milestone;
    $thispageid=$contentsid;
    if($npage==1) $contentstitle=$title;
    else $contentstitle=$contentstitle.'-'.$cnttext->title;

    // Extract outputtext from mdl_abrainalignment_gptresults for full audio subtitle
    // Same logic as openai_tts.php (line 14-15)
    $fullAudioSubtitle = '';
    try {
      $gptResult = $DB->get_record_sql("SELECT * FROM {abrainalignment_gptresults} WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '1' ORDER BY id DESC LIMIT 1");

      if($gptResult && !empty($gptResult->outputtext)) {
        $fullAudioSubtitle = $gptResult->outputtext;

        // Format speaker names (선생님:, 학생:) with bold and line breaks
        $fullAudioSubtitle = preg_replace('/^(선생님:|학생:)/m', '<br><strong>$1</strong>', $fullAudioSubtitle);
        // Remove leading <br> if subtitle starts with speaker
        $fullAudioSubtitle = preg_replace('/^<br>/', '', $fullAudioSubtitle);

        error_log(sprintf(
          '[Full Audio Subtitle] File: %s, Line: %d, Source: DB outputtext (type=conversation), Length: %d',
          basename(__FILE__),
          __LINE__,
          strlen($fullAudioSubtitle)
        ));
      } else {
        error_log(sprintf(
          '[Full Audio Subtitle] File: %s, Line: %d, Warning: No conversation GPT result found for contentsid=%s, contentstype=%s',
          basename(__FILE__),
          __LINE__,
          $contentsid,
          $contentstype
        ));
      }
    } catch (Exception $e) {
      error_log(sprintf(
        '[Full Audio Subtitle] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
      ));
    }

    if($cnttext->audiourl !== NULL || $cnttext->audiourl2 !== NULL) {
      $audiocnt = '
      <style>
        #speedSlider::-webkit-slider-thumb::after,
        #speedSlider::-moz-range-thumb::after,
        #speedSlider::after,
        #speedSlider2::-webkit-slider-thumb::after,
        #speedSlider2::-moz-range-thumb::after,
        #speedSlider2::after {
          content: none !important;
          display: none !important;
        }

        /* Hide volume controls */
        audio::-webkit-media-controls-volume-slider,
        audio::-webkit-media-controls-mute-button,
        audio::-webkit-media-controls-volume-slider-container,
        audio::-webkit-media-controls-volume-control-container {
          display: none !important;
        }
        audio::-moz-media-controls-volume-button,
        audio::-moz-media-controls-volume-slider {
          display: none !important;
        }

        .audio-container {
          background: transparent !important;
          padding: 5px;
          position: relative;
          border: none !important;
        }

        /* Speed slider - only visible on hover */
        .speed-slider-wrapper {
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .audio-container:hover .speed-slider-wrapper {
          opacity: 1;
          visibility: visible;
        }

        /* Remove all table borders and backgrounds */
        .audio-container table {
          border: none !important;
          border-collapse: collapse !important;
          background: transparent !important;
        }

        .audio-container td {
          border: none !important;
          background: transparent !important;
        }

        .audio-player-wrapper {
          position: relative;
          display: inline-block;
          background: transparent !important;
          transition: filter 0.3s ease;
        }

        /* Localized blur effect for audio players */
        .audio-player-wrapper.blurred {
          filter: blur(5px);
        }

        .audio-player-wrapper.blurred .audio-tooltip {
          display: none;
        }

        /* Blur the speed slider when its audio player is blurred */
        .speed-slider-wrapper {
          transition: filter 0.3s ease;
          background: transparent !important;
        }

        .speed-slider-wrapper.blurred {
          filter: blur(5px);
        }

        /* Blur the review count when its audio player is blurred */
        .review-count-wrapper {
          transition: filter 0.3s ease;
          background: transparent !important;
        }

        .review-count-wrapper.blurred {
          filter: blur(5px);
        }

        .audio-tooltip {
          position: absolute;
          left: 45px;
          top: -28px;
          transform: translateX(0);
          background: rgba(0, 0, 0, 0.8);
          color: white;
          padding: 5px 10px;
          border-radius: 4px;
          white-space: nowrap;
          font-size: 12px;
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s, visibility 0.3s;
          z-index: 1000;
        }

        .audio-tooltip.show {
          opacity: 1;
          visibility: visible;
        }

        /* Audio player visibility control */
        .audio-player-hidden {
          display: none !important;
        }

        .audio-player-visible {
          display: block !important;
        }

        /* Full mode: hide section-specific elements only, keep container visible */
        body[data-audio-mode="full"] #audioWrapper2 {
          display: none !important;
        }

        body[data-audio-mode="full"] .listening-text-display,
        body[data-audio-mode="full"] .listening-progress-dots,
        body[data-audio-mode="full"] .nav-arrow {
          display: none !important;
        }

        body[data-audio-mode="full"] #fullAudioControls {
          display: block !important;
        }

        /* Section mode: hide full player and controls */
        body[data-audio-mode="section"] #audioPlayerFull,
        body[data-audio-mode="section"] #fullAudioControls {
          display: none !important;
        }

        /* Subtitle container styles */
        .subtitle-container {
          transition: all 0.3s ease;
        }

        .subtitle-container.show {
          display: block !important;
        }

        .subtitle-container.hide {
          display: none !important;
        }

        .subtitle-text-highlight {
          background-color: #fff3cd;
          padding: 2px 4px;
          border-radius: 3px;
        }
      </style>
      <div class="audio-container" id="audioContainer">';

      // First audio player
      if($cnttext->audiourl !== NULL) {
        $audiocnt .= '
        <div style="display: none;">
          <!-- Hidden audio element for full narration - controlled by toggle -->
          <audio id="audioPlayerFull" data-audiourl="'.$cnttext->audiourl.'">
            <source src="'.$cnttext->audiourl.'" type="audio/mpeg">
          </audio>
        </div>';
      }

      // Second audio player (절차기억 또는 듣기평가)
      // audiourl 또는 audiourl2 중 하나라도 있으면 듣기평가 모드 확인
      if($cnttext->audiourl !== NULL || $cnttext->audiourl2 !== NULL) {
        // 듣기평가 모드 확인 (reflections1 필드에 구간 정보가 있는지 확인)
        $isListeningTest = false;
        $sectionData = null;

        // 사용할 오디오 URL 결정 (audiourl2 우선, 없으면 audiourl)
        $audioUrlToUse = ($cnttext->audiourl2 !== NULL) ? $cnttext->audiourl2 : $cnttext->audiourl;

        // 듣기평가 모드 확인 (디버깅 정보 제거, 에러 로그만 유지)
        if(!empty($cnttext->reflections1)) {
          error_log("DEBUG - reflections1 원본: " . $cnttext->reflections1);

          $decoded = json_decode($cnttext->reflections1, true);
          error_log("DEBUG - JSON 디코드 결과: " . print_r($decoded, true));
          if(isset($decoded['mode']) && $decoded['mode'] === 'listening_test') {
            $isListeningTest = true;
            $sectionData = $decoded;
            error_log("DEBUG - 듣기평가 모드 활성화됨. 사용 URL: " . $audioUrlToUse);
          } else {
            error_log("DEBUG - 듣기평가 모드 아님. mode 값: " . (isset($decoded['mode']) ? $decoded['mode'] : 'null'));
          }
        } else {
          error_log("DEBUG - reflections1 필드가 비어있음. audiourl: " . $cnttext->audiourl . ", audiourl2: " . $cnttext->audiourl2);
        }
        
        if($isListeningTest && $sectionData) {
          // 듣기평가 맞춤 인터페이스 (구간별 재생)
          $sectionFiles = $sectionData['sections'];
          $textSections = $sectionData['text_sections'];
          $sectionCount = count($sectionFiles);
          
          $audiocnt .= '
          <style>
             /* 중앙 하단 플로팅 미니 플레이어 */
             .listening-test-container {
               position: fixed;
               bottom: 20px;
               left: 50%;
               transform: translateX(-50%);
               width: 320px;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              border-radius: 16px;
              box-shadow: 0 10px 40px rgba(0,0,0,0.3);
              padding: 0;
              z-index: 9999;
              transition: all 0.3s ease;
              cursor: default;
            }
            
            .listening-test-container.minimized {
              width: 60px;
              height: 60px;
              border-radius: 50%;
              cursor: pointer;
              transform: translateX(-50%);
            }
            
            .listening-test-container.minimized .listening-header {
              display: none;
            }
            
            .listening-test-container.minimized .listening-body {
              display: none;
            }
            
            .listening-test-container.minimized::before {
              content: "🎧";
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              font-size: 28px;
              line-height: 1;
            }
            
            .listening-header {
              background: rgba(255,255,255,0.1);
              padding: 12px 16px;
              border-radius: 16px 16px 0 0;
              display: flex;
              justify-content: space-between;
              align-items: center;
              cursor: move;
            }
            
            .listening-progress {
              font-size: 13px;
              font-weight: 600;
              color: white;
              margin: 0;
            }
            
            .listening-minimize-btn {
              background: rgba(255,255,255,0.2);
              border: none;
              color: white;
              width: 24px;
              height: 24px;
              border-radius: 50%;
              cursor: pointer;
              font-size: 16px;
              line-height: 1;
              transition: all 0.2s;
            }
            
            .listening-minimize-btn:hover {
              background: rgba(255,255,255,0.3);
              transform: scale(1.1);
            }
            
            .listening-body {
              padding: 16px;
            }
            
            .listening-test-container.minimized .listening-header,
            .listening-test-container.minimized .listening-body {
              display: none;
            }
            
            .listening-test-container.minimized::before {
              content: "🎧";
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              font-size: 28px;
            }
            
            .listening-text-display {
              background: rgba(255,255,255,0.95);
              border-left: 4px solid #4CAF50;
              padding: 12px;
              margin: 0 0 12px 0;
              border-radius: 8px;
              font-size: 13px;
              line-height: 1.6;
              min-height: 120px;
              max-height: 120px;
              overflow-y: auto;
              display: none;
              color: #333;
            }
            
            .listening-text-display.active {
              display: block;
              animation: fadeIn 0.3s;
            }
            
            @keyframes fadeIn {
              from { opacity: 0; transform: translateY(-10px); }
              to { opacity: 1; transform: translateY(0); }
            }
            
            .listening-audio-hidden {
              width: 100%;
              height: 40px;
              margin-bottom: 12px;
              border-radius: 8px;
              display: none; /* 기본 숨김 */
            }

            /* 전체재생 모드일 때는 기본 audio player 표시 */
            body[data-audio-mode="full"] .listening-audio-hidden {
              display: block !important;
            }

            /* 단계별 모드일 때는 오디오 플레이어 숨김 */
            body[data-audio-mode="section"] .listening-audio-hidden {
              display: none !important;
            }
            
            /* Progress Dots */
            .listening-progress-dots {
              display: flex;
              justify-content: center;
              align-items: center;
              gap: 8px;
              margin: 0 0 10px 0; /* 상단 마진 제거하여 위로 이동 */
              padding: 0;
              position: relative; /* 절대 위치의 자식 요소를 위한 기준점 */
            }
            
            .progress-dot {
              width: 10px;
              height: 10px;
              border-radius: 50%;
              background: rgba(255,255,255,0.3);
              cursor: pointer;
              transition: all 0.3s ease;
              position: relative;
            }
            
            .progress-dot:hover {
              background: rgba(255,255,255,0.6);
              transform: scale(1.3);
            }
            
            .progress-dot.active {
              background: white;
              box-shadow: 0 0 10px rgba(255,255,255,0.8);
              transform: scale(1.4);
            }
            
            .progress-dot.completed {
              background: #4CAF50;
              box-shadow: 0 0 8px rgba(76,175,80,0.6);
            }

            /* Search Section Button - 우측 끝에 절대 위치로 배치 */
            .replay-section-btn {
              background: transparent;
              border: none;
              color: white;
              width: 28px;
              height: 28px;
              border-radius: 50%;
              cursor: pointer;
              font-size: 16px;
              display: flex;
              align-items: center;
              justify-content: center;
              transition: all 0.2s ease;
              padding: 0;
              opacity: 0.9;
              position: absolute;
              right: 10px;
              top: 50%;
              transform: translateY(-50%);
            }

            .replay-section-btn:hover {
              background: rgba(255,255,255,0.4);
              transform: translateY(-50%) scale(1.2);
              opacity: 1;
              box-shadow: 0 0 8px rgba(255,255,255,0.5);
            }

            .replay-section-btn:active {
              transform: translateY(-50%) scale(0.95);
            }

            /* 좌우 화살표 네비게이션 버튼 */
            .nav-arrow {
              width: auto;
              height: auto;
              background: transparent;
              border: none;
              border-radius: 0;
              cursor: pointer;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 14px;
              color: white;
              transition: all 0.3s ease;
              padding: 0;
            }

            .nav-arrow:hover:not(:disabled) {
              color: rgba(255,255,255,0.8);
              transform: scale(1.3);
            }

            .nav-arrow:disabled {
              color: rgba(255,255,255,0.3);
              cursor: not-allowed;
            }

            .listening-progress-dots {
              display: flex;
              gap: 8px;
              align-items: center;
              position: relative; /* 절대 위치의 자식 요소를 위한 기준점 */
            }

            /* 속도 조절 버튼 */
            .speed-control-btn {
              background: transparent;
              border: none;
              color: white;
              padding: 5px 12px;
              border-radius: 14px;
              cursor: pointer;
              font-size: 12px;
              font-weight: 600;
              transition: all 0.3s ease;
              min-width: 50px;
              text-align: center;
              box-shadow: none;
            }

            .speed-control-btn:hover {
              background: rgba(255,255,255,0.2);
              transform: scale(1.08);
              box-shadow: none;
            }

            .speed-control-btn:active {
              transform: scale(0.96);
              box-shadow: none;
            }

            /* 자막 토글 버튼 */
            #subtitleToggleBtn:hover {
              background: rgba(255,255,255,0.2) !important;
              transform: scale(1.08);
              box-shadow: none;
            }

            #subtitleToggleBtn:active {
              transform: scale(0.96) !important;
            }
          </style>
          
          <div class="listening-test-container minimized" id="listeningContainer">
            <div class="listening-header">
              <div style="display: flex; align-items: center; gap: 8px;">
                <div class="listening-progress" id="listeningProgress">
                  🎧 구간 1/'.$sectionCount.'
                </div>
                <button onclick="toggleSubtitles()" id="subtitleToggleBtn" style="background: transparent; border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; padding: 0;" title="자막 보기/숨기기">📄</button>
              </div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <!-- Speed Control Button -->
                <button class="speed-control-btn" id="speedControlBtn" onclick="cyclePlaybackSpeed()" title="재생 속도 조절">1.0x</button>

                <!-- Audio Mode Toggle Button -->
                <div id="audioModeToggleContainer" style="display: none; align-items: center; gap: 4px;">
                  <button id="audioModeToggle"
                          class="audio-mode-toggle"
                          data-mode="section"
                          type="button"
                          title="오디오 재생 모드 전환">
                    <span id="audioModeLabel">━━</span>
                  </button>
                </div>
                <button class="listening-minimize-btn" id="minimizeBtn" onclick="toggleListeningPlayer()">+</button>
              </div>
            </div>
            <div class="listening-body">
              <audio id="audioPlayer2" class="listening-audio-hidden" controls src="'.$sectionFiles[0].'">
              </audio>';
            
          foreach($textSections as $idx => $text) {
            $num = $idx + 1;
            $activeClass = ($idx === 0) ? 'active' : '';
            $displayText = mb_substr($text, 0, 150) . (mb_strlen($text) > 150 ? '...' : '');
            $audiocnt .= '
              <div class="listening-text-display '.$activeClass.'" id="listeningText'.$num.'">
                '.htmlspecialchars($displayText).'
              </div>';
          }

          // Full audio subtitle container (전체재생모드 자막)
          $audiocnt .= '
              <div id="fullAudioSubtitleContainer" style="
                min-height: 120px;
                max-height: 120px;
                overflow-y: auto;
                background: rgba(255,255,255,0.95);
                border-left: 4px solid #4CAF50;
                padding: 12px;
                margin: 0 0 12px 0;
                border-radius: 8px;
                font-size: 13px;
                line-height: 1.6;
                color: #333;
                display: none;
              ">'.$fullAudioSubtitle.'</div>';

          // Progress dots with navigation arrows
          $audiocnt .= '
              <div class="listening-progress-dots" id="progressDots">
                <button class="nav-arrow" id="prevSectionBtn" title="이전 구간">◀</button>';

          for($i = 0; $i < $sectionCount; $i++) {
            $activeClass = ($i === 0) ? 'active' : '';
            $audiocnt .= '<div class="progress-dot '.$activeClass.'" data-section="'.$i.'" title="구간 '.($i+1).'"></div>';
          }

          $audiocnt .= '
                <button class="nav-arrow" id="nextSectionBtn" title="다음 구간">▶</button>
                <button class="replay-section-btn" id="replaySectionBtn" title="상세보기 (새탭)">🔍</button>
              </div>

              <!-- Subtitle Container for Section Mode -->
              <div id="subtitle-container" class="subtitle-container" style="
                  min-height: 120px;
                  max-height: 300px;
                  overflow-y: auto;
                  background-color: #f5f5f5;
                  padding: 10px;
                  margin-top: 10px;
                  border-radius: 5px;
                  border: 1px solid #ddd;
                  display: none;">
                <div id="subtitle-text" style="
                    font-size: 14px;
                    line-height: 1.6;
                    color: #333;">
                </div>
              </div>
            </div>
          </div>

          <script>
            // 재생 속도 순환 함수 (1.0x → 1.25x → 1.5x → 1.75x → 2.0x → 1.0x) with localStorage
            let currentSpeedIndex = 0;
            const speedOptions = [1.0, 1.25, 1.5, 1.75, 2.0];
            const contentsId = "'.$contentsid.'";

            // 페이지 로드 시 저장된 속도 복원
            function restorePlaybackSpeed() {
              const savedSpeed = localStorage.getItem("mynote_playbackSpeed");
              const audioPlayer = document.getElementById("audioPlayer2");
              const speedBtn = document.getElementById("speedControlBtn");

              if (!audioPlayer || !speedBtn) {
                console.error("[Speed Restore] File: mynote.php, Line: 710, Elements not found");
                return;
              }

              if (savedSpeed) {
                const speed = parseFloat(savedSpeed);
                const speedIndex = speedOptions.indexOf(speed);

                if (speedIndex !== -1) {
                  currentSpeedIndex = speedIndex;
                  audioPlayer.playbackRate = speed;
                  speedBtn.textContent = speed.toFixed(2) + "x";
                  console.log("[Speed Restore] File: mynote.php, Line: 722, Restored speed:", speed);
                }
              }
            }

            function cyclePlaybackSpeed() {
              const audioPlayer = document.getElementById("audioPlayer2");
              const speedBtn = document.getElementById("speedControlBtn");

              if (!audioPlayer || !speedBtn) {
                console.error("[Speed Control] File: mynote.php, Line: 731, Elements not found");
                return;
              }

              // 다음 속도로 전환
              currentSpeedIndex = (currentSpeedIndex + 1) % speedOptions.length;
              const newSpeed = speedOptions[currentSpeedIndex];

              // 오디오 속도 적용
              audioPlayer.playbackRate = newSpeed;

              // 버튼 텍스트 업데이트
              speedBtn.textContent = newSpeed.toFixed(2) + "x";

              // localStorage에 저장
              localStorage.setItem("mynote_playbackSpeed", newSpeed.toString());

              console.log("[Speed Control] File: mynote.php, Line: 749, Speed changed and saved:", newSpeed);
            }

            // 페이지 로드 시 속도 복원 실행
            document.addEventListener("DOMContentLoaded", function() {
              restorePlaybackSpeed();
            });

            // 플레이어 최소화/최대화 토글
            function toggleListeningPlayer() {
              const container = document.getElementById("listeningContainer");
              const minimizeBtn = document.getElementById("minimizeBtn");
              const audioPlayer = document.getElementById("audioPlayer2");

              if(container.classList.contains("minimized")) {
                // 펼침: 최소화 해제
                container.classList.remove("minimized");
                minimizeBtn.textContent = "−";
                // 최대화 시 중앙 하단으로 재설정
                container.style.left = "50%";
                container.style.bottom = "20px";
                container.style.right = "auto";
                container.style.top = "auto";
                container.style.transform = "translateX(-50%)";

                // 자동 재생 (일시정지 상태인 경우 재생)
                if(audioPlayer && audioPlayer.paused) {
                  audioPlayer.play().catch(e => {
                    console.log("[Auto Resume] File: mynote.php, Line: 789, Blocked:", e);
                  });
                  console.log("[Interface] File: mynote.php, Line: 789, Player expanded - Auto resume audio");
                }
              } else {
                // 최소화: 자동 일시정지
                container.classList.add("minimized");
                minimizeBtn.textContent = "+";

                // 재생 중인 오디오 자동 일시정지
                if(audioPlayer && !audioPlayer.paused) {
                  audioPlayer.pause();
                  console.log("[Interface] File: mynote.php, Line: 789, Player minimized - Auto pause audio");
                }
              }
            }

            // 최소화/최대화 클릭 기능 (드래그 기능 제거됨)
            (function() {
              const container = document.getElementById("listeningContainer");

              container.addEventListener("click", function(e) {
                const excludeIds = ["audioModeToggle", "audioModeToggleContainer", "audioModeLabel",
                                    "fullAudioPlayBtn", "fullAudioPauseBtn", "minimizeBtn"];

                for(let id of excludeIds) {
                  const el = document.getElementById(id);
                  if(el && (e.target === el || el.contains(e.target))) {
                    return;
                  }
                }

                if(container.classList.contains("minimized") && e.target === container) {
                  toggleListeningPlayer();
                }
              });
            })();

            /* 드래그 앤 드롭 기능 - 비활성화됨 (우측 끝 고정 위치)
            (function() {
              let isDragging = false;
              let currentX;
              let currentY;
              let initialX;
              let initialY;
              let xOffset = 0;
              let yOffset = 0;
              
              const container = document.getElementById("listeningContainer");
              const header = container.querySelector(".listening-header");
              
               // 초기 위치 설정 (중앙 하단)
               const setInitialPosition = () => {
                 const rect = container.getBoundingClientRect();
                 xOffset = (window.innerWidth - rect.width) / 2;
                 yOffset = window.innerHeight - rect.height - 20;
                 container.style.left = "50%";
                 container.style.bottom = "20px";
                 container.style.right = "auto";
                 container.style.top = "auto";
                 container.style.transform = "translateX(-50%)";
               };
              
              // 마우스/터치 시작
              header.addEventListener("mousedown", dragStart);
              header.addEventListener("touchstart", dragStart);
              
              // 마우스/터치 이동
              document.addEventListener("mousemove", drag);
              document.addEventListener("touchmove", drag);
              
              // 마우스/터치 종료
              document.addEventListener("mouseup", dragEnd);
              document.addEventListener("touchend", dragEnd);
              
              function dragStart(e) {
                // Exclude buttons from dragging
                const excludeElements = [
                  document.getElementById("minimizeBtn"),
                  document.getElementById("audioModeToggle"),
                  document.getElementById("audioModeToggleContainer"),
                  document.getElementById("audioModeLabel")
                ];

                // Check if clicked element or its parent is a button to exclude
                for(let el of excludeElements) {
                  if(el && (e.target === el || el.contains(e.target))) {
                    return; // Don\'t start dragging for these elements
                  }
                }

                if(e.type === "touchstart") {
                  initialX = e.touches[0].clientX - xOffset;
                  initialY = e.touches[0].clientY - yOffset;
                } else {
                  initialX = e.clientX - xOffset;
                  initialY = e.clientY - yOffset;
                }

                if(e.target === header || header.contains(e.target)) {
                  isDragging = true;
                  container.style.transition = "none";
                }
              }
              
              function drag(e) {
                if(isDragging) {
                  e.preventDefault();
                  
                  if(e.type === "touchmove") {
                    currentX = e.touches[0].clientX - initialX;
                    currentY = e.touches[0].clientY - initialY;
                  } else {
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                  }
                  
                  xOffset = currentX;
                  yOffset = currentY;
                  
                  // position을 fixed로 유지하면서 top, left 사용
                  container.style.right = "auto";
                  container.style.bottom = "auto";
                  container.style.transform = "none";
                  container.style.left = currentX + "px";
                  container.style.top = currentY + "px";
                }
              }
              
              function dragEnd(e) {
                if(isDragging) {
                  initialX = currentX;
                  initialY = currentY;
                  isDragging = false;
                  container.style.transition = "all 0.3s ease";
                }
              }
              
              // 플레이어를 클릭하면 최대화 (버튼 클릭은 제외)
              container.addEventListener("click", function(e) {
                // Exclude our custom buttons from triggering minimize/maximize
                const excludeIds = ["audioModeToggle", "audioModeToggleContainer", "audioModeLabel",
                                    "fullAudioPlayBtn", "fullAudioPauseBtn", "minimizeBtn"];

                // Check if clicked element is one of our buttons
                for(let id of excludeIds) {
                  const el = document.getElementById(id);
                  if(el && (e.target === el || el.contains(e.target))) {
                    return; // Don\'t toggle for button clicks
                  }
                }

                if(container.classList.contains("minimized") && e.target === container) {
                  toggleListeningPlayer();
                }
              });
              
              // 페이지 로드 시 초기 위치 설정
              window.addEventListener("load", setInitialPosition);
            })();
            */

            // 자막 표시/숨기기 토글 함수 (구간별 + 전체재생 모두 지원)
            // 전역 자막 상태 변수 초기화 (localStorage에서 복원)
            if (typeof window.subtitlesVisible === "undefined") {
              const savedSubtitleState = localStorage.getItem("mynote_subtitlesVisible");
              window.subtitlesVisible = savedSubtitleState !== null ? (savedSubtitleState === "true") : false;
              console.log("[Settings] File: mynote.php, Line: 956, Subtitle state restored from localStorage (default: closed):", window.subtitlesVisible);
            }

            window.toggleSubtitles = function() {
              // 자막 상태 토글
              window.subtitlesVisible = !window.subtitlesVisible;

              // localStorage에 저장
              localStorage.setItem("mynote_subtitlesVisible", window.subtitlesVisible);
              console.log("[Settings] File: mynote.php, Line: 850, Subtitle state saved to localStorage:", window.subtitlesVisible);

              // 구간별 재생 자막 요소들
              const allSectionSubtitles = document.querySelectorAll(".listening-text-display");

              // 전체 재생 자막 컨테이너
              const fullAudioSubtitle = document.getElementById("fullAudioSubtitleContainer");

              if (window.subtitlesVisible) {
                // 자막 표시 모드
                console.log("[Subtitle Toggle] File: mynote.php, Line: 843, Action: Show All");

                // 구간별 재생: 현재 섹션의 자막 표시
                const currentSectionIndex = window.currentSection || 0;
                const subtitleToShow = document.getElementById("listeningText" + (currentSectionIndex + 1));
                if (subtitleToShow) {
                  subtitleToShow.classList.add("active");
                }

                // 전체 재생: 현재 모드가 전체 재생인 경우 자막 표시
                if (fullAudioSubtitle) {
                  // 현재 오디오 모드 확인 (data-audio-mode 속성)
                  const currentMode = document.body.getAttribute("data-audio-mode");
                  if (currentMode === "full") {
                    fullAudioSubtitle.style.display = "block";
                    console.log("[Subtitle Toggle] File: mynote.php, Line: 870, Full Audio Subtitle: Show");
                  }
                }
              } else {
                // 자막 숨기기 모드
                console.log("[Subtitle Toggle] File: mynote.php, Line: 843, Action: Hide All");

                // 구간별 재생: 모든 자막 숨기기
                allSectionSubtitles.forEach(function(subtitle) {
                  subtitle.classList.remove("active");
                });

                // 전체 재생: 자막 숨기기
                if (fullAudioSubtitle) {
                  fullAudioSubtitle.style.display = "none";
                }
              }
            };

            (function() {
              window.sectionFiles = '.json_encode($sectionFiles).';
              window.sectionCount = '.$sectionCount.';
              window.currentSection = 0;
              const sectionFiles = window.sectionFiles;
              const sectionCount = window.sectionCount;
              let currentSection = window.currentSection;
              let audioPlayer2 = document.getElementById("audioPlayer2");
              let prevBtn = document.getElementById("prevSectionBtn");
              let nextBtn = document.getElementById("nextSectionBtn");
              let progress = document.getElementById("listeningProgress");
              
              // 오디오 재생 종료 이벤트
              audioPlayer2.addEventListener("ended", function() {
                if(currentSection < sectionCount - 1) {
                  // 다음 구간이 있으면 다음 버튼 활성화
                  nextBtn.disabled = false;
                } else {
                  // 마지막 구간 완료
                  progress.textContent = "✅ 완료!";
                  nextBtn.disabled = true;

                  // 모든 dots를 완료 상태로
                  const dots = document.querySelectorAll(".progress-dot");
                  dots.forEach(dot => {
                    dot.classList.remove("active");
                    dot.classList.add("completed");
                  });
                }

                // 이전 버튼 상태 업데이트
                prevBtn.disabled = (currentSection === 0);
              });
              
              // 버튼 상태 업데이트 함수
              function updateButtonStates() {
                prevBtn.disabled = (currentSection === 0);
                nextBtn.disabled = (currentSection >= sectionCount - 1);
                console.log("[Button States] File: mynote.php, Line: 931, Prev:", prevBtn.disabled, "Next:", nextBtn.disabled);
              }

              // 섹션 전환 함수
              function switchToSection(newSection) {
                if(newSection < 0 || newSection >= sectionCount || newSection === currentSection) {
                  return;
                }

                console.log("[Section Switch] File: mynote.php, Line: 941, From:", currentSection, "To:", newSection);

                // 현재 텍스트 숨기기
                const currentTextDiv = document.getElementById("listeningText"+(currentSection+1));
                if(currentTextDiv) {
                  currentTextDiv.classList.remove("active");
                }

                // 새 구간으로 이동
                currentSection = newSection;
                window.currentSection = currentSection;

                // 새 텍스트 표시 (자막 상태 확인)
                const newTextDiv = document.getElementById("listeningText"+(currentSection+1));
                if(newTextDiv && window.subtitlesVisible) {
                  newTextDiv.classList.add("active");
                  console.log("[Section Switch] File: mynote.php, Line: 1124, Subtitle shown for section:", currentSection+1);
                } else if(newTextDiv) {
                  console.log("[Section Switch] File: mynote.php, Line: 1124, Subtitle hidden (user preference)");
                }

                // 진행 상황 업데이트
                progress.textContent = "🎧 구간 "+(currentSection+1)+"/"+sectionCount;

                // 오디오 정지
                audioPlayer2.pause();
                audioPlayer2.currentTime = 0;

                // 새 오디오 로드 및 재생
                audioPlayer2.src = sectionFiles[currentSection];
                audioPlayer2.load();

                // 로드 완료 후 재생
                audioPlayer2.addEventListener("loadeddata", function playNext() {
                  // 저장된 속도 복원
                  const savedSpeed = localStorage.getItem("mynote_playbackSpeed");
                  if (savedSpeed) {
                    audioPlayer2.playbackRate = parseFloat(savedSpeed);
                    console.log("[Section Switch] File: mynote.php, Line: 1064, Speed restored:", savedSpeed);
                  }

                  audioPlayer2.play().catch(e => console.error("[Audio Error] File: mynote.php, Line: 1069, Error:", e));
                  audioPlayer2.removeEventListener("loadeddata", playNext);
                });

                // Progress dots 업데이트
                updateProgressDots();

                // 버튼 상태 업데이트
                updateButtonStates();
              }

              // 이전 버튼 클릭
              prevBtn.addEventListener("click", function() {
                console.log("[Prev Button] File: mynote.php, Line: 986, Section:", currentSection);
                if(currentSection > 0) {
                  switchToSection(currentSection - 1);
                }
              });

              // 다음 버튼 클릭
              nextBtn.addEventListener("click", function() {
                console.log("[Next Button] File: mynote.php, Line: 994, Section:", currentSection);
                if(currentSection < sectionCount - 1) {
                  switchToSection(currentSection + 1);
                }
              });
              
              // Progress dots 업데이트 함수
              function updateProgressDots() {
                const dots = document.querySelectorAll(".progress-dot");

                dots.forEach((dot, index) => {
                  dot.classList.remove("active", "completed");
                  if(index < currentSection) {
                    dot.classList.add("completed");
                  } else if(index === currentSection) {
                    dot.classList.add("active");
                  }
                });
              }
              
              // Progress dots 클릭 이벤트 - 현재 구간 클릭 시 다시 재생
              // 자막 데이터를 JavaScript 변수로 전달
              const textSectionsData = '.json_encode($textSections, JSON_UNESCAPED_UNICODE).';

              const progressDots = document.querySelectorAll(".progress-dot");
              progressDots.forEach((dot, index) => {
                // 단일 클릭: 현재 구간이면 다시 재생, 다른 구간이면 이동
                dot.addEventListener("click", function() {
                  // 현재 재생 중인 구간을 클릭한 경우: 다시 재생
                  if(index === currentSection) {
                    console.log("[Dot Click] File: mynote.php, Line: 1189, Replay current section:", currentSection);

                    // 현재 구간을 처음부터 다시 재생
                    if(audioPlayer2) {
                      audioPlayer2.currentTime = 0;

                      // 저장된 재생 속도 복원
                      const savedSpeed = localStorage.getItem("mynote_playbackSpeed");
                      if (savedSpeed) {
                        audioPlayer2.playbackRate = parseFloat(savedSpeed);
                        console.log("[Dot Click] File: mynote.php, Line: 1199, Speed restored:", savedSpeed);
                      }

                      audioPlayer2.play().catch(e => {
                        console.error("[Audio Error] File: mynote.php, Line: 1203, Replay error:", e);
                      });
                    }
                  } else {
                    // 다른 구간을 클릭한 경우: 해당 구간으로 이동
                    console.log("[Dot Click] File: mynote.php, Line: 1207, From:", currentSection, "To:", index);
                    switchToSection(index);
                  }
                });
              });

              // 슬라이드 오버레이 CSS 추가
              if (!document.getElementById("drilling-overlay-styles")) {
                const styleHTML = `
                  <style id="drilling-overlay-styles">
                    .drilling-overlay {
                      position: fixed;
                      top: 0;
                      left: 0;
                      width: 100%;
                      height: 100%;
                      background: rgba(0, 0, 0, 0.3);
                      z-index: 9999;
                      opacity: 0;
                      visibility: hidden;
                      transition: opacity 0.3s ease, visibility 0.3s ease;
                    }

                    .drilling-overlay.active {
                      opacity: 1;
                      visibility: visible;
                    }

                    .drilling-overlay.active .drilling-overlay-content {
                      transform: translateX(0);
                    }

                    .drilling-overlay-header {
                      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                      color: white;
                      padding: 20px 30px;
                      display: flex;
                      justify-content: space-between;
                      align-items: center;
                      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    }

                    .drilling-overlay-header h3 {
                      margin: 0;
                      font-size: 18px;
                      font-weight: 600;
                    }

                    .drilling-close-btn {
                      background: rgba(255, 255, 255, 0.2);
                      border: none;
                      color: white;
                      font-size: 24px;
                      width: 36px;
                      height: 36px;
                      border-radius: 50%;
                      cursor: pointer;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      transition: background 0.2s ease;
                    }

                    .drilling-close-btn:hover {
                      background: rgba(255, 255, 255, 0.3);
                    }

                    .drilling-overlay-content {
                      position: absolute;
                      top: 0;
                      right: 0;
                      width: 33.333%;
                      height: 100%;
                      background: white;
                      transform: translateX(100%);
                      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                      display: flex;
                      flex-direction: column;
                      box-shadow: -4px 0 12px rgba(0, 0, 0, 0.15);
                    }

                    .drilling-overlay-content iframe {
                      flex: 1;
                      width: 100%;
                      height: calc(100% - 76px);
                      border: none;
                    }

                    @media (max-width: 1200px) {
                      .drilling-overlay-content {
                        width: 50%;
                      }
                    }

                    @media (max-width: 768px) {
                      .drilling-overlay-content {
                        width: 80%;
                      }

                      .drilling-overlay-header {
                        padding: 15px 20px;
                      }

                      .drilling-overlay-header h3 {
                        font-size: 16px;
                      }

                      .drilling-close-btn {
                        width: 32px;
                        height: 32px;
                        font-size: 20px;
                      }
                    }

                    @media (max-width: 480px) {
                      .drilling-overlay-content {
                        width: 100%;
                      }
                    }
                  </style>
                `;
                document.head.insertAdjacentHTML("beforeend", styleHTML);
              }

              // 슬라이드 오버레이 HTML 추가
              if (!document.getElementById("drilling-overlay")) {
                const overlayHTML = `
                  <div id="drilling-overlay" class="drilling-overlay">
                    <div class="drilling-overlay-content">
                      <div class="drilling-overlay-header">
                        <h3>📝 상세보기</h3>
                        <button class="drilling-close-btn" onclick="closeDrillingOverlay()">✕</button>
                      </div>
                      <iframe id="drilling-iframe" frameborder="0"></iframe>
                    </div>
                  </div>
                `;
                document.body.insertAdjacentHTML("beforeend", overlayHTML);
              }

              // Replay 버튼 클릭 이벤트 - 슬라이드 오버레이로 변경
              const replayBtn = document.getElementById("replaySectionBtn");
              if(replayBtn) {
                replayBtn.addEventListener("click", function(e) {
                  e.stopPropagation();

                  // 현재 구간의 자막 텍스트 가져오기
                  const sectionText = textSectionsData[currentSection] || "";
                  const nstepValue = currentSection + 1;

                  console.log("[Replay Click] File: mynote.php, Line: 1391", {
                    contentsId: contentsId,
                    currentSection: currentSection,
                    nstep: nstepValue,
                    sectionText: sectionText.substring(0, 100),
                    textSectionsDataLength: textSectionsData.length
                  });

                  // URL 파라미터로 전달
                  // nstep: DB 구간 번호, section: 오디오 구간 번호 (현재는 동일하게 전달)
                  const url = "https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=" + contentsId +
                              "&ctype=1&nstep=" + nstepValue +
                              "&section=" + currentSection +
                              "&subtitle=" + encodeURIComponent(sectionText);

                  console.log("[Replay Click] File: mynote.php, Line: 1411, Full URL:", url);

                  // 오버레이에 URL 로드 및 표시
                  const overlay = document.getElementById("drilling-overlay");
                  const iframe = document.getElementById("drilling-iframe");

                  // iframe 캐시 방지: 먼저 비운 후 새 URL 로드
                  iframe.src = "about:blank";
                  setTimeout(() => {
                    iframe.src = url;
                    overlay.classList.add("active");
                    console.log("[Replay Click] File: mynote.php, Line: 1418, Overlay opened with nstep:", nstepValue);
                  }, 50);
                });
              }

              // 오버레이 닫기 함수 (전역)
              window.closeDrillingOverlay = function() {
                const overlay = document.getElementById("drilling-overlay");
                overlay.classList.remove("active");
                // iframe 언로드 (메모리 절약)
                setTimeout(() => {
                  document.getElementById("drilling-iframe").src = "about:blank";
                }, 300);
              };

              // 배경 클릭 시 오버레이 닫기
              const overlay = document.getElementById("drilling-overlay");
              if (overlay) {
                overlay.addEventListener("click", function(e) {
                  // 오버레이 배경 클릭 시에만 닫기 (content 영역 클릭은 무시)
                  if (e.target === overlay) {
                    closeDrillingOverlay();
                  }
                });
              }

              // ESC 키로 오버레이 닫기
              document.addEventListener("keydown", function(e) {
                if (e.key === "Escape") {
                  const overlay = document.getElementById("drilling-overlay");
                  if (overlay && overlay.classList.contains("active")) {
                    closeDrillingOverlay();
                  }
                }
              });

              // 초기 버튼 상태 설정
              updateButtonStates();

              // 첫 구간 자동 재생 비활성화 (사용자가 수동으로 재생 버튼 클릭 필요)
              // setTimeout(function() {
              //   audioPlayer2.play().catch(e => console.log("[Auto Play] File: mynote.php, Line: 1026, Blocked:", e));
              // }, 500);
              console.log("[Auto Play] File: mynote.php, Line: 1149, Auto-play disabled - user must click play button");
            })();
          </script>';
          
        } else {
          // reflections1이 없는 경우: 단일 파일 모드로 듣기평가 인터페이스 표시
          $sectionFiles = array($audioUrlToUse);
          $textSections = array('오디오 파일');
          $sectionCount = 1;

          $audiocnt .= '
          <style>
             /* 중앙 하단 플로팅 미니 플레이어 */
             .listening-test-container {
               position: fixed;
               bottom: 20px;
               left: 50%;
               transform: translateX(-50%);
               width: 320px;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              border-radius: 16px;
              box-shadow: 0 10px 40px rgba(0,0,0,0.3);
              padding: 0;
              z-index: 9999;
              transition: all 0.3s ease;
              cursor: default;
            }

            .listening-test-container.minimized {
              width: 60px;
              height: 60px;
              border-radius: 50%;
              cursor: pointer;
              transform: translateX(-50%);
            }

            .listening-test-container.minimized .listening-header {
              display: none;
            }

            .listening-test-container.minimized .listening-body {
              display: none;
            }

            .listening-test-container.minimized::before {
              content: "🎧";
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              font-size: 28px;
              line-height: 1;
            }

            .listening-header {
              background: rgba(255,255,255,0.1);
              padding: 12px 16px;
              border-radius: 16px 16px 0 0;
              display: flex;
              justify-content: space-between;
              align-items: center;
              cursor: move;
            }

            .listening-progress {
              font-size: 13px;
              font-weight: 600;
              color: white;
              margin: 0;
            }

            .listening-minimize-btn {
              background: rgba(255,255,255,0.2);
              border: none;
              color: white;
              width: 24px;
              height: 24px;
              border-radius: 50%;
              cursor: pointer;
              font-size: 16px;
              line-height: 1;
              transition: all 0.2s;
            }

            .listening-minimize-btn:hover {
              background: rgba(255,255,255,0.3);
              transform: scale(1.1);
            }

            .listening-body {
              padding: 16px;
            }

            .listening-audio-hidden {
              width: 100%;
              height: 40px;
              margin-bottom: 12px;
              border-radius: 8px;
              display: none; /* 기본 숨김 */
            }

            /* 전체재생 모드일 때는 기본 audio player 표시 */
            body[data-audio-mode="full"] .listening-audio-hidden {
              display: block !important;
            }

            /* 전체 재생 모드: 전체 컨테이너를 flexbox로 변경하여 헤더와 본문 순서 조정 */
            body[data-audio-mode="full"] .listening-test-container {
              display: flex;
              flex-direction: column;
              height: auto; /* 내용에 맞춰 자동 조정 */
              max-height: 400px; /* 최대 높이 제한 */
              transition: all 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); /* 부드러운 전환 애니메이션 */
            }

            /* 전체 재생 모드: 헤더(플레이바)를 하단으로 이동 */
            body[data-audio-mode="full"] .listening-header {
              order: 2;
              flex-shrink: 0; /* 축소 방지 */
              border-radius: 0 0 16px 16px; /* 하단 모서리만 둥글게 */
              transition: all 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); /* 부드러운 전환 */
            }

            /* 전체 재생 모드: 본문(자막 영역)을 상단으로 이동 */
            body[data-audio-mode="full"] .listening-body {
              order: 1;
              flex: 1; /* 남은 공간 차지 */
              min-height: 0; /* flex 축소 허용 */
              overflow-y: auto; /* 스크롤 활성화 */
              display: flex;
              flex-direction: column;
              border-radius: 16px 16px 0 0; /* 상단 모서리만 둥글게 */
              transition: all 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); /* 부드러운 전환 */
            }

            body[data-audio-mode="full"] #audioPlayer2 {
              order: 2;
              margin-top: 12px;
              margin-bottom: 0;
              flex-shrink: 0; /* 오디오 플레이어 크기 고정 */
            }

            body[data-audio-mode="full"] #fullAudioSubtitleContainer {
              order: 1;
              flex: 1; /* 가용 공간 차지 */
              overflow-y: auto; /* 긴 자막 스크롤 */
              min-height: 80px; /* 최소 높이 */
              max-height: 250px; /* 최대 높이 */
              margin-bottom: 0;
            }

            /* 단계별 모드일 때는 오디오 플레이어 숨김 */
            body[data-audio-mode="section"] .listening-audio-hidden {
              display: none !important;
            }

            .listening-progress-dots {
              margin: 0 0 10px 0; /* 상단 마진 제거하여 위로 이동 */
              padding: 0;
              display: flex;
              justify-content: center;
              gap: 8px;
              list-style: none;
              position: relative; /* 절대 위치의 자식 요소를 위한 기준점 */
            }

            .progress-dot {
              width: 12px;
              height: 12px;
              border-radius: 50%;
              background: rgba(255,255,255,0.3);
              cursor: pointer;
              transition: all 0.3s ease;
              border: 2px solid transparent;
            }

            .progress-dot.active {
              background: white;
              transform: scale(1.3);
              box-shadow: 0 0 10px rgba(255,255,255,0.5);
            }

            .progress-dot.completed {
              background: rgba(76, 175, 80, 0.8);
              border-color: white;
            }

            .progress-dot:hover {
              transform: scale(1.2);
              background: rgba(255,255,255,0.6);
            }

            .listening-nav-buttons {
              display: flex;
              justify-content: center;
              align-items: center;
              gap: 12px;
              margin-top: 12px;
            }

            .nav-btn {
              background: rgba(255,255,255,0.2);
              border: 1px solid rgba(255,255,255,0.4);
              color: white;
              width: 36px;
              height: 36px;
              border-radius: 50%;
              cursor: pointer;
              font-size: 16px;
              display: flex;
              align-items: center;
              justify-content: center;
              transition: all 0.2s;
              padding: 0;
            }

            .nav-btn:hover:not(:disabled) {
              background: rgba(255,255,255,0.3);
              transform: scale(1.1);
            }

            .nav-btn:disabled {
              opacity: 0.3;
              cursor: not-allowed;
            }

            .speed-control-btn {
              background: transparent;
              border: none;
              color: white;
              padding: 5px 12px;
              border-radius: 14px;
              cursor: pointer;
              font-size: 12px;
              font-weight: 600;
              transition: all 0.3s ease;
              min-width: 50px;
              text-align: center;
              box-shadow: none;
            }

            .speed-control-btn:hover {
              background: rgba(255,255,255,0.2);
              transform: scale(1.05);
            }

            #subtitleToggleBtn:hover {
              background: rgba(255,255,255,0.2) !important;
              transform: scale(1.08);
              box-shadow: none;
            }
          </style>

          <div class="listening-test-container minimized" id="listeningContainer" onclick="handleContainerClick(event)">
            <div class="listening-header" id="listeningHeader">
              <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                <button class="speed-control-btn" id="speedControlBtn" onclick="event.stopPropagation(); cyclePlaybackSpeed();" title="재생 속도 조절">1.0x</button>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <div class="listening-progress" id="listeningProgress">
                    🎧 전체 재생
                  </div>
                  <button onclick="event.stopPropagation(); toggleSubtitles();" id="subtitleToggleBtn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; padding: 0;" title="자막 보기/숨기기">📄</button>
                </div>
              </div>
              <button class="listening-minimize-btn" id="minimizeBtn" onclick="event.stopPropagation(); toggleListeningPlayer();">+</button>
            </div>
            <div class="listening-body">
              <audio id="audioPlayer2" controls src="'.$audioUrlToUse.'" style="width: 100%; height: 40px; margin-bottom: 12px; border-radius: 8px;">
              </audio>

              <!-- Subtitle Container for Full Audio Mode -->
              <div id="subtitle-container" class="subtitle-container" style="
                  min-height: 120px;
                  max-height: 300px;
                  overflow-y: auto;
                  background-color: #f5f5f5;
                  padding: 10px;
                  margin-top: 10px;
                  border-radius: 5px;
                  border: 1px solid #ddd;
                  display: none;">
                <div id="subtitle-text" style="
                    font-size: 14px;
                    line-height: 1.6;
                    color: #333;">
                </div>
              </div>
            </div>
          </div>

        <script>
          (function() {
            const audioPlayer2 = document.getElementById("audioPlayer2");
            const listeningContainer = document.getElementById("listeningContainer");

            // 속도 조절 관련 변수
            let currentSpeedIndex = 0;
            const speedOptions = [1.0, 1.25, 1.5, 1.75, 2.0];
            const contentsId = "'.$contentsid.'";

            // 페이지 로드 시 저장된 속도 복원
            function restorePlaybackSpeed() {
              const savedSpeed = localStorage.getItem("mynote_playbackSpeed");
              const speedBtn = document.getElementById("speedControlBtn");

              if (savedSpeed) {
                const speed = parseFloat(savedSpeed);
                const speedIndex = speedOptions.indexOf(speed);
                if (speedIndex !== -1) {
                  currentSpeedIndex = speedIndex;
                  audioPlayer2.playbackRate = speed;
                  speedBtn.textContent = speed.toFixed(2) + "x";
                }
              }
            }

            // 속도 조절 버튼 클릭
            window.cyclePlaybackSpeed = function() {
              currentSpeedIndex = (currentSpeedIndex + 1) % speedOptions.length;
              const newSpeed = speedOptions[currentSpeedIndex];
              const speedBtn = document.getElementById("speedControlBtn");

              audioPlayer2.playbackRate = newSpeed;
              speedBtn.textContent = newSpeed.toFixed(2) + "x";
              localStorage.setItem("mynote_playbackSpeed", newSpeed.toString());
            };

            // 플레이어 최소화/펼치기
            window.toggleListeningPlayer = function() {
              const isMinimized = listeningContainer.classList.contains("minimized");
              listeningContainer.classList.toggle("minimized");
              const btn = document.getElementById("minimizeBtn");
              btn.textContent = listeningContainer.classList.contains("minimized") ? "+" : "−";

              // 오디오 자동 재생/일시정지
              if(isMinimized) {
                // 펼침: 자동 재생 (일시정지 상태인 경우 재생)
                if(audioPlayer2 && audioPlayer2.paused) {
                  audioPlayer2.play().catch(e => {
                    console.log("[Auto Resume] File: mynote.php, Line: 1450, Blocked:", e);
                  });
                  console.log("[Interface] File: mynote.php, Line: 1450, Player expanded - Auto resume audio");
                }
              } else {
                // 최소화: 자동 일시정지
                if(audioPlayer2 && !audioPlayer2.paused) {
                  audioPlayer2.pause();
                  console.log("[Interface] File: mynote.php, Line: 1450, Player minimized - Auto pause audio");
                }
              }
            };

            // 컨테이너 클릭 처리 (최소화 상태에서만 펼치기)
            window.handleContainerClick = function(event) {
              if (listeningContainer.classList.contains("minimized")) {
                event.stopPropagation();
                toggleListeningPlayer();
              }
            };

            // 자막 데이터 (reflections0에서 가져옴)
            const fullSubtitleText = '.json_encode($fullAudioSubtitle, JSON_UNESCAPED_UNICODE).';

            // 자막 상태 초기화
            if (typeof window.subtitlesVisible === "undefined") {
              const savedSubtitleState = localStorage.getItem("mynote_subtitlesVisible");
              window.subtitlesVisible = savedSubtitleState !== null ? (savedSubtitleState === "true") : false;
            }

            // 자막 토글 함수 (전역 함수가 없을 경우에만 정의)
            if (typeof window.toggleSubtitles === "undefined") {
              window.toggleSubtitles = function() {
                window.subtitlesVisible = !window.subtitlesVisible;
                localStorage.setItem("mynote_subtitlesVisible", window.subtitlesVisible);

                const subtitleContainer = document.getElementById("subtitle-container");
                if (subtitleContainer) {
                  subtitleContainer.style.display = window.subtitlesVisible ? "block" : "none";
                }
              };
            }

            // 페이지 로드 시 속도 복원 및 자막 데이터 로드
            document.addEventListener("DOMContentLoaded", function() {
              restorePlaybackSpeed();

              // 자막 데이터를 subtitle-container에 로드
              const subtitleContainer = document.getElementById("subtitle-container");
              const subtitleText = document.getElementById("subtitle-text");

              if (subtitleText && fullSubtitleText) {
                subtitleText.textContent = fullSubtitleText;
              }

              // 자막 초기 상태 적용
              if (subtitleContainer) {
                subtitleContainer.style.display = window.subtitlesVisible ? "block" : "none";
              }
            });

            // 자동 재생 비활성화 (사용자가 수동으로 재생 버튼 클릭 필요)
            // setTimeout(function() {
            //   audioPlayer2.play().catch(e => console.log("[Auto Play] Blocked:", e));
            // }, 500);
            console.log("[Auto Play] File: mynote.php, Line: 1488, Auto-play disabled - user must click play button");
          })();
        </script>';
        }
      }

      $audiocnt .= '
      </div>
      <script>
        // Audio 변형 기능 추가
        const audioPlayer = document.getElementById("audioPlayer");
        const audioPlayer2 = document.getElementById("audioPlayer2");
        const audioWrapper1 = document.getElementById("audioWrapper1");
        const audioWrapper2 = document.getElementById("audioWrapper2");
        const speedWrapper1 = document.getElementById("speedWrapper1");
        const speedWrapper2 = document.getElementById("speedWrapper2");
        const reviewWrapper1 = document.getElementById("reviewWrapper1");
        const reviewWrapper2 = document.getElementById("reviewWrapper2");
        const tooltip1 = document.getElementById("tooltip1");
        const tooltip2 = document.getElementById("tooltip2");

        let tooltipTimeout1 = null;
        let tooltipTimeout2 = null;

        function blurPlayer2() {
          // Blur player 2 and its controls
          if(audioWrapper2) {
            audioWrapper2.classList.add("blurred");
          }
          if(speedWrapper2) {
            speedWrapper2.classList.add("blurred");
          }
          if(reviewWrapper2) {
            reviewWrapper2.classList.add("blurred");
          }
          // Ensure player 1 is not blurred
          if(audioWrapper1) {
            audioWrapper1.classList.remove("blurred");
          }
          if(speedWrapper1) {
            speedWrapper1.classList.remove("blurred");
          }
          if(reviewWrapper1) {
            reviewWrapper1.classList.remove("blurred");
          }
        }

        function blurPlayer1() {
          // Blur player 1 and its controls
          if(audioWrapper1) {
            audioWrapper1.classList.add("blurred");
          }
          if(speedWrapper1) {
            speedWrapper1.classList.add("blurred");
          }
          if(reviewWrapper1) {
            reviewWrapper1.classList.add("blurred");
          }
          // Ensure player 2 is not blurred
          if(audioWrapper2) {
            audioWrapper2.classList.remove("blurred");
          }
          if(speedWrapper2) {
            speedWrapper2.classList.remove("blurred");
          }
          if(reviewWrapper2) {
            reviewWrapper2.classList.remove("blurred");
          }
        }

        function removeAllBlur() {
          // Remove blur from all elements
          if(audioWrapper1) {
            audioWrapper1.classList.remove("blurred");
          }
          if(audioWrapper2) {
            audioWrapper2.classList.remove("blurred");
          }
          if(speedWrapper1) {
            speedWrapper1.classList.remove("blurred");
          }
          if(speedWrapper2) {
            speedWrapper2.classList.remove("blurred");
          }
          if(reviewWrapper1) {
            reviewWrapper1.classList.remove("blurred");
          }
          if(reviewWrapper2) {
            reviewWrapper2.classList.remove("blurred");
          }
        }

        if(audioPlayer) {
          // Tooltip event handlers for player 1
          audioPlayer.addEventListener("mouseover", function() {
            if(tooltip1) {
              // Clear any existing timeout
              if(tooltipTimeout1) {
                clearTimeout(tooltipTimeout1);
              }

              // Show tooltip
              tooltip1.classList.add("show");

              // Hide after 3 seconds
              tooltipTimeout1 = setTimeout(function() {
                tooltip1.classList.remove("show");
              }, 3000);
            }
          });

          audioPlayer.addEventListener("mouseout", function() {
            if(tooltip1) {
              // Clear timeout and hide tooltip immediately on mouse out
              if(tooltipTimeout1) {
                clearTimeout(tooltipTimeout1);
              }
              tooltip1.classList.remove("show");
            }
          });

          audioPlayer.addEventListener("play", function () {
            // 랜덤 속도 변형 (0.95배 ~ 1.05배)
            const playbackRate = Math.random() * 0.2 + 1;
            audioPlayer.playbackRate = playbackRate;
            console.log("Playback rate set to:", playbackRate);

            // Blur player 2 when player 1 plays
            blurPlayer2();

            // Pause other audio if playing
            if(audioPlayer2 && !audioPlayer2.paused) {
              audioPlayer2.pause();
            }
          });

          audioPlayer.addEventListener("pause", function() {
            // Remove blur if no other audio is playing
            if(!audioPlayer2 || audioPlayer2.paused) {
              removeAllBlur();
            }
          });

          audioPlayer.addEventListener("ended", function() {
            this.currentTime = 0;
            this.play();
            swal("", "OK ! 한 번 더 들어보세요 ! (3번씩 추천!)", {buttons: false,timer: 3000});
            var Wboardid= \''.$wboardid.'\';
            var Contentstitle= \''.$contentstitle.'\';
            $.ajax({
              url:"check_status.php",
              type: "POST",
              dataType:"json",
              data : {
                "eventid":6,
                "wboardid":Wboardid,
                "contentstitle":Contentstitle,
              },
              success:function(data){}
            });
          });
        }

        if(audioPlayer2) {
          // 듣기평가 모드인지 확인 (화살표 버튼 존재 여부로 판단)
          const isListeningTestMode = document.getElementById("prevSectionBtn") !== null;

          if(!isListeningTestMode) {
            // 일반 모드: 기존 기능 유지
            // Tooltip event handlers for player 2
            audioPlayer2.addEventListener("mouseover", function() {
              if(tooltip2) {
                // Clear any existing timeout
                if(tooltipTimeout2) {
                  clearTimeout(tooltipTimeout2);
                }

                // Show tooltip
                tooltip2.classList.add("show");

                // Hide after 3 seconds
                tooltipTimeout2 = setTimeout(function() {
                  tooltip2.classList.remove("show");
                }, 3000);
              }
            });

            audioPlayer2.addEventListener("mouseout", function() {
              if(tooltip2) {
                // Clear timeout and hide tooltip immediately on mouse out
                if(tooltipTimeout2) {
                  clearTimeout(tooltipTimeout2);
                }
                tooltip2.classList.remove("show");
              }
            });

            audioPlayer2.addEventListener("play", function () {
              // 랜덤 속도 변형 (0.95배 ~ 1.05배)
              const playbackRate = Math.random() * 0.2 + 1;
              audioPlayer2.playbackRate = playbackRate;
              console.log("Playback rate set to:", playbackRate);

              // Blur player 1 when player 2 plays
              blurPlayer1();

              // Pause other audio if playing
              if(audioPlayer && !audioPlayer.paused) {
                audioPlayer.pause();
              }
            });

            audioPlayer2.addEventListener("pause", function() {
              // Remove blur if no other audio is playing
              if(!audioPlayer || audioPlayer.paused) {
                removeAllBlur();
              }
            });

            audioPlayer2.addEventListener("ended", function() {
              this.currentTime = 0;
              this.play();
              swal("", "OK ! 한 번 더 들어보세요 ! (3번씩 추천!)", {buttons: false,timer: 3000});
              var Wboardid= \''.$wboardid.'\';
              var Contentstitle= \''.$contentstitle.'\';
              $.ajax({
                url:"check_status.php",
                type: "POST",
                dataType:"json",
                data : {
                  "eventid":6,
                  "wboardid":Wboardid,
                  "contentstitle":Contentstitle,
                },
                success:function(data){}
              });
            });
          } else {
            // 듣기평가 모드: 다른 플레이어와 상호작용만 처리
            audioPlayer2.addEventListener("play", function () {
              // Pause other audio if playing
              if(audioPlayer && !audioPlayer.paused) {
                audioPlayer.pause();
              }
            });
          }
        }

        document.addEventListener("dragstart", function(e) {
          e.preventDefault();
        });
        document.addEventListener("selectstart", function(e) {
          e.preventDefault();
        });

        document.addEventListener("DOMContentLoaded", function() {
          const audioPlayer = document.getElementById("audioPlayer");
          const speedSlider = document.getElementById("speedSlider");
          const audioPlayer2 = document.getElementById("audioPlayer2");
          const speedSlider2 = document.getElementById("speedSlider2");

          if(audioPlayer) {
            audioPlayer.addEventListener("error", function() {
              console.error("Error loading audio file. Please check the audio URL.");
            });
          }

          if(speedSlider) {
            let speedTooltipTimeout1 = null;

            speedSlider.addEventListener("input", function() {
              const playbackRate = parseFloat(this.value);
              if(audioPlayer) audioPlayer.playbackRate = playbackRate;

              // 툴팁 표시
              const speedTooltip = document.getElementById("speedTooltip1");
              if(speedTooltip) {
                speedTooltip.textContent = playbackRate.toFixed(1) + "x";
                speedTooltip.style.opacity = "1";
                speedTooltip.style.visibility = "visible";

                // 기존 타이머 클리어
                if(speedTooltipTimeout1) {
                  clearTimeout(speedTooltipTimeout1);
                }

                // 2초 후 툴팁 숨김
                speedTooltipTimeout1 = setTimeout(function() {
                  speedTooltip.style.opacity = "0";
                  speedTooltip.style.visibility = "hidden";
                }, 2000);
              }
            });
          }

          if(audioPlayer2) {
            audioPlayer2.addEventListener("error", function() {
              console.error("Error loading audio file 2. Please check the audio URL.");
            });
          }

          if(speedSlider2) {
            let speedTooltipTimeout2 = null;

            speedSlider2.addEventListener("input", function() {
              const playbackRate = parseFloat(this.value);
              if(audioPlayer2) audioPlayer2.playbackRate = playbackRate;

              // 툴팁 표시
              const speedTooltip = document.getElementById("speedTooltip2");
              if(speedTooltip) {
                speedTooltip.textContent = playbackRate.toFixed(1) + "x";
                speedTooltip.style.opacity = "1";
                speedTooltip.style.visibility = "visible";

                // 기존 타이머 클리어
                if(speedTooltipTimeout2) {
                  clearTimeout(speedTooltipTimeout2);
                }

                // 2초 후 툴팁 숨김
                speedTooltipTimeout2 = setTimeout(function() {
                  speedTooltip.style.opacity = "0";
                  speedTooltip.style.visibility = "hidden";
                }, 2000);
              }
            });
          }
        });
      </script>';
    }

    if(strpos($cnttext->reflections0,'수학 풍경')!==false && $thisboard->id==NULL) {
     $DB->execute("INSERT INTO {abessi_messages} (userid, userto, userrole, talkid, nstep,   student_check, status, contentstype, wboardid, contentstitle, contentsid, url, timemodified, timecreated)
      VALUES ('$studentid','2','$role','2','0','0','begintopic','1','$wboardid','inspecttopic','$contentsid','$mynoteurl2','$timecreated','$timecreated')");
      echo '<script> 
      // iframe에서 부모 창으로 메시지 수신하는 이벤트 리스너 추가
      window.addEventListener("message", function(event) {
        // 메시지가 "refreshParent"일 경우 부모 창 새로고침
        if (event.data === "refreshParent") {
          window.location.reload();
        }
      }, false);
      
      document.addEventListener("DOMContentLoaded", function() {
        if (typeof Swal !== "undefined") {
          Swal.fire({
                backdrop: true,
                position:"center",
                showConfirmButton: false,
                width: "100%",
                height: "100%",
                heightAuto: false,
                allowOutsideClick: false,
                customClass: {
                    container: "swal-container-fullscreen",
                    popup: "swal-popup-fullscreen"
                },
                html:
                \'<table align="center" style="width:100%; height:100%; margin:0; padding:0;"><tr><td style="width:100%; height:100%; margin:0; padding:0;"><iframe id="mathgrowthFrame" style="border: none; width:100%; height:100vh; margin:0; padding:0; position:fixed; top:0; left:0;" src="https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/mathgrowthmind.php?id='.$studentid.'&contentsid='.$contentsid.'&contentstype=1&parentrefresh=true" ></iframe></td></tr></table>\'
                + \'<script>\'
                + \'  // iframe이 로드된 후에 실행\\n\'
                + \'  document.getElementById("mathgrowthFrame").onload = function() {\\n\'
                + \'    try {\\n\'
                + \'      // iframe 내부의 문서에 접근(동일 도메인일 경우만 가능)\\n\'
                + \'      var iframeWindow = this.contentWindow;\\n\'
                + \'      var iframeDoc = iframeWindow.document;\\n\'
                + \'      \\n\'
                + \'      // 다양한 방법으로 시작하기 버튼을 찾아보기\\n\'
                + \'      var startButtons = [];\\n\'
                + \'      // 1. 버튼 요소 중 시작하기 텍스트가 포함된 것 찾기\\n\'
                + \'      var allButtons = iframeDoc.querySelectorAll("button, input[type=button], input[type=submit], a.btn");\\n\'
                + \'      for(var i=0; i < allButtons.length; i++) {\\n\'
                + \'        var btn = allButtons[i];\\n\'
                + \'        if(btn.textContent && (btn.textContent.indexOf("시작") !== -1 || btn.textContent.indexOf("start") !== -1 || btn.textContent.toLowerCase().indexOf("start") !== -1)) {\\n\'
                + \'          startButtons.push(btn);\\n\'
                + \'        }\\n\'
                + \'        if(btn.value && (btn.value.indexOf("시작") !== -1 || btn.value.indexOf("start") !== -1 || btn.value.toLowerCase().indexOf("start") !== -1)) {\\n\'
                + \'          startButtons.push(btn);\\n\'
                + \'        }\\n\'
                + \'      }\\n\'
                + \'      \\n\'
                + \'      // 2. 특정 클래스나 ID로 찾기\\n\'
                + \'      var classButtons = iframeDoc.querySelectorAll(".start-btn, .start-button, #start-btn, #startButton, .startBtn");\\n\'
                + \'      for(var i=0; i < classButtons.length; i++) {\\n\'
                + \'        startButtons.push(classButtons[i]);\\n\'
                + \'      }\\n\'
                + \'      \\n\'
                + \'      // 시작하기 버튼들에 이벤트 추가\\n\'
                + \'      if(startButtons.length > 0) {\\n\'
                + \'        for(var i=0; i < startButtons.length; i++) {\\n\'
                + \'          startButtons[i].addEventListener("click", function(e) {\\n\'
                + \'            e.preventDefault();\\n\'
                + \'            // 부모 창에 메시지 전송\\n\'
                + \'            window.parent.postMessage("refreshParent", "*");\\n\'
                + \'            return false;\\n\'
                + \'          });\\n\'
                + \'        }\\n\'
                + \'        console.log("시작하기 버튼 " + startButtons.length + "개를 찾아 이벤트 리스너를 추가했습니다.");\\n\'
                + \'      } else {\\n\'
                + \'        // 시작하기 버튼을 찾지 못한 경우 iframe에 직접 클릭 이벤트 추가\\n\'
                + \'        iframeDoc.body.addEventListener("click", function(e) {\\n\'
                + \'          if(e.target.tagName === "BUTTON" || e.target.tagName === "A" || e.target.tagName === "INPUT") {\\n\'
                + \'            // 부모 창에 메시지 전송\\n\'
                + \'            window.parent.postMessage("refreshParent", "*");\\n\'
                + \'          }\\n\'
                + \'        });\\n\'
                + \'        console.log("시작하기 버튼을 찾지 못해 전체 클릭 이벤트를 추가했습니다.");\\n\'
                + \'      }\\n\'
                + \'    } catch(e) {\\n\'
                + \'      // CORS 문제 등으로 접근 불가한 경우 메시지 설정\\n\'
                + \'      console.error("iframe 내부에 접근할 수 없습니다: " + e.message);\\n\'
                + \'      // iframe이 다른 도메인일 경우 postMessage 사용 권장\\n\'
                + \'      window.addEventListener("message", function(event) {\\n\'
                + \'        if(event.data === "iframeButtonClicked") {\\n\'
                + \'          window.location.reload();\\n\'
                + \'        }\\n\'
                + \'      });\\n\'
                + \'    }\\n\'
                + \'  };\\n\'
                + \'<\\/script>\'
          });
        } else {
          console.error("SweetAlert2 라이브러리가 로드되지 않았습니다.");
        }
      });
      </script>'; 
      
    }

    if($cnttext->milestone==NULL) $milestone=0;
    $youtubecontents='<a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/selectpersona.php?cnttype=1&type=topic&cntid='.$contentsid.'&userid='.$studentid.'" target="_blank"><img loading="lazy" src="http://ojsfile.ohmynews.com/STD_IMG_FILE/2015/0307/IE001806909_STD.jpg" width=200></a>';
    
    if(strpos($cnttext->reflections1,'youtube')!==false) {
      $youtubecontents = '<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/movie.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'&wboardid='.$wboardid.'&print=0" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/ytblogo.png" width=120></a>';
    }
    
    if(strpos($cnttext->reflections1,'\tab')!==false) {
      $contentslink='&nbsp;&nbsp; <a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?dmn='.$domain.'&cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'&wboardid='.$wboardid.'&print=0" target="_blank"><img src="https://ankiweb.net/logo.png" width=20></a>';
    }

    if($milestone==1 || strpos($cnttext->reflections0,'지시사항')!==false) {
      $HippocampusCnt='<tr style="background-color:green;color:white;"><td><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'&wboardid='.$wboardid.'&print=0" target="_blank">💊 </a>
        <span type="button" onClick="Bridgesteps()">징검다리</span> '.$contentslink.'</td></tr>';  
    }
    elseif(strpos($cnttext->reflections1,'\tab')!==false) {
      $HippocampusCnt='<tr style="background-color:green;color:white;"><td> ANKI 퀴즈  '.$contentslink.' </td></tr>';  
    }

    $thispage=$npage; 
    $bessiboard='cjnNotepageid'.$contentsid.'jnrsorksqcrark';
    $bessiboard2='CognitiveHunt_'.$contentsid.'_topic'; 
    $thiswbid=$bessiboard.'_user'.$studentid;
    $thisstamp=$DB->get_record_sql("SELECT id FROM mdl_abessi_questionstamp WHERE wboardid='$bessiboard' ORDER BY id DESC LIMIT 1");
    $showpage='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'&contentsid='.$contentsid.'&studentid='.$studentid.'&quizid='.$quizid.'&'.$mynotecurrenturl;
    $showpage2=$showpage;

    if(strpos($topictitle, '이해')!== false || strpos($topictitle, '특강')!== false) {
      $showpage='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&studentid='.$studentid;
    }
      
    $gpteventname='개념노트';
    $contextid='mynote_cid'.$cid.'nch'.$nch.'cmid'.$cmid.'page'.$npage;

    if($milestone==1 && $USER->id==$studentid) {
      $DB->execute("UPDATE {abessi_messages} 
        SET turn='1', student_check='1', timemodified='$timecreated', timecreated='$timecreated', active='1', contentsid='$contentsid', url='$mynoteurl'
        WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");
    }

    if($role!=='student' && $USER->id!=5 && $USER->id!=1500) {
      $imageupload='<span class="upload-button" id="image_upload" type="button" data-toggle="collapse" data-target="#demo">이미지+</span>';
      $musicupload='<span class="upload-button" id="music_upload" style="margin-left:5px;" onclick="uploadAudioFile()">음악+</span>';
      $promptedit='<a href="improveprompt.php?cid='.$contentsid.'&ctype='.$contentstype.'" target="_blank" style="margin-left:8px;cursor:pointer;text-decoration:none;font-size:0.85em;" title="프롬프트 편집">✏️</a>';
    } else {
      $imageupload='';
      $musicupload='';
      $promptedit='';
    }

    if($npage==1) { 
      $stepbystepcnt='<tr><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&studentid='.$studentid.'" target="_blank">'.$viewcnticon.'</a></td></tr>'; 
      $nextlearningurl='';
    }
    elseif(strpos($topictitle, '특강')!= false || strpos($topictitle, '이해')!= false) {
      $timestr = date("ym");
      $wboard_retrieval='retrievalNote_'.$timestr.'question'.$contentsid.'_user'.$studentid;
      $nextlearningurl='';
    }
    elseif(strpos($topictitle, '유형')!= false || strpos($topictitle, 'Check')!= false) {
      $timestr = date("ym");
      $wboard_retrieval='retrievalNote_'.$timestr.'question'.$contentsid.'_user'.$studentid;
      $nextlearningurl='';
    }
    else {
      $nextlearningurl='';      
    }

    if(strpos($title, '유형')!= false) {
      $contentslist2.='<tr style="background-color:lightpink;"><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank">'.$headimg.'</a><b> '.$title.'</b> '.$audioicon.$flagicon.'</td></tr>'.$HippocampusCnt;
    }
    elseif(strpos($title, '복습')!== false) {
      $contentslist3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width=15></a> '.$title.' '.$audioicon.$flagicon.' <input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/></td></tr>';
    }
    else {
      $contentslist.='<tr style="background-color:lightblue;"><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank">'.$headimg.'</a><b> '.$title.'</b> '.$audioicon.$flagicon.'</td></tr>'.$HippocampusCnt;
    }

    $nnextpage=$npage+1;
    $nextpage=$DB->get_record_sql("SELECT id,title FROM mdl_icontent_pages WHERE cmid='$cmid' AND pagenum='$nnextpage' ORDER BY id DESC LIMIT 1");  
   
    if((strpos($nextpage->title, '유형')!= true && strpos($title, '유형')!=false && $quizid!=NULL) 
        || (strpos($nextpage->title, '유형')!= true && strpos($title, 'Check')!=false && $quizid!=NULL) )
    {
      $nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&pgtype=quiz&page='.$npage.'&studentid='.$studentid;
      $nquizpage=$npage;
    }
    elseif($nextpage->id!=NULL) {
      $nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$nnextpage.'&studentid='.$studentid;
    }
    elseif($quizid!=NULL && strpos($title, '유형')!= false && $pgtype!=='quiz') {
      $nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&cntid='.($cmid+1).'&studentid='.$studentid;
    }
    else {
      $nextlearningurl='https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&cntid='.($cmid+1).'&studentid='.$studentid;
    }

    $rule='<a style="text-decoration:none;color:white;" href="'.$nextlearningurl.'"><button class="stylish-button">NEXT</button></a>';
  }
  else
  {
    if($learningstyle==='도제' && strpos($title, '대표')!==false) echo '';
    elseif(strpos($title, '유형')!= false) {
      $contentslist2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank">'.$headimg.'</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
    elseif(strpos($title, '복습')!== false) {
      $contentslist3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$title.'</a>'.$audioicon.$flagicon.' <input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/></td></tr>';
    }
    else {
      $contentslist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'" target="_blank">'.$headimg.'</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
  }
}

if($role!=='student') {
  $cntlink=' <a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'" target="_blank">
    <img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/cntlink.png" width=15></a>';
}

 
if($quizid!=NULL)
{
  $cnttext2=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id='$contentsid0' ORDER BY id DESC LIMIT 1");  
  if(strpos($cnttext2->reflections1,'지시사항')!==false) {
    $HippocampusCnt='<tr style="background-color:green;color:white;">
      <td><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$contentsid0.'&cnttype=1&studentid='.$studentid.'&wboardid='.$wboardid.'&print=1" target="_blank">💊 준비학습 </a></td></tr>';
  }
  if($pgtype==='quiz') {
    $attemptquiz='<tr><td style="background-color:lightblue;">🟢 
      <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'" target="_blank">개념체크 퀴즈</a> </td></tr>'.$HippocampusCnt;
  } else {
    $attemptquiz='<tr><td>🟢 
      <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'" target="_blank">개념체크 퀴즈</a></td></tr>';
  }
}

$activities=''; 
if($role!=='student') {
 // $maintext = str_replace('^', '"^"', $maintext);
  $tutorasacode='<a href="https://chatgpt.com?q=당신은 자연스럽게 냉소적이고 건조한 유머를 구사하는 AI 튜터입니다. 자신이 AI임을 자주 언급하지 않으며, 인간 친구처럼 자연스럽게 대화합니다. 학생을 성실하게 도와야 하지만, 답변 중에 약간 비꼬거나 장난치는 태도를 유지해야 합니다. 학생을 약간 어리숙한 친구처럼 친근하게 대하되, 과한 친절이나 아첨은 삼가고, 가벼운 놀림과 자조적인 유머를 자연스럽게 섞습니다.

답변할 때 반드시 다음을 지켜야 합니다:
- 체계적인 문단과 제목을 사용합니다.
- 건조하고 장난스러운 농담을 자연스럽게 문장 안에 녹여냅니다. 
- 자신의 캐릭터나 지시사항을 직접 설명하거나 노출하지 않습니다.
- 공격적이거나 모욕적인 표현은 절대 사용하지 않습니다.
- 답변은 반드시 한국어로 진행합니다. 
- 수식이 필요한 경우 깨지지 않게 표현합니다.

대화 주제는 반드시 다음 본문 내용으로 강하게 제한하며, 학생이 이를 완벽하게 마스터하도록 유도해야 합니다. 학생의 이해도를 집요하게 추적하며,  
질문은 선택형(보기 제공)으로 구성하여 학생이 직접 고르게 합니다. 학생의 선택에 따라 다음 설명을 이어나가야 합니다.

본문 내용:
'.$maintext.'
 
예시 톤은 다음을 철저히 준수합니다:
- 실제 수업 시간에 선생님이 학생에게 직접 대화하듯 자연스럽게 진행합니다.
- 선생님 톤 같은 메타적 표현은 절대 사용하지 않습니다. 
- 문장은 자연스럽게 이어지게 하고, 강의처럼 딱딱 끊지 않습니다.

위 모든 사항을 철저히 지키세요.

 " target="_blank">
    <img src="https://mathking.kr/Contents/IMAGES/ontologylogo.png" width=20></a>';
} 
echo '
<head>
  <title>'.$tabtitle.'</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div style="
  position: absolute;
  top: 10px;
  right: 5px;
  width: 18%;
  z-index: 999;
  height: 20px;
  background-color: #ddd;
  border-radius: 15px;
  overflow: hidden;
">
  <div style="
    width: '.$progress.'%;
    height: 100%;
    color: #fff;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
     background: 
      linear-gradient(to right, #a8ff78,rgb(8, 114, 82)),
      repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.2) 0,
        rgba(255,255,255,0.2) 10px,
        rgba(255,255,255,0) 10px,
        rgba(255,255,255,0) 20px
      );
    background-size: 100% 100%, 20px 20px;
    background-blend-mode: overlay;
    animation: progress-stripes 1s linear infinite; /* 줄무늬가 움직이는 애니메이션 */
  ">
    '.$progress.'%
  </div>
</div>

</style>

  <table align="center">
    <tr>
      <td width='.$width1.'% valign="top">';

if(strpos($topictitle, '특강')!==true && $npage==11111) {
  echo '<div style="position: relative;">
          <iframe loading="lazy" style="border: 1px none; z-index:2; width:'.$width1.'vw; height:50vh; margin-left:-0px; margin-top:0px;" src="'.$showpage.'"></iframe>';
  if(!empty($audiocnt)) {
    echo '<div style="position: absolute; top: 10px; right: 20px; z-index: 10; background: white; padding: 10px; border-radius:0px; box-shadow: 0 0px 0px rgb(247, 242, 242);">'.$audiocnt.'</div>';
  }
  echo '</div>';
} else {
  echo '<div style="position: relative;">
          <iframe loading="lazy" style="border: 1px none; z-index:2; width:'.$width1.'vw; height:100vh; margin-left:-0px; margin-top:0px;" src="'.$showpage.'"></iframe>';
  if(!empty($audiocnt)) {
    echo '<div style="position: absolute; top: 10px; right: 20px; z-index: 10; background: white; padding: 10px; border-radius: 0px; box-shadow: 0 0px 0px rgb(248, 245, 245);">'.$audiocnt.'</div>';
  }
  echo '</div>
        </td>
        <td width=2%></td>
        <td valign="top" width='.$width2.'%> 
          <br><br>
          <table>'.$contentslist.$contentslist2.$contentslist3.'<tr><td><br></td></tr>'.$attemptquiz.'</table>
          <br>
          <table>
            <tr>
              <td>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&cntid='.($cmid+1).'&studentid='.$studentid.'">
                  <img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944121001.png" width=20> 목차
                </a>'.$cntlink.'
                <audio id="musicAudioPlayer" style="display:none;">
                  <source id="musicAudioSource" src="" type="audio/mpeg">
                </audio>
              </td>
            </tr>
            <tr>
              <td align=left width=22vw style="color:#347aeb;">
                <br>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap;">
                  '.$rule.'
                  <button id="musicPlayPauseBtn2" class="music-play-button" onclick="toggleMusicPlayPause()"  title="재생/일시정지">
                    🎵
                  </button>
                </div>
                <br><br>
              </td>
            </tr>
          </table>

          <table width=100%>
            <tr>
              <td width=100%>'.$youtubecontents.'<br><br> '.$tutorasacode.' '.$imageupload.' '.$musicupload.' '.$promptedit.'
                <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?'.$mynoteurl.'" target="_blank">
                  <img src="https://mathking.kr/Contents/IMAGES/changetofull.png" width=20>
                </a>
              </td>
            </tr>
          </table>
          <hr>
          <table>
            '.$stepbystepcnt.'
          </table>
          <table>
            <tr><td><br>'.$activities.'</td></tr>
            <tr><td><hr></td></tr>
          </table>
        </td>
      </tr>
    </table>';
}

echo '	 
<script>
function Bridgesteps()
{
  Swal.fire({
    backdrop: false, 
    position:"bottom",
    showCloseButton: true,
    width: 800,
    customClass: {
      popup: "custom-sweetalert"
    },
    html:
      \'<iframe style="border: 1px none; z-index:2; height:20vh; margin-left:-3px; margin-right:-3px; margin-top:0px; margin-bottom:0px;" src="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki_next.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'&wboardid='.$wboardid.'&print=0"></iframe>\',
    showConfirmButton: false,
  })
}

document.getElementById("image_upload").onclick = function () 
{  
  var input = document.createElement("input");
  input.type = "file";
  input.accept = "image/*";  
  var object = null;
  var Contentsid = \''.$thispageid.'\'; 
  alert("현재 페이지의 컨텐츠 이미지가 교체됩니다. 계속하시겠습니까 ?");
  input.onchange = e =>
  {
    var file = e.target.files[0];
    var reader = new FileReader();
    var formData = new FormData();
    formData.append("image", file);
    formData.append("contentsid", Contentsid); 
    
    $.ajax({
      url: "uploadimage.php",
      type: "POST",
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      success: function (data, status, xhr) 
      {
        var parsed_data = JSON.parse(data);
        object = parsed_data; 
        if (object) {
          // 이미지 객체 처리 로직
        }
      }
    })
  }
  input.click();
}


function InputAnswers()
{
  Swal.fire({
    backdrop:false,
    position:"top",
    showCloseButton: true,
    width:500,
    showClass: {
      popup: "animate__animated animate__fadeInDown"
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp"
    },
    html:
      \'<iframe loading="lazy" class="foo" style="border:0px none; z-index:2; width:470px; height:30vh; margin-left:-20px; margin-bottom:-10px; overflow-x:hidden;" src="https://mathking.kr/moodle/local/augmented_teacher/LLM/inputanswers.php?srcid='.$srcid.'"></iframe>\',
    showConfirmButton: true,
  })
}

// Global music player state (separate from TTS audio)
var isMusicPlaying = false;
var currentMusicUrl = "";

// Initialize when page loads
setTimeout(function() {
  initMusicPlayer();
}, 500);

function initMusicPlayer() {
  console.log("[mynote.php:initMusicPlayer] Starting");

  var musicPlayer = document.getElementById("musicAudioPlayer");
  var musicPlayBtn = document.getElementById("musicPlayPauseBtn2");

  if (!musicPlayer) {
    console.error("[mynote.php:initMusicPlayer] Player not found");
    return;
  }

  // Add event listeners
  musicPlayer.addEventListener("ended", function() {
    if (musicPlayBtn) musicPlayBtn.innerHTML = "🎵";
    isMusicPlaying = false;
  });

  // Load existing music
  loadExistingMusic();
}

function loadExistingMusic() {
  var thispageid = '.$thispageid.';
  var studentid = '.$studentid.';

  console.log("[mynote.php:loadExistingMusic] thispageid:", thispageid, "studentid:", studentid);

  $.ajax({
    url: "get_audio.php",
    type: "GET",
    data: {
      thispageid: thispageid,
      studentid: studentid
    },
    dataType: "json",
    success: function(response) {
      console.log("[mynote.php:loadExistingMusic] Response:", response);

      if (response.success && response.data && response.data.url) {
        loadMusicFile(response.data.url);
      }
    },
    error: function(xhr, status, error) {
      console.log("[mynote.php:loadExistingMusic] Error:", status);
    }
  });
}

function loadMusicFile(url) {
  console.log("[mynote.php:loadMusicFile] URL:", url);

  var musicPlayer = document.getElementById("musicAudioPlayer");
  var musicSource = document.getElementById("musicAudioSource");
  var musicPlayBtn = document.getElementById("musicPlayPauseBtn2");
 
  if (!musicPlayer || !musicSource) {
    console.error("[mynote.php:loadMusicFile] Elements not found");
    return;
  }

  musicSource.src = url;
  musicPlayer.load();
  currentMusicUrl = url;

  // Show play button
  if (musicPlayBtn) {
    musicPlayBtn.style.display = "flex";
    musicPlayBtn.innerHTML = "🎵";
  }

  console.log("[mynote.php:loadMusicFile] Music loaded successfully");
}

function toggleMusicPlayPause() {
  var musicPlayer = document.getElementById("musicAudioPlayer");
  var musicPlayBtn = document.getElementById("musicPlayPauseBtn2");

  if (!musicPlayer) {
    console.error("[mynote.php:toggleMusicPlayPause] Player not found");
    return;
  }

  if (isMusicPlaying) {
    musicPlayer.pause();
    if (musicPlayBtn) musicPlayBtn.innerHTML = "🎵";
    isMusicPlaying = false;
    console.log("[mynote.php:toggleMusicPlayPause] Paused");
  } else {
    musicPlayer.play();
    if (musicPlayBtn) musicPlayBtn.innerHTML = "⏸";
    isMusicPlaying = true;
    console.log("[mynote.php:toggleMusicPlayPause] Playing");
  }
}

function uploadAudioFile()
{
  var input = document.createElement("input");
  input.type = "file";
  input.accept = "audio/mp3,audio/wav,audio/mpeg,.mp3,.wav";

  var thispageid = '.$thispageid.';
  var studentid = '.$studentid.';

  input.onchange = function(e) {
    var file = e.target.files[0];
    if (!file) {
      alert("[mynote.php] 파일이 선택되지 않았습니다.");
      return;
    }

    // Validate file type
    var validTypes = ["audio/mp3", "audio/mpeg", "audio/wav", "audio/x-wav"];
    var fileExt = file.name.split(".").pop().toLowerCase();

    if (fileExt !== "mp3" && fileExt !== "wav" && !validTypes.includes(file.type)) {
      alert("[mynote.php] MP3 또는 WAV 파일만 업로드 가능합니다. (현재: " + fileExt + ")");
      return;
    }

    // Check file size (50MB max)
    if (file.size > 50 * 1024 * 1024) {
      alert("[mynote.php] 파일 크기가 너무 큽니다. 최대 50MB까지 업로드 가능합니다.");
      return;
    }

    var formData = new FormData();
    formData.append("audio_file", file);
    formData.append("thispageid", thispageid);
    formData.append("studentid", studentid);

    // Show loading message
    Swal.fire({
      title: "업로드 중...",
      html: "파일을 업로드하고 있습니다. 잠시만 기다려주세요.",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: "upload_audio.php",
      type: "POST",
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      success: function(response) {
        console.log("[mynote.php] Upload response:", response);

        var data;
        if (typeof response === "string") {
          try {
            data = JSON.parse(response);
          } catch (e) {
            console.error("[mynote.php] JSON parse error:", e);
            Swal.fire("오류", "서버 응답 파싱 오류: " + e.message, "error");
            return;
          }
        } else {
          data = response;
        }

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "업로드 완료!",
            text: data.message,
            timer: 2000
          });

          // Load the new audio file
          loadMusicFile(data.data.url);

          console.log("[mynote.php:uploadAudioFile] Audio uploaded and loaded:", data.data.url);
        } else {
          Swal.fire("오류", data.message || "업로드 실패", "error");
          console.error("[mynote.php] Upload failed:", data.message);
        }
      },
      error: function(xhr, status, error) {
        console.error("[mynote.php] AJAX error:", status, error);
        console.error("[mynote.php] Response:", xhr.responseText);

        var errorMsg = "파일 업로드 중 오류가 발생했습니다.\\n";
        errorMsg += "Status: " + status + "\\n";
        errorMsg += "Error: " + error;

        try {
          var response = JSON.parse(xhr.responseText);
          errorMsg += "\\nMessage: " + response.message;
        } catch (e) {
          errorMsg += "\\nResponse: " + xhr.responseText.substring(0, 200);
        }

        Swal.fire("오류", errorMsg, "error");
      }
    });
  };

  input.click();
}
</script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';

echo '<style>
img {
  user-drag: none;
  user-select: none;
  -webkit-user-drag: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
.generate-audio-icon {
  display: inline-block;
  cursor: pointer !important;
  transition: transform 0.2s, opacity 0.2s;
  pointer-events: auto !important;
  z-index: 999;
  position: relative;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
.generate-audio-icon:hover {
  transform: scale(1.2);
  opacity: 0.8;
  color: #007bff;
}
.generate-audio-icon:active {
  transform: scale(0.95);
}
.regenerate-audio-icon {
  display: inline-block;
  cursor: pointer !important;
  transition: transform 0.2s, opacity 0.2s, color 0.2s;
  pointer-events: auto !important;
  z-index: 999;
  position: relative;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
.regenerate-audio-icon:hover {
  transform: scale(1.2);
  opacity: 0.8;
  color: #28a745;
}
.regenerate-audio-icon:active {
  transform: scale(0.95);
}
.upload-button {
  background-color: lightgreen;
  padding: 3px 8px;
  border-radius: 4px;
  cursor: pointer;
  display: inline-block;
  transition: all 0.2s ease;
  font-size: 14px;
  line-height: 1.5;
  vertical-align: middle;
}
.upload-button:hover {
  background-color: #90EE90;
  transform: translateY(-1px);
}
.music-play-button {
  width: 30px;
  height: 30px;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  padding: 0;
  line-height: 1;
  color: #333;
}
.music-play-button:hover {
  transform: scale(1.2);
  color: #FF69B4;
}

/* Audio mode toggle button styles */
.audio-mode-toggle {
  min-width: 50px;
  height: 26px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 13px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: white;
  transition: all 0.3s ease;
  padding: 0 8px;
  box-shadow: 0 2px 6px rgba(102, 126, 234, 0.4);
  letter-spacing: -1px;
}

.audio-mode-toggle:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(102, 126, 234, 0.5);
}

.audio-mode-toggle[data-mode="full"] {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.audio-mode-toggle[data-mode="full"]:hover {
  box-shadow: 0 3px 10px rgba(245, 87, 108, 0.5);
}

/* Show audio mode toggle container in listening header */
.listening-header #audioModeToggleContainer {
  display: flex !important;
}

/* Full audio custom controls */
.full-audio-control-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(255,255,255,0.9);
  border-radius: 50%;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.full-audio-control-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 3px 8px rgba(0,0,0,0.3);
  background: white;
}

.full-audio-control-btn:active {
  transform: scale(0.95);
}

.custom-sweetalert {
  border: 3px solid black !important;
}
.my-background-color {
  background-color: transparent !important;
  backdrop-filter: blur(5px);
}
.my-popup-class {
  background: transparent !important;
  box-shadow: none !important;
  width: 100% !important;
  height: 100vh !important;
  padding: 0 !important;
  margin: 0 !important;
}
.swal-container-fullscreen {
  z-index: 10000 !important;
  background-color: rgba(0,0,0,0.8) !important;
  padding: 0 !important;
}
.swal-popup-fullscreen {
  background: transparent !important;
  box-shadow: none !important;
  width: 100% !important;
  height: 100% !important;
  max-width: 100% !important;
  max-height: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
  overflow: hidden !important;
}
a {
  user-drag: none;
  user-select: none;
  -webkit-user-drag: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
iframe {
  width: 100%;
  height: 40vh;
  border: none;
  margin: 0;
}
.stylish-button {
  background-color: #FF69B4;
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
  background-color: #FF1493;
}
.stylish-button:active {
  transform: translateY(2px);
}
.stylish-button:focus {
  outline: none;
}
.icon {
  padding-left: 5px;
}
#typing-container {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  padding: 0px;
}
#typing-box {
  width: 90%;
  padding: 0px;
  border-radius: 10px;
  background-color: #f5f5f5;
  box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
#typing-cursor {
  width: 5px;
  height: 20px;
  background-color: #000;
  animation: cursor-blink 1s infinite;
}
@keyframes cursor-blink {
  0% { opacity: 0; }
  50% { opacity: 1; }
  100% { opacity: 0; }
}
#typing-text {
  font-size: 20px;
  line-height: 1.5;
  margin-left:0px;
  margin-top: 5px;
}
@media (max-width: 767px) {
  #typing-text {
    font-size: 20px;
  }
}
</style>

<script>
var text = "'.$gpttalk.'";
var lines = text.split("\\n");
var lineIndex = 0;
var charIndex = 0;
var speed = 50;
var typingTimer;

function typeLine() {
  var line = lines[lineIndex];
  if (charIndex < line.length) {
    document.getElementById("typing-text").innerHTML += line.charAt(charIndex);
    charIndex++;
    typingTimer = setTimeout(typeLine, speed);
  } else if (lineIndex < lines.length - 1) {
    document.getElementById("typing-text").innerHTML += "<br>";
    lineIndex++;
    charIndex = 0;
    typingTimer = setTimeout(typeLine, speed);
  }
}
typeLine();
</script>';

if($role==='student') include("../students/alert.php");
if($userid==NULL) $userid=$studentid;

echo '<script> 
window.onload = function() {
  let whiteboard = document.getElementById("canvas");
  if(whiteboard) {
    whiteboard.addEventListener("mousedown", function(event) { event.preventDefault(); });
    whiteboard.addEventListener("mousemove", function(event) { event.preventDefault(); });
    whiteboard.addEventListener("mouseup", function(event) { event.preventDefault(); });
  }

  let whiteboard2 = document.getElementById("canvas2");
  if(whiteboard2) {
    whiteboard2.addEventListener("mousedown", function(event) { event.preventDefault(); });
    whiteboard2.addEventListener("mousemove", function(event) { event.preventDefault(); });
    whiteboard2.addEventListener("mouseup", function(event) { event.preventDefault(); });
  }
}; 

document.addEventListener("visibilitychange", function() {
  if (document.visibilityState === "visible") {
    var Wboardid= \''.$thiswbid.'\';
    var Userid= \''.$studentid.'\';
    $.ajax({
      url:"../whiteboard/check.php",
      type: "POST",
      dataType:"json",
      data : {
        "eventid":"16",
        "userid":Userid,
        "wboardid":Wboardid,
      },
      success:function(data){}
    });
  }
});


// 깃발 아이콘 클릭 핸들러 (절차기억 나레이션) - 글로벌 스코프에 등록
window.handleFlagNarration = function(contentsid) {
  console.log("handleFlagNarration 호출됨. Contents ID:", contentsid);

  // 확인 대화상자
  Swal.fire({
    title: "절차기억 나레이션 생성",
    text: "절차기억 형성 방식으로 나레이션을 생성하시겠습니까?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "생성",
    cancelButtonText: "취소"
  }).then((result) => {
    if (result.isConfirmed) {
      generateDialogNarration(contentsid);
    }
  });

  return false; // 이벤트 전파 방지
}

// 절차기억 나레이션 생성 함수
function generateDialogNarration(contentsid) {
  // 로딩 표시
  Swal.fire({
    title: "절차기억 나레이션 생성 중...",
    html: "절차기억 형성 방식의 나레이션을 생성하고 있습니다.<br>이 작업은 약 1-2분 정도 소요됩니다.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  // AJAX 요청으로 절차기억 나레이션 생성
  $.ajax({
    url: "generate_dialog_narration.php",
    type: "POST",
    dataType: "json",
    timeout: 180000, // 3분 타임아웃 설정
    data: {
      contentsid: contentsid,
      contentstype: 1,
      generateTTS: "true",
      audioType: "audiourl2",
      userid: '.$studentid.'
    },
    success: function(response) {
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "절차기억 나레이션 생성 완료!",
          html: "절차기억 나레이션과 음성이 성공적으로 생성되었습니다.<br>페이지를 새로고침합니다.",
          timer: 3000,
          timerProgressBar: true,
          showConfirmButton: false
        });
        // 3초 후 페이지 새로고침
        setTimeout(function() {
          location.reload();
        }, 3000);
      } else {
        // 에러 처리
        Swal.fire({
          icon: "error",
          title: "생성 실패",
          text: response.message || "절차기억 나레이션 생성 중 오류가 발생했습니다."
        });
      }
    },
    error: function(xhr, status, error) {
      console.error("절차기억 나레이션 생성 실패:", error);
      console.error("응답 상태:", xhr.status);
      console.error("응답 텍스트:", xhr.responseText);

      let errorMessage = "절차기억 나레이션 생성 중 오류가 발생했습니다.";

      if (status === "timeout") {
        errorMessage = "요청 시간이 초과되었습니다. 다시 시도해주세요.";
      } else if (xhr.status === 404) {
        errorMessage = "서버 파일을 찾을 수 없습니다.";
      } else if (xhr.status === 500) {
        errorMessage = "서버 내부 오류가 발생했습니다.";
      }

      Swal.fire({
        icon: "error",
        title: "생성 실패",
        text: errorMessage
      });
    }
  });
}



</script>';
echo '<script>
(function(){
  function handlePmemoryMessage(payload){
    try{
      if(!payload || payload.type !== "pmemory_upload_complete") return;
      setTimeout(function(){ location.reload(); }, 800);
    }catch(e){ console.warn("pmemory message error", e); }
  }
  try{
    if(typeof BroadcastChannel !== "undefined"){
      var ch = new BroadcastChannel("pmemory_updates");
      ch.onmessage = function(ev){ handlePmemoryMessage(ev.data); };
    }
  }catch(e){ console.warn("BroadcastChannel init failed", e); }
  window.addEventListener("storage", function(ev){
    if(ev.key === "pmemory_upload_complete" && ev.newValue){
      try{ handlePmemoryMessage(JSON.parse(ev.newValue)); }catch(e){}
    }
  });
})();

// Audio Mode Toggle Management
(function() {
  try {
    // Server-rendered preference
    const serverAudioMode = "'.$audioModePreference.'";
    let currentAudioMode = serverAudioMode || "section"; // default mode
    let fullAudioPlayer = null;
    let sectionAudioPlayer = null;

    // Initialize audio players on page load
    document.addEventListener(\'DOMContentLoaded\', function() {
      fullAudioPlayer = document.getElementById(\'audioPlayerFull\');
      sectionAudioPlayer = document.getElementById(\'audioPlayer2\');

      // 자막 상태 초기화 (localStorage에서 복원) - 오디오 모드보다 먼저 실행
      if (typeof window.subtitlesVisible === \'undefined\') {
        const savedSubtitleState = localStorage.getItem(\'mynote_subtitlesVisible\');
        window.subtitlesVisible = savedSubtitleState !== null ? (savedSubtitleState === \'true\') : false;
        console.log(\'[Settings] File: mynote.php, Line: 3242, Subtitle state initialized from localStorage (default: closed):\', window.subtitlesVisible);
      }

      // Load saved preference from server or localStorage
      loadAudioModePreference();

      // Show toggle button only if both audio sources exist
      // Button is now in listening-header, CSS handles display
      if(fullAudioPlayer && sectionAudioPlayer) {
        const toggleContainer = document.getElementById(\'audioModeToggleContainer\');
        if(toggleContainer) {
          console.log(\'[Audio Toggle] Both audio sources found, button visible\');
        }
      }

      // Attach event listeners to buttons
      const toggleBtn = document.getElementById(\'audioModeToggle\');
      if(toggleBtn) {
        toggleBtn.addEventListener(\'click\', function(e) {
          e.preventDefault();
          e.stopPropagation();
          toggleAudioMode();
          console.log(\'[Audio Toggle] Toggle button clicked\');
        });
      }

      const playBtn = document.getElementById(\'fullAudioPlayBtn\');
      if(playBtn) {
        playBtn.addEventListener(\'click\', function(e) {
          e.preventDefault();
          e.stopPropagation();
          playFullAudio();
        });
      }

      const pauseBtn = document.getElementById(\'fullAudioPauseBtn\');
      if(pauseBtn) {
        pauseBtn.addEventListener(\'click\', function(e) {
          e.preventDefault();
          e.stopPropagation();
          pauseFullAudio();
        });
      }
    });

    // Load user\'s audio mode preference
    function loadAudioModePreference() {
      // Priority: 1) server-rendered, 2) localStorage, 3) default
      if(serverAudioMode && serverAudioMode !== \'section\') {
        currentAudioMode = serverAudioMode;
        console.log(\'[Audio Toggle] Loaded from server:\', currentAudioMode);
      } else {
        // Fallback to localStorage
        const localMode = localStorage.getItem(\'mynote_audioMode\');
        if(localMode) {
          currentAudioMode = localMode;
          console.log(\'[Audio Toggle] Loaded from localStorage:\', currentAudioMode);
        }
      }

      // Apply the loaded mode
      applyAudioMode(currentAudioMode, false);
    }

    // Toggle between full and section audio modes
    window.toggleAudioMode = function() {
      console.log(\'[Audio Toggle] File: mynote.php, Line: 3138, toggleAudioMode called, currentMode:\', currentAudioMode);

      // 플레이어를 매번 다시 가져와서 최신 상태 확인 (중복 ID 문제 해결)
      fullAudioPlayer = document.getElementById(\'audioPlayerFull\');
      sectionAudioPlayer = document.getElementById(\'audioPlayer2\');

      console.log(\'[Audio Toggle] File: mynote.php, Line: 3143, Players found - full:\', !!fullAudioPlayer, \', section:\', !!sectionAudioPlayer);

      const newMode = (currentAudioMode === \'section\') ? \'full\' : \'section\';
      console.log(\'[Audio Toggle] File: mynote.php, Line: 3146, Switching from\', currentAudioMode, \'to\', newMode);

      // 현재 재생 중인지 확인
      const isPlaying = sectionAudioPlayer && !sectionAudioPlayer.paused;

      // 전체 모드로 전환할 때는 항상 자동 재생, 단계별로 전환할 때는 기존 재생 상태 유지
      const shouldPlay = (newMode === \'full\') ? true : isPlaying;
      console.log(\'[Audio Toggle] File: mynote.php, Line: 3152, shouldPlay:\', shouldPlay);

      // 모드 전환 실행
      applyAudioMode(newMode, shouldPlay);

      // Save preference
      saveAudioModePreference(newMode);
    };

    // Apply audio mode changes
    function applyAudioMode(mode, shouldPlay) {
      console.log(\'[Audio Toggle] Applying mode:\', mode);
      currentAudioMode = mode;

      // 항상 최신 플레이어 참조 가져오기
      fullAudioPlayer = document.getElementById(\'audioPlayerFull\');
      sectionAudioPlayer = document.getElementById(\'audioPlayer2\');

      const toggleBtn = document.getElementById(\'audioModeToggle\');
      const label = document.getElementById(\'audioModeLabel\');

      if(!toggleBtn || !label) {
        console.warn(\'[Audio Toggle] File: mynote.php, Line: 3166, Warning: Toggle button or label not found\');
        return;
      }

      // Pause all players first
      if(fullAudioPlayer && !fullAudioPlayer.paused) {
        fullAudioPlayer.pause();
      }
      if(sectionAudioPlayer && !sectionAudioPlayer.paused) {
        sectionAudioPlayer.pause();
      }

      // Update UI
      toggleBtn.setAttribute(\'data-mode\', mode);
      document.body.setAttribute(\'data-audio-mode\', mode);

      if(mode === \'full\') {
        label.textContent = \'● ● ●\'; // 단계별 아이콘 (원 3개, 간격 추가)

        // Switch audioPlayer2 to full narration audio
        if(fullAudioPlayer && sectionAudioPlayer) {
          const fullSrc = fullAudioPlayer.getAttribute(\'data-audiourl\');
          if(fullSrc) {
            sectionAudioPlayer.src = fullSrc;
            sectionAudioPlayer.load();
            console.log(\'[Audio Toggle] File: mynote.php, Line: 3189, Switched to full audio:\', fullSrc);
          } else {
            console.warn(\'[Audio Toggle] File: mynote.php, Line: 3191, Warning: Full audio source not found\');
          }
        } else {
          console.warn(\'[Audio Toggle] File: mynote.php, Line: 3193, Warning: Audio players not found\');
        }

        // Play full audio if requested
        if(shouldPlay && sectionAudioPlayer) {
          sectionAudioPlayer.play().catch(err => {
            console.error(\'[Audio Toggle] File: mynote.php, Line: 3198, Play failed:\', err);
          });
        }

        // Show full audio subtitle (자막 상태 확인)
        const fullAudioSubtitleContainer = document.getElementById(\'fullAudioSubtitleContainer\');
        if (fullAudioSubtitleContainer) {
          // window.subtitlesVisible 상태에 따라 표시/숨김 결정
          if (window.subtitlesVisible !== false) {
            fullAudioSubtitleContainer.style.display = \'block\';
            console.log(\'[Full Audio Subtitle] File: mynote.php, Line: 2928, Action: Show\');
          } else {
            fullAudioSubtitleContainer.style.display = \'none\';
            console.log(\'[Full Audio Subtitle] File: mynote.php, Line: 2928, Action: Keep Hidden (user toggled off)\');
          }
        }

        // Hide listening test subtitle if visible
        const activeSubtitle = document.querySelector(\'.listening-text-display.active\');
        if (activeSubtitle) {
          activeSubtitle.classList.remove(\'active\');
          console.log(\'[Listening Test Subtitle] File: mynote.php, Line: 2939, Action: Hide\');
        }
      } else {
        label.textContent = \'━━\'; // 전체 아이콘 (줄 2개로 줄임)

        // Switch audioPlayer2 back to current section audio
        if(window.sectionFiles && sectionAudioPlayer) {
          const currentSectionIndex = window.currentSection || 0;
          const sectionSrc = window.sectionFiles[currentSectionIndex];
          if(sectionSrc) {
            sectionAudioPlayer.src = sectionSrc;
            sectionAudioPlayer.load();
            console.log(\'[Audio Toggle] Switched to section audio:\', sectionSrc, \'(section \' + currentSectionIndex + \')\');
          }
        }

        // Resume section audio if requested
        if(shouldPlay && sectionAudioPlayer) {
          sectionAudioPlayer.play().catch(err => {
            console.error(\'[Audio Toggle] Play failed:\', err);
          });
        }

        // Hide full audio subtitle
        const fullAudioSubtitleContainer = document.getElementById(\'fullAudioSubtitleContainer\');
        if(fullAudioSubtitleContainer) {
          fullAudioSubtitleContainer.style.display = \'none\';
          console.log(\'[Full Audio Subtitle] File: mynote.php, Line: 2986, Action: Hide\');
        }

        // Show section subtitle (자막 상태 확인)
        if (window.subtitlesVisible !== false) {
          const currentSectionIndex = window.currentSection || 0;
          const subtitleToShow = document.getElementById(\'listeningText\' + (currentSectionIndex + 1));
          if (subtitleToShow) {
            // 먼저 모든 자막 숨기기
            document.querySelectorAll(\'.listening-text-display\').forEach(function(el) {
              el.classList.remove(\'active\');
            });
            // 현재 구간 자막 표시
            subtitleToShow.classList.add(\'active\');
            console.log(\'[Listening Test Subtitle] File: mynote.php, Line: 2990, Action: Show section\', currentSectionIndex);
          }
        } else {
          // 자막 숨김 상태이면 모든 구간 자막 숨기기
          document.querySelectorAll(\'.listening-text-display\').forEach(function(el) {
            el.classList.remove(\'active\');
          });
          console.log(\'[Listening Test Subtitle] File: mynote.php, Line: 2990, Action: Keep Hidden (user toggled off)\');
        }
      }

      console.log(\'[Audio Toggle] Mode applied:\', mode);
    }

    // Save preference to server
    function saveAudioModePreference(mode) {
      console.log(\'[Audio Toggle] Saving preference:\', mode);

      // Save to localStorage immediately
      localStorage.setItem(\'mynote_audioMode\', mode);

      // Save to server asynchronously
      $.ajax({
        url: \'save_audio_mode.php\',
        method: \'POST\',
        data: {
          contentsid: \'<?php echo $contentsid; ?>\',
          wboardid: \'<?php echo $wboardid; ?>\',
          audio_mode: mode,
          userid: \'<?php echo $studentid; ?>\'
        },
        success: function(response) {
          console.log(\'[Audio Toggle] Preference saved:\', response);
        },
        error: function(xhr, status, error) {
          console.error(\'[Audio Toggle] Save failed:\', error);
        }
      });
    }

    // Full Audio Playback Controls
    window.playFullAudio = function() {
      console.log(\'[Full Audio] Play requested\');
      if(!fullAudioPlayer) {
        console.error(\'[Full Audio ERROR] 파일: mynote.php, 위치: playFullAudio\');
        console.error(\'[Full Audio ERROR] Player not found\');
        return;
      }

      fullAudioPlayer.play().catch(err => {
        console.error(\'[Full Audio ERROR] 파일: mynote.php, 위치: playFullAudio\');
        console.error(\'[Full Audio ERROR] Play failed:\', err.message);
      });
    };

    window.pauseFullAudio = function() {
      console.log(\'[Full Audio] Pause requested\');
      if(!fullAudioPlayer) {
        console.error(\'[Full Audio ERROR] 파일: mynote.php, 위치: pauseFullAudio\');
        return;
      }

      fullAudioPlayer.pause();
    };

    // Update progress bar and time display
    function updateFullAudioProgress() {
      if(!fullAudioPlayer) return;

      const current = fullAudioPlayer.currentTime;
      const duration = fullAudioPlayer.duration;

      if(!isNaN(duration)) {
        const percent = (current / duration) * 100;
        const progressBar = document.getElementById(\'fullAudioProgressBar\');
        const timeDisplay = document.getElementById(\'fullAudioTime\');

        if(progressBar) progressBar.style.width = percent + \'%\';

        const currentMin = Math.floor(current / 60);
        const currentSec = Math.floor(current % 60);
        const durationMin = Math.floor(duration / 60);
        const durationSec = Math.floor(duration % 60);

        if(timeDisplay) {
          timeDisplay.textContent =
            `${currentMin}:${currentSec.toString().padStart(2, \'0\')} / ${durationMin}:${durationSec.toString().padStart(2, \'0\')}`;
        }
      }
    }

    // Attach event listeners to fullAudioPlayer
    if(fullAudioPlayer) {
      fullAudioPlayer.addEventListener(\'timeupdate\', updateFullAudioProgress);
      fullAudioPlayer.addEventListener(\'ended\', function() {
        console.log(\'[Full Audio] Playback ended\');
      });
      fullAudioPlayer.addEventListener(\'error\', function(e) {
        console.error(\'[Full Audio ERROR] 파일: mynote.php, 위치: audio player error event\');
        console.error(\'[Full Audio ERROR] Error loading audio:\', e);
      });
    }

  } catch(error) {
    console.error(\'[Audio Toggle ERROR] 파일: mynote.php, 위치: audio toggle initialization\');
    console.error(\'[Audio Toggle ERROR] 상세:\', error.message);
    console.error(\'[Audio Toggle ERROR] 스택:\', error.stack);
  }
})();
</script>';
?>
