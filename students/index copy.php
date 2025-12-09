import React, { useState, useEffect, useRef, useCallback } from 'react';
import { ChevronDown, ChevronRight, MapPin, Home, BookOpen, FileText, Play, 
         Brain, Timer, Coffee, Target, Zap, Moon, Sun, Volume2, BarChart3,
         CheckCircle, Circle, RefreshCw, Award, Calculator, ExternalLink,
         Edit3, Eraser, Palette, Square, Triangle, Calendar, Search,
         Users, Heart, Sparkles, TrendingUp, Clock, Book } from 'lucide-react';

const MathKingLMS = () => {
  const [currentLevel, setCurrentLevel] = useState(0);
  const [currentPath, setCurrentPath] = useState([]);
  const [animationDirection, setAnimationDirection] = useState('down');
  const [isAnimating, setIsAnimating] = useState(false);
  const [showMinimap, setShowMinimap] = useState(false);
  
  // 뇌과학 기반 상태
  const [studyTime, setStudyTime] = useState(0);
  const [breakTime, setBreakTime] = useState(0);
  const [isOnBreak, setIsOnBreak] = useState(false);
  const [completedItems, setCompletedItems] = useState({});
  const [isDarkMode, setIsDarkMode] = useState(true);
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [focusScore, setFocusScore] = useState(100);
  const [streakDays, setStreakDays] = useState(0);
  const [totalXP, setTotalXP] = useState(0);
  const [showStats, setShowStats] = useState(false);
  const [pomodoroCount, setPomodoroCount] = useState(0);
  
  // 옴니모드 상태
  const [isAIAnalyzing, setIsAIAnalyzing] = useState(false);
  const [currentProblem, setCurrentProblem] = useState(null);
  const [chatMessages, setChatMessages] = useState([]);
  const [chatInput, setChatInput] = useState('');
  const [isVoiceActive, setIsVoiceActive] = useState(false);
  const [teacherEmotion, setTeacherEmotion] = useState('normal');
  
  const containerRef = useRef(null);
  const audioContextRef = useRef(null);
  const canvasRef = useRef(null);

  // 메인 대시보드 - 6개 메뉴
  const mainDashboard = {
    "내공부방": { 
      icon: "📚", 
      description: "나만의 학습 공간", 
      color: "from-blue-500 to-blue-600",
      emoji: "🏠"
    },
    "공부결과": { 
      icon: "📊", 
      description: "학습 성과 분석", 
      color: "from-green-500 to-green-600",
      emoji: "📈"
    },
    "수학일기": { 
      icon: "📝", 
      description: "학습 기록 관리", 
      color: "from-purple-500 to-purple-600",
      emoji: "✏️"
    },
    "시간표": { 
      icon: "📅", 
      description: "학습 스케줄", 
      color: "from-orange-500 to-orange-600",
      emoji: "⏰"
    },
    "보충학습": { 
      icon: "🎯", 
      description: "심화 학습 콘텐츠", 
      color: "from-red-500 to-red-600",
      emoji: "💡"
    },
    "성장마인드": { 
      icon: "🌱", 
      description: "마인드셋 개발", 
      color: "from-teal-500 to-teal-600",
      emoji: "🚀"
    }
  };

  // 서브 메뉴 구조
  const subMenus = {
    "내공부방": {
      url: "https://mathking.kr/moodle/local/augmented_teacher/students/index.php",
      hasSubMenu: false
    },
    "공부결과": {
      url: "https://mathking.kr/moodle/local/augmented_teacher/students/today.php?tb=604800",
      hasSubMenu: false
    },
    "수학일기": {
      url: "https://mathking.kr/moodle/local/augmented_teacher/students/integrated_goals.php?tb=604800",
      hasSubMenu: false
    },
    "시간표": {
      url: "https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?eid=1&nweek=12",
      hasSubMenu: false
    },
    "보충학습": {
      hasSubMenu: true,
      items: {
        "주제특강": {
          icon: "🎓",
          description: "핵심 주제별 특강",
          url: "https://mathking.kr/moodle/local/augmented_teacher/books/domaindrilling.php?domain=120",
          color: "from-indigo-500 to-indigo-600"
        },
        "개념검색": {
          icon: "🔍",
          description: "개념 통합 검색",
          url: "https://mathking.kr/moodle/local/augmented_teacher/students/searchmynote.php",
          color: "from-blue-500 to-blue-600"
        },
        "안키퀴즈": {
          icon: "🎮",
          description: "간격반복 퀴즈",
          url: "https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=math&sbjt=m1&nch=1&tid=&vm=&tid=1080",
          color: "from-green-500 to-green-600"
        }
      }
    },
    "성장마인드": {
      hasSubMenu: true,
      items: {
        "도파민 이야기": {
          icon: "🧠",
          description: "학습 동기 과학",
          url: "https://claude.ai/public/artifacts/d6cbea55-b8c5-4076-8f3d-145fa1048673?fullscreen=true",
          color: "from-pink-500 to-pink-600"
        },
        "수학 성장마인드셋": {
          icon: "📈",
          description: "성장형 사고방식",
          url: "https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/growthmindset.php",
          color: "from-purple-500 to-purple-600"
        },
        "마인드셋 진단": {
          icon: "🔬",
          description: "사고방식 진단",
          url: "https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/self_diagnosis.php",
          color: "from-yellow-500 to-yellow-600"
        },
        "인지관성 진단": {
          icon: "🎯",
          description: "학습 패턴 분석",
          url: "https://claude.ai/public/artifacts/56d40197-a5a3-49e2-857d-555b76a9e7cb?fullscreen=true",
          color: "from-orange-500 to-orange-600"
        },
        "페르소나 탐구": {
          icon: "👥",
          description: "학습자 유형 탐색",
          url: "https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php",
          color: "from-teal-500 to-teal-600"
        },
        "마이 에이전트": {
          icon: "🤖",
          description: "AI 학습 도우미",
          url: "https://claude.ai/public/artifacts/87e21b71-3a87-4838-88b4-baf267174449?fullscreen=true",
          color: "from-indigo-500 to-indigo-600"
        }
      }
    }
  };

  // 뽀모도로 타이머
  useEffect(() => {
    const timer = setInterval(() => {
      if (!isOnBreak) {
        setStudyTime(prev => {
          const newTime = prev + 1;
          if (newTime >= 1500) { // 25분
            triggerBreak();
            return 0;
          }
          return newTime;
        });
        
        setFocusScore(prev => Math.max(0, prev - 0.05));
      } else {
        setBreakTime(prev => {
          const newTime = prev + 1;
          if (newTime >= 300) { // 5분
            endBreak();
            return 0;
          }
          return newTime;
        });
        
        setFocusScore(prev => Math.min(100, prev + 0.5));
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [isOnBreak]);

  // 사운드 효과
  const playSound = useCallback((type) => {
    if (!soundEnabled) return;
    
    if (!audioContextRef.current) {
      audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
    }
    
    const ctx = audioContextRef.current;
    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);
    
    switch(type) {
      case 'complete':
        oscillator.frequency.value = 523.25;
        gainNode.gain.value = 0.1;
        break;
      case 'levelup':
        oscillator.frequency.value = 659.25;
        gainNode.gain.value = 0.15;
        break;
      case 'break':
        oscillator.frequency.value = 440;
        gainNode.gain.value = 0.08;
        break;
    }
    
    oscillator.start();
    oscillator.stop(ctx.currentTime + 0.2);
  }, [soundEnabled]);

  const triggerBreak = () => {
    setIsOnBreak(true);
    setPomodoroCount(prev => prev + 1);
    playSound('break');
    
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('휴식 시간!', {
        body: '5분간 휴식하세요. 물을 마시고 스트레칭을 해보세요.',
        icon: '☕'
      });
    }
  };

  const endBreak = () => {
    setIsOnBreak(false);
    setBreakTime(0);
    playSound('complete');
    
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('학습 재개!', {
        body: '다시 집중해볼까요?',
        icon: '📚'
      });
    }
  };

  // 네비게이션 애니메이션
  const navigateToLevel = (level, newPath, direction = 'down') => {
    if (isAnimating) return;
    
    setIsAnimating(true);
    setAnimationDirection(direction);
    
    if (containerRef.current) {
      if (direction === 'down') {
        containerRef.current.style.transform = 'scale(4) translateZ(200px)';
        containerRef.current.style.opacity = '0';
        containerRef.current.style.filter = 'blur(40px)';
      } else {
        containerRef.current.style.transform = 'scale(0) translateZ(-200px)';
        containerRef.current.style.opacity = '0';
        containerRef.current.style.filter = 'blur(30px)';
      }
    }
    
    setTimeout(() => {
      setCurrentLevel(level);
      setCurrentPath(newPath);
      
      if (containerRef.current) {
        if (direction === 'down') {
          containerRef.current.style.transform = 'scale(0) translateZ(-100px)';
          containerRef.current.style.opacity = '0';
          containerRef.current.style.filter = 'blur(30px)';
        } else {
          containerRef.current.style.transform = 'scale(3) translateZ(300px)';
          containerRef.current.style.opacity = '0';
          containerRef.current.style.filter = 'blur(50px)';
        }
      }
      
      setTimeout(() => {
        if (containerRef.current) {
          containerRef.current.style.transform = 'scale(1) translateZ(0)';
          containerRef.current.style.opacity = '1';
          containerRef.current.style.filter = 'blur(0px)';
        }
        
        setTimeout(() => {
          setIsAnimating(false);
        }, 800);
      }, 50);
    }, 800);
  };

  const goBack = () => {
    if (currentLevel > 0) {
      const newPath = [...currentPath];
      newPath.pop();
      navigateToLevel(currentLevel - 1, newPath, 'up');
    }
  };

  const goHome = () => {
    navigateToLevel(0, [], 'up');
  };

  // 외부 링크 열기
  const openExternalLink = (url) => {
    if (url) {
      window.open(url, '_blank');
      // XP 증가
      setTotalXP(prev => prev + 50);
      playSound('complete');
    }
  };

  // 메뉴 클릭 핸들러
  const handleMenuClick = (category) => {
    const menu = subMenus[category];
    
    if (menu.hasSubMenu) {
      // 서브메뉴가 있는 경우 레벨 1로 이동
      navigateToLevel(1, [category]);
    } else {
      // 서브메뉴가 없는 경우 바로 링크 열기
      openExternalLink(menu.url);
    }
  };

  // 휴식 화면
  if (isOnBreak) {
    return (
      <div className={`min-h-screen flex items-center justify-center ${
        isDarkMode ? 'bg-gray-900' : 'bg-blue-50'
      }`}>
        <div className="text-center">
          <Coffee className={`w-24 h-24 mx-auto mb-6 ${
            isDarkMode ? 'text-blue-400' : 'text-blue-600'
          }`} />
          <h2 className={`text-4xl font-bold mb-4 ${
            isDarkMode ? 'text-white' : 'text-gray-800'
          }`}>
            휴식 시간
          </h2>
          <p className={`text-xl mb-8 ${
            isDarkMode ? 'text-gray-300' : 'text-gray-600'
          }`}>
            잠시 눈을 쉬게 하고 스트레칭을 해보세요
          </p>
          <div className={`text-6xl font-mono ${
            isDarkMode ? 'text-blue-400' : 'text-blue-600'
          }`}>
            {Math.floor((300 - breakTime) / 60)}:{String((300 - breakTime) % 60).padStart(2, '0')}
          </div>
          <button
            onClick={endBreak}
            className="mt-8 px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
          >
            휴식 종료
          </button>
        </div>
      </div>
    );
  }

  // 메인 화면 (6개 메뉴 + 옴니모드)
  const renderLevel0 = () => (
    <div className="h-screen flex flex-col justify-center items-center pt-2">
      <div className="text-center mb-6">
        <h1 className={`text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4`}>
          Math King 학습 플랫폼
        </h1>
        <p className={`text-xl ${isDarkMode ? 'text-gray-300' : 'text-gray-600'} mb-8`}>
          체계적인 수학 학습의 시작
        </p>
      </div>
      
      <div className="grid grid-cols-3 gap-6 max-w-6xl mx-auto mb-6">
        {Object.entries(mainDashboard).map(([category, info]) => (
          <div
            key={category}
            className={`relative bg-gradient-to-r ${info.color} text-white p-8 rounded-2xl 
                       cursor-pointer transform hover:scale-105 transition-all duration-500
                       shadow-xl hover:shadow-2xl flex flex-col items-center justify-center
                       min-h-[180px] ${isAnimating ? 'pointer-events-none' : ''}`}
            onClick={() => handleMenuClick(category)}
          >
            <div className="text-5xl mb-3">{info.emoji}</div>
            <h2 className="text-2xl font-bold mb-2">{category}</h2>
            <p className="text-white/90 text-sm text-center">{info.description}</p>
            {!subMenus[category].hasSubMenu && (
              <ExternalLink className="w-4 h-4 mt-2 opacity-70" />
            )}
          </div>
        ))}
      </div>

      {/* 옴니모드 학습 버튼 */}
      <div className="max-w-6xl mx-auto w-full relative px-4">
        <button
          className={`w-full py-6 px-0 relative overflow-hidden text-white 
                     rounded-2xl font-bold text-2xl shadow-2xl hover:shadow-3xl transform hover:scale-105 
                     transition-all duration-500 ${isAnimating ? 'pointer-events-none' : ''}`}
          onClick={() => navigateToLevel(2, ['omni'])}
          style={{
            background: `linear-gradient(to right, 
              transparent 0%, 
              rgba(236, 72, 153, 0.3) 5%, 
              rgba(236, 72, 153, 1) 15%, 
              rgba(239, 68, 68, 1) 50%, 
              rgba(245, 158, 11, 1) 85%, 
              rgba(245, 158, 11, 0.3) 95%, 
              transparent 100%)`
          }}
        >
          <div className="flex items-center justify-center space-x-4">
            <Brain className="w-8 h-8" />
            <span>🤖 옴니모드로 학습</span>
            <Zap className="w-8 h-8" />
          </div>
          <p className="text-base font-normal mt-2 text-white/90">
            AI가 진단하여 맞춤형 문제를 제공하는 스마트 학습
          </p>
        </button>
      </div>
    </div>
  );

  // 서브메뉴 화면
  const renderLevel1 = () => {
    const currentCategory = currentPath[0];
    const menu = subMenus[currentCategory];
    
    if (!menu || !menu.hasSubMenu) {
      return renderLevel0();
    }
    
    return (
      <div className="space-y-8 max-w-5xl mx-auto">
        <div className="text-center mb-12">
          <h1 className={`text-5xl font-bold mb-4 ${
            isDarkMode ? 'text-white' : 'text-blue-900'
          }`}>
            {currentCategory}
          </h1>
          <p className={`text-xl ${isDarkMode ? 'text-gray-300' : 'text-gray-600'}`}>
            학습 콘텐츠를 선택하세요
          </p>
        </div>
        
        <div className={`grid ${
          Object.keys(menu.items).length <= 3 ? 'grid-cols-3' : 'grid-cols-3'
        } gap-6`}>
          {Object.entries(menu.items).map(([itemName, itemInfo]) => (
            <div
              key={itemName}
              className={`relative bg-gradient-to-br ${itemInfo.color} text-white p-8 rounded-2xl 
                         cursor-pointer transform hover:scale-105 transition-all duration-300
                         shadow-xl hover:shadow-2xl flex flex-col items-center justify-center
                         min-h-[200px] ${isAnimating ? 'pointer-events-none' : ''}`}
              onClick={() => openExternalLink(itemInfo.url)}
            >
              <div className="text-5xl mb-4">{itemInfo.icon}</div>
              <h3 className="text-xl font-bold text-center mb-2">{itemName}</h3>
              <p className="text-white/90 text-sm text-center">{itemInfo.description}</p>
              <ExternalLink className="w-5 h-5 mt-3 opacity-70" />
            </div>
          ))}
        </div>
        
        <div className="text-center mt-8">
          <p className={`text-sm ${isDarkMode ? 'text-gray-400' : 'text-gray-600'}`}>
            항목을 클릭하면 새 창에서 학습 페이지가 열립니다
          </p>
        </div>
      </div>
    );
  };

  // 옴니모드 학습 화면 (기존 코드 유지)
  const renderOmniMode = () => {
    return (
      <div className="h-screen flex items-center justify-center">
        <div className="text-center">
          <Brain className={`w-24 h-24 mx-auto mb-6 ${
            isDarkMode ? 'text-pink-400' : 'text-pink-600'
          } animate-pulse`} />
          <h2 className={`text-4xl font-bold mb-4 ${
            isDarkMode ? 'text-white' : 'text-gray-800'
          }`}>
            🤖 AI 옴니모드 학습
          </h2>
          <p className={`text-xl mb-8 ${
            isDarkMode ? 'text-gray-300' : 'text-gray-600'
          }`}>
            AI 기반 맞춤형 학습 시스템
          </p>
          <div className={`text-lg ${
            isDarkMode ? 'text-gray-400' : 'text-gray-600'
          }`}>
            Coming Soon...
          </div>
          <button
            onClick={goHome}
            className="mt-8 px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
          >
            홈으로 돌아가기
          </button>
        </div>
      </div>
    );
  };

  const renderContent = () => {
    switch (currentLevel) {
      case 0: return renderLevel0();
      case 1: return renderLevel1();
      case 2: return currentPath[0] === 'omni' ? renderOmniMode() : renderLevel0();
      default: return renderLevel0();
    }
  };

  const renderMinimap = () => (
    <div className={`fixed top-20 right-4 ${
      isDarkMode ? 'bg-gray-800 border-gray-600' : 'bg-white border-blue-300'
    } border-2 rounded-lg p-4 shadow-lg z-50 w-64`}>
      <h3 className={`font-bold text-sm mb-3 ${isDarkMode ? 'text-white' : 'text-black'}`}>
        학습 경로
      </h3>
      <div className="space-y-2">
        <div
          className={`flex items-center space-x-2 p-2 rounded cursor-pointer ${isDarkMode ? 'text-white' : 'text-black'} ${
            currentLevel === 0 
              ? isDarkMode ? 'bg-blue-800' : 'bg-blue-100' 
              : isDarkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-100'
          }`}
          onClick={() => navigateToLevel(0, [])}
        >
          <Home className="w-4 h-4" />
          <span className="text-sm">홈</span>
        </div>
        {currentPath.length > 0 && currentPath[0] !== 'omni' && (
          <div
            className={`flex items-center space-x-2 p-2 rounded cursor-pointer ml-4 ${isDarkMode ? 'text-white' : 'text-black'} ${
              currentLevel === 1 
                ? isDarkMode ? 'bg-blue-800' : 'bg-blue-100' 
                : isDarkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-100'
            }`}
            onClick={() => navigateToLevel(1, [currentPath[0]])}
          >
            <BookOpen className="w-4 h-4" />
            <span className="text-sm">{currentPath[0]}</span>
          </div>
        )}
        {currentPath.length > 0 && currentPath[0] === 'omni' && (
          <div
            className={`flex items-center space-x-2 p-2 rounded cursor-pointer ml-4 ${isDarkMode ? 'text-white' : 'text-black'} ${
              currentLevel === 2 
                ? isDarkMode ? 'bg-pink-800' : 'bg-pink-100' 
                : isDarkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-100'
            }`}
          >
            <Brain className="w-4 h-4" />
            <span className="text-sm">🤖 AI 옴니모드</span>
          </div>
        )}
      </div>
      
      {/* 학습 시간 표시 */}
      <div className="mt-4 pt-4 border-t border-gray-600">
        <div className={`text-sm space-y-2 ${isDarkMode ? 'text-white' : 'text-black'}`}>
          <div className="flex justify-between">
            <span>학습 시간</span>
            <span className="font-mono">
              {Math.floor(studyTime / 60)}:{String(studyTime % 60).padStart(2, '0')}
            </span>
          </div>
          <div className="flex justify-between">
            <span>뽀모도로</span>
            <span>{pomodoroCount}회</span>
          </div>
          <div className="flex justify-between">
            <span>집중도</span>
            <span className={`font-bold ${
              focusScore > 70 ? 'text-green-500' : 
              focusScore > 40 ? 'text-yellow-500' : 'text-red-500'
            }`}>{Math.round(focusScore)}%</span>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <div className={`min-h-screen relative overflow-hidden ${
      isDarkMode 
        ? 'bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900' 
        : 'bg-gradient-to-br from-blue-50 to-purple-50'
    }`}>
      {/* 네비게이션 바 */}
      <div className={`${
        isDarkMode ? 'bg-gray-800/90' : 'bg-white/90'
      } backdrop-blur-md shadow-md p-4 flex items-center justify-between sticky top-0 z-40`}>
        <div className="flex items-center space-x-4">
          <button
            onClick={goHome}
            className="flex items-center space-x-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
          >
            <Home className="w-4 h-4" />
            <span>홈</span>
          </button>
          {currentLevel > 0 && currentPath[0] !== 'omni' && (
            <button
              onClick={goBack}
              className="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
            >
              <ChevronDown className="w-4 h-4 transform rotate-90" />
              <span>뒤로</span>
            </button>
          )}
        </div>
        
        {/* 중앙 상태 표시 */}
        <div className="flex items-center space-x-6">
          <div className="flex items-center space-x-2">
            <Timer className="w-5 h-5 text-blue-500" />
            <span className={`font-mono ${isDarkMode ? 'text-white' : 'text-gray-800'}`}>
              {Math.floor(studyTime / 60)}:{String(studyTime % 60).padStart(2, '0')}
            </span>
          </div>
          <div className="flex items-center space-x-2">
            <Brain className={`w-5 h-5 ${
              focusScore > 70 ? 'text-green-500' : 
              focusScore > 40 ? 'text-yellow-500' : 'text-red-500'
            }`} />
            <span className={isDarkMode ? 'text-white' : 'text-gray-800'}>
              {Math.round(focusScore)}%
            </span>
          </div>
        </div>
        
        {/* 우측 컨트롤 */}
        <div className="flex items-center space-x-4">
          <button
            onClick={() => setShowStats(!showStats)}
            className="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
          >
            <BarChart3 className={`w-5 h-5 ${isDarkMode ? 'text-gray-300' : 'text-gray-600'}`} />
          </button>
          <button
            onClick={() => setSoundEnabled(!soundEnabled)}
            className="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
          >
            <Volume2 className={`w-5 h-5 ${
              soundEnabled 
                ? isDarkMode ? 'text-blue-400' : 'text-blue-600'
                : isDarkMode ? 'text-gray-600' : 'text-gray-400'
            }`} />
          </button>
          <button
            onClick={() => setIsDarkMode(!isDarkMode)}
            className="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
          >
            {isDarkMode 
              ? <Sun className="w-5 h-5 text-yellow-400" />
              : <Moon className="w-5 h-5 text-gray-600" />
            }
          </button>
          <button
            onClick={() => setShowMinimap(!showMinimap)}
            className="flex items-center space-x-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
          >
            <MapPin className="w-4 h-4" />
            <span>미니맵</span>
          </button>
        </div>
      </div>

      {/* 메인 컨텐츠 */}
      <div 
        ref={containerRef}
        className="container mx-auto px-4 py-8 transition-all duration-800 ease-in-out relative"
        style={{
          transform: 'scale(1) translateZ(0)',
          opacity: 1,
          transformOrigin: 'center center',
          filter: 'blur(0px)',
          perspective: '2000px',
          transformStyle: 'preserve-3d',
          willChange: 'transform, opacity, filter'
        }}
      >
        <div className={`transition-all duration-300 ${isAnimating ? 'pointer-events-none' : ''}`}>
          {renderContent()}
        </div>
      </div>

      {/* 미니맵 */}
      {showMinimap && renderMinimap()}

      {/* 통계 오버레이 */}
      {showStats && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
             onClick={() => setShowStats(false)}>
          <div className={`${
            isDarkMode ? 'bg-gray-800' : 'bg-white'
          } rounded-2xl p-8 max-w-2xl w-full mx-4 shadow-2xl`}
               onClick={e => e.stopPropagation()}>
            <h2 className={`text-2xl font-bold mb-6 ${isDarkMode ? 'text-white' : 'text-gray-800'}`}>
              학습 통계
            </h2>
            <div className="grid grid-cols-2 gap-6">
              <div>
                <p className={`text-sm ${isDarkMode ? 'text-gray-400' : 'text-gray-600'}`}>총 학습 시간</p>
                <p className={`text-2xl font-bold ${isDarkMode ? 'text-white' : 'text-gray-800'}`}>
                  {Math.floor(studyTime / 3600)}시간 {Math.floor((studyTime % 3600) / 60)}분
                </p>
              </div>
              <div>
                <p className={`text-sm ${isDarkMode ? 'text-gray-400' : 'text-gray-600'}`}>총 XP</p>
                <p className={`text-2xl font-bold ${isDarkMode ? 'text-white' : 'text-gray-800'}`}>
                  {totalXP}
                </p>
              </div>
              <div>
                <p className={`text-sm ${isDarkMode ? 'text-gray-400' : 'text-gray-600'}`}>평균 집중도</p>
                <p className={`text-2xl font-bold ${isDarkMode ? 'text-white' : 'text-gray-800'}`}>
                  {Math.round(focusScore)}%
                </p>
              </div>
              <div>
                <p className={`text-sm ${isDarkMode ? 'text-gray-400' : 'text-gray-600'}`}>뽀모도로 세션</p>
                <p className={`text-2xl font-bold ${isDarkMode ? 'text-white' : 'text-gray-800'}`}>
                  {pomodoroCount}회
                </p>
              </div>
            </div>
          </div>
        </div>
      )}

      <style jsx>{`
        .container {
          perspective: 2000px;
          transform-style: preserve-3d;
          backface-visibility: hidden;
        }
        
        @keyframes portal {
          0%, 100% {
            opacity: 0.2;
            transform: translate(-50%, -50%) scale(1) rotate(0deg);
          }
          50% {
            opacity: 0.4;
            transform: translate(-50%, -50%) scale(1.5) rotate(180deg);
          }
        }
        
        .min-h-screen::before {
          content: '';
          position: fixed;
          top: 50%;
          left: 50%;
          width: 400px;
          height: 400px;
          background: radial-gradient(
            circle at center,
            rgba(59, 130, 246, 0.4) 0%,
            rgba(147, 51, 234, 0.3) 40%,
            transparent 70%
          );
          transform: translate(-50%, -50%);
          border-radius: 50%;
          z-index: 0;
          animation: portal 8s ease-in-out infinite;
          pointer-events: none;
        }
      `}</style>
    </div>
  );
};

export default MathKingLMS;