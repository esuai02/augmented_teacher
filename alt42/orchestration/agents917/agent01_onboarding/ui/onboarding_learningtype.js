/**
 * Learning Assessment Type JavaScript
 * File: ui/onboarding_learningtype.js
 * Extracted from onboarding_learningtype.php for better maintainability
 */

// Note: questions and currentUserId are passed from PHP
// const questions = ...; // injected from PHP
// const currentUserId = ...; // injected from PHP

// State management
let currentQuestion = -1;
let answers = {};
let isTyping = false;
let started = false;
let isComplete = false;

// DOM elements
const welcomeScreen = document.getElementById('welcomeScreen');
const questionScreen = document.getElementById('questionScreen');
const resultsScreen = document.getElementById('resultsScreen');
const welcomeText = document.getElementById('welcomeText');
const typingCursor = document.getElementById('typingCursor');
const startButtonContainer = document.getElementById('startButtonContainer');
const questionText = document.getElementById('questionText');
const questionCursor = document.getElementById('questionCursor');
const optionsContainer = document.getElementById('optionsContainer');
const progressText = document.getElementById('progressText');
const progressFill = document.getElementById('progressFill');

// Initialize
window.addEventListener('DOMContentLoaded', function() {
    console.log('Learning Assessment initialized');
    console.log('Current User ID:', window.currentUserId || currentUserId);

    showWelcomeMessage();

    document.getElementById('startBtn').addEventListener('click', startAssessment);
    document.getElementById('printBtn').addEventListener('click', function() {
        window.print();
    });
    document.getElementById('restartBtn').addEventListener('click', restartAssessment);
});

/**
 * Typing animation function
 * @param {HTMLElement} element - Element to type into
 * @param {string} text - Text to type
 * @param {Function} callback - Callback after typing completes
 */
function typeText(element, text, callback) {
    let index = 0;
    isTyping = true;
    element.textContent = '';

    const cursor = element === welcomeText ? typingCursor : questionCursor;
    cursor.classList.remove('hidden');

    const timer = setInterval(function() {
        if (index <= text.length) {
            element.textContent = text.slice(0, index);
            index++;
        } else {
            clearInterval(timer);
            isTyping = false;
            cursor.classList.add('hidden');
            if (callback) {
                setTimeout(callback, 300);
            }
        }
    }, 30);
}

/**
 * Show welcome message
 */
function showWelcomeMessage() {
    const welcomeMessage = "안녕하세요,\n카이스트 터치수학에 오신 것을 환영합니다.\n평상시 수학공부 장면들을 떠올리며 다음 내용들에 답해주세요.\n몇 가지 질문을 통해 학습 스타일을 파악해보겠습니다.";

    typeText(welcomeText, welcomeMessage, function() {
        startButtonContainer.classList.remove('hidden');
        startButtonContainer.style.animation = 'fadeIn 0.5s ease-out';
    });
}

/**
 * Start assessment
 */
function startAssessment() {
    started = true;
    currentQuestion = 0;
    welcomeScreen.classList.add('hidden');
    questionScreen.classList.remove('hidden');
    showQuestion();
}

/**
 * Show question
 */
function showQuestion() {
    if (currentQuestion >= questions.length) {
        showResults();
        return;
    }

    const question = questions[currentQuestion];

    // Update progress
    progressText.textContent = `${currentQuestion + 1} / ${questions.length}`;
    progressFill.style.width = `${((currentQuestion + 1) / questions.length) * 100}%`;

    // Clear options
    optionsContainer.innerHTML = '';
    optionsContainer.classList.add('hidden');

    // Type question
    typeText(questionText, question.question, function() {
        showOptions();
    });
}

/**
 * Show options
 */
function showOptions() {
    const question = questions[currentQuestion];
    optionsContainer.classList.remove('hidden');

    question.options.forEach(function(option, index) {
        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.textContent = option.label;
        btn.style.animationDelay = `${index * 0.1}s`;

        btn.addEventListener('click', function() {
            handleAnswer(option.value);
        });

        optionsContainer.appendChild(btn);
    });
}

/**
 * Handle answer
 * @param {number} value - Answer value (1-5)
 */
