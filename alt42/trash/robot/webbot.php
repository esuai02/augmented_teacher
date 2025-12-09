<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// GET 파라미터(예: userid) 확인
$studentid = optional_param('userid', 0, PARAM_INT);

$aweekago = time() - 604800;
if($studentid == NULL) {
    $studentid = $USER->id;
}
$timecreated = time();
$halfdayago = time() - 3600*12;

$tracking = $DB->get_record_sql("SELECT id,type FROM mdl_abessi_tracking
    WHERE userid='$studentid'
      AND type LIKE 'task'
      AND duration>'$timecreated'
      AND status='begin'
      AND timecreated >'$halfdayago'
    ORDER BY id ASC LIMIT 1");

$set_todaygoal = $DB->get_record_sql("SELECT id FROM mdl_abessi_today
    WHERE userid='$studentid'
      AND (type LIKE '오늘목표' OR type LIKE '검사요청')
      AND timecreated>'$halfdayago'
    ORDER BY id DESC LIMIT 1");

if($tracking->id == NULL && $set_todaygoal->id != NULL) {
    $DB->execute("INSERT INTO {abessi_chat} (mode,text,userid,userto,wboardid,mark,t_trigger)
    VALUES('needtarget','수학일기 쓰기','$studentid','$userid','needtarget','0','$timecreated')");
}

$lmode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data
    WHERE userid='$userid'
      AND fieldid='90'");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <!-- 스마트폰 화면 크기로 스케일 고정 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <title>스마트폰용 웹앱</title>

  <!-- Tailwind CSS 불러오기 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

  <!-- PC에서도 모바일 사이즈로 제한할 수 있는 예시 스타일 -->
  <style>
    @media screen and (min-width: 401px) {
      /* 원하면 PC에서 400px 고정폭으로 표시 (팝업/새창 등) */
      #root {
        max-width: 400px;
        height: 800px;
        margin: 0 auto;
        border: 1px solid #ccc;
      }
    }
  </style>
</head>
<body>
  <!-- 연결 상태 표시용 엘리먼트 (선택사항) -->
  <div id="status" class="ml-4 text-sm text-gray-600">Connecting...</div>

  <!-- React 컴포넌트를 렌더링할 영역 -->
  <div id="root"></div>

  <!-- React / ReactDOM / Babel CDN 불러오기 -->
  <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/babel-standalone@6.26.0/babel.min.js"></script>

  <!-- WebSocket 연결 스크립트 -->
  <script>
    let ws = null;
    const Userid = "<?php echo $studentid; ?>";
    let existingTab = null;

    function openOrFocusTab(Userid, Cntinput) {
      const url = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid="
                  + Userid + "&cntinput=" + Cntinput;
      const windowName = "UserWindow_" + Userid; // Unique window name based on Userid

      existingTab = window.open(url, windowName);
      existingTab.focus();
    }

    // 웹소켓 연결을 시도하는 함수
    function connectWebSocket() {
      ws = new WebSocket("wss://mathking.kr:8080");

      ws.onopen = function() {
        if (document.getElementById("status")) {
          document.getElementById("status").textContent = "Connected";
        }
      };

      ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (document.visibilityState === "visible" && data.mode === "instantmessage" && Userid == data.userId) {
          if(window.handleIncomingMessage) {
            window.handleIncomingMessage(data);
          }
          ws.send(JSON.stringify({ confirmed: true, messageId: data.messageId }));
        }
      };

      ws.onclose = function() {
        if (document.getElementById("status")) {
          document.getElementById("status").textContent = "Disconnected. Reconnecting...";
        }
        // 일정 시간 후 재연결 시도
        setTimeout(connectWebSocket, 3000);
      };
    }

    // 페이지 로드 시도
    window.addEventListener("load", function() {
      connectWebSocket();
    });
  </script>

  <!-- Babel로 트랜스파일할 React 코드 -->
  <script type="text/babel">
    const { useState, useEffect, useRef } = React;

    const RobotInterface = () => {
      const [message, setMessage] = useState("안녕하세요! 저는 당신의 학습 멘토입니다. 무엇을 도와드릴까요?");
      const [blinking, setBlinking] = useState(false);
      const [incomingMessages, setIncomingMessages] = useState([]);
      const messageEndRef = useRef(null);

      // 눈 깜박임
      useEffect(() => {
        const blinkInterval = setInterval(() => {
          setBlinking(true);
          setTimeout(() => setBlinking(false), 200);
        }, Math.random() * 4000 + 3000);

        return () => clearInterval(blinkInterval);
      }, []);

      // WebSocket에서 받은 메시지를 처리하기 위한 전역 함수
      useEffect(() => {
        window.handleIncomingMessage = (data) => {
          setIncomingMessages(prev => [...prev, data.text]);
        };
      }, []);

      // 메시지가 추가될 때마다 스크롤을 맨 아래로 이동
      useEffect(() => {
        if (messageEndRef.current) {
          messageEndRef.current.scrollIntoView({ behavior: "smooth" });
        }
      }, [incomingMessages]);

      return (
        <div className="w-full max-w-md mx-auto p-4">
          <div className="bg-blue-100 rounded-lg p-8">
            {/* 로봇 얼굴 */}
            <div className="flex justify-center items-center space-x-10 mb-6">
              {/* 왼쪽 눈 */}
              <div
                className={
                  "w-16 h-16 rounded-full " +
                  (blinking ? "bg-blue-200" : "bg-blue-400") +
                  " flex items-center justify-center transition-colors duration-200 shadow-lg"
                }
              >
                <div className="w-12 h-12 rounded-full bg-white flex items-center justify-center">
                  <div className="w-6 h-6 rounded-full bg-black relative">
                    <div className="absolute top-1 left-1 w-2 h-2 rounded-full bg-white opacity-80"></div>
                  </div>
                </div>
              </div>

              {/* 오른쪽 눈 */}
              <div
                className={
                  "w-16 h-16 rounded-full " +
                  (blinking ? "bg-blue-200" : "bg-blue-400") +
                  " flex items-center justify-center transition-colors duration-200 shadow-lg"
                }
              >
                <div className="w-12 h-12 rounded-full bg-white flex items-center justify-center">
                  <div className="w-6 h-6 rounded-full bg-black relative">
                    <div className="absolute top-1 left-1 w-2 h-2 rounded-full bg-white opacity-80"></div>
                  </div>
                </div>
              </div>
            </div>

            {/* 초기 메시지 */}
            <hr className="border-blue-200 mb-4" />
            <p className="text-blue-900 text-lg whitespace-normal mb-4">
              {message}
            </p>

            {/* 새로 들어온 메시지 표시 영역 */}
            <div className="bg-white p-4 rounded shadow-inner max-h-64 overflow-y-auto">
              {incomingMessages.map((msg, index) => (
                <div key={index} className="mb-2">
                  <span className="bg-blue-200 px-2 py-1 rounded inline-block">
                    {msg}
                  </span>
                </div>
              ))}
              <div ref={messageEndRef}></div>
            </div>
          </div>
        </div>
      );
    };

    ReactDOM.render(<RobotInterface />, document.getElementById("root"));
  </script>
</body>
</html>
