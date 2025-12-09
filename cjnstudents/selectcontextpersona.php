<?php
// selectpersona.php
// 1) Moodle config 불러오기
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 2) PHP 에러 표시 설정
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;

// 추가: 사용자 역할 정보 가져오기
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;

// 3) URL 파라미터 받기
$cnttype = $_GET['cnttype'] ?? 0;
$cntid   = $_GET['cntid'] ?? 0;
$userid  = $_GET['userid'] ?? 0;

if ($cnttype == 1) {
    $getimg = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$cntid' "); // 전자책에서 가져오기
    $ctext = $getimg->pageicontent;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctext);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $extractedImages = array();
    $nimg = 0;
    foreach ($imageTags as $imageTag) {
        $nimg++;
        $imgSrc = $imageTag->getAttribute('src');
        $imgSrc = str_replace(' ', '%20', $imgSrc); 
        if (strpos($imgSrc, 'MATRIX') != false || strpos($imgSrc, 'MATH') != false || strpos($imgSrc, 'imgur') != false) break;
    } 
} else {
    $qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$cntid' ");
    $htmlDom = new DOMDocument; 
    @$htmlDom->loadHTML($qtext->questiontext);
    $imageTags = $htmlDom->getElementsByTagName('img'); 
    $extractedImages = array();
    foreach ($imageTags as $imageTag) {
        $imgSrc = $imageTag->getAttribute('src');
        $imgSrc = str_replace(' ', '%20', $imgSrc); 
        if (strpos($imgSrc, 'MATRIX/MATH') != false || strpos($imgSrc, 'HintIMG') != false) break;
    }  
}

// 4) DB에서 prsn_contents 테이블 조회
$sql = "SELECT *
          FROM {prsn_contents}
         WHERE contentstype = :ctype
           AND contentsid   = :cid
      ORDER BY npersona";
$rows = $DB->get_records_sql($sql, [
    'ctype' => $cnttype,
    'cid'   => $cntid
]);

// 5) 만약 페르소나 정보가 없으면 IFRAME으로 정보 입력 페이지 표시
if (empty($rows)) {
  echo '
  <div class="no-data-container">   
    <iframe   
        src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/inputpersonainfo.php?cnttype=' . $cnttype . '&type=contents&cntid=' . $cntid . '" 
        class="persona-iframe" style="overflow: hidden;">
    </iframe>
  </div>
  <style>
    .no-data-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 2rem auto;
      padding: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      background: #fafafa;
    } 
    .persona-iframe {
      width: 80%;
      height: 80vh;
      border: none;
      margin: 1rem auto;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
  </style>
  ';
  exit;
}

// 6) 각 콘텐츠 행에 대해 해당 사용자의 최신 strength와 status 값을 가져와 추가
foreach ($rows as $row) {
    $usermap = $DB->get_record_sql(
        "SELECT strength, status FROM {prsn_usermap} 
         WHERE userid = :userid AND prsnid = :prsnid 
         ORDER BY id DESC LIMIT 1",
        [
            'userid' => $userid,
            'prsnid' => $row->id
        ]
    );
    if ($usermap) {
        $row->strength = $usermap->strength;
        $row->status = $usermap->status; // 사용자 저장값으로 덮어씀
    }
}

// 7) DB 결과를 배열로 변환
$rowsArray = array_values($rows);

// 8) 첫 번째 레코드에서 type 값 추출 (수정하기 링크에 사용)
$firstRow = reset($rows);
$type     = $firstRow->type ?? '';

// 9) abessi_messages 테이블에서 wboardid 조회 (노트보기 링크 등에 사용)
$thisboard = $DB->get_record_sql(
    "SELECT * 
       FROM mdl_abessi_messages
      WHERE contentstype = :ctype
        AND contentsid   = :cid
        AND userid       = :userid
        AND status NOT LIKE 'extendmemory'
   ORDER BY id DESC
      LIMIT 1",
    [
        'ctype'  => $cnttype,
        'cid'    => $cntid,
        'userid' => $userid
    ]
);
$wboardid = $thisboard->wboardid ?? '';