function handleAnswer(value) {
    const question = questions[currentQuestion];
    answers[question.id] = value;

    console.log(`Answer saved: ${question.id} = ${value}`);

    // Get the selected option's text
    let answerText = '';
    if (question.options && Array.isArray(question.options)) {
        const selectedOption = question.options.find(opt => opt.value === value);
        if (selectedOption) {
            answerText = selectedOption.label;
        }
    }

    // Get currentUserId from window or global scope
    const userId = window.currentUserId || currentUserId;

    // Save answer via AJAX with complete Q&A data
    const formData = new URLSearchParams();
    formData.append('action', 'save_answer');
    formData.append('userid', userId);
    formData.append('question_id', question.id);
    formData.append('value', value);
    formData.append('question_text', question.question);
    formData.append('answer_text', answerText);
    formData.append('question_number', currentQuestion + 1); // 1-based numbering

    fetch('onboarding_learningtype.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success') {
            console.error('Failed to save answer:', data);
        } else {
            console.log('Answer saved successfully to session');
            if (data.qa_saved) {
                console.log('QA text also saved for question ' + (currentQuestion + 1));
            }
        }
    })
    .catch(error => {
        console.error('AJAX error saving answer:', error);
    });

    // Move to next question
    currentQuestion++;

    if (currentQuestion < questions.length) {
        optionsContainer.classList.add('hidden');
        setTimeout(function() {
            showQuestion();
        }, 300);
    } else {
        showResults();
    }
}

/**
 * Calculate results
 * @returns {Object} Results by category
 */
function calculateResults() {
    const categories = {
        '인지': [],
        '감정': [],
        '행동': []
    };

    questions.forEach(function(q) {
        if (answers[q.id]) {
            categories[q.category].push(answers[q.id]);
        }
    });

    const results = {};
    for (let category in categories) {
        const values = categories[category];
        if (values.length > 0) {
            results[category] = values.reduce((a, b) => a + b, 0) / values.length;
        } else {
            results[category] = 0;
        }
    }

    // Calculate total
    const allValues = Object.values(answers);
    results['전체'] = allValues.length > 0 ?
        allValues.reduce((a, b) => a + b, 0) / allValues.length : 0;

    return results;
}

/**
 * Get level description
 * @param {number} score - Score value (0-5)
 * @returns {Object} Level and className
 */
function getLevel(score) {
    if (score >= 4.5) return { level: '매우 우수', className: 'level-excellent' };
    if (score >= 3.5) return { level: '양호', className: 'level-good' };
    if (score >= 2.5) return { level: '보통', className: 'level-average' };
    return { level: '개선 필요', className: 'level-needs-improvement' };
}

/**
 * Get detailed analysis
 * @returns {Object} Weak and strong areas
 */
function getDetailedAnalysis() {
    const weakAreas = [];
    const strongAreas = [];

    questions.forEach(function(q) {
        if (answers[q.id] <= 2) {
            weakAreas.push(q.id);
        } else if (answers[q.id] >= 4) {
            strongAreas.push(q.id);
        }
    });

    return { weakAreas, strongAreas };
}

/**
 * Get area description
 * @param {string} areaId - Area identifier
 * @param {boolean} isStrength - True if strength, false if weakness
 * @returns {string} Description text
 */
function getAreaDescription(areaId, isStrength) {
    const descriptions = {
        strength: {
            'reading': '꼼꼼한 문제 독해',
            'persistence': '높은 문제 집착력',
            'questioning': '적극적인 질문 태도',
            'timeManagement': '우수한 시간 관리',
            'conceptUnderstanding': '깊이 있는 개념 이해',
            'mathAnxiety': '수학에 대한 자신감',
            'motivation': '내적 동기 충만',
            'errorAnalysis': '체계적인 오답 분석',
            'logicalThinking': '논리적 사고력',
            'mathExpression': '명확한 풀이 표현',
            'resilience': '높은 회복탄력성',
            'stressManagement': '우수한 스트레스 관리',
            'studyHabits': '규칙적인 학습 습관',
            'concentration': '뛰어난 집중력',
            'collaboration': '협동 학습 능력',
            'selfDirected': '높은 메타인지'
        },
        weakness: {
            'reading': '문제 읽기 습관 개선',
            'persistence': '끈기와 인내심 향상',
            'questioning': '질문 습관 형성',
            'timeManagement': '체계적 시간 관리',
            'errorAnalysis': '오답 분석 능력',
            'mathAnxiety': '수학 불안감 해소',
            'concentration': '집중력 향상 훈련',
            'conceptUnderstanding': '개념 이해 심화',
            'logicalThinking': '논리적 접근법 연습',
            'mathExpression': '풀이 과정 작성 연습',
            'resilience': '실패 극복 능력',
            'motivation': '학습 동기 강화',
            'stressManagement': '스트레스 대처법',
            'studyHabits': '학습 루틴 확립',
            'collaboration': '협력 학습 기술',
            'selfDirected': '자기 평가 능력'
        }
    };

    const type = isStrength ? 'strength' : 'weakness';
    return descriptions[type][areaId] || '';
}

/**
 * Show results
 */
