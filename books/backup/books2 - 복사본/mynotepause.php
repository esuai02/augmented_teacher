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

  // 오디오 아이콘과 반복청취 횟수 설정
  $flagicon = ''; // 깃발 아이콘 초기화
  if($value['audiourl']!=NULL || $value['audiourl2']!=NULL) {
    // 헤드폰 아이콘을 클릭 가능하게 만들기 (수업 엿듣기 재생성)
    $audioicon=' <span class="regenerate-audio-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); regenerateClassroomAudio(\''.$contentsid.'\');" style="cursor:pointer; font-size:0.9em;" title="수업 엿듣기 재생성">🎧</span>';
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
    // 오디오가 없을 때 클릭 가능한 헤드폰 아이콘과 깃발 아이콘 생성
    $audioicon=' <span class="generate-audio-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); handleAudioGeneration(\''.$contentsid.'\');" style="cursor:pointer; color:#007bff; font-size:0.9em;" title="나레이션 생성">🎧</span>';

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
  $presetfunction='ConnectNeurons';
  $width1=80; 
  $width2=20;
 
  if($pgtype==='quiz')
  {    
    $showpage='https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid;
    
    if($learningstyle==='도제' && strpos($title, '대표')!==false) echo '';
    elseif(strpos($title, '유형')!== false) {
      $contentslist2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.' '.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
    elseif(strpos($title, '복습')!== false) {
      $contentslist3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width=15> '.$title.'</a>'.$audioicon.$flagicon.' <input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/></td></tr>';
    }
    else {
      $contentslist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.' '.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
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
      </style>
      <div class="audio-container" id="audioContainer">';

      // First audio player
      if($cnttext->audiourl !== NULL) {
        $audiocnt .= '
        <table style="width: 100%; padding: 0; margin: 0; border-spacing: 0; background: transparent !important;">
          <tr>
            <td style="padding: 2px; background: transparent !important;">
              <div class="audio-player-wrapper" id="audioWrapper1">
                <audio id="audioPlayer" controls style="width:270px;height:30px;">
                  <source src="'.$cnttext->audiourl.'" type="audio/mpeg">
                </audio>
                <span class="audio-tooltip" id="tooltip1">수업 엿듣기</span>
              </div>
            </td>
            <td style="padding: 2px; background: transparent !important;">
              <div class="speed-slider-wrapper" id="speedWrapper1" style="position: relative;">
                <input type="range" id="speedSlider" min="1.0" max="2.0" step="0.1" value="1.0" style="width:80px;height:30px;" list="speedMarks1">
                <datalist id="speedMarks1">
                  <option value="1.0" label="1.0"></option>
                  <option value="1.2"></option>
                  <option value="1.4"></option>
                  <option value="1.6"></option>
                  <option value="1.8"></option>
                  <option value="2.0" label="2.0"></option>
                </datalist>
                <span id="speedTooltip1" class="speed-tooltip" style="position: absolute; left: 50%; bottom: 35px; transform: translateX(-50%); background: rgba(0, 0, 0, 0.8); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; white-space: nowrap; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; z-index: 1000;">1.0x</span>
              </div>
            </td>
          </tr>
        </table>';
      }

      // Second audio player (절차기억 또는 듣기평가)
      if($cnttext->audiourl2 !== NULL) {
        // 듣기평가 모드 확인 (reflections1 필드에 구간 정보가 있는지 확인)
        $isListeningTest = false;
        $sectionData = null;
        
        // 듣기평가 모드 확인 (디버깅 정보 제거, 에러 로그만 유지)
        if(!empty($cnttext->reflections1)) {
          error_log("DEBUG - reflections1 원본: " . $cnttext->reflections1);
          
          $decoded = json_decode($cnttext->reflections1, true);
          error_log("DEBUG - JSON 디코드 결과: " . print_r($decoded, true));
          if(isset($decoded['mode']) && $decoded['mode'] === 'listening_test') {
            $isListeningTest = true;
            $sectionData = $decoded;
            error_log("DEBUG - 듣기평가 모드 활성화됨");
          } else {
            error_log("DEBUG - 듣기평가 모드 아님. mode 값: " . (isset($decoded['mode']) ? $decoded['mode'] : 'null'));
          }
        } else {
          error_log("DEBUG - reflections1 필드가 비어있음. audiourl2: " . $cnttext->audiourl2);
        }
        
        if($isListeningTest && $sectionData) {
          // 듣기평가 맞춤 인터페이스
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
            }
            
            .listening-test-container.minimized {
              width: 60px;
              height: 60px;
              border-radius: 50%;
              cursor: pointer;
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
            }
            
            .listening-next-btn {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              color: white;
              border: none;
              padding: 12px;
              border-radius: 8px;
              font-size: 14px;
              font-weight: 600;
              cursor: not-allowed;
              width: 100%;
              transition: all 0.3s;
              box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .listening-next-btn:not(:disabled) {
              cursor: pointer;
              background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            }
            
            .listening-next-btn:not(:disabled):hover {
              transform: translateY(-2px);
              box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            }
            
            .listening-next-btn:disabled {
              background: #ccc;
            }
            .listening-next-btn:not(:disabled) {
              background-color: #4CAF50;
              cursor: pointer;
            }
            .listening-next-btn:not(:disabled):hover {
              background-color: #45a049;
            }
            .listening-audio-hidden {
              width: 270px;
              height: 30px;
            }
            
            /* Progress Dots */
            .listening-progress-dots {
              display: flex;
              justify-content: center;
              align-items: center;
              gap: 8px;
              margin: 15px 0 10px 0;
              padding: 10px 0;
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
          </style>
          
          <div class="listening-test-container" id="listeningContainer">
            <div class="listening-header">
              <div class="listening-progress" id="listeningProgress">
                🎧 구간 1/'.$sectionCount.'
                '.($role !== 'student' ? '<a href="improveprompt.php?cid='.$contentsid.'&ctype='.$contentstype.'" target="_blank" style="margin-left:8px;cursor:pointer;text-decoration:none;font-size:0.85em;" title="프롬프트 편집">✏️</a>' : '').'
                '.($role !== 'student' ? '<a href="../LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$USER->id.'" target="_blank" style="margin-left:5px;cursor:pointer;text-decoration:none;font-size:0.85em;" title="프롬프트 상세">📄</a>' : '').'
              </div>
              <button class="listening-minimize-btn" id="minimizeBtn" onclick="toggleListeningPlayer()">−</button>
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
          
          // Progress dots 생성
          $audiocnt .= '
              <div class="listening-progress-dots" id="progressDots">';
          
          for($i = 0; $i < $sectionCount; $i++) {
            $activeClass = ($i === 0) ? 'active' : '';
            $audiocnt .= '<div class="progress-dot '.$activeClass.'" data-section="'.$i.'" title="구간 '.($i+1).'"></div>';
          }
          
          $audiocnt .= '
              </div>
              <button class="listening-next-btn" id="listeningNextBtn" disabled>
                다음 구간 (2/'.$sectionCount.')
              </button>
            </div>
          </div>
          
          <script>
            // 플레이어 최소화/최대화 토글
            function toggleListeningPlayer() {
              const container = document.getElementById("listeningContainer");
              const minimizeBtn = document.getElementById("minimizeBtn");
              
              if(container.classList.contains("minimized")) {
                container.classList.remove("minimized");
                minimizeBtn.textContent = "−";
                // 최대화 시 중앙 하단으로 재설정
                container.style.left = "50%";
                container.style.bottom = "20px";
                container.style.right = "auto";
                container.style.top = "auto";
                container.style.transform = "translateX(-50%)";
              } else {
                container.classList.add("minimized");
                minimizeBtn.textContent = "+";
              }
            }
            
            // 드래그 앤 드롭 기능
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
                if(e.target === document.getElementById("minimizeBtn")) {
                  return; // 최소화 버튼은 드래그 제외
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
              
              // 플레이어를 클릭하면 최대화
              container.addEventListener("click", function(e) {
                if(container.classList.contains("minimized") && e.target === container) {
                  toggleListeningPlayer();
                }
              });
              
              // 페이지 로드 시 초기 위치 설정
              window.addEventListener("load", setInitialPosition);
            })();
            
            (function() {
              const sectionFiles = '.json_encode($sectionFiles).';
              const sectionCount = '.$sectionCount.';
              let currentSection = 0;
              let audioPlayer2 = document.getElementById("audioPlayer2");
              let nextBtn = document.getElementById("listeningNextBtn");
              let progress = document.getElementById("listeningProgress");
              
              // 오디오 재생 종료 이벤트
              audioPlayer2.addEventListener("ended", function() {
                if(currentSection < sectionCount - 1) {
                  // 다음 구간이 있으면 버튼 활성화
                  nextBtn.disabled = false;
                  nextBtn.textContent = "다음 구간 ("+(currentSection+2)+"/"+sectionCount+")";
                } else {
                  // 마지막 구간 완료
                  progress.textContent = "✅ 완료!";
                  nextBtn.textContent = "✅ 완료";
                  nextBtn.disabled = true;
                  
                  // 모든 dots를 완료 상태로
                  const dots = document.querySelectorAll(".progress-dot");
                  dots.forEach(dot => {
                    dot.classList.remove("active");
                    dot.classList.add("completed");
                  });
                }
              });
              
              // 다음 버튼 클릭
              nextBtn.addEventListener("click", function() {
                console.log("다음 구간 버튼 클릭 - 현재 구간:", currentSection);
                
                if(currentSection < sectionCount - 1) {
                  // 현재 텍스트 숨기기
                  const currentTextDiv = document.getElementById("listeningText"+(currentSection+1));
                  if(currentTextDiv) {
                    currentTextDiv.classList.remove("active");
                  }
                  
                  // 다음 구간으로 이동
                  currentSection++;
                  console.log("다음 구간으로 이동:", currentSection, "파일:", sectionFiles[currentSection]);
                  
                  // 다음 텍스트 표시
                  const nextTextDiv = document.getElementById("listeningText"+(currentSection+1));
                  if(nextTextDiv) {
                    nextTextDiv.classList.add("active");
                  }
                  
                  // 진행 상황 업데이트
                  progress.textContent = "🎧 구간 "+(currentSection+1)+"/"+sectionCount;
                  
                  // 오디오 정지
                  audioPlayer2.pause();
                  audioPlayer2.currentTime = 0;
                  
                  // 다음 오디오 로드 및 재생
                  audioPlayer2.src = sectionFiles[currentSection];
                  audioPlayer2.load();
                  
                  // 로드 완료 후 재생
                  audioPlayer2.addEventListener("loadeddata", function playNext() {
                    audioPlayer2.play().catch(e => console.error("재생 오류:", e));
                    // 이벤트 리스너 제거 (한 번만 실행)
                    audioPlayer2.removeEventListener("loadeddata", playNext);
                  });
                  
                  // 버튼 비활성화
                  nextBtn.disabled = true;
                  
                  // Progress dots 업데이트
                  updateProgressDots();
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
              
              // Progress dots 클릭 이벤트
              const progressDots = document.querySelectorAll(".progress-dot");
              progressDots.forEach((dot, index) => {
                dot.addEventListener("click", function() {
                  if(index === currentSection) return; // 현재 구간은 무시
                  
                  console.log("Dot 클릭 - 구간 이동:", currentSection, "→", index);
                  
                  // 현재 텍스트 숨기기
                  const currentTextDiv = document.getElementById("listeningText"+(currentSection+1));
                  if(currentTextDiv) {
                    currentTextDiv.classList.remove("active");
                  }
                  
                  // 오디오 정지
                  audioPlayer2.pause();
                  audioPlayer2.currentTime = 0;
                  
                  // 구간 이동
                  currentSection = index;
                  
                  // 새 텍스트 표시
                  const newTextDiv = document.getElementById("listeningText"+(currentSection+1));
                  if(newTextDiv) {
                    newTextDiv.classList.add("active");
                  }
                  
                  // 진행 상황 업데이트
                  progress.textContent = "🎧 구간 "+(currentSection+1)+"/"+sectionCount;
                  
                  // 오디오 로드 및 재생
                  audioPlayer2.src = sectionFiles[currentSection];
                  audioPlayer2.load();
                  
                  audioPlayer2.addEventListener("loadeddata", function playJump() {
                    audioPlayer2.play().catch(e => console.error("재생 오류:", e));
                    audioPlayer2.removeEventListener("loadeddata", playJump);
                  });
                  
                  // Progress dots 업데이트
                  updateProgressDots();
                  
                  // 버튼 비활성화
                  nextBtn.disabled = true;
                });
              });
              
              // 첫 구간 자동 재생
              setTimeout(function() {
                audioPlayer2.play().catch(e => console.log("자동재생 차단됨"));
              }, 500);
            })();
          </script>';
          
        } else {
          // 기존 방식: 일반 오디오 플레이어
          $audiocnt .= '
          <table style="width: 100%; padding: 0; margin: 5px 0 0 0; border-spacing: 0; background: transparent !important;">
            <tr>
              <td style="padding: 2px; background: transparent !important;">
                <div class="audio-player-wrapper" id="audioWrapper2">
                  <audio id="audioPlayer2" controls style="width:270px;height:30px;">
                    <source src="'.$cnttext->audiourl2.'" type="audio/mpeg">
                  </audio>
                  <span class="audio-tooltip" id="tooltip2">절차기억 연습하기</span>
                </div>
              </td>
              <td style="padding: 2px; background: transparent !important;">
                <div class="speed-slider-wrapper" id="speedWrapper2" style="position: relative;">
                  <input type="range" id="speedSlider2" min="1.0" max="2.0" step="0.1" value="1.0" style="width:80px;height:30px;" list="speedMarks2">
                  <datalist id="speedMarks2">
                    <option value="1.0" label="1.0"></option>
                    <option value="1.2"></option>
                    <option value="1.4"></option>
                    <option value="1.6"></option>
                    <option value="1.8"></option>
                    <option value="2.0" label="2.0"></option>
                  </datalist>
                  <span id="speedTooltip2" class="speed-tooltip" style="position: absolute; left: 50%; bottom: 35px; transform: translateX(-50%); background: rgba(0, 0, 0, 0.8); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; white-space: nowrap; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; z-index: 1000;">1.0x</span>
                </div>
              </td>
            </tr>
          </table>';
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
          // 듣기평가 모드인지 확인
          const isListeningTestMode = document.getElementById("listeningNextBtn") !== null;
          
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
      $imageupload='<span style="background-color:lightgreen;" id="image_upload" type="button" class="" data-toggle="collapse" data-target="#demo">image+</span>';
    } else {
      $imageupload='';
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
      $contentslist2.='<tr style="background-color:lightpink;"><td><span type="button" onClick="'.$presetfunction.'(\''.$contentsid.'\')">'.$headimg.'</span><b> '.$title.'</b> '.$audioicon.$flagicon.'</td></tr>'.$HippocampusCnt;
    }
    elseif(strpos($title, '복습')!== false) {
      $contentslist3.='<tr><td><span type="button" onClick="'.$presetfunction.'(\''.$contentsid.'\')"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width=15></span> '.$title.' '.$audioicon.$flagicon.' <input type="checkbox" onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/></td></tr>';
    }
    else {
      $contentslist.='<tr style="background-color:lightblue;"><td><span type="button" onClick="'.$presetfunction.'(\''.$contentsid.'\')">'.$headimg.'</span><b> '.$title.'</b> '.$audioicon.$flagicon.'</td></tr>'.$HippocampusCnt;
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
      $contentslist2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.' '.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
    elseif(strpos($title, '복습')!== false) {
      $contentslist3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'"><span  type="button"  onClick="'.$presetfunction.'(\''.$contentsid.'\')"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width=15></span> '.$title.'</a>'.$audioicon.$flagicon.' <input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/></td></tr>';
    }
    else {
      $contentslist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$nch.'&cmid='.$cmid.'&quizid='.$quizid.'&page='.$npage.'&studentid='.$studentid.'">'.$headimg.' '.$title.'</a>'.$audioicon.$flagicon.'</td></tr>';
    }
  }
}

if($role!=='student') {
  $cntlink=' <a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'" target="_blank">
    <img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/cntlink.png" width=15></a>';
}

$singleref=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/connectmemories.php?domain=8&contentstype=2" target="_blank">
  <img loading="lazy" src="https://mathking.kr/Contents/IMAGES/learningpath.png" width=15></a>';

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
                </a>'.$singleref.$cntlink.'
              </td>
            </tr>
            <tr>
              <td align=left width=22vw style="color:#347aeb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <br>'.$rule.' 
                <br><br> 
              </td>
            </tr>
          </table>

          <table width=100%>
            <tr>
              <td width=100%>'.$youtubecontents.'<br><br> '.$tutorasacode.' 기억방으로 '.$imageupload.' 
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

function ConnectNeurons(Contentsid)
{
  var Userid= \''.$studentid.'\';	
  Swal.fire({
    backdrop:false,
    position:"top-end",
    showCloseButton: true,
    width:1200,
    showClass: {
      popup: "animate__animated animate__fadeInDown"
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp"
    },
    html:
      \'<iframe loading="lazy" class="foo" style="border:0px none; z-index:2; width:1180px; height:90vh; margin-left:-20px; margin-bottom:-10px; overflow-x:hidden;" src="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid=\'+Contentsid+\'&cnttype=1&studentid=\'+Userid+\'"></iframe>\',
    showConfirmButton: true,
  })
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

// 헤드폰 아이콘 클릭 핸들러 (인라인 onclick용) - 글로벌 스코프에 등록
window.handleAudioGeneration = function(contentsid) {
  console.log("handleAudioGeneration 호출됨. Contents ID:", contentsid);

  // 확인 대화상자
  Swal.fire({
    title: "나레이션 생성",
    text: "이 콘텐츠의 나레이션을 생성하시겠습니까?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "생성",
    cancelButtonText: "취소"
  }).then((result) => {
    if (result.isConfirmed) {
      generateNarration(contentsid);
    }
  });

  return false; // 이벤트 전파 방지
}

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

// 나레이션 생성 함수
function generateNarration(contentsid) {
  // 로딩 표시
  Swal.fire({
    title: "나레이션 생성 중...",
    html: "나레이션과 음성을 생성하고 있습니다.<br>잠시만 기다려주세요.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  // AJAX 요청으로 나레이션 생성
  $.ajax({
    url: "generate_narration.php",
    type: "POST",
    dataType: "json",
    timeout: 120000, // 2분 타임아웃 설정
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
          title: "나레이션 생성 완료!",
          html: "나레이션과 음성이 성공적으로 생성되었습니다.<br>페이지를 새로고침합니다.",
          timer: 2000,
          timerProgressBar: true,
          didClose: () => {
            // 페이지 새로고침
            location.reload();
          }
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "생성 실패",
          text: response.message || "나레이션 생성 중 오류가 발생했습니다."
        });
      }
    },
    error: function(xhr, status, error) {
      // 로딩 다이얼로그 강제 종료 (중요!)
      Swal.close();

      // 상세 에러 정보 콘솔에 출력
      console.error("나레이션 생성 오류 상세:", {
        status: xhr.status,
        statusText: xhr.statusText,
        error: error,
        responseText: xhr.responseText
      });

      // 에러 타입별 메시지 설정
      var errorMessage = "";
      if (xhr.status === 0) {
        errorMessage = "네트워크 오류 또는 타임아웃이 발생했습니다.<br>인터넷 연결을 확인해주세요.";
      } else if (xhr.status === 401) {
        errorMessage = "API 키가 유효하지 않습니다.<br>관리자에게 문의하세요.";
      } else if (xhr.status === 429) {
        errorMessage = "API 사용량 한도를 초과했습니다.<br>잠시 후 다시 시도해주세요.";
      } else if (xhr.status === 500) {
        errorMessage = "서버 오류가 발생했습니다.<br>잠시 후 다시 시도해주세요.";
      } else {
        errorMessage = "나레이션 생성 중 오류가 발생했습니다.<br>상태 코드: " + xhr.status;
      }

      // 사용자에게 에러 메시지 표시
      Swal.fire({
        icon: "error",
        title: "오류 발생",
        html: errorMessage,
        footer: "자세한 내용은 브라우저 콘솔을 확인하세요."
      });
    }
  });
}

// 수업 엿듣기 재생성 함수
window.regenerateClassroomAudio = function(contentsid) {
  console.log("regenerateClassroomAudio 호출됨. Contents ID:", contentsid);

  // 확인 대화상자
  Swal.fire({
    title: "수업 엿듣기 재생성",
    text: "수업 엿듣기 컨텐츠를 새로 생성하시겠습니까?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "재생성",
    cancelButtonText: "취소"
  }).then((result) => {
    if (result.isConfirmed) {
      generateClassroomNarration(contentsid, true);
    }
  });

  return false; // 이벤트 전파 방지
}

// 수업 엿듣기 나레이션 생성/재생성 함수
function generateClassroomNarration(contentsid, isRegenerate) {
  // 로딩 표시
  Swal.fire({
    title: isRegenerate ? "수업 엿듣기 재생성 중..." : "수업 엿듣기 생성 중...",
    html: "새로운 수업 엿듣기 컨텐츠를 생성하고 있습니다.<br>약 1-2분 정도 소요됩니다.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  // AJAX 요청으로 나레이션 생성/재생성
  $.ajax({
    url: "generate_narration.php",
    type: "POST",
    dataType: "json",
    timeout: 120000, // 2분 타임아웃 설정
    data: {
      contentsid: contentsid,
      contentstype: 1,
      generateTTS: "true",
      audioType: "audiourl", // 수업 엿듣기는 audiourl 사용
      regenerate: isRegenerate ? "true" : "false",
      userid: '.$studentid.'
    },
    success: function(response) {
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: isRegenerate ? "재생성 완료!" : "생성 완료!",
          html: "수업 엿듣기 컨텐츠가 성공적으로 " + (isRegenerate ? "재생성" : "생성") + "되었습니다.<br>페이지를 새로고침합니다.",
          timer: 2000,
          timerProgressBar: true,
          showConfirmButton: false
        });
        // 2초 후 페이지 새로고침
        setTimeout(function() {
          location.reload();
        }, 2000);
      } else {
        // 에러 처리
        Swal.fire({
          icon: "error",
          title: "생성 실패",
          text: response.message || "나레이션 생성 중 오류가 발생했습니다."
        });
      }
    },
    error: function(xhr, status, error) {
      console.error("나레이션 생성 실패:", error);
      console.error("응답 상태:", xhr.status);
      console.error("응답 텍스트:", xhr.responseText);

      let errorMessage = "나레이션 생성 중 오류가 발생했습니다.";

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

// jQuery 로드 확인
if (typeof jQuery === "undefined") {
  console.error("jQuery가 로드되지 않았습니다. jQuery를 먼저 로드해주세요.");
} else {
  console.log("jQuery 버전:", jQuery.fn.jquery);
}

// 헤드폰 아이콘 클릭 이벤트
$(document).ready(function() {
  console.log("Document ready - 헤드폰 아이콘 이벤트 바인딩 시작");

  // 페이지 로드 후 아이콘 존재 확인
  setTimeout(function() {
    var icons = $(".generate-audio-icon");
    console.log("발견된 헤드폰 아이콘 수:", icons.length);
    if (icons.length > 0) {
      console.log("헤드폰 아이콘 요소:", icons);
    }
  }, 1000);

  // 동적으로 생성된 요소에 대한 이벤트 위임
  $(document).on("click", ".generate-audio-icon", function(e) {
    console.log("헤드폰 아이콘 클릭됨!");
    e.preventDefault();
    e.stopPropagation();

    var contentsid = $(this).data("contentsid");
    console.log("Contents ID:", contentsid);

    // 확인 대화상자
    Swal.fire({
      title: "나레이션 생성",
      text: "이 콘텐츠의 나레이션을 생성하시겠습니까?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "생성",
      cancelButtonText: "취소"
    }).then((result) => {
      if (result.isConfirmed) {
        generateNarration(contentsid);
      }
    });
  });

  // 대체 방법: 네이티브 JavaScript 이벤트 리스너
  document.body.addEventListener("click", function(e) {
    if (e.target.classList && e.target.classList.contains("generate-audio-icon")) {
      console.log("네이티브 JS로 헤드폰 아이콘 클릭 감지!");
      e.preventDefault();
      e.stopPropagation();

      var contentsid = e.target.getAttribute("data-contentsid");
      console.log("Contents ID (네이티브):", contentsid);

      // jQuery가 있으면 jQuery 방식으로, 없으면 네이티브 방식으로
      if (typeof jQuery !== "undefined" && typeof generateNarration === "function") {
        // generateNarration 함수가 이미 정의되어 있으면 호출
        generateNarration(contentsid);
      } else {
        alert("나레이션 생성 기능을 사용할 수 없습니다. 페이지를 새로고침 해주세요.");
      }
    }
  });

  console.log("헤드폰 아이콘 이벤트 리스너 등록 완료");

  // 디버깅: 페이지 로드 후 아이콘 상태 확인
  window.addEventListener("load", function() {
    console.log("=== 페이지 로드 완료 - 헤드폰 아이콘 디버깅 ===");
    var audioIcons = document.querySelectorAll(".generate-audio-icon");
    console.log("발견된 헤드폰 아이콘 수:", audioIcons.length);

    audioIcons.forEach(function(icon, index) {
      console.log("아이콘 " + (index + 1) + ":");
      console.log("  - Contents ID:", icon.getAttribute("data-contentsid"));
      console.log("  - onclick 속성:", icon.getAttribute("onclick"));
      console.log("  - 계산된 스타일 cursor:", window.getComputedStyle(icon).cursor);
      console.log("  - 계산된 스타일 pointer-events:", window.getComputedStyle(icon).pointerEvents);
      console.log("  - 부모 요소:", icon.parentElement.tagName);

      // 테스트: 첫 번째 아이콘에 배경색 추가하여 시각적 확인
      if (index === 0) {
        icon.style.backgroundColor = "yellow";
        icon.style.padding = "2px";
        console.log("  - 첫 번째 아이콘에 노란 배경 추가됨 (시각적 확인용)");
      }
    });

    // handleAudioGeneration 함수 확인
    console.log("handleAudioGeneration 함수 존재 여부:", typeof window.handleAudioGeneration === "function");
    console.log("generateNarration 함수 존재 여부:", typeof generateNarration === "function");
    console.log("jQuery 로드 여부:", typeof jQuery !== "undefined");
    console.log("SweetAlert2 로드 여부:", typeof Swal !== "undefined");
  });
});
</script>';
?>
