// 카이스트 터치수학 학원 학부모 상담 페르소나 생성기
const gradeData = [
    { grade: 'elementary4', name: '초등학교 4학년', mathTopics: ['분수의 기초', '소수점', '도형의 넓이'] },
    { grade: 'elementary5', name: '초등학교 5학년', mathTopics: ['분수의 사칙연산', '소수의 연산', '평면도형'] },
    { grade: 'elementary6', name: '초등학교 6학년', mathTopics: ['비와 비율', '원의 넓이', '입체도형'] },
    { grade: 'middle1', name: '중학교 1학년', mathTopics: ['정수와 유리수', '일차방정식', '좌표평면'] },
    { grade: 'middle2', name: '중학교 2학년', mathTopics: ['연립방정식', '일차함수', '도형의 성질'] },
    { grade: 'middle3', name: '중학교 3학년', mathTopics: ['이차방정식', '이차함수', '삼각비'] },
    { grade: 'high1', name: '고등학교 1학년', mathTopics: ['다항식', '방정식과 부등식', '도형의 방정식'] },
    { grade: 'high2', name: '고등학교 2학년', mathTopics: ['함수의 극한', '미분법', '적분법'] },
    { grade: 'high3', name: '고등학교 3학년', mathTopics: ['확률과 통계', '기하와 벡터', '미적분 심화'] }
];

const levelData = [
    { 
        level: 'extreme_low', 
        name: '극하위권', 
        icon: '😱', 
        description: '기초 연산도 어려움',
        mathContext: '구구단과 기본 연산에서도 어려움을 겪음'
    },
    { 
        level: 'struggling', 
        name: '하위권', 
        icon: '😰', 
        description: '개념 이해 어려움',
        mathContext: '연산 실수가 잦고 문제 해석을 못함'
    },
    { 
        level: 'below_average', 
        name: '중하위권', 
        icon: '😔', 
        description: '60점 미만',
        mathContext: '개념은 아는데 문제 적용이 안됨'
    },
    { 
        level: 'average', 
        name: '중위권', 
        icon: '😐', 
        description: '60~80점',
        mathContext: '기본 문제는 맞지만 응용 문제 틀림'
    },
    { 
        level: 'above_average', 
        name: '중상위권', 
        icon: '😊', 
        description: '80~90점',
        mathContext: '대부분 문제 풀지만 심화 문제 어려워함'
    },
    { 
        level: 'excellent', 
        name: '상위권', 
        icon: '🤩', 
        description: '90점 이상',
        mathContext: '학교 시험은 잘 보지만 경시대회 수준은 부족'
    },
    { 
        level: 'extreme_high', 
        name: '극상위권', 
        icon: '🌟', 
        description: '최상위 1%',
        mathContext: '경시대회 입상 수준의 실력을 보유'
    }
];

const concernData = {
    'study_habits': {
        icon: '📚',
        name: '수학 학습 습관',
        concerns: [
            { id: 'calculation_errors', text: '계산 실수가 너무 많아요', weight: 'high' },
            { id: 'problem_solving', text: '문제 해석을 못해서 틀려요', weight: 'high' },
            { id: 'formula_memorization', text: '공식을 자꾸 까먹어요', weight: 'medium' },
            { id: 'study_time', text: '수학 공부 시간이 부족해요', weight: 'medium' },
            { id: 'homework_completion', text: '수학 숙제를 제때 안해요', weight: 'medium' }
        ]
    },
    'test_performance': {
        icon: '📝',
        name: '시험 성적',
        concerns: [
            { id: 'test_anxiety', text: '시험만 보면 아는 문제도 틀려요', weight: 'high' },
            { id: 'time_management', text: '시험 시간 안에 못 풀어요', weight: 'high' },
            { id: 'careless_mistakes', text: '시험에서 실수로 점수를 잃어요', weight: 'medium' },
            { id: 'grade_decline', text: '수학 성적이 계속 떨어져요', weight: 'high' },
            { id: 'concept_gaps', text: '기본 개념이 부족해서 점수가 안 나와요', weight: 'high' }
        ]
    },
    'motivation': {
        icon: '💪',
        name: '학습 동기',
        concerns: [
            { id: 'math_dislike', text: '수학을 너무 싫어해요', weight: 'high' },
            { id: 'confidence_loss', text: '수학 자신감이 없어요', weight: 'high' },
            { id: 'giving_up', text: '어려우면 바로 포기해요', weight: 'medium' },
            { id: 'comparison_stress', text: '다른 아이들과 비교해서 스트레스받아요', weight: 'medium' },
            { id: 'future_anxiety', text: '수학 때문에 진로가 걱정돼요', weight: 'high' }
        ]
    },
    'learning_method': {
        icon: '🎯',
        name: '학습 방법',
        concerns: [
            { id: 'concept_understanding', text: '개념 이해보다 문제 풀이만 해요', weight: 'medium' },
            { id: 'review_habits', text: '틀린 문제를 다시 안 봐요', weight: 'medium' },
            { id: 'note_taking', text: '수학 노트 정리를 안 해요', weight: 'low' },
            { id: 'question_asking', text: '모르는 걸 질문을 안 해요', weight: 'medium' },
            { id: 'self_study', text: '혼자 공부하는 방법을 몰라요', weight: 'medium' }
        ]
    }
};

