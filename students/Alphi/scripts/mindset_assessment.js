// 성장형 마인드셋 자가진단 스크립트
document.addEventListener('DOMContentLoaded', function() {
  const app = document.getElementById('mindset-assessment-app');
  if (!app) return;

  let currentPage = 'intro';
  let answers = {};
  let userName = '';

  const questions = [
    {
      id: 1,
      text: '새로운 개념이나 기술을 배우는 것은 나에게 즐거운 일이다.',
      category: '학습태도'
    },
    {
      id: 2,
      text: '실패는 성장의 기회라고 생각한다.',
      category: '실패에 대한 태도'
    },
    {
      id: 3,
      text: '나의 능력은 시간과 노력에 따라 변할 수 있다.',
      category: '능력에 대한 신념'
    },
    {
      id: 4,
      text: '도전적인 일을 회피하기보다 적극적으로 맞서는 편이다.',
      category: '도전에 대한 태도'
    },
    {
      id: 5,
      text: '다른 사람의 성공이 나에게 영감을 준다.',
      category: '타인의 성공에 대한 관점'
    },
    {
      id: 6,
      text: '비판을 받으면 성장의 기회로 삼는다.',
      category: '피드백 수용성'
    },
    {
      id: 7,
      text: '어려운 문제에 직면했을 때 쉽게 포기하지 않는다.',
      category: '끈기'
    },
    {
      id: 8,
      text: '내 지능과 재능은 고정되어 있지 않고 발전할 수 있다.',
      category: '능력에 대한 신념'
    },
    {
      id: 9,
      text: '실수를 하면 부끄러워하기보다는 무엇을 배울 수 있는지 생각한다.',
      category: '실패에 대한 태도'
    },
    {
      id: 10,
      text: '노력하는 과정 자체를 가치 있게 생각한다.',
      category: '과정 중시'
    }
  ];

  function handleAnswer(questionId, value) {
    answers[questionId] = value;
    renderApp();
  }

  function calculateScore() {
    let total = 0;
    Object.values(answers).forEach(value => {
      total += value;
    });
    return total;
  }

  function getResultFeedback(score) {
    const maxScore = questions.length * 5;
    const percentage = (score / maxScore) * 100;
    
    if (percentage >= 80) {
      return {
        title: '강한 성장형 마인드셋',
        description: '당신은 강한 성장형 마인드셋을 가지고 있습니다. 도전, 실패, 노력의 가치를 잘 이해하고 있으며 지속적인 성장을 추구합니다.',
        tips: [
          '다른 사람들이 성장형 마인드셋을 개발할 수 있도록 돕는 멘토가 되어보세요.',
          '더 어려운 도전을 찾아 자신의 한계를 계속 확장해 보세요.'
        ]
      };
    } else if (percentage >= 60) {
      return {
        title: '성장형 마인드셋 성향',
        description: '당신은 대체로 성장형 마인드셋을 가지고 있지만, 일부 영역에서는 고정형 사고방식이 나타납니다.',
        tips: [
          '낮은 점수를 받은 영역에 초점을 맞추어 개선해 보세요.',
          '일상에서 "아직"이라는 단어를 더 자주 사용해 보세요. "나는 할 수 없어"가 아니라 "나는 아직 할 수 없어"로 생각하면 가능성이 열립니다.'
        ]
      };
    } else if (percentage >= 40) {
      return {
        title: '혼합형 마인드셋',
        description: '당신은 성장형과 고정형 마인드셋이 혼합되어 있습니다. 상황에 따라 다른 마인드셋을 보입니다.',
        tips: [
          '실패나 비판을 받을 때 자신의 반응을 의식적으로 관찰해 보세요.',
          '성장 기회로 볼 수 있는 상황들을 일기에 기록해 보세요.',
          '성장형 마인드셋 관련 서적이나 강연을 찾아보세요.'
        ]
      };
    } else {
      return {
        title: '고정형 마인드셋 성향',
        description: '당신은 고정형 마인드셋 성향이 강합니다. 능력이나 지능이 고정되어 있다고 생각하는 경향이 있습니다.',
        tips: [
          '뇌의 가소성에 대해 더 알아보세요. 뇌는 계속해서 변화하고 발전할 수 있습니다.',
          '작은 도전부터 시작하여 점진적으로 어려운 과제에 도전해 보세요.',
          '실패를 배움의 과정으로 재해석하는 연습을 해보세요.'
        ]
      };
    }
  }

  function renderIntro() {
    return `
      <div class="flex flex-col items-center p-6 bg-white rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">성장형 마인드셋 자가진단</h1>
        <p class="text-gray-600 mb-6 text-center">
          성장형 마인드셋은 능력과 지능이 노력과 학습을 <br>
          통해 발전할 수 있다는 단순/확고한 믿음을 뜻합니다.<br><br>
          자가진단을 통해 자신의 마인드셋 성향을 알아보세요.
        </p>
         <div class="mb-4 w-full">
   
          <input style="display:none;"
            type="text" 
            id="name-input"
            value="${userName}" 
            class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline"
          />
        </div>
        <button 
          id="start-button"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
        >
          시작하기
        </button>
      </div>
    `;
  }

  function renderQuestions() {
    let questionsHtml = '';
    
    questions.forEach(q => {
      const userAnswer = answers[q.id] || 0;
      
      let ratingButtons = '';
      for (let i = 1; i <= 5; i++) {
        const activeClass = userAnswer === i ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700';
        ratingButtons += `
          <button
            class="w-10 h-10 rounded-full focus:outline-none flex items-center justify-center ${activeClass} answer-button"
            data-question="${q.id}" 
            data-value="${i}"
          >
            ${i}
          </button>
        `;
      }
      
      questionsHtml += `
        <div class="mb-6 border-b pb-4">
          <div class="flex items-start">
            <span class="font-bold mr-2">${q.id}.</span>
            <div>
              <p class="font-medium text-gray-800">${q.text}</p>
              <p class="text-sm text-gray-500">${q.category}</p>
              <div class="mt-2 flex items-center gap-1">
                  <div class="ml-4 flex w-full justify-between text-xs text-gray-500">
                  <span>전혀 그렇지 않다</span>
                </div>
                ${ratingButtons}
                <div class="ml-4 flex w-full justify-between text-xs text-gray-500">
                  <span>매우 그렇다</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    const answeredCount = Object.keys(answers).length;
    const isComplete = answeredCount === questions.length;
    const buttonClass = isComplete 
      ? 'bg-blue-500 hover:bg-blue-700 text-white' 
      : 'bg-gray-300 text-gray-700 cursor-not-allowed';
      
    return `
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">성장형 마인드셋 자가진단</h1>
        <p class="text-gray-600 mb-6">
          각 문항에 대해 1(전혀 그렇지 않다)부터 5(매우 그렇다)까지 해당하는 점수를 선택해주세요.
        </p>
        
        ${questionsHtml}

        <button 
          id="results-button"
          class="mt-4 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline ${buttonClass}"
          ${isComplete ? '' : 'disabled'}
        >
          ${isComplete ? '결과 보기' : `${answeredCount}/${questions.length} 문항 완료`}
        </button>
      </div>
    `;
  }

  function renderResults() {
    const score = calculateScore();
    const feedback = getResultFeedback(score);
    const maxScore = questions.length * 5;
    const percentage = (score / maxScore) * 100;
    
    let tipsHtml = '';
    feedback.tips.forEach(tip => {
      tipsHtml += `<li class="text-gray-700">${tip}</li>`;
    });
    
    return `
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">자가진단 결과</h1>
        ${userName ? `<p class="text-lg text-gray-600 mb-4">${userName}님의 성장형 마인드셋 분석</p>` : ''}
        
        <div class="mb-6">
          <div class="flex justify-between mb-1">
            <span class="text-gray-700">점수: ${score}/${maxScore}</span>
            <span class="text-gray-700">${percentage.toFixed(1)}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-4">
            <div 
              class="bg-blue-500 h-4 rounded-full" 
              style="width: ${percentage}%"
            ></div>
          </div>
        </div>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
          <h2 class="text-xl font-bold text-gray-800 mb-2">${feedback.title}</h2>
          <p class="text-gray-700 mb-4">${feedback.description}</p>
        </div>
        
        <div>
          <h3 class="text-lg font-bold text-gray-800 mb-2">추천 사항:</h3>
          <ul class="list-disc pl-5 space-y-1">
            ${tipsHtml}
          </ul>
        </div>
        
        <div class="mt-8 flex space-x-4">
          <button 
            id="retake-button"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
          >
            다시 진단하기
          </button>
          <button 
            id="restart-button"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
          >
            처음으로
          </button>
        </div>
      </div>
    `;
  }

  function renderApp() {
    let content = '';
    
    if (currentPage === 'intro') {
      content = renderIntro();
    } else if (currentPage === 'questions') {
      content = renderQuestions();
    } else if (currentPage === 'results') {
      content = renderResults();
    }
    
    app.innerHTML = content;
    
    // 이벤트 리스너 추가
    attachEventListeners();
  }

  function attachEventListeners() {
    if (currentPage === 'intro') {
      const startButton = document.getElementById('start-button');
      const nameInput = document.getElementById('name-input');
      
      startButton.addEventListener('click', () => {
        userName = nameInput.value.trim();
        currentPage = 'questions';
        renderApp();
      });
    } 
    else if (currentPage === 'questions') {
      const answerButtons = document.querySelectorAll('.answer-button');
      const resultsButton = document.getElementById('results-button');
      
      answerButtons.forEach(button => {
        button.addEventListener('click', () => {
          const questionId = parseInt(button.getAttribute('data-question'));
          const value = parseInt(button.getAttribute('data-value'));
          handleAnswer(questionId, value);
        });
      });
      
      if (resultsButton && !resultsButton.disabled) {
        resultsButton.addEventListener('click', () => {
          currentPage = 'results';
          renderApp();
        });
      }
    } 
    else if (currentPage === 'results') {
      const retakeButton = document.getElementById('retake-button');
      const restartButton = document.getElementById('restart-button');
      
      retakeButton.addEventListener('click', () => {
        answers = {};
        currentPage = 'questions';
        renderApp();
      });
      
      restartButton.addEventListener('click', () => {
        answers = {};
        userName = '';
        currentPage = 'intro';
        renderApp();
      });
    }
  }

  // 초기 렌더링
  renderApp();
}); 