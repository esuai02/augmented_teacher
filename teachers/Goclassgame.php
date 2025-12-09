<?php 
/////////////////////////////// PHP 초기 설정 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 사용자 ID 결정: GET 파라미터 또는 현재 사용자
$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
$timecreated = time(); 
$halfdayago = $timecreated - 43200;

// 사용자 정보 조회 (예: 성과 이름)
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid'");
$username = $thisuser->lastname;

// 사용자 역할 정보 조회
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->role;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Time Catcher Game</title>
  <!-- 간단한 CSS 스타일 (추가 스타일은 별도 파일로 관리 가능) -->
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f4f6; }
    .p-6 { padding: 1.5rem; }
    .max-w-6xl { max-width: 72rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }
    .bg-white { background-color: #fff; }
    .rounded-lg { border-radius: 0.5rem; }
    .shadow-lg { box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
    .flex { display: flex; }
    .justify-between { justify-content: space-between; }
    .items-center { align-items: center; }
    .mb-6 { margin-bottom: 1.5rem; }
    .text-2xl { font-size: 1.5rem; }
    .font-bold { font-weight: bold; }
    .mr-2 { margin-right: 0.5rem; }
    .mb-4 { margin-bottom: 1rem; }
    .grid { display: grid; }
    .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
    .gap-6 { gap: 1.5rem; }
    .bg-gray-100 { background-color: #f7fafc; }
    .text-lg { font-size: 1.125rem; }
    .font-semibold { font-weight: 600; }
    .relative { position: relative; }
    .absolute { position: absolute; }
    .cursor-pointer { cursor: pointer; }
    .shadow-md { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .transition-all { transition: all 0.3s ease; }
    .bg-gray-50 { background-color: #f9fafb; }
    .border { border: 1px solid #e5e7eb; }
    .border-gray-200 { border-color: #e5e7eb; }
    .border-dashed { border-style: dashed; }
    .rounded-t { border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; }
    .rounded-b { border-bottom-left-radius: 0.25rem; border-bottom-right-radius: 0.25rem; }
    .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
    .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
    .bg-blue-500 { background-color: #3b82f6; }
    .hover\:bg-blue-600:hover { background-color: #2563eb; }
    .text-white { color: #fff; }
    .text-sm { font-size: 0.875rem; }
    .min-h-8 { min-height: 2rem; }
    .mb-1 { margin-bottom: 0.25rem; }
    /* 색상 클래스 데모용 */
    .bg-pink-200 { background-color: #fbcfe8; }
    .bg-yellow-200 { background-color: #fef08a; }
    .bg-purple-200 { background-color: #e9d5ff; }
    .bg-blue-200 { background-color: #bfdbfe; }
    .bg-green-200 { background-color: #bbf7d0; }
  </style>
</head>
<body>
  <!-- React 컴포넌트를 렌더링할 컨테이너 -->
  <div id="root"></div>
  
  <!-- PHP 데이터를 JavaScript로 전달 -->
  <script>
    window.initialData = {
      username: "<?php echo addslashes($username); ?>",
      role: "<?php echo addslashes($role); ?>"
    };
  </script>
  
  <!-- React, ReactDOM, Babel, Framer Motion CDN -->
  <script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
  <script src="https://unpkg.com/framer-motion/dist/framer-motion.umd.js"></script>
  
  <!-- React 코드 (Babel로 인라인 변환) -->
  <script type="text/babel">
    /******************* 더미 컴포넌트 정의 *******************/
    // Lucide 아이콘 더미 컴포넌트
    const Clock = (props) => <span {...props}>🕒</span>;
    const AlertCircle = (props) => <span {...props}>⚠️</span>;

    // Alert 컴포넌트
    const Alert = ({ variant, className, children }) => (
      <div className={`alert ${variant} ${className}`} style={{ border: '1px solid red', padding: '0.5rem', borderRadius: '0.5rem', marginBottom: '1rem' }}>
        {children}
      </div>
    );
    const AlertDescription = ({ children }) => <div>{children}</div>;

    // AlertDialog 및 하위 컴포넌트 더미 정의
    const AlertDialog = ({ open, onOpenChange, children }) => open ? <div className="alert-dialog">{children}</div> : null;
    const AlertDialogAction = ({ onClick, children }) => <button onClick={onClick}>{children}</button>;
    const AlertDialogCancel = ({ children, onClick }) => <button onClick={onClick}>{children}</button>;
    const AlertDialogContent = ({ children }) => <div className="alert-dialog-content" style={{ border: '1px solid #ccc', padding: '1rem', borderRadius: '0.5rem' }}>{children}</div>;
    const AlertDialogDescription = ({ children }) => <div>{children}</div>;
    const AlertDialogFooter = ({ children }) => <div className="alert-dialog-footer" style={{ marginTop: '1rem' }}>{children}</div>;
    const AlertDialogHeader = ({ children }) => <div className="alert-dialog-header">{children}</div>;
    const AlertDialogTitle = ({ children }) => <h2>{children}</h2>;

    // Tooltip 컴포넌트 더미 정의
    const TooltipProvider = ({ children }) => <div>{children}</div>;
    const Tooltip = ({ children }) => <div>{children}</div>;
    const TooltipTrigger = ({ asChild, children }) => children;
    const TooltipContent = ({ children }) => <div className="tooltip-content" style={{ background: '#333', color: '#fff', padding: '0.5rem', borderRadius: '0.25rem' }}>{children}</div>;

    /******************* TimeCatcherGame 컴포넌트 *******************/
    const { useState, useEffect, useRef } = React;
    const { motion } = window["framer-motion"];

    const TimeCatcherGame = () => {
      const [brainDumpItems, setBrainDumpItems] = useState([
        { title: '이메일 확인', content: '고객 문의 메일 답변하기' },
        { title: '회의 준비', content: '프로젝트 진행상황 보고서 작성' },
        { title: '보고서 작성', content: '월간 실적 보고서 초안 작성' }
      ]);
      const [todoList, setTodoList] = useState([]);
      const [timePlan, setTimePlan] = useState([]);
      const [currentTime, setCurrentTime] = useState(new Date());
      const [title, setTitle] = useState('');
      const [content, setContent] = useState('');
      const [currentTimeSlot, setCurrentTimeSlot] = useState(0);
      const [showAlert, setShowAlert] = useState(false);
      const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
      const [itemToDelete, setItemToDelete] = useState(null);
      const [dragItem, setDragItem] = useState(null);
      
      const timePlanRef = useRef(null);

      // 현재 시간 업데이트
      useEffect(() => {
        const timer = setInterval(() => {
          setCurrentTime(new Date());
        }, 1000);
        return () => clearInterval(timer);
      }, []);

      // 시간 슬롯 업데이트 및 스크롤 처리
      useEffect(() => {
        const hour = currentTime.getHours();
        const minute = currentTime.getMinutes();
        const currentSlot = hour * 2 + (minute >= 30 ? 1 : 0);
        setCurrentTimeSlot(currentSlot);

        if (timePlanRef.current) {
          const slotHeight = 32;
          const targetSlot = Math.max(0, currentSlot - 1);
          timePlanRef.current.scrollTop = targetSlot * slotHeight;
        }
      }, [currentTime]);

      // Brain Dump 항목 추가 처리
      const handleSubmit = (e) => {
        e.preventDefault();
        if (title.trim() && content.trim()) {
          setBrainDumpItems([...brainDumpItems, { title: title.trim(), content: content.trim() }]);
          setTitle('');
          setContent('');
        }
      };

      // Brain Dump에서 To Do List로 항목 이동
      const moveTodoList = (item) => {
        if (todoList.length >= 3) {
          setShowAlert(true);
          setTimeout(() => setShowAlert(false), 3000);
          return;
        }
        setBrainDumpItems(brainDumpItems.filter(i => i.title !== item.title));
        setTodoList([...todoList, { ...item, color: getRandomColor() }]);
      };

      // 48개의 30분 단위 시간 슬롯 생성
      const timeSlots = Array.from({ length: 48 }, (_, i) => ({
        hour: Math.floor(i / 2),
        minute: (i % 2) * 30
      }));

      // 현재 시간 슬롯 이후의 첫 사용 가능한 슬롯 찾기
      const findNextAvailableSlot = () => {
        const currentSlot = currentTimeSlot;
        for (let i = currentSlot; i < timeSlots.length; i++) {
          const slot = timeSlots[i];
          const hasItem = timePlan.some(item => 
            item.hour === slot.hour && item.minute === slot.minute
          );
          if (!hasItem) return slot;
        }
        return timeSlots[currentSlot]; // fallback to current slot
      };

      // To Do List에서 Time Plan으로 항목 이동
      const moveToTimePlan = (item) => {
        const nextSlot = findNextAvailableSlot();
        setTodoList(todoList.filter(i => i.title !== item.title));
        setTimePlan([...timePlan, { ...item, hour: nextSlot.hour, minute: nextSlot.minute }]);
      };

      // 드래그 시작 처리
      const handleDragStart = (item) => {
        setDragItem(item);
      };

      // 드래그 오버 처리
      const handleDragOver = (e, hour, minute) => {
        e.preventDefault();
      };

      // 드롭 처리 (Time Plan에서 시간 슬롯 변경)
      const handleDrop = (e, hour, minute) => {
        e.preventDefault();
        if (dragItem) {
          const updatedTimePlan = timePlan.map(item => 
            item === dragItem ? { ...item, hour, minute } : item
          );
          setTimePlan(updatedTimePlan);
          setDragItem(null);
        }
      };

      // 더블 클릭 시 항목 삭제 다이얼로그 열기
      const handleDoubleClick = (item) => {
        setItemToDelete(item);
        setDeleteDialogOpen(true);
      };

      // 항목 삭제 처리
      const handleDelete = () => {
        if (itemToDelete) {
          setBrainDumpItems(brainDumpItems.filter(item => item !== itemToDelete));
          setDeleteDialogOpen(false);
          setItemToDelete(null);
        }
      };

      // 랜덤 색상 선택 함수
      const getRandomColor = () => {
        const colors = ['bg-pink-200', 'bg-yellow-200', 'bg-purple-200', 'bg-blue-200', 'bg-green-200'];
        return colors[Math.floor(Math.random() * colors.length)];
      };

      return (
        <div className="p-6 max-w-6xl mx-auto bg-white rounded-lg shadow-lg">
          <div className="flex justify-between items-center mb-6">
            <h1 className="text-2xl font-bold">Time Catcher</h1>
            <div className="flex items-center">
              <Clock className="mr-2" />
              <span>{currentTime.toLocaleTimeString()}</span>
            </div>
          </div>

          {showAlert && (
            <Alert variant="destructive" className="mb-4">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                Todo List가 가득 찼습니다. 기존 항목을 완료한 후 새로운 항목을 추가해주세요.
              </AlertDescription>
            </Alert>
          )}

          <div className="grid grid-cols-2 gap-6">
            <div className="flex flex-col h-full">
              {/* Brain Dump */}
              <div className="bg-gray-100 p-4 rounded-lg flex-1">
                <h2 className="text-lg font-semibold mb-4">Brain Dump</h2>
                <div className="relative" style={{ height: '384px' }}>
                  <TooltipProvider>
                    {brainDumpItems.map((item, index) => (
                      <Tooltip key={item.title}>
                        <TooltipTrigger asChild>
                          <motion.div
                            animate={{
                              x: Math.cos(index * (2 * Math.PI / brainDumpItems.length)) * 120,
                              y: Math.sin(index * (2 * Math.PI / brainDumpItems.length)) * 80,
                            }}
                            transition={{
                              repeat: Infinity,
                              duration: 20,
                              ease: "linear",
                              delay: index * -2
                            }}
                            whileHover={{ scale: 1.1 }}
                            className="absolute cursor-pointer bg-white p-2 rounded-lg shadow-md"
                            style={{ left: '50%', top: '50%', transform: 'translate(-50%, -50%)' }}
                            onClick={() => moveTodoList(item)}
                            onDoubleClick={() => handleDoubleClick(item)}
                          >
                            {item.title}
                          </motion.div>
                        </TooltipTrigger>
                        <TooltipContent>
                          <p>{item.content}</p>
                        </TooltipContent>
                      </Tooltip>
                    ))}
                  </TooltipProvider>
                </div>
              </div>

              {/* Todo List */}
              <div className="bg-gray-100 p-4 rounded-lg mt-6">
                <h2 className="text-lg font-semibold mb-4">To Do List ({todoList.length}/3)</h2>
                <div>
                  {[0, 1, 2].map((index) => {
                    const item = todoList[index];
                    return (
                      <div
                        key={index}
                        className={`h-12 rounded-lg transition-all ${item ? `${item.color} cursor-pointer hover:scale-[1.02] shadow-sm` : 'bg-gray-50 border-2 border-dashed border-gray-200'} ${index !== 2 ? 'mb-2' : ''}`}
                        onClick={() => item && moveToTimePlan(item)}
                      >
                        {item && (
                          <div className="h-full flex items-center px-4">
                            {item.title}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
                <form onSubmit={handleSubmit} className="flex flex-col mt-2">
                  <input
                    type="text"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    className="w-full py-1 px-2 rounded-t border border-b-0 text-sm"
                    placeholder="제목 입력..."
                  />
                  <input
                    type="text"
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                    className="w-full py-1 px-2 border border-b-0 text-sm"
                    placeholder="내용 입력..."
                  />
                  <button 
                    type="submit" 
                    className="w-full bg-blue-500 text-white py-1 rounded-b hover:bg-blue-600 transition-colors text-sm"
                  >
                    추가
                  </button>
                </form>
              </div>
            </div>

            {/* Time Plan */}
            <div className="bg-gray-100 p-4 rounded-lg overflow-y-auto" ref={timePlanRef} style={{ maxHeight: '800px' }}>
              <h2 className="text-lg font-semibold mb-4">Time Plan</h2>
              <div className="space-y-1">
                {timeSlots.map(({ hour, minute }, index) => {
                  const isPast = index < currentTimeSlot;
                  return (
                    <div 
                      key={index} 
                      className="flex items-center text-sm"
                      onDragOver={(e) => handleDragOver(e, hour, minute)}
                      onDrop={(e) => handleDrop(e, hour, minute)}
                    >
                      <span className={`w-16 font-medium ${isPast ? 'text-gray-400' : ''}`}>
                        {String(hour).padStart(2, '0')}:{String(minute).padStart(2, '0')}
                      </span>
                      <div className={`flex-1 min-h-8 border-b border-gray-300 ${isPast ? 'bg-gray-50' : ''}`}>
                        {timePlan
                          .filter(item => item.hour === hour && item.minute === minute)
                          .map(item => (
                            <motion.div
                              key={item.title}
                              initial={{ opacity: 0 }}
                              animate={{ opacity: 1 }}
                              draggable
                              onDragStart={() => handleDragStart(item)}
                              className={`${isPast ? 'opacity-50' : ''} ${item.color} p-2 rounded-lg mb-1 cursor-move`}
                            >
                              <div className="font-medium">{item.title}</div>
                              <div className="text-xs">{item.content}</div>
                            </motion.div>
                          ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>항목 삭제</AlertDialogTitle>
                <AlertDialogDescription>
                  "{itemToDelete?.title}"을(를) 삭제하시겠습니까?
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel onClick={() => setDeleteDialogOpen(false)}>취소</AlertDialogCancel>
                <AlertDialogAction onClick={handleDelete}>삭제</AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      );
    };

    // ReactDOM을 이용해 TimeCatcherGame 컴포넌트를 렌더링
    const rootElement = document.getElementById('root');
    const root = ReactDOM.createRoot(rootElement);
    root.render(<TimeCatcherGame />);
  </script>
</body>
</html>
