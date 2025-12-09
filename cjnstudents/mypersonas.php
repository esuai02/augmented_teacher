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

// 3) 사용자 ID 설정 (GET에 userid가 없으면 현재 로그인 사용자 사용)
$userid = isset($_GET['userid']) ? $_GET['userid'] : $USER->id;

// 4) 최근 1주일(7일) 전의 unixtime 계산
$time_threshold = time() - (7 * 24 * 3600);

// 5) mdl_prsn_usermap 테이블에서 사용자 아이디와 최근 1주일 내의 데이터(prsnid) 조회
$sql_usermap = "SELECT prsnid FROM {prsn_usermap} WHERE userid = :userid AND timemodified >= :time_threshold";
$rows_usermap = $DB->get_records_sql($sql_usermap, [
    'userid'         => $userid,
    'time_threshold' => $time_threshold
]);

// 만약 데이터가 없으면 빈 배열로 처리 (React에서 빈 상태 메시지 출력)
if (empty($rows_usermap)) {
    $rowsArray = [];
} else {
    // 6) mdl_prsn_usermap에서 prsnid 값 추출 후 중복 제거
    $prsnids = [];
    foreach ($rows_usermap as $record) {
        $prsnids[] = $record->prsnid;
    }
    $prsnids = array_unique($prsnids);

    // 7) mdl_prsn_contents 테이블에서 prsnid 목록에 해당하는 행들을 조회
    list($in_sql, $in_params) = $DB->get_in_or_equal($prsnids, SQL_PARAMS_NAMED, 'prsn');
    $sql_contents = "SELECT * FROM {prsn_contents} WHERE id $in_sql ORDER BY npersona";
    $rows_contents = $DB->get_records_sql($sql_contents, $in_params);

    if (empty($rows_contents)) {
        $rowsArray = [];
    } else {
        // 8) 각 콘텐츠에 대해 해당 사용자의 최신 strength와 status 값을 가져와 추가
        foreach ($rows_contents as $row) {
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
                $row->status = $usermap->status; // 사용자 저장값으로 덮어쓰기
            }
        }
        // 9) DB 결과를 배열로 변환
        $rowsArray = array_values($rows_contents);
    }
}

// 10) HTML 출력
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>React + PHP Example (Font Awesome, 3D Flip, Slider & Revival)</title>
  
  <!-- React, ReactDOM, Babel, Tailwind CSS -->
  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    body { margin: 0; padding: 0; background: #111; }
    /* 3D 카드 스타일 */
    .flip-container { perspective: 1000px; }
    .flip-card { transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55); transform-style: preserve-3d; position: relative; }
    .flip-card-front, .flip-card-back {
      position: absolute; width: 100%; height: 100%; backface-visibility: hidden;
      top: 0; left: 0; border-radius: 0.5rem;
    }
    .flip-card-back { transform: rotateY(180deg); }
    /* 애니메이션 효과 */
    .move-down { animation: moveDown 0.5s forwards; }
    @keyframes moveDown {
      from { transform: translateY(0); }
      to { transform: translateY(100px); }
    }
    .move-up { animation: moveUp 0.5s forwards; }
    @keyframes moveUp {
      from { transform: translateY(0); }
      to { transform: translateY(-100px); }
    }
    /* 하단 미니아이콘 영역 */
    .minimized-container {
      position: fixed; bottom: 80px; left: 0; right: 0;
      display: flex; justify-content: center; gap: 1rem; z-index: 100;
    }
  </style>
</head>
<body>

<div id="root"></div>

<!-- PHP 데이터 JS 변수 할당 -->
<script type="text/javascript">
  var serverData = <?php echo json_encode($rowsArray, JSON_UNESCAPED_UNICODE); ?>;
  var currentUserId = <?php echo json_encode($userid); ?>;
  // strength 업데이트 URL (DB 업데이트 포함)
  var updateUrl = "https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/update_userpersona.php";
</script>

