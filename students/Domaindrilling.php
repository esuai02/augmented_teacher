<?php
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

// 파라미터 처리
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

// role, username 정보 (필요하다면)
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

// 유효 도메인 목록 (16개: 120~133, 135~136)
$validDomains = [120,121,122,123,124,125,126,127,128,129,130,131,132,133,135,136];

// domain=134 이거나 validDomains에 없는 경우 -> 종료
if ($domain == 134 || !in_array($domain, $validDomains)) {
  die('Invalid domain (134 or out of range).');
}

// React의 categories와 같은 구조를 PHP 배열로 옮김 (아이콘 이름은 예시 텍스트)
$categories = [
  [ 'name'=>'수체계',       'icon'=>'Hash',       'color'=>'blue'   ],
  [ 'name'=>'지수와 로그',  'icon'=>'Binary',     'color'=>'green'  ],
  [ 'name'=>'수열',        'icon'=>'ArrowUpDown','color'=>'purple' ],
  [ 'name'=>'식의 계산',    'icon'=>'Plus',       'color'=>'red'    ],
  [ 'name'=>'집합과 명제',  'icon'=>'GitBranch',  'color'=>'yellow' ],
  [ 'name'=>'방정식',      'icon'=>'Equal',      'color'=>'pink'   ],
  [ 'name'=>'부등식',      'icon'=>'MinusCircle','color'=>'indigo' ],
  [ 'name'=>'함수',        'icon'=>'TrendingUp', 'color'=>'cyan'   ],
  [ 'name'=>'미분',        'icon'=>'Calculator', 'color'=>'emerald'],
  [ 'name'=>'적분',        'icon'=>'Sigma',      'color'=>'orange' ],
  [ 'name'=>'평면도형',     'icon'=>'Circle',     'color'=>'violet' ],
  [ 'name'=>'평면좌표',     'icon'=>'Target',     'color'=>'rose'   ],
  [ 'name'=>'입체도형',     'icon'=>'Box',        'color'=>'blue'   ],
  [ 'name'=>'공간좌표',     'icon'=>'Grid',       'color'=>'teal'   ],
  [ 'name'=>'경우의 수와 확률','icon'=>'Percent', 'color'=>'amber'  ],
  [ 'name'=>'통계',        'icon'=>'BarChart2',  'color'=>'lime'   ],
];

// categories 배열은 인덱스 0~15
// domain=120 -> index=0, domain=121 -> index=1, ... , domain=133 -> index=13,
// domain=135 -> index=14, domain=136 -> index=15

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
  // fallback
  return -1;
}

$idx = domainToIndex($domain);
if ($idx < 0 || $idx > 15) {
  die('Invalid mapping index.');
}

// 이제 매칭된 카테고리 항목
$selected = $categories[$idx];

/**
 * UI: 
 * - 전체 4x4 그리드를 모두 보여주되, 클릭 시 `chapterdrilling.php?domain=...&studentid=...` 로 이동
 * - 또는 "현재 domain"에 해당하는 칸을 강조할 수도 있음(옵션).
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>도메인 매핑 그리드 UI (PHP/HTML/CSS)</title>
  <style>
    /* 간단한 reset */
    * { box-sizing: border-box; margin:0; padding:0; }

    body {
      font-family: sans-serif;
      background-color: #f3f4f6;
      color: #333;
    }
    .container {
      max-width: 1024px;
      margin: 0 auto;
      padding: 20px;
    }
    .heading {
      text-align: center;
      margin-bottom: 20px;
    }
    /* 4 x 4 그리드 */
    .grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      grid-gap: 20px;
    }
    .grid-item {
      position: relative;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .grid-item:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .grid-item:active {
      transform: scale(0.95);
    }
    .icon {
      display: block;
      font-size: 2rem;
      margin-bottom: 10px;
    }
    .title {
      font-size: 0.95rem;
      font-weight: 500;
    }
    /* 색상 예시 (원색) => icon 색에 사용 */
    .blue   { color: #1d4ed8; }
    .green  { color: #059669; }
    .purple { color: #7c3aed; }
    .red    { color: #dc2626; }
    .yellow { color: #ca8a04; }
    .pink   { color: #db2777; }
    .indigo { color: #6366f1; }
    .cyan   { color: #06b6d4; }
    .emerald{ color: #10b981; }
    .orange { color: #ea580c; }
    .violet { color: #8b5cf6; }
    .rose   { color: #f43f5e; }
    .teal   { color: #0d9488; }
    .amber  { color: #d97706; }
    .lime   { color: #65a30d; }
    /* 'blue' 재사용 -> 다른 음영이나 icon class를 달리 써도 됨 */

    /* 강조 스타일 (현재 domain) */
    .highlight {
      box-shadow: 0 0 0 2px #3b82f6;
      transform: scale(1.08);
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="heading"><?php echo "도메인: $domain / $selected[name]"; ?></h1>
    <p class="heading">
      사용자: <?php echo $username; ?> (role: <?php echo $role; ?>)
    </p>

    <div class="grid">

      <?php
      // 16개 모두 출력하되, domain=134는 건너뛰는 로직
      // 하지만 134는 $validDomains에 없으므로 이 loop에는 포함되지 않음
      foreach ($validDomains as $dom) {
        $i = domainToIndex($dom);
        if ($i < 0) continue; // 안전빵

        $cat = $categories[$i];
        // 현재 domain이면 .highlight 추가
        $highlightClass = ($dom == $domain) ? 'highlight' : '';

        // onClick 시 이동할 링크
        $jumpUrl = "chapterdrilling.php?domain={$dom}&studentid={$studentid}";

        // 아이콘 출력(예시) - 실제 SVG 아이콘 대신 텍스트로 대체
        // 필요하다면 <svg> 등으로 만들어도 됨
        echo "
        <div class='grid-item $highlightClass' onclick=\"location.href='$jumpUrl'\">
          <div class='icon {$cat['color']}'>
            [{$cat['icon']}] 
          </div>
          <div class='title'>
            {$cat['name']}
          </div>
        </div>
        ";
      }
      ?>
    </div>
  </div>
</body>
</html>