// 10) HTML 출력
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>React + PHP Example (Font Awesome & 3D Flip)</title>
  
  <!-- React, ReactDOM, Babel, Tailwind CSS -->
  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome CDN (예: 6.x) -->
  <link 
    rel="stylesheet" 
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" 
    integrity="sha512-..." 
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />

  <style>
    body {
      margin: 0;
      padding: 0;
      background: #111;
    }
    /* 카드 3D 뒤집기 핵심 스타일 */
    .flip-container {
      perspective: 1000px; /* 3D 공간 */
    }
    .flip-card {
      transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      transform-style: preserve-3d;
      position: relative;
    }
    /* 클릭 시 flip 효과 적용 */
    .flip-card.flipped {
      transform: rotateY(180deg);
    }
    .flip-card-front,
    .flip-card-back {
      position: absolute;
      width: 100%;
      height: 100%;
      backface-visibility: hidden; /* 뒷면 숨김 */
      top: 0;
      left: 0;
      border-radius: 0.5rem;
    }
    .flip-card-back {
      transform: rotateY(180deg);
    }
    /* 툴팁 스타일 */
    .tooltip3:hover .tooltiptext1 {
      visibility: visible;
    }
    a:hover { color: green; text-decoration: underline; }
    
    .tooltip3 {
      position: relative;
      display: inline;
      border-bottom: 0px solid black;
      font-size: 14px;
    }
    
    .tooltip3 .tooltiptext3 {
      visibility: hidden;
      width: 40%;
      background-color: #ffffff;
      color: #e1e2e6;
      text-align: center;
      font-size: 14px;
      border-radius: 10px;
      border-style: solid;
      border-color: #0aa1bf;
      padding: 20px 1;
      /* Position the tooltip */
      top:50;
      left:5%;
      position: fixed;
      z-index: 1;
    } 
    .tooltip3 img {
      max-width: 600px;
      max-height: 1200px;
    }
    .tooltip3:hover .tooltiptext3 {
      visibility: visible;
    }
  </style>
</head>
<body>

<!-- 상단 우측 버튼들 -->
<div class="fixed top-4 right-4 flex items-center space-x-4 z-50">
  <!-- 노트보기 -->
  <div class="tooltip3">
    <a 
      href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid=<?php echo $userid; ?>&mode=2&wboardid=<?php echo $wboardid; ?>"
      target="_blank"
      rel="noopener noreferrer"
      class="inline-flex items-center px-3 py-2 rounded bg-gray-800 text-white hover:bg-gray-700"
    >
      📝<span class="ml-2">노트보기</span>
    </a>
    <span class="tooltiptext3">
      <table align="center">
        <tr>
          <td><img loading="lazy" src="<?php echo $imgSrc; ?>" width="600"></td>
        </tr>
      </table>
    </span>
  </div>
  <!-- 수정하기 & 초기화 버튼 (학생이 아닌 경우에만 표시) -->
  <?php if ($role !== 'student') { ?>
    <a 
      href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/editpersona.php?cnttype=<?php echo $cnttype; ?>&type=<?php echo $type; ?>&cntid=<?php echo $cntid; ?>&userid=<?php echo $userid; ?>"
      target="_blank"
      rel="noopener noreferrer"
      class="inline-flex items-center px-3 py-2 rounded bg-green-700 text-white hover:bg-green-600"
    >
      수정하기
    </a> 
    <a href="#" id="reset-button" class="inline-flex items-center px-3 py-2 rounded bg-red-700 text-white hover:bg-red-600">
      초기화
    </a>
  <?php } ?>
</div>

<div id="root"></div>

<!-- PHP에서 받아온 데이터 JS 변수 할당 -->
<script type="text/javascript">
  var serverData = <?php echo json_encode($rowsArray, JSON_UNESCAPED_UNICODE); ?>;
  var currentUserId = <?php echo json_encode($userid); ?>;
  var currentCntType = <?php echo json_encode($cnttype); ?>;
  var currentCntId = <?php echo json_encode($cntid); ?>;
  // 서버 저장용 업데이트 URL
  var updateUrl = "https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/update_userpersona.php";
</script>