let selectedGrade = null;
let selectedLevel = null;
let selectedConcerns = new Set();

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeGrades();
    initializeLevels();
    initializeConcerns();
    updateGenerateButton();
});

// 학년 선택 UI 생성
function initializeGrades() {
    const container = document.getElementById('grade-container');
    container.innerHTML = '';
    
    gradeData.forEach(grade => {
        const button = document.createElement('button');
        button.className = 'grade-button';
        button.textContent = grade.name;
        button.onclick = function(e) {
            e.stopPropagation();
            selectGrade(grade.grade, this);
        };
        container.appendChild(button);
    });
}

// 수준 선택 UI 생성
function initializeLevels() {
    const container = document.getElementById('level-container');
    container.innerHTML = '';
    
    levelData.forEach(level => {
        const button = document.createElement('div');
        button.className = 'level-button';
        button.onclick = function(e) {
            e.stopPropagation();
            selectLevel(level.level, this);
        };
        
        button.innerHTML = `
            <div class="level-content" style="pointer-events: none;">
                <span class="level-icon">${level.icon}</span>
                <span class="level-text">
                    <span class="level-name">${level.name}</span>
                </span>
            </div>
        `;
        
        container.appendChild(button);
    });
}

// 걱정사항 선택 UI 생성
function initializeConcerns() {
    const container = document.getElementById('concerns-container');
    container.innerHTML = '';
    
    Object.entries(concernData).forEach(([categoryKey, category]) => {
        const categoryDiv = document.createElement('div');
        categoryDiv.className = 'concern-category';
        
        const header = document.createElement('div');
        header.className = 'category-header';
        header.innerHTML = `<span>${category.icon}</span> ${category.name}`;
        
        const concernList = document.createElement('div');
        concernList.className = 'concern-list';
        
        category.concerns.forEach(concern => {
            const button = document.createElement('button');
            button.className = 'concern-button';
            button.onclick = function(e) {
                e.stopPropagation();
                toggleConcern(concern.id, this);
            };
            
            const weightIcon = concern.weight === 'high' ? '⚠️' : 
                             concern.weight === 'medium' ? '📍' : '💡';
            
            button.innerHTML = `
                <span style="pointer-events: none;">${concern.text}</span>
                <span class="${concern.weight}-weight" style="pointer-events: none;">${weightIcon}</span>
            `;
            
            concernList.appendChild(button);
        });
        
        categoryDiv.appendChild(header);
        categoryDiv.appendChild(concernList);
        container.appendChild(categoryDiv);
    });
}

// 학년 선택 함수
function selectGrade(grade, buttonElement) {
    selectedGrade = grade;
    
    // UI 업데이트
    document.querySelectorAll('.grade-button').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    buttonElement.classList.add('selected');
    updateGenerateButton();
}