function showResults() {
    isComplete = true;
    questionScreen.classList.add('hidden');
    resultsScreen.classList.remove('hidden');

    const results = calculateResults();
    const { weakAreas, strongAreas } = getDetailedAnalysis();

    // Display category results
    const categoryResultsDiv = document.getElementById('categoryResults');
    categoryResultsDiv.innerHTML = '';

    ['인지', '감정', '행동'].forEach(function(category) {
        const score = results[category];
        const { level, className } = getLevel(score);
        const icon = category === '인지' ? '🧠' : (category === '감정' ? '❤️' : '⚡');

        const resultCard = document.createElement('div');
        resultCard.className = 'result-card';
        resultCard.innerHTML = `
            <div class="result-header">
                <span class="result-icon">${icon}</span>
                <h3>${category}적 요소</h3>
            </div>
            <div class="result-score">${(score * 20).toFixed(0)}점</div>
            <div class="result-level ${className}">${level}</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${(score / 5) * 100}%"></div>
            </div>
        `;
        categoryResultsDiv.appendChild(resultCard);
    });

    // Display total result
    const totalResultDiv = document.getElementById('totalResult');
    const totalScore = results['전체'];
    const { level: totalLevel, className: totalClassName } = getLevel(totalScore);

    totalResultDiv.innerHTML = `
        <h3 style="margin-bottom: 1rem;">종합 평가</h3>
        <div class="result-score">${(totalScore * 20).toFixed(0)}점</div>
        <div class="result-level ${totalClassName}">${totalLevel}</div>
    `;

    // Display analysis cards
    const analysisCardsDiv = document.getElementById('analysisCards');
    analysisCardsDiv.innerHTML = '';

    // Strengths
    const strengthCard = document.createElement('div');
    strengthCard.className = 'strength-card';
    strengthCard.innerHTML = '<h4>🌟 강점 영역</h4><ul class="area-list">';

    if (strongAreas.length > 0) {
        strongAreas.slice(0, 3).forEach(function(area) {
            const li = document.createElement('li');
            li.textContent = '• ' + getAreaDescription(area, true);
            strengthCard.querySelector('ul').appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = '• 더 많은 연습이 필요합니다';
        strengthCard.querySelector('ul').appendChild(li);
    }

    analysisCardsDiv.appendChild(strengthCard);

    // Weaknesses
    const weaknessCard = document.createElement('div');
    weaknessCard.className = 'weakness-card';
    weaknessCard.innerHTML = '<h4>📚 개선 필요 영역</h4><ul class="area-list">';

    if (weakAreas.length > 0) {
        weakAreas.slice(0, 3).forEach(function(area) {
            const li = document.createElement('li');
            li.textContent = '• ' + getAreaDescription(area, false);
            weaknessCard.querySelector('ul').appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = '• 전반적으로 우수합니다';
        weaknessCard.querySelector('ul').appendChild(li);
    }

    analysisCardsDiv.appendChild(weaknessCard);

    // Debug: Log what we're sending
    console.log('Saving results to database...');
    console.log('Answers:', answers);
    console.log('Results:', results);
    console.log('Weak areas:', weakAreas);
    console.log('Strong areas:', strongAreas);

    // Get currentUserId from window or global scope
    const userId = window.currentUserId || currentUserId;

    // Save results
    fetch('onboarding_learningtype.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=save_results&userid=${userId}&results=${JSON.stringify(results)}&answers=${JSON.stringify(answers)}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Save results response:', data);
        if (data.status === 'success') {
            console.log('Assessment saved successfully with ID:', data.assessment_id);

            // Show success message in UI
            const successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            successDiv.style.cssText = `
                background: #d4edda;
                color: #155724;
                padding: 12px 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #28a745;
                display: flex;
                align-items: center;
                font-weight: 500;
            `;
            successDiv.innerHTML = `
                <span style="font-size: 24px; margin-right: 10px;">✅</span>
                평가가 성공적으로 저장되었습니다! (ID: ${data.assessment_id})
            `;

            // Insert success message at top of results screen
            const resultsTitle = resultsScreen.querySelector('h2');
            if (resultsTitle && resultsTitle.nextSibling) {
                resultsTitle.parentNode.insertBefore(successDiv, resultsTitle.nextSibling);
            } else {
                resultsScreen.insertBefore(successDiv, resultsScreen.firstChild.nextSibling);
            }
        } else {
            console.error('Failed to save assessment:', data.message);
            alert('평가 결과 저장 중 오류가 발생했습니다. 자세한 내용은 콘솔을 확인하세요.');
        }
    })
    .catch(error => {
        console.error('AJAX error saving results:', error);
        alert('네트워크 오류가 발생했습니다. 다시 시도해주세요.');
    });
}

/**
 * Restart assessment
 */
function restartAssessment() {
    // Reset state
    currentQuestion = -1;
    answers = {};
    isComplete = false;
    started = false;

    // Get currentUserId from window or global scope
    const userId = window.currentUserId || currentUserId;

    // Reset server-side session
    fetch('onboarding_learningtype.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=reset_assessment&userid=${userId}`
    });

    // Show welcome screen
    resultsScreen.classList.add('hidden');
    welcomeScreen.classList.remove('hidden');
    startButtonContainer.classList.add('hidden');
    showWelcomeMessage();
}