<!-- React 코드 (Babel 컴파일) -->
<script type="text/babel">
  const { useState, useEffect } = React;

  // Tailwind용 Card/CardContent 단순 컴포넌트
  function Card({ children, className, style }) {
    return (
      <div className={"rounded shadow-lg overflow-hidden " + (className || "")} style={style}>
        {children}
      </div>
    );
  }
  function CardContent({ children, className, style }) {
    return (
      <div className={"p-4 " + (className || "")} style={style}>
        {children}
      </div>
    );
  }

  function PersonaInterface() {
    const [personas, setPersonas] = useState([]);

    // 컴포넌트 마운트 시 DB 데이터 로딩 및 isFlipped 상태 추가
    useEffect(() => {
      const data = serverData || [];
      // Font Awesome 아이콘 클래스 배열 (npersona == 1~6에 매핑)
      const iconMap = [
        "fa-solid fa-moon",
        "fa-solid fa-brain",
        "fa-solid fa-sun",
        "fa-solid fa-fan",
        "fa-solid fa-clock",
        "fa-solid fa-eye"
      ];

      const newPersonas = data.map(row => {
        const iconIndex = (row.npersona - 1);
        const iconClass = iconMap[iconIndex] || "fa-solid fa-star";
        return {
          id: row.id,
          npersona: row.npersona,
          iconClass,
          isFlipped: false, // 플립 상태 초기값
          // 상태: 0 (Negative), 1 (Positive), 2 (Enepoem)
          status: row.status !== undefined ? parseInt(row.status, 10) : 0,
          negative: {
            title: row.negative_prsnname || "부정적 페르소나",
            subtitle: "",
            description: row.negative_persona || "부정적 성격 설명"
          },
          positive: {
            title: row.positive_prsnname || "긍정적 페르소나",
            subtitle: "",
            description: row.positive_persona || "긍정적 성격 설명"
          },
          enepoem: row.enepoem || "enthusiast without effort"
        };
      });

      // 두번째 카드(인덱스 1)와 세번째 카드(인덱스 2)의 순서를 스왑
      if (newPersonas.length >= 3) {
        [newPersonas[1], newPersonas[2]] = [newPersonas[2], newPersonas[1]];
      }

      setPersonas(newPersonas);
    }, []);

    // 상태 변경(클릭 시) 및 DB 저장: Negative (0) → Enepoem (2) → Positive (1) → Negative (0)
    const toggleCard = (prsnid, npersona, currentStatus) => {
      // 1) 카드 flip 애니메이션을 위해 flip 상태 활성화
      setPersonas(prev =>
        prev.map(p => p.id === prsnid ? { ...p, isFlipped: true } : p)
      );
      
      // 2) 새로운 상태 계산: Negative(0) → Enepoem(2) → Positive(1) → Negative(0)
      let newStatus;
      if (currentStatus === 0) {
          newStatus = 2;
      } else if (currentStatus === 2) {
          newStatus = 1;
      } else if (currentStatus === 1) {
          newStatus = 0;
      }
      
      let strength;
      if (newStatus === 0) strength = 2.5;
      else if (newStatus === 1) strength = 7.5;
      else if (newStatus === 2) strength = 5.0;
      
      // 3) 애니메이션 지속 시간(800ms) 후 DB 업데이트 및 상태 변경
      setTimeout(() => {
        // DB 업데이트 (fetch 요청)
        const formData = new URLSearchParams();
        formData.append('userid', currentUserId);
        formData.append('type', 'defaultcontents');
        formData.append('prsnid', prsnid);
        formData.append('npersona', npersona);
        formData.append('status', newStatus);
        formData.append('strength', strength);

        fetch(updateUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formData.toString()
        })
          .then(res => res.json())
          .then(data => {
            if (!data.success) {
              console.error("DB 저장 실패:", data.message);
            }
          })
          .catch(err => console.error("에러 발생:", err));

        // 로컬 상태 업데이트: 새로운 상태 저장 및 flip 효과 해제
        setPersonas(prev =>
          prev.map(p =>
            p.id === prsnid ? { ...p, status: newStatus, isFlipped: false } : p
          )
        );
      }, 800); // CSS 전환 시간과 동일하게 설정
    };

    // 전체 진행률 계산 (각 status 합산 대비 최대값 2 * 카드 수)
    const totalCards = personas.length;
    const sumStatus = personas.reduce((acc, p) => acc + p.status, 0);
    const progressPercent = totalCards > 0 ? Math.round((sumStatus / (2 * totalCards)) * 100) : 0;

    return (
      <div className="min-h-[80vh] bg-gradient-to-b from-gray-900 via-indigo-900 to-purple-900 p-2">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <span className="inline-block w-6 h-6 mb-2 text-yellow-300">★</span>
            <h1 className="text-2xl text-white font-serif mb-2">
              당신의 현재 모습을 선택하고 성장 가능성을 발견하세요
            </h1>
          </div>

          {/* 카드 목록 */}
          <div className="grid grid-cols-3 gap-6">
            {personas.map(p => {
              const { id, npersona, status, iconClass } = p;
              return (
                <div key={id} className="flip-container cursor-pointer" onClick={() => toggleCard(id, npersona, status)}>
                  <div className={`flip-card w-full h-64 ${p.isFlipped ? 'flipped' : ''}`}>
                    
                    {/* Negative 상태 (status === 0) */}
                    {status === 0 && (
                      <div className="flip-card-front bg-gradient-to-br from-red-900/80 to-purple-900/80 text-white flex flex-col p-6">
                        <div className="flex justify-center mb-6 text-4xl">
                          <i className={`${iconClass} text-red-300/80`}></i>
                        </div>
                        <div className="flex-grow flex flex-col justify-center text-center">
                          <h3 className="text-xl font-serif mb-2">{p.negative.title}</h3>
                          <div className="text-red-300/80 text-sm mb-4">{p.negative.subtitle}</div>
                          <p className="text-sm text-gray-300">{p.negative.description}</p>
                        </div>
                      </div>
                    )}

                    {/* Positive 상태 (status === 1) */}
                    {status === 1 && (
                      <div className="flip-card-front bg-gradient-to-br from-indigo-900/80 to-purple-900/80 text-white flex flex-col p-6">
                        <div className="flex justify-center mb-6 text-4xl">
                          <i className={`${iconClass} text-yellow-300/80`}></i>
                        </div>
                        <div className="flex-grow flex flex-col justify-center text-center">
                          <h3 className="text-xl font-serif mb-2">{p.positive.title}</h3>
                          <div className="text-yellow-300/80 text-sm mb-4">{p.positive.subtitle}</div>
                          <p className="text-sm text-gray-300">{p.positive.description}</p>
                        </div>
                      </div>
                    )}

                    {/* Enepoem 상태 (status === 2) */}
                    {status === 2 && (
                      <div className="flip-card-front bg-gradient-to-br from-yellow-600/80 to-orange-600/80 text-white flex flex-col p-6">
                        <div className="flex justify-center mb-6 text-4xl">
                          <i className={`${iconClass} text-yellow-300/80`}></i>
                        </div>
                        <div className="flex-grow flex flex-col justify-center text-center">
                          <h3 className="text-xl font-serif mb-2">🧭 나침반</h3>
                          <p className="text-sm text-gray-100">{p.enepoem}</p>
                        </div>
                      </div>
                    )}

                    {/* 카드 뒤면 */}
                    <div className="flip-card-back bg-gray-800/80 flex items-center justify-center text-white p-6">
                      <div className="text-center">
                        <h2 className="text-lg font-bold mb-2">이 카드를 클릭하면 상태가 순환됩니다.</h2>
                        <p className="text-sm text-gray-100">
                          부정 페르소나 → 나침반 시 → 긍정 페르소나 → 초기화
                        </p>
                      </div>
                    </div>



                        

                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* 진행률 표시 (하단) */}
        <div className="fixed bottom-0 left-0 right-0 bg-gray-900/95 backdrop-blur p-5">
          <div className="max-w-4xl mx-auto">
            <div className="h-1 bg-gray-700/50 rounded-full overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full"
                style={{ width: `${progressPercent}%` }}
              />
            </div>
            <div className="mt-2 text-xs text-center text-gray-400">
              페르소나 싱크율 {progressPercent}%
            </div>
          </div>
        </div>
      </div>
    );
  }

  // ReactDOM으로 렌더링
  const container = document.getElementById('root');
  const root = ReactDOM.createRoot(container);
  root.render(<PersonaInterface />);
</script>

<!-- 초기화 버튼 이벤트 처리 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const resetButton = document.getElementById('reset-button');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('정말로 초기화하시겠습니까? 해당 페르소나 정보가 삭제됩니다.')) {
                fetch('reset_persona.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'cnttype=' + encodeURIComponent(currentCntType) + '&cntid=' + encodeURIComponent(currentCntId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('초기화가 완료되었습니다.');
                        location.reload();
                    } else {
                        alert('초기화 실패: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('에러:', error);
                    alert('에러가 발생했습니다.');
                });
            }
        });
    }
});
</script>

</body>
</html>