// 수준 선택 함수
function selectLevel(level, buttonElement) {
    selectedLevel = level;
    
    // UI 업데이트
    document.querySelectorAll('.level-button').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    buttonElement.classList.add('selected');
    updateGenerateButton();
}

// 걱정사항 토글 함수
function toggleConcern(concernId, buttonElement) {
    if (selectedConcerns.has(concernId)) {
        selectedConcerns.delete(concernId);
        buttonElement.classList.remove('selected');
    } else {
        selectedConcerns.add(concernId);
        buttonElement.classList.add('selected');
    }
    
    updateGenerateButton();
}

// 생성 버튼 상태 업데이트
function updateGenerateButton() {
    const button = document.getElementById('generate-btn');
    const isValid = selectedGrade && selectedLevel && selectedConcerns.size > 0;
    
    button.disabled = !isValid;
}

// 폼 초기화
function resetForm() {
    selectedGrade = null;
    selectedLevel = null;
    selectedConcerns.clear();
    
    document.querySelectorAll('.grade-button').forEach(btn => btn.classList.remove('selected'));
    document.querySelectorAll('.level-button').forEach(btn => btn.classList.remove('selected'));
    document.querySelectorAll('.concern-button').forEach(btn => btn.classList.remove('selected'));
    
    document.getElementById('result-area').innerHTML = '';
    document.getElementById('prompt-area').innerHTML = '';
    
    updateGenerateButton();
}

// 페르소나 생성 메인 함수
function generatePersona() {
    if (!selectedGrade || !selectedLevel || selectedConcerns.size === 0) {
        alert('모든 항목을 선택해주세요.');
        return;
    }
    
    const persona = buildPersona();
    const prompt = buildPrompt(persona);
    
    displayResults(persona, prompt);
    
    // 결과 영역으로 스크롤
    document.getElementById('result-area').scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
    });
}

// 페르소나 구성 함수
function buildPersona() {
    const gradeInfo = gradeData.find(g => g.grade === selectedGrade);
    const levelInfo = levelData.find(l => l.level === selectedLevel);
    
    // 선택된 걱정사항들 정리
    const selectedConcernsList = [];
    Object.values(concernData).forEach(category => {
        category.concerns.forEach(concern => {
            if (selectedConcerns.has(concern.id)) {
                selectedConcernsList.push({
                    category: category.name,
                    text: concern.text,
                    weight: concern.weight
                });
            }
        });
    });
    
    return {
        grade: gradeInfo,
        level: levelInfo,
        concerns: selectedConcernsList,
        mathContext: getMathContextByGrade(selectedGrade),
        parentProfile: generateParentProfile(selectedLevel, selectedConcernsList)
    };
}

