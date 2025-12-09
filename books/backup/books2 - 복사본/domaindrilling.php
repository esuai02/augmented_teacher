<?php
/****************************************************
 * [도메인 그리드 + 체크박스 로직 + 디자인 일체화 코드]
 *  - checkprogress() Ajax 로직 포함
 *  - 4x4 Grid에 아이콘/이름 박스로 표시
 ****************************************************/

// 1) Moodle 환경(DB 연결 등) 세팅
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

// 2) GET 파라미터
$cid       = isset($_GET["cid"]) ? $_GET["cid"] : 0;
$chnum     = isset($_GET["nch"]) ? $_GET["nch"] : 0;
$mode      = isset($_GET["mode"]) ? $_GET["mode"] : '';
$domain    = isset($_GET["domain"]) ? $_GET["domain"] : 0; 
$studentid = isset($_GET["studentid"]) ? $_GET["studentid"] : 0;
$timecreated = time();
$checkitem = 'd'.$domain.'cid'.$cid.'ch'.$chnum;

// studentid가 없으면 현재 로그인 사용자
if (!$studentid) {
  $studentid = $USER->id;
}

// 3) role, username 정보 (예: user_info_data->fieldid=22 가 role임)
$userrole = $DB->get_record_sql("
    SELECT data AS role 
      FROM {user_info_data}
     WHERE userid = :userid
       AND fieldid = 22
", ['userid'=>$USER->id]);
$role = $userrole ? $userrole->role : '';

$userinfo = $DB->get_record_sql("
    SELECT lastname, firstname
      FROM {user}
     WHERE id = :studentid
", ['studentid'=>$studentid]);
$username = $userinfo ? $userinfo->firstname.$userinfo->lastname : 'unknown';

// 4) 유효 도메인 목록 (16개: 120~133, 135~136)
$validDomains = [120,121,122,123,124,125,126,127,128,129,130,131,132,133,135,136];

// domain=134 이거나 $validDomains에 없는 경우 -> 종료
if ($domain == 134 || !in_array($domain, $validDomains)) {
  die('Invalid domain (134 or out of range).');
}

/*************************************************
 * 5) 체크박스 진행현황 로직
 *************************************************/
$checklist= $DB->get_records_sql("
    SELECT * 
      FROM mdl_abessi_topicprogress 
     WHERE userid = :stid
       AND checkitem = :ci
     ORDER BY id
", ['stid'=>$studentid, 'ci'=>$checkitem]);

$result = json_decode(json_encode($checklist), true);
unset($value);

foreach($result as $value) {
  $cstr = 'checked'.$value['checkid'];
  $$cstr = ($value['checked'] == 1) ? 'Checked' : '';
}

// 도메인별 체크박스 HTML 누적
$dmprinciples = '';
for ($d = 125; $d <= 133; $d++) {
  if ($domain == $d) {
    for ($i = 1; $i <= 7; $i++) {
      $checked_var = 'checked'.$i;
      switch($i) {
        case 1: $label = "개념습득 (개념노트)"; break;
        case 2: $label = "연산훈련 (개념예제)"; break;
        case 3: $label = "공식습득 (대표유형 노트)"; break;
        case 4: $label = "공식체화 (주제별 테스트)"; break;
        case 5: $label = "유형습득 (대표유형)"; break;
        case 6: $label = "유형응용 (심화 보강학습)"; break;
        case 7: $label = "추상화 (심화 내신테스트)"; break;
        default: $label = ""; break;
      }
      $dmprinciples .= '<tr><td><input type="checkbox" '.${$checked_var}.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\','.$i.',this.checked)">'.$label.'</td></tr>';
    }
  }
}
if ($domain == 135 || $domain == 136) {
  for ($i = 1; $i <= 7; $i++) {
    $checked_var = 'checked'.$i;
    switch($i) {
      case 1: $label = "개념습득 (개념노트)"; break;
      case 2: $label = "연산훈련 (개념예제)"; break;
      case 3: $label = "공식습득 (대표유형 노트)"; break;
      case 4: $label = "공식체화 (주제별 테스트)"; break;
      case 5: $label = "유형습득 (대표유형)"; break;
      case 6: $label = "유형응용 (심화 보강학습)"; break;
      case 7: $label = "추상화 (심화 내신테스트)"; break;
      default: $label = ""; break;
    }
    $dmprinciples .= '<tr><td><input type="checkbox" '.${$checked_var}.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\','.$i.',this.checked)">'.$label.'</td></tr>';
  }
}

/*************************************************
 * 6) 16개 도메인 그리드 (One Piece 테마)
 *************************************************/
$categories = [
  [ 'name'=>'수체계',          'icon'=>'luffy',     'color'=>'strawhat-red' ],
  [ 'name'=>'지수와 로그',     'icon'=>'zoro',      'color'=>'zoro-green'   ],
  [ 'name'=>'수열',           'icon'=>'nami',      'color'=>'nami-orange'  ],
  [ 'name'=>'식의 계산',       'icon'=>'usopp',     'color'=>'usopp-yellow' ],
  [ 'name'=>'집합과 명제',     'icon'=>'sanji',     'color'=>'sanji-blue'   ],
  [ 'name'=>'방정식',         'icon'=>'chopper',   'color'=>'chopper-pink' ],
  [ 'name'=>'부등식',         'icon'=>'robin',     'color'=>'robin-purple' ],
  [ 'name'=>'함수',           'icon'=>'franky',    'color'=>'franky-cyan'  ],
  [ 'name'=>'미분',           'icon'=>'brook',     'color'=>'brook-black'  ],
  [ 'name'=>'적분',           'icon'=>'jinbe',     'color'=>'jinbe-blue'   ],
  [ 'name'=>'평면도형',        'icon'=>'shanks',    'color'=>'shanks-red'   ],
  [ 'name'=>'평면좌표',        'icon'=>'law',       'color'=>'law-yellow'   ],
  [ 'name'=>'입체도형',        'icon'=>'ace',       'color'=>'ace-orange'   ],
  [ 'name'=>'공간좌표',        'icon'=>'sabo',      'color'=>'sabo-blue'    ],
  [ 'name'=>'경우의 수와 확률', 'icon'=>'hancock',   'color'=>'hancock-red'  ],
  [ 'name'=>'통계',           'icon'=>'mihawk',    'color'=>'mihawk-green' ],
];

function domainToIndex($domain) {
  if ($domain >= 120 && $domain <= 133) {
    return $domain - 120;
  } elseif ($domain == 135) {
    return 14;
  } elseif ($domain == 136) {
    return 15;
  }
  return -1;
}
$idx = domainToIndex($domain);
if ($idx < 0 || $idx > 15) {
  die('Invalid mapping index.');
}
$selected = $categories[$idx];

$characterIcons = [
  'luffy'   => '⚓',     
  'zoro'    => '⚔️',    
  'nami'    => '🌩️',    
  'usopp'   => '🔫',    
  'sanji'   => '🔥',    
  'chopper' => '🦌',    
  'robin'   => '👐',    
  'franky'  => '🤖',    
  'brook'   => '🎸',    
  'jinbe'   => '🌊',    
  'shanks'  => '🏴‍☠️',  
  'law'     => '📐',    
  'ace'     => '🧊',    
  'sabo'    => '🥽',    
  'hancock' => '🎲',    
  'mihawk'  => '🗡️',    
];

// 현재 학생의 active 등록 여부 확인
$registrationActive = false;
$registration = $DB->get_record_sql("
    SELECT * FROM {abessi_registration}
    WHERE studentid = :studentid AND status = 'active'
    ORDER BY id DESC LIMIT 1
", ['studentid' => $studentid]);
if ($registration) {
  $registrationActive = true;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>수학항해</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
  <style>
    /* 기본 스타일 */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: url('') repeat;
      background-color: #002147;
      color: #fff;
      background-size: cover;
      background-attachment: fixed;
      background-position: center;
    }
    .container {
      max-width: 1024px;
      margin: 0 auto;
      padding: 20px;
    }
    /* 헤더 스타일 */
    .heading {
      text-align: center;
      margin-bottom: 30px;
      text-transform: uppercase;
      letter-spacing: 2px;
      font-weight: 700;
      color: #ffd700;
      text-shadow: 3px 3px 0 #c00d0d, 6px 6px 0 #000;
      font-size: 2.5rem;
      transform: skew(-5deg);
    }
    .subheading {
      text-align: center;
      margin-bottom: 20px;
      font-size: 1.5rem;
      background-color: rgba(0,0,0,0.7);
      border-radius: 30px;
      padding: 8px 15px;
      display: inline-block;
      border: 2px solid #ffd700;
    }
    .header-wrapper {
      text-align: center;
      position: relative;
    }
    .header-wrapper:before,
    .header-wrapper:after {
      content: "☠️";
      font-size: 2rem;
      position: relative;
      top: 5px;
      margin: 0 10px;
    }
    /* 그리드 스타일 */
    .grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      grid-gap: 20px;
      margin-bottom: 40px;
    }
    .grid-item {
      background: linear-gradient(135deg, #8B4513 0%, #CD853F 100%);
      border: 3px solid #ffd700;
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 20px rgba(0,0,0,0.3);
    }
    .grid-item:hover {
      transform: translateY(-10px) scale(1.05);
      box-shadow: 0 15px 30px rgba(0,0,0,0.4);
    }
    .icon {
      font-size: 3rem;
      margin-bottom: 15px;
      display: block;
      transition: transform 0.5s ease;
    }
    .grid-item:hover .icon {
      transform: rotate(360deg);
    }
    .title {
      font-size: 1.1rem;
      font-weight: 600;
      text-shadow: 2px 2px 0 #000;
    }
    /* 캐릭터 컬러 */
    .strawhat-red { color: #ff0000; }
    .zoro-green { color: #009900; }
    .nami-orange { color: #ff9900; }
    .usopp-yellow { color: #ffcc00; }
    .sanji-blue { color: #3366ff; }
    .chopper-pink { color: #ff66cc; }
    .robin-purple { color: #9900cc; }
    .franky-cyan { color: #00ccff; }
    .brook-black { color: #cccccc; }
    .jinbe-blue { color: #006699; }
    .shanks-red { color: #cc0000; }
    .law-yellow { color: #cccc00; }
    .ace-orange { color: #ff6600; }
    .sabo-blue { color: #0066cc; }
    .hancock-red { color: #ff3366; }
    .mihawk-green { color: #006633; }
    .highlight {
      box-shadow: 0 0 30px 5px #ffd700;
      transform: scale(1.08);
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0% { box-shadow: 0 0 30px 5px #ffd700; }
      50% { box-shadow: 0 0 50px 10px #ffd700; }
      100% { box-shadow: 0 0 30px 5px #ffd700; }
    }
    /* 수강신청/수강중 버튼 */
    #registerBtn {
      display: block;
      margin: 30px auto;
      padding: 15px 25px;
      font-size: 1.2rem;
      background-color: #ffd700;
      color: #002147;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      box-shadow: 0 5px 10px rgba(0,0,0,0.3);
      transition: background-color 0.3s ease;
    }
    #registerBtn:hover { background-color: #ffc107; }
    /* 팝업 모달 (원피스 두루마리 스타일) */
    .modal {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background: rgba(0,0,0,0.6);
    }
    .modal-content {
      background: url('') no-repeat center center;
      background-size: contain;
      background-color:rgb(232, 252, 143);
      margin: 10% auto;
      padding: 30px 20px;
      border: 3px solid #ffd700;
      width: 320px;
      border-radius: 15px;
      color: #002147;
      box-shadow: 0 10px 20px rgba(0,0,0,0.5);
      position: relative;
    }
    .close {
      color: #c00;
      position: absolute;
      right: 15px;
      top: 10px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover { color: #000; }
    /* 팝업 내부 form 스타일 */
    #registrationForm label {
      font-weight: bold;
      margin-top: 10px;
      display: block;
    }
    #registrationForm select,
    #registrationForm input[type="date"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    /* 수강료 계산표 */
    #tuitionTable {
      width: 100%;
      margin-top: 15px;
      border-collapse: collapse;
      text-align: center;
    }
    #tuitionTable th,
    #tuitionTable td {
      border: 1px solid #ccc;
      padding: 8px;
    }
    /* 신청하기 버튼 (팝업 내부) */
    #submitRegistration {
      width: 100%;
      padding: 10px;
      background-color: #ffd700;
      color: #002147;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      cursor: pointer;
      font-weight: bold;
      box-shadow: 0 3px 8px rgba(0,0,0,0.3);
      transition: background-color 0.3s ease;
    }
    #submitRegistration:hover {
      background-color: #ffc107;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- 헤더 -->
    <div class="header-wrapper">
      <h1 class="heading">Grand Line 수학 항해</h1>
      <p class="subheading">
        항해중인 영역: <?php echo $selected['name']; ?> | 항해사: <?php echo $username; ?>
      </p>
    </div>

    <!-- 도메인 그리드 -->
    <div class="grid">
    <?php
      foreach ($validDomains as $dom) {
        $i = domainToIndex($dom);
        if ($i < 0) continue;
        $cat = $categories[$i];
        $highlightClass = ($dom == $domain) ? 'highlight' : '';
        $jumpUrl = "chapterdrilling.php?domain={$dom}&studentid={$studentid}";
        $characterIcon = $characterIcons[$cat['icon']];
        echo "
        <div class='grid-item $highlightClass' onclick=\"location.href='$jumpUrl'\">
          <div class='icon {$cat['color']}'>
            {$characterIcon}
          </div>
          <div class='title'>
            {$cat['name']}
          </div>
        </div>
        ";
      }
    ?>
    </div>

    <!-- 수강신청/수강중 버튼 -->
    <?php if ($registrationActive): ?>
      <button id="registerBtn">수강중</button>
    <?php else: ?>
      <button id="registerBtn">수강신청</button>
    <?php endif; ?>

    <!-- 팝업 모달: 수강신청 폼 -->
    <div id="registerModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2 style="text-align:center; margin-bottom:15px;">수강신청</h2>
        <form id="registrationForm">
          <label for="courseType">수강유형</label>
          <select id="courseType" name="courseType">
            <option value="개념복습">개념복습</option>
            <option value="유형연습">유형연습</option>
          </select>
          <label for="startDate">시작일</label>
          <input type="date" id="startDate" name="startDate" required>
          <label for="studyHours">주별 공부시간</label>
          <select id="studyHours" name="studyHours">
            <option value="3">3시간</option>
            <option value="4">4시간</option>
            <option value="5">5시간</option>
            <option value="6">6시간</option>
          </select>
        </form>
        <h3 style="text-align:center; margin-top:10px;">수강료 계산표</h3>
        <table id="tuitionTable">
          <tr>
            <th>주별 공부시간</th>
            <th>수강료 (KRW)</th>
          </tr>
          <tr>
            <td id="selectedHours">3시간</td>
            <td id="tuitionFee">60,000</td>
          </tr>
        </table>
        <!-- 신청하기 버튼 (팝업 내부) -->
        <button id="submitRegistration">신청하기</button>
      </div>
    </div>

  </div>

  <script>
    // 모달과 버튼 요소
    var modal = document.getElementById("registerModal");
    var btn = document.getElementById("registerBtn");
    var span = document.getElementsByClassName("close")[0];

    // 버튼 클릭 이벤트
    btn.onclick = function() {
      if (btn.innerText === "수강중") {
        if (confirm("수강을 중단하시겠습니까?")) {
          var formData = new FormData();
          formData.append("studentid", "<?php echo $studentid; ?>");
          fetch("cancel_registration.php", {
            method: "POST",
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            alert("수강 중단됨: " + data);
            btn.innerText = "수강신청";
          })
          .catch(error => {
            alert("수강 중단 실패: " + error);
          });
        }
      } else {
        modal.style.display = "block";
        updateTuition();
        document.getElementById("submitRegistration").style.display = "block";
      }
    }

    // 닫기 버튼 및 모달 외부 클릭 시 모달 닫기
    span.onclick = function() { modal.style.display = "none"; }
    window.onclick = function(event) {
      if (event.target == modal) { modal.style.display = "none"; }
    }

    // 수강료 업데이트
    document.getElementById("studyHours").addEventListener("change", updateTuition);
    function updateTuition() {
      var hours = document.getElementById("studyHours").value;
      document.getElementById("selectedHours").innerText = hours + "시간";
      var fee = hours * 20000;
      document.getElementById("tuitionFee").innerText = fee.toLocaleString();
    }

    // 신청하기 버튼 클릭: Ajax 등록 처리
    document.getElementById("submitRegistration").addEventListener("click", function() {
      var courseType = document.getElementById("courseType").value;
      var startDateValue = document.getElementById("startDate").value;
      var studyHours = document.getElementById("studyHours").value;
      if (!startDateValue) {
        alert("시작일을 선택해주세요.");
        return;
      }
      var startDateUnix = Math.floor(new Date(startDateValue).getTime() / 1000);
      var timeCreated = Math.floor(Date.now() / 1000);
      var studentid = "<?php echo $studentid; ?>";
      
      var formData = new FormData();
      formData.append("courseType", courseType);
      formData.append("startDate", startDateUnix);
      formData.append("studyHours", studyHours);
      formData.append("studentid", studentid);
      formData.append("timecreated", timeCreated);
      
      fetch("save_registration.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        alert("등록 성공: " + data);
        modal.style.display = "none";
        btn.innerText = "수강중";
      })
      .catch(error => {
        alert("등록 실패: " + error);
      });
    });
  </script>
</body>
</html>
