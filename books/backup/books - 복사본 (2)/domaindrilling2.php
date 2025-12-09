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
 *    - mdl_abessi_topicprogress 에서 checkid 별 값 읽어옴
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

// 'checked1', 'checked2'... 이런 변수를 동적으로 세팅
foreach($result as $value) {
  $cstr = 'checked'.$value['checkid'];
  if($value['checked'] == 1) {
    $$cstr = 'Checked';
  } else {
    $$cstr = '';
  }
}

// $dmprinciples 에 도메인별 체크박스 HTML 누적
$dmprinciples = '';

if ($domain == 120) {
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked1.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',1,this.checked)">개념습득 (개념노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked2.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',2,this.checked)">연산훈련 (개념예제)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked3.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',3,this.checked)">공식습득 (대표유형 노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked4.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',4,this.checked)">공식체화 (주제별 테스트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked5.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',5,this.checked)">유형습득 (대표유형)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked6.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',6,this.checked)">유형응용 (심화 보강학습)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked7.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',7,this.checked)">추상화 (심화 내신테스트)</td></tr>';
}
elseif ($domain == 121) {
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked1.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',1,this.checked)">개념습득 (개념노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked2.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',2,this.checked)">연산훈련 (개념예제)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked3.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',3,this.checked)">공식습득 (대표유형 노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked4.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',4,this.checked)">공식체화 (주제별 테스트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked5.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',5,this.checked)">유형습득 (대표유형)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked6.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',6,this.checked)">유형응용 (심화 보강학습)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked7.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',7,this.checked)">추상화 (심화 내신테스트)</td></tr>';
}
elseif ($domain == 122) {
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked1.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',1,this.checked)">개념습득 (개념노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked2.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',2,this.checked)">연산훈련 (개념예제)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked3.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',3,this.checked)">공식습득 (대표유형 노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked4.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',4,this.checked)">공식체화 (주제별 테스트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked5.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',5,this.checked)">유형습득 (대표유형)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked6.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',6,this.checked)">유형응용 (심화 보강학습)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked7.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',7,this.checked)">추상화 (심화 내신테스트)</td></tr>';
}
elseif ($domain == 123) {
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked1.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',1,this.checked)">개념습득 (개념노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked2.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',2,this.checked)">연산훈련 (개념예제)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked3.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',3,this.checked)">공식습득 (대표유형 노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked4.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',4,this.checked)">공식체화 (주제별 테스트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked5.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',5,this.checked)">유형습득 (대표유형)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked6.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',6,this.checked)">유형응용 (심화 보강학습)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked7.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',7,this.checked)">추상화 (심화 내신테스트)</td></tr>';
}
elseif ($domain == 124) {
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked1.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',1,this.checked)">개념습득 (개념노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked2.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',2,this.checked)">연산훈련 (개념예제)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked3.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',3,this.checked)">공식습득 (대표유형 노트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked4.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',4,this.checked)">공식체화 (주제별 테스트)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked5.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',5,this.checked)">유형습득 (대표유형)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked6.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',6,this.checked)">유형응용 (심화 보강학습)</td></tr>';
  $dmprinciples .= '<tr><td><input type="checkbox" '.$checked7.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\',7,this.checked)">추상화 (심화 내신테스트)</td></tr>';
}

// 원본과 동일한 구조지만 모든 도메인에 대한 반복을 간략히 표현
for ($d = 125; $d <= 133; $d++) {
  if ($domain == $d) {
    for ($i = 1; $i <= 7; $i++) {
      $checked_var = 'checked'.$i;
      switch($i) {
        case 1:
          $label = "개념습득 (개념노트)";
          break;
        case 2:
          $label = "연산훈련 (개념예제)";
          break;
        case 3:
          $label = "공식습득 (대표유형 노트)";
          break;
        case 4:
          $label = "공식체화 (주제별 테스트)";
          break;
        case 5:
          $label = "유형습득 (대표유형)";
          break;
        case 6:
          $label = "유형응용 (심화 보강학습)";
          break;
        case 7:
          $label = "추상화 (심화 내신테스트)";
          break;
        default:
          $label = "";
          break;
      }
      $dmprinciples .= '<tr><td><input type="checkbox" '.${$checked_var}.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\','.$i.',this.checked)">'.$label.'</td></tr>';
    }
  }
}
if ($domain == 135 || $domain == 136) {
  for ($i = 1; $i <= 7; $i++) {
    $checked_var = 'checked'.$i;
    switch($i) {
      case 1:
        $label = "개념습득 (개념노트)";
        break;
      case 2:
        $label = "연산훈련 (개념예제)";
        break;
      case 3:
        $label = "공식습득 (대표유형 노트)";
        break;
      case 4:
        $label = "공식체화 (주제별 테스트)";
        break;
      case 5:
        $label = "유형습득 (대표유형)";
        break;
      case 6:
        $label = "유형응용 (심화 보강학습)";
        break;
      case 7:
        $label = "추상화 (심화 내신테스트)";
        break;
      default:
        $label = "";
        break;
    }
    $dmprinciples .= '<tr><td><input type="checkbox" '.${$checked_var}.' onclick="Checkprogress(\''.$studentid.'\',\''.$checkitem.'\','.$i.',this.checked)">'.$label.'</td></tr>';
  }
}

/*************************************************
 * 6) 16개 도메인 그리드 (One Piece 테마 아이콘/이름)
 *************************************************/