// 학년별 수학 학습 상황 컨텍스트
function getMathContextByGrade(grade) {
    const contexts = {
        'elementary4': {
            currentTopics: ['분수의 기초', '소수점', '도형의 넓이'],
            commonDifficulties: ['분수 개념 이해', '소수점 위치 헷갈림', '넓이 공식 적용'],
            testExamples: ['분수 덧셈뺄셈', '소수 연산', '직사각형 넓이 구하기'],
            studyEpisodes: ['분수 막대 교구로 설명해도 이해 못함', '소수점 자리 실수로 답 틀림', '넓이 공식 외우기만 하고 응용 안됨']
        },
        'elementary5': {
            currentTopics: ['분수의 사칙연산', '소수의 연산', '평면도형'],
            commonDifficulties: ['분수 곱셈나눗셈', '소수 나눗셈', '도형 성질 이해'],
            testExamples: ['분수 혼합계산', '소수 나눗셈', '삼각형 넓이'],
            studyEpisodes: ['분수 나눗셈을 곱셈으로 바꾸는 걸 못함', '소수 나눗셈에서 몫의 위치 헷갈림', '평행사변형 넓이 공식 이해 안됨']
        },
        'elementary6': {
            currentTopics: ['비와 비율', '원의 넓이', '입체도형'],
            commonDifficulties: ['비례식 풀이', '원주율 계산', '부피 공식'],
            testExamples: ['비례식 문제', '원의 넓이 구하기', '직육면체 부피'],
            studyEpisodes: ['비례식 내항외항 개념 이해 안됨', '원의 넓이에서 반지름 구하기 어려워함', '부피 단위 변환 실수']
        },
        'middle1': {
            currentTopics: ['정수와 유리수', '일차방정식', '좌표평면'],
            commonDifficulties: ['음수 계산', '방정식 풀이', '좌표 개념'],
            testExamples: ['유리수 사칙연산', '일차방정식 풀이', '좌표평면에서 점 찾기'],
            studyEpisodes: ['음수 곱셈에서 부호 실수', '방정식 이항할 때 부호 바꾸기 까먹음', '좌표평면에서 x축 y축 헷갈림']
        },
        'middle2': {
            currentTopics: ['연립방정식', '일차함수', '도형의 성질'],
            commonDifficulties: ['연립방정식 풀이', '기울기 개념', '도형 증명'],
            testExamples: ['연립방정식 응용', '일차함수 그래프', '삼각형 합동'],
            studyEpisodes: ['연립방정식 소거법 실수', '기울기 공식 적용 못함', '도형 증명 논리 전개 어려워함']
        },
        'middle3': {
            currentTopics: ['이차방정식', '이차함수', '삼각비'],
            commonDifficulties: ['근의 공식', '포물선 그래프', '삼각비 계산'],
            testExamples: ['이차방정식 풀이', '이차함수 최댓값', '삼각비 활용'],
            studyEpisodes: ['판별식 계산 실수', '포물선 축과 꼭짓점 헷갈림', '삼각비 표 활용 못함']
        },
        'high1': {
            currentTopics: ['다항식', '방정식과 부등식', '도형의 방정식'],
            commonDifficulties: ['인수분해', '절댓값 부등식', '직선과 원의 방정식'],
            testExamples: ['인수분해', '부등식 풀이', '원의 방정식 구하기'],
            studyEpisodes: ['복잡한 인수분해 못함', '절댓값 부등식 경우 나누기 어려워함', '원의 방정식 표준형 이해 안됨']
        },
        'high2': {
            currentTopics: ['함수의 극한', '미분법', '적분법'],
            commonDifficulties: ['극한 계산', '미분 공식', '적분 계산'],
            testExamples: ['극한값 구하기', '도함수 구하기', '정적분 계산'],
            studyEpisodes: ['무한대 극한에서 최고차항 개념 이해 안됨', '곱의 미분법 적용 실수', '적분 상수 놓치기']
        },
        'high3': {
            currentTopics: ['확률과 통계', '기하와 벡터', '미적분 심화'],
            commonDifficulties: ['확률 계산', '벡터 연산', '미적분 응용'],
            testExamples: ['확률 문제', '벡터 내적', '미적분 활용'],
            studyEpisodes: ['경우의 수 중복 계산', '벡터 성분 분해 못함', '미적분 응용 문제 해석 어려워함']
        }
    };
    
    return contexts[grade] || contexts['middle1'];
}

// 학부모 프로필 생성
function generateParentProfile(level, concerns) {
    const profiles = {
        'struggling': {
            personality: '걱정이 많고 조급한 성격',
            speechPattern: '한숨 섞인 말투, "도대체 왜", "이러다가 어쩌죠" 같은 표현',
            background: '아이 수학 성적 때문에 스트레스가 극심함',
            expectations: '일단 기본기라도 확실히 잡았으면 좋겠음'
        },
        'below_average': {
            personality: '현실적이지만 약간 조급한 성격',
            speechPattern: '"솔직히 말하면", "그런데 말이죠" 같은 표현',
            background: '아이가 수학을 어려워하는 걸 알고 있음',
            expectations: '평균 정도만 되었으면 좋겠음'
        },
        'average': {
            personality: '안정적이지만 더 나은 성과를 바라는 성격',
            speechPattern: '"괜찮긴 한데", "조금 더 잘했으면" 같은 표현',
            background: '현재 성적에 큰 불만은 없지만 향상을 원함',
            expectations: '상위권 진입을 목표로 함'
        },
        'above_average': {
            personality: '성취지향적이고 체계적인 성격',
            speechPattern: '"더 체계적으로", "효율적으로" 같은 표현',
            background: '아이가 수학을 잘하지만 더 발전시키고 싶음',
            expectations: '최상위권 진입과 심화 학습'
        },
        'excellent': {
            personality: '완벽주의적이고 목표 지향적인 성격',
            speechPattern: '"최고 수준으로", "완벽하게" 같은 표현',
            background: '아이의 수학 실력을 더욱 극대화하고 싶음',
            expectations: '수학 영재 수준까지 끌어올리고 싶음'
        }
    };
    
    return profiles[level] || profiles['average'];
}

