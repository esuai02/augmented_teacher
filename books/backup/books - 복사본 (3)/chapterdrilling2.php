<?php
/**
 * 체크 해제 시 '현재 챕터' 상태로 되돌리는 버전
 * - 중간 챕터를 해제해도 뒤쪽 챕터는 잠기지 않고 남음
 * - 해제된 챕터는 다시 학습 가능 상태로 표시
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$cid       = $_GET["cid"];
$chnum     = $_GET["nch"];
$mode      = $_GET["mode"];
$domain    = $_GET["domain"];
$studentid = $_GET["studentid"];
$timecreated = time();
$checkitem = 'd'.$domain.'cid'.$cid.'ch'.$chnum;

if(!$studentid){
    $studentid = $USER->id;
}

// 사용자 역할, 이름
$userrole = $DB->get_record_sql("
    SELECT data AS role 
      FROM {user_info_data}
     WHERE userid = :userid
       AND fieldid = 22
", ['userid' => $USER->id]);
$role = $userrole->role;

$userinfo = $DB->get_record_sql("
    SELECT lastname, firstname
      FROM {user}
     WHERE id = :studentid
", ['studentid' => $studentid]);
$username = $userinfo->firstname . $userinfo->lastname;

// domain 정보
$chlist = $DB->get_record_sql("
    SELECT *
      FROM {abessi_domain}
     WHERE domain = :domain
", ['domain' => $domain]);

if(!$chlist){
    die('Invalid domain.');
}

$domaintitle = $chlist->title;
$chapnum     = $chlist->chnum;

// 챕터 정보 배열
$chapters = [];
for ($nch = 1; $nch <= $chapnum; $nch++) {
    $cidstr   = 'cid'.$nch; 
    $chstr    = 'nch'.$nch;
    $cid2     = $chlist->$cidstr;
    $nchapter = $chlist->$chstr;

    $curri = $DB->get_record_sql("
        SELECT *
          FROM {abessi_curriculum}
         WHERE id = :id
    ", ['id' => $cid2]);
    if(!$curri) continue;

    $chname = 'ch' . $nchapter;
    $title  = $curri->$chname;

    // 열기/노트 링크
    $chapterUrl = "https://mathking.kr/moodle/local/augmented_teacher/books/chapter_topic.php"
                . "?cid={$cid2}&nch={$nchapter}&studentid={$studentid}";
    $noteUrl    = "https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php"
                . "?cid={$cid2}&nch={$nchapter}&studentid={$studentid}&mode=fix&domain={$domain}";

    $chapters[] = [
        'id'      => $nch,
        'title'   => $title,
        'url'     => $chapterUrl,
        'noteUrl' => $noteUrl
    ];
}

// One Piece 주제 값 획득
function getDomainTheme($domain) {
    $themes = [
        120 => ['name' => '수체계', 'island' => 'East Blue', 'character' => 'Luffy'],
        121 => ['name' => '지수와 로그', 'island' => 'Loguetown', 'character' => 'Zoro'],
        122 => ['name' => '수열', 'island' => 'Whiskey Peak', 'character' => 'Nami'],
        123 => ['name' => '식의 계산', 'island' => 'Little Garden', 'character' => 'Usopp'],
        124 => ['name' => '집합과 명제', 'island' => 'Drum Island', 'character' => 'Sanji'],
        125 => ['name' => '방정식', 'island' => 'Alabasta', 'character' => 'Chopper'],
        126 => ['name' => '부등식', 'island' => 'Jaya', 'character' => 'Robin'],
        127 => ['name' => '함수', 'island' => 'Skypiea', 'character' => 'Franky'],
        128 => ['name' => '미분', 'island' => 'Water 7', 'character' => 'Brook'],
        129 => ['name' => '적분', 'island' => 'Enies Lobby', 'character' => 'Jinbe'],
        130 => ['name' => '평면도형', 'island' => 'Thriller Bark', 'character' => 'Shanks'],
        131 => ['name' => '평면좌표', 'island' => 'Sabaody', 'character' => 'Law'],
        132 => ['name' => '입체도형', 'island' => 'Amazon Lily', 'character' => 'Ace'],
        133 => ['name' => '공간좌표', 'island' => 'Impel Down', 'character' => 'Sabo'],
        135 => ['name' => '경우의 수와 확률', 'island' => 'Marineford', 'character' => 'Hancock'],
        136 => ['name' => '통계', 'island' => 'Fish-Man Island', 'character' => 'Mihawk']
    ];
    
    return isset($themes[$domain]) ? $themes[$domain] : ['name' => '미지의 영역', 'island' => 'Unknown', 'character' => 'Mystery'];
}

$themeInfo = getDomainTheme($domain);

// JSON 변환
$chapters_json = json_encode($chapters, JSON_UNESCAPED_UNICODE);
$theme_json = json_encode($themeInfo, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title><?php echo $themeInfo['island']; ?> 탐험</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
  <style>
    /* 기본 리셋 및 전역 스타일 */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #002147;
      color: #fff;
      margin: 0;
      padding: 0;
      background-image: url('https://i.ibb.co/BG2ghQz/op-bg.jpg');
      background-size: cover;
      background-attachment: fixed;
      background-position: center;
      position: relative;
    }
    
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 33, 71, 0.7);
      z-index: -1;
    }
    
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    
    /* 원피스 스타일 헤더 */
    .island-header {
      text-align: center;
      margin-bottom: 40px;
      position: relative;
    }
    
    .island-name {
      font-size: 2.5rem;
      color: #ffd700;
      text-transform: uppercase;
      letter-spacing: 2px;
      font-weight: 700;
      text-shadow: 3px 3px 0 #c00d0d, 6px 6px 0 #000000;
      transform: skew(-5deg);
      margin-bottom: 10px;
    }
    
    .island-subtitle {
      font-size: 1.2rem;
      color: #fff;
      margin-bottom: 10px;
      background-color: rgba(0, 0, 0, 0.6);
      display: inline-block;
      padding: 5px 15px;
      border-radius: 20px;
      border: 2px solid #ffd700;
    }
    
    .island-map {
      position: relative;
      background: url('https://i.ibb.co/1qmV7zZ/parchment.jpg') no-repeat center center;
      background-size: cover;
      max-width: 700px;
      margin: 0 auto;
      padding: 40px;
      border-radius: 10px;
      border: 5px solid #8B4513;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
    }
    
    .map-title {
      text-align: center;
      color: #8B4513;
      font-size: 1.8rem;
      margin-bottom: 20px;
      font-weight: bold;
      text-shadow: 1px 1px 1px rgba(139, 69, 19, 0.3);
      font-family: 'Courier New', monospace;
      text-transform: uppercase;
      letter-spacing: 3px;
    }
    
    /* 목적지 (챕터) 카드 */
    .location-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
      position: relative;
    }
    
    /* 항해 경로 라인 */
    .location-container::before {
      content: "";
      position: absolute;
      top: 0;
      left: 25px;
      width: 5px;
      height: 100%;
      background: #8B4513;
      border-radius: 5px;
      z-index: 0;
    }
    
    .location-card {
      position: relative;
      background: rgba(255, 245, 225, 0.9);
      border: 3px solid #8B4513;
      border-radius: 10px;
      padding: 15px;
      margin-left: 45px;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 1;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .location-card::before {
      content: "";
      position: absolute;
      left: -45px;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      background: #8B4513;
      border-radius: 50%;
      background-position: center;
      background-repeat: no-repeat;
      background-size: 24px;
      transition: all 0.3s ease;
    }
    
    .location-card.locked {
      opacity: 0.7;
      filter: grayscale(80%);
      cursor: not-allowed;
    }
    
    .location-card.locked::before {
      background-image: url('https://i.ibb.co/T8qk7bt/lock.png');
      background-color: #555;
    }
    
    .location-card.current {
      border-color: #ffd700;
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
    }
    
    .location-card.current::before {
      background-image: url('https://i.ibb.co/26D5QQR/compass.png');
      background-color: #1d4ed8;
    }
    
    .location-card.completed {
      background: rgba(255, 245, 225, 0.95);
    }
    
    .location-card.completed::before {
      background-image: url('https://i.ibb.co/cNjyZXK/treasure.png');
      background-color: #16a34a;
    }
    
    .location-card:hover:not(.locked) {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .location-info {
      display: flex;
      align-items: center;
      gap: 15px;
      color: #8B4513;
    }
    
    .location-icon {
      font-size: 1.5rem;
      min-width: 30px;
      text-align: center;
    }
    
    .location-name {
      font-weight: bold;
      font-size: 1.1rem;
    }
    
    .location-actions {
      display: flex;
      gap: 10px;
    }
    
    .action-btn {
      background: #8B4513;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-block;
    }
    
    .action-btn:hover {
      background: #a0522d;
      transform: scale(1.05);
    }
    
    .location-card.locked .action-btn {
      opacity: 0.5;
      pointer-events: none;
    }
    
    /* 완료 상태 표시 */
    .footer-treasure {
      text-align: center;
      margin-top: 30px;
      background: rgba(139, 69, 19, 0.2);
      padding: 15px;
      border-radius: 10px;
      border: 2px dashed #8B4513;
    }
    
    .footer-treasure p {
      color: #8B4513;
      font-weight: bold;
      font-size: 1.1rem;
    }
    
    .all-complete {
      color: #ffd700 !important;
      text-shadow: 1px 1px 2px #000;
      animation: treasure-glow 1.5s infinite alternate;
    }
    
    @keyframes treasure-glow {
      from {
        text-shadow: 0 0 5px #ffd700, 0 0 10px #ffd700;
      }
      to {
        text-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700, 0 0 30px #ffd700;
      }
    }
    
    /* 돌아가기 버튼 */
    .back-to-map {
      display: inline-block;
      margin-top: 20px;
      background: #c00d0d;
      color: #fff;
      padding: 10px 20px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: bold;
      transition: all 0.3s ease;
      border: 2px solid #ffd700;
      text-align: center;
    }
    
    .back-to-map:hover {
      background: #8B0000;
      transform: scale(1.05);
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
    }
    
    /* 애니메이션 효과 */
    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
      100% { transform: translateY(0px); }
    }
    
    .floating {
      animation: float 3s ease-in-out infinite;
    }
    
    /* 장식 요소 */
    .decoration {
      position: absolute;
      opacity: 0.6;
      z-index: -1;
    }
    
    .decoration.bird {
      top: 50px;
      right: 30px;
      font-size: 2rem;
      animation: float 4s ease-in-out infinite;
    }
    
    .decoration.ship {
      bottom: 30px;
      left: 40px;
      font-size: 2.5rem;
      animation: float 6s ease-in-out infinite;
    }
    
    .decoration.fish {
      bottom: 80px;
      right: 60px;
      font-size: 1.8rem;
      animation: float 5s ease-in-out infinite;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- 헤더 섹션 -->
    <div class="island-header">
      <h1 class="island-name"><?php echo $themeInfo['island']; ?></h1>
      <p class="island-subtitle"><?php echo $themeInfo['name']; ?> 탐험 | 항해사: <?php echo $username; ?></p>
    </div>
    
    <!-- 탐험 지도 -->
    <div class="island-map">
      <h2 class="map-title"><?php echo $themeInfo['character']; ?>의 탐험 일지</h2>
      <div id="locationList" class="location-container">
        <!-- 자바스크립트로 동적 생성 -->
      </div>
      
      <!-- 진행도 표시 -->
      <div id="footer" class="footer-treasure"></div>
    </div>
    
    <!-- 돌아가기 버튼 -->
    <div style="text-align: center; margin-top: 20px;">
      <a href="chapterdrilling.php?studentid=<?php echo $studentid; ?>" class="back-to-map">그랜드 라인 항해도로 돌아가기</a>
    </div>
    
    <!-- 장식 요소 -->
    <div class="decoration bird">🐦</div>
    <div class="decoration ship">⛵</div>
    <div class="decoration fish">🐟</div>
  </div>

  <script>
    // 데이터 로드
    var chaptersData = JSON.parse('<?php echo addslashes($chapters_json); ?>');
    var themeData = JSON.parse('<?php echo addslashes($theme_json); ?>');
    
    // 각 챕터에 맞는 아이콘 설정
    var locationIcons = {
      'Luffy': '⚓',
      'Zoro': '⚔️',
      'Nami': '🌩️',
      'Usopp': '🔫',
      'Sanji': '🔥',
      'Chopper': '🦌',
      'Robin': '👐',
      'Franky': '🤖',
      'Brook': '🎸',
      'Jinbe': '🌊',
      'Shanks': '🏴‍☠️',
      'Law': '⚕️',
      'Ace': '🔥',
      'Sabo': '🔥',
      'Hancock': '💘',
      'Mihawk': '🗡️',
      'Mystery': '❓'
    };
    
    // 상태 관리
    var completedChapters = new Set(); 
    var currentChapter = 1;
    
    document.addEventListener("DOMContentLoaded", function() {
      renderLocations();
      updateFooter();
    });

    // 1) 상태 판별
    function getChapterStatus(chapterId) {
      if (completedChapters.has(chapterId)) {
        return "completed";
      }
      if (chapterId === currentChapter) {
        return "current";
      }
      return "locked";
    }

    // 2) 클릭 로직
    function handleChapterClick(chapterId) {
      if (chapterId > currentChapter) {
        // 현재 챕터보다 큰 건 잠겨 있으므로 아무 동작 안 함
        return;
      }

      // 이미 완료된 챕터 -> 체크 해제
      if (completedChapters.has(chapterId)) {
        completedChapters.delete(chapterId);
      } 
      else {
        // 아직 미완료 -> 체크
        completedChapters.add(chapterId);
      }

      // 체크/해제 후 currentChapter 재계산
      recalcCurrentChapter();
      
      // 화면 갱신
      renderLocations();
      updateFooter();
    }

    // 3) currentChapter 재계산
    function recalcCurrentChapter() {
      var maxConsecutive = 0;
      for (var i = 1; i <= chaptersData.length; i++) {
        if (completedChapters.has(i)) {
          maxConsecutive = i;
        } else {
          break; 
        }
      }
      currentChapter = Math.max(currentChapter, maxConsecutive + 1);
    }

    // 4) 렌더
    function renderLocations() {
      var container = document.getElementById("locationList");
      container.innerHTML = "";

      chaptersData.forEach(function(chapter) {
        var status = getChapterStatus(chapter.id);
        var locationIcon = locationIcons[themeData.character] || '🏝️';

        var locationCard = document.createElement("div");
        locationCard.className = "location-card " + status;

        // 왼쪽 정보 (아이콘 + 이름)
        var locationInfo = document.createElement("div");
        locationInfo.className = "location-info";

        var iconSpan = document.createElement("span");
        iconSpan.className = "location-icon";
        iconSpan.textContent = locationIcon;

        var nameSpan = document.createElement("span");
        nameSpan.className = "location-name";
        nameSpan.textContent = chapter.title;

        locationInfo.appendChild(iconSpan);
        locationInfo.appendChild(nameSpan);

        // 오른쪽 버튼들
        var locationActions = document.createElement("div");
        locationActions.className = "location-actions";

        var openBtn = document.createElement("a");
        openBtn.href = chapter.url;
        openBtn.className = "action-btn";
        openBtn.textContent = "탐험";
        openBtn.target = "_blank";

        var noteBtn = document.createElement("a");
        noteBtn.href = chapter.noteUrl;
        noteBtn.className = "action-btn";
        noteBtn.textContent = "기록";
        noteBtn.target = "_blank";

        locationActions.appendChild(openBtn);
        locationActions.appendChild(noteBtn);

        // 카드에 요소 추가
        locationCard.appendChild(locationInfo);
        locationCard.appendChild(locationActions);

        // 카드 클릭 처리 (체크/해제)
        locationCard.addEventListener("click", function(e) {
          if (e.target !== openBtn && e.target !== noteBtn) {
            handleChapterClick(chapter.id);
          }
        });

        // 잠김 상태면 링크 비활성화
        if (status === "locked") {
          openBtn.onclick = function(e){ e.preventDefault(); };
          noteBtn.onclick = function(e){ e.preventDefault(); };
        }

        container.appendChild(locationCard);
      });
    }

    // 5) 진행도 표시
    function updateFooter() {
      var footer = document.getElementById("footer");
      var total = chaptersData.length;
      var done = completedChapters.size;

      if (total > 0 && done === total) {
        footer.innerHTML = '<p class="all-complete">축하합니다! 이 섬의 모든 보물을 찾았습니다! 🎉</p>';
      } else {
        var progressText = "발견한 보물: " + done + " / " + total;
        footer.innerHTML = '<p>' + progressText + '</p>';
      }
    }
  </script>
</body>
</html>