<?php
echo '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Tailwind + React 로컬 테스트</title>
  <!-- 로컬에 저장한 Tailwind CSS 불러오기 -->
  <link href="tailwind.min.css" rel="stylesheet">
  <!-- React, ReactDOM CDN -->
  <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <!-- Babel Standalone -->
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body class="bg-gray-900 min-h-screen">

  <!-- React Component가 삽입될 영역 -->
  <div id="root" class="p-8"></div>

  <!-- JSX/ES6 코드를 작성 -->
  <script type="text/babel">
    const { useState, useEffect } = React;

    // 임시 아이콘 컴포넌트
    const UserIcon = (props) => (
      <div
        style={{
          width: props.size || "32px",
          height: props.size || "32px",
          backgroundColor: props.color || "white",
        }}
      />
    );

    const UserCircleIcon = (props) => (
      <div
        style={{
          width: props.size || "48px",
          height: props.size || "48px",
          borderRadius: "50%",
          backgroundColor: props.color || "green",
        }}
      />
    );

    const VirtualSpace = () => {
      // 초기 플레이어 위치
      const initialPosition = { x: 10, y: 7 };
      const [playerPosition, setPlayerPosition] = useState(initialPosition);

      const [nearbyZone, setNearbyZone] = useState(null);
      const [nearTeacher, setNearTeacher] = useState(false);
      const [showChatAlert, setShowChatAlert] = useState(false);

      // 격자 크기
      const gridSize = { width: 20, height: 15 };

      // 선생님 위치 및 상호작용 반경
      const teacherPosition = { x: 10, y: 12, interactionRadius: 2 };

      // 메뉴 아이템 (모두 같은 영역 크기: width=4, height=3)
      const menuItems = [
        { text: "내 공부방", position: "top-left",     zone: { x: 2,  y: 2,  width: 4, height: 3 } },
        { text: "활동결과", position: "top-right",    zone: { x: 14, y: 2,  width: 4, height: 3 } },
        { text: "목표설정", position: "middle-left",  zone: { x: 2,  y: 6,  width: 4, height: 3 } },
        { text: "수학일기", position: "middle-right", zone: { x: 14, y: 6,  width: 4, height: 3 } },
        { text: "분기목표", position: "bottom-left",  zone: { x: 2,  y: 10, width: 4, height: 3 } },
        { text: "시간표",   position: "bottom-right", zone: { x: 14, y: 10, width: 4, height: 3 } },
      ];

      // 채팅 방 열기 예시 (Alert)
const openChatRoom = () => {
  setShowChatAlert(true);
        // 실사용 시 여기에서 채팅 열기 로직 구현
        setTimeout(() => setShowChatAlert(false), 3000);
      };

      useEffect(() => {
        const handleKeyPress = (e) => {
          const speed = 1;
          let newPos = { ...playerPosition };

          switch (e.key) {
            case "ArrowUp":
              newPos.y = Math.max(1, playerPosition.y - speed);
              break;
            case "ArrowDown":
              newPos.y = Math.min(gridSize.height - 2, playerPosition.y + speed);
              break;
            case "ArrowLeft":
              newPos.x = Math.max(1, playerPosition.x - speed);
              break;
            case "ArrowRight":
              newPos.x = Math.min(gridSize.width - 2, playerPosition.x + speed);
              break;
            default:
              return;
          }

          // 이동 후 선생님과의 거리 계산
          const distanceToTeacher = Math.sqrt(
            Math.pow(newPos.x - teacherPosition.x, 2) +
            Math.pow(newPos.y - teacherPosition.y, 2)
          );

          // 선생님 반경 안으로 들어가면 이동 중단 & 링크 오픈
          if (distanceToTeacher <= teacherPosition.interactionRadius) {
            window.open("https://www.mathking.kr", "_blank");
            return; // 이동 자체를 취소
          }

          // 메뉴 영역 진입 여부
          const nearby = menuItems.find((item) => {
            const { x, y, width, height } = item.zone;
            return (
              newPos.x >= x - 1 &&
              newPos.x <= x + width + 1 &&
              newPos.y >= y - 1 &&
              newPos.y <= y + height + 1
            );
          });

          // 영역에 들어가면 링크 열고 플레이어 위치 리셋
          if (nearby) {
            window.open("https://www.mathking.kr", "_blank");
            setPlayerPosition(initialPosition);
            return;
          }

          // 이동 확정
          setPlayerPosition(newPos);

          // 상태 업데이트
          setNearTeacher(distanceToTeacher <= teacherPosition.interactionRadius);
          setNearbyZone(nearby);
        };

        window.addEventListener("keydown", handleKeyPress);
        return () => window.removeEventListener("keydown", handleKeyPress);
      }, [playerPosition]);

      // 메뉴 아이템 위치 클래스
      const getMenuItemClass = (position) => {
        const baseClass =
          "absolute bg-indigo-900 text-white p-4 rounded flex items-center justify-center";
        switch (position) {
          case "top-left":
            return `${baseClass} top-4 left-4`;
          case "top-right":
            return `${baseClass} top-4 right-4`;
          case "middle-left":
            return `${baseClass} top-1/2 -translate-y-1/2 left-4`;
          case "middle-right":
            return `${baseClass} top-1/2 -translate-y-1/2 right-4`;
          case "bottom-left":
            return `${baseClass} bottom-4 left-4`;
          case "bottom-right":
            return `${baseClass} bottom-4 right-4`;
          default:
            return baseClass;
        }
      };

      // 셀 크기(px)
      const cellSize = 32;

      return (
        <div className="relative w-full max-w-4xl mx-auto bg-gray-900 overflow-hidden p-8 min-h-screen">
          <div
            className="relative rounded-lg"
            style={{
              width: `${gridSize.width * cellSize}px`,
              height: `${gridSize.height * cellSize}px`,
            }}
          >
            {/* 격자 배경 */}
            <div
              className="absolute inset-0"
              style={{
                backgroundSize: `${cellSize}px ${cellSize}px`,
                backgroundImage: `
                  linear-gradient(to right, rgba(255,255,255,0.1) 1px, transparent 1px),
                  linear-gradient(to bottom, rgba(255,255,255,0.1) 1px, transparent 1px)
                `,
              }}
            />

            {/* 메뉴 아이템 */}
            {menuItems.map((item, index) => (
              <div key={index} className={getMenuItemClass(item.position)}>
                <span className="text-lg font-medium">{item.text}</span>
              </div>
            ))}

            {/* 선생님 */}
            <div
              className="absolute z-10 transform -translate-x-1/2 -translate-y-1/2 
                         transition-all duration-300 ease-in-out"
              style={{
                left: `${teacherPosition.x * cellSize}px`,
                top: `${teacherPosition.y * cellSize}px`,
              }}
            >
              <UserCircleIcon size="48px" color="green" />
              <div className="absolute -top-6 left-1/2 -translate-x-1/2 text-green-400 text-sm whitespace-nowrap">
                선생님
              </div>
            </div>

            {/* 플레이어 */}
            <div
              className="absolute z-10 transform -translate-x-1/2 -translate-y-1/2 
                         transition-all duration-300 ease-in-out"
              style={{
                left: `${playerPosition.x * cellSize}px`,
                top: `${playerPosition.y * cellSize}px`,
              }}
            >
              <UserIcon size="32px" color="white" />
              {(nearbyZone || nearTeacher) && (
                <div className="absolute -top-8 left-1/2 -translate-x-1/2 
                                bg-white text-black px-2 py-1 rounded text-sm whitespace-nowrap">
                  {nearTeacher
                    ? "선생님 근처입니다"
                    : "이 영역에 들어왔습니다"}
                </div>
              )}
            </div>
          </div>

          {/* 채팅 알림 (실제 사용 시 수정 가능) */}
          {showChatAlert && (
            <div className="fixed bottom-4 right-4 z-50 bg-green-100 border border-green-500 p-4 rounded">
              <div className="text-green-900 font-bold mb-2">채팅방이 열렸습니다</div>
              <div className="text-green-900">선생님과 실시간으로 대화하실 수 있습니다</div>
            </div>
          )}
        </div>
      );
    };

    ReactDOM.render(<VirtualSpace />, document.getElementById("root"));
  </script>
</body>
</html>';
?>