// 프롬프트 생성 함수
function buildPrompt(persona) {
    const { grade, level, concerns, mathContext, parentProfile } = persona;
    
    const concernTexts = concerns.map(c => `"${c.text}"`).join(', ');
    const mathTopics = mathContext.currentTopics.join(', ');
    const studyEpisodes = mathContext.studyEpisodes.join(' / ');
    
    return `당신은 카이스트 터치수학 학원에 자녀 상담을 받으러 온 학부모입니다.

## 기본 정보
- 자녀 학년: ${grade.name}
- 수학 수준: ${level.name} (${level.description})
- 현재 학습 단원: ${mathTopics}
- 학부모 성격: ${parentProfile.personality}

## 주요 걱정사항
${concernTexts}

## 구체적인 수학 학습 에피소드
${studyEpisodes}

## 최근 시험 상황
- 주요 어려움: ${mathContext.commonDifficulties.join(', ')}
- 대표 문제 유형: ${mathContext.testExamples.join(', ')}

## 대화 스타일
- 말투: ${parentProfile.speechPattern}
- 기대치: ${parentProfile.expectations}
- 배경: ${parentProfile.background}

## 상담 진행 방식
1. 처음에는 일반적인 고민을 털어놓되, 점차 구체적인 에피소드를 말하기
2. 아이의 수학 공부 과정에서 벌어지는 실제 상황들을 상세히 설명
3. 시험 상황에서의 구체적인 문제점들을 언급
4. 가정에서의 수학 학습 모습을 리얼하게 묘사
5. 학원 선생님의 조언을 간절히 구하는 모습 표현

## 대화 예시 상황들
- "어제도 분수 문제 하나 때문에 2시간 동안 울었어요"
- "시험에서 아는 문제인데 시간이 부족해서 못 풀었다고 하더라고요"
- "개념은 이해한다고 하는데 문제만 보면 어디서부터 시작해야 할지 모르겠다고 해요"
- "다른 아이들은 다 100점인데 저희 아이만 80점이라서..."

자연스럽고 리얼한 학부모 상담 대화를 진행해주세요. 선생님의 질문에 따라 점차 더 구체적인 상황들을 털어놓으면 됩니다.

**중요: 대화 외의 글은 생성하지 마세요.**`;
}

