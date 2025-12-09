import React, { useState, useEffect, useRef } from 'react';
import { Brain, Sparkles, Lightbulb, Target, TrendingUp, BookOpen, ChevronRight, Zap, MessageCircle, Star, Award, Compass, Map, Rocket } from 'lucide-react';

const ReflectionWelcomeSession = () => {
  const [currentScene, setCurrentScene] = useState(0);
  const [typedText, setTypedText] = useState('');
  const [showButton, setShowButton] = useState(false);
  const [userName, setUserName] = useState('');
  const [userInput, setUserInput] = useState('');
  const [thoughtBubbles, setThoughtBubbles] = useState([]);
  const [selectedPath, setSelectedPath] = useState(null);
  const [strategyCards, setStrategyCards] = useState([]);
  const [showMetaChecklist, setShowMetaChecklist] = useState(false);
  const progressBarRef = useRef(null);

  // 타이핑 효과 함수
  const typeText = (text, callback) => {
    let index = 0;
    setTypedText('');
    const timer = setInterval(() => {
      if (index < text.length) {
        setTypedText(prev => prev + text[index]);
        index++;
      } else {
        clearInterval(timer);
        if (callback) callback();
      }
    }, 40);
    return timer;
  };

  // Scene 컨텐츠 정의
  const scenes = [
    // Scene 0: 오프닝
    {
      id: 0,
      content: "안녕! 나는 너의 사고력 트레이너야. 🧠\n\n오늘부터 너와 함께 '생각하는 방법'을 훈련할 거야.\n정답보다 중요한 건... '어떻게 거기까지 갔는가'거든.",
      animation: 'fadeIn',
      bgGradient: 'from-purple-900 via-indigo-900 to-blue-900'
    },
    // Scene 1: 이름 묻기
    {
      id: 1,
      content: "먼저 네 이름을 알려줄래?\n\n우리가 함께 만들어갈 사고력 포트폴리오에\n네 이름을 새겨넣고 싶어.",
      animation: 'slideUp',
      bgGradient: 'from-indigo-900 via-purple-900 to-pink-900',
      needsInput: true
    },
    // Scene 2: 질문 던지기
    {
      id: 2,
      content: "{name}! 좋은 이름이야.\n\n자, 여기 간단한 질문이 있어:\n'왜 공부를 잘하는 학생들은 문제를 빨리 풀까?'\n\n네 생각을 자유롭게 말해봐.",
      animation: 'pulse',
      bgGradient: 'from-purple-900 to-indigo-900',
      needsThought: true
    },
    // Scene 3: 사고 과정 시각화
    {
      id: 3,
      content: "훌륭해! 방금 네가 보여준 게 바로 '사고 과정'이야.\n\n🧠 사고력 중심 학습에서는\n이런 생각의 흐름을 포착하고, 정리하고, 발전시켜.",
      animation: 'thoughtFlow',
      bgGradient: 'from-indigo-900 to-blue-900'
    },
    // Scene 4: W-X-S-P-E-R-T-A 소개
    {
      id: 4,
      content: "우리의 학습 시스템은 8개의 지능으로 구성돼 있어.\n\n각각이 너의 사고력을 다른 방향으로 확장시켜줄 거야.",
      animation: 'gridReveal',
      bgGradient: 'from-blue-900 via-purple-900 to-indigo-900',
      showSystem: true
    },
    // Scene 5: 전략 카드 소개
    {
      id: 5,
      content: "매주 새로운 사고 전략을 배우고\n네 전략 포트폴리오를 확장해나갈 거야.\n\n이미 준비된 전략들을 살펴볼까?",
      animation: 'cardFlip',
      bgGradient: 'from-purple-900 to-pink-900',
      showStrategies: true
    },
    // Scene 6: 메타인지 체크리스트
    {
      id: 6,
      content: "그리고 매주 금요일엔\n네 사고 과정을 점검하는 시간을 가질 거야.\n\n메타인지 체크리스트로 스스로를 돌아보는 거지.",
      animation: 'checklistReveal',
      bgGradient: 'from-indigo-900 via-blue-900 to-purple-900',
      showChecklist: true
    },
    // Scene 7: 성장 경로
    {
      id: 7,
      content: "전이 성공률 80%, 설명 점수 4/5...\n\n이 숫자들이 네 사고력의 성장을 보여줄 거야.\n함께 J-커브를 그려보자!",
      animation: 'growthChart',
      bgGradient: 'from-blue-900 to-indigo-900'
    },
    // Scene 8: 마무리
    {
      id: 8,
      content: "{name}, 준비됐어?\n\n오늘부터 시작되는 사고력 여정.\n네가 생각하는 방법이 완전히 바뀔 거야.\n\n🧠 Let's Think Different!",
      animation: 'finale',
      bgGradient: 'from-purple-900 via-pink-900 to-indigo-900',
      showStart: true
    }
  ];

  useEffect(() => {
    const scene = scenes[currentScene];
    let content = scene.content;
    if (userName && content.includes('{name}')) {
      content = content.replace('{name}', userName);
    }
    
    const timer = typeText(content, () => {
      setTimeout(() => setShowButton(true), 500);
    });

    return () => clearInterval(timer);
  }, [currentScene, userName]);

  const handleNext = () => {
    if (scenes[currentScene].needsInput && !userName) {
      setUserName(userInput || '학습자');
    }
    if (scenes[currentScene].needsThought) {
      setThoughtBubbles([userInput]);
    }
    
    setShowButton(false);
    setUserInput('');
    setCurrentScene(prev => Math.min(prev + 1, scenes.length - 1));
  };

  const renderSystemGrid = () => (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 animate-fadeIn">
      {[
        { icon: <Compass className="w-6 h-6" />, title: "W", desc: "세계관 정렬" },
        { icon: <Brain className="w-6 h-6" />, title: "X", desc: "문맥 지능" },
        { icon: <Map className="w-6 h-6" />, title: "S", desc: "구조 지능" },
        { icon: <Target className="w-6 h-6" />, title: "P", desc: "절차 지능" },
        { icon: <Zap className="w-6 h-6" />, title: "E", desc: "실행 지능" },
        { icon: <MessageCircle className="w-6 h-6" />, title: "R", desc: "성찰 지능" },
        { icon: <TrendingUp className="w-6 h-6" />, title: "T", desc: "트래픽 지능" },
        { icon: <Rocket className="w-6 h-6" />, title: "A", desc: "추상화 지능" }
      ].map((item, idx) => (
        <div 
          key={idx}
          className="bg-white/10 backdrop-blur-md rounded-xl p-4 transform hover:scale-105 transition-all duration-300"
          style={{ animationDelay: `${idx * 100}ms` }}
        >
          <div className="text-yellow-300 mb-2">{item.icon}</div>
          <div className="text-2xl font-bold text-white mb-1">{item.title}</div>
          <div className="text-xs text-white/70">{item.desc}</div>
        </div>
      ))}
    </div>
  );

  const renderStrategyCards = () => (
    <div className="flex gap-4 mt-8 overflow-x-auto pb-4">
      {[
        { title: "거꾸로 추론", desc: "결과에서 시작으로", level: "Basic" },
        { title: "조건 분해", desc: "복잡함을 단순하게", level: "Basic" },
        { title: "패턴 인식", desc: "규칙성 발견하기", level: "Advanced" },
        { title: "전이 실험", desc: "다른 문제에 적용", level: "Master" }
      ].map((card, idx) => (
        <div
          key={idx}
          className="min-w-[200px] bg-gradient-to-br from-purple-600/30 to-pink-600/30 backdrop-blur-md rounded-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer"
          style={{ animationDelay: `${idx * 150}ms` }}
          onClick={() => setStrategyCards([...strategyCards, card.title])}
        >
          <div className="text-yellow-400 text-sm mb-2">{card.level}</div>
          <div className="text-white font-bold text-lg mb-2">{card.title}</div>
          <div className="text-white/70 text-sm">{card.desc}</div>
          <div className="mt-4 flex justify-center">
            <Lightbulb className="w-8 h-8 text-yellow-300 animate-pulse" />
          </div>
        </div>
      ))}
    </div>
  );

  const renderMetaChecklist = () => (
    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 mt-8 animate-slideUp">
      <h3 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
        <BookOpen className="w-6 h-6 text-yellow-300" />
        메타인지 체크리스트
      </h3>
      <div className="space-y-3">
        {[
          "오늘 배운 전략을 설명할 수 있나요?",
          "실패한 문제의 원인을 알고 있나요?",
          "다른 문제에 적용할 수 있나요?",
          "더 나은 방법을 생각해봤나요?"
        ].map((item, idx) => (
          <label key={idx} className="flex items-center gap-3 text-white/90 cursor-pointer hover:text-white transition-colors">
            <input type="checkbox" className="w-5 h-5 rounded border-2 border-white/30 bg-white/10" />
            <span>{item}</span>
          </label>
        ))}
      </div>
    </div>
  );

  const currentSceneData = scenes[currentScene];

  return (
    <div className={`min-h-screen bg-gradient-to-br ${currentSceneData.bgGradient} relative overflow-hidden transition-all duration-1000`}>
      {/* 배경 애니메이션 */}
      <div className="absolute inset-0">
        {[...Array(20)].map((_, i) => (
          <div
            key={i}
            className="absolute animate-float"
            style={{
              left: `${Math.random() * 100}%`,
              top: `${Math.random() * 100}%`,
              animationDelay: `${Math.random() * 5}s`,
              animationDuration: `${10 + Math.random() * 10}s`
            }}
          >
            <Sparkles className="w-4 h-4 text-white/20" />
          </div>
        ))}
      </div>

      {/* 진행 바 */}
      <div className="absolute top-0 left-0 right-0 h-1 bg-white/10">
        <div 
          className="h-full bg-gradient-to-r from-yellow-400 to-pink-400 transition-all duration-500"
          style={{ width: `${((currentScene + 1) / scenes.length) * 100}%` }}
        />
      </div>

      {/* 메인 컨텐츠 */}
      <div className="relative z-10 min-h-screen flex flex-col items-center justify-center p-8">
        <div className="max-w-4xl w-full">
          {/* 헤더 아이콘 */}
          <div className="flex justify-center mb-8">
            <div className="relative">
              <Brain className="w-20 h-20 text-yellow-300 animate-pulse" />
              <div className="absolute -inset-4 bg-yellow-300/20 rounded-full blur-xl animate-pulse" />
            </div>
          </div>

          {/* 타이핑 텍스트 */}
          <div className="bg-white/5 backdrop-blur-md rounded-2xl p-8 mb-8 border border-white/10">
            <p className="text-xl md:text-2xl text-white leading-relaxed whitespace-pre-line">
              {typedText}
              <span className="inline-block w-1 h-6 bg-yellow-300 ml-1 animate-blink" />
            </p>
          </div>

          {/* 입력 필드 */}
          {currentSceneData.needsInput && showButton && (
            <div className="animate-slideUp">
              <input
                type="text"
                value={userInput}
                onChange={(e) => setUserInput(e.target.value)}
                onKeyPress={(e) => e.key === 'Enter' && handleNext()}
                placeholder="네 이름을 입력해줘..."
                className="w-full px-6 py-4 bg-white/10 backdrop-blur-md rounded-xl text-white placeholder-white/50 border border-white/20 focus:outline-none focus:border-yellow-300 transition-colors"
                autoFocus
              />
            </div>
          )}

          {/* 사고 입력 필드 */}
          {currentSceneData.needsThought && showButton && (
            <div className="animate-slideUp">
              <textarea
                value={userInput}
                onChange={(e) => setUserInput(e.target.value)}
                placeholder="네 생각을 자유롭게 적어봐..."
                className="w-full px-6 py-4 bg-white/10 backdrop-blur-md rounded-xl text-white placeholder-white/50 border border-white/20 focus:outline-none focus:border-yellow-300 transition-colors h-32 resize-none"
                autoFocus
              />
            </div>
          )}

          {/* 사고 버블 표시 */}
          {thoughtBubbles.length > 0 && currentScene === 3 && (
            <div className="mt-6 space-y-3 animate-fadeIn">
              {thoughtBubbles.map((thought, idx) => (
                <div key={idx} className="bg-gradient-to-r from-purple-600/30 to-pink-600/30 backdrop-blur-md rounded-xl p-4 border border-white/20">
                  <div className="flex items-start gap-3">
                    <MessageCircle className="w-5 h-5 text-yellow-300 mt-1" />
                    <p className="text-white/90">{thought}</p>
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* 시스템 그리드 */}
          {currentSceneData.showSystem && renderSystemGrid()}

          {/* 전략 카드 */}
          {currentSceneData.showStrategies && renderStrategyCards()}

          {/* 메타인지 체크리스트 */}
          {currentSceneData.showChecklist && renderMetaChecklist()}

          {/* 다음 버튼 */}
          {showButton && !currentSceneData.needsInput && !currentSceneData.needsThought && (
            <div className="flex justify-center mt-8 animate-fadeIn">
              {currentSceneData.showStart ? (
                <button
                  onClick={handleNext}
                  className="group relative px-12 py-4 bg-gradient-to-r from-yellow-400 to-pink-400 rounded-full text-gray-900 font-bold text-lg hover:scale-105 transform transition-all duration-300 shadow-xl"
                >
                  <span className="flex items-center gap-3">
                    시작하기
                    <Rocket className="w-6 h-6 group-hover:translate-x-1 transition-transform" />
                  </span>
                  <div className="absolute -inset-1 bg-gradient-to-r from-yellow-400 to-pink-400 rounded-full blur-md opacity-50 group-hover:opacity-75 transition-opacity" />
                </button>
              ) : (
                <button
                  onClick={handleNext}
                  className="group px-8 py-3 bg-white/10 backdrop-blur-md rounded-full text-white border border-white/20 hover:bg-white/20 transition-all duration-300 flex items-center gap-2"
                >
                  계속하기
                  <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                </button>
              )}
            </div>
          )}

          {/* 입력 후 다음 버튼 */}
          {showButton && (currentSceneData.needsInput || currentSceneData.needsThought) && userInput && (
            <div className="flex justify-center mt-4 animate-fadeIn">
              <button
                onClick={handleNext}
                className="group px-8 py-3 bg-gradient-to-r from-yellow-400 to-pink-400 rounded-full text-gray-900 font-bold hover:scale-105 transform transition-all duration-300"
              >
                <span className="flex items-center gap-2">
                  다음
                  <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                </span>
              </button>
            </div>
          )}
        </div>
      </div>

      <style jsx>{`
        @keyframes float {
          0%, 100% { transform: translateY(0) rotate(0deg); }
          50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        @keyframes blink {
          0%, 50% { opacity: 1; }
          51%, 100% { opacity: 0; }
        }
        
        @keyframes slideUp {
          from { 
            opacity: 0;
            transform: translateY(20px);
          }
          to { 
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        
        .animate-float {
          animation: float 10s ease-in-out infinite;
        }
        
        .animate-blink {
          animation: blink 1s infinite;
        }
        
        .animate-slideUp {
          animation: slideUp 0.5s ease-out;
        }
        
        .animate-fadeIn {
          animation: fadeIn 0.5s ease-out;
        }
      `}</style>
    </div>
  );
};

export default ReflectionWelcomeSession;