<!-- React 코드 (Babel 컴파일) -->
<script type="text/babel">
  const { useState, useEffect } = React;

  function PersonaInterface() {
    const [cards, setCards] = useState([]);

    // 초기 데이터 로딩: 카드의 sliderValue 등 기본 상태 설정
    useEffect(() => {
      const data = serverData || [];
      const iconMap = [
        "fa-solid fa-moon",
        "fa-solid fa-brain",
        "fa-solid fa-sun",
        "fa-solid fa-fan",
        "fa-solid fa-clock",
        "fa-solid fa-eye"
      ];
      const newCards = data.map(row => {
        const iconIndex = (row.npersona - 1);
        const iconClass = iconMap[iconIndex] || "fa-solid fa-star";
        const status = row.status !== undefined ? parseInt(row.status, 10) : 0;
        return {
          id: row.id,
          npersona: row.npersona,
          iconClass,
          status,
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
          enepoem: row.enepoem || "enthusiast without effort",
          // enepoem 상태일 때 DB에 저장된 strength 값이 있으면 사용, 없으면 기본 5
          sliderValue: status === 2 ? (row.strength !== undefined ? parseFloat(row.strength) : 5) : null,
          moveDirection: null
        };
      });
      setCards(newCards);
    }, []);

    // 슬라이더 값 변경 처리
    const handleSliderChange = (prsnId, newValue) => {
      setCards(prev =>
        prev.map(card => {
          if (card.id === prsnId) {
            // 슬라이더 값이 5 미만이면 카드 앞면(negative)으로 전환
            if (newValue < 5) {
              const updated = {
                ...card,
                status: 0,         // negative 상태
                sliderValue: null, // 슬라이더 숨김
                moveDirection: null
              };
              // DB 업데이트: 토글 업데이트 분기 (status와 npersona 함께 전송)
              const formData = new URLSearchParams();
              formData.append('userid', currentUserId);
              formData.append('type', 'defaultcontents');
              formData.append('prsnid', prsnId);
              formData.append('npersona', card.npersona);
              formData.append('status', 0);
              formData.append('strength', newValue);
              fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
              })
              .then(res => res.json())
              .then(data => {
                if (!data.success) {
                  console.error("DB status update 실패:", data.message);
                }
              })
              .catch(err => console.error("에러 발생:", err));
              return updated;
            } else {
              // 새 값이 5 이상이면 슬라이더 업데이트 진행
              const wasMinimized = (card.sliderValue > 8);
              const willBeMinimized = (newValue > 8);
              let updated = { ...card, sliderValue: newValue };
              if (!wasMinimized && willBeMinimized) {
                updated.moveDirection = 'down';
              } else if (wasMinimized && !willBeMinimized) {
                updated.moveDirection = 'up';
              }
              // DB 업데이트: strength만 업데이트 (슬라이더 업데이트 분기)
              const formData = new URLSearchParams();
              formData.append('userid', currentUserId);
              formData.append('type', 'defaultcontents');
              formData.append('prsnid', prsnId);
              formData.append('strength', newValue);
              fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
              })
              .then(res => res.json())
              .then(data => {
                if (!data.success) {
                  console.error("DB strength update 실패:", data.message);
                }
              })
              .catch(err => console.error("에러 발생:", err));
              // 애니메이션 효과 후 moveDirection 플래그 제거
              setTimeout(() => {
                setCards(prev2 => prev2.map(c => {
                  if (c.id === prsnId) {
                    const { moveDirection, ...rest } = c;
                    return rest;
                  }
                  return c;
                }));
              }, 500);
              return updated;
            }
          }
          return card;
        })
      );
    };

    // 미니아이콘 클릭 시 부활 기능
    const handleRevive = (prsnId) => {
      setCards(prev =>
        prev.map(card => {
          if (card.id === prsnId) {
            const updated = { ...card, sliderValue: 5, moveDirection: 'up' };
            const formData = new URLSearchParams();
            formData.append('userid', currentUserId);
            formData.append('type', 'defaultcontents');
            formData.append('prsnid', prsnId);
            formData.append('strength', 5);
            fetch(updateUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
              if (!data.success) {
                console.error("DB strength update 실패:", data.message);
              }
            })
            .catch(err => console.error("에러 발생:", err));
            setTimeout(() => {
              setCards(prev2 => prev2.map(c => {
                if (c.id === prsnId) {
                  const { moveDirection, ...rest } = c;
                  return rest;
                }
                return c;
              }));
            }, 500);
            return updated;
          }
          return card;
        })
      );
    };

    // 토글: 카드 클릭 시 상태 순환 (Negative → Positive → Enepoem → Negative)
    const toggleCard = (prsnid, npersona, currentStatus) => {
      const newStatus = (currentStatus + 1) % 3;
      let strength;
      if (newStatus === 0) strength = 2.5;
      else if (newStatus === 1) strength = 7.5;
      else if (newStatus === 2) strength = 5.0;
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
      setCards(prev =>
        prev.map(card =>
          card.id === prsnid ? { ...card, status: newStatus, sliderValue: newStatus === 2 ? 5 : card.sliderValue } : card
        )
      );
    };

    // 카드 분리: sliderValue > 8인 카드는 하단 미니아이콘 영역, 나머지는 상단 그리드에 배치
    const topCards = cards.filter(card => !(card.status === 2 && card.sliderValue > 8));
    const minimizedCards = cards.filter(card => card.status === 2 && card.sliderValue > 8);

    if (cards.length === 0) {
      return (
        <div className="min-h-screen bg-gradient-to-r from-purple-800 to-blue-500 p-4 sm:p-8">
          <div className="max-w-6xl mx-auto flex items-center justify-center h-full">
            <div className="bg-white text-gray-800 p-6 sm:p-8 rounded-lg shadow-2xl space-y-1 font-sans transition-transform duration-300 hover:scale-105 text-center">
              <span className="inline-block w-60 h-130 text-yellow-400 animate-bounce">★</span>
              <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1615326239001.png" alt="캐릭터" className="w-120 mx-auto" />
              <h1 className="text-3xl sm:text-4xl font-bold leading-tight"> </h1>
              <p className="text-lg sm:text-xl leading-tight">모든 부정적인 페르소나가 클리어되었습니다!</p>
              <p className="text-lg sm:text-xl leading-tight"> </p>
              <h2 className="text-3xl sm:text-4xl font-bold leading-tight">새로운 페르소나를 탐험해 보세요!</h2>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-900 via-indigo-900 to-purple-900 p-8">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <span className="inline-block w-6 h-6 mb-2 text-yellow-300">★</span>
            <h1 className="text-2xl text-white font-serif mb-2">
              당신의 현재 모습을 선택하고 성장 가능성을 발견하세요
            </h1>
          </div>
          <div className="grid grid-cols-3 gap-6">
            {topCards.map(card => {
              const { id, npersona, status, iconClass } = card;
              return (
                <div 
                  key={id} 
                  className={`flip-container cursor-pointer ${card.moveDirection === 'up' ? "move-up" : ""}`}
                  onClick={() => toggleCard(id, npersona, status)}
                >
                  <div className="flip-card w-full h-96">
                    {status === 0 && (
                      <div className="flip-card-front bg-gradient-to-br from-red-900/80 to-purple-900/80 text-white flex flex-col p-6">
                        <div className="flex justify-center mb-6 text-4xl">
                          <i className={`${iconClass} text-red-300/80`}></i>
                        </div>
                        <div className="flex-grow flex flex-col justify-center text-center">
                          <h3 className="text-xl font-serif mb-2">{card.negative.title}</h3>
                          <div className="text-red-300/80 text-sm mb-4">{card.negative.subtitle}</div>
                          <p className="text-sm text-gray-300">{card.negative.description}</p>
                        </div>
                      </div>
                    )}
                    {status === 1 && (
                      <div className="flip-card-front bg-gradient-to-br from-indigo-900/80 to-purple-900/80 text-white flex flex-col p-6">
                        <div className="flex justify-center mb-6 text-4xl">
                          <i className={`${iconClass} text-yellow-300/80`}></i>
                        </div>
                        <div className="flex-grow flex flex-col justify-center text-center">
                          <h3 className="text-xl font-serif mb-2">{card.positive.title}</h3>
                          <div className="text-yellow-300/80 text-sm mb-4">{card.positive.subtitle}</div>
                          <p className="text-sm text-gray-300">{card.positive.description}</p>
                        </div>
                      </div>
                    )}
                    {status === 2 && (
                      <div 
                        className="flip-card-front bg-gradient-to-br from-yellow-600/80 to-orange-600/80 text-white flex flex-col p-6"
                        onClick={(e) => e.stopPropagation()}
                      >
                        <div className="flex justify-center mb-6 text-4xl">
                          <i className={`${iconClass} text-yellow-300/80`}></i>
                        </div>
                        <div className="flex-grow flex flex-col justify-center text-center">
                          <h3 className="text-xl font-serif mb-2">Enepoem</h3>
                          <p className="text-sm text-gray-100">{card.enepoem}</p>
                        </div>
                        { card.sliderValue >= 5 ? (
                          <div className="mt-4">
                            <input 
                              type="range" 
                              min="0" 
                              max="10" 
                              step="1" 
                              value={card.sliderValue} 
                              onChange={(e) => handleSliderChange(card.id, parseInt(e.target.value))}
                              className="w-full"
                            />
                            <div className="text-xs text-gray-200 mt-1">Strength: {card.sliderValue}</div>
                          </div>
                        ) : (
                          <div className="mt-4">
                            <p className="text-sm text-gray-200">카드의 앞면</p>
                          </div>
                        )}
                      </div>
                    )}
                    <div className="flip-card-back bg-gray-800/80 flex items-center justify-center text-white p-6">
                      <div className="text-center">
                        <h2 className="text-lg font-bold mb-2">이 카드를 클릭하면 상태가 순환됩니다.</h2>
                        <p className="text-sm text-gray-100">
                          Negative → Positive → Enepoem → Negative
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
        <div className="minimized-container">
          {minimizedCards.map(card => (
            <div 
              key={card.id} 
              className={`minimized-card bg-gray-800 text-white p-2 rounded flex items-center cursor-pointer ${card.moveDirection === 'down' ? "move-down" : ""}`}
              onClick={() => handleRevive(card.id)}
            >
              <i className={`${card.iconClass} mr-1`}></i>
              <span className="text-sm">{card.positive.title}</span>
            </div>
          ))}
        </div>
        <div className="fixed bottom-0 left-0 right-0 bg-gray-900/95 backdrop-blur p-4">
          <div className="max-w-4xl mx-auto">
            <div className="h-1 bg-gray-700/50 rounded-full overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full"
                style={{ width: `${Math.round((cards.reduce((acc, c) => acc + c.status, 0) / (2 * cards.length)) * 100)}%` }}
              />
            </div>
            <div className="mt-2 text-xs text-center text-gray-400">
              페르소나 싱크율 {Math.round((cards.reduce((acc, c) => acc + c.status, 0) / (2 * cards.length)) * 100)}%
            </div>
          </div>
        </div>
      </div>
    );
  }

  const container = document.getElementById('root');
  const root = ReactDOM.createRoot(container);
  root.render(<PersonaInterface />);
</script>

</body>
</html>
