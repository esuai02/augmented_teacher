<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

echo '<script>



import React, { useState } from "react";
import { Brain, Link, RefreshCw, Eye, Star, ArrowRight, Check, ChevronLeft } from "lucide-react";

const MathMetacognitionHelper = () => {
  // 상태 관리: problems, method, guide, result 단계
  const [stage, setStage] = useState("problems");
  const [selectedProblem, setSelectedProblem] = useState(null);
  const [selectedMethod, setSelectedMethod] = useState(null);
  const [stepIndex, setStepIndex] = useState(0);
  
  // 수학 공부 중 겪는 문제 상황 목록
  const mathProblems = [
    {
      id: "distraction",
      emoji: "😵",
      title: "집중이 잘 안돼요",
      description: "수학 문제를 풀 때 다른 생각이 자꾸 떠올라요",
      situations: [
        "책상에 앉으면 딴생각이 많이 나요",
        "문제를 읽어도 무슨 뜻인지 잘 이해가 안돼요",
        "계산하다가 자꾸 실수를 해요",
        "문제를 보면 머리가 복잡해져요"
      ],
      recommendedMethod: "eyeShifting"
    },
    {
      id: "understanding",
      emoji: "🤔",
      title: "이해가 어려워요",
      description: "공식은 외웠는데 문제에 적용하기 어려워요",
      situations: [
        "공식은 알지만 왜 그렇게 되는지 모르겠어요",
        "비슷한 문제가 나오면 헷갈려요",
        "선생님 설명은 이해했는데 혼자 풀면 막혀요",
        "내가 아는 것과 연결이 안돼요"
      ],
      recommendedMethod: "schemaConnection"
    },
    {
      id: "forgetting",
      emoji: "😓",
      title: "금방 잊어버려요",
      description: "오늘 배운 내용을 내일이면 까먹어요",
      situations: [
        "배운 내용을 시험 때 기억 못할까봐 걱정돼요",
        "어제 배운 내용을 자꾸 잊어버려요",
        "공부한 내용이 머릿속에서 섞여요",
        "문제 풀 때 공식이 생각나지 않아요"
      ],
      recommendedMethod: "retrievalPractice"
    },
    {
      id: "overwhelmed",
      emoji: "😱",
      title: "양이 너무 많아요",
      description: "시험 범위가 많아서 어떻게 공부해야 할지 모르겠어요",
      situations: [
        "공부할 양이 많아서 어디서부터 시작해야 할지 모르겠어요",
        "한꺼번에 공부하면 머리가 아파요",
        "시험 전날 벼락치기 공부를 자주 해요",
        "오래 앉아있으면 집중력이 떨어져요"
      ],
      recommendedMethod: "distributedLearning"
    }
  ];
  
  // 메타인지 학습 방법 정보
  const studyMethods = {
    eyeShifting: {
      id: "eyeShifting",
      title: "시선돌리기 마법",
      tagline: "순간적으로 바로 집중되는 방법",
      icon: <Eye className="w-12 h-12 text-purple-500" />,
      color: "bg-purple-100",
      borderColor: "border-purple-300",
      textColor: "text-purple-700",
      character: "집중이 마법사",
      description: "시선을 돌려 머릿속을 깨끗하게 정리하는 마법이에요!",
      steps: [
        {
          title: "마법의 순간 만들기",
          description: "문제를 풀기 전, 눈을 감고 3번 크게 숨을 쉬어보세요. 머릿속 잡생각들이 구름처럼 둥둥 떠다니는 모습을 상상해보세요.",
          tip: "깊게 숨을 들이마시고 천천히 내쉬면서 머릿속 구름들이 사라지는 걸 상상해요!"
        },
        {
          title: "중요한 부분만 쏙쏙",
          description: "문제의 중요한 숫자와 기호에 동그라미를 쳐보세요. 문제에서 꼭 필요한 정보만 골라내는 거예요.",
          tip: "필요 없는 정보는 과감히 무시하고 꼭 필요한 숫자들만 모아봐요."
        },
        {
          title: "생각할 때 하늘 보기",
          description: "계산이 필요할 때는 책상 위쪽 빈 공간이나 창밖을 바라보세요. 이렇게 하면 머릿속에 그림을 더 잘 그릴 수 있어요.",
          tip: "어려운 계산을 할 때 고개를 살짝 들어 천장을 바라보면 집중력이 UP!"
        },
        {
          title: "뇌에게 쉬는 시간 주기",
          description: "한 문제를 풀고 나면 10초 동안 눈을 감고 휴식해보세요. 뇌가 다음 문제를 위해 충전하는 시간이에요.",
          tip: "10초 동안 "잘했어, 다음 문제도 잘 풀 수 있어"라고 속으로 말해보세요!"
        }
      ],
      result: "이제 시선돌리기 마법을 배웠어요! 이 마법을 쓰면 문제를 더 정확하게 풀 수 있어요. 다른 생각이 자꾸 떠오를 때마다 이 마법을 사용해보세요!"
    },
    schemaConnection: {
      id: "schemaConnection",
      title: "지식 연결 모험",
      tagline: "이해의 깊이를 더하는 방법",
      icon: <Link className="w-12 h-12 text-blue-500" />,
      color: "bg-blue-100",
      borderColor: "border-blue-300",
      textColor: "text-blue-700",
      character: "연결이 탐험가",
      description: "새로운 지식을 이미 알고 있는 것과 연결하는 모험이에요!",
      steps: [
        {
          title: "지식 지도 펼치기",
          description: "새로운 개념을 배울 때 "이건 내가 아는 어떤 것과 비슷하지?"라고 자신에게 물어보세요.",
          tip: "분수를 배울 때 피자나 케이크 조각을 떠올려보세요!"
        },
        {
          title: "실생활 보물 찾기",
          description: "배운 개념을 일상생활에서 찾아보세요. 예를 들어, 분수는 요리할 때 1/2컵, 1/4컵처럼 사용해요.",
          tip: "수학 개념이 실제 생활에서 어떻게 사용되는지 찾아보는 게임을 해보세요!"
        },
        {
          title: "그림으로 표현하기",
          description: "배운 개념을 그림이나 만화로 그려보세요. 예를 들어, 더하기는 두 친구가 만나는 모습으로 그릴 수 있어요.",
          tip: "내가 가장 좋아하는 캐릭터가 수학 개념을 설명한다면 어떻게 할지 상상해보세요!"
        },
        {
          title: "이야기 만들기",
          description: "수학 문제를 재미있는 이야기로 바꿔보세요. 숫자들이 주인공이 되어 모험을 떠나는 이야기를 만들어보세요.",
          tip: "7 + 5 = 12를 "7이라는 기사가 5라는 용사를 만나 12라는 강한 팀을 이루었어요"라는 이야기로 만들 수 있어요!"
        }
      ],
      result: "이제 지식 연결 모험을 마스터했어요! 새로운 개념이 어렵게 느껴질 때마다 이미 알고 있는 것과 연결해보세요. 그러면 더 쉽게 이해할 수 있어요!"
    },
    retrievalPractice: {
      id: "retrievalPractice",
      title: "기억 꺼내기 게임",
      tagline: "장기기억에 도움이 되는 방법",
      icon: <Brain className="w-12 h-12 text-green-500" />,
      color: "bg-green-100",
      borderColor: "border-green-300",
      textColor: "text-green-700",
      character: "기억이 마술사",
      description: "배운 내용을 퀴즈처럼 떠올려보는 재미있는 게임이에요!",
      steps: [
        {
          title: "책 덮고 생각하기",
          description: "책이나 노트를 읽은 후 덮고, 방금 배운 내용을 스스로 말해보세요. 마치 선생님이 된 것처럼 설명해보세요.",
          tip: "곰인형이나 인형에게 설명하는 것처럼 해보면 더 재미있어요!"
        },
        {
          title: "나만의 퀴즈 만들기",
          description: "배운 내용으로 퀴즈 문제를 만들어보세요. 다음 날 그 퀴즈를 풀어보면 얼마나 기억하고 있는지 알 수 있어요.",
          tip: "퀴즈 쇼 진행자가 된 것처럼 재미있게 문제를 만들어보세요!"
        },
        {
          title: "플래시카드 대결",
          description: "작은 카드에 앞면에는 질문, 뒷면에는 답을 적어보세요. 매일 카드를 섞어서 앞면만 보고 답을 맞혀보세요.",
          tip: "친구나 가족과 함께 플래시카드 대결을 해보세요. 누가 더 많이 맞히는지 경쟁해보세요!"
        },
        {
          title: "가르치기 놀이",
          description: "배운 내용을 친구나 가족에게 가르쳐보세요. 다른 사람에게 설명하면 자신이 얼마나 알고 있는지 확인할 수 있어요.",
          tip: "선생님처럼 칠판이나 종이에 그림을 그리면서 설명해보세요!"
        }
      ],
      result: "이제 기억 꺼내기 게임을 마스터했어요! 이 방법을 꾸준히 하면 시험 때 훨씬 더 많은 내용을 기억할 수 있어요. 매일 조금씩 해보세요!"
    },
    distributedLearning: {
      id: "distributedLearning",
      title: "나누기 학습 전략",
      tagline: "많은 양을 오래 기억하게 하는 방법",
      icon: <RefreshCw className="w-12 h-12 text-orange-500" />,
      color: "bg-orange-100",
      borderColor: "border-orange-300",
      textColor: "text-orange-700",
      character: "시간이 여행자",
      description: "공부를 작은 조각으로 나누어 여러 날에 걸쳐 하는 마법 같은 방법이에요!",
      steps: [
        {
          title: "학습 달력 만들기",
          description: "큰 달력에 시험 날짜를 표시하고, 그전까지 매일 조금씩 공부할 내용을 나눠 적어보세요.",
          tip: "시험 범위를 작은 조각으로 나누어 매일 하나씩 공부하는 계획을 세워보세요!"
        },
        {
          title: "타이머 도전",
          description: "25분 동안 집중해서 공부한 후 5분 휴식하는 방법을 사용해보세요. 이렇게 하면 오래 집중할 수 있어요.",
          tip: "타이머를 모양이 귀여운 것으로 사용하면, 시간 재는 것도 재미있어질 거예요!"
        },
        {
          title: "복습 사이클 만들기",
          description: "오늘 배운 내용은 내일 한 번, 일주일 후 한 번, 한 달 후 한 번 더 복습하세요. 이렇게 하면 오래 기억할 수 있어요.",
          tip: "복습할 때마다 다른 색깔 펜을 사용해서 무지개 노트를 만들어보세요!"
        },
        {
          title: "공부-놀이 교대하기",
          description: "30분 공부 후 좋아하는 활동을 10분 하는 방식으로 번갈아가며 해보세요. 공부가 더 즐거워질 거예요.",
          tip: "좋아하는 활동 목록을 만들어두고, 공부 후 그중 하나를 선택해 보세요!"
        }
      ],
      result: "이제 나누기 학습 전략을 마스터했어요! 이 방법을 사용하면 시험 전날 밤새 공부하지 않아도 더 많은 내용을 기억할 수 있어요. 꾸준히 조금씩 하는 것이 비결이에요!"
    }
  };
  
  // 문제 상황 선택 처리
  const selectProblem = (problem) => {
    setSelectedProblem(problem);
    setSelectedMethod(problem.recommendedMethod);
    setStage("method");
  };
  
  // 다음 단계 진행
  const nextStep = () => {
    if (stepIndex < studyMethods[selectedMethod].steps.length - 1) {
      setStepIndex(stepIndex + 1);
    } else {
      setStage("result");
    }
  };
  
  // 이전 단계로 돌아가기
  const prevStep = () => {
    if (stepIndex > 0) {
      setStepIndex(stepIndex - 1);
    } else {
      setStage("method");
    }
  };
  
  // 처음으로 돌아가기
  const restart = () => {
    setStage("problems");
    setSelectedProblem(null);
    setSelectedMethod(null);
    setStepIndex(0);
  };
  
  // 문제 상황 선택 화면 렌더링
  const renderProblems = () => (
    <div className="max-w-lg mx-auto bg-white rounded-xl shadow-lg p-6">
      <div className="text-center mb-6">
        <h1 className="text-2xl font-bold text-gray-800">수학 공부 도우미</h1>
        <p className="text-gray-600 mt-2">내가 겪고 있는 상황을 선택해봐요!</p>
      </div>
      
      <div className="grid grid-cols-1 gap-4">
        {mathProblems.map((problem) => (
          <button
            key={problem.id}
            className="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-all duration-200 flex items-center text-left"
            onClick={() => selectProblem(problem)}
          >
            <div className="text-4xl mr-4">{problem.emoji}</div>
            <div className="flex-1">
              <h3 className="font-bold text-lg text-gray-800">{problem.title}</h3>
              <p className="text-gray-600 text-sm">{problem.description}</p>
            </div>
            <ArrowRight className="text-gray-400" />
          </button>
        ))}
      </div>
    </div>
  );
  
  // 방법 소개 화면 렌더링
  const renderMethod = () => {
    const method = studyMethods[selectedMethod];
    
    return (
      <div className={`max-w-lg mx-auto ${method.color} rounded-xl shadow-lg p-6 border-2 ${method.borderColor}`}>
        <div className="flex items-center mb-2">
          <button 
            className="mr-3 text-gray-500 hover:text-gray-700 flex items-center"
            onClick={restart}
          >
            <ChevronLeft className="w-5 h-5 mr-1" />
            <span>처음으로</span>
          </button>
        </div>
        
        <div className="text-center mb-6">
          <div className={`w-20 h-20 rounded-full mx-auto flex items-center justify-center mb-3 ${method.color} border-2 ${method.borderColor}`}>
            {method.icon}
          </div>
          <h2 className={`text-2xl font-bold ${method.textColor}`}>{method.title}</h2>
          <p className="text-gray-600 mt-1">{method.tagline}</p>
        </div>
        
        <div className="mb-6">
          <div className={`p-4 rounded-lg border ${method.borderColor} bg-white`}>
            <div className="flex items-center mb-2">
              <Star className={`w-5 h-5 ${method.textColor} mr-2`} />
              <h3 className="font-semibold text-gray-800">이 방법은 이럴 때 좋아요!</h3>
            </div>
            <ul className="ml-7 list-disc space-y-1">
              {selectedProblem.situations.map((situation, index) => (
                <li key={index} className="text-gray-700">{situation}</li>
              ))}
            </ul>
          </div>
        </div>
        
        <div className="text-center mb-6">
          <p className="text-gray-700">{method.character}와 함께 <span className="font-bold">{method.title}</span> 방법을 배워볼까요?</p>
          <p className="text-gray-500 text-sm mt-1">{method.description}</p>
        </div>
        
        <button 
          className={`w-full py-3 ${method.textColor} bg-white rounded-md border-2 ${method.borderColor} hover:bg-gray-50 transition-colors duration-200 font-bold`}
          onClick={() => setStage("guide")}
        >
          시작하기
        </button>
      </div>
    );
  };
  
  // 단계별 가이드 화면 렌더링
  const renderGuide = () => {
    const method = studyMethods[selectedMethod];
    const step = method.steps[stepIndex];
    
    return (
      <div className={`max-w-lg mx-auto ${method.color} rounded-xl shadow-lg p-6 border-2 ${method.borderColor}`}>
        <div className="flex items-center justify-between mb-4">
          <button 
            className="text-gray-500 hover:text-gray-700 flex items-center"
            onClick={prevStep}
          >
            <ChevronLeft className="w-5 h-5 mr-1" />
            <span>{stepIndex === 0 ? "뒤로" : "이전"}</span>
          </button>
          <span className="text-gray-500 text-sm">단계 {stepIndex + 1}/{method.steps.length}</span>
        </div>
        
        <div className="mb-4">
          <div className="w-full bg-gray-200 rounded-full h-3">
            <div 
              className={`h-3 rounded-full transition-all duration-500 ${method.textColor}`}
              style={{width: `${((stepIndex + 1) / method.steps.length) * 100}%`, backgroundColor: method.textColor, opacity: 0.7}}
            ></div>
          </div>
        </div>
        
        <div className="text-center mb-4">
          <h2 className={`text-xl font-bold ${method.textColor}`}>{step.title}</h2>
        </div>
        
        <div className="mb-6">
          <div className="bg-white rounded-lg p-5 border-2 border-gray-200">
            <p className="text-gray-800 mb-4">{step.description}</p>
            
            {/* 예시 이미지 영역 */}
            <div className={`${method.color} rounded-lg h-40 flex items-center justify-center mb-4 border ${method.borderColor}`}>
              <p className="text-gray-500">여기에 재미있는 그림이 들어갈 거예요!</p>
            </div>
            
            <div className={`rounded-lg p-3 flex bg-yellow-50 border-l-4 border-yellow-300`}>
              <Star className="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0" />
              <p className="text-sm text-gray-700">
                <span className="font-bold">꿀팁:</span> {step.tip}
              </p>
            </div>
          </div>
        </div>
        
        <button 
          className={`w-full py-3 text-white rounded-md transition-colors duration-200 font-bold flex items-center justify-center`}
          style={{backgroundColor: method.textColor}}
          onClick={nextStep}
        >
          {stepIndex < method.steps.length - 1 ? (
            <>다음 단계<ArrowRight className="ml-2 w-4 h-4" /></>
          ) : (
            <>완료하기<Check className="ml-2 w-4 h-4" /></>
          )}
        </button>
      </div>
    );
  };
  
  // 결과 화면 렌더링
  const renderResult = () => {
    const method = studyMethods[selectedMethod];
    
    return (
      <div className={`max-w-lg mx-auto ${method.color} rounded-xl shadow-lg p-6 border-2 ${method.borderColor}`}>
        <div className="text-center mb-6">
          <div className={`w-20 h-20 rounded-full mx-auto flex items-center justify-center mb-3 bg-white border-2 ${method.borderColor}`}>
            {method.icon}
          </div>
          <h2 className={`text-2xl font-bold ${method.textColor}`}>축하해요! 🎉</h2>
          <p className={`${method.textColor} font-semibold mt-1`}>{method.title} 마스터!</p>
        </div>
        
        <div className="bg-white rounded-lg p-5 border-2 border-gray-200 mb-6">
          <p className="text-gray-800">{method.result}</p>
        </div>
        
        <div className="flex flex-col space-y-3">
          <button 
            className={`py-3 ${method.textColor} bg-white rounded-md border-2 ${method.borderColor} hover:bg-gray-50 transition-colors duration-200 font-bold`}
            onClick={() => {
              setStage("guide");
              setStepIndex(0);
            }}
          >
            다시 복습하기
          </button>
          
          <button 
            className={`py-3 text-white rounded-md transition-colors duration-200 font-bold`}
            style={{backgroundColor: method.textColor}}
            onClick={restart}
          >
            다른 방법 배우러 가기
          </button>
        </div>
      </div>
    );
  };
  
  // 현재 단계에 따라 UI 렌더링
  const renderCurrentStage = () => {
    switch(stage) {
      case "problems":
        return renderProblems();
      case "method":
        return renderMethod();
      case "guide":
        return renderGuide();
      case "result":
        return renderResult();
      default:
        return renderProblems();
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 py-8 px-4" style={{backgroundImage: "radial-gradient(#e5e7eb 1px, transparent 1px)", backgroundSize: "20px 20px"}}>
      {renderCurrentStage()}
    </div>
  );
};

export default MathMetacognitionHelper;


</script>';
?>