// 결과 화면 표시
function displayResults(persona, prompt) {
    const resultArea = document.getElementById('result-area');
    const promptArea = document.getElementById('prompt-area');
    
    // 결과 요약 표시
    resultArea.innerHTML = `
        <div class="card">
            <h2 class="card-title">
                <span>👤</span>
                생성된 학부모 페르소나
            </h2>
            <div class="result-section">
                <div class="result-box">
                    <h3><span>🎓</span> 자녀 기본 정보</h3>
                    <p><strong>학년:</strong> ${persona.grade.name}</p>
                    <p><strong>수학 수준:</strong> ${persona.level.name} - ${persona.level.description}</p>
                    <p><strong>현재 학습 내용:</strong> ${persona.mathContext.currentTopics.join(', ')}</p>
                </div>
                
                <div class="result-box">
                    <h3><span>😰</span> 주요 걱정사항</h3>
                    <p>${persona.concerns.map(c => `<span class="concern-tag">${c.text}</span>`).join('')}</p>
                </div>
                
                <div class="result-box">
                    <h3><span>💭</span> 학부모 특성</h3>
                    <p><strong>성격:</strong> ${persona.parentProfile.personality}</p>
                    <p><strong>말투:</strong> ${persona.parentProfile.speechPattern}</p>
                    <p><strong>기대치:</strong> ${persona.parentProfile.expectations}</p>
                </div>
            </div>
        </div>
    `;
    
    // 프롬프트 표시
    promptArea.innerHTML = `
        <div class="card">
            <h2 class="card-title">
                <span>🤖</span>
                생성된 프롬프트
            </h2>
            
            <div class="prompt-display">
                <div class="prompt-text" id="final-prompt-text">${prompt}</div>
            </div>
            
            <!-- 추가 프롬프트 입력 영역 -->
            <div class="custom-prompt-section">
                <h3 class="custom-prompt-title">
                    <span>✏️</span>
                    사용자의 추가 프롬프트
                </h3>
                <textarea 
                    id="custom-prompt-input" 
                    class="custom-prompt-input" 
                    placeholder="특별히 강조하고 싶은 상황이나 추가 지시사항을 입력하세요...&#10;예: '아이가 특히 도형 문제를 어려워합니다', '최근 학원을 바꿔야 할지 고민 중입니다' 등"
                    rows="3"
                ></textarea>
            </div>
            
            <div class="prompt-actions">
                <button onclick="copyPrompt()" class="btn-copy">
                    <span>📋</span>
                    <span style="margin-left: 8px;">프롬프트 복사</span>
                </button>
                <button onclick="startConversation()" class="btn-start">
                    <span>💬</span>
                    <span style="margin-left: 8px;">대화 시작하기</span>
                </button>
            </div>
            
            <div class="tips-box">
                <div class="tips-title">
                    <span>💡</span>
                    상담 진행 팁
                </div>
                <div class="tip-grid">
                    <div class="good-example">
                        <div class="example-label">✅ 좋은 질문</div>
                        <div class="example-text">"어떤 부분에서 가장 어려워하시나요?"</div>
                    </div>
                    <div class="bad-example">
                        <div class="example-label">❌ 피할 질문</div>
                        <div class="example-text">"성적이 왜 안 나오죠?"</div>
                    </div>
                    <div class="good-example">
                        <div class="example-label">✅ 좋은 접근</div>
                        <div class="example-text">"구체적인 예시를 들어주세요"</div>
                    </div>
                    <div class="bad-example">
                        <div class="example-label">❌ 피할 접근</div>
                        <div class="example-text">"다른 아이들과 비교하면..."</div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 추가 프롬프트 입력 시 실시간 업데이트
    document.getElementById('custom-prompt-input').addEventListener('input', function() {
        updateFinalPrompt(prompt);
    });
}

// 애니메이션 시퀀스 표시
function showAnimatedSequence() {
    const loadingDiv = document.getElementById('loading-animation');
    const gradeInfo = gradeData.find(g => g.grade === selectedGrade);
    const levelInfo = levelData.find(l => l.level === selectedLevel);
    
    // 선택된 걱정사항들 정리
    const selectedConcernsList = [];
    Object.values(concernData).forEach(category => {
        category.concerns.forEach(concern => {
            if (selectedConcerns.has(concern.id)) {
                selectedConcernsList.push(concern.text);
            }
        });
    });
    
    loadingDiv.innerHTML = `
        <div class="sequence-content">
            <h2 class="sequence-title">선택하신 정보를 확인하고 있습니다...</h2>
            <div class="sequence-items" id="sequence-items">
                <!-- 동적으로 추가될 항목들 -->
            </div>
            <div class="past-experience-section hidden" id="past-experience-section">
                <h3 class="past-experience-title">
                    <span>💭</span>
                    이와 유사한 과거의 경험을 떠올려보세요
                </h3>
                <p class="past-experience-desc">실제로 만났던 학부모나 상담 경험을 구체적으로 떠올리면 더욱 실감나는 대화가 가능합니다.</p>
                <textarea 
                    id="past-experience-input" 
                    class="past-experience-input" 
                    placeholder="예: 작년에 만났던 김○○ 학부모님은 아이가 수학 시험만 보면 긴장해서 실수를 많이 한다고 하셨는데...\n그때 상담하면서 느꼈던 감정이나 대화 내용을 떠올려보세요."
                    rows="4"
                ></textarea>
                <button onclick="proceedToChat()" class="btn-proceed">
                    <span>💬</span>
                    <span style="margin-left: 8px;">대화 시작하기</span>
                </button>
            </div>
        </div>
    `;
    loadingDiv.classList.remove('hidden');
    
    // 순차적으로 항목 표시
    const items = [
        { icon: '🎓', label: '학년', value: gradeInfo.name },
        { icon: '📊', label: '학업 수준', value: levelInfo.name },
        ...selectedConcernsList.map((concern, index) => ({
            icon: '😟',
            label: `걱정사항 ${index + 1}`,
            value: concern
        }))
    ];
    
    displayItemsSequentially(items);
}

// 로딩 애니메이션 숨김
function hideLoadingAnimation() {
    document.getElementById('loading-animation').classList.add('hidden');
}

// 항목들을 순차적으로 표시
function displayItemsSequentially(items) {
    const container = document.getElementById('sequence-items');
    let currentIndex = 0;
    
    function displayNextItem() {
        if (currentIndex < items.length) {
            const item = items[currentIndex];
            const itemDiv = document.createElement('div');
            itemDiv.className = 'sequence-item fade-in';
            itemDiv.innerHTML = `
                <span class="sequence-item-icon">${item.icon}</span>
                <span class="sequence-item-label">${item.label}:</span>
                <span class="sequence-item-value">${item.value}</span>
            `;
            container.appendChild(itemDiv);
            currentIndex++;
            
            // 다음 항목 표시
            setTimeout(displayNextItem, 800);
        } else {
            // 모든 항목 표시 완료 후 경험 입력 영역 표시
            setTimeout(() => {
                document.getElementById('past-experience-section').classList.remove('hidden');
                document.getElementById('past-experience-input').focus();
            }, 1000);
        }
    }
    
    displayNextItem();
}

// ChatGPT로 이동
function proceedToChat() {
    const pastExperience = document.getElementById('past-experience-input').value.trim();
    const finalPromptText = document.getElementById('final-prompt-text').textContent;
    
    let fullPrompt = finalPromptText;
    if (pastExperience) {
        fullPrompt += `\n\n## 교사의 과거 경험\n${pastExperience}`;
    }
    fullPrompt += '\n\n**중요: 대화 외의 글은 생성하지 마세요.**';
    
    // 전환 애니메이션
    const sequenceContent = document.querySelector('.sequence-content');
    sequenceContent.innerHTML = `
        <div class="transition-content">
            <div class="spinner"></div>
            <p class="transition-text">ChatGPT로 이동 중...</p>
            <p class="transition-subtext">곧 상담이 시작됩니다</p>
        </div>
    `;
    
    setTimeout(() => {
        const chatGPTUrl = `https://chat.openai.com/?q=${encodeURIComponent(fullPrompt)}`;
        window.open(chatGPTUrl, '_blank');
        hideLoadingAnimation();
    }, 1500);
}

// 추가 프롬프트 포함하여 최종 프롬프트 업데이트
function updateFinalPrompt(basePrompt) {
    const customPrompt = document.getElementById('custom-prompt-input').value.trim();
    const finalPromptElement = document.getElementById('final-prompt-text');
    
    let finalPrompt = basePrompt;
    if (customPrompt) {
        finalPrompt = basePrompt + '\n\n## 추가 상황\n' + customPrompt;
    }
    
    finalPromptElement.textContent = finalPrompt;
}

// 프롬프트 복사 함수
function copyPrompt() {
    const finalPromptText = document.getElementById('final-prompt-text').textContent;
    
    navigator.clipboard.writeText(finalPromptText).then(() => {
        // 복사 완료 피드백
        const copyBtn = document.querySelector('.btn-copy');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<span>✅</span><span style="margin-left: 8px;">복사 완료!</span>';
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
        }, 2000);
    }).catch(err => {
        console.error('복사 실패:', err);
        alert('복사에 실패했습니다. 프롬프트를 직접 선택해서 복사해주세요.');
    });
}

// 대화 시작 함수
function startConversation() {
    showAnimatedSequence();
}