// One Piece 스타일로 수학 주제 매핑
$categories = [
  // 각 항목은 [이름, One Piece 캐릭터 이미지 URL, 배경색]
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

// domain -> categories 배열 index 매핑
function domainToIndex($domain) {
  // 120~133 => index = domain - 120
  // 135 => 14, 136 => 15
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

// One Piece 캐릭터 아이콘 매핑 (실제로는 이미지 경로 설정)
$characterIcons = [
  'luffy' => '⚓',     // 앵커 (루피의 상징)
  'zoro' => '⚔️',      // 검 (조로의 삼도류)
  'nami' => '🌩️',      // 번개 (나미의 날씨 기술)
  'usopp' => '🔫',     // 슬링샷 (우솝의 무기)
  'sanji' => '🔥',     // 불 (상디의 발차기)
  'chopper' => '🦌',   // 사슴 (쵸파의 원형)
  'robin' => '👐',     // 손 (로빈의 능력)
  'franky' => '🤖',    // 로봇 (프랑키의 개조된 몸)
  'brook' => '🎸',     // 기타 (브룩의 악기)
  'jinbe' => '🌊',     // 파도 (징베의 어부살법)
  'shanks' => '🏴‍☠️',   // 해적기 (샹크스의 해적단)
  'law' => '⚕️',       // 의학 상징 (로의 의사 배경)
  'ace' => '🔥',       // 불 (에이스의 능력)
  'sabo' => '🔥',      // 불 (사보의 능력)
  'hancock' => '💘',   // 하트 (핸콕의 사랑 능력)
  'mihawk' => '🗡️',    // 검 (미호크의 검)
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>원피스 테마 - 수학 도메인</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
  <style>
    /* 원피스 테마 스타일 */
    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #002147; /* 바다 배경색 */
      color: #fff;
      background-image: url('https://i.ibb.co/BG2ghQz/op-bg.jpg');
      background-size: cover;
      background-attachment: fixed;
      background-position: center;
    }
    
    .container {
      max-width: 1024px;
      margin: 0 auto;
      padding: 20px;
    }
    
    /* 원피스 로고 스타일의 헤더 */
    .heading {
      text-align: center;
      margin-bottom: 30px;
      text-transform: uppercase;
      letter-spacing: 2px;
      font-weight: 700;
      color: #ffd700; /* 금색 (원피스 로고 색상) */
      text-shadow: 3px 3px 0 #c00d0d, 
                  6px 6px 0 #000000;
      font-size: 2.5rem;
      transform: skew(-5deg);
    }
    
    .subheading {
      text-align: center;
      margin-bottom: 20px;
      font-size: 1.5rem;
      color: #fff;
      background-color: rgba(0, 0, 0, 0.7);
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
    
    /* 4 x 4 그리드 (보물상자 스타일) */
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
    
    .grid-item:before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: rgba(255,255,255,0.2);
      border-radius: 10px 10px 0 0;
    }
    
    .grid-item:hover {
      transform: translateY(-10px) scale(1.05);
      box-shadow: 0 15px 30px rgba(0,0,0,0.4);
    }
    
    .grid-item:active {
      transform: scale(0.95);
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
      color: #fff;
      text-shadow: 2px 2px 0 #000;
    }
    
    /* 원피스 캐릭터 색상 */
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
    
    /* 현재 domain 강조 */
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
    
    /* 체크박스 테이블 (두루마리 스타일) */
    .checklist-container {
      background: url('https://i.ibb.co/c6HQzFk/scroll.png') no-repeat center center;
      background-size: 100% 100%;
      padding: 50px 40px;
      position: relative;
    }
    
    .checklist-title {
      text-align: center;
      font-size: 1.8rem;
      margin-bottom: 20px;
      color: #8B4513;
      font-weight: 700;
      text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
    }
    
    .checklist-table {
      width: 100%;
      border-collapse: collapse;
      background: transparent;
    }
    
    .checklist-table tr td {
      padding: 15px;
      border-bottom: 1px dashed #8B4513;
      font-size: 1.1rem;
      color: #8B4513;
      font-weight: 500;
    }
    
    .checklist-table tr:last-child td {
      border-bottom: none;
    }
    
    /* 체크박스를 원피스 해적기 스타일로 */
    .checklist-table input[type="checkbox"] {
      appearance: none;
      -webkit-appearance: none;
      width: 25px;
      height: 25px;
      background-image: url('https://i.ibb.co/rkk44Lk/skull.png');
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      margin-right: 10px;
      vertical-align: middle;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      opacity: 0.7;
    }
    
    .checklist-table input[type="checkbox"]:checked {
      background-image: url('https://i.ibb.co/W0rwWmv/skull-checked.png');
      opacity: 1;
      transform: scale(1.2);
    }
    
    .checklist-table input[type="checkbox"]:hover {
      transform: scale(1.1);
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- 원피스 스타일 타이틀 -->
    <div class="header-wrapper">
      <h1 class="heading">
        Grand Line 수학 항해
      </h1>
      <p class="subheading">
        항해중인 영역: <?php echo $selected['name']; ?> | 항해사: <?php echo $username; ?>
      </p>
    </div>

    <!-- 1) 도메인 16개 Grid (보물상자 디자인) -->
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

    <!-- 2) 체크박스 진행 테이블 (두루마리 디자인) -->
    <div class="checklist-container">
      <h2 class="checklist-title">학습 로그북</h2>
      <table class="checklist-table">
        <?php echo $dmprinciples; ?>
      </table>
    